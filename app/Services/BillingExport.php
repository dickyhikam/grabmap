<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export halaman Tagihan satu akun AWS (admin & link publik) ke Excel atau CSV.
 *
 * Isi dibangun sekali sebagai daftar baris bergaya (judul, info, header tabel,
 * perusahaan, API key, operasi, total), lalu ditulis ke salah satu format —
 * jadi isi kedua file selalu sama:
 *   1. Kepala: akun, bulan, periode, perusahaan, waktu dibuat.
 *   2. Kurs & tarif: kurs yang dipakai, asalnya, rumus Rupiah, fee, VAT.
 *   3. Keterangan tiap kolom.
 *   4. Satu bagian per periode: perusahaan → API key → operasi, lalu TOTAL.
 *
 * Excel: angka asli dengan format Rp / $ / ribuan (rapi dan tetap bisa dihitung),
 * teks selalu ditulis sebagai teks. CSV: angka diformat sesuai pilihan
 * (id: 1.234,56 dan ";" — intl: 1,234.56 dan ","), sel mirip rumus dinetralkan.
 */
class BillingExport
{
    public const TYPES = ['xlsx', 'csv'];
    public const COLUMNS = ['usd', 'aws', 'fee', 'invoice', 'vat', 'total'];
    public const LEVELS = ['company', 'key', 'op'];

    /** Kolom export → kunci angka di baris rincian per perusahaan. */
    private const FIELD = ['usd' => 'usd', 'aws' => 'idr', 'fee' => 'fee', 'invoice' => 'invoice', 'vat' => 'vat', 'total' => 'total'];

    private const BRAND = 'GrabMaps';
    private const GREEN = 'FF00B14F';
    private const GREEN_SOFT = 'FFE8F8EF';
    private const GREEN_TEXT = 'FF009640';
    private const SURFACE = 'FFF2F4F4';
    private const LINE = 'FFE3E7E6';
    private const MUTED = 'FF8A938F';

    /** Format angka Excel per jenis nilai (dua desimal untuk kurs). */
    private const XLSX_FORMATS = [
        'idr0'   => '"Rp "#,##0',
        'idr2'   => '"Rp "#,##0.00',
        'usd2'   => '"$"#,##0.00',
        'count0' => '#,##0',
        'plain2' => '0.00',
        'plain0' => '0',
    ];

    private string $dec;
    private string $sep;
    /** @var array<int, array{style: string, cells: array<int, string|array>}> */
    private array $out = [];

    public function __construct(private array $data, private array $opt)
    {
        $intl = $opt['format'] === 'intl';
        $this->dec = $intl ? '.' : ',';
        $this->sep = $intl ? ',' : ';';
    }

    /**
     * Pilihan dari modal Export. Nilai yang tidak dikenal dibuang; pilihan
     * kosong kembali ke bawaan, jadi file tidak pernah kosong tanpa sebab.
     */
    public static function optionsFrom(Request $request, array $data, bool $otherMonth = false): array
    {
        $ids = array_map(fn ($r) => (int) ($r['company_id'] ?? 0), $data['split']['rows']);

        $co = $request->query('co');
        $companies = is_array($co) ? array_values(array_intersect($ids, array_map('intval', $co))) : $ids;
        // Bulan lain di export banyak bulan: perusahaan terpilih bisa saja tidak
        // memakai apa pun di bulan itu, jadi daftar kosong tetap kosong.
        if (!$otherMonth) {
            $companies = $companies ?: $ids;
        }

        $periodKeys = ['month'];
        if (count($data['periods']) > 1) {
            foreach (array_keys($data['periods']) as $i) {
                $periodKeys[] = (string) $i;
            }
        }
        $per = $request->query('per');
        if ($otherMonth) {
            // Indeks periode di modal milik bulan yang sedang dibuka. Bulan lain:
            // "Sebulan penuh" kalau dicentang, ditambah semua periode pecahannya
            // kalau "Periode pecahan bulan lain" (per[]=split) dicentang.
            $per = is_array($per) ? array_map('strval', $per) : ['month'];
            $periods = in_array('month', $per, true) ? ['month'] : [];
            if (in_array('split', $per, true)) {
                $periods = array_merge($periods, array_slice($periodKeys, 1));
            }
        } else {
            $periods = is_array($per) ? array_values(array_intersect($periodKeys, array_map('strval', $per))) : ['month'];
        }
        $periods = $periods ?: ['month'];

        $level = in_array($request->query('level'), self::LEVELS, true) ? $request->query('level') : 'key';

        $available = self::columnsFor($data);
        $c = $request->query('cols');
        $cols = is_array($c) ? array_values(array_intersect($available, $c)) : $available;
        $cols = $cols ?: $available;

        return [
            'companies' => $companies,
            'all'       => !is_array($co) || count($companies) === count($ids),
            // Versi untuk klien: tanpa angka tingkat akun (total invoice AWS,
            // porsi dari akun, perusahaan lain) dan tanpa catatan internal.
            'client'    => $request->query('client') === '1',
            // Nama perusahaan yang dicentang (hanya milik akun ini), untuk kepala versi klien.
            'picked_names' => is_array($co)
                ? \App\Models\Company::whereIn('id', array_map('intval', $co))
                    ->where(fn ($q) => $q->where('aws_account_id', $data['account']->id)
                        ->orWhereHas('apiKeys', fn ($k) => $k->where('aws_account_id', $data['account']->id)))
                    ->orderBy('name')->pluck('name')->all()
                : [],
            'periods'   => $periods,
            'level'     => $level,
            'cols'      => $cols,
            'type'      => in_array($request->query('type'), self::TYPES, true) ? $request->query('type') : 'xlsx',
            'format'    => $request->query('fmt') === 'intl' ? 'intl' : 'id',
            // "Rp 826.085" / "90.000" (bawaan) atau angka polos tanpa pemisah ribuan.
            'pretty'    => $request->query('pretty', '1') !== '0',
        ];
    }

