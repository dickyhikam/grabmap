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
