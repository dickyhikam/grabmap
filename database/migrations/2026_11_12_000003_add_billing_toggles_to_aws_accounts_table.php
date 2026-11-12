<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per akun AWS: apakah tagihan ke klien memakai management fee dan VAT.
     * Akun yang ditagih langsung oleh AWS (mis. Akun Grab) tidak perlu keduanya,
     * jadi halaman Tagihan cukup menampilkan tagihan AWS.
     */
    public function up(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->boolean('bill_management_fee')->default(true)->after('notes');
            $table->boolean('bill_vat')->default(true)->after('bill_management_fee');
        });
    }

    public function down(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropColumn(['bill_management_fee', 'bill_vat']);
        });
    }
};
