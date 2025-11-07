<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>GrabMaps via Amazon Location + MapLibre</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- MapLibre CSS/JS -->
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />
    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        #app {
            height: 100%;
            display: grid;
            grid-template-rows: auto 1fr;
        }

        header {
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-family: system-ui, sans-serif;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        select,
        button {
            padding: 6px 10px;
        }

        .spacer {
            flex: 1;
        }
    </style>
</head>

<body>
    <div id="app">
        <!-- <header>
            <strong>GrabMaps (AWS)</strong>
            <label>
                Style:
                <select id="stylePicker">
                    <option value="VectorGrabStandardLight">VectorGrabStandardLight</option>
                    <option value="VectorGrabStandardDark">VectorGrabStandardDark</option>
                </select>
            </label>
            <div class="spacer"></div>
            <button id="resetBtn">Reset ke Indonesia</button>
        </header> -->


    </div>
    <div id="map"></div>

    <script>
        // ====== KONFIGURASI ======
        // GrabMaps hanya tersedia di ap-southeast-1 (Singapore)
        const region = "{{ env('AWS_REGION') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";

        // Center default: Indonesia (Jakarta)
        const defaultCenter = [106.8456, -6.2088]; // [lng, lat]
        const defaultZoom = 15;

        // ====== INISIALISASI PETA ======
        const map = new maplibregl.Map({
            container: "map",
            style: `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`,
            center: defaultCenter,
            zoom: defaultZoom,
            attributionControl: true,
            cooperativeGestures: true
        });

        map.addControl(new maplibregl.NavigationControl({
            visualizePitch: true
        }), "top-right"); // Zoom and rotation controls
        map.addControl(new maplibregl.FullscreenControl(), "top-right"); // Fullscreen control
        map.addControl(new maplibregl.ScaleControl({
            maxWidth: 120,
            unit: "metric"
        }), 'bottom-right'); // Scale control in bottom-right
        map.addControl(new maplibregl.GeolocateControl()); // Geolocation control

        // Contoh sederhana: tambah marker di Jakarta
        map.on("load", () => {
            new maplibregl.Marker().setLngLat(defaultCenter).setPopup(
                new maplibregl.Popup({
                    offset: 12
                }).setText("Jakarta")
            ).addTo(map);
        });
    </script>
</body>

</html>