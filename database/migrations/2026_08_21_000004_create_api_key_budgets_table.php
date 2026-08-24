<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ambang peringatan biaya per API key. Berbeda dengan ambang global di app_settings,
 * batas ini menempel pada satu key di satu akun AWS — nama key hanya unik dalam
 * akunnya sendiri, jadi pasangan (akun, nama key) yang dijadikan kunci.
 *
 * Catatan: ini peringatan sisi aplikasi, bukan AWS Budgets. AWS tidak bisa memecah
 * biaya per API key Location Service, jadi hanya panel ini yang bisa mengingatkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_budgets', function (Blueprint $table) {
            $table->id();
            // Null = kredensial .env (belum ada akun tersimpan). Akun dihapus =
            // batasnya ikut hilang; kalau di-null-kan, barisnya jadi yatim dan
            // bisa bentrok dengan batas milik jalur .env yang namanya sama.
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('key_name');
            $table->decimal('limit_usd', 10, 2);
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['aws_account_id', 'key_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_budgets');
    }
};
