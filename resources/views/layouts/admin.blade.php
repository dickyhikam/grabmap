<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') | GrabMaps</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @stack('css')
    <style>
        :root {
            --grab-green: #00B14F;
            --grab-green-light: #e8f8ef;
            --grab-green-dark: #009640;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
        }

        .navbar-brand img {
            height: 28px;
        }

        /* Navbar */
        .admin-navbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0;
            height: 60px;
        }

        .admin-navbar .container {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .admin-navbar .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-right: 32px;
        }

        .admin-navbar .navbar-brand img {
            height: 26px;
        }

        .admin-navbar .brand-text {
            font-size: 0.88rem;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: -0.01em;
        }

        .admin-navbar .brand-badge {
            font-size: 0.62rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 2px 7px;
            border-radius: 4px;
            background: var(--grab-green-light);
            color: var(--grab-green-dark);
        }

        .nav-tabs-admin {
            display: flex;
            align-items: center;
            gap: 4px;
            height: 100%;
        }

        .nav-tab-item {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 100%;
            padding: 0 16px;
            font-size: 0.84rem;
            font-weight: 500;
            color: #6c757d;
            text-decoration: none;
            border-bottom: 2.5px solid transparent;
            transition: all 0.15s;
        }

        .nav-tab-item:hover {
            color: #1a1a2e;
            border-bottom-color: #e2e8f0;
        }

        .nav-tab-item.active {
            color: var(--grab-green);
            border-bottom-color: var(--grab-green);
        }

        .nav-tab-item i {
            font-size: 1rem;
        }

        .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-right .nav-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1rem;
            transition: all 0.15s;
            text-decoration: none;
        }

        .nav-right .nav-icon:hover {
            background: #f0f2f5;
            color: #1a1a2e;
        }

        .lang-switcher {
            display: flex;
            align-items: center;
            background: #f0f2f5;
            border-radius: 8px;
            padding: 2px;
            gap: 2px;
        }
        .lang-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.15s;
            line-height: 1.4;
        }
        .lang-btn:hover { color: #1a1a2e; }
        .lang-btn.active {
            background: #fff;
            color: #1a1a2e;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        /* Legacy compat */
        .nav-link-admin {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.15s;
        }

        .nav-link-admin:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link-admin.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }

        /* Shared card style */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        /* Buttons */
        .btn-grab {
            background: var(--grab-green);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .btn-grab:hover {
            background: var(--grab-green-dark);
            color: #fff;
        }

        .btn-outline-grab {
            border: 1.5px solid var(--grab-green);
            color: var(--grab-green);
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.85rem;
            background: transparent;
        }

        .btn-outline-grab:hover {
            background: var(--grab-green-light);
            color: var(--grab-green-dark);
        }

        /* Table */
        .table th {
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
        }

        /* Stat cards */
        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card .stat-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
            margin-bottom: 4px;
        }

        /* Dark header */
        .page-header {
            background: linear-gradient(135deg, #0a2e1a 0%, #0d4a28 40%, var(--grab-green-dark) 100%);
            padding: 2rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 177, 79, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .page-header * {
            position: relative;
            z-index: 1;
        }

        .page-header h4,
        .page-header small,
        .page-header a {
            color: #fff;
        }

        .page-header a.back-link {
            color: rgba(255, 255, 255, 0.5);
            text-decoration: none;
            font-size: 0.82rem;
            transition: color 0.15s;
        }

        .page-header a.back-link:hover {
            color: #fff;
        }

        .content-area {
            margin-top: -1.5rem;
            position: relative;
            z-index: 2;
        }

        /* Chart bar */
        .chart-bar {
            display: flex;
            align-items: flex-end;
            gap: 2px;
            height: 200px;
        }

        .chart-bar .bar-wrapper {
            flex: 1;
            height: 100%;
            display: flex;
            align-items: flex-end;
            position: relative;
        }

        .chart-bar .bar {
            width: 100%;
            background: linear-gradient(180deg, var(--grab-green) 0%, #34d876 100%);
            border-radius: 4px 4px 0 0;
            min-height: 2px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .chart-bar .bar:hover {
            background: linear-gradient(180deg, var(--grab-green-dark) 0%, var(--grab-green) 100%);
            transform: scaleY(1.02);
            transform-origin: bottom;
        }

        .chart-bar .bar:hover .bar-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(-4px);
        }

        .bar-tooltip {
            opacity: 0;
            visibility: hidden;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(0);
            background: #1a1a2e;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            transition: all 0.15s;
            z-index: 10;
            pointer-events: none;
        }

        .bar-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid transparent;
            border-top-color: #1a1a2e;
        }

        /* Operation breakdown bars */
        .op-bar-track {
            flex: 1;
            height: 8px;
            background: #f0f2f5;
            border-radius: 4px;
            overflow: hidden;
        }

        .op-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--grab-green), #34d876);
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* Empty state */
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: #ced4da;
            margin-bottom: 12px;
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 16px;
        }

        .modal .form-select,
        .modal .form-control {
            border-radius: 10px;
            border-color: #e2e8f0;
        }

        .modal .form-select:focus,
        .modal .form-control:focus {
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.1);
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            border: none;
        }

        @stack('styles')
    </style>
</head>

<body>

    <nav class="admin-navbar">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('logo2.png') }}" alt="Grab">
                <span class="brand-text">{{ __('admin.brand') }}</span>
                <span class="brand-badge">{{ __('admin.admin') }}</span>
            </a>

            <div class="nav-tabs-admin">
                <a href="{{ route('admin.dashboard') }}"
                    class="nav-tab-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> {{ __('admin.dashboard') }}
                </a>
                <a href="{{ route('admin.companies.index') }}"
                    class="nav-tab-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}">
                    <i class="bi bi-building"></i> {{ __('admin.companies') }}
                </a>
                <a href="{{ route('admin.api-keys.index') }}"
                    class="nav-tab-item {{ request()->routeIs('admin.api-keys.*') ? 'active' : '' }}">
                    <i class="bi bi-key"></i> {{ __('admin.api_keys') }}
                </a>
            </div>

            <div class="nav-right">
                <div class="lang-switcher">
                    <a href="{{ route('admin.language', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    <a href="{{ route('admin.language', 'id') }}" class="lang-btn {{ app()->getLocale() === 'id' ? 'active' : '' }}">ID</a>
                </div>
                <a href="{{ url('/') }}" class="nav-icon" title="{{ __('admin.view_homepage') }}" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
    </nav>

    @yield('header')

    <div class="{{ $__env->hasSection('header') ? 'container content-area pb-5' : 'container py-4' }}">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>