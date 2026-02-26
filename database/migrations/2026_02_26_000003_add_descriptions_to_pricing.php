<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
        });

        Schema::table('pricing_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('notes');
        });

        $this->seedDescriptions();
    }

    protected function seedDescriptions(): void
    {
        $categories = [
            'places' => 'Search for places, convert addresses to coordinates, and get place details. Essential for location search, address autocomplete, and geocoding.',
            'routes' => 'Calculate directions, routes, and travel times between locations. Includes route matrices, waypoint optimization, and snap-to-road for GPS traces.',
            'maps' => 'Display map tiles and static map images. Used for showing interactive maps, static map images, and custom map visualizations.',
            'trackers' => 'Track device positions over time. Store and retrieve location history for fleet tracking, delivery apps, and asset monitoring.',
            'geofences' => 'Define virtual boundaries and detect when devices enter or exit. Used for location-based alerts, delivery zones, and attendance.',
            'service-resources' => 'Manage API resources (trackers, geofence collections). Create, read, update, and delete resources.',
        ];

        foreach ($categories as $slug => $desc) {
            \DB::table('pricing_categories')->where('slug', $slug)->update(['description' => $desc]);
        }

        $items = [
            ['Autocomplete, Suggest', null, 'Suggest addresses and places as users type. Powers search-as-you-type in address fields and place search.'],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Core', 'Convert addresses to coordinates (lat/lng) and vice versa. Get place details, search by text, or find nearby places.'],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Advanced', 'Advanced place data: business hours, contact info, time zone, access points. For richer place details.'],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Stored', 'Store place results for long-term use. Reduces API calls when reusing results for analytics or caching.'],
            ['Calculate Routes', 'Core', 'Get turn-by-turn directions between two or more points. Supports car, truck, pedestrian, and bike modes.'],
            ['Calculate Routes', 'Advanced', 'Advanced routing with real-time traffic. Additional travel modes like scooter.'],
            ['Calculate Routes', 'Premium', 'Includes toll cost calculation. For routes that need toll pricing.'],
            ['Calculate Route Matrix', 'Core', 'Calculate routes between multiple origins and destinations in one request. Ideal for delivery optimization and fleet planning.'],
            ['Calculate Route Matrix', 'Advanced', 'Advanced route matrix with more options.'],
            ['Snap to Roads', 'Advanced', 'Snap GPS points to roads. Up to 200 points; car, truck, or pedestrian mode.'],
            ['Snap to Roads', 'Premium', 'Snap up to 5,000 GPS points to roads. No travel mode restrictions.'],
            ['Optimize Waypoints', 'Advanced', 'Reorder waypoints for optimal route. For delivery route optimization.'],
            ['Optimize Waypoints', 'Premium', 'Up to 50 waypoints. Full flexibility for complex routes.'],
            ['Calculate Isolines', 'Advanced', 'Draw travel-time or distance polygons. Up to 60 min or 100 km.'],
            ['Calculate Isolines', 'Premium', 'Extended isolines: up to 180 min or 300 km. No travel mode restrictions.'],
            ['Dynamic Maps', null, 'Interactive map tiles. Load map tiles for web and mobile apps. Real-time map display.'],
            ['Map Tiles', null, '2D map tiles for custom styling. Design unique map interfaces.'],
            ['Static Maps', null, 'Static map images. Embed simple map images without JavaScript.'],
            ['Open Data Dynamic Maps (Tiles)', null, 'Map tiles from OpenStreetMap and open data. Free data sources.'],
            ['Positions Written', '0 - 500K', 'Store device positions. First 500K positions per month.'],
            ['Positions Written', '500K - 5M', 'Store device positions. Volume tier for 500K–5M positions.'],
            ['Positions Written', '5M - 50M', 'Store device positions. Volume tier for 5M–50M positions.'],
            ['Positions Written', 'Above 50M', 'Store device positions. Volume tier for 50M+ positions.'],
            ['Batch Positions Read', null, 'Retrieve positions for up to 100 devices in one request.'],
            ['Devices Deleted', null, 'Delete tracker devices. Clean up when no longer needed.'],
            ['Position Integrity Evaluated', null, 'Validate position data for accuracy.'],
            ['Positions Evaluated', '0 - 250K', 'Check if positions are inside geofences. First 250K evaluations per month.'],
            ['Positions Evaluated', '250K - 2M', 'Geofence position evaluations. Volume tier.'],
            ['Positions Evaluated', '2M - 25M', 'Geofence position evaluations. Volume tier.'],
            ['Positions Evaluated', 'Above 25M', 'Geofence position evaluations. Volume tier.'],
            ['Geofences Created/Deleted/Described', null, 'Create, delete, or get geofence details. Manage geofence collections.'],
            ['Geofence List Requests', null, 'List geofences in a collection.'],
            ['Geofence-months Stored', null, 'Storage cost for geofences. Per geofence per month.'],
            ['Geofence Event Forecast Requested', null, 'Forecast when a device will enter/exit a geofence.'],
            ['Resource CRUD/List Requests', null, 'Create, read, update, delete, or list API resources.'],
        ];

        foreach ($items as [$apiName, $tier, $desc]) {
            $q = \DB::table('pricing_items')->where('api_name', $apiName);
            if ($tier !== null) {
                $q->where('tier', $tier);
            } else {
                $q->whereNull('tier');
            }
            $q->update(['description' => $desc]);
        }
    }

    public function down(): void
    {
        Schema::table('pricing_categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });
        Schema::table('pricing_items', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
