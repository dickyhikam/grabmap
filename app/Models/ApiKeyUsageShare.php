<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyUsageShare extends Model
{
    protected $fillable = [
        'company_id',
        'label',
        'aws_account_id',
        'key_name',
        'share_token',
        'share_enabled',
        'share_created_by',
        'share_last_accessed_at',
        'share_expires_at',
    ];

    protected $casts = [
        'share_enabled' => 'boolean',
        'share_last_accessed_at' => 'datetime',
        'share_expires_at' => 'datetime',
    ];

    public function awsAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Jejak akses — link ini terbuka tanpa login, jadi riwayatnya yang jadi kendali. */
    public function visits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UsageShareVisit::class, 'usage_share_id')->latest('last_seen_at');
    }

    /**
     * Link tingkat perusahaan menggabungkan seluruh key milik perusahaan itu;
     * link biasa hanya satu key. Tepat satu di antara keduanya yang terisi.
     */
    public function isCompanyShare(): bool
    {
        return $this->company_id !== null;
    }

    /**
     * Key yang dicakup link ini. Tanpa pilihan eksplisit = seluruh key perusahaan,
     * jadi key baru ikut sendiri tanpa perlu mengubah link-nya.
     */
    public function keys(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(CompanyApiKey::class, 'usage_share_keys', 'usage_share_id', 'company_api_key_id');
    }

    /** @return \Illuminate\Support\Collection<int, CompanyApiKey> */
    public function resolvedKeys(): \Illuminate\Support\Collection
    {
        $picked = $this->keys()->with('awsAccount')->get();

        if ($picked->isNotEmpty()) {
            return $picked;
        }

        return $this->company?->apiKeys()->with('awsAccount')->get() ?? collect();
    }

    /** Apakah link ini mengikuti seluruh key perusahaan secara otomatis. */
    public function coversAllKeys(): bool
    {
        return $this->keys()->count() === 0;
    }

    /** Judul yang pantas ditampilkan di halaman publik. */
    public function subjectLabel(): string
    {
        if (!$this->isCompanyShare()) {
            return (string) $this->key_name;
        }

        return $this->company?->name ?? '—';
    }

    public static function forKey(?int $accountId, string $keyName): ?self
    {
        return static::query()
            ->whereNull('company_id')
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->first();
    }

    public static function forCompany(int $companyId): ?self
    {
        return static::query()->where('company_id', $companyId)->first();
    }

    public static function findActiveByToken(string $token): ?self
    {
        $share = static::query()
            ->where('share_token', $token)
            ->where('share_enabled', true)
            ->first();

        if (!$share || !$share->isActive()) {
            return null;
        }

        return $share;
    }

    public function isActive(): bool
    {
        if (!$this->share_enabled) {
            return false;
        }

        return !$this->share_expires_at || $this->share_expires_at->isFuture();
    }

    public function publicUrl(array $query = []): string
    {
        return route('usage-report.show', array_merge(['token' => $this->share_token], $query));
    }

    /** Aktifkan (atau buat) link share; token baru hanya dibuat saat belum ada. */
    public static function enable(?int $accountId, string $keyName, ?string $by = null, ?\DateTimeInterface $expiresAt = null): self
    {
        $share = static::query()->firstOrNew([
            'company_id' => null,
            'aws_account_id' => $accountId,
            'key_name' => $keyName,
        ]);

        return static::activate($share, $by, $expiresAt);
    }

    /**
     * Link gabungan untuk satu perusahaan. Tiap pemanggilan membuat link BARU —
     * satu perusahaan boleh punya beberapa link dengan cakupan key berbeda.
     *
     * @param  array<int, int>  $keyIds  kosong = seluruh key perusahaan
     */
    public static function enableForCompany(
        int $companyId,
        ?string $by = null,
        ?\DateTimeInterface $expiresAt = null,
        ?string $label = null,
        array $keyIds = [],
    ): self {
        $share = static::activate(new static(['company_id' => $companyId, 'label' => $label]), $by, $expiresAt);
        $share->keys()->sync($keyIds);

        return $share;
    }

    private static function activate(self $share, ?string $by, ?\DateTimeInterface $expiresAt): self
    {
        if (!$share->share_token) {
            $share->share_token = static::generateToken();
        }

        $share->share_enabled = true;
        $share->share_created_by = $by ?? $share->share_created_by;
        $share->share_expires_at = $expiresAt;
        $share->save();

        return $share;
    }

    public function disable(): void
    {
        $this->update(['share_enabled' => false]);
    }

    public function regenerateToken(?string $by = null): void
    {
        $this->update([
            'share_token' => static::generateToken(),
            'share_enabled' => true,
            'share_created_by' => $by ?? $this->share_created_by,
        ]);
    }

    /** Catat akses terakhir — dibatasi supaya tidak menulis DB tiap request. */
    public function touchAccess(): void
    {
        if ($this->share_last_accessed_at?->gt(now()->subMinutes(5))) {
            return;
        }

        $this->forceFill(['share_last_accessed_at' => now()])->save();
    }

    public static function generateToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::query()->where('share_token', $token)->exists());

        return $token;
    }
}
