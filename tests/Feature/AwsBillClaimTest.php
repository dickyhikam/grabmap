<?php

namespace Tests\Feature;

use App\Models\AwsAccount;
use App\Models\AwsBillClaim;
use App\Models\AwsBillClaimActivity;
use App\Models\AwsInvoiceSummary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pelacak pengajuan penggantian tagihan AWS. */
class AwsBillClaimTest extends TestCase
{
    use RefreshDatabase;

    private AwsAccount $account;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->account = AwsAccount::create([
            'name' => 'Akun Grab', 'access_key_id' => 'AKIAGRAB', 'secret_access_key' => 'secret',
            'region' => 'ap-southeast-1', 'is_active' => true, 'is_default' => true, 'claims_enabled' => true,
        ]);
        foreach (['2026-08' => [140.08, 2520098], '2026-09' => [1.33, 23874], '2026-10' => [2.49, 45208], '2026-05' => [0, 0]] as $m => [$usd, $idr]) {
            AwsInvoiceSummary::create([
                'aws_account_id' => $this->account->id, 'invoice_id' => 'INV-' . $m, 'billing_month' => $m . '-01',
                'invoice_type' => 'INVOICE', 'base_amount' => $usd, 'base_currency' => 'USD',
                'tax_currency_amount' => $idr, 'tax_currency' => 'IDR',
            ]);
        }

