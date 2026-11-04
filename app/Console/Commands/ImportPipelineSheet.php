<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyActivity;
use App\Models\PipelineStage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor lembar status klien tim ("B2B_G Clients – Status Update and Clarifications
 * Needed.xlsx") ke board pipeline — sekali jalan, setelah itu board yang jadi
 * sumber utama.
 *
 * Perusahaan yang sudah ada dicocokkan lewat nama/slug/singkatan dalam kurung
 * ("PT KAI" ↔ "PT Kereta Api Indonesia (KAI)"); sisanya dibuat baru dalam keadaan
 * nonaktif. Catatan dan klarifikasi tidak diimpor dua kali untuk perusahaan yang sama.
 */
class ImportPipelineSheet extends Command
{
    protected $signature = 'pipeline:import-sheet
        {file : Path file .xlsx lembar status klien}
        {--dry-run : Tampilkan rencana impor tanpa menyimpan apa pun}';

    protected $description = 'Import the team client status sheet into the company pipeline board';

    /** Judul kolom di lembar tim → kunci di sini. Dicari per judul, bukan per huruf kolom. */
    private const COLUMNS = [
        'name'     => 'company name',
        'website'  => 'company website',
        'needs'    => 'needs',
        'status'   => 'status',
        'stage'    => 'stage',
        'clarify'  => 'need to clarify / support',
        'response' => 'response',
        'mom'      => 'link to mom',
        'notes'    => 'notes',
        'pic'      => 'pic gfb',
    ];

    /** Isi kolom "Stage" di lembar → nama kolom board. */
    private const STAGES = [
        'meeting'    => 'Meeting',
        'poc'        => 'POC',
        'subscribed' => 'Subscribed',
    ];

    private const DROP_STAGE = 'Drop';

    public function handle(): int
    {
        $rows = $this->readRows($this->argument('file'));
        if ($rows === null) {
            return self::FAILURE;
        }

        $stages = PipelineStage::ordered()->keyBy(fn ($s) => mb_strtolower($s->name));
        $missing = collect(array_merge(array_values(self::STAGES), [self::DROP_STAGE]))
            ->reject(fn ($name) => $stages->has(mb_strtolower($name)));
        if ($missing->isNotEmpty()) {
            $this->error('Kolom board belum ada: ' . $missing->implode(', ') . '. Ada: ' . $stages->pluck('name')->implode(', '));
            return self::FAILURE;
        }

        $companies = Company::all();
        $plan = collect($rows)->map(fn ($row) => $this->plan($row, $companies, $stages));

        $this->table(
            ['Perusahaan', 'Cocok dengan', 'Kolom', 'Berhenti di', 'Klarifikasi', 'Catatan'],
            $plan->map(fn ($p) => [
                $p['row']['name'],
                $p['company']?->name ?? '— baru —',
                $p['stage']->name,
                $p['closed_from'] ?? '',
                count($p['clarifications']),
                $p['row']['notes'] ? 'ya' : '',
            ]),
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run — tidak ada yang disimpan.');
            return self::SUCCESS;
        }

        $made = 0;
        DB::transaction(function () use ($plan, &$made) {
            foreach ($plan as $p) {
                $made += $this->apply($p) ? 1 : 0;
            }
        });

        $this->info(sprintf('%d perusahaan diproses, %d dibuat baru.', $plan->count(), $made));

        return self::SUCCESS;
    }

    /** @return array<int, array<string, mixed>>|null */
    private function readRows(string $file): ?array
    {
        if (!is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return null;
        }

        $sheet = IOFactory::load($file)->getSheet(0);
        $header = [];
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $header[mb_strtolower(trim((string) $cell->getValue()))] = $cell->getColumn();
            }
        }

        $cols = [];
        foreach (self::COLUMNS as $key => $title) {
            if (!isset($header[$title])) {
                $this->error("Kolom \"{$title}\" tidak ada di baris judul.");
                return null;
            }
            $cols[$key] = $header[$title];
        }

        $rows = [];
        for ($r = 2; $r <= $sheet->getHighestDataRow(); $r++) {
            $cell = fn (string $key) => $sheet->getCell($cols[$key] . $r);
            $text = fn (string $key) => trim((string) $cell($key)->getValue());

            if ($text('name') === '') {
                continue;
            }

            // Website & MOM berupa hyperlink: teks sel bisa cuma judul ("MOM ...").
            $websiteLink = $cell('website')->getHyperlink()->getUrl();
            $momLink     = $cell('mom')->getHyperlink()->getUrl();

            $rows[] = [
                'name'     => $text('name'),
                'website'  => $websiteLink ?: (preg_match('~^https?://~i', $text('website')) ? $text('website') : null),
                'needs'    => $text('needs') ?: null,
                'status'   => mb_strtolower($text('status')),
                'stage'    => mb_strtolower($text('stage')),
                'clarify'  => $text('clarify'),
                'response' => $text('response'),
                'mom'      => $momLink ? ['label' => $text('mom') ?: null, 'url' => $momLink] : null,
                'notes'    => $text('notes') ?: null,
                'pic'      => trim(preg_replace('/^gfb\s*team\s*:\s*/i', '', $text('pic'))) ?: null,
            ];
        }

        return $rows;
    }

    private function plan(array $row, $companies, $stages): array
    {
        $mapped = isset(self::STAGES[$row['stage']]) ? $stages[mb_strtolower(self::STAGES[$row['stage']])] : null;
        $drop   = $row['status'] === 'drop';

        return [
            'row'            => $row,
            'company'        => $this->match($row['name'], $companies),
            // Tanpa tahap di lembar → kolom pertama (Prospek).
            'stage'          => $drop ? $stages[mb_strtolower(self::DROP_STAGE)] : ($mapped ?? $stages->first()),
            'closed_from'    => $drop ? $mapped?->name : null,
            'clarifications' => $this->clarifications($row),
        ];
    }

    /** Cocokkan nama lembar dengan perusahaan yang sudah ada. */
    private function match(string $name, $companies): ?Company
    {
        $norm  = fn (string $v) => Str::of($v)->lower()->replaceMatches('/^pt\.?\s+/', '')->squish()->toString();
        $want  = $norm($name);
        $slug  = Str::slug($name);

        return $companies->first(function (Company $c) use ($norm, $want, $slug) {
            if ($norm($c->name) === $want || $c->slug === $slug) {
                return true;
            }
            // "PT Kereta Api Indonesia (KAI)" dikenal juga sebagai "KAI".
            return preg_match('/\(([^)]+)\)\s*$/', $c->name, $m) && $norm($m[1]) === $want;
        });
    }

    /**
     * Pecah kolom "Need to Clarify / Support" per butir bernomor. Statusnya dari
     * kolom "Response": Clarified = selesai, isi lain = menunggu (isinya jadi
     * jawaban), kosong = terbuka — kecuali kliennya sudah Completed.
     *
     * @return array<int, array{question: string, answer: ?string, status: string}>
     */
    private function clarifications(array $row): array
    {
        if ($row['clarify'] === '') {
            return [];
        }

        $items = preg_split('/(?:^|\n)\s*\d+\.\s+/', $row['clarify'], -1, PREG_SPLIT_NO_EMPTY);
        $items = array_values(array_filter(array_map('trim', $items)));

        $response = $row['response'];
        [$status, $answer] = match (true) {
            stripos($response, 'clarified') !== false => ['clarified', null],
            $response !== ''                          => ['waiting', $response],
            $row['status'] === 'completed'            => ['clarified', null],
            default                                   => ['open', null],
        };

        return array_map(fn ($q) => ['question' => $q, 'answer' => $answer, 'status' => $status], $items);
    }

    /** @return bool true kalau perusahaannya dibuat baru. */
    private function apply(array $p): bool
    {
        $row = $p['row'];
        $source = ['source' => 'sheet'];
        $company = $p['company'];
        $isNew = !$company;

        if ($isNew) {
            // Belum tentu jadi klien — halaman peta /{slug} baru dibuka setelah diaktifkan.
            $company = Company::create([
                'name'              => $row['name'],
                'slug'              => Company::uniqueSlug($row['name']),
                'is_active'         => false,
                'pipeline_stage_id' => $p['stage']->id,
            ]);
            $company->ensureDefaultFeatures();
        } else {
            $company->moveToStage($p['stage'], $source);
        }

        // Tahap sebelum berhenti diambil dari lembar, bukan dari kolom board lama.
        $company->closed_from = $p['closed_from'];

        // Isian profil hanya mengisi yang masih kosong — data di board tidak ditimpa.
        $company->website      ??= $row['website'];
        $company->needs        ??= $row['needs'];
        $company->pic_external ??= $row['pic'];
        if ($row['mom'] && !collect($company->mom_links ?? [])->contains('url', $row['mom']['url'])) {
            $company->mom_links = array_merge($company->mom_links ?? [], [$row['mom']]);
        }
        $company->save();

        // Catatan & klarifikasi hanya sekali per perusahaan, supaya impor ulang tidak menggandakan.
        if (!$company->activities()->where('type', 'imported')->exists()) {
            CompanyActivity::log($company, 'imported', null, $source);

            foreach ($p['clarifications'] as $item) {
                $company->clarifications()->create($item);
            }
            if ($row['notes']) {
                CompanyActivity::log($company, 'note', $row['notes'], $source);
            }
        }

        return $isNew;
    }
}
