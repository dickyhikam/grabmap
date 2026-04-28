<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Demo MAP Grab X TJ (AWS) — Direct API [TEST]</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/map-custom.css') }}">

    <style>
        /* 🌙 Dark UI overrides — aktif saat <body> punya class .dark-ui
           Memanfaatkan CSS variables dari map-custom.css supaya 1 toggle = semua UI ikut */
        body.dark-ui {
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --bg-glass: rgba(15, 23, 42, 0.88);
            --bg-glass-strong: rgba(15, 23, 42, 0.96);
            --bg-surface: #1e293b;
            --bg-subtle: #0f172a;
            --border-light: rgba(255, 255, 255, 0.08);
            --grab-green-light: rgba(0, 177, 79, 0.20);
            --grab-green-subtle: rgba(0, 177, 79, 0.10);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.45);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.55);
        }

        /* Beberapa elemen pakai hardcoded #fff — paksa pakai surface var */
        body.dark-ui .v2-panel,
        body.dark-ui input.form-control,
        body.dark-ui select.form-select {
            background-color: var(--bg-surface) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-light) !important;
        }

        body.dark-ui input.form-control::placeholder {
            color: var(--text-muted) !important;
        }

        /* Bootstrap modal body & content */
        body.dark-ui .modal-content {
            background: var(--bg-surface);
            color: var(--text-primary);
        }

        body.dark-ui .modal-content .text-muted,
        body.dark-ui .modal-content .text-secondary {
            color: var(--text-muted) !important;
        }

        /* Toast container (kontras tetap baca) */
        body.dark-ui .toast {
            background-color: var(--bg-surface);
            color: var(--text-primary);
        }

        /* Suggestion title row: title kiri (truncate) + distance kanan */
        .suggestion-title-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .suggestion-title-row .suggestion-title {
            flex: 1;
            min-width: 0;
        }

        .suggestion-distance {
            flex-shrink: 0;
            font-size: 0.72rem;
            font-weight: 600;
            color: #00B14F;
            white-space: nowrap;
        }

        body.dark-ui .suggestion-distance {
            color: #4ade80;
        }
    </style>
</head>

