<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_features', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('company_features', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