    public function type(): string
    {
        return $this->opt['type'];
    }

    public function extension(): string
    {
        return $this->opt['type'];
    }

    public function contentType(): string
    {
        return $this->opt['type'] === 'csv'
            ? 'text/csv; charset=UTF-8'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function render(): string
    {
        return self::renderMany([['month' => $this->data['start'], 'export' => $this]]);
    }

    /**
     * Satu file untuk beberapa bulan. Excel: tab "Ringkasan" (satu baris per
     * bulan) lalu satu tab per bulan dengan isi persis seperti export sebulan.
     * CSV: ringkasan lalu tiap bulan berurutan dalam satu file. Satu bulan saja
     * = file sebulan biasa, tanpa ringkasan.
     *
     * @param  array<int, array{month: Carbon, export: ?self}>  $items  urut bulan; export null = belum ada data
     */
    public static function renderMany(array $items): string
    {
        $exports = array_values(array_filter(array_column($items, 'export')));
        $first = $exports[0];

        $sheets = [];
        if (count($items) > 1) {
            $first->out = [];
            $first->summary($items);
            $sheets[] = ['title' => $first->t('summary_tab'), 'rows' => $first->out];
        }
        foreach ($exports as $e) {
            $e->out = [];
            $e->build();
            $sheets[] = ['title' => $e->data['start']->translatedFormat('M Y'), 'rows' => $e->out];
        }

        // Kalimat penutup formal di akhir setiap tab.
        foreach ($sheets as &$sh) {
            $sh['rows'][] = ['style' => '', 'cells' => []];
            $sh['rows'][] = ['style' => 'foot', 'cells' => [$first->t('footer')]];
        }
        unset($sh);

        return $first->opt['type'] === 'csv' ? $first->toCsv($sheets) : $first->toXlsx($sheets, $items);
    }

    private function build(): void
    {
        $this->header();

        foreach ($this->opt['periods'] as $p) {
            if ($p === 'month') {
                $this->section(
                    $this->cutoff()
                        ? $this->t('sec_running', ['range' => $this->range($this->data['start'], $this->cutoff())])
                        : $this->t('sec_month', ['range' => $this->range($this->data['start'], $this->data['end'])]),
                    $this->data['split'],
                    $this->data['detail'],
                );
            } else {
                $pp = $this->data['periods'][(int) $p];
                $this->section(
                    $this->isClient()
                        ? $this->t('sec_period_client', ['range' => $this->range($pp['start'], $pp['end'])])
                        : $this->t('sec_period', ['range' => $this->range($pp['start'], $pp['end']), 'share' => $this->pct($pp['share'])]),
                    $pp['split'],
                    $pp['detail'] ?? null,
                );
            }
        }

    }

    // ---------------------------------------------------------------- CSV

    private function toCsv(array $sheets): string
    {
        $rows = [];
        foreach ($sheets as $i => $s) {
            // Pemisah antarbulan: dua baris kosong.
            if ($i > 0) {
                $rows[] = ['style' => '', 'cells' => []];
                $rows[] = ['style' => '', 'cells' => []];
            }
            array_push($rows, ...$s['rows']);
        }

        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            $cells = array_map(
                fn ($c) => is_array($c) ? $this->numText($c) : self::safeCell((string) $c),
                $row['cells'],
            );
            fputcsv($fh, $cells, $this->sep, '"', '', "\r\n");
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        // BOM: tanpa ini Excel membaca UTF-8 sebagai ANSI ("–" jadi "â€“").
        return "\xEF\xBB\xBF" . $csv;
    }

    // ---------------------------------------------------------------- Excel

    private function toXlsx(array $sheets, array $items): string
    {
        $months = count($items) > 1
            ? $items[0]['month']->translatedFormat('M Y') . ' – ' . end($items)['month']->translatedFormat('M Y')
            : $this->data['start']->translatedFormat('F Y');

        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator(self::BRAND)
            ->setLastModifiedBy(self::BRAND)
            ->setCompany(self::BRAND)
            ->setTitle($this->t('title') . ($this->isClient() ? '' : ' · ' . $this->data['account']->name) . ' · ' . $months);
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $used = [];
        foreach ($sheets as $i => $s) {
            $sheet = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            // Nama tab: tanpa karakter terlarang Excel, maks. 31 huruf, tidak kembar.
            $title = mb_substr(trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $s['title'])), 0, 28) ?: 'Sheet';
            $name = $title;
            for ($n = 2; isset($used[mb_strtolower($name)]); $n++) {
                $name = $title . ' ' . $n;
            }
            $used[mb_strtolower($name)] = true;
            $sheet->setTitle($name);
            $this->fillSheet($sheet, $s['rows']);
        }
        $book->setActiveSheetIndex(0);

        ob_start();
        (new Xlsx($book))->save('php://output');
        $bin = ob_get_clean();
        $book->disconnectWorksheets();

