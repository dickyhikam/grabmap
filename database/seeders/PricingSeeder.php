<?php

namespace Database\Seeders;

use App\Models\PricingCategory;
use App\Models\PricingItem;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        // ---- PLACES ----
        $places = PricingCategory::create([
            'name' => 'Places', 'slug' => 'places', 'sort_order' => 1,
        ]);

        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Autocomplete, Suggest',
            'als_price' => 0.7000,
            'google_price' => 3.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 1,
            'notes' => 'Google: Autocomplete Requests',
        ]);
        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby',
            'tier' => 'Core',
            'als_price' => 0.5000,
            'google_price' => 5.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 2,
            'notes' => 'Google: Places API Place Details Essentials',
        ]);
        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'google_price' => 40.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 3,
            'notes' => 'Google: Time Zone ($5) + Places API Nearby Search Enterprise ($35)',
        ]);
        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby',
            'tier' => 'Stored',
            'als_price' => 4.0000,
            'sort_order' => 4,
            'notes' => 'Storing results including all supported fields',
        ]);

        // ---- ROUTES ----
        $routes = PricingCategory::create([
            'name' => 'Routes', 'slug' => 'routes', 'sort_order' => 2,
        ]);

        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Routes',
            'tier' => 'Core',
            'als_price' => 0.5000,
            'google_price' => 5.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 1,
            'notes' => 'Google: Routes: Compute Route Matrix Essentials',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Routes',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'google_price' => 5.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 2,
            'notes' => 'Google: Routes: Compute Routes Essentials',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Routes',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 3,
            'notes' => 'Including Toll Cost calculation',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Route Matrix',
            'tier' => 'Core',
            'als_price' => 0.5000,
            'sort_order' => 4,
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Route Matrix',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'sort_order' => 5,
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Snap to Roads',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'sort_order' => 6,
            'notes' => 'Up to 200 GPS points; car, truck, or pedestrian mode',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Snap to Roads',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 7,
            'notes' => 'Up to 5,000 GPS points; no restriction on travel mode',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Optimize Waypoints',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'google_price' => 10.0000,
            'google_free_threshold' => 5000,
            'sort_order' => 8,
            'notes' => 'Google: Routes: Compute Routes Pro',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Optimize Waypoints',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 9,
            'notes' => 'Up to 50 waypoints; no restriction on travel mode; with optional parameters',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Isolines',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'sort_order' => 10,
            'notes' => 'Up to 60 min or 100KM travel distance',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Isolines',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 11,
            'notes' => 'Up to 180 min or 300KM travel distance; no restriction on travel mode',
        ]);

        // ---- MAPS ----
        $maps = PricingCategory::create([
            'name' => 'Maps', 'slug' => 'maps', 'sort_order' => 3,
        ]);

        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Dynamic Maps',
            'als_price' => 0.5750,
            'google_price' => 10.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 1,
            'notes' => 'Google: Dynamic Maps ($7 actual, shown as $10 in comparison)',
        ]);
        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Map Tiles',
            'als_price' => 0.0400,
            'google_price' => 1.0000,
            'google_free_threshold' => 100000,
            'sort_order' => 2,
        ]);
        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Static Maps',
            'als_price' => 0.5000,
            'google_price' => 2.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 3,
        ]);
        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Open Data Dynamic Maps (Tiles)',
            'als_price' => 0.0350,
            'als_only' => true,
            'sort_order' => 4,
        ]);

        // ---- TRACKERS (ALS-GRAB only) ----
        $trackers = PricingCategory::create([
            'name' => 'Trackers', 'slug' => 'trackers', 'sort_order' => 4,
        ]);

        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => '0 - 500K',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 1,
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => '500K - 5M',
            'als_price' => 0.0350,
            'als_only' => true,
            'sort_order' => 2,
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => '5M - 50M',
            'als_price' => 0.0250,
            'als_only' => true,
            'sort_order' => 3,
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => 'Above 50M',
            'als_price' => 0.0125,
            'als_only' => true,
            'sort_order' => 4,
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Batch Positions Read',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 5,
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Devices Deleted',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 6,
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Position Integrity Evaluated',
            'als_price' => 1.0000,
            'als_only' => true,
            'sort_order' => 7,
        ]);

        // ---- GEOFENCES (ALS-GRAB only) ----
        $geofences = PricingCategory::create([
            'name' => 'Geofences', 'slug' => 'geofences', 'sort_order' => 5,
        ]);

        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => '0 - 250K',
            'als_price' => 0.1600,
            'als_only' => true,
            'sort_order' => 1,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => '250K - 2M',
            'als_price' => 0.1100,
            'als_only' => true,
            'sort_order' => 2,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => '2M - 25M',
            'als_price' => 0.0700,
            'als_only' => true,
            'sort_order' => 3,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => 'Above 25M',
            'als_price' => 0.0600,
            'als_only' => true,
            'sort_order' => 4,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofences Created/Deleted/Described',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 5,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofence List Requests',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 6,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofence-months Stored',
            'als_price' => 0.2000,
            'als_only' => true,
            'sort_order' => 7,
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofence Event Forecast Requested',
            'als_price' => 1.7500,
            'als_only' => true,
            'sort_order' => 8,
        ]);

        // ---- SERVICE RESOURCES ----
        $serviceResources = PricingCategory::create([
            'name' => 'Service Resources', 'slug' => 'service-resources', 'sort_order' => 6,
        ]);

        PricingItem::create([
            'pricing_category_id' => $serviceResources->id,
            'api_name' => 'Resource CRUD/List Requests',
            'als_price' => 0.0100,
            'als_only' => true,
            'sort_order' => 1,
        ]);
    }
}
