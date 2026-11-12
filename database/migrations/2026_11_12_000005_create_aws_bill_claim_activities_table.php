<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak siapa melakukan apa pada pengajuan tagihan: dibuat, ditandai dibayar,
     * dikembalikan, dibatalkan. Kode pengajuan disalin supaya log tetap terbaca
     * walau pengajuannya sudah dibatalkan (dihapus).
     */
    public function up(): void
    {
        Schema::create('aws_bill_claim_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->foreignId('aws_bill_claim_id')->nullable()->constrained('aws_bill_claims')->nullOnDelete();
            $table->string('claim_code', 20);
            $table->string('action', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['aws_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aws_bill_claim_activities');
    }
};
