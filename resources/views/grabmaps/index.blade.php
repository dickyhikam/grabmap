<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>GrabMaps Playground</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">

    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --grab-green: #00B14F;
            --grab-green-dark: #009344;
            --grab-green-soft: #E7F8EE;
            --ink: #10221A;
            --ink-2: #46564E;
            --ink-3: #84948C;
            --line: #E4E9E6;
            --surface: #FFFFFF;
            --surface-2: #F6F8F7;
            --danger: #D93025;
            --radius: 14px;
            --shadow-sm: 0 1px 2px rgba(16, 34, 26, .06), 0 1px 3px rgba(16, 34, 26, .05);
            --shadow-md: 0 4px 14px rgba(16, 34, 26, .08);
            --shadow-lg: 0 18px 50px rgba(16, 34, 26, .18);
        }

        * { box-sizing: border-box; }

        [hidden] { display: none !important; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            background: var(--surface-2);
            -webkit-font-smoothing: antialiased;
        }

        button { font-family: inherit; cursor: pointer; }
        input, select { font-family: inherit; }

        /* ============ WELCOME ============ */
        .welcome {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-y: auto;
            background:
                radial-gradient(1100px 600px at 12% -10%, rgba(0, 177, 79, .16), transparent 60%),
                radial-gradient(900px 520px at 92% 108%, rgba(0, 177, 79, .12), transparent 60%),
                #F3F7F4;
        }

        .welcome::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(16, 34, 26, .035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 34, 26, .035) 1px, transparent 1px);
            background-size: 46px 46px;
            mask-image: radial-gradient(circle at 50% 45%, #000 20%, transparent 78%);
            -webkit-mask-image: radial-gradient(circle at 50% 45%, #000 20%, transparent 78%);
            pointer-events: none;
        }

        .welcome-card {
            position: relative;
            width: 100%;
            max-width: 940px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 22px;
            box-shadow: var(--shadow-lg);
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            overflow: hidden;
        }

        .welcome-left { padding: 40px 38px; }

        .welcome-right {
            padding: 40px 38px;
            background: linear-gradient(180deg, #FBFDFC 0%, #F2F7F4 100%);
            border-left: 1px solid var(--line);
        }

        .brand-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px 6px 8px;
            border-radius: 999px;
            background: var(--grab-green-soft);
            color: var(--grab-green-dark);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .brand-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--grab-green);
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 11px;
        }

        .welcome h1 {
            margin: 18px 0 10px;
            font-size: 30px;
            line-height: 1.18;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .welcome h1 em { font-style: normal; color: var(--grab-green); }

        .welcome p.lede {
            margin: 0 0 22px;
            color: var(--ink-2);
            font-size: 14.5px;
            line-height: 1.6;
        }

        .cap-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 12px;
        }

        .cap-list li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            font-size: 13.5px;
            color: var(--ink-2);
            line-height: 1.5;
        }

        .cap-list i {
            flex: none;
            width: 30px;
            height: 30px;
            border-radius: 9px;
            background: var(--grab-green-soft);
            color: var(--grab-green-dark);
            display: grid;
            place-items: center;
            font-size: 14px;
        }

        .cap-list strong {
            display: block;
            color: var(--ink);
            font-weight: 600;
            font-size: 13.5px;
        }

        .cap-list code {
            font-size: 11.5px;
            color: var(--ink-3);
            background: var(--surface-2);
            padding: 1px 5px;
            border-radius: 5px;
        }

        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 7px;
            color: var(--ink);
        }

        .input-wrap { position: relative; }

        .input {
            width: 100%;
            height: 46px;
            padding: 0 44px 0 14px;
            border: 1.5px solid var(--line);
            border-radius: 11px;
            background: #fff;
            font-size: 14px;
            color: var(--ink);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .input:focus {
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, .14);
        }

        .input.is-invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(217, 48, 37, .12);
        }

        select.input { padding-right: 14px; appearance: auto; }

        .peek {
            position: absolute;
            right: 6px;
            top: 6px;
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            border-radius: 8px;
            color: var(--ink-3);
            font-size: 15px;
        }

        .peek:hover { background: var(--surface-2); color: var(--ink); }

        .hint {
            font-size: 12px;
            color: var(--ink-3);
            margin-top: 7px;
            line-height: 1.5;
        }

        .hint a { color: var(--grab-green-dark); font-weight: 600; }

        .check {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13px;
            color: var(--ink-2);
            cursor: pointer;
            user-select: none;
        }

        .check input { width: 16px; height: 16px; accent-color: var(--grab-green); cursor: pointer; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 46px;
            padding: 0 20px;
            border: 0;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 600;
            transition: background .15s, transform .06s, box-shadow .15s;
        }

        .btn:active { transform: translateY(1px); }

        .btn-primary {
            background: var(--grab-green);
            color: #fff;
            width: 100%;
            box-shadow: 0 6px 16px rgba(0, 177, 79, .28);
        }

        .btn-primary:hover { background: var(--grab-green-dark); }

        .btn-primary:disabled {
            background: #B9CFC3;
            box-shadow: none;
            cursor: not-allowed;
        }

        .btn-ghost {
            background: var(--surface-2);
            color: var(--ink-2);
            border: 1px solid var(--line);
        }

        .btn-ghost:hover { background: #EDF2EF; color: var(--ink); }

        .alert {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            padding: 11px 13px;
            border-radius: 10px;
            font-size: 12.5px;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .alert-error { background: #FDECEA; color: #92211A; }
        .alert-ok { background: var(--grab-green-soft); color: #04642E; }

        .privacy-note {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 11px;
            background: var(--surface-2);
            border: 1px dashed var(--line);
            font-size: 11.5px;
            color: var(--ink-2);
            line-height: 1.55;
            display: flex;
            gap: 10px;
        }

        .privacy-note i { color: var(--grab-green-dark); font-size: 14px; }

        .spin {
            display: inline-block;
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: rot .7s linear infinite;
        }

        @keyframes rot { to { transform: rotate(360deg); } }

        /* ============ APP SHELL ============ */
        .app {
            display: grid;
            grid-template-columns: 396px 1fr;
            height: 100vh;
            height: 100dvh;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border-right: 1px solid var(--line);
            min-width: 0;
            /* Item grid defaultnya min-height:auto — tanpa ini sidebar ikut
               melar setinggi isinya dan .panes tidak pernah dapat giliran scroll. */
            min-height: 0;
            overflow: hidden;
            z-index: 5;
        }

        .side-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .side-head .mark {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: var(--grab-green);
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 16px;
            flex: none;
        }

        .side-head .title {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }

        .side-head .sub {
            font-size: 11px;
            color: var(--ink-3);
            margin-top: 1px;
        }

        .key-chip {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--surface-2);
            font-size: 11.5px;
            font-weight: 600;
            color: var(--ink-2);
            font-variant-numeric: tabular-nums;
        }

        .key-chip:hover { border-color: var(--grab-green); color: var(--grab-green-dark); }
        .key-chip .bi-key-fill { color: var(--grab-green); }

        .tabs {
            display: flex;
            padding: 0 8px;
            border-bottom: 1px solid var(--line);
            gap: 2px;
        }

        .tab {
            flex: 1;
            height: 44px;
            border: 0;
            background: transparent;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink-3);
            border-bottom: 2px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tab:hover { color: var(--ink); }

        .tab.active {
            color: var(--grab-green-dark);
            border-bottom-color: var(--grab-green);
        }

        .panes {
            flex: 1 1 auto;
            min-height: 0;             /* wajib, kalau tidak flex item menolak menyusut */
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        /* Panel dibagi tiga: kontrol (boleh ikut menyusut), judul hasil yang
           tetap terlihat, dan daftar hasil yang jadi satu-satunya area scroll. */
        .pane {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }

        .pane-controls {
            flex: 0 1 auto;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 16px 16px 0;
        }

        .pane-results {
            flex: 1 1 auto;
            min-height: 150px;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 0 16px 16px;
        }

        .pane-results.pad-top { padding-top: 16px; }

        .pane-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--ink-3);
            margin: 0 0 10px;
        }

        .pane-title.mt { margin-top: 22px; }

        .row { display: flex; gap: 10px; }
        .row > * { flex: 1; min-width: 0; }

        .input-sm {
            height: 40px;
            font-size: 13px;
            padding: 0 12px;
            width: 100%;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            outline: none;
        }

        .input-sm:focus {
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, .12);
        }

        .mini-label {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--ink-2);
            margin-bottom: 6px;
            display: block;
        }

        /* Baris label + rentang yang diizinkan, supaya batas nilai terlihat
           tanpa harus mencoba mengetik angka di luar rentang dulu. */
        .field-head {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 6px;
            min-height: 17px;
        }

        .field-head .mini-label { margin-bottom: 0; }

        .range-hint {
            margin-left: auto;
            font-size: 10px;
            font-weight: 600;
            color: var(--ink-3);
            font-variant-numeric: tabular-nums;
            cursor: help;
        }

        /* Stepper: spinner bawaan browser disembunyikan dan diganti tombol -/+
           yang ukuran serta posisinya konsisten di semua platform. */
        .stepper {
            display: flex;
            align-items: stretch;
            height: 40px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
            transition: border-color .15s, box-shadow .15s;
        }

        .stepper:focus-within {
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, .12);
        }

        .step-input {
            flex: 1;
            min-width: 0;
            border: 0;
            outline: none;
            background: transparent;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            font-variant-numeric: tabular-nums;
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .step-input::-webkit-outer-spin-button,
        .step-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .step-btn {
            flex: none;
            width: 34px;
            border: 0;
            background: var(--surface-2);
            color: var(--ink-2);
            font-size: 13px;
            display: grid;
            place-items: center;
            transition: background .12s, color .12s;
        }

        .step-btn:hover:not(:disabled) {
            background: var(--grab-green-soft);
            color: var(--grab-green-dark);
        }

        .step-btn:disabled {
            color: #C8D6CE;
            cursor: not-allowed;
        }

        .stepper.clamped { animation: clampFlash .5s ease-out; }

        @keyframes clampFlash {
            0%, 100% { border-color: var(--line); }
            30%, 65% { border-color: #E8A33D; box-shadow: 0 0 0 3px rgba(232, 163, 61, .18); }
        }

        /* search + suggestions */
        .search-box { position: relative; }

        .search-box > .bi-search {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink-3);
            font-size: 14px;
            pointer-events: none;
        }

        .search-box .input-sm { padding-left: 36px; padding-right: 34px; }

        .search-box .clear {
            position: absolute;
            right: 5px;
            top: 5px;
            width: 30px;
            height: 30px;
            border: 0;
            background: transparent;
            border-radius: 7px;
            color: var(--ink-3);
        }

        .search-box .clear:hover { background: var(--surface-2); }

        /* WebKit menambahkan tombol clear sendiri pada input[type=search];
           kita sudah punya tombol clear sendiri, jadi yang bawaan disembunyikan. */
        .search-box input[type="search"]::-webkit-search-cancel-button,
        .search-box input[type="search"]::-webkit-search-decoration,
        .search-box input[type="search"]::-webkit-search-results-button,
        .search-box input[type="search"]::-webkit-search-results-decoration {
            -webkit-appearance: none;
            appearance: none;
            display: none;
        }

        .suggests {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            z-index: 30;
            max-height: 320px;
            overflow-y: auto;
        }

        .sug {
            display: flex;
            gap: 10px;
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid var(--surface-2);
            align-items: flex-start;
        }

        .sug:last-child { border-bottom: 0; }
        .sug:hover, .sug.active { background: var(--grab-green-soft); }

        .sug i {
            color: var(--grab-green);
            font-size: 14px;
            margin-top: 2px;
            flex: none;
        }

        .sug .n {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sug .a {
            font-size: 11.5px;
            color: var(--ink-3);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* result list */
        .results { display: grid; gap: 8px; }

        /* Kartu hasil: grid 2 kolom supaya badge nomor jadi kolom sendiri dan
           kolom teks punya lebar terbatas (min-width:0) — teks panjang tidak bisa
           mendorong kartu melebar atau terpotong di tepi sidebar. */
        .res {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            column-gap: 10px;
            row-gap: 5px;
            align-items: start;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 11px 12px;
            cursor: pointer;
            background: #fff;
            overflow: hidden;
            transition: border-color .12s, box-shadow .12s, background .12s;
        }

        .res:hover {
            border-color: var(--grab-green);
            box-shadow: 0 3px 10px rgba(0, 177, 79, .1);
        }

        .res.active {
            border-color: var(--grab-green);
            background: var(--grab-green-soft);
            box-shadow: 0 3px 10px rgba(0, 177, 79, .12);
        }

        .res-idx {
            grid-column: 1;
            width: 22px;
            height: 22px;
            border-radius: 7px;
            background: var(--grab-green);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: grid;
            place-items: center;
            font-variant-numeric: tabular-nums;
        }

        .res-top {
            grid-column: 2;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            min-height: 22px;
        }

        .res-name {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.35;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .res-dist {
            flex: none;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--grab-green-dark);
            background: var(--grab-green-soft);
            border-radius: 999px;
            padding: 2px 8px;
            font-variant-numeric: tabular-nums;
        }

        .res-addr {
            grid-column: 2;
            font-size: 11.5px;
            color: var(--ink-3);
            line-height: 1.5;
            min-width: 0;
            overflow-wrap: anywhere;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Kartu terpilih menampilkan alamat penuh — tidak dipotong 2 baris. */
        .res.active .res-addr {
            -webkit-line-clamp: unset;
            display: block;
            color: var(--ink-2);
        }

        .res-tags {
            grid-column: 2;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-width: 0;
        }

        .tag {
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 5px;
            background: var(--surface-2);
            color: var(--ink-2);
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tag.mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 500;
            font-size: 10px;
            color: var(--ink-3);
            background: transparent;
            border: 1px solid var(--line);
        }

        /* Judul panel + aksi di kanan (jumlah hasil, tombol bersihkan) */
        .pane-head {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: none;
            padding: 14px 16px 10px;
            border-top: 1px solid var(--line);
            background: var(--surface);
        }

        .pane-head .pane-title { margin: 0; }

        .count-pill {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            background: var(--grab-green-soft);
            color: var(--grab-green-dark);
            font-variant-numeric: tabular-nums;
        }

        .link-btn {
            margin-left: auto;
            border: 0;
            background: transparent;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--ink-3);
            padding: 4px 7px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .link-btn:hover { background: #FDECEA; color: var(--danger); }

        /* chips / segmented */
        .chips { display: flex; flex-wrap: wrap; gap: 6px; }

        .chip {
            border: 1.5px solid var(--line);
            background: #fff;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-2);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .chip:hover { border-color: #C6D5CD; }

        .chip.on {
            border-color: var(--grab-green);
            background: var(--grab-green-soft);
            color: var(--grab-green-dark);
        }

        /* stops */
        .stops { display: grid; gap: 8px; }

        .stop { display: flex; align-items: center; gap: 9px; }

        .stop-dot {
            flex: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 10px;
            font-weight: 800;
            color: #fff;
            background: var(--ink-3);
        }

        .stop:first-child .stop-dot { background: var(--grab-green); }
        .stop:last-child .stop-dot { background: #E4572E; }

        .stop .search-box { flex: 1; min-width: 0; }

        .stop-del {
            flex: none;
            width: 30px;
            height: 30px;
            border: 0;
            background: transparent;
            border-radius: 8px;
            color: var(--ink-3);
        }

        .stop-del:hover { background: #FDECEA; color: var(--danger); }

        /* route summary */
        .route-card {
            border: 1.5px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            background: #fff;
        }

        .route-card.on {
            border-color: var(--grab-green);
            background: var(--grab-green-soft);
        }

        .route-metrics {
            display: flex;
            align-items: baseline;
            gap: 10px;
            flex-wrap: wrap;
        }

        .metric-big {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .metric-sub { font-size: 12.5px; color: var(--ink-2); font-weight: 600; }

        .metric-row {
            display: flex;
            gap: 14px;
            margin-top: 8px;
            font-size: 11.5px;
            color: var(--ink-3);
            flex-wrap: wrap;
        }

        .metric-row span { display: inline-flex; align-items: center; gap: 5px; }

        /* empty / loading */
        .empty {
            text-align: center;
            padding: 34px 18px;
            color: var(--ink-3);
        }

        .empty i { font-size: 28px; opacity: .45; display: block; margin-bottom: 10px; }
        .empty p { margin: 0; font-size: 12.5px; line-height: 1.55; }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 22px;
            color: var(--ink-3);
            font-size: 12.5px;
        }

        .spin-dark {
            width: 15px;
            height: 15px;
            border: 2px solid var(--line);
            border-top-color: var(--grab-green);
            border-radius: 50%;
            animation: rot .7s linear infinite;
        }

        /* api pane */
        .req-line {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .method {
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 7px;
            border-radius: 5px;
            background: var(--grab-green);
            color: #fff;
        }

        .status-pill {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 7px;
            border-radius: 5px;
            background: var(--surface-2);
            color: var(--ink-2);
        }

        .status-pill.ok { background: var(--grab-green-soft); color: #04642E; }
        .status-pill.bad { background: #FDECEA; color: #92211A; }

        .code {
            background: #0F1A15;
            color: #D7E5DD;
            border-radius: 11px;
            padding: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11.5px;
            line-height: 1.55;
            overflow-x: auto;
            white-space: pre;
        }

        .code .k { color: #7FD8A4; }
        .code .s { color: #F2C97D; }
        .code .n { color: #8FC6F0; }
        .code .b { color: #E58C8C; }

        .url-box {
            background: var(--surface-2);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 11px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11px;
            color: var(--ink-2);
            word-break: break-all;
            line-height: 1.5;
            margin-bottom: 10px;
        }

        .side-foot {
            padding: 9px 16px;
            border-top: 1px solid var(--line);
            font-size: 10.5px;
            color: var(--ink-3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .side-foot a { color: var(--ink-2); text-decoration: none; }
        .side-foot a:hover { color: var(--grab-green-dark); }

        /* ============ MAP ============ */
        .map-wrap { position: relative; min-width: 0; }
        #map { position: absolute; inset: 0; }

        .maplibregl-ctrl-attrib.maplibregl-compact { font-size: 10px; }

        .map-tools {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 4;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: calc(100% - 24px);
        }

        .map-hint,
        .map-btn {
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(6px);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--ink-2);
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .map-hint i { color: var(--grab-green); }

        .map-btn { cursor: pointer; }

        .map-btn:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: #fff;
        }

        .map-btn i { font-size: 12px; }

        .place-card {
            position: absolute;
            left: 12px;
            bottom: 34px;
            z-index: 6;
            width: min(340px, calc(100% - 24px));
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .pc-head {
            padding: 13px 14px 11px;
            border-bottom: 1px solid var(--surface-2);
        }

        .pc-kicker {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--grab-green-dark);
        }

        .pc-name {
            font-size: 15px;
            font-weight: 700;
            margin-top: 4px;
            line-height: 1.3;
        }

        .pc-addr {
            font-size: 12px;
            color: var(--ink-2);
            margin-top: 5px;
            line-height: 1.5;
        }

        .pc-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 9px;
        }

        .pc-actions {
            display: flex;
            gap: 6px;
            padding: 10px 12px;
            background: var(--surface-2);
        }

        .pc-actions .btn {
            height: 34px;
            font-size: 12px;
            border-radius: 9px;
            flex: 1;
            padding: 0 10px;
        }

        .pc-close {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border: 0;
            background: rgba(255, 255, 255, .8);
            border-radius: 8px;
            color: var(--ink-3);
        }

        .pc-close:hover { background: var(--surface-2); color: var(--ink); }

        /* markers */
        .mk {
            width: 26px;
            height: 26px;
            border-radius: 50% 50% 50% 4px;
            transform: rotate(-45deg);
            background: var(--grab-green);
            border: 2.5px solid #fff;
            box-shadow: 0 3px 8px rgba(16, 34, 26, .32);
            display: grid;
            place-items: center;
            cursor: pointer;
        }

        .mk span {
            transform: rotate(45deg);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
        }

        .mk.pin { background: #2C6BED; }

        /* Marker yang sedang jadi titik pusat pencarian sekitar. Bentuknya cincin,
           bukan pin, supaya jelas bedanya dengan marker hasil. */
        .center-mk {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            border: 4px solid var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, .22), 0 2px 6px rgba(16, 34, 26, .3);
            pointer-events: none;
        }

        /* Kotak ringkasan titik pusat */
        .center-box {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 11px;
            background: var(--surface-2);
            margin-bottom: 10px;
        }

        .center-box.locked {
            border-color: var(--grab-green);
            background: var(--grab-green-soft);
        }

        .center-src {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--ink-2);
            min-width: 0;
        }

        .center-box.locked .center-src { color: var(--grab-green-dark); }

        .center-src i { flex: none; font-size: 12px; }

        .center-src span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .center-coord {
            display: block;
            margin-top: 5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 10.5px;
            color: var(--ink-3);
            word-break: break-all;
        }
        .mk.a { background: var(--grab-green); }
        .mk.b { background: #E4572E; }
        .mk.via { background: #7A5AF8; }

        /* toast */
        .toast-wrap {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3000;
            display: grid;
            gap: 8px;
            pointer-events: none;
        }

        .toast {
            background: #10221A;
            color: #fff;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 500;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 9px;
            animation: pop .2s ease-out;
            max-width: 460px;
        }

        .toast.err { background: #92211A; }
        .toast.ok { background: #04642E; }

        @keyframes pop {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* responsive */
        @media (max-width: 900px) {
            .welcome-card { grid-template-columns: 1fr; max-width: 520px; }
            .welcome-left { padding: 30px 26px 8px; }
            .welcome-right { padding: 24px 26px 30px; border-left: 0; border-top: 1px solid var(--line); }
            .welcome h1 { font-size: 25px; }

            .app {
                grid-template-columns: 1fr;
                grid-template-rows: 44dvh 1fr;
            }

            .map-wrap { order: -1; }
            .sidebar { border-right: 0; border-top: 1px solid var(--line); }
            .place-card { bottom: 12px; }
        }
    </style>
</head>

<body>

    <div class="toast-wrap" id="toasts"></div>

    <!-- ==================== WELCOME ==================== -->
    <div class="welcome" id="welcome">
        <div class="welcome-card">
            <div class="welcome-left">
                <span class="brand-chip">
                    <span class="brand-dot"><i class="bi bi-geo-alt-fill"></i></span>
                    GrabMaps Playground
                </span>

                <h1>Southeast Asia mapping, <em>straight from the GrabMaps API</em></h1>
                <p class="lede">
                    An interactive demo that calls the official GrabMaps endpoints straight from your
                    browser. Drop in an API key to begin — maps, search, and routing go live immediately.
                </p>

                <ul class="cap-list">
                    <li>
                        <i class="bi bi-map-fill"></i>
                        <div>
                            <strong>GrabMaps vector basemap</strong>
                            <code>urban-light-partner</code> style on the <code>karta-v3</code> tileset
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-search"></i>
                        <div>
                            <strong>Place search &amp; autocomplete</strong>
                            <code>/search-text</code> &amp; <code>/autocomplete</code>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-pin-map-fill"></i>
                        <div>
                            <strong>Nearby places &amp; reverse geocoding</strong>
                            <code>/search-nearby</code> &amp; <code>/reverse-geocode</code>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-signpost-split-fill"></i>
                        <div>
                            <strong>Multi-stop routing with ETAs</strong>
                            <code>/routes</code> — car, motorcycle, tricycle, bicycle, walking
                        </div>
                    </li>
                </ul>

                <div class="privacy-note">
                    <i class="bi bi-shield-lock-fill"></i>
                    <div>
                        Your API key is stored only in this browser and sent directly to
                        <strong>maps.grab.com</strong>. This application's server never receives,
                        logs, or relays it.
                    </div>
                </div>
            </div>

            <div class="welcome-right">
                <div id="welcomeAlert"></div>

                <form id="keyForm" autocomplete="off">
                    <div class="field">
                        <label for="keyInput">GrabMaps API key</label>
                        <div class="input-wrap">
                            <input type="password" id="keyInput" class="input" placeholder="bm_xxxxxxxxxxxxxxxx"
                                spellcheck="false" autocomplete="off" autocapitalize="off" autocorrect="off">
                            <button type="button" class="peek" id="peekBtn" title="Show / hide">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="hint">
                            No key yet? Create one in the
                            <a href="https://maps.grab.com/developer/admin" target="_blank" rel="noopener">
                                GrabMaps developer dashboard <i class="bi bi-box-arrow-up-right"></i></a>.
                        </div>
                    </div>

                    <div class="field">
                        <label for="citySelect">Start from city</label>
                        <select id="citySelect" class="input"></select>
                    </div>

                    <div class="field">
                        <label class="check">
                            <input type="checkbox" id="rememberKey" checked>
                            Remember this key on this device
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" id="startBtn">
                        <i class="bi bi-play-fill"></i> Open map
                    </button>
                </form>

                <div class="hint" style="margin-top:14px;">
                    By continuing you agree to the GrabMaps
                    <a href="https://developer.grab.com/pages/terms-of-use" target="_blank" rel="noopener">Terms of Use</a>.
                    Required attribution: <strong>&copy; Grab | &copy; OpenStreetMap contributors</strong>.
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== APP ==================== -->
    <div class="app" id="app" hidden>
        <aside class="sidebar">
            <div class="side-head">
                <div class="mark"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <div class="title">GrabMaps Playground</div>
                    <div class="sub">maps.grab.com/api/v1</div>
                </div>
                <button class="key-chip" id="keyChip" title="Change API key">
                    <i class="bi bi-key-fill"></i><span id="keyMask">—</span>
                </button>
            </div>

            <nav class="tabs">
                <button class="tab active" data-pane="search"><i class="bi bi-search"></i> Search</button>
                <button class="tab" data-pane="nearby"><i class="bi bi-pin-map"></i> Nearby</button>
                <button class="tab" data-pane="route"><i class="bi bi-signpost-split"></i> Routes</button>
                <button class="tab" data-pane="api"><i class="bi bi-braces"></i> API</button>
            </nav>

            <div class="panes">
                <!-- ---------- SEARCH ---------- -->
                <section class="pane" id="pane-search">
                    <div class="pane-controls">
                    <div class="search-box" style="margin-bottom:12px;">
                        <i class="bi bi-search"></i>
                        <input type="search" id="q" class="input-sm" placeholder="Search a place or address…"
                            autocomplete="off" spellcheck="false">
                        <button class="clear" id="qClear" hidden><i class="bi bi-x-lg"></i></button>
                        <div class="suggests" id="qSuggests" hidden></div>
                    </div>

                    <div class="row" style="margin-bottom:12px;">
                        <div>
                            <div class="field-head">
                                <label class="mini-label" for="country">Country</label>
                            </div>
                            <select id="country" class="input-sm"></select>
                        </div>
                        <div>
                            <div class="field-head">
                                <label class="mini-label" for="searchLimit">Limit</label>
                                <span class="range-hint" title="App-side guard, not an API limit — GrabMaps documents a default of 10 results but no maximum. Values outside the range are clamped before the request. The gateway may still return more than requested.">1–50</span>
                            </div>
                            <div class="stepper">
                                <button type="button" class="step-btn" data-step="down" aria-label="Decrease limit">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number" id="searchLimit" class="step-input"
                                    value="10" min="1" max="50" step="1" inputmode="numeric">
                                <button type="button" class="step-btn" data-step="up" aria-label="Increase limit">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom:14px;">
                        <div>
                            <label class="mini-label">Scenario (type)</label>
                            <select id="searchType" class="input-sm">
                                <option value="">— not set —</option>
                                <option value="pickup">pickup</option>
                                <option value="dropoff">dropoff</option>
                            </select>
                        </div>
                        <div>
                            <label class="mini-label">Location bias</label>
                            <select id="searchBias" class="input-sm">
                                <option value="center">Map center</option>
                                <option value="none">No bias</option>
                            </select>
                        </div>
                    </div>

                    </div><!-- /.pane-controls -->

                    <div class="pane-head">
                        <h3 class="pane-title">Search results</h3>
                        <span class="count-pill" id="searchCount" hidden>0</span>
                        <button class="link-btn" id="searchClear" hidden>
                            <i class="bi bi-eraser"></i> Clear
                        </button>
                    </div>
                    <div class="pane-results" id="searchResults">
                        <div class="empty">
                            <i class="bi bi-search"></i>
                            <p>Type a place name and press Enter, or pick one of the suggestions as they appear.</p>
                        </div>
                    </div>
                </section>

                <!-- ---------- NEARBY ---------- -->
                <section class="pane" id="pane-nearby" hidden>
                    <div class="pane-controls">
                    <h3 class="pane-title">Center point</h3>
                    <div class="center-box" id="nearbyCenter"></div>
                    <div class="chips" style="margin-bottom:16px;">
                        <button class="chip" id="nbUseCenter"><i class="bi bi-crosshair"></i> Use map center</button>
                        <button class="chip" id="nbUsePin"><i class="bi bi-pin-fill"></i> Use dropped pin</button>
                        <button class="chip" id="nbUnlock" hidden><i class="bi bi-unlock"></i> Follow map</button>
                    </div>

                    <div class="row" style="margin-bottom:12px;">
                        <div>
                            <div class="field-head">
                                <label class="mini-label" for="nbRadius">Radius (km)</label>
                                <span class="range-hint" title="App-side guard, not an API limit — GrabMaps documents a default of 1 km but no maximum. Values outside the range are clamped before the request.">0.1–30</span>
                            </div>
                            <div class="stepper">
                                <button type="button" class="step-btn" data-step="down" aria-label="Decrease radius">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number" id="nbRadius" class="step-input"
                                    value="1" min="0.1" max="30" step="0.1" inputmode="decimal">
                                <button type="button" class="step-btn" data-step="up" aria-label="Increase radius">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <div class="field-head">
                                <label class="mini-label" for="nbLimit">Limit</label>
                                <span class="range-hint" title="App-side guard, not an API limit — GrabMaps documents a default of 10 results but no maximum. Values outside the range are clamped before the request.">1–50</span>
                            </div>
                            <div class="stepper">
                                <button type="button" class="step-btn" data-step="down" aria-label="Decrease limit">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number" id="nbLimit" class="step-input"
                                    value="15" min="1" max="50" step="1" inputmode="numeric">
                                <button type="button" class="step-btn" data-step="up" aria-label="Increase limit">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <label class="mini-label">Sort by (rankBy)</label>
                    <div class="chips" style="margin-bottom:16px;">
                        <button class="chip on" data-rank="distance">Distance</button>
                        <button class="chip" data-rank="popularity">Popularity</button>
                    </div>

                    <button class="btn btn-primary" id="nbGo" style="height:42px;">
                        <i class="bi bi-broadcast"></i> Find places nearby
                    </button>

                    </div><!-- /.pane-controls -->

                    <div class="pane-head">
                        <h3 class="pane-title">Results</h3>
                        <span class="count-pill" id="nearbyCount" hidden>0</span>
                        <button class="link-btn" id="nearbyClear" hidden>
                            <i class="bi bi-eraser"></i> Clear
                        </button>
                    </div>
                    <div class="pane-results" id="nearbyResults">
                        <div class="empty">
                            <i class="bi bi-pin-map"></i>
                            <p>Pan the map or click it to drop a pin, then run the nearby search.</p>
                        </div>
                    </div>
                </section>

                <!-- ---------- ROUTE ---------- -->
                <section class="pane" id="pane-route" hidden>
                    <div class="pane-controls">
                    <h3 class="pane-title">Trip points</h3>
                    <div class="stops" id="stops"></div>

                    <div class="chips" style="margin-top:10px;">
                        <button class="chip" id="addStop"><i class="bi bi-plus-lg"></i> Add stop</button>
                        <button class="chip" id="swapStops"><i class="bi bi-arrow-down-up"></i> Reverse</button>
                    </div>

                    <h3 class="pane-title mt">Mode (profile)</h3>
                    <div class="chips" id="profiles">
                        <button class="chip on" data-profile="driving"><i class="bi bi-car-front-fill"></i> Car</button>
                        <button class="chip" data-profile="motorcycle"><i class="bi bi-scooter"></i> Motorcycle</button>
                        <button class="chip" data-profile="tricycle"><i class="bi bi-truck"></i> Tricycle</button>
                        <button class="chip" data-profile="cycling"><i class="bi bi-bicycle"></i> Bicycle</button>
                        <button class="chip" data-profile="walking"><i class="bi bi-person-walking"></i> Walking</button>
                    </div>

                    <h3 class="pane-title mt">Avoid</h3>
                    <div class="chips" id="avoids">
                        <button class="chip" data-avoid="tolls"><i class="bi bi-cash-coin"></i> Tolls</button>
                        <button class="chip" data-avoid="highways"><i class="bi bi-signpost-2"></i> Highways</button>
                    </div>

                    <div class="row" style="margin-top:14px;">
                        <div>
                            <label class="mini-label">Alternatives</label>
                            <select id="alts" class="input-sm">
                                <option value="">None</option>
                                <option value="1">1 route</option>
                                <option value="2">2 routes</option>
                            </select>
                        </div>
                        <div>
                            <label class="mini-label">Overview</label>
                            <select id="overview" class="input-sm">
                                <option value="full">full</option>
                                <option value="simplified">simplified</option>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-primary" id="routeGo" style="height:42px;margin-top:14px;">
                        <i class="bi bi-signpost-split-fill"></i> Calculate route
                    </button>

                    </div><!-- /.pane-controls -->

                    <div class="pane-head">
                        <h3 class="pane-title">Route results</h3>
                        <button class="link-btn" id="routeClear" hidden>
                            <i class="bi bi-eraser"></i> Clear
                        </button>
                    </div>
                    <div class="pane-results" id="routeResults">
                        <div class="empty">
                            <i class="bi bi-signpost-split"></i>
                            <p>Set an origin and a destination — clicking the map and choosing “Set as origin / destination” works too.</p>
                        </div>
                    </div>
                </section>

                <!-- ---------- API ---------- -->
                <section class="pane" id="pane-api" hidden>
                    <div class="pane-controls">
                    <h3 class="pane-title">Last request</h3>
                    <div class="req-line">
                        <span class="method">GET</span>
                        <span class="status-pill" id="apiStatus">none yet</span>
                        <span class="status-pill" id="apiTime">—</span>
                        <span class="status-pill" id="apiCount">0 requests</span>
                    </div>
                    <div class="url-box" id="apiUrl">—</div>
                    <div class="chips" style="margin-bottom:14px;">
                        <button class="chip" id="copyCurl"><i class="bi bi-terminal"></i> Copy cURL</button>
                        <button class="chip" id="copyJson"><i class="bi bi-clipboard"></i> Copy JSON</button>
                    </div>

                    </div><!-- /.pane-controls -->

                    <div class="pane-head">
                        <h3 class="pane-title">Response</h3>
                    </div>
                    <div class="pane-results pad-top">
                        <pre class="code" id="apiBody">// Run a search or a route request to see the raw response here.</pre>
                    </div>
                </section>
            </div>

            <div class="side-foot">
                <span>&copy; Grab | &copy; OpenStreetMap contributors</span>
                <a href="https://maps.grab.com/developer/documentation" target="_blank" rel="noopener">
                    Documentation <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </aside>

        <main class="map-wrap">
            <div id="map"></div>
            <div class="map-tools">
                <div class="map-hint"><i class="bi bi-cursor-fill"></i> Click the map to reverse geocode</div>
                <button class="map-btn" id="clearMap" hidden>
                    <i class="bi bi-eraser-fill"></i> Clear map
                </button>
            </div>
            <div class="place-card" id="placeCard" hidden></div>
        </main>
    </div>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
    <script>
        (function () {
            'use strict';

            /* =====================================================
             * GrabMaps constants
             * Every endpoint follows maps.grab.com/developer/documentation
             * ===================================================== */
            const BASE = 'https://maps.grab.com';
            const STYLE_NAME = 'urban-light-partner';
            const TILESET = 'karta-v3';
            const LS_KEY = 'grabmaps.playground.key';
            const LS_CITY = 'grabmaps.playground.city';

            const CITIES = [
                { code: 'IDN', name: 'Jakarta, Indonesia', center: [106.8272, -6.1751] },
                { code: 'IDN', name: 'Surabaya, Indonesia', center: [112.7521, -7.2575] },
                { code: 'IDN', name: 'Bali (Denpasar), Indonesia', center: [115.2126, -8.6705] },
                { code: 'SGP', name: 'Singapore', center: [103.8198, 1.3521] },
                { code: 'MYS', name: 'Kuala Lumpur, Malaysia', center: [101.6869, 3.1390] },
                { code: 'THA', name: 'Bangkok, Thailand', center: [100.5018, 13.7563] },
                { code: 'VNM', name: 'Ho Chi Minh City, Vietnam', center: [106.6297, 10.8231] },
                { code: 'PHL', name: 'Manila, Philippines', center: [120.9842, 14.5995] },
                { code: 'KHM', name: 'Phnom Penh, Cambodia', center: [104.9282, 11.5564] },
                { code: 'MMR', name: 'Yangon, Myanmar', center: [96.1951, 16.8661] }
            ];

            const COUNTRIES = [
                { code: '', label: '— not set —' },
                { code: 'IDN', label: 'Indonesia (IDN)' },
                { code: 'SGP', label: 'Singapore (SGP)' },
                { code: 'MYS', label: 'Malaysia (MYS)' },
                { code: 'THA', label: 'Thailand (THA)' },
                { code: 'VNM', label: 'Vietnam (VNM)' },
                { code: 'PHL', label: 'Philippines (PHL)' },
                { code: 'KHM', label: 'Cambodia (KHM)' },
                { code: 'MMR', label: 'Myanmar (MMR)' }
            ];

            /* ===================== State ===================== */
            let apiKey = '';
            let map = null;
            let startCity = CITIES[0];

            let searchMarkers = [];
            let markerOwner = null;      // 'search' | 'nearby' — daftar mana yang memiliki marker saat ini
            let pinMarker = null;
            let pinLngLat = null;
            let nearbyCenter = null;
            let nearbyCenterLabel = '';
            let centerMarker = null;
            let rankBy = 'distance';
            let profile = 'driving';
            const avoidSet = new Set();
            let stops = [
                { label: 'A', text: '', lngLat: null },
                { label: 'B', text: '', lngLat: null }
            ];
            let stopMarkers = [];
            let lastRoutes = [];
            let activeRouteIdx = 0;
            let reqCount = 0;
            let lastResponse = null;
            let lastUrl = '';

            /* ===================== Util ===================== */
            const $ = (sel, root) => (root || document).querySelector(sel);
            const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, c => (
                    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
                ));
            }

            function maskKey(k) {
                if (!k) return '—';
                if (k.length <= 10) return k.slice(0, 3) + '…';
                return k.slice(0, 6) + '…' + k.slice(-4);
            }

            function toast(msg, kind) {
                const el = document.createElement('div');
                el.className = 'toast' + (kind ? ' ' + kind : '');
                const icon = kind === 'err' ? 'bi-exclamation-triangle-fill'
                    : kind === 'ok' ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
                el.innerHTML = '<i class="bi ' + icon + '"></i><span>' + esc(msg) + '</span>';
                $('#toasts').appendChild(el);
                setTimeout(() => el.remove(), 4200);
            }

            function fmtDist(m) {
                if (m == null) return '—';
                return m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(m < 10000 ? 2 : 1) + ' km';
            }

            function fmtDur(s) {
                if (s == null) return '—';
                s = Math.round(s);
                const h = Math.floor(s / 3600);
                const m = Math.round((s % 3600) / 60);
                if (h > 0) return h + ' hr ' + m + ' min';
                if (m > 0) return m + ' min';
                return s + ' sec';
            }

            function haversine(a, b) {
                const R = 6371000, rad = Math.PI / 180;
                const dLat = (b[1] - a[1]) * rad, dLng = (b[0] - a[0]) * rad;
                const s = Math.sin(dLat / 2) ** 2 +
                    Math.cos(a[1] * rad) * Math.cos(b[1] * rad) * Math.sin(dLng / 2) ** 2;
                return 2 * R * Math.asin(Math.sqrt(s));
            }

            /* ===================== GrabMaps HTTP layer ===================== */
            /**
             * Every request uses the `Authorization: Bearer <key>` header per the documentation.
             * The key never lands in the query string, so it cannot leak via logs or Referer.
             */
            function buildUrl(path, params) {
                const qs = new URLSearchParams();
                Object.keys(params || {}).forEach(k => {
                    const v = params[k];
                    if (v === undefined || v === null || v === '') return;
                    if (Array.isArray(v)) v.forEach(x => qs.append(k, x));
                    else qs.set(k, v);
                });
                const q = qs.toString();
                return BASE + path + (q ? '?' + q : '');
            }

            async function grabGet(path, params, signal) {
                const url = buildUrl(path, params);
                const t0 = performance.now();
                let res;

                try {
                    res = await fetch(url, {
                        headers: { Authorization: 'Bearer ' + apiKey },
                        signal: signal
                    });
                } catch (e) {
                    if (e.name === 'AbortError') throw e;
                    // Network/CORS failure: there is no HTTP response at all.
                    recordRequest(url, null, performance.now() - t0, { error: 'network_or_cors_failure' });
                    throw new Error('Could not reach GrabMaps (network or CORS failure).');
                }

                const ms = performance.now() - t0;
                const text = await res.text();
                let body = null;
                try { body = text ? JSON.parse(text) : null; } catch (_) { body = { raw: text }; }

                recordRequest(url, res.status, ms, body);

                if (!res.ok) throw new Error(httpMessage(res, body));

                // Some Places endpoints carry a response-level status, separate from the HTTP status.
                if (body && body.status && body.status.code && body.status.code !== 'SUCCESS') {
                    throw new Error('GrabMaps rejected the request: ' + body.status.code);
                }
                return body;
            }

            function httpMessage(res, body) {
                const ret = body && (body.retMsg || body.retCode)
                    ? ' (' + [body.retCode, body.retMsg].filter(Boolean).join(' — ') + ')'
                    : '';
                switch (res.status) {
                    case 400: return 'Invalid request' + ret;
                    case 401: return 'API key is invalid or has been revoked (401)';
                    case 403: return 'Access denied — check the key quota or permissions (403)' + ret;
                    case 404: return 'Endpoint or data not found (404)';
                    case 424: return 'A GrabMaps upstream service failed (424)' + ret;
                    case 429: {
                        const ra = res.headers.get('Retry-After');
                        return 'Rate limit exceeded (429)' + (ra ? ', retry in ' + ra + ' seconds' : '');
                    }
                    default: return 'GrabMaps returned HTTP ' + res.status + ret;
                }
            }

            /* ===================== API panel ===================== */
            function recordRequest(url, status, ms, body) {
                reqCount++;
                lastUrl = url;
                lastResponse = body;

                const st = $('#apiStatus');
                st.textContent = status === null ? 'network error' : status;
                st.className = 'status-pill ' + (status === null ? 'bad' : (status < 400 ? 'ok' : 'bad'));

                $('#apiTime').textContent = Math.round(ms) + ' ms';
                $('#apiCount').textContent = reqCount + (reqCount === 1 ? ' request' : ' requests');
                $('#apiUrl').textContent = url;
                $('#apiBody').innerHTML = highlightJson(body);
            }

            function highlightJson(obj) {
                if (obj === null || obj === undefined) return '// no body';
                let json;
                try { json = JSON.stringify(obj, null, 2); } catch (_) { return '// body could not be serialised'; }
                if (json.length > 200000) json = json.slice(0, 200000) + '\n… (truncated)';
                return esc(json).replace(
                    /(&quot;(?:\\.|[^&\\])*&quot;)(\s*:)?|\b(true|false|null)\b|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)/g,
                    (m, str, colon, bool, num) => {
                        if (str) return '<span class="' + (colon ? 'k' : 's') + '">' + str + '</span>' + (colon || '');
                        if (bool) return '<span class="b">' + bool + '</span>';
                        return '<span class="n">' + num + '</span>';
                    }
                );
            }

            /* ===================== Map ===================== */
            async function loadStyle() {
                const res = await fetch(BASE + '/api/v1/styles/' + STYLE_NAME + '/descriptor', {
                    headers: { Authorization: 'Bearer ' + apiKey }
                });
                if (!res.ok) throw new Error('Failed to load the map style (HTTP ' + res.status + ')');
                const style = await res.json();

                // The descriptor points at Grab's internal tile host, which sends no CORS headers.
                // Repoint it at the documented public endpoint: /api/v1/tiles/{Tileset}/{Z}/{X}/{Y}
                const src = style.sources && style.sources.grabmaptiles;
                if (src) src.tiles = [BASE + '/api/v1/tiles/' + TILESET + '/{z}/{x}/{y}'];

                return style;
            }

            async function initMap() {
                const style = await loadStyle();

                map = new maplibregl.Map({
                    container: 'map',
                    style: style,
                    center: startCity.center,
                    zoom: 12.5,
                    attributionControl: false,
                    transformRequest: (url) => {
                        // Every GrabMaps resource (tiles, sprites, glyphs) needs the Bearer token.
                        if (url.indexOf(BASE) === 0) {
                            return { url: url, headers: { Authorization: 'Bearer ' + apiKey } };
                        }
                        return { url: url };
                    }
                });

                map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');
                map.addControl(new maplibregl.GeolocateControl({
                    positionOptions: { enableHighAccuracy: true },
                    trackUserLocation: true
                }), 'top-right');
                map.addControl(new maplibregl.ScaleControl({ maxWidth: 110, unit: 'metric' }), 'bottom-right');
                map.addControl(new maplibregl.AttributionControl({
                    compact: true,
                    customAttribution: '&copy; Grab | &copy; OpenStreetMap contributors'
                }), 'bottom-right');

                map.on('load', () => {
                    // Lingkaran radius digambar lebih dulu supaya garis rute ada di atasnya.
                    map.addSource('nearby-radius', { type: 'geojson', data: emptyFC() });
                    map.addLayer({
                        id: 'nearby-radius-fill',
                        type: 'fill',
                        source: 'nearby-radius',
                        paint: { 'fill-color': '#00B14F', 'fill-opacity': .07 }
                    });
                    map.addLayer({
                        id: 'nearby-radius-line',
                        type: 'line',
                        source: 'nearby-radius',
                        paint: {
                            'line-color': '#00B14F',
                            'line-width': 1.5,
                            'line-opacity': .65,
                            'line-dasharray': [2, 2]
                        }
                    });

                    map.addSource('route-line', { type: 'geojson', data: emptyFC() });
                    map.addLayer({
                        id: 'route-alt',
                        type: 'line',
                        source: 'route-line',
                        filter: ['!=', ['get', 'active'], true],
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                        paint: { 'line-color': '#9AA8A1', 'line-width': 5, 'line-opacity': .75 }
                    });
                    map.addLayer({
                        id: 'route-casing',
                        type: 'line',
                        source: 'route-line',
                        filter: ['==', ['get', 'active'], true],
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                        paint: { 'line-color': '#00803A', 'line-width': 9 }
                    });
                    map.addLayer({
                        id: 'route-main',
                        type: 'line',
                        source: 'route-line',
                        filter: ['==', ['get', 'active'], true],
                        layout: { 'line-cap': 'round', 'line-join': 'round' },
                        paint: { 'line-color': '#00B14F', 'line-width': 5.5 }
                    });

                    map.on('click', 'route-alt', (e) => {
                        const i = e.features[0].properties.idx;
                        if (i !== undefined) selectRoute(Number(i));
                    });
                    map.on('mouseenter', 'route-alt', () => map.getCanvas().style.cursor = 'pointer');
                    map.on('mouseleave', 'route-alt', () => map.getCanvas().style.cursor = '');

                    updateNearbyCenterLabel();
                });

                map.on('click', (e) => {
                    // Clicks on an alternative route line belong to the layer handler, not reverse geocoding.
                    if (map.getLayer('route-alt') &&
                        map.queryRenderedFeatures(e.point, { layers: ['route-alt'] }).length) return;
                    dropPin([e.lngLat.lng, e.lngLat.lat]);
                });

                map.on('moveend', updateNearbyCenterLabel);

                map.on('error', (e) => {
                    const msg = (e && e.error && e.error.message) || '';
                    if (/401|403/.test(msg)) toast('Tiles rejected — check your API key permissions.', 'err');
                });
            }

            function emptyFC() { return { type: 'FeatureCollection', features: [] }; }

            /* ===================== Pin & reverse geocode ===================== */
            function makeMarkerEl(cls, label) {
                const el = document.createElement('div');
                el.className = 'mk ' + cls;
                el.innerHTML = '<span>' + esc(label) + '</span>';
                return el;
            }

            async function dropPin(lngLat) {
                pinLngLat = lngLat;
                if (pinMarker) pinMarker.remove();
                pinMarker = new maplibregl.Marker({
                    element: makeMarkerEl('pin', '•'),
                    draggable: true,
                    offset: [0, -10]
                }).setLngLat(lngLat).addTo(map);

                pinMarker.on('dragend', () => {
                    const p = pinMarker.getLngLat();
                    pinLngLat = [p.lng, p.lat];
                    if (nearbyCenter) setNearbyCenter(pinLngLat, 'Dropped pin');
                    reverseGeocode(pinLngLat);
                });

                pinMarker.getElement().onclick = (ev) => {
                    ev.stopPropagation();
                    setNearbyCenter(pinLngLat, 'Dropped pin');
                    toast('Center point moved to the pin.', 'ok');
                };

                updateClearMapBtn();
                reverseGeocode(lngLat);
            }

            async function reverseGeocode(lngLat) {
                showPlaceCard({ loading: true, lngLat: lngLat });
                try {
                    const data = await grabGet('/api/v1/reverse-geocode', {
                        location: lngLat[1].toFixed(6) + ',' + lngLat[0].toFixed(6),
                        limit: 1
                    });
                    const place = (data && data.places && data.places[0]) || null;
                    showPlaceCard({ place: place, lngLat: lngLat });
                } catch (e) {
                    if (e.name === 'AbortError') return;
                    showPlaceCard({ error: e.message, lngLat: lngLat });
                    toast(e.message, 'err');
                }
            }

            function showPlaceCard(opts) {
                const card = $('#placeCard');
                card.hidden = false;
                const coord = opts.lngLat
                    ? opts.lngLat[1].toFixed(6) + ', ' + opts.lngLat[0].toFixed(6)
                    : '';

                if (opts.loading) {
                    card.innerHTML = '<div class="loading"><span class="spin-dark"></span> Looking up address…</div>';
                    return;
                }

                if (opts.error) {
                    card.innerHTML =
                        '<button class="pc-close" data-close><i class="bi bi-x-lg"></i></button>' +
                        '<div class="pc-head"><div class="pc-kicker">Reverse geocode failed</div>' +
                        '<div class="pc-addr">' + esc(opts.error) + '</div>' +
                        '<div class="pc-meta"><span class="tag">' + esc(coord) + '</span></div></div>';
                    bindPlaceCard(opts.lngLat, null);
                    return;
                }

                const p = opts.place;
                const name = (p && (p.name || p.short_name)) || 'Unnamed location';
                const addr = (p && p.formatted_address) || 'No address available for this point.';

                const tags = [];
                if (p && p.business_type) tags.push(p.business_type);
                if (p && p.postcode) tags.push(p.postcode);
                if (p && p.country_code) tags.push(p.country_code);
                tags.push(coord);

                card.innerHTML =
                    '<button class="pc-close" data-close><i class="bi bi-x-lg"></i></button>' +
                    '<div class="pc-head">' +
                    '<div class="pc-kicker">Reverse geocode</div>' +
                    '<div class="pc-name">' + esc(name) + '</div>' +
                    '<div class="pc-addr">' + esc(addr) + '</div>' +
                    '<div class="pc-meta">' + tags.map(t => '<span class="tag">' + esc(t) + '</span>').join('') + '</div>' +
                    '</div>' +
                    '<div class="pc-actions">' +
                    '<button class="btn btn-ghost" data-as="0"><i class="bi bi-record-circle"></i> Set as origin</button>' +
                    '<button class="btn btn-ghost" data-as="last"><i class="bi bi-geo-alt-fill"></i> Set as destination</button>' +
                    '<button class="btn btn-ghost" data-nearby title="Search around here"><i class="bi bi-broadcast"></i></button>' +
                    '</div>';

                bindPlaceCard(opts.lngLat, p);
            }

            function bindPlaceCard(lngLat, place) {
                const card = $('#placeCard');
                const label = (place && (place.name || place.formatted_address)) ||
                    (lngLat ? lngLat[1].toFixed(5) + ', ' + lngLat[0].toFixed(5) : '');

                const closeBtn = $('[data-close]', card);
                if (closeBtn) closeBtn.onclick = () => { card.hidden = true; };

                $$('[data-as]', card).forEach(btn => {
                    btn.onclick = () => {
                        const idx = btn.dataset.as === 'last' ? stops.length - 1 : 0;
                        stops[idx].lngLat = lngLat;
                        stops[idx].text = label;
                        renderStops();
                        switchPane('route');
                        toast('Point ' + stops[idx].label + ' updated.', 'ok');
                    };
                });

                const nb = $('[data-nearby]', card);
                if (nb) nb.onclick = () => {
                    setNearbyCenter(lngLat, label);
                    switchPane('nearby');
                    runNearby();
                };
            }

            /* ===================== Autocomplete ===================== */
            function attachAutocomplete(inputEl, boxEl, onPick) {
                let controller = null;
                let seq = 0;
                let timer = null;
                let items = [];
                let cursor = -1;

                function close() { boxEl.hidden = true; cursor = -1; }

                function render() {
                    if (!items.length) { close(); return; }
                    boxEl.innerHTML = items.map((p, i) =>
                        '<div class="sug' + (i === cursor ? ' active' : '') + '" data-i="' + i + '">' +
                        '<i class="bi bi-geo-alt-fill"></i>' +
                        '<div style="min-width:0;flex:1">' +
                        '<div class="n">' + esc(p.name || p.short_name || '—') + '</div>' +
                        '<div class="a">' + esc(p.formatted_address || '') + '</div>' +
                        '</div></div>'
                    ).join('');
                    boxEl.hidden = false;
                    $$('.sug', boxEl).forEach(el => {
                        el.onmousedown = (ev) => {
                            ev.preventDefault();
                            pick(items[Number(el.dataset.i)]);
                        };
                    });
                }

                function pick(p) {
                    close();
                    onPick(p);
                }

                async function query(kw) {
                    if (controller) controller.abort();
                    controller = new AbortController();
                    const mine = ++seq;

                    const bias = biasLocation();
                    try {
                        const data = await grabGet('/api/v1/autocomplete', {
                            keyword: kw,
                            country: $('#country').value || '',
                            location: bias,
                            limit: 6
                        }, controller.signal);

                        if (mine !== seq) return;               // never let a stale response overwrite a newer one
                        items = (data && data.places) || [];
                        render();
                    } catch (e) {
                        if (e.name === 'AbortError' || mine !== seq) return;
                        close();
                    }
                }

                inputEl.addEventListener('input', () => {
                    const kw = inputEl.value.trim();
                    clearTimeout(timer);
                    if (kw.length < 2) { if (controller) controller.abort(); close(); return; }
                    timer = setTimeout(() => query(kw), 220);
                });

                inputEl.addEventListener('keydown', (e) => {
                    if (boxEl.hidden || !items.length) return;
                    if (e.key === 'ArrowDown') { e.preventDefault(); cursor = (cursor + 1) % items.length; render(); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); cursor = (cursor - 1 + items.length) % items.length; render(); }
                    else if (e.key === 'Enter' && cursor >= 0) { e.preventDefault(); pick(items[cursor]); }
                    else if (e.key === 'Escape') close();
                });

                inputEl.addEventListener('blur', () => setTimeout(close, 120));
            }

            function biasLocation() {
                if ($('#searchBias') && $('#searchBias').value === 'none') return '';
                if (!map) return startCity.center[1] + ',' + startCity.center[0];
                const c = map.getCenter();
                return c.lat.toFixed(6) + ',' + c.lng.toFixed(6);
            }

            function placeLngLat(p) {
                if (!p || !p.location) return null;
                return [p.location.longitude, p.location.latitude];
            }

            /* ===================== Pane: Search ===================== */
            function initSearchPane() {
                const sel = $('#country');
                sel.innerHTML = COUNTRIES.map(c =>
                    '<option value="' + c.code + '"' + (c.code === startCity.code ? ' selected' : '') + '>' +
                    esc(c.label) + '</option>'
                ).join('');

                const input = $('#q');
                attachAutocomplete(input, $('#qSuggests'), (p) => {
                    input.value = p.name || p.formatted_address || '';
                    $('#qClear').hidden = !input.value;
                    renderSearchResults([p]);
                    focusPlace(p, 0);
                });

                input.addEventListener('input', () => { $('#qClear').hidden = !input.value; });
                input.addEventListener('keydown', (e) => {
                    // If autocomplete already handled Enter (picking a suggestion), don't search again.
                    if (e.key === 'Enter' && !e.defaultPrevented) {
                        e.preventDefault();
                        runSearch();
                    }
                });

                $('#qClear').onclick = () => {
                    input.value = '';
                    $('#qClear').hidden = true;
                    clearList('search');
                    input.focus();
                };
            }

            async function runSearch() {
                const kw = $('#q').value.trim();
                if (!kw) return;

                const country = $('#country').value;
                const bias = biasLocation();
                if (!country && !bias) {
                    toast('Set a country or enable location bias — search-text requires one of them.', 'err');
                    return;
                }

                $('#searchResults').innerHTML = loadingBlock('Searching places…');
                try {
                    const data = await grabGet('/api/v1/search-text', {
                        keyword: kw,
                        country: country,
                        location: bias,
                        limit: readNum('searchLimit'),
                        type: $('#searchType').value
                    });
                    renderSearchResults((data && data.places) || []);
                } catch (e) {
                    $('#searchResults').innerHTML = emptyBlock('bi-exclamation-triangle', e.message);
                    toast(e.message, 'err');
                }
            }

            function renderSearchResults(places) {
                clearSearchMarkers();
                const box = $('#searchResults');

                if (!places.length) {
                    box.innerHTML = emptyBlock('bi-inbox', 'No matching places. Try another keyword or change the country.');
                    setListMeta('search', 0);
                    return;
                }

                box.innerHTML = '<div class="results">' + places.map((p, i) => placeRow(p, i)).join('') + '</div>';
                bindResultRows(box, places);
                setListMeta('search', places.length);

                const bounds = new maplibregl.LngLatBounds();
                places.forEach((p, i) => {
                    const ll = placeLngLat(p);
                    if (!ll) return;
                    bounds.extend(ll);
                    const mk = new maplibregl.Marker({ element: makeMarkerEl('', String(i + 1)), offset: [0, -10] })
                        .setLngLat(ll).addTo(map);
                    mk.getElement().onclick = () => pickMarker(p, i, ll);
                    searchMarkers.push(mk);
                });

                markerOwner = 'search';
                updateClearMapBtn();

                if (!bounds.isEmpty()) {
                    map.fitBounds(bounds, { padding: 70, maxZoom: 16, duration: 700 });
                }
            }

            function placeRow(p, i, distanceKm) {
                // Badge nomor duduk di kolomnya sendiri; alamat dan tag mewarisi kolom teks,
                // jadi tidak perlu padding-left manual dan tidak bisa melimpah keluar kartu.
                const tags = [];
                if (p.business_type) tags.push({ text: p.business_type });
                if (p.postcode) tags.push({ text: p.postcode });
                const area = pickArea(p);
                if (area) tags.push({ text: area });
                if (p.poi_id) tags.push({ text: p.poi_id, mono: true });

                return '<div class="res" data-i="' + i + '">' +
                    '<span class="res-idx">' + (i + 1) + '</span>' +
                    '<div class="res-top">' +
                    '<span class="res-name">' + esc(p.name || p.short_name || 'Unnamed place') + '</span>' +
                    (distanceKm != null ? '<span class="res-dist">' + fmtDist(distanceKm * 1000) + '</span>' : '') +
                    '</div>' +
                    '<div class="res-addr">' + esc(p.formatted_address || 'No address returned') + '</div>' +
                    (tags.length
                        ? '<div class="res-tags">' + tags.map(t =>
                            '<span class="tag' + (t.mono ? ' mono' : '') + '" title="' + esc(t.text) + '">' +
                            esc(t.text) + '</span>').join('') + '</div>'
                        : '') +
                    '</div>';
            }

            /** Ambil area administratif paling spesifik untuk jadi tag konteks. */
            function pickArea(p) {
                const areas = p.administrative_areas;
                if (!Array.isArray(areas) || !areas.length) return '';
                const wanted = ['Neighborhood', 'Municipality', 'SubRegion'];
                for (const type of wanted) {
                    const hit = areas.find(a => a && a.type === type && a.name);
                    if (hit) return hit.name;
                }
                return (areas[0] && areas[0].name) || '';
            }

            function bindResultRows(box, places) {
                $$('.res', box).forEach(el => {
                    el.onclick = () => {
                        $$('.res', box).forEach(x => x.classList.remove('active'));
                        el.classList.add('active');
                        focusPlace(places[Number(el.dataset.i)], Number(el.dataset.i));
                    };
                });
            }

            /**
             * Klik marker di peta: fokuskan tempatnya sekaligus jadikan titik pusat
             * pencarian sekitar. Klik baris di daftar hasil tetap hanya memfokuskan,
             * supaya menelusuri hasil tidak diam-diam memindahkan titik pusat.
             */
            function pickMarker(p, i, lngLat) {
                focusPlace(p, i);
                setNearbyCenter(lngLat, p.name || p.short_name || p.formatted_address || 'Selected place');
                highlightResultRow(i);
            }

            function highlightResultRow(i) {
                const box = markerOwner === 'nearby' ? $('#nearbyResults') : $('#searchResults');
                $$('.res', box).forEach(el => el.classList.toggle('active', Number(el.dataset.i) === i));
                const row = $('.res[data-i="' + i + '"]', box);
                if (row && row.scrollIntoView) row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            function focusPlace(p, i) {
                const ll = placeLngLat(p);
                if (!ll) return;
                map.flyTo({ center: ll, zoom: Math.max(map.getZoom(), 16), duration: 800 });
                showPlaceCard({ place: p, lngLat: ll });
            }

            function clearSearchMarkers() {
                searchMarkers.forEach(m => m.remove());
                searchMarkers = [];
                markerOwner = null;
            }

            /* ---------- Clearing ---------- */

            /** Perbarui pill jumlah + tombol Clear untuk satu daftar hasil. */
            function setListMeta(which, count) {
                const pill = $('#' + which + 'Count');
                const btn = $('#' + which + 'Clear');
                pill.textContent = count;
                pill.hidden = count === 0;
                btn.hidden = count === 0;
                updateClearMapBtn();
            }

            /** Daftar hasil pencarian dan sekitar berbagi satu set marker, jadi hanya
             *  pemilik marker saat ini yang boleh menghapusnya. */
            function clearList(which) {
                const isSearch = which === 'search';
                if (markerOwner === which) clearSearchMarkers();

                $('#' + which + 'Results').innerHTML = isSearch
                    ? emptyBlock('bi-search',
                        'Type a place name and press Enter, or pick one of the suggestions as they appear.')
                    : emptyBlock('bi-pin-map',
                        'Pan the map or click it to drop a pin, then run the nearby search.');

                setListMeta(which, 0);
            }

            function clearPin() {
                if (pinMarker) { pinMarker.remove(); pinMarker = null; }
                pinLngLat = null;
                $('#placeCard').hidden = true;
                updateClearMapBtn();
            }

            function clearRouteResults() {
                lastRoutes = [];
                activeRouteIdx = 0;
                const src = map && map.getSource('route-line');
                if (src) src.setData(emptyFC());
                $('#routeResults').innerHTML = emptyBlock('bi-signpost-split',
                    'Set an origin and a destination — clicking the map and choosing “Set as origin / destination” works too.');
                $('#routeClear').hidden = true;
                updateClearMapBtn();
            }

            /** Bersihkan semua yang tergambar: marker hasil, pin, garis rute, dan titik rute. */
            function clearEverything() {
                clearSearchMarkers();
                clearList('search');
                clearList('nearby');
                clearPin();
                clearRouteResults();

                stops.forEach(st => { st.lngLat = null; st.text = ''; });
                stops = stops.slice(0, 2);
                renderStops();

                unlockNearbyCenter();
                updateClearMapBtn();
                toast('Map cleared.', 'ok');
            }

            function hasAnythingOnMap() {
                return searchMarkers.length > 0 ||
                    pinMarker !== null ||
                    centerMarker !== null ||
                    lastRoutes.length > 0 ||
                    stops.some(st => st.lngLat);
            }

            function updateClearMapBtn() {
                const btn = $('#clearMap');
                if (btn) btn.hidden = !hasAnythingOnMap();
            }

            function initClearControls() {
                $('#searchClear').onclick = () => clearList('search');
                $('#nearbyClear').onclick = () => clearList('nearby');
                $('#routeClear').onclick = clearRouteResults;
                $('#clearMap').onclick = clearEverything;
            }

            function emptyBlock(icon, text) {
                return '<div class="empty"><i class="bi ' + icon + '"></i><p>' + esc(text) + '</p></div>';
            }

            function loadingBlock(text) {
                return '<div class="loading"><span class="spin-dark"></span> ' + esc(text) + '</div>';
            }

            /* ===================== Pane: Nearby ===================== */
            function initNearbyPane() {
                $('#nbUseCenter').onclick = () => {
                    const c = map.getCenter();
                    setNearbyCenter([c.lng, c.lat], 'Map center');
                };
                $('#nbUsePin').onclick = () => {
                    if (!pinLngLat) { toast('No pin yet — click the map first.', 'err'); return; }
                    setNearbyCenter(pinLngLat, 'Dropped pin');
                };
                $('#nbUnlock').onclick = unlockNearbyCenter;

                // Lingkaran radius ikut berubah saat angka radius diubah.
                $('#nbRadius').addEventListener('input', drawNearbyRadius);
                $$('[data-rank]').forEach(btn => {
                    btn.onclick = () => {
                        $$('[data-rank]').forEach(b => b.classList.remove('on'));
                        btn.classList.add('on');
                        rankBy = btn.dataset.rank;
                    };
                });
                $('#nbGo').onclick = runNearby;
            }

            /**
             * Kunci titik pusat ke satu koordinat — dipakai saat marker di peta diklik,
             * saat pin dipindahkan, atau lewat tombol di panel.
             */
            function setNearbyCenter(lngLat, label) {
                nearbyCenter = lngLat;
                nearbyCenterLabel = label || '';

                if (centerMarker) centerMarker.remove();
                const el = document.createElement('div');
                el.className = 'center-mk';
                centerMarker = new maplibregl.Marker({ element: el }).setLngLat(lngLat).addTo(map);

                updateNearbyCenterLabel();
                updateClearMapBtn();
            }

            /** Lepas kunci — titik pusat kembali mengikuti tengah peta. */
            function unlockNearbyCenter() {
                nearbyCenter = null;
                nearbyCenterLabel = '';
                if (centerMarker) { centerMarker.remove(); centerMarker = null; }
                updateNearbyCenterLabel();
                updateClearMapBtn();
            }

            function updateNearbyCenterLabel() {
                const c = nearbyCenter || (map ? [map.getCenter().lng, map.getCenter().lat] : startCity.center);
                const box = $('#nearbyCenter');

                box.classList.toggle('locked', !!nearbyCenter);
                box.innerHTML =
                    '<div class="center-src">' +
                    (nearbyCenter
                        ? '<i class="bi bi-pin-map-fill"></i><span>' +
                          esc(nearbyCenterLabel || 'Pinned point') + '</span>'
                        : '<i class="bi bi-arrows-move"></i><span>Follows the map center</span>') +
                    '</div>' +
                    '<code class="center-coord">location=' +
                    c[1].toFixed(6) + ',' + c[0].toFixed(6) + '</code>';

                const unlock = $('#nbUnlock');
                if (unlock) unlock.hidden = !nearbyCenter;

                drawNearbyRadius();
            }

            /** Lingkaran radius di peta, supaya cakupan pencarian terlihat. */
            function circlePolygon(center, radiusKm, steps) {
                steps = steps || 96;
                const [lng, lat] = center;
                const dLat = radiusKm / 110.574;
                const dLng = radiusKm / (111.320 * Math.cos(lat * Math.PI / 180) || 1);
                const ring = [];
                for (let i = 0; i <= steps; i++) {
                    const t = (i / steps) * 2 * Math.PI;
                    ring.push([lng + dLng * Math.cos(t), lat + dLat * Math.sin(t)]);
                }
                return { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [ring] } };
            }

            function drawNearbyRadius() {
                if (!map) return;
                const src = map.getSource('nearby-radius');
                if (!src) return;
                if (!nearbyCenter) { src.setData(emptyFC()); return; }
                src.setData({
                    type: 'FeatureCollection',
                    features: [circlePolygon(nearbyCenter, readNum('nbRadius'))]
                });
            }

            async function runNearby() {
                const c = nearbyCenter || [map.getCenter().lng, map.getCenter().lat];
                $('#nearbyResults').innerHTML = loadingBlock('Searching nearby places…');

                try {
                    const data = await grabGet('/api/v1/search-nearby', {
                        location: c[1].toFixed(6) + ',' + c[0].toFixed(6),
                        radius: readNum('nbRadius'),       // kilometres, not metres
                        limit: readNum('nbLimit'),
                        rankBy: rankBy
                    });

                    const places = (data && data.places) || [];
                    const box = $('#nearbyResults');
                    clearSearchMarkers();

                    if (!places.length) {
                        box.innerHTML = emptyBlock('bi-inbox', 'No places within this radius. Try widening it.');
                        setListMeta('nearby', 0);
                        return;
                    }

                    box.innerHTML = '<div class="results">' + places.map((p, i) => {
                        const ll = placeLngLat(p);
                        const d = ll ? haversine(c, ll) / 1000 : null;
                        return placeRow(p, i, d);
                    }).join('') + '</div>';
                    bindResultRows(box, places);
                    setListMeta('nearby', places.length);

                    const bounds = new maplibregl.LngLatBounds();
                    bounds.extend(c);
                    places.forEach((p, i) => {
                        const ll = placeLngLat(p);
                        if (!ll) return;
                        bounds.extend(ll);
                        const mk = new maplibregl.Marker({ element: makeMarkerEl('', String(i + 1)), offset: [0, -10] })
                            .setLngLat(ll).addTo(map);
                        mk.getElement().onclick = () => pickMarker(p, i, ll);
                        searchMarkers.push(mk);
                    });
                    markerOwner = 'nearby';
                    updateClearMapBtn();
                    map.fitBounds(bounds, { padding: 70, maxZoom: 17, duration: 700 });
                } catch (e) {
                    $('#nearbyResults').innerHTML = emptyBlock('bi-exclamation-triangle', e.message);
                    toast(e.message, 'err');
                }
            }

            /* ===================== Pane: Routes ===================== */
            function initRoutePane() {
                renderStops();

                $('#addStop').onclick = () => {
                    if (stops.length >= 8) { toast('Maximum of 8 points.', 'err'); return; }
                    stops.splice(stops.length - 1, 0, { label: '', text: '', lngLat: null });
                    renderStops();
                };

                $('#swapStops').onclick = () => {
                    stops.reverse();
                    renderStops();
                };

                $$('[data-profile]').forEach(btn => {
                    btn.onclick = () => {
                        $$('[data-profile]').forEach(b => b.classList.remove('on'));
                        btn.classList.add('on');
                        profile = btn.dataset.profile;
                    };
                });

                $$('[data-avoid]').forEach(btn => {
                    btn.onclick = () => {
                        const v = btn.dataset.avoid;
                        if (avoidSet.has(v)) { avoidSet.delete(v); btn.classList.remove('on'); }
                        else { avoidSet.add(v); btn.classList.add('on'); }
                    };
                });

                $('#routeGo').onclick = runRoute;
            }

            function relabelStops() {
                stops.forEach((s, i) => {
                    s.label = i === 0 ? 'A' : (i === stops.length - 1 ? 'B' : String(i));
                });
            }

            function renderStops() {
                relabelStops();
                const wrap = $('#stops');
                wrap.innerHTML = stops.map((s, i) =>
                    '<div class="stop" data-i="' + i + '">' +
                    '<span class="stop-dot">' + esc(s.label) + '</span>' +
                    '<div class="search-box">' +
                    '<i class="bi bi-search"></i>' +
                    '<input type="text" class="input-sm" placeholder="' +
                    (i === 0 ? 'Origin' : (i === stops.length - 1 ? 'Destination' : 'Waypoint')) +
                    '" value="' + esc(s.text) + '" autocomplete="off" spellcheck="false">' +
                    '<div class="suggests" hidden></div>' +
                    '</div>' +
                    (stops.length > 2
                        ? '<button class="stop-del" title="Remove"><i class="bi bi-x-lg"></i></button>'
                        : '<span style="width:30px;flex:none"></span>') +
                    '</div>'
                ).join('');

                $$('.stop', wrap).forEach(row => {
                    const i = Number(row.dataset.i);
                    const input = $('input', row);
                    const box = $('.suggests', row);

                    attachAutocomplete(input, box, (p) => {
                        stops[i].lngLat = placeLngLat(p);
                        stops[i].text = p.name || p.formatted_address || '';
                        input.value = stops[i].text;
                        drawStopMarkers();
                        if (stops[i].lngLat) map.flyTo({ center: stops[i].lngLat, zoom: 15, duration: 700 });
                    });

                    input.addEventListener('input', () => {
                        stops[i].text = input.value;
                        if (!input.value) { stops[i].lngLat = null; drawStopMarkers(); }
                    });

                    const del = $('.stop-del', row);
                    if (del) del.onclick = () => { stops.splice(i, 1); renderStops(); drawStopMarkers(); };
                });

                drawStopMarkers();
            }

            function drawStopMarkers() {
                stopMarkers.forEach(m => m.remove());
                stopMarkers = [];
                stops.forEach((s, i) => {
                    if (!s.lngLat) return;
                    const cls = i === 0 ? 'a' : (i === stops.length - 1 ? 'b' : 'via');
                    const mk = new maplibregl.Marker({
                        element: makeMarkerEl(cls, s.label),
                        draggable: true,
                        offset: [0, -10]
                    }).setLngLat(s.lngLat).addTo(map);

                    mk.on('dragend', () => {
                        const p = mk.getLngLat();
                        stops[i].lngLat = [p.lng, p.lat];
                        stops[i].text = p.lat.toFixed(5) + ', ' + p.lng.toFixed(5);
                        const input = $('.stop[data-i="' + i + '"] input');
                        if (input) input.value = stops[i].text;
                    });

                    stopMarkers.push(mk);
                });

                updateClearMapBtn();
            }

            async function runRoute() {
                const pts = stops.filter(s => s.lngLat);
                if (pts.length < 2) {
                    toast('At least 2 points with coordinates are required.', 'err');
                    return;
                }

                $('#routeResults').innerHTML = loadingBlock('Calculating route…');
                try {
                    const data = await grabGet('/api/v1/routes', {
                        // Default order: longitude,latitude
                        coordinates: pts.map(s => s.lngLat[0] + ',' + s.lngLat[1]),
                        profile: profile,
                        overview: $('#overview').value,
                        geometries: 'polyline6',
                        avoid: Array.from(avoidSet).join(','),
                        alternatives: $('#alts').value
                    });

                    if (!data || data.code !== 'ok' || !data.routes || !data.routes.length) {
                        throw new Error('GrabMaps returned no route (code: ' + ((data && data.code) || '—') + ')');
                    }

                    lastRoutes = data.routes.map(r => {
                        const pairs = decodePolyline(r.geometry || '', 6);
                        return { raw: r, coords: orientToLngLat(pairs, pts[0].lngLat) };
                    });

                    activeRouteIdx = 0;
                    renderRouteResults();
                    drawRoutes(true);
                } catch (e) {
                    $('#routeResults').innerHTML = emptyBlock('bi-exclamation-triangle', e.message);
                    toast(e.message, 'err');
                }
            }

            function renderRouteResults() {
                const box = $('#routeResults');
                box.innerHTML = '<div class="results">' + lastRoutes.map((r, i) => {
                    const d = r.raw;
                    const fee = d.fee && d.fee.amount ? (d.fee.currency || '') + ' ' + d.fee.amount : null;
                    return '<div class="route-card' + (i === activeRouteIdx ? ' on' : '') + '" data-i="' + i + '">' +
                        '<div class="route-metrics">' +
                        '<span class="metric-big">' + esc(fmtDur(d.duration)) + '</span>' +
                        '<span class="metric-sub">' + esc(fmtDist(d.distance)) + '</span>' +
                        (i === 0 ? '<span class="tag" style="margin-left:auto">fastest</span>'
                            : '<span class="tag" style="margin-left:auto">alternative ' + i + '</span>') +
                        '</div>' +
                        '<div class="metric-row">' +
                        '<span><i class="bi bi-traffic-light"></i> ' + (d.traffic_light || 0) + ' traffic lights</span>' +
                        '<span><i class="bi bi-diagram-3"></i> ' + ((d.legs && d.legs.length) || 0) + ' legs</span>' +
                        (fee ? '<span><i class="bi bi-cash"></i> ' + esc(fee) + '</span>' : '') +
                        '</div>' +
                        '</div>';
                }).join('') + '</div>';

                $$('.route-card', box).forEach(el => {
                    el.onclick = () => selectRoute(Number(el.dataset.i));
                });

                $('#routeClear').hidden = !lastRoutes.length;
                updateClearMapBtn();
            }

            function selectRoute(i) {
                if (i < 0 || i >= lastRoutes.length) return;
                activeRouteIdx = i;
                renderRouteResults();
                drawRoutes(false);
            }

            function drawRoutes(fit) {
                const src = map.getSource('route-line');
                if (!src) return;

                const features = lastRoutes.map((r, i) => ({
                    type: 'Feature',
                    properties: { idx: i, active: i === activeRouteIdx },
                    geometry: { type: 'LineString', coordinates: r.coords }
                })).filter(f => f.geometry.coordinates.length > 1);

                src.setData({ type: 'FeatureCollection', features: features });

                const active = features.find(f => f.properties.idx === activeRouteIdx);
                if (fit && active) {
                    const bounds = new maplibregl.LngLatBounds();
                    active.geometry.coordinates.forEach(c => bounds.extend(c));
                    map.fitBounds(bounds, { padding: { top: 70, bottom: 70, left: 70, right: 70 }, duration: 800 });
                }
            }

            /**
             * Polyline decoder. GrabMaps defaults to polyline6 (a 1e6 factor).
             */
            function decodePolyline(str, precision) {
                const factor = Math.pow(10, precision || 5);
                let index = 0, a = 0, b = 0;
                const out = [];

                while (index < str.length) {
                    let shift = 0, result = 0, byte;
                    do {
                        byte = str.charCodeAt(index++) - 63;
                        result |= (byte & 0x1f) << shift;
                        shift += 5;
                    } while (byte >= 0x20);
                    a += (result & 1) ? ~(result >> 1) : (result >> 1);

                    shift = 0; result = 0;
                    do {
                        byte = str.charCodeAt(index++) - 63;
                        result |= (byte & 0x1f) << shift;
                        shift += 5;
                    } while (byte >= 0x20);
                    b += (result & 1) ? ~(result >> 1) : (result >> 1);

                    out.push([a / factor, b / factor]);
                }
                return out;
            }

            /**
             * The docs say geometry is already MapLibre-ready, yet waypoints[].location comes
             * back as [latitude, longitude]. Rather than guess, match the first decoded point
             * against the origin we actually sent.
             */
            function orientToLngLat(pairs, refLngLat) {
                if (!pairs.length || !refLngLat) return pairs;
                const first = pairs[0];
                const asIs = haversine([first[0], first[1]], refLngLat);
                const swapped = haversine([first[1], first[0]], refLngLat);
                return swapped < asIs ? pairs.map(p => [p[1], p[0]]) : pairs;
            }

            /* ===================== Steppers ===================== */

            /**
             * Rentang di sini adalah pagar aplikasi, bukan batas API: dokumentasi
             * GrabMaps hanya menyebut nilai default (radius 1 km, limit 10) tanpa
             * maksimum. Pagar ini mencegah permintaan konyol seperti radius 5000 km.
             */
            function initSteppers() {
                $$('.stepper').forEach(wrap => {
                    const input = $('.step-input', wrap);
                    const dec = $('[data-step="down"]', wrap);
                    const inc = $('[data-step="up"]', wrap);

                    const min = parseFloat(input.min);
                    const max = parseFloat(input.max);
                    const fractional = String(input.step).indexOf('.') >= 0;

                    // Langkah menyesuaikan besaran nilai supaya 0.1 → 30 tidak perlu 300 klik.
                    function stepFor(v) {
                        if (!fractional) return parseFloat(input.step) || 1;
                        if (v <= 1) return 0.1;
                        if (v <= 5) return 0.5;
                        return 1;
                    }

                    function current() {
                        const v = parseFloat(input.value);
                        return isNaN(v) ? min : v;
                    }

                    function round(v) {
                        return fractional ? Math.round(v * 10) / 10 : Math.round(v);
                    }

                    function sync() {
                        const v = current();
                        dec.disabled = v <= min;
                        inc.disabled = v >= max;
                    }

                    function commit(v, flashOnClamp) {
                        const clamped = Math.min(max, Math.max(min, round(v)));
                        if (flashOnClamp && Math.abs(clamped - round(v)) > 1e-9) {
                            wrap.classList.remove('clamped');
                            void wrap.offsetWidth;          // paksa reflow supaya animasi bisa diulang
                            wrap.classList.add('clamped');
                        }
                        input.value = String(clamped);
                        sync();
                    }

                    dec.onclick = () => commit(current() - stepFor(current()), false);
                    inc.onclick = () => commit(current() + stepFor(current() + 1e-9), false);

                    input.addEventListener('input', sync);
                    input.addEventListener('blur', () => commit(current(), true));
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'ArrowUp') { e.preventDefault(); inc.click(); }
                        else if (e.key === 'ArrowDown') { e.preventDefault(); dec.click(); }
                    });

                    sync();
                });
            }

            /** Baca nilai stepper, selalu dalam rentang, meski isian diubah lewat DevTools. */
            function readNum(id) {
                const el = $('#' + id);
                const min = parseFloat(el.min);
                const max = parseFloat(el.max);
                const v = parseFloat(el.value);
                if (isNaN(v)) return min;
                return Math.min(max, Math.max(min, v));
            }

            /* ===================== Tabs ===================== */
            function switchPane(name) {
                $$('.tab').forEach(t => t.classList.toggle('active', t.dataset.pane === name));
                ['search', 'nearby', 'route', 'api'].forEach(n => {
                    $('#pane-' + n).hidden = (n !== name);
                });
            }

            /* ===================== API panel: actions ===================== */
            function initApiPane() {
                $('#copyCurl').onclick = () => {
                    if (!lastUrl) { toast('No request yet.', 'err'); return; }
                    const curl = "curl --request GET \\\n  '" + lastUrl + "' \\\n" +
                        "  --header 'Authorization: Bearer YOUR_API_KEY'";
                    copy(curl, 'cURL command copied (key replaced with a placeholder).');
                };
                $('#copyJson').onclick = () => {
                    if (lastResponse === null) { toast('No response yet.', 'err'); return; }
                    copy(JSON.stringify(lastResponse, null, 2), 'Response JSON copied.');
                };
            }

            function copy(text, okMsg) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => toast(okMsg, 'ok'),
                        () => toast('Copy failed.', 'err'));
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); toast(okMsg, 'ok'); }
                    catch (_) { toast('Copy failed.', 'err'); }
                    ta.remove();
                }
            }

            /* ===================== Welcome flow ===================== */
            function initWelcome() {
                const sel = $('#citySelect');
                sel.innerHTML = CITIES.map((c, i) =>
                    '<option value="' + i + '">' + esc(c.name) + '</option>'
                ).join('');

                const savedCity = localStorage.getItem(LS_CITY);
                if (savedCity !== null && CITIES[Number(savedCity)]) sel.value = savedCity;

                const savedKey = localStorage.getItem(LS_KEY);
                if (savedKey) $('#keyInput').value = savedKey;

                $('#peekBtn').onclick = () => {
                    const inp = $('#keyInput');
                    const show = inp.type === 'password';
                    inp.type = show ? 'text' : 'password';
                    $('#peekBtn').innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
                };

                $('#keyForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    await startWithKey();
                });
            }

            function setWelcomeAlert(kind, msg) {
                const box = $('#welcomeAlert');
                if (!kind) { box.innerHTML = ''; return; }
                const icon = kind === 'ok' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
                box.innerHTML = '<div class="alert alert-' + kind + '"><i class="bi ' + icon + '"></i>' +
                    '<div>' + esc(msg) + '</div></div>';
            }

            async function startWithKey() {
                const input = $('#keyInput');
                const key = input.value.trim();
                const btn = $('#startBtn');

                if (!key) {
                    input.classList.add('is-invalid');
                    setWelcomeAlert('error', 'Please enter an API key.');
                    input.focus();
                    return;
                }

                input.classList.remove('is-invalid');
                btn.disabled = true;
                btn.innerHTML = '<span class="spin"></span> Verifying key…';
                setWelcomeAlert(null);

                apiKey = key;

                try {
                    // Cheap ping to confirm the key works before loading the map.
                    await grabGet('/api/v1/autocomplete', { keyword: 'Marina', country: 'SGP', limit: 1 });
                } catch (err) {
                    apiKey = '';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-play-fill"></i> Open map';
                    input.classList.add('is-invalid');
                    setWelcomeAlert('error', err.message);
                    return;
                }

                const cityIdx = Number($('#citySelect').value) || 0;
                startCity = CITIES[cityIdx];
                localStorage.setItem(LS_CITY, String(cityIdx));

                if ($('#rememberKey').checked) localStorage.setItem(LS_KEY, key);
                else localStorage.removeItem(LS_KEY);

                btn.innerHTML = '<span class="spin"></span> Loading map…';

                try {
                    $('#welcome').hidden = true;
                    $('#app').hidden = false;
                    $('#keyMask').textContent = maskKey(key);

                    await initMap();

                    initSearchPane();
                    initNearbyPane();
                    initRoutePane();
                    initApiPane();
                    initSteppers();
                    initClearControls();

                    $$('.tab').forEach(t => { t.onclick = () => switchPane(t.dataset.pane); });
                    $('#keyChip').onclick = resetKey;

                    toast('Connected to GrabMaps.', 'ok');
                } catch (err) {
                    $('#welcome').hidden = false;
                    $('#app').hidden = true;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-play-fill"></i> Open map';
                    setWelcomeAlert('error', err.message);
                }
            }

            function resetKey() {
                localStorage.removeItem(LS_KEY);
                location.reload();
            }

            /* ===================== Boot ===================== */
            initWelcome();
            $('#keyInput').focus();
        })();
    </script>
</body>

</html>
