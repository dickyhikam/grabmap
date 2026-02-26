<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>GrabMaps - Batch Routing System</title>
    <meta name="description" content="GrabMaps interactive batch routing." />
    <link rel="icon" href="images.png" type="image/x-icon" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/search.css') }}" />

    <style>
        /* Tambahan CSS untuk tampilan hasil batch */
        .batch-container {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 10px;
        }

        .batch-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
            background: white;
            margin-bottom: 5px;
            border-radius: 4px;
            font-size: 13px;
        }

        .batch-item i {
            color: #00B14F;
            margin-right: 5px;
        }

        .badge-origin {
            background: #00B14F;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .btn-batch {
            width: 100%;
            margin-top: 10px;
            background-color: #00B14F;
            color: white;
            font-weight: bold;
        }

        .marker-label {
            background: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            border: 2px solid #00B14F;
            font-weight: bold;
        }
    </style>

    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@aws-sdk/client-location/dist-browser/index.js"></script>
</head>

<body>
    <div id="header" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <a href="{{ route('pageHome') }}">
            <img src="logo.png" alt="GrabMaps Logo" style="height: 40px;">
        </a>
        <div class="header-buttons">
            <button class="btn btn-outline-secondary" onclick="resetMaps();">Reset Maps</button>
        </div>
    </div>

    <div class="container-result" style="display: flex; height: calc(100vh - 60px);">
        <div class="sidebar-panel" style="width: 350px; overflow-y: auto; padding: 20px; border-right: 1px solid #ddd;">
            <div class="search-box">
                <div class="search-input-group" style="display: flex; margin-bottom: 10px;">
                    <input type="text" class="form-control" placeholder="Search location..." id="searchInput">
                    <button class="btn btn-success" onclick="performSearch()"><i class="fas fa-search"></i></button>
                </div>

                <div class="search-options-row" style="display: flex; gap: 10px; align-items: center;">
                    <div class="search-options" style="display: flex; gap: 10px;">
                        <button class="btn btn-light search-option active" data-type="car"><i class="fas fa-car"></i></button>
                        <button class="btn btn-light search-option" data-type="motorcycle"><i class="fas fa-motorcycle"></i></button>
                    </div>
                </div>

                <button class="btn btn-batch mt-3" id="btnCalculateBatch" onclick="calculateGrabBatchMatrix()">
                    <i class="fas fa-layer-group"></i> Calculate Batch Matrix
                </button>
                <small class="text-muted">*Click map to add points (1st click = Origin)</small>
            </div>

            <hr>
            <div class="result-box" id="resultBox">
                <p class="text-center text-muted">No locations selected yet.</p>
            </div>
        </div>

        <div id="map" style="flex-grow: 1;"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Konfigurasi dari Laravel ENV
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const mapRoute = "{{ env('AWS_MAP_ROUTE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";

        let map;
        let selectedLocations = []; // Menyimpan [lng, lat]
        let markers = [];

        // 1. Initialize Map
        function initializeMap() {
            map = new maplibregl.Map({
                container: 'map',
                style: `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`,
                center: [106.8271, -6.1751], // Jakarta
                zoom: 12
            });

            map.addControl(new maplibregl.NavigationControl());

            // Klik peta untuk menambah titik batch
            map.on('click', function(e) {
                if (selectedLocations.length >= 11) {
                    alert("Maximum 1 origin and 10 destinations for this demo.");
                    return;
                }
                const coords = [e.lngLat.lng, e.lngLat.lat];
                addPoint(coords);
            });
        }

        // 2. Tambah Titik Ke Peta
        function addPoint(coords) {
            selectedLocations.push(coords);

            const isOrigin = selectedLocations.length === 1;
            const el = document.createElement('div');
            el.className = 'marker-label';
            el.innerHTML = selectedLocations.length;
            el.style.backgroundColor = isOrigin ? '#00B14F' : 'white';
            el.style.color = isOrigin ? 'white' : '#333';

            const marker = new maplibregl.Marker({
                    element: el
                })
                .setLngLat(coords)
                .addTo(map);

            markers.push(marker);
            updateSidebarUI();
        }

        function updateSidebarUI() {
            if (selectedLocations.length > 0) {
                $('#resultBox').html(`<p>${selectedLocations.length} points selected. Ready to calculate.</p>`);
            }
        }

        // 3. LOGIKA UTAMA: CalculateRouteMatrix (Batch Call)
        async function calculateGrabBatchMatrix() {
            if (selectedLocations.length < 2) {
                alert("Please select at least 1 Origin and 1 Destination on the map.");
                return;
            }

            const travelMode = $('.search-option.active').data('type') === 'motorcycle' ? 'Motorcycle' : 'Car';

            // Inisialisasi AWS Location Client
            const client = new aws_sdk_client_location.LocationClient({
                region: region,
                credentials: {
                    // Gunakan API Key via middleware atau Cognito. 
                    // Untuk demo ini, kita asumsikan akses diatur via AWS Policy
                }
            });

            const origin = selectedLocations[0];
            const destinations = selectedLocations.slice(1);

            const params = {
                CalculatorName: mapRoute,
                DeparturePositions: [origin],
                DestinationPositions: destinations,
                TravelMode: travelMode,
                DistanceUnit: "Kilometers"
            };

            // Tampilkan Loading
            $('#resultBox').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Calculating...</div>');

            try {
                // Endpoint manual karena AWS SDK v3 butuh auth header khusus untuk API Key
                // Kita gunakan fetch dengan SigV4 jika diperlukan, namun ini simulasi panggilannya:
                const command = new aws_sdk_client_location.CalculateRouteMatrixCommand(params);
                // Note: Pada implementasi API Key murni, biasanya menggunakan URL builder
                const data = await client.send(command);

                renderResults(data.RouteMatrix[0]);
            } catch (err) {
                console.error(err);
                $('#resultBox').html('<div class="alert alert-danger">Error: Make sure your API Key has RouteCalculator permissions.</div>');
            }
        }

        // 4. Render Hasil ke Sidebar
        function renderResults(results) {
            let html = '<div class="batch-container"><h6><span class="badge-origin">Origin Point 1</span></h6>';

            results.forEach((item, index) => {
                const km = item.Distance.toFixed(2);
                const mins = Math.round(item.DurationSeconds / 60);

                html += `
                    <div class="batch-item">
                        <span><strong>To Destination ${index + 2}</strong></span>
                        <span><i class="fas fa-road"></i> ${km} km</span>
                        <span><i class="fas fa-clock"></i> ${mins} mins</span>
                    </div>
                `;
            });

            html += '</div><button class="btn btn-sm btn-link" onclick="resetMaps()">Clear All</button>';
            $('#resultBox').html(html);
        }

        function resetMaps() {
            markers.forEach(m => m.remove());
            markers = [];
            selectedLocations = [];
            $('#resultBox').html('<p class="text-center text-muted">No locations selected yet.</p>');
        }

        async function calculateGrabBatchMatrix() {
            // 1. Validasi Input
            if (selectedLocations.length < 2) {
                alert("Please select at least 1 Origin and 1 Destination on the map.");
                return;
            }

            // 2. Tentukan Travel Mode (Car/Motorcycle)
            const travelMode = $('.search-option.active').data('type') === 'motorcycle' ? 'Motorcycle' : 'Car';
            const origin = selectedLocations[0]; // Titik pertama sebagai Asal
            const destinations = selectedLocations.slice(1); // Sisanya sebagai Tujuan
            const params = {
                DeparturePositions: [origin],
                DestinationPositions: destinations,
                TravelMode: travelMode,
                DistanceUnit: "Kilometers"
            };

            // Tambahkan opsi hindari tol jika mode Mobil (Opsional)
            if (travelMode === 'Car') {
                params.CarModeOptions = {
                    AvoidTolls: true
                };
            }

            // 5. Bangun URL Endpoint AWS
            const url = `https://routes.geo.${region}.amazonaws.com/routes/v0/calculators/${mapRoute}/calculate/route-matrix?key=${apiKey}`;

            // Tampilkan Loading di UI
            $('#resultBox').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Calculating via GrabMaps...</div>');

            try {
                // 6. Eksekusi Request menggunakan Fetch
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(params)
                });

                // Cek jika respon gagal (403, 400, dll)
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'API Request Failed');
                }

                const data = await response.json();

                // 7. Kirim hasil ke fungsi render (data.RouteMatrix[0] karena kita hanya kirim 1 origin)
                renderResults(data.RouteMatrix[0]);

                console.log("Route Matrix Success:", data.RouteMatrix);

            } catch (err) {
                console.error("Detail Error:", err);
                $('#resultBox').html('<div class="alert alert-danger">Error: ' + err.message + '</div>');
            }
        }

        // Jalankan saat ready
        $(document).ready(function() {
            initializeMap();

            // Toggle kendaraan
            $('.search-option').click(function() {
                $('.search-option').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
</body>

</html>