        return $bin;
    }

    private function fillSheet($sheet, array $rows): void
    {
        $sheet->setShowGridlines(false);

        // Lebar tabel terlebar: dipakai untuk pita judul bagian.
        $tableStyles = ['th', 'co', 'key', 'op', 'tot'];
        $width = max(2, ...array_map(
            fn ($r) => in_array($r['style'], $tableStyles, true) ? count($r['cells']) : 2,
            $rows,
        ));
        $last = Coordinate::stringFromColumnIndex($width);

        $colLen = [];
        foreach ($rows as $i => $row) {
            $r = $i + 1;
            foreach ($row['cells'] as $c => $cell) {
                $addr = Coordinate::stringFromColumnIndex($c + 1) . $r;
                if (is_array($cell)) {
                    $sheet->setCellValueExplicit($addr, round($cell['n'], $cell['d']), DataType::TYPE_NUMERIC);
                    $sheet->getStyle($addr)->getNumberFormat()->setFormatCode($this->xlsxFormat($cell));
                    // Angka di blok info sejajar dengan teks lain; di tabel rata kanan.
                    $sheet->getStyle($addr)->getAlignment()->setHorizontal(
                        $row['style'] === 'kv' ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_RIGHT,
                    );
                } elseif ($cell !== '') {
                    // Teks eksplisit: tidak pernah dibaca sebagai rumus.
                    $sheet->setCellValueExplicit($addr, (string) $cell, DataType::TYPE_STRING);
                }

                // Lebar kolom dari isi tabel (+ label info di kolom A).
                // Angka di blok info ikut dihitung: angka tidak meluber ke sel sebelah
                // seperti teks, jadi kolomnya harus cukup lebar (mis. kurs).
                if (in_array($row['style'], $tableStyles, true) || ($c === 0 && $row['style'] === 'kv')
                    || ($row['style'] === 'kv' && is_array($cell))) {
                    $len = mb_strlen(is_array($cell) ? $this->numText($cell, true) : (string) $cell);
                    $colLen[$c] = max($colLen[$c] ?? 0, $len);
                }
            }
            $this->styleRow($sheet, $row['style'], $r, $last, count($row['cells']));
            // Kop: logo lengkap "GrabMaps" sebagai satu gambar, sel dikosongkan.
            // Jangan logo + teks di sel yang sama: Google Sheets mengabaikan indent
            // sel, jadi teksnya tertimpa logo.
            if ($row['style'] === 'brand' && is_file($logo = public_path('logo.png'))) {
                $sheet->setCellValueExplicit("A{$r}", null, DataType::TYPE_NULL);
                $img = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $img->setName(self::BRAND)->setDescription(self::BRAND)->setPath($logo)->setHeight(26)
                    ->setCoordinates("A{$r}")->setOffsetX(2)->setOffsetY(6)->setWorksheet($sheet);
            }
        }

        foreach ($colLen as $c => $len) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c + 1))
                ->setWidth(min(max($len + 3, 9), 46));
        }

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);
        // Kepala & kaki halaman saat dicetak / disimpan PDF.
        $sheet->getHeaderFooter()
            ->setOddHeader('&L&"Arial,Bold"&K00B14F' . self::BRAND . '&R&"Arial"&8' . $this->t('title'))
            ->setOddFooter('&L&"Arial"&8' . $this->t('footer') . '&R&"Arial"&8' . $this->t('print_page'));
        $sheet->setSelectedCell('A1');
    }

    private function styleRow($sheet, string $style, int $r, string $last, int $cells): void
    {
        $row = "A{$r}:{$last}{$r}";
        $span = "A{$r}:" . Coordinate::stringFromColumnIndex(max($cells, 1)) . $r;
        $font = fn ($range) => $sheet->getStyle($range)->getFont();
        $fill = fn ($range, $color) => $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
        $line = fn ($range, $side = 'bottom', $color = self::LINE, $kind = Border::BORDER_THIN) => $sheet->getStyle($range)->getBorders()->{'get' . ucfirst($side)}()->setBorderStyle($kind)->getColor()->setARGB($color);

        switch ($style) {
            case 'brand':
                // Cadangan kalau file logo tidak ada: teks "GrabMaps" saja.
                $font("A{$r}")->setBold(true)->setSize(18)->getColor()->setARGB(self::GREEN);
                $sheet->getStyle("A{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($r)->setRowHeight(36);
                break;
            case 'brand_sub':
                $font("A{$r}")->setSize(9)->getColor()->setARGB(self::MUTED);
                $line($row, 'bottom', self::GREEN, Border::BORDER_MEDIUM);
                $sheet->getRowDimension($r)->setRowHeight(18);
                break;
            case 'foot':
                $font("A{$r}")->setItalic(true)->setSize(9)->getColor()->setARGB(self::MUTED);
                $line($row, 'top', self::LINE);
                break;
            case 'title':
                $font("A{$r}")->setBold(true)->setSize(16)->getColor()->setARGB(self::GREEN_TEXT);
                $sheet->getRowDimension($r)->setRowHeight(26);
                break;
            case 'kv':
                $font("A{$r}")->setBold(true)->getColor()->setARGB(self::MUTED);
                break;
            case 'head':
                $font("A{$r}")->setBold(true)->setSize(11)->getColor()->setARGB(self::GREEN_TEXT);
                $line($row, 'bottom', self::GREEN);
                $sheet->getRowDimension($r)->setRowHeight(20);
                break;
            case 'section':
                $fill($row, self::GREEN);
                $font($row)->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
                $sheet->getRowDimension($r)->setRowHeight(22);
                $sheet->getStyle($row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                break;
            case 'th':
                $fill($span, self::GREEN_SOFT);
                $font($span)->setBold(true)->getColor()->setARGB(self::GREEN_TEXT);
                $line($span, 'bottom', self::GREEN);
                $sheet->getStyle($span)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($r)->setRowHeight(30);
                break;
            case 'co':
                $fill($span, self::SURFACE);
                $font($span)->setBold(true);
                $line($span);
                break;
            case 'key':
                $sheet->getStyle("A{$r}")->getAlignment()->setIndent(1);
                $line($span);
                break;
            case 'op':
                $sheet->getStyle("A{$r}")->getAlignment()->setIndent(2);
                $font($span)->setSize(9)->getColor()->setARGB(self::MUTED);
                $line($span);
                break;
            case 'tot':
                $fill($span, self::GREEN_SOFT);
                $font($span)->setBold(true);
                $line($span, 'top', self::GREEN, Border::BORDER_MEDIUM);
                break;
            case 'note':
                $font("A{$r}")->setItalic(true)->getColor()->setARGB(self::MUTED);
                break;
        }
    }

    private function xlsxFormat(array $cell): string
    {
        return self::XLSX_FORMATS[$cell['k'] . $cell['d']]
            ?? ($cell['d'] > 0 ? '#,##0.' . str_repeat('0', $cell['d']) : '#,##0');
    }

    // ---------------------------------------------------------------- bagian

    /** Kop dokumen: logo + GrabMaps, subjudul layanan, garis hijau (Excel). */
    private function letterhead(): void
    {
        $this->row([self::BRAND], 'brand');
        $this->row([$this->t('brand_sub')], 'brand_sub');
        $this->row([]);
    }

    private function header(): void
    {
        $d = $this->data;
        $o = $this->opt;
        $split = $d['split'];

        $names = [];
        foreach ($split['rows'] as $r) {
            if (in_array((int) ($r['company_id'] ?? 0), $o['companies'], true)) {
                $names[] = $r['name'] ?? __('awsbilling.unassigned');
            }
        }

        $periodLabels = array_map(fn ($p) => $p === 'month'
            ? ($this->cutoff()
                ? $this->t('running_upto', ['range' => $this->range($d['start'], $this->cutoff())])
                : $this->t('full_month', ['range' => $this->range($d['start'], $d['end'])]))
            : $this->range($d['periods'][(int) $p]['start'], $d['periods'][(int) $p]['end']), $o['periods']);

        $this->letterhead();
        $this->row([$this->t('title')], 'title');
        if (!$this->isClient()) {
            $this->row([$this->t('account'), $d['account']->name]);
        }
        $this->row([$this->t('month'), $d['start']->translatedFormat('F Y')]);
        $this->row([$this->t('periods'), implode('; ', $periodLabels)]);
        if ($this->isClient()) {
            // Klien: selalu nama perusahaan yang dipilih, juga di bulan tanpa pemakaian.
            $names = $o['picked_names'] ?: $names;
        }
        $this->row([$this->t('companies'), $o['all'] && !$this->isClient()
            ? $this->t('companies_all', ['n' => count($names)])
            : (implode('; ', $names) ?: '—')]);
        $this->row([$this->t('detail'), $this->t('level_' . $o['level'])]);
        $this->row([$this->t('status'), $this->statusText()]);
        $this->row([$this->t('generated'), now()->wib()->translatedFormat('d M Y, H:i') . ' WIB']);
        $this->row([]);

        // ---- Kurs & tarif: angka yang menentukan semua kolom Rupiah.
        $rate = $d['detail']['rate'];
        $vatPct = $this->pct($split['vat_rate'] * 100);

        $this->row([$this->t('rate_head')], 'head');
        $this->row([$this->t('rate'), $this->num($rate, 2, 'idr')]);
        $this->row([$this->t('rate_text'), '1 USD = ' . $this->rpText($rate, 2)]);
        $this->row([$this->t('rate_source'), $this->rateSource()]);
        $this->row([$this->t('rate_formula'), $this->t('rate_formula_v')]);
        // Fee/VAT yang dimatikan di akun, atau kolomnya tidak dicentang, tidak
        // ikut dihitung dan tidak disebut sama sekali.
        $hasFee = ($split['has_fee'] ?? true) && $this->countsFee();
        $hasVat = ($split['has_vat'] ?? true) && $this->countsVat();
        if ($hasFee) {
            $feePct = $this->feePct();
            $this->row([$this->t('fee'), $feePct !== null
                ? $this->t('fee_v', ['pct' => $this->pct($feePct)])
                : $this->t('fee_mixed')]);
            // Minimum fee: versi klien hanya melihat minimum perusahaan yang dipilih.
            $mins = array_unique(array_filter(array_map(
                fn ($r) => (float) ($r['min'] ?? 0),
                $this->isClient() ? $this->selectedRows($split) : $split['rows'],
            )));
            if ($mins) {
                $this->row([$this->t('fee_min'), $this->isClient() && count($mins) === 1
                    ? $this->t('fee_min_client_v', ['min' => $this->rpText(reset($mins), 0)])
                    : $this->t('fee_min_v')]);
            }
        }
        if ($hasVat) {
            $this->row([$this->t('vat'), $this->t($hasFee ? 'vat_v' : 'vat_v_aws', ['pct' => $vatPct])]);
        }
        if (in_array('total', $o['cols'], true)) {
            $this->row([$this->t('total_formula'), $this->totalFormula()]);
        }
        $this->row([$this->t('rounding'), $this->t($this->isClient() ? 'rounding_client_v' : 'rounding_v')]);
        $this->row([]);

        // ---- Keterangan kolom yang ikut di file ini.
        $this->row([$this->t('cols_head')], 'head');
        if (!$this->isClient()) {
            $this->row([$this->t('h_share'), $this->t('col_share_v')]);
        }
        if (in_array('fee', $o['cols'], true)) {
            $this->row([$this->t('h_fee_pct'), $this->t('col_fee_pct_v')]);
        }
        foreach ($o['cols'] as $c) {
            $desc = match (true) {
                $c === 'invoice' && !$hasFee => $this->t('col_invoice_v_nofee'),
                $c === 'vat' && !$hasFee     => $this->t('vat_v_aws', ['pct' => $vatPct]),
                $c === 'total'               => $this->t('col_total_v_f', ['formula' => $this->totalFormula()]),
                default                      => $this->t('col_' . $c . '_v', ['pct' => $vatPct]),
            };
            $this->row([$this->colLabel($c), $desc]);
        }
        if ($o['level'] === 'op') {
            $this->row([$this->t('h_requests'), $this->t('col_requests_v')]);
            $this->row([$this->t('lv_op'), $this->t('col_op_v')]);
        }
        $this->row([]);
    }

    private function section(string $title, array $split, ?array $detail): void
    {
        $split = $this->adjustSplit($split);
        $o = $this->opt;
        $withPct = in_array('fee', $o['cols'], true);
        $withOps = $o['level'] === 'op';
        // Porsi (%) = bagian dari total akun: tidak untuk klien.
        $withShare = !$this->isClient();
        $ops = $withOps ? $this->opsIndex($detail) : [];

        $rows = array_values(array_filter(
            $split['rows'],
            fn ($r) => in_array((int) ($r['company_id'] ?? 0), $o['companies'], true),
        ));

        // Satu bentuk baris untuk semua tingkat: perusahaan → API key → operasi.
        $line = fn (string $lv, string $co, string $key, array $opCells, float $share, string|array $pct, array $v) => array_merge(
            [$lv, $co, $key],
            $withOps ? $opCells : [],
            $withShare ? [$this->num($share, 2)] : [],
            $withPct ? [$pct] : [],
            $this->values($v),
        );
        $requests = fn (int $cid, ?string $key = null) => $this->num(array_sum(array_map(
            fn ($k) => $k['requests'],
            $key === null ? ($ops[$cid] ?? []) : array_filter([$ops[$cid][$key] ?? null]),
        )), 0, 'count');

        $this->row(['▶ ' . $title], 'section');

        $head = [$this->t('h_level'), $this->t('h_company'), $this->t('h_key')];
        if ($withOps) {
            array_push($head, $this->t('h_category'), $this->t('h_operation'), $this->t('h_requests'));
        }
        if ($withShare) {
            $head[] = $this->t('h_share');
        }
        if ($withPct) {
            $head[] = $this->t('h_fee_pct');
        }
        foreach ($o['cols'] as $c) {
            $head[] = $this->colLabel($c);
        }
        $this->row($head, 'th');

        foreach ($rows as $r) {
            $cid = (int) ($r['company_id'] ?? 0);
            $name = $r['name'] ?? __('awsbilling.unassigned');
            $this->row($line($this->t('lv_company'), $name, '', ['', '', $requests($cid)], $r['share'], $this->num($r['percent'], 2), $r), 'co');

            if ($o['level'] === 'company') {
                continue;
            }

            foreach ($r['keys'] as $k) {
                $this->row($line($this->t('lv_key'), $name, $k['name'], ['', '', $requests($cid, $k['name'])], $k['share'], '', $k), 'key');

                // Operasi: porsi baris API key dibagi sebanding biaya tarif daftarnya,
                // jadi semua kolom operasi menjumlah ke baris API key di atasnya.
                $info = $ops[$cid][$k['name']] ?? null;
                foreach ($info['lines'] ?? [] as $ln) {
                    $f = $info['usd'] > 0 ? $ln['usd'] / $info['usd'] : 0.0;
                    $this->row($line(
                        $this->t('lv_op'), $name, $k['name'], [$ln['cat'], $ln['op'], $this->num($ln['count'], 0, 'count')],
                        $k['share'] * $f, '', array_map(fn ($x) => $x * $f, array_intersect_key($k, array_flip(self::FIELD))),
                    ), 'op');
                }
            }
        }

        if (!$rows) {
            $this->row([$this->t('no_usage')], 'note');
        }

        $total = $this->selectedTotal($split);
        if ($o['all']) {
            $label = $this->t('lv_total');
            $pct = $split['percent'] !== null ? $this->num($split['percent'], 2) : '';
        } else {
            $label = $this->t($this->isClient() ? 'lv_total' : 'lv_total_sel');
            $pct = '';
        }
        $totalReq = $this->num(array_sum(array_map(
            fn ($r) => array_sum(array_column($ops[(int) ($r['company_id'] ?? 0)] ?? [], 'requests')),
            $rows,
        )), 0, 'count');
        $this->row($line($label, '', '', ['', '', $totalReq], $total['share'], $pct, $total), 'tot');

        // Jembatan ke Billing AWS = angka tingkat akun: tidak pernah untuk klien.
        if ($withOps && $o['all'] && $detail && !$this->isClient()) {
            $this->bridge($detail);
        }

        $this->row([]);
    }

    private function isClient(): bool
    {
        return (bool) ($this->opt['client'] ?? false);
    }

    /** @return array<int, array> baris perusahaan yang dipilih */
    private function selectedRows(array $split): array
    {
        return array_values(array_filter(
            $split['rows'],
            fn ($r) => in_array((int) ($r['company_id'] ?? 0), $this->opt['companies'], true),
        ));
    }

    /**
     * Persen fee untuk judul kolom & kepala file. Internal: tarif seragam akun.
     * Klien: tarif perusahaan yang dipilih (kalau sama semua), tanpa melihat
     * tarif perusahaan lain.
     */
    private function feePct(): ?float
    {
        if (!$this->isClient()) {
            return $this->data['split']['percent'];
        }
        $p = array_unique(array_map(fn ($r) => (float) $r['percent'], $this->selectedRows($this->data['split'])));

        return count($p) === 1 ? reset($p) : null;
    }

    private function countsFee(): bool
    {
        return in_array('fee', $this->opt['cols'], true);
    }

    private function countsVat(): bool
    {
        return in_array('vat', $this->opt['cols'], true);
    }

    /**
     * Kolom Management fee / VAT yang tidak dicentang juga tidak dihitung:
     * Invoice = Billing AWS + fee (kalau dicentang), VAT = tarif × Invoice itu
     * (kalau dicentang, dibulatkan per baris seperti halaman), Total = Invoice +
     * VAT. Baris API key tetap sebanding baris perusahaannya, baris total
     * dihitung dari totalnya sendiri — sama dengan cara halaman menghitung.
     */
    private function adjustSplit(array $split): array
    {
        $withFee = $this->countsFee();
        $rate = $this->countsVat() ? (float) $split['vat_rate'] : 0.0;
        if ($withFee && $this->countsVat()) {
            return $split;
        }

        $line = function (array $v) use ($withFee, $rate) {
            $fee = $withFee ? (float) ($v['fee'] ?? 0) : 0.0;
            $invoice = (float) ($v['idr'] ?? 0) + $fee;
            $vat = round($invoice * $rate);

            return ['fee' => $fee, 'invoice' => $invoice, 'vat' => $vat, 'total' => $invoice + $vat] + $v;
        };

        foreach ($split['rows'] as &$r) {
            $idr = (float) ($r['idr'] ?? 0);
            $r = $line($r);
            foreach ($r['keys'] as &$k) {
                $f = $idr > 0 ? $k['idr'] / $idr : 0.0;
                foreach (['fee', 'invoice', 'vat', 'total'] as $x) {
                    $k[$x] = $r[$x] * $f;
                }
            }
            unset($k);
        }
        unset($r);
        $split['total'] = $line($split['total']);

        return $split;
    }

    /** "Billing AWS (IDR) + Management fee + VAT" — hanya komponen yang dihitung. */
    private function totalFormula(): string
    {
        $parts = [$this->t('col_aws')];
        if ($this->countsFee() && ($this->data['split']['has_fee'] ?? true)) {
            $parts[] = $this->t('fee');
        }
        if ($this->countsVat() && ($this->data['split']['has_vat'] ?? true)) {
            $parts[] = $this->t('vat');
        }

        return implode(' + ', $parts);
    }

    /**
     * Baris total: semua perusahaan → baris total halaman (sama persis dengan
     * layar); sebagian → jumlah perusahaan yang dipilih saja.
     */
    private function selectedTotal(array $split): array
    {
        if ($this->opt['all']) {
            return $split['total'] + ['share' => count($split['rows']) ? 100 : 0];
        }

        $total = ['share' => 0];
        foreach ($split['rows'] as $r) {
            if (!in_array((int) ($r['company_id'] ?? 0), $this->opt['companies'], true)) {
                continue;
            }
            foreach (['share', ...array_values(self::FIELD)] as $f) {
                $total[$f] = ($total[$f] ?? 0) + ($r[$f] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Tab Ringkasan export banyak bulan: satu baris per bulan dengan kurs bulan
     * itu sendiri (kurs tiap bulan bisa beda, jadi Rupiah tiap bulan dihitung
     * dengan kursnya masing-masing), lalu TOTAL.
     *
     * @param  array<int, array{month: Carbon, export: ?self}>  $items
     */
    private function summary(array $items): void
    {
        $exports = array_values(array_filter(array_column($items, 'export')));

        $names = [];
        foreach ($exports as $e) {
            foreach ($e->data['split']['rows'] as $r) {
                if (in_array((int) ($r['company_id'] ?? 0), $e->opt['companies'], true)) {
                    $names[$r['name'] ?? __('awsbilling.unassigned')] = true;
                }
            }
        }
        $all = collect($exports)->every(fn ($e) => $e->opt['all']);

        $this->letterhead();
        $this->row([$this->t('title')], 'title');
        if (!$this->isClient()) {
            $this->row([$this->t('account'), $this->data['account']->name]);
        }
        $this->row([$this->t('months'), $this->t('months_v', [
            'from' => $items[0]['month']->translatedFormat('F Y'),
            'to'   => end($items)['month']->translatedFormat('F Y'),
            'n'    => count($items),
        ])]);
        if ($this->isClient() && $this->opt['picked_names']) {
            $names = array_flip($this->opt['picked_names']);
        }
        $this->row([$this->t('companies'), $all && !$this->isClient() ? $this->t('companies_all_months') : (implode('; ', array_keys($names)) ?: '—')]);
        $this->row([$this->t('detail'), $this->t('level_' . $this->opt['level'])]);
        $this->row([$this->t('generated'), now()->wib()->translatedFormat('d M Y, H:i') . ' WIB']);
        $this->row([]);

        $this->row([$this->t('sum_head')], 'head');
        $head = [$this->t('h_month'), $this->t('status'), $this->t('h_rate'), $this->t('rate_source')];
        foreach ($this->opt['cols'] as $c) {
            // Persen fee bisa berubah antarbulan: judul kolomnya tanpa persen.
            $head[] = $c === 'fee' ? $this->t('col_fee') : $this->colLabel($c);
        }
        $this->row($head, 'th');

        $sum = [];
        foreach ($items as $it) {
            $label = $it['month']->translatedFormat('F Y');
            $e = $it['export'];
            if ($e && $e->cutoff()) {
                $label = $this->t('sum_upto', ['month' => $label, 'range' => $this->range($e->data['start'], $e->cutoff())]);
            }
            if (!$e) {
                $this->row([$label, $this->t('sum_nodata')], 'key');
                continue;
            }
            $tot = $e->selectedTotal($e->adjustSplit($e->data['split']));
            foreach (self::FIELD as $f) {
                $sum[$f] = ($sum[$f] ?? 0) + (float) ($tot[$f] ?? 0);
            }
            $this->row(array_merge(
                [$label, $e->statusText(), $this->num($e->data['detail']['rate'], 2, 'idr'), $e->rateSource()],
                $this->values($tot),
            ), 'key');
        }
        $this->row(array_merge([$all || $this->isClient() ? $this->t('lv_total') : $this->t('lv_total_sel'), '', '', ''], $this->values($sum)), 'tot');
        $this->row([]);
        $this->row([$this->t('sum_note_rate')], 'note');
        $this->row([$this->t('sum_note_tabs')], 'note');
    }

    /** Akhir data bulan berjalan (akhir kemarin, UTC); null untuk bulan yang sudah tutup. */
    private function cutoff(): ?Carbon
    {
        return $this->data['cutoff'] ?? null;
    }

    private function statusText(): string
    {
        $d = $this->data;

        // Bulan berjalan: sebut persis sampai kapan dihitung. Hari tagihan AWS
        // memakai UTC, jadi akhir hari UTC = pukul 06.59 WIB keesokan harinya.
        if ($cut = $this->cutoff()) {
            return $this->t('status_cut', [
                'date' => $cut->translatedFormat('j M Y'),
                'wib'  => $cut->copy()->endOfDay()->setTimezone('Asia/Jakarta')->translatedFormat('j M Y, H.i'),
            ]);
        }

        return __('awsbilling.status_' . ($d['complete'] ? 'complete' : (!$d['monthClosed'] ? 'running' : 'partial')));
    }

    /**
     * Operasi per perusahaan → API key dari rincian halaman: hanya yang dipakai,
     * plus biaya tarif daftar key itu (dasar pembagian) dan jumlah request-nya.
     *
     * @return array<int, array<string, array{usd: float, requests: int, lines: array}>>
     */
    private function opsIndex(?array $detail): array
    {
        $idx = [];
        foreach ($detail['companies'] ?? [] as $co) {
            foreach ($co['keys'] as $kb) {
                $lines = [];
                $req = 0;
                foreach ($kb['categories'] as $cat) {
                    foreach ($cat['lines'] as $ln) {
                        $req += $ln['count'];
                        if ($ln['count'] > 0) {
                            $lines[] = $ln + ['cat' => $cat['name']];
                        }
                    }
                }
                $idx[(int) ($co['company_id'] ?? 0)][$kb['name']] = ['usd' => $kb['usd'], 'requests' => $req, 'lines' => $lines];
            }
        }

        return $idx;
    }

    /** Dari biaya pemakaian (tarif daftar) sampai angka Billing AWS — tingkat akun. */
    private function bridge(array $detail): void
    {
        $this->row([]);
        $this->row([$this->t('rec_head')], 'head');
        $this->row([$this->t('h_component'), 'USD', $this->t('h_rec_idr')], 'th');
        $this->row([$this->t('rec_sub'), $this->num($detail['subtotal']['usd'], 2, 'usd'), $this->num($detail['subtotal']['idr'], 0, 'idr')], 'key');
        if ($detail['tax']) {
            $this->row([$this->t('rec_tax'), $this->num($detail['tax']['usd'], 2, 'usd'), $this->num($detail['tax']['idr'], 0, 'idr')], 'key');
        }
        $this->row([$this->t('rec_other'), $this->num($detail['other']['usd'], 2, 'usd'), $this->num($detail['other']['idr'], 0, 'idr')], 'key');
        $this->row([$this->t('rec_aws'), $this->num($detail['aws']['usd'], 2, 'usd'), $this->num($detail['aws']['idr'], 0, 'idr')], 'tot');
    }

    // ---------------------------------------------------------------- bantu

    /**
     * Kolom yang masuk akal untuk akun ini: tanpa management fee, kolom fee dan
     * "invoice" (= tagihan + fee) hilang; tanpa VAT, kolom VAT hilang; tanpa
     * keduanya, total sama dengan tagihan AWS sehingga ikut hilang.
     *
     * @return array<int, string>
     */
    public static function columnsFor(array $data): array
    {
        $fee = $data['split']['has_fee'] ?? true;
        $vat = $data['split']['has_vat'] ?? true;
        $drop = array_merge($fee ? [] : ['fee', 'invoice'], $vat ? [] : ['vat'], ($fee || $vat) ? [] : ['total']);

        return array_values(array_diff(self::COLUMNS, $drop));
    }

    private function rateSource(): string
    {
        $d = $this->data;

        // Klien: asal kurs tanpa total invoice akun (dari situ pemakaian
        // perusahaan lain bisa ditebak).
        if ($this->isClient()) {
            return match (true) {
                $d['simulated']           => $this->t('src_client_sim'),
                (bool) $d['invoice']      => $this->t('src_client_invoice', ['month' => $d['start']->translatedFormat('F Y')]),
                (bool) $d['storedRate']   => $this->t('src_client_table', ['date' => Carbon::parse($d['storedRate']->rate_date)->translatedFormat('d M Y')]),
                default                   => $this->t('src_none'),
            };
        }

        if ($d['simulated']) {
            return $this->t('src_sim', ['official' => $this->rpText($d['officialRate'], 2)]);
        }
        if ($d['invoice']) {
            return $this->t('src_invoice', [
                'idr' => $this->rpText($d['invoice']->total_idr, 0),
                'usd' => '$' . number_format($d['invoice']->total_usd, 2, '.', ','),
            ]);
        }
        if ($d['storedRate']) {
            return $this->t('src_table', ['date' => Carbon::parse($d['storedRate']->rate_date)->translatedFormat('d M Y')]);
        }

        return $this->t('src_none');
    }

    /** @return array<int, array> */
    private function values(array $r): array
    {
        return array_map(
            fn ($c) => $this->num((float) ($r[self::FIELD[$c]] ?? 0), $c === 'usd' ? 2 : 0, $c === 'usd' ? 'usd' : 'idr'),
            $this->opt['cols'],
        );
    }

    private function colLabel(string $c): string
    {
        // Fee seragam: persennya ditulis di judul kolom. Tarif campuran: lihat kolom "Fee (%)".
        $fee = $this->feePct();
        if ($c === 'fee' && $fee !== null) {
            return $this->t('col_fee_pct', ['fee' => $this->pct($fee)]);
        }

        return $this->t('col_' . $c, ['pct' => $this->pct($this->data['split']['vat_rate'] * 100)]);
    }

    /**
     * Sel angka bertipe — ditulis sebagai angka asli di Excel, sebagai teks
     * berformat di CSV.
     *
     * @param  string  $kind  idr | usd | count | plain
     * @return array{n: float, d: int, k: string}
     */
    private function num(float $v, int $dec, string $kind = 'plain'): array
    {
        return ['n' => $v, 'd' => $dec, 'k' => $kind];
    }

    /**
     * Teks angka untuk CSV (atau perkiraan lebar kolom Excel). Desimal mengikuti
     * format; dengan "pretty", ribuan dipisah dan uang diberi satuan.
     */
    private function numText(array $cell, ?bool $pretty = null): string
    {
        $v = round($cell['n'], $cell['d']);
        $pretty ??= $this->opt['pretty'] ?? false;
        $thousands = $pretty ? ($this->opt['format'] === 'intl' ? ',' : '.') : '';
        $out = number_format(abs($v), $cell['d'], $this->dec, $thousands);

        if ($pretty && $cell['k'] === 'idr') {
            $out = 'Rp ' . $out;
        } elseif ($pretty && $cell['k'] === 'usd') {
            $out = '$' . $out;
        }

        return ($v < 0 ? '-' : '') . $out;
    }

    /** "Rp 17.990,27" — untuk teks penjelasan, bukan kolom angka. */
    private function rpText(float $v, int $dec): string
    {
        return 'Rp ' . number_format($v, $dec, ',', '.');
    }

    private function pct(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    }

    private function range(Carbon $a, Carbon $b): string
    {
        return $a->format('j') . '–' . $b->translatedFormat('j M Y');
    }

    private function t(string $key, array $replace = []): string
    {
        return __('awsbilling.csv_' . $key, $replace);
    }

    /** @param  string  $style  title|kv|head|section|th|co|key|op|tot|note|'' */
    private function row(array $cells, string $style = ''): void
    {
        if ($style === '' && count($cells) === 2) {
            $style = 'kv';
        }
        $this->out[] = ['style' => $style, 'cells' => $cells];
    }

    /**
     * Sel yang diawali = + - @ (atau tab/CR) dibaca Excel/Sheets sebagai rumus:
     * label jadi #ERROR!, dan nama perusahaan/API key bisa disusupi rumus
     * (CSV injection). Sel seperti itu diberi awalan ' supaya tetap teks.
     * Angka negatif yang sah (-12,50 / -Rp 1.000 / -$3.10) dibiarkan.
     */
    public static function safeCell(string $c): string
    {
        if ($c === '') {
            return $c;
        }
        if (preg_match('/^[=+@\t\r]/', $c)) {
            return "'" . $c;
        }
        if ($c[0] === '-' && !preg_match('/^-(\$|Rp )?[\d.,]+$/', $c)) {
            return "'" . $c;
        }

        return $c;
    }
}
