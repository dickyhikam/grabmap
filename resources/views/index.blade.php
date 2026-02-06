<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AWS Grab Maps - Location List</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />

    <style>
        :root {
            /* Colors */
            --grab-green: #00B14F;
            --grab-green-hover: #009543;
            --text-color: #333;
            --bg-glass: rgba(255, 255, 255, 0.95);
            --bg-hover: #f0f0f0;
            --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.15);

            /* Layout & Sizes */
            --header-max-width: 600px;
            --header-radius: 50px;
            --btn-size: 40px;

            /* Z-Indices */
            --z-map: 1;
            --z-header: 1000;
            --z-panel: 1050;
            --z-toast: 9999;
        }

        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Segoe UI', sans-serif;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100vh;
            z-index: var(--z-map);
        }

        /* --- HEADER SEARCH --- */
        .floating-header {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: var(--header-max-width);
            z-index: var(--z-header);
            background: var(--bg-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--header-radius);
            box-shadow: var(--shadow-soft);
            padding: 10px 15px;
            display: flex;
            align-items: flex-start;
        }

        .logo-container {
            padding-top: 5px;
            padding-right: 15px;
        }

        .grab-logo {
            height: 28px;
            width: auto;
        }

        /* Search Wrapper */
        .search-wrapper {
            position: relative;
            flex-grow: 1;
            margin-right: 10px;
        }

        .search-input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 1rem;
            color: var(--text-color);
            padding: 5px 0;
        }

        /* Suggestions Dropdown */
        .suggestions-list {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
            display: none;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #eee;
        }

        .suggestions-list.show {
            display: block;
        }

        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }

        .suggestion-item:hover {
            background-color: #f7f7f7;
            color: var(--grab-green);
        }

        /* Buttons */
        .btn-circle {
            width: var(--btn-size);
            height: var(--btn-size);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: 0.2s;
        }

        .btn-grab {
            background-color: var(--grab-green);
            border-color: var(--grab-green);
            color: white;
        }

        .btn-grab:hover {
            background-color: var(--grab-green-hover);
            border-color: var(--grab-green-hover);
            color: white;
        }

        /* --- PANEL LIST LOKASI --- */
        .locations-panel {
            position: fixed;
            top: 100px;
            left: 20px;
            /* Left Position */
            width: 320px;
            max-height: calc(100vh - 120px);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            z-index: var(--z-panel);
            display: none;
            overflow: hidden;
            animation: slideInPanel 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideInPanel {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .panel-header {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Primary Action Button */
        .btn-action-primary {
            background: linear-gradient(135deg, #00B14F 0%, #009543 100%);
            color: white;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.25);
            position: relative;
            overflow: hidden;
        }

        .btn-action-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 177, 79, 0.35);
            color: white;
        }

        .btn-action-primary:active {
            transform: translateY(1px);
            box-shadow: 0 2px 5px rgba(0, 177, 79, 0.2);
        }

        /* Mode Switch */
        .mode-switch-container {
            background-color: #f1f3f5;
            padding: 4px;
            border-radius: 12px;
            display: flex;
            position: relative;
        }

        .btn-mode-switch {
            background: transparent;
            color: #adb5bd;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            padding: 8px;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-mode-switch:hover {
            color: var(--grab-green);
        }

        .btn-check:checked+.btn-mode-switch {
            background-color: white;
            color: var(--grab-green);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: scale(1.02);
        }

        /* Reset Button */
        .btn-reset-minimal {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            background: white;
            border: 1px solid #e9ecef;
            padding: 5px 12px;
            border-radius: 30px;
            transition: all 0.2s ease;
        }

        .btn-reset-minimal:hover {
            color: #dc3545;
            border-color: #dc3545;
            background: #fff5f5;
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.15);
        }

        .badge-count {
            background: var(--grab-green);
            color: white;
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 5px;
        }

        #listContainer {
            overflow-y: auto;
            max-height: calc(100vh - 180px);
            padding: 10px;
        }

        #listContainer::-webkit-scrollbar {
            width: 6px;
        }

        #listContainer::-webkit-scrollbar-track {
            background: transparent;
        }

        #listContainer::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        #listContainer::-webkit-scrollbar-thumb:hover {
            background: #bbb;
        }

        /* Item Location */
        .location-item {
            background: white;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            border: 1px solid transparent;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .location-item.active {
            border: 2px solid var(--grab-green);
            background-color: #f0fdf4;
            transform: translateX(5px);
        }

        .location-item.active .loc-coord i {
            color: var(--grab-green);
        }

        .location-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--grab-green);
            opacity: 0;
            transition: 0.2s;
        }

        .location-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-color: var(--grab-green);
        }

        .location-item:hover::before {
            opacity: 1;
        }

        .loc-info {
            flex-grow: 1;
            padding-left: 8px;
            padding-right: 10px;
        }

        .loc-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: #222;
            display: block;
            margin-bottom: 2px;
            white-space: normal;
            word-wrap: break-word;
        }

        .loc-coord {
            font-size: 0.75rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-delete-item {
            color: #999;
            background: #f8f9fa;
            border: 1px solid #eee;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .btn-delete-item:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        /* Route Result Card */
        .route-result-card {
            background: #f0fdf4;
            border: 1px dashed var(--grab-green);
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .route-stat-box {
            text-align: center;
            flex: 1;
        }

        .route-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .route-value {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--grab-green);
        }

        .route-divider {
            width: 1px;
            background-color: #d1fae5;
            margin: 0 10px;
        }

        .toast-container {
            z-index: var(--z-toast) !important;
        }

        .maplibregl-ctrl-top-left {
            margin-top: 80px;
        }
    </style>

    <style>
        /* Tombol Secondary (Multi Route) - Outline Style */
        .btn-action-secondary {
            background: transparent;
            color: var(--grab-green);
            border: 2px solid var(--grab-green);
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            border-radius: 12px;
            padding: 10px;
            transition: all 0.2s ease;
            margin-top: 10px;
            /* Jarak dari tombol utama */
        }

        .btn-action-secondary:hover {
            background: #f0fdf4;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 177, 79, 0.15);
        }

        .btn-action-secondary:active {
            transform: translateY(1px);
        }
    </style>
</head>

<body>

    <div class="floating-header">
        <div class="logo-container border-end">
            <img src="logo.png" alt="Grab Logo" class="grab-logo">
        </div>

        <div class="search-wrapper">
            <div class="flex-grow-1 px-3">
                <input type="text" class="search-input" placeholder="Search location here..." id="searchInput">
            </div>
            <ul class="suggestions-list" id="suggestionsList"></ul>
        </div>

        <button class="btn btn-circle btn-grab shadow-sm" type="button" onclick="handleManualSearch()">
            <i class="bi bi-search"></i>
        </button>
    </div>

    <div class="locations-panel" id="locationsPanel">
        <div class="panel-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 fw-bold text-dark d-flex align-items-center">
                    Location List
                    <span class="badge-count shadow-sm ms-2" id="locCount">0</span>
                </h6>
                <button class="btn-reset-minimal" onclick="clearAllMarkers()">
                    <i class="bi bi-trash3 me-1"></i> Reset
                </button>
            </div>

            <div class="mode-switch-container mb-3">
                <input type="radio" class="btn-check" name="travelMode" id="modeCar" value="Car" checked>
                <label class="btn-mode-switch flex-grow-1" for="modeCar">
                    <i class="bi bi-car-front-fill me-2"></i> Car
                </label>

                <input type="radio" class="btn-check" name="travelMode" id="modeBike" value="Motorcycle">
                <label class="btn-mode-switch flex-grow-1" for="modeBike">
                    <i class="bi bi-scooter me-2"></i> Motorcycle
                </label>
            </div>

            <button class="btn btn-action-primary w-100" onclick="calculateRoute()">
                <i class="bi bi-sign-turn-right-fill me-2"></i> Calculate Route (A &rarr; B)
            </button>

            <button class="btn btn-action-secondary w-100" onclick="calculateMultiRoute()">
                <i class="bi bi-diagram-3-fill me-2"></i> Multi-Stop Route
            </button>

            <div id="routeResultCard" class="route-result-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="route-stat-box">
                        <div class="route-label"><i class="bi bi-rulers"></i> Distance</div>
                        <div class="route-value" id="resDistance">-</div>
                    </div>
                    <div class="route-divider align-self-stretch"></div>
                    <div class="route-stat-box">
                        <div class="route-label"><i class="bi bi-stopwatch"></i> Duration</div>
                        <div class="route-value" id="resDuration">-</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="listContainer"></div>
    </div>

    <div id="map"></div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        // --- ENV CONFIGURATION ---
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const placeIndex = "{{ env('AWS_MAP_PLACE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";
        const routeCalculator = "{{ env('AWS_MAP_ROUTE') }}";

        let map = null;
        let markersData = [];
        let selectedMarkerId = null;

        // --- 1. TOAST ---
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

        // --- 2. INIT MAP ---
        function initMap() {
            if (!apiKey) return showToast('Error', 'API Key Missing', 'error');

            map = new maplibregl.Map({
                container: 'map',
                style: `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`,
                center: [106.8456, -6.2088],
                zoom: 13,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.AttributionControl({
                customAttribution: '© Grab, © AWS'
            }), 'bottom-right');

            // --- CLICK MAP TO ADD LOCATION ---
            map.on('click', async (e) => {
                const coords = [e.lngLat.lng, e.lngLat.lat];

                // Optimistic UI update
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

        // --- 3. ADD LOCATION & RENDER LIST ---
        function addLocation(coords, label) {
            const id = Date.now();

            const newMarker = new maplibregl.Marker({
                    color: '#00B14F',
                    draggable: true
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setText(label))
                .addTo(map);

            newMarker.togglePopup();

            // DRAG EVENT
            newMarker.on('dragend', async () => {
                const lngLat = newMarker.getLngLat();
                const updatedCoords = [lngLat.lng, lngLat.lat];

                const item = markersData.find(m => m.id === id);
                if (item) {
                    item.coords = updatedCoords;
                    showToast('Loading...', 'Finding new address...', 'info');

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
                }
            });

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

        // --- 5. CALCULATE ROUTE ---
        async function calculateRoute() {
            if (markersData.length < 2) return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');

            const origin = markersData[0].coords;
            const destination = markersData[1].coords;
            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            showToast('Processing...', 'Calculating single route...', 'info');

            try {
                const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalculator}/calculate/route?key=${apiKey}`;
                const body = {
                    DeparturePosition: origin,
                    DestinationPosition: destination,
                    TravelMode: selectedMode,
                    DistanceUnit: "Kilometers",
                    DepartNow: true,
                    IncludeLegGeometry: true
                };

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                if (!response.ok) throw new Error('Failed');
                const data = await response.json();

                if (data.Legs && data.Legs.length > 0 && data.Legs[0].Geometry) {

                    // BUAT FEATURE COLLECTION UNTUK SINGLE ROUTE (Warna Hijau Grab Saja)
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': [{
                            'type': 'Feature',
                            'properties': {
                                'color': '#00B14F'
                            }, // Satu warna
                            'geometry': {
                                'type': 'LineString',
                                'coordinates': data.Legs[0].Geometry.LineString
                            }
                        }]
                    };

                    drawRouteOnMap(featureCollection);

                    // Summary UI
                    const summary = data.Summary;
                    document.getElementById('resDistance').innerText = summary.Distance.toFixed(1) + ' km';
                    document.getElementById('resDuration').innerText = Math.round(summary.DurationSeconds / 60) + ' min';
                    document.getElementById('routeResultCard').style.display = 'block';

                } else {
                    showToast('Error', 'Path not found.', 'error');
                }

            } catch (e) {
                console.error(e);
                showToast('Error', 'Failed.', 'error');
            }
        }

        // --- 6. LOGIC MULTI-STOP ROUTE (UNLIMITED STOPS / BATCHING) ---
        async function calculateMultiRoute() {
            if (markersData.length < 2) {
                return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');
            }

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            // --- PALET WARNA (Akan diulang jika titiknya banyak) ---
            const colors = [
                '#00B14F', // Hijau Grab (Segmen 1)
                '#007bff', // Biru (Segmen 2)
                '#dc3545', // Merah (Segmen 3)
                '#fd7e14', // Orange (Segmen 4)
                '#6f42c1', // Ungu (Segmen 5)
                '#e83e8c', // Pink (Segmen 6)
                '#17a2b8' // Cyan (Segmen 7)
            ];

            let totalDistance = 0;
            let totalDuration = 0;

            // Kita tampung Feature GeoJSON di sini
            let allRouteFeatures = [];
            let globalLegIndex = 0; // Untuk index warna

            const MAX_STOPS_PER_REQUEST = 25;

            showToast('Processing...', `Calculating colorful route...`, 'info');

            try {
                for (let i = 0; i < markersData.length - 1; i += (MAX_STOPS_PER_REQUEST - 1)) {
                    const chunk = markersData.slice(i, i + MAX_STOPS_PER_REQUEST);
                    const origin = chunk[0].coords;
                    const destination = chunk[chunk.length - 1].coords;
                    const waypoints = chunk.length > 2 ? chunk.slice(1, -1).map(m => m.coords) : [];

                    const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${routeCalculator}/calculate/route?key=${apiKey}`;

                    const body = {
                        DeparturePosition: origin,
                        DestinationPosition: destination,
                        WaypointPositions: waypoints,
                        TravelMode: selectedMode,
                        DistanceUnit: "Kilometers",
                        DepartNow: true,
                        IncludeLegGeometry: true
                    };

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });

                    if (!response.ok) throw new Error(`Failed at batch index ${i}`);
                    const data = await response.json();

                    // Aggregation Data
                    totalDistance += data.Summary.Distance;
                    totalDuration += data.Summary.DurationSeconds;

                    // --- PROSES WARNA PER SEGMENT ---
                    if (data.Legs && data.Legs.length > 0) {
                        data.Legs.forEach(leg => {
                            if (leg.Geometry && leg.Geometry.LineString) {

                                // Pilih warna berdasarkan urutan segmen
                                // Gunakan modulus (%) agar warna berulang jika segmen > jumlah warna
                                const segmentColor = colors[globalLegIndex % colors.length];

                                // Buat Feature GeoJSON untuk segmen ini
                                const feature = {
                                    'type': 'Feature',
                                    'properties': {
                                        'color': segmentColor, // Properti warna ini dibaca oleh map
                                        'description': `Leg ${globalLegIndex + 1}`
                                    },
                                    'geometry': {
                                        'type': 'LineString',
                                        'coordinates': leg.Geometry.LineString
                                    }
                                };

                                allRouteFeatures.push(feature);
                                globalLegIndex++; // Lanjut ke segmen berikutnya
                            }
                        });
                    }
                }

                // --- GAMBAR KE PETA ---
                if (allRouteFeatures.length > 0) {

                    // Bungkus jadi FeatureCollection
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': allRouteFeatures
                    };

                    drawRouteOnMap(featureCollection);

                    // Update Card UI
                    const finalDistance = totalDistance.toFixed(1) + ' km';
                    const totalMinutes = Math.round(totalDuration / 60);
                    let durationText = totalMinutes + ' min';
                    if (totalMinutes >= 60) {
                        const hrs = Math.floor(totalMinutes / 60);
                        const mins = totalMinutes % 60;
                        durationText = `${hrs} hr ${mins} min`;
                    }

                    document.getElementById('resDistance').innerText = finalDistance;
                    document.getElementById('resDuration').innerText = durationText;
                    document.getElementById('routeResultCard').style.display = 'block';

                    showToast('Success', `Multi-color route calculated!`, 'success');
                } else {
                    showToast('Error', 'Route geometry missing.', 'error');
                }

            } catch (error) {
                console.error(error);
                showToast('Error', 'Failed to calculate complex route.', 'error');
            }
        }

        function drawRouteOnMap(geoJsonFeatureCollection) {
            // Hapus layer lama jika ada
            removeRouteLayer();

            // Tambahkan Source
            map.addSource('routeSource', {
                'type': 'geojson',
                'data': geoJsonFeatureCollection
            });

            // 1. Layer Outline (Putih) - Agar garis lebih kontras dan rapi
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

            // 2. Layer Utama (Warna-Warni)
            map.addLayer({
                'id': 'routeLayer',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    // LOGIC WARNA: Ambil dari properti 'color' yang kita set di JS
                    'line-color': ['get', 'color'],
                    'line-width': 4,
                    'line-opacity': 0.9
                }
            });

            // --- SAYA MENGHAPUS LAYER 'routeArrows' DISINI AGAR TIDAK ERROR FONT ---

            // Fit Bounds (Zoom otomatis agar seluruh rute terlihat)
            const bounds = new maplibregl.LngLatBounds();

            // Loop untuk mencari batas koordinat (Bounding Box)
            geoJsonFeatureCollection.features.forEach(feature => {
                feature.geometry.coordinates.forEach(coord => {
                    bounds.extend(coord);
                });
            });

            // Zoom peta ke area rute
            map.fitBounds(bounds, {
                padding: 50
            });
        }

        function removeRouteLayer() {
            // if (map.getLayer('routeArrows')) map.removeLayer('routeArrows');
            if (map.getLayer('routeLayer')) map.removeLayer('routeLayer');
            if (map.getLayer('routeLayerOutline')) map.removeLayer('routeLayerOutline');
            if (map.getSource('routeSource')) map.removeSource('routeSource');
        }

        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');

            if (markersData.length > 0) {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }

            countBadge.innerText = markersData.length;
            container.innerHTML = '';

            markersData.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'location-item';

                if (item.id === selectedMarkerId) {
                    div.classList.add('active');
                }

                div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;

                const lat = item.coords[1].toFixed(5);
                const lng = item.coords[0].toFixed(5);

                div.innerHTML = `
                    <div class="loc-info" onclick="zoomToLocation(${item.id})">
                        <span class="loc-name text-truncate" title="${item.name}">${item.name}</span>
                        <span class="loc-coord">
                            <i class="bi bi-crosshair"></i> ${lat}, ${lng}
                        </span>
                    </div>
                    <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                container.appendChild(div);
            });
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
            showToast('Reset', 'All markers and route cleared.', 'info');
        }

        // --- SEARCH LOGIC ---
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
                const res = await fetch(`https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIndex}/search/suggestions?key=${apiKey}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Text: query,
                        MaxResults: 5,
                        Language: 'en' // Changed to EN
                    })
                });
                const data = await res.json();
                renderSuggestions(data.Results);
            } catch (err) {
                console.error(err);
            }
        }, 300));

        async function getPlaceNameByCoords(coords) {
            try {
                const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIndex}/search/position?key=${apiKey}`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Position: coords,
                        MaxResults: 1,
                        Language: 'en' // Changed to EN
                    })
                });

                if (!response.ok) throw new Error('AWS API Error');
                const data = await response.json();

                if (data.Results && data.Results.length > 0) {
                    return data.Results[0].Place.Label;
                }
                return null;

            } catch (error) {
                console.error("Reverse geocode failed:", error);
                return null;
            }
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
                const res = await fetch(`https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIndex}/places/${placeId}?key=${apiKey}`);
                const data = await res.json();
                addLocation(data.Place.Geometry.Point, data.Place.Label);
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
                const res = await fetch(`https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIndex}/search/text?key=${apiKey}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Text: query,
                        MaxResults: 1
                    })
                });
                const data = await res.json();

                if (data.Results && data.Results.length > 0) {
                    const place = data.Results[0].Place;
                    addLocation(place.Geometry.Point, place.Label);
                    showToast('Found', place.Label, 'success');
                } else {
                    showToast('Not Found', 'Try another keyword.', 'warning');
                }
            } catch (err) {
                showToast('Error', 'API search failed.', 'error');
            }
        }

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !list.contains(e.target)) list.classList.remove('show');
        });

        input.addEventListener("keypress", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                handleManualSearch();
            }
        });

        initMap();
    </script>
</body>

</html>