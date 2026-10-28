<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Route CalculateRouteMatrix yang ditagih AWS per akun per hari.
 *
 * CloudWatch hanya tahu jumlah request, padahal tagihan Matrix dihitung per
 * route (origin × destination). Cost Explorer tahu jumlah route, tapi hanya per
 * akun. Jadi rasio route/request akun dipakai untuk mengubah request Matrix
 * tiap key menjadi route yang ditagih.
 */
class MatrixRouteDaily extends Model
{
    public const OPERATION = 'CalculateRouteMatrix';

    protected $table = 'matrix_route_daily';

    protected $fillable = ['aws_account_id', 'usage_date', 'routes', 'requests', 'synced_at'];

    protected $casts = [
        'usage_date' => 'date',
        'routes'     => 'float',
        'synced_at'  => 'datetime',
    ];

    /**
     * @param  array<string, array{routes: float, requests: int}>  $days  [tanggal => angka]
     */
    public static function store(?int $accountId, array $days): void
    {
        $now = now();
        $rows = [];

        foreach ($days as $date => $v) {
            $rows[] = [
                'aws_account_id' => $accountId,
                'usage_date'     => $date,
                'routes'         => $v['routes'],
                'requests'       => $v['requests'],
                'synced_at'      => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (!$rows) {
            return;
        }

        DB::table((new static())->getTable())->upsert(
            $rows,
            ['aws_account_id', 'usage_date'],
            ['routes', 'requests', 'synced_at', 'updated_at'],
        );
    }

    /**
     * Rata-rata route per request Matrix pada rentang ini; null kalau belum ada
     * data route sama sekali. Hari dengan route 0 dilewati: itu hari yang belum
     * masuk Cost Explorer (tertinggal ±24 jam), bukan matriks kosong.
     */
    public static function routesPerRequest(?int $accountId, string $startDate, string $endDate): ?float
    {
        $sum = static::query()
            ->where('aws_account_id', $accountId)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->where('routes', '>', 0)
            ->selectRaw('SUM(routes) AS routes, SUM(requests) AS requests')
            ->first();

        if (!$sum || (float) $sum->routes <= 0 || (int) $sum->requests <= 0) {
            return null;
        }

        return (float) $sum->routes / (int) $sum->requests;
    }

    /**
     * Ganti jumlah request Matrix di [operasi => jumlah] dengan route yang
     * ditagih, supaya semua hitungan biaya (jumlah × harga) ikut benar. Tanpa
     * data route, angkanya dibiarkan (1 request = 1 route, perilaku lama).
     *
     * @param  array<string, int>  $operations
     * @return array<string, int>
     */
    public static function billable(array $operations, ?int $accountId, string $startDate, string $endDate): array
    {
        $requests = $operations[self::OPERATION] ?? 0;
        if ($requests <= 0) {
            return $operations;
        }

        $ratio = static::routesPerRequest($accountId, $startDate, $endDate);
        if ($ratio !== null) {
            $operations[self::OPERATION] = (int) round($requests * $ratio);
        }

        return $operations;
    }
}
