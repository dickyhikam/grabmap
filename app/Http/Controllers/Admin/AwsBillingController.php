<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKeyUsageDaily;
use App\Models\AwsAccount;
use App\Models\AwsBillingShare;
use App\Models\AwsBillingSplit;
use App\Models\AwsInvoice;
use App\Models\AwsInvoiceSummary;
use App\Models\AwsServiceCostMonthly;
use App\Models\AwsUsageTypeDaily;
use App\Models\CompanyApiKey;
use App\Models\ExchangeRate;
use App\Models\MatrixRouteDaily;
use App\Models\Setting;
use App\Services\AwsLocationService;
use App\Services\BillingExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tagihan satu akun AWS per bulan, disusun seperti PDF invoice AWS: biaya per
 * service (termasuk PPN), rincian Amazon Location per usage type, porsi tiap
 * API key, lalu dicocokkan dengan angka yang tertulis di invoice.
 *
 * Halaman ini hanya membaca database. AWS ditembak lewat tombol Refresh atau
 * scheduler usage:refresh.
 */
class AwsBillingController extends Controller
{
    /** Bulan terjauh yang bisa dipilih; CloudWatch harian bertahan ±15 bulan. */
    private const MONTHS_BACK = 12;

    /**
     * Operasi yang selalu ditampilkan di rincian per API key, per kategori —
     * baris nol tetap muncul supaya susunannya sama tiap bulan. Operasi lain
     * yang kebetulan terpakai ditambahkan ke kategorinya.
     */
    private const OP_GROUPS = [
        'Maps'   => ['GetTile', 'GetMapStyleDescriptor'],
        'Places' => ['SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace'],
        'Routes' => ['CalculateRoutes', 'CalculateRouteMatrix'],
    ];

    /** Selisih invoice yang masih dianggap cocok (pembulatan sen). */
    private const MATCH_TOLERANCE = 0.05;

    /**
     * Berapa bulan ke belakang biaya per layanan ikut ditarik saat "Tarik ulang".
     * Cost Explorer menyimpan riwayat ~13 bulan dan menagih per request, bukan per
     * bulan, jadi rentang ini gratis dibanding menarik satu bulan saja.
     */
    private const COST_HISTORY_MONTHS = 12;

    public function show(Request $request, AwsAccount $awsAccount)
    {
        return view('admin.aws-accounts.billing', $this->billingData($awsAccount, $request->query('month')) + [
            'public' => false,
            'share'  => AwsBillingShare::where('aws_account_id', $awsAccount->id)->first(),
        ]);
    }

    /**
     * Halaman Tagihan versi publik: isi sama dengan admin, hanya-baca, tanpa
     * login. Link mati begitu dinonaktifkan atau diganti token baru.
     */
    public function publicShow(Request $request, string $token)
    {
        $share = AwsBillingShare::with('account')->where('token', $token)->where('is_active', true)->first();
        abort_if(!$share || !$share->account, 404);

        $this->applyPublicLocale($request);
        $share->recordView();

        return view('admin.aws-accounts.billing', $this->billingData($share->account, $request->query('month')) + [
            'public' => true,
            'share'  => $share,
        ]);
    }

    /** Export (Excel/CSV) dari halaman Tagihan admin. Pilihan isi datang dari modal Export. */
    public function export(Request $request, AwsAccount $awsAccount)
    {
        return $this->exportResponse($request, $awsAccount);
    }

    /** Export yang sama dari link publik (hanya-baca, token rahasia). */
    public function publicExport(Request $request, string $token)
    {
        $share = AwsBillingShare::with('account')->where('token', $token)->where('is_active', true)->first();
        abort_if(!$share || !$share->account, 404);

        $this->applyPublicLocale($request);

        return $this->exportResponse($request, $share->account);
    }

