<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rate', 'rate_date', 'source', 'reference', 'note', 'is_active', 'created_by',
    ];

    protected $casts = [
        'rate'      => 'decimal:2',
        'rate_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Kurs yang sedang dipakai: yang ditandai aktif, kalau tidak ada ambil yang terbaru.
     */
    public static function current(): ?self
    {
        return static::where('is_active', true)->latest('rate_date')->first()
            ?? static::latest('rate_date')->latest('id')->first();
    }

    /**
     * Jadikan kurs ini satu-satunya yang aktif.
     */
    public function makeActive(): void
    {
        static::query()->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
