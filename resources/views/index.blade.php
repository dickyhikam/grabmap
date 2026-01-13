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

        /* --- PANEL LIST LOKASI (MODERN STYLE) --- */
        .locations-panel {
            position: fixed;
            top: 100px;

            /* GANTI INI (Dari right jadi left) */
            left: 20px;
            /* right: 20px; HAPUS ATAU KOMENTAR BARIS INI */

            width: 320px;
            max-height: calc(100vh - 120px);

            /* Glassmorphism Effect */
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

        /* Animasi Muncul Panel */
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

        /* Header Panel */
        .panel-header {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* 3. Tombol Hitung Rute (Gradient & Glowing) */
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
            /* Glow hijau */
            position: relative;
            overflow: hidden;
        }

        .btn-action-primary:hover {
            transform: translateY(-2px);
            /* Efek naik saat hover */
            box-shadow: 0 6px 15px rgba(0, 177, 79, 0.35);
            color: white;
        }

        .btn-action-primary:active {
            transform: translateY(1px);
            /* Efek tekan */
            box-shadow: 0 2px 5px rgba(0, 177, 79, 0.2);
        }

        /* Style untuk Toggle Button Mode Kendaraan */
        .mode-switch-container {
            background-color: #f1f3f5;
            /* Abu-abu muda sebagai track */
            padding: 4px;
            border-radius: 12px;
            /* Pill shape */
            display: flex;
            position: relative;
        }

        .btn-mode-switch {
            background: transparent;
            color: #adb5bd;
            /* Teks abu-abu saat tidak aktif */
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

        /* Saat Radio Checked -> Label berubah jadi Putih & Shadow */
        .btn-check:checked+.btn-mode-switch {
            background-color: white;
            color: var(--grab-green);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            /* Shadow halus */
            transform: scale(1.02);
            /* Sedikit membesar */
        }

        /* Tombol Hapus Semua */
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
            /* Merah */
            border-color: #dc3545;
            background: #fff5f5;
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.15);
        }

        /* Badge Count */
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

        /* List Container dengan Custom Scrollbar */
        #listContainer {
            overflow-y: auto;
            max-height: calc(100vh - 180px);
            padding: 10px;
        }

        /* Scrollbar Cantik */
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

        /* ITEM LOKASI (CARD STYLE) */
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

        /* Style untuk Item yang Sedang Aktif */
        .location-item.active {
            border: 2px solid var(--grab-green);
            /* Border Hijau */
            background-color: #f0fdf4;
            /* Background Hijau Sangat Muda */
            transform: translateX(5px);
            /* Geser sedikit ke kanan biar estetik */
        }

        /* Ubah warna ikon koordinat saat active */
        .location-item.active .loc-coord i {
            color: var(--grab-green);
        }

        /* Garis Hijau di kiri item */
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

            /* Izinkan teks turun ke bawah */
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

        /* Tombol Sampah */
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

        .toast-container {
            z-index: var(--z-toast) !important;
        }

        .maplibregl-ctrl-top-left {
            margin-top: 80px;
        }
    </style>

    <style>
        /* --- STYLE KARTU HASIL RUTE --- */
        .route-result-card {
            background: #f0fdf4;
            /* Hijau sangat muda */
            border: 1px dashed var(--grab-green);
            /* Garis putus-putus estetik */
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            /* Jarak dari tombol hitung */
            display: none;
            /* Default sembunyi */
            animation: fadeIn 0.3s ease;
        }

        .route-stat-box {
            text-align: center;
            flex: 1;
            /* Agar lebar rata */
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
        <div class="panel-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 fw-bold text-dark d-flex align-items-center">
                    Daftar Lokasi
                    <span class="badge-count shadow-sm ms-2" id="locCount">0</span>
                </h6>
                <button class="btn-reset-minimal" onclick="clearAllMarkers()">
                    <i class="bi bi-trash3 me-1"></i> Reset
                </button>
            </div>

            <div class="mode-switch-container mb-3">
                <input type="radio" class="btn-check" name="travelMode" id="modeCar" value="Car" checked>
                <label class="btn-mode-switch flex-grow-1" for="modeCar">
                    <i class="bi bi-car-front-fill me-2"></i> Mobil
                </label>

                <input type="radio" class="btn-check" name="travelMode" id="modeBike" value="Motorcycle">
                <label class="btn-mode-switch flex-grow-1" for="modeBike">
                    <i class="bi bi-scooter me-2"></i> Motor
                </label>
            </div>

            <button class="btn btn-action-primary w-100" onclick="calculateRoute()">
                <i class="bi bi-sign-turn-right-fill me-2"></i> Hitung Rute
            </button>

            <div id="routeResultCard" class="route-result-card">
                <div class="d-flex justify-content-between align-items-center">

                    <div class="route-stat-box">
                        <div class="route-label"><i class="bi bi-rulers"></i> Jarak</div>
                        <div class="route-value" id="resDistance">-</div>
                    </div>

                    <div class="route-divider align-self-stretch"></div>

                    <div class="route-stat-box">
                        <div class="route-label"><i class="bi bi-stopwatch"></i> Waktu</div>
                        <div class="route-value" id="resDuration">-</div>
                    </div>

                </div>
            </div>
        </div>

        <div id="listContainer">
        </div>
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
        const routeCalculator = "{{ env('AWS_MAP_ROUTE') }}";

        let map = null;
        let markersData = []; // Array penyimpanan data lokasi

        // --- 1. TOAST ---
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toastContainer');

            // Setup Icon & Warna
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

            // Buat Elemen Wrapper
            const wrapper = document.createElement('div');

            // Masukkan HTML
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

            // 1. Append dulu ke container (PENTING: Harus masuk DOM dulu sebelum di-init Bootstrap)
            const toastElement = wrapper.firstElementChild;
            container.appendChild(toastElement);

            // 2. Gunakan requestAnimationFrame untuk memastikan DOM sudah siap
            requestAnimationFrame(() => {
                try {
                    const t = new bootstrap.Toast(toastElement, {
                        autohide: false
                    });
                    t.show();

                    // Auto hide manual
                    setTimeout(() => {
                        if (toastElement && document.body.contains(toastElement)) {
                            t.hide();
                        }
                    }, 5000);

                    // Hapus dari DOM setelah animasi hide selesai
                    toastElement.addEventListener('hidden.bs.toast', () => {
                        toastElement.remove();
                    });
                } catch (error) {
                    console.error("Gagal init toast:", error);
                    // Fallback jika bootstrap error: hapus elemen agar tidak nyangkut
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

            // --- FITUR BARU: KLIK PETA UNTUK TAMBAH LOKASI ---
            map.on('click', async (e) => {
                // 1. Ambil koordinat klik
                const coords = [e.lngLat.lng, e.lngLat.lat];

                // 2. Tambahkan Marker SECARA LANGSUNG (Optimistic UI)
                // Kita beri nama sementara dulu supaya user merasa responsif
                addLocation(coords, "Memuat alamat...");

                // Ambil ID dari marker yang baru saja dibuat (Logic: dia jadi selectedMarkerId)
                const currentId = selectedMarkerId;

                try {
                    // 3. Panggil API Reverse Geocoding (Cari nama jalan)
                    const addressName = await getPlaceNameByCoords(coords);

                    // 4. Jika nama ditemukan, update Marker & List
                    if (addressName) {
                        // Cari data marker di array berdasarkan ID
                        const item = markersData.find(m => m.id === currentId);

                        if (item) {
                            item.name = addressName; // Update Nama

                            // Update Popup di Peta
                            item.marker.setPopup(new maplibregl.Popup({
                                offset: 25
                            }).setText(addressName));

                            // Render ulang List Panel
                            renderLocationList();

                            showToast('Lokasi Ditemukan', addressName, 'success');
                        }
                    } else {
                        // Jika tidak ketemu nama jalan (misal di tengah laut)
                        const item = markersData.find(m => m.id === currentId);
                        if (item) {
                            item.name = `Lokasi (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                            renderLocationList();
                        }
                    }
                } catch (error) {
                    console.error(error);
                }
            });
        }

        // --- 3. CORE FUNCTION: ADD LOCATION & RENDER LIST ---
        function addLocation(coords, label) {
            const id = Date.now(); // ID Unik

            // A. Buat Marker di Peta (Pastikan draggable: true)
            const newMarker = new maplibregl.Marker({
                    color: '#00B14F',
                    draggable: true // Fitur geser aktif
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setText(label))
                .addTo(map);

            newMarker.togglePopup();

            // --- TAMBAHAN LOGIC UPDATE SAAT DRAG ---
            newMarker.on('dragend', async () => {
                const lngLat = newMarker.getLngLat();
                const updatedCoords = [lngLat.lng, lngLat.lat];

                // 1. Update data lokal dulu (biar cepat)
                const item = markersData.find(m => m.id === id);
                if (item) {
                    item.coords = updatedCoords;
                    // renderLocationList(); // Update angka koordinat di panel

                    showToast('Memuat...', 'Mencari alamat baru...', 'info');

                    // 2. PANGGIL FUNGSI HELPER (Code lebih bersih!)
                    const newName = await getPlaceNameByCoords(updatedCoords);

                    // 3. Update UI jika nama ditemukan
                    if (newName) {
                        item.name = newName; // Update data
                        newMarker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setText(newName)); // Update Popup
                        renderLocationList(); // Update List Panel
                        showToast('Lokasi Terupdate', newName, 'success');
                    } else {
                        showToast('Info', 'Nama lokasi tidak ditemukan.', 'warning');
                    }
                }
            });
            // ----------------------------------------

            selectedMarkerId = id;

            // B. Simpan ke Array
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

        // --- 5. LOGIC RUTE (ROUTING) ---
        async function calculateRoute() {
            if (markersData.length < 2) {
                return showToast('Kurang Data', 'Tambahkan minimal 2 lokasi.', 'warning');
            }

            const origin = markersData[0].coords;
            const destination = markersData[1].coords;
            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            // Tampilkan loading di button atau toast kecil
            showToast('Memproses...', 'Menghitung rute...', 'info');

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

                if (!response.ok) throw new Error('Gagal menghitung rute');

                const data = await response.json();

                if (data.Legs && data.Legs.length > 0 && data.Legs[0].Geometry) {
                    // 1. Gambar Garis
                    const points = data.Legs[0].Geometry.LineString;
                    const geoJsonGeometry = {
                        type: 'LineString',
                        coordinates: points
                    };
                    drawRouteOnMap(geoJsonGeometry);

                    // 2. Olah Data Jarak & Waktu
                    const summary = data.Summary;
                    const distance = summary.Distance.toFixed(1) + ' km';

                    // Format Waktu (Contoh: 65 min -> 1 jam 5 mnt)
                    const totalMinutes = Math.round(summary.DurationSeconds / 60);
                    let durationText = totalMinutes + ' mnt';
                    if (totalMinutes >= 60) {
                        const hrs = Math.floor(totalMinutes / 60);
                        const mins = totalMinutes % 60;
                        durationText = `${hrs} jam ${mins} mnt`;
                    }

                    // 3. TAMPILKAN KE CARD (Manipulasi DOM)
                    document.getElementById('resDistance').innerText = distance;
                    document.getElementById('resDuration').innerText = durationText;

                    // Munculkan Card
                    document.getElementById('routeResultCard').style.display = 'block';

                } else {
                    showToast('Error', 'Jalur rute tidak ditemukan.', 'error');
                }

            } catch (error) {
                console.error(error);
                if (selectedMode === 'Motorcycle') {
                    showToast('Info', 'Mode Motor belum tersedia. Coba Mobil.', 'warning');
                } else {
                    showToast('Error', 'Gagal mengambil rute API.', 'error');
                }
            }
        }

        function drawRouteOnMap(lineStringGeometry) {
            // Hapus layer rute lama jika ada
            removeRouteLayer();

            // Tambahkan Source GeoJSON
            map.addSource('routeSource', {
                'type': 'geojson',
                'data': {
                    'type': 'Feature',
                    'properties': {},
                    'geometry': lineStringGeometry
                }
            });

            // Tambahkan Layer Garis (Line)
            map.addLayer({
                'id': 'routeLayer',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#00B14F', // Warna Grab Green
                    'line-width': 5,
                    'line-opacity': 0.8
                }
            });

            // Tambahkan Layer Panah/Outline (Opsional biar cantik)
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
                    'line-width': 2,
                    'line-gap-width': 5,
                    'line-opacity': 0.5
                }
            });

            // Fit Bounds (Zoom peta agar seluruh rute terlihat)
            const coordinates = lineStringGeometry.coordinates;
            const bounds = coordinates.reduce((bounds, coord) => {
                return bounds.extend(coord);
            }, new maplibregl.LngLatBounds(coordinates[0], coordinates[0]));

            map.fitBounds(bounds, {
                padding: 50
            });
        }

        function removeRouteLayer() {
            if (map.getLayer('routeLayer')) map.removeLayer('routeLayer');
            if (map.getLayer('routeLayerOutline')) map.removeLayer('routeLayerOutline');
            if (map.getSource('routeSource')) map.removeSource('routeSource');
        }

        // --- 4. FUNCTION RENDER LIST SIDEBAR ---
        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');

            // Tampilkan/Sembunyikan Panel dengan efek fade
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

                // --- LOGIC ACTIVE ---
                // Jika ID item ini sama dengan ID yang sedang dipilih, tambahkan class 'active'
                if (item.id === selectedMarkerId) {
                    div.classList.add('active');
                }
                // --------------------

                // Animasi masuk
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
                    <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})" title="Hapus">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                container.appendChild(div);
            });
        }

        // --- ACTIONS ---
        function zoomToLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) {
                // 1. Set ID ini sebagai yang aktif
                selectedMarkerId = id;

                map.flyTo({
                    center: item.coords,
                    zoom: 17
                });
                item.marker.togglePopup();

                // showToast('Edit Mode', 'Marker terpilih.', 'info');

                // 2. RENDER ULANG LIST (Supaya class .active berpindah)
                renderLocationList();
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
            selectedMarkerId = null;

            removeRouteLayer(); // Hapus garis
            renderLocationList();

            // SEMBUNYIKAN CARD HASIL RUTE
            document.getElementById('routeResultCard').style.display = 'none';

            showToast('Reset', 'Semua marker dan rute dihapus.', 'info');
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

        // --- HELPER FUNCTION: REVERSE GEOCODING ---
        // Menerima array koordinat [lng, lat], mengembalikan String Nama Lokasi atau null
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
                        Language: 'id'
                    })
                });

                if (!response.ok) throw new Error('AWS API Error');

                const data = await response.json();

                // Cek jika ada hasil
                if (data.Results && data.Results.length > 0) {
                    return data.Results[0].Place.Label;
                }
                return null; // Tidak ditemukan

            } catch (error) {
                console.error("Gagal reverse geocode:", error);
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