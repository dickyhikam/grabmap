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

    searchGeocode(searchText);
}

// ====== SEARCH GEOCODING (teks -> daftar tempat) ======
async function searchGeocode(search) {
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
        displayGeocodeResults(results, search);
    } catch (error) {
        console.error("Geocoding error:", error);
        displayGeocodeError();
    }
}

// Function untuk menampilkan hasil geocoding
function displayGeocodeResults(results, searchQuery) {
    const resultBox = document.getElementById("resultBox");
    const texts = languageTexts[currentLanguage];

    if (!results.length) {
        resultBox.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4>${texts.noResults}</h4>
                <p>${texts.tryDifferentKeywords}</p>
            </div>
        `;
        return;
    }

    let htmlContent = `
        <div class="results-container">
            <!-- Tabs Navigation -->
            <div class="results-tabs">
                <button class="tab-button active" data-tab="search-results">
                    <i class="fas fa-search"></i>
                    <span>${texts.searchResults} (${results.length})</span>
                </button>
                <button class="tab-button" data-tab="selected-locations">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${
                        texts.selectedLocations
                    } <span class="selected-count">0</span></span>
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="tab-content">
                <!-- Search Results Tab -->
                <div class="tab-pane active" id="search-results">
                    <div class="search-header">
                        <p class="search-summary">${
                            texts.foundLocations
                        } "<strong>${escapeHtml(searchQuery)}</strong>"</p>
                    </div>
                    <div class="search-results">
    `;

    results.forEach((result, index) => {
        const place = result?.Place;
        const label = place?.Label || "Unknown location";
        const pt = place?.Geometry?.Point || [];
        const lon = Number(pt[0]);
        const lat = Number(pt[1]);

        const { title, address } = splitLabel(label);
        const features = "";

        htmlContent += `
            <div class="result-item" data-location-id="${index}" data-lon="${lon}" data-lat="${lat}" data-label="${escapeHtml(
            label
        )}">
                <div class="result-header">
                    <h4 class="result-title">${escapeHtml(title || label)}</h4>
                    <span class="result-distance">10KM</span>
                </div>
                <p class="result-address">${escapeHtml(address || label)}</p>
                <div class="result-features">
                    ${features}
                </div>
                <div class="result-actions">
                    <button class="btn-outline select-location-btn" onclick="event.stopPropagation(); addToSelectedLocations(${lon}, ${lat}, '${escapeHtml(
            label
        )}', ${index})">
                        <i class="fas fa-plus"></i> ${texts.select}
                    </button>
                    <button class="btn-primary" onclick="event.stopPropagation(); getDirectionsTo(${lon}, ${lat}, '${escapeHtml(
            label
        )}')">
                        <i class="fas fa-route"></i> ${texts.directions}
                    </button>
                </div>
            </div>
        `;
    });

    htmlContent += `
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
                    <div class="selected-results" id="selected-results-list">
                        <div class="empty-selected">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>${texts.noSelectedLocations}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    resultBox.innerHTML = htmlContent;

    // Initialize tabs functionality
    // initResultsTabs();
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
