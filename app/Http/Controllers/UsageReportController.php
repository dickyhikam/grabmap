<?php

namespace App\Http\Controllers;

use App\Models\ApiKeyUsageShare;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Services\AwsLocationService;
use Illuminate\Http\Request;

class UsageReportController extends Controller
{
    private const MAX_RANGE_DAYS = 92;

    public function show(Request $request, string $token)
    {
        $share = ApiKeyUsageShare::findActiveByToken($token);

        if (!$share) {
            abort(404);
        }

        $share->touchAccess();

        [$startDate, $endDate, $days] = $this->resolveDateRange($request);

        $account = $share->awsAccount;
        $keyName = $share->key_name;

        if (!AwsLocationService::hasCredentials($account)) {
            abort(404);
        }

        $service = AwsLocationService::forAccount($account);

        // Client read-only: tidak boleh memicu refresh ke AWS.
        $snapshot = $service->getCachedUsage($keyName, $startDate, $endDate, null, false);
        $metrics = $snapshot['metrics'];
        $fetchedAt = !empty($snapshot['fetched_at']) ? \Carbon\Carbon::parse($snapshot['fetched_at']) : null;

        $assignedCompany = Company::query()
            ->where('aws_api_key_name', $keyName)
            ->when($account, fn ($q) => $q->where('aws_account_id', $account->id))
            ->first();

        $activeRate = ExchangeRate::current();
        $idrRate = $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500);
        $taxRate = (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11));

        $response = response()->view('usage-report.show', compact(
            'share', 'metrics', 'fetchedAt', 'assignedCompany',
            'startDate', 'endDate', 'days', 'idrRate', 'taxRate', 'activeRate'
        ));

        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    private function resolveDateRange(Request $request): array
    {
        $startDate = $request->query('start', now()->subDays(29)->format('Y-m-d'));
        $endDate = $request->query('end', now()->format('Y-m-d'));

        try {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        } catch (\Exception) {
            $start = now()->subDays(29)->startOfDay();
            $end = now()->endOfDay();
        }

        if ($end->gt(now())) {
            $end = now()->endOfDay();
        }

        if ($start->gt($end)) {
            $start = $end->copy()->subDays(29)->startOfDay();
        }

        if ($start->lt(now()->subYear())) {
            $start = now()->subYear()->startOfDay();
        }

        $days = $start->diffInDays($end) + 1;
        if ($days > self::MAX_RANGE_DAYS) {
            $start = $end->copy()->subDays(self::MAX_RANGE_DAYS - 1)->startOfDay();
            $days = self::MAX_RANGE_DAYS;
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d'), $days];
    }
}
