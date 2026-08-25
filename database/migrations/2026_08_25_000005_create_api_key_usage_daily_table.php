<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemakaian harian per API key per operasi, disimpan permanen.
 *
 * Sebelumnya laporan hanya mengandalkan snapshot cache yang kuncinya mengandung
 * RENTANG TANGGAL, sehingga laporan untuk rentang yang belum pernah dibuka admin
 * selalu kosong. Dengan baris harian, rentang apa pun bisa dijawab dari database
 * tanpa menembak CloudWatch lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_usage_daily', function (Blueprint $table) {
            $table->id();
            // Null = kredensial .env.
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('key_name');
            $table->date('usage_date');
            $table->string('operation', 60);
            $table->unsignedBigInteger('request_count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['aws_account_id', 'key_name', 'usage_date', 'operation'], 'usage_daily_unique');
            $table->index(['aws_account_id', 'key_name', 'usage_date'], 'usage_daily_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_usage_daily');
    }
};
