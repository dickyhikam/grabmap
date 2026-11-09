<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan AWS per akun per bulan, supaya halaman Tagihan bisa disandingkan
     * dengan PDF invoice AWS baris per baris.
     *
     * - aws_service_cost_monthly: biaya per service dari Cost Explorer, termasuk
     *   baris "Tax" (PPN dari PT AWS Indonesia), CloudWatch, S3, dan Cost Explorer
     *   sendiri. aws_usage_type_daily hanya memuat Amazon Location Service, jadi
     *   tanpa tabel ini totalnya tidak akan pernah sama dengan invoice.
     * - aws_invoices: angka yang tertulis di PDF invoice (USD dan IDR), diketik
     *   admin. Dari situ kurs bulan itu diturunkan dan dicocokkan dengan sync.
     */
    public function up(): void
    {
        Schema::create('aws_service_cost_monthly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->date('month');
            $table->string('service', 160);
            $table->decimal('cost_usd', 16, 6)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->unique(['aws_account_id', 'month', 'service'], 'service_cost_monthly_unique');
        });

        Schema::create('aws_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->date('month');
            $table->string('invoice_number', 80)->nullable();
            $table->decimal('total_usd', 14, 2);
            $table->decimal('total_idr', 18, 2);
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->string('created_by', 120)->nullable();
            $table->timestamps();
            $table->unique(['aws_account_id', 'month']);
        });

        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->timestamp('service_costs_synced_at')->nullable()->after('usage_types_error');
            $table->string('service_costs_error')->nullable()->after('service_costs_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropColumn(['service_costs_synced_at', 'service_costs_error']);
        });

        Schema::dropIfExists('aws_invoices');
        Schema::dropIfExists('aws_service_cost_monthly');
    }
};