<body>

    <div class="floating-header">
        <div class="logo-container">
            <img src="{{ asset('logo.png') }}" alt="Grab Logo" class="grab-logo">
            <span class="logo-x">x</span>
            <img src="{{ asset('images/logo-tj.png') }}" alt="TJ Logo" class="partner-logo">
        </div>

        <div class="search-wrapper">
            <div class="search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" placeholder="Search a place..." id="searchInput">
            </div>
            <ul class="suggestions-list" id="suggestionsList"></ul>
        </div>

        <div class="header-actions">
            <button class="btn-search-main" type="button" onclick="handleManualSearch()" title="Search">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>

    <div class="locations-panel" id="locationsPanel">
        <div class="panel-header">
            <div class="panel-title-row">
                <div class="panel-title">
                    <h6>Location Manager</h6>
                    <button class="btn-help-minimal" data-bs-toggle="modal" data-bs-target="#helpModal"
                        title="Guide & Information">
                        <i class="bi bi-question-circle-fill"></i>
                    </button>
                </div>
                <button class="btn-reset-minimal" onclick="clearAllMarkers()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>

            <div class="mode-switch-container mb-2">
                <input type="radio" class="btn-check" name="travelMode" id="modeWalk" value="Walking" checked>
                <label class="btn-mode-switch flex-grow-1" for="modeWalk">
                    <i class="bi bi-person-walking me-1"></i>
                    Walking
                </label>
                <input type="radio" class="btn-check" name="travelMode" id="modeBike" value="Motorcycle">
                <label class="btn-mode-switch flex-grow-1" for="modeBike">
                    <i class="bi bi-bicycle me-1"></i>
                    Motorcycle
                </label>
                <input type="radio" class="btn-check" name="travelMode" id="modeCar" value="Car">
                <label class="btn-mode-switch flex-grow-1" for="modeCar">
                    <i class="bi bi-car-front-fill me-1"></i>
                    Car
                </label>
            </div>

            <div class="mode-switch-container mb-3" id="roadTypeContainer" hidden>
                <input type="radio" class="btn-check" name="roadType" id="roadNormal" value="false" checked>
                <label class="btn-mode-switch flex-grow-1" for="roadNormal" title="Normal Roads">
                    <i class="bi bi-signpost-split-fill me-1"></i> Normal
                </label>
                <input type="radio" class="btn-check" name="roadType" id="roadToll" value="true">
                <label class="btn-mode-switch flex-grow-1" for="roadToll" title="Enable Toll Roads">
                    <i class="bi bi-cash-coin me-1"></i> Toll
                </label>
            </div>

            <div id="originCardContainer" class="mb-2"></div>

            <div class="d-flex gap-1 mb-3">
                <button class="btn d-flex align-items-center justify-content-center py-2"
                    style="background:#fff;color:#00B14F;border:1px solid #00B14F;font-weight:600;min-width:44px;"
                    onclick="useMyLocation()" title="Use my location as starting point">
                    <i class="bi bi-crosshair"></i>
                </button>
                <button id="halteBtn" class="btn flex-grow-1 d-flex align-items-center justify-content-center py-2"
                    style="background:#ff8c00;color:#fff;border:none;font-weight:600;opacity:0.5;cursor:not-allowed;"
                    onclick="handleFindHalteClick()" title="Set a starting point first (click map or 'My Location')" disabled>
                    <i class="bi bi-bus-front-fill me-2"></i> Find Nearest Stop
                </button>
            </div>

            <div class="panel-tabs">
                <div class="tab-item active" onclick="switchTab('locations')" id="tabBtn-locations">
                    Stops <span class="badge-count ms-1" id="locCount">0</span>
                </div>
                <div class="tab-item" onclick="switchTab('routes')" id="tabBtn-routes">
                    Route Details
                </div>
            </div>
        </div>

        <div class="panel-body px-3 pb-3">

            <div id="tabPane-locations" class="tab-pane active">
                <div id="listContainer"></div>

                <div id="emptyState" class="text-center mt-4" style="font-size: 0.82rem;">
                    <div
                        style="width: 48px; height: 48px; border-radius: 14px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="bi bi-bus-front-fill" style="font-size: 1.3rem; color: var(--text-muted);"></i>
                    </div>
                    <p class="mb-1" style="font-weight: 600; color: var(--text-secondary);">No stops yet</p>
                    <p style="color: var(--text-muted); font-size: 0.75rem;">Click the map to set a starting point, then press "Find Nearest Stop"</p>
                </div>
            </div>

            <div id="tabPane-routes" class="tab-pane">

                <div id="routeResultCard" class="route-result-card mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="route-stat-box">
                            <div class="route-label"><i class="bi bi-rulers"></i> Distance</div>
                            <div class="route-value" id="resDistance">-</div>
                        </div>
                        <div class="route-divider"></div>
                        <div class="route-stat-box">
                            <div class="route-label"><i class="bi bi-stopwatch"></i> Duration</div>
                            <div class="route-value" id="resDuration">-</div>
                        </div>
                    </div>
                </div>

                <div id="segmentListContainer" style="display: none;">
                </div>

                <div id="routeEmptyState" class="text-center mt-5">
                    <div
                        style="width: 56px; height: 56px; border-radius: 16px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i class="bi bi-map" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                    </div>
                    <p style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">
                        No route yet</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Add locations then press Calculate</p>
                </div>

            </div>

        </div>
    </div>

    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-header-pro">
                    <div class="d-flex align-items-center gap-3">
                        <div
                            style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-book-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 1.05rem;">Features & Guide</h5>
                            <small style="opacity: 0.8; font-size: 0.75rem;">Everything you need to know</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body modal-body-pro">

                    <p class="text-uppercase fw-bold small mb-2 ms-1"
                        style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">Basic Controls</p>
                    <div class="bg-white p-3 rounded-3 border mb-4"
                        style="border-color: var(--border-light) !important;">
                        <div class="row g-3 text-center">
                            <div class="col-4 border-end">
                                <div
                                    style="width: 36px; height: 36px; border-radius: 10px; background: #fef2f2; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                </div>
                                <div class="small fw-bold text-dark">Add</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Click Map / Search</div>
                            </div>
                            <div class="col-4 border-end">
                                <div
                                    style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-arrows-move text-primary"></i>
                                </div>
                                <div class="small fw-bold text-dark">Move</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Drag Marker</div>
                            </div>
                            <div class="col-4">
                                <div
                                    style="width: 36px; height: 36px; border-radius: 10px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-x-circle text-secondary"></i>
                                </div>
                                <div class="small fw-bold text-dark">Remove</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Click 'X' in List</div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1"
                        style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">1. Optimization
                        Methods</p>
                    <div class="p-3 bg-white rounded-3 border mb-3"
                        style="border-color: var(--border-light) !important;">
                        <table class="table table-borderless table-sm small mb-0">
                            <thead class="border-bottom" style="color: var(--text-muted);">
                                <tr>
                                    <th class="pb-2">Feature</th>
                                    <th class="pb-2 text-center text-primary">Straight Line</th>
                                    <th class="pb-2 text-center text-success">Real Road</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);">Accuracy</td>
                                    <td class="py-2 text-center">Low (Flight)</td>
                                    <td class="py-2 text-center">High (Traffic)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);">Best For</td>
                                    <td class="py-2 text-center">Estimates</td>
                                    <td class="py-2 text-center">Delivery</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-2 pt-2 border-top d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-warning mt-1"></i>
                            <p class="small mb-0" style="font-size: 0.73rem; color: var(--text-secondary);">
                                <strong>Tip:</strong> Use "Straight Line" to list points quickly, then "Real Road" to
                                finalize.
                            </p>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1"
                        style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">2. Travel Modes</p>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2"
                                style="border-color: var(--border-light) !important;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-car-front-fill" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark">Car</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);">Standard Routes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2"
                                style="border-color: var(--border-light) !important;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-scooter" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark">Motorcycle</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);">Faster ETA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1"
                        style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">3. Calculation
                        Actions</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box"
                            style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-sign-turn-right-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Single Route (A&rarr;B)</h6>
                            <p style="font-size: 0.78rem;">Direct path from the first to the second location only.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box"
                            style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Multi-Stop (Optimized)</h6>
                            <p style="font-size: 0.78rem;">Automatically <b>reorders</b> all stops to find the most
                                efficient path.</p>
                        </div>
                    </div>

                </div>

                <div class="modal-footer-pro text-center">
                    <button type="button" class="btn btn-action-primary w-100 py-2" data-bs-dismiss="modal">
                        Got it, thanks!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="map"></div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <!-- ============================================
         🆕 V2 MAP CONTROLS — fitur baru di AWS Location v2
         ============================================ -->
    <div id="v2Controls" class="v2-panel" style="
        position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%);
        background: rgba(255,255,255,0.97); padding: 10px 14px; border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.18); z-index: 5;
        display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
        font-family: Inter, system-ui, sans-serif; font-size: 0.78rem;">
        <span style="font-weight:700;color:#d23a3a;">v2 Maps</span>

        <select id="v2Style" class="form-select form-select-sm" style="width:auto;"
            title="Provider GrabMaps di ap-southeast-1 cuma support Standard & Monochrome. Satellite/Hybrid cuma di region US/EU.">
            <option value="Standard">Standard</option>
            <option value="Monochrome">Monochrome</option>
        </select>

        <select id="v2Color" class="form-select form-select-sm" style="width:auto;">
            <option value="Light">☀️ Light</option>
            <option value="Dark">🌙 Dark</option>
        </select>

        <select id="v2Politic" class="form-select form-select-sm" style="width:auto;" title="Political view (border style per negara)">
            <option value="">🌐 Default border</option>
            <option value="IND">🇮🇩 Indonesia view</option>
            <option value="MYS">🇲🇾 Malaysia view</option>
            <option value="SGP">🇸🇬 Singapore view</option>
            <option value="ARG">🇦🇷 Argentina view</option>
            <option value="MAR">🇲🇦 Morocco view</option>
        </select>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        /* =========================================
       1. CONFIGURATION & GLOBAL STATE
       =========================================
       ⚠️ MODE TEST — API key di-expose ke browser.
          JANGAN dipakai di production! Untuk produksi pakai versi di /transjakarta
          yang routing lewat proxy Laravel (lihat MapController.php).
       ========================================= */
        const AWS = {
            region: "{{ env('AWS_REGION') }}",
            apiKey: "{{ env('AWS_API_KEY') }}",
        };

        // Base URL — sudah include /v2 (AWS Location Service v2 butuh /v2/ di path
        // untuk Places, Routes, dan Maps).
        const PLACES = `https://places.geo.${AWS.region}.amazonaws.com/v2`;
        const ROUTES = `https://routes.geo.${AWS.region}.amazonaws.com/v2`;
        const KEY = `key=${AWS.apiKey}`;

        // UI TravelMode (Car/Motorcycle/Walking) → v2 TravelMode (Car/Scooter/Pedestrian)
        const V2_TRAVEL_MODE = {
            Car: 'Car',
            Motorcycle: 'Scooter',
            Walking: 'Pedestrian'
        };

        // 🆕 v2 Maps state — dipakai panel di bawah map untuk ganti style live
        const mapStyleState = {
            style: 'Standard', // Standard | Monochrome (di ap-southeast-1)
            colorScheme: 'Light', // Light | Dark
            politicalView: '' // '' | 'IND' | 'MYS' | dst (3-letter ISO)
        };

        // Bangun URL style v2 dari state.
        // ℹ️ Di ap-southeast-1, provider 'default' = GrabMaps yang cuma punya
        //    Standard & Monochrome (vector). Satellite/Hybrid raster cuma tersedia
        //    di region US/EU dengan provider HERE/Esri.
        function buildMapStyle() {
            const params = [KEY, `color-scheme=${mapStyleState.colorScheme}`];
            if (mapStyleState.politicalView) params.push(`political-view=${mapStyleState.politicalView}`);
            return `https://maps.geo.${AWS.region}.amazonaws.com/v2/styles/${mapStyleState.style}/descriptor?${params.join('&')}`;
        }

        // Cache GeoJSON rute terakhir, supaya bisa di-redraw setelah map.setStyle()
        // (setStyle akan menghapus semua source/layer custom)
        let lastRouteFeatureCollection = null;

        // Helper: cek user mau pakai jalan tol atau tidak (dari radio button UI)
        function isAvoidTolls() {
            const toll = document.querySelector('input[name="roadType"]:checked');
            return toll ? toll.value !== 'true' : true;
        }

        // Thin wrappers — semua call AWS pakai ini (POST/GET generic)
        async function awsPost(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body),
            });
        }
        async function awsGet(url) {
            return fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
        }

        let map = null;
        let markersData = [];
        let selectedMarkerId = null;
        let highlightMarkers = [];
        let markerIdCounter = 0;
        let currentRoutedHalteId = null; // halte yg lagi di-route (untuk recalc saat ganti mode)


        /* =========================================
           2. UI UTILITIES (Toast & Tabs)
           ========================================= */
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toastContainer');
            let bgClass, iconClass;

            switch (type) {
                case 'success':
                    bgClass = 'text-bg-success';
                    iconClass = 'bi-check-circle-fill';
                    break;
                case 'error':
                    bgClass = 'text-bg-danger';
                    iconClass = 'bi-exclamation-triangle-fill';
                    break;
                case 'warning':
                    bgClass = 'text-bg-warning';
                    iconClass = 'bi-exclamation-circle-fill';
                    break;
                default:
                    bgClass = 'text-bg-primary';
                    iconClass = 'bi-info-circle-fill';
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
            <div class="toast align-items-start ${bgClass} border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body text-white">
                        <i class="${iconClass} me-2 fs-5"></i>
                        <strong>${title}</strong>
                        <div class="mt-1 small">${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;

            const toastElement = wrapper.firstElementChild;
            container.appendChild(toastElement);

            requestAnimationFrame(() => {
                try {
                    const t = new bootstrap.Toast(toastElement, {
                        autohide: false
                    });
                    t.show();
                    setTimeout(() => {
                        if (toastElement && document.body.contains(toastElement)) t.hide();
                    }, 5000);
                    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
                } catch (error) {
                    console.error("Failed init toast:", error);
                    toastElement.remove();
                }
            });
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));

            document.getElementById(`tabBtn-${tabName}`).classList.add('active');
            document.getElementById(`tabPane-${tabName}`).classList.add('active');
        }


        /* =========================================
           3. MAP INITIALIZATION
           ========================================= */
        function initMap() {
            map = new maplibregl.Map({
                container: 'map',
                // AWS Location Service v2 — Maps Style Descriptor
                // URL dibangun dari mapStyleState (lihat panel v2 di pojok bawah)
                style: buildMapStyle(),
                center: [106.873687, -6.252809],
                zoom: 13,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.AttributionControl({
                customAttribution: '© Grab, © AWS'
            }), 'bottom-right');

            // Add Geolocation Control
            const geolocate = new maplibregl.GeolocateControl({
                positionOptions: {
                    enableHighAccuracy: true
                },
                trackUserLocation: true
            });
            map.addControl(geolocate, 'top-right');

            // Click map to add location
            map.on('click', async (e) => {
                const coords = [e.lngLat.lng, e.lngLat.lat];
                addLocation(coords, "Loading address...");
                const currentId = selectedMarkerId;

                try {
                    const addressName = await getPlaceNameByCoords(coords);
                    if (addressName) {
                        const item = markersData.find(m => m.id === currentId);
                        if (item) {
                            item.name = addressName;
                            item.marker.setPopup(new maplibregl.Popup({
                                offset: 25
                            }).setText(addressName));
                            renderLocationList();
                            showToast('Location Found', addressName, 'success');
                        }
                    } else {
                        const item = markersData.find(m => m.id === currentId);
                        if (item) {
                            item.name = `Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                            renderLocationList();
                        }
                    }
                } catch (error) {
                    console.error(error);
                }
            });
        }


        /* =========================================
           4. LOCATION MANAGEMENT (CRUD)
           ========================================= */
        // Bangun HTML popup halte (dipakai saat add & saat recalc mode).
        // distance: km (great-circle dari search-text ATAU road dari route-matrix)
        // duration: detik (null = belum ada data jalan asli)
        function buildHaltePopupHtml(id, name, distance, duration) {
            let info = '';
            if (distance !== null) {
                // "≈" = perkiraan garis lurus; tanpa "≈" = sudah road distance
                const prefix = duration === null ? '≈ ' : '';
                info = `${prefix}${distance.toFixed(2)} km`;
                if (duration !== null) info += ` • ${Math.round(duration / 60)} min`;
            }
            const distHtml = info ?
                `<div style="font-size:0.72rem;color:#6b7280;margin-top:2px;">${info}</div>` :
                '';
            return `<div style="font-family:Inter,sans-serif;font-size:0.82rem;min-width:160px;">
                <i class="bi bi-bus-front-fill" style="color:#ff8c00;"></i> <strong>${name}</strong>
                ${distHtml}
                <button type="button" onclick="routeFromOriginToHalte(${id})"
                        style="margin-top:8px;width:100%;padding:6px 10px;border:none;border-radius:6px;background:#ff8c00;color:#fff;font-weight:600;font-size:0.74rem;cursor:pointer;">
                    <i class="bi bi-signpost-2-fill"></i> Lihat Rute
                </button>
            </div>`;
        }

        function addLocation(coords, label, options = {}) {
            const {
                type = 'user', placeId = null, distance = null, duration = null
            } = options;
            const isHalte = type === 'halte';

            // console.log("HTML Popup untuk:", label, "Jarak:", distance);

            // New starting point → fresh state: clear all existing markers (user + stops) + route
            if (!isHalte) {
                markersData.forEach(m => m.marker.remove());
                markersData = [];
                selectedMarkerId = null;
                currentRoutedHalteId = null;
                removeRouteLayer();
                document.getElementById('routeResultCard').style.display = 'none';
                document.getElementById('segmentListContainer').style.display = 'none';
                document.getElementById('segmentListContainer').innerHTML = '';
                document.getElementById('routeEmptyState').style.display = 'block';
            }

            const id = ++markerIdCounter;
            const popupHtml = isHalte ?
                buildHaltePopupHtml(id, label, distance, duration) :
                `<div style="font-family:Inter,sans-serif;font-size:0.82rem;">${label}</div>`;
            const newMarker = new maplibregl.Marker({
                    color: isHalte ? '#ff8c00' : '#00B14F',
                    draggable: false
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setHTML(popupHtml))
                .addTo(map);

            if (!isHalte) newMarker.togglePopup();

            // Only update selection & flyTo for user markers (stops are added in batch)
            if (!isHalte) {
                selectedMarkerId = id;
                map.flyTo({
                    center: coords,
                    zoom: 15
                });
            }
            markersData.push({
                id,
                marker: newMarker,
                name: label,
                coords,
                type,
                placeId,
                distance,
                duration
            });
            renderLocationList();

            // New starting point → auto-find nearest stops
            if (!isHalte) {
                findNearbyHaltes(coords);
            }
        }

        function removeLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) item.marker.remove();
            markersData = markersData.filter(m => m.id !== id);
            if (currentRoutedHalteId === id) {
                currentRoutedHalteId = null;
                removeRouteLayer();
            }
            renderLocationList();
        }

        function clearAllMarkers() {
            markersData.forEach(m => m.marker.remove());
            markersData = [];
            selectedMarkerId = null;
            currentRoutedHalteId = null;

            removeRouteLayer();
            renderLocationList();

            // Reset Route UI
            document.getElementById('routeResultCard').style.display = 'none';
            document.getElementById('segmentListContainer').style.display = 'none';
            document.getElementById('segmentListContainer').innerHTML = '';
            document.getElementById('routeEmptyState').style.display = 'block';

            switchTab('locations');
            showToast('Reset', 'All markers and route cleared.', 'info');
        }

        function zoomToLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) {
                selectedMarkerId = id;
                map.flyTo({
                    center: item.coords,
                    zoom: 17
                });
                item.marker.togglePopup();
                renderLocationList();
            }
        }

        async function getPlaceNameByCoords(coords) {
            try {
                // AWS Location Service v2 — Reverse Geocode
                const response = await awsPost(`${PLACES}/reverse-geocode?${KEY}`, {
                    QueryPosition: coords,
                    MaxResults: 1,
                    Language: 'en',
                    AdditionalFeatures: ['TimeZone']
                });

                if (!response.ok) throw new Error('API Error');
                const data = await response.json();

                if (data.ResultItems && data.ResultItems.length > 0) {
                    const item = data.ResultItems[0];
                    return item.Title || (item.Address && item.Address.Label) || null;
                }
                return null;
            } catch (error) {
                console.error("Reverse geocode failed:", error);
                return null;
            }
        }

        // Use browser geolocation as starting point
        function useMyLocation() {
            if (!navigator.geolocation) {
                showToast('Not Supported', 'Browser does not support geolocation.', 'error');
                return;
            }
            showToast('Searching...', 'Getting your location...', 'info');
            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                        const coords = [pos.coords.longitude, pos.coords.latitude];
                        addLocation(coords, 'Loading address...');
                        const currentId = selectedMarkerId;
                        try {
                            const addressName = await getPlaceNameByCoords(coords);
                            const item = markersData.find(m => m.id === currentId);
                            if (!item) return;
                            if (addressName) {
                                item.name = addressName;
                                item.marker.setPopup(new maplibregl.Popup({
                                    offset: 25
                                }).setHTML(`<div style="font-family:Inter,sans-serif;font-size:0.82rem;">${addressName}</div>`));
                                renderLocationList();
                                showToast('Location Found', addressName, 'success');
                            } else {
                                item.name = `My Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                                renderLocationList();
                                showToast('Location Found', 'Starting point set.', 'success');
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    (err) => {
                        let msg = 'Failed to get location.';
                        if (err.code === 1) msg = 'Location permission denied. Enable it in browser settings.';
                        else if (err.code === 2) msg = 'Location unavailable (GPS / network).';
                        else if (err.code === 3) msg = 'Timeout getting location. Please try again.';
                        showToast('Failed', msg, 'error');
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
            );
        }

        // Header "Stop" button: use the currently selected marker, otherwise use map center
        function handleFindHalteClick() {
            let originCoords = null;
            if (selectedMarkerId) {
                const sel = markersData.find(m => m.id === selectedMarkerId);
                if (sel) originCoords = sel.coords;
            }
            if (!originCoords) {
                // Fallback: use latest user marker, or map center
                const lastUser = [...markersData].reverse().find(m => m.type !== 'halte');
                if (lastUser) {
                    originCoords = lastUser.coords;
                } else {
                    const center = map.getCenter();
                    originCoords = [center.lng, center.lat];
                }
            }
            findNearbyHaltes(originCoords);
        }

        // Find nearest TransJakarta stops, biased ke originCoords.
        // Hasil dari AWS sudah include Distance (great-circle, meter) — gak perlu route-matrix.
        async function findNearbyHaltes(originCoords) {
            showToast('Searching...', 'Finding nearby TransJakarta stops...', 'info');

            try {
                // AWS v2 SearchText — paling reliable untuk find places (bukan query refinement).
                // ⚠️ Exactly 1 of: BiasPosition / Filter.BoundingBox / Filter.Circle.
                //    Pakai Filter.Circle: Center jadi reference distance + batasi radius.
                const RADIUS_METERS = 2000; // batasi 2 km dari origin
                const res = await awsPost(`${PLACES}/search-text?${KEY}`, {
                    QueryText: 'halte TransJakarta',
                    MaxResults: 20, // max 20 untuk search-text
                    Language: 'id',
                    // BiasPosition: originCoords,
                    Filter: {
                        Circle: {
                            Center: originCoords,
                            Radius: RADIUS_METERS
                        },
                        IncludeCountries: ['IDN']
                    }
                });

                if (!res.ok) {
                    const errBody = await res.json().catch(() => ({}));
                    const awsMsg = errBody.Message || errBody.message || JSON.stringify(errBody).slice(0, 200);
                    console.error('AWS search-text error', res.status, errBody);
                    throw new Error(`HTTP ${res.status}: ${awsMsg}`);
                }
                const data = await res.json();
                console.log('search-text result:', data);

                if (!data.ResultItems || data.ResultItems.length === 0) {
                    showToast('None Found', 'No nearby TransJakarta stops found.', 'warning');
                    return;
                }

                // Filter & dedup:
                //   - item.Position WAJIB (Region/Street kadang gak punya Position)
                //   - skip halte yang sudah ada di peta
                const existingIds = new Set(
                    markersData.filter(m => m.type === 'halte' && m.placeId).map(m => m.placeId)
                );
                const candidates = data.ResultItems
                    .filter(it => it.Position && !(it.PlaceId && existingIds.has(it.PlaceId)))
                    .map(it => {
                        // Distance: prioritas dari AWS, fallback Haversine
                        const distKm = typeof it.Distance === 'number' ?
                            it.Distance / 1000 :
                            getDistanceFromLatLonInKm(originCoords[1], originCoords[0], it.Position[1], it.Position[0]);
                        return {
                            item: {
                                Title: it.Title,
                                Position: it.Position,
                                PlaceId: it.PlaceId,
                                Address: it.Address
                            },
                            distance: distKm
                        };
                    });

                if (candidates.length === 0) {
                    showToast('Already Added', 'All nearby stops are already in the list.', 'info');
                    return;
                }

                // Sort ascending by straight-line distance (sudah didapat dari AWS)
                candidates.sort((a, b) => (a.distance ?? Infinity) - (b.distance ?? Infinity));

                // Render ke peta
                const bounds = new maplibregl.LngLatBounds();
                bounds.extend(originCoords);

                candidates.forEach(({
                    item,
                    distance
                }) => {
                    const title = item.Title || (item.Address && item.Address.Label) || 'Stop';
                    addLocation(item.Position, title, {
                        type: 'halte',
                        placeId: item.PlaceId || null,
                        distance, // km (great-circle)
                        duration: null // gak ada duration tanpa route-matrix
                    });
                    bounds.extend(item.Position);
                });

                map.fitBounds(bounds, {
                    padding: 80,
                    duration: 800
                });
                showToast('Stops Found', `${candidates.length} stops added (≈ straight-line distance).`, 'success');

            } catch (err) {
                console.error('Stop search failed:', err);
                showToast('Error', err.message || 'Failed to find nearest stops.', 'error');
            }
        }


        /* =========================================
           5. ROUTING LOGIC
           ========================================= */

        // --- Single Route v2 (origin -> destination) ---
        async function calculateRoute(originCoords, destCoords, meta = {}) {
            if (!originCoords || !destCoords) {
                return showToast('Insufficient Data', 'Origin / destination incomplete.', 'warning');
            }
            const uiMode = document.querySelector('input[name="travelMode"]:checked').value;
            const travelMode = V2_TRAVEL_MODE[uiMode] || 'Car';

            showToast('Processing...', 'Calculating route (v2)...', 'info');

            try {
                const body = {
                    Origin: originCoords,
                    Destination: destCoords,
                    TravelMode: travelMode,
                    LegGeometryFormat: 'Simple',
                    InstructionsMeasurementSystem: 'Metric',
                    TravelStepType: 'TurnByTurn',
                    Locale: 'id',
                    // Avoid tolls — hanya untuk kendaraan, Pedestrian gak support
                    ...(travelMode !== 'Pedestrian' ? {
                        Avoid: {
                            TollRoads: isAvoidTolls()
                        }
                    } : {})
                };
                // Note: `Traffic` parameter is not supported in ap-southeast-1,
                // so we don't send it — AWS will use free-flow duration.

                // AWS Location Service v2 — Calculate Routes (single A → B)
                const response = await awsPost(`${ROUTES}/routes?${KEY}`, body);
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const awsMsg = data && (data.Message || data.message || data.error || JSON.stringify(data).slice(0, 200));
                    console.error('v2 Routes error:', response.status, data);
                    showToast('Error ' + response.status, awsMsg || 'Failed to call v2 Routes.', 'error');
                    return;
                }

                if (!data.Routes || data.Routes.length === 0) {
                    console.warn('v2 Routes response:', data);
                    showToast('Error', 'Route not found.', 'error');
                    return;
                }

                const route = data.Routes[0];

                // Combine geometry from all legs
                const allCoords = [];
                (route.Legs || []).forEach(leg => {
                    const line = leg.Geometry && leg.Geometry.LineString;
                    if (line && line.length) line.forEach(c => allCoords.push(c));
                });

                if (allCoords.length === 0) {
                    showToast('Error', 'Route geometry is empty.', 'error');
                    return;
                }

                drawRouteOnMap({
                    type: 'FeatureCollection',
                    features: [{
                        type: 'Feature',
                        properties: {
                            color: '#00B14F'
                        },
                        geometry: {
                            type: 'LineString',
                            coordinates: allCoords
                        }
                    }]
                });

                // Summary (v2: Distance in meters, Duration in seconds)
                const summary = route.Summary || {};
                const distM = summary.Distance || 0;
                const durS = summary.Duration || 0;
                const distKm = (distM / 1000);
                const durMin = Math.round(durS / 60);
                document.getElementById('resDistance').innerText = distKm.toFixed(2) + ' km';
                document.getElementById('resDuration').innerText = formatDuration(durS);

                // Render rich details (header, turn-by-turn, tolls, notices, major roads)
                // (originName / destName passed in via meta)
                renderRouteDetailsV2(route, data.Notices || [], meta);

                document.getElementById('routeEmptyState').style.display = 'none';
                document.getElementById('routeResultCard').style.display = 'block';
                document.getElementById('segmentListContainer').style.display = 'block';

                switchTab('routes');
                showToast('Success', `${distKm.toFixed(2)} km • ${durMin} min`, 'success');
            } catch (e) {
                console.error('calculateRoute v2 exception:', e);
                showToast('Error', e && e.message ? e.message : 'Failed to calculate route.', 'error');
            }
        }

        // --- v2 Turn icon mapping ---
        function turnIconFor(type) {
            const map = {
                Depart: 'bi-geo-alt-fill',
                Arrive: 'bi-flag-fill',
                Continue: 'bi-arrow-up',
                TurnLeft: 'bi-arrow-90deg-left',
                TurnRight: 'bi-arrow-90deg-right',
                TurnSharpLeft: 'bi-arrow-90deg-left',
                TurnSharpRight: 'bi-arrow-90deg-right',
                TurnSlightLeft: 'bi-arrow-up-left',
                TurnSlightRight: 'bi-arrow-up-right',
                KeepLeft: 'bi-signpost-split',
                KeepRight: 'bi-signpost-split',
                Merge: 'bi-sign-merge-left',
                Exit: 'bi-box-arrow-right',
                Roundabout: 'bi-arrow-clockwise',
                RoundaboutEnter: 'bi-arrow-clockwise',
                RoundaboutExit: 'bi-box-arrow-right',
                UTurn: 'bi-arrow-return-left',
                Ramp: 'bi-sign-turn-right',
                Ferry: 'bi-water'
            };
            return map[type] || 'bi-arrow-right-circle';
        }

        function formatStepDistance(m) {
            if (m == null) return '';
            return m >= 1000 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m';
        }

        // Try to extract direction (Left/Right/Straight) from various possible fields
        function extractStepDirection(step) {
            const sources = [
                step.TurnStepDetails,
                step.KeepStepDetails,
                step.ExitStepDetails,
                step.RampStepDetails
            ];
            for (const src of sources) {
                if (!src) continue;
                const v = src.SteeringDirection || src.Direction || src.Side;
                if (v) return v;
            }
            // Fallback: parse dari Type name (e.g. "TurnLeft", "RampRight", "ExitLeft")
            if (step.Type) {
                if (/Left/i.test(step.Type)) return 'Left';
                if (/Right/i.test(step.Type)) return 'Right';
                if (/Straight/i.test(step.Type)) return 'Straight';
            }
            return null;
        }

        function extractTurnIntensity(step) {
            const t = step.TurnStepDetails;
            if (!t) return null;
            return t.Intensity || t.TurnIntensity || null;
        }

        function extractRoadName(step) {
            const nr = step.NextRoad || step.CurrentRoad || {};
            if (nr.RoadName) {
                const rn = Array.isArray(nr.RoadName) ? nr.RoadName[0] : nr.RoadName;
                return rn && (rn.Value || rn);
            }
            if (nr.Name) return nr.Name;
            return null;
        }

        // Build step label when Instruction is empty (common in Pedestrian mode)
        function buildStepLabel(step) {
            if (step.Instruction && step.Instruction.trim()) return step.Instruction;

            const dir = extractStepDirection(step);
            const intensity = extractTurnIntensity(step);
            const baseType = (step.Type || '').replace(/(Left|Right|Straight)$/i, '') || step.Type;

            let label;
            switch (baseType) {
                case 'Depart':
                    label = 'Depart';
                    break;
                case 'Arrive':
                    label = 'Arrive at destination';
                    break;
                case 'Continue':
                    label = 'Continue straight';
                    break;
                case 'Turn':
                    if (dir === 'Left') label = intensity === 'Sharp' ? 'Sharp left turn' : intensity === 'Slight' ? 'Slight left turn' : 'Turn left';
                    else if (dir === 'Right') label = intensity === 'Sharp' ? 'Sharp right turn' : intensity === 'Slight' ? 'Slight right turn' : 'Turn right';
                    else label = 'Turn';
                    break;
                case 'Keep':
                    label = dir === 'Left' ? 'Keep left' : dir === 'Right' ? 'Keep right' : 'Keep lane';
                    break;
                case 'UTurn':
                    label = 'Make a U-turn';
                    break;
                case 'Roundabout':
                case 'RoundaboutEnter':
                    label = 'Enter roundabout';
                    break;
                case 'RoundaboutExit':
                    label = 'Exit roundabout';
                    break;
                case 'Merge':
                    label = 'Merge';
                    break;
                case 'Ramp':
                    label = dir === 'Left' ? 'Take left ramp' : dir === 'Right' ? 'Take right ramp' : 'Take ramp';
                    break;
                case 'Exit':
                    label = dir === 'Left' ? 'Exit left' : dir === 'Right' ? 'Exit right' : 'Exit';
                    break;
                default:
                    // Unknown type: still try to show direction
                    if (dir === 'Left') label = `${step.Type || 'Continue'} (left)`;
                    else if (dir === 'Right') label = `${step.Type || 'Continue'} (right)`;
                    else label = step.Type || 'Continue';
            }

            const roadName = extractRoadName(step);
            if (roadName && baseType !== 'Arrive' && baseType !== 'Depart') {
                label += ' onto ' + roadName;
            }
            return label;
        }

        // Icon mapping that is direction-aware
        function turnIconForStep(step) {
            const baseType = (step.Type || '').replace(/(Left|Right|Straight)$/i, '') || step.Type;
            const dir = extractStepDirection(step);
            if (baseType === 'Turn') {
                if (dir === 'Left') return 'bi-arrow-90deg-left';
                if (dir === 'Right') return 'bi-arrow-90deg-right';
            }
            if (baseType === 'Keep' || baseType === 'Ramp' || baseType === 'Exit') {
                if (dir === 'Left') return 'bi-arrow-up-left';
                if (dir === 'Right') return 'bi-arrow-up-right';
            }
            return turnIconFor(baseType);
        }

        // --- Render v2 route details (header, turn-by-turn, tolls, notices, road labels) ---
        function renderRouteDetailsV2(route, topLevelNotices, meta = {}) {
            const container = document.getElementById('segmentListContainer');
            container.innerHTML = '';
            container.style.display = 'block';

            // Header: From → To
            if (meta.originName || meta.destName) {
                const header = document.createElement('div');
                header.className = 'mb-3 p-3 rounded';
                header.style.cssText = 'background:#fff;border:1px solid #e5e7eb;font-size:0.82rem;';
                header.innerHTML = `
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:20px;height:20px;border-radius:50%;background:#00B14F;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.65rem;"><i class="bi bi-geo-alt-fill"></i></div>
                        <div style="color:#1f2937;font-weight:600;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${meta.originName || 'Starting point'}</div>
                    </div>
                    <div style="border-left:2px dotted #cbd5e1;height:14px;margin-left:9px;"></div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:20px;height:20px;border-radius:50%;background:#ff8c00;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.65rem;"><i class="bi bi-bus-front-fill"></i></div>
                        <div style="color:#1f2937;font-weight:600;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${meta.destName || 'Destination'}</div>
                    </div>
                `;
                container.appendChild(header);
            }

            // Major road labels ("via Jl. Sudirman")
            const labels = (route.MajorRoadLabels || [])
                .map(r => (r.RoadName && r.RoadName.Value) || '')
                .filter(Boolean);
            if (labels.length > 0) {
                const el = document.createElement('div');
                el.className = 'mb-3 p-2 rounded';
                el.style.cssText = 'background:#f0faf4;border:1px solid #d1f0dc;font-size:0.78rem;color:#166534;';
                el.innerHTML = `<i class="bi bi-signpost-2-fill"></i> via <strong>${labels.join(', ')}</strong>`;
                container.appendChild(el);
            }

            // Iterate legs
            (route.Legs || []).forEach((leg, legIdx) => {
                const details = leg.VehicleLegDetails || leg.PedestrianLegDetails || {};

                // Tolls
                const tolls = details.Tolls || [];
                if (tolls.length > 0) {
                    const systems = new Set();
                    tolls.forEach(t => (t.PaymentSites || []).forEach(p => p.Name && systems.add(p.Name)));
                    const el = document.createElement('div');
                    el.className = 'mb-2 p-2 rounded';
                    el.style.cssText = 'background:#fff4e5;border:1px solid #fde4c4;font-size:0.75rem;color:#9a3412;';
                    el.innerHTML = `<i class="bi bi-cash-coin"></i> <strong>${tolls.length} toll section${tolls.length > 1 ? 's' : ''}</strong>${systems.size ? ' · ' + [...systems].join(', ') : ''}`;
                    container.appendChild(el);
                }

                // Leg-level Notices
                (details.Notices || []).forEach(n => {
                    const el = document.createElement('div');
                    el.className = 'mb-2 p-2 rounded';
                    el.style.cssText = 'background:#fef2f2;border:1px solid #fecaca;font-size:0.72rem;color:#991b1b;';
                    el.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> ${n.Code || 'Notice'}${n.Details ? ' — ' + JSON.stringify(n.Details).slice(0, 80) : ''}`;
                    container.appendChild(el);
                });

                // Turn-by-turn
                const steps = details.TravelSteps || [];
                if (steps.length > 0) {
                    // Debug: log first step so we can inspect actual AWS response shape
                    console.log('[v2 Routes] sample step (leg ' + legIdx + '):', JSON.parse(JSON.stringify(steps[0])));
                    if (steps.length > 1) console.log('[v2 Routes] second step:', JSON.parse(JSON.stringify(steps[1])));
                    const header = document.createElement('div');
                    header.style.cssText = 'font-size:0.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;margin:10px 2px 6px;';
                    header.innerHTML = `<i class="bi bi-list-ol"></i> Directions${route.Legs.length > 1 ? ` (Leg ${legIdx + 1})` : ''}`;
                    container.appendChild(header);

                    steps.forEach((step) => {
                        const stepEl = document.createElement('div');
                        stepEl.className = 'd-flex align-items-start gap-2 mb-2 p-2 rounded';
                        stepEl.style.cssText = 'background:#f9fafb;font-size:0.78rem;border:1px solid #f1f5f9;';
                        const stepDist = formatStepDistance(step.Distance);
                        const stepDur = step.Duration ? Math.round(step.Duration / 60) + ' min' : '';
                        const instruction = buildStepLabel(step);
                        const meta = [stepDist, stepDur].filter(Boolean).join(' · ');
                        stepEl.innerHTML = `
                            <div style="width:28px;height:28px;border-radius:8px;background:#e6f7ec;color:#00B14F;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="${turnIconForStep(step)}"></i>
                            </div>
                            <div style="line-height:1.35;flex:1;min-width:0;">
                                <div style="color:#1f2937;">${instruction}</div>
                                ${meta ? `<div style="font-size:0.7rem;color:#6b7280;margin-top:2px;">${meta}</div>` : ''}
                            </div>
                        `;
                        container.appendChild(stepEl);
                    });
                }
            });

            // Top-level notices (route-wide)
            (topLevelNotices || []).forEach(n => {
                const el = document.createElement('div');
                el.className = 'mt-2 p-2 rounded';
                el.style.cssText = 'background:#fef9c3;border:1px solid #fde68a;font-size:0.72rem;color:#854d0e;';
                el.innerHTML = `<i class="bi bi-info-circle-fill"></i> ${n.Code || 'Info'}`;
                container.appendChild(el);
            });
        }

        // Re-run matrix for all existing stops with the current travel mode.
        // Updates distance/duration, re-sorts, and re-renders.
        async function recalculateHalteDistances() {
            const origin = markersData.find(m => m.type !== 'halte');
            if (!origin) return;
            const haltes = markersData.filter(m => m.type === 'halte');
            if (haltes.length === 0) return;

            const uiMode = document.querySelector('input[name="travelMode"]:checked').value;
            const travelMode = V2_TRAVEL_MODE[uiMode] || 'Car';

            try {
                // AWS Location Service v2 — Route Matrix (recalc setelah ganti mode)
                const matrixRes = await awsPost(`${ROUTES}/route-matrix?${KEY}`, {
                    Origins: [{
                        Position: origin.coords
                    }],
                    Destinations: haltes.map(h => ({
                        Position: h.coords
                    })),
                    TravelMode: travelMode,
                    RoutingBoundary: {
                        Unbounded: true
                    },
                    ...(travelMode !== 'Pedestrian' ? {
                        Avoid: {
                            TollRoads: isAvoidTolls()
                        }
                    } : {})
                });
                if (!matrixRes.ok) throw new Error('Matrix failed');
                const matrixData = await matrixRes.json();
                const matrixRow = matrixData.RouteMatrix && matrixData.RouteMatrix[0];

                // Update distance/duration + popup for each stop (v2: m → km)
                haltes.forEach((halte, idx) => {
                    const cell = matrixRow && matrixRow[idx];
                    const hasRoute = cell && typeof cell.Distance === 'number' && typeof cell.Duration === 'number';
                    halte.distance = hasRoute ? cell.Distance / 1000 : null;
                    halte.duration = hasRoute ? cell.Duration : null;

                    const popupHtml = buildHaltePopupHtml(halte.id, halte.name, halte.distance, halte.duration);
                    halte.marker.setPopup(new maplibregl.Popup({
                        offset: 25
                    }).setHTML(popupHtml));
                });

                // Re-sort: user markers tetap di depan, haltes sorted ascending
                const userMarkers = markersData.filter(m => m.type !== 'halte');
                const sortedHaltes = [...haltes].sort((a, b) => {
                    if (a.distance === null && b.distance === null) return 0;
                    if (a.distance === null) return 1;
                    if (b.distance === null) return -1;
                    return a.distance - b.distance;
                });
                markersData = [...userMarkers, ...sortedHaltes];

                renderLocationList();
            } catch (err) {
                console.error('Recalc stop distances failed:', err);
            }
        }

        // Route from the user's starting point to the clicked stop
        function routeFromOriginToHalte(halteId) {
            const origin = markersData.find(m => m.type !== 'halte');
            if (!origin) {
                showToast('No Starting Point', 'Click the map first to set a starting point.', 'warning');
                return;
            }
            const halte = markersData.find(m => m.id === halteId);
            if (!halte) return;

            selectedMarkerId = halteId;
            currentRoutedHalteId = halteId;
            renderLocationList();
            calculateRoute(origin.coords, halte.coords, {
                originName: origin.name,
                destName: halte.name
            });
        }

        async function calculateMultiRoute() {
            if (markersData.length < 2) return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            // Optimization mode (Fast / Precise)
            const optimizationMode = 'real';

            const colors = ['#00B14F', '#007bff', '#dc3545', '#fd7e14', '#6f42c1', '#e83e8c', '#17a2b8'];
            const MAX_STOPS = 25;

            // --- STEP 1: OPTIMIZATION LOGIC ---
            let optimizedData = [];

            if (optimizationMode === 'real') {
                // A. Mode Precise (Real Road)
                showToast('Optimizing...', 'Analyzing traffic & road restrictions...', 'info');
                try {
                    optimizedData = await optimizeMarkersOrderReal([...markersData]);
                } catch (e) {
                    console.error(e);
                    showToast('Warning', 'Optimization failed, fallback to default.', 'warning');
                    optimizedData = [...markersData];
                }
            } else {
                // B. Mode Fast (Straight Line)
                showToast('Optimizing...', 'Reordering stops (Straight Line)...', 'info');
                optimizedData = optimizeMarkersOrder([...markersData]);
            }

            // Update global data & UI list
            markersData = optimizedData;
            renderLocationList();
            const workingData = markersData;
            // ------------------------------------------

            let totalDistance = 0;
            let totalDuration = 0;
            let allRouteFeatures = [];
            let globalLegIndex = 0;
            let segmentDetails = [];

            showToast('Processing...', `Calculating final route path...`, 'info');

            try {
                // Loop batching (same as before)
                for (let i = 0; i < workingData.length - 1; i += (MAX_STOPS - 1)) {
                    const chunk = workingData.slice(i, i + MAX_STOPS);
                    const origin = chunk[0].coords;
                    const destination = chunk[chunk.length - 1].coords;
                    const waypoints = chunk.length > 2 ? chunk.slice(1, -1).map(m => m.coords) : [];

                    // AWS Location Service v2 — Calculate Routes (multi-stop)
                    // ⚠️ v2 beda dari v0:
                    //    - Field: Origin/Destination/Waypoints (array of {Position})
                    //    - TravelMode: Scooter/Pedestrian (bukan Motorcycle/Walking)
                    //    - Distance dalam METER, Duration dalam detik
                    //    - Response di data.Routes[0].Legs (bukan data.Legs)
                    const v2Mode = V2_TRAVEL_MODE[selectedMode] || 'Car';
                    const response = await awsPost(`${ROUTES}/routes?${KEY}`, {
                        Origin: origin,
                        Destination: destination,
                        Waypoints: waypoints.map(p => ({
                            Position: p
                        })),
                        TravelMode: v2Mode,
                        LegGeometryFormat: 'Simple',
                        InstructionsMeasurementSystem: 'Metric',
                        Locale: 'id',
                        ...(v2Mode !== 'Pedestrian' ? {
                            Avoid: {
                                TollRoads: isAvoidTolls()
                            }
                        } : {})
                    });

                    if (!response.ok) throw new Error(`Batch error`);
                    const data = await response.json();
                    const route = data.Routes && data.Routes[0];
                    if (!route) throw new Error('No route returned (v2)');

                    totalDistance += route.Summary.Distance / 1000; // m → km
                    totalDuration += route.Summary.Duration; // detik

                    if (route.Legs && route.Legs.length > 0) {
                        route.Legs.forEach((leg, legIndexInBatch) => {
                            if (leg.Geometry && leg.Geometry.LineString) {
                                const segmentColor = colors[globalLegIndex % colors.length];

                                allRouteFeatures.push({
                                    'type': 'Feature',
                                    'properties': {
                                        'color': segmentColor
                                    },
                                    'geometry': {
                                        'type': 'LineString',
                                        'coordinates': leg.Geometry.LineString
                                    }
                                });

                                const startNode = workingData[i + legIndexInBatch];
                                const endNode = workingData[i + legIndexInBatch + 1];

                                segmentDetails.push({
                                    from: startNode.name || 'Unknown Point',
                                    to: endNode.name || 'Unknown Point',
                                    distance: leg.Distance / 1000, // m → km
                                    duration: leg.Duration, // detik
                                    color: segmentColor,
                                    geometry: leg.Geometry.LineString
                                });

                                globalLegIndex++;
                            }
                        });
                    }
                }

                // Render Results
                if (allRouteFeatures.length > 0) {
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': allRouteFeatures
                    };
                    drawRouteOnMap(featureCollection);

                    const finalDist = totalDistance.toFixed(1) + ' km';
                    const finalDur = formatDuration(totalDuration);

                    document.getElementById('resDistance').innerText = finalDist;
                    document.getElementById('resDuration').innerText = finalDur;
                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    renderSegmentList(segmentDetails);
                    switchTab('routes');
                    showToast('Success', `Optimized route calculated!`, 'success');
                } else {
                    showToast('Error', 'Route geometry missing.', 'error');
                }

            } catch (error) {
                console.error(error);
                showToast('Error', 'Failed to calculate route.', 'error');
            }
        }

        // --- HELPER: DISTANCE BETWEEN 2 COORDS (Haversine) ---
        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            var R = 6371; // Earth radius in km
            var dLat = deg2rad(lat2 - lat1);
            var dLon = deg2rad(lon2 - lon1);
            var a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var d = R * c; // Distance in km
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // --- ORDER OPTIMIZATION (Nearest Neighbor) ---
        function optimizeMarkersOrder(originalData) {
            if (originalData.length <= 2) return originalData; // Only 2 points: no sorting needed

            // 1. Fixed starting point (cannot move)
            let sorted = [originalData[0]];

            // 2. Remaining unvisited points
            let remaining = originalData.slice(1);

            // 3. Loop to find the nearest
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1]; // Last fixed point
                let nearestIndex = -1;
                let minDistance = Infinity;

                // Compare distance to all remaining points
                remaining.forEach((point, index) => {
                    // Note: coords[1] = lat, coords[0] = lng
                    let dist = getDistanceFromLatLonInKm(
                        current.coords[1], current.coords[0],
                        point.coords[1], point.coords[0]
                    );

                    if (dist < minDistance) {
                        minDistance = dist;
                        nearestIndex = index;
                    }
                });

                // Move the nearest point into sorted array
                sorted.push(remaining[nearestIndex]);
                // Remove from remaining
                remaining.splice(nearestIndex, 1);
            }

            return sorted;
        }

        // --- HELPER: CALL AWS MATRIX v2 (REAL ROAD DISTANCE) ---
        // ⚠️ Caller harus tahu: response v2 punya Distance dalam METER & Duration (detik).
        async function getRouteMatrix(departure, destinations) {
            const response = await awsPost(`${ROUTES}/route-matrix?${KEY}`, {
                Origins: [{
                    Position: departure
                }],
                Destinations: destinations.map(p => ({
                    Position: p
                })),
                TravelMode: "Car",
                RoutingBoundary: {
                    Unbounded: true
                },
                Avoid: {
                    TollRoads: isAvoidTolls()
                }
            });

            if (!response.ok) throw new Error("Matrix API Error");
            return await response.json();
        }

        // --- REAL ORDER OPTIMIZATION (ASYNC / PRECISE) ---
        async function optimizeMarkersOrderReal(originalData) {
            if (originalData.length <= 2) return originalData;

            // 1. Start from the first point (fixed)
            let sorted = [originalData[0]];
            let remaining = originalData.slice(1);

            // 2. Loop until all points are ordered
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1];

                // Update toast so the user knows the progress
                showToast('Optimizing...', `Checking roads from Stop ${sorted.length}...`, 'info');

                // Prepare coordinates
                const currentCoords = current.coords;
                const destCoords = remaining.map(m => m.coords);

                try {
                    // CALL MATRIX API
                    const matrixData = await getRouteMatrix(currentCoords, destCoords);

                    // AWS Matrix returns "RouteMatrix[0]" (since 1 origin)
                    const results = matrixData.RouteMatrix[0];

                    let bestIndex = -1;
                    let minDuration = Infinity; // Find the fastest DURATION

                    results.forEach((res, idx) => {
                        if (res && res.Duration !== undefined) { // v2: Duration (detik)
                            if (res.Duration < minDuration) {
                                minDuration = res.Duration;
                                bestIndex = idx;
                            }
                        }
                    });

                    if (bestIndex !== -1) {
                        sorted.push(remaining[bestIndex]);
                        remaining.splice(bestIndex, 1);
                    } else {
                        // Fallback when API fails to calculate a route (e.g. different island)
                        sorted.push(remaining[0]);
                        remaining.shift();
                    }

                } catch (err) {
                    console.error("Matrix Optimization Failed:", err);
                    // On error (e.g. connection drop), return the rest as-is
                    return sorted.concat(remaining);
                }
            }
            return sorted;
        }


        /* =========================================
           6. VISUALIZATION & HELPERS
           ========================================= */
        function drawRouteOnMap(geoJsonFeatureCollection) {
            removeRouteLayer();
            lastRouteFeatureCollection = geoJsonFeatureCollection; // 🆕 cache buat redraw setelah ganti style

            map.addSource('routeSource', {
                'type': 'geojson',
                'data': geoJsonFeatureCollection
            });

            // Layer Outline (White)
            map.addLayer({
                'id': 'routeLayerOutline',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#ffffff',
                    'line-width': 6,
                    'line-opacity': 0.8
                }
            });

            // Main Layer (Colorful)
            map.addLayer({
                'id': 'routeLayer',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': ['get', 'color'],
                    'line-width': 4,
                    'line-opacity': 0.9
                }
            });

            const bounds = new maplibregl.LngLatBounds();
            geoJsonFeatureCollection.features.forEach(feature => {
                feature.geometry.coordinates.forEach(coord => bounds.extend(coord));
            });

            map.fitBounds(bounds, {
                padding: 50
            });
        }

        function removeRouteLayer() {
            // Clear highlight first
            clearSegmentHighlight();

            if (map.getLayer('routeLayer')) map.removeLayer('routeLayer');
            if (map.getLayer('routeLayerOutline')) map.removeLayer('routeLayerOutline');
            if (map.getSource('routeSource')) map.removeSource('routeSource');
            lastRouteFeatureCollection = null; // 🆕 batalkan cache rute
        }

        function zoomToSegment(coordinates) {
            if (!coordinates || coordinates.length === 0) return;
            const bounds = new maplibregl.LngLatBounds();
            coordinates.forEach(coord => bounds.extend(coord));
            map.fitBounds(bounds, {
                padding: 100,
                duration: 1000
            });
        }

        function highlightSegment(seg) {
            clearSegmentHighlight();

            // 1. Dim all routes
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.25);
                map.setPaintProperty('routeLayer', 'line-width', 3);
            }
            if (map.getLayer('routeLayerOutline')) {
                map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.15);
            }

            // 2. Add highlight layers for selected segment
            map.addSource('highlightSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {
                        color: seg.color
                    },
                    geometry: {
                        type: 'LineString',
                        coordinates: seg.geometry
                    }
                }
            });

            // Glow effect
            map.addLayer({
                id: 'highlightGlow',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 12,
                    'line-opacity': 0.2
                }
            });

            // White outline
            map.addLayer({
                id: 'highlightOutline',
                type: 'line',
                source: 'highlightSource',
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

            // Main highlight line
            map.addLayer({
                id: 'highlightLine',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 5,
                    'line-opacity': 1
                }
            });

            // 3. Add POI markers at start and end
            const startCoord = seg.geometry[0];
            const endCoord = seg.geometry[seg.geometry.length - 1];

            const startMarker = new maplibregl.Marker({
                    element: createPOIElement('A', seg.color)
                })
                .setLngLat(startCoord)
                .setPopup(new maplibregl.Popup({
                    offset: 20,
                    closeButton: false
                }).setHTML(
                    `<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">Start</strong><br>${seg.from}</div>`
                ))
                .addTo(map);

            const endMarker = new maplibregl.Marker({
                    element: createPOIElement('B', seg.color)
                })
                .setLngLat(endCoord)
                .setPopup(new maplibregl.Popup({
                    offset: 20,
                    closeButton: false
                }).setHTML(
                    `<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">End</strong><br>${seg.to}</div>`
                ))
                .addTo(map);

            startMarker.togglePopup();
            endMarker.togglePopup();
            highlightMarkers.push(startMarker, endMarker);

            // 4. Zoom to segment
            zoomToSegment(seg.geometry);
        }

        function clearSegmentHighlight() {
            // Remove highlight layers
            ['highlightLine', 'highlightOutline', 'highlightGlow'].forEach(id => {
                if (map.getLayer(id)) map.removeLayer(id);
            });
            if (map.getSource('highlightSource')) map.removeSource('highlightSource');

            // Restore main route opacity
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.9);
                map.setPaintProperty('routeLayer', 'line-width', 4);
            }
            if (map.getLayer('routeLayerOutline')) {
                map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.8);
            }

            // Remove POI markers
            highlightMarkers.forEach(m => m.remove());
            highlightMarkers = [];
        }

        function createPOIElement(label, color) {
            const el = document.createElement('div');
            el.className = 'poi-marker';
            el.style.backgroundColor = color;
            el.textContent = label;
            return el;
        }

        function formatDuration(seconds) {
            const totalMinutes = Math.round(seconds / 60);
            if (totalMinutes >= 60) {
                const hrs = Math.floor(totalMinutes / 60);
                const mins = totalMinutes % 60;
                return `${hrs} hr ${mins} min`;
            }
            return `${totalMinutes} min`;
        }


        /* =========================================
           7. UI RENDERING (LISTS)
           ========================================= */
        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const originContainer = document.getElementById('originCardContainer');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');
            const emptyState = document.getElementById('emptyState');

            panel.style.display = 'flex';

            const userMarkers = markersData.filter(m => m.type !== 'halte');
            const halteMarkers = markersData.filter(m => m.type === 'halte');

            countBadge.innerText = halteMarkers.length;

            // Toggle "Find Nearest Stop" button based on whether a starting point exists
            const halteBtn = document.getElementById('halteBtn');
            if (halteBtn) {
                const hasOrigin = userMarkers.length > 0;
                halteBtn.disabled = !hasOrigin;
                halteBtn.style.opacity = hasOrigin ? '1' : '0.5';
                halteBtn.style.cursor = hasOrigin ? 'pointer' : 'not-allowed';
                halteBtn.title = hasOrigin ?
                    'Find nearest TransJakarta stop' :
                    "Set a starting point first (click map or 'My Location')";
            }

            // Starting point (user markers) rendered above the button
            originContainer.innerHTML = '';
            userMarkers.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'location-item';
                if (item.id === selectedMarkerId) div.classList.add('active');
                div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;
                const lat = item.coords[1].toFixed(5);
                const lng = item.coords[0].toFixed(5);

                div.innerHTML = `
                    <div class="loc-info" onclick="zoomToLocation(${item.id})" style="cursor:pointer;">
                        <span class="loc-name text-truncate" title="${item.name}">
                            <span class="badge rounded-pill me-1" style="background:#e6f7ec;color:#00B14F;font-weight:600;font-size:0.62rem;"><i class="bi bi-geo-alt-fill"></i> Start</span>
                            ${item.name}
                        </span>
                        <span class="loc-coord"><i class="bi bi-crosshair"></i> ${lat}, ${lng}</span>
                    </div>
                    <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                originContainer.appendChild(div);
            });

            // Stops in the bottom list (already sorted ascending by route distance from origin)
            container.innerHTML = '';
            halteMarkers.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'location-item';
                if (item.id === selectedMarkerId) div.classList.add('active');
                div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;
                const lat = item.coords[1].toFixed(5);
                const lng = item.coords[0].toFixed(5);

                // Distance saja (great-circle dari search-text) → tampilkan dengan "≈"
                // Distance + duration (road dari route-matrix) → tampilkan tanpa "≈"
                let distanceLine = '';
                if (typeof item.distance === 'number') {
                    const hasDuration = typeof item.duration === 'number';
                    const prefix = hasDuration ? '' : '≈ ';
                    const durationPart = hasDuration ?
                        ` &middot; <i class="bi bi-stopwatch"></i> ${Math.round(item.duration / 60)} min` :
                        '';
                    distanceLine = `<span class="loc-coord" style="color:#00B14F;font-weight:600;"><i class="bi bi-rulers"></i> ${prefix}${item.distance.toFixed(2)} km${durationPart}</span>`;
                }

                div.innerHTML = `
                    <div class="loc-info" onclick="routeFromOriginToHalte(${item.id})" style="cursor:pointer;">
                        <span class="loc-name text-truncate" title="${item.name}">
                            <span class="badge rounded-pill me-1" style="background:#fff4e5;color:#ff8c00;font-weight:600;font-size:0.62rem;"><i class="bi bi-bus-front-fill"></i> #${index + 1} Stop</span>
                            ${item.name}
                        </span>
                        <span class="loc-coord"><i class="bi bi-crosshair"></i> ${lat}, ${lng}</span>
                        ${distanceLine}
                        <span class="loc-coord" style="color:#ff8c00;"><i class="bi bi-sign-turn-right-fill"></i> Click to calculate route</span>
                    </div>
                    <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                container.appendChild(div);
            });

            // Empty state muncul kalau belum ada halte sama sekali
            if (halteMarkers.length === 0) {
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
            }
        }

        function renderSegmentList(details) {
            const container = document.getElementById('segmentListContainer');
            container.innerHTML = '';
            container.style.display = 'block';

            details.forEach((seg, index) => {
                const dist = seg.distance.toFixed(1) + ' km';
                const dur = formatDuration(seg.duration);

                const item = document.createElement('div');
                item.className = 'segment-card';
                item.style.cursor = 'pointer';

                item.onclick = () => {
                    const isActive = item.classList.contains('active-card');
                    document.querySelectorAll('.segment-card').forEach(el => el.classList.remove('active-card'));

                    if (isActive) {
                        clearSegmentHighlight();
                    } else {
                        item.classList.add('active-card');
                        highlightSegment(seg);
                    }
                };

                item.innerHTML = `
                <div class="segment-color-bar" style="background-color: ${seg.color};"></div>
                <div class="d-flex flex-column">
                    <div class="segment-title">
                        <span class="text-truncate" style="max-width: 240px;">
                            <span class="badge rounded-pill text-bg-light border me-1">${index + 1}</span>
                            ${seg.to}
                        </span>
                    </div>
                    <div class="segment-details">
                        <span><i class="bi bi-rulers segment-icon"></i> ${dist}</span>
                        <span class="border-start mx-2"></span>
                        <span><i class="bi bi-clock segment-icon"></i> ${dur}</span>
                    </div>
                    <div style="font-size: 0.7rem; color: #999; margin-top: 2px;">
                        From: ${seg.from}
                    </div>
                </div>
            `;
                container.appendChild(item);
            });
        }


        /* =========================================
           8. SEARCH FUNCTIONALITY
           ========================================= */
        const input = document.getElementById('searchInput');
        const list = document.getElementById('suggestionsList');

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        input.addEventListener('input', debounce(async (e) => {
            const query = e.target.value;
            if (query.length < 3) {
                list.classList.remove('show');
                return;
            }
            try {
                const SEARCH_RADIUS_METERS = 5000; // Contoh: Maksimal 2km dari bias
                const center = map.getCenter();
                // AWS Location Service v2 — Suggest (autocomplete)
                // ⚠️ Exactly 1 of: BiasPosition / Filter.BoundingBox / Filter.Circle.
                //    Pakai Filter.Circle: Center = map center, Radius = 50 km (max AWS).
                const res = await awsPost(`${PLACES}/search-text?${KEY}`, {
                    "QueryText": query,
                    "BiasPosition": [106.8233, -6.1920],
                    "Filter": {
                        "IncludeCountries": ["IDN"],
                        "IncludePlaceTypes": ["PointOfInterest"]
                    },
                    "MaxResults": 50,
                    "Language": "id",
                    "IntendedUse": "SingleUse"
                });

                const data = await res.json();
                console.log('Raw suggestions:', data.ResultItems);
                // PROSES PENYARINGAN (Agar tidak jauh-jauh)
                const filteredResults = data.ResultItems.filter(item => {
                    // Hanya simpan hasil yang jaraknya <= 2000 meter
                    return item.Distance <= SEARCH_RADIUS_METERS;
                });
                console.log(filteredResults);

                renderSuggestions(filteredResults);
            } catch (err) {
                console.error(err);
            }
        }, 300));

        function renderSuggestions(results) {
            list.innerHTML = '';
            if (!results || results.length === 0) {
                list.classList.remove('show');
                return;
            }
            results.forEach(item => {
                // Normalisasi: handle 2 bentuk response —
                //   Suggest v2:    { Title, Place: { PlaceId, Address, Position, Distance } }
                //   SearchText v2: { Title, PlaceId, Address, Position, Distance }
                const place = item.Place || item; // fallback ke item kalau gak ada .Place

                const placeId = place.PlaceId;
                if (!placeId) return; // Skip Query-type suggestion (no place to select)
                const title = item.Title || '';
                const addressLabel = (place.Address && place.Address.Label) || '';
                const showAddr = addressLabel && addressLabel !== title;

                // Distance: prioritas pakai field dari AWS, fallback hitung sendiri pakai Haversine
                let distanceKm = null;
                if (typeof place.Distance === 'number') {
                    distanceKm = place.Distance / 1000; // AWS kasih dalam meter
                } else if (place.Position && Array.isArray(place.Position)) {
                    const c = map.getCenter();
                    distanceKm = getDistanceFromLatLonInKm(c.lat, c.lng, place.Position[1], place.Position[0]);
                }
                let distanceBadge = '';
                if (distanceKm !== null) {
                    const label = distanceKm < 1 ?
                        `${Math.round(distanceKm * 1000)} m` :
                        `${distanceKm.toFixed(1)} km`;
                    distanceBadge = `<span class="suggestion-distance">≈ ${label}</span>`;
                }

                const li = document.createElement('li');
                li.className = 'suggestion-item';
                li.innerHTML = `
                    <i class="bi bi-geo-alt"></i>
                    <div class="suggestion-text">
                        <div class="suggestion-title-row">
                            <span class="suggestion-title">${title}</span>
                            ${distanceBadge}
                        </div>
                        ${showAddr ? `<div class="suggestion-address">${addressLabel}</div>` : ''}
                    </div>
                `;
                li.onclick = () => selectPlace(placeId, title);
                list.appendChild(li);
            });
            list.classList.add('show');
        }

        async function selectPlace(placeId, placeName) {
            list.classList.remove('show');
            input.value = '';

            try {
                // AWS Location Service v2 — Get Place (detail by PlaceId)
                const url = `${PLACES}/place/${encodeURIComponent(placeId)}?${KEY}&additional-features=TimeZone&language=en`;
                const res = await awsGet(url);
                const data = await res.json();
                const title = data.Title || (data.Address && data.Address.Label) || placeName;
                addLocation(data.Position, title);
                showToast('Added', title, 'success');
            } catch (err) {
                showToast('Failed', 'Cannot fetch location', 'error');
            }
        }

        async function handleManualSearch() {
            const query = input.value;
            if (!query) return showToast('Empty Search', 'Enter a keyword.', 'warning');
            list.classList.remove('show');

            try {
                const center = map.getCenter();
                // AWS Location Service v2 — Search Text (manual search)
                const res = await awsPost(`${PLACES}/search-text?${KEY}`, {
                    QueryText: query,
                    MaxResults: 1,
                    BiasPosition: [center.lng, center.lat],
                    AdditionalFeatures: ['TimeZone']
                });
                const data = await res.json();

                if (data.ResultItems && data.ResultItems.length > 0) {
                    const item = data.ResultItems[0];
                    const title = item.Title || (item.Address && item.Address.Label) || query;
                    addLocation(item.Position, title);
                    showToast('Found', title, 'success');
                } else {
                    showToast('Not Found', 'Try another keyword.', 'warning');
                }
            } catch (err) {
                showToast('Error', 'API search failed.', 'error');
            }
        }

        /* =========================================
           9. INITIALIZATION & EVENTS
           ========================================= */

        function setupEventListeners() {
            // 1. Close suggestion list when clicking outside
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.remove('show');
                }
            });

            // 2. Handle Enter key on Search Input
            input.addEventListener("keypress", (event) => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    handleManualSearch();
                }
            });

            // 3. Travel mode change → refresh stop distances + recalc current route
            document.querySelectorAll('input[name="travelMode"]').forEach(radio => {
                radio.addEventListener('change', async () => {
                    await recalculateHalteDistances();
                    if (currentRoutedHalteId !== null &&
                        markersData.find(m => m.id === currentRoutedHalteId)) {
                        routeFromOriginToHalte(currentRoutedHalteId);
                    }
                });
            });
        }

        // --- MORE MENU ---
        function toggleMoreMenu() {
            const dropdown = document.getElementById('moreMenuDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.more-menu-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('moreMenuDropdown').classList.remove('show');
            }
        });

        /* =========================================
          🆕 V2 MAP CONTROLS — wire up panel di pojok bawah
           ========================================= */
        function applyMapStyle() {
            const newStyle = buildMapStyle(); // bisa string URL (vector) atau object (raster)
            // setStyle akan menghapus semua source/layer custom (route, dll)
            // Setelah style baru ter-load, kita re-draw rute dari cache
            map.once('style.load', () => {
                if (lastRouteFeatureCollection) {
                    drawRouteOnMap(lastRouteFeatureCollection);
                }
            });
            map.setStyle(newStyle);
            applyDarkUI(); // ikutin colorScheme

            const supportsColor = mapStyleState.style === 'Standard' || mapStyleState.style === 'Monochrome';
            const parts = [mapStyleState.style];
            if (supportsColor) parts.push(mapStyleState.colorScheme);
            if (mapStyleState.politicalView) parts.push(mapStyleState.politicalView);
            showToast('Style Updated', parts.join(' · '), 'info');
        }

        // 🌙 Toggle dark UI mengikuti mapStyleState.colorScheme
        function applyDarkUI() {
            document.body.classList.toggle('dark-ui', mapStyleState.colorScheme === 'Dark');
        }

        function setupV2Controls() {
            const styleSel = document.getElementById('v2Style');
            const colorSel = document.getElementById('v2Color');
            const politicSel = document.getElementById('v2Politic');

            styleSel.addEventListener('change', e => {
                mapStyleState.style = e.target.value;
                applyMapStyle();
            });
            colorSel.addEventListener('change', e => {
                mapStyleState.colorScheme = e.target.value;
                applyMapStyle();
            });
            politicSel.addEventListener('change', e => {
                mapStyleState.politicalView = e.target.value;
                applyMapStyle();
            });
        }

        // --- MAIN BOOTSTRAP ---
        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            setupEventListeners();
            setupV2Controls(); // 🆕
            applyDarkUI(); // 🌙 sync UI ke colorScheme awal
            // Show the panel from the start so "My Location" & "Find Stop" buttons are clickable immediately
            document.getElementById('locationsPanel').style.display = 'flex';
        });
    </script>
</body>

</html>