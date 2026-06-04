<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWS Location Service API Tester</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

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
            height: 450px;
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

        .log-time {
            color: #94e2d5;
        }

        .log-method {
            color: #f9e2af;
            font-weight: bold;
        }

        .log-url {
            color: #89b4fa;
        }

        .log-success {
            color: #a6e3a1;
        }

        .log-error {
            color: #f38ba8;
        }

        .log-key {
            color: #cba6f7;
        }

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
            padding: 10px 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.8rem;
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

        .chip-location {
            background: #ede9fe;
            color: #6d28d9;
        }

        .chip-maps {
            background: #cffafe;
            color: #0e7490;
        }

        .chip-geofence {
            background: #ffedd5;
            color: #9a3412;
        }

        .chip-tracking {
            background: #fce7f3;
            color: #9d174d;
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

        /* Matrix destination items */
        .matrix-dest-item {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            animation: fadeSlideIn 0.2s ease;
        }

        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Location sub-mode tabs */
        .loc-submode-toggle {
            background: #f8f9fa;
            padding: 3px;
            border-radius: 10px;
            display: flex;
            gap: 2px;
        }

        .loc-submode-btn {
            flex: 1;
            text-align: center;
            padding: 7px 4px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: #adb5bd;
        }

        .loc-submode-btn.active {
            background: white;
            color: #6d28d9;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .loc-submode-btn:hover:not(.active) {
            color: #6d28d9;
        }

        /* Location result items */
        .loc-result-item {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.82rem;
        }

        .loc-result-item:hover {
            border-color: #6d28d9;
            transform: translateX(4px);
            background: #faf5ff;
        }

        /* Maps/Geofence/Tracking sub-mode tabs — reuse loc pattern */
        .maps-submode-toggle,
        .geo-submode-toggle,
        .trk-submode-toggle {
            background: #f8f9fa;
            padding: 3px;
            border-radius: 10px;
            display: flex;
            gap: 2px;
        }

        .maps-submode-btn,
        .geo-submode-btn,
        .trk-submode-btn {
            flex: 1;
            text-align: center;
            padding: 7px 4px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: #adb5bd;
        }

        .maps-submode-btn.active {
            background: white;
            color: #0891b2;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .maps-submode-btn:hover:not(.active) {
            color: #0891b2;
        }

        .geo-submode-btn.active {
            background: white;
            color: #ea580c;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .geo-submode-btn:hover:not(.active) {
            color: #ea580c;
        }

        .trk-submode-btn.active {
            background: white;
            color: #db2777;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .trk-submode-btn:hover:not(.active) {
            color: #db2777;
        }

        /* Geofence/Tracking result items */
        .geo-result-item,
        .trk-result-item {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.82rem;
        }

        .geo-result-item:hover {
            border-color: #ea580c;
            transform: translateX(4px);
            background: #fff7ed;
        }

        .trk-result-item:hover {
            border-color: #db2777;
            transform: translateX(4px);
            background: #fdf2f8;
        }

        /* Reference Modal */
        .ref-modal .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }

        .ref-modal .modal-header {
            background: linear-gradient(135deg, #00B14F 0%, #009543 100%);
            color: white;
            border: none;
            padding: 20px 25px;
        }

        .ref-modal .modal-body {
            background: #f8f9fa;
            padding: 25px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .ref-card {
            background: white;
            border-radius: 14px;
            border: 1px solid #e9ecef;
            padding: 18px;
            margin-bottom: 16px;
            transition: 0.2s;
        }

        .ref-card:hover {
            border-color: var(--grab-green);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .ref-card-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ref-badge {
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .ref-badge-yes {
            background: #dcfce7;
            color: #166534;
        }

        .ref-badge-no {
            background: #fee2e2;
            color: #991b1b;
        }

        .ref-badge-exclusive {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .ref-badge-limited {
            background: #fef3c7;
            color: #92400e;
        }

        .ref-endpoint {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 6px;
            font-size: 0.78rem;
            font-family: 'Fira Code', monospace;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ref-method {
            font-weight: 700;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .ref-method-post {
            background: #dcfce7;
            color: #166534;
        }

        .ref-method-get {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .ref-method-put {
            background: #fef3c7;
            color: #92400e;
        }

        .ref-badge-auth {
            background: #fce7f3;
            color: #9d174d;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('pageHome') }}" class="btn btn-light rounded-circle shadow-sm" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="flex-grow-1">
                        <h4 class="mb-0 fw-bold">AWS Location Service API Tester</h4>
                        <small class="text-muted">Debug & test AWS Location Service APIs — Routes, Places, Maps, Geofencing & Tracking (Grab)</small>
                    </div>
                    <button class="btn rounded-pill px-3" type="button" id="btnChangeApiKey" onclick="showApiKeyGate(document.getElementById('awsApiKey').value)" style="background:#7c3aed;color:#fff;font-weight:600;border:none;">
                        <i class="bi bi-key-fill me-1"></i> API Key
                        <span class="badge bg-light text-dark ms-1" id="apiKeyStatusBadge" style="font-size:0.6rem;font-weight:600;">●</span>
                    </button>
                    <button class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#refModal">
                        <i class="bi bi-book me-1"></i> API Reference
                    </button>
                </div>
            </div>
        </div>

        <!-- Map — 100% width -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-glass p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">
                            <i class="bi bi-map me-1"></i> Preview Map
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="clearAllMapLayers()" id="btnClearRoute" style="display:none;">
                            <i class="bi bi-eraser me-1"></i> Clear
                        </button>
                    </div>
                    <div id="miniMap"></div>
                    <div id="mapRouteStatus"></div>

                    <!-- Route & Matrix Input (inside map card) — same UI as API Configuration -->
                    <div class="mt-4 pt-4 border-top" id="cardRouteMatrix" style="border-color:rgba(0,0,0,0.08)!important;">
                        <div class="section-title">
                            <i class="bi bi-sign-turn-right-fill me-1"></i> Route & Matrix Input
                        </div>

                        <!-- Departure -->
                        <div class="mb-3" id="cardDeparture">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0"><i class="bi bi-geo-alt-fill text-success me-1"></i> Departure Position</label>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill py-0 px-2" onclick="setPickFromMapTarget('dep')" title="Klik peta untuk set koordinat">
                                    <i class="bi bi-crosshair me-1"></i> Pick from map
                                </button>
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

                        <!-- Waypoints (route only) -->
                        <div class="mb-3" id="waypointsCard">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0"><i class="bi bi-three-dots-vertical text-info me-1"></i> Waypoints <span class="text-muted fw-normal">(Route only)</span></label>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" onclick="setPickFromMapTarget('waypoint')" title="Klik peta untuk tambah waypoint">
                                        <i class="bi bi-crosshair me-1"></i> From map
                                    </button>
                                    <button class="btn btn-sm btn-outline-success rounded-pill py-0 px-2" onclick="addWaypoint()">
                                        <i class="bi bi-plus-lg"></i> Add
                                    </button>
                                </div>
                            </div>
                            <div id="waypointsContainer">
                                <div class="text-center text-muted small py-2" id="waypointEmpty">
                                    <i class="bi bi-three-dots d-block mb-1" style="color:#ddd;font-size:1rem;"></i>
                                    No waypoints (optional)
                                </div>
                            </div>
                        </div>

                        <!-- Destination (route) -->
                        <div class="mb-3" id="cardDestination">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Destination Position</label>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" onclick="setPickFromMapTarget('dest')" title="Klik peta untuk set koordinat">
                                    <i class="bi bi-crosshair me-1"></i> Pick from map
                                </button>
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

                        <!-- Matrix Destinations (matrix only) -->
                        <div class="mb-3" id="cardMatrixDests" style="display:none;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Destinations <span class="badge bg-danger rounded-pill ms-1" id="matrixDestCount">1</span></label>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" onclick="setPickFromMapTarget('matrixDest')" title="Klik peta untuk tambah destination">
                                        <i class="bi bi-crosshair me-1"></i> From map
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" onclick="addMatrixDest()">
                                        <i class="bi bi-plus-lg"></i> Add
                                    </button>
                                </div>
                            </div>
                            <div id="matrixDestsContainer">
                                <div class="matrix-dest-item" data-id="1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold small text-danger"><i class="bi bi-geo me-1"></i> Dest 1</span>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" onclick="removeMatrixDest(1)" style="font-size:0.7rem;">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Longitude</label>
                                            <input type="number" step="any" class="form-control coord-input" value="106.728272" placeholder="lng">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Latitude</label>
                                            <input type="number" step="any" class="form-control coord-input" value="-6.285317" placeholder="lat">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <small class="text-muted d-block mt-2" id="mapHintRoute">
                        <i class="bi bi-info-circle me-1"></i>
                        Klik tombol <b>Pick from map</b> lalu klik peta untuk set koordinat.
                    </small>
                    <small class="text-muted d-block mt-2" id="mapHintMatrix" style="display:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Klik tombol <b>Pick from map</b> / <b>Add from map</b> lalu klik peta untuk set koordinat.
                    </small>
                    <small class="text-muted d-block mt-2" id="mapHintLocation" style="display:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Click map to fill reverse geocode coordinates. Search results appear as markers.
                    </small>
                    <small class="text-muted d-block mt-2" id="mapHintMaps" style="display:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Click "Fill from map center" to get tile coordinates from current view.
                    </small>
                    <small class="text-muted d-block mt-2" id="mapHintGeofence" style="display:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Click map to set geofence center / evaluation position. Geofence boundaries shown as polygons.
                    </small>
                    <small class="text-muted d-block mt-2" id="mapHintTracking" style="display:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Click map to set device position. Device markers and history trails shown on map.
                    </small>
                    <div class="d-flex flex-wrap gap-3 mt-2" style="font-size:0.75rem;">
                        <span><i class="bi bi-circle-fill text-success me-1" style="font-size:0.5rem;"></i> Departure</span>
                        <span><i class="bi bi-circle-fill text-danger me-1" style="font-size:0.5rem;"></i> Destination</span>
                        <span><i class="bi bi-dash-lg text-primary me-1"></i> Route</span>
                        <span style="color:#6d28d9;"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Location result</span>
                        <span style="color:#ea580c;"><i class="bi bi-square-fill me-1" style="font-size:0.5rem;"></i> Geofence</span>
                        <span style="color:#db2777;"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Tracking</span>
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
                    <div class="api-mode-toggle mb-3" style="flex-wrap:wrap;gap:4px;">
                        <button class="api-mode-btn active" onclick="switchApiMode('route')" id="modeBtn-route" style="flex:1 1 30%;">
                            <i class="bi bi-sign-turn-right-fill me-1"></i> Route
                        </button>
                        <button class="api-mode-btn" onclick="switchApiMode('matrix')" id="modeBtn-matrix" style="flex:1 1 30%;">
                            <i class="bi bi-grid-3x3 me-1"></i> Matrix
                        </button>
                        <button class="api-mode-btn" onclick="switchApiMode('location')" id="modeBtn-location" style="flex:1 1 30%;">
                            <i class="bi bi-geo-alt-fill me-1"></i> Location
                        </button>
                        <button class="api-mode-btn" onclick="switchApiMode('maps')" id="modeBtn-maps" style="flex:1 1 30%;">
                            <i class="bi bi-map-fill me-1"></i> Maps
                        </button>
                        <button class="api-mode-btn" onclick="switchApiMode('geofence')" id="modeBtn-geofence" style="flex:1 1 30%;">
                            <i class="bi bi-bounding-box me-1"></i> Geofence
                        </button>
                        <button class="api-mode-btn" onclick="switchApiMode('tracking')" id="modeBtn-tracking" style="flex:1 1 30%;">
                            <i class="bi bi-broadcast-pin me-1"></i> Tracking
                        </button>
                    </div>

                    <!-- Mode descriptions -->
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
                    <div id="modeDesc-location" style="display:none;">
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#faf5ff;border:1px solid #d8b4fe;">
                            <i class="bi bi-info-circle-fill mt-1" style="color:#7c3aed;"></i>
                            <div>
                                <div class="fw-bold small" style="color:#7c3aed;">Places API</div>
                                <div style="font-size:0.78rem;color:#475569;">
                                    Search, autocomplete, reverse geocode, dan get place details.
                                    Test semua <b>Places endpoints</b> dari AWS Location Service.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modeDesc-maps" style="display:none;">
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#ecfeff;border:1px solid #a5f3fc;">
                            <i class="bi bi-info-circle-fill mt-1" style="color:#0891b2;"></i>
                            <div>
                                <div class="fw-bold small" style="color:#0891b2;">Maps API</div>
                                <div style="font-size:0.78rem;color:#475569;">
                                    Inspect map resources: <b>style descriptor, tiles, glyphs, sprites</b>.
                                    Test Maps rendering endpoints langsung.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modeDesc-geofence" style="display:none;">
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#fff7ed;border:1px solid #fed7aa;">
                            <i class="bi bi-info-circle-fill mt-1" style="color:#ea580c;"></i>
                            <div>
                                <div class="fw-bold small" style="color:#ea580c;">Geofencing API</div>
                                <div style="font-size:0.78rem;color:#475569;">
                                    Create, list, dan evaluate geofences. Draw boundaries di map.
                                    <b>Note:</b> Mungkin butuh IAM/Cognito auth (API Key bisa error).
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="modeDesc-tracking" style="display:none;">
                        <div class="d-flex align-items-start gap-2 p-3 rounded-3" style="background:#fdf2f8;border:1px solid #fbcfe8;">
                            <i class="bi bi-info-circle-fill mt-1" style="color:#db2777;"></i>
                            <div>
                                <div class="fw-bold small" style="color:#db2777;">Tracking API</div>
                                <div style="font-size:0.78rem;color:#475569;">
                                    Update dan retrieve device positions. View position history trails di map.
                                    <b>Note:</b> Mungkin butuh IAM/Cognito auth (API Key bisa error).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Config -->
                <div class="card-glass p-4 mb-4" hidden>
                    <div class="section-title">
                        <i class="bi bi-gear me-1"></i> API Configuration
                        <span class="badge ms-2" style="background:#6b7280;color:#fff;font-size:0.65rem;">readonly</span>
                    </div>
                    <div class="mb-3" id="cfgRouteCalc">
                        <label class="form-label small fw-semibold">Route Calculator Name</label>
                        <input type="text" class="form-control coord-input" id="routeCalc" value="{{ env('AWS_MAP_ROUTE') }}" readonly tabindex="-1">
                    </div>
                    <div class="mb-3" id="cfgPlaceIndex" style="display:none;">
                        <label class="form-label small fw-semibold">Place Index Name</label>
                        <input type="text" class="form-control coord-input" id="placeIndex" value="{{ env('AWS_MAP_PLACE') }}" readonly tabindex="-1">
                    </div>
                    <div class="mb-3" id="cfgMapName" style="display:none;">
                        <label class="form-label small fw-semibold">Map Name</label>
                        <input type="text" class="form-control coord-input" id="mapNameInput" value="{{ env('AWS_MAP_NAME') }}" readonly tabindex="-1">
                    </div>
                    <div class="mb-3" id="cfgGeofenceCollection" style="display:none;">
                        <label class="form-label small fw-semibold">Geofence Collection Name</label>
                        <input type="text" class="form-control coord-input" id="geofenceCollection" value="{{ env('AWS_GEOFENCE_COLLECTION', 'explore.geofence-collection') }}" readonly tabindex="-1">
                    </div>
                    <div class="mb-3" id="cfgTrackerName" style="display:none;">
                        <label class="form-label small fw-semibold">Tracker Name</label>
                        <input type="text" class="form-control coord-input" id="trackerName" value="{{ env('AWS_TRACKER_NAME', 'explore.tracker') }}" readonly tabindex="-1">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Region</label>
                            <input type="text" class="form-control coord-input" id="awsRegion" value="{{ env('AWS_REGION') }}" readonly tabindex="-1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">
                                API Key <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control coord-input" id="awsApiKey" value="" placeholder="Enter your API key..." required style="border-radius:10px 0 0 10px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKey()" style="border-radius:0 10px 10px 0;">
                                    <i class="bi bi-eye" id="apiKeyToggleIcon"></i>
                                </button>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-1">
                                <small class="text-muted" style="font-size:0.7rem;">
                                    <i class="bi bi-shield-lock me-1"></i> Saved in browser only
                                </small>
                                <button class="btn btn-link btn-sm p-0" type="button" onclick="showApiKeyGate(document.getElementById('awsApiKey').value)" style="font-size:0.7rem;text-decoration:none;color:#7c3aed;">
                                    <i class="bi bi-pencil-square me-1"></i>Change
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Options (route + matrix) -->
                <div class="card-glass p-4 mb-4" id="cardTravelMode">
                    <div class="section-title">
                        <i class="bi bi-sliders me-1"></i> Request Options
                        <span class="badge ms-2" id="routeApiVersionBadge" style="background:#7c3aed;color:#fff;font-size:0.7rem;">v2</span>
                    </div>

                    <!-- API Version Toggle (v0 vs v2) -->
                    <div class="mb-3 p-2 rounded" style="background:#f3f4f6;">
                        <label class="form-label small fw-semibold mb-2 d-block">
                            <i class="bi bi-arrow-left-right me-1"></i> API Version
                        </label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="routeApiVersion" id="routeApiV0" value="v0" onchange="switchRouteApiVersion('v0')">
                            <label class="btn btn-sm btn-outline-secondary" for="routeApiV0">
                                <i class="bi bi-clock-history me-1"></i> v0 (Legacy)
                            </label>
                            <input type="radio" class="btn-check" name="routeApiVersion" id="routeApiV2" value="v2" checked onchange="switchRouteApiVersion('v2')">
                            <label class="btn btn-sm btn-outline-primary" for="routeApiV2">
                                <i class="bi bi-stars me-1"></i> v2 (New)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                            v2 mendukung <b>Motorcycle mode</b> 🏍️, alternative routes, toll info, ferry info
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Travel Mode</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="radio" class="btn-check" name="travelMode" id="mCar" value="Car" checked>
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mCar">
                                <i class="bi bi-car-front-fill me-1"></i> Car
                            </label>
                            <input type="radio" class="btn-check" name="travelMode" id="mScooter" value="Scooter">
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mScooter">
                                <i class="bi bi-scooter me-1"></i> Motorcycle <small style="opacity:0.7;">(v2)</small>
                            </label>
                            <input type="radio" class="btn-check" name="travelMode" id="mTruck" value="Truck">
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mTruck">
                                <i class="bi bi-truck me-1"></i> Truck
                            </label>
                            <input type="radio" class="btn-check" name="travelMode" id="mWalk" value="Pedestrian">
                            <label class="btn btn-outline-success rounded-pill flex-grow-1" for="mWalk">
                                <i class="bi bi-person-walking me-1"></i> Walk
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

                <!-- ============ LOCATION CARD ============ -->
                <div class="card-glass p-4 mb-4" id="cardLocation" style="display:none;">
                    <div class="section-title">
                        <i class="bi bi-search me-1"></i> Places API
                        <span class="mode-info-chip chip-location ms-2">Location mode</span>
                        <span class="badge ms-2" id="locApiVersionBadge" style="background:#7c3aed;color:#fff;font-size:0.7rem;">v2</span>
                    </div>

                    <!-- API Version Toggle (v0 vs v2) -->
                    <div class="mb-3 p-2 rounded" style="background:#f3f4f6;">
                        <label class="form-label small fw-semibold mb-2 d-block">
                            <i class="bi bi-arrow-left-right me-1"></i> API Version
                        </label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="locApiVersion" id="locApiV0" value="v0" onchange="switchLocApiVersion('v0')">
                            <label class="btn btn-sm btn-outline-secondary" for="locApiV0">
                                <i class="bi bi-clock-history me-1"></i> v0 (Legacy)
                            </label>
                            <input type="radio" class="btn-check" name="locApiVersion" id="locApiV2" value="v2" checked onchange="switchLocApiVersion('v2')">
                            <label class="btn btn-sm btn-outline-primary" for="locApiV2">
                                <i class="bi bi-stars me-1"></i> v2 (New)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                            v0 = legacy place index • v2 = standalone API dengan POI Title terpisah
                        </small>
                    </div>

                    <!-- Sub-mode selector -->
                    <div class="loc-submode-toggle mb-3">
                        <button class="loc-submode-btn active" onclick="switchLocSubMode('suggestions')" id="locSub-suggestions">
                            <i class="bi bi-lightning-fill d-block mb-1"></i> Suggestions
                        </button>
                        <button class="loc-submode-btn" onclick="switchLocSubMode('search')" id="locSub-search">
                            <i class="bi bi-search d-block mb-1"></i> Search
                        </button>
                        <button class="loc-submode-btn" onclick="switchLocSubMode('reverse')" id="locSub-reverse">
                            <i class="bi bi-pin-map-fill d-block mb-1"></i> Reverse
                        </button>
                        <button class="loc-submode-btn" onclick="switchLocSubMode('getplace')" id="locSub-getplace">
                            <i class="bi bi-building d-block mb-1"></i> Get Place
                        </button>
                    </div>

                    <!-- Sub: Suggestions -->
                    <div id="locPane-suggestions">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Search Text</label>
                            <input type="text" class="form-control coord-input" id="locSuggestText" placeholder="e.g. Monas Jakarta" value="Monas">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">MaxResults</label>
                                <input type="number" class="form-control coord-input" id="locSuggestMax" value="5" min="1" max="15">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Language</label>
                                <select class="form-select coord-input" id="locSuggestLang">
                                    <option value="en" selected>English</option>
                                    <option value="id">Indonesia</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sub: Search Text -->
                    <div id="locPane-search" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Search Text</label>
                            <input type="text" class="form-control coord-input" id="locSearchText" placeholder="e.g. Grand Indonesia" value="Grand Indonesia">
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">MaxResults</label>
                                <input type="number" class="form-control coord-input" id="locSearchMax" value="5" min="1" max="50">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Language</label>
                                <select class="form-select coord-input" id="locSearchLang">
                                    <option value="en" selected>English</option>
                                    <option value="id">Indonesia</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sub: Reverse Geocode -->
                    <div id="locPane-reverse" style="display:none;">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Longitude</label>
                                <input type="number" step="any" class="form-control coord-input" id="locRevLng" value="106.8456" placeholder="lng">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Latitude</label>
                                <input type="number" step="any" class="form-control coord-input" id="locRevLat" value="-6.2088" placeholder="lat">
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">MaxResults</label>
                                <input type="number" class="form-control coord-input" id="locRevMax" value="1" min="1" max="50">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Language</label>
                                <select class="form-select coord-input" id="locRevLang">
                                    <option value="en" selected>English</option>
                                    <option value="id">Indonesia</option>
                                </select>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i> Click map to auto-fill coordinates
                        </small>
                    </div>

                    <!-- Sub: Get Place -->
                    <div id="locPane-getplace" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Place ID</label>
                            <input type="text" class="form-control coord-input" id="locPlaceId" placeholder="Place ID from search results">
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-lightbulb me-1"></i> Tip: Run a Search first, then click a result to auto-fill its PlaceId here.
                        </small>
                    </div>
                </div>

                <!-- Location Results Preview -->
                <div class="card-glass p-4 mb-4" id="cardLocResults" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">
                            <i class="bi bi-list-ul me-1"></i> Results
                            <span class="badge bg-secondary ms-1" id="locResultCount">0</span>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="clearLocResults()">
                            <i class="bi bi-x-lg me-1"></i> Clear
                        </button>
                    </div>
                    <div id="locResultsContainer" style="max-height:250px;overflow-y:auto;"></div>
                </div>

                <!-- Places v0 vs v2 Comparison (Location mode only) -->
                <div class="card-glass p-4 mb-4" id="cardLocComparison" style="display:none;border:2px solid #7c3aed;">
                    <div class="section-title mb-2">
                        <i class="bi bi-arrow-left-right me-1" style="color:#7c3aed;"></i> Places API: v0 vs v2
                        <span class="badge bg-success ms-1" style="font-size:0.65rem;">v2 Available</span>
                    </div>
                    <div class="small text-muted mb-3">Perbandingan utama legacy v0 vs standalone v2 API GrabMaps</div>
                    <div class="table-responsive">
                        <table class="table table-sm small mb-0" style="font-size:0.75rem;">
                            <thead style="background:#f3f4f6;">
                                <tr>
                                    <th style="width:25%;">Aspek</th>
                                    <th class="text-muted" style="width:37.5%;">v0 (Legacy)</th>
                                    <th class="text-success" style="width:37.5%;">v2 (Baru)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><b>Hostname</b></td>
                                    <td><code>places.geo.{region}.amazonaws.com</code></td>
                                    <td><code>places.geo.{region}.amazonaws.com/v2</code></td>
                                </tr>
                                <tr>
                                    <td><b>Resource setup</b></td>
                                    <td>Wajib bikin Place Index</td>
                                    <td>✅ Resource-less</td>
                                </tr>
                                <tr>
                                    <td><b>Path search</b></td>
                                    <td><code>/places/v0/indexes/{idx}/search/text</code></td>
                                    <td><code>/v2/search-text</code></td>
                                </tr>
                                <tr>
                                    <td><b>Path suggest</b></td>
                                    <td><code>/.../search/suggestions</code></td>
                                    <td><code>/v2/suggest</code></td>
                                </tr>
                                <tr>
                                    <td><b>Path reverse</b></td>
                                    <td><code>/.../search/position</code></td>
                                    <td><code>/v2/reverse-geocode</code></td>
                                </tr>
                                <tr>
                                    <td><b>Path get place</b></td>
                                    <td><code>/.../places/{id}</code></td>
                                    <td><code>/v2/place/{id}</code></td>
                                </tr>
                                <tr>
                                    <td><b>Field query (text)</b></td>
                                    <td><code>Text</code></td>
                                    <td><code>QueryText</code></td>
                                </tr>
                                <tr>
                                    <td><b>Field query (pos)</b></td>
                                    <td><code>Position</code></td>
                                    <td><code>QueryPosition</code></td>
                                </tr>
                                <tr>
                                    <td><b>Wrapper hasil</b></td>
                                    <td><code>data.Results[]</code></td>
                                    <td><code>data.ResultItems[]</code></td>
                                </tr>
                                <tr style="background:#fef3c7;">
                                    <td><b>POI Name</b></td>
                                    <td>❌ Tidak ada (cuma <code>Label</code>)</td>
                                    <td>✅ <code>Title</code> terpisah</td>
                                </tr>
                                <tr>
                                    <td><b>Full Address</b></td>
                                    <td><code>Place.Label</code></td>
                                    <td><code>Address.Label</code></td>
                                </tr>
                                <tr>
                                    <td><b>Koordinat</b></td>
                                    <td><code>Place.Geometry.Point</code></td>
                                    <td><code>Position</code> (root)</td>
                                </tr>
                                <tr>
                                    <td><b>Suggest PlaceId</b></td>
                                    <td><code>item.PlaceId</code></td>
                                    <td><code>item.Place.PlaceId</code></td>
                                </tr>
                                <tr>
                                    <td><b>Suggest label</b></td>
                                    <td><code>item.Text</code></td>
                                    <td><code>item.Title</code></td>
                                </tr>
                                <tr>
                                    <td><b>Auth</b></td>
                                    <td><code>?key=...</code></td>
                                    <td><code>?key=...</code> (sama)</td>
                                </tr>
                                <tr>
                                    <td><b>IAM service</b></td>
                                    <td><code>geo:*</code></td>
                                    <td><code>geo-places:*</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 p-2 rounded" style="background:#dcfce7;font-size:0.75rem;">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        <b>Keuntungan v2:</b> POI <code>Title</code> dipisah dari address, resource-less (tanpa place index), response lebih kaya (kategori, contacts, opening hours, dll).
                    </div>
                </div>

                <!-- ============ MAPS INSPECTOR CARD ============ -->
                <div class="card-glass p-4 mb-4" id="cardMaps" style="display:none;">
                    <div class="section-title">
                        <i class="bi bi-map-fill me-1" style="color:#0891b2;"></i> Maps API
                        <span class="mode-info-chip chip-maps ms-2">Maps mode</span>
                        <span class="badge ms-2" id="mapsApiVersionBadge" style="background:#7c3aed;color:#fff;font-size:0.7rem;">v2</span>
                    </div>

                    <!-- API Version Toggle (v0 vs v2) -->
                    <div class="mb-3 p-2 rounded" style="background:#f3f4f6;">
                        <label class="form-label small fw-semibold mb-2 d-block">
                            <i class="bi bi-arrow-left-right me-1"></i> API Version
                        </label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="mapsApiVersion" id="mapsApiV0" value="v0" onchange="switchMapsApiVersion('v0')">
                            <label class="btn btn-sm btn-outline-secondary" for="mapsApiV0">
                                <i class="bi bi-clock-history me-1"></i> v0 (Legacy)
                            </label>
                            <input type="radio" class="btn-check" name="mapsApiVersion" id="mapsApiV2" value="v2" checked onchange="switchMapsApiVersion('v2')">
                            <label class="btn btn-sm btn-outline-primary" for="mapsApiV2">
                                <i class="bi bi-stars me-1"></i> v2 (New)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:0.7rem;">
                            v0 = pakai map resource (<code>explore.map.Grab</code>) • v2 = standalone, langsung pilih Style
                        </small>
                    </div>

                    <div class="maps-submode-toggle mb-3">
                        <button class="maps-submode-btn active" onclick="switchMapsSubMode('style')" id="mapsSub-style">
                            <i class="bi bi-palette-fill d-block mb-1"></i> Style
                        </button>
                        <button class="maps-submode-btn" onclick="switchMapsSubMode('tile')" id="mapsSub-tile">
                            <i class="bi bi-grid-fill d-block mb-1"></i> Tile
                        </button>
                        <button class="maps-submode-btn" onclick="switchMapsSubMode('glyphs')" id="mapsSub-glyphs">
                            <i class="bi bi-fonts d-block mb-1"></i> Glyphs
                        </button>
                        <button class="maps-submode-btn" onclick="switchMapsSubMode('sprites')" id="mapsSub-sprites">
                            <i class="bi bi-image d-block mb-1"></i> Sprites
                        </button>
                    </div>
                    <div id="mapsPane-style">
                        <div class="p-2 rounded-3 small" style="background:#ecfeff;border:1px solid #a5f3fc;">
                            <i class="bi bi-info-circle me-1" style="color:#0891b2;"></i>
                            Fetches the full MapLibre GL style JSON. No additional parameters needed.
                        </div>
                    </div>
                    <div id="mapsPane-tile" style="display:none;">
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Zoom (z)</label>
                                <input type="number" class="form-control coord-input" id="mapsZ" value="12" min="0" max="22">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">X</label>
                                <input type="number" class="form-control coord-input" id="mapsX" value="3248" min="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Y</label>
                                <input type="number" class="form-control coord-input" id="mapsY" value="2050" min="0">
                            </div>
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Returns PBF vector tile. Response info will be shown.</small>
                        <button class="btn btn-sm btn-outline-info rounded-pill mt-2" onclick="fillTileFromMapCenter()">
                            <i class="bi bi-crosshair me-1"></i> Fill from map center
                        </button>
                    </div>
                    <div id="mapsPane-glyphs" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Font Stack</label>
                            <input type="text" class="form-control coord-input" id="mapsFontStack" value="Amazon Ember Regular" placeholder="e.g. Amazon Ember Regular">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Unicode Range</label>
                            <input type="text" class="form-control coord-input" id="mapsGlyphRange" value="0-255" placeholder="e.g. 0-255">
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Returns PBF glyph data for map label rendering.</small>
                    </div>
                    <div id="mapsPane-sprites" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Sprite File</label>
                            <select class="form-select coord-input" id="mapsSpriteFile">
                                <option value="sprites.json">sprites.json</option>
                                <option value="sprites.png">sprites.png</option>
                                <option value="sprites@2x.json">sprites@2x.json</option>
                                <option value="sprites@2x.png">sprites@2x.png</option>
                            </select>
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> JSON = sprite metadata. PNG = sprite atlas image.</small>
                    </div>
                </div>

                <!-- ============ GEOFENCING CARD ============ -->
                <div class="card-glass p-4 mb-4" id="cardGeofence" style="display:none;">
                    <div class="section-title">
                        <i class="bi bi-bounding-box me-1" style="color:#ea580c;"></i> Geofencing API
                        <span class="mode-info-chip chip-geofence ms-2">Geofence mode</span>
                        <span class="badge ms-2" style="background:#6b7280;color:#fff;font-size:0.7rem;">v0 only</span>
                    </div>
                    <div class="p-2 rounded-3 mb-3 small" style="background:#fef3c7;border:1px solid #fde68a;">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                        <b>Note:</b> Geofencing belum punya v2 di AWS Location Service — masih pakai legacy resource-based API. Mungkin butuh IAM/Cognito auth.
                    </div>
                    <div class="geo-submode-toggle mb-3">
                        <button class="geo-submode-btn active" onclick="switchGeoSubMode('put')" id="geoSub-put">
                            <i class="bi bi-plus-circle-fill d-block mb-1"></i> Put
                        </button>
                        <button class="geo-submode-btn" onclick="switchGeoSubMode('get')" id="geoSub-get">
                            <i class="bi bi-eye-fill d-block mb-1"></i> Get
                        </button>
                        <button class="geo-submode-btn" onclick="switchGeoSubMode('list')" id="geoSub-list">
                            <i class="bi bi-list-ul d-block mb-1"></i> List
                        </button>
                        <button class="geo-submode-btn" onclick="switchGeoSubMode('evaluate')" id="geoSub-evaluate">
                            <i class="bi bi-pin-map-fill d-block mb-1"></i> Evaluate
                        </button>
                    </div>
                    <div id="geoPane-put">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Geofence ID</label>
                            <input type="text" class="form-control coord-input" id="geoPutId" value="test-geofence-01">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Geometry Type</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="geoType" id="geoTypeCircle" value="Circle" checked>
                                <label class="btn btn-outline-warning rounded-pill flex-grow-1" for="geoTypeCircle"><i class="bi bi-circle me-1"></i> Circle</label>
                                <input type="radio" class="btn-check" name="geoType" id="geoTypePolygon" value="Polygon">
                                <label class="btn btn-outline-warning rounded-pill flex-grow-1" for="geoTypePolygon"><i class="bi bi-pentagon me-1"></i> Polygon</label>
                            </div>
                        </div>
                        <div id="geoCircleInputs">
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Center Lng</label>
                                    <input type="number" step="any" class="form-control coord-input" id="geoCircleLng" value="106.8456">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Center Lat</label>
                                    <input type="number" step="any" class="form-control coord-input" id="geoCircleLat" value="-6.2088">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Radius (m)</label>
                                    <input type="number" class="form-control coord-input" id="geoCircleRadius" value="500" min="1">
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="fillGeoCircleFromMap()">
                                <i class="bi bi-crosshair me-1"></i> Use map center
                            </button>
                        </div>
                        <div id="geoPolygonInputs" style="display:none;">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Polygon Coordinates (JSON)</label>
                                <textarea class="form-control coord-input" id="geoPolygonCoords" rows="4" placeholder='[[lng1,lat1],[lng2,lat2],...]'>[[106.84,-6.20],[106.85,-6.20],[106.85,-6.21],[106.84,-6.21],[106.84,-6.20]]</textarea>
                            </div>
                            <button class="btn btn-sm btn-outline-warning rounded-pill" onclick="drawGeofenceOnMap(event)">
                                <i class="bi bi-pencil me-1"></i> Draw on map
                            </button>
                            <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i> First and last coordinates must be identical (closed ring).</small>
                        </div>
                    </div>
                    <div id="geoPane-get" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Geofence ID</label>
                            <input type="text" class="form-control coord-input" id="geoGetId" value="test-geofence-01">
                        </div>
                    </div>
                    <div id="geoPane-list" style="display:none;">
                        <div class="p-2 rounded-3 small" style="background:#fff7ed;border:1px solid #fed7aa;">
                            <i class="bi bi-info-circle me-1" style="color:#ea580c;"></i>
                            Lists all geofences in the configured collection. No additional parameters needed.
                        </div>
                    </div>
                    <div id="geoPane-evaluate" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Device ID</label>
                            <input type="text" class="form-control coord-input" id="geoEvalDeviceId" value="test-device-01">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Longitude</label>
                                <input type="number" step="any" class="form-control coord-input" id="geoEvalLng" value="106.8456">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Latitude</label>
                                <input type="number" step="any" class="form-control coord-input" id="geoEvalLat" value="-6.2088">
                            </div>
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Click map to set evaluation position.</small>
                    </div>
                </div>

                <!-- Geofence Results -->
                <div class="card-glass p-4 mb-4" id="cardGeoResults" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">
                            <i class="bi bi-list-ul me-1"></i> Geofences
                            <span class="badge bg-secondary ms-1" id="geoResultCount">0</span>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="clearGeoResults()"><i class="bi bi-x-lg me-1"></i> Clear</button>
                    </div>
                    <div id="geoResultsContainer" style="max-height:250px;overflow-y:auto;"></div>
                </div>

                <!-- ============ TRACKING CARD ============ -->
                <div class="card-glass p-4 mb-4" id="cardTracking" style="display:none;">
                    <div class="section-title">
                        <i class="bi bi-broadcast-pin me-1" style="color:#db2777;"></i> Tracking API
                        <span class="mode-info-chip chip-tracking ms-2">Tracking mode</span>
                        <span class="badge ms-2" style="background:#6b7280;color:#fff;font-size:0.7rem;">v0 only</span>
                    </div>
                    <div class="p-2 rounded-3 mb-3 small" style="background:#fce7f3;border:1px solid #fbcfe8;">
                        <i class="bi bi-exclamation-triangle-fill me-1" style="color:#db2777;"></i>
                        <b>Note:</b> Tracking belum punya v2 di AWS Location Service — masih pakai legacy resource-based API. Mungkin butuh IAM/Cognito auth.
                    </div>
                    <div class="trk-submode-toggle mb-3">
                        <button class="trk-submode-btn active" onclick="switchTrkSubMode('update')" id="trkSub-update">
                            <i class="bi bi-upload d-block mb-1"></i> Update
                        </button>
                        <button class="trk-submode-btn" onclick="switchTrkSubMode('get')" id="trkSub-get">
                            <i class="bi bi-crosshair d-block mb-1"></i> Get
                        </button>
                        <button class="trk-submode-btn" onclick="switchTrkSubMode('history')" id="trkSub-history">
                            <i class="bi bi-clock-history d-block mb-1"></i> History
                        </button>
                        <button class="trk-submode-btn" onclick="switchTrkSubMode('list')" id="trkSub-list">
                            <i class="bi bi-list-ul d-block mb-1"></i> List
                        </button>
                    </div>
                    <div id="trkPane-update">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Device ID</label>
                            <input type="text" class="form-control coord-input" id="trkUpdateDeviceId" value="test-device-01">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Longitude</label>
                                <input type="number" step="any" class="form-control coord-input" id="trkUpdateLng" value="106.8456">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Latitude</label>
                                <input type="number" step="any" class="form-control coord-input" id="trkUpdateLat" value="-6.2088">
                            </div>
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Click map to set device position. Timestamp auto-generated.</small>
                    </div>
                    <div id="trkPane-get" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Device IDs (comma-separated)</label>
                            <input type="text" class="form-control coord-input" id="trkGetDeviceIds" value="test-device-01" placeholder="device1, device2">
                        </div>
                    </div>
                    <div id="trkPane-history" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Device ID</label>
                            <input type="text" class="form-control coord-input" id="trkHistDeviceId" value="test-device-01">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Start Time (optional)</label>
                                <input type="datetime-local" class="form-control coord-input" id="trkHistStart">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">End Time (optional)</label>
                                <input type="datetime-local" class="form-control coord-input" id="trkHistEnd">
                            </div>
                        </div>
                    </div>
                    <div id="trkPane-list" style="display:none;">
                        <div class="p-2 rounded-3 small" style="background:#fdf2f8;border:1px solid #fbcfe8;">
                            <i class="bi bi-info-circle me-1" style="color:#db2777;"></i>
                            Lists all device positions for the configured tracker. No additional parameters needed.
                        </div>
                    </div>
                </div>

                <!-- Tracking Results -->
                <div class="card-glass p-4 mb-4" id="cardTrkResults" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title mb-0">
                            <i class="bi bi-list-ul me-1"></i> Devices
                            <span class="badge bg-secondary ms-1" id="trkResultCount">0</span>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="clearTrkResults()"><i class="bi bi-x-lg me-1"></i> Clear</button>
                    </div>
                    <div id="trkResultsContainer" style="max-height:250px;overflow-y:auto;"></div>
                </div>

                <!-- Send Button -->
                <button class="btn btn-grab w-100 py-3 mb-4" onclick="sendRequest()" id="btnSend">
                    <i class="bi bi-send-fill me-2"></i> Send <span id="btnModeLabel">Route</span> Request
                </button>
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
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="copyToClipboard('requestLog', event)">
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
                        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="copyToClipboard('responseLog', event)">
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

    <!-- ============ API KEY GATE MODAL ============ -->
    <div class="modal fade" id="apiKeyGateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%);color:#fff;border:none;padding:22px 24px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill" style="font-size:1.4rem;"></i>
                        <div>
                            <h5 class="modal-title mb-0" style="font-weight:700;">API Key Required</h5>
                            <small style="opacity:0.85;font-size:0.75rem;">Enter your AWS Location Service API key to continue</small>
                        </div>
                    </div>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <div class="p-3 rounded-3 mb-3" style="background:#f5f3ff;border:1px solid #ddd6fe;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill" style="color:#7c3aed;font-size:1rem;margin-top:2px;"></i>
                            <div style="font-size:0.8rem;color:#5b21b6;line-height:1.5;">
                                Tester ini butuh <b>AWS API Key</b> untuk render map dan call API.
                                Key kamu akan disimpan di browser (<code>localStorage</code>) dan tidak dikirim ke server kami.
                            </div>
                        </div>
                    </div>

                    <label class="form-label small fw-semibold">
                        AWS Location Service API Key <span class="text-danger">*</span>
                    </label>
                    <div class="input-group mb-2">
                        <span class="input-group-text" style="background:#f3f4f6;border-radius:10px 0 0 10px;">
                            <i class="bi bi-key-fill" style="color:#7c3aed;"></i>
                        </span>
                        <input type="password" class="form-control" id="gateApiKey" placeholder="v1.public.xxxxxxxxxxxxxxxxxxx" autocomplete="off" style="border-radius:0;">
                        <button class="btn btn-outline-secondary" type="button" id="gateApiKeyToggle" style="border-radius:0 10px 10px 0;">
                            <i class="bi bi-eye" id="gateApiKeyToggleIcon"></i>
                        </button>
                    </div>
                    <div id="gateError" class="text-danger small mb-2" style="display:none;font-size:0.78rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="gateErrorMsg">API Key tidak boleh kosong</span>
                    </div>

                    <details class="mt-3">
                        <summary style="cursor:pointer;color:#7c3aed;font-weight:600;font-size:0.8rem;">
                            <i class="bi bi-question-circle me-1"></i> Bagaimana cara dapat API Key?
                        </summary>
                        <div class="mt-2 p-3 rounded-3 small" style="background:#f9fafb;border:1px solid #e5e7eb;color:#4b5563;font-size:0.75rem;line-height:1.6;">
                            <ol class="mb-0 ps-3">
                                <li>Buka <a href="https://console.aws.amazon.com/location/" target="_blank" style="color:#7c3aed;">AWS Console → Location Service</a></li>
                                <li>Klik <b>API keys → Create API key</b></li>
                                <li>Pilih region <b>ap-southeast-1</b> (Singapore)</li>
                                <li>Centang resources yang dibutuhkan: Maps, Places, Routes</li>
                                <li>Copy API key value (format: <code>v1.public.xxx...</code>)</li>
                                <li>Paste di field di atas</li>
                            </ol>
                        </div>
                    </details>
                </div>
                <div class="modal-footer" style="border:none;padding:0 24px 24px;">
                    <button type="button" class="btn rounded-pill px-4 w-100 py-2" id="gateContinueBtn" style="background:#7c3aed;color:#fff;font-weight:600;">
                        <i class="bi bi-arrow-right-circle-fill me-1"></i> Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- API Reference Modal -->
    <div class="modal fade ref-modal" id="refModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-book-fill fs-4"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">AWS Location Service — Grab Provider</h5>
                            <small style="opacity:0.8;">Complete API Reference & Coverage</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Coverage -->
                    <div class="ref-card">
                        <div class="ref-card-title">
                            <i class="bi bi-globe-asia-australia text-success"></i> Coverage & Region
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 rounded-3 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                    <div style="font-size:1.5rem;font-weight:800;color:#166534;">8</div>
                                    <div class="small text-muted">Countries</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded-3 text-center" style="background:#f0f9ff;border:1px solid #bae6fd;">
                                    <div style="font-size:1rem;font-weight:700;color:#075985;">ap-southeast-1</div>
                                    <div class="small text-muted">AWS Region (Singapore)</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge rounded-pill bg-light text-dark border">Indonesia</span>
                            <span class="badge rounded-pill bg-light text-dark border">Singapore</span>
                            <span class="badge rounded-pill bg-light text-dark border">Malaysia</span>
                            <span class="badge rounded-pill bg-light text-dark border">Thailand</span>
                            <span class="badge rounded-pill bg-light text-dark border">Vietnam</span>
                            <span class="badge rounded-pill bg-light text-dark border">Philippines</span>
                            <span class="badge rounded-pill bg-light text-dark border">Cambodia</span>
                            <span class="badge rounded-pill bg-light text-dark border">Myanmar</span>
                        </div>
                    </div>

                    <!-- Maps -->
                    <div class="ref-card">
                        <div class="ref-card-title">
                            <i class="bi bi-map-fill text-primary"></i> Maps API
                        </div>
                        <div class="mb-2 small text-muted">2 map styles available — vector only, no satellite imagery</div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-get">GET</span>
                            <code>/maps/v0/maps/{mapName}/style-descriptor</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-get">GET</span>
                            <code>/maps/v0/maps/{mapName}/tiles/{z}/{x}/{y}</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-get">GET</span>
                            <code>/maps/v0/maps/{mapName}/glyphs/{fontstack}/{range}</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-get">GET</span>
                            <code>/maps/v0/maps/{mapName}/sprites/{fileName}</code>
                        </div>
                        <div class="mt-3">
                            <div class="small fw-semibold mb-2">Available Styles:</div>
                            <div class="d-flex gap-2">
                                <div class="flex-grow-1 p-2 rounded text-center border" style="background:#fff;">
                                    <i class="bi bi-sun d-block mb-1"></i>
                                    <div class="small fw-bold">VectorGrabStandardLight</div>
                                </div>
                                <div class="flex-grow-1 p-2 rounded text-center border" style="background:#1a1a2e;color:white;">
                                    <i class="bi bi-moon-stars d-block mb-1"></i>
                                    <div class="small fw-bold">VectorGrabStandardDark</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Places -->
                    <div class="ref-card">
                        <div class="ref-card-title">
                            <i class="bi bi-geo-alt-fill" style="color:#7c3aed;"></i> Places API <span class="badge bg-success ms-1" style="font-size:0.6rem;">v2</span>
                        </div>
                        <div class="mb-2 small text-muted">Search, autocomplete, reverse geocode & get place details (Standalone v2)</div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/v2/suggest</code>
                            <span class="ref-badge ref-badge-yes ms-auto">Supported</span>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/v2/search-text</code>
                            <span class="ref-badge ref-badge-yes ms-auto">Supported</span>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/v2/reverse-geocode</code>
                            <span class="ref-badge ref-badge-yes ms-auto">Supported</span>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-get">GET</span>
                            <code>/v2/place/{placeId}</code>
                            <span class="ref-badge ref-badge-yes ms-auto">Supported</span>
                        </div>
                        <div class="mt-2 p-2 rounded" style="background:#dcfce7;font-size:0.75rem;">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            <b>v2 Improvement:</b> POI <code>Title</code> dipisah dari <code>Address.Label</code>, resource-less (tidak perlu place index), response lebih kaya.
                        </div>
                    </div>

                    <!-- v0 vs v2 Comparison -->
                    <div class="ref-card" style="border:2px solid #7c3aed;">
                        <div class="ref-card-title">
                            <i class="bi bi-arrow-left-right" style="color:#7c3aed;"></i> Places v0 vs v2
                        </div>
                        <div class="mb-2 small text-muted">Perbandingan utama antara legacy v0 dan v2 standalone API</div>
                        <div class="table-responsive">
                            <table class="table table-sm small mb-0" style="font-size:0.72rem;">
                                <thead style="background:#f3f4f6;">
                                    <tr>
                                        <th>Aspek</th>
                                        <th class="text-muted">v0 (Legacy)</th>
                                        <th class="text-success">v2 (Baru)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><b>Hostname</b></td>
                                        <td><code>places.geo.{region}.<br>amazonaws.com</code></td>
                                        <td><code>places.geo.{region}.<br>amazonaws.com/v2</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Resource setup</b></td>
                                        <td>Wajib bikin Place Index</td>
                                        <td>Resource-less</td>
                                    </tr>
                                    <tr>
                                        <td><b>Path search</b></td>
                                        <td><code>/places/v0/indexes/{idx}/search/text</code></td>
                                        <td><code>/v2/search-text</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Path suggest</b></td>
                                        <td><code>/.../search/suggestions</code></td>
                                        <td><code>/v2/suggest</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Path reverse</b></td>
                                        <td><code>/.../search/position</code></td>
                                        <td><code>/v2/reverse-geocode</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Path get place</b></td>
                                        <td><code>/.../places/{id}</code></td>
                                        <td><code>/v2/place/{id}</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Field query (text)</b></td>
                                        <td><code>Text</code></td>
                                        <td><code>QueryText</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Field query (pos)</b></td>
                                        <td><code>Position</code></td>
                                        <td><code>QueryPosition</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Wrapper hasil</b></td>
                                        <td><code>data.Results[]</code></td>
                                        <td><code>data.ResultItems[]</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>POI Name</b></td>
                                        <td>❌ Tidak ada (cuma <code>Label</code>)</td>
                                        <td>✅ <code>Title</code> terpisah</td>
                                    </tr>
                                    <tr>
                                        <td><b>Full Address</b></td>
                                        <td><code>Place.Label</code></td>
                                        <td><code>Address.Label</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Koordinat</b></td>
                                        <td><code>Place.Geometry.Point</code></td>
                                        <td><code>Position</code> (root)</td>
                                    </tr>
                                    <tr>
                                        <td><b>Suggest PlaceId</b></td>
                                        <td><code>item.PlaceId</code></td>
                                        <td><code>item.Place.PlaceId</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Suggest label</b></td>
                                        <td><code>item.Text</code></td>
                                        <td><code>item.Title</code></td>
                                    </tr>
                                    <tr>
                                        <td><b>Auth</b></td>
                                        <td><code>?key=...</code></td>
                                        <td><code>?key=...</code> (sama)</td>
                                    </tr>
                                    <tr>
                                        <td><b>IAM service</b></td>
                                        <td><code>geo:*</code></td>
                                        <td><code>geo-places:*</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Routes -->
                    <div class="ref-card">
                        <div class="ref-card-title">
                            <i class="bi bi-sign-turn-right-fill text-primary"></i> Routes API
                        </div>
                        <div class="mb-2 small text-muted">Calculate route & route matrix — no distance limit!</div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/routes/v0/calculators/{calc}/calculate/route</code>
                            <span class="ref-badge ref-badge-yes ms-auto">Supported</span>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/routes/v0/calculators/{calc}/calculate/route-matrix</code>
                            <span class="ref-badge ref-badge-yes ms-auto">Max 350 pos</span>
                        </div>

                        <div class="mt-3">
                            <div class="small fw-semibold mb-2">Travel Modes:</div>
                            <table class="table table-sm table-bordered small mb-0" style="font-size:0.78rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mode</th>
                                        <th class="text-center">Grab</th>
                                        <th class="text-center">Esri</th>
                                        <th class="text-center">HERE</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="bi bi-car-front-fill me-1"></i> Car</td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td>8 negara SEA</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-scooter me-1"></i> Motorcycle</td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                        <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                        <td><span class="ref-badge ref-badge-exclusive">Grab Exclusive</span></td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-bicycle me-1"></i> Bicycle</td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                        <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                        <td><span class="ref-badge ref-badge-exclusive">Grab Exclusive</span> 7 kota</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-person-walking me-1"></i> Walking</td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td><span class="ref-badge ref-badge-limited">7 kota</span></td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-truck me-1"></i> Truck</td>
                                        <td class="text-center"><i class="bi bi-x-circle text-danger"></i></td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                        <td>Tidak didukung Grab</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <div class="small fw-semibold mb-2">Walking & Bicycle Coverage (7 cities):</div>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Jakarta</span>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Singapore</span>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Manila</span>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Klang Valley</span>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Bangkok</span>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Ho Chi Minh City</span>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:0.7rem;">Hanoi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Grab Advantages -->
                    <div class="ref-card" style="border-color:#bbf7d0;background:#f0fdf4;">
                        <div class="ref-card-title">
                            <i class="bi bi-trophy-fill text-warning"></i> Keunggulan Grab vs Esri/HERE
                        </div>
                        <div class="small">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-check-lg text-success mt-1"></i>
                                <span><b>No distance limit</b> pada CalculateRoute (Esri terbatas 400km)</span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-check-lg text-success mt-1"></i>
                                <span><b>350 positions</b> di Route Matrix (Esri hanya 10)</span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-check-lg text-success mt-1"></i>
                                <span><b>Motorcycle routing</b> — hanya Grab yang support</span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-check-lg text-success mt-1"></i>
                                <span><b>Bicycle routing</b> — hanya Grab yang support (7 kota)</span>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-check-lg text-success mt-1"></i>
                                <span><b>SEA data terbaik</b> — 50M+ addresses & POIs di 8 negara</span>
                            </div>
                        </div>
                    </div>

                    <!-- Limitations -->
                    <div class="ref-card" style="border-color:#fecaca;background:#fef2f2;">
                        <div class="ref-card-title">
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i> Limitasi Grab
                        </div>
                        <div class="small">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-x-lg text-danger mt-1"></i>
                                <span><b>SEA only</b> — hanya 8 negara Asia Tenggara (Esri/HERE global)</span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-x-lg text-danger mt-1"></i>
                                <span><b>ap-southeast-1 only</b> — hanya 1 AWS region</span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-x-lg text-danger mt-1"></i>
                                <span><b>2 map styles</b> saja (Light & Dark) — no satellite/imagery</span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-x-lg text-danger mt-1"></i>
                                <span><b>No Truck routing</b></span>
                            </div>
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-x-lg text-danger mt-1"></i>
                                <span><b>Walking & Bicycle terbatas 7 kota</b> (Esri/HERE global)</span>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-x-lg text-danger mt-1"></i>
                                <span><b>API v1 only</b> — v2 belum fully supported untuk Grab</span>
                            </div>
                        </div>
                    </div>

                    <!-- Geofencing API -->
                    <div class="ref-card">
                        <div class="ref-card-title">
                            <i class="bi bi-bounding-box" style="color:#ea580c;"></i> Geofencing API
                            <span class="ref-badge ref-badge-auth ms-auto">IAM/Cognito Required</span>
                        </div>
                        <div class="mb-2 small text-muted">Manage geofences dan evaluate positions. Provider-independent (AWS-native).</div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-put">PUT</span>
                            <code>/geofencing/v0/collections/{collection}/geofences/{id}</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-get">GET</span>
                            <code>/geofencing/v0/collections/{collection}/geofences/{id}</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/geofencing/v0/collections/{collection}/list-geofences</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/geofencing/v0/collections/{collection}/positions</code>
                        </div>
                        <div class="mt-2 p-2 rounded" style="background:#fce7f3;font-size:0.75rem;">
                            <i class="bi bi-exclamation-triangle-fill me-1" style="color:#db2777;"></i>
                            <b>Auth:</b> Geofencing APIs require IAM atau Cognito authentication. API Key auth akan menampilkan error (berguna untuk debugging).
                        </div>
                    </div>

                    <!-- Tracking API -->
                    <div class="ref-card">
                        <div class="ref-card-title">
                            <i class="bi bi-broadcast-pin" style="color:#db2777;"></i> Tracking API
                            <span class="ref-badge ref-badge-auth ms-auto">IAM/Cognito Required</span>
                        </div>
                        <div class="mb-2 small text-muted">Track device positions dan retrieve history. Provider-independent (AWS-native).</div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/tracking/v0/trackers/{tracker}/update-positions</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/tracking/v0/trackers/{tracker}/get-positions</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/tracking/v0/trackers/{tracker}/devices/{deviceId}/list-positions</code>
                        </div>
                        <div class="ref-endpoint">
                            <span class="ref-method ref-method-post">POST</span>
                            <code>/tracking/v0/trackers/{tracker}/list-positions</code>
                        </div>
                        <div class="mt-2 p-2 rounded" style="background:#fce7f3;font-size:0.75rem;">
                            <i class="bi bi-exclamation-triangle-fill me-1" style="color:#db2777;"></i>
                            <b>Auth:</b> Tracking APIs require IAM atau Cognito authentication. API Key auth akan menampilkan error (berguna untuk debugging).
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top p-3">
                    <small class="text-muted flex-grow-1">
                        <i class="bi bi-info-circle me-1"></i>
                        Source: <a href="https://docs.aws.amazon.com/location/previous/developerguide/grab.html" target="_blank" class="text-decoration-none">AWS Docs — GrabMaps</a>
                    </small>
                    <button type="button" class="btn btn-success rounded-pill px-4" data-bs-dismiss="modal">Got it!</button>
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
        let locationMarkers = [];
        let clickCount = 0;
        let requestHistory = [];
        let currentApiMode = 'route';
        let currentLocSubMode = 'suggestions';
        let currentLocApiVersion = 'v2';
        let currentRouteApiVersion = 'v2';
        let currentMapsApiVersion = 'v2';

        function switchMapsApiVersion(ver) {
            currentMapsApiVersion = ver;
            const badge = document.getElementById('mapsApiVersionBadge');
            if (badge) {
                badge.textContent = ver;
                badge.style.background = ver === 'v2' ? '#7c3aed' : '#6b7280';
            }
        }

        function switchLocApiVersion(ver) {
            currentLocApiVersion = ver;
            const badge = document.getElementById('locApiVersionBadge');
            if (badge) {
                badge.textContent = ver;
                badge.style.background = ver === 'v2' ? '#7c3aed' : '#6b7280';
            }
        }

        function switchRouteApiVersion(ver) {
            currentRouteApiVersion = ver;
            const badge = document.getElementById('routeApiVersionBadge');
            if (badge) {
                badge.textContent = ver;
                badge.style.background = ver === 'v2' ? '#7c3aed' : '#6b7280';
            }
        }

        // Map UI travel mode to AWS API value per version
        function mapTravelMode(uiMode, ver) {
            if (ver === 'v0') {
                // v0 supports: Car, Truck, Walking (no Scooter/Pedestrian)
                if (uiMode === 'Scooter') return 'Car'; // fallback
                if (uiMode === 'Pedestrian') return 'Walking';
                return uiMode;
            }
            // v2 uses: Car, Truck, Pedestrian, Scooter
            return uiMode;
        }
        let currentMapsSubMode = 'style';
        let currentGeoSubMode = 'put';
        let currentTrkSubMode = 'update';
        let waypointCounter = 0;
        let waypointMarkers = [];
        let matrixDestCounter = 1;
        let matrixDestMarkers = [];
        let matrixResultLayers = [];
        let matrixResultMarkers = [];
        let geofenceMarkers = [];
        let geofenceLayers = [];
        let trackingMarkers = [];
        let trackingLayers = [];
        let geoDrawMode = false;
        let geoDrawPoints = [];
        let lastGeoResults = [];
        let pickFromMapTarget = null; // 'dep' | 'dest' | 'waypoint' | 'matrixDest'


        /* =========================================
           API MODE TOGGLE
           ========================================= */
        function switchApiMode(mode) {
            currentApiMode = mode;
            clearPickFromMapMode();

            const allModes = ['route', 'matrix', 'location', 'maps', 'geofence', 'tracking'];
            allModes.forEach(m => {
                document.getElementById(`modeBtn-${m}`).classList.toggle('active', m === mode);
                document.getElementById(`modeDesc-${m}`).style.display = m === mode ? 'block' : 'none';
            });

            const isRoute = mode === 'route';
            const isMatrix = mode === 'matrix';
            const isLocation = mode === 'location';
            const isMaps = mode === 'maps';
            const isGeofence = mode === 'geofence';
            const isTracking = mode === 'tracking';
            const isRouteOrMatrix = isRoute || isMatrix;

            // Cards
            document.getElementById('cardRouteMatrix').style.display = isRouteOrMatrix ? 'block' : 'none';
            document.getElementById('cardDeparture').style.display = isRouteOrMatrix ? 'block' : 'none';
            document.getElementById('waypointsCard').style.display = isRoute ? 'block' : 'none';
            document.getElementById('cardDestination').style.display = isRoute ? 'block' : 'none';
            document.getElementById('cardMatrixDests').style.display = isMatrix ? 'block' : 'none';
            document.getElementById('cardTravelMode').style.display = isRouteOrMatrix ? 'block' : 'none';
            document.getElementById('routeOnlyOpts').style.display = isRoute ? 'block' : 'none';
            document.getElementById('cardLocation').style.display = isLocation ? 'block' : 'none';
            document.getElementById('cardMaps').style.display = isMaps ? 'block' : 'none';
            document.getElementById('cardGeofence').style.display = isGeofence ? 'block' : 'none';
            document.getElementById('cardTracking').style.display = isTracking ? 'block' : 'none';

            // Config fields
            document.getElementById('cfgRouteCalc').style.display = isRouteOrMatrix ? 'block' : 'none';
            document.getElementById('cfgPlaceIndex').style.display = isLocation ? 'block' : 'none';
            document.getElementById('cfgMapName').style.display = isMaps ? 'block' : 'none';
            document.getElementById('cfgGeofenceCollection').style.display = isGeofence ? 'block' : 'none';
            document.getElementById('cfgTrackerName').style.display = isTracking ? 'block' : 'none';

            // Map hints
            document.getElementById('mapHintRoute').style.display = isRoute ? 'block' : 'none';
            document.getElementById('mapHintMatrix').style.display = isMatrix ? 'block' : 'none';
            document.getElementById('mapHintLocation').style.display = isLocation ? 'block' : 'none';
            document.getElementById('mapHintMaps').style.display = isMaps ? 'block' : 'none';
            document.getElementById('mapHintGeofence').style.display = isGeofence ? 'block' : 'none';
            document.getElementById('mapHintTracking').style.display = isTracking ? 'block' : 'none';

            // Result panels
            if (!isLocation) document.getElementById('cardLocResults').style.display = 'none';
            if (!isGeofence) document.getElementById('cardGeoResults').style.display = 'none';
            if (!isTracking) document.getElementById('cardTrkResults').style.display = 'none';

            // Places v0 vs v2 comparison card (only in Location mode)
            document.getElementById('cardLocComparison').style.display = isLocation ? 'block' : 'none';

            // Button label
            const labels = {
                route: 'Route',
                matrix: 'Route Matrix',
                location: 'Location',
                maps: 'Maps',
                geofence: 'Geofence',
                tracking: 'Tracking'
            };
            document.getElementById('btnModeLabel').textContent = labels[mode];

            // Map cleanup: clear all visualizations and markers from previous mode
            clearAllMapLayers();

            // Marker management: show dep/dest markers only for route/matrix
            if (isRouteOrMatrix) {
                clickCount = 0;
                updateMapMarkers();
            } else {
                if (depMarker) {
                    depMarker.remove();
                    depMarker = null;
                }
                if (destMarker) {
                    destMarker.remove();
                    destMarker = null;
                }
                waypointMarkers.forEach(m => m.remove());
                waypointMarkers = [];
                clearMatrixDestMarkers();
            }
        }

        function switchLocSubMode(sub) {
            currentLocSubMode = sub;
            ['suggestions', 'search', 'reverse', 'getplace'].forEach(s => {
                document.getElementById(`locSub-${s}`).classList.toggle('active', s === sub);
                document.getElementById(`locPane-${s}`).style.display = s === sub ? 'block' : 'none';
            });
        }

        function switchMapsSubMode(sub) {
            currentMapsSubMode = sub;
            ['style', 'tile', 'glyphs', 'sprites'].forEach(s => {
                document.getElementById(`mapsSub-${s}`).classList.toggle('active', s === sub);
                document.getElementById(`mapsPane-${s}`).style.display = s === sub ? 'block' : 'none';
            });
        }

        function switchGeoSubMode(sub) {
            currentGeoSubMode = sub;
            ['put', 'get', 'list', 'evaluate'].forEach(s => {
                document.getElementById(`geoSub-${s}`).classList.toggle('active', s === sub);
                document.getElementById(`geoPane-${s}`).style.display = s === sub ? 'block' : 'none';
            });
        }

        function switchTrkSubMode(sub) {
            currentTrkSubMode = sub;
            ['update', 'get', 'history', 'list'].forEach(s => {
                document.getElementById(`trkSub-${s}`).classList.toggle('active', s === sub);
                document.getElementById(`trkPane-${s}`).style.display = s === sub ? 'block' : 'none';
            });
        }


        /* =========================================
           PICK FROM MAP
           ========================================= */
        function setPickFromMapTarget(target) {
            pickFromMapTarget = target;
            const msg = document.getElementById('mapRouteStatus');
            const labels = {
                dep: 'Departure',
                dest: 'Destination',
                waypoint: 'Waypoint',
                matrixDest: 'Matrix Destination'
            };
            msg.innerHTML = `<div class="route-status-bar loading d-flex align-items-center justify-content-between"><span><i class="bi bi-crosshair me-1"></i> Klik peta untuk set <b>${labels[target]}</b></span><button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="clearPickFromMapMode()">Batal</button></div>`;
            if (miniMap) miniMap.getCanvas().style.cursor = 'crosshair';
        }

        function clearPickFromMapMode() {
            pickFromMapTarget = null;
            document.getElementById('mapRouteStatus').innerHTML = '';
            if (miniMap) miniMap.getCanvas().style.cursor = '';
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
                        <label class="form-label small fw-semibold">Longitude</label>
                        <input type="number" step="any" class="form-control coord-input" placeholder="lng" id="wpLng-${id}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Latitude</label>
                        <input type="number" step="any" class="form-control coord-input" placeholder="lat" id="wpLat-${id}">
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        function removeWaypoint(id) {
            const el = document.getElementById(`wp-${id}`);
            if (el) el.remove();
            if (document.querySelectorAll('#waypointsContainer .waypoint-item').length === 0) {
                document.getElementById('waypointEmpty').style.display = 'block';
            }
        }

        function getWaypoints() {
            const waypoints = [];
            document.querySelectorAll('#waypointsContainer .waypoint-item').forEach(item => {
                const id = item.id.replace('wp-', '');
                const lng = parseFloat(document.getElementById(`wpLng-${id}`).value);
                const lat = parseFloat(document.getElementById(`wpLat-${id}`).value);
                if (!isNaN(lng) && !isNaN(lat)) waypoints.push([lng, lat]);
            });
            return waypoints;
        }


        /* =========================================
           MATRIX DESTINATIONS MANAGEMENT
           ========================================= */
        function addMatrixDest(lng, lat) {
            matrixDestCounter++;
            const id = matrixDestCounter;
            const container = document.getElementById('matrixDestsContainer');
            const div = document.createElement('div');
            div.className = 'matrix-dest-item';
            div.dataset.id = id;
            div.innerHTML = `<div class="d-flex justify-content-between align-items-center mb-1"><span class="fw-semibold small text-danger"><i class="bi bi-geo me-1"></i> Dest ${id}</span><button class="btn btn-sm btn-outline-danger rounded-pill py-0 px-2" onclick="removeMatrixDest(${id})" style="font-size:0.7rem;"><i class="bi bi-x-lg"></i></button></div><div class="row g-2"><div class="col-6"><label class="form-label small fw-semibold">Longitude</label><input type="number" step="any" class="form-control coord-input" value="${lng || ''}" placeholder="lng"></div><div class="col-6"><label class="form-label small fw-semibold">Latitude</label><input type="number" step="any" class="form-control coord-input" value="${lat || ''}" placeholder="lat"></div></div>`;
            container.appendChild(div);
            updateMatrixDestCount();
            updateMapMarkers();
        }

        function removeMatrixDest(id) {
            const el = document.querySelector(`#matrixDestsContainer .matrix-dest-item[data-id="${id}"]`);
            if (el) el.remove();
            updateMatrixDestCount();
            updateMapMarkers();
        }

        function getMatrixDests() {
            const dests = [];
            document.querySelectorAll('#matrixDestsContainer .matrix-dest-item').forEach(item => {
                const inputs = item.querySelectorAll('input');
                const lng = parseFloat(inputs[0].value);
                const lat = parseFloat(inputs[1].value);
                if (!isNaN(lng) && !isNaN(lat)) dests.push([lng, lat]);
            });
            return dests;
        }

        function updateMatrixDestCount() {
            const count = document.querySelectorAll('#matrixDestsContainer .matrix-dest-item').length;
            document.getElementById('matrixDestCount').textContent = count;
        }


        /* =========================================
           MINI MAP
           ========================================= */
        function initMiniMap() {
            // Use Laravel proxy v2 endpoint (consistent with /docs/aws-api docs and main app).
            // Proxy handles API key injection server-side — works regardless of user's pasted key.
            // (User's pasted key is used for the Try-It panels below, not for the preview map.)
            miniMap = new maplibregl.Map({
                container: 'miniMap',
                style: '/api/v2/map-style?style=Standard&color=Light',
                center: [106.8456, -6.2088],
                zoom: 11,
                attributionControl: false
            });

            miniMap.addControl(new maplibregl.NavigationControl(), 'top-right');

            miniMap.on('load', () => updateMapMarkers());

            miniMap.on('click', (e) => {
                const lng = e.lngLat.lng.toFixed(6);
                const lat = e.lngLat.lat.toFixed(6);

                // Pick from map mode (Route/Matrix)
                if (pickFromMapTarget && (currentApiMode === 'route' || currentApiMode === 'matrix')) {
                    if (pickFromMapTarget === 'dep') {
                        document.getElementById('depLng').value = lng;
                        document.getElementById('depLat').value = lat;
                    } else if (pickFromMapTarget === 'dest') {
                        document.getElementById('destLng').value = lng;
                        document.getElementById('destLat').value = lat;
                    } else if (pickFromMapTarget === 'waypoint' && currentApiMode === 'route') {
                        addWaypoint();
                        const items = document.querySelectorAll('#waypointsContainer .waypoint-item');
                        const last = items[items.length - 1];
                        if (last) {
                            const inputs = last.querySelectorAll('input');
                            if (inputs.length >= 2) {
                                inputs[0].value = lng;
                                inputs[1].value = lat;
                            }
                        }
                    } else if (pickFromMapTarget === 'matrixDest' && currentApiMode === 'matrix') {
                        addMatrixDest(parseFloat(lng), parseFloat(lat));
                    }
                    clearPickFromMapMode();
                    updateMapMarkers();
                    return;
                }

                if (currentApiMode === 'location') {
                    document.getElementById('locRevLng').value = lng;
                    document.getElementById('locRevLat').value = lat;
                    switchLocSubMode('reverse');
                } else if (currentApiMode === 'maps') {
                    if (currentMapsSubMode === 'tile') {
                        fillTileFromMapCenter();
                        const z = parseInt(document.getElementById('mapsZ').value),
                            x = parseInt(document.getElementById('mapsX').value),
                            y = parseInt(document.getElementById('mapsY').value);
                        drawTileBoundsOnMap(z, x, y);
                    }
                } else if (currentApiMode === 'geofence') {
                    if (currentGeoSubMode === 'put') {
                        const isCircle = document.getElementById('geoTypeCircle').checked;
                        if (isCircle) {
                            document.getElementById('geoCircleLng').value = lng;
                            document.getElementById('geoCircleLat').value = lat;
                        } else if (geoDrawMode) {
                            geoDrawPoints.push([parseFloat(lng), parseFloat(lat)]);
                            const closedCoords = [...geoDrawPoints, geoDrawPoints[0]];
                            document.getElementById('geoPolygonCoords').value = JSON.stringify(closedCoords, null, 2);
                            drawGeofencePolygonOnMap(closedCoords);
                        }
                    } else if (currentGeoSubMode === 'evaluate') {
                        document.getElementById('geoEvalLng').value = lng;
                        document.getElementById('geoEvalLat').value = lat;
                    }
                } else if (currentApiMode === 'tracking') {
                    if (currentTrkSubMode === 'update') {
                        document.getElementById('trkUpdateLng').value = lng;
                        document.getElementById('trkUpdateLat').value = lat;
                    }
                } else if (currentApiMode === 'matrix') {
                    if (clickCount === 0) {
                        document.getElementById('depLng').value = lng;
                        document.getElementById('depLat').value = lat;
                    } else {
                        addMatrixDest(lng, lat);
                    }
                    clickCount++;
                    updateMapMarkers();
                } else {
                    // Route mode: alternate dep/dest
                    if (clickCount % 2 === 0) {
                        document.getElementById('depLng').value = lng;
                        document.getElementById('depLat').value = lat;
                    } else {
                        document.getElementById('destLng').value = lng;
                        document.getElementById('destLat').value = lat;
                    }
                    clickCount++;
                    updateMapMarkers();
                }
            });
        }

        function updateMapMarkers() {
            const depLng = parseFloat(document.getElementById('depLng').value);
            const depLat = parseFloat(document.getElementById('depLat').value);
            const bounds = new maplibregl.LngLatBounds();
            let hasPoints = false;

            // Clear existing markers
            if (depMarker) depMarker.remove();
            if (destMarker) destMarker.remove();
            waypointMarkers.forEach(m => m.remove());
            waypointMarkers = [];
            clearMatrixDestMarkers();

            // Departure marker (green) — shared by route & matrix
            if (!isNaN(depLng) && !isNaN(depLat)) {
                depMarker = new maplibregl.Marker({
                        color: '#00B14F'
                    })
                    .setLngLat([depLng, depLat])
                    .setPopup(new maplibregl.Popup({
                        offset: 25
                    }).setText('Departure'))
                    .addTo(miniMap);
                bounds.extend([depLng, depLat]);
                hasPoints = true;
            }

            if (currentApiMode === 'route') {
                // Route: single destination + waypoints
                const destLng = parseFloat(document.getElementById('destLng').value);
                const destLat = parseFloat(document.getElementById('destLat').value);
                if (!isNaN(destLng) && !isNaN(destLat)) {
                    destMarker = new maplibregl.Marker({
                            color: '#dc3545'
                        })
                        .setLngLat([destLng, destLat])
                        .setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setText('Destination'))
                        .addTo(miniMap);
                    bounds.extend([destLng, destLat]);
                    hasPoints = true;
                }
                // Waypoint markers (blue)
                getWaypoints().forEach((wp, i) => {
                    const el = document.createElement('div');
                    el.style.cssText = 'background:#0d6efd;color:white;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,0.3);';
                    el.textContent = i + 1;
                    const m = new maplibregl.Marker({
                            element: el
                        })
                        .setLngLat(wp)
                        .setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setText(`Waypoint ${i + 1}`))
                        .addTo(miniMap);
                    waypointMarkers.push(m);
                    bounds.extend(wp);
                    hasPoints = true;
                });
            } else if (currentApiMode === 'matrix') {
                // Matrix: multiple destinations (numbered red markers)
                getMatrixDests().forEach((dest, i) => {
                    const el = document.createElement('div');
                    el.style.cssText = 'background:#dc3545;color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,0.3);';
                    el.textContent = i + 1;
                    const m = new maplibregl.Marker({
                            element: el
                        })
                        .setLngLat(dest)
                        .setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setText(`Destination ${i + 1}`))
                        .addTo(miniMap);
                    matrixDestMarkers.push(m);
                    bounds.extend(dest);
                    hasPoints = true;
                });
            }

            if (hasPoints && !bounds.isEmpty()) {
                miniMap.fitBounds(bounds, {
                    padding: 60
                });
            }
        }

        function clearMatrixDestMarkers() {
            matrixDestMarkers.forEach(m => m.remove());
            matrixDestMarkers = [];
        }

        function clearMatrixResultLayers() {
            matrixResultLayers.forEach(id => {
                if (miniMap && miniMap.getLayer(id)) miniMap.removeLayer(id);
                if (miniMap && miniMap.getSource(id)) miniMap.removeSource(id);
            });
            matrixResultLayers = [];
            matrixResultMarkers.forEach(m => m.remove());
            matrixResultMarkers = [];
        }


        /* =========================================
           ROUTE DRAWING ON MAP
           ========================================= */
        function removeRouteFromMap() {
            ['routeLine', 'routeLineOutline', 'routeLineDashed'].forEach(id => {
                if (miniMap.getLayer(id)) miniMap.removeLayer(id);
            });
            if (miniMap.getSource('routeSource')) miniMap.removeSource('routeSource');
        }

        function clearLocationMarkers() {
            locationMarkers.forEach(m => m.remove());
            locationMarkers = [];
        }

        function clearAllMapLayers() {
            removeRouteFromMap();
            clearLocationMarkers();
            clearGeofenceLayers();
            clearTrackingLayers();
            clearMapsLayers();
            clearMatrixResultLayers();
            document.getElementById('btnClearRoute').style.display = 'none';
            document.getElementById('mapRouteStatus').innerHTML = '';
        }

        /* =========================================
           GEOFENCE MAP VISUALIZATION
           ========================================= */
        function clearGeofenceLayers() {
            geofenceLayers.forEach(id => {
                if (miniMap && miniMap.getLayer(id)) miniMap.removeLayer(id);
                if (miniMap && miniMap.getSource(id)) miniMap.removeSource(id);
            });
            geofenceLayers = [];
            geofenceMarkers.forEach(m => m.remove());
            geofenceMarkers = [];
        }

        function drawGeofenceCircleOnMap(center, radiusMeters) {
            clearGeofenceLayers();
            const points = 64;
            const km = radiusMeters / 1000;
            const coords = [];
            for (let i = 0; i <= points; i++) {
                const angle = (i / points) * 2 * Math.PI;
                const dx = km * Math.cos(angle);
                const dy = km * Math.sin(angle);
                const lat = center[1] + (dy / 110.574);
                const lng = center[0] + (dx / (111.320 * Math.cos(center[1] * Math.PI / 180)));
                coords.push([lng, lat]);
            }
            const srcId = 'geofence-circle-' + Date.now();
            miniMap.addSource(srcId, {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'Polygon',
                        coordinates: [coords]
                    }
                }
            });
            miniMap.addLayer({
                id: srcId + '-fill',
                type: 'fill',
                source: srcId,
                paint: {
                    'fill-color': '#ea580c',
                    'fill-opacity': 0.15
                }
            });
            miniMap.addLayer({
                id: srcId + '-line',
                type: 'line',
                source: srcId,
                paint: {
                    'line-color': '#ea580c',
                    'line-width': 2,
                    'line-opacity': 0.8
                }
            });
            geofenceLayers.push(srcId, srcId + '-fill', srcId + '-line');
            const marker = new maplibregl.Marker({
                color: '#ea580c'
            }).setLngLat(center).setPopup(new maplibregl.Popup({
                offset: 25
            }).setText(`Geofence center (r=${radiusMeters}m)`)).addTo(miniMap);
            geofenceMarkers.push(marker);
            miniMap.flyTo({
                center,
                zoom: 14
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function drawGeofencePolygonOnMap(coords) {
            clearGeofenceLayers();
            const srcId = 'geofence-poly-' + Date.now();
            miniMap.addSource(srcId, {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'Polygon',
                        coordinates: [coords]
                    }
                }
            });
            miniMap.addLayer({
                id: srcId + '-fill',
                type: 'fill',
                source: srcId,
                paint: {
                    'fill-color': '#ea580c',
                    'fill-opacity': 0.15
                }
            });
            miniMap.addLayer({
                id: srcId + '-line',
                type: 'line',
                source: srcId,
                paint: {
                    'line-color': '#ea580c',
                    'line-width': 2,
                    'line-opacity': 0.8
                }
            });
            geofenceLayers.push(srcId, srcId + '-fill', srcId + '-line');
            const bounds = new maplibregl.LngLatBounds();
            coords.forEach(c => bounds.extend(c));
            miniMap.fitBounds(bounds, {
                padding: 60
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function visualizeGeofenceData(data) {
            if (data.Geometry?.Circle) drawGeofenceCircleOnMap(data.Geometry.Circle.Center, data.Geometry.Circle.Radius);
            else if (data.Geometry?.Polygon) drawGeofencePolygonOnMap(data.Geometry.Polygon[0]);
        }

        function fillGeoCircleFromMap() {
            if (!miniMap) return;
            const c = miniMap.getCenter();
            document.getElementById('geoCircleLng').value = c.lng.toFixed(6);
            document.getElementById('geoCircleLat').value = c.lat.toFixed(6);
        }

        function drawGeofenceOnMap(evt) {
            const btn = (evt || window.event)?.target?.closest('button');
            if (!btn) return;
            if (geoDrawMode) {
                geoDrawMode = false;
                geoDrawPoints = [];
                btn.innerHTML = '<i class="bi bi-pencil me-1"></i> Draw on map';
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-outline-warning');
            } else {
                geoDrawMode = true;
                geoDrawPoints = [];
                btn.innerHTML = '<i class="bi bi-stop-circle me-1"></i> Stop drawing';
                btn.classList.remove('btn-outline-warning');
                btn.classList.add('btn-warning');
            }
        }

        /* =========================================
           TRACKING MAP VISUALIZATION
           ========================================= */
        function clearTrackingLayers() {
            trackingLayers.forEach(id => {
                if (miniMap && miniMap.getLayer(id)) miniMap.removeLayer(id);
                if (miniMap && miniMap.getSource(id)) miniMap.removeSource(id);
            });
            trackingLayers = [];
            trackingMarkers.forEach(m => m.remove());
            trackingMarkers = [];
        }

        function plotTrackingMarker(coords, deviceId) {
            clearTrackingLayers();
            const marker = new maplibregl.Marker({
                    color: '#db2777'
                }).setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setHTML(`<b>${deviceId}</b><br><span style="font-size:0.7rem;">${coords[0].toFixed(6)}, ${coords[1].toFixed(6)}</span>`))
                .addTo(miniMap);
            trackingMarkers.push(marker);
            miniMap.flyTo({
                center: coords,
                zoom: 14
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function plotTrackingPositions(positions) {
            clearTrackingLayers();
            const bounds = new maplibregl.LngLatBounds();
            positions.forEach(pos => {
                const coords = pos.Position;
                if (!coords) return;
                bounds.extend(coords);
                const marker = new maplibregl.Marker({
                        color: '#db2777'
                    }).setLngLat(coords)
                    .setPopup(new maplibregl.Popup({
                        offset: 25
                    }).setHTML(`<b>${pos.DeviceId || 'Unknown'}</b><br><span style="font-size:0.7rem;">${coords[0].toFixed(6)}, ${coords[1].toFixed(6)}</span>`))
                    .addTo(miniMap);
                trackingMarkers.push(marker);
            });
            if (positions.length > 0) {
                positions.length === 1 ? miniMap.flyTo({
                    center: positions[0].Position,
                    zoom: 14
                }) : miniMap.fitBounds(bounds, {
                    padding: 60
                });
            }
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function drawTrackingTrail(positions) {
            clearTrackingLayers();
            if (positions.length === 0) return;
            positions.sort((a, b) => new Date(a.SampleTime) - new Date(b.SampleTime));
            const coords = positions.map(p => p.Position).filter(Boolean);
            if (coords.length === 0) return;
            const srcId = 'tracking-trail-' + Date.now();
            miniMap.addSource(srcId, {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'LineString',
                        coordinates: coords
                    }
                }
            });
            miniMap.addLayer({
                id: srcId + '-line',
                type: 'line',
                source: srcId,
                paint: {
                    'line-color': '#db2777',
                    'line-width': 3,
                    'line-opacity': 0.8,
                    'line-dasharray': [2, 2]
                }
            });
            trackingLayers.push(srcId, srcId + '-line');
            const bounds = new maplibregl.LngLatBounds();
            positions.forEach((pos, i) => {
                if (!pos.Position) return;
                bounds.extend(pos.Position);
                const marker = new maplibregl.Marker({
                        color: i === positions.length - 1 ? '#db2777' : '#f9a8d4',
                        scale: i === positions.length - 1 ? 1 : 0.6
                    })
                    .setLngLat(pos.Position).setPopup(new maplibregl.Popup({
                        offset: 25
                    }).setHTML(`<b>#${i + 1}</b><br>${pos.SampleTime || ''}`)).addTo(miniMap);
                trackingMarkers.push(marker);
            });
            miniMap.fitBounds(bounds, {
                padding: 60
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        /* =========================================
           MAPS TILE HELPERS
           ========================================= */
        let mapsLayers = [];
        let mapsMarkers = [];

        function clearMapsLayers() {
            mapsLayers.forEach(id => {
                if (miniMap && miniMap.getLayer(id)) miniMap.removeLayer(id);
                if (miniMap && miniMap.getSource(id)) miniMap.removeSource(id);
            });
            mapsLayers = [];
            mapsMarkers.forEach(m => m.remove());
            mapsMarkers = [];
        }

        function fillTileFromMapCenter() {
            if (!miniMap) return;
            const center = miniMap.getCenter();
            const zoom = Math.floor(miniMap.getZoom());
            const x = lng2tile(center.lng, zoom);
            const y = lat2tile(center.lat, zoom);
            document.getElementById('mapsZ').value = zoom;
            document.getElementById('mapsX').value = x;
            document.getElementById('mapsY').value = y;
            drawTileBoundsOnMap(zoom, x, y);
        }

        function lng2tile(lng, zoom) {
            return Math.floor((lng + 180) / 360 * Math.pow(2, zoom));
        }

        function lat2tile(lat, zoom) {
            return Math.floor((1 - Math.log(Math.tan(lat * Math.PI / 180) + 1 / Math.cos(lat * Math.PI / 180)) / Math.PI) / 2 * Math.pow(2, zoom));
        }

        function tile2lng(x, z) {
            return x / Math.pow(2, z) * 360 - 180;
        }

        function tile2lat(y, z) {
            const n = Math.PI - 2 * Math.PI * y / Math.pow(2, z);
            return 180 / Math.PI * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
        }

        function drawTileBoundsOnMap(z, x, y) {
            clearMapsLayers();
            const west = tile2lng(x, z),
                east = tile2lng(x + 1, z);
            const north = tile2lat(y, z),
                south = tile2lat(y + 1, z);
            const coords = [
                [west, north],
                [east, north],
                [east, south],
                [west, south],
                [west, north]
            ];
            const srcId = 'tile-bounds-' + Date.now();
            miniMap.addSource(srcId, {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'Polygon',
                        coordinates: [coords]
                    }
                }
            });
            miniMap.addLayer({
                id: srcId + '-fill',
                type: 'fill',
                source: srcId,
                paint: {
                    'fill-color': '#0891b2',
                    'fill-opacity': 0.12
                }
            });
            miniMap.addLayer({
                id: srcId + '-line',
                type: 'line',
                source: srcId,
                paint: {
                    'line-color': '#0891b2',
                    'line-width': 2.5,
                    'line-opacity': 0.9,
                    'line-dasharray': [4, 2]
                }
            });
            mapsLayers.push(srcId, srcId + '-fill', srcId + '-line');

            const centerLng = (west + east) / 2,
                centerLat = (north + south) / 2;
            const el = document.createElement('div');
            el.style.cssText = 'background:#0891b2;color:white;padding:2px 8px;border-radius:6px;font-size:0.7rem;font-weight:700;white-space:nowrap;';
            el.textContent = `z${z} / x${x} / y${y}`;
            const marker = new maplibregl.Marker({
                element: el
            }).setLngLat([centerLng, centerLat]).addTo(miniMap);
            mapsMarkers.push(marker);

            miniMap.fitBounds([
                [west, south],
                [east, north]
            ], {
                padding: 40
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
            setMapRouteStatus('success', `Tile bounds: z${z}/x${x}/y${y} — ${(east - west).toFixed(4)}° × ${(north - south).toFixed(4)}°`);
        }

        function showStyleInfoOnMap(data) {
            clearMapsLayers();
            const layerCount = data.layers?.length || 0;
            const sourceCount = Object.keys(data.sources || {}).length;
            const sourceTypes = [...new Set(Object.values(data.sources || {}).map(s => s.type))].join(', ');
            setMapRouteStatus('success', `Style loaded: ${layerCount} layers, ${sourceCount} sources (${sourceTypes || 'none'})`);
            // Show a center info marker
            const center = miniMap.getCenter();
            const el = document.createElement('div');
            el.style.cssText = 'background:#0891b2;color:white;padding:4px 10px;border-radius:8px;font-size:0.72rem;font-weight:600;text-align:center;line-height:1.3;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,0.15);';
            el.innerHTML = `<i class="bi bi-palette-fill"></i> ${layerCount} layers · ${sourceCount} sources`;
            const marker = new maplibregl.Marker({
                element: el
            }).setLngLat([center.lng, center.lat]).addTo(miniMap);
            mapsMarkers.push(marker);
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function plotGeoEvalMarker(lng, lat, deviceId) {
            // Orange marker for geofence evaluation position
            const marker = new maplibregl.Marker({
                    color: '#f59e0b'
                }).setLngLat([lng, lat])
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setHTML(`<b>Eval: ${deviceId}</b><br><span style="font-size:0.7rem;">[${lng.toFixed(6)}, ${lat.toFixed(6)}]</span>`))
                .addTo(miniMap);
            geofenceMarkers.push(marker);
            miniMap.flyTo({
                center: [lng, lat],
                zoom: 14
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function drawRealRouteOnMap(coordinates) {
            removeRouteFromMap();
            miniMap.addSource('routeSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'LineString',
                        coordinates
                    }
                }
            });
            miniMap.addLayer({
                id: 'routeLineOutline',
                type: 'line',
                source: 'routeSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#ffffff',
                    'line-width': 7,
                    'line-opacity': 0.9
                }
            });
            miniMap.addLayer({
                id: 'routeLine',
                type: 'line',
                source: 'routeSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#007bff',
                    'line-width': 4,
                    'line-opacity': 0.9
                }
            });
            const bounds = new maplibregl.LngLatBounds();
            coordinates.forEach(c => bounds.extend(c));
            miniMap.fitBounds(bounds, {
                padding: 60
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function drawDashedLineOnMap(depCoords, destCoords) {
            removeRouteFromMap();
            miniMap.addSource('routeSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {},
                    geometry: {
                        type: 'LineString',
                        coordinates: [depCoords, destCoords]
                    }
                }
            });
            miniMap.addLayer({
                id: 'routeLineDashed',
                type: 'line',
                source: 'routeSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#dc3545',
                    'line-width': 3,
                    'line-opacity': 0.7,
                    'line-dasharray': [3, 3]
                }
            });
            const bounds = new maplibregl.LngLatBounds();
            bounds.extend(depCoords);
            bounds.extend(destCoords);
            miniMap.fitBounds(bounds, {
                padding: 60
            });
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function plotLocationMarkers(places) {
            clearLocationMarkers();
            const bounds = new maplibregl.LngLatBounds();
            let hasCoords = false;

            places.forEach((place, i) => {
                const coords = place.coords;
                if (!coords) return;
                hasCoords = true;
                bounds.extend(coords);

                const marker = new maplibregl.Marker({
                        color: '#7c3aed'
                    })
                    .setLngLat(coords)
                    .setPopup(new maplibregl.Popup({
                            offset: 25,
                            maxWidth: '280px'
                        })
                        .setHTML(`<div style="font-size:0.8rem;"><b>${place.label || 'Unknown'}</b><br><span style="color:#888;font-size:0.7rem;">${coords[0].toFixed(6)}, ${coords[1].toFixed(6)}</span></div>`))
                    .addTo(miniMap);
                locationMarkers.push(marker);
            });

            if (hasCoords) {
                if (places.length === 1) {
                    miniMap.flyTo({
                        center: places[0].coords,
                        zoom: 15
                    });
                } else {
                    miniMap.fitBounds(bounds, {
                        padding: 60
                    });
                }
            }
            document.getElementById('btnClearRoute').style.display = 'inline-flex';
        }

        function setMapRouteStatus(type, message) {
            const icons = {
                success: 'bi-check-circle-fill',
                error: 'bi-exclamation-triangle-fill',
                loading: 'bi-hourglass-split'
            };
            document.getElementById('mapRouteStatus').innerHTML =
                `<div class="route-status-bar ${type}"><i class="bi ${icons[type]}"></i> ${message}</div>`;
        }


        /* =========================================
           SEND REQUEST (Mode-aware)
           ========================================= */
        async function sendRequest() {
            // Guard: API key wajib diisi
            const apiKeyInput = document.getElementById('awsApiKey');
            const apiKey = apiKeyInput.value.trim();
            if (!apiKey) {
                setStatus('Missing API Key', 'Please enter your AWS Location Service API key in the API Configuration section.', 0, false);
                document.getElementById('statusCard').style.display = 'block';
                apiKeyInput.classList.add('is-invalid');
                apiKeyInput.focus();
                // Visual shake feedback
                apiKeyInput.style.transition = 'transform 0.1s';
                apiKeyInput.style.transform = 'translateX(-4px)';
                setTimeout(() => {
                    apiKeyInput.style.transform = 'translateX(4px)';
                }, 100);
                setTimeout(() => {
                    apiKeyInput.style.transform = '';
                }, 200);
                return;
            }
            apiKeyInput.classList.remove('is-invalid');

            const btn = document.getElementById('btnSend');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';

            if (currentApiMode === 'route') await sendRouteRequest();
            else if (currentApiMode === 'matrix') await sendMatrixRequest();
            else if (currentApiMode === 'location') await sendLocationRequest();
            else if (currentApiMode === 'maps') await sendMapsRequest();
            else if (currentApiMode === 'geofence') await sendGeofenceRequest();
            else if (currentApiMode === 'tracking') await sendTrackingRequest();

            btn.disabled = false;
            const labels = {
                route: 'Route',
                matrix: 'Route Matrix',
                location: 'Location',
                maps: 'Maps',
                geofence: 'Geofence',
                tracking: 'Tracking'
            };
            btn.innerHTML = `<i class="bi bi-send-fill me-2"></i> Send ${labels[currentApiMode]} Request`;
        }


        // --- MODE 1: calculate/route ---
        async function sendRouteRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const routeCalc = document.getElementById('routeCalc').value;
            const uiTravelMode = document.querySelector('input[name="travelMode"]:checked').value;
            const distUnit = document.getElementById('distUnit').value;
            const departNow = document.getElementById('optDepartNow').checked;
            const includeGeometry = document.getElementById('optIncludeGeometry').checked;

            const depLng = parseFloat(document.getElementById('depLng').value);
            const depLat = parseFloat(document.getElementById('depLat').value);
            const destLng = parseFloat(document.getElementById('destLng').value);
            const destLat = parseFloat(document.getElementById('destLat').value);
            const waypoints = getWaypoints();

            const apiVer = currentRouteApiVersion;
            const travelMode = mapTravelMode(uiTravelMode, apiVer);
            let url, requestBody;

            if (apiVer === 'v0') {
                url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalc}/calculate/route?key=${apiKey}`;
                requestBody = {
                    DeparturePosition: [depLng, depLat],
                    DestinationPosition: [destLng, destLat],
                    TravelMode: travelMode,
                    DistanceUnit: distUnit,
                    IncludeLegGeometry: includeGeometry
                };
                if (departNow) requestBody.DepartNow = true;
                if (waypoints.length > 0) requestBody.WaypointPositions = waypoints;
            } else {
                url = `https://routes.geo.${region}.amazonaws.com/v2/routes?key=${apiKey}`;
                requestBody = {
                    Origin: [depLng, depLat],
                    Destination: [destLng, destLat],
                    TravelMode: travelMode,
                    LegGeometryFormat: includeGeometry ? 'Simple' : 'FlexiblePolyline'
                };
                if (departNow) requestBody.DepartureTime = new Date().toISOString();
                if (waypoints.length > 0) requestBody.Waypoints = waypoints.map(w => ({
                    Position: w
                }));
            }

            const startTime = performance.now();
            const timeStr = new Date().toISOString();

            showRequestLog(url, requestBody, timeStr);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });
                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();
                showResponseLog(response.status, elapsed, data);
                setStatusChip('route');
                document.getElementById('statusCard').style.display = 'block';

                let parsed = null;
                if (response.ok) {
                    if (apiVer === 'v0' && data.Summary) {
                        parsed = {
                            distance: data.Summary.Distance.toFixed(2),
                            distUnit: distUnit === 'Miles' ? 'mi' : 'km',
                            durationMin: Math.round(data.Summary.DurationSeconds / 60),
                            legsCount: data.Legs?.length || 0,
                            coords: []
                        };
                        if (includeGeometry && data.Legs) {
                            data.Legs.forEach(leg => {
                                if (leg.Geometry?.LineString) parsed.coords.push(...leg.Geometry.LineString);
                            });
                        }
                    } else if (apiVer === 'v2' && data.Routes && data.Routes.length > 0) {
                        const route = data.Routes[0];
                        const distMeters = route.Summary?.Distance || 0;
                        const distKm = distMeters / 1000;
                        const distConverted = distUnit === 'Miles' ? (distKm * 0.621371) : distKm;
                        parsed = {
                            distance: distConverted.toFixed(2),
                            distUnit: distUnit === 'Miles' ? 'mi' : 'km',
                            durationMin: Math.round((route.Summary?.Duration || 0) / 60),
                            legsCount: route.Legs?.length || 0,
                            coords: []
                        };
                        if (includeGeometry && route.Legs) {
                            route.Legs.forEach(leg => {
                                if (leg.Geometry?.LineString) parsed.coords.push(...leg.Geometry.LineString);
                            });
                        }
                    }
                }

                if (parsed) {
                    setStatus('Route Found', `Distance: ${parsed.distance} ${parsed.distUnit} | Duration: ${parsed.durationMin} min | Legs: ${parsed.legsCount} | ${apiVer} | ${elapsed}ms`, response.status, true);
                    if (parsed.coords.length > 0) {
                        drawRealRouteOnMap(parsed.coords);
                        setMapRouteStatus('success', `Route drawn: ${parsed.distance} ${parsed.distUnit}, ~${parsed.durationMin} min (${travelMode}, ${apiVer})`);
                    }
                } else {
                    setStatus('Error', `${data.message || data.Message || 'Request failed'} | ${apiVer} | ${elapsed}ms`, response.status, false);
                    drawDashedLineOnMap([depLng, depLat], [destLng, destLat]);
                    setMapRouteStatus('error', 'Route failed. Showing straight line.');
                }

                addHistory({
                    time: timeStr,
                    apiMode: 'route',
                    travelMode: uiTravelMode,
                    depLng,
                    depLat,
                    destLng,
                    destLat,
                    status: response.status,
                    hasError: !response.ok || !parsed,
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
            const uiTravelMode = document.querySelector('input[name="travelMode"]:checked').value;
            const distUnit = document.getElementById('distUnit').value;

            const depLng = parseFloat(document.getElementById('depLng').value);
            const depLat = parseFloat(document.getElementById('depLat').value);
            const destinations = getMatrixDests();

            if (destinations.length === 0) {
                setStatus('Error', 'Add at least one destination', 0, false);
                document.getElementById('statusCard').style.display = 'block';
                return;
            }

            const apiVer = currentRouteApiVersion;
            const travelMode = mapTravelMode(uiTravelMode, apiVer);
            let url, requestBody;

            if (apiVer === 'v0') {
                url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalc}/calculate/route-matrix?key=${apiKey}`;
                requestBody = {
                    DeparturePositions: [
                        [depLng, depLat]
                    ],
                    DestinationPositions: destinations,
                    TravelMode: travelMode,
                    DistanceUnit: distUnit
                };
            } else {
                url = `https://routes.geo.${region}.amazonaws.com/v2/route-matrix?key=${apiKey}`;
                requestBody = {
                    Origins: [{
                        Position: [depLng, depLat]
                    }],
                    Destinations: destinations.map(d => ({
                        Position: d
                    })),
                    TravelMode: travelMode,
                    RoutingBoundary: {
                        Unbounded: true
                    }
                };
            }

            const startTime = performance.now();
            const timeStr = new Date().toISOString();
            showRequestLog(url, requestBody, timeStr);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestBody)
                });
                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();
                showResponseLog(response.status, elapsed, data);
                setStatusChip('matrix');
                document.getElementById('statusCard').style.display = 'block';

                const hasError = (apiVer === 'v0' ? data.Summary?.ErrorCount : data.ErrorCount) > 0;
                const results = data.RouteMatrix?.[0] || [];
                const distLabel = distUnit === 'Miles' ? 'mi' : 'km';

                if (response.ok) {
                    // Build summary for all destinations (handle v0 km vs v2 meters)
                    const summaryParts = results.map((r, i) => {
                        if (r.Error) return `Dest ${i + 1}: ${r.Error.Code}`;
                        let distVal, durSec;
                        if (apiVer === 'v0') {
                            distVal = r.Distance;
                            durSec = r.DurationSeconds || 0;
                        } else {
                            // v2: Distance in meters, Duration in seconds
                            const km = (r.Distance || 0) / 1000;
                            distVal = distUnit === 'Miles' ? (km * 0.621371) : km;
                            durSec = r.Duration || 0;
                        }
                        return `Dest ${i + 1}: ${distVal?.toFixed(2)} ${distLabel} / ${Math.round(durSec / 60)} min`;
                    });
                    setStatus(`Matrix: ${destinations.length} Destination${destinations.length !== 1 ? 's' : ''}`, `${summaryParts.join(' | ')} | ${apiVer} | ${elapsed}ms`, response.status, !hasError);

                    // Draw dashed lines from departure to each destination
                    clearMatrixResultLayers();
                    const origin = [depLng, depLat];
                    results.forEach((r, i) => {
                        const dest = destinations[i];
                        const isErr = !!r.Error;
                        const color = isErr ? '#dc3545' : '#00B14F';
                        const srcId = 'matrix-line-' + Date.now() + '-' + i;
                        miniMap.addSource(srcId, {
                            type: 'geojson',
                            data: {
                                type: 'Feature',
                                properties: {},
                                geometry: {
                                    type: 'LineString',
                                    coordinates: [origin, dest]
                                }
                            }
                        });
                        miniMap.addLayer({
                            id: srcId + '-line',
                            type: 'line',
                            source: srcId,
                            paint: {
                                'line-color': color,
                                'line-width': 2.5,
                                'line-dasharray': [4, 3],
                                'line-opacity': 0.8
                            }
                        });
                        matrixResultLayers.push(srcId, srcId + '-line');

                        // Label at midpoint
                        const midLng = (origin[0] + dest[0]) / 2,
                            midLat = (origin[1] + dest[1]) / 2;
                        const el = document.createElement('div');
                        el.style.cssText = `background:${color};color:white;padding:2px 6px;border-radius:6px;font-size:0.65rem;font-weight:700;white-space:nowrap;box-shadow:0 1px 4px rgba(0,0,0,0.2);`;
                        el.textContent = isErr ? r.Error.Code : `${r.Distance?.toFixed(1)} km`;
                        const marker = new maplibregl.Marker({
                            element: el
                        }).setLngLat([midLng, midLat]).addTo(miniMap);
                        matrixResultMarkers.push(marker);
                    });
                    document.getElementById('btnClearRoute').style.display = 'inline-flex';
                    setMapRouteStatus('success', `${destinations.length} matrix line${destinations.length !== 1 ? 's' : ''} drawn`);
                } else {
                    const errCode = results[0]?.Error?.Code || data.message || 'Unknown';
                    setStatus(`Error: ${errCode}`, `TravelMode: ${travelMode} | DataSource: ${data.Summary?.DataSource || '-'} | ${elapsed}ms`, response.status, false);
                    setMapRouteStatus('error', `${errCode}: Matrix request failed.`);
                }

                addHistory({
                    time: timeStr,
                    apiMode: 'matrix',
                    travelMode,
                    depLng,
                    depLat,
                    destCount: destinations.length,
                    status: response.status,
                    hasError,
                    errorCode: hasError ? 'ErrorCount: ' + (data.Summary?.ErrorCount || 0) : null,
                    elapsed
                });
            } catch (err) {
                handleFetchError(err);
            }
        }


        // --- MODE 3: Places / Location APIs ---
        async function sendLocationRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const placeIdx = document.getElementById('placeIndex').value;
            const sub = currentLocSubMode;

            let url, requestBody, method = 'POST';

            const apiVer = currentLocApiVersion;

            if (apiVer === 'v0') {
                // ===== v0 (Legacy) endpoints =====
                if (sub === 'suggestions') {
                    url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIdx}/search/suggestions?key=${apiKey}`;
                    requestBody = {
                        Text: document.getElementById('locSuggestText').value,
                        MaxResults: parseInt(document.getElementById('locSuggestMax').value) || 5,
                        Language: document.getElementById('locSuggestLang').value
                    };
                } else if (sub === 'search') {
                    url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIdx}/search/text?key=${apiKey}`;
                    requestBody = {
                        Text: document.getElementById('locSearchText').value,
                        MaxResults: parseInt(document.getElementById('locSearchMax').value) || 5,
                        Language: document.getElementById('locSearchLang').value
                    };
                } else if (sub === 'reverse') {
                    url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIdx}/search/position?key=${apiKey}`;
                    const lng = parseFloat(document.getElementById('locRevLng').value);
                    const lat = parseFloat(document.getElementById('locRevLat').value);
                    requestBody = {
                        Position: [lng, lat],
                        MaxResults: parseInt(document.getElementById('locRevMax').value) || 1,
                        Language: document.getElementById('locRevLang').value
                    };
                } else if (sub === 'getplace') {
                    const placeId = document.getElementById('locPlaceId').value;
                    url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIdx}/places/${encodeURIComponent(placeId)}?key=${apiKey}`;
                    method = 'GET';
                    requestBody = null;
                }
            } else {
                // ===== v2 (New) endpoints =====
                if (sub === 'suggestions') {
                    url = `https://places.geo.${region}.amazonaws.com/v2/suggest?key=${apiKey}`;
                    requestBody = {
                        QueryText: document.getElementById('locSuggestText').value,
                        MaxResults: parseInt(document.getElementById('locSuggestMax').value) || 5,
                        Language: document.getElementById('locSuggestLang').value
                    };
                } else if (sub === 'search') {
                    url = `https://places.geo.${region}.amazonaws.com/v2/search-text?key=${apiKey}`;
                    requestBody = {
                        QueryText: document.getElementById('locSearchText').value,
                        MaxResults: parseInt(document.getElementById('locSearchMax').value) || 5,
                        Language: document.getElementById('locSearchLang').value
                    };
                } else if (sub === 'reverse') {
                    url = `https://places.geo.${region}.amazonaws.com/v2/reverse-geocode?key=${apiKey}`;
                    const lng = parseFloat(document.getElementById('locRevLng').value);
                    const lat = parseFloat(document.getElementById('locRevLat').value);
                    requestBody = {
                        QueryPosition: [lng, lat],
                        MaxResults: parseInt(document.getElementById('locRevMax').value) || 1,
                        Language: document.getElementById('locRevLang').value
                    };
                } else if (sub === 'getplace') {
                    const placeId = document.getElementById('locPlaceId').value;
                    url = `https://places.geo.${region}.amazonaws.com/v2/place/${encodeURIComponent(placeId)}?key=${apiKey}`;
                    method = 'GET';
                    requestBody = null;
                }
            }

            const startTime = performance.now();
            const timeStr = new Date().toISOString();
            showRequestLog(url, requestBody, timeStr, method);

            try {
                const fetchOpts = {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };
                if (requestBody) fetchOpts.body = JSON.stringify(requestBody);

                const response = await fetch(url, fetchOpts);
                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();
                showResponseLog(response.status, elapsed, data);
                setStatusChip('location');
                document.getElementById('statusCard').style.display = 'block';

                if (response.ok) {
                    // Parse results for display
                    const places = parseLocationResults(sub, data, apiVer);
                    const count = places.length;
                    setStatus(`${count} Result${count !== 1 ? 's' : ''} Found`, `Endpoint: ${sub} (${apiVer}) | ${elapsed}ms`, response.status, true);
                    renderLocResults(places, sub);
                    plotLocationMarkers(places);
                    setMapRouteStatus('success', `${count} location${count !== 1 ? 's' : ''} plotted on map`);
                } else {
                    const errMsg = data.message || data.Message || 'Request failed';
                    setStatus('Error', `${errMsg} | ${elapsed}ms`, response.status, false);
                    setMapRouteStatus('error', errMsg);
                }

                addHistory({
                    time: timeStr,
                    apiMode: 'location',
                    subMode: sub,
                    label: sub === 'getplace' ? document.getElementById('locPlaceId').value : (requestBody?.QueryText || requestBody?.Text || `[${(requestBody?.QueryPosition || requestBody?.Position)?.[0]?.toFixed(4)}, ${(requestBody?.QueryPosition || requestBody?.Position)?.[1]?.toFixed(4)}]`),
                    status: response.status,
                    hasError: !response.ok,
                    errorCode: !response.ok ? 'Error' : null,
                    elapsed
                });
            } catch (err) {
                handleFetchError(err);
            }
        }

        function parseLocationResults(sub, data, apiVer) {
            const places = [];
            if (apiVer === 'v0') {
                // ===== v0 (Legacy) parsing =====
                if (sub === 'suggestions') {
                    (data.Results || []).forEach(r => {
                        places.push({
                            label: r.Text || 'Unknown',
                            subLabel: null,
                            placeId: r.PlaceId || null,
                            coords: null
                        });
                    });
                } else if (sub === 'search' || sub === 'reverse') {
                    (data.Results || []).forEach(r => {
                        const p = r.Place || {};
                        places.push({
                            label: p.Label || 'Unknown',
                            subLabel: null,
                            placeId: r.PlaceId || null,
                            coords: p.Geometry?.Point || null
                        });
                    });
                } else if (sub === 'getplace') {
                    const p = data.Place;
                    if (p) places.push({
                        label: p.Label || 'Unknown',
                        subLabel: null,
                        placeId: null,
                        coords: p.Geometry?.Point || null
                    });
                }
            } else {
                // ===== v2 (New) parsing =====
                if (sub === 'suggestions') {
                    (data.ResultItems || []).forEach(r => {
                        const p = r.Place || {};
                        const title = r.Title || '';
                        const addr = (p.Address && p.Address.Label) || '';
                        places.push({
                            label: title || addr || 'Unknown',
                            subLabel: title && addr ? addr : null,
                            placeId: p.PlaceId || null,
                            coords: p.Position || null
                        });
                    });
                } else if (sub === 'search' || sub === 'reverse') {
                    (data.ResultItems || []).forEach(r => {
                        const title = r.Title || '';
                        const addr = (r.Address && r.Address.Label) || '';
                        places.push({
                            label: title || addr || 'Unknown',
                            subLabel: title && addr ? addr : null,
                            placeId: r.PlaceId || null,
                            coords: r.Position || null
                        });
                    });
                } else if (sub === 'getplace') {
                    const title = data.Title || '';
                    const addr = (data.Address && data.Address.Label) || '';
                    places.push({
                        label: title || addr || 'Unknown',
                        subLabel: title && addr ? addr : null,
                        placeId: data.PlaceId || null,
                        coords: data.Position || null
                    });
                }
            }
            return places;
        }

        function renderLocResults(places, sub) {
            const container = document.getElementById('locResultsContainer');
            const countBadge = document.getElementById('locResultCount');
            container.innerHTML = '';
            countBadge.textContent = places.length;

            if (places.length === 0) {
                container.innerHTML = '<div class="text-center text-muted small py-3">No results</div>';
                document.getElementById('cardLocResults').style.display = 'block';
                return;
            }

            places.forEach((p, i) => {
                const div = document.createElement('div');
                div.className = 'loc-result-item';

                const coordStr = p.coords ? `<span style="font-size:0.7rem;color:#888;">${p.coords[0].toFixed(6)}, ${p.coords[1].toFixed(6)}</span>` : '<span style="font-size:0.7rem;color:#ccc;">No coordinates</span>';

                const placeIdBtn = p.placeId ?
                    `<button class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 ms-auto" style="font-size:0.65rem;" onclick="event.stopPropagation(); useAsPlaceId(${JSON.stringify(p.placeId)})" title="Use as PlaceId"><i class="bi bi-arrow-right-short"></i> GetPlace</button>` :
                    '';

                const subLabelHtml = p.subLabel ? `<div style="font-size:0.72rem;color:#666;">${p.subLabel}</div>` : '';

                div.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill" style="background:#ede9fe;color:#6d28d9;">${i + 1}</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.82rem;">${p.label}</div>
                            ${subLabelHtml}
                            ${coordStr}
                        </div>
                        ${placeIdBtn}
                    </div>
                `;

                if (p.coords) {
                    div.onclick = () => miniMap.flyTo({
                        center: p.coords,
                        zoom: 16
                    });
                }

                container.appendChild(div);
            });

            document.getElementById('cardLocResults').style.display = 'block';
        }

        function useAsPlaceId(placeId) {
            document.getElementById('locPlaceId').value = placeId;
            switchLocSubMode('getplace');
        }

        function clearLocResults() {
            document.getElementById('locResultsContainer').innerHTML = '';
            document.getElementById('locResultCount').textContent = '0';
            document.getElementById('cardLocResults').style.display = 'none';
            clearLocationMarkers();
        }


        // --- MODE 4: Maps Inspector ---
        async function sendMapsRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const mapName = document.getElementById('mapNameInput').value;
            const sub = currentMapsSubMode;
            const apiVer = currentMapsApiVersion;
            let url;

            if (apiVer === 'v0') {
                // ===== v0 (Legacy) — uses map resource =====
                if (sub === 'style') {
                    url = `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`;
                } else if (sub === 'tile') {
                    const z = document.getElementById('mapsZ').value,
                        x = document.getElementById('mapsX').value,
                        y = document.getElementById('mapsY').value;
                    url = `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/tiles/${z}/${x}/${y}?key=${apiKey}`;
                } else if (sub === 'glyphs') {
                    const fontStack = encodeURIComponent(document.getElementById('mapsFontStack').value);
                    const range = document.getElementById('mapsGlyphRange').value;
                    url = `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/glyphs/${fontStack}/${range}.pbf?key=${apiKey}`;
                } else if (sub === 'sprites') {
                    const spriteFile = document.getElementById('mapsSpriteFile').value;
                    url = `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/sprites/${spriteFile}?key=${apiKey}`;
                }
            } else {
                // ===== v2 (New) — resource-less, langsung pilih Style =====
                // Default style: 'Standard' (vector). Bisa diganti ke Hybrid/Satellite kalau GrabMaps support.
                const styleName = 'Standard';
                if (sub === 'style') {
                    url = `https://maps.geo.${region}.amazonaws.com/v2/styles/${styleName}/descriptor?key=${apiKey}`;
                } else if (sub === 'tile') {
                    const z = document.getElementById('mapsZ').value,
                        x = document.getElementById('mapsX').value,
                        y = document.getElementById('mapsY').value;
                    url = `https://maps.geo.${region}.amazonaws.com/v2/tiles/raster/${styleName}/${z}/${x}/${y}?key=${apiKey}`;
                } else if (sub === 'glyphs') {
                    const fontStack = encodeURIComponent(document.getElementById('mapsFontStack').value);
                    const range = document.getElementById('mapsGlyphRange').value;
                    url = `https://maps.geo.${region}.amazonaws.com/v2/glyphs/${styleName}/${fontStack}/${range}?key=${apiKey}`;
                } else if (sub === 'sprites') {
                    const spriteFile = document.getElementById('mapsSpriteFile').value;
                    url = `https://maps.geo.${region}.amazonaws.com/v2/sprites/${styleName}/Default/Default/${spriteFile}?key=${apiKey}`;
                }
            }

            const startTime = performance.now();
            const timeStr = new Date().toISOString();
            showRequestLog(url, null, timeStr, 'GET');

            try {
                const response = await fetch(url);
                const elapsed = Math.round(performance.now() - startTime);
                const contentType = response.headers.get('content-type') || '';
                setStatusChip('maps');
                document.getElementById('statusCard').style.display = 'block';

                if (sub === 'style' || (sub === 'sprites' && contentType.includes('json'))) {
                    const data = await response.json();
                    showResponseLog(response.status, elapsed, data);
                    if (response.ok) {
                        const info = sub === 'style' ? `Layers: ${data.layers?.length || 0} | Sources: ${Object.keys(data.sources || {}).length}` : `Sprites: ${Object.keys(data).length}`;
                        setStatus(sub === 'style' ? 'Style Descriptor Retrieved' : 'Sprite JSON Retrieved', `${info} | ${elapsed}ms`, response.status, true);
                        if (sub === 'style') showStyleInfoOnMap(data);
                    } else {
                        setStatus('Error', `${data.message || 'Request failed'} | ${elapsed}ms`, response.status, false);
                    }
                } else {
                    const blob = await response.blob();
                    const binaryInfo = {
                        status: response.status,
                        contentType,
                        size: `${(blob.size / 1024).toFixed(2)} KB`,
                        url: url.replace(/key=[^&]+/, 'key=***REDACTED***')
                    };
                    showResponseLog(response.status, elapsed, binaryInfo);
                    if (response.ok) {
                        setStatus('Binary Data Retrieved', `Size: ${binaryInfo.size} | Type: ${contentType} | ${elapsed}ms`, response.status, true);
                        if (sub === 'tile') {
                            const z = parseInt(document.getElementById('mapsZ').value),
                                x = parseInt(document.getElementById('mapsX').value),
                                y = parseInt(document.getElementById('mapsY').value);
                            drawTileBoundsOnMap(z, x, y);
                        }
                        if (contentType.includes('image/png')) {
                            const imgUrl = URL.createObjectURL(blob);
                            document.getElementById('responseLog').innerHTML += `\n\n<img src="${imgUrl}" style="max-width:100%;border:1px solid #333;border-radius:8px;margin-top:8px;">`;
                        }
                    } else {
                        setStatus('Error', `HTTP ${response.status} | ${elapsed}ms`, response.status, false);
                    }
                }
                addHistory({
                    time: timeStr,
                    apiMode: 'maps',
                    subMode: sub,
                    label: `${sub}${sub === 'tile' ? ` z${document.getElementById('mapsZ').value}` : ''}`,
                    status: response.status,
                    hasError: !response.ok,
                    elapsed
                });
            } catch (err) {
                handleFetchError(err);
            }
        }


        // --- MODE 5: Geofencing ---
        async function sendGeofenceRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const collection = document.getElementById('geofenceCollection').value;
            const sub = currentGeoSubMode;
            const baseUrl = `https://geofencing.geo.${region}.amazonaws.com`;
            let url, method, requestBody = null;

            if (sub === 'put') {
                const geoId = document.getElementById('geoPutId').value;
                url = `${baseUrl}/geofencing/v0/collections/${collection}/geofences/${encodeURIComponent(geoId)}?key=${apiKey}`;
                method = 'PUT';
                const isCircle = document.getElementById('geoTypeCircle').checked;
                if (isCircle) {
                    requestBody = {
                        Geometry: {
                            Circle: {
                                Center: [parseFloat(document.getElementById('geoCircleLng').value), parseFloat(document.getElementById('geoCircleLat').value)],
                                Radius: parseFloat(document.getElementById('geoCircleRadius').value)
                            }
                        }
                    };
                } else {
                    requestBody = {
                        Geometry: {
                            Polygon: [JSON.parse(document.getElementById('geoPolygonCoords').value)]
                        }
                    };
                }
            } else if (sub === 'get') {
                const geoId = document.getElementById('geoGetId').value;
                url = `${baseUrl}/geofencing/v0/collections/${collection}/geofences/${encodeURIComponent(geoId)}?key=${apiKey}`;
                method = 'GET';
            } else if (sub === 'list') {
                url = `${baseUrl}/geofencing/v0/collections/${collection}/list-geofences?key=${apiKey}`;
                method = 'POST';
                requestBody = {};
            } else if (sub === 'evaluate') {
                url = `${baseUrl}/geofencing/v0/collections/${collection}/positions?key=${apiKey}`;
                method = 'POST';
                requestBody = {
                    DevicePositionUpdates: [{
                        DeviceId: document.getElementById('geoEvalDeviceId').value,
                        Position: [parseFloat(document.getElementById('geoEvalLng').value), parseFloat(document.getElementById('geoEvalLat').value)],
                        SampleTime: new Date().toISOString()
                    }]
                };
            }

            const startTime = performance.now();
            const timeStr = new Date().toISOString();
            showRequestLog(url, requestBody, timeStr, method);

            try {
                const fetchOpts = {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };
                if (requestBody) fetchOpts.body = JSON.stringify(requestBody);
                const response = await fetch(url, fetchOpts);
                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();
                showResponseLog(response.status, elapsed, data);
                setStatusChip('geofence');
                document.getElementById('statusCard').style.display = 'block';

                if (response.ok) {
                    if (sub === 'put') {
                        setStatus('Geofence Saved', `ID: ${document.getElementById('geoPutId').value} | ${elapsed}ms`, response.status, true);
                        const isCircle = document.getElementById('geoTypeCircle').checked;
                        if (isCircle) drawGeofenceCircleOnMap([parseFloat(document.getElementById('geoCircleLng').value), parseFloat(document.getElementById('geoCircleLat').value)], parseFloat(document.getElementById('geoCircleRadius').value));
                        else drawGeofencePolygonOnMap(JSON.parse(document.getElementById('geoPolygonCoords').value));
                    } else if (sub === 'get') {
                        setStatus('Geofence Retrieved', `ID: ${data.GeofenceId || '-'} | ${elapsed}ms`, response.status, true);
                        visualizeGeofenceData(data);
                    } else if (sub === 'list') {
                        const count = data.Entries?.length || 0;
                        setStatus(`${count} Geofence${count !== 1 ? 's' : ''} Found`, `${elapsed}ms`, response.status, true);
                        renderGeoResults(data.Entries || []);
                        clearGeofenceLayers();
                        (data.Entries || []).forEach(entry => visualizeGeofenceData(entry));
                    } else if (sub === 'evaluate') {
                        const pos = requestBody.DevicePositionUpdates[0];
                        setStatus('Position Evaluated', `Device: ${pos.DeviceId} | ${elapsed}ms`, response.status, true);
                        plotGeoEvalMarker(pos.Position[0], pos.Position[1], pos.DeviceId);
                    }
                    setMapRouteStatus('success', `Geofence ${sub} completed`);
                } else {
                    setStatus('Error', `${data.message || data.Message || 'Request failed'} | ${elapsed}ms`, response.status, false);
                    setMapRouteStatus('error', data.message || 'Geofence request failed');
                }
                addHistory({
                    time: timeStr,
                    apiMode: 'geofence',
                    subMode: sub,
                    label: sub === 'put' ? document.getElementById('geoPutId').value : sub,
                    status: response.status,
                    hasError: !response.ok,
                    elapsed
                });
            } catch (err) {
                handleFetchError(err);
            }
        }

        function visualizeGeofenceByIndex(i) {
            if (lastGeoResults[i]) visualizeGeofenceData(lastGeoResults[i]);
        }

        function renderGeoResults(entries) {
            lastGeoResults = entries;
            const container = document.getElementById('geoResultsContainer');
            document.getElementById('geoResultCount').textContent = entries.length;
            container.innerHTML = entries.length === 0 ? '<div class="text-center text-muted small py-3">No geofences</div>' :
                entries.map((e, i) => `<div class="geo-result-item" onclick="visualizeGeofenceByIndex(${i})">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill" style="background:#ffedd5;color:#ea580c;">${i + 1}</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.82rem;">${(e.GeofenceId || 'Unknown').replace(/</g, '&lt;')}</div>
                            <span style="font-size:0.7rem;color:#888;">${e.Geometry?.Circle ? `Circle r=${e.Geometry.Circle.Radius}m` : 'Polygon'} | ${e.Status || '-'}</span>
                        </div>
                    </div></div>`).join('');
            document.getElementById('cardGeoResults').style.display = 'block';
        }

        function clearGeoResults() {
            document.getElementById('geoResultsContainer').innerHTML = '';
            document.getElementById('geoResultCount').textContent = '0';
            document.getElementById('cardGeoResults').style.display = 'none';
            clearGeofenceLayers();
        }


        // --- MODE 6: Tracking ---
        async function sendTrackingRequest() {
            const region = document.getElementById('awsRegion').value;
            const apiKey = document.getElementById('awsApiKey').value;
            const tracker = document.getElementById('trackerName').value;
            const sub = currentTrkSubMode;
            const baseUrl = `https://tracking.geo.${region}.amazonaws.com`;
            let url, method = 'POST',
                requestBody = null;

            if (sub === 'update') {
                url = `${baseUrl}/tracking/v0/trackers/${tracker}/update-positions?key=${apiKey}`;
                requestBody = {
                    Updates: [{
                        DeviceId: document.getElementById('trkUpdateDeviceId').value,
                        Position: [parseFloat(document.getElementById('trkUpdateLng').value), parseFloat(document.getElementById('trkUpdateLat').value)],
                        SampleTime: new Date().toISOString()
                    }]
                };
            } else if (sub === 'get') {
                url = `${baseUrl}/tracking/v0/trackers/${tracker}/get-positions?key=${apiKey}`;
                requestBody = {
                    DeviceIds: document.getElementById('trkGetDeviceIds').value.split(',').map(s => s.trim()).filter(Boolean)
                };
            } else if (sub === 'history') {
                const deviceId = document.getElementById('trkHistDeviceId').value;
                url = `${baseUrl}/tracking/v0/trackers/${tracker}/devices/${encodeURIComponent(deviceId)}/list-positions?key=${apiKey}`;
                requestBody = {};
                const start = document.getElementById('trkHistStart').value;
                const end = document.getElementById('trkHistEnd').value;
                if (start) requestBody.StartTimeInclusive = new Date(start).toISOString();
                if (end) requestBody.EndTimeExclusive = new Date(end).toISOString();
            } else if (sub === 'list') {
                url = `${baseUrl}/tracking/v0/trackers/${tracker}/list-positions?key=${apiKey}`;
                requestBody = {};
            }

            const startTime = performance.now();
            const timeStr = new Date().toISOString();
            showRequestLog(url, requestBody, timeStr, method);

            try {
                const fetchOpts = {
                    method,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };
                if (requestBody) fetchOpts.body = JSON.stringify(requestBody);
                const response = await fetch(url, fetchOpts);
                const elapsed = Math.round(performance.now() - startTime);
                const data = await response.json();
                showResponseLog(response.status, elapsed, data);
                setStatusChip('tracking');
                document.getElementById('statusCard').style.display = 'block';

                if (response.ok) {
                    if (sub === 'update') {
                        const errors = data.Errors?.length || 0;
                        setStatus(errors === 0 ? 'Position Updated' : `Updated with ${errors} error(s)`, `Device: ${document.getElementById('trkUpdateDeviceId').value} | ${elapsed}ms`, response.status, errors === 0);
                        plotTrackingMarker([parseFloat(document.getElementById('trkUpdateLng').value), parseFloat(document.getElementById('trkUpdateLat').value)], document.getElementById('trkUpdateDeviceId').value);
                    } else if (sub === 'get') {
                        const count = data.DevicePositions?.length || 0;
                        setStatus(`${count} Position${count !== 1 ? 's' : ''} Retrieved`, `${elapsed}ms`, response.status, true);
                        plotTrackingPositions(data.DevicePositions || []);
                        renderTrkResults(data.DevicePositions || []);
                    } else if (sub === 'history') {
                        const count = data.DevicePositions?.length || 0;
                        setStatus(`${count} Historical Position${count !== 1 ? 's' : ''}`, `${elapsed}ms`, response.status, true);
                        drawTrackingTrail(data.DevicePositions || []);
                    } else if (sub === 'list') {
                        const count = data.Entries?.length || 0;
                        setStatus(`${count} Device${count !== 1 ? 's' : ''} Found`, `${elapsed}ms`, response.status, true);
                        plotTrackingPositions(data.Entries || []);
                        renderTrkResults(data.Entries || []);
                    }
                    setMapRouteStatus('success', `Tracking ${sub} completed`);
                } else {
                    setStatus('Error', `${data.message || data.Message || 'Request failed'} | ${elapsed}ms`, response.status, false);
                    setMapRouteStatus('error', data.message || 'Tracking request failed');
                }
                addHistory({
                    time: timeStr,
                    apiMode: 'tracking',
                    subMode: sub,
                    label: sub === 'update' ? document.getElementById('trkUpdateDeviceId').value : sub,
                    status: response.status,
                    hasError: !response.ok,
                    elapsed
                });
            } catch (err) {
                handleFetchError(err);
            }
        }

        function renderTrkResults(entries) {
            const container = document.getElementById('trkResultsContainer');
            document.getElementById('trkResultCount').textContent = entries.length;
            container.innerHTML = entries.length === 0 ? '<div class="text-center text-muted small py-3">No devices</div>' :
                entries.map((e, i) => {
                    const hasPos = e.Position && e.Position.length >= 2;
                    const onclick = hasPos ? `onclick="miniMap.flyTo({center: [${e.Position[0]}, ${e.Position[1]}], zoom: 14})"` : '';
                    return `<div class="trk-result-item" ${onclick}>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill" style="background:#fce7f3;color:#db2777;">${i + 1}</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.82rem;">${e.DeviceId || 'Unknown'}</div>
                            <span style="font-size:0.7rem;color:#888;">${e.Position ? `[${e.Position[0].toFixed(4)}, ${e.Position[1].toFixed(4)}]` : 'No position'} | ${e.SampleTime || '-'}</span>
                        </div>
                    </div></div>`;
                }).join('');
            document.getElementById('cardTrkResults').style.display = 'block';
        }

        function clearTrkResults() {
            document.getElementById('trkResultsContainer').innerHTML = '';
            document.getElementById('trkResultCount').textContent = '0';
            document.getElementById('cardTrkResults').style.display = 'none';
            clearTrackingLayers();
        }


        /* =========================================
           SHARED HELPERS
           ========================================= */
        function showRequestLog(url, body, timeStr, method = 'POST') {
            document.getElementById('requestLog').innerHTML = formatJson({
                Time: timeStr,
                Method: method,
                URL: url.replace(/key=[^&]+/, 'key=***REDACTED***'),
                Headers: {
                    "Content-Type": "application/json"
                },
                Body: body
            });
        }

        function showResponseLog(status, elapsed, data) {
            document.getElementById('responseLog').innerHTML = formatJson({
                Time: new Date().toISOString(),
                HttpStatus: status,
                ResponseTime: elapsed + 'ms',
                Body: data
            });
        }

        function setStatus(title, sub, httpStatus, isSuccess) {
            document.getElementById('statusTitle').textContent = title;
            document.getElementById('statusSub').textContent = sub;
            document.getElementById('statusBadge').className = `status-badge ${isSuccess ? 'bg-success' : 'bg-danger'} text-white`;
            document.getElementById('statusBadge').textContent = `${httpStatus} ${isSuccess ? 'OK' : 'ERROR'}`;
        }

        function setStatusChip(mode) {
            const chip = document.getElementById('statusModeChip');
            const map = {
                route: {
                    cls: 'chip-route',
                    icon: 'bi-sign-turn-right-fill',
                    text: 'Route'
                },
                matrix: {
                    cls: 'chip-matrix',
                    icon: 'bi-grid-3x3',
                    text: 'Matrix'
                },
                location: {
                    cls: 'chip-location',
                    icon: 'bi-geo-alt-fill',
                    text: 'Location'
                },
                maps: {
                    cls: 'chip-maps',
                    icon: 'bi-map-fill',
                    text: 'Maps'
                },
                geofence: {
                    cls: 'chip-geofence',
                    icon: 'bi-bounding-box',
                    text: 'Geofence'
                },
                tracking: {
                    cls: 'chip-tracking',
                    icon: 'bi-broadcast-pin',
                    text: 'Tracking'
                }
            };
            const m = map[mode];
            chip.className = `mode-info-chip ${m.cls}`;
            chip.innerHTML = `<i class="bi ${m.icon}"></i> ${m.text}`;
        }

        function handleFetchError(err) {
            document.getElementById('responseLog').innerHTML = `<span class="log-error">FETCH ERROR: ${err.message}</span>`;
            document.getElementById('statusCard').style.display = 'block';
            setStatus('Network Error', err.message, 0, false);
        }


        /* =========================================
           FORMAT & UTILITIES
           ========================================= */
        function formatJson(obj) {
            const json = JSON.stringify(obj, null, 2);
            return json
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
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
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        }

        function copyToClipboard(elementId, evt) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = (evt || window.event)?.target?.closest('button');
                if (btn) {
                    const original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check me-1"></i> Copied!';
                    setTimeout(() => btn.innerHTML = original, 1500);
                }
            });
        }


        /* =========================================
           HISTORY
           ========================================= */
        function addHistory(entry) {
            requestHistory.unshift(entry);
            if (requestHistory.length > 15) requestHistory.pop();
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

                const chipMap = {
                    route: '<span class="mode-info-chip chip-route" style="font-size:0.6rem;padding:2px 6px;">Route</span>',
                    matrix: '<span class="mode-info-chip chip-matrix" style="font-size:0.6rem;padding:2px 6px;">Matrix</span>',
                    location: '<span class="mode-info-chip chip-location" style="font-size:0.6rem;padding:2px 6px;">Location</span>',
                    maps: '<span class="mode-info-chip chip-maps" style="font-size:0.6rem;padding:2px 6px;">Maps</span>',
                    geofence: '<span class="mode-info-chip chip-geofence" style="font-size:0.6rem;padding:2px 6px;">Geofence</span>',
                    tracking: '<span class="mode-info-chip chip-tracking" style="font-size:0.6rem;padding:2px 6px;">Tracking</span>'
                };

                const modeChip = chipMap[h.apiMode] || chipMap.route;
                let detail;
                if (['location', 'maps', 'geofence', 'tracking'].includes(h.apiMode)) {
                    detail = `<span class="text-muted" style="font-size:0.7rem;">${h.subMode}: ${h.label || '-'}</span>`;
                } else if (h.apiMode === 'matrix') {
                    detail = `<span class="text-muted" style="font-size:0.7rem;">[${h.depLng}, ${h.depLat}] &rarr; ${h.destCount || 1} dest(s)</span>`;
                } else {
                    detail = `<span class="text-muted" style="font-size:0.7rem;">[${h.depLng}, ${h.depLat}] &rarr; [${h.destLng}, ${h.destLat}]</span>`;
                }

                return `
                    <div class="history-item" onclick="loadHistory(${i})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi ${statusIcon} ${statusColor}"></i>
                                ${modeChip}
                                <span class="fw-semibold small">${h.travelMode || h.subMode || ''}</span>
                                <span class="text-muted small">${time}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small">${h.elapsed}ms</span>
                                <span class="badge ${h.hasError ? 'text-bg-danger' : 'text-bg-success'}">${statusText}</span>
                            </div>
                        </div>
                        <div class="mt-1">${detail}</div>
                    </div>`;
            }).join('');
        }

        function loadHistory(index) {
            const h = requestHistory[index];
            if (h.apiMode) switchApiMode(h.apiMode);
            if (h.apiMode === 'location' && h.subMode) switchLocSubMode(h.subMode);
            if (h.apiMode === 'maps' && h.subMode) switchMapsSubMode(h.subMode);
            if (h.apiMode === 'geofence' && h.subMode) switchGeoSubMode(h.subMode);
            if (h.apiMode === 'tracking' && h.subMode) switchTrkSubMode(h.subMode);

            if (h.depLng !== undefined) {
                document.getElementById('depLng').value = h.depLng;
                document.getElementById('depLat').value = h.depLat;
                if (h.destLng !== undefined) {
                    document.getElementById('destLng').value = h.destLng;
                    document.getElementById('destLat').value = h.destLat;
                }
                const modeRadio = document.querySelector(`input[name="travelMode"][value="${h.travelMode}"]`);
                if (modeRadio) modeRadio.checked = true;
                updateMapMarkers();
            }
        }

        function clearHistory() {
            requestHistory = [];
            renderHistory();
        }


        /* =========================================
           INIT
           ========================================= */
        // === API Key Gate ===
        let mapInitialized = false;

        function bootstrapTester() {
            if (mapInitialized) return;
            mapInitialized = true;
            initMiniMap();
        }

        function showApiKeyGate(prefill) {
            const modalEl = document.getElementById('apiKeyGateModal');
            const gateInput = document.getElementById('gateApiKey');
            const gateError = document.getElementById('gateError');
            if (prefill) gateInput.value = prefill;
            gateError.style.display = 'none';
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            setTimeout(() => gateInput.focus(), 300);
        }

        function setupApiKeyGate() {
            const gateInput = document.getElementById('gateApiKey');
            const gateBtn = document.getElementById('gateContinueBtn');
            const gateError = document.getElementById('gateError');
            const gateToggle = document.getElementById('gateApiKeyToggle');
            const gateToggleIcon = document.getElementById('gateApiKeyToggleIcon');
            const apiKeyInput = document.getElementById('awsApiKey');
            const modalEl = document.getElementById('apiKeyGateModal');

            // Toggle show/hide on gate input
            gateToggle.addEventListener('click', () => {
                const isPassword = gateInput.type === 'password';
                gateInput.type = isPassword ? 'text' : 'password';
                gateToggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });

            // Submit on Continue
            const submit = () => {
                const val = gateInput.value.trim();
                if (!val) {
                    gateError.querySelector('#gateErrorMsg').textContent = 'API Key tidak boleh kosong';
                    gateError.style.display = 'block';
                    gateInput.focus();
                    return;
                }
                if (val.length < 20) {
                    gateError.querySelector('#gateErrorMsg').textContent = 'API Key terlalu pendek — pastikan kamu copy lengkap';
                    gateError.style.display = 'block';
                    return;
                }
                // Save & sync to main input (uses shared storage with docs Inspector)
                saveSharedKey(val);
                apiKeyInput.value = val;
                apiKeyInput.classList.remove('is-invalid');
                // Hide modal
                bootstrap.Modal.getInstance(modalEl).hide();
                // Init map (one-time)
                bootstrapTester();
            };

            gateBtn.addEventListener('click', submit);
            gateInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submit();
                }
            });
        }

        function updateApiKeyStatusBadge() {
            const badge = document.getElementById('apiKeyStatusBadge');
            if (!badge) return;
            const apiKeyInput = document.getElementById('awsApiKey');
            const hasKey = apiKeyInput && apiKeyInput.value.trim().length > 0;
            badge.style.color = hasKey ? '#16a34a' : '#dc2626';
            badge.title = hasKey ? 'API Key configured' : 'API Key not set';
        }

        /* === Shared API Key storage with /docs/aws-api Inspector ===
           Read order: docs Inspector key (awsapi_user_key.apiKey) → tester own key (tester_aws_api_key).
           Write: always save to BOTH stores so user only needs to paste once.
           Also syncs region when docs Inspector has one set. */
        function loadDocsInspectorKey() {
            try {
                const obj = JSON.parse(localStorage.getItem('awsapi_user_key') || 'null');
                if (obj && obj.apiKey) return obj;
            } catch (_) {}
            return null;
        }
        function saveSharedKey(apiKey) {
            apiKey = (apiKey || '').trim();
            if (apiKey) {
                localStorage.setItem('tester_aws_api_key', apiKey);
                // Also update docs Inspector key if it exists — preserve other fields
                try {
                    const obj = JSON.parse(localStorage.getItem('awsapi_user_key') || 'null') || {};
                    obj.apiKey = apiKey;
                    localStorage.setItem('awsapi_user_key', JSON.stringify(obj));
                } catch (_) {}
            } else {
                localStorage.removeItem('tester_aws_api_key');
                // Don't fully delete docs Inspector key — just clear apiKey field
                try {
                    const obj = JSON.parse(localStorage.getItem('awsapi_user_key') || 'null');
                    if (obj) { obj.apiKey = ''; localStorage.setItem('awsapi_user_key', JSON.stringify(obj)); }
                } catch (_) {}
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setupApiKeyGate();

            // Load saved key (prefer docs Inspector if available)
            const apiKeyInput = document.getElementById('awsApiKey');
            const docsKey = loadDocsInspectorKey();
            const saved = docsKey?.apiKey || localStorage.getItem('tester_aws_api_key');
            if (saved) {
                apiKeyInput.value = saved;
                // If docs Inspector has region, sync it (test page region is readonly env)
                const regionInput = document.getElementById('awsRegion');
                if (docsKey?.region && regionInput) regionInput.value = docsKey.region;
                bootstrapTester();
            } else {
                showApiKeyGate();
            }
            updateApiKeyStatusBadge();

            // Sync changes from main input → BOTH localStorage stores
            if (apiKeyInput) {
                apiKeyInput.addEventListener('input', () => {
                    const val = apiKeyInput.value.trim();
                    if (val) apiKeyInput.classList.remove('is-invalid');
                    saveSharedKey(val);
                    updateApiKeyStatusBadge();
                });
            }

            // Listen for cross-tab storage changes (e.g. user updates key in /docs/aws-api in another tab)
            window.addEventListener('storage', (e) => {
                if (e.key === 'awsapi_user_key' || e.key === 'tester_aws_api_key') {
                    const k = loadDocsInspectorKey()?.apiKey || localStorage.getItem('tester_aws_api_key') || '';
                    if (k && apiKeyInput && apiKeyInput.value !== k) {
                        apiKeyInput.value = k;
                        updateApiKeyStatusBadge();
                    }
                }
            });
            ['depLng', 'depLat', 'destLng', 'destLat'].forEach(id => {
                document.getElementById(id).addEventListener('change', updateMapMarkers);
            });
            // Geofence geometry type toggle
            document.querySelectorAll('input[name="geoType"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    const isCircle = document.getElementById('geoTypeCircle').checked;
                    document.getElementById('geoCircleInputs').style.display = isCircle ? 'block' : 'none';
                    document.getElementById('geoPolygonInputs').style.display = isCircle ? 'none' : 'block';
                });
            });
        });
    </script>
</body>

</html>