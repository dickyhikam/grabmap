<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link publik read-only untuk laporan pemakaian per API key.
 * Terpisah dari api_key_budgets supaya share bisa diaktifkan tanpa batas biaya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_key_usage_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('key_name');
            $table->string('share_token', 64)->unique();
            $table->boolean('share_enabled')->default(false);
            $table->string('share_created_by')->nullable();
            $table->timestamp('share_last_accessed_at')->nullable();
            $table->timestamp('share_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['aws_account_id', 'key_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_usage_shares');
    }
};
