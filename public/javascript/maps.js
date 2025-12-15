// Global variables
let currentMap = null;
let locFirst = [106.8456, -6.2088]; // Default coordinates (Jakarta)

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
                // Listen for geolocate event to capture coordinates
                currentMap.on("geolocate", (e) => {
                    console.log(e);

                    const longitude = e.coords.longitude;
                    const latitude = e.coords.latitude;
                    console.log("Longitude:", longitude);
                    console.log("Latitude:", latitude);

                    // You can now use the longitude and latitude as needed
                    // For example, you could set the map's center based on these coordinates
                    currentMap.setCenter([longitude, latitude]);
                });

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
function showLocation(lon, lat, label = "", address = "") {
    // Pastikan map sudah siap
    if (!currentMap) {
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
        // 1. Switch tab ke Search Results
        switchToSearchResultsTab();

        // 2. Clear existing markers terlebih dahulu
        // clearMarkers();

        // 3. Add marker baru
        // addMarkerToMap(coordinates, label, address);

        // 4. Fly to location
        // flyLocation(LON, LAT);

        // 5. Show success message
        if (label) {
            showToast(`Showing: ${label}`, "success");
        }

        // 6. Update list selected location
        refreshSelectedLocationsList();
    } catch (error) {
        console.error("Error showing location:", error);
        showToast("Failed to show location on map", "error");
    }
}

function flyLocation(lon, lat) {
    const ll = [lon, lat]; // Menyimpan koordinat lon dan lat dalam array ll

    // Mengarahkan kamera ke lokasi marker yang sudah ada
    currentMap.flyTo({
        center: ll,
        zoom: 15, // level zoom dapat disesuaikan
        speed: 1.2,
        curve: 1,
        essential: true,
    });

    // Mencari marker berdasarkan koordinat dan menampilkan popup
    selectedLocations.forEach((marker) => {
        const markerLngLat = marker.latlong;
        if (markerLngLat.lng === lon && markerLngLat.lat === lat) {
            setTimeout(() => {
                marker.togglePopup();
            }, 1000);
        }
    });
}

// Function untuk menambahkan marker
function addMarkerToMap(coordinates, title = "", address = "") {
    const markerId = selectedLocations.length + 1;

    if (!currentMap) return null;

    // Create popup content
    const popupContent = title
        ? `<div class="map-popup"><h3>${escapeHtml(title)}</h3></div>`
        : `<div class="map-popup"><h3>Selected Location</h3></div>`;

    // Create marker dengan popup
    const marker = new maplibregl.Marker({
        color: "#00b14f",
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
    selectedLocations.push({
        id: markerId,
        latlong: coordinates,
        title: escapeHtml(title),
        address: escapeHtml(address),
    });

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
