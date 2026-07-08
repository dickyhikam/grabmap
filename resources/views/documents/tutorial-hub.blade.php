<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GrabMaps + AWS Location Service — Start Building</title>

    <meta name="description" content="Two paths to build with GrabMaps + AWS Location Service across Southeast Asia. Set up an API Key, or jump straight into the API reference & playground.">
    <meta name="keywords" content="GrabMaps, AWS Location Service, tutorial, hub, Southeast Asia, API Key, developer">
    <meta property="og:title" content="GrabMaps + AWS Location Service — Start Building">
    <meta property="og:description" content="Pick your path: set up a new AWS API Key or use one you already have. Interactive playground included.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">
    <meta property="og:locale:alternate" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --grab-green: #00B14F;
            --grab-green-dark: #008b3d;
            --grab-green-light: #dcfce7;
            --amber: #f59e0b;
            --amber-dark: #92400e;
            --blue: #2563eb;
            --purple: #7c3aed;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-600: #6b7280;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --bg: #fafbfc;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.05), 0 10px 25px rgba(0,0,0,0.08);
            --shadow-lg: 0 12px 24px rgba(0,0,0,0.08), 0 25px 50px rgba(0,0,0,0.12);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.1), 0 40px 80px rgba(0,0,0,0.15);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 80px; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
        }
        code, pre { font-family: 'JetBrains Mono', ui-monospace, 'SF Mono', Consolas, monospace; }
        a { text-decoration: none; }

        /* Reading progress */
        .read-progress {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; z-index: 1000; pointer-events: none;
        }
        .read-progress-fill {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, var(--grab-green), #10d966, var(--amber));
            transition: width 0.1s linear;
            box-shadow: 0 0 10px rgba(0,177,79,0.5);
        }

        /* Nav */
        .topnav {
            position: fixed; top: 3px; left: 0; right: 0;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            z-index: 999; padding: 12px 24px;
            display: flex; align-items: center; gap: 12px;
        }
        .topnav-brand {
            display: flex; align-items: center; gap: 10px;
            font-weight: 800; color: var(--gray-800);
            font-size: 0.95rem;
        }
        .topnav-brand .logo-dot {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.9rem;
            box-shadow: 0 2px 6px rgba(0,177,79,0.35);
        }
        .topnav-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }
        .btn-nav {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px;
            font-size: 0.8rem; font-weight: 600;
            transition: all 0.15s;
            border: 1px solid var(--gray-200);
            color: var(--gray-800); background: #fff;
        }
        .btn-nav:hover { background: var(--gray-50); transform: translateY(-1px); box-shadow: var(--shadow-sm); color: var(--gray-800); }
        .btn-nav.primary { background: var(--grab-green); color: #fff; border-color: var(--grab-green); }
        .btn-nav.primary:hover { background: var(--grab-green-dark); color: #fff; }

        .lang-toggle {
            display: flex;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 2px;
            gap: 2px;
        }
        .lang-toggle button {
            background: transparent; border: 0;
            color: var(--gray-600);
            font-size: 0.72rem; font-weight: 700;
            padding: 5px 10px; cursor: pointer;
            letter-spacing: 0.5px;
            border-radius: 6px; transition: all 0.12s;
        }
        .lang-toggle button:hover { background: rgba(0,0,0,0.04); color: var(--gray-800); }
        .lang-toggle button.active {
            background: #fff; color: var(--grab-green);
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        /* Hero */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 80px 24px 60px;
            overflow: hidden;
            background: radial-gradient(ellipse at top, #f0fdf4 0%, #fafbfc 60%);
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -20%; right: -10%;
            width: 700px; height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,177,79,0.10), transparent 70%);
            filter: blur(60px);
            animation: floatBlob 22s ease-in-out infinite;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -25%; left: -15%;
            width: 900px; height: 900px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.08), transparent 70%);
            filter: blur(80px);
            animation: floatBlob 28s ease-in-out infinite reverse;
        }
        @keyframes floatBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -40px) scale(1.05); }
            66% { transform: translate(-20px, 30px) scale(0.95); }
        }

        .hero-inner {
            max-width: 1080px; width: 100%;
            text-align: center;
            position: relative; z-index: 2;
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 18px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0,177,79,0.15);
            border-radius: 999px;
            font-size: 0.78rem; font-weight: 600;
            color: var(--grab-green-dark);
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            animation: fadeInUp 0.7s ease both;
        }
        .badge-dot {
            width: 8px; height: 8px;
            background: var(--grab-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0,177,79,0.5); }
            50% { box-shadow: 0 0 0 8px rgba(0,177,79,0); }
        }

        .hero h1 {
            font-size: clamp(2.4rem, 5vw, 4rem);
            font-weight: 900;
            line-height: 1.05;
            margin: 0 0 20px;
            letter-spacing: -0.025em;
            color: var(--gray-900);
            animation: fadeInUp 0.8s ease 0.1s both;
        }
        .hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--grab-green) 0%, #10d966 40%, var(--amber) 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        .hero p.lead {
            font-size: clamp(1.05rem, 2vw, 1.25rem);
            color: var(--gray-600);
            max-width: 720px;
            margin: 0 auto 40px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        /* Path selector (main CTA) */
        .path-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto 32px;
            animation: fadeInUp 0.9s ease 0.3s both;
        }
        .path-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            border: 1.5px solid var(--gray-200);
            border-radius: 22px;
            padding: 32px 28px 28px;
            text-align: left;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .path-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 22px;
            padding: 2px;
            background: linear-gradient(135deg, transparent, transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            transition: background 0.3s;
            pointer-events: none;
        }
        .path-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.12);
        }
        .path-card.a:hover::before {
            background: linear-gradient(135deg, var(--grab-green), var(--amber));
        }
        .path-card.b:hover::before {
            background: linear-gradient(135deg, var(--purple), var(--blue));
        }

        .path-card .pc-icon {
            width: 64px; height: 64px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin-bottom: 18px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.12);
            transition: transform 0.3s;
        }
        .path-card:hover .pc-icon { transform: scale(1.08) rotate(-3deg); }
        .path-card.a .pc-icon {
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            color: #fff;
        }
        .path-card.b .pc-icon {
            background: linear-gradient(135deg, var(--purple), #6d28d9);
            color: #fff;
        }

        .path-card .pc-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--gray-400);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .path-card.a .pc-label { color: var(--grab-green-dark); }
        .path-card.b .pc-label { color: var(--purple); }

        .path-card h3 {
            margin: 0 0 10px;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.01em;
        }
        .path-card p {
            margin: 0 0 20px;
            color: var(--gray-600);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .pc-meta {
            display: flex; gap: 14px; flex-wrap: wrap;
            font-size: 0.75rem; color: var(--gray-600);
            margin-bottom: 20px;
        }
        .pc-meta span { display: inline-flex; align-items: center; gap: 4px; }
        .pc-meta strong { color: var(--gray-800); font-weight: 700; }

        .pc-actions {
            margin-top: auto;
            display: flex; gap: 10px; flex-wrap: wrap;
        }

        .btn-cta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 0.88rem; font-weight: 700;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-cta.primary-green {
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            color: #fff;
            box-shadow: 0 8px 20px rgba(0,177,79,0.35);
        }
        .btn-cta.primary-green:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0,177,79,0.45);
            color: #fff;
        }
        .btn-cta.primary-purple {
            background: linear-gradient(135deg, var(--purple), #6d28d9);
            color: #fff;
            box-shadow: 0 8px 20px rgba(124,58,237,0.35);
        }
        .btn-cta.primary-purple:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(124,58,237,0.45);
            color: #fff;
        }
        .btn-cta.ghost {
            background: transparent;
            color: var(--gray-800);
            border: 1px solid var(--gray-200);
        }
        .btn-cta.ghost:hover {
            background: var(--gray-50);
            transform: translateY(-1px);
            color: var(--gray-800);
        }

        /* Journey visualization */
        .journey {
            background: linear-gradient(180deg, #ffffff, #f9fafb);
            padding: 80px 24px;
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
        }
        .journey-inner {
            max-width: 1080px;
            margin: 0 auto;
        }
        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }
        .section-header .kicker {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--grab-green-dark);
            margin-bottom: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .section-header .kicker::before,
        .section-header .kicker::after {
            content: '';
            width: 32px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--grab-green));
        }
        .section-header .kicker::after {
            background: linear-gradient(90deg, var(--grab-green), transparent);
        }
        .section-header h2 {
            margin: 0 0 12px;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 900;
            color: var(--gray-900);
            letter-spacing: -0.02em;
        }
        .section-header p {
            margin: 0;
            color: var(--gray-600);
            font-size: 1rem;
            max-width: 640px;
            margin-inline: auto;
        }

        .journey-flow {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            align-items: stretch;
            position: relative;
        }
        @media (max-width: 780px) {
            .journey-flow { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 460px) {
            .journey-flow { grid-template-columns: 1fr; }
        }
        .journey-step {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 22px 18px;
            text-align: center;
            position: relative;
            transition: all 0.3s;
        }
        .journey-step:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--grab-green);
        }
        .journey-step .js-num {
            position: absolute;
            top: -10px; left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            color: #fff;
            width: 26px; height: 26px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 800;
            box-shadow: 0 4px 10px rgba(0,177,79,0.35);
        }
        .journey-step .js-icon {
            font-size: 2.4rem;
            margin: 10px 0 14px;
        }
        .journey-step .js-title {
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        .journey-step .js-desc {
            font-size: 0.78rem;
            color: var(--gray-600);
        }
        .journey-step .js-arrow {
            position: absolute;
            top: 50%;
            right: -18px;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1.4rem;
            z-index: 2;
        }
        .journey-step:last-child .js-arrow { display: none; }
        @media (max-width: 780px) {
            .journey-step .js-arrow { display: none; }
        }

        /* What you can do (features) */
        .features {
            padding: 80px 24px;
            background: #fafbfc;
        }
        .features-inner {
            max-width: 1080px;
            margin: 0 auto;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .feature-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(20px);
        }
        .feature-card.visible {
            opacity: 1; transform: translateY(0);
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }
        .feature-card .fc-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 14px;
        }
        .feature-card.maps .fc-icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: var(--grab-green-dark); }
        .feature-card.places .fc-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: var(--purple); }
        .feature-card.routes .fc-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--amber-dark); }
        .feature-card.matrix .fc-icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: var(--blue); }
        .feature-card h4 {
            margin: 0 0 6px;
            font-size: 1rem;
            font-weight: 800;
            color: var(--gray-900);
        }
        .feature-card p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--gray-600);
            line-height: 1.55;
        }

        /* Trust bar */
        .trust {
            padding: 60px 24px;
            background: linear-gradient(135deg, #ffffff, #f0fdf4);
            text-align: center;
            border-top: 1px solid var(--gray-200);
        }
        .trust-inner { max-width: 900px; margin: 0 auto; }
        .country-strip {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .country-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--gray-800);
            transition: all 0.2s;
        }
        .country-pill:hover {
            transform: translateY(-2px);
            border-color: var(--grab-green);
            box-shadow: var(--shadow-sm);
        }
        .country-pill .flag { font-size: 1.05rem; }

        /* Mini FAQ */
        .faq-mini {
            padding: 80px 24px;
            background: linear-gradient(180deg, #f0fdf4, #fafbfc);
        }
        .faq-mini-inner { max-width: 820px; margin: 0 auto; }
        .faq-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.2s;
        }
        .faq-item:hover { box-shadow: var(--shadow-md); }
        .faq-item summary {
            padding: 18px 22px;
            font-weight: 700;
            color: var(--gray-900);
            cursor: pointer;
            list-style: none;
            display: flex; align-items: center; gap: 12px;
            font-size: 0.92rem;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after {
            content: '+';
            margin-left: auto;
            width: 26px; height: 26px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800;
            transition: transform 0.2s;
        }
        .faq-item[open] summary::after {
            content: '−';
            background: var(--grab-green-light);
            color: var(--grab-green-dark);
        }
        .faq-item .faq-body {
            padding: 0 22px 20px;
            color: var(--gray-800);
            font-size: 0.86rem;
            line-height: 1.7;
        }

        /* Final CTA */
        .final-cta {
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark) 60%, var(--amber));
            padding: 80px 24px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .final-cta::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 800px; height: 800px;
            background: radial-gradient(circle, rgba(255,255,255,0.1), transparent 70%);
            border-radius: 50%;
        }
        .final-cta h2 {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            margin: 0 0 16px;
            font-weight: 900;
            letter-spacing: -0.02em;
            position: relative;
        }
        .final-cta p {
            font-size: 1.05rem;
            opacity: 0.95;
            max-width: 560px;
            margin: 0 auto 32px;
            position: relative;
        }
        .final-cta .cta-buttons {
            display: flex; gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
        }
        .final-cta .btn-cta.white {
            background: #fff;
            color: var(--grab-green-dark);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }
        .final-cta .btn-cta.white:hover {
            background: #f9fafb;
            transform: translateY(-3px);
            color: var(--grab-green-dark);
        }
        .final-cta .btn-cta.outline {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.4);
            color: #fff;
        }
        .final-cta .btn-cta.outline:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            padding: 40px 24px;
            text-align: center;
            font-size: 0.82rem;
            color: var(--gray-600);
            border-top: 1px solid var(--gray-200);
            background: #fff;
        }
        .footer a { color: var(--grab-green-dark); font-weight: 600; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Reveal-on-scroll base */
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 640px) {
            .path-card { padding: 26px 22px 24px; }
            .topnav { padding: 10px 14px; gap: 8px; }
        }
    </style>
