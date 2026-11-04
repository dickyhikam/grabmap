<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perusahaan di board pipeline: pindah tahap, ganti PIC / langkah
 * berikutnya, dan catatan bebas. Nama tahap disalin ke `meta` saat kejadian,
 * supaya riwayatnya tetap terbaca walau tahapnya kemudian diganti nama atau dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);   // created | moved | updated | note
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_activities');
    }
};
