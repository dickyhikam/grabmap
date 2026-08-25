<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('apikeys.share_report_title')) | GrabMaps</title>
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
        :root {
            color-scheme: light;
            --green: #00B14F;
            --green-dark: #009640;
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

        * { -webkit-font-smoothing: antialiased; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--page);
            color: var(--ink);
            font-size: 0.875rem;
            margin: 0;
        }

        .display-font { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }

        /* Kerangkanya mengikuti panel admin v2: topbar mengambang berisi pil
           brand di kiri dan kendali di kanan, lalu isi halaman di bawahnya. */
        .report-wrap {
            max-width: 1460px;
            margin: 0 auto;
            padding: 18px 22px 34px;
        }

        @media (max-width: 860px) {
            .report-wrap { padding: 12px 12px 26px; }
        }

        .report-top {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .report-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--card);
            border-radius: 999px;
            padding: 9px 22px 9px 10px;
            text-decoration: none;
            box-shadow: var(--shadow-card);
        }

        .report-brand img { width: 30px; height: 30px; object-fit: contain; }
        .report-brand span {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: -0.03em;
            color: var(--ink);
        }

        .report-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; flex-wrap: wrap; }

        /* Tab pemilih key — bentuknya sama dengan tab di panel admin. */
        .rp-tabs {
            display: inline-flex; gap: 4px;
            background: var(--card); border-radius: 999px;
            padding: 5px; box-shadow: var(--shadow-card);
            max-width: 100%; overflow-x: auto; scrollbar-width: none;
        }
        .rp-tabs::-webkit-scrollbar { display: none; }

        .rp-tab {
            display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
            border: none; background: none; cursor: pointer; text-decoration: none;
            border-radius: 999px; padding: 9px 16px;
            font-size: 0.8rem; font-weight: 600; color: var(--muted);
            transition: background 0.18s, color 0.18s;
        }
        .rp-tab:hover { color: var(--ink); }
        .rp-tab.on { background: var(--green); color: #fff; box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3); }

        @media (max-width: 1000px) {
            .rp-tabs { order: 3; width: 100%; }
        }

        @media print { .rp-tabs { display: none !important; } }

        .lang-pick {
            display: inline-flex;
            gap: 2px;
            background: var(--card);
            border-radius: 999px;
            padding: 4px;
            box-shadow: var(--shadow-card);
        }

        .lang-pick a {
            min-width: 38px;
            height: 32px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--muted);
            transition: background 0.16s, color 0.16s;
        }

        .lang-pick a:hover { color: var(--ink); }
        .lang-pick a.on { background: var(--green); color: #fff; }

        /* Pemilih tema — pembaca laporan ini bukan pengguna panel, jadi
           pilihannya cukup disimpan di browsernya sendiri. */
        .theme-pick {
            display: inline-flex;
            gap: 2px;
            background: var(--card);
            border-radius: 999px;
            padding: 4px;
            box-shadow: var(--shadow-card);
        }

        .theme-pick button {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            background: none;
            color: var(--muted);
            font-size: 0.8rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.16s, color 0.16s, transform 0.16s cubic-bezier(0.34, 1.5, 0.5, 1);
        }

        .theme-pick button:hover { color: var(--ink); transform: scale(1.08); }
        .theme-pick button.on { background: var(--green); color: #fff; }

        .report-foot {
            margin-top: 22px;
            background: var(--card);
            border-radius: var(--r-card);
            box-shadow: var(--shadow-card);
            padding: 14px 18px;
            font-size: 0.72rem;
            color: var(--muted);
            text-align: center;
            line-height: 1.6;
        }

        @media print {
            .report-actions, .theme-pick, .lang-pick { display: none !important; }
            .report-brand, .report-foot { box-shadow: none; }
        }

        .q-card {
            background: var(--card);
            border-radius: var(--r-card);
            box-shadow: var(--shadow-card);
            padding: 18px 20px;
        }

        .q-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .q-card-title { font-weight: 700; font-size: 0.92rem; }
        .q-card-sub { font-size: 0.72rem; color: var(--muted); margin-top: 2px; }

        .q-icon-box {
            width: 36px; height: 36px; border-radius: 12px;
            background: var(--green-soft); color: var(--green-text);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.95rem;
        }

        .q-num { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; letter-spacing: -0.03em; }
        .q-num .cents { color: var(--faint); font-weight: 700; }

        .q-pill {
            display: inline-flex; align-items: center; gap: 7px;
            background: var(--card); border: none; border-radius: 999px;
            padding: 9px 16px; font-size: 0.78rem; font-weight: 600;
            color: var(--ink); box-shadow: var(--shadow-card); cursor: pointer;
            text-decoration: none;
        }

        .q-page-head {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 16px;
        }

        .q-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800; font-size: 1.45rem; letter-spacing: -0.03em; margin: 0;
        }

        .q-title .soft { color: var(--muted); font-weight: 700; }

        .q-alert {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 16px; border-radius: 16px; margin-bottom: 16px;
            background: var(--warn-soft); color: var(--ink);
        }

        .q-alert.bad { background: var(--danger-soft); }
        .q-alert-icon { font-size: 1.1rem; color: var(--warn-fg); }
        .q-alert.bad .q-alert-icon { color: var(--danger-fg); }
        .q-alert-body { font-size: 0.82rem; line-height: 1.45; }

        .q-empty { text-align: center; padding: 34px 10px; color: var(--muted); }
        .q-empty > i { font-size: 1.8rem; display: block; margin-bottom: 8px; color: var(--faint); }

        .select {
            border: none; background: var(--surface); color: var(--ink);
            border-radius: 999px; padding: 9px 14px; font-size: 0.78rem; font-weight: 600;
        }

        @stack('styles')
    </style>
</head>

<body>
    <div class="report-wrap">
        <header class="report-top">
            <a href="#" class="report-brand" onclick="return false;">
                <img src="{{ asset('logo2.png') }}" alt="GrabMaps">
                <span>GrabMaps</span>
            </a>

            @yield('top-tabs')

            <div class="report-actions">
                @yield('header-actions')

                {{-- Bahasa: tautan biasa supaya tetap jalan tanpa JS, dan
                     parameter lain (rentang tanggal, key) ikut terbawa. --}}
                <div class="lang-pick" role="group" aria-label="{{ __('ui.language') }}">
                    @foreach(['en' => 'EN', 'id' => 'ID'] as $code => $label)
                        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
                           class="{{ app()->getLocale() === $code ? 'on' : '' }}" data-no-loader>{{ $label }}</a>
                    @endforeach
                </div>

                <div class="theme-pick" role="group" aria-label="{{ __('ui.theme') }}">
                    <button type="button" data-theme-pick="light" title="{{ __('ui.theme_light') }}"><i class="bi bi-sun-fill"></i></button>
                    <button type="button" data-theme-pick="dark" title="{{ __('ui.theme_dark') }}"><i class="bi bi-moon-stars-fill"></i></button>
                    <button type="button" data-theme-pick="system" title="{{ __('ui.theme_system') }}"><i class="bi bi-circle-half"></i></button>
                </div>
            </div>
        </header>

        @yield('content')

        <footer class="report-foot">
            @yield('footer-note', __('apikeys.share_disclaimer'))
        </footer>
    </div>

    <script>
        // Pemilih tema: disimpan dengan kunci yang sama seperti panel admin.
        (function () {
            const root = document.documentElement;
            const buttons = document.querySelectorAll('[data-theme-pick]');

            function paint(mode) {
                buttons.forEach((b) => b.classList.toggle('on', b.dataset.themePick === mode));
            }

            let current = 'system';
            try { current = localStorage.getItem('gm-theme') || 'system'; } catch (e) {}
            paint(current);

            buttons.forEach((btn) => btn.addEventListener('click', () => {
                const mode = btn.dataset.themePick;
                root.setAttribute('data-theme', mode);
                try { localStorage.setItem('gm-theme', mode); } catch (e) {}
                paint(mode);
            }));
        })();
    </script>

    @stack('scripts')
</body>
</html>