    private function exportResponse(Request $request, AwsAccount $account)
    {
        $rate = (float) str_replace(',', '.', (string) $request->query('rate', ''));
        $data = $this->billingData($account, $request->query('month'), $rate > 0 ? $rate : null);
        abort_unless($data['split'] && $data['detail'], 404);

        $export = new BillingExport($data, BillingExport::optionsFrom($request, $data));
        $name = 'tagihan-aws-' . Str::slug($account->name) . '-' . $data['start']->format('Y-m') . '.' . $export->extension();

        return response($export->render(), 200, [
            'Content-Type'        => $export->contentType(),
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'X-Robots-Tag'        => 'noindex, nofollow',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    /** Aktifkan link publik akun ini (buat kalau belum ada). */
    public function shareEnable(Request $request, AwsAccount $awsAccount)
    {
        $share = AwsBillingShare::firstOrNew(['aws_account_id' => $awsAccount->id]);
        if (!$share->exists) {
            $share->token = AwsBillingShare::newToken();
            $share->created_by = optional($request->user())->name ?? 'admin';
        }
        $share->is_active = true;
        $share->save();

        return $this->backToBilling($request, $awsAccount)->with('success', __('awsbilling.share_enabled'));
    }

    public function shareDisable(Request $request, AwsAccount $awsAccount)
    {
        AwsBillingShare::where('aws_account_id', $awsAccount->id)->update(['is_active' => false]);

        return $this->backToBilling($request, $awsAccount)->with('success', __('awsbilling.share_disabled'));
    }

    /** Token baru: link lama langsung tidak bisa dibuka lagi. */
    public function shareRegenerate(Request $request, AwsAccount $awsAccount)
    {
        $share = AwsBillingShare::where('aws_account_id', $awsAccount->id)->firstOrFail();
        $share->update(['token' => AwsBillingShare::newToken(), 'is_active' => true, 'view_count' => 0, 'last_viewed_at' => null]);

        return $this->backToBilling($request, $awsAccount)->with('success', __('awsbilling.share_regenerated'));
    }

    private function backToBilling(Request $request, AwsAccount $account)
    {
        [$start] = $this->resolveMonth($request->input('month'));

        return redirect()->route('admin.aws-accounts.billing', [$account, 'month' => $start->format('Y-m'), 'share' => 1]);
    }

    /** Bahasa halaman publik: ?lang= → pilihan sebelumnya → bawaan aplikasi (sama dengan laporan pemakaian). */
    private function applyPublicLocale(Request $request): void
    {
        $supported = ['en', 'id'];
        $lang = $request->query('lang');
        if (!in_array($lang, $supported, true)) {
            $lang = $request->session()->get('report_lang');
        }
        if (!in_array($lang, $supported, true)) {
            $lang = config('app.locale');
        }
        $request->session()->put('report_lang', $lang);
        app()->setLocale($lang);
    }

    /** Semua angka halaman Tagihan satu akun dan satu bulan — dipakai admin maupun publik. */
    private function billingData(AwsAccount $awsAccount, ?string $month, ?float $simRate = null): array
    {
        [$start, $end] = $this->resolveMonth($month);
        $accountId = $awsAccount->id;

        $services = AwsServiceCostMonthly::summary($accountId, $start->format('Y-m-d'));
        $usage = AwsUsageTypeDaily::summaryFor([$accountId], $start->format('Y-m-d'), $end->format('Y-m-d'));
        $hasServices = $services['subtotal'] > 0 || $services['tax'] > 0;

        // Subtotal sebelum pajak: dari biaya per service kalau sudah ditarik,
        // kalau belum hanya Amazon Location dari usage type harian.
        $subtotal = $hasServices ? $services['subtotal'] : $usage['total_cost'];

        // Pajak baru muncul di Cost Explorer setelah bulan ditutup AWS; sebelum
        // itu ditaksir dari tarif PPN di menu Kurs & Pajak.
        $taxRate = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));
        $taxEstimated = $services['tax'] <= 0;
        $tax = $taxEstimated ? $subtotal * $taxRate : $services['tax'];
        $effectiveTaxRate = $subtotal > 0 ? $tax / $subtotal : $taxRate;
        $total = $subtotal + $tax;

        $invoice = AwsInvoice::query()
            ->where('aws_account_id', $accountId)
            ->whereDate('month', $start->format('Y-m-d'))
            ->first();

        // Invoice resmi dari AWS Invoicing API. Selama admin belum mengisi invoice
        // manual, angka resmi inilah yang dipakai: total IDR AWS dihitung dari
        // rincian sebelum dibulatkan, jadi USD bulat × kurs bisa meleset puluhan rupiah.
        $awsInvoices = AwsInvoiceSummary::forMonth($accountId, $start);
        $awsMain = $awsInvoices->first(fn ($i) => !$i->isCreditMemo());
        $invoiceSource = $invoice ? 'manual' : null;
        if (!$invoice && ($official = $awsMain?->asInvoice())) {
            $invoice = $official;
            $invoiceSource = 'aws';
        }

        $storedRate = ExchangeRate::forPeriod($end->format('Y-m-d'));
        $rate = $invoiceSource === 'aws'
            ? ($awsMain->exchange_rate ?: $invoice->rate())
            : ($invoice?->rate() ?? ($storedRate ? (float) $storedRate->rate : null));
        $rateSource = $invoiceSource === 'aws' ? 'aws' : ($invoice ? 'invoice' : ($storedRate ? 'table' : null));

        $reconcile = null;
        if ($invoice && $hasServices) {
            $diff = $invoice->total_usd - $total;
            $reconcile = [
                'diff'    => $diff,
                'diff_idr'=> $rate ? $diff * $rate : null,
                'matched' => abs($diff) <= self::MATCH_TOLERANCE,
            ];
        }

        // Sampai tanggal berapa data Cost Explorer sudah ada untuk bulan ini.
        $lastDataDay = collect($usage['daily'])->filter(fn ($c) => $c > 0)->keys()->max();
        $monthClosed = now()->utc()->startOfDay()->gt($end);
        $complete = $monthClosed && $lastDataDay === $end->format('Y-m-d') && !$taxEstimated;

        $keys = $this->keyBreakdown($awsAccount, $start, $end, $effectiveTaxRate);

        // Angka dasar rincian per perusahaan: IDR di invoice kalau sudah diisi,
        // kalau belum total aplikasi × kurs (USD dibulatkan 2 desimal seperti AWS).
        $baseUsd = $invoice ? $invoice->total_usd : round($total, 2);
        $baseIdr = $invoice ? $invoice->total_idr : ($rate ? round(round($total, 2) * $rate) : null);

        // Kurs simulasi (export): semua Rupiah dihitung ulang dari USD × kurs itu,
        // sama dengan penggeser kurs di halaman. Dijepit ±50% dari kurs resmi
        // supaya angka ketikan yang keliru tidak menghasilkan file ngawur.
        $officialRate = $baseIdr !== null && $baseUsd > 0 ? $baseIdr / $baseUsd : null;
        $simulated = false;
        if ($simRate && $officialRate && abs($simRate - $officialRate) >= 0.005
            && $simRate >= $officialRate * 0.5 && $simRate <= $officialRate * 1.5) {
            $baseIdr = round($baseUsd * $simRate);
            $simulated = true;
        }
        $split = $baseIdr !== null && $baseUsd > 0
            ? $this->companySplit($awsAccount, $keys, $baseUsd, $baseIdr, $end)
            : null;
        $detail = $split ? $this->operationDetail($split, $keys, $baseUsd, $baseIdr, $hasServices && !$taxEstimated ? $tax : null) : null;

        $cuts = AwsBillingSplit::cutsFor($accountId, $start);
        $periods = $split && $cuts
            ? $this->periodSplits($awsAccount, $start, $end, $cuts, $usage['daily'], $baseUsd, $baseIdr, $effectiveTaxRate, $split)
            : [];

        // Rincian operasi tiap periode (contoh di modal export & file CSV).
        // Murni hitungan dari key yang sudah dimuat, tanpa query tambahan.
        foreach ($periods as &$pp) {
            $pp['detail'] = $pp['usd'] > 0 && $pp['idr'] > 0
                ? $this->operationDetail($pp['split'], $pp['keys'], $pp['usd'], $pp['idr'], null)
                : null;
        }
        unset($pp);

        return [
            'baseUsd'      => $baseUsd,
            'baseIdr'      => $baseIdr,
            'officialRate' => $officialRate,
            'simulated'    => $simulated,
            'cuts'         => $cuts,
            'periods'      => $periods,
            'split'        => $split,
            'detail'       => $detail,
            'account'      => $awsAccount,
            'start'        => $start,
            'end'          => $end,
            'monthOptions' => $this->monthOptions(),
            'services'     => $services,
            'hasServices'  => $hasServices,
            'usage'        => $usage,
            'subtotal'     => $subtotal,
            'tax'          => $tax,
            'taxEstimated' => $taxEstimated,
            'taxRate'      => $effectiveTaxRate,
            'total'        => $total,
            'invoice'      => $invoice,
            'invoiceSource'=> $invoiceSource,
            'awsInvoices'  => $awsInvoices,
            'billStatus'   => $this->billStatus($awsAccount, $start, $awsInvoices),
            'rate'         => $rate,
            'rateSource'   => $rateSource,
            'storedRate'   => $storedRate,
            'reconcile'    => $reconcile,
            'lastDataDay'  => $lastDataDay ? Carbon::parse($lastDataDay) : null,
            'monthClosed'  => $monthClosed,
            'complete'     => $complete,
            'keys'         => $keys,
        ];
    }

    /**
     * Unduh PDF invoice resmi AWS: minta URL bertanda tangan saat diklik lalu
     * alihkan ke sana. Hanya invoice yang tercatat untuk akun ini.
     */
    public function invoicePdf(Request $request, AwsAccount $awsAccount, string $invoiceId)
    {
        $summary = AwsInvoiceSummary::where('aws_account_id', $awsAccount->id)->where('invoice_id', $invoiceId)->firstOrFail();

        $pdf = AwsLocationService::forAccount($awsAccount)->invoicePdfUrl($summary->invoice_id);
        if ($pdf['url']) {
            return redirect()->away($pdf['url']);
        }

        return redirect()
            ->route('admin.aws-accounts.billing', [$awsAccount, 'month' => $summary->billing_month->format('Y-m')])
            ->with('error', $pdf['denied'] ? __('awsbilling.bs_pdf_denied') : __('awsbilling.bs_pdf_failed', ['error' => $pdf['error']]));
    }

    /**
     * Daftar dokumen resmi satu invoice (PDF + lampiran) sebagai JSON. URL-nya
     * sementara, jadi halaman memintanya saat tombol "Dokumen" dibuka lalu
     * menampilkannya sebagai tautan.
     */
    public function invoiceDocuments(AwsAccount $awsAccount, string $invoiceId)
    {
        $summary = AwsInvoiceSummary::where('aws_account_id', $awsAccount->id)->where('invoice_id', $invoiceId)->firstOrFail();
        $pdf = AwsLocationService::forAccount($awsAccount)->invoicePdfUrl($summary->invoice_id);

        if (!$pdf['url']) {
            return response()->json([
                'error' => $pdf['denied'] ? __('awsbilling.bs_pdf_denied') : __('awsbilling.bs_pdf_failed', ['error' => $pdf['error']]),
            ], $pdf['denied'] ? 403 : 502);
        }

        return response()->json([
            'invoice'      => $summary->invoice_id,
            'pdf'          => $pdf['url'],
            'supplemental' => $pdf['supplemental'],
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Status tagihan bulan itu menurut AWS Invoicing API:
     * issued  = invoice resmi sudah terbit;
     * pending = bulan berjalan atau baru tutup, invoice belum terbit (masih estimasi);
     * none    = sudah lama tutup tapi tidak ada invoice (biasanya bukan payer account);
     * denied  = izin invoicing:ListInvoiceSummaries belum ada;
     * error   = penarikan gagal karena hal lain;
     * unknown = belum pernah ditarik.
     */
    private function billStatus(AwsAccount $account, Carbon $start, $invoices): string
    {
        if ($invoices->contains(fn ($i) => !$i->isCreditMemo())) {
            return 'issued';
        }
        if ($account->invoices_error) {
            return $account->invoices_error === 'access_denied' ? 'denied' : 'error';
        }
        if (!$account->invoices_synced_at) {
            return 'unknown';
        }

        // Invoice bulanan AWS terbit beberapa hari setelah bulan tutup.
        return now()->lt($start->copy()->addMonth()->startOfMonth()->addDays(10)) ? 'pending' : 'none';
    }

    /**
     * Tarik ulang data satu bulan: biaya per service + usage type (Cost Explorer,
     * dua panggilan berbayar $0,01) dan pemakaian tiap API key (CloudWatch).
     */
    public function refresh(Request $request, AwsAccount $awsAccount)
    {
        [$start, $end] = $this->resolveMonth($request->input('month'));
        $back = redirect()->route('admin.aws-accounts.billing', [$awsAccount, 'month' => $start->format('Y-m')]);

        if (!$awsAccount->hasCredentials()) {
            return $back->with('error', __('awsbilling.no_credentials'));
        }

        $until = $end->copy()->min(now()->utc()->startOfDay());
        $service = AwsLocationService::forAccount($awsAccount);
        $errors = [];

        // Biaya per layanan diambil bulanan: satu request Cost Explorer ($0,01)
        // berlaku untuk berapa pun bulannya. Jadi sekalian tarik 12 bulan ke
        // belakang supaya bulan lama tidak kosong, tanpa tambahan biaya.
        $floor = now()->utc()->startOfMonth()->subMonths(self::COST_HISTORY_MONTHS);
        $costFrom = $start->lt($floor) ? $start->copy() : $floor;
        $costTo = $start->copy()->max(now()->utc()->startOfMonth());

        $costs = $service->syncServiceCosts($costFrom->format('Y-m-d'), $costTo->format('Y-m-d'));
        if (!$costs['ok']) {
            $errors[] = 'Cost Explorer: ' . $costs['error'];
        }

        // Status invoice resmi. Izin yang belum ada tidak dihitung sebagai error di
        // sini — halaman sudah menampilkan petunjuk izinnya sendiri.
        $service->syncInvoiceSummaries($costFrom->format('Y-m-d'));

        $types = $service->syncUsageTypes($start->format('Y-m-d'), $until->format('Y-m-d'));
        if (!$types['ok'] && $costs['ok']) {
            $errors[] = 'Cost Explorer: ' . $types['error'];
        }

        // Key yang terdaftar di AWS sekarang, ditambah key yang sudah pernah tercatat
        // (key yang sudah dihapus tetap punya pemakaian di bulan lampau).
        $names = collect($service->listApiKeys()['keys'] ?? [])->pluck('key_name')
            ->merge(CompanyApiKey::where('aws_account_id', $awsAccount->id)->pluck('key_name'))
            ->merge(ApiKeyUsageDaily::where('aws_account_id', $awsAccount->id)
                ->whereBetween('usage_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->distinct()->pluck('key_name'))
            ->filter()->unique()->values();

        $failedKeys = 0;
        foreach ($names as $name) {
            $m = $service->getKeyUsageMetrics($name, $start->format('Y-m-d'), $until->format('Y-m-d'));
            if (!empty($m['error'])) {
                $failedKeys++;
            }
        }
        if ($failedKeys) {
            $errors[] = __('awsbilling.keys_failed', ['count' => $failedKeys]);
        }

        return $errors
            ? $back->with('error', implode(' · ', $errors))
            : $back->with('success', __('awsbilling.refreshed', ['keys' => $names->count()]));
    }

    /**
     * Simpan angka invoice AWS bulan ini. Kurs invoice (IDR ÷ USD) ikut dicatat
     * di Kurs & Pajak dengan sumber "Tagihan AWS", sehingga laporan klien bulan
     * yang sama otomatis memakai kurs AWS yang sebenarnya.
     */
    /**
     * Simpan tanggal potong bulan ini (kosong = sebulan penuh lagi). Halaman
     * tetap sebulan; angkanya saja yang dipecah per periode.
     */
    public function storePeriods(Request $request, AwsAccount $awsAccount)
    {
        $data = $request->validate([
            'month'  => ['required', 'date_format:Y-m'],
            'cuts'   => ['nullable', 'array', 'max:' . AwsBillingSplit::MAX_CUTS],
            'cuts.*' => ['integer', 'min:1', 'max:30'],
        ]);

        [$start] = $this->resolveMonth($data['month']);
        $cuts = AwsBillingSplit::clean($data['cuts'] ?? [], $start->daysInMonth);

        if ($cuts) {
            AwsBillingSplit::updateOrCreate(
                ['aws_account_id' => $awsAccount->id, 'month' => $start->format('Y-m-d')],
                ['cuts' => $cuts, 'created_by' => optional($request->user())->name ?? 'admin'],
            );
        } else {
            AwsBillingSplit::where('aws_account_id', $awsAccount->id)->whereDate('month', $start->format('Y-m-d'))->delete();
        }

        return redirect()
            ->route('admin.aws-accounts.billing', [$awsAccount, 'month' => $start->format('Y-m')])
            ->with('success', $cuts ? __('awsbilling.periods_saved', ['n' => count($cuts) + 1]) : __('awsbilling.periods_cleared'));
    }

    public function storeInvoice(Request $request, AwsAccount $awsAccount)
    {
        $request->merge([
            'total_usd' => self::parseAmount($request->input('total_usd')),
            'total_idr' => self::parseAmount($request->input('total_idr')),
        ]);

        $data = $request->validate([
            'month'          => ['required', 'date_format:Y-m'],
            'total_usd'      => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'total_idr'      => ['required', 'numeric', 'min:1', 'max:1000000000000'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        [$start, $end] = $this->resolveMonth($data['month']);
        $by = optional($request->user())->name ?? 'admin';
        $usd = (float) $data['total_usd'];
        $idr = (float) $data['total_idr'];
        $label = $start->format('M Y');

        DB::transaction(function () use ($awsAccount, $start, $end, $data, $by, $usd, $idr, $label) {
            $invoice = AwsInvoice::query()
                ->where('aws_account_id', $awsAccount->id)
                ->whereDate('month', $start->format('Y-m-d'))
                ->first() ?? new AwsInvoice(['aws_account_id' => $awsAccount->id, 'month' => $start->format('Y-m-d')]);

            // Kurs AWS sama untuk semua akun di bulan yang sama, jadi satu baris
            // "Tagihan AWS" per bulan dipakai bersama.
            $rate = $invoice->exchangeRate
                ?? ExchangeRate::query()
                    ->where('source', 'Tagihan AWS')
                    ->whereDate('rate_date', $end->format('Y-m-d'))
                    ->first()
                ?? new ExchangeRate(['is_active' => false]);

            $rate->fill([
                'rate'       => round($idr / $usd, 4),
                'rate_date'  => $end->format('Y-m-d'),
                'source'     => 'Tagihan AWS',
                'reference'  => 'AWS Bill ' . $label . (!empty($data['invoice_number']) ? ' - ' . $data['invoice_number'] : ''),
                'note'       => 'IDR ' . number_format($idr, 0, ',', '.') . ' / USD ' . number_format($usd, 2, ',', '.')
                    . ' (' . $awsAccount->name . ')',
            ]);
            if (!$rate->exists) {
                $rate->created_by = $by;
            }
            $rate->save();

            $invoice->fill([
                'invoice_number'   => $data['invoice_number'] ?? null,
                'total_usd'        => $usd,
                'total_idr'        => $idr,
                'exchange_rate_id' => $rate->id,
                'note'             => $data['note'] ?? null,
                'created_by'       => $by,
            ])->save();
        });

        return redirect()
            ->route('admin.aws-accounts.billing', [$awsAccount, 'month' => $start->format('Y-m')])
            ->with('success', __('awsbilling.invoice_saved', [
                'rate' => number_format($idr / $usd, 2, ',', '.'),
            ]));
    }

    /**
     * Angka yang disalin dari invoice, gaya Inggris maupun Indonesia:
     * "14,364,343" / "14.364.343" → 14364343, "790.02" / "790,02" → 790.02,
     * "1,234.56" / "1.234,56" → 1234.56. Kalau dua pemisah muncul, yang terakhir
     * adalah desimal; kalau satu jenis saja, dianggap ribuan hanya bila memisah
     * kelompok tiga angka.
     */
    private static function parseAmount(?string $value): ?string
    {
        $value = preg_replace('/[^\d.,]/', '', (string) $value);
        if ($value === '') {
            return $value;
        }

        $lastDot = strrpos($value, '.');
        $lastComma = strrpos($value, ',');

        if ($lastDot !== false && $lastComma !== false) {
            $decimal = $lastDot > $lastComma ? '.' : ',';
            $thousand = $decimal === '.' ? ',' : '.';

            return str_replace([$thousand, $decimal], ['', '.'], $value);
        }

        $sep = $lastDot !== false ? '.' : ($lastComma !== false ? ',' : null);
        if ($sep === null) {
            return $value;
        }

        if (preg_match('/^\d{1,3}(' . preg_quote($sep, '/') . '\d{3})+$/', $value)) {
            return str_replace($sep, '', $value);
        }

        return str_replace($sep, '.', $value);
    }

    /**
     * Bulan dari "?month=YYYY-MM", dijepit ke 12 bulan terakhir. Bawaannya bulan
     * lalu: yang paling sering dicek adalah invoice yang baru terbit.
     *
     * @return array{0: Carbon, 1: Carbon}  awal dan akhir bulan (tanggal, UTC)
     */
    private function resolveMonth(?string $value): array
    {
        $current = now()->utc()->startOfMonth();
        $oldest = $current->copy()->subMonthsNoOverflow(self::MONTHS_BACK - 1);

        $start = null;
        if ($value && preg_match('/^\d{4}-\d{2}$/', $value)) {
            $start = Carbon::createFromFormat('Y-m-d', $value . '-01', 'UTC')->startOfDay();
        }
        $start ??= $current->copy()->subMonthNoOverflow();

        if ($start->gt($current)) {
            $start = $current->copy();
        }
        if ($start->lt($oldest)) {
            $start = $oldest->copy();
        }

        return [$start, $start->copy()->endOfMonth()->startOfDay()];
    }

    /** @return array<int, Carbon> */
    private function monthOptions(): array
    {
        $current = now()->utc()->startOfMonth();
        $out = [];
        for ($i = 0; $i < self::MONTHS_BACK; $i++) {
            $out[] = $current->copy()->subMonthsNoOverflow($i);
        }

        return $out;
    }

    /**
     * Pecah tagihan sebulan ke beberapa periode. Porsi tiap periode = biaya
     * Amazon Location hariannya (Cost Explorer) ÷ biaya Location sebulan; PPN
     * AWS dan biaya kecil lain ikut sebanding. Pembagian ke perusahaan memakai
     * pemakaian API key pada tanggal periode itu saja. Jumlah semua periode =
     * angka sebulan.
     *
     * @param  array<int, int>  $cuts
     * @param  array<string, float>  $daily  biaya Location per tanggal
     */
    private function periodSplits(AwsAccount $account, Carbon $start, Carbon $end, array $cuts, array $daily,
        float $baseUsd, float $baseIdr, float $taxRate, array $monthSplit): array
    {
        $bounds = [];
        $from = $start->copy();
        foreach ($cuts as $day) {
            $to = $start->copy()->day($day);
            $bounds[] = [$from, $to];
            $from = $to->copy()->addDay();
        }
        $bounds[] = [$from, $end->copy()];

        $monthLoc = array_sum($daily);
        $days = $start->daysInMonth;
        $vatRate = $monthSplit['vat_rate'];
        $out = [];

        foreach ($bounds as [$a, $b]) {
            $loc = 0.0;
            foreach ($daily as $date => $cost) {
                if ($date >= $a->format('Y-m-d') && $date <= $b->format('Y-m-d')) {
                    $loc += $cost;
                }
            }
            // Tanpa data harian sama sekali, bagi menurut jumlah hari.
            $share = $monthLoc > 0 ? $loc / $monthLoc : ($a->diffInDays($b) + 1) / $days;

            $keys = $this->keyBreakdown($account, $a, $b, $taxRate);
            $split = $this->companySplit($account, $keys, $baseUsd * $share, $baseIdr * $share, $end, $share);

            // Periode tanpa pemakaian API key: totalnya tetap dihitung dengan tarif akun.
            if (!$split) {
                $idr = $baseIdr * $share;
                $pct = $monthSplit['percent'] ?? 0;
                $split = [
                    'rows'     => [],
                    'total'    => self::invoiceLine($idr, $idr * $pct / 100, $vatRate) + ['usd' => $baseUsd * $share],
                    'percent'  => $monthSplit['percent'],
                    'vat_rate' => $vatRate,
                    'has_fee'  => $monthSplit['has_fee'],
                    'has_vat'  => $monthSplit['has_vat'],
                ];
            }

            $out[] = [
                'start' => $a,
                'end'   => $b,
                'share' => $share * 100,
                'loc'   => $loc,
                'split' => $split,
                'keys'  => $keys,
                'usd'   => $baseUsd * $share,
                'idr'   => $baseIdr * $share,
            ];
        }

        return $out;
    }

    /**
     * Rincian tagihan ke klien dengan susunan lembar Tim Alfa:
     *
     *   Billing AWS (IDR invoice, sudah termasuk PPN AWS)
     *   + Management fee (service charge, mis. 9%)   = Invoice
     *   + VAT 11% atas Invoice                         = Total invoice
     *
     * Total invoice AWS dibagi ke perusahaan sebanding porsi biaya API key-nya
     * (CloudWatch), jadi biaya kecil di luar key (CloudWatch, S3, selisih) ikut
     * terbagi. Seperti lembar Alfa, angka dihitung tanpa pembulatan (kecuali PPN
     * per baris) dan baru dibulatkan saat ditampilkan, dan baris total dihitung dari totalnya
     * sendiri — jadi jumlah baris bisa beda Rp 1 dari total, sama persis dengan
     * lembar itu.
     *
     * @param  array<int, array>  $keys  hasil keyBreakdown()
     */
    private function companySplit(AwsAccount $account, array $keys, float $baseUsd, float $baseIdr, Carbon $end, float $minFactor = 1.0): ?array
    {
        $weights = array_column($keys, 'cost', 'name');
        $sum = array_sum($weights);
        if ($sum <= 0) {
            return null;
        }

        // VAT dan management fee bisa dimatikan per akun (form edit akun AWS).
        $vatRate = $account->bill_vat ? (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11)) : 0.0;
        $date = $end->format('Y-m-d');

        // Kelompokkan key per perusahaan; key tanpa pemilik jadi kelompok sendiri.
        $groups = [];
        foreach ($keys as $k) {
            $companyId = \App\Models\ServiceCharge::companyIdForKey($account->id, $k['name']);
            $gid = $companyId ?? 0;
            $groups[$gid] ??= ['company_id' => $companyId, 'name' => $k['company'], 'keys' => []];
            $groups[$gid]['keys'][] = $k['name'];
        }

        $rows = [];
        $percents = [];
        $hasMinimum = false;

        foreach ($groups as $g) {
            $rule = $account->bill_management_fee
                ? \App\Models\ServiceCharge::ruleFor($account->id, $g['company_id'], $date)
                : null;
            $pct = (float) ($rule?->percent ?? 0);
            // Minimum bulanan ikut dipotong sebanding untuk periode sebagian bulan.
            $min = (float) ($rule?->monthly_min_idr ?? 0) * $minFactor;
            $percents[] = $pct;
            $hasMinimum = $hasMinimum || $min > 0;

            $share = array_sum(array_map(fn ($n) => $weights[$n], $g['keys'])) / $sum;
            $idr = $baseIdr * $share;
            $fee = max($idr * $pct / 100, $min);

            $row = self::invoiceLine($idr, $fee, $vatRate) + [
                'company_id' => $g['company_id'],
                'name'    => $g['name'],
                'percent' => $pct,
                'min'     => $min,
                'usd'     => $baseUsd * $share,
                'share'   => $share * 100,
            ];

            // Baris API key: kolom perusahaan dibagi lagi sebanding biaya key.
            $groupWeight = $share * $sum;
            $row['keys'] = [];
            foreach ($g['keys'] as $name) {
                $f = $groupWeight > 0 ? $weights[$name] / $groupWeight : 0;
                $row['keys'][] = ['name' => $name, 'share' => $weights[$name] / $sum * 100]
                    + array_map(fn ($v) => $v * $f, array_intersect_key($row, array_flip(['usd', 'idr', 'fee', 'invoice', 'vat', 'total'])));
            }

            $rows[] = $row;
        }

        // Key yang belum dipasang ke perusahaan paling bawah.
        usort($rows, fn ($a, $b) => [$a['name'] === null, -$a['idr']] <=> [$b['name'] === null, -$b['idr']]);

        // Satu tarif tanpa minimum: hitung dari total, seperti lembar Alfa.
        // Tarif campuran: jumlahkan fee per perusahaan.
        $uniform = !$hasMinimum && count(array_unique($percents)) === 1;
        $totalFee = $uniform ? $baseIdr * $percents[0] / 100 : array_sum(array_column($rows, 'fee'));

        return [
            'rows'     => $rows,
            'total'    => self::invoiceLine($baseIdr, $totalFee, $vatRate) + ['usd' => $baseUsd],
            'percent'  => $uniform ? $percents[0] : null,
            'vat_rate' => $vatRate,
            'has_fee'  => (bool) $account->bill_management_fee,
            'has_vat'  => (bool) $account->bill_vat,
        ];
    }

    /**
     * Rincian per perusahaan → API key → kategori → operasi: request, biaya
     * tarif daftar (USD) dan Rupiah dengan kurs invoice. Bagian bawahnya
     * menyambung ke angka invoice: PPN AWS, biaya lain + selisih, lalu
     * management fee dan VAT dari rincian per perusahaan, sehingga totalnya
     * sama dengan Total Invoice di atas.
     *
     * @param  ?float  $awsTax  PPN AWS sebenarnya (Cost Explorer); null kalau belum ada
     */
    private function operationDetail(array $split, array $keys, float $baseUsd, float $baseIdr, ?float $awsTax): array
    {
        $rate = $baseIdr / $baseUsd;
        $byName = array_column($keys, null, 'name');
        $companies = [];

        foreach ($split['rows'] as $row) {
            $blocks = [];
            foreach ($row['keys'] as $k) {
                $ops = $byName[$k['name']]['operations'] ?? [];

                $cats = array_map(fn ($list) => array_fill_keys($list, 0), self::OP_GROUPS);
                foreach ($ops as $op => $n) {
                    $cats[self::categoryOf($op)][$op] = $n;
                }

                $categories = [];
                foreach ($cats as $cat => $list) {
                    $lines = [];
                    foreach ($list as $op => $n) {
                        $usd = $n / 1000 * (AwsLocationService::PRICING[$op] ?? 0);
                        $lines[] = ['op' => $op, 'count' => (int) $n, 'usd' => $usd, 'idr' => $usd * $rate];
                    }
                    $categories[] = [
                        'name'  => $cat,
                        'lines' => $lines,
                        'usd'   => array_sum(array_column($lines, 'usd')),
                        'idr'   => array_sum(array_column($lines, 'idr')),
                    ];
                }

                // Rupiah per operasi memakai porsi "Tagihan AWS" key ini (sudah
                // termasuk PPN AWS dan biaya kecil lain), dibagi sebanding biaya
                // operasinya — jadi operasi → key → perusahaan selalu menjumlah.
                $keyUsd = array_sum(array_column($categories, 'usd'));
                foreach ($categories as &$c) {
                    foreach ($c['lines'] as &$l) {
                        $l['idr'] = $keyUsd > 0 ? $l['usd'] / $keyUsd * $k['idr'] : 0.0;
                    }
                    unset($l);
                    $c['idr'] = array_sum(array_column($c['lines'], 'idr'));
                }
                unset($c);

                $blocks[] = [
                    'name'       => $k['name'],
                    'categories' => $categories,
                    'usd'        => $keyUsd,
                    'idr'        => $k['idr'],
                ];
            }

            $companies[] = [
                'company_id' => $row['company_id'] ?? null,
                'name' => $row['name'],
                'keys' => $blocks,
                'usd'  => array_sum(array_column($blocks, 'usd')),
                'idr'  => array_sum(array_column($blocks, 'idr')),
            ];
        }

        $subUsd = array_sum(array_column($companies, 'usd'));

        // Dari subtotal pemakaian ke angka invoice: PPN AWS dulu, sisanya biaya
        // service lain (CloudWatch, S3, Cost Explorer) dan selisih cara hitung.
        $taxUsd = $awsTax ?? 0.0;
        $otherUsd = $baseUsd - $subUsd - $taxUsd;

        return [
            'companies' => $companies,
            'rate'      => $rate,
            'subtotal'  => ['usd' => $subUsd, 'idr' => $subUsd * $rate],
            'tax'       => $awsTax !== null ? ['usd' => $taxUsd, 'idr' => $taxUsd * $rate] : null,
            'other'     => ['usd' => $otherUsd, 'idr' => $otherUsd * $rate],
            'aws'       => ['usd' => $baseUsd, 'idr' => $baseIdr],
            'fee'       => $split['total']['fee'],
            'invoice'   => $split['total']['invoice'],
            'vat'       => $split['total']['vat'],
            'total'     => $split['total']['total'],
        ];
    }

    private static function categoryOf(string $op): string
    {
        foreach (self::OP_GROUPS as $cat => $list) {
            if (in_array($op, $list, true)) {
                return $cat;
            }
        }

        return match (true) {
            str_starts_with($op, 'GetMap') => 'Maps',
            str_starts_with($op, 'Calculate') => 'Routes',
            default => 'Places',
        };
    }

    /** @return array{idr: float, fee: float, invoice: float, vat: float, total: float} */
    private static function invoiceLine(float $idr, float $fee, float $vatRate): array
    {
        // PPN dibulatkan ke rupiah penuh (angka pajak), kolom lain tidak —
        // dengan begitu hasil tampilannya sama persis dengan lembar Alfa.
        $invoice = $idr + $fee;
        $vat = round($invoice * $vatRate);

        return ['idr' => $idr, 'fee' => $fee, 'invoice' => $invoice, 'vat' => $vat, 'total' => $invoice + $vat];
    }

    /**
     * Porsi tiap API key dari CloudWatch (api_key_usage_daily), dihargai dengan
     * tarif daftar. Ini taksiran: tagihan sebenarnya per usage type di atasnya.
     *
     * @return array<int, array{name: string, company: ?string, requests: int, cost: float, cost_tax: float, share: float, last_day: ?string}>
     */
    private function keyBreakdown(AwsAccount $account, Carbon $start, Carbon $end, float $taxRate): array
    {
        $from = $start->format('Y-m-d');
        $to = $end->format('Y-m-d');

        $rows = ApiKeyUsageDaily::query()
            ->where('aws_account_id', $account->id)
            ->whereBetween('usage_date', [$from, $to])
            ->selectRaw('key_name, operation, SUM(request_count) AS n, MAX(usage_date) AS last_day')
            ->groupBy('key_name', 'operation')
            ->get();

        $companies = CompanyApiKey::with('company:id,name')
            ->where('aws_account_id', $account->id)
            ->get()
            ->mapWithKeys(fn ($k) => [$k->key_name => $k->company?->name]);

        $keys = [];
        foreach ($rows->groupBy('key_name') as $name => $ops) {
            $operations = $ops->mapWithKeys(fn ($r) => [$r->operation => (int) $r->n])->all();
            $requests = array_sum($operations);
            $operations = MatrixRouteDaily::billable($operations, $account->id, $from, $to);
            $cost = AwsLocationService::estimateCost($operations);

            $keys[] = [
                'name'     => (string) $name,
                'company'  => $companies[$name] ?? null,
                'requests' => $requests,
                'cost'     => $cost,
                'cost_tax' => $cost * (1 + $taxRate),
                'last_day' => $ops->max('last_day'),
                'operations' => $operations,
            ];
        }

        $sum = array_sum(array_column($keys, 'cost')) ?: 1;
        foreach ($keys as &$k) {
            $k['share'] = $k['cost'] / $sum * 100;
        }
        unset($k);

        usort($keys, fn ($a, $b) => $b['cost'] <=> $a['cost']);

        return $keys;
    }
}
