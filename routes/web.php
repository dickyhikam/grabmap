<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/waresix', function () {
    return view('welcome');
});

Route::get('/', function () {
    return view('new');
});

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
