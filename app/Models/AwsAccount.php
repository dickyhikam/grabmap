<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu akun AWS (kredensial IAM) yang dipakai untuk mengelola API key Location Service
 * dan menarik metrik CloudWatch. Aplikasi bisa memegang lebih dari satu akun sekaligus —
 * CloudWatch tidak bisa lintas akun, jadi tiap akun ditembak dengan kredensialnya sendiri.
 */
class AwsAccount extends Model
{
    protected $fillable = [
        'name', 'account_number', 'access_key_id', 'secret_access_key',
        'region', 'is_active', 'is_default', 'notes', 'last_verified_at',
    ];

    protected $casts = [
        'secret_access_key' => 'encrypted',
        'is_active'         => 'boolean',
        'is_default'        => 'boolean',
        'last_verified_at'  => 'datetime',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Akun yang dipakai kalau pemanggil tidak menyebut akun tertentu. */
    public static function defaultAccount(): ?self
    {
        return static::query()->active()->orderByDesc('is_default')->orderBy('id')->first();
    }

    /** Cari akun by id, jatuh ke akun default kalau id kosong/tidak ketemu. */
    public static function resolve(int|string|null $id): ?self
    {
        $account = $id ? static::find($id) : null;

        return $account ?: static::defaultAccount();
    }

    public function hasCredentials(): bool
    {
        return !empty($this->access_key_id) && !empty($this->secret_access_key);
    }

    /** Bentuk array kredensial yang dimengerti AWS SDK. */
    public function credentials(): array
    {
        return [
            'key'    => (string) $this->access_key_id,
            'secret' => (string) $this->secret_access_key,
        ];
    }

    /** Access key ID yang aman ditampilkan di layar (AKIA…XIF4). */
    public function maskedAccessKey(): string
    {
        $key = (string) $this->access_key_id;

        if (strlen($key) <= 8) {
            return $key ? str_repeat('•', strlen($key)) : '—';
        }

        return substr($key, 0, 4) . str_repeat('•', 6) . substr($key, -4);
    }

    /**
     * Jadikan akun ini satu-satunya default. Pelepasan default lama dan penetapan
     * default baru dijalankan dalam satu transaksi supaya tabel tidak pernah berada
     * dalam keadaan tanpa default (atau dengan dua default) kalau prosesnya terputus.
     */
    public function makeDefault(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();
        });
    }
}
