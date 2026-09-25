<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service charge yang ditagihkan ke klien di atas biaya AWS.
 *
 * Aturannya disimpan sebagai riwayat (berlaku mulai tanggal tertentu), bukan
 * satu nilai yang ditimpa — kalau tarif berubah, laporan periode lama tetap
 * memakai tarif lamanya. Baris tanpa company_id = tarif bawaan akun AWS; baris
 * dengan company_id = tarif khusus perusahaan itu. Baris perusahaan dengan
 * percent null berarti "kembali ikut tarif akun" mulai tanggal tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->nullable()->constrained('aws_accounts')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            // Persen dari biaya AWS (sebelum PPN).
            $table->decimal('percent', 6, 3)->nullable();
            // Tagihan minimum per bulan kalender, dalam Rupiah.
            $table->decimal('monthly_min_idr', 15, 2)->nullable();
            $table->date('effective_from');
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['aws_account_id', 'company_id', 'effective_from'], 'service_charges_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_charges');
    }
};
