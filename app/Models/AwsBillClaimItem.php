<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu bulan tagihan di dalam pengajuan. Unik per akun + bulan. */
class AwsBillClaimItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'aws_bill_claim_id', 'aws_account_id', 'month', 'invoice_id', 'usd', 'aws_idr', 'fee_idr', 'vat_idr', 'total_idr',
    ];

    protected $casts = [
        'month'     => 'date',
        'usd'       => 'float',
        'aws_idr'   => 'float',
        'fee_idr'   => 'float',
        'vat_idr'   => 'float',
        'total_idr' => 'float',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(AwsBillClaim::class, 'aws_bill_claim_id');
    }
}
