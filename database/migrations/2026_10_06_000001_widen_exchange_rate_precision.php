<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurs tagihan AWS punya empat desimal (mis. 17.990,2746 untuk Agustus 2026).
 * Dua desimal membuat total rupiah meleset beberapa rupiah dari invoice, yang
 * jadi masalah karena laporan ini dipakai klien untuk mencocokkan tagihan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->decimal('rate', 14, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->decimal('rate', 12, 2)->change();
        });
    }
};
