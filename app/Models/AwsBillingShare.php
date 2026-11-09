<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Link publik (tanpa login, hanya-baca) ke halaman Tagihan satu akun AWS.
 */
class AwsBillingShare extends Model
{
    protected $fillable = ['aws_account_id', 'token', 'is_active', 'view_count', 'last_viewed_at', 'created_by'];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_viewed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class, 'aws_account_id');
    }

    public static function newToken(): string
    {
        return Str::random(48);
    }

    public function url(): string
    {
        return route('aws-billing.public', $this->token);
    }

    /** Catat kunjungan tanpa menyentuh updated_at (itu penanda perubahan link). */
    public function recordView(): void
    {
        static::query()->whereKey($this->id)->update([
            'view_count'     => \Illuminate\Support\Facades\DB::raw('view_count + 1'),
            'last_viewed_at' => now(),
        ]);
    }
}
