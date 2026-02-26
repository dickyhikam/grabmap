<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->string('tier_group', 80)->nullable()->after('tier');
            $table->unsignedBigInteger('tier_min')->nullable()->after('tier_group');
            $table->unsignedBigInteger('tier_max')->nullable()->after('tier_min');
        });

        $this->updateTierGroupData();
    }

    protected function updateTierGroupData(): void
    {
        \DB::table('pricing_items')->where('api_name', 'Positions Written')->where('tier', '0 - 500K')->update(['tier_group' => 'tracker_positions_written', 'tier_min' => 0, 'tier_max' => 500000]);
        \DB::table('pricing_items')->where('api_name', 'Positions Written')->where('tier', '500K - 5M')->update(['tier_group' => 'tracker_positions_written', 'tier_min' => 500000, 'tier_max' => 5000000]);
        \DB::table('pricing_items')->where('api_name', 'Positions Written')->where('tier', '5M - 50M')->update(['tier_group' => 'tracker_positions_written', 'tier_min' => 5000000, 'tier_max' => 50000000]);
        \DB::table('pricing_items')->where('api_name', 'Positions Written')->where('tier', 'Above 50M')->update(['tier_group' => 'tracker_positions_written', 'tier_min' => 50000000, 'tier_max' => null]);
        \DB::table('pricing_items')->where('api_name', 'Positions Evaluated')->where('tier', '0 - 250K')->update(['tier_group' => 'geofence_positions_evaluated', 'tier_min' => 0, 'tier_max' => 250000]);
        \DB::table('pricing_items')->where('api_name', 'Positions Evaluated')->where('tier', '250K - 2M')->update(['tier_group' => 'geofence_positions_evaluated', 'tier_min' => 250000, 'tier_max' => 2000000]);
        \DB::table('pricing_items')->where('api_name', 'Positions Evaluated')->where('tier', '2M - 25M')->update(['tier_group' => 'geofence_positions_evaluated', 'tier_min' => 2000000, 'tier_max' => 25000000]);
        \DB::table('pricing_items')->where('api_name', 'Positions Evaluated')->where('tier', 'Above 25M')->update(['tier_group' => 'geofence_positions_evaluated', 'tier_min' => 25000000, 'tier_max' => null]);
    }

    public function down(): void
    {
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->dropColumn(['tier_group', 'tier_min', 'tier_max']);
        });
    }
};
