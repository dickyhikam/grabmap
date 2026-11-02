<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Aws\Credentials\AssumeRoleCredentialProvider;
use Aws\Credentials\CredentialProvider;
use Aws\Sts\StsClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu akun AWS (kredensial IAM) yang dipakai untuk mengelola API key Location Service
 * dan menarik metrik CloudWatch. Aplikasi bisa memegang lebih dari satu akun sekaligus —
 * CloudWatch tidak bisa lintas akun, jadi tiap akun ditembak dengan kredensialnya sendiri.
 *
 * Dua cara masuk: access key IAM, atau role milik klien (`role_arn`) yang di-assume
 * memakai IAM user akun sumber (`source_account_id`). Cara kedua tidak menyimpan
 * secret klien sama sekali.
 */
class AwsAccount extends Model
{
    use \App\Models\Concerns\HasUuidRouteKey;

    protected $fillable = [
        'name', 'account_number', 'access_key_id', 'secret_access_key',
        'role_arn', 'external_id', 'source_account_id', 'owner_company_id',
        'resources', 'resources_checked_at', 'usage_types_synced_at', 'usage_types_error',
        'region', 'is_active', 'is_default', 'notes', 'last_verified_at',
    ];

    protected $casts = [
        'secret_access_key' => 'encrypted',
        'is_active'         => 'boolean',
        'is_default'        => 'boolean',
        'last_verified_at'  => 'datetime',
        'resources'         => 'array',
        'resources_checked_at'  => 'datetime',
        'usage_types_synced_at' => 'datetime',
    ];

    /**
     * Provider kredensial AssumeRole per role, dibuat sekali per proses. Memoize
     * menyimpan kredensial sementaranya dan baru meminta lagi ke STS saat kedaluwarsa,
     * jadi banyak instance service dalam satu proses tidak memanggil STS berulang.
     */
    private static array $roleProviders = [];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Akun yang dipakai kalau pemanggil tidak menyebut akun tertentu. */
    public static function defaultAccount(): ?self
    {
        return static::query()->active()->orderByDesc('is_default')->orderBy('id')->first();
    }

    /**
     * Cari akun lewat UUID (dipakai di URL) atau id (sesi & data lama), jatuh ke
     * akun default kalau kosong/tidak ketemu.
     */
    public static function resolve(int|string|null $key): ?self
    {
        if (!$key) {
            return static::defaultAccount();
        }

        $account = static::query()->where('uuid', $key)->first()
            ?: (is_numeric($key) ? static::find($key) : null);

        return $account ?: static::defaultAccount();
    }

    /** Akun (milik Grab) yang IAM user-nya dipakai untuk meng-assume role akun ini. */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_account_id');
    }

    /** Akun role klien yang bergantung pada akun ini sebagai sumber. */
    public function roleAccounts(): HasMany
    {
        return $this->hasMany(self::class, 'source_account_id');
    }

    /** Akun yang bisa jadi sumber AssumeRole: aktif, memakai access key, kredensial lengkap. */
    public static function assumeRoleSources(): \Illuminate\Support\Collection
    {
        return static::query()->active()->whereNull('role_arn')->orderBy('name')->get()
            ->filter(fn (self $a) => $a->hasCredentials())
            ->values();
    }

    /** Perusahaan pemilik seluruh akun ini (akun klien yang terhubung lewat undangan). */
    public function ownerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'owner_company_id');
    }

    /**
     * Catat setiap key di akun ini sebagai key perusahaan pemiliknya, termasuk
     * key yang dibuat klien sesudah terhubung. Key yang sudah diklaim perusahaan
     * lain dibiarkan — satu key hanya boleh punya satu pemilik.
     *
     * @param  array<int, string>|null  $keyNames  daftar yang baru saja dibaca dari AWS; null = baca sekarang
     * @return int Jumlah key yang baru ditambahkan.
     */
    public function syncOwnerKeys(?array $keyNames = null): int
    {
        $company = $this->ownerCompany;
        if (!$company || !$this->hasCredentials()) {
            return 0;
        }

        if ($keyNames === null) {
            $result = \App\Services\AwsLocationService::forAccount($this)->listApiKeys();
            if ($result['error']) {
                return 0;
            }
            $keyNames = array_column($result['keys'], 'key_name');
        }

        $claimed = CompanyApiKey::query()->where('aws_account_id', $this->id)->pluck('key_name')->all();
        $added = 0;

        foreach ($keyNames as $name) {
            $name = (string) $name;
            if ($name === '' || in_array($name, $claimed, true)) {
                continue;
            }

            $company->apiKeys()->create([
                'aws_account_id' => $this->id,
                'key_name'       => $name,
                'is_primary'     => !$company->apiKeys()->exists(),
            ]);
            $added++;
        }

        if ($added) {
            $company->syncPrimaryKeyName();
        }

        return $added;
    }

    public function usesRole(): bool
    {
        return !empty($this->role_arn);
    }

    public function hasCredentials(): bool
    {
        if ($this->usesRole()) {
            // Sumbernya harus akun access key — role tidak di-assume berantai.
            $source = $this->sourceAccount;

            return $source !== null && !$source->usesRole() && $source->hasCredentials();
        }

        return !empty($this->access_key_id) && !empty($this->secret_access_key);
    }

    /**
     * Kredensial dalam bentuk yang dimengerti AWS SDK: array key/secret, atau
     * provider AssumeRole untuk akun role.
     */
    public function credentials(): array|callable
    {
        if ($this->usesRole()) {
            return $this->roleCredentials();
        }

        return [
            'key'    => (string) $this->access_key_id,
            'secret' => (string) $this->secret_access_key,
        ];
    }

    private function roleCredentials(): callable
    {
        $memo = implode('|', [$this->source_account_id, $this->role_arn, $this->external_id, $this->region]);

        return self::$roleProviders[$memo] ??= CredentialProvider::memoize(new AssumeRoleCredentialProvider([
            'client' => new StsClient([
                'region'      => $this->region ?: 'ap-southeast-1',
                'version'     => 'latest',
                'credentials' => $this->sourceAccount->credentials(),
            ]),
            'assume_role_params' => [
                'RoleArn'         => $this->role_arn,
                'RoleSessionName' => 'grabmaps-dashboard',
                'ExternalId'      => $this->external_id,
                'DurationSeconds' => 3600,
            ],
        ]));
    }

    /** 12 digit Account ID dari Role ARN (arn:aws:iam::123456789012:role/…). */
    public static function accountIdFromRoleArn(?string $arn): ?string
    {
        return preg_match('/^arn:aws:iam::(\d{12}):role\//', (string) $arn, $m) ? $m[1] : null;
    }

    /** Access key ID yang aman ditampilkan di layar (AKIA…XIF4). */
    public function maskedAccessKey(): string
    {
        $key = (string) $this->access_key_id;

        if (strlen($key) <= 8) {
            return $key ? str_repeat('•', strlen($key)) : '—';
        }

        return substr($key, 0, 4) . str_repeat('•', 6) . substr($key, -4);
    }

    /**
     * Jadikan akun ini satu-satunya default. Pelepasan default lama dan penetapan
     * default baru dijalankan dalam satu transaksi supaya tabel tidak pernah berada
     * dalam keadaan tanpa default (atau dengan dua default) kalau prosesnya terputus.
     */
    public function makeDefault(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
            $this->forceFill(['is_default' => true])->save();
        });
    }
}
