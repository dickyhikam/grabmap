// variable global localdata
const deviceId = getOrCreateDeviceId();
const STORAGE_KEY = `snippets_${deviceId}`;
const QUOTA_KEY = `quota_${deviceId}`;
const MAX_QUOTA = 5;
// Load Quota
let storedQuota = localStorage.getItem(QUOTA_KEY);
let currentQuota = storedQuota === null ? MAX_QUOTA : parseInt(storedQuota);

// variable reset Quota scraping
let secretClickCount = 0;
let secretClickTimer;

// variable MAPS
let map;
let mapMarkers = []; // <--- Variable for map markers
let clickMarker = null; // special marker for map clicks
let locFirst = [106.8456, -6.2088];
let radiusMeters = 500;

let tempScrapData = null; // Store temporary data for scraping

// Initialize when document is ready
$(document).ready(function () {
    initLanguage();
    updateQuotaUI();
    initMap();
    renderHistory();

    // Search button using Enter key
    $("#geminiSearchInput").keypress(function (e) {
        if (e.which == 13 && !e.shiftKey) {
            e.preventDefault();
            performSearch();
        }
    });

    // --- SECRET FEATURE: FACTORY RESET (5x Clicks on Quota) ---
    $("#quotaBadge")
        .attr("title", "Secret Reset")
        .on("click", function () {
            // Clear previous timer
            clearTimeout(secretClickTimer);

            secretClickCount++;

            if (secretClickCount >= 5) {
                // === ACTION: TOTAL RESET ===

                // 1. Reset Quota to 5
                currentQuota = MAX_QUOTA;
                localStorage.setItem(QUOTA_KEY, currentQuota);

                // 2. CLEAR HISTORY (LocalStorage & UI)
                localStorage.removeItem(STORAGE_KEY);
                $("#historyListBody").empty();
                $("#historyCard").fadeOut();

                // 3. Update Quota Display
                updateQuotaUI();

                // 4. Re-enable Input
                $("#btnSearchGemini")
                    .prop("disabled", false)
                    .css("background-color", "");
                $("#geminiSearchInput")
                    .prop("disabled", false)
                    .attr("placeholder", "Search for an address...");

                // 5. Notification
                showToast(
                    "System Reset: Quota restored & History cleared!",
                    "info"
                );

                // Reset click counter
                secretClickCount = 0;
                return;
            }

            // Reset counter if idle for 400ms
            secretClickTimer = setTimeout(() => {
                secretClickCount = 0;
            }, 400);
        });
});

// =============================
// button Search Address
// =============================
async function performSearch() {
    const address = $("#geminiSearchInput").val().trim();
    const texts = languageTexts[currentLanguage];

    // Input Validation
    if (!address) {
        showToast("Please enter an address first.", "error");
        return;
    }

    // Input length validation
    if (address.length < 5) {
        showToast(
            "Please enter an address with at least 5 characters.",
            "error"
        );
        return;
    }

    // Quota Validation
    if (currentQuota <= 0) {
        showToast("Quota limit reached (0/5).", "error");
        return;
    }

    // Show search list container
    const resultContainer = $("#verificationResult");
    // Loading State
    resultContainer.show().html(`
                <div class="results-loading">
                    <i class="fas fa-spinner"></i>
                    <p>${texts.searching || "Searching..."}</p>
                </div>`);

    // Get data address with geocode
    const resSearch = await searchGeocode(address.toLowerCase(), 20);

    resultListApiData(resSearch); // Show data API in the list card

    showMapMarkers(resSearch);
}

// Manage API Data Results
function resultListApiData(rawResults) {
    const texts = languageTexts[currentLanguage];
    let html = "";

    // Scrollable wrapper
    html += `<div class="results-scroll-container">`;

    // Loop through all data
    rawResults.forEach((result, index) => {
        // Data processing helper
        const place = result.Place;
        const pt = place?.Geometry?.Point || [0, 0];
        let displayTitle = place.Label;
        let displayAddress = place.Label;
        let latitude = pt[1];
        let longitude = pt[0];

        if (typeof splitLabel === "function") {
            const { title, body } = splitLabel(place.Label);
            displayTitle = title || place.Label;
            displayAddress = body || "";
        }

        // Check if item is selected
        let activeClass = "normal";

        // --- LOGIC: CHECK QUOTA ---
        const isQuotaEmpty = currentQuota <= 0;
        // If empty, add disabled attribute
        const disabledAttr = isQuotaEmpty ? "disabled" : "";

        // Render Card
        html += `
                    <div id="card-${index}" class="result-card ${activeClass}" onclick="selectCard(${index}, this)">
                        <div class="icon-box">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="res-content">
                            <div style="font-weight:bold; font-size:15px; color:#333; margin-top:2px;">
                                ${displayTitle}
                            </div>
                            <p class="res-address" style="margin-bottom:5px; font-size:13px; color:#666;">
                                ${displayAddress}
                            </p>
                            <div style="font-size:12px; color:#999;">
                                <i class="fas fa-map-pin"></i> ${Number(
                                    latitude
                                ).toFixed(5)}, ${Number(longitude).toFixed(5)}
                            </div>
                        </div>

                        <div class="action-box">
                            <button class="btn-scrap-mini" 
                                onclick="saveSingleItem(event, '${displayTitle}', '${displayAddress}', ${latitude}, ${longitude})" 
                                title="Extract Data" ${disabledAttr}>
                                <i class="fas fa-cloud-download-alt"></i>
                            </button>
                        </div>
                    </div>`;
    });

    html += `</div>`; // Close scroll container
    $("#verificationResult").html(html);
}

