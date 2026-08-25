<?php

namespace App\Services;

use App\Models\AwsAccount;
use Aws\CloudWatch\CloudWatchClient;
use Aws\LocationService\LocationServiceClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AwsLocationService
{
    private LocationServiceClient $client;

    /** Akun AWS yang dipakai instance ini. Null = jatuh ke kredensial .env (mode lama). */
    private ?AwsAccount $account;

    private string $region;

    private array $credentials;

    public function __construct(?AwsAccount $account = null)
    {
        $this->account = $account ?? AwsAccount::defaultAccount();

        $this->region = $this->account?->region ?: (string) config('aws.region', 'ap-southeast-1');
        $this->credentials = $this->account
            ? $this->account->credentials()
            : (array) config('aws.credentials');

        $this->client = new LocationServiceClient($this->clientConfig());
    }

    public static function forAccount(?AwsAccount $account): self
    {
        return new self($account);
    }

    public function account(): ?AwsAccount
    {
        return $this->account;
    }

    public function region(): string
    {
        return $this->region;
    }

    /**
     * Konfigurasi client. Kalau kredensial kosong, jangan kirim key 'credentials' sama sekali —
     * AWS SDK akan melempar exception untuk credentials null, sedangkan default provider chain
     * (env / IAM role instance) masih mungkin berhasil.
     */
    private function clientConfig(): array
    {
        $config = [
            'region'  => $this->region,
            'version' => config('aws.version', 'latest'),
        ];

        if (!empty($this->credentials['key']) && !empty($this->credentials['secret'])) {
            $config['credentials'] = $this->credentials;
        }

        return $config;
    }

    /**
     * Namespace cache per akun — dua akun bisa punya API key dengan nama sama,
     * tanpa ini snapshot usage-nya akan saling menimpa.
     */
    public function cacheScope(): string
    {
        return $this->account ? 'acct' . $this->account->id : 'env';
    }

    /** Cache key ber-namespace akun, untuk dipakai controller (mis. cache detail key). */
    public function cacheKey(string $suffix): string
    {
        return $this->cacheScope() . ':' . $suffix;
    }

    /**
     * Apakah kredensial tersedia? Tanpa argumen: cek akun default (atau .env kalau belum ada akun).
     */
    public static function hasCredentials(?AwsAccount $account = null): bool
    {
        $account = $account ?: AwsAccount::defaultAccount();

        if ($account) {
            return $account->hasCredentials();
        }

        return !empty(config('aws.credentials.key')) && !empty(config('aws.credentials.secret'));
    }

    /**
     * List all API keys from AWS Location Service.
     *
     * @return array{keys: array, error: string|null}
     */
    public function listApiKeys(): array
    {
        try {
            $result = $this->client->listKeys();
            $keys = [];

            foreach ($result['Entries'] ?? [] as $entry) {
                $keys[] = [
                    'key_name'    => $entry['KeyName'] ?? '',
                    'description' => $entry['Description'] ?? '',
                    'create_time' => isset($entry['CreateTime']) ? $entry['CreateTime']->format('Y-m-d H:i:s') : null,
                    'expire_time' => isset($entry['ExpireTime']) ? $entry['ExpireTime']->format('Y-m-d H:i:s') : null,
                    'restrictions' => $entry['Restrictions'] ?? [],
                ];
            }

            return ['keys' => $keys, 'error' => null];
        } catch (AwsException $e) {
            Log::error('AWS ListKeys error: ' . $e->getAwsErrorMessage());
            return ['keys' => [], 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('AWS ListKeys exception: ' . $e->getMessage());
            return ['keys' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get details of a specific API key, including the key value.
     *
     * @return array{key: array|null, error: string|null}
     */
    public function describeKey(string $keyName): array
    {
        try {
            $result = $this->client->describeKey([
                'KeyName' => $keyName,
            ]);

            return [
                'key' => [
                    'key_name'    => $result['KeyName'] ?? $keyName,
                    'key_arn'     => $result['KeyArn'] ?? '',
                    'key'         => $result['Key'] ?? '',
                    'description' => $result['Description'] ?? '',
                    'create_time' => isset($result['CreateTime']) ? $result['CreateTime']->format('Y-m-d H:i:s') : null,
                    'expire_time' => isset($result['ExpireTime']) ? $result['ExpireTime']->format('Y-m-d H:i:s') : null,
                    'restrictions' => $result['Restrictions'] ?? [],
                ],
                'error' => null,
            ];
        } catch (AwsException $e) {
            Log::error("AWS DescribeKey error for '{$keyName}': " . $e->getAwsErrorMessage());
            return ['key' => null, 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
        } catch (\Exception $e) {
            Log::error("AWS DescribeKey exception for '{$keyName}': " . $e->getMessage());
            return ['key' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new API key in AWS Location Service.
     *
     * @param  array  $params  Required: key_name, restrictions (with AllowActions[], AllowResources[]).
     *                         Optional: description, no_expiry (bool), expire_time (ISO 8601 string),
     *                         restrictions.AllowReferers[].
     * @return array{key: array|null, error: string|null, created: bool}
     *                         created=true means the AWS key exists; error may still be set if DescribeKey
     *                         failed afterwards (transient consistency, IAM gap, etc.) — caller should NOT
     *                         retry creation in that case.
     */
    public function createKey(array $params): array
    {
        $args = [
            'KeyName' => (string) ($params['key_name'] ?? ''),
            'Restrictions' => $params['restrictions'] ?? [],
        ];

        if (!empty($params['description'])) {
            $args['Description'] = (string) $params['description'];
        }

        if (!empty($params['no_expiry'])) {
            $args['NoExpiry'] = true;
        } elseif (!empty($params['expire_time'])) {
            $args['ExpireTime'] = $params['expire_time'] instanceof \DateTimeInterface
                ? $params['expire_time']->format(\DateTime::ATOM)
                : (string) $params['expire_time'];
        }

        // Phase 1: actual CreateKey call — failures here mean key was NOT created
        try {
            $this->client->createKey($args);
        } catch (AwsException $e) {
            Log::error('AWS CreateKey error: ' . $e->getAwsErrorMessage());
            return ['key' => null, 'error' => $e->getAwsErrorMessage() ?: $e->getMessage(), 'created' => false];
        } catch (\Exception $e) {
            Log::error('AWS CreateKey exception: ' . $e->getMessage());
            return ['key' => null, 'error' => $e->getMessage(), 'created' => false];
        }

        // Phase 2: load details — failure here is non-fatal, key already exists in AWS
        $described = $this->describeKey($args['KeyName']);
        return [
            'key'     => $described['key'],
            'error'   => $described['error'],
            'created' => true,
        ];
    }

    /**
     * Update an existing API key (description, expiry, restrictions).
     *
     * @param  string  $keyName
     * @param  array   $params  Supported keys: description, expire_time (ISO 8601 or DateTimeInterface or null),
     *                          no_expiry (bool), force_update (bool), restrictions (array)
     * @return array{key: array|null, error: string|null}
     */
    public function updateKey(string $keyName, array $params): array
    {
        try {
            $args = ['KeyName' => $keyName];

            if (array_key_exists('description', $params)) {
                $args['Description'] = (string) $params['description'];
            }

            // Expiry handling: explicit datetime, never-expiry, or remove if not provided
            if (!empty($params['no_expiry'])) {
                $args['NoExpiry'] = true;
            } elseif (!empty($params['expire_time'])) {
                $args['ExpireTime'] = $params['expire_time'] instanceof \DateTimeInterface
                    ? $params['expire_time']->format(\DateTime::ATOM)
                    : (string) $params['expire_time'];
            }

            if (!empty($params['force_update'])) {
                $args['ForceUpdate'] = true;
            }

            if (!empty($params['restrictions']) && is_array($params['restrictions'])) {
                $args['Restrictions'] = $params['restrictions'];
            }

            $this->client->updateKey($args);

            // Return refreshed key info
            return $this->describeKey($keyName);
        } catch (AwsException $e) {
            Log::error("AWS UpdateKey error for '{$keyName}': " . $e->getAwsErrorMessage());
            return ['key' => null, 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
        } catch (\Exception $e) {
            Log::error("AWS UpdateKey exception for '{$keyName}': " . $e->getMessage());
            return ['key' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get usage metrics for a specific API key from CloudWatch.
     *
     * @return array{total: int, daily: array, error: string|null}
     */
    // Nama operasi GeoPlaces/GeoRoutes terbaru yang dipublikasikan AWS ke CloudWatch.
    // (Nama lama SearchPlaceIndexFor*/CalculateRoute sudah tidak menghasilkan metric apa pun.)
    private const OPERATIONS = [
        'GetMapTile', 'GetTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites',
        'SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace',
        'CalculateRoutes', 'CalculateRouteMatrix',
    ];

    // Harga AWS Location Service (USD per 1.000 request) — ap-southeast-1, satu sumber kebenaran.
    // Glyphs/Sprites/StyleDescriptor tidak ditagih terpisah (bagian dari load peta) => 0.
    public const PRICING = [
        'GetMapTile' => 0.04, 'GetTile' => 0.04, 'GetMapStyleDescriptor' => 0, 'GetMapGlyphs' => 0, 'GetMapSprites' => 0,
        'SearchText' => 0.50, 'ReverseGeocode' => 0.50, 'Suggest' => 0.50, 'GetPlace' => 1.50,
        'CalculateRoutes' => 0.50, 'CalculateRouteMatrix' => 0.50,
    ];

    /** Total estimasi biaya (USD, sebelum pajak) dari [operasi => jumlah request]. */
    public static function estimateCost(array $operations): float
    {
        $total = 0.0;
        foreach ($operations as $op => $count) {
            $total += ($count / 1000) * (self::PRICING[$op] ?? 0);
        }
        return $total;
    }

    /**
     * Ambil metrik usage dengan model "manual refresh":
     * - TIDAK menembak CloudWatch tiap halaman dibuka (hemat & tidak real-time).
     * - Hasil disimpan sebagai snapshot + waktu pengambilan.
     * - Hanya menembak AWS lagi kalau $forceRefresh = true (tombol Refresh) atau belum ada snapshot.
     * Return: ['metrics' => [...], 'fetched_at' => ISO8601 string]
     */
    /** Kunci cache snapshot satu key — dipakai bersama oleh pembaca & penulisnya. */
    public function usageCacheKey(string $keyName, string $startDate, string $endDate, ?string $filterOperation = null): string
    {
        return "aws_usage_snapshot:{$this->cacheScope()}:{$keyName}:{$startDate}:{$endDate}:" . ($filterOperation ?? 'all');
    }

    /**
     * Baca snapshot yang SUDAH ada tanpa pernah menembak AWS; null kalau belum ada.
     * Dipakai halaman publik: pengunjung anonim tidak boleh memicu panggilan
     * CloudWatch, apalagi untuk laporan perusahaan yang isinya banyak key.
     */
    public function peekCachedUsage(string $keyName, string $startDate, string $endDate): ?array
    {
        $snapshot = Cache::get($this->usageCacheKey($keyName, $startDate, $endDate));

        return $snapshot && isset($snapshot['metrics']) ? $snapshot : null;
    }

    public function getCachedUsage(string $keyName, string $startDate, string $endDate, ?string $filterOperation, bool $forceRefresh = false): array
    {
        $cacheKey = $this->usageCacheKey($keyName, $startDate, $endDate, $filterOperation);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $snapshot = Cache::get($cacheKey);
        if (!$snapshot || !isset($snapshot['metrics'])) {
            $metrics = $this->getKeyUsageMetrics($keyName, $startDate, $endDate, $filterOperation);
            $snapshot = ['metrics' => $metrics, 'fetched_at' => now()->toIso8601String()];
            Cache::put($cacheKey, $snapshot, now()->addDays(30)); // hanya berubah saat refresh manual
        }

        return $snapshot;
    }

    /**
     * Agregat usage SEMUA API key (untuk dashboard) — model manual refresh.
     * Snapshot disimpan terpisah; hanya menembak AWS saat $forceRefresh atau belum ada snapshot.
     * Return: ['data' => ['total','operations','by_key','daily','error'], 'fetched_at' => ISO8601]
     */
    public function getCachedAggregateUsage(string $startDate, string $endDate, bool $forceRefresh = false): array
    {
        $cacheKey = "aws_usage_aggregate:{$this->cacheScope()}:{$startDate}:{$endDate}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $snapshot = Cache::get($cacheKey);
        if (!$snapshot || !isset($snapshot['data'])) {
            $snapshot = ['data' => $this->aggregateAllKeys($startDate, $endDate), 'fetched_at' => now()->toIso8601String()];
            Cache::put($cacheKey, $snapshot, now()->addDays(30)); // hanya berubah saat refresh manual
        }

        return $snapshot;
    }

    /**
     * Agregat usage LINTAS AKUN (untuk dashboard). CloudWatch tidak bisa lintas akun,
     * jadi tiap akun aktif ditembak dengan kredensialnya sendiri lalu hasilnya dijumlahkan.
     * Return: ['data' => [...seperti getCachedAggregateUsage + 'by_account'], 'fetched_at' => ISO8601|null]
     */
    public static function aggregateAcrossAccounts(
        string $startDate,
        string $endDate,
        bool $forceRefresh = false,
        ?AwsAccount $only = null
    ): array {
        $accounts = AwsAccount::query()->active()->orderByDesc('is_default')->orderBy('id')->get();
        $noAccountsAtAll = $accounts->isEmpty();

        // Dibatasi ke satu akun (pilihan cakupan di topbar).
        if ($only) {
            $accounts = $accounts->where('id', $only->id)->values();
        }

        // Belum ada akun tersimpan: pakai kredensial .env (perilaku lama).
        if ($noAccountsAtAll) {
            $snapshot = (new self())->getCachedAggregateUsage($startDate, $endDate, $forceRefresh);
            $snapshot['data']['by_account'] = [];

            // Tanpa akun tersimpan, kuncinya berawalan kosong — sepadan dengan
            // aws_account_id null di tabel batas biaya.
            $keyed = [];
            foreach ($snapshot['data']['by_key_ops'] ?? [] as $name => $ops) {
                $keyed['|' . $name] = $ops;
            }
            $snapshot['data']['by_key_ops'] = $keyed;

            return $snapshot;
        }

        $total = 0;
        $operations = [];
        $byKey = [];
        $byKeyOps = [];
        $byAccount = [];
        $daily = [];
        $errors = [];
        $fetchedAt = null;

        foreach ($accounts as $account) {
            if (!$account->hasCredentials()) {
                continue;
            }

            $snapshot = self::forAccount($account)->getCachedAggregateUsage($startDate, $endDate, $forceRefresh);
            $data = $snapshot['data'];

            if (!empty($data['error'])) {
                $errors[] = "{$account->name}: {$data['error']}";
            }

            $total += $data['total'] ?? 0;
            $byAccount[$account->name] = $data['total'] ?? 0;

            foreach ($data['operations'] ?? [] as $op => $count) {
                $operations[$op] = ($operations[$op] ?? 0) + $count;
            }
            foreach ($data['daily'] ?? [] as $date => $count) {
                $daily[$date] = ($daily[$date] ?? 0) + $count;
            }
            // Nama key hanya unik dalam satu akun — beri prefiks nama akun kalau bentrok.
            foreach ($data['by_key'] ?? [] as $name => $count) {
                $label = isset($byKey[$name]) ? "{$name} ({$account->name})" : $name;
                $byKey[$label] = ($byKey[$label] ?? 0) + $count;
            }

            // Rincian per key dikunci "akunId|namaKey" — batas biaya per key dicari
            // dengan pasangan itu, bukan dengan label tampilan yang bisa berubah.
            foreach ($data['by_key_ops'] ?? [] as $name => $ops) {
                $byKeyOps[$account->id . '|' . $name] = $ops;
            }

            // Tampilkan waktu pengambilan tertua supaya tidak terkesan lebih segar dari kenyataan.
            if (!empty($snapshot['fetched_at']) && ($fetchedAt === null || $snapshot['fetched_at'] < $fetchedAt)) {
                $fetchedAt = $snapshot['fetched_at'];
            }
        }

        arsort($operations);
        arsort($byKey);
        arsort($byAccount);
        ksort($daily);

        return [
            'data' => [
                'total'      => $total,
                'operations' => $operations,
                'by_key'     => $byKey,
                'by_key_ops' => $byKeyOps,
                'by_account' => $byAccount,
                'daily'      => $daily,
                'error'      => $errors ? implode(' | ', $errors) : null,
            ],
            'fetched_at' => $fetchedAt,
        ];
    }

    /**
     * Gabungkan pemakaian beberapa API key jadi satu laporan (dipakai link share
     * tingkat perusahaan). HANYA membaca snapshot yang sudah ada — key yang belum
     * pernah diambil dihitung sebagai "belum ada data", bukan nol yang menyesatkan.
     *
     * @param  iterable<\App\Models\CompanyApiKey>  $keys
     * @return array{
     *     total: int, operations: array<string, int>, daily: array<string, int>,
     *     per_key: array<int, array{name: string, label: ?string, account: ?string,
     *                               total: int, cost: float, has_data: bool}>,
     *     missing: int, fetched_at: ?string
     * }
     */
    public static function aggregateForKeys(iterable $keys, string $startDate, string $endDate): array
    {
        $total = 0;
        $operations = [];
        $daily = [];
        $perKey = [];
        $missing = 0;
        $fetchedAt = null;

        foreach ($keys as $key) {
            $account = $key->awsAccount;
            $metrics = null;

            if (self::hasCredentials($account)) {
                $snapshot = self::forAccount($account)->peekCachedUsage($key->key_name, $startDate, $endDate);
                $metrics  = $snapshot['metrics'] ?? null;

                // Waktu pengambilan tertua yang dipakai — laporan tidak boleh
                // terkesan lebih segar daripada bagian datanya yang paling basi.
                if ($metrics && !empty($snapshot['fetched_at'])
                    && ($fetchedAt === null || $snapshot['fetched_at'] < $fetchedAt)) {
                    $fetchedAt = $snapshot['fetched_at'];
                }
            }

            if (!$metrics) {
                $missing++;
                $perKey[] = [
                    'name' => $key->key_name, 'label' => $key->label,
                    'account' => $account?->name, 'total' => 0, 'cost' => 0.0, 'has_data' => false,
                ];
                continue;
            }

            $keyOps = $metrics['operations'] ?? [];
            $total += $metrics['total'] ?? 0;

            foreach ($keyOps as $op => $count) {
                $operations[$op] = ($operations[$op] ?? 0) + $count;
            }
            foreach ($metrics['daily'] ?? [] as $date => $count) {
                $daily[$date] = ($daily[$date] ?? 0) + $count;
            }

            $perKey[] = [
                'name' => $key->key_name, 'label' => $key->label,
                'account' => $account?->name,
                'total' => $metrics['total'] ?? 0,
                'cost' => self::estimateCost($keyOps),
                'has_data' => true,
            ];
        }

        arsort($operations);
        ksort($daily);
        usort($perKey, fn ($a, $b) => $b['cost'] <=> $a['cost']);

        return [
            'total' => $total, 'operations' => $operations, 'daily' => $daily,
            'per_key' => $perKey, 'missing' => $missing, 'fetched_at' => $fetchedAt,
        ];
    }

    /**
     * Uji kredensial akun dengan satu panggilan murah (ListKeys).
     *
     * @return array{ok: bool, keys: int, error: string|null}
     */
    public function testConnection(): array
    {
        $result = $this->listApiKeys();

        return [
            'ok'    => $result['error'] === null,
            'keys'  => count($result['keys']),
            'error' => $result['error'],
        ];
    }

    /** Jumlahkan metrik CloudWatch dari seluruh API key menjadi satu agregat. */
    private function aggregateAllKeys(string $startDate, string $endDate): array
    {
        $total = 0;
        $operations = [];
        $byKey = [];
        $byKeyOps = [];
        $daily = [];
        $error = null;

        try {
            foreach ($this->listApiKeys()['keys'] ?? [] as $key) {
                $name = $key['key_name'] ?? null;
                if (!$name) {
                    continue;
                }
                $m = $this->getKeyUsageMetrics($name, $startDate, $endDate);
                if (!empty($m['error'])) {
                    $error = $m['error'];
                    continue;
                }
                $total += $m['total'];
                if ($m['total'] > 0) {
                    $byKey[$name] = $m['total'];
                    // Rincian per operasi ikut disimpan supaya biaya per key bisa
                    // dihitung tanpa menembak CloudWatch lagi (harga tiap operasi beda).
                    $byKeyOps[$name] = $m['operations'] ?? [];
                }
                foreach ($m['daily'] ?? [] as $d => $c) {
                    $daily[$d] = ($daily[$d] ?? 0) + $c;
                }
                foreach ($m['operations'] ?? [] as $op => $c) {
                    $operations[$op] = ($operations[$op] ?? 0) + $c;
                }
            }
            arsort($operations);
            arsort($byKey);
            ksort($daily);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'total'       => $total,
            'operations'  => $operations,
            'by_key'      => $byKey,
            'by_key_ops'  => $byKeyOps,
            'daily'       => $daily,
            'error'       => $error,
        ];
    }

    public function getKeyUsageMetrics(string $keyName, string $startDate, string $endDate, ?string $filterOperation = null): array
    {
        try {
            $cloudwatch = new CloudWatchClient($this->clientConfig());

            $startTime = \Carbon\Carbon::parse($startDate)->startOfDay();
            $endTime = \Carbon\Carbon::parse($endDate)->endOfDay();

            // Query per operation with daily granularity
            $ops = $filterOperation ? [$filterOperation] : self::OPERATIONS;

            $queries = [];
            foreach ($ops as $i => $op) {
                $queries[] = [
                    'Id' => 'op_' . $i,
                    'MetricStat' => [
                        'Metric' => [
                            'Namespace'  => 'AWS/Location',
                            'MetricName' => 'CallCount',
                            'Dimensions' => [
                                ['Name' => 'ApiKeyName', 'Value' => $keyName],
                                ['Name' => 'OperationName', 'Value' => $op],
                            ],
                        ],
                        'Period' => 86400,
                        'Stat'   => 'Sum',
                    ],
                    'ReturnData' => true,
                ];
            }

            $result = $cloudwatch->getMetricData([
                'MetricDataQueries' => $queries,
                'StartTime' => $startTime->toIso8601String(),
                'EndTime'   => $endTime->toIso8601String(),
            ]);

            $daily = [];
            $total = 0;
            $operations = [];
            $matrix = [];               // [tanggal][operasi] => jumlah

            foreach ($result['MetricDataResults'] ?? [] as $metricResult) {
                $idx = (int) str_replace('op_', '', $metricResult['Id']);
                $opName = $ops[$idx] ?? '';
                $opTotal = 0;

                $timestamps = $metricResult['Timestamps'] ?? [];
                $values = $metricResult['Values'] ?? [];

                for ($i = 0; $i < count($timestamps); $i++) {
                    $date = $timestamps[$i]->format('Y-m-d');
                    $count = (int) ($values[$i] ?? 0);
                    $daily[$date] = ($daily[$date] ?? 0) + $count;
                    $matrix[$date][$opName] = ($matrix[$date][$opName] ?? 0) + $count;
                    $total += $count;
                    $opTotal += $count;
                }

                if ($opTotal > 0) {
                    $operations[$opName] = $opTotal;
                }
            }

            ksort($daily);
            arsort($operations);

            // Disimpan permanen supaya rentang lain (mis. yang dibuka klien di
            // halaman laporan) bisa dijawab tanpa menembak CloudWatch lagi.
            // Hanya saat menarik seluruh operasi — hasil terfilter tidak utuh.
            if (!$filterOperation) {
                \App\Models\ApiKeyUsageDaily::store($this->account?->id, $keyName, $matrix);
            }

            return [
                'total'      => $total,
                'daily'      => $daily,
                'operations' => $operations,
                'error'      => null,
            ];
        } catch (AwsException $e) {
            Log::error("CloudWatch metrics error for key '{$keyName}': " . $e->getAwsErrorMessage());
            return ['total' => 0, 'daily' => [], 'operations' => [], 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
        } catch (\Exception $e) {
            Log::error("CloudWatch metrics exception for key '{$keyName}': " . $e->getMessage());
            return ['total' => 0, 'daily' => [], 'operations' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get list of available operations.
     */
    public static function getOperations(): array
    {
        return self::OPERATIONS;
    }
}
