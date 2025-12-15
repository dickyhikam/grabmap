<!DOCTYPE html>
<html lang="en"> <!-- Changed to English -->

<head>
    <meta charset="utf-8" />
    <title>GrabMaps</title>

    <!-- Meta description in English -->
    <meta name="description" content="GrabMaps is an interactive map platform that provides accurate location information. Explore the map to discover interesting places." />
    <link rel="icon" href="images.png" type="image/x-icon" />

    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- External CSS libraries -->
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style-alert.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style-loading.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/search.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/tab.css') }}" />

    <!-- Map library -->
    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>
</head>

<body>
    <!-- Full-width Header -->
    <div id="header">
        <!-- Logo Section -->
        <a href="{{ route('pageHome') }}">
            <img src="logo.png" alt="GrabMaps Logo" style="cursor: pointer;">
        </a>

        <!-- Button Section -->
        <div class="header-buttons">
            <!-- Language Dropdown -->
            <div class="language-dropdown-container">
                <button class="language-btn" id="languageBtn">
                    <span class="language-text">EN</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="language-dropdown" id="languageDropdown">
                    <div class="language-option active" data-lang="en">
                        <span>English</span>
                    </div>
                    <div class="language-option" data-lang="id">
                        <span>Indonesia</span>
                    </div>
                </div>
            </div>

            <button class="btn-history" id="btnToggleHistory" onclick="toggleHistory()">
                <i class="fas fa-history"></i> History <span id="historyCountBadge" style="display:none; background:#eee; padding:0 5px; border-radius:10px; font-size:10px;">0</span>
            </button>

            <!-- view count scraping proses -->
            <div class="quota-badge" id="quotaBadge">
                <i class="fas fa-bolt"></i>
                <span>Credits: <b id="quotaCount">0</b>/5</span>
            </div>
        </div>
    </div>

    <div id="historySidebar" class="history-sidebar">
        <div class="history-header">
            <h4><i class="fas fa-list"></i> Activity Log</h4>
            <button onclick="toggleHistory()" style="border:none; background:transparent; cursor:pointer; font-size:16px; color:#999;"><i class="fas fa-times"></i></button>
        </div>
        <div class="history-content" id="historyListContainer">
            <div class="empty-history">
                <i class="fas fa-search" style="font-size: 30px; margin-bottom: 10px; opacity: 0.3;"></i>
                <p>No scraping history yet.</p>
            </div>
        </div>
    </div>

    <!-- Main container for map and sidebar -->
    <div class="container-result split-layout">
        <!-- Sidebar section -->
        <div class="sidebar-panel2">

            <div class="sidebar-header">
                <h2 id="titleScrap">POI Data Scraping</h2>
                <p class="subtitle" id="descripScrap">
                    Search for an address and select a location to collect data from the surrounding area.
                </p>
            </div>

            <div class="gemini-input-group big-search">
                <textarea id="geminiSearchInput" class="gemini-textarea" placeholder="Search for an address..." rows="1"></textarea>
                <button id="btnSearchGemini" class="gemini-send-btn" onclick="performSearch()">
                    <!-- <i class="fas fa-cloud-download-alt"></i> -->
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div class="gemini-helper split">
                <div class="specs-tags">
                    <span class="tag"><i class="fas fa-bullseye"></i> Radius: 100m</span>
                    <span class="tag"><i class="fas fa-list-ol"></i> Max: 50 POI</span>
                </div>
                <div class="helper-text">Press <b>Enter</b> to extract</div>
            </div>

            <div id="verificationResult" class="verification-container"></div>
        </div>

        <!-- Map container -->
        <div id="map"></div>

        <!-- Loading overlay -->
        <div id="loading" class="loading">
            <div class="loading-container">
                <!-- Loading Animation -->
                <div class="loading-spinner">
                    <div class="spinner-circle"></div>
                    <div class="spinner-inner"></div>
                </div>

                <!-- Loading Text -->
                <div class="loading-text">Loading Maps<span class="loading-dots"></span></div>
                <div class="loading-subtext">Preparing your navigation experience</div>

                <!-- Progress Bar -->
                <div class="loading-progress">
                    <div class="progress-bar"></div>
                </div>

                <!-- Loading Details -->
                <div id="load-details" class="loading-details">
                    <!-- Details will be shown here -->
                </div>
            </div>
        </div>
    </div>

    <div id="scrapModal" class="custom-modal-overlay" style="display: none;">
        <div class="custom-modal-box">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h3>Confirm Data Scraping</h3>
            <p>This action will deduct <strong>1 Credit</strong> from your balance.</p>

            <div class="quota-info-box">
                <div class="q-row">
                    <span>Current Credits:</span>
                    <span id="modalCurrentQuota" style="font-weight:bold;">5</span>
                </div>
                <div class="q-row" style="color: #d9534f;">
                    <span>Cost:</span>
                    <span>-1</span>
                </div>
                <hr>
                <div class="q-row">
                    <span>New Balance:</span>
                    <b id="modalFutureQuota" style="color: #00B14F;">4</b>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeScrapModal()">Cancel</button>
                <button class="btn-confirm" onclick="executeScraping()">Yes, Scraping Data</button>
            </div>
        </div>
    </div>

    <!-- JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('javascript/custom-loading.js') }}"></script>
    <script src="{{ asset('javascript/custom.js') }}"></script>
    <!-- <script src="{{ asset('javascript/maps.js') }}"></script> -->
    <script src="{{ asset('javascript/search-geocode.js') }}"></script>

    <script>
        // ====== KONFIGURASI DASAR (DIISI DARI ENV/LARAVEL) ======
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const mapPlace = "{{ env('AWS_MAP_PLACE') }}";
        const mapRoute = "{{ env('AWS_MAP_ROUTE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";
    </script>

    <script src="{{ asset('javascript/page/scraping.js') }}"></script>
</body>

</html>