<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu perusahaan boleh punya beberapa link laporan, dan tiap link memilih
 * sendiri key mana yang dicakup — bisa satu key, bisa beberapa.
 *
 * Link TANPA baris di sini berarti "semua key perusahaan": key yang ditambahkan
 * belakangan ikut otomatis tanpa perlu mengubah link-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_key_usage_shares', function (Blueprint $table) {
            // Nama link supaya beberapa token dalam satu perusahaan bisa dibedakan.
            $table->string('label')->nullable()->after('company_id');
        });

        Schema::create('usage_share_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_share_id')->constrained('api_key_usage_shares')->cascadeOnDelete();
            $table->foreignId('company_api_key_id')->constrained('company_api_keys')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['usage_share_id', 'company_api_key_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_share_keys');

        Schema::table('api_key_usage_shares', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
