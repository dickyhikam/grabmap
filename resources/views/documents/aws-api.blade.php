<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AWS Location Service Documentation — GrabMaps</title>

    {{-- Tema dipasang sebelum CSS supaya tidak berkedip putih. Pilihan disimpan
         di data-theme-choice, hasilnya (light/dark) di data-theme — jadi aturan
         gelap cukup ditulis sekali sebagai [data-theme="dark"]. Kuncinya sama
         dengan halaman lain: gm-theme. --}}
    <script>
        (function() {
            var root = document.documentElement;
            var choice = 'system';
            try {
                choice = localStorage.getItem('gm-theme') || 'system';
            } catch (e) {
                /* mode privat */ }

            window.gmApplyTheme = function(next) {
                if (next) choice = next;
                var dark = choice === 'dark' ||
                    (choice === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                root.setAttribute('data-theme-choice', choice);
                root.setAttribute('data-theme', dark ? 'dark' : 'light');
            };

            window.gmApplyTheme();
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                    if (choice === 'system') window.gmApplyTheme();
                });
            }
        })();
    </script>

    {{-- Halaman ini sempat tidak punya favicon sama sekali, jadi tabnya memakai
         ikon bawaan peramban. --}}
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">

    {{-- MapLibre untuk live preview di Maps panels --}}
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <link rel="stylesheet" href="/css/gm-page-head.css?v={{ filemtime(public_path('css/gm-page-head.css')) }}">

    <link rel="stylesheet" href="{{ asset('css/aws-api-docs.css') }}?v={{ filemtime(public_path('css/aws-api-docs.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/aws-api-builder.css') }}?v={{ filemtime(public_path('css/aws-api-builder.css')) }}">
</head>

