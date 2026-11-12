<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kurs USD→IDR persis yang dipakai AWS di invoice (CurrencyExchangeDetails.Rate),
     * supaya halaman Tagihan tidak perlu menebak kurs dari total yang sudah dibulatkan.
     */
    public function up(): void
    {
        Schema::table('aws_invoice_summaries', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('payment_currency');
        });
    }

    public function down(): void
    {
        Schema::table('aws_invoice_summaries', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
