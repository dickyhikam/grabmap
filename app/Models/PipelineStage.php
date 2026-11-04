<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu kolom di board pipeline perusahaan. */
class PipelineStage extends Model
{
    use \App\Models\Concerns\HasUuidRouteKey;

    /** Warna yang boleh dipilih — tiap nama punya pasangan token terang/gelap di view board. */
    public const COLORS = ['slate', 'indigo', 'sky', 'teal', 'amber', 'green', 'red'];

    /**
     * Kategori kolom, urut dari kiri ke kanan board, beserta warnanya. Tetap di kode:
     * artinya harus sama bagi semua orang supaya hitungan per kategori bisa dipercaya.
     */
    public const CATEGORIES = [
        'presales'   => 'indigo',
        'onboarding' => 'sky',
        'live'       => 'green',
        'closed'     => 'red',
    ];

    protected $fillable = ['name', 'description', 'color', 'category', 'position'];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class)->orderBy('pipeline_position')->orderBy('id');
    }

    /** Semua kolom sesuai urutan board: kategori dulu, lalu posisi di dalam kategorinya. */
    public static function ordered(): Collection
    {
        $rank = array_flip(array_keys(self::CATEGORIES));

        return static::query()->get()
            ->sortBy([
                fn ($a, $b) => ($rank[$a->category] ?? 0) <=> ($rank[$b->category] ?? 0),
                ['position', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    /** Kolom paling kiri — tempat perusahaan baru mendarat kalau tahapnya tidak disebut. */
    public static function firstStage(): ?self
    {
        return static::ordered()->first();
    }
}
