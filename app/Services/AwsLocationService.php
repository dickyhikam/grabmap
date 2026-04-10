<?php

namespace App\Services;

use Aws\CloudWatch\CloudWatchClient;
use Aws\LocationService\LocationServiceClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class AwsLocationService
{
    private LocationServiceClient $client;

    public function __construct()
    {
        $this->client = new LocationServiceClient([
            'region'      => config('aws.region'),
            'version'     => config('aws.version', 'latest'),
            'credentials' => config('aws.credentials'),
        ]);
    }

    /**
     * Check if AWS credentials are configured.
     */
    public static function hasCredentials(): bool
    {
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
    private const OPERATIONS = [
        'GetMapTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites',
        'SearchPlaceIndexForSuggestions', 'SearchPlaceIndexForText', 'SearchPlaceIndexForPosition',
        'GetPlace', 'CalculateRoute', 'CalculateRouteMatrix',
    ];

    public function getKeyUsageMetrics(string $keyName, string $startDate, string $endDate, ?string $filterOperation = null): array
    {
        try {
            $cloudwatch = new CloudWatchClient([
                'region'      => config('aws.region'),
                'version'     => 'latest',
                'credentials' => config('aws.credentials'),
            ]);

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
                    $total += $count;
                    $opTotal += $count;
                }

                if ($opTotal > 0) {
                    $operations[$opName] = $opTotal;
                }
            }

            ksort($daily);
            arsort($operations);

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
