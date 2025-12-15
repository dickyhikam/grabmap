// Set up search-related event listeners
function setupSearchEvents() {
    // Search on Enter key press
    $("#searchInput").on("keypress", function (e) {
        if (e.which === 13) {
            performSearch();
        }
    });

    // Vehicle type selection
    $(".search-option").on("click", function () {
        $(".search-option").removeClass("active");
        $(this).addClass("active");
    });
}

// Perform search function
function performSearch() {
    const $input = $("#searchInput");
    const searchText = ($input.val() || "").toString().trim();
    const activeType = $(".search-option.active").data("type");

    const searchGroup = $(".search-input-group");
    const texts = languageTexts[currentLanguage];

    if (!searchText) {
        showToast(texts.validationEmpty, "error");
        $input.focus();
        return false;
    }

    showResultsLoading();
    displayGeocodeResults(searchText);
}

// ====== Show UI/UX result search and selected marker ======
async function displayGeocodeResults(search) {
    const resultBox = document.getElementById("resultBox");
    const texts = languageTexts[currentLanguage];
    let resSearch, resSelected;

    if (search.length < 3) {
        resSearch = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4>${texts.noResults}</h4>
                <p>${texts.tryDifferentKeywords}</p>
            </div>`;
    } else {
        resSearch = await searchGeocode(search, 10);
        displaySearch(resSearch, texts);
    }

    resSelected = displaySelectedMarkers(texts);

    let htmlContent = `
            <div class="results-container">
                <!-- Tabs Navigation -->
                <div class="results-tabs">
                    <button class="tab-button active" data-tab="search-results">
                        <i class="fas fa-search"></i>
                        <span>${texts.searchResults}</span>
                    </button>
                    <button class="tab-button" data-tab="selected-locations">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${texts.selectedLocations}</span>
                    </button>
                </div>

                <!-- Tab Contents -->
                <div class="tab-content">
                    <!-- Search Results Tab -->
                    <div class="tab-pane active" id="search-results">
                        <div class="search-results">
                            ${resSearch}
                        </div>
                    </div>

                    <!-- Selected Locations Tab -->
                    <div class="tab-pane" id="selected-locations">
                        <div class="selected-header">
                            <div class="selected-actions">
                                <button class="btn-outline small" onclick="clearAllSelected()">
                                    <i class="fas fa-trash"></i> ${texts.clearAll}
                                </button>
                                <button class="btn-primary small" onclick="planRouteForSelected()">
                                    <i class="fas fa-route"></i> ${texts.planRoute}
                                </button>
                            </div>
                        </div>
                        <div class="selected-results">
                            ${resSelected}
                        </div>
                    </div>
                </div>
            </div>`;

    resultBox.innerHTML = htmlContent;

    initResultsTabs();
}

// ====== SEARCH GEOCODING (teks -> daftar tempat) ======
async function searchGeocode(search, countResult) {
    if (search.length < 3) {
        // hindari call API terlalu sering saat input pendek
        return;
    }

    const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/text?key=${apiKey}`;

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                Text: search,
                MaxResults: countResult,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const results = data["Results"] || [];

        // Render results ke HTML
        // return displaySearch(results, texts);
        return results;
    } catch (error) {
        console.error("Geocoding error:", error);
        displayGeocodeError();
    }
}
// ====== LOCATION GEOCODING (teks -> daftar tempat) ======
async function locationGeocode(lat, lon, countResult) {
    // if (search.length < 3) {
    //     // hindari call API terlalu sering saat input pendek
    //     return;
    // }

    const url = `https://places.geo.${region}.amazonaws.com/places/v0/indexes/${mapPlace}/search/position?key=${apiKey}`;

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                Position: [lon, lat],
                MaxResults: countResult,
            }), // ⚠️ urutan [lon, lat]
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const results = data["Results"] || [];

        // Render results ke HTML
        // return displaySearch(results, texts);
        return results;
    } catch (error) {
        console.error("Geocoding error:", error);
        displayGeocodeError();
    }
}

function displaySearch(results, texts) {
    if (!results || results.length === 0) {
        return `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4>${texts.noResults}</h4>
                <p>${texts.tryDifferentKeywords}</p>
            </div>`;
    }

    let htmlContent = "";

    results.forEach((result, index) => {
        const place = result?.Place;
        const label = place?.Label || "Unknown location";
        const pt = place?.Geometry?.Point || [];
        const lon = Number(pt[0]);
        const lat = Number(pt[1]);

        const { title, body } = splitLabel(label);
        const features = "";

        const res_label = escapeHtml(label);
        const res_title = escapeHtml(title || label);
        const res_address = escapeHtml(body || label);
        // console.log(splitLabel(label));

        htmlContent += `
            <div class="result-item" onclick="showLocation(${lon}, ${lat}, &quot;${res_title}&quot;, &quot;${res_address}&quot;)">
                <div class="result-header">
                    <h4 class="result-title">${res_title}</h4>
                    <span class="result-distance">10KM</span>
                </div>
                <p class="result-address">${res_address}</p>
                <div class="result-features">
                    ${features}
                </div>
                <div class="result-actions">
                    <button class="btn-outline select-location-btn" onclick="event.stopPropagation(); addToSelectedLocations(${lon}, ${lat}, '${res_label}', ${index})">
                        <i class="fas fa-plus"></i> ${texts.select}
                    </button>
                    <button class="btn-primary" onclick="event.stopPropagation(); getDirectionsTo(${lon}, ${lat}, '${res_label}')">
                        <i class="fas fa-route"></i> ${texts.directions}
                    </button>
                </div>
            </div>`;
    });

    return htmlContent;
}

