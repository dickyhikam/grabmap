<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Satu pengajuan penggantian tagihan AWS: beberapa bulan sekaligus, dengan
 * pilihan menambah management fee dan VAT. Angka disimpan saat diajukan
 * (snapshot), jadi tidak berubah walau tarif atau kurs berubah kemudian.
 */
class AwsBillClaim extends Model
{
    public const SUBMITTED = 'submitted';
    public const PAID = 'paid';

    protected $fillable = [
        'aws_account_id', 'number', 'status', 'include_fee', 'fee_percent', 'include_vat', 'vat_rate',
        'aws_idr', 'fee_idr', 'vat_idr', 'total_idr', 'total_usd', 'submitted_on', 'paid_on',
        'proof_path', 'proof_name', 'note', 'created_by',
    ];

    protected $casts = [
        'include_fee'  => 'boolean',
        'include_vat'  => 'boolean',
        'fee_percent'  => 'float',
        'vat_rate'     => 'float',
        'aws_idr'      => 'float',
        'fee_idr'      => 'float',
        'vat_idr'      => 'float',
        'total_idr'    => 'float',
        'total_usd'    => 'float',
        'submitted_on' => 'date',
        'paid_on'      => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $claim) {
            $claim->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class, 'aws_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AwsBillClaimItem::class)->orderBy('month');
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    /** Nomor tampilan, mis. "PGJ-007". */
    public function code(): string
    {
        return 'PGJ-' . str_pad((string) $this->number, 3, '0', STR_PAD_LEFT);
    }

    public static function nextNumber(int $accountId): int
    {
        return (int) static::where('aws_account_id', $accountId)->max('number') + 1;
    }
}
