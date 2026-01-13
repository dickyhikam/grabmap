<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tutorial GrabMaps: 3 Langkah Utama</title>
    <style>
        /* --- STYLE DOKUMEN CETAK --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background-color: #fff;
        }

        /* Cover Page */
        .cover {
            text-align: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 40px;
            margin-bottom: 40px;
        }

        .cover h1 {
            font-size: 32px;
            color: #00b140;
            margin-bottom: 5px;
        }

        .cover h2 {
            font-size: 18px;
            color: #555;
            font-weight: normal;
            margin-top: 0;
        }

        .cover .meta {
            font-size: 13px;
            color: #999;
            margin-top: 20px;
        }

        /* Headings & Points */
        h3 {
            color: #2c3e50;
            margin-top: 30px;
            border-left: 5px solid #00b140;
            padding-left: 10px;
        }

        .point-container {
            background: #f9fffb;
            border: 1px solid #e0f2e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .point-badge {
            background: #00b140;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: inline-block;
        }

        .point-title {
            font-size: 20px;
            font-weight: bold;
            color: #00b140;
            display: block;
            margin-bottom: 10px;
        }

        /* Code Blocks */
        pre {
            background-color: #2d2d2d;
            color: #f8f8f2;
            border-radius: 6px;
            padding: 15px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            margin-top: 10px;
        }

        code {
            font-family: monospace;
        }

        /* Helpers */
        ul {
            margin-top: 5px;
        }

        li {
            margin-bottom: 5px;
        }

        .note {
            font-size: 13px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 4px;
            color: #856404;
        }

        .page-break {
            page-break-before: always;
        }

        @media print {
            body {
                max-width: 100%;
                padding: 0;
            }

            pre {
                white-space: pre-wrap;
                word-wrap: break-word;
            }
        }
    </style>
</head>

<body>

    <div class="cover">
        <h1>Tutorial Implementasi GrabMaps</h1>
        <h2>Menggunakan AWS Location Service & MapLibre GL JS</h2>
        <div class="meta">
            <p><strong>Fokus:</strong> Show Maps &bull; Place &bull; Route</p>
            <p>2025</p>
        </div>
    </div>

    <p>
        Dokumen ini menjelaskan struktur kode modular untuk membangun aplikasi peta menggunakan data GrabMaps.
        Implementasi dibagi menjadi tiga layanan utama (Services) agar kode mudah dipahami dan dikelola.
    </p>

    <div class="note">
        <strong>Prasyarat Konfigurasi:</strong><br>
        Pastikan variabel <code>CONFIG</code> di dalam kode JavaScript telah diisi dengan API Key dan nama resource (Map, Place, Route) yang dibuat di AWS Console dengan provider <strong>GrabMaps</strong>.
    </div>

    <br>

    <div class="point-container">
        <span class="point-badge">Point 1</span>
        <span class="point-title">Implementasi Show Maps (MapService)</span>

        <p>Langkah pertama adalah inisialisasi peta dasar. Kita menggunakan library <strong>MapLibre GL JS</strong> yang mendukung format <em>Vector Tiles</em> dari AWS.</p>

        <strong>Tugas Utama Modul Ini:</strong>
        <ul>
            <li>Memuat peta ke dalam elemen HTML <code>div id="map"</code>.</li>
            <li>Mengatur gaya peta (Style URL) sesuai konfigurasi AWS.</li>
            <li>Menambahkan kontrol navigasi (Zoom In/Out).</li>
        </ul>

        <pre>
// MapService: Menangani rendering peta
const MapService = {
    init: () => {
        // Inisialisasi Peta
        AppState.map = new maplibregl.Map({
            container: "map",
            // URL ini menentukan tampilan peta (Grab Light/Dark)
            style: `https://maps.geo.${CONFIG.region}.amazonaws.com/maps/v0/maps/${CONFIG.mapName}/style-descriptor?key=${CONFIG.apiKey}`,
            center: CONFIG.center, // [Lon, Lat] Jakarta
            zoom: 14
        });
        
        // Tambah tombol Zoom
        AppState.map.addControl(new maplibregl.NavigationControl());
    },
    // Fungsi untuk memindahkan kamera peta
    flyTo: (lngLat) => AppState.map.flyTo({ center: lngLat, zoom: 16, speed: 1.5 })
};
</pre>
    </div>

    <div class="page-break"></div>

    <div class="point-container">
        <span class="point-badge">Point 2</span>
        <span class="point-title">Implementasi Place (LocationService)</span>

        <p>Poin kedua menangani data lokasi. GrabMaps memiliki data POI (Point of Interest) yang sangat kaya, termasuk lokasi UMKM dan jalan kecil.</p>

        <strong>Tugas Utama Modul Ini:</strong>
        <ul>
            <li><strong>Reverse Geocoding:</strong> Mengubah koordinat (saat marker digeser) menjadi nama jalan yang mudah dibaca.</li>
            <li><strong>Searching:</strong> Mencari lokasi berdasarkan teks input pengguna.</li>
            <li><strong>Marker Management:</strong> Mengelola posisi Marker A (Jemput) dan B (Tujuan).</li>
        </ul>

        <pre>
