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

        /* 1. Primary Action (A->B) */
        .btn-action-primary {
            background: linear-gradient(135deg, #00B14F 0%, #009543 100%);
            color: white;
            border: none;
            font-weight: 700;
            font-size: 0.9rem;
            /* Font agak diperkecil dikit */
            letter-spacing: 0.3px;
            border-radius: 12px;
            /* padding dihapus dari CSS, diatur via class bootstrap py-2 */
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
        }

        /* 2. Secondary Action (Multi) - Hapus Margin Top */
        .btn-action-secondary {
            background: white;
            /* Ubah jadi putih biar bersih */
            color: var(--grab-green);
            border: 1px solid var(--grab-green);
            /* Border ditipisin jadi 1px */
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            border-radius: 12px;
            /* padding dihapus dari CSS, diatur via class bootstrap py-2 */
            transition: all 0.2s ease;
            margin-top: 0;
            /* PENTING: Hapus margin agar sejajar */
        }

        .btn-action-secondary:hover {
            background: #f0fdf4;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 177, 79, 0.15);
        }

        .btn-action-secondary:active {
            transform: translateY(1px);
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
            /* padding: 10px; */
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
        /* --- STYLE DETAIL LIST SEGMENT --- */
        #segmentListContainer {
            margin-top: 15px;
            max-height: 250px;
            /* Batasi tinggi agar bisa discroll jika panjang */
            overflow-y: auto;
            display: none;
            /* Default hide */
            padding-right: 5px;
            /* Space untuk scrollbar */
        }

        /* Scrollbar halus */
        #segmentListContainer::-webkit-scrollbar {
            width: 4px;
        }

        #segmentListContainer::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 4px;
        }

        .segment-card {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            position: relative;
            padding-left: 15px;
            /* Space untuk garis warna */
            transition: 0.2s;
        }

        .segment-card:hover {
            background: #f9f9f9;
        }

        /* Garis Warna Indikator di Kiri */
        .segment-color-bar {
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            /* Warna akan di-set lewat JS */
        }

        .segment-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .segment-details {
            font-size: 0.75rem;
            color: #666;
            display: flex;
            gap: 10px;
        }

        .segment-icon {
            font-size: 0.7rem;
            margin-right: 4px;
        }

        /* --- STYLE TAB NAVIGASI --- */
        .panel-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            /* margin-bottom: 10px; */
            margin-top: 15px;
        }

        .tab-item {
            flex: 1;
            text-align: center;
            padding: 10px 5px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #adb5bd;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .tab-item:hover {
            color: var(--grab-green-hover);
            background-color: #f8f9fa;
        }

        /* Tab Aktif (Hijau) */
        .tab-item.active {
            color: var(--grab-green);
            border-bottom: 3px solid var(--grab-green);
        }

        /* Konten Tab */
        .tab-pane {
            display: none;
            /* Default sembunyi */
            animation: fadeIn 0.2s ease;
        }

        .tab-pane.active {
            display: block;
            /* Muncul jika active */
        }

        /* Sesuaikan container list agar pas dengan tab */
        #listContainer,
        #segmentListContainer {
            max-height: calc(100vh - 350px);
            /* Sesuaikan tinggi agar scrollable */
            overflow-y: auto;
            padding-bottom: 20px;
        }

        /* Efek saat card dipilih */
        .segment-card.active-card {
            background-color: #f0fdf4;
            /* Hijau sangat muda */
            border-color: var(--grab-green);
            box-shadow: 0 0 0 1px var(--grab-green);
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
                    Location Manager
                </h6>
                <button class="btn-reset-minimal" onclick="clearAllMarkers()">
                    <i class="bi bi-trash3 me-1"></i> Reset
                </button>
            </div>

            <div class="mode-switch-container mb-2">
                <input type="radio" class="btn-check" name="travelMode" id="modeCar" value="Car" checked>
                <label class="btn-mode-switch flex-grow-1" for="modeCar"><i class="bi bi-car-front-fill me-2"></i> Car</label>

                <input type="radio" class="btn-check" name="travelMode" id="modeBike" value="Motorcycle">
                <label class="btn-mode-switch flex-grow-1" for="modeBike"><i class="bi bi-scooter me-2"></i> Motorcycle</label>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-action-primary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateRoute()" title="Hitung Rute A ke B">
                    <i class="bi bi-sign-turn-right-fill me-2"></i> A&rarr;B
                </button>

                <button class="btn btn-action-secondary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateMultiRoute()" title="Hitung Rute Multi-Stop">
                    <i class="bi bi-diagram-3-fill me-2"></i> Multi
                </button>
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

                <div id="emptyState" class="text-center text-muted mt-4" style="font-size: 0.85rem;">
                    <i class="bi bi-pin-map fs-3 d-block mb-2" style="color: #ddd;"></i>
                    Click map or search to add locations
                </div>
            </div>

            <div id="tabPane-routes" class="tab-pane">

                <div id="routeResultCard" class="route-result-card mb-3" style="display: none;">
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

                <div id="segmentListContainer" style="display: none;">
                </div>

                <div id="routeEmptyState" class="text-center text-muted mt-5">
                    <i class="bi bi-sign-turn-slight-right fs-1 d-block mb-3" style="color: #eee;"></i>
                    <p style="font-size: 0.9rem;">No route calculated yet.</p>
                    <small style="font-size: 0.75rem;">Add locations and click "Calculate Route"</small>
                </div>

            </div>

        </div>
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
                    // document.getElementById('routeResultCard').style.display = 'block';
                    // Sembunyikan Empty State
                    document.getElementById('routeEmptyState').style.display = 'none';

                    // Munculkan Card & List
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    // --- AUTO SWITCH TAB ---
                    switchTab('routes');

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
            if (markersData.length < 2) return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            // Palet Warna
            const colors = ['#00B14F', '#007bff', '#dc3545', '#fd7e14', '#6f42c1', '#e83e8c', '#17a2b8'];

            let totalDistance = 0;
            let totalDuration = 0;
            let allRouteFeatures = [];
            let globalLegIndex = 0;

            // ARRAY BARU UNTUK MENAMPUNG DETAIL TEXT
            let segmentDetails = [];

            const MAX_STOPS = 25;

            showToast('Processing...', `Calculating detailed route...`, 'info');

            try {
                for (let i = 0; i < markersData.length - 1; i += (MAX_STOPS - 1)) {
                    const chunk = markersData.slice(i, i + MAX_STOPS);
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
                    if (!response.ok) throw new Error(`Batch error`);
                    const data = await response.json();

                    totalDistance += data.Summary.Distance;
                    totalDuration += data.Summary.DurationSeconds;

                    if (data.Legs && data.Legs.length > 0) {
                        data.Legs.forEach((leg, legIndexInBatch) => {
                            if (leg.Geometry && leg.Geometry.LineString) {

                                // Tentukan Warna
                                const segmentColor = colors[globalLegIndex % colors.length];

                                // 1. Simpan Feature untuk Peta
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

                                // 2. SIMPAN DATA DETAIL UNTUK LIST
                                // Logic Nama: 
                                // Start point segmen ini adalah: markersData[i + legIndexInBatch]
                                // End point segmen ini adalah: markersData[i + legIndexInBatch + 1]

                                const startNode = markersData[i + legIndexInBatch];
                                const endNode = markersData[i + legIndexInBatch + 1];

                                segmentDetails.push({
                                    from: startNode.name || 'Unknown Point',
                                    to: endNode.name || 'Unknown Point',
                                    distance: leg.Distance, // Dulu: leg.Summary.Distance (Salah)
                                    duration: leg.DurationSeconds, // Dulu: leg.Summary.DurationSeconds (Salah)
                                    color: segmentColor,
                                    geometry: leg.Geometry.LineString
                                });

                                globalLegIndex++;
                            }
                        });
                    }
                }

                // --- RENDER HASIL ---
                if (allRouteFeatures.length > 0) {

                    // A. Gambar di Peta
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': allRouteFeatures
                    };
                    drawRouteOnMap(featureCollection);

                    // B. Update Summary Card
                    const finalDist = totalDistance.toFixed(1) + ' km';
                    const finalDur = formatDuration(totalDuration);

                    document.getElementById('resDistance').innerText = finalDist;
                    document.getElementById('resDuration').innerText = finalDur;

                    // Sembunyikan Empty State
                    document.getElementById('routeEmptyState').style.display = 'none';

                    // Munculkan Card & List
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    // C. RENDER DETAIL LIST (Fungsi Baru)
                    renderSegmentList(segmentDetails);

                    // --- AUTO SWITCH TAB ---
                    switchTab('routes');

                    showToast('Success', `Multi-stop route calculated!`, 'success');
                } else {
                    showToast('Error', 'Route geometry missing.', 'error');
                }

            } catch (error) {
                console.error(error);
                showToast('Error', 'Failed to calculate route.', 'error');
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
            const emptyState = document.getElementById('emptyState');

            // Tampilkan panel jika ada marker (opsional, atau biarkan panel selalu ada)
            panel.style.display = 'block';

            countBadge.innerText = markersData.length;

            // Tampilkan/Sembunyikan Empty State
            if (markersData.length === 0) {
                emptyState.style.display = 'block';
                container.innerHTML = '';
            } else {
                emptyState.style.display = 'none';
                // ... (kode loop markersData yang lama tetap sama) ...
                container.innerHTML = '';
                markersData.forEach((item, index) => {
                    // ... kode create element div location-item ...
                    // COPY PASTE LOGIC RENDER ITEM YANG LAMA DISINI
                    const div = document.createElement('div');
                    div.className = 'location-item';
                    if (item.id === selectedMarkerId) div.classList.add('active');

                    // (Animasi slideIn)
                    div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;

                    const lat = item.coords[1].toFixed(5);
                    const lng = item.coords[0].toFixed(5);

                    div.innerHTML = `
                            <div class="loc-info" onclick="zoomToLocation(${item.id})">
                                <span class="loc-name text-truncate" title="${item.name}">${item.name}</span>
                                <span class="loc-coord"><i class="bi bi-crosshair"></i> ${lat}, ${lng}</span>
                            </div>
                            <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        `;
                    container.appendChild(div);
                });
            }
        }

        // RENDER LIST DETAIL ---
        function renderSegmentList(details) {
            const container = document.getElementById('segmentListContainer');
            container.innerHTML = '';
            container.style.display = 'block';

            details.forEach((seg, index) => {
                const dist = seg.distance.toFixed(1) + ' km';
                const dur = formatDuration(seg.duration);

                const item = document.createElement('div');
                item.className = 'segment-card';

                // Agar kursor berubah jadi jari telunjuk saat hover
                item.style.cursor = 'pointer';

                // --- EVENT KLIK ---
                item.onclick = () => {
                    // 1. Zoom ke segmen ini
                    zoomToSegment(seg.geometry);

                    // 2. (Opsional) Highlight visual card yang aktif
                    document.querySelectorAll('.segment-card').forEach(el => el.classList.remove('active-card'));
                    item.classList.add('active-card');
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

        // Helper Format Waktu
        function formatDuration(seconds) {
            const totalMinutes = Math.round(seconds / 60);
            if (totalMinutes >= 60) {
                const hrs = Math.floor(totalMinutes / 60);
                const mins = totalMinutes % 60;
                return `${hrs} hr ${mins} min`;
            }
            return `${totalMinutes} min`;
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

        // Fungsi untuk zoom kamera ke segmen tertentu
        function zoomToSegment(coordinates) {
            if (!coordinates || coordinates.length === 0) return;

            // Buat batas kotak (Bounding Box) dari kumpulan koordinat segmen
            const bounds = new maplibregl.LngLatBounds();

            coordinates.forEach(coord => {
                bounds.extend(coord);
            });

            // Perintahkan map untuk fit ke bounds tersebut
            map.fitBounds(bounds, {
                padding: 100, // Memberi jarak bantalan agar tidak mepet pinggir layar
                duration: 1000 // Animasi zoom selama 1 detik
            });
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

            // --- RESET TAMPILAN ROUTE TAB ---
            // 1. Sembunyikan Hasil
            document.getElementById('routeResultCard').style.display = 'none';
            document.getElementById('segmentListContainer').style.display = 'none';
            document.getElementById('segmentListContainer').innerHTML = ''; // Bersihkan isi list

            // 2. Munculkan Empty State Rute Lagi
            document.getElementById('routeEmptyState').style.display = 'block';

            switchTab('locations');
            showToast('Reset', 'All markers and route cleared.', 'info');
        }

        // --- FUNGSI GANTI TAB ---
        function switchTab(tabName) {
            // 1. Reset semua tombol tab jadi tidak active
            document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
            // 2. Sembunyikan semua pane konten
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));

            // 3. Aktifkan yang dipilih
            document.getElementById(`tabBtn-${tabName}`).classList.add('active');
            document.getElementById(`tabPane-${tabName}`).classList.add('active');
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