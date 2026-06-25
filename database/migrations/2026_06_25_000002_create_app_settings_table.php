<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('app_settings')->insert([
            'key'        => 'tax_rate',
            'value'      => (string) config('aws.tax_rate', 0.11),
            'updated_by' => 'system',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
