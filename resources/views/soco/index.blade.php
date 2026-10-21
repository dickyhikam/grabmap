<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SOCO × GrabMaps — Place Review Demo</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --g: #00B14F;
            --g-d: #00873c;
            --g-soft: #e6f7ee;
            --ink: #0f172a;
            --mut: #64748b;
            --line: #e5e7eb;
            --bg: #f8fafc;
            --red: #dc2626;
            --red-soft: #fee2e2;
            --amber: #b45309;
            --amber-soft: #fef3c7;
            --pink: #e6397f;
            --pink-soft: #fde8f1;
            --phone-w: 372px;
            --panel-w: 400px;
        }

        * { box-sizing: border-box; }
        [hidden] { display: none !important; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--ink);
            overflow: hidden;
            font-size: 13px;
        }

        button, input, textarea { font: inherit; color: inherit; }
        #map { position: absolute; inset: 0; }

        /* ---------- Bilah atas ---------- */
        .top {
            position: absolute;
            top: 12px;
            left: calc(var(--phone-w) + 36px);
            right: calc(var(--panel-w) + 24px);
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border-radius: 14px;
            padding: 8px 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .16);
        }
        .top > * { flex: none; }
        .top img { height: 24px; }
        .top h1 { margin: 0; font-size: 13.5px; font-weight: 800; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .seg { display: inline-grid; grid-auto-flow: column; gap: 3px; padding: 3px; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; }
        .seg button { border: 0; background: none; border-radius: 7px; padding: 6px 10px; font-weight: 700; font-size: 12px; cursor: pointer; color: var(--mut); white-space: nowrap; }
        .seg button.on { background: #fff; color: var(--ink); box-shadow: 0 1px 4px rgba(15, 23, 42, .15); }
        .seg button:disabled { opacity: .45; cursor: not-allowed; }

        .switch { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 12px; cursor: pointer; user-select: none; }
        .switch input { accent-color: var(--g); width: 15px; height: 15px; margin: 0; }

        /* ---------- Ponsel ---------- */
        .phone {
            position: absolute;
            top: 12px;
            left: 12px;
            bottom: 12px;
            width: var(--phone-w);
            z-index: 10;
            background: #111827;
            border-radius: 34px;
            padding: 9px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, .32);
        }
        .screen { position: relative; height: 100%; background: #fff; border-radius: 26px; overflow: hidden; display: flex; flex-direction: column; }
        .app-bar { display: flex; align-items: center; gap: 8px; padding: 14px 14px 10px; border-bottom: 1px solid var(--line); }
        .app-bar .brand { font-weight: 800; font-size: 17px; color: var(--pink); letter-spacing: .02em; }
        .app-bar h2 { margin: 0; font-size: 14.5px; font-weight: 700; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .icon-btn { border: 0; background: var(--bg); width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: grid; place-items: center; flex: none; }
        .body { flex: 1; overflow-y: auto; padding: 12px 14px 16px; }
        .tabbar { position: absolute; left: 0; right: 0; bottom: 0; height: 54px; z-index: 6; display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid var(--line); }
        .tabbar button { border: 0; background: #fff; padding: 9px 0 11px; cursor: pointer; color: var(--mut); font-size: 11px; font-weight: 600; display: grid; gap: 2px; justify-items: center; }
        .tabbar button i { font-size: 17px; }
        .tabbar button.on { color: var(--pink); }

        .field { position: relative; }
        .field i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--mut); }
        .input { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 9px 11px; background: #fff; outline: none; }
        .field .input { padding-left: 32px; }
        .input:focus { border-color: var(--pink); }
        textarea.input { resize: vertical; min-height: 84px; }
        label.lbl { display: block; font-weight: 700; font-size: 11.5px; margin: 12px 0 5px; }

        .chips { display: flex; gap: 6px; flex-wrap: wrap; margin: 10px 0 4px; }
        .chip { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 5px 10px; font-size: 11.5px; font-weight: 600; cursor: pointer; }
        .chip:hover, .chip.on { border-color: var(--pink); color: var(--pink); background: var(--pink-soft); }

        .hint { color: var(--mut); font-size: 11px; line-height: 1.45; margin: 8px 0; }
        .sec { font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em; color: var(--mut); font-weight: 700; margin: 14px 0 6px; }

        .row { display: flex; gap: 10px; padding: 9px 4px; border-bottom: 1px solid var(--line); cursor: pointer; align-items: flex-start; }
        .row:hover { background: var(--bg); }
        .row .n { flex: none; width: 22px; height: 22px; border-radius: 50%; background: var(--pink); color: #fff; font-size: 11px; font-weight: 700; display: grid; place-items: center; margin-top: 1px; }
        .row b { display: block; font-size: 12.5px; }
        .row small { display: block; color: var(--mut); font-size: 11px; line-height: 1.4; margin-top: 1px; }
        .row .d { margin-left: auto; flex: none; color: var(--mut); font-size: 11px; white-space: nowrap; }

        .tag { display: inline-flex; align-items: center; gap: 4px; border-radius: 999px; padding: 2px 8px; font-size: 10.5px; font-weight: 700; background: var(--bg); border: 1px solid var(--line); }
        .tag.ok { background: var(--g-soft); border-color: transparent; color: var(--g-d); }
        .tag.no { background: var(--red-soft); border-color: transparent; color: var(--red); }
        .tag.warn { background: var(--amber-soft); border-color: transparent; color: var(--amber); }
        .tag.pink { background: var(--pink-soft); border-color: transparent; color: var(--pink); }

        .btn { border: 0; border-radius: 10px; padding: 10px 14px; font-weight: 700; cursor: pointer; background: var(--pink); color: #fff; width: 100%; }
        .btn.ghost { background: var(--bg); color: var(--ink); border: 1px solid var(--line); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }

        .kv { display: grid; grid-template-columns: 96px 1fr; gap: 5px 10px; font-size: 12px; margin: 8px 0; }
        .kv dt { color: var(--mut); }
        .kv dd { margin: 0; word-break: break-word; }
        .na { color: var(--red); font-style: italic; }

        .stars { display: inline-flex; gap: 3px; font-size: 22px; color: #d1d5db; cursor: pointer; }
        .stars i.on, .stars-ro i.on { color: #f59e0b; }
        .stars-ro { display: inline-flex; gap: 1px; font-size: 12px; color: #d1d5db; }

        .card { border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; margin-bottom: 10px; }
        .card h4 { margin: 0 0 4px; font-size: 13px; }
        .card p { margin: 4px 0; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }
        .thumbs { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
        .thumbs img { width: 68px; height: 68px; object-fit: cover; border-radius: 8px; }
        .place-link { display: inline-flex; align-items: center; gap: 4px; color: var(--pink); font-weight: 700; cursor: pointer; font-size: 12px; border: 0; background: none; padding: 0; }

        .empty { text-align: center; color: var(--mut); padding: 26px 10px; line-height: 1.5; }
        .empty i { font-size: 26px; display: block; margin-bottom: 6px; }

        .sheet { position: absolute; inset: 0 0 54px 0; background: #fff; z-index: 5; display: flex; flex-direction: column; }

        /* ---------- Panel kanan ---------- */
        .panel {
            position: absolute;
            top: 12px;
            right: 12px;
            bottom: 12px;
            width: var(--panel-w);
            z-index: 10;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .18);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .meter { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--line); border-bottom: 1px solid var(--line); }
        .meter div { background: var(--bg); padding: 8px 12px; }
        .meter b { display: block; font-size: 15px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .meter span { color: var(--mut); font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; }
        .ptabs { display: grid; grid-template-columns: repeat(3, 1fr); border-bottom: 1px solid var(--line); }
        .ptabs button { border: 0; background: none; padding: 10px 4px; font-weight: 700; font-size: 12px; color: var(--mut); cursor: pointer; border-bottom: 2px solid transparent; }
        .ptabs button.on { color: var(--ink); border-bottom-color: var(--g); }
        .pbody { flex: 1; overflow-y: auto; padding: 12px 14px 16px; }

        table.t { width: 100%; border-collapse: collapse; font-size: 12px; }
        table.t th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--mut); padding: 6px 6px; border-bottom: 1px solid var(--line); }
        table.t td { padding: 7px 6px; border-bottom: 1px solid var(--line); vertical-align: top; word-break: break-word; }
        table.t td.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; background: var(--bg); padding: 1px 4px; border-radius: 4px; }

        .call { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
        .call summary { list-style: none; cursor: pointer; padding: 8px 10px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .call summary::-webkit-details-marker { display: none; }
        .call summary b { font-size: 12px; }
        .call summary .ms { margin-left: auto; color: var(--mut); font-size: 11px; }
        .call .up { padding: 0 10px 6px; color: var(--mut); font-size: 11px; word-break: break-all; }
        .call pre { margin: 0; padding: 8px 10px; background: #0f172a; color: #e2e8f0; font-size: 10.5px; line-height: 1.45; max-height: 220px; overflow: auto; white-space: pre-wrap; word-break: break-all; }
        .call h5 { margin: 0; padding: 5px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: var(--mut); background: var(--bg); }

        .note { border-radius: 10px; padding: 9px 11px; font-size: 11.5px; line-height: 1.5; margin: 10px 0; background: var(--amber-soft); color: #7c2d12; }
        .note.g { background: var(--g-soft); color: #064e3b; }

        .me-dot { width: 16px; height: 16px; border-radius: 50%; background: #2563eb; border: 3px solid #fff; box-shadow: 0 0 0 5px rgba(37, 99, 235, .25); cursor: grab; }
        .pin { width: 24px; height: 24px; border-radius: 50%; background: var(--pink); color: #fff; font-size: 11px; font-weight: 700; display: grid; place-items: center; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(15, 23, 42, .35); cursor: pointer; }
        .pin.sel { background: var(--ink); transform: scale(1.25); z-index: 2; }

        .toast { position: absolute; left: 50%; bottom: 22px; transform: translateX(-50%); z-index: 30; background: var(--ink); color: #fff; padding: 9px 14px; border-radius: 10px; font-weight: 600; box-shadow: 0 8px 24px rgba(15, 23, 42, .3); }

        /* Di antara dua panel tetap, bilah atas sempit: judul mengalah supaya tombol tetap satu baris. */
        @media (min-width: 1181px) and (max-width: 1620px) {
            .top h1 { display: none; }
            .top { justify-content: space-between; }
        }

        @media (max-width: 1180px) {
            body { overflow: auto; }
            #map { position: fixed; }
            .top, .phone, .panel { position: relative; inset: auto; width: auto; margin: 12px; }
            .top { flex-wrap: wrap; }
            .top h1 { flex-basis: 100%; order: 9; }
            .phone { height: 720px; max-width: var(--phone-w); margin-inline: auto; }
            .panel { height: 620px; }
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <header class="top">
        <img src="{{ asset('logo.png') }}" alt="GrabMaps">
        <h1 title="SOCO × GrabMaps — place reviews">SOCO × GrabMaps — place reviews</h1>
        <div class="seg" id="provSeg" title="API used by the app">
            <button type="button" data-prov="aws">AWS Location</button>
            <button type="button" data-prov="grab" @disabled(!$hasGrab)>GrabMaps Direct</button>
        </div>
        <label class="switch" id="storeWrap" title="SearchText is sent with IntendedUse=Storage so the results may be stored">
            <input type="checkbox" id="storeChk" checked> Storage mode
        </label>
        <button type="button" class="icon-btn" id="locBtn" title="Use device location"><i class="bi bi-crosshair"></i></button>
    </header>

    <!-- ================= Aplikasi (mock) ================= -->
    <section class="phone">
        <div class="screen">

            <!-- A. Cari tempat -->
            <div class="sheet" id="scrSearch">
                <div class="app-bar"><span class="brand">SOCO</span><h2>Find places</h2></div>
                <div class="body">
                    <div class="field">
                        <i class="bi bi-search"></i>
                        <input class="input" id="q" type="search" placeholder="Gyu Kaku, Sociolla, restaurants near me…" autocomplete="off">
                    </div>
                    <div class="chips" id="catChips"></div>
                    <p class="hint">Suggestions appear as you type. Press Enter or pick a category to run a full search around the blue dot on the map (drag it to move).</p>
                    <div id="results"></div>
                </div>
            </div>

            <!-- Detail tempat -->
            <div class="sheet" id="scrDetail" hidden>
                <div class="app-bar">
                    <button type="button" class="icon-btn" data-back><i class="bi bi-arrow-left"></i></button>
                    <h2 id="dTitle">Place details</h2>
                </div>
                <div class="body" id="detailBody"></div>
            </div>

            <!-- B. Tulis review -->
            <div class="sheet" id="scrReview" hidden>
                <div class="app-bar">
                    <button type="button" class="icon-btn" data-back><i class="bi bi-arrow-left"></i></button>
                    <h2>Write a review</h2>
                </div>
                <div class="body">
                    <div class="card" id="rvPlace"></div>
                    <label class="lbl">Rating</label>
                    <div class="stars" id="rvStars"></div>
                    <label class="lbl" for="rvText">Review</label>
                    <textarea class="input" id="rvText" placeholder="Tell us about your experience here"></textarea>
                    <label class="lbl">Photos (max. 3)</label>
                    <input type="file" id="rvFiles" accept="image/*" multiple>
                    <div class="thumbs" id="rvThumbs"></div>
                    <p class="hint">Reviews are saved in this browser (localStorage) in place of the SOCO database.</p>
                    <button type="button" class="btn" id="rvSubmit">Submit review</button>
                </div>
            </div>

            <!-- Komunitas -->
            <div class="sheet" id="scrFeed" hidden>
                <div class="app-bar"><span class="brand">SOCO</span><h2>Community</h2>
                    <button type="button" class="icon-btn" id="newPost" title="Create post"><i class="bi bi-plus-lg"></i></button>
                </div>
                <div class="body" id="feedBody"></div>
            </div>

            <!-- C. Buat post -->
            <div class="sheet" id="scrPost" hidden>
                <div class="app-bar">
                    <button type="button" class="icon-btn" data-back><i class="bi bi-arrow-left"></i></button>
                    <h2>Create post</h2>
                </div>
                <div class="body">
                    <label class="lbl" for="poTitle">Title</label>
                    <input class="input" id="poTitle" placeholder="Post title">
                    <label class="lbl" for="poText">Description</label>
                    <textarea class="input" id="poText" placeholder="Write something"></textarea>
                    <label class="lbl">Photos (max. 3)</label>
                    <input type="file" id="poFiles" accept="image/*" multiple>
                    <div class="thumbs" id="poThumbs"></div>
                    <label class="lbl">Location</label>
                    <div id="poPlace"></div>
                    <div style="height:12px"></div>
                    <button type="button" class="btn" id="poSubmit">Publish</button>
                </div>
            </div>

            <!-- Pemilih tempat untuk post -->
            <div class="sheet" id="scrPick" hidden>
                <div class="app-bar">
                    <button type="button" class="icon-btn" data-back><i class="bi bi-arrow-left"></i></button>
                    <h2>Add place</h2>
                </div>
                <div class="body">
                    <div class="field">
                        <i class="bi bi-geo-alt"></i>
                        <input class="input" id="pickQ" type="search" placeholder="Search place name" autocomplete="off">
                    </div>
                    <div id="pickResults"></div>
                </div>
            </div>

            <nav class="tabbar" id="tabbar">
                <button type="button" data-tab="scrSearch" class="on"><i class="bi bi-search"></i>Find places</button>
                <button type="button" data-tab="scrFeed"><i class="bi bi-people"></i>Community</button>
            </nav>
        </div>
    </section>

    <!-- ================= Panel data & API ================= -->
    <aside class="panel">
        <div class="meter" title="Every number comes from real API calls made during this demo">
            <div><b id="mReq">0</b><span>API requests</span></div>
            <div><b id="mLast">–</b><span>Last (ms)</span></div>
            <div><b id="mCost">–</b><span>Session cost</span></div>
        </div>
        <div class="ptabs" id="ptabs">
            <button type="button" data-p="data" class="on">Place data</button>
            <button type="button" data-p="calls">API calls</button>
            <button type="button" data-p="cost">Cost &amp; storage</button>
        </div>
        <div class="pbody" id="pData"></div>
        <div class="pbody" id="pCalls" hidden></div>
        <div class="pbody" id="pCost" hidden></div>
    </aside>

    <div class="toast" id="toast" hidden></div>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    <script>
        (function () {
            'use strict';

            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const REGION = '{{ config('services.aws.region') }}';
            const PRICE = {!! json_encode($prices) !!}; // USD per 1.000 request, dari katalog /pricing
            const RATE = {{ $rate }};
            const HAS_GRAB = {{ $hasGrab ? 'true' : 'false' }};

            const $ = (s, el = document) => el.querySelector(s);
            const $$ = (s, el = document) => [...el.querySelectorAll(s)];
            const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const rp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            const usd = (n) => '$' + n.toLocaleString('en-US', { minimumFractionDigits: n < 1 ? 4 : 2, maximumFractionDigits: n < 1 ? 4 : 2 });

            function toast(msg) {
                const t = $('#toast');
                t.textContent = msg;
                t.hidden = false;
                clearTimeout(toast.t);
                toast.t = setTimeout(() => { t.hidden = true; }, 2600);
            }

            /* =========================================
               Penyimpanan — pengganti database SOCO
               ========================================= */
            const DB_KEY = 'soco_demo_v1';
            let db = { places: {}, reviews: [], posts: [] };
            try { db = Object.assign(db, JSON.parse(localStorage.getItem(DB_KEY) || '{}')); } catch (e) { /* penyimpanan diblokir */ }
            function persist() {
                try { localStorage.setItem(DB_KEY, JSON.stringify(db)); } catch (e) { toast('Browser storage is full — photos were not saved'); }
            }
            const pkey = (p) => p.provider + ':' + p.id;

            /* =========================================
               State
               ========================================= */
            const S = {
                provider: 'aws',
                storage: true,
                me: [106.8229, -6.1944], // [lng, lat] — Bundaran HI
                results: [],
                place: null,
                stack: ['scrSearch'],
            };

            /* =========================================
               API (lewat proxy Laravel — key tetap di server)
               ========================================= */
            const calls = [];
            const meter = { n: 0, usd: 0, unpriced: 0 };

            async function api(op, provider, proxy, upstream, { body, query } = {}) {
                const t = performance.now();
                let url = proxy;
                if (query) url += '?' + new URLSearchParams(query);
                const res = await fetch(url, body
                    ? { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) }
                    : { headers: { 'Accept': 'application/json' } });
                const ms = Math.round(performance.now() - t);
                let data = null;
                try { data = await res.json(); } catch (e) { /* bukan JSON */ }

                const bucket = res.headers.get('X-Pricing-Bucket');
                // Suggest punya satu tarif di katalog; operasi Places lain mengikuti tier dari header AWS.
                let per1k = null;
                if (provider === 'aws' && res.ok) per1k = op === 'Suggest' ? PRICE.Suggest : (PRICE[bucket] ?? null);
                meter.n++;
                if (per1k != null) meter.usd += per1k / 1000; else if (res.ok) meter.unpriced++;

                calls.unshift({ op, provider, upstream, method: body ? 'POST' : 'GET', req: body || query || null, res: data, status: res.status, ms, bucket, per1k, at: new Date() });
                if (calls.length > 60) calls.length = 60;
                $('#mReq').textContent = meter.n;
                $('#mLast').textContent = ms;
                $('#mCost').textContent = meter.usd ? usd(meter.usd) : '–';
                renderCalls();
                renderCost();

                if (!res.ok) throw new Error(data?.message || data?.Message || data?.error || data?.devMessage || ('HTTP ' + res.status));
                return { data, bucket };
            }

            const NEAR_M = 5000;
            function haversine(a, b) {
                const r = Math.PI / 180;
                const h = Math.sin((b[1] - a[1]) * r / 2) ** 2 + Math.cos(a[1] * r) * Math.cos(b[1] * r) * Math.sin((b[0] - a[0]) * r / 2) ** 2;
                return 12742000 * Math.asin(Math.sqrt(h));
            }

            const AWS_UP = `https://places.geo.${REGION}.amazonaws.com/v2`;
            const GRAB_UP = 'https://maps.grab.com/api/v1';
            const latlng = () => S.me[1].toFixed(6) + ',' + S.me[0].toFixed(6);

            const P = {
                aws: {
                    async suggest(q) {
                        const { data } = await api('Suggest', 'aws', '/api/soco/aws/suggest', AWS_UP + '/suggest', {
                            body: { QueryText: q, BiasPosition: S.me, MaxResults: 6, Language: 'id', AdditionalFeatures: ['Core'], Filter: { IncludeCountries: ['IDN'] } },
                        });
                        return (data.ResultItems || []).filter((i) => i.Place).map((i) => normAws(i.Place, i.Title, false));
                    },
                    async search(q, near) {
                        // BiasPosition hanya condong, hasil 50 km pun bisa ikut. "Dekat saya" memakai Filter.Circle supaya benar-benar dibatasi radius.
                        const where = near ? { Filter: { Circle: { Center: S.me, Radius: NEAR_M }, IncludeCountries: ['IDN'] } } : { BiasPosition: S.me, Filter: { IncludeCountries: ['IDN'] } };
                        const { data, bucket } = await api('SearchText', 'aws', '/api/soco/aws/search-text', AWS_UP + '/search-text', {
                            body: { QueryText: q, MaxResults: 15, Language: 'id', IntendedUse: S.storage ? 'Storage' : 'SingleUse', ...where },
                        });
                        return (data.ResultItems || []).map((p) => normAws(p, null, bucket === 'Stored'));
                    },
                    async byId(id) {
                        const { data } = await api('GetPlace', 'aws', '/api/soco/aws/place/' + encodeURIComponent(id), AWS_UP + '/place/{PlaceId}', { query: { language: 'id' } });
                        return normAws(data, null, false);
                    },
                },
                grab: {
                    async suggest(q) {
                        const { data } = await api('autocomplete', 'grab', '/api/soco/grab/autocomplete', GRAB_UP + '/autocomplete', {
                            query: { keyword: q, country: 'IDN', location: latlng(), limit: 6 },
                        });
                        return (data.places || []).map(normGrab);
                    },
                    async search(q) {
                        const { data } = await api('search-text', 'grab', '/api/soco/grab/search-text', GRAB_UP + '/search-text', {
                            query: { keyword: q, country: 'IDN', location: latlng(), limit: 15 },
                        });
                        return (data.places || []).slice(0, 15).map(normGrab);
                    },
                    byId: null, // tidak ada endpoint detail per poi_id yang ditemukan
                },
            };

            /* ---------- Normalisasi: undefined = field tidak disediakan API, null/'' = kosong untuk tempat ini ---------- */
            function normAws(p, title, storable) {
                const a = p.Address || {};
                return {
                    provider: 'aws',
                    id: p.PlaceId,
                    name: p.Title || title || '',
                    label: a.Label || '',
                    street: [a.Street, a.AddressNumber].filter(Boolean).join(' ') || null,
                    district: [a.SubDistrict, a.District].filter(Boolean).join(', ') || null,
                    city: undefined, // hanya ada di dalam Label, bukan komponen tersendiri
                    province: a.Region?.Name || a.Locality || null,
                    postcode: a.PostalCode || null,
                    pos: p.Position || null,
                    cat: (p.Categories || []).map((c) => (c.Name && c.Name !== c.Id ? c.Id + ' › ' + c.Name : c.Id)).join(', ') || null,
                    status: undefined,
                    hours: undefined,
                    dist: p.Distance ?? (p.Position ? haversine(S.me, p.Position) : null), // meter; Distance hanya ada bila BiasPosition dikirim
                    storable,
                };
            }

            function normGrab(p) {
                const area = (t) => (p.administrative_areas || []).find((x) => x.type === t)?.name;
                let hours = null;
                try { hours = JSON.parse(p.opening_hours || '{}'); } catch (e) { /* format tak dikenal */ }
                return {
                    provider: 'grab',
                    id: p.poi_id,
                    name: p.name || '',
                    label: p.formatted_address || '',
                    street: [p.street, p.house].filter(Boolean).join(' ') || null,
                    district: [area('Neighborhood'), area('Municipality')].filter(Boolean).join(', ') || null,
                    city: area('SubRegion') || p.city || null,
                    province: area('Region') || null,
                    postcode: p.postcode || null,
                    pos: p.location ? [p.location.longitude, p.location.latitude] : null,
                    cat: p.category || (p.categories || []).map((c) => c.category_name).join(', ') || p.business_type || null,
                    status: p.status, // autocomplete tidak membawa field ini
                    hours: hours && Object.keys(hours).length ? hours : null,
                    dist: p.distance != null ? p.distance * 1000 : null, // API memberi kilometer
                    storable: null, // ketentuan penyimpanan belum dikonfirmasi Grab
                };
            }

            /* ---------- Pemetaan ke kategori aplikasi SOCO ---------- */
            const SOCO_CATS = [
                ['Salon & Spa', /salon|spa|beauty|barber|hair|nail|massage|kecantikan/i],
                ['Clinic & Health', /clinic|klinik|hospital|health|medical|pharmacy|dental|apotek/i],
                ['Cafe', /cafe|coffee|kopi|bakery|dessert|tea/i],
                ['Food & Drink', /restaurant|food|beverage|diner|bar|yakiniku|japanese|indonesian|chinese|korean|western/i],
                ['Shopping', /shop|store|mall|retail|market|cosmetic|fashion|commercial/i],
                ['Hotel & Lodging', /hotel|lodging|resort|hostel/i],
            ];
            const socoCat = (raw) => (raw && SOCO_CATS.find(([, re]) => re.test(raw))?.[0]) || 'Other';

            const CHIPS = [['Restaurants', 'restoran'], ['Cafes', 'kafe'], ['Salons', 'salon'], ['Spas', 'spa'], ['Beauty clinics', 'klinik kecantikan'], ['Cosmetics stores', 'kosmetik']];

            const distTxt = (m) => (m == null ? '' : m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(1) + ' km');

            const DAYS = [['monday', 'Mon'], ['tuesday', 'Tue'], ['wednesday', 'Wed'], ['thursday', 'Thu'], ['friday', 'Fri'], ['saturday', 'Sat'], ['sunday', 'Sun']];
            function hoursHtml(h) {
                if (h === undefined) return '<span class="na">Not available from this API</span>';
                if (!h) return '<span style="color:var(--mut)">Empty for this place</span>';
                return DAYS.map(([k, l]) => `${l} ${(h[k] || []).map((r) => esc(r.join('–'))).join(', ') || 'closed'}`).join('<br>');
            }
            function statusHtml(s, provider) {
                if (s === undefined && provider === 'grab') return '<span style="color:var(--mut)">Not returned by autocomplete — available in search-text results</span>';
                if (s === undefined) return '<span class="na">Not available from this API</span>';
                return `<code>status: ${esc(s)}</code>` + (s === 1 ? ' <span class="tag ok">active</span>' : ' <span class="tag warn">meaning of value needs confirmation from Grab</span>');
            }
            const NA = '<span class="na">Not available from this API</span>';

            /* =========================================
               Peta
               ========================================= */
            const map = new maplibregl.Map({
                container: 'map',
                style: '/api/v2/map-style?style=Standard&color=Light',
                center: S.me,
                zoom: 13.5,
                attributionControl: false,
            });
            map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
            map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-left');
            const wide = () => window.innerWidth > 1180;
            // Padding dipasang sekali di peta; fitBounds menambahkan padding miliknya di atas ini.
            const applyPad = () => map.setPadding(wide() ? { left: 400, right: 430, top: 90, bottom: 20 } : { left: 0, right: 0, top: 0, bottom: 0 });
            applyPad();
            window.addEventListener('resize', applyPad);

            const meEl = document.createElement('div');
            meEl.className = 'me-dot';
            meEl.title = 'User location — drag to move';
            const meMarker = new maplibregl.Marker({ element: meEl, draggable: true }).setLngLat(S.me).addTo(map);
            meMarker.on('dragend', () => { const l = meMarker.getLngLat(); S.me = [l.lng, l.lat]; });
            map.once('load', () => map.jumpTo({ center: S.me }));

            let pins = [];
            function drawPins(list, selId) {
                pins.forEach((m) => m.remove());
                pins = [];
                list.forEach((p, i) => {
                    if (!p.pos) return;
                    const el = document.createElement('div');
                    el.className = 'pin' + (p.id === selId ? ' sel' : '');
                    el.textContent = i + 1;
                    el.title = p.name;
                    el.addEventListener('click', () => openPlace(p, 'list'));
                    pins.push(new maplibregl.Marker({ element: el }).setLngLat(p.pos).addTo(map));
                });
            }
            function fit(list) {
                const pts = list.filter((p) => p.pos).map((p) => p.pos);
                if (!pts.length) return;
                const b = new maplibregl.LngLatBounds(S.me, S.me);
                pts.forEach((p) => b.extend(p));
                map.fitBounds(b, { padding: 50, maxZoom: 15.5, duration: 600 });
            }

            $('#locBtn').addEventListener('click', () => {
                if (!navigator.geolocation) return toast('This browser does not support geolocation');
                navigator.geolocation.getCurrentPosition((g) => {
                    S.me = [g.coords.longitude, g.coords.latitude];
                    meMarker.setLngLat(S.me);
                    map.easeTo({ center: S.me, zoom: 14 });
                }, () => toast('Location permission denied — drag the blue dot manually'));
            });

            /* =========================================
               Navigasi layar
               ========================================= */
            const SCREENS = ['scrSearch', 'scrDetail', 'scrReview', 'scrFeed', 'scrPost', 'scrPick'];
            function show(id, push = true) {
                SCREENS.forEach((s) => { $('#' + s).hidden = s !== id; });
                if (push && S.stack[S.stack.length - 1] !== id) S.stack.push(id);
                $$('#tabbar button').forEach((b) => b.classList.toggle('on', (id === 'scrFeed' || id === 'scrPost' || id === 'scrPick') === (b.dataset.tab === 'scrFeed')));
            }
            function back() {
                S.stack.pop();
                show(S.stack[S.stack.length - 1] || 'scrSearch', false);
            }
            $$('[data-back]').forEach((b) => b.addEventListener('click', back));
            $('#tabbar').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (!b) return;
                S.stack = [b.dataset.tab];
                show(b.dataset.tab, false);
                if (b.dataset.tab === 'scrFeed') renderFeed();
            });

            /* =========================================
               A. Cari tempat
               ========================================= */
            function rowsHtml(list, numbered) {
                return list.map((p, i) => `
                    <div class="row" data-i="${i}">
                        ${numbered ? `<span class="n">${i + 1}</span>` : '<i class="bi bi-geo-alt" style="color:var(--pink);margin-top:2px"></i>'}
                        <div style="min-width:0">
                            <b>${esc(p.name)}</b>
                            <small>${esc(p.label)}</small>
                            <small><span class="tag pink">${esc(socoCat(p.cat))}</span> ${esc(p.cat || '')}</small>
                        </div>
                        <span class="d">${distTxt(p.dist)}</span>
                    </div>`).join('');
            }
            const loading = (t) => `<div class="empty"><i class="bi bi-arrow-repeat"></i>${esc(t)}</div>`;
            const errBox = (e) => `<div class="empty"><i class="bi bi-exclamation-triangle"></i>${esc(e.message)}</div>`;

            let typeSeq = 0;
            function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

            // Tanpa debounce, tiap ketukan jadi satu request berbayar.
            const onType = debounce(async () => {
                const q = $('#q').value.trim();
                if (q.length < 3) return;
                const seq = ++typeSeq;
                try {
                    const list = await P[S.provider].suggest(q);
                    if (seq !== typeSeq) return;
                    S.results = list;
                    $('#results').innerHTML = `<div class="sec">Suggestions (${S.provider === 'aws' ? 'Suggest' : 'autocomplete'})</div>` + (rowsHtml(list, false) || '<div class="empty">No suggestions</div>');
                    $('#results').dataset.src = 'suggest';
                    drawPins([], null);
                } catch (e) {
                    if (seq === typeSeq) $('#results').innerHTML = errBox(e);
                }
            }, 400);

            async function runSearch(q, near = false) {
                if (!q) return;
                const seq = ++typeSeq;
                $('#results').innerHTML = loading('Searching places…');
                try {
                    let list = await P[S.provider].search(q, near);
                    if (seq !== typeSeq) return;
                    // GrabMaps langsung tidak punya filter radius di search-text, jadi disaring di sisi aplikasi.
                    if (near) list = list.filter((p) => p.dist == null || p.dist <= NEAR_M).sort((a, b) => (a.dist ?? 0) - (b.dist ?? 0));
                    S.results = list;
                    $('#results').dataset.src = 'list';
                    $('#results').innerHTML = `<div class="sec">${list.length} result${list.length === 1 ? '' : 's'} for “${esc(q)}”${near ? ' within ' + NEAR_M / 1000 + ' km' : ''}</div>` + (rowsHtml(list, true) || '<div class="empty">No results</div>');
                    drawPins(list, null);
                    fit(list);
                } catch (e) {
                    if (seq === typeSeq) $('#results').innerHTML = errBox(e);
                }
            }

            $('#q').addEventListener('input', onType);
            $('#q').addEventListener('keydown', (ev) => { if (ev.key === 'Enter') runSearch($('#q').value.trim()); });
            $('#catChips').innerHTML = CHIPS.map(([l, q]) => `<button type="button" class="chip" data-q="${esc(q)}">${esc(l)} near me</button>`).join('');
            $('#catChips').addEventListener('click', (ev) => {
                const b = ev.target.closest('.chip');
                if (!b) return;
                $('#q').value = b.dataset.q;
                runSearch(b.dataset.q, true);
            });
            $('#results').addEventListener('click', (ev) => {
                const r = ev.target.closest('.row');
                if (r) openPlace(S.results[+r.dataset.i], $('#results').dataset.src);
            });

            /* =========================================
               Detail tempat
               ========================================= */
            async function openPlace(p, src) {
                show('scrDetail');
                $('#dTitle').textContent = p.name;
                // AWS: saran (Suggest) hanya ringkasan, jadi detail diambil ulang per PlaceId.
                // Hasil SearchText sudah lengkap, dan hanya itu yang bisa bertier Stored.
                if (p.provider === 'aws' && src !== 'list') {
                    $('#detailBody').innerHTML = loading('Fetching details by PlaceId…');
                    try { p = await P.aws.byId(p.id); } catch (e) { $('#detailBody').innerHTML = errBox(e); return; }
                }
                S.place = p;
                renderDetail();
                renderData();
                if (src === 'list') drawPins(S.results, p.id); else drawPins([p], p.id);
                if (p.pos) map.easeTo({ center: p.pos, zoom: Math.max(map.getZoom(), 15) });
            }

            function starsRo(n) { return '<span class="stars-ro">' + [1, 2, 3, 4, 5].map((i) => `<i class="bi bi-star-fill${i <= n ? ' on' : ''}"></i>`).join('') + '</span>'; }

            function renderDetail() {
                const p = S.place;
                if (!p) return;
                const rv = db.reviews.filter((r) => r.place === pkey(p));
                const avg = rv.length ? rv.reduce((s, r) => s + r.rating, 0) / rv.length : 0;
                $('#dTitle').textContent = p.name;
                $('#detailBody').innerHTML = `
                    <h3 style="margin:0 0 4px;font-size:16px">${esc(p.name)}</h3>
                    <div><span class="tag pink">${esc(socoCat(p.cat))}</span>
                        ${rv.length ? starsRo(Math.round(avg)) + ` <b>${avg.toFixed(1)}</b> <span style="color:var(--mut)">(${rv.length})</span>` : '<span style="color:var(--mut)">No reviews yet</span>'}
                    </div>
                    <dl class="kv">
                        <dt>Address</dt><dd>${esc(p.label)}</dd>
                        <dt>City</dt><dd>${p.city === undefined ? '<span class="na">Not a separate address component</span>' : esc(p.city || '—')}</dd>
                        <dt>Province</dt><dd>${esc(p.province || '—')}</dd>
                        <dt>Postcode</dt><dd>${esc(p.postcode || '—')}</dd>
                        <dt>Status</dt><dd>${statusHtml(p.status, p.provider)}</dd>
                        <dt>Opening hours</dt><dd>${hoursHtml(p.hours)}</dd>
                        <dt>Description</dt><dd>${NA}</dd>
                        <dt>Website</dt><dd>${NA}</dd>
                    </dl>
                    <button type="button" class="btn" id="goReview"><i class="bi bi-pencil-square"></i> Write a review</button>
                    <div class="sec">Reviews (${rv.length})</div>
                    ${rv.map(reviewHtml).join('') || '<div class="empty"><i class="bi bi-chat-square-text"></i>Be the first to review this place</div>'}`;
                $('#goReview').addEventListener('click', startReview);
            }

            const reviewHtml = (r) => `
                <div class="card">
                    ${starsRo(r.rating)} <span style="color:var(--mut);font-size:11px">${esc(new Date(r.at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }))}</span>
                    <p>${esc(r.text)}</p>
                    ${r.images?.length ? `<div class="thumbs">${r.images.map((s) => `<img src="${esc(s)}" alt="">`).join('')}</div>` : ''}
                </div>`;

            /* =========================================
               Foto — dikecilkan supaya muat di localStorage
               ========================================= */
            function shrink(file) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => {
                        const k = Math.min(1, 480 / Math.max(img.width, img.height));
                        const c = document.createElement('canvas');
                        c.width = Math.round(img.width * k);
                        c.height = Math.round(img.height * k);
                        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                        URL.revokeObjectURL(img.src);
                        resolve(c.toDataURL('image/jpeg', .72));
                    };
                    img.onerror = reject;
                    img.src = URL.createObjectURL(file);
                });
            }
            function bindPhotos(inputSel, thumbsSel, bag) {
                $(inputSel).addEventListener('change', async (ev) => {
                    bag.length = 0;
                    for (const f of [...ev.target.files].slice(0, 3)) {
                        try { bag.push(await shrink(f)); } catch (e) { /* bukan gambar */ }
                    }
                    $(thumbsSel).innerHTML = bag.map((s) => `<img src="${s}" alt="">`).join('');
                });
            }

            /* =========================================
               B. Tulis review
               ========================================= */
            const rvImages = [];
            let rvRating = 0;
            bindPhotos('#rvFiles', '#rvThumbs', rvImages);

            function paintStars() {
                $('#rvStars').innerHTML = [1, 2, 3, 4, 5].map((i) => `<i class="bi bi-star-fill${i <= rvRating ? ' on' : ''}" data-n="${i}"></i>`).join('');
            }
            $('#rvStars').addEventListener('click', (ev) => {
                const s = ev.target.closest('[data-n]');
                if (s) { rvRating = +s.dataset.n; paintStars(); }
            });

            function startReview() {
                const p = S.place;
                rvRating = 0;
                rvImages.length = 0;
                $('#rvText').value = '';
                $('#rvFiles').value = '';
                $('#rvThumbs').innerHTML = '';
                paintStars();
                $('#rvPlace').innerHTML = `<h4>${esc(p.name)}</h4><span style="color:var(--mut)">${esc(p.label)}</span>`;
                show('scrReview');
            }

            $('#rvSubmit').addEventListener('click', () => {
                const p = S.place;
                const text = $('#rvText').value.trim();
                if (!rvRating) return toast('Please choose a rating first');
                if (!text) return toast('Review text is empty');
                db.places[pkey(p)] = p; // record tempat ikut disimpan — review menempel ke record ini
                db.reviews.unshift({ place: pkey(p), rating: rvRating, text, images: [...rvImages], at: Date.now() });
                persist();
                back();
                renderDetail();
                renderCost();
                toast('Review submitted');
            });

            /* =========================================
               C. Post komunitas + tag lokasi
               ========================================= */
            const poImages = [];
            let poTag = null;
            bindPhotos('#poFiles', '#poThumbs', poImages);

            function paintTag() {
                $('#poPlace').innerHTML = poTag
                    ? `<span class="tag pink"><i class="bi bi-geo-alt-fill"></i> ${esc(poTag.name)}</span> <button type="button" class="place-link" id="poClear" style="color:var(--mut)">remove</button>`
                    : '<button type="button" class="btn ghost" id="poAdd"><i class="bi bi-geo-alt"></i> Add place</button>';
                $('#poAdd')?.addEventListener('click', () => { $('#pickQ').value = ''; $('#pickResults').innerHTML = ''; show('scrPick'); $('#pickQ').focus(); });
                $('#poClear')?.addEventListener('click', () => { poTag = null; paintTag(); });
            }

            $('#newPost').addEventListener('click', () => {
                poTag = null;
                poImages.length = 0;
                $('#poTitle').value = '';
                $('#poText').value = '';
                $('#poFiles').value = '';
                $('#poThumbs').innerHTML = '';
                paintTag();
                show('scrPost');
            });

            let pickList = [];
            let pickSeq = 0;
            $('#pickQ').addEventListener('input', debounce(async () => {
                const q = $('#pickQ').value.trim();
                if (q.length < 3) return;
                const seq = ++pickSeq;
                try {
                    const list = await P[S.provider].suggest(q);
                    if (seq !== pickSeq) return;
                    pickList = list;
                    $('#pickResults').innerHTML = rowsHtml(list, false) || '<div class="empty">No suggestions</div>';
                } catch (e) {
                    if (seq === pickSeq) $('#pickResults').innerHTML = errBox(e);
                }
            }, 400));
            $('#pickResults').addEventListener('click', (ev) => {
                const r = ev.target.closest('.row');
                if (!r) return;
                poTag = pickList[+r.dataset.i];
                back();
                paintTag();
            });

            $('#poSubmit').addEventListener('click', () => {
                const title = $('#poTitle').value.trim();
                if (!title) return toast('Title is empty');
                if (poTag) db.places[pkey(poTag)] = db.places[pkey(poTag)] || poTag;
                db.posts.unshift({
                    title,
                    text: $('#poText').value.trim(),
                    images: [...poImages],
                    place: poTag ? { provider: poTag.provider, id: poTag.id, name: poTag.name } : null, // yang menempel di post: place_id + nama
                    at: Date.now(),
                });
                persist();
                S.stack = ['scrFeed'];
                show('scrFeed', false);
                renderFeed();
                renderCost();
                toast('Post published');
            });

            function renderFeed() {
                $('#feedBody').innerHTML = db.posts.map((po, i) => `
                    <div class="card">
                        <h4>${esc(po.title)}</h4>
                        <span style="color:var(--mut);font-size:11px">${esc(new Date(po.at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' }))}</span>
                        ${po.text ? `<p>${esc(po.text)}</p>` : ''}
                        ${po.images?.length ? `<div class="thumbs">${po.images.map((s) => `<img src="${esc(s)}" alt="">`).join('')}</div>` : ''}
                        ${po.place ? `<div style="margin-top:8px"><button type="button" class="place-link" data-post="${i}"><i class="bi bi-geo-alt-fill"></i> ${esc(po.place.name)}</button></div>` : ''}
                    </div>`).join('') || '<div class="empty"><i class="bi bi-people"></i>No posts yet. Tap + to create a post with a location tag.</div>';
            }
            $('#feedBody').addEventListener('click', async (ev) => {
                const b = ev.target.closest('[data-post]');
                if (!b) return;
                const ref = db.posts[+b.dataset.post].place;
                const saved = db.places[ref.provider + ':' + ref.id];
                // AWS: detail diambil ulang per PlaceId. GrabMaps langsung: tidak ada endpoint per poi_id, jadi pakai record tersimpan.
                if (ref.provider === 'aws') return openPlace({ provider: 'aws', id: ref.id, name: ref.name }, 'id');
                if (saved) return openPlace(saved, 'saved');
                toast('Place record not found in storage');
            });

            /* =========================================
               Panel kanan
               ========================================= */
            const FIELDS = [
                ['Place ID', { aws: 'PlaceId', grab: 'poi_id' }, (p) => esc(p.id)],
                ['Place name', { aws: 'Title', grab: 'name' }, (p) => esc(p.name)],
                ['Address, city, province, postcode', { aws: 'Address.Label / Street / District / Locality / PostalCode', grab: 'formatted_address / street / administrative_areas / postcode' },
                    (p) => `${esc(p.label)}<br><small>City: ${p.city === undefined ? '<span class="na">only inside Label</span>' : esc(p.city || '—')} · Province: ${esc(p.province || '—')} · Postcode: ${esc(p.postcode || '—')}</small>`],
                ['Latitude, longitude', { aws: 'Position [lng, lat]', grab: 'location.latitude / longitude' }, (p) => (p.pos ? esc(p.pos[1].toFixed(6) + ', ' + p.pos[0].toFixed(6)) : '—')],
                ['Type / category', { aws: 'Categories[]', grab: 'category / business_type' }, (p) => `${esc(p.cat || '—')}<br><small>SOCO category: <b>${esc(socoCat(p.cat))}</b></small>`],
                ['Status (open / permanently closed)', { aws: null, grab: 'status' }, (p) => statusHtml(p.status, p.provider)],
                ['Description', { aws: null, grab: null }, () => NA],
                ['Opening hours', { aws: null, grab: 'opening_hours' }, (p) => hoursHtml(p.hours)],
                ['Website / social media', { aws: null, grab: null }, () => NA],
            ];

            function renderData() {
                const p = S.place;
                const prov = p ? p.provider : S.provider;
                const have = FIELDS.filter(([, src]) => src[prov]).length;
                $('#pData').innerHTML = `
                    <div class="sec" style="margin-top:0">9 data fields requested by SOCO — ${prov === 'aws' ? 'AWS Location' : 'GrabMaps Direct'} API</div>
                    <div><span class="tag ok">${have} available</span> <span class="tag no">${FIELDS.length - have} not available</span></div>
                    <table class="t" style="margin-top:8px">
                        <thead><tr><th>#</th><th>Data</th><th>${p ? 'Value from API' : 'API field'}</th></tr></thead>
                        <tbody>${FIELDS.map(([label, src, val], i) => `
                            <tr>
                                <td>${i + 1}</td>
                                <td><b>${esc(label)}</b><br>${src[prov] ? `<code>${esc(src[prov])}</code>` : '<span class="tag no">none</span>'}</td>
                                <td>${p ? (src[prov] ? val(p) : NA) : (src[prov] ? '<span style="color:var(--mut)">select a place</span>' : NA)}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                    ${prov === 'aws'
                        ? '<div class="note">Region <code>' + esc(REGION) + '</code> rejects <code>AdditionalFeatures: Contact</code>, so opening hours and contact details cannot be requested. Status and description are not part of the response schema.</div>'
                        : '<div class="note">Description and website do not appear in the response. <code>status</code> was 1 in every test result — the meaning of other values needs confirmation from Grab. Suggestions (autocomplete) do not include <code>status</code>; full search results do.</div>'}`;
            }

            function renderCalls() {
                $('#pCalls').innerHTML = calls.map((c) => `
                    <details class="call">
                        <summary>
                            <b>${esc(c.op)}</b>
                            <span class="tag ${c.status < 400 ? 'ok' : 'no'}">${c.status}</span>
                            ${c.bucket ? `<span class="tag ${c.bucket === 'Stored' ? 'warn' : ''}">${esc(c.bucket)} tier</span>` : ''}
                            ${c.per1k != null ? `<span class="tag">${usd(c.per1k / 1000)}</span>` : ''}
                            <span class="ms">${c.ms} ms</span>
                        </summary>
                        <div class="up">${c.method} ${esc(c.upstream)}</div>
                        <h5>Request</h5><pre>${esc(JSON.stringify(c.req, null, 2))}</pre>
                        <h5>Response</h5><pre>${esc(JSON.stringify(c.res, null, 2))}</pre>
                    </details>`).join('') || '<div class="empty"><i class="bi bi-broadcast"></i>No calls yet. Try searching for a place in the app.</div>';
            }

            function renderCost() {
                const byOp = {};
                calls.filter((c) => c.status < 400).forEach((c) => {
                    const k = (c.provider === 'aws' ? 'AWS · ' : 'GrabMaps · ') + c.op + (c.bucket && c.op !== 'Suggest' ? ' (' + c.bucket + ')' : '');
                    byOp[k] = byOp[k] || { n: 0, usd: 0, priced: c.per1k != null };
                    byOp[k].n++;
                    byOp[k].usd += (c.per1k || 0) / 1000;
                });
                const rows = Object.entries(byOp).map(([k, v]) => `<tr><td>${esc(k)}</td><td class="num">${v.n}</td><td class="num">${v.priced ? usd(v.usd) : '—'}</td></tr>`).join('');
                const proj = [['Suggest / autocomplete', PRICE.Suggest], ['Core (single use)', PRICE.Core], ['Stored (may be stored)', PRICE.Stored]]
                    .filter(([, p]) => p != null)
                    .map(([l, p]) => `<tr><td>${esc(l)}<br><small>${usd(p)} / 1,000</small></td><td class="num">${usd(p * 50)}${RATE ? '<br><small>' + rp(p * 50 * RATE) + '</small>' : ''}</td><td class="num">${usd(p * 100)}${RATE ? '<br><small>' + rp(p * 100 * RATE) + '</small>' : ''}</td></tr>`).join('');
                const saved = Object.values(db.places);
                const unsafe = saved.filter((p) => p.provider === 'aws' && !p.storable).length;

                $('#pCost').innerHTML = `
                    <div class="sec" style="margin-top:0">Calls this session</div>
                    <table class="t"><thead><tr><th>Operation</th><th style="text-align:right">Count</th><th style="text-align:right">Cost</th></tr></thead>
                        <tbody>${rows || '<tr><td colspan="3" style="color:var(--mut)">None yet</td></tr>'}</tbody></table>
                    ${meter.unpriced ? '<p class="hint">GrabMaps Direct calls are counted only — their price is not in the catalog yet and must come from a Grab quote.</p>' : ''}

                    <div class="sec">Monthly AWS projection (before VAT &amp; service charge)</div>
                    <table class="t"><thead><tr><th>If every call is</th><th style="text-align:right">50,000</th><th style="text-align:right">100,000</th></tr></thead>
                        <tbody>${proj}</tbody></table>
                    ${RATE ? `<p class="hint">Exchange rate ${rp(RATE)} per USD. Prices from the <a href="/pricing" target="_blank" rel="noopener">/pricing</a> catalog.</p>` : ''}

                    <div class="sec">Records stored in the SOCO “database”</div>
                    <div><span class="tag">${saved.length} place${saved.length === 1 ? '' : 's'}</span> <span class="tag">${db.reviews.length} review${db.reviews.length === 1 ? '' : 's'}</span> <span class="tag">${db.posts.length} post${db.posts.length === 1 ? '' : 's'}</span>
                        ${unsafe ? `<span class="tag warn">${unsafe} from single-use results</span>` : ''}</div>
                    <div class="note">
                        <b>AWS:</b> only SearchText with <code>IntendedUse: "Storage"</code> (Stored tier) returns results that may be stored permanently. Suggest and GetPlace reject <code>Storage</code> in this region, so details fetched through them are single use.
                        Reviews and location tags attach to stored records, so the production flow should fetch records through SearchText in storage mode.
                    </div>
                    <div class="note">
                        <b>GrabMaps Direct:</b> storage terms and pricing are not available yet — an official answer from Grab is needed. No details endpoint by <code>poi_id</code> was found either, so location tags in posts open from the stored record.
                    </div>
                    <div class="note g">Reviews, ratings, photos and internal categories are SOCO's own data.</div>
                    <button type="button" class="btn ghost" id="wipe"><i class="bi bi-trash"></i> Clear demo data</button>`;
                $('#wipe').addEventListener('click', () => {
                    if (!confirm('Delete all demo reviews, posts and place records in this browser?')) return;
                    db = { places: {}, reviews: [], posts: [] };
                    persist();
                    renderCost();
                    renderFeed();
                    if (S.place) renderDetail();
                });
            }

            $('#ptabs').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (!b) return;
                $$('#ptabs button').forEach((x) => x.classList.toggle('on', x === b));
                $('#pData').hidden = b.dataset.p !== 'data';
                $('#pCalls').hidden = b.dataset.p !== 'calls';
                $('#pCost').hidden = b.dataset.p !== 'cost';
            });

            /* =========================================
               Jalur API & mode simpan
               ========================================= */
            function setProvider(p) {
                S.provider = p === 'grab' && HAS_GRAB ? 'grab' : 'aws';
                $$('#provSeg button').forEach((b) => b.classList.toggle('on', b.dataset.prov === S.provider));
                $('#storeWrap').hidden = S.provider !== 'aws';
                S.results = [];
                S.place = null;
                typeSeq++;
                $('#results').innerHTML = '';
                drawPins([], null);
                S.stack = ['scrSearch'];
                show('scrSearch', false);
                renderData();
                const url = new URL(location.href);
                url.searchParams.set('jalur', S.provider);
                history.replaceState(null, '', url);
            }
            $('#provSeg').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (b && !b.disabled && b.dataset.prov !== S.provider) setProvider(b.dataset.prov);
            });
            $('#storeChk').addEventListener('change', (ev) => {
                S.storage = ev.target.checked;
                toast(S.storage ? 'SearchText uses IntendedUse=Storage (Stored tier)' : 'SearchText uses SingleUse (Core tier, may not be stored)');
            });

            setProvider(new URLSearchParams(location.search).get('jalur') || 'aws');
            renderCalls();
            renderCost();
        })();
    </script>
</body>

</html>
