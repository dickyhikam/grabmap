<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>POI Scraper - History View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/maplibre-gl@4.x/dist/maplibre-gl.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* --- Base Layout --- */
        body,
        html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100%;
            overflow: hidden;
            background: #f4f4f4;
        }

        /* --- Header --- */
        #header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* --- Map & Center Wrapper --- */
        .container-result {
            position: relative;
            width: 100%;
            height: 100vh;
        }

        #map {
            width: 100%;
            height: 100%;
            background: #e5e5e5;
        }

        .search-center-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 600px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            max-height: 90vh;
            /* Agar tidak kepotong kalau list panjang */
        }

        /* --- Input Group --- */
        .gemini-input-group {
            position: relative;
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            padding: 5px;
            display: flex;
            align-items: flex-start;
            z-index: 2;
            margin-bottom: 10px;
        }

        .gemini-textarea {
            flex: 1;
            border: none;
            outline: none;
            resize: none;
            padding: 15px;
            font-size: 16px;
            border-radius: 12px;
            min-height: 50px;
            font-family: inherit;
        }

        .gemini-send-btn {
            background: #00B14F;
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            margin: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: background 0.2s;
        }

        .gemini-send-btn:hover {
            background: #008c3e;
        }

        .gemini-send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* --- Progress Bar --- */
        .progress-wrapper {
            width: 100%;
            margin-bottom: 15px;
            background: white;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: none;
            box-sizing: border-box;
        }

        .progress-track {
            width: 100%;
            height: 6px;
            background-color: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #00B14F, #00dd63);
            transition: width 0.3s ease;
        }

        /* --- HISTORY CARD STYLES (NEW) --- */
        .history-card {
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: none;
            /* Hidden by default until first search */
            max-height: 350px;
            /* Limit height */
            display: flex;
            flex-direction: column;
        }

        .history-header {
            padding: 15px 20px;
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            font-weight: 700;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-list-container {
            overflow-y: auto;
            flex: 1;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.2s;
        }

        .history-item:hover {
            background: #fafafa;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        /* Left Side: Icon & Info */
        .h-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            overflow: hidden;
        }

        .h-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .h-icon-box.success {
            background: #e8f5e9;
            color: #00B14F;
        }

        .h-icon-box.fail {
            background: #ffebee;
            color: #d9534f;
        }

        .h-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .h-address {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .h-meta {
            font-size: 11px;
            color: #888;
            margin-top: 2px;
        }

        /* Right Side: Status & Action */
        .h-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
            margin-left: 10px;
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
    </style>
</head>

<body>
    <div id="header">
        <h3 style="margin:0; color:#00B14F;"><i class="fas fa-map-marked-alt"></i> POI Scraper Tool</h3>
        <div class="quota-badge" id="quotaBadge">
            <i class="fas fa-bolt"></i>
            <span>Credits: <b id="quotaCount">5</b>/5</span>
        </div>
    </div>

    <div class="container-result centered-layout">
        <div id="map"></div>

        <div class="search-center-wrapper">

            <div class="gemini-input-group">
                <textarea id="addressInput" class="gemini-textarea" placeholder="Enter address (Type 'error' to test fail case)..." rows="1"></textarea>
                <button id="btnScrap" class="gemini-send-btn" onclick="startProcess()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div id="progressSection" class="progress-wrapper">
                <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:2px;">
                    <span id="progressStatus">Starting...</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>

            <div id="historyCard" class="history-card" style="display:none;">
                <div class="history-header">
                    <span><i class="fas fa-history"></i> Recent Activity</span>
                    <span style="font-size:11px; color:#999; font-weight:400;">Last 5 results</span>
                </div>
                <div class="history-list-container" id="historyListBody">
                </div>
            </div>

        </div>
    </div>

    <div id="toast-container" style="position:fixed; bottom:30px; left:50%; transform:translateX(-50%); z-index:2000;"></div>

    <script>
        // --- Variables ---
        let currentQuota = 5;
        let historyLog = [];

        // --- Init ---
        const map = new maplibregl.Map({
            container: 'map',
            style: 'https://demotiles.maplibre.org/style.json',
            center: [106.8456, -6.2088],
            zoom: 13
        });

        $(document).ready(function() {
            // Auto-resize
            $('#addressInput').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                if (this.value === '') this.style.height = 'auto';
            });
        });

        // --- Logic ---
        function startProcess() {
            const address = $('#addressInput').val().trim();
            if (!address) return alert("Please enter address");
            if (currentQuota <= 0) return alert("Quota Exceeded");

            // UI State Loading
            $('#btnScrap').prop('disabled', true);
            $('#addressInput').prop('disabled', true);
            $('#progressSection').fadeIn();

            // Show Card if hidden (first time)
            $('#historyCard').fadeIn();

            // Simulate Process
            simulateScraping(address);
        }

        function simulateScraping(address) {
            let width = 0;
            const bar = $('#progressBar');
            const txt = $('#progressPercent');

            const interval = setInterval(() => {
                width += 10;
                if (width > 100) width = 100;
                bar.css('width', width + '%');
                txt.text(width + '%');

                if (width >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        finishScraping(address);
                    }, 500);
                }
            }, 150);
        }

        function finishScraping(address) {
            // Reset Loading UI
            $('#progressSection').fadeOut();
            $('#btnScrap').prop('disabled', false);
            $('#addressInput').prop('disabled', false).val('').focus();
            $('#progressBar').css('width', '0%');

            // --- SIMULASI HASIL (SUCCESS vs FAIL) ---
            // Jika user mengetik "error", kita simulasikan gagal
            const isError = address.toLowerCase().includes("error");

            let resultData;

            if (isError) {
                // FAIL CASE
                resultData = {
                    id: Date.now(),
                    address: address,
                    time: new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }),
                    status: 'fail',
                    message: 'Address not found',
                    count: 0
                };
            } else {
                // SUCCESS CASE
                currentQuota--;
                $('#quotaCount').text(currentQuota);

                resultData = {
                    id: Date.now(),
                    address: address,
                    time: new Date().toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }),
                    status: 'success',
                    message: '42 POI Found',
                    count: 42
                };
            }

            // Tambah ke Array History (Paling atas)
            historyLog.unshift(resultData);

            // Render Ulang List
            renderHistoryList();
        }

        function renderHistoryList() {
            const container = $('#historyListBody');
            container.empty();

            historyLog.forEach(item => {
                let statusHtml = '';
                let actionHtml = '';
                let iconHtml = '';

                if (item.status === 'success') {
                    // Tampilan Sukses (Hijau)
                    iconHtml = `<div class="h-icon-box success"><i class="fas fa-check"></i></div>`;

                    statusHtml = `<span class="status-badge success">${item.message}</span>`;

                    actionHtml = `
                        <button class="btn-download-sm" onclick="downloadFile('${item.address}')">
                            <i class="fas fa-download"></i> CSV
                        </button>
                    `;
                } else {
                    // Tampilan Gagal (Merah)
                    iconHtml = `<div class="h-icon-box fail"><i class="fas fa-times"></i></div>`;

                    statusHtml = `<span class="status-badge fail">Failed</span>`;

                    actionHtml = `<span class="fail-msg">${item.message}</span>`;
                }

                const html = `
                    <div class="history-item">
                        <div class="h-left">
                            ${iconHtml}
                            <div class="h-info">
                                <div class="h-address" title="${item.address}">${item.address}</div>
                                <div class="h-meta"><i class="far fa-clock"></i> ${item.time}</div>
                            </div>
                        </div>
                        <div class="h-right">
                            ${statusHtml}
                            <div style="margin-top:5px;">${actionHtml}</div>
                        </div>
                    </div>
                `;
                container.append(html);
            });
        }

        function downloadFile(name) {
            // Simulasi download
            const data = "Name,Category,Lat,Long\nA,Food,-6.2,106.8\nB,Shop,-6.21,106.81";
            const blob = new Blob([data], {
                type: 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `POI_${name}.csv`;
            a.click();
        }
    </script>
</body>

</html>