// LocationService: Menangani Geocoding & Marker
const LocationService = {
    // 1. Setup Marker Awal
    initMarkers: () => {
        AppState.markerA = new maplibregl.Marker({draggable: true, color: '#00b140'})
            .setLngLat(CONFIG.center).addTo(AppState.map);
        
        AppState.markerB = new maplibregl.Marker({draggable: true, color: '#d9534f'})
            .setLngLat([CONFIG.center[0]+0.01, CONFIG.center[1]+0.01]).addTo(AppState.map);
    },

    // 2. Reverse Geocoding (Koordinat -> Alamat)
    reverseGeocode: async (ll, inputSelector) => {
        // Panggil API AWS Place Index
        const response = await fetch(`https://places.geo.${CONFIG.region}.amazonaws.com/places/v0/indexes/${CONFIG.placeIndex}/search/position?key=${CONFIG.apiKey}`, {
            method: 'POST',
            body: JSON.stringify({ Position: [ll.lng, ll.lat] }),
            headers: {'Content-Type': 'application/json'}
        });
        const data = await response.json();
        
        // Isi Textarea dengan Label Alamat
        $(inputSelector).val(data.Results?.[0]?.Place?.Label || "Lokasi tidak dikenal");
    }
};
</pre>
    </div>

    <div class="point-container">
        <span class="point-badge">Point 3</span>
        <span class="point-title">Implementasi Route (RouteService)</span>

        <p>Poin terakhir adalah perhitungan jalur. Menggunakan data GrabMaps memungkinkan kita mendapatkan estimasi waktu yang memperhitungkan lalu lintas khas Asia Tenggara (termasuk rute khusus motor).</p>

        <strong>Tugas Utama Modul Ini:</strong>
        <ul>
            <li><strong>Calculate:</strong> Mengirim koordinat A & B ke API Route Calculator AWS.</li>
            <li><strong>Draw:</strong> Menggambar garis rute (Polyline) di atas peta menggunakan format GeoJSON.</li>
        </ul>

        <pre>
// RouteService: Menghitung & Menggambar Jalur
const RouteService = {
    // 1. Hitung Rute (API Call)
    calculate: async () => {
        const start = AppState.markerA.getLngLat();
        const end = AppState.markerB.getLngLat();

        const response = await fetch(`https://routes.geo.${CONFIG.region}.amazonaws.com/routes/v0/calculators/${CONFIG.routeCalc}/calculate/route?key=${CONFIG.apiKey}`, {
            method: 'POST',
            body: JSON.stringify({
                DeparturePosition: [start.lng, start.lat],
                DestinationPosition: [end.lng, end.lat],
                TravelMode: $('#vehicleType').val(), // Penting: 'Motorcycle' atau 'Car'
                IncludeLegGeometry: true, // Wajib true untuk dapat garis peta
                DistanceUnit: "Kilometers"
            }),
            headers: {'Content-Type': 'application/json'}
        });
        return await response.json();
    },

    // 2. Gambar Garis (GeoJSON Layer)
    draw: (geometryPoints) => {
        const geoJsonData = {
            type: 'Feature',
            geometry: { type: 'LineString', coordinates: geometryPoints }
        };

        // Jika layer sudah ada, update datanya. Jika belum, buat baru.
        if (AppState.map.getSource('route')) {
            AppState.map.getSource('route').setData(geoJsonData);
        } else {
            AppState.map.addSource('route', { type: 'geojson', data: geoJsonData });
            AppState.map.addLayer({
                id: 'route',
                type: 'line',
                source: 'route',
                paint: { 
                    'line-color': '#00b140', // Hijau Grab
                    'line-width': 6 
                }
            });
        }
    }
};
</pre>
    </div>

    <div class="page-break"></div>

    <h3>4. Kesimpulan Alur Kerja</h3>
    <p>Aplikasi bekerja dengan menghubungkan ketiga poin di atas melalui <strong>Event Listeners</strong> (misalnya: saat tombol diklik atau marker digeser).</p>

    <ol>
        <li><strong>Inisialisasi:</strong> Saat halaman dimuat, <code>MapService</code> menampilkan peta dan <code>LocationService</code> menaruh marker.</li>
        <li><strong>Interaksi Marker:</strong> Saat marker digeser, <code>LocationService.reverseGeocode</code> dipanggil untuk memperbarui teks alamat.</li>
        <li><strong>Kalkulasi:</strong> Saat tombol "Cek Rute" ditekan, <code>RouteService.calculate</code> dipanggil untuk mendapatkan data, lalu <code>RouteService.draw</code> memvisualisasikan jalur hijau di peta.</li>
    </ol>

</body>

</html>