<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWS Route API Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <style>
        :root {
            --grab-green: #00B14F;
            --grab-green-hover: #009543;
        }

        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', sans-serif;
        }

        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .btn-grab {
            background: linear-gradient(135deg, #00B14F 0%, #009543 100%);
            color: white;
            border: none;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.25);
        }

        .btn-grab:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 177, 79, 0.35);
            color: white;
        }

        .btn-grab:disabled {
            opacity: 0.6;
            transform: none;
        }

        #miniMap {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
        }

        .route-status-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .route-status-bar.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .route-status-bar.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .route-status-bar.loading {
            background: #f0f9ff;
            color: #075985;
            border: 1px solid #bae6fd;
        }

        .log-box {
            background: #1e1e2e;
            color: #cdd6f4;
            border-radius: 12px;
            padding: 16px;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.8rem;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .log-box::-webkit-scrollbar {
            width: 6px;
        }

        .log-box::-webkit-scrollbar-thumb {
            background: #585b70;
            border-radius: 10px;
        }

        .log-time { color: #94e2d5; }
        .log-method { color: #f9e2af; font-weight: bold; }
        .log-url { color: #89b4fa; }
        .log-success { color: #a6e3a1; }
        .log-error { color: #f38ba8; }
        .log-key { color: #cba6f7; }

        .status-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .coord-input {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 10px 14px;
            transition: border-color 0.2s;
        }

        .coord-input:focus {
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.1);
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .history-item {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .history-item:hover {
            border-color: var(--grab-green);
            transform: translateX(4px);
        }

        /* API Mode Toggle */
        .api-mode-toggle {
            background: #f1f3f5;
            padding: 4px;
            border-radius: 14px;
            display: flex;
        }

        .api-mode-btn {
            flex: 1;
            text-align: center;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            background: transparent;
            color: #adb5bd;
        }

        .api-mode-btn.active {
            background: white;
            color: var(--grab-green);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .api-mode-btn:hover:not(.active) {
            color: var(--grab-green-hover);
        }

        .mode-info-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .chip-route {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .chip-matrix {
            background: #fef3c7;
            color: #92400e;
        }

        /* Waypoint items */
        .waypoint-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            animation: fadeSlideIn 0.2s ease;
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center gap-3">
                    <a href="/" class="btn btn-light rounded-circle shadow-sm" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h4 class="mb-0 fw-bold">AWS Route API Tester</h4>
                        <small class="text-muted">Debug & test AWS Location Service Route APIs (Grab)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT: Config & Input -->
            <div class="col-lg-5">
                <!-- API Mode Toggle -->
                <div class="card-glass p-4 mb-4">
                    <div class="section-title">
                        <i class="bi bi-toggles me-1"></i> API Endpoint
                    </div>
                    <div class="api-mode-toggle mb-3">
                        <button class="api-mode-btn active" onclick="switchApiMode('route')" id="modeBtn-route">
                            <i class="bi bi-sign-turn-right-fill me-1"></i> Route
                        </button>
                        <button class="api-mode-btn" onclick="switchApiMode('matrix')" id="modeBtn-matrix">
                            <i class="bi bi-grid-3x3 me-1"></i> Route Matrix
                        </button>
                    </div>

                    <!-- Mode description -->
                    <div id="modeDesc-route">
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#f0f9ff;border:1px solid #bae6fd;">
                            <i class="bi bi-info-circle-fill text-primary mt-1"></i>
                            <div>
                                <div class="fw-bold small text-primary">calculate/route</div>
                                <div style="font-size:0.78rem;color:#475569;">
                                    Single route A&rarr;B with optional waypoints.
                                    Returns <b>full geometry (LineString)</b>, distance, duration, dan leg details.
                                    Cocok untuk <b>menggambar rute di peta</b>.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modeDesc-matrix" style="display:none;">
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#fffbeb;border:1px solid #fde68a;">
                            <i class="bi bi-info-circle-fill text-warning mt-1"></i>
                            <div>
                                <div class="fw-bold small text-warning">calculate/route-matrix</div>
                                <div style="font-size:0.78rem;color:#475569;">
                                    N origins &times; M destinations matrix.
                                    Returns <b>distance + duration</b> per pair saja (tanpa geometry).
                                    Cocok untuk <b>optimasi urutan, cari terdekat, batch comparison</b>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Config -->
                <div class="card-glass p-4 mb-4">
                    <div class="section-title">
                        <i class="bi bi-gear me-1"></i> API Configuration
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Route Calculator Name</label>
                        <input type="text" class="form-control coord-input" id="routeCalc" value="{{ env('AWS_MAP_ROUTE') }}">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Region</label>
                            <input type="text" class="form-control coord-input" id="awsRegion" value="{{ env('AWS_REGION') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">API Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control coord-input" id="awsApiKey" value="{{ env('AWS_API_KEY') }}" style="border-radius:10px 0 0 10px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKey()" style="border-radius:0 10px 10px 0;">
                                    <i class="bi bi-eye" id="apiKeyToggleIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departure -->
                <div class="card-glass p-4 mb-4">
                    <div class="section-title">
                        <i class="bi bi-geo-alt-fill text-success me-1"></i> Departure Position
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Longitude</label>
                            <input type="number" step="any" class="form-control coord-input" id="depLng" value="106.817369" placeholder="lng">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Latitude</label>
                            <input type="number" step="any" class="form-control coord-input" id="depLat" value="-6.213694" placeholder="lat">
                        </div>
                    </div>
                </div>

                <!-- Waypoints (Route mode only) -->
                <div class="card-glass p-4 mb-4" id="waypointsCard" style="display:block;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">
                            <i class="bi bi-three-dots-vertical text-info me-1"></i> Waypoints
                            <span class="mode-info-chip chip-route ms-2">Route only</span>
                        </div>
                        <button class="btn btn-sm btn-outline-success rounded-pill" onclick="addWaypoint()">
                            <i class="bi bi-plus-lg me-1"></i> Add
                        </button>
                    </div>
                    <div id="waypointsContainer">
                        <div class="text-center text-muted small py-2" id="waypointEmpty">
                            <i class="bi bi-three-dots d-block mb-1" style="color:#ddd;font-size:1.2rem;"></i>
                            No waypoints added (optional)
                        </div>
                    </div>
                </div>

                <!-- Destination -->
                <div class="card-glass p-4 mb-4">
                    <div class="section-title">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> Destination Position
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Longitude</label>
                            <input type="number" step="any" class="form-control coord-input" id="destLng" value="106.728272" placeholder="lng">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Latitude</label>
                            <input type="number" step="any" class="form-control coord-input" id="destLat" value="-6.285317" placeholder="lat">
                        </div>
                    </div>
                </div>

                <!-- Travel Mode & Options -->
                <div class="card-glass p-4 mb-4">
                    <div class="section-title">
                        <i class="bi bi-sliders me-1"></i> Request Options
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Travel Mode</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="travelMode" id="mCar" value="Car" checked>
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mCar">
                                <i class="bi bi-car-front-fill me-1"></i> Car
                            </label>
                            <input type="radio" class="btn-check" name="travelMode" id="mBike" value="Motorcycle">
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mBike">
                                <i class="bi bi-scooter me-1"></i> Motorcycle
                            </label>
                            <input type="radio" class="btn-check" name="travelMode" id="mWalk" value="Walking">
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mWalk">
                                <i class="bi bi-person-walking me-1"></i> Walking
                            </label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Distance Unit</label>
                            <select class="form-select coord-input" id="distUnit">
                                <option value="Kilometers" selected>Kilometers</option>
                                <option value="Miles">Miles</option>
                            </select>
                        </div>
                        <div class="col-6" id="routeOnlyOpts">
                            <label class="form-label small fw-semibold">Route Options</label>
                            <div class="d-flex flex-column gap-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="optDepartNow" checked>
                                    <label class="form-check-label small" for="optDepartNow">DepartNow</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="optIncludeGeometry" checked>
                                    <label class="form-check-label small" for="optIncludeGeometry">IncludeLegGeometry</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Send Button -->
                <button class="btn btn-grab w-100 py-3 mb-4" onclick="sendRequest()" id="btnSend">
                    <i class="bi bi-send-fill me-2"></i> Send <span id="btnModeLabel">Route</span> Request
                </button>

                <!-- Mini Map -->
                <div class="card-glass p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">
                            <i class="bi bi-map me-1"></i> Preview Map & Route
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="clearRouteFromMap()" id="btnClearRoute" style="display:none;">
                            <i class="bi bi-eraser me-1"></i> Clear Route
                        </button>
                    </div>
                    <div id="miniMap"></div>
                    <div id="mapRouteStatus"></div>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Click map to set coordinates. First click = departure, second = destination.
                    </small>
                    <div class="d-flex gap-3 mt-2" style="font-size:0.75rem;">
                        <span><i class="bi bi-circle-fill text-success me-1" style="font-size:0.5rem;"></i> Departure</span>
                        <span><i class="bi bi-circle-fill text-danger me-1" style="font-size:0.5rem;"></i> Destination</span>
                        <span><i class="bi bi-dash-lg text-primary me-1"></i> Route (real road)</span>
                        <span style="color:#999;"><i class="bi bi-three-dots me-1"></i> Fallback (straight)</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Log Output -->
            <div class="col-lg-7">
                <!-- Status Summary -->
                <div class="card-glass p-4 mb-4" id="statusCard" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold" id="statusTitle">-</span>
                            <div class="text-muted small" id="statusSub">-</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="mode-info-chip" id="statusModeChip">-</span>
                            <span class="status-badge" id="statusBadge">-</span>
                        </div>
                    </div>
                </div>

                <!-- Request Log -->
                <div class="card-glass p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">
                            <i class="bi bi-arrow-up-circle me-1"></i> Request
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="copyToClipboard('requestLog')">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                    <div class="log-box" id="requestLog">
<span class="log-key">// Click "Send" to see the request payload here</span>
                    </div>
                </div>

                <!-- Response Log -->
                <div class="card-glass p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">
                            <i class="bi bi-arrow-down-circle me-1"></i> Response
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="copyToClipboard('responseLog')">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                    <div class="log-box" id="responseLog">
<span class="log-key">// Response will appear here</span>
                    </div>
                </div>

                <!-- History -->
                <div class="card-glass p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">
                            <i class="bi bi-clock-history me-1"></i> Request History
                        </div>
                        <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="clearHistory()">
                            <i class="bi bi-trash3 me-1"></i> Clear
                        </button>
                    </div>
                    <div id="historyContainer">
                        <div class="text-center text-muted small py-3">No requests yet</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        /* =========================================
           GLOBAL STATE
           ========================================= */
        let miniMap = null;
        let depMarker = null;
        let destMarker = null;
        let waypointMarkers = [];
        let clickCount = 0;
        let requestHistory = [];
        let currentApiMode = 'route'; // 'route' or 'matrix'
        let waypointCounter = 0;


        /* =========================================
           API MODE TOGGLE
           ========================================= */
        function switchApiMode(mode) {
            currentApiMode = mode;

            // Toggle buttons
            document.getElementById('modeBtn-route').classList.toggle('active', mode === 'route');
            document.getElementById('modeBtn-matrix').classList.toggle('active', mode === 'matrix');

            // Toggle descriptions
            document.getElementById('modeDesc-route').style.display = mode === 'route' ? 'block' : 'none';
            document.getElementById('modeDesc-matrix').style.display = mode === 'matrix' ? 'block' : 'none';

            // Toggle waypoints card (route only)
            document.getElementById('waypointsCard').style.display = mode === 'route' ? 'block' : 'none';

            // Toggle route-only options
            document.getElementById('routeOnlyOpts').style.display = mode === 'route' ? 'block' : 'none';

            // Update button label
            document.getElementById('btnModeLabel').textContent = mode === 'route' ? 'Route' : 'Route Matrix';
        }


        /* =========================================
           WAYPOINTS MANAGEMENT (Route mode)
           ========================================= */
        function addWaypoint() {
            waypointCounter++;
            const id = waypointCounter;
            const container = document.getElementById('waypointsContainer');
            document.getElementById('waypointEmpty').style.display = 'none';

            const div = document.createElement('div');
            div.className = 'waypoint-item';
            div.id = `wp-${id}`;
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold small text-info"><i class="bi bi-geo me-1"></i> Waypoint ${id}</span>
                    <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" onclick="removeWaypoint(${id})" style="font-size:0.7rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="number" step="any" class="form-control coord-input py-1" placeholder="lng" id="wpLng-${id}" style="font-size:0.85rem;">
                    </div>
                    <div class="col-6">
                        <input type="number" step="any" class="form-control coord-input py-1" placeholder="lat" id="wpLat-${id}" style="font-size:0.85rem;">
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        function removeWaypoint(id) {
            const el = document.getElementById(`wp-${id}`);
            if (el) el.remove();

            const container = document.getElementById('waypointsContainer');
            const items = container.querySelectorAll('.waypoint-item');
            if (items.length === 0) {
                document.getElementById('waypointEmpty').style.display = 'block';
            }
        }

        function getWaypoints() {
            const items = document.querySelectorAll('#waypointsContainer .waypoint-item');
            const waypoints = [];
            items.forEach(item => {
                const id = item.id.replace('wp-', '');
                const lng = parseFloat(document.getElementById(`wpLng-${id}`).value);
                const lat = parseFloat(document.getElementById(`wpLat-${id}`).value);
                if (!isNaN(lng) && !isNaN(lat)) {
                    waypoints.push([lng, lat]);
                }
            });
            return waypoints;
        }


        /* =========================================
           MINI MAP
           ========================================= */
        function initMiniMap() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const mapName = "{{ env('AWS_MAP_NAME') }}";

            miniMap = new maplibregl.Map({
                container: 'miniMap',
                style: `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`,
                center: [106.8456, -6.2088],
                zoom: 11,
                attributionControl: false
            });

            miniMap.addControl(new maplibregl.NavigationControl(), 'top-right');

            miniMap.on('load', () => {
                updateMapMarkers();
            });

            miniMap.on('click', (e) => {
                const lng = e.lngLat.lng.toFixed(6);
                const lat = e.lngLat.lat.toFixed(6);

                if (clickCount % 2 === 0) {
                    document.getElementById('depLng').value = lng;
                    document.getElementById('depLat').value = lat;
                } else {
                    document.getElementById('destLng').value = lng;
                    document.getElementById('destLat').value = lat;
                }
                clickCount++;
                updateMapMarkers();
            });
        }

        function updateMapMarkers() {
            const depLng = parseFloat(document.getElementById('depLng').value);
            const depLat = parseFloat(document.getElementById('depLat').value);
            const destLng = parseFloat(document.getElementById('destLng').value);
            const destLat = parseFloat(document.getElementById('destLat').value);

            if (depMarker) depMarker.remove();
            if (destMarker) destMarker.remove();

            if (!isNaN(depLng) && !isNaN(depLat)) {
                depMarker = new maplibregl.Marker({ color: '#00B14F' })
                    .setLngLat([depLng, depLat])
                    .setPopup(new maplibregl.Popup({ offset: 25 }).setText('Departure'))
                    .addTo(miniMap);
            }

            if (!isNaN(destLng) && !isNaN(destLat)) {
                destMarker = new maplibregl.Marker({ color: '#dc3545' })
                    .setLngLat([destLng, destLat])
                    .setPopup(new maplibregl.Popup({ offset: 25 }).setText('Destination'))
                    .addTo(miniMap);
            }

            if (!isNaN(depLng) && !isNaN(depLat) && !isNaN(destLng) && !isNaN(destLat)) {
                const bounds = new maplibregl.LngLatBounds();
                bounds.extend([depLng, depLat]);
                bounds.extend([destLng, destLat]);
                miniMap.fitBounds(bounds, { padding: 60 });
            }
        }


        /* =========================================
           ROUTE DRAWING ON MAP
           ========================================= */
        function removeRouteFromMap() {
            ['routeLine', 'routeLineOutline', 'routeLineDashed'].forEach(id => {
                if (miniMap.getLayer(id)) miniMap.removeLayer(id);
            });
            if (miniMap.getSource('routeSource')) miniMap.removeSource('routeSource');
            document.getElementById('btnClearRoute').style.display = 'none';
            document.getElementById('mapRouteStatus').innerHTML = '';
        }

        function clearRouteFromMap() {
            removeRouteFromMap();
        }

        function drawRealRouteOnMap(coordinates) {
            removeRouteFromMap();

            miniMap.addSource('routeSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: { type: 'LineString', coordinates }
                }
            });

            miniMap.addLayer({
                id: 'routeLineOutline',
                type: 'line',
                source: 'routeSource',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#ffffff', 'line-width': 7, 'line-opacity': 0.9 }
            });

            miniMap.addLayer({
                id: 'routeLine',
                type: 'line',
                source: 'routeSource',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#007bff', 'line-width': 4, 'line-opacity': 0.9 }
            });

            const bounds = new maplibregl.LngLatBounds();
            coordinates.forEach(c => bounds.extend(c));
            miniMap.fitBounds(bounds, { padding: 60 });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function drawDashedLineOnMap(depCoords, destCoords) {
            removeRouteFromMap();

            miniMap.addSource('routeSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: { type: 'LineString', coordinates: [depCoords, destCoords] }
                }
            });

            miniMap.addLayer({
                id: 'routeLineDashed',
                type: 'line',
                source: 'routeSource',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#dc3545', 'line-width': 3, 'line-opacity': 0.7, 'line-dasharray': [3, 3] }
            });

            const bounds = new maplibregl.LngLatBounds();
            bounds.extend(depCoords);
            bounds.extend(destCoords);
            miniMap.fitBounds(bounds, { padding: 60 });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function setMapRouteStatus(type, message) {
            const container = document.getElementById('mapRouteStatus');
            const icon = type === 'success' ? 'bi-check-circle-fill' :
                         type === 'error' ? 'bi-exclamation-triangle-fill' :
                         'bi-hourglass-split';
            container.innerHTML = `<div class="route-status-bar ${type}"><i class="bi ${icon}"></i> ${message}</div>`;
        }


        /* =========================================
           SEND REQUEST (Mode-aware)
           ========================================= */
        async function sendRequest() {
            const btn = document.getElementById('btnSend');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';

            if (currentApiMode === 'route') {
                await sendRouteRequest();
            } else {
                await sendMatrixRequest();
            }

            btn.disabled = false;
            const label = currentApiMode === 'route' ? 'Route' : 'Route Matrix';
            btn.innerHTML = `<i class="bi bi-send-fill me-2"></i> Send ${label} Request`;
        }

        // --- MODE 1: calculate/route ---
        async function sendRouteRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const routeCalc = document.getElementById('routeCalc').value;
            const travelMode = document.querySelector('input[name="travelMode"]:checked').value;
            const distUnit = document.getElementById('distUnit').value;
            const departNow = document.getElementById('optDepartNow').checked;
            const includeGeometry = document.getElementById('optIncludeGeometry').checked;

            const depLng = parseFloat(document.getElementById('depLng').value);
            const depLat = parseFloat(document.getElementById('depLat').value);
            const destLng = parseFloat(document.getElementById('destLng').value);
            const destLat = parseFloat(document.getElementById('destLat').value);
            const waypoints = getWaypoints();

            const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalc}/calculate/route?key=${apiKey}`;

            const requestBody = {
                DeparturePosition: [depLng, depLat],
                DestinationPosition: [destLng, destLat],
                TravelMode: travelMode,
                DistanceUnit: distUnit,
                IncludeLegGeometry: includeGeometry
            };

            if (departNow) requestBody.DepartNow = true;
            if (waypoints.length > 0) requestBody.WaypointPositions = waypoints;

            const startTime = performance.now();
            const timeStr = new Date().toISOString();

            // Show request
            document.getElementById('requestLog').innerHTML = formatJson({
                Time: timeStr,
                Method: "POST",
                URL: url.replace(/key=[^&]+/, 'key=***REDACTED***'),
                Headers: { "Content-Type": "application/json" },
                Body: requestBody
            });

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });

                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();

                document.getElementById('responseLog').innerHTML = formatJson({
                    Time: new Date().toISOString(),
                    HttpStatus: response.status,
                    ResponseTime: elapsed + 'ms',
                    Body: data
                });

                const statusCard = document.getElementById('statusCard');
                statusCard.style.display = 'block';
                setStatusChip('route');

                if (response.ok && data.Summary) {
                    const dist = data.Summary.Distance.toFixed(2);
                    const dur = Math.round(data.Summary.DurationSeconds / 60);
                    const legs = data.Legs?.length || 0;

                    document.getElementById('statusTitle').textContent = 'Route Found';
                    document.getElementById('statusSub').textContent =
                        `Distance: ${dist} km | Duration: ${dur} min | Legs: ${legs} | ${elapsed}ms`;
                    document.getElementById('statusBadge').className = 'status-badge bg-success text-white';
                    document.getElementById('statusBadge').textContent = `${response.status} OK`;

                    // Draw route on map if geometry available
                    if (includeGeometry && data.Legs) {
                        const allCoords = [];
                        data.Legs.forEach(leg => {
                            if (leg.Geometry?.LineString) {
                                allCoords.push(...leg.Geometry.LineString);
                            }
                        });
                        if (allCoords.length > 0) {
                            drawRealRouteOnMap(allCoords);
                            setMapRouteStatus('success', `Route drawn: ${dist} km, ~${dur} min (${travelMode})`);
                        }
                    }
                } else {
                    const errMsg = data.message || data.Message || 'Request failed';
                    document.getElementById('statusTitle').textContent = 'Error';
                    document.getElementById('statusSub').textContent = `${errMsg} | ${elapsed}ms`;
                    document.getElementById('statusBadge').className = 'status-badge bg-danger text-white';
                    document.getElementById('statusBadge').textContent = `${response.status} ERROR`;

                    drawDashedLineOnMap([depLng, depLat], [destLng, destLat]);
                    setMapRouteStatus('error', `Route failed. Showing straight line.`);
                }

                addHistory({
                    time: timeStr, apiMode: 'route', travelMode,
                    depLng, depLat, destLng, destLat,
                    status: response.status,
                    hasError: !response.ok || !data.Summary,
                    errorCode: (!response.ok) ? (data.message || 'Error') : null,
                    elapsed
                });

            } catch (err) {
                handleFetchError(err);
            }
        }

        // --- MODE 2: calculate/route-matrix ---
        async function sendMatrixRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const routeCalc = document.getElementById('routeCalc').value;
            const travelMode = document.querySelector('input[name="travelMode"]:checked').value;
            const distUnit = document.getElementById('distUnit').value;

            const depLng = parseFloat(document.getElementById('depLng').value);
            const depLat = parseFloat(document.getElementById('depLat').value);
            const destLng = parseFloat(document.getElementById('destLng').value);
            const destLat = parseFloat(document.getElementById('destLat').value);

            const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalc}/calculate/route-matrix?key=${apiKey}`;

            const requestBody = {
                DeparturePositions: [[depLng, depLat]],
                DestinationPositions: [[destLng, destLat]],
                TravelMode: travelMode,
                DistanceUnit: distUnit
            };

            const startTime = performance.now();
            const timeStr = new Date().toISOString();

            document.getElementById('requestLog').innerHTML = formatJson({
                Time: timeStr,
                Method: "POST",
                URL: url.replace(/key=[^&]+/, 'key=***REDACTED***'),
                Headers: { "Content-Type": "application/json" },
                Body: requestBody
            });

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });

                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();

                document.getElementById('responseLog').innerHTML = formatJson({
                    Time: new Date().toISOString(),
                    HttpStatus: response.status,
                    ResponseTime: elapsed + 'ms',
                    Body: data
                });

                const statusCard = document.getElementById('statusCard');
                statusCard.style.display = 'block';
                setStatusChip('matrix');

                const hasError = data.Summary?.ErrorCount > 0 ||
                    data.RouteMatrix?.[0]?.[0]?.Error;

                if (response.ok && !hasError) {
                    const matrix = data.RouteMatrix[0][0];
                    document.getElementById('statusTitle').textContent = 'Matrix Result Found';
                    document.getElementById('statusSub').textContent =
                        `Distance: ${matrix.Distance?.toFixed(2)} km | Duration: ${Math.round((matrix.DurationSeconds || 0) / 60)} min | DataSource: ${data.Summary?.DataSource || '-'} | ${elapsed}ms`;
                    document.getElementById('statusBadge').className = 'status-badge bg-success text-white';
                    document.getElementById('statusBadge').textContent = `${response.status} OK`;

                    // Matrix has no geometry, fetch route for drawing
                    setMapRouteStatus('loading', 'Matrix OK. Fetching route geometry for map...');
                    try {
                        const routeUrl = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalc}/calculate/route?key=${apiKey}`;
                        const routeRes = await fetch(routeUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                DeparturePosition: [depLng, depLat],
                                DestinationPosition: [destLng, destLat],
                                TravelMode: travelMode,
                                DistanceUnit: distUnit,
                                DepartNow: true,
                                IncludeLegGeometry: true
                            })
                        });
                        const routeData = await routeRes.json();
                        if (routeData.Legs?.[0]?.Geometry?.LineString) {
                            drawRealRouteOnMap(routeData.Legs[0].Geometry.LineString);
                            setMapRouteStatus('success', `Route drawn from separate /route call (${travelMode})`);
                        } else {
                            throw new Error('No geometry');
                        }
                    } catch {
                        drawDashedLineOnMap([depLng, depLat], [destLng, destLat]);
                        setMapRouteStatus('error', 'Could not fetch geometry. Showing straight line.');
                    }
                } else {
                    const errCode = data.RouteMatrix?.[0]?.[0]?.Error?.Code || 'Unknown';
                    document.getElementById('statusTitle').textContent = `Error: ${errCode}`;
                    document.getElementById('statusSub').textContent =
                        `TravelMode: ${travelMode} | DataSource: ${data.Summary?.DataSource || '-'} | ${elapsed}ms`;
                    document.getElementById('statusBadge').className = 'status-badge bg-danger text-white';
                    document.getElementById('statusBadge').textContent = `${response.status} ERROR`;

                    drawDashedLineOnMap([depLng, depLat], [destLng, destLat]);
                    setMapRouteStatus('error', `${errCode}: No route found. Showing straight line.`);
                }

                addHistory({
                    time: timeStr, apiMode: 'matrix', travelMode,
                    depLng, depLat, destLng, destLat,
                    status: response.status, hasError,
                    errorCode: hasError ? (data.RouteMatrix?.[0]?.[0]?.Error?.Code || 'Error') : null,
                    elapsed
                });

            } catch (err) {
                handleFetchError(err);
            }
        }

        function handleFetchError(err) {
            document.getElementById('responseLog').innerHTML =
                `<span class="log-error">FETCH ERROR: ${err.message}</span>`;
            document.getElementById('statusCard').style.display = 'block';
            document.getElementById('statusTitle').textContent = 'Network Error';
            document.getElementById('statusSub').textContent = err.message;
            document.getElementById('statusBadge').className = 'status-badge bg-danger text-white';
            document.getElementById('statusBadge').textContent = 'FAILED';

            const dLng = parseFloat(document.getElementById('depLng').value);
            const dLat = parseFloat(document.getElementById('depLat').value);
            const xLng = parseFloat(document.getElementById('destLng').value);
            const xLat = parseFloat(document.getElementById('destLat').value);
            if (!isNaN(dLng) && !isNaN(xLng)) {
                drawDashedLineOnMap([dLng, dLat], [xLng, xLat]);
                setMapRouteStatus('error', 'Network error. Showing straight line.');
            }
        }

        function setStatusChip(mode) {
            const chip = document.getElementById('statusModeChip');
            if (mode === 'route') {
                chip.className = 'mode-info-chip chip-route';
                chip.innerHTML = '<i class="bi bi-sign-turn-right-fill"></i> Route';
            } else {
                chip.className = 'mode-info-chip chip-matrix';
                chip.innerHTML = '<i class="bi bi-grid-3x3"></i> Matrix';
            }
        }


        /* =========================================
           FORMAT & UTILITIES
           ========================================= */
        function formatJson(obj) {
            const json = JSON.stringify(obj, null, 2);
            return json
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"([^"]+)":/g, '<span class="log-key">"$1"</span>:')
                .replace(/: "([^"]*http[^"]*)"/g, ': "<span class="log-url">$1</span>"')
                .replace(/: "([^"]*)"/g, ': "<span class="log-time">$1</span>"')
                .replace(/: (\d+\.?\d*)/g, ': <span class="log-method">$1</span>')
                .replace(/"RouteNotFound"/g, '"<span class="log-error">RouteNotFound</span>"')
                .replace(/"Error"/g, '"<span class="log-error">Error</span>"');
        }

        function toggleApiKey() {
            const input = document.getElementById('awsApiKey');
            const icon = document.getElementById('apiKeyToggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target.closest('button');
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check me-1"></i> Copied!';
                setTimeout(() => btn.innerHTML = original, 1500);
            });
        }


        /* =========================================
           HISTORY
           ========================================= */
        function addHistory(entry) {
            requestHistory.unshift(entry);
            if (requestHistory.length > 10) requestHistory.pop();
            renderHistory();
        }

        function renderHistory() {
            const container = document.getElementById('historyContainer');
            if (requestHistory.length === 0) {
                container.innerHTML = '<div class="text-center text-muted small py-3">No requests yet</div>';
                return;
            }

            container.innerHTML = requestHistory.map((h, i) => {
                const statusColor = h.hasError ? 'text-danger' : 'text-success';
                const statusIcon = h.hasError ? 'bi-x-circle-fill' : 'bi-check-circle-fill';
                const statusText = h.hasError ? (h.errorCode || 'Error') : 'OK';
                const time = new Date(h.time).toLocaleTimeString();
                const modeChip = h.apiMode === 'route'
                    ? '<span class="mode-info-chip chip-route" style="font-size:0.6rem;padding:2px 6px;">Route</span>'
                    : '<span class="mode-info-chip chip-matrix" style="font-size:0.6rem;padding:2px 6px;">Matrix</span>';

                return `
                    <div class="history-item" onclick="loadHistory(${i})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi ${statusIcon} ${statusColor}"></i>
                                ${modeChip}
                                <span class="fw-semibold small">${h.travelMode}</span>
                                <span class="text-muted small">${time}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">${h.elapsed}ms</span>
                                <span class="badge ${h.hasError ? 'text-bg-danger' : 'text-bg-success'}">${statusText}</span>
                            </div>
                        </div>
                        <div class="text-muted mt-1" style="font-size:0.7rem;">
                            [${h.depLng}, ${h.depLat}] &rarr; [${h.destLng}, ${h.destLat}]
                        </div>
                    </div>`;
            }).join('');
        }

        function loadHistory(index) {
            const h = requestHistory[index];
            document.getElementById('depLng').value = h.depLng;
            document.getElementById('depLat').value = h.depLat;
            document.getElementById('destLng').value = h.destLng;
            document.getElementById('destLat').value = h.destLat;

            const modeRadio = document.querySelector(`input[name="travelMode"][value="${h.travelMode}"]`);
            if (modeRadio) modeRadio.checked = true;

            if (h.apiMode) switchApiMode(h.apiMode);
            updateMapMarkers();
        }

        function clearHistory() {
            requestHistory = [];
            renderHistory();
        }


        /* =========================================
           INIT
           ========================================= */
        document.addEventListener('DOMContentLoaded', () => {
            initMiniMap();
            ['depLng', 'depLat', 'destLng', 'destLat'].forEach(id => {
                document.getElementById(id).addEventListener('change', updateMapMarkers);
            });
        });
    </script>
</body>

</html>
