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
                <input type="text" class="search-input" data-i18n-placeholder="search_placeholder" placeholder="Search a place..." id="searchInput">
            </div>
            <ul class="suggestions-list" id="suggestionsList"></ul>
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

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-action-primary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateRoute()" data-i18n-title="route_a_b" title="Calculate Route A to B">
                    <i class="bi bi-sign-turn-right-fill me-2"></i> A&rarr;B
                </button>
                <button class="btn btn-action-secondary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateMultiRoute()" data-i18n-title="route_multi" title="Calculate Multi-Stop Route">
                    <i class="bi bi-diagram-3-fill me-2"></i> Multi
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
                                    <td class="py-2 text-center">Low (Flight)</td>
                                    <td class="py-2 text-center">High (Traffic)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);" data-i18n="best_for">Best For</td>
                                    <td class="py-2 text-center" data-i18n="estimates">Estimates</td>
                                    <td class="py-2 text-center" data-i18n="delivery">Delivery</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-2 pt-2 border-top d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-warning mt-1"></i>
                            <p class="small mb-0" style="font-size: 0.73rem; color: var(--text-secondary);">
                                <strong>Tip:</strong> Use "Straight Line" to list points quickly, then "Real Road" to finalize.
                            </p>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;" data-i18n="travel_modes">2. Travel Modes</p>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-car-front-fill" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark" data-i18n="car">Car</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);" data-i18n="standard_routes">Standard Routes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-scooter" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark" data-i18n="motorcycle">Motorcycle</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);" data-i18n="faster_eta">Faster ETA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">3. Calculation Actions</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-sign-turn-right-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Single Route (A&rarr;B)</h6>
                            <p style="font-size: 0.78rem;">Direct path from the first to the second location only.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;" data-i18n="multi_stop_info">Multi-Stop (Optimized)</h6>
                            <p style="font-size: 0.78rem;" data-i18n-html="multi_stop_desc">Automatically <b>reorders</b> all stops to find the most efficient path.</p>
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
                            <span class="badge bg-light text-success ms-1" style="font-size:0.7rem;">v2.0</span>
                        </div>
                        <small style="opacity:0.9;" data-i18n="whats_new_subtitle">Latest GrabMaps app update</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <!-- Highlight v2 -->
                    <div class="p-3 rounded-3 mb-3" style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);border:1px solid #bbf7d0;">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-rocket-takeoff-fill" style="color:#16a34a;font-size:1.2rem;"></i>
                            <div>
                                <div style="font-weight:700;color:#166534;" data-i18n="v2_migration_title">Migration to Places API v2</div>
                                <small style="color:#15803d;" data-i18n="v2_migration_desc">Powered by AWS Location Service Places v2 + GrabMaps</small>
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
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_search_title">Richer Search Results</div>
                                <small style="color:#6b7280;" data-i18n="feat_search_desc">POI name and address are now separated — like Grab/Google Maps. Example:</small>
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
                                <i class="bi bi-lightning-charge-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_autocomplete_title">Faster Autocomplete</div>
                                <small style="color:#6b7280;" data-i18n="feat_autocomplete_desc">Suggestion dropdown shows POI name (bold) + full address below. Biased to your map view.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="background:#fef3c7;color:#d97706;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-pin-map-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_reverse_title">Reverse Geocode v2</div>
                                <small style="color:#6b7280;" data-i18n="feat_reverse_desc">Click anywhere on the map — get full POI info with categories, not just plain address.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="background:#fce7f3;color:#db2777;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi bi-database-fill-check"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight:600;color:#1f2937;" data-i18n="feat_resourceless_title">Resource-less API</div>
                                <small style="color:#6b7280;" data-i18n="feat_resourceless_desc">No need to setup Place Index — AWS auto-routes to GrabMaps in Southeast Asia. Faster setup.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Technical details -->
                    <details class="mt-3">
                        <summary style="cursor:pointer;color:#7c3aed;font-weight:600;font-size:0.85rem;">
                            <i class="bi bi-code-slash me-1"></i> <span data-i18n="tech_details">Technical Details</span>
                        </summary>
                        <div class="mt-2 p-3 rounded-3" style="background:#1f2937;color:#e5e7eb;font-size:0.72rem;font-family:monospace;">
                            <div style="color:#9ca3af;">// v0 (legacy)</div>
                            <div>POST /places/v0/indexes/&lcub;idx&rcub;/search/text</div>
                            <div>{ Text: "...", Position: [...] }</div>
                            <div>→ data.Results[0].Place.Label</div>
                            <br>
                            <div style="color:#9ca3af;">// v2 (baru) ✨</div>
                            <div style="color:#86efac;">POST /v2/search-text</div>
                            <div style="color:#86efac;">{ QueryText: "...", QueryPosition: [...] }</div>
                            <div style="color:#86efac;">→ data.ResultItems[0].Title + Address.Label</div>
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
        function initMap() {
            map = new maplibregl.Map({
                container: 'map',
                style: '/api/map-style',
                center: [106.8456, -6.2088],
                zoom: 13,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.AttributionControl({
                customAttribution: '© Grab, © AWS'
            }), 'bottom-right');

            // Click map to add location
            map.on('click', async (e) => {
                const coords = [e.lngLat.lng, e.lngLat.lat];
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
                        showToast(t('location_found'), place.title, 'success');
                    } else {
                        item.name = `Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                        item.address = null;
                        renderLocationList();
                    }
                } catch (error) {
                    console.error(error);
                }
            });
        }


        /* =========================================
           4. LOCATION MANAGEMENT (CRUD)
           ========================================= */
        function buildPopupHtml(title, address) {
            const safeTitle = (title || '').replace(/</g, '&lt;');
            const safeAddr = (address || '').replace(/</g, '&lt;');
            if (safeAddr) {
                return `<div style="font-family:Inter,sans-serif;max-width:240px;">
                    <div style="font-weight:600;font-size:0.85rem;color:#1f2937;line-height:1.3;">${safeTitle}</div>
                    <div style="font-size:0.72rem;color:#6b7280;margin-top:3px;line-height:1.3;">${safeAddr}</div>
                </div>`;
            }
            return `<div style="font-family:Inter,sans-serif;font-weight:600;font-size:0.85rem;">${safeTitle}</div>`;
        }

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

                if (item) {
                    item.coords = updatedCoords;
                    showToast(t('loading'), t('finding_address'), 'info');

                    const newPlace = await getPlaceNameByCoords(updatedCoords);
                    if (newPlace && newPlace.title) {
                        item.name = newPlace.title;
                        item.address = newPlace.address;
                        newMarker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setHTML(buildPopupHtml(newPlace.title, newPlace.address)));
                        renderLocationList();
                        showToast(t('location_updated'), newPlace.title, 'success');
                    } else {
                        showToast(t('info'), t('location_not_found'), 'warning');
                    }
                }
            });

            selectedMarkerId = id;
            markersData.push({
                id,
                marker: newMarker,
                name: label,
                address,
                coords,
                placeId
            });
            renderLocationList();
            map.flyTo({
                center: coords,
                zoom: 15
            });
        }

        function removeLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) item.marker.remove();
            markersData = markersData.filter(m => m.id !== id);
            renderLocationList();
        }

        function clearAllMarkers() {
            markersData.forEach(m => m.marker.remove());
            markersData = [];
            selectedMarkerId = null;

            removeRouteLayer();
            renderLocationList();

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
                item.marker.togglePopup();
                renderLocationList();
            }
        }

        // === Location Detail Modal (v2 rich data) ===
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
                loadingEl.style.display = 'none';
                contentEl.style.display = 'block';
            } catch (err) {
                console.error('Detail fetch failed:', err);
                loadingEl.style.display = 'none';
                errorEl.style.display = 'block';
            }
        }

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

            let html = '';

            // === Header: Title + PlaceType badge ===
            html += `<div class="mb-3">
                <div style="font-size:1.05rem;font-weight:700;color:#1f2937;line-height:1.3;">${escapeHtml(title)}</div>`;
            if (placeType) {
                const placeTypeLabel = placeType.replace(/([A-Z])/g, ' $1').trim();
                html += `<span class="badge mt-2" style="background:#ede9fe;color:#6d28d9;font-size:0.7rem;font-weight:600;">
                    <i class="bi bi-tag-fill me-1"></i>${escapeHtml(placeTypeLabel)}
                </span>`;
            }
            html += `</div>`;

            // === Address section ===
            if (address || addr.Country) {
                html += `<div class="mb-3 p-3 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-geo-alt-fill" style="color:#00B14F;font-size:1rem;"></i>
                        <div class="flex-grow-1" style="font-size:0.82rem;color:#1f2937;line-height:1.4;">${escapeHtml(address || '-')}</div>
                    </div>`;

                // Structured address fields
                const structured = [];
                if (addr.PostalCode) structured.push(`<span class="badge bg-light text-dark border" style="font-size:0.68rem;font-weight:500;"><i class="bi bi-mailbox me-1"></i>${escapeHtml(addr.PostalCode)}</span>`);
                if (addr.Region && addr.Region.Name) structured.push(`<span class="badge bg-light text-dark border" style="font-size:0.68rem;font-weight:500;">${escapeHtml(addr.Region.Name)}</span>`);
                if (addr.Country && addr.Country.Name) structured.push(`<span class="badge bg-light text-dark border" style="font-size:0.68rem;font-weight:500;">🌐 ${escapeHtml(addr.Country.Name)}</span>`);
                if (structured.length) {
                    html += `<div class="d-flex flex-wrap gap-1 mt-2">${structured.join('')}</div>`;
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

        async function getPlaceNameByCoords(coords) {
            try {
                const response = await proxyPost('/api/places/reverse', {
                    QueryPosition: coords,
                    MaxResults: 1,
                    Language: 'en'
                });

                if (!response.ok) throw new Error('API Error');
                const data = await response.json();

                if (data.ResultItems && data.ResultItems.length > 0) {
                    const item = data.ResultItems[0];
                    const title = item.Title || '';
                    const address = (item.Address && item.Address.Label) || '';
                    return {
                        title: title || address || null,
                        address: title && address && title !== address ? address : null
                    };
                }
                return null;
            } catch (error) {
                console.error("Reverse geocode failed:", error);
                return null;
            }
        }


        /* =========================================
           5. ROUTING LOGIC
           ========================================= */

        // --- Single Route (A -> B) ---
        async function calculateRoute() {
            if (markersData.length < 2) return showToast(t('insufficient_data'), t('add_at_least_2'), 'warning');

            const origin = markersData[0].coords;
            const destination = markersData[1].coords;
            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            showToast(t('processing'), t('calc_single_route'), 'info');

            try {
                const response = await proxyPost('/api/routes/calculate', {
                    DeparturePosition: origin,
                    DestinationPosition: destination,
                    TravelMode: selectedMode,
                    DistanceUnit: "Kilometers",
                    DepartNow: true,
                    IncludeLegGeometry: true
                });
                if (!response.ok) throw new Error('Failed');
                const data = await response.json();

                if (data.Legs && data.Legs.length > 0 && data.Legs[0].Geometry) {
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': [{
                            'type': 'Feature',
                            'properties': {
                                'color': '#00B14F'
                            },
                            'geometry': {
                                'type': 'LineString',
                                'coordinates': data.Legs[0].Geometry.LineString
                            }
                        }]
                    };

                    drawRouteOnMap(featureCollection);

                    // UI Updates
                    const summary = data.Summary;
                    document.getElementById('resDistance').innerText = summary.Distance.toFixed(1) + ' km';
                    document.getElementById('resDuration').innerText = Math.round(summary.DurationSeconds / 60) + ' min';

                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    switchTab('routes');
                } else {
                    showToast(t('error'), t('path_not_found'), 'error');
                }
            } catch (e) {
                console.error(e);
                showToast(t('error'), t('failed'), 'error');
            }
        }

        async function calculateMultiRoute() {
            if (markersData.length < 2) return showToast(t('insufficient_data'), t('add_at_least_2'), 'warning');

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            //Ambil mode optimasi (Fast / Precise)
            const optimizationMode = document.querySelector('input[name="optMode"]:checked').value;

            const colors = ['#00B14F', '#007bff', '#dc3545', '#fd7e14', '#6f42c1', '#e83e8c', '#17a2b8'];
            const MAX_STOPS = 25;

            // --- STEP 1: LOGIKA PEMILIHAN OPTIMASI ---
            let optimizedData = [];

            if (optimizationMode === 'real') {
                // A. Mode Precise (Real Road)
                showToast(t('optimizing'), t('analyzing_traffic'), 'info');
                try {
                    optimizedData = await optimizeMarkersOrderReal([...markersData]);
                } catch (e) {
                    console.error(e);
                    showToast(t('warning'), t('opt_failed_fallback'), 'warning');
                    optimizedData = [...markersData];
                }
            } else {
                // B. Mode Fast (Straight Line)
                showToast(t('optimizing'), t('reordering_stops_sl'), 'info');
                optimizedData = optimizeMarkersOrder([...markersData]);
            }

            // Update data global & UI List
            markersData = optimizedData;
            renderLocationList();
            const workingData = markersData;
            // ------------------------------------------

            let totalDistance = 0;
            let totalDuration = 0;
            let allRouteFeatures = [];
            let globalLegIndex = 0;
            let segmentDetails = [];

            showToast(t('processing'), t('calc_final_route'), 'info');

            try {
                // Loop Batching (Sama seperti sebelumnya)
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
                        DistanceUnit: "Kilometers",
                        DepartNow: true,
                        IncludeLegGeometry: true
                    });

                    if (!response.ok) throw new Error(`Batch error`);
                    const data = await response.json();

                    totalDistance += data.Summary.Distance;
                    totalDuration += data.Summary.DurationSeconds;

                    if (data.Legs && data.Legs.length > 0) {
                        data.Legs.forEach((leg, legIndexInBatch) => {
                            if (leg.Geometry && leg.Geometry.LineString) {
                                const segmentColor = colors[globalLegIndex % colors.length];

                                allRouteFeatures.push({
                                    'type': 'Feature',
                                    'properties': {
                                        'color': segmentColor
                                    },
                                    'geometry': {
                                        'type': 'LineString',
                                        'coordinates': leg.Geometry.LineString
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

                                globalLegIndex++;
                            }
                        });
                    }
                }

                // Render Results
                if (allRouteFeatures.length > 0) {
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': allRouteFeatures
                    };
                    drawRouteOnMap(featureCollection);

                    const finalDist = totalDistance.toFixed(1) + ' km';
                    const finalDur = formatDuration(totalDuration);

                    document.getElementById('resDistance').innerText = finalDist;
                    document.getElementById('resDuration').innerText = finalDur;
                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    renderSegmentList(segmentDetails);
                    switchTab('routes');
                    showToast(t('success'), t('opt_route_done'), 'success');
                } else {
                    showToast(t('error'), t('route_geom_missing'), 'error');
                }

            } catch (error) {
                console.error(error);
                showToast(t('error'), t('failed_calc_route'), 'error');
            }
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
        async function getRouteMatrix(departure, destinations) {
            const response = await proxyPost('/api/routes/matrix', {
                DeparturePositions: [departure],
                DestinationPositions: destinations,
                TravelMode: "Car",
                DistanceUnit: "Kilometers"
            });

            if (!response.ok) throw new Error("Matrix API Error");
            return await response.json();
        }

        // --- LOGIKA PENGURUTAN REAL (ASYNC / PRECISE) ---
        async function optimizeMarkersOrderReal(originalData) {
            if (originalData.length <= 2) return originalData;

            // 1. Mulai dari titik pertama (Start Fixed)
            let sorted = [originalData[0]];
            let remaining = originalData.slice(1);

            // 2. Loop sampai semua titik masuk rute
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1];

                // Update Toast biar user tau prosesnya
                showToast(t('optimizing'), t('checking_roads', { n: sorted.length }), 'info');

                // Siapkan koordinat
                const currentCoords = current.coords;
                const destCoords = remaining.map(m => m.coords);

                try {
                    // PANGGIL API MATRIX
                    const matrixData = await getRouteMatrix(currentCoords, destCoords);

                    // AWS Matrix mengembalikan array "RouteMatrix[0]" (karena 1 origin)
                    const results = matrixData.RouteMatrix[0];

                    let bestIndex = -1;
                    let minDuration = Infinity; // Cari WAKTU tercepat

                    results.forEach((res, idx) => {
                        if (res && res.DurationSeconds !== undefined) { // Cek validitas
                            if (res.DurationSeconds < minDuration) {
                                minDuration = res.DurationSeconds;
                                bestIndex = idx;
                            }
                        }
                    });

                    if (bestIndex !== -1) {
                        sorted.push(remaining[bestIndex]);
                        remaining.splice(bestIndex, 1);
                    } else {
                        // Fallback jika API gagal kalkulasi rute (misal beda pulau)
                        sorted.push(remaining[0]);
                        remaining.shift();
                    }

                } catch (err) {
                    console.error("Matrix Optimization Failed:", err);
                    // Jika error (misal internet putus), kembalikan sisa apa adanya
                    return sorted.concat(remaining);
                }
            }
            return sorted;
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
                padding: 50
            });
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
                padding: 100,
                duration: 1000
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
            if (totalMinutes >= 60) {
                const hrs = Math.floor(totalMinutes / 60);
                const mins = totalMinutes % 60;
                return `${hrs} hr ${mins} min`;
            }
            return `${totalMinutes} min`;
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

                    const safeName = (item.name || '').replace(/"/g, '&quot;');
                    const safeAddr = (item.address || '').replace(/"/g, '&quot;');
                    const addressHtml = item.address
                        ? `<span class="loc-address text-truncate" title="${safeAddr}">${item.address}</span>`
                        : '';

                    div.innerHTML = `
                    <div class="loc-info" onclick="showLocationDetail(${item.id})">
                        <span class="loc-name text-truncate" title="${safeName}">${item.name}</span>
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

        input.addEventListener('input', debounce(async (e) => {
            const query = e.target.value;
            if (query.length < 3) {
                list.classList.remove('show');
                return;
            }
            try {
                const center = map.getCenter();
                const res = await proxyPost('/api/places/suggestions', {
                    QueryText: query,
                    MaxResults: 10,
                    Language: 'en',
                    BiasPosition: [center.lng, center.lat]
                });
                const data = await res.json();
                renderSuggestions(data.ResultItems);
            } catch (err) {
                console.error(err);
            }
        }, 300));

        function renderSuggestions(results) {
            list.innerHTML = '';
            if (!results || results.length === 0) {
                list.classList.remove('show');
                return;
            }
            results.forEach(item => {
                const place = item.Place || {};
                const placeId = place.PlaceId;
                if (!placeId) return; // Skip query-type suggestions (no place to select)
                const title = item.Title || '';
                const addressLabel = (place.Address && place.Address.Label) || '';
                const li = document.createElement('li');
                li.className = 'suggestion-item';
                li.innerHTML = `
                    <i class="bi bi-geo-alt"></i>
                    <div class="suggestion-text">
                        <div class="suggestion-title">${title}</div>
                        ${addressLabel ? `<div class="suggestion-address">${addressLabel}</div>` : ''}
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

        async function handleManualSearch() {
            const query = input.value;
            if (!query) return showToast(t('empty_search'), t('enter_keyword'), 'warning');
            list.classList.remove('show');

            try {
                const center = map.getCenter();
                const res = await proxyPost('/api/places/search', {
                    QueryText: query,
                    MaxResults: 10,
                    BiasPosition: [center.lng, center.lat]
                });
                const data = await res.json();

                if (data.ResultItems && data.ResultItems.length > 0) {
                    const item = data.ResultItems[0];
                    const title = item.Title || (item.Address && item.Address.Label) || query;
                    const address = (item.Title && item.Address && item.Address.Label && item.Title !== item.Address.Label)
                        ? item.Address.Label
                        : null;
                    addLocation(item.Position, title, address, item.PlaceId || null);
                    showToast(t('found'), title, 'success');
                } else {
                    showToast(t('not_found'), t('try_another'), 'warning');
                }
            } catch (err) {
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
        const WHATS_NEW_VERSION = 'v2.0';

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
            initMap();
            setupEventListeners();
            maybeShowWhatsNew();
        });
    </script>
</body>

</html>