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
            'name' => 'Places', 'slug' => 'places', 'description' => 'Search for places, convert addresses to coordinates, and get place details. Essential for location search, address autocomplete, and geocoding.', 'sort_order' => 1,
        ]);

        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Autocomplete, Suggest',
            'als_price' => 0.7000,
            'google_price' => 3.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 1,
            'is_recommended' => true,
            'notes' => 'Google: Autocomplete Requests',
            'description' => 'Saran alamat dan tempat saat user mengetik. Mendukung search-as-you-type di field alamat dan pencarian tempat.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby',
            'tier' => 'Core',
            'als_price' => 0.5000,
            'google_price' => 5.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 2,
            'is_recommended' => true,
            'notes' => 'Google: Places API Place Details Essentials',
            'description' => 'Konversi alamat ke koordinat (lat/lng) dan sebaliknya. Detail tempat, search by text, atau cari tempat terdekat.',
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
            'description' => 'Data tempat lanjutan: jam operasional, kontak, time zone, access points. Untuk detail tempat yang lebih lengkap.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby',
            'tier' => 'Stored',
            'als_price' => 4.0000,
            'sort_order' => 4,
            'notes' => 'Storing results including all supported fields',
            'description' => 'Simpan hasil untuk penggunaan jangka panjang. Mengurangi API calls saat reuse hasil untuk analytics atau caching.',
        ]);

        // ---- ROUTES ----
        $routes = PricingCategory::create([
            'name' => 'Routes', 'slug' => 'routes', 'description' => 'Calculate directions, routes, and travel times between locations. Includes route matrices, waypoint optimization, and snap-to-road for GPS traces.', 'sort_order' => 2,
        ]);

        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Routes',
            'tier' => 'Core',
            'als_price' => 0.5000,
            'google_price' => 5.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 1,
            'is_recommended' => true,
            'notes' => 'Google: Routes: Compute Route Matrix Essentials',
            'description' => 'Petunjuk arah turn-by-turn antar dua titik atau lebih. Mendukung mobil, truck, pejalan kaki, dan sepeda.',
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
            'description' => 'Routing lanjutan dengan traffic real-time. Mode perjalanan tambahan seperti scooter.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Routes',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 3,
            'notes' => 'Including Toll Cost calculation',
            'description' => 'Termasuk perhitungan biaya tol. Untuk rute yang membutuhkan estimasi tol.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Route Matrix',
            'tier' => 'Core',
            'als_price' => 0.5000,
            'sort_order' => 4,
            'description' => 'Hitung rute antar banyak origin dan destination dalam satu request. Ideal untuk optimasi delivery dan fleet.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Route Matrix',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'sort_order' => 5,
            'description' => 'Route matrix lanjutan dengan opsi lebih banyak.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Snap to Roads',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'sort_order' => 6,
            'notes' => 'Up to 200 GPS points; car, truck, or pedestrian mode',
            'description' => 'Snap titik GPS ke jalan. Hingga 200 titik; mode mobil, truck, atau pejalan kaki.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Snap to Roads',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 7,
            'notes' => 'Up to 5,000 GPS points; no restriction on travel mode',
            'description' => 'Snap hingga 5.000 titik GPS ke jalan. Tanpa batasan mode perjalanan.',
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
            'description' => 'Urutkan waypoint untuk rute optimal. Untuk optimasi rute delivery.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Optimize Waypoints',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 9,
            'notes' => 'Up to 50 waypoints; no restriction on travel mode; with optional parameters',
            'description' => 'Hingga 50 waypoint. Fleksibilitas penuh untuk rute kompleks.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Isolines',
            'tier' => 'Advanced',
            'als_price' => 1.5000,
            'sort_order' => 10,
            'notes' => 'Up to 60 min or 100KM travel distance',
            'description' => 'Gambar polygon waktu tempuh atau jarak. Hingga 60 menit atau 100 km.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Isolines',
            'tier' => 'Premium',
            'als_price' => 4.0000,
            'sort_order' => 11,
            'notes' => 'Up to 180 min or 300KM travel distance; no restriction on travel mode',
            'description' => 'Isoline extended: hingga 180 menit atau 300 km. Tanpa batasan mode perjalanan.',
        ]);

        // ---- MAPS ----
        $maps = PricingCategory::create([
            'name' => 'Maps', 'slug' => 'maps', 'description' => 'Display map tiles and static map images. Used for showing interactive maps, static map images, and custom map visualizations.', 'sort_order' => 3,
        ]);

        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Dynamic Maps',
            'als_price' => 0.5750,
            'google_price' => 10.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 1,
            'is_recommended' => true,
            'notes' => 'Google: Dynamic Maps ($7 actual, shown as $10 in comparison)',
            'description' => 'Tile peta interaktif. Load untuk web dan mobile. Tampilan peta real-time.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Map Tiles',
            'als_price' => 0.0400,
            'google_price' => 1.0000,
            'google_free_threshold' => 100000,
            'sort_order' => 2,
            'is_recommended' => true,
            'description' => 'Tile peta 2D untuk custom styling. Desain interface peta unik.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Static Maps',
            'als_price' => 0.5000,
            'google_price' => 2.0000,
            'google_free_threshold' => 10000,
            'sort_order' => 3,
            'description' => 'Gambar peta statis. Embed peta sederhana tanpa JavaScript.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Open Data Dynamic Maps (Tiles)',
            'als_price' => 0.0350,
            'als_only' => true,
            'sort_order' => 4,
            'description' => 'Tile peta dari OpenStreetMap dan open data. Sumber data gratis.',
        ]);

        // ---- TRACKERS (ALS-GRAB only) ----
        $trackers = PricingCategory::create([
            'name' => 'Trackers', 'slug' => 'trackers', 'description' => 'Track device positions over time. Store and retrieve location history for fleet tracking, delivery apps, and asset monitoring.', 'sort_order' => 4,
        ]);

        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => '0 - 500K',
            'tier_group' => 'tracker_positions_written',
            'tier_min' => 0,
            'tier_max' => 500000,
            'als_price' => 0.0500,
            'als_only' => true,
            'is_recommended' => true,
            'sort_order' => 1,
            'description' => 'Simpan posisi device. 500K pertama per bulan.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => '500K - 5M',
            'tier_group' => 'tracker_positions_written',
            'tier_min' => 500000,
            'tier_max' => 5000000,
            'als_price' => 0.0350,
            'als_only' => true,
            'sort_order' => 2,
            'description' => 'Simpan posisi device. Volume tier 500K–5M.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => '5M - 50M',
            'tier_group' => 'tracker_positions_written',
            'tier_min' => 5000000,
            'tier_max' => 50000000,
            'als_price' => 0.0250,
            'als_only' => true,
            'sort_order' => 3,
            'description' => 'Simpan posisi device. Volume tier 5M–50M.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Positions Written',
            'tier' => 'Above 50M',
            'tier_group' => 'tracker_positions_written',
            'tier_min' => 50000000,
            'tier_max' => null,
            'als_price' => 0.0125,
            'als_only' => true,
            'sort_order' => 4,
            'description' => 'Simpan posisi device. Volume tier 50M+.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Batch Positions Read',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 5,
            'description' => 'Ambil posisi hingga 100 device dalam satu request.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Devices Deleted',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 6,
            'description' => 'Hapus device tracker. Cleanup saat tidak lagi dipakai.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $trackers->id,
            'api_name' => 'Position Integrity Evaluated',
            'als_price' => 1.0000,
            'als_only' => true,
            'sort_order' => 7,
            'description' => 'Validasi akurasi data posisi.',
        ]);

        // ---- GEOFENCES (ALS-GRAB only) ----
        $geofences = PricingCategory::create([
            'name' => 'Geofences', 'slug' => 'geofences', 'description' => 'Define virtual boundaries and detect when devices enter or exit. Used for location-based alerts, delivery zones, and attendance.', 'sort_order' => 5,
        ]);

        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => '0 - 250K',
            'tier_group' => 'geofence_positions_evaluated',
            'tier_min' => 0,
            'tier_max' => 250000,
            'als_price' => 0.1600,
            'als_only' => true,
            'is_recommended' => true,
            'sort_order' => 1,
            'description' => 'Cek apakah posisi masuk geofence. 250K evaluasi pertama per bulan.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => '250K - 2M',
            'tier_group' => 'geofence_positions_evaluated',
            'tier_min' => 250000,
            'tier_max' => 2000000,
            'als_price' => 0.1100,
            'als_only' => true,
            'sort_order' => 2,
            'description' => 'Evaluasi posisi geofence. Volume tier.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => '2M - 25M',
            'tier_group' => 'geofence_positions_evaluated',
            'tier_min' => 2000000,
            'tier_max' => 25000000,
            'als_price' => 0.0700,
            'als_only' => true,
            'sort_order' => 3,
            'description' => 'Evaluasi posisi geofence. Volume tier.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Positions Evaluated',
            'tier' => 'Above 25M',
            'tier_group' => 'geofence_positions_evaluated',
            'tier_min' => 25000000,
            'tier_max' => null,
            'als_price' => 0.0600,
            'als_only' => true,
            'sort_order' => 4,
            'description' => 'Evaluasi posisi geofence. Volume tier.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofences Created/Deleted/Described',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 5,
            'description' => 'Buat, hapus, atau ambil detail geofence. Kelola koleksi geofence.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofence List Requests',
            'als_price' => 0.0500,
            'als_only' => true,
            'sort_order' => 6,
            'description' => 'List geofence dalam collection.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofence-months Stored',
            'als_price' => 0.2000,
            'als_only' => true,
            'sort_order' => 7,
            'description' => 'Biaya penyimpanan geofence. Per geofence per bulan.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $geofences->id,
            'api_name' => 'Geofence Event Forecast Requested',
            'als_price' => 1.7500,
            'als_only' => true,
            'sort_order' => 8,
            'description' => 'Prediksi kapan device masuk/keluar geofence.',
        ]);

        // ---- SERVICE RESOURCES ----
        $serviceResources = PricingCategory::create([
            'name' => 'Service Resources', 'slug' => 'service-resources', 'description' => 'Manage API resources (trackers, geofence collections). Create, read, update, and delete resources.', 'sort_order' => 6,
        ]);

        PricingItem::create([
            'pricing_category_id' => $serviceResources->id,
            'api_name' => 'Resource CRUD/List Requests',
            'als_price' => 0.0100,
            'als_only' => true,
            'sort_order' => 1,
            'description' => 'Create, read, update, delete, atau list resource API.',
        ]);
    }
}
