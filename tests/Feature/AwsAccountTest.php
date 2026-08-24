<?php

namespace Tests\Feature;

use App\Models\AwsAccount;
use App\Models\Company;
use App\Models\User;
use App\Services\AwsLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AwsAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        // Role sekarang berupa relasi ke tabel roles (di-seed oleh migrasi).
        return User::factory()->create([
            'role_id'           => \App\Models\Role::where('slug', 'admin')->value('id'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_admin_can_create_and_default_an_account(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.aws-accounts.store'), [
                'name'              => 'Akun Klien',
                'access_key_id'     => 'AKIAEXAMPLECLIENT',
                'secret_access_key' => 'client-secret',
                'region'            => 'ap-southeast-1',
                'is_active'         => '1',
            ])
            ->assertRedirect(route('admin.aws-accounts.index'));

        $account = AwsAccount::where('name', 'Akun Klien')->firstOrFail();

        // Secret disimpan terenkripsi di DB, tapi terbaca lagi lewat model.
        $this->assertSame('client-secret', $account->secret_access_key);
        $this->assertNotSame('client-secret', $account->getRawOriginal('secret_access_key'));
    }

    public function test_only_one_account_can_be_default(): void
    {
        $first  = AwsAccount::create(['name' => 'A', 'access_key_id' => 'k1', 'secret_access_key' => 's1', 'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true]);
        $second = AwsAccount::create(['name' => 'B', 'access_key_id' => 'k2', 'secret_access_key' => 's2', 'region' => 'us-east-1', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.aws-accounts.default', $second))
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame($second->id, AwsAccount::defaultAccount()->id);
    }

    public function test_service_uses_credentials_and_region_of_the_given_account(): void
    {
        $a = AwsAccount::create(['name' => 'A', 'access_key_id' => 'k1', 'secret_access_key' => 's1', 'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true]);
        $b = AwsAccount::create(['name' => 'B', 'access_key_id' => 'k2', 'secret_access_key' => 's2', 'region' => 'us-east-1', 'is_active' => true]);

        $this->assertSame('ap-southeast-1', AwsLocationService::forAccount($a)->region());
        $this->assertSame('us-east-1', AwsLocationService::forAccount($b)->region());

        // Tanpa argumen = akun default.
        $this->assertSame($a->id, (new AwsLocationService())->account()->id);

        // Snapshot usage tidak boleh tercampur antar akun walau nama key-nya sama.
        $this->assertNotSame(
            AwsLocationService::forAccount($a)->cacheKey('aws_key_info:demo'),
            AwsLocationService::forAccount($b)->cacheKey('aws_key_info:demo'),
        );
    }

    public function test_account_still_used_by_a_company_cannot_be_deleted(): void
    {
        $account = AwsAccount::create(['name' => 'A', 'access_key_id' => 'k1', 'secret_access_key' => 's1', 'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true]);
        Company::create(['name' => 'Klien', 'slug' => 'klien', 'is_active' => true, 'aws_account_id' => $account->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.aws-accounts.destroy', $account))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('aws_accounts', ['id' => $account->id]);
    }

    /**
     * Dashboard menampilkan SATU akun saja (akun default, atau akun yang dipilih di
     * topbar) — angkanya tidak lagi menjumlahkan seluruh akun.
     */
    public function test_dashboard_shows_one_account_at_a_time(): void
    {
        $a = AwsAccount::create(['name' => 'Akun Utama', 'access_key_id' => 'k1', 'secret_access_key' => 's1', 'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true]);
        $b = AwsAccount::create(['name' => 'Akun Klien', 'access_key_id' => 'k2', 'secret_access_key' => 's2', 'region' => 'ap-southeast-1', 'is_active' => true]);

        $start = now()->startOfMonth()->format('Y-m-d');
        $end   = now()->format('Y-m-d');

        // Isi snapshot manual supaya dashboard tidak menembak AWS sama sekali.
        Cache::put('dashboard_api_keys:' . $a->id, ['total' => 2, 'active' => 2]);
        Cache::put('dashboard_api_keys:' . $b->id, ['total' => 1, 'active' => 1]);
        foreach ([[$a, 1000], [$b, 250]] as [$account, $count]) {
            Cache::put("aws_usage_aggregate:acct{$account->id}:{$start}:{$end}", [
                'data' => [
                    'total'      => $count,
                    'operations' => ['SearchText' => $count],
                    'by_key'     => ['demo' => $count],
                    'daily'      => [$end => $count],
                    'error'      => null,
                ],
                'fetched_at' => now()->toIso8601String(),
            ]);
        }

        // Tanpa memilih apa pun: akun default yang tampil.
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(number_format(1000))
            ->assertDontSee(number_format(1250));

        // Pindah ke akun lain lewat pill di topbar → angkanya ikut berganti.
        $this->actingAs($this->admin())
            ->get(route('admin.aws-scope', $b))
            ->assertSessionHas('admin_aws_scope', $b->id);

        $this->actingAs($this->admin())
            ->withSession(['admin_aws_scope' => $b->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(number_format(250))
            ->assertDontSee(number_format(1000));
    }

    public function test_aws_account_pages_render(): void
    {
        $account = AwsAccount::create(['name' => 'Akun Utama', 'access_key_id' => 'AKIAEXAMPLE123', 'secret_access_key' => 's1', 'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true]);
        $company = Company::create(['name' => 'Klien', 'slug' => 'klien', 'is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.aws-accounts.index'))->assertOk()->assertSee('Akun Utama');
        $this->actingAs($admin)->get(route('admin.aws-accounts.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.aws-accounts.edit', $account))->assertOk()->assertSee('AKIAEXAMPLE123');
        $this->actingAs($admin)->get(route('admin.companies.edit', $company))->assertOk()->assertSee('Akun AWS');
    }

    public function test_company_usage_page_resolves_the_account_of_its_key(): void
    {
        $account = AwsAccount::create(['name' => 'Akun Klien', 'access_key_id' => 'k1', 'secret_access_key' => 's1', 'region' => 'us-east-1', 'is_active' => true, 'is_default' => true]);
        $company = Company::create(['name' => 'Klien', 'slug' => 'klien', 'is_active' => true, 'aws_account_id' => $account->id]);

        $this->assertSame('us-east-1', AwsLocationService::forAccount($company->awsAccount)->region());
    }
}
