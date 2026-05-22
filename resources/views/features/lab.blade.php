<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Feature Lab — AWS Grab Maps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
        }
        #map { position: absolute; inset: 0; }

        .lab-header {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 10;
            background: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lab-header .badge-v2 {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
        }
        .lab-header h1 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
        }
        .lab-header .back-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 1.1rem;
        }
        .lab-header .back-link:hover { color: #111; }

        .feature-panel {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10;
            width: 300px;
            max-height: calc(100vh - 32px);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width 0.2s, max-height 0.2s;
        }
        .feature-panel.collapsed {
            width: 44px;
            max-height: 44px;
        }
        .feature-panel.collapsed .feature-panel-title,
        .feature-panel.collapsed .feature-list {
            display: none;
        }
        .feature-panel.collapsed .feature-panel-header {
            padding: 10px;
            justify-content: center;
            border-bottom: none;
        }
        .feature-panel-header {
            padding: 10px 14px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .feature-panel-title { flex: 1; min-width: 0; }
        .feature-panel-header h2 {
            margin: 0;
            font-size: 0.85rem;
            font-weight: 700;
            color: #111;
            line-height: 1.2;
        }
        .feature-panel-header small {
            color: #6b7280;
            font-size: 0.68rem;
        }
        .panel-toggle {
            border: none;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 6px;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .panel-toggle:hover { background: #f3f4f6; color: #111; }
        .feature-list {
            overflow-y: auto;
            padding: 8px;
        }
        .feature-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.15s;
        }
        .feature-card:hover {
            border-color: #16a34a;
            box-shadow: 0 2px 8px rgba(22,163,74,0.1);
        }
        .feature-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #111;
            margin-bottom: 4px;
        }
        .feature-card-title i {
            color: #16a34a;
            font-size: 1rem;
        }
        .feature-card-desc {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .feature-btn {
            width: 100%;
            border: none;
            background: #16a34a;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: background 0.15s;
        }
        .feature-btn:hover { background: #15803d; }
        .feature-btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .feature-btn.toggle-on {
            background: #dc2626;
        }
        .feature-btn.toggle-on:hover { background: #b91c1c; }

        .coord-readout {
            margin-top: 10px;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: none;
        }
        .coord-readout.show { display: block; }
        .coord-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
        }
        .coord-row + .coord-row { border-top: 1px dashed #e5e7eb; }
        .coord-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 32px;
            flex-shrink: 0;
        }
        .coord-row code {
            flex: 1;
            font-family: ui-monospace, 'SF Mono', monospace;
            font-size: 0.75rem;
            color: #111;
            background: transparent;
            padding: 0;
            word-break: break-all;
        }
        .mini-copy {
            border: none;
            background: #fff;
            color: #16a34a;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            padding: 3px 7px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.15s;
            flex-shrink: 0;
        }
        .mini-copy:hover {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }
        .mini-copy.copied {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }
        .gmaps-link {
            display: flex !important;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 6px 10px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.72rem;
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.15s;
        }
        .gmaps-link:hover {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        /* Toast */
        .lab-toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #111;
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lab-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .lab-toast.success { background: #16a34a; }
        .lab-toast.error { background: #dc2626; }

        /* Crosshair cursor for copy mode */
        .copy-mode #map canvas { cursor: crosshair !important; }

        /* Style switcher (bottom-left) */
        .style-switcher {
            position: absolute;
            bottom: 48px;
            left: 16px;
            z-index: 10;
            background: #fff;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .style-row {
            display: flex;
            gap: 4px;
        }
        .style-btn {
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 7px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s;
        }
        .style-btn:hover {
            border-color: #16a34a;
            color: #16a34a;
        }
        .style-btn.active {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }
        .style-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 2px 0;
        }
        .style-btn i { font-size: 0.9rem; }
    </style>
</head>

<body>

    <div class="lab-header">
        <a href="/" class="back-link" title="Back to home"><i class="bi bi-arrow-left"></i></a>
        <h1>Feature Lab</h1>
        <span class="badge-v2">Maps v2</span>
    </div>

    <div class="feature-panel" id="featurePanel">
        <div class="feature-panel-header">
            <div class="feature-panel-title">
                <h2>Available Features</h2>
                <small>Click to try each demo</small>
            </div>
            <button type="button" class="panel-toggle" id="panelToggle" title="Collapse/expand panel">
                <i class="bi bi-chevron-right" id="panelToggleIcon"></i>
            </button>
        </div>
        <div class="feature-list" id="featureList">

            <!-- Feature 1: Zoom to current location -->
            <div class="feature-card">
                <div class="feature-card-title">
                    <i class="bi bi-geo-alt-fill"></i>
                    Zoom to My Location
                </div>
                <div class="feature-card-desc">
                    Detect your current GPS location and fly the map there.
                </div>
                <button class="feature-btn" id="btnZoomToMe">
                    <i class="bi bi-crosshair"></i>
                    Locate Me
                </button>
            </div>

            <!-- Feature 2: Pick coordinates -->
            <div class="feature-card">
                <div class="feature-card-title">
                    <i class="bi bi-pin-map-fill"></i>
                    Pick Coordinates
                </div>
                <div class="feature-card-desc">
                    Enable, then click anywhere to drop a pin. Copy lat, lng, or open in Google Maps.
                </div>
                <button class="feature-btn" id="btnCopyCoords">
                    <i class="bi bi-toggle-off"></i>
                    <span id="copyCoordsLabel">Enable Pick Mode</span>
                </button>
                <div class="coord-readout" id="coordReadout">
                    <div class="coord-row">
                        <span class="coord-label">Lat</span>
                        <code id="coordLat">—</code>
                        <button type="button" class="mini-copy" data-target="coordLat" title="Copy latitude"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div class="coord-row">
                        <span class="coord-label">Lng</span>
                        <code id="coordLng">—</code>
                        <button type="button" class="mini-copy" data-target="coordLng" title="Copy longitude"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div class="coord-row">
                        <span class="coord-label">Pair</span>
                        <code id="coordPair">—</code>
                        <button type="button" class="mini-copy" data-target="coordPair" title="Copy lat, lng"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <a id="coordGmaps" href="#" target="_blank" rel="noopener" class="gmaps-link" style="display:none;">
                        <i class="bi bi-box-arrow-up-right"></i> Open in Google Maps
                    </a>
                </div>
            </div>

        </div>
    </div>

    <div id="map"></div>

    <div class="style-switcher" id="styleSwitcher">
        <div class="style-row">
            <button type="button" class="style-btn active" data-style="Standard" title="Standard"><i class="bi bi-map"></i>Standard</button>
            <button type="button" class="style-btn" data-style="Monochrome" title="Monochrome"><i class="bi bi-circle-half"></i>Mono</button>
        </div>
        <div class="style-divider"></div>
        <div class="style-row">
            <button type="button" class="style-btn active" data-color="Light" title="Light"><i class="bi bi-sun-fill"></i>Light</button>
            <button type="button" class="style-btn" data-color="Dark" title="Dark"><i class="bi bi-moon-stars-fill"></i>Dark</button>
        </div>
    </div>

    <div class="lab-toast" id="labToast"></div>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    <script>
        /* =========================================
           MAP INIT (uses AWS Location Service v2 via /api/map-style)
           ========================================= */
        const mapState = { style: 'Standard', color: 'Light' };
        const styleUrl = () => `/api/v2/map-style?style=${mapState.style}&color=${mapState.color}`;

        const map = new maplibregl.Map({
            container: 'map',
            style: styleUrl(),
            center: [106.8456, -6.2088], // Jakarta
            zoom: 12,
            attributionControl: false
        });

        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
        map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-left');

        let myLocationMarker = null;

        /* =========================================
           PANEL COLLAPSE TOGGLE
           ========================================= */
        const featurePanel = document.getElementById('featurePanel');
        const panelToggle = document.getElementById('panelToggle');
        const panelToggleIcon = document.getElementById('panelToggleIcon');
        panelToggle.addEventListener('click', () => {
            const collapsed = featurePanel.classList.toggle('collapsed');
            panelToggleIcon.className = collapsed ? 'bi bi-list' : 'bi bi-chevron-right';
            panelToggle.title = collapsed ? 'Expand panel' : 'Collapse panel';
        });

        /* =========================================
           STYLE SWITCHER
           ========================================= */
        const styleSwitcher = document.getElementById('styleSwitcher');
        styleSwitcher.addEventListener('click', (ev) => {
            const btn = ev.target.closest('.style-btn');
            if (!btn) return;

            if (btn.dataset.style) {
                if (mapState.style === btn.dataset.style) return;
                mapState.style = btn.dataset.style;
                styleSwitcher.querySelectorAll('[data-style]').forEach(b => b.classList.toggle('active', b === btn));
            } else if (btn.dataset.color) {
                if (mapState.color === btn.dataset.color) return;
                mapState.color = btn.dataset.color;
                styleSwitcher.querySelectorAll('[data-color]').forEach(b => b.classList.toggle('active', b === btn));
            } else {
                return;
            }

            // Swap basemap — markers persist (they're DOM, not part of style)
            map.setStyle(styleUrl());
        });

        /* =========================================
           TOAST HELPER
           ========================================= */
        const toastEl = document.getElementById('labToast');
        let toastTimer = null;
        function toast(msg, type = '') {
            toastEl.className = 'lab-toast show ' + type;
            toastEl.innerHTML = (type === 'success' ? '<i class="bi bi-check-circle-fill"></i>' :
                                 type === 'error' ? '<i class="bi bi-exclamation-circle-fill"></i>' :
                                 '<i class="bi bi-info-circle-fill"></i>') + ' ' + msg;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2800);
        }

        /* =========================================
           FEATURE 1: ZOOM TO MY LOCATION
           ========================================= */
        const btnZoomToMe = document.getElementById('btnZoomToMe');
        btnZoomToMe.addEventListener('click', () => {
            if (!navigator.geolocation) {
                toast('Geolocation not supported by your browser', 'error');
                return;
            }

            btnZoomToMe.disabled = true;
            btnZoomToMe.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Locating...';

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lng = pos.coords.longitude;
                    const lat = pos.coords.latitude;
                    const accuracy = pos.coords.accuracy;

                    map.flyTo({ center: [lng, lat], zoom: 16, speed: 1.2 });

                    if (myLocationMarker) myLocationMarker.remove();
                    myLocationMarker = new maplibregl.Marker({ color: '#2563eb' })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(
                            `<strong>You are here</strong><br>
                             <small>Accuracy: ±${Math.round(accuracy)}m</small><br>
                             <small>${lat.toFixed(6)}, ${lng.toFixed(6)}</small>`
                        ))
                        .addTo(map);
                    myLocationMarker.togglePopup();

                    toast(`Located you within ${Math.round(accuracy)}m`, 'success');
                    btnZoomToMe.disabled = false;
                    btnZoomToMe.innerHTML = '<i class="bi bi-crosshair"></i> Locate Me Again';
                },
                (err) => {
                    const msgs = {
                        1: 'Permission denied. Please allow location access.',
                        2: 'Location unavailable.',
                        3: 'Request timed out.'
                    };
                    toast(msgs[err.code] || 'Failed to get location', 'error');
                    btnZoomToMe.disabled = false;
                    btnZoomToMe.innerHTML = '<i class="bi bi-crosshair"></i> Locate Me';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });

        /* =========================================
           FEATURE 2: PICK COORDINATES
           ========================================= */
        const btnCopyCoords = document.getElementById('btnCopyCoords');
        const copyCoordsLabel = document.getElementById('copyCoordsLabel');
        const coordReadout = document.getElementById('coordReadout');
        const coordLat = document.getElementById('coordLat');
        const coordLng = document.getElementById('coordLng');
        const coordPair = document.getElementById('coordPair');
        const coordGmaps = document.getElementById('coordGmaps');
        let copyModeOn = false;
        let pickMarker = null;

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            let ok = false;
            try { ok = document.execCommand('copy'); } catch (_) { ok = false; }
            document.body.removeChild(ta);
            return ok;
        }

        function flashCopied(btn) {
            if (!btn) return;
            btn.classList.add('copied');
            const icon = btn.querySelector('i');
            if (!icon) return;
            const original = icon.className;
            icon.className = 'bi bi-check-lg';
            setTimeout(() => {
                btn.classList.remove('copied');
                icon.className = original;
            }, 1200);
        }

        async function copyText(text, btn) {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    if (!fallbackCopy(text)) throw new Error('execCommand failed');
                }
                toast(`Copied: ${text}`, 'success');
                flashCopied(btn);
            } catch (err) {
                console.error('Copy failed', err);
                if (fallbackCopy(text)) {
                    toast(`Copied: ${text}`, 'success');
                    flashCopied(btn);
                } else {
                    toast('Copy failed — clipboard blocked', 'error');
                }
            }
        }

        function handleCopyClick(e) {
            const lat = e.lngLat.lat.toFixed(6);
            const lng = e.lngLat.lng.toFixed(6);
            const pair = `${lat}, ${lng}`;

            coordReadout.classList.add('show');
            coordLat.textContent = lat;
            coordLng.textContent = lng;
            coordPair.textContent = pair;
            coordGmaps.href = `https://www.google.com/maps?q=${lat},${lng}`;
            coordGmaps.style.display = 'flex';

            // Drop / move pin
            if (pickMarker) {
                pickMarker.setLngLat(e.lngLat);
            } else {
                pickMarker = new maplibregl.Marker({ color: '#16a34a', draggable: true })
                    .setLngLat(e.lngLat)
                    .addTo(map);
                pickMarker.on('dragend', () => {
                    const ll = pickMarker.getLngLat();
                    handleCopyClick({ lngLat: ll });
                });
            }

            // Auto-copy the pair on click for convenience
            copyText(pair, null);
        }

        // Per-row copy buttons (event delegation handles clicks on <i> child too)
        coordReadout.addEventListener('click', (ev) => {
            const btn = ev.target.closest('.mini-copy');
            if (!btn) return;
            ev.preventDefault();
            ev.stopPropagation();
            const target = document.getElementById(btn.dataset.target);
            if (!target) return;
            const val = target.textContent.trim();
            if (!val || val === '—') {
                toast('Click the map first to pick a point', 'error');
                return;
            }
            copyText(val, btn);
        });

        btnCopyCoords.addEventListener('click', () => {
            copyModeOn = !copyModeOn;
            if (copyModeOn) {
                map.on('click', handleCopyClick);
                document.body.classList.add('copy-mode');
                btnCopyCoords.classList.add('toggle-on');
                copyCoordsLabel.textContent = 'Disable Pick Mode';
                btnCopyCoords.querySelector('i').className = 'bi bi-toggle-on';
                toast('Pick mode ON — click the map', 'success');
            } else {
                map.off('click', handleCopyClick);
                document.body.classList.remove('copy-mode');
                btnCopyCoords.classList.remove('toggle-on');
                copyCoordsLabel.textContent = 'Enable Pick Mode';
                btnCopyCoords.querySelector('i').className = 'bi bi-toggle-off';
                if (pickMarker) { pickMarker.remove(); pickMarker = null; }
                coordReadout.classList.remove('show');
                coordGmaps.style.display = 'none';
            }
        });
    </script>
</body>

</html>
