<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun AWS milik klien seluruhnya milik satu perusahaan: setiap key di dalamnya,
     * termasuk yang dibuat klien belakangan, otomatis tercatat sebagai key perusahaan
     * itu. Undangan mencatat link dashboard yang dibuat saat klien terhubung.
     */
    public function up(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->foreignId('owner_company_id')->nullable()->after('source_account_id')
                ->constrained('companies')->nullOnDelete();
        });

        Schema::table('aws_connect_invites', function (Blueprint $table) {
            $table->unsignedInteger('keys_found')->nullable()->after('aws_account_id');
            $table->foreignId('usage_share_id')->nullable()->after('keys_found')
                ->constrained('api_key_usage_shares')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aws_connect_invites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usage_share_id');
            $table->dropColumn('keys_found');
        });

        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_company_id');
        });
    }
};
