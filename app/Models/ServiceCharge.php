<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Service charge di atas biaya AWS: persen dari biaya, dengan tagihan minimum
 * per bulan kalender dalam Rupiah. Yang ditagihkan adalah yang lebih besar di
 * antara keduanya. PPN dihitung dari (biaya AWS + service charge).
 *
 * Tarif bawaan menempel di akun AWS; perusahaan boleh punya tarif sendiri.
 */
class ServiceCharge extends Model
{
    protected $fillable = [
        'aws_account_id', 'company_id', 'percent', 'monthly_min_idr', 'effective_from', 'created_by',
    ];

    protected $casts = [
        'percent'         => 'float',
        'monthly_min_idr' => 'float',
        'effective_from'  => 'date',
    ];

    public function awsAccount(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Baris perusahaan yang menyatakan "ikut tarif akun". */
    public function inherits(): bool
    {
        return $this->company_id !== null && $this->percent === null;
    }

    public function isZero(): bool
    {
        return (float) $this->percent <= 0 && (float) $this->monthly_min_idr <= 0;
    }

    /**
     * Tarif yang berlaku pada tanggal tertentu. Tarif khusus perusahaan menang;
     * kalau perusahaan tidak punya (atau sedang "ikut akun"), pakai tarif akun.
     */
    public static function ruleFor(?int $accountId, ?int $companyId, string $date): ?self
    {
        if ($companyId) {
            $own = static::query()
                ->where('company_id', $companyId)
                ->whereDate('effective_from', '<=', $date)
                ->orderByDesc('effective_from')->orderByDesc('id')
                ->first();

            if ($own && !$own->inherits()) {
                return $own;
            }
        }

        if (!$accountId) {
            return null;
        }

        return static::query()
            ->where('aws_account_id', $accountId)
            ->whereNull('company_id')
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')->orderByDesc('id')
            ->first();
    }

    /** Perusahaan pemilik satu API key — lewat daftar key perusahaan, lalu key utama lama. */
    public static function companyIdForKey(?int $accountId, string $keyName): ?int
    {
        return CompanyApiKey::ownerOf($accountId, $keyName)?->id
            ?? Company::query()
                ->where('aws_api_key_name', $keyName)
                ->when($accountId, fn ($q) => $q->where('aws_account_id', $accountId))
                ->value('id');
    }

    /** Riwayat tarif akun (companyId null) atau perusahaan, terbaru dulu. */
    public static function history(?int $accountId, ?int $companyId = null): Collection
    {
        return static::query()
            ->when($companyId,
                fn ($q) => $q->where('company_id', $companyId),
                fn ($q) => $q->where('aws_account_id', $accountId)->whereNull('company_id'))
            ->orderByDesc('effective_from')->orderByDesc('id')
            ->get();
    }

    /**
     * Simpan tarif baru dari formulir. Tidak membuat baris kalau nilainya sama
     * dengan yang sudah berlaku di tanggal itu; kalau tanggalnya sama dengan
     * baris yang ada, baris itu yang diperbarui (koreksi salah ketik).
     *
     * @param  float|null  $percent  null = "ikut akun" (hanya untuk perusahaan)
     */
    public static function record(?int $accountId, ?int $companyId, ?float $percent, ?float $minIdr, string $effectiveFrom, ?string $by): void
    {
        $scope = static::query()
            ->when($companyId,
                fn ($q) => $q->where('company_id', $companyId),
                fn ($q) => $q->where('aws_account_id', $accountId)->whereNull('company_id'));

        $current = (clone $scope)
            ->whereDate('effective_from', '<=', $effectiveFrom)
            ->orderByDesc('effective_from')->orderByDesc('id')
            ->first();

        $minIdr = $percent === null ? null : ($minIdr ?? 0);

        if ($current && $current->percent === $percent && $current->monthly_min_idr === $minIdr) {
            return;
        }

        // Perusahaan yang belum pernah punya tarif sendiri dan tetap "ikut akun"
        // tidak perlu dicatat apa-apa.
        if (!$current && $companyId && $percent === null) {
            return;
        }

        // Begitu juga cakupan yang belum punya tarif dan tetap diisi nol.
        if (!$current && $percent !== null && $percent <= 0 && $minIdr <= 0) {
            return;
        }

        $same = (clone $scope)->whereDate('effective_from', $effectiveFrom)->first();

        $values = [
            'percent'         => $percent,
            'monthly_min_idr' => $minIdr,
            'created_by'      => $by,
        ];

        if ($same) {
            $same->update($values);
            return;
        }

        static::create($values + [
            'aws_account_id' => $accountId,
            'company_id'     => $companyId,
            'effective_from' => $effectiveFrom,
        ]);
    }

    /** Aturan validasi field service charge di formulir akun & perusahaan. */
    public static function validationRules(bool $forCompany): array
    {
        return [
            'sc_mode'      => $forCompany ? ['required', 'in:inherit,custom'] : ['nullable'],
            'sc_percent'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sc_min_idr'   => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'sc_effective' => ['nullable', 'date'],
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'sc_percent.min' => __('servicecharge.err_percent'),
            'sc_percent.max' => __('servicecharge.err_percent'),
            'sc_min_idr.min' => __('servicecharge.err_min'),
        ];
    }

    /** Simpan isian formulir; companyId null = tarif bawaan akun. */
    public static function saveFromRequest(\Illuminate\Http\Request $request, ?int $accountId, ?int $companyId = null): void
    {
        $inherit = $companyId && $request->input('sc_mode') === 'inherit';

        static::record(
            $accountId,
            $companyId,
            $inherit ? null : (float) $request->input('sc_percent', 0),
            $inherit ? null : (float) $request->input('sc_min_idr', 0),
            $request->input('sc_effective') ?: now()->wib()->toDateString(),
            $request->user()?->name,
        );
    }

    /**
     * Rincian tagihan untuk satu rentang laporan.
     *
     * Persen dihitung dari biaya AWS seluruh rentang. Minimum bulanan dipotong
     * sesuai jumlah hari rentang di tiap bulan kalender (10 hari di bulan 30
     * hari = 10/30 minimum). Tarif yang dipakai = tarif yang berlaku di tanggal
     * akhir rentang. Semua angka hasil dalam USD, kecuali yang berakhiran _idr.
     */
    public static function breakdown(?self $rule, float $costUsd, string $startDate, string $endDate, float $idrRate, float $taxRate): array
    {
        $percent = $rule ? (float) $rule->percent : 0.0;
        $minMonthly = $rule ? (float) $rule->monthly_min_idr : 0.0;

        $minIdr = $minMonthly > 0 ? $minMonthly * self::monthFraction($startDate, $endDate) : 0.0;
        $minUsd = $idrRate > 0 ? $minIdr / $idrRate : 0.0;
        $byPercent = $costUsd * $percent / 100;

        $charge = max($byPercent, $minUsd);
        $basis = $charge <= 0 ? null : ($minUsd > $byPercent ? 'minimum' : 'percent');

        $tax = ($costUsd + $charge) * $taxRate;

        return [
            'cost'            => $costUsd,
            'percent'         => $percent,
            'monthly_min_idr' => $minMonthly,
            'min_idr'         => $minIdr,
            'charge'          => $charge,
            'basis'           => $basis,
            'active'          => $charge > 0,
            'tax'             => $tax,
            'grand'           => $costUsd + $charge + $tax,
        ];
    }

    /** ruleFor() + breakdown() sekaligus — bentuk yang dipakai controller. */
    public static function calculate(?int $accountId, ?int $companyId, float $costUsd, string $startDate, string $endDate, float $idrRate, float $taxRate): array
    {
        return static::breakdown(
            static::ruleFor($accountId, $companyId, $endDate),
            $costUsd, $startDate, $endDate, $idrRate, $taxRate,
        );
    }

    /** Jumlah "bulan" yang tercakup rentang, dihitung per bulan kalender. */
    public static function monthFraction(string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $total = 0.0;

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor = $cursor->copy()->addMonthNoOverflow()->startOfMonth()) {
            $monthEnd = $cursor->copy()->endOfMonth()->startOfDay();
            $segmentEnd = $end->lt($monthEnd) ? $end : $monthEnd;
            $days = (int) $cursor->diffInDays($segmentEnd) + 1;
            $total += $days / $cursor->daysInMonth;
        }

        return $total;
    }

    /** Teks singkat dasar hitungan, untuk label baris di laporan & invoice. */
    public static function basisLabel(array $b, ?string $locale = null): string
    {
        if ($b['basis'] === 'minimum') {
            return __('servicecharge.basis_min', ['amount' => 'Rp ' . number_format($b['monthly_min_idr'], 0, ',', '.')], $locale);
        }

        return rtrim(rtrim(number_format($b['percent'], 3, ',', '.'), '0'), ',') . '%';
    }
}
