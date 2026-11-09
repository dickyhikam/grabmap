<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Tanggal potong tagihan per akun per bulan. cuts berisi tanggal terakhir tiap
 * periode kecuali yang terakhir: [10, 20] → 1–10, 11–20, 21–akhir bulan.
 */
class AwsBillingSplit extends Model
{
    public const MAX_CUTS = 5;

    protected $fillable = ['aws_account_id', 'month', 'cuts', 'created_by'];

    protected $casts = [
        'month' => 'date',
        'cuts'  => 'array',
    ];

    /** @return array<int, int> tanggal potong yang sah untuk bulan itu, urut naik */
    public static function cutsFor(int $accountId, Carbon $monthStart): array
    {
        $row = static::query()
            ->where('aws_account_id', $accountId)
            ->whereDate('month', $monthStart->format('Y-m-d'))
            ->first();

        return static::clean($row?->cuts ?? [], $monthStart->daysInMonth);
    }

    /** @return array<int, int> */
    public static function clean(array $cuts, int $daysInMonth): array
    {
        $cuts = array_values(array_unique(array_filter(
            array_map('intval', $cuts),
            fn ($d) => $d >= 1 && $d < $daysInMonth,
        )));
        sort($cuts);

        return array_slice($cuts, 0, self::MAX_CUTS);
    }
}
