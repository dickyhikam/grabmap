<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>GrabMaps (AWS)</title>
    <meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
    <!-- Library MapLibre (Kompatibel dengan GrabMaps Vector Tiles) -->
    <link href="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
        }

        .sidebar-view {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1;
            width: 380px;
            max-width: 90%;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.96);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .input-active {
            border-color: #00b140 !important;
            box-shadow: 0 0 5px rgba(0, 177, 64, 0.3);
        }

        .btn-grab {
            background-color: #00b140;
            color: white;
            font-weight: bold;
            border: none;
        }

        .btn-grab:hover {
            background-color: #009e39;
            color: white;
        }

        #results {
            display: none;
            background: #fff;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #eee;
        }

        #results:not(:empty) {
            display: block;
        }
    </style>
</head>

<body>

    <div class="sidebar-view">
        <div class="sidebar">
            <h5 style="color:#00b140">GrabMaps (AWS)</h5>
            <!-- Input Area -->
            <div class="input-group-custom">
                <span class="input-label" style="color: #00b140;"><i class="fas fa-map-marker-alt"></i> Titik Jemput</span>
                <textarea id="inputA" rows="3" class="form-control mb-2" placeholder="Titik Jemput..."></textarea>
            </div>
            <div class="input-group-custom">
                <span class="input-label" style="color: #d9534f;"><i class="fas fa-map-marker-alt"></i> Tujuan</span>
                <textarea id="inputB" rows="3" class="form-control mb-2" placeholder="Tujuan..."></textarea>
            </div>

            <!-- Opsi Kendaraan -->
            <div class="d-flex gap-2 mb-2">
                <select id="vehicleType" class="form-select">
                    <option value="Motorcycle">Motor</option>
                    <option value="Car">Mobil</option>
                    <option value="Walking">Jalan Kaki</option>
                </select>
            </div>

            <button class="btn btn-grab w-100" id="btnCalculate">Cek Rute</button>

            <!-- Hasil -->
            <div id="route-summary" class="alert alert-light border mt-2" style="display:none; font-size:14px;">
                <strong>Jarak:</strong> <span id="dist-val"></span> km <br>
                <strong>Waktu:</strong> <span id="time-val"></span> menit
            </div>
            <div id="results"></div>
        </div>
    </div>
    <div id="map"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@^5.9.0/dist/maplibre-gl.js"></script>

    <script>
        // 1. KONFIGURASI (Isi dengan Resource GrabMaps dari AWS Console)
        const CONFIG = {
            region: "{{ env('AWS_REGION') }}",
            mapName: "{{ env('AWS_MAP_NAME') }}", // Pastikan ini Map Grab
            placeIndex: "{{ env('AWS_MAP_PLACE') }}", // Pastikan ini Place Index Grab
            routeCalc: "{{ env('AWS_MAP_ROUTE') }}", // Pastikan ini Route Calc Grab
            apiKey: "{{ env('AWS_API_KEY') }}",
            center: [106.845, -6.230] // Jakarta
        };

        const AppState = {
            map: null,
            markerA: null,
            markerB: null,
            activeMode: 'A'
        };

        // 2. MAP SERVICE
        const MapService = {
            init: () => {
                AppState.map = new maplibregl.Map({
                    container: "map",
                    style: `https://maps.geo.${CONFIG.region}.amazonaws.com/maps/v0/maps/${CONFIG.mapName}/style-descriptor?key=${CONFIG.apiKey}`,
                    center: CONFIG.center,
                    zoom: 14
                });
                AppState.map.addControl(new maplibregl.NavigationControl());
            },
            flyTo: (lngLat) => AppState.map.flyTo({
                center: lngLat,
                zoom: 16,
                speed: 1.5
            })
        };

        // 3. LOCATION SERVICE (Geocoding GrabMaps)
        const LocationService = {
            initMarkers: () => {
                // Marker Biru (Jemput)
                AppState.markerA = new maplibregl.Marker({
                        draggable: true,
                        color: '#00b140'
                    })
                    .setLngLat(CONFIG.center).addTo(AppState.map);
                // Marker Merah (Tujuan)
                AppState.markerB = new maplibregl.Marker({
                        draggable: true,
                        color: '#d9534f'
                    })
                    .setLngLat([CONFIG.center[0] + 0.01, CONFIG.center[1] + 0.01]).addTo(AppState.map);
            },
            updatePos: (lngLat, mode) => {
                const m = mode === 'A' ? AppState.markerA : AppState.markerB;
                m.setLngLat(lngLat);
                LocationService.reverseGeocode(lngLat, mode === 'A' ? '#inputA' : '#inputB');
            },
            reverseGeocode: async (ll, sel) => {
                try {
                    // Menggunakan Place Index GrabMaps
                    const r = await fetch(`https://places.geo.${CONFIG.region}.amazonaws.com/places/v0/indexes/${CONFIG.placeIndex}/search/position?key=${CONFIG.apiKey}`, {
                        method: 'POST',
                        body: JSON.stringify({
                            Position: [ll.lng, ll.lat]
                        }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    const d = await r.json();
                    $(sel).val(d.Results?.[0]?.Place?.Label || "Lokasi tidak dikenal");
                } catch (e) {
                    console.error(e);
                }
            },
            search: async (text) => {
                if (text.length < 3) return [];
                // Pencarian POI GrabMaps
                const r = await fetch(`https://places.geo.${CONFIG.region}.amazonaws.com/places/v0/indexes/${CONFIG.placeIndex}/search/text?key=${CONFIG.apiKey}`, {
                    method: 'POST',
                    body: JSON.stringify({
                        Text: text,
                        MaxResults: 5
                    }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const d = await r.json();
                return d.Results || [];
            }
        };

        // 4. ROUTE SERVICE (Kalkulasi Rute GrabMaps)
        const RouteService = {
            calculate: async () => {
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
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                return await res.json();
            },
            draw: (geo) => {
                const data = {
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: geo
                    }
                };
                // Update atau Buat Layer Garis Hijau Grab
                if (AppState.map.getSource('route')) AppState.map.getSource('route').setData(data);
                else {
                    AppState.map.addSource('route', {
                        type: 'geojson',
                        data: data
                    });
                    AppState.map.addLayer({
                        id: 'route',
                        type: 'line',
                        source: 'route',
                        paint: {
                            'line-color': '#00b140',
                            'line-width': 6,
                            'line-opacity': 0.8
                        }
                    });
                }
                const b = new maplibregl.LngLatBounds();
                geo.forEach(c => b.extend(c));
                AppState.map.fitBounds(b, {
                    padding: 50
                });
            }
        };

        // 5. MAIN LOGIC
        $(document).ready(() => {
            MapService.init();
            LocationService.initMarkers();

            // Initial Geocode
            LocationService.reverseGeocode(AppState.markerA.getLngLat(), '#inputA');
            LocationService.reverseGeocode(AppState.markerB.getLngLat(), '#inputB');

            // Logic UI
            const setMode = (m) => {
                AppState.activeMode = m;
                $('textarea').removeClass('input-active');
                $('#input' + m).addClass('input-active');
                $('#results').empty();
            };

            $('#inputA').on('focus', () => setMode('A')).on('input', async function() {
                renderRes(await LocationService.search(this.value));
            });
            $('#inputB').on('focus', () => setMode('B')).on('input', async function() {
                renderRes(await LocationService.search(this.value));
            });

            AppState.markerA.on('dragend', () => {
                LocationService.updatePos(AppState.markerA.getLngLat(), 'A');
                setMode('A');
            });
            AppState.markerB.on('dragend', () => {
                LocationService.updatePos(AppState.markerB.getLngLat(), 'B');
                setMode('B');
            });

            AppState.map.on('click', (e) => {
                LocationService.updatePos(e.lngLat, AppState.activeMode);
                MapService.flyTo(e.lngLat);
            });

            $('#btnCalculate').click(async function() {
                $(this).text('Sedang Menghitung...').prop('disabled', true);
                try {
                    const data = await RouteService.calculate();
                    RouteService.draw(data.Legs[0].Geometry.LineString);
                    $('#dist-val').text(data.Summary.Distance.toFixed(2));
                    $('#time-val').text((data.Summary.DurationSeconds / 60).toFixed(0));
                    $('#route-summary').slideDown();
                } catch (e) {
                    alert("Gagal koneksi ke GrabMaps");
                }
                $(this).text('Cek Rute').prop('disabled', false);
            });

            const renderRes = (res) => {
                let h = "";
                res.forEach(r => {
                    const p = r.Place.Geometry.Point;
                    h += `<div class="p-2 border-bottom" style="cursor:pointer" onclick="selectSearch(${p[0]},${p[1]}, '${r.Place.Label.replace(/'/g,"")}')">${r.Place.Label}</div>`;
                });
                $('#results').html(h);
            }

            window.selectSearch = (lon, lat, label) => {
                const ll = {
                    lng: lon,
                    lat: lat
                };
                LocationService.updatePos(ll, AppState.activeMode);
                MapService.flyTo(ll);
                $('#results').empty();
            };
        });
    </script>
</body>

</html>