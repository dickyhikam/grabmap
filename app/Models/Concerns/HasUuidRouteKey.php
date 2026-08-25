<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Model yang muncul di URL admin memakai UUID sebagai kunci rute, bukan id
 * auto-increment — supaya jumlah dan urutan barisnya tidak terbaca dari alamat.
 */
trait HasUuidRouteKey
{
    public static function bootHasUuidRouteKey(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Baris lama (dibuat sebelum kolom uuid ada, atau lewat query builder yang
     * melewati event model) bisa saja belum punya uuid. Kalau dibiarkan, URL-nya
     * gagal dibuat dan halaman daftar ikut mati — jadi diisi saat pertama dipakai.
     */
    public function getRouteKey()
    {
        if (blank($this->uuid)) {
            $this->forceFill(['uuid' => (string) Str::uuid()])->save();
        }

        return $this->uuid;
    }
}
