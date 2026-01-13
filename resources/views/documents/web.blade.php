<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tutorial Implementasi GrabMaps (AWS)</title>
    <style>
        /* CSS untuk Tampilan Dokumen Cetak yang Rapih */
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
            margin-top: 80px;
            margin-bottom: 120px;
            border-bottom: 1px solid #eee;
            padding-bottom: 50px;
        }

        .cover h1 {
            font-size: 34px;
            color: #00b140;
            /* Warna Hijau Khas Grab */
            margin-bottom: 10px;
        }

        .cover h2 {
            font-size: 20px;
            color: #2c3e50;
            font-weight: normal;
        }

        .cover .meta {
            margin-top: 40px;
            font-size: 14px;
            color: #7f8c8d;
        }

        /* Headings */
        h3 {
            color: #00b140;
            /* Hijau Grab */
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
            margin-top: 40px;
        }

        h4 {
            color: #2c3e50;
            margin-top: 25px;
            font-size: 16px;
        }

        /* Highlight Box */
        .info-box {
            background-color: #e8f8f0;
            border-left: 5px solid #00b140;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }

        /* Code Blocks */
        pre {
            background-color: #f8f9fa;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            padding: 15px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 11px;
            white-space: pre-wrap;
        }

        /* Diagram Modules */
        .diagram-box {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .module {
            border: 1px solid #00b140;
            background: #fff;
            padding: 10px;
            border-radius: 6px;
            width: 130px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            color: #00b140;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .page-break {
            page-break-before: always;
        }

        /* Print Styles */
        @media print {
            body {
                max-width: 100%;
                padding: 0;
            }

            .diagram-box {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="cover">
        <h1>Panduan Implementasi GrabMaps</h1>
        <h2>Menggunakan AWS Location Service</h2>
        <div class="meta">
            <p><strong>Topik:</strong> Routing, Geocoding & Map Rendering</p>
            <p><strong>Provider:</strong> GrabMaps (via AWS)</p>
            <p><strong>Tahun:</strong> 2025</p>
        </div>
    </div>

    <h3>1. Mengapa GrabMaps di AWS?</h3>
    <p>
        Tutorial ini akan memandu Anda membuat aplikasi peta menggunakan data dari <strong>GrabMaps</strong> yang diakses melalui AWS Location Service.
    </p>
    <p>
        Untuk wilayah Asia Tenggara (termasuk Indonesia), GrabMaps menawarkan keunggulan dibandingkan provider global (seperti Esri/HERE) dalam hal:
    </p>
    <ul>
        <li><strong>Akurasi Jalan Kecil (Gangs):</strong> Data lebih detail untuk rute sepeda motor.</li>
        <li><strong>Point of Interest (POI):</strong> Database lokasi UMKM, warung, dan gedung lokal yang lebih lengkap.</li>
        <li><strong>Traffic Real-time:</strong> Estimasi waktu tempuh yang disesuaikan dengan kondisi lalu lintas lokal.</li>
    </ul>

    <h3>2. Konfigurasi AWS Console (Wajib)</h3>
    <div class="info-box">
        <strong>PENTING:</strong> Agar tutorial ini bekerja sebagai "GrabMaps", Anda harus memilih Provider yang tepat saat membuat resource di AWS Console.
    </div>

    <ol>
        <li><strong>Buat Map Resource:</strong>
            <ul>
                <li>Nama: <code>GrabMapStyle</code></li>
                <li>Data Provider: Pilih <strong>GrabMaps</strong></li>
                <li>Style: <em>Grab Standard Light</em></li>
            </ul>
        </li>
        <li><strong>Buat Place Index (Pencarian):</strong>
            <ul>
                <li>Nama: <code>GrabPlaces</code></li>
                <li>Data Provider: Pilih <strong>GrabMaps</strong></li>
            </ul>
        </li>
        <li><strong>Buat Route Calculator (Rute):</strong>
            <ul>
                <li>Nama: <code>GrabRoutes</code></li>
                <li>Data Provider: Pilih <strong>GrabMaps</strong></li>
            </ul>
        </li>
    </ol>

    <div class="page-break"></div>

    <h3>3. Arsitektur Kode Modular</h3>
    <p>Kita akan menggunakan pendekatan modular agar kode mudah dikelola.</p>

    <div class="diagram-box">
        <div class="module">1. Config<br>(API Keys & Env)</div>
        <div class="module">2. MapService<br>(Render Peta)</div>
        <div class="module">3. LocationService<br>(Data GrabMaps)</div>
        <div class="module">4. RouteService<br>(Jalur GrabRoutes)</div>
    </div>

    <h3>4. Kode Implementasi Lengkap</h3>
    <p>Salin kode berikut ke dalam file HTML Anda.</p>

    <pre>
&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;meta charset="utf-8" /&gt;
    &lt;title&gt;Aplikasi GrabMaps (AWS)&lt;/title&gt;
    &lt;meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" /&gt;
    &lt;!-- Library MapLibre (Kompatibel dengan GrabMaps Vector Tiles) --&gt;
    &lt;link href="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.css" rel="stylesheet" /&gt;
    &lt;link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"&gt;
    &lt;style&gt;
        #map { position: absolute; top: 0; bottom: 0; width: 100%; }
        .sidebar-view { position: absolute; top: 10px; left: 10px; z-index: 1; width: 380px; max-width: 90%; }
        .sidebar { background: rgba(255,255,255,0.96); padding: 15px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .input-active { border-color: #00b140 !important; box-shadow: 0 0 5px rgba(0, 177, 64, 0.3); }
        .btn-grab { background-color: #00b140; color: white; font-weight: bold; border: none; }
        .btn-grab:hover { background-color: #009e39; color: white; }
        #results { display: none; background: #fff; max-height: 200px; overflow-y: auto; border: 1px solid #eee; }
        #results:not(:empty) { display: block; }
    &lt;/style&gt;
&lt;/head&gt;
&lt;body&gt;

    &lt;div class="sidebar-view"&gt;
        &lt;div class="sidebar"&gt;
            &lt;h5 style="color:#00b140"&gt;GrabMaps Routing&lt;/h5&gt;
            &lt;!-- Input Area --&gt;
            &lt;textarea id="inputA" rows="2" class="form-control mb-2" placeholder="Titik Jemput..."&gt;&lt;/textarea&gt;
            &lt;textarea id="inputB" rows="2" class="form-control mb-2" placeholder="Tujuan..."&gt;&lt;/textarea&gt;
            
            &lt;!-- Opsi Kendaraan --&gt;
            &lt;div class="d-flex gap-2 mb-2"&gt;
                &lt;select id="vehicleType" class="form-select"&gt;
                    &lt;option value="Motorcycle"&gt;Motor (GrabBike)&lt;/option&gt;
                    &lt;option value="Car"&gt;Mobil (GrabCar)&lt;/option&gt;
                    &lt;option value="Walking"&gt;Jalan Kaki&lt;/option&gt;
                &lt;/select&gt;
                &lt;div class="d-flex align-items-center"&gt;
                    &lt;input type="checkbox" id="avoidTolls" class="me-1"&gt; &lt;small&gt;No Tol&lt;/small&gt;
                &lt;/div&gt;
            &lt;/div&gt;
            
            &lt;button class="btn btn-grab w-100" id="btnCalculate"&gt;Cek Harga & Rute&lt;/button&gt;
            
            &lt;!-- Hasil --&gt;
            &lt;div id="route-summary" class="alert alert-light border mt-2" style="display:none; font-size:14px;"&gt;
                &lt;strong&gt;Jarak:&lt;/strong&gt; &lt;span id="dist-val"&gt;&lt;/span&gt; km &lt;br&gt;
                &lt;strong&gt;Waktu:&lt;/strong&gt; &lt;span id="time-val"&gt;&lt;/span&gt; menit
            &lt;/div&gt;
            &lt;div id="results"&gt;&lt;/div&gt;
        &lt;/div&gt;
    &lt;/div&gt;
    &lt;div id="map"&gt;&lt;/div&gt;

    &lt;script src="https://code.jquery.com/jquery-3.6.0.min.js"&gt;&lt;/script&gt;
    &lt;script src="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.js"&gt;&lt;/script&gt;

    &lt;script&gt;
    // 1. KONFIGURASI (Isi dengan Resource GrabMaps dari AWS Console)
    const CONFIG = {
        region: "{{ env('AWS_REGION') }}",
        mapName: "{{ env('AWS_MAP_NAME') }}",     // Pastikan ini Map Grab
        placeIndex: "{{ env('AWS_MAP_PLACE') }}", // Pastikan ini Place Index Grab
        routeCalc: "{{ env('AWS_MAP_ROUTE') }}",  // Pastikan ini Route Calc Grab
        apiKey: "{{ env('AWS_API_KEY') }}",
        center: [106.845, -6.230] // Jakarta
    };

    const AppState = { map: null, markerA: null, markerB: null, activeMode: 'A' };

    // 2. MAP SERVICE
    const MapService = {
        init: () =&gt; {
            AppState.map = new maplibregl.Map({
                container: "map",
                style: `https://maps.geo.${CONFIG.region}.amazonaws.com/maps/v0/maps/${CONFIG.mapName}/style-descriptor?key=${CONFIG.apiKey}`,
                center: CONFIG.center, zoom: 14
            });
            AppState.map.addControl(new maplibregl.NavigationControl());
        },
        flyTo: (lngLat) =&gt; AppState.map.flyTo({ center: lngLat, zoom: 16, speed: 1.5 })
    };

    // 3. LOCATION SERVICE (Geocoding GrabMaps)
    const LocationService = {
        initMarkers: () =&gt; {
            // Marker Biru (Jemput)
            AppState.markerA = new maplibregl.Marker({draggable: true, color: '#00b140'})
                .setLngLat(CONFIG.center).addTo(AppState.map);
            // Marker Merah (Tujuan)
            AppState.markerB = new maplibregl.Marker({draggable: true, color: '#d9534f'})
                .setLngLat([CONFIG.center[0]+0.01, CONFIG.center[1]+0.01]).addTo(AppState.map);
        },
        updatePos: (lngLat, mode) =&gt; {
            const m = mode === 'A' ? AppState.markerA : AppState.markerB;
            m.setLngLat(lngLat);
            LocationService.reverseGeocode(lngLat, mode === 'A' ? '#inputA' : '#inputB');
        },
        reverseGeocode: async (ll, sel) =&gt; {
            try {
                // Menggunakan Place Index GrabMaps
                const r = await fetch(`https://places.geo.${CONFIG.region}.amazonaws.com/places/v0/indexes/${CONFIG.placeIndex}/search/position?key=${CONFIG.apiKey}`, {
                    method: 'POST', body: JSON.stringify({ Position: [ll.lng, ll.lat] }), headers: {'Content-Type': 'application/json'}
                });
                const d = await r.json();
                $(sel).val(d.Results?.[0]?.Place?.Label || "Lokasi tidak dikenal");
            } catch(e) { console.error(e); }
        },
        search: async (text) =&gt; {
            if(text.length &lt; 3) return [];
            // Pencarian POI GrabMaps
            const r = await fetch(`https://places.geo.${CONFIG.region}.amazonaws.com/places/v0/indexes/${CONFIG.placeIndex}/search/text?key=${CONFIG.apiKey}`, {
                method: 'POST', body: JSON.stringify({ Text: text, MaxResults: 5 }), headers: {'Content-Type': 'application/json'}
            });
            const d = await r.json();
            return d.Results || [];
        }
    };

    // 4. ROUTE SERVICE (Kalkulasi Rute GrabMaps)
    const RouteService = {
        calculate: async () =&gt; {
            const start = AppState.markerA.getLngLat();
            const end = AppState.markerB.getLngLat();
            
            // API Call ke Route Calculator GrabMaps
            const res = await fetch(`https://routes.geo.${CONFIG.region}.amazonaws.com/routes/v0/calculators/${CONFIG.routeCalc}/calculate/route?key=${CONFIG.apiKey}`, {
                method: 'POST',
                body: JSON.stringify({
                    DeparturePosition: [start.lng, start.lat],
                    DestinationPosition: [end.lng, end.lat],
                    TravelMode: $('#vehicleType').val(), // Motorcycle/Car/Walking
                    IncludeLegGeometry: true, 
                    DistanceUnit: "Kilometers",
                    AvoidTolls: $('#avoidTolls').is(':checked')
                }),
                headers: {'Content-Type': 'application/json'}
            });
            return await res.json();
        },
        draw: (geo) =&gt; {
            const data = { type: 'Feature', geometry: { type: 'LineString', coordinates: geo } };
            // Update atau Buat Layer Garis Hijau Grab
            if (AppState.map.getSource('route')) AppState.map.getSource('route').setData(data);
            else {
                AppState.map.addSource('route', { type: 'geojson', data: data });
                AppState.map.addLayer({ 
                    id: 'route', type: 'line', source: 'route', 
                    paint: { 'line-color': '#00b140', 'line-width': 6, 'line-opacity': 0.8 } 
                });
            }
            const b = new maplibregl.LngLatBounds();
            geo.forEach(c =&gt; b.extend(c));
            AppState.map.fitBounds(b, { padding: 50 });
        }
    };

    // 5. MAIN LOGIC
    $(document).ready(() =&gt; {
        MapService.init();
        LocationService.initMarkers();
        
        // Initial Geocode
        LocationService.reverseGeocode(AppState.markerA.getLngLat(), '#inputA');
        LocationService.reverseGeocode(AppState.markerB.getLngLat(), '#inputB');

        // Logic UI
        const setMode = (m) =&gt; { AppState.activeMode = m; $('textarea').removeClass('input-active'); $('#input'+m).addClass('input-active'); $('#results').empty(); };
        
        $('#inputA').on('focus', () =&gt; setMode('A')).on('input', async function() { renderRes(await LocationService.search(this.value)); });
        $('#inputB').on('focus', () =&gt; setMode('B')).on('input', async function() { renderRes(await LocationService.search(this.value)); });

        AppState.markerA.on('dragend', () =&gt; { LocationService.updatePos(AppState.markerA.getLngLat(), 'A'); setMode('A'); });
        AppState.markerB.on('dragend', () =&gt; { LocationService.updatePos(AppState.markerB.getLngLat(), 'B'); setMode('B'); });

        AppState.map.on('click', (e) =&gt; {
            LocationService.updatePos(e.lngLat, AppState.activeMode);
            MapService.flyTo(e.lngLat);
        });

        $('#btnCalculate').click(async function() {
            $(this).text('Sedang Menghitung...').prop('disabled', true);
            try {
                const data = await RouteService.calculate();
                RouteService.draw(data.Legs[0].Geometry.LineString);
                $('#dist-val').text(data.Summary.Distance.toFixed(2));
                $('#time-val').text((data.Summary.DurationSeconds/60).toFixed(0));
                $('#route-summary').slideDown();
            } catch(e) { alert("Gagal koneksi ke GrabMaps"); }
            $(this).text('Cek Harga & Rute').prop('disabled', false);
        });

        const renderRes = (res) =&gt; {
            let h = "";
            res.forEach(r =&gt; {
                const p = r.Place.Geometry.Point;
                h += `&lt;div class="p-2 border-bottom" style="cursor:pointer" onclick="selectSearch(${p[0]},${p[1]}, '${r.Place.Label.replace(/'/g,"")}')"&gt;${r.Place.Label}&lt;/div&gt;`;
            });
            $('#results').html(h);
        }

        window.selectSearch = (lon, lat, label) =&gt; {
            const ll = {lng:lon, lat:lat};
            LocationService.updatePos(ll, AppState.activeMode);
            MapService.flyTo(ll);
            $('#results').empty();
        };
    });
    &lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;
</pre>

    <div class="page-break"></div>

    <h3>5. Penjelasan Penyesuaian GrabMaps</h3>
    <ul>
        <li><strong>Warna Tema:</strong> Menggunakan kode warna <code>#00b140</code> (Hijau Grab) untuk tombol dan jalur rute agar tampilan otentik.</li>
        <li><strong>Mode Kendaraan:</strong> Pilihan disesuaikan dengan layanan Grab: <code>Motorcycle</code> (GrabBike) dan <code>Car</code> (GrabCar). <em>Catatan: Pastikan Route Calculator di AWS mendukung mode "Motorcycle" jika menggunakan provider GrabMaps.</em></li>
        <li><strong>Akurasi Data:</strong> Dengan memilih GrabMaps sebagai provider di AWS Console, fungsi <code>LocationService</code> akan otomatis mencari jalan tikus/gang yang sering tidak terdeteksi oleh peta global lain.</li>
    </ul>

</body>

</html>