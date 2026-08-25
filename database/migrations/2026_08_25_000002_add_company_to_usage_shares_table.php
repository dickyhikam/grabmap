<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link laporan pemakaian sekarang bisa menunjuk satu perusahaan (menggabungkan
 * semua key miliknya), bukan cuma satu key. Tepat satu di antara company_id atau
 * key_name yang terisi — dijaga di model lewat ApiKeyUsageShare::enable*().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_key_usage_shares', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained('companies')->cascadeOnDelete();
        });

        // key_name kosong untuk link tingkat perusahaan.
        Schema::table('api_key_usage_shares', function (Blueprint $table) {
            $table->string('key_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('api_key_usage_shares', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
