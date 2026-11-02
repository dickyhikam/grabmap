<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor file uji akurasi geocoding dari SiCepat ("Check Accurary Grapmap - Sample.xlsx")
 * menjadi data halaman /sicepat.
 *
 * Hasilnya disimpan di storage privat, bukan di repo atau folder public, karena
 * isinya alamat dan titik rumah pelanggan SiCepat.
 */
class ImportSicepatSample extends Command
{
    public const PATH = 'demos/sicepat-geocode.json';

    protected $signature = 'demo:sicepat-import {file : Path file .xlsx dari SiCepat}';

    protected $description = 'Import the SiCepat geocoding accuracy sample for the /sicepat demo page';

    // Ada nomor rumah / blok di teks alamat.
    private const HAS_NUMBER = '/(\bno\b\s*[.:]?\s*[a-z]?\d|\bnomor\b\s*\d|\bblok\b|\bblk\b|\bjalan\s+[\w\s]+\s\d{1,4}[a-z]?\b|\bjl\.?\s*[\w\s.]+\s\d{1,4}[a-z]?\b)/iu';

    // Patokan atau pesan untuk kurir yang ikut terkirim sebagai alamat.
    private const HAS_NOTE = '/(\(|\bdekat\b|\bdeket\b|\bdepan\b|\bbelakang\b|\bsamping\b|\bsebelah\b|\bseberang\b|\bwarung\b|\bkontrakan\b|\bkontrkan\b|\brumah\s+(ibu|bu|pak|bapak|hj)\b|\bwarna\b|\bcat\b|\bpagar\b|\btolong\b|\bturun\b|\bujung\b|\bpatokan\b|\bmasuk\b|\bbekas\b|\btoko\b|\bkios\b|\bbengkel\b)/iu';

    private const COLUMNS = [
        'partner' => 'Partner',
        'district' => 'DESTINATION_DISTRICT',
        'city' => 'DESTINATION_CITY',
        'address' => 'CONSIGNEE_ADDRESS',
        'g_lat' => 'lat_grapmap',
        'g_lng' => 'long_grapmap',
        'p_lat' => 'lat_dlv',
        'p_lng' => 'long_dlv',
        'diff' => 'difference (m)',
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        $book = IOFactory::load($file);
        $sheet = $book->getSheetByName('Sheet1') ?? $book->getSheet(0);
        $table = $sheet->toArray(null, true, false, false);

        $header = array_map(fn ($v) => trim((string) $v), array_shift($table) ?? []);
        $index = [];
        foreach (self::COLUMNS as $key => $name) {
            $pos = array_search($name, $header, true);
            if ($pos === false) {
                $this->error("Kolom \"{$name}\" tidak ada di baris pertama.");
                return self::FAILURE;
            }
            $index[$key] = $pos;
        }

        $rows = [];
        $mismatch = 0;
        foreach ($table as $line) {
            if (blank($line[$index['partner']] ?? null)) {
                continue;
            }

            $address = trim(preg_replace('/\s+/u', ' ', (string) $line[$index['address']]));
            [$gLat, $gLng, $pLat, $pLng] = array_map(
                fn ($k) => (float) $line[$index[$k]],
                ['g_lat', 'g_lng', 'p_lat', 'p_lng'],
            );

            // Jarak dihitung sendiri, lalu dicocokkan dengan kolom SiCepat.
            $distance = self::haversine($gLat, $gLng, $pLat, $pLng);
            if (abs($distance - (float) $line[$index['diff']]) > max(1, $distance * 0.01)) {
                $mismatch++;
            }

            $hasNumber = (bool) preg_match(self::HAS_NUMBER, $address);
            $hasNote = (bool) preg_match(self::HAS_NOTE, $address);

            $rows[] = [
                'i'  => count($rows) + 1,
                'pt' => (string) $line[$index['partner']],
                'ds' => (string) $line[$index['district']],
                'ct' => (string) $line[$index['city']],
                'ad' => $address,
                'gl' => round($gLat, 7),
                'gn' => round($gLng, 7),
                'pl' => round($pLat, 7),
                'pn' => round($pLng, 7),
                'd'  => round($distance, 1),
                // Kategori mengikuti batas SiCepat: 50 / 100 / 200 m.
                'c'  => $distance <= 50 ? 1 : ($distance <= 100 ? 2 : ($distance <= 200 ? 3 : 4)),
                // 1 nomor tanpa catatan, 2 nomor + catatan, 3 tanpa nomor + catatan, 4 tanpa keduanya.
                'q'  => $hasNumber ? ($hasNote ? 2 : 1) : ($hasNote ? 3 : 4),
            ];
        }

        Storage::disk('local')->put(self::PATH, json_encode([
            'source'      => basename($file),
            'imported_at' => now()->toIso8601String(),
            'rows'        => $rows,
        ], JSON_UNESCAPED_UNICODE));

        $this->info(count($rows) . ' baris diimpor ke storage/app/private/' . self::PATH);
        if ($mismatch) {
            $this->warn("{$mismatch} baris jaraknya beda lebih dari 1% dari kolom \"difference (m)\".");
        }

        return self::SUCCESS;
    }

    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $h = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return 2 * 6371000 * asin(sqrt($h));
    }
}
