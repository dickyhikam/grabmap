<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiUsageLog;
use App\Models\Company;
use App\Services\AwsLocationService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // Companies stats
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $companiesWithKey = Company::whereNotNull('aws_api_key_name')->count();

        // API Keys stats (cached 30 min)
        $apiKeysData = ['total' => 0, 'active' => 0];
        if (AwsLocationService::hasCredentials()) {
            $apiKeysData = Cache::remember('dashboard_api_keys', 30 * 60, function () {
                $service = new AwsLocationService();
                $result = $service->listApiKeys();
                $keys = $result['keys'];
                $active = collect($keys)->filter(fn($k) => !($k['expire_time'] && \Carbon\Carbon::parse($k['expire_time'])->isPast()))->count();
                return ['total' => count($keys), 'active' => $active];
            });
        }

        // Local usage stats (this month)
        $month = now()->startOfMonth();
        $localRequestsThisMonth = ApiUsageLog::where('created_at', '>=', $month)->count();
        $localRequestsTotal = ApiUsageLog::count();

        // Local usage by endpoint (this month)
        $localByEndpoint = ApiUsageLog::where('created_at', '>=', $month)
            ->selectRaw('endpoint_type, COUNT(*) as count')
            ->groupBy('endpoint_type')
            ->pluck('count', 'endpoint_type');

        $localEstimatedCost = 0;
        foreach ($localByEndpoint as $type => $count) {
            $localEstimatedCost += ApiUsageLog::estimateCost($type, $count);
        }

        // Local daily usage (last 30 days)
        $localDaily = ApiUsageLog::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Top companies by usage (this month)
        $topCompanies = ApiUsageLog::where('created_at', '>=', $month)
            ->whereNotNull('company_id')
            ->selectRaw('company_id, COUNT(*) as count')
            ->groupBy('company_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item->company = Company::find($item->company_id);
                return $item;
            })
            ->filter(fn($item) => $item->company !== null);

        // CloudWatch aggregate usage (cached 30 min)
        $cloudwatchData = null;
        if (AwsLocationService::hasCredentials()) {
            $cloudwatchData = Cache::remember('dashboard_cloudwatch', 30 * 60, function () {
                $service = new AwsLocationService();
                // Get usage for all keys combined
                $result = $service->listApiKeys();
                $totalRequests = 0;
                $byCategory = ['maps' => 0, 'places' => 0, 'routes' => 0];

                $mapOps = ['GetMapTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites'];
                $placeOps = ['SearchPlaceIndexForSuggestions', 'SearchPlaceIndexForText', 'SearchPlaceIndexForPosition', 'GetPlace'];
                $routeOps = ['CalculateRoute', 'CalculateRouteMatrix'];

                foreach ($result['keys'] ?? [] as $key) {
                    $metrics = $service->getKeyUsageMetrics($key['key_name'], now()->startOfMonth()->format('Y-m-d'), now()->format('Y-m-d'));
                    $totalRequests += $metrics['total'];
                    foreach ($metrics['operations'] ?? [] as $op => $count) {
                        if (in_array($op, $mapOps)) $byCategory['maps'] += $count;
                        elseif (in_array($op, $placeOps)) $byCategory['places'] += $count;
                        elseif (in_array($op, $routeOps)) $byCategory['routes'] += $count;
                    }
                }

                // Estimate cost
                $pricing = ['maps' => 0.04, 'places' => 4.00, 'routes' => 5.00];
                $totalCost = 0;
                foreach ($byCategory as $cat => $count) {
                    $totalCost += ($count / 1000) * $pricing[$cat];
                }

                return [
                    'total_requests' => $totalRequests,
                    'by_category' => $byCategory,
                    'total_cost' => $totalCost,
                ];
            });
        }

        return view('admin.dashboard', compact(
            'totalCompanies', 'activeCompanies', 'companiesWithKey',
            'apiKeysData', 'localRequestsThisMonth', 'localRequestsTotal',
            'localByEndpoint', 'localEstimatedCost', 'localDaily',
            'topCompanies', 'cloudwatchData'
        ));
    }
}
