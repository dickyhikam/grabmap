<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * Satu jejak akses ke link laporan klien. Dipakai sebagai kendali keamanan:
 * link ini terbuka tanpa login, jadi yang bisa dilakukan adalah melihat siapa
 * yang membukanya lalu mematikan link-nya kalau janggal.
 */
class UsageShareVisit extends Model
{
    /** Kunjungan berulang dari pembaca yang sama dalam jendela ini digabung. */
    private const WINDOW_MINUTES = 30;

    /** Riwayat yang lebih tua dari ini dibuang. */
    private const KEEP_MONTHS = 12;

    protected $fillable = [
        'usage_share_id', 'ip_address', 'user_agent',
        'viewed_range', 'viewed_key', 'hits', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(ApiKeyUsageShare::class, 'usage_share_id');
    }

    /** Catat satu kunjungan; refresh berturut-turut hanya menambah penghitung. */
    public static function record(ApiKeyUsageShare $share, Request $request, string $range, ?string $key = null): void
    {
        $ip = $request->ip();
        $agent = \Illuminate\Support\Str::limit((string) $request->userAgent(), 500, '');

        $recent = static::query()
            ->where('usage_share_id', $share->id)
            ->where('ip_address', $ip)
            ->where('user_agent', $agent)
            ->where('last_seen_at', '>=', now()->subMinutes(self::WINDOW_MINUTES))
            ->latest('last_seen_at')
            ->first();

        if ($recent) {
            $recent->forceFill([
                'hits'         => $recent->hits + 1,
                'viewed_range' => $range,
                'viewed_key'   => $key,
                'last_seen_at' => now(),
            ])->save();

            return;
        }

        // Baris lama dibuang saat ada pembaca baru — riwayat tidak perlu abadi,
        // dan pemangkasannya jarang jalan karena kunjungan berulang digabung.
        static::query()
            ->where('usage_share_id', $share->id)
            ->where('last_seen_at', '<', now()->subMonths(self::KEEP_MONTHS))
            ->delete();

        static::create([
            'usage_share_id' => $share->id,
            'ip_address'     => $ip,
            'user_agent'     => $agent,
            'viewed_range'   => $range,
            'viewed_key'     => $key,
            'hits'           => 1,
            'first_seen_at'  => now(),
            'last_seen_at'   => now(),
        ]);
    }

    /** Ringkasan perangkat dari user agent — cukup untuk mengenali pembacanya. */
    public function device(): string
    {
        $ua = (string) $this->user_agent;

        $browser = match (true) {
            str_contains($ua, 'Edg/')                        => 'Edge',
            str_contains($ua, 'OPR/')                        => 'Opera',
            str_contains($ua, 'Chrome/') && !str_contains($ua, 'Chromium') => 'Chrome',
            str_contains($ua, 'Firefox/')                    => 'Firefox',
            str_contains($ua, 'Safari/')                     => 'Safari',
            default                                          => null,
        };

        $os = match (true) {
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android')   => 'Android',
            str_contains($ua, 'Mac OS X')  => 'macOS',
            str_contains($ua, 'Windows')   => 'Windows',
            str_contains($ua, 'Linux')     => 'Linux',
            default                        => null,
        };

        return trim(implode(' · ', array_filter([$browser, $os]))) ?: '—';
    }
}
