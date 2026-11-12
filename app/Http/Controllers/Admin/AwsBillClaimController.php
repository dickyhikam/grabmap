<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwsAccount;
use App\Models\AwsBillClaim;
use App\Models\AwsBillClaimActivity;
use App\Models\AwsBillClaimItem;
use App\Models\AwsInvoiceSummary;
use App\Models\ServiceCharge;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Pelacak pengajuan penggantian tagihan AWS per akun: bulan mana yang belum
 * diajukan, sedang diajukan, atau sudah dibayar. Sumber angkanya invoice resmi
 * AWS (aws_invoice_summaries), jadi Rupiah-nya sama dengan yang terpotong di kartu.
 * Hanya aktif untuk akun yang menyalakan "Pengajuan tagihan" di form edit.
 */
class AwsBillClaimController extends Controller
{
    private const PROOF_DISK = 'local';

    public function index(AwsAccount $awsAccount)
    {
        if (!$awsAccount->claims_enabled) {
            return redirect()->route('admin.aws-accounts.billing', $awsAccount)->with('error', __('awsclaims.disabled'));
        }

        $claims = AwsBillClaim::with('items')
            ->where('aws_account_id', $awsAccount->id)
            ->orderByDesc('number')
            ->get();

        return view('admin.aws-accounts.claims', [
            'account'    => $awsAccount,
            'rows'       => $this->monthRows($awsAccount, $claims),
            'claims'     => $claims,
            'feePercent' => (float) (ServiceCharge::ruleFor($awsAccount->id, null, now()->format('Y-m-d'))?->percent ?? 0),
            'vatRate'    => (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11)),
            'canEdit'    => (bool) auth()->user()?->can('aws_accounts.update'),
            'activities' => AwsBillClaimActivity::where('aws_account_id', $awsAccount->id)
                ->latest('created_at')->latest('id')->limit(50)->get(),
        ]);
    }

    public function store(Request $request, AwsAccount $awsAccount)
    {
        abort_unless($awsAccount->claims_enabled, 404);

        $data = $request->validate([
            'months'       => ['required', 'array', 'min:1'],
            'months.*'     => ['required', 'date_format:Y-m'],
            'include_fee'  => ['nullable', 'boolean'],
            'fee_percent'  => ['nullable', 'required_if:include_fee,1', 'numeric', 'min:0', 'max:100'],
            'include_vat'  => ['nullable', 'boolean'],
            'status'       => ['required', 'in:' . AwsBillClaim::SUBMITTED . ',' . AwsBillClaim::PAID],
            'submitted_on' => ['required', 'date'],
            'paid_on'      => ['nullable', 'required_if:status,' . AwsBillClaim::PAID, 'date', 'after_or_equal:submitted_on'],
            'note'         => ['nullable', 'string', 'max:500'],
        ], [
            'months.required' => __('awsclaims.err_months'),
            'paid_on.after_or_equal' => __('awsclaims.err_paid_before'),
        ]);

        $includeFee = $request->boolean('include_fee');
        $includeVat = $request->boolean('include_vat');
        $feePct = $includeFee ? (float) $data['fee_percent'] : 0.0;
        $vatRate = $includeVat ? (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11)) : 0.0;

        // Hanya bulan yang invoice resminya sudah terbit dan belum diajukan.
        $months = collect($data['months'])->unique()->map(fn ($m) => Carbon::createFromFormat('Y-m-d', $m . '-01')->startOfDay());
        $invoices = $this->mainInvoices($awsAccount)->keyBy(fn ($i) => $i->billing_month->format('Y-m'));
        $taken = AwsBillClaimItem::where('aws_account_id', $awsAccount->id)->get()->map(fn ($i) => $i->month->format('Y-m'))->all();

        $items = [];
        foreach ($months as $m) {
            $key = $m->format('Y-m');
            $inv = $invoices->get($key);
            if (!$inv || in_array($key, $taken, true)) {
                return back()->withInput()->with('error', __('awsclaims.err_month_unavailable', ['month' => $m->translatedFormat('F Y')]));
            }

            $aws = (float) $inv->idrAmount();
            $fee = $aws * $feePct / 100;
            // VAT dibulatkan ke rupiah per baris, seperti lembar invoice Alfa.
            $vat = round(($aws + $fee) * $vatRate);
            $items[] = [
                'aws_account_id' => $awsAccount->id,
                'month'          => $m->format('Y-m-d'),
                'invoice_id'     => $inv->invoice_id,
                'usd'            => (float) $inv->base_amount,
                'aws_idr'        => $aws,
                'fee_idr'        => $fee,
                'vat_idr'        => $vat,
                'total_idr'      => $aws + $fee + $vat,
            ];
        }

        $paid = $data['status'] === AwsBillClaim::PAID;

        try {
            $claim = DB::transaction(function () use ($awsAccount, $items, $includeFee, $feePct, $includeVat, $vatRate, $data, $paid, $request) {
                $claim = AwsBillClaim::create([
                    'aws_account_id' => $awsAccount->id,
                    'number'         => AwsBillClaim::nextNumber($awsAccount->id),
                    'status'         => $data['status'],
                    'include_fee'    => $includeFee,
                    'fee_percent'    => $feePct,
                    'include_vat'    => $includeVat,
                    'vat_rate'       => $vatRate,
                    'aws_idr'        => array_sum(array_column($items, 'aws_idr')),
                    'fee_idr'        => array_sum(array_column($items, 'fee_idr')),
                    'vat_idr'        => array_sum(array_column($items, 'vat_idr')),
                    'total_idr'      => array_sum(array_column($items, 'total_idr')),
                    'total_usd'      => array_sum(array_column($items, 'usd')),
                    'submitted_on'   => $data['submitted_on'],
                    'paid_on'        => $paid ? $data['paid_on'] : null,
                    'note'           => $data['note'] ?? null,
                    'created_by'     => optional($request->user())->name ?? 'admin',
                ]);
                $claim->items()->createMany($items);

                AwsBillClaimActivity::record($claim, $paid ? AwsBillClaimActivity::CREATED_PAID : AwsBillClaimActivity::CREATED, array_filter([
                    'total'   => $claim->total_idr,
                    'months'  => array_map(fn ($i) => substr($i['month'], 0, 7), $items),
                    'paid_on' => $paid ? $data['paid_on'] : null,
                ]));

                return $claim;
            });
        } catch (QueryException) {
            // Indeks unik akun + bulan: bulan yang sama diajukan bersamaan dari tab lain.
            return back()->withInput()->with('error', __('awsclaims.err_conflict'));
        }

        return redirect()->route('admin.aws-accounts.claims', $awsAccount)
            ->with('success', __($paid ? 'awsclaims.created_paid' : 'awsclaims.created', ['code' => $claim->code()]));
    }

    public function markPaid(Request $request, AwsAccount $awsAccount, AwsBillClaim $claim)
    {
        $this->ensureOwned($awsAccount, $claim);

        // Galat dikirim sebagai pesan biasa: modal "tandai dibayar" dibuka per
        // pengajuan, jadi tidak bisa dibuka ulang otomatis seperti formulir lain.
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'paid_on' => ['required', 'date', 'after_or_equal:' . $claim->submitted_on->format('Y-m-d')],
            'proof'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ], ['paid_on.after_or_equal' => __('awsclaims.err_paid_before')]);
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }
        $data = $validator->validated();

        if ($file = $request->file('proof')) {
            $this->deleteProof($claim);
            $claim->proof_path = $file->store('aws-claims/' . $awsAccount->getRouteKey(), self::PROOF_DISK);
            $claim->proof_name = mb_substr($file->getClientOriginalName(), 0, 250);
        }

        $claim->status = AwsBillClaim::PAID;
        $claim->paid_on = $data['paid_on'];
        $claim->save();

        AwsBillClaimActivity::record($claim, AwsBillClaimActivity::PAID, array_filter([
            'paid_on' => $data['paid_on'],
            'proof'   => $request->hasFile('proof') ? $claim->proof_name : null,
        ]));

        return back()->with('success', __('awsclaims.marked_paid', ['code' => $claim->code()]));
    }

    /** Salah tandai: kembali ke "diajukan". Bukti transfer ikut dihapus. */
    public function markUnpaid(AwsAccount $awsAccount, AwsBillClaim $claim)
    {
        $this->ensureOwned($awsAccount, $claim);

        $this->deleteProof($claim);
        $claim->forceFill(['status' => AwsBillClaim::SUBMITTED, 'paid_on' => null, 'proof_path' => null, 'proof_name' => null])->save();
        AwsBillClaimActivity::record($claim, AwsBillClaimActivity::UNPAID);

        return back()->with('success', __('awsclaims.marked_unpaid', ['code' => $claim->code()]));
    }

    /** Batalkan pengajuan: bulannya kembali "belum diajukan". */
    public function destroy(AwsAccount $awsAccount, AwsBillClaim $claim)
    {
        $this->ensureOwned($awsAccount, $claim);

        $code = $claim->code();
        AwsBillClaimActivity::record($claim->loadMissing('items'), AwsBillClaimActivity::CANCELLED, [
            'total'  => $claim->total_idr,
            'months' => $claim->items->map(fn ($i) => $i->month->format('Y-m'))->all(),
        ]);
        $this->deleteProof($claim);
        $claim->delete();

        return back()->with('success', __('awsclaims.cancelled', ['code' => $code]));
    }

    public function proof(AwsAccount $awsAccount, AwsBillClaim $claim)
    {
        $this->ensureOwned($awsAccount, $claim);
        abort_unless($claim->proof_path && Storage::disk(self::PROOF_DISK)->exists($claim->proof_path), 404);

        return Storage::disk(self::PROOF_DISK)->response($claim->proof_path, $claim->proof_name, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Sampul pengajuan (PDF): ringkasan, rincian per bulan, dan daftar lampiran.
     * Halaman ini lalu digabung dengan PDF invoice asli AWS di browser (lihat
     * attachments()). Riwayat aktivitas sengaja tidak ikut, cukup di halaman.
     */
    public function pdf(AwsAccount $awsAccount, AwsBillClaim $claim)
    {
        $this->ensureOwned($awsAccount, $claim);
        $claim->load('items');

        $summaries = AwsInvoiceSummary::where('aws_account_id', $awsAccount->id)
            ->whereIn('invoice_id', $claim->items->pluck('invoice_id')->filter())
            ->get()->keyBy('invoice_id');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.aws-accounts.claim-pdf', [
            'account'    => $awsAccount,
            'claim'      => $claim,
            'summaries'  => $summaries,
            'printedBy'  => auth()->user()?->name,
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);

        return $pdf->stream($this->pdfName($awsAccount, $claim), ['Attachment' => false]);
    }

    /**
     * PDF invoice asli AWS + lampirannya untuk setiap bulan di pengajuan, sebagai
     * base64 dalam satu JSON. Diambil server (URL S3 AWS tidak boleh dibaca
     * langsung oleh browser karena CORS) lalu digabung dengan sampul di browser.
     */
    public function attachments(AwsAccount $awsAccount, AwsBillClaim $claim)
    {
        $this->ensureOwned($awsAccount, $claim);
        $service = \App\Services\AwsLocationService::forAccount($awsAccount);

        $files = [];
        $errors = [];
        foreach ($claim->items()->orderBy('month')->get() as $it) {
            if (!$it->invoice_id) {
                continue;
            }
            $docs = $service->invoicePdfUrl($it->invoice_id);
            if (!$docs['url']) {
                $errors[] = $it->invoice_id . ': ' . ($docs['denied'] ? __('awsbilling.bs_pdf_denied') : $docs['error']);
                continue;
            }
            foreach (array_merge([$docs['url']], $docs['supplemental']) as $n => $url) {
                $res = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
                if (!$res->successful() || !str_starts_with($res->body(), '%PDF')) {
                    $errors[] = $it->invoice_id . ($n ? ' #' . $n : '') . ': HTTP ' . $res->status();
                    continue;
                }
                $files[] = [
                    'invoice' => $it->invoice_id,
                    'label'   => $n ? __('awsclaims.docs_supp', ['n' => $n]) : __('awsclaims.docs_pdf'),
                    'data'    => base64_encode($res->body()),
                ];
            }
        }

        return response()->json(['files' => $files, 'errors' => $errors, 'name' => $this->pdfName($awsAccount, $claim)])
            ->header('Cache-Control', 'private, no-store');
    }

    // ---------------------------------------------------------------- bantu

    private function pdfName(AwsAccount $account, AwsBillClaim $claim): string
    {
        return $claim->code() . '-' . \Illuminate\Support\Str::slug($account->name) . '.pdf';
    }

    /** @return Collection<int, AwsInvoiceSummary> invoice utama ber-Rupiah, terbaru dulu */
    private function mainInvoices(AwsAccount $account): Collection
    {
        return AwsInvoiceSummary::where('aws_account_id', $account->id)
            ->where('invoice_type', '!=', 'CREDIT_MEMO')
            ->orderByDesc('billing_month')
            ->get()
            ->filter(fn ($i) => ($i->idrAmount() ?? 0) > 0)
            ->unique(fn ($i) => $i->billing_month->format('Y-m'))
            ->values();
    }

    /**
     * Satu baris per bulan: bulan berjalan (pending) di atas, lalu bulan yang
     * invoicenya sudah terbit. Bulan bernilai nol tidak ditampilkan.
     */
    private function monthRows(AwsAccount $account, Collection $claims): array
    {
        $claimByMonth = [];
        $itemByMonth = [];
        foreach ($claims as $c) {
            foreach ($c->items as $it) {
                $claimByMonth[$it->month->format('Y-m')] = $c;
                $itemByMonth[$it->month->format('Y-m')] = $it;
            }
        }

        $rows = [];
        $invoices = $this->mainInvoices($account);
        $current = now()->startOfMonth();
        if (!$invoices->contains(fn ($i) => $i->billing_month->isSameMonth($current))) {
            $rows[] = ['month' => $current, 'status' => 'pending', 'invoice' => null, 'claim' => null, 'item' => null];
        }

        foreach ($invoices as $inv) {
            $key = $inv->billing_month->format('Y-m');
            $claim = $claimByMonth[$key] ?? null;
            $rows[] = [
                'month'   => $inv->billing_month,
                'status'  => !$claim ? 'unclaimed' : ($claim->isPaid() ? 'paid' : 'submitted'),
                'invoice' => $inv,
                'claim'   => $claim,
                'item'    => $itemByMonth[$key] ?? null,
            ];
        }

        return $rows;
    }

    private function ensureOwned(AwsAccount $account, AwsBillClaim $claim): void
    {
        abort_unless($account->claims_enabled && (int) $claim->aws_account_id === (int) $account->id, 404);
    }

    private function deleteProof(AwsBillClaim $claim): void
    {
        if ($claim->proof_path) {
            Storage::disk(self::PROOF_DISK)->delete($claim->proof_path);
        }
    }
}
