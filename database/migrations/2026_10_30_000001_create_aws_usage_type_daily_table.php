<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemakaian Amazon Location per usage type per hari, dari Cost Explorer —
     * baris yang sama dengan tagihan AWS (mis. APS1-Usage-Grab-Geocode). Dipakai
     * untuk akun klien yang tidak memanggil lewat API key (mis. Place Index v1
     * dengan kredensial IAM), sehingga CloudWatch per key menunjukkan nol.
     *
     * Hasil penelusuran resource (Place Index, Map, dst. beserta data source-nya)
     * disimpan di akun supaya halaman tidak perlu menembak AWS.
     */
    public function up(): void
    {
        Schema::create('aws_usage_type_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aws_account_id')->constrained('aws_accounts')->cascadeOnDelete();
            $table->date('usage_date');
            $table->string('usage_type', 160);
            $table->string('unit', 60)->nullable();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('cost_usd', 16, 6)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->unique(['aws_account_id', 'usage_date', 'usage_type'], 'usage_type_daily_unique');
        });

        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->json('resources')->nullable()->after('owner_company_id');
            $table->timestamp('resources_checked_at')->nullable()->after('resources');
            $table->timestamp('usage_types_synced_at')->nullable()->after('resources_checked_at');
            $table->string('usage_types_error')->nullable()->after('usage_types_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('aws_accounts', function (Blueprint $table) {
            $table->dropColumn(['resources', 'resources_checked_at', 'usage_types_synced_at', 'usage_types_error']);
        });

        Schema::dropIfExists('aws_usage_type_daily');
    }
};
