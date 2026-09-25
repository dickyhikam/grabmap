<?php

namespace App\Services;

use App\Models\ServiceCharge;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Laporan pemakaian publik dalam bentuk .xlsx.
 *
 * Isinya mengikuti halaman /usage-report: ringkasan, pemakaian harian, rincian
 * per operasi, dan — untuk link perusahaan — rincian per API key. Nilai ditulis
 * sebagai angka (bukan teks berformat) supaya klien bisa langsung menghitung.
 */
class UsageReportExcel
{
    private const GREEN = 'FF00B14F';
    private const USD = '"$"#,##0.00##';
    private const IDR = '"Rp "#,##0';
    private const INT = '#,##0';

    private const CATEGORIES = [
        'maps'   => ['dash.cat_maps',   ['GetMapTile', 'GetTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites']],
        'places' => ['dash.cat_places', ['SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace']],
        'routes' => ['dash.cat_routes', ['CalculateRoutes', 'CalculateRouteMatrix']],
    ];

    public function write(array $data): void
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle(__('apikeys.share_report_title'));

        $this->summary($book->getActiveSheet(), $data);
        $this->daily($book->createSheet(), $data);
        $this->operations($book->createSheet(), $data);

        if (isset($data['usage'])) {
            $this->perKey($book->createSheet(), $data);
        }

        $book->setActiveSheetIndex(0);

