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

    <style>
        /* Container Hasil di bawah search box */
        .verification-container {
            margin-top: 25px;
            width: 100%;
            animation: fadeIn 0.4s ease-out;
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

        /* Base Style untuk Kartu Hasil */
        .result-card {
            padding: 20px;
            border-radius: 16px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            background: white;
            border: 1px solid transparent;
        }

        /* Style untuk SUKSES (Ada) */
        .result-card.success {
            background-color: #e6f9ed;
            /* Hijau sangat muda */
            border-color: #bcebd0;
        }

        .result-card.success .icon-box {
            background-color: #00ba4e;
            color: white;
        }

        .result-card.success .res-title {
            color: #008a3a;
        }

        /* Style untuk GAGAL (Tidak Ada) */
        .result-card.error {
            background-color: #fff5f5;
            /* Merah sangat muda */
            border-color: #fed7d7;
        }

        .result-card.error .icon-box {
            background-color: #e53e3e;
            color: white;
        }

        .result-card.error .res-title {
            color: #c53030;
        }

        /* Ikon Bulat */
        .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        /* Teks dalam kartu */
        .res-content {
            flex: 1;
        }

        .res-title {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 700;
        }

        .res-address {
            margin: 0 0 10px 0;
            font-size: 15px;
            color: #333;
            line-height: 1.5;
        }

        .res-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
            background: rgba(255, 255, 255, 0.6);
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-flex;
        }

        .res-meta i {
            margin-right: 5px;
        }
    </style>
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
            <button class="button btn btn-success" onclick="resetSearch();" id="btnResetSearch">Reset Search</button>
        </div>
    </div>

    <!-- Main container for map and sidebar -->
    <div class="container-result centered-layout">

        <div class="search-center-wrapper">

            <div class="search-header-text">
                <h2>Verify Your Address Here</h2>
            </div>

            <div class="gemini-input-group big-search">
                <textarea
                    id="geminiSearchInput"
                    class="gemini-textarea"
                    placeholder="Search for address..."
                    rows="1"></textarea>

                <button id="btnSearchGemini" class="gemini-send-btn" onclick="performSearch()">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <div class="gemini-helper text-center">
                <small>Press <b>Enter</b> to search</small>
            </div>

            <div id="verificationResult" class="verification-container" style="display: none;"></div>

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

        let lastSearchStatus = null; // Menyimpan status terakhir (success/error)
        let lastSearchData = null; // Menyimpan data alamat terakhir

        // Initialize when document is ready
        $(document).ready(function() {
            initLanguage();

            const textarea = document.getElementById('geminiSearchInput');

            // Auto resize tinggi textarea
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Handle Enter
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();

                    performSearch()
                }
            });
        });

        // Reset map and clear search
        function resetSearch() {
            const input = document.getElementById('geminiSearchInput');

            if (input) {
                input.value = "";
                input.style.height = "auto"; // Kembalikan ukuran textarea
                input.focus();
            }

            // Sembunyikan hasil pencarian sebelumnya
            $('#verificationResult').hide().html('');

            // HAPUS DATA GLOBAL
            lastSearchStatus = null;
            lastSearchData = null;
        }

        // Fungsi Utama Pencarian (Simulasi)
        async function performSearch() {
            const query = $('#geminiSearchInput').val().trim();
            if (!query) return;

            const texts = languageTexts[currentLanguage];
            const resultContainer = $('#verificationResult');
            let resSearch;

            //check length address
            if (query.length < 10) {
                showToast(texts.textToastAddress, "error");
                return;
            }

            // 1. Tampilkan Loading State
            resultContainer.show().html(`
                <div class="results-loading">
                    <i class="fas fa-spinner"></i>
                    <p>${texts.searching || "Searching..."}</p>
                </div>`);

            // get data address with geocode
            resSearch = await searchGeocode(query.toLowerCase(), 1);

            // 2. SIMULASI API CALL (Ganti ini dengan logika fetch ke backend Anda)
            // Disini saya pakai timeout untuk pura-pura loading
            setTimeout(function() {
                if (!resSearch || resSearch.length === 0) {
                    lastSearchStatus = 'error';
                    lastSearchData = null;

                    // Data dummy jika GAGAL
                    renderVerificationResult("error", null);
                }

                // console.log(resSearch[0].Place);
                renderVerificationResult("success", resSearch);

            }, 1500); // delay 1.5 detik
        }

        // Fungsi Render HTML Hasil
        function renderVerificationResult(status, dataArray) {
            const texts = languageTexts[currentLanguage];
            let html = '';

            // Cek apakah status sukses DAN dataArray ada isinya
            if (status === 'success' && dataArray && dataArray.length > 0) {

                // 1. AMBIL DATA PERTAMA SAJA (sesuai permintaan)
                const result = dataArray[0];
                const place = result.Place;

                // 2. AMBIL KOORDINAT
                const pt = place?.Geometry?.Point || [0, 0];
                const lon = Number(pt[0]);
                const lat = Number(pt[1]);

                // 3. OLAH LABEL (Title & Body)
                // Kita gunakan logika splitLabel seperti kode Anda
                // Jika fungsi splitLabel ada di file lain, pastikan terload.
                // Jika tidak, logika fallback di bawah ini aman:
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

                // 4. HTML TAMPILAN SUKSES
                html = `
            <div class="result-card success">
                <div class="icon-box">
                    <i class="fas fa-check"></i>
                </div>
                <div class="res-content">
                    <!-- Status Header -->
                    <h4 class="res-title" style="color:#008a3a; margin-bottom:2px;">
                        ${texts.verifSuccessTitle}
                    </h4>
                    
                    <!-- Nama Tempat (Tebal) -->
                    <div style="font-weight:bold; font-size:15px; color:#333; margin-top:5px;">
                        ${displayTitle}
                    </div>

                    <!-- Alamat Detail (Abu-abu) -->
                    <p class="res-address" style="margin-bottom:10px; font-size:14px;">
                        ${displayAddress}
                    </p>
                    
                    <!-- Meta Koordinat -->
                    <div class="res-meta">
                        <span>
                            <i class="fas fa-map-pin"></i> 
                            ${texts.coordinate}: ${lat.toFixed(5)}, ${lon.toFixed(5)}
                        </span>
                    </div>
                </div>
            </div>
        `;

            } else {
                // TAMPILAN GAGAL / DATA KOSONG
                html = `
            <div class="result-card error">
                <div class="icon-box">
                    <i class="fas fa-times"></i>
                </div>
                <div class="res-content">
                    <h4 class="res-title">${texts.verifFailedTitle}</h4>
                    <p class="res-address">${texts.verifFailedDesc}</p>
                </div>
            </div>
        `;
            }

            $('#verificationResult').html(html);
        }
    </script>
</body>

</html>