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
            'name' => 'Places', 'slug' => 'places', 'description' => 'Search for places, convert addresses to coordinates, and get place details. Essential for location search, address autocomplete, and geocoding.', 'sort_order' => 2,
        ]);

        PricingItem::create([
            'pricing_category_id' => $places->id,
            'api_name' => 'Autocomplete, Suggest',
            'als_price' => 0.7000,
            'google_price' => 2.8300,
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
            'google_price' => 17.0000,
            'google_free_threshold' => 5000,
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
            'name' => 'Routes', 'slug' => 'routes', 'description' => 'Calculate directions, routes, and travel times between locations. Includes route matrices for many-to-many origin and destination pairs.', 'sort_order' => 3,
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
            'google_price' => 10.0000,
            'google_free_threshold' => 5000,
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
            'google_price' => 5.0000,
            'google_free_threshold' => 10000,
            'als_price' => 0.5000,
            'sort_order' => 4,
            'is_recommended' => true,
            'description' => 'Hitung rute antar banyak origin dan destination dalam satu request. Ideal untuk optimasi delivery dan fleet.',
        ]);
        PricingItem::create([
            'pricing_category_id' => $routes->id,
            'api_name' => 'Calculate Route Matrix',
            'tier' => 'Advanced',
            'google_price' => 10.0000,
            'google_free_threshold' => 5000,
            'als_price' => 1.5000,
            'sort_order' => 5,
            'description' => 'Route matrix lanjutan dengan opsi lebih banyak.',
        ]);

        // ---- MAPS ----
        $maps = PricingCategory::create([
            'name' => 'Maps', 'slug' => 'maps', 'description' => 'Serve map tiles for interactive maps. GrabMaps bills tile requests through GetTile.', 'sort_order' => 1,
        ]);

        PricingItem::create([
            'pricing_category_id' => $maps->id,
            'api_name' => 'Map Tiles',
            'als_price' => 0.0400,
            'google_price' => 0.6000,
            'google_free_threshold' => 100000,
            'sort_order' => 1,
            'is_recommended' => true,
            'description' => 'Tile peta 2D untuk custom styling. Desain interface peta unik.',
        ]);

    }
}
