<?php

namespace App\Console\Commands;

use App\Models\AwsAccount;
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

        foreach (AwsAccount::query()->active()->get() as $account) {
            if (!$account->hasCredentials()) {
                continue;
            }

            $service = AwsLocationService::forAccount($account);

            // Key yang dibuat klien di akunnya sendiri sejak refresh terakhir ikut dicatat.
            if ($account->owner_company_id) {
                if ($added = $account->syncOwnerKeys()) {
                    $this->info("{$account->name}: {$added} key baru dicatat");
                }
                $service->discoverResources();
            }

            // Tagihan per usage type dan per service berlaku untuk semua akun, termasuk
            // akun internal (Akun Grab, Tim Alfa) — halaman Tagihan akun membacanya.
            $sync = $service->syncUsageTypes($startDate, $endDate);
            $sync['ok']
                ? $this->info("{$account->name}: {$sync['rows']} baris tagihan per usage type")
                : $this->warn("{$account->name}: Cost Explorer gagal — {$sync['error']}");

            // Bulan lalu ikut ditarik: pajak dan koreksi akhir bulan baru masuk
            // beberapa hari setelah bulan tutup.
            $costs = $service->syncServiceCosts(now()->subMonthNoOverflow()->format('Y-m-01'), now()->format('Y-m-01'));
            $costs['ok']
                ? $this->info("{$account->name}: biaya per service {$costs['months']} bulan")
                : $this->warn("{$account->name}: biaya per service gagal — {$costs['error']}");
        }

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
