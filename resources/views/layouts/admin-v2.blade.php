<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') | GrabMaps</title>
    {{-- Tema dipasang sebelum CSS supaya tidak ada kedipan putih saat mode gelap. --}}
    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('gm-theme') || 'system');
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'system');
            }
        })();
    </script>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @stack('css')
    <style>
        /* ================= Token tema =================
           Terang = default di :root. Gelap dipakai kalau data-theme="dark",
           atau saat mengikuti sistem (data-theme="system"/kosong) dan OS-nya gelap. */
        :root {
            color-scheme: light;

            --green: #00B14F;
            --green-dark: #009640;
            --green-deep: #067a3c;
            --green-soft: #e8f8ef;
            --green-text: #009640;

            --page: #eceeef;
            --card: #ffffff;
            --surface: #f2f4f4;
            --line: #ebeeee;
            --ink: #141b18;
            --muted: #8a938f;

            --tone-indigo-bg: #eef2ff;
            --tone-indigo-fg: #6366f1;
            --danger-soft: #fdecec;
            --danger-fg: #d64545;
            --warn-soft: #fef6e7;
            --warn-fg: #b45309;
            --faint: #b3bcb7;

            --r-card: 22px;
            --shadow-card: 0 1px 2px rgba(20, 27, 24, 0.04), 0 8px 24px rgba(20, 27, 24, 0.05);
            --shadow-pop: 0 12px 40px rgba(20, 27, 24, 0.14);
        }

        :root[data-theme="dark"] {
            color-scheme: dark;

            --green-soft: rgba(0, 177, 79, 0.16);
            --green-text: #45d67f;

            --page: #0e1210;
            --card: #191e1b;
            --surface: #232926;
            --line: #2b322e;
            --ink: #eaf0ec;
            --muted: #8e9a94;

            --tone-indigo-bg: rgba(99, 102, 241, 0.18);
            --tone-indigo-fg: #a5b4fc;
            --danger-soft: rgba(220, 38, 38, 0.16);
            --danger-fg: #f08a8a;
            --warn-soft: rgba(245, 158, 11, 0.14);
            --warn-fg: #fbbf24;
            --faint: #5f6b65;

            --shadow-card: 0 1px 2px rgba(0, 0, 0, 0.4), 0 10px 26px rgba(0, 0, 0, 0.34);
            --shadow-pop: 0 14px 44px rgba(0, 0, 0, 0.55);
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]):not([data-theme="dark"]) {
                color-scheme: dark;

                --green-soft: rgba(0, 177, 79, 0.16);
                --green-text: #45d67f;

                --page: #0e1210;
                --card: #191e1b;
                --surface: #232926;
                --line: #2b322e;
                --ink: #eaf0ec;
                --muted: #8e9a94;

                --tone-indigo-bg: rgba(99, 102, 241, 0.18);
                --tone-indigo-fg: #a5b4fc;
                --danger-soft: rgba(220, 38, 38, 0.16);
                --danger-fg: #f08a8a;
                --warn-soft: rgba(245, 158, 11, 0.14);
                --warn-fg: #fbbf24;
                --faint: #5f6b65;

                --shadow-card: 0 1px 2px rgba(0, 0, 0, 0.4), 0 10px 26px rgba(0, 0, 0, 0.34);
                --shadow-pop: 0 14px 44px rgba(0, 0, 0, 0.55);
            }
        }

        * { -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--page);
            color: var(--ink);
            font-size: 0.875rem;
        }

        .display-font { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }

        /* ---------- Kerangka halaman ---------- */
        .page {
            max-width: 1460px;
            margin: 0 auto;
            padding: 18px 22px 34px;
        }

        .page-top {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        /* Layar sempit: topbar boleh turun baris, tab-nya sendiri bisa digeser. */
        @media (max-width: 1100px) {
            .page-top { flex-wrap: wrap; }
            .top-actions { order: -1; }
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--card);
            border-radius: 999px;
            padding: 9px 22px 9px 10px;
            text-decoration: none;
            box-shadow: var(--shadow-card);
        }

        .brand-pill img { height: 30px; width: 30px; object-fit: contain; }

        .brand-pill span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: -0.03em;
            color: var(--ink);
        }

        .top-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }

        .q-circle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: var(--card);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.92rem;
            text-decoration: none;
            box-shadow: var(--shadow-card);
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }

        .q-circle-btn:hover { background: var(--green); color: #fff; }

        /* Pemilih bahasa: indikator hijau meluncur ke sisi yang dipilih. */
        .q-lang {
            position: relative;
            display: inline-flex;
            align-items: center;
            background: var(--card);
            border-radius: 999px;
            padding: 4px;
            box-shadow: var(--shadow-card);
        }

        .lang-thumb {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 42px;
            height: calc(100% - 8px);
            border-radius: 999px;
            background: var(--green);
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.35);
            transform: translateX(0);
            transition: transform 0.4s cubic-bezier(0.34, 1.45, 0.5, 1);
        }

        .q-lang[data-active="1"] .lang-thumb { transform: translateX(42px); }

        .q-lang a {
            position: relative;
            z-index: 1;
            width: 42px;
            text-align: center;
            padding: 6px 0;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            line-height: 1.35;
            transition: color 0.28s ease, transform 0.15s ease;
        }

        .q-lang a:hover { color: var(--ink); }
        .q-lang a.active { color: #fff; }
        .q-lang a:active { transform: scale(0.9); }

        @media (prefers-reduced-motion: reduce) {
            .lang-thumb { transition: none; }
        }

        /* ---------- Rail kiri ---------- */
        .page-body { display: flex; gap: 18px; align-items: flex-start; }

        /* Rail menempel di layar: grup tema di atas, menu di tengah, sisanya di bawah. */
        .rail {
            width: 48px;
            flex-shrink: 0;
            position: sticky;
            top: 18px;
            height: calc(100vh - 36px);
            display: flex;
            flex-direction: column;
            gap: 12px;
            /* position:sticky selalu membuat stacking context, jadi tooltip di dalam rail
               ikut terkurung. Rail dinaikkan di atas konten supaya tooltipnya tidak
               tertutup kartu — tetap di bawah overlay (skeleton 40, palette 1200, toast 1500). */
            z-index: 60;
        }

        /* Menu + grup bawahnya jadi satu kelompok yang dipatok di tengah tinggi layar,
           terlepas dari grup tema yang tetap menempel di atas. */
        .rail-center {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Layar pendek: kembali ke aliran normal supaya tidak tumpang tindih. */
        @media (max-height: 700px) {
            .rail { height: auto; }
            .rail-center { position: static; transform: none; }
        }

        .rail-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
            background: var(--card);
            border-radius: 24px;
            padding: 4px;
            box-shadow: var(--shadow-card);
        }

        /* Sengaja seukuran tombol topbar (40px) supaya ritmenya sama. */
        .rail-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 0.92rem;
            text-decoration: none;
            cursor: pointer;
            position: relative;
            transition: background 0.15s, color 0.15s;
        }

        .rail-btn:hover { background: var(--surface); color: var(--ink); }

        .rail-btn.active {
            background: var(--green);
            color: #fff;
            box-shadow: 0 6px 16px rgba(0, 177, 79, 0.35);
        }

        .theme-btn.active { background: var(--green); color: #fff; box-shadow: 0 6px 16px rgba(0, 177, 79, 0.35); }

        /* Grup tema menciut: hanya mode aktif yang tampil, membuka saat hover/fokus.
           Saat menciut wadahnya ikut hilang supaya yang tersisa satu lingkaran penuh,
           selebar rail (48px) — bukan tombol kecil di dalam cincin putih. */
        .theme-group {
            gap: 0;
            transition:
                padding 0.34s cubic-bezier(0.34, 1.4, 0.5, 1),
                background-color 0.28s ease,
                box-shadow 0.28s ease;
        }

        .theme-group:not(:hover):not(:focus-within) {
            padding: 0;
            background: transparent;
            box-shadow: none;
        }

        .theme-group .theme-btn {
            width: 40px;
            height: 40px;
            margin-top: 3px;
            transition:
                width 0.34s cubic-bezier(0.34, 1.4, 0.5, 1),
                height 0.34s cubic-bezier(0.34, 1.4, 0.5, 1),
                margin-top 0.34s cubic-bezier(0.34, 1.4, 0.5, 1),
                transform 0.34s cubic-bezier(0.34, 1.4, 0.5, 1),
                opacity 0.2s ease,
                background 0.15s, color 0.15s;
        }

        .theme-group .theme-btn:first-child { margin-top: 0; }

        .theme-group:not(:hover):not(:focus-within) .theme-btn.active {
            width: 48px;
            height: 48px;
            font-size: 1.05rem;
        }

        .theme-group:not(:hover):not(:focus-within) .theme-btn:not(.active) {
            height: 0;
            margin-top: 0;
            opacity: 0;
            transform: scale(0.4);
            pointer-events: none;
        }

        /* Buka berurutan, bukan serempak — terasa lebih hidup. */
        .theme-group:hover .theme-btn:nth-child(2),
        .theme-group:focus-within .theme-btn:nth-child(2) { transition-delay: 0.03s; }
        .theme-group:hover .theme-btn:nth-child(3),
        .theme-group:focus-within .theme-btn:nth-child(3) { transition-delay: 0.06s; }

        /* Ikon berputar masuk saat mode baru dipilih (dipicu dari JS). */
        .theme-btn.pop i { animation: themePop 0.42s cubic-bezier(0.34, 1.56, 0.64, 1); }

        @keyframes themePop {
            from { transform: rotate(-120deg) scale(0.3); opacity: 0; }
            to   { transform: none; opacity: 1; }
        }

        /* Pergantian tema di-crossfade sesaat, bukan ganti warna mendadak. */
        html.theme-anim, html.theme-anim * {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .theme-group .theme-btn { transition: opacity 0.15s ease; }
            .theme-btn.pop i { animation: none; }
            html.theme-anim, html.theme-anim * { transition: none !important; }
        }

        /* Tooltip, karena menu ikon tanpa label */
        .rail-btn::after {
            content: attr(data-tip);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: var(--ink);
            color: var(--page);
            font-size: 0.72rem;
            font-weight: 500;
            padding: 6px 11px;
            border-radius: 9px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.14s;
            z-index: 40;
            pointer-events: none;
        }

        .rail-btn:hover::after { opacity: 1; visibility: visible; }

        .rail-main { margin-top: 6px; }

        /* relative: jadi jangkar untuk skeleton loader saat pindah halaman. */
        .page-main { flex: 1; min-width: 0; position: relative; }

        /* Di layar kecil rail jadi bar horizontal yang bisa digeser */
        @media (max-width: 860px) {
            .page { padding: 12px 12px 26px; }
            .page-body { flex-direction: column; }
            .rail {
                width: 100%;
                position: static;
                flex-direction: row;
                gap: 10px;
                overflow-x: auto;
                scrollbar-width: none;
                padding-bottom: 4px;
            }
            .rail::-webkit-scrollbar { display: none; }
            .rail-group { flex-direction: row; border-radius: 999px; }
            .rail-main { margin-top: 0; }
            .rail-btn::after { display: none; }
            .page-main { width: 100%; }
        }

        /* ---------- Pemilih akun AWS ---------- */
        .aws-trigger {
            display: flex;
            align-items: center;
            gap: 9px;
            background: var(--card);
            border: none;
            border-radius: 999px;
            padding: 5px 14px 5px 5px;
            box-shadow: var(--shadow-card);
            cursor: pointer;
            color: var(--ink);
        }

        .aws-trigger .ic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--green-soft);
            color: var(--green-text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            flex-shrink: 0;
        }

        .aws-trigger .ic.warn { background: var(--danger-soft); color: var(--danger-fg); }
        .aws-trigger .bi-chevron-down { font-size: 0.65rem; color: var(--muted); }
        .aws-meta { display: flex; flex-direction: column; text-align: left; line-height: 1.2; }
        .aws-meta .nm { font-size: 0.76rem; font-weight: 600; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .aws-meta .rg { font-size: 0.66rem; color: var(--muted); }

        @media (max-width: 1150px) { .aws-meta { display: none; } .aws-trigger { padding: 5px; } }

        .q-dd-title {
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 10px 12px 6px;
        }

        /* Sedikit lebih lebar supaya "region · access key" muat satu baris. */
        #awsDropdown { min-width: 302px; }

        .aws-row {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 9px 12px;
            border: none;
            background: none;
            border-radius: 12px;
            text-align: left;
            color: var(--ink);
            text-decoration: none;
            cursor: pointer;
        }

        .aws-row:hover { color: var(--ink); }
        .aws-row .nm { font-size: 0.81rem; font-weight: 600; }
        .aws-row .sub { font-size: 0.69rem; color: var(--muted); }
        .aws-row > i { color: var(--muted); font-size: 0.9rem; flex-shrink: 0; }
        .aws-row .min-w-0 { min-width: 0; flex: 1; }
        .aws-row.current { cursor: default; }
        .aws-row.current > i { color: var(--green); }
        .aws-row:not(.current):hover { background: var(--surface); }
        .aws-row .pick { font-size: 0.68rem; font-weight: 700; color: var(--green-text); opacity: 0; }
        .aws-row:hover .pick { opacity: 1; }
        .aws-row .q-chip { margin-top: 0; flex-shrink: 0; }

        /* Umpan balik saat aksi yang memicu request panjang (ganti akun, refresh AWS). */
        .aws-row.busy { opacity: 0.65; pointer-events: none; }
        .aws-row.busy .pick { opacity: 1; }
        .spin { display: inline-block; animation: gmSpin 0.9s linear infinite; }

        @keyframes gmSpin { to { transform: rotate(360deg); } }

        @media (prefers-reduced-motion: reduce) {
            .spin { animation-duration: 2s; }
        }

        /* ---------- Menu user ---------- */
        .q-menu, .q-user { position: relative; }

        .q-user-trigger {
            display: flex;
            align-items: center;
            gap: 9px;
            background: var(--card);
            border: none;
            padding: 5px 16px 5px 5px;
            border-radius: 999px;
            cursor: pointer;
            box-shadow: var(--shadow-card);
        }

        .q-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(140deg, var(--green) 0%, var(--green-deep) 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .q-user-meta { text-align: left; line-height: 1.25; }
        .q-user-meta .nm { font-size: 0.78rem; font-weight: 600; }
        .q-user-meta .rl { font-size: 0.68rem; color: var(--muted); }
        .q-user-trigger .bi-chevron-down { font-size: 0.65rem; color: var(--muted); }

        @media (max-width: 600px) { .q-user-meta { display: none; } .q-user-trigger { padding: 5px; } }

        .q-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            min-width: 250px;
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--shadow-pop);
            padding: 8px;
            display: none;
            z-index: 1080;
        }

        .q-dropdown.show { display: block; animation: popIn 0.16s ease-out; }

        @keyframes popIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

        .q-dd-head {
            display: flex;
            gap: 11px;
            align-items: center;
            padding: 12px 12px 14px;
            border-bottom: 1px solid var(--line);
            margin-bottom: 6px;
        }

        .q-dd-head .nm { font-size: 0.85rem; font-weight: 700; }
        .q-dd-head .em { font-size: 0.72rem; color: var(--muted); word-break: break-all; }

        .q-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.63rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green-text);
            margin-top: 5px;
        }

        .q-dd-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            border: none;
            background: none;
            border-radius: 12px;
            font-size: 0.82rem;
            color: var(--ink);
            text-decoration: none;
            text-align: left;
            cursor: pointer;
        }

        .q-dd-item i { color: var(--muted); width: 18px; text-align: center; }
        .q-dd-item:hover { background: var(--surface); }
        .q-dd-item.danger, .q-dd-item.danger i { color: #dc2626; }
        .q-dd-item.danger:hover { background: var(--danger-soft); }
        .q-dd-sep { height: 1px; background: var(--line); margin: 5px 0; }

        /* ---------- Kartu & komponen ---------- */
        .q-card {
            background: var(--card);
            border-radius: var(--r-card);
            padding: 20px;
            box-shadow: var(--shadow-card);
        }

        .q-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
        }

        .q-card-title { font-size: 0.9rem; font-weight: 700; line-height: 1.3; }
        .q-card-sub { font-size: 0.74rem; color: var(--muted); margin-top: 1px; }

        .q-ghost-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--surface);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            text-decoration: none;
            flex-shrink: 0;
            border: none;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }

        .q-ghost-btn:hover { background: var(--green); color: #fff; }

        /* Kotak ikon bernuansa, ikut tema */
        .q-icon-box.tone-indigo { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
        .q-icon-box.tone-green { background: var(--green-soft); color: var(--green-text); }

        .q-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--surface);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .q-delta {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            background: var(--green-soft);
            color: var(--green-text);
        }

        .q-delta.down { background: var(--danger-soft); color: var(--danger-fg); }
        .q-delta.flat { background: var(--surface); color: var(--muted); }

        .q-num {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.05;
        }

        .q-num .cents { color: var(--faint); }

        .q-toggle { display: inline-flex; background: var(--surface); border-radius: 999px; padding: 4px; gap: 2px; }

        .q-toggle button {
            border: none;
            background: none;
            border-radius: 999px;
            padding: 6px 15px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.15s;
        }

        .q-toggle button.active { background: var(--green); color: #fff; }

        .q-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: var(--card);
            border: none;
            border-radius: 999px;
            padding: 11px 20px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--ink);
            text-decoration: none;
            box-shadow: var(--shadow-card);
            cursor: pointer;
            transition: box-shadow 0.15s;
        }

        .q-pill:hover { color: var(--ink); box-shadow: 0 4px 16px rgba(20, 27, 24, 0.1); }
        .q-pill i { color: var(--muted); }

        .q-pill-green { background: var(--green); color: #fff; box-shadow: 0 6px 18px rgba(0, 177, 79, 0.3); }
        .q-pill-green:hover { background: var(--green-dark); color: #fff; }
        .q-pill-green i { color: rgba(255, 255, 255, 0.85); }

        .q-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.3rem;
            font-weight: 800;
            letter-spacing: -0.035em;
            margin: 0;
            line-height: 1.1;
        }

        .q-title .soft { color: var(--faint); font-weight: 700; }

        @media (max-width: 767px) { .q-title { font-size: 1.6rem; } }

        .q-page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        /* Alert KEADAAN — sengaja dibedakan dari toast: menyatu di aliran halaman,
           berlatar warna, ada garis tepi, tanpa bayangan. Toast sebaliknya: kartu
           putih mengambang dengan ikon bulat dan bilah umur. */
        .q-alert {
            display: flex;
            gap: 12px;
            align-items: center;
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 0.82rem;
            line-height: 1.5;
            margin-bottom: 18px;
            background: var(--alert-bg);
            border: 1px solid var(--alert-line);
            box-shadow: none;
        }

        .q-alert-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.85rem;
            background: var(--alert-tint);
            color: var(--alert-fg);
        }

        .q-alert-body { flex: 1; min-width: 0; }

        .q-alert-action {
            align-self: center;
            flex-shrink: 0;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--alert-fg);
            text-decoration: none;
            border: 1px solid var(--alert-line);
            border-radius: 999px;
            padding: 6px 14px;
            transition: background 0.15s;
        }

        .q-alert-action:hover { background: var(--alert-tint); color: var(--alert-fg); }

        .q-alert.ok {
            --alert-bg: var(--green-soft);
            --alert-line: rgba(0, 177, 79, 0.28);
            --alert-tint: rgba(0, 177, 79, 0.18);
            --alert-fg: var(--green-text);
        }

        .q-alert.warn {
            --alert-bg: var(--warn-soft);
            --alert-line: rgba(245, 158, 11, 0.32);
            --alert-tint: rgba(245, 158, 11, 0.2);
            --alert-fg: var(--warn-fg);
        }

        .q-alert.bad {
            --alert-bg: var(--danger-soft);
            --alert-line: rgba(220, 38, 38, 0.3);
            --alert-tint: rgba(220, 38, 38, 0.18);
            --alert-fg: var(--danger-fg);
        }

        /* ---------- Dropdown pengganti <select> bawaan ----------
           Select aslinya tetap ada di DOM (disembunyikan) supaya form submit dan
           event change yang sudah dipakai halaman tetap bekerja. */
        .gm-select { position: relative; width: 100%; }

        .gm-select-native {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }

        .gm-select-btn {
            width: 100%;
            height: 42px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
            font-size: 0.82rem;
            font-weight: 500;
            text-align: left;
            padding: 0 34px 0 38px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .gm-select-btn:hover { border-color: var(--muted); }

        .gm-select.open > .gm-select-btn,
        .gm-select-btn:focus-visible {
            border-color: var(--green);
            background: var(--card);
            box-shadow: 0 0 0 4px var(--green-soft);
            outline: none;
        }

        .gm-select-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .gm-select-caret {
            position: absolute;
            right: 14px;
            top: 50%;
            font-size: 0.6rem;
            color: var(--muted);
            transform: translateY(-50%);
            transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.5, 1);
            pointer-events: none;
        }

        .gm-select.open .gm-select-caret { transform: translateY(-50%) rotate(180deg); }

        .gm-select-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 100%;
            max-height: 274px;
            overflow-y: auto;
            background: var(--card);
            border-radius: 16px;
            box-shadow: var(--shadow-pop);
            padding: 6px;
            z-index: 1150;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px) scale(0.98);
            transform-origin: top center;
            transition:
                opacity 0.16s ease,
                transform 0.26s cubic-bezier(0.34, 1.4, 0.5, 1),
                visibility 0.16s;
            scrollbar-width: thin;
        }

        .gm-select.open .gm-select-panel { opacity: 1; visibility: visible; transform: none; }
        .gm-select.drop-up .gm-select-panel { top: auto; bottom: calc(100% + 8px); transform-origin: bottom center; }

        .gm-select-opt {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 9px 12px;
            border: none;
            background: none;
            border-radius: 11px;
            font-size: 0.82rem;
            color: var(--ink);
            text-align: left;
            cursor: pointer;
            animation: gmOptIn 0.24s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
        }

        @keyframes gmOptIn {
            from { opacity: 0; transform: translateY(5px); }
            to   { opacity: 1; transform: none; }
        }

        .gm-select-group {
            padding: 8px 12px 4px;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--faint);
        }
        .gm-select-group + .gm-select-opt { margin-top: 0; }

        .gm-select-opt:hover, .gm-select-opt.cursor { background: var(--surface); }
        .gm-select-opt.sel { color: var(--green-text); font-weight: 700; }
        .gm-select-opt[disabled] { opacity: 0.45; cursor: default; }
        .gm-select-opt .tick { width: 14px; flex-shrink: 0; font-size: 0.72rem; opacity: 0; }
        .gm-select-opt.sel .tick { opacity: 1; }
        .gm-select-opt .txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        @media (prefers-reduced-motion: reduce) {
            .gm-select-panel { transition: opacity 0.12s ease, visibility 0.12s; transform: none; }
            .gm-select-opt { animation: none; }
            .gm-select-caret { transition: none; }
        }

        /* Tombol aksi di dalam kartu & modal (dipakai lintas halaman admin). */
        .btn-row { display: flex; gap: 10px; }

        .btn-soft, .btn-solid {
            border: none;
            border-radius: 999px;
            padding: 12px 24px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background 0.15s, color 0.15s, filter 0.15s, transform 0.12s, box-shadow 0.15s;
        }

        .btn-soft { background: var(--surface); color: var(--ink); }
        .btn-soft:hover { background: var(--line); color: var(--ink); }

        .btn-solid { background: var(--green); color: #fff; box-shadow: 0 6px 16px rgba(0, 177, 79, 0.3); }
        .btn-solid:hover { background: var(--green-dark); color: #fff; box-shadow: 0 8px 20px rgba(0, 177, 79, 0.38); }

        .btn-solid.danger { background: #dc2626; box-shadow: 0 6px 16px rgba(220, 38, 38, 0.3); }
        .btn-solid.danger:hover { background: #b91c1c; box-shadow: 0 8px 20px rgba(220, 38, 38, 0.38); }

        .btn-solid.info { background: #4f46e5; box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3); }
        .btn-solid.info:hover { background: #4338ca; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.38); }

        .btn-soft:active, .btn-solid:active { transform: scale(0.97); }

        /* Tombol lihat/sembunyikan kata sandi — dipasang di dalam input yang
           punya position:relative, dipakai lewat atribut data-toggle-pw. */
        .pw-eye {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--muted);
            cursor: pointer;
            padding: 7px;
            border-radius: 9px;
            line-height: 1;
            transition: background 0.15s, color 0.15s;
        }

        .pw-eye:hover { background: var(--green-soft); color: var(--green-text); }
        .pw-eye.on { color: var(--green-text); }

        .q-empty { text-align: center; padding: 34px 10px; color: var(--muted); }
        /* Hanya ikon langsung di bawah .q-empty yang jadi ilustrasi besar — ikon di dalam
           tombol ajakan (mis. "Tambah akun") tetap seukuran teksnya. */
        .q-empty > i { font-size: 1.8rem; display: block; margin-bottom: 8px; color: var(--faint); }

        @stack('styles')
    </style>
</head>

<body>
    @php
        $authUser = auth()->user();
        $initials = $authUser
            ? collect(explode(' ', $authUser->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')
            : '?';

        // Menu disaring per izin: yang tidak boleh dibuka tidak usah ditampilkan.
        $menu = collect([
            ['route' => 'admin.dashboard',           'match' => 'admin.dashboard*',      'icon' => 'bi-grid-1x2-fill', 'label' => __('admin.dashboard'),    'can' => 'dashboard.view'],
            ['route' => 'admin.companies.index',     'match' => 'admin.companies.*',     'icon' => 'bi-building',      'label' => __('admin.companies'),    'can' => 'companies.view'],
            ['route' => 'admin.api-keys.index',      'match' => 'admin.api-keys.*',      'icon' => 'bi-key-fill',      'label' => __('admin.api_keys'),     'can' => 'api_keys.view'],
            ['route' => 'admin.aws-accounts.index',  'match' => 'admin.aws-accounts.*',  'icon' => 'bi-cloud-fill',    'label' => __('ui.aws_accounts'),    'can' => 'aws_accounts.view'],
            ['route' => 'admin.cost-settings.index', 'match' => 'admin.cost-settings.*', 'icon' => 'bi-cash-coin',     'label' => __('ui.cost_settings'),   'can' => 'cost_settings.view'],
            // Simulator disembunyikan dulu, sama seperti Perusahaan. Rute, izin, dan
            // halamannya tetap ada — cukup buka komentar baris ini untuk mengembalikannya.
            // ['route' => 'admin.simulator',           'match' => 'admin.simulator',       'icon' => 'bi-speedometer2',  'label' => __('ui.simulator'),       'can' => 'simulator.use'],
            ['route' => 'admin.users.index',         'match' => 'admin.users.*',         'icon' => 'bi-people-fill',   'label' => __('ui.users'),           'can' => 'users.view'],
            ['route' => 'admin.roles.index',         'match' => 'admin.roles.*',         'icon' => 'bi-shield-lock',   'label' => __('ui.roles'),           'can' => 'roles.view'],
        ])->filter(fn ($item) => $authUser?->hasPermission($item['can']))->values()->all();

        // Akun AWS yang sedang dilihat — selalu SATU akun, tidak pernah digabung.
        // Kalau sesi belum memilih, jatuh ke akun default. Urutan daftar dikunci ke id
        // supaya posisi tiap akun tidak berpindah-pindah setelah dipilih.
        $navAwsAccounts = \App\Models\AwsAccount::query()->active()->orderBy('id')->get();
        $navScopeId     = session('admin_aws_scope');
        $navAwsActive   = ($navScopeId ? $navAwsAccounts->firstWhere('id', $navScopeId) : null)
            ?: $navAwsAccounts->firstWhere('is_default', true)
            ?: $navAwsAccounts->first();
    @endphp

    <div class="page">
        {{-- ===== Baris atas: brand + aksi ===== --}}
        <header class="page-top">
            <a href="{{ route('admin.dashboard') }}" class="brand-pill">
                <img src="{{ asset('logo2.png') }}" alt="GrabMaps">
                <span>GrabMaps</span>
            </a>

            {{-- Slot tab milik halaman (mis. penyaring di daftar API key). --}}
            @yield('top-tabs')

            <div class="top-actions">
                <div class="q-lang" id="langSwitch" data-active="{{ app()->getLocale() === 'id' ? '1' : '0' }}">
                    <span class="lang-thumb" aria-hidden="true"></span>
                    <a href="{{ route('admin.language', 'en') }}" data-idx="0"
                       class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    <a href="{{ route('admin.language', 'id') }}" data-idx="1"
                       class="{{ app()->getLocale() === 'id' ? 'active' : '' }}">ID</a>
                </div>

                <button type="button" class="q-circle-btn" data-search-open title="{{ __('ui.search_hint') }}">
                    <i class="bi bi-search"></i>
                </button>

                {{-- Cakupan akun AWS untuk angka yang ditampilkan --}}
                <div class="q-menu" id="awsMenu">
                    <button type="button" class="aws-trigger" data-menu-toggle="awsDropdown"
                            title="{{ __('ui.aws_scope_hint') }}">
                        <span class="ic {{ $navAwsActive && !$navAwsActive->hasCredentials() ? 'warn' : '' }}">
                            <i class="bi bi-cloud-fill"></i>
                        </span>
                        <span class="aws-meta">
                            <span class="nm">{{ $navAwsActive->name ?? __('ui.aws_env_creds') }}</span>
                            <span class="rg">{{ $navAwsActive->region ?? config('aws.region', '—') }}</span>
                        </span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="q-dropdown" id="awsDropdown">
                        <div class="q-dd-title">{{ __('ui.aws_scope_title') }}</div>

                        @if($navAwsAccounts->isEmpty())
                            <div class="aws-row current">
                                <i class="bi bi-info-circle"></i>
                                <div class="min-w-0">
                                    <div class="nm">{{ __('ui.aws_env_creds') }}</div>
                                    <div class="sub">{{ __('ui.aws_no_account') }}</div>
                                </div>
                            </div>
                        @else
                            @foreach($navAwsAccounts as $acc)
                                @php $isScope = $acc->id === $navAwsActive?->id; @endphp
                                <a href="{{ route('admin.aws-scope', $acc) }}"
                                   class="aws-row {{ $isScope ? 'current' : '' }}">
                                    <i class="bi bi-{{ $isScope ? 'check-circle-fill' : 'circle' }}"></i>
                                    <div class="min-w-0">
                                        <div class="nm">{{ $acc->name }}</div>
                                        <div class="sub">
                                            {{ $acc->region }} · {{ $acc->maskedAccessKey() }}
                                            @unless($acc->hasCredentials())
                                                <span style="color:var(--danger-fg);">· {{ __('ui.aws_no_creds') }}</span>
                                            @endunless
                                        </div>
                                    </div>
                                    @if($isScope)<span class="q-chip">{{ __('ui.aws_active') }}</span>@else<span class="pick">{{ __('ui.aws_view') }}</span>@endif
                                </a>
                            @endforeach
                        @endif

                        <div class="q-dd-sep"></div>
                        <a href="{{ route('admin.aws-accounts.index') }}" class="q-dd-item">
                            <i class="bi bi-gear"></i> {{ __('ui.aws_manage') }}
                        </a>
                    </div>
                </div>

                @auth
                <div class="q-user q-menu" id="qUser">
                    <button type="button" class="q-user-trigger" data-menu-toggle="qUserDropdown">
                        <div class="q-avatar">{{ $initials }}</div>
                        <div class="q-user-meta">
                            <div class="nm">{{ $authUser->name }}</div>
                            <div class="rl">{{ $authUser->roleName() }}</div>
                        </div>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <div class="q-dropdown" id="qUserDropdown">
                        <div class="q-dd-head">
                            <div class="q-avatar" style="width:44px;height:44px;font-size:0.95rem;">{{ $initials }}</div>
                            <div style="min-width:0;">
                                <div class="nm">{{ $authUser->name }}</div>
                                <div class="em">{{ $authUser->email }}</div>
                                @if($authUser->hasVerifiedEmail())
                                    <span class="q-chip"><i class="bi bi-patch-check-fill"></i> {{ __('ui.verified') }}</span>
                                @else
                                    <span class="q-chip" style="background:#fef3c7;color:#92400e;">
                                        <i class="bi bi-exclamation-circle-fill"></i> {{ __('ui.unverified') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.profile') }}" class="q-dd-item"><i class="bi bi-person"></i> {{ __('profile.menu') }}</a>
                        <div class="q-dd-sep"></div>
                        <a href="{{ url('/') }}" target="_blank" class="q-dd-item"><i class="bi bi-house-door"></i> {{ __('ui.homepage') }}</a>
                        <a href="{{ route('pageRouteTester') }}" target="_blank" class="q-dd-item"><i class="bi bi-code-slash"></i> {{ __('ui.api_tester') }}</a>
                        <div class="q-dd-sep"></div>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="q-dd-item danger"><i class="bi bi-box-arrow-right"></i> {{ __('ui.sign_out') }}</button>
                        </form>
                    </div>
                </div>
                @endauth
            </div>
        </header>

        {{-- ===== Rail kiri + konten ===== --}}
        <div class="page-body">
            <aside class="rail">
                {{-- Tema: terang / gelap / ikut sistem --}}
                <div class="rail-group theme-group" id="themeToggle">
                    <button type="button" class="rail-btn theme-btn" data-theme-set="light" data-tip="{{ __('ui.theme_light') }}">
                        <i class="bi bi-sun"></i>
                    </button>
                    <button type="button" class="rail-btn theme-btn" data-theme-set="dark" data-tip="{{ __('ui.theme_dark') }}">
                        <i class="bi bi-moon"></i>
                    </button>
                    <button type="button" class="rail-btn theme-btn" data-theme-set="system" data-tip="{{ __('ui.theme_system') }}">
                        <i class="bi bi-circle-half"></i>
                    </button>
                </div>

                {{-- Menu utama + pintasan bawah: satu kelompok, berdiri di tengah layar --}}
                <div class="rail-center">
                    <div class="rail-group rail-main">
                        @foreach($menu as $item)
                            <a href="{{ route($item['route']) }}" data-tip="{{ $item['label'] }}"
                               class="rail-btn {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </a>
                        @endforeach
                    </div>

                    {{-- Bantuan & keluar --}}
                    <div class="rail-group">
                        <a href="{{ url('/') }}" target="_blank" data-tip="{{ __('ui.open_homepage') }}" class="rail-btn">
                            <i class="bi bi-map"></i>
                        </a>
                        <a href="{{ route('pageRouteTester') }}" target="_blank" data-tip="{{ __('ui.api_tester') }}" class="rail-btn">
                            <i class="bi bi-question-circle"></i>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="rail-btn" data-tip="{{ __('ui.sign_out') }}"><i class="bi bi-box-arrow-left"></i></button>
                        </form>
                    </div>
                </div>
            </aside>

            <main class="page-main">
                @include('admin.partials.page-loader')

                @yield('content')
            </main>
        </div>
    </div>

    @include('admin.partials.search-palette', ['menu' => $menu, 'paletteAccounts' => $navAwsAccounts])
    @include('admin.partials.toasts')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ---- Dropdown topbar (profil & akun AWS): satu terbuka pada satu waktu ----
        document.addEventListener('click', (e) => {
            // Klik di dalam dropdown dibiarkan apa adanya (link & submit form tetap jalan).
            if (e.target.closest('.q-dropdown')) return;

            const trigger = e.target.closest('[data-menu-toggle]');
            const target = trigger ? document.getElementById(trigger.dataset.menuToggle) : null;

            document.querySelectorAll('.q-dropdown.show').forEach(d => {
                if (d !== target) d.classList.remove('show');
            });

            target?.classList.toggle('show');
        });

        // ---- Dropdown pengganti <select> ----
        // Select aslinya tidak dibuang: nilainya tetap ikut terkirim saat submit dan
        // event 'change' tetap dilepas, jadi kode halaman yang mendengarkannya tetap jalan.
        // Tandai <select data-native> kalau ada yang mau dibiarkan bawaan.
        (function () {
            function enhance(select) {
                if (select.dataset.gmSelect || select.multiple || select.size > 1) return;
                select.dataset.gmSelect = '1';

                const wrap = document.createElement('div');
                wrap.className = 'gm-select';
                select.parentNode.insertBefore(wrap, select);
                wrap.appendChild(select);
                select.classList.add('gm-select-native');
                select.tabIndex = -1;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'gm-select-btn';
                btn.setAttribute('aria-haspopup', 'listbox');
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<span class="gm-select-label"></span><i class="bi bi-chevron-down gm-select-caret"></i>';

                const panel = document.createElement('div');
                panel.className = 'gm-select-panel';
                panel.setAttribute('role', 'listbox');

                // Judul <optgroup> ikut dirender sebagai pemisah — dipakai mis. saat
                // memilih API key yang bisa berasal dari beberapa akun AWS.
                let lastGroup = null;

                Array.from(select.options).forEach((opt, i) => {
                    const group = opt.parentElement?.tagName === 'OPTGROUP' ? opt.parentElement.label : null;
                    if (group && group !== lastGroup) {
                        const head = document.createElement('div');
                        head.className = 'gm-select-group';
                        head.textContent = group;
                        panel.appendChild(head);
                    }
                    lastGroup = group;

                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'gm-select-opt';
                    row.dataset.value = opt.value;
                    row.style.setProperty('--i', i);
                    row.style.animationDelay = (i * 20) + 'ms';
                    if (opt.disabled) row.disabled = true;
                    row.innerHTML = '<i class="bi bi-check-lg tick"></i><span class="txt"></span>';
                    row.querySelector('.txt').textContent = opt.text;
                    row.setAttribute('role', 'option');
                    panel.appendChild(row);
                });

                wrap.append(btn, panel);

                const rows = () => Array.from(panel.querySelectorAll('.gm-select-opt'));
                let cursor = Math.max(select.selectedIndex, 0);

                function paint() {
                    const value = select.value;
                    btn.querySelector('.gm-select-label').textContent = select.options[select.selectedIndex]?.text ?? '';
                    rows().forEach((r, i) => {
                        r.classList.toggle('sel', r.dataset.value === value);
                        r.classList.toggle('cursor', i === cursor);
                        r.setAttribute('aria-selected', r.dataset.value === value ? 'true' : 'false');
                    });
                }

                function openPanel() {
                    closeAllSelects(wrap);
                    // Buka ke atas kalau ruang di bawah tidak cukup.
                    const room = window.innerHeight - btn.getBoundingClientRect().bottom;
                    wrap.classList.toggle('drop-up', room < 240);
                    wrap.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                    cursor = Math.max(select.selectedIndex, 0);
                    paint();
                    rows()[cursor]?.scrollIntoView({ block: 'nearest' });
                }

                function closePanel() {
                    wrap.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                }

                function choose(value) {
                    if (select.value !== value) {
                        select.value = value;
                        select.dispatchEvent(new Event('input', { bubbles: true }));
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    paint();
                    closePanel();
                    btn.focus();
                }

                btn.addEventListener('click', () => {
                    wrap.classList.contains('open') ? closePanel() : openPanel();
                });

                panel.addEventListener('click', (e) => {
                    const row = e.target.closest('.gm-select-opt');
                    if (row && !row.disabled) choose(row.dataset.value);
                });

                btn.addEventListener('keydown', (e) => {
                    const isOpen = wrap.classList.contains('open');

                    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key) && !isOpen) {
                        e.preventDefault();
                        openPanel();
                        return;
                    }
                    if (!isOpen) return;

                    const list = rows();
                    if (e.key === 'Escape') { e.preventDefault(); closePanel(); }
                    else if (e.key === 'ArrowDown') { e.preventDefault(); cursor = Math.min(cursor + 1, list.length - 1); paint(); list[cursor].scrollIntoView({ block: 'nearest' }); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); cursor = Math.max(cursor - 1, 0); paint(); list[cursor].scrollIntoView({ block: 'nearest' }); }
                    else if (e.key === 'Home') { e.preventDefault(); cursor = 0; paint(); }
                    else if (e.key === 'End') { e.preventDefault(); cursor = list.length - 1; paint(); }
                    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choose(list[cursor].dataset.value); }
                });

                // Kalau nilainya diubah dari kode lain, labelnya ikut menyesuaikan.
                select.addEventListener('change', paint);

                paint();
            }

            function closeAllSelects(except) {
                document.querySelectorAll('.gm-select.open').forEach((el) => {
                    if (el !== except) {
                        el.classList.remove('open');
                        el.querySelector('.gm-select-btn')?.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.gm-select')) closeAllSelects(null);
            });

            document.querySelectorAll('select:not([data-native])').forEach(enhance);

            // Halaman bisa memanggil ini setelah menyisipkan select baru (mis. di modal).
            window.gmEnhanceSelects = (root = document) =>
                root.querySelectorAll('select:not([data-native])').forEach(enhance);
        })();

        // ---- Lihat/sembunyikan kata sandi ----
        document.addEventListener('click', (e) => {
            const eye = e.target.closest('[data-toggle-pw]');
            if (!eye) return;

            const input = document.getElementById(eye.dataset.togglePw);
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eye.classList.toggle('on', show);
            eye.setAttribute('aria-pressed', show ? 'true' : 'false');
            eye.setAttribute('aria-label', show ? @json(__('ui.hide_password')) : @json(__('ui.show_password')));
            eye.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';

            // Kembalikan fokus tanpa memindahkan kursor ke awal teks.
            const end = input.value.length;
            input.focus();
            try { input.setSelectionRange(end, end); } catch (err) { /* input tipe tertentu */ }
        });

        // ---- Umpan balik langsung: ikon berputar sebelum halaman berganti ----
        document.addEventListener('click', (e) => {
            const spin = e.target.closest('[data-spin]');
            spin?.querySelector('i')?.classList.add('spin');
        });

        document.addEventListener('click', (e) => {
            const row = e.target.closest('.aws-row[href]');
            if (!row || row.classList.contains('current')) return;
            row.classList.add('busy');
            const icon = row.querySelector('i');
            if (icon) icon.className = 'bi bi-arrow-repeat spin';
            const pick = row.querySelector('.pick');
            if (pick) pick.textContent = @json(__('ui.loading'));
        });

        // ---- Bahasa: indikator meluncur dulu, pindah halaman setelahnya ----
        document.getElementById('langSwitch')?.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-idx]');
            if (!link) return;

            const box = link.closest('.q-lang');
            if (box.dataset.active === link.dataset.idx) {
                e.preventDefault(); // sudah aktif, tidak perlu muat ulang
                return;
            }

            const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (still) return; // biarkan navigasi biasa

            e.preventDefault();
            box.dataset.active = link.dataset.idx;
            box.querySelectorAll('a[data-idx]').forEach(a => a.classList.toggle('active', a === link));
            setTimeout(() => { window.location.href = link.href; }, 230);
        });

        // ---- Tema: light / dark / system (disimpan di localStorage) ----
        (function () {
            const group = document.getElementById('themeToggle');
            if (!group) return;

            const current = () => document.documentElement.getAttribute('data-theme') || 'system';
            const paint = () => group.querySelectorAll('[data-theme-set]').forEach(
                b => b.classList.toggle('active', b.dataset.themeSet === current())
            );

            group.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-theme-set]');
                if (!btn) return;

                const root = document.documentElement;
                root.classList.add('theme-anim');
                root.setAttribute('data-theme', btn.dataset.themeSet);
                try { localStorage.setItem('gm-theme', btn.dataset.themeSet); } catch (err) { /* mode privat */ }

                paint();
                btn.classList.add('pop');
                setTimeout(() => btn.classList.remove('pop'), 450);
                setTimeout(() => root.classList.remove('theme-anim'), 350);

                // Lepas fokus supaya grup langsung menciut lagi setelah memilih.
                btn.blur();
            });

            paint();
        })();
    </script>
    @stack('scripts')
</body>

</html>
