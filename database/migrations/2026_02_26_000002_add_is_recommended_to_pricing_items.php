<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->boolean('is_recommended')->default(false)->after('als_only');
        });

        $this->setRecommendedItems();
    }

    protected function setRecommendedItems(): void
    {
        // Commonly used APIs on both ALS & Google:
        // Places: Autocomplete, Geocode Core
        // Routes: Calculate Routes Core
        // Maps: Map Tiles, Dynamic Maps
        \DB::table('pricing_items')->where('api_name', 'Autocomplete, Suggest')->update(['is_recommended' => true]);
        \DB::table('pricing_items')->where('api_name', 'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby')->where('tier', 'Core')->update(['is_recommended' => true]);
        \DB::table('pricing_items')->where('api_name', 'Calculate Routes')->where('tier', 'Core')->update(['is_recommended' => true]);
        \DB::table('pricing_items')->where('api_name', 'Map Tiles')->update(['is_recommended' => true]);
        \DB::table('pricing_items')->where('api_name', 'Dynamic Maps')->update(['is_recommended' => true]);
        \DB::table('pricing_items')->where('api_name', 'Positions Written')->where('tier', '0 - 500K')->update(['is_recommended' => true]);
        \DB::table('pricing_items')->where('api_name', 'Positions Evaluated')->where('tier', '0 - 250K')->update(['is_recommended' => true]);
    }

    public function down(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->dropColumn('is_recommended');
        });
    }
};
