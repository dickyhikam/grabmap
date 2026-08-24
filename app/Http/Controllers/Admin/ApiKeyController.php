<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwsAccount;
use App\Models\ApiKeyBudget;
use App\Models\ApiKeyUsageShare;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    /**
     * Akun AWS yang sedang dilihat.
     *
     * Memakai kunci sesi yang SAMA dengan pill akun di topbar ('admin_aws_scope'),
     * supaya tidak ada dua pemilih akun yang bisa berselisih — dulu halaman ini punya
     * sesinya sendiri, jadi dashboard bisa menunjuk akun A sementara daftar key akun B.
     * Parameter ?account= tetap dihormati dan sekaligus memperbarui pilihan itu.
     */
    private function resolveAccount(Request $request): ?AwsAccount
    {
        $account = AwsAccount::resolve($request->input('account') ?: $request->session()->get('admin_aws_scope'));

        if ($account) {
            $request->session()->put('admin_aws_scope', $account->id);
        }

        return $account;
    }

    /** Semua nilai yang sah untuk actions[]: wildcard per grup + aksi satuan. */
    private static function allowedActions(): array
    {
        return collect(config('geo_actions'))
            ->flatMap(fn ($group) => array_merge([$group['wildcard']], $group['actions']))
            ->all();
    }

    /**
     * Ubah pilihan aksi jadi [AllowActions, AllowResources].
     * Kalau satu grup memilih wildcard, aksi satuan grup itu tidak perlu ikut dikirim.
     *
     * @return array{0: array<string>, 1: array<string>}
     */
    private static function buildRestrictions(array $chosen, string $region): array
    {
        $actions = [];
        $resources = [];

        foreach (config('geo_actions') as $group) {
            $picked = array_values(array_intersect($chosen, array_merge([$group['wildcard']], $group['actions'])));

            if (!$picked) {
                continue;
            }

            $actions = array_merge(
                $actions,
                in_array($group['wildcard'], $picked, true) ? [$group['wildcard']] : $picked
            );
            $resources[] = str_replace('{region}', $region, $group['resource']);
        }

        return [array_values(array_unique($actions)), array_values(array_unique($resources))];
    }

    /** Daftar akun untuk switcher di halaman list. */
    private function accountOptions()
    {
        return AwsAccount::query()->active()->orderByDesc('is_default')->orderBy('id')->get();
    }

    public function index(Request $request)
    {
        $account = $this->resolveAccount($request);
        $accounts = $this->accountOptions();

        $hasCredentials = AwsLocationService::hasCredentials($account);
        $keys = [];
        $error = null;

        if ($hasCredentials) {
            $service = AwsLocationService::forAccount($account);
            $result = $service->listApiKeys();
            $keys = $result['keys'];
            $error = $result['error'];
        }

        // Hanya company milik akun ini yang relevan — nama key bisa sama di akun berbeda.
        $companies = Company::latest()->get();
        $accountCompanies = $account
            ? $companies->where('aws_account_id', $account->id)
            : $companies;

        // Build a map of which key is assigned to which company
        $assignedKeys = [];
        foreach ($accountCompanies as $company) {
            if ($company->aws_api_key) {
                $assignedKeys[$company->aws_api_key_name ?? ''] = $company;
            }
        }

        $region = $account?->region ?: config('aws.region');

        return view('admin.api-keys.index', compact(
            'hasCredentials', 'keys', 'error', 'companies', 'accountCompanies',
            'assignedKeys', 'account', 'accounts', 'region'
        ));
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'key_name'   => 'required|string',
            'company_id' => 'required|exists:companies,id',
        ]);

        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return back()->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $service = AwsLocationService::forAccount($account);
        $result = $service->describeKey($validated['key_name']);

        if ($result['error']) {
            return back()->with('error', 'Gagal mengambil detail key: ' . $result['error']);
        }

        $keyValue = $result['key']['key'] ?? null;
        if (!$keyValue) {
            return back()->with('error', 'API key value tidak ditemukan untuk key ini.');
        }

        $company = Company::findOrFail($validated['company_id']);
        $company->update([
            'aws_account_id'   => $account?->id,
            'aws_api_key'      => $keyValue,
            'aws_api_key_name' => $validated['key_name'],
            'aws_key_active'   => true,
        ]);

        $accountNote = $account ? " (akun {$account->name})" : '';

        return back()->with('success', "API Key \"{$validated['key_name']}\"{$accountNote} berhasil di-assign ke {$company->name}.");
    }

    public function unassign(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $company->update([
            'aws_account_id'   => null,
            'aws_api_key'      => null,
            'aws_api_key_name' => null,
            'aws_key_active'   => true,
        ]);

        return back()->with('success', "API Key berhasil di-lepas dari {$company->name}.");
    }

    public function create(Request $request)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }
        $region = $account?->region ?: config('aws.region', 'ap-southeast-1');

        // View-nya sama dengan halaman ubah, hanya tanpa $key.
        return view('admin.api-keys.form', [
            'key'     => null,
            'keyName' => null,
            'region'  => $region,
            'account' => $account,
            'budget'  => null,
        ]);
    }

    public function store(Request $request)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        // datetime-local emits Y-m-d\TH:i; normalize to Y-m-d H:i before validation
        if ($request->filled('expire_date')) {
            $request->merge([
                'expire_date' => str_replace('T', ' ', (string) $request->input('expire_date')),
            ]);
        }

        $validated = $request->validate([
            'key_name'      => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.\-]+$/'],
            'description'   => 'nullable|string|max:1000',
            'actions'       => 'required|array|min:1',
            'actions.*'     => ['string', Rule::in(self::allowedActions())],
            'allow_referers' => 'nullable|string|max:2000',
            'expiry_mode'   => 'required|in:never,date,preset',
            'expire_date'   => ['exclude_unless:expiry_mode,date', 'required_if:expiry_mode,date', 'nullable', 'date_format:Y-m-d H:i', 'after:now'],
            'preset_days'   => ['exclude_unless:expiry_mode,preset', 'required_if:expiry_mode,preset', 'nullable', 'integer', 'in:30,90,180,365'],
            'budget_usd'    => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'actions.required' => 'Pilih minimal satu aksi yang boleh dipakai key ini.',
            'key_name.regex' => 'Key name boleh berisi huruf, angka, underscore, hyphen, dan titik.',
            'expire_date.required_if' => 'Tanggal expiry wajib diisi saat memilih Custom.',
            'preset_days.required_if' => 'Pilih durasi preset (30, 90, 180, atau 365 hari).',
        ]);

        $region = $account?->region ?: config('aws.region', 'ap-southeast-1');

        // Aksi yang dipilih menentukan AllowActions; resource provider ikut per grup
        // yang tersentuh — AWS menolak aksi tanpa resource yang bersesuaian.
        [$actions, $resources] = self::buildRestrictions($validated['actions'], $region);

        $restrictions = [
            'AllowActions'   => array_values(array_unique($actions)),
            'AllowResources' => array_values(array_unique($resources)),
        ];

        if (!empty($validated['allow_referers'])) {
            $referers = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $validated['allow_referers'])
            )));
            if ($referers) {
                $restrictions['AllowReferers'] = $referers;
            }
        }

        $params = [
            'key_name'     => $validated['key_name'],
            'description'  => $validated['description'] ?? '',
            'restrictions' => $restrictions,
        ];

        // required_if + exclude_unless guarantees the right field is present for the chosen mode
        if ($validated['expiry_mode'] === 'never') {
            $params['no_expiry'] = true;
        } elseif ($validated['expiry_mode'] === 'date') {
            $params['expire_time'] = \Carbon\Carbon::parse($validated['expire_date']);
        } else { // preset
            $params['expire_time'] = now()->addDays((int) $validated['preset_days']);
        }

        $service = AwsLocationService::forAccount($account);
        $result = $service->createKey($params);

        // Hard create failure (CreateKey call itself failed)
        if (!($result['created'] ?? false)) {
            return back()->with('error', 'Gagal membuat API Key: ' . ($result['error'] ?? 'Unknown error'))->withInput();
        }

        // Batas biaya disimpan lokal (bukan ke AWS) — key sudah pasti ada di titik ini.
        ApiKeyBudget::set($account?->id, $validated['key_name'], $validated['budget_usd'] ?? null, $request->user()?->name);

        // Create succeeded but DescribeKey failed afterwards — key exists, just couldn't load details
        if ($result['error']) {
            return redirect()->route('admin.api-keys.index')
                ->with('warning', "API Key \"{$validated['key_name']}\" berhasil dibuat, tetapi gagal memuat detailnya: {$result['error']}. Refresh daftar untuk melihat key.");
        }

        return redirect()->route('admin.api-keys.index')
            ->with('success', "API Key \"{$validated['key_name']}\" berhasil dibuat.");
    }

    public function edit(Request $request, string $keyName)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $service = AwsLocationService::forAccount($account);
        $result = $service->describeKey($keyName);

        if ($result['error']) {
            return redirect()->route('admin.api-keys.index')->with('error', 'Gagal mengambil detail key: ' . $result['error']);
        }

        return view('admin.api-keys.form', [
            'key'     => $result['key'],
            'keyName' => $keyName,
            'region'  => $account?->region ?: config('aws.region', 'ap-southeast-1'),
            'account' => $account,
            'budget'  => ApiKeyBudget::forKey($account?->id, $keyName),
        ]);
    }

    public function update(Request $request, string $keyName)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        if ($request->filled('expire_date')) {
            $request->merge([
                'expire_date' => str_replace('T', ' ', (string) $request->input('expire_date')),
            ]);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:1000',
            'expiry_mode' => 'required|in:never,date,preset',
            'expire_date' => ['exclude_unless:expiry_mode,date', 'required_if:expiry_mode,date', 'nullable', 'date_format:Y-m-d H:i', 'after:now'],
            'preset_days' => ['exclude_unless:expiry_mode,preset', 'required_if:expiry_mode,preset', 'nullable', 'integer', 'in:30,90,180,365'],
            'budget_usd'  => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'expire_date.required_if' => 'Tanggal expiry wajib diisi saat memilih Custom.',
            'preset_days.required_if' => 'Pilih durasi preset (30, 90, 180, atau 365 hari).',
        ]);

        $params = [
            'description'  => $validated['description'] ?? '',
            'force_update' => true,
        ];

        if ($validated['expiry_mode'] === 'never') {
            $params['no_expiry'] = true;
        } elseif ($validated['expiry_mode'] === 'date') {
            $params['expire_time'] = \Carbon\Carbon::parse($validated['expire_date']);
        } else { // preset
            $params['expire_time'] = now()->addDays((int) $validated['preset_days']);
        }

        $service = AwsLocationService::forAccount($account);
        $result = $service->updateKey($keyName, $params);

        if ($result['error']) {
            return back()->with('error', 'Gagal update API Key: ' . $result['error'])->withInput();
        }

        // Batas biaya hanya tersimpan di aplikasi — tidak ikut dikirim ke AWS.
        ApiKeyBudget::set($account?->id, $keyName, $validated['budget_usd'] ?? null, $request->user()?->name);

        // Bust cache so usage page reloads fresh
        Cache::forget($service->cacheKey("aws_key_info:{$keyName}"));

        return redirect()->route('admin.api-keys.index')->with('success', "API Key \"{$keyName}\" berhasil diperbarui.");
    }

    public function usage(Request $request, string $keyName)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return redirect()->route('admin.api-keys.index')->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $startDate = $request->query('start', now()->subDays(29)->format('Y-m-d'));
        $endDate = $request->query('end', now()->format('Y-m-d'));

        // Validate dates
        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            if ($end->lt($start)) {
                $startDate = now()->subDays(29)->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
            }
        } catch (\Exception $e) {
            $startDate = now()->subDays(29)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
        }

        $days = $start->diffInDays($end) + 1;

        $filterOperation = $request->query('operation');
        $operations = AwsLocationService::getOperations();
        if ($filterOperation && !in_array($filterOperation, $operations)) {
            $filterOperation = null;
        }

        $refresh = $request->query('refresh') === '1';
        $cacheTtl = 30 * 60; // 30 minutes

        $service = AwsLocationService::forAccount($account);

        // Cache key info (ber-namespace akun: nama key bisa sama di akun berbeda)
        $keyInfoCacheKey = $service->cacheKey("aws_key_info:{$keyName}");
        if ($refresh) {
            Cache::forget($keyInfoCacheKey);
        }
        $keyResult = Cache::remember($keyInfoCacheKey, $cacheTtl, fn() => $service->describeKey($keyName));
        $keyInfo = $keyResult['key'];
        $keyError = $keyResult['error'];

        // Metrik usage: model "manual refresh" — hanya menembak AWS saat tombol Refresh ditekan
        // atau saat snapshot belum ada. Selain itu pakai snapshot terakhir + waktu pengambilannya.
        $snapshot = $service->getCachedUsage($keyName, $startDate, $endDate, $filterOperation, $refresh);
        $metrics = $snapshot['metrics'];
        $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;

        $assignedCompany = Company::where('aws_api_key_name', $keyName)
            ->when($account, fn ($q) => $q->where('aws_account_id', $account->id))
            ->first();

        // Kurs & PPN diambil dari menu Pengaturan Biaya (bukti & history) — bukan hardcode.
        $activeRate = ExchangeRate::current();
        $idrRate = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));

        $budget = ApiKeyBudget::forKey($account?->id, $keyName);
        $share = ApiKeyUsageShare::forKey($account?->id, $keyName);

        return view('admin.api-keys.usage', compact(
            'keyName', 'keyInfo', 'keyError', 'metrics', 'assignedCompany',
            'startDate', 'endDate', 'days', 'filterOperation', 'operations',
            'fetchedAt', 'idrRate', 'taxRate', 'activeRate', 'account', 'budget', 'share'
        ));
    }

    /**
     * Dokumen tagihan (invoice/perincian) per API key — siap cetak / Simpan-PDF.
     * Memakai snapshot CloudWatch yang sama dengan halaman usage (tidak menembak AWS lagi).
     */
    public function invoice(Request $request, string $keyName)
    {
        $startDate = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->query('end', now()->format('Y-m-d'));

        $account   = $this->resolveAccount($request);
        $service   = AwsLocationService::forAccount($account);
        $snapshot  = $service->getCachedUsage($keyName, $startDate, $endDate, null, false);
        $metrics   = $snapshot['metrics'];
        $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;

        // Kalau key ini terhubung ke company, pakai identitas company-nya.
        $company = Company::where('aws_api_key_name', $keyName)
            ->when($account, fn ($q) => $q->where('aws_account_id', $account->id))
            ->first();

        $operations = $metrics['operations'] ?? [];
        $subtotal   = AwsLocationService::estimateCost($operations);
        $activeRate = ExchangeRate::current();
        $idrRate    = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate    = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));
        $tax        = $subtotal * $taxRate;
        $grand      = $subtotal + $tax;

        $slugPart  = $company ? strtoupper($company->slug) : strtoupper($keyName);
        $invoiceNo = 'INV/' . \Carbon\Carbon::parse($endDate)->format('Ym') . '/' . $slugPart;
        $issuedAt  = now();
        $backUrl   = route('admin.api-keys.usage', [
            'keyName' => $keyName,
            'start'   => $startDate,
            'end'     => $endDate,
            'account' => $account?->id,
        ]);

        return view('admin.companies.invoice', compact(
            'company', 'keyName', 'metrics', 'operations', 'fetchedAt',
            'subtotal', 'tax', 'grand', 'idrRate', 'taxRate', 'activeRate',
            'startDate', 'endDate', 'invoiceNo', 'issuedAt', 'backUrl'
        ));
    }

    public function enableShare(Request $request, string $keyName)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return back()->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $validated = $request->validate([
            'expires_days' => ['nullable', 'integer', 'in:30,90,180,365'],
        ]);

        $expiresAt = isset($validated['expires_days'])
            ? now()->addDays((int) $validated['expires_days'])
            : null;

        ApiKeyUsageShare::enable($account?->id, $keyName, $request->user()?->name, $expiresAt);

        return back()->with('success', __('apikeys.share_enabled'));
    }

    public function disableShare(Request $request, string $keyName)
    {
        $account = $this->resolveAccount($request);
        $share = ApiKeyUsageShare::forKey($account?->id, $keyName);

        $share?->disable();

        return back()->with('success', __('apikeys.share_disabled'));
    }

    public function regenerateShare(Request $request, string $keyName)
    {
        $account = $this->resolveAccount($request);

        if (!AwsLocationService::hasCredentials($account)) {
            return back()->with('error', 'AWS credentials belum dikonfigurasi.');
        }

        $share = ApiKeyUsageShare::forKey($account?->id, $keyName);

        if ($share) {
            $share->regenerateToken($request->user()?->name);
        } else {
            ApiKeyUsageShare::enable($account?->id, $keyName, $request->user()?->name);
        }

        return back()->with('success', __('apikeys.share_regenerated'));
    }
}