function refreshSelectedLocationsList() {
    const selectedList = document.querySelector(".selected-results");
    const texts = languageTexts[currentLanguage];

    if (selectedLocations.length === 0) {
        selectedList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <h4>${texts.noSelectedLocations}</h4>
                <p>${texts.noSelectedLocations}</p>
            </div>`;
        return;
    }
    console.log(displaySelectedMarkers(texts));

    selectedList.innerHTML = displaySelectedMarkers(texts);
}

function displaySelectedMarkers(texts) {
    if (!selectedLocations || selectedLocations.length === 0) {
        return `
            <div class="empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <h4>${texts.noSelectedLocations}</h4>
                <p>${texts.noSelectedLocations}</p>
            </div>`;
    }

    let htmlContent = "";

    selectedLocations.forEach((location, index) => {
        // PERBAIKAN: Gunakan struktur data yang konsisten
        const lon = Number(location.latlong[0]);
        const lat = Number(location.latlong[1]);
        const res_title = escapeHtml(
            location.label || location.title || "Unknown"
        );
        const res_address = escapeHtml(
            location.address || location.body || "No address"
        );

        htmlContent += `
            <div class="result-item selected-location-item" onclick="showLocation(${lon}, ${lat}, '${res_title}', '${res_address}')">
                <div class="result-header">
                    <h4 class="result-title">${res_title}</h4>
                    <span class="result-distance">10KM</span>
                </div>
                <p class="result-address">${res_address}</p>
                <div class="result-features">
                    <span class="result-feature">
                        <i class="fas fa-bookmark"></i> Saved
                    </span>
                </div>
                <div class="result-actions">
                    <button class="btn-outline" onclick="event.stopPropagation(); removeFromSelectedLocations(${location.id})">
                        <i class="fas fa-trash"></i> ${texts.remove}
                    </button>
                    <button class="btn-primary" onclick="event.stopPropagation(); getDirectionsTo(${lon}, ${lat}, '${res_title}')">
                        <i class="fas fa-route"></i> ${texts.directions}
                    </button>
                </div>
            </div>`;
    });

    return htmlContent;
}

// Function untuk error state
function displayGeocodeError() {
    const resultBox = document.getElementById("resultBox");
    const texts = languageTexts[currentLanguage];

    resultBox.innerHTML = `
        <div class="error-state">
            <i class="fas fa-exclamation-triangle"></i>
            <h4>${texts.searchError}</h4>
            <p>${texts.searchErrorDesc}</p>
            <button class="retry-btn" onclick="performSearch()">
                <i class="fas fa-redo"></i> ${texts.tryAgain}
            </button>
        </div>
    `;
}

// Helper function untuk show loading dalam result box
function showResultsLoading(message = null) {
    const resultBox = document.getElementById("resultBox");
    const texts = languageTexts[currentLanguage];

    resultBox.innerHTML = `
        <div class="results-loading">
            <i class="fas fa-spinner"></i>
            <p>${message || texts.searching || "Searching..."}</p>
        </div>
    `;
}

// Initialize tabs functionality
function initResultsTabs() {
    const tabButtons = document.querySelectorAll(".tab-button");
    const tabPanes = document.querySelectorAll(".tab-pane");

    tabButtons.forEach((button) => {
        console.log(button);

        button.addEventListener("click", function () {
            const targetTab = this.getAttribute("data-tab");

            // Remove active class dari semua buttons dan panes
            tabButtons.forEach((btn) => btn.classList.remove("active"));
            tabPanes.forEach((pane) => pane.classList.remove("active"));

            // Add active class ke clicked button dan target pane
            this.classList.add("active");
            document.getElementById(targetTab).classList.add("active");

            // Jika pindah ke selected tab, refresh list
            // if (targetTab === "selected-locations") {
            //     refreshSelectedLocationsList();
            // }
        });
    });
}

// Function untuk switch ke Search Results tab
function switchToSearchResultsTab() {
    console.log("Attempting to switch to Search Results tab...");

    // Method 1: Gunakan event click pada tab button
    const searchTabButton = document.querySelector(
        '.tab-button[data-tab="search-results"]'
    );

    if (searchTabButton) {
        console.log("Found search tab button, triggering click...");
        searchTabButton.click(); // Trigger click event
        return true;
    }

    // Method 2: Manual switching jika Method 1 gagal
    const selectedTabButton = document.querySelector(
        '.tab-button[data-tab="selected-locations"]'
    );
    const searchTabPane = document.getElementById("search-results");
    const selectedTabPane = document.getElementById("selected-locations");

    if (
        searchTabButton &&
        selectedTabButton &&
        searchTabPane &&
        selectedTabPane
    ) {
        console.log("Manual tab switching...");

        // Remove active class dari semua tabs
        document.querySelectorAll(".tab-button").forEach((btn) => {
            btn.classList.remove("active");
        });
        document.querySelectorAll(".tab-pane").forEach((pane) => {
            pane.classList.remove("active");
        });

        // Add active class ke search tab
        searchTabButton.classList.add("active");
        searchTabPane.classList.add("active");

        console.log("Tab switched successfully");
        return true;
    }

    console.error("Tab elements not found");
    return false;
}