        $this->admin = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', 'admin')->value('id'),
            'is_active' => true, 'email_verified_at' => now(),
        ]);
        $this->actingAs($this->admin)->withSession(['admin_locale' => 'id']);
    }

    private function claim(array $data)
    {
        return $this->post(route('admin.aws-accounts.claims.store', $this->account), $data + [
            'status' => 'submitted', 'submitted_on' => '2026-11-12',
        ]);
    }

    public function test_page_lists_months_and_hides_zero_bills(): void
    {
        $this->get(route('admin.aws-accounts.claims', $this->account))->assertOk()
            ->assertSee('INV-2026-10')->assertSee('INV-2026-08')->assertDontSee('INV-2026-05')
            ->assertSee('Rp 2.589.180'); // total belum diajukan
    }

    public function test_disabled_account_redirects(): void
    {
        $this->account->forceFill(['claims_enabled' => false])->save();

        $this->get(route('admin.aws-accounts.claims', $this->account))->assertRedirect();
        $this->claim(['months' => ['2026-10']])->assertNotFound();
    }

    public function test_claim_several_months_with_fee_and_vat(): void
    {
        $this->claim(['months' => ['2026-09', '2026-10'], 'include_fee' => '1', 'fee_percent' => '10', 'include_vat' => '1'])
            ->assertRedirect()->assertSessionHas('success');

        $c = AwsBillClaim::with('items')->sole();
        $this->assertSame('PGJ-001', $c->code());
        $this->assertCount(2, $c->items);
        // Sep: 23.874 + 2.387,4 fee + VAT round(26.261,4 × 11%) = 2.889 → 29.150,4
        // Okt: 45.208 + 4.520,8 fee + VAT round(49.728,8 × 11%) = 5.470 → 55.198,8
        $this->assertEqualsWithDelta(69082, $c->aws_idr, 0.01);
        $this->assertEqualsWithDelta(8359, $c->vat_idr, 0.01);
        $this->assertEqualsWithDelta(84349.2, $c->total_idr, 0.01);

        // Bulan yang sama tidak bisa diajukan dua kali.
        $this->claim(['months' => ['2026-10']])->assertSessionHas('error');
        $this->assertSame(1, AwsBillClaim::count());
    }

    public function test_old_months_can_be_recorded_as_already_paid(): void
    {
        $this->claim(['months' => ['2026-08'], 'status' => 'paid', 'submitted_on' => '2026-09-05', 'paid_on' => '2026-09-20'])
            ->assertSessionHas('success');

        $c = AwsBillClaim::sole();
        $this->assertTrue($c->isPaid());
        $this->assertSame('2026-09-20', $c->paid_on->format('Y-m-d'));
        $this->assertEqualsWithDelta(2520098, $c->total_idr, 0.01);
    }

    public function test_mark_paid_with_proof_then_undo_and_cancel(): void
    {
        Storage::fake('local');
        $this->claim(['months' => ['2026-10']]);
        $c = AwsBillClaim::sole();

        $this->post(route('admin.aws-accounts.claims.paid', [$this->account, $c]), [
            'paid_on' => '2026-11-20', 'proof' => UploadedFile::fake()->create('transfer.pdf', 100, 'application/pdf'),
        ])->assertSessionHas('success');
        $c->refresh();
        $this->assertTrue($c->isPaid());
        Storage::disk('local')->assertExists($c->proof_path);
        $this->get(route('admin.aws-accounts.claims.proof', [$this->account, $c]))->assertOk();

        $path = $c->proof_path;
        $this->post(route('admin.aws-accounts.claims.unpaid', [$this->account, $c]))->assertSessionHas('success');
        $this->assertFalse($c->refresh()->isPaid());
        Storage::disk('local')->assertMissing($path);

        $this->delete(route('admin.aws-accounts.claims.destroy', [$this->account, $c]))->assertSessionHas('success');
        $this->assertSame(0, AwsBillClaim::count());
        // Bulannya bebas lagi.
        $this->claim(['months' => ['2026-10']])->assertSessionHas('success');
    }

    public function test_claim_of_another_account_is_not_reachable(): void
    {
        $other = AwsAccount::create(['name' => 'Lain', 'access_key_id' => 'AKIALAIN', 'secret_access_key' => 'x', 'region' => 'ap-southeast-1', 'is_active' => true, 'claims_enabled' => true]);
        $this->claim(['months' => ['2026-10']]);
        $c = AwsBillClaim::sole();

        $this->delete(route('admin.aws-accounts.claims.destroy', [$other, $c]))->assertNotFound();
        $this->assertSame(1, AwsBillClaim::count());
    }

    public function test_paid_date_before_submit_date_is_rejected(): void
    {
        $this->claim(['months' => ['2026-10'], 'status' => 'paid', 'submitted_on' => '2026-11-10', 'paid_on' => '2026-11-01'])
            ->assertSessionHasErrors('paid_on');
        $this->assertSame(0, AwsBillClaim::count());
    }

    public function test_activity_log_records_who_did_what_even_after_cancel(): void
    {
        $this->claim(['months' => ['2026-10']]);
        $c = AwsBillClaim::sole();
        $this->post(route('admin.aws-accounts.claims.paid', [$this->account, $c]), ['paid_on' => '2026-11-20']);
        $this->post(route('admin.aws-accounts.claims.unpaid', [$this->account, $c]));
        $this->delete(route('admin.aws-accounts.claims.destroy', [$this->account, $c]));

        $log = AwsBillClaimActivity::orderBy('id')->get();
        $this->assertSame(['created', 'paid', 'unpaid', 'cancelled'], $log->pluck('action')->all());
        $this->assertSame([$this->admin->name], $log->pluck('actor')->unique()->values()->all());
        $this->assertSame('PGJ-001', $log->last()->claim_code);
        $this->assertNull($log->last()->fresh()->aws_bill_claim_id, 'Log tetap ada walau pengajuan dihapus');

        $this->get(route('admin.aws-accounts.claims', $this->account))->assertOk()
            ->assertSee('Riwayat aktivitas')->assertSee('membatalkan', false);
    }

    public function test_claim_cover_pdf_is_generated(): void
    {
        $this->claim(['months' => ['2026-09', '2026-10'], 'include_vat' => '1']);
        $c = AwsBillClaim::sole();

        $res = $this->get(route('admin.aws-accounts.claims.pdf', [$this->account, $c]))->assertOk();
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertStringContainsString('application/pdf', $res->headers->get('Content-Type'));

        $other = AwsAccount::create(['name' => 'Lain', 'access_key_id' => 'AKIALAIN', 'secret_access_key' => 'x', 'region' => 'ap-southeast-1', 'is_active' => true, 'claims_enabled' => true]);
        $this->get(route('admin.aws-accounts.claims.pdf', [$other, $c]))->assertNotFound();
        $this->get(route('admin.aws-accounts.claims.attachments', [$other, $c]))->assertNotFound();
    }
}
