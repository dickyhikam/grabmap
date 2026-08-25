<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu API key yang menempel ke sebuah perusahaan. Pasangan (akun, nama key)
 * unik: satu key tidak boleh diklaim dua perusahaan, supaya biaya di laporan
 * tidak terhitung dobel.
 */
class CompanyApiKey extends Model
{
    protected $fillable = ['company_id', 'aws_account_id', 'key_name', 'label', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function awsAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class);
    }

    /** Kunci pencocokan dengan rincian per key di snapshot CloudWatch. */
    public function snapshotKey(): string
    {
        return $this->aws_account_id . '|' . $this->key_name;
    }

    /** Perusahaan yang mengklaim key ini, kalau ada. */
    public static function ownerOf(?int $accountId, string $keyName): ?Company
    {
        return static::query()
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->first()?->company;
    }
}
