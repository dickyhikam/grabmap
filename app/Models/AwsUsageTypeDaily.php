<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Pemakaian Amazon Location per usage type per hari, dari Cost Explorer.
 *
 * Nama usage type memuat provider datanya: APS1-Usage-Grab-Geocode berarti
 * resource v1 (Place Index) berdata Grab, sedangkan API v2 tercatat sebagai
 * APS1-Usage-Default-Places-Core. Inilah satu-satunya sumber yang menangkap
 * pemakaian tanpa API key — CloudWatch per key tidak melihatnya.
 */
class AwsUsageTypeDaily extends Model
{
    protected $table = 'aws_usage_type_daily';

    public $timestamps = false;

    protected $fillable = ['aws_account_id', 'usage_date', 'usage_type', 'unit', 'quantity', 'cost_usd', 'synced_at'];

    protected $casts = [
        'usage_date' => 'date',
        'quantity'   => 'float',
        'cost_usd'   => 'float',
        'synced_at'  => 'datetime',
    ];

    /** Provider di nama usage type → label yang dibaca manusia. */
    public const PROVIDERS = [
        'Grab'    => 'GrabMaps',
        'Esri'    => 'Esri',
        'Here'    => 'HERE',
        'Default' => 'Non-GrabMaps',
    ];

    /**
     * Region tempat API v2 memakai data GrabMaps (dokumentasi AWS "GrabMaps for
     * Southeast Asia"). Di sana tagihan v2 tetap bertuliskan "Default", jadi
     * providernya harus disimpulkan dari region-nya.
     */
    public const GRAB_V2_REGIONS = ['APS1', 'APS5'];

    /**
     * Pecah usage type jadi wilayah, provider, generasi API, dan produk.
     *
     *  - "APS1-Usage-Grab-Geocode"        → Grab, v1 (resource Place Index berdata Grab)
     *  - "APS1-Usage-Default-Places-Core" → Grab, v2 (API v2 di Singapura = GrabMaps)
     *  - "USE1-Usage-Default-Maps-Tiles"  → Default, v2 (provider bawaan region lain)
     *  - "APS1-GeofenceMonth"             → tanpa provider
     *
     * @return array{region: ?string, provider: ?string, api: ?string, product: string}
     */
    public static function parse(string $usageType): array
    {
        $region = null;
        $rest = $usageType;

        if (preg_match('/^([A-Z]{2,4}\d)-(.+)$/', $usageType, $m)) {
            [$region, $rest] = [$m[1], $m[2]];
        }

        if (preg_match('/^Usage-([A-Za-z]+)-(.+)$/', $rest, $m)) {
            [$provider, $product] = [$m[1], $m[2]];

            // Provider bernama (Grab/Esri/Here) hanya muncul pada resource v1;
            // API v2 selalu tercatat "Default".
            if ($provider !== 'Default') {
                return ['region' => $region, 'provider' => $provider, 'api' => 'v1', 'product' => $product];
            }

            return [
                'region'   => $region,
                'provider' => in_array($region, self::GRAB_V2_REGIONS, true) ? 'Grab' : 'Default',
                'api'      => 'v2',
                'product'  => $product,
            ];
        }

        return ['region' => $region, 'provider' => null, 'api' => null, 'product' => preg_replace('/^Usage-/', '', $rest)];
    }

    /**
     * @param  array<int, array{date: string, usage_type: string, unit: ?string, quantity: float, cost: float}>  $rows
     */
    public static function store(int $accountId, string $startDate, string $endDate, array $rows): void
    {
        $now = now();

        DB::transaction(function () use ($accountId, $startDate, $endDate, $rows, $now) {
            // Rentang yang ditarik ditulis ulang utuh: angka Cost Explorer untuk hari
            // yang belum tutup masih bisa berubah.
            static::query()
                ->where('aws_account_id', $accountId)
                ->whereBetween('usage_date', [$startDate, $endDate])
                ->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                static::query()->insert(array_map(fn ($r) => [
                    'aws_account_id' => $accountId,
                    'usage_date'     => $r['date'],
                    'usage_type'     => $r['usage_type'],
                    'unit'           => $r['unit'],
                    'quantity'       => $r['quantity'],
                    'cost_usd'       => $r['cost'],
                    'synced_at'      => $now,
                ], $chunk));
            }
        });
    }

    /**
     * Ringkasan per usage type untuk satu atau beberapa akun pada rentang tanggal.
     *
     * @param  array<int, int>  $accountIds
     * @return array{rows: array<int, array>, daily: array<string, float>, total_cost: float, by_provider: array<string, float>, synced_at: ?\Carbon\Carbon}
     */
    public static function summaryFor(array $accountIds, string $startDate, string $endDate): array
    {
        $rows = static::query()
            ->whereIn('aws_account_id', $accountIds)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('usage_type, MAX(unit) as unit, SUM(quantity) as quantity, SUM(cost_usd) as cost, MAX(synced_at) as synced_at')
            ->groupBy('usage_type')
            ->get();

        $byProvider = [];
        $out = [];

        foreach ($rows as $row) {
            $parsed = static::parse($row->usage_type);
            $cost = (float) $row->cost;

            if ($parsed['provider']) {
                $byProvider[$parsed['provider']] = ($byProvider[$parsed['provider']] ?? 0) + (float) $row->quantity;
            }

            $out[] = $parsed + [
                'usage_type' => $row->usage_type,
                'unit'       => $row->unit,
                'quantity'   => (float) $row->quantity,
                'cost'       => $cost,
            ];
        }

        // Yang paling banyak ditagih di atas.
        usort($out, fn ($a, $b) => [$b['cost'], $b['quantity']] <=> [$a['cost'], $a['quantity']]);

        $synced = $rows->max('synced_at');

        // Biaya per hari untuk grafik kecil di laporan.
        $daily = static::query()
            ->whereIn('aws_account_id', $accountIds)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('usage_date, SUM(cost_usd) as cost')
            ->groupBy('usage_date')
            ->get()
            ->mapWithKeys(fn ($r) => [\Carbon\Carbon::parse($r->usage_date)->format('Y-m-d') => (float) $r->cost])
            ->all();

        return [
            'rows'        => $out,
            'daily'       => $daily,
            'total_cost'  => array_sum(array_column($out, 'cost')),
            'by_provider' => $byProvider,
            'synced_at'   => $synced ? \Carbon\Carbon::parse($synced) : null,
        ];
    }
}
