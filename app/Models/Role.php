<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Role yang bisa dikelola dari panel admin. Daftar izin yang tersedia ada di
 * config/permissions.php — di sini hanya disimpan izin mana yang dicentang.
 */
class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'color', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    /** Role dengan izin ini boleh apa saja — dipakai role admin bawaan. */
    public const WILDCARD = '*';

    protected static function booted(): void
    {
        static::creating(function (self $role) {
            $role->uuid ??= (string) Str::uuid();
            $role->slug ??= Str::slug($role->name);
        });
    }

    /** URL admin memakai UUID, bukan id auto-increment. */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $key): bool
    {
        $granted = $this->permissions ?? [];

        return in_array(self::WILDCARD, $granted, true) || in_array($key, $granted, true);
    }

    public function isFullAccess(): bool
    {
        return in_array(self::WILDCARD, $this->permissions ?? [], true);
    }

    /** Role default untuk user baru yang tidak menyebut role. */
    public static function defaultRole(): ?self
    {
        return static::query()->where('slug', 'user')->first()
            ?? static::query()->orderBy('id')->first();
    }

    /** Semua kunci izin yang dikenal aplikasi, apa adanya dari config. */
    public static function catalog(): array
    {
        return config('permissions', []);
    }

    public static function allPermissionKeys(): array
    {
        return collect(static::catalog())->flatten()->all();
    }
}
