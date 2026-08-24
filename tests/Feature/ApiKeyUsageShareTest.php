<?php

namespace Tests\Feature;

use App\Models\ApiKeyUsageShare;
use App\Models\AwsAccount;
use App\Models\Role;
use App\Models\User;
use App\Services\AwsLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiKeyUsageShareTest extends TestCase
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

    public function test_admin_can_enable_and_view_public_share_link(): void
    {
        $account = AwsAccount::create([
            'name' => 'A', 'access_key_id' => 'k1', 'secret_access_key' => 's1',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);

        $keyName = 'client-prod-key';

        $this->actingAs($this->admin())
            ->post(route('admin.api-keys.share.enable', ['keyName' => $keyName, 'account' => $account->id]), [
                'expires_days' => 90,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $share = ApiKeyUsageShare::forKey($account->id, $keyName);
        $this->assertNotNull($share);
        $this->assertTrue($share->share_enabled);
        $this->assertNotNull($share->share_expires_at);

        $start = now()->subDays(29)->format('Y-m-d');
        $end = now()->format('Y-m-d');
        $cacheKey = 'aws_usage_snapshot:acct' . $account->id . ':' . $keyName . ':' . $start . ':' . $end . ':all';

        Cache::put(
            $cacheKey,
            [
                'metrics' => ['total' => 42, 'daily' => [], 'operations' => ['GetMapTile' => 42], 'error' => null],
                'fetched_at' => now()->toIso8601String(),
            ],
            now()->addHour()
        );

        $this->get($share->publicUrl())
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('42');
    }

    public function test_disabled_or_unknown_token_returns_404(): void
    {
        $account = AwsAccount::create([
            'name' => 'A', 'access_key_id' => 'k1', 'secret_access_key' => 's1',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);

        $share = ApiKeyUsageShare::enable($account->id, 'demo-key', 'Tester');

        $share->disable();

        $this->get(route('usage-report.show', ['token' => $share->share_token]))->assertNotFound();
        $this->get(route('usage-report.show', ['token' => 'not-a-real-token']))->assertNotFound();
    }

    public function test_regenerating_token_invalidates_old_link(): void
    {
        $account = AwsAccount::create([
            'name' => 'A', 'access_key_id' => 'k1', 'secret_access_key' => 's1',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);

        $keyName = 'rotate-me';
        $share = ApiKeyUsageShare::enable($account->id, $keyName, 'Tester');
        $oldToken = $share->share_token;

        $this->actingAs($this->admin())
            ->post(route('admin.api-keys.share.regenerate', ['keyName' => $keyName, 'account' => $account->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('usage-report.show', ['token' => $oldToken]))->assertNotFound();
        $this->assertNotSame($oldToken, $share->fresh()->share_token);
    }
}
