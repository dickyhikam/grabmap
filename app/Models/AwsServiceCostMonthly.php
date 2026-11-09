<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Biaya per service AWS per bulan dari Cost Explorer — bentuknya sama dengan
 * ringkasan di PDF invoice AWS, termasuk baris "Tax" (PPN 11% dari PT Amazon
 * Web Services Indonesia).
 */
class AwsServiceCostMonthly extends Model
{
    /** Nama service yang dipakai Cost Explorer untuk pajak. */
    public const TAX = 'Tax';

    public const LOCATION = 'Amazon Location Service';

    protected $table = 'aws_service_cost_monthly';

    public $timestamps = false;

    protected $fillable = ['aws_account_id', 'month', 'service', 'cost_usd', 'synced_at'];

    protected $casts = [
        'month'     => 'date',
        'cost_usd'  => 'float',
        'synced_at' => 'datetime',
    ];

    /**
     * Tulis ulang bulan-bulan yang ditarik. Angka bulan berjalan masih berubah,
     * jadi barisnya diganti utuh, bukan ditambah.
     *
     * @param  array<string, array<string, float>>  $months  ['2026-10-01' => [service => biaya]]
     */
    public static function store(int $accountId, array $months): void
    {
        $now = now();

        DB::transaction(function () use ($accountId, $months, $now) {
            foreach ($months as $month => $services) {
                static::query()->where('aws_account_id', $accountId)->whereDate('month', $month)->delete();

                $rows = [];
                foreach ($services as $service => $cost) {
                    $rows[] = [
                        'aws_account_id' => $accountId,
                        'month'          => $month,
                        'service'        => $service,
                        'cost_usd'       => $cost,
                        'synced_at'      => $now,
                    ];
                }

                if ($rows) {
                    static::query()->insert($rows);
                }
            }
        });
    }

    /**
     * Ringkasan satu bulan: biaya per service (pajak dipisah), subtotal sebelum
     * pajak, pajak, dan total yang mestinya sama dengan "Total in USD" di invoice.
     *
     * @return array{services: array<string, float>, subtotal: float, tax: float, total: float, synced_at: ?\Carbon\Carbon}
     */
    public static function summary(int $accountId, string $month): array
    {
        $rows = static::query()
            ->where('aws_account_id', $accountId)
            ->whereDate('month', $month)
            ->get();

        $services = [];
        $tax = 0.0;

        foreach ($rows as $row) {
            if ($row->service === self::TAX) {
                $tax += $row->cost_usd;
                continue;
            }
            $services[$row->service] = ($services[$row->service] ?? 0) + $row->cost_usd;
        }

        arsort($services);
        $subtotal = array_sum($services);

        return [
            'services'  => $services,
            'subtotal'  => $subtotal,
            'tax'       => $tax,
            'total'     => $subtotal + $tax,
            'synced_at' => $rows->max('synced_at'),
        ];
    }
}
