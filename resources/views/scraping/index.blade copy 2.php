<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>GrabMaps</title>

    <meta name="description" content="GrabMaps is an interactive map platform that provides accurate location information. Explore the map to discover interesting places." />
    <link rel="icon" href="images.png" type="image/x-icon" />

    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style-alert.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style-loading.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/search.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/tab.css') }}" />

    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>

    <style>
        .quota-badge {
            background: #fff;
            padding: 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .specs-tags {
            display: flex;
            gap: 10px;
        }

        .tag {
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .gemini-helper2 {
            width: 100%;
            display: flex;
            margin-top: -10px;
            justify-content: space-between;
            align-items: center;
            transition: opacity 0.3s;
        }

        /* --- Modern Progress Bar --- */
        .progress-wrapper {
            width: 100%;
            margin-top: 20px;
            background: #ffffff;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            display: none;
            box-sizing: border-box;
            border: 1px solid #f0f0f0;
            animation: fadeIn 0.3s ease;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .status-text {
            font-size: 13px;
            font-weight: 600;
            color: #444;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-text i {
            color: #00B14F;
        }

        .percent-text {
            font-size: 13px;
            font-weight: 700;
            color: #00B14F;
            background: #e8f5e9;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .progress-track {
            width: 100%;
            height: 8px;
            background-color: #f1f3f5;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            border-radius: 10px;
            background-color: #00B14F;
            background-image: linear-gradient(45deg,
                    rgba(255, 255, 255, 0.2) 25%,
                    transparent 25%,
                    transparent 50%,
                    rgba(255, 255, 255, 0.2) 50%,
                    rgba(255, 255, 255, 0.2) 75%,
                    transparent 75%,
                    transparent);
            background-size: 1rem 1rem;
            box-shadow: 0 0 10px rgba(0, 177, 79, 0.5);
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: progress-stripes 1s linear infinite;
        }

        @keyframes progress-stripes {
            from {
                background-position: 1rem 0;
            }

            to {
                background-position: 0 0;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .status-badge.success {
            color: #00B14F;
            background: #e8f5e9;
        }

        .status-badge.fail {
            color: #d9534f;
            background: #ffebee;
        }

        .btn-download-sm {
            border: 1px solid #00B14F;
            background: white;
            color: #00B14F;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .btn-download-sm:hover {
            background: #00B14F;
            color: white;
        }

        .fail-msg {
            font-size: 11px;
            color: #d9534f;
            font-style: italic;
        }

        /* --- History Button & Sidebar --- */
        .btn-history {
            background: transparent;
            border: 1px solid #ddd;
            padding: 8px 18px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            color: #555;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-history:hover {
            background: #f8f9fa;
            border-color: #bbb;
        }

        .btn-history.active {
            background: #e8f5e9;
            color: #00B14F;
            border-color: #00B14F;
        }

        .history-sidebar {
            position: fixed;
            top: 60px;
            right: -350px;
            /* Hidden initially */
            width: 320px;
            height: calc(100vh - 60px);
            background: white;
            box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 900;
            transition: right 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        }

        .history-sidebar.open {
            right: 0;
        }

        .history-header {
            padding: 15px 25px;
            margin-top: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-header h4 {
            margin: 0;
            color: #333;
        }

        .history-content {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .empty-history {
            text-align: center;
            color: #999;
            margin-top: 50px;
            font-size: 14px;
        }

        .history-item {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            transition: transform 0.2s;
            position: relative;
        }

        .history-item:hover {
            transform: translateX(-2px);
            border-color: #00B14F;
        }

        .h-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .h-address {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .h-badge {
            font-size: 10px;
            background: #e8f5e9;
            color: #00B14F;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .h-time {
            font-size: 11px;
            color: #999;
            display: block;
            text-align: right;
        }

        .split-layout {
            display: flex;
            flex-direction: row;
        }

        /* --- PANEL KIRI (SIDEBAR) --- */
        .sidebar-panel2 {
            width: 500px;
            /* Lebar tetap */
            min-width: 350px;
            height: 100%;
            background: #ffffff;
            border-right: 1px solid #e0e0e0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            box-sizing: border-box;
            /* overflow-y: auto; */
            /* Bisa discroll kalau isinya panjang */
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 20px;
            /* Jarak antar elemen */
        }

        /* Header kecil di sidebar */
        .sidebar-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
            font-weight: 700;
        }

        .sidebar-header .subtitle {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #888;
        }

        /* --- AREA KANAN (MAP) --- */
        .map-area {
            flex: 1;
            /* Mengambil sisa lebar layar */
            height: 100%;
            position: relative;
            background: #e5e5e5;
            /* Placeholder warna */
        }

        #map {
            width: 100%;
            height: 100%;
        }

        /* --- RESPONSIVE (MOBILE) --- */
        @media screen and (max-width: 768px) {
            .container-result.split-layout {
                flex-direction: column;
                /* Jadi Atas-Bawah di HP */
            }

            .sidebar-panel2 {
                width: 100%;
                height: 50%;
                /* Setengah layar untuk kontrol */
                order: 2;
            }

            .map-area {
                height: 50%;
                /* Setengah layar untuk Peta */
                order: 1;
                /* Peta di atas */
            }

            /* 1. Sembunyikan Teks "History" */
            .btn-history .btn-text {
                display: none;
            }

            /* 2. Ubah Tombol Jadi Bulat/Kotak Kecil */
            .btn-history {
                padding: 0;
                /* Reset padding */
                width: 36px;
                /* Lebar fix */
                height: 36px;
                /* Tinggi fix */
                border-radius: 50%;
                /* Jadi Bulat */
                justify-content: center;
                /* Ikon di tengah */
                gap: 0;
                /* Hapus jarak */
                border: 1px solid #ddd;
                /* Border tipis */
            }

            /* 3. Atur Posisi Badge (Notifikasi) Melayang */
            .btn-history .badge-count {
                position: absolute;
                top: -2px;
                right: -2px;
                background: #d9534f !important;
                /* Merah biar kelihatan */
                color: white;
                border: 1px solid white;
                font-size: 9px;
                padding: 1px 4px;
                min-width: 12px;
                text-align: center;
            }

            /* Perbesar sedikit ikonnya biar pas */
            .btn-history i {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div id="header">
        <a href="{{ route('pageHome') }}">
            <img src="logo.png" alt="GrabMaps Logo" style="cursor: pointer;">
        </a>

        <div class="header-buttons">
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
                        <span>Indonesian</span>
                    </div>
                </div>
            </div>

            <button class="btn-history" id="btnToggleHistory" onclick="toggleHistory()">
                <i class="fas fa-history"></i>
                <span class="btn-text">History</span>
                <span id="historyCountBadge" class="badge-count" style="display:none;">0</span>
            </button>

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

    <div class="container-result split-layout">

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

            <div class="gemini-helper">
                <div id="helperSection" class="gemini-helper2">
                    <div class="specs-tags">
                        <span class="tag"><i class="fas fa-bullseye"></i> Radius: 100m</span>
                        <span class="tag"><i class="fas fa-list-ol"></i> Max: 50 POI</span>
                    </div>
                    <div class="helper-text">Press <b>Enter</b> to extract</div>
                </div>
            </div>

            <!-- <div id="progressSection" class="progress-wrapper">
                <div class="progress-info">
                    <div class="status-text">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span id="progressStatus">Initializing...</span>
                    </div>
                    <span id="progressPercent" class="percent-text">0%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div> -->

            <div id="verificationResult" class="verification-container"></div>
        </div>

        <div class="map-area">
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

    </div>

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

        // --- 1. GLOBAL CONFIGURATION ---
        const deviceId = getOrCreateDeviceId();
        const STORAGE_KEY = `snippets_${deviceId}`;
        const QUOTA_KEY = `quota_${deviceId}`;
        const MAX_QUOTA = 5;

        // Variable global (atau passing via parameter) untuk mengontrol status loading
        // let scrapingState = {
        //     isComplete: false,
        //     data: null,
        //     error: null
        // };

        // Global State untuk sinkronisasi logika & visual
        let scrapingState = {
            stage: 'IDLE', // Pilihan: IDLE, FETCHING, PROCESSING, GENERATING, DONE, ERROR
            data: null, // Data bersih hasil olahan
            error: null
        };

        // Fungsi Helper untuk membuat jeda (agar loading tidak terlalu cepat kedipnya)
        const sleep = (ms) => new Promise(r => setTimeout(r, ms));

        // Load Quota
        let storedQuota = localStorage.getItem(QUOTA_KEY);
        let currentQuota = storedQuota === null ? MAX_QUOTA : parseInt(storedQuota);

        let map;
        let mapMarkers = []; // <--- Variable baru untuk menampung marker
        let clickMarker = null; // marker khusus hasil klik di map

        // --- 2. INITIALIZATION ---
        $(document).ready(function() {
            initializeMap();
            renderHistory();
            updateQuotaUI();

            showLoading("Loading Maps", "Initializing map components...");

            // Init external language if exists
            if (typeof initLanguage === "function") initLanguage();

            $('#geminiSearchInput').keypress(function(e) {
                if (e.which == 13 && !e.shiftKey) {
                    e.preventDefault();
                    performSearch();
                }
            });

            // --- SECRET FEATURE: FACTORY RESET (5x Clicks on Quota) ---
            let secretClickCount = 0;
            let secretClickTimer;

            $('#quotaBadge').attr('title', 'Secret Reset').on('click', function() {
                // Clear timer sebelumnya
                clearTimeout(secretClickTimer);

                secretClickCount++;

                if (secretClickCount >= 5) {
                    // === AKSI RESET TOTAL ===

                    // 1. Reset Quota ke 5
                    currentQuota = MAX_QUOTA;
                    localStorage.setItem(QUOTA_KEY, currentQuota);

                    // 2. HAPUS HISTORY (LocalStorage & Tampilan)
                    localStorage.removeItem(STORAGE_KEY); // Hapus data di browser
                    $('#historyListBody').empty(); // Kosongkan list di layar
                    $('#historyCard').fadeOut(); // Sembunyikan kotak history

                    // 3. Update Tampilan Quota
                    updateQuotaUI();

                    // 4. Hidupkan kembali Input
                    $('#btnSearchGemini').prop('disabled', false).css('background-color', '');
                    $('#geminiSearchInput').prop('disabled', false).attr('placeholder', 'Search for an address...');

                    // 5. Notifikasi
                    showToast("System Reset: Quota restored & History cleared!", "info");

                    // Reset counter klik
                    secretClickCount = 0;
                    return;
                }

                // Reset hitungan kalau diam selama 400ms
                secretClickTimer = setTimeout(() => {
                    secretClickCount = 0;
                }, 400);
            });
        });

        // --- 3. CORE LOGIC ---

        async function performSearch() {
            const address = $('#geminiSearchInput').val().trim();
            const texts = languageTexts[currentLanguage];

            // Input Validation
            if (!address) {
                showToast("Please enter an address first.", "error");
                return;
            }

            // Quota Validation
            if (currentQuota <= 0) {
                showToast("Quota limit reached (0/5). Please try again tomorrow.", "error");
                return;
            }

            //show list search address
            const resultContainer = $('#verificationResult');
            // Loading State
            resultContainer.show().html(`
                <div class="results-loading">
                    <i class="fas fa-spinner"></i>
                    <p>${texts.searching || "Searching..."}</p>
                </div>`);

            // get data address with geocode
            const resSearch = await searchGeocode(address.toLowerCase(), 10);
            processApiData(resSearch);
            updateMapMarkers(resSearch);
        }

        async function startProcess(address) {
            // 1. UI Setup
            $('#btnSearchGemini').prop('disabled', true);
            $('#geminiSearchInput').prop('disabled', true);
            $('#progressSection').fadeIn();
            $('#historyCard').fadeIn();

            // 2. Reset State
            scrapingState = {
                stage: 'FETCHING',
                data: null,
                error: null
            };

            // 3. Jalankan Visual (Simulate Progress)
            // Kita panggil tanpa await agar jalan paralel dengan logic di bawah
            simulateProgress(address);

            try {
                // --- TAHAP 1: FETCH API ---
                // (Visual akan mentok di 50% sampai baris ini selesai)
                const resSearch = await searchGeocode(address.toLowerCase(), 50);
                console.log("Data Mentah POI : ", resSearch);


                // --- TAHAP 2: PROCESSING DATA ---
                scrapingState.stage = 'PROCESSING'; // Sinyal ke Visual untuk maju ke 75%
                await sleep(800); // Jeda buatan agar user sempat baca teks "Processing..."

                const cleanData = processApiData(resSearch); // Panggil fungsi pengelola 1
                console.log("Hasil Data POI : ", cleanData);


                // --- TAHAP 3: GENERATING CSV ---
                scrapingState.stage = 'GENERATING'; // Sinyal ke Visual untuk maju ke 90%
                await sleep(800); // Jeda buatan lagi

                // (Opsional) Kita generate CSV stringnya di sini kalau mau disimpan
                // const csvString = formatCsvData(cleanData); 

                // --- TAHAP 4: DONE ---
                scrapingState.data = cleanData; // Simpan data bersih untuk history
                scrapingState.stage = 'DONE'; // Sinyal ke Visual untuk loncat ke 100%

            } catch (error) {
                console.error("Error scraping:", error);
                scrapingState.stage = 'ERROR';
                scrapingState.error = true;

                showToast("Error during process. Please try again.", "error");

                // Reset UI
                $('#progressSection').fadeOut();
                $('#btnSearchGemini').prop('disabled', false);
                $('#geminiSearchInput').prop('disabled', false);
            }
        }

        function simulateProgress(address) {
            let width = 0;
            const bar = $('#progressBar');
            const txt = $('#progressPercent');
            const status = $('#progressStatus');

            // Reset UI
            bar.css('width', '0%');
            txt.text('0%');
            status.text("Initializing...");

            function advanceProgress() {
                let increment = 0;
                let timeout = 100; // Default speed
                const currentStage = scrapingState.stage;

                // --- LOGIKA BERDASARKAN STAGE ---

                if (currentStage === 'FETCHING') {
                    // Tahap 1: Mentok di 50% kalau API belum respon
                    if (width < 50) {
                        increment = Math.random() * 2 + 1; // Naik pelan
                        status.text("Connecting to Maps API...");
                    } else {
                        increment = 0; // Stalling (diam menunggu API)
                        timeout = 500;
                        status.text("Waiting for server response...");
                    }
                } else if (currentStage === 'PROCESSING') {
                    // Tahap 2: API Selesai, sedang mengolah data JSON
                    // Loncatkan width ke minimal 50 jika belum
                    if (width < 50) width = 50;

                    if (width < 75) {
                        increment = 2; // Naik cepat
                        status.text("Cleaning & Filtering Data...");
                    } else {
                        increment = 0; // Tunggu sinyal berikutnya
                    }
                } else if (currentStage === 'GENERATING') {
                    // Tahap 3: Sedang membuat format CSV
                    if (width < 75) width = 75;

                    if (width < 95) {
                        increment = 2;
                        status.text("Generating CSV Format...");
                    } else {
                        increment = 0;
                    }
                } else if (currentStage === 'DONE') {
                    // Tahap 4: Selesai total
                    width = 100;
                    increment = 0;
                } else if (currentStage === 'ERROR') {
                    return; // Hentikan animasi
                }

                // Update Width
                width += increment;
                if (width > 100) width = 100;

                // Render ke Layar
                bar.css('width', width + '%');
                txt.text(Math.floor(width) + '%');

                // Cek Selesai Total
                if (width >= 100 && currentStage === 'DONE') {
                    status.text("Process Completed!");
                    setTimeout(() => {
                        // Panggil finishScraping dengan data bersih
                        finishScraping(address, scrapingState.data);
                    }, 600);
                } else {
                    // Loop terus selama belum error/selesai
                    if (currentStage !== 'ERROR') {
                        setTimeout(advanceProgress, timeout);
                    }
                }
            }

            advanceProgress();
        }

        function finishScraping(address, cleanResults) {
            // 1. Reset UI
            $('#progressSection').fadeOut();
            $('#btnSearchGemini').prop('disabled', false);
            $('#geminiSearchInput').prop('disabled', false).val('').focus();
            setTimeout(() => {
                $('#progressBar').css('width', '0%');
            }, 500);

            // 2. Logic Quota
            if (currentQuota > 0) {
                currentQuota--;
                localStorage.setItem(QUOTA_KEY, currentQuota);
                updateQuotaUI();
            }

            // 3. Simpan Data (cleanResults ini sudah array hasil olahan, bukan raw lagi)
            // Tapi karena fungsi saveToStorage kita sebelumnya menerima 'raw' dan mengolah sendiri,
            // Kita harus sedikit berhati-hati. 
            // OPSI TERBAIK: Kita sesuaikan saveToStorage agar menerima data yg sudah jadi.

            const count = cleanResults ? cleanResults.length : 0;
            saveCleanDataToStorage(address, count, cleanResults); // <-- Fungsi save baru (lihat bawah)

            // 4. Toast
            if (count > 0) {
                showToast(`Success! Prepared ${count} POIs for download.`, "success");
            } else {
                showToast("No POIs found.", "warning");
            }

            if (currentQuota <= 0) disableInterface();
        }

        // --- 4. STORAGE & HISTORY ---
        // Tambahkan parameter count
        // function saveToStorage(address, countResult) {
        //     let currentData = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];

        //     const snippet = {
        //         id: Date.now(),
        //         address: address,
        //         // Gunakan count asli, kalau undefined pake 0
        //         count: countResult || 0,
        //         createdAt: new Date().toISOString()
        //     };

        //     currentData.unshift(snippet);
        //     localStorage.setItem(STORAGE_KEY, JSON.stringify(currentData));
        //     renderHistory();
        // }
        function saveCleanDataToStorage(address, count, cleanItems) {
            let currentData = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];

            const snippet = {
                id: Date.now(),
                address: address,
                count: count,
                createdAt: new Date().toISOString(),
                items: cleanItems // Langsung simpan, karena sudah diolah di step Processing
            };

            currentData.unshift(snippet);
            if (currentData.length > 20) currentData.pop(); // Limit history

            localStorage.setItem(STORAGE_KEY, JSON.stringify(currentData));
            renderHistory();
        }

        function renderHistory() {
            const container = $('#historyListBody');
            const data = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
            container.empty();

            if (data.length === 0) return;

            data.forEach(item => {
                // Escape quotes for safety
                const safeAddress = item.address.replace(/'/g, "\\'");

                // DATE FORMAT TO ENGLISH (Standard)
                const date = new Date(item.createdAt);
                const locale = currentLanguage === 'en' ? 'en-US' : 'id-ID';
                const createdAtStr = date.toLocaleString(locale, {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const html = `
                <div class="history-item">
                    <div class="h-left">
                        <div class="h-icon-box success"><i class="fas fa-check"></i></div>
                        <div class="h-info">
                            <div class="h-address" title="${item.address}">${item.address}</div>
                            <div class="h-meta"><i class="far fa-clock"></i> ${createdAtStr}</div>
                        </div>
                    </div>
                    <div class="h-right">
                        <span class="status-badge success">${item.count} POIs</span>
                        <div style="margin-top:5px;">
                            <button class="btn-download-sm" onclick="showToast('Downloading CSV for: ${safeAddress}', 'info')">
                                <i class="fas fa-download"></i> CSV
                            </button>
                        </div>
                    </div>
                </div>
            `;
                container.append(html);
            });
            $('#historyCard').show();
        }

        // FUNGSI BARU 1: Mengelola Data API (Membersihkan JSON)
        function processApiData(rawResults) {
            // if (!rawResults || !Array.isArray(rawResults)) return [];

            // // Simulasi map data yang kompleks
            // return rawResults.map(item => {
            //     const p = item.Place || {};

            //     let displayTitle = p.Label;
            //     let displayAddress = p.Label;
            //     let oriAddress = p.Street;
            //     let oriAddressNumber = p.AddressNumber;

            //     if (typeof splitLabel === 'function') {
            //         const {
            //             title,
            //             body
            //         } = splitLabel(p.Label);
            //         displayTitle = title || p.Label;
            //         displayAddress = body || "";
            //     }

            //     if (
            //         displayTitle === oriAddress ||
            //         displayTitle === `${oriAddress}, ${oriAddressNumber}`
            //     ) {
            //         return null; // atau return undefined;
            //     }

            //     return {
            //         name: displayTitle,
            //         address: displayAddress,
            //         phone: (p.Phones && p.Phones.length > 0) ? p.Phones[0] : "-",
            //         lat: p.Geometry?.Point ? p.Geometry.Point[1] : 0,
            //         lng: p.Geometry?.Point ? p.Geometry.Point[0] : 0
            //     };
            // });

            const texts = languageTexts[currentLanguage];
            let html = '';

            // Wrapper agar bisa di-scroll
            html += `<div class="results-scroll-container">`;

            // Loop semua data
            rawResults.forEach((result, index) => {
                // Helper pengolah data (sama seperti single)
                const place = result.Place;
                const pt = place?.Geometry?.Point || [0, 0];
                let displayTitle = place.Label;
                let displayAddress = place.Label;

                if (typeof splitLabel === 'function') {
                    const {
                        title,
                        body
                    } = splitLabel(place.Label);
                    displayTitle = title || place.Label;
                    displayAddress = body || "";
                }

                // Cek apakah item ini adalah yang sedang selected?
                // Agar saat di-render ulang border hijaunya tetap ada di item yang benar
                // Kita bandingkan objek-nya atau koordinatnya
                let activeClass = 'normal';

                // Render Card NORMAL
                // Tambahkan onclick="selectCard(this)" untuk efek selected
                html += `
                            <div id="card-${index}" class="result-card ${activeClass}" onclick="selectCard(${index}, this)">
                                <div class="icon-box">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="res-content">
                                    <div style="font-weight:bold; font-size:15px; color:#333; margin-top:2px;">
                                        ${displayTitle}
                                    </div>
                                    <p class="res-address" style="margin-bottom:5px; font-size:13px; color:#666;">
                                        ${displayAddress}
                                    </p>
                                    <div style="font-size:12px; color:#999;">
                                        <i class="fas fa-map-pin"></i> ${Number(pt[1]).toFixed(5)}, ${Number(pt[0]).toFixed(5)}
                                    </div>
                                </div>
                            </div>
                        `;
            });

            html += `</div>`; // Tutup scroll container
            $('#verificationResult').html(html);
        }
        // Agar saat card normal diklik, warnanya berubah jadi selected
        function selectCard(index, element) {
            // 1. Visual: Reset class selected
            $('.result-card').removeClass('selected').addClass('normal');
            $(element).removeClass('normal').addClass('selected');

            // --- TAMBAHAN: FLY TO MAP ---
            // Pastikan object map sudah ada
            if (typeof map !== 'undefined' && map) {

                // Ambil koordinat [Longitude, Latitude]
                // const coords = lastSelectedData.Place.Geometry.Point;
                const pos = mapMarkers[index].getLngLat();
                const lng = pos.lng;
                const lat = pos.lat;

                // Perintahkan map untuk terbang ke koordinat tersebut
                map.flyTo({
                    center: [lng, lat], // Koordinat tujuan
                    zoom: 16, // Level zoom (16 = cukup dekat untuk level jalan)
                    speed: 1.2, // Kecepatan animasi (default 1.2)
                    curve: 1.42, // Kelengkungan animasi zoom
                    essential: true // Pastikan animasi berjalan meski user meminimalisir tab
                });

                // show radius 100 meter
                showRadiusCircle(lng, lat);

                // (OPSIONAL) Buka Popup pada Marker yang sesuai
                // Ini bekerja jika index array markers urutannya sama dengan index data
                if (typeof mapMarkers !== 'undefined' && mapMarkers[index]) {
                    // Tutup popup lain dulu (opsional)
                    mapMarkers.forEach(m => m.getPopup().remove());

                    // Buka popup marker yg dipilih
                    mapMarkers[index].togglePopup();
                }
            }
        }

        // FUNGSI BARU 2: Ekstrak Data ke CSV (Hanya formatting string)
        function formatCsvData(cleanData) {
            let csvContent = "data:text/csv;charset=utf-8,Name,Address,Phone,Latitude,Longitude\n";
            cleanData.forEach(row => {
                const cleanName = `"${String(row.name).replace(/"/g, '""')}"`;
                const cleanAddress = `"${String(row.address).replace(/"/g, '""')}"`;
                const cleanPhone = `"${String(row.phone)}"`;
                csvContent += `${cleanName},${cleanAddress},${cleanPhone},${row.lat},${row.lng}\n`;
            });
            return csvContent; // Kita return stringnya saja
        }

        // --- 5. UI UPDATES ---

        function updateQuotaUI() {
            $('#quotaCount').text(currentQuota);
            if (currentQuota <= 0) {
                $('#quotaBadge').css({
                    'border': '1px solid #d9534f',
                    'background': '#fff5f5',
                    'color': '#d9534f'
                });
                disableInterface();
            } else {
                $('#quotaBadge').css({
                    'border': 'none',
                    'background': '#fff',
                    'color': '#555'
                });
            }
        }

        function disableInterface() {
            $('#btnSearchGemini').prop('disabled', true).css('background-color', '#ccc');
            $('#geminiSearchInput').prop('disabled', true).attr('placeholder', 'Credits Exhausted (0/5).');
        }

        function getOrCreateDeviceId() {
            let id = localStorage.getItem('device_id');
            if (!id) {
                if (window.crypto && window.crypto.randomUUID) {
                    id = crypto.randomUUID();
                } else {
                    id = 'dev-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
                }
                localStorage.setItem('device_id', id);
            }
            return id;
        }

        // --- Sidebar Logic ---
        function toggleHistory() {
            $('#historySidebar').toggleClass('open');
            $('#btnToggleHistory').toggleClass('active');
        }

        function addToHistoryLog(address, count) {
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            // Create object
            const logItem = {
                keyword: address,
                time: timeString,
                count: count
            };

            // Add to beginning of array
            historyLog.unshift(logItem);

            renderHistoryList();
        }

        function renderHistoryList() {
            const container = $('#historyListContainer');
            const badge = $('#historyCountBadge');

            if (historyLog.length === 0) {
                container.html(`
                    <div class="empty-history">
                        <i class="fas fa-search" style="font-size: 30px; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>No scraping history yet.</p>
                    </div>
                `);
                badge.hide();
                return;
            }

            badge.text(historyLog.length).show();

            let html = '';
            historyLog.forEach(item => {
                html += `
                <div class="history-item">
                    <div class="h-top">
                        <div class="h-address" title="${item.keyword}">${item.keyword}</div>
                        <span class="h-badge">${item.count} POI</span>
                    </div>
                    <span class="h-time"><i class="far fa-clock"></i> ${item.time} • Success</span>
                </div>
                `;
            });

            container.html(html);
        }

        // Bagian MAP
        function initMap() {
            // Style URL untuk AWS Location Service / GrabMaps
            const styleUrl = `https://maps.geo.${region}.amazonaws.com/maps/v0/maps/${mapName}/style-descriptor?key=${apiKey}`;

            map = new maplibregl.Map({
                container: 'map', // ID div peta
                style: styleUrl,
                center: [106.8456, -6.2088], // Default Jakarta
                zoom: 12,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'bottom-right');

            // === KLIK DI MAP ===
            map.on('click', function(e) {
                const lng = e.lngLat.lng;
                const lat = e.lngLat.lat;

                // Kalau marker klik sudah ada → pindahkan saja
                if (clickMarker) {
                    clickMarker.setLngLat([lng, lat]);
                } else {
                    // Kalau belum ada → buat baru
                    clickMarker = new maplibregl.Marker({
                            color: "#ff4d4f" // beda warna biar kelihatan
                        })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup().setHTML(
                            `<b>Selected point</b><br>Lng: ${lng.toFixed(5)}<br>Lat: ${lat.toFixed(5)}`
                        ))
                        .addTo(map);
                }

                // Optional: auto flyTo titik yang diklik (sebenernya map sudah di posisi itu)
                map.flyTo({
                    center: [lng, lat],
                    zoom: 16,
                    speed: 1.2,
                    curve: 1.42,
                    essential: true
                });

                // Kalau kamu punya state global:
                lastSelectedData = {
                    Geometry: {
                        Point: [lng, lat]
                    }
                };

                console.log('Clicked point:', lng, lat);
            });
        }

        function updateMapMarkers(dataArray) {
            if (!map) return;

            // 1. Bersihkan Marker Lama
            if (mapMarkers.length > 0) {
                mapMarkers.forEach(marker => marker.remove());
                mapMarkers = [];
            }

            if (!dataArray || dataArray.length === 0) return;

            const bounds = new maplibregl.LngLatBounds();

            // 2. Loop Data (Mau 1 atau Banyak, sama saja)
            dataArray.forEach(item => {
                const pt = item.Place?.Geometry?.Point;
                if (pt) {
                    const lng = pt[0];
                    const lat = pt[1];

                    // Buat Marker
                    const marker = new maplibregl.Marker({
                            color: "#00b14f"
                        })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup().setHTML(`<b>${item.Place.Label}</b>`))
                        .addTo(map);

                    mapMarkers.push(marker);
                    bounds.extend([lng, lat]);
                }
            });

            // 3. Atur Zoom Kamera
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, {
                    padding: 100, // Beri jarak dari pinggir layar
                    maxZoom: 15, // <--- PENTING: Batasi zoom agar tidak terlalu dekat jika datanya cuma 1
                    duration: 1000 // Animasi zoom smooth (1 detik)
                });
            }
        }

        function showRadiusCircle(lng, lat) {
            if (!map) return;

            const circleFeature = createCirclePolygon(lng, lat, 100); // 100 meter
            const data = {
                type: "FeatureCollection",
                features: [circleFeature]
            };

            // kalau source belum ada → buat
            if (!map.getSource('selection-radius')) {
                map.addSource('selection-radius', {
                    type: 'geojson',
                    data: data
                });

                // fill (warna dalam)
                map.addLayer({
                    id: 'selection-radius-fill',
                    type: 'fill',
                    source: 'selection-radius',
                    paint: {
                        'fill-color': '#00b14f',
                        'fill-opacity': 0.15
                    }
                });

                // outline
                map.addLayer({
                    id: 'selection-radius-outline',
                    type: 'line',
                    source: 'selection-radius',
                    paint: {
                        'line-color': '#00b14f',
                        'line-width': 2
                    }
                });
            } else {
                // kalau sudah ada, cukup update datanya
                map.getSource('selection-radius').setData(data);
            }
        }

        function createCirclePolygon(lng, lat, radiusMeters = 100, points = 64) {
            const coords = [];
            const earthRadius = 6378137; // radius bumi dalam meter

            const centerLat = lat * Math.PI / 180;
            const centerLng = lng * Math.PI / 180;
            const angularDistance = radiusMeters / earthRadius;

            for (let i = 0; i <= points; i++) {
                const bearing = i * 2 * Math.PI / points;

                const latRad = Math.asin(
                    Math.sin(centerLat) * Math.cos(angularDistance) +
                    Math.cos(centerLat) * Math.sin(angularDistance) * Math.cos(bearing)
                );

                const lngRad = centerLng + Math.atan2(
                    Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(centerLat),
                    Math.cos(angularDistance) - Math.sin(centerLat) * Math.sin(latRad)
                );

                coords.push([lngRad * 180 / Math.PI, latRad * 180 / Math.PI]);
            }

            return {
                type: "Feature",
                geometry: {
                    type: "Polygon",
                    coordinates: [coords]
                },
                properties: {}
            };
        }
    </script>
</body>

</html>