<?php

namespace App\Models;

use App\Services\AwsLocationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pemakaian harian per API key per operasi. Sumber angka untuk laporan publik:
 * rentang apa pun bisa dijumlahkan dari sini tanpa memanggil CloudWatch, jadi
 * laporan tidak lagi bergantung pada rentang mana yang kebetulan pernah dibuka.
 */
class ApiKeyUsageDaily extends Model
{
    protected $table = 'api_key_usage_daily';

    protected $fillable = ['aws_account_id', 'key_name', 'usage_date', 'operation', 'request_count', 'synced_at'];

    protected $casts = [
        'usage_date' => 'date',
        'synced_at'  => 'datetime',
    ];

    /**
     * Simpan hasil tarikan CloudWatch. Baris hari yang sama ditimpa, hari yang
     * tidak ada di hasil dibiarkan — penarikan rentang pendek tidak menghapus
     * riwayat di luar rentangnya.
     *
     * @param  array<string, array<string, int>>  $matrix  [tanggal => [operasi => jumlah]]
     */
    public static function store(?int $accountId, string $keyName, array $matrix): void
    {
        $now = now();
        $rows = [];

        foreach ($matrix as $date => $ops) {
            foreach ($ops as $operation => $count) {
                $rows[] = [
                    'aws_account_id' => $accountId,
                    'key_name'       => $keyName,
                    'usage_date'     => $date,
                    'operation'      => $operation,
                    'request_count'  => (int) $count,
                    'synced_at'      => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        if (!$rows) {
            return;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table((new static())->getTable())->upsert(
                $chunk,
                ['aws_account_id', 'key_name', 'usage_date', 'operation'],
                ['request_count', 'synced_at', 'updated_at'],
            );
        }
    }

    /** Kapan key ini terakhir ditarik; null kalau belum pernah. */
    public static function lastSync(?int $accountId, string $keyName): ?\Carbon\Carbon
    {
        $value = static::query()
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->max('synced_at');

        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    /**
     * Jumlahkan pemakaian beberapa key pada satu rentang — bentuk hasilnya sama
     * dengan AwsLocationService::aggregateForKeys() supaya view-nya tidak berubah.
     *
     * @param  iterable<CompanyApiKey>  $keys
     */
    public static function aggregateForKeys(iterable $keys, string $startDate, string $endDate): array
    {
        $total = 0;
        $operations = [];
        $daily = [];
        $perKey = [];
        $missing = 0;
        $fetchedAt = null;

        foreach ($keys as $key) {
            $rows = static::query()
                ->where('aws_account_id', $key->aws_account_id)
                ->where('key_name', $key->key_name)
                ->whereBetween('usage_date', [$startDate, $endDate])
                ->get(['usage_date', 'operation', 'request_count']);

            $sync = static::lastSync($key->aws_account_id, $key->key_name);

            // Belum pernah ditarik sama sekali = "belum ada data"; sudah pernah
            // ditarik tapi rentangnya sepi = memang nol pemakaian.
            if (!$sync) {
                $missing++;
                $perKey[] = [
                    'name' => $key->key_name, 'label' => $key->label,
                    'account' => $key->awsAccount?->name, 'total' => 0, 'cost' => 0.0, 'has_data' => false,
                ];
                continue;
            }

            if ($fetchedAt === null || $sync->lt($fetchedAt)) {
                $fetchedAt = $sync;
            }

            $keyOps = [];
            $keyTotal = 0;

            foreach ($rows as $row) {
                $date = $row->usage_date->format('Y-m-d');
                $count = (int) $row->request_count;

                $keyOps[$row->operation] = ($keyOps[$row->operation] ?? 0) + $count;
                $operations[$row->operation] = ($operations[$row->operation] ?? 0) + $count;
                $daily[$date] = ($daily[$date] ?? 0) + $count;
                $keyTotal += $count;
                $total += $count;
            }

            $perKey[] = [
                'name' => $key->key_name, 'label' => $key->label,
                'account' => $key->awsAccount?->name,
                'total' => $keyTotal,
                'cost' => AwsLocationService::estimateCost($keyOps),
                'has_data' => true,
            ];
        }

        arsort($operations);
        ksort($daily);
        usort($perKey, fn ($a, $b) => $b['cost'] <=> $a['cost']);

        return [
            'total' => $total, 'operations' => $operations, 'daily' => $daily,
            'per_key' => $perKey, 'missing' => $missing,
            'fetched_at' => $fetchedAt?->toIso8601String(),
        ];
    }

    /** Bentuk metrics untuk satu key (dipakai laporan per-key). */
    public static function metricsFor(?int $accountId, string $keyName, string $startDate, string $endDate): array
    {
        $rows = static::query()
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->get(['usage_date', 'operation', 'request_count']);

        $operations = [];
        $daily = [];
        $total = 0;

        foreach ($rows as $row) {
            $count = (int) $row->request_count;
            $operations[$row->operation] = ($operations[$row->operation] ?? 0) + $count;
            $daily[$row->usage_date->format('Y-m-d')] = ($daily[$row->usage_date->format('Y-m-d')] ?? 0) + $count;
            $total += $count;
        }

        arsort($operations);
        ksort($daily);

        return ['total' => $total, 'operations' => $operations, 'daily' => $daily, 'error' => null];
    }
}
