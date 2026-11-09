<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal potong tagihan satu akun dalam satu bulan. cuts = [10] berarti
     * bulan itu dipecah 1–10 dan 11–akhir bulan (mis. 1–10 sudah ditagih di PO
     * sebelumnya). Halaman Tagihan tetap sebulan penuh; potongan hanya membagi
     * angkanya per periode.
     */
    public function up(): void
    {
        Schema::create('aws_billing_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->date('month');
            $table->json('cuts');
            $table->string('created_by', 120)->nullable();
            $table->timestamps();
            $table->unique(['aws_account_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aws_billing_splits');
    }
};
