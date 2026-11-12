<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ringkasan invoice resmi dari AWS Invoicing API (ListInvoiceSummaries).
     * Dari sini halaman Tagihan tahu apakah tagihan bulan itu sudah terbit
     * (Issued) atau masih estimasi (Pending), nomor invoice, tanggal terbit,
     * dan jatuh tempo. Status bayar (Paid/Unpaid) tidak tersedia di API AWS.
     *
     * Invoice hanya terbit di payer account; member account Organization
     * akan selalu kosong.
     */
    public function up(): void
    {
        Schema::create('aws_invoice_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->string('invoice_id', 80);
            $table->date('billing_month');
            $table->string('invoice_type', 20)->default('INVOICE');
            $table->string('original_invoice_id', 80)->nullable();
            $table->string('invoicing_entity', 160)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->decimal('base_amount', 16, 2)->nullable();
            $table->string('base_currency', 3)->nullable();
            $table->decimal('tax_currency_amount', 18, 2)->nullable();
            $table->string('tax_currency', 3)->nullable();
            $table->decimal('payment_amount', 18, 2)->nullable();
            $table->string('payment_currency', 3)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->unique(['aws_account_id', 'invoice_id']);
            $table->index(['aws_account_id', 'billing_month']);
        });

        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->timestamp('invoices_synced_at')->nullable()->after('service_costs_error');
            $table->string('invoices_error')->nullable()->after('invoices_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropColumn(['invoices_synced_at', 'invoices_error']);
        });

        Schema::dropIfExists('aws_invoice_summaries');
    }
};
