<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jumlah route CalculateRouteMatrix per akun per hari.
 *
 * AWS menagih Matrix per route (origin × destination), sedangkan CloudWatch
 * hanya mencatat jumlah request. Jumlah route diambil dari Cost Explorer
 * (tingkat akun, tanpa rincian API key) lalu dibagi ke key sesuai porsi
 * request Matrix-nya. Oktober 2026: 32 request = 7.852 route di tagihan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrix_route_daily', function (Blueprint $table) {
            $table->id();
            // Null = kredensial .env.
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->date('usage_date');
            // Route yang ditagih (Cost Explorer) dan request Matrix seluruh akun (CloudWatch).
            $table->decimal('routes', 16, 2)->default(0);
            $table->unsignedBigInteger('requests')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['aws_account_id', 'usage_date'], 'matrix_route_daily_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_route_daily');
    }
};
