<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    public function showMap()
    {
        return view('maps');
    }

    public function getMapStyle()
    {
        $region = config('services.aws.region');
        $apiKey = config('services.aws.api_key');
        $mapName = config('services.aws.map_name');

        Log::info('Map style request', [
            'region' => $region,
            'map_name' => $mapName
        ]);

        try {
            $url = "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/style-descriptor";

            $response = Http::timeout(30)->get($url, [
                'key' => $apiKey
            ]);

            Log::info('AWS Response Status: ' . $response->status());

            if ($response->successful()) {
                $styleData = $response->json();

                // **FIX: Validasi dan perbaiki struktur layers**
                return $this->validateAndFixMapStyle($styleData);
            }

            Log::error('AWS API Error: ' . $response->body());
            return response()->json([
                'error' => 'Failed to fetch map style',
                'status' => $response->status(),
                'message' => $response->body()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Map style exception: ' . $e->getMessage());
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validasi dan perbaiki map style untuk MapLibre
     */
    private function validateAndFixMapStyle($styleData)
    {
        // Pastikan ini adalah style JSON yang valid
        if (!is_array($styleData)) {
            return response()->json(['error' => 'Invalid style data'], 500);
        }

        // **FIX 1: Tambahkan version jika tidak ada**
        if (!isset($styleData['version'])) {
            $styleData['version'] = 8;
        }

        // **FIX 2: Perbaiki struktur layers**
        if (isset($styleData['layers']) && is_array($styleData['layers'])) {
            $styleData['layers'] = $this->fixLayersStructure($styleData['layers']);
        }

        // **FIX 3: Pastikan sources ada**
        if (!isset($styleData['sources']) || empty($styleData['sources'])) {
            $region = config('services.aws.region');
            $mapName = config('services.aws.map_name');

            $styleData['sources'] = [
                'default' => [
                    'type' => 'vector',
                    'tiles' => [
                        "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/tiles/{z}/{x}/{y}"
                    ]
                ]
            ];
        }

        // **FIX 4: Tambahkan glyphs jika diperlukan**
        if (!isset($styleData['glyphs'])) {
            $region = config('services.aws.region');
            $apiKey = config('services.aws.api_key');
            $mapName = config('services.aws.map_name');

            $styleData['glyphs'] = "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/glyphs/{fontstack}/{range}?key={$apiKey}";
        }

        Log::info('Style data processed', [
            'version' => $styleData['version'],
            'sources_count' => count($styleData['sources']),
            'layers_count' => count($styleData['layers']),
            'layers_fixed' => true
        ]);

        return response()->json($styleData);
    }

    /**
     * Perbaiki struktur layers - ini yang jadi masalah utama
     */
    private function fixLayersStructure($layers)
    {
        $fixedLayers = [];

        foreach ($layers as $layer) {
            // Pastikan layout selalu jadi JSON object {}, bukan array []
            if (isset($layer['layout']) && is_array($layer['layout'])) {
                $layer['layout'] = empty($layer['layout'])
                    ? new \stdClass()
                    : (object) $layer['layout'];
            }

            // Pastikan paint selalu jadi JSON object {}, bukan array []
            if (isset($layer['paint']) && is_array($layer['paint'])) {
                $layer['paint'] = empty($layer['paint'])
                    ? new \stdClass()
                    : (object) $layer['paint'];
            }

            // Pastikan filter tetap array
            if (isset($layer['filter']) && !is_array($layer['filter'])) {
                $layer['filter'] = [$layer['filter']];
            }

            $fixedLayers[] = $layer;
        }

        return $fixedLayers;
    }

    /**
     * Alternative: Simple style yang guaranteed work
     */
    public function getMapStyleSimple()
    {
        $region = config('services.aws.region');
        $apiKey = config('services.aws.api_key');
        $mapName = config('services.aws.map_name');

        try {
            // **SIMPLE STYLE yang pasti work**
            $style = [
                'version' => 8,
                'name' => 'Amazon Location Simple Style',
                'metadata' => [
                    'mapbox:autocomposite' => true
                ],
                'sources' => [
                    'amazon-location' => [
                        'type' => 'vector',
                        'tiles' => [
                            "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/tiles/{z}/{x}/{y}?key={$apiKey}"
                        ],
                        'maxzoom' => 22
                    ]
                ],
                'sprite' => "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/sprites?key={$apiKey}",
                'glyphs' => "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/glyphs/{fontstack}/{range}?key={$apiKey}",
                'layers' => [
                    // Background layer
                    [
                        'id' => 'background',
                        'type' => 'background',
                        'paint' => [
                            'background-color' => '#f8f9fa'
                        ]
                    ],
                    // Basic land layer
                    [
                        'id' => 'land',
                        'type' => 'fill',
                        'source' => 'amazon-location',
                        'source-layer' => 'landuse',
                        'filter' => ['==', '$type', 'Polygon'],
                        'paint' => [
                            'fill-color' => '#e9ecef'
                        ]
                    ],
                    // Basic road layer  
                    [
                        'id' => 'roads',
                        'type' => 'line',
                        'source' => 'amazon-location',
                        'source-layer' => 'transportation',
                        'filter' => ['==', '$type', 'LineString'],
                        'paint' => [
                            'line-color' => '#495057',
                            'line-width' => 1
                        ]
                    ],
                    // Water layer
                    [
                        'id' => 'water',
                        'type' => 'fill',
                        'source' => 'amazon-location',
                        'source-layer' => 'water',
                        'filter' => ['==', '$type', 'Polygon'],
                        'paint' => [
                            'fill-color' => '#dee2e6'
                        ]
                    ]
                ]
            ];

            Log::info('Simple style generated', [
                'layers_count' => count($style['layers'])
            ]);

            return response()->json($style);
        } catch (\Exception $e) {
            Log::error('Simple style error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * New endpoint: Clean style (remove problematic layers)
     */
    public function getMapStyleClean()
    {
        $region = config('services.aws.region');
        $apiKey = config('services.aws.api_key');
        $mapName = config('services.aws.map_name');

        try {
            // Membuat URL untuk request API dari AWS
            $url = "https://maps.geo.{$region}.amazonaws.com/maps/v0/maps/{$mapName}/style-descriptor";

            // Melakukan request menggunakan Http client Laravel
            $response = Http::timeout(30)->get($url, [
                'key' => $apiKey // Ini hanya digunakan di backend, tidak perlu dikirim ke frontend
            ]);

            if ($response->successful()) {
                $styleData = $response->json();

                // **CLEAN APPROACH: Hapus layers yang bermasalah**
                return $this->cleanProblematicLayers($styleData);
            }

            return response()->json(['error' => 'Failed to fetch style'], 500);
        } catch (\Exception $e) {
            Log::error('Clean style error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Hapus layers yang menyebabkan error
     */
    private function cleanProblematicLayers($styleData)
    {
        if (!isset($styleData['layers']) || !is_array($styleData['layers'])) {
            return response()->json($styleData);
        }

        $cleanLayers = [];
        $removedCount = 0;

        foreach ($styleData['layers'] as $layer) {
            // Jika layout adalah array (yang menyebabkan error), skip layer ini
            if (isset($layer['layout']) && is_array($layer['layout'])) {
                $layoutKeys = array_keys($layer['layout']);
                if (!empty($layoutKeys) && $layoutKeys === range(0, count($layer['layout']) - 1)) {
                    $removedCount++;
                    continue; // Skip layer ini
                }
            }

            // Perbaiki struktur yang mungkin bermasalah (mengubah array ke object)
            if (isset($layer['layout']) && is_array($layer['layout'])) {
                $layer['layout'] = (object) $layer['layout'];
            }

            if (isset($layer['paint']) && is_array($layer['paint'])) {
                $layer['paint'] = (object) $layer['paint'];
            }

            // Menghapus API key yang ada di URL (tiles, glyphs, sprite)
            $layer = $this->removeApiKeyFromLayer($layer);

            $cleanLayers[] = $layer;
        }

        $styleData['layers'] = $cleanLayers;

        Log::info('Layers cleaned', [
            'original_count' => count($styleData['layers']) + $removedCount,
            'cleaned_count' => count($cleanLayers),
            'removed_count' => $removedCount
        ]);

        return response()->json($styleData);
    }

    private function removeApiKeyFromLayer($layer)
    {
        // Menghapus API key dalam URL tiles, glyphs, dan sprite
        $keysToRemove = ['tiles', 'glyphs', 'sprite'];

        foreach ($keysToRemove as $key) {
            if (isset($layer[$key])) {
                // Menghapus parameter 'key' dari URL
                $layer[$key] = preg_replace('/\?key=[^&]+/', '', $layer[$key]);
            }
        }

        return $layer;
    }

    /* =========================================
       PROXY ENDPOINTS — Places & Routes
       ========================================= */

    private function awsConfig()
    {
        return [
            'region'     => config('services.aws.region'),
            'api_key'    => config('services.aws.api_key'),
            'place_index'=> config('services.aws.place_index'),
            'route_calc' => config('services.aws.route_calculator'),
        ];
    }

    public function searchSuggestions(Request $request)
    {
        $cfg = $this->awsConfig();
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/places/v0/indexes/{$cfg['place_index']}/search/suggestions?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(15)->post($url, $request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchText(Request $request)
    {
        $cfg = $this->awsConfig();
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/places/v0/indexes/{$cfg['place_index']}/search/text?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(15)->post($url, $request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPlace($placeId)
    {
        $cfg = $this->awsConfig();
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/places/v0/indexes/{$cfg['place_index']}/places/{$placeId}?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(15)->get($url);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reverseGeocode(Request $request)
    {
        $cfg = $this->awsConfig();
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/places/v0/indexes/{$cfg['place_index']}/search/position?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(15)->post($url, $request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateRoute(Request $request)
    {
        $cfg = $this->awsConfig();
        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/routes/v0/calculators/{$cfg['route_calc']}/calculate/route?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateRouteMatrix(Request $request)
    {
        $cfg = $this->awsConfig();
        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/routes/v0/calculators/{$cfg['route_calc']}/calculate/route-matrix?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
