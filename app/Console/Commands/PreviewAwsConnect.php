<?php

namespace App\Console\Commands;

use App\Models\ApiKeyUsageShare;
use App\Models\AwsAccount;
use App\Models\AwsConnectInvite;
use App\Models\AwsUsageTypeDaily;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh untuk melihat halaman publik undangan AWS tanpa role sungguhan:
 * undangan menunggu, terhubung (beserta dashboard berisi tagihan ala JPAI), dan
 * kedaluwarsa. Hanya untuk lokal. Akun role-nya nonaktif, jadi cron dan tombol
 * Refresh tidak pernah menembak AWS dengannya.
 */
class PreviewAwsConnect extends Command
{
    protected $signature = 'aws-connect:preview {--cleanup : Hapus data contoh}';

    protected $description = 'Buat (atau hapus) data contoh halaman publik undangan AWS — hanya lokal';

    private const SLUG = 'preview-klien-aws';
    private const ROLE_ARN = 'arn:aws:iam::999988887777:role/GrabMaps-Usage-ReadOnly';

    public function handle(): int
    {
        if (!app()->environment('local')) {
            $this->error('Hanya untuk APP_ENV=local.');
            return self::FAILURE;
        }

        $this->cleanup();

        if ($this->option('cleanup')) {
            $this->info('Data contoh dihapus.');
            return self::SUCCESS;
        }

        $source = AwsAccount::assumeRoleSources()->firstWhere('is_default', true) ?? AwsAccount::assumeRoleSources()->first();
        if (!$source) {
            $this->error('Butuh minimal satu akun AWS aktif dengan access key sebagai sumber.');
            return self::FAILURE;
        }

        // Account ID asli di balik kredensial sumber, sama seperti undangan sungguhan —
        // isian manual di akun bisa kosong atau keliru.
        try {
            $trusted = app(\App\Services\AwsRoleConnector::class)->callerAccountId($source);
        } catch (\Throwable $e) {
            $this->error("Tidak bisa membaca identitas akun {$source->name}: " . $e->getMessage());
            return self::FAILURE;
        }

        [$pending, $connected, $expired, $share] = DB::transaction(function () use ($source, $trusted) {
            $company = Company::create(['name' => 'Preview Klien AWS', 'slug' => self::SLUG, 'is_active' => true]);

            $base = [
                'company_id' => $company->id, 'source_account_id' => $source->id,
                'trusted_account_id' => $trusted, 'region' => 'ap-southeast-1',
            ];
            $make = fn (array $extra) => AwsConnectInvite::create($base + $extra + [
                'token'       => AwsConnectInvite::generateToken(),
                'label'       => 'Preview Klien AWS',
                'external_id' => AwsConnectInvite::generateExternalId(),
                'expires_at'  => now()->addDays(7),
            ]);

            $pending = $make([]);
            $expired = $make(['expires_at' => now()->subDay()]);

            // Akun klien palsu: nonaktif supaya tidak pernah disinkron ke AWS.
            $account = AwsAccount::create([
                'name' => 'Preview Klien AWS (contoh)', 'account_number' => '999988887777',
                'role_arn' => self::ROLE_ARN, 'external_id' => 'grabmaps-preview',
                'source_account_id' => $source->id, 'owner_company_id' => $company->id,
                'region' => 'ap-southeast-1', 'is_active' => false, 'is_default' => false,
                'last_verified_at' => now(), 'resources_checked_at' => now(),
                'resources' => [
                    'place_indexes'        => [['name' => 'preview-place-index', 'data_source' => 'Grab']],
                    'maps'                 => [],
                    'route_calculators'    => [],
                    'geofence_collections' => [['name' => 'preview-geofence']],
                    'denied'               => [],
                ],
                'notes' => 'Data contoh dari aws-connect:preview — aman dihapus.',
            ]);
            $company->update(['aws_account_id' => $account->id]);

            // Tagihan 30 hari mirip JPAI: geocoding Place Index v1 berdata Grab, tanpa API key.
            $rows = [];
            for ($i = 30; $i >= 1; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $geocode = random_int(15000, 24000);
                $rows[] = ['date' => $date, 'usage_type' => 'APS1-Usage-Grab-Geocode', 'unit' => 'Address', 'quantity' => $geocode, 'cost' => $geocode * 0.0005];
                $rows[] = ['date' => $date, 'usage_type' => 'APS1-GeofenceMonth', 'unit' => 'Geofence-month', 'quantity' => 0.023, 'cost' => 0];
            }
            $rows[] = ['date' => now()->subDays(12)->format('Y-m-d'), 'usage_type' => 'APS1-Usage-Grab-ReverseGeocode', 'unit' => 'Position', 'quantity' => 1, 'cost' => 0.0005];
            AwsUsageTypeDaily::store($account->id, now()->subDays(30)->format('Y-m-d'), now()->subDay()->format('Y-m-d'), $rows);
            $account->forceFill(['usage_types_synced_at' => now()])->save();

            $share = ApiKeyUsageShare::enableForCompany($company->id, 'aws-connect:preview', null, 'Dashboard klien');

            // Baru saja terhubung, supaya animasi sukses (termasuk percikan) ikut tampil.
            $connected = $make([
                'used_at' => now(), 'used_ip' => '127.0.0.1', 'aws_account_id' => $account->id,
                'keys_found' => 0, 'usage_share_id' => $share->id,
            ]);

            return [$pending, $connected, $expired, $share];
        });

        $this->info('Data contoh siap. Buka:');
        $this->line('  Menunggu (tutorial + form) : ' . $pending->url());
        $this->line('  Terhubung (sukses)         : ' . $connected->url());
        $this->line('  Kedaluwarsa                : ' . $expired->url());
        $this->line('  Dashboard klien (laporan)  : ' . $share->publicUrl());
        $this->line('  Detail company (admin)     : ' . route('admin.companies.show', Company::where('slug', self::SLUG)->first()));
        $this->newLine();
        $this->line('Jalankan ulang untuk mereset animasi sukses. Hapus: php artisan aws-connect:preview --cleanup');

        return self::SUCCESS;
    }

    private function cleanup(): void
    {
        DB::transaction(function () {
            $company = Company::where('slug', self::SLUG)->first();
            if ($company) {
                AwsConnectInvite::where('company_id', $company->id)->delete();
                ApiKeyUsageShare::where('company_id', $company->id)->delete();
                $company->update(['aws_account_id' => null]);
            }

            // Baris tagihan ikut terhapus lewat cascade.
            AwsAccount::where('role_arn', self::ROLE_ARN)->where('is_active', false)->delete();
            $company?->features()->delete();
            $company?->delete();
        });
    }
}
