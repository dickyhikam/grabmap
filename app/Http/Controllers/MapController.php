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

    /**
     * Routes — adapter: accepts v0-shape request, calls v2 AWS, returns v0-shape response.
     * v0 routes endpoint is no longer authorized for this account's API key — all calls
     * are routed to v2 transparently while preserving the legacy contract for callers.
     */
    public function calculateRoute(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $v0  = $request->all();
        $v2Body = $this->buildV2RouteRequest($v0);

        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/v2/routes?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $v2Body);
            $this->logUsage($cfg['company'], 'calculate_route', $response->status());

            if (!$response->successful()) {
                Log::error('Routes v2 (adapted) error: ' . $response->body());
                return response()->json($response->json() ?? ['error' => $response->body()], $response->status());
            }

            return response()->json($this->translateV2RouteToV0Shape($response->json()), 200);
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'calculate_route', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Routes Matrix — adapter: v0-shape in, v2 AWS, v0-shape out.
     */
    public function calculateRouteMatrix(Request $request)
    {
        $cfg = $this->resolveAwsConfig($request);
        $v0  = $request->all();

        $v2Body = [
            'Origins'         => array_map(fn($p) => ['Position' => $p], $v0['DeparturePositions'] ?? []),
            'Destinations'    => array_map(fn($p) => ['Position' => $p], $v0['DestinationPositions'] ?? []),
            'TravelMode'      => $this->mapTravelModeV0toV2($v0['TravelMode'] ?? 'Car'),
            'RoutingBoundary' => ['Unbounded' => true],
        ];

        $url = "https://routes.geo.{$cfg['region']}.amazonaws.com/v2/route-matrix?key={$cfg['api_key']}";

        try {
            $response = Http::timeout(30)->post($url, $v2Body);
            $this->logUsage($cfg['company'], 'calculate_route_matrix', $response->status());

            if (!$response->successful()) {
                Log::error('Routes Matrix v2 (adapted) error: ' . $response->body());
                return response()->json($response->json() ?? ['error' => $response->body()], $response->status());
            }

            $v2 = $response->json();
            $matrix = [];
            foreach ($v2['RouteMatrix'] ?? [] as $row) {
                $newRow = [];
                foreach ($row as $cell) {
                    if (!empty($cell['Error'])) {
                        $newRow[] = ['Error' => $cell['Error']];
                    } else {
                        $newRow[] = [
                            'Distance'        => (float) ($cell['Distance'] ?? 0) / 1000.0,
                            'DistanceUnit'    => 'Kilometers',
                            'DurationSeconds' => (int) ($cell['Duration'] ?? 0),
                        ];
                    }
                }
                $matrix[] = $newRow;
            }

            return response()->json([
                'RouteMatrix' => $matrix,
                'Summary'     => ['DistanceUnit' => 'Kilometers'],
            ], 200);
        } catch (\Exception $e) {
            $this->logUsage($cfg['company'], 'calculate_route_matrix', 500);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Compose a human-readable instruction string from a v2 TravelStep.
     * v2 doesn't return a ready instruction text; assemble from Type + SteeringDirection + road names.
     */
    private function buildStepInstruction(string $type, ?string $direction, ?string $intensity, ?string $currentRoad, ?string $nextRoad, array $step): string
    {
        switch ($type) {
            case 'Depart':
                return $currentRoad ? "Depart on {$currentRoad}" : 'Depart';
            case 'Arrive':
                return 'Arrive at destination';
            case 'Turn':
                $dir = $direction ?: 'Right';
                $prefix = $intensity === 'Sharp' ? "Sharp {$dir}" :
                          ($intensity === 'Slight' ? "Slight {$dir}" : "Turn {$dir}");
                return $nextRoad ? "{$prefix} onto {$nextRoad}" : $prefix;
            case 'Continue':
                $road = $nextRoad ?: $currentRoad;
                return $road ? "Continue on {$road}" : 'Continue';
            case 'Keep':
                $dir = $direction ?: 'Right';
                return $nextRoad ? "Keep {$dir} onto {$nextRoad}" : "Keep {$dir}";
            case 'UTurn':
                return $nextRoad ? "Make a U-turn onto {$nextRoad}" : 'Make a U-turn';
            case 'Merge':
                $dir = $direction ?: '';
                $base = $dir ? "Merge {$dir}" : 'Merge';
                return $nextRoad ? "{$base} onto {$nextRoad}" : $base;
            case 'Exit':
                $dir = $direction ? " {$direction}" : '';
                $exitNum = $step['ExitNumber'][0]['Value'] ?? null;
                $base = $exitNum ? "Take exit {$exitNum}{$dir}" : ("Take exit{$dir}");
                return $nextRoad ? "{$base} onto {$nextRoad}" : $base;
            case 'Ramp':
                $dir = $direction ? " {$direction}" : '';
                return $nextRoad ? "Take the ramp{$dir} onto {$nextRoad}" : "Take the ramp{$dir}";
            case 'RoundaboutEnter':
                return $nextRoad ? "Enter roundabout, exit onto {$nextRoad}" : 'Enter roundabout';
            case 'RoundaboutPass':
                return 'Pass through roundabout';
            case 'RoundaboutExit':
                return $nextRoad ? "Exit roundabout onto {$nextRoad}" : 'Exit roundabout';
            case 'Ferry':
                return $nextRoad ? "Take ferry to {$nextRoad}" : 'Take ferry';
            default:
                return $nextRoad ? "{$type} onto {$nextRoad}" : $type;
        }
    }

    private function mapTravelModeV0toV2(string $mode): string
    {
        $map = [
            'Car'        => 'Car',
            'Truck'      => 'Truck',
            'Walking'    => 'Pedestrian',
            'Pedestrian' => 'Pedestrian',
            'Bicycle'    => 'Scooter',
            'Scooter'    => 'Scooter',
        ];
        return $map[$mode] ?? 'Car';
    }

    private function buildV2RouteRequest(array $v0): array
    {
        $body = [
            'Origin'      => $v0['DeparturePosition'] ?? [],
            'Destination' => $v0['DestinationPosition'] ?? [],
            'TravelMode'  => $this->mapTravelModeV0toV2($v0['TravelMode'] ?? 'Car'),
            'LegGeometryFormat'             => 'Simple',
            'InstructionsMeasurementSystem' => 'Metric',
            'TravelStepType' => 'TurnByTurn',
        ];

        // Note: PassThrough is not supported in ap-southeast-1 — omit it.
        if (!empty($v0['WaypointPositions']) && is_array($v0['WaypointPositions'])) {
            $body['Waypoints'] = array_map(
                fn($p) => ['Position' => $p],
                $v0['WaypointPositions']
            );
        }

        // Alternative routes (A→B): MaxAlternatives 0–6
        if (isset($v0['MaxAlternatives']) && is_numeric($v0['MaxAlternatives'])) {
            $n = max(0, min(6, (int) $v0['MaxAlternatives']));
            if ($n > 0) $body['MaxAlternatives'] = $n;
        }

        // Departure time (ISO 8601). Falls back to "now" if not provided.
        if (!empty($v0['DepartureTime']) && is_string($v0['DepartureTime'])) {
            $body['DepartureTime'] = $v0['DepartureTime'];
        }

        // Avoidances: pass through only options supported in ap-southeast-1.
        // (DirtRoads/Tunnels/UTurns are rejected by AWS in this region.)
        if (!empty($v0['Avoid']) && is_array($v0['Avoid'])) {
            $allowed = ['TollRoads', 'Ferries', 'ControlledAccessHighways'];
            $avoid = [];
            foreach ($allowed as $key) {
                if (!empty($v0['Avoid'][$key])) $avoid[$key] = true;
            }
            if ($avoid) $body['Avoid'] = $avoid;
        }

        return $body;
    }

    private function translateV2RouteToV0Shape(array $v2): array
    {
        $routes = $v2['Routes'] ?? [];
        if (empty($routes)) {
            return [
                'Legs'    => [],
                'Summary' => ['Distance' => 0, 'DistanceUnit' => 'Kilometers', 'DurationSeconds' => 0],
            ];
        }

        $convert = function(array $route): array {
            $summary = $route['Summary'] ?? [];
            $totalDistKm  = (float) ($summary['Distance'] ?? 0) / 1000.0;
            $totalDurSecs = (int)   ($summary['Duration'] ?? 0);

            $legs = [];
            foreach ($route['Legs'] ?? [] as $leg) {
                $details = $leg['VehicleLegDetails']
                    ?? $leg['PedestrianLegDetails']
                    ?? $leg['TruckLegDetails']
                    ?? [];

                $legDistM = 0.0;
                $legDurS  = 0;
                foreach ($details['Spans'] ?? [] as $span) {
                    $legDistM += (float) ($span['Distance'] ?? 0);
                    $legDurS  += (int)   ($span['Duration'] ?? 0);
                }
                if ($legDistM === 0.0 && $legDurS === 0) {
                    foreach ($details['TravelSteps'] ?? [] as $step) {
                        $legDistM += (float) ($step['Distance'] ?? 0);
                        $legDurS  += (int)   ($step['Duration'] ?? 0);
                    }
                }

                // Turn-by-turn steps (v2 TurnByTurn travel-step type) — synthesize
                // a usable instruction string + extract direction (Left/Right) since v2
                // does not return a ready-made "Instruction" field.
                $steps = [];
                foreach ($details['TravelSteps'] ?? [] as $step) {
                    $type = (string) ($step['Type'] ?? 'Continue');
                    $currentRoad = $step['CurrentRoad']['RoadName'][0]['Value'] ?? null;
                    $nextRoad    = $step['NextRoad']['RoadName'][0]['Value'] ?? null;
                    $turnDetails = $step['TurnStepDetails'] ?? null;
                    $direction   = $turnDetails['SteeringDirection'] ?? null; // Left|Right|Straight
                    $intensity   = $turnDetails['TurnIntensity'] ?? null;     // Sharp|Normal|Slight

                    // Note: human-readable Instruction is built on the frontend (so it can be
                    // localized to the user's current language). Backend only exposes the
                    // structured fields and an English fallback.
                    $instruction = $this->buildStepInstruction($type, $direction, $intensity, $currentRoad, $nextRoad, $step);

                    $steps[] = [
                        'Distance'        => (float) ($step['Distance'] ?? 0) / 1000.0, // km
                        'DurationSeconds' => (int)   ($step['Duration'] ?? 0),
                        'Type'            => $type,
                        'Direction'       => $direction,
                        'Intensity'       => $intensity,
                        'CurrentRoad'     => $currentRoad,
                        'NextRoad'        => $nextRoad,
                        'Instruction'     => $instruction, // fallback only
                        'GeometryOffset'  => (int)   ($step['GeometryOffset'] ?? 0),
                    ];
                }

                $linestring = $leg['Geometry']['LineString'] ?? [];
                $start = $linestring[0] ?? null;
                $end   = $linestring ? $linestring[count($linestring) - 1] : null;

                $legs[] = [
                    'Distance'        => $legDistM / 1000.0,
                    'DistanceUnit'    => 'Kilometers',
                    'DurationSeconds' => $legDurS,
                    'Geometry'        => ['LineString' => $linestring],
                    'StartPosition'   => $start,
                    'EndPosition'     => $end,
                    'Steps'           => $steps,
                ];
            }

            // Major road labels (v2: each item has RoadName.Value)
            $majorRoads = [];
            foreach ($route['MajorRoadLabels'] ?? [] as $label) {
                if (!is_array($label)) continue;
                $name = $label['RoadName']['Value'] ?? null;
                if ($name) $majorRoads[] = (string) $name;
            }

            // Tolls — v2 returns route-level Tolls (when applicable). Extract total cost.
            $tolls = null;
            if (!empty($route['Tolls']) && is_array($route['Tolls'])) {
                $totalValue = 0.0;
                $currency = null;
                $items = [];
                foreach ($route['Tolls'] as $tollSystem) {
                    foreach ($tollSystem['Payment']['Items'] ?? [] as $payItem) {
                        $price = $payItem['Price'] ?? null;
                        if (!$price) continue;
                        $val = (float) ($price['Value'] ?? 0);
                        $cur = $price['Currency'] ?? null;
                        $totalValue += $val;
                        if (!$currency && $cur) $currency = $cur;
                        $items[] = ['value' => $val, 'currency' => $cur];
                    }
                }
                if ($totalValue > 0) {
                    $tolls = [
                        'Total'    => $totalValue,
                        'Currency' => $currency,
                        'Items'    => $items,
                    ];
                }
            }

            return [
                'Legs'    => $legs,
                'Summary' => [
                    'Distance'        => $totalDistKm,
                    'DistanceUnit'    => 'Kilometers',
                    'DurationSeconds' => $totalDurSecs,
                ],
                'MajorRoadLabels' => $majorRoads,
                'Tolls' => $tolls,
            ];
        };

        $primary = $convert($routes[0]);
        if (count($routes) > 1) {
            $primary['Alternatives'] = array_map($convert, array_slice($routes, 1));
        }
        return $primary;
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
