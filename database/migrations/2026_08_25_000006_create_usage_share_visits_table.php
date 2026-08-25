<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak akses link laporan yang dibagikan ke klien. Link ini terbuka tanpa login,
 * jadi satu-satunya kendali yang tersisa adalah bisa melihat siapa saja yang
 * membukanya — dan mematikan link-nya kalau terlihat janggal.
 *
 * Kunjungan berulang dari pembaca yang sama dalam satu jendela waktu digabung
 * jadi satu baris (kolom hits) supaya tabelnya tidak membengkak karena refresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_share_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_share_id')->constrained('api_key_usage_shares')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('viewed_range', 40)->nullable();   // rentang tanggal yang dibuka
            $table->string('viewed_key')->nullable();          // tab key yang dipilih, kalau ada
            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['usage_share_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_share_visits');
    }
};
