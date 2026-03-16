<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('aws_api_key')->nullable()->after('is_active');
            $table->boolean('aws_key_active')->default(true)->after('aws_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['aws_api_key', 'aws_key_active']);
        });
    }
};
