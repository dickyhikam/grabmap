<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan API key yang dinonaktifkan dari panel.
 *
 * AWS Location Service TIDAK punya sakelar aktif/nonaktif untuk API key — yang
 * ada hanya masa berlaku. Jadi "nonaktifkan" dikerjakan dengan memajukan
 * ExpireTime ke sekarang, dan masa berlaku aslinya disimpan di sini supaya
 * "aktifkan lagi" bisa mengembalikannya persis seperti semula.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_disables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('key_name');
            // Null = dulunya memang tanpa masa berlaku.
            $table->timestamp('previous_expire_time')->nullable();
            $table->string('disabled_by')->nullable();
            $table->timestamps();

            $table->unique(['aws_account_id', 'key_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_disables');
    }
};
