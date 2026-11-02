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

    /** Akun AWS klien yang seluruh key-nya milik perusahaan ini. */
    public function ownedAwsAccounts(): HasMany
    {
        return $this->hasMany(AwsAccount::class, 'owner_company_id');
    }

    /** Undangan menghubungkan akun AWS klien untuk perusahaan ini. */
    public function awsInvites(): HasMany
    {
        return $this->hasMany(AwsConnectInvite::class)->latest('id');
    }

    /**
     * Pemakaian menurut tagihan AWS (Cost Explorer per usage type) dari akun milik
     * perusahaan ini, beserta resource dan provider datanya. Null kalau perusahaan
     * tidak punya akun AWS sendiri — key di akun Grab sudah terwakili CloudWatch.
     *
     * @return array{summary: array, resources: array, providers: array<int, string>, synced_at: ?\Carbon\Carbon, error: ?string, denied: array<int, string>}|null
     */
    public function billingSummary(string $startDate, string $endDate): ?array
    {
        $accounts = $this->ownedAwsAccounts()->get();
        if ($accounts->isEmpty()) {
            return null;
        }

        $summary = AwsUsageTypeDaily::summaryFor($accounts->pluck('id')->all(), $startDate, $endDate);

        $resources = [];
        $denied = [];
        foreach ($accounts as $account) {
            foreach (['place_indexes', 'maps', 'route_calculators', 'geofence_collections'] as $type) {
                foreach ($account->resources[$type] ?? [] as $item) {
                    $resources[] = ['type' => $type] + $item;
                }
            }
            $denied = array_merge($denied, $account->resources['denied'] ?? []);
        }

        // Provider dari dua sumber: nama usage type di tagihan dan data source resource.
        $providers = collect(array_keys($summary['by_provider']))
            ->merge(collect($resources)->pluck('data_source')->filter())
            ->unique()->values()->all();

        return [
            'summary'   => $summary,
            'resources' => $resources,
            'providers' => $providers,
            'synced_at' => $accounts->max('usage_types_synced_at') ?? $summary['synced_at'],
            'error'     => $accounts->pluck('usage_types_error')->filter()->first(),
            'denied'    => array_values(array_unique($denied)),
        ];
    }

    /**
     * Tarik ulang pemakaian CloudWatch semua key perusahaan ke api_key_usage_daily.
     * Key baru di akun milik perusahaan ini dicatat dulu supaya ikut tertarik.
     *
     * @return array{done: int, failed: int}
     */
    public function refreshUsage(string $startDate, string $endDate): array
    {
        // Akun milik klien: key baru dicatat, lalu tagihan per usage type dan daftar
        // resource-nya ikut diperbarui — pemakaian tanpa API key hanya terlihat di sana.
        foreach ($this->ownedAwsAccounts()->active()->get() as $account) {
            $account->syncOwnerKeys();
            $service = \App\Services\AwsLocationService::forAccount($account);
            $service->syncUsageTypes($startDate, $endDate);
            $service->discoverResources();
        }

        $done = 0;
        $failed = 0;

        foreach ($this->apiKeys()->with('awsAccount')->get() as $key) {
            if (!\App\Services\AwsLocationService::hasCredentials($key->awsAccount)) {
                $failed++;
                continue;
            }

            // getCachedUsage(force) menembak CloudWatch dan sekaligus menuliskan
            // pemakaian hariannya ke tabel api_key_usage_daily.
            $snapshot = \App\Services\AwsLocationService::forAccount($key->awsAccount)
                ->getCachedUsage($key->key_name, $startDate, $endDate, null, true);

            empty($snapshot['metrics']['error']) ? $done++ : $failed++;
        }

        return ['done' => $done, 'failed' => $failed];
    }

    /**
     * Nama key utama disimpan juga di kolom lama karena halaman peta klien masih
     * membacanya. Dipanggil tiap kali daftar key berubah.
     */
    public function syncPrimaryKeyName(): void
    {
        $primary = $this->apiKeys()->where('is_primary', true)->first()
            ?? $this->apiKeys()->first();

        $this->aws_api_key_name = $primary?->key_name;

        // Akun perusahaan ikut akun key utamanya — tidak ada lagi pilihan akun
        // di formulir. Saat key terakhir dilepas akunnya sengaja dibiarkan:
        // tarif service charge yang sudah berjalan tidak boleh ikut berubah
        // hanya karena daftar key sedang kosong.
        if ($primary) {
            $this->aws_account_id = $primary->aws_account_id;
        }

        $this->save();
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
