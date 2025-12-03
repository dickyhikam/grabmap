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

            <!-- Reset Maps Button -->
            <button class="button btn btn-success" onclick="resetMaps();">Reset Maps</button>
        </div>
    </div>

    <!-- Main container for map and sidebar -->
    <div class="container-result">
        <!-- Sidebar section -->
        <div class="container-box">
            <div class="search-box">
                <!-- Search Input Section -->
                <div class="search-input-group">
                    <input type="text" class="search-input" placeholder="Search for address, place, or location..." id="searchInput">
                    <button class="search-button" onclick="performSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Search Options Row -->
                <div class="search-options-row">
                    <!-- Vehicle Type Selection -->
                    <div class="search-options">
                        <div class="search-option active" data-type="car">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="search-option" data-type="motorcycle">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                    </div>

                    <!-- Current Location Toggle dengan ON/OFF -->
                    <div class="location-toggle-container">
                        <div class="toggle-switch">
                            <input type="checkbox" id="locationToggle" class="toggle-checkbox">
                            <label for="locationToggle" class="toggle-label">
                                <span class="toggle-text">
                                    <i class="fas fa-crosshairs"></i>
                                    <span class="toggle-state">
                                        <span class="toggle-on">ON</span>
                                        <span class="toggle-off">OFF</span>
                                    </span>
                                </span>
                            </label>
                            <!-- Tooltip Element -->
                            <div class="toggle-tooltip" id="txt-loc-markers">
                                Search nearest location from selected maker
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results container - dynamically populated -->
            <div class="result-box" id="resultBox"></div>
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



    <!-- JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('javascript/custom-loading.js') }}"></script>
    <script src="{{ asset('javascript/custom.js') }}"></script>
    <script src="{{ asset('javascript/maps.js') }}"></script>
    <script src="{{ asset('javascript/search-geocode.js') }}"></script>

    <script>
        // ====== KONFIGURASI DASAR (DIISI DARI ENV/LARAVEL) ======
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const mapPlace = "{{ env('AWS_MAP_PLACE') }}";
        const mapRoute = "{{ env('AWS_MAP_ROUTE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";

        // Global variables
        let currentMap = null;
        let locFirst = [106.8456, -6.2088]; // Default coordinates (Jakarta)

        // Global variables untuk selected locations
        let selectedLocations = [];

        // Initialize when document is ready
        $(document).ready(function() {
            // initializeMap();
            setupSearchEvents();

            initLanguage();

            // showWelcomeMessage();
            // performSearch();
        });

        // Location selection handler
        function selectLocation(locationId) {
            console.log('Location selected:', locationId);
            // TODO: Add logic to highlight location on map
        }

        // Save location to favorites
        function saveLocation(locationId) {
            console.log('Location saved:', locationId);
            alert('Location saved to favorites!');
            // TODO: Add logic to save location to user favorites
        }

        // Get directions to location
        function getDirections(locationId) {
            console.log('Getting directions to:', locationId);
            alert('Getting directions...');
            // TODO: Add logic to calculate and display route
        }

        // Reset map and clear search
        function resetMaps() {
            initializeMap(); // Reinitialize map
            showWelcomeMessage(); // Show welcome screen
            // Reset vehicle type to default (car)
            $('.search-option').removeClass('active');
            $('.search-option[data-type="car"]').addClass('active');
        }

        // Display welcome message in results panel
        function showWelcomeMessage() {
            const searchInput = $('#searchInput');
            searchInput.val('');

            const texts = languageTexts[currentLanguage];
            const resultBox = document.getElementById('resultBox');
            resultBox.innerHTML = `
                <div class="welcome-message">
                    <h3>${texts.welcomeTitle}</h3>
                    <div class="welcome-content">
                        <div class="welcome-feature">
                            <i class="fas fa-search"></i>
                            <div>
                                <h4>${texts.searchFeature}</h4>
                                <p>${texts.searchDesc}</p>
                            </div>
                        </div>

                        <div class="welcome-feature">
                            <i class="fas fa-route"></i>
                            <div>
                                <h4>${texts.routeFeature}</h4>
                                <p>${texts.routeDesc}</p>
                            </div>
                        </div>

                        <div class="welcome-feature">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>${texts.exploreFeature}</h4>
                                <p>${texts.exploreDesc}</p>
                            </div>
                        </div>
                    </div>

                    <div class="welcome-tips">
                        <h4>${texts.tipsTitle}</h4>
                        <ul>
                            <li>${texts.tip1}</li>
                            <li>${texts.tip2}</li>
                            <li>${texts.tip3}</li>
                            <li>${texts.tip4}</li>
                        </ul>
                    </div>
                </div>
            `;
        }
    </script>
</body>

</html>