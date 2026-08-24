<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Batas biaya (USD) untuk satu API key. Dibandingkan dengan biaya hasil estimasi
 * CloudWatch pada rentang yang sedang ditampilkan — peringatan di aplikasi, bukan
 * AWS Budgets (AWS tidak memecah biaya per API key Location Service).
 */
class ApiKeyBudget extends Model
{
    protected $fillable = ['aws_account_id', 'key_name', 'limit_usd', 'updated_by'];

    protected $casts = [
        'limit_usd' => 'decimal:2',
    ];

    public function awsAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class);
    }

    /** Batas untuk satu key pada satu akun; null kalau belum diatur. */
    public static function forKey(?int $accountId, string $keyName): ?self
    {
        return static::query()
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->first();
    }

    /**
     * Simpan atau hapus batas. Nilai kosong/nol dianggap "tidak ada batas" sehingga
     * barisnya dihapus — tabel ini hanya menyimpan key yang benar-benar dibatasi.
     */
    public static function set(?int $accountId, string $keyName, $limit, ?string $by = null): ?self
    {
        $limit = $limit === null || $limit === '' ? null : (float) $limit;

        if (!$limit || $limit <= 0) {
            static::query()->where('aws_account_id', $accountId)->where('key_name', $keyName)->delete();

            return null;
        }

        return static::updateOrCreate(
            ['aws_account_id' => $accountId, 'key_name' => $keyName],
            ['limit_usd' => $limit, 'updated_by' => $by],
        );
    }

    /** Peta "accountId|keyName" => batas, untuk dipakai sekali jalan di halaman daftar. */
    public static function map(): Collection
    {
        return static::all()->keyBy(fn (self $b) => $b->aws_account_id . '|' . $b->key_name);
    }

    /**
     * Bagian batas yang sudah terpakai (0..n). Lebih dari 1 berarti terlampaui.
     */
    public function usedRatio(float $costUsd): float
    {
        $limit = (float) $this->limit_usd;

        return $limit > 0 ? $costUsd / $limit : 0.0;
    }

    /** Mulai diperingatkan saat pemakaian menyentuh bagian ini dari batasnya. */
    public const NEAR_RATIO = 0.8;

    /**
     * Bandingkan semua batas dengan rincian pemakaian per key dari snapshot
     * (['akunId|namaKey' => ['Operasi' => jumlah, ...]]).
     *
     * @return \Illuminate\Support\Collection<int, array{
     *     budget: self, key_name: string, account: ?string, cost: float, limit: float,
     *     ratio: float, state: string
     * }>
     */
    public static function evaluate(array $byKeyOps): Collection
    {
        if (!$byKeyOps) {
            return collect();
        }

        return static::with('awsAccount')->get()
            ->map(function (self $budget) use ($byKeyOps) {
                $ops = $byKeyOps[$budget->aws_account_id . '|' . $budget->key_name] ?? null;
                if ($ops === null) {
                    return null;                     // key ini tidak ada di rentang yang dilihat
                }

                $cost  = \App\Services\AwsLocationService::estimateCost($ops);
                $ratio = $budget->usedRatio($cost);

                return [
                    'budget'   => $budget,
                    'key_name' => $budget->key_name,
                    'account'  => $budget->awsAccount?->name,
                    'cost'     => $cost,
                    'limit'    => (float) $budget->limit_usd,
                    'ratio'    => $ratio,
                    'state'    => $ratio >= 1 ? 'over' : ($ratio >= self::NEAR_RATIO ? 'near' : 'ok'),
                ];
            })
            ->filter()
            ->sortByDesc('ratio')
            ->values();
    }
}
