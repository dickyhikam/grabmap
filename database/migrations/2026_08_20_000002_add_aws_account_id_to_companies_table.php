<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('aws_account_id')->nullable()->after('slug')
                ->constrained('aws_accounts')->nullOnDelete();
        });

        // Semua company yang sudah punya API key berasal dari akun lama (.env) = akun default.
        $defaultId = DB::table('aws_accounts')->where('is_default', true)->value('id');

        if ($defaultId) {
            DB::table('companies')->whereNotNull('aws_api_key_name')->update(['aws_account_id' => $defaultId]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aws_account_id');
        });
    }
};
