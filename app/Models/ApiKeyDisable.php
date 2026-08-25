<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Jejak API key yang sengaja dinonaktifkan dari panel. Adanya baris di sini
 * membedakan "dimatikan orang" dari "kedaluwarsa sendiri" — di AWS keduanya
 * terlihat sama: masa berlakunya lewat.
 */
class ApiKeyDisable extends Model
{
    protected $fillable = ['aws_account_id', 'key_name', 'previous_expire_time', 'disabled_by'];

    protected $casts = ['previous_expire_time' => 'datetime'];

    public static function forKey(?int $accountId, string $keyName): ?self
    {
        return static::query()
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->first();
    }

    /** Peta nama key => catatan, untuk halaman daftar. */
    public static function mapFor(?int $accountId): Collection
    {
        return static::query()->where('aws_account_id', $accountId)->get()->keyBy('key_name');
    }
}
