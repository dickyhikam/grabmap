<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>GrabMaps via Laravel + MapLibre</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />
    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
        }

        .debug-info {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
            max-width: 500px;
            max-height: 200px;
            overflow: auto;
        }

        .control-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px;
            border-radius: 5px;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <div id="loading" class="loading">
        <div>Memuat peta...</div>
        <div id="load-details" style="font-size: 12px; margin-top: 10px;"></div>
    </div>

    <div class="control-panel">
        <button onclick="switchStyle('normal')">Style Normal</button>
        <button onclick="switchStyle('simple')">Style Simple</button>
        <button onclick="switchStyle('clean')">Style Clean</button>
    </div>

    <div id="map"></div>
    <div id="debug" class="debug-info" style="display: none;"></div>

    <script>
        let currentMap = null;

        // Debug function
        function debugLog(message) {
            console.log(message);
            const debugDiv = document.getElementById('debug');
            debugDiv.innerHTML += new Date().toLocaleTimeString() + ': ' + message + '<br>';
            debugDiv.style.display = 'block';
            debugDiv.scrollTop = debugDiv.scrollHeight;

            // Update loading details
            const loadDetails = document.getElementById('load-details');
            loadDetails.innerHTML = message;
        }

        // Function untuk switch style
        async function switchStyle(styleType) {
            const endpoints = {
                'normal': '/api/map-style',
                'simple': '/api/map-style-simple',
                'clean': '/api/map-style-clean'
            };

            const endpoint = endpoints[styleType] || '/api/map-style';

            debugLog(`🔄 Switching to ${styleType} style...`);
            await initializeMap(endpoint);
        }

        // ====== INISIALISASI PETA ======
        async function initializeMap(styleEndpoint = '/api/map-style-clean') {
            try {
                // Hancurkan map existing jika ada
                if (currentMap) {
                    currentMap.remove();
                    currentMap = null;
                    debugLog('🗑️ Map previous dihapus');
                }

                document.getElementById('loading').style.display = 'block';
                document.getElementById('loading').className = 'loading';

                debugLog('🔄 Memulai inisialisasi peta...');
                debugLog(`📡 Requesting dari: ${styleEndpoint}`);

                const response = await fetch(styleEndpoint);

                debugLog(`📊 Response status: ${response.status}`);

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                const mapStyle = await response.json();
                debugLog('✅ Berhasil mendapatkan map style');

                // Cek jika response adalah error
                if (mapStyle.error) {
                    throw new Error(`Server error: ${mapStyle.message || mapStyle.error}`);
                }

                // Validasi style data
                if (!mapStyle.version || !mapStyle.sources) {
                    debugLog('⚠️ Style data mungkin tidak lengkap, mencoba continue...');
                }

                debugLog('🗺️ Membuat instance peta...');
                debugLog(`Style info: v${mapStyle.version}, ${Object.keys(mapStyle.sources).length} sources`);

                currentMap = new maplibregl.Map({
                    container: "map",
                    style: mapStyle,
                    center: [106.8456, -6.2088],
                    zoom: 10,
                    attributionControl: true,
                    cooperativeGestures: true
                });

                // Handle map load success
                currentMap.on('load', () => {
                    debugLog('✅ Peta berhasil di-load sepenuhnya');
                    document.getElementById('loading').style.display = 'none';

                    // Tambahkan marker
                    new maplibregl.Marker()
                        .setLngLat([106.8456, -6.2088])
                        .setPopup(new maplibregl.Popup({
                            offset: 12
                        }).setHTML("<strong>Jakarta</strong><br>Ibu Kota Indonesia"))
                        .addTo(currentMap);
                    debugLog('🎯 Marker ditambahkan');
                });

                // Handle render errors
                currentMap.on('error', (e) => {
                    debugLog('❌ Map error: ' + (e.error?.message || 'Unknown error'));
                    console.error('Detailed map error:', e);
                });

                // Handle style data loading errors
                currentMap.on('style.load', () => {
                    debugLog('🎨 Style loaded');
                });

                currentMap.on('data', (e) => {
                    if (e.dataType === 'source' && e.sourceId) {
                        debugLog(`📦 Source loaded: ${e.sourceId}`);
                    }
                });

                // Tambahkan controls setelah map ready
                currentMap.on('load', () => {
                    currentMap.addControl(new maplibregl.NavigationControl(), "top-right");
                    currentMap.addControl(new maplibregl.FullscreenControl(), "top-right");
                    currentMap.addControl(new maplibregl.ScaleControl(), 'bottom-right');
                    currentMap.addControl(new maplibregl.GeolocateControl());
                    debugLog('🎮 Controls ditambahkan');
                });

            } catch (error) {
                console.error('Error initializing map:', error);
                debugLog('❌ Initialization error: ' + error.message);

                document.getElementById('loading').className = 'loading error';
                document.getElementById('loading').innerHTML =
                    'Error memuat peta: ' + error.message +
                    '<br><br><button onclick="initializeMap()">Coba Lagi</button>' +
                    '<button onclick="switchStyle(\'simple\')" style="margin-left: 10px;">Coba Style Simple</button>';
            }
        }

        // Initialize map ketika page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeMap();
        });
    </script>
</body>

</html>