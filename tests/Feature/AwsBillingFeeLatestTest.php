<?php

namespace Tests\Feature;

use App\Models\ApiKeyUsageDaily;
use App\Models\AwsAccount;
use App\Models\AwsInvoice;
use App\Models\AwsServiceCostMonthly;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\ServiceCharge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tarif management fee dibetulkan (19% → 9%) dengan tanggal berlaku sesudah
 * bulan tagihan: halaman Tagihan memberi tahu, bisa pratinjau, dan bisa
 * menyimpan tarif terbaru untuk bulan itu tanpa mengubah bulan sebelumnya.
 */
class AwsBillingFeeLatestTest extends TestCase
{
    use RefreshDatabase;

    private AwsAccount $account;
    private Company $kai;
    private Carbon $month;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->month = now()->utc()->startOfMonth()->subMonthNoOverflow();
        $m = $this->month->format('Y-m-d');

        $this->account = AwsAccount::create([
            'name' => 'Akun Tim Alfa', 'access_key_id' => 'AKIAALFA', 'secret_access_key' => 'secret',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);
        $this->kai = Company::create(['name' => 'PT KAI', 'slug' => 'kai', 'is_active' => true, 'aws_account_id' => $this->account->id]);
        CompanyApiKey::create(['company_id' => $this->kai->id, 'aws_account_id' => $this->account->id, 'key_name' => 'kai-prod', 'is_primary' => true]);

        ApiKeyUsageDaily::create(['aws_account_id' => $this->account->id, 'key_name' => 'kai-prod', 'usage_date' => $this->month->copy()->day(5)->format('Y-m-d'), 'operation' => 'SearchText', 'request_count' => 10000, 'synced_at' => now()]);
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $m, 'service' => 'Amazon Location Service', 'cost_usd' => 90, 'synced_at' => now()]);
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $m, 'service' => AwsServiceCostMonthly::TAX, 'cost_usd' => 9.9, 'synced_at' => now()]);
        AwsInvoice::create(['aws_account_id' => $this->account->id, 'month' => $m, 'total_usd' => 99.90, 'total_idr' => 1000000, 'created_by' => 'test']);

        // 19% sejak dua bulan lalu, lalu dibetulkan jadi 9% mulai hari ini.
        $this->rate(null, 19, $this->month->copy()->subMonth());
        $this->rate(null, 9, now()->wib()->startOfDay());
    }

    private function rate(?int $companyId, ?float $percent, Carbon $from): void
    {
        ServiceCharge::create([
            'aws_account_id' => $this->account->id, 'company_id' => $companyId, 'percent' => $percent,
            'monthly_min_idr' => $percent === null ? null : 0, 'effective_from' => $from->format('Y-m-d'),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->value('id'),
            'is_active' => true, 'email_verified_at' => now(),
        ]);
    }

    private function page(array $query = [])
    {
        return $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing', [$this->account, 'month' => $this->month->format('Y-m')] + $query));
    }

    public function test_notice_and_preview_use_latest_rate_without_saving(): void
    {
        $this->page()->assertOk()
            ->assertSee(__('awsbilling.fee_stale_title'))
            ->assertSee('Rp 190.000')               // fee 19% dari Rp 1.000.000
            ->assertSee(__('awsbilling.fee_apply_btn'));

        $this->page(['fee' => 'latest'])->assertOk()
            ->assertSee(__('awsbilling.fee_preview_on'))
            ->assertSee('Rp 90.000')
            ->assertDontSee('Rp 190.000');

        $this->assertSame(2, ServiceCharge::count());
    }

    public function test_apply_saves_latest_rate_from_month_start_only(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.aws-accounts.billing.fee-latest', $this->account), ['month' => $this->month->format('Y-m')])
            ->assertRedirect();

        $end = $this->month->copy()->endOfMonth()->format('Y-m-d');
        $prevEnd = $this->month->copy()->subMonth()->endOfMonth()->format('Y-m-d');

        $this->assertSame(9.0, ServiceCharge::ruleFor($this->account->id, $this->kai->id, $end)->percent);
        $this->assertSame(19.0, ServiceCharge::ruleFor($this->account->id, $this->kai->id, $prevEnd)->percent);

        $this->page()->assertOk()->assertDontSee(__('awsbilling.fee_stale_title'))->assertSee('Rp 90.000');
    }

    public function test_company_override_switched_to_inherit_is_applied(): void
    {
        // Akun 9% sepanjang waktu, KAI sempat punya tarif khusus 19% lalu "ikut akun".
        ServiceCharge::query()->delete();
        $this->rate(null, 9, $this->month->copy()->subYear());
        $this->rate($this->kai->id, 19, $this->month->copy()->subMonth());
        $this->rate($this->kai->id, null, now()->wib()->startOfDay());

        $this->page()->assertSee(__('awsbilling.fee_stale_title'))->assertSee('PT KAI');

        $this->actingAs($this->admin())
            ->post(route('admin.aws-accounts.billing.fee-latest', $this->account), ['month' => $this->month->format('Y-m')]);

        $end = $this->month->copy()->endOfMonth()->format('Y-m-d');
        $this->assertSame(9.0, ServiceCharge::ruleFor($this->account->id, $this->kai->id, $end)->percent);
        $this->assertSame(19.0, ServiceCharge::ruleFor($this->account->id, $this->kai->id, $this->month->copy()->subMonth()->endOfMonth()->format('Y-m-d'))->percent);
    }

    public function test_public_link_never_shows_notice(): void
    {
        $token = \App\Models\AwsBillingShare::newToken();
        \App\Models\AwsBillingShare::create(['aws_account_id' => $this->account->id, 'token' => $token, 'is_active' => true]);

        $this->get(route('aws-billing.public', ['token' => $token, 'month' => $this->month->format('Y-m'), 'fee' => 'latest']))
            ->assertOk()
            ->assertDontSee(__('awsbilling.fee_stale_title'))
            ->assertDontSee(__('awsbilling.fee_preview_on'))
            ->assertSee('Rp 190.000');
    }
}
