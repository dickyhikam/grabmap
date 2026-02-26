<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AWS Grab Maps</title>

    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* =========================================
       1. VARIABLES & CONFIGURATION
       ========================================= */
        :root {
            --grab-green: #00B14F;
            --grab-green-hover: #009543;
            --grab-green-light: #e8faf0;
            --grab-green-subtle: #f0fdf4;
            --text-primary: #1a1a2e;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --bg-glass: rgba(255, 255, 255, 0.88);
            --bg-glass-strong: rgba(255, 255, 255, 0.95);
            --bg-surface: #ffffff;
            --bg-subtle: #f8fafc;
            --border-light: rgba(0, 0, 0, 0.06);
            --border-hover: rgba(0, 177, 79, 0.3);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.06);
            --shadow-green: 0 4px 14px rgba(0, 177, 79, 0.2);
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 28px;
            --radius-full: 9999px;
            --header-max-width: 540px;
            --btn-size: 42px;
            --z-map: 1;
            --z-header: 1000;
            --z-panel: 1050;
            --z-toast: 9999;
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-smooth: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* =========================================
       2. BASE LAYOUT & MAP
       ========================================= */
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100vh;
            z-index: var(--z-map);
        }

        .maplibregl-ctrl-top-left {
            margin-top: 80px;
        }


        /* =========================================
       3. FLOATING HEADER (SEARCH BAR) - Modern Redesign
       ========================================= */
        .floating-header {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 94%;
            max-width: 580px;
            z-index: var(--z-header);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 252, 250, 0.92) 100%);
            backdrop-filter: blur(24px) saturate(200%);
            -webkit-backdrop-filter: blur(24px) saturate(200%);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.9);
            padding: 10px 12px 10px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all var(--transition-smooth);
        }

        .floating-header:focus-within {
            box-shadow: 0 8px 32px rgba(0, 177, 79, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06), 0 0 0 2px rgba(0, 177, 79, 0.2);
            border-color: rgba(0, 177, 79, 0.25);
        }

        .logo-container {
            display: flex;
            align-items: center;
            padding-right: 14px;
            margin-right: 2px;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }

        .grab-logo {
            height: 26px;
            width: auto;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.06));
        }

        .search-wrapper {
            position: relative;
            flex-grow: 1;
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .search-input-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 8px 12px;
            background: rgba(248, 250, 249, 0.8);
            border-radius: 10px;
            border: 1px solid transparent;
            transition: all var(--transition-fast);
        }

        .floating-header:focus-within .search-input-wrap {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 177, 79, 0.15);
        }

        .search-input-wrap .bi-search {
            color: var(--text-muted);
            font-size: 0.95rem;
            flex-shrink: 0;
            transition: color var(--transition-fast);
        }

        .floating-header:focus-within .search-input-wrap .bi-search {
            color: var(--grab-green);
        }

        .search-input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 0.925rem;
            font-weight: 500;
            color: var(--text-primary);
            padding: 0;
            font-family: inherit;
            min-width: 0;
        }

        .search-input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-search-main {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: linear-gradient(145deg, var(--grab-green) 0%, #008c3d 100%);
            border: none;
            color: white;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all var(--transition-smooth);
            box-shadow: 0 4px 14px rgba(0, 177, 79, 0.35);
        }

        .btn-search-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 177, 79, 0.4);
            background: linear-gradient(145deg, #00c257 0%, var(--grab-green) 100%);
        }

        .btn-search-main:active {
            transform: translateY(0);
        }

        .btn-header-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(248, 250, 249, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.06);
            color: var(--text-secondary);
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-smooth);
            flex-shrink: 0;
            text-decoration: none;
        }

        .btn-header-link:hover {
            background: var(--grab-green-light);
            border-color: rgba(0, 177, 79, 0.2);
            color: var(--grab-green);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 177, 79, 0.15);
        }

        .btn-header-link.btn-header-link--pricing:hover {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.2);
        }

        .btn-header-link.btn-header-link--tester:hover {
            color: #6366f1;
            background: rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        /* Suggestions Dropdown */
        .suggestions-list {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
            list-style: none;
            padding: 6px;
            margin: 0;
            display: none;
            max-height: 320px;
            overflow-y: auto;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .suggestions-list.show {
            display: block;
            animation: dropdownIn 0.2s var(--transition-bounce);
        }

        @keyframes dropdownIn {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .suggestion-item {
            padding: 10px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            color: var(--text-primary);
            transition: all var(--transition-fast);
        }

        .suggestion-item i {
            color: var(--text-muted);
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .suggestion-item:hover {
            background-color: var(--grab-green-light);
            color: var(--grab-green);
        }

        .suggestion-item:hover i {
            color: var(--grab-green);
        }

        /* =========================================
       4. PANEL LAYOUT (LEFT SIDEBAR)
       ========================================= */
        .locations-panel {
            position: fixed;
            top: 80px;
            left: 16px;
            width: 340px;
            max-height: calc(100vh - 100px);
            display: none;
            flex-direction: column;
            background: var(--bg-glass);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            z-index: var(--z-panel);
            overflow: hidden;
            animation: slideInPanel 0.4s var(--transition-bounce);
        }

        @keyframes slideInPanel {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .panel-header {
            flex-shrink: 0;
            padding: 20px 20px 0 20px;
            background: rgba(255, 255, 255, 0.6);
            z-index: 10;
            position: sticky;
            top: 0;
        }

        .panel-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .panel-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title h6 {
            margin: 0;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .panel-body {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 16px 16px 16px !important;
        }

        .panel-body::-webkit-scrollbar {
            width: 4px;
        }

        .panel-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .panel-body::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 10px;
        }

        .panel-body::-webkit-scrollbar-thumb:hover {
            background: #a1a1aa;
        }

        /* =========================================
       5. TABS NAVIGATION
       ========================================= */
        .panel-tabs {
            display: flex;
            background: var(--bg-subtle);
            border-radius: var(--radius-sm);
            padding: 3px;
            margin-bottom: 4px;
            flex-shrink: 0;
        }

        .tab-item {
            flex: 1;
            text-align: center;
            padding: 8px 8px;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
            border-radius: 6px;
            border-bottom: none;
            transition: all var(--transition-fast);
            user-select: none;
        }

        .tab-item:hover {
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.5);
        }

        .tab-item.active {
            color: var(--grab-green);
            background: var(--bg-surface);
            box-shadow: var(--shadow-sm);
        }

        .tab-pane {
            display: none;
            animation: fadeInTab 0.2s ease;
        }

        .tab-pane.active {
            display: block;
        }

        @keyframes fadeInTab {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* =========================================
       6. SCROLLABLE LIST CONTAINERS
       ========================================= */
        #listContainer,
        #segmentListContainer {
            padding: 8px 2px;
        }

        /* =========================================
       7. LIST ITEMS & CARDS (COMPONENTS)
       ========================================= */

        /* A. Location Item (Tab 1) */
        .location-item {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            border: 1.5px solid transparent;
            transition: all var(--transition-smooth);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .location-item.active {
            border-color: var(--grab-green);
            background: var(--grab-green-subtle);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.08);
        }

        .location-item.active .loc-coord i {
            color: var(--grab-green);
        }

        .location-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--grab-green);
            opacity: 0;
            transition: opacity var(--transition-fast);
            border-radius: 0 3px 3px 0;
        }

        .location-item:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }

        .location-item:hover::before {
            opacity: 1;
        }

        .loc-info {
            flex-grow: 1;
            padding-left: 6px;
            padding-right: 10px;
            min-width: 0;
        }

        .loc-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
            display: block;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .loc-coord {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
            font-variant-numeric: tabular-nums;
        }

        /* B. Segment Card (Tab 2 - Route Detail) */
        .segment-card {
            background: var(--bg-surface);
            border: 1.5px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 12px 12px 12px 18px;
            margin-bottom: 8px;
            position: relative;
            transition: all var(--transition-smooth);
            cursor: pointer;
        }

        .segment-card:hover {
            background: var(--bg-subtle);
            border-color: var(--border-hover);
            box-shadow: var(--shadow-sm);
        }

        .segment-card.active-card {
            background-color: var(--grab-green-subtle);
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.08);
        }

        .segment-color-bar {
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 3px;
            border-radius: 0 3px 3px 0;
        }

        .segment-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .segment-details {
            font-size: 0.74rem;
            color: var(--text-secondary);
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .segment-icon {
            font-size: 0.7rem;
            margin-right: 3px;
        }

        /* C. Result Summary Card */
        .route-result-card {
            background: linear-gradient(135deg, var(--grab-green-light) 0%, #dcfce7 100%);
            border: 1.5px solid rgba(0, 177, 79, 0.2);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-top: 12px;
            display: none;
            animation: fadeInTab 0.3s ease;
        }

        .route-stat-box {
            text-align: center;
            flex: 1;
        }

        .route-label {
            font-size: 0.68rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .route-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--grab-green);
            letter-spacing: -0.02em;
        }

        .route-divider {
            width: 1.5px;
            background-color: rgba(0, 177, 79, 0.15);
            margin: 0 8px;
            align-self: stretch;
        }

        /* =========================================
       8. BUTTONS
       ========================================= */

        .btn-circle {
            width: var(--btn-size);
            height: var(--btn-size);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all var(--transition-fast);
        }

        .btn-grab {
            background-color: var(--grab-green);
            border-color: var(--grab-green);
            color: white;
        }

        .btn-grab:hover {
            background-color: var(--grab-green-hover);
            border-color: var(--grab-green-hover);
            color: white;
        }

        /* Primary Action */
        .btn-action-primary {
            background: linear-gradient(135deg, #00B14F 0%, #009543 100%);
            color: white;
            border: none;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.02em;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
            box-shadow: var(--shadow-green);
            position: relative;
            overflow: hidden;
        }

        .btn-action-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 177, 79, 0.3);
            color: white;
        }

        .btn-action-primary:active {
            transform: translateY(1px);
            box-shadow: 0 2px 6px rgba(0, 177, 79, 0.2);
        }

        /* Secondary Action */
        .btn-action-secondary {
            background: var(--bg-surface);
            color: var(--grab-green);
            border: 1.5px solid rgba(0, 177, 79, 0.3);
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.02em;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }

        .btn-action-secondary:hover {
            background: var(--grab-green-subtle);
            border-color: var(--grab-green);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.12);
        }

        .btn-action-secondary:active {
            transform: translateY(1px);
        }

        /* Reset Button */
        .btn-reset-minimal {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-subtle);
            border: 1px solid var(--border-light);
            padding: 5px 12px;
            border-radius: var(--radius-full);
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .btn-reset-minimal:hover {
            color: #ef4444;
            border-color: #fecaca;
            background: #fef2f2;
        }

        /* Help Button */
        .btn-help-minimal {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            padding: 0;
            line-height: 1;
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .btn-help-minimal:hover {
            color: var(--grab-green);
            transform: scale(1.15);
        }

        /* Delete Item Button */
        .btn-delete-item {
            color: var(--text-muted);
            background: var(--bg-subtle);
            border: 1px solid var(--border-light);
            width: 30px;
            height: 30px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-fast);
            cursor: pointer;
            flex-shrink: 0;
            font-size: 0.75rem;
        }

        .btn-delete-item:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            transform: scale(1.05);
        }

        /* Travel Mode Switch */
        .mode-switch-container {
            background-color: var(--bg-subtle);
            padding: 3px;
            border-radius: var(--radius-sm);
            display: flex;
            border: 1px solid var(--border-light);
        }

        .btn-mode-switch {
            background: transparent;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.78rem;
            border: none;
            padding: 7px 8px;
            border-radius: 6px;
            transition: all var(--transition-smooth);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-mode-switch:hover {
            color: var(--text-secondary);
        }

        .btn-check:checked+.btn-mode-switch {
            background-color: var(--bg-surface);
            color: var(--grab-green);
            box-shadow: var(--shadow-sm);
        }

        /* =========================================
       9. UTILITIES (Toast & Badges)
       ========================================= */
        .toast-container {
            z-index: var(--z-toast) !important;
        }

        .badge-count {
            background: var(--grab-green);
            color: white;
            min-width: 18px;
            height: 18px;
            border-radius: var(--radius-full);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            font-weight: 700;
            margin-left: 5px;
            line-height: 1;
        }

        /* =========================================
       10. MODAL & INFO STYLE
       ========================================= */
        .modal-content-pro {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header-pro {
            background: linear-gradient(135deg, #00B14F 0%, #00963f 50%, #007a33 100%);
            color: white;
            padding: 24px 28px;
            border-bottom: none;
        }

        .modal-body-pro {
            padding: 24px;
            background: var(--bg-subtle);
        }

        .info-section {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            padding: 14px;
            margin-bottom: 10px;
            border: 1px solid var(--border-light);
            display: flex;
            gap: 14px;
            transition: all var(--transition-smooth);
        }

        .info-section:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
            border-color: var(--border-hover);
        }

        .info-icon-box {
            width: 38px;
            height: 38px;
            background: var(--grab-green-light);
            color: var(--grab-green);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .info-content h6 {
            margin: 0 0 3px 0;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .info-content p {
            margin: 0;
            font-size: 0.82rem;
            color: var(--text-secondary);
            line-height: 1.45;
        }

        .modal-footer-pro {
            background: var(--bg-surface);
            padding: 16px 24px;
            border-top: 1px solid var(--border-light);
        }

        /* =========================================
       11. POI HIGHLIGHT MARKERS
       ========================================= */
        .poi-marker {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 0.7rem;
            font-family: 'Inter', sans-serif;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            transition: transform var(--transition-fast);
            animation: poiBounce 0.4s var(--transition-bounce);
        }

        .poi-marker:hover {
            transform: scale(1.15);
        }

        @keyframes poiBounce {
            from {
                transform: scale(0) translateY(-10px);
                opacity: 0;
            }

            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        /* =========================================
       12. RESPONSIVE
       ========================================= */
        @media (max-width: 768px) {
            .locations-panel {
                width: calc(100% - 32px);
                left: 16px;
                top: 76px;
                max-height: 50vh;
            }

            .floating-header {
                width: 96%;
                padding: 8px 10px 8px 14px;
                gap: 10px;
            }

            .btn-search-main, .btn-header-link {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>

<body>

    <!-- Kalau mau 100% hidden dari Network tab juga, kita bisa proxy semua tile/sprite/glyph lewat backend. Tapi ada trade-off:
    Pros: API key benar-benar tidak terlihat di browser
    Cons: Semua tile request lewat server PHP dulu → lebih lambat, server load naik (setiap scroll/zoom peta = puluhan request tile)
    Mau saya implementasi full proxy untuk tile/sprite/glyph juga, atau cukup yang sekarang? (API key sudah aman dari view-source dan bot crawler, hanya terlihat kalau user buka DevTools Network tab) -->

    <div class="floating-header">
        <div class="logo-container">
            <img src="logo.png" alt="Grab Logo" class="grab-logo">
        </div>

        <div class="search-wrapper">
            <div class="search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" placeholder="Search a place..." id="searchInput">
            </div>
            <ul class="suggestions-list" id="suggestionsList"></ul>
        </div>

        <div class="header-actions">
            <button class="btn-search-main" type="button" onclick="handleManualSearch()" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <a href="{{ route('pricing') }}" class="btn-header-link btn-header-link--pricing" title="Pricing Comparison">
                <i class="bi bi-tag-fill"></i>
            </a>
            <a href="{{ route('pageRouteTester') }}" class="btn-header-link btn-header-link--tester" title="Tester API">
                <i class="bi bi-code-slash"></i>
            </a>
        </div>
    </div>

    <div class="locations-panel" id="locationsPanel">
        <div class="panel-header">
            <div class="panel-title-row">
                <div class="panel-title">
                    <h6>Location Manager</h6>
                    <button class="btn-help-minimal" data-bs-toggle="modal" data-bs-target="#helpModal" title="Guide & Information">
                        <i class="bi bi-question-circle-fill"></i>
                    </button>
                </div>
                <button class="btn-reset-minimal" onclick="clearAllMarkers()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>

            <div class="mode-switch-container mb-2">
                <input type="radio" class="btn-check" name="travelMode" id="modeCar" value="Car" checked>
                <label class="btn-mode-switch flex-grow-1" for="modeCar"><i class="bi bi-car-front-fill me-1"></i> Car</label>
                <input type="radio" class="btn-check" name="travelMode" id="modeBike" value="Motorcycle">
                <label class="btn-mode-switch flex-grow-1" for="modeBike"><i class="bi bi-scooter me-1"></i> Motorcycle</label>
            </div>

            <div class="mode-switch-container mb-3">
                <input type="radio" class="btn-check" name="optMode" id="optFast" value="fast" checked>
                <label class="btn-mode-switch flex-grow-1" for="optFast" title="Sort by direct distance (Faster)">
                    <i class="bi bi-rulers me-1"></i> Straight Line
                </label>
                <input type="radio" class="btn-check" name="optMode" id="optPrecise" value="real">
                <label class="btn-mode-switch flex-grow-1" for="optPrecise" title="Sort by actual driving route (More Accurate)">
                    <i class="bi bi-sign-turn-slight-right-fill me-1"></i> Real Road
                </label>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-action-primary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateRoute()" title="Hitung Rute A ke B">
                    <i class="bi bi-sign-turn-right-fill me-2"></i> A&rarr;B
                </button>
                <button class="btn btn-action-secondary flex-grow-1 d-flex align-items-center justify-content-center py-2" onclick="calculateMultiRoute()" title="Hitung Rute Multi-Stop">
                    <i class="bi bi-diagram-3-fill me-2"></i> Multi
                </button>
            </div>

            <div class="panel-tabs">
                <div class="tab-item active" onclick="switchTab('locations')" id="tabBtn-locations">
                    Locations <span class="badge-count ms-1" id="locCount">0</span>
                </div>
                <div class="tab-item" onclick="switchTab('routes')" id="tabBtn-routes">
                    Route Details
                </div>
            </div>
        </div>

        <div class="panel-body px-3 pb-3">

            <div id="tabPane-locations" class="tab-pane active">
                <div id="listContainer"></div>

                <div id="emptyState" class="text-center mt-4" style="font-size: 0.82rem;">
                    <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <i class="bi bi-pin-map-fill" style="font-size: 1.3rem; color: var(--text-muted);"></i>
                    </div>
                    <p class="mb-1" style="font-weight: 600; color: var(--text-secondary);">No locations yet</p>
                    <p style="color: var(--text-muted); font-size: 0.75rem;">Click on the map or search to add</p>
                </div>
            </div>

            <div id="tabPane-routes" class="tab-pane">

                <div id="routeResultCard" class="route-result-card mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="route-stat-box">
                            <div class="route-label"><i class="bi bi-rulers"></i> Distance</div>
                            <div class="route-value" id="resDistance">-</div>
                        </div>
                        <div class="route-divider"></div>
                        <div class="route-stat-box">
                            <div class="route-label"><i class="bi bi-stopwatch"></i> Duration</div>
                            <div class="route-value" id="resDuration">-</div>
                        </div>
                    </div>
                </div>

                <div id="segmentListContainer" style="display: none;">
                </div>

                <div id="routeEmptyState" class="text-center mt-5">
                    <div style="width: 56px; height: 56px; border-radius: 16px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i class="bi bi-map" style="font-size: 1.5rem; color: var(--text-muted);"></i>
                    </div>
                    <p style="font-size: 0.88rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">No route yet</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Add locations then press Calculate</p>
                </div>

            </div>

        </div>
    </div>

    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-content-pro">
                <div class="modal-header modal-header-pro">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-book-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" style="font-size: 1.05rem;">Features & Guide</h5>
                            <small style="opacity: 0.8; font-size: 0.75rem;">Everything you need to know</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body modal-body-pro">

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">Basic Controls</p>
                    <div class="bg-white p-3 rounded-3 border mb-4" style="border-color: var(--border-light) !important;">
                        <div class="row g-3 text-center">
                            <div class="col-4 border-end">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #fef2f2; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                </div>
                                <div class="small fw-bold text-dark">Add</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Click Map / Search</div>
                            </div>
                            <div class="col-4 border-end">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-arrows-move text-primary"></i>
                                </div>
                                <div class="small fw-bold text-dark">Move</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Drag Marker</div>
                            </div>
                            <div class="col-4">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--bg-subtle); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                                    <i class="bi bi-x-circle text-secondary"></i>
                                </div>
                                <div class="small fw-bold text-dark">Remove</div>
                                <div style="font-size: 0.68rem; color: var(--text-muted);">Click 'X' in List</div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">1. Optimization Methods</p>
                    <div class="p-3 bg-white rounded-3 border mb-3" style="border-color: var(--border-light) !important;">
                        <table class="table table-borderless table-sm small mb-0">
                            <thead class="border-bottom" style="color: var(--text-muted);">
                                <tr>
                                    <th class="pb-2">Feature</th>
                                    <th class="pb-2 text-center text-primary">Straight Line</th>
                                    <th class="pb-2 text-center text-success">Real Road</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);">Accuracy</td>
                                    <td class="py-2 text-center">Low (Flight)</td>
                                    <td class="py-2 text-center">High (Traffic)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 fw-semibold" style="color: var(--text-secondary);">Best For</td>
                                    <td class="py-2 text-center">Estimates</td>
                                    <td class="py-2 text-center">Delivery</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="mt-2 pt-2 border-top d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill text-warning mt-1"></i>
                            <p class="small mb-0" style="font-size: 0.73rem; color: var(--text-secondary);">
                                <strong>Tip:</strong> Use "Straight Line" to list points quickly, then "Real Road" to finalize.
                            </p>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">2. Travel Modes</p>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-car-front-fill" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark">Car</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);">Standard Routes</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white d-flex align-items-center gap-2" style="border-color: var(--border-light) !important;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--grab-green-light); display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-scooter" style="color: var(--grab-green);"></i>
                                </div>
                                <div style="line-height: 1.2;">
                                    <div class="small fw-bold text-dark">Motorcycle</div>
                                    <div style="font-size: 0.63rem; color: var(--text-muted);">Faster ETA</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase fw-bold small mb-2 ms-1" style="font-size: 0.68rem; color: var(--text-muted); letter-spacing: 1px;">3. Calculation Actions</p>

                    <div class="info-section py-2 mb-2">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-sign-turn-right-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Single Route (A&rarr;B)</h6>
                            <p style="font-size: 0.78rem;">Direct path from the first to the second location only.</p>
                        </div>
                    </div>

                    <div class="info-section py-2 mb-0">
                        <div class="info-icon-box" style="background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; font-size: 1rem;">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div class="info-content">
                            <h6 style="font-size: 0.88rem;">Multi-Stop (Optimized)</h6>
                            <p style="font-size: 0.78rem;">Automatically <b>reorders</b> all stops to find the most efficient path.</p>
                        </div>
                    </div>

                </div>

                <div class="modal-footer-pro text-center">
                    <button type="button" class="btn btn-action-primary w-100 py-2" data-bs-dismiss="modal">
                        Got it, thanks!
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="map"></div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <script>
        /* =========================================
       1. CONFIGURATION & GLOBAL STATE
       ========================================= */
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Helper for POST requests to our proxy
        function proxyPost(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            });
        }

        // Helper for GET requests to our proxy
        function proxyGet(url) {
            return fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });
        }

        let map = null;
        let markersData = [];
        let selectedMarkerId = null;
        let highlightMarkers = [];


        /* =========================================
           2. UI UTILITIES (Toast & Tabs)
           ========================================= */
        function showToast(title, message, type = 'info') {
            const container = document.getElementById('toastContainer');
            let bgClass, iconClass;

            switch (type) {
                case 'success':
                    bgClass = 'text-bg-success';
                    iconClass = 'bi-check-circle-fill';
                    break;
                case 'error':
                    bgClass = 'text-bg-danger';
                    iconClass = 'bi-exclamation-triangle-fill';
                    break;
                case 'warning':
                    bgClass = 'text-bg-warning';
                    iconClass = 'bi-exclamation-circle-fill';
                    break;
                default:
                    bgClass = 'text-bg-primary';
                    iconClass = 'bi-info-circle-fill';
            }

            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
            <div class="toast align-items-start ${bgClass} border-0 mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body text-white">
                        <i class="${iconClass} me-2 fs-5"></i>
                        <strong>${title}</strong>
                        <div class="mt-1 small">${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;

            const toastElement = wrapper.firstElementChild;
            container.appendChild(toastElement);

            requestAnimationFrame(() => {
                try {
                    const t = new bootstrap.Toast(toastElement, {
                        autohide: false
                    });
                    t.show();
                    setTimeout(() => {
                        if (toastElement && document.body.contains(toastElement)) t.hide();
                    }, 5000);
                    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
                } catch (error) {
                    console.error("Failed init toast:", error);
                    toastElement.remove();
                }
            });
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));

            document.getElementById(`tabBtn-${tabName}`).classList.add('active');
            document.getElementById(`tabPane-${tabName}`).classList.add('active');
        }


        /* =========================================
           3. MAP INITIALIZATION
           ========================================= */
        function initMap() {
            map = new maplibregl.Map({
                container: 'map',
                style: '/api/map-style',
                center: [106.8456, -6.2088],
                zoom: 13,
                attributionControl: false
            });

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.AttributionControl({
                customAttribution: '© Grab, © AWS'
            }), 'bottom-right');

            // Click map to add location
            map.on('click', async (e) => {
                const coords = [e.lngLat.lng, e.lngLat.lat];
                addLocation(coords, "Loading address...");
                const currentId = selectedMarkerId;

                try {
                    const addressName = await getPlaceNameByCoords(coords);
                    if (addressName) {
                        const item = markersData.find(m => m.id === currentId);
                        if (item) {
                            item.name = addressName;
                            item.marker.setPopup(new maplibregl.Popup({
                                offset: 25
                            }).setText(addressName));
                            renderLocationList();
                            showToast('Location Found', addressName, 'success');
                        }
                    } else {
                        const item = markersData.find(m => m.id === currentId);
                        if (item) {
                            item.name = `Location (${coords[1].toFixed(4)}, ${coords[0].toFixed(4)})`;
                            renderLocationList();
                        }
                    }
                } catch (error) {
                    console.error(error);
                }
            });
        }


        /* =========================================
           4. LOCATION MANAGEMENT (CRUD)
           ========================================= */
        function addLocation(coords, label) {
            const id = Date.now();
            const newMarker = new maplibregl.Marker({
                    color: '#00B14F',
                    draggable: true
                })
                .setLngLat(coords)
                .setPopup(new maplibregl.Popup({
                    offset: 25
                }).setText(label))
                .addTo(map);

            newMarker.togglePopup();

            // Drag Event Handler
            newMarker.on('dragend', async () => {
                const lngLat = newMarker.getLngLat();
                const updatedCoords = [lngLat.lng, lngLat.lat];
                const item = markersData.find(m => m.id === id);

                if (item) {
                    item.coords = updatedCoords;
                    showToast('Loading...', 'Finding new address...', 'info');

                    const newName = await getPlaceNameByCoords(updatedCoords);
                    if (newName) {
                        item.name = newName;
                        newMarker.setPopup(new maplibregl.Popup({
                            offset: 25
                        }).setText(newName));
                        renderLocationList();
                        showToast('Location Updated', newName, 'success');
                    } else {
                        showToast('Info', 'Location name not found.', 'warning');
                    }
                }
            });

            selectedMarkerId = id;
            markersData.push({
                id,
                marker: newMarker,
                name: label,
                coords
            });
            renderLocationList();
            map.flyTo({
                center: coords,
                zoom: 15
            });
        }

        function removeLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) item.marker.remove();
            markersData = markersData.filter(m => m.id !== id);
            renderLocationList();
        }

        function clearAllMarkers() {
            markersData.forEach(m => m.marker.remove());
            markersData = [];
            selectedMarkerId = null;

            removeRouteLayer();
            renderLocationList();

            // Reset Route UI
            document.getElementById('routeResultCard').style.display = 'none';
            document.getElementById('segmentListContainer').style.display = 'none';
            document.getElementById('segmentListContainer').innerHTML = '';
            document.getElementById('routeEmptyState').style.display = 'block';

            switchTab('locations');
            showToast('Reset', 'All markers and route cleared.', 'info');

            document.getElementById('locationsPanel').style.display = 'none';
        }

        function zoomToLocation(id) {
            const item = markersData.find(m => m.id === id);
            if (item) {
                selectedMarkerId = id;
                map.flyTo({
                    center: item.coords,
                    zoom: 17
                });
                item.marker.togglePopup();
                renderLocationList();
            }
        }

        async function getPlaceNameByCoords(coords) {
            try {
                const response = await proxyPost('/api/places/reverse', {
                    Position: coords,
                    MaxResults: 1,
                    Language: 'en'
                });

                if (!response.ok) throw new Error('API Error');
                const data = await response.json();

                if (data.Results && data.Results.length > 0) {
                    return data.Results[0].Place.Label;
                }
                return null;
            } catch (error) {
                console.error("Reverse geocode failed:", error);
                return null;
            }
        }


        /* =========================================
           5. ROUTING LOGIC
           ========================================= */

        // --- Single Route (A -> B) ---
        async function calculateRoute() {
            if (markersData.length < 2) return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');

            const origin = markersData[0].coords;
            const destination = markersData[1].coords;
            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            showToast('Processing...', 'Calculating single route...', 'info');

            try {
                const response = await proxyPost('/api/routes/calculate', {
                    DeparturePosition: origin,
                    DestinationPosition: destination,
                    TravelMode: selectedMode,
                    DistanceUnit: "Kilometers",
                    DepartNow: true,
                    IncludeLegGeometry: true
                });
                if (!response.ok) throw new Error('Failed');
                const data = await response.json();

                if (data.Legs && data.Legs.length > 0 && data.Legs[0].Geometry) {
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': [{
                            'type': 'Feature',
                            'properties': {
                                'color': '#00B14F'
                            },
                            'geometry': {
                                'type': 'LineString',
                                'coordinates': data.Legs[0].Geometry.LineString
                            }
                        }]
                    };

                    drawRouteOnMap(featureCollection);

                    // UI Updates
                    const summary = data.Summary;
                    document.getElementById('resDistance').innerText = summary.Distance.toFixed(1) + ' km';
                    document.getElementById('resDuration').innerText = Math.round(summary.DurationSeconds / 60) + ' min';

                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    switchTab('routes');
                } else {
                    showToast('Error', 'Path not found.', 'error');
                }
            } catch (e) {
                console.error(e);
                showToast('Error', 'Failed.', 'error');
            }
        }

        async function calculateMultiRoute() {
            if (markersData.length < 2) return showToast('Insufficient Data', 'Add at least 2 locations.', 'warning');

            const selectedMode = document.querySelector('input[name="travelMode"]:checked').value;

            //Ambil mode optimasi (Fast / Precise)
            const optimizationMode = document.querySelector('input[name="optMode"]:checked').value;

            const colors = ['#00B14F', '#007bff', '#dc3545', '#fd7e14', '#6f42c1', '#e83e8c', '#17a2b8'];
            const MAX_STOPS = 25;

            // --- STEP 1: LOGIKA PEMILIHAN OPTIMASI ---
            let optimizedData = [];

            if (optimizationMode === 'real') {
                // A. Mode Precise (Real Road)
                showToast('Optimizing...', 'Analyzing traffic & road restrictions...', 'info');
                try {
                    optimizedData = await optimizeMarkersOrderReal([...markersData]);
                } catch (e) {
                    console.error(e);
                    showToast('Warning', 'Optimization failed, fallback to default.', 'warning');
                    optimizedData = [...markersData];
                }
            } else {
                // B. Mode Fast (Straight Line)
                showToast('Optimizing...', 'Reordering stops (Straight Line)...', 'info');
                optimizedData = optimizeMarkersOrder([...markersData]);
            }

            // Update data global & UI List
            markersData = optimizedData;
            renderLocationList();
            const workingData = markersData;
            // ------------------------------------------

            let totalDistance = 0;
            let totalDuration = 0;
            let allRouteFeatures = [];
            let globalLegIndex = 0;
            let segmentDetails = [];

            showToast('Processing...', `Calculating final route path...`, 'info');

            try {
                // Loop Batching (Sama seperti sebelumnya)
                for (let i = 0; i < workingData.length - 1; i += (MAX_STOPS - 1)) {
                    const chunk = workingData.slice(i, i + MAX_STOPS);
                    const origin = chunk[0].coords;
                    const destination = chunk[chunk.length - 1].coords;
                    const waypoints = chunk.length > 2 ? chunk.slice(1, -1).map(m => m.coords) : [];

                    const response = await proxyPost('/api/routes/calculate', {
                        DeparturePosition: origin,
                        DestinationPosition: destination,
                        WaypointPositions: waypoints,
                        TravelMode: selectedMode,
                        DistanceUnit: "Kilometers",
                        DepartNow: true,
                        IncludeLegGeometry: true
                    });

                    if (!response.ok) throw new Error(`Batch error`);
                    const data = await response.json();

                    totalDistance += data.Summary.Distance;
                    totalDuration += data.Summary.DurationSeconds;

                    if (data.Legs && data.Legs.length > 0) {
                        data.Legs.forEach((leg, legIndexInBatch) => {
                            if (leg.Geometry && leg.Geometry.LineString) {
                                const segmentColor = colors[globalLegIndex % colors.length];

                                allRouteFeatures.push({
                                    'type': 'Feature',
                                    'properties': {
                                        'color': segmentColor
                                    },
                                    'geometry': {
                                        'type': 'LineString',
                                        'coordinates': leg.Geometry.LineString
                                    }
                                });

                                const startNode = workingData[i + legIndexInBatch];
                                const endNode = workingData[i + legIndexInBatch + 1];

                                segmentDetails.push({
                                    from: startNode.name || 'Unknown Point',
                                    to: endNode.name || 'Unknown Point',
                                    distance: leg.Distance,
                                    duration: leg.DurationSeconds,
                                    color: segmentColor,
                                    geometry: leg.Geometry.LineString
                                });

                                globalLegIndex++;
                            }
                        });
                    }
                }

                // Render Results
                if (allRouteFeatures.length > 0) {
                    const featureCollection = {
                        'type': 'FeatureCollection',
                        'features': allRouteFeatures
                    };
                    drawRouteOnMap(featureCollection);

                    const finalDist = totalDistance.toFixed(1) + ' km';
                    const finalDur = formatDuration(totalDuration);

                    document.getElementById('resDistance').innerText = finalDist;
                    document.getElementById('resDuration').innerText = finalDur;
                    document.getElementById('routeEmptyState').style.display = 'none';
                    document.getElementById('routeResultCard').style.display = 'block';
                    document.getElementById('segmentListContainer').style.display = 'block';

                    renderSegmentList(segmentDetails);
                    switchTab('routes');
                    showToast('Success', `Optimized route calculated!`, 'success');
                } else {
                    showToast('Error', 'Route geometry missing.', 'error');
                }

            } catch (error) {
                console.error(error);
                showToast('Error', 'Failed to calculate route.', 'error');
            }
        }

        // --- HELPER: MENGHITUNG JARAK ANTARA 2 KOORDINAT (Haversine) ---
        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            var R = 6371; // Radius bumi dalam km
            var dLat = deg2rad(lat2 - lat1);
            var dLon = deg2rad(lon2 - lon1);
            var a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var d = R * c; // Jarak dalam km
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // --- FUNGSI OPTIMASI URUTAN (Nearest Neighbor) ---
        function optimizeMarkersOrder(originalData) {
            if (originalData.length <= 2) return originalData; // Kalau cuma 2 titik, gak perlu diurutkan

            // 1. Ambil titik awal (Fixed, tidak boleh pindah)
            let sorted = [originalData[0]];

            // 2. Sisa titik yang belum dikunjungi
            let remaining = originalData.slice(1);

            // 3. Looping cari yang terdekat
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1]; // Titik terakhir yang sudah fix
                let nearestIndex = -1;
                let minDistance = Infinity;

                // Bandingkan jarak ke semua sisa titik
                remaining.forEach((point, index) => {
                    // Ingat: coords[1] = lat, coords[0] = lng
                    let dist = getDistanceFromLatLonInKm(
                        current.coords[1], current.coords[0],
                        point.coords[1], point.coords[0]
                    );

                    if (dist < minDistance) {
                        minDistance = dist;
                        nearestIndex = index;
                    }
                });

                // Pindahkan titik terdekat ke array sorted
                sorted.push(remaining[nearestIndex]);
                // Hapus dari remaining
                remaining.splice(nearestIndex, 1);
            }

            return sorted;
        }

        // --- HELPER: PANGGIL AWS MATRIX (REAL ROAD DISTANCE) ---
        async function getRouteMatrix(departure, destinations) {
            const response = await proxyPost('/api/routes/matrix', {
                DeparturePositions: [departure],
                DestinationPositions: destinations,
                TravelMode: "Car",
                DistanceUnit: "Kilometers"
            });

            if (!response.ok) throw new Error("Matrix API Error");
            return await response.json();
        }

        // --- LOGIKA PENGURUTAN REAL (ASYNC / PRECISE) ---
        async function optimizeMarkersOrderReal(originalData) {
            if (originalData.length <= 2) return originalData;

            // 1. Mulai dari titik pertama (Start Fixed)
            let sorted = [originalData[0]];
            let remaining = originalData.slice(1);

            // 2. Loop sampai semua titik masuk rute
            while (remaining.length > 0) {
                let current = sorted[sorted.length - 1];

                // Update Toast biar user tau prosesnya
                showToast('Optimizing...', `Checking roads from Stop ${sorted.length}...`, 'info');

                // Siapkan koordinat
                const currentCoords = current.coords;
                const destCoords = remaining.map(m => m.coords);

                try {
                    // PANGGIL API MATRIX
                    const matrixData = await getRouteMatrix(currentCoords, destCoords);

                    // AWS Matrix mengembalikan array "RouteMatrix[0]" (karena 1 origin)
                    const results = matrixData.RouteMatrix[0];

                    let bestIndex = -1;
                    let minDuration = Infinity; // Cari WAKTU tercepat

                    results.forEach((res, idx) => {
                        if (res && res.DurationSeconds !== undefined) { // Cek validitas
                            if (res.DurationSeconds < minDuration) {
                                minDuration = res.DurationSeconds;
                                bestIndex = idx;
                            }
                        }
                    });

                    if (bestIndex !== -1) {
                        sorted.push(remaining[bestIndex]);
                        remaining.splice(bestIndex, 1);
                    } else {
                        // Fallback jika API gagal kalkulasi rute (misal beda pulau)
                        sorted.push(remaining[0]);
                        remaining.shift();
                    }

                } catch (err) {
                    console.error("Matrix Optimization Failed:", err);
                    // Jika error (misal internet putus), kembalikan sisa apa adanya
                    return sorted.concat(remaining);
                }
            }
            return sorted;
        }


        /* =========================================
           6. VISUALIZATION & HELPERS
           ========================================= */
        function drawRouteOnMap(geoJsonFeatureCollection) {
            removeRouteLayer();

            map.addSource('routeSource', {
                'type': 'geojson',
                'data': geoJsonFeatureCollection
            });

            // Layer Outline (White)
            map.addLayer({
                'id': 'routeLayerOutline',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#ffffff',
                    'line-width': 6,
                    'line-opacity': 0.8
                }
            });

            // Main Layer (Colorful)
            map.addLayer({
                'id': 'routeLayer',
                'type': 'line',
                'source': 'routeSource',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': ['get', 'color'],
                    'line-width': 4,
                    'line-opacity': 0.9
                }
            });

            const bounds = new maplibregl.LngLatBounds();
            geoJsonFeatureCollection.features.forEach(feature => {
                feature.geometry.coordinates.forEach(coord => bounds.extend(coord));
            });

            map.fitBounds(bounds, {
                padding: 50
            });
        }

        function removeRouteLayer() {
            // Clear highlight first
            clearSegmentHighlight();

            if (map.getLayer('routeLayer')) map.removeLayer('routeLayer');
            if (map.getLayer('routeLayerOutline')) map.removeLayer('routeLayerOutline');
            if (map.getSource('routeSource')) map.removeSource('routeSource');
        }

        function zoomToSegment(coordinates) {
            if (!coordinates || coordinates.length === 0) return;
            const bounds = new maplibregl.LngLatBounds();
            coordinates.forEach(coord => bounds.extend(coord));
            map.fitBounds(bounds, {
                padding: 100,
                duration: 1000
            });
        }

        function highlightSegment(seg) {
            clearSegmentHighlight();

            // 1. Dim all routes
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.25);
                map.setPaintProperty('routeLayer', 'line-width', 3);
            }
            if (map.getLayer('routeLayerOutline')) {
                map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.15);
            }

            // 2. Add highlight layers for selected segment
            map.addSource('highlightSource', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    properties: {
                        color: seg.color
                    },
                    geometry: {
                        type: 'LineString',
                        coordinates: seg.geometry
                    }
                }
            });

            // Glow effect
            map.addLayer({
                id: 'highlightGlow',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 12,
                    'line-opacity': 0.2
                }
            });

            // White outline
            map.addLayer({
                id: 'highlightOutline',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#ffffff',
                    'line-width': 7,
                    'line-opacity': 0.9
                }
            });

            // Main highlight line
            map.addLayer({
                id: 'highlightLine',
                type: 'line',
                source: 'highlightSource',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': ['get', 'color'],
                    'line-width': 5,
                    'line-opacity': 1
                }
            });

            // 3. Add POI markers at start and end
            const startCoord = seg.geometry[0];
            const endCoord = seg.geometry[seg.geometry.length - 1];

            const startMarker = new maplibregl.Marker({
                    element: createPOIElement('A', seg.color)
                })
                .setLngLat(startCoord)
                .setPopup(new maplibregl.Popup({
                    offset: 20,
                    closeButton: false
                }).setHTML(
                    `<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">Start</strong><br>${seg.from}</div>`
                ))
                .addTo(map);

            const endMarker = new maplibregl.Marker({
                    element: createPOIElement('B', seg.color)
                })
                .setLngLat(endCoord)
                .setPopup(new maplibregl.Popup({
                    offset: 20,
                    closeButton: false
                }).setHTML(
                    `<div style="font-family:Inter,sans-serif;font-size:0.8rem;"><strong style="color:${seg.color};">End</strong><br>${seg.to}</div>`
                ))
                .addTo(map);

            startMarker.togglePopup();
            endMarker.togglePopup();
            highlightMarkers.push(startMarker, endMarker);

            // 4. Zoom to segment
            zoomToSegment(seg.geometry);
        }

        function clearSegmentHighlight() {
            // Remove highlight layers
            ['highlightLine', 'highlightOutline', 'highlightGlow'].forEach(id => {
                if (map.getLayer(id)) map.removeLayer(id);
            });
            if (map.getSource('highlightSource')) map.removeSource('highlightSource');

            // Restore main route opacity
            if (map.getLayer('routeLayer')) {
                map.setPaintProperty('routeLayer', 'line-opacity', 0.9);
                map.setPaintProperty('routeLayer', 'line-width', 4);
            }
            if (map.getLayer('routeLayerOutline')) {
                map.setPaintProperty('routeLayerOutline', 'line-opacity', 0.8);
            }

            // Remove POI markers
            highlightMarkers.forEach(m => m.remove());
            highlightMarkers = [];
        }

        function createPOIElement(label, color) {
            const el = document.createElement('div');
            el.className = 'poi-marker';
            el.style.backgroundColor = color;
            el.textContent = label;
            return el;
        }

        function formatDuration(seconds) {
            const totalMinutes = Math.round(seconds / 60);
            if (totalMinutes >= 60) {
                const hrs = Math.floor(totalMinutes / 60);
                const mins = totalMinutes % 60;
                return `${hrs} hr ${mins} min`;
            }
            return `${totalMinutes} min`;
        }


        /* =========================================
           7. UI RENDERING (LISTS)
           ========================================= */
        function renderLocationList() {
            const panel = document.getElementById('locationsPanel');
            const container = document.getElementById('listContainer');
            const countBadge = document.getElementById('locCount');
            const emptyState = document.getElementById('emptyState');

            panel.style.display = 'flex';
            countBadge.innerText = markersData.length;

            if (markersData.length === 0) {
                emptyState.style.display = 'block';
                container.innerHTML = '';
            } else {
                emptyState.style.display = 'none';
                container.innerHTML = '';
                markersData.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'location-item';
                    if (item.id === selectedMarkerId) div.classList.add('active');

                    div.style.animation = `slideInPanel 0.3s ease forwards ${index * 0.05}s`;
                    const lat = item.coords[1].toFixed(5);
                    const lng = item.coords[0].toFixed(5);

                    div.innerHTML = `
                    <div class="loc-info" onclick="zoomToLocation(${item.id})">
                        <span class="loc-name text-truncate" title="${item.name}">${item.name}</span>
                        <span class="loc-coord"><i class="bi bi-crosshair"></i> ${lat}, ${lng}</span>
                    </div>
                    <button class="btn-delete-item shadow-sm" onclick="event.stopPropagation(); removeLocation(${item.id})">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                    container.appendChild(div);
                });
            }
        }

        function renderSegmentList(details) {
            const container = document.getElementById('segmentListContainer');
            container.innerHTML = '';
            container.style.display = 'block';

            details.forEach((seg, index) => {
                const dist = seg.distance.toFixed(1) + ' km';
                const dur = formatDuration(seg.duration);

                const item = document.createElement('div');
                item.className = 'segment-card';
                item.style.cursor = 'pointer';

                item.onclick = () => {
                    const isActive = item.classList.contains('active-card');
                    document.querySelectorAll('.segment-card').forEach(el => el.classList.remove('active-card'));

                    if (isActive) {
                        clearSegmentHighlight();
                    } else {
                        item.classList.add('active-card');
                        highlightSegment(seg);
                    }
                };

                item.innerHTML = `
                <div class="segment-color-bar" style="background-color: ${seg.color};"></div>
                <div class="d-flex flex-column">
                    <div class="segment-title">
                        <span class="text-truncate" style="max-width: 240px;">
                            <span class="badge rounded-pill text-bg-light border me-1">${index + 1}</span>
                            ${seg.to}
                        </span>
                    </div>
                    <div class="segment-details">
                        <span><i class="bi bi-rulers segment-icon"></i> ${dist}</span>
                        <span class="border-start mx-2"></span>
                        <span><i class="bi bi-clock segment-icon"></i> ${dur}</span>
                    </div>
                    <div style="font-size: 0.7rem; color: #999; margin-top: 2px;">
                        From: ${seg.from}
                    </div>
                </div>
            `;
                container.appendChild(item);
            });
        }


        /* =========================================
           8. SEARCH FUNCTIONALITY
           ========================================= */
        const input = document.getElementById('searchInput');
        const list = document.getElementById('suggestionsList');

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        input.addEventListener('input', debounce(async (e) => {
            const query = e.target.value;
            if (query.length < 3) {
                list.classList.remove('show');
                return;
            }
            try {
                const res = await proxyPost('/api/places/suggestions', {
                    Text: query,
                    MaxResults: 5,
                    Language: 'en'
                });
                const data = await res.json();
                renderSuggestions(data.Results);
            } catch (err) {
                console.error(err);
            }
        }, 300));

        function renderSuggestions(results) {
            list.innerHTML = '';
            if (!results || results.length === 0) {
                list.classList.remove('show');
                return;
            }
            results.forEach(item => {
                const li = document.createElement('li');
                li.className = 'suggestion-item';
                li.innerHTML = `<i class="bi bi-geo-alt"></i> ${item.Text}`;
                li.onclick = () => selectPlace(item.PlaceId, item.Text);
                list.appendChild(li);
            });
            list.classList.add('show');
        }

        async function selectPlace(placeId, placeName) {
            list.classList.remove('show');
            input.value = '';

            try {
                const res = await proxyGet(`/api/places/${placeId}`);
                const data = await res.json();
                addLocation(data.Place.Geometry.Point, data.Place.Label);
                showToast('Added', placeName, 'success');
            } catch (err) {
                showToast('Failed', 'Cannot fetch location', 'error');
            }
        }

        async function handleManualSearch() {
            const query = input.value;
            if (!query) return showToast('Empty Search', 'Enter a keyword.', 'warning');
            list.classList.remove('show');

            try {
                const res = await proxyPost('/api/places/search', {
                    Text: query,
                    MaxResults: 1
                });
                const data = await res.json();

                if (data.Results && data.Results.length > 0) {
                    const place = data.Results[0].Place;
                    addLocation(place.Geometry.Point, place.Label);
                    showToast('Found', place.Label, 'success');
                } else {
                    showToast('Not Found', 'Try another keyword.', 'warning');
                }
            } catch (err) {
                showToast('Error', 'API search failed.', 'error');
            }
        }

        /* =========================================
           9. INITIALIZATION & EVENTS
           ========================================= */

        function setupEventListeners() {
            // 1. Close suggestion list when clicking outside
            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !list.contains(e.target)) {
                    list.classList.remove('show');
                }
            });

            // 2. Handle Enter key on Search Input
            input.addEventListener("keypress", (event) => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    handleManualSearch();
                }
            });
        }

        // --- MAIN BOOTSTRAP ---
        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            setupEventListeners();
        });
    </script>
</body>

</html>