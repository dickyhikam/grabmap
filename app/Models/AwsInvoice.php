<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Angka di PDF invoice AWS untuk satu akun dan satu bulan, diketik admin.
 * Kurs bulan itu = total IDR ÷ total USD, sama persis dengan yang dipakai AWS.
 */
class AwsInvoice extends Model
{
    protected $fillable = [
        'aws_account_id', 'month', 'invoice_number', 'total_usd', 'total_idr',
        'exchange_rate_id', 'note', 'created_by',
    ];

    protected $casts = [
        'month'     => 'date',
        'total_usd' => 'float',
        'total_idr' => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class, 'aws_account_id');
    }

    public function exchangeRate(): BelongsTo
    {
        return $this->belongsTo(ExchangeRate::class);
    }

    /** Kurs tersirat invoice ini (IDR per USD). */
    public function rate(): ?float
    {
        return $this->total_usd > 0 ? $this->total_idr / $this->total_usd : null;
    }
}
