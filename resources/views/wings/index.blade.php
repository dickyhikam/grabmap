<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wings × GrabMaps — Demo Routing & Distribusi</title>

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
            --amber: #b45309;
            --amber-soft: #fef3c7;
            --blue: #1d4ed8;
            --blue-soft: #dbeafe;
            --side-w: 410px;
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

        #map { position: absolute; inset: 0; }

        /* ---------- Panel kiri ---------- */
        .side {
            position: absolute;
            top: 12px;
            left: 12px;
            bottom: 12px;
            width: var(--side-w);
            z-index: 10;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .18);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .side-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px 12px;
            border-bottom: 1px solid var(--line);
        }
        .side-head img { height: 30px; width: auto; }
        .side-head h1 { margin: 0; font-size: 15px; font-weight: 800; letter-spacing: -.01em; }
        .side-head p { margin: 2px 0 0; color: var(--mut); font-size: 11.5px; }

        .meter {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--line);
            border-bottom: 1px solid var(--line);
        }
        .meter div { background: var(--bg); padding: 8px 12px; }
        .meter b { display: block; font-size: 15px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .meter span { color: var(--mut); font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; }

        /* ---------- Dua mode: murni API / solusi lengkap ---------- */
        .mode { padding: 10px 12px; border-bottom: 1px solid var(--line); }
        .mode-seg { display: grid; grid-template-columns: 1fr 1fr; gap: 3px; padding: 3px; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; }
        .mode-seg button { border: 0; background: none; border-radius: 7px; padding: 7px; font: inherit; font-weight: 700; font-size: 12px; cursor: pointer; color: var(--mut); }
        .mode-seg button.on { background: #fff; color: var(--ink); box-shadow: 0 1px 4px rgba(15, 23, 42, .15); }
        .mode p { margin: 7px 2px 0; font-size: 11px; color: var(--mut); line-height: 1.45; }
        .mode .tally { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 6px; }

        .mods { overflow-y: auto; padding: 10px; flex: 1; display: flex; flex-direction: column; }
        .mods > * { flex: none; }
        .chip-st.na { background: #fee2e2; color: #b91c1c; }
        .chip-pure, .pure-msg { display: none; }
        body.pure .chip-st:not(.chip-pure) { display: none; }
        body.pure .chip-pure { display: inline-block; }
        body.pure .mod[data-tier="a"] { order: 1; }
        body.pure .mod[data-tier="b"] { order: 2; }
        body.pure .mod[data-tier="c"] { order: 3; }
        body.pure .mods > p { order: 9; }
        /* Modul yang bukan murni API: isi interaktifnya disembunyikan, tinggal penjelasannya. */
        body.pure .mod:not([data-tier="a"]) .mod-body > * { display: none; }
        body.pure .mod:not([data-tier="a"]) .mod-body > .needs { display: flex; }
        body.pure .mod:not([data-tier="a"]) .mod-body > .pure-msg { display: block; }
        body.pure .mod:not([data-tier="a"]) .info-btn { display: none; }
        body.pure .mod:not([data-tier="a"]) .mod-ic { background: #f1f5f9; color: #94a3b8; }
        .pure-msg p { margin: 4px 0 8px; font-size: 12px; line-height: 1.5; }
        .pure-msg b.k { display: block; font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--mut); margin-top: 8px; }

        .mod {
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-bottom: 8px;
            overflow: hidden;
            transition: border-color .15s, box-shadow .15s;
        }
        .mod.open { border-color: var(--g); box-shadow: 0 2px 10px rgba(0, 177, 79, .12); }

        .mod-head {
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: 10px 12px;
            display: flex;
            gap: 10px;
            align-items: center;
            cursor: pointer;
            font: inherit;
            color: inherit;
        }
        .mod-head:hover { background: var(--bg); }
        .mod-ic {
            width: 34px; height: 34px; flex: none;
            border-radius: 10px;
            background: var(--g-soft); color: var(--g-d);
            display: grid; place-items: center;
            font-size: 16px;
        }
        .mod.open .mod-ic { background: var(--g); color: #fff; }
        .mod-tt { flex: 1; min-width: 0; }
        .mod-tt b { display: block; font-size: 13px; font-weight: 700; }
        .mod-tt small { color: var(--mut); font-size: 11px; display: block; margin-top: 1px; }

        .chip-st {
            flex: none;
            font-size: 10px; font-weight: 700;
            padding: 3px 7px; border-radius: 999px;
            text-transform: uppercase; letter-spacing: .03em;
            white-space: nowrap;
        }
        .chip-st.live { background: var(--g-soft); color: var(--g-d); }
        .chip-st.mix { background: var(--blue-soft); color: var(--blue); }
        .chip-st.sim { background: var(--amber-soft); color: var(--amber); }

        .mod-body { display: none; padding: 4px 12px 12px; border-top: 1px dashed var(--line); }
        .mod.open .mod-body { display: block; }

        .needs { display: flex; flex-wrap: wrap; gap: 4px; margin: 8px 0; }
        .needs span {
            background: var(--bg); border: 1px solid var(--line);
            font-size: 10.5px; padding: 2px 7px; border-radius: 6px; color: #334155;
        }

        .hint { color: var(--mut); font-size: 11.5px; margin: 6px 0; line-height: 1.45; }
        .note {
            background: var(--amber-soft); color: #78350f;
            border-radius: 8px; padding: 8px 10px; font-size: 11px; line-height: 1.45; margin: 10px 0 0;
        }
        .note.info { background: var(--blue-soft); color: #1e3a8a; }
        .note code { font-size: 10.5px; background: rgba(255, 255, 255, .6); padding: 0 3px; border-radius: 3px; }

        /* ---------- Kontrol ---------- */
        .row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin: 8px 0; }
        .grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin: 8px 0; }
        label.f { display: block; font-size: 10.5px; color: var(--mut); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        label.f input, label.f select {
            display: block; width: 100%; margin-top: 3px;
            border: 1px solid var(--line); border-radius: 8px;
            padding: 7px 8px; font: inherit; font-size: 13px; color: var(--ink);
            background: #fff; text-transform: none; letter-spacing: 0;
        }
        label.f input:focus, label.f select:focus { outline: 2px solid var(--g); outline-offset: -1px; }

        .btn {
            border: 0; border-radius: 9px; padding: 8px 12px;
            background: var(--g); color: #fff;
            font: inherit; font-weight: 700; font-size: 12.5px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn:hover { background: var(--g-d); }
        .btn.ghost { background: #fff; color: var(--ink); border: 1px solid var(--line); }
        .btn.ghost:hover { background: var(--bg); }
        .btn.danger { background: var(--red); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn .spin {
            width: 12px; height: 12px; border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .4); border-top-color: #fff;
            animation: spin .7s linear infinite;
        }
        .btn.ghost .spin { border-color: rgba(15, 23, 42, .2); border-top-color: var(--ink); }
        @keyframes spin { to { transform: rotate(360deg); } }

        .chips { display: flex; gap: 5px; flex-wrap: wrap; margin: 8px 0; }
        .chips button {
            border: 1px solid var(--line); background: #fff; border-radius: 999px;
            padding: 5px 10px; font: inherit; font-size: 11.5px; font-weight: 600; cursor: pointer; color: #334155;
        }
        .chips button.on { background: var(--ink); border-color: var(--ink); color: #fff; }

        label.sw { display: flex; align-items: center; gap: 8px; padding: 6px 0; font-weight: 600; cursor: pointer; }
        label.sw input { width: 16px; height: 16px; accent-color: var(--g); }

        input[type=range] { accent-color: var(--g); flex: 1; }

        /* ---------- Hasil ---------- */
        table.t { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11.5px; font-variant-numeric: tabular-nums; }
        table.t th { text-align: right; color: var(--mut); font-weight: 600; font-size: 10.5px; padding: 4px 5px; text-transform: uppercase; border-bottom: 1px solid var(--line); }
        table.t th:first-child, table.t td:first-child { text-align: left; }
        table.t td { padding: 6px 5px; text-align: right; border-bottom: 1px solid #f1f5f9; }
        table.t tr.pick td { background: var(--g-soft); font-weight: 700; }
        table.t tbody tr { cursor: pointer; }
        table.t tbody tr:hover td { background: var(--bg); }
        table.t tr.pick:hover td { background: var(--g-soft); }
        .tag { display: inline-block; font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 4px; margin-left: 4px; vertical-align: 1px; }
        .tag.g { background: var(--g); color: #fff; }
        .tag.s { background: #e2e8f0; color: #334155; }
        .ok { color: var(--g-d); font-weight: 700; }
        .bad { color: var(--red); font-weight: 700; }

        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin: 8px 0; }
        .kpis.two { grid-template-columns: repeat(2, 1fr); }
        .kpi { background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 8px 10px; }
        .kpi b { display: block; font-size: 15px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .kpi span { font-size: 10.5px; color: var(--mut); }
        .kpi.win { background: var(--g-soft); border-color: #b7e8cd; }
        .kpi small { display: block; font-size: 10.5px; color: var(--g-d); font-weight: 700; margin-top: 2px; }
        .kpi small.up { color: var(--amber); }

        .bars { display: flex; align-items: flex-end; gap: 6px; height: 120px; margin: 12px 0 4px; }
        .bar { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; cursor: pointer; border: 0; background: none; font: inherit; padding: 0; }
        .bar i { display: block; width: 100%; border-radius: 6px 6px 0 0; background: #cbd5e1; transition: height .3s; }
        .bar.on i { background: var(--g); }
        .bar b { font-size: 11px; margin-bottom: 3px; font-variant-numeric: tabular-nums; }
        .bar span { font-size: 10.5px; color: var(--mut); margin-top: 4px; }

        .legend { display: flex; gap: 10px; flex-wrap: wrap; font-size: 10.5px; color: var(--mut); margin: 8px 0; }
        .legend i { display: inline-block; width: 16px; height: 4px; border-radius: 2px; vertical-align: middle; margin-right: 4px; }
        .legend i.dot { width: 8px; height: 8px; border-radius: 50%; }

        .log { list-style: none; margin: 8px 0 0; padding: 0; max-height: 190px; overflow-y: auto; }
        .log li { padding: 6px 0 6px 18px; border-bottom: 1px solid #f1f5f9; position: relative; font-size: 11.5px; line-height: 1.4; }
        .log li::before { content: ''; position: absolute; left: 3px; top: 11px; width: 7px; height: 7px; border-radius: 50%; background: #94a3b8; }
        .log li.g::before { background: var(--g); }
        .log li.r::before { background: var(--red); }
        .log li.b::before { background: var(--blue); }
        .log li time { color: var(--mut); font-variant-numeric: tabular-nums; margin-right: 5px; }

        .mx-wrap { overflow: auto; margin-top: 8px; max-height: 300px; border: 1px solid var(--line); border-radius: 8px; }
        table.mx { border-collapse: collapse; font-size: 10.5px; font-variant-numeric: tabular-nums; width: 100%; }
        table.mx th, table.mx td { padding: 5px 4px; text-align: center; min-width: 26px; }
        table.mx th { background: var(--bg); color: var(--mut); font-weight: 700; position: sticky; top: 0; }
        table.mx th.r { left: 0; position: sticky; }
        table.mx td.z { color: #cbd5e1; }

        .steps { list-style: none; margin: 8px 0 0; padding: 0; max-height: 220px; overflow-y: auto; }
        .steps li { display: flex; gap: 8px; padding: 6px 4px; border-bottom: 1px solid #f1f5f9; font-size: 11.5px; align-items: center; }
        .steps li i { color: var(--mut); font-size: 14px; width: 16px; text-align: center; }
        .steps li span { flex: 1; }
        .steps li em { font-style: normal; color: var(--mut); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .steps li.cur { background: var(--g-soft); border-radius: 6px; font-weight: 700; }
        .steps li.cur i { color: var(--g-d); }
        .steps li.done { opacity: .45; }

        .order { list-style: none; margin: 8px 0 0; padding: 0; max-height: 190px; overflow-y: auto; }
        .order li { display: flex; gap: 8px; align-items: center; padding: 5px 2px; border-bottom: 1px solid #f1f5f9; font-size: 11.5px; }
        .order li b { width: 20px; height: 20px; border-radius: 50%; background: var(--g); color: #fff; display: grid; place-items: center; font-size: 10px; flex: none; }
        .order li span { flex: 1; }
        .order li em { font-style: normal; color: var(--mut); font-variant-numeric: tabular-nums; }

        /* ---------- Marker ---------- */
        .mk {
            width: 26px; height: 26px; border-radius: 50%;
            background: #fff; border: 2px solid var(--ink); color: var(--ink);
            display: grid; place-items: center; font-size: 11px; font-weight: 800;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .25);
        }
        .mk.depot { background: var(--ink); color: #fff; border-color: #fff; width: 32px; height: 32px; font-size: 14px; }
        .mk.seq { background: var(--g); border-color: #fff; color: #fff; }
        .mk.done { background: var(--g); border-color: #fff; color: #fff; }
        .mk.city { width: 14px; height: 14px; border-width: 3px; border-color: #94a3b8; }
        .mk.city.okc { border-color: var(--g); background: var(--g); }
        .mk.city.badc { border-color: var(--red); background: var(--red); }
        .truck {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--g); color: #fff; border: 3px solid #fff;
            display: grid; place-items: center; font-size: 15px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .35);
        }
        .incident {
            width: 30px; height: 30px; border-radius: 8px; transform: rotate(45deg);
            background: var(--red); border: 3px solid #fff; color: #fff;
            display: grid; place-items: center; font-size: 13px;
            box-shadow: 0 3px 10px rgba(220, 38, 38, .5);
        }
        .incident i { display: inline-block; transform: rotate(-45deg); }
        .puck {
            width: 22px; height: 22px; border-radius: 50%;
            background: #2563eb; border: 3px solid #fff;
            box-shadow: 0 0 0 6px rgba(37, 99, 235, .25);
        }

        /* ---------- Ponsel (navigasi) ---------- */
        .phone {
            position: absolute; right: 24px; top: 50%; transform: translateY(-50%);
            width: 300px; height: min(600px, calc(100vh - 60px));
            z-index: 9;
            background: #0f172a; border-radius: 38px; padding: 10px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .45);
        }
        body:has(.info-panel:not([hidden])) .phone { right: 508px; }
        .ph-screen { position: relative; width: 100%; height: 100%; border-radius: 30px; overflow: hidden; background: #e2e8f0; }
        #phoneMap { position: absolute; inset: 0; }
        .ph-top {
            position: absolute; top: 10px; left: 10px; right: 10px; z-index: 2;
            background: var(--g-d); color: #fff; border-radius: 16px;
            padding: 12px 14px; display: flex; gap: 12px; align-items: center;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .25);
        }
        .ph-top > i { font-size: 30px; }
        .ph-top b { display: block; font-size: 20px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .ph-top span { font-size: 12px; line-height: 1.3; display: block; }
        .ph-next {
            position: absolute; top: 86px; left: 10px; z-index: 2;
            background: #065f35; color: #d1fae5; font-size: 11px;
            padding: 5px 10px; border-radius: 0 0 10px 10px; max-width: 85%;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ph-bottom {
            position: absolute; bottom: 10px; left: 10px; right: 10px; z-index: 2;
            background: #fff; border-radius: 16px; padding: 10px 6px;
            display: grid; grid-template-columns: repeat(3, 1fr); text-align: center;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .2);
        }
        .ph-bottom b { display: block; font-size: 16px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .ph-bottom span { font-size: 10px; color: var(--mut); text-transform: uppercase; }
        .ph-tag {
            position: absolute; bottom: -24px; left: 0; right: 0; text-align: center;
            font-size: 10.5px; color: #fff; text-shadow: 0 1px 3px rgba(0, 0, 0, .6); font-weight: 600;
        }

        /* ---------- Panel info (kanan) ---------- */
        .info-btn {
            margin-left: auto; border: 1px solid #bfdbfe; background: var(--blue-soft); color: var(--blue);
            border-radius: 999px; padding: 3px 10px; font: inherit; font-size: 11px; font-weight: 700;
            cursor: pointer; display: inline-flex; gap: 5px; align-items: center;
        }
        .info-btn:hover { background: #bfdbfe; }
        .info-panel {
            position: absolute; top: 12px; right: 12px; bottom: 12px; width: 480px; z-index: 20;
            background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, .25);
            display: flex; flex-direction: column; overflow: hidden;
        }
        .info-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid var(--line); }
        .info-head h2 { margin: 0; font-size: 14px; font-weight: 800; }
        .info-head p { margin: 2px 0 0; font-size: 11px; color: var(--mut); }
        .info-x { border: 0; background: var(--bg); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 13px; color: var(--ink); }
        .info-x:hover { background: var(--line); }
        .info-body { overflow-y: auto; padding: 4px 16px 16px; flex: 1; line-height: 1.5; }
        .info-body h3 { font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--mut); margin: 18px 0 8px; }
        .info-body ol, .info-body ul { margin: 0; padding-left: 18px; }
        .info-body li { margin-bottom: 6px; }
        .info-body code { font-size: 11px; background: var(--bg); border: 1px solid var(--line); padding: 0 4px; border-radius: 4px; }
        .src { display: grid; grid-template-columns: auto 1fr; gap: 7px 10px; align-items: start; }
        .src-tag { font-size: 9.5px; font-weight: 800; padding: 2px 7px; border-radius: 5px; text-transform: uppercase; white-space: nowrap; margin-top: 1px; text-align: center; }
        .src-tag.aws { background: #ffedd5; color: #c2410c; }
        .src-tag.page { background: #e2e8f0; color: #334155; }
        .src-tag.none { background: #fee2e2; color: #b91c1c; }
        .src small { display: block; color: var(--mut); font-size: 11px; }
        .stp { border: 1px solid var(--line); border-radius: 10px; padding: 9px 11px; margin-bottom: 7px; }
        .stp.aws { border-left: 3px solid #f97316; }
        .stp.page { border-left: 3px solid #64748b; }
        .stp-h { display: flex; align-items: center; gap: 7px; font-weight: 700; font-size: 12.5px; }
        .stp-h .n { width: 19px; height: 19px; border-radius: 50%; background: var(--ink); color: #fff; font-size: 10px; display: grid; place-items: center; flex: none; }
        .stp-h .src-tag { margin: 0 0 0 auto; }
        .stp .ep { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 10.5px; word-break: break-all; margin: 6px 0 2px; background: var(--bg); border-radius: 6px; padding: 5px 7px; }
        .stp .ep b { background: var(--ink); color: #fff; padding: 1px 5px; border-radius: 4px; margin-right: 5px; font-size: 9.5px; }
        .stp dl { margin: 6px 0 0; display: grid; grid-template-columns: auto 1fr; gap: 3px 9px; font-size: 11.5px; }
        .stp dt { color: var(--mut); font-weight: 600; white-space: nowrap; }
        .stp dd { margin: 0; }
        table.fx { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 11.5px; font-variant-numeric: tabular-nums; }
        table.fx th { text-align: left; color: var(--mut); font-size: 10px; text-transform: uppercase; padding: 4px 6px; border-bottom: 1px solid var(--line); }
        table.fx td { padding: 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        table.fx td.m { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; white-space: nowrap; }
        table.fx tr.pick td { background: var(--g-soft); font-weight: 700; }
        .call { border: 1px solid var(--line); border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
        .call-head { padding: 8px 10px; background: var(--bg); font-size: 11.5px; }
        .call-head .u { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 10.5px; word-break: break-all; margin-top: 4px; }
        .call-head .u b { background: var(--ink); color: #fff; padding: 1px 5px; border-radius: 4px; margin-right: 5px; font-size: 9.5px; }
        .call-head .meta { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; color: var(--mut); }
        .call-head .meta strong { color: var(--ink); }
        .st { font-weight: 800; padding: 1px 6px; border-radius: 4px; font-size: 10.5px; }
        .st.s2 { background: var(--g-soft); color: var(--g-d); }
        .st.s4 { background: #fee2e2; color: #b91c1c; }
        .call details summary { padding: 7px 10px; cursor: pointer; font-weight: 700; font-size: 11.5px; border-top: 1px solid var(--line); display: flex; align-items: center; gap: 6px; }
        .call details summary span { color: var(--mut); font-weight: 500; }
        .call details summary button { margin-left: auto; border: 1px solid var(--line); background: #fff; border-radius: 6px; font: inherit; font-size: 10.5px; padding: 2px 7px; cursor: pointer; }
        pre.json { margin: 0; padding: 10px; background: #0f172a; color: #e2e8f0; font-size: 10.5px; line-height: 1.5; max-height: 340px; overflow: auto; white-space: pre; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

        .toast {
            position: absolute; bottom: 24px; left: 50%; transform: translate(-50%, 20px);
            background: var(--ink); color: #fff; padding: 10px 16px; border-radius: 10px;
            font-size: 12.5px; z-index: 30; opacity: 0; pointer-events: none; transition: all .25s;
            max-width: min(520px, 90vw);
        }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }
        .toast.err { background: var(--red); }

        @media (max-width: 820px) {
            .side { top: auto; left: 8px; right: 8px; bottom: 8px; width: auto; height: 52vh; }
            .info-panel { top: 8px; left: 8px; right: 8px; bottom: 8px; width: auto; }
            body:has(.info-panel:not([hidden])) .phone { right: 8px; }
            .phone { width: 190px; height: 300px; top: 8px; right: 8px; transform: none; border-radius: 26px; padding: 6px; }
            .ph-screen { border-radius: 20px; }
            .ph-top { padding: 7px 9px; gap: 8px; }
            .ph-top > i { font-size: 20px; }
            .ph-top b { font-size: 14px; }
            .ph-top span { font-size: 10px; }
            .ph-next, .ph-tag { display: none; }
            .ph-bottom b { font-size: 12px; }
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <aside class="side" id="side">
        <header class="side-head">
            <img src="{{ asset('logo.png') }}" alt="GrabMaps">
            <div>
                <h1>Wings × GrabMaps</h1>
                <p>Demo routing truk, multi-drop &amp; tracking distribusi</p>
            </div>
        </header>

        <div class="meter" title="Setiap angka berasal dari panggilan API sungguhan selama demo ini">
            <div><b id="mReq">0</b><span>Request API</span></div>
            <div><b id="mAvg">–</b><span>Rata-rata ms</span></div>
            <div><b id="mLast">–</b><span>Terakhir ms</span></div>
        </div>

        <div class="mode">
            <div class="mode-seg" id="modeSeg">
                <button type="button" data-mode="pure">Murni API</button>
                <button type="button" data-mode="full">Solusi lengkap</button>
            </div>
            <div id="modeHint"></div>
        </div>

        <div class="mods" id="mods">

            {{-- 1. Truk --}}
            <section class="mod" data-mod="truck">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-truck"></i></span>
                    <span class="mod-tt"><b>Routing truk &amp; hindari gang sempit</b><small>Tinggi, berat, jumlah as</small></span>
                    <span class="chip-st mix">Live + heuristik</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Routing khusus truk</span><span>Hindari jalan tikus / gang sempit</span></div>
                    <div class="chips" id="truckPresets">
                        <button type="button" data-h="2.2" data-w="4" data-a="2">CDE 4 roda</button>
                        <button type="button" data-h="2.6" data-w="8" data-a="2" class="on">CDD 6 roda</button>
                        <button type="button" data-h="3.4" data-w="15" data-a="2">Fuso</button>
                        <button type="button" data-h="3.8" data-w="24" data-a="3">Tronton</button>
                    </div>
                    <div class="grid3">
                        <label class="f">Tinggi (m)<input id="tH" type="number" step="0.1" min="1.5" max="4.5" value="2.6"></label>
                        <label class="f">Berat (ton)<input id="tW" type="number" step="0.5" min="1" max="40" value="8"></label>
                        <label class="f">Jumlah as<input id="tA" type="number" step="1" min="2" max="6" value="2"></label>
                    </div>
                    <label class="f">Tujuan<select id="truckDest"></select></label>
                    <div class="row"><button class="btn" id="truckGo" type="button"><i class="bi bi-signpost-split"></i> Cari rute truk</button></div>
                    <div id="truckOut"></div>
                    <p class="note">
                        Rute, jarak, dan waktu berasal dari Routes API (live, hingga 5 alternatif). Pemilihan rute untuk truk memakai
                        <b>heuristik demo</b>: waktu tempuh + penalti manuver (belokan, belokan rapat, putar balik) yang makin berat untuk kendaraan besar.
                        Profil <code>Truck</code> bawaan dengan batasan dimensi belum dilayani di region <code>ap-southeast-1</code>.
                    </p>
                </div>
            </section>

            {{-- 2. Traffic --}}
            <section class="mod" data-mod="traffic">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-stoplights"></i></span>
                    <span class="mod-tt"><b>Traffic &amp; ETA per jam berangkat</b><small>Kecepatan per segmen jalan</small></span>
                    <span class="chip-st live">Live API</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Live traffic</span></div>
                    <p class="hint">DC Cakung → Toko Sentosa, Tangerang. Lima jam berangkat dihitung sekaligus; klik batang untuk melihat kecepatan tiap segmen.</p>
                    <div class="row"><button class="btn" id="trafGo" type="button"><i class="bi bi-clock-history"></i> Bandingkan jam berangkat</button></div>
                    <div class="bars" id="trafBars" hidden></div>
                    <div id="trafOut"></div>
                    <div class="legend" id="trafLegend" hidden>
                        <span><i style="background:#16a34a"></i>&gt; 50 km/j</span>
                        <span><i style="background:#eab308"></i>30–50</span>
                        <span><i style="background:#f97316"></i>15–30</span>
                        <span><i style="background:#dc2626"></i>&lt; 15</span>
                    </div>
                    <p class="note info">
                        ETA mengikuti <code>DepartureTime</code> — durasi dan kecepatan tiap segmen dihitung API untuk jam tersebut.
                        Umpan kepadatan real-time per ruas (<code>Traffic</code>) belum dibuka di region ini.
                    </p>
                </div>
            </section>

            {{-- 3. Insiden & re-routing --}}
            <section class="mod" data-mod="reroute">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-cone-striped"></i></span>
                    <span class="mod-tt"><b>Insiden &amp; re-routing dinamis</b><small>Rute ulang saat perjalanan</small></span>
                    <span class="chip-st mix">Live + simulasi</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Insiden &amp; penutupan jalan</span><span>Re-routing dinamis saat perjalanan</span></div>
                    <p class="hint">DC Cakung → Toko Harapan Baru, Tanjung Priok. Jalankan truk, lalu tutup jalan di depannya — atau klik titik pada rute di peta.</p>
                    <div class="row">
                        <button class="btn" id="rrStart" type="button"><i class="bi bi-play-fill"></i> Mulai perjalanan</button>
                        <button class="btn danger" id="rrBlock" type="button" disabled><i class="bi bi-exclamation-octagon"></i> Tutup jalan di depan</button>
                    </div>
                    <div class="kpis two" id="rrKpis" hidden>
                        <div class="kpi"><b id="rrEta">–</b><span>Sisa waktu</span></div>
                        <div class="kpi"><b id="rrRem">–</b><span>Sisa jarak</span></div>
                    </div>
                    <div class="legend">
                        <span><i style="background:var(--g)"></i>Rute aktif</span>
                        <span><i style="background:var(--red)"></i>Rute lama (tertutup)</span>
                    </div>
                    <ul class="log" id="rrLog"></ul>
                    <p class="note">
                        Titik penutupan jalan <b>disimulasikan</b> di halaman ini. Rute penggantinya dihitung ulang lewat Routes API (live) dari posisi truk saat itu,
                        lalu dipilih alternatif tercepat yang tidak melewati titik tertutup.
                    </p>
                </div>
            </section>

            {{-- 4. Distance Matrix --}}
            <section class="mod" data-mod="matrix">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-grid-3x3"></i></span>
                    <span class="mod-tt"><b>Distance Matrix</b><small>Batch multi-drop, throughput tinggi</small></span>
                    <span class="chip-st live">Live API</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Distance Matrix</span></div>
                    <div class="row">
                        <span>Toko</span>
                        <input type="range" id="mxN" min="3" max="12" value="8">
                        <b id="mxNv">8</b>
                    </div>
                    <div class="row">
                        <button class="btn" id="mxGo" type="button"><i class="bi bi-grid-3x3-gap"></i> Hitung matrix</button>
                        <button class="btn ghost" id="mxBurst" type="button"><i class="bi bi-lightning-charge"></i> Uji 5 batch paralel</button>
                    </div>
                    <div id="mxStats"></div>
                    <div class="mx-wrap" id="mxWrap" hidden>
                        <table class="mx" id="mxTable"></table>
                    </div>
                    <p class="hint" id="mxHint" hidden>Angka = menit berkendara dari baris ke kolom. D = DC Cakung.</p>
                </div>
            </section>

            {{-- 5. Optimasi tur --}}
            <section class="mod" data-mod="tour">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-diagram-3"></i></span>
                    <span class="mod-tt"><b>Optimasi urutan tur</b><small>Rute optimal sebelum berangkat</small></span>
                    <span class="chip-st mix">Live + solver</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Optimasi urutan tur di EMS</span></div>
                    <div class="row">
                        <span>Toko</span>
                        <input type="range" id="tourN" min="4" max="12" value="10">
                        <b id="tourNv">10</b>
                    </div>
                    <label class="sw"><input type="checkbox" id="tourBack" checked> Kembali ke DC setelah drop terakhir</label>
                    <div class="row"><button class="btn" id="tourGo" type="button"><i class="bi bi-magic"></i> Optimalkan urutan</button></div>
                    <div id="tourOut"></div>
                    <div class="chips" id="tourView" hidden>
                        <button type="button" data-view="opt" class="on">Urutan optimal</button>
                        <button type="button" data-view="base">Urutan awal</button>
                    </div>
                    <ol class="order" id="tourOrder"></ol>
                    <p class="note info">
                        Waktu antar titik diambil dari Route Matrix (live). Urutan dihitung oleh solver di aplikasi (nearest-neighbour + 2-opt) —
                        pola yang sama dipakai EMS: matrix dari API, optimasi di sisi sistem.
                    </p>
                </div>
            </section>

            {{-- 6. Navigasi --}}
            <section class="mod" data-mod="nav">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-phone"></i></span>
                    <span class="mod-tt"><b>Navigasi turn-by-turn</b><small>App Personal Delivery Assistant</small></span>
                    <span class="chip-st mix">Live + mockup</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Navigation SDK</span></div>
                    <label class="f">Tujuan<select id="navDest"></select></label>
                    <div class="row">
                        <button class="btn" id="navGo" type="button"><i class="bi bi-cursor-fill"></i> Mulai navigasi</button>
                        <button class="btn ghost" id="navStop" type="button" disabled><i class="bi bi-stop-fill"></i> Berhenti</button>
                    </div>
                    <div class="chips" id="navSpeed">
                        <button type="button" data-mult="10">×10</button>
                        <button type="button" data-mult="30" class="on">×30</button>
                        <button type="button" data-mult="80">×80</button>
                    </div>
                    <ol class="steps" id="navSteps"></ol>
                    <p class="note">
                        Instruksi belokan, nama jalan, jarak, dan durasi tiap langkah berasal dari Routes API (live).
                        Layar ponsel adalah <b>mockup web</b> untuk menggambarkan tampilan di aplikasi pengemudi; pergerakan kendaraan disimulasikan.
                    </p>
                </div>
            </section>

            {{-- 7. Tracking --}}
            <section class="mod" data-mod="track">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-broadcast-pin"></i></span>
                    <span class="mod-tt"><b>Snap-to-road &amp; geofence</b><small>Breadcrumb, deteksi tiba di toko</small></span>
                    <span class="chip-st sim">Simulasi</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Snap-to-Road &amp; geofence</span></div>
                    <p class="hint">DC Cakung → 3 toko. Titik GPS mentah diberi derau ±20 m, lalu ditarik ke ruas jalan.</p>
                    <div class="row">
                        <span>Radius geofence</span>
                        <input type="range" id="trkR" min="50" max="300" step="10" value="150">
                        <b id="trkRv">150 m</b>
                    </div>
                    <div class="row"><button class="btn" id="trkGo" type="button"><i class="bi bi-play-fill"></i> Mulai tracking</button></div>
                    <div class="kpis">
                        <div class="kpi"><b id="trkPts">0</b><span>Titik GPS</span></div>
                        <div class="kpi"><b id="trkOff">–</b><span>Koreksi rata-rata</span></div>
                        <div class="kpi"><b id="trkVis">0/3</b><span>Toko dikunjungi</span></div>
                    </div>
                    <div class="legend">
                        <span><i class="dot" style="background:var(--red)"></i>GPS mentah</span>
                        <span><i style="background:#2563eb"></i>Jejak setelah snap</span>
                        <span><i style="background:rgba(0,177,79,.4)"></i>Geofence toko</span>
                    </div>
                    <ul class="log" id="trkLog"></ul>
                    <p class="note">
                        Bagian ini <b>simulasi di browser</b>: derau GPS, penarikan titik ke ruas jalan, dan deteksi masuk/keluar geofence dihitung di halaman ini
                        di atas geometri rute dari Routes API. Belum memakai layanan snap-to-road atau geofencing terkelola.
                    </p>
                </div>
            </section>

            {{-- 8. Preferensi rute --}}
            <section class="mod" data-mod="pref">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-sliders"></i></span>
                    <span class="mod-tt"><b>Preferensi rute</b><small>Hindari tol, ferry, jalan bebas hambatan</small></span>
                    <span class="chip-st live">Live API</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Preferensi rute</span></div>
                    <div class="chips" id="prefCase">
                        <button type="button" data-case="tol" class="on">Cakung → Tangerang</button>
                        <button type="button" data-case="ferry">Surabaya → Denpasar</button>
                    </div>
                    <label class="sw"><input type="checkbox" id="pfToll"> Hindari jalan tol</label>
                    <label class="sw"><input type="checkbox" id="pfHwy"> Hindari jalan bebas hambatan</label>
                    <label class="sw"><input type="checkbox" id="pfFerry"> Hindari ferry</label>
                    <div id="prefOut"></div>
                    <div class="legend">
                        <span><i style="background:var(--g)"></i>Rute sesuai preferensi</span>
                        <span><i style="background:#94a3b8"></i>Rute tanpa batasan</span>
                    </div>
                </div>
            </section>

            {{-- 9. Cakupan --}}
            <section class="mod" data-mod="cov">
                <button class="mod-head" type="button">
                    <span class="mod-ic"><i class="bi bi-globe-asia-australia"></i></span>
                    <span class="mod-tt"><b>Cakupan Indonesia</b><small>Wilayah operasi Wings</small></span>
                    <span class="chip-st live">Live API</span>
                </button>
                <div class="mod-body">
                    <div class="needs"><span>Cakupan Indonesia</span></div>
                    <p class="hint">Satu rute dalam kota dihitung di tiap kota, dari Medan sampai Jayapura. Klik baris untuk melihat rutenya.</p>
                    <div class="row"><button class="btn" id="covGo" type="button"><i class="bi bi-check2-all"></i> Uji 14 kota</button></div>
                    <div id="covSum"></div>
                    <table class="t" id="covTable">
                        <thead><tr><th>Kota</th><th>km</th><th>mnt</th><th>ms</th><th>Status</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <p class="hint" style="text-align:center;margin:12px 0 4px">Lokasi DC dan toko pada demo ini adalah data contoh.</p>
        </div>
    </aside>

    <div class="phone" id="phone" hidden>
        <div class="ph-screen">
            <div id="phoneMap"></div>
            <div class="ph-top">
                <i class="bi bi-arrow-up" id="phIcon"></i>
                <div><b id="phDist">–</b><span id="phText">Menyiapkan rute…</span></div>
            </div>
            <div class="ph-next" id="phNext" hidden></div>
            <div class="ph-bottom">
                <div><b id="phEta">–</b><span>Tiba</span></div>
                <div><b id="phRem">–</b><span>Sisa</span></div>
                <div><b id="phMin">–</b><span>Menit</span></div>
            </div>
        </div>
        <div class="ph-tag">Mockup tampilan aplikasi pengemudi</div>
    </div>

    <aside class="info-panel" id="info" hidden>
        <header class="info-head">
            <span class="mod-ic"><i class="bi bi-info-lg"></i></span>
            <div style="flex:1;min-width:0">
                <h2 id="infoTitle">Info</h2>
                <p>Sumber data, cara hitung, dan panggilan API</p>
            </div>
            <button class="info-x" id="infoClose" type="button" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </header>
        <div class="info-body" id="infoBody"></div>
    </aside>

    <div class="toast" id="toast"></div>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    <script>
        (() => {
            'use strict';

            /* =========================================
               DATA CONTOH
               ========================================= */
            const DEPOT = { name: 'DC Cakung', pos: [106.9370, -6.1830] };
            const STORES = [
                { name: 'Toko Sumber Rejeki', area: 'Pulogadung', pos: [106.9005, -6.1905] },
                { name: 'Toko Berkah Jaya', area: 'Rawamangun', pos: [106.8830, -6.1975] },
                { name: 'Warung Bu Sari', area: 'Cempaka Putih', pos: [106.8690, -6.1790] },
                { name: 'Toko Maju Makmur', area: 'Kelapa Gading', pos: [106.9050, -6.1590] },
                { name: 'Minimarket Sinar', area: 'Sunter', pos: [106.8700, -6.1420] },
                { name: 'Toko Harapan Baru', area: 'Tanjung Priok', pos: [106.8850, -6.1180] },
                { name: 'Toko Cahaya', area: 'Klender', pos: [106.9040, -6.2130] },
                { name: 'Toko Sejahtera', area: 'Duren Sawit', pos: [106.9200, -6.2290] },
                { name: 'Grosir Amanah', area: 'Jatinegara', pos: [106.8700, -6.2150] },
                { name: 'Toko Mitra Usaha', area: 'Pondok Kopi', pos: [106.9420, -6.2250] },
                { name: 'Toko Laris Manis', area: 'Bekasi Barat', pos: [106.9750, -6.2350] },
                { name: 'Toko Dua Saudara', area: 'Harapan Indah', pos: [106.9800, -6.1850] },
            ];
            const FAR = { name: 'Toko Sentosa', area: 'Tangerang', pos: [106.6300, -6.1780] };
            const CITIES = [
                ['Medan', [98.6722, 3.5952], [98.6800, 3.6400]],
                ['Pekanbaru', [101.4478, 0.5071], [101.4200, 0.4700]],
                ['Palembang', [104.7458, -2.9909], [104.7800, -2.9500]],
                ['Jakarta', [106.9370, -6.1830], [106.8456, -6.2088]],
                ['Bandung', [107.6191, -6.9175], [107.6500, -6.9500]],
                ['Semarang', [110.4203, -6.9932], [110.4500, -7.0300]],
                ['Surabaya', [112.7378, -7.2459], [112.7800, -7.3100]],
                ['Denpasar', [115.2167, -8.6500], [115.1700, -8.7200]],
                ['Pontianak', [109.3425, -0.0263], [109.3300, -0.0700]],
                ['Balikpapan', [116.8312, -1.2654], [116.8900, -1.2300]],
                ['Makassar', [119.4327, -5.1477], [119.4900, -5.1300]],
                ['Manado', [124.8421, 1.4748], [124.8800, 1.4500]],
                ['Ambon', [128.1814, -3.6954], [128.2100, -3.6600]],
                ['Jayapura', [140.7181, -2.5337], [140.6700, -2.5900]],
            ];

            /* =========================================
               UTIL
               ========================================= */
            const $ = (s, r = document) => r.querySelector(s);
            const $$ = (s, r = document) => [...r.querySelectorAll(s)];
            const CSRF = $('meta[name="csrf-token"]').content;
            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const nf = (n, d = 1) => Number(n).toLocaleString('id-ID', { maximumFractionDigits: d, minimumFractionDigits: 0 });
            const fmtKm = (m) => m >= 1000 ? nf(m / 1000) + ' km' : Math.round(m / 10) * 10 + ' m';
            const fmtMin = (s) => {
                const m = Math.round(s / 60);
                return m >= 60 ? Math.floor(m / 60) + ' j ' + (m % 60) + ' mnt' : m + ' mnt';
            };
            const clock = (sec) => {
                const t = Math.floor(sec) % 86400;
                const p = (n) => String(n).padStart(2, '0');
                return p(Math.floor(t / 3600)) + ':' + p(Math.floor(t / 60) % 60) + ':' + p(t % 60);
            };
            const isMobile = () => window.matchMedia('(max-width: 820px)').matches;

            let toastTimer = 0;
            function toast(msg, err = false) {
                const el = $('#toast');
                el.textContent = msg;
                el.classList.toggle('err', err);
                el.classList.add('show');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => el.classList.remove('show'), err ? 5000 : 2800);
            }

            // Jalankan aksi tombol: kunci tombol, tampilkan spinner, tangkap error.
            async function run(btn, fn) {
                if (btn.disabled) return;
                const html = btn.innerHTML;
                btn.disabled = true;
                btn.insertAdjacentHTML('afterbegin', '<span class="spin"></span>');
                btn.querySelector('i')?.setAttribute('hidden', '');
                let keepDisabled = false;
                try {
                    keepDisabled = (await fn()) === 'keep-disabled';
                } catch (e) {
                    console.error(e);
                    toast(e.message || 'Terjadi kesalahan', true);
                } finally {
                    btn.innerHTML = html;
                    btn.disabled = keepDisabled;
                }
            }

            /* =========================================
               GEOMETRI
               ========================================= */
            const rad = (d) => d * Math.PI / 180;

            function hav(a, b) {
                const dLat = rad(b[1] - a[1]), dLng = rad(b[0] - a[0]);
                const h = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a[1])) * Math.cos(rad(b[1])) * Math.sin(dLng / 2) ** 2;
                return 2 * 6371000 * Math.asin(Math.sqrt(h));
            }

            function bearing(a, b) {
                const y = Math.sin(rad(b[0] - a[0])) * Math.cos(rad(b[1]));
                const x = Math.cos(rad(a[1])) * Math.sin(rad(b[1])) - Math.sin(rad(a[1])) * Math.cos(rad(b[1])) * Math.cos(rad(b[0] - a[0]));
                return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
            }

            // Geser titik sejauh `m` meter ke arah `brg` derajat.
            function offset(p, brg, m) {
                const dx = m * Math.sin(rad(brg)), dy = m * Math.cos(rad(brg));
                return [p[0] + dx / (111320 * Math.cos(rad(p[1]))), p[1] + dy / 110540];
            }

            function circle(c, r, n = 48) {
                const out = [];
                for (let i = 0; i <= n; i++) out.push(offset(c, i * 360 / n, r));
                return out;
            }

            // Gabungkan semua leg jadi satu garis + daftar langkah dengan posisi (meter) di sepanjang garis.
            function flatten(route) {
                const coords = [], cum = [0], steps = [], legEnd = [];
                for (const leg of route.Legs || []) {
                    const legStart = cum[cum.length - 1];
                    for (const p of leg.Geometry?.LineString || []) {
                        const last = coords[coords.length - 1];
                        if (last && last[0] === p[0] && last[1] === p[1]) continue;
                        if (last) cum.push(cum[cum.length - 1] + hav(last, p));
                        coords.push(p);
                    }
                    const legLen = cum[cum.length - 1] - legStart;
                    const det = leg.VehicleLegDetails || leg.PedestrianLegDetails || {};
                    const raw = det.TravelSteps || [];
                    const sum = raw.reduce((a, s) => a + (s.Distance || 0), 0) || 1;
                    let acc = 0;
                    for (const s of raw) {
                        const det2 = s.TurnStepDetails || s.KeepStepDetails || s.RampStepDetails || s.ExitStepDetails || s.EnterHighwayStepDetails || {};
                        const st = {
                            type: s.Type,
                            dir: det2.SteeringDirection || null,
                            intensity: det2.TurnIntensity || null,
                            road: s.CurrentRoad?.RoadName?.[0]?.Value || null,
                            next: s.NextRoad?.RoadName?.[0]?.Value || null,
                            dist: s.Distance || 0,
                            dur: s.Duration || 0,
                            start: legStart + acc / sum * legLen,
                        };
                        acc += st.dist;
                        st.end = legStart + acc / sum * legLen;
                        steps.push(st);
                    }
                    legEnd.push(cum[cum.length - 1]);
                }
                const total = cum[cum.length - 1];
                return {
                    coords, cum, steps, legEnd, total,
                    km: (route.Summary?.Distance ?? total) / 1000,
                    sec: route.Summary?.Duration ?? steps.reduce((a, s) => a + s.dur, 0),
                };
            }

            function pointAt(f, d) {
                d = Math.max(0, Math.min(f.total, d));
                let lo = 1, hi = f.cum.length - 1;
                while (lo < hi) {
                    const m = (lo + hi) >> 1;
                    if (f.cum[m] < d) lo = m + 1; else hi = m;
                }
                const a = f.coords[lo - 1], b = f.coords[lo];
                const seg = f.cum[lo] - f.cum[lo - 1];
                const t = seg ? (d - f.cum[lo - 1]) / seg : 0;
                return { pos: [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t], brg: bearing(a, b) };
            }

            // Titik terdekat pada garis; d0–d1 membatasi pencarian (meter sepanjang garis).
            function nearest(f, p, d0 = 0, d1 = Infinity) {
                const kx = 111320 * Math.cos(rad(p[1])), ky = 110540;
                let best = { dist: Infinity, pos: p, along: 0 };
                for (let i = 1; i < f.coords.length; i++) {
                    if (f.cum[i] < d0) continue;
                    if (f.cum[i - 1] > d1) break;
                    const a = f.coords[i - 1], b = f.coords[i];
                    const ax = (a[0] - p[0]) * kx, ay = (a[1] - p[1]) * ky;
                    const dx = (b[0] - a[0]) * kx, dy = (b[1] - a[1]) * ky;
                    const L = dx * dx + dy * dy;
                    const t = L ? Math.max(0, Math.min(1, -(ax * dx + ay * dy) / L)) : 0;
                    const dist = Math.hypot(ax + dx * t, ay + dy * t);
                    if (dist < best.dist) {
                        best = {
                            dist,
                            pos: [a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t],
                            along: f.cum[i - 1] + (f.cum[i] - f.cum[i - 1]) * t,
                        };
                    }
                }
                return best;
            }

            function slice(f, d0, d1) {
                const out = [pointAt(f, d0).pos];
                for (let i = 0; i < f.coords.length; i++) {
                    if (f.cum[i] > d0 && f.cum[i] < d1) out.push(f.coords[i]);
                }
                out.push(pointAt(f, d1).pos);
                return out;
            }

            function stepIndex(f, d) {
                for (let i = 0; i < f.steps.length; i++) if (d < f.steps[i].end) return i;
                return f.steps.length - 1;
            }

            function remainSec(f, d) {
                let s = 0;
                for (const st of f.steps) {
                    if (st.end <= d) continue;
                    const len = st.end - st.start || 1;
                    s += st.dur * Math.min(1, (st.end - d) / len);
                }
                return s;
            }

            /* =========================================
               API (lewat proxy Laravel — key tetap di server)
               ========================================= */
            const REGION = '{{ config('services.aws.region') }}';
            // Proxy meneruskan body apa adanya ke endpoint AWS ini dan mengembalikan balasannya tanpa diubah.
            const AWS = {
                '/api/v2/routes/calculate': { op: 'CalculateRoutes', url: `https://routes.geo.${REGION}.amazonaws.com/v2/routes` },
                '/api/v2/routes/matrix': { op: 'CalculateRouteMatrix', url: `https://routes.geo.${REGION}.amazonaws.com/v2/route-matrix` },
            };
            const meter = { n: 0, total: 0 };
            const calls = []; // riwayat panggilan untuk panel info, terbaru di depan
            let callId = 0;

            async function post(url, body) {
                const mod = active;
                const t = performance.now();
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify(body),
                });
                const ms = Math.round(performance.now() - t);
                meter.n++;
                meter.total += ms;
                $('#mReq').textContent = meter.n;
                $('#mAvg').textContent = Math.round(meter.total / meter.n);
                $('#mLast').textContent = ms;

                let data = null;
                try { data = await res.json(); } catch (e) { /* bukan JSON */ }
                calls.unshift({ id: ++callId, mod, at: new Date(), proxy: url, req: body, res: data, status: res.status, ms });
                if (calls.length > 80) calls.length = 80;
                renderInfo();
                if (!res.ok) {
                    const err = new Error(data?.message || data?.error || ('HTTP ' + res.status));
                    err.status = res.status;
                    throw err;
                }
                return { data, ms };
            }

            async function calcRoutes(origin, dest, opt = {}) {
                const body = {
                    Origin: origin,
                    Destination: dest,
                    TravelMode: 'Car',
                    LegGeometryFormat: 'Simple',
                    TravelStepType: 'TurnByTurn',
                };
                if (opt.waypoints?.length) body.Waypoints = opt.waypoints.map((p) => ({ Position: p }));
                if (opt.alternatives) body.MaxAlternatives = opt.alternatives;
                if (opt.avoid && Object.keys(opt.avoid).length) body.Avoid = opt.avoid;
                if (opt.departure) body.DepartureTime = opt.departure;

                const { data, ms } = await post('/api/v2/routes/calculate', body);
                const routes = (data?.Routes || []).map(flatten).filter((f) => f.coords.length > 1);
                if (!routes.length) throw new Error('Rute tidak ditemukan');
                routes.ms = ms;
                return routes;
            }

            async function calcMatrix(points) {
                const pts = points.map((p) => ({ Position: p }));
                const { data, ms } = await post('/api/v2/routes/matrix', {
                    Origins: pts,
                    Destinations: pts,
                    TravelMode: 'Car',
                    RoutingBoundary: { Unbounded: true },
                });
                const m = (data?.RouteMatrix || []).map((row) => row.map((c) => c.Error ? null : { km: c.Distance / 1000, s: c.Duration }));
                if (!m.length) throw new Error('Matrix kosong');
                return { m, ms };
            }

            /* =========================================
               PETA
               ========================================= */
            const map = new maplibregl.Map({
                container: 'map',
                style: '/api/v2/map-style?style=Standard&color=Light',
                center: [106.91, -6.185],
                zoom: 11.2,
                attributionControl: false,
            });
            map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
            map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');

            const SOURCES = ['fence', 'alt', 'old', 'main', 'speed', 'link', 'trail', 'raw'];
            const fc = (features) => ({ type: 'FeatureCollection', features });
            const line = (coords, props = {}) => ({ type: 'Feature', properties: props, geometry: { type: 'LineString', coordinates: coords } });
            const pt = (pos, props = {}) => ({ type: 'Feature', properties: props, geometry: { type: 'Point', coordinates: pos } });
            const poly = (ring, props = {}) => ({ type: 'Feature', properties: props, geometry: { type: 'Polygon', coordinates: [ring] } });

            function setData(id, features) {
                map.getSource(id)?.setData(fc(features));
            }

            function addLayers() {
                for (const id of SOURCES) map.addSource(id, { type: 'geojson', data: fc([]) });
                const round = { 'line-cap': 'round', 'line-join': 'round' };
                map.addLayer({ id: 'fence-fill', type: 'fill', source: 'fence', paint: { 'fill-color': ['case', ['get', 'in'], '#00B14F', '#00B14F'], 'fill-opacity': ['case', ['get', 'in'], 0.35, 0.14] } });
                map.addLayer({ id: 'fence-line', type: 'line', source: 'fence', paint: { 'line-color': '#00873c', 'line-width': 1.5, 'line-dasharray': [2, 2] } });
                map.addLayer({ id: 'alt-line', type: 'line', source: 'alt', layout: round, paint: { 'line-color': '#94a3b8', 'line-width': 4, 'line-opacity': 0.85 } });
                map.addLayer({ id: 'old-line', type: 'line', source: 'old', layout: round, paint: { 'line-color': '#dc2626', 'line-width': 4, 'line-dasharray': [1, 1.6] } });
                map.addLayer({ id: 'main-case', type: 'line', source: 'main', layout: round, paint: { 'line-color': '#ffffff', 'line-width': 9 } });
                map.addLayer({ id: 'main-line', type: 'line', source: 'main', layout: round, paint: { 'line-color': '#00B14F', 'line-width': 5 } });
                map.addLayer({ id: 'speed-line', type: 'line', source: 'speed', layout: round, paint: { 'line-color': ['get', 'c'], 'line-width': 5 } });
                map.addLayer({ id: 'link-line', type: 'line', source: 'link', paint: { 'line-color': '#dc2626', 'line-width': 1, 'line-opacity': 0.6 } });
                map.addLayer({ id: 'trail-line', type: 'line', source: 'trail', layout: round, paint: { 'line-color': '#2563eb', 'line-width': 4 } });
                map.addLayer({ id: 'raw-pt', type: 'circle', source: 'raw', paint: { 'circle-radius': 3.5, 'circle-color': '#dc2626', 'circle-stroke-color': '#fff', 'circle-stroke-width': 1 } });
            }

            let markers = [];
            function mk(pos, html, cls = '', title = '') {
                const el = document.createElement('div');
                el.className = 'mk ' + cls;
                el.innerHTML = html;
                if (title) el.title = title;
                const m = new maplibregl.Marker({ element: el }).setLngLat(pos).addTo(map);
                markers.push(m);
                return m;
            }

            function depotMarker() {
                return mk(DEPOT.pos, '<i class="bi bi-building"></i>', 'depot', DEPOT.name);
            }

            const truckEl = document.createElement('div');
            truckEl.className = 'truck';
            truckEl.innerHTML = '<i class="bi bi-truck-front-fill"></i>';
            const truck = new maplibregl.Marker({ element: truckEl });

            function fit(coords, opt = {}) {
                if (!coords.length) return;
                const b = new maplibregl.LngLatBounds(coords[0], coords[0]);
                for (const c of coords) b.extend(c);
                const mobile = isMobile();
                map.fitBounds(b, {
                    padding: mobile
                        ? { top: 60, left: 40, right: 40, bottom: window.innerHeight * 0.55 + 20 }
                        : { top: 70, bottom: 70, left: $('#side').offsetWidth + 60, right: $('#info').hidden ? (opt.right ?? 70) : 520 },
                    duration: 700,
                    maxZoom: opt.maxZoom ?? 15,
                });
            }

            /* =========================================
               SIMULASI PERJALANAN
               ========================================= */
            let trip = null;
            let epoch = 0; // naik tiap ganti modul — hasil async yang terlambat dibuang

            function stopTrip() {
                if (trip) cancelAnimationFrame(trip.raf);
                trip = null;
            }

            function startTrip(f, { mult = 30, onMove, onDone } = {}) {
                stopTrip();
                const st = { f, d: 0, t: 0, mult, paused: false, last: performance.now(), raf: 0, onMove, onDone };
                truck.setLngLat(f.coords[0]).addTo(map);
                const tick = (now) => {
                    const dt = Math.min(0.1, (now - st.last) / 1000);
                    st.last = now;
                    if (!st.paused) {
                        const s = f.steps[stepIndex(f, st.d)];
                        const v = s && s.dur > 0 ? Math.max(3, s.dist / s.dur) : 8; // m/dtk dari langkah API
                        st.d = Math.min(f.total, st.d + v * dt * st.mult);
                        st.t += dt * st.mult;
                        const p = pointAt(f, st.d);
                        truck.setLngLat(p.pos);
                        st.onMove?.(p, st);
                        if (st.d >= f.total) {
                            trip = null;
                            st.onDone?.(st);
                            return;
                        }
                    }
                    st.raf = requestAnimationFrame(tick);
                };
                st.raf = requestAnimationFrame(tick);
                trip = st;
                return st;
            }

            function clearAll() {
                epoch++;
                stopTrip();
                truck.remove();
                markers.forEach((m) => m.remove());
                markers = [];
                for (const id of SOURCES) setData(id, []);
                $('#phone').hidden = true;
            }

            /* =========================================
               1. TRUK
               ========================================= */
            const truckMod = {
                routes: [],
                enter() {
                    $('#truckOut').innerHTML = '';
                    this.routes = [];
                    depotMarker();
                    this.destMarker();
                    fit([DEPOT.pos, this.dest().pos]);
                },
                dest() { return STORES[+$('#truckDest').value]; },
                destMarker() {
                    const d = this.dest();
                    mk(d.pos, '<i class="bi bi-shop"></i>', '', d.name);
                },
                klass() {
                    const h = +$('#tH').value, w = +$('#tW').value, a = +$('#tA').value;
                    if (w > 12 || h > 3.2 || a >= 3) return { k: 1.5, label: 'kendaraan berat', h, w, a };
                    if (w > 5 || h > 2.4) return { k: 0.8, label: 'kendaraan sedang', h, w, a };
                    return { k: 0.3, label: 'kendaraan ringan', h, w, a };
                },
                analyze(f, k) {
                    let turns = 0, tight = 0, uturn = 0;
                    for (const s of f.steps) {
                        if (s.type === 'Turn') {
                            turns++;
                            if (s.dist < 150 || s.intensity === 'Sharp') tight++;
                        } else if (s.type === 'UTurn') uturn++;
                    }
                    const penalty = k * (turns * 0.5 + tight + uturn * 3); // menit
                    return { turns, tight, uturn, penalty, score: f.sec / 60 + penalty };
                },
                async go() {
                    const e = epoch, d = this.dest(), kl = this.klass();
                    const routes = await calcRoutes(DEPOT.pos, d.pos, { alternatives: 5 });
                    if (e !== epoch) return;
                    this.routes = routes.map((f) => ({ f, ...this.analyze(f, kl.k) }));
                    const fastest = this.routes.reduce((a, b) => b.f.sec < a.f.sec ? b : a);
                    const best = this.routes.reduce((a, b) => b.score < a.score ? b : a);
                    this.routes.forEach((r) => { r.fast = r === fastest; r.best = r === best; });
                    this.routes.sort((a, b) => a.score - b.score);
                    this.kl = kl;
                    this.pick(0);
                    fit(routes.flatMap((f) => f.coords));
                },
                pick(i) {
                    this.sel = i;
                    setData('alt', this.routes.filter((_, j) => j !== i).map((r) => line(r.f.coords)));
                    setData('main', [line(this.routes[i].f.coords)]);
                    const rows = this.routes.map((r, j) => `
                        <tr data-i="${j}" class="${j === i ? 'pick' : ''}">
                            <td>Rute ${String.fromCharCode(65 + j)}${r.best ? '<span class="tag g">Truk</span>' : ''}${r.fast ? '<span class="tag s">Tercepat</span>' : ''}</td>
                            <td>${nf(r.f.km)}</td><td>${Math.round(r.f.sec / 60)}</td>
                            <td>${r.turns}</td><td>${r.tight}</td><td>${nf(r.score, 0)}</td>
                        </tr>`).join('');
                    const best = this.routes.find((r) => r.best), fast = this.routes.find((r) => r.fast);
                    const msg = best === fast
                        ? 'Rute tercepat juga yang paling sedikit manuvernya — aman untuk ' + this.kl.label + '.'
                        : `Untuk ${this.kl.label}, dipilih rute yang ${best.f.sec - fast.f.sec < 45 ? 'nyaris sama cepat dengan' : Math.round((best.f.sec - fast.f.sec) / 60) + ' mnt lebih lama dari'} rute tercepat tetapi lebih sedikit manuver (belokan ${best.turns} vs ${fast.turns}, belokan rapat ${best.tight} vs ${fast.tight}).`;
                    $('#truckOut').innerHTML = `
                        <table class="t">
                            <thead><tr><th>Alternatif</th><th>km</th><th>mnt</th><th>Belok</th><th>Rapat</th><th>Skor</th></tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                        <p class="hint">${esc(msg)} Skor = menit + penalti manuver; makin kecil makin cocok.</p>`;
                    renderInfo();
                },
            };
            $('#truckDest').innerHTML = STORES.map((s, i) => `<option value="${i}">${esc(s.name)} — ${esc(s.area)}</option>`).join('');
            $('#truckDest').value = '8';
            $('#truckDest').addEventListener('change', () => activate('truck', true));
            $('#truckPresets').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (!b) return;
                $$('#truckPresets button').forEach((x) => x.classList.toggle('on', x === b));
                $('#tH').value = b.dataset.h;
                $('#tW').value = b.dataset.w;
                $('#tA').value = b.dataset.a;
            });
            ['tH', 'tW', 'tA'].forEach((id) => $('#' + id).addEventListener('input', () => $$('#truckPresets button').forEach((x) => x.classList.remove('on'))));
            $('#truckGo').addEventListener('click', (ev) => run(ev.currentTarget, () => truckMod.go()));
            $('#truckOut').addEventListener('click', (ev) => {
                const tr = ev.target.closest('tr[data-i]');
                if (tr) truckMod.pick(+tr.dataset.i);
            });

            /* =========================================
               2. TRAFFIC
               ========================================= */
            const trafMod = {
                slots: ['03:00', '07:30', '12:00', '17:30', '21:00'],
                res: [],
                enter() {
                    depotMarker();
                    mk(FAR.pos, '<i class="bi bi-shop"></i>', '', FAR.name);
                    fit([DEPOT.pos, FAR.pos]);
                    if (this.res.length) this.show(this.sel);
                },
                async go() {
                    const e = epoch;
                    // Besok, zona WIB — DepartureTime harus di masa depan.
                    const day = new Date(Date.now() + (7 + 24) * 3600e3).toISOString().slice(0, 10);
                    const res = await Promise.all(this.slots.map((t) => calcRoutes(DEPOT.pos, FAR.pos, { departure: `${day}T${t}:00+07:00` }).then((r) => r[0])));
                    if (e !== epoch) return;
                    this.res = res;
                    let peak = 0;
                    res.forEach((f, i) => { if (f.sec > res[peak].sec) peak = i; });
                    this.show(peak);
                },
                show(i) {
                    this.sel = i;
                    const res = this.res, max = Math.max(...res.map((f) => f.sec)), min = Math.min(...res.map((f) => f.sec));
                    const bars = $('#trafBars');
                    bars.hidden = false;
                    $('#trafLegend').hidden = false;
                    bars.innerHTML = res.map((f, j) => `
                        <button type="button" class="bar ${j === i ? 'on' : ''}" data-i="${j}">
                            <b>${Math.round(f.sec / 60)}′</b><i style="height:${Math.round(f.sec / max * 78)}%"></i><span>${this.slots[j]}</span>
                        </button>`).join('');

                    const f = res[i];
                    const col = (kmh) => kmh > 50 ? '#16a34a' : kmh > 30 ? '#eab308' : kmh > 15 ? '#f97316' : '#dc2626';
                    setData('main', [line(f.coords)]);
                    setData('speed', f.steps.filter((s) => s.end - s.start > 1 && s.dur > 0).map((s) => line(slice(f, s.start, s.end), { c: col(s.dist / s.dur * 3.6) })));
                    const slowM = f.steps.filter((s) => s.dur > 0 && s.dist / s.dur * 3.6 <= 15).reduce((a, s) => a + s.dist, 0);
                    $('#trafOut').innerHTML = `
                        <div class="kpis">
                            <div class="kpi"><b>${fmtMin(f.sec)}</b><span>ETA berangkat ${this.slots[i]}</span>${f.sec > min ? `<small class="up">+${Math.round((f.sec - min) / 60)} mnt vs jam tersepi</small>` : '<small>Jam tersepi</small>'}</div>
                            <div class="kpi"><b>${nf(f.km / (f.sec / 3600), 0)} km/j</b><span>Kecepatan rata-rata</span></div>
                            <div class="kpi"><b>${nf(slowM / 1000)} km</b><span>Ruas padat (&lt; 15 km/j)</span></div>
                        </div>
                        <p class="hint">Jarak ${nf(f.km)} km. Selisih jam tersibuk dan tersepi: <b>${Math.round((max - min) / 60)} mnt</b>.</p>`;
                },
            };
            $('#trafGo').addEventListener('click', (ev) => run(ev.currentTarget, () => trafMod.go()));
            $('#trafBars').addEventListener('click', (ev) => {
                const b = ev.target.closest('.bar');
                if (b) trafMod.show(+b.dataset.i);
            });

            /* =========================================
               3. INSIDEN & RE-ROUTING
               ========================================= */
            const rrMod = {
                dest: STORES[5],
                incidents: [],
                busy: false,
                enter() {
                    depotMarker();
                    mk(this.dest.pos, '<i class="bi bi-shop"></i>', '', this.dest.name);
                    fit([DEPOT.pos, this.dest.pos]);
                    this.incidents = [];
                    $('#rrLog').innerHTML = '';
                    $('#rrKpis').hidden = true;
                    $('#rrBlock').disabled = true;
                    $('#rrStart').disabled = false;
                },
                log(msg, cls = '') {
                    const t = trip ? clock(8 * 3600 + (this.t0 || 0) + trip.t) : clock(8 * 3600 + (this.t0 || 0));
                    $('#rrLog').insertAdjacentHTML('afterbegin', `<li class="${cls}"><time>${t}</time>${msg}</li>`);
                },
                drive(f) {
                    setData('main', [line(f.coords)]);
                    startTrip(f, {
                        mult: 25,
                        onMove: (p, st) => {
                            $('#rrEta').textContent = fmtMin(remainSec(f, st.d));
                            $('#rrRem').textContent = fmtKm(f.total - st.d);
                        },
                        onDone: (st) => {
                            this.t0 += st.t;
                            this.log('Tiba di ' + esc(this.dest.name) + '.', 'g');
                            $('#rrBlock').disabled = true;
                            $('#rrStart').disabled = false;
                        },
                    });
                },
                async start() {
                    activate('reroute', true);
                    $('#rrStart').disabled = true;
                    const e = epoch;
                    const [f] = await calcRoutes(DEPOT.pos, this.dest.pos);
                    if (e !== epoch) return;
                    this.t0 = 0;
                    fit(f.coords);
                    $('#rrKpis').hidden = false;
                    $('#rrBlock').disabled = false;
                    this.drive(f);
                    this.log(`Berangkat dari ${esc(DEPOT.name)} — ${nf(f.km)} km, ${fmtMin(f.sec)}.`, 'b');
                    return 'keep-disabled';
                },
                clear(f) {
                    return this.incidents.every((inc) => nearest(f, inc).dist > 250);
                },
                blockAhead() {
                    if (!trip) return;
                    const left = trip.f.total - trip.d;
                    if (left < 2000) return toast('Truk sudah terlalu dekat dengan tujuan.');
                    return this.block(pointAt(trip.f, trip.d + Math.max(900, left * 0.35)).pos);
                },
                async block(pos) {
                    if (!trip || this.busy) return;
                    this.busy = true;
                    const e = epoch, st = trip, old = st.f;
                    st.paused = true;
                    try {
                        const el = document.createElement('div');
                        el.className = 'incident';
                        el.innerHTML = '<i class="bi bi-x-lg"></i>';
                        markers.push(new maplibregl.Marker({ element: el }).setLngLat(pos).addTo(map));
                        this.incidents.push(pos);
                        const ahead = nearest(old, pos, st.d).along - st.d;
                        this.log(`Penutupan jalan terdeteksi ${fmtKm(ahead)} di depan. Menghitung rute ulang…`, 'r');

                        const cur = pointAt(old, st.d).pos;
                        const t0 = performance.now();
                        let cands = (await calcRoutes(cur, this.dest.pos, { alternatives: 5 })).filter((f) => this.clear(f));
                        // Belum ada yang bebas: paksa memutar lewat titik bantu di kiri/kanan lokasi tertutup.
                        if (!cands.length) {
                            const brg = pointAt(old, nearest(old, pos, st.d).along).brg;
                            for (const m of [800, 1500]) {
                                const tries = await Promise.allSettled([90, -90].map((side) => calcRoutes(cur, this.dest.pos, { waypoints: [offset(pos, brg + side, m)] })));
                                cands = tries.filter((r) => r.status === 'fulfilled').map((r) => r.value[0]).filter((f) => this.clear(f));
                                if (cands.length) break;
                            }
                        }
                        if (e !== epoch) return;
                        const ms = Math.round(performance.now() - t0);

                        if (!cands.length) {
                            this.log('Tidak ada rute pengganti yang bebas dari titik tertutup. Melanjutkan rute semula.', 'r');
                            this.incidents.pop();
                            st.paused = false;
                            return;
                        }
                        const f = cands.reduce((a, b) => b.sec < a.sec ? b : a);
                        const oldSec = remainSec(old, st.d), oldM = old.total - st.d;
                        const dMin = Math.round((f.sec - oldSec) / 60), dKm = (f.total - oldM) / 1000;
                        setData('old', [line(slice(old, st.d, old.total))]);
                        this.t0 += st.t;
                        this.drive(f);
                        fit(f.coords.concat([pos]));
                        this.log(`Rute baru aktif dalam ${ms} ms: ${nf(f.total / 1000)} km, ${fmtMin(f.sec)} (${dMin >= 0 ? '+' : ''}${dMin} mnt, ${dKm >= 0 ? '+' : ''}${nf(dKm)} km dari sisa rute lama).`, 'g');
                    } catch (err) {
                        if (e === epoch) {
                            st.paused = false;
                            this.log('Gagal menghitung rute ulang: ' + esc(err.message), 'r');
                        }
                    } finally {
                        this.busy = false;
                    }
                },
            };
            $('#rrStart').addEventListener('click', (ev) => run(ev.currentTarget, () => rrMod.start()));
            $('#rrBlock').addEventListener('click', () => rrMod.blockAhead());
            map.on('click', (ev) => {
                if (active !== 'reroute' || !trip) return;
                const hit = nearest(trip.f, [ev.lngLat.lng, ev.lngLat.lat], trip.d + 400);
                if (hit.dist > 250) return toast('Klik lebih dekat ke rute di depan truk.');
                if (trip.f.total - hit.along < 300) return toast('Titik terlalu dekat dengan tujuan.');
                rrMod.block(hit.pos);
            });

            /* =========================================
               4. DISTANCE MATRIX
               ========================================= */
            const mxMod = {
                enter() {
                    this.draw();
                },
                n() { return +$('#mxN').value; },
                draw() {
                    markers.forEach((m) => m.remove());
                    markers = [];
                    depotMarker();
                    STORES.slice(0, this.n()).forEach((s, i) => mk(s.pos, i + 1, '', s.name));
                    fit([DEPOT.pos, ...STORES.slice(0, this.n()).map((s) => s.pos)]);
                },
                async go() {
                    const e = epoch, n = this.n();
                    const pts = [DEPOT, ...STORES.slice(0, n)];
                    const { m, ms } = await calcMatrix(pts.map((p) => p.pos));
                    if (e !== epoch) return;
                    const pairs = pts.length ** 2;
                    const vals = m.flat().filter(Boolean).map((c) => c.s).filter((s) => s > 0);
                    const lo = Math.min(...vals), hi = Math.max(...vals);
                    const lab = (i) => i === 0 ? 'D' : i;
                    let html = '<tr><th class="r"></th>' + pts.map((_, i) => `<th>${lab(i)}</th>`).join('') + '</tr>';
                    m.forEach((row, i) => {
                        html += `<tr><th class="r" title="${esc(pts[i].name)}">${lab(i)}</th>` + row.map((c, j) => {
                            if (i === j) return '<td class="z">–</td>';
                            if (!c) return '<td class="bad">!</td>';
                            const t = hi > lo ? (c.s - lo) / (hi - lo) : 0;
                            return `<td style="background:hsl(${Math.round(140 - 140 * t)} 75% 88%)" title="${esc(pts[i].name)} → ${esc(pts[j].name)}: ${fmtMin(c.s)} · ${nf(c.km)} km">${Math.round(c.s / 60)}</td>`;
                        }).join('') + '</tr>';
                    });
                    $('#mxTable').innerHTML = html;
                    $('#mxWrap').hidden = false;
                    $('#mxHint').hidden = false;
                    $('#mxStats').innerHTML = `
                        <div class="kpis">
                            <div class="kpi"><b>${pairs}</b><span>Pasangan asal–tujuan</span></div>
                            <div class="kpi"><b>${ms} ms</b><span>Satu request</span></div>
                            <div class="kpi"><b>${nf(pairs / (ms / 1000), 0)}</b><span>Pasangan / detik</span></div>
                        </div>`;
                },
                async burst() {
                    const e = epoch;
                    const pts = [DEPOT, ...STORES].map((p) => p.pos);
                    const t = performance.now();
                    const res = await Promise.all(Array.from({ length: 5 }, () => calcMatrix(pts)));
                    if (e !== epoch) return;
                    const wall = Math.round(performance.now() - t);
                    const pairs = 5 * pts.length ** 2;
                    const errs = res.reduce((a, r) => a + r.m.flat().filter((c) => !c).length, 0);
                    $('#mxStats').innerHTML = `
                        <div class="kpis">
                            <div class="kpi win"><b>${pairs}</b><span>Pasangan · 5 batch 13×13</span></div>
                            <div class="kpi win"><b>${wall} ms</b><span>Total, paralel</span></div>
                            <div class="kpi win"><b>${nf(pairs / (wall / 1000), 0)}</b><span>Pasangan / detik</span></div>
                        </div>
                        <p class="hint">${errs ? `<span class="bad">${errs} sel gagal.</span>` : '<span class="ok">Semua sel terisi, 0 error.</span>'} Waktu diukur dari browser, termasuk perjalanan lewat server demo.</p>`;
                },
            };
            $('#mxN').addEventListener('input', () => {
                $('#mxNv').textContent = $('#mxN').value;
                if (active === 'matrix') mxMod.draw();
            });
            $('#mxGo').addEventListener('click', (ev) => run(ev.currentTarget, () => mxMod.go()));
            $('#mxBurst').addEventListener('click', (ev) => run(ev.currentTarget, () => mxMod.burst()));

            /* =========================================
               5. OPTIMASI TUR
               ========================================= */
            const tourMod = {
                enter() {
                    this.res = null;
                    $('#tourOut').innerHTML = '';
                    $('#tourOrder').innerHTML = '';
                    $('#tourView').hidden = true;
                    this.draw();
                },
                n() { return +$('#tourN').value; },
                draw() {
                    markers.forEach((m) => m.remove());
                    markers = [];
                    setData('main', []);
                    depotMarker();
                    STORES.slice(0, this.n()).forEach((s, i) => mk(s.pos, i + 1, '', s.name));
                    fit([DEPOT.pos, ...STORES.slice(0, this.n()).map((s) => s.pos)]);
                },
                cost(m, order, closed, key = 's') {
                    let c = 0, prev = 0;
                    for (const i of order) { c += m[prev][i]?.[key] ?? 1e9; prev = i; }
                    if (closed) c += m[prev][0]?.[key] ?? 1e9;
                    return c;
                },
                solve(m, n, closed) {
                    // Nearest-neighbour sebagai awal…
                    const left = new Set(Array.from({ length: n }, (_, i) => i + 1));
                    let order = [], cur = 0;
                    while (left.size) {
                        let b = null, bv = Infinity;
                        for (const j of left) {
                            const v = m[cur][j]?.s ?? 1e9;
                            if (v < bv) { bv = v; b = j; }
                        }
                        order.push(b); left.delete(b); cur = b;
                    }
                    // …lalu 2-opt + pindah-satu-titik sampai tidak ada perbaikan. Matrix tidak simetris, jadi biaya dihitung utuh.
                    let best = this.cost(m, order, closed), improved = true;
                    while (improved) {
                        improved = false;
                        for (let i = 0; i < n - 1; i++) for (let j = i + 1; j < n; j++) {
                            const cand = order.slice(0, i).concat(order.slice(i, j + 1).reverse(), order.slice(j + 1));
                            const c = this.cost(m, cand, closed);
                            if (c < best - 1e-6) { order = cand; best = c; improved = true; }
                        }
                        for (let i = 0; i < n; i++) for (let j = 0; j < n; j++) {
                            if (i === j) continue;
                            const cand = order.slice();
                            const [x] = cand.splice(i, 1);
                            cand.splice(j, 0, x);
                            const c = this.cost(m, cand, closed);
                            if (c < best - 1e-6) { order = cand; best = c; improved = true; }
                        }
                    }
                    return order;
                },
                async go() {
                    const e = epoch, n = this.n(), closed = $('#tourBack').checked;
                    const pts = [DEPOT, ...STORES.slice(0, n)];
                    const { m, ms } = await calcMatrix(pts.map((p) => p.pos));
                    if (e !== epoch) return;
                    const t = performance.now();
                    const base = Array.from({ length: n }, (_, i) => i + 1);
                    const opt = this.solve(m, n, closed);
                    const solveMs = Math.max(1, Math.round(performance.now() - t));
                    this.res = { m, pts, closed, orders: { base, opt }, routes: {}, ms, solveMs };
                    await this.view('opt');
                },
                async view(which) {
                    const e = epoch, r = this.res;
                    if (!r) return;
                    const order = r.orders[which];
                    if (!r.routes[which]) {
                        const stops = order.map((i) => r.pts[i].pos);
                        const dest = r.closed ? DEPOT.pos : stops[stops.length - 1];
                        const wps = r.closed ? stops : stops.slice(0, -1);
                        r.routes[which] = (await calcRoutes(DEPOT.pos, dest, { waypoints: wps }))[0];
                        if (e !== epoch) return;
                    }
                    const f = r.routes[which];
                    markers.forEach((m) => m.remove());
                    markers = [];
                    depotMarker();
                    order.forEach((i, k) => mk(r.pts[i].pos, k + 1, 'seq', r.pts[i].name));
                    setData('main', [line(f.coords)]);
                    fit(f.coords);

                    const c = (o, key) => this.cost(r.m, o, r.closed, key);
                    const bS = c(r.orders.base, 's'), oS = c(r.orders.opt, 's'), bK = c(r.orders.base, 'km'), oK = c(r.orders.opt, 'km');
                    $('#tourOut').innerHTML = `
                        <div class="kpis two">
                            <div class="kpi"><b>${fmtMin(bS)}</b><span>Urutan awal · ${nf(bK)} km</span></div>
                            <div class="kpi win"><b>${fmtMin(oS)}</b><span>Optimal · ${nf(oK)} km</span>
                                <small>Hemat ${Math.round((bS - oS) / 60)} mnt (${nf((1 - oS / bS) * 100, 0)}%) · ${nf(bK - oK)} km</small></div>
                        </div>
                        <p class="hint">Matrix ${r.pts.length}×${r.pts.length} dalam ${r.ms} ms, solver ${r.solveMs} ms. Waktu berkendara saja, belum termasuk bongkar muat.</p>`;
                    $('#tourView').hidden = false;
                    $$('#tourView button').forEach((b) => b.classList.toggle('on', b.dataset.view === which));
                    let acc = 0, prev = 0;
                    $('#tourOrder').innerHTML = order.map((i, k) => {
                        acc += r.m[prev][i]?.s ?? 0;
                        prev = i;
                        return `<li><b>${k + 1}</b><span>${esc(r.pts[i].name)} <span style="color:var(--mut)">· ${esc(r.pts[i].area)}</span></span><em>+${fmtMin(acc)}</em></li>`;
                    }).join('');
                },
            };
            $('#tourN').addEventListener('input', () => {
                $('#tourNv').textContent = $('#tourN').value;
                if (active === 'tour') activate('tour', true);
            });
            $('#tourBack').addEventListener('change', () => { if (active === 'tour') activate('tour', true); });
            $('#tourGo').addEventListener('click', (ev) => run(ev.currentTarget, () => tourMod.go()));
            $('#tourView').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (b) tourMod.view(b.dataset.view).catch((e) => toast(e.message, true));
            });

            /* =========================================
               6. NAVIGASI
               ========================================= */
            function instruction(s) {
                const side = s.dir === 'Left' ? 'kiri' : s.dir === 'Right' ? 'kanan' : '';
                const to = s.next ? (s.next !== s.road ? ' ke ' + s.next : ' di ' + s.next) : '';
                switch (s.type) {
                    case 'Depart': return 'Berangkat' + (s.road ? ' lewat ' + s.road : '');
                    case 'Arrive': return 'Tiba di tujuan';
                    case 'Turn':
                        if (!side) return 'Lurus' + to;
                        return (s.intensity === 'Sharp' ? 'Belok tajam ' : s.intensity === 'Slight' ? 'Serong ' : 'Belok ') + side + to;
                    case 'Keep': return 'Ambil lajur ' + (side || 'tengah') + to;
                    case 'Continue': return 'Lurus terus' + (s.next ? ' di ' + s.next : '');
                    case 'UTurn': return 'Putar balik' + to;
                    case 'Exit': return 'Keluar ' + (side ? 'ke ' + side : '') + to;
                    case 'Ramp': return 'Masuk ramp ' + side + to;
                    case 'EnterHighway': return 'Masuk jalan tol' + to;
                    case 'RoundaboutEnter': return 'Masuk bundaran' + to;
                    case 'RoundaboutExit': return 'Keluar bundaran' + to;
                    case 'RoundaboutPass': return 'Lewati bundaran';
                    default: return s.type + to;
                }
            }

            function stepIcon(s) {
                if (s.type === 'Arrive') return 'bi-geo-alt-fill';
                if (s.type === 'Depart') return 'bi-cursor-fill';
                if (s.type === 'UTurn') return 'bi-arrow-counterclockwise';
                if (s.type.startsWith('Roundabout')) return 'bi-arrow-repeat';
                if (s.type === 'Turn' && s.intensity !== 'Slight') {
                    if (s.dir === 'Left') return 'bi-arrow-90deg-left';
                    if (s.dir === 'Right') return 'bi-arrow-90deg-right';
                }
                if (s.dir === 'Left') return 'bi-arrow-up-left';
                if (s.dir === 'Right') return 'bi-arrow-up-right';
                return 'bi-arrow-up';
            }

            const navMod = {
                mult: 30,
                phoneMap: null,
                puck: null,
                enter() {
                    depotMarker();
                    const d = this.dest();
                    mk(d.pos, '<i class="bi bi-shop"></i>', '', d.name);
                    fit([DEPOT.pos, d.pos]);
                    $('#navSteps').innerHTML = '';
                    $('#navGo').disabled = false;
                    $('#navStop').disabled = true;
                },
                dest() { return STORES[+$('#navDest').value]; },
                ensurePhone(f) {
                    $('#phone').hidden = false;
                    const draw = () => {
                        this.phoneMap.getSource('r').setData(fc([line(f.coords)]));
                        this.phoneMap.resize();
                    };
                    this.pending = draw;
                    if (this.phoneMap) return this.phoneReady && draw();
                    this.phoneMap = new maplibregl.Map({
                        container: 'phoneMap',
                        style: '/api/v2/map-style?style=Standard&color=Light',
                        center: f.coords[0], zoom: 16.5, pitch: 55,
                        interactive: false, attributionControl: false,
                    });
                    this.phoneMap.on('load', () => {
                        this.phoneMap.addSource('r', { type: 'geojson', data: fc([]) });
                        this.phoneMap.addLayer({ id: 'r-case', type: 'line', source: 'r', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': '#fff', 'line-width': 14 } });
                        this.phoneMap.addLayer({ id: 'r-line', type: 'line', source: 'r', layout: { 'line-cap': 'round', 'line-join': 'round' }, paint: { 'line-color': '#00B14F', 'line-width': 9 } });
                        this.phoneReady = true;
                        this.pending();
                    });
                    const el = document.createElement('div');
                    el.className = 'puck';
                    this.puck = new maplibregl.Marker({ element: el }).setLngLat(f.coords[0]).addTo(this.phoneMap);
                },
                async go() {
                    activate('nav', true);
                    $('#navGo').disabled = true;
                    const e = epoch, d = this.dest();
                    const [f] = await calcRoutes(DEPOT.pos, d.pos);
                    if (e !== epoch) return;
                    setData('main', [line(f.coords)]);
                    this.ensurePhone(f);
                    fit(f.coords, { right: isMobile() ? 40 : 360 });

                    const list = $('#navSteps');
                    list.innerHTML = f.steps.map((s, i) => `<li data-i="${i}"><i class="bi ${stepIcon(s)}"></i><span>${esc(instruction(s))}</span><em>${s.dist ? fmtKm(s.dist) : ''}</em></li>`).join('');
                    const items = $$('li', list);
                    const start = Date.now();
                    let last = -1, brg = pointAt(f, 0).brg;

                    $('#navStop').disabled = false;
                    startTrip(f, {
                        mult: this.mult,
                        onMove: (p, st) => {
                            st.mult = this.mult;
                            const i = stepIndex(f, st.d);
                            const cur = f.steps[i], nxt = f.steps[i + 1], after = f.steps[i + 2];
                            const rem = remainSec(f, st.d);
                            $('#phDist').textContent = nxt ? fmtKm(Math.max(0, cur.end - st.d)) : fmtKm(f.total - st.d);
                            $('#phText').textContent = nxt ? instruction(nxt) : 'Tiba di tujuan';
                            $('#phMin').textContent = Math.max(1, Math.round(rem / 60));
                            $('#phRem').textContent = nf((f.total - st.d) / 1000) + ' km';
                            $('#phEta').textContent = new Date(start + (st.t + rem) * 1000).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                            if (i !== last) {
                                last = i;
                                $('#phIcon').className = 'bi ' + stepIcon(nxt || { type: 'Arrive' });
                                $('#phNext').hidden = !after;
                                if (after) $('#phNext').textContent = 'Lalu: ' + instruction(after);
                                items.forEach((li, k) => {
                                    li.classList.toggle('done', k <= i);
                                    li.classList.toggle('cur', k === i + 1);
                                });
                                items[Math.min(i + 1, items.length - 1)]?.scrollIntoView({ block: 'nearest' });
                            }
                            // Haluskan putaran kamera supaya tidak menyentak di tiap titik geometri.
                            brg += (((p.brg - brg) % 360 + 540) % 360 - 180) * 0.12;
                            this.puck?.setLngLat(p.pos);
                            if (this.phoneReady) this.phoneMap.jumpTo({ center: p.pos, bearing: brg });
                        },
                        onDone: () => {
                            $('#phDist').textContent = '0 m';
                            $('#phText').textContent = 'Tiba di ' + d.name;
                            $('#phIcon').className = 'bi bi-geo-alt-fill';
                            $('#phNext').hidden = true;
                            $('#navStop').disabled = true;
                            $('#navGo').disabled = false;
                            toast('Tiba di ' + d.name);
                        },
                    });
                    return 'keep-disabled';
                },
            };
            $('#navDest').innerHTML = $('#truckDest').innerHTML;
            $('#navDest').value = '8';
            $('#navDest').addEventListener('change', () => activate('nav', true));
            $('#navGo').addEventListener('click', (ev) => run(ev.currentTarget, () => navMod.go()));
            $('#navStop').addEventListener('click', () => activate('nav', true));
            $('#navSpeed').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (!b) return;
                $$('#navSpeed button').forEach((x) => x.classList.toggle('on', x === b));
                navMod.mult = +b.dataset.mult;
            });

            /* =========================================
               7. SNAP-TO-ROAD & GEOFENCE
               ========================================= */
            const trkMod = {
                stops: [STORES[0], STORES[1], STORES[2]],
                enter() {
                    depotMarker();
                    this.marks = this.stops.map((s, i) => mk(s.pos, i + 1, '', s.name));
                    this.centers = this.stops.map((s) => s.pos);
                    this.inside = this.stops.map(() => false);
                    this.fences();
                    fit([DEPOT.pos, ...this.centers]);
                    $('#trkLog').innerHTML = '';
                    $('#trkPts').textContent = '0';
                    $('#trkOff').textContent = '–';
                    $('#trkVis').textContent = '0/' + this.stops.length;
                    $('#trkGo').disabled = false;
                },
                r() { return +$('#trkR').value; },
                fences() {
                    setData('fence', this.centers.map((c, i) => poly(circle(c, this.r()), { in: !!this.inside[i] })));
                },
                log(t, msg, cls = '') {
                    $('#trkLog').insertAdjacentHTML('afterbegin', `<li class="${cls}"><time>${clock(8 * 3600 + t)}</time>${msg}</li>`);
                },
                gauss() {
                    return Math.sqrt(-2 * Math.log(1 - Math.random())) * Math.cos(2 * Math.PI * Math.random());
                },
                async go() {
                    activate('track', true);
                    $('#trkGo').disabled = true;
                    const e = epoch;
                    const pos = this.stops.map((s) => s.pos);
                    const [f] = await calcRoutes(DEPOT.pos, pos[pos.length - 1], { waypoints: pos.slice(0, -1) });
                    if (e !== epoch) return;
                    // Pusat geofence = titik berhenti di jalan (ujung tiap leg), bukan koordinat mentah toko.
                    this.centers = f.legEnd.map((d) => pointAt(f, d).pos);
                    this.centers.forEach((c, i) => this.marks[i].setLngLat(c));
                    this.fences();
                    setData('alt', [line(f.coords)]);
                    fit(f.coords);

                    const raw = [], links = [], trail = [f.coords[0]];
                    let lastD = -1e9, sumOff = 0, visited = 0;
                    this.log(0, 'Berangkat dari ' + esc(DEPOT.name) + '.', 'b');

                    startTrip(f, {
                        mult: 80,
                        onMove: (p, st) => {
                            if (st.d - lastD < 50 && st.d < f.total) return;
                            lastD = st.d;
                            // Titik GPS mentah: posisi sebenarnya + derau σ≈14 m per sumbu (≈ ±20 m).
                            const g = offset(offset(p.pos, 0, this.gauss() * 14), 90, this.gauss() * 14);
                            const snap = nearest(f, g, st.d - 250, st.d + 250);
                            raw.push(pt(g));
                            links.push(line([g, snap.pos]));
                            trail.push(snap.pos);
                            sumOff += snap.dist;
                            setData('raw', raw);
                            setData('link', links);
                            setData('trail', [line(trail)]);
                            $('#trkPts').textContent = raw.length;
                            $('#trkOff').textContent = nf(sumOff / raw.length) + ' m';

                            const r = this.r();
                            this.centers.forEach((c, i) => {
                                const isIn = hav(snap.pos, c) <= r;
                                if (isIn === this.inside[i]) return;
                                this.inside[i] = isIn;
                                this.fences();
                                if (isIn) {
                                    visited++;
                                    this.marks[i].getElement().classList.add('done');
                                    $('#trkVis').textContent = visited + '/' + this.stops.length;
                                    this.log(st.t, `Masuk geofence <b>${esc(this.stops[i].name)}</b> — kedatangan tercatat.`, 'g');
                                    // Berhenti sebentar: bongkar muat.
                                    st.paused = true;
                                    setTimeout(() => { if (trip === st) st.paused = false; }, 1300);
                                } else {
                                    this.log(st.t, `Keluar geofence ${esc(this.stops[i].name)}.`);
                                }
                            });
                        },
                        onDone: (st) => {
                            this.log(st.t, 'Tur selesai. ' + visited + ' dari ' + this.stops.length + ' toko terdeteksi.', 'b');
                            $('#trkGo').disabled = false;
                        },
                    });
                    return 'keep-disabled';
                },
            };
            $('#trkR').addEventListener('input', () => {
                $('#trkRv').textContent = $('#trkR').value + ' m';
                if (active === 'track') trkMod.fences();
            });
            $('#trkGo').addEventListener('click', (ev) => run(ev.currentTarget, () => trkMod.go()));

            /* =========================================
               8. PREFERENSI RUTE
               ========================================= */
            const prefMod = {
                cases: {
                    tol: { from: DEPOT.pos, to: FAR.pos, a: DEPOT.name, b: FAR.name + ', ' + FAR.area },
                    ferry: { from: [112.7378, -7.2459], to: [115.2167, -8.6500], a: 'Surabaya', b: 'Denpasar' },
                },
                which: 'tol',
                base: {},
                seq: 0,
                enter() {
                    this.update();
                },
                async update() {
                    const e = epoch, id = ++this.seq, c = this.cases[this.which], out = $('#prefOut');
                    const avoid = {};
                    if ($('#pfToll').checked) avoid.TollRoads = true;
                    if ($('#pfHwy').checked) avoid.ControlledAccessHighways = true;
                    if ($('#pfFerry').checked) avoid.Ferries = true;
                    const has = Object.keys(avoid).length > 0;
                    const stale = () => e !== epoch || id !== this.seq;

                    markers.forEach((m) => m.remove());
                    markers = [];
                    mk(c.from, 'A', 'seq', c.a);
                    mk(c.to, 'B', 'seq', c.b);
                    out.innerHTML = '<p class="hint">Menghitung rute…</p>';

                    try {
                        const base = this.base[this.which] || (this.base[this.which] = (await calcRoutes(c.from, c.to))[0]);
                        if (stale()) return;
                        const card = (f, label, win, cmp) => `
                            <div class="kpi ${win ? 'win' : ''}"><b>${fmtMin(f.sec)}</b><span>${label} · ${nf(f.km)} km</span>
                            ${cmp ? `<small class="${f.sec > cmp.sec ? 'up' : ''}">${f.sec >= cmp.sec ? '+' : '−'}${fmtMin(Math.abs(f.sec - cmp.sec))} · ${f.km >= cmp.km ? '+' : '−'}${nf(Math.abs(f.km - cmp.km))} km</small>` : ''}</div>`;
                        if (!has) {
                            setData('alt', []);
                            setData('main', [line(base.coords)]);
                            fit(base.coords);
                            out.innerHTML = `<div class="kpis two">${card(base, 'Tanpa batasan', true)}</div><p class="hint">Aktifkan preferensi untuk membandingkan.</p>`;
                            return;
                        }
                        let pref = null, err = null;
                        try { pref = (await calcRoutes(c.from, c.to, { avoid }))[0]; } catch (x) { err = x; }
                        if (stale()) return;
                        setData('alt', [line(base.coords)]);
                        if (!pref) {
                            setData('main', []);
                            fit(base.coords);
                            out.innerHTML = `<div class="kpis two">${card(base, 'Tanpa batasan', false)}
                                <div class="kpi"><b class="bad">Tidak ada rute</b><span>Dengan preferensi ini</span></div></div>
                                <p class="hint">API membalas: “${esc(err.message)}”. ${avoid.Ferries ? c.a + ' → ' + c.b + ' memang harus menyeberang laut.' : ''}</p>`;
                            return;
                        }
                        setData('main', [line(pref.coords)]);
                        fit(base.coords.concat(pref.coords));
                        const same = Math.abs(pref.sec - base.sec) < 30 && Math.abs(pref.km - base.km) < 0.3;
                        out.innerHTML = `<div class="kpis two">${card(base, 'Tanpa batasan', false)}${card(pref, 'Sesuai preferensi', true, base)}</div>
                            ${same ? '<p class="hint">Rutenya sama — rute tanpa batasan sudah memenuhi preferensi ini.</p>' : ''}`;
                    } catch (x) {
                        if (!stale()) out.innerHTML = `<p class="hint bad">${esc(x.message)}</p>`;
                    }
                },
            };
            $('#prefCase').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (!b) return;
                $$('#prefCase button').forEach((x) => x.classList.toggle('on', x === b));
                prefMod.which = b.dataset.case;
                prefMod.update();
            });
            ['pfToll', 'pfHwy', 'pfFerry'].forEach((id) => $('#' + id).addEventListener('change', () => prefMod.update()));

            /* =========================================
               9. CAKUPAN
               ========================================= */
            const covMod = {
                res: [],
                enter() {
                    this.marks = CITIES.map(([name, a], i) => mk(a, '', 'city' + (this.res[i] ? (this.res[i].f ? ' okc' : ' badc') : ''), name));
                    this.render();
                    map.fitBounds([[95, -11], [141.5, 6]], {
                        padding: isMobile() ? { top: 30, left: 20, right: 20, bottom: window.innerHeight * 0.55 } : { top: 60, bottom: 60, right: 40, left: $('#side').offsetWidth + 40 },
                        duration: 700,
                    });
                },
                render() {
                    $('#covTable tbody').innerHTML = CITIES.map(([name], i) => {
                        const r = this.res[i];
                        if (!r) return `<tr data-i="${i}"><td>${name}</td><td>–</td><td>–</td><td>–</td><td style="color:var(--mut)">Belum diuji</td></tr>`;
                        if (!r.f) return `<tr data-i="${i}"><td>${name}</td><td>–</td><td>–</td><td>–</td><td class="bad" title="${esc(r.err)}">Gagal</td></tr>`;
                        return `<tr data-i="${i}"><td>${name}</td><td>${nf(r.f.km)}</td><td>${Math.round(r.f.sec / 60)}</td><td>${r.ms}</td><td class="ok"><i class="bi bi-check-circle-fill"></i> 200</td></tr>`;
                    }).join('');
                    const done = this.res.filter(Boolean), ok = done.filter((r) => r.f);
                    $('#covSum').innerHTML = done.length ? `<div class="kpis two">
                        <div class="kpi ${ok.length === CITIES.length ? 'win' : ''}"><b>${ok.length}/${CITIES.length}</b><span>Kota terlayani</span></div>
                        <div class="kpi"><b>${ok.length ? Math.round(ok.reduce((a, r) => a + r.ms, 0) / ok.length) : '–'} ms</b><span>Rata-rata respons</span></div></div>` : '';
                },
                async go() {
                    const e = epoch;
                    this.res = [];
                    this.render();
                    // Empat sekaligus supaya tabel terisi bertahap.
                    for (let i = 0; i < CITIES.length; i += 4) {
                        await Promise.all(CITIES.slice(i, i + 4).map(async ([, a, b], k) => {
                            const idx = i + k;
                            try {
                                const routes = await calcRoutes(a, b);
                                this.res[idx] = { f: routes[0], ms: routes.ms };
                            } catch (x) {
                                this.res[idx] = { f: null, err: x.message };
                            }
                            if (e !== epoch) return;
                            this.marks[idx]?.getElement().classList.add(this.res[idx].f ? 'okc' : 'badc');
                            this.render();
                        }));
                        if (e !== epoch) return;
                    }
                },
                show(i) {
                    const r = this.res[i], [, a] = CITIES[i];
                    if (!r?.f) return map.flyTo({ center: a, zoom: 11 });
                    setData('main', [line(r.f.coords)]);
                    fit(r.f.coords, { maxZoom: 13 });
                },
            };
            $('#covGo').addEventListener('click', (ev) => run(ev.currentTarget, () => covMod.go()));
            $('#covTable').addEventListener('click', (ev) => {
                const tr = ev.target.closest('tr[data-i]');
                if (tr) covMod.show(+tr.dataset.i);
            });

            /* =========================================
               PANEL INFO — sumber data, cara hitung, request/response AWS
               ========================================= */
            const TILE = ['aws', 'Peta dasar', '<code>GetTile</code> — vector tile GrabMaps'];
            const EP = {
                routes: `https://routes.geo.${REGION}.amazonaws.com/v2/routes`,
                matrix: `https://routes.geo.${REGION}.amazonaws.com/v2/route-matrix`,
                style: `https://maps.geo.${REGION}.amazonaws.com/v2/styles/Standard/descriptor?color-scheme=Light`,
                tiles: `https://maps.geo.${REGION}.amazonaws.com/v2/tiles/vector.basemap/{z}/{x}/{y}`,
            };
            // Satu langkah = satu kartu. `api` butuh panggilan ke AWS; `page` dihitung di browser.
            const api = (title, url, rows, method = 'POST') => ({ t: 'aws', title, eps: [].concat(url).map((u) => [method, u]), rows });
            const page = (title, rows) => ({ t: 'page', title, eps: [], rows });
            const MAP_STEP = api('Peta dasar', [EP.style, EP.tiles], [
                ['Operasi', '<code>GetStyleDescriptor</code> sekali saat halaman dibuka, lalu <code>GetTile</code> tiap peta digeser atau diperbesar'],
                ['Dipakai', 'Gaya peta dan vector tile, digambar oleh MapLibre GL di browser'],
            ], 'GET');
            const MOVE_STEP = (mult) => page('Gerakkan kendaraan di garis rute', [
                ['Input', 'Garis rute dan <code>TravelSteps[].Distance</code> / <code>Duration</code>'],
                ['Cara', `Tiap frame (<code>requestAnimationFrame</code>): kecepatan = <code>Distance ÷ Duration</code> langkah yang sedang dilalui, dikali ${mult}. Jarak tempuh bertambah, lalu posisi dicari dengan interpolasi di antara dua titik garis.`],
                ['Catatan', 'Simulasi — bukan posisi GPS kendaraan sungguhan'],
            ]);
            const ROUTE_USED = '<code>Routes[].Summary.Distance</code>, <code>Summary.Duration</code>, <code>Legs[].Geometry.LineString</code>, <code>Legs[].VehicleLegDetails.TravelSteps[]</code>';

            const INFO = {
                truck: {
                    title: 'Routing truk & hindari gang sempit',
                    sources: [
                        TILE,
                        ['aws', 'Hingga 5 alternatif rute', '<code>CalculateRoutes</code> — jarak, durasi, garis rute, dan daftar langkah belokan beserta nama jalan'],
                        ['page', 'Jumlah belok, belok rapat, putar balik', 'Dihitung dari <code>TravelSteps</code> pada balasan AWS'],
                        ['page', 'Kelas kendaraan, penalti, skor, rute terpilih', 'Dihitung dari input tinggi / berat / as'],
                        ['none', 'Batasan dimensi di jalan', 'Jembatan rendah, batas tonase, lebar jalan — tidak tersedia; mode <code>Truck</code> ditolak region ini'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta alternatif rute DC → toko', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — satu permintaan tiap tombol <b>Cari rute truk</b> diklik'],
                            ['Dikirim', '<code>Origin</code> (DC), <code>Destination</code> (toko), <code>TravelMode: "Car"</code>, <code>MaxAlternatives: 5</code>, <code>LegGeometryFormat: "Simple"</code>, <code>TravelStepType: "TurnByTurn"</code>'],
                            ['Tidak dikirim', 'Tinggi, berat, jumlah as. <code>TravelMode: "Truck"</code> dibalas 400 di region ini'],
                            ['Dipakai', ROUTE_USED + ' — dari tiap langkah: <code>Type</code>, <code>Distance</code>, <code>TurnStepDetails.TurnIntensity</code>'],
                        ]),
                        page('Gambar semua alternatif di peta', [
                            ['Input', '<code>Geometry.LineString</code> tiap rute'],
                            ['Cara', 'Titik-titik garis dijadikan GeoJSON lalu digambar sebagai layer garis MapLibre: rute terpilih hijau, sisanya abu-abu'],
                        ]),
                        page('Hitung manuver tiap rute', [
                            ['Input', '<code>TravelSteps[]</code> tiap rute'],
                            ['Cara', 'Satu putaran atas semua langkah. <code>Type = "Turn"</code> → <b>Belok</b> +1. Kalau belokan itu <code>Distance</code>-nya &lt; 150 m (ruas sesudah belok pendek) atau <code>TurnIntensity = "Sharp"</code> → <b>Rapat</b> +1. <code>Type = "UTurn"</code> → <b>Putar balik</b> +1'],
                            ['Catatan', 'Belokan rapat dipakai sebagai tanda jalan kecil. Lebar jalan sendiri tidak ada di balasan AWS'],
                        ]),
                        page('Tentukan kelas kendaraan', [
                            ['Input', 'Kolom tinggi, berat, jumlah as di panel kiri'],
                            ['Cara', 'Aturan berurutan: berat &gt; 12 t, tinggi &gt; 3,2 m, atau as ≥ 3 → <b>berat</b>, k = 1,5. Kalau bukan: berat &gt; 5 t atau tinggi &gt; 2,4 m → <b>sedang</b>, k = 0,8. Sisanya → <b>ringan</b>, k = 0,3'],
                        ]),
                        page('Hitung skor', [
                            ['Input', '<code>Summary.Duration</code> (detik) dari AWS, hitungan manuver, bobot k'],
                            ['Cara', '<b>Skor = Duration ÷ 60 + k × (belok × 0,5 + rapat + putar balik × 3)</b>. Satuan menit'],
                            ['Catatan', 'Bobot 0,5 / 1 / 3 dan nilai k adalah asumsi demo, belum dikalibrasi dengan data lapangan'],
                        ]),
                        page('Pilih rute dan susun tabel', [
                            ['Cara', 'Skor terkecil diberi tanda <b>Truk</b> dan digambar hijau. <code>Duration</code> terkecil diberi tanda <b>Tercepat</b>. Tabel diurutkan dari skor terkecil; klik baris untuk melihat rute lain'],
                        ]),
                    ],
                    after() {
                        const rs = truckMod.routes, kl = truckMod.kl;
                        if (!rs.length || !kl) return '<p class="hint">Klik <b>Cari rute truk</b> untuk melihat hitungannya dengan angka sungguhan.</p>';
                        const rows = rs.map((r, j) => `
                            <tr class="${r.best ? 'pick' : ''}">
                                <td>Rute ${String.fromCharCode(65 + j)}</td>
                                <td class="m">${nf(r.f.sec / 60)} + ${nf(kl.k)} × (${r.turns} × 0,5 + ${r.tight} + ${r.uturn} × 3)</td>
                                <td class="m">= ${nf(r.score)}</td>
                            </tr>`).join('');
                        return `
                            <h3>Hitungan terakhir</h3>
                            <p style="margin:0">Tinggi ${nf(kl.h)} m, berat ${nf(kl.w)} t, ${kl.a} as → <b>${kl.label}</b>, k = ${nf(kl.k)}</p>
                            <table class="fx"><thead><tr><th>Rute</th><th>Menit (AWS) + penalti</th><th>Skor</th></tr></thead><tbody>${rows}</tbody></table>`;
                    },
                },
                traffic: {
                    title: 'Traffic & ETA per jam berangkat',
                    sources: [
                        TILE,
                        ['aws', 'Lima rute, satu per jam berangkat', '<code>CalculateRoutes</code> dengan <code>DepartureTime</code> berbeda (besok, WIB)'],
                        ['page', 'Kecepatan dan warna tiap segmen', '<code>Distance ÷ Duration</code> tiap langkah pada balasan AWS'],
                        ['none', 'Kepadatan real-time & insiden', 'Parameter <code>Traffic</code> dan <code>Incidents</code> ditolak region ini'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta rute untuk 5 jam berangkat', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — 5 permintaan sekaligus (paralel)'],
                            ['Dikirim', 'Asal dan tujuan sama di kelimanya; yang berbeda hanya <code>DepartureTime</code>: besok pukul 03:00, 07:30, 12:00, 17:30, 21:00 dengan zona <code>+07:00</code>'],
                            ['Dipakai', ROUTE_USED + ' — dari tiap langkah: <code>Distance</code> dan <code>Duration</code>'],
                            ['Catatan', 'Durasi berbeda antar jam karena AWS menghitungnya untuk jam tersebut. Parameter <code>Traffic</code> (real-time) dibalas 400'],
                        ]),
                        page('Batang perbandingan', [
                            ['Input', '<code>Summary.Duration</code> kelima balasan'],
                            ['Cara', 'Angka = <code>Duration ÷ 60</code>. Tinggi batang sebanding dengan durasi terbesar. Jam dengan durasi terbesar dipilih otomatis'],
                        ]),
                        page('Potong garis rute per langkah', [
                            ['Input', '<code>Geometry.LineString</code> dan <code>TravelSteps[].Distance</code>'],
                            ['Cara', 'Jarak langkah dijumlah berurutan dan diskalakan ke panjang garis (haversine antar titik), sehingga tiap langkah punya awal dan akhir di garis. Garis lalu dipotong di batas-batas itu'],
                        ]),
                        page('Warnai tiap potongan', [
                            ['Cara', 'Kecepatan = <code>Distance ÷ Duration × 3,6</code> km/j. &gt; 50 hijau, 30–50 kuning, 15–30 jingga, &lt; 15 merah'],
                        ]),
                        page('Angka ringkasan', [
                            ['Cara', 'Kecepatan rata-rata = <code>Summary.Distance ÷ Summary.Duration</code>. Ruas padat = jumlah <code>Distance</code> langkah yang kecepatannya ≤ 15 km/j. Selisih = durasi terbesar − terkecil'],
                        ]),
                    ],
                },
                reroute: {
                    title: 'Insiden & re-routing dinamis',
                    sources: [
                        TILE,
                        ['aws', 'Rute awal', '<code>CalculateRoutes</code> DC → toko'],
                        ['aws', 'Rute pengganti', '<code>CalculateRoutes</code> dari posisi truk, <code>MaxAlternatives: 5</code>'],
                        ['page', 'Titik penutupan jalan & posisi truk', 'Disimulasikan di halaman'],
                        ['page', 'Pemilihan rute pengganti', 'Alternatif yang berjarak &gt; 250 m dari titik tertutup, lalu yang tercepat'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta rute awal', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — saat <b>Mulai perjalanan</b> diklik'],
                            ['Dikirim', '<code>Origin</code> (DC), <code>Destination</code> (toko), <code>TravelMode: "Car"</code>, <code>TravelStepType: "TurnByTurn"</code>'],
                            ['Dipakai', ROUTE_USED],
                        ]),
                        MOVE_STEP('25'),
                        page('Taruh titik penutupan jalan', [
                            ['Cara', 'Tombol: titik di garis rute sejauh 35% sisa perjalanan di depan truk (minimal 900 m). Klik peta: titik terdekat pada garis rute di depan truk, diterima kalau klik berjarak ≤ 250 m dari garis'],
                            ['Catatan', 'Simulasi. AWS tidak mengirim data insiden di region ini (<code>Incidents</code> dibalas 400)'],
                        ]),
                        api('Minta rute pengganti dari posisi truk', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — langsung setelah titik ditaruh'],
                            ['Dikirim', '<code>Origin</code> = posisi truk saat itu, <code>Destination</code> = toko, <code>MaxAlternatives: 5</code>'],
                            ['Tidak dikirim', 'Lokasi penutupan. <code>Avoid.Areas</code> dibalas 400 di region ini, jadi penyaringan dikerjakan halaman'],
                            ['Dipakai', ROUTE_USED],
                        ]),
                        page('Saring dan pilih rute pengganti', [
                            ['Cara', 'Untuk tiap alternatif, titik tertutup diproyeksikan ke tiap segmen garis untuk mencari jarak terdekatnya. Alternatif yang lewat ≤ 250 m dari titik tertutup mana pun dibuang. Dari sisanya dipilih <code>Summary.Duration</code> terkecil'],
                        ]),
                        api('Cadangan: paksa memutar lewat titik bantu', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — hanya kalau semua alternatif tadi masih lewat titik tertutup'],
                            ['Dikirim', 'Sama, ditambah satu <code>Waypoints</code>: titik 800 m di kiri dan di kanan titik tertutup, tegak lurus arah jalan (2 permintaan). Kalau belum bebas, diulang dengan 1.500 m'],
                        ]),
                        page('Hitung selisih terhadap rute lama', [
                            ['Cara', 'Sisa waktu rute lama = jumlah <code>Duration</code> langkah yang belum dilalui (langkah yang sedang dilalui dihitung sebagian). Selisih menit dan km = rute baru − sisa rute lama. Sisa rute lama digambar merah putus-putus'],
                        ]),
                    ],
                },
                matrix: {
                    title: 'Distance Matrix',
                    sources: [
                        TILE,
                        ['aws', 'Jarak & durasi semua pasangan', '<code>CalculateRouteMatrix</code> — satu request untuk N × N pasangan'],
                        ['page', 'Warna sel & pasangan per detik', 'Jumlah pasangan ÷ waktu respons yang diukur browser'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta matrix semua pasangan', EP.matrix, [
                            ['Operasi', '<code>CalculateRouteMatrix</code> — satu permintaan'],
                            ['Dikirim', '<code>Origins</code> dan <code>Destinations</code> = daftar yang sama (DC + N toko, masing-masing <code>{ Position }</code>), <code>TravelMode: "Car"</code>, <code>RoutingBoundary: { Unbounded: true }</code>'],
                            ['Dipakai', '<code>RouteMatrix[baris][kolom].Distance</code> (meter), <code>.Duration</code> (detik), <code>.Error</code>'],
                        ]),
                        page('Susun tabel berwarna', [
                            ['Cara', 'Angka sel = <code>Duration ÷ 60</code>, baris = asal, kolom = tujuan. Warna diinterpolasi dari hijau (durasi terkecil di matrix) ke merah (terbesar). Sel dengan <code>Error</code> ditandai “!”'],
                        ]),
                        page('Ukur kecepatan', [
                            ['Cara', '<code>performance.now()</code> dicatat sebelum dan sesudah <code>fetch</code>. Pasangan per detik = (N+1)² ÷ waktu respons'],
                            ['Catatan', 'Diukur dari browser: termasuk perjalanan browser → server demo → AWS dan kembali'],
                        ]),
                        api('Uji 5 batch paralel', EP.matrix, [
                            ['Operasi', '<code>CalculateRouteMatrix</code> — 5 permintaan 13 × 13 dikirim bersamaan (<code>Promise.all</code>)'],
                            ['Dipakai', 'Total waktu sampai kelimanya selesai, dan jumlah sel <code>Error</code>'],
                        ]),
                    ],
                },
                tour: {
                    title: 'Optimasi urutan tur',
                    sources: [
                        TILE,
                        ['aws', 'Waktu & jarak antar titik', '<code>CalculateRouteMatrix</code>'],
                        ['aws', 'Garis rute tur', '<code>CalculateRoutes</code> dengan <code>Waypoints</code> sesuai urutan'],
                        ['page', 'Urutan kunjungan', 'Solver di halaman: nearest-neighbour, lalu 2-opt dan pindah-satu-titik'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta matrix DC + toko', EP.matrix, [
                            ['Operasi', '<code>CalculateRouteMatrix</code> — satu permintaan'],
                            ['Dikirim', '<code>Origins</code> = <code>Destinations</code> = DC + N toko, <code>TravelMode: "Car"</code>, <code>RoutingBoundary: { Unbounded: true }</code>'],
                            ['Dipakai', '<code>RouteMatrix[i][j].Duration</code> dan <code>.Distance</code>'],
                        ]),
                        page('Hitung biaya satu urutan', [
                            ['Cara', 'Jumlah <code>Duration</code> dari matrix untuk tiap perpindahan: DC → toko pertama → … → toko terakhir, ditambah kembali ke DC kalau opsinya dicentang'],
                        ]),
                        page('Cari urutan terbaik (solver)', [
                            ['Cara', '(1) Nearest-neighbour: dari DC selalu ke toko terdekat yang belum dikunjungi. (2) 2-opt: coba balik tiap potongan urutan, simpan kalau total turun. (3) Pindah-satu-titik: coba pindahkan tiap toko ke posisi lain. Langkah 2–3 diulang sampai tidak ada perbaikan'],
                            ['Catatan', 'Matrix tidak simetris (A→B ≠ B→A), jadi biaya dihitung utuh tiap percobaan. Hasilnya baik tetapi tidak dijamin optimum mutlak'],
                        ]),
                        api('Minta garis rute untuk urutan itu', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — satu permintaan per urutan yang ditampilkan (optimal, dan awal bila dibuka)'],
                            ['Dikirim', '<code>Origin</code> = DC, <code>Waypoints</code> = toko sesuai urutan, <code>Destination</code> = DC atau toko terakhir'],
                            ['Dipakai', '<code>Legs[].Geometry.LineString</code> untuk garis di peta'],
                        ]),
                        page('Angka perbandingan dan daftar urutan', [
                            ['Cara', '“Urutan awal” = urutan toko di daftar. Kedua total dihitung dari matrix yang sama. Hemat = awal − optimal. Waktu di tiap toko = akumulasi <code>Duration</code> sampai toko itu'],
                            ['Catatan', 'Hanya waktu berkendara. Bongkar muat, jam buka toko, dan kapasitas truk tidak dihitung'],
                        ]),
                    ],
                },
                nav: {
                    title: 'Navigasi turn-by-turn',
                    sources: [
                        TILE,
                        ['aws', 'Garis rute & langkah belokan', '<code>CalculateRoutes</code> dengan <code>TravelStepType: "TurnByTurn"</code>'],
                        ['page', 'Kalimat instruksi bahasa Indonesia', 'Dirangkai dari <code>Type</code>, <code>SteeringDirection</code>, <code>TurnIntensity</code>, dan <code>NextRoad</code>'],
                        ['page', 'Posisi kendaraan, layar ponsel', 'Simulasi dan mockup web, bukan SDK native'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta rute dengan langkah belokan', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — saat <b>Mulai navigasi</b> diklik'],
                            ['Dikirim', '<code>Origin</code>, <code>Destination</code>, <code>TravelMode: "Car"</code>, <code>TravelStepType: "TurnByTurn"</code>'],
                            ['Dipakai', 'Dari tiap <code>TravelSteps[]</code>: <code>Type</code>, <code>TurnStepDetails.SteeringDirection</code>, <code>TurnStepDetails.TurnIntensity</code>, <code>CurrentRoad.RoadName[0].Value</code>, <code>NextRoad.RoadName[0].Value</code>, <code>Distance</code>, <code>Duration</code>'],
                        ]),
                        page('Rangkai kalimat instruksi', [
                            ['Cara', 'AWS bisa mengirim kalimat siap pakai (<code>LegAdditionalFeatures: ["TravelStepInstructions"]</code> → field <code>Instruction</code>, mis. “Turn left”), tetapi hanya bahasa Inggris — <code>Languages</code> dibalas 400 di region ini. Supaya berbahasa Indonesia, halaman memetakan <code>Type</code> ke templat: <code>Turn</code> + <code>Left</code> → “Belok kiri ke {NextRoad}”; <code>Sharp</code> → “Belok tajam”, <code>Slight</code> → “Serong”; <code>Keep</code> → “Ambil lajur”; <code>UTurn</code> → “Putar balik”; <code>RoundaboutEnter</code> → “Masuk bundaran”; dan seterusnya. Ikon panah dipilih dengan aturan yang sama'],
                        ]),
                        MOVE_STEP('×10 / ×30 / ×80'),
                        page('Isi layar ponsel', [
                            ['Cara', 'Jarak ke belokan berikutnya = ujung langkah saat ini − posisi kendaraan. Sisa menit = jumlah <code>Duration</code> langkah yang belum dilalui. Jam tiba = jam mulai + waktu simulasi berjalan + sisa'],
                        ]),
                        page('Peta di dalam ponsel', [
                            ['Cara', 'Peta MapLibre kedua. Tiap frame kamera dipindah ke posisi kendaraan dan diputar searah jalan (arah dihitung dari dua titik garis, lalu dihaluskan)'],
                            ['Catatan', 'Mockup web. Navigation SDK native untuk Android/iOS adalah produk terpisah'],
                        ]),
                    ],
                },
                track: {
                    title: 'Snap-to-road & geofence',
                    sources: [
                        TILE,
                        ['aws', 'Garis rute DC → 3 toko', '<code>CalculateRoutes</code> dengan <code>Waypoints</code>'],
                        ['page', 'Titik GPS mentah', 'Posisi di rute + derau acak ±20 m'],
                        ['page', 'Snap ke jalan', 'Titik terdekat pada garis rute — bukan pada jaringan jalan'],
                        ['page', 'Geofence', 'Jarak ke titik berhenti ≤ radius'],
                        ['none', 'Layanan snap-to-road & geofencing terkelola', 'Tidak dipakai di demo ini'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta rute DC → 3 toko', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — saat <b>Mulai tracking</b> diklik'],
                            ['Dikirim', '<code>Origin</code> = DC, <code>Waypoints</code> = toko 1 dan 2, <code>Destination</code> = toko 3'],
                            ['Dipakai', '<code>Legs[].Geometry.LineString</code> (satu leg per toko) dan <code>TravelSteps[]</code>'],
                        ]),
                        MOVE_STEP('80'),
                        page('Buat titik GPS mentah', [
                            ['Cara', 'Tiap ±50 m perjalanan: posisi sebenarnya digeser acak ke utara–selatan dan timur–barat dengan sebaran normal (Box–Muller), simpangan baku 14 m per sumbu'],
                            ['Catatan', 'Simulasi derau GPS; titik merah di peta'],
                        ]),
                        page('Tarik titik ke jalan (snap)', [
                            ['Cara', 'Titik mentah diproyeksikan ke tiap segmen garis rute dalam rentang ±250 m dari posisi kendaraan; yang terdekat diambil. Koreksi rata-rata = rata-rata jarak tarikan'],
                            ['Catatan', 'Snap ke garis rute rencana, bukan ke jaringan jalan — kendaraan yang menyimpang dari rute tidak terdeteksi di demo ini'],
                        ]),
                        page('Deteksi geofence', [
                            ['Cara', 'Pusat geofence = ujung tiap leg (titik berhenti di jalan). Jarak haversine titik hasil snap ke pusat ≤ radius → “masuk” dan kedatangan dicatat; kembali &gt; radius → “keluar”. Lingkaran di peta = 48 titik di sekeliling pusat'],
                        ]),
                    ],
                },
                pref: {
                    title: 'Preferensi rute',
                    sources: [
                        TILE,
                        ['aws', 'Rute tanpa batasan', '<code>CalculateRoutes</code>'],
                        ['aws', 'Rute sesuai preferensi', '<code>CalculateRoutes</code> dengan <code>Avoid</code>: <code>TollRoads</code>, <code>ControlledAccessHighways</code>, <code>Ferries</code>'],
                        ['page', 'Selisih menit dan km', 'Pengurangan dua <code>Summary</code>'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta rute tanpa batasan', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — sekali per pasangan kota, lalu disimpan'],
                            ['Dikirim', '<code>Origin</code>, <code>Destination</code>, <code>TravelMode: "Car"</code>'],
                            ['Dipakai', '<code>Summary</code> dan <code>Geometry</code> — garis abu-abu'],
                        ]),
                        api('Minta rute sesuai preferensi', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — tiap sakelar diubah'],
                            ['Dikirim', 'Sama, ditambah <code>Avoid</code> berisi sakelar yang menyala: <code>TollRoads: true</code>, <code>ControlledAccessHighways: true</code>, <code>Ferries: true</code>'],
                            ['Dipakai', '<code>Summary</code> dan <code>Geometry</code> — garis hijau. Penghindaran dikerjakan AWS, bukan halaman'],
                        ]),
                        page('Bandingkan', [
                            ['Cara', 'Selisih menit dan km = rute preferensi − rute tanpa batasan. Kalau AWS membalas 400, pesannya ditampilkan apa adanya'],
                        ]),
                    ],
                },
                cov: {
                    title: 'Cakupan Indonesia',
                    sources: [
                        TILE,
                        ['aws', 'Satu rute dalam kota di 14 kota', '<code>CalculateRoutes</code>, empat sekaligus'],
                        ['page', 'Tabel & rata-rata respons', 'Waktu diukur dari browser'],
                    ],
                    steps: () => [
                        MAP_STEP,
                        api('Minta satu rute di tiap kota', EP.routes, [
                            ['Operasi', '<code>CalculateRoutes</code> — 14 permintaan, empat sekaligus per gelombang'],
                            ['Dikirim', 'Sepasang titik contoh di dalam kota, <code>TravelMode: "Car"</code>'],
                            ['Dipakai', 'Status HTTP, <code>Summary.Distance</code>, <code>Summary.Duration</code>, <code>Geometry</code>'],
                        ]),
                        page('Susun tabel', [
                            ['Cara', 'Status 200 = AWS menemukan rute berkendara di antara kedua titik. ms diukur dengan <code>performance.now()</code> dari browser. Klik baris menggambar rute kota itu'],
                            ['Catatan', 'Menguji ketersediaan routing di kota tersebut, bukan kelengkapan seluruh jalan atau alamatnya'],
                        ]),
                    ],
                },
            };

            function stepHtml(st, i) {
                return `
                    <div class="stp ${st.t}">
                        <div class="stp-h"><span class="n">${i + 1}</span>${st.title}<span class="src-tag ${st.t}">${st.t === 'aws' ? 'Panggil API' : 'Di halaman'}</span></div>
                        ${st.eps.map(([m, u]) => `<div class="ep"><b>${m}</b>${esc(u)}</div>`).join('')}
                        <dl>${st.rows.map(([k, v]) => `<dt>${k}</dt><dd>${v}</dd>`).join('')}</dl>
                    </div>`;
            }

            // Ringkas array panjang supaya JSON terbaca; versi lengkap tetap bisa disalin.
            function brief(v) {
                if (Array.isArray(v)) {
                    if (v.length <= 3 && v.every((x) => typeof x === 'number')) return v;
                    if (v.length > 4) return [...v.slice(0, 3).map(brief), `… +${v.length - 3} item lagi`];
                    return v.map(brief);
                }
                if (v && typeof v === 'object') {
                    const o = {};
                    for (const k in v) o[k] = brief(v[k]);
                    return o;
                }
                return v;
            }
            const pretty = (v) => esc(JSON.stringify(brief(v), null, 2).replace(/\[\s+(-?[\d.]+),\s+(-?[\d.]+)\s+\]/g, '[$1, $2]'));
            const kb = (v) => nf(JSON.stringify(v ?? null).length / 1024) + ' KB';

            function callHtml(c) {
                const a = AWS[c.proxy] || { op: '?', url: c.proxy };
                return `
                    <div class="call">
                        <div class="call-head">
                            <div class="meta"><strong>${a.op}</strong><span class="st s${c.status < 400 ? 2 : 4}">${c.status}</span><span>${c.ms} ms</span><span>${c.at.toLocaleTimeString('id-ID')}</span></div>
                            <div class="u"><b>POST</b>${esc(a.url)}?key=••••••</div>
                            <div class="u" style="color:var(--mut)">lewat proxy ${esc(c.proxy)}</div>
                        </div>
                        <details data-k="${c.id}-req"><summary>Request <span>${kb(c.req)}</span><button type="button" data-copy="${c.id}:req">Salin</button></summary><pre class="json">${pretty(c.req)}</pre></details>
                        <details data-k="${c.id}-res"><summary>Response <span>${kb(c.res)}</span><button type="button" data-copy="${c.id}:res">Salin lengkap</button></summary><pre class="json">${pretty(c.res)}</pre></details>
                    </div>`;
            }

            function renderInfo() {
                const box = $('#info'), body = $('#infoBody');
                if (box.hidden || !active) return;
                const info = INFO[active];
                const open = new Set($$('details[open]', body).map((d) => d.dataset.k));
                const top = body.dataset.mod === active ? body.scrollTop : 0;
                const mine = calls.filter((c) => c.mod === active);
                const tag = { aws: 'AWS', page: 'Halaman', none: 'Tidak ada' };
                $('#infoTitle').textContent = info.title;
                body.innerHTML = `
                    <h3>Sumber data</h3>
                    <div class="src">${info.sources.map(([t, what, detail]) => `<span class="src-tag ${t}">${tag[t]}</span><div><b>${what}</b><small>${detail}</small></div>`).join('')}</div>
                    <h3>Kenapa hasilnya seperti itu</h3>
                    ${info.steps().map(stepHtml).join('')}
                    ${info.after ? info.after() : ''}
                    <h3>Panggilan ke AWS (${mine.length})</h3>
                    ${mine.length ? mine.map(callHtml).join('') : '<p class="hint">Belum ada panggilan. Jalankan modul ini dulu.</p>'}
                    <p class="hint">Amazon Location Service, provider GrabMaps, region <code>${esc(REGION)}</code>. Browser memanggil proxy di server demo; server menambahkan API key lalu meneruskan body apa adanya ke AWS dan mengembalikan balasannya tanpa diubah. Array panjang diringkas di tampilan — tombol salin memberi JSON utuh.</p>`;
                $$('details', body).forEach((d) => { if (open.has(d.dataset.k)) d.open = true; });
                body.dataset.mod = active;
                body.scrollTop = top;
            }

            async function copyText(text) {
                try {
                    if (navigator.clipboard && window.isSecureContext) return await navigator.clipboard.writeText(text);
                    throw new Error('no clipboard');
                } catch (e) {
                    // http://… bukan konteks aman — clipboard API tidak ada, pakai cara lama.
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.cssText = 'position:fixed;opacity:0';
                    document.body.appendChild(ta);
                    ta.select();
                    const ok = document.execCommand('copy');
                    ta.remove();
                    if (!ok) throw new Error('Gagal menyalin');
                }
            }

            $$('.mod .needs').forEach((n) => n.insertAdjacentHTML('beforeend', '<button type="button" class="info-btn" data-info><i class="bi bi-info-circle"></i> Info &amp; API</button>'));
            $('#mods').addEventListener('click', (ev) => {
                if (!ev.target.closest('[data-info]')) return;
                const box = $('#info');
                box.hidden = !box.hidden;
                renderInfo();
            });
            $('#infoClose').addEventListener('click', () => { $('#info').hidden = true; });
            $('#infoBody').addEventListener('click', (ev) => {
                const b = ev.target.closest('[data-copy]');
                if (!b) return;
                ev.preventDefault();
                const [id, part] = b.dataset.copy.split(':');
                const c = calls.find((x) => x.id === +id);
                if (c) copyText(JSON.stringify(c[part], null, 2)).then(() => toast('JSON disalin'), (e) => toast(e.message, true));
            });

            /* =========================================
               GANTI MODUL
               ========================================= */
            // Golongan tiap modul: a = dijawab langsung API, b = dibangun di atas API, c = belum tersedia di API.
            const TIER = { traffic: 'a', matrix: 'a', pref: 'a', cov: 'a', reroute: 'b', tour: 'b', nav: 'b', truck: 'c', track: 'c' };
            const TIER_CHIP = { a: ['live', 'API langsung'], b: ['mix', 'Di atas API'], c: ['na', 'Belum tersedia'] };
            const PURE_MSG = {
                truck: ['Routing truk dengan batasan tinggi, berat, dan jumlah as belum dilayani Amazon Location + GrabMaps di region ini — <code>TravelMode: "Truck"</code> dibalas 400.',
                    'Rute mobil dengan hingga 5 alternatif (<code>CalculateRoutes</code>).'],
                reroute: ['Data insiden dan penutupan jalan belum tersedia dari API di region ini. Kapan harus rute ulang, dan rute mana yang dipilih, ditentukan logika di aplikasi.',
                    'Hitung rute ulang dari posisi kendaraan mana pun (<code>CalculateRoutes</code>, hingga 5 alternatif).'],
                tour: ['Urutan kunjungan tidak dihitung API — operasi optimasi waypoint bukan bagian dari aksi yang didukung GrabMaps. Urutan dihitung solver di sisi sistem (EMS).',
                    'Waktu dan jarak semua pasangan titik dalam satu permintaan (<code>CalculateRouteMatrix</code>).'],
                nav: ['Aplikasi navigasi — panduan suara, kamera yang mengikuti kendaraan, pelacakan posisi — dibangun di aplikasi pengemudi. SDK navigasi native bukan bagian dari Amazon Location.',
                    'Garis rute dan langkah belokan: jenis manuver, arah, nama jalan, jarak, durasi (<code>CalculateRoutes</code>).'],
                track: ['Snap-to-road dan geofencing tidak tersedia lewat API key GrabMaps. Amazon Location punya layanan Trackers dan Geofences terpisah (kredensial IAM); belum diuji di demo ini.',
                    'Garis rute rencana sebagai acuan (<code>CalculateRoutes</code>).'],
            };
            let mode = 'full';

            $$('.mod').forEach((el) => {
                const id = el.dataset.mod, tier = TIER[id];
                el.dataset.tier = tier;
                el.querySelector('.mod-head').insertAdjacentHTML('beforeend', `<span class="chip-st chip-pure ${TIER_CHIP[tier][0]}">${TIER_CHIP[tier][1]}</span>`);
                if (tier === 'a') return;
                const [gap, have] = PURE_MSG[id];
                el.querySelector('.mod-body').insertAdjacentHTML('beforeend', `
                    <div class="pure-msg">
                        <b class="k">${tier === 'b' ? 'Yang dikerjakan di luar API' : 'Status'}</b><p>${gap}</p>
                        <b class="k">Yang disediakan API</b><p>${have}</p>
                        <button type="button" class="btn ghost" data-full="${id}"><i class="bi bi-box-arrow-up-right"></i> Lihat di mode Solusi lengkap</button>
                    </div>`);
            });

            function setMode(m, target) {
                mode = m === 'pure' ? 'pure' : 'full';
                document.body.classList.toggle('pure', mode === 'pure');
                $$('#modeSeg button').forEach((b) => b.classList.toggle('on', b.dataset.mode === mode));
                const count = (t) => Object.values(TIER).filter((x) => x === t).length;
                $('#modeHint').innerHTML = mode === 'pure'
                    ? `<p>Hanya yang dijawab langsung oleh Amazon Location Service + GrabMaps. Tanpa simulasi.</p>
                       <div class="tally">${['a', 'b', 'c'].map((t) => `<span class="chip-st chip-pure ${TIER_CHIP[t][0]}">${TIER_CHIP[t][1]} · ${count(t)}</span>`).join('')}</div>`
                    : '<p>Semua kebutuhan, termasuk bagian yang dibangun di atas API dan ilustrasi konsep. Tiap modul diberi label sumbernya.</p>';
                try { localStorage.setItem('wings_mode', mode); } catch (e) { /* penyimpanan diblokir */ }
                const url = new URL(location.href);
                url.searchParams.set('mode', mode);
                history.replaceState(null, '', url);
                if (!ready) return;
                let id = target || active || 'truck';
                if (mode === 'pure' && TIER[id] !== 'a') id = 'traffic';
                activate(id, true);
                $('#mods').scrollTop = 0;
            }
            $('#modeSeg').addEventListener('click', (ev) => {
                const b = ev.target.closest('button');
                if (b && b.dataset.mode !== mode) setMode(b.dataset.mode);
            });
            $('#mods').addEventListener('click', (ev) => {
                const b = ev.target.closest('[data-full]');
                if (!b) return;
                setMode('full', b.dataset.full);
                b.closest('.mod').scrollIntoView({ block: 'nearest' });
            });

            const MODS = { truck: truckMod, traffic: trafMod, reroute: rrMod, matrix: mxMod, tour: tourMod, nav: navMod, track: trkMod, pref: prefMod, cov: covMod };
            let active = null;
            let ready = false;

            function activate(id, force = false) {
                if (!ready) return;
                if (active === id && !force) return;
                active = id;
                clearAll();
                $$('.mod').forEach((m) => m.classList.toggle('open', m.dataset.mod === id));
                // Mode murni API: modul di luar golongan A hanya menampilkan penjelasan, tidak dijalankan.
                if (mode === 'pure' && TIER[id] !== 'a') {
                    $('#info').hidden = true;
                    return;
                }
                MODS[id].enter();
                renderInfo();
            }

            $('#mods').addEventListener('click', (ev) => {
                const head = ev.target.closest('.mod-head');
                if (!head) return;
                const id = head.parentElement.dataset.mod;
                if (active === id) {
                    // Klik ulang kepala kartu: lipat saja, peta dibiarkan.
                    head.parentElement.classList.toggle('open');
                    return;
                }
                activate(id);
                head.parentElement.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });

            let saved = null;
            try { saved = localStorage.getItem('wings_mode'); } catch (e) { /* penyimpanan diblokir */ }
            setMode(new URLSearchParams(location.search).get('mode') || saved || 'full');

            map.on('load', () => {
                addLayers();
                ready = true;
                activate(mode === 'pure' ? 'traffic' : 'truck');
            });
            map.on('error', (ev) => console.warn('map error', ev.error?.message || ev));
        })();
    </script>
</body>

</html>
