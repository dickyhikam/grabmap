<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Error') · GrabMaps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: {{ $accent ?? '#00B14F' }};
            --accent-dark: {{ $accentDark ?? '#008b3d' }};
            --accent-tint: {{ $accentTint ?? 'rgba(0,177,79,0.15)' }};
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #fafbfc;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(700px 400px at 15% 20%, var(--accent-tint), transparent 60%),
                radial-gradient(700px 400px at 85% 30%, rgba(245,158,11,0.08), transparent 60%),
                radial-gradient(700px 400px at 50% 100%, rgba(124,58,237,0.06), transparent 60%);
            animation: floatBg 22s ease-in-out infinite alternate;
            z-index: 0;
        }
        @keyframes floatBg {
            0% { transform: translate(0,0) scale(1); }
            100% { transform: translate(-30px, 20px) scale(1.05); }
        }

        .wrap {
            position: relative;
            z-index: 1;
            max-width: 640px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.7s cubic-bezier(0.4,0,0.2,1) both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Status badge */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            border: 1px solid var(--accent-tint);
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--accent-dark);
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-status .dot {
            width: 8px; height: 8px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--accent) 55%, transparent); }
            50% { box-shadow: 0 0 0 8px transparent; }
        }

        /* Error code — big monospace */
        .error-code {
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: clamp(4rem, 12vw, 7rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            position: relative;
            margin-bottom: 8px;
        }
        .error-code::before {
            content: attr(data-code);
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: blur(28px);
            opacity: 0.4;
            z-index: -1;
        }

        /* Icon container */
        .icon-container {
            width: 88px;
            height: 88px;
            margin: 0 auto 20px;
            border-radius: 24px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 25%, #fff), color-mix(in srgb, var(--accent) 12%, #fff));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow:
                0 20px 40px color-mix(in srgb, var(--accent) 20%, transparent),
                inset 0 1px 0 rgba(255,255,255,0.5);
            animation: gentleFloat 4s ease-in-out infinite;
        }
        @keyframes gentleFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-6px) rotate(-3deg); }
        }

        h1 {
            font-size: clamp(1.6rem, 3.5vw, 2.2rem);
            font-weight: 900;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
            color: #111827;
        }

        p.lead {
            font-size: 1rem;
            color: #4b5563;
            line-height: 1.65;
            max-width: 520px;
            margin: 0 auto 28px;
        }

        /* Extra info card (optional per-error) */
        .info-card {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 24px;
            text-align: left;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
        }
        .info-card p { font-size: 0.86rem; color: #4b5563; line-height: 1.6; margin: 0; }
        .info-card p + p { margin-top: 8px; }
        .info-card code {
            background: rgba(0,0,0,0.04);
            padding: 2px 6px;
            border-radius: 5px;
            font-size: 0.82rem;
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent-dark);
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
            box-shadow: 0 8px 20px color-mix(in srgb, var(--accent) 35%, transparent);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px color-mix(in srgb, var(--accent) 45%, transparent);
        }
        .btn-ghost {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            color: #1f2937;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .btn-ghost:hover {
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
        }

        .footer-note {
            margin-top: 30px;
            font-size: 0.78rem;
            color: #9ca3af;
        }
        .footer-note a { color: var(--accent-dark); font-weight: 600; text-decoration: none; }
        .footer-note a:hover { text-decoration: underline; }
    </style>

    @yield('head')
</head>

<body>
    <div class="wrap">
        @yield('badge')

        @hasSection('code')
            <div class="error-code" data-code="@yield('code')">@yield('code')</div>
        @endif

        @hasSection('icon')
            <div class="icon-container">@yield('icon')</div>
        @endif

        <h1>@yield('title-h1')</h1>

        @hasSection('lead')
            <p class="lead">@yield('lead')</p>
        @endif

        @yield('extra')

        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="bi bi-house-fill"></i> Back to Home
                </a>
            @endif
        </div>

        @yield('footer-note')
    </div>
</body>

</html>
