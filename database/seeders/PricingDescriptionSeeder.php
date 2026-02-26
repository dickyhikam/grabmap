<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Updates description for existing pricing items and categories.
 * Run: php artisan db:seed --class=PricingDescriptionSeeder
 */
class PricingDescriptionSeeder extends Seeder
{
    public function run(): void
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
            ['Autocomplete, Suggest', null, 'Saran alamat dan tempat saat user mengetik. Mendukung search-as-you-type di field alamat dan pencarian tempat.'],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Core', 'Konversi alamat ke koordinat (lat/lng) dan sebaliknya. Detail tempat, search by text, atau cari tempat terdekat.'],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Advanced', 'Data tempat lanjutan: jam operasional, kontak, time zone, access points. Untuk detail tempat yang lebih lengkap.'],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Stored', 'Simpan hasil untuk penggunaan jangka panjang. Mengurangi API calls saat reuse hasil untuk analytics atau caching.'],
            ['Calculate Routes', 'Core', 'Petunjuk arah turn-by-turn antar dua titik atau lebih. Mendukung mobil, truck, pejalan kaki, dan sepeda.'],
            ['Calculate Routes', 'Advanced', 'Routing lanjutan dengan traffic real-time. Mode perjalanan tambahan seperti scooter.'],
            ['Calculate Routes', 'Premium', 'Termasuk perhitungan biaya tol. Untuk rute yang membutuhkan estimasi tol.'],
            ['Calculate Route Matrix', 'Core', 'Hitung rute antar banyak origin dan destination dalam satu request. Ideal untuk optimasi delivery dan fleet.'],
            ['Calculate Route Matrix', 'Advanced', 'Route matrix lanjutan dengan opsi lebih banyak.'],
            ['Snap to Roads', 'Advanced', 'Snap titik GPS ke jalan. Hingga 200 titik; mode mobil, truck, atau pejalan kaki.'],
            ['Snap to Roads', 'Premium', 'Snap hingga 5.000 titik GPS ke jalan. Tanpa batasan mode perjalanan.'],
            ['Optimize Waypoints', 'Advanced', 'Urutkan waypoint untuk rute optimal. Untuk optimasi rute delivery.'],
            ['Optimize Waypoints', 'Premium', 'Hingga 50 waypoint. Fleksibilitas penuh untuk rute kompleks.'],
            ['Calculate Isolines', 'Advanced', 'Gambar polygon waktu tempuh atau jarak. Hingga 60 menit atau 100 km.'],
            ['Calculate Isolines', 'Premium', 'Isoline extended: hingga 180 menit atau 300 km. Tanpa batasan mode perjalanan.'],
            ['Dynamic Maps', null, 'Tile peta interaktif. Load untuk web dan mobile. Tampilan peta real-time.'],
            ['Map Tiles', null, 'Tile peta 2D untuk custom styling. Desain interface peta unik.'],
            ['Static Maps', null, 'Gambar peta statis. Embed peta sederhana tanpa JavaScript.'],
            ['Open Data Dynamic Maps (Tiles)', null, 'Tile peta dari OpenStreetMap dan open data. Sumber data gratis.'],
            ['Positions Written', '0 - 500K', 'Simpan posisi device. 500K pertama per bulan.'],
            ['Positions Written', '500K - 5M', 'Simpan posisi device. Volume tier 500K–5M.'],
            ['Positions Written', '5M - 50M', 'Simpan posisi device. Volume tier 5M–50M.'],
            ['Positions Written', 'Above 50M', 'Simpan posisi device. Volume tier 50M+.'],
            ['Batch Positions Read', null, 'Ambil posisi hingga 100 device dalam satu request.'],
            ['Devices Deleted', null, 'Hapus device tracker. Cleanup saat tidak lagi dipakai.'],
            ['Position Integrity Evaluated', null, 'Validasi akurasi data posisi.'],
            ['Positions Evaluated', '0 - 250K', 'Cek apakah posisi masuk geofence. 250K evaluasi pertama per bulan.'],
            ['Positions Evaluated', '250K - 2M', 'Evaluasi posisi geofence. Volume tier.'],
            ['Positions Evaluated', '2M - 25M', 'Evaluasi posisi geofence. Volume tier.'],
            ['Positions Evaluated', 'Above 25M', 'Evaluasi posisi geofence. Volume tier.'],
            ['Geofences Created/Deleted/Described', null, 'Buat, hapus, atau ambil detail geofence. Kelola koleksi geofence.'],
            ['Geofence List Requests', null, 'List geofence dalam collection.'],
            ['Geofence-months Stored', null, 'Biaya penyimpanan geofence. Per geofence per bulan.'],
            ['Geofence Event Forecast Requested', null, 'Prediksi kapan device masuk/keluar geofence.'],
            ['Resource CRUD/List Requests', null, 'Create, read, update, delete, atau list resource API.'],
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
}
