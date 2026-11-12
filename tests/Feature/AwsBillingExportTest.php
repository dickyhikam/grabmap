<?php

namespace Tests\Feature;

use App\Models\ApiKeyUsageDaily;
use App\Models\AwsAccount;
use App\Models\AwsBillingShare;
use App\Models\AwsBillingSplit;
use App\Models\AwsInvoice;
use App\Models\AwsServiceCostMonthly;
use App\Models\AwsUsageTypeDaily;
use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\ServiceCharge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwsBillingExportTest extends TestCase
{
    use RefreshDatabase;

    private AwsAccount $account;
    private Company $kai;
    private Company $kfc;
    private Carbon $month;
    private string $token;

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
        $this->kfc = Company::create(['name' => 'KFCKU', 'slug' => 'kfcku', 'is_active' => true, 'aws_account_id' => $this->account->id]);
        CompanyApiKey::create(['company_id' => $this->kai->id, 'aws_account_id' => $this->account->id, 'key_name' => 'kai-prod', 'is_primary' => true]);
        CompanyApiKey::create(['company_id' => $this->kfc->id, 'aws_account_id' => $this->account->id, 'key_name' => 'kfc-web', 'is_primary' => true]);

        // Pemakaian di dua sisi tanggal potong (5 & 20).
        foreach ([5 => [30000, 10000], 20 => [60000, 20000]] as $day => [$kai, $kfc]) {
            $date = $this->month->copy()->day($day)->format('Y-m-d');
            ApiKeyUsageDaily::create(['aws_account_id' => $this->account->id, 'key_name' => 'kai-prod', 'usage_date' => $date, 'operation' => 'SearchText', 'request_count' => $kai, 'synced_at' => now()]);
            ApiKeyUsageDaily::create(['aws_account_id' => $this->account->id, 'key_name' => 'kfc-web', 'usage_date' => $date, 'operation' => 'GetTile', 'request_count' => $kfc, 'synced_at' => now()]);
            AwsUsageTypeDaily::create(['aws_account_id' => $this->account->id, 'usage_date' => $date, 'usage_type' => 'APS1-Places-SearchText', 'unit' => 'Count', 'quantity' => $kai, 'cost_usd' => $day === 5 ? 30 : 60, 'synced_at' => now()]);
        }

        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $m, 'service' => 'Amazon Location Service', 'cost_usd' => 90, 'synced_at' => now()]);
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $m, 'service' => AwsServiceCostMonthly::TAX, 'cost_usd' => 9.9, 'synced_at' => now()]);
        AwsInvoice::create(['aws_account_id' => $this->account->id, 'month' => $m, 'total_usd' => 99.90, 'total_idr' => 1798000, 'created_by' => 'test']);
        ServiceCharge::create(['aws_account_id' => $this->account->id, 'company_id' => null, 'percent' => 9, 'monthly_min_idr' => 0, 'effective_from' => $this->month->copy()->subYear()->format('Y-m-d')]);
        AwsBillingSplit::create(['aws_account_id' => $this->account->id, 'month' => $m, 'cuts' => [10], 'created_by' => 'test']);

        $this->token = AwsBillingShare::newToken();
        AwsBillingShare::create(['aws_account_id' => $this->account->id, 'token' => $this->token, 'is_active' => true]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'           => \App\Models\Role::where('slug', 'admin')->value('id'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    private function csv(array $query = [], bool $public = false): string
    {
        // Angka polos (tanpa Rp/pemisah ribuan) kecuali tes memintanya.
        $query = $query + ['month' => $this->month->format('Y-m'), 'pretty' => '0', 'type' => 'csv'];
        $response = $public
            ? $this->get(route('aws-billing.public.export', ['token' => $this->token] + $query + ['lang' => 'id']))
            : $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])->get(route('admin.aws-accounts.billing.export', [$this->account] + $query));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="tagihan-aws-akun-tim-alfa-', $response->headers->get('Content-Disposition'));

        $body = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'BOM supaya Excel membaca UTF-8');

        return $body;
    }

    /** @return array<int, array<int, string>> */
    private function rows(string $csv, string $sep = ';'): array
    {
        $lines = preg_split('/\r\n/', trim(substr($csv, 3)));

        return array_map(fn ($l) => str_getcsv($l, $sep, '"', ''), $lines);
    }

    private function rowStarting(array $rows, string $first): ?array
    {
        foreach ($rows as $r) {
            if (($r[0] ?? null) === $first) {
                return $r;
            }
        }

        return null;
    }

    public function test_default_export_states_rate_and_lists_every_company(): void
    {
        $rows = $this->rows($this->csv());

        // Kurs tertulis sebagai angka dan sebagai teks, lengkap dengan asal & rumusnya.
        $this->assertSame('17998,00', $this->rowStarting($rows, 'Kurs (IDR per 1 USD)')[1]);
        $this->assertSame('1 USD = Rp 17.998,00', $this->rowStarting($rows, 'Kurs (dibaca)')[1]);
        $this->assertSame('Invoice AWS: Rp 1.798.000 ÷ $99.90', $this->rowStarting($rows, 'Asal kurs')[1]);
        $this->assertSame('Billing AWS (IDR) = Billing AWS (USD) × kurs', $this->rowStarting($rows, 'Rumus Rupiah')[1]);
        $this->assertSame('9% × Billing AWS (IDR)', $this->rowStarting($rows, 'Management fee')[1]);

        $csv = implode("\n", array_map(fn ($r) => implode('|', $r), $rows));
        $this->assertStringContainsString('PT KAI', $csv);
        $this->assertStringContainsString('KFCKU', $csv);
        $this->assertStringContainsString('kai-prod', $csv, 'Bawaan menyertakan baris API key');

        // Baris TOTAL sebulan = angka invoice.
        $total = $this->rowStarting($rows, 'TOTAL');
        $this->assertSame(['TOTAL', '', '', '100,00', '9,00', '99,90', '1798000', '161820', '1959820', '215580', '2175400'], $total);
    }

    public function test_account_without_fee_and_vat_bills_only_the_aws_amount(): void
    {
        $this->account->forceFill(['bill_management_fee' => false, 'bill_vat' => false])->save();

        // Export: kolom fee/invoice/VAT/total hilang, baris tarif fee & VAT tidak ditulis.
        $rows = $this->rows($this->csv());
        $this->assertNull($this->rowStarting($rows, 'Management fee'));
        $this->assertSame(['TOTAL', '', '', '100,00', '99,90', '1798000'], $this->rowStarting($rows, 'TOTAL'));

        // Halaman: total ke klien = tagihan AWS, tanpa langkah fee & VAT.
        $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing', [$this->account, 'month' => $this->month->format('Y-m')]))
            ->assertOk()
            ->assertSee('Tagihan AWS, tanpa management fee dan VAT')
            ->assertSee('Rp 1.798.000')
            ->assertDontSee('data-f="fee"', false)
            ->assertDontSee('data-f="vat"', false);
    }

    public function test_vat_only_account_skips_fee(): void
    {
        $this->account->forceFill(['bill_management_fee' => false])->save();

        // 1.798.000 + VAT 11% (197.780) = 1.995.780
        $total = $this->rowStarting($this->rows($this->csv()), 'TOTAL');
        $this->assertSame(['TOTAL', '', '', '100,00', '99,90', '1798000', '197780', '1995780'], $total);
    }

    public function test_company_filter_keeps_only_selected_and_totals_them(): void
    {
        $csv = $this->csv(['co' => [$this->kai->id]]);

        $this->assertStringContainsString('PT KAI', $csv);
        $this->assertStringNotContainsString('KFCKU', $csv);
        $this->assertStringContainsString('TOTAL perusahaan terpilih', $csv);
        $this->assertStringNotContainsString(';TOTAL;', ";$csv");
    }

    public function test_period_and_column_choices_shape_the_file(): void
    {
        $rows = $this->rows($this->csv(['per' => ['0', '1'], 'cols' => ['fee', 'vat'], 'level' => 'company']));
        $flat = array_map(fn ($r) => $r[0] ?? '', $rows);

        $this->assertNotEmpty(preg_grep('/^▶ PERIODE 1–10 /', $flat));
        $this->assertNotEmpty(preg_grep('/^▶ PERIODE 11–/', $flat));
        $this->assertEmpty(preg_grep('/^▶ SEBULAN PENUH/', $flat));

        $head = $this->rowStarting($rows, 'Baris');
        $this->assertSame(['Baris', 'Perusahaan', 'API key', 'Porsi (%)', 'Fee (%)', 'Management fee 9% (IDR)', 'VAT 11% (IDR)'], $head);
        $this->assertNull($this->rowStarting($rows, 'API key'), 'Tingkat "per perusahaan" tanpa baris API key');
    }

    public function test_operation_rows_sit_under_their_api_key_and_bridge_to_aws_bill(): void
    {
        $rows = $this->rows($this->csv(['level' => 'op']));

        // Urutan: perusahaan → API key → operasinya, dalam satu tabel.
        $i = array_search(['API key', 'PT KAI', 'kai-prod'], array_map(fn ($r) => array_slice($r, 0, 3), $rows), true);
        $this->assertNotFalse($i);
        $key = $rows[$i];
        $op = $rows[$i + 1];
        $this->assertSame(['Operasi', 'PT KAI', 'kai-prod', 'Places', 'SearchText', '90000'], array_slice($op, 0, 6));
        // Satu-satunya operasi key itu: semua kolomnya = baris API key.
        $this->assertSame(array_slice($key, 6), array_slice($op, 6));

        $this->assertSame(['Total = Billing AWS', '99,90', '1798000'], $this->rowStarting($rows, 'Total = Billing AWS'));
    }

    public function test_pretty_numbers_show_rupiah_dollars_and_thousands(): void
    {
        $rows = $this->rows($this->csv(['pretty' => '1', 'level' => 'op']));

        $this->assertSame('Rp 17.998,00', $this->rowStarting($rows, 'Kurs (IDR per 1 USD)')[1]);
        $total = $this->rowStarting($rows, 'TOTAL');
        $this->assertSame(['TOTAL', '', '', '', '', '120.000', '100,00', '9,00', '$99,90', 'Rp 1.798.000', 'Rp 161.820', 'Rp 1.959.820', 'Rp 215.580', 'Rp 2.175.400'], $total);

        // Internasional: koma ribuan, titik desimal; sel berisi koma dikutip.
        $rows = $this->rows($this->csv(['pretty' => '1', 'fmt' => 'intl']), ',');
        $this->assertSame(['$99.90', 'Rp 1,798,000'], array_slice($this->rowStarting($rows, 'TOTAL'), 5, 2));
    }

    public function test_no_cell_is_read_as_a_spreadsheet_formula(): void
    {
        // Nama dari database yang diawali = tidak boleh jadi rumus (CSV injection).
        $this->kfc->update(['name' => '=HYPERLINK("http://evil","klik")']);

        $csv = $this->csv(['level' => 'op']);
        $rows = $this->rows($csv);

        foreach ($rows as $r) {
            foreach ($r as $cell) {
                $this->assertDoesNotMatchRegularExpression('/^[=+@]/', (string) $cell, "Sel dibaca sebagai rumus: $cell");
            }
        }
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertSame('-12,50', \App\Services\BillingExport::safeCell('-12,50'));
        $this->assertSame('-Rp 1.000', \App\Services\BillingExport::safeCell('-Rp 1.000'));
        $this->assertSame("'-cmd", \App\Services\BillingExport::safeCell('-cmd'));
    }

    public function test_excel_is_the_default_and_keeps_real_numbers(): void
    {
        $this->kfc->update(['name' => '=HYPERLINK("http://evil","klik")']);

        $response = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing.export', [$this->account, 'month' => $this->month->format('Y-m'), 'level' => 'op']));
        $response->assertOk();
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringEndsWith('.xlsx"', $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'bill') . '.xlsx';
        file_put_contents($path, $response->getContent());
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        $this->assertSame('LAPORAN TAGIHAN AWS', $sheet->getCell('A1')->getValue());

        // Baris TOTAL: angka asli (bisa dijumlah) dengan format Rupiah.
        $totalRow = null;
        foreach ($sheet->getRowIterator() as $row) {
            if ($sheet->getCell('A' . $row->getRowIndex())->getValue() === 'TOTAL') {
                $totalRow = $row->getRowIndex();
                break;
            }
        }
        $this->assertNotNull($totalRow);
        $idr = $sheet->getCell('J' . $totalRow);
        $this->assertSame(1798000.0, (float) $idr->getValue());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC, $idr->getDataType());
        $this->assertSame('"Rp "#,##0', $sheet->getStyle('J' . $totalRow)->getNumberFormat()->getFormatCode());
        $this->assertTrue($sheet->getStyle('A' . $totalRow)->getFont()->getBold());

        // Teks mirip rumus tetap teks, tanpa awalan ' yang terlihat.
        $found = false;
        foreach ($sheet->getRowIterator() as $row) {
            $cell = $sheet->getCell('B' . $row->getRowIndex());
            if (str_starts_with((string) $cell->getValue(), '=HYPERLINK')) {
                $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $cell->getDataType());
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function test_international_format_uses_comma_separator_and_dot_decimal(): void
    {
        $rows = $this->rows($this->csv(['fmt' => 'intl']), ',');

        $this->assertSame('17998.00', $this->rowStarting($rows, 'Kurs (IDR per 1 USD)')[1]);
    }

    public function test_simulated_rate_is_applied_and_labelled(): void
    {
        $rows = $this->rows($this->csv(['rate' => '20000']));

        $this->assertSame('20000,00', $this->rowStarting($rows, 'Kurs (IDR per 1 USD)')[1]);
        $this->assertStringStartsWith('Simulasi dari halaman', $this->rowStarting($rows, 'Asal kurs')[1]);
        $this->assertSame('1998000', $this->rowStarting($rows, 'TOTAL')[6]);

        // Kurs ngawur (di luar ±50%) diabaikan.
        $rows = $this->rows($this->csv(['rate' => '1']));
        $this->assertSame('17998,00', $this->rowStarting($rows, 'Kurs (IDR per 1 USD)')[1]);
    }

    public function test_public_link_exports_the_same_file_and_dies_with_the_link(): void
    {
        $this->assertStringContainsString('PT KAI', $this->csv([], true));

        AwsBillingShare::where('token', $this->token)->update(['is_active' => false]);
        $this->get(route('aws-billing.public.export', ['token' => $this->token, 'month' => $this->month->format('Y-m')]))->assertNotFound();
    }

    public function test_pages_offer_export_instead_of_copy(): void
    {
        $month = $this->month->format('Y-m');

        $this->actingAs($this->admin())->get(route('admin.aws-accounts.billing', [$this->account, 'month' => $month]))
            ->assertOk()
            ->assertSee('id="exportModal"', false)
            ->assertDontSee('data-copy-tsv', false);

        auth()->logout();
        $this->get(route('aws-billing.public', ['token' => $this->token, 'month' => $month]))
            ->assertOk()
            ->assertSee('id="exportModal"', false)
            ->assertSee(route('aws-billing.public.export', $this->token), false);
    }
}
