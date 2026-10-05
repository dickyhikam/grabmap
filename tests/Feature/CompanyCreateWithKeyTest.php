<?php

namespace Tests\Feature;

use App\Models\AwsAccount;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memilih API key langsung di formulir tambah perusahaan.
 *
 * Yang diuji hanya sisi penempelannya — daftar key yang ditawarkan datang dari
 * AWS dan sudah punya jalurnya sendiri di halaman detail.
 */
class CompanyCreateWithKeyTest extends TestCase
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

    private function account(): AwsAccount
    {
        return AwsAccount::create([
            'name' => 'Utama', 'access_key_id' => 'k1', 'secret_access_key' => 's1',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name'      => 'PT Contoh',
            'slug'      => 'pt-contoh',
            'is_active' => 1,
            // Formulir selalu mengirim tarif service charge — di sini cukup "ikut akun".
            'sc_mode'   => 'inherit',
        ], $extra);
    }

    public function test_chosen_key_is_attached_as_primary(): void
    {
        $account = $this->account();

        $this->actingAs($this->admin())
            ->post(route('admin.companies.store'), $this->payload([
                'key_ref' => $account->id . '|client-prod',
            ]))
            ->assertRedirect(route('admin.companies.index'));

        $company = Company::where('slug', 'pt-contoh')->firstOrFail();
        $key = $company->apiKeys()->firstOrFail();

        $this->assertSame('client-prod', $key->key_name);
        $this->assertSame($account->id, $key->aws_account_id);
        $this->assertTrue($key->is_primary);

        // Akun perusahaan ikut akun key-nya — tidak ada lagi pilihan akun di formulir.
        $company->refresh();
        $this->assertSame('client-prod', $company->aws_api_key_name);
        $this->assertSame($account->id, $company->aws_account_id);
    }

    public function test_company_is_still_created_when_the_key_was_claimed_meanwhile(): void
    {
        $account = $this->account();

        $other = Company::create(['name' => 'PT Lain', 'slug' => 'pt-lain', 'is_active' => true]);
        CompanyApiKey::create([
            'company_id'     => $other->id,
            'aws_account_id' => $account->id,
            'key_name'       => 'client-prod',
            'is_primary'     => true,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.store'), $this->payload([
                'key_ref' => $account->id . '|client-prod',
            ]))
            ->assertRedirect(route('admin.companies.index'))
            ->assertSessionHas('warning');

        $company = Company::where('slug', 'pt-contoh')->firstOrFail();

        $this->assertSame(0, $company->apiKeys()->count());
    }

    public function test_key_is_optional(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.companies.store'), $this->payload())
            ->assertRedirect(route('admin.companies.index'))
            ->assertSessionMissing('warning');

        $this->assertSame(0, Company::where('slug', 'pt-contoh')->firstOrFail()->apiKeys()->count());
    }
}
