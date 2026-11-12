<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Invoice resmi AWS (Invoicing API ListInvoiceSummaries) untuk satu akun.
 * Satu bulan bisa punya lebih dari satu baris: invoice utama plus credit memo
 * atau invoice dari entitas lain (mis. AWS Marketplace).
 */
class AwsInvoiceSummary extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'aws_account_id', 'invoice_id', 'billing_month', 'invoice_type', 'original_invoice_id',
        'invoicing_entity', 'issued_at', 'due_at', 'base_amount', 'base_currency',
        'tax_currency_amount', 'tax_currency', 'payment_amount', 'payment_currency', 'exchange_rate', 'synced_at',
    ];

    protected $casts = [
        'billing_month'       => 'date',
        'issued_at'           => 'datetime',
        'due_at'              => 'datetime',
        'synced_at'           => 'datetime',
        'base_amount'         => 'float',
        'tax_currency_amount' => 'float',
        'payment_amount'      => 'float',
        'exchange_rate'       => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AwsAccount::class, 'aws_account_id');
    }

    public function isCreditMemo(): bool
    {
        return $this->invoice_type === 'CREDIT_MEMO';
    }

    /**
     * Jatuh tempo sudah lewat. Bukan berarti belum dibayar — API tidak memberi
     * status bayar. Invoice kartu kredit ditagih otomatis dengan jatuh tempo di
     * hari terbit (bahkan beberapa jam sebelum terbit); itu tidak dianggap lewat.
     */
    public function isPastDue(): bool
    {
        if ($this->due_at === null || !$this->due_at->isPast()) {
            return false;
        }

        return !$this->issued_at || $this->due_at->gt($this->issued_at->copy()->addDay());
    }

    /** Jumlah dalam Rupiah kalau salah satu mata uang invoice adalah IDR. */
    public function idrAmount(): ?float
    {
        if ($this->tax_currency === 'IDR') {
            return $this->tax_currency_amount;
        }

        return $this->payment_currency === 'IDR' ? $this->payment_amount : null;
    }

    /**
     * Invoice resmi ini dalam bentuk AwsInvoice (tidak disimpan), dipakai halaman
     * Tagihan selama admin belum mengisi invoice manual. Hanya invoice utama
     * dalam USD yang punya jumlah Rupiah.
     */
    public function asInvoice(): ?AwsInvoice
    {
        $idr = $this->idrAmount();
        if ($this->isCreditMemo() || $this->base_currency !== 'USD' || !$this->base_amount || !$idr) {
            return null;
        }

        return (new AwsInvoice())->forceFill([
            'aws_account_id' => $this->aws_account_id,
            'month'          => $this->billing_month,
            'invoice_number' => $this->invoice_id,
            'total_usd'      => $this->base_amount,
            'total_idr'      => $idr,
            'created_by'     => 'AWS',
        ]);
    }

    /** @return Collection<int, self> invoice bulan itu, invoice utama dulu, lalu credit memo. */
    public static function forMonth(int $accountId, Carbon $month): Collection
    {
        return static::where('aws_account_id', $accountId)
            ->whereDate('billing_month', $month->copy()->startOfMonth()->format('Y-m-d'))
            ->orderByRaw("invoice_type = 'CREDIT_MEMO'")
            ->orderBy('issued_at')
            ->get();
    }

    /**
     * Ganti seluruh invoice akun untuk bulan-bulan yang ditarik. Bulan di luar
     * rentang tidak disentuh.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $months  Y-m-01 yang tercakup penarikan
     */
    public static function store(int $accountId, array $rows, array $months): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($accountId, $rows, $months) {
            $ids = array_column($rows, 'invoice_id');
            static::where('aws_account_id', $accountId)
                ->where(function ($q) use ($months) {
                    foreach ($months as $m) {
                        $q->orWhereDate('billing_month', $m);
                    }
                })
                ->when($ids, fn ($q) => $q->whereNotIn('invoice_id', $ids))
                ->delete();

            foreach ($rows as $row) {
                static::updateOrCreate(
                    ['aws_account_id' => $accountId, 'invoice_id' => $row['invoice_id']],
                    $row + ['synced_at' => now()],
                );
            }
        });
    }
}