<body>

    {{-- Try it Live module — load awal supaya AWSAPI_TryIt available saat inline init() di setiap panel --}}
    <script src="{{ asset('javascript/docs/aws-api-try-it.js') }}"></script>

    {{-- Kepala halaman memakai komponen bersama (components/page-head). Halaman
         ini tidak menggulir jendela — isinya yang bergulir di dalam .layout —
         jadi kepala tetap melebar penuh; tingginya dibagikan lewat --head-h dan
         dipakai .layout serta banner tanpa-kunci. --}}
    <x-page-head :back="url('/')" back-label="Kembali ke beranda">
        <x-slot:title><span data-i18n="topbar_title">AWS Location Service Documentation</span></x-slot:title>
        <x-slot:subtitle><span data-i18n="topbar_subtitle">v2 (standalone, recommended) &amp; v0 (legacy)</span></x-slot:subtitle>

        <x-slot:tools>
            {{-- Kotak cari dibungkus supaya ikonnya jadi elemen sendiri, bukan
                 emoji di dalam placeholder, dan bentuknya bisa mengikuti pil
                 kontrol lain di kepala halaman. --}}
            <div class="head-search">
                <i class="bi bi-search"></i>
                <input type="text" id="searchBox" placeholder="Search operation..." data-i18n-placeholder="search_placeholder" autocomplete="off">
            </div>

            {{-- Tema: terang / gelap / ikut sistem --}}
            <div class="theme-switch" id="themeToggle">
                <button type="button" data-theme-set="light" data-tip="Light mode" aria-label="Light mode"><i class="bi bi-sun"></i></button>
                <button type="button" data-theme-set="dark" data-tip="Dark mode" aria-label="Dark mode"><i class="bi bi-moon"></i></button>
                <button type="button" data-theme-set="system" data-tip="Follow system" aria-label="Follow system"><i class="bi bi-circle-half"></i></button>
            </div>

            {{-- Bahasa ditukar di browser lewat AWSAPI_applyI18n, jadi rodanya
                 memakai <button> tanpa href. --}}
            <div class="lang-wheel" id="langWheel" role="listbox" aria-label="Language">
                <div class="lang-track" id="langTrack">
                    <button type="button" class="lang-item" data-lang="en" data-tip="English">EN</button>
                    <button type="button" class="lang-item" data-lang="id" data-tip="Indonesia">ID</button>
                </div>
            </div>
        </x-slot:tools>

        {{-- Bentuknya sama dengan tombol API Key di /tester-api: ikon saja,
             keterangannya lewat tooltip, dan titik status di pojok. Label serta
             pil masa berlaku tetap ada di DOM (disembunyikan) karena skrip
             kunci masih menulis ke keduanya. --}}
        <button type="button" id="btnKeyInspector" class="head-btn is-solid is-icon"
            data-i18n-title="key_inspector_title" title="Configure your API Key"
            data-tip="My Key" aria-label="My Key">
            <i class="bi bi-key-fill"></i>
            <span class="head-btn-dot" id="keyInspectorDot"></span>
            <span id="keyInspectorLabel" class="visually-hidden" data-i18n="key_inspector_btn">My Key</span>
            <span id="keyInspectorPill" class="key-pill" style="display:none;"></span>
        </button>
    </x-page-head>

    <!-- Persistent no-key banner (shown when user hasn't configured API Key) -->
    <div class="no-key-banner" id="noKeyBanner" role="alert">
        <span class="nkb-icon">🔑</span>
        <span class="nkb-text" data-i18n-html="no_key_banner">
            <b>Configure your API Key first</b> — <span>Try it Live is disabled until you paste your own AWS Location Service API Key. Your key stays in this browser only.</span>
        </span>
        <button type="button" class="nkb-btn" id="nkbConfigureBtn" data-i18n="no_key_banner_cta">Configure now</button>
    </div>

    <!-- ====================== API KEY INSPECTOR MODAL ====================== -->
    <div id="keyInspectorModal" class="key-modal" style="display:none;">
        <div class="key-modal-card">
            {{-- Bentuknya disamakan dengan gerbang API key di /tester-api: kepala
                 hijau, catatan penyimpanan, lalu satu kolom key. Isian yang cuma
                 dipakai halaman ini (nama, masa berlaku, daftar aksi, dua sakelar)
                 dilipat ke bagian "Lanjutan" supaya alur utamanya sependek tester,
                 tapi titik izin di sidebar dan lencana kedaluwarsa tetap hidup. --}}
            {{-- Susunannya disalin dari gerbang API key di /tester-api supaya
                 keduanya benar-benar sama: kepala hijau berikon perisai, kotak
                 catatan ungu muda, satu kolom key bergaya input-group, panduan
                 yang bisa dibuka, lalu tombol pil selebar modal. --}}
            <div class="key-modal-header">
                <div class="kmh-title">
                    <i class="bi bi-shield-lock-fill"></i>
                    <div>
                        <strong data-i18n="key_inspector_title">Configure your API Key</strong>
                        <small data-i18n="key_gate_subtitle">Enter your AWS Location Service API key to continue</small>
                    </div>
                </div>
                <button type="button" class="key-modal-close" id="keyModalClose">&times;</button>
            </div>

            <div class="key-modal-body">
                <div class="key-note">
                    <i class="bi bi-info-circle-fill"></i>
                    <div data-i18n="key_inspector_intro">
                        Paste your AWS Location Service API Key info to see which operations you can access. All data stays in your browser (localStorage).
                    </div>
                </div>

                <label class="key-label">
                    <span data-i18n="key_field_key">AWS Location Service API Key</span> <span class="key-req">*</span>
                </label>

                <div class="key-input-group">
                    <span class="kig-prefix"><i class="bi bi-key-fill"></i></span>
                    <input type="password" id="keyForm_apiKey" placeholder="v1.public.xxxxxxxxxxxxxxxxxxx" autocomplete="off">
                    <button type="button" class="kig-peek" id="keyPeekBtn" aria-label="Show / hide"><i class="bi bi-eye" id="keyPeekIcon"></i></button>
                </div>

                <details class="key-how">
                    <summary>
                        <i class="bi bi-question-circle me-1"></i> <span data-i18n="key_how_to">How do I get an API key?</span>
                    </summary>
                    <div class="key-how-body">
                        <ol>
                            <li>Buka <a href="https://console.aws.amazon.com/location/" target="_blank" rel="noopener">AWS Console &rarr; Location Service</a></li>
                            <li>Klik <b>API keys &rarr; Create API key</b></li>
                            <li>Pilih region <b>ap-southeast-1</b> (Singapore)</li>
                            <li>Centang resources yang dibutuhkan: Maps, Places, Routes</li>
                            <li>Copy API key value (format: <code>v1.public.xxx...</code>)</li>
                            <li>Paste di kolom di atas</li>
                        </ol>
                    </div>
                </details>
            </div>

            <div class="key-modal-footer">
                <button type="button" class="key-btn-primary" id="keySaveBtn">
                    <i class="bi bi-arrow-right-circle-fill me-1"></i> <span data-i18n="key_gate_continue">Continue</span>
                </button>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="layout">
        <!-- ========================== SIDEBAR ========================== -->
        <aside class="sidebar">
            <div id="sidebarSearchEmpty" style="display:none;padding:14px 12px;font-size:0.78rem;color:#9ca3af;text-align:center;" data-i18n="sidebar_search_empty">No operations found</div>

            {{-- Daftar utama hanya memuat operasi yang benar-benar didukung
                 GrabMaps di ap-southeast-1 — tujuh aksi yang sama dengan yang
                 boleh dipilih saat membuat API key (config/geo_actions.php).
                 Sisanya tidak dihapus, hanya dipindah ke kelompok tertutup di
                 bawah supaya daftar ini tidak lagi kepanjangan. --}}

            <!-- Maps V2 -->
            <div class="service-group" data-service="maps">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_maps">Maps</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="maps-get-tile">GetTile </a></li>
                </ul>
            </div>

            <!-- Places V2 -->
            <div class="service-group" data-service="places">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_places">Places</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="places-search-text">SearchText </a></li>
                    <li><a class="op-link" data-op="places-suggest">Suggest </a></li>
                    <li><a class="op-link" data-op="places-reverse-geocode">ReverseGeocode </a></li>
                    <li><a class="op-link" data-op="places-get-place">GetPlace </a></li>
                </ul>
            </div>

            <!-- Routes V2 -->
            <div class="service-group" data-service="routes">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_routes">Routes</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="routes-calculate-routes">CalculateRoutes </a></li>
                    <li><a class="op-link" data-op="routes-calculate-route-matrix">CalculateRouteMatrix </a></li>
                </ul>
            </div>

            <!-- Meta -->
            <div class="service-group" data-service="meta">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_general">General Topics</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="meta-overview" data-i18n="meta_overview">Overview v0 vs v2</a></li>
                    <li><a class="op-link" data-op="meta-auth" data-i18n="meta_auth">Authentication</a></li>
                    <li><a class="op-link" data-op="meta-quotas" data-i18n="meta_quotas">Quotas &amp; Limits</a></li>
                    <li><a class="op-link" data-op="meta-migration" data-i18n="meta_migration">Migration Guide</a></li>
                </ul>
            </div>

            <!-- Legend -->
            <div class="legend">
                <div class="row" style="margin-top:8px;font-size:0.7rem;line-height:1.4;">
                    Status berdasarkan <code style="font-size:0.65rem;padding:1px 4px;">ap-southeast-1</code> default provider (GrabMaps).
                </div>
            </div>

        </aside>

        <!-- ========================== MAIN ========================== -->
        <main class="main">

            <!-- Welcome panel -->
            <div class="op-panel active" id="op-welcome">
                <div class="welcome-panel">
                    <div class="icon"><i class="bi bi-book-half"></i></div>
                    <h2 data-i18n="welcome_title">AWS Location Service Documentation</h2>
                    <p data-i18n="welcome_desc">Pilih operation di sidebar kiri untuk lihat detail endpoint, request body, response shape, dan perbandingan v0 ↔ v2.</p>
                    <p class="text-muted small" data-i18n="welcome_note">
                        Default provider di ap-southeast-1 = GrabMaps. Dokumentasi ini fokus untuk integrasi API.
                    </p>

                    <!-- Region caveats — important things to know upfront -->
                    <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;margin-top:24px;text-align:left;max-width:720px;margin-left:auto;margin-right:auto;">
                        <div style="font-weight:700;color:#92400e;margin-bottom:8px;font-size:0.88rem;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <span data-i18n="region_caveats_title">Region Caveats — ap-southeast-1 (GrabMaps)</span>
                        </div>
                        <ul style="font-size:0.78rem;color:#78350f;line-height:1.7;margin:0;padding-left:20px;">
                            <li data-i18n-html="caveat_styles">Map styles: hanya <code>Standard</code> & <code>Monochrome</code> (Hybrid/Satellite ditolak)</li>
                            <li data-i18n-html="caveat_traffic">Live <code>Traffic</code> parameter: ditolak. <code>DepartureTime</code> tetap honored via pola traffic historis per jam</li>
                            <li data-i18n-html="caveat_avoid">Avoid options yang valid: <code>TollRoads</code>, <code>Ferries</code>, <code>ControlledAccessHighways</code> (DirtRoads/Tunnels/UTurns ditolak)</li>
                            <li data-i18n-html="caveat_passthrough">Waypoints: <code>PassThrough</code> tidak didukung — omit field-nya</li>
                            <li data-i18n-html="caveat_tolls">Routes return <code>Tolls</code> dan <code>MajorRoadLabels</code> — tapi AWS jarang populate field ini di SEA</li>
                            <li data-i18n-html="caveat_suggest_pos">Suggest tidak return <code>Position</code> by default — tambah <code>AdditionalFeatures: ['Core']</code></li>
                            <li data-i18n-html="caveat_places_extra">GetPlace: <code>Contacts</code>, <code>OpeningHours</code>, <code>Brand</code>, <code>FoodTypes</code> jarang populated di SEA</li>
                            <li data-i18n-html="caveat_places_af">Places <code>AdditionalFeatures</code>: hanya <code>TimeZone</code> yang dilayani — <code>Contact</code>, <code>Access</code>, <code>Phonemes</code> membalas 400</li>
                            <li data-i18n-html="caveat_places_unavail">Ditolak 400 di semua operasi Places: <code>PoliticalView</code>, <code>NextToken</code>, <code>MaxQueryRefinements</code>. <code>QueryId</code> tidak bisa didapat karena Suggest hanya mengembalikan item bertipe Place</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Shared "Coming Soon" panel (untuk operations yang belum tersedia di region) -->
            <div class="op-panel" id="op-coming-soon">
                <div class="welcome-panel">
                    <div class="icon" style="color:#f59e0b;"><i class="bi bi-hourglass-split"></i></div>
                    <h2><span id="comingSoonTitle">Operation</span></h2>
                    <p style="margin-bottom:18px;">
                        <span style="display:inline-block;background:linear-gradient(90deg,#fbbf24,#f59e0b);color:#fff;padding:4px 14px;border-radius:999px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;" data-i18n="soon_pill">⏳ Coming Soon</span>
                    </p>
                    <p data-i18n="soon_main">Operation ini belum tersedia di API key kamu untuk region ap-southeast-1.</p>
                    <p class="text-muted small" data-i18n="soon_note">
                        Dokumentasi detail akan ditambahkan setelah AWS rilis action ini di region kamu.
                        Sementara, cek alternatif yang sudah available di sidebar (dot hijau).
                    </p>
                </div>
            </div>

            {{-- =============================================================== --}}
            {{-- ROUTES OPERATIONS                                              --}}
            {{-- =============================================================== --}}

            <!-- CalculateRoutes -->
            <div class="op-panel" id="op-routes-calculate-routes">
                <div class="breadcrumb-mini">Routes V2 / CalculateRoutes</div>
                <h1>CalculateRoutes</h1>
                <p class="op-desc" data-i18n="cr_desc">Hitung rute dari Origin ke Destination, dengan opsi waypoints, mode kendaraan, hindari toll/ferry, dan turn-by-turn instructions.</p>


                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://routes.geo.{region}.amazonaws.com/v2/routes?key=...</span></div>


                        {{-- Empat tab seperti operasi Places: perakit + hasil, lalu rujukan
                             request, respons, dan error. --}}
                        <div class="op-tabs" role="tablist">
                            <button class="op-tab-btn is-on" data-tab="live" type="button"><i class="bi bi-play-circle"></i> <span data-i18n="tab_live">Live try</span></button>
                            <button class="op-tab-btn" data-tab="request" type="button"><i class="bi bi-arrow-up-right"></i> <span data-i18n="tab_request">Request</span></button>
                            <button class="op-tab-btn" data-tab="response" type="button"><i class="bi bi-arrow-down-left"></i> <span data-i18n="tab_response">Respons</span></button>
                            <button class="op-tab-btn" data-tab="error" type="button"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="tab_error">Error</span></button>
                        </div>

                        <div class="op-tab is-on" data-tab="live">
                            <div data-builder="routes-calculate-routes"></div>

                            {{-- Mesin Try It: tersembunyi, dipakai builder untuk mengirim. --}}
                            <div class="tryit-engine" hidden>
                                <span class="json-status ok" id="cr-json-status">VALID</span>
                                <button class="btn-copy" id="cr-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                                <div class="try-it-url">
                                    <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://routes.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/routes</span></div>
                                </div>
                                <textarea class="json-editor" id="cr-req-preview" spellcheck="false"></textarea>
                                <button class="btn-send" id="cr-run" type="button"><span data-i18n="btn_send">Send Request</span></button>
                                <span id="cr-spinner"></span>
                            </div>

                            <div class="doc-section-h"><span class="ic orange"><i class="bi bi-broadcast"></i></span> <span data-i18n="bld_result">Hasil dari AWS</span></div>
                            <div class="resp-bar">
                                <span style="font-weight:700;color:var(--text-primary);">Response</span>
                                <span class="status-pill idle" id="cr-status">— idle —</span>
                                <span class="meta" id="cr-meta"></span>
                            </div>
                            <div id="cr-resp" class="resp-body empty" data-i18n="bld_resp_idle">Tekan "Kirim ke AWS" di perakit request untuk melihat balasan aslinya.</div>
                        </div>

                        <div class="op-tab" data-tab="request">
                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                            <pre><code class="language-json">{
      "Origin": [ number, number ],
      "Destination": [ number, number ],
      "Waypoints": [ { "Position": [ number, number ], "StopDuration": number } ]   // StopDuration & PassThrough: 400 di region ini,
      "TravelMode": "Car" | "Scooter" | "Pedestrian",   // tier: Core
      "TravelStepType": "Default" | "TurnByTurn",
      "LegGeometryFormat": "Simple" | "FlexiblePolyline",
      "InstructionsMeasurementSystem": "Metric" | "Imperial",
      "Locale": "string",
      "Avoid": {
        "TollRoads": boolean,
        "Ferries": boolean,
        "ControlledAccessHighways": boolean
      },
      "DepartureTime": "string",
      "OptimizeRoutingFor": "FastestRoute" | "ShortestRoute",
      "MaxAlternatives": 0..5
    }</code></pre>
                            <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_routes_live">Hanya <code>TravelMode</code> yang memindahkan keranjang harga: <code>Car</code> dan <code>Pedestrian</code> masuk <b>Core</b>, <code>Scooter</code> masuk <b>Advanced</b> — tapi untuk pelanggan GrabMaps di region ini ikut dihitung <b>Core</b>. Pemicu <b>Premium</b> (tol dan <code>Intermodal</code>) tidak bisa dipanggil dari <code>ap-southeast-1</code>.</span></p>

                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>Origin</code></td>
                                        <td><span class="type-tag">[lng, lat]</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td data-i18n="cr_p_origin">Starting point</td>
                                    </tr>
                                    <tr>
                                        <td><code>Destination</code></td>
                                        <td><span class="type-tag">[lng, lat]</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td data-i18n="cr_p_dest">End point</td>
                                    </tr>
                                    <tr>
                                        <td><code>Waypoints</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td>—</td>
                                        <td data-i18n="cr_p_wp">Max 23 (excluding Origin &amp; Destination)</td>
                                    </tr>
                                    <tr>
                                        <td><code>TravelMode</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_travel_mode_v2">Default: Car. v0→v2 mapping: Motorcycle→Scooter, Walking→Pedestrian</td>
                                    </tr>
                                    <tr>
                                        <td><code>LegGeometryFormat</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td>—</td>
                                        <td><code>Simple</code> = LineString array, <code>FlexiblePolyline</code> = encoded</td>
                                    </tr>
                                    <tr>
                                        <td><code>Avoid.TollRoads</code></td>
                                        <td><span class="type-tag">boolean</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_no_pedestrian">Not supported for Pedestrian</td>
                                    </tr>
                                    <tr>
                                        <td><code>DepartureTime</code></td>
                                        <td><span class="type-tag">ISO 8601</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_excl_arrival">Mutually exclusive with ArrivalTime</td>
                                    </tr>
                                    <tr>
                                        <td><code>OptimizeRoutingFor</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_default_fastest">Default: FastestRoute</td>
                                    </tr>
                                    <tr>
                                        <td><code>MaxAlternatives</code></td>
                                        <td><span class="type-tag">0–6</span></td>
                                        <td>—</td>
                                        <td data-i18n="cr_n_alternatives">Request alternative routes. Response <code>Routes[]</code> = primary + alternatives. Actual count depends on geography (kadang AWS return lebih sedikit dari yang diminta).</td>
                                    </tr>
                                    <tr>
                                        <td><code>TravelStepType</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td>—</td>
                                        <td data-i18n="cr_p_tst">Set ke <code>TurnByTurn</code> untuk dapat full turn-by-turn instructions (<code>VehicleLegDetails.TravelSteps[]</code> dengan Type/SteeringDirection/CurrentRoad/NextRoad).</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="alert-mini warn" style="margin-top:14px;">
                                ⚠️ <span data-i18n-html="cr_region_caveats"><b>Region caveats (ap-southeast-1):</b> <code>Avoid.DirtRoads</code>, <code>Avoid.Tunnels</code>, <code>Avoid.UTurns</code> ditolak dengan FieldValidationFailed. Hanya <code>TollRoads</code>, <code>Ferries</code>, <code>ControlledAccessHighways</code> yang valid. Live <code>Traffic</code> parameter juga ditolak — <code>DepartureTime</code> akan honored lewat pola traffic historis per jam.</span>
                            </div>
                        </div>

                        <div class="op-tab" data-tab="response">
                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
                            <pre><code class="language-json">{
      "PricingBucket": "string",
      "LegGeometryFormat": "string",
      "Notices": [...],
      "Routes": [
        {
          "Summary": { "Distance": number, "Duration": number },
          "Legs": [
            {
              "Distance": number,
              "Duration": number,
              "Geometry": { "LineString": [ [lng, lat], ... ] },
              "TravelMode": "string",
              "Type": "Vehicle" | "Pedestrian" | "Ferry",
              "VehicleLegDetails": {
                "TravelSteps": [ { "Distance": number, "Duration": number, "Instruction": "string" } ]
              }
            }
          ]
        }
      ]
    }</code></pre>

                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>Routes[0].Summary.Distance</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td data-i18n-html="r_meter_not_km"><strong>Meters</strong> (not km like v0)</td>
                                    </tr>
                                    <tr>
                                        <td><code>Routes[0].Summary.Duration</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td data-i18n-html="r_seconds_renamed">Seconds (previously <code>DurationSeconds</code>)</td>
                                    </tr>
                                    <tr>
                                        <td><code>Routes[0].Legs[].Geometry.LineString</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td data-i18n="r_linestring">Array of [lng, lat] for MapLibre LineString</td>
                                    </tr>
                                    <tr>
                                        <td><code>Routes[0].Legs[].Type</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td data-i18n="r_legtype">Vehicle / Pedestrian / Ferry</td>
                                    </tr>
                                    <tr>
                                        <td><code>Routes[0].Legs[].VehicleLegDetails.TravelSteps</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td data-i18n="r_steps">Turn-by-turn instructions (when TravelStepType=TurnByTurn)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="error">
                            <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="err_status">Status</th>
                                        <th data-i18n="err_trigger">Trigger</th>
                                        <th data-i18n="err_message">AWS Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="cr_err_origin">Tanpa <code>Origin</code> atau <code>Destination</code></td>
                                        <td><em>"Origin/Destination is required"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="cr_err_pedestrian"><code>Avoid.TollRoads</code> dengan <code>TravelMode: Pedestrian</code></td>
                                        <td><em>"TollRoads not supported for Pedestrian"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="cr_err_waypoints">Waypoints &gt; 23</td>
                                        <td><em>"...less than or equal to 23"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="cr_err_time">Kirim <code>DepartureTime</code> + <code>ArrivalTime</code> bareng</td>
                                        <td><em>"Only one of DepartureTime/ArrivalTime allowed"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n="cr_err_unreach">Origin/Destination tidak bisa di-reach (mis. tengah laut)</td>
                                        <td><em>"No route found"</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <script>
                            AWSAPI_TryIt.init({
                                prefix: 'cr',
                                panelId: 'op-routes-calculate-routes',
                                proxy: '/api/v2/routes/calculate',
                                defaultPreset: 'car',
                                // Custom meta: tampilin distance/duration dari Routes[0].Summary
                                metaFormatter: (data, ms, ok) => {
                                    const route = (data.Routes || [])[0];
                                    return ok && route ?
                                        `<b>${ms}ms</b> · <b>${(route.Summary.Distance/1000).toFixed(2)} km</b> · <b>${Math.round(route.Summary.Duration/60)} min</b>` :
                                        `<b>${ms}ms</b> · error`;
                                },
                                presets: {
                                    car: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        TravelMode: 'Car',
                                        LegGeometryFormat: 'Simple',
                                        InstructionsMeasurementSystem: 'Metric',
                                        Locale: 'id'
                                    },
                                    scooter: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        TravelMode: 'Scooter',
                                        LegGeometryFormat: 'Simple',
                                        Locale: 'id'
                                    },
                                    pedestrian: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        TravelMode: 'Pedestrian',
                                        LegGeometryFormat: 'Simple',
                                        Locale: 'id'
                                    },
                                    waypoints: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        Waypoints: [{
                                            Position: [106.8410, -6.1900]
                                        }, {
                                            Position: [106.8350, -6.1820]
                                        }],
                                        TravelMode: 'Car',
                                        LegGeometryFormat: 'Simple',
                                        Locale: 'id'
                                    },
                                    alternatives: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        TravelMode: 'Car',
                                        LegGeometryFormat: 'Simple',
                                        Locale: 'id',
                                        MaxAlternatives: 2
                                    },
                                    turnbyturn: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        TravelMode: 'Car',
                                        LegGeometryFormat: 'Simple',
                                        InstructionsMeasurementSystem: 'Metric',
                                        Locale: 'id',
                                        TravelStepType: 'TurnByTurn'
                                    },
                                    full: {
                                        Origin: [106.8456, -6.2088],
                                        Destination: [106.8270, -6.1751],
                                        TravelMode: 'Car',
                                        LegGeometryFormat: 'Simple',
                                        InstructionsMeasurementSystem: 'Metric',
                                        Locale: 'id',
                                        Avoid: {
                                            TollRoads: true,
                                            Ferries: false,
                                            ControlledAccessHighways: false
                                        },
                                        OptimizeRoutingFor: 'FastestRoute',
                                        TravelStepType: 'TurnByTurn',
                                        MaxAlternatives: 2
                                    }
                                }
                            });
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/routes/v0/calculators/{Calc}/calculate/route?key=...</span></div>
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
                        <pre><code class="language-json">{
  "DeparturePosition": [106.84, -6.20],
  "DestinationPosition": [106.85, -6.24],
  "WaypointPositions": [[106.846, -6.21]],
  "TravelMode": "Car",
  "DistanceUnit": "Kilometers",
  "IncludeLegGeometry": true,
  "AvoidTolls": true,
  "DepartNow": true
}</code></pre>
                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response</div>
                        <pre><code class="language-json">{
  "Summary": { "Distance": 5.2, "DurationSeconds": 720 },
  "Legs": [{ "Distance": 5.2, "DurationSeconds": 720,
             "Geometry": { "LineString": [[106.84,-6.20], ...] } }]
}</code></pre>
                        <div class="alert-mini warn" data-i18n-html="cr_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li><code>DeparturePosition</code> → <code>Origin</code>, <code>DestinationPosition</code> → <code>Destination</code></li>
                                <li><code>WaypointPositions: [[lng,lat]]</code> → <code>Waypoints: [{ Position: [lng,lat] }]</code></li>
                                <li><code>AvoidTolls: bool</code> → <code>Avoid: { TollRoads: bool }</code> (nested)</li>
                                <li>TravelMode: <code>Motorcycle</code> → <code>Scooter</code>, <code>Walking</code> → <code>Pedestrian</code></li>
                                <li>Distance v0 dalam <strong>kilometer</strong>, v2 <strong>meter</strong></li>
                                <li><code>DurationSeconds</code> → <code>Duration</code></li>
                                <li>Response wrapper: v0 langsung Summary/Legs, v2 ada <code>Routes[0]</code> array</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- CalculateRouteMatrix -->
            <div class="op-panel" id="op-routes-calculate-route-matrix">
                <div class="breadcrumb-mini">Routes V2 / CalculateRouteMatrix</div>
                <h1>CalculateRouteMatrix</h1>
                <p class="op-desc" data-i18n="crm_desc">Hitung jarak &amp; waktu untuk semua kombinasi origin × destination — efisien untuk "find nearest" use case.</p>


                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://routes.geo.{region}.amazonaws.com/v2/route-matrix?key=...</span></div>


                        {{-- Empat tab seperti operasi Places: perakit + hasil, lalu rujukan
                             request, respons, dan error. --}}
                        <div class="op-tabs" role="tablist">
                            <button class="op-tab-btn is-on" data-tab="live" type="button"><i class="bi bi-play-circle"></i> <span data-i18n="tab_live">Live try</span></button>
                            <button class="op-tab-btn" data-tab="request" type="button"><i class="bi bi-arrow-up-right"></i> <span data-i18n="tab_request">Request</span></button>
                            <button class="op-tab-btn" data-tab="response" type="button"><i class="bi bi-arrow-down-left"></i> <span data-i18n="tab_response">Respons</span></button>
                            <button class="op-tab-btn" data-tab="error" type="button"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="tab_error">Error</span></button>
                        </div>

                        <div class="op-tab is-on" data-tab="live">
                            <div data-builder="routes-calculate-route-matrix"></div>

                            {{-- Mesin Try It: tersembunyi, dipakai builder untuk mengirim. --}}
                            <div class="tryit-engine" hidden>
                                <span class="json-status ok" id="crm-json-status">VALID</span>
                                <button class="btn-copy" id="crm-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                                <div class="try-it-url">
                                    <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://routes.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/route-matrix</span></div>
                                </div>
                                <textarea class="json-editor" id="crm-req-preview" spellcheck="false"></textarea>
                                <button class="btn-send" id="crm-run" type="button"><span data-i18n="btn_send">Send Request</span></button>
                                <span id="crm-spinner"></span>
                            </div>

                            <div class="doc-section-h"><span class="ic orange"><i class="bi bi-broadcast"></i></span> <span data-i18n="bld_result">Hasil dari AWS</span></div>
                            <div class="resp-bar">
                                <span style="font-weight:700;color:var(--text-primary);">Response</span>
                                <span class="status-pill idle" id="crm-status">— idle —</span>
                                <span class="meta" id="crm-meta"></span>
                            </div>
                            <div id="crm-resp" class="resp-body empty" data-i18n="bld_resp_idle">Tekan "Kirim ke AWS" di perakit request untuk melihat balasan aslinya.</div>
                        </div>

                        <div class="op-tab" data-tab="request">
                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                            <pre><code class="language-json">{
      "Origins": [ { "Position": [ number, number ] } ],
      "Destinations": [ { "Position": [ number, number ] } ],
      "TravelMode": "Car" | "Scooter" | "Pedestrian",   // tier: Core
      "RoutingBoundary": {
        "Unbounded": true,
        "Geometry": { "AutoCircle": { "Margin": number, "MaxRadius": number } }
      },
      "Avoid": {
        "TollRoads": boolean,
        "Ferries": boolean
      },
      "DepartureTime": "string",
      "OptimizeRoutingFor": "FastestRoute" | "ShortestRoute"
    }</code></pre>
                            <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_routes_live">Hanya <code>TravelMode</code> yang memindahkan keranjang harga: <code>Car</code> dan <code>Pedestrian</code> masuk <b>Core</b>, <code>Scooter</code> masuk <b>Advanced</b> — tapi untuk pelanggan GrabMaps di region ini ikut dihitung <b>Core</b>. Pemicu <b>Premium</b> (tol dan <code>Intermodal</code>) tidak bisa dipanggil dari <code>ap-southeast-1</code>.</span></p>

                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>Origins</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td>Array of <code>{ Position: [lng,lat] }</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>Destinations</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td>Array of <code>{ Position: [lng,lat] }</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>RoutingBoundary</code></td>
                                        <td><span class="type-tag">object</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td>WAJIB di v2! <code>{ Unbounded: true }</code> untuk perilaku v0.</td>
                                    </tr>
                                    <tr>
                                        <td><code>TravelMode</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_default_car">Default: Car</td>
                                    </tr>
                                    <tr>
                                        <td><code>Avoid.TollRoads</code></td>
                                        <td><span class="type-tag">boolean</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_no_pedestrian">Not allowed for Pedestrian</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="response">
                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
                            <pre><code class="language-json">{
      "PricingBucket": "string",
      "RouteMatrix": [
        [
          { "Distance": number, "Duration": number, "Error": null }
        ]
      ],
      "ErrorCount": number
    }</code></pre>

                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>RouteMatrix[i][j].Distance</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td><strong>Meter</strong> (v0: km)</td>
                                    </tr>
                                    <tr>
                                        <td><code>RouteMatrix[i][j].Duration</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td>Detik (v0: <code>DurationSeconds</code>)</td>
                                    </tr>
                                    <tr>
                                        <td><code>RouteMatrix[i][j].Error</code></td>
                                        <td><span class="type-tag">object|null</span></td>
                                        <td data-i18n-html="r_cell_error">Per-cell error (e.g. unreachable). <code>null</code> if OK.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="error">
                            <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="err_status">Status</th>
                                        <th data-i18n="err_trigger">Trigger</th>
                                        <th data-i18n="err_message">AWS Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="crm_err_boundary">Tanpa <code>RoutingBoundary</code></td>
                                        <td><em>"RoutingBoundary is required"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n="crm_err_cells">Origins × Destinations &gt; 700</td>
                                        <td><em>"Too many cells: max 700"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n="crm_err_pos">Position kosong di salah satu Origins/Destinations</td>
                                        <td><em>"Position is required"</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <script>
                            AWSAPI_TryIt.init({
                                prefix: 'crm',
                                panelId: 'op-routes-calculate-route-matrix',
                                proxy: '/api/routes/matrix',
                                defaultPreset: 'v0_basic',
                                // Custom meta: tampilin total cells dari matrix
                                metaFormatter: (data, ms, ok) => {
                                    const m = data.RouteMatrix || [];
                                    const cells = m.reduce((a, row) => a + row.length, 0);
                                    return ok ? `<b>${ms}ms</b> · <b>${cells}</b> cells` : `<b>${ms}ms</b> · error`;
                                },
                                presets: {
                                    v2_basic: {
                                        Origins: [{
                                            Position: [106.8456, -6.2088]
                                        }],
                                        Destinations: [{
                                            Position: [106.8270, -6.1751]
                                        }, {
                                            Position: [106.8410, -6.1900]
                                        }],
                                        TravelMode: 'Car',
                                        RoutingBoundary: {
                                            Unbounded: true
                                        }
                                    },
                                    v0_basic: {
                                        DeparturePositions: [
                                            [106.8456, -6.2088]
                                        ],
                                        DestinationPositions: [
                                            [106.8270, -6.1751],
                                            [106.8410, -6.1900]
                                        ],
                                        TravelMode: 'Car',
                                        DistanceUnit: 'Kilometers'
                                    }
                                }
                            });
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/routes/v0/calculators/{Calc}/calculate/route-matrix?key=...</span></div>
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
                        <pre><code class="language-json">{
  "DeparturePositions": [[106.84, -6.20]],
  "DestinationPositions": [[106.85,-6.24], [106.86,-6.25]],
  "TravelMode": "Car",
  "DistanceUnit": "Kilometers"
}</code></pre>
                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response (km, DurationSeconds)</div>
                        <pre><code class="language-json">{
  "RouteMatrix": [[
    { "Distance": 1.2, "DurationSeconds": 240 },
    { "Distance": 2.5, "DurationSeconds": 480 }
  ]]
}</code></pre>
                        <div class="alert-mini warn" data-i18n-html="crm_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li><code>DeparturePositions: [[lng,lat]]</code> → <code>Origins: [{ Position: [lng,lat] }]</code></li>
                                <li><code>DestinationPositions: [[lng,lat]]</code> → <code>Destinations: [{ Position: [lng,lat] }]</code></li>
                                <li>v2 wajib <code>RoutingBoundary</code> (v0 tidak)</li>
                                <li>Distance: v0 km → v2 meter</li>
                                <li><code>DurationSeconds</code> → <code>Duration</code></li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- CalculateIsolines -->
            <div class="op-panel" id="op-routes-calculate-isolines">
                <div class="breadcrumb-mini">Routes V2 / CalculateIsolines</div>
                <h1>CalculateIsolines <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="iso_desc">Polygon area "reachable within X minutes" atau "within Y km" dari satu titik. Cocok untuk visualisasi service coverage.</p>

                <div class="alert-mini soon" data-i18n-html="soon_iso"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Action not listed in AWS Console permissions. Wait for AWS rollout or use the workaround below.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/isolines?key=...</span></div>

                <h4 data-i18n="label_request_body">Request body</h4>
                <pre><code class="language-json">{
  "Origin": [106.84, -6.20],
  "TravelMode": "Car",
  "Thresholds": {
    "Time": [300, 600, 900]
  },
  "OptimizeIsolineFor": "FastestRoute"
}</code></pre>
                <p data-i18n-html="iso_thresholds_note">Bisa juga pakai <code>Thresholds.Distance</code> (meter) atau kedua-duanya.</p>

                <h4 data-i18n="label_response">Response</h4>
                <pre><code class="language-json">{
  "Isolines": [{
    "TimeThreshold": 300,
    "Geometries": [{
      "Polygon": [[ [106.83,-6.19], ... ]]
    }]
  }]
}</code></pre>
                <div class="alert-mini info" data-i18n-html="iso_use_case">
                    💡 Use case: visualize <strong>"stops reachable within 10 minutes on foot"</strong> with polygon overlay on MapLibre.
                </div>
            </div>

            <!-- OptimizeWaypoints -->
            <div class="op-panel" id="op-routes-optimize-waypoints">
                <div class="breadcrumb-mini">Routes V2 / OptimizeWaypoints</div>
                <h1>OptimizeWaypoints <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="opt_desc">TSP solver — kasih AWS list of waypoints, dia kembalikan urutan optimal (ngirit jarak/waktu).</p>

                <div class="alert-mini soon" data-i18n-html="soon_opt"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. For this feature, implement nearest-neighbor TSP yourself in JS or use a library.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/optimize-waypoints?key=...</span></div>

                <h4 data-i18n="label_request_body">Request body</h4>
                <pre><code class="language-json">{
  "Origin": [106.84, -6.20],
  "Destination": [106.92, -6.30],
  "Waypoints": [
    { "Id": "stop1", "Position": [106.85,-6.21] },
    { "Id": "stop2", "Position": [106.90,-6.22] },
    { "Id": "stop3", "Position": [106.87,-6.25] }
  ],
  "TravelMode": "Car",
  "OptimizeSequencingFor": "FastestRoute"
}</code></pre>

                <h4 data-i18n="label_response">Response</h4>
                <pre><code class="language-json">{
  "OptimizedWaypoints": [
    { "Id": "stop1", "Position": [106.85,-6.21] },
    { "Id": "stop3", "Position": [106.87,-6.25] },
    { "Id": "stop2", "Position": [106.90,-6.22] }
  ],
  "Distance": 12500,
  "Duration": 1850
}</code></pre>
                <div class="alert-mini success">
                    ✅ <span data-i18n="ow_tsp_note">Sebelumnya kamu harus implement TSP / nearest-neighbor sendiri di JS. Sekarang AWS yang hitungkan.</span>
                </div>
            </div>

            <!-- SnapToRoads -->
            <div class="op-panel" id="op-routes-snap-to-roads">
                <div class="breadcrumb-mini">Routes V2 / SnapToRoads</div>
                <h1>SnapToRoads <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="snap_desc">Snap GPS trace yang noisy ke jalan terdekat. Berguna untuk clean trip log GPS dari kendaraan.</p>

                <div class="alert-mini soon" data-i18n-html="soon_snap"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Not yet rolled out for GrabMaps provider.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/snap-to-roads?key=...</span></div>

                <h4 data-i18n="label_request_body">Request body</h4>
                <pre><code class="language-json">{
  "TracePoints": [
    { "Position": [106.840, -6.200] },
    { "Position": [106.842, -6.201] },
    { "Position": [106.845, -6.203] }
  ],
  "TravelMode": "Car",
  "SnappedGeometryFormat": "Simple"
}</code></pre>

                <h4 data-i18n="label_response">Response</h4>
                <pre><code class="language-json">{
  "SnappedGeometry": {
    "LineString": [[106.840,-6.200], [106.843,-6.201], ...]
  },
  "SnappedTracePoints": [
    { "OriginalPosition": [106.840,-6.200], "SnappedPosition": [106.840,-6.200] }
  ]
}</code></pre>
                <div class="alert-mini info" data-i18n="snap_use_case">
                    💡 Pair with ride-hailing trip recording to get clean trip lines (without GPS-noise zigzags).
                </div>
            </div>

            {{-- =============================================================== --}}
            {{-- MAPS OPERATIONS                                                --}}
            {{-- =============================================================== --}}

            <!-- GetStyleDescriptor -->
            <div class="op-panel" id="op-maps-get-style-descriptor">
                <div class="breadcrumb-mini">Maps V2 / GetStyleDescriptor</div>
                <h1>GetStyleDescriptor</h1>
                <p class="op-desc" data-i18n="gsd_desc">Return MapLibre style spec JSON. URL ini langsung ditaroh di property <code>style</code> waktu inisialisasi MapLibre map.</p>

                <div class="ver-tabs">
                    <button data-version="v0" data-i18n="ver_v0">v0 Legacy</button>
                    <button data-version="v2" class="active" data-i18n="ver_v2">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/styles/{Style}/descriptor?key=...</span></div>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Query Parameters</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th data-i18n="th_param">Param</th>
                                    <th data-i18n="th_values">Values</th>
                                    <th data-i18n="th_note">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{Style}</code><span class="req">PATH</span></td>
                                    <td><span class="type-tag">enum</span> Standard | Monochrome | Hybrid | Satellite</td>
                                    <td data-i18n-html="gsd_p_style_note">In <code>ap-southeast-1</code>: only Standard &amp; Monochrome (GrabMaps provider)</td>
                                </tr>
                                <tr>
                                    <td><code>key</code><span class="req">REQ</span></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td data-i18n="note_api_key">API key</td>
                                </tr>
                                <tr>
                                    <td><code>color-scheme</code></td>
                                    <td><span class="type-tag">enum</span> Light | Dark</td>
                                    <td data-i18n="note_only_std_mono">Only for Standard / Monochrome</td>
                                </tr>
                                <tr>
                                    <td><code>political-view</code></td>
                                    <td><span class="type-tag">string</span> ISO-3</td>
                                    <td data-i18n="note_iso3_examples">IDN, MYS, ARG, MAR, etc.</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                        <div class="rules-grid">
                            <div class="rule-card combo">
                                <div class="rule-header"><span class="ic"><i class="bi bi-puzzle-fill"></i></span> <span data-i18n="gsd_rule_header">Style + Color compatibility</span></div>
                                <div class="field-list" data-i18n-html="gsd_rule_fields"><code>Standard</code> + <code>Light/Dark</code> ✓<br><code>Monochrome</code> + <code>Light/Dark</code> ✓<br><code>Hybrid</code>/<code>Satellite</code> + <code>color-scheme</code> ❌</div>
                                <div class="rule-note" data-i18n="gsd_rule_note">Raster styles (Hybrid/Satellite) don't accept color-scheme — sending it returns error 400.</div>
                            </div>
                        </div>

                        <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                        <table class="error-table">
                            <thead>
                                <tr>
                                    <th data-i18n="err_status">Status</th>
                                    <th data-i18n="err_trigger">Trigger</th>
                                    <th data-i18n="err_message">AWS Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n="gsd_e_style">Style not available in region (e.g. Satellite in ap-southeast-1)</td>
                                    <td><em>"Satellite is not a supported map style"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="gsd_e_color"><code>color-scheme</code> used on Hybrid/Satellite</td>
                                    <td><em>"color-scheme not applicable"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td data-i18n-html="gsd_e_perm">API Key lacks <code>geo-maps:GetStyleDescriptor</code></td>
                                    <td><em>"explicit deny"</em></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_json">Response (JSON)</span></div>
                        <pre><code class="language-json">{
  "version": 8,
  "name": "Standard",
  "sources": {
    "tiles": { "type": "vector", "tiles": [ "https://maps.geo.../v2/tiles/Standard/Default/Default/{z}/{x}/{y}?key=..." ] }
  },
  "sprite": "https://maps.geo.../v2/sprites/Standard/Default/Default",
  "glyphs": "https://maps.geo.../v2/glyphs/Standard/{fontstack}/{range}?key=...",
  "layers": [ ... ]
}</code></pre>
                        <p style="font-size:0.85rem;color:var(--text-muted);" data-i18n="gsd_response_note">Style descriptor is a complete MapLibre recipe — it contains URLs for GetTile / GetGlyphs / GetSprites. MapLibre auto-fetches all three.</p>

                        <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                        <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gsd_try_hint">
                            💡 Pick style + color + political-view → the MapLibre map below auto re-renders with the new style descriptor.
                        </div>

                        <div class="preset-row">
                            <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                            <button class="preset-btn" data-style="Standard" data-color="Light">☀️ Standard Light</button>
                            <button class="preset-btn" data-style="Standard" data-color="Dark">🌙 Standard Dark</button>
                            <button class="preset-btn" data-style="Monochrome" data-color="Light">⚪ Monochrome Light</button>
                            <button class="preset-btn" data-style="Monochrome" data-color="Dark">⚫ Monochrome Dark</button>
                        </div>

                        <div id="gsd-map" style="width:100%;height:380px;border-radius:10px;border:1px solid var(--border-light);margin-bottom:12px;"></div>
                        <div class="resp-bar">
                            <span style="font-weight:700;" data-i18n="label_url">URL</span>
                            <code id="gsd-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                            <button class="btn-copy" id="gsd-copy-url" style="background:#e2e8f0;color:#334155;border:1px solid #cbd5e1;" data-i18n="btn_copy">📋 Copy</button>
                        </div>

                        <script>
                            (function() {
                                const REGION = "{{ env('AWS_REGION') }}";
                                const API_KEY = "{{ env('AWS_API_KEY') }}";
                                let map = null;
                                let curStyle = 'Standard',
                                    curColor = 'Light';

                                function buildUrl() {
                                    const params = ['key=' + API_KEY, 'color-scheme=' + curColor];
                                    return `https://maps.geo.${REGION}.amazonaws.com/v2/styles/${curStyle}/descriptor?` + params.join('&');
                                }

                                function render() {
                                    const url = buildUrl();
                                    document.getElementById('gsd-url').textContent = url.replace(API_KEY, '***');
                                    if (!map) {
                                        map = new maplibregl.Map({
                                            container: 'gsd-map',
                                            style: url,
                                            center: [106.8456, -6.2088],
                                            zoom: 11
                                        });
                                    } else {
                                        map.setStyle(url);
                                    }
                                }
                                document.querySelectorAll('#op-maps-get-style-descriptor .preset-btn').forEach(b => {
                                    b.addEventListener('click', () => {
                                        curStyle = b.dataset.style;
                                        curColor = b.dataset.color;
                                        render();
                                    });
                                });
                                document.getElementById('gsd-copy-url').addEventListener('click', e => {
                                    navigator.clipboard.writeText(buildUrl().replace(API_KEY, '***'));
                                    e.currentTarget.innerHTML = '✓ Copied';
                                    setTimeout(() => e.currentTarget.innerHTML = '📋 Copy', 1500);
                                });
                                // Render saat panel pertama kali ditampilkan
                                const observer = new MutationObserver(() => {
                                    if (document.getElementById('op-maps-get-style-descriptor').classList.contains('active') && !map) {
                                        render();
                                    }
                                });
                                observer.observe(document.getElementById('op-maps-get-style-descriptor'), {
                                    attributes: true
                                });
                                if (document.getElementById('op-maps-get-style-descriptor').classList.contains('active')) render();
                            })();
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/style-descriptor?key=...</span></div>
                        <div class="alert-mini warn" data-i18n-html="gsd_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Pakai <code>{MapName}</code> custom resource (harus dibuat dulu di Console)</li>
                                <li>Tidak ada <code>color-scheme</code> / <code>political-view</code> param</li>
                                <li>Provider lock per resource — gak bisa switch style runtime</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetTile -->
            <div class="op-panel" id="op-maps-get-tile">
                <div class="breadcrumb-mini">Maps V2 / GetTile</div>
                <h1>GetTile</h1>
                <p class="op-desc" data-i18n="gt_desc">Vector / raster tile per koordinat z/x/y. URL pattern ini ada di field <code>tiles</code> dalam style descriptor — biasanya gak perlu di-call manual.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/tiles/{Tileset}/{z}/{x}/{y}?key=...</span></div>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                        <pre><code class="language-bash">GET https://maps.geo.{region}.amazonaws.com/v2/tiles/vector.basemap/{z}/{x}/{y}
      ?key=&lt;API_KEY&gt;</code></pre>
                        <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_tile">Maps cuma punya satu harga per 1.000 tile — tidak ada tier di operasi ini. Nama tileset diambil dari field <code>tiles</code> di style descriptor.</span></p>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Path / Query Parameters</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Param</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{Tileset}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td data-i18n-html="gt_p_tileset"><code>vector.basemap</code> — nama tileset, diambil dari field <code>tiles</code> di style descriptor. Gaya dan color scheme ditentukan descriptor-nya, bukan URL tile ini.</td>
                                </tr>
                                <tr>
                                    <td><code>{z}/{x}/{y}</code></td>
                                    <td><span class="type-tag">path int</span></td>
                                    <td data-i18n="gt_p_zxy">Tile coordinate (z 0-22, x/y per zoom level)</td>
                                </tr>
                                <tr>
                                    <td><code>key</code></td>
                                    <td><span class="type-tag">query</span></td>
                                    <td data-i18n="note_api_key">API key</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                        <table class="error-table">
                            <thead>
                                <tr>
                                    <th data-i18n="err_status">Status</th>
                                    <th data-i18n="err_trigger">Trigger</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n="gt_e_400_short">z/x/y out of valid range</td>
                                    <td data-i18n-html="gt_e_400_note">E.g. y &gt; 2^z - 1</td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">404</span></td>
                                    <td data-i18n="gt_e_404_short">Tile not available in this area</td>
                                    <td data-i18n="gt_e_404_note">E.g. coordinate outside provider coverage</td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td data-i18n-html="gt_e_perm">API Key lacks <code>geo-maps:GetTile</code></td>
                                    <td data-i18n="note_perm_missing">Permission missing</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Content-Type</div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Style</th>
                                    <th>Content-Type</th>
                                    <th>Format</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Standard / Monochrome</td>
                                    <td><code>application/x-protobuf</code></td>
                                    <td data-i18n="gt_r_vector">Vector tile (PBF) — rendered by MapLibre client-side</td>
                                </tr>
                                <tr>
                                    <td>Hybrid / Satellite</td>
                                    <td><code>image/png</code> atau <code>image/jpeg</code></td>
                                    <td data-i18n="gt_r_raster">Raster tile, rendered as-is</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>
                        <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gt_try_hint">
                            💡 Use the z/x/y picker to view a specific tile. Vector tile (PBF) cannot be previewed directly — use MapLibre to render it.
                        </div>

                        <div class="preset-row">
                            <span class="preset-label" title="Contoh: Jakarta pada zoom 11">z/x/y</span>
                            <input id="gt-z" type="number" value="11" min="0" max="22" style="width:60px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                            <input id="gt-x" type="number" value="1631" style="width:80px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                            <input id="gt-y" type="number" value="1059" style="width:80px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                            {{-- Tileset, bukan gaya: URL tile tidak mengenal Standard/Monochrome.
                                 Nama ini yang muncul di field `tiles` style descriptor. --}}
                            <select id="gt-tileset" style="min-width:150px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>vector.basemap</option>
                            </select>
                            <button class="preset-btn is-primary" id="gt-apply" data-i18n="gt_apply">Apply</button>
                        </div>
                        <div class="resp-bar">
                            <span style="font-weight:700;">URL</span>
                            <code id="gt-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                            <button class="btn-copy" id="gt-test" style="background:#00B14F;color:#fff;border:0;" data-i18n="gt_test">▶ Test</button>
                            <button class="btn-copy" id="gt-open" style="background:#3b82f6;color:#fff;border:0;">↗ Open</button>
                        </div>
                        <div id="gt-result" class="alert-mini" style="display:none;"></div>
                        <p style="font-size:0.78rem;color:var(--text-muted);" data-i18n-html="gt_try_note">⬆️ <strong>Test</strong> mengambil tile dan melaporkan status, tipe, serta ukurannya di sini. <strong>Open</strong> membuka URL-nya langsung — tile vektor akan terunduh sebagai berkas biner, dan Chrome memblokirnya kalau halaman ini masih diakses lewat HTTP.</p>

                        <script>
                            (function() {
                                // Read ONLY from user's My Key Inspector — env key stays server-side.
                                function resolveCreds() {
                                    const uk = window.AWSAPI_UserKey;
                                    if (uk && uk.apiKey) {
                                        return {
                                            region: uk.region || 'ap-southeast-1',
                                            apiKey: uk.apiKey
                                        };
                                    }
                                    return {
                                        region: 'ap-southeast-1',
                                        apiKey: ''
                                    };
                                }

                                function build() {
                                    const {
                                        region,
                                        apiKey
                                    } = resolveCreds();
                                    const z = document.getElementById('gt-z').value;
                                    const x = document.getElementById('gt-x').value;
                                    const y = document.getElementById('gt-y').value;
                                    // Jalur tile yang benar adalah /v2/tiles/{tileset}/{z}/{x}/{y}.
                                    // Bentuk lama /v2/tiles/{Style}/{ColorScheme}/{Variant}/... tidak
                                    // dilayani AWS dan membalas 403 "Missing Authentication Token".
                                    const s = document.getElementById('gt-tileset').value;
                                    return {
                                        url: `https://maps.geo.${region}.amazonaws.com/v2/tiles/${s}/${z}/${x}/${y}?key=${apiKey}`,
                                        hasKey: !!apiKey
                                    };
                                }

                                function refresh() {
                                    const {
                                        url,
                                        hasKey
                                    } = build();
                                    const el = document.getElementById('gt-url');
                                    const btn = document.getElementById('gt-open');
                                    // Mask displayed key (last 20 chars) but keep real URL for Open
                                    el.textContent = hasKey ?
                                        url.replace(/key=.+$/, 'key=' + '•'.repeat(6) + '…' + url.slice(-6)) :
                                        url.replace(/key=$/, 'key=(not set)');
                                    // Toggle button state
                                    if (hasKey) {
                                        btn.disabled = false;
                                        btn.style.opacity = '';
                                        btn.style.cursor = 'pointer';
                                        btn.title = 'Open tile URL in a new tab';
                                    } else {
                                        btn.disabled = true;
                                        btn.style.opacity = '0.5';
                                        btn.style.cursor = 'not-allowed';
                                        btn.title = 'Set your API Key first via the 🔑 My Key button at the top';
                                    }
                                }

                                ['gt-z', 'gt-x', 'gt-y', 'gt-tileset'].forEach(id => document.getElementById(id).addEventListener('input', refresh));
                                document.getElementById('gt-apply').addEventListener('click', refresh);
                                // Mengunduh tile lewat window.open() diblokir Chrome kalau halamannya
                                // masih HTTP ("Insecure download blocked"), dan berkas PBF-nya sendiri
                                // tidak bisa dilihat. Jadi tombol utama sekarang mengambil tile lewat
                                // fetch dan melaporkan hasilnya di halaman.
                                document.getElementById('gt-test').addEventListener('click', async () => {
                                    const { url, hasKey } = build();
                                    const box = document.getElementById('gt-result');
                                    box.style.display = 'block';

                                    if (!hasKey) {
                                        box.className = 'alert-mini danger';
                                        box.textContent = '🔒 Isi API Key dulu lewat tombol kunci di kanan atas.';
                                        return;
                                    }

                                    box.className = 'alert-mini info';
                                    box.textContent = '⏳ Mengambil tile...';
                                    const t0 = performance.now();
                                    try {
                                        const res = await fetch(url);
                                        const buf = await res.arrayBuffer();
                                        const ms = Math.round(performance.now() - t0);
                                        const kb = (buf.byteLength / 1024).toFixed(1);

                                        if (!res.ok) {
                                            box.className = 'alert-mini danger';
                                            box.innerHTML = `❌ <b>${res.status} ${res.statusText}</b> — ${new TextDecoder().decode(buf).slice(0, 160)}`;
                                            return;
                                        }

                                        // Tile di atas laut balasannya sah tapi nyaris kosong; itu
                                        // sumber kebingungan "kok hasilnya tidak ada".
                                        const kosong = buf.byteLength < 500;
                                        box.className = kosong ? 'alert-mini warn' : 'alert-mini success';
                                        box.innerHTML = `${kosong ? '⚠️' : '✅'} <b>${res.status} OK</b> · `
                                            + `${res.headers.get('content-type') || 'binary'} · <b>${kb} KB</b> · ${ms} ms`
                                            + (kosong
                                                ? ' — tile sah tapi nyaris kosong. Koordinat ini kemungkinan di atas laut atau di luar cakupan.'
                                                : ' — tile berisi data. Vector PBF perlu MapLibre untuk digambar.');
                                    } catch (e) {
                                        box.className = 'alert-mini danger';
                                        box.textContent = '❌ Permintaan gagal: ' + e.message;
                                    }
                                });

                                document.getElementById('gt-open').addEventListener('click', () => {
                                    const {
                                        url,
                                        hasKey
                                    } = build();
                                    if (!hasKey) {
                                        alert('Set your API Key first — click the 🔑 My Key button at the top of the page, paste your key, and check "Use in Try it Live".');
                                        return;
                                    }
                                    window.open(url, '_blank', 'noopener');
                                });

                                // React to My Key Inspector updates
                                window.addEventListener('AWSAPI_UserKeyChanged', refresh);
                                document.addEventListener('DOMContentLoaded', refresh);
                                refresh();
                            })();
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/tiles/{z}/{x}/{y}?key=...</span></div>
                        <div class="alert-mini warn" data-i18n-html="gt_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Path lebih sederhana: <code>{MapName}/tiles/{z}/{x}/{y}</code></li>
                                <li>Tidak ada Style/ColorScheme/Variant — provider locked di resource</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetGlyphs -->
            <div class="op-panel" id="op-maps-get-glyphs">
                <div class="breadcrumb-mini">Maps V2 / GetGlyphs</div>
                <h1>GetGlyphs</h1>
                <p class="op-desc" data-i18n="gg_desc">Font glyphs (PBF) untuk text rendering di vector tiles. Auto-fetched oleh MapLibre, gak perlu di-call manual.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/glyphs/{Style}/{fontstack}/{range}?key=...</span></div>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Path Parameters</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Param</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{Style}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td data-i18n="note_styles_v2">Standard | Monochrome</td>
                                </tr>
                                <tr>
                                    <td><code>{fontstack}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td>Nama font (mis. <code>Noto Sans Regular</code>) — URL-encoded</td>
                                </tr>
                                <tr>
                                    <td><code>{range}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td>Unicode range, 256 chars per chunk: <code>0-255</code>, <code>256-511</code>, dst.</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Content-Type</div>
                        <p><code>application/x-protobuf</code> — binary PBF berisi font glyphs. Di-decode sama MapLibre untuk render label di map.</p>

                        <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                        <table class="error-table">
                            <thead>
                                <tr>
                                    <th data-i18n="err_status">Status</th>
                                    <th data-i18n="err_trigger">Trigger</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="err-code">404</span></td>
                                    <td data-i18n="gg_e_404_short">Fontstack not available</td>
                                    <td data-i18n="gg_e_404_note">See available fonts in the style descriptor</td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n="gg_e_400_short">Invalid range</td>
                                    <td data-i18n="gg_e_400_note">Max range usually up to 65279 (basic Unicode)</td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td data-i18n="note_perm_missing">Permission missing</td>
                                    <td><code>geo-maps:GetGlyphs</code> gak di-grant</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>
                        <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gg_try_hint">
                            💡 Glyphs PBF cannot be previewed directly. Shown here: URL builder + open button to download the file.
                        </div>

                        <div class="preset-row">
                            <span class="preset-label">Style</span>
                            <select id="gg-style" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>Standard</option>
                                <option>Monochrome</option>
                            </select>
                            <span class="preset-label">Font</span>
                            <select id="gg-font" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>Noto Sans Regular</option>
                                <option>Noto Sans Bold</option>
                                <option>Noto Sans Italic</option>
                            </select>
                            <span class="preset-label">Range</span>
                            <select id="gg-range" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>0-255</option>
                                <option>256-511</option>
                                <option>4096-4351</option>
                            </select>
                        </div>
                        <div class="resp-bar">
                            <span style="font-weight:700;">URL</span>
                            <code id="gg-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                            <button class="btn-copy" id="gg-open" style="background:#3b82f6;color:#fff;border:0;">↗ Open</button>
                        </div>

                        <script>
                            (function() {
                                const REGION = "{{ env('AWS_REGION') }}";
                                const API_KEY = "{{ env('AWS_API_KEY') }}";

                                function build() {
                                    const s = document.getElementById('gg-style').value;
                                    const f = encodeURIComponent(document.getElementById('gg-font').value);
                                    const r = document.getElementById('gg-range').value;
                                    return `https://maps.geo.${REGION}.amazonaws.com/v2/glyphs/${s}/${f}/${r}?key=${API_KEY}`;
                                }

                                function refresh() {
                                    document.getElementById('gg-url').textContent = build().replace(API_KEY, '***');
                                }
                                ['gg-style', 'gg-font', 'gg-range'].forEach(id => document.getElementById(id).addEventListener('change', refresh));
                                document.getElementById('gg-open').addEventListener('click', () => window.open(build(), '_blank'));
                                refresh();
                            })();
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/glyphs/{fontstack}/{range}?key=...</span></div>
                        <div class="alert-mini warn" data-i18n-html="gg_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Path: <code>{MapName}/glyphs/{fontstack}/{range}</code> (tanpa Style)</li>
                                <li>Font set tergantung MapName resource</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetSprites -->
            <div class="op-panel" id="op-maps-get-sprites">
                <div class="breadcrumb-mini">Maps V2 / GetSprites</div>
                <h1>GetSprites</h1>
                <p class="op-desc" data-i18n="gsp_desc">Sprite sheet (PNG + JSON) untuk icon point-of-interest di map. Auto-fetched oleh MapLibre.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/sprites/{Style}/{ColorScheme}/{Variant}/{file}?key=...</span></div>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Path Parameters</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Param</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{Style}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td data-i18n="note_styles_v2">Standard | Monochrome</td>
                                </tr>
                                <tr>
                                    <td><code>{ColorScheme}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td data-i18n="note_light_dark">Light | Dark | Default</td>
                                </tr>
                                <tr>
                                    <td><code>{Variant}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td data-i18n="note_default">Default</td>
                                </tr>
                                <tr>
                                    <td><code>{file}</code></td>
                                    <td><span class="type-tag">path</span></td>
                                    <td><code>sprites.json</code> | <code>sprites.png</code> | <code>sprites@2x.json</code> | <code>sprites@2x.png</code></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Content-Type</div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Content-Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>sprites.json</code></td>
                                    <td><code>application/json</code></td>
                                    <td data-i18n="gsp_r_json">Manifest: coordinates &amp; size of each icon in the sheet</td>
                                </tr>
                                <tr>
                                    <td><code>sprites.png</code></td>
                                    <td><code>image/png</code></td>
                                    <td data-i18n="gsp_r_png">Sheet image — all icons in one PNG</td>
                                </tr>
                                <tr>
                                    <td><code>sprites@2x.png</code></td>
                                    <td><code>image/png</code></td>
                                    <td data-i18n="gsp_r_2x">@2x version for retina display</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>
                        <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gsp_try_hint">
                            💡 PNG sprites can be previewed directly. JSON manifest can be fetched to inspect structure.
                        </div>

                        <div class="preset-row">
                            <span class="preset-label">Style</span>
                            <select id="gsp-style" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>Standard</option>
                                <option>Monochrome</option>
                            </select>
                            <span class="preset-label">Color</span>
                            <select id="gsp-color" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>Light</option>
                                <option>Dark</option>
                            </select>
                            <span class="preset-label">File</span>
                            <select id="gsp-file" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                                <option>sprites.png</option>
                                <option>sprites@2x.png</option>
                                <option>sprites.json</option>
                            </select>
                        </div>
                        <div class="resp-bar">
                            <span style="font-weight:700;">URL</span>
                            <code id="gsp-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                            <button class="btn-copy" id="gsp-open" style="background:#3b82f6;color:#fff;border:0;">↗ Open</button>
                        </div>
                        <div id="gsp-preview" style="margin-top:14px;padding:14px;background:#f8fafc;border:1px solid var(--border-light);border-radius:8px;text-align:center;">
                            <img id="gsp-img" style="max-width:100%;background:#fff;border-radius:6px;" alt="Sprite preview">
                        </div>

                        <script>
                            (function() {
                                const REGION = "{{ env('AWS_REGION') }}";
                                const API_KEY = "{{ env('AWS_API_KEY') }}";

                                function build() {
                                    const s = document.getElementById('gsp-style').value;
                                    const c = document.getElementById('gsp-color').value;
                                    const f = document.getElementById('gsp-file').value;
                                    return `https://maps.geo.${REGION}.amazonaws.com/v2/sprites/${s}/${c}/Default/${f}?key=${API_KEY}`;
                                }

                                function refresh() {
                                    const url = build();
                                    document.getElementById('gsp-url').textContent = url.replace(API_KEY, '***');
                                    const file = document.getElementById('gsp-file').value;
                                    const imgEl = document.getElementById('gsp-img');
                                    if (file.endsWith('.png')) {
                                        imgEl.src = url;
                                        imgEl.style.display = '';
                                    } else {
                                        imgEl.style.display = 'none';
                                    }
                                }
                                ['gsp-style', 'gsp-color', 'gsp-file'].forEach(id => document.getElementById(id).addEventListener('change', refresh));
                                document.getElementById('gsp-open').addEventListener('click', () => window.open(build(), '_blank'));
                                refresh();
                            })();
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/sprites?key=...</span></div>
                        <div class="alert-mini warn" data-i18n-html="gsp_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Path: <code>{MapName}/sprites</code> (tanpa Style/Color/Variant/file split)</li>
                                <li>v0 punya endpoint terpisah untuk JSON vs PNG (mis. <code>/sprites?json=true</code>)</li>
                                <li>Sprite set lock per provider</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetStaticMap -->
            <div class="op-panel" id="op-maps-get-static-map">
                <div class="breadcrumb-mini">Maps V2 / GetStaticMap</div>
                <h1>GetStaticMap <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="gsm_desc">Render map jadi gambar PNG/JPEG single. Cocok untuk thumbnail, preview di card, email, atau social sharing.</p>

                <div class="alert-mini soon" data-i18n-html="soon_static"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Maps Actions in Console only has <code>GetTile</code>. Workaround: screenshot from MapLibre canvas.</div>

                <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/static-map?key=...&amp;...</span></div>

                <h4 data-i18n="label_query_params">Query parameters</h4>
                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Param</th>
                            <th>Type</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>Center</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">lng,lat</span></td>
                            <td data-i18n="gsm_p_center">Map center</td>
                        </tr>
                        <tr>
                            <td><code>Zoom</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td data-i18n="note_zoom_range">0-22</td>
                        </tr>
                        <tr>
                            <td><code>Width</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td data-i18n="note_pixel">Pixel</td>
                        </tr>
                        <tr>
                            <td><code>Height</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td data-i18n="note_pixel">Pixel</td>
                        </tr>
                        <tr>
                            <td><code>Style</code></td>
                            <td>—</td>
                            <td data-i18n="note_styles_all">Standard | Monochrome | Hybrid | Satellite</td>
                        </tr>
                        <tr>
                            <td><code>FileFormat</code></td>
                            <td>—</td>
                            <td data-i18n="note_png_jpeg">png | jpeg</td>
                        </tr>
                        <tr>
                            <td><code>Pins</code></td>
                            <td><span class="type-tag">array</span></td>
                            <td data-i18n="gsm_p_pins">Optional marker overlay</td>
                        </tr>
                    </tbody>
                </table>

                <h4 data-i18n="label_example">Example</h4>
                <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/static-map?key=...&amp;Center=106.84,-6.20&amp;Zoom=14&amp;Width=600&amp;Height=400&amp;Style=Standard</span></div>
                <p data-i18n="static_map_result">Hasil: gambar PNG 600×400 pusat di Sudirman zoom 14.</p>
            </div>

            {{-- =============================================================== --}}
            {{-- PLACES OPERATIONS                                              --}}
            {{-- =============================================================== --}}

            <!-- SearchText -->
            <div class="op-panel" id="op-places-search-text">
                <div class="breadcrumb-mini">Places V2 / SearchText</div>
                <h1>SearchText</h1>
                <p class="op-desc" data-i18n="st_desc">Pencarian Place (POI / alamat / area) berbasis free-form text. Cocok untuk search bar dengan tombol "Search". Mendukung bias position, filter geografis (BoundingBox/Circle/Country), dan filter kategori.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">

                    {{-- =================================================================== --}}
                    {{-- V2 TAB                                                              --}}
                    {{-- =================================================================== --}}
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/v2/search-text?key=...</span></div>

                        {{-- Empat langkah kerja satu operasi dipisah jadi tab, bukan
                             ditumpuk: yang dibaca orang biasanya cuma satu di antaranya.
                             Menekan "Kirim ke AWS" otomatis memindahkan tab ke Hasil. --}}
                        <div class="op-tabs" role="tablist">
                            <button class="op-tab-btn is-on" data-tab="live" type="button"><i class="bi bi-play-circle"></i> <span data-i18n="tab_live">Live try</span></button>
                            <button class="op-tab-btn" data-tab="request" type="button"><i class="bi bi-arrow-up-right"></i> <span data-i18n="tab_request">Request</span></button>
                            <button class="op-tab-btn" data-tab="response" type="button"><i class="bi bi-arrow-down-left"></i> <span data-i18n="tab_response">Respons</span></button>
                            <button class="op-tab-btn" data-tab="error" type="button"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="tab_error">Error</span></button>
                        </div>

                        <div class="op-tab is-on" data-tab="live">
                            {{-- Builder menggantikan tiga bagian sekaligus: kartu tier, blok
                                 Request Syntax statis, dan kartu Field Rules. Aturannya kini
                                 jadi bentuk kontrolnya sendiri, dan keranjang harganya
                                 dihitung ulang tiap pilihan berubah. Skemanya ada di
                                 public/javascript/docs/aws-api-schemas.js. --}}
                            <div data-builder="places-search-text"></div>

                            {{-- Panel Try It lama sudah tidak ditampilkan: JSON-nya dirakit
                                 builder di atas. Elemennya tetap ada (tersembunyi) karena
                                 AWSAPI_TryIt memakainya sebagai mesin — editor tempat JSON
                                 ditulis, tombol yang diklik builder, penanda status, dan URL
                                 AWS untuk mode panggilan langsung dengan key sendiri. --}}
                            <div class="tryit-engine" hidden>
                                <span class="json-status ok" id="st-json-status">VALID</span>
                                <button class="btn-copy" id="st-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                                {{-- Bentuk sarangnya harus tetap .try-it-url > div > span kedua:
                                     itu yang dibaca getAwsUrlRaw() untuk menemukan URL AWS. --}}
                                <div class="try-it-url">
                                    <div>
                                        <span class="try-it-method">POST</span>
                                        <span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/search-text?key=***</span>
                                    </div>
                                </div>
                                <textarea class="json-editor" id="st-req-preview" spellcheck="false"></textarea>
                                <button class="btn-send" id="st-run" type="button"><span data-i18n="btn_send">Send Request</span></button>
                                <span id="st-spinner"></span>
                            </div>

                            {{-- Hasil panggilan tinggal di tab yang sama dengan perakitnya:
                                 begitu tombol kirim ditekan, balasannya muncul tepat di
                                 bawah formulir, bukan di tab sebelah. --}}
                            <div class="doc-section-h"><span class="ic orange"><i class="bi bi-broadcast"></i></span> <span data-i18n="bld_result">Hasil dari AWS</span></div>
                            {{-- Response area --}}
                            <div class="resp-bar">
                                <span style="font-weight:700;color:var(--text-primary);">Response</span>
                                <span class="status-pill idle" id="st-status">— idle —</span>
                                <span class="meta" id="st-meta"></span>
                                <button class="btn-copy" id="st-resp-copy" style="margin-left:auto;background:#e2e8f0;color:#334155;border:1px solid #cbd5e1;display:none;"><span data-i18n="btn_copy_response">📋 Copy Response</span></button>
                            </div>
                            <div id="st-resp" class="resp-body empty" data-i18n="bld_resp_idle">Tekan "Kirim ke AWS" di perakit request untuk melihat balasan aslinya.</div>
                        </div>

                        {{-- Rujukan bentuk request: sintaks lengkap beserta seluruh
                             parameter, termasuk yang ditolak ap-southeast-1 supaya tetap
                             ketahuan ada. Yang bisa dipakai langsung ada di tab Live try. --}}
                        <div class="op-tab" data-tab="request">
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                        <pre><code class="language-json">{
  "QueryText": "string",
  "MaxResults": number,
  "BiasPosition": [ number, number ],
  "Filter": {
    "BoundingBox": [ number, number, number, number ],
    "Circle": {
      "Center": [ number, number ],
      "Radius": number
    },
    "IncludeCountries": [ "string" ]
  },
  "AdditionalFeatures": [ "TimeZone" ],      // tier: Advanced
  "Language": "string",
  "IntendedUse": "SingleUse" | "Storage"     // tier: Stored
}</code></pre>
                        <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_places_live">Baris yang diberi pil tier menentukan keranjang harga. Tanpa satu pun penanda, panggilan masuk <b>Core</b>; <b>Stored</b> hanya kalau <code>IntendedUse</code> diisi <code>Storage</code>.</span></p>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>QueryText</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td><span class="req">YES</span></td>
                                    <td data-i18n-html="st_p_querytext">Free-form keyword (1-200 char). *Required: one of <code>QueryText</code> or <code>QueryId</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>MaxResults</code></td>
                                    <td><span class="type-tag">number</span></td>
                                    <td>—</td>
                                    <td data-i18n="note_max_20">1–100, default 20</td>
                                </tr>
                                <tr>
                                    <td><code>BiasPosition</code></td>
                                    <td><span class="type-tag">[lng, lat]</span></td>
                                    <td><span class="req">YES*</span></td>
                                    <td data-i18n-html="st_p_bias">Bias ranking + reference for the <code>Distance</code> field. <strong>Exactly 1</strong> of BiasPosition / Filter.BoundingBox / Filter.Circle.<br><strong>📌 Use this if you need Distance</strong> — Filter.Circle/BoundingBox don't trigger Distance in <code>ap-southeast-1</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.BoundingBox</code></td>
                                    <td><span class="type-tag">[w, s, e, n]</span></td>
                                    <td>—</td>
                                    <td data-i18n="st_p_bbox">Limit results to a box (west, south, east, north)</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.Circle</code></td>
                                    <td><span class="type-tag">object</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_circle"><code>Center: [lng,lat]</code>, <code>Radius: meter</code> (max 50000)</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.IncludeCountries</code></td>
                                    <td><span class="type-tag">array&lt;string&gt;</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_iso3_arr">ISO-3 country codes, e.g. <code>["IDN"]</code></td>
                                </tr>
                                <tr>
                                    <td><code>AdditionalFeatures</code></td>
                                    <td><span class="type-tag">array&lt;string&gt;</span></td>
                                    <td>—</td>
                                    <td><span class="tier-pill tier-advanced">Advanced</span> <span data-i18n-html="st_p_addfeat_full"><code>Contact</code> | <code>TimeZone</code> | <code>Phonemes</code> | <code>Access</code>. Di <code>ap-southeast-1</code> hanya <code>TimeZone</code> yang dilayani.</span></td>
                                </tr>
                                <tr>
                                    <td><code>Language</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_bcp47">BCP 47 (e.g. <code>id</code>, <code>en</code>)</td>
                                </tr>
                                <tr>
                                    <td><code>IntendedUse</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td><span class="tier-pill tier-stored">Stored</span> <span data-i18n-html="note_intended_use"><code>SingleUse</code> (default) | <code>Storage</code></span></td>
                                </tr>
                            </tbody>
                        </table>

                        
                        </div>

                        <div class="op-tab" data-tab="response">
                            <div class="resp-sample"><i class="bi bi-eye"></i> <span data-i18n="bld_resp_sample">Contoh bentuk balasan — tekan "Kirim ke AWS" untuk balasan asli.</span></div>
                            <pre style="margin:0;background:transparent;"><code class="language-json">{
      "ResultItems": [
        {
          "PlaceId": "AQAAAC0A2MtBKefVps2gjOatU5vuegwizzHZ2R53...",
          "PlaceType": "PointOfInterest",
          "Title": "Silang Monas Jakarta Pusat",
          "Address": {
            "Label": "Silang Monas, Gambir, Daerah Khusus Ibukota Jakarta, 10110",
            "Country": { "Code2": "ID", "Code3": "IDN", "Name": "Indonesia" },
            "Locality": "DKI Jakarta",
            "District": "Gambir",
            "SubDistrict": "Gambir",
            "PostalCode": "10110",
            "Street": "Jalan Medan Merdeka Barat",
            "AddressNumber": "12"
          },
          "Position": [ 106.826, -6.175 ],
          "MapView": [ 106.824, -6.177, 106.828, -6.173 ],
          "Distance": 640,
          "Categories": [
            { "Id": "monument", "Name": "Monument", "LocalizedName": "monumen", "Primary": true }
          ],
          "TimeZone": { "Name": "Asia/Jakarta", "Offset": "UTC+07:00", "OffsetSeconds": 25200 }
        }
      ]
    }</code></pre>

                            <p class="syn-legend"><i class="bi bi-info-circle"></i> <span data-i18n-html="st_resp_note"></span></p>

                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>ResultItems[].PlaceId</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td data-i18n="r_placeid">Unique AWS ID — use with GetPlace to fetch full detail</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].PlaceType</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td data-i18n="r_placetype_examples">PointOfInterest | Address | Street | District | Region | etc.</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Title</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td data-i18n="r_title">Main display name</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Position</code></td>
                                        <td><span class="type-tag">[lng, lat]</span></td>
                                        <td data-i18n="r_position">Center coordinate of the place</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Distance</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td data-i18n-html="r_distance_long">
                                            <strong>Meters</strong> from reference point.<br>
                                            ⚠️ <strong>Empirical in <code>ap-southeast-1</code>:</strong> this field only appears when the request uses <code>BiasPosition</code>. When using <code>Filter.Circle</code> or <code>Filter.BoundingBox</code>, the <code>Distance</code> field is <strong>absent</strong> from the response.<br>
                                            Workaround: compute Haversine in JS from <code>Position</code> to the origin.
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Address</code></td>
                                        <td><span class="type-tag">object</span></td>
                                        <td data-i18n="r_address">Structured address (label + components)</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].MapView</code></td>
                                        <td><span class="type-tag">[w,s,e,n]</span></td>
                                        <td data-i18n="r_mapview">Bounding box for fitting the map</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Categories</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td>Kategori AWS (mis. <code>transit_station_bus</code>)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="error">
                            {{-- ====================== COMMON ERRORS ====================== --}}

                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="err_status">Status</th>
                                        <th data-i18n="err_trigger">Trigger</th>
                                        <th data-i18n="err_message">AWS Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t1">2+ of <code>BiasPosition</code> / <code>Filter.BoundingBox</code> / <code>Filter.Circle</code> sent together</td>
                                        <td><em>"Exactly one of the following fields must be set: BiasPosition, Filter.BoundingBox, Filter.Circle."</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t2">No <code>QueryText</code> and no <code>QueryId</code></td>
                                        <td><em>"Either QueryText or QueryId is required."</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t3"><code>MaxResults</code> &gt; 20</td>
                                        <td><em>"Member must have value less than or equal to 20"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t4"><code>Filter.Circle.Radius</code> &gt; 50000</td>
                                        <td><em>"Member must have value less than or equal to 50000"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t5">Wrong coordinate format (e.g. <code>[lat, lng]</code> instead of <code>[lng, lat]</code>)</td>
                                        <td data-i18n-html="err_m5"><em>Weird / empty result</em> — AWS doesn't validate ranges, coords are accepted as-is</td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t6"><code>Filter.IncludeCountries</code> not ISO-3 (e.g. "Indonesia" or "ID")</td>
                                        <td><em>"Validation failed: country code must be 3 letters"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">403</span></td>
                                        <td data-i18n-html="err_t7">API Key lacks action <code>geo-places:SearchText</code></td>
                                        <td><em>"User is not authorized to access this resource with an explicit deny"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">403</span></td>
                                        <td data-i18n-html="err_t8">API Key wrong or missing <code>?key=</code></td>
                                        <td><em>"The security token included in the request is invalid"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">429</span></td>
                                        <td data-i18n="err_t9">Rate limit exceeded (default 50 TPS)</td>
                                        <td data-i18n-html="err_m9"><em>"Rate exceeded"</em> — implement retry with backoff</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>




                        {{-- ====================== HASIL PANGGILAN (V2 only) ====================== --}}


                        {{-- Arti tiap field dilipat: yang dicari orang biasanya balasan
                             nyata di atas, bukan tabel. --}}



                        <script>
                            // SearchText — Try it Live (logic generic ada di aws-api-try-it.js)
                            AWSAPI_TryIt.init({
                                prefix: 'st',
                                panelId: 'op-places-search-text',
                                proxy: '/api/places/search',
                                defaultPreset: 'bias',
                                presets: {
                                    bias: {
                                        QueryText: 'halte TransJakarta',
                                        BiasPosition: [106.8456, -6.2088],
                                        MaxResults: 5,
                                        Language: 'id',
                                        Filter: {
                                            IncludeCountries: ['IDN']
                                        }
                                    },
                                    circle: {
                                        QueryText: 'halte TransJakarta',
                                        Filter: {
                                            Circle: {
                                                Center: [106.8456, -6.2088],
                                                Radius: 2000
                                            },
                                            IncludeCountries: ['IDN']
                                        },
                                        MaxResults: 10,
                                        Language: 'id'
                                    },
                                    bbox: {
                                        QueryText: 'stasiun',
                                        Filter: {
                                            BoundingBox: [106.689, -6.371, 106.971, -6.089],
                                            IncludeCountries: ['IDN']
                                        },
                                        MaxResults: 10,
                                        Language: 'id'
                                    },
                                    minimal: {
                                        QueryText: 'Monas Jakarta'
                                    },
                                    full: {
                                        QueryText: 'halte TransJakarta',
                                        BiasPosition: [106.8456, -6.2088],
                                        Filter: {
                                            IncludeCountries: ['IDN']
                                        },
                                        MaxResults: 10,
                                        Language: 'id',
                                        PoliticalView: 'IDN',
                                        AdditionalFeatures: ['TimeZone'],
                                        IntendedUse: 'SingleUse'
                                    },
                                    error: {
                                        QueryText: 'halte',
                                        BiasPosition: [106.8456, -6.2088],
                                        Filter: {
                                            Circle: {
                                                Center: [106.8456, -6.2088],
                                                Radius: 2000
                                            }
                                        }
                                    }
                                }
                            });
                        </script>

                    </div> {{-- end v2 tab --}}

                    {{-- =================================================================== --}}
                    {{-- V0 TAB                                                              --}}
                    {{-- =================================================================== --}}
                    <div data-version="v0">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/places/v0/indexes/{IndexName}/search/text?key=...</span></div>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
                        <pre><code class="language-json">{
  "Text": "halte TJ",
  "BiasPosition": [106.84, -6.20],
  "MaxResults": 10,
  "FilterCountries": ["IDN"]
}</code></pre>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Body</div>
                        <pre><code class="language-json">{
  "Summary": { "Text": "halte TJ", "MaxResults": 10, "ResultBBox": [...] },
  "Results": [
    {
      "Place": {
        "Label": "Halte TJ Halimun, Jakarta",
        "Geometry": { "Point": [106.85, -6.24] },
        "Country": "IDN",
        "AddressNumber": "5"
      },
      "Distance": 850.42
    }
  ]
}</code></pre>

                        <div class="alert-mini warn" data-i18n-html="st_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Field <code>Text</code> → <code>QueryText</code></li>
                                <li><code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                                <li>Response: <code>Results[].Place.Geometry.Point</code> → <code>ResultItems[].Position</code></li>
                                <li><code>MaxResults</code> max 10 (v2: 20)</li>
                                <li>Must create <code>PlaceIndex</code> resource first in AWS Console</li>
                            </ul>
                        </div>

                    </div> {{-- end v0 tab --}}

                </div> {{-- end ver-content --}}
            </div>

            <!-- Suggest -->
            <div class="op-panel" id="op-places-suggest">
                <div class="breadcrumb-mini">Places V2 / Suggest</div>
                <h1>Suggest</h1>
                <p class="op-desc" data-i18n-html="sg_desc">Autocomplete ketik-langsung untuk dropdown. Di <code>ap-southeast-1</code> yang kembali selalu Place — saran perbaikan kueri butuh <code>MaxQueryRefinements</code> yang ditolak di sini.</p>


                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/v2/suggest?key=...</span></div>


                        {{-- Empat tab seperti SearchText: perakit + hasil, lalu rujukan
                             request, respons, dan error. --}}
                        <div class="op-tabs" role="tablist">
                            <button class="op-tab-btn is-on" data-tab="live" type="button"><i class="bi bi-play-circle"></i> <span data-i18n="tab_live">Live try</span></button>
                            <button class="op-tab-btn" data-tab="request" type="button"><i class="bi bi-arrow-up-right"></i> <span data-i18n="tab_request">Request</span></button>
                            <button class="op-tab-btn" data-tab="response" type="button"><i class="bi bi-arrow-down-left"></i> <span data-i18n="tab_response">Respons</span></button>
                            <button class="op-tab-btn" data-tab="error" type="button"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="tab_error">Error</span></button>
                        </div>

                        <div class="op-tab is-on" data-tab="live">
                            <div data-builder="places-suggest"></div>

                            {{-- Mesin Try It: tersembunyi, dipakai builder untuk mengirim. --}}
                            <div class="tryit-engine" hidden>
                                <span class="json-status ok" id="sg-json-status">VALID</span>
                                <button class="btn-copy" id="sg-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                                <div class="try-it-url">
                                    <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/suggest</span></div>
                                </div>
                                <textarea class="json-editor" id="sg-req-preview" spellcheck="false"></textarea>
                                <button class="btn-send" id="sg-run" type="button"><span data-i18n="btn_send">Send Request</span></button>
                                <span id="sg-spinner"></span>
                            </div>

                            <div class="doc-section-h"><span class="ic orange"><i class="bi bi-broadcast"></i></span> <span data-i18n="bld_result">Hasil dari AWS</span></div>
                            <div class="resp-bar">
                                <span style="font-weight:700;color:var(--text-primary);">Response</span>
                                <span class="status-pill idle" id="sg-status">— idle —</span>
                                <span class="meta" id="sg-meta"></span>
                            </div>
                            <div id="sg-resp" class="resp-body empty" data-i18n="bld_resp_idle">Tekan "Kirim ke AWS" di perakit request untuk melihat balasan aslinya.</div>
                        </div>

                        <div class="op-tab" data-tab="request">
                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                            <pre><code class="language-json">{
      "QueryText": "string",
      "MaxResults": number,
      "BiasPosition": [ number, number ],
      "Filter": {
        "BoundingBox": [ number, number, number, number ],
        "Circle": { "Center": [ number, number ], "Radius": number },
        "IncludeCountries": [ "string" ]
      },
      "AdditionalFeatures": [ "Core" | "TimeZone" ],   // tier: Core/Advanced
      "Language": "string"
    }</code></pre>
                            <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_suggest_live">Baris yang diberi pil tier menentukan keranjang harga. Tanpa <code>AdditionalFeatures</code>, panggilan masuk <b>Label</b> — keranjang termurah. Suggest tidak menerima <code>IntendedUse: "Storage"</code>, jadi tidak ada tier Stored di sini.</span></p>

                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>QueryText</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td>1–200 char. Partial keyword OK (autocomplete).</td>
                                    </tr>
                                    <tr>
                                        <td><code>MaxResults</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td>—</td>
                                        <td data-i18n="sg_n_maxresults">1–100, bawaan 5</td>
                                    </tr>
                                    <tr>
                                        <td><code>BiasPosition</code></td>
                                        <td><span class="type-tag">[lng, lat]</span></td>
                                        <td>—</td>
                                        <td><strong>Exactly 1</strong> dari Bias / Filter.Circle / Filter.BoundingBox.</td>
                                    </tr>
                                    <tr>
                                        <td><code>Filter.Circle</code></td>
                                        <td><span class="type-tag">object</span></td>
                                        <td>—</td>
                                        <td><code>Center: [lng,lat]</code>, <code>Radius: meter</code> (max 50000)</td>
                                    </tr>
                                    <tr>
                                        <td><code>Filter.BoundingBox</code></td>
                                        <td><span class="type-tag">[w,s,e,n]</span></td>
                                        <td>—</td>
                                        <td>west, south, east, north</td>
                                    </tr>
                                    <tr>
                                        <td><code>Filter.IncludeCountries</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_iso3_codes">ISO-3 codes</td>
                                    </tr>
                                    <tr>
                                        <td><code>Language</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td>—</td>
                                        <td data-i18n-html="note_bcp47_id">BCP 47 (e.g. <code>id</code>)</td>
                                    </tr>
                                    <tr>
                                        <td><code>AdditionalFeatures</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td>—</td>
                                        <td><span class="tier-pill tier-core">Core</span><span class="tier-pill tier-advanced">Advanced</span> <span data-i18n-html="sg_p_addfeat">Valid: <code>Core</code>, <code>TimeZone</code>, <code>Phonemes</code>. <b>Tip:</b> tambah <code>"Core"</code> untuk unlock field <code>Position</code> di response — tanpa ini Suggest gak return koordinat (jadi distance ke marker user gak bisa dihitung client-side).</span></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="alert-mini warn" style="margin-top:14px;">
                                💡 <span data-i18n-html="sg_position_tip"><b>Penting untuk autocomplete dengan distance:</b> response Suggest <b>tidak include</b> <code>Position</code> by default. Kirim <code>"AdditionalFeatures": ["Core"]</code> untuk dapat lat/lng tiap suggestion — supaya bisa compute distance dari current marker/map center langsung di client.</span>
                            </div>
                        </div>

                        <div class="op-tab" data-tab="response">
                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
                            <pre><code class="language-json">{
      "ResultItems": [
        {
          "Title": "string",
          "SuggestResultItemType": "Place",
          "Highlights": {                              // hanya dengan AdditionalFeatures: ["Core"]
            "Title": [ { "StartIndex": number, "EndIndex": number, "Value": "string" } ],
            "Address": { "Label": [ { "StartIndex": number, "EndIndex": number, "Value": "string" } ] }
          },
          "Place": {
            "PlaceId": "string",
            "PlaceType": "string",
            "Address": { "Label": "string", "Country": { ... }, "Locality": "string", "Street": "string" },
            "Position": [ number, number ],               // AdditionalFeatures: ["Core"]
            "Distance": number,                           // AdditionalFeatures: ["Core"]
            "MapView": [ number, number, number, number ],
            "Categories": [ { "Id": "string", "Name": "string", "LocalizedName": "string", "Primary": boolean } ],
            "TimeZone": { "Name": "string", "Offset": "string", "OffsetSeconds": number }
          }
        }
      ]
    }</code></pre>

                            <p class="syn-legend"><i class="bi bi-info-circle"></i> <span data-i18n-html="sg_resp_note"><code>ResultItems</code> satu-satunya kunci di badan respons; <code>PricingBucket</code> dikirim sebagai header <code>x-amz-geo-pricing-bucket</code>. <code>SuggestResultItemType</code> selalu <code>Place</code> — item <code>Query</code> butuh <code>MaxQueryRefinements</code> yang ditolak 400 di <code>ap-southeast-1</code>.</span></p>

                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>SuggestResultItemType</code></td>
                                        <td><span class="type-tag">enum</span></td>
                                        <td><code>Place</code> = actual hit, <code>Query</code> = refinement keyword</td>
                                    </tr>
                                    <tr>
                                        <td><code>Place.Position</code></td>
                                        <td><span class="type-tag">[lng,lat]</span></td>
                                        <td data-i18n="r_pos_place_only">Only for SuggestResultItemType=Place</td>
                                    </tr>
                                    <tr>
                                        <td><code>Place.Distance</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td data-i18n="r_distance_bias_only">Meters (only when using BiasPosition)</td>
                                    </tr>
                                    <tr>
                                        <td><code>Highlights.Title</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td data-i18n="r_highlights">Range index for highlighting matched keywords</td>
                                    </tr>
                                    <tr>
                                        <td><code>Query.QueryId</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td data-i18n="r_queryid">Pass to SearchText as QueryId for full search</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="error">
                            <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="err_status">Status</th>
                                        <th data-i18n="err_trigger">Trigger</th>
                                        <th data-i18n="err_message">AWS Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="err_t1">2+ spatial filter</td>
                                        <td><em>"Exactly one of..."</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="sg_err_qt">Empty <code>QueryText</code></td>
                                        <td><em>"QueryText: Member must have length greater than or equal to 1"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td><code>MaxResults</code> &gt; 100</td>
                                        <td><em>"...less than or equal to 10"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">403</span></td>
                                        <td data-i18n-html="sg_err_perm">Action <code>geo-places:Suggest</code> tidak di-grant</td>
                                        <td><em>"explicit deny"</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <script>
                            AWSAPI_TryIt.init({
                                prefix: 'sg',
                                panelId: 'op-places-suggest',
                                proxy: '/api/places/suggestions',
                                defaultPreset: 'bias',
                                presets: {
                                    bias: {
                                        QueryText: 'halte',
                                        BiasPosition: [106.8456, -6.2088],
                                        MaxResults: 5,
                                        Language: 'id'
                                    },
                                    with_position: {
                                        QueryText: 'halte',
                                        BiasPosition: [106.8456, -6.2088],
                                        MaxResults: 5,
                                        Language: 'id',
                                        AdditionalFeatures: ['Core']
                                    },
                                    circle: {
                                        QueryText: 'halte',
                                        Filter: {
                                            Circle: {
                                                Center: [106.8456, -6.2088],
                                                Radius: 2000
                                            },
                                            IncludeCountries: ['IDN']
                                        },
                                        MaxResults: 5,
                                        Language: 'id'
                                    },
                                    minimal: {
                                        QueryText: 'mon'
                                    },
                                    full: {
                                        QueryText: 'halte',
                                        BiasPosition: [106.8456, -6.2088],
                                        MaxResults: 10,
                                        Language: 'id',
                                        PoliticalView: 'IDN',
                                        MaxQueryRefinements: 2,
                                        AdditionalFeatures: ['Core'],
                                        IntendedUse: 'SingleUse'
                                    }
                                }
                            });
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/places/v0/indexes/{Idx}/search/suggestions?key=...</span></div>
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
                        <pre><code class="language-json">{
  "Text": "halte tj",
  "BiasPosition": [106.84, -6.20],
  "MaxResults": 5,
  "FilterCountries": ["IDN"]
}</code></pre>
                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response</div>
                        <pre><code class="language-json">{
  "Summary": { "Text": "halte tj" },
  "Results": [
    { "Text": "Halte Transjakarta Halimun", "PlaceId": "AQABA..." }
  ]
}</code></pre>
                        <div class="alert-mini warn" data-i18n-html="sg_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li><code>Text</code> → <code>QueryText</code></li>
                                <li><code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                                <li><code>Results[].Text</code> → <code>ResultItems[].Title</code></li>
                                <li>v0 tidak return Position di Suggest — harus call GetPlace</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- ReverseGeocode -->
            <div class="op-panel" id="op-places-reverse-geocode">
                <div class="breadcrumb-mini">Places V2 / ReverseGeocode</div>
                <h1>ReverseGeocode</h1>
                <p class="op-desc" data-i18n="rg_desc">Koordinat → alamat terdekat.</p>


                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/v2/reverse-geocode?key=...</span></div>


                        {{-- Empat tab seperti operasi Places lain: perakit + hasil, lalu
                             rujukan request, respons, dan error. --}}
                        <div class="op-tabs" role="tablist">
                            <button class="op-tab-btn is-on" data-tab="live" type="button"><i class="bi bi-play-circle"></i> <span data-i18n="tab_live">Live try</span></button>
                            <button class="op-tab-btn" data-tab="request" type="button"><i class="bi bi-arrow-up-right"></i> <span data-i18n="tab_request">Request</span></button>
                            <button class="op-tab-btn" data-tab="response" type="button"><i class="bi bi-arrow-down-left"></i> <span data-i18n="tab_response">Respons</span></button>
                            <button class="op-tab-btn" data-tab="error" type="button"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="tab_error">Error</span></button>
                        </div>

                        <div class="op-tab is-on" data-tab="live">
                            <div data-builder="places-reverse-geocode"></div>

                            {{-- Mesin Try It: tersembunyi, dipakai builder untuk mengirim. --}}
                            <div class="tryit-engine" hidden>
                                <span class="json-status ok" id="rg-json-status">VALID</span>
                                <button class="btn-copy" id="rg-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                                <div class="try-it-url">
                                    <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/reverse-geocode</span></div>
                                </div>
                                <textarea class="json-editor" id="rg-req-preview" spellcheck="false"></textarea>
                                <button class="btn-send" id="rg-run" type="button"><span data-i18n="btn_send">Send Request</span></button>
                                <span id="rg-spinner"></span>
                            </div>

                            <div class="doc-section-h"><span class="ic orange"><i class="bi bi-broadcast"></i></span> <span data-i18n="bld_result">Hasil dari AWS</span></div>
                            <div class="resp-bar">
                                <span style="font-weight:700;color:var(--text-primary);">Response</span>
                                <span class="status-pill idle" id="rg-status">— idle —</span>
                                <span class="meta" id="rg-meta"></span>
                            </div>
                            <div id="rg-resp" class="resp-body empty" data-i18n="bld_resp_idle">Tekan "Kirim ke AWS" di perakit request untuk melihat balasan aslinya.</div>
                        </div>

                        <div class="op-tab" data-tab="request">
                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                            <pre><code class="language-json">{
      "QueryPosition": [ number, number ],
      "QueryRadius": number,
      "MaxResults": number,
      "Filter": {
        "IncludeCountries": [ "string" ]
      },
      "AdditionalFeatures": [ "TimeZone" ],      // tier: Advanced
      "Language": "string",
      "IntendedUse": "SingleUse" | "Storage"     // tier: Stored
    }</code></pre>
                            <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_rg_live">Baris yang diberi pil tier menentukan keranjang harga. Tanpa satu pun penanda, panggilan masuk <b>Core</b>; <b>Stored</b> hanya kalau <code>IntendedUse</code> diisi <code>Storage</code>.</span></p>

                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>QueryPosition</code></td>
                                        <td><span class="type-tag">[lng, lat]</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td data-i18n="rg_p_qpos">Coordinate point to reverse-geocode</td>
                                    </tr>
                                    <tr>
                                        <td><code>QueryRadius</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td>—</td>
                                        <td data-i18n="rg_n_radius">Radius pencarian dalam meter, 1–100000. Nilai 0 ditolak 400</td>
                                    </tr>
                                    <tr>
                                        <td><code>MaxResults</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td>—</td>
                                        <td data-i18n="rg_n_maxresults">1–100, bawaan 1</td>
                                    </tr>
                                    <tr>
                                        <td><code>AdditionalFeatures</code></td>
                                        <td><span class="type-tag">array</span></td>
                                        <td>—</td>
                                        <td><span class="tier-pill tier-advanced">Advanced</span> <span data-i18n-html="rg_p_addfeat_full"><code>TimeZone</code> | <code>Intersections</code>. Di <code>ap-southeast-1</code> hanya <code>TimeZone</code> yang dilayani.</span></td>
                                    </tr>
                                    <tr>
                                        <td><code>Language</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td>—</td>
                                        <td data-i18n="note_bcp47_short">BCP 47</td>
                                    </tr>
                                    <tr>
                                        <td><code>IntendedUse</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td>—</td>
                                        <td><span class="tier-pill tier-stored">Stored</span> <span data-i18n-html="note_intended_use"><code>SingleUse</code> (default) | <code>Storage</code></span></td>
                                    </tr>
                                    <tr>
                                        <td><code>Filter.IncludeCountries</code></td>
                                        <td><span class="type-tag">array&lt;string&gt;</span></td>
                                        <td>—</td>
                                        <td data-i18n-html="note_iso3_arr">Kode negara ISO-3, mis. <code>["IDN"]</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="response">
                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
                            <pre><code class="language-json">{
      "ResultItems": [
        {
          "PlaceId": "string",
          "PlaceType": "string",
          "Title": "string",
          "Address": {
            "Label": "string",
            "Country": { "Code2": "string", "Name": "string" },
            "Region": { "Code": "string", "Name": "string" },
            "PostalCode": "string"
          },
          "Position": [ number, number ],
          "Distance": number,
          "MapView": [ number, number, number, number ]
        }
      ]
    }</code></pre>

                            <p class="syn-legend"><i class="bi bi-info-circle"></i> <span data-i18n-html="rg_resp_note"><code>ResultItems</code> satu-satunya kunci di badan respons — <code>PricingBucket</code> dikirim sebagai header <code>x-amz-geo-pricing-bucket</code>. Tiap item selalu membawa <code>Position</code>, <code>Distance</code>, <code>MapView</code>, dan <code>Categories</code>; <code>TimeZone</code> hanya kalau diminta.</span></p>

                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Type</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>ResultItems[].Title</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td data-i18n="r_short_name">Short location name</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Address.Label</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td data-i18n="r_full_address">Full formatted address</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].Distance</code></td>
                                        <td><span class="type-tag">number</span></td>
                                        <td data-i18n="r_distance_qpos">Meters from QueryPosition</td>
                                    </tr>
                                    <tr>
                                        <td><code>ResultItems[].PlaceType</code></td>
                                        <td><span class="type-tag">string</span></td>
                                        <td>Locality, District, Street, PointAddress, dst.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="op-tab" data-tab="error">
                            <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="err_status">Status</th>
                                        <th data-i18n="err_trigger">Trigger</th>
                                        <th data-i18n="err_message">AWS Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="rg_err_pos">Tanpa <code>QueryPosition</code></td>
                                        <td><em>"QueryPosition is required"</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="rg_err_format">Format <code>[lat, lng]</code> (terbalik)</td>
                                        <td data-i18n-html="rg_err_format_msg">Hasil aneh — AWS treat sebagai [lng, lat]</td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td><code>MaxResults</code> &gt; 100</td>
                                        <td><em>"...less than or equal to 4"</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <script>
                            AWSAPI_TryIt.init({
                                prefix: 'rg',
                                panelId: 'op-places-reverse-geocode',
                                proxy: '/api/places/reverse',
                                defaultPreset: 'basic',
                                presets: {
                                    basic: {
                                        QueryPosition: [106.8456, -6.2088],
                                        MaxResults: 1,
                                        Language: 'id'
                                    },
                                    filter: {
                                        QueryPosition: [106.8456, -6.2088],
                                        MaxResults: 4,
                                        Language: 'id',
                                        Filter: {
                                            IncludePlaceTypes: ['Street', 'PointAddress']
                                        }
                                    },
                                    full: {
                                        QueryPosition: [106.8456, -6.2088],
                                        QueryRadius: 100,
                                        MaxResults: 4,
                                        Language: 'id',
                                        PoliticalView: 'IDN',
                                        AdditionalFeatures: ['TimeZone', 'Access'],
                                        IntendedUse: 'SingleUse'
                                    }
                                }
                            });
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/places/v0/indexes/{Idx}/search/position?key=...</span></div>
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
                        <pre><code class="language-json">{
  "Position": [106.84, -6.20],
  "MaxResults": 1
}</code></pre>
                        <div class="alert-mini warn" data-i18n-html="rg_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li><code>Position</code> → <code>QueryPosition</code></li>
                                <li>Tidak ada <code>Filter.IncludePlaceTypes</code> di v0</li>
                                <li>Wajib bikin <code>PlaceIndex</code> resource dulu</li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetPlace -->
            <div class="op-panel" id="op-places-get-place">
                <div class="breadcrumb-mini">Places V2 / GetPlace</div>
                <h1>GetPlace</h1>
                <p class="op-desc" data-i18n="gp_desc">Detail lengkap Place by <code>PlaceId</code> (dari hasil Search/Suggest sebelumnya).</p>


                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method GET">GET</span><span>https://places.geo.{region}.amazonaws.com/v2/place/{PlaceId}?key=...&amp;...</span></div>


                        {{-- Empat tab seperti operasi Places lain. GetPlace beda sendiri:
                             GET tanpa badan JSON, jadi perakitnya merangkai URL dan
                             mengisi formulir tersembunyi di bawahnya. --}}
                        <div class="op-tabs" role="tablist">
                            <button class="op-tab-btn is-on" data-tab="live" type="button"><i class="bi bi-play-circle"></i> <span data-i18n="tab_live">Live try</span></button>
                            <button class="op-tab-btn" data-tab="request" type="button"><i class="bi bi-arrow-up-right"></i> <span data-i18n="tab_request">Request</span></button>
                            <button class="op-tab-btn" data-tab="response" type="button"><i class="bi bi-arrow-down-left"></i> <span data-i18n="tab_response">Respons</span></button>
                            <button class="op-tab-btn" data-tab="error" type="button"><i class="bi bi-exclamation-triangle"></i> <span data-i18n="tab_error">Error</span></button>
                        </div>

                        <div class="op-tab is-on" data-tab="live">
                            <div class="alert-mini info" style="margin-bottom:14px;">
                                💡 <span data-i18n="gp_hint">Dapat <code>PlaceId</code> dulu dari Try it Live SearchText/Suggest, copy hasil <code>ResultItems[0].PlaceId</code>, paste ke sini.</span>
                            </div>
                            <div data-builder="places-get-place"></div>

                            {{-- Formulir asli tetap ada tapi disembunyikan: skrip panel ini
                                 membacanya untuk merangkai URL proxy, dan builder yang
                                 mengisinya sebelum menekan tombol kirim. --}}
                            <div class="tryit-engine" hidden>
                                <div class="try-it">
                                    <div class="try-it-pane right" style="border-right:0;">
                                        <div class="try-it-pane-header">
                                            <span><i class="bi bi-link-45deg"></i> <span data-i18n="gp_query_params">Query Parameters</span></span>
                                        </div>
                                        <div class="try-it-url">
                                            <div><span class="try-it-method GET">GET</span><span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/place/&lt;PlaceId&gt;</span></div>
                                            <div><span class="try-it-method" style="background:#10b981;">VIA</span><span style="color:#86efac;">/api/places/&lt;PlaceId&gt;</span></div>
                                        </div>
                                        <div style="padding-top:10px;display:flex;flex-direction:column;gap:8px;">
                                            <div>
                                                <label style="font-size:0.74rem;color:#cbd5e1;font-weight:600;">PlaceId <span style="color:#ef4444;">*</span></label>
                                                <input id="gp-id" placeholder="Paste PlaceId (e.g. AQABA...)" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-family:monospace;font-size:0.78rem;">
                                            </div>
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                                <div>
                                                    <label style="font-size:0.74rem;color:#cbd5e1;font-weight:600;">language</label>
                                                    <select id="gp-lang" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-size:0.84rem;">
                                                        <option value="id">id</option>
                                                        <option value="en">en</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label style="font-size:0.74rem;color:#cbd5e1;font-weight:600;">additional-features</label>
                                                    <input id="gp-feat" value="TimeZone" placeholder="TimeZone,Contact,Hours" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-family:monospace;font-size:0.78rem;">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                                            <button class="btn-send" id="gp-run" type="button"><i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span></button>
                                            <span id="gp-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">⏳ <span data-i18n="loading">Loading</span>...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="doc-section-h"><span class="ic orange"><i class="bi bi-broadcast"></i></span> <span data-i18n="bld_result">Hasil dari AWS</span></div>
                            <div class="resp-bar">
                                <span style="font-weight:700;color:var(--text-primary);">Response</span>
                                <span class="status-pill idle" id="gp-status">— idle —</span>
                                <span class="meta" id="gp-meta"></span>
                            </div>
                            <div id="gp-resp" class="resp-body empty" data-i18n="gp_resp_idle">Masukkan PlaceId lalu klik Send Request.</div>
                        </div>

                        <div class="op-tab" data-tab="request">
                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                            <pre><code class="language-bash">GET https://places.geo.{region}.amazonaws.com/v2/place/{PlaceId}
          ?key=&lt;API_KEY&gt;
          &amp;additional-features=TimeZone     # tier: Advanced
          &amp;language=id
          &amp;intended-use=Storage             # tier: Stored</code></pre>
                            <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_gp_live">Baris yang diberi pil tier menentukan keranjang harga. Tanpa keduanya, panggilan masuk <b>Core</b> dan <code>intended-use</code> dianggap <code>SingleUse</code>. Di <code>ap-southeast-1</code> <code>intended-use=Storage</code> ditolak 400.</span></p>

                            <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Query Parameters</span></div>
                            <table class="param-table">
                                <thead>
                                    <tr>
                                        <th>Param</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>{PlaceId}</code></td>
                                        <td><span class="type-tag">path</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td data-i18n-html="note_placeid_path">Path segment (URL-encoded). From SearchText/Suggest.</td>
                                    </tr>
                                    <tr>
                                        <td><code>key</code></td>
                                        <td><span class="type-tag">query</span></td>
                                        <td><span class="req">YES</span></td>
                                        <td data-i18n="note_api_key">API key</td>
                                    </tr>
                                    <tr>
                                        <td><code>additional-features</code></td>
                                        <td><span class="type-tag">query</span></td>
                                        <td>—</td>
                                        <td><span class="tier-pill tier-advanced">Advanced</span> <span data-i18n-html="gp_p_addfeat_live">Comma-separated: <code>TimeZone</code>, <code>Contact</code>, <code>Access</code>, <code>Phonemes</code>, <code>SecondaryAddresses</code>. Di <code>ap-southeast-1</code> hanya <code>TimeZone</code> yang dilayani.</span></td>
                                    </tr>
                                    <tr>
                                        <td><code>language</code></td>
                                        <td><span class="type-tag">query</span></td>
                                        <td>—</td>
                                        <td data-i18n-html="note_bcp47">BCP 47 (e.g. <code>id</code>, <code>en</code>)</td>
                                    </tr>
                                </tbody>
                            </table>

                            <p class="syn-legend"><i class="bi bi-tag-fill"></i> <span data-i18n-html="syn_legend_places">Baris yang diberi pil tier menentukan keranjang harga. Tanpa satu pun penanda, panggilan masuk <b>Core</b>; <b>Stored</b> hanya kalau <code>IntendedUse</code> diisi <code>Storage</code>.</span></p>
                        </div>

                        <div class="op-tab" data-tab="response">
                            <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
                            <pre><code class="language-json">{
      "PlaceId": "string",
      "PlaceType": "string",
      "Title": "string",
      "Address": {
        "Label": "string",
        "Country": { "Code2": "string", "Name": "string" },
        "Region": { "Code": "string", "Name": "string" },
        "Locality": "string",
        "PostalCode": "string"
      },
      "Position": [ number, number ],
      "MapView": [ number, number, number, number ],
      "Categories": [ { "Id": "string", "Name": "string", "Primary": boolean } ],
      "Contacts": {
        "Phones": [ { "Value": "string" } ],
        "Websites": [ { "Value": "string" } ]
      },
      "OpeningHours": [
        { "Display": [ "string" ], "OpenNow": boolean }
      ],
      "TimeZone": { "Name": "string", "Offset": "string", "OffsetSeconds": number }
    }</code></pre>

                            <p class="syn-legend"><i class="bi bi-info-circle"></i> <span data-i18n-html="gp_resp_note">Balasannya objek tunggal, bukan <code>ResultItems</code>. Tidak ada <code>Distance</code> — GetPlace tidak punya titik acuan. <code>PricingBucket</code> dikirim sebagai header <code>x-amz-geo-pricing-bucket</code>.</span></p>
                        </div>

                        <div class="op-tab" data-tab="error">
                            <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                            <table class="error-table">
                                <thead>
                                    <tr>
                                        <th data-i18n="err_status">Status</th>
                                        <th data-i18n="err_trigger">Trigger</th>
                                        <th data-i18n="err_message">AWS Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n="gp_err_badid">PlaceId tidak sah, dipotong, atau salah ketik</td>
                                        <td><em>"'PlaceId' must be a valid ID."</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">400</span></td>
                                        <td data-i18n-html="gp_err_unsupported"><code>additional-features</code> berisi value yang gak disupport region</td>
                                        <td><em>"Unsupported AdditionalFeatures..."</em></td>
                                    </tr>
                                    <tr>
                                        <td><span class="err-code">403</span></td>
                                        <td data-i18n-html="gp_err_perm">API Key tidak punya <code>geo-places:GetPlace</code></td>
                                        <td><em>"explicit deny"</em></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <script>
                            (function() {
                                const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                const $ = id => document.getElementById(id);
                                $('gp-run').addEventListener('click', async () => {
                                    const id = $('gp-id').value.trim();
                                    const lang = $('gp-lang').value;
                                    const feat = $('gp-feat').value.trim();
                                    const btn = $('gp-run'),
                                        resp = $('gp-resp'),
                                        st = $('gp-status'),
                                        mt = $('gp-meta'),
                                        sp = $('gp-spinner');
                                    if (!id) {
                                        resp.className = 'resp-body error';
                                        resp.textContent = '❌ PlaceId required';
                                        st.textContent = 'MISSING PlaceId';
                                        st.className = 'status-pill bad';
                                        return;
                                    }
                                    btn.disabled = true;
                                    sp.style.display = 'inline-block';
                                    st.textContent = '...';
                                    st.className = 'status-pill idle';
                                    mt.textContent = '';
                                    resp.className = 'resp-body';
                                    resp.textContent = '⏳';
                                    const url = `/api/places/${encodeURIComponent(id)}?language=${encodeURIComponent(lang)}` + (feat ? `&additional-features=${encodeURIComponent(feat)}` : '');
                                    const t0 = performance.now();
                                    try {
                                        const r = await fetch(url, {
                                            headers: {
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': CSRF
                                            }
                                        });
                                        const ms = Math.round(performance.now() - t0);
                                        const d = await r.json();
                                        st.textContent = `${r.status} ${r.statusText}`;
                                        st.className = `status-pill ${r.ok?'ok':'bad'}`;
                                        mt.innerHTML = `<b>${ms}ms</b>`;
                                        if (!r.ok) resp.classList.add('error');
                                        resp.textContent = JSON.stringify(d, null, 2);
                                    } catch (e) {
                                        resp.classList.add('error');
                                        resp.textContent = 'Error: ' + e.message;
                                        st.textContent = 'NETWORK ERROR';
                                        st.className = 'status-pill bad';
                                    } finally {
                                        btn.disabled = false;
                                        sp.style.display = 'none';
                                    }
                                });
                            })();
                        </script>

                    </div> {{-- end v2 --}}

                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/places/v0/indexes/{Idx}/places/{PlaceId}?key=...</span></div>
                        <div class="alert-mini warn" data-i18n-html="gp_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Path beda: <code>/places/v0/indexes/{Idx}/places/{PlaceId}</code></li>
                                <li>Tidak ada <code>additional-features</code> param di v0</li>
                                <li>Response shape lebih simple, tanpa <code>OpeningHours</code>, <code>Contacts</code>, <code>TimeZone</code></li>
                            </ul>
                        </div>
                    </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- Autocomplete -->
            <div class="op-panel" id="op-places-autocomplete">
                <div class="breadcrumb-mini">Places V2 / Autocomplete</div>
                <h1>Autocomplete <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n-html="ac_desc">Type-ahead khusus untuk <strong>alamat</strong> (street, address number, postal code) — bukan untuk POI.</p>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/autocomplete?key=...</span></div>

                <h4 data-i18n="label_request_body">Request body</h4>
                <pre><code class="language-json">{
  "QueryText": "Jl. Sudirman",
  "BiasPosition": [106.84, -6.20],
  "Filter": {
    "IncludeCountries": ["IDN"],
    "IncludePlaceTypes": ["Street", "PointAddress"]
  },
  "MaxResults": 5,
  "Language": "id"
}</code></pre>

                <h4 data-i18n="label_response_no_pos">Response (does NOT return Position)</h4>
                <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Jl. Jenderal Sudirman, Jakarta",
    "PlaceId": "AQAB...",
    "Address": { "Label": "..." },
    "Distance": 1250,
    "Highlights": { ... }
  }]
}</code></pre>

                <div class="alert-mini warn" data-i18n-html="ac_no_position">
                    ⚠️ <strong>Does not return <code>Position</code></strong> — must call GetPlace per item if you need coordinates. For finding POIs, <strong>Suggest</strong> is more efficient (returns Position directly).
                </div>
                <div class="alert-mini soon" data-i18n-html="soon_autocomplete">
                    <span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong> at the moment. Check AWS Console for available actions.
                </div>

                <h3 data-i18n="ac_inc_types">IncludePlaceTypes valid values</h3>
                <p><code>Locality</code> | <code>PostalCode</code> | <code>Intersection</code> | <code>Street</code> | <code>PointAddress</code> | <code>InterpolatedAddress</code></p>
                <p data-i18n-html="ac_no_poi"><code>PointOfInterest</code> is <strong>not valid</strong> in Autocomplete — use SearchText / SearchNearby.</p>
            </div>

            <!-- Geocode -->
            <div class="op-panel" id="op-places-geocode">
                <div class="breadcrumb-mini">Places V2 / Geocode</div>
                <h1>Geocode <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="gc_desc">Alamat terstruktur (street, city, postal) → koordinat. Lebih akurat dari SearchText untuk address lookup karena input-nya sudah parsed.</p>

                <div class="alert-mini soon" data-i18n-html="soon_geocode"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Workaround: use <code>SearchText</code> with structured QueryText.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/geocode?key=...</span></div>

                <h4 data-i18n="label_request_body">Request body</h4>
                <pre><code class="language-json">{
  "QueryComponents": {
    "Country": "IDN",
    "Region": "Jakarta",
    "Locality": "Jakarta Selatan",
    "Street": "Jl. Sudirman",
    "AddressNumber": "1",
    "PostalCode": "10220"
  },
  "MaxResults": 1
}</code></pre>

                <h4 data-i18n="label_response">Response</h4>
                <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Jl. Sudirman 1, Jakarta",
    "Position": [106.823, -6.224],
    "Address": { ... },
    "MatchScores": { "Overall": 0.95 }
  }]
}</code></pre>
                <div class="alert-mini info" data-i18n="gc_use_case">
                    💡 Use this when you already have field-by-field address (e.g. from a form input). For free-text search, use SearchText.
                </div>
            </div>

            <!-- SearchNearby -->
            <div class="op-panel" id="op-places-search-nearby">
                <div class="breadcrumb-mini">Places V2 / SearchNearby</div>
                <h1>SearchNearby <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="sn_desc">Find POI dalam radius dari satu titik, opsional filter by category. Tidak butuh QueryText — cuma "tunjukin yang dekat tipe X".</p>

                <div class="alert-mini soon" data-i18n-html="soon_nearby"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Workaround: use <code>SearchText</code> with <code>Filter.Circle</code> + category keyword in QueryText.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/search-nearby?key=...</span></div>

                <h4 data-i18n="label_request_body">Request body</h4>
                <pre><code class="language-json">{
  "QueryPosition": [106.84, -6.20],
  "QueryRadius": 1000,
  "Filter": {
    "IncludeCategories": ["transit_station_bus"]
  },
  "MaxResults": 20,
  "Language": "id"
}</code></pre>

                <h4 data-i18n="label_response">Response</h4>
                <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Halte Transjakarta Halimun",
    "PlaceId": "AQAB...",
    "Position": [106.85, -6.24],
    "Distance": 850,
    "Categories": [{ "Id": "transit_station_bus", "Primary": true }]
  }]
}</code></pre>

                <div class="alert-mini success">
                    ✅ <span data-i18n-html="sn_use_case">Untuk use case <strong>"halte terdekat"</strong> ini paling direct: 1 API call, sorted by distance, gak perlu QueryText. Prasyaratnya kamu tau ID kategori (mis. <code>transit_station_bus</code>).</span>
                </div>

                <h3 data-i18n="label_notes">Notes</h3>
                <ul>
                    <li>Max <code>QueryRadius</code> = <strong>50,000 m</strong> (50 km)</li>
                    <li>Max <code>MaxResults</code> = <strong>20</strong></li>
                </ul>
            </div>

            {{-- =============================================================== --}}
            {{-- META PANELS                                                    --}}
            {{-- =============================================================== --}}

            <!-- Overview -->
            <div class="op-panel" id="op-meta-overview">
                <div class="breadcrumb-mini">General / Overview</div>
                <h1 data-i18n="meta_overview">Overview v0 vs v2</h1>
                <p class="op-desc" data-i18n="ov_desc">Dua generation API yang masih jalan paralel. v2 = standalone mode (recommended), v0 = legacy resource-based.</p>

                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Aspek</th>
                            <th>v0 Legacy</th>
                            <th>v2 Standalone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b>Setup AWS Console</b></td>
                            <td data-i18n="meta_setup_v0">Must create Map / PlaceIndex / RouteCalculator first</td>
                            <td data-i18n="meta_setup_v2">Not needed — works immediately</td>
                        </tr>
                        <tr>
                            <td><b>Hostname</b></td>
                            <td data-i18n-html="meta_host_v0"><code>maps.geo.{region}.amazonaws.com</code> (shared)</td>
                            <td data-i18n-html="meta_host_v2">Per service: <code>maps.geo</code>, <code>places.geo</code>, <code>routes.geo</code></td>
                        </tr>
                        <tr>
                            <td><b>Path version</b></td>
                            <td data-i18n-html="meta_path_v0"><code>/{service}/v0/...</code></td>
                            <td data-i18n-html="meta_path_v2"><code>/v2/...</code></td>
                        </tr>
                        <tr>
                            <td><b>Provider</b></td>
                            <td data-i18n="meta_provider_v0">Lock per resource</td>
                            <td data-i18n="meta_provider_v2">Auto-picked per region</td>
                        </tr>
                        <tr>
                            <td><b>Status</b></td>
                            <td data-i18n="meta_status_v0">⚠️ Maintenance only</td>
                            <td data-i18n="meta_status_v2">✅ Active development</td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert-mini success">
                    <span data-i18n-html="overview_recommendation"><strong>Rekomendasi:</strong> Project baru pakai <strong>v2</strong>. Project existing tetap aman di v0 sampai AWS announce deprecation.</span>
                </div>
            </div>

            <!-- Auth -->
            <div class="op-panel" id="op-meta-auth">
                <div class="breadcrumb-mini">General / Authentication</div>
                <h1 data-i18n="meta_auth">Authentication</h1>
                <p class="op-desc" data-i18n="auth_desc">Dua-duanya support API Key (recommended frontend) atau AWS SigV4 (backend).</p>

                <h3 data-i18n="label_api_key_url">API Key in URL</h3>
                <pre><code>?key=v1.public.eyJq...</code></pre>

                <h3 data-i18n="label_resource_arn">Resource ARN (per service)</h3>
                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th class="cmp-legacy">v0 <span class="ver-tag ver-legacy" data-i18n="ver_legacy">Legacy</span></th>
                            <th class="cmp-rec">v2 <span class="ver-tag ver-rec" data-i18n="ver_recommended">Recommended</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Maps</td>
                            <td><code>arn:aws:geo:...:map/{Name}</code></td>
                            <td><code>arn:aws:geo-maps:...::provider/default</code></td>
                        </tr>
                        <tr>
                            <td>Places</td>
                            <td><code>arn:aws:geo:...:place-index/{Name}</code></td>
                            <td><code>arn:aws:geo-places:...::provider/default</code></td>
                        </tr>
                        <tr>
                            <td>Routes</td>
                            <td><code>arn:aws:geo:...:route-calculator/{Name}</code></td>
                            <td><code>arn:aws:geo-routes:...::provider/default</code></td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert-mini warn">
                    ⚠️ <span data-i18n-html="auth_actions_note">Saat bikin API Key di AWS Console, centang <strong>actions</strong> per service yang dibutuhkan. Action yang gak dicentang akan return <code>403 Forbidden</code> (explicit deny).</span>
                </div>
            </div>

            <!-- Quotas -->
            <div class="op-panel" id="op-meta-quotas">
                <div class="breadcrumb-mini">General / Quotas &amp; Limits</div>
                <h1 data-i18n="meta_quotas">Quotas &amp; Limits</h1>
                <p class="op-desc" data-i18n="qu_desc">Limit per request untuk API Location v2.</p>

                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Operation</th>
                            <th>Param</th>
                            <th>Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SearchText</td>
                            <td>MaxResults</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>Suggest</td>
                            <td>MaxResults</td>
                            <td>10</td>
                        </tr>
                        <tr>
                            <td>ReverseGeocode</td>
                            <td>MaxResults</td>
                            <td>4</td>
                        </tr>
                        <tr>
                            <td>SearchNearby</td>
                            <td>MaxResults</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td data-i18n="qu_all_search">All Search</td>
                            <td>Filter.Circle.Radius</td>
                            <td>50,000 m</td>
                        </tr>
                        <tr>
                            <td>CalculateRoutes</td>
                            <td>Waypoints</td>
                            <td>23</td>
                        </tr>
                        <tr>
                            <td>CalculateRouteMatrix</td>
                            <td>Origins × Destinations</td>
                            <td>700 cells</td>
                        </tr>
                        <tr>
                            <td data-i18n="qu_rate_label">Rate limit (default)</td>
                            <td>—</td>
                            <td data-i18n="qu_tps">50 TPS / account (Places)</td>
                        </tr>
                    </tbody>
                </table>

                <p>Lengkap: <a href="https://docs.aws.amazon.com/location/latest/developerguide/location-quotas.html" target="_blank">Service Quotas</a></p>
            </div>

            <!-- Migration -->
            <div class="op-panel" id="op-meta-migration">
                <div class="breadcrumb-mini">General / Migration Guide</div>
                <h1 data-i18n="meta_migration">Migration v0 → v2</h1>
                <p class="op-desc" data-i18n="mig_desc">Checklist 3-step untuk pindahin code dari v0 (resource-based) ke v2 (standalone).</p>

                <h3>1. Maps</h3>
                <ul>
                    <li>Hapus dependency ke <code>AWS_MAP_NAME</code></li>
                    <li>Ganti URL style ke <code>/v2/styles/Standard/descriptor</code></li>
                    <li>Tambah API Key actions: <code>geo-maps:*</code></li>
                </ul>

                <h3>2. Places</h3>
                <ul>
                    <li>Hapus <code>AWS_MAP_PLACE</code> (PlaceIndex)</li>
                    <li>Endpoint: <code>/places/v0/indexes/.../search/text</code> → <code>/v2/search-text</code></li>
                    <li>Body: <code>Text</code> → <code>QueryText</code>, <code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                    <li>Response: <code>Results</code> → <code>ResultItems</code>, <code>Place.Geometry.Point</code> → <code>Position</code></li>
                </ul>

                <h3>3. Routes</h3>
                <ul>
                    <li>Hapus <code>AWS_MAP_ROUTE</code> (RouteCalculator)</li>
                    <li>Endpoint ke <code>/v2/routes</code> &amp; <code>/v2/route-matrix</code></li>
                    <li>Body: <code>DeparturePosition</code> → <code>Origin</code>, <code>WaypointPositions</code> → <code>Waypoints: [{Position}]</code></li>
                    <li>TravelMode: <code>Motorcycle</code>/<code>Walking</code> → <code>Scooter</code>/<code>Pedestrian</code></li>
                    <li>Distance v0 (km) → v2 (meter), <code>DurationSeconds</code> → <code>Duration</code></li>
                    <li>Matrix: tambah <code>RoutingBoundary: {Unbounded: true}</code></li>
                </ul>

                <div class="alert-mini info" data-i18n-html="mig_tip">
                    💡 Test on a separate endpoint first (e.g. <a href="/transjakarta-test">/transjakarta-test</a>) before migrating production code.
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <script src="{{ asset('javascript/docs/aws-api-schemas.js') }}?v={{ filemtime(public_path('javascript/docs/aws-api-schemas.js')) }}"></script>
    <script src="{{ asset('javascript/docs/aws-api-builder.js') }}?v={{ filemtime(public_path('javascript/docs/aws-api-builder.js')) }}"></script>

    <script>
        // Ubah komentar "// tier: Advanced" di blok Request Syntax jadi pil warna,
        // supaya baris yang menentukan keranjang harga langsung kelihatan tanpa
        // harus membaca kartu tier di atasnya. Prism menandai komentar itu
        // sebagai .token.comment; kita cuma menukar isinya.
        (function () {
            const TIERS = ['Label', 'Core', 'Advanced', 'Premium', 'Stored'];

            function decorate(root) {
                root.querySelectorAll('.token.comment').forEach(node => {
                    if (node.classList.contains('syn-tier')) return;
                    const m = node.textContent.match(/^(?:\/\/|#)\s*tier:\s*(.+)$/);
                    if (!m) return;

                    const names = m[1].split('/').map(n => n.trim()).filter(n => TIERS.includes(n));
                    if (!names.length) return;

                    node.classList.add('syn-tier');
                    node.textContent = '';
                    names.forEach(name => {
                        const pill = document.createElement('span');
                        pill.className = 'tier-pill tier-' + name.toLowerCase();
                        pill.textContent = name;
                        node.appendChild(pill);
                    });
                });
            }

            // Autoloader mewarnai tiap blok secara asinkron, jadi hook 'complete'
            // yang jadi pegangan utama; sapuan awal untuk blok yang keburu selesai.
            if (window.Prism) Prism.hooks.add('complete', env => decorate(env.element.parentElement || document));
            document.addEventListener('DOMContentLoaded', () => decorate(document));
        })();
    </script>

    <script>
        // ===== Sidebar collapse/expand =====
        document.querySelectorAll('.service-header').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentElement.classList.toggle('collapsed');
            });
        });

        // ===== Operation switching =====
        function showOp(op) {
            document.querySelectorAll('.op-panel').forEach(p => p.classList.remove('active'));
            // Operation yang ditandai unavail → arahkan ke shared "Coming Soon" panel
            const link = document.querySelector(`.op-link[data-op="${op}"]`);
            const isUnavail = link && link.classList.contains('unavail');
            const targetId = isUnavail ? 'op-coming-soon' : 'op-' + op;
            const target = document.getElementById(targetId);
            if (target) target.classList.add('active');
            // Update title dinamis di Coming Soon panel
            if (isUnavail) {
                const opTitle = link.textContent.trim().replace(/Soon$/i, '').trim();
                document.getElementById('comingSoonTitle').textContent = opTitle;
            }
            document.querySelectorAll('.op-link').forEach(a => a.classList.remove('active'));
            if (link) {
                link.classList.add('active');
                link.closest('.service-group')?.classList.remove('collapsed');
            }
            // Reset main scroll
            document.querySelector('.main').scrollTop = 0;
            // Update URL hash
            history.replaceState(null, '', '#' + op);
        }

        document.querySelectorAll('.op-link').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                if (a.dataset.op) showOp(a.dataset.op);
            });
        });

        // ===== v0/v2 tab switcher inside panels =====
        document.querySelectorAll('.ver-tabs').forEach(tabs => {
            const buttons = tabs.querySelectorAll('button');
            const content = tabs.nextElementSibling;
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    buttons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    content.querySelectorAll(':scope > div').forEach(d => d.classList.remove('active'));
                    content.querySelector(`:scope > div[data-version="${btn.dataset.version}"]`)?.classList.add('active');
                });
            });
        });

        // ===== Search filter — with highlight + empty state + group auto-hide =====
        const SEARCH_HL_CLASS = 'match-hl';

        function clearHighlights() {
            document.querySelectorAll('.op-link .' + SEARCH_HL_CLASS).forEach(span => {
                const parent = span.parentNode;
                parent.replaceChild(document.createTextNode(span.textContent), span);
                parent.normalize();
            });
        }

        function highlightInLink(link, query) {
            // Only operate on the leaf text node(s) inside <a>, skip <span class="badge-soon">
            link.childNodes.forEach(node => {
                if (node.nodeType !== Node.TEXT_NODE) return;
                const text = node.textContent;
                const lower = text.toLowerCase();
                const idx = lower.indexOf(query);
                if (idx === -1) return;
                const before = document.createTextNode(text.slice(0, idx));
                const match = document.createElement('span');
                match.className = SEARCH_HL_CLASS;
                match.textContent = text.slice(idx, idx + query.length);
                const after = document.createTextNode(text.slice(idx + query.length));
                const parent = node.parentNode;
                parent.insertBefore(before, node);
                parent.insertBefore(match, node);
                parent.insertBefore(after, node);
                parent.removeChild(node);
            });
        }

        document.getElementById('searchBox').addEventListener('input', e => {
            const q = e.target.value.toLowerCase().trim();
            clearHighlights();

            let totalVisible = 0;
            document.querySelectorAll('.op-link').forEach(a => {
                const txt = a.textContent.toLowerCase();
                const li = a.parentElement;
                const matches = !q || txt.includes(q);
                li.style.display = matches ? '' : 'none';
                if (matches) totalVisible++;
                if (matches && q) highlightInLink(a, q);
            });

            // Hide groups that have no visible op-links
            document.querySelectorAll('.service-group').forEach(g => {
                if (q) {
                    g.classList.remove('collapsed');
                    const visibleOps = g.querySelectorAll('.operations > li:not([style*="display: none"])').length;
                    g.style.display = visibleOps > 0 ? '' : 'none';
                } else {
                    g.style.display = '';
                }
            });

            // Show/hide empty state
            const empty = document.getElementById('sidebarSearchEmpty');
            if (empty) empty.style.display = (q && totalVisible === 0) ? 'block' : 'none';
        });

        // ===== Init from URL hash (with hashchange listener for sharing) =====
        function handleHashNav() {
            const hash = window.location.hash.replace('#', '').replace(/^op-/, '');
            if (hash) showOp(hash);
        }
        handleHashNav(); // initial
        window.addEventListener('hashchange', handleHashNav);

        /* ============================================================
           API KEY INSPECTOR
           ============================================================ */
        const KEY_STORAGE = 'awsapi_user_key';

        /** Convert op-id (e.g. "maps-get-tile") to AWS action ("Maps.GetTile") */
        function opToAction(opId) {
            const parts = opId.split('-');
            const service = parts[0]; // maps / places / routes / meta
            if (service === 'meta') return null; // General docs aren't real ops
            const Service = service.charAt(0).toUpperCase() + service.slice(1);
            const action = parts.slice(1).map(p => p.charAt(0).toUpperCase() + p.slice(1)).join('');
            return Service + '.' + action;
        }

        function loadUserKey() {
            try {
                return JSON.parse(localStorage.getItem(KEY_STORAGE) || 'null');
            } catch (_) {
                return null;
            }
        }

        function saveUserKey(data) {
            if (!data) localStorage.removeItem(KEY_STORAGE);
            else localStorage.setItem(KEY_STORAGE, JSON.stringify(data));

            // Halaman /tester-api membaca kunci docs lebih dulu, tapi masih punya
            // simpanannya sendiri. Ditulis bersamaan supaya keduanya tidak pernah
            // berbeda — termasuk saat kuncinya dihapus dari sini.
            try {
                if (data && data.apiKey) localStorage.setItem('tester_aws_api_key', data.apiKey);
                else localStorage.removeItem('tester_aws_api_key');
            } catch (_) {
                /* mode privat */ }
        }

        // Kunci yang diubah di tab lain (mis. halaman tester) langsung terpasang.
        window.addEventListener('storage', (e) => {
            if (e.key !== KEY_STORAGE && e.key !== 'tester_aws_api_key') return;
            if (typeof applyKeyToUI === 'function') applyKeyToUI();
        });

        // Tombol intip isi kunci, sama seperti di gerbang key halaman tester.
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#keyPeekBtn');
            if (!btn) return;
            const input = document.getElementById('keyForm_apiKey');
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.innerHTML = showing
                ? '<i class="bi bi-eye" id="keyPeekIcon"></i>'
                : '<i class="bi bi-eye-slash" id="keyPeekIcon"></i>';
        });

        function daysUntil(isoStr) {
            if (!isoStr) return null;
            const target = new Date(isoStr).getTime();
            if (isNaN(target)) return null;
            return Math.floor((target - Date.now()) / (1000 * 60 * 60 * 24));
        }

        /** Apply current key state to sidebar dots + topbar pill + form values */
        function applyKeyToUI() {
            const key = loadUserKey();
            const btn = document.getElementById('btnKeyInspector');
            const label = document.getElementById('keyInspectorLabel');
            const pill = document.getElementById('keyInspectorPill');

            // Clear all existing key-* classes
            document.querySelectorAll('.op-link').forEach(a => {
                a.classList.remove('key-allowed', 'key-denied');
            });

            const dot = document.getElementById('keyInspectorDot');

            if (!key) {
                btn.classList.remove('has-key');
                label.textContent = window.AWSAPI_I18N && window.AWSAPI_I18N.t ? window.AWSAPI_I18N.t('key_inspector_btn') : 'My Key';
                pill.style.display = 'none';
                if (dot) dot.classList.remove('is-ok');
                btn.dataset.tip = label.textContent;
                return;
            }

            // Has key — update topbar
            btn.classList.add('has-key');
            label.textContent = key.name || 'API Key';
            if (dot) dot.classList.add('is-ok');
            btn.dataset.tip = label.textContent;
            const days = daysUntil(key.expirationUtc);
            if (days !== null) {
                pill.style.display = 'inline-block';
                if (days < 0) {
                    pill.textContent = '⚠ EXPIRED';
                    pill.className = 'key-pill';
                } else if (days <= 30) {
                    pill.textContent = days + 'd';
                    pill.className = 'key-pill warn';
                } else {
                    pill.textContent = days + 'd';
                    pill.className = 'key-pill ok';
                }
            } else {
                pill.style.display = 'none';
            }

            // Tandai operasi di sidebar. Daftar aksi itu isian opsional di bagian
            // "Lanjutan" — kalau pengguna belum mengisinya, izin key-nya memang
            // tidak diketahui, jadi jangan ada yang dicoret. Dulu keadaan kosong
            // ini membuat semua operasi tampak ditolak.
            const allowed = key.allowed || {};
            const adaDaftarAksi = Object.values(allowed).some(list => Array.isArray(list) && list.length);
            if (!adaDaftarAksi) return;

            document.querySelectorAll('.op-link[data-op]').forEach(a => {
                const action = opToAction(a.dataset.op);
                if (!action) return; // meta items
                const [svc, act] = action.split('.');
                const allowList = allowed[svc] || [];
                if (allowList.includes(act)) a.classList.add('key-allowed');
                else a.classList.add('key-denied');
            });
        }

        /** Open modal + populate form from saved key */
        function openKeyModal() {
            const modal = document.getElementById('keyInspectorModal');
            const key = loadUserKey() || {};
            document.getElementById('keyForm_apiKey').value = key.apiKey || '';
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('keyForm_apiKey').focus(), 50);
        }

        /**
         * Modal ini sekarang cuma meminta kuncinya, sama seperti gerbang key di
         * /tester-api. Data lain yang mungkin masih tersimpan dari versi lama
         * (nama, masa berlaku, daftar aksi) ikut dibawa apa adanya supaya
         * penanda izin di sidebar milik pengguna lama tidak hilang.
         */
        function readKeyForm() {
            const before = loadUserKey() || {};
            return {
                ...before,
                region: 'ap-southeast-1',
                apiKey: document.getElementById('keyForm_apiKey').value.trim(),
            };
        }

        // Modal trigger
        document.getElementById('btnKeyInspector').addEventListener('click', openKeyModal);
        document.getElementById('keyModalClose').addEventListener('click', () => {
            document.getElementById('keyInspectorModal').style.display = 'none';
        });
        document.getElementById('keyInspectorModal').addEventListener('click', (e) => {
            if (e.target.id === 'keyInspectorModal') e.currentTarget.style.display = 'none';
        });

        // Save
        document.getElementById('keySaveBtn').addEventListener('click', () => {
            const form = readKeyForm();
            // Tidak ada lagi tombol "Clear" terpisah — mengosongkan kolom lalu
            // menekan Continue yang menghapus kunci dari browser ini.
            const data = form.apiKey ? form : null;
            saveUserKey(data);
            applyKeyToUI();
            document.getElementById('keyInspectorModal').style.display = 'none';
            // Expose to AWSAPI_TryIt for code snippet override + Send Request bypass
            window.AWSAPI_UserKey = data;
            if (window.AWSAPI_TryIt_refreshBadges) window.AWSAPI_TryIt_refreshBadges();
            window.dispatchEvent(new CustomEvent('AWSAPI_UserKeyChanged', {
                detail: data
            }));
        });

        // Dipertahankan untuk tombol lama di tempat lain (kalau ada).
        document.getElementById('keyClearBtn')?.addEventListener('click', () => {
            if (!confirm('Clear saved API Key from this browser?')) return;
            saveUserKey(null);
            window.AWSAPI_UserKey = null;
            applyKeyToUI();
            if (window.AWSAPI_TryIt_refreshBadges) window.AWSAPI_TryIt_refreshBadges();
            document.getElementById('keyInspectorModal').style.display = 'none';
            window.dispatchEvent(new CustomEvent('AWSAPI_UserKeyChanged', {
                detail: null
            }));
        });

        // Initial load
        const initialKey = loadUserKey();
        if (initialKey) window.AWSAPI_UserKey = initialKey;
        applyKeyToUI();
        // Refresh every hour (countdown updates)
        setInterval(applyKeyToUI, 60 * 60 * 1000);

        // === No-key banner control ===
        const noKeyBanner = document.getElementById('noKeyBanner');
        const btnKeyInspector = document.getElementById('btnKeyInspector');
        const nkbConfigureBtn = document.getElementById('nkbConfigureBtn');

        function updateNoKeyBanner() {
            const hasKey = !!(window.AWSAPI_UserKey && window.AWSAPI_UserKey.apiKey);
            noKeyBanner.classList.toggle('visible', !hasKey);
            btnKeyInspector.classList.toggle('needs-key', !hasKey);
            if (window.AWSAPI_TryIt_refreshBadges) window.AWSAPI_TryIt_refreshBadges();
        }
        // Configure now → open key inspector modal
        if (nkbConfigureBtn) {
            nkbConfigureBtn.addEventListener('click', () => btnKeyInspector.click());
        }
        // React to key changes (dispatched from Save/Clear handlers)
        window.addEventListener('AWSAPI_UserKeyChanged', updateNoKeyBanner);
        // Initial state
        updateNoKeyBanner();

        /* ============================================================
           I18N — pindah ke file: public/javascript/docs/aws-api-i18n.js
           Auto-init via window.AWSAPI_applyI18n / window.AWSAPI_I18N
           ============================================================ */
    </script>

    {{-- I18N module — auto-init di DOMContentLoaded --}}
    <script src="{{ asset('javascript/docs/aws-api-i18n.js') }}"></script>

    <script>
        // Sakelar tema — kunci gm-theme, sama dengan halaman lain.
        (function() {
            const group = document.getElementById('themeToggle');
            if (!group || !window.gmApplyTheme) return;

            const current = () => {
                try {
                    return localStorage.getItem('gm-theme') || 'system';
                } catch (e) {
                    return 'system';
                }
            };
            const paint = () => group.querySelectorAll('[data-theme-set]').forEach(
                b => b.classList.toggle('active', b.dataset.themeSet === current())
            );

            group.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-theme-set]');
                if (!btn) return;
                try {
                    localStorage.setItem('gm-theme', btn.dataset.themeSet);
                } catch (err) {
                    /* mode privat */ }
                window.gmApplyTheme(btn.dataset.themeSet);
                paint();
                btn.classList.add('pop');
                setTimeout(() => btn.classList.remove('pop'), 450);
                btn.blur();
            });

            paint();
        })();
    </script>

</body>

</html>