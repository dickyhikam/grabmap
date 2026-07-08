<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AWS Grab Maps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/map-custom.css') }}">
</head>

<body>

    <!-- Kalau mau 100% hidden dari Network tab juga, kita bisa proxy semua tile/sprite/glyph lewat backend. Tapi ada trade-off:
    Pros: API key benar-benar tidak terlihat di browser
    Cons: Semua tile request lewat server PHP dulu → lebih lambat, server load naik (setiap scroll/zoom peta = puluhan request tile)
    Mau saya implementasi full proxy untuk tile/sprite/glyph juga, atau cukup yang sekarang? (API key sudah aman dari view-source dan bot crawler, hanya terlihat kalau user buka DevTools Network tab) -->

    <div class="floating-header">
        <div class="logo-container">
            <img src="logo.png" alt="Grab Logo" class="grab-logo">
        </div>

        <div class="search-wrapper">
            <div class="search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="search" class="search-input" data-i18n-placeholder="search_placeholder" placeholder="Search a place..." id="searchInput"
                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                    name="grabmaps-search" enterkeyhint="search">
            </div>

            <!-- Search dropdown — shown when input is focused -->
            <div id="searchDropdown" class="search-dropdown" style="display:none;">
                <!-- Category quick filter chips (only when query is empty) -->
                <div id="categoryChipsBar" class="category-chips-bar"></div>

                <!-- Recent searches (only when query is empty and history exists) -->
                <div id="recentSearchesPanel" class="recent-searches" style="display:none;">
                    <div class="recent-searches-header">
                        <i class="bi bi-clock-history me-1"></i> <span data-i18n="recent_searches">Recent</span>
                        <button type="button" class="recent-clear-btn" onclick="clearRecentSearches()" title="Clear all" data-i18n-title="clear_all">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div id="recentSearchesList"></div>
                </div>

                <!-- Loading indicator while fetching -->
                <div id="searchLoading" class="search-loading" style="display:none;">
                    <div class="spinner-border spinner-border-sm" style="color:#00B14F;"></div>
                    <span data-i18n="searching">Searching...</span>
                </div>

                <!-- Empty state when no results -->
                <div id="searchEmpty" class="search-empty" style="display:none;">
                    <i class="bi bi-search" style="opacity:0.4;font-size:1.2rem;"></i>
                    <div data-i18n="no_results">No results found</div>
                    <small data-i18n="try_different_keyword">Try a different keyword</small>
                </div>

                <!-- Live suggestions (when query is typed) -->
                <ul class="suggestions-list" id="suggestionsList"></ul>
            </div>
        </div>

        <div class="header-actions">
            <button class="btn-search-main" type="button" onclick="handleManualSearch()" data-i18n-title="search_btn" title="Search">
                <i class="bi bi-search"></i>
            </button>

            <!-- Language Switcher -->
            <div class="lang-switcher-wrapper">
                <button class="btn-header-link" type="button" onclick="toggleLangMenu()" title="Language" id="langMenuBtn">
                    <i class="bi bi-translate"></i>
                    <span class="lang-current-label" id="langCurrentLabel">EN</span>
                </button>
                <div class="lang-menu-dropdown" id="langMenuDropdown">
                    <div class="lang-menu-item" onclick="switchLang('en')" data-lang="en">
                        <span class="lang-flag">🇬🇧</span>
                        <span class="lang-name">English</span>
                        <i class="bi bi-check2 lang-check"></i>
                    </div>
                    <div class="lang-menu-item" onclick="switchLang('id')" data-lang="id">
                        <span class="lang-flag">🇮🇩</span>
                        <span class="lang-name">Indonesia</span>
                        <i class="bi bi-check2 lang-check"></i>
                    </div>
                </div>
            </div>

            <div class="more-menu-wrapper">
                <button class="btn-header-link" type="button" onclick="toggleMoreMenu()" data-i18n-title="more_features" title="More Features" id="moreMenuBtn">
                    <i class="bi bi-three-dots"></i>
                </button>
                <div class="more-menu-dropdown" id="moreMenuDropdown">
                    <a href="{{ route('pricing') }}" class="more-menu-item">
                        <div class="menu-icon icon-pricing"><i class="bi bi-tag-fill"></i></div>
                        <div class="menu-label" data-i18n="menu_pricing">Pricing Dashboard<small data-i18n="menu_pricing_desc">Compare route pricing</small></div>
                    </a>
                    <a href="{{ route('pageRouteTester') }}" class="more-menu-item">
                        <div class="menu-icon icon-tester"><i class="bi bi-code-slash"></i></div>
                        <div class="menu-label" data-i18n="menu_tester">Tester API<small data-i18n="menu_tester_desc">Test API endpoints</small></div>
                    </a>
                    <a href="{{ route('docs.aws-api') }}" class="more-menu-item">
                        <div class="menu-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-book-half"></i></div>
                        <div class="menu-label" data-i18n="menu_docs">API Reference<small data-i18n="menu_docs_desc">AWS Location v0 &amp; v2 docs</small></div>
                    </a>
                    <a href="{{ route('pageAddress') }}" class="more-menu-item">
                        <div class="menu-icon icon-address"><i class="bi bi-patch-check-fill"></i></div>
                        <div class="menu-label" data-i18n="menu_address">Address Verification<small data-i18n="menu_address_desc">Verify & geocode addresses</small></div>
                    </a>
                    <a href="{{ route('admin.companies.index') }}" class="more-menu-item" hidden>
                        <div class="menu-icon" style="background:#f0faf4; color:#00B14F;"><i class="bi bi-building"></i></div>
                        <div class="menu-label" data-i18n="menu_admin">Company Admin<small data-i18n="menu_admin_desc">Manage client map pages</small></div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="locations-panel" id="locationsPanel">
        <div class="panel-header">
            <div class="panel-title-row">
                <div class="panel-title">
                    <h6 data-i18n="location_manager">Location Manager</h6>
                    <button class="btn-help-minimal" data-bs-toggle="modal" data-bs-target="#helpModal" data-i18n-title="guide_info" title="Guide & Information">
                        <i class="bi bi-question-circle-fill"></i>
                    </button>
                </div>
                <button class="btn-reset-minimal" onclick="clearAllMarkers()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i><span data-i18n="reset">Reset</span>
                </button>
            </div>

            <div class="mode-switch-container mb-2">
                <input type="radio" class="btn-check" name="travelMode" id="modeCar" value="Car" checked>
                <label class="btn-mode-switch flex-grow-1" for="modeCar"><i class="bi bi-car-front-fill me-1"></i> <span data-i18n="car">Car</span></label>
                <input type="radio" class="btn-check" name="travelMode" id="modeScooter" value="Scooter">
                <label class="btn-mode-switch flex-grow-1" for="modeScooter"><i class="bi bi-scooter me-1"></i> <span data-i18n="scooter">Scooter</span></label>
                <input type="radio" class="btn-check" name="travelMode" id="modeWalk" value="Pedestrian">
                <label class="btn-mode-switch flex-grow-1" for="modeWalk"><i class="bi bi-person-walking me-1"></i> <span data-i18n="walk">Walk</span></label>
            </div>

            <div class="mode-switch-container mb-3">
                <input type="radio" class="btn-check" name="optMode" id="optFast" value="fast" checked>
                <label class="btn-mode-switch flex-grow-1" for="optFast" data-i18n-title="sort_direct_distance" title="Sort by direct distance (Faster)">
                    <i class="bi bi-rulers me-1"></i> <span data-i18n="straight_line">Straight Line</span>
                </label>
                <input type="radio" class="btn-check" name="optMode" id="optPrecise" value="real">
                <label class="btn-mode-switch flex-grow-1" for="optPrecise" data-i18n-title="sort_actual_route" title="Sort by actual driving route (More Accurate)">
                    <i class="bi bi-sign-turn-slight-right-fill me-1"></i> <span data-i18n="real_road">Real Road</span>
                </label>
            </div>

            <!-- Quick actions row (Swap A↔B) -->
            <div id="quickActionsRow" class="mb-2 d-flex gap-2" style="display:none;">
                <button id="btnSwapAB" type="button" class="btn btn-sm flex-grow-1 d-flex align-items-center justify-content-center"
                    style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:6px 10px;font-size:0.74rem;color:#374151;"
                    onclick="swapStartEnd()" data-i18n-title="swap_ab_title" title="Reverse the order of locations">
                    <i class="bi bi-arrow-down-up me-1" style="color:#7c3aed;"></i> <span data-i18n="swap_ab">Swap</span>
                </button>
                <button id="btnDistUnit" type="button" class="btn btn-sm flex-grow-1 d-flex align-items-center justify-content-center"
                    style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:6px 10px;font-size:0.74rem;color:#374151;"
                    onclick="toggleDistanceUnit()" data-i18n-title="dist_unit_title" title="Switch distance unit">
                    <i class="bi bi-rulers me-1" style="color:#2563eb;"></i> <span id="distUnitLabel">km</span>
                </button>
                <button id="btnDeparture" type="button" class="btn btn-sm flex-grow-1 d-flex align-items-center justify-content-center"
                    style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:6px 10px;font-size:0.74rem;color:#374151;"
                    onclick="toggleDeparturePanel()" data-i18n-title="departure_title" title="Set departure time">
                    <i class="bi bi-calendar2-event me-1" style="color:#16a34a;"></i> <span id="departureLabel" data-i18n="depart_now">Now</span>
                </button>
            </div>
            <div id="departurePanel" class="mb-2 p-2 rounded-3" style="display:none;background:#fff;border:1px solid #e5e7eb;">
                <label style="font-size:0.72rem;color:#6b7280;font-weight:600;display:block;margin-bottom:4px;" data-i18n="departure_time_label">Departure time</label>
                <div class="d-flex gap-2">
                    <input type="datetime-local" id="departureInput" class="form-control form-control-sm" style="font-size:0.78rem;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearDeparture()" data-i18n="clear">Clear</button>
                </div>
            </div>

            <!-- Avoidances toggle row -->
            <div id="avoidRow" class="mb-2" style="display:none;">
                <button type="button" class="btn btn-sm w-100 d-flex align-items-center justify-content-between"
                    style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:0.78rem;color:#374151;"
                    onclick="toggleAvoidPanel()">
                    <span><i class="bi bi-shield-fill-exclamation me-2" style="color:#d97706;"></i> <span data-i18n="route_preferences">Route Preferences</span> <span id="avoidCount" class="badge bg-success ms-1" style="display:none;font-size:0.6rem;"></span></span>
                    <i id="avoidChevron" class="bi bi-chevron-down"></i>
                </button>
                <div id="avoidPanel" class="mt-2 p-2 rounded-3" style="display:none;background:#fff;border:1px solid #e5e7eb;">
                    <div class="row g-1" style="font-size:0.74rem;">
                        {{-- Only options supported by AWS Location Routes v2 in ap-southeast-1.
                             DirtRoads/Tunnels/UTurns are not supported in this region. --}}
                        @foreach([
                            ['key'=>'TollRoads','label'=>'Tolls','icon'=>'bi-cash-coin'],
                            ['key'=>'Ferries','label'=>'Ferries','icon'=>'bi-water'],
                            ['key'=>'ControlledAccessHighways','label'=>'Highways','icon'=>'bi-signpost-2'],
                        ] as $av)
                        <div class="col-6">
                            <label class="d-flex align-items-center gap-1" style="cursor:pointer;padding:4px 6px;border-radius:6px;">
                                <input type="checkbox" class="form-check-input avoid-check" data-avoid="{{ $av['key'] }}" style="margin:0;">
                                <i class="bi {{ $av['icon'] }}" style="color:#d97706;font-size:0.85rem;"></i>
                                <span data-i18n="avoid_{{ strtolower($av['key']) }}">{{ $av['label'] }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="calcRouteRow" class="gap-2 mb-3" style="display:none;">
                <button id="btnCalcUnified" class="btn btn-action-primary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateUnified()" title="Calculate Route">
                    <i id="btnCalcIcon" class="bi bi-sign-turn-right-fill me-2"></i>
                    <span id="btnCalcLabel" data-i18n="calc_route">Calculate Route</span>
                </button>
            </div>

            <div class="panel-tabs">
                <div class="tab-item active" onclick="switchTab('locations')" id="tabBtn-locations">
                    <span data-i18n="locations_tab">Locations</span> <span class="badge-count ms-1" id="locCount">0</span>
                </div>
                <div class="tab-item" onclick="switchTab('routes')" id="tabBtn-routes">
                    <span data-i18n="route_details_tab">Route Details</span>
                </div>
            </div>
        </div>

        <div class="panel-body px-3 pb-3">

            <div id="tabPane-locations" class="tab-pane active">
                <div id="listContainer"></div>

                <div id="emptyState" class="text-center mt-4" style="font-size: 0.82rem;">
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="bi bi-pin-map-fill" style="font-size: 1.3rem; color: var(--text-muted);"></i>
                    </div>
                    <p class="mb-1" style="font-weight: 600; color: var(--text-secondary);" data-i18n="no_locations_yet">No locations yet</p>
                    <p style="color: var(--text-muted); font-size: 0.75rem;" data-i18n="click_or_search">Click on the map or search to add</p>
                </div>
            </div>

            <div id="tabPane-routes" class="tab-pane">

                <div id="routeResultCard" class="route-result-card mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="route-stat-box">
                            <div class="route-label"><i class="bi bi-rulers"></i> <span data-i18n="distance">Distance</span></div>
                            <div class="route-value" id="resDistance">-</div>
                        </div>
                        <div class="route-divider"></div>
                        <div class="route-stat-box">
                            <div class="route-label"><i class="bi bi-stopwatch"></i> <span data-i18n="duration">Duration</span></div>
                            <div class="route-value" id="resDuration">-</div>
                        </div>
                    </div>
                    <!-- Extra info: ETA + Tolls + Major roads -->
                    <div id="resExtraInfo" style="margin-top:10px;display:none;">
                        <div class="d-flex flex-wrap gap-2" style="font-size:0.72rem;">
                            <span id="resEta" class="d-none" style="background:#eef2ff;color:#4338ca;padding:4px 10px;border-radius:6px;font-weight:600;">
                                <i class="bi bi-clock-history me-1"></i> <span data-i18n="eta_label">Arrive</span> <span id="resEtaValue">—</span>
                            </span>
                            <span id="resTolls" class="d-none" style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:6px;font-weight:600;">
                                <i class="bi bi-cash-coin me-1"></i> <span data-i18n="toll_label">Toll</span> <span id="resTollsValue">—</span>
                            </span>
                        </div>
                        <div id="resMajorRoads" style="margin-top:8px;font-size:0.7rem;color:#374151;display:none;">
                            <i class="bi bi-signpost-2 me-1" style="color:#16a34a;"></i><span data-i18n="via_label">Via</span> <span id="resMajorRoadsValue">—</span>
                        </div>
                    </div>
                </div>

                <!-- Route alternatives toggle (multi: Input order vs Fastest, OR A→B: alt 1/2/3) -->
                <div id="multiRouteCompare" class="mb-3" style="display:none;">
                    <div style="font-size:0.68rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">
                        <i class="bi bi-shuffle me-1"></i> <span data-i18n="route_options">Route Options</span>
                    </div>
                    <div id="routeOptionsPills" class="d-flex flex-column gap-2">
                        <!-- Dynamically populated -->
                    </div>
                    <div id="optSavings" style="font-size:0.72rem; font-weight:600; color:#16a34a; margin-top:8px; display:none;">
                        <i class="bi bi-lightning-charge-fill"></i> <span id="optSavingsText"></span>
                    </div>
                </div>

                <!-- Turn-by-turn directions (collapsible) -->
                <div id="directionsSection" class="mb-3" style="display:none;">
                    <button type="button" class="btn btn-sm w-100 d-flex align-items-center justify-content-between"
                        style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:8px 12px;font-size:0.78rem;color:#374151;"
                        onclick="toggleDirections()">
                        <span><i class="bi bi-list-task me-2" style="color:#2563eb;"></i> <span data-i18n="turn_by_turn">Turn-by-turn Directions</span></span>
                        <i id="directionsChevron" class="bi bi-chevron-down"></i>
                    </button>
                    <div id="directionsWrap" style="display:none;" class="mt-2">
                        <div id="directionsList"></div>
                    </div>
                </div>

                <div id="segmentListContainer" style="display: none;">
                </div>

                <div id="routeEmptyState" class="text-center mt-5">
                    <div style="width: 56px; height: 56px; border-radius: 16px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i class="bi bi-map" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                    </div>
                    <p style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;" data-i18n="no_route_yet">No route yet</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted);" data-i18n="add_then_calc">Add locations then press Calculate</p>
                </div>

            </div>

        </div>
    </div>

    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-header-pro">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-book-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 1.05rem;" data-i18n="help_title">Features & Guide</h5>
                            <small style="opacity: 0.8; font-size: 0.75rem;" data-i18n="help_subtitle">Everything you need to know</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body modal-body-pro">

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="basic_controls">Basic Controls</p>
                    <div class="bg-white p-3 rounded-3 border mb-4" style="border-color: var(--border-light) !important;">
                        <div class="row g-3 text-center">
                            <div class="col-4 border-end">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fef2f2; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                </div>
                                <div class="small fw-bold text-dark" data-i18n="add">Add</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);" data-i18n="click_map">Click Map / Search</div>
                            </div>
                            <div class="col-4 border-end">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-arrows-move text-primary"></i>
                                </div>
                                <div class="small fw-bold text-dark" data-i18n="move">Move</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);" data-i18n="drag_marker">Drag Marker</div>
                            </div>
                            <div class="col-4">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-x-circle text-secondary"></i>
                                </div>
                                <div class="small fw-bold text-dark" data-i18n="remove">Remove</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);" data-i18n="click_remove_btn">Click 'X' in List</div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">1. Optimization Methods</p>
                    <div class="p-3 bg-white rounded-3 border mb-3" style="border-color: var(--border-light) !important;">
                        <table class="table table-borderless table-sm small mb-0">
                            <thead class="border-bottom" style="color: var(--text-muted);">
                                <tr>
                                    <th class="pb-2" data-i18n="feature">Feature</th>
                                    <th class="pb-2 text-center text-primary" data-i18n="straight_line_col">Straight Line</th>
                                    <th class="pb-2 text-center text-success" data-i18n="real_road_col">Real Road</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);" data-i18n="accuracy">Accuracy</td>
                                    <td class="py-2 text-center" data-i18n="accuracy_sl">Low (Geometric)</td>
                                    <td class="py-2 text-center" data-i18n="accuracy_rr">High (Actual roads)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);" data-i18n="cost_label">API cost</td>
                                    <td class="py-2 text-center" data-i18n="cost_sl">None (local calc)</td>
                                    <td class="py-2 text-center" data-i18n="cost_rr">1 route-matrix call</td>
                                </tr>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);" data-i18n="best_for">Best For</td>
                                    <td class="py-2 text-center" data-i18n="estimates">Rough estimates</td>
                                    <td class="py-2 text-center" data-i18n="delivery">Real routing</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-2 pt-2 border-top d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-warning mt-1"></i>
                            <p class="small mb-0" style="font-size: 0.73rem; color: var(--text-secondary);" data-i18n-html="opt_tip">
                                <strong>Tip:</strong> Only applies to <b>Multi-Stop</b>. Straight Line uses Haversine (geometric distance), Real Road uses AWS Route Matrix + 2-opt swap.
                            </p>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="travel_modes">2. Travel Modes</p>
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center; flex-shrink:0;">
                                    <i class="bi bi-car-front-fill" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2; min-width:0;">
                                    <div class="small fw-bold text-dark" data-i18n="car">Car</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);" data-i18n="standard_routes">Standard Routes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center; flex-shrink:0;">
                                    <i class="bi bi-scooter" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2; min-width:0;">
                                    <div class="small fw-bold text-dark" data-i18n="motorcycle">Motorcycle</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);" data-i18n="faster_eta">Faster ETA</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center; flex-shrink:0;">
                                    <i class="bi bi-person-walking" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2; min-width:0;">
                                    <div class="small fw-bold text-dark" data-i18n="walk">Walk</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);" data-i18n="pedestrian_routes">Pedestrian</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="calc_actions">3. Calculation Actions</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-sign-turn-right-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="single_route_info">Single Route (A&rarr;B)</h6>
                            <p style="font-size: 0.78rem;" data-i18n="single_route_desc">Direct path between exactly 2 locations. Auto-enabled when you have 2 markers.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="multi_stop_info">Multi-Stop (Optimized)</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="multi_stop_desc">Auto-enabled with 3+ markers. Uses <b>2-opt optimization</b> to find a near-optimal route order, then shows you both the fastest version <em>and</em> your input order so you can compare.</p>
                        </div>
                    </div>

                    <!-- ============ 4. ROUTE CUSTOMIZATION ============ -->
                    <p class="text-uppercase fw-bold small mb-2 mt-3 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="route_customization">4. Route Customization</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #fef3c7; color: #d97706; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-shield-fill-exclamation"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="prefs_info">Route Preferences</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="prefs_desc">Avoid <b>tolls</b>, <b>ferries</b>, or <b>highways</b>. Badge shows how many you have active.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #f0fdf4; color: #16a34a; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-calendar2-event"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="departure_info">Departure Time</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="departure_desc">Plan ahead — set when you'll start. Duration adjusts based on <b>historical traffic patterns by time-of-day</b> (e.g. rush hour 17:00 is slower than 14:00). Note: AWS does not provide <em>live real-time</em> traffic in this region.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #2563eb; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-rulers"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="dist_unit_info">Distance Unit</h6>
                            <p style="font-size: 0.78rem;" data-i18n="dist_unit_desc">Toggle between kilometers and miles. Choice persists across visits.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #ede9fe; color: #7c3aed; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-arrow-down-up"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="swap_info">Swap Order</h6>
                            <p style="font-size: 0.78rem;" data-i18n="swap_desc">Quick reverse the marker list — useful for "return trip" testing.</p>
                        </div>
                    </div>

                    <!-- ============ 5. ROUTE RESULTS ============ -->
                    <p class="text-uppercase fw-bold small mb-2 mt-3 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="route_results">5. Route Results</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eef2ff; color: #4338ca; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="eta_info">Arrival Time (ETA)</h6>
                            <p style="font-size: 0.78rem;" data-i18n="eta_desc">Shows what time you'll arrive based on now (or your set departure time) + total duration.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #fef3c7; color: #92400e; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="toll_info">Toll Cost</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="toll_desc">When AWS returns toll cost data, it's shown (IDR / local currency). <b>Note:</b> in <code>ap-southeast-1</code> AWS rarely populates this field, so it usually stays hidden.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #f0fdf4; color: #16a34a; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-signpost-2"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="via_info">Via Major Roads</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="via_desc">Lists main roads the route uses. <b>Note:</b> AWS doesn't populate this field in <code>ap-southeast-1</code> currently, so this row is usually hidden.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #f0fdf4; color: #16a34a; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="alternatives_info">Alternative Routes</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="alternatives_desc">A→B returns up to <b>3 alternatives</b>. Switch via the Route Options pills — Fastest is marked <b>Recommended</b>.</p>
                        </div>
                    </div>

                    <!-- ============ 6. TURN-BY-TURN ============ -->
                    <p class="text-uppercase fw-bold small mb-2 mt-3 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="navigation_section">6. Navigation</p>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #eff6ff; color: #2563eb; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-list-task"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="tbt_info">Turn-by-turn Directions</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="tbt_desc">After calculating, expand <b>Turn-by-turn Directions</b> to see every maneuver: turn left/right, merge, roundabout, etc. — each with distance & duration. Localized to your language.</p>
                        </div>
                    </div>

                    <!-- ============ 7. MAP TOOLS ============ -->
                    <p class="text-uppercase fw-bold small mb-2 mt-3 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="map_tools">7. Map Tools</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #2563eb; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-crosshair"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="locate_me_info">Locate Me</h6>
                            <p style="font-size: 0.78rem;" data-i18n="locate_me_desc">Fly the map to your current GPS position. Requires browser location permission.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #f3e8ff; color: #7c3aed; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-palette"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="map_style_info">Map Style</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="map_style_desc">Switch between <b>Standard / Monochrome</b> styles and <b>Light / Dark</b> color schemes. Routes stay drawn during style swap.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #fef3c7; color: #d97706; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-pin-map"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="pick_coords_info">Pick Coordinates</h6>
                            <p style="font-size: 0.78rem;" data-i18n="pick_coords_desc">Toggle mode then click anywhere on the map to copy lat/lng or open in Google Maps — without adding a marker.</p>
                        </div>
                    </div>

                    <!-- ============ 8. EXTRA TIPS ============ -->
                    <p class="text-uppercase fw-bold small mb-2 mt-3 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="extra_tips">8. Extra Tips</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #f0fdf4; color: #16a34a; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="route_options_info">Route Options Toggle</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="route_options_desc">After a multi-stop calculation, switch between <b>Fastest Route</b> and <b>Input Order</b> with one click — no recompute needed.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #fef3c7; color: #d97706; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-pin-map-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="segment_focus_info">Focus a Segment</h6>
                            <p style="font-size: 0.78rem;" data-i18n="segment_focus_desc">Click any segment card in the Route Details panel to zoom & highlight that specific leg on the map.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #ede9fe; color: #7c3aed; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="place_details_info">Place Details</h6>
                            <p style="font-size: 0.78rem;" data-i18n="place_details_desc">Click a location row in the list to open AWS Places v2 details: categories, contacts, opening hours, and more.</p>
                        </div>
                    </div>

                </div>

                <div class="modal-footer-pro text-center">
                    <button type="button" class="btn btn-action-primary w-100 py-2" data-bs-dismiss="modal">
                        Got it, thanks!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="map"></div>

    <!-- "Search this area" floating button (shown when map moved after a search) -->
    <button id="searchThisAreaBtn" type="button" class="search-this-area-btn" style="display:none;" onclick="searchThisArea()">
        <i class="bi bi-search me-2"></i><span data-i18n="search_this_area">Search this area</span>
    </button>

    <!-- ============ MAP UTILITY PANEL (Locate me / Style switcher / Pick coords) ============ -->
    <div class="map-utility-panel" id="mapUtilityPanel">
        <button type="button" class="map-util-btn" id="btnLocateMe" onclick="locateMe()"
            data-i18n-title="locate_me" title="Find my location">
            <i class="bi bi-crosshair"></i>
        </button>
        <button type="button" class="map-util-btn" id="btnPickCoords" onclick="togglePickCoords()"
            data-i18n-title="pick_coords_mode" title="Click map to copy coordinates">
            <i class="bi bi-pin-map"></i>
        </button>
        <div class="map-util-btn-wrap">
            <button type="button" class="map-util-btn" id="btnStyleToggle" onclick="toggleStylePanel()"
                data-i18n-title="map_style" title="Map style">
                <i class="bi bi-palette"></i>
            </button>
            <div class="map-style-panel" id="mapStylePanel" style="display:none;">
                <div class="map-style-row">
                    <button type="button" class="map-style-btn active" data-style="Standard" title="Standard"><i class="bi bi-map"></i> Standard</button>
                    <button type="button" class="map-style-btn" data-style="Monochrome" title="Monochrome"><i class="bi bi-circle-half"></i> Mono</button>
                </div>
                <div class="map-style-divider"></div>
                <div class="map-style-row">
                    <button type="button" class="map-style-btn active" data-color="Light" title="Light"><i class="bi bi-sun-fill"></i> Light</button>
                    <button type="button" class="map-style-btn" data-color="Dark" title="Dark"><i class="bi bi-moon-stars-fill"></i> Dark</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Coordinate copy popup (shown after Pick Coords click) -->
    <div id="pickCoordsPopup" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:1100;background:#fff;border-radius:12px;padding:12px 16px;box-shadow:0 8px 24px rgba(0,0,0,0.15);font-size:0.78rem;min-width:260px;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <strong style="color:#16a34a;"><i class="bi bi-pin-map-fill me-1"></i> <span data-i18n="picked_coords">Picked</span></strong>
            <button type="button" onclick="document.getElementById('pickCoordsPopup').style.display='none';" style="background:none;border:none;color:#9ca3af;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="pickCoordsText" style="font-family:ui-monospace,monospace;color:#111;margin-bottom:8px;"></div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm flex-grow-1" style="background:#16a34a;color:#fff;font-size:0.74rem;" onclick="copyPickedCoords()"><i class="bi bi-clipboard me-1"></i> <span data-i18n="copy">Copy</span></button>
            <a id="pickCoordsGmaps" href="#" target="_blank" rel="noopener" class="btn btn-sm flex-grow-1" style="background:#fff;border:1px solid #d1d5db;color:#1f2937;font-size:0.74rem;">
                <i class="bi bi-box-arrow-up-right me-1"></i> Google Maps
            </a>
        </div>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <!-- ============ LOCATION DETAIL MODAL (v2) ============ -->
    <div class="modal fade" id="locDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#00B14F 0%,#008b3d 100%);color:#fff;border:none;padding:18px 22px;">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-width-0">
                        <i class="bi bi-info-circle-fill" style="font-size:1.2rem;"></i>
                        <h5 class="modal-title mb-0 text-truncate" style="font-weight:700;font-size:1rem;" id="locDetailTitle" data-i18n="loc_detail_title">Place Details</h5>
                        <span class="badge bg-light text-success ms-auto" style="font-size:0.65rem;">v2</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="locDetailBody" style="padding:20px;">
                    <!-- Loading state -->
                    <div id="locDetailLoading" class="text-center py-4">
                        <div class="spinner-border text-success" role="status" style="width:2rem;height:2rem;"></div>
                        <p class="text-muted mt-2 small mb-0" data-i18n="loading">Loading...</p>
                    </div>
                    <!-- Detail content (filled dynamically) -->
                    <div id="locDetailContent" style="display:none;"></div>
                    <!-- Error state -->
                    <div id="locDetailError" class="text-center py-4" style="display:none;">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size:2rem;"></i>
                        <p class="text-muted mt-2 small mb-0" data-i18n="loc_detail_error">Failed to load details</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ WHAT'S NEW MODAL ============ -->
    <div class="modal fade" id="whatsNewModal" tabindex="-1" aria-labelledby="whatsNewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden;">
                <div class="modal-header" style="background:linear-gradient(135deg,#00B14F 0%,#008b3d 100%);color:#fff;border:none;padding:20px 24px;">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-stars" style="font-size:1.4rem;"></i>
                            <h5 class="modal-title mb-0" id="whatsNewModalLabel" style="font-weight:700;" data-i18n="whats_new">What's New</h5>
                            <span class="badge bg-light text-success ms-1" style="font-size:0.7rem;">v2.1</span>
                        </div>
                        <small style="opacity:0.9;" data-i18n="whats_new_subtitle">Home v2: Places, Routes, Maps, and smarter map tools</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <!-- Highlight v2 -->
                    <div class="p-3 rounded-3 mb-3" style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1px solid #bbf7d0;">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-rocket-takeoff-fill" style="color:#16a34a;font-size:1.2rem;"></i>
                            <div>
                                <div style="font-weight:700;color:#166534;" data-i18n="v2_migration_title">Home is now powered by AWS Location v2</div>
                                <small style="color:#15803d;" data-i18n="v2_migration_desc">Places v2, Routes v2, Route Matrix v2, and Maps v2 styles are proxied through Laravel.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Feature list -->
                    <h6 class="mb-3" style="font-weight:700;color:#1f2937;">
                        <i class="bi bi-list-stars me-1" style="color:#7c3aed;"></i> <span data-i18n="new_features">New Features</span>
                    </h6>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="background:#ede9fe;color:#7c3aed;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-search"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_search_title">Search v2 + Search This Area</div>
                                <small style="color:#6b7280;" data-i18n="feat_search_desc">Search and autocomplete now separate POI title and address, bias to the visible map, and preview results on the map.</small>
                                <div class="mt-2 p-2 rounded-2" style="background:#fff;border:1px solid #e5e7eb;font-size:0.78rem;">
                                    <div style="font-weight:600;color:#1f2937;">Kantor Pusat PT. Kereta Api Indonesia</div>
                                    <div style="color:#6b7280;font-size:0.72rem;">Jl. Perintis Kemerdekaan No.1, Bandung</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="background:#dbeafe;color:#2563eb;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-sign-turn-right-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_autocomplete_title">Routes v2 & Multi-stop</div>
                                <small style="color:#6b7280;" data-i18n="feat_autocomplete_desc">A-to-B routes, alternatives, turn-by-turn directions, and route-matrix optimization now run on the v2 adapter.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="background:#fef3c7;color:#d97706;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-pin-map-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_reverse_title">Place Details v2</div>
                                <small style="color:#6b7280;" data-i18n="feat_reverse_desc">Click a marker or location row to open richer place details, including categories, contact data, and opening hours when available.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="background:#fce7f3;color:#db2777;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-moon-stars-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_resourceless_title">Map styles & utilities</div>
                                <small style="color:#6b7280;" data-i18n="feat_resourceless_desc">The map can switch Light/Dark automatically by local time, with Standard/Monochrome styles, Locate Me, and Pick Coordinates tools.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Technical details -->
                    <details class="mt-3">
                        <summary style="cursor:pointer;color:#7c3aed;font-weight:600;font-size:0.85rem;">
                            <i class="bi bi-code-slash me-1"></i> <span data-i18n="tech_details">Technical Details</span>
                        </summary>
                        <div class="mt-2 p-3 rounded-3" style="background:#1f2937;color:#e5e7eb;font-size:0.72rem;font-family:monospace;">
                            <div style="color:#9ca3af;">// Home v2 endpoints</div>
                            <div style="color:#86efac;">GET  /api/v2/map-style</div>
                            <div style="color:#86efac;">POST /api/places/suggestions</div>
                            <div style="color:#86efac;">POST /api/places/search</div>
                            <div style="color:#86efac;">GET  /api/places/{placeId}</div>
                            <div style="color:#86efac;">POST /api/routes/calculate</div>
                            <div style="color:#86efac;">POST /api/routes/matrix</div>
                        </div>
                    </details>

                    <div class="mt-3 p-2 rounded-3 small text-center" style="background:#f3f4f6;color:#6b7280;">
                        <i class="bi bi-info-circle me-1"></i>
                        <span data-i18n="tester_api_note">Tester API available at</span> <a href="/tester-api" style="color:#7c3aed;font-weight:600;">/tester-api</a> <span data-i18n="tester_api_note_2">— can toggle v0 vs v2 for comparison</span>
                    </div>
                </div>
                <div class="modal-footer" style="border:none;padding:16px 24px 24px;background:#fff;">
                    <div class="form-check me-auto">
                        <input class="form-check-input" type="checkbox" id="dontShowAgain" checked>
                        <label class="form-check-label small text-muted" for="dontShowAgain" data-i18n="dont_show_again">
                            Don't show again
                        </label>
                    </div>
                    <button type="button" class="btn rounded-pill px-4" style="background:#00B14F;color:#fff;font-weight:600;" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-1"></i> <span data-i18n="understood">Understood</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        /* =========================================================================
           GrabMaps Home Page — Frontend JS Architecture Overview
           =========================================================================

           This file is the main map demo page. It combines AWS Location Service v2
           APIs (via Laravel proxy) with a lot of custom UX layered on top.

           ── CODE LEGEND ─────────────────────────────────────────────────────────
           [AWS]   Direct pass-through to AWS Location v2. Payload shape comes from
                   AWS docs; we only forward via the Laravel proxy controller.
           [CUSTOM]Custom UI/UX logic — multi-stop optimization, swap, alternatives
                   toggle, search dropdown, distance calculation, i18n, etc.
           [HYBRID]Sends an AWS-shaped request but does extra post-processing
                   (caching alternatives, route adapter, distance computation).
           [HELPER]Pure utility — formatting, debouncing, DOM, geometry math.

           ── SECTIONS (search "===" to jump) ─────────────────────────────────────
            1. CONFIGURATION & GLOBAL STATE     (csrf, i18n, language switcher)
            2. PROXY HELPERS                    (proxyGet / proxyPost wrappers)
            3. MAP INITIALIZATION               (MapLibre init + click handler)
            4. LOCATION MANAGEMENT (CRUD)       (markers add/remove/clear)
            5. PLACE DETAILS MODAL              (Places v2 GetPlace render)
            6. ROUTING                          (A→B, Multi-stop, 2-opt optimizer)
            7. VISUALIZATION & HELPERS          (drawRouteOnMap, formatDuration)
            8. SEARCH FUNCTIONALITY             (suggest/search-text + dropdown UX)
            9. INITIALIZATION & EVENTS          (bootstrap)

           ── AWS ENDPOINTS USED (all v2, via Laravel proxy) ──────────────────────
            POST /api/places/suggestions  → AWS POST /v2/suggest
            POST /api/places/search       → AWS POST /v2/search-text
            GET  /api/places/{id}         → AWS GET  /v2/place/{id}
            POST /api/places/reverse      → AWS POST /v2/reverse-geocode
            POST /api/routes/calculate    → AWS POST /v2/routes        [adapter]
            POST /api/routes/matrix       → AWS POST /v2/route-matrix   [adapter]
            GET  /api/v2/map-style        → AWS GET  /v2/styles/{Style}/descriptor

           "Adapter" = backend translates v0-shape request to v2 + v2 response back
                       to v0 shape for backward-compat with all callers. Source:
                       app/Http/Controllers/MapController.php → translateV2RouteToV0Shape()

           ── HOW TO TEST AWS ENDPOINTS DIRECTLY (curl) ───────────────────────────

           Get AWS API key from .env (AWS_API_KEY), then:

           # 1. Search-text v2 — full search (returns Position)
           curl -X POST "https://places.geo.ap-southeast-1.amazonaws.com/v2/search-text?key=KEY" \
             -H "Content-Type: application/json" \
             -d '{"QueryText":"cafe jakarta","MaxResults":5,"BiasPosition":[106.84,-6.21]}'

           # 2. Suggest v2 — autocomplete (needs AdditionalFeatures=Core for Position!)
           curl -X POST "https://places.geo.ap-southeast-1.amazonaws.com/v2/suggest?key=KEY" \
             -H "Content-Type: application/json" \
             -d '{"QueryText":"warung","MaxResults":5,"BiasPosition":[106.84,-6.21],
                  "Language":"id","AdditionalFeatures":["Core"]}'

           # 3. Reverse-geocode v2
           curl -X POST "https://places.geo.ap-southeast-1.amazonaws.com/v2/reverse-geocode?key=KEY" \
             -H "Content-Type: application/json" \
             -d '{"QueryPosition":[106.84,-6.21],"MaxResults":1,"Language":"id"}'

           # 4. GetPlace v2 — full details for a PlaceId
           PID="..." # from Suggest/Search response
           curl "https://places.geo.ap-southeast-1.amazonaws.com/v2/place/$PID?key=KEY&AdditionalFeatures=Contact,TimeZone"

           # 5. CalculateRoutes v2
           curl -X POST "https://routes.geo.ap-southeast-1.amazonaws.com/v2/routes?key=KEY" \
             -H "Content-Type: application/json" \
             -d '{"Origin":[106.84,-6.21],"Destination":[106.86,-6.22],
                  "TravelMode":"Car","LegGeometryFormat":"Simple",
                  "TravelStepType":"TurnByTurn","MaxAlternatives":2}'

           # 6. RouteMatrix v2
           curl -X POST "https://routes.geo.ap-southeast-1.amazonaws.com/v2/route-matrix?key=KEY" \
             -H "Content-Type: application/json" \
             -d '{"Origins":[{"Position":[106.84,-6.21]}],
                  "Destinations":[{"Position":[106.86,-6.22]}],
                  "TravelMode":"Car","RoutingBoundary":{"Unbounded":true}}'

           # 7. Map style v2 descriptor (Standard or Monochrome only in ap-southeast-1)
           curl "https://maps.geo.ap-southeast-1.amazonaws.com/v2/styles/Standard/descriptor?key=KEY&color-scheme=Light"

           ── REGION CAVEATS (ap-southeast-1 / GrabMaps) ──────────────────────────
            - Map styles: only Standard + Monochrome (no Hybrid/Satellite)
            - Avoidances: only TollRoads, Ferries, ControlledAccessHighways
            - Live Traffic param: NOT supported (historical pattern only via DepartureTime)
            - Tolls + MajorRoadLabels: AWS rarely populates in this region
            - Waypoints.PassThrough: not supported

           ── KEY GLOBAL STATE ────────────────────────────────────────────────────
            markersData          : marker objects added via click/search [CUSTOM]
            routeAlternatives    : cached route options (Fastest/Original/Alt N) [CUSTOM]
            myLocationCoords     : [lng,lat] from Locate Me [CUSTOM]
            currentLang          : 'en' | 'id' [CUSTOM]
            currentDistanceUnit  : 'Kilometers' | 'Miles' [CUSTOM]
            departureTimeIso     : ISO 8601 string when picked [CUSTOM]
            mapStyleState        : { style, color } for switcher [CUSTOM]
           ========================================================================= */

        /* =========================================
           1. CONFIGURATION & GLOBAL STATE
           ========================================= */
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        /* =========================================
           I18N — Multi-language support (EN / ID)
           ========================================= */
        // Translations loaded from lang/{en,id}/home.php — single source of truth
        const i18n = {
            en: @json(__('home', [], 'en')),
            id: @json(__('home', [], 'id'))
        };

        let currentLang = localStorage.getItem('home_lang') || 'en';

        function t(key, params = {}) {
            let str = (i18n[currentLang] && i18n[currentLang][key]) || i18n.en[key] || key;
            Object.keys(params).forEach(p => {
                str = str.replace(`{${p}}`, params[p]);
            });
            return str;
        }

        function applyTranslations() {
            document.querySelectorAll('[data-i18n]').forEach(el => {
                // Preserve nested <small> tags by setting only the first text node
                const smallEl = el.querySelector('small');
                if (smallEl) {
                    // Replace text before <small> while keeping <small>
                    const newText = t(el.dataset.i18n);
                    [...el.childNodes].forEach(n => {
                        if (n.nodeType === Node.TEXT_NODE) n.remove();
                    });
                    el.insertBefore(document.createTextNode(newText), smallEl);
                } else {
                    el.textContent = t(el.dataset.i18n);
                }
            });
            // For text containing HTML tags (e.g. <b>)
            document.querySelectorAll('[data-i18n-html]').forEach(el => {
                el.innerHTML = t(el.dataset.i18nHtml);
            });
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                el.placeholder = t(el.dataset.i18nPlaceholder);
            });
            document.querySelectorAll('[data-i18n-title]').forEach(el => {
                el.title = t(el.dataset.i18nTitle);
            });
            document.documentElement.lang = currentLang;
            // Update language switcher UI
            const label = document.getElementById('langCurrentLabel');
            if (label) label.textContent = currentLang.toUpperCase();
            document.querySelectorAll('.lang-menu-item').forEach(item => {
                item.classList.toggle('active', item.dataset.lang === currentLang);
            });
        }

        function switchLang(lang) {
            if (!i18n[lang]) return;
            currentLang = lang;
            localStorage.setItem('home_lang', lang);
            applyTranslations();
            // Re-render dynamic content so popups/list/segments pick up new translations
            try { renderLocationList(); } catch (_) {}
            try { if (lastSegmentDetails && lastSegmentDetails.length) renderSegmentList(lastSegmentDetails); } catch (_) {}
            try { updateRouteButtonsByCount(); } catch (_) {}
            // Refresh route result card + compare panel (formatDuration + step instructions localize)
            try {
                const hasAny = Object.values(routeAlternatives).some(v => v != null);
                if (hasAny) {
                    const activeRadio = document.querySelector('input[name="multiRouteChoice"]:checked');
                    const fallbackKey = Object.keys(routeAlternatives).find(k => routeAlternatives[k] != null);
                    const activeKey = activeRadio ? activeRadio.value : fallbackKey;
                    if (routeAlternatives[activeKey]) {
                        applyMultiRouteAlternative(activeKey);
                        // Also re-localize the alt labels (Fastest Route / Input Order / Alternative N)
                        Object.entries(routeAlternatives).forEach(([k, v]) => {
                            if (!v) return;
                            if (k === 'optimized' || k === 'primary') v.label = t('fastest_route');
                            else if (k === 'original') v.label = t('input_order');
                            else if (k.startsWith('alt')) {
                                const n = parseInt(k.slice(3), 10) || 1;
                                v.label = t('alternative_n', { n });
                            }
                        });
                        renderMultiRouteCompare(activeKey);
                    }
                }
            } catch (_) {}
            const dropdown = document.getElementById('langMenuDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function toggleLangMenu() {
            const dropdown = document.getElementById('langMenuDropdown');
            if (dropdown) dropdown.classList.toggle('show');
        }

        // Close lang menu when clicking outside
        document.addEventListener('click', (e) => {
            const wrapper = document.querySelector('.lang-switcher-wrapper');
            const dropdown = document.getElementById('langMenuDropdown');
            if (wrapper && dropdown && !wrapper.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Helper for POST requests to our proxy
        /**
         * [HELPER] POST wrapper to the Laravel proxy. Always sends CSRF token
         * + JSON body. Used by all AWS-touching calls (places/routes).
         */
        function proxyPost(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            });
        }

        // Helper for GET requests to our proxy
        /** [HELPER] GET wrapper to the Laravel proxy (with CSRF header). */
        function proxyGet(url) {
            return fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
        }

        let map = null;
        let markersData = [];
        let selectedMarkerId = null;
        let lastSegmentDetails = [];
        let routeCalcInFlight = false;
        let operationGeneration = 0;
        let routeAlternatives = {};
        let currentDistanceUnit = localStorage.getItem('home_dist_unit') || 'Kilometers'; // or 'Miles'
        let departureTimeIso = null; // ISO 8601 string when user picks a departure time
        let highlightMarkers = [];


        /* =========================================
           2. UI UTILITIES (Toast & Tabs)
           ========================================= */
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toastContainer');
            let bgClass, iconClass;

            switch (type) {
                case 'success':
                    bgClass = 'text-bg-success';
                    iconClass = 'bi-check-circle-fill';
                    break;
                case 'error':
                    bgClass = 'text-bg-danger';
                    iconClass = 'bi-exclamation-triangle-fill';
                    break;
                case 'warning':
                    bgClass = 'text-bg-warning';
                    iconClass = 'bi-exclamation-circle-fill';
                    break;
                default:
                    bgClass = 'text-bg-primary';
                    iconClass = 'bi-info-circle-fill';
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
            <div class="toast align-items-start ${bgClass} border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body text-white">
                        <i class="${iconClass} me-2 fs-5"></i>
                        <strong>${title}</strong>
                        <div class="mt-1 small">${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;

            const toastElement = wrapper.firstElementChild;
            container.appendChild(toastElement);

            requestAnimationFrame(() => {
                try {
                    const t = new bootstrap.Toast(toastElement, {
                        autohide: false
                    });
                    t.show();
                    setTimeout(() => {
                        if (toastElement && document.body.contains(toastElement)) t.hide();
                    }, 5000);
                    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
                } catch (error) {
                    console.error("Failed init toast:", error);
                    toastElement.remove();
                }
            });
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));

            document.getElementById(`tabBtn-${tabName}`).classList.add('active');
            document.getElementById(`tabPane-${tabName}`).classList.add('active');
        }


        /* =========================================
           3. MAP INITIALIZATION
           ========================================= */
        /**
         * [CUSTOM + AWS] Initialize MapLibre GL with AWS Location v2 style descriptor.
         * Style URL `/api/v2/map-style` proxies to AWS GetStyleDescriptor.
         * Also wires:
         *   - moveend → "Search this area" button visibility [CUSTOM]
         *   - styledata → re-draw routes after style swap [CUSTOM]
         *   - click → either Pick Coords mode OR Add Location (reverse-geocode) [CUSTOM]
         */
        function initMap() {
            map = new maplibregl.Map({
                container: 'map',
                style: buildMapStyleUrl(),
                center: [106.8456, -6.2088],
                zoom: 13,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.AttributionControl({
                customAttribution: '© Grab, © AWS'
            }), 'bottom-right');

            // Show "Search this area" button when map moves significantly after a search
            map.on('moveend', () => {
                maybeShowSearchThisArea();
            });

            // Re-disable map style switcher when AWS style finishes loading + re-draw routes if any
            map.on('styledata', () => {
                if (mapStyleLoading) {
                    mapStyleLoading = false;
                    document.querySelectorAll('.map-style-btn').forEach(b => b.disabled = false);
                    // Re-draw currently active route line if user had one
                    const activeRadio = document.querySelector('input[name="multiRouteChoice"]:checked');
                    const fallbackKey = Object.keys(routeAlternatives).find(k => routeAlternatives[k] != null);
                    const activeKey = activeRadio ? activeRadio.value : fallbackKey;
                    if (activeKey && routeAlternatives[activeKey]) {
                        try { applyMultiRouteAlternative(activeKey); } catch (_) {}
                    }
                }
            });

            // Click map — handles either Pick Coords (utility mode) OR Add Location
            map.on('click', async (e) => {
                const lng = e.lngLat.lng;
                const lat = e.lngLat.lat;

                if (pickCoordsActive) {
                    pickedCoordsText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    document.getElementById('pickCoordsText').innerText = pickedCoordsText;
                    document.getElementById('pickCoordsGmaps').href = `https://www.google.com/maps?q=${lat},${lng}`;
                    document.getElementById('pickCoordsPopup').style.display = 'block';
                    return; // don't add a marker
                }

                const coords = [lng, lat];
                addLocation(coords, t('loading'));
                const currentId = selectedMarkerId;

                try {
                    const place = await getPlaceNameByCoords(coords);
                    const item = markersData.find(m => m.id === currentId);
                    if (!item) return;
                    if (place && place.title) {
                        item.name = place.title;
                        item.address = place.address;
                        item.marker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setHTML(buildPopupHtml(place.title, place.address)));
                        renderLocationList();
                        // If a POI was nearby (but >50m), mention it as soft info — don't claim it's the place
                        if (place.nearby) {
                            showToast(t('location_found'), `${place.title} • ${t('near')} ${place.nearby.name} (~${place.nearby.distance}m)`, 'info');
                        } else {
                            showToast(t('location_found'), place.title, 'success');
                        }
                    } else {
                        item.name = `Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                        item.address = null;
                        item.marker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setHTML(buildPopupHtml(item.name, null)));
                        renderLocationList();
                        showToast(t('info'), t('location_not_found'), 'warning');
                    }
                } catch (error) {
                    console.error(error);
                    const item = markersData.find(m => m.id === currentId);
                    if (item) {
                        item.name = `Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                        item.address = null;
                        item.marker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setHTML(buildPopupHtml(item.name, null)));
                        renderLocationList();
                    }
                }
            });
        }


        /* =========================================
           4. LOCATION MANAGEMENT (CRUD)
           ========================================= */
        function buildPopupHtml(title, address) {
            const safeTitle = escapeHtml(title || '');
            const safeAddr = escapeHtml(address || '');
            if (safeAddr) {
                return `<div style="font-family:Inter,sans-serif;max-width:240px;">
                    <div style="font-weight:600;font-size:0.85rem;color:#1f2937;line-height:1.3;">${safeTitle}</div>
                    <div style="font-size:0.72rem;color:#6b7280;margin-top:3px;line-height:1.3;">${safeAddr}</div>
                </div>`;
            }
            return `<div style="font-family:Inter,sans-serif;font-weight:600;font-size:0.85rem;">${safeTitle}</div>`;
        }

        /**
         * [CUSTOM] Add a draggable marker to the map + push to markersData.
         * Side effects:
         *   - operationGeneration bumped (async ops can detect stale state)
         *   - invalidates current route (since marker set changed)
         *   - re-renders location list & route buttons (auto-enable A→B vs Multi)
         *   - flies map to new marker
         */
        function addLocation(coords, label, address = null, placeId = null) {
            const id = Date.now();
            const newMarker = new maplibregl.Marker({
                    color: '#00B14F',
                    draggable: true
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setHTML(buildPopupHtml(label, address)))
                .addTo(map);

            newMarker.togglePopup();

            // Drag Event Handler
            newMarker.on('dragend', async () => {
                const lngLat = newMarker.getLngLat();
                const updatedCoords = [lngLat.lng, lngLat.lat];
                const item = markersData.find(m => m.id === id);
                if (!item) return;

                item.coords = updatedCoords;
                const mySeq = (item._reverseSeq = (item._reverseSeq || 0) + 1);
                showToast(t('loading'), t('finding_address'), 'info');

                const newPlace = await getPlaceNameByCoords(updatedCoords);

                // Bail if a newer drag superseded this, or if the marker was removed during await
                const stillExists = markersData.find(m => m.id === id);
                if (!stillExists || item._reverseSeq !== mySeq) return;

                if (newPlace && newPlace.title) {
                    item.name = newPlace.title;
                    item.address = newPlace.address;
                    newMarker.setPopup(new maplibregl.Popup({
                        offset: 25
                    }).setHTML(buildPopupHtml(newPlace.title, newPlace.address)));
                    renderLocationList();
                    if (newPlace.nearby) {
                        showToast(t('location_updated'), `${newPlace.title} • ${t('near')} ${newPlace.nearby.name} (~${newPlace.nearby.distance}m)`, 'info');
                    } else {
                        showToast(t('location_updated'), newPlace.title, 'success');
                    }
                } else {
                    item.name = `Location (${updatedCoords[1].toFixed(4)}, ${updatedCoords[0].toFixed(4)})`;
                    item.address = null;
                    newMarker.setPopup(new maplibregl.Popup({
                        offset: 25
                    }).setHTML(buildPopupHtml(item.name, null)));
                    renderLocationList();
                    showToast(t('info'), t('location_not_found'), 'warning');
                }
            });

            selectedMarkerId = id;
            const hadRoute = lastSegmentDetails.length > 0;
            markersData.push({
                id,
                marker: newMarker,
                name: label,
                address,
                coords,
                placeId
            });
            operationGeneration++;
            if (hadRoute) invalidateRouteUI(); // existing route no longer matches marker set
            renderLocationList();
            updateRouteButtonsByCount();
            map.flyTo({
                center: coords,
                zoom: 15
            });
        }

        function removeLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) item.marker.remove();
            markersData = markersData.filter(m => m.id !== id);
            // Bump generation so any in-flight async operation aborts before mutating state
            operationGeneration++;
            // Existing route is stale (segments may reference the deleted marker) — clear it
            invalidateRouteUI();
            renderLocationList();
            updateRouteButtonsByCount();
        }

        function invalidateRouteUI() {
            try { removeRouteLayer(); } catch (_) {}
            const resultCard = document.getElementById('routeResultCard');
            const segContainer = document.getElementById('segmentListContainer');
            const emptyState = document.getElementById('routeEmptyState');
            const compare = document.getElementById('multiRouteCompare');
            if (resultCard) resultCard.style.display = 'none';
            if (segContainer) { segContainer.innerHTML = ''; segContainer.style.display = 'none'; }
            if (emptyState) emptyState.style.display = 'block';
            if (compare) compare.style.display = 'none';
            lastSegmentDetails = [];
            routeAlternatives = {};
            const dirSection = document.getElementById('directionsSection');
            if (dirSection) { dirSection.style.display = 'none'; }
            const dirWrap = document.getElementById('directionsWrap');
            if (dirWrap) { dirWrap.style.display = 'none'; }
        }

        function clearAllMarkers() {
            markersData.forEach(m => m.marker.remove());
            markersData = [];
            selectedMarkerId = null;

            removeRouteLayer();
            renderLocationList();
            updateRouteButtonsByCount();

            // Reset Route UI
            document.getElementById('routeResultCard').style.display = 'none';
            document.getElementById('segmentListContainer').style.display = 'none';
            document.getElementById('segmentListContainer').innerHTML = '';
            document.getElementById('routeEmptyState').style.display = 'block';

            switchTab('locations');
            showToast(t('reset'), t('reset_msg'), 'info');

            document.getElementById('locationsPanel').style.display = 'none';
        }

        function zoomToLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) {
                selectedMarkerId = id;
                map.flyTo({
                    center: item.coords,
                    zoom: 17
                });
                // Only open popup if currently closed — avoid toggling off an already-open popup
                const popup = item.marker.getPopup();
                if (popup && !popup.isOpen()) item.marker.togglePopup();
                renderLocationList();
            }
        }

        // === Location Detail Modal (v2 rich data) ===
        /**
         * [HYBRID] Open Place Details modal for a marker.
         * - If marker has placeId → call AWS GetPlace /v2/place/{id} (rich data)
         * - Otherwise → fall back to reverse-geocode by coords
         * Then renderLocationDetail() formats AWS response into the modal body.
         */
        async function showLocationDetail(id) {
            const item = markersData.find(m => m.id === id);
            if (!item) return;

            // Zoom to marker first (preserve old behavior)
            zoomToLocation(id);

            // Open modal
            const modal = new bootstrap.Modal(document.getElementById('locDetailModal'));
            modal.show();

            const titleEl = document.getElementById('locDetailTitle');
            const loadingEl = document.getElementById('locDetailLoading');
            const contentEl = document.getElementById('locDetailContent');
            const errorEl = document.getElementById('locDetailError');

            titleEl.textContent = item.name || t('loc_detail_title');
            loadingEl.style.display = 'block';
            contentEl.style.display = 'none';
            errorEl.style.display = 'none';

            try {
                let data;
                if (item.placeId) {
                    // Fetch full detail via GetPlace (richest data)
                    const res = await proxyGet(`/api/places/${item.placeId}`);
                    if (!res.ok) throw new Error('GetPlace failed');
                    data = await res.json();
                } else {
                    // Fallback: reverse geocode (has less detail)
                    const res = await proxyPost('/api/places/reverse', {
                        QueryPosition: item.coords,
                        MaxResults: 1,
                        Language: currentLang
                    });
                    if (!res.ok) throw new Error('ReverseGeocode failed');
                    const json = await res.json();
                    data = (json.ResultItems && json.ResultItems[0]) || null;
                }

                if (!data) throw new Error('No data');

                contentEl.innerHTML = renderLocationDetail(data, item);
                // Translate freshly-injected [data-i18n] nodes (Categories, Contacts, etc.)
                applyTranslations();
                loadingEl.style.display = 'none';
                contentEl.style.display = 'block';
            } catch (err) {
                console.error('Detail fetch failed:', err);
                loadingEl.style.display = 'none';
                errorEl.style.display = 'block';
            }
        }

        /**
         * [CUSTOM] Render Places v2 GetPlace response into the modal HTML.
         * Renders (when AWS returns the data):
         *   - Quick action row (Set as Start/Dest, Open in Google Maps, Copy coords, Fit on map)
         *   - Distance from current location (Locate Me marker, if set)
         *   - Title + PlaceType badge
         *   - Address breakdown (Street/SubDistrict/District/Locality/Region/PostalCode/Country)
         *   - Categories (deduped, primary highlighted)
         *   - Contacts (Phones/Websites/Emails) — usually empty in SEA
         *   - OpeningHours — usually empty in SEA
         *   - TimeZone
         *   - PlaceId in dev info collapsible
         */
        function renderLocationDetail(data, item) {
            const title = data.Title || item.name || '-';
            const address = (data.Address && data.Address.Label) || item.address || '';
            const placeType = data.PlaceType || '';
            const position = data.Position || item.coords;
            const categories = data.Categories || [];
            const contacts = data.Contacts || {};
            const openingHours = data.OpeningHours || [];
            const timeZone = data.TimeZone || null;
            const addr = data.Address || {};
            const mapView = data.MapView || null; // bounding box [west, south, east, north]

            // Store data for action handlers (need to reach them via global)
            currentDetailContext = { id: item.id, title, position, mapView };

            let html = '';

            // === Quick actions row ===
            const posStr = position ? `${position[1].toFixed(6)},${position[0].toFixed(6)}` : '';
            html += `<div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm flex-grow-1" style="background:#00B14F;color:#fff;font-size:0.74rem;font-weight:600;border:none;border-radius:8px;" onclick="placeDetailAction('start')">
                    <i class="bi bi-flag-fill me-1"></i> <span data-i18n="set_as_start">Set as Start</span>
                </button>
                <button type="button" class="btn btn-sm flex-grow-1" style="background:#dc2626;color:#fff;font-size:0.74rem;font-weight:600;border:none;border-radius:8px;" onclick="placeDetailAction('destination')">
                    <i class="bi bi-geo-fill me-1"></i> <span data-i18n="set_as_dest">Set as Destination</span>
                </button>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="https://www.google.com/maps?q=${posStr}" target="_blank" rel="noopener" class="btn btn-sm flex-grow-1" style="background:#fff;border:1px solid #d1d5db;color:#1f2937;font-size:0.72rem;font-weight:500;border-radius:8px;">
                    <i class="bi bi-box-arrow-up-right me-1" style="color:#2563eb;"></i> Google Maps
                </a>
                <button type="button" class="btn btn-sm flex-grow-1" style="background:#fff;border:1px solid #d1d5db;color:#1f2937;font-size:0.72rem;font-weight:500;border-radius:8px;" onclick="placeDetailAction('copy')">
                    <i class="bi bi-clipboard me-1" style="color:#7c3aed;"></i> <span data-i18n="copy_coords">Copy coords</span>
                </button>
                ${mapView ? `<button type="button" class="btn btn-sm flex-grow-1" style="background:#fff;border:1px solid #d1d5db;color:#1f2937;font-size:0.72rem;font-weight:500;border-radius:8px;" onclick="placeDetailAction('fit')">
                    <i class="bi bi-bounding-box me-1" style="color:#16a34a;"></i> <span data-i18n="fit_on_map">Fit on map</span>
                </button>` : ''}
            </div>

            <hr style="border-color:#e5e7eb;margin:0 0 12px;">`;

            // === Header: Title + PlaceType badge ===
            html += `<div class="mb-3">
                <div style="font-size:1.05rem;font-weight:700;color:#1f2937;line-height:1.3;">${escapeHtml(title)}</div>`;
            if (placeType) {
                const placeTypeLabel = placeType.replace(/([A-Z])/g, ' $1').trim();
                html += `<span class="badge mt-2" style="background:#ede9fe;color:#6d28d9;font-size:0.7rem;font-weight:600;">
                    <i class="bi bi-tag-fill me-1"></i>${escapeHtml(placeTypeLabel)}
                </span>`;
            }

            // === Distance from current location (if Locate Me was used) ===
            if (myLocationCoords && position) {
                const distKm = getDistanceFromLatLonInKm(
                    myLocationCoords[1], myLocationCoords[0],
                    position[1], position[0]
                );
                const useMiles = currentDistanceUnit === 'Miles';
                const distVal = useMiles ? distKm * 0.621371 : distKm;
                const distUnit = useMiles ? (t('unit_mi') || 'mi') : 'km';
                html += `<div class="mt-2 d-inline-flex align-items-center gap-1" style="background:#eff6ff;color:#1d4ed8;font-size:0.7rem;padding:3px 10px;border-radius:6px;font-weight:600;">
                    <i class="bi bi-rulers"></i> ${distVal.toFixed(2)} ${distUnit} <small style="font-weight:400;opacity:0.8;">${escapeHtml(t('from_you') || 'from you')}</small>
                </div>`;
            }
            html += `</div>`;

            // === Address section ===
            if (address || addr.Country) {
                html += `<div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-geo-alt-fill" style="color:#00B14F;font-size:1rem;"></i>
                        <div class="flex-grow-1" style="font-size:0.82rem;color:#1f2937;line-height:1.4;">${escapeHtml(address || '-')}</div>
                    </div>`;

                // Structured address breakdown (richer detail) — only show non-empty fields
                const addrRows = [
                    ['street', 'bi-signpost', addr.Street],
                    ['sub_district', 'bi-building', addr.SubDistrict],
                    ['district', 'bi-buildings', addr.District],
                    ['locality', 'bi-pin-map', addr.Locality],
                    ['region', 'bi-globe', addr.Region && addr.Region.Name],
                    ['postal_code', 'bi-mailbox', addr.PostalCode],
                    ['country', 'bi-flag', addr.Country && addr.Country.Name],
                ];
                const filledRows = addrRows.filter(([_, __, v]) => v);
                if (filledRows.length > 0) {
                    html += `<div class="mt-2 pt-2" style="border-top:1px dashed #e5e7eb;">`;
                    filledRows.forEach(([key, icon, value]) => {
                        html += `<div class="d-flex align-items-center gap-2 mb-1" style="font-size:0.72rem;">
                            <i class="bi ${icon}" style="color:#6b7280;width:14px;text-align:center;"></i>
                            <span style="color:#9ca3af;text-transform:capitalize;min-width:80px;" data-i18n="addr_${key}">${key.replace('_', ' ')}</span>
                            <span style="color:#1f2937;font-weight:500;">${escapeHtml(value)}</span>
                        </div>`;
                    });
                    html += `</div>`;
                }

                // Coordinates
                if (position) {
                    html += `<div class="mt-2 small" style="color:#6b7280;font-size:0.7rem;font-variant-numeric:tabular-nums;">
                        <i class="bi bi-crosshair me-1"></i>${position[1].toFixed(6)}, ${position[0].toFixed(6)}
                    </div>`;
                }
                html += `</div>`;
            }

            // === Categories ===
            if (categories.length > 0) {
                // De-duplicate by formatted name + sort primary first
                const seen = new Set();
                const unique = [];
                categories
                    .slice()
                    .sort((a, b) => (b.Primary ? 1 : 0) - (a.Primary ? 1 : 0))
                    .forEach(cat => {
                        const name = formatCategoryName(cat);
                        if (name && !seen.has(name.toLowerCase())) {
                            seen.add(name.toLowerCase());
                            unique.push({ ...cat, _displayName: name });
                        }
                    });

                if (unique.length > 0) {
                    html += `<div class="mb-3">
                        <div class="small fw-semibold mb-2" style="color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;font-size:0.7rem;">
                            <i class="bi bi-collection-fill me-1" style="color:#7c3aed;"></i> <span data-i18n="categories">Categories</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">`;
                    unique.forEach((cat, i) => {
                        const isPrimary = cat.Primary || (i === 0 && unique.length > 1);
                        const bg = isPrimary ? '#7c3aed' : '#ede9fe';
                        const color = isPrimary ? '#fff' : '#6d28d9';
                        html += `<span class="badge" style="background:${bg};color:${color};font-size:0.72rem;font-weight:500;padding:5px 10px;text-transform:capitalize;">${escapeHtml(cat._displayName)}</span>`;
                    });
                    html += `</div></div>`;
                }
            }

            // === Contacts (only if data exists — GrabMaps SEA doesn't expose Contact AdditionalFeature) ===
            const phones = contacts.Phones || [];
            const websites = contacts.Websites || [];
            const emails = contacts.Emails || [];
            if (phones.length || websites.length || emails.length) {
                html += `<div class="mb-3">
                    <div class="small fw-semibold mb-2" style="color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;font-size:0.7rem;">
                        <i class="bi bi-telephone-fill me-1" style="color:#0891b2;"></i> <span data-i18n="contacts">Contacts</span>
                    </div>
                    <div class="d-flex flex-column gap-2">`;
                phones.forEach(p => {
                    html += `<a href="tel:${escapeHtml(p.Value)}" class="d-flex align-items-center gap-2 text-decoration-none p-2 rounded-2" style="background:#ecfeff;color:#0e7490;font-size:0.8rem;border:1px solid #a5f3fc;">
                        <i class="bi bi-telephone-fill"></i> ${escapeHtml(p.Value)}
                    </a>`;
                });
                websites.forEach(w => {
                    html += `<a href="${escapeHtml(w.Value)}" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 text-decoration-none p-2 rounded-2 text-truncate" style="background:#eff6ff;color:#1d4ed8;font-size:0.8rem;border:1px solid #bfdbfe;">
                        <i class="bi bi-globe"></i> ${escapeHtml(w.Value)}
                    </a>`;
                });
                emails.forEach(em => {
                    html += `<a href="mailto:${escapeHtml(em.Value)}" class="d-flex align-items-center gap-2 text-decoration-none p-2 rounded-2" style="background:#fef3c7;color:#92400e;font-size:0.8rem;border:1px solid #fde68a;">
                        <i class="bi bi-envelope-fill"></i> ${escapeHtml(em.Value)}
                    </a>`;
                });
                html += `</div></div>`;
            }

            // === Opening Hours ===
            if (openingHours.length > 0) {
                const oh = openingHours[0];
                const isOpen = oh.OpenNow;
                const display = oh.Display || [];
                html += `<div class="mb-3">
                    <div class="small fw-semibold mb-2" style="color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;font-size:0.7rem;">
                        <i class="bi bi-clock-fill me-1" style="color:#d97706;"></i> <span data-i18n="opening_hours">Opening Hours</span>
                        ${isOpen !== undefined ? `<span class="badge ms-1" style="background:${isOpen ? '#dcfce7' : '#fee2e2'};color:${isOpen ? '#166534' : '#991b1b'};font-size:0.6rem;">${isOpen ? t('open_now') : t('closed_now')}</span>` : ''}
                    </div>
                    <div class="p-2 rounded-2" style="background:#fef3c7;border:1px solid #fde68a;font-size:0.75rem;color:#78350f;">`;
                if (display.length > 0) {
                    display.forEach(line => {
                        html += `<div>${escapeHtml(line)}</div>`;
                    });
                } else {
                    html += `<div data-i18n="schedule_unavailable">Schedule details unavailable</div>`;
                }
                html += `</div></div>`;
            }

            // === Note: GrabMaps SEA limitations ===
            // If neither Contacts nor OpeningHours present, note that they're not exposed by GrabMaps in SEA
            if (!phones.length && !websites.length && !emails.length && !openingHours.length) {
                html += `<div class="mb-3 p-2 rounded-2 small d-flex align-items-start gap-2" style="background:#f3f4f6;color:#6b7280;font-size:0.7rem;border:1px dashed #d1d5db;">
                    <i class="bi bi-info-circle mt-1" style="color:#9ca3af;"></i>
                    <span data-i18n="grabmaps_sea_limit">Phone, website & opening hours are not available for this region in GrabMaps data.</span>
                </div>`;
            }

            // === TimeZone ===
            if (timeZone && timeZone.Name) {
                html += `<div class="d-flex align-items-center gap-2 small p-2 rounded-2 mb-2" style="background:#f3f4f6;color:#4b5563;font-size:0.75rem;">
                    <i class="bi bi-globe-asia-australia"></i>
                    <span><b data-i18n="timezone">Timezone</b>: ${escapeHtml(timeZone.Name)}${timeZone.Offset ? ` (UTC${timeZone.Offset})` : ''}</span>
                </div>`;
            }

            // === PlaceId (debug/dev info) ===
            if (data.PlaceId || item.placeId) {
                const pid = data.PlaceId || item.placeId;
                html += `<details class="mt-3">
                    <summary style="cursor:pointer;color:#9ca3af;font-size:0.7rem;font-weight:500;">
                        <i class="bi bi-code-slash me-1"></i><span data-i18n="dev_info">Developer Info</span>
                    </summary>
                    <div class="mt-2 p-2 rounded-2" style="background:#1f2937;color:#d1d5db;font-family:monospace;font-size:0.65rem;word-break:break-all;">
                        <div style="color:#9ca3af;">PlaceId:</div>
                        <div>${escapeHtml(pid)}</div>
                    </div>
                </details>`;
            }

            return html;
        }

        // Format category name nicely:
        // - Prefer LocalizedName > Name > Id
        // - If string contains '::', extract last meaningful segment
        // - Trim and clean up
        function formatCategoryName(cat) {
            let raw = cat.LocalizedName || cat.Name || cat.Id || '';
            if (!raw) return '';
            // If contains ::, take last non-empty segment (handles "commercial building::shopping area::shopping plaza")
            if (raw.includes('::')) {
                const parts = raw.split('::').map(p => p.trim()).filter(p => p !== '');
                if (parts.length > 0) raw = parts[parts.length - 1];
            }
            return raw.trim();
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /**
         * [AWS] Reverse-geocode (proxied to AWS /v2/reverse-geocode).
         * Returns { title, address } or null on no-result/error.
         * Used by map-click & marker-drag flows. Language follows currentLang.
         */
        async function getPlaceNameByCoords(coords) {
            try {
                const response = await proxyPost('/api/places/reverse', {
                    QueryPosition: coords,
                    MaxResults: 5,                    // ask for top 5 — we may filter out distant POIs
                    Language: currentLang
                });

                if (!response.ok) throw new Error('API Error');
                const data = await response.json();
                const items = data.ResultItems;
                if (!items || items.length === 0) return null;

                const top = items[0];
                const dist = Number(top.Distance) || 0;
                const addr = top.Address || {};
                const POI_PROXIMITY_M = 50; // POI within this distance → trust as "the place user clicked"

                // POI is close enough — return as-is (current behaviour)
                if (top.PlaceType !== 'PointOfInterest' || dist <= POI_PROXIMITY_M) {
                    const title = top.Title || addr.Label || '';
                    const address = addr.Label || '';
                    return {
                        title: title || address || null,
                        address: title && address && title !== address ? address : null
                    };
                }

                // POI is far (>50m) — user likely clicked an empty area (sawah/lapangan/jalan).
                // Don't claim it's the POI. Synthesize a neutral label from structured address parts.
                const localTitle = addr.Street || addr.SubDistrict || addr.District || addr.Locality
                    || `Pinned (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;

                // Build sub-line from area hierarchy, deduplicating repeated parts
                const parts = [addr.SubDistrict, addr.District, addr.Locality, addr.PostalCode]
                    .filter(Boolean)
                    .filter((v, i, a) => a.indexOf(v) === i);
                const subLine = parts.join(', ');

                // Optional info — let caller surface "near X" toast if useful (not embedded in title)
                return {
                    title: localTitle,
                    address: subLine || null,
                    nearby: { name: top.Title, distance: Math.round(dist) }
                };
            } catch (error) {
                console.error("Reverse geocode failed:", error);
                return null;
            }
        }


        /* =========================================
           5. ROUTING LOGIC
           ========================================= */

        // --- Single Route (A -> B) ---
        // Convert a single v2-adapted route to internal alternative shape (features, summary, legs)
        function buildAlternativeFromRoute(routeData, meta) {
            const features = [{
                type: 'Feature',
                properties: { color: meta.color || '#00B14F' },
                geometry: {
                    type: 'LineString',
                    coordinates: (routeData.Legs && routeData.Legs[0] && routeData.Legs[0].Geometry && routeData.Legs[0].Geometry.LineString) || []
                }
            }];
            return {
                label: meta.label,
                icon: meta.icon || 'bi-arrow-right',
                color: meta.color || '#6b7280',
                recommended: !!meta.recommended,
                features,
                segmentDetails: [], // A→B has no per-segment cards
                totalDistance: (routeData.Summary && routeData.Summary.Distance) || 0,
                totalDuration: (routeData.Summary && routeData.Summary.DurationSeconds) || 0,
                legs: routeData.Legs || [],
                majorRoads: routeData.MajorRoadLabels || [],
                tolls: routeData.Tolls || null,
            };
        }

        /**
         * [HYBRID] A→B route calculation with up to 2 alternatives.
         * Flow: collect 2 markers + travel mode + avoidances → call /api/routes/calculate
         * (which adapts to AWS v2 /v2/routes) with MaxAlternatives=2 → cache all routes
         * into routeAlternatives (primary/alt1/alt2) → render pills + draw primary.
         * Sends DepartureTime if set (AWS uses historical traffic patterns).
         */
        async function calculateRoute() {
            if (markersData.length < 2) return showToast(t('insufficient_data'), t('add_at_least_2'), 'warning');
            if (routeCalcInFlight) return;
            routeCalcInFlight = true;
            setRouteButtonsDisabled(true);

            const origin = markersData[0].coords;
            const destination = markersData[1].coords;
            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            showToast(t('processing'), t('calc_single_route'), 'info');

            // Reset cached alternatives — A→B uses different keys (primary/alt1/alt2)
            routeAlternatives = {};

            try {
                const response = await proxyPost('/api/routes/calculate', {
                    DeparturePosition: origin,
                    DestinationPosition: destination,
                    TravelMode: selectedMode,
                    DistanceUnit: currentDistanceUnit,
                    DepartNow: !departureTimeIso,
                    DepartureTime: departureTimeIso || undefined,
                    IncludeLegGeometry: true,
                    MaxAlternatives: 2,
                    Avoid: getAvoidances()
                });
                if (!response.ok) throw new Error('Failed');
                const data = await response.json();

                if (data.Legs && data.Legs.length > 0 && data.Legs[0].Geometry) {
                    // Primary route
                    routeAlternatives.primary = buildAlternativeFromRoute(data, {
                        label: t('fastest_route'),
                        icon: 'bi-stars',
                        color: '#00B14F',
                        recommended: true,
                    });

                    // Alternative routes (if returned)
                    const altColors = ['#2563eb', '#dc2626', '#7c3aed'];
                    const altIcons = ['bi-signpost-split-fill', 'bi-signpost-2-fill', 'bi-signpost-fill'];
                    (data.Alternatives || []).forEach((altRoute, idx) => {
                        if (!altRoute.Legs || !altRoute.Legs[0] || !altRoute.Legs[0].Geometry) return;
                        routeAlternatives['alt' + (idx + 1)] = buildAlternativeFromRoute(altRoute, {
                            label: t('alternative_n', { n: idx + 1 }),
                            icon: altIcons[idx % altIcons.length],
                            color: altColors[idx % altColors.length],
                            recommended: false,
                        });
                    });

                    applyMultiRouteAlternative('primary');
                    renderMultiRouteCompare('primary');
                    switchTab('routes');
                } else {
                    invalidateRouteUI();
                    showToast(t('error'), t('path_not_found'), 'error');
                }
            } catch (e) {
                console.error(e);
                invalidateRouteUI();
                showToast(t('error'), t('failed'), 'error');
            } finally {
                routeCalcInFlight = false;
                setRouteButtonsDisabled(false);
            }
        }

        // Run one batched multi-stop route calculation for a given ordered list of markers.
        // Returns { features, segmentDetails, totalDistance, totalDuration, legs } or throws on error.
        async function runMultiRouteBatch(workingData, selectedMode) {
            const colors = ['#00B14F', '#007bff', '#dc3545', '#fd7e14', '#6f42c1', '#e83e8c', '#17a2b8'];
            const MAX_STOPS = 25;
            let totalDistance = 0;
            let totalDuration = 0;
            let allRouteFeatures = [];
            let globalLegIndex = 0;
            let segmentDetails = [];
            let allLegs = [];

            for (let i = 0; i < workingData.length - 1; i += (MAX_STOPS - 1)) {
                const chunk = workingData.slice(i, i + MAX_STOPS);
                const origin = chunk[0].coords;
                const destination = chunk[chunk.length - 1].coords;
                const waypoints = chunk.length > 2 ? chunk.slice(1, -1).map(m => m.coords) : [];

                const response = await proxyPost('/api/routes/calculate', {
                    DeparturePosition: origin,
                    DestinationPosition: destination,
                    WaypointPositions: waypoints,
                    TravelMode: selectedMode,
                    DistanceUnit: currentDistanceUnit,
                    DepartNow: !departureTimeIso,
                    DepartureTime: departureTimeIso || undefined,
                    IncludeLegGeometry: true,
                    Avoid: getAvoidances()
                });

                if (!response.ok) throw new Error('Batch error: ' + response.status);
                const data = await response.json();

                totalDistance += (data.Summary && data.Summary.Distance) || 0;
                totalDuration += (data.Summary && data.Summary.DurationSeconds) || 0;

                if (data.Legs && data.Legs.length > 0) {
                    data.Legs.forEach((leg, legIndexInBatch) => {
                        if (leg.Geometry && leg.Geometry.LineString) {
                            const segmentColor = colors[globalLegIndex % colors.length];

                            allRouteFeatures.push({
                                type: 'Feature',
                                properties: { color: segmentColor },
                                geometry: {
                                    type: 'LineString',
                                    coordinates: leg.Geometry.LineString
                                }
                            });

                            const startNode = workingData[i + legIndexInBatch];
                            const endNode = workingData[i + legIndexInBatch + 1];

                            segmentDetails.push({
                                from: startNode.name || 'Unknown Point',
                                to: endNode.name || 'Unknown Point',
                                distance: leg.Distance,
                                duration: leg.DurationSeconds,
                                color: segmentColor,
                                geometry: leg.Geometry.LineString
                            });

                            // Accumulate raw leg (with Steps) for turn-by-turn rendering
                            allLegs.push(leg);
                            globalLegIndex++;
                        }
                    });
                }
            }

            return { features: allRouteFeatures, segmentDetails, totalDistance, totalDuration, legs: allLegs };
        }

        // Render one cached alternative onto the map + result panel + directions
        function applyMultiRouteAlternative(key) {
            const alt = routeAlternatives[key];
            if (!alt) return;

            const featureCollection = { type: 'FeatureCollection', features: alt.features };
            drawRouteOnMap(featureCollection);

            // Distance — convert km → mi if user prefers Miles
            const useMiles = currentDistanceUnit === 'Miles';
            const distVal = useMiles ? alt.totalDistance * 0.621371 : alt.totalDistance;
            const distUnit = useMiles ? (t('unit_mi') || 'mi') : 'km';
            document.getElementById('resDistance').innerText = distVal.toFixed(1) + ' ' + distUnit;
            document.getElementById('resDuration').innerText = formatDuration(alt.totalDuration);
            document.getElementById('routeEmptyState').style.display = 'none';
            document.getElementById('routeResultCard').style.display = 'block';
            document.getElementById('segmentListContainer').style.display = 'block';

            // Extra info row: ETA, Tolls, Major roads
            renderRouteExtraInfo(alt);

            lastSegmentDetails = alt.segmentDetails;
            renderSegmentList(alt.segmentDetails);

            // Render turn-by-turn directions for this alternative's legs
            const dirSection = document.getElementById('directionsSection');
            if (alt.legs && alt.legs.some(l => (l.Steps || []).length > 0)) {
                dirSection.style.display = 'block';
                renderDirections(alt.legs);
            } else {
                dirSection.style.display = 'none';
            }
        }

        function renderRouteExtraInfo(alt) {
            const extraWrap = document.getElementById('resExtraInfo');
            const etaEl = document.getElementById('resEta');
            const etaVal = document.getElementById('resEtaValue');
            const tollsEl = document.getElementById('resTolls');
            const tollsVal = document.getElementById('resTollsValue');
            const roadsWrap = document.getElementById('resMajorRoads');
            const roadsVal = document.getElementById('resMajorRoadsValue');

            let anyVisible = false;

            // ETA — base on departureTime if user picked one, else now
            if (alt.totalDuration > 0) {
                const baseMs = departureTimeIso ? new Date(departureTimeIso).getTime() : Date.now();
                const arriveMs = baseMs + alt.totalDuration * 1000;
                const arrive = new Date(arriveMs);
                const hh = String(arrive.getHours()).padStart(2, '0');
                const mm = String(arrive.getMinutes()).padStart(2, '0');
                const sameDay = arrive.toDateString() === new Date(baseMs).toDateString();
                etaVal.innerText = sameDay ? `${hh}:${mm}` : `${arrive.toLocaleDateString(currentLang === 'id' ? 'id-ID' : 'en-US', {month:'short', day:'numeric'})} ${hh}:${mm}`;
                etaEl.classList.remove('d-none');
                anyVisible = true;
            } else {
                etaEl.classList.add('d-none');
            }

            // Tolls
            if (alt.tolls && alt.tolls.Total > 0) {
                const currency = alt.tolls.Currency || 'IDR';
                let formatted;
                try {
                    formatted = new Intl.NumberFormat(currentLang === 'id' ? 'id-ID' : 'en-US', {
                        style: 'currency',
                        currency,
                        maximumFractionDigits: 0
                    }).format(alt.tolls.Total);
                } catch (_) {
                    formatted = `${currency} ${alt.tolls.Total.toLocaleString()}`;
                }
                tollsVal.innerText = formatted;
                tollsEl.classList.remove('d-none');
                anyVisible = true;
            } else {
                tollsEl.classList.add('d-none');
            }

            // Major roads — show first 3, joined with comma
            if (alt.majorRoads && alt.majorRoads.length > 0) {
                roadsVal.innerText = alt.majorRoads.slice(0, 3).join(', ');
                roadsWrap.style.display = 'block';
                anyVisible = true;
            } else {
                roadsWrap.style.display = 'none';
            }

            extraWrap.style.display = anyVisible ? 'block' : 'none';
        }

        // Render N route option pills dynamically — works for both Multi (optimized/original)
        // and A→B with alternatives (primary/alt1/alt2/...).
        function renderMultiRouteCompare(activeKey) {
            const compare = document.getElementById('multiRouteCompare');
            const pillsContainer = document.getElementById('routeOptionsPills');
            const savings = document.getElementById('optSavings');
            const savingsText = document.getElementById('optSavingsText');

            const entries = Object.entries(routeAlternatives).filter(([_, v]) => v != null);
            pillsContainer.innerHTML = '';

            if (entries.length < 2) {
                compare.style.display = 'none';
                savings.style.display = 'none';
                return;
            }

            compare.style.display = 'block';

            entries.forEach(([key, alt]) => {
                const isActive = key === activeKey;
                const recBadge = alt.recommended
                    ? `<span class="badge bg-success ms-2" style="font-size:0.6rem;">${escapeHtml(t('recommended'))}</span>`
                    : '';
                const label = document.createElement('label');
                label.className = 'route-opt-pill';
                label.dataset.route = key;
                label.innerHTML = `
                    <input type="radio" name="multiRouteChoice" value="${escapeHtml(key)}" ${isActive ? 'checked' : ''}>
                    <div class="opt-content">
                        <div class="opt-head">
                            <i class="bi ${escapeHtml(alt.icon || 'bi-arrow-right')}" style="color:${escapeHtml(alt.color || '#6b7280')};"></i>
                            <strong>${escapeHtml(alt.label || key)}</strong>
                            ${recBadge}
                        </div>
                        <div class="opt-stats">${alt.totalDistance.toFixed(1)} km • ${escapeHtml(formatDuration(alt.totalDuration))}</div>
                    </div>
                    <i class="bi bi-check-circle-fill opt-check"></i>
                `;
                pillsContainer.appendChild(label);
            });

            // Savings line — only show when user is NOT on the fastest route.
            // Message: "Save X with the fastest route" (i.e. switch to fastest to save X).
            const all = entries.map(([_, a]) => a);
            const fastest = all.reduce((m, a) => a.totalDuration < m.totalDuration ? a : m, all[0]);
            const active = routeAlternatives[activeKey];
            const savedSec = active ? active.totalDuration - fastest.totalDuration : 0;
            if (active && active !== fastest && savedSec >= 10) {
                savings.style.display = 'block';
                savingsText.innerText = t('save_with_fastest', { duration: formatDuration(savedSec) });
            } else {
                savings.style.display = 'none';
            }
        }

        /**
         * [HYBRID] Multi-stop routing (3+ markers).
         * Steps:
         *   1. Snapshot original input order
         *   2. Compute optimized order — either Haversine NN (Fast) or 2-opt on real route matrix (Precise)
         *   3. Call /api/routes/calculate for BOTH orders (max 25 stops per batch)
         *   4. Cache both into routeAlternatives.{optimized, original}
         *   5. Render Route Options pills + draw optimized by default
         * Auto-skips the input-order request if optimized order is identical (saves 1 API call).
         */
        async function calculateMultiRoute() {
            if (markersData.length < 2) return showToast(t('insufficient_data'), t('add_at_least_2'), 'warning');
            if (routeCalcInFlight) return;
            routeCalcInFlight = true;
            setRouteButtonsDisabled(true);
            const myGen = ++operationGeneration;

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;
            const optimizationMode = document.querySelector('input[name="optMode"]:checked').value;

            // Snapshot original input order BEFORE optimization
            const originalOrder = [...markersData];

            // Step 1: Compute optimized order
            let optimizedOrder = [];
            if (optimizationMode === 'real') {
                showToast(t('optimizing'), t('analyzing_traffic'), 'info');
                try {
                    optimizedOrder = await optimizeMarkersOrderReal(originalOrder, selectedMode);
                } catch (e) {
                    console.error(e);
                    showToast(t('warning'), t('opt_failed_fallback'), 'warning');
                    optimizedOrder = [...originalOrder];
                }
            } else {
                showToast(t('optimizing'), t('reordering_stops_sl'), 'info');
                optimizedOrder = optimizeMarkersOrder([...originalOrder]);
            }

            if (myGen !== operationGeneration) {
                routeCalcInFlight = false;
                setRouteButtonsDisabled(false);
                return;
            }

            // Step 2: Compute BOTH route geometries
            showToast(t('processing'), t('calc_final_route'), 'info');
            routeAlternatives = {};

            // Detect whether optimization actually changed the order
            const sameOrder = originalOrder.length === optimizedOrder.length
                && originalOrder.every((m, i) => m.id === optimizedOrder[i].id);

            try {
                // Always compute the optimized one (this is the recommendation)
                const optResult = await runMultiRouteBatch(optimizedOrder, selectedMode);
                routeAlternatives.optimized = {
                    label: t('fastest_route'),
                    icon: 'bi-stars',
                    color: '#16a34a',
                    recommended: true,
                    ...optResult,
                };

                if (myGen !== operationGeneration) return;

                // Only compute the original-order route if it differs from optimized (saves API calls)
                if (!sameOrder) {
                    try {
                        const origResult = await runMultiRouteBatch(originalOrder, selectedMode);
                        routeAlternatives.original = {
                            label: t('input_order'),
                            icon: 'bi-list-ol',
                            color: '#6b7280',
                            recommended: false,
                            ...origResult,
                        };
                    } catch (e) {
                        console.warn('Input-order route failed (will only show fastest):', e);
                    }
                }

                if (myGen !== operationGeneration) return;

                if (!routeAlternatives.optimized || routeAlternatives.optimized.features.length === 0) {
                    invalidateRouteUI();
                    showToast(t('error'), t('route_geom_missing'), 'error');
                    return;
                }

                // Default: render the optimized (fastest) route
                applyMultiRouteAlternative('optimized');
                renderMultiRouteCompare('optimized');
                switchTab('routes');
                showToast(t('success'), t('opt_route_done'), 'success');
            } catch (error) {
                console.error(error);
                invalidateRouteUI();
                showToast(t('error'), t('failed_calc_route'), 'error');
            } finally {
                routeCalcInFlight = false;
                setRouteButtonsDisabled(false);
            }
        }

        // Wire up the toggle pills (deferred until DOM ready below in setupEventListeners path is unrelated;
        // do it at script load — radios exist in initial HTML).
        document.addEventListener('change', (ev) => {
            const radio = ev.target.closest('input[name="multiRouteChoice"]');
            if (!radio) return;
            applyMultiRouteAlternative(radio.value);
            // Refresh compare panel so the "savings" line reflects the newly-active route
            // (e.g. when user picks the slowest option, savings should hide; when picking
            // fastest while another was active, it should re-appear).
            renderMultiRouteCompare(radio.value);
        });

        function setRouteButtonsDisabled(disabled) {
            const btn = document.getElementById('btnCalcUnified');
            if (btn) {
                btn.disabled = disabled;
                btn.style.opacity = disabled ? '0.55' : '';
                btn.style.pointerEvents = disabled ? 'none' : '';
            }
            // After a forced toggle (e.g. during calculation), restore the count-based logic
            if (!disabled) updateRouteButtonsByCount();
        }

        // Single unified route button — auto-routes to A→B (exactly 2 markers) or Multi (3+).
        // Hidden entirely when <2 markers.
        function updateRouteButtonsByCount() {
            // Don't override the "in-flight" disable from setRouteButtonsDisabled(true)
            if (routeCalcInFlight) return;

            const row = document.getElementById('calcRouteRow');
            const avoidRow = document.getElementById('avoidRow');
            const quickRow = document.getElementById('quickActionsRow');
            const btn = document.getElementById('btnCalcUnified');
            const icon = document.getElementById('btnCalcIcon');
            const label = document.getElementById('btnCalcLabel');
            if (!row || !btn) return;

            const count = markersData.length;

            if (count < 2) {
                row.style.display = 'none';
                if (avoidRow) avoidRow.style.display = 'none';
                if (quickRow) quickRow.style.display = 'none';
                return;
            }

            row.style.display = 'flex';
            if (avoidRow) avoidRow.style.display = 'block';
            if (quickRow) quickRow.style.display = 'flex';
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.pointerEvents = '';

            if (count === 2) {
                icon.className = 'bi bi-sign-turn-right-fill me-2';
                label.textContent = t('calc_route_ab') || 'Calculate Route (A→B)';
                btn.title = t('route_a_b') || 'Calculate Route A to B';
            } else {
                icon.className = 'bi bi-diagram-3-fill me-2';
                label.textContent = t('calc_route_multi') || 'Calculate Multi-Stop Route';
                btn.title = t('route_multi') || 'Calculate Multi-Stop Route';
            }
        }

        // Single entry-point — routes to A→B or Multi based on count
        function calculateUnified() {
            const count = markersData.length;
            if (count < 2) return;
            if (count === 2) {
                calculateRoute();
            } else {
                calculateMultiRoute();
            }
        }

        /* =========================================
           LOCATE ME (geolocation)
           ========================================= */
        let myLocationMarker = null;
        let myLocationCoords = null; // [lng, lat] — set after successful Locate Me
        let currentDetailContext = null; // { id, title, position, mapView } — set when Place Details modal opens

        // Handle Quick Action buttons in the Place Details modal.
        // type: 'start' | 'destination' | 'copy' | 'fit'
        function placeDetailAction(type) {
            const ctx = currentDetailContext;
            if (!ctx || !ctx.position) return;
            const [lng, lat] = ctx.position;

            if (type === 'copy') {
                const text = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                const fallback = () => {
                    const ta = document.createElement('textarea');
                    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                    document.body.appendChild(ta); ta.select();
                    try { document.execCommand('copy'); showToast(t('success'), t('copied') + ': ' + text, 'success'); }
                    catch (_) { showToast(t('error'), 'Copy failed', 'error'); }
                    document.body.removeChild(ta);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text)
                        .then(() => showToast(t('success'), t('copied') + ': ' + text, 'success'))
                        .catch(fallback);
                } else { fallback(); }
                return;
            }

            if (type === 'fit' && ctx.mapView && ctx.mapView.length === 4) {
                // MapView shape: [west, south, east, north]
                const [west, south, east, north] = ctx.mapView;
                map.fitBounds([[west, south], [east, north]], { padding: getMapFitPadding(), duration: 800, maxZoom: 18 });
                bootstrap.Modal.getInstance(document.getElementById('locDetailModal'))?.hide();
                return;
            }

            if (type === 'start' || type === 'destination') {
                // Replace the corresponding marker (start = index 0, dest = last)
                const existing = markersData.find(m => m.id === ctx.id);
                if (existing) {
                    // Move existing marker to start or end of list
                    markersData = markersData.filter(m => m.id !== ctx.id);
                    if (type === 'start') markersData.unshift(existing);
                    else markersData.push(existing);
                } else {
                    // Add new marker at this place
                    addLocation(ctx.position, ctx.title);
                    const newItem = markersData[markersData.length - 1];
                    if (newItem) {
                        markersData = markersData.filter(m => m.id !== newItem.id);
                        if (type === 'start') markersData.unshift(newItem);
                        else markersData.push(newItem);
                    }
                }
                operationGeneration++;
                invalidateRouteUI();
                renderLocationList();
                updateRouteButtonsByCount();
                bootstrap.Modal.getInstance(document.getElementById('locDetailModal'))?.hide();
                showToast(t('success'), type === 'start' ? t('set_as_start_done') || 'Set as start' : t('set_as_dest_done') || 'Set as destination', 'success');
            }
        }
        /**
         * [HELPER + CUSTOM] Browser geolocation (NOT AWS — uses navigator.geolocation).
         * Drops a blue marker at user's GPS pos + saves coords to `myLocationCoords`
         * which is then used as the "from" reference in Place Details distance badge.
         */
        function locateMe() {
            const btn = document.getElementById('btnLocateMe');
            if (!navigator.geolocation) {
                showToast(t('error'), t('geolocation_unsupported') || 'Geolocation not supported', 'error');
                return;
            }
            if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lng = pos.coords.longitude;
                    const lat = pos.coords.latitude;
                    const accuracy = pos.coords.accuracy;
                    myLocationCoords = [lng, lat]; // remember for distance calcs
                    map.flyTo({ center: [lng, lat], zoom: 16, speed: 1.2 });
                    if (myLocationMarker) myLocationMarker.remove();
                    myLocationMarker = new maplibregl.Marker({ color: '#2563eb' })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(
                            `<strong>${escapeHtml(t('you_are_here') || 'You are here')}</strong><br>
                             <small>±${Math.round(accuracy)}m</small><br>
                             <small>${lat.toFixed(6)}, ${lng.toFixed(6)}</small>`
                        ))
                        .addTo(map);
                    myLocationMarker.togglePopup();
                    showToast(t('success'), t('located_within', { m: Math.round(accuracy) }) || `Located within ${Math.round(accuracy)}m`, 'success');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-crosshair"></i>'; }
                },
                (err) => {
                    const msgs = { 1: t('geo_permission_denied'), 2: t('geo_unavailable'), 3: t('geo_timeout') };
                    showToast(t('error'), msgs[err.code] || t('geo_failed') || 'Failed to get location', 'error');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-crosshair"></i>'; }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        /* =========================================
           MAP STYLE SWITCHER (Standard/Monochrome × Light/Dark)
           ========================================= */
        const MAP_LIGHT_START_HOUR = 6;
        const MAP_DARK_START_HOUR = 18;

        function getTimeBasedMapColor(date = new Date()) {
            const hour = date.getHours();
            return hour >= MAP_LIGHT_START_HOUR && hour < MAP_DARK_START_HOUR ? 'Light' : 'Dark';
        }

        function getNextMapThemeDelay() {
            const now = new Date();
            const nextSwitch = new Date(now);
            const nextHour = now.getHours() < MAP_LIGHT_START_HOUR
                ? MAP_LIGHT_START_HOUR
                : (now.getHours() < MAP_DARK_START_HOUR ? MAP_DARK_START_HOUR : MAP_LIGHT_START_HOUR);

            nextSwitch.setHours(nextHour, 0, 0, 0);
            if (nextSwitch <= now) {
                nextSwitch.setDate(nextSwitch.getDate() + 1);
            }

            return nextSwitch.getTime() - now.getTime();
        }

        const mapStyleState = { style: 'Standard', color: getTimeBasedMapColor() };
        let mapStyleLoading = false;
        let mapAutoThemeTimer = null;
        let lastAutoMapColor = mapStyleState.color;

        function buildMapStyleUrl() {
            const params = new URLSearchParams({
                style: mapStyleState.style,
                color: mapStyleState.color
            });

            return `/api/v2/map-style?${params.toString()}`;
        }

        function syncMapStyleButtons() {
            document.querySelectorAll('.map-style-btn[data-style]').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.style === mapStyleState.style);
            });
            document.querySelectorAll('.map-style-btn[data-color]').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.color === mapStyleState.color);
            });
            // Sync UI theme with map color scheme (Dark map → dark panels)
            const theme = mapStyleState.color === 'Dark' ? 'dark' : 'light';
            document.body.setAttribute('data-map-theme', theme);
        }

        function toggleStylePanel() {
            const panel = document.getElementById('mapStylePanel');
            if (!panel) return;
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        }

        function applyMapStyle() {
            if (!map) return;
            mapStyleLoading = true;
            document.querySelectorAll('.map-style-btn').forEach(b => b.disabled = true);
            map.setStyle(buildMapStyleUrl());
        }

        function applyTimeBasedMapStyle() {
            const timeBasedColor = getTimeBasedMapColor();
            if (timeBasedColor === lastAutoMapColor) {
                syncMapStyleButtons();
                return;
            }

            lastAutoMapColor = timeBasedColor;
            if (mapStyleState.color === timeBasedColor) {
                syncMapStyleButtons();
                return;
            }

            mapStyleState.color = timeBasedColor;
            syncMapStyleButtons();
            applyMapStyle();
        }

        function scheduleAutoMapStyle() {
            if (mapAutoThemeTimer) {
                clearTimeout(mapAutoThemeTimer);
            }

            mapAutoThemeTimer = setTimeout(() => {
                applyTimeBasedMapStyle();
                scheduleAutoMapStyle();
            }, getNextMapThemeDelay() + 1000);
        }

        // Wire map-style buttons + reattach route layers after style swap
        document.addEventListener('click', (ev) => {
            const btn = ev.target.closest('.map-style-btn');
            if (!btn || btn.disabled || mapStyleLoading) return;
            if (btn.dataset.style) {
                if (mapStyleState.style === btn.dataset.style) return;
                mapStyleState.style = btn.dataset.style;
                syncMapStyleButtons();
                applyMapStyle();
            } else if (btn.dataset.color) {
                if (mapStyleState.color === btn.dataset.color) return;
                mapStyleState.color = btn.dataset.color;
                syncMapStyleButtons();
                applyMapStyle();
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                applyTimeBasedMapStyle();
            }
        });

        /* =========================================
           PICK COORDINATES MODE
           ========================================= */
        let pickCoordsActive = false;
        let pickedCoordsText = '';
        function togglePickCoords() {
            pickCoordsActive = !pickCoordsActive;
            const btn = document.getElementById('btnPickCoords');
            if (btn) btn.classList.toggle('active', pickCoordsActive);
            document.body.classList.toggle('pick-coords-mode', pickCoordsActive);
            if (pickCoordsActive) {
                showToast(t('info'), t('pick_coords_on') || 'Click any point on the map', 'info');
            } else {
                document.getElementById('pickCoordsPopup').style.display = 'none';
            }
        }
        function copyPickedCoords() {
            if (!pickedCoordsText) return;
            const doCopy = navigator.clipboard && window.isSecureContext
                ? navigator.clipboard.writeText(pickedCoordsText)
                : Promise.reject('no clipboard');
            doCopy.then(() => showToast(t('success'), t('copied') + ': ' + pickedCoordsText, 'success'))
                .catch(() => {
                    // Fallback
                    const ta = document.createElement('textarea');
                    ta.value = pickedCoordsText;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); showToast(t('success'), t('copied') + ': ' + pickedCoordsText, 'success'); }
                    catch (_) { showToast(t('error'), 'Copy failed', 'error'); }
                    document.body.removeChild(ta);
                });
        }

        /* =========================================
           SWAP A↔B
           ========================================= */
        function swapStartEnd() {
            if (markersData.length < 2) return;
            markersData = markersData.slice().reverse();
            operationGeneration++;
            // Existing route is now invalid because the order changed
            invalidateRouteUI();
            renderLocationList();
            showToast(t('reset'), t('order_reversed') || 'Order reversed', 'info');
        }

        /* =========================================
           DISTANCE UNIT TOGGLE
           ========================================= */
        function toggleDistanceUnit() {
            currentDistanceUnit = currentDistanceUnit === 'Kilometers' ? 'Miles' : 'Kilometers';
            localStorage.setItem('home_dist_unit', currentDistanceUnit);
            const lbl = document.getElementById('distUnitLabel');
            if (lbl) lbl.innerText = currentDistanceUnit === 'Miles' ? (t('unit_mi') || 'mi') : 'km';
            // Re-render active route if any (numbers stay in km internally, just display converts)
            const activeRadio = document.querySelector('input[name="multiRouteChoice"]:checked');
            const fallbackKey = Object.keys(routeAlternatives).find(k => routeAlternatives[k] != null);
            const activeKey = activeRadio ? activeRadio.value : fallbackKey;
            if (activeKey && routeAlternatives[activeKey]) {
                applyMultiRouteAlternative(activeKey);
                renderMultiRouteCompare(activeKey);
            }
        }

        /* =========================================
           DEPARTURE TIME PICKER
           ========================================= */
        function toggleDeparturePanel() {
            const panel = document.getElementById('departurePanel');
            if (!panel) return;
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        }
        function clearDeparture() {
            departureTimeIso = null;
            const input = document.getElementById('departureInput');
            if (input) input.value = '';
            const lbl = document.getElementById('departureLabel');
            if (lbl) lbl.innerText = t('depart_now');
            // Re-render active alt ETA
            const activeRadio = document.querySelector('input[name="multiRouteChoice"]:checked');
            const fallbackKey = Object.keys(routeAlternatives).find(k => routeAlternatives[k] != null);
            const activeKey = activeRadio ? activeRadio.value : fallbackKey;
            if (activeKey && routeAlternatives[activeKey]) renderRouteExtraInfo(routeAlternatives[activeKey]);
        }
        // Wire datetime-local input
        document.addEventListener('change', (ev) => {
            if (ev.target && ev.target.id === 'departureInput') {
                const v = ev.target.value; // "YYYY-MM-DDTHH:MM"
                if (!v) { clearDeparture(); return; }
                departureTimeIso = new Date(v).toISOString();
                const lbl = document.getElementById('departureLabel');
                if (lbl) {
                    const d = new Date(v);
                    const hh = String(d.getHours()).padStart(2,'0');
                    const mm = String(d.getMinutes()).padStart(2,'0');
                    lbl.innerText = `${hh}:${mm}`;
                }
                // Refresh active alt ETA
                const activeRadio = document.querySelector('input[name="multiRouteChoice"]:checked');
                const fallbackKey = Object.keys(routeAlternatives).find(k => routeAlternatives[k] != null);
                const activeKey = activeRadio ? activeRadio.value : fallbackKey;
                if (activeKey && routeAlternatives[activeKey]) renderRouteExtraInfo(routeAlternatives[activeKey]);
            }
        });

        /* =========================================
           AVOIDANCES
           ========================================= */
        function toggleAvoidPanel() {
            const panel = document.getElementById('avoidPanel');
            const chev = document.getElementById('avoidChevron');
            if (!panel) return;
            const open = panel.style.display !== 'block';
            panel.style.display = open ? 'block' : 'none';
            if (chev) chev.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        }

        function getAvoidances() {
            const avoid = {};
            document.querySelectorAll('.avoid-check').forEach(cb => {
                if (cb.checked) avoid[cb.dataset.avoid] = true;
            });
            return avoid;
        }

        function updateAvoidCountBadge() {
            const count = Object.keys(getAvoidances()).length;
            const badge = document.getElementById('avoidCount');
            if (!badge) return;
            if (count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = count;
            } else {
                badge.style.display = 'none';
            }
        }

        // Wire avoid checkboxes
        document.addEventListener('change', (ev) => {
            if (ev.target.classList.contains('avoid-check')) {
                updateAvoidCountBadge();
            }
        });

        /* =========================================
           TURN-BY-TURN DIRECTIONS
           ========================================= */
        // Pick icon based on step Type + Direction (Left/Right) + Intensity (Sharp/Slight)
        function turnIconFor(step) {
            const type = step.Type || 'Continue';
            const dir = step.Direction;       // "Left" | "Right" | undefined
            const intensity = step.Intensity; // "Sharp" | "Slight" | "Normal" | undefined

            if (type === 'Turn') {
                if (intensity === 'Slight') {
                    return dir === 'Left' ? 'bi-arrow-up-left' : 'bi-arrow-up-right';
                }
                // Sharp or Normal — use the 90-degree icons
                return dir === 'Left' ? 'bi-arrow-90deg-left' : 'bi-arrow-90deg-right';
            }
            if (type === 'Keep') {
                return dir === 'Left' ? 'bi-signpost-split' : 'bi-signpost-split-fill';
            }
            if (type === 'Ramp' || type === 'Exit') {
                return dir === 'Left' ? 'bi-box-arrow-left' : 'bi-box-arrow-right';
            }
            if (type === 'Merge') {
                return dir === 'Left' ? 'bi-sign-merge-left' : 'bi-sign-merge-right';
            }
            const map = {
                Depart: 'bi-geo-alt-fill',
                Arrive: 'bi-flag-fill',
                Continue: 'bi-arrow-up',
                UTurn: 'bi-arrow-return-left',
                Roundabout: 'bi-arrow-clockwise',
                RoundaboutEnter: 'bi-arrow-clockwise',
                RoundaboutPass: 'bi-arrow-clockwise',
                RoundaboutExit: 'bi-box-arrow-right',
                Ferry: 'bi-water'
            };
            return map[type] || 'bi-arrow-right-circle';
        }

        // Build an instruction string from a step's structured fields using i18n templates.
        // Frontend builds this (not backend) so the text switches when user toggles language.
        /**
         * [CUSTOM] Build a localized turn-by-turn instruction string from
         * structured fields (Type, Direction, Intensity, CurrentRoad, NextRoad).
         * AWS v2 doesn't return ready text — we synthesize via i18n templates
         * so the directions match user's language (en/id) on the fly.
         */
        function buildStepInstruction(step) {
            const dir = step.Direction === 'Left' ? t('dir_left')
                       : step.Direction === 'Right' ? t('dir_right')
                       : '';
            const road = step.NextRoad || '';
            const cur  = step.CurrentRoad || '';

            switch (step.Type) {
                case 'Depart':
                    return cur ? t('instr_depart', { road: cur }) : t('instr_depart_alone');
                case 'Arrive':
                    return t('instr_arrive');
                case 'Turn': {
                    const tmpl = step.Intensity === 'Sharp'   ? 'instr_turn_sharp'
                               : step.Intensity === 'Slight'  ? 'instr_turn_slight'
                                                              : 'instr_turn';
                    return road ? t(tmpl, { dir, road }) : t('instr_turn_alone', { dir });
                }
                case 'Continue':
                    return (road || cur) ? t('instr_continue', { road: road || cur }) : t('instr_continue_alone');
                case 'Keep':
                    return road ? t('instr_keep', { dir, road }) : t('instr_keep_alone', { dir });
                case 'UTurn':
                    return road ? t('instr_uturn', { road }) : t('instr_uturn_alone');
                case 'Merge': {
                    const tmpl = dir ? 'instr_merge_dir' : 'instr_merge';
                    return road ? t(tmpl, { dir, road }) : (dir ? t('instr_merge_dir_alone', { dir }) : t('instr_merge_alone'));
                }
                case 'Exit':
                    return road ? t('instr_exit_road', { road }) : t('instr_exit');
                case 'Ramp': {
                    const tmpl = dir ? 'instr_ramp_dir' : 'instr_ramp';
                    return road ? t(tmpl, { dir, road }) : (dir ? t('instr_ramp_dir_alone', { dir }) : t('instr_ramp_alone'));
                }
                case 'RoundaboutEnter':
                    return road ? t('instr_roundabout_enter', { road }) : t('instr_roundabout_enter_alone');
                case 'RoundaboutPass':
                    return t('instr_roundabout_pass');
                case 'RoundaboutExit':
                    return road ? t('instr_roundabout_exit', { road }) : t('instr_roundabout_exit_alone');
                case 'Ferry':
                    return road ? t('instr_ferry', { road }) : t('instr_ferry_alone');
                default:
                    return step.Instruction || step.Type;
            }
        }

        function renderDirections(legs) {
            const container = document.getElementById('directionsList');
            if (!container) return;
            container.innerHTML = '';

            const allSteps = [];
            (legs || []).forEach(leg => {
                (leg.Steps || []).forEach(s => allSteps.push(s));
            });

            if (allSteps.length === 0) {
                container.innerHTML = `<div class="text-muted text-center py-3" style="font-size:0.78rem;">${escapeHtml(t('no_directions'))}</div>`;
                return;
            }

            allSteps.forEach((step, idx) => {
                const icon = turnIconFor(step);
                const dist = step.Distance > 0 ? `${(step.Distance).toFixed(2)} km` : '';
                const dur = step.DurationSeconds > 0 ? formatDuration(step.DurationSeconds) : '';
                const meta = [dist, dur].filter(Boolean).join(' • ');
                const div = document.createElement('div');
                div.className = 'direction-step';
                div.innerHTML = `
                    <div class="direction-icon"><i class="bi ${icon}"></i></div>
                    <div class="direction-body">
                        <div class="direction-text">${escapeHtml(buildStepInstruction(step))}</div>
                        ${meta ? `<div class="direction-meta">${meta}</div>` : ''}
                    </div>
                    <div class="direction-num">${idx + 1}</div>
                `;
                container.appendChild(div);
            });
        }

        function toggleDirections() {
            const wrap = document.getElementById('directionsWrap');
            const chev = document.getElementById('directionsChevron');
            if (!wrap) return;
            const open = wrap.style.display !== 'block';
            wrap.style.display = open ? 'block' : 'none';
            if (chev) chev.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        }

        // --- HELPER: MENGHITUNG JARAK ANTARA 2 KOORDINAT (Haversine) ---
        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            var R = 6371; // Radius bumi dalam km
            var dLat = deg2rad(lat2 - lat1);
            var dLon = deg2rad(lon2 - lon1);
            var a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var d = R * c; // Jarak dalam km
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // --- FUNGSI OPTIMASI URUTAN (Nearest Neighbor) ---
        function optimizeMarkersOrder(originalData) {
            if (originalData.length <= 2) return originalData; // Kalau cuma 2 titik, gak perlu diurutkan

            // 1. Ambil titik awal (Fixed, tidak boleh pindah)
            let sorted = [originalData[0]];

            // 2. Sisa titik yang belum dikunjungi
            let remaining = originalData.slice(1);

            // 3. Looping cari yang terdekat
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1]; // Titik terakhir yang sudah fix
                let nearestIndex = -1;
                let minDistance = Infinity;

                // Bandingkan jarak ke semua sisa titik
                remaining.forEach((point, index) => {
                    // Ingat: coords[1] = lat, coords[0] = lng
                    let dist = getDistanceFromLatLonInKm(
                        current.coords[1], current.coords[0],
                        point.coords[1], point.coords[0]
                    );

                    if (dist < minDistance) {
                        minDistance = dist;
                        nearestIndex = index;
                    }
                });

                // Pindahkan titik terdekat ke array sorted
                sorted.push(remaining[nearestIndex]);
                // Hapus dari remaining
                remaining.splice(nearestIndex, 1);
            }

            return sorted;
        }

        // --- HELPER: PANGGIL AWS MATRIX (REAL ROAD DISTANCE) ---
        /** [AWS] Single-origin route matrix (1 × N). Proxied to AWS /v2/route-matrix. */
        async function getRouteMatrix(departure, destinations, travelMode = 'Car') {
            const response = await proxyPost('/api/routes/matrix', {
                DeparturePositions: [departure],
                DestinationPositions: destinations,
                TravelMode: travelMode,
                DistanceUnit: "Kilometers"
            });

            if (!response.ok) throw new Error("Matrix API Error");
            return await response.json();
        }

        // --- LOGIKA PENGURUTAN REAL (PRECISE) ---
        // Strategy:
        //   1. Fetch full N×N duration matrix in ONE batched call (instead of N-1 sequential calls).
        //   2. Greedy nearest-neighbor as initial solution (anchored at index 0).
        //   3. 2-opt local search: keep swapping segment pairs while total duration decreases.
        //      First and last stops are kept fixed if user explicitly sets a destination, otherwise
        //      only the start is anchored (open tour TSP).
        /**
         * [HYBRID] "Precise" multi-stop ordering — uses real driving durations.
         * Strategy:
         *   1. One batched N×N matrix call (instead of N-1 sequential calls)
         *   2. Greedy nearest-neighbor as initial solution (anchored at index 0)
         *   3. 2-opt local search (swap pairs while total duration decreases)
         * Result: near-optimal route order, typically 95-100% of true TSP for ≤20 stops.
         */
        async function optimizeMarkersOrderReal(originalData, travelMode = 'Car') {
            const N = originalData.length;
            if (N <= 2) return originalData;

            const coords = originalData.map(m => m.coords);

            showToast(t('optimizing'), t('checking_roads', { n: 0 }), 'info');

            // Step 1: full N×N matrix in ONE call
            let matrix;
            try {
                const matrixData = await getFullRouteMatrix(coords, travelMode);
                const rm = matrixData && matrixData.RouteMatrix;
                if (!rm || rm.length !== N) throw new Error('Invalid matrix shape');
                matrix = rm.map(row =>
                    row.map(cell => (cell && cell.DurationSeconds !== undefined)
                        ? cell.DurationSeconds
                        : Infinity)
                );
            } catch (err) {
                console.error('Matrix optimization failed:', err);
                showToast(t('warning'), t('opt_failed_fallback'), 'warning');
                return originalData;
            }

            // Step 2: greedy nearest-neighbor (start anchored at index 0)
            const route = [0];
            const visited = new Set([0]);
            while (route.length < N) {
                const last = route[route.length - 1];
                let best = -1, bestDur = Infinity;
                for (let j = 0; j < N; j++) {
                    if (visited.has(j)) continue;
                    if (matrix[last][j] < bestDur) {
                        bestDur = matrix[last][j];
                        best = j;
                    }
                }
                if (best === -1) {
                    // No reachable next — append rest in input order
                    for (let j = 0; j < N; j++) if (!visited.has(j)) { route.push(j); visited.add(j); }
                    break;
                }
                route.push(best);
                visited.add(best);
            }

            // Helper: total duration of a route through matrix
            const tourDuration = (r) => {
                let total = 0;
                for (let i = 0; i < r.length - 1; i++) total += matrix[r[i]][r[i + 1]];
                return total;
            };

            // Step 3: 2-opt local search — anchor start (index 0), allow last to move
            let improved = true;
            let iter = 0;
            const MAX_ITER = 50;
            while (improved && iter < MAX_ITER) {
                improved = false;
                iter++;
                showToast(t('optimizing'), t('checking_roads', { n: iter }), 'info');
                for (let i = 1; i < route.length - 2; i++) {
                    for (let k = i + 1; k < route.length - 1; k++) {
                        const a = route[i - 1], b = route[i], c = route[k], d = route[k + 1];
                        const before = matrix[a][b] + matrix[c][d];
                        const after  = matrix[a][c] + matrix[b][d];
                        if (after + 1e-9 < before) {
                            // Reverse segment route[i..k]
                            const slice = route.slice(i, k + 1).reverse();
                            route.splice(i, k - i + 1, ...slice);
                            improved = true;
                        }
                    }
                }
            }

            // Compute improvement vs. original input order for telemetry/feedback
            const original = Array.from({ length: N }, (_, i) => i);
            const origDur = tourDuration(original);
            const newDur  = tourDuration(route);
            if (newDur < origDur && origDur !== Infinity) {
                const savedMin = Math.round((origDur - newDur) / 60);
                if (savedMin >= 1) {
                    showToast(t('success'), t('optimized_faster', { minutes: savedMin }), 'success');
                }
            }

            return route.map(idx => originalData[idx]);
        }

        // Fetch a single batched N×N route matrix
        /** [AWS] Full N×N route matrix in one call. Proxied to AWS /v2/route-matrix. */
        async function getFullRouteMatrix(coords, travelMode = 'Car') {
            const response = await proxyPost('/api/routes/matrix', {
                DeparturePositions: coords,
                DestinationPositions: coords,
                TravelMode: travelMode,
                DistanceUnit: 'Kilometers'
            });
            if (!response.ok) throw new Error('Matrix API Error');
            return await response.json();
        }


        /* =========================================
           6. VISUALIZATION & HELPERS
           ========================================= */
        function drawRouteOnMap(geoJsonFeatureCollection) {
            removeRouteLayer();

            map.addSource('routeSource', {
                'type': 'geojson',
                'data': geoJsonFeatureCollection
            });

            // Layer Outline (White)
            map.addLayer({
                'id': 'routeLayerOutline',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#ffffff',
                    'line-width': 6,
                    'line-opacity': 0.8
                }
            });

            // Main Layer (Colorful)
            map.addLayer({
                'id': 'routeLayer',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': ['get', 'color'],
                    'line-width': 4,
                    'line-opacity': 0.9
                }
            });

            const bounds = new maplibregl.LngLatBounds();
            geoJsonFeatureCollection.features.forEach(feature => {
                feature.geometry.coordinates.forEach(coord => bounds.extend(coord));
            });

            map.fitBounds(bounds, {
                padding: getMapFitPadding(),
                duration: 800
            });
        }

        // Asymmetric padding that keeps the route visible despite the floating header
        // and the left location panel (desktop) / top panel (mobile).
        function getMapFitPadding() {
            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            const panel = document.getElementById('locationsPanel');
            const panelVisible = !!panel
                && panel.style.display !== 'none'
                && getComputedStyle(panel).display !== 'none';

            if (isMobile) {
                // Mobile: header at top, panel (when visible) occupies up to 50vh from y≈76
                const headerH = 90;
                const panelOverlay = panelVisible
                    ? Math.min(window.innerHeight * 0.5 + 76, window.innerHeight * 0.6)
                    : headerH;
                return { top: panelOverlay, bottom: 60, left: 30, right: 30 };
            }

            // Desktop: header ≈ y=0..96, panel ≈ x=16..356 (340 + 16)
            return {
                top: 110,
                bottom: 60,
                left: panelVisible ? 380 : 60,
                right: 60,
            };
        }

        function removeRouteLayer() {
            // Clear highlight first
            clearSegmentHighlight();

            if (map.getLayer('routeLayer')) map.removeLayer('routeLayer');
            if (map.getLayer('routeLayerOutline')) map.removeLayer('routeLayerOutline');
            if (map.getSource('routeSource')) map.removeSource('routeSource');
        }

        function zoomToSegment(coordinates) {
            if (!coordinates || coordinates.length === 0) return;
            const bounds = new maplibregl.LngLatBounds();
            coordinates.forEach(coord => bounds.extend(coord));
            map.fitBounds(bounds, {
                padding: getMapFitPadding(),
                duration: 1000,
                maxZoom: 17
            });
        }

        function highlightSegment(seg) {
            clearSegmentHighlight();

            // 1. Dim all routes
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.25);
                map.setPaintProperty('routeLayer', 'line-width', 3);
            }
            if (map.getLayer('routeLayerOutline')) {
                map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.15);
            }

            // 2. Add highlight layers for selected segment
            map.addSource('highlightSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {
                        color: seg.color
                    },
                    geometry: {
                        type: 'LineString',
                        coordinates: seg.geometry
                    }
                }
            });

            // Glow effect
            map.addLayer({
                id: 'highlightGlow',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 12,
                    'line-opacity': 0.2
                }
            });

            // White outline
            map.addLayer({
                id: 'highlightOutline',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#ffffff',
                    'line-width': 7,
                    'line-opacity': 0.9
                }
            });

            // Main highlight line
            map.addLayer({
                id: 'highlightLine',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 5,
                    'line-opacity': 1
                }
            });

            // 3. Add POI markers at start and end
            const startCoord = seg.geometry[0];
            const endCoord = seg.geometry[seg.geometry.length - 1];

            const startMarker = new maplibregl.Marker({
                    element: createPOIElement('A', seg.color)
                })
                .setLngLat(startCoord)
                .setPopup(new maplibregl.Popup({
                    offset: 20,
                    closeButton: false
                }).setHTML(
                    `<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">Start</strong><br>${seg.from}</div>`
                ))
                .addTo(map);

            const endMarker = new maplibregl.Marker({
                    element: createPOIElement('B', seg.color)
                })
                .setLngLat(endCoord)
                .setPopup(new maplibregl.Popup({
                    offset: 20,
                    closeButton: false
                }).setHTML(
                    `<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">End</strong><br>${seg.to}</div>`
                ))
                .addTo(map);

            startMarker.togglePopup();
            endMarker.togglePopup();
            highlightMarkers.push(startMarker, endMarker);

            // 4. Zoom to segment
            zoomToSegment(seg.geometry);
        }

        function clearSegmentHighlight() {
            // Remove highlight layers
            ['highlightLine', 'highlightOutline', 'highlightGlow'].forEach(id => {
                if (map.getLayer(id)) map.removeLayer(id);
            });
            if (map.getSource('highlightSource')) map.removeSource('highlightSource');

            // Restore main route opacity
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.9);
                map.setPaintProperty('routeLayer', 'line-width', 4);
            }
            if (map.getLayer('routeLayerOutline')) {
                map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.8);
            }

            // Remove POI markers
            highlightMarkers.forEach(m => m.remove());
            highlightMarkers = [];
        }

        function createPOIElement(label, color) {
            const el = document.createElement('div');
            el.className = 'poi-marker';
            el.style.backgroundColor = color;
            el.textContent = label;
            return el;
        }

        function formatDuration(seconds) {
            const totalMinutes = Math.round(seconds / 60);
            const minLabel = t('unit_min') || 'min';
            const hrLabel = t('unit_hr') || 'hr';
            if (totalMinutes >= 60) {
                const hrs = Math.floor(totalMinutes / 60);
                const mins = totalMinutes % 60;
                return mins > 0 ? `${hrs} ${hrLabel} ${mins} ${minLabel}` : `${hrs} ${hrLabel}`;
            }
            // Show "<1 min" for sub-minute durations rather than "0 min"
            if (totalMinutes === 0) return `<1 ${minLabel}`;
            return `${totalMinutes} ${minLabel}`;
        }


        /* =========================================
           7. UI RENDERING (LISTS)
           ========================================= */
        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');
            const emptyState = document.getElementById('emptyState');

            panel.style.display = 'flex';
            countBadge.innerText = markersData.length;

            if (markersData.length === 0) {
                emptyState.style.display = 'block';
                container.innerHTML = '';
            } else {
                emptyState.style.display = 'none';
                container.innerHTML = '';
                markersData.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'location-item';
                    if (item.id === selectedMarkerId) div.classList.add('active');

                    div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;
                    const lat = item.coords[1].toFixed(5);
                    const lng = item.coords[0].toFixed(5);

                    const safeName = escapeHtml(item.name || '');
                    const safeAddr = escapeHtml(item.address || '');
                    const addressHtml = item.address
                        ? `<span class="loc-address text-truncate" title="${safeAddr}">${safeAddr}</span>`
                        : '';

                    div.innerHTML = `
                    <div class="loc-info" onclick="showLocationDetail(${item.id})">
                        <span class="loc-name text-truncate" title="${safeName}">${safeName}</span>
                        ${addressHtml}
                        <span class="loc-coord"><i class="bi bi-crosshair"></i> ${lat}, ${lng}</span>
                    </div>
                    <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                    container.appendChild(div);
                });
            }
        }

        function renderSegmentList(details) {
            const container = document.getElementById('segmentListContainer');
            container.innerHTML = '';
            container.style.display = 'block';

            details.forEach((seg, index) => {
                const dist = seg.distance.toFixed(1) + ' km';
                const dur = formatDuration(seg.duration);

                const item = document.createElement('div');
                item.className = 'segment-card';
                item.style.cursor = 'pointer';

                item.onclick = () => {
                    const isActive = item.classList.contains('active-card');
                    document.querySelectorAll('.segment-card').forEach(el => el.classList.remove('active-card'));

                    if (isActive) {
                        clearSegmentHighlight();
                    } else {
                        item.classList.add('active-card');
                        highlightSegment(seg);
                    }
                };

                item.innerHTML = `
                <div class="segment-color-bar" style="background-color: ${seg.color};"></div>
                <div class="d-flex flex-column">
                    <div class="segment-title">
                        <span class="text-truncate" style="max-width: 240px;">
                            <span class="badge rounded-pill text-bg-light border me-1">${index + 1}</span>
                            ${seg.to}
                        </span>
                    </div>
                    <div class="segment-details">
                        <span><i class="bi bi-rulers segment-icon"></i> ${dist}</span>
                        <span class="border-start mx-2"></span>
                        <span><i class="bi bi-clock segment-icon"></i> ${dur}</span>
                    </div>
                    <div style="font-size: 0.7rem; color: #999; margin-top: 2px;">
                        From: ${seg.from}
                    </div>
                </div>
            `;
                container.appendChild(item);
            });
        }


        /* =========================================
           8. SEARCH FUNCTIONALITY
           ========================================= */
        const input = document.getElementById('searchInput');
        const list = document.getElementById('suggestionsList');

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        let _suggestSeq = 0;
        // When user clicks "Search this area" we switch to bounding-box filter for the NEXT search.
        let searchUseBbox = false;
        function buildSearchSpatial() {
            if (searchUseBbox) {
                const b = map.getBounds();
                return { Filter: { BoundingBox: [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()] } };
            }
            const c = map.getCenter();
            return { BiasPosition: [c.lng, c.lat] };
        }

        /* =========================================
           SEARCH HISTORY (localStorage)
           ========================================= */
        const RECENT_SEARCH_KEY = 'home_recent_searches';
        const MAX_RECENT = 5;

        function getRecentSearches() {
            try {
                const raw = localStorage.getItem(RECENT_SEARCH_KEY);
                return raw ? JSON.parse(raw) : [];
            } catch (_) { return []; }
        }
        function addRecentSearch(query) {
            if (!query || !query.trim()) return;
            let recent = getRecentSearches().filter(q => q.toLowerCase() !== query.toLowerCase());
            recent.unshift(query);
            recent = recent.slice(0, MAX_RECENT);
            try { localStorage.setItem(RECENT_SEARCH_KEY, JSON.stringify(recent)); } catch (_) {}
        }
        function removeRecentSearch(query) {
            const recent = getRecentSearches().filter(q => q !== query);
            try { localStorage.setItem(RECENT_SEARCH_KEY, JSON.stringify(recent)); } catch (_) {}
            renderRecentSearches();
        }
        function clearRecentSearches() {
            try { localStorage.removeItem(RECENT_SEARCH_KEY); } catch (_) {}
            renderRecentSearches();
        }
        function renderRecentSearches() {
            const panel = document.getElementById('recentSearchesPanel');
            const listEl = document.getElementById('recentSearchesList');
            if (!panel || !listEl) return;
            const recent = getRecentSearches();
            if (recent.length === 0) {
                panel.style.display = 'none';
                return;
            }
            listEl.innerHTML = '';
            recent.forEach(q => {
                const item = document.createElement('div');
                item.className = 'recent-item';
                item.innerHTML = `
                    <i class="bi bi-clock"></i>
                    <span style="flex:1;">${escapeHtml(q)}</span>
                    <span class="recent-remove" title="Remove" onclick="event.stopPropagation();removeRecentSearch(${JSON.stringify(q).replace(/"/g, '&quot;')})"><i class="bi bi-x"></i></span>
                `;
                item.onclick = () => {
                    input.value = q;
                    input.dispatchEvent(new Event('input'));
                };
                listEl.appendChild(item);
            });
            panel.style.display = 'block';
        }

        function maybeShowRecentSearches() {
            renderRecentSearches();
        }

        /* =========================================
           CATEGORY QUICK FILTERS
           ========================================= */
        const CATEGORY_FILTERS = [
            { key: 'restaurant', icon: 'bi-cup-hot-fill',     bg:'#fff7ed', color:'#ea580c', label_key: 'cat_restaurant' },
            { key: 'cafe',       icon: 'bi-cup',              bg:'#fef3c7', color:'#92400e', label_key: 'cat_cafe' },
            { key: 'atm',        icon: 'bi-cash-coin',         bg:'#ecfdf5', color:'#059669', label_key: 'cat_atm' },
            { key: 'hotel',      icon: 'bi-building',          bg:'#eef2ff', color:'#4338ca', label_key: 'cat_hotel' },
            { key: 'gas',        icon: 'bi-fuel-pump-fill',    bg:'#fef2f2', color:'#b91c1c', label_key: 'cat_gas' },
            { key: 'pharmacy',   icon: 'bi-prescription2',     bg:'#f3e8ff', color:'#7c3aed', label_key: 'cat_pharmacy' },
        ];

        function renderCategoryChips() {
            const bar = document.getElementById('categoryChipsBar');
            if (!bar) return;
            bar.innerHTML = '';
            CATEGORY_FILTERS.forEach(c => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'category-chip';
                chip.innerHTML = `<i class="bi ${c.icon}" style="color:${c.color};"></i> <span data-i18n="${c.label_key}">${c.key}</span>`;
                chip.onclick = () => {
                    const label = t(c.label_key) || c.key;
                    input.value = label;
                    handleManualSearch();
                };
                bar.appendChild(chip);
            });
            // Re-translate the newly inserted nodes
            applyTranslations();
        }

        /* =========================================
           SEARCH DROPDOWN VISIBILITY
           ========================================= */
        // Re-evaluate whether the dropdown container should be visible.
        // Visible only when at least one of its children has actual content.
        function refreshDropdownVisibility() {
            const dd = document.getElementById('searchDropdown');
            if (!dd) return;
            const chipsBar = document.getElementById('categoryChipsBar');
            const recents = document.getElementById('recentSearchesPanel');
            const loading = document.getElementById('searchLoading');
            const empty = document.getElementById('searchEmpty');
            const chipsShown = chipsBar && chipsBar.style.display !== 'none' && chipsBar.children.length > 0;
            const recentsShown = recents && recents.style.display === 'block';
            const loadingShown = loading && loading.style.display === 'flex';
            const emptyShown = empty && empty.style.display === 'block';
            const suggShown = list && list.classList.contains('show') && list.children.length > 0;
            dd.style.display = (chipsShown || recentsShown || loadingShown || emptyShown || suggShown) ? 'block' : 'none';
        }

        function setSearchLoading(show) {
            const el = document.getElementById('searchLoading');
            if (el) el.style.display = show ? 'flex' : 'none';
        }
        function setSearchEmpty(show) {
            const el = document.getElementById('searchEmpty');
            if (el) el.style.display = show ? 'block' : 'none';
        }

        function showSearchDropdown() {
            if (!input.value || input.value.length < 3) {
                renderCategoryChips();
                document.getElementById('categoryChipsBar').style.display = 'flex';
                renderRecentSearches();
                list.classList.remove('show');
            } else {
                document.getElementById('categoryChipsBar').style.display = 'none';
                document.getElementById('recentSearchesPanel').style.display = 'none';
            }
            refreshDropdownVisibility();
        }
        function hideSearchDropdown() {
            const dd = document.getElementById('searchDropdown');
            if (!dd) return;
            dd.style.display = 'none';
        }

        input.addEventListener('focus', showSearchDropdown);
        input.addEventListener('blur', () => {
            // Delay so click events on dropdown items can register
            setTimeout(hideSearchDropdown, 200);
        });

        /* =========================================
           "SEARCH THIS AREA" BUTTON
           ========================================= */
        let lastSearchCenter = null; // {lng, lat, zoom} at time of last search
        let lastSearchHadResults = false;

        function maybeShowSearchThisArea() {
            const btn = document.getElementById('searchThisAreaBtn');
            if (!btn || !lastSearchHadResults || !lastSearchCenter) return;
            const c = map.getCenter();
            const dx = c.lng - lastSearchCenter.lng;
            const dy = c.lat - lastSearchCenter.lat;
            const movedFar = Math.hypot(dx, dy) > 0.005; // ~500m rough threshold
            btn.style.display = movedFar ? 'flex' : 'none';
        }
        function searchThisArea() {
            const q = input.value || lastQueryString;
            if (!q) return;
            searchUseBbox = true;
            input.value = q;
            handleManualSearch();
        }

        let lastQueryString = '';

        /**
         * Parse a string as coordinates. Supports common formats:
         *   - "-7.94259, 110.25978"   (decimal, comma)
         *   - "-7.94259 110.25978"    (decimal, space)
         *   - "7.94259S, 110.25978E"  (decimal + direction)
         *   - "7°54'15.4\"S 110°17'43.9\"E"  (DMS — Google Maps style)
         * Returns { lat, lng } or null if not parseable.
         */
        function parseCoords(text) {
            if (!text) return null;
            const s = text.trim();

            // 1. Decimal: e.g. "-7.94259, 110.25978" or "-7.94259 110.25978"
            const dec = s.match(/^(-?\d{1,2}(?:\.\d+)?)[\s,]+(-?\d{1,3}(?:\.\d+)?)$/);
            if (dec) {
                const lat = parseFloat(dec[1]);
                const lng = parseFloat(dec[2]);
                if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) return { lat, lng };
            }

            // 2. Decimal with direction: "7.94259S, 110.25978E"
            const dir = s.match(/^(\d{1,2}(?:\.\d+)?)\s*([NS])[\s,]+(\d{1,3}(?:\.\d+)?)\s*([EW])$/i);
            if (dir) {
                let lat = parseFloat(dir[1]);
                let lng = parseFloat(dir[3]);
                if (dir[2].toUpperCase() === 'S') lat = -lat;
                if (dir[4].toUpperCase() === 'W') lng = -lng;
                if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) return { lat, lng };
            }

            // 3. DMS: 7°54'15.4"S 110°17'43.9"E
            const dms = s.match(/^(\d{1,2})°\s*(\d{1,2})'?\s*(\d{1,2}(?:\.\d+)?)?"?\s*([NS])[\s,]+(\d{1,3})°\s*(\d{1,2})'?\s*(\d{1,2}(?:\.\d+)?)?"?\s*([EW])$/i);
            if (dms) {
                let lat = parseInt(dms[1]) + parseInt(dms[2]) / 60 + (parseFloat(dms[3]) || 0) / 3600;
                let lng = parseInt(dms[5]) + parseInt(dms[6]) / 60 + (parseFloat(dms[7]) || 0) / 3600;
                if (dms[4].toUpperCase() === 'S') lat = -lat;
                if (dms[8].toUpperCase() === 'W') lng = -lng;
                if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) return { lat, lng };
            }

            return null;
        }

        /** Render a single "Pin at coordinates" suggestion in the dropdown */
        function renderCoordsSuggestion(parsed, rawQuery) {
            list.innerHTML = '';
            const li = document.createElement('li');
            li.className = 'suggestion-item';
            li.innerHTML = `
                <div class="suggestion-cat-icon" style="background:#eef2ff;color:#4338ca;">
                    <i class="bi bi-pin-map-fill"></i>
                </div>
                <div class="suggestion-text" style="flex:1;min-width:0;">
                    <div class="suggestion-title">${escapeHtml(t('pin_at_coords') || 'Pin at coordinates')}</div>
                    <div class="suggestion-address" style="font-family:ui-monospace,monospace;">${parsed.lat.toFixed(6)}, ${parsed.lng.toFixed(6)}</div>
                </div>
            `;
            li.onclick = async () => {
                hideSearchDropdown();
                input.value = '';
                const coords = [parsed.lng, parsed.lat];
                addLocation(coords, t('loading'));
                const currentId = selectedMarkerId;
                // Try to reverse-geocode for a friendly label
                try {
                    const place = await getPlaceNameByCoords(coords);
                    const item = markersData.find(m => m.id === currentId);
                    if (item && place && place.title) {
                        item.name = place.title;
                        item.address = place.address;
                        item.marker.setPopup(new maplibregl.Popup({offset:25}).setHTML(buildPopupHtml(place.title, place.address)));
                        renderLocationList();
                    }
                } catch (_) {}
            };
            list.appendChild(li);
            list.classList.add('show');
        }

        input.addEventListener('input', debounce(async (e) => {
            const query = e.target.value;
            const chipsBar = document.getElementById('categoryChipsBar');

            // Coordinate detection — short-circuit before triggering AWS Suggest
            const coordsMatch = parseCoords(query);
            if (coordsMatch) {
                if (chipsBar) chipsBar.style.display = 'none';
                document.getElementById('recentSearchesPanel').style.display = 'none';
                setSearchLoading(false);
                setSearchEmpty(false);
                renderCoordsSuggestion(coordsMatch, query);
                refreshDropdownVisibility();
                return;
            }

            if (query.length < 3) {
                list.classList.remove('show');
                list.innerHTML = '';
                setSearchLoading(false);
                setSearchEmpty(false);
                if (chipsBar) chipsBar.style.display = 'flex';
                maybeShowRecentSearches();
                refreshDropdownVisibility();
                return;
            }
            // Typing — hide chips/recents/empty, show loading spinner
            if (chipsBar) chipsBar.style.display = 'none';
            document.getElementById('recentSearchesPanel').style.display = 'none';
            setSearchEmpty(false);
            setSearchLoading(true);
            refreshDropdownVisibility();

            const mySeq = ++_suggestSeq;
            try {
                const res = await proxyPost('/api/places/suggestions', {
                    QueryText: query,
                    MaxResults: 10,
                    Language: currentLang,
                    AdditionalFeatures: ['Core'], // include Position so we can compute distance
                    ...buildSearchSpatial()
                });

                if (mySeq !== _suggestSeq || input.value !== query) return;

                if (!res.ok) {
                    list.classList.remove('show');
                    list.innerHTML = '';
                    setSearchLoading(false);
                    setSearchEmpty(false);
                    refreshDropdownVisibility();
                    showToast(t('error'), t('failed'), 'error');
                    return;
                }
                let data;
                try { data = await res.json(); } catch (_) { data = null; }
                if (mySeq !== _suggestSeq) return;
                setSearchLoading(false);
                const items = data && data.ResultItems;
                if (items && items.length > 0) {
                    setSearchEmpty(false);
                    renderSuggestions(items, query);
                } else {
                    list.classList.remove('show');
                    list.innerHTML = '';
                    setSearchEmpty(true);
                }
                refreshDropdownVisibility();
            } catch (err) {
                console.error(err);
                if (mySeq === _suggestSeq) {
                    list.classList.remove('show');
                    list.innerHTML = '';
                    setSearchLoading(false);
                    setSearchEmpty(false);
                    refreshDropdownVisibility();
                    showToast(t('error'), t('failed'), 'error');
                }
            }
        }, 300));

        // Pick an icon + colors for a category id/name
        const CATEGORY_ICON_MAP = [
            { match: /restaurant|food|dining|cuisine/i,   icon: 'bi-cup-hot-fill',     bg:'#fff7ed', color:'#ea580c' },
            { match: /cafe|coffee/i,                       icon: 'bi-cup',              bg:'#fef3c7', color:'#92400e' },
            { match: /atm|bank|finance/i,                  icon: 'bi-cash-coin',        bg:'#ecfdf5', color:'#059669' },
            { match: /hotel|lodging|accommodation/i,       icon: 'bi-building',         bg:'#eef2ff', color:'#4338ca' },
            { match: /gas|fuel|petrol|spbu/i,               icon: 'bi-fuel-pump-fill',   bg:'#fef2f2', color:'#b91c1c' },
            { match: /pharmacy|drug|apotek/i,              icon: 'bi-prescription2',    bg:'#f3e8ff', color:'#7c3aed' },
            { match: /hospital|clinic|medical|rumah sakit/i,icon: 'bi-hospital',        bg:'#fee2e2', color:'#dc2626' },
            { match: /school|education|sekolah|university/i,icon: 'bi-mortarboard-fill',bg:'#dbeafe', color:'#1d4ed8' },
            { match: /shopping|mall|store|toko/i,           icon: 'bi-bag-fill',        bg:'#fce7f3', color:'#be185d' },
            { match: /park|recreation/i,                    icon: 'bi-tree-fill',       bg:'#dcfce7', color:'#15803d' },
            { match: /airport|transport|station/i,          icon: 'bi-airplane',        bg:'#e0e7ff', color:'#4f46e5' },
            { match: /mosque|masjid/i,                      icon: 'bi-moon-stars',      bg:'#fef3c7', color:'#a16207' },
            { match: /church|gereja/i,                      icon: 'bi-cross',           bg:'#e0e7ff', color:'#3730a3' },
            { match: /office|business|kantor/i,             icon: 'bi-briefcase-fill',  bg:'#f1f5f9', color:'#475569' },
        ];
        function pickCategoryIcon(item) {
            const cats = (item.Place && item.Place.Categories) || [];
            const text = cats.map(c => c.Name || c.LocalizedName || c.Id || '').join(' ') + ' ' + (item.Title || '');
            for (const r of CATEGORY_ICON_MAP) {
                if (r.match.test(text)) return r;
            }
            return { icon: 'bi-geo-alt', bg: '#f3f4f6', color: '#6b7280' };
        }

        // Bold the matched query inside a text — case-insensitive
        function highlightMatch(text, query) {
            if (!text || !query) return escapeHtml(text);
            const safeText = escapeHtml(text);
            const tokens = query.trim().split(/\s+/).filter(t => t.length >= 2);
            if (tokens.length === 0) return safeText;
            // Escape regex special chars
            const escaped = tokens.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
            const re = new RegExp('(' + escaped.join('|') + ')', 'gi');
            return safeText.replace(re, '<span class="suggestion-match-highlight">$1</span>');
        }

        // Smart reference point for "distance" — prefers nearest existing marker, else map center
        function distanceForSuggestion(suggestionLngLat) {
            if (!suggestionLngLat || suggestionLngLat.length !== 2) return null;
            const [lng, lat] = suggestionLngLat;

            if (markersData.length > 0) {
                let best = null;
                markersData.forEach(m => {
                    const d = getDistanceFromLatLonInKm(lat, lng, m.coords[1], m.coords[0]);
                    if (best == null || d < best.km) {
                        best = { km: d, fromName: m.name || '?', fromMarker: true };
                    }
                });
                return best;
            }
            const c = map.getCenter();
            return {
                km: getDistanceFromLatLonInKm(lat, lng, c.lat, c.lng),
                fromName: '',
                fromMarker: false
            };
        }

        /**
         * [CUSTOM] Render AWS /v2/suggest results as live dropdown items.
         * Each item shows:
         *   - Category-aware colored icon (auto-detect from item.Place.Categories)
         *   - Title with matched query bolded
         *   - Address line
         *   - Distance — from nearest existing marker (if any) or map center
         *     (requires AdditionalFeatures: ['Core'] in the request — see input handler)
         */
        function renderSuggestions(results, query) {
            list.innerHTML = '';
            if (!results || results.length === 0) {
                list.classList.remove('show');
                return;
            }
            const useMiles = currentDistanceUnit === 'Miles';
            const distUnit = useMiles ? (t('unit_mi') || 'mi') : 'km';

            results.forEach(item => {
                const place = item.Place || {};
                const placeId = place.PlaceId;
                if (!placeId) return;
                const title = item.Title || '';
                const addressLabel = (place.Address && place.Address.Label) || '';
                const distInfo = distanceForSuggestion(place.Position);

                let distStr = '';
                if (distInfo) {
                    const distVal = useMiles ? distInfo.km * 0.621371 : distInfo.km;
                    const formatted = distVal < 10 ? distVal.toFixed(1) : distVal.toFixed(0);
                    const fromLabel = distInfo.fromMarker
                        ? `${escapeHtml(t('from'))} ${escapeHtml(distInfo.fromName)}`
                        : escapeHtml(t('from_center'));
                    distStr = `<span class="suggestion-distance"><i class="bi bi-rulers"></i> ${formatted} ${distUnit}</span>
                               <small style="color:#9ca3af;font-weight:400;margin-left:4px;">${fromLabel}</small>`;
                }
                const cat = pickCategoryIcon(item);

                const li = document.createElement('li');
                li.className = 'suggestion-item';
                li.innerHTML = `
                    <div class="suggestion-cat-icon" style="background:${cat.bg};color:${cat.color};">
                        <i class="bi ${cat.icon}"></i>
                    </div>
                    <div class="suggestion-text" style="flex:1;min-width:0;">
                        <div class="suggestion-title">${highlightMatch(title, query)}</div>
                        ${addressLabel ? `<div class="suggestion-address">${highlightMatch(addressLabel, query)}</div>` : ''}
                        ${distStr ? `<div class="suggestion-meta">${distStr}</div>` : ''}
                    </div>
                `;
                li.onclick = () => selectPlace(placeId, title);
                list.appendChild(li);
            });
            list.classList.add('show');
        }

        async function selectPlace(placeId, placeName) {
            list.classList.remove('show');
            input.value = '';

            try {
                const res = await proxyGet(`/api/places/${placeId}`);
                const data = await res.json();
                const title = data.Title || (data.Address && data.Address.Label) || placeName;
                const address = (data.Title && data.Address && data.Address.Label && data.Title !== data.Address.Label)
                    ? data.Address.Label
                    : null;
                addLocation(data.Position, title, address, placeId);
                showToast(t('added'), title, 'success');
            } catch (err) {
                showToast(t('failed'), t('cannot_fetch'), 'error');
            }
        }

        // Markers for "all search results" preview — separate from markersData (not for routing)
        let searchResultMarkers = [];
        function clearSearchResultMarkers() {
            searchResultMarkers.forEach(m => { try { m.remove(); } catch (_) {} });
            searchResultMarkers = [];
        }

        /**
         * [HYBRID] "Enter / click Search" full search.
         * Calls AWS /v2/search-text via proxy. Then:
         *   - Adds query to recent-searches localStorage
         *   - Drops up to 10 preview markers on map (red = top, gray = rest)
         *   - fitBounds map to all results
         *   - Click a preview marker → promotes it to a routing marker
         */
        async function handleManualSearch() {
            const query = input.value;
            if (!query) return showToast(t('empty_search'), t('enter_keyword'), 'warning');

            // Coordinate shortcut — bypass AWS search-text, drop pin directly
            const coordsMatch = parseCoords(query);
            if (coordsMatch) {
                list.classList.remove('show');
                hideSearchDropdown();
                input.value = '';
                const coords = [coordsMatch.lng, coordsMatch.lat];
                addLocation(coords, t('loading'));
                const currentId = selectedMarkerId;
                try {
                    const place = await getPlaceNameByCoords(coords);
                    const item = markersData.find(m => m.id === currentId);
                    if (item && place && place.title) {
                        item.name = place.title;
                        item.address = place.address;
                        item.marker.setPopup(new maplibregl.Popup({offset:25}).setHTML(buildPopupHtml(place.title, place.address)));
                        renderLocationList();
                    }
                } catch (_) {}
                showToast(t('found'), `${coordsMatch.lat.toFixed(5)}, ${coordsMatch.lng.toFixed(5)}`, 'success');
                return;
            }

            list.classList.remove('show');
            hideSearchDropdown();
            lastQueryString = query;

            try {
                const res = await proxyPost('/api/places/search', {
                    QueryText: query,
                    MaxResults: 10,
                    Language: currentLang,
                    ...buildSearchSpatial()
                });

                // Reset to bias mode after one bbox-search call
                searchUseBbox = false;

                if (!res.ok) {
                    console.error('Search failed', res.status);
                    showToast(t('error'), t('api_search_failed'), 'error');
                    return;
                }

                let data;
                try { data = await res.json(); } catch (_) { data = null; }

                if (data && data.ResultItems && data.ResultItems.length > 0) {
                    addRecentSearch(query); // save to history

                    // Track for "Search this area" trigger
                    const c = map.getCenter();
                    lastSearchCenter = { lng: c.lng, lat: c.lat };
                    lastSearchHadResults = true;
                    document.getElementById('searchThisAreaBtn').style.display = 'none';

                    // Render ALL results as preview markers (gray) + first as red emphasized
                    clearSearchResultMarkers();
                    const bounds = new maplibregl.LngLatBounds();
                    data.ResultItems.forEach((item, idx) => {
                        if (!item.Position || item.Position.length !== 2) return;
                        const isFirst = idx === 0;
                        const m = new maplibregl.Marker({
                                color: isFirst ? '#dc2626' : '#9ca3af',
                                scale: isFirst ? 1 : 0.75
                            })
                            .setLngLat(item.Position)
                            .setPopup(new maplibregl.Popup({ offset: 25 })
                                .setHTML(buildPopupHtml(item.Title || query, item.Address && item.Address.Label)))
                            .addTo(map);
                        // Click a preview marker → add it as a routing marker
                        m.getElement().addEventListener('click', (ev) => {
                            ev.stopPropagation();
                            const title = item.Title || (item.Address && item.Address.Label) || query;
                            const address = (item.Title && item.Address && item.Address.Label && item.Title !== item.Address.Label)
                                ? item.Address.Label
                                : null;
                            addLocation(item.Position, title, address, item.PlaceId || null);
                            clearSearchResultMarkers();
                            showToast(t('added'), title, 'success');
                        });
                        searchResultMarkers.push(m);
                        bounds.extend(item.Position);
                    });
                    if (searchResultMarkers.length > 0) {
                        map.fitBounds(bounds, { padding: getMapFitPadding(), duration: 800, maxZoom: 16 });
                    }

                    showToast(t('found'), `${data.ResultItems.length} ${t('results_found') || 'results'}`, 'success');
                } else {
                    showToast(t('not_found'), t('try_another'), 'warning');
                }
            } catch (err) {
                console.error(err);
                showToast(t('error'), t('api_search_failed'), 'error');
            }
        }

        /* =========================================
           9. INITIALIZATION & EVENTS
           ========================================= */

        function setupEventListeners() {
            // 1. Close suggestion list when clicking outside
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.remove('show');
                }
            });

            // 2. Handle Enter key on Search Input
            input.addEventListener("keypress", (event) => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    handleManualSearch();
                }
            });
        }

        // --- MORE MENU ---
        function toggleMoreMenu() {
            const dropdown = document.getElementById('moreMenuDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.more-menu-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('moreMenuDropdown').classList.remove('show');
            }
        });

        // --- WHAT'S NEW MODAL ---
        // Bump this version when releasing new updates — modal will auto-show again
        const WHATS_NEW_VERSION = 'v2.1';

        function showWhatsNewModal() {
            const modal = new bootstrap.Modal(document.getElementById('whatsNewModal'));
            modal.show();
            // Save preference when modal closes
            document.getElementById('whatsNewModal').addEventListener('hidden.bs.modal', () => {
                if (document.getElementById('dontShowAgain').checked) {
                    localStorage.setItem('whatsNewSeen', WHATS_NEW_VERSION);
                }
            }, { once: true });
        }

        function maybeShowWhatsNew() {
            const seen = localStorage.getItem('whatsNewSeen');
            if (seen !== WHATS_NEW_VERSION) {
                // Delay sedikit supaya tidak nutup map yang belum siap
                setTimeout(showWhatsNewModal, 800);
            }
        }

        // --- MAIN BOOTSTRAP ---
        document.addEventListener('DOMContentLoaded', () => {
            applyTranslations();
            syncMapStyleButtons();
            initMap();
            setupEventListeners();
            scheduleAutoMapStyle();
            updateRouteButtonsByCount(); // Initial state: 0 markers → both disabled
            maybeShowWhatsNew();
        });
    </script>
</body>

</html>
