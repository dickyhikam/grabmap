<?php

namespace App\Models;

use App\Notifications\CustomVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /**
     * URL admin memakai UUID, bukan id auto-increment — jumlah & urutan user
     * tidak ikut terbaca dari alamat halaman.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->uuid ??= (string) \Illuminate\Support\Str::uuid();
            // Registrasi mandiri tidak menyebut role — beri role default.
            $user->role_id ??= Role::defaultRole()?->id;
        });
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmail);
    }

    /**
     * Send the branded password reset notification (custom email template).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /** Slug role bawaan — dipakai untuk pencarian, bukan sebagai nilai kolom. */
    const ROLE_ADMIN = 'admin';
    const ROLE_USER  = 'user';

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Nama role untuk ditampilkan; aman meski relasinya kosong. */
    public function roleName(): string
    {
        return $this->role?->name ?? __('users.role_none');
    }

    public function hasPermission(string $key): bool
    {
        return (bool) $this->role?->hasPermission($key);
    }

    /**
     * Halaman pertama yang boleh dibuka user ini. Dipakai setelah login supaya
     * role tanpa izin dashboard tidak mendarat di 403.
     */
    public function homeRoute(): string
    {
        $candidates = [
            'dashboard.view'       => 'admin.dashboard',
            'companies.view'       => 'admin.companies.index',
            'api_keys.view'        => 'admin.api-keys.index',
            'aws_accounts.view'    => 'admin.aws-accounts.index',
            'cost_settings.view'   => 'admin.cost-settings.index',
            'simulator.use'        => 'admin.simulator',
            'users.view'           => 'admin.users.index',
            'roles.view'           => 'admin.roles.index',
        ];

        foreach ($candidates as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return route($route);
            }
        }

        // Role tanpa izin sama sekali: biarkan mendarat di dashboard dan lihat 403,
        // itu memang keadaan yang perlu diperbaiki oleh admin.
        return route('admin.dashboard');
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    public function isAdmin(): bool
    {
        return $this->role?->slug === self::ROLE_ADMIN || (bool) $this->role?->isFullAccess();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
