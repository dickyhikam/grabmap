<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link publik halaman Tagihan satu akun AWS (untuk Tim Alfa / perusahaan).
     * Satu baris per akun; mematikan link cukup is_active = false, mengganti
     * link = token baru sehingga link lama langsung mati.
     */
    public function up(): void
    {
        Schema::create('aws_billing_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->unique()->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->string('created_by', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aws_billing_shares');
    }
};
