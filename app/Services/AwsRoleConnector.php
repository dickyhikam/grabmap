<?php

namespace App\Services;

use App\Models\AwsAccount;
use App\Models\Company;
use Aws\Exception\AwsException;
use Aws\Sts\StsClient;

/**
 * Panggilan STS untuk menghubungkan akun klien lewat AssumeRole. Dipisah dari
 * controller supaya bisa diganti di test tanpa menembak AWS.
 */
class AwsRoleConnector
{
    /** Account ID asli di balik kredensial akun sumber (sts:GetCallerIdentity, tanpa izin khusus). */
    public function callerAccountId(AwsAccount $source): string
    {
        return (string) $this->sts($source)->getCallerIdentity()['Account'];
    }

    /**
     * Coba assume role-nya, lalu baca daftar API key memakai kredensial hasilnya.
     * Dua langkah terpisah supaya pesan galatnya menyebut bagian yang gagal —
     * provider AssumeRole SDK hanya melempar pesan umum.
     *
     * @return array{ok: bool, step: string|null, error: string|null, keys: int, key_names?: array<int, string>, resources?: ?array}
     */
    public function verify(AwsAccount $candidate): array
    {
        try {
            $this->sts($candidate->sourceAccount, $candidate->region)->assumeRole([
                'RoleArn'         => $candidate->role_arn,
                'RoleSessionName' => 'grabmaps-connect-check',
                'ExternalId'      => $candidate->external_id,
                'DurationSeconds' => 900,
            ]);
        } catch (AwsException $e) {
            return ['ok' => false, 'step' => 'assume', 'error' => $e->getAwsErrorMessage() ?: $e->getMessage(), 'keys' => 0];
        }

        $service = AwsLocationService::forAccount($candidate);
        $result = $service->listApiKeys();
        $ok = $result['error'] === null;

        return [
            'ok'        => $ok,
            'step'      => $ok ? null : 'list_keys',
            'error'     => $result['error'],
            'keys'      => count($result['keys']),
            'key_names' => array_values(array_filter(array_column($result['keys'], 'key_name'))),
            // Place Index / Map / dst. beserta data source-nya. Tidak menggagalkan
            // koneksi: role lama yang belum punya izinnya cukup tercatat di 'denied'.
            'resources' => $ok ? $service->discoverResources() : null,
        ];
    }

    /** Tarikan pertama 91 hari untuk semua key perusahaan yang baru terhubung. */
    public function pullInitialUsage(Company $company): void
    {
        $company->refreshUsage(now()->subDays(91)->format('Y-m-d'), now()->format('Y-m-d'));
    }

    private function sts(AwsAccount $source, ?string $region = null): StsClient
    {
        return new StsClient([
            'region'      => $region ?: ($source->region ?: 'ap-southeast-1'),
            'version'     => 'latest',
            'credentials' => $source->credentials(),
        ]);
    }
}
