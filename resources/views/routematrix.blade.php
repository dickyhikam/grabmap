<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Demo MAP Grab (AWS)</title>
    <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
        }

        /* Sidebar View untuk Flexbox */
        .sidebar-view {
            display: flex;
            flex-direction: column;
            /* Sidebar disusun vertikal */
            position: absolute;
            top: 0;
            left: 10px;
            z-index: 1;
            width: 400px;
            height: 100vh;
            /* Full height of the screen */
        }

        /* Sidebar Panel */
        .sidebar {
            width: 100%;
            padding: 10px;
            z-index: 2;
            /* Di atas elemen lain */
            overflow-y: auto;
            /* Aktifkan scroll jika konten tinggi */
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Kolom untuk Form Pencarian */
        .sidebar-search {
            flex: 1;
            /* Menggunakan sisa ruang untuk pencarian */
            background: rgba(255, 255, 255, 0.82);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Kolom untuk Hasil Lokasi */
        .sidebar-locations {
            flex: 1;
            margin-top: 10px;
        }

        .card-locations {
            background: rgba(255, 255, 255, 0.82);
            padding-left: 15px;
            padding-right: 15px;
            padding-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            max-height: 40vh;
            /* Tinggi hasil pencarian terbatas */
            overflow-y: auto;
        }

        /* Pengaturan untuk input pencarian */
        .sidebar-search input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .sidebar-search textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .sidebar-search button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .sidebar-search button:hover {
            background-color: #0056b3;
        }

        /* Untuk hasil pencarian */
        #results {
            margin-top: 10px;
            max-height: 30vh;
            /* Tinggi hasil pencarian terbatas */
            overflow-y: auto;
        }

        /* Kartu hasil pencarian */
        .card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            padding: 15px;
            margin: 5px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 10px;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .card-address {
            font-size: 14px;
            color: #777;
        }

        #loading {
            text-align: center;
            margin: 20px 0;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* Style untuk Tab (Radio Button) */
        .tabs {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 20px;
            /* Menempatkan di atas dengan jarak */
            left: 50%;
            transform: translateX(-50%);
            /* Tengah-tengah secara horizontal */
            z-index: 3;
            background-color: white;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        input[type="radio"] {
            display: none;
            /* Sembunyikan radio button asli */
        }

        .tab-label {
            background-color: #f0f0f0;
            padding: 8px 10px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            text-align: center;
            flex-grow: 1;
            text-transform: capitalize;
            margin: 0 5px;
            border-radius: 5px;
            /* Memberikan jarak antar tab */
        }

        .tab-label i {
            margin-right: 5px;
        }

        input[type="radio"]:checked+.tab-label {
            background-color: #00B14F;
            /* Ganti warna latar belakang saat dipilih */
            color: white;
            /* Ganti teks menjadi putih saat dipilih */
        }

        /* Hover effect untuk tab */
        .tab-label:hover {
            background-color: #e0e0e0;
        }

        /* Modern submit button */
        .submit-btn {
            padding: 7px 10px;
            font-size: 16px;
            background-color: white;
            border-color: #00B14F;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            width: 200px;
            margin-left: 20px;
            text-align: center;
            font-weight: bold;
        }

        /* Hover effects for the button */
        .submit-btn:hover {
            background-color: #00B14F;
            transform: scale(1.05);
            /* Slightly increase size on hover */
        }

        /* Focus effect for better accessibility */
        .submit-btn:focus {
            outline: none;
            box-shadow: 0 0 5px 2px rgba(0, 200, 0, 0.6);
        }

        /* Style untuk tabel dan elemen lainnya */
        table {
            width: 100%;
            margin-top: 15px;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.css" rel="stylesheet" />
</head>

<body>
    <!-- Radio Buttons for Vehicle Type (Pindah ke atas, di luar sidebar-view) -->
    <div class="tabs">
        <input type="radio" id="vehicleCar" name="vehicleType" value="Car" checked>
        <label for="vehicleCar" class="tab-label"><i class="fas fa-car"></i> Car</label>

        <input type="radio" id="vehicleMotorcycle" name="vehicleType" value="Motorcycle">
        <label for="vehicleMotorcycle" class="tab-label"><i class="fas fa-motorcycle"></i> Motorcycle</label>

        <button class="submit-btn">Reset MAPS</button>
    </div>

    <!-- Container untuk beberapa sidebar -->
    <div class="sidebar-view">
        <div class="sidebar">
            <!-- Kolom Pencarian -->
            <div class="sidebar-search">
                <h5>Enter your address</h5>
                <textarea type="text" id="searchIHTML" placeholder="Enter address"></textarea>
                <small>Press Enter to search</small>
                <!-- Spinner loading -->
                <div id="loading" style="display: none;">
                    <div class="spinner-border" role="status"></div>
                    <br>
                    <span>Loading...</span>
                </div>
                <div id="results"></div>
            </div>

            <!-- Kolom Hasil Lokasi -->
            <div class="sidebar-locations" id="cardLocations"> </div>
        </div>
    </div>

    <div id="map"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script src="{{ asset('recomentRoutes.js') }}"></script>

    <script>
        // ====== KONFIGURASI DASAR (DIISI DARI ENV/LARAVEL) ======
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const mapPlace = "{{ env('AWS_MAP_PLACE') }}";
        const mapRoute = "{{ env('AWS_MAP_ROUTE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";

        // ⚠️ Format koordinat MapLibre: [lon, lat] (bukan [lat, lon])
        const latlong = [106.82274271212066, -6.192552220246095];

        // Elemen UI
        const resultsDiv = document.getElementById('results');
        const searchInput = document.getElementById('searchIHTML');
        // const loc1 = document.getElementById('text_location1');
        // const loc2 = document.getElementById('text_location2');
        // const routeDistanceEl = document.getElementById('route-distance');
        // const routeDurationEl = document.getElementById('route-duration');
        // const straightDistanceEl = document.getElementById('straight-distance');
        const cardLocations = document.getElementById('cardLocations');


        let vehicleType = "Car"; // using Car or Motorcycle
        let dataDesti = [];
        let dataAddress = [];
        let markerCounter = 0; // Counter untuk marker

        // Jadikan objek map & marker bersifat global agar bisa diakses dari onclick di HTML
        window.map = null;
        window.marker = null;
        window.mapReady = false; // flag untuk memastikan peta sudah siap

        // ====== INISIALISASI PETA ======
        $(document).ready(function() {
            const mapStyle = `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`;
            window.map = new maplibregl.Map({
                container: "map",
                style: mapStyle,
                center: latlong,
                zoom: 15,
            });
            map.addControl(new maplibregl.NavigationControl(), "top-right");

            // Menyimpan marker dalam array
            window.markers = [];

            // Tambahkan marker pertama pada posisi awal
            initializeMap();

            // Klik pada peta memindahkan marker ke lokasi yang diklik
            map.on('click', async function(e) {
                const p = e.lngLat; // {lng, lat}

                // Pastikan koordinat valid
                if (!isFinite(p.lng) || !isFinite(p.lat)) {
                    console.error("Invalid coordinates:", p);
                    return;
                }

                try {
                    // Ambil alamat dari koordinat
                    const address = await reverseGeocode(p.lng, p.lat);

                    const ll = [p.lng, p.lat]; // [lon, lat]

                    // Menambahkan marker di peta
                    addMarker(ll, address);
                } catch (error) {
                    console.error("Error in reverse geocoding:", error);
                }
            });

            window.mapReady = true; // tandai peta siap dipakai
            searchInput.addEventListener('input', () => {
                // Fungsi ini akan terus memanggil searchPlaceSuggestions saat pengguna mengetik
                searchGeocode(searchInput.value);
            });

            // Menambahkan event listener untuk mendeteksi tombol Enter
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    resultsDiv.style.display = 'none';
                    // Saat Enter ditekan, jalankan fungsi searchGeocode
                    searchGeocode(searchInput.value);
                    searchInput.blur(); // Menghilangkan fokus dari input setelah Enter ditekan
                }
            });

            document.querySelectorAll('input[name="vehicleType"]').forEach((radio) => {
                radio.addEventListener('change', getSelectedType);

                // Jika ada lebih dari satu marker, hitung rute
                if (window.markers.length == 2) {
                    calculateRoute();
                }
                if (window.markers.length > 2) {
                    calculateRouteMatrix();
                }
            });
        });

        // Fungsi untuk mendapatkan nilai dari radio button yang terpilih
        function getSelectedType() {
            // Ambil nilai kendaraan terpilih
            vehicleType = document.querySelector('input[name="vehicleType"]:checked').value;
        }

        async function initializeMap() {
            // Mengambil alamat menggunakan reverse geocoding dan menambahkan marker
            const address = await reverseGeocode(latlong[0], latlong[1]);
            addMarker(latlong, address);
        }

        // Fungsi untuk menambah marker
        function addMarker(latlong, address) {
            // Menentukan ID untuk marker (berbasis panjang array markers)
            const markerId = window.markers.length + 1;

            // Menambahkan marker baru
            const newMarker = new maplibregl.Marker({
                    draggable: true
                })
                .setLngLat(latlong)
                .setPopup(new maplibregl.Popup().setHTML(`<h4>Location ${markerId}</h4><p>${escapeHtml(address)}</p>`))
                .addTo(map);

            // newMarker.togglePopup();

            // Menambahkan marker ke array hanya dengan objek marker dan data popup
            window.markers.push(newMarker);
            dataAddress.push({
                id: markerId,
                address: escapeHtml(address),
                latlong: latlong
            });

            // Increment marker counter
            markerCounter++;
            if (window.markers.length == 1) {
                listCardLocations();
            }

            // Jika ada lebih dari satu marker, hitung rute
            if (window.markers.length > 1) {
                calculateRoute();
            }
            // if (window.markers.length > 2) {
            //     calculateRouteMatrix();
            // }

            // Saat marker di-drag, lakukan reverse geocoding & update popup
            newMarker.on('dragend', async function() {
                const lngLat = newMarker.getLngLat(); // {lng, lat}
                try {
                    const address = await reverseGeocode(lngLat.lng, lngLat.lat);
                    // Update popup dengan alamat baru
                    newMarker.setPopup(new maplibregl.Popup().setHTML(`<h4>Location ${markerId}</h4><p>${escapeHtml(address)}</p>`));
                    newMarker.togglePopup();

                    getSelectedType();
                } catch (err) {
                    // Jika gagal, beri informasi gagal mengambil alamat
                    newMarker.setPopup(new maplibregl.Popup().setHTML(`<h4>Location ${markerId}</h4><p>Gagal mengambil alamat.</p>`));
                    newMarker.togglePopup();
                }
            });

            getSelectedType();
        }

        function deleteMarker(lon, lat, id) {
            // Cari marker berdasarkan latlong
            const markerToDelete = window.markers.find(marker => {
                const markerLngLat = marker.getLngLat();
                return markerLngLat.lng === lon && markerLngLat.lat === lat;
            });
            console.log(markerToDelete);


            if (markerToDelete) {
                // Hapus marker dari peta
                markerToDelete.remove();

                // Hapus data marker dari array
                const index = window.markers.indexOf(markerToDelete);
                if (index > -1) {
                    window.markers.splice(index, 1);
                }

                // Akses marker pertama setelah penghapusan
                const firstMarker = window.markers[0]; // Mengakses marker pertama
                if (firstMarker) {
                    console.log("First Marker after deletion:", firstMarker);
                    // Lakukan sesuatu dengan firstMarker, misalnya arahkan kamera ke firstMarker
                    flyLocation(firstMarker.getLngLat().lng, firstMarker.getLngLat().lat);
                }

                // Hapus card dari sidebar
                const card = document.querySelector(`[data-id="${id}"]`);
                if (card) {
                    card.remove();
                }


            }
        }

        // ====== UTIL: Escape sederhana agar aman saat menaruh teks ke HTML ======
        function escapeHtml(str = "") {
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#39;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
        }

        // ====== REVERSE GEOCODING (koordinat -> alamat label) ======
        async function reverseGeocode(lon, lat) {
            const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/position?key=${apiKey}`;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Position: [lon, lat]
                    }) // ⚠️ urutan [lon, lat]
                });

                if (response.ok) {
                    const data = await response.json();
                    const results = data['Results'];
                    if (results && results.length > 0) {
                        const label = results[0]?.Place?.Label;
                        return label ?? "Alamat tidak ditemukan";
                    }
                }
                return "Alamat tidak ditemukan";
            } catch (error) {
                return 'Galat geocoding';
            }
        }

        // ====== SEARCH GEOCODING (teks -> daftar tempat) ======
        async function searchGeocode(search) {
            if (search.length < 3) { // hindari call API terlalu sering saat input pendek
                resultsDiv.innerHTML = '';
                return;
            }

            // Menampilkan spinner loading
            document.getElementById('loading').style.display = 'block'; // Menampilkan spinner

            const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/text?key=${apiKey}`;
            let htmlContent = '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Text: search,
                        MaxResults: 5
                    }),
                });

                if (!response.ok) {
                    resultsDiv.innerHTML = `<div class="empty">Place/address not found.</div>`;
                    return;
                }

                const data = await response.json();
                const results = data['Results'] || [];
                console.log(data);

                if (!results.length) {
                    resultsDiv.innerHTML = `<div class="empty">Place/address not found.</div>`;
                    return;
                }

                // Render the results
                results.forEach((r) => {
                    const label = r?.Place?.Label || 'Not found';
                    const pt = r?.Place?.Geometry?.Point || []; // [lon, lat]
                    const lon = Number(pt[0]);
                    const lat = Number(pt[1]);

                    if (!isFinite(lon) || !isFinite(lat)) return; // guard if data is not valid

                    const {
                        title,
                        body
                    } = splitLabel(label);

                    const safeLabel = escapeHtml(label);
                    const safeTitle = escapeHtml(title || "Title");
                    const safeBody = escapeHtml(body || label);

                    htmlContent += `
                        <div class="card" onclick="showLocation(${lon}, ${lat}, &quot;${safeLabel}&quot;)">
                            <div class="card-title">${safeTitle}</div>
                            <div class="card-address">${safeBody}</div>
                        </div>
                    `;
                });

                // resultsDiv.innerHTML = "<h5>This Search Data</h5><hr>" + htmlContent;
                resultsDiv.innerHTML = htmlContent;

            } catch (error) {
                resultsDiv.innerHTML = `<div class="empty">Geocoding error. Try again.</div>`;
            } finally {
                // Menyembunyikan spinner setelah pencarian selesai
                document.getElementById('loading').style.display = 'none'; // Menyembunyikan spinner
                resultsDiv.style.display = 'block';
            }
        }

        // Fungsi untuk menangani pencarian dengan saran tempat menggunakan SearchPlaceIndexForSuggestions
        async function searchPlaceSuggestions(search) {
            // Cek apakah teks pencarian sudah cukup panjang
            if (search.length < 3) { // Hindari pencarian saat teks kurang dari 3 karakter
                resultsDiv.innerHTML = ''; // Hapus hasil pencarian
                return;
            }

            const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/suggestions?key=${apiKey}`;
            let htmlContent = '';

            try {
                // Mengirim request ke API untuk mendapatkan saran tempat
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        Text: search, // Teks pencarian
                        MaxResults: 5 // Batas maksimal hasil yang dikembalikan
                    }),
                });

                if (!response.ok) {
                    resultsDiv.innerHTML = `<div class="empty">Tempat/alamat tidak ditemukan.</div>`;
                    return;
                }

                const data = await response.json();
                const results = data['Results'] || []; // Ambil hasil saran


                if (!results.length) {
                    resultsDiv.innerHTML = `<div class="empty">Tempat/alamat tidak ditemukan.</div>`;
                    return;
                }

                // Render hasil pencarian sebagai kartu
                results.forEach((r) => {
                    const label = r?.Text || 'Tidak ditemukan';

                    const {
                        title,
                        body
                    } = splitLabel(label);

                    // Escape sebelum dimasukkan ke HTML untuk menghindari masalah XSS
                    const safeLabel = escapeHtml(label);
                    const safeTitle = escapeHtml(title || "Judul");
                    const safeBody = escapeHtml(body || label);

                    // Bangun konten HTML untuk kartu hasil
                    htmlContent += `
                    <div class="card" onclick="showData()">
                        <div class="card-title">${safeTitle}</div>
                        <div class="card-address">${safeBody}</div>
                    </div>
                    `;
                });

                // Tampilkan hasil pencarian
                // resultsDiv.innerHTML = "<h5>This Suggestions</h5><hr>" + htmlContent;
                resultsDiv.innerHTML = htmlContent;

            } catch (error) {
                resultsDiv.innerHTML = ` < div class = "empty" > Galal geocoding.Coba lagi. < /div>`;
            }
        }

        // ====== LOGIKA POPUP: pecah label menjadi title (sebelum koma pertama) dan body (setelahnya) ======
        function splitLabel(label = "") {
            const raw = String(label || "");
            const i = raw.indexOf(",");
            if (i === -1) {
                return {
                    title: raw.trim(),
                    body: ""
                };
            }
            const title = raw.slice(0, i).trim();
            const body = raw.slice(i + 1).trim(); // sisa setelah koma pertama
            return {
                title,
                body
            };
        }

        // ====== PINDAHKAN MARKER KE LOKASI PILIHAN ======
        function showLocation(lon, lat, label = "") {
            // Pastikan map & marker sudah siap (diinisialisasi di $(document).ready)
            if (!window.mapReady || !window.map) return;

            const LON = Number(lon);
            const LAT = Number(lat);
            if (!isFinite(LON) || !isFinite(LAT)) return; // guard input

            const ll = [LON, LAT]; // [lon, lat]

            // Menambahkan marker dan mengarahkan kamera
            addMarker(ll, label); // Fungsi untuk menambahkan marker
            window.map.flyTo({
                center: ll,
                zoom: 15, // zoom level dapat disesuaikan
                speed: 1.2,
                curve: 1
            });
        }

        function flyLocation(lon, lat) {
            const ll = [lon, lat]; // Menyimpan koordinat lon dan lat dalam array ll

            // Mengarahkan kamera ke lokasi marker yang sudah ada
            window.map.flyTo({
                center: ll,
                zoom: 15, // level zoom dapat disesuaikan
                speed: 1.2,
                curve: 1
            });

            // Mencari marker berdasarkan koordinat dan menampilkan popup
            window.markers.forEach(marker => {
                const markerLngLat = marker.getLngLat();
                if (markerLngLat.lng === lon && markerLngLat.lat === lat) {
                    // Jika marker ditemukan, tampilkan popup-nya
                    marker.togglePopup();
                }
            });
        }

        // ====== PERHITUNGAN RUTE A KE B ======
        async function calculateRoute() {
            // Periksa apakah ada cukup marker
            if (window.markers.length < 2) {
                console.error("Harus ada setidaknya dua marker untuk menghitung rute.");
                return;
            }

            const drawLine = [];
            // Loop untuk menghitung rute berurutan antara titik 1 ke 2, 2 ke 3, dll.
            for (let i = 0; i < window.markers.length - 1; i++) {
                const start = window.markers[i].getLngLat(); // Titik keberangkatan
                const end = window.markers[i + 1].getLngLat(); // Titik tujuan berikutnya

                // Cek apakah koordinat titik keberangkatan dan tujuan sama
                if (start.lng === end.lng && start.lat === end.lat) {
                    console.log(`Koordinat titik ${i + 1} dan ${i + 2} sama, melewati API call.`);
                    continue; // Lewati perhitungan ini jika titik sama
                }

                const params = {
                    "DeparturePosition": [start.lng, start.lat],
                    "DestinationPosition": [end.lng, end.lat],
                    "TravelMode": "Car", // Bisa diganti "Motorcycle"
                    "IncludeLegGeometry": true
                };

                const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${mapRoute}/calculate/route?key=${apiKey}`;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: JSON.stringify(params),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();
                    // console.log("Route 1 Response Data:", data);

                    // Cek apakah data.Legs ada dan valid
                    if (data.Legs && data.Legs.length > 0) {

                        // Menambahkan LineString dari rute yang dihitung ke dalam array drawLine
                        drawLine.push(data.Legs[0].Geometry.LineString);

                        // Tampilkan info jarak dan durasi dari hasil routing
                        const distanceInKm = data.Summary.Distance.toFixed(2);
                        const durationInMinutes = (data.Summary.DurationSeconds / 60).toFixed(0);

                        let durationText; // Variabel untuk menyimpan teks durasi final
                        //check total duration
                        if (durationInMinutes >= 60) {
                            // 3. Jika ya, hitung jam dan sisa menitnya
                            const hours = Math.floor(durationInMinutes / 60); // Ambil jam (pembulatan ke bawah)
                            const minutes = durationInMinutes % 60; // Ambil sisa menitnya

                            // Format teksnya, hanya tampilkan menit jika tidak nol
                            durationText = `${hours} hours`;
                            if (minutes > 0) {
                                durationText += ` ${minutes} minutes`;
                            }
                        } else {
                            // 4. Jika kurang dari 60, tampilkan menit saja
                            durationText = `${durationInMinutes} minutes`;
                        }
                        // Menambahkan data rute ke dalam array dataDesti
                        // dataDesti.push({
                        //     loc1: {
                        //         id: window.markers[i].popupContent.markerId,
                        //         latlng: start,
                        //         address: window.markers[i].popupContent.address
                        //     },
                        //     loc2: {
                        //         id: window.markers[i + 1].popupContent.markerId,
                        //         latlng: end,
                        //         address: window.markers[i + 1].popupContent.address
                        //     },
                        //     distance: distanceInKm,
                        //     duration: durationText,
                        //     drawLine: data.Legs[0].Geometry.LineString
                        // });

                        // listCardLocations();
                    } else {
                        console.error('No Legs found in the route response');
                    }

                } catch (error) {
                    console.error("Error calculating route:", error);
                    alert('Gagal menghitung rute.');
                }
            }

            drawRoute(drawLine); // Draw the route if valid
        }

        // Fungsi untuk menggambar rute di peta dengan warna acak untuk setiap rute
        function drawRoute(lineStrings) {
            lineStrings.forEach((lineString, index) => {
                const geojson = {
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: lineString // Koordinat untuk menggambar rute
                    }
                };

                // Menghasilkan warna random untuk setiap rute
                const routeColor = getRandomColor(); // Menggunakan fungsi untuk mendapatkan warna acak

                // Memeriksa apakah sudah ada sumber 'route' pada peta
                if (map.getSource('route-' + index)) {
                    // Jika sudah ada, hanya perbarui data sumber
                    map.getSource('route-' + index).setData(geojson);
                } else {
                    // Jika belum ada, tambahkan sumber dan layer baru untuk setiap rute
                    map.addSource('route-' + index, {
                        type: 'geojson',
                        data: geojson
                    });
                    map.addLayer({
                        id: 'route-' + index,
                        type: 'line',
                        source: 'route-' + index,
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        paint: {
                            'line-color': routeColor, // Menentukan warna garis untuk rute tertentu
                            'line-width': 5, // Lebar garis
                            'line-opacity': 0.8 // Opasitas garis
                        }
                    });
                }
            });
        }

        // Fungsi untuk menghasilkan warna random dalam format hex
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }

        // Fungsi untuk menghitung jarak garis lurus (Haversine Formula)
        function calculateStraightLineDistance() {
            const pointA = window.markers[0].getLngLat();
            const pointB = window.markers[1].getLngLat();
            if (!pointA || !pointB) return;

            const lon1 = pointA.lng;
            const lat1 = pointA.lat;
            const lon2 = pointB.lng;
            const lat2 = pointB.lat;

            const R = 6371; // Radius bumi dalam KM
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a =
                0.5 - Math.cos(dLat) / 2 +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                (1 - Math.cos(dLon)) / 2;

            const distance = R * 2 * Math.asin(Math.sqrt(a));
            straightDistanceEl.textContent = distance.toFixed(2);
        }

        function listCardLocations() {
            let htmlContent = ``;

            // Loop through each route segment in dataDesti
            for (let i = 0; i < dataDesti.length; i++) {
                const route = dataDesti[i];

                // For each route, create a row for each location and display the information
                htmlContent += `
                <div class="card-locations">
                    <table style="width: 100%; font-size: small; margin-bottom: 10px;">
                        <tr>
                            <td>Location ${i + 1}:</td>
                            <td><span>${route.loc1.address} (ID: ${route.loc1.id})</span></td>
                        </tr>
                        <hr>
                        <tr>
                            <td>Location ${i + 2}:</td>
                            <td><span>${route.loc2.address} (ID: ${route.loc2.id})</span></td>
                        </tr>
                    </table>
                    <table style="width: 100%; font-size: small; margin-bottom: 10px;">
                        <tr>
                            <td>Route Distance</td>
                            <td>:</td>
                            <td><span>${route.distance}</span> KM</td>
                        </tr>
                        <tr>
                            <td>Estimated Time</td>
                            <td>:</td>
                            <td><span>${route.duration}</span></td>
                        </tr>
                        <tr>
                            <td>Straight-Line Distance</td>
                            <td>:</td>
                            <td><span id="straight-distance">-</span> KM</td>
                        </tr>
                    </table>
                </div>
                `;
            }

            // Insert the dynamic content into the cardLocations container
            cardLocations.innerHTML = htmlContent;
        }
    </script>
</body>

</html>