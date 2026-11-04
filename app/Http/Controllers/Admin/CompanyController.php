<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwsAccount;
use App\Models\ApiKeyUsageShare;
use App\Models\AwsConnectInvite;
use App\Models\Company;
use App\Models\ServiceCharge;
use App\Models\CompanyApiKey;
use App\Models\UsageShareVisit;
use App\Models\ExchangeRate;
use App\Models\PipelineStage;
use App\Models\Setting;
use App\Services\AwsLocationService;
use App\Services\AwsRoleConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /** Nilai key_ref untuk "buat API key baru" di formulir tambah perusahaan. */
    public const NEW_KEY = '__new';

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
        return view('admin.companies.form', [
            'availableKeys'  => $this->availableKeys(),
            'inviteSources'  => AwsAccount::assumeRoleSources(),
            'pipelineStages' => PipelineStage::ordered(),
        ]);
    }

    public function store(Request $request, AwsRoleConnector $connector)
    {
        ApiKeyController::normalizeExpireDate($request);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:companies,slug|regex:/^[a-z0-9\-]+$/',
            'logo'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'is_active'    => 'nullable|boolean',
            'aws_api_key'  => 'nullable|string|max:1000',
            'aws_key_active' => 'nullable|boolean',
            // "akunId|namaKey", bentuk yang sama dengan dropdown di halaman detail.
            'key_ref'      => 'nullable|string|max:160',
            // Undangan akun AWS klien (opsional): klien menghubungkan akunnya sendiri.
            'connect_aws'       => 'nullable|boolean',
            // Kolom board pipeline (uuid); kosong = kolom pertama.
            'pipeline_stage'    => ['nullable', 'uuid', 'exists:pipeline_stages,uuid'],
            'connect_source_id' => ['required_if:connect_aws,1', 'required_if:key_ref,' . self::NEW_KEY, 'nullable', 'integer', Rule::in(AwsAccount::assumeRoleSources()->pluck('id')->all())],
        ] + ServiceCharge::validationRules(true) + $this->newKeyRules(), ServiceCharge::validationMessages() + [
            'connect_source_id.in' => __('awsconnect.err_source'),
        ] + ApiKeyController::newKeyMessages());

        // Key baru dibuat lebih dulu: kalau AWS menolak, belum ada yang tersimpan
        // dan formulir kembali utuh untuk diperbaiki.
        $newKey = null;
        if (($validated['key_ref'] ?? null) === self::NEW_KEY) {
            abort_unless($request->user()?->can('api_keys.create'), 403);

            $newKey = $this->createKeyFromForm($validated, AwsAccount::findOrFail($validated['connect_source_id']), $request->user()?->name);
            if (is_string($newKey)) {
                return back()->withInput()->with('error', $newKey);
            }
        }

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
            'aws_api_key'    => $apiKey && $apiKey !== '********' ? $apiKey : null,
            'aws_key_active' => $request->boolean('aws_key_active', true),
            'pipeline_stage_id' => !empty($validated['pipeline_stage'])
                ? PipelineStage::where('uuid', $validated['pipeline_stage'])->value('id')
                : null,
        ]);

        // Key ditempel lebih dulu: akun perusahaan ikut akun key itu, dan tarif
        // "ikut akun" di bawah ini perlu akunnya sudah terisi.
        $keyWarning = $newKey
            ? $this->attachNewKey($company, $newKey)
            : $this->attachChosenKey($company, $validated['key_ref'] ?? null);

        ServiceCharge::saveFromRequest($request, $company->fresh()->aws_account_id, $company->id);

        // Fitur peta tidak lagi diatur dari formulir perusahaan — pembatas yang
        // sebenarnya ada di izin API key-nya. Barisnya tetap dibuat aktif supaya
        // halaman peta klien punya nilai bawaan yang masuk akal.
        $company->ensureDefaultFeatures();

        if ($request->boolean('connect_aws')) {
            $inviteWarning = $this->issueInvite($request, $company, $connector);

            // Halaman detail menampilkan link undangannya untuk disalin.
            return redirect()->route('admin.companies.show', $company)
                ->with('success', "Company \"{$company->name}\" berhasil dibuat.")
                ->with('warning', $inviteWarning ?? $keyWarning);
        }

        // Dari board: kembali ke board dengan panel perusahaan baru terbuka.
        $to = $request->input('from') === 'pipeline'
            ? route('admin.pipeline.index', ['c' => $company->getRouteKey()])
            : route('admin.companies.index');

        return redirect($to)
            ->with('success', "Company \"{$company->name}\" berhasil dibuat.")
            ->with('warning', $keyWarning);
    }

    /**
     * Aturan isian key baru, sama dengan halaman tambah API key, tapi hanya
     * berlaku saat "buat API key baru" dipilih.
     */
    private function newKeyRules(): array
    {
        return collect(ApiKeyController::newKeyRules())
            ->map(fn ($rules) => array_merge(['exclude_unless:key_ref,' . self::NEW_KEY], (array) $rules))
            ->all();
    }

    /**
     * Buat API key baru di akun sumber dari isian formulir perusahaan.
     *
     * @return array{account: AwsAccount, name: string, value: ?string, warning: ?string}|string
     *         string = pesan galat; key tidak terbuat.
     */
    private function createKeyFromForm(array $validated, AwsAccount $account, ?string $by): array|string
    {
        $region = $account->region ?: config('aws.region', 'ap-southeast-1');
        $params = ApiKeyController::newKeyParams($validated, $region);

        $result = AwsLocationService::forAccount($account)->createKey($params);

        if (!($result['created'] ?? false)) {
            return 'Gagal membuat API Key: ' . (ApiKeyController::friendlyAwsError($result['error']) ?? 'Unknown error');
        }

        // Batas biaya disimpan lokal (bukan ke AWS) — key sudah pasti ada di titik ini.
        \App\Models\ApiKeyBudget::set($account->id, $params['key_name'], $validated['budget_usd'] ?? null, $by);

        return [
            'account' => $account,
            'name'    => $params['key_name'],
            'value'   => $result['key']['key'] ?? null,
            // Key sudah ada di AWS; DescribeKey yang gagal hanya membuat nilai key kosong.
            'warning' => $result['error'] ? __('companies.new_key_no_value', ['error' => $result['error']]) : null,
        ];
    }

    /** Tempelkan key yang baru dibuat sebagai key utama, sekaligus jadi key peta kalau belum diisi. */
    private function attachNewKey(Company $company, array $newKey): ?string
    {
        $company->apiKeys()->create([
            'aws_account_id' => $newKey['account']->id,
            'key_name'       => $newKey['name'],
            'is_primary'     => true,
        ]);
        $company->syncPrimaryKeyName();

        if ($newKey['value'] && !$company->aws_api_key) {
            $company->update(['aws_api_key' => $newKey['value']]);
        }

        return $newKey['warning'];
    }

    /**
     * Buat undangan akun AWS klien untuk perusahaan yang baru dibuat. Perusahaannya
     * sudah tersimpan, jadi kegagalan di sini hanya peringatan.
     *
     * @return string|null Pesan peringatan kalau undangannya tidak jadi dibuat.
     */
    private function issueInvite(Request $request, Company $company, AwsRoleConnector $connector): ?string
    {
        $source = AwsAccount::find($request->integer('connect_source_id'));

        try {
            $trusted = $connector->callerAccountId($source);
        } catch (\Throwable $e) {
            return __('awsconnect.err_identity', [
                'name'  => $source->name,
                'error' => $e instanceof \Aws\Exception\AwsException ? ($e->getAwsErrorMessage() ?: $e->getMessage()) : $e->getMessage(),
            ]);
        }

        AwsConnectInvite::issue(
            $source,
            $trusted,
            $company->name,
            $company->id,
            (string) $request->input('connect_region'),
            $request->integer('connect_days'),
            $request->user()?->id,
        );

        return null;
    }

    /**
     * Tempelkan key yang dipilih di formulir tambah perusahaan.
     *
     * Daftar key di formulir bisa sudah basi kalau ada yang mengklaim key yang
     * sama di sela-sela pengisian, jadi kepemilikannya diperiksa ulang di sini.
     * Perusahaannya sendiri sudah telanjur dibuat, maka kegagalan di sini
     * dilaporkan sebagai peringatan — bukan membatalkan apa pun.
     *
     * @return string|null Pesan peringatan kalau key-nya tidak jadi menempel.
     */
    private function attachChosenKey(Company $company, ?string $keyRef): ?string
    {
        if (!$keyRef) {
            return null;
        }

        [$accountId, $keyName] = array_pad(explode('|', $keyRef, 2), 2, null);
        $accountId = $accountId === '' ? null : (int) $accountId;

        if (!$keyName) {
            return __('companies.key_invalid');
        }

        if ($owner = CompanyApiKey::ownerOf($accountId, $keyName)) {
            return __('companies.key_taken', ['name' => $owner->name]);
        }

        $company->apiKeys()->create([
            'aws_account_id' => $accountId,
            'key_name'       => $keyName,
            'is_primary'     => true,
        ]);

        $company->syncPrimaryKeyName();

        return null;
    }

    public function edit(Company $company)
    {
        $today = now()->wib()->toDateString();
        $own = ServiceCharge::history(null, $company->id)
            ->first(fn ($row) => $row->effective_from->toDateString() <= $today);

        return view('admin.companies.form', [
            'company'       => $company,
            'scCurrent'     => $own,
            'scAccountRule' => $company->aws_account_id ? ServiceCharge::ruleFor($company->aws_account_id, null, $today) : null,
            'scHistory'     => ServiceCharge::history(null, $company->id),
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
            'awsInvites.awsAccount',
            'awsInvites.usageShare',
            'usageShares.keys',
            // Riwayat akses ditampilkan ringkas — 8 kunjungan terakhir per link.
            'usageShares.visits' => fn ($q) => $q->limit(8),
        ]);
        $company->usageShares->loadCount('visits')->loadSum('visits', 'hits');

        return view('admin.companies.show', [
            'company'       => $company,
            'availableKeys' => $this->availableKeys(),
            'inviteSources' => AwsAccount::assumeRoleSources(),
        ]);
    }

    /**
     * Key yang belum diklaim perusahaan mana pun, dikelompokkan per akun AWS —
     * satu perusahaan boleh memegang key dari lebih dari satu akun. Tiap akun
     * cukup satu panggilan ListKeys (hasilnya di-cache service).
     *
     * Keterangan tiap key ikut dibawa (masa berlaku, aksi yang diizinkan) supaya
     * yang memilih tahu key mana yang diambilnya. Semua itu sudah ada di hasil
     * ListKeys — tidak ada panggilan DescribeKey tambahan per key.
     *
     * @return array<int, array{id: ?int, name: string, keys: array<int, array>}>
     */
    private function availableKeys(): array
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
                    ->filter(fn ($key) => !empty($key['key_name']))
                    ->reject(fn ($key) => in_array($account?->id . '|' . $key['key_name'], $claimed, true))
                    ->map(fn ($key) => [
                        'name'        => $key['key_name'],
                        'description' => $key['description'] ?? '',
                        'expire_time' => $key['expire_time'] ?? null,
                        'actions'     => $key['restrictions']['AllowActions'] ?? [],
                        'referers'    => $key['restrictions']['AllowReferers'] ?? [],
                    ])
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

        ['done' => $done, 'failed' => $failed] = $company->refreshUsage($startDate, $endDate);

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
            'aws_api_key'  => 'nullable|string|max:1000',
            'aws_key_active' => 'nullable|boolean',
        ] + ServiceCharge::validationRules(true), ServiceCharge::validationMessages());

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
            'aws_key_active' => $request->boolean('aws_key_active', true),
        ];

        $apiKeyInput = $request->input('aws_api_key');
        if ($apiKeyInput && $apiKeyInput !== '********') {
            $updateData['aws_api_key'] = $apiKeyInput;
        } elseif ($apiKeyInput === '' || $apiKeyInput === null) {
            $updateData['aws_api_key'] = null;
        }

        $company->update($updateData);

        ServiceCharge::saveFromRequest($request, $company->aws_account_id, $company->id);

        // Perusahaan lama bisa saja belum punya baris fitur — lengkapi seperlunya,
        // tapi jangan mengubah yang sudah ada.
        $company->ensureDefaultFeatures();

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

        $sc = ServiceCharge::calculate($company->aws_account_id, $company->id, $totalCost, $startDate, $endDate, $idrRate, $taxRate);

        return view('admin.companies.usage', compact(
            'company', 'keyName', 'metrics', 'fetchedAt', 'totalCost', 'sc',
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
        // PPN hanya atas biaya AWS; service charge PT Alfa ditambahkan setelahnya.
        $sc         = ServiceCharge::calculate($company->aws_account_id, $company->id, $subtotal, $startDate, $endDate, $idrRate, $taxRate);
        $tax        = $sc['tax'];
        $totalVat   = $sc['total_vat'];
        $grand      = $sc['grand_idr'];

        // Nomor invoice deterministik dari periode + slug company.
        $invoiceNo = 'INV/' . \Carbon\Carbon::parse($endDate)->format('Ym') . '/' . strtoupper($company->slug);
        $issuedAt  = now();
        $backUrl   = route('admin.companies.usage', ['company' => $company, 'start' => $startDate, 'end' => $endDate]);

        return view('admin.companies.invoice', compact(
            'company', 'keyName', 'metrics', 'operations', 'fetchedAt',
            'subtotal', 'sc', 'tax', 'totalVat', 'grand', 'idrRate', 'taxRate', 'activeRate',
            'startDate', 'endDate', 'invoiceNo', 'issuedAt', 'backUrl'
        ));
    }
}
