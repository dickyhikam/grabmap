// Switch map style (for future use)
async function switchStyle(styleType) {
    const endpoints = {
        normal: "/api/map-style",
        simple: "/api/map-style-simple",
        clean: "/api/map-style-clean",
    };

    const endpoint = endpoints[styleType] || "/api/map-style";
    await initializeMap(endpoint);
}

// Initialize map with specified style
async function initializeMap(styleEndpoint = "/api/map-style-clean") {
    try {
        // Show enhanced loading
        showLoading("Loading Maps", "Initializing map components...");

        // Simulate loading steps
        setTimeout(() => {
            showLoadDetails("✓ Loading map style...");
        }, 500);

        setTimeout(() => {
            showLoadDetails(
                "✓ Loading map style...<br>✓ Setting up controls..."
            );
        }, 1000);

        const response = await fetch(styleEndpoint);
        if (!response.ok)
            throw new Error(
                `HTTP ${response.status}: ${await response.text()}`
            );

        const mapStyle = await response.json();
        if (mapStyle.error) throw new Error(mapStyle.message || mapStyle.error);

        showLoadDetails(
            "✓ Loading map style...<br>✓ Setting up controls...<br>✓ Finalizing map..."
        );

        currentMap = new maplibregl.Map({
            container: "map",
            style: mapStyle,
            center: locFirst,
            zoom: 13,
            attributionControl: true,
        });

        currentMap.on("load", () => {
            setTimeout(() => {
                showLoadDetails(
                    "✓ Loading map style...<br>✓ Setting up controls...<br>✓ Finalizing map...<br>✓ Map ready!"
                );

                // Add controls
                currentMap.addControl(
                    new maplibregl.NavigationControl(),
                    "top-right"
                );
                currentMap.addControl(
                    new maplibregl.FullscreenControl(),
                    "top-right"
                );
                currentMap.addControl(
                    new maplibregl.ScaleControl(),
                    "bottom-right"
                );
                currentMap.addControl(new maplibregl.GeolocateControl());

                // Show success and hide
                setLoadingSuccess("Maps Loaded Successfully");
            }, 500);
        });
    } catch (error) {
        console.error("Error initializing map:", error);
        setLoadingError("Failed to Load Maps");

        const loadDetails = document.getElementById("load-details");
        loadDetails.innerHTML = `Error: ${error.message}<br><br>
            <button class="retry-btn" onclick="initializeMap()">
                <i class="fas fa-redo"></i> Try Again
            </button>`;
    }
}

// ====== SHOW LOCATION ON MAP ======
function showLocation(lon, lat, label = "") {
    console.log("Showing location:", { lon, lat, label });

    // Pastikan map sudah siap
    if (!currentMap) {
        console.error("Map not initialized");
        showToast("Map is not ready yet", "error");
        return;
    }

    const LON = Number(lon);
    const LAT = Number(lat);

    // Validasi koordinat
    if (!isFinite(LON) || !isFinite(LAT)) {
        console.error("Invalid coordinates:", { lon, lat });
        showToast("Invalid location coordinates", "error");
        return;
    }

    const coordinates = [LON, LAT];

    try {
        // Clear existing markers terlebih dahulu
        clearMarkers();

        // Add marker baru
        addMarkerToMap(coordinates, label);

        // Fly to location
        currentMap.flyTo({
            center: coordinates,
            zoom: 16, // Zoom level lebih dekat
            speed: 1.5,
            curve: 1.2,
            essential: true,
        });

        // Show success message
        if (label) {
            showToast(`Showing: ${label}`, "success");
        }
    } catch (error) {
        console.error("Error showing location:", error);
        showToast("Failed to show location on map", "error");
    }
}

// Function untuk menambahkan marker
function addMarkerToMap(coordinates, title = "") {
    if (!currentMap) return null;

    // Create popup content
    const popupContent = title
        ? `<div class="map-popup"><h3>${escapeHtml(title)}</h3></div>`
        : `<div class="map-popup"><h3>Selected Location</h3></div>`;

    // Create marker dengan popup
    const marker = new maplibregl.Marker({
        color: "#00ba4e",
        draggable: false,
    })
        .setLngLat(coordinates)
        .setPopup(
            new maplibregl.Popup({
                offset: 25,
                closeOnClick: false,
            }).setHTML(popupContent)
        )
        .addTo(currentMap);

    // Simpan marker ke array
    if (!window.markers) {
        window.markers = [];
    }
    window.markers.push(marker);

    // Auto open popup
    setTimeout(() => {
        marker.togglePopup();
    }, 1000);

    return marker;
}

// Function untuk clear semua markers
function clearMarkers() {
    if (window.markers && window.markers.length > 0) {
        window.markers.forEach((marker) => marker.remove());
        window.markers = [];
    }
}
