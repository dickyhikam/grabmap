<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup AWS Location Service v2 with GrabMaps — Complete Tutorial</title>

    <!-- SEO & Social Sharing (Open Graph + Twitter Cards) -->
    <meta name="description" content="Step-by-step guide from zero to an active AWS Location Service API Key with GrabMaps provider. 8 steps, ~10 minutes.">
    <meta name="keywords" content="AWS Location Service, GrabMaps, API Key, tutorial, v2, ap-southeast-1, Indonesia, Southeast Asia">
    <meta property="og:title" content="Setup AWS Location Service v2 with GrabMaps">
    <meta property="og:description" content="Complete 8-step tutorial — from zero to an active API Key for GrabMaps across all of Southeast Asia. ~10 minutes.">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="en_US">
    <meta property="og:locale:alternate" content="id_ID">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Setup AWS Location Service v2 with GrabMaps">
    <meta name="twitter:description" content="Complete 8-step tutorial for setting up AWS Location Service + GrabMaps.">

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
            --amber-light: #fef3c7;
            --amber-dark: #92400e;
            --blue: #2563eb;
            --blue-light: #eff6ff;
            --purple: #7c3aed;
            --red: #dc2626;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-600: #6b7280;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --bg: #fafbfc;
            --card: #ffffff;
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

        /* =================== READING PROGRESS BAR =================== */
        .read-progress {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
            z-index: 1000;
            pointer-events: none;
        }
        .read-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--grab-green), #10d966, var(--amber));
            transition: width 0.1s linear;
            box-shadow: 0 0 10px rgba(0, 177, 79, 0.5);
        }

        /* =================== TOP NAV (glass) =================== */
        .topnav {
            position: fixed;
            top: 3px;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            z-index: 999;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topnav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
            color: var(--gray-800);
            text-decoration: none;
            font-size: 0.95rem;
        }
        .topnav-brand .logo-dot {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.9rem;
            box-shadow: 0 2px 6px rgba(0, 177, 79, 0.35);
        }
        .topnav-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }
        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
            border: 1px solid var(--gray-200);
            color: var(--gray-800);
            background: #fff;
        }
        .btn-nav:hover { background: var(--gray-50); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
        .btn-nav.primary { background: var(--grab-green); color: #fff; border-color: var(--grab-green); }
        .btn-nav.primary:hover { background: var(--grab-green-dark); }

        /* Language toggle (matches aws-api glass style) */
        .lang-toggle {
            display: flex;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
            padding: 2px;
            gap: 2px;
        }
        .lang-toggle button {
            background: transparent;
            border: 0;
            color: var(--gray-600);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 10px;
            cursor: pointer;
            letter-spacing: 0.5px;
            border-radius: 6px;
            transition: all 0.12s;
        }
        .lang-toggle button:hover { background: rgba(0,0,0,0.04); color: var(--gray-800); }
        .lang-toggle button.active {
            background: #fff;
            color: var(--grab-green);
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        @media (max-width: 640px) {
            .topnav { padding: 10px 14px; gap: 8px; }
            .btn-nav span { display: none; }
            .btn-nav i { margin: 0; }
        }

        /* =================== HERO =================== */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 24px 60px;
            overflow: hidden;
            background: radial-gradient(ellipse at top, #f0fdf4 0%, #fafbfc 60%);
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,177,79,0.10), transparent 70%);
            filter: blur(60px);
            animation: floatBlob 22s ease-in-out infinite;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -25%;
            left: -15%;
            width: 900px;
            height: 900px;
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
            max-width: 900px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0,177,79,0.15);
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--grab-green-dark);
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            animation: fadeInUp 0.7s ease both;
        }
        .hero-badge .badge-dot {
            width: 8px;
            height: 8px;
            background: var(--grab-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        /* Version pill (v2 indicator) */
        .version-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            color: #fff;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.35);
            margin-left: 8px;
            vertical-align: middle;
            text-transform: uppercase;
        }
        .version-pill::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #a6e3a1;
            border-radius: 50%;
            box-shadow: 0 0 8px #a6e3a1;
        }
        .version-pill.legacy {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            box-shadow: 0 4px 12px rgba(107,114,128,0.35);
        }
        .version-pill.legacy::before {
            background: #d1d5db;
            box-shadow: 0 0 8px #d1d5db;
        }

        /* Version comparison box */
        .version-compare {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin: 20px 0;
        }
        @media (max-width: 640px) { .version-compare { grid-template-columns: 1fr; } }
        .version-card {
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 16px 18px;
            background: #fff;
            position: relative;
        }
        .version-card.this {
            border-color: var(--grab-green);
            background: linear-gradient(135deg, #fff, #f0fdf4);
            box-shadow: 0 6px 16px rgba(0,177,79,0.12);
        }
        .version-card .vc-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .version-card .vc-title {
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--gray-900);
        }
        .version-card.this .vc-title { color: var(--grab-green-dark); }
        .version-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.82rem;
        }
        .version-card ul li {
            padding: 4px 0;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .version-card ul li::before {
            content: '•';
            color: var(--gray-400);
            flex-shrink: 0;
        }
        .version-card.this ul li::before { color: var(--grab-green); }

        /* Whats-next grid */
        .next-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 14px;
            margin: 24px 0;
        }
        .next-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 18px;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .next-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--grab-green);
        }
        .next-card .nc-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--grab-green-light), #bbf7d0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .next-card h4 {
            margin: 0 0 4px;
            font-weight: 800;
            font-size: 0.92rem;
            color: var(--gray-900);
        }
        .next-card p {
            margin: 0;
            font-size: 0.78rem;
            color: var(--gray-600);
            line-height: 1.5;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0,177,79,0.5); }
            50% { box-shadow: 0 0 0 8px rgba(0,177,79,0); }
        }

        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            font-weight: 900;
            line-height: 1.1;
            margin: 0 0 20px;
            letter-spacing: -0.02em;
            animation: fadeInUp 0.8s ease 0.1s both;
            color: var(--gray-900);
        }
        .hero h1 .gradient-text {
            background: linear-gradient(135deg, var(--grab-green) 0%, #10d966 40%, var(--amber) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        .hero p.lead {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--gray-600);
            max-width: 640px;
            margin: 0 auto 32px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero-stats {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease 0.3s both;
        }
        .hero-stat {
            background: var(--card);
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            padding: 16px 24px;
            min-width: 130px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s;
        }
        .hero-stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .hero-stat .num {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--grab-green), var(--amber));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        .hero-stat .label {
            font-size: 0.75rem;
            color: var(--gray-600);
            font-weight: 500;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-cta.primary {
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            color: #fff;
            box-shadow: 0 8px 20px rgba(0,177,79,0.3);
        }
        .btn-cta.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,177,79,0.4);
        }
        .btn-cta.secondary {
            background: #fff;
            color: var(--gray-800);
            border: 1px solid var(--gray-200);
        }
        .btn-cta.secondary:hover { background: var(--gray-50); transform: translateY(-2px); }

        .hero-scroll {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--gray-400);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            text-align: center;
            animation: bounce 2s infinite;
        }
        .hero-scroll::after {
            content: '↓';
            display: block;
            font-size: 1.2rem;
            margin-top: 4px;
        }
        @keyframes bounce {
            0%, 100% { transform: translate(-50%, 0); }
            50% { transform: translate(-50%, 8px); }
        }

        /* =================== LAYOUT =================== */
        .container-tutorial {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 24px;
        }

        /* =================== SIDEBAR =================== */
        .sidebar {
            position: sticky;
            top: 80px;
            align-self: start;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            padding: 4px;
        }
        .sidebar-title {
            font-size: 0.68rem;
            font-weight: 800;
            color: var(--gray-400);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 16px 4px;
        }
        .step-nav-list { list-style: none; padding: 0; margin: 0; position: relative; }
        .step-nav-list::before {
            content: '';
            position: absolute;
            left: 16px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }
        .step-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            border-radius: 8px;
            position: relative;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        .step-nav-item .step-num {
            width: 32px;
            height: 32px;
            background: #fff;
            border: 2px solid var(--gray-200);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.78rem;
            color: var(--gray-600);
            flex-shrink: 0;
            z-index: 1;
            transition: all 0.2s;
        }
        .step-nav-item:hover { color: var(--gray-800); background: var(--gray-50); }
        .step-nav-item:hover .step-num { border-color: var(--grab-green); color: var(--grab-green); }
        .step-nav-item.active {
            color: var(--grab-green-dark);
            background: var(--grab-green-light);
            font-weight: 700;
        }
        .step-nav-item.active .step-num {
            background: var(--grab-green);
            border-color: var(--grab-green);
            color: #fff;
            box-shadow: 0 0 0 4px rgba(0,177,79,0.15);
        }
        .step-nav-item.done .step-num {
            background: var(--grab-green-light);
            border-color: var(--grab-green);
            color: var(--grab-green);
        }
        .step-nav-item.done .step-num::before { content: '✓'; }

        /* =================== MAIN CONTENT =================== */
        .content { min-width: 0; }

        .step-section {
            margin-bottom: 80px;
            padding-top: 20px;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .step-section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .step-header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }
        .step-badge-big {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--grab-green), var(--grab-green-dark));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 900;
            font-size: 1.5rem;
            box-shadow: 0 10px 20px rgba(0,177,79,0.25);
            flex-shrink: 0;
        }
        .step-badge-big.amber {
            background: linear-gradient(135deg, var(--amber), var(--amber-dark));
            box-shadow: 0 10px 20px rgba(245,158,11,0.25);
        }
        .step-badge-big.blue {
            background: linear-gradient(135deg, var(--blue), #1d4ed8);
            box-shadow: 0 10px 20px rgba(37,99,235,0.25);
        }
        .step-badge-big.purple {
            background: linear-gradient(135deg, var(--purple), #6d28d9);
            box-shadow: 0 10px 20px rgba(124,58,237,0.25);
        }
        .step-header h2 {
            margin: 0 0 6px;
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 800;
            line-height: 1.2;
            color: var(--gray-900);
            letter-spacing: -0.01em;
        }
        .step-header .step-meta {
            display: flex;
            gap: 12px;
            font-size: 0.78rem;
            color: var(--gray-600);
        }
        .step-header .step-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .step-body {
            padding-left: 84px;
            font-size: 0.95rem;
            color: var(--gray-800);
        }
        @media (max-width: 640px) {
            .step-body { padding-left: 0; }
            .step-header { flex-direction: column; gap: 12px; }
        }
        .step-body p { margin: 0 0 14px; }
        .step-body strong { color: var(--gray-900); }
        .step-body a { color: var(--grab-green-dark); font-weight: 600; }

        /* Info boxes */
        .info-box {
            background: linear-gradient(135deg, #ffffff, #f9fafb);
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            padding: 18px 22px;
            margin: 20px 0;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .info-box.tip { border-left: 4px solid var(--blue); background: linear-gradient(135deg, #fff, #eff6ff); }
        .info-box.warn { border-left: 4px solid var(--amber); background: linear-gradient(135deg, #fff, #fef3c7); }
        .info-box.success { border-left: 4px solid var(--grab-green); background: linear-gradient(135deg, #fff, #dcfce7); }
        .info-box.danger { border-left: 4px solid var(--red); background: linear-gradient(135deg, #fff, #fee2e2); }
        .info-box i {
            font-size: 1.4rem;
            margin-top: 2px;
        }
        .info-box.tip i { color: var(--blue); }
        .info-box.warn i { color: var(--amber-dark); }
        .info-box.success i { color: var(--grab-green-dark); }
        .info-box.danger i { color: var(--red); }
        .info-box strong { display: block; margin-bottom: 4px; color: var(--gray-900); }
        .info-box p { margin: 0; font-size: 0.88rem; color: var(--gray-800); }

        /* Action steps within a step */
        .action-list { padding: 0; list-style: none; margin: 20px 0; }
        .action-list li {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px dashed var(--gray-200);
        }
        .action-list li:last-child { border-bottom: none; }
        .action-list .a-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--grab-green-light);
            color: var(--grab-green-dark);
            font-weight: 800;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .action-list .a-body { flex: 1; min-width: 0; }
        .action-list code {
            background: var(--gray-100);
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.82rem;
            color: var(--grab-green-dark);
            font-weight: 600;
            word-break: break-word;
        }

        /* Screen mockup */
        .screen-mockup {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s;
        }
        .screen-mockup:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        .screen-mockup-header {
            background: #f0f0f0;
            padding: 10px 14px;
            display: flex;
            gap: 6px;
            border-bottom: 1px solid var(--gray-200);
            align-items: center;
        }
        .screen-mockup-header .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .screen-mockup-header .dot.r { background: #ff5f56; }
        .screen-mockup-header .dot.y { background: #ffbd2e; }
        .screen-mockup-header .dot.g { background: #27c93f; }
        .screen-mockup-header .url {
            margin-left: 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            color: var(--gray-600);
            background: #fff;
            padding: 4px 12px;
            border-radius: 6px;
            flex: 1;
            max-width: 380px;
        }
        .screen-mockup-body { padding: 20px; }

        /* Code blocks with copy */
        .code-block {
            background: #1e1e2e;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 16px 0;
            position: relative;
            overflow-x: auto;
            font-size: 0.82rem;
            color: #cdd6f4;
            line-height: 1.5;
            box-shadow: var(--shadow-md);
        }
        .code-block pre { margin: 0; font-family: 'JetBrains Mono', monospace; }
        .code-block .btn-copy {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: #cdd6f4;
            padding: 4px 10px;
            font-size: 0.7rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.15s;
        }
        .code-block .btn-copy:hover { background: rgba(255,255,255,0.2); }
        .code-block .cmt { color: #7f849c; }
        .code-block .kw { color: #f38ba8; }
        .code-block .str { color: #a6e3a1; }
        .code-block .num { color: #fab387; }

        /* AWS console mockup — resource/action grid like real UI */
        .aws-mockup {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            padding: 20px;
            margin: 20px 0;
        }
        .aws-mockup h4 {
            font-size: 0.88rem;
            margin: 0 0 12px;
            color: var(--gray-900);
            font-weight: 700;
        }
        .aws-mockup .arn-line {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            color: var(--gray-800);
            background: var(--gray-100);
            padding: 6px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            word-break: break-all;
        }
        .arn-line .arn-hl { background: rgba(0,177,79,0.15); color: var(--grab-green-dark); font-weight: 800; padding: 0 3px; border-radius: 3px; }
        .aws-mockup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        .aws-service {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 14px;
        }
        .aws-service-title {
            font-weight: 800;
            font-size: 0.85rem;
            color: var(--gray-900);
            margin: 0 0 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .aws-service ul { list-style: none; padding: 0; margin: 0; }
        .aws-service li {
            padding: 5px 0;
            font-size: 0.78rem;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .aws-service li i { color: var(--grab-green); font-size: 0.9rem; }

        /* =================== AWS CONSOLE MOCKUPS (rich UI) =================== */
        .aws-console {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
            font-size: 0.82rem;
            transition: transform 0.3s;
        }
        .aws-console:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
        /* AWS-style dark topbar */
        .aws-topbar {
            background: #232f3e;
            color: #fff;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.78rem;
        }
        .aws-topbar .aws-logo {
            background: #ff9900;
            color: #232f3e;
            font-weight: 900;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
        }
        .aws-topbar .aws-search {
            background: #fff;
            color: #333;
            padding: 5px 12px;
            border-radius: 4px;
            flex: 1;
            max-width: 380px;
            font-size: 0.75rem;
            border: 1px solid transparent;
        }
        .aws-topbar .aws-search.hl {
            border-color: #ff9900;
            box-shadow: 0 0 0 2px rgba(255,153,0,0.3);
        }
        .aws-topbar .aws-region {
            background: #37475a;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.72rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            margin-left: auto;
        }
        .aws-topbar .aws-region.hl {
            background: linear-gradient(135deg, #ff9900, #ec7211);
            color: #232f3e;
            font-weight: 700;
            box-shadow: 0 0 0 2px rgba(255,153,0,0.35);
        }
        .aws-topbar .aws-region::after { content: '▾'; opacity: 0.7; }

        /* AWS console body */
        .aws-body {
            padding: 20px;
            background: #f2f3f3;
            min-height: 180px;
        }
        .aws-body-white { background: #fff; padding: 20px; }

        /* Region dropdown mockup */
        .region-dropdown {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            max-width: 340px;
            margin-left: auto;
            overflow: hidden;
        }
        .region-dropdown .rd-head {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: 0.72rem;
            color: #6b7280;
            text-transform: uppercase;
            background: #f9fafb;
        }
        .region-item {
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.78rem;
            cursor: pointer;
            transition: background 0.1s;
        }
        .region-item:hover { background: #f3f4f6; }
        .region-item.selected {
            background: #ecfeff;
            color: #0e7490;
            font-weight: 700;
        }
        .region-item.grab {
            background: linear-gradient(90deg, rgba(0,177,79,0.12), rgba(0,177,79,0.02));
            border-left: 3px solid var(--grab-green);
            color: var(--grab-green-dark);
            font-weight: 700;
        }
        .region-item .r-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            color: #6b7280;
        }
        .region-item.grab .r-code {
            color: var(--grab-green-dark);
            background: rgba(0,177,79,0.18);
            padding: 1px 6px;
            border-radius: 4px;
        }
        .region-item .r-tick { color: var(--grab-green); font-weight: 900; }

        /* AWS form field mockup */
        .aws-form { max-width: 560px; }
        .aws-form-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
            margin: 0 0 4px;
        }
        .aws-form-desc {
            font-size: 0.75rem;
            color: #6b7280;
            margin: 0 0 18px;
        }
        .aws-field { margin-bottom: 14px; }
        .aws-field label {
            display: block;
            font-weight: 700;
            font-size: 0.78rem;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .aws-field label .opt {
            font-weight: 500;
            color: #9ca3af;
            font-size: 0.72rem;
            margin-left: 4px;
        }
        .aws-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.8rem;
            background: #fff;
            color: #1f2937;
            font-family: inherit;
        }
        .aws-input.filled { color: #1f2937; }
        .aws-input.filled.hl { border-color: #ff9900; box-shadow: 0 0 0 2px rgba(255,153,0,0.2); }
        .aws-input.mono { font-family: 'JetBrains Mono', monospace; font-size: 0.72rem; }
        .aws-hint { font-size: 0.7rem; color: #6b7280; margin-top: 3px; }

        /* AWS button */
        .aws-btn {
            background: linear-gradient(180deg, #f59e0b, #d97706);
            color: #fff;
            border: 1px solid #b45309;
            padding: 6px 14px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .aws-btn.primary {
            background: linear-gradient(180deg, #0972d3, #033160);
            border-color: #002b58;
        }
        .aws-btn.secondary {
            background: #fff;
            color: #0972d3;
            border-color: #0972d3;
        }

        /* Success key display */
        .aws-success-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%);
            border-left: 4px solid var(--grab-green);
        }
        .aws-success-panel .sp-title {
            font-weight: 800;
            font-size: 0.92rem;
            color: var(--grab-green-dark);
            margin: 0 0 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .aws-success-panel .sp-desc { font-size: 0.75rem; color: #6b7280; margin: 0 0 14px; }
        .aws-key-value {
            background: #1e1e2e;
            color: #a6e3a1;
            padding: 12px 14px;
            border-radius: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            display: flex;
            align-items: center;
            gap: 8px;
            word-break: break-all;
        }
        .aws-key-value .lbl {
            background: #ff9900;
            color: #232f3e;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.62rem;
            font-weight: 800;
            flex-shrink: 0;
            letter-spacing: 0.5px;
        }
        .aws-key-value .btn-eye {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.65rem;
            cursor: pointer;
            flex-shrink: 0;
            margin-left: auto;
        }

        /* Sidebar item mockup (AWS console left nav) */
        .aws-sidebar {
            display: flex;
            gap: 16px;
        }
        .aws-sidebar-nav {
            background: #fff;
            border-right: 1px solid #d1d5db;
            padding: 12px 4px 12px 12px;
            width: 200px;
            font-size: 0.78rem;
        }
        .aws-sidebar-nav .snav-head {
            font-weight: 800;
            font-size: 0.72rem;
            color: #6b7280;
            margin: 8px 0 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .aws-sidebar-nav .snav-item {
            padding: 5px 10px;
            border-radius: 4px;
            color: #1f2937;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .aws-sidebar-nav .snav-item.active {
            background: #ecfeff;
            color: #0972d3;
            font-weight: 700;
            border-right: 2px solid #0972d3;
        }
        .aws-sidebar-content { flex: 1; padding: 8px; }

        /* Checkbox row (for Actions) */
        .aws-check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
            font-size: 0.78rem;
        }
        .aws-checkbox {
            width: 14px;
            height: 14px;
            border: 1.5px solid #6b7280;
            border-radius: 3px;
            background: #fff;
            flex-shrink: 0;
            position: relative;
        }
        .aws-checkbox.checked {
            background: #0972d3;
            border-color: #0972d3;
        }
        .aws-checkbox.checked::after {
            content: '✓';
            color: #fff;
            font-size: 0.7rem;
            position: absolute;
            top: -2px;
            left: 2px;
            font-weight: 900;
        }

        /* Mockup caption */
        .mockup-caption {
            text-align: center;
            font-size: 0.75rem;
            color: var(--gray-600);
            font-style: italic;
            margin-top: -8px;
            margin-bottom: 20px;
            padding: 0 8px;
        }
        .mockup-caption .icon {
            font-style: normal;
            margin-right: 4px;
        }

        /* Real screenshot wrapper — for AWS docs images */
        .real-screenshot {
            position: relative;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .real-screenshot:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        .real-screenshot img {
            display: block;
            width: 100%;
            height: auto;
            transition: opacity 0.3s;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        }
        .real-screenshot img:not([data-loaded]) {
            opacity: 0;
        }
        .real-screenshot img[data-loaded] {
            opacity: 1;
        }
        /* Loading skeleton behind image */
        .real-screenshot::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #f0f0f0 0%, #f9f9f9 50%, #f0f0f0 100%);
            background-size: 200% 100%;
            animation: rsShimmer 1.4s ease-in-out infinite;
            z-index: 0;
        }
        .real-screenshot.loaded::before { display: none; }
        @keyframes rsShimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .real-screenshot .rs-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0,0,0,0.7);
            color: #fff;
            font-size: 0.65rem;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
            z-index: 2;
        }
        .real-screenshot .rs-fallback {
            display: none;
            padding: 60px 30px;
            text-align: center;
            color: var(--gray-600);
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        }
        .real-screenshot.failed .rs-fallback { display: block; }
        .real-screenshot.failed img { display: none; }
        .real-screenshot.failed::before { display: none; }

        @media (max-width: 640px) {
            .aws-sidebar { flex-direction: column; }
            .aws-sidebar-nav { width: 100%; }
        }

        /* =================== FAQ =================== */
        .faq-section {
            background: linear-gradient(135deg, #f0fdf4, #fafbfc);
            padding: 80px 24px;
            border-top: 1px solid var(--gray-200);
        }
        .faq-container { max-width: 800px; margin: 0 auto; }
        .faq-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 40px;
            color: var(--gray-900);
        }
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
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after {
            content: '+';
            margin-left: auto;
            width: 26px;
            height: 26px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            transition: transform 0.2s;
        }
        .faq-item[open] summary::after { content: '−'; background: var(--grab-green-light); color: var(--grab-green-dark); }
        .faq-item .faq-body {
            padding: 0 22px 20px;
            color: var(--gray-800);
            font-size: 0.88rem;
            line-height: 1.7;
        }

        /* =================== FINAL CTA =================== */
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
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
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
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 560px;
            margin: 0 auto 32px;
            position: relative;
        }
        .final-cta .cta-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
        }
        .final-cta .btn-cta.primary {
            background: #fff;
            color: var(--grab-green-dark);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }
        .final-cta .btn-cta.primary:hover { background: #f9fafb; }
        .final-cta .btn-cta.secondary {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.4);
            color: #fff;
        }
        .final-cta .btn-cta.secondary:hover { background: rgba(255,255,255,0.15); }

        /* =================== FOOTER =================== */
        .footer {
            padding: 40px 24px;
            text-align: center;
            font-size: 0.82rem;
            color: var(--gray-600);
            border-top: 1px solid var(--gray-200);
        }
        .footer a { color: var(--grab-green-dark); text-decoration: none; font-weight: 600; }

        /* =================== HIGHLIGHT ANIMATIONS =================== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ARN highlight */
        .arn-highlight {
            display: inline-block;
            background: linear-gradient(120deg, transparent 0%, transparent 45%, rgba(0,177,79,0.25) 45%, rgba(0,177,79,0.25) 100%);
            padding: 1px 4px;
            font-weight: 800;
            color: var(--grab-green-dark);
        }

        /* Provider comparison table */
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.85rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .compare-table th {
            background: var(--gray-900);
            color: #fff;
            padding: 12px 16px;
            text-align: left;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .compare-table td {
            padding: 12px 16px;
            background: #fff;
            border-bottom: 1px solid var(--gray-100);
        }
        .compare-table tr:last-child td { border-bottom: none; }
        .compare-table .highlight-row { background: var(--grab-green-light) !important; }
        .compare-table .highlight-row td { background: var(--grab-green-light); font-weight: 600; }

        /* Responsive */
        @media (max-width: 900px) {
            .container-tutorial { grid-template-columns: 1fr; }
            .sidebar { display: none; }
        }
        @media (max-width: 640px) {
            .hero-stats { gap: 12px; }
            .hero-stat { min-width: 100px; padding: 12px 16px; }
            .step-body { padding-left: 0; }
        }
    </style>
</head>

<body>
    <!-- Reading progress bar -->
    <div class="read-progress"><div class="read-progress-fill" id="readProgress"></div></div>

    <!-- Top nav (glass) -->
    <nav class="topnav">
        <a href="/tutorial" class="topnav-brand" title="Back to Tutorial Hub">
            <span class="logo-dot">🗺️</span>
            <span data-i18n="brand">GrabMaps Tutorial</span>
        </a>
        <div class="topnav-actions">
            <a href="/tutorial" class="btn-nav"><i class="bi bi-arrow-left"></i> <span data-i18n="nav_hub">Hub</span></a>
            <a href="/docs/aws-api" class="btn-nav"><i class="bi bi-book"></i> <span data-i18n="nav_reference">API Reference</span></a>
            <a href="https://console.aws.amazon.com/location/" target="_blank" rel="noopener" class="btn-nav primary">
                <i class="bi bi-arrow-up-right"></i> <span data-i18n="nav_console">AWS Console</span>
            </a>
            <div class="lang-toggle" role="group" aria-label="Language">
                <button type="button" data-lang="en" class="active">EN</button>
                <button type="button" data-lang="id">ID</button>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="badge-dot"></span> <span data-i18n="hero_badge">AWS Location Service v2 · GrabMaps · ap-southeast-1</span>
                <span class="version-pill">v2</span>
            </div>

            <h1 data-i18n-html="hero_title">
                Setup AWS Location Service<br>
                dengan <span class="gradient-text">GrabMaps Provider</span>
            </h1>

            <p class="lead" data-i18n-html="hero_lead">
                A step-by-step guide from <b>zero</b> to an active API Key ready for mapping across Southeast Asia — POI, routing, geocoding — all powered by GrabMaps.
            </p>

            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="num" data-count="8">0</span>
                    <div class="label" data-i18n="stat_steps">Langkah</div>
                </div>
                <div class="hero-stat">
                    <span class="num" data-count="10">0</span>
                    <div class="label" data-i18n="stat_minutes">Menit</div>
                </div>
                <div class="hero-stat">
                    <span class="num" data-count="3">0</span>
                    <div class="label" data-i18n="stat_service">Service</div>
                </div>
                <div class="hero-stat">
                    <span class="num" data-count="0">0</span>
                    <div class="label" data-i18n="stat_coding">Coding</div>
                </div>
            </div>

            <div class="hero-actions">
                <a href="#step-1" class="btn-cta primary">
                    <i class="bi bi-play-circle-fill"></i> <span data-i18n="hero_cta_start">Mulai Tutorial</span>
                </a>
                <a href="#faq" class="btn-cta secondary">
                    <i class="bi bi-question-circle"></i> <span data-i18n="hero_cta_faq">Lihat FAQ</span>
                </a>
            </div>
        </div>
        <div class="hero-scroll" data-i18n="hero_scroll">Scroll</div>
    </section>

    <!-- MAIN CONTENT -->
    <div class="container-tutorial">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-title" data-i18n="sidebar_title">Tutorial Progress</div>
            <ul class="step-nav-list" id="stepNavList">
                <li><a href="#step-1" class="step-nav-item" data-step="1"><span class="step-num">1</span> <span data-i18n="nav_step_1">Prasyarat & Persiapan</span></a></li>
                <li><a href="#step-2" class="step-nav-item" data-step="2"><span class="step-num">2</span> <span data-i18n="nav_step_2">Login & Buka Location Service</span></a></li>
                <li><a href="#step-3" class="step-nav-item" data-step="3"><span class="step-num">3</span> <span data-i18n="nav_step_3">Pilih Region Singapore</span></a></li>
                <li><a href="#step-4" class="step-nav-item" data-step="4"><span class="step-num">4</span> <span data-i18n="nav_step_4">Buat API Key Baru</span></a></li>
                <li><a href="#step-5" class="step-nav-item" data-step="5"><span class="step-num">5</span> <span data-i18n="nav_step_5">Set Restrictions</span></a></li>
                <li><a href="#step-6" class="step-nav-item" data-step="6"><span class="step-num">6</span> <span data-i18n="nav_step_6">Resources & Actions</span></a></li>
                <li><a href="#step-7" class="step-nav-item" data-step="7"><span class="step-num">7</span> <span data-i18n="nav_step_7">Review & Create</span></a></li>
                <li><a href="#step-8" class="step-nav-item" data-step="8"><span class="step-num">8</span> <span data-i18n="nav_step_8">Test API Key</span></a></li>
            </ul>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <!-- STEP 1 -->
            <section class="step-section" id="step-1">
                <div class="step-header">
                    <div class="step-badge-big">01</div>
                    <div>
                        <h2 data-i18n="s1_title">Prasyarat & Persiapan</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s1_time">~2 menit</span></span>
                            <span><i class="bi bi-lightning-charge"></i> <span data-i18n="difficulty_easy">Mudah</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n="s1_intro">Sebelum mulai, pastikan kamu punya beberapa hal berikut:</p>

                    <ol class="action-list">
                        <li>
                            <span class="a-num">1</span>
                            <div class="a-body">
                                <strong data-i18n="s1_p1_t">AWS Account aktif</strong>
                                <p data-i18n-html="s1_p1_d">Kalau belum punya, sign up gratis di <a href="https://aws.amazon.com/free/" target="_blank" rel="noopener">aws.amazon.com/free</a>. Free tier cukup untuk demo & development.</p>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">2</span>
                            <div class="a-body">
                                <strong data-i18n="s1_p2_t">Email verifikasi + Kartu kredit</strong>
                                <p data-i18n="s1_p2_d">AWS wajibkan verifikasi payment method walaupun free tier. Tidak akan di-charge kalau usage di bawah limit gratis.</p>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">3</span>
                            <div class="a-body">
                                <strong data-i18n="s1_p3_t">Browser modern</strong>
                                <p data-i18n="s1_p3_d">Chrome, Firefox, Edge, atau Safari versi terbaru. AWS Console pakai banyak JavaScript.</p>
                            </div>
                        </li>
                    </ol>

                    <div class="info-box tip">
                        <i class="bi bi-lightbulb-fill"></i>
                        <div>
                            <strong data-i18n="s1_cost_t">Free tier estimate (first 3 months)</strong>
                            <p data-i18n-html="s1_cost_d">AWS Location Service free tier (3 months trial): <code>10K Places Suggest</code>, <code>20K Geocode/Search</code>, <code>10K Routes</code>, and <code>500K Map tile loads</code> per month. Great for POC & development. See <a href="https://aws.amazon.com/location/pricing/" target="_blank" rel="noopener">official pricing</a>.</p>
                        </div>
                    </div>

                    <h4 style="margin:28px 0 10px;font-weight:800;" data-i18n="version_h">📌 Kamu mengikuti flow AWS Location Service v2</h4>
                    <p style="font-size:0.88rem;color:var(--gray-600);margin-bottom:14px;" data-i18n="version_intro">Tutorial ini pakai API standalone v2 modern, bukan v0 legacy. Ini bedanya singkat:</p>

                    <div class="version-compare">
                        <div class="version-card this">
                            <div class="vc-head">
                                <div class="vc-title" data-i18n="v2_title">v2 Standalone (yang kita pakai)</div>
                                <span class="version-pill">v2</span>
                            </div>
                            <ul>
                                <li data-i18n="v2_p1">Endpoint langsung: <code>places.geo.*</code>, <code>routes.geo.*</code>, <code>maps.geo.*</code></li>
                                <li data-i18n-html="v2_p2"><b>Tanpa</b> resource setup — pakai <code>provider/default</code></li>
                                <li data-i18n-html="v2_p3">Provider ditentukan <b>otomatis by region</b></li>
                                <li data-i18n="v2_p4">Action: <code>geo-maps:*</code>, <code>geo-places:*</code>, <code>geo-routes:*</code></li>
                                <li data-i18n-html="v2_p5">Direkomendasikan untuk semua project baru ✅</li>
                            </ul>
                        </div>
                        <div class="version-card">
                            <div class="vc-head">
                                <div class="vc-title" data-i18n="v0_title">v0 Legacy (tidak kita pakai)</div>
                                <span class="version-pill legacy">v0</span>
                            </div>
                            <ul>
                                <li data-i18n-html="v0_p1">Endpoint via resource: <code>maps.geo.*/maps/v0/maps/{name}</code></li>
                                <li data-i18n-html="v0_p2"><b>Wajib</b> create Map/PlaceIndex/RouteCalculator dulu</li>
                                <li data-i18n-html="v0_p3">Provider dipilih <b>manual</b> saat create resource</li>
                                <li data-i18n="v0_p4">Action: <code>geo:GetMapTile</code>, <code>geo:SearchPlaceIndex*</code>, dsb</li>
                                <li data-i18n-html="v0_p5">Masih supported, tapi <b>tidak untuk project baru</b> ⚠️</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STEP 2 -->
            <section class="step-section" id="step-2">
                <div class="step-header">
                    <div class="step-badge-big blue">02</div>
                    <div>
                        <h2 data-i18n="s2_title">Login & Buka Location Service</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s2_time">~1 menit</span></span>
                            <span><i class="bi bi-lightning-charge"></i> <span data-i18n="difficulty_easy">Mudah</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n-html="s2_intro">Login ke <a href="https://console.aws.amazon.com/" target="_blank" rel="noopener">AWS Management Console</a>, lalu:</p>

                    <ol class="action-list">
                        <li>
                            <span class="a-num">1</span>
                            <div class="a-body" data-i18n-html="s2_p1">
                                Klik search bar di atas → ketik <code>Location Service</code>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">2</span>
                            <div class="a-body" data-i18n-html="s2_p2">
                                Klik hasil pertama: <strong>Amazon Location Service</strong>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">3</span>
                            <div class="a-body" data-i18n="s2_p3">
                                Kamu akan masuk ke dashboard Location Service
                            </div>
                        </li>
                    </ol>

                    <figure class="real-screenshot" data-rs>
                        <span class="rs-badge">Screenshot</span>
                        <img src="{{ asset('images/tutorial/aws-key/step-2-search-service.png') }}"
                             data-fallback="https://d2908q01vomqb2.cloudfront.net/0a57cb53ba59c46fc4b692527a38a87c78d84028/2023/09/11/image001.jpg"
                             alt="AWS Location Service Console — API Keys menu in the left sidebar"
                             loading="lazy" referrerpolicy="no-referrer">
                        <div class="rs-fallback">
                            <div style="font-size:2rem;margin-bottom:8px;">📍</div>
                            <b>Amazon Location Service</b> · sidebar menu with <b>API keys</b>
                        </div>
                    </figure>
                    <div class="mockup-caption"><span class="icon">📸</span> <span data-i18n="s2_caption">Real screenshot: Amazon Location Service Console — API keys menu in the sidebar</span></div>
                </div>
            </section>

            <!-- STEP 3 -->
            <section class="step-section" id="step-3">
                <div class="step-header">
                    <div class="step-badge-big amber">03</div>
                    <div>
                        <h2 data-i18n="s3_title">Pilih Region: Singapore (ap-southeast-1)</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s3_time">~30 detik</span></span>
                            <span><i class="bi bi-exclamation-triangle-fill" style="color:var(--amber-dark);"></i> <span data-i18n="difficulty_crucial">Krusial</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n-html="s3_intro">Ini <strong>langkah paling penting</strong> — region menentukan provider mana yang aktif. GrabMaps hanya tersedia di <code>ap-southeast-1</code>.</p>

                    <ol class="action-list">
                        <li>
                            <span class="a-num">1</span>
                            <div class="a-body" data-i18n-html="s3_p1">
                                Cari dropdown region di <strong>pojok kanan atas</strong> AWS Console
                            </div>
                        </li>
                        <li>
                            <span class="a-num">2</span>
                            <div class="a-body" data-i18n-html="s3_p2">
                                Klik → pilih <code>Asia Pacific (Singapore) ap-southeast-1</code>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">3</span>
                            <div class="a-body" data-i18n="s3_p3">
                                Halaman akan refresh — pastikan indikator region sudah berubah
                            </div>
                        </li>
                    </ol>

                    <figure class="real-screenshot" data-rs>
                        <span class="rs-badge">Screenshot</span>
                        <img src="{{ asset('images/tutorial/aws-key/step-3-region-selector.png') }}"
                             alt="AWS Console region selector showing Singapore ap-southeast-1 highlighted"
                             loading="lazy" referrerpolicy="no-referrer">
                        <div class="rs-fallback">
                            <div style="font-size:2rem;margin-bottom:8px;">🇸🇬</div>
                            <b>Region selector</b> · Asia Pacific (Singapore) <code>ap-southeast-1</code>
                            <div style="margin-top:12px;font-size:0.75rem;color:#9ca3af;">Screenshot not yet uploaded — drop it at <code>public/images/tutorial/aws-key/step-3-region-selector.png</code></div>
                        </div>
                    </figure>
                    <div class="mockup-caption"><span class="icon">📸</span> <span data-i18n="s3_caption">Real screenshot: AWS Console region dropdown — pick <b>Asia Pacific (Singapore) ap-southeast-1</b></span></div>

                    <div class="info-box warn">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong data-i18n="s3_why_t">Kenapa Singapore, bukan Jakarta?</strong>
                            <p data-i18n-html="s3_why_d">AWS Location Service <b>belum tersedia di region Jakarta / Malaysia / Thailand</b>. Data GrabMaps untuk seluruh SEA — 8 negara sampai jalan lokal — diserve dari region Singapore. Latency ~10-30ms tambahan dari negara SEA manapun, masih acceptable untuk maps app.</p>
                        </div>
                    </div>

                    <h4 style="margin:24px 0 12px;font-weight:800;" data-i18n="s3_table_h">Region → Provider mapping</h4>
                    <table class="compare-table">
                        <thead>
                            <tr>
                                <th data-i18n="s3_col_code">Region Code</th>
                                <th data-i18n="s3_col_loc">Location</th>
                                <th data-i18n="s3_col_prov">Default Provider</th>
                                <th data-i18n="s3_col_best">Best For</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="highlight-row">
                                <td><code>ap-southeast-1</code></td>
                                <td>🇸🇬 Singapore</td>
                                <td><b>GrabMaps</b> ✅</td>
                                <td>SEA (ID, MY, TH, VN, PH)</td>
                            </tr>
                            <tr>
                                <td><code>us-east-1</code></td>
                                <td>🇺🇸 N. Virginia</td>
                                <td>HERE</td>
                                <td>US, Europe</td>
                            </tr>
                            <tr>
                                <td><code>eu-central-1</code></td>
                                <td>🇩🇪 Frankfurt</td>
                                <td>HERE</td>
                                <td>Europe</td>
                            </tr>
                            <tr>
                                <td><code>ap-southeast-3</code></td>
                                <td>🇮🇩 Jakarta</td>
                                <td data-i18n="s3_na">N/A (belum available)</td>
                                <td>—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- STEP 4 -->
            <section class="step-section" id="step-4">
                <div class="step-header">
                    <div class="step-badge-big">04</div>
                    <div>
                        <h2 data-i18n="s4_title">Buat API Key Baru</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s2_time">~1 menit</span></span>
                            <span><i class="bi bi-lightning-charge"></i> <span data-i18n="difficulty_easy">Mudah</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n="s4_intro">Sekarang kita create API Key yang akan digunakan aplikasi untuk memanggil AWS Location Service.</p>

                    <ol class="action-list">
                        <li>
                            <span class="a-num">1</span>
                            <div class="a-body" data-i18n-html="s4_p1">
                                Di sidebar kiri, klik menu <strong>API keys</strong>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">2</span>
                            <div class="a-body" data-i18n-html="s4_p2">
                                Klik tombol biru <strong>Create API key</strong> di kanan atas
                            </div>
                        </li>
                        <li>
                            <span class="a-num">3</span>
                            <div class="a-body" data-i18n-html="s4_p3">
                                Isi <code>Name</code>: misal <code>my-grabmaps-key</code> — nama bebas, cuma untuk identifikasi
                            </div>
                        </li>
                        <li>
                            <span class="a-num">4</span>
                            <div class="a-body" data-i18n-html="s4_p4">
                                <code>Description</code> (optional): "API Key for GrabMaps Southeast Asia demo"
                            </div>
                        </li>
                    </ol>

                    <figure class="real-screenshot" data-rs>
                        <span class="rs-badge">Screenshot</span>
                        <img src="{{ asset('images/tutorial/aws-key/step-4-create-key-form.png') }}"
                             data-fallback="https://d2908q01vomqb2.cloudfront.net/0a57cb53ba59c46fc4b692527a38a87c78d84028/2023/09/11/image003-1024x727.jpg"
                             alt="Create API key — Name and Description form in AWS Console"
                             loading="lazy" referrerpolicy="no-referrer">
                        <div class="rs-fallback">
                            <b>Create API key</b> · form with Name & Description fields
                        </div>
                    </figure>
                    <div class="mockup-caption"><span class="icon">📸</span> <span data-i18n="s4_caption">Real screenshot: Create API key form — just fill Name & Description</span></div>

                    <div class="info-box tip">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong data-i18n="s4_naming_t">Naming convention</strong>
                            <p data-i18n-html="s4_naming_d">Kalau nanti punya multiple keys untuk beda project, kasih prefix jelas. Contoh: <code>demo-jkt</code>, <code>prod-nasional</code>, <code>dev-tim-a</code>.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STEP 5 -->
            <section class="step-section" id="step-5">
                <div class="step-header">
                    <div class="step-badge-big amber">05</div>
                    <div>
                        <h2 data-i18n="s5_title">Set Restrictions (Opsional tapi Recommended)</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s2_time">~1 menit</span></span>
                            <span><i class="bi bi-shield-lock"></i> <span data-i18n="difficulty_security">Security</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n="s5_intro">Batasi API Key supaya kalau bocor ke public, damage terkontrol.</p>

                    <h4 style="margin:20px 0 12px;font-weight:800;" data-i18n="s5_exp_h">Expiration Date</h4>
                    <p data-i18n="s5_exp_d">Set tanggal expired supaya key otomatis inactive setelah tanggal itu. Recommended:</p>
                    <ul>
                        <li data-i18n-html="s5_exp_dev"><strong>Development/Demo</strong>: 3-6 bulan</li>
                        <li data-i18n-html="s5_exp_prod"><strong>Production</strong>: 1 tahun, rotasi rutin</li>
                        <li data-i18n-html="s5_exp_pub"><strong>Public client-side</strong>: 90 hari max + Referer restriction</li>
                    </ul>

                    <h4 style="margin:24px 0 12px;font-weight:800;" data-i18n="s5_ref_h">Referer Restriction</h4>
                    <p data-i18n="s5_ref_d">Kalau API Key dipakai di frontend (browser), tambahkan Referer restriction supaya cuma domain kamu yang bisa pakai.</p>

                    <div class="code-block">
                        <button class="btn-copy" onclick="copyBlock(this)">📋 Copy</button>
                        <pre><span class="cmt" data-i18n="s5_code_cmt1"># Contoh Referer patterns yang valid</span>
https://grabmap.test/*
https://*.myapp.com/*
https://localhost:*/*      <span class="cmt" data-i18n="s5_code_cmt2"># untuk local development</span></pre>
                    </div>

                    <figure class="real-screenshot" data-rs>
                        <span class="rs-badge">Screenshot</span>
                        <img src="{{ asset('images/tutorial/aws-key/step-5-restrictions.png') }}"
                             data-fallback="https://d2908q01vomqb2.cloudfront.net/0a57cb53ba59c46fc4b692527a38a87c78d84028/2023/09/11/image004-1024x989.jpg"
                             alt="Client restrictions and expiration options in AWS Console"
                             loading="lazy" referrerpolicy="no-referrer">
                        <div class="rs-fallback">
                            <b>Restrictions</b> · Referer allowlist & expiration date
                        </div>
                    </figure>
                    <div class="mockup-caption"><span class="icon">📸</span> <span data-i18n="s5_caption">Real screenshot: Client restrictions & expiration options</span></div>

                    <div class="info-box warn">
                        <i class="bi bi-shield-exclamation"></i>
                        <div>
                            <strong data-i18n="s5_tradeoff_t">Trade-off Referer</strong>
                            <p data-i18n-html="s5_tradeoff_d">Kalau di-set restrict ke <code>example.com</code>, API Key tidak akan jalan dari <code>localhost</code>. Untuk development, kasih pattern yang mencakup keduanya atau bikin key terpisah dev vs prod.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STEP 6 -->
            <section class="step-section" id="step-6">
                <div class="step-header">
                    <div class="step-badge-big purple">06</div>
                    <div>
                        <h2 data-i18n="s6_title">Resources & Actions — Pilih yang Kamu Butuh</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s1_time">~2 menit</span></span>
                            <span><i class="bi bi-star-fill" style="color:var(--amber);"></i> <span data-i18n="difficulty_important">Yang Paling Penting</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n-html="s6_intro">Ini bagian inti — kamu tentukan action apa aja yang API Key ini boleh lakukan. AWS akan otomatis assign <code>provider/default</code> yang di region <code>ap-southeast-1</code> = <strong>GrabMaps</strong>.</p>

                    <div class="aws-mockup">
                        <h4>📍 Maps Service</h4>
                        <div class="arn-line">arn:aws:geo-maps:ap-southeast-1::provider/<span class="arn-hl">default</span></div>
                        <div class="aws-mockup-grid" style="margin-top:14px;">
                            <div class="aws-service">
                                <div class="aws-service-title"><i class="bi bi-map-fill" style="color:var(--grab-green);"></i> Actions</div>
                                <ul>
                                    <li><i class="bi bi-check-circle-fill"></i> GetTile</li>
                                    <li><i class="bi bi-check-circle-fill"></i> GetStyleDescriptor</li>
                                    <li><i class="bi bi-check-circle-fill"></i> GetGlyphs</li>
                                    <li><i class="bi bi-check-circle-fill"></i> GetSprites</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="aws-mockup">
                        <h4>🔍 Places Service</h4>
                        <div class="arn-line">arn:aws:geo-places:ap-southeast-1::provider/<span class="arn-hl">default</span></div>
                        <div class="aws-mockup-grid" style="margin-top:14px;">
                            <div class="aws-service">
                                <div class="aws-service-title"><i class="bi bi-search" style="color:var(--purple);"></i> Actions</div>
                                <ul>
                                    <li><i class="bi bi-check-circle-fill"></i> SearchText</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Suggest</li>
                                    <li><i class="bi bi-check-circle-fill"></i> ReverseGeocode</li>
                                    <li><i class="bi bi-check-circle-fill"></i> GetPlace</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="aws-mockup">
                        <h4>🛣️ Routes Service</h4>
                        <div class="arn-line">arn:aws:geo-routes:ap-southeast-1::provider/<span class="arn-hl">default</span></div>
                        <div class="aws-mockup-grid" style="margin-top:14px;">
                            <div class="aws-service">
                                <div class="aws-service-title"><i class="bi bi-signpost-split-fill" style="color:var(--amber-dark);"></i> Actions</div>
                                <ul>
                                    <li><i class="bi bi-check-circle-fill"></i> CalculateRoutes</li>
                                    <li><i class="bi bi-check-circle-fill"></i> CalculateRouteMatrix</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="info-box success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <strong data-i18n="s6_why_t">Kenapa "default" di ARN?</strong>
                            <p data-i18n-html="s6_why_d">Di v2 modern, kamu <b>tidak perlu pilih provider manual</b>. AWS otomatis pakai provider default per region. Di <code>ap-southeast-1</code>, <code>provider/default</code> = <strong>GrabMaps</strong>. Ini bedanya v2 dengan v0 legacy yang harus create Map/PlaceIndex resource dulu.</p>
                        </div>
                    </div>

                    <div class="info-box tip">
                        <i class="bi bi-lightbulb-fill"></i>
                        <div>
                            <strong data-i18n="s6_tips_t">Tips: minimal actions</strong>
                            <p data-i18n-html="s6_tips_d">Prinsip <b>least privilege</b> — cuma centang action yang benar-benar dibutuhkan. Bisa selalu ditambah nanti via <b>Edit</b>.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STEP 7 -->
            <section class="step-section" id="step-7">
                <div class="step-header">
                    <div class="step-badge-big blue">07</div>
                    <div>
                        <h2 data-i18n="s7_title">Review & Create API Key</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s3_time">~30 detik</span></span>
                            <span><i class="bi bi-key-fill"></i> <span data-i18n="difficulty_final">Final</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n="s7_intro">Sebelum submit, review satu-satu:</p>

                    <ol class="action-list">
                        <li>
                            <span class="a-num">1</span>
                            <div class="a-body" data-i18n-html="s7_p1">
                                Region masih <code>ap-southeast-1</code>? ✓
                            </div>
                        </li>
                        <li>
                            <span class="a-num">2</span>
                            <div class="a-body" data-i18n-html="s7_p2">
                                Semua ARN berakhiran <span class="arn-highlight">provider/default</span>? ✓
                            </div>
                        </li>
                        <li>
                            <span class="a-num">3</span>
                            <div class="a-body" data-i18n="s7_p3">
                                Actions yang di-check sesuai kebutuhan app? ✓
                            </div>
                        </li>
                        <li>
                            <span class="a-num">4</span>
                            <div class="a-body" data-i18n-html="s7_p4">
                                Klik <strong>Create API key</strong>
                            </div>
                        </li>
                        <li>
                            <span class="a-num">5</span>
                            <div class="a-body" data-i18n-html="s7_p5">
                                <strong>Copy value key</strong> yang muncul (format: <code>v1.public.eyJqdGki...</code>)
                            </div>
                        </li>
                    </ol>

                    <figure class="real-screenshot" data-rs>
                        <span class="rs-badge">Screenshot</span>
                        <img src="{{ asset('images/tutorial/aws-key/step-7-copy-key.png') }}"
                             data-fallback="https://d2908q01vomqb2.cloudfront.net/0a57cb53ba59c46fc4b692527a38a87c78d84028/2023/09/11/image005-1-1024x962.jpg"
                             alt="AWS Console showing newly created API key with Show/Copy button"
                             loading="lazy" referrerpolicy="no-referrer">
                        <div class="rs-fallback">
                            <b>API key created</b> · click <b>Show API key value</b> then Copy
                        </div>
                    </figure>
                    <div class="mockup-caption"><span class="icon">📸</span> <span data-i18n="s7_caption">Real screenshot: Detail page — click "Show API key value" then Copy immediately</span></div>

                    <div class="info-box danger">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                        <div>
                            <strong data-i18n="s7_warn_t">PENTING: Simpan key value SEKARANG</strong>
                            <p data-i18n-html="s7_warn_d">Setelah tutup halaman, kamu <b>tidak bisa lihat value key lagi</b>. AWS hanya menampilkan value pertama kali. Kalau hilang, harus create key baru.</p>
                        </div>
                    </div>

                    <div class="code-block">
                        <button class="btn-copy" onclick="copyBlock(this)">📋 Copy</button>
                        <pre><span class="cmt" data-i18n="s7_code_c1"># Format API Key:</span>
<span class="str">v1.public.eyJqdGkiOiJhYmNkZWYxMi0zNDU2LTc4OTAtZ2hpai1rbG1ub3BxcnN0dXYifQ...</span>

<span class="cmt" data-i18n="s7_code_c2"># Simpan di tempat aman:</span>
<span class="kw">export</span> AWS_API_KEY=<span class="str">"v1.public.eyJqdGki..."</span>

<span class="cmt" data-i18n="s7_code_c3"># Atau di file .env Laravel:</span>
AWS_API_KEY=<span class="str">"v1.public.eyJqdGki..."</span>
AWS_REGION=<span class="str">"ap-southeast-1"</span></pre>
                    </div>
                </div>
            </section>

            <!-- STEP 8 -->
            <section class="step-section" id="step-8">
                <div class="step-header">
                    <div class="step-badge-big">08</div>
                    <div>
                        <h2 data-i18n="s8_title">Test API Key</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-clock"></i> <span data-i18n="s1_time">~2 menit</span></span>
                            <span><i class="bi bi-check2-square"></i> <span data-i18n="difficulty_verify">Verifikasi</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n="s8_intro">Sekarang test key untuk pastikan permission benar dan data GrabMaps ke-return. Ada 3 cara:</p>

                    <h4 style="margin:24px 0 12px;font-weight:800;" data-i18n="s8_way1_h">🚀 Cara 1: Test via Web App Kita (paling gampang)</h4>
                    <p data-i18n-html="s8_way1_d1">Buka <a href="/docs/aws-api">/docs/aws-api</a> → klik tombol <b>🔑 My Key</b> di kanan atas → paste API Key + centang allowed actions → Save.</p>
                    <p data-i18n-html="s8_way1_d2">Otomatis semua operations di sidebar akan kasih indikator hijau (allowed) atau merah (denied). Bisa langsung <b>Send Request</b> untuk test permission real-time.</p>

                    <h4 style="margin:24px 0 12px;font-weight:800;" data-i18n="s8_way2_h">🐚 Cara 2: Test via curl (paling detail)</h4>
                    <div class="code-block">
                        <button class="btn-copy" onclick="copyBlock(this)">📋 Copy</button>
                        <pre><span class="cmt" data-i18n="s8_code1_c"># Test Places Suggest (autocomplete) — any SEA city works</span>
<span class="kw">curl</span> -X POST <span class="str">"https://places.geo.ap-southeast-1.amazonaws.com/v2/suggest?key=<b>YOUR_API_KEY</b>"</span> \
  -H <span class="str">"Content-Type: application/json"</span> \
  -d <span class="str">'{
    "QueryText": "hawker centre singapore",
    "MaxResults": 3,
    "BiasPosition": [103.8198, 1.3521],
    "Language": "en"
  }'</span></pre>
                    </div>

                    <p data-i18n-html="s8_way2_d">Kalau balik <code>200</code> + <code>ResultItems</code> yang berisi POI Asia Tenggara → <span style="color:var(--grab-green-dark);font-weight:800;">✅ SUCCESS</span></p>

                    <h4 style="margin:24px 0 12px;font-weight:800;" data-i18n="s8_way3_h">🗺️ Cara 3: Test Map Rendering</h4>
                    <div class="code-block">
                        <button class="btn-copy" onclick="copyBlock(this)">📋 Copy</button>
                        <pre><span class="cmt" data-i18n="s8_code2_c"># Buka URL ini di browser — harus tampil tile map:</span>
https://maps.geo.ap-southeast-1.amazonaws.com/v2/tiles/<b>raster</b>/13/6480/4033?key=<b>YOUR_API_KEY</b></pre>
                    </div>

                    <div class="info-box success">
                        <i class="bi bi-trophy-fill"></i>
                        <div>
                            <strong data-i18n="s8_ok_t">🎉 Selamat! API Key siap dipakai!</strong>
                            <p data-i18n="s8_ok_d">Kalau semua test lolos, API Key kamu siap untuk production. Data yang di-return adalah GrabMaps coverage untuk seluruh SEA.</p>
                        </div>
                    </div>

                    <div class="info-box warn">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong data-i18n="s8_err_t">Kalau gagal (403 explicit deny)</strong>
                            <p data-i18n="s8_err_intro">Common causes:</p>
                            <ol style="margin:8px 0 0 20px;padding:0;font-size:0.85rem;">
                                <li data-i18n="s8_err_1">Action yang di-call belum di-tick saat create key</li>
                                <li data-i18n="s8_err_2">Referer restriction terlalu ketat</li>
                                <li data-i18n="s8_err_3">Region ARN di key beda dengan endpoint yang di-call</li>
                                <li data-i18n="s8_err_4">Key sudah expired</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <!-- BONUS: What's Next -->
            <section class="step-section" id="whats-next">
                <div class="step-header">
                    <div class="step-badge-big amber"><i class="bi bi-stars"></i></div>
                    <div>
                        <h2 data-i18n="wn_title">Bonus: Setelah Ini Apa?</h2>
                        <div class="step-meta">
                            <span><i class="bi bi-bookmark-star"></i> <span data-i18n="wn_meta">Referensi cepat</span></span>
                        </div>
                    </div>
                </div>
                <div class="step-body">
                    <p data-i18n="wn_intro">Kamu sudah punya API Key aktif. Berikut hal-hal yang biasanya dilakukan berikutnya untuk masuk ke tahap production:</p>

                    <div class="next-grid">
                        <a href="/docs/aws-api" class="next-card">
                            <div class="nc-icon">📖</div>
                            <h4 data-i18n="wn_c1_t">Pakai Key di Kode</h4>
                            <p data-i18n="wn_c1_d">Buka API Reference lengkap dengan contoh curl, JavaScript, & Laravel untuk setiap endpoint.</p>
                        </a>
                        <a href="https://console.aws.amazon.com/cloudwatch/" target="_blank" rel="noopener" class="next-card">
                            <div class="nc-icon">📊</div>
                            <h4 data-i18n="wn_c2_t">Monitor Usage</h4>
                            <p data-i18n="wn_c2_d">Set CloudWatch alarm untuk spike request abnormal & budget alert supaya tidak kaget di akhir bulan.</p>
                        </a>
                        <a href="https://console.aws.amazon.com/location/" target="_blank" rel="noopener" class="next-card">
                            <div class="nc-icon">🔄</div>
                            <h4 data-i18n="wn_c3_t">Rotasi Key Berkala</h4>
                            <p data-i18n="wn_c3_d">Best practice: rotasi API Key setiap 90 hari untuk production. Buat key baru dulu, ganti di app, baru delete key lama.</p>
                        </a>
                        <a href="/" class="next-card">
                            <div class="nc-icon">🗺️</div>
                            <h4 data-i18n="wn_c4_t">Live Demo GrabMaps</h4>
                            <p data-i18n="wn_c4_d">Coba fitur peta interaktif dengan data GrabMaps: search POI di seluruh Asia Tenggara, routing, geocoding real-time.</p>
                        </a>
                    </div>

                    <div class="info-box success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <strong data-i18n="wn_ok_t">Butuh bantuan integrasi?</strong>
                            <p data-i18n-html="wn_ok_d">Tim kami siap bantu proses integrasi API Key ke aplikasi kamu — dari POC sampai deployment production. Hubungi kami untuk konsultasi lebih lanjut.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- FAQ -->
    <section class="faq-section" id="faq">
        <div class="faq-container">
            <h2 class="faq-title" data-i18n="faq_title">Pertanyaan yang Sering Ditanya</h2>

            <details class="faq-item">
                <summary data-i18n="faq_q1">❓ What makes GrabMaps stand out across Southeast Asia?</summary>
                <div class="faq-body" data-i18n-html="faq_a1">
                    GrabMaps is built from <b>real operational data across Grab's Southeast Asia footprint</b> — millions of driver-partners operating daily across <b>Singapore, Malaysia, Thailand, Indonesia, Vietnam, the Philippines, Cambodia, and Myanmar</b>. The result: complete POI coverage from street vendors to enterprise venues, alley-level roads, accurate hyper-local addresses, and regularly refreshed traffic data. Perfect for logistics, ride-hailing, delivery, and on-demand services that need genuine SEA-native coverage.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q2">❓ Why the Singapore region for the whole of SEA?</summary>
                <div class="faq-body" data-i18n-html="faq_a2">
                    AWS Location Service serves GrabMaps for the entire Southeast Asia region from <b>ap-southeast-1 (Singapore)</b>. Other regional AWS regions (Jakarta, Malaysia, Thailand) don't yet host Location Service — but latency from any SEA country to Singapore is only ~10-30ms, negligible for maps applications.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q3">❓ Which countries does GrabMaps cover?</summary>
                <div class="faq-body" data-i18n-html="faq_a3">
                    GrabMaps provides detailed coverage for <b>8 Southeast Asian countries</b>: 🇸🇬 Singapore, 🇲🇾 Malaysia, 🇹🇭 Thailand, 🇮🇩 Indonesia, 🇻🇳 Vietnam, 🇵🇭 Philippines, 🇰🇭 Cambodia, and 🇲🇲 Myanmar. Data extends down to local roads, back alleys, and small POIs (restaurants, retail, public services, schools, hospitals, ATMs). Updates are continuous, driven by live Grab operations and community feedback across each country.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n-html="faq_q4">❓ What's the difference between <code>provider/default</code> and <code>provider/grab</code>?</summary>
                <div class="faq-body" data-i18n-html="faq_a4">
                    In modern v2, ARNs use <code>provider/default</code> — this auto-points to the default provider for the selected region. In <code>ap-southeast-1</code> that means <b>GrabMaps</b> automatically. The <code>provider/grab</code> format only appears in v0 Legacy which we don't use. Just follow the v2 flow and you get GrabMaps out of the box.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q5">❓ Can I test the key without installing anything?</summary>
                <div class="faq-body" data-i18n-html="faq_a5">
                    Yes. Open <a href="/docs/aws-api">/docs/aws-api</a> on this site — the <b>My Key Inspector</b> feature lets you paste your key, tick allowed actions, and hit <b>Send Request</b> in each panel to test permissions in real time. You can immediately see GrabMaps responses for queries anywhere in Southeast Asia.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q6">❓ What if my key leaks publicly?</summary>
                <div class="faq-body" data-i18n-html="faq_a6">
                    <b>Quick steps:</b> Open AWS Console → Location Service → API keys → pick the leaked key → <b>Delete</b>. Create a new key with Referer restrictions + short expiration. Set up a CloudWatch alarm to monitor abnormal request spikes. Best practice: use separate keys per environment (dev / staging / production) so a leak's blast radius stays contained.
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q7">❓ Why do I get 403 even though everything is ticked?</summary>
                <div class="faq-body" data-i18n-html="faq_a7">
                    Check 4 things:
                    <ol style="margin:8px 0 0 20px;padding:0;">
                        <li><b>Action naming:</b> make sure you're using <code>geo-maps:*</code> / <code>geo-places:*</code> / <code>geo-routes:*</code> (v2), not <code>geo:*</code> (v0 legacy)</li>
                        <li><b>Region ARN:</b> the key's ARN must be in <code>ap-southeast-1</code> to get GrabMaps</li>
                        <li><b>Referer:</b> if restrictions exist, the browser must come from a matching domain</li>
                        <li><b>Expiration:</b> check the expiry date</li>
                    </ol>
                </div>
            </details>

            <details class="faq-item">
                <summary data-i18n="faq_q8">❓ Need custom integration or a POC for your organization?</summary>
                <div class="faq-body" data-i18n-html="faq_a8">
                    We're ready to help from POC through production — including custom integration, maps architecture consulting, and integration with your internal systems (WMS, TMS, dispatch, fleet management, etc.). Reach out for a scheduled demo and a discussion around your organization's specific needs across any SEA market.
                </div>
            </details>
        </div>
    </section>

    <!-- FINAL CTA -->
    <section class="final-cta">
        <h2 data-i18n="cta_title">Ready to Power Richer Maps Across Southeast Asia?</h2>
        <p data-i18n="cta_desc">Kamu sudah selesai tutorial. Sekarang paste API Key ke aplikasi, atau explore reference lengkap kita.</p>
        <div class="cta-buttons">
            <a href="/docs/aws-api" class="btn-cta primary">
                <i class="bi bi-book-fill"></i> <span data-i18n="cta_ref">Buka API Reference</span>
            </a>
            <a href="/" class="btn-cta secondary">
                <i class="bi bi-play-circle-fill"></i> <span data-i18n="cta_demo">Coba Live Demo</span>
            </a>
        </div>
    </section>

    <footer class="footer" data-i18n-html="footer_text">
        Tutorial Setup GrabMaps · Powered by <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location Service</a> · Provider by <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab</a>
    </footer>

    <script>
        /* ================= i18n dictionary ================= */
        const I18N = {
            id: {
                brand: 'GrabMaps Tutorial',
                nav_hub: 'Hub',
                nav_reference: 'API Reference',
                nav_console: 'AWS Console',

                hero_badge: 'AWS Location Service v2 · GrabMaps · ap-southeast-1',
                hero_title: 'Setup AWS Location Service<br>dengan <span class="gradient-text">GrabMaps Provider</span>',
                hero_lead: 'Tutorial step-by-step dari <b>nol</b> sampai punya API Key aktif yang siap dipakai untuk mapping seluruh Asia Tenggara — POI, routing, geocoding — semuanya bertenaga GrabMaps.',
                stat_steps: 'Langkah',
                stat_minutes: 'Menit',
                stat_service: 'Service',
                stat_coding: 'Coding',
                hero_cta_start: 'Mulai Tutorial',
                hero_cta_faq: 'Lihat FAQ',
                hero_scroll: 'Scroll',

                sidebar_title: 'Progress Tutorial',
                nav_step_1: 'Prasyarat & Persiapan',
                nav_step_2: 'Login & Buka Location Service',
                nav_step_3: 'Pilih Region Singapore',
                nav_step_4: 'Buat API Key Baru',
                nav_step_5: 'Set Restrictions',
                nav_step_6: 'Resources & Actions',
                nav_step_7: 'Review & Create',
                nav_step_8: 'Test API Key',

                difficulty_easy: 'Mudah',
                difficulty_crucial: 'Krusial',
                difficulty_security: 'Security',
                difficulty_important: 'Yang Paling Penting',
                difficulty_final: 'Final',
                difficulty_verify: 'Verifikasi',

                s1_title: 'Prasyarat & Persiapan',
                s1_time: '~2 menit',
                s1_intro: 'Sebelum mulai, pastikan kamu punya beberapa hal berikut:',
                s1_p1_t: 'AWS Account aktif',
                s1_p1_d: 'Kalau belum punya, sign up gratis di <a href="https://aws.amazon.com/free/" target="_blank" rel="noopener">aws.amazon.com/free</a>. Free tier cukup untuk demo & development.',
                s1_p2_t: 'Email verifikasi + Kartu kredit',
                s1_p2_d: 'AWS wajibkan verifikasi payment method walaupun free tier. Tidak akan di-charge kalau usage di bawah limit gratis.',
                s1_p3_t: 'Browser modern',
                s1_p3_d: 'Chrome, Firefox, Edge, atau Safari versi terbaru. AWS Console pakai banyak JavaScript.',
                s1_cost_t: 'Free tier (trial 3 bulan pertama)',
                s1_cost_d: 'Free tier AWS Location Service (3 bulan trial): <code>10K Places Suggest</code>, <code>20K Geocode/Search</code>, <code>10K Routes</code>, dan <code>500K Map tile loads</code> per bulan. Cukup untuk POC & development. Lihat <a href="https://aws.amazon.com/location/pricing/" target="_blank" rel="noopener">pricing resmi</a>.',

                s2_title: 'Login & Buka Location Service',
                s2_time: '~1 menit',
                s2_intro: 'Login ke <a href="https://console.aws.amazon.com/" target="_blank" rel="noopener">AWS Management Console</a>, lalu:',
                s2_p1: 'Klik search bar di atas → ketik <code>Location Service</code>',
                s2_p2: 'Klik hasil pertama: <strong>Amazon Location Service</strong>',
                s2_p3: 'Kamu akan masuk ke dashboard Location Service',
                s2_mockup: 'Sidebar kiri berisi menu — kita akan langsung ke <b>API keys</b> di langkah selanjutnya',
                s2_caption: 'Screenshot resmi AWS Docs: menu <b>API keys</b> di sidebar Amazon Location Service Console',
                s3_caption: 'Ilustrasi: dropdown region — pilih Singapore untuk auto-dapat GrabMaps',
                s4_caption: 'Screenshot resmi AWS Docs: form Create API key — isi Name & Description',
                s5_caption: 'Screenshot resmi AWS Docs: opsi Client restrictions & expiration',
                s7_caption: 'Screenshot resmi AWS Docs: detail page — klik "Show API key value" lalu Copy langsung',
                s7_mockup_hint: 'Value key hanya bisa dilihat sekali. Copy sekarang sebelum tutup halaman.',

                s3_title: 'Pilih Region: Singapore (ap-southeast-1)',
                s3_time: '~30 detik',
                s3_intro: 'Ini <strong>langkah paling penting</strong> — region menentukan provider mana yang aktif. GrabMaps hanya tersedia di <code>ap-southeast-1</code>.',
                s3_p1: 'Cari dropdown region di <strong>pojok kanan atas</strong> AWS Console',
                s3_p2: 'Klik → pilih <code>Asia Pacific (Singapore) ap-southeast-1</code>',
                s3_p3: 'Halaman akan refresh — pastikan indikator region sudah berubah',
                s3_why_t: 'Kenapa Singapore untuk seluruh SEA?',
                s3_why_d: 'AWS Location Service <b>belum tersedia di region Jakarta / Malaysia / Thailand</b>. Data GrabMaps untuk seluruh SEA — 8 negara sampai jalan lokal — diserve dari region Singapore. Latency ~10-30ms tambahan dari negara SEA manapun, masih acceptable untuk maps app.',
                s3_table_h: 'Region → Provider mapping',
                s3_col_code: 'Region Code',
                s3_col_loc: 'Lokasi',
                s3_col_prov: 'Default Provider',
                s3_col_best: 'Cocok untuk',
                s3_na: 'N/A (belum tersedia)',

                s4_title: 'Buat API Key Baru',
                s4_intro: 'Sekarang kita create API Key yang akan digunakan aplikasi untuk memanggil AWS Location Service.',
                s4_p1: 'Di sidebar kiri, klik menu <strong>API keys</strong>',
                s4_p2: 'Klik tombol biru <strong>Create API key</strong> di kanan atas',
                s4_p3: 'Isi <code>Name</code>: misal <code>my-grabmaps-key</code> — nama bebas, cuma untuk identifikasi',
                s4_p4: '<code>Description</code> (opsional): "API Key untuk demo GrabMaps Asia Tenggara"',
                s4_naming_t: 'Naming convention',
                s4_naming_d: 'Kalau nanti punya multiple keys untuk beda project, kasih prefix jelas. Contoh: <code>demo-sea</code>, <code>prod-regional</code>, <code>dev-tim-a</code>.',

                s5_title: 'Set Restrictions (Opsional tapi Direkomendasikan)',
                s5_intro: 'Batasi API Key supaya kalau bocor ke public, damage terkontrol.',
                s5_exp_h: 'Expiration Date',
                s5_exp_d: 'Set tanggal expired supaya key otomatis inactive setelah tanggal itu. Rekomendasi:',
                s5_exp_dev: '<strong>Development/Demo</strong>: 3-6 bulan',
                s5_exp_prod: '<strong>Production</strong>: 1 tahun, rotasi rutin',
                s5_exp_pub: '<strong>Public client-side</strong>: 90 hari max + Referer restriction',
                s5_ref_h: 'Referer Restriction',
                s5_ref_d: 'Kalau API Key dipakai di frontend (browser), tambahkan Referer restriction supaya cuma domain kamu yang bisa pakai.',
                s5_code_cmt1: '# Contoh Referer patterns yang valid',
                s5_code_cmt2: '# untuk local development',
                s5_tradeoff_t: 'Trade-off Referer',
                s5_tradeoff_d: 'Kalau di-set restrict ke <code>example.com</code>, API Key tidak akan jalan dari <code>localhost</code>. Untuk development, kasih pattern yang mencakup keduanya atau bikin key terpisah dev vs prod.',

                s6_title: 'Resources & Actions — Pilih yang Kamu Butuh',
                s6_intro: 'Ini bagian inti — kamu tentukan action apa aja yang API Key ini boleh lakukan. AWS akan otomatis assign <code>provider/default</code> yang di region <code>ap-southeast-1</code> = <strong>GrabMaps</strong>.',
                s6_why_t: 'Kenapa "default" di ARN?',
                s6_why_d: 'Di v2 modern, kamu <b>tidak perlu pilih provider manual</b>. AWS otomatis pakai provider default per region. Di <code>ap-southeast-1</code>, <code>provider/default</code> = <strong>GrabMaps</strong>. Ini bedanya v2 dengan v0 legacy yang harus create Map/PlaceIndex resource dulu.',
                s6_tips_t: 'Tips: minimal actions',
                s6_tips_d: 'Prinsip <b>least privilege</b> — cuma centang action yang benar-benar dibutuhkan. Bisa selalu ditambah nanti via <b>Edit</b>.',

                s7_title: 'Review & Create API Key',
                s7_intro: 'Sebelum submit, review satu-satu:',
                s7_p1: 'Region masih <code>ap-southeast-1</code>? ✓',
                s7_p2: 'Semua ARN berakhiran <span class="arn-highlight">provider/default</span>? ✓',
                s7_p3: 'Actions yang di-check sesuai kebutuhan app? ✓',
                s7_p4: 'Klik <strong>Create API key</strong>',
                s7_p5: '<strong>Copy value key</strong> yang muncul (format: <code>v1.public.eyJqdGki...</code>)',
                s7_warn_t: 'PENTING: Simpan key value SEKARANG',
                s7_warn_d: 'Setelah tutup halaman, kamu <b>tidak bisa lihat value key lagi</b>. AWS hanya menampilkan value pertama kali. Kalau hilang, harus create key baru.',
                s7_code_c1: '# Format API Key:',
                s7_code_c2: '# Simpan di tempat aman:',
                s7_code_c3: '# Atau di file .env Laravel:',

                s8_title: 'Test API Key',
                s8_intro: 'Sekarang test key untuk pastikan permission benar dan data GrabMaps ke-return. Ada 3 cara:',
                s8_way1_h: '🚀 Cara 1: Test via Web App Kita (paling gampang)',
                s8_way1_d1: 'Buka <a href="/docs/aws-api">/docs/aws-api</a> → klik tombol <b>🔑 My Key</b> di kanan atas → paste API Key + centang allowed actions → Save.',
                s8_way1_d2: 'Otomatis semua operations di sidebar akan kasih indikator hijau (allowed) atau merah (denied). Bisa langsung <b>Send Request</b> untuk test permission real-time.',
                s8_way2_h: '🐚 Cara 2: Test via curl (paling detail)',
                s8_code1_c: '# Test Places Suggest (autocomplete) — any SEA city works',
                s8_way2_d: 'Kalau balik <code>200</code> + <code>ResultItems</code> yang berisi POI Asia Tenggara → <span style="color:var(--grab-green-dark);font-weight:800;">✅ SUCCESS</span>',
                s8_way3_h: '🗺️ Cara 3: Test Map Rendering',
                s8_code2_c: '# Buka URL ini di browser — harus tampil tile map:',
                s8_ok_t: '🎉 Selamat! API Key siap dipakai!',
                s8_ok_d: 'Kalau semua test lolos, API Key kamu siap untuk production. Data yang di-return adalah GrabMaps coverage untuk seluruh SEA.',
                s8_err_t: 'Kalau gagal (403 explicit deny)',
                s8_err_intro: 'Penyebab umum:',
                s8_err_1: 'Action yang di-call belum di-tick saat create key',
                s8_err_2: 'Referer restriction terlalu ketat',
                s8_err_3: 'Region ARN di key beda dengan endpoint yang di-call',
                s8_err_4: 'Key sudah expired',

                version_h: '📌 Kamu mengikuti flow AWS Location Service v2',
                version_intro: 'Tutorial ini pakai API standalone v2 modern, bukan v0 legacy. Ini bedanya singkat:',
                v2_title: 'v2 Standalone (yang kita pakai)',
                v2_p1: 'Endpoint langsung: <code>places.geo.*</code>, <code>routes.geo.*</code>, <code>maps.geo.*</code>',
                v2_p2: '<b>Tanpa</b> resource setup — pakai <code>provider/default</code>',
                v2_p3: 'Provider ditentukan <b>otomatis by region</b>',
                v2_p4: 'Action: <code>geo-maps:*</code>, <code>geo-places:*</code>, <code>geo-routes:*</code>',
                v2_p5: 'Direkomendasikan untuk semua project baru ✅',
                v0_title: 'v0 Legacy (tidak kita pakai)',
                v0_p1: 'Endpoint via resource: <code>maps.geo.*/maps/v0/maps/{name}</code>',
                v0_p2: '<b>Wajib</b> create Map/PlaceIndex/RouteCalculator dulu',
                v0_p3: 'Provider dipilih <b>manual</b> saat create resource',
                v0_p4: 'Action: <code>geo:GetMapTile</code>, <code>geo:SearchPlaceIndex*</code>, dsb',
                v0_p5: 'Masih supported, tapi <b>tidak untuk project baru</b> ⚠️',

                wn_title: 'Bonus: Setelah Ini Apa?',
                wn_meta: 'Referensi cepat',
                wn_intro: 'Kamu sudah punya API Key aktif. Berikut hal-hal yang biasanya dilakukan berikutnya untuk masuk ke tahap production:',
                wn_c1_t: 'Pakai Key di Kode',
                wn_c1_d: 'Buka API Reference lengkap dengan contoh curl, JavaScript, & Laravel untuk setiap endpoint.',
                wn_c2_t: 'Monitor Usage',
                wn_c2_d: 'Set CloudWatch alarm untuk spike request abnormal & budget alert supaya tidak kaget di akhir bulan.',
                wn_c3_t: 'Rotasi Key Berkala',
                wn_c3_d: 'Best practice: rotasi API Key setiap 90 hari untuk production. Buat key baru dulu, ganti di app, baru delete key lama.',
                wn_c4_t: 'Live Demo GrabMaps',
                wn_c4_d: 'Coba fitur peta interaktif dengan data GrabMaps: search POI di seluruh Asia Tenggara, routing, geocoding real-time.',
                wn_ok_t: 'Butuh bantuan integrasi?',
                wn_ok_d: 'Tim kami siap bantu proses integrasi API Key ke aplikasi kamu — dari POC sampai deployment production. Hubungi kami untuk konsultasi lebih lanjut.',

                faq_title: 'Pertanyaan yang Sering Ditanya',
                faq_q1: '❓ Apa keunggulan GrabMaps di seluruh Asia Tenggara?',
                faq_a1: 'GrabMaps dibangun dari <b>data operasional real Grab di seluruh Asia Tenggara</b> — jutaan driver-partner beroperasi setiap hari di <b>Singapura, Malaysia, Thailand, Indonesia, Vietnam, Filipina, Kamboja, dan Myanmar</b>. Hasilnya: POI lengkap dari pedagang kaki lima sampai venue enterprise, cakupan gang & jalan lokal, alamat hyper-lokal yang akurat, dan data traffic yang di-refresh rutin. Cocok untuk logistik, ride-hailing, delivery, dan on-demand service yang butuh coverage SEA yang genuine.',
                faq_q2: '❓ Kenapa region Singapore untuk seluruh SEA?',
                faq_a2: 'AWS Location Service serve GrabMaps untuk seluruh Asia Tenggara dari <b>ap-southeast-1 (Singapore)</b>. Region AWS lain di kawasan (Jakarta, Malaysia, Thailand) belum host Location Service — tapi latency dari negara SEA manapun ke Singapore cuma ~10-30ms, tidak signifikan untuk aplikasi maps.',
                faq_q3: '❓ GrabMaps meng-cover negara apa saja?',
                faq_a3: 'GrabMaps punya coverage detail di <b>8 negara Asia Tenggara</b>: 🇸🇬 Singapura, 🇲🇾 Malaysia, 🇹🇭 Thailand, 🇮🇩 Indonesia, 🇻🇳 Vietnam, 🇵🇭 Filipina, 🇰🇭 Kamboja, dan 🇲🇲 Myanmar. Data mencakup jalan lokal, gang belakang, POI kecil (restoran, retail, layanan publik, sekolah, rumah sakit, ATM). Update kontinyu berdasarkan aktivitas operasional Grab yang live dan feedback komunitas di masing-masing negara.',
                faq_q4: '❓ Bedanya <code>provider/default</code> vs <code>provider/grab</code>?',
                faq_a4: 'Di v2 modern, ARN pakai <code>provider/default</code> — ini otomatis mengarah ke provider default per region. Di <code>ap-southeast-1</code> = <b>GrabMaps</b> otomatis. Format <code>provider/grab</code> hanya muncul di v0 Legacy yang tidak kita pakai. Tinggal ikuti flow v2 dan dapat GrabMaps langsung.',
                faq_q5: '❓ Bisa test key tanpa install apa-apa?',
                faq_a5: 'Bisa. Buka <a href="/docs/aws-api">/docs/aws-api</a> di web ini — ada fitur <b>My Key Inspector</b>. Paste key, centang allowed actions, dan hit <b>Send Request</b> di setiap panel untuk test permission real-time. Kamu langsung bisa lihat response GrabMaps untuk query di manapun di Asia Tenggara.',
                faq_q6: '❓ Kalau key bocor ke public gimana?',
                faq_a6: '<b>Langkah cepat:</b> Buka AWS Console → Location Service → API keys → pilih key yang bocor → <b>Delete</b>. Buat key baru dengan Referer restriction + expiration singkat. Setup CloudWatch alarm untuk monitor spike request abnormal. Best practice: pakai key terpisah per environment (dev / staging / production) supaya blast radius kebocoran terkontrol.',
                faq_q7: '❓ Kenapa dapat 403 padahal semua sudah di-tick?',
                faq_a7: 'Cek 4 hal:<ol style="margin:8px 0 0 20px;padding:0;"><li><b>Action naming:</b> pastikan pakai <code>geo-maps:*</code> / <code>geo-places:*</code> / <code>geo-routes:*</code> (v2), bukan <code>geo:*</code> (v0 legacy)</li><li><b>Region ARN:</b> ARN key harus di <code>ap-southeast-1</code> supaya dapat GrabMaps</li><li><b>Referer:</b> kalau ada restriction, browser harus datang dari domain yang match</li><li><b>Expiration:</b> cek tanggal expired</li></ol>',
                faq_q8: '❓ Butuh custom integrasi atau POC untuk organisasi?',
                faq_a8: 'Kami siap bantu dari POC sampai production — termasuk custom integrasi, konsultasi arsitektur maps, dan integrasi dengan sistem internal (WMS, TMS, dispatch, fleet management, dsb). Hubungi kami untuk demo terjadwal & diskusi kebutuhan organisasi kamu di market SEA manapun.',

                cta_title: 'Siap Bikin Peta Asia Tenggara Lebih Kaya?',
                cta_desc: 'Kamu sudah selesai tutorial. Sekarang paste API Key ke aplikasi, atau explore reference lengkap kita.',
                cta_ref: 'Buka API Reference',
                cta_demo: 'Coba Live Demo',

                footer_text: 'Tutorial Setup GrabMaps · Powered by <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location Service</a> · Provider by <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab</a>'
            },
            en: {
                brand: 'GrabMaps Tutorial',
                nav_hub: 'Hub',
                nav_reference: 'API Reference',
                nav_console: 'AWS Console',

                hero_badge: 'AWS Location Service v2 · GrabMaps · ap-southeast-1',
                hero_title: 'Setup AWS Location Service<br>with <span class="gradient-text">GrabMaps Provider</span>',
                hero_lead: 'A step-by-step guide from <b>zero</b> to an active API Key ready for mapping across Southeast Asia — POI, routing, geocoding — all powered by GrabMaps.',
                stat_steps: 'Steps',
                stat_minutes: 'Minutes',
                stat_service: 'Services',
                stat_coding: 'Coding',
                hero_cta_start: 'Start Tutorial',
                hero_cta_faq: 'See FAQ',
                hero_scroll: 'Scroll',

                sidebar_title: 'Tutorial Progress',
                nav_step_1: 'Prerequisites & Setup',
                nav_step_2: 'Login & Open Location Service',
                nav_step_3: 'Choose Singapore Region',
                nav_step_4: 'Create a New API Key',
                nav_step_5: 'Set Restrictions',
                nav_step_6: 'Resources & Actions',
                nav_step_7: 'Review & Create',
                nav_step_8: 'Test the API Key',

                difficulty_easy: 'Easy',
                difficulty_crucial: 'Crucial',
                difficulty_security: 'Security',
                difficulty_important: 'Most Important',
                difficulty_final: 'Final',
                difficulty_verify: 'Verification',

                s1_title: 'Prerequisites & Setup',
                s1_time: '~2 minutes',
                s1_intro: 'Before starting, make sure you have the following:',
                s1_p1_t: 'Active AWS Account',
                s1_p1_d: 'If you don\'t have one, sign up for free at <a href="https://aws.amazon.com/free/" target="_blank" rel="noopener">aws.amazon.com/free</a>. Free tier is enough for demos & development.',
                s1_p2_t: 'Verified email + Credit card',
                s1_p2_d: 'AWS requires payment method verification even for free tier. You won\'t be charged if usage stays below the free limit.',
                s1_p3_t: 'Modern browser',
                s1_p3_d: 'Latest Chrome, Firefox, Edge, or Safari. The AWS Console relies heavily on JavaScript.',
                s1_cost_t: 'Free tier (first 3 months)',
                s1_cost_d: 'AWS Location Service free tier (3-month trial): <code>10K Places Suggest</code>, <code>20K Geocode/Search</code>, <code>10K Routes</code>, and <code>500K Map tile loads</code> per month. Great for POC & development. See <a href="https://aws.amazon.com/location/pricing/" target="_blank" rel="noopener">official pricing</a>.',

                s2_title: 'Login & Open Location Service',
                s2_time: '~1 minute',
                s2_intro: 'Log in to the <a href="https://console.aws.amazon.com/" target="_blank" rel="noopener">AWS Management Console</a>, then:',
                s2_p1: 'Click the search bar at the top → type <code>Location Service</code>',
                s2_p2: 'Click the first result: <strong>Amazon Location Service</strong>',
                s2_p3: 'You\'ll land on the Location Service dashboard',
                s2_mockup: 'The left sidebar contains menus — we\'ll head straight to <b>API keys</b> in the next step',
                s2_caption: 'Official AWS Docs screenshot: <b>API keys</b> menu in the Amazon Location Service Console sidebar',
                s3_caption: 'Illustration: region dropdown — pick Singapore to auto-get GrabMaps',
                s4_caption: 'Official AWS Docs screenshot: Create API key form — fill Name & Description',
                s5_caption: 'Official AWS Docs screenshot: Client restrictions & expiration options',
                s7_caption: 'Official AWS Docs screenshot: Detail page — click "Show API key value" then Copy immediately',
                s7_mockup_hint: 'The key value is shown only once. Copy it now before closing the page.',

                s3_title: 'Choose Region: Singapore (ap-southeast-1)',
                s3_time: '~30 seconds',
                s3_intro: 'This is the <strong>most important step</strong> — the region determines which provider is active. GrabMaps is only available in <code>ap-southeast-1</code>.',
                s3_p1: 'Find the region dropdown at the <strong>top right</strong> of the AWS Console',
                s3_p2: 'Click → select <code>Asia Pacific (Singapore) ap-southeast-1</code>',
                s3_p3: 'The page will refresh — make sure the region indicator has changed',
                s3_why_t: 'Why Singapore for the whole of SEA?',
                s3_why_d: 'AWS Location Service is <b>not yet available in Jakarta / Malaysia / Thailand regions</b>. GrabMaps data for all of SEA — 8 countries down to local streets — is served from Singapore. ~10-30ms extra latency from any SEA country, still acceptable for maps apps.',
                s3_table_h: 'Region → Provider mapping',
                s3_col_code: 'Region Code',
                s3_col_loc: 'Location',
                s3_col_prov: 'Default Provider',
                s3_col_best: 'Best For',
                s3_na: 'N/A (not available)',

                s4_title: 'Create a New API Key',
                s4_intro: 'Now let\'s create the API Key the app will use to call AWS Location Service.',
                s4_p1: 'In the left sidebar, click <strong>API keys</strong>',
                s4_p2: 'Click the blue <strong>Create API key</strong> button at the top right',
                s4_p3: 'Fill in <code>Name</code>: e.g. <code>my-grabmaps-key</code> — free-form, just for identification',
                s4_p4: '<code>Description</code> (optional): "API Key for GrabMaps Southeast Asia demo"',
                s4_naming_t: 'Naming convention',
                s4_naming_d: 'If you plan to have multiple keys across projects, use clear prefixes. E.g. <code>demo-sea</code>, <code>prod-regional</code>, <code>dev-team-a</code>.',

                s5_title: 'Set Restrictions (Optional but Recommended)',
                s5_intro: 'Restrict the API Key so that if it ever leaks publicly, damage is contained.',
                s5_exp_h: 'Expiration Date',
                s5_exp_d: 'Set an expiration date so the key auto-deactivates. Recommended:',
                s5_exp_dev: '<strong>Development/Demo</strong>: 3-6 months',
                s5_exp_prod: '<strong>Production</strong>: 1 year, rotate periodically',
                s5_exp_pub: '<strong>Public client-side</strong>: 90 days max + Referer restriction',
                s5_ref_h: 'Referer Restriction',
                s5_ref_d: 'If the API Key is used in the frontend (browser), add Referer restrictions so only your domain can use it.',
                s5_code_cmt1: '# Example valid Referer patterns',
                s5_code_cmt2: '# for local development',
                s5_tradeoff_t: 'Referer trade-off',
                s5_tradeoff_d: 'If restricted to <code>example.com</code>, the API Key won\'t work from <code>localhost</code>. For development, use patterns that cover both or make separate dev/prod keys.',

                s6_title: 'Resources & Actions — Pick What You Need',
                s6_intro: 'This is the core section — you decide which actions this API Key can perform. AWS auto-assigns <code>provider/default</code>, which in region <code>ap-southeast-1</code> = <strong>GrabMaps</strong>.',
                s6_why_t: 'Why "default" in the ARN?',
                s6_why_d: 'In modern v2, you <b>don\'t need to pick a provider manually</b>. AWS uses the default provider per region. In <code>ap-southeast-1</code>, <code>provider/default</code> = <strong>GrabMaps</strong>. That\'s the key difference from v0 legacy which required creating Map/PlaceIndex resources first.',
                s6_tips_t: 'Tip: minimal actions',
                s6_tips_d: '<b>Least privilege</b> principle — only tick actions you actually need. You can always add more later via <b>Edit</b>.',

                s7_title: 'Review & Create the API Key',
                s7_intro: 'Before submitting, review one by one:',
                s7_p1: 'Region still <code>ap-southeast-1</code>? ✓',
                s7_p2: 'All ARNs ending in <span class="arn-highlight">provider/default</span>? ✓',
                s7_p3: 'Checked actions match app needs? ✓',
                s7_p4: 'Click <strong>Create API key</strong>',
                s7_p5: '<strong>Copy the key value</strong> that appears (format: <code>v1.public.eyJqdGki...</code>)',
                s7_warn_t: 'IMPORTANT: Save the key value NOW',
                s7_warn_d: 'Once you close the page, you <b>can\'t see the key value again</b>. AWS only shows the value once. If lost, you must create a new key.',
                s7_code_c1: '# API Key format:',
                s7_code_c2: '# Save it somewhere safe:',
                s7_code_c3: '# Or in your Laravel .env file:',

                s8_title: 'Test the API Key',
                s8_intro: 'Now test the key to make sure permissions are correct and GrabMaps data comes back. Three ways:',
                s8_way1_h: '🚀 Way 1: Test via our Web App (easiest)',
                s8_way1_d1: 'Open <a href="/docs/aws-api">/docs/aws-api</a> → click the <b>🔑 My Key</b> button at the top right → paste API Key + tick allowed actions → Save.',
                s8_way1_d2: 'All operations in the sidebar will auto-show green (allowed) or red (denied). You can directly hit <b>Send Request</b> in each panel to test permissions in real time.',
                s8_way2_h: '🐚 Way 2: Test via curl (most detailed)',
                s8_code1_c: '# Test Places Suggest (autocomplete) — any SEA city works',
                s8_way2_d: 'If you get <code>200</code> + <code>ResultItems</code> containing SEA POIs → <span style="color:var(--grab-green-dark);font-weight:800;">✅ SUCCESS</span>',
                s8_way3_h: '🗺️ Way 3: Test Map Rendering',
                s8_code2_c: '# Open this URL in browser — should display a map tile:',
                s8_ok_t: '🎉 Congrats! Your API Key is ready!',
                s8_ok_d: 'If all tests pass, your API Key is ready for production. The returned data is GrabMaps coverage across all of SEA.',
                s8_err_t: 'If it fails (403 explicit deny)',
                s8_err_intro: 'Common causes:',
                s8_err_1: 'The called action wasn\'t ticked when creating the key',
                s8_err_2: 'Referer restriction is too strict',
                s8_err_3: 'Region ARN in the key differs from the endpoint being called',
                s8_err_4: 'The key has expired',

                version_h: '📌 You\'re following the AWS Location Service v2 flow',
                version_intro: 'This tutorial uses the modern v2 standalone API, not the v0 legacy. Quick summary of the difference:',
                v2_title: 'v2 Standalone (what we use)',
                v2_p1: 'Direct endpoints: <code>places.geo.*</code>, <code>routes.geo.*</code>, <code>maps.geo.*</code>',
                v2_p2: '<b>No</b> resource setup — uses <code>provider/default</code>',
                v2_p3: 'Provider chosen <b>automatically by region</b>',
                v2_p4: 'Actions: <code>geo-maps:*</code>, <code>geo-places:*</code>, <code>geo-routes:*</code>',
                v2_p5: 'Recommended for all new projects ✅',
                v0_title: 'v0 Legacy (not what we use)',
                v0_p1: 'Endpoint via resource: <code>maps.geo.*/maps/v0/maps/{name}</code>',
                v0_p2: '<b>Must</b> create Map/PlaceIndex/RouteCalculator first',
                v0_p3: 'Provider picked <b>manually</b> at resource creation',
                v0_p4: 'Actions: <code>geo:GetMapTile</code>, <code>geo:SearchPlaceIndex*</code>, etc.',
                v0_p5: 'Still supported, but <b>not for new projects</b> ⚠️',

                wn_title: 'Bonus: What\'s Next?',
                wn_meta: 'Quick reference',
                wn_intro: 'You have an active API Key. Here are the usual next steps toward production:',
                wn_c1_t: 'Use the Key in Code',
                wn_c1_d: 'Open the full API Reference with curl, JavaScript, & Laravel examples for every endpoint.',
                wn_c2_t: 'Monitor Usage',
                wn_c2_d: 'Set CloudWatch alarms for abnormal request spikes & budget alerts so you don\'t get surprised at month-end.',
                wn_c3_t: 'Rotate Keys Regularly',
                wn_c3_d: 'Best practice: rotate API Keys every 90 days for production. Create a new key first, swap in the app, then delete the old key.',
                wn_c4_t: 'Live GrabMaps Demo',
                wn_c4_d: 'Try interactive map features with GrabMaps data: Southeast Asia POI search, routing, real-time geocoding.',
                wn_ok_t: 'Need integration help?',
                wn_ok_d: 'Our team is ready to help you integrate the API Key into your app — from POC through production deployment. Contact us for further consultation.',

                faq_title: 'Frequently Asked Questions',
                faq_q1: '❓ What makes GrabMaps stand out across Southeast Asia?',
                faq_a1: 'GrabMaps is built from <b>real operational data across Grab\'s Southeast Asia footprint</b> — millions of driver-partners operating daily across <b>Singapore, Malaysia, Thailand, Indonesia, Vietnam, the Philippines, Cambodia, and Myanmar</b>. The result: complete POI coverage from street vendors to enterprise venues, alley-level roads, accurate hyper-local addresses, and regularly refreshed traffic data. Perfect for logistics, ride-hailing, delivery, and on-demand services that need genuine SEA-native coverage.',
                faq_q2: '❓ Why the Singapore region for the whole of SEA?',
                faq_a2: 'AWS Location Service serves GrabMaps for the entire Southeast Asia region from <b>ap-southeast-1 (Singapore)</b>. Other regional AWS regions (Jakarta, Malaysia, Thailand) don\'t yet host Location Service — but latency from any SEA country to Singapore is only ~10-30ms, negligible for maps applications.',
                faq_q3: '❓ Which countries does GrabMaps cover?',
                faq_a3: 'GrabMaps provides detailed coverage for <b>8 Southeast Asian countries</b>: 🇸🇬 Singapore, 🇲🇾 Malaysia, 🇹🇭 Thailand, 🇮🇩 Indonesia, 🇻🇳 Vietnam, 🇵🇭 Philippines, 🇰🇭 Cambodia, and 🇲🇲 Myanmar. Data extends down to local roads, back alleys, and small POIs (restaurants, retail, public services, schools, hospitals, ATMs). Updates are continuous, driven by live Grab operations and community feedback across each country.',
                faq_q4: '❓ What\'s the difference between <code>provider/default</code> and <code>provider/grab</code>?',
                faq_a4: 'In modern v2, ARNs use <code>provider/default</code> — this auto-points to the default provider for the selected region. In <code>ap-southeast-1</code> that means <b>GrabMaps</b> automatically. The <code>provider/grab</code> format only appears in v0 Legacy which we don\'t use. Just follow the v2 flow and you get GrabMaps out of the box.',
                faq_q5: '❓ Can I test the key without installing anything?',
                faq_a5: 'Yes. Open <a href="/docs/aws-api">/docs/aws-api</a> on this site — the <b>My Key Inspector</b> feature lets you paste your key, tick allowed actions, and hit <b>Send Request</b> in each panel to test permissions in real time. You can immediately see GrabMaps responses for queries anywhere in Southeast Asia.',
                faq_q6: '❓ What if my key leaks publicly?',
                faq_a6: '<b>Quick steps:</b> Open AWS Console → Location Service → API keys → pick the leaked key → <b>Delete</b>. Create a new key with Referer restrictions + short expiration. Set up a CloudWatch alarm to monitor abnormal request spikes. Best practice: use separate keys per environment (dev / staging / production) so a leak\'s blast radius stays contained.',
                faq_q7: '❓ Why do I get 403 even though everything is ticked?',
                faq_a7: 'Check 4 things:<ol style="margin:8px 0 0 20px;padding:0;"><li><b>Action naming:</b> make sure you\'re using <code>geo-maps:*</code> / <code>geo-places:*</code> / <code>geo-routes:*</code> (v2), not <code>geo:*</code> (v0 legacy)</li><li><b>Region ARN:</b> the key\'s ARN must be in <code>ap-southeast-1</code> to get GrabMaps</li><li><b>Referer:</b> if restrictions exist, the browser must come from a matching domain</li><li><b>Expiration:</b> check the expiry date</li></ol>',
                faq_q8: '❓ Need custom integration or a POC for your organization?',
                faq_a8: 'We\'re ready to help from POC through production — including custom integration, maps architecture consulting, and integration with your internal systems (WMS, TMS, dispatch, fleet management, etc.). Reach out for a scheduled demo and a discussion around your organization\'s specific needs across any SEA market.',

                cta_title: 'Ready to Power Richer Maps Across Southeast Asia?',
                cta_desc: 'You\'ve finished the tutorial. Now paste your API Key into your app, or explore our full reference.',
                cta_ref: 'Open API Reference',
                cta_demo: 'Try Live Demo',

                footer_text: 'GrabMaps Setup Tutorial · Powered by <a href="https://aws.amazon.com/location/" target="_blank" rel="noopener">AWS Location Service</a> · Provider by <a href="https://developer.grab.com/" target="_blank" rel="noopener">Grab</a>'
            }
        };

        function applyLang(lang) {
            const dict = I18N[lang] || I18N.id;
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

        /* ================= Real screenshot loader (local → fallback → placeholder) ================= */
        document.querySelectorAll('.real-screenshot img').forEach(img => {
            const wrap = img.closest('.real-screenshot');
            let triedFallback = false;
            const onLoaded = () => {
                img.setAttribute('data-loaded', '');
                wrap.classList.add('loaded');
                // Update badge: if using fallback (AWS docs), show that label
                const badge = wrap.querySelector('.rs-badge');
                if (badge && triedFallback) badge.textContent = 'AWS Docs';
            };
            const onError = () => {
                if (!triedFallback && img.dataset.fallback) {
                    triedFallback = true;
                    img.src = img.dataset.fallback;
                } else {
                    wrap.classList.add('failed');
                }
            };
            if (img.complete && img.naturalHeight > 0) {
                onLoaded();
            } else {
                img.addEventListener('load', onLoaded);
                img.addEventListener('error', onError);
            }
        });

        /* ================= Reading progress bar ================= */
        const readProgressBar = document.getElementById('readProgress');
        function updateProgress() {
            const doc = document.documentElement;
            const scrollTop = window.scrollY || doc.scrollTop;
            const scrollHeight = doc.scrollHeight - doc.clientHeight;
            const pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
            readProgressBar.style.width = pct + '%';
        }
        window.addEventListener('scroll', updateProgress, { passive: true });
        updateProgress();

        /* ================= Counter animation on hero ================= */
        function animateCounters() {
            document.querySelectorAll('.hero-stat .num').forEach(el => {
                const target = parseInt(el.dataset.count, 10);
                if (isNaN(target)) return;
                const duration = 1400;
                const start = performance.now();
                function step(now) {
                    const t = Math.min(1, (now - start) / duration);
                    // easeOutQuart
                    const eased = 1 - Math.pow(1 - t, 4);
                    el.textContent = Math.round(target * eased);
                    if (t < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });
        }
        // Trigger after page load
        window.addEventListener('load', () => setTimeout(animateCounters, 300));

        /* ================= Scroll-triggered fade-in ================= */
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
        document.querySelectorAll('.step-section').forEach(el => observer.observe(el));

        /* ================= Sidebar step tracking ================= */
        const stepSections = document.querySelectorAll('.step-section');
        const stepNavItems = document.querySelectorAll('.step-nav-item');
        const navObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    const stepNum = parseInt(id.replace('step-', ''), 10);
                    stepNavItems.forEach((item, idx) => {
                        item.classList.remove('active', 'done');
                        const itemStep = parseInt(item.dataset.step, 10);
                        if (itemStep === stepNum) item.classList.add('active');
                        else if (itemStep < stepNum) item.classList.add('done');
                    });
                }
            });
        }, { rootMargin: '-45% 0px -45% 0px' });
        stepSections.forEach(sec => navObserver.observe(sec));

        /* ================= Copy code button ================= */
        window.copyBlock = function(btn) {
            const pre = btn.parentElement.querySelector('pre');
            if (!pre) return;
            const text = pre.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const original = btn.innerHTML;
                btn.innerHTML = '✓ Copied';
                btn.style.background = 'rgba(0,177,79,0.3)';
                setTimeout(() => {
                    btn.innerHTML = original;
                    btn.style.background = '';
                }, 1500);
            }).catch(() => {
                btn.innerHTML = '❌ Failed';
                setTimeout(() => btn.innerHTML = '📋 Copy', 1500);
            });
        };
    </script>
</body>

</html>