</head>

<body>
    <div class="read-progress"><div class="read-progress-fill" id="readProgress"></div></div>

    <nav class="topnav">
        <a href="/" class="topnav-brand">
            <span class="logo-dot">🗺️</span>
            <span data-i18n="brand">GrabMaps Tutorial Hub</span>
        </a>
        <div class="topnav-actions">
            <a href="/docs/aws-api" class="btn-nav"><i class="bi bi-book"></i> <span data-i18n="nav_reference">API Reference</span></a>
            <a href="/tester-api" class="btn-nav primary"><i class="bi bi-play-circle-fill"></i> <span data-i18n="nav_playground">Playground</span></a>
            <div class="lang-toggle" role="group" aria-label="Language">
                <button type="button" data-lang="en" class="active">EN</button>
                <button type="button" data-lang="id">ID</button>
            </div>
        </div>
    </nav>

    <!-- HERO with path selector -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="badge-dot"></span> <span data-i18n="hero_badge">AWS Location Service · GrabMaps · Southeast Asia</span>
            </div>

            <h1 data-i18n-html="hero_title">
                Start Building With<br>
                <span class="gradient-text">GrabMaps + AWS</span>
            </h1>

            <p class="lead" data-i18n="hero_lead">
                Pick your path — set up a brand new AWS API Key from scratch, or jump straight into the interactive API playground if you already have one.
            </p>

            <div class="path-grid">
                <!-- PATH A: I don't have a key -->
                <div class="path-card a">
                    <div class="pc-icon">🔑</div>
                    <div class="pc-label"><i class="bi bi-arrow-right-circle-fill"></i> <span data-i18n="path_a_label">Path A — Beginner</span></div>
                    <h3 data-i18n="path_a_title">I need to create an AWS API Key</h3>
                    <p data-i18n="path_a_desc">Never used AWS Location Service before? Follow the 8-step visual tutorial with real Console screenshots — from signup to a working key you can use immediately.</p>
                    <div class="pc-meta">
                        <span><i class="bi bi-clock"></i> <span data-i18n-html="path_a_meta_time"><strong>~10 min</strong></span></span>
                        <span><i class="bi bi-list-check"></i> <span data-i18n-html="path_a_meta_steps"><strong>8 steps</strong></span></span>
                        <span><i class="bi bi-shield-check"></i> <span data-i18n-html="path_a_meta_diff"><strong>Beginner</strong></span></span>
                    </div>
                    <div class="pc-actions">
                        <a href="/tutorial/aws-key" class="btn-cta primary-green">
                            <span data-i18n="path_a_cta">Start Tutorial</span> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- PATH B: I have a key -->
                <div class="path-card b">
                    <div class="pc-icon">🚀</div>
                    <div class="pc-label"><i class="bi bi-arrow-right-circle-fill"></i> <span data-i18n="path_b_label">Path B — Ready to build</span></div>
                    <h3 data-i18n="path_b_title">I already have an API Key</h3>
                    <p data-i18n="path_b_desc">Jump straight into the interactive API reference with a live inspector, or hit the playground to test endpoints against your key in real time — no coding required.</p>
                    <div class="pc-meta">
                        <span><i class="bi bi-lightning-charge-fill"></i> <span data-i18n-html="path_b_meta_1"><strong>Interactive</strong></span></span>
                        <span><i class="bi bi-code-slash"></i> <span data-i18n-html="path_b_meta_2"><strong>Live examples</strong></span></span>
                        <span><i class="bi bi-braces"></i> <span data-i18n-html="path_b_meta_3"><strong>No-code</strong></span></span>
                    </div>
                    <div class="pc-actions">
                        <a href="/docs/aws-api" class="btn-cta primary-purple">
                            <span data-i18n="path_b_cta">Open API Reference</span> <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="/tester-api" class="btn-cta ghost">
                            <i class="bi bi-play-circle"></i> <span data-i18n="path_b_cta2">Playground</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Journey overview -->
    <section class="journey">
        <div class="journey-inner">
            <div class="section-header">
                <div class="kicker" data-i18n="journey_kicker">How it fits together</div>
                <h2 data-i18n="journey_title">From AWS Console to your app in 4 steps</h2>
                <p data-i18n="journey_desc">GrabMaps is served through AWS Location Service. Understanding the flow makes debugging easier once you're up and running.</p>
            </div>
            <div class="journey-flow">
                <div class="journey-step reveal">
                    <span class="js-num">1</span>
                    <div class="js-icon">🏢</div>
                    <div class="js-title" data-i18n="j1_t">AWS Console</div>
                    <div class="js-desc" data-i18n="j1_d">Sign in, pick <code>ap-southeast-1</code></div>
                    <div class="js-arrow">→</div>
                </div>
                <div class="journey-step reveal">
                    <span class="js-num">2</span>
                    <div class="js-icon">🔑</div>
                    <div class="js-title" data-i18n="j2_t">Create API Key</div>
                    <div class="js-desc" data-i18n="j2_d">Restrict actions & referrers</div>
                    <div class="js-arrow">→</div>
                </div>
                <div class="journey-step reveal">
                    <span class="js-num">3</span>
                    <div class="js-icon">📱</div>
                    <div class="js-title" data-i18n="j3_t">Your App</div>
                    <div class="js-desc" data-i18n="j3_d">Web / iOS / Android</div>
                    <div class="js-arrow">→</div>
                </div>
                <div class="journey-step reveal">
                    <span class="js-num">4</span>
                    <div class="js-icon">🗺️</div>
                    <div class="js-title" data-i18n="j4_t">GrabMaps Data</div>
                    <div class="js-desc" data-i18n="j4_d">POI · Routes · Maps · Geocoding</div>
                </div>
            </div>
        </div>
    </section>

    <!-- What you can do (features) -->
    <section class="features">
        <div class="features-inner">
            <div class="section-header">
                <div class="kicker" data-i18n="features_kicker">Capabilities</div>
                <h2 data-i18n="features_title">What you can build with GrabMaps</h2>
                <p data-i18n="features_desc">Four core service families — all bundled with your API Key when you follow this tutorial.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-card maps">
                    <div class="fc-icon">🗺️</div>
                    <h4 data-i18n="f1_t">Maps</h4>
                    <p data-i18n="f1_d">High-resolution vector & raster tiles for the whole SEA region, styled to match your brand.</p>
                </div>
                <div class="feature-card places">
                    <div class="fc-icon">📍</div>
                    <h4 data-i18n="f2_t">Places (Search & Geocode)</h4>
                    <p data-i18n="f2_d">Autocomplete POI, reverse geocode coordinates, search by text — down to alley-level precision.</p>
                </div>
                <div class="feature-card routes">
                    <div class="fc-icon">🛣️</div>
                    <h4 data-i18n="f3_t">Routes</h4>
                    <p data-i18n="f3_d">Turn-by-turn directions, alternative routes, real-time traffic — optimised for SEA road networks.</p>
                </div>
                <div class="feature-card matrix">
                    <div class="fc-icon">🎯</div>
                    <h4 data-i18n="f4_t">Route Matrix</h4>
                    <p data-i18n="f4_d">Calculate distance & time for hundreds of origin-destination pairs at once — perfect for dispatch.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust / country coverage -->
    <section class="trust">
        <div class="trust-inner">
            <div class="section-header">
                <div class="kicker" data-i18n="trust_kicker">Coverage</div>
                <h2 data-i18n="trust_title">Built on real Grab operational data</h2>
                <p data-i18n="trust_desc">GrabMaps is refreshed continuously from millions of daily driver-partner trips across Southeast Asia.</p>
            </div>
            <div class="country-strip">
                <span class="country-pill"><span class="flag">🇸🇬</span> Singapore</span>
                <span class="country-pill"><span class="flag">🇲🇾</span> Malaysia</span>
                <span class="country-pill"><span class="flag">🇹🇭</span> Thailand</span>
                <span class="country-pill"><span class="flag">🇮🇩</span> Indonesia</span>
                <span class="country-pill"><span class="flag">🇻🇳</span> Vietnam</span>
                <span class="country-pill"><span class="flag">🇵🇭</span> Philippines</span>
                <span class="country-pill"><span class="flag">🇰🇭</span> Cambodia</span>
                <span class="country-pill"><span class="flag">🇲🇲</span> Myanmar</span>
            </div>
        </div>
    </section>

    <!-- Mini FAQ -->
    <section class="faq-mini">
        <div class="faq-mini-inner">
            <div class="section-header">
                <div class="kicker" data-i18n="faq_kicker">Quick answers</div>
                <h2 data-i18n="faq_title">Common questions</h2>
            </div>

            <details class="faq-item">
                <summary data-i18n="faq_q1">Do I need to install anything?</summary>
                <div class="faq-body" data-i18n-html="faq_a1">
                    No. Both paths are browser-based. Path A only requires an AWS account (free tier is enough). Path B just needs an API Key you can paste into the inspector — everything runs live in your browser.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q2">Is there a cost?</summary>
                <div class="faq-body" data-i18n-html="faq_a2">
                    AWS Location Service has a <b>3-month trial free tier</b>: <code>10K Places Suggest</code>, <code>20K Geocode/Search</code>, <code>10K Routes</code>, and <code>500K Map tile loads</code> per month. Enough for POC and testing. After the trial, pay-per-request applies — see <a href="https://aws.amazon.com/location/pricing/" target="_blank" rel="noopener">official pricing</a>.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q3">Which countries are covered?</summary>
                <div class="faq-body" data-i18n-html="faq_a3">
                    GrabMaps has native detail across 🇸🇬 Singapore, 🇲🇾 Malaysia, 🇹🇭 Thailand, 🇮🇩 Indonesia, 🇻🇳 Vietnam, 🇵🇭 Philippines, 🇰🇭 Cambodia, and 🇲🇲 Myanmar. All served from region <code>ap-southeast-1</code>.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q4">Which path should I pick?</summary>
                <div class="faq-body" data-i18n-html="faq_a4">
                    If you've never touched AWS Location Service before → <b>Path A</b>. If you already have <code>v1.public.eyJqdGki...</code> in your notes → <b>Path B</b>. You can always come back and switch paths.
                </div>
            </details>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
        <h2 data-i18n="cta_title">Not sure where to start?</h2>
        <p data-i18n="cta_desc">Most first-timers pick Path A — it takes ~10 minutes and hands you a working key you can test immediately.</p>
        <div class="cta-buttons">
            <a href="/tutorial/aws-key" class="btn-cta white">
                <i class="bi bi-play-circle-fill"></i> <span data-i18n="cta_start">Start with Path A</span>
            </a>
            <a href="/docs/aws-api" class="btn-cta outline">
                <i class="bi bi-book-fill"></i> <span data-i18n="cta_ref">Browse API Reference</span>
            </a>
        </div>
    </section>

    <footer class="footer" data-i18n-html="footer_text">
        GrabMaps Tutorial Hub · Powered by <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location Service</a> · Provider by <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab</a>
    </footer>

    <script>
        /* ================= i18n dictionary ================= */
        const I18N = {
            en: {
                brand: 'GrabMaps Tutorial Hub',
                nav_reference: 'API Reference',
                nav_playground: 'Playground',
                hero_badge: 'AWS Location Service · GrabMaps · Southeast Asia',
                hero_title: 'Start Building With<br><span class="gradient-text">GrabMaps + AWS</span>',
                hero_lead: 'Pick your path — set up a brand new AWS API Key from scratch, or jump straight into the interactive API playground if you already have one.',

                path_a_label: 'Path A — Beginner',
                path_a_title: 'I need to create an AWS API Key',
                path_a_desc: 'Never used AWS Location Service before? Follow the 8-step visual tutorial with real Console screenshots — from signup to a working key you can use immediately.',
                path_a_meta_time: '<strong>~10 min</strong>',
                path_a_meta_steps: '<strong>8 steps</strong>',
                path_a_meta_diff: '<strong>Beginner</strong>',
                path_a_cta: 'Start Tutorial',

                path_b_label: 'Path B — Ready to build',
                path_b_title: 'I already have an API Key',
                path_b_desc: 'Jump straight into the interactive API reference with a live inspector, or hit the playground to test endpoints against your key in real time — no coding required.',
                path_b_meta_1: '<strong>Interactive</strong>',
                path_b_meta_2: '<strong>Live examples</strong>',
                path_b_meta_3: '<strong>No-code</strong>',
                path_b_cta: 'Open API Reference',
                path_b_cta2: 'Playground',

                journey_kicker: 'How it fits together',
                journey_title: 'From AWS Console to your app in 4 steps',
                journey_desc: 'GrabMaps is served through AWS Location Service. Understanding the flow makes debugging easier once you\'re up and running.',
                j1_t: 'AWS Console',
                j1_d: 'Sign in, pick <code>ap-southeast-1</code>',
                j2_t: 'Create API Key',
                j2_d: 'Restrict actions & referrers',
                j3_t: 'Your App',
                j3_d: 'Web / iOS / Android',
                j4_t: 'GrabMaps Data',
                j4_d: 'POI · Routes · Maps · Geocoding',

                features_kicker: 'Capabilities',
                features_title: 'What you can build with GrabMaps',
                features_desc: 'Four core service families — all bundled with your API Key when you follow this tutorial.',
                f1_t: 'Maps',
                f1_d: 'High-resolution vector & raster tiles for the whole SEA region, styled to match your brand.',
                f2_t: 'Places (Search & Geocode)',
                f2_d: 'Autocomplete POI, reverse geocode coordinates, search by text — down to alley-level precision.',
                f3_t: 'Routes',
                f3_d: 'Turn-by-turn directions, alternative routes, real-time traffic — optimised for SEA road networks.',
                f4_t: 'Route Matrix',
                f4_d: 'Calculate distance & time for hundreds of origin-destination pairs at once — perfect for dispatch.',

                trust_kicker: 'Coverage',
                trust_title: 'Built on real Grab operational data',
                trust_desc: 'GrabMaps is refreshed continuously from millions of daily driver-partner trips across Southeast Asia.',

                faq_kicker: 'Quick answers',
                faq_title: 'Common questions',
                faq_q1: 'Do I need to install anything?',
                faq_a1: 'No. Both paths are browser-based. Path A only requires an AWS account (free tier is enough). Path B just needs an API Key you can paste into the inspector — everything runs live in your browser.',
                faq_q2: 'Is there a cost?',
                faq_a2: 'AWS Location Service has a <b>3-month trial free tier</b>: <code>10K Places Suggest</code>, <code>20K Geocode/Search</code>, <code>10K Routes</code>, and <code>500K Map tile loads</code> per month. Enough for POC and testing. After the trial, pay-per-request applies — see <a href="https://aws.amazon.com/location/pricing/" target="_blank" rel="noopener">official pricing</a>.',
                faq_q3: 'Which countries are covered?',
                faq_a3: 'GrabMaps has native detail across 🇸🇬 Singapore, 🇲🇾 Malaysia, 🇹🇭 Thailand, 🇮🇩 Indonesia, 🇻🇳 Vietnam, 🇵🇭 Philippines, 🇰🇭 Cambodia, and 🇲🇲 Myanmar. All served from region <code>ap-southeast-1</code>.',
                faq_q4: 'Which path should I pick?',
                faq_a4: 'If you\'ve never touched AWS Location Service before → <b>Path A</b>. If you already have <code>v1.public.eyJqdGki...</code> in your notes → <b>Path B</b>. You can always come back and switch paths.',

                cta_title: 'Not sure where to start?',
                cta_desc: 'Most first-timers pick Path A — it takes ~10 minutes and hands you a working key you can test immediately.',
                cta_start: 'Start with Path A',
                cta_ref: 'Browse API Reference',
                footer_text: 'GrabMaps Tutorial Hub · Powered by <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location Service</a> · Provider by <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab</a>'
            },
            id: {
                brand: 'GrabMaps Tutorial Hub',
                nav_reference: 'API Reference',
                nav_playground: 'Playground',
                hero_badge: 'AWS Location Service · GrabMaps · Asia Tenggara',
                hero_title: 'Mulai Bangun dengan<br><span class="gradient-text">GrabMaps + AWS</span>',
                hero_lead: 'Pilih jalurmu — setup AWS API Key baru dari nol, atau langsung masuk ke playground interaktif kalau kamu sudah punya.',

                path_a_label: 'Jalur A — Pemula',
                path_a_title: 'Saya perlu buat AWS API Key',
                path_a_desc: 'Belum pernah pakai AWS Location Service? Ikuti tutorial visual 8-langkah dengan screenshot Console asli — dari signup sampai punya key aktif yang siap langsung dipakai.',
                path_a_meta_time: '<strong>~10 mnt</strong>',
                path_a_meta_steps: '<strong>8 langkah</strong>',
                path_a_meta_diff: '<strong>Pemula</strong>',
                path_a_cta: 'Mulai Tutorial',

                path_b_label: 'Jalur B — Sudah siap bangun',
                path_b_title: 'Saya sudah punya API Key',
                path_b_desc: 'Langsung masuk API reference interaktif dengan inspector live, atau ke playground untuk test endpoint pakai key kamu secara real-time — tanpa coding.',
                path_b_meta_1: '<strong>Interaktif</strong>',
                path_b_meta_2: '<strong>Contoh live</strong>',
                path_b_meta_3: '<strong>Tanpa coding</strong>',
                path_b_cta: 'Buka API Reference',
                path_b_cta2: 'Playground',

                journey_kicker: 'Bagaimana semua terhubung',
                journey_title: 'Dari AWS Console ke aplikasi kamu dalam 4 langkah',
                journey_desc: 'GrabMaps di-serve lewat AWS Location Service. Paham flow ini akan bantu banget saat debugging nanti.',
                j1_t: 'AWS Console',
                j1_d: 'Login, pilih <code>ap-southeast-1</code>',
                j2_t: 'Buat API Key',
                j2_d: 'Batasi actions & referrer',
                j3_t: 'Aplikasi kamu',
                j3_d: 'Web / iOS / Android',
                j4_t: 'Data GrabMaps',
                j4_d: 'POI · Rute · Peta · Geocoding',

                features_kicker: 'Kapabilitas',
                features_title: 'Apa yang bisa kamu bangun',
                features_desc: 'Empat layanan utama — semua ter-bundle dengan API Key yang kamu buat lewat tutorial ini.',
                f1_t: 'Maps',
                f1_d: 'Tile vector & raster resolusi tinggi untuk seluruh SEA, bisa di-style sesuai brand kamu.',
                f2_t: 'Places (Search & Geocode)',
                f2_d: 'Autocomplete POI, reverse geocode koordinat, text search — presisi sampai level gang.',
                f3_t: 'Rute',
                f3_d: 'Turn-by-turn, rute alternatif, traffic real-time — dioptimasi untuk jalanan SEA.',
                f4_t: 'Route Matrix',
                f4_d: 'Hitung jarak & waktu untuk ratusan pasangan origin-destination sekaligus — cocok untuk dispatch.',

                trust_kicker: 'Coverage',
                trust_title: 'Dibangun dari data operasional Grab yang real',
                trust_desc: 'GrabMaps di-refresh terus-menerus dari jutaan trip driver-partner setiap hari di seluruh Asia Tenggara.',

                faq_kicker: 'Jawaban singkat',
                faq_title: 'Pertanyaan umum',
                faq_q1: 'Apa perlu install sesuatu?',
                faq_a1: 'Tidak. Kedua jalur berbasis browser. Jalur A cuma butuh akun AWS (free tier cukup). Jalur B cuma perlu API Key yang bisa kamu paste ke inspector — semua jalan live di browser.',
                faq_q2: 'Ada biayanya?',
                faq_a2: 'AWS Location Service punya <b>free tier trial 3 bulan</b>: <code>10K Places Suggest</code>, <code>20K Geocode/Search</code>, <code>10K Routes</code>, dan <code>500K Map tile loads</code> per bulan. Cukup untuk POC dan testing. Setelah trial, pay-per-request berlaku — lihat <a href="https://aws.amazon.com/location/pricing/" target="_blank" rel="noopener">pricing resmi</a>.',
                faq_q3: 'Negara mana saja yang di-cover?',
                faq_a3: 'GrabMaps punya detail native di 🇸🇬 Singapura, 🇲🇾 Malaysia, 🇹🇭 Thailand, 🇮🇩 Indonesia, 🇻🇳 Vietnam, 🇵🇭 Filipina, 🇰🇭 Kamboja, dan 🇲🇲 Myanmar. Semua di-serve dari region <code>ap-southeast-1</code>.',
                faq_q4: 'Saya harus pilih jalur mana?',
                faq_a4: 'Kalau belum pernah pakai AWS Location Service → <b>Jalur A</b>. Kalau di catatan kamu sudah ada <code>v1.public.eyJqdGki...</code> → <b>Jalur B</b>. Bisa balik & switch kapan aja.',

                cta_title: 'Bingung mulai dari mana?',
                cta_desc: 'Kebanyakan first-timer pilih Jalur A — ~10 menit dan langsung dapat key aktif yang bisa dites.',
                cta_start: 'Mulai dari Jalur A',
                cta_ref: 'Buka API Reference',
                footer_text: 'GrabMaps Tutorial Hub · Powered by <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location Service</a> · Provider by <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab</a>'
            }
        };

        function applyLang(lang) {
            const dict = I18N[lang] || I18N.en;
            document.documentElement.setAttribute('lang', lang);
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (dict[key] != null) el.textContent = dict[key];
            });
            document.querySelectorAll('[data-i18n-html]').forEach(el => {
                const key = el.getAttribute('data-i18n-html');
                if (dict[key] != null) el.innerHTML = dict[key];
            });
            document.querySelectorAll('.lang-toggle button').forEach(b => {
                b.classList.toggle('active', b.dataset.lang === lang);
            });
            try { localStorage.setItem('tutorial_lang', lang); } catch(_) {}
        }

        (function initLang() {
            let saved = 'en';
            try { saved = localStorage.getItem('tutorial_lang') || 'en'; } catch(_) {}
            applyLang(saved);
            document.querySelectorAll('.lang-toggle button').forEach(btn => {
                btn.addEventListener('click', () => applyLang(btn.dataset.lang));
            });
        })();

        // Reading progress
        const bar = document.getElementById('readProgress');
        const updateBar = () => {
            const doc = document.documentElement;
            const scrollTop = window.scrollY || doc.scrollTop;
            const scrollHeight = doc.scrollHeight - doc.clientHeight;
            const pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
            bar.style.width = pct + '%';
        };
        window.addEventListener('scroll', updateBar, { passive: true });
        updateBar();

        // Auto-tag common reveal targets
        document.querySelectorAll('.journey-step, .feature-card, .country-pill, .faq-item').forEach((el, i) => {
            if (!el.classList.contains('reveal')) el.classList.add('reveal');
            el.style.transitionDelay = Math.min(i * 40, 240) + 'ms';
        });

        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

        // Path card hover — subtle parallax on icons
        document.querySelectorAll('.path-card').forEach(card => {
            const icon = card.querySelector('.pc-icon');
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width - 0.5) * 10;
                const y = ((e.clientY - rect.top) / rect.height - 0.5) * 10;
                icon.style.transform = `scale(1.08) rotate(-3deg) translate(${x}px, ${y}px)`;
            });
            card.addEventListener('mouseleave', () => {
                icon.style.transform = '';
            });
        });
    </script>
</body>

</html>
