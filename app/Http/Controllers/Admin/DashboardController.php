<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function index(Request $request)
    {
        // Statistik company
        $totalCompanies   = Company::count();
        $activeCompanies  = Company::where('is_active', true)->count();
        $companiesWithKey = Company::whereNotNull('aws_api_key_name')->count();

        // Rentang: bulan berjalan (selaras siklus tagihan AWS).
        $refresh   = $request->query('refresh') === '1';
        $startDate = now()->startOfMonth()->format('Y-m-d');
        $endDate   = now()->format('Y-m-d');

        $apiKeysData = ['total' => 0, 'active' => 0];
        $cw          = ['total' => 0, 'operations' => [], 'by_key' => [], 'daily' => [], 'error' => null];
        $fetchedAt   = null;

        if (AwsLocationService::hasCredentials()) {
            $service = new AwsLocationService();

            // Jumlah API key (murah, boleh cache otomatis).
            $apiKeysData = Cache::remember('dashboard_api_keys', 30 * 60, function () use ($service) {
                $keys = $service->listApiKeys()['keys'] ?? [];
                $active = collect($keys)
                    ->filter(fn ($k) => !($k['expire_time'] && \Carbon\Carbon::parse($k['expire_time'])->isPast()))
                    ->count();
                return ['total' => count($keys), 'active' => $active];
            });

            // Usage agregat — model manual refresh (TIDAK menembak AWS tiap load).
            $snapshot  = $service->getCachedAggregateUsage($startDate, $endDate, $refresh);
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

        return view('admin.dashboard', compact(
            'totalCompanies', 'activeCompanies', 'companiesWithKey',
            'apiKeysData', 'fetchedAt', 'cw', 'operations', 'totalRequests',
            'totalCost', 'tax', 'grandCost', 'catCount', 'catCost',
            'activeRate', 'idrRate', 'taxRate', 'budgetAlert', 'companyByKey', 'startDate', 'endDate'
        ));
    }
}
