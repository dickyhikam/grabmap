<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KFC × GrabMaps — Studi Kasus Pencarian Alamat HMD</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* Peta penuh di belakang; skenario di kiri, temuan & rekomendasi di kanan. */
        :root {
            --ink: #1b1f24;
            --mut: #66707a;
            --line: #e4e7eb;
            --bg: #f5f6f8;
            --card: #ffffff;
            --brand: #00a651;
            --brand-d: #00873c;
            --brand-soft: #e5f6ec;
            --user: #2a63d6;
            --user-soft: #e8effc;
            --m3: #0f9d58;      /* persis */
            --m2: #2a78d6;      /* persis, tapi ada blok */
            --m1: #e09b00;      /* mirip */
            --m0: #8a939c;      /* lain */
            --bad-soft: #fdeaea;
            --left-w: 410px;
            --right-w: 360px;
            --sans: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        * { box-sizing: border-box; }
        [hidden] { display: none !important; }
        html, body { height: 100%; }
        body { margin: 0; font: 13px/1.5 var(--sans); color: var(--ink); background: var(--bg); overflow: hidden; }
        button, select, input { font: inherit; color: inherit; }
        #map { position: absolute; inset: 0; }
        .num { font-family: var(--mono); font-variant-numeric: tabular-nums; }
        button:focus-visible, select:focus-visible, input:focus-visible, [tabindex]:focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

        .float { position: absolute; z-index: 10; background: var(--card); border-radius: 14px; box-shadow: 0 10px 30px rgba(27, 31, 36, .16); }

        /* ---------- Bilah atas ---------- */
        .top { top: 12px; left: calc(var(--left-w) + 24px); right: calc(var(--right-w) + 24px); display: flex; align-items: center; gap: 10px; padding: 8px 14px; }
        .top img { height: 22px; flex: none; }
        .top h1 { margin: 0; font-size: 13px; font-weight: 800; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .top h1 span { color: #e4002b; }

        /* ---------- Panel kiri ---------- */
        .left { top: 12px; left: 12px; bottom: 12px; width: var(--left-w); display: flex; flex-direction: column; overflow: hidden; }
        .head { padding: 14px 16px 10px; border-bottom: 1px solid var(--line); }
        .head h2 { margin: 0; font-size: 15px; font-weight: 800; }
        .head p { margin: 3px 0 0; color: var(--mut); font-size: 11.5px; }
        .form { padding: 10px 14px 12px; border-bottom: 1px solid var(--line); display: grid; gap: 8px; }
        .qrow { display: flex; gap: 6px; }
        .sbox { position: relative; flex: 1; min-width: 0; }
        .sbox i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--mut); font-size: 12px; }
        .sbox input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px 8px 30px; background: var(--card); font-size: 12.5px; outline: none; }
        .sbox input:focus { border-color: var(--brand); }
        .gbtn { flex: none; border: 0; border-radius: 8px; padding: 0 12px; background: var(--brand); color: #fff; font-weight: 700; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
        .gbtn:hover { background: var(--brand-d); }
        .gbtn:disabled { opacity: .55; cursor: progress; }
        .lbl { font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--mut); font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .lbl .num { text-transform: none; letter-spacing: 0; font-weight: 600; margin-left: auto; }
        .presets { display: flex; flex-wrap: wrap; gap: 5px; }
        .presets button { border: 1px solid var(--line); background: var(--card); border-radius: 999px; padding: 3px 10px; font-size: 11.5px; font-weight: 600; cursor: pointer; }
        .presets button.on { background: var(--user-soft); border-color: var(--user); color: var(--user); }
        .arow { display: flex; align-items: center; gap: 8px; }
        .arow select { flex: 1; min-width: 0; border: 1px solid var(--line); border-radius: 8px; padding: 5px 6px; background: var(--card); font-size: 11.5px; }

        .pbody { flex: 1; overflow-y: auto; padding: 12px 14px 18px; }
        .sec { font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--mut); font-weight: 700; margin: 16px 0 8px; }
        .sec:first-child { margin-top: 2px; }

        /* Kartu skenario */
        .scen { border: 1.5px solid var(--line); border-radius: 12px; margin-bottom: 10px; overflow: hidden; cursor: pointer; background: var(--card); }
        .scen.on { border-color: var(--ink); box-shadow: 0 4px 14px rgba(27, 31, 36, .12); }
        .scen.rec { border-style: dashed; border-color: var(--brand); }
        .scen.rec.on { border-style: solid; }
        .shead { display: grid; grid-template-columns: 26px 1fr auto; gap: 8px; align-items: start; padding: 9px 10px; background: var(--bg); }
        .scen.rec .shead { background: var(--brand-soft); }
        .sk { width: 24px; height: 24px; border-radius: 7px; background: var(--ink); color: #fff; font-weight: 800; font-size: 12px; display: grid; place-items: center; }
        .scen.rec .sk { background: var(--brand); }
        .shead b { display: block; font-size: 12.5px; line-height: 1.3; }
        .shead small { display: block; color: var(--mut); font-size: 11px; font-family: var(--mono); }
        .bucket { font: 600 10px var(--mono); padding: 2px 6px; border-radius: 5px; background: var(--card); border: 1px solid var(--line); white-space: nowrap; color: var(--mut); }
        .ritem { display: grid; grid-template-columns: 22px 1fr auto; gap: 8px; padding: 7px 10px; border: 0; border-top: 1px solid var(--line); background: none; width: 100%; text-align: left; cursor: pointer; }
        .ritem:hover, .ritem.on { background: var(--bg); }
        .ritem .t { font-size: 12px; font-weight: 700; overflow-wrap: anywhere; }
        .ritem small { display: block; color: var(--mut); font-size: 11px; overflow-wrap: anywhere; }
        .ritem .d { font: 600 11px var(--mono); white-space: nowrap; text-align: right; }
        .ritem .d .b { display: block; margin-top: 3px; }
        .rk { width: 20px; height: 20px; border-radius: 50%; color: #fff; font-size: 10.5px; font-weight: 800; display: grid; place-items: center; }
        .mb { display: inline-block; font: 700 10px var(--sans); padding: 1px 6px; border-radius: 999px; color: #fff; }
        .moved { font: 600 10px var(--mono); color: var(--brand-d); }
        .scen .msg { padding: 9px 10px; font-size: 11.5px; color: var(--mut); border-top: 1px solid var(--line); }
        .scen .msg.err { color: #b42318; background: var(--bad-soft); }

        /* ---------- Panel kanan ---------- */
        .right { top: 12px; right: 12px; bottom: 12px; width: var(--right-w); display: flex; flex-direction: column; overflow: hidden; }
        .rhead { padding: 12px 16px; border-bottom: 1px solid var(--line); }
        .rhead h3 { margin: 0; font-size: 13.5px; font-weight: 800; }
        .finding { background: var(--bad-soft); border-radius: 10px; padding: 10px 12px; color: #7a1d1d; }
        .finding.ok { background: var(--brand-soft); color: #0d3b24; }
        .finding p { margin: 0 0 6px; font-weight: 700; }
        .finding p:last-child { margin: 0; }
        .dup { display: grid; gap: 4px; margin-top: 6px; }
        .dup button { display: grid; grid-template-columns: 12px 1fr auto; gap: 8px; align-items: center; border: 0; background: rgba(255, 255, 255, .7); border-radius: 7px; padding: 5px 8px; text-align: left; cursor: pointer; font-size: 11.5px; }
        .dup button:hover { background: #fff; }
        .dup .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dup .num { color: var(--mut); font-size: 11px; }
        ol.steps, ul.plain { margin: 0; padding-left: 18px; display: grid; gap: 6px; font-size: 12px; }
        ul.plain li::marker { color: var(--mut); }
        code, .code { font-family: var(--mono); font-size: 11px; background: var(--bg); padding: 1px 5px; border-radius: 4px; }
        .legend { display: grid; grid-template-columns: 18px 1fr; gap: 6px 8px; align-items: center; font-size: 12px; }
        .legend .dot { width: 12px; height: 12px; border-radius: 50%; justify-self: center; }
        .legend .usym { width: 14px; height: 14px; border-radius: 50%; background: var(--user); border: 3px solid #fff; box-shadow: 0 0 0 1.5px var(--user); justify-self: center; }
        details.req { margin-top: 10px; border: 1px solid var(--line); border-radius: 9px; }
        details.req summary { padding: 7px 10px; font-weight: 700; font-size: 11.5px; cursor: pointer; }
        details.req pre { margin: 0; padding: 8px 10px; border-top: 1px solid var(--line); background: var(--bg); font: 11px/1.5 var(--mono); white-space: pre-wrap; overflow-wrap: anywhere; border-radius: 0 0 9px 9px; }
        .note { border-radius: 10px; padding: 9px 11px; font-size: 11.5px; line-height: 1.5; margin: 10px 0 0; background: var(--bg); color: var(--mut); }

        /* ---------- Penanda peta ---------- */
        .umk { display: flex; flex-direction: column; align-items: center; cursor: grab; }
        .umk .pill { padding: 3px 9px; border-radius: 999px; background: var(--user); color: #fff; font: 700 11px/1.3 var(--sans); white-space: nowrap; box-shadow: 0 3px 10px rgba(27, 31, 36, .28); margin-bottom: 4px; }
        .umk .pin { width: 20px; height: 20px; border-radius: 50%; background: var(--user); border: 4px solid #fff; box-shadow: 0 0 0 2px var(--user), 0 2px 8px rgba(27, 31, 36, .4); }
        .rmk { display: flex; flex-direction: column; align-items: center; cursor: pointer; }
        .rmk .rk { width: 26px; height: 26px; font-size: 12px; border: 2.5px solid #fff; box-shadow: 0 2px 8px rgba(27, 31, 36, .4); }
        .rmk .pill { max-width: 240px; overflow: hidden; text-overflow: ellipsis; padding: 3px 9px; border-radius: 999px; background: var(--ink); color: #fff; font: 700 11px/1.3 var(--sans); white-space: nowrap; box-shadow: 0 3px 10px rgba(27, 31, 36, .28); margin-bottom: 4px; }
        .rmk:not(.on) .pill { display: none; }
        .rmk.on .rk { width: 32px; height: 32px; font-size: 13.5px; }
        .dist { padding: 4px 10px; border-radius: 8px; background: #fff; border: 2px solid var(--user); font: 700 12px/1.2 var(--mono); box-shadow: 0 3px 10px rgba(27, 31, 36, .22); white-space: nowrap; pointer-events: none; }
        .dist small { display: block; font: 600 9.5px/1.2 var(--sans); color: var(--mut); text-transform: uppercase; letter-spacing: .05em; }

        @media (max-width: 1500px) and (min-width: 1181px) {
            .top h1 { font-size: 12px; }
        }

        @media (max-width: 1180px) {
            body { overflow: auto; }
            .page { display: flex; flex-direction: column; }
            #map { position: relative; height: 60vh; inset: auto; order: -1; }
            .float { position: relative; inset: auto; width: auto; margin: 12px; }
            .top { order: -2; }
            .left { height: auto; }
            .left .pbody, .right .pbody { overflow: visible; }
            .right { height: auto; }
        }
    </style>
</head>

<body>
    <div class="page">
        <div id="map" role="region" aria-label="Peta posisi user dan hasil pencarian alamat"></div>

        <header class="top float">
            <img src="{{ asset('logo.png') }}" alt="GrabMaps">
            <h1><span>KFC</span> × GrabMaps — studi kasus HMD: hasil pencarian alamat dipengaruhi posisi user</h1>
        </header>

        <!-- ================= Skenario ================= -->
        <aside class="left float" aria-label="Skenario pencarian">
            <div class="head">
                <h2>Uji pencarian alamat</h2>
                <p>Query yang sama dikirim ke Amazon Location (data GrabMaps, <span class="num">ap-southeast-1</span>) dengan cara berbeda. Pilih kartu untuk melihat hasilnya di peta.</p>
            </div>
            <form class="form" id="form" autocomplete="off">
                <div class="qrow">
                    <div class="sbox">
                        <i class="bi bi-search"></i>
                        <input type="search" id="q" value="Jl. swakarsa no.4" aria-label="Alamat yang diketik user" required maxlength="200">
                    </div>
                    <button type="submit" class="gbtn" id="go"><i class="bi bi-play-fill"></i> Uji</button>
                </div>
                <div class="lbl"><i class="bi bi-person-fill"></i> Posisi user (GPS) <span class="num" id="uPos">—</span></div>
                <div class="presets" id="presets"></div>
                <div class="lbl" style="font-weight:600;text-transform:none;letter-spacing:0">Klik peta atau geser penanda biru untuk memindah posisi user.</div>
                <div class="arow">
                    <div class="lbl" style="flex:none"><i class="bi bi-bounding-box"></i> Area layanan (C &amp; D)</div>
                    <select id="area" aria-label="Area layanan">
                        <option value="jabodetabek">Jabodetabek</option>
                        <option value="jakarta">DKI Jakarta saja</option>
                        <option value="indonesia">Seluruh Indonesia</option>
                    </select>
                </div>
            </form>
            <div class="pbody" id="scenList"></div>
        </aside>

        <!-- ================= Temuan ================= -->
        <aside class="right float" aria-label="Temuan dan rekomendasi">
            <div class="rhead"><h3>Temuan &amp; rekomendasi</h3></div>
            <div class="pbody">
                <div class="sec">Alamat yang persis sama</div>
                <div id="dupBox" class="finding ok"><p>Jalankan uji untuk melihat hasil.</p></div>

                <div class="sec">Kenapa urutannya begitu</div>
                <ul class="plain">
                    <li><b>Bias wajib.</b> <code>Suggest</code> dan <code>SearchText</code> menolak permintaan (400) kalau tidak ada salah satu dari <code>BiasPosition</code>, <code>Filter.BoundingBox</code>, atau <code>Filter.Circle</code>. Posisi tidak bisa dimatikan sama sekali.</li>
                    <li><b>Suggest = autocomplete.</b> Mencocokkan awalan teks, jadi "no.4" juga cocok dengan "No. 4C". Dengan <code>BiasPosition</code>, yang lebih dekat ke GPS naik ke atas.</li>
                    <li><b>Nama jalan tidak unik.</b> "Jl. Swakarsa No. 4" ada di beberapa kota. Tanpa kota/kelurahan di query, API tidak bisa tahu mana yang dimaksud user.</li>
                    <li><b>Tanpa batas area justru lebih buruk.</b> Coba area "Seluruh Indonesia": hasil dari luar Jabodetabek ikut naik, dan <code>SearchText</code> bisa kosong.</li>
                    <li>Tidak ada parameter API untuk mengatur bobot ranking (mis. utamakan nomor rumah persis).</li>
                </ul>

                <div class="sec">Rekomendasi untuk aplikasi HMD KFC</div>
                <ol class="steps">
                    <li>Ganti <code>BiasPosition</code> dari GPS user dengan <code>Filter.BoundingBox</code> area layanan (skenario C). Hasil tetap dalam area, posisi user tidak lagi menentukan urutan.</li>
                    <li>Saat user menekan Enter atau memilih, pakai <code>SearchText</code>, jangan berhenti di daftar <code>Suggest</code>.</li>
                    <li>Urut ulang di aplikasi: nomor rumah dan nama jalan yang persis sama naik ke atas (skenario D).</li>
                    <li>Tampilkan kelurahan, kecamatan, dan kota di setiap baris hasil, dan arahkan user menambah kota/kelurahan saat mengetik.</li>
                </ol>

                <div class="sec">Pertanyaan untuk tim Grab</div>
                <ul class="plain">
                    <li>Apakah <code>Suggest</code>/<code>SearchText</code> bisa memberi bobot lebih besar ke nomor rumah yang persis sama dibanding jarak dan kecocokan awalan?</li>
                    <li>Apakah ada rencana parameter untuk mengatur ranking, atau dukungan <code>Geocode</code> untuk alamat terstruktur?</li>
                </ul>

                <div class="sec">Keterangan peta</div>
                <div class="legend">
                    <span class="usym"></span><span>Posisi user (GPS)</span>
                    <span class="dot" style="background:var(--m3)"></span><span>Persis: nama jalan &amp; nomor sama</span>
                    <span class="dot" style="background:var(--m2)"></span><span>Persis, tapi alamatnya pakai blok</span>
                    <span class="dot" style="background:var(--m1)"></span><span>Mirip: mis. No. 4C, Swakarsa II No. 4</span>
                    <span class="dot" style="background:var(--m0)"></span><span>Lain</span>
                </div>

                <details class="req" id="reqBox">
                    <summary>Request skenario terpilih</summary>
                    <pre id="reqPre">—</pre>
                </details>
                <p class="note">Jarak dihitung garis lurus dari posisi user. Kecocokan dinilai di halaman ini dari komponen <code>Street</code> dan <code>AddressNumber</code>, bukan dari skor API.</p>
            </div>
        </aside>
    </div>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const css = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        const PRESETS = [
            { k: 'pusat', name: 'Jakarta Pusat (Monas)', lat: -6.1754, lng: 106.8227 },
            { k: 'bekasi', name: 'Bekasi', lat: -6.2383, lng: 106.9756 },
            { k: 'tangerang', name: 'Tangerang', lat: -6.1783, lng: 106.6319 },
            { k: 'depok', name: 'Depok', lat: -6.4025, lng: 106.7942 },
        ];

        const SCEN = [
            { k: 'A', title: 'Saat ini, user mengetik (autocomplete)', sub: 'Suggest + BiasPosition = GPS user', api: 'suggest', mode: 'bias' },
            { k: 'B', title: 'Saat ini, user menekan Enter', sub: 'SearchText + BiasPosition = GPS user', api: 'text', mode: 'bias' },
            { k: 'C', title: 'Area layanan, GPS tidak dipakai', sub: 'SearchText + Filter.BoundingBox', api: 'text', mode: 'bbox' },
            { k: 'D', title: 'Usulan: C + urut ulang nomor persis', sub: 'diurutkan di aplikasi HMD KFC', from: 'C', rec: true },
        ];

        const LEVEL = [
            { name: 'Lain', col: '--m0' },
            { name: 'Mirip', col: '--m1' },
            { name: 'Persis + blok', col: '--m2' },
            { name: 'Persis', col: '--m3' },
        ];

        const S = { user: { ...PRESETS[0] }, preset: 'pusat', sel: 'A', focus: null, res: {}, q: '', busy: false };

        // ---------- Kecocokan alamat ----------
        // "Jalan"/"Jl." disamakan, tanda baca dibuang, supaya "Jl. swakarsa no.4" = "Jalan Swakarsa No. 4".
        const norm = (s) => String(s ?? '').toLowerCase()
            .replace(/[.,#/()-]/g, ' ')
            .replace(/\bjalan\b/g, 'jl')
            .replace(/\s+/g, ' ').trim();
        const bareStreet = (s) => norm(s).replace(/^jl\s+/, '').replace(/\b(rt|rw)\s*\d+\b/g, '').trim();

        function parseQuery(q) {
            const s = norm(q);
            const m = s.match(/\b(?:no|nomor|nomer|nmr)\s*(\d+[a-z]?)\b/) || s.match(/\b(\d+[a-z]?)\s*$/);
            return { num: m ? m[1] : null, street: bareStreet(m ? s.replace(m[0], ' ') : s) };
        }

        function matchLevel(p, Q) {
            if (!Q.num) return 0;
            const first = String(p.label || '').split(',')[0];
            const numSrc = norm(p.number || first.replace(/^.*?\b(no|nomor)\b/i, ''));
            const rn = (numSrc.match(/(\d+[a-z]?)/) || [])[1] || null;
            const rs = bareStreet(p.street || first.replace(/\b(no|nomor|blok)\b.*$/i, ''));
            const blok = /\bblok\b/.test(norm((p.number || '') + ' ' + first)) && !/\bblok\b/.test(norm(S.q));
            if (!rn || !rs) return 0;
            if (rs === Q.street && rn === Q.num) return blok ? 2 : 3;
            if ((rs === Q.street || rs.startsWith(Q.street + ' ')) && rn.startsWith(Q.num)) return 1;
            return 0;
        }

        function haversine(a, b) {
            const R = 6371000, r = Math.PI / 180;
            const dLat = (b.lat - a.lat) * r, dLng = (b.lng - a.lng) * r;
            const h = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLng / 2) ** 2;
            return 2 * R * Math.asin(Math.sqrt(h));
        }
        const km = (m) => m >= 1000 ? (m / 1000).toFixed(1).replace('.', ',') + ' km' : Math.round(m) + ' m';

        // Hasil skenario D = hasil C diurutkan ulang: level kecocokan dulu, urutan API sebagai penentu seri.
        function listFor(k) {
            const sc = SCEN.find((s) => s.k === k);
            const r = S.res[sc.from || k];
            if (!r || !r.places) return r;
            let places = r.places.map((p, i) => ({ ...p, apiRank: i + 1 }));
            if (sc.from) places = places.slice().sort((a, b) => (b.lvl - a.lvl) || (a.apiRank - b.apiRank));
            return { ...r, places };
        }

        // ---------- Peta ----------
        const map = new maplibregl.Map({
            container: 'map',
            style: '/api/v2/map-style?style=Standard&color=Light',
            center: [S.user.lng, S.user.lat],
            zoom: 10.3,
            attributionControl: false,
        });
        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
        map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-left');
        map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-left');

        const wide = () => window.innerWidth > 1180;
        const pad = () => wide()
            ? { left: parseInt(css('--left-w')) + 60, right: parseInt(css('--right-w')) + 60, top: 90, bottom: 50 }
            : 40;

        const uEl = document.createElement('div');
        uEl.className = 'umk';
        uEl.innerHTML = '<span class="pill">Posisi user</span><span class="pin"></span>';
        const uMarker = new maplibregl.Marker({ element: uEl, anchor: 'bottom', draggable: true, offset: [0, 10] })
            .setLngLat([S.user.lng, S.user.lat]).addTo(map);
        uMarker.on('dragend', () => { const p = uMarker.getLngLat(); setUser(p.lat, p.lng, null); });
        map.on('click', (e) => setUser(e.lngLat.lat, e.lngLat.lng, null));

        const EMPTY = { type: 'FeatureCollection', features: [] };
        map.on('load', () => {
            map.addSource('link', { type: 'geojson', data: EMPTY });
            map.addLayer({ id: 'link', type: 'line', source: 'link', paint: { 'line-color': css('--user'), 'line-width': 3, 'line-dasharray': [2, 1.5] } });
            drawMap(true);
        });

        let rMarkers = [];
        function drawMap(fit) {
            rMarkers.forEach((m) => m.remove());
            rMarkers = [];
            const r = listFor(S.sel);
            const places = (r && r.places) || [];
            const b = new maplibregl.LngLatBounds([S.user.lng, S.user.lat], [S.user.lng, S.user.lat]);

            // Digambar terbalik supaya peringkat 1 berada paling atas.
            places.slice().reverse().forEach((p) => {
                const rank = places.indexOf(p) + 1;
                const on = S.focus === p.id;
                const el = document.createElement('div');
                el.className = 'rmk' + (on ? ' on' : '');
                el.innerHTML = `<span class="pill">#${rank} ${esc(p.name)} · ${esc(p.city || '')}</span>`
                    + `<span class="rk" style="background:${css(LEVEL[p.lvl].col)}">${rank}</span>`;
                el.title = `#${rank} ${p.label}`;
                el.addEventListener('click', (e) => { e.stopPropagation(); focusPlace(p.id); });
                const mk = new maplibregl.Marker({ element: el, anchor: 'bottom' }).setLngLat([p.lng, p.lat]).addTo(map);
                if (on) mk.getElement().style.zIndex = 5;
                rMarkers.push(mk);
                b.extend([p.lng, p.lat]);
            });

            const fp = places.find((p) => p.id === S.focus);
            if (map.getSource('link')) {
                map.getSource('link').setData(fp ? {
                    type: 'Feature', properties: {},
                    geometry: { type: 'LineString', coordinates: [[S.user.lng, S.user.lat], [fp.lng, fp.lat]] },
                } : EMPTY);
            }
            if (fp) {
                const el = document.createElement('div');
                el.className = 'dist';
                el.innerHTML = `<small>dari posisi user</small>${km(haversine(S.user, fp))}`;
                rMarkers.push(new maplibregl.Marker({ element: el, anchor: 'center' })
                    .setLngLat([(S.user.lng + fp.lng) / 2, (S.user.lat + fp.lat) / 2]).addTo(map));
            }
            if (fit && places.length) map.fitBounds(b, { padding: pad(), maxZoom: 14, duration: 600 });
        }

        function focusPlace(id) {
            S.focus = S.focus === id ? null : id;
            renderList();
            drawMap(false);
            const r = listFor(S.sel);
            const p = r && r.places && r.places.find((x) => x.id === S.focus);
            if (p) {
                const b = new maplibregl.LngLatBounds([S.user.lng, S.user.lat], [S.user.lng, S.user.lat]).extend([p.lng, p.lat]);
                map.fitBounds(b, { padding: pad(), maxZoom: 15, duration: 600 });
            }
        }

        // ---------- Posisi user ----------
        function renderPresets() {
            document.getElementById('presets').innerHTML = PRESETS.map((p) =>
                `<button type="button" data-k="${p.k}" class="${S.preset === p.k ? 'on' : ''}">${esc(p.name)}</button>`).join('');
            document.getElementById('uPos').textContent = `${S.user.lat.toFixed(4)}, ${S.user.lng.toFixed(4)}`;
        }
        document.getElementById('presets').addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-k]');
            if (!btn) return;
            const p = PRESETS.find((x) => x.k === btn.dataset.k);
            setUser(p.lat, p.lng, p.k);
        });

        function setUser(lat, lng, preset) {
            S.user = { lat, lng };
            S.preset = preset;
            uMarker.setLngLat([lng, lat]);
            renderPresets();
            if (S.q) run(['A', 'B']);
        }

        // ---------- Panggilan API ----------
        async function call(sc, Q) {
            const body = { q: S.q, api: sc.api, mode: sc.mode, area: document.getElementById('area').value };
            if (sc.mode === 'bias') { body.lat = S.user.lat; body.lng = S.user.lng; }
            try {
                const res = await fetch('/api/kfc/search', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(body),
                });
                const j = await res.json().catch(() => ({}));
                if (!res.ok) return { error: j.error || j.message || `HTTP ${res.status}`, request: j.request };
                j.places.forEach((p) => { p.lvl = matchLevel(p, Q); });
                return j;
            } catch (e) {
                return { error: e.message };
            }
        }

        async function run(keys) {
            S.q = document.getElementById('q').value.trim();
            if (!S.q) return;
            const Q = parseQuery(S.q);
            const todo = SCEN.filter((s) => !s.from && keys.includes(s.k));
            todo.forEach((s) => { S.res[s.k] = { loading: true }; });
            S.focus = null;
            renderList();
            const btn = document.getElementById('go');
            btn.disabled = true;
            const out = await Promise.all(todo.map((s) => call(s, Q)));
            todo.forEach((s, i) => { S.res[s.k] = out[i]; });
            btn.disabled = false;
            renderList();
            renderDup();
            drawMap(true);
        }

        document.getElementById('form').addEventListener('submit', (e) => { e.preventDefault(); run(['A', 'B', 'C']); });
        document.getElementById('area').addEventListener('change', () => { if (S.q) run(['C']); });

        // ---------- Daftar skenario ----------
        function renderList() {
            const box = document.getElementById('scenList');
            box.innerHTML = SCEN.map((sc) => {
                const r = listFor(sc.k);
                let body;
                if (!r) body = '<div class="msg">Belum diuji.</div>';
                else if (r.loading) body = '<div class="msg"><i class="bi bi-hourglass-split"></i> Memanggil API…</div>';
                else if (r.error) body = `<div class="msg err"><i class="bi bi-exclamation-triangle"></i> ${esc(r.error)}</div>`;
                else if (!r.places.length) body = '<div class="msg err">Tidak ada hasil.</div>';
                else body = r.places.map((p, i) => {
                    const lv = LEVEL[p.lvl];
                    const sub = [p.subdistrict, p.district, p.city].filter(Boolean).join(', ');
                    const moved = sc.from && p.apiRank !== i + 1 ? `<span class="moved">dari #${p.apiRank}</span>` : '';
                    const on = S.sel === sc.k && S.focus === p.id;
                    return `<button type="button" class="ritem${on ? ' on' : ''}" data-s="${sc.k}" data-id="${esc(p.id)}">
                        <span class="rk" style="background:${css(lv.col)}">${i + 1}</span>
                        <span><span class="t">${esc(p.name)}</span><small>${esc(p.street || '')}${p.number ? ' ' + esc(p.number) : ''} · ${esc(sub)}</small></span>
                        <span class="d">${km(haversine(S.user, p))}<span class="b"><span class="mb" style="background:${css(lv.col)}">${lv.name}</span></span>${moved}</span>
                    </button>`;
                }).join('');
                const area = document.getElementById('area');
                const sub = sc.mode === 'bbox' ? `${sc.sub} (${area.options[area.selectedIndex].text})` : sc.sub;
                return `<div class="scen${S.sel === sc.k ? ' on' : ''}${sc.rec ? ' rec' : ''}" data-s="${sc.k}">
                    <div class="shead"><span class="sk">${sc.k}</span><span><b>${esc(sc.title)}</b><small>${esc(sub)}</small></span>
                    ${r && r.bucket ? `<span class="bucket" title="Pricing bucket dari header x-amz-geo-pricing-bucket">${esc(r.bucket)}</span>` : ''}</div>
                    ${body}
                </div>`;
            }).join('');

            const r = listFor(S.sel);
            const sc = SCEN.find((s) => s.k === S.sel);
            document.getElementById('reqPre').textContent = r && r.request
                ? `${r.op || ''} (${sc.k})\n` + JSON.stringify(r.request, null, 2) + (sc.from ? '\n\n// lalu diurutkan ulang di aplikasi' : '')
                : '—';
        }

        document.getElementById('scenList').addEventListener('click', (e) => {
            const item = e.target.closest('.ritem');
            const card = e.target.closest('.scen');
            if (!card) return;
            const changed = S.sel !== card.dataset.s;
            S.sel = card.dataset.s;
            if (item) {
                if (changed) S.focus = null;
                focusPlace(item.dataset.id);
                return;
            }
            if (changed) {
                S.focus = null;
                renderList();
                drawMap(true);
            }
        });

        // ---------- Temuan: alamat persis sama di kota berbeda ----------
        function renderDup() {
            const box = document.getElementById('dupBox');
            const Q = parseQuery(S.q);
            const seen = new Map();
            ['A', 'B', 'C'].forEach((k) => {
                const r = S.res[k];
                (r && r.places || []).filter((p) => p.lvl >= 2).forEach((p) => {
                    // Titik yang sama bisa muncul dengan PlaceId berbeda di Suggest vs SearchText.
                    const key = p.lat.toFixed(3) + ',' + p.lng.toFixed(3);
                    const old = seen.get(key);
                    if (!old) { seen.set(key, { ...p, src: [k] }); return; }
                    // Ambil versi dengan kecocokan tertinggi (mis. "No. 4" menang atas "Blok K No. 4").
                    if (p.lvl > old.lvl) seen.set(key, { ...p, src: old.src });
                    if (!seen.get(key).src.includes(k)) seen.get(key).src.push(k);
                });
            });
            const list = [...seen.values()];
            const cities = new Set(list.map((p) => p.city));
            if (!Q.num) {
                box.className = 'finding ok';
                box.innerHTML = '<p>Query tidak memuat nomor rumah, jadi kecocokan persis tidak dinilai.</p>';
                return;
            }
            if (!list.length) {
                box.className = 'finding';
                box.innerHTML = '<p>Tidak ada hasil yang nama jalan dan nomornya persis sama dengan query.</p>';
                return;
            }
            box.className = cities.size > 1 ? 'finding' : 'finding ok';
            box.innerHTML = `<p>${cities.size > 1
                ? `"${esc(S.q)}" persis cocok dengan ${list.length} titik di ${cities.size} kota/area berbeda. Tanpa kota di query, semuanya sama benar.`
                : `"${esc(S.q)}" persis cocok dengan ${list.length} titik.`}</p>
                <div class="dup">${list.map((p) => `<button type="button" data-lat="${p.lat}" data-lng="${p.lng}" title="${esc(p.label)}">
                    <span class="dot" style="background:${css(LEVEL[p.lvl].col)}"></span>
                    <span><b>${esc(p.city || '?')}</b> · ${esc([p.subdistrict, p.district].filter(Boolean).join(', '))}</span>
                    <span class="num" title="Muncul di skenario ${p.src.join(', ')}">${km(haversine(S.user, p))} · ${p.src.join('·')}</span></button>`).join('')}</div>`;
        }
        document.getElementById('dupBox').addEventListener('click', (e) => {
            const b = e.target.closest('button[data-lat]');
            if (b) map.flyTo({ center: [+b.dataset.lng, +b.dataset.lat], zoom: 15 });
        });

        renderPresets();
        renderList();
        run(['A', 'B', 'C']);
    </script>
</body>

</html>
