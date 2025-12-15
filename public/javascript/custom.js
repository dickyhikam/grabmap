// Language management
let currentLanguage = "en"; // Default language: English

// Language texts
const languageTexts = {
    en: {
        // --- TEXT KHUSUS TAMPILAN GEMINI ---
        heroTitleVerify: "Verify Your Address Here",
        pressEnterHelper: "Press <b>Enter</b> to search",
        searchPlaceholder: "Search for address...",

        // --- TEXT LAMA (TETAP DIPERTAHANKAN) ---
        resetMaps: "Reset Maps",
        resetSearch: "Reset Search",
        // searchPlaceholder: "Search for address, place, or location...",
        enterKeywords: "Please enter search keywords",
        welcomeTitle: "Welcome to<br>GrabMaps!",
        searchFeature: "Search Location",
        searchDesc:
            "Use the search box above to find places, addresses, or locations you're looking for.",
        routeFeature: "Route Filter",
        routeDesc:
            "Choose vehicle type (Car or Motorcycle) to get routes that match your travel needs.",
        exploreFeature: "Explore Map",
        exploreDesc:
            "Click and drag the map to explore various locations in your desired area.",
        tipsTitle: "Usage Tips:",
        tip1: "Use complete addresses for maximum accuracy",
        tip2: "Select vehicle type for optimal routes",
        tip3: "Check estimated time and distance before starting your trip",
        tip4: "Use GPS feature for more precise location",
        searchResults: "Search Results",
        foundLocations: "Found 3 locations for",
        save: "Save",
        directions: "Directions",
        locationSaved: "Location saved to favorites!",
        gettingDirections: "Getting directions...",
        loadingMap: "Loading Maps...",
        errorLoading: "Error loading map:",
        tryAgain: "Try Again",
        validationEmpty: "Please enter search keywords to find locations",
        validationError: "Please enter search keywords",
        validationSuccess: "Search keywords entered",

        // Loading Messages
        loadingMaps: "Loading Maps",
        loadingSubtitle: "Preparing your navigation experience",
        loadingPleaseWait: "Please wait",
        loadingInitializing: "Initializing map components",
        loadingStyle: "Loading map style",
        loadingControls: "Setting up controls",
        loadingFinalizing: "Finalizing map",
        loadingReady: "Map ready",

        // Loading States
        loadingTryAgain: "Please try again",
        loadingReadyExplore: "Ready to explore!",
        loadingMapsReady: "Maps Ready",
        loadingFailed: "Map Loading Failed",

        // Loading Details
        detailLoadingStyle: "• Loading map style",
        detailSettingControls: "• Setting up controls",
        detailFinalizingMap: "• Finalizing map",
        detailMapReady: "• ✓ Map ready!",

        noResultsFound: "No results found for",
        noResults: "No Results Found",
        tryDifferentKeywords: "Try different keywords or check your spelling",
        searchError: "Search Failed",
        searchErrorDesc:
            "There was an error while searching. Please check your connection and try again.",
        location: "Location",
        detailsAvailable: "Details available",

        selectedLocations: "Marker Selected",
        select: "Select",
        clearAll: "Clear All",
        planRoute: "Plan Route",
        noSelectedLocations: "No location selected",
        noSelectedLocationsDes: "Please select a location first",
        confirmClearAll:
            "Are you sure you want to clear all selected locations?",
        allLocationsCleared: "All locations cleared",
        needAtLeastTwoLocations: "Need at least 2 locations to plan a route",
        view: "View",

        searching: "Searching...",

        displayNearestMarkers: "Displaying nearest markers",

        //verif address
        verifSuccessTitle: "Address Verified",
        verifSuccessDesc: "Location found in database.",
        verifFailedTitle: "Address Not Found",
        verifFailedDesc:
            "We couldn't verify this location. Please check your spelling.",
        coordinate: "Coordinate",
        confidence: "Match Accuracy",
        searchingAddress: "Verifying address...",

        textToastAddress:
            "Please enter a complete address (minimum 10 characters)",

        labelBasemap: "Maps",
        labelManyData: "Multiple Data",

        // --- SCRAPING TEXTS ---
        heroTitleScrap: "POI Data Scraping",
        heroDescripScrap:
            "Search for an address and select a location to collect data from the surrounding area.",
        tagRadius: "Radius: 100m",
        tagMax: "Max: 50 POIs",
        historyTitle: "Recent Activity",
        // Quota & Status
        quotaExceeded: "Quota limit reached (0/5). Please try again tomorrow.",
        quotaRestored: "System Reset: Quota restored & History cleared!",
        scrapingSuccess: "Data extraction completed successfully.",
        inputEmpty: "Please enter an address first.",
        downloading: "Downloading CSV for:",
        // Loading Steps
        stepInit: "Initializing...",
        stepConnect: "Connecting to Maps API...",
        stepExtract: "Extracting Points of Interest...",
        stepVerify: "Verifying Data...",
        stepDone: "Completed.",
        // --- SCRAPING TEXTS ---
    },
    id: {
        // --- TEXT KHUSUS TAMPILAN GEMINI ---
        heroTitleVerify: "Verifikasi Alamat Anda Di Sini",
        heroTitleScrap: "Scraping Data POI",
        pressEnterHelper: "Tekan <b>Enter</b> untuk mencari",

        // --- TEXT LAMA (TETAP DIPERTAHANKAN) ---
        resetMaps: "Reset Peta",
        resetSearch: "Reset Pencarian",
        searchPlaceholder: "Cari alamat...",
        enterKeywords: "Masukkan kata kunci pencarian",
        welcomeTitle: "Selamat Datang di<br>GrabMaps!",
        searchFeature: "Cari Lokasi",
        searchDesc:
            "Gunakan kotak pencarian di atas untuk menemukan tempat, alamat, atau lokasi yang Anda cari.",
        routeFeature: "Filter Rute",
        routeDesc:
            "Pilih jenis kendaraan (Mobil atau Motor) untuk mendapatkan rute yang sesuai dengan kebutuhan perjalanan Anda.",
        exploreFeature: "Jelajahi Peta",
        exploreDesc:
            "Klik dan seret peta untuk menjelajahi berbagai lokasi di area yang Anda inginkan.",
        tipsTitle: "Tips Penggunaan:",
        tip1: "Gunakan alamat lengkap untuk akurasi maksimal",
        tip2: "Pilih jenis kendaraan untuk rute yang optimal",
        tip3: "Periksa estimasi waktu dan jarak sebelum memulai perjalanan",
        tip4: "Gunakan fitur GPS untuk lokasi yang lebih presisi",
        searchResults: "Hasil Pencarian",
        foundLocations: "Ditemukan 3 lokasi untuk",
        save: "Simpan",
        directions: "Petunjuk Arah",
        locationSaved: "Lokasi disimpan ke favorit!",
        gettingDirections: "Mendapatkan petunjuk arah...",
        loadingMap: "Memuat Peta...",
        errorLoading: "Error memuat peta:",
        tryAgain: "Coba Lagi",
        validationEmpty: "Masukkan kata kunci pencarian untuk menemukan lokasi",
        validationError: "Masukkan kata kunci pencarian",
        validationSuccess: "Kata kunci pencarian telah dimasukkan",

        // Loading Messages
        loadingMaps: "Memuat Peta",
        loadingSubtitle: "Mempersiapkan pengalaman navigasi Anda",
        loadingPleaseWait: "Harap tunggu",
        loadingInitializing: "Memulai komponen peta",
        loadingStyle: "Memuat gaya peta",
        loadingControls: "Menyiapkan kontrol",
        loadingFinalizing: "Menyelesaikan peta",
        loadingReady: "Peta siap",

        // Loading States
        loadingTryAgain: "Silakan coba lagi",
        loadingReadyExplore: "Siap untuk menjelajah!",
        loadingMapsReady: "Peta Siap",
        loadingFailed: "Gagal Memuat Peta",

        // Loading Details
        detailLoadingStyle: "• Memuat gaya peta",
        detailSettingControls: "• Menyiapkan kontrol",
        detailFinalizingMap: "• Menyelesaikan peta",
        detailMapReady: "• ✓ Peta siap!",

        noResultsFound: "Tidak ada hasil ditemukan untuk",
        noResults: "Tidak Ada Hasil Ditemukan",
        tryDifferentKeywords: "Coba kata kunci berbeda atau periksa ejaan Anda",
        searchError: "Pencarian Gagal",
        searchErrorDesc:
            "Terjadi kesalahan saat mencari. Periksa koneksi Anda dan coba lagi.",
        location: "Lokasi",
        detailsAvailable: "Detail tersedia",

        selectedLocations: "Maps Terpilih",
        select: "Pilih",
        clearAll: "Hapus Semua",
        planRoute: "Rencanakan Rute",
        noSelectedLocations: "Lokasi belum dipilih",
        noSelectedLocationsDes: "Silakan pilih lokasi terlebih dahulu",
        confirmClearAll:
            "Apakah Anda yakin ingin menghapus semua lokasi yang dipilih?",
        allLocationsCleared: "Semua lokasi telah dihapus",
        needAtLeastTwoLocations:
            "Perlu setidaknya 2 lokasi untuk merencanakan rute",
        view: "Lihat",

        searching: "Sedang mencari...",

        displayNearestMarkers: "Menampilkan marker terdekat",

        //verif address
        verifSuccessTitle: "Alamat Terverifikasi",
        verifSuccessDesc: "Lokasi ditemukan dalam database.",
        verifFailedTitle: "Alamat Tidak Ditemukan",
        verifFailedDesc:
            "Kami tidak dapat memverifikasi lokasi ini. Mohon periksa ejaan Anda.",
        coordinate: "Koordinat",
        confidence: "Akurasi Cocok",
        searchingAddress: "Sedang memverifikasi alamat...",

        textToastAddress: "Mohon masukkan alamat lengkap (minimal 10 karakter)",

        labelBasemap: "Peta",
        labelManyData: "Banyak Data",

        // --- SCRAPING TEXTS ---
        heroTitleScrap: "Scraping Data POI",
        heroDescripScrap:
            "Cari alamat dan pilih lokasi untuk melakukan pengambilan data di sekitarnya.",
        tagRadius: "Radius: 100m",
        tagMax: "Maks: 50 POI",
        historyTitle: "Aktivitas Terkini",
        // Quota & Status
        quotaExceeded: "Kuota habis (0/5). Silakan coba lagi besok.",
        quotaRestored: "System Reset: Kuota dipulihkan & Riwayat dihapus!",
        scrapingSuccess: "Ekstraksi data berhasil diselesaikan.",
        inputEmpty: "Mohon masukkan alamat terlebih dahulu.",
        downloading: "Mengunduh CSV untuk:",
        // Loading Steps
        stepInit: "Memulai...",
        stepConnect: "Menghubungkan ke Maps API...",
        stepExtract: "Mengambil Point of Interest...",
        stepVerify: "Memverifikasi Data...",
        stepDone: "Selesai.",
        // --- SCRAPING TEXTS ---
    },
};

// Initialize language functionality
function initLanguage() {
    setupLanguageEvents();
}

// Setup language dropdown events
function setupLanguageEvents() {
    const languageBtn = document.getElementById("languageBtn");
    const languageDropdown = document.getElementById("languageDropdown");

    // Toggle dropdown on button click
    languageBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        languageDropdown.classList.toggle("show");
        languageBtn.classList.toggle("active");
    });

    // Handle language selection
    document.querySelectorAll(".language-option").forEach((option) => {
        option.addEventListener("click", function () {
            const lang = this.getAttribute("data-lang");
            changeLanguage(lang);
            languageDropdown.classList.remove("show");
            languageBtn.classList.remove("active");
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function () {
        languageDropdown.classList.remove("show");
        languageBtn.classList.remove("active");
    });
}

// Change language
function changeLanguage(lang) {
    if (lang === currentLanguage) return;

    currentLanguage = lang;
    updateLanguage();
}

// Update all text elements to current language
function updateLanguage() {
    const texts = languageTexts[currentLanguage];

    // Update header button
    $("#btnResetMaps").text(texts.resetMaps);
    $("#btnResetSearch").text(texts.resetSearch);

    $(".toggle-tooltip").text(texts.displayNearestMarkers);

    // Update search elements
    $("#searchInput").attr("placeholder", texts.searchPlaceholder);

    // Update language button
    const flagSrc =
        currentLanguage === "en"
            ? "https://flagcdn.com/w40/gb.png"
            : "https://flagcdn.com/w40/id.png";
    const langText = currentLanguage === "en" ? "EN" : "ID";

    $("#languageBtn .flag-icon").attr("src", flagSrc);
    $("#languageBtn .flag-icon").attr(
        "alt",
        currentLanguage === "en" ? "English" : "Indonesian"
    );
    $("#languageBtn .language-text").text(langText);

    // Update active state in dropdown
    $(".language-option").removeClass("active");
    $(`.language-option[data-lang="${currentLanguage}"]`).addClass("active");

    // Update content based on current view
    updateContentLanguage();

    // Update content based on current view address
    updateContentLanguageAddress(texts, lastSearchStatus);

    updateContentLanguageScrap(texts);
}

// Update content based on current language
function updateContentLanguageAddress(texts, lastSearchStatus = null) {
    // UPDATE ELEMENT TAMPILAN GEMINI (TENGAH)
    $("#titleVerify").text(texts.heroTitleVerify); // Judul Besar
    $("#geminiSearchInput").attr("placeholder", texts.searchPlaceholder); // Placeholder Textarea
    $(".gemini-helper small").html(texts.pressEnterHelper); // Helper di bawah input (pakai .html agar <b> terbaca)

    // Update element label ON OFF
    $("#labelBasemap").text(texts.labelBasemap);
    $("#labelManyData").text(texts.labelManyData);

    if (lastSearchStatus !== null) {
        // Panggil ulang fungsi render.
        // Karena 'currentLanguage' sudah berubah, teks di dalamnya otomatis ganti.
        renderVerificationResult(lastSearchStatus, lastSearchData);
    }
}
function updateContentLanguageScrap(texts) {
    // 1. Update Judul
    $("#titleScrap").text(texts.heroTitleScrap);
    $("#descripScrap").text(texts.heroDescripScrap);

    // 2. Update Tag Radius (Tag Pertama)
    // Kita pakai .html() supaya icon <i class="fas..."> tetap ada/ditulis ulang
    $(".specs-tags .tag")
        .eq(0)
        .html(`<i class="fas fa-bullseye"></i> ${texts.tagRadius}`);

    // 3. Update Tag Max POI (Tag Kedua)
    $(".specs-tags .tag")
        .eq(1)
        .html(`<i class="fas fa-list-ol"></i> ${texts.tagMax}`);

    $(".history-header span").html(
        `<i class="fas fa-history"></i> ${texts.historyTitle}`
    );
}
function updateContentLanguage() {
    const texts = languageTexts[currentLanguage];

    // Update welcome message if visible
    if ($("#resultBox").find(".welcome-message").length) {
        showWelcomeMessage();
    }

    // Update search results if visible
    if ($("#resultBox").find(".search-results").length) {
        const searchText = $("#searchInput").val().trim();
        if (searchText) {
            performSearch();
        }
    }

    // Update loading text
    const loadingDiv = document.querySelector("#loading div");
    if (loadingDiv) {
        loadingDiv.textContent = texts.loadingMap;
    }
}

function showToast(message, type = "warning") {
    const texts = languageTexts[currentLanguage];

    // Remove existing toast if any
    $(".custom-toast").remove();

    const toastClass =
        type === "warning"
            ? "toast-warning"
            : type === "error"
            ? "toast-error"
            : type === "success"
            ? "toast-success"
            : "toast-info";

    const icon =
        type === "warning"
            ? "fa-exclamation-circle"
            : type === "error"
            ? "fa-times-circle"
            : type === "success"
            ? "fa-check-circle"
            : "fa-info-circle";

    const toastHTML = `
        <div class="custom-toast ${toastClass}">
            <div class="toast-content">
                <div class="toast-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="toast-message">
                    ${message}
                </div>
                <button class="toast-close" onclick="closeToast()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="toast-progress"></div>
            </div>
        </div>
    `;

    $("body").append(toastHTML);

    // Auto close with progress bar
    setTimeout(() => {
        closeToast();
    }, 4000);
}

function closeToast() {
    const toast = $(".custom-toast");
    if (toast.length) {
        toast.addClass("hide");
        setTimeout(() => {
            toast.remove();
        }, 400); // Match animation duration
    }
}

// ====== UTIL: Escape sederhana agar aman saat menaruh teks ke HTML ======
function escapeHtml(str = "") {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}

// ====== LOGIKA POPUP: pecah label menjadi title (sebelum koma pertama) dan body (setelahnya) ======
function splitLabel(label = "") {
    const raw = String(label || "");
    const i = raw.indexOf(",");
    if (i === -1) {
        return {
            title: raw.trim(),
            body: "",
        };
    }
    const title = raw.slice(0, i).trim();
    const body = raw.slice(i + 1).trim(); // sisa setelah koma pertama
    return {
        title,
        body,
    };
}

// ====== Fungsi untuk mendapatkan kode unik pada setiap device yg akses web ======
function getOrCreateDeviceId() {
    let id = localStorage.getItem("device_id");
    if (!id) {
        if (window.crypto && window.crypto.randomUUID) {
            id = crypto.randomUUID();
        } else {
            id =
                "dev-" +
                Math.random().toString(36).slice(2) +
                Date.now().toString(36);
        }
        localStorage.setItem("device_id", id);
    }
    return id;
}