        (new Xlsx($book))->save('php://output');
    }

    private function summary(Worksheet $sheet, array $d): void
    {
        $sheet->setTitle($this->title(__('apikeys.xl_summary')));

        $ops = $d['metrics']['operations'] ?? [];
        $total = $d['metrics']['total'] ?? 0;
        $cost = AwsLocationService::estimateCost($ops);
        $sc = $d['charge'];

        $start = Carbon::parse($d['startDate']);
        $end = Carbon::parse($d['endDate']);

        $sheet->setCellValue('A1', $d['assignedCompany']?->name ?? __('apikeys.share_report_title'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $rows = [
            [__('apikeys.xl_period'), $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y'), null],
            [__('apikeys.xl_days'), $d['days'], self::INT],
        ];

        if ($d['activeKey']) {
            $rows[] = [__('apikeys.share_key_col'), $d['activeKey'], null];
        } elseif (isset($d['usage'])) {
            $rows[] = [__('apikeys.share_key_col'), count($d['usage']['per_key']), self::INT];
        }

        if ($d['fetchedAt']) {
            $rows[] = [__('apikeys.xl_fetched'), $d['fetchedAt']->wib()->format('d M Y H:i') . ' WIB', null];
        }

        $rows = array_merge($rows, [
            [null, null, null],
            [__('apikeys.total_requests'), $total, self::INT],
            [__('apikeys.avg_per_day'), $total > 0 ? round($total / max($d['days'], 1)) : 0, self::INT],
            [null, null, null],
            [__('apikeys.subtotal'), $cost, self::USD],
        ]);

        if ($sc['active']) {
            $rows[] = [__('servicecharge.line') . ' (' . ServiceCharge::basisLabel($sc) . ')', $sc['charge'], self::USD];
        }

        $rows = array_merge($rows, [
            [__('apikeys.vat', ['pct' => round($d['taxRate'] * 100, 2)]), $sc['tax'], self::USD],
            [__('apikeys.total_vat'), $sc['grand'], self::USD],
            [__('apikeys.rate_title'), $d['idrRate'], self::IDR],
            [__('apikeys.xl_total_idr'), $sc['grand'] * $d['idrRate'], self::IDR],
        ]);

        $r = 3;
        foreach ($rows as [$label, $value, $format]) {
            if ($label !== null) {
                $sheet->setCellValue("A{$r}", $label);
                $sheet->setCellValue("B{$r}", $value);
                $sheet->getStyle("A{$r}")->getFont()->setBold(true);
                if ($format) {
                    $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode($format);
                }
                $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal('right');
            }
            $r++;
        }

        // Baris total dengan PPN disorot, sama seperti angka besar di halaman.
        $grandRow = $r - 3;
        $sheet->getStyle("A{$grandRow}:B{$grandRow}")->getFont()->setBold(true)->getColor()->setARGB(self::GREEN);

        // Pembagian per kategori (Maps / Places / Routes).
        $r++;
        $this->header($sheet, $r, [__('apikeys.cat_title'), __('apikeys.requests'), __('apikeys.est_cost'), __('apikeys.share_portion')]);
        foreach (self::CATEGORIES as [$label, $catOps]) {
            $r++;
            $counts = array_map(fn ($op) => $ops[$op] ?? 0, array_combine($catOps, $catOps));
            $catCost = AwsLocationService::estimateCost($counts);

            $sheet->fromArray([__($label), array_sum($counts), $catCost, $cost > 0 ? $catCost / $cost : 0], null, "A{$r}", true);
            $this->formats($sheet, $r, ['B' => self::INT, 'C' => self::USD, 'D' => '0.0%']);
        }

        $r += 2;
        // Disclaimer digabung A:D supaya teks panjangnya tidak ikut melebarkan kolom A.
        $sheet->setCellValue("A{$r}", __('apikeys.share_disclaimer'));
        $sheet->mergeCells("A{$r}:D{$r}");
        $sheet->getStyle("A{$r}")->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getStyle("A{$r}")->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF6B7280');
        $sheet->getRowDimension($r)->setRowHeight(48);

        $this->autosize($sheet, 'D');
    }

    private function daily(Worksheet $sheet, array $d): void
    {
        $sheet->setTitle($this->title(__('apikeys.xl_daily')));
        $this->header($sheet, 1, [__('apikeys.xl_date'), __('apikeys.requests')]);

        $r = 1;
        $end = Carbon::parse($d['endDate']);
        for ($day = Carbon::parse($d['startDate']); $day->lte($end); $day->addDay()) {
            $r++;
            $sheet->setCellValue("A{$r}", $day->format('Y-m-d'));
            $sheet->setCellValue("B{$r}", $d['metrics']['daily'][$day->format('Y-m-d')] ?? 0);
            $this->formats($sheet, $r, ['B' => self::INT]);
        }

        $this->totalRow($sheet, $r + 1, 'A', ['B' => self::INT], 2, $r);
        $this->autosize($sheet, 'B');
        $sheet->freezePane('A2');
    }

    private function operations(Worksheet $sheet, array $d): void
    {
        $sheet->setTitle($this->title(__('apikeys.ops_title')));
        $this->header($sheet, 1, [__('apikeys.op'), __('apikeys.requests'), __('apikeys.xl_price'), __('apikeys.est_cost')]);

        $r = 1;
        foreach ($d['metrics']['operations'] ?? [] as $op => $count) {
            $r++;
            $price = AwsLocationService::PRICING[$op] ?? 0;
            $sheet->fromArray([$op, $count, $price, ($count / 1000) * $price], null, "A{$r}", true);
            $this->formats($sheet, $r, ['B' => self::INT, 'C' => self::USD, 'D' => self::USD]);
        }

        $this->totalRow($sheet, $r + 1, 'A', ['B' => self::INT, 'D' => self::USD], 2, $r);

        // Lanjutan tagihan di bawah total operasi: service charge, PPN, total.
        $sc = $d['charge'];
        $lines = [];
        if ($sc['active']) {
            $lines[] = [__('servicecharge.line') . ' (' . ServiceCharge::basisLabel($sc) . ')', $sc['charge'], false];
        }
        $lines[] = [__('apikeys.vat', ['pct' => round($d['taxRate'] * 100, 2)]), $sc['tax'], false];
        $lines[] = [__('apikeys.total_vat'), $sc['grand'], true];

        $row = $r + 2;
        foreach ($lines as [$label, $value, $bold]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("D{$row}", $value);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode(self::USD);
            if ($bold) {
                $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true)->getColor()->setARGB(self::GREEN);
            }
            $row++;
        }

        $this->autosize($sheet, 'D');
        $sheet->freezePane('A2');
    }

    private function perKey(Worksheet $sheet, array $d): void
    {
        $sheet->setTitle($this->title(__('apikeys.share_per_key')));
        $this->header($sheet, 1, [__('apikeys.share_key_col'), __('apikeys.xl_label'), __('apikeys.requests'), __('apikeys.est_cost'), __('apikeys.share_portion'), __('apikeys.xl_note')]);

        $cost = AwsLocationService::estimateCost($d['metrics']['operations'] ?? []);

        $r = 1;
        foreach ($d['usage']['per_key'] as $row) {
            $r++;
            $sheet->fromArray([
                $row['name'],
                $row['label'] ?: '',
                $row['total'],
                $row['cost'],
                $cost > 0 ? $row['cost'] / $cost : 0,
                $row['has_data'] ? '' : __('apikeys.share_key_no_data'),
            ], null, "A{$r}", true);
            $this->formats($sheet, $r, ['C' => self::INT, 'D' => self::USD, 'E' => '0.0%']);
        }

        $this->totalRow($sheet, $r + 1, 'A', ['C' => self::INT, 'D' => self::USD], 2, $r);
        $this->autosize($sheet, 'F');
        $sheet->freezePane('A2');
    }

    private function header(Worksheet $sheet, int $row, array $labels): void
    {
        $sheet->fromArray($labels, null, "A{$row}");
        $range = 'A' . $row . ':' . chr(ord('A') + count($labels) - 1) . $row;

        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::GREEN);
    }

    /** Baris jumlah memakai rumus SUM, jadi tetap benar kalau klien menyunting isinya. */
    private function totalRow(Worksheet $sheet, int $row, string $labelCol, array $sumCols, int $from, int $to): void
    {
        $sheet->setCellValue("{$labelCol}{$row}", __('apikeys.xl_total'));

        foreach ($sumCols as $col => $format) {
            $sheet->setCellValue("{$col}{$row}", $to >= $from ? "=SUM({$col}{$from}:{$col}{$to})" : 0);
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($format);
        }

        $last = max(array_merge([$labelCol], array_keys($sumCols)));
        $style = $sheet->getStyle("{$labelCol}{$row}:{$last}{$row}");
        $style->getFont()->setBold(true);
        $style->getBorders()->getTop()->setBorderStyle('thin');
    }

    private function formats(Worksheet $sheet, int $row, array $formats): void
    {
        foreach ($formats as $col => $format) {
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($format);
        }
    }

    private function autosize(Worksheet $sheet, string $lastCol): void
    {
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /** Nama sheet Excel maksimal 31 karakter dan tanpa karakter tertentu. */
    private function title(string $title): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $title), 0, 31);
    }
}
