<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyamakan katalog /pricing dengan yang benar-benar dipakai GrabMaps.
 *
 * Perubahan ini semula dikerjakan langsung di basis data pengembangan, jadi
 * server lain (staging/produksi) masih memuat katalog lama. Dituangkan jadi
 * migrasi supaya setiap lingkungan bertemu di keadaan yang sama.
 *
 * Isinya tiga hal:
 *  1. Kategori dan baris yang tidak dipakai GrabMaps dihapus. Aksi yang
 *     didukung hanya GetTile, SearchText, Suggest, ReverseGeocode, GetPlace,
 *     CalculateRoutes, dan CalculateRouteMatrix.
 *  2. Urutan kategori: Maps, Places, Routes.
 *  3. Harga Google diluruskan ke daftar SKU resmi, dan tier Core dijadikan
 *     bawaan untuk setiap API yang bertingkat.
 *
 * Ditulis idempoten — aman dijalankan di basis data yang sudah rapi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('pricing_categories')) {
            return;
        }

        // 1. Kategori khusus ALS yang tidak dipakai GrabMaps.
        $dropCategories = ['trackers', 'geofences', 'service-resources'];
        $categoryIds = DB::table('pricing_categories')->whereIn('slug', $dropCategories)->pluck('id');

        if ($categoryIds->isNotEmpty()) {
            DB::table('pricing_items')->whereIn('pricing_category_id', $categoryIds)->delete();
            DB::table('pricing_categories')->whereIn('id', $categoryIds)->delete();
        }

        // Baris API yang tidak didukung GrabMaps.
        DB::table('pricing_items')->whereIn('api_name', [
            'Snap to Roads',
            'Optimize Waypoints',
            'Calculate Isolines',
            'Dynamic Maps',
            'Static Maps',
            'Open Data Dynamic Maps (Tiles)',
        ])->delete();

        // 2. Maps lebih dulu, baru Places dan Routes.
        foreach (['maps' => 1, 'places' => 2, 'routes' => 3] as $slug => $order) {
            DB::table('pricing_categories')->where('slug', $slug)->update(['sort_order' => $order]);
        }
        DB::table('pricing_items')->where('api_name', 'Map Tiles')->update(['sort_order' => 1]);

        // 3a. Core jadi tier bawaan; tier lain tidak tercentang.
        DB::table('pricing_items')->whereNotNull('tier')->update(['is_recommended' => false]);
        DB::table('pricing_items')->where('tier', 'Core')->update(['is_recommended' => true]);

        // 3b. Harga Google per 1.000 request menurut daftar SKU resmi
        //     (developers.google.com/maps/billing-and-pricing/pricing), tarif
        //     tingkat pertama. Kuota gratis ikut disimpan walau kalkulator
        //     tidak lagi memotongnya.
        $prices = [
            ['Map Tiles', null, 0.6000, 100000],
            ['Autocomplete, Suggest', null, 2.8300, 10000],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Core', 5.0000, 10000],
            ['Geocode / Reverse Geocode / Get Place / Search Text / Search Nearby', 'Advanced', 17.0000, 5000],
            ['Calculate Routes', 'Core', 5.0000, 10000],
            ['Calculate Routes', 'Advanced', 10.0000, 5000],
            ['Calculate Route Matrix', 'Core', 5.0000, 10000],
            ['Calculate Route Matrix', 'Advanced', 10.0000, 5000],
        ];

        foreach ($prices as [$apiName, $tier, $googlePrice, $freeThreshold]) {
            $query = DB::table('pricing_items')->where('api_name', $apiName);
            $tier === null ? $query->whereNull('tier') : $query->where('tier', $tier);

            $query->update([
                'google_price' => $googlePrice,
                'google_free_threshold' => $freeThreshold,
            ]);
        }
    }

    public function down(): void
    {
        // Tidak dibalik: baris yang dihapus tidak disimpan di mana pun, dan
        // memulihkannya berarti menghidupkan lagi katalog yang memang salah.
        // Kalau katalog lama benar-benar dibutuhkan, jalankan PricingSeeder
        // pada basis data kosong.
    }
};
