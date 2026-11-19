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
        $this->assertStringContainsString('attachment; filename="tagihan-aws-', $response->headers->get('Content-Disposition'));

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

        // Kop formal: logo + GrabMaps, subjudul, lalu judul laporan; penutup di akhir.
        // Logo lengkap GrabMaps sebagai satu gambar; sel A1 kosong supaya tidak ada
        // teks yang tertimpa (Google Sheets mengabaikan indent sel).
        $this->assertNull($sheet->getCell('A1')->getValue());
        $this->assertCount(1, $sheet->getDrawingCollection(), 'Logo di kop');
        $this->assertSame('A1', $sheet->getDrawingCollection()[0]->getCoordinates());
        $this->assertSame('GrabMaps', $sheet->getDrawingCollection()[0]->getName());
        $this->assertSame('LAPORAN TAGIHAN AWS', $sheet->getCell('A4')->getValue());
        $this->assertSame('Dokumen ini dibuat otomatis oleh sistem GrabMaps.', $sheet->getCell('A' . $sheet->getHighestRow())->getValue());
        $this->assertStringContainsString('Halaman &P dari &N', $sheet->getHeaderFooter()->getOddFooter());

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
    /** Bulan sebelumnya dengan invoice & kurs berbeda: hanya KAI yang memakai. */
    private function seedPreviousMonth(): Carbon
    {
        $prev = $this->month->copy()->subMonthNoOverflow();
        $date = $prev->copy()->day(15)->format('Y-m-d');
        ApiKeyUsageDaily::create(['aws_account_id' => $this->account->id, 'key_name' => 'kai-prod', 'usage_date' => $date, 'operation' => 'SearchText', 'request_count' => 50000, 'synced_at' => now()]);
        AwsUsageTypeDaily::create(['aws_account_id' => $this->account->id, 'usage_date' => $date, 'usage_type' => 'APS1-Places-SearchText', 'unit' => 'Count', 'quantity' => 50000, 'cost_usd' => 50, 'synced_at' => now()]);
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $prev->format('Y-m-d'), 'service' => 'Amazon Location Service', 'cost_usd' => 50, 'synced_at' => now()]);
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $prev->format('Y-m-d'), 'service' => AwsServiceCostMonthly::TAX, 'cost_usd' => 5.5, 'synced_at' => now()]);
        // Kurs 16.000 (bulan ini 17.998).
        AwsInvoice::create(['aws_account_id' => $this->account->id, 'month' => $prev->format('Y-m-d'), 'total_usd' => 55.50, 'total_idr' => 888000, 'created_by' => 'test']);

        return $prev;
    }

    public function test_multi_month_excel_has_summary_tab_and_one_tab_per_month_with_its_own_rate(): void
    {
        $prev = $this->seedPreviousMonth();
        $empty = $prev->copy()->subMonthNoOverflow();

        $response = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing.export', [$this->account,
                'month' => $this->month->format('Y-m'),
                'months' => [$this->month->format('Y-m'), $prev->format('Y-m'), $empty->format('Y-m')],
            ]));
        $response->assertOk();
        $this->assertStringContainsString('-' . $empty->format('Y-m') . '-sd-' . $this->month->format('Y-m') . '.xlsx"', $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'bill') . '.xlsx';
        file_put_contents($path, $response->getContent());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        @unlink($path);

        // Ringkasan dulu, lalu bulan berurutan dari yang terlama; bulan kosong tanpa tab.
        $this->assertSame(
            ['Ringkasan', $prev->translatedFormat('M Y'), $this->month->translatedFormat('M Y')],
            $book->getSheetNames(),
        );

        $sum = $book->getSheet(0)->toArray(null, false, false);
        $byMonth = [];
        foreach ($sum as $r) {
            $byMonth[$r[0]] = $r;
        }
        $this->assertSame('Belum ada data tagihan', $byMonth[$empty->translatedFormat('F Y')][1]);
        // Kurs tiap bulan sendiri-sendiri: 888.000 ÷ 55,50 = 16.000; 1.798.000 ÷ 99,90 = 17.998.
        $this->assertEqualsWithDelta(16000, $byMonth[$prev->translatedFormat('F Y')][2], 0.01);
        $this->assertEqualsWithDelta(17998, $byMonth[$this->month->translatedFormat('F Y')][2], 0.01);
        // Kolom: Bulan, Status, Kurs, Asal kurs, USD, IDR, …
        $this->assertEqualsWithDelta(888000, $byMonth[$prev->translatedFormat('F Y')][5], 0.5);
        $this->assertEqualsWithDelta(1798000 + 888000, $byMonth['TOTAL'][5], 0.5);
        $this->assertEqualsWithDelta(99.90 + 55.50, $byMonth['TOTAL'][4], 0.001);

        // Tab bulan sebelumnya memakai kursnya sendiri.
        $prevRows = $book->getSheet(1)->toArray(null, false, false);
        $rate = collect($prevRows)->first(fn ($r) => $r[0] === 'Kurs (IDR per 1 USD)');
        $this->assertEqualsWithDelta(16000, $rate[1], 0.01);
    }

    public function test_multi_month_csv_keeps_selected_company_and_stacks_months(): void
    {
        $prev = $this->seedPreviousMonth();

        $rows = $this->rows($this->csv([
            'months' => [$prev->format('Y-m'), $this->month->format('Y-m')],
            'co' => [$this->kfc->id],
        ]));
        $flat = implode("\n", array_map(fn ($r) => implode('|', $r), $rows));

        $this->assertStringContainsString('RINGKASAN PER BULAN', $flat);
        $this->assertSame(2, substr_count($flat, 'Bulan tagihan|'), 'Satu bagian per bulan');
        $this->assertStringNotContainsString('PT KAI', $flat, 'Perusahaan yang tidak dipilih tidak muncul di bulan mana pun');

        // KFC tidak memakai apa pun di bulan sebelumnya: baris bulan itu nol.
        $prevRow = $this->rowStarting($rows, $prev->translatedFormat('F Y'));
        $this->assertSame('0,00', $prevRow[4]);
    }

    public function test_months_outside_the_page_range_are_ignored(): void
    {
        $rows = $this->rows($this->csv(['months' => ['2001-01', 'xx']]));
        $flat = implode("\n", array_map(fn ($r) => implode('|', $r), $rows));

        $this->assertStringNotContainsString('RINGKASAN PER BULAN', $flat);
        $this->assertSame('TOTAL', $this->rowStarting($rows, 'TOTAL')[0]);
    }
    public function test_unticked_fee_or_vat_is_left_out_of_the_calculation(): void
    {
        // Tanpa fee: Invoice = Billing AWS, VAT = 11% × 1.798.000 = 197.780, Total = 1.995.780.
        $rows = $this->rows($this->csv(['cols' => ['usd', 'aws', 'invoice', 'vat', 'total']]));
        $this->assertSame(['TOTAL', '', '', '100,00', '99,90', '1798000', '1798000', '197780', '1995780'], $this->rowStarting($rows, 'TOTAL'));
        $this->assertNull($this->rowStarting($rows, 'Management fee'), 'Fee yang tidak dicentang tidak disebut');
        $this->assertSame('Billing AWS (IDR) + VAT', $this->rowStarting($rows, 'Total invoice')[1]);

        // Baris perusahaan menjumlah ke total (selisih pembulatan VAT maks. Rp 1 per baris).
        $co = array_values(array_filter($rows, fn ($r) => ($r[0] ?? '') === 'Perusahaan' && isset($r[8])));
        $this->assertCount(2, $co);
        $this->assertEqualsWithDelta(1995780, array_sum(array_map(fn ($r) => (int) $r[8], $co)), 2);
        foreach ($co as $r) {
            $this->assertSame((int) round((int) $r[5] * 0.11), (int) $r[7], 'VAT = 11% × Billing AWS tiap perusahaan');
        }

        // Tanpa VAT: Total = Billing AWS + fee.
        $rows = $this->rows($this->csv(['cols' => ['usd', 'aws', 'fee', 'invoice', 'total']]));
        $this->assertSame(['TOTAL', '', '', '100,00', '9,00', '99,90', '1798000', '161820', '1959820', '1959820'], $this->rowStarting($rows, 'TOTAL'));
        $this->assertNull($this->rowStarting($rows, 'VAT'));
    }
    public function test_client_version_hides_account_level_numbers(): void
    {
        $response = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing.export', [$this->account,
                'month' => $this->month->format('Y-m'), 'type' => 'csv', 'pretty' => '0',
                'co' => [$this->kfc->id], 'client' => '1', 'cols' => ['usd', 'aws', 'vat', 'total'],
            ]));
        $response->assertOk();
        $this->assertStringContainsString('filename="tagihan-aws-kfcku-', $response->headers->get('Content-Disposition'));

        $rows = $this->rows($response->getContent());
        $flat = implode("\n", array_map(fn ($r) => implode('|', $r), $rows));

        // Tanpa total invoice akun, tanpa porsi akun, tanpa perusahaan lain / catatan internal.
        $this->assertSame('Kurs pada invoice AWS ' . $this->month->translatedFormat('F Y'), $this->rowStarting($rows, 'Asal kurs')[1]);
        $this->assertStringNotContainsString('1.798.000', $flat);
        $this->assertStringNotContainsString('Porsi (%)', $flat);
        $this->assertStringNotContainsString('PT KAI', $flat);
        $this->assertStringNotContainsString('perusahaan terpilih', $flat);
        $this->assertStringNotContainsString('lembar invoice', $flat);
        $this->assertNull($this->rowStarting($rows, 'Management fee'));
        $this->assertNull($this->rowStarting($rows, 'Akun AWS'), 'Nama akun AWS internal');
        $this->assertStringNotContainsString('Akun Tim Alfa', $flat);

        // Baris TOTAL: tanpa kolom porsi, VAT dari Billing AWS KFC saja.
        $total = $this->rowStarting($rows, 'TOTAL');
        $this->assertCount(7, $total);
        $this->assertSame((int) round((int) $total[4] * 0.11), (int) $total[5]);
    }
    public function test_client_version_period_title_has_no_account_share(): void
    {
        $response = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing.export', [$this->account,
                'month' => $this->month->format('Y-m'), 'type' => 'csv', 'pretty' => '0',
                'co' => [$this->kfc->id], 'client' => '1', 'per' => ['1'],
            ]));
        $flat = implode("\n", array_map(fn ($r) => implode('|', $r), $this->rows($response->getContent())));

        $this->assertMatchesRegularExpression('/▶ PERIODE 11–\d+ \S+ \d{4}\n/u', $flat);
        $this->assertStringNotContainsString('dari sebulan', $flat);
    }
    /**
     * Audit menyeluruh versi klien: skenario terberat (banyak bulan, per operasi,
     * semua periode & kolom, bulan lain di mana klien satu-satunya pemakai),
     * lalu SETIAP sel di SETIAP tab diperiksa — Excel maupun CSV.
     */
    public function test_client_file_contains_nothing_about_the_account_or_other_companies(): void
    {
        $prev = $this->seedPreviousMonth(); // bulan lalu: hanya KAI yang memakai
        $empty = $prev->copy()->subMonthNoOverflow();
        $query = [
            'month'  => $this->month->format('Y-m'),
            'months' => [$empty->format('Y-m'), $prev->format('Y-m'), $this->month->format('Y-m')],
            'co'     => [$this->kai->id], 'client' => '1', 'level' => 'op', 'per' => ['month', '0', '1'],
            'cols'   => ['usd', 'aws', 'fee', 'invoice', 'vat', 'total'],
        ];

        $forbiddenText = [
            'Akun Tim Alfa', 'Akun AWS', 'KFCKU', 'kfc-web', 'GetTile',          // akun & perusahaan/key lain
            'Porsi', 'terpilih', 'dari sebulan', 'lembar invoice',                // porsi akun & catatan internal
            'Invoice AWS:', '1.798.000', '99.90', '99,90',                       // total invoice akun
            'DARI PEMAKAIAN', 'Subtotal pemakaian', 'service lain', 'Total = Billing AWS', // jembatan tingkat akun
            'Tidak dihitung',
        ];
        // Total akun bulan ini (KAI bukan satu-satunya pemakai, jadi angka ini bukan miliknya).
        $forbiddenNumbers = [99.90, 1798000, 161820, 1959820, 215580, 2175400];

        // ---- Excel: semua tab, semua sel, plus properti file.
        $res = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing.export', [$this->account] + $query + ['type' => 'xlsx']));
        $res->assertOk();
        $this->assertStringContainsString('filename="tagihan-aws-pt-kai-', $res->headers->get('Content-Disposition'));
        $path = tempnam(sys_get_temp_dir(), 'bill') . '.xlsx';
        file_put_contents($path, $res->getContent());
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        @unlink($path);

        $this->assertStringNotContainsString('Akun Tim Alfa', (string) $book->getProperties()->getTitle());
        $this->assertSame(['Ringkasan', $prev->translatedFormat('M Y'), $this->month->translatedFormat('M Y')], $book->getSheetNames());

        foreach ($book->getAllSheets() as $sheet) {
            $hasCompany = false;
            foreach ($sheet->toArray(null, false, false) as $r => $row) {
                foreach ($row as $c => $v) {
                    if ($v === null || $v === '') {
                        continue;
                    }
                    $where = $sheet->getTitle() . '!' . ($c + 1) . ':' . ($r + 1);
                    if (is_numeric($v)) {
                        foreach ($forbiddenNumbers as $n) {
                            $this->assertFalse(abs((float) $v - $n) < 0.005, "Angka akun $n bocor di $where");
                        }
                    } else {
                        foreach ($forbiddenText as $bad) {
                            $this->assertStringNotContainsString($bad, (string) $v, "\"$bad\" bocor di $where");
                        }
                    }
                }
                $hasCompany = $hasCompany || (($row[0] ?? '') === 'Perusahaan' && ($row[1] ?? '') === 'PT KAI');
            }
            $this->assertTrue($hasCompany, 'Kepala tiap tab menyebut perusahaan klien: ' . $sheet->getTitle());
        }

        // ---- CSV dengan angka berformat: semua teks diperiksa sekaligus.
        $csv = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing.export', [$this->account] + $query + ['type' => 'csv']))
            ->assertOk()->getContent();
        foreach ([...$forbiddenText, 'Rp 1.959.820', 'Rp 2.175.400', 'Rp 215.580', 'Rp 161.820'] as $bad) {
            $this->assertStringNotContainsString($bad, $csv, "\"$bad\" bocor di CSV");
        }
        $this->assertStringContainsString('PT KAI', $csv);
    }

    public function test_client_month_without_usage_still_names_the_client(): void
    {
        $prev = $this->seedPreviousMonth(); // KFC tidak memakai apa pun bulan lalu
        $rows = $this->rows($this->csv([
            'months' => [$prev->format('Y-m'), $this->month->format('Y-m')],
            'co' => [$this->kfc->id], 'client' => '1',
        ]));
        $named = array_filter($rows, fn ($r) => count($r) === 2 && $r[0] === 'Perusahaan' && $r[1] === 'KFCKU');
        $this->assertCount(3, $named, 'Ringkasan + dua tab bulan');
    }
    public function test_running_month_is_cut_at_the_end_of_yesterday(): void
    {
        // "Hari ini" = tanggal 18 bulan berjalan, pukul 08.00 UTC.
        $cur = now()->utc()->startOfMonth();
        Carbon::setTestNow($cur->copy()->day(18)->setTime(8, 0));
        $add = function (int $day, string $key, string $op, int $n, float $cost) use ($cur) {
            $date = $cur->copy()->day($day)->format('Y-m-d');
            ApiKeyUsageDaily::create(['aws_account_id' => $this->account->id, 'key_name' => $key, 'usage_date' => $date, 'operation' => $op, 'request_count' => $n, 'synced_at' => now()]);
            AwsUsageTypeDaily::create(['aws_account_id' => $this->account->id, 'usage_date' => $date, 'usage_type' => 'APS1-' . $op, 'unit' => 'Count', 'quantity' => $n, 'cost_usd' => $cost, 'synced_at' => now()]);
        };
        $add(5, 'kai-prod', 'SearchText', 10000, 10);   // kemarin dan sebelumnya: dihitung
        $add(18, 'kfc-web', 'GetTile', 5000, 5);        // hari ini, belum lengkap: tidak dihitung
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $cur->format('Y-m-d'), 'service' => 'Amazon Location Service', 'cost_usd' => 15, 'synced_at' => now()]);
        AwsServiceCostMonthly::create(['aws_account_id' => $this->account->id, 'month' => $cur->format('Y-m-d'), 'service' => AwsServiceCostMonthly::TAX, 'cost_usd' => 1.65, 'synced_at' => now()]);
        \App\Models\ExchangeRate::create(['rate' => 16000, 'rate_date' => $cur->format('Y-m-d'), 'source' => 'test', 'is_active' => false, 'created_by' => 'test']);

        try {
            $rows = $this->rows($this->csv(['month' => $cur->format('Y-m'), 'cols' => ['usd', 'aws']]));
            // Modal di halaman memberi tahu potongannya dan memakai angka yang sama.
            $this->get(route('admin.aws-accounts.billing', [$this->account, 'month' => $cur->format('Y-m')]))
                ->assertOk()
                ->assertSee('Bulan berjalan: file dihitung sampai akhir ' . $cur->copy()->day(17)->translatedFormat('j M Y'), false)
                ->assertSee('BULAN BERJALAN S.D. KEMARIN', false);
        } finally {
            Carbon::setTestNow();
        }
        $flat = implode("\n", array_map(fn ($r) => implode('|', $r), $rows));
        $yesterday = $cur->copy()->day(17);

        $range = '1–' . $yesterday->translatedFormat('j M Y');
        $this->assertSame('Bulan berjalan, sampai kemarin (' . $range . ')', $this->rowStarting($rows, 'Periode di file ini')[1]);
        $this->assertStringContainsString('▶ BULAN BERJALAN S.D. KEMARIN · ' . $range, $flat);
        $this->assertStringStartsWith('Bulan berjalan — dihitung sampai akhir ' . $yesterday->translatedFormat('j M Y') . ' (UTC, = 18 ', $this->rowStarting($rows, 'Status data')[1]);
        $this->assertStringNotContainsString('Sebulan penuh', $flat);

        // Pemakaian hari ini (KFC) tidak ikut; porsi 1–17 = 10 ÷ 15 dari $16,65.
        $this->assertStringNotContainsString('KFCKU', $flat);
        $total = $this->rowStarting($rows, 'TOTAL');
        $this->assertSame(['TOTAL', '', '', '100,00', '11,10'], array_slice($total, 0, 5));
        $rate = (float) str_replace(',', '.', $this->rowStarting($rows, 'Kurs (IDR per 1 USD)')[1]);
        $this->assertEqualsWithDelta(round(16.65 * $rate) * 10 / 15, (float) $total[5], 1);
    }

    public function test_closed_month_is_not_cut(): void
    {
        $rows = $this->rows($this->csv());
        $this->assertStringStartsWith('Sebulan penuh', $this->rowStarting($rows, 'Periode di file ini')[1]);
        $this->assertStringStartsNotWith('Bulan berjalan', $this->rowStarting($rows, 'Status data')[1]);
    }
    public function test_split_periods_of_another_month_come_along_when_asked(): void
    {
        // Halaman dibuka di bulan lalu (tidak dipecah); bulan ini dipecah di tanggal 10.
        $prev = $this->seedPreviousMonth();
        $base = ['month' => $prev->format('Y-m'), 'months' => [$prev->format('Y-m'), $this->month->format('Y-m')]];

        $flat = fn (string $csv) => implode("\n", array_map(fn ($r) => implode('|', $r), $this->rows($csv)));

        $with = $flat($this->csv($base + ['per' => ['month', 'split']]));
        $this->assertStringContainsString('▶ PERIODE 1–10 ' . $this->month->translatedFormat('M Y'), $with);
        $this->assertStringContainsString('▶ PERIODE 11–', $with);
        $this->assertSame(2, substr_count($with, '▶ SEBULAN PENUH'));

        $without = $flat($this->csv($base + ['per' => ['month']]));
        $this->assertStringNotContainsString('▶ PERIODE', $without);

        // Hanya periode, tanpa sebulan penuh: bulan tanpa pecahan tetap sebulan penuh.
        $only = $flat($this->csv($base + ['per' => ['split']]));
        $this->assertSame(1, substr_count($only, '▶ SEBULAN PENUH'));
        $this->assertStringContainsString('▶ PERIODE 1–10', $only);
    }

    public function test_export_modal_knows_each_month_rate_and_split(): void
    {
        $prev = $this->seedPreviousMonth();
        $html = $this->actingAs($this->admin())->withSession(['admin_locale' => 'id'])
            ->get(route('admin.aws-accounts.billing', [$this->account, 'month' => $prev->format('Y-m')]))
            ->assertOk()->getContent();

        preg_match('#<script type="application/json" id="exData">(.*?)</script>#s', $html, $m);
        $ex = json_decode($m[1], true);
        $this->assertSame($prev->format('Y-m'), $ex['base']);
        $this->assertEqualsWithDelta(16000, $ex['months'][$prev->format('Y-m')]['rate'], 0.01);
        $this->assertEqualsWithDelta(17998, $ex['months'][$this->month->format('Y-m')]['rate'], 0.01);
        $this->assertSame('invoice manual', $ex['months'][$this->month->format('Y-m')]['srcLabel']);
        $this->assertCount(2, $ex['months'][$this->month->format('Y-m')]['periods'], 'Bulan ini dipecah di tanggal 10');
        $this->assertSame([], $ex['months'][$prev->format('Y-m')]['periods']);
        // Chip periode bulan lain ada (disembunyikan JS sampai bulan itu dipilih).
        $this->assertStringContainsString('value="split"', $html);
    }
}
