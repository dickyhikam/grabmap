<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->decimal('from_lat', 10, 7);
            $table->decimal('from_lng', 10, 7);
            $table->decimal('to_lat', 10, 7);
            $table->decimal('to_lng', 10, 7);
            $table->string('mode', 20)->default('Car');
            $table->string('from_label', 120)->nullable();
            $table->string('to_label', 120)->nullable();
            $table->unsignedInteger('opens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_links');
    }
};
