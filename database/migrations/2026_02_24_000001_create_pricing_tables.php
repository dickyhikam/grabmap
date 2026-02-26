<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pricing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_category_id')->constrained()->cascadeOnDelete();
            $table->string('api_name');
            $table->string('tier', 50)->nullable();
            $table->decimal('als_price', 10, 4)->nullable();
            $table->decimal('google_price', 10, 4)->nullable();
            $table->integer('google_free_threshold')->default(0);
            $table->boolean('als_only')->default(false);
            $table->string('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_items');
        Schema::dropIfExists('pricing_categories');
    }
};
