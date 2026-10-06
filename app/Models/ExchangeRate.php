<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use \App\Models\Concerns\HasUuidRouteKey;

    public $timestamps = false;

    protected $fillable = [
        'rate', 'rate_date', 'source', 'reference', 'note', 'is_active', 'created_by',
    ];

    protected $casts = [
        'rate'      => 'decimal:4',
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
     * Kurs yang berlaku untuk satu periode laporan. Laporan klien dipakai untuk
     * mencocokkan tagihan AWS, dan AWS memakai kursnya sendiri tiap bulan, jadi
     * laporan Agustus harus memakai kurs Agustus — bukan kurs yang kebetulan
     * sedang aktif hari ini. Urutan: kurs di bulan yang sama dengan akhir
     * periode, lalu kurs terakhir sebelum periode berakhir, lalu kurs aktif.
     */
    public static function forPeriod(?string $endDate): ?self
    {
        if (!$endDate) {
            return static::current();
        }

        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        return static::whereYear('rate_date', $end->year)
                ->whereMonth('rate_date', $end->month)
                ->latest('rate_date')->latest('id')->first()
            ?? static::whereDate('rate_date', '<=', $end)
                ->latest('rate_date')->latest('id')->first()
            ?? static::current();
    }

    /**
     * Jadikan kurs ini satu-satunya yang aktif. Pelepasan kurs lama dan penetapan
     * kurs baru dijalankan dalam satu transaksi supaya tabel tidak pernah kehilangan
     * kurs aktif kalau prosesnya terputus di tengah.
     */
    public function makeActive(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            static::query()->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }
}
