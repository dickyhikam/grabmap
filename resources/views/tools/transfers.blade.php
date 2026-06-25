<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>transfers.txt Generator — GrabMaps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @verbatim
    <style>
        :root {
            --green: #00B14F;
            --green-d: #009543;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e8ebee;
            --bg: #f4f6f8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--ink);
            font-family: 'Inter', sans-serif;
            background-image: radial-gradient(900px 400px at 100% -5%, rgba(0,177,79,.08), transparent 60%),
                              radial-gradient(700px 350px at -5% 110%, rgba(0,177,79,.06), transparent 55%);
            min-height: 100vh;
        }

        /* ---- topbar ---- */
        .topbar {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 22px; background: rgba(255,255,255,.8);
            backdrop-filter: blur(10px); border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 20;
            animation: fadeDown .5s ease both;
        }
        .topbar .back {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            display: grid; place-items: center; color: var(--muted);
            background: #fff; border: 1px solid var(--line); text-decoration: none;
            transition: .15s;
        }
        .topbar .back:hover { color: var(--green); border-color: var(--green); transform: translateX(-2px); }
        .topbar .dot {
            width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--green), var(--green-d));
            box-shadow: 0 6px 14px rgba(0,177,79,.35);
            display: grid; place-items: center; color: #fff; font-size: 1.1rem;
        }
        .topbar h1 { margin: 0; font-size: 1.02rem; font-weight: 800; letter-spacing: -.02em; }
        .topbar small { color: var(--muted); font-size: .74rem; }
        .badge-v2 {
            background: linear-gradient(135deg, var(--green), var(--green-d)); color: #fff;
            font-size: .58rem; padding: 2px 7px; border-radius: 6px; font-weight: 800;
            vertical-align: middle; letter-spacing: .04em;
        }

        /* ---- layout ---- */
        .wrap { max-width: 940px; margin: 0 auto; padding: 26px 18px 60px; }

        /* ---- stepper ---- */
        .stepper { display: flex; align-items: center; gap: 6px; margin: 6px 0 22px; }
        .step {
            display: flex; align-items: center; gap: 8px; font-size: .8rem; font-weight: 600;
            color: var(--muted); transition: .25s;
        }
        .step .num {
            width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center;
            background: #fff; border: 1.5px solid var(--line); font-size: .78rem; transition: .3s;
        }
        .step.active { color: var(--ink); }
        .step.active .num { background: var(--green); border-color: var(--green); color: #fff; transform: scale(1.08); box-shadow: 0 4px 12px rgba(0,177,79,.35); }
        .step.done .num { background: #e7f7ee; border-color: var(--green); color: var(--green); }
        .step.done .num::before { content: "\2713"; }
        .step.done .num span { display: none; }
        .stepper .bar { flex: 1; height: 2px; background: var(--line); border-radius: 2px; position: relative; overflow: hidden; }
        .stepper .bar::after { content: ""; position: absolute; inset: 0; width: 0; background: var(--green); transition: width .4s ease; }
        .stepper .bar.fill::after { width: 100%; }

        /* ---- card / panels ---- */
        .card {
            background: rgba(255,255,255,.96); backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.7); border-radius: 20px;
            box-shadow: 0 18px 50px rgba(16,24,40,.08); overflow: hidden;
        }
        .panel { display: none; padding: 26px; }
        .panel.active { display: block; animation: fadeUp .45s ease both; }
        .panel h2 { font-size: 1.05rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.01em; }
        .panel .sub { color: var(--muted); font-size: .82rem; margin-bottom: 20px; }

        /* ---- dropzone ---- */
        .dropzone {
            border: 2px dashed #cfd6dd; border-radius: 16px; padding: 30px; text-align: center;
            cursor: pointer; transition: .2s; background: #fcfdfe; position: relative;
        }
        .dropzone:hover { border-color: var(--green); background: #f6fdf9; transform: translateY(-1px); }
        .dropzone.drag { border-color: var(--green); background: #eafaf1; animation: pulse 1.1s ease infinite; }
        .dropzone.has-file { border-style: solid; border-color: var(--green); background: #f3fbf6; }
        .dropzone i.big { font-size: 2.1rem; color: var(--green); }
        .dropzone .dz-title { font-weight: 700; font-size: .92rem; margin-top: 8px; }
        .dropzone .dz-sub { color: var(--muted); font-size: .76rem; margin-top: 2px; }
        .dropzone .fname { display: none; align-items: center; justify-content: center; gap: 8px; font-weight: 700; color: var(--green); }
        .dropzone.has-file .fname { display: flex; }
        .dropzone.has-file .dz-default { display: none; }

        /* ---- settings grid ---- */
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 22px; }
        .field > label:not(.switch) { font-size: .76rem; font-weight: 700; display: block; margin-bottom: 6px; }
        .field label .hint { font-weight: 500; color: var(--muted); }
        .field .ctl {
            width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px;
            font-size: .85rem; font-family: inherit; background: #fff; transition: .15s; color: var(--ink);
        }
        .field .ctl:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px rgba(0,177,79,.12); }
        textarea.ctl { resize: vertical; min-height: 62px; line-height: 1.45; }
        .ctl.mono { font-family: 'SFMono-Regular', ui-monospace, Menlo, monospace; font-size: .72rem; color: #475569; }
        #geotoolsAuth { overflow: hidden; transition: opacity .2s; }
        #geotoolsAuth.hide { display: none; }
        .range-row { display: flex; align-items: center; gap: 12px; }
        .range-row input[type=range] { flex: 1; accent-color: var(--green); }
        .range-row .num { width: 88px; }
        .pill-val { font-variant-numeric: tabular-nums; font-weight: 700; color: var(--green); }

        /* ---- switch ---- */
        .switch { display: flex; align-items: center; gap: 12px; padding: 11px 12px; border: 1px solid var(--line); border-radius: 10px; background: #fff; }
        .switch input { display: none; }
        .switch .track { width: 44px; height: 25px; border-radius: 20px; background: #d3d9df; position: relative; transition: .25s; flex-shrink: 0; }
        .switch .track::after { content: ""; position: absolute; top: 3px; left: 3px; width: 19px; height: 19px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.25); transition: .25s; }
        .switch input:checked + .track { background: var(--green); }
        .switch input:checked + .track::after { transform: translateX(19px); }
        .switch .txt small { display: block; color: var(--muted); font-size: .72rem; font-weight: 500; }
        .switch .txt b { font-size: .82rem; }
        .switch .txt b i { color: var(--muted); margin-right: 4px; transition: .25s; }

        /* ---- cool form: label icons + focus motion + stagger ---- */
        .field > label i { color: var(--green); margin-right: 5px; display: inline-block; transition: transform .25s cubic-bezier(.34,1.5,.5,1); }
        .field:focus-within > label { color: var(--green); }
        .field:focus-within > label i { transform: scale(1.22) rotate(-6deg); }
        .field .desc { display: flex; gap: 6px; font-size: .72rem; line-height: 1.5; color: var(--muted); margin-top: 8px; }
        .field .desc i { color: var(--green); font-size: .8rem; margin-top: 1px; flex-shrink: 0; opacity: .85; }
        .field .desc b { color: #4b5563; font-weight: 700; }
        .panel.active .anim { animation: fieldIn .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--d, 0) * 65ms); }
        @keyframes fieldIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

        /* ---- segmented control (travel mode) ---- */
        .segmented { position: relative; display: grid; grid-template-columns: repeat(3, 1fr); background: #eef1f4; border: 1px solid var(--line); border-radius: 12px; padding: 4px; }
        .segmented .seg { position: relative; z-index: 1; border: none; background: transparent; padding: 10px 6px; border-radius: 9px; font: inherit; font-size: .8rem; font-weight: 600; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: color .25s; }
        .segmented .seg i { font-size: .95rem; }
        .segmented .seg.active { color: #fff; }
        .segmented .seg:not(.active):hover { color: var(--ink); }
        .segmented .seg:active { transform: scale(.95); }
        .seg-ind { position: absolute; z-index: 0; top: 4px; bottom: 4px; left: 4px; border-radius: 9px; background: linear-gradient(135deg, var(--green), var(--green-d)); box-shadow: 0 5px 13px rgba(0,177,79,.42); transition: left .32s cubic-bezier(.34,1.45,.5,1), width .32s cubic-bezier(.34,1.45,.5,1); }

        /* ---- fancy range slider ---- */
        .range-wrap { position: relative; display: flex; align-items: center; gap: 12px; padding-top: 4px; }
        .rng { -webkit-appearance: none; appearance: none; flex: 1; height: 7px; border-radius: 7px; outline: none; cursor: pointer;
               background: linear-gradient(to right, var(--green) 0%, var(--green) var(--pct,28%), #e3e8ec var(--pct,28%), #e3e8ec 100%); }
        .rng::-webkit-slider-thumb { -webkit-appearance: none; width: 21px; height: 21px; border-radius: 50%; background: #fff; border: 3px solid var(--green); box-shadow: 0 3px 8px rgba(0,177,79,.45); cursor: pointer; transition: transform .15s; }
        .rng:active::-webkit-slider-thumb { transform: scale(1.32); }
        .rng::-moz-range-thumb { width: 21px; height: 21px; border: 3px solid var(--green); border-radius: 50%; background: #fff; box-shadow: 0 3px 8px rgba(0,177,79,.45); cursor: pointer; transition: transform .15s; }
        .rng:active::-moz-range-thumb { transform: scale(1.32); }
        .range-wrap .num { width: 84px; flex: 0 0 auto; }
        .range-bubble { position: absolute; top: -28px; transform: translateX(-50%) scale(.6); background: var(--ink); color: #fff; font-size: .72rem; font-weight: 800; padding: 3px 9px; border-radius: 8px; opacity: 0; transition: opacity .18s, transform .2s cubic-bezier(.34,1.5,.5,1); pointer-events: none; white-space: nowrap; }
        .range-bubble span { opacity: .65; font-weight: 600; margin-left: 1px; }
        .range-bubble::after { content: ''; position: absolute; bottom: -4px; left: 50%; transform: translateX(-50%); border: 5px solid transparent; border-bottom: 0; border-top-color: var(--ink); }
        .range-wrap:hover .range-bubble, .range-wrap.dragging .range-bubble { opacity: 1; transform: translateX(-50%) scale(1); }

        /* ---- toggle: danger glow when auto-block ON ---- */
        .switch.danger { transition: border-color .25s, background .25s, box-shadow .25s; }
        .switch.danger input:checked + .track { background: #dc2626; }
        .switch.danger:has(input:checked) { border-color: #f3b4b4; background: #fff6f6; box-shadow: 0 0 0 3px rgba(220,38,38,.09); }
        .switch.danger:has(input:checked) .txt b i { color: #dc2626; transform: scale(1.12); }

        /* ---- stepper ( − value + ) ---- */
        .num-stepper { display: flex; align-items: center; gap: 8px; border: 1px solid var(--line); border-radius: 12px; padding: 6px; background: #fff; transition: border-color .18s, box-shadow .18s; }
        .num-stepper:focus-within { border-color: var(--green); box-shadow: 0 0 0 3px rgba(0,177,79,.12); }
        .step-btn { width: 40px; height: 40px; flex: 0 0 auto; border: none; border-radius: 10px; background: #f1f4f6; color: var(--ink); font-size: 1rem; cursor: pointer; display: grid; place-items: center; transition: background .15s, transform .12s, box-shadow .15s; -webkit-tap-highlight-color: transparent; }
        .step-btn:hover { background: var(--green); color: #fff; transform: translateY(-1px); box-shadow: 0 5px 12px rgba(0,177,79,.32); }
        .step-btn:active { transform: scale(.86); }
        .step-val { flex: 1; min-width: 0; width: 100%; border: none; outline: none; text-align: center; font-family: inherit; font-size: 1.15rem; font-weight: 800; color: var(--ink); background: transparent; appearance: textfield; -moz-appearance: textfield; }
        .step-val::-webkit-outer-spin-button, .step-val::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .step-val.bump { animation: bump .32s ease; }
        @keyframes bump { 0% { transform: scale(1); } 35% { transform: scale(1.2); color: var(--green); } 100% { transform: scale(1); color: var(--ink); } }

        /* ---- advanced reveal stagger ---- */
        .adv[open] .grid > .field { animation: fieldIn .42s cubic-bezier(.22,1,.36,1) both; }
        .adv[open] .grid > .field:nth-child(2) { animation-delay: .07s; }
        .adv[open] .grid > .field:nth-child(3) { animation-delay: .14s; }

        /* ---- advanced ---- */
        .adv { margin-top: 16px; border-top: 1px dashed var(--line); }
        .adv > summary { cursor: pointer; padding: 12px 2px; font-size: .8rem; font-weight: 700; color: var(--muted); list-style: none; display: flex; align-items: center; gap: 6px; }
        .adv > summary::-webkit-details-marker { display: none; }
        .adv > summary i { transition: .2s; }
        .adv[open] > summary i { transform: rotate(90deg); }

        /* ---- buttons ---- */
        .actions { display: flex; gap: 10px; margin-top: 24px; }
        .btn {
            border: none; border-radius: 11px; padding: 12px 18px; font-size: .86rem; font-weight: 700;
            cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            font-family: inherit; transition: .15s; position: relative;
        }
        .btn-primary { background: var(--green); color: #fff; box-shadow: 0 8px 18px rgba(0,177,79,.28); }
        .btn-primary:hover { background: var(--green-d); transform: translateY(-1px); box-shadow: 0 10px 22px rgba(0,177,79,.34); }
        .btn-primary:disabled { background: #b9c2cb; box-shadow: none; cursor: not-allowed; transform: none; }
        .btn-ghost { background: #fff; color: var(--ink); border: 1px solid var(--line); }
        .btn-ghost:hover { border-color: #c9d1d9; background: #f8fafb; }
        .btn .spin { width: 15px; height: 15px; border: 2px solid rgba(255,255,255,.5); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; display: none; }
        .btn.loading .spin { display: inline-block; }
        .btn.loading .lbl { opacity: .85; }
        .ml-auto { margin-left: auto; }

        /* ---- preview / result ---- */
        .summary { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .stat { background: #f8fafb; border: 1px solid var(--line); border-radius: 12px; padding: 12px 16px; min-width: 110px; animation: pop .4s ease both; }
        .stat .v { font-size: 1.5rem; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .stat .l { font-size: .7rem; color: var(--muted); font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: .04em; }
        .stat.green .v { color: var(--green); }
        .stat.red .v { color: #dc2626; }
        .stat.amber .v { color: #d97706; }

        .tbl { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .tbl th { text-align: left; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); padding: 8px 10px; border-bottom: 1px solid var(--line); }
        .tbl td { padding: 9px 10px; border-bottom: 1px solid #f1f3f5; }
        .tbl tbody tr { animation: rowin .4s ease both; }
        .tbl .stn { font-weight: 600; }
        .tbl .arrow { color: var(--muted); }
        .tbl-wrap { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; margin-bottom: 8px; }
        .tbl-wrap .cap { padding: 10px 14px; font-weight: 700; font-size: .8rem; background: #fafbfc; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 8px; }

        .chip { font-size: .64rem; font-weight: 800; padding: 2px 7px; border-radius: 20px; letter-spacing: .03em; }
        .chip.mode { background: #eef2f6; color: #475569; }
        .chip.cross { background: #e7f7ee; color: var(--green-d); }
        .chip.t2 { background: #e7f7ee; color: var(--green-d); }
        .chip.t3 { background: #fdeaea; color: #dc2626; }
        .chip.t0, .chip.t1 { background: #eef2ff; color: #4f46e5; }
        .chip.hold { background: #fef3e2; color: #d97706; }

        .codebox { position: relative; margin-top: 6px; }
        .codebox pre {
            background: #0f172a; color: #e2e8f0; border-radius: 14px; padding: 16px 16px; margin: 0;
            font-size: .76rem; line-height: 1.55; max-height: 280px; overflow: auto;
            font-family: 'SFMono-Regular', ui-monospace, Menlo, monospace;
        }
        .codebox .copy { position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,.12); color: #fff; border: none; border-radius: 8px; padding: 5px 10px; font-size: .72rem; cursor: pointer; transition: .15s; }
        .codebox .copy:hover { background: rgba(255,255,255,.22); }

        .note { font-size: .78rem; color: var(--muted); background: #f8fafb; border: 1px solid var(--line); border-left: 3px solid var(--green); border-radius: 8px; padding: 10px 12px; margin: 14px 0; }
        .ovsel { border: 1px solid var(--line); border-radius: 8px; padding: 5px 8px; font-size: .76rem; font-family: inherit; background: #fff; }

        .foot { text-align: center; color: var(--muted); font-size: .74rem; margin-top: 20px; }

        /* ---- progress bar ---- */
        .prog { height: 3px; background: var(--line); border-radius: 3px; overflow: hidden; margin-top: 16px; display: none; }
        .prog.show { display: block; }
        .prog::after { content: ""; display: block; height: 100%; width: 40%; background: var(--green); border-radius: 3px; animation: slide 1.1s ease infinite; }

        /* ---- toast ---- */
        .toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(20px); background: #1f2937; color: #fff; padding: 12px 18px; border-radius: 12px; font-size: .82rem; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,.25); opacity: 0; pointer-events: none; transition: .3s; z-index: 50; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-14px); } to { opacity: 1; transform: none; } }
        @keyframes pop { from { opacity: 0; transform: scale(.94); } to { opacity: 1; transform: none; } }
        @keyframes rowin { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slide { 0% { transform: translateX(-120%); } 100% { transform: translateX(320%); } }
        @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(0,177,79,.25); } 50% { box-shadow: 0 0 0 8px rgba(0,177,79,0); } }

        @media (max-width: 640px) {
            .grid { grid-template-columns: 1fr; }
            .step .lbl-t { display: none; }
            .topbar small { display: none; }
            .panel { padding: 18px; }
        }
    </style>
    @endverbatim
</head>

<body>
    <header class="topbar">
        <a href="{{ route('pageHome') }}" class="back" title="Back"><i class="bi bi-arrow-left"></i></a>
        <div class="dot"><i class="bi bi-signpost-split"></i></div>
        <div>
            <h1>transfers.txt Generator <span class="badge-v2">GTFS</span></h1>
            <small>Build the multimodal transfer file from the Working Sheet — walking time via AWS Routes</small>
        </div>
    </header>

    <main class="wrap">
        <div class="stepper">
            <div class="step active" data-step="1"><span class="num"><span>1</span></span><span class="lbl-t">Upload &amp; Settings</span></div>
            <div class="bar"></div>
            <div class="step" data-step="2"><span class="num"><span>2</span></span><span class="lbl-t">Candidates</span></div>
            <div class="bar"></div>
            <div class="step" data-step="3"><span class="num"><span>3</span></span><span class="lbl-t">Result</span></div>
        </div>

        <section class="card">
            <!-- ============ STEP 1 ============ -->
            <div class="panel active" id="p1">
                <h2>Upload data &amp; set parameters</h2>
                <p class="sub">Upload the Working Sheet (.xlsx). stop_id automatically uses the <b>POI IT</b> column.</p>

                <div class="dropzone anim" style="--d:0" id="dropzone">
                    <input type="file" id="excel" accept=".xlsx,.xls" hidden>
                    <div class="dz-default">
                        <i class="bi bi-cloud-arrow-up big"></i>
                        <div class="dz-title">Drag a file here, or click to choose</div>
                        <div class="dz-sub">Working Sheet — [Paratransit] JKT_Public Transport.xlsx</div>
                    </div>
                    <div class="fname"><i class="bi bi-file-earmark-spreadsheet"></i><span id="fileName"></span></div>
                </div>

                <div class="field anim" style="--d:1; margin-top:18px">
                    <label><i class="bi bi-hdd-network"></i> Routing source <span class="hint">— for walking time</span></label>
                    <select class="ctl" id="provider" onchange="toggleProvider()">
                        <option value="geotools" selected>geo-tools (Grab — same as the VN team)</option>
                        <option value="aws">AWS Location (no login needed)</option>
                    </select>
                    <div id="geotoolsAuth" style="margin-top:10px">
                        <textarea class="ctl mono" id="geotools_curl" rows="3" placeholder="Paste the route-engine request — in Geotools: DevTools → Network → route-engine → right-click → Copy as cURL"></textarea>
                        <p class="desc"><i class="bi bi-shield-lock"></i> Your session auth is used only to call route-engine from your machine — not stored. The token expires, so re-copy if calls start failing. Or switch to AWS (no login).</p>
                    </div>
                </div>

                <div class="grid">
                    <div class="field anim" style="--d:1">
                        <label><i class="bi bi-signpost-2"></i> Travel mode</label>
                        <div class="segmented" id="travelSeg">
                            <span class="seg-ind"></span>
                            <button type="button" class="seg active" data-val="Pedestrian"><i class="bi bi-person-walking"></i> Walk</button>
                            <button type="button" class="seg" data-val="Car"><i class="bi bi-car-front-fill"></i> Car</button>
                            <button type="button" class="seg" data-val="Scooter"><i class="bi bi-scooter"></i> Scooter</button>
                        </div>
                        <input type="hidden" id="travel_mode" value="Pedestrian">
                        <p class="desc"><i class="bi bi-info-circle"></i> How the transfer time is measured. <b>Pedestrian</b> (walking) is what real transfers use — Car/Scooter are just for experiments.</p>
                    </div>
                    <div class="field anim" style="--d:2">
                        <label><i class="bi bi-diagram-3"></i> Default transfer_type <span class="hint">— for measured pairs</span></label>
                        <select class="ctl" id="default_type">
                            <option value="2" selected>2 — use min_transfer_time (recommended)</option>
                            <option value="0">0 — recommended (default 90s)</option>
                            <option value="1">1 — timed transfer (default 90s)</option>
                        </select>
                        <p class="desc"><i class="bi bi-info-circle"></i> GTFS type written for each measured pair. <b>2</b> tells the engine to use the real walking time we measured.</p>
                    </div>
                    <div class="field anim" style="--d:3">
                        <label><i class="bi bi-rulers"></i> Max candidate distance <span class="hint">— straight line</span></label>
                        <div class="range-wrap">
                            <input type="range" class="rng" id="max_transfer_m_r" min="100" max="1500" step="50" value="500">
                            <div class="range-bubble" id="rangeBubble">500<span>m</span></div>
                            <input type="number" class="ctl num" id="max_transfer_m" min="100" max="1500" step="50" value="500">
                        </div>
                        <p class="desc"><i class="bi bi-info-circle"></i> Two different stops closer than this (straight line) become a transfer candidate. Higher = more pairs to check.</p>
                    </div>
                    <div class="field anim" style="--d:4">
                        <label><i class="bi bi-shield-slash"></i> Auto type 3</label>
                        <label class="switch danger">
                            <input type="checkbox" id="auto_type3">
                            <span class="track"></span>
                            <span class="txt"><b><i class="bi bi-shield-slash-fill"></i> Auto-block</b><small>odd pairs become type 3 directly</small></span>
                        </label>
                        <p class="desc"><i class="bi bi-info-circle"></i> <b>Off</b>: odd pairs are held for you to review. <b>On</b>: they're written straight to type 3 (transfer blocked).</p>
                    </div>
                </div>

                <div class="field anim" style="--d:5; margin-top:2px">
                    <label><i class="bi bi-door-open"></i> Station access buffer <span class="hint">(seconds)</span></label>
                    <div class="num-stepper" data-step="30" data-min="0" data-max="900">
                        <button type="button" class="step-btn" data-dir="-1" aria-label="decrease"><i class="bi bi-dash-lg"></i></button>
                        <input type="number" class="step-val" id="access_buffer_sec" value="180" min="0" step="30">
                        <button type="button" class="step-btn" data-dir="1" aria-label="increase"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <p class="desc"><i class="bi bi-info-circle"></i> Extra time added on top of walking for transfers that enter a <b>rail/metro</b> station (go down, buy a ticket, find the platform) — added to <b>min_transfer_time</b>. 0 = walking only. <i>(per VN team)</i></p>
                </div>

                <details class="adv anim" style="--d:6">
                    <summary><i class="bi bi-chevron-right"></i> Advanced settings</summary>
                    <div class="grid" style="margin-top:8px">
                        <div class="field">
                            <label><i class="bi bi-clock-history"></i> Max reasonable walk <span class="hint">(seconds)</span></label>
                            <div class="num-stepper" data-step="30" data-min="60" data-max="3600">
                                <button type="button" class="step-btn" data-dir="-1" aria-label="decrease"><i class="bi bi-dash-lg"></i></button>
                                <input type="number" class="step-val" id="max_walk_sec" value="900" min="60" step="30">
                                <button type="button" class="step-btn" data-dir="1" aria-label="increase"><i class="bi bi-plus-lg"></i></button>
                            </div>
                            <p class="desc"><i class="bi bi-info-circle"></i> If walking takes longer than this, the pair is held (or blocked) — likely split by a highway/river, not a real transfer.</p>
                        </div>
                        <div class="field">
                            <label><i class="bi bi-bezier2"></i> Detour factor <span class="hint">× straight line</span></label>
                            <div class="num-stepper" data-step="0.5" data-min="1" data-max="6">
                                <button type="button" class="step-btn" data-dir="-1" aria-label="decrease"><i class="bi bi-dash-lg"></i></button>
                                <input type="number" class="step-val" id="detour_factor" value="3" min="1" step="0.5">
                                <button type="button" class="step-btn" data-dir="1" aria-label="increase"><i class="bi bi-plus-lg"></i></button>
                            </div>
                            <p class="desc"><i class="bi bi-info-circle"></i> If the walking route is more than this many times the straight-line distance, the pair looks suspicious and is held.</p>
                        </div>
                        <div class="field">
                            <label><i class="bi bi-hourglass-split"></i> Minimum transfer <span class="hint">(seconds)</span></label>
                            <div class="num-stepper" data-step="5" data-min="0" data-max="600">
                                <button type="button" class="step-btn" data-dir="-1" aria-label="decrease"><i class="bi bi-dash-lg"></i></button>
                                <input type="number" class="step-val" id="min_floor_sec" value="30" min="0" step="5">
                                <button type="button" class="step-btn" data-dir="1" aria-label="increase"><i class="bi bi-plus-lg"></i></button>
                            </div>
                            <p class="desc"><i class="bi bi-info-circle"></i> Floor for min_transfer_time — any measured time below this is raised up to it (avoids unrealistically short transfers).</p>
                        </div>
                    </div>
                </details>

                <div class="actions anim" style="--d:6">
                    <button class="btn btn-primary ml-auto" id="btnPreview" disabled onclick="doPreview()">
                        <span class="spin"></span><span class="lbl">View candidates <i class="bi bi-arrow-right"></i></span>
                    </button>
                </div>
            </div>

            <!-- ============ STEP 2 ============ -->
            <div class="panel" id="p2">
                <h2>Transfer candidates</h2>
                <p class="sub">Nearby pairs of different stops. No API calls yet — review here first.</p>
                <div class="summary" id="previewStats"></div>
                <div class="tbl-wrap">
                    <div class="cap"><i class="bi bi-list-ol"></i> Pair list</div>
                    <div style="max-height:340px;overflow:auto"><table class="tbl"><tbody id="previewRows"></tbody></table></div>
                </div>
                <div class="note" id="genNote"></div>
                <div class="actions">
                    <button class="btn btn-ghost" onclick="goStep(1)"><i class="bi bi-arrow-left"></i> Change settings</button>
                    <button class="btn btn-primary ml-auto" id="btnGenerate" onclick="doGenerate()">
                        <span class="spin"></span><span class="lbl"><i class="bi bi-cpu"></i> Generate (measure walking)</span>
                    </button>
                </div>
                <div class="prog" id="prog"></div>
            </div>

            <!-- ============ STEP 3 ============ -->
            <div class="panel" id="p3">
                <h2>transfers.txt result</h2>
                <p class="sub">Transfer time from AWS Routes (walking). Odd pairs are held for review.</p>
                <div class="summary" id="resultStats"></div>

                <div class="codebox">
                    <button class="copy" onclick="copyTxt()"><i class="bi bi-clipboard"></i> Copy</button>
                    <pre id="txtOut"></pre>
                </div>
                <div class="actions" style="margin-top:14px">
                    <button class="btn btn-ghost" onclick="goStep(2)"><i class="bi bi-arrow-left"></i> Back</button>
                    <button class="btn btn-primary ml-auto" onclick="downloadTxt()"><i class="bi bi-download"></i> Download transfers.txt</button>
                </div>

                <div id="confirmedWrap" style="margin-top:22px"></div>
                <div id="reviewWrap" style="margin-top:14px"></div>
            </div>
        </section>

        <p class="foot">stop_id = <b>POI IT</b> · transfer_type <b>2</b> = uses measured walking time · 2 rows per pair (both directions)</p>
    </main>

    <div class="toast" id="toast"></div>

    @verbatim
    <script>
        const $ = (s, el = document) => el.querySelector(s);
        const csrf = $('meta[name=csrf-token]').content;
        const state = { token: null, preview: null, result: null };

        /* ---------- file upload ---------- */
        const fileInput = $('#excel'), drop = $('#dropzone');
        drop.addEventListener('click', () => fileInput.click());
        drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag'); });
        ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('drag'); }));
        drop.addEventListener('drop', e => { if (e.dataTransfer.files[0]) { fileInput.files = e.dataTransfer.files; onFile(); } });
        fileInput.addEventListener('change', onFile);
        function onFile() {
            const f = fileInput.files[0];
            if (!f) return;
            $('#fileName').textContent = f.name;
            drop.classList.add('has-file');
            $('#btnPreview').disabled = false;
        }

        /* ---------- segmented travel mode (sliding indicator) ---------- */
        const seg = $('#travelSeg'), segInd = seg.querySelector('.seg-ind'), travelHidden = $('#travel_mode');
        const moveSeg = btn => { segInd.style.left = btn.offsetLeft + 'px'; segInd.style.width = btn.offsetWidth + 'px'; };
        seg.querySelectorAll('.seg').forEach(b => b.addEventListener('click', () => {
            seg.querySelector('.seg.active').classList.remove('active');
            b.classList.add('active'); travelHidden.value = b.dataset.val; moveSeg(b);
        }));

        /* ---------- range: green fill + value bubble ---------- */
        const rng = $('#max_transfer_m_r'), num = $('#max_transfer_m');
        const bubble = $('#rangeBubble'), rangeWrap = rng.closest('.range-wrap');
        function paintRange() {
            const v = +rng.value, mn = +rng.min, mx = +rng.max, pct = (v - mn) / (mx - mn);
            rng.style.setProperty('--pct', (pct * 100) + '%');
            bubble.childNodes[0].nodeValue = v;
            const w = rng.offsetWidth, thumb = 21;
            bubble.style.left = (rng.offsetLeft + pct * (w - thumb) + thumb / 2) + 'px';
        }
        rng.addEventListener('input', () => { num.value = rng.value; paintRange(); });
        num.addEventListener('input', () => { rng.value = num.value; paintRange(); });
        ['pointerdown', 'focus'].forEach(e => rng.addEventListener(e, () => rangeWrap.classList.add('dragging')));
        ['pointerup', 'blur'].forEach(e => rng.addEventListener(e, () => rangeWrap.classList.remove('dragging')));

        /* ---------- position indicators once laid out ---------- */
        function initForm() { moveSeg(seg.querySelector('.seg.active')); paintRange(); }
        initForm();
        window.addEventListener('load', initForm);
        window.addEventListener('resize', initForm);

        /* ---------- steppers ( − / + , hold to repeat ) ---------- */
        document.querySelectorAll('.num-stepper').forEach(stepper => {
            const input = stepper.querySelector('.step-val');
            const stepv = parseFloat(stepper.dataset.step) || 1;
            const mn = stepper.dataset.min != null ? parseFloat(stepper.dataset.min) : -Infinity;
            const mx = stepper.dataset.max != null ? parseFloat(stepper.dataset.max) : Infinity;
            const dec = (String(stepv).split('.')[1] || '').length;
            const apply = dir => {
                let v = (parseFloat(input.value) || 0) + dir * stepv;
                v = Math.min(mx, Math.max(mn, v));
                input.value = dec ? +v.toFixed(dec) : v;
                input.classList.remove('bump'); void input.offsetWidth; input.classList.add('bump');
            };
            stepper.querySelectorAll('.step-btn').forEach(btn => {
                const dir = +btn.dataset.dir; let timer;
                const start = e => {
                    e.preventDefault(); apply(dir);
                    let delay = 380;
                    const tick = () => { apply(dir); delay = Math.max(45, delay * 0.78); timer = setTimeout(tick, delay); };
                    timer = setTimeout(tick, delay);
                };
                const stop = () => clearTimeout(timer);
                btn.addEventListener('pointerdown', start);
                ['pointerup', 'pointerleave', 'pointercancel'].forEach(ev => btn.addEventListener(ev, stop));
            });
        });

        toggleProvider();   // show/hide geo-tools auth based on default provider

        /* ---------- settings ---------- */
        function settings() {
            return {
                travel_mode: $('#travel_mode').value,
                max_transfer_m: +num.value,
                default_type: +$('#default_type').value,
                max_walk_sec: +$('#max_walk_sec').value,
                detour_factor: +$('#detour_factor').value,
                auto_type3: $('#auto_type3').checked,
                min_floor_sec: +$('#min_floor_sec').value,
                access_buffer_sec: +$('#access_buffer_sec').value,
                provider: $('#provider').value,
            };
        }
        function toggleProvider() {
            $('#geotoolsAuth').classList.toggle('hide', $('#provider').value !== 'geotools');
        }

        /* ---------- step navigation ---------- */
        function goStep(n) {
            for (let i = 1; i <= 3; i++) $('#p' + i).classList.toggle('active', i === n);
            document.querySelectorAll('.step').forEach(s => {
                const d = +s.dataset.step;
                s.classList.toggle('active', d === n);
                s.classList.toggle('done', d < n);
            });
            document.querySelectorAll('.stepper .bar').forEach((b, i) => b.classList.toggle('fill', i < n - 1));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function loading(sel, on) { $(sel).classList.toggle('loading', on); $(sel).disabled = on; }
        function toast(msg) {
            const t = $('#toast'); t.textContent = msg; t.classList.add('show');
            clearTimeout(t._t); t._t = setTimeout(() => t.classList.remove('show'), 3200);
        }
        const modeChip = m => `<span class="chip mode">${m}</span>`;
        const esc = s => String(s).replace(/[<>&]/g, c => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]));

        /* ---------- count-up ---------- */
        function countUp(el, to) {
            const dur = 600, t0 = performance.now();
            (function tick(t) {
                const p = Math.min(1, (t - t0) / dur);
                el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(tick);
            })(t0);
        }

        /* ---------- STEP 1 -> preview ---------- */
        async function doPreview() {
            if (!fileInput.files[0]) return toast('Choose an Excel file first');
            loading('#btnPreview', true);
            const fd = new FormData();
            fd.append('excel', fileInput.files[0]);
            Object.entries(settings()).forEach(([k, v]) => fd.append(k, v));
            try {
                const r = await fetch('/tools/transfers/preview', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd });
                const d = await r.json();
                if (!d.ok) return toast(d.error || 'Preview failed');
                state.token = d.file_token; state.preview = d;
                renderPreview(d); goStep(2);
            } catch (e) { toast('Error: ' + e.message); }
            finally { loading('#btnPreview', false); }
        }

        function renderPreview(d) {
            $('#previewStats').innerHTML = `
                <div class="stat"><div class="v" data-n="${d.stops}">0</div><div class="l">unique stops</div></div>
                <div class="stat green"><div class="v" data-n="${d.pair_count}">0</div><div class="l">candidate pairs</div></div>`;
            $('#previewStats').querySelectorAll('.v').forEach(v => countUp(v, +v.dataset.n));
            $('#previewRows').innerHTML = d.pairs.map((p, i) => `
                <tr style="animation-delay:${i * 45}ms">
                    <td class="stn">${esc(p.from_station)} ${modeChip(p.from_mode)}</td>
                    <td class="arrow"><i class="bi bi-arrow-left-right"></i></td>
                    <td class="stn">${esc(p.to_station)} ${modeChip(p.to_mode)}</td>
                    <td style="text-align:right;color:#6b7280">${p.straight_m} m ${p.cross_mode ? '<span class="chip cross">cross-mode</span>' : ''}</td>
                </tr>`).join('') || `<tr><td style="padding:20px;text-align:center;color:#6b7280">No pairs within that radius.</td></tr>`;
            $('#genNote').innerHTML = `<i class="bi bi-info-circle"></i> Generate will call AWS Routes <b>${d.pair_count}×</b> (one per pair) to measure walking time.`;
        }

        /* ---------- STEP 2 -> generate ---------- */
        async function doGenerate(overrides = []) {
            loading('#btnGenerate', true); $('#prog').classList.add('show');
            try {
                const r = await fetch('/tools/transfers/generate', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ file_token: state.token, overrides, geotools_curl: $('#geotools_curl').value, ...settings() })
                });
                const d = await r.json();
                if (!d.ok) return toast(d.error || 'Generate failed');
                state.result = d; renderResult(d); goStep(3);
            } catch (e) { toast('Error: ' + e.message); }
            finally { loading('#btnGenerate', false); $('#prog').classList.remove('show'); }
        }

        function typeChip(t) {
            const lbl = { 0: 'type 0', 1: 'type 1', 2: 'type 2', 3: 'type 3 · blocked' }[t] || ('type ' + t);
            return `<span class="chip t${t}">${lbl}</span>`;
        }

        function renderResult(d) {
            const c2 = d.counts['2'] || 0, c3 = d.counts['3'] || 0, held = d.review.length;
            $('#resultStats').innerHTML = `
                <div class="stat"><div class="v" data-n="${d.rows}">0</div><div class="l">transfers.txt rows</div></div>
                <div class="stat green"><div class="v" data-n="${c2}">0</div><div class="l">type 2 (measured)</div></div>
                <div class="stat red"><div class="v" data-n="${c3}">0</div><div class="l">type 3 (blocked)</div></div>
                <div class="stat amber"><div class="v" data-n="${held}">0</div><div class="l">held</div></div>`;
            $('#resultStats').querySelectorAll('.v').forEach(v => countUp(v, +v.dataset.n));
            $('#txtOut').textContent = d.transfers_txt;

            // confirmed
            $('#confirmedWrap').innerHTML = !d.confirmed.length ? '' : `
                <div class="tbl-wrap">
                    <div class="cap"><i class="bi bi-check2-circle" style="color:var(--green)"></i> Included in file (${d.confirmed.length} pairs)</div>
                    <table class="tbl"><thead><tr><th>From</th><th>To</th><th style="text-align:right">Walk + buffer</th><th style="text-align:right">Type</th></tr></thead>
                    <tbody>${d.confirmed.map((r, i) => `
                        <tr style="animation-delay:${i * 40}ms">
                            <td class="stn">${esc(r.from_station)} ${modeChip(r.from_mode)}</td>
                            <td class="stn">${esc(r.to_station)} ${modeChip(r.to_mode)}</td>
                            <td style="text-align:right;color:#6b7280">${r.walk_m != null ? r.walk_m + ' m · ' + (r.buffer_sec ? r.walk_sec + '+' + r.buffer_sec + '=<b style="color:#1f2937">' + r.min_time + '</b>s' : r.walk_sec + ' s') : '—'}</td>
                            <td style="text-align:right">${typeChip(r.transfer_type)}</td>
                        </tr>`).join('')}</tbody></table>
                </div>`;

            // held / review with override controls
            $('#reviewWrap').innerHTML = !held ? '' : `
                <div class="tbl-wrap">
                    <div class="cap"><i class="bi bi-exclamation-triangle" style="color:#d97706"></i> Held — needs decision (${held})</div>
                    <table class="tbl"><thead><tr><th>Pair</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody>${d.review.map((r, i) => `
                        <tr style="animation-delay:${i * 40}ms">
                            <td class="stn">${esc(r.from_station)} <span class="arrow"><i class="bi bi-arrow-left-right"></i></span> ${esc(r.to_station)}</td>
                            <td style="color:#6b7280;font-size:.76rem">${esc(r.reason || '')}</td>
                            <td><select class="ovsel" data-i="${i}">
                                <option value="">Hold (default)</option>
                                <option value="3">Block — type 3</option>
                                <option value="2">Include anyway — type 2</option>
                            </select></td>
                        </tr>`).join('')}</tbody></table>
                </div>
                <div class="actions" style="margin-top:6px">
                    <button class="btn btn-ghost ml-auto" onclick="applyOverrides()"><i class="bi bi-arrow-repeat"></i> Apply &amp; rebuild</button>
                </div>`;
        }

        function applyOverrides() {
            const ov = [];
            document.querySelectorAll('.ovsel').forEach(sel => {
                if (!sel.value) return;
                const r = state.result.review[+sel.dataset.i];
                const o = { from_id: r.from_id, to_id: r.to_id, transfer_type: +sel.value };
                if (+sel.value === 2 && r.walk_sec) o.min_transfer_time = r.walk_sec;
                ov.push(o);
            });
            if (!ov.length) return toast('Nothing selected');
            goStep(2); doGenerate(ov);
        }

        /* ---------- download / copy ---------- */
        function downloadTxt() {
            const blob = new Blob([state.result.transfers_txt], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = 'transfers.txt'; a.click();
            URL.revokeObjectURL(a.href);
            toast('transfers.txt downloaded');
        }
        function copyTxt() {
            navigator.clipboard.writeText(state.result.transfers_txt).then(() => toast('Copied to clipboard'));
        }
    </script>
    @endverbatim
</body>

</html>
