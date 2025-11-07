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
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Kolom untuk Form Pencarian */
        .sidebar-card {
            background: rgba(255, 255, 255, 0.82);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.css" rel="stylesheet" />
</head>

<body>
    <!-- Container untuk beberapa sidebar -->
    <div class="sidebar-view">
        <div class="sidebar" id="card-show">

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
        const latlong = [106.79429899999458, -6.598382822071136];

        // Elemen UI
        const resultsDiv = document.getElementById('results');
        const searchInput = document.getElementById('searchIHTML');
        // const cardShow = document.getElementById('card-show');

        let markerCounter = 0; // Counter untuk marker
        let dataDesti = [];
        let markerData = [];

        // location test
        const locTest = [{
            lat: -6.598382822071136,
            lng: 106.79429899999458
        }, {
            lat: -6.582489956676503,
            lng: 106.80626586615912
        }, {
            lat: -6.584585177943203,
            lng: 106.8216278676437
        }, {
            lat: -6.580786899665337,
            lng: 106.81252303455234
        }];

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
                zoom: 13,
            });
            map.addControl(new maplibregl.NavigationControl(), "top-right");

            // Menyimpan marker dalam array
            window.markers = [];

            // Tambahkan marker pertama pada posisi awal
            // initializeMap();

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
            map.addControl(new maplibregl.FullscreenControl(), "top-right"); // Fullscreen control
            map.addControl(new maplibregl.ScaleControl({
                maxWidth: 120,
                unit: "metric"
            }), 'bottom-right'); // Scale control in bottom-right
            map.addControl(new maplibregl.GeolocateControl()); // Geolocation control

            window.mapReady = true; // tandai peta siap dipakai

            // Menambahkan event listener untuk memastikan cardMarker dipanggil setelah peta dimuat
            // window.map.on('load', function() {
            //     cardsMarker(); // Panggil cardMarker hanya setelah peta selesai dimuat
            // });

            addMarkersFromArray();
        });

        async function initializeMap() {
            // Mengambil alamat menggunakan reverse geocoding dan menambahkan marker
            const address = await reverseGeocode(latlong[0], latlong[1]);
            addMarker(latlong, address);

            //show card
            // await cardMarker();
        }

        // Menambahkan marker ke peta untuk setiap lokasi dalam locTest
        async function addMarkersFromArray() {
            for (let i = 0; i < locTest.length; i++) {
                const location = locTest[i];
                const address = await reverseGeocode(location.lng, location.lat); // Ambil alamat dari koordinat

                const ll = [location.lng, location.lat]; // [lon, lat]

                // Menambahkan marker di peta
                addMarker(ll, address);
            }
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

            newMarker.togglePopup();

            // Menambahkan marker ke array hanya dengan objek marker
            window.markers.push(newMarker);

            // Menambahkan data marker ke array markersData untuk digunakan dalam list
            markerData.push({
                id: markerId,
                marker: newMarker,
                latlong: latlong,
                address: address
            });

            // Increment marker counter
            markerCounter++;

            // Jika ada lebih dari dua marker, hitung rute
            if (window.markers.length > 1) {
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
                    newMarker.setPopup(new maplibregl.Popup().setHTML(`<h4>Location ${markerId}</h4><p>${escapeHtml(address)}</p>`));
                    newMarker.togglePopup();

                    calculateRoute();
                } catch (err) {
                    // Jika gagal, beri informasi gagal mengambil alamat
                    newMarker.setPopup(new maplibregl.Popup().setHTML(`<h4>Location ${markerId}</h4><p>Gagal mengambil alamat.</p>`));
                    newMarker.togglePopup();
                }
            });
        }

        function cardsMarker() {
            //html marker loc
            var cardMarker = `<div class="sidebar-card">
                                <table style="width: 100%; font-size: small;">
                                    <tr>
                                        <td>
                                            Location 1 :
                                            <br>
                                            <span></span>
                                        </td>
                                        <td>
                                            Location 2 :
                                            <br>
                                            <span id="text_location2"></span>
                                        </td>
                                    </tr>
                                </table>

                                <table style="width: 100%; font-size: small;">
                                    <tr>
                                        <td>Route Distance</td>
                                        <td>:</td>
                                        <td><span id="route-distance">-</span> KM</td>
                                    </tr>
                                    <tr>
                                        <td>Estimated Time</td>
                                        <td>:</td>
                                        <td><span id="route-duration">-</span></td>
                                    </tr>
                                    <tr>
                                        <td>Straight-Line Distance</td>
                                        <td>:</td>
                                        <td><span id="straight-distance">-</span> KM</td>
                                    </tr>
                                </table>
                            </div>`;

            //check length markerData
            if (markerData.length == 0) {
                cardShow.innerHTML = cardMarker;
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
                        //get data deatil
                        // const markerIndexS = markerData.find(marker =>
                        //     JSON.stringify(marker.latlong) === JSON.stringify([start.lng, start.lat])
                        // );
                        // const markerIndexE = markerData.find(marker =>
                        //     JSON.stringify(marker.latlong) === JSON.stringify([end.lng, end.lat])
                        // );

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
                        // dataDesti.push({
                        //     start: start,
                        //     end: end,
                        //     distance: distanceInKm,
                        //     duration: durationText,
                        //     drawLine: data.Legs[0].Geometry.LineString
                        // });
                    } else {
                        console.error('No Legs found in the route response');
                    }

                } catch (error) {
                    console.error("Error calculating route:", error);
                    alert('Gagal menghitung rute.');
                }
            }
            // Ekspor fungsi agar bisa digunakan di file lain
            // saveDataToFile()

            drawRoute(drawLine); // Draw the route if valid
        }

        // Fungsi untuk menyimpan dataDesti ke file JS
        function saveDataToFile() {
            const dataString = `let dataDesti = ${JSON.stringify(dataDesti, null, 4)};`; // Convert array to JS code

            // Membuat Blob dari dataString dan membuat URL untuk download
            const blob = new Blob([dataString], {
                type: 'application/javascript'
            });
            const url = URL.createObjectURL(blob);

            // Membuat elemen anchor untuk download
            const link = document.createElement('a');
            link.href = url;
            link.download = 'dataMatrix.js'; // Nama file yang diunduh

            // Memicu click pada elemen anchor untuk mengunduh file
            link.click();

            // Revoke URL setelah selesai
            URL.revokeObjectURL(url);
        }

        async function calculateRouteMatrix() {
            // Cek apakah ada marker di array
            if (window.markers.length < 2) {
                alert("Pastikan ada minimal dua marker (keberangkatan dan tujuan).");
                return;
            }

            // Ambil koordinat dari marker terakhir (destination)
            // const destinationPosition = window.markers.getLngLat(); // Koordinat marker terakhir (tujuan)

            // // Ambil koordinat dari semua marker kecuali marker terakhir (departure)
            // const departurePositions = window.markers.slice(0, window.markers.length - 1).map(marker => {
            //     const {
            //         lng,
            //         lat
            //     } = marker.getLngLat();
            //     return [lng, lat];
            // });

            const departurePositions = window.markers.map(m => {
                const {
                    lng,
                    lat
                } = m.getLngLat();
                return [lng, lat]; // format [lng, lat]
            });

            // Menyiapkan parameter untuk API
            const params = {
                DeparturePositions: departurePositions,
                DestinationPositions: departurePositions, // Koordinat titik tujuan (hanya marker terakhir)
                TravelMode: 'Car', // Mode perjalanan: 'Car'
                IncludeLegGeometry: true, // Sertakan geometri perjalanan
                AvoidTolls: true // Menghindari jalan tol
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
                dataDesti.push(data);
                saveDataToFile();

                console.log("Route Matrix Response Data:", data);

            } catch (error) {
                console.error("Error calculating route matrix:", error);
                alert('Gagal menghitung rute.');
            }
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
    </script>
</body>

</html>