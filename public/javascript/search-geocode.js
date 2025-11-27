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
    const searchText = $("#searchInput").val().trim();
    const activeType = $(".search-option.active").data("type");

    const searchGroup = $(".search-input-group");
    const texts = languageTexts[currentLanguage];

    if (!searchText) {
        showToast(texts.validationEmpty, "error");
        // Focus on input
        $("#searchInput").focus();

        return false;
    }

    // Show loading state
    showResultsLoading();

    // Logic geocoding search
    displayGeocodeResults(searchText);
}

// ====== Show UI/UX result search and selected marker ======
async function displayGeocodeResults(search) {
    const resultBox = document.getElementById("resultBox");
    const texts = languageTexts[currentLanguage];
    let resSearch;

    if (search.length < 3) {
        resSearch = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4>${texts.noResults}</h4>
                <p>${texts.tryDifferentKeywords}</p>
            </div>`;
    } else {
        resSearch = await searchGeocode(search, texts);
        console.log(resSearch);
    }

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
                        <div class="search-results" id="selected-results-list">
                            <div class="empty-state">
                                <i class="fas fa-map-marker-alt"></i>
                                <h4>${texts.noSelectedLocations}</h4>
                                <p>${texts.noSelectedLocations}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

    resultBox.innerHTML = htmlContent;

    initResultsTabs();
}

// ====== SEARCH GEOCODING (teks -> daftar tempat) ======
async function searchGeocode(search, texts) {
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
                MaxResults: 10,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const results = data["Results"] || [];

        // Render results ke HTML
        return displaySearch(results, texts);
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

        const { title, address } = splitLabel(label);
        const features = "";

        const res_label = escapeHtml(label);
        const res_title = escapeHtml(title || label);
        const res_address = escapeHtml(address || label);

        htmlContent += `
            <div class="result-item" onclick="showLocation(${lon}, ${lat}, &quot;${res_label}&quot;)">
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
        button.addEventListener("click", function () {
            const targetTab = this.getAttribute("data-tab");

            // Remove active class dari semua buttons dan panes
            tabButtons.forEach((btn) => btn.classList.remove("active"));
            tabPanes.forEach((pane) => pane.classList.remove("active"));

            // Add active class ke clicked button dan target pane
            this.classList.add("active");
            document.getElementById(targetTab).classList.add("active");

            // Jika pindah ke selected tab, refresh list
            if (targetTab === "selected-locations") {
                refreshSelectedLocationsList();
            }
        });
    });
}
