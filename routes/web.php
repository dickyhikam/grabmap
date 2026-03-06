<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\PricingController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/waresix', function () {
    return view('welcome');
});
Route::get('/transjakarta', function () {
    return view('vendor.tj');
});
Route::get('/otto', function () {
    return view('otto');
});

Route::get('/doc-web', function () {
    return view('documents.web');
});

Route::get('/address', function () {
    return view('old.address');
})->name('pageAddress');

Route::get('/scrap-test', function () {
    return view('scraping/scrap-test');
})->name('pageScrapTest');
Route::get('/scrap', function () {
    return view('scraping/index');
})->name('pageScrap');

Route::get('/tests', function () {
    return view('scraping/test');
})->name('pageScrap');

Route::get('/map', [MapController::class , 'showMap']);
Route::get('/api/map-style', [MapController::class , 'getMapStyle']);
Route::get('/api/map-style-simple', [MapController::class , 'getMapStyleSimple']);
Route::get('/api/map-style-clean', [MapController::class , 'getMapStyleClean']);

// Proxy endpoints — keeps API keys server-side
Route::post('/api/places/suggestions', [MapController::class , 'searchSuggestions']);
Route::post('/api/places/search', [MapController::class , 'searchText']);
Route::get('/api/places/{placeId}', [MapController::class , 'getPlace']);
Route::post('/api/places/reverse', [MapController::class , 'reverseGeocode']);
Route::post('/api/routes/calculate', [MapController::class , 'calculateRoute']);
Route::post('/api/routes/matrix', [MapController::class , 'calculateRouteMatrix']);

//route API
Route::get('/map-style', function () {
    $region = env('AWS_REGION'); // Ambil dari .env
    $mapName = env('AWS_MAP_NAME'); // Ambil dari .env
    $apiKey = env('AWS_API_KEY'); // Ambil API key dari .env

    // Buat URL untuk style map
    $url = "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/style-descriptor?key={$apiKey}";

    // Ambil data dari API
    $response = Http::get($url);

    // Kembalikan data JSON ke frontend
    return response()->json($response->json());
});

Route::get('/map-config', function () {
    return response()->json([
    'region' => env('AWS_REGION'),
    'apiKey' => env('AWS_API_KEY'),
    'mapName' => env('AWS_MAP_NAME')
    ]);
});

// Pricing Comparison
Route::get('/pricing', [PricingController::class , 'index'])->name('pricing');
Route::post('/api/pricing/calculate', [PricingController::class , 'calculate']);

// Pricing Admin
Route::get('/pricing/admin', [PricingController::class , 'adminIndex'])->name('pricing.admin');
Route::post('/pricing/admin/items', [PricingController::class , 'store'])->name('pricing.store');
Route::put('/pricing/admin/items/{item}', [PricingController::class , 'update'])->name('pricing.update');
Route::delete('/pricing/admin/items/{item}', [PricingController::class , 'destroy'])->name('pricing.destroy');
Route::get('/', function () {
    return view('home.index');
})->name('pageHome');
Route::get('/tester-api', function () {
    return view('testing.index');
})->name('pageRouteTester');