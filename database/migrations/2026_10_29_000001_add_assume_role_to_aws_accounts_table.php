<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun milik klien bisa dihubungkan lewat sts:AssumeRole: klien membuat role di
     * akunnya, kita hanya menyimpan ARN + External ID. Kredensial sementaranya diminta
     * memakai IAM user akun sumber (akun Grab sendiri) — tidak ada secret klien di DB.
     */
    public function up(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->string('role_arn')->nullable()->after('secret_access_key');
            $table->string('external_id')->nullable()->after('role_arn');
            $table->foreignId('source_account_id')->nullable()->after('external_id')
                ->constrained('aws_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_account_id');
            $table->dropColumn(['role_arn', 'external_id']);
        });
    }
};
