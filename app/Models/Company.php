<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use \App\Models\Concerns\HasUuidRouteKey;

    protected $fillable = ['name', 'slug', 'logo_path', 'is_active', 'aws_account_id', 'aws_api_key', 'aws_api_key_name', 'aws_key_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'aws_key_active' => 'boolean',
        'aws_api_key' => 'encrypted',
    ];

    /** Akun AWS tempat API key company ini berada (menentukan kredensial CloudWatch). */
    public function awsAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class, 'aws_account_id');
    }

    /** Semua API key yang menempel ke perusahaan ini (laporan pemakaian). */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(CompanyApiKey::class)->orderByDesc('is_primary')->orderBy('key_name');
    }

    /** Link laporan pemakaian milik perusahaan ini — boleh lebih dari satu. */
    public function usageShares(): HasMany
    {
        return $this->hasMany(ApiKeyUsageShare::class)->latest('id');
    }

    /**
     * Nama key utama disimpan juga di kolom lama karena halaman peta klien masih
     * membacanya. Dipanggil tiap kali daftar key berubah.
     */
    public function syncPrimaryKeyName(): void
    {
        $primary = $this->apiKeys()->where('is_primary', true)->first()
            ?? $this->apiKeys()->first();

        // Hanya nama key-nya yang disalin. Akun perusahaan sengaja tidak ikut
        // berubah: key boleh berasal dari akun lain, sementara kolom ini dipakai
        // halaman peta klien dan diatur sendiri lewat formulir.
        $this->forceFill(['aws_api_key_name' => $primary?->key_name])->save();
    }

    public function features(): HasMany
    {
        return $this->hasMany(CompanyFeature::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    public function getActiveApiKey(): ?string
    {
        if ($this->aws_key_active && $this->aws_api_key) {
            return $this->aws_api_key;
        }
        return null;
    }

    public function isFeatureEnabled(string $key): bool
    {
        $feature = $this->features->firstWhere('feature_key', $key);
        return $feature ? (bool) $feature->is_enabled : false;
    }

    public function getFeaturesMap(): array
    {
        $keys = ['search', 'route', 'reverse_geocode', 'route_matrix'];
        $map = [];
        foreach ($keys as $key) {
            $feature = $this->features->firstWhere('feature_key', $key);
            $map[$key] = $feature ? (bool) $feature->is_enabled : false;
            $map[$key . '_settings'] = $feature?->settings ?? [];
        }
        return $map;
    }
}
