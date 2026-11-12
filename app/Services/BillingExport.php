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
    public static function optionsFrom(Request $request, array $data): array
    {
        $ids = array_map(fn ($r) => (int) ($r['company_id'] ?? 0), $data['split']['rows']);

        $co = $request->query('co');
        $companies = is_array($co) ? array_values(array_intersect($ids, array_map('intval', $co))) : $ids;
        $companies = $companies ?: $ids;

        $periodKeys = ['month'];
        if (count($data['periods']) > 1) {
            foreach (array_keys($data['periods']) as $i) {
                $periodKeys[] = (string) $i;
            }
        }
        $per = $request->query('per');
        $periods = is_array($per) ? array_values(array_intersect($periodKeys, array_map('strval', $per))) : ['month'];
        $periods = $periods ?: ['month'];

        $level = in_array($request->query('level'), self::LEVELS, true) ? $request->query('level') : 'key';

        $c = $request->query('cols');
        $cols = is_array($c) ? array_values(array_intersect(self::COLUMNS, $c)) : self::COLUMNS;
        $cols = $cols ?: self::COLUMNS;

        return [
            'companies' => $companies,
            'all'       => count($companies) === count($ids),
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
        $this->out = [];
        $this->build();

        return $this->opt['type'] === 'csv' ? $this->toCsv() : $this->toXlsx();
    }

    private function build(): void
    {
        $this->header();

        foreach ($this->opt['periods'] as $p) {
            if ($p === 'month') {
                $this->section(
                    $this->t('sec_month', ['range' => $this->range($this->data['start'], $this->data['end'])]),
                    $this->data['split'],
                    $this->data['detail'],
                );
            } else {
                $pp = $this->data['periods'][(int) $p];
                $this->section(
                    $this->t('sec_period', ['range' => $this->range($pp['start'], $pp['end']), 'share' => $this->pct($pp['share'])]),
                    $pp['split'],
                    $pp['detail'] ?? null,
                );
            }
        }

    }

    // ---------------------------------------------------------------- CSV

    private function toCsv(): string
    {
        $fh = fopen('php://temp', 'r+');
        foreach ($this->out as $row) {
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

    private function toXlsx(): string
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('GrabMaps')
            ->setTitle($this->t('title') . ' · ' . $this->data['account']->name . ' · ' . $this->data['start']->translatedFormat('F Y'));
        $book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $sheet = $book->getActiveSheet();
        $sheet->setTitle(mb_substr($this->data['start']->translatedFormat('M Y'), 0, 31));
        $sheet->setShowGridlines(false);

        // Lebar tabel terlebar: dipakai untuk pita judul bagian.
        $tableStyles = ['th', 'co', 'key', 'op', 'tot'];
        $width = max(2, ...array_map(
            fn ($r) => in_array($r['style'], $tableStyles, true) ? count($r['cells']) : 2,
            $this->out,
        ));
        $last = Coordinate::stringFromColumnIndex($width);

        $colLen = [];
        foreach ($this->out as $i => $row) {
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
                if (in_array($row['style'], $tableStyles, true) || ($c === 0 && $row['style'] === 'kv')) {
                    $len = mb_strlen(is_array($cell) ? $this->numText($cell, true) : (string) $cell);
                    $colLen[$c] = max($colLen[$c] ?? 0, $len);
                }
            }
            $this->styleRow($sheet, $row['style'], $r, $last, count($row['cells']));
        }

        foreach ($colLen as $c => $len) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c + 1))
                ->setWidth(min(max($len + 3, 9), 46));
        }

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);
        $sheet->setSelectedCell('A1');

        ob_start();
        (new Xlsx($book))->save('php://output');
        $bin = ob_get_clean();
        $book->disconnectWorksheets();

        return $bin;
    }

    private function styleRow($sheet, string $style, int $r, string $last, int $cells): void
    {
        $row = "A{$r}:{$last}{$r}";
        $span = "A{$r}:" . Coordinate::stringFromColumnIndex(max($cells, 1)) . $r;
        $font = fn ($range) => $sheet->getStyle($range)->getFont();
        $fill = fn ($range, $color) => $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
        $line = fn ($range, $side = 'bottom', $color = self::LINE, $kind = Border::BORDER_THIN) => $sheet->getStyle($range)->getBorders()->{'get' . ucfirst($side)}()->setBorderStyle($kind)->getColor()->setARGB($color);

        switch ($style) {
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
            ? $this->t('full_month', ['range' => $this->range($d['start'], $d['end'])])
            : $this->range($d['periods'][(int) $p]['start'], $d['periods'][(int) $p]['end']), $o['periods']);

        $status = $d['complete'] ? 'complete' : (!$d['monthClosed'] ? 'running' : 'partial');

        $this->row([$this->t('title')], 'title');
        $this->row([$this->t('account'), $d['account']->name]);
        $this->row([$this->t('month'), $d['start']->translatedFormat('F Y')]);
        $this->row([$this->t('periods'), implode('; ', $periodLabels)]);
        $this->row([$this->t('companies'), $o['all'] ? $this->t('companies_all', ['n' => count($names)]) : implode('; ', $names)]);
        $this->row([$this->t('detail'), $this->t('level_' . $o['level'])]);
        $this->row([$this->t('status'), __('awsbilling.status_' . $status)]);
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
        $this->row([$this->t('fee'), $split['percent'] !== null
            ? $this->t('fee_v', ['pct' => $this->pct($split['percent'])])
            : $this->t('fee_mixed')]);
        if (collect($split['rows'])->contains(fn ($r) => ($r['min'] ?? 0) > 0)) {
            $this->row([$this->t('fee_min'), $this->t('fee_min_v')]);
        }
        $this->row([$this->t('vat'), $this->t('vat_v', ['pct' => $vatPct])]);
        $this->row([$this->t('total_formula'), $this->t('total_formula_v')]);
        $this->row([$this->t('rounding'), $this->t('rounding_v')]);
        $this->row([]);

        // ---- Keterangan kolom yang ikut di file ini.
        $this->row([$this->t('cols_head')], 'head');
        $this->row([$this->t('h_share'), $this->t('col_share_v')]);
        if (in_array('fee', $o['cols'], true)) {
            $this->row([$this->t('h_fee_pct'), $this->t('col_fee_pct_v')]);
        }
        foreach ($o['cols'] as $c) {
            $this->row([$this->colLabel($c), $this->t('col_' . $c . '_v', ['pct' => $vatPct])]);
        }
        if ($o['level'] === 'op') {
            $this->row([$this->t('h_requests'), $this->t('col_requests_v')]);
            $this->row([$this->t('lv_op'), $this->t('col_op_v')]);
        }
        $this->row([]);
    }

    private function section(string $title, array $split, ?array $detail): void
    {
        $o = $this->opt;
        $withPct = in_array('fee', $o['cols'], true);
        $withOps = $o['level'] === 'op';
        $ops = $withOps ? $this->opsIndex($detail) : [];

        $rows = array_values(array_filter(
            $split['rows'],
            fn ($r) => in_array((int) ($r['company_id'] ?? 0), $o['companies'], true),
        ));

        // Satu bentuk baris untuk semua tingkat: perusahaan → API key → operasi.
        $line = fn (string $lv, string $co, string $key, array $opCells, float $share, string|array $pct, array $v) => array_merge(
            [$lv, $co, $key],
            $withOps ? $opCells : [],
            [$this->num($share, 2)],
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
        $head[] = $this->t('h_share');
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

        // Semua perusahaan: total dari baris total halaman (sama persis dengan layar).
        // Sebagian: jumlah baris yang dipilih saja.
        if ($o['all']) {
            $total = $split['total'] + ['share' => count($split['rows']) ? 100 : 0];
            $label = $this->t('lv_total');
            $pct = $split['percent'] !== null ? $this->num($split['percent'], 2) : '';
        } else {
            $total = ['share' => 0];
            foreach ($rows as $r) {
                foreach (['share', ...array_values(self::FIELD)] as $f) {
                    $total[$f] = ($total[$f] ?? 0) + ($r[$f] ?? 0);
                }
            }
            $label = $this->t('lv_total_sel');
            $pct = '';
        }
        $totalReq = $this->num(array_sum(array_map(
            fn ($r) => array_sum(array_column($ops[(int) ($r['company_id'] ?? 0)] ?? [], 'requests')),
            $rows,
        )), 0, 'count');
        $this->row($line($label, '', '', ['', '', $totalReq], $total['share'], $pct, $total), 'tot');

        if ($withOps && $o['all'] && $detail) {
            $this->bridge($detail);
        }

        $this->row([]);
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

    private function rateSource(): string
    {
        $d = $this->data;

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
        $fee = $this->data['split']['percent'];
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
