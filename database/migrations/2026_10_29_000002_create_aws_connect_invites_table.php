<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link undangan untuk klien menghubungkan akun AWS-nya sendiri lewat AssumeRole.
     * Sekali pakai dan punya masa berlaku; External ID dibuat di sini supaya bisa
     * ditulis di tutorial sebelum klien membuat role-nya.
     */
    public function up(): void
    {
        Schema::create('aws_connect_invites', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('token', 64)->unique();
            $table->string('label');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('trusted_account_id', 12);   // akun AWS sumber yang dipercaya role klien
            $table->string('external_id');
            $table->string('region')->default('ap-southeast-1');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip', 45)->nullable();
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aws_connect_invites');
    }
};
