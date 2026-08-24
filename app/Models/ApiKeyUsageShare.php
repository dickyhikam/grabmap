<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyUsageShare extends Model
{
    protected $fillable = [
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

    public static function forKey(?int $accountId, string $keyName): ?self
    {
        return static::query()
            ->where('aws_account_id', $accountId)
            ->where('key_name', $keyName)
            ->first();
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
            'aws_account_id' => $accountId,
            'key_name' => $keyName,
        ]);

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
