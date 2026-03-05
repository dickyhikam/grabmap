<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GrabMaps - Address Verification</title>

    <meta name="description" content="GrabMaps is an interactive map platform that provides accurate location information. Verify your address with our geocoding service." />
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

        body.no-map {
            background: linear-gradient(135deg, #f8faf9 0%, #eef5f0 50%, #f0f7f2 100%);
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100vh;
            z-index: var(--z-map);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }

        #map.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* =========================================
           3. FLOATING HEADER
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

        .header-label {
            flex: 1;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: rgba(248, 250, 249, 0.8);
            border-radius: 10px;
        }

        .header-label .bi-patch-check-fill {
            color: var(--grab-green);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .header-label span {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
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

        /* =========================================
           4. LANGUAGE DROPDOWN
           ========================================= */
        .lang-wrapper {
            position: relative;
        }

        .lang-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 60px;
            height: 40px;
            padding: 0 12px;
            border-radius: 10px;
            background: rgba(248, 250, 249, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.06);
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-smooth);
            font-family: inherit;
        }

        .lang-btn:hover {
            background: var(--grab-green-light);
            border-color: rgba(0, 177, 79, 0.2);
            color: var(--grab-green);
        }

        .lang-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-lg);
            padding: 6px;
            min-width: 130px;
            display: none;
            border: 1px solid var(--border-light);
            z-index: 1001;
        }

        .lang-dropdown.show {
            display: block;
            animation: dropdownIn 0.2s ease;
        }

        .lang-option {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            color: var(--text-primary);
        }

        .lang-option:hover {
            background: var(--grab-green-light);
            color: var(--grab-green);
        }

        .lang-option.active {
            background: var(--grab-green-subtle);
            color: var(--grab-green);
            font-weight: 600;
        }

        /* =========================================
           5. ADDRESS CONTAINER & SEARCH PANEL
           ========================================= */
        .address-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 94%;
            max-width: 560px;
            max-height: calc(100vh - 40px);
            z-index: var(--z-panel);
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
            padding-bottom: 20px;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .address-container.sidebar-mode {
            top: 90px;
            left: 24px;
            transform: translate(0, 0);
            width: 380px;
            max-height: calc(100vh - 110px);
        }

        .address-container::-webkit-scrollbar {
            width: 4px;
        }

        .address-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .address-container::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 10px;
        }

        .address-search-panel {
            background: var(--bg-glass-strong);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 24px;
            transition: all var(--transition-smooth);
            animation: slideInPanel 0.4s var(--transition-bounce);
        }

        body.no-map .address-search-panel {
            background: var(--bg-surface);
            box-shadow: var(--shadow-md);
            border-color: var(--border-light);
        }

        .address-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            letter-spacing: -0.01em;
            margin-top: 0;
        }

        .address-textarea-wrap {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(248, 250, 249, 0.8);
            border-radius: var(--radius-md);
            border: 1.5px solid transparent;
            transition: all var(--transition-fast);
        }

        .address-textarea-wrap:focus-within {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 177, 79, 0.2);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.08);
        }

        .address-textarea {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.925rem;
            font-weight: 500;
            color: var(--text-primary);
            font-family: inherit;
            resize: none;
            overflow-y: hidden;
            line-height: 1.5;
            max-height: 150px;
            padding: 0;
        }

        .address-textarea::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .btn-search-main {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: linear-gradient(145deg, var(--grab-green) 0%, #008c3d 100%);
            border: none;
            color: white;
            font-size: 1rem;
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

        /* =========================================
           6. TOGGLE PILLS
           ========================================= */
        .toggle-pills-row {
            display: flex;
            align-items: center;
            margin-top: 14px;
            gap: 8px;
        }

        .helper-hint {
            flex: 1;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .helper-hint b {
            color: var(--text-secondary);
        }

        .toggle-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: 1.5px solid var(--border-light);
            background: var(--bg-subtle);
            color: var(--text-muted);
            user-select: none;
        }

        .toggle-pill.active {
            background: var(--grab-green);
            color: white;
            border-color: var(--grab-green);
            box-shadow: var(--shadow-green);
        }

        .toggle-pill:hover:not(.active) {
            border-color: var(--border-hover);
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.9);
        }

        /* =========================================
           6b. GRID SEARCH PANEL
           ========================================= */
        .grid-search-options {
            display: none;
            margin-top: 12px;
            padding: 14px;
            background: var(--bg-subtle);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            animation: fadeIn 0.25s ease;
        }

        .grid-search-options.show {
            display: block;
        }

        .grid-search-options .grid-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .city-select-wrap {
            position: relative;
            margin-bottom: 10px;
        }

        .city-select {
            width: 100%;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border-light);
            background: var(--bg-surface);
            font-size: 0.82rem;
            font-weight: 500;
            font-family: inherit;
            color: var(--text-primary);
            cursor: pointer;
            transition: all var(--transition-fast);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px;
        }

        .city-select:focus {
            outline: none;
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.08);
        }

        .grid-size-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .grid-size-row label {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
        }

        .grid-size-chips {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .grid-chip {
            padding: 5px 12px;
            border-radius: var(--radius-full);
            font-size: 0.72rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: 1.5px solid var(--border-light);
            background: var(--bg-surface);
            color: var(--text-muted);
            user-select: none;
        }

        .grid-chip.active {
            background: var(--grab-green);
            color: white;
            border-color: var(--grab-green);
            box-shadow: var(--shadow-green);
        }

        .grid-chip:hover:not(.active) {
            border-color: var(--border-hover);
            color: var(--text-secondary);
        }

        .grid-info {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
        }

        .grid-info i {
            font-size: 0.7rem;
        }

        .grid-progress {
            margin-top: 8px;
            display: none;
        }

        .grid-progress.show {
            display: block;
        }

        .grid-progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(0, 177, 79, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .grid-progress-fill {
            height: 100%;
            background: var(--grab-green);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }

        .grid-progress-text {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 4px;
            text-align: center;
        }

        /* =========================================
           7. RESULT CARDS
           ========================================= */
        .results-panel {
            padding: 16px;
        }

        .results-scroll-container {
            max-height: 45vh;
            overflow-y: auto;
            padding: 4px 2px;
        }

        .results-scroll-container::-webkit-scrollbar {
            width: 4px;
        }

        .results-scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .results-scroll-container::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 10px;
        }

        .result-card {
            padding: 14px 16px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: var(--bg-surface);
            border: 1.5px solid transparent;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-smooth);
            cursor: default;
            position: relative;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .result-card.success {
            background: var(--grab-green-subtle);
            border-color: rgba(0, 177, 79, 0.2);
        }

        .result-card.success .icon-box {
            background: var(--grab-green);
            color: white;
        }

        .result-card.error {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .result-card.error .icon-box {
            background: #ef4444;
            color: white;
        }

        .result-card.normal {
            cursor: pointer;
            border-color: var(--border-light);
        }

        .result-card.normal:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-hover);
        }

        .result-card.normal::before {
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

        .result-card.normal:hover::before {
            opacity: 1;
        }

        .result-card.selected {
            border-color: var(--grab-green);
            background: var(--grab-green-subtle);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.08);
        }

        .result-card.selected::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--grab-green);
            opacity: 1;
            border-radius: 0 3px 3px 0;
        }

        .icon-box {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            background: var(--bg-subtle);
            color: var(--text-muted);
        }

        .res-content {
            flex: 1;
            min-width: 0;
        }

        .res-title {
            margin: 0 0 3px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .res-address {
            margin: 0 0 8px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.45;
        }

        .res-meta {
            display: inline-flex;
            gap: 12px;
            font-size: 0.72rem;
            color: var(--text-muted);
            background: var(--bg-subtle);
            padding: 6px 10px;
            border-radius: 6px;
            font-variant-numeric: tabular-nums;
        }

        .res-meta i {
            margin-right: 4px;
        }

        /* =========================================
           8. LOADING STATE
           ========================================= */
        .results-loading {
            text-align: center;
            padding: 32px 20px;
        }

        .results-loading .spinner-border {
            color: var(--grab-green);
            width: 2rem;
            height: 2rem;
        }

        .results-loading p {
            margin-top: 12px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* =========================================
           9. TOAST NOTIFICATIONS
           ========================================= */
        .custom-toast {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            z-index: var(--z-toast);
            min-width: 320px;
            max-width: 400px;
            animation: slideDownToast 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        .custom-toast.hide {
            animation: slideUpToast 0.4s ease forwards;
        }

        @keyframes slideDownToast {
            0% { transform: translateX(-50%) translateY(-100px); opacity: 0; }
            50% { transform: translateX(-50%) translateY(10px); opacity: 0.8; }
            100% { transform: translateX(-50%) translateY(0); opacity: 1; }
        }

        @keyframes slideUpToast {
            0% { transform: translateX(-50%) translateY(0); opacity: 1; }
            100% { transform: translateX(-50%) translateY(-100px); opacity: 0; }
        }

        .toast-content {
            display: flex;
            align-items: center;
            padding: 18px 20px;
            gap: 14px;
            position: relative;
        }

        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
            width: 24px;
            text-align: center;
        }

        .toast-message {
            flex: 1;
            font-size: 15px;
            line-height: 1.4;
            color: #2d3748;
            font-weight: 500;
        }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: currentColor;
            opacity: 0.7;
            animation: progressBar 4s linear forwards;
        }

        @keyframes progressBar {
            from { width: 100%; }
            to { width: 0%; }
        }

        .toast-close {
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
            flex-shrink: 0;
            font-size: 14px;
        }

        .toast-close:hover {
            background: #f7fafc;
            color: #718096;
        }

        .toast-warning {
            border-left: 4px solid #ed8936;
            color: #ed8936;
            background: linear-gradient(135deg, #fffaf0, #fff5eb);
        }

        .toast-error {
            border-left: 4px solid #f56565;
            color: #f56565;
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
        }

        .toast-success {
            border-left: 4px solid #48bb78;
            color: #48bb78;
            background: linear-gradient(135deg, #f0fff4, #c6f6d5);
        }

        .toast-info {
            border-left: 4px solid #4299e1;
            color: #4299e1;
            background: linear-gradient(135deg, #ebf8ff, #bee3f8);
        }

        /* =========================================
           10. ANIMATIONS
           ========================================= */
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

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================================
           11. RESPONSIVE
           ========================================= */
        @media (max-width: 768px) {
            .floating-header {
                width: 96%;
                padding: 8px 10px 8px 14px;
                gap: 10px;
                top: 12px;
            }

            .address-container {
                width: 96%;
            }

            .address-container.sidebar-mode {
                top: 80px;
                left: 50%;
                transform: translateX(-50%);
                width: 96%;
                max-height: calc(100vh - 100px);
            }

            .address-search-panel {
                padding: 16px;
            }

            .address-title {
                font-size: 0.95rem;
            }

            .btn-header-link,
            .lang-btn {
                width: 36px;
                height: 36px;
                min-width: 36px;
            }

            .header-label span {
                font-size: 0.78rem;
            }

            .custom-toast {
                top: 80px;
                left: 20px;
                right: 20px;
                min-width: auto;
                max-width: none;
                transform: translateX(0) translateY(-100px);
            }
        }
    </style>

    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
</head>

<body class="no-map">
    <!-- Full-screen Map (hidden by default, toggled via Maps pill) -->
    <div id="map"></div>

    <!-- Floating Header -->
    <div class="floating-header">
        <div class="logo-container">
            <a href="{{ route('pageHome') }}">
                <img src="logo.png" alt="Grab Logo" class="grab-logo">
            </a>
        </div>

        <div class="header-label">
            <i class="bi bi-patch-check-fill"></i>
            <span>Address Verification</span>
        </div>

        <div class="header-actions">
            <!-- Language -->
            <div class="lang-wrapper">
                <button class="lang-btn" id="languageBtn">
                    <span class="language-text">EN</span>
                    <i class="bi bi-chevron-down" style="font-size: 0.6rem;"></i>
                </button>
                <div class="lang-dropdown" id="languageDropdown">
                    <div class="lang-option active" data-lang="en">English</div>
                    <div class="lang-option" data-lang="id">Indonesia</div>
                </div>
            </div>

            <!-- Reset -->
            <button class="btn-header-link" onclick="resetSearch();" id="btnResetSearch" title="Reset Search">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>

            <!-- Home -->
            <a href="{{ route('pageHome') }}" class="btn-header-link" title="Home">
                <i class="bi bi-house-fill"></i>
            </a>
        </div>
    </div>

    <!-- Address Verification Container -->
    <div class="address-container">
        <!-- Search Panel -->
        <div class="address-search-panel">
            <h2 class="address-title" id="titleVerify">Verify Your Address Here</h2>

            <div class="address-textarea-wrap">
                <textarea id="geminiSearchInput" class="address-textarea" placeholder="Search for address..." rows="1"></textarea>
                <button class="btn-search-main" onclick="performSearch()">
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>

            <div class="toggle-pills-row">
                <div class="helper-hint">
                    <small>Press <b>Enter</b> to search</small>
                </div>
                <div class="toggle-pill" id="toggleMap" onclick="handleToggleMap()">
                    <i class="bi bi-map"></i>
                    <span id="labelBasemap">Maps</span>
                </div>
                <div class="toggle-pill" id="toggleManyData" onclick="handleToggleManyData()">
                    <i class="bi bi-collection"></i>
                    <span id="labelManyData">Many Data</span>
                </div>
            </div>
        </div>

        <!-- Results Panel -->
        <div class="address-search-panel results-panel" id="verificationResult" style="display: none;"></div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('javascript/custom-loading.js') }}"></script>
    <script src="{{ asset('javascript/custom.js') }}"></script>
    <script src="{{ asset('javascript/maps.js') }}"></script>
    <script src="{{ asset('javascript/search-geocode.js') }}"></script>

    <script>
        // ====== CONFIG (from Laravel ENV) ======
        const region = "{{ env('AWS_REGION') }}";
        const mapName = "{{ env('AWS_MAP_NAME') }}";
        const mapPlace = "{{ env('AWS_MAP_PLACE') }}";
        const mapRoute = "{{ env('AWS_MAP_ROUTE') }}";
        const apiKey = "{{ env('AWS_API_KEY') }}";

        let lastSearchStatus = null;
        let lastSearchData = null;
        let lastSelectedData = null;
        let lastManyData = "OFF";

        let map;
        let mapMarkers = [];

        // ====== OVERRIDE showToast to use Bootstrap Icons ======
        window.showToast = function(message, type = "warning") {
            $(".custom-toast").remove();
            const toastClass = type === "warning" ? "toast-warning" : type === "error" ? "toast-error" : type === "success" ? "toast-success" : "toast-info";
            const icon = type === "warning" ? "bi-exclamation-circle-fill" : type === "error" ? "bi-x-circle-fill" : type === "success" ? "bi-check-circle-fill" : "bi-info-circle-fill";
            const toastHTML = '<div class="custom-toast ' + toastClass + '"><div class="toast-content"><div class="toast-icon"><i class="bi ' + icon + '"></i></div><div class="toast-message">' + message + '</div><button class="toast-close" onclick="closeToast()"><i class="bi bi-x"></i></button><div class="toast-progress"></div></div></div>';
            $("body").append(toastHTML);
            setTimeout(function() { closeToast(); }, 4000);
        };

        // ====== OVERRIDE updateContentLanguageAddress for new selectors ======
        window.updateContentLanguageAddress = function(texts, status) {
            $("#titleVerify").text(texts.heroTitleVerify);
            $("#geminiSearchInput").attr("placeholder", texts.searchPlaceholder);
            $(".helper-hint small").html(texts.pressEnterHelper);
            $("#labelBasemap").text(texts.labelBasemap);
            $("#labelManyData").text(texts.labelManyData);
            if (status !== null) {
                renderVerificationResult(status, lastSearchData);
            }
        };

        // ====== INIT ======
        $(document).ready(function() {
            initLanguage();

            const textarea = document.getElementById('geminiSearchInput');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    performSearch();
                }
            });

            // Language dropdown
            document.getElementById('languageBtn').addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('languageDropdown').classList.toggle('show');
            });
            document.querySelectorAll('.lang-option').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    var lang = this.getAttribute('data-lang');
                    document.querySelectorAll('.lang-option').forEach(function(o) { o.classList.remove('active'); });
                    this.classList.add('active');
                    document.querySelector('.language-text').textContent = lang.toUpperCase();
                    document.getElementById('languageDropdown').classList.remove('show');
                    if (typeof changeLanguage === 'function') changeLanguage(lang);
                });
            });
            document.addEventListener('click', function() {
                document.getElementById('languageDropdown').classList.remove('show');
            });
        });

        function initMap() {
            const styleUrl = 'https://maps.geo.' + region + '.amazonaws.com/maps/v0/maps/' + mapName + '/style-descriptor?key=' + apiKey;
            map = new maplibregl.Map({
                container: 'map',
                style: styleUrl,
                center: [106.8456, -6.2088],
                zoom: 12,
                attributionControl: false
            });
            map.addControl(new maplibregl.NavigationControl(), 'bottom-right');
        }

        // ====== TOGGLE HANDLERS ======
        let mapInitialized = false;

        function handleToggleMap() {
            var pill = document.getElementById('toggleMap');
            pill.classList.toggle('active');
            var isActive = pill.classList.contains('active');

            var mapDiv = document.getElementById('map');
            var container = document.querySelector('.address-container');

            if (isActive) {
                document.body.classList.remove('no-map');
                mapDiv.classList.add('active');
                container.classList.add('sidebar-mode');
                if (!mapInitialized) {
                    initMap();
                    mapInitialized = true;
                }
                setTimeout(function() {
                    if (map) map.resize();
                }, 500);
            } else {
                document.body.classList.add('no-map');
                mapDiv.classList.remove('active');
                container.classList.remove('sidebar-mode');
            }
        }

        function handleToggleManyData() {
            var pill = document.getElementById('toggleManyData');
            pill.classList.toggle('active');
            var isActive = pill.classList.contains('active');

            if (isActive) {
                if (lastSearchData && lastSearchStatus === 'success') {
                    renderVerificationResult('success', lastSearchData);
                }
            } else {
                if (lastSelectedData) {
                    renderVerificationResult('success', [lastSelectedData]);
                } else if (lastSearchData && lastSearchData.length > 0) {
                    renderVerificationResult('success', [lastSearchData[0]]);
                }
            }
        }

        // ====== RESET ======
        function resetSearch() {
            var input = document.getElementById('geminiSearchInput');
            if (input) {
                input.value = "";
                input.style.height = "auto";
                input.focus();
            }
            $('#verificationResult').hide().html('');
            lastSearchStatus = null;
            lastSearchData = null;
            lastSelectedData = null;
            if (mapMarkers.length > 0) {
                mapMarkers.forEach(function(m) { m.remove(); });
                mapMarkers = [];
            }
            if (map) {
                map.flyTo({ center: [106.8456, -6.2088], zoom: 12 });
            }
            document.getElementById('toggleManyData').classList.remove('active');
            // Reset map toggle
            document.getElementById('toggleMap').classList.remove('active');
            document.body.classList.add('no-map');
            document.getElementById('map').classList.remove('active');
            document.querySelector('.address-container').classList.remove('sidebar-mode');
        }

        // ====== SEARCH ======
        async function performSearch() {
            var query = $('#geminiSearchInput').val().trim();
            if (!query) return;

            var texts = languageTexts[currentLanguage];
            var resultContainer = $('#verificationResult');

            if (query.length < 10) {
                showToast(texts.textToastAddress, "error");
                return;
            }

            resultContainer.show().html('<div class="results-loading"><div class="spinner-border" role="status"></div><p>' + (texts.searchingAddress || "Searching...") + '</p></div>');

            var resSearch = await searchGeocode(query.toLowerCase(), 5);

            setTimeout(function() {
                if (!resSearch || resSearch.length === 0) {
                    lastSearchStatus = 'error';
                    lastSearchData = null;
                    lastSelectedData = null;
                    renderVerificationResult("error", null);
                } else {
                    lastSearchStatus = 'success';
                    lastSearchData = resSearch;
                    lastSelectedData = resSearch[0];
                    renderVerificationResult("success", resSearch);
                }
            }, 1500);
        }

        // ====== RENDER RESULTS ======
        function renderVerificationResult(status, dataArray) {
            var texts = languageTexts[currentLanguage];
            var html = '';
            var isManyData = document.getElementById('toggleManyData').classList.contains('active');

            if (status === 'success' && dataArray && dataArray.length > 0) {
                if (isManyData) {
                    updateMapMarkers(dataArray);
                } else {
                    var singleData = lastSelectedData ? lastSelectedData : dataArray[0];
                    updateMapMarkers([singleData]);
                }
            }

            // ERROR
            if (status !== 'success' || !dataArray || dataArray.length === 0) {
                html = '<div class="result-card error"><div class="icon-box"><i class="bi bi-x-lg"></i></div><div class="res-content"><h4 class="res-title">' + texts.verifFailedTitle + '</h4><p class="res-address">' + texts.verifFailedDesc + '</p></div></div>';
            }
            // MANY DATA ON
            else if (isManyData) {
                html += '<div class="results-scroll-container">';
                dataArray.forEach(function(result, index) {
                    var place = result.Place;
                    var pt = place && place.Geometry && place.Geometry.Point ? place.Geometry.Point : [0, 0];
                    var displayTitle = place.Label;
                    var displayAddress = place.Label;

                    if (typeof splitLabel === 'function') {
                        var parsed = splitLabel(place.Label);
                        displayTitle = parsed.title || place.Label;
                        displayAddress = parsed.body || "";
                    }

                    var activeClass = 'normal';
                    if (lastSelectedData && result.Place.Geometry.Point[0] === lastSelectedData.Place.Geometry.Point[0] && result.Place.Geometry.Point[1] === lastSelectedData.Place.Geometry.Point[1]) {
                        activeClass = 'selected';
                    }

                    html += '<div id="card-' + index + '" class="result-card ' + activeClass + '" onclick="selectCard(' + index + ', this)"><div class="icon-box"><i class="bi bi-geo-alt-fill"></i></div><div class="res-content"><div style="font-weight:600; font-size:0.85rem; color:var(--text-primary); margin-top:2px;">' + displayTitle + '</div><p class="res-address" style="margin-bottom:5px;">' + displayAddress + '</p><div class="res-meta"><span><i class="bi bi-pin-map-fill"></i> ' + Number(pt[1]).toFixed(5) + ', ' + Number(pt[0]).toFixed(5) + '</span></div></div></div>';
                });
                html += '</div>';
            }
            // SINGLE RESULT
            else {
                var result = dataArray[0];
                var place = result.Place;
                var pt = place && place.Geometry && place.Geometry.Point ? place.Geometry.Point : [0, 0];
                var lon = Number(pt[0]);
                var lat = Number(pt[1]);

                var displayTitle = place.Label;
                var displayAddress = place.Label;

                if (typeof splitLabel === 'function') {
                    var parsed = splitLabel(place.Label);
                    displayTitle = parsed.title || place.Label;
                    displayAddress = parsed.body || "";
                }

                html = '<div class="result-card success"><div class="icon-box"><i class="bi bi-check-lg"></i></div><div class="res-content"><h4 class="res-title" style="color:var(--grab-green); margin-bottom:2px;">' + texts.verifSuccessTitle + '</h4><div style="font-weight:600; font-size:0.85rem; color:var(--text-primary); margin-top:5px;">' + displayTitle + '</div><p class="res-address" style="margin-bottom:10px;">' + displayAddress + '</p><div class="res-meta"><span><i class="bi bi-pin-map-fill"></i> ' + (texts.coordinate || 'Coordinate') + ': ' + lat.toFixed(5) + ', ' + lon.toFixed(5) + '</span></div></div></div>';
            }

            $('#verificationResult').show().html(html);
        }

        // ====== SELECT CARD ======
        function selectCard(index, element) {
            $('.result-card').removeClass('selected').addClass('normal');
            $(element).removeClass('normal').addClass('selected');

            if (lastSearchData && lastSearchData[index]) {
                lastSelectedData = lastSearchData[index];

                if (map && mapInitialized) {
                    var coords = lastSelectedData.Place.Geometry.Point;
                    map.flyTo({
                        center: [coords[0], coords[1]],
                        zoom: 16,
                        speed: 1.2,
                        curve: 1.42,
                        essential: true
                    });

                    if (mapMarkers[index]) {
                        mapMarkers.forEach(function(m) { m.getPopup().remove(); });
                        mapMarkers[index].togglePopup();
                    }
                }
            }
        }

        // ====== MAP MARKERS ======
        function updateMapMarkers(dataArray) {
            if (!map || !mapInitialized) return;

            if (mapMarkers.length > 0) {
                mapMarkers.forEach(function(m) { m.remove(); });
                mapMarkers = [];
            }

            if (!dataArray || dataArray.length === 0) return;

            var bounds = new maplibregl.LngLatBounds();

            dataArray.forEach(function(item) {
                var pt = item.Place && item.Place.Geometry ? item.Place.Geometry.Point : null;
                if (pt) {
                    var lng = pt[0];
                    var lat = pt[1];
                    var marker = new maplibregl.Marker({ color: "#00b14f" })
                        .setLngLat([lng, lat])
                        .setPopup(new maplibregl.Popup().setHTML('<b>' + item.Place.Label + '</b>'))
                        .addTo(map);
                    mapMarkers.push(marker);
                    bounds.extend([lng, lat]);
                }
            });

            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, {
                    padding: 100,
                    maxZoom: 15,
                    duration: 1000
                });
            }
        }
    </script>
</body>

</html>