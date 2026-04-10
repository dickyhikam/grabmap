<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->index();
            $table->string('action', 30)->index();
            $table->string('status', 10);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('failed_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};
