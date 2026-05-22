<?php

namespace App\Http\Controllers;

use App\Models\ApiUsageLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    public function showMap()
    {
        return view('maps');
    }

    public function getMapStyle(Request $request)
    {
        $region = config('services.aws.region');
        $apiKey = config('services.aws.api_key');
        $mapName = config('services.aws.map_name');

        // Use company-specific API key if available
        $companySlug = $request->query('company');
        if ($companySlug) {
            $company = Company::where('slug', $companySlug)->where('is_active', true)->first();
            if ($company) {
                $companyKey = $company->getActiveApiKey();
                if ($companyKey) {
                    $apiKey = $companyKey;
                }
            }
        }

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
     * Maps v2 — AWS Location Service Standalone Maps API.
     * Endpoint: https://maps.geo.{region}.amazonaws.com/v2/styles/{Style}/descriptor
     * Style options: Standard | Monochrome | Hybrid | Satellite
     * Color scheme: Light | Dark
     */
    public function getMapStyleV2(Request $request)
    {
        $region = config('services.aws.region');
        $apiKey = config('services.aws.api_key');

        // Per-company key override (same pattern as v0)
        $companySlug = $request->query('company');
        if ($companySlug) {
            $company = Company::where('slug', $companySlug)->where('is_active', true)->first();
            if ($company) {
                $companyKey = $company->getActiveApiKey();
                if ($companyKey) {
                    $apiKey = $companyKey;
                }
            }
        }

        $style = $request->query('style', 'Standard');
        $colorScheme = $request->query('color', 'Light');
        // Note: ap-southeast-1 / GrabMaps only supports Standard + Monochrome.
        // Hybrid/Satellite are not valid for GetStyleDescriptor in this region.
        $allowedStyles = ['Standard', 'Monochrome'];
        $allowedColors = ['Light', 'Dark'];
        if (!in_array($style, $allowedStyles, true)) $style = 'Standard';
        if (!in_array($colorScheme, $allowedColors, true)) $colorScheme = 'Light';

        Log::info('Map style v2 request', [
            'region' => $region,
            'style' => $style,
            'color' => $colorScheme,
        ]);

        try {
            $url = "https://maps.geo.{$region}.amazonaws.com/v2/styles/{$style}/descriptor";

            $response = Http::timeout(30)->get($url, [
                'key' => $apiKey,
                'color-scheme' => $colorScheme,
            ]);

            Log::info('AWS v2 Response Status: ' . $response->status());

            if (!$response->successful()) {
                Log::error('AWS v2 API Error: ' . $response->body());
                return response()->json([
                    'error' => 'Failed to fetch v2 map style',
                    'status' => $response->status(),
                    'message' => $response->body(),
                ], 500);
            }

            $styleData = $response->json();
            return $this->fixV2MapStyle($styleData, $region, $apiKey, $style);
        } catch (\Exception $e) {
            Log::error('Map style v2 exception: ' . $e->getMessage());
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inject API key into v2 style URLs (sources, glyphs, sprite)
     * and normalize layers for MapLibre.
     */
    private function fixV2MapStyle(array $styleData, string $region, string $apiKey, string $style)
    {
        if (!isset($styleData['version'])) {
            $styleData['version'] = 8;
        }

        if (isset($styleData['layers']) && is_array($styleData['layers'])) {
            $styleData['layers'] = $this->fixLayersStructure($styleData['layers']);
        }

        // Inject API key into tile URLs inside sources (works for both vector & raster sources)
        if (isset($styleData['sources']) && is_array($styleData['sources'])) {
            foreach ($styleData['sources'] as &$source) {
                if (isset($source['tiles']) && is_array($source['tiles'])) {
                    $source['tiles'] = array_map(
                        fn($u) => $this->appendKey($u, $apiKey),
                        $source['tiles']
                    );
                }
                // Some styles return a `url` (TileJSON endpoint) instead of `tiles`
                if (isset($source['url']) && is_string($source['url'])) {
                    $source['url'] = $this->appendKey($source['url'], $apiKey);
                }
            }
            unset($source);
        }

        // Only inject key if AWS actually returned glyphs/sprite. Satellite has neither.
        if (isset($styleData['glyphs']) && is_string($styleData['glyphs'])) {
            $styleData['glyphs'] = $this->appendKey($styleData['glyphs'], $apiKey);
        }

        if (isset($styleData['sprite'])) {
            if (is_array($styleData['sprite'])) {
                foreach ($styleData['sprite'] as &$entry) {
                    if (isset($entry['url'])) $entry['url'] = $this->appendKey($entry['url'], $apiKey);
                }
                unset($entry);
            } elseif (is_string($styleData['sprite'])) {
                $styleData['sprite'] = $this->appendKey($styleData['sprite'], $apiKey);
            }
        }

        Log::info('V2 style processed', [
            'style' => $style,
            'sources_count' => count($styleData['sources']),
            'layers_count' => isset($styleData['layers']) ? count($styleData['layers']) : 0,
        ]);

        return response()->json($styleData);
    }

    private function appendKey(string $url, string $apiKey): string
    {
        if (str_contains($url, 'key=')) return $url;
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . 'key=' . $apiKey;
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

    private function resolveAwsConfig(Request $request): array
    {
        $cfg = [
            'region'      => config('services.aws.region'),
            'api_key'     => config('services.aws.api_key'),
            'place_index' => config('services.aws.place_index'),
            'route_calc'  => config('services.aws.route_calculator'),
            'company'     => null,
        ];

        $slug = $request->header('X-Company-Slug');
        if ($slug) {
            $company = Company::where('slug', $slug)->where('is_active', true)->first();
            if ($company) {
                $cfg['company'] = $company;
                $companyKey = $company->getActiveApiKey();
                if ($companyKey) {
                    $cfg['api_key'] = $companyKey;
                }
            }
        }

        return $cfg;
    }

    private function logUsage(?Company $company, string $endpointType, int $status): void
    {
        ApiUsageLog::create([
            'company_id'      => $company?->id,
            'endpoint_type'   => $endpointType,
            'response_status' => $status,
        ]);
    }

    public function searchSuggestions(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/v2/suggest?key={$cfg['api_key']}";

        try {
            $body = $request->all();
            $response = Http::timeout(15)->post($url, $body);
            $this->logUsage($cfg['company'], 'search_suggestions', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'search_suggestions', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchText(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/v2/search-text?key={$cfg['api_key']}";

        try {
            $body = $request->all();
            // GrabMaps in ap-southeast-1/5 only supports 'TimeZone' (Contact/Access/Phonemes return 400)
            $body['AdditionalFeatures'] = $body['AdditionalFeatures'] ?? ['TimeZone'];
            $response = Http::timeout(15)->post($url, $body);
            $this->logUsage($cfg['company'], 'search_text', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'search_text', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPlace(Request $request, $placeId)
    {
        $cfg = $this->resolveAwsConfig($request);
        // GrabMaps in ap-southeast-1/5 only supports 'TimeZone' (Contact/Access/Phonemes return 400)
        $features = 'TimeZone';
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/v2/place/" . urlencode($placeId)
            . "?key={$cfg['api_key']}&additional-features={$features}&language=" . ($request->query('language', 'en'));

        try {
            $response = Http::timeout(15)->get($url);
            $this->logUsage($cfg['company'], 'get_place', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'get_place', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reverseGeocode(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $url = "https://places.geo.{$cfg['region']}.amazonaws.com/v2/reverse-geocode?key={$cfg['api_key']}";

        try {
            $body = $request->all();
            $body['AdditionalFeatures'] = $body['AdditionalFeatures'] ?? ['TimeZone'];
            $response = Http::timeout(15)->post($url, $body);
            $this->logUsage($cfg['company'], 'reverse_geocode', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'reverse_geocode', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateRoute(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/routes/v0/calculators/{$cfg['route_calc']}/calculate/route?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $request->all());
            $this->logUsage($cfg['company'], 'calculate_route', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'calculate_route', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateRouteMatrix(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/routes/v0/calculators/{$cfg['route_calc']}/calculate/route-matrix?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $request->all());
            $this->logUsage($cfg['company'], 'calculate_route_matrix', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'calculate_route_matrix', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateRouteV2(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/v2/routes?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $request->all());
            $this->logUsage($cfg['company'], 'calculate_route_v2', $response->status());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'calculate_route_v2', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
