<?php

namespace Tests\Feature;

use App\Models\AwsAccount;
use App\Models\AwsConnectInvite;
use App\Models\AwsUsageTypeDaily;
use App\Models\ApiKeyUsageShare;
use App\Models\Company;
use App\Models\User;
use App\Services\AwsLocationService;
use App\Services\AwsRoleConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AwsConnectInviteTest extends TestCase
{
    use RefreshDatabase;

    private const ARN = 'arn:aws:iam::999988887777:role/GrabMaps-Usage-ReadOnly';

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'           => \App\Models\Role::where('slug', 'admin')->value('id'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    private function source(): AwsAccount
    {
        return AwsAccount::create([
            'name' => 'Akun Grab', 'access_key_id' => 'AKIAGRAB', 'secret_access_key' => 'grab-secret',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);
    }

    private function invite(AwsAccount $source, array $attrs = []): AwsConnectInvite
    {
        return AwsConnectInvite::create($attrs + [
            'token'              => AwsConnectInvite::generateToken(),
            'label'              => 'JPAI',
            'source_account_id'  => $source->id,
            'trusted_account_id' => '111122223333',
            'external_id'        => 'grabmaps-test-external',
            'region'             => 'ap-southeast-1',
            'expires_at'         => now()->addDays(7),
        ]);
    }

    private function fakeConnector(array $verify = ['ok' => true, 'step' => null, 'error' => null, 'keys' => 2, 'key_names' => ['jpai-web', 'jpai-app']]): void
    {
        $mock = Mockery::mock(AwsRoleConnector::class);
        $mock->shouldReceive('callerAccountId')->andReturn('111122223333');
        $mock->shouldReceive('verify')->andReturn($verify);
        $mock->shouldReceive('pullInitialUsage');
        $this->app->instance(AwsRoleConnector::class, $mock);
    }

    public function test_admin_creates_invite_with_trusted_account_from_aws(): void
    {
        $this->fakeConnector();
        $source = $this->source();

        $this->actingAs($this->admin())
            ->post(route('admin.aws-invites.store'), [
                'label' => 'JPAI', 'source_account_id' => $source->id, 'region' => 'ap-southeast-1', 'days' => 7,
            ])
            ->assertRedirect(route('admin.aws-invites.index'));

        $invite = AwsConnectInvite::firstOrFail();
        $this->assertSame('111122223333', $invite->trusted_account_id);
        $this->assertStringStartsWith('grabmaps-', $invite->external_id);
        // Account ID akun sumber yang kosong ikut terisi dari AWS.
        $this->assertSame('111122223333', $source->fresh()->account_number);

        $this->actingAs($this->admin())->get(route('admin.aws-invites.index'))->assertOk()->assertSee('JPAI');
    }

    public function test_public_page_and_template_contain_trust_details(): void
    {
        $invite = $this->invite($this->source());

        $this->get(route('aws-connect.show', $invite->token))
            ->assertOk()
            ->assertSee('111122223333')
            ->assertSee('grabmaps-test-external');

        $yaml = $this->get(route('aws-connect.template', $invite->token))->assertOk()->getContent();
        $this->assertStringContainsString('arn:aws:iam::111122223333:root', $yaml);
        $this->assertStringContainsString('"sts:ExternalId": "grabmaps-test-external"', $yaml);
        $this->assertStringContainsString('RoleName: GrabMaps-Usage-ReadOnly', $yaml);

        $this->get(route('aws-connect.show', 'token-salah'))->assertNotFound();
    }

    public function test_client_connects_role_and_invite_is_used_once(): void
    {
        $this->fakeConnector();
        $source  = $this->source();
        $company = Company::create(['name' => 'JPAI', 'slug' => 'jpai', 'is_active' => true]);
        $invite  = $this->invite($source, ['company_id' => $company->id]);

        $this->post(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1'])
            ->assertRedirect(route('aws-connect.show', $invite->token));

        $account = AwsAccount::where('role_arn', self::ARN)->firstOrFail();
        $this->assertSame('JPAI', $account->name);
        $this->assertSame('999988887777', $account->account_number);
        $this->assertSame('grabmaps-test-external', $account->external_id);
        $this->assertNull($account->secret_access_key);
        $this->assertTrue($account->hasCredentials());
        $this->assertSame($account->id, $company->fresh()->aws_account_id);
        $this->assertSame($account->id, $invite->fresh()->aws_account_id);

        // Seluruh key di akun klien otomatis jadi milik company, dan link dashboard
        // tanpa cakupan key (= semua key company) langsung dibuat.
        $this->assertSame($company->id, $account->owner_company_id);
        $this->assertEqualsCanonicalizing(['jpai-web', 'jpai-app'], $company->apiKeys()->pluck('key_name')->all());
        $share = $invite->fresh()->usageShare;
        $this->assertNotNull($share);
        $this->assertSame($company->id, $share->company_id);
        $this->assertTrue($share->isActive());
        $this->assertSame(0, $share->keys()->count());

        // Halaman yang sama sekarang menampilkan link dashboard.
        $this->get(route('aws-connect.show', $invite->token))
            ->assertOk()
            ->assertSee(__('awsconnect.ok_title'))
            ->assertSee($share->publicUrl(), false);

        // Link tidak bisa dipakai dua kali.
        $this->post(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1'])
            ->assertStatus(410);
        $this->assertSame(1, AwsAccount::where('role_arn', self::ARN)->count());
    }

    public function test_failed_verification_saves_nothing(): void
    {
        $this->fakeConnector(['ok' => false, 'step' => 'assume', 'error' => 'not authorized to perform: sts:AssumeRole', 'keys' => 0]);
        $invite = $this->invite($this->source());

        $this->from(route('aws-connect.show', $invite->token))
            ->post(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1'])
            ->assertRedirect()
            ->assertSessionHas('connect_error');

        $this->assertNull(AwsAccount::where('role_arn', self::ARN)->first());
        $this->assertNull($invite->fresh()->used_at);
    }

    public function test_json_submission_reports_the_failed_step(): void
    {
        $this->fakeConnector(['ok' => false, 'step' => 'list_keys', 'error' => 'AccessDenied', 'keys' => 0]);
        $invite = $this->invite($this->source());

        $this->postJson(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1'])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'step' => 'list_keys', 'error' => 'AccessDenied']);

        $this->postJson(route('aws-connect.store', $invite->token), ['role_arn' => 'bukan-arn', 'region' => 'ap-southeast-1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role_arn');
    }

    public function test_json_submission_returns_redirect_on_success(): void
    {
        $this->fakeConnector();
        $invite = $this->invite($this->source());

        $this->postJson(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1'])
            ->assertOk()
            ->assertJson(['ok' => true, 'redirect' => route('aws-connect.show', $invite->token)]);
    }

    public function test_company_form_can_issue_an_invite(): void
    {
        $this->fakeConnector();
        $source = $this->source();

        // Dua jalur API key tampil di card terpisah.
        $this->actingAs($this->admin())->get(route('admin.companies.create'))
            ->assertOk()
            ->assertSee(__('companies.card_grab_key'))
            ->assertSee(__('awsconnect.co_card'))
            ->assertSee('connectAws', false);

        $response = $this->actingAs($this->admin())->post(route('admin.companies.store'), [
            'name' => 'JPAI', 'slug' => 'jpai', 'is_active' => '1',
            'connect_aws' => '1', 'connect_source_id' => $source->id,
            'connect_region' => 'ap-southeast-1', 'connect_days' => 7,
            'sc_mode' => 'inherit',
        ]);

        $company = Company::where('slug', 'jpai')->firstOrFail();
        $response->assertRedirect(route('admin.companies.show', $company));

        $invite = AwsConnectInvite::firstOrFail();
        $this->assertSame($company->id, $invite->company_id);
        $this->assertSame('JPAI', $invite->label);

        $this->actingAs($this->admin())->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee($invite->url(), false);
    }

    public function test_new_key_option_validates_before_anything_is_created(): void
    {
        $source = $this->source();

        $this->actingAs($this->admin())->get(route('admin.companies.create'))
            ->assertOk()
            ->assertSee(__('companies.new_key_option'))
            ->assertSee('newKeyFields', false)
            // Isian key baru memakai komponen yang sama dengan halaman tambah API key.
            ->assertSee('svc-group', false)
            ->assertSee('expRow', false);

        $this->actingAs($this->admin())->get(route('admin.api-keys.create'))
            ->assertOk()
            ->assertSee('svc-group', false)
            ->assertSee('expRow', false);

        // Nama key kosong: ditolak validasi, AWS tidak dipanggil, perusahaan tidak dibuat.
        $this->actingAs($this->admin())->post(route('admin.companies.store'), [
            'name' => 'JPAI', 'slug' => 'jpai', 'is_active' => '1', 'sc_mode' => 'inherit',
            'connect_source_id' => $source->id, 'key_ref' => '__new',
            'actions' => ['geo-maps:*'], 'expiry_mode' => 'never',
        ])->assertSessionHasErrors('key_name');

        $this->assertNull(Company::where('slug', 'jpai')->first());
    }

    public function test_usage_type_names_reveal_the_provider(): void
    {
        // v1: provider tertulis di nama usage type.
        $this->assertSame(['region' => 'APS1', 'provider' => 'Grab', 'api' => 'v1', 'product' => 'Geocode'], AwsUsageTypeDaily::parse('APS1-Usage-Grab-Geocode'));
        $this->assertSame(['region' => 'APS1', 'provider' => 'Esri', 'api' => 'v1', 'product' => 'Geocode'], AwsUsageTypeDaily::parse('APS1-Usage-Esri-Geocode'));
        // v2: tertulis "Default"; di Singapura & Malaysia itu GrabMaps, di region lain bukan.
        $this->assertSame(['region' => 'APS1', 'provider' => 'Grab', 'api' => 'v2', 'product' => 'Places-Core'], AwsUsageTypeDaily::parse('APS1-Usage-Default-Places-Core'));
        $this->assertSame(['region' => 'APS5', 'provider' => 'Grab', 'api' => 'v2', 'product' => 'Maps-Tiles'], AwsUsageTypeDaily::parse('APS5-Usage-Default-Maps-Tiles'));
        $this->assertSame(['region' => 'USE1', 'provider' => 'Default', 'api' => 'v2', 'product' => 'Maps-Tiles'], AwsUsageTypeDaily::parse('USE1-Usage-Default-Maps-Tiles'));
        $this->assertSame(['region' => 'APS1', 'provider' => null, 'api' => null, 'product' => 'GeofenceMonth'], AwsUsageTypeDaily::parse('APS1-GeofenceMonth'));
        $this->assertSame(['region' => 'APS1', 'provider' => null, 'api' => null, 'product' => 'ResourceCreateReadUpdateDelete'], AwsUsageTypeDaily::parse('APS1-Usage-ResourceCreateReadUpdateDelete'));

        // Policy role klien sekarang juga boleh membaca daftar resource beserta data source-nya.
        $actions = collect(AwsConnectInvite::permissionPolicy()['Statement'])->pluck('Action')->flatten()->all();
        $this->assertContains('geo:ListPlaceIndexes', $actions);
        $this->assertContains('geo:ListMaps', $actions);
    }

    /**
     * Kasus JPAI: geocoding lewat Place Index v1 (provider Grab) tanpa API key —
     * CloudWatch per key nol, tapi tagihan AWS ada. Laporan harus menunjukkannya.
     */
    public function test_report_shows_billing_when_usage_bypasses_api_keys(): void
    {
        $this->fakeConnector(['ok' => true, 'step' => null, 'error' => null, 'keys' => 0, 'key_names' => [],
            'resources' => ['place_indexes' => [['name' => 'jpai-index', 'data_source' => 'Grab']], 'maps' => [], 'route_calculators' => [], 'geofence_collections' => [['name' => 'jpai-fence']], 'denied' => []]]);
        $company = Company::create(['name' => 'JPAI', 'slug' => 'jpai', 'is_active' => true]);
        $invite  = $this->invite($this->source(), ['company_id' => $company->id]);

        $this->post(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1']);

        $account = AwsAccount::where('role_arn', self::ARN)->firstOrFail();
        $this->assertSame('Grab', $account->resources['place_indexes'][0]['data_source']);

        // Halaman sukses: resource & provider terlihat, plus penjelasan tanpa API key.
        $this->get(route('aws-connect.show', $invite->token))
            ->assertSee(__('awsconnect.ok_chip_resources', ['n' => 2]))
            ->assertSee('GrabMaps')
            ->assertSee(__('awsconnect.ok_no_keys_note'));

        $day = now()->subDays(2)->format('Y-m-d');
        AwsUsageTypeDaily::store($account->id, $day, $day, [
            ['date' => $day, 'usage_type' => 'APS1-Usage-Grab-Geocode', 'unit' => 'Address', 'quantity' => 596723, 'cost' => 298.36],
            ['date' => $day, 'usage_type' => 'APS1-GeofenceMonth', 'unit' => 'Geofence-month', 'quantity' => 0.699, 'cost' => 0.0],
        ]);

        $share = $invite->fresh()->usageShare;
        $this->get($share->publicUrl())
            ->assertOk()
            // Layout khusus akun milik klien: semua angka dari tagihan, dan
            // cakupan API key 0% menggantikan peringatan "tidak lewat API key".
            ->assertSee(__('awsconnect.ca_eyebrow'))
            ->assertSee(__('awsconnect.kpi_coverage'))
            ->assertSee(__('awsconnect.ca_no_keys'))
            ->assertSee('data-rate-card', false)
            ->assertSee('APS1-Usage-Grab-Geocode')
            ->assertSee(__('awsconnect.products.Geocode'))
            ->assertSee('jpai-index')
            ->assertSee('$298.36')
            // Kartu biaya memakai tagihan, jadi PPN 11% dihitung dari $298.36.
            ->assertSee(__('awsconnect.cost_from_bill'))
            ->assertSee('$32.82');

        // Excel ikut: ada lembar tagihan per usage type.
        $xlsx = $this->get(route('usage-report.export', ['token' => $share->share_token]))->assertOk()->streamedContent();
        $this->assertStringStartsWith('PK', $xlsx);

        $this->actingAs($this->admin())->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('jpai-index')
            ->assertSee('$298.36');
    }

    public function test_expired_invite_rejects_submission(): void
    {
        $this->fakeConnector();
        $invite = $this->invite($this->source(), ['expires_at' => now()->subMinute()]);

        $this->get(route('aws-connect.show', $invite->token))->assertOk()->assertSee(__('awsconnect.expired_title'));
        $this->post(route('aws-connect.store', $invite->token), ['role_arn' => self::ARN, 'region' => 'ap-southeast-1'])
            ->assertStatus(410);
    }

    public function test_role_account_service_uses_assume_role_provider(): void
    {
        $source = $this->source();
        $role = AwsAccount::create([
            'name' => 'JPAI', 'role_arn' => self::ARN, 'external_id' => 'x', 'source_account_id' => $source->id,
            'region' => 'ap-southeast-1', 'is_active' => true,
        ]);

        $this->assertTrue(is_callable($role->credentials()));
        $this->assertTrue(AwsLocationService::hasCredentials($role));
        $this->assertSame('ap-southeast-1', AwsLocationService::forAccount($role)->region());

        // Akun sumber tidak bisa dihapus selama masih dipakai akun role.
        $this->actingAs($this->admin())
            ->delete(route('admin.aws-accounts.destroy', $source))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('aws_accounts', ['id' => $source->id]);

        $this->actingAs($this->admin())->get(route('admin.aws-accounts.index'))->assertOk()->assertSee('AssumeRole');
        $this->actingAs($this->admin())->get(route('admin.aws-accounts.edit', $role))->assertOk()->assertSee(self::ARN);
    }
}
