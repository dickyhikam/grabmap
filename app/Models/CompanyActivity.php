<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris riwayat perusahaan di board pipeline. */
class CompanyActivity extends Model
{
    protected $fillable = ['company_id', 'user_id', 'type', 'body', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(Company $company, string $type, ?string $body = null, array $meta = []): self
    {
        return static::create([
            'company_id' => $company->id,
            'user_id'    => auth()->id(),
            'type'       => $type,
            'body'       => $body,
            'meta'       => $meta ?: null,
        ]);
    }
}
