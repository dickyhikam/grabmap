<?php

namespace Tests\Feature;

use App\Models\AwsAccount;
use App\Models\AwsBillingShare;
use App\Models\AwsInvoiceSummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Status tagihan resmi AWS (Invoicing API) di halaman Tagihan per akun. */
class AwsBillStatusTest extends TestCase
{
    use RefreshDatabase;

    private AwsAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->account = AwsAccount::create([
            'name' => 'Akun Uji', 'access_key_id' => 'AKIAUJI', 'secret_access_key' => 'secret',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true,
        ]);
    }

    private function page(Carbon $month, bool $public = false)
    {
        if ($public) {
            $token = AwsBillingShare::newToken();
            AwsBillingShare::create(['aws_account_id' => $this->account->id, 'token' => $token, 'is_active' => true]);

            return $this->get(route('aws-billing.public', ['token' => $token, 'month' => $month->format('Y-m'), 'lang' => 'id']));
        }

        $admin = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->value('id'),
            'is_active' => true, 'email_verified_at' => now(),
        ]);

        return $this->actingAs($admin)->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing', [$this->account, 'month' => $month->format('Y-m')]));
    }

    public function test_issued_invoice_is_shown_and_prefills_form(): void
    {
        $month = now()->utc()->startOfMonth()->subMonthsNoOverflow(2);
        $this->account->forceFill(['invoices_synced_at' => now()])->save();
        AwsInvoiceSummary::create([
            'aws_account_id' => $this->account->id, 'invoice_id' => 'INV-ID-123', 'billing_month' => $month->format('Y-m-d'),
            'invoice_type' => 'INVOICE', 'issued_at' => $month->copy()->addMonth()->addDays(2), 'due_at' => now()->addDays(5),
            'base_amount' => 790.02, 'base_currency' => 'USD', 'tax_currency_amount' => 14364343, 'tax_currency' => 'IDR',
            'exchange_rate' => 18182.24725,
        ]);

        $this->page($month)->assertOk()
            ->assertSee('Status tagihan AWS')->assertSee('Issued')->assertSee('INV-ID-123')
            ->assertSee('value="790.02"', false)->assertSee('value="14.364.343"', false)
            ->assertSee(route('admin.aws-accounts.billing.invoice-pdf', [$this->account, 'INV-ID-123']), false);

        // Tanpa invoice manual, angka resmi AWS yang dipakai: IDR persis dan kurs AWS.
        $this->page($month)->assertSee('Rp 18.182,25')->assertSee('dari invoice resmi AWS')->assertSee('Rp 14.364.343');

        // Link publik tidak membawa tombol unduh PDF.
        $this->page($month, public: true)->assertOk()->assertSee('INV-ID-123')->assertDontSee('/pdf', false);
    }

    public function test_pdf_route_rejects_invoice_of_another_account(): void
    {
        $other = AwsAccount::create(['name' => 'Lain', 'access_key_id' => 'AKIALAIN', 'secret_access_key' => 'x', 'region' => 'ap-southeast-1', 'is_active' => true]);
        AwsInvoiceSummary::create(['aws_account_id' => $other->id, 'invoice_id' => 'INV-LAIN', 'billing_month' => '2026-09-01']);
        $admin = User::factory()->create(['role_id' => \App\Models\Role::where('slug', 'admin')->value('id'), 'is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('admin.aws-accounts.billing.invoice-pdf', [$this->account, 'INV-LAIN']))->assertNotFound();
    }

    public function test_missing_permission_shows_hint_to_admin_only(): void
    {
        $month = now()->utc()->startOfMonth();
        $this->account->forceFill(['invoices_error' => 'access_denied'])->save();

        $this->page($month)->assertOk()->assertSee('Izin belum ada')->assertSee('invoicing:ListInvoiceSummaries');
        $this->page($month, public: true)->assertOk()->assertDontSee('Status tagihan AWS');
    }

    public function test_current_month_without_invoice_is_pending(): void
    {
        $this->account->forceFill(['invoices_synced_at' => now()])->save();

        $this->page(now()->utc()->startOfMonth())->assertOk()->assertSee('Pending');
    }

    public function test_store_replaces_stale_invoices_in_synced_months_only(): void
    {
        $id = $this->account->id;
        AwsInvoiceSummary::create(['aws_account_id' => $id, 'invoice_id' => 'OLD-IN', 'billing_month' => '2026-09-01']);
        AwsInvoiceSummary::create(['aws_account_id' => $id, 'invoice_id' => 'OLD-OUT', 'billing_month' => '2025-01-01']);

        AwsInvoiceSummary::store($id, [['invoice_id' => 'NEW', 'billing_month' => '2026-09-01']], ['2026-09-01', '2026-10-01']);

        $this->assertEqualsCanonicalizing(['NEW', 'OLD-OUT'], AwsInvoiceSummary::pluck('invoice_id')->all());
    }
}
