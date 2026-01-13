<!DOCTYPE html>
<html lang="id">

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
            /* Panel di atas Header */
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

        /* Search Wrapper: Input + Suggestion List */
        .search-wrapper {
            position: relative;
            flex-grow: 1;
            margin-right: 10px;
            /* Hapus margin nesting yang aneh */
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

        /* --- PANEL LIST LOKASI (FIXED) --- */
        .locations-panel {
            position: fixed;
            top: 100px;
            /* Di bawah header */
            right: 20px;
            /* Pojok Kanan */
            width: 300px;
            max-height: calc(100vh - 120px);
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow-soft);
            z-index: var(--z-panel);
            /* Pastikan ini 1050 */
            overflow-y: auto;
            display: none;
            /* Default sembunyi, muncul via JS */
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .panel-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .badge-count {
            background: var(--grab-green);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .location-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: 0.2s;
            cursor: pointer;
        }

        .location-item:hover {
            background-color: #f0ffff;
        }

        .loc-info {
            flex-grow: 1;
            padding-right: 10px;
        }

        .loc-name {
            font-weight: 600;
            font-size: 0.95rem;
            display: block;
        }

        .loc-coord {
            font-size: 0.75rem;
            color: #888;
        }

        .btn-delete-item {
            color: #dc3545;
            background: #fff5f5;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .btn-delete-item:hover {
            background: #dc3545;
            color: white;
        }

        .toast-container {
            z-index: var(--z-toast) !important;
        }

        .maplibregl-ctrl-top-left {
            margin-top: 80px;
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
                <input type="text" class="search-input" placeholder="Cari lokasi di sini..." id="searchInput">
            </div>

            <ul class="suggestions-list" id="suggestionsList"></ul>
        </div>

        <button class="btn btn-circle btn-grab shadow-sm" type="button" onclick="handleManualSearch()">
            <i class="bi bi-search"></i>
        </button>
    </div>

    <div class="locations-panel" id="locationsPanel">
        <div class="panel-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold"><i class="bi bi-list-check me-2"></i>Daftar Lokasi <span class="badge-count" id="locCount">0</span></h6>
            <button class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="clearAllMarkers()" style="font-size:0.85rem;">Hapus Semua</button>
        </div>
        <div id="listContainer"></div>
    </div>

    <div id="map"></div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        // --- KONFIGURASI ENV ---
        // GANTI INI DENGAN DATA DARI LARAVEL ENV ANDA
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const placeIndex = "{{ env('AWS_MAP_PLACE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";

        let map = null;
        let markersData = []; // Array penyimpanan data lokasi

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
            wrapper.innerHTML = `<div class="toast align-items-start ${bgClass} border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body text-white"><i class="${iconClass} me-2 fs-5"></i><strong>${title}</strong><div class="mt-1 small">${message}</div></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
            container.appendChild(wrapper.firstElementChild);
            const t = new bootstrap.Toast(wrapper.firstElementChild);
            t.show();
            setTimeout(() => {
                if (wrapper.firstElementChild) t.hide();
            }, 5000);
            wrapper.firstElementChild.addEventListener('hidden.bs.toast', () => wrapper.firstElementChild.remove());
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
            map.addControl(new maplibregl.NavigationControl(), 'top-left');
            map.addControl(new maplibregl.AttributionControl({
                customAttribution: '© Grab, © AWS'
            }), 'bottom-right');
        }

        // --- 3. CORE FUNCTION: ADD LOCATION & RENDER LIST ---
        function addLocation(coords, label) {
            // A. Buat Marker di Peta
            const newMarker = new maplibregl.Marker({
                    color: '#00B14F'
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setText(label))
                .addTo(map);
            newMarker.togglePopup();

            // B. Simpan ke Array
            const id = Date.now();
            markersData.push({
                id,
                marker: newMarker,
                name: label,
                coords
            });

            // C. Update Tampilan Panel
            renderLocationList();

            // D. Pindah Kamera
            map.flyTo({
                center: coords,
                zoom: 15
            });
        }

        // --- 4. FUNCTION RENDER LIST SIDEBAR ---
        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');

            // Logic: Jika ada data, TAMPILKAN panel. Jika tidak, SEMBUNYIKAN.
            if (markersData.length > 0) {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }

            countBadge.innerText = markersData.length;
            container.innerHTML = '';

            markersData.forEach((item) => {
                const div = document.createElement('div');
                div.className = 'location-item';
                const lat = item.coords[1].toFixed(4);
                const lng = item.coords[0].toFixed(4);

                div.innerHTML = `
                    <div class="loc-info" onclick="zoomToLocation(${item.id})">
                        <span class="loc-name text-truncate">${item.name}</span>
                        <span class="loc-coord"><i class="bi bi-geo-alt me-1"></i>${lat}, ${lng}</span>
                    </div>
                    <button class="btn-delete-item shadow-sm" onclick="removeLocation(${item.id})" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                container.appendChild(div);
            });
        }

        // --- ACTIONS ---
        function zoomToLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) {
                map.flyTo({
                    center: item.coords,
                    zoom: 17
                });
                item.marker.togglePopup();
            }
        }

        function removeLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) item.marker.remove(); // Hapus dari peta
            markersData = markersData.filter(m => m.id !== id); // Hapus dari array
            renderLocationList(); // Render ulang
        }

        function clearAllMarkers() {
            markersData.forEach(m => m.marker.remove());
            markersData = [];
            renderLocationList();
            showToast('Reset', 'Semua marker dihapus.', 'info');
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
                        Language: 'id'
                    })
                });
                const data = await res.json();
                renderSuggestions(data.Results);
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
            input.value = ''; // Kosongkan input

            try {
                const res = await fetch(`https://places.geo.${region}.amazonaws.com/places/v0/indexes/${placeIndex}/places/${placeId}?key=${apiKey}`);
                const data = await res.json();
                addLocation(data.Place.Geometry.Point, data.Place.Label);
                showToast('Ditambahkan', placeName, 'success');
            } catch (err) {
                showToast('Gagal', 'Tidak bisa mengambil lokasi', 'error');
            }
        }

        async function handleManualSearch() {
            const query = input.value;
            if (!query) return showToast('Pencarian Kosong', 'Masukkan kata kunci.', 'warning');
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
                    showToast('Ditemukan', place.Label, 'success');
                } else {
                    showToast('Tidak Ditemukan', 'Coba kata kunci lain.', 'warning');
                }
            } catch (err) {
                showToast('Error', 'Gagal pencarian API.', 'error');
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