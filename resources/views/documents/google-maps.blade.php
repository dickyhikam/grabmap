<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Google Maps Demo — RS Fatmawati</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
        }
        #map { position: absolute; top: 0; bottom: 0; right: 0; left: 360px; }

        .sidebar {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 360px;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            z-index: 5;
        }
        .sidebar-header {
            background: linear-gradient(135deg, #4285F4 0%, #34A853 100%);
            color: #fff;
            padding: 16px 18px;
        }
        .sidebar-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1rem;
            display: flex; align-items: center; gap: 8px;
        }
        .sidebar-header small {
            display: block;
            opacity: 0.92;
            font-size: 0.75rem;
            margin-top: 2px;
        }

        .center-card {
            margin: 12px;
            padding: 12px 14px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
        }
        .center-card .label {
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #15803d;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .center-card .name {
            font-size: 0.92rem;
            font-weight: 700;
            color: #14532d;
        }
        .center-card .coord {
            font-size: 0.72rem;
            color: #166534;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 2px;
        }

        .search-box {
            padding: 0 12px 12px;
            display: flex; gap: 6px;
        }
        .search-box input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.88rem;
            outline: none;
            transition: all 0.12s;
        }
        .search-box input:focus { border-color: #4285F4; box-shadow: 0 0 0 3px rgba(66,133,244,0.15); }
        .search-box button {
            background: #4285F4;
            color: #fff;
            border: 0;
            padding: 0 14px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.12s;
        }
        .search-box button:hover { background: #1a73e8; }
        .search-box button:disabled { opacity: 0.6; cursor: wait; }

        .results-area { flex: 1; overflow-y: auto; padding: 0 12px 12px; }
        .result-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.12s;
        }
        .result-item:hover {
            border-color: #4285F4;
            box-shadow: 0 2px 8px rgba(66,133,244,0.15);
            transform: translateY(-1px);
        }
        .result-item.active {
            border-color: #4285F4;
            background: #eff6ff;
        }
        .result-item .ri-title {
            font-weight: 600;
            font-size: 0.86rem;
            color: #1f2937;
            margin-bottom: 2px;
            display: flex; align-items: center; gap: 6px;
            justify-content: space-between;
        }
        .result-item .ri-distance {
            display: inline-block;
            background: #f0fdf4;
            color: #15803d;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .result-item .ri-addr {
            font-size: 0.74rem;
            color: #6b7280;
            line-height: 1.4;
        }
        .result-item .ri-meta {
            display: flex; gap: 6px; flex-wrap: wrap;
            margin-top: 6px;
            font-size: 0.7rem;
            color: #6b7280;
        }
        .result-item .ri-rating {
            color: #f59e0b;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 40px 16px;
            color: #94a3b8;
            font-size: 0.86rem;
        }
        .empty-state i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 8px; }

        .status-bar {
            padding: 8px 12px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            font-size: 0.74rem;
            color: #64748b;
        }
        .status-bar.error { background: #fef2f2; color: #b91c1c; }

        @media (max-width: 768px) {
            .sidebar { width: 100%; height: 50%; bottom: auto; }
            #map { top: 50%; left: 0; }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <h5><i class="bi bi-google"></i> Google Maps Demo</h5>
        <small>Pencarian dengan jarak dari titik default</small>
    </div>

    <div class="center-card">
        <div class="label"><i class="bi bi-geo-alt-fill"></i> Lokasi Default</div>
        <div class="name">RSUP Fatmawati</div>
        <div class="coord">-6.2939, 106.7975</div>
    </div>

    <div class="search-box">
        <input id="searchInput" type="text" placeholder="Cari tempat... (mis. apotek, atm)" autocomplete="off">
        <button id="btnSearch"><i class="bi bi-search"></i></button>
    </div>

    <div class="results-area" id="resultsArea">
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <div>Ketik kata kunci lalu klik tombol cari</div>
            <div style="font-size:0.72rem;margin-top:8px;">Hasil akan menampilkan jarak dari RSUP Fatmawati</div>
        </div>
    </div>

    <div class="status-bar" id="statusBar">Ready · API Google Places (Legacy)</div>
</aside>

<div id="map"></div>

<script>
    // ====== CONFIG ======
    const FATMAWATI = { lat: -6.2939, lng: 106.7975 };
    const FATMAWATI_NAME = 'RSUP Fatmawati';

    let map, originMarker, placesService;
    let resultMarkers = [];
    let activeInfoWindow = null;

    // ====== CALLBACK INIT ======
    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: FATMAWATI,
            zoom: 15,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true
        });

        // Origin marker — RS Fatmawati (green)
        originMarker = new google.maps.Marker({
            position: FATMAWATI,
            map,
            title: FATMAWATI_NAME,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                fillColor: '#10b981',
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 3,
                scale: 10
            }
        });

        const originInfo = new google.maps.InfoWindow({
            content: `<div style="padding:4px 6px;"><strong>📍 ${FATMAWATI_NAME}</strong><br><small style="color:#6b7280;">Titik referensi distance</small></div>`
        });
        originMarker.addListener('click', () => {
            if (activeInfoWindow) activeInfoWindow.close();
            originInfo.open({ anchor: originMarker, map });
            activeInfoWindow = originInfo;
        });

        // Places service
        placesService = new google.maps.places.PlacesService(map);

        // Wire up UI
        document.getElementById('btnSearch').addEventListener('click', runSearch);
        document.getElementById('searchInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') runSearch();
        });
    }

    // ====== SEARCH ======
    function runSearch() {
        const query = document.getElementById('searchInput').value.trim();
        if (!query) {
            setStatus('Ketik kata kunci dulu', 'error');
            return;
        }

        const btn = document.getElementById('btnSearch');
        btn.disabled = true;
        setStatus('🔍 Mencari "' + query + '"...');

        const request = {
            query: query,
            location: FATMAWATI,
            radius: 5000  // 5 km bias radius
        };

        placesService.textSearch(request, (results, status) => {
            btn.disabled = false;
            console.log('Google Places status:', status, 'results:', results);

            if (status === google.maps.places.PlacesServiceStatus.OK && results) {
                renderResults(results);
                setStatus(`✅ ${results.length} hasil ditemukan untuk "${query}"`);
            } else if (status === google.maps.places.PlacesServiceStatus.ZERO_RESULTS) {
                renderEmpty('Tidak ada hasil untuk "' + query + '"');
                setStatus('Tidak ada hasil', 'error');
            } else if (status === 'REQUEST_DENIED') {
                renderEmpty(`
                    <strong style="color:#b91c1c;">REQUEST_DENIED</strong><br>
                    <small>Cek di Google Cloud Console:</small>
                    <ol style="text-align:left;font-size:0.74rem;color:#6b7280;margin-top:8px;padding-left:16px;">
                        <li>Enable <strong>Places API</strong> (yang lama, bukan "New")</li>
                        <li>Enable <strong>Maps JavaScript API</strong></li>
                        <li>Setup <strong>Billing account</strong></li>
                        <li>Cek API Key <strong>HTTP referrer restrictions</strong></li>
                    </ol>
                `);
                setStatus('❌ REQUEST_DENIED — cek console.log untuk detail', 'error');
            } else {
                renderEmpty('Error: ' + status);
                setStatus('❌ ' + status, 'error');
            }
        });
    }

    // ====== RENDER RESULTS ======
    function renderResults(places) {
        // Clear previous result markers
        resultMarkers.forEach(m => m.setMap(null));
        resultMarkers = [];

        const container = document.getElementById('resultsArea');
        container.innerHTML = '';

        // Sort by distance ASC
        const enriched = places.map(place => {
            const distMeters = google.maps.geometry.spherical.computeDistanceBetween(
                new google.maps.LatLng(FATMAWATI),
                place.geometry.location
            );
            return { place, distMeters };
        });
        enriched.sort((a, b) => a.distMeters - b.distMeters);

        const bounds = new google.maps.LatLngBounds();
        bounds.extend(FATMAWATI);

        enriched.forEach(({ place, distMeters }, idx) => {
            const distLabel = distMeters < 1000
                ? Math.round(distMeters) + ' m'
                : (distMeters / 1000).toFixed(2) + ' km';

            // Marker
            const marker = new google.maps.Marker({
                position: place.geometry.location,
                map,
                title: place.name,
                label: { text: String(idx + 1), color: '#fff', fontWeight: '700' }
            });
            const info = new google.maps.InfoWindow({
                content: `
                    <div style="padding:4px 6px;max-width:240px;">
                        <strong>${place.name}</strong><br>
                        <small style="color:#6b7280;">${place.formatted_address || ''}</small><br>
                        <small style="color:#15803d;font-weight:700;">≈ ${distLabel} dari ${FATMAWATI_NAME}</small>
                    </div>`
            });
            marker.addListener('click', () => {
                if (activeInfoWindow) activeInfoWindow.close();
                info.open({ anchor: marker, map });
                activeInfoWindow = info;
            });
            resultMarkers.push(marker);
            bounds.extend(place.geometry.location);

            // List card
            const card = document.createElement('div');
            card.className = 'result-item';
            card.dataset.idx = idx;

            const ratingHtml = place.rating
                ? `<span class="ri-rating">★ ${place.rating}</span>`
                : '';
            const totalUserRatings = place.user_ratings_total
                ? `<span>(${place.user_ratings_total} ulasan)</span>`
                : '';
            const openHtml = place.opening_hours && place.opening_hours.open_now !== undefined
                ? (place.opening_hours.open_now
                    ? '<span style="color:#15803d;font-weight:600;">● Buka</span>'
                    : '<span style="color:#dc2626;font-weight:600;">● Tutup</span>')
                : '';

            card.innerHTML = `
                <div class="ri-title">
                    <span><strong>#${idx + 1}</strong> ${escape(place.name)}</span>
                    <span class="ri-distance">≈ ${distLabel}</span>
                </div>
                <div class="ri-addr">${escape(place.formatted_address || '—')}</div>
                <div class="ri-meta">
                    ${ratingHtml}
                    ${totalUserRatings}
                    ${openHtml}
                </div>
            `;
            card.addEventListener('click', () => {
                document.querySelectorAll('.result-item').forEach(el => el.classList.remove('active'));
                card.classList.add('active');
                map.panTo(place.geometry.location);
                map.setZoom(17);
                if (activeInfoWindow) activeInfoWindow.close();
                info.open({ anchor: marker, map });
                activeInfoWindow = info;
            });
            container.appendChild(card);
        });

        // Fit bounds to show origin + all results
        if (enriched.length > 0) {
            map.fitBounds(bounds, 80);
        }
    }

    function renderEmpty(message) {
        const container = document.getElementById('resultsArea');
        container.innerHTML = `<div class="empty-state"><i class="bi bi-emoji-frown"></i>${message}</div>`;
        // Clear markers
        resultMarkers.forEach(m => m.setMap(null));
        resultMarkers = [];
    }

    function setStatus(text, type) {
        const bar = document.getElementById('statusBar');
        bar.textContent = text;
        bar.className = 'status-bar' + (type === 'error' ? ' error' : '');
    }

    function escape(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
    }
</script>

<!-- Google Maps JS API + Places + Geometry libraries -->
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_API_KEY') }}&libraries=places,geometry&callback=initMap&loading=async">
</script>

</body>
</html>
