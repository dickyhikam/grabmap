<?php

namespace App\Http\Controllers;

use App\Models\ApiKeyUsageDaily;
use App\Models\ApiKeyUsageShare;
use App\Models\UsageShareVisit;
use App\Models\Company;
use App\Models\ExchangeRate;
use App\Models\Setting;
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

        // Halaman ini dibaca klien, bukan pengguna panel, jadi pilihan bahasanya
        // ditentukan sendiri lewat ?lang= dan diingat di sesi pengunjung.
        $this->applyLocale($request);

        [$startDate, $endDate, $days] = $this->resolveDateRange($request);

        // Jejak akses dicatat sesudah rentangnya diketahui, supaya terlihat juga
        // periode mana yang dibuka pembacanya.
        UsageShareVisit::record($share, $request, $startDate . ' → ' . $endDate, $request->query('key'));

        $view = $share->isCompanyShare()
            ? $this->companyReport($request, $share, $startDate, $endDate)
            : $this->keyReport($share, $startDate, $endDate);

        $activeRate = ExchangeRate::current();

        $response = response()->view($view['view'], array_merge($view['data'], [
            'share'      => $share,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'days'       => $days,
            'activeRate' => $activeRate,
            'idrRate'    => $activeRate ? (float) $activeRate->rate : (float) config('aws.usd_to_idr', 16500),
            'taxRate'    => (float) Setting::get('tax_rate', config('aws.tax_rate', 0.11)),
        ]));

        return $response
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }

    /** Bahasa halaman publik: ?lang= → pilihan sebelumnya → bawaan aplikasi. */
    private function applyLocale(Request $request): void
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

    /** Laporan satu API key — perilaku lama, tidak berubah. */
    private function keyReport(ApiKeyUsageShare $share, string $startDate, string $endDate): array
    {
        $account = $share->awsAccount;

        // Angkanya dibaca dari pemakaian harian yang tersimpan — halaman publik
        // tidak pernah menembak CloudWatch, dan rentang apa pun bisa dijawab.
        $metrics = ApiKeyUsageDaily::metricsFor($account?->id, $share->key_name, $startDate, $endDate);
        $syncedAt = ApiKeyUsageDaily::lastSync($account?->id, $share->key_name);

        $assignedCompany = Company::query()
            ->where('aws_api_key_name', $share->key_name)
            ->when($account, fn ($q) => $q->where('aws_account_id', $account->id))
            ->first();

        return [
            'view' => 'usage-report.show',
            'data' => [
                'metrics'         => $metrics,
                'fetchedAt'       => $syncedAt,
                'assignedCompany' => $assignedCompany,
            ],
        ];
    }

    /**
     * Laporan gabungan seluruh key milik satu perusahaan.
     *
     * Sengaja hanya membaca snapshot yang sudah ada: satu kunjungan anonim tidak
     * boleh memicu sederet panggilan CloudWatch. Key yang belum punya snapshot
     * ditandai "belum ada data" — admin bisa menariknya dari panel.
     */
    private function companyReport(Request $request, ApiKeyUsageShare $share, string $startDate, string $endDate): array
    {
        $company = $share->company;

        if (!$company || !$company->is_active) {
            abort(404);
        }

        // Cakupan mengikuti link: beberapa key pilihan, atau seluruh key
        // perusahaan kalau link-nya tidak menyebut key tertentu.
        $keys = $share->resolvedKeys();

        // Tab di topbar boleh mempersempit ke satu key — tapi hanya key yang
        // memang tercakup link ini, supaya tidak jadi celah melihat key lain.
        $picked = $request->query('key');
        $active = $picked ? $keys->firstWhere('key_name', $picked) : null;

        $usage = ApiKeyUsageDaily::aggregateForKeys(
            $active ? collect([$active]) : $keys,
            $startDate,
            $endDate,
        );

        return [
            'view' => 'usage-report.show',
            'data' => [
                'assignedCompany' => $company,
                'keyTabs'   => $keys->count() > 1 ? $keys : collect(),
                'activeKey' => $active?->key_name,
                'usage'     => $usage,
                'metrics'   => [
                    'total'      => $usage['total'],
                    'operations' => $usage['operations'],
                    'daily'      => $usage['daily'],
                    'error'      => null,
                ],
                'fetchedAt' => !empty($usage['fetched_at']) ? \Carbon\Carbon::parse($usage['fetched_at']) : null,
            ],
        ];
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

        $days = (int) floor($start->diffInDays($end)) + 1;
        if ($days > self::MAX_RANGE_DAYS) {
            $start = $end->copy()->subDays(self::MAX_RANGE_DAYS - 1)->startOfDay();
            $days = self::MAX_RANGE_DAYS;
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d'), $days];
    }
}
