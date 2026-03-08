<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Demo MAP Grab X {{ $company->name }} (AWS)</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/map-custom.css') }}">
</head>

<body>

    <div class="floating-header">
        <div class="logo-container">
            <img src="{{ asset('logo.png') }}" alt="Grab Logo" class="grab-logo">
            @if($company->logo_path)
            <span class="logo-x">x</span>
            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }} Logo" class="partner-logo">
            @endif
        </div>

        @if($features['search'])
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
        @endif
    </div>

    @if($features['route'])
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

            @php
            $routeModes = $features['route_settings']['modes'] ?? ['Car', 'Motorcycle'];
            $modeIcons = ['Car' => 'bi-car-front-fill', 'Motorcycle' => 'bi-bicycle', 'Walking' => 'bi-person-walking'];
            $firstMode = true;
            @endphp
            <div class="mode-switch-container mb-2">
                @foreach($routeModes as $mode)
                @if(isset($modeIcons[$mode]))
                <input type="radio" class="btn-check" name="travelMode" id="mode{{ $mode }}" value="{{ $mode }}" {{ $firstMode ? 'checked' : '' }}>
                <label class="btn-mode-switch flex-grow-1" for="mode{{ $mode }}">
                    <i class="bi {{ $modeIcons[$mode] }} me-1"></i> {{ $mode }}
                </label>
                @php $firstMode = false; @endphp
                @endif
                @endforeach
            </div>

            @if($features['route_matrix'])
            <div class="mode-switch-container mb-3">
                <input type="radio" class="btn-check" name="optMode" id="optFast" value="fast" checked>
                <label class="btn-mode-switch flex-grow-1" for="optFast" title="Sort by direct distance (Faster)">
                    <i class="bi bi-rulers me-1"></i> Straight Line
                </label>
                <input type="radio" class="btn-check" name="optMode" id="optPrecise" value="real">
                <label class="btn-mode-switch flex-grow-1" for="optPrecise" title="Sort by actual driving route (More Accurate)">
                    <i class="bi bi-sign-turn-slight-right-fill me-1"></i> Real Road
                </label>
            </div>
            @endif

            <!-- <div class="d-flex gap-2 mb-2">
                <button class="btn btn-outline-location flex-grow-1 d-flex align-items-center justify-content-center py-2"
                    onclick="useCurrentLocation()" title="Gunakan lokasi saat ini sebagai titik asal">
                    <i class="bi bi-crosshair2 me-2"></i> My Location
                </button>
            </div> -->
            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-action-primary flex-grow-1 d-flex align-items-center justify-content-center py-2"
                    onclick="calculateRoute()" title="Hitung Rute A ke B">
                    <i class="bi bi-sign-turn-right-fill me-2"></i> A&rarr;B
                </button>
                @if($features['route_matrix'])
                <button class="btn btn-action-secondary flex-grow-1 d-flex align-items-center justify-content-center py-2"
                    onclick="calculateMultiRoute()" title="Hitung Rute Multi-Stop">
                    <i class="bi bi-diagram-3-fill me-2"></i> Multi
                </button>
                @endif
            </div>

            <div class="panel-tabs">
                <div class="tab-item active" onclick="switchTab('locations')" id="tabBtn-locations">
                    Locations <span class="badge-count ms-1" id="locCount">0</span>
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
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="bi bi-pin-map-fill" style="font-size: 1.3rem; color: var(--text-muted);"></i>
                    </div>
                    <p class="mb-1" style="font-weight: 600; color: var(--text-secondary);">No locations yet</p>
                    <p style="color: var(--text-muted); font-size: 0.75rem;">Click on the map or search to add</p>
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

                <div id="segmentListContainer" style="display: none;"></div>

                <div id="routeEmptyState" class="text-center mt-5">
                    <div style="width: 56px; height: 56px; border-radius: 16px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i class="bi bi-map" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                    </div>
                    <p style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">No route yet</p>
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
                        <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-book-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 1.05rem;">Features & Guide</h5>
                            <small style="opacity: 0.8; font-size: 0.75rem;">Everything you need to know</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-pro">

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">Basic Controls</p>
                    <div class="bg-white p-3 rounded-3 border mb-4" style="border-color: var(--border-light) !important;">
                        <div class="row g-3 text-center">
                            <div class="col-4 border-end">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fef2f2; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                </div>
                                <div class="small fw-bold text-dark">Add</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Click Map / Search</div>
                            </div>
                            <div class="col-4 border-end">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-arrows-move text-primary"></i>
                                </div>
                                <div class="small fw-bold text-dark">Move</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Drag Marker</div>
                            </div>
                            <div class="col-4">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-x-circle text-secondary"></i>
                                </div>
                                <div class="small fw-bold text-dark">Remove</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Click 'X' in List</div>
                            </div>
                        </div>
                    </div>

                    @if($features['route_matrix'])
                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">1. Optimization Methods</p>
                    <div class="p-3 bg-white rounded-3 border mb-3" style="border-color: var(--border-light) !important;">
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
                                <strong>Tip:</strong> Use "Straight Line" to list points quickly, then "Real Road" to finalize.
                            </p>
                        </div>
                    </div>
                    @endif

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">Travel Modes</p>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-car-front-fill" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark">Car</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);">Standard Routes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-scooter" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark">Motorcycle</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);">Faster ETA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">Calculation Actions</p>
                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-sign-turn-right-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Single Route (A&rarr;B)</h6>
                            <p style="font-size: 0.78rem;">Direct path from the first to the second location only.</p>
                        </div>
                    </div>
                    @if($features['route_matrix'])
                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Multi-Stop (Optimized)</h6>
                            <p style="font-size: 0.78rem;">Automatically <b>reorders</b> all stops to find the most efficient path.</p>
                        </div>
                    </div>
                    @endif

                </div>
                <div class="modal-footer-pro text-center">
                    <button type="button" class="btn btn-action-primary w-100 py-2" data-bs-dismiss="modal">
                        Got it, thanks!
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div id="map"></div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        /* =========================================
           1. CONFIGURATION & GLOBAL STATE
           ========================================= */
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Feature flags passed from server
        const features = {
            search: !!"{{ $features['search'] ? '1' : '' }}",
            route: !!"{{ $features['route'] ? '1' : '' }}",
            reverse_geocode: !!"{{ $features['reverse_geocode'] ? '1' : '' }}",
            route_matrix: !!"{{ $features['route_matrix'] ? '1' : '' }}",
        };
        const searchLang = "{{ $features['search_settings']['language'] ?? 'id' }}";
        const geocodeLang = "{{ $features['reverse_geocode_settings']['language'] ?? 'id' }}";

        // All API calls go through Laravel proxy — API keys stay server-side
        function proxyPost(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            });
        }

        function proxyGet(url) {
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
                style: '/api/map-style',
                center: [106.8456, -6.2088],
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
                if (!features.route && !features.reverse_geocode) return;

                const coords = [e.lngLat.lng, e.lngLat.lat];

                if (features.route) {
                    // addLocation / replaceDestination handles max-2 logic internally
                    addLocation(coords, 'Loading address...');
                    const currentId = selectedMarkerId;

                    if (features.reverse_geocode) {
                        try {
                            const addressName = await getPlaceNameByCoords(coords);
                            const item = markersData.find(m => m.id === currentId);
                            if (item) {
                                item.name = addressName || `Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                                item.marker.setPopup(new maplibregl.Popup({
                                    offset: 25
                                }).setText(item.name));
                                renderLocationList();
                                if (addressName) showToast('Location Found', addressName, 'success');
                            }
                        } catch (error) {
                            console.error(error);
                        }
                    }
                }
            });
        }


        /* =========================================
           4. LOCATION MANAGEMENT (CRUD)
           ========================================= */
        // Marker colors: A=green, B=red/orange
        const MARKER_COLORS = ['#00B14F', '#E8341C'];

        function createMarkerForIndex(coords, label, index, id) {
            const color = MARKER_COLORS[index] || '#00B14F';
            const newMarker = new maplibregl.Marker({
                    color,
                    draggable: true
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setText(label))
                .addTo(map);

            newMarker.togglePopup();

            newMarker.on('dragend', async () => {
                const lngLat = newMarker.getLngLat();
                const updatedCoords = [lngLat.lng, lngLat.lat];
                const item = markersData.find(m => m.id === id);
                if (item) {
                    item.coords = updatedCoords;
                    showToast('Loading...', 'Finding new address...', 'info');
                    if (features.reverse_geocode) {
                        const newName = await getPlaceNameByCoords(updatedCoords);
                        if (newName) {
                            item.name = newName;
                            newMarker.setPopup(new maplibregl.Popup({
                                offset: 25
                            }).setText(newName));
                            renderLocationList();
                            showToast('Location Updated', newName, 'success');
                        } else {
                            showToast('Info', 'Location name not found.', 'warning');
                        }
                    } else {
                        item.name = `Location (${updatedCoords[1].toFixed(4)}, ${updatedCoords[0].toFixed(4)})`;
                        renderLocationList();
                    }
                }
            });
            return newMarker;
        }

        function addLocation(coords, label) {
            // Route only: max 2 markers (replace destination when full)
            // Route matrix: unlimited markers
            if (!features.route_matrix && markersData.length >= 2) {
                replaceDestination(coords, label);
                return;
            }
            const id = Date.now();
            const index = markersData.length;
            const newMarker = createMarkerForIndex(coords, label, index, id);

            selectedMarkerId = id;
            markersData.push({
                id,
                marker: newMarker,
                name: label,
                coords
            });
            renderLocationList();
            map.flyTo({
                center: coords,
                zoom: 15
            });
        }

        function replaceDestination(coords, label) {
            // Replace the destination (index 1) marker
            if (markersData.length < 1) return;

            if (markersData.length >= 2) {
                // Remove existing destination marker
                markersData[1].marker.remove();
                markersData = markersData.slice(0, 1);
            }

            const id = Date.now();
            const resolvedLabel = label || 'Loading address...';
            const newMarker = createMarkerForIndex(coords, resolvedLabel, 1, id);
            selectedMarkerId = id;
            markersData.push({
                id,
                marker: newMarker,
                name: resolvedLabel,
                coords
            });

            // Reverse geocode if needed and label not yet resolved
            if (!label && features.reverse_geocode) {
                getPlaceNameByCoords(coords).then(name => {
                    const item = markersData.find(m => m.id === id);
                    if (item && name) {
                        item.name = name;
                        newMarker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setText(name));
                        renderLocationList();
                    }
                });
            }

            renderLocationList();
            map.flyTo({
                center: coords,
                zoom: 15
            });
            showToast('Destination Updated', 'Titik tujuan diperbarui.', 'info');
        }

        async function useCurrentLocation() {
            showToast('Getting Location...', 'Mengambil posisi GPS saat ini...', 'info');
            try {
                const coords = await getCurrentPosition();

                // Save existing destination (index 1) if any
                let existingDest = null;
                if (markersData.length >= 2) {
                    existingDest = {
                        name: markersData[1].name,
                        coords: markersData[1].coords
                    };
                }

                // Remove all existing markers from map
                markersData.forEach(m => m.marker.remove());
                markersData = [];

                // Create new origin marker (A - green)
                const originId = Date.now();
                const originLabel = '📍 My Location (GPS)';
                const originMarker = createMarkerForIndex(coords, originLabel, 0, originId);
                markersData.push({
                    id: originId,
                    marker: originMarker,
                    name: originLabel,
                    coords
                });

                // Re-create destination marker if there was one (B - red)
                if (existingDest) {
                    const destId = Date.now() + 1;
                    const destMarker = createMarkerForIndex(existingDest.coords, existingDest.name, 1, destId);
                    markersData.push({
                        id: destId,
                        marker: destMarker,
                        name: existingDest.name,
                        coords: existingDest.coords
                    });
                }

                selectedMarkerId = originId;
                renderLocationList();
                map.flyTo({
                    center: coords,
                    zoom: 15
                });
                showToast('Origin Set ✓', 'Posisi GPS digunakan sebagai titik asal.', 'success');
            } catch (e) {
                showToast('GPS Error', 'Tidak dapat mengambil lokasi. Pastikan izin GPS aktif.', 'error');
            }
        }

        function removeLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) item.marker.remove();
            markersData = markersData.filter(m => m.id !== id);
            renderLocationList();
        }

        function clearAllMarkers() {
            markersData.forEach(m => m.marker.remove());
            markersData = [];
            selectedMarkerId = null;
            removeRouteLayer();
            renderLocationList();
            document.getElementById('routeResultCard').style.display = 'none';
            document.getElementById('segmentListContainer').style.display = 'none';
            document.getElementById('segmentListContainer').innerHTML = '';
            document.getElementById('routeEmptyState').style.display = 'block';
            switchTab('locations');
            showToast('Reset', 'All markers and route cleared.', 'info');
            document.getElementById('locationsPanel').style.display = 'none';
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
                const response = await proxyPost('/api/places/reverse', {
                    Position: coords,
                    MaxResults: 1,
                    Language: geocodeLang
                });
                if (!response.ok) throw new Error('API Error');
                const data = await response.json();
                if (data.Results && data.Results.length > 0) return data.Results[0].Place.Label;
                return null;
            } catch (error) {
                console.error("Reverse geocode failed:", error);
                return null;
            }
        }


        /* =========================================
           5. ROUTING LOGIC
           ========================================= */
        function getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) return reject(new Error('Geolocation not supported'));
                navigator.geolocation.getCurrentPosition(
                    pos => resolve([pos.coords.longitude, pos.coords.latitude]),
                    err => reject(err), {
                        enableHighAccuracy: true,
                        timeout: 8000
                    }
                );
            });
        }

        async function calculateRoute() {
            if (markersData.length < 1) {
                return showToast('Belum Ada Lokasi', 'Tambahkan minimal 1 titik tujuan di peta, atau tekan "My Location" untuk menentukan asal.', 'warning');
            }

            let origin, destination, usingGPS = false;

            if (markersData.length === 1) {
                // Only destination set — use GPS as origin
                showToast('Getting Location...', 'Mengambil posisi GPS sebagai titik asal...', 'info');
                try {
                    origin = await getCurrentPosition();
                    usingGPS = true;
                } catch (e) {
                    return showToast('GPS Error', 'Tidak dapat mengambil lokasi GPS. Tambahkan titik asal secara manual atau tekan "My Location".', 'error');
                }
                destination = markersData[0].coords;
            } else {
                origin = markersData[0].coords;
                destination = markersData[1].coords;
            }

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;
            showToast('Processing...', 'Menghitung rute terbaik...', 'info');

            try {
                const response = await proxyPost('/api/routes/calculate', {
                    DeparturePosition: origin,
                    DestinationPosition: destination,
                    TravelMode: selectedMode,
                    DistanceUnit: "Kilometers",
                    DepartNow: true,
                    IncludeLegGeometry: true
                });
                if (!response.ok) throw new Error('Failed');
                const data = await response.json();
                if (data.Legs && data.Legs.length > 0 && data.Legs[0].Geometry) {
                    const featureCollection = {
                        type: 'FeatureCollection',
                        features: [{
                            type: 'Feature',
                            properties: {
                                color: '#00B14F'
                            },
                            geometry: {
                                type: 'LineString',
                                coordinates: data.Legs[0].Geometry.LineString
                            }
                        }]
                    };
                    drawRouteOnMap(featureCollection);

                    // If GPS was used as origin, add temporary A marker
                    if (usingGPS) {
                        const gpsMark = new maplibregl.Marker({
                                color: '#00B14F'
                            })
                            .setLngLat(origin)
                            .setPopup(new maplibregl.Popup({
                                offset: 25
                            }).setText('📍 My Location'))
                            .addTo(map);
                        gpsMark.togglePopup();
                        highlightMarkers.push(gpsMark);
                    }

                    const summary = data.Summary;
                    document.getElementById('resDistance').innerText = summary.Distance.toFixed(1) + ' km';
                    document.getElementById('resDuration').innerText = formatDuration(summary.DurationSeconds);
                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'none';
                    switchTab('routes');
                    showToast('Rute Ditemukan! 🎉', `${summary.Distance.toFixed(1)} km · ${formatDuration(summary.DurationSeconds)}`, 'success');
                } else {
                    showToast('Error', 'Rute tidak ditemukan.', 'error');
                }
            } catch (e) {
                console.error(e);
                showToast('Error', 'Gagal menghitung rute.', 'error');
            }
        }

        async function calculateMultiRoute() {
            if (markersData.length < 2) return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');
            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;
            const optimizationMode = document.querySelector('input[name="optMode"]:checked').value;
            const colors = ['#00B14F', '#007bff', '#dc3545', '#fd7e14', '#6f42c1', '#e83e8c', '#17a2b8'];
            const MAX_STOPS = 25;

            let optimizedData = [];
            if (optimizationMode === 'real') {
                showToast('Optimizing...', 'Analyzing traffic & road restrictions...', 'info');
                try {
                    optimizedData = await optimizeMarkersOrderReal([...markersData]);
                } catch (e) {
                    console.error(e);
                    showToast('Warning', 'Optimization failed, fallback to default.', 'warning');
                    optimizedData = [...markersData];
                }
            } else {
                showToast('Optimizing...', 'Reordering stops (Straight Line)...', 'info');
                optimizedData = optimizeMarkersOrder([...markersData]);
            }

            markersData = optimizedData;
            renderLocationList();
            const workingData = markersData;

            let totalDistance = 0;
            let totalDuration = 0;
            let allRouteFeatures = [];
            let globalLegIndex = 0;
            let segmentDetails = [];
            showToast('Processing...', 'Calculating final route path...', 'info');

            try {
                for (let i = 0; i < workingData.length - 1; i += (MAX_STOPS - 1)) {
                    const chunk = workingData.slice(i, i + MAX_STOPS);
                    const origin = chunk[0].coords;
                    const destination = chunk[chunk.length - 1].coords;
                    const waypoints = chunk.length > 2 ? chunk.slice(1, -1).map(m => m.coords) : [];
                    const response = await proxyPost('/api/routes/calculate', {
                        DeparturePosition: origin,
                        DestinationPosition: destination,
                        WaypointPositions: waypoints,
                        TravelMode: selectedMode,
                        DistanceUnit: "Kilometers",
                        DepartNow: true,
                        IncludeLegGeometry: true
                    });
                    if (!response.ok) throw new Error('Batch error');
                    const data = await response.json();
                    totalDistance += data.Summary.Distance;
                    totalDuration += data.Summary.DurationSeconds;
                    if (data.Legs && data.Legs.length > 0) {
                        data.Legs.forEach((leg, legIndexInBatch) => {
                            if (leg.Geometry && leg.Geometry.LineString) {
                                const segmentColor = colors[globalLegIndex % colors.length];
                                allRouteFeatures.push({
                                    type: 'Feature',
                                    properties: {
                                        color: segmentColor
                                    },
                                    geometry: {
                                        type: 'LineString',
                                        coordinates: leg.Geometry.LineString
                                    }
                                });
                                segmentDetails.push({
                                    from: workingData[i + legIndexInBatch].name || 'Unknown Point',
                                    to: workingData[i + legIndexInBatch + 1].name || 'Unknown Point',
                                    distance: leg.Distance,
                                    duration: leg.DurationSeconds,
                                    color: segmentColor,
                                    geometry: leg.Geometry.LineString
                                });
                                globalLegIndex++;
                            }
                        });
                    }
                }
                if (allRouteFeatures.length > 0) {
                    drawRouteOnMap({
                        type: 'FeatureCollection',
                        features: allRouteFeatures
                    });
                    document.getElementById('resDistance').innerText = totalDistance.toFixed(1) + ' km';
                    document.getElementById('resDuration').innerText = formatDuration(totalDuration);
                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';
                    renderSegmentList(segmentDetails);
                    switchTab('routes');
                    showToast('Success', 'Optimized route calculated!', 'success');
                } else {
                    showToast('Error', 'Route geometry missing.', 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Error', 'Failed to calculate route.', 'error');
            }
        }

        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            var R = 6371;
            var dLat = deg2rad(lat2 - lat1),
                dLon = deg2rad(lon2 - lon1);
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        function optimizeMarkersOrder(originalData) {
            if (originalData.length <= 2) return originalData;
            let sorted = [originalData[0]];
            let remaining = originalData.slice(1);
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1];
                let nearestIndex = -1,
                    minDistance = Infinity;
                remaining.forEach((point, index) => {
                    let dist = getDistanceFromLatLonInKm(current.coords[1], current.coords[0], point.coords[1], point.coords[0]);
                    if (dist < minDistance) {
                        minDistance = dist;
                        nearestIndex = index;
                    }
                });
                sorted.push(remaining[nearestIndex]);
                remaining.splice(nearestIndex, 1);
            }
            return sorted;
        }

        async function getRouteMatrix(departure, destinations) {
            const response = await proxyPost('/api/routes/matrix', {
                DeparturePositions: [departure],
                DestinationPositions: destinations,
                TravelMode: "Car",
                DistanceUnit: "Kilometers"
            });
            if (!response.ok) throw new Error("Matrix API Error");
            return await response.json();
        }

        async function optimizeMarkersOrderReal(originalData) {
            if (originalData.length <= 2) return originalData;
            let sorted = [originalData[0]];
            let remaining = originalData.slice(1);
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1];
                showToast('Optimizing...', `Checking roads from Stop ${sorted.length}...`, 'info');
                const matrixData = await getRouteMatrix(current.coords, remaining.map(m => m.coords));
                const results = matrixData.RouteMatrix[0];
                let bestIndex = -1,
                    minDuration = Infinity;
                results.forEach((res, idx) => {
                    if (res && res.DurationSeconds !== undefined && res.DurationSeconds < minDuration) {
                        minDuration = res.DurationSeconds;
                        bestIndex = idx;
                    }
                });
                if (bestIndex !== -1) {
                    sorted.push(remaining[bestIndex]);
                    remaining.splice(bestIndex, 1);
                } else {
                    sorted.push(remaining[0]);
                    remaining.shift();
                }
            }
            return sorted;
        }


        /* =========================================
           6. VISUALIZATION & HELPERS
           ========================================= */
        function drawRouteOnMap(geoJsonFeatureCollection) {
            removeRouteLayer();
            map.addSource('routeSource', {
                type: 'geojson',
                data: geoJsonFeatureCollection
            });
            map.addLayer({
                id: 'routeLayerOutline',
                type: 'line',
                source: 'routeSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#ffffff',
                    'line-width': 6,
                    'line-opacity': 0.8
                }
            });
            map.addLayer({
                id: 'routeLayer',
                type: 'line',
                source: 'routeSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 4,
                    'line-opacity': 0.9
                }
            });
            const bounds = new maplibregl.LngLatBounds();
            geoJsonFeatureCollection.features.forEach(f => f.geometry.coordinates.forEach(c => bounds.extend(c)));
            map.fitBounds(bounds, {
                padding: 50
            });
        }

        function removeRouteLayer() {
            clearSegmentHighlight();
            if (map.getLayer('routeLayer')) map.removeLayer('routeLayer');
            if (map.getLayer('routeLayerOutline')) map.removeLayer('routeLayerOutline');
            if (map.getSource('routeSource')) map.removeSource('routeSource');
        }

        function highlightSegment(seg) {
            clearSegmentHighlight();
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.25);
                map.setPaintProperty('routeLayer', 'line-width', 3);
            }
            if (map.getLayer('routeLayerOutline')) map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.15);
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
            const startCoord = seg.geometry[0];
            const endCoord = seg.geometry[seg.geometry.length - 1];
            const startMarker = new maplibregl.Marker({
                element: createPOIElement('A', seg.color)
            }).setLngLat(startCoord).setPopup(new maplibregl.Popup({
                offset: 20,
                closeButton: false
            }).setHTML(`<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">Start</strong><br>${seg.from}</div>`)).addTo(map);
            const endMarker = new maplibregl.Marker({
                element: createPOIElement('B', seg.color)
            }).setLngLat(endCoord).setPopup(new maplibregl.Popup({
                offset: 20,
                closeButton: false
            }).setHTML(`<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">End</strong><br>${seg.to}</div>`)).addTo(map);
            startMarker.togglePopup();
            endMarker.togglePopup();
            highlightMarkers.push(startMarker, endMarker);
            const bounds = new maplibregl.LngLatBounds();
            seg.geometry.forEach(c => bounds.extend(c));
            map.fitBounds(bounds, {
                padding: 100,
                duration: 1000
            });
        }

        function clearSegmentHighlight() {
            ['highlightLine', 'highlightOutline', 'highlightGlow'].forEach(id => {
                if (map.getLayer(id)) map.removeLayer(id);
            });
            if (map.getSource('highlightSource')) map.removeSource('highlightSource');
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.9);
                map.setPaintProperty('routeLayer', 'line-width', 4);
            }
            if (map.getLayer('routeLayerOutline')) map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.8);
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
        const MARKER_LABELS = [{
                letter: 'A',
                role: 'Origin',
                color: '#00B14F',
                bg: '#e6f7ee'
            },
            {
                letter: 'B',
                role: 'Destination',
                color: '#E8341C',
                bg: '#fdecea'
            }
        ];

        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');
            const emptyState = document.getElementById('emptyState');
            panel.style.display = 'flex';
            countBadge.innerText = markersData.length;

            if (markersData.length === 0) {
                emptyState.style.display = 'block';
                container.innerHTML = '';
                return;
            }

            emptyState.style.display = 'none';
            container.innerHTML = '';

            markersData.forEach((item, index) => {
                const meta = MARKER_LABELS[index] || {
                    letter: String.fromCharCode(65 + index),
                    role: `Stop ${index + 1}`,
                    color: '#6f42c1',
                    bg: '#f3eeff'
                };
                const div = document.createElement('div');
                div.className = 'location-item';
                if (item.id === selectedMarkerId) div.classList.add('active');
                div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;
                const lat = item.coords[1].toFixed(5),
                    lng = item.coords[0].toFixed(5);
                div.innerHTML = `
                <div class="loc-marker-badge" style="background:${meta.bg}; color:${meta.color};">${meta.letter}</div>
                <div class="loc-info" onclick="zoomToLocation(${item.id})" style="flex:1;">
                    <span class="loc-role" style="font-size:0.65rem; font-weight:700; color:${meta.color}; text-transform:uppercase; letter-spacing:0.5px;">${meta.role}</span>
                    <span class="loc-name text-truncate d-block" title="${item.name}">${item.name}</span>
                    <span class="loc-coord"><i class="bi bi-crosshair"></i> ${lat}, ${lng}</span>
                </div>
                <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})">
                    <i class="bi bi-x-lg"></i>
                </button>`;
                container.appendChild(div);
            });

            // Show hint if only 1 marker
            if (markersData.length === 1) {
                const hint = document.createElement('div');
                hint.style.cssText = 'font-size:0.75rem; color:var(--text-muted); text-align:center; padding:10px 0 4px; display:flex; align-items:center; justify-content:center; gap:6px;';
                hint.innerHTML = `<i class="bi bi-info-circle"></i> Tap map to set destination, or press Calculate to use GPS as origin.`;
                container.appendChild(hint);
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
                    if (isActive) clearSegmentHighlight();
                    else {
                        item.classList.add('active-card');
                        highlightSegment(seg);
                    }
                };
                item.innerHTML = `
                <div class="segment-color-bar" style="background-color: ${seg.color};"></div>
                <div class="d-flex flex-column">
                    <div class="segment-title"><span class="text-truncate" style="max-width: 240px;"><span class="badge rounded-pill text-bg-light border me-1">${index + 1}</span>${seg.to}</span></div>
                    <div class="segment-details"><span><i class="bi bi-rulers segment-icon"></i> ${dist}</span><span class="border-start mx-2"></span><span><i class="bi bi-clock segment-icon"></i> ${dur}</span></div>
                    <div style="font-size: 0.7rem; color: #999; margin-top: 2px;">From: ${seg.from}</div>
                </div>`;
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

        if (input) {
            input.addEventListener('input', debounce(async (e) => {
                const query = e.target.value;
                if (query.length < 3) {
                    list.classList.remove('show');
                    return;
                }
                try {
                    const res = await proxyPost('/api/places/suggestions', {
                        Text: query,
                        MaxResults: 5,
                        Language: searchLang
                    });
                    const data = await res.json();
                    renderSuggestions(data.Results);
                } catch (err) {
                    console.error(err);
                }
            }, 300));
        }

        function renderSuggestions(results) {
            list.innerHTML = '';
            if (!results || results.length === 0) {
                list.classList.remove('show');
                return;
            }
            results.forEach(item => {
                const li = document.createElement('li');
                li.className = 'suggestion-item';
                li.innerHTML = `<i class="bi bi-geo-alt"></i> ${item.Text}`;
                li.onclick = () => selectPlace(item.PlaceId, item.Text);
                list.appendChild(li);
            });
            list.classList.add('show');
        }

        async function selectPlace(placeId, placeName) {
            list.classList.remove('show');
            input.value = '';
            try {
                const res = await proxyGet(`/api/places/${placeId}`);
                const data = await res.json();
                if (features.route) {
                    addLocation(data.Place.Geometry.Point, data.Place.Label);
                } else {
                    map.flyTo({
                        center: data.Place.Geometry.Point,
                        zoom: 15
                    });
                }
                showToast('Added', placeName, 'success');
            } catch (err) {
                showToast('Failed', 'Cannot fetch location', 'error');
            }
        }

        async function handleManualSearch() {
            const query = input.value;
            if (!query) return showToast('Empty Search', 'Enter a keyword.', 'warning');
            list.classList.remove('show');
            try {
                const res = await proxyPost('/api/places/search', {
                    Text: query,
                    MaxResults: 1
                });
                const data = await res.json();
                if (data.Results && data.Results.length > 0) {
                    const place = data.Results[0].Place;
                    if (features.route) {
                        addLocation(place.Geometry.Point, place.Label);
                    } else {
                        map.flyTo({
                            center: place.Geometry.Point,
                            zoom: 15
                        });
                    }
                    showToast('Found', place.Label, 'success');
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
            if (input) {
                document.addEventListener('click', (e) => {
                    if (!input.contains(e.target) && !list.contains(e.target)) list.classList.remove('show');
                });
                input.addEventListener('keypress', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        handleManualSearch();
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            setupEventListeners();
        });
    </script>
</body>

</html>