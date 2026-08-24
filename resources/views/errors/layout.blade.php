<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Error') · GrabMaps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('logo2.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">

    {{-- Tema dipasang sebelum CSS, memakai kunci yang sama dengan panel admin,
         supaya halaman error tidak berkedip putih saat panelnya bertema gelap. --}}
    <script>
        (function() {
            try {
                var mode = localStorage.getItem('gm-theme') || 'system';
                if (mode !== 'system') document.documentElement.setAttribute('data-theme', mode);
            } catch (e) {}
        })();
    </script>

    <style>
        :root {
            --bg: #eef0f2;
            --card: #ffffff;
            --surface: #f4f6f7;
            --line: #e6e9eb;
            --ink: #141b18;
            --muted: #7c8a84;
            --faint: #aab5b0;
            --green: #00b14f;
            --green-dark: #009944;
            --green-soft: #e6f7ee;
            --green-text: #0a7c3c;
            --shadow: 0 1px 3px rgba(20, 27, 24, 0.04), 0 12px 34px rgba(20, 27, 24, 0.07);
            --stage: #f4f8f5;
        }

        :root[data-theme="dark"] {
            --bg: #0b0d0c;
            --card: #141917;
            --surface: #1b2220;
            --line: #26302c;
            --ink: #eef2f0;
            --muted: #8b9a94;
            --faint: #5f6d68;
            --green-soft: rgba(0, 177, 79, 0.16);
            --green-text: #5ddb96;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.4), 0 16px 44px rgba(0, 0, 0, 0.5);
            --stage: #10201a;
        }

        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]):not([data-theme="dark"]) {
                --bg: #0b0d0c;
                --card: #141917;
                --surface: #1b2220;
                --line: #26302c;
                --ink: #eef2f0;
                --muted: #8b9a94;
                --faint: #5f6d68;
                --green-soft: rgba(0, 177, 79, 0.16);
                --green-text: #5ddb96;
                --shadow: 0 1px 3px rgba(0, 0, 0, 0.4), 0 16px 44px rgba(0, 0, 0, 0.5);
                --stage: #10201a;
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100%;
        }

        /* Halaman error selalu muat satu layar: tinggi dikunci ke viewport dan
           ukuran isinya ikut tinggi layar lewat clamp(). Gulir baru diizinkan
           kalau layarnya benar-benar pendek — terpotong lebih buruk daripada
           harus menggulir sedikit. */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--ink);
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(14px, 3vh, 28px) 20px;
            gap: clamp(10px, 2vh, 20px);
        }

        @media (max-height: 600px) {
            body {
                height: auto;
                min-height: 100vh;
                overflow: auto;
            }
        }

        /* ---------- Brand ---------- */
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--card);
            border-radius: 999px;
            padding: 9px 18px 9px 10px;
            box-shadow: var(--shadow);
            text-decoration: none;
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 0.92rem;
            letter-spacing: -0.02em;
            transition: transform 0.18s cubic-bezier(0.34, 1.4, 0.5, 1);
            animation: dropIn 0.5s cubic-bezier(0.34, 1.4, 0.5, 1) both;
        }

        .brand:hover {
            transform: translateY(-2px);
        }

        .brand img {
            width: 26px;
            height: 26px;
        }

        @keyframes dropIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        /* ---------- Kartu ---------- */
        .card {
            width: 100%;
            max-width: 620px;
            background: var(--card);
            border-radius: 32px;
            box-shadow: var(--shadow);
            padding: clamp(16px, 2.6vh, 26px) clamp(20px, 4vw, 30px) clamp(20px, 3vh, 30px);
            text-align: center;
            animation: riseIn 0.55s cubic-bezier(0.34, 1.3, 0.5, 1) 0.06s both;
        }

        @keyframes riseIn {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        /* ---------- Panggung gambar ---------- */
        .stage {
            border-radius: 24px;
            padding: clamp(10px, 1.6vh, 18px);
            margin-bottom: clamp(14px, 2.2vh, 22px);
            background: var(--stage);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Ilustrasi yang latarnya sudah gelap (mis. 403) diberi panggung gelap
           supaya tepinya menyatu, bukan jadi kotak hitam di tengah kartu. */
        .stage.dark {
            background: #050806;
        }

        .stage.dark::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(60% 60% at 50% 50%, rgba(0, 177, 79, 0.14), transparent 70%);
            pointer-events: none;
        }

        .stage img {
            width: 100%;
            max-width: 420px;
            max-height: 42vh;
            height: auto;
            object-fit: contain;
            display: block;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            50% {
                transform: translateY(-9px);
            }
        }

        /* Panggung tanpa gambar: kode besar + ikon. */
        .stage-fallback {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: clamp(18px, 4vh, 34px) 0;
        }

        .stage-fallback .glyph {
            font-size: clamp(2.6rem, 8vh, 4rem);
            line-height: 1;
            animation: float 6s ease-in-out infinite;
        }

        .stage-fallback .code {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 3.6rem;
            letter-spacing: -0.05em;
            line-height: 1;
            color: var(--green);
        }

        /* ---------- Teks ---------- */
        /* Kode errornya tetap tertulis, hanya tidak lagi berbentuk pil. */
        .label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: clamp(1.3rem, 3.4vh, 1.7rem);
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .lead {
            font-size: clamp(0.82rem, 1.9vh, 0.9rem);
            line-height: 1.6;
            color: var(--muted);
            max-width: 46ch;
            margin: 0 auto;
        }

        /* ---------- Tombol ---------- */
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: clamp(16px, 2.6vh, 24px);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 999px;
            padding: clamp(10px, 1.6vh, 13px) 26px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.16s, color 0.16s, box-shadow 0.16s, transform 0.12s;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: var(--green);
            color: #fff;
            box-shadow: 0 6px 18px rgba(0, 177, 79, 0.32);
        }

        .btn-primary:hover {
            background: var(--green-dark);
            box-shadow: 0 9px 22px rgba(0, 177, 79, 0.4);
        }

        .btn-ghost {
            background: var(--surface);
            color: var(--ink);
        }

        .btn-ghost:hover {
            background: var(--line);
        }

        /* ---------- Catatan kaki ---------- */
        .footer-note {
            margin-top: clamp(14px, 2.4vh, 22px);
            padding-top: clamp(12px, 2vh, 18px);
            border-top: 1px solid var(--line);
            font-size: 0.76rem;
            color: var(--muted);
            line-height: 1.7;
        }

        .footer-note a {
            color: var(--green-text);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        @media (max-width: 520px) {
            .card {
                padding: 20px 20px 26px;
                border-radius: 26px;
            }

            h1 {
                font-size: 1.4rem;
            }

            .btn {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .brand,
            .card,
            .stage img,
            .stage-fallback .glyph {
                animation: none;
            }
        }
    </style>
</head>

<body>
    <a href="{{ url('/') }}" class="brand">
        <img src="{{ asset('logo2.png') }}" alt="GrabMaps"> GrabMaps
    </a>

    <main class="card">
        <div class="stage @yield('stage', 'soft')">
            @hasSection('image')
            @yield('image')
            @else
            <div class="stage-fallback">
                @hasSection('icon')
                <span class="glyph">@yield('icon')</span>
                @endif
                @hasSection('code')
                <span class="code">@yield('code')</span>
                @endif
            </div>
            @endif
        </div>

        <span class="label" style="display: none;">@yield('label')</span>

        <h1>@yield('title-h1')</h1>
        <p class="lead">@yield('lead')</p>

        <div class="actions">
            @yield('actions')
        </div>

        @yield('footer-note')
    </main>
</body>

</html>