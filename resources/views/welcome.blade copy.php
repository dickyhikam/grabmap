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
            /* Menggunakan sisa ruang yang tersisa */
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.82);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Pengaturan untuk input pencarian */
        .sidebar-search input {
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

        #results_select {
            margin-top: 10px;
            max-height: 40vh;
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
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.css" rel="stylesheet" />
</head>

<body>
    <!-- Container untuk beberapa sidebar -->
    <div class="sidebar-view">
        <div class="sidebar">
            <!-- Kolom Pencarian -->
            <div class="sidebar-search">
                <h5>Search Location</h5>
                <input type="text" id="searchIHTML" placeholder="Enter place/address">
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
            <div class="sidebar-locations">
                <h5>Selected Locations</h5>
                <hr>

                <div id="results_select"></div>
            </div>
        </div>
    </div>

    <div id="map"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script>
        // ====== KONFIGURASI DASAR (DIISI DARI ENV/LARAVEL) ======
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const mapPlace = "{{ env('AWS_MAP_PLACE') }}";
        const mapRoute = "{{ env('AWS_MAP_ROUTE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";

        // ⚠️ Format koordinat MapLibre: [lon, lat] (bukan [lat, lon])
        // const latlong = [103.79293649717077, 1.291233669355683];
        const latlong = [106.82751358132005, -6.180677475119887];

        // Elemen UI
        const resultsDiv = document.getElementById('results');
        const searchInput = document.getElementById('searchIHTML');

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

            calculateRouteMatrix();

            window.mapReady = true; // tandai peta siap dipakai
            searchInput.addEventListener('input', () => {
                // Fungsi ini akan terus memanggil searchPlaceSuggestions saat pengguna mengetik
                searchPlaceSuggestions(searchInput.value);
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
        });

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
                .setPopup(new maplibregl.Popup().setHTML(`<h4>Lokasi ${markerId}</h4><p>${escapeHtml(address)}</p>`))
                .addTo(map);

            newMarker.togglePopup();

            // Menambahkan marker ke array hanya dengan objek marker
            window.markers.push(newMarker);

            // Menambahkan data marker ke array markersData untuk digunakan dalam list
            const markerData = {
                id: markerId,
                marker: newMarker,
                latlong: latlong,
                address: address
            };

            // Render card untuk marker di sidebar
            renderMarkerCard(markerData);

            // Increment marker counter
            markerCounter++;

            // Jika ada lebih dari satu marker, hitung rute
            if (window.markers.length == 2) {
                calculateRoute();
            }
            if (window.markers.length > 2) {
                calculateRouteMatrix();
            }

            // Saat marker di-drag, lakukan reverse geocoding & update popup
            newMarker.on('dragend', async function() {
                const lngLat = newMarker.getLngLat(); // {lng, lat}
                try {
                    const address = await reverseGeocode(lngLat.lng, lngLat.lat);
                    // Update popup dengan alamat baru
                    newMarker.setPopup(new maplibregl.Popup().setHTML(`<h4>Lokasi ${markerId}</h4><p>${escapeHtml(address)}</p>`));
                    newMarker.togglePopup();
                } catch (err) {
                    // Jika gagal, beri informasi gagal mengambil alamat
                    newMarker.setPopup(new maplibregl.Popup().setHTML(`<h4>Lokasi ${markerId}</h4><p>Gagal mengambil alamat.</p>`));
                    newMarker.togglePopup();
                }
            });
        }

        // Fungsi untuk merender card lokasi marker di sidebar
        function renderMarkerCard(markerData) {
            const card = document.createElement('div');
            card.classList.add('card');
            card.setAttribute('data-id', markerData.id); // Menambahkan data-id pada card

            // Membuat konten card
            if (markerData.id == 1) {
                card.innerHTML = `
                    <div class="card-title">Lokasi ${markerData.id}</div>
                    <div class="card-address">${escapeHtml(markerData.address)}</div>
                `;
            } else {
                card.innerHTML = `
                    <div class="card-title">Lokasi ${markerData.id}</div>
                    <div class="card-address">${escapeHtml(markerData.address)}</div>
                    <button onclick="deleteMarker(${markerData.latlong[0]}, ${markerData.latlong[1]}, ${markerData.id})" class="btn btn-danger">Delete</button>
                `;
            }

            // Menambahkan onclick pada card untuk mengarahkan kamera ke marker saat card diklik
            card.onclick = function() {
                flyLocation(markerData.latlong[0], markerData.latlong[1]);
            };

            // Menambahkan card ke dalam results_select
            document.getElementById('results_select').appendChild(card);
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
            const start = window.markers[0].getLngLat();
            const end = window.markers[1].getLngLat();
            console.log("Latlong data:", end);

            const params = {
                "DeparturePosition": [start.lng, start.lat],
                "DestinationPosition": [end.lng, end.lat],
                "TravelMode": "Car", // Bisa diganti "Truck" atau "Walking"
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
                console.log("Response data:", data); // Log the response data to check the structure

                // Cek apakah data.Legs ada dan valid
                if (data.Legs && data.Legs.length > 0) {
                    drawRoute(data.Legs[0].Geometry.LineString); // Draw the route if valid
                } else {
                    console.error('No Legs found in the route response');
                }

            } catch (error) {
                console.error("Error calculating route:", error);
                alert('Gagal menghitung rute.');
            }
        }

        async function calculateRouteMatrix() {
            // // Ambil semua titik keberangkatan (start) dan tujuan (end)
            // const starts = window.markers.map(marker => marker.getLngLat()); // Mengambil lat long semua marker sebagai titik start
            // const ends = starts; // Untuk contoh, kita menggunakan titik yang sama untuk tujuan, tetapi bisa disesuaikan.

            // // Pastikan ada setidaknya dua marker untuk dihitung rutenya
            // if (starts.length < 2) return;

            // // Siapkan parameter untuk calculateRouteMatrix
            // const params = {
            //     DeparturePositions: starts.map(latlng => [latlng.lng, latlng.lat]), // Koordinat titik keberangkatan
            //     DestinationPositions: ends.map(latlng => [latlng.lng, latlng.lat]), // Koordinat titik tujuan
            //     TravelMode: "Car", // Mode perjalanan (Car, Walking, etc.)
            //     IncludeLegGeometry: true // Sertakan geometri leg untuk rute
            // };

            // Data dummy: Titik keberangkatan dan tujuan di sekitar Monas
            const starts = [{
                    lat: -6.175392,
                    lng: 106.828270
                }, // Monas
            ];

            const ends = [{
                    lat: -6.187342,
                    lng: 106.822951
                }, // Grand Indonesia Mall
                {
                    lat: -6.193381,
                    lng: 106.831940
                }, // Katedral Jakarta
                {
                    lat: -6.192610,
                    lng: 106.828302
                }, // Istiqlal Mosque
                {
                    lat: -6.175067,
                    lng: 106.828441
                }, // Gambir Station
            ];

            // Siapkan parameter untuk `calculateRouteMatrix`
            const params = {
                DeparturePositions: starts.map(latlng => [latlng.lng, latlng.lat]), // Koordinat titik keberangkatan
                DestinationPositions: ends.map(latlng => [latlng.lng, latlng.lat]), // Koordinat titik tujuan
                TravelMode: "Car", // Mode perjalanan: 'Car'
                IncludeLegGeometry: true // Sertakan geometri perjalanan
            };

            const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${mapRoute}/calculate/route-matrix?key=${apiKey}`;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: JSON.stringify(params),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                console.log("Route Matrix Response Data:", data);

                if (data.Matrix) {
                    // Jika Matrix ditemukan, gambar rute di peta
                    data.Matrix.forEach((row, rowIndex) => {
                        row.forEach((cell, colIndex) => {
                            // Menggambar rute antara titik keberangkatan dan tujuan
                            if (cell && cell.Geometry) {
                                drawRoute(cell.Geometry.LineString, starts[rowIndex], ends[colIndex]);
                            }
                        });
                    });
                } else {
                    console.error('Route matrix tidak ditemukan.');
                }

            } catch (error) {
                console.error("Error calculating route matrix:", error);
                alert('Gagal menghitung rute.');
            }
        }

        // Fungsi untuk menggambar rute di peta
        function drawRoute(lineString) {
            const geojson = {
                type: 'Feature',
                geometry: {
                    type: 'LineString',
                    coordinates: lineString
                }
            };

            if (map.getSource('route')) {
                map.getSource('route').setData(geojson);
            } else {
                map.addSource('route', {
                    type: 'geojson',
                    data: geojson
                });
                map.addLayer({
                    id: 'route',
                    type: 'line',
                    source: 'route',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#007bff',
                        'line-width': 5,
                        'line-opacity': 0.8
                    }
                });
            }
        }
    </script>
</body>

</html>