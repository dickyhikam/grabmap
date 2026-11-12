<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu kejadian di pelacak pengajuan tagihan (dibuat, dibayar, dibatalkan, ...). */
class AwsBillClaimActivity extends Model
{
    public const UPDATED_AT = null;

    public const CREATED = 'created';
    public const CREATED_PAID = 'created_paid';
    public const PAID = 'paid';
    public const UNPAID = 'unpaid';
    public const CANCELLED = 'cancelled';

    protected $fillable = ['aws_account_id', 'aws_bill_claim_id', 'claim_code', 'action', 'user_id', 'actor', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(AwsBillClaim::class, 'aws_bill_claim_id');
    }

    public static function record(AwsBillClaim $claim, string $action, array $meta = []): self
    {
        $user = auth()->user();

        return static::create([
            'aws_account_id'    => $claim->aws_account_id,
            'aws_bill_claim_id' => $claim->exists ? $claim->id : null,
            'claim_code'        => $claim->code(),
            'action'            => $action,
            'user_id'           => $user?->id,
            'actor'             => $user?->name ?? 'admin',
            'meta'              => $meta ?: null,
        ]);
    }
}
