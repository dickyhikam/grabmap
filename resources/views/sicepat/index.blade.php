<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiCepat × GrabMaps — Akurasi Geocoding</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* Peta penuh di belakang; panel analisis di kiri, panel detail/keterangan di kanan. */
        :root {
            --ink: #1b1f24;
            --mut: #66707a;
            --line: #e4e7eb;
            --bg: #f5f6f8;
            --card: #ffffff;
            --brand: #00a651;
            --brand-soft: #e5f6ec;
            --c1: #0f9d58;      /* <= 50 m */
            --c2: #2a78d6;      /* 50–100 m */
            --c3: #e09b00;      /* 100–200 m */
            --c4: #d93636;      /* > 200 m */
            --c4-soft: #fdeaea;
            --pod: #1b1f24;
            --grab: #00b14f;    /* hasil pencarian lokasi */
            --grab-d: #00873c;
            --grab-soft: #e6f7ee;
            --left-w: 390px;
            --right-w: 360px;
            --sans: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        html, body { height: 100%; }
        body { margin: 0; font: 13px/1.5 var(--sans); color: var(--ink); background: var(--bg); overflow: hidden; }
        button, select { font: inherit; color: inherit; }
        #map { position: absolute; inset: 0; }
        .num { font-family: var(--mono); font-variant-numeric: tabular-nums; }
        button:focus-visible, select:focus-visible, [tabindex]:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

        .float { position: absolute; z-index: 10; background: var(--card); border-radius: 14px; box-shadow: 0 10px 30px rgba(27, 31, 36, .16); }

        /* ---------- Bilah atas ---------- */
        .top { top: 12px; left: calc(var(--left-w) + 24px); right: calc(var(--right-w) + 24px); display: flex; align-items: center; gap: 10px; padding: 8px 12px; flex-wrap: wrap; }
        .top img { height: 22px; flex: none; }
        .top h1 { margin: 0; font-size: 13px; font-weight: 800; flex: 1 1 180px; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .top h1 span { color: #e3001b; }
        .chips { display: flex; gap: 5px; flex-wrap: wrap; }
        .chip { border: 1px solid var(--line); background: var(--card); border-radius: 999px; padding: 4px 10px 4px 8px; font-size: 11.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .chip[aria-pressed="false"] { opacity: .45; }
        .chip[aria-pressed="false"] .dot { background: var(--line) !important; }
        .dot { width: 10px; height: 10px; border-radius: 50%; flex: none; display: inline-block; }
        .seg { display: inline-grid; grid-auto-flow: column; gap: 3px; padding: 3px; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; }
        .seg button { border: 0; background: none; border-radius: 7px; padding: 5px 9px; font-weight: 700; font-size: 11.5px; cursor: pointer; color: var(--mut); white-space: nowrap; }
        .seg button.on { background: var(--card); color: var(--ink); box-shadow: 0 1px 4px rgba(27, 31, 36, .15); }

        /* ---------- Panel kiri: analisis ---------- */
        .left { top: 12px; left: 12px; bottom: 12px; width: var(--left-w); display: flex; flex-direction: column; overflow: hidden; }
        .head { padding: 14px 16px 10px; border-bottom: 1px solid var(--line); }
        .head h2 { margin: 0; font-size: 15px; font-weight: 800; }
        .head p { margin: 3px 0 0; color: var(--mut); font-size: 11.5px; }
        .meter { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1px; background: var(--line); border-bottom: 1px solid var(--line); }
        .meter div { background: var(--bg); padding: 8px 14px; }
        .meter b { display: block; font: 600 17px/1.3 var(--mono); }
        .meter span { color: var(--mut); font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
        .meter .bad b { color: var(--c4); }
        .filters { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; padding: 10px 14px; border-bottom: 1px solid var(--line); }
        .search { grid-column: 1 / -1; display: flex; gap: 6px; }
        .sbox { position: relative; flex: 1; min-width: 0; }
        .gbtn { flex: none; border: 0; border-radius: 8px; padding: 0 11px; background: var(--grab); color: #fff; font-weight: 700; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
        .gbtn:hover { background: var(--grab-d); }
        .gbtn:disabled { opacity: .55; cursor: progress; }
        .gres { border: 1.5px solid var(--grab); border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
        .gres h4 { margin: 0; padding: 7px 10px; background: var(--grab-soft); color: #064e2b; font-size: 11.5px; display: flex; align-items: center; gap: 6px; }
        .gres h4 span { margin-left: auto; font-weight: 600; color: var(--mut); font-size: 10.5px; }
        .gitem { display: grid; grid-template-columns: 22px 1fr auto; gap: 8px; padding: 8px 10px; border: 0; border-top: 1px solid var(--line); background: none; width: 100%; text-align: left; cursor: pointer; }
        .gitem:hover, .gitem.on { background: var(--grab-soft); }
        .gitem b { display: block; font-size: 12px; }
        .gitem small { display: block; color: var(--mut); font-size: 11px; overflow-wrap: anywhere; }
        .gitem .d { font: 600 11.5px var(--mono); white-space: nowrap; color: var(--grab-d); }
        .gnum { width: 20px; height: 20px; border-radius: 50%; background: var(--grab); color: #fff; font-size: 10.5px; font-weight: 800; display: grid; place-items: center; }
        .gres .msg { padding: 9px 10px; font-size: 11.5px; color: var(--mut); border-top: 1px solid var(--line); }
        .gres .msg.err { color: var(--c4); }
        .sbox i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--mut); font-size: 12px; }
        .search input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 7px 10px 7px 30px; background: var(--card); font: inherit; font-size: 12px; outline: none; }
        .search input:focus { border-color: var(--brand); }
        .item mark { background: #fff1a8; color: inherit; border-radius: 2px; padding: 0 1px; }
        .filters select { width: 100%; min-width: 0; border: 1px solid var(--line); border-radius: 8px; padding: 5px 6px; background: var(--card); font-size: 11.5px; }
        .ptabs { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); }
        .ptabs button { border: 0; background: none; padding: 9px 4px; font-weight: 700; font-size: 12px; color: var(--mut); cursor: pointer; border-bottom: 2px solid transparent; }
        .ptabs button.on { color: var(--ink); border-bottom-color: var(--brand); }
        .pbody { flex: 1; overflow-y: auto; padding: 12px 16px 18px; }
        .sec { font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--mut); font-weight: 700; margin: 16px 0 8px; }
        .sec:first-child { margin-top: 2px; }

        .finding { background: var(--brand-soft); border-radius: 10px; padding: 10px 12px; color: #0d3b24; }
        .finding p { margin: 0 0 6px; font-weight: 600; }
        .finding ul { margin: 0; padding-left: 17px; display: grid; gap: 5px; }

        .hist { display: grid; gap: 5px; }
        .hrow { display: grid; grid-template-columns: 72px 1fr 74px; gap: 8px; align-items: center; font-size: 11.5px; }
        .hrow .lab { color: var(--mut); font-family: var(--mono); font-size: 11px; }
        .hrow .val { text-align: right; font-family: var(--mono); font-size: 11px; }
        .bar { height: 12px; border-radius: 3px; background: var(--bg); overflow: hidden; }
        .bar i { display: block; height: 100%; border-radius: 3px; }
        .cut { position: relative; border-top: 1px dashed var(--mut); margin: 3px 0; }
        .cut span { position: absolute; right: 0; top: -8px; background: var(--card); padding-left: 4px; font-size: 10px; color: var(--mut); }

        table.t { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.t th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: var(--mut); padding: 5px 6px; border-bottom: 1px solid var(--line); font-weight: 700; }
        table.t td { padding: 7px 6px; border-bottom: 1px solid var(--line); vertical-align: top; }
        table.t .n { text-align: right; font-family: var(--mono); font-size: 11.5px; white-space: nowrap; }
        table.t tr.pick { cursor: pointer; }
        table.t tr.pick:hover td { background: var(--bg); }
        table.t tr.on td { background: var(--brand-soft); font-weight: 700; }
        .share { display: block; height: 4px; border-radius: 2px; background: var(--bg); margin-top: 4px; overflow: hidden; }
        .share i { display: block; height: 100%; background: var(--c1); }

        .item { display: grid; grid-template-columns: 12px 1fr auto; gap: 10px; padding: 9px 4px; border: 0; border-bottom: 1px solid var(--line); background: none; width: 100%; text-align: left; cursor: pointer; }
        .item:hover, .item.on { background: var(--bg); }
        .item .dot { margin-top: 5px; }
        .item .a { font-size: 12px; overflow-wrap: anywhere; }
        .item small { display: block; color: var(--mut); font-size: 11px; margin-top: 1px; }
        .item .d { font: 600 11.5px var(--mono); white-space: nowrap; }
        .hint { color: var(--mut); font-size: 11px; line-height: 1.5; margin: 8px 0 0; }

        /* ---------- Panel kanan: detail & keterangan ---------- */
        .right { top: 12px; right: 12px; bottom: 12px; width: var(--right-w); display: flex; flex-direction: column; overflow: hidden; }
        .right .pbody { padding-top: 14px; }
        .rhead { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--line); }
        .rhead h3 { margin: 0; font-size: 13.5px; font-weight: 800; flex: 1; }
        .icon-btn { border: 0; background: var(--bg); width: 28px; height: 28px; border-radius: 50%; cursor: pointer; display: grid; place-items: center; flex: none; }
        .legend { display: grid; grid-template-columns: 22px 1fr; gap: 7px 8px; align-items: center; }
        .legend .sym { display: grid; place-items: center; }
        .pod-sym { width: 10px; height: 10px; border-radius: 50%; background: #fff; border: 2.5px solid var(--pod); }
        .line-sym { width: 20px; border-top: 2px solid var(--c4); }
        .ring-sym { width: 18px; height: 18px; border-radius: 50%; border: 1.5px dashed var(--ink); }
        .note { border-radius: 10px; padding: 9px 11px; font-size: 11.5px; line-height: 1.5; margin: 10px 0 0; background: var(--bg); color: var(--mut); }

        .big { font: 700 26px/1.1 var(--mono); }
        .tag { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 2px 9px; font-size: 11px; font-weight: 700; color: #fff; }
        .addr { font-size: 13px; font-weight: 600; line-height: 1.45; overflow-wrap: anywhere; margin: 10px 0; }
        .kv { display: grid; grid-template-columns: 104px 1fr; gap: 6px 10px; font-size: 12px; margin: 0; }
        .kv dt { color: var(--mut); }
        .kv dd { margin: 0; overflow-wrap: anywhere; }
        .links { display: grid; gap: 6px; margin-top: 12px; }
        .links a { display: flex; align-items: center; gap: 6px; padding: 8px 10px; border: 1px solid var(--line); border-radius: 9px; color: var(--ink); text-decoration: none; font-weight: 600; font-size: 12px; }
        .links a:hover { background: var(--bg); }
        .why { margin-top: 12px; border-radius: 10px; padding: 9px 11px; font-size: 11.5px; line-height: 1.5; background: var(--c4-soft); color: #7a1d1d; }
        .why.ok { background: var(--brand-soft); color: #0d3b24; }

        /* Penanda alamat terpilih: label di kedua ujung dan jarak di tengah garis. */
        .mk { display: flex; flex-direction: column; align-items: center; pointer-events: none; }
        .mk .pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font: 700 11px/1.3 var(--sans); white-space: nowrap; color: #fff; box-shadow: 0 3px 10px rgba(27, 31, 36, .28); }
        .mk .stem { width: 2px; height: 8px; }
        .mk .pin { width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 1px 6px rgba(27, 31, 36, .45); }
        .mk.pod .pill, .mk.pod .stem { background: var(--ink); }
        .mk.pod .pin { background: #fff; border-color: var(--ink); border-width: 4px; }
        .gmk { display: flex; flex-direction: column; align-items: center; cursor: pointer; }
        .gmk .gnum { width: 24px; height: 24px; font-size: 11.5px; border: 2.5px solid #fff; box-shadow: 0 2px 8px rgba(27, 31, 36, .4); }
        .gmk .pill { display: inline-flex; max-width: 220px; overflow: hidden; text-overflow: ellipsis; padding: 3px 9px; border-radius: 999px; background: var(--grab); color: #fff; font: 700 11px/1.3 var(--sans); white-space: nowrap; box-shadow: 0 3px 10px rgba(27, 31, 36, .28); margin-bottom: 4px; }
        .gmk:not(.on) .pill { display: none; }
        .gmk.on .gnum { width: 30px; height: 30px; font-size: 13px; }
        .dist.g { border-color: var(--grab); }
        .dist { padding: 4px 10px; border-radius: 8px; background: #fff; border: 2px solid currentColor; font: 700 12px/1.2 var(--mono); box-shadow: 0 3px 10px rgba(27, 31, 36, .22); white-space: nowrap; pointer-events: none; }
        .dist small { display: block; font: 600 9.5px/1.2 var(--sans); color: var(--mut); text-transform: uppercase; letter-spacing: .05em; }

        .empty { position: absolute; inset: 0; z-index: 20; display: grid; place-items: center; padding: 24px; background: var(--bg); }
        .empty div { max-width: 460px; background: var(--card); border-radius: 14px; padding: 22px; box-shadow: 0 10px 30px rgba(27, 31, 36, .12); }
        .empty code { font-family: var(--mono); font-size: 11.5px; background: var(--bg); padding: 2px 6px; border-radius: 5px; overflow-wrap: anywhere; }

        @media (max-width: 1500px) and (min-width: 1181px) {
            .top h1 { display: none; }
        }

        @media (max-width: 1180px) {
            body { overflow: auto; }
            #map { position: relative; height: 60vh; inset: auto; }
            .float { position: relative; inset: auto; width: auto; margin: 12px; }
            .left { height: 640px; }
            .right { height: auto; max-height: none; }
            .right .pbody { overflow: visible; }
            .page { display: flex; flex-direction: column; }
            .top { order: -2; }
            #map { order: -1; }
        }
    </style>
</head>

<body>
    @if (!$rows)
        <div class="empty">
            <div>
                <h2 style="margin:0 0 6px;font-size:16px">Data SiCepat belum diimpor</h2>
                <p style="margin:0 0 12px;color:var(--mut)">Halaman ini membaca hasil impor file uji akurasi dari SiCepat. Jalankan perintah ini di server, lalu muat ulang halaman:</p>
                <code>php artisan demo:sicepat-import "/path/Check Accurary Grapmap - Sample.xlsx"</code>
            </div>
        </div>
    @endif

    <div class="page">
        <div id="map" role="region" aria-label="Peta titik hasil geocoding dan titik POD kurir"></div>

        <header class="top float">
            <img src="{{ asset('logo.png') }}" alt="GrabMaps">
            <h1><span>SiCepat</span> × GrabMaps — akurasi geocoding alamat</h1>
            <div class="chips" id="catChips" role="group" aria-label="Kategori jarak"></div>
            <div class="seg" id="styleSeg" title="Tampilan peta">
                <button type="button" data-style="Standard" class="on">Standar</button>
                <button type="button" data-style="Monochrome">Monokrom</button>
                <button type="button" data-style="Satellite" title="Citra satelit Esri, untuk mengecek apakah titik jatuh di bangunan">Satelit</button>
            </div>
        </header>

        <!-- ================= Analisis ================= -->
        <aside class="left float">
            <div class="head">
                <h2>Hasil uji SiCepat</h2>
                <p>
                    Titik hasil geocoding API dibandingkan dengan titik POD kurir.
                    @if ($source) Sumber: {{ $source }}@endif
                    @if ($importedAt) · diimpor {{ $importedAt->wib()->translatedFormat('d M Y H:i') }} WIB @endif
                </p>
            </div>
            <div class="meter">
                <div><b id="mN">—</b><span>Alamat</span></div>
                <div><b id="mMed">—</b><span>Median selisih</span></div>
                <div><b id="mIn">—</b><span>Dalam 200 m</span></div>
                <div class="bad"><b id="mOut">—</b><span>Meleset &gt; 200 m</span></div>
            </div>
            <div class="filters">
                <div class="search">
                    <div class="sbox">
                        <i class="bi bi-search"></i>
                        <input type="search" id="fText" placeholder="Cari alamat, kecamatan, kota, atau #nomor baris" autocomplete="off" aria-label="Cari alamat">
                    </div>
                    <button type="button" class="gbtn" id="gBtn" title="Cari teks ini di peta lewat AWS Location SearchText (Enter)"><i class="bi bi-geo-alt-fill"></i> Cari lokasi</button>
                </div>
                <select id="fCity" aria-label="Kota"><option value="">Semua kota</option></select>
                <select id="fQual" aria-label="Bentuk alamat">
                    <option value="">Semua alamat</option>
                    <option value="1">Nomor, tanpa catatan</option>
                    <option value="2">Nomor + catatan</option>
                    <option value="3">Tanpa nomor + catatan</option>
                    <option value="4">Tanpa nomor</option>
                </select>
                <select id="fPartner" aria-label="Marketplace">
                    <option value="">Semua partner</option>
                    <option value="Shopee">Shopee</option>
                    <option value="Tiktok">TikTok</option>
                </select>
            </div>
            <div class="ptabs" role="tablist">
                <button type="button" class="on" data-tab="sum" role="tab">Ringkasan</button>
                <button type="button" data-tab="city" role="tab">Per kota</button>
                <button type="button" data-tab="list" role="tab">Daftar <span id="listN"></span></button>
            </div>

            <div class="pbody" data-pane="sum">
                <div class="sec">Temuan</div>
                <div class="finding">
                    <p>Angka SiCepat benar: <span id="fOut">—</span> alamat meleset lebih dari 200 m. Tapi melesetnya tidak merata.</p>
                    <ul>
                        <li><b>Bentuk alamat paling menentukan.</b> Alamat dengan nomor rumah/blok dan tanpa catatan: median <b id="fGood">—</b>. Alamat tanpa nomor: median <b id="fBad">—</b>.</li>
                        <li><b id="fWorstCity">—</b> paling buruk: hanya <b id="fWorstIn">—</b> alamat masuk radius 200 m.</li>
                        <li><b>Catatan kurir ikut terkirim</b> sebagai alamat ("depan warung", "kontrakan hijau", "tolong masukin ke box"), dan teks seperti ini mengacaukan pencarian.</li>
                        <li><b>Titik POD adalah GPS HP kurir saat scan</b>, bukan titik rumah yang pasti. Selisih 10–30 m wajar walaupun hasilnya benar.</li>
                    </ul>
                </div>

                <div class="sec">Sebaran selisih jarak</div>
                <div class="hist" id="hist"></div>

                <div class="sec">Per bentuk alamat</div>
                <table class="t" id="tQual">
                    <thead><tr><th>Bentuk alamat</th><th class="n">Alamat</th><th class="n">Median</th><th class="n">≤ 200 m</th></tr></thead>
                    <tbody></tbody>
                </table>
                <p class="hint">Dipilah otomatis dari teks alamat. "Nomor" berarti ada No./nomor/blok. "Catatan" berarti ada patokan atau pesan ke kurir (dekat, depan, warung, kontrakan, warna, teks dalam kurung). Hasilnya perkiraan, jadi beberapa alamat bisa salah kelompok. Klik baris untuk memfilter.</p>
            </div>

            <div class="pbody" data-pane="city" hidden>
                <table class="t" id="tCity">
                    <thead><tr><th>Kota</th><th class="n">Alamat</th><th class="n">Median</th><th class="n">≤ 200 m</th></tr></thead>
                    <tbody></tbody>
                </table>
                <p class="hint">Klik kota untuk memfilter peta. Klik lagi untuk kembali ke semua kota.</p>
            </div>

            <div class="pbody" data-pane="list" hidden>
                <div id="gres" class="gres" hidden></div>
                <p class="hint" style="margin:0 0 6px">Urut dari yang paling jauh melesetnya. Klik untuk melihat di peta.</p>
                <div id="list"></div>
            </div>
        </aside>

        <!-- ================= Detail & keterangan ================= -->
        <aside class="right float">
            <div class="rhead">
                <h3 id="rTitle">Keterangan</h3>
                <button type="button" class="icon-btn" id="rClose" title="Tutup detail" hidden><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="pbody" id="paneLegend">
                <div class="sec">Cara membaca peta</div>
                <div class="legend">
                    <span class="sym"><span class="pod-sym"></span></span><span><b>Titik POD</b>: lokasi kurir saat scan terkirim, dipakai sebagai titik benar</span>
                    <span class="sym"><span class="dot" style="background:var(--c4)"></span></span><span><b>Hasil geocoding API</b>: warnanya sesuai kategori jarak</span>
                    <span class="sym"><span class="line-sym"></span></span><span><b>Garis penghubung</b>: makin panjang, makin jauh melesetnya</span>
                    <span class="sym"><span class="ring-sym"></span></span><span><b>Radius 200 m</b> di sekitar POD, muncul saat alamat dipilih</span>
                </div>

                <div class="sec">Kategori jarak (versi SiCepat)</div>
                <div class="legend" id="catLegend"></div>

                <div class="sec">Cara menghitung</div>
                <p style="margin:0">Jarak adalah garis lurus (haversine) antara titik hasil API dan titik POD. Hasil hitung ulang sama dengan kolom <i>difference (m)</i> di file SiCepat.</p>
                <p class="note"><b>Kota cocok 99%</b> di laporan SiCepat hanya berarti hasilnya tidak salah kota. Ukuran akurasi alamat adalah jarak ke titik POD, seperti di peta ini.</p>
                <p class="note">Klik titik di peta atau baris di daftar untuk melihat alamat lengkap dan detailnya.</p>
            </div>

            <div class="pbody" id="paneDetail" hidden></div>
        </aside>
    </div>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    <script>
        const ROWS = @json($rows);

        const CATS = {
            1: { label: 'Highly accurate', range: '≤ 50 m', v: '--c1' },
            2: { label: 'Accurate', range: '50–100 m', v: '--c2' },
            3: { label: 'Moderately accurate', range: '100–200 m', v: '--c3' },
            4: { label: 'Low accuracy', range: '> 200 m', v: '--c4' },
        };
        const QUAL = {
            1: 'Ada nomor/blok, tanpa catatan',
            2: 'Ada nomor/blok + catatan',
            3: 'Tanpa nomor, ada catatan',
            4: 'Tanpa nomor, tanpa catatan',
        };
        const BUCKETS = [[0, 50], [50, 100], [100, 200], [200, 500], [500, 1000], [1000, 2000], [2000, 5000], [5000, Infinity]];
        const LIMIT = 200; // batas "akurat" yang dipakai SiCepat, dalam meter

        const css = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
        const catColor = (c) => css(CATS[c].v);
        const catOf = (m) => m <= 50 ? 1 : m <= 100 ? 2 : m <= 200 ? 3 : 4;
        const fmtM = (m) => !isFinite(m) ? '—' : m >= 1000
            ? (m / 1000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' km'
            : Math.round(m).toLocaleString('id-ID') + ' m';
        const pct = (a, b) => b ? (a / b * 100).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%' : '—';
        const median = (xs) => {
            if (!xs.length) return NaN;
            const s = [...xs].sort((a, b) => a - b), m = s.length >> 1;
            return s.length % 2 ? s[m] : (s[m - 1] + s[m]) / 2;
        };
        const el = (tag, cls, text) => {
            const e = document.createElement(tag);
            if (cls) e.className = cls;
            if (text != null) e.textContent = text;
            return e;
        };
        const partnerName = (p) => p === 'Tiktok' ? 'TikTok' : p;

        const S = { cats: new Set([1, 2, 3, 4]), city: '', q: '', pt: '', text: '', sel: null, style: 'Standard' };

        // Pencarian tidak peka huruf besar dan aksen; "#16" mencari nomor baris.
        const norm = (s) => String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim();
        ROWS.forEach((r) => { r._t = norm(`${r.ad} ${r.ds} ${r.ct}`); });
        function textMatch(r) {
            if (!S.text) return true;
            const m = S.text.match(/^#\s*(\d+)$/);
            if (m) return r.i === +m[1];
            return S.text.split(' ').every((w) => r._t.includes(w));
        }
        const cities = [...new Set(ROWS.map((r) => r.ct))].sort();

        // ---------- Kontrol ----------
        const chips = document.getElementById('catChips');
        const catLegend = document.getElementById('catLegend');
        Object.entries(CATS).forEach(([c, k]) => {
            const b = el('button', 'chip');
            b.type = 'button'; b.dataset.cat = c; b.setAttribute('aria-pressed', 'true');
            const d = el('span', 'dot'); d.style.background = `var(${k.v})`;
            b.append(d, document.createTextNode(k.range));
            b.addEventListener('click', () => {
                S.cats.has(+c) ? S.cats.delete(+c) : S.cats.add(+c);
                b.setAttribute('aria-pressed', S.cats.has(+c));
                render(false);
            });
            chips.append(b);

            const s = el('span', 'sym'); const d2 = el('span', 'dot'); d2.style.background = `var(${k.v})`; s.append(d2);
            const t = el('span'); t.append(el('b', null, k.label), document.createTextNode(`: selisih ${k.range}`));
            catLegend.append(s, t);
        });

        const fCity = document.getElementById('fCity');
        cities.forEach((c) => fCity.add(new Option(c, c)));
        fCity.addEventListener('change', () => { S.city = fCity.value; render(); });
        document.getElementById('fQual').addEventListener('change', (e) => { S.q = e.target.value; render(); });
        document.getElementById('fPartner').addEventListener('change', (e) => { S.pt = e.target.value; render(); });

        // Mengetik membuka tab Daftar; Enter langsung memilih hasil teratas.
        const fText = document.getElementById('fText');
        let typing;
        fText.addEventListener('input', () => {
            clearTimeout(typing);
            typing = setTimeout(() => {
                S.text = norm(fText.value);
                if (S.text) openTab('list');
                render();
            }, 200);
        });
        fText.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            mapSearch();
        });
        // Tombol × di kolom pencarian juga menghapus marker hasil pencarian lokasi.
        fText.addEventListener('search', () => { if (!fText.value.trim()) clearMapSearch(); });
        document.getElementById('gBtn').addEventListener('click', mapSearch);

        // ---------- Pencarian lokasi (marker hijau) ----------
        const G = { q: '', places: [], active: 0, ref: null, error: null, bucket: null, loading: false };
        let gMarkers = [];

        const haversine = (lat1, lng1, lat2, lng2) => {
            const R = 6371000, r = Math.PI / 180;
            const h = Math.sin((lat2 - lat1) * r / 2) ** 2 + Math.cos(lat1 * r) * Math.cos(lat2 * r) * Math.sin((lng2 - lng1) * r / 2) ** 2;
            return 2 * R * Math.asin(Math.sqrt(h));
        };

        async function mapSearch() {
            const q = fText.value.trim();
            if (!q) return;
            clearTimeout(typing);
            S.text = norm(q);
            openTab('list');
            render(false);

            // Patokan jarak: alamat terpilih, atau satu-satunya alamat yang cocok dengan teks.
            if (!S.sel) {
                const hits = visible();
                if (hits.length === 1) select(hits[0].i, false);
            }
            G.ref = S.sel ? ROWS.find((r) => r.i === S.sel) : null;
            const near = G.ref ? { lat: G.ref.pl, lng: G.ref.pn } : { lat: map.getCenter().lat, lng: map.getCenter().lng };

            Object.assign(G, { q, places: [], active: 0, error: null, bucket: null, loading: true });
            drawMapSearch();
            const btn = document.getElementById('gBtn');
            btn.disabled = true;
            try {
                const url = `/api/sicepat/search?${new URLSearchParams({ q, lat: near.lat.toFixed(6), lng: near.lng.toFixed(6) })}`;
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.error || data.message || `HTTP ${res.status}`);
                G.places = (data.places || []).map((p) => ({ ...p, d: G.ref ? haversine(p.lat, p.lng, G.ref.pl, G.ref.pn) : null }));
                G.bucket = data.bucket;
            } catch (err) {
                G.error = err.message;
            } finally {
                G.loading = false;
                btn.disabled = false;
            }
            drawMapSearch();
            fitMapSearch();
        }

        function clearMapSearch() {
            Object.assign(G, { q: '', places: [], active: 0, ref: null, error: null, bucket: null, loading: false });
            drawMapSearch();
        }

        function fitMapSearch() {
            const p = G.places[G.active];
            if (!p) return;
            const b = new maplibregl.LngLatBounds([p.lng, p.lat], [p.lng, p.lat]);
            if (G.ref) { b.extend([G.ref.pn, G.ref.pl]); b.extend([G.ref.gn, G.ref.gl]); }
            else G.places.forEach((x) => b.extend([x.lng, x.lat]));
            map.fitBounds(b, { padding: pad(), maxZoom: 17, duration: 700 });
        }

        function setActive(i) {
            G.active = i;
            drawMapSearch();
            fitMapSearch();
        }

        function drawMapSearch() {
            // Daftar hasil di panel kiri.
            const box = document.getElementById('gres');
            box.replaceChildren();
            box.hidden = !G.q;
            if (G.q) {
                const h = el('h4');
                h.append(el('i', 'bi bi-geo-alt-fill'), document.createTextNode(`Lokasi "${G.q}"`));
                h.append(el('span', null, 'AWS Location' + (G.bucket ? ` · ${G.bucket}` : '')));
                box.append(h);
                if (G.loading) box.append(el('div', 'msg', 'Mencari…'));
                else if (G.error) box.append(el('div', 'msg err', `Gagal mencari: ${G.error}`));
                else if (!G.places.length) box.append(el('div', 'msg', 'Tidak ada lokasi yang ditemukan untuk teks ini.'));
                G.places.forEach((p, i) => {
                    const b = el('button', 'gitem' + (i === G.active ? ' on' : ''));
                    b.type = 'button';
                    const mid = el('span'); mid.append(el('b', null, p.name), el('small', null, p.address));
                    b.append(el('span', 'gnum', i + 1), mid, el('span', 'd', p.d != null ? fmtM(p.d) : ''));
                    b.addEventListener('click', () => setActive(i));
                    box.append(b);
                });
                if (G.places.length) {
                    box.append(el('div', 'msg', G.ref
                        ? `Jarak dihitung ke titik POD alamat #${G.ref.i}.`
                        : 'Pilih satu alamat dulu supaya jarak ke titik POD-nya ikut dihitung.'));
                }
            }

            // Marker hijau bernomor; yang aktif lebih besar dan berlabel nama.
            gMarkers.forEach((m) => m.remove());
            gMarkers = [];
            G.places.forEach((p, i) => {
                const m = el('div', 'gmk' + (i === G.active ? ' on' : ''));
                m.append(el('span', 'pill', p.name || p.address), el('span', 'gnum', i + 1));
                m.title = `${p.name}\n${p.address}`;
                m.addEventListener('click', (e) => { e.stopPropagation(); setActive(i); });
                const mk = new maplibregl.Marker({ element: m, anchor: 'bottom' }).setLngLat([p.lng, p.lat]).addTo(map);
                if (i === G.active) mk.getElement().style.zIndex = 5;
                gMarkers.push(mk);
            });

            // Garis putus-putus hijau dari hasil aktif ke titik POD, dengan jaraknya.
            const p = G.places[G.active];
            if (map.getSource('gline')) {
                map.getSource('gline').setData(p && G.ref ? {
                    type: 'Feature', properties: {},
                    geometry: { type: 'LineString', coordinates: [[p.lng, p.lat], [G.ref.pn, G.ref.pl]] },
                } : EMPTY);
            }
            if (p && G.ref) {
                const dist = el('div', 'dist g');
                dist.style.color = css('--grab');
                const v = el('span', null, fmtM(p.d)); v.style.color = 'var(--ink)';
                dist.append(el('small', null, 'ke POD'), v);
                gMarkers.push(new maplibregl.Marker({ element: dist, anchor: 'center' })
                    .setLngLat([(p.lng + G.ref.pn) / 2, (p.lat + G.ref.pl) / 2]).addTo(map));
            }
        }

        function openTab(tab) {
            document.querySelectorAll('.ptabs button').forEach((x) => x.classList.toggle('on', x.dataset.tab === tab));
            document.querySelectorAll('.left [data-pane]').forEach((p) => p.hidden = p.dataset.pane !== tab);
        }
        document.querySelectorAll('.ptabs button').forEach((b) => b.addEventListener('click', () => openTab(b.dataset.tab)));

        // ---------- Data terfilter ----------
        const matches = (r, skip) => (skip === 'cat' || S.cats.has(r.c))
            && (skip === 'city' || !S.city || r.ct === S.city)
            && (skip === 'q' || !S.q || r.q === +S.q)
            && (!S.pt || r.pt === S.pt)
            && textMatch(r);
        const visible = () => ROWS.filter((r) => matches(r));

        // ---------- Peta ----------
        const map = new maplibregl.Map({
            container: 'map',
            style: '/api/v2/map-style?style=Standard&color=Light',
            center: [113.5, -4.5],
            zoom: 4.3,
            attributionControl: false,
        });
        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
        map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-left');
        map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-left');

        const wide = () => window.innerWidth > 1180;
        const pad = () => wide()
            ? { left: parseInt(css('--left-w')) + 50, right: parseInt(css('--right-w')) + 50, top: 90, bottom: 40 }
            : 40;

        // Lingkaran radius 200 m digambar sebagai poligon supaya ukurannya benar di semua zoom.
        function ring(lng, lat, meters) {
            const pts = [];
            for (let i = 0; i <= 64; i++) {
                const a = (i / 64) * 2 * Math.PI;
                const dLat = (meters / 111320) * Math.cos(a);
                const dLng = (meters / (111320 * Math.cos(lat * Math.PI / 180))) * Math.sin(a);
                pts.push([lng + dLng, lat + dLat]);
            }
            return { type: 'Feature', geometry: { type: 'Polygon', coordinates: [pts] }, properties: {} };
        }

        function featureSets(rows) {
            const lines = [], pods = [], grabs = [];
            rows.forEach((r) => {
                // Alamat terpilih selalu digambar paling atas.
                const p = { i: r.i, c: r.c, k: r.i === S.sel ? 1e9 : -r.d, col: catColor(r.c) };
                lines.push({ type: 'Feature', properties: p, geometry: { type: 'LineString', coordinates: [[r.gn, r.gl], [r.pn, r.pl]] } });
                pods.push({ type: 'Feature', properties: p, geometry: { type: 'Point', coordinates: [r.pn, r.pl] } });
                grabs.push({ type: 'Feature', properties: p, geometry: { type: 'Point', coordinates: [r.gn, r.gl] } });
            });
            const fc = (features) => ({ type: 'FeatureCollection', features });
            return { lines: fc(lines), pods: fc(pods), grabs: fc(grabs) };
        }

        const EMPTY = { type: 'FeatureCollection', features: [] };

        function addLayers() {
            const sets = featureSets(visible());
            const sel = S.sel ? ROWS.find((r) => r.i === S.sel) : null;
            map.addSource('ring', { type: 'geojson', data: sel ? ring(sel.pn, sel.pl, LIMIT) : EMPTY });
            map.addSource('lines', { type: 'geojson', data: sets.lines });
            map.addSource('pods', { type: 'geojson', data: sets.pods });
            map.addSource('grabs', { type: 'geojson', data: sets.grabs });

            map.addLayer({ id: 'ring-fill', type: 'fill', source: 'ring', paint: { 'fill-color': css('--ink'), 'fill-opacity': 0.06 } });
            map.addLayer({ id: 'ring-line', type: 'line', source: 'ring', paint: { 'line-color': css('--ink'), 'line-width': 1.5, 'line-dasharray': [3, 2] } });
            map.addSource('selline', { type: 'geojson', data: EMPTY });
            map.addLayer({
                id: 'lines', type: 'line', source: 'lines',
                layout: { 'line-cap': 'round', 'line-sort-key': ['get', 'k'] },
                paint: { 'line-color': ['get', 'col'], 'line-width': 2, 'line-opacity': 0.8 },
            });
            map.addLayer({
                id: 'pods', type: 'circle', source: 'pods',
                layout: { 'circle-sort-key': ['get', 'k'] },
                paint: {
                    'circle-radius': ['interpolate', ['linear'], ['zoom'], 4, 2.5, 12, 4.5, 16, 6],
                    'circle-color': '#ffffff', 'circle-stroke-color': css('--pod'), 'circle-stroke-width': 2,
                },
            });
            map.addLayer({
                id: 'grabs', type: 'circle', source: 'grabs',
                layout: { 'circle-sort-key': ['get', 'k'] },
                paint: {
                    'circle-radius': ['interpolate', ['linear'], ['zoom'], 4, 4, 12, 6.5, 16, 8],
                    'circle-color': ['get', 'col'], 'circle-stroke-color': '#ffffff', 'circle-stroke-width': 1.5,
                },
            });
            // Garis terpilih di atas semua titik lain: casing putih lalu warna kategorinya.
            map.addLayer({ id: 'sel-casing', type: 'line', source: 'selline', layout: { 'line-cap': 'round' }, paint: { 'line-color': '#ffffff', 'line-width': 9 } });
            map.addLayer({ id: 'sel-line', type: 'line', source: 'selline', layout: { 'line-cap': 'round' }, paint: { 'line-color': ['get', 'col'], 'line-width': 5 } });
            map.addSource('gline', { type: 'geojson', data: EMPTY });
            map.addLayer({ id: 'gline', type: 'line', source: 'gline', paint: { 'line-color': css('--grab'), 'line-width': 3, 'line-dasharray': [2, 1.5] } });
            applySelection();
            if (typeof drawMapSearch === 'function' && G.q) drawMapSearch();

            ['grabs', 'pods'].forEach((id) => {
                map.on('click', id, (e) => select(e.features[0].properties.i, false));
                map.on('mouseenter', id, () => { map.getCanvas().style.cursor = 'pointer'; });
                map.on('mouseleave', id, () => { map.getCanvas().style.cursor = ''; });
            });
        }

        // Saat ada alamat terpilih, alamat lain diredupkan supaya pasangan titiknya jelas.
        function applySelection() {
            const sel = S.sel ? ROWS.find((r) => r.i === S.sel) : null;
            // Titik dan garis milik alamat terpilih disembunyikan di layer biasa,
            // karena sudah digambar ulang oleh sel-line dan penanda berlabel.
            const dim = (normal, others) => sel ? ['case', ['==', ['get', 'i'], sel.i], 0, others] : normal;
            map.setPaintProperty('lines', 'line-opacity', dim(0.8, 0.15));
            ['pods', 'grabs'].forEach((id) => {
                map.setPaintProperty(id, 'circle-opacity', dim(1, 0.3));
                map.setPaintProperty(id, 'circle-stroke-opacity', dim(1, 0.3));
            });
            map.getSource('ring').setData(sel ? ring(sel.pn, sel.pl, LIMIT) : EMPTY);
            map.getSource('selline').setData(sel ? {
                type: 'Feature', properties: { col: catColor(sel.c) },
                geometry: { type: 'LineString', coordinates: [[sel.gn, sel.gl], [sel.pn, sel.pl]] },
            } : EMPTY);
            drawSelMarkers(sel);
        }

        // Label "POD kurir", "Hasil API", dan jarak di tengah garis (penanda HTML, di atas kanvas).
        let selMarkers = [];
        function drawSelMarkers(r) {
            selMarkers.forEach((m) => m.remove());
            selMarkers = [];
            if (!r) return;
            const col = catColor(r.c);

            const pin = (cls, text, color) => {
                const box = el('div', 'mk ' + cls);
                const pill = el('span', 'pill', text);
                const stem = el('span', 'stem');
                const dot = el('span', 'pin');
                if (color) { pill.style.background = color; stem.style.background = color; dot.style.background = color; }
                box.append(pill, stem, dot);
                return box;
            };
            const podEl = pin('pod', 'POD kurir');
            const apiEl = pin('api', 'Hasil API', col);
            // Titik pin berada di dasar elemen; offset menaruh tengah bulatannya tepat di koordinat.
            selMarkers.push(new maplibregl.Marker({ element: podEl, anchor: 'bottom', offset: [0, 9] }).setLngLat([r.pn, r.pl]).addTo(map));
            selMarkers.push(new maplibregl.Marker({ element: apiEl, anchor: 'bottom', offset: [0, 8] }).setLngLat([r.gn, r.gl]).addTo(map));

            const dist = el('div', 'dist');
            dist.style.color = col;
            const v = el('span', null, fmtM(r.d)); v.style.color = 'var(--ink)';
            dist.append(el('small', null, 'selisih'), v);
            selMarkers.push(new maplibregl.Marker({ element: dist, anchor: 'center' })
                .setLngLat([(r.gn + r.pn) / 2, (r.gl + r.pl) / 2]).addTo(map));
        }

        function refreshMap(refit) {
            if (!map.getSource('lines')) return;
            const rows = visible();
            const sets = featureSets(rows);
            map.getSource('lines').setData(sets.lines);
            map.getSource('pods').setData(sets.pods);
            map.getSource('grabs').setData(sets.grabs);
            applySelection();
            if (refit && rows.length) {
                const b = new maplibregl.LngLatBounds();
                rows.forEach((r) => { b.extend([r.gn, r.gl]); b.extend([r.pn, r.pl]); });
                map.fitBounds(b, { padding: pad(), maxZoom: 15, duration: 600 });
            }
        }

        // Satelit bukan gaya GrabMaps (ap-southeast-1 hanya Standard/Monochrome),
        // jadi dipakai citra Esri khusus untuk mengecek titik di atas bangunan.
        const SATELLITE = {
            version: 8,
            sources: { sat: { type: 'raster', tileSize: 256, attribution: 'Imagery © Esri', tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'] } },
            layers: [{ id: 'sat', type: 'raster', source: 'sat' }],
        };

        document.querySelectorAll('#styleSeg button').forEach((b) => b.addEventListener('click', () => {
            if (S.style === b.dataset.style) return;
            S.style = b.dataset.style;
            document.querySelectorAll('#styleSeg button').forEach((x) => x.classList.toggle('on', x === b));
            map.setStyle(S.style === 'Satellite' ? SATELLITE : `/api/v2/map-style?style=${S.style}&color=Light`);
        }));
        map.on('style.load', () => { addLayers(); });
        let firstFit = true;
        map.on('load', () => { if (firstFit) { firstFit = false; refreshMap(true); } });

        // ---------- Detail ----------
        const paneLegend = document.getElementById('paneLegend');
        const paneDetail = document.getElementById('paneDetail');
        const rTitle = document.getElementById('rTitle');
        const rClose = document.getElementById('rClose');
        rClose.addEventListener('click', () => select(null));

        function mapsLink(text, lat, lng) {
            const a = el('a');
            a.href = `https://www.google.com/maps?q=${lat},${lng}`; a.target = '_blank'; a.rel = 'noopener';
            const i = el('i', 'bi bi-box-arrow-up-right');
            a.append(i, document.createTextNode(text));
            return a;
        }

        function showDetail(r) {
            paneDetail.replaceChildren();
            const top = el('div');
            top.style.cssText = 'display:flex;align-items:flex-end;justify-content:space-between;gap:10px';
            const big = el('div'); big.append(el('div', 'big', fmtM(r.d)), el('small', null, 'selisih ke titik POD'));
            big.querySelector('small').style.color = 'var(--mut)';
            const tag = el('span', 'tag', CATS[r.c].label); tag.style.background = catColor(r.c);
            top.append(big, tag);
            paneDetail.append(top);
            paneDetail.append(el('p', 'addr', r.ad));

            const kv = el('dl', 'kv');
            const add = (k, v, mono) => { kv.append(el('dt', null, k)); const dd = el('dd', mono ? 'num' : null, v); kv.append(dd); };
            add('Kecamatan', r.ds);
            add('Kota', r.ct);
            add('Partner', partnerName(r.pt));
            add('Bentuk alamat', QUAL[r.q]);
            add('Hasil API', `${r.gl.toFixed(6)}, ${r.gn.toFixed(6)}`, true);
            add('POD kurir', `${r.pl.toFixed(6)}, ${r.pn.toFixed(6)}`, true);
            add('No. baris', `#${r.i}`, true);
            paneDetail.append(kv);

            const why = el('div', 'why' + (r.d <= LIMIT ? ' ok' : ''));
            why.textContent = r.d <= LIMIT
                ? 'Masuk radius 200 m. Titik hasil API berada di sekitar lokasi kurir saat mengantar.'
                : r.q >= 3
                    ? 'Alamat ini tidak punya nomor rumah atau blok, jadi API hanya bisa menunjuk jalan, kampung, atau desa. Hasilnya wajar meleset.'
                    : r.q === 2
                        ? 'Alamat punya nomor, tapi ikut membawa catatan patokan untuk kurir. Catatan seperti ini bisa membuat API memilih tempat lain.'
                        : 'Alamat cukup lengkap, tapi titiknya tetap meleset. Bisa karena nomor rumah belum ada di data peta, atau titik POD diambil jauh dari rumah. Layak dicek di tampilan satelit.';
            paneDetail.append(why);

            const links = el('div', 'links');
            links.append(mapsLink('Buka titik POD di Google Maps', r.pl, r.pn), mapsLink('Buka titik hasil API di Google Maps', r.gl, r.gn));
            paneDetail.append(links);
        }

        function select(i, fly = true) {
            S.sel = i;
            const r = i ? ROWS.find((x) => x.i === i) : null;
            paneLegend.hidden = !!r;
            paneDetail.hidden = !r;
            rClose.hidden = !r;
            rTitle.textContent = r ? 'Detail alamat' : 'Keterangan';
            if (r) showDetail(r);
            document.querySelectorAll('.item').forEach((it) => it.classList.toggle('on', +it.dataset.i === S.sel));
            refreshMap(false);
            // Hasil pencarian lokasi yang sudah ada diukur ulang ke POD alamat yang baru dipilih.
            if (G.places.length) {
                G.ref = r;
                G.places.forEach((p) => { p.d = r ? haversine(p.lat, p.lng, r.pl, r.pn) : null; });
                drawMapSearch();
            }
            if (r && fly) {
                const b = new maplibregl.LngLatBounds([r.gn, r.gl], [r.gn, r.gl]);
                b.extend([r.pn, r.pl]);
                const rb = ring(r.pn, r.pl, LIMIT).geometry.coordinates[0];
                rb.forEach((p) => b.extend(p));
                map.fitBounds(b, { padding: pad(), maxZoom: 18, duration: 700 });
            }
        }

        // ---------- Panel analisis ----------
        function drawMeter(rows) {
            const inside = rows.filter((r) => r.d <= LIMIT).length;
            document.getElementById('mN').textContent = rows.length.toLocaleString('id-ID');
            document.getElementById('mMed').textContent = fmtM(median(rows.map((r) => r.d)));
            document.getElementById('mIn').textContent = pct(inside, rows.length);
            document.getElementById('mOut').textContent = rows.length ? `${rows.length - inside} · ${pct(rows.length - inside, rows.length)}` : '—';
            document.getElementById('listN').textContent = `(${rows.length})`;
        }

        function drawFindings() {
            const out = ROWS.filter((r) => r.d > LIMIT).length;
            document.getElementById('fOut').textContent = `${out} dari ${ROWS.length} (${pct(out, ROWS.length)})`;
            document.getElementById('fGood').textContent = fmtM(median(ROWS.filter((r) => r.q === 1).map((r) => r.d)));
            document.getElementById('fBad').textContent = fmtM(median(ROWS.filter((r) => r.q >= 3).map((r) => r.d)));
            const worst = cities.map((c) => {
                const g = ROWS.filter((r) => r.ct === c);
                return { c, n: g.length, in: g.filter((r) => r.d <= LIMIT).length };
            }).sort((a, b) => a.in / a.n - b.in / b.n)[0];
            if (worst) {
                document.getElementById('fWorstCity').textContent = worst.c;
                document.getElementById('fWorstIn').textContent = `${worst.in} dari ${worst.n}`;
            }
        }

        function drawHist(rows) {
            const hist = document.getElementById('hist');
            hist.replaceChildren();
            const counts = BUCKETS.map(([a, b]) => rows.filter((r) => (a === 0 ? r.d >= 0 : r.d > a) && r.d <= b).length);
            const max = Math.max(1, ...counts);
            const lab = ([a, b]) => b === Infinity ? '> 5 km' : `${a >= 1000 ? a / 1000 : a}–${b >= 1000 ? b / 1000 + ' km' : b + ' m'}`;
            BUCKETS.forEach((bk, i) => {
                if (bk[0] === LIMIT) { const cut = el('div', 'cut'); cut.append(el('span', null, 'batas 200 m')); hist.append(cut); }
                const row = el('div', 'hrow');
                const bar = el('div', 'bar'); const fill = el('i');
                fill.style.width = (counts[i] / max * 100) + '%';
                fill.style.background = catColor(catOf(Math.min(bk[1], 1e9)));
                bar.append(fill);
                row.append(el('span', 'lab', lab(bk)), bar, el('span', 'val', `${counts[i]} · ${pct(counts[i], rows.length)}`));
                hist.append(row);
            });
        }

        // Tabel kelompok tidak ikut filter kategori jarak, supaya persentase ≤ 200 m tetap jujur.
        function drawGroup(tableId, skip, keys, keyOf, labelOf, active, onPick) {
            const tb = document.querySelector(`#${tableId} tbody`);
            tb.replaceChildren();
            const rows = ROWS.filter((r) => (skip === 'city' || !S.city || r.ct === S.city) && (skip === 'q' || !S.q || r.q === +S.q) && (!S.pt || r.pt === S.pt) && textMatch(r));
            keys.forEach((k) => {
                const g = rows.filter((r) => keyOf(r) === k);
                if (!g.length) return;
                const inside = g.filter((r) => r.d <= LIMIT).length;
                const tr = el('tr', 'pick' + (String(active) === String(k) ? ' on' : ''));
                tr.tabIndex = 0;
                const name = el('td', null, labelOf(k));
                const share = el('span', 'share'); const si = el('i'); si.style.width = (inside / g.length * 100) + '%'; share.append(si); name.append(share);
                tr.append(name, el('td', 'n', g.length), el('td', 'n', fmtM(median(g.map((r) => r.d)))), el('td', 'n', `${inside} · ${pct(inside, g.length)}`));
                const pick = () => onPick(String(active) === String(k) ? '' : String(k));
                tr.addEventListener('click', pick);
                tr.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
                tb.append(tr);
            });
        }

        // Kata yang dicari ditandai <mark>; disusun dari node teks, bukan innerHTML.
        function highlighted(cls, text) {
            const box = el('div', cls);
            const words = S.text && !S.text.startsWith('#') ? S.text.split(' ').filter(Boolean) : [];
            if (!words.length) { box.textContent = text; return box; }
            const re = new RegExp('(' + words.map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|') + ')', 'gi');
            text.split(re).forEach((part, i) => box.append(i % 2 ? el('mark', null, part) : document.createTextNode(part)));
            return box;
        }

        function drawList(rows) {
            const list = document.getElementById('list');
            list.replaceChildren();
            if (!rows.length) {
                list.append(el('p', 'hint', S.text ? `Tidak ada alamat yang cocok dengan "${fText.value.trim()}". Coba kata lain atau longgarkan filter.` : 'Tidak ada alamat untuk filter ini.'));
                return;
            }
            [...rows].sort((a, b) => b.d - a.d).forEach((r) => {
                const b = el('button', 'item' + (S.sel === r.i ? ' on' : ''));
                b.type = 'button'; b.dataset.i = r.i;
                const dot = el('span', 'dot'); dot.style.background = catColor(r.c);
                const mid = el('span'); mid.append(highlighted('a', r.ad), el('small', null, `#${r.i} · ${r.ds}, ${r.ct} · ${partnerName(r.pt)}`));
                b.append(dot, mid, el('span', 'd', fmtM(r.d)));
                b.addEventListener('click', () => select(r.i));
                list.append(b);
            });
        }

        function render(refit = true) {
            const rows = visible();
            drawMeter(rows);
            drawHist(rows);
            drawList(rows);
            drawGroup('tQual', 'q', [1, 2, 3, 4], (r) => r.q, (k) => QUAL[k], S.q, (v) => { S.q = v; document.getElementById('fQual').value = v; render(); });
            drawGroup('tCity', 'city', cities, (r) => r.ct, (k) => k, S.city, (v) => { S.city = v; fCity.value = v; render(); });
            if (S.sel && !rows.some((r) => r.i === S.sel)) select(null);
            refreshMap(refit);
        }

        drawFindings();
        render(false);
    </script>
</body>

</html>
