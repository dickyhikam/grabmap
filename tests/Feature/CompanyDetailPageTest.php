<?php

namespace Tests\Feature;

use App\Models\ApiKeyUsageShare;
use App\Models\AwsAccount;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman detail perusahaan: isinya banyak cabang (key, link, riwayat), jadi
 * yang dijaga di sini adalah halamannya tetap terbentuk dan angka ringkasnya
 * benar — bukan tampilannya.
 */
class CompanyDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'           => Role::where('slug', 'admin')->value('id'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_detail_page_renders_summary_numbers(): void
    {
        $account = AwsAccount::create([
            'name' => 'Utama', 'access_key_id' => 'k1', 'secret_access_key' => 's1',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);

        $company = Company::create(['name' => 'Klien', 'slug' => 'klien', 'is_active' => true]);

        CompanyApiKey::create([
            'company_id'     => $company->id,
            'aws_account_id' => $account->id,
            'key_name'       => 'klien-prod',
            'is_primary'     => true,
        ]);

        ApiKeyUsageShare::create([
            'company_id'    => $company->id,
            'share_token'   => 'tok-aktif',
            'share_enabled' => true,
        ]);

        ApiKeyUsageShare::create([
            'company_id'    => $company->id,
            'share_token'   => 'tok-mati',
            'share_enabled' => false,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('klien-prod')
            // Satu key, satu link aktif dari dua link: angkanya ada di data-count.
            ->assertSee('data-count="1"', false)
            ->assertSee('co-hero', false);
    }

    public function test_detail_page_renders_for_a_company_without_keys(): void
    {
        $company = Company::create(['name' => 'Kosong', 'slug' => 'kosong', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('data-count="0"', false);
    }
}
