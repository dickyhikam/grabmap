<?php

use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/waresix', function () {
    return view('welcome');
});
Route::get('/tj', function () {
    return view('tj');
});
Route::get('/otto', function () {
    return view('otto');
});

Route::get('/doc-web', function () {
    return view('documents.web');
});

Route::get('/', function () {
    return view('index');
})->name('pageHome');

Route::get('/address', function () {
    return view('address');
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

Route::get('/map', [MapController::class, 'showMap']);
Route::get('/api/map-style', [MapController::class, 'getMapStyle']);
Route::get('/api/map-style-simple', [MapController::class, 'getMapStyleSimple']);
Route::get('/api/map-style-clean', [MapController::class, 'getMapStyleClean']); // New endpoint

//route API
Route::get('/map-style', function () {
    $region = env('AWS_REGION');  // Ambil dari .env
    $mapName = env('AWS_MAP_NAME');  // Ambil dari .env
    $apiKey = env('AWS_API_KEY');  // Ambil API key dari .env

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
