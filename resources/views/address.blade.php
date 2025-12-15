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

        <div id="map" class="background-map"></div>

        <div class="search-center-wrapper">

            <div class="search-header-text">
                <h2 id="titleVerify">Verify Your Address Here</h2>
            </div>

            <div class="gemini-input-group big-search">
                <textarea id="geminiSearchInput" class="gemini-textarea" placeholder="Search for address..." rows="1"></textarea>
                <button id="btnSearchGemini" class="gemini-send-btn" onclick="performSearch()">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <div class="gemini-helper">
                <div class="helper-text">
                    <small>Press <b>Enter</b> to search</small>
                </div>

                <div class="helper-actions-toggles">
                    <label class="switch wide-switch">
                        <input type="checkbox" id="checkBasemap" onchange="handleBasemap(this)">
                        <span class="slider round">
                            <span class="text-label" id="labelBasemap">Maps</span>
                        </span>
                    </label>

                    <label class="switch wide-switch">
                        <input type="checkbox" id="checkManyData" onchange="handleManyData(this)">
                        <span class="slider round">
                            <span class="text-label" id="labelManyData">Many Data</span>
                        </span>
                    </label>
                </div>
            </div>

            <div id="verificationResult" class="verification-container"></div>

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
        let lastSelectedData = null;
        let lastBasemap = "OFF";
        let lastManyData = "OFF";

        let map;
        let mapMarkers = []; // <--- Variable baru untuk menampung marker

        // Initialize when document is ready
        $(document).ready(function() {
            initLanguage();
            initMap(); // Panggil inisialisasi peta

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
        }

        function handleBasemap(checkbox) {
            const mapDiv = $('.background-map');
            const searchWrapper = $('.search-center-wrapper');

            if (checkbox.checked) {
                // show maps
                mapDiv.addClass('active');
                mapDiv.show();

                // 2. Pindahkan Search Box ke Kiri (Slide Animation)
                searchWrapper.addClass('sidebar-mode');

                // 3. Resize map (Opsional: agar render map tidak gepeng saat transisi)
                setTimeout(() => {
                    if (typeof map !== 'undefined') map.resize();
                }, 600);

            } else {

                // 1. Sembunyikan Peta (Fade Out)
                mapDiv.removeClass('active');
                mapDiv.hide();

                // 2. Kembalikan Search Box ke Tengah
                searchWrapper.removeClass('sidebar-mode');
            }
        }

        function handleManyData(checkbox) {
            // 1. Jika Checkbox ON -> Tampilkan Semua Data
            if (checkbox.checked) {
                if (lastSearchData && lastSearchStatus === 'success') {
                    renderVerificationResult('success', lastSearchData);
                }
            }
            // 2. Jika Checkbox OFF -> Tampilkan HANYA Data Terakhir Dipilih
            else {
                if (lastSelectedData) {
                    // Bungkus dalam array [] karena fungsi render mengharapkan array
                    renderVerificationResult('success', [lastSelectedData]);
                } else if (lastSearchData && lastSearchData.length > 0) {
                    // Fallback jika belum ada yang dipilih, ambil index 0
                    renderVerificationResult('success', [lastSearchData[0]]);
                }
            }
        }

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

            // HAPUS MARKER DI PETA
            if (mapMarkers.length > 0) {
                mapMarkers.forEach(marker => marker.remove());
                mapMarkers = [];
            }

            // Reset posisi peta ke default (Jakarta)
            if (map) {
                map.flyTo({
                    center: [106.8456, -6.2088],
                    zoom: 12
                });
            }
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
                    <p>${texts.searchingAddress || "Searching..."}</p>
                </div>`);

            // get data address with geocode
            resSearch = await searchGeocode(query.toLowerCase(), 5);

            // 2. SIMULASI API CALL (Ganti ini dengan logika fetch ke backend Anda)
            // Disini saya pakai timeout untuk pura-pura loading
            setTimeout(function() {
                if (!resSearch || resSearch.length === 0) {
                    lastSearchStatus = 'error';
                    lastSearchData = null;
                    lastSelectedData = null; // Reset
                    renderVerificationResult("error", null);
                } else {
                    lastSearchStatus = 'success';
                    lastSearchData = resSearch;

                    // DEFAULT: Set data terpilih ke index 0 saat pertama kali cari
                    lastSelectedData = resSearch[0];

                    renderVerificationResult("success", resSearch);
                }
            }, 1500);
        }

        // Fungsi Render HTML Hasil
        function renderVerificationResult(status, dataArray) {
            const texts = languageTexts[currentLanguage];
            let html = '';

            // Cek apakah Checkbox Many Data sedang dicentang?
            const isManyData = $('#checkManyData').is(':checked');

            // --- OPTIMASI: UPDATE MARKER DI SINI (DI ATAS) ---
            if (status === 'success' && dataArray && dataArray.length > 0) {
                if (isManyData) {
                    // JIKA MANY DATA ON: Tampilkan SEMUA marker yang ada di array
                    updateMapMarkers(dataArray);
                } else {
                    // JIKA MANY DATA OFF: Tampilkan SATU marker saja
                    // Prioritas: Gunakan data yang terakhir dipilih (lastSelectedData)
                    // Jika belum ada yang dipilih, gunakan data pertama (dataArray[0])
                    const singleData = lastSelectedData ? lastSelectedData : dataArray[0];

                    // Bungkus dalam array [] karena fungsi marker butuh array
                    updateMapMarkers([singleData]);
                }
            }

            // KONDISI 1: ERROR / DATA KOSONG
            if (status !== 'success' || !dataArray || dataArray.length === 0) {
                html = `
                        <div class="result-card error">
                            <div class="icon-box"><i class="fas fa-times"></i></div>
                            <div class="res-content">
                                <h4 class="res-title">${texts.verifFailedTitle}</h4>
                                <p class="res-address">${texts.verifFailedDesc}</p>
                            </div>
                        </div>
                    `;
            }
            // KONDISI 2: JIKA MANY DATA "ON" (TAMPILKAN LIST NORMAL)
            else if (isManyData) {
                // Wrapper agar bisa di-scroll
                html += `<div class="results-scroll-container">`;

                // Loop semua data
                dataArray.forEach((result, index) => {
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
                    if (lastSelectedData &&
                        result.Place.Geometry.Point[0] === lastSelectedData.Place.Geometry.Point[0] &&
                        result.Place.Geometry.Point[1] === lastSelectedData.Place.Geometry.Point[1]) {
                        activeClass = 'selected';
                    }

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

            }
            // KONDISI 3: JIKA MANY DATA "OFF" (TAMPILKAN 1 DATA SUCCESS - KODE ANDA)
            else {
                // AMBIL DATA PERTAMA SAJA
                const result = dataArray[0];
                const place = result.Place;
                const pt = place?.Geometry?.Point || [0, 0];
                const lon = Number(pt[0]);
                const lat = Number(pt[1]);

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

                // Render Card SUKSES (Hijau)
                html = `
                        <div class="result-card success">
                            <div class="icon-box">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="res-content">
                                <h4 class="res-title" style="color:#008a3a; margin-bottom:2px;">
                                    ${texts.verifSuccessTitle}
                                </h4>
                                <div style="font-weight:bold; font-size:15px; color:#333; margin-top:5px;">
                                    ${displayTitle}
                                </div>
                                <p class="res-address" style="margin-bottom:10px; font-size:14px;">
                                    ${displayAddress}
                                </p>
                                <div class="res-meta">
                                    <span>
                                        <i class="fas fa-map-pin"></i> 
                                        ${texts.coordinate}: ${lat.toFixed(5)}, ${lon.toFixed(5)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
            }

            $('#verificationResult').html(html);
        }

        // Agar saat card normal diklik, warnanya berubah jadi selected
        function selectCard(index, element) {
            // 1. Visual: Reset class selected
            $('.result-card').removeClass('selected').addClass('normal');
            $(element).removeClass('normal').addClass('selected');

            // 2. LOGIKA DATA: Simpan data yang diklik
            if (lastSearchData && lastSearchData[index]) {
                lastSelectedData = lastSearchData[index];
                console.log("Data terpilih diupdate:", lastSelectedData.Place.Label);

                // --- TAMBAHAN: FLY TO MAP ---
                // Pastikan object map sudah ada
                if (typeof map !== 'undefined' && map) {

                    // Ambil koordinat [Longitude, Latitude]
                    const coords = lastSelectedData.Place.Geometry.Point;

                    // Perintahkan map untuk terbang ke koordinat tersebut
                    map.flyTo({
                        center: [coords[0], coords[1]], // Koordinat tujuan
                        zoom: 16, // Level zoom (16 = cukup dekat untuk level jalan)
                        speed: 1.2, // Kecepatan animasi (default 1.2)
                        curve: 1.42, // Kelengkungan animasi zoom
                        essential: true // Pastikan animasi berjalan meski user meminimalisir tab
                    });

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
    </script>
</body>

</html>