<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwsAccount;
use App\Models\ApiKeyUsageShare;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\UsageShareVisit;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    private const FEATURES = ['search', 'route', 'reverse_geocode', 'route_matrix'];

    private const DEFAULT_FEATURE_SETTINGS = [
        'search'          => ['language' => 'id'],
        'route'           => ['modes' => ['Car', 'Motorcycle']],
        'reverse_geocode' => ['language' => 'id'],
        'route_matrix'    => ['modes' => ['Car', 'Motorcycle']],
    ];

    public function index()
    {
        $companies = Company::query()
            ->withCount([
                'features',
                'apiKeys',
                'usageShares as active_shares_count' => fn ($q) => $q->where('share_enabled', true),
            ])
            ->with('awsAccount')
            ->latest()
            ->get();

        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.form', ['awsAccounts' => $this->awsAccounts()]);
    }

    /** Akun AWS aktif untuk dropdown di form company. */
    private function awsAccounts()
    {
        return AwsAccount::query()->active()->orderByDesc('is_default')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:companies,slug|regex:/^[a-z0-9\-]+$/',
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'    => 'nullable|boolean',
            'aws_account_id' => 'nullable|exists:aws_accounts,id',
            'aws_api_key'  => 'nullable|string|max:1000',
            'aws_key_active' => 'nullable|boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('logos'), $filename);
            $logoPath = 'logos/' . $filename;
        }

        $apiKey = $request->input('aws_api_key');

        $company = Company::create([
            'name'           => $validated['name'],
            'slug'           => $validated['slug'],
            'logo_path'      => $logoPath,
            'is_active'      => $request->boolean('is_active', true),
            'aws_account_id' => $validated['aws_account_id'] ?? null,
            'aws_api_key'    => $apiKey && $apiKey !== '********' ? $apiKey : null,
            'aws_key_active' => $request->boolean('aws_key_active', true),
        ]);

        // Fitur peta tidak lagi diatur dari formulir perusahaan — pembatas yang
        // sebenarnya ada di izin API key-nya. Barisnya tetap dibuat aktif supaya
        // halaman peta klien punya nilai bawaan yang masuk akal.
        foreach (self::FEATURES as $key) {
            $company->features()->create([
                'feature_key' => $key,
                'is_enabled'  => true,
                'settings'    => self::DEFAULT_FEATURE_SETTINGS[$key] ?? null,
            ]);
        }

        return redirect()->route('admin.companies.index')
            ->with('success', "Company \"{$company->name}\" berhasil dibuat.");
    }

    public function edit(Company $company)
    {
        return view('admin.companies.form', [
            'company'     => $company,
            'awsAccounts' => $this->awsAccounts(),
        ]);
    }

    /**
     * Halaman detail: tempat API key ditempelkan dan link laporan dibuat.
     * Sengaja dipisah dari formulir identitas — isinya berbeda sifat.
     */
    public function show(Company $company)
    {
        $company->load([
            'apiKeys.awsAccount',
            'usageShares.keys',
            // Riwayat akses ditampilkan ringkas — 8 kunjungan terakhir per link.
            'usageShares.visits' => fn ($q) => $q->limit(8),
        ]);
        $company->usageShares->loadCount('visits')->loadSum('visits', 'hits');

        return view('admin.companies.show', [
            'company'       => $company,
            'availableKeys' => $this->availableKeys($company),
        ]);
    }

    /**
     * Key yang belum diklaim perusahaan mana pun, dikelompokkan per akun AWS —
     * satu perusahaan boleh memegang key dari lebih dari satu akun. Tiap akun
     * cukup satu panggilan ListKeys (hasilnya di-cache service).
     *
     * @return array<int, array{id: ?int, name: string, keys: array<int, string>}>
     */
    private function availableKeys(Company $company): array
    {
        $claimed = CompanyApiKey::query()
            ->get(['aws_account_id', 'key_name'])
            ->map(fn ($row) => $row->aws_account_id . '|' . $row->key_name)
            ->all();

        $accounts = AwsAccount::query()->active()->orderByDesc('is_default')->orderBy('id')->get();

        // Belum ada akun tersimpan: masih ada jalur kredensial .env.
        $sources = $accounts->isEmpty() ? collect([null]) : $accounts;

        return $sources
            ->filter(fn ($account) => AwsLocationService::hasCredentials($account))
            ->map(function ($account) use ($claimed) {
                $keys = collect(AwsLocationService::forAccount($account)->listApiKeys()['keys'] ?? [])
                    ->pluck('key_name')
                    ->filter()
                    ->reject(fn ($name) => in_array($account?->id . '|' . $name, $claimed, true))
                    ->values()
                    ->all();

                return ['id' => $account?->id, 'name' => $account?->name ?? '.env', 'keys' => $keys];
            })
            ->filter(fn ($group) => $group['keys'] !== [])
            ->values()
            ->all();
    }

    /** Tempelkan satu key ke perusahaan ini. */
    public function attachKey(Request $request, Company $company)
    {
        $validated = $request->validate([
            // Kolomnya berisi "akunId|namaKey" supaya satu dropdown cukup untuk
            // memilih key dari akun mana pun.
            'key_ref' => ['required', 'string', 'max:160'],
            'label'   => ['nullable', 'string', 'max:100'],
        ]);

        [$accountId, $keyName] = array_pad(explode('|', $validated['key_ref'], 2), 2, null);
        $accountId = $accountId === '' ? null : (int) $accountId;

        if (!$keyName) {
            return back()->with('error', __('companies.key_invalid'));
        }

        $validated['key_name'] = $keyName;

        // Key hanya boleh dimiliki satu perusahaan supaya biayanya tidak dobel.
        $owner = CompanyApiKey::ownerOf($accountId, $keyName);
        if ($owner) {
            return back()->with('error', __('companies.key_taken', ['name' => $owner->name]));
        }

        $company->apiKeys()->create([
            'aws_account_id' => $accountId,
            'key_name'       => $validated['key_name'],
            'label'          => $validated['label'] ?? null,
            'is_primary'     => $company->apiKeys()->count() === 0,
        ]);

        $company->syncPrimaryKeyName();

        return back()->with('success', __('companies.key_attached', ['name' => $validated['key_name']]));
    }

    public function detachKey(Company $company, CompanyApiKey $key)
    {
        abort_unless($key->company_id === $company->id, 404);

        $wasPrimary = $key->is_primary;
        $key->delete();

        // Selalu sisakan satu key utama selama masih ada key lain.
        if ($wasPrimary && ($next = $company->apiKeys()->first())) {
            $next->update(['is_primary' => true]);
        }

        $company->syncPrimaryKeyName();

        return back()->with('success', __('companies.key_detached', ['name' => $key->key_name]));
    }

    public function primaryKey(Company $company, CompanyApiKey $key)
    {
        abort_unless($key->company_id === $company->id, 404);

        $company->apiKeys()->update(['is_primary' => false]);
        $key->update(['is_primary' => true]);
        $company->syncPrimaryKeyName();

        return back()->with('success', __('companies.key_primary', ['name' => $key->key_name]));
    }

    /**
     * Riwayat akses seluruh link perusahaan dalam satu daftar. Dengan banyak
     * link, membuka satu per satu di halaman detail jadi tidak praktis —
     * pertanyaannya biasanya "siapa saja yang membuka laporan kami?", bukan
     * "siapa yang membuka link nomor tiga?".
     */
    public function accessLog(Request $request, Company $company)
    {
        $shares = $company->usageShares()->get();

        $filter = $request->query('link');
        $active = $filter ? $shares->firstWhere('id', (int) $filter) : null;

        $visits = UsageShareVisit::query()
            ->whereIn('usage_share_id', $shares->pluck('id'))
            ->when($active, fn ($q) => $q->where('usage_share_id', $active->id))
            ->with('share')
            ->latest('last_seen_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.companies.access-log', [
            'company' => $company,
            'shares'  => $shares,
            'active'  => $active,
            'visits'  => $visits,
        ]);
    }

    // ─── Link laporan publik ──────────────────────────────────────────

    /**
     * Buat link baru. Cakupannya bisa seluruh key perusahaan (ikut otomatis saat
     * key baru ditambah) atau beberapa key tertentu — termasuk cuma satu.
     */
    public function storeShare(Request $request, Company $company)
    {
        $validated = $request->validate([
            'label'      => ['nullable', 'string', 'max:100'],
            'scope'      => ['required', 'in:all,pick'],
            'key_ids'    => ['array'],
            'key_ids.*'  => ['integer'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        // Hanya key milik perusahaan ini yang boleh masuk cakupan.
        $keyIds = $validated['scope'] === 'pick'
            ? $company->apiKeys()->whereIn('id', $validated['key_ids'] ?? [])->pluck('id')->all()
            : [];

        if ($validated['scope'] === 'pick' && !$keyIds) {
            return back()->with('error', __('companies.share_pick_none'));
        }

        $share = ApiKeyUsageShare::enableForCompany(
            $company->id,
            $request->user()?->name,
            // Akhir hari menurut waktu Jakarta, bukan akhir hari UTC.
            !empty($validated['expires_at'])
                ? \Carbon\Carbon::parse($validated['expires_at'], config('app.display_timezone'))->endOfDay()
                : null,
            $validated['label'] ?? null,
            $keyIds,
        );

        return back()->with('success', __('companies.share_on'))->with('share_url', $share->publicUrl());
    }

    public function toggleShare(Company $company, ApiKeyUsageShare $share)
    {
        abort_unless($share->company_id === $company->id, 404);

        $share->share_enabled
            ? $share->disable()
            : $share->update(['share_enabled' => true]);

        return back()->with('success', $share->fresh()->share_enabled
            ? __('companies.share_on')
            : __('companies.share_off'));
    }

    public function regenerateShare(Request $request, Company $company, ApiKeyUsageShare $share)
    {
        abort_unless($share->company_id === $company->id, 404);

        $share->regenerateToken($request->user()?->name);

        return back()->with('success', __('companies.share_regenerated'));
    }

    public function destroyShare(Company $company, ApiKeyUsageShare $share)
    {
        abort_unless($share->company_id === $company->id, 404);

        $share->delete();

        return back()->with('success', __('companies.share_deleted'));
    }

    /**
     * Tarik ulang snapshot seluruh key perusahaan ini. Laporan publik hanya
     * membaca cache, jadi inilah satu-satunya cara mengisinya.
     */
    public function refreshUsage(Request $request, Company $company)
    {
        $startDate = $request->input('start', now()->subDays(91)->format('Y-m-d'));
        $endDate   = $request->input('end', now()->format('Y-m-d'));

        $done = 0;
        $failed = 0;

        foreach ($company->apiKeys()->with('awsAccount')->get() as $key) {
            if (!AwsLocationService::hasCredentials($key->awsAccount)) {
                $failed++;
                continue;
            }

            // getCachedUsage(force) menembak CloudWatch dan sekaligus menuliskan
            // pemakaian hariannya ke tabel api_key_usage_daily.
            $snapshot = AwsLocationService::forAccount($key->awsAccount)
                ->getCachedUsage($key->key_name, $startDate, $endDate, null, true);

            empty($snapshot['metrics']['error']) ? $done++ : $failed++;
        }

        return back()->with(
            $failed ? 'error' : 'success',
            __('companies.refreshed', ['done' => $done, 'failed' => $failed]),
        );
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'slug'         => "required|string|max:100|unique:companies,slug,{$company->id}|regex:/^[a-z0-9\-]+$/",
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'    => 'nullable|boolean',
            'aws_account_id' => 'nullable|exists:aws_accounts,id',
            'aws_api_key'  => 'nullable|string|max:1000',
            'aws_key_active' => 'nullable|boolean',
        ]);

        $logoPath = $company->logo_path;
        if ($request->hasFile('logo')) {
            if ($company->logo_path && file_exists(public_path($company->logo_path))) {
                unlink(public_path($company->logo_path));
            }
            $file = $request->file('logo');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('logos'), $filename);
            $logoPath = 'logos/' . $filename;
        }

        $updateData = [
            'name'           => $validated['name'],
            'slug'           => $validated['slug'],
            'logo_path'      => $logoPath,
            'is_active'      => $request->boolean('is_active', true),
            'aws_account_id' => $validated['aws_account_id'] ?? null,
            'aws_key_active' => $request->boolean('aws_key_active', true),
        ];

        $apiKeyInput = $request->input('aws_api_key');
        if ($apiKeyInput && $apiKeyInput !== '********') {
            $updateData['aws_api_key'] = $apiKeyInput;
        } elseif ($apiKeyInput === '' || $apiKeyInput === null) {
            $updateData['aws_api_key'] = null;
        }

        $company->update($updateData);

        // Perusahaan lama bisa saja belum punya baris fitur — lengkapi seperlunya,
        // tapi jangan mengubah yang sudah ada.
        foreach (self::FEATURES as $key) {
            $company->features()->firstOrCreate(
                ['feature_key' => $key],
                ['is_enabled' => true, 'settings' => self::DEFAULT_FEATURE_SETTINGS[$key] ?? null],
            );
        }

        return redirect()->route('admin.companies.index')
            ->with('success', "Company \"{$company->name}\" berhasil diperbarui.");
    }

    public function toggleStatus(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Company \"{$company->name}\" berhasil {$status}.");
    }

    public function usage(Request $request, Company $company)
    {
        $keyName = $company->aws_api_key_name;

        // Default rentang: bulan berjalan (selaras dengan siklus tagihan AWS).
        $startDate = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->query('end', now()->format('Y-m-d'));
        $refresh   = $request->query('refresh') === '1';

        $metrics   = ['total' => 0, 'daily' => [], 'operations' => [], 'error' => null];
        $fetchedAt = null;

        // Ambil dari CloudWatch (data AWS asli) lewat API key milik company — model manual refresh.
        // Kredensial diambil dari akun AWS tempat key itu dibuat (CloudWatch tidak lintas akun).
        if ($keyName) {
            $service  = AwsLocationService::forAccount($company->awsAccount);
            $snapshot = $service->getCachedUsage($keyName, $startDate, $endDate, null, $refresh);
            $metrics  = $snapshot['metrics'];
            $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;
        }

        $totalCost  = AwsLocationService::estimateCost($metrics['operations'] ?? []);
        $activeRate = ExchangeRate::current();
        $idrRate    = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate    = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));

        return view('admin.companies.usage', compact(
            'company', 'keyName', 'metrics', 'fetchedAt', 'totalCost',
            'idrRate', 'taxRate', 'activeRate', 'startDate', 'endDate'
        ));
    }

    /**
     * Dokumen tagihan (invoice/perincian) siap cetak / Simpan-PDF dari browser.
     * Memakai snapshot CloudWatch yang sama dengan halaman usage (tidak menembak AWS lagi).
     */
    public function invoice(Request $request, Company $company)
    {
        $keyName = $company->aws_api_key_name;

        $startDate = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->query('end', now()->format('Y-m-d'));

        $metrics = ['total' => 0, 'daily' => [], 'operations' => [], 'error' => null];
        $fetchedAt = null;

        if ($keyName) {
            $service  = AwsLocationService::forAccount($company->awsAccount);
            $snapshot = $service->getCachedUsage($keyName, $startDate, $endDate, null, false);
            $metrics  = $snapshot['metrics'];
            $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;
        }

        $operations = $metrics['operations'] ?? [];
        $subtotal   = AwsLocationService::estimateCost($operations);
        $activeRate = ExchangeRate::current();
        $idrRate    = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate    = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));
        $tax        = $subtotal * $taxRate;
        $grand      = $subtotal + $tax;

        // Nomor invoice deterministik dari periode + slug company.
        $invoiceNo = 'INV/' . \Carbon\Carbon::parse($endDate)->format('Ym') . '/' . strtoupper($company->slug);
        $issuedAt  = now();
        $backUrl   = route('admin.companies.usage', ['company' => $company, 'start' => $startDate, 'end' => $endDate]);

        return view('admin.companies.invoice', compact(
            'company', 'keyName', 'metrics', 'operations', 'fetchedAt',
            'subtotal', 'tax', 'grand', 'idrRate', 'taxRate', 'activeRate',
            'startDate', 'endDate', 'invoiceNo', 'issuedAt', 'backUrl'
        ));
    }
}
