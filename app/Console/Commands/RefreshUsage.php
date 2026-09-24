<?php

namespace App\Console\Commands;

use App\Models\CompanyApiKey;
use App\Services\AwsLocationService;
use Illuminate\Console\Command;

/**
 * Tarik ulang pemakaian CloudWatch untuk semua API key milik perusahaan.
 * Sama dengan tombol "Refresh usage data" di halaman detail perusahaan, tapi
 * dijalankan scheduler — halaman laporan publik tetap tidak pernah menembak AWS.
 */
class RefreshUsage extends Command
{
    protected $signature = 'usage:refresh {--days=91 : Jumlah hari ke belakang yang ditarik}';

    protected $description = 'Refresh CloudWatch usage for every company API key';

    public function handle(): int
    {
        // Rentang sama dengan bawaan tombol Refresh, supaya snapshot cache yang
        // dibaca halaman admin ikut segar.
        $startDate = now()->subDays((int) $this->option('days'))->format('Y-m-d');
        $endDate   = now()->format('Y-m-d');

        $done = 0;
        $failed = 0;

        foreach (CompanyApiKey::with('awsAccount')->get() as $key) {
            if (!AwsLocationService::hasCredentials($key->awsAccount)) {
                $this->warn("Skip {$key->key_name}: akun AWS tanpa kredensial");
                $failed++;
                continue;
            }

            $snapshot = AwsLocationService::forAccount($key->awsAccount)
                ->getCachedUsage($key->key_name, $startDate, $endDate, null, true);

            if (empty($snapshot['metrics']['error'])) {
                $done++;
            } else {
                $this->warn("Gagal {$key->key_name}: {$snapshot['metrics']['error']}");
                $failed++;
            }
        }

        $this->info("Selesai: {$done} berhasil, {$failed} gagal ({$startDate} s/d {$endDate})");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
