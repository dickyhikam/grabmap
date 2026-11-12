<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pelacak pengajuan penggantian tagihan AWS (mis. tagihan Akun Grab yang
     * terpotong dari kartu kredit pribadi lalu diajukan ke Grab).
     *
     * Satu pengajuan berisi satu atau beberapa bulan; satu bulan hanya bisa
     * masuk satu pengajuan (unik akun + bulan di item). Bulan tanpa item =
     * "belum diajukan". Batal = pengajuan dihapus, bulannya kembali bebas.
     */
    public function up(): void
    {
        Schema::create('aws_bill_claims', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('status', 20)->default('submitted'); // submitted | paid
            $table->boolean('include_fee')->default(false);
            $table->decimal('fee_percent', 6, 3)->default(0);
            $table->boolean('include_vat')->default(false);
            $table->decimal('vat_rate', 6, 4)->default(0);
            $table->decimal('aws_idr', 18, 2)->default(0);
            $table->decimal('fee_idr', 18, 2)->default(0);
            $table->decimal('vat_idr', 18, 2)->default(0);
            $table->decimal('total_idr', 18, 2)->default(0);
            $table->decimal('total_usd', 14, 2)->default(0);
            $table->date('submitted_on');
            $table->date('paid_on')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_name')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('created_by', 120)->nullable();
            $table->timestamps();
            $table->unique(['aws_account_id', 'number']);
        });

        Schema::create('aws_bill_claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_bill_claim_id')->constrained('aws_bill_claims')->cascadeOnDelete();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->date('month');
            $table->string('invoice_id', 80)->nullable();
            $table->decimal('usd', 14, 2)->default(0);
            $table->decimal('aws_idr', 18, 2)->default(0);
            $table->decimal('fee_idr', 18, 2)->default(0);
            $table->decimal('vat_idr', 18, 2)->default(0);
            $table->decimal('total_idr', 18, 2)->default(0);
            $table->unique(['aws_account_id', 'month']);
        });

        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->boolean('claims_enabled')->default(false)->after('bill_vat');
        });
    }

    public function down(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropColumn('claims_enabled');
        });
        Schema::dropIfExists('aws_bill_claim_items');
        Schema::dropIfExists('aws_bill_claims');
    }
};