// Select card effect
function selectCard(index, element) {
    // 1. Visual: Reset class
    $(".result-card").removeClass("selected").addClass("normal");
    $(element).removeClass("normal").addClass("selected");

    // --- MAP FLY TO ---
    if (typeof map !== "undefined" && map) {
        const pos = mapMarkers[index].getLngLat();
        const lng = pos.lng;
        const lat = pos.lat;

        map.flyTo({
            center: [lng, lat],
            zoom: 16,
            speed: 1.2,
            curve: 1.42,
            essential: true,
        });

        showRadiusCircle(lng, lat);

        // Open Popup (Optional)
        if (typeof mapMarkers !== "undefined" && mapMarkers[index]) {
            mapMarkers.forEach((m) => m.getPopup().remove());
            mapMarkers[index].togglePopup();
        }
    }
}

// =============================
// Maps Initialization
// =============================
async function initMap() {
    try {
        showLoading("Loading Maps", "Initializing map components...");

        setTimeout(() => {
            showLoadDetails("✓ Loading map style...");
        }, 500);

        setTimeout(() => {
            showLoadDetails(
                "✓ Loading map style...<br>✓ Setting up controls..."
            );
        }, 1000);

        const response = await fetch("/api/map-style-clean");
        if (!response.ok)
            throw new Error(
                `HTTP ${response.status}: ${await response.text()}`
            );

        const mapStyle = await response.json();
        if (mapStyle.error) throw new Error(mapStyle.message || mapStyle.error);

        showLoadDetails(
            "✓ Loading map style...<br>✓ Setting up controls...<br>✓ Finalizing map..."
        );

        map = new maplibregl.Map({
            container: "map",
            style: mapStyle,
            center: locFirst,
            zoom: 13,
            attributionControl: true,
        });

        map.on("load", () => {
            setTimeout(() => {
                showLoadDetails(
                    "✓ Loading map style...<br>✓ Setting up controls...<br>✓ Finalizing map...<br>✓ Map ready!"
                );

                map.addControl(new maplibregl.NavigationControl(), "top-right");
                map.addControl(new maplibregl.FullscreenControl(), "top-right");
                map.addControl(new maplibregl.ScaleControl(), "bottom-right");
                map.addControl(new maplibregl.GeolocateControl());

                map.on("geolocate", (e) => {
                    const longitude = e.coords.longitude;
                    const latitude = e.coords.latitude;
                    map.setCenter([longitude, latitude]);
                });

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

// Show Markers on Map
function showMapMarkers(dataArray) {
    if (!map) return;

    // 1. Clear Old Markers
    if (mapMarkers.length > 0) {
        mapMarkers.forEach((marker) => marker.remove());
        mapMarkers = [];
    }

    if (!dataArray || dataArray.length === 0) return;

    const bounds = new maplibregl.LngLatBounds();

    // 2. Loop Data
    dataArray.forEach((item) => {
        const pt = item.Place?.Geometry?.Point;
        if (pt) {
            const lng = pt[0];
            const lat = pt[1];

            const marker = new maplibregl.Marker({
                color: "#00b14f",
            })
                .setLngLat([lng, lat])
                .setPopup(
                    new maplibregl.Popup().setHTML(`<b>${item.Place.Label}</b>`)
                )
                .addTo(map);

            mapMarkers.push(marker);
            bounds.extend([lng, lat]);
        }
    });

    // 3. Adjust Camera Zoom
    if (!bounds.isEmpty()) {
        map.fitBounds(bounds, {
            padding: 100,
            maxZoom: 15,
            duration: 1000,
        });
    }
}

// Show Radius Circle
function showRadiusCircle(lng, lat) {
    if (!map) return;

    const circleFeature = createCirclePolygon(lng, lat);
    const data = {
        type: "FeatureCollection",
        features: [circleFeature],
    };

    if (!map.getSource("selection-radius")) {
        map.addSource("selection-radius", {
            type: "geojson",
            data: data,
        });

        map.addLayer({
            id: "selection-radius-fill",
            type: "fill",
            source: "selection-radius",
            paint: {
                "fill-color": "#00b14f",
                "fill-opacity": 0.15,
            },
        });

        map.addLayer({
            id: "selection-radius-outline",
            type: "line",
            source: "selection-radius",
            paint: {
                "line-color": "#00b14f",
                "line-width": 2,
            },
        });
    } else {
        map.getSource("selection-radius").setData(data);
    }
}

// Create Circle Polygon
function createCirclePolygon(lng, lat, points = 64) {
    const coords = [];
    const earthRadius = 6378137;

    const centerLat = (lat * Math.PI) / 180;
    const centerLng = (lng * Math.PI) / 180;
    const angularDistance = radiusMeters / earthRadius;

    for (let i = 0; i <= points; i++) {
        const bearing = (i * 2 * Math.PI) / points;

        const latRad = Math.asin(
            Math.sin(centerLat) * Math.cos(angularDistance) +
                Math.cos(centerLat) *
                    Math.sin(angularDistance) *
                    Math.cos(bearing)
        );

        const lngRad =
            centerLng +
            Math.atan2(
                Math.sin(bearing) *
                    Math.sin(angularDistance) *
                    Math.cos(centerLat),
                Math.cos(angularDistance) -
                    Math.sin(centerLat) * Math.sin(latRad)
            );

        coords.push([(lngRad * 180) / Math.PI, (latRad * 180) / Math.PI]);
    }

    return {
        type: "Feature",
        geometry: {
            type: "Polygon",
            coordinates: [coords],
        },
        properties: {},
    };
}

// =============================
// Credits Scraping UI
// =============================
function updateQuotaUI() {
    $("#quotaCount").text(currentQuota);
    if (currentQuota <= 0) {
        $("#quotaBadge").css({
            border: "1px solid #d9534f",
            background: "#fff5f5",
            color: "#d9534f",
        });
        disableInterface();
    } else {
        $("#quotaBadge").css({
            border: "none",
            background: "#fff",
            color: "#555",
        });
    }
}

function disableInterface() {
    $("#btnSearchGemini")
        .prop("disabled", true)
        .css("background-color", "#ccc");
    $("#geminiSearchInput")
        .prop("disabled", true)
        .attr("placeholder", "Credits Exhausted (0/5).");
}

// =============================
// Scraping Logic
// =============================
function processApiDataLocation(rawResults) {
    let dataResult = [];
    const radiusMeters = 100;

    for (const result of rawResults) {
        if (dataResult.length >= 50) break;

        if (
            !result.Place ||
            !result.Place.Geometry ||
            !result.Place.Geometry.Point
        )
            continue;

        const place = result.Place;

        let displayTitle = place.Label;
        let displayAddress = place.Label;

        if (typeof splitLabel === "function") {
            const { title, body } = splitLabel(place.Label);
            displayTitle = title || place.Label;
            displayAddress = body || "";
        }

        if (displayTitle.includes("Jalan") || displayTitle.includes("Jl")) {
            continue;
        }

        const itemLat = place.Geometry.Point[1];
        const itemLng = place.Geometry.Point[0];

        if (tempScrapData) {
            const distance = calculateStraightLineDistance(
                tempScrapData.lat,
                tempScrapData.lng,
                itemLat,
                itemLng
            );
            if (distance > radiusMeters) continue;
        }

        dataResult.push({
            name: displayTitle,
            address: displayAddress,
            street: place.Street || "",
            number: place.AddressNumber || "",
            phone:
                place.Phones && place.Phones.length > 0 ? place.Phones[0] : "-",
            lat: itemLat,
            lng: itemLng,
        });
    }

    return dataResult;
}

function calculateStraightLineDistance(lat1, lon1, lat2, lon2) {
    if (!lat1 || !lon1 || !lat2 || !lon2) return;

    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        0.5 -
        Math.cos(dLat) / 2 +
        (Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            (1 - Math.cos(dLon))) /
            2;

    const distance = R * 2 * Math.asin(Math.sqrt(a));

    return distance.toFixed(2);
}

// 1. BUTTON CLICK HANDLER (OPEN MODAL)
function saveSingleItem(event, title, address, lat, lng) {
    event.stopPropagation();

    if (currentQuota <= 0) {
        showToast("Credits exhausted! Cannot perform this action.", "error");
        return;
    }

    tempScrapData = {
        title: title,
        address: address,
        lat: lat,
        lng: lng,
    };

    $("#modalCurrentQuota").text(currentQuota);
    $("#modalFutureQuota").text(currentQuota - 1);

    $("#scrapModal").fadeIn(200);
}

// 2. EXECUTION FUNCTION (CALLED BY "YES" BUTTON)
async function executeScraping() {
    if (!tempScrapData) return;

    // --- CEK HISTORY DUPLIKAT ---
    const historyData = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    // Cari apakah alamat yang mau discrap (tempScrapData.address) sudah ada?
    const existingItem = historyData.find(
        (item) =>
            item.address.trim().toLowerCase() ===
            tempScrapData.address.trim().toLowerCase()
    );
    // JIKA SUDAH ADA:
    if (existingItem) {
        console.log("Address already scraped. Loading from cache...");

        // Tutup Modal
        closeScrapModal();

        // Download ulang file CSV dari data lama
        downloadFromHistory(existingItem.id);

        // Beri notifikasi (Info, bukan Success, karena dari cache)
        showToast(`Data loaded from history (No quota deducted).`, "info");

        // HENTIKAN PROSES DI SINI (Jangan lanjut ke API/Kuota)
        return;
    }

    // --- JIKA BELUM ADA, LANJUT PROSES ASLI ---
    const resLocation = await locationGeocode(
        tempScrapData.lat,
        tempScrapData.lng,
        50
    );

    const cleanItems = processApiDataLocation(resLocation);

    currentQuota--;
    localStorage.setItem(QUOTA_KEY, currentQuota);
    updateQuotaUI();

    if (typeof saveCleanDataToStorage === "function") {
        saveCleanDataToStorage(
            tempScrapData.address,
            cleanItems.length,
            cleanItems
        );
    } else {
        console.error("Function saveCleanDataToStorage not found");
    }

    closeScrapModal();
    showToast(`Successfully saved: ${cleanItems.length} POIs`, "success");

    if (currentQuota <= 0) {
        disableInterface();
    }
}

function saveCleanDataToStorage(address, count, items) {
    let data = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    let id = Date.now();
    data.unshift({
        id: id,
        address: address,
        count: count,
        createdAt: new Date().toISOString(),
        items: items,
    });
    if (data.length > 20) data.pop();
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    downloadFromHistory(id);
    renderHistory();
}

function closeScrapModal() {
    $("#scrapModal").fadeOut(200);
    tempScrapData = null;
}

function downloadFromHistory(snippetId) {
    const allData = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    const targetSnippet = allData.find((item) => item.id === snippetId);

    if (
        !targetSnippet ||
        !targetSnippet.items ||
        targetSnippet.items.length === 0
    ) {
        showToast("Data not found or empty.", "error");
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Name,Address,Phone,Latitude,Longitude\n";

    targetSnippet.items.forEach((row) => {
        const name = `"${String(row.name || "").replace(/"/g, '""')}"`;
        const address = `"${String(row.address || "").replace(/"/g, '""')}"`;
        const phone = `"${String(row.phone || "-")}"`;
        const lat = row.lat;
        const lng = row.lng;

        csvContent += `${name},${address},${phone},${lat},${lng}\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);

    const safeName = targetSnippet.address.replace(/[^a-zA-Z0-9]/g, "_");
    link.setAttribute("download", `POI_${safeName}_${targetSnippet.id}.csv`);

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast("Downloading CSV file...", "info");
}

function toggleHistory() {
    $("#historySidebar").toggleClass("open");
    $("#btnToggleHistory").toggleClass("active");
}

function renderHistory() {
    const container = $("#historyListContainer");
    const badge = $("#historyCountBadge");

    const data = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];

    if (data.length === 0) {
        badge.hide();
        container.html(`
            <div class="empty-history">
                <i class="fas fa-search-location"></i>
                <h5>No Activity Yet</h5>
                <p>Your scraping history will appear here.</p>
            </div>
        `);
        return;
    }

    badge.text(data.length).show();

    let html = "";

    data.forEach((item) => {
        // --- UX: READABLE DATE FORMAT ---
        let dateObj = new Date(item.createdAt);
        let dateStr = dateObj.toLocaleDateString("en-US", {
            day: "numeric",
            month: "short",
        });
        let timeStr = dateObj.toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
        });
        let niceDate = `${dateStr}, ${timeStr}`;

        let safeAddress = item.address.replace(/'/g, "\\'");

        html += `
            <div class="history-item">
                <div class="h-left">
                    <div class="h-icon-circle">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    
                    <div class="h-info">
                        <div class="h-address" title="${item.address}">${item.address}</div>
                        <div class="h-meta">
                            <i class="far fa-clock"></i> ${niceDate}
                        </div>
                    </div>
                </div>

                <div class="h-right">
                    <span class="poi-badge">${item.count} Items</span>
                    
                    <button class="btn-download-icon" onclick="downloadFromHistory(${item.id})">
                        <i class="fas fa-download"></i> CSV
                    </button>
                </div>
            </div>
        `;
    });

    container.html(html);
}
