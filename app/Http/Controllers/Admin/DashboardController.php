<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKeyBudget;
use App\Models\ApiKeyDisable;
use App\Models\ApiKeyUsageDaily;
use App\Models\ApiKeyUsageShare;
use App\Models\CompanyApiKey;
use App\Models\UsageShareVisit;
use App\Models\AwsAccount;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    // Pemetaan operasi AWS -> kategori (memakai nama API baru).
    private const CATEGORY_OPS = [
        'maps'   => ['GetMapTile', 'GetTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites'],
        'places' => ['SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace'],
        'routes' => ['CalculateRoutes', 'CalculateRouteMatrix'],
    ];

    /** Rentang terpanjang yang boleh diminta sekali jalan — penjaga biaya CloudWatch. */
    private const MAX_RANGE_DAYS = 92;

    /**
     * Baca ?start=&end= lalu jepit ke rentang yang masuk akal:
     * tidak melewati hari ini, tidak melampaui retensi CloudWatch (15 bulan),
     * dan tidak lebih panjang dari MAX_RANGE_DAYS. Input ngawur → kembali ke default.
     *
     * @return array{0: string, 1: string} [startDate, endDate] format Y-m-d
     */
    private function resolveRange(Request $request): array
    {
        $parse = function (?string $value): ?\Carbon\Carbon {
            if (!$value) {
                return null;
            }
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        };

        $today  = now()->startOfDay();
        $oldest = $today->copy()->subMonths(15);

        $start = $parse($request->query('start'));
        $end   = $parse($request->query('end'));

        if (!$start || !$end) {
            return [now()->startOfMonth()->format('Y-m-d'), $today->format('Y-m-d')];
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $end   = $end->min($today)->max($oldest);
        $start = $start->min($end)->max($oldest);

        if ((int) floor($start->diffInDays($end)) + 1 > self::MAX_RANGE_DAYS) {
            $start = $end->copy()->subDays(self::MAX_RANGE_DAYS - 1);
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    public function index(Request $request)
    {
        // Statistik company
        $totalCompanies   = Company::count();
        $activeCompanies  = Company::where('is_active', true)->count();
        $companiesWithKey = Company::whereNotNull('aws_api_key_name')->count();

        // Rentang: default bulan berjalan (selaras siklus tagihan AWS), bisa diatur
        // lewat ?start=&end= dari pemilih tanggal.
        $refresh = $request->query('refresh') === '1';
        [$startDate, $endDate] = $this->resolveRange($request);

        // Dashboard menampilkan SATU akun AWS, tidak pernah menggabungkan beberapa akun.
        // Pilihannya dari pill di topbar (disimpan di sesi); kalau belum memilih, pakai
        // akun default. Null hanya terjadi kalau memang belum ada akun tersimpan —
        // saat itu service jatuh ke kredensial .env.
        $scopeAccount = null;
        if ($scopeId = session('admin_aws_scope')) {
            $scopeAccount = AwsAccount::query()->active()->find($scopeId);
            if (!$scopeAccount) {
                session()->forget('admin_aws_scope');   // akunnya dihapus/dinonaktifkan
            }
        }
        $scopeAccount = $scopeAccount ?: AwsAccount::defaultAccount();

        $apiKeysData = ['total' => 0, 'active' => 0];
        $cw          = ['total' => 0, 'operations' => [], 'by_key' => [], 'daily' => [], 'error' => null];
        $fetchedAt   = null;

        if (AwsLocationService::hasCredentials($scopeAccount)) {
            $keysCacheKey = 'dashboard_api_keys:' . ($scopeAccount?->id ?? 'all');

            if ($refresh) {
                Cache::forget($keysCacheKey);
            }

            // Jumlah API key pada akun yang sedang dilihat (murah, boleh cache otomatis).
            $apiKeysData = Cache::remember($keysCacheKey, 30 * 60, function () use ($scopeAccount) {
                $accounts = $scopeAccount
                    ? collect([$scopeAccount])
                    : AwsAccount::query()->active()->get();
                $accounts = $accounts->filter->hasCredentials();

                $services = $accounts->isEmpty()
                    ? collect([new AwsLocationService()])           // belum ada akun tersimpan → kredensial .env
                    : $accounts->map(fn ($a) => AwsLocationService::forAccount($a));

                $total = 0;
                $active = 0;
                $expiring = [];         // aktif tapi tinggal < 14 hari

                foreach ($services as $service) {
                    $accountId = $service->account()?->id;
                    $off = ApiKeyDisable::mapFor($accountId);
                    $keys = $service->listApiKeys()['keys'] ?? [];
                    $total += count($keys);

                    foreach ($keys as $key) {
                        $expire = $key['expire_time'] ? \Carbon\Carbon::parse($key['expire_time']) : null;

                        if ($expire && $expire->isPast()) {
                            continue;                       // kedaluwarsa / dinonaktifkan
                        }

                        $active++;

                        if ($expire && $expire->lt(now()->addDays(14))) {
                            $expiring[] = ['name' => $key['key_name'], 'at' => $expire->toIso8601String()];
                        }
                    }
                }

                return ['total' => $total, 'active' => $active, 'expiring' => $expiring];
            });

            // Usage agregat — model manual refresh (TIDAK menembak AWS tiap load).
            // Tanpa cakupan, semua akun aktif dijumlahkan; dengan cakupan, hanya satu akun.
            $snapshot  = AwsLocationService::aggregateAcrossAccounts($startDate, $endDate, $refresh, $scopeAccount);
            $cw        = $snapshot['data'];
            $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;
        }

        $operations    = $cw['operations'] ?? [];
        $totalRequests = $cw['total'] ?? 0;
        $totalCost     = AwsLocationService::estimateCost($operations);

        // Jumlah & biaya per kategori (harga per-operasi yang akurat, bukan rata-rata kategori).
        $catCount = ['maps' => 0, 'places' => 0, 'routes' => 0];
        $catCost  = ['maps' => 0.0, 'places' => 0.0, 'routes' => 0.0];
        foreach ($operations as $op => $count) {
            foreach (self::CATEGORY_OPS as $cat => $ops) {
                if (in_array($op, $ops, true)) {
                    $catCount[$cat] += $count;
                    $catCost[$cat]  += AwsLocationService::estimateCost([$op => $count]);
                    break;
                }
            }
        }

        // Kurs & pajak (sumber kebenaran: menu Kurs & Pajak).
        $activeRate = ExchangeRate::current();
        $idrRate    = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate    = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));
        $tax        = $totalCost * $taxRate;
        $grandCost  = $totalCost + $tax;

        // Ambang peringatan budget (selaras AWS Budgets) — default $170.
        $budgetAlert = (float) Setting::get('budget_alert_usd', 170);

        // Peta API key -> company (untuk label "paling banyak dipakai").
        $companyByKey = Company::whereNotNull('aws_api_key_name')->get()->keyBy('aws_api_key_name');

        // Batas biaya per API key (peringatan sisi aplikasi, bukan AWS Budgets).
        // Rincian per key sudah ikut di snapshot, jadi tidak ada panggilan AWS tambahan.
        $keyBudgets = ApiKeyBudget::evaluate($cw['by_key_ops'] ?? [])
            ->filter(fn ($row) => $row['state'] !== 'ok')
            ->values();

        // ── Bahan kartu "Perlu perhatian" & "Laporan klien" ──────────────
        // Semuanya dibaca dari database lokal; tidak ada panggilan AWS tambahan.
        $disabledKeys = ApiKeyDisable::query()
            ->when($scopeAccount, fn ($q) => $q->where('aws_account_id', $scopeAccount->id))
            ->count();

        $expiringKeys = collect($apiKeysData['expiring'] ?? [])
            ->map(fn ($k) => ['name' => $k['name'], 'at' => \Carbon\Carbon::parse($k['at'])])
            ->sortBy('at')
            ->values();

        // Perusahaan tanpa key sama sekali → laporannya pasti kosong.
        $companiesWithoutKey = Company::query()
            ->where('is_active', true)
            ->whereDoesntHave('apiKeys')
            ->count();

        // Key milik perusahaan yang belum pernah ditarik datanya.
        $keysNeverPulled = CompanyApiKey::query()
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                    ->from('api_key_usage_daily')
                    ->whereColumn('api_key_usage_daily.key_name', 'company_api_keys.key_name')
                    ->whereRaw('COALESCE(api_key_usage_daily.aws_account_id, 0) = COALESCE(company_api_keys.aws_account_id, 0)');
            })
            ->count();

        $lastPull = ApiKeyUsageDaily::max('synced_at');
        $lastPull = $lastPull ? \Carbon\Carbon::parse($lastPull) : null;

        // Laporan yang dibagikan ke klien.
        $shareStats = [
            'active'  => ApiKeyUsageShare::query()->where('share_enabled', true)->whereNotNull('company_id')->count(),
            'visits'  => UsageShareVisit::query()->where('last_seen_at', '>=', now()->subDays(7))->sum('hits'),
            'readers' => UsageShareVisit::query()->where('last_seen_at', '>=', now()->subDays(7))
                ->distinct('ip_address')->count('ip_address'),
            'last'    => UsageShareVisit::query()->latest('last_seen_at')->first(),
        ];

        // Rincian per akun AWS — hanya relevan kalau memang ada lebih dari satu akun.
        $byAccount    = $cw['by_account'] ?? [];
        $awsAccounts  = AwsAccount::query()->active()->orderByDesc('is_default')->orderBy('id')->get();

        return view('admin.dashboard', compact(
            'totalCompanies', 'activeCompanies', 'companiesWithKey',
            'apiKeysData', 'fetchedAt', 'cw', 'operations', 'totalRequests',
            'totalCost', 'tax', 'grandCost', 'catCount', 'catCost',
            'activeRate', 'idrRate', 'taxRate', 'budgetAlert', 'companyByKey',
            'startDate', 'endDate', 'byAccount', 'awsAccounts', 'scopeAccount', 'keyBudgets',
            'disabledKeys', 'expiringKeys', 'companiesWithoutKey', 'keysNeverPulled', 'lastPull', 'shareStats'
        ));
    }

}
