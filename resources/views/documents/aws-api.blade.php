<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AWS Location Service — GrabMaps Provider</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">

    {{-- MapLibre untuk live preview di Maps panels --}}
    <link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>

    <style>
        :root {
            --grab-green: #00B14F;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --bg-page: #f8fafc;
            --bg-surface: #ffffff;
            --bg-sidebar: #ffffff;
            --border-light: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-primary);
            background: var(--bg-page);
            font-size: 14px;
            overflow: hidden;
        }

        /* Top bar */
        .topbar {
            height: 56px;
            background: linear-gradient(90deg, #00B14F 0%, #00d65a 100%);
            color: #fff;
            padding: 0 24px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            z-index: 10;
            position: relative;
        }

        .topbar .brand {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.2px;
        }

        .topbar .brand small {
            opacity: 0.85;
            font-weight: 500;
            margin-left: 8px;
            font-size: 0.78rem;
        }

        .topbar .search-box {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.82rem;
            width: 240px;
        }

        .topbar .search-box::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .topbar .search-box:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.3);
        }

        /* Language toggle */
        .lang-toggle {
            display: flex;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            margin-left: 10px;
            overflow: hidden;
        }

        .lang-toggle button {
            background: transparent;
            border: 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.74rem;
            font-weight: 700;
            padding: 5px 10px;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all 0.12s;
        }

        .lang-toggle button:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .lang-toggle button.active {
            background: #fff;
            color: #00B14F;
        }

        /* Layout */
        .layout {
            display: flex;
            height: calc(100vh - 56px);
        }

        /* Sidebar */
        .sidebar {
            width: 320px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-light);
            overflow-y: auto;
            padding: 16px 0;
            flex-shrink: 0;
        }

        .sidebar .service-group {
            margin-bottom: 4px;
        }

        .sidebar .service-header {
            width: 100%;
            text-align: left;
            background: transparent;
            border: 0;
            padding: 8px 18px;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.86rem;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: background 0.12s;
        }

        .sidebar .service-header:hover {
            background: #f1f5f9;
        }

        .sidebar .service-header .caret {
            font-size: 0.7rem;
            color: var(--text-muted);
            transition: transform 0.15s;
        }

        .sidebar .service-group.collapsed .caret {
            transform: rotate(-90deg);
        }

        .sidebar .service-group.collapsed .operations {
            display: none;
        }

        .sidebar .operations {
            list-style: none;
            padding: 0;
            margin: 0 0 8px;
        }

        .sidebar .op-link {
            display: block;
            padding: 6px 18px 6px 36px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.82rem;
            border-left: 2px solid transparent;
            cursor: pointer;
            transition: all 0.12s;
        }

        .sidebar .op-link:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .sidebar .op-link.active {
            color: var(--grab-green);
            background: #ecfdf5;
            border-left-color: var(--grab-green);
            font-weight: 600;
        }

        .sidebar .op-link .badge-v0 {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 0.62rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 6px;
            letter-spacing: 0.5px;
        }

        .sidebar .op-link .badge-new {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 0.62rem;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            margin-left: 6px;
            letter-spacing: 0.5px;
        }

        .sidebar .op-link .badge-soon {
            display: inline-block;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 10px;
            margin-left: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Availability dot — depan op name */
        .sidebar .op-link .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
            flex-shrink: 0;
        }

        .sidebar .op-link .dot.ok {
            background: #10b981;
            box-shadow: 0 0 0 2px #d1fae5;
        }

        .sidebar .op-link .dot.no {
            background: #cbd5e1;
        }

        .sidebar .op-link.unavail {
            color: #94a3b8;
        }

        .sidebar .op-link.unavail:hover {
            color: #64748b;
        }

        /* Legend bawah sidebar */
        .sidebar .legend {
            margin-top: 16px;
            padding: 12px 18px;
            border-top: 1px solid var(--border-light);
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .sidebar .legend .row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .sidebar .legend .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .sidebar .legend .dot.ok {
            background: #10b981;
            box-shadow: 0 0 0 2px #d1fae5;
        }

        .sidebar .legend .dot.no {
            background: #cbd5e1;
        }

        /* Main content */
        .main {
            flex: 1;
            overflow-y: auto;
            padding: 28px 36px 80px;
        }

        .op-panel {
            display: none;
            max-width: 960px;
        }

        .op-panel.active {
            display: block;
        }

        .op-panel .breadcrumb-mini {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .op-panel h1 {
            font-weight: 800;
            font-size: 1.7rem;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
        }

        .op-panel .op-desc {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 22px;
            line-height: 1.55;
        }

        .op-panel h3 {
            font-weight: 700;
            font-size: 1rem;
            margin: 24px 0 10px;
            color: var(--text-primary);
        }

        .op-panel h4 {
            font-weight: 600;
            font-size: 0.86rem;
            margin: 16px 0 6px;
            color: var(--text-secondary);
        }

        /* Endpoint pill */
        .endpoint-line {
            background: #1e293b;
            color: #e2e8f0;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', 'Menlo', monospace;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            overflow-x: auto;
        }

        .endpoint-line .method {
            background: #f59e0b;
            color: #1e293b;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.72rem;
            flex-shrink: 0;
        }

        .endpoint-line .method.GET {
            background: #10b981;
            color: #fff;
        }

        .endpoint-line .method.POST {
            background: #3b82f6;
            color: #fff;
        }

        .endpoint-line .method.PUT {
            background: #f59e0b;
            color: #fff;
        }

        .endpoint-line .method.DELETE {
            background: #ef4444;
            color: #fff;
        }

        code {
            background: #f1f5f9;
            color: #d23a3a;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85em;
            font-family: 'JetBrains Mono', 'Menlo', monospace;
        }

        pre {
            background: #1e293b !important;
            color: #f1f5f9;
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 0.8rem;
            line-height: 1.55;
            overflow-x: auto;
            margin: 0 0 14px;
        }

        pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }

        /* v0 / v2 tabs */
        .ver-tabs {
            border-bottom: 2px solid var(--border-light);
            margin-bottom: 14px;
            display: flex;
            gap: 4px;
        }

        .ver-tabs button {
            background: transparent;
            border: 0;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            padding: 8px 16px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.86rem;
            cursor: pointer;
            transition: all 0.12s;
        }

        .ver-tabs button:hover {
            color: var(--text-primary);
            background: #f8fafc;
        }

        .ver-tabs button.active[data-version="v2"] {
            color: var(--grab-green);
            border-bottom-color: var(--grab-green);
        }

        .ver-tabs button.active[data-version="v0"] {
            color: #ef4444;
            border-bottom-color: #ef4444;
        }

        .ver-content>div {
            display: none;
        }

        .ver-content>div.active {
            display: block;
        }

        /* Info boxes */
        .alert-mini {
            font-size: 0.84rem;
            padding: 10px 14px;
            border-radius: 6px;
            margin: 12px 0;
        }

        .alert-mini.warn {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
        }

        .alert-mini.info {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
        }

        .alert-mini.success {
            background: #ecfdf5;
            border-left: 3px solid #10b981;
        }

        .alert-mini.danger {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
        }

        .alert-mini.soon {
            background: linear-gradient(90deg, #fff7ed 0%, #fffbeb 100%);
            border-left: 3px solid #f59e0b;
            color: #78350f;
        }

        .alert-mini.soon .soon-pill {
            display: inline-block;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            margin-right: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        /* Section heading dengan icon */
        .doc-section-h {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
            margin: 28px 0 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--border-light);
        }

        .doc-section-h .ic {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #f0fdf4;
            color: var(--grab-green);
            font-size: 0.86rem;
        }

        .doc-section-h .ic.purple {
            background: #faf5ff;
            color: #9333ea;
        }

        .doc-section-h .ic.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .doc-section-h .ic.orange {
            background: #fff7ed;
            color: #f59e0b;
        }

        /* Live demo form */
        .demo-form {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 16px;
        }

        .demo-form .row-form {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .demo-form label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .demo-form label .req {
            color: #ef4444;
            margin-left: 3px;
        }

        .demo-form input,
        .demo-form select {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.84rem;
            font-family: inherit;
            background: #fff;
        }

        .demo-form .row-form-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .demo-form .btn-run {
            background: linear-gradient(90deg, #00B14F, #00d65a);
            color: #fff;
            border: 0;
            padding: 8px 22px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.86rem;
            cursor: pointer;
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.12s;
        }

        .demo-form .btn-run:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3);
        }

        .demo-form .btn-run:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        .demo-output {
            background: #1e293b;
            color: #f1f5f9;
            padding: 14px 16px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.55;
            max-height: 420px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .demo-output.empty {
            color: #64748b;
            font-style: italic;
            padding: 40px;
            text-align: center;
        }

        .demo-output.error {
            background: #450a0a;
            color: #fecaca;
        }

        .demo-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            margin-left: 6px;
        }

        .demo-status.ok {
            background: #dcfce7;
            color: #166534;
        }

        .demo-status.bad {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Try It Live — Postman-style 2-column layout */
        .try-it {
            border: 1px solid var(--border-light);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            margin-bottom: 16px;
        }

        .try-it-grid {
            display: grid;
            grid-template-columns: minmax(300px, 1fr) minmax(300px, 1.3fr);
        }

        @media (max-width: 900px) {
            .try-it-grid {
                grid-template-columns: 1fr;
            }
        }

        .try-it-pane {
            padding: 16px 18px;
        }

        .try-it-pane.left {
            background: #f8fafc;
            border-right: 1px solid var(--border-light);
        }

        .try-it-pane.right {
            background: #1e293b;
        }

        @media (max-width: 900px) {
            .try-it-pane.left {
                border-right: 0;
                border-bottom: 1px solid var(--border-light);
            }
        }

        .try-it-pane-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: 12px;
        }

        .try-it-pane.left .try-it-pane-header {
            color: var(--text-muted);
        }

        .try-it-pane.right .try-it-pane-header {
            color: #94a3b8;
        }

        .try-it-pane.right .try-it-method {
            display: inline-block;
            background: #3b82f6;
            color: #fff;
            padding: 1px 7px;
            border-radius: 3px;
            font-size: 0.65rem;
            margin-right: 6px;
        }

        .try-it-pane.right .try-it-url {
            color: #cbd5e1;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.74rem;
            margin-bottom: 10px;
            word-break: break-all;
        }

        .try-it-body {
            background: #0f172a;
            color: #f1f5f9;
            padding: 12px 14px;
            border-radius: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.55;
            max-height: 320px;
            overflow: auto;
            white-space: pre-wrap;
        }

        .try-it form .field {
            margin-bottom: 12px;
        }

        .try-it form label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .try-it form label .req {
            color: #ef4444;
            margin-left: 3px;
        }

        .try-it form input,
        .try-it form select {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.86rem;
            font-family: inherit;
            background: #fff;
            transition: border-color 0.12s;
        }

        .try-it form input:focus,
        .try-it form select:focus {
            outline: 0;
            border-color: var(--grab-green);
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.12);
        }

        .try-it form .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .try-it .send-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        .try-it .btn-send {
            background: linear-gradient(90deg, #00B14F, #00d65a);
            color: #fff;
            border: 0;
            padding: 9px 22px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.86rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.12s;
        }

        .try-it .btn-send:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3);
        }

        .try-it .btn-send:disabled {
            opacity: 0.6;
            cursor: wait;
            transform: none;
            box-shadow: none;
        }

        .try-it .btn-copy {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.12s;
        }

        .try-it .btn-copy:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        /* Response section */
        .resp-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: 8px 8px 0 0;
            border-bottom: 0;
            font-size: 0.78rem;
        }

        .resp-bar .status-pill {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 0.74rem;
        }

        .resp-bar .status-pill.ok {
            background: #dcfce7;
            color: #15803d;
        }

        .resp-bar .status-pill.bad {
            background: #fee2e2;
            color: #b91c1c;
        }

        .resp-bar .status-pill.idle {
            background: #e2e8f0;
            color: #64748b;
        }

        .resp-bar .meta {
            color: var(--text-muted);
            font-size: 0.74rem;
        }

        .resp-bar .meta b {
            color: var(--text-primary);
            font-weight: 700;
        }

        .resp-body {
            background: #1e293b;
            color: #f1f5f9;
            padding: 14px 16px;
            border-radius: 0 0 8px 8px;
            border: 1px solid var(--border-light);
            border-top: 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.55;
            max-height: 460px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 16px;
        }

        .resp-body.empty {
            color: #64748b;
            font-style: italic;
            padding: 36px;
            text-align: center;
            background: #1e293b;
        }

        .resp-body.error {
            background: #450a0a;
            color: #fecaca;
        }

        /* Preset buttons row */
        .preset-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
            padding: 10px 14px;
            background: #f1f5f9;
            border-radius: 8px;
            border: 1px solid var(--border-light);
        }

        .preset-row .preset-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            margin-right: 4px;
        }

        .preset-btn {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: var(--text-secondary);
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 0.74rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.12s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .preset-btn:hover {
            background: var(--grab-green);
            color: #fff;
            border-color: var(--grab-green);
        }

        .preset-btn .pico {
            font-size: 0.72rem;
            opacity: 0.7;
        }

        /* Editable JSON textarea */
        .json-editor {
            width: 100%;
            min-height: 320px;
            background: #0f172a;
            color: #f1f5f9;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 12px 14px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.55;
            resize: vertical;
            outline: none;
            tab-size: 2;
            white-space: pre;
            overflow-wrap: normal;
            overflow-x: auto;
        }

        .json-editor:focus {
            border-color: var(--grab-green);
            box-shadow: 0 0 0 2px rgba(0, 177, 79, 0.2);
        }

        .json-editor.invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
        }

        .json-status {
            display: inline-block;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 3px;
            margin-left: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .json-status.ok {
            background: #16a34a;
            color: #fff;
        }

        .json-status.invalid {
            background: #dc2626;
            color: #fff;
        }

        /* Field rules card */
        .rules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .rule-card {
            border: 1px solid var(--border-light);
            border-radius: 10px;
            padding: 14px 16px;
            background: #fff;
        }

        .rule-card .rule-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .rule-card .rule-header .ic {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 5px;
            font-size: 0.78rem;
        }

        .rule-card.required {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .rule-card.required .ic {
            background: #fef3c7;
            color: #d97706;
        }

        .rule-card.exclusive {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .rule-card.exclusive .ic {
            background: #fee2e2;
            color: #dc2626;
        }

        .rule-card.combo {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .rule-card.combo .ic {
            background: #dcfce7;
            color: #15803d;
        }

        .rule-card .field-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .rule-card .field-list code {
            background: #fff;
            border: 1px solid var(--border-light);
            color: var(--text-primary);
            font-size: 0.74rem;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .rule-card .field-list .sep {
            color: #94a3b8;
            font-weight: 700;
            padding: 3px 0;
            font-size: 0.74rem;
        }

        .rule-card .rule-note {
            font-size: 0.74rem;
            color: var(--text-muted);
            margin-top: 8px;
            line-height: 1.5;
        }

        .rule-card .rule-note code {
            font-size: 0.72rem;
            background: rgba(0, 0, 0, 0.05);
        }

        /* Error table */
        .error-table {
            width: 100%;
            font-size: 0.82rem;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .error-table th {
            text-align: left;
            background: #fef2f2;
            color: #991b1b;
            padding: 8px 12px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #fecaca;
        }

        .error-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #fee2e2;
            vertical-align: top;
        }

        .error-table .err-code {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            background: #fee2e2;
            color: #991b1b;
            padding: 1px 7px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        /* Param table */
        .param-table {
            width: 100%;
            font-size: 0.84rem;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .param-table th {
            text-align: left;
            background: #f8fafc;
            padding: 8px 12px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-light);
        }

        .param-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .param-table .req {
            color: #ef4444;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 4px;
        }

        .param-table .type-tag {
            display: inline-block;
            font-size: 0.7rem;
            background: #f1f5f9;
            color: #64748b;
            padding: 1px 6px;
            border-radius: 3px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Welcome panel */
        .welcome-panel {
            text-align: center;
            padding: 60px 40px;
        }

        .welcome-panel .icon {
            font-size: 3rem;
            color: var(--grab-green);
            margin-bottom: 12px;
        }

        .welcome-panel h2 {
            font-weight: 800;
            margin-bottom: 6px;
        }

        .welcome-panel p {
            color: var(--text-secondary);
            max-width: 500px;
            margin: 0 auto 16px;
        }

        /* Sidebar scroll */
        .sidebar::-webkit-scrollbar,
        .main::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-thumb,
        .main::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    {{-- Try it Live module — load awal supaya AWSAPI_TryIt available saat inline init() di setiap panel --}}
    <script src="{{ asset('javascript/docs/aws-api-try-it.js') }}"></script>

    <header class="topbar">
        <span class="brand">📚 <span data-i18n="topbar_title">AWS Location Service Reference</span> <small data-i18n="topbar_subtitle">v0 (legacy) &amp; v2 (standalone)</small></span>
        <input type="text" class="search-box" placeholder="🔍 Search operation..." id="searchBox" data-i18n-placeholder="search_placeholder">
        <div class="lang-toggle">
            <button data-lang="id">ID</button>
            <button data-lang="en">EN</button>
        </div>
    </header>

    <div class="layout">
        <!-- ========================== SIDEBAR ========================== -->
        <aside class="sidebar">

            <!-- Maps V2 -->
            <div class="service-group" data-service="maps">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_maps">Maps</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="maps-get-style-descriptor">GetStyleDescriptor </a></li>
                    <li><a class="op-link" data-op="maps-get-tile">GetTile </a></li>
                    <li><a class="op-link" data-op="maps-get-glyphs">GetGlyphs </a></li>
                    <li><a class="op-link" data-op="maps-get-sprites">GetSprites </a></li>
                    <li><a class="op-link unavail" data-op="maps-get-static-map">GetStaticMap <span class="badge-soon">Soon</span></a></li>
                </ul>
            </div>

            <!-- Places V2 -->
            <div class="service-group" data-service="places">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_places">Places</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="places-search-text">SearchText </a></li>
                    <li><a class="op-link" data-op="places-suggest">Suggest </a></li>
                    <li><a class="op-link" data-op="places-reverse-geocode">ReverseGeocode </a></li>
                    <li><a class="op-link" data-op="places-get-place">GetPlace </a></li>
                    <li><a class="op-link unavail" data-op="places-autocomplete">Autocomplete <span class="badge-soon">Soon</span></a></li>
                    <li><a class="op-link unavail" data-op="places-geocode">Geocode <span class="badge-soon">Soon</span></a></li>
                    <li><a class="op-link unavail" data-op="places-search-nearby">SearchNearby <span class="badge-soon">Soon</span></a></li>
                </ul>
            </div>

            <!-- Routes V2 -->
            <div class="service-group" data-service="routes">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_routes">Routes</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="routes-calculate-routes">CalculateRoutes </a></li>
                    <li><a class="op-link" data-op="routes-calculate-route-matrix">CalculateRouteMatrix </a></li>
                    <li><a class="op-link unavail" data-op="routes-calculate-isolines">CalculateIsolines <span class="badge-soon">Soon</span></a></li>
                    <li><a class="op-link unavail" data-op="routes-optimize-waypoints">OptimizeWaypoints <span class="badge-soon">Soon</span></a></li>
                    <li><a class="op-link unavail" data-op="routes-snap-to-roads">SnapToRoads <span class="badge-soon">Soon</span></a></li>
                </ul>
            </div>

            <!-- Meta -->
            <div class="service-group" data-service="meta">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    <span data-i18n="svc_general">General Topics</span>
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="meta-overview" data-i18n="meta_overview">Overview v0 vs v2</a></li>
                    <li><a class="op-link" data-op="meta-auth" data-i18n="meta_auth">Authentication</a></li>
                    <li><a class="op-link" data-op="meta-quotas" data-i18n="meta_quotas">Quotas &amp; Limits</a></li>
                    <li><a class="op-link" data-op="meta-migration" data-i18n="meta_migration">Migration Guide</a></li>
                </ul>
            </div>

            <!-- Legend -->
            <div class="legend">
                <div class="row" style="margin-top:8px;font-size:0.7rem;line-height:1.4;">
                    Status berdasarkan <code style="font-size:0.65rem;padding:1px 4px;">ap-southeast-1</code> default provider (GrabMaps).
                </div>
            </div>

        </aside>

        <!-- ========================== MAIN ========================== -->
        <main class="main">

            <!-- Welcome panel -->
            <div class="op-panel active" id="op-welcome">
                <div class="welcome-panel">
                    <div class="icon"><i class="bi bi-book-half"></i></div>
                    <h2 data-i18n="welcome_title">AWS Location Service Reference</h2>
                    <p data-i18n="welcome_desc">Pilih operation di sidebar kiri untuk lihat detail endpoint, request body, response shape, dan perbandingan v0 ↔ v2.</p>
                    <p class="text-muted small" data-i18n="welcome_note">
                        Default provider di ap-southeast-1 = GrabMaps. Dokumentasi ini fokus untuk integrasi API.
                    </p>
                </div>
            </div>

            <!-- Shared "Coming Soon" panel (untuk operations yang belum tersedia di region) -->
            <div class="op-panel" id="op-coming-soon">
                <div class="welcome-panel">
                    <div class="icon" style="color:#f59e0b;"><i class="bi bi-hourglass-split"></i></div>
                    <h2><span id="comingSoonTitle">Operation</span></h2>
                    <p style="margin-bottom:18px;">
                        <span style="display:inline-block;background:linear-gradient(90deg,#fbbf24,#f59e0b);color:#fff;padding:4px 14px;border-radius:999px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;" data-i18n="soon_pill">⏳ Coming Soon</span>
                    </p>
                    <p data-i18n="soon_main">Operation ini belum tersedia di API key kamu untuk region ap-southeast-1.</p>
                    <p class="text-muted small" data-i18n="soon_note">
                        Dokumentasi detail akan ditambahkan setelah AWS rilis action ini di region kamu.
                        Sementara, cek alternatif yang sudah available di sidebar (dot hijau).
                    </p>
                </div>
            </div>

            {{-- =============================================================== --}}
            {{-- ROUTES OPERATIONS                                              --}}
            {{-- =============================================================== --}}

            <!-- CalculateRoutes -->
            <div class="op-panel" id="op-routes-calculate-routes">
                <div class="breadcrumb-mini">Routes V2 / CalculateRoutes</div>
                <h1>CalculateRoutes</h1>
                <p class="op-desc" data-i18n="cr_desc">Hitung rute dari Origin ke Destination, dengan opsi waypoints, mode kendaraan, hindari toll/ferry, dan turn-by-turn instructions.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method POST">POST</span><span>https://routes.geo.{region}.amazonaws.com/v2/routes?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
<pre><code class="language-json">{
  "Origin": [ number, number ],
  "Destination": [ number, number ],
  "Waypoints": [ { "Position": [ number, number ], "StopDuration": number } ],
  "TravelMode": "Car" | "Scooter" | "Pedestrian" | "Truck",
  "TravelStepType": "Default" | "TurnByTurn",
  "LegGeometryFormat": "Simple" | "FlexiblePolyline",
  "InstructionsMeasurementSystem": "Metric" | "Imperial",
  "Locale": "string",
  "Avoid": {
    "TollRoads": boolean,
    "Ferries": boolean,
    "ControlledAccessHighways": boolean,
    "DirtRoads": boolean,
    "TruckRoadTypes": [ "string" ],
    "Areas": [ { "Geometry": { "Polygon": [...] } } ]
  },
  "DepartureTime": "string",
  "ArrivalTime": "string",
  "OptimizeRoutingFor": "FastestRoute" | "ShortestRoute"
}</code></pre>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>Origin</code></td><td><span class="type-tag">[lng, lat]</span></td><td><span class="req">YES</span></td><td data-i18n="cr_p_origin">Starting point</td></tr>
                        <tr><td><code>Destination</code></td><td><span class="type-tag">[lng, lat]</span></td><td><span class="req">YES</span></td><td data-i18n="cr_p_dest">End point</td></tr>
                        <tr><td><code>Waypoints</code></td><td><span class="type-tag">array</span></td><td>—</td><td data-i18n="cr_p_wp">Max 23 (excluding Origin <td>Max 23 (di luar Origin & Destination)</td> Destination)</td></tr>
                        <tr><td><code>TravelMode</code></td><td><span class="type-tag">enum</span></td><td>—</td><td data-i18n="note_travel_mode_v2">Default: Car. v0→v2 mapping: Motorcycle→Scooter, Walking→Pedestrian</td></tr>
                        <tr><td><code>LegGeometryFormat</code></td><td><span class="type-tag">enum</span></td><td>—</td><td><code>Simple</code> = LineString array, <code>FlexiblePolyline</code> = encoded</td></tr>
                        <tr><td><code>Avoid.TollRoads</code></td><td><span class="type-tag">boolean</span></td><td>—</td><td data-i18n="note_no_pedestrian">Not supported for Pedestrian</td></tr>
                        <tr><td><code>DepartureTime</code></td><td><span class="type-tag">ISO 8601</span></td><td>—</td><td data-i18n="note_excl_arrival">Mutually exclusive with ArrivalTime</td></tr>
                        <tr><td><code>OptimizeRoutingFor</code></td><td><span class="type-tag">enum</span></td><td>—</td><td data-i18n="note_default_fastest">Default: FastestRoute</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                <div class="rules-grid">
                    <div class="rule-card required">
                        <div class="rule-header"><span class="ic"><i class="bi bi-check-square-fill"></i></span> <span data-i18n="rule_required_qt">Required</span></div>
                        <div class="field-list"><code>Origin</code> <code>Destination</code></div>
                        <div class="rule-note" data-i18n="cr_required_note">Dua-duanya wajib. Format [lng, lat] valid.</div>
                    </div>
                    <div class="rule-card exclusive">
                        <div class="rule-header"><span class="ic"><i class="bi bi-x-octagon-fill"></i></span> <span data-i18n="rule_exclusive">Mutually exclusive</span></div>
                        <div class="field-list"><code>DepartureTime</code> <span class="sep">XOR</span> <code>ArrivalTime</code></div>
                        <div class="rule-note" data-i18n="cr_time_note">Pilih salah satu — kapan mau berangkat ATAU kapan harus tiba. Default = sekarang.</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-puzzle-fill"></i></span> <span data-i18n="cr_avoid_label">Avoid options</span></div>
                        <div class="field-list"><code>TollRoads</code> <code>Ferries</code> <code>ControlledAccessHighways</code> <code>DirtRoads</code></div>
                        <div class="rule-note" data-i18n="cr_avoid_note">Boleh dikombinasi semua. ⚠️ TollRoads=true tidak boleh untuk TravelMode Pedestrian (error 400).</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-layers-fill"></i></span> <span data-i18n="rule_independent">Independent</span></div>
                        <div class="field-list"><code>TravelMode</code> <code>LegGeometryFormat</code> <code>Locale</code> <code>OptimizeRoutingFor</code></div>
                    </div>
                </div>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th data-i18n="err_message">AWS Message</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="cr_err_origin">Tanpa <code>Origin</code> atau <code>Destination</code></td><td><em>"Origin/Destination is required"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="cr_err_pedestrian"><code>Avoid.TollRoads</code> dengan <code>TravelMode: Pedestrian</code></td><td><em>"TollRoads not supported for Pedestrian"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="cr_err_waypoints">Waypoints &gt; 23</td><td><em>"...less than or equal to 23"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="cr_err_time">Kirim <code>DepartureTime</code> + <code>ArrivalTime</code> bareng</td><td><em>"Only one of DepartureTime/ArrivalTime allowed"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n="cr_err_unreach">Origin/Destination tidak bisa di-reach (mis. tengah laut)</td><td><em>"No route found"</em></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
<pre><code class="language-json">{
  "PricingBucket": "string",
  "LegGeometryFormat": "string",
  "Notices": [...],
  "Routes": [
    {
      "Summary": { "Distance": number, "Duration": number },
      "Legs": [
        {
          "Distance": number,
          "Duration": number,
          "Geometry": { "LineString": [ [lng, lat], ... ] },
          "TravelMode": "string",
          "Type": "Vehicle" | "Pedestrian" | "Ferry",
          "VehicleLegDetails": {
            "TravelSteps": [ { "Distance": number, "Duration": number, "Instruction": "string" } ]
          }
        }
      ]
    }
  ]
}</code></pre>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>Routes[0].Summary.Distance</code></td><td><span class="type-tag">number</span></td><td data-i18n-html="r_meter_not_km"><strong>Meters</strong> (not km like v0)</td></tr>
                        <tr><td><code>Routes[0].Summary.Duration</code></td><td><span class="type-tag">number</span></td><td data-i18n-html="r_seconds_renamed">Seconds (previously <code>DurationSeconds</code>)</td></tr>
                        <tr><td><code>Routes[0].Legs[].Geometry.LineString</code></td><td><span class="type-tag">array</span></td><td data-i18n="r_linestring">Array of [lng, lat] for MapLibre LineString</td></tr>
                        <tr><td><code>Routes[0].Legs[].Type</code></td><td><span class="type-tag">enum</span></td><td data-i18n="r_legtype">Vehicle / Pedestrian / Ferry</td></tr>
                        <tr><td><code>Routes[0].Legs[].VehicleLegDetails.TravelSteps</code></td><td><span class="type-tag">array</span></td><td data-i18n="r_steps">Turn-by-turn instructions (when TravelStepType=TurnByTurn)</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                <div class="alert-mini success" style="margin-bottom:14px;">
                    🔒 <span data-i18n="proxy_safe_cr">Aman — routing lewat <code>/api/v2/routes/calculate</code> (Laravel proxy).</span>
                </div>

                <div class="preset-row">
                    <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                    <button class="preset-btn" data-preset="car">🚗 Car</button>
                    <button class="preset-btn" data-preset="scooter">🛵 Scooter</button>
                    <button class="preset-btn" data-preset="pedestrian">🚶 Pedestrian</button>
                    <button class="preset-btn" data-preset="waypoints">🚦 <span data-i18n="cr_preset_wp">+ Waypoints</span></button>
                    <button class="preset-btn" data-preset="full">🎛️ <span data-i18n="preset_all">All Features</span></button>
                </div>

                <div class="try-it">
                    <div class="try-it-pane right" style="border-right:0;">
                        <div class="try-it-pane-header">
                            <span><i class="bi bi-code-slash"></i> Request Body <span class="json-status ok" id="cr-json-status">VALID</span></span>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-copy" onclick="copyToClipboard('cr-req-preview', this)"><span data-i18n="btn_copy">📋 Copy</span></button>
                                <button class="btn-copy" id="cr-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                            </div>
                        </div>
                        <div class="try-it-url">
                            <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://routes.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/routes</span></div>
                            <div><span class="try-it-method" style="background:#10b981;">VIA</span><span style="color:#86efac;">/api/v2/routes/calculate</span></div>
                        </div>
                        <textarea class="json-editor" id="cr-req-preview" spellcheck="false"></textarea>
                        <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                            <button class="btn-send" id="cr-run" type="button"><i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span></button>
                            <span id="cr-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">⏳ <span data-i18n="loading">Loading</span>...</span>
                        </div>
                    </div>
                </div>

                <div class="resp-bar">
                    <span style="font-weight:700;color:var(--text-primary);">Response</span>
                    <span class="status-pill idle" id="cr-status">— idle —</span>
                    <span class="meta" id="cr-meta"></span>
                </div>
                <div id="cr-resp" class="resp-body empty" data-i18n="resp_idle">Klik Send Request.</div>

                <script>
                    AWSAPI_TryIt.init({
                        prefix: 'cr',
                        panelId: 'op-routes-calculate-routes',
                        proxy: '/api/v2/routes/calculate',
                        defaultPreset: 'car',
                        // Custom meta: tampilin distance/duration dari Routes[0].Summary
                        metaFormatter: (data, ms, ok) => {
                            const route = (data.Routes || [])[0];
                            return ok && route
                                ? `<b>${ms}ms</b> · <b>${(route.Summary.Distance/1000).toFixed(2)} km</b> · <b>${Math.round(route.Summary.Duration/60)} min</b>`
                                : `<b>${ms}ms</b> · error`;
                        },
                        presets: {
                            car: { Origin: [106.8456, -6.2088], Destination: [106.8270, -6.1751], TravelMode: 'Car', LegGeometryFormat: 'Simple', InstructionsMeasurementSystem: 'Metric', Locale: 'id' },
                            scooter: { Origin: [106.8456, -6.2088], Destination: [106.8270, -6.1751], TravelMode: 'Scooter', LegGeometryFormat: 'Simple', Locale: 'id' },
                            pedestrian: { Origin: [106.8456, -6.2088], Destination: [106.8270, -6.1751], TravelMode: 'Pedestrian', LegGeometryFormat: 'Simple', Locale: 'id' },
                            waypoints: { Origin: [106.8456, -6.2088], Destination: [106.8270, -6.1751], Waypoints: [{ Position: [106.8410, -6.1900] }, { Position: [106.8350, -6.1820] }], TravelMode: 'Car', LegGeometryFormat: 'Simple', Locale: 'id' },
                            full: { Origin: [106.8456, -6.2088], Destination: [106.8270, -6.1751], TravelMode: 'Car', LegGeometryFormat: 'Simple', InstructionsMeasurementSystem: 'Metric', Locale: 'id', Avoid: { TollRoads: true, Ferries: false }, OptimizeRoutingFor: 'FastestRoute', TravelStepType: 'TurnByTurn' }
                        }
                    });
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method POST">POST</span><span>/routes/v0/calculators/{Calc}/calculate/route?key=...</span></div>
                    <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
<pre><code class="language-json">{
  "DeparturePosition": [106.84, -6.20],
  "DestinationPosition": [106.85, -6.24],
  "WaypointPositions": [[106.846, -6.21]],
  "TravelMode": "Car",
  "DistanceUnit": "Kilometers",
  "IncludeLegGeometry": true,
  "AvoidTolls": true,
  "DepartNow": true
}</code></pre>
                    <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response</div>
<pre><code class="language-json">{
  "Summary": { "Distance": 5.2, "DurationSeconds": 720 },
  "Legs": [{ "Distance": 5.2, "DurationSeconds": 720,
             "Geometry": { "LineString": [[106.84,-6.20], ...] } }]
}</code></pre>
                    <div class="alert-mini warn" data-i18n-html="cr_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li><code>DeparturePosition</code> → <code>Origin</code>, <code>DestinationPosition</code> → <code>Destination</code></li>
                            <li><code>WaypointPositions: [[lng,lat]]</code> → <code>Waypoints: [{ Position: [lng,lat] }]</code></li>
                            <li><code>AvoidTolls: bool</code> → <code>Avoid: { TollRoads: bool }</code> (nested)</li>
                            <li>TravelMode: <code>Motorcycle</code> → <code>Scooter</code>, <code>Walking</code> → <code>Pedestrian</code></li>
                            <li>Distance v0 dalam <strong>kilometer</strong>, v2 <strong>meter</strong></li>
                            <li><code>DurationSeconds</code> → <code>Duration</code></li>
                            <li>Response wrapper: v0 langsung Summary/Legs, v2 ada <code>Routes[0]</code> array</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- CalculateRouteMatrix -->
            <div class="op-panel" id="op-routes-calculate-route-matrix">
                <div class="breadcrumb-mini">Routes V2 / CalculateRouteMatrix</div>
                <h1>CalculateRouteMatrix</h1>
                <p class="op-desc" data-i18n="crm_desc">Hitung jarak &amp; waktu untuk semua kombinasi origin × destination — efisien untuk "find nearest" use case.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method POST">POST</span><span>https://routes.geo.{region}.amazonaws.com/v2/route-matrix?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
<pre><code class="language-json">{
  "Origins": [ { "Position": [ number, number ] } ],
  "Destinations": [ { "Position": [ number, number ] } ],
  "TravelMode": "Car" | "Scooter" | "Pedestrian" | "Truck",
  "RoutingBoundary": {
    "Unbounded": true,
    "Geometry": { "AutoCircle": { "Margin": number, "MaxRadius": number } }
  },
  "Avoid": {
    "TollRoads": boolean,
    "Ferries": boolean
  },
  "DepartureTime": "string",
  "OptimizeRoutingFor": "FastestRoute" | "ShortestRoute"
}</code></pre>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>Origins</code></td><td><span class="type-tag">array</span></td><td><span class="req">YES</span></td><td>Array of <code>{ Position: [lng,lat] }</code></td></tr>
                        <tr><td><code>Destinations</code></td><td><span class="type-tag">array</span></td><td><span class="req">YES</span></td><td>Array of <code>{ Position: [lng,lat] }</code></td></tr>
                        <tr><td><code>RoutingBoundary</code></td><td><span class="type-tag">object</span></td><td><span class="req">YES</span></td><td>WAJIB di v2! <code>{ Unbounded: true }</code> untuk perilaku v0.</td></tr>
                        <tr><td><code>TravelMode</code></td><td><span class="type-tag">enum</span></td><td>—</td><td data-i18n="note_default_car">Default: Car</td></tr>
                        <tr><td><code>Avoid.TollRoads</code></td><td><span class="type-tag">boolean</span></td><td>—</td><td data-i18n="note_no_pedestrian">Not allowed for Pedestrian</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                <div class="rules-grid">
                    <div class="rule-card required">
                        <div class="rule-header"><span class="ic"><i class="bi bi-check-square-fill"></i></span> <span data-i18n="rule_required_qt">Required</span></div>
                        <div class="field-list"><code>Origins</code> <code>Destinations</code> <code>RoutingBoundary</code></div>
                        <div class="rule-note" data-i18n="crm_required_note">Tiga-tiganya wajib. Beda dari v0 yang gak butuh RoutingBoundary.</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-grid-3x3"></i></span> <span data-i18n="crm_limit_label">Cell limit</span></div>
                        <div class="field-list"><code>Origins.length × Destinations.length ≤ 700</code></div>
                        <div class="rule-note" data-i18n="crm_limit_note">Max 700 sel per request. Mis. 7×100 atau 35×20. Pisah jadi multiple request kalau lebih.</div>
                    </div>
                </div>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th data-i18n="err_message">AWS Message</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="crm_err_boundary">Tanpa <code>RoutingBoundary</code></td><td><em>"RoutingBoundary is required"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n="crm_err_cells">Origins × Destinations &gt; 700</td><td><em>"Too many cells: max 700"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n="crm_err_pos">Position kosong di salah satu Origins/Destinations</td><td><em>"Position is required"</em></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
<pre><code class="language-json">{
  "PricingBucket": "string",
  "RouteMatrix": [
    [
      { "Distance": number, "Duration": number, "Error": null }
    ]
  ],
  "ErrorCount": number
}</code></pre>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>RouteMatrix[i][j].Distance</code></td><td><span class="type-tag">number</span></td><td><strong>Meter</strong> (v0: km)</td></tr>
                        <tr><td><code>RouteMatrix[i][j].Duration</code></td><td><span class="type-tag">number</span></td><td>Detik (v0: <code>DurationSeconds</code>)</td></tr>
                        <tr><td><code>RouteMatrix[i][j].Error</code></td><td><span class="type-tag">object|null</span></td><td data-i18n-html="r_cell_error">Per-cell error (e.g. unreachable). <code>null</code> if OK.</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                <div class="alert-mini success" style="margin-bottom:14px;">
                    🔒 <span data-i18n="proxy_safe_crm">Aman — routing lewat <code>/api/routes/matrix</code> (Laravel proxy).</span>
                </div>

                <div class="alert-mini warn" style="margin-bottom:14px;">
                    ⚠️ <span data-i18n="crm_proxy_note">Note: proxy <code>/api/routes/matrix</code> di project ini masih panggil endpoint <strong>v0</strong> di backend (untuk backward compat). Body request harus pakai shape v0 (lihat tab v0). Untuk demo native v2, panggil AWS langsung dengan API Key.</span>
                </div>

                <div class="preset-row">
                    <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                    <button class="preset-btn" data-preset="v2_basic">📦 v2 Body</button>
                    <button class="preset-btn" data-preset="v0_basic">📜 <span data-i18n="crm_preset_v0">v0 Body (proxy)</span></button>
                </div>

                <div class="try-it">
                    <div class="try-it-pane right" style="border-right:0;">
                        <div class="try-it-pane-header">
                            <span><i class="bi bi-code-slash"></i> Request Body <span class="json-status ok" id="crm-json-status">VALID</span></span>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-copy" onclick="copyToClipboard('crm-req-preview', this)"><span data-i18n="btn_copy">📋 Copy</span></button>
                                <button class="btn-copy" id="crm-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                            </div>
                        </div>
                        <div class="try-it-url">
                            <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://routes.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/route-matrix</span></div>
                            <div><span class="try-it-method" style="background:#10b981;">VIA</span><span style="color:#86efac;">/api/routes/matrix</span></div>
                        </div>
                        <textarea class="json-editor" id="crm-req-preview" spellcheck="false"></textarea>
                        <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                            <button class="btn-send" id="crm-run" type="button"><i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span></button>
                            <span id="crm-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">⏳ <span data-i18n="loading">Loading</span>...</span>
                        </div>
                    </div>
                </div>

                <div class="resp-bar">
                    <span style="font-weight:700;color:var(--text-primary);">Response</span>
                    <span class="status-pill idle" id="crm-status">— idle —</span>
                    <span class="meta" id="crm-meta"></span>
                </div>
                <div id="crm-resp" class="resp-body empty" data-i18n="resp_idle">Klik Send Request.</div>

                <script>
                    AWSAPI_TryIt.init({
                        prefix: 'crm',
                        panelId: 'op-routes-calculate-route-matrix',
                        proxy: '/api/routes/matrix',
                        defaultPreset: 'v0_basic',
                        // Custom meta: tampilin total cells dari matrix
                        metaFormatter: (data, ms, ok) => {
                            const m = data.RouteMatrix || [];
                            const cells = m.reduce((a, row) => a + row.length, 0);
                            return ok ? `<b>${ms}ms</b> · <b>${cells}</b> cells` : `<b>${ms}ms</b> · error`;
                        },
                        presets: {
                            v2_basic: {
                                Origins: [{ Position: [106.8456, -6.2088] }],
                                Destinations: [{ Position: [106.8270, -6.1751] }, { Position: [106.8410, -6.1900] }],
                                TravelMode: 'Car',
                                RoutingBoundary: { Unbounded: true }
                            },
                            v0_basic: {
                                DeparturePositions: [[106.8456, -6.2088]],
                                DestinationPositions: [[106.8270, -6.1751], [106.8410, -6.1900]],
                                TravelMode: 'Car',
                                DistanceUnit: 'Kilometers'
                            }
                        }
                    });
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method POST">POST</span><span>/routes/v0/calculators/{Calc}/calculate/route-matrix?key=...</span></div>
                    <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
<pre><code class="language-json">{
  "DeparturePositions": [[106.84, -6.20]],
  "DestinationPositions": [[106.85,-6.24], [106.86,-6.25]],
  "TravelMode": "Car",
  "DistanceUnit": "Kilometers"
}</code></pre>
                    <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response (km, DurationSeconds)</div>
<pre><code class="language-json">{
  "RouteMatrix": [[
    { "Distance": 1.2, "DurationSeconds": 240 },
    { "Distance": 2.5, "DurationSeconds": 480 }
  ]]
}</code></pre>
                    <div class="alert-mini warn" data-i18n-html="crm_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li><code>DeparturePositions: [[lng,lat]]</code> → <code>Origins: [{ Position: [lng,lat] }]</code></li>
                            <li><code>DestinationPositions: [[lng,lat]]</code> → <code>Destinations: [{ Position: [lng,lat] }]</code></li>
                            <li>v2 wajib <code>RoutingBoundary</code> (v0 tidak)</li>
                            <li>Distance: v0 km → v2 meter</li>
                            <li><code>DurationSeconds</code> → <code>Duration</code></li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- CalculateIsolines -->
            <div class="op-panel" id="op-routes-calculate-isolines">
                <div class="breadcrumb-mini">Routes V2 / CalculateIsolines</div>
                <h1>CalculateIsolines <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="iso_desc">Polygon area "reachable within X minutes" atau "within Y km" dari satu titik. Cocok untuk visualisasi service coverage.</p>

                <div class="alert-mini soon" data-i18n-html="soon_iso"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Action not listed in AWS Console permissions. Wait for AWS rollout or use the workaround below.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/isolines?key=...</span></div>

                <h4>Request body</h4>
                <pre><code class="language-json">{
  "Origin": [106.84, -6.20],
  "TravelMode": "Car",
  "Thresholds": {
    "Time": [300, 600, 900]
  },
  "OptimizeIsolineFor": "FastestRoute"
}</code></pre>
                <p>Bisa juga pakai <code>Thresholds.Distance</code> (meter) atau kedua-duanya.</p>

                <h4>Response</h4>
                <pre><code class="language-json">{
  "Isolines": [{
    "TimeThreshold": 300,
    "Geometries": [{
      "Polygon": [[ [106.83,-6.19], ... ]]
    }]
  }]
}</code></pre>
                <div class="alert-mini info" data-i18n-html="iso_use_case">
                    💡 Use case: visualize <strong>"stops reachable within 10 minutes on foot"</strong> with polygon overlay on MapLibre.
                </div>
            </div>

            <!-- OptimizeWaypoints -->
            <div class="op-panel" id="op-routes-optimize-waypoints">
                <div class="breadcrumb-mini">Routes V2 / OptimizeWaypoints</div>
                <h1>OptimizeWaypoints <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="opt_desc">TSP solver — kasih AWS list of waypoints, dia kembalikan urutan optimal (ngirit jarak/waktu).</p>

                <div class="alert-mini soon" data-i18n-html="soon_opt"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. For this feature, implement nearest-neighbor TSP yourself in JS or use a library.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/optimize-waypoints?key=...</span></div>

                <h4>Request body</h4>
                <pre><code class="language-json">{
  "Origin": [106.84, -6.20],
  "Destination": [106.92, -6.30],
  "Waypoints": [
    { "Id": "stop1", "Position": [106.85,-6.21] },
    { "Id": "stop2", "Position": [106.90,-6.22] },
    { "Id": "stop3", "Position": [106.87,-6.25] }
  ],
  "TravelMode": "Car",
  "OptimizeSequencingFor": "FastestRoute"
}</code></pre>

                <h4>Response</h4>
                <pre><code class="language-json">{
  "OptimizedWaypoints": [
    { "Id": "stop1", "Position": [106.85,-6.21] },
    { "Id": "stop3", "Position": [106.87,-6.25] },
    { "Id": "stop2", "Position": [106.90,-6.22] }
  ],
  "Distance": 12500,
  "Duration": 1850
}</code></pre>
                <div class="alert-mini success">
                    ✅ Sebelumnya kamu harus implement TSP / nearest-neighbor sendiri di JS. Sekarang AWS yang hitungkan.
                </div>
            </div>

            <!-- SnapToRoads -->
            <div class="op-panel" id="op-routes-snap-to-roads">
                <div class="breadcrumb-mini">Routes V2 / SnapToRoads</div>
                <h1>SnapToRoads <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="snap_desc">Snap GPS trace yang noisy ke jalan terdekat. Berguna untuk clean trip log GPS dari kendaraan.</p>

                <div class="alert-mini soon" data-i18n-html="soon_snap"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Not yet rolled out for GrabMaps provider.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/snap-to-roads?key=...</span></div>

                <h4>Request body</h4>
                <pre><code class="language-json">{
  "TracePoints": [
    { "Position": [106.840, -6.200] },
    { "Position": [106.842, -6.201] },
    { "Position": [106.845, -6.203] }
  ],
  "TravelMode": "Car",
  "SnappedGeometryFormat": "Simple"
}</code></pre>

                <h4>Response</h4>
                <pre><code class="language-json">{
  "SnappedGeometry": {
    "LineString": [[106.840,-6.200], [106.843,-6.201], ...]
  },
  "SnappedTracePoints": [
    { "OriginalPosition": [106.840,-6.200], "SnappedPosition": [106.840,-6.200] }
  ]
}</code></pre>
                <div class="alert-mini info" data-i18n="snap_use_case">
                    💡 Pair with ride-hailing trip recording to get clean trip lines (without GPS-noise zigzags).
                </div>
            </div>

            {{-- =============================================================== --}}
            {{-- MAPS OPERATIONS                                                --}}
            {{-- =============================================================== --}}

            <!-- GetStyleDescriptor -->
            <div class="op-panel" id="op-maps-get-style-descriptor">
                <div class="breadcrumb-mini">Maps V2 / GetStyleDescriptor</div>
                <h1>GetStyleDescriptor</h1>
                <p class="op-desc" data-i18n="gsd_desc">Return MapLibre style spec JSON. URL ini langsung ditaroh di property <code>style</code> waktu inisialisasi MapLibre map.</p>

                <div class="ver-tabs">
                    <button data-version="v0" data-i18n="ver_v0">v0 Legacy</button>
                    <button data-version="v2" class="active" data-i18n="ver_v2">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/styles/{Style}/descriptor?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Query Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th data-i18n="th_param">Param</th><th data-i18n="th_values">Values</th><th data-i18n="th_note">Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>{Style}</code><span class="req">PATH</span></td><td><span class="type-tag">enum</span> Standard | Monochrome | Hybrid | Satellite</td><td data-i18n-html="gsd_p_style_note">In <code>ap-southeast-1</code>: only Standard &amp; Monochrome (GrabMaps provider)</td></tr>
                        <tr><td><code>key</code><span class="req">REQ</span></td><td><span class="type-tag">string</span></td><td data-i18n="note_api_key">API key</td></tr>
                        <tr><td><code>color-scheme</code></td><td><span class="type-tag">enum</span> Light | Dark</td><td data-i18n="note_only_std_mono">Only for Standard / Monochrome</td></tr>
                        <tr><td><code>political-view</code></td><td><span class="type-tag">string</span> ISO-3</td><td data-i18n="note_iso3_examples">IDN, MYS, ARG, MAR, etc.</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                <div class="rules-grid">
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-puzzle-fill"></i></span> <span data-i18n="gsd_rule_header">Style + Color compatibility</span></div>
                        <div class="field-list" data-i18n-html="gsd_rule_fields"><code>Standard</code> + <code>Light/Dark</code> ✓<br><code>Monochrome</code> + <code>Light/Dark</code> ✓<br><code>Hybrid</code>/<code>Satellite</code> + <code>color-scheme</code> ❌</div>
                        <div class="rule-note" data-i18n="gsd_rule_note">Raster styles (Hybrid/Satellite) don't accept color-scheme — sending it returns error 400.</div>
                    </div>
                </div>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th data-i18n="err_message">AWS Message</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">400</span></td><td data-i18n="gsd_e_style">Style not available in region (e.g. Satellite in ap-southeast-1)</td><td><em>"Satellite is not a supported map style"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="gsd_e_color"><code>color-scheme</code> used on Hybrid/Satellite</td><td><em>"color-scheme not applicable"</em></td></tr>
                        <tr><td><span class="err-code">403</span></td><td data-i18n-html="gsd_e_perm">API Key lacks <code>geo-maps:GetStyleDescriptor</code></td><td><em>"explicit deny"</em></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_json">Response (JSON)</span></div>
<pre><code class="language-json">{
  "version": 8,
  "name": "Standard",
  "sources": {
    "tiles": { "type": "vector", "tiles": [ "https://maps.geo.../v2/tiles/Standard/Default/Default/{z}/{x}/{y}?key=..." ] }
  },
  "sprite": "https://maps.geo.../v2/sprites/Standard/Default/Default",
  "glyphs": "https://maps.geo.../v2/glyphs/Standard/{fontstack}/{range}?key=...",
  "layers": [ ... ]
}</code></pre>
                <p style="font-size:0.85rem;color:var(--text-muted);" data-i18n="gsd_response_note">Style descriptor is a complete MapLibre recipe — it contains URLs for GetTile / GetGlyphs / GetSprites. MapLibre auto-fetches all three.</p>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gsd_try_hint">
                    💡 Pick style + color + political-view → the MapLibre map below auto re-renders with the new style descriptor.
                </div>

                <div class="preset-row">
                    <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                    <button class="preset-btn" data-style="Standard" data-color="Light">☀️ Standard Light</button>
                    <button class="preset-btn" data-style="Standard" data-color="Dark">🌙 Standard Dark</button>
                    <button class="preset-btn" data-style="Monochrome" data-color="Light">⚪ Monochrome Light</button>
                    <button class="preset-btn" data-style="Monochrome" data-color="Dark">⚫ Monochrome Dark</button>
                </div>

                <div id="gsd-map" style="width:100%;height:380px;border-radius:10px;border:1px solid var(--border-light);margin-bottom:12px;"></div>
                <div class="resp-bar">
                    <span style="font-weight:700;" data-i18n="label_url">URL</span>
                    <code id="gsd-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                    <button class="btn-copy" id="gsd-copy-url" style="background:#e2e8f0;color:#334155;border:1px solid #cbd5e1;" data-i18n="btn_copy">📋 Copy</button>
                </div>

                <script>
                (function() {
                    const REGION = "{{ env('AWS_REGION') }}";
                    const API_KEY = "{{ env('AWS_API_KEY') }}";
                    let map = null;
                    let curStyle = 'Standard', curColor = 'Light';
                    function buildUrl() {
                        const params = ['key=' + API_KEY, 'color-scheme=' + curColor];
                        return `https://maps.geo.${REGION}.amazonaws.com/v2/styles/${curStyle}/descriptor?` + params.join('&');
                    }
                    function render() {
                        const url = buildUrl();
                        document.getElementById('gsd-url').textContent = url.replace(API_KEY, '***');
                        if (!map) {
                            map = new maplibregl.Map({
                                container: 'gsd-map',
                                style: url,
                                center: [106.8456, -6.2088],
                                zoom: 11
                            });
                        } else {
                            map.setStyle(url);
                        }
                    }
                    document.querySelectorAll('#op-maps-get-style-descriptor .preset-btn').forEach(b => {
                        b.addEventListener('click', () => {
                            curStyle = b.dataset.style; curColor = b.dataset.color;
                            render();
                        });
                    });
                    document.getElementById('gsd-copy-url').addEventListener('click', e => {
                        navigator.clipboard.writeText(buildUrl().replace(API_KEY, '***'));
                        e.currentTarget.innerHTML = '✓ Copied';
                        setTimeout(() => e.currentTarget.innerHTML = '📋 Copy', 1500);
                    });
                    // Render saat panel pertama kali ditampilkan
                    const observer = new MutationObserver(() => {
                        if (document.getElementById('op-maps-get-style-descriptor').classList.contains('active') && !map) {
                            render();
                        }
                    });
                    observer.observe(document.getElementById('op-maps-get-style-descriptor'), { attributes: true });
                    if (document.getElementById('op-maps-get-style-descriptor').classList.contains('active')) render();
                })();
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/style-descriptor?key=...</span></div>
                    <div class="alert-mini warn" data-i18n-html="gsd_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li>Pakai <code>{MapName}</code> custom resource (harus dibuat dulu di Console)</li>
                            <li>Tidak ada <code>color-scheme</code> / <code>political-view</code> param</li>
                            <li>Provider lock per resource — gak bisa switch style runtime</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetTile -->
            <div class="op-panel" id="op-maps-get-tile">
                <div class="breadcrumb-mini">Maps V2 / GetTile</div>
                <h1>GetTile</h1>
                <p class="op-desc" data-i18n="gt_desc">Vector / raster tile per koordinat z/x/y. URL pattern ini ada di field <code>tiles</code> dalam style descriptor — biasanya gak perlu di-call manual.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/tiles/{Style}/{ColorScheme}/{Variant}/{z}/{x}/{y}?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Path / Query Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Param</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>{Style}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_styles_all">Standard | Monochrome | Hybrid | Satellite</td></tr>
                        <tr><td><code>{ColorScheme}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_light_dark">Light | Dark | Default</td></tr>
                        <tr><td><code>{Variant}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_default_only">Default (for now)</td></tr>
                        <tr><td><code>{z}/{x}/{y}</code></td><td><span class="type-tag">path int</span></td><td data-i18n="gt_p_zxy">Tile coordinate (z 0-22, x/y per zoom level)</td></tr>
                        <tr><td><code>key</code></td><td><span class="type-tag">query</span></td><td data-i18n="note_api_key">API key</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">400</span></td><td data-i18n="gt_e_400_short">z/x/y out of valid range</td><td data-i18n="gt_e_400_note">E.g. y <td>Mis. y &gt; 2^z - 1</td>gt; 2^z - 1</td></tr>
                        <tr><td><span class="err-code">404</span></td><td data-i18n="gt_e_404_short">Tile not available in this area</td><td data-i18n="gt_e_404_note">E.g. coordinate outside provider coverage</td></tr>
                        <tr><td><span class="err-code">403</span></td><td data-i18n-html="gt_e_perm">API Key lacks <code>geo-maps:GetTile</code></td><td data-i18n="note_perm_missing">Permission missing</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Content-Type</div>
                <table class="param-table">
                    <thead><tr><th>Style</th><th>Content-Type</th><th>Format</th></tr></thead>
                    <tbody>
                        <tr><td>Standard / Monochrome</td><td><code>application/x-protobuf</code></td><td data-i18n="gt_r_vector">Vector tile (PBF) — rendered by MapLibre client-side</td></tr>
                        <tr><td>Hybrid / Satellite</td><td><code>image/png</code> atau <code>image/jpeg</code></td><td data-i18n="gt_r_raster">Raster tile, rendered as-is</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>
                <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gt_try_hint">
                    💡 Use the z/x/y picker to view a specific tile. Vector tile (PBF) cannot be previewed directly — use MapLibre to render it.
                </div>

                <div class="preset-row">
                    <span class="preset-label">z/x/y</span>
                    <input id="gt-z" type="number" value="11" min="0" max="22" style="width:60px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                    <input id="gt-x" type="number" value="1656" style="width:80px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                    <input id="gt-y" type="number" value="1057" style="width:80px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                    <select id="gt-style" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>Standard</option>
                        <option>Monochrome</option>
                    </select>
                    <button class="preset-btn" id="gt-apply">Apply</button>
                </div>
                <div class="resp-bar">
                    <span style="font-weight:700;">URL</span>
                    <code id="gt-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                    <button class="btn-copy" id="gt-open" style="background:#3b82f6;color:#fff;border:0;">↗ Open</button>
                </div>
                <p style="font-size:0.78rem;color:var(--text-muted);">⬆️ Klik <strong>Open</strong> untuk download tile. Vector PBF (Standard/Monochrome) terlihat sebagai binary file. Raster (Satellite/Hybrid) terlihat sebagai gambar.</p>

                <script>
                (function() {
                    const REGION = "{{ env('AWS_REGION') }}";
                    const API_KEY = "{{ env('AWS_API_KEY') }}";
                    function build() {
                        const z = document.getElementById('gt-z').value;
                        const x = document.getElementById('gt-x').value;
                        const y = document.getElementById('gt-y').value;
                        const s = document.getElementById('gt-style').value;
                        return `https://maps.geo.${REGION}.amazonaws.com/v2/tiles/${s}/Default/Default/${z}/${x}/${y}?key=${API_KEY}`;
                    }
                    function refresh() {
                        document.getElementById('gt-url').textContent = build().replace(API_KEY, '***');
                    }
                    ['gt-z','gt-x','gt-y','gt-style'].forEach(id => document.getElementById(id).addEventListener('input', refresh));
                    document.getElementById('gt-apply').addEventListener('click', refresh);
                    document.getElementById('gt-open').addEventListener('click', () => window.open(build(), '_blank'));
                    refresh();
                })();
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/tiles/{z}/{x}/{y}?key=...</span></div>
                    <div class="alert-mini warn" data-i18n-html="gt_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li>Path lebih sederhana: <code>{MapName}/tiles/{z}/{x}/{y}</code></li>
                            <li>Tidak ada Style/ColorScheme/Variant — provider locked di resource</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetGlyphs -->
            <div class="op-panel" id="op-maps-get-glyphs">
                <div class="breadcrumb-mini">Maps V2 / GetGlyphs</div>
                <h1>GetGlyphs</h1>
                <p class="op-desc" data-i18n="gg_desc">Font glyphs (PBF) untuk text rendering di vector tiles. Auto-fetched oleh MapLibre, gak perlu di-call manual.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/glyphs/{Style}/{fontstack}/{range}?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Path Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Param</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>{Style}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_styles_v2">Standard | Monochrome</td></tr>
                        <tr><td><code>{fontstack}</code></td><td><span class="type-tag">path</span></td><td>Nama font (mis. <code>Noto Sans Regular</code>) — URL-encoded</td></tr>
                        <tr><td><code>{range}</code></td><td><span class="type-tag">path</span></td><td>Unicode range, 256 chars per chunk: <code>0-255</code>, <code>256-511</code>, dst.</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Content-Type</div>
                <p><code>application/x-protobuf</code> — binary PBF berisi font glyphs. Di-decode sama MapLibre untuk render label di map.</p>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">404</span></td><td data-i18n="gg_e_404_short">Fontstack not available</td><td data-i18n="gg_e_404_note">See available fonts in the style descriptor</td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n="gg_e_400_short">Invalid range</td><td data-i18n="gg_e_400_note">Max range usually up to 65279 (basic Unicode)</td></tr>
                        <tr><td><span class="err-code">403</span></td><td data-i18n="note_perm_missing">Permission missing</td><td><code>geo-maps:GetGlyphs</code> gak di-grant</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>
                <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gg_try_hint">
                    💡 Glyphs PBF cannot be previewed directly. Shown here: URL builder + open button to download the file.
                </div>

                <div class="preset-row">
                    <span class="preset-label">Style</span>
                    <select id="gg-style" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>Standard</option><option>Monochrome</option>
                    </select>
                    <span class="preset-label">Font</span>
                    <select id="gg-font" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>Noto Sans Regular</option>
                        <option>Noto Sans Bold</option>
                        <option>Noto Sans Italic</option>
                    </select>
                    <span class="preset-label">Range</span>
                    <select id="gg-range" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>0-255</option><option>256-511</option><option>4096-4351</option>
                    </select>
                </div>
                <div class="resp-bar">
                    <span style="font-weight:700;">URL</span>
                    <code id="gg-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                    <button class="btn-copy" id="gg-open" style="background:#3b82f6;color:#fff;border:0;">↗ Open</button>
                </div>

                <script>
                (function() {
                    const REGION = "{{ env('AWS_REGION') }}";
                    const API_KEY = "{{ env('AWS_API_KEY') }}";
                    function build() {
                        const s = document.getElementById('gg-style').value;
                        const f = encodeURIComponent(document.getElementById('gg-font').value);
                        const r = document.getElementById('gg-range').value;
                        return `https://maps.geo.${REGION}.amazonaws.com/v2/glyphs/${s}/${f}/${r}?key=${API_KEY}`;
                    }
                    function refresh() { document.getElementById('gg-url').textContent = build().replace(API_KEY, '***'); }
                    ['gg-style','gg-font','gg-range'].forEach(id => document.getElementById(id).addEventListener('change', refresh));
                    document.getElementById('gg-open').addEventListener('click', () => window.open(build(), '_blank'));
                    refresh();
                })();
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/glyphs/{fontstack}/{range}?key=...</span></div>
                    <div class="alert-mini warn" data-i18n-html="gg_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li>Path: <code>{MapName}/glyphs/{fontstack}/{range}</code> (tanpa Style)</li>
                            <li>Font set tergantung MapName resource</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetSprites -->
            <div class="op-panel" id="op-maps-get-sprites">
                <div class="breadcrumb-mini">Maps V2 / GetSprites</div>
                <h1>GetSprites</h1>
                <p class="op-desc" data-i18n="gsp_desc">Sprite sheet (PNG + JSON) untuk icon point-of-interest di map. Auto-fetched oleh MapLibre.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method GET">GET</span><span>https://maps.geo.{region}.amazonaws.com/v2/sprites/{Style}/{ColorScheme}/{Variant}/{file}?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Path Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Param</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>{Style}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_styles_v2">Standard | Monochrome</td></tr>
                        <tr><td><code>{ColorScheme}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_light_dark">Light | Dark | Default</td></tr>
                        <tr><td><code>{Variant}</code></td><td><span class="type-tag">path</span></td><td data-i18n="note_default">Default</td></tr>
                        <tr><td><code>{file}</code></td><td><span class="type-tag">path</span></td><td><code>sprites.json</code> | <code>sprites.png</code> | <code>sprites@2x.json</code> | <code>sprites@2x.png</code></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Content-Type</div>
                <table class="param-table">
                    <thead><tr><th>File</th><th>Content-Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>sprites.json</code></td><td><code>application/json</code></td><td data-i18n="gsp_r_json">Manifest: coordinates &amp; size of each icon in the sheet</td></tr>
                        <tr><td><code>sprites.png</code></td><td><code>image/png</code></td><td data-i18n="gsp_r_png">Sheet image — all icons in one PNG</td></tr>
                        <tr><td><code>sprites@2x.png</code></td><td><code>image/png</code></td><td data-i18n="gsp_r_2x">@2x version for retina display</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>
                <div class="alert-mini info" style="margin-bottom:14px;" data-i18n="gsp_try_hint">
                    💡 PNG sprites can be previewed directly. JSON manifest can be fetched to inspect structure.
                </div>

                <div class="preset-row">
                    <span class="preset-label">Style</span>
                    <select id="gsp-style" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>Standard</option><option>Monochrome</option>
                    </select>
                    <span class="preset-label">Color</span>
                    <select id="gsp-color" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>Light</option><option>Dark</option>
                    </select>
                    <span class="preset-label">File</span>
                    <select id="gsp-file" style="padding:5px 8px;border:1px solid #cbd5e1;border-radius:5px;font-size:0.84rem;">
                        <option>sprites.png</option><option>sprites@2x.png</option><option>sprites.json</option>
                    </select>
                </div>
                <div class="resp-bar">
                    <span style="font-weight:700;">URL</span>
                    <code id="gsp-url" style="flex:1;word-break:break-all;font-size:0.74rem;background:transparent;padding:0;"></code>
                    <button class="btn-copy" id="gsp-open" style="background:#3b82f6;color:#fff;border:0;">↗ Open</button>
                </div>
                <div id="gsp-preview" style="margin-top:14px;padding:14px;background:#f8fafc;border:1px solid var(--border-light);border-radius:8px;text-align:center;">
                    <img id="gsp-img" style="max-width:100%;background:#fff;border-radius:6px;" alt="Sprite preview">
                </div>

                <script>
                (function() {
                    const REGION = "{{ env('AWS_REGION') }}";
                    const API_KEY = "{{ env('AWS_API_KEY') }}";
                    function build() {
                        const s = document.getElementById('gsp-style').value;
                        const c = document.getElementById('gsp-color').value;
                        const f = document.getElementById('gsp-file').value;
                        return `https://maps.geo.${REGION}.amazonaws.com/v2/sprites/${s}/${c}/Default/${f}?key=${API_KEY}`;
                    }
                    function refresh() {
                        const url = build();
                        document.getElementById('gsp-url').textContent = url.replace(API_KEY, '***');
                        const file = document.getElementById('gsp-file').value;
                        const imgEl = document.getElementById('gsp-img');
                        if (file.endsWith('.png')) {
                            imgEl.src = url;
                            imgEl.style.display = '';
                        } else {
                            imgEl.style.display = 'none';
                        }
                    }
                    ['gsp-style','gsp-color','gsp-file'].forEach(id => document.getElementById(id).addEventListener('change', refresh));
                    document.getElementById('gsp-open').addEventListener('click', () => window.open(build(), '_blank'));
                    refresh();
                })();
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/sprites?key=...</span></div>
                    <div class="alert-mini warn" data-i18n-html="gsp_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li>Path: <code>{MapName}/sprites</code> (tanpa Style/Color/Variant/file split)</li>
                            <li>v0 punya endpoint terpisah untuk JSON vs PNG (mis. <code>/sprites?json=true</code>)</li>
                            <li>Sprite set lock per provider</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetStaticMap -->
            <div class="op-panel" id="op-maps-get-static-map">
                <div class="breadcrumb-mini">Maps V2 / GetStaticMap</div>
                <h1>GetStaticMap <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="gsm_desc">Render map jadi gambar PNG/JPEG single. Cocok untuk thumbnail, preview di card, email, atau social sharing.</p>

                <div class="alert-mini soon" data-i18n-html="soon_static"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Maps Actions in Console only has <code>GetTile</code>. Workaround: screenshot from MapLibre canvas.</div>

                <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/static-map?key=...&amp;...</span></div>

                <h4>Query parameters</h4>
                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Param</th>
                            <th>Type</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>Center</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">lng,lat</span></td>
                            <td data-i18n="gsm_p_center">Map center</td>
                        </tr>
                        <tr>
                            <td><code>Zoom</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td data-i18n="note_zoom_range">0-22</td>
                        </tr>
                        <tr>
                            <td><code>Width</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td data-i18n="note_pixel">Pixel</td>
                        </tr>
                        <tr>
                            <td><code>Height</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td data-i18n="note_pixel">Pixel</td>
                        </tr>
                        <tr>
                            <td><code>Style</code></td>
                            <td>—</td>
                            <td data-i18n="note_styles_all">Standard | Monochrome | Hybrid | Satellite</td>
                        </tr>
                        <tr>
                            <td><code>FileFormat</code></td>
                            <td>—</td>
                            <td data-i18n="note_png_jpeg">png | jpeg</td>
                        </tr>
                        <tr>
                            <td><code>Pins</code></td>
                            <td><span class="type-tag">array</span></td>
                            <td data-i18n="gsm_p_pins">Optional marker overlay</td>
                        </tr>
                    </tbody>
                </table>

                <h4>Contoh</h4>
                <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/static-map?key=...&amp;Center=106.84,-6.20&amp;Zoom=14&amp;Width=600&amp;Height=400&amp;Style=Standard</span></div>
                <p>Hasil: gambar PNG 600×400 pusat di Sudirman zoom 14.</p>
            </div>

            {{-- =============================================================== --}}
            {{-- PLACES OPERATIONS                                              --}}
            {{-- =============================================================== --}}

            <!-- SearchText -->
            <div class="op-panel" id="op-places-search-text">
                <div class="breadcrumb-mini">Places V2 / SearchText</div>
                <h1>SearchText</h1>
                <p class="op-desc" data-i18n="st_desc">Pencarian Place (POI / alamat / area) berbasis free-form text. Cocok untuk search bar dengan tombol "Search". Mendukung bias position, filter geografis (BoundingBox/Circle/Country), dan filter kategori.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">

                    {{-- =================================================================== --}}
                    {{-- V2 TAB                                                              --}}
                    {{-- =================================================================== --}}
                    <div data-version="v2" class="active">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/v2/search-text?key=...</span></div>

                        {{-- ====================== REQUEST ====================== --}}
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
                        <pre><code class="language-json">{
                            "QueryText": "string",
                            "QueryId": "string",
                            "MaxResults": number,
                            "BiasPosition": [ number, number ],
                            "Filter": {
                                "BoundingBox": [ number, number, number, number ],
                                "Circle": {
                                "Center": [ number, number ],
                                "Radius": number
                                },
                                "IncludeCountries": [ "string" ]
                            },
                            "AdditionalFeatures": [ "string" ],
                            "Language": "string",
                            "PoliticalView": "string",
                            "IntendedUse": "string",
                            "NextToken": "string"
                            }</code></pre>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>QueryText</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td><span class="req">YES*</span></td>
                                    <td data-i18n-html="st_p_querytext">Free-form keyword (1-200 char). *Required: one of <code>QueryText</code> or <code>QueryId</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>QueryId</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td data-i18n="st_p_queryid">Alternative: ID from a previous Suggest result</td>
                                </tr>
                                <tr>
                                    <td><code>MaxResults</code></td>
                                    <td><span class="type-tag">number</span></td>
                                    <td>—</td>
                                    <td data-i18n="note_max_20">1–20, default 20</td>
                                </tr>
                                <tr>
                                    <td><code>BiasPosition</code></td>
                                    <td><span class="type-tag">[lng, lat]</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="st_p_bias">Bias ranking + reference for the <code>Distance</code> field. <strong>Exactly 1</strong> of BiasPosition / Filter.BoundingBox / Filter.Circle.<br><strong>📌 Use this if you need Distance</strong> — Filter.Circle/BoundingBox don't trigger Distance in <code>ap-southeast-1</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.BoundingBox</code></td>
                                    <td><span class="type-tag">[w, s, e, n]</span></td>
                                    <td>—</td>
                                    <td data-i18n="st_p_bbox">Limit results to a box (west, south, east, north)</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.Circle</code></td>
                                    <td><span class="type-tag">object</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_circle"><code>Center: [lng,lat]</code>, <code>Radius: meter</code> (max 50000)</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.IncludeCountries</code></td>
                                    <td><span class="type-tag">array&lt;string&gt;</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_iso3_arr">ISO-3 country codes, e.g. <code>["IDN"]</code></td>
                                </tr>
                                <tr>
                                    <td><code>AdditionalFeatures</code></td>
                                    <td><span class="type-tag">array&lt;string&gt;</span></td>
                                    <td>—</td>
                                    <td><code>Contact</code> | <code>TimeZone</code> | <code>Phonemes</code> | <code>Access</code></td>
                                </tr>
                                <tr>
                                    <td><code>Language</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_bcp47">BCP 47 (e.g. <code>id</code>, <code>en</code>)</td>
                                </tr>
                                <tr>
                                    <td><code>PoliticalView</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_iso3_single">ISO-3 country code (e.g. <code>IDN</code>)</td>
                                </tr>
                                <tr>
                                    <td><code>IntendedUse</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td data-i18n-html="note_intended_use"><code>SingleUse</code> (default) | <code>Storage</code></td>
                                </tr>
                                <tr>
                                    <td><code>NextToken</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td data-i18n="note_pagination">Pagination cursor</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- ====================== FIELD RULES ====================== --}}
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules — yang bisa &amp; tidak bisa di-combo</span></div>

                        <div class="rules-grid">
                            <div class="rule-card required">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-check-square-fill"></i></span>
                                    <span data-i18n="rule_required">Required (one-of)</span>
                                </div>
                                <div class="field-list">
                                    <code>QueryText</code>
                                    <span class="sep">OR</span>
                                    <code>QueryId</code>
                                </div>
                                <div class="rule-note" data-i18n="rule_required_note">One of these must be in the body. Both can be sent but AWS picks based on internal priority.</div>
                            </div>

                            <div class="rule-card exclusive">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-x-octagon-fill"></i></span>
                                    <span data-i18n="rule_exclusive">Mutually exclusive (exactly one)</span>
                                </div>
                                <div class="field-list">
                                    <code>BiasPosition</code>
                                    <span class="sep">XOR</span>
                                    <code>Filter.BoundingBox</code>
                                    <span class="sep">XOR</span>
                                    <code>Filter.Circle</code>
                                </div>
                                <div class="rule-note" data-i18n-html="rule_exclusive_note">
                                    Sending 2 or more = 400 error <em>"Exactly one of..."</em>. Sending none → no spatial bias (global results).<br>
                                    💡 <strong>Trade-off:</strong>
                                    <br>· <code>BiasPosition</code> → ranking-bias + <code>Distance</code> field in response
                                    <br>· <code>Filter.Circle</code> / <code>BoundingBox</code> → hard geographic cut-off but <strong>no Distance</strong> (empirical in <code>ap-southeast-1</code>)
                                </div>
                            </div>

                            <div class="rule-card combo">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-puzzle-fill"></i></span>
                                    <span data-i18n="rule_combo">Can be combined</span>
                                </div>
                                <div class="field-list">
                                    <code>Filter.IncludeCountries</code>
                                    <span class="sep">+</span>
                                    <code>BiasPosition</code>/<code>Circle</code>/<code>BoundingBox</code>
                                </div>
                                <div class="rule-note" data-i18n="rule_combo_note">IncludeCountries is an additive filter (AND), not exclusive — can be combined with any spatial filter.</div>
                            </div>

                            <div class="rule-card combo">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-layers-fill"></i></span>
                                    <span data-i18n="rule_independent">Independent (use freely)</span>
                                </div>
                                <div class="field-list">
                                    <code>MaxResults</code>
                                    <code>Language</code>
                                    <code>PoliticalView</code>
                                    <code>AdditionalFeatures</code>
                                    <code>IntendedUse</code>
                                    <code>NextToken</code>
                                </div>
                                <div class="rule-note" data-i18n="rule_independent_note">These fields have no conflict constraints with each other. Use any combination or none at all.</div>
                            </div>
                        </div>

                        {{-- ====================== COMMON ERRORS ====================== --}}
                        <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors &amp; Validation</span></div>

                        <table class="error-table">
                            <thead>
                                <tr>
                                    <th data-i18n="err_status">Status</th>
                                    <th data-i18n="err_trigger">Trigger</th>
                                    <th data-i18n="err_message">AWS Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="err_t1">2+ of <code>BiasPosition</code> / <code>Filter.BoundingBox</code> / <code>Filter.Circle</code> sent together</td>
                                    <td><em>"Exactly one of the following fields must be set: BiasPosition, Filter.BoundingBox, Filter.Circle."</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="err_t2">No <code>QueryText</code> and no <code>QueryId</code></td>
                                    <td><em>"Either QueryText or QueryId is required."</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="err_t3"><code>MaxResults</code> &gt; 20</td>
                                    <td><em>"Member must have value less than or equal to 20"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="err_t4"><code>Filter.Circle.Radius</code> &gt; 50000</td>
                                    <td><em>"Member must have value less than or equal to 50000"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="err_t5">Wrong coordinate format (e.g. <code>[lat, lng]</code> instead of <code>[lng, lat]</code>)</td>
                                    <td data-i18n-html="err_m5"><em>Weird / empty result</em> — AWS doesn't validate ranges, coords are accepted as-is</td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td data-i18n-html="err_t6"><code>Filter.IncludeCountries</code> not ISO-3 (e.g. "Indonesia" or "ID")</td>
                                    <td><em>"Validation failed: country code must be 3 letters"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td data-i18n-html="err_t7">API Key lacks action <code>geo-places:SearchText</code></td>
                                    <td><em>"User is not authorized to access this resource with an explicit deny"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td data-i18n-html="err_t8">API Key wrong or missing <code>?key=</code></td>
                                    <td><em>"The security token included in the request is invalid"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">429</span></td>
                                    <td data-i18n="err_t9">Rate limit exceeded (default 50 TPS)</td>
                                    <td data-i18n-html="err_m9"><em>"Rate exceeded"</em> — implement retry with backoff</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- ====================== RESPONSE ====================== --}}
                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
                        <pre><code class="language-json">{
                            "PricingBucket": "string",
                            "NextToken": "string",
                            "ResultItems": [
                                {
                                "PlaceId": "string",
                                "PlaceType": "string",
                                "Title": "string",
                                "Address": {
                                    "Label": "string",
                                    "Country": { "Code2": "string", "Name": "string" },
                                    "Region": { "Code": "string", "Name": "string" },
                                    "Locality": "string",
                                    "PostalCode": "string"
                                },
                                "Position": [ number, number ],
                                "MapView": [ number, number, number, number ],
                                "Distance": number,
                                "Categories": [ { "Id": "string", "Name": "string", "Primary": boolean } ],
                                "TimeZone": { "Name": "string", "OffsetSeconds": number }
                                }
                            ]
                            }</code></pre>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>ResultItems[].PlaceId</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td data-i18n="r_placeid">Unique AWS ID — use with GetPlace to fetch full detail</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].PlaceType</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td data-i18n="r_placetype_examples">PointOfInterest | Address | Street | District | Region | etc.</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Title</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td data-i18n="r_title">Main display name</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Position</code></td>
                                    <td><span class="type-tag">[lng, lat]</span></td>
                                    <td data-i18n="r_position">Center coordinate of the place</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Distance</code></td>
                                    <td><span class="type-tag">number</span></td>
                                    <td data-i18n-html="r_distance_long">
                                        <strong>Meters</strong> from reference point.<br>
                                        ⚠️ <strong>Empirical in <code>ap-southeast-1</code>:</strong> this field only appears when the request uses <code>BiasPosition</code>. When using <code>Filter.Circle</code> or <code>Filter.BoundingBox</code>, the <code>Distance</code> field is <strong>absent</strong> from the response.<br>
                                        Workaround: compute Haversine in JS from <code>Position</code> to the origin.
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Address</code></td>
                                    <td><span class="type-tag">object</span></td>
                                    <td data-i18n="r_address">Structured address (label + components)</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].MapView</code></td>
                                    <td><span class="type-tag">[w,s,e,n]</span></td>
                                    <td data-i18n="r_mapview">Bounding box for fitting the map</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Categories</code></td>
                                    <td><span class="type-tag">array</span></td>
                                    <td>Kategori AWS (mis. <code>transit_station_bus</code>)</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- ====================== TRY IT LIVE (V2 only) ====================== --}}
                        <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                        {{-- Preset buttons — kombinasi valid sesuai field rules --}}
                        <div class="preset-row">
                            <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                            <button class="preset-btn" data-preset="bias">📍 BiasPosition <span class="pico" data-i18n="preset_simple">simple</span></button>
                            <button class="preset-btn" data-preset="circle">⭕ Filter.Circle <span class="pico" data-i18n="preset_radius">radius 2km</span></button>
                            <button class="preset-btn" data-preset="bbox">🟦 Filter.BoundingBox <span class="pico">Jakarta</span></button>
                            <button class="preset-btn" data-preset="minimal">📝 <span data-i18n="preset_minimal">Minimal</span> <span class="pico">QueryText only</span></button>
                            <button class="preset-btn" data-preset="full">🎛️ <span data-i18n="preset_all">All Features</span> <span class="pico">+TimeZone</span></button>
                            <button class="preset-btn" data-preset="error">💥 <span data-i18n="preset_error">Error case</span> <span class="pico" data-i18n="preset_error_desc">2 spatial filters</span></button>
                        </div>

                        <div class="try-it">
                            <div class="try-it-pane right" style="border-right:0;">
                                <div class="try-it-pane-header">
                                    <span>
                                        <i class="bi bi-code-slash"></i> Request Body
                                        <span class="json-status ok" id="st-json-status">VALID</span>
                                    </span>
                                    <div style="display:flex;gap:6px;">
                                        <button class="btn-copy" onclick="copyToClipboard('st-req-preview', this)"><span data-i18n="btn_copy">📋 Copy</span></button>
                                        <button class="btn-copy" id="st-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                                    </div>
                                </div>
                                <div class="try-it-url" style="display:flex;flex-direction:column;gap:4px;">
                                    <div>
                                        <span class="try-it-method">POST</span>
                                        <span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/search-text?key=***</span>
                                        <span style="color:#64748b;font-size:0.7rem;margin-left:6px;">(canonical AWS URL — reference)</span>
                                    </div>
                                </div>
                                <textarea class="json-editor" id="st-req-preview" spellcheck="false"></textarea>
                                <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                                    <button class="btn-send" id="st-run" type="button">
                                        <i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span>
                                    </button>
                                    <span id="st-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">
                                        <i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Calling AWS...
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Response area --}}
                        <div class="resp-bar">
                            <span style="font-weight:700;color:var(--text-primary);">Response</span>
                            <span class="status-pill idle" id="st-status">— idle —</span>
                            <span class="meta" id="st-meta"></span>
                            <button class="btn-copy" id="st-resp-copy" style="margin-left:auto;background:#e2e8f0;color:#334155;border:1px solid #cbd5e1;display:none;"><span data-i18n="btn_copy_response">📋 Copy Response</span></button>
                        </div>
                        <div id="st-resp" class="resp-body empty" data-i18n="resp_idle">Klik Send Request di atas untuk lihat live response dari AWS.</div>

                        <style>
                            @keyframes spin {
                                from {
                                    transform: rotate(0)
                                }

                                to {
                                    transform: rotate(360deg)
                                }
                            }
                        </style>

                        <script>
                            // SearchText — Try it Live (logic generic ada di aws-api-try-it.js)
                            AWSAPI_TryIt.init({
                                prefix: 'st',
                                panelId: 'op-places-search-text',
                                proxy: '/api/places/search',
                                defaultPreset: 'bias',
                                presets: {
                                    bias: { QueryText: 'halte TransJakarta', BiasPosition: [106.8456, -6.2088], MaxResults: 5, Language: 'id', Filter: { IncludeCountries: ['IDN'] } },
                                    circle: { QueryText: 'halte TransJakarta', Filter: { Circle: { Center: [106.8456, -6.2088], Radius: 2000 }, IncludeCountries: ['IDN'] }, MaxResults: 10, Language: 'id' },
                                    bbox: { QueryText: 'stasiun', Filter: { BoundingBox: [106.689, -6.371, 106.971, -6.089], IncludeCountries: ['IDN'] }, MaxResults: 10, Language: 'id' },
                                    minimal: { QueryText: 'Monas Jakarta' },
                                    full: { QueryText: 'halte TransJakarta', BiasPosition: [106.8456, -6.2088], Filter: { IncludeCountries: ['IDN'] }, MaxResults: 10, Language: 'id', PoliticalView: 'IDN', AdditionalFeatures: ['TimeZone'], IntendedUse: 'SingleUse' },
                                    error: { QueryText: 'halte', BiasPosition: [106.8456, -6.2088], Filter: { Circle: { Center: [106.8456, -6.2088], Radius: 2000 } } }
                                }
                            });
                        </script>

                    </div> {{-- end v2 tab --}}

                    {{-- =================================================================== --}}
                    {{-- V0 TAB                                                              --}}
                    {{-- =================================================================== --}}
                    <div data-version="v0">

                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/places/v0/indexes/{IndexName}/search/text?key=...</span></div>

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
                        <pre><code class="language-json">{
  "Text": "halte TJ",
  "BiasPosition": [106.84, -6.20],
  "MaxResults": 10,
  "FilterCountries": ["IDN"]
}</code></pre>

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Body</div>
                        <pre><code class="language-json">{
  "Summary": { "Text": "halte TJ", "MaxResults": 10, "ResultBBox": [...] },
  "Results": [
    {
      "Place": {
        "Label": "Halte TJ Halimun, Jakarta",
        "Geometry": { "Point": [106.85, -6.24] },
        "Country": "IDN",
        "AddressNumber": "5"
      },
      "Distance": 850.42
    }
  ]
}</code></pre>

                        <div class="alert-mini warn" data-i18n-html="st_v0_diff">
                            <strong>Differences from v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Field <code>Text</code> → <code>QueryText</code></li>
                                <li><code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                                <li>Response: <code>Results[].Place.Geometry.Point</code> → <code>ResultItems[].Position</code></li>
                                <li><code>MaxResults</code> max 10 (v2: 20)</li>
                                <li>Must create <code>PlaceIndex</code> resource first in AWS Console</li>
                            </ul>
                        </div>

                    </div> {{-- end v0 tab --}}

                </div> {{-- end ver-content --}}
            </div>

            <!-- Suggest -->
            <div class="op-panel" id="op-places-suggest">
                <div class="breadcrumb-mini">Places V2 / Suggest</div>
                <h1>Suggest</h1>
                <p class="op-desc" data-i18n="sg_desc">Type-ahead autocomplete — return Place hits + Query refinement. Pakai untuk dropdown live di search bar.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/v2/suggest?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
<pre><code class="language-json">{
  "QueryText": "string",
  "MaxResults": number,
  "BiasPosition": [ number, number ],
  "Filter": {
    "BoundingBox": [ number, number, number, number ],
    "Circle": { "Center": [ number, number ], "Radius": number },
    "IncludeCountries": [ "string" ]
  },
  "AdditionalFeatures": [ "string" ],
  "Language": "string",
  "PoliticalView": "string",
  "MaxQueryRefinements": number,
  "IntendedUse": "string"
}</code></pre>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>QueryText</code></td><td><span class="type-tag">string</span></td><td><span class="req">YES</span></td><td>1–200 char. Partial keyword OK (autocomplete).</td></tr>
                        <tr><td><code>MaxResults</code></td><td><span class="type-tag">number</span></td><td>—</td><td data-i18n="note_max_10">1–10, default 5</td></tr>
                        <tr><td><code>BiasPosition</code></td><td><span class="type-tag">[lng, lat]</span></td><td>—</td><td><strong>Exactly 1</strong> dari Bias / Filter.Circle / Filter.BoundingBox.</td></tr>
                        <tr><td><code>Filter.Circle</code></td><td><span class="type-tag">object</span></td><td>—</td><td><code>Center: [lng,lat]</code>, <code>Radius: meter</code> (max 50000)</td></tr>
                        <tr><td><code>Filter.BoundingBox</code></td><td><span class="type-tag">[w,s,e,n]</span></td><td>—</td><td>west, south, east, north</td></tr>
                        <tr><td><code>Filter.IncludeCountries</code></td><td><span class="type-tag">array</span></td><td>—</td><td data-i18n="note_iso3_codes">ISO-3 codes</td></tr>
                        <tr><td><code>MaxQueryRefinements</code></td><td><span class="type-tag">number</span></td><td>—</td><td data-i18n="sg_p_refine">Max items with SuggestResultItemType=Query</td></tr>
                        <tr><td><code>Language</code></td><td><span class="type-tag">string</span></td><td>—</td><td data-i18n-html="note_bcp47_id">BCP 47 (e.g. <code>id</code>)</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                <div class="rules-grid">
                    <div class="rule-card required">
                        <div class="rule-header"><span class="ic"><i class="bi bi-check-square-fill"></i></span> <span data-i18n="rule_required_qt">Required</span></div>
                        <div class="field-list"><code>QueryText</code></div>
                        <div class="rule-note" data-i18n="sg_required_note">Wajib ada QueryText (min 1 karakter). Suggest tidak menerima QueryId seperti SearchText.</div>
                    </div>
                    <div class="rule-card exclusive">
                        <div class="rule-header"><span class="ic"><i class="bi bi-x-octagon-fill"></i></span> <span data-i18n="rule_exclusive">Mutually exclusive</span></div>
                        <div class="field-list"><code>BiasPosition</code> <span class="sep">XOR</span> <code>Filter.BoundingBox</code> <span class="sep">XOR</span> <code>Filter.Circle</code></div>
                        <div class="rule-note" data-i18n-html="rule_exclusive_note">Pakai 2+ = error 400.</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-funnel-fill"></i></span> <span data-i18n="sg_response_filter">Response filter (client-side)</span></div>
                        <div class="field-list"><code>SuggestResultItemType</code></div>
                        <div class="rule-note" data-i18n="sg_response_filter_note">Filter SuggestResultItemType === "Place" untuk actual place; "Query" = refinement keyword saja.</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-layers-fill"></i></span> <span data-i18n="rule_independent">Independent</span></div>
                        <div class="field-list"><code>MaxResults</code> <code>Language</code> <code>PoliticalView</code> <code>MaxQueryRefinements</code></div>
                    </div>
                </div>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th data-i18n="err_message">AWS Message</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="err_t1">2+ spatial filter</td><td><em>"Exactly one of..."</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="sg_err_qt">Empty <code>QueryText</code></td><td><em>"QueryText: Member must have length greater than or equal to 1"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td><code>MaxResults</code> &gt; 10</td><td><em>"...less than or equal to 10"</em></td></tr>
                        <tr><td><span class="err-code">403</span></td><td data-i18n-html="sg_err_perm">Action <code>geo-places:Suggest</code> tidak di-grant</td><td><em>"explicit deny"</em></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
<pre><code class="language-json">{
  "PricingBucket": "string",
  "ResultItems": [
    {
      "Title": "string",
      "SuggestResultItemType": "Place" | "Query",
      "Highlights": { "Title": [...] },
      "Place": {
        "PlaceId": "string",
        "PlaceType": "string",
        "Address": { "Label": "string" },
        "Position": [ number, number ],
        "Distance": number,
        "Categories": [ ... ]
      },
      "Query": { "QueryId": "string", "QueryType": "string" }
    }
  ]
}</code></pre>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>SuggestResultItemType</code></td><td><span class="type-tag">enum</span></td><td><code>Place</code> = actual hit, <code>Query</code> = refinement keyword</td></tr>
                        <tr><td><code>Place.Position</code></td><td><span class="type-tag">[lng,lat]</span></td><td data-i18n="r_pos_place_only">Only for SuggestResultItemType=Place</td></tr>
                        <tr><td><code>Place.Distance</code></td><td><span class="type-tag">number</span></td><td data-i18n="r_distance_bias_only">Meters (only when using BiasPosition)</td></tr>
                        <tr><td><code>Highlights.Title</code></td><td><span class="type-tag">array</span></td><td data-i18n="r_highlights">Range index for highlighting matched keywords</td></tr>
                        <tr><td><code>Query.QueryId</code></td><td><span class="type-tag">string</span></td><td data-i18n="r_queryid">Pass to SearchText as QueryId for full search</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                <div class="alert-mini success" style="margin-bottom:14px;">
                    🔒 <span data-i18n="proxy_safe">Aman — routing lewat <code>/api/places/suggestions</code> (Laravel proxy). API key di server.</span>
                </div>

                <div class="preset-row">
                    <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                    <button class="preset-btn" data-preset="bias">📍 BiasPosition</button>
                    <button class="preset-btn" data-preset="circle">⭕ Filter.Circle</button>
                    <button class="preset-btn" data-preset="minimal">📝 <span data-i18n="preset_minimal">Minimal</span></button>
                    <button class="preset-btn" data-preset="full">🎛️ <span data-i18n="preset_all">All Features</span></button>
                </div>

                <div class="try-it">
                    <div class="try-it-pane right" style="border-right:0;">
                        <div class="try-it-pane-header">
                            <span><i class="bi bi-code-slash"></i> Request Body <span class="json-status ok" id="sg-json-status">VALID</span></span>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-copy" onclick="copyToClipboard('sg-req-preview', this)"><span data-i18n="btn_copy">📋 Copy</span></button>
                                <button class="btn-copy" id="sg-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                            </div>
                        </div>
                        <div class="try-it-url">
                            <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/suggest</span></div>
                            <div><span class="try-it-method" style="background:#10b981;">VIA</span><span style="color:#86efac;">/api/places/suggestions</span></div>
                        </div>
                        <textarea class="json-editor" id="sg-req-preview" spellcheck="false"></textarea>
                        <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                            <button class="btn-send" id="sg-run" type="button"><i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span></button>
                            <span id="sg-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">⏳ <span data-i18n="loading">Loading</span>...</span>
                        </div>
                    </div>
                </div>

                <div class="resp-bar">
                    <span style="font-weight:700;color:var(--text-primary);">Response</span>
                    <span class="status-pill idle" id="sg-status">— idle —</span>
                    <span class="meta" id="sg-meta"></span>
                </div>
                <div id="sg-resp" class="resp-body empty" data-i18n="resp_idle">Klik Send Request di atas.</div>

                <script>
                    AWSAPI_TryIt.init({
                        prefix: 'sg',
                        panelId: 'op-places-suggest',
                        proxy: '/api/places/suggestions',
                        defaultPreset: 'bias',
                        presets: {
                            bias: { QueryText: 'halte', BiasPosition: [106.8456, -6.2088], MaxResults: 5, Language: 'id' },
                            circle: { QueryText: 'halte', Filter: { Circle: { Center: [106.8456, -6.2088], Radius: 2000 }, IncludeCountries: ['IDN'] }, MaxResults: 5, Language: 'id' },
                            minimal: { QueryText: 'mon' },
                            full: { QueryText: 'halte', BiasPosition: [106.8456, -6.2088], MaxResults: 10, Language: 'id', PoliticalView: 'IDN', MaxQueryRefinements: 2, AdditionalFeatures: ['Core'], IntendedUse: 'SingleUse' }
                        }
                    });
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method POST">POST</span><span>/places/v0/indexes/{Idx}/search/suggestions?key=...</span></div>
                    <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
<pre><code class="language-json">{
  "Text": "halte tj",
  "BiasPosition": [106.84, -6.20],
  "MaxResults": 5,
  "FilterCountries": ["IDN"]
}</code></pre>
                    <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response</div>
<pre><code class="language-json">{
  "Summary": { "Text": "halte tj" },
  "Results": [
    { "Text": "Halte Transjakarta Halimun", "PlaceId": "AQABA..." }
  ]
}</code></pre>
                    <div class="alert-mini warn" data-i18n-html="sg_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li><code>Text</code> → <code>QueryText</code></li>
                            <li><code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                            <li><code>Results[].Text</code> → <code>ResultItems[].Title</code></li>
                            <li>v0 tidak return Position di Suggest — harus call GetPlace</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- ReverseGeocode -->
            <div class="op-panel" id="op-places-reverse-geocode">
                <div class="breadcrumb-mini">Places V2 / ReverseGeocode</div>
                <h1>ReverseGeocode</h1>
                <p class="op-desc" data-i18n="rg_desc">Koordinat → alamat terdekat.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method POST">POST</span><span>https://places.geo.{region}.amazonaws.com/v2/reverse-geocode?key=...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> <span data-i18n="sec_request_syntax">Request Syntax</span></div>
<pre><code class="language-json">{
  "QueryPosition": [ number, number ],
  "QueryRadius": number,
  "MaxResults": number,
  "Filter": {
    "IncludePlaceTypes": [ "string" ]
  },
  "AdditionalFeatures": [ "string" ],
  "Language": "string",
  "PoliticalView": "string",
  "IntendedUse": "string"
}</code></pre>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Request Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>QueryPosition</code></td><td><span class="type-tag">[lng, lat]</span></td><td><span class="req">YES</span></td><td data-i18n="rg_p_qpos">Coordinate point to reverse-geocode</td></tr>
                        <tr><td><code>QueryRadius</code></td><td><span class="type-tag">number</span></td><td>—</td><td data-i18n="rg_p_radius">Search radius (meters), default 0 (exact point)</td></tr>
                        <tr><td><code>MaxResults</code></td><td><span class="type-tag">number</span></td><td>—</td><td data-i18n="note_max_4">1–4, default 1</td></tr>
                        <tr><td><code>Filter.IncludePlaceTypes</code></td><td><span class="type-tag">array</span></td><td>—</td><td><code>Locality</code> | <code>Street</code> | <code>PointAddress</code> | <code>Block</code> | dst.</td></tr>
                        <tr><td><code>AdditionalFeatures</code></td><td><span class="type-tag">array</span></td><td>—</td><td><code>TimeZone</code> | <code>Access</code></td></tr>
                        <tr><td><code>Language</code></td><td><span class="type-tag">string</span></td><td>—</td><td data-i18n="note_bcp47_short">BCP 47</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                <div class="rules-grid">
                    <div class="rule-card required">
                        <div class="rule-header"><span class="ic"><i class="bi bi-check-square-fill"></i></span> <span data-i18n="rule_required_qt">Required</span></div>
                        <div class="field-list"><code>QueryPosition</code></div>
                        <div class="rule-note" data-i18n="rg_required_note">Wajib koordinat [lng, lat] valid (lng ±180, lat ±90).</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-funnel-fill"></i></span> <span data-i18n="rg_filter_label">Filter PlaceType</span></div>
                        <div class="field-list"><code>Filter.IncludePlaceTypes</code></div>
                        <div class="rule-note" data-i18n="rg_filter_note">Batasi tipe hasil — mis. cuma <code>Street</code> atau <code>PointAddress</code> aja, tanpa <code>Locality</code>/<code>District</code>.</div>
                    </div>
                </div>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th data-i18n="err_message">AWS Message</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="rg_err_pos">Tanpa <code>QueryPosition</code></td><td><em>"QueryPosition is required"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="rg_err_format">Format <code>[lat, lng]</code> (terbalik)</td><td data-i18n-html="rg_err_format_msg">Hasil aneh — AWS treat sebagai [lng, lat]</td></tr>
                        <tr><td><span class="err-code">400</span></td><td><code>MaxResults</code> &gt; 4</td><td><em>"...less than or equal to 4"</em></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
<pre><code class="language-json">{
  "PricingBucket": "string",
  "ResultItems": [
    {
      "PlaceId": "string",
      "PlaceType": "string",
      "Title": "string",
      "Address": {
        "Label": "string",
        "Country": { "Code2": "string", "Name": "string" },
        "Region": { "Code": "string", "Name": "string" },
        "PostalCode": "string"
      },
      "Position": [ number, number ],
      "Distance": number,
      "MapView": [ number, number, number, number ]
    }
  ]
}</code></pre>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_response_fields">Response Fields</span></div>
                <table class="param-table">
                    <thead><tr><th>Field</th><th>Type</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>ResultItems[].Title</code></td><td><span class="type-tag">string</span></td><td data-i18n="r_short_name">Short location name</td></tr>
                        <tr><td><code>ResultItems[].Address.Label</code></td><td><span class="type-tag">string</span></td><td data-i18n="r_full_address">Full formatted address</td></tr>
                        <tr><td><code>ResultItems[].Distance</code></td><td><span class="type-tag">number</span></td><td data-i18n="r_distance_qpos">Meters from QueryPosition</td></tr>
                        <tr><td><code>ResultItems[].PlaceType</code></td><td><span class="type-tag">string</span></td><td>Locality, District, Street, PointAddress, dst.</td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                <div class="alert-mini success" style="margin-bottom:14px;">
                    🔒 <span data-i18n="proxy_safe_rg">Aman — routing lewat <code>/api/places/reverse</code> (Laravel proxy).</span>
                </div>

                <div class="preset-row">
                    <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;<span data-i18n="presets">Presets</span></span>
                    <button class="preset-btn" data-preset="basic">📍 <span data-i18n="rg_preset_basic">Jakarta</span></button>
                    <button class="preset-btn" data-preset="filter">🔧 <span data-i18n="rg_preset_filter">+ Filter Street only</span></button>
                    <button class="preset-btn" data-preset="full">🎛️ <span data-i18n="preset_all">All Features</span></button>
                </div>

                <div class="try-it">
                    <div class="try-it-pane right" style="border-right:0;">
                        <div class="try-it-pane-header">
                            <span><i class="bi bi-code-slash"></i> Request Body <span class="json-status ok" id="rg-json-status">VALID</span></span>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-copy" onclick="copyToClipboard('rg-req-preview', this)"><span data-i18n="btn_copy">📋 Copy</span></button>
                                <button class="btn-copy" id="rg-format-btn" type="button"><span data-i18n="btn_format">✨ Format</span></button>
                            </div>
                        </div>
                        <div class="try-it-url">
                            <div><span class="try-it-method">POST</span><span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/reverse-geocode</span></div>
                            <div><span class="try-it-method" style="background:#10b981;">VIA</span><span style="color:#86efac;">/api/places/reverse</span></div>
                        </div>
                        <textarea class="json-editor" id="rg-req-preview" spellcheck="false"></textarea>
                        <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                            <button class="btn-send" id="rg-run" type="button"><i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span></button>
                            <span id="rg-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">⏳ <span data-i18n="loading">Loading</span>...</span>
                        </div>
                    </div>
                </div>

                <div class="resp-bar">
                    <span style="font-weight:700;color:var(--text-primary);">Response</span>
                    <span class="status-pill idle" id="rg-status">— idle —</span>
                    <span class="meta" id="rg-meta"></span>
                </div>
                <div id="rg-resp" class="resp-body empty" data-i18n="resp_idle">Klik Send Request.</div>

                <script>
                    AWSAPI_TryIt.init({
                        prefix: 'rg',
                        panelId: 'op-places-reverse-geocode',
                        proxy: '/api/places/reverse',
                        defaultPreset: 'basic',
                        presets: {
                            basic: { QueryPosition: [106.8456, -6.2088], MaxResults: 1, Language: 'id' },
                            filter: { QueryPosition: [106.8456, -6.2088], MaxResults: 4, Language: 'id', Filter: { IncludePlaceTypes: ['Street', 'PointAddress'] } },
                            full: { QueryPosition: [106.8456, -6.2088], QueryRadius: 100, MaxResults: 4, Language: 'id', PoliticalView: 'IDN', AdditionalFeatures: ['TimeZone', 'Access'], IntendedUse: 'SingleUse' }
                        }
                    });
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method POST">POST</span><span>/places/v0/indexes/{Idx}/search/position?key=...</span></div>
                    <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Body</div>
<pre><code class="language-json">{
  "Position": [106.84, -6.20],
  "MaxResults": 1
}</code></pre>
                    <div class="alert-mini warn" data-i18n-html="rg_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li><code>Position</code> → <code>QueryPosition</code></li>
                            <li>Tidak ada <code>Filter.IncludePlaceTypes</code> di v0</li>
                            <li>Wajib bikin <code>PlaceIndex</code> resource dulu</li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- GetPlace -->
            <div class="op-panel" id="op-places-get-place">
                <div class="breadcrumb-mini">Places V2 / GetPlace</div>
                <h1>GetPlace</h1>
                <p class="op-desc" data-i18n="gp_desc">Detail lengkap Place by <code>PlaceId</code> (dari hasil Search/Suggest sebelumnya).</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>

                <div class="ver-content">
                <div data-version="v2" class="active">

                <div class="endpoint-line"><span class="method GET">GET</span><span>https://places.geo.{region}.amazonaws.com/v2/place/{PlaceId}?key=...&amp;...</span></div>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> <span data-i18n="sec_request_params">Query Parameters</span></div>
                <table class="param-table">
                    <thead><tr><th>Param</th><th>Type</th><th>Required</th><th>Note</th></tr></thead>
                    <tbody>
                        <tr><td><code>{PlaceId}</code></td><td><span class="type-tag">path</span></td><td><span class="req">YES</span></td><td data-i18n-html="note_placeid_path">Path segment (URL-encoded). From SearchText/Suggest.</td></tr>
                        <tr><td><code>key</code></td><td><span class="type-tag">query</span></td><td><span class="req">YES</span></td><td data-i18n="note_api_key">API key</td></tr>
                        <tr><td><code>additional-features</code></td><td><span class="type-tag">query</span></td><td>—</td><td data-i18n-html="gp_p_addfeat">Comma-separated: <code>TimeZone,Contact,Hours,Phonemes,Access</code></td></tr>
                        <tr><td><code>language</code></td><td><span class="type-tag">query</span></td><td>—</td><td data-i18n-html="note_bcp47">BCP 47 (e.g. <code>id</code>, <code>en</code>)</td></tr>
                        <tr><td><code>political-view</code></td><td><span class="type-tag">query</span></td><td>—</td><td data-i18n="note_iso3_label">ISO-3 country code</td></tr>
                        <tr><td><code>intended-use</code></td><td><span class="type-tag">query</span></td><td>—</td><td><code>SingleUse</code> | <code>Storage</code></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> <span data-i18n="sec_field_rules">Field Rules</span></div>
                <div class="rules-grid">
                    <div class="rule-card required">
                        <div class="rule-header"><span class="ic"><i class="bi bi-check-square-fill"></i></span> <span data-i18n="rule_required_qt">Required</span></div>
                        <div class="field-list"><code>PlaceId</code> (path)</div>
                        <div class="rule-note" data-i18n="gp_required_note">Wajib di URL path. PlaceId valid time-bounded ~1 jam dari saat di-issue Search/Suggest.</div>
                    </div>
                    <div class="rule-card combo">
                        <div class="rule-header"><span class="ic"><i class="bi bi-puzzle-fill"></i></span> <span data-i18n="gp_addfeat">Additional Features</span></div>
                        <div class="field-list"><code>TimeZone</code> <code>Contact</code> <code>Hours</code> <code>Phonemes</code> <code>Access</code></div>
                        <div class="rule-note" data-i18n="gp_addfeat_note">Opsional, comma-separated. Tambah cost per feature. Beberapa region cuma support TimeZone.</div>
                    </div>
                </div>

                <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> <span data-i18n="sec_common_errors">Common Errors</span></div>
                <table class="error-table">
                    <thead><tr><th data-i18n="err_status">Status</th><th data-i18n="err_trigger">Trigger</th><th data-i18n="err_message">AWS Message</th></tr></thead>
                    <tbody>
                        <tr><td><span class="err-code">404</span></td><td data-i18n="gp_err_notfound">PlaceId tidak ditemukan / expired</td><td><em>"Place not found"</em></td></tr>
                        <tr><td><span class="err-code">400</span></td><td data-i18n-html="gp_err_unsupported"><code>additional-features</code> berisi value yang gak disupport region</td><td><em>"Unsupported AdditionalFeatures..."</em></td></tr>
                        <tr><td><span class="err-code">403</span></td><td data-i18n-html="gp_err_perm">API Key tidak punya <code>geo-places:GetPlace</code></td><td><em>"explicit deny"</em></td></tr>
                    </tbody>
                </table>

                <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> <span data-i18n="sec_response_syntax">Response Syntax</span></div>
<pre><code class="language-json">{
  "PlaceId": "string",
  "PlaceType": "string",
  "Title": "string",
  "Address": {
    "Label": "string",
    "Country": { "Code2": "string", "Name": "string" },
    "Region": { "Code": "string", "Name": "string" },
    "Locality": "string",
    "PostalCode": "string"
  },
  "Position": [ number, number ],
  "MapView": [ number, number, number, number ],
  "Categories": [ { "Id": "string", "Name": "string", "Primary": boolean } ],
  "Contacts": {
    "Phones": [ { "Value": "string" } ],
    "Websites": [ { "Value": "string" } ]
  },
  "OpeningHours": [
    { "Display": [ "string" ], "OpenNow": boolean }
  ],
  "TimeZone": { "Name": "string", "Offset": "string", "OffsetSeconds": number }
}</code></pre>

                <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> <span data-i18n="sec_try_live">Try it Live</span></div>

                <div class="alert-mini info" style="margin-bottom:14px;">
                    💡 <span data-i18n="gp_hint">Dapat <code>PlaceId</code> dulu dari Try it Live SearchText/Suggest, copy hasil <code>ResultItems[0].PlaceId</code>, paste ke sini.</span>
                </div>

                <div class="try-it">
                    <div class="try-it-pane right" style="border-right:0;">
                        <div class="try-it-pane-header">
                            <span><i class="bi bi-link-45deg"></i> <span data-i18n="gp_query_params">Query Parameters</span></span>
                        </div>
                        <div class="try-it-url">
                            <div><span class="try-it-method GET">GET</span><span style="color:#fbbf24;">https://places.geo.{{ env('AWS_REGION') }}.amazonaws.com/v2/place/&lt;PlaceId&gt;</span></div>
                            <div><span class="try-it-method" style="background:#10b981;">VIA</span><span style="color:#86efac;">/api/places/&lt;PlaceId&gt;</span></div>
                        </div>
                        <div style="padding-top:10px;display:flex;flex-direction:column;gap:8px;">
                            <div>
                                <label style="font-size:0.74rem;color:#cbd5e1;font-weight:600;">PlaceId <span style="color:#ef4444;">*</span></label>
                                <input id="gp-id" placeholder="Paste PlaceId (e.g. AQABA...)" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-family:monospace;font-size:0.78rem;">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                <div>
                                    <label style="font-size:0.74rem;color:#cbd5e1;font-weight:600;">language</label>
                                    <select id="gp-lang" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-size:0.84rem;">
                                        <option value="id">id</option>
                                        <option value="en">en</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:0.74rem;color:#cbd5e1;font-weight:600;">additional-features</label>
                                    <input id="gp-feat" value="TimeZone" placeholder="TimeZone,Contact,Hours" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #334155;background:#0f172a;color:#f1f5f9;font-family:monospace;font-size:0.78rem;">
                                </div>
                            </div>
                        </div>
                        <div class="send-row" style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);">
                            <button class="btn-send" id="gp-run" type="button"><i class="bi bi-play-fill"></i> <span data-i18n="btn_send">Send Request</span></button>
                            <span id="gp-spinner" style="display:none;color:#cbd5e1;font-size:0.8rem;">⏳ <span data-i18n="loading">Loading</span>...</span>
                        </div>
                    </div>
                </div>

                <div class="resp-bar">
                    <span style="font-weight:700;color:var(--text-primary);">Response</span>
                    <span class="status-pill idle" id="gp-status">— idle —</span>
                    <span class="meta" id="gp-meta"></span>
                </div>
                <div id="gp-resp" class="resp-body empty" data-i18n="gp_resp_idle">Masukkan PlaceId lalu klik Send Request.</div>

                <script>
                (function() {
                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const $ = id => document.getElementById(id);
                    $('gp-run').addEventListener('click', async () => {
                        const id = $('gp-id').value.trim();
                        const lang = $('gp-lang').value;
                        const feat = $('gp-feat').value.trim();
                        const btn=$('gp-run'),resp=$('gp-resp'),st=$('gp-status'),mt=$('gp-meta'),sp=$('gp-spinner');
                        if (!id) { resp.className='resp-body error'; resp.textContent='❌ PlaceId required'; st.textContent='MISSING PlaceId'; st.className='status-pill bad'; return; }
                        btn.disabled=true; sp.style.display='inline-block'; st.textContent='...'; st.className='status-pill idle'; mt.textContent=''; resp.className='resp-body'; resp.textContent='⏳';
                        const url = `/api/places/${encodeURIComponent(id)}?language=${encodeURIComponent(lang)}` + (feat ? `&additional-features=${encodeURIComponent(feat)}` : '');
                        const t0 = performance.now();
                        try {
                            const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
                            const ms = Math.round(performance.now() - t0); const d = await r.json();
                            st.textContent = `${r.status} ${r.statusText}`; st.className = `status-pill ${r.ok?'ok':'bad'}`;
                            mt.innerHTML = `<b>${ms}ms</b>`;
                            if (!r.ok) resp.classList.add('error');
                            resp.textContent = JSON.stringify(d, null, 2);
                        } catch (e) {
                            resp.classList.add('error'); resp.textContent='Error: '+e.message; st.textContent='NETWORK ERROR'; st.className='status-pill bad';
                        } finally { btn.disabled=false; sp.style.display='none'; }
                    });
                })();
                </script>

                </div> {{-- end v2 --}}

                <div data-version="v0">
                    <div class="endpoint-line"><span class="method GET">GET</span><span>/places/v0/indexes/{Idx}/places/{PlaceId}?key=...</span></div>
                    <div class="alert-mini warn" data-i18n-html="gp_v0_diff">
                        <strong>Differences from v2:</strong>
                        <ul style="margin:6px 0 0 18px;">
                            <li>Path beda: <code>/places/v0/indexes/{Idx}/places/{PlaceId}</code></li>
                            <li>Tidak ada <code>additional-features</code> param di v0</li>
                            <li>Response shape lebih simple, tanpa <code>OpeningHours</code>, <code>Contacts</code>, <code>TimeZone</code></li>
                        </ul>
                    </div>
                </div>

                </div> {{-- end ver-content --}}
            </div>

            <!-- Autocomplete -->
            <div class="op-panel" id="op-places-autocomplete">
                <div class="breadcrumb-mini">Places V2 / Autocomplete</div>
                <h1>Autocomplete <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n-html="ac_desc">Type-ahead khusus untuk <strong>alamat</strong> (street, address number, postal code) — bukan untuk POI.</p>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/autocomplete?key=...</span></div>

                <h4>Request body</h4>
                <pre><code class="language-json">{
  "QueryText": "Jl. Sudirman",
  "BiasPosition": [106.84, -6.20],
  "Filter": {
    "IncludeCountries": ["IDN"],
    "IncludePlaceTypes": ["Street", "PointAddress"]
  },
  "MaxResults": 5,
  "Language": "id"
}</code></pre>

                <h4>Response (TIDAK return Position)</h4>
                <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Jl. Jenderal Sudirman, Jakarta",
    "PlaceId": "AQAB...",
    "Address": { "Label": "..." },
    "Distance": 1250,
    "Highlights": { ... }
  }]
}</code></pre>

                <div class="alert-mini warn" data-i18n-html="ac_no_position">
                    ⚠️ <strong>Does not return <code>Position</code></strong> — must call GetPlace per item if you need coordinates. For finding POIs, <strong>Suggest</strong> is more efficient (returns Position directly).
                </div>
                <div class="alert-mini soon" data-i18n-html="soon_autocomplete">
                    <span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong> at the moment. Check AWS Console for available actions.
                </div>

                <h3 data-i18n="ac_inc_types">IncludePlaceTypes valid values</h3>
                <p><code>Locality</code> | <code>PostalCode</code> | <code>Intersection</code> | <code>Street</code> | <code>PointAddress</code> | <code>InterpolatedAddress</code></p>
                <p data-i18n-html="ac_no_poi"><code>PointOfInterest</code> is <strong>not valid</strong> in Autocomplete — use SearchText / SearchNearby.</p>
            </div>

            <!-- Geocode -->
            <div class="op-panel" id="op-places-geocode">
                <div class="breadcrumb-mini">Places V2 / Geocode</div>
                <h1>Geocode <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="gc_desc">Alamat terstruktur (street, city, postal) → koordinat. Lebih akurat dari SearchText untuk address lookup karena input-nya sudah parsed.</p>

                <div class="alert-mini soon" data-i18n-html="soon_geocode"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Workaround: use <code>SearchText</code> with structured QueryText.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/geocode?key=...</span></div>

                <h4>Request body</h4>
                <pre><code class="language-json">{
  "QueryComponents": {
    "Country": "IDN",
    "Region": "Jakarta",
    "Locality": "Jakarta Selatan",
    "Street": "Jl. Sudirman",
    "AddressNumber": "1",
    "PostalCode": "10220"
  },
  "MaxResults": 1
}</code></pre>

                <h4>Response</h4>
                <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Jl. Sudirman 1, Jakarta",
    "Position": [106.823, -6.224],
    "Address": { ... },
    "MatchScores": { "Overall": 0.95 }
  }]
}</code></pre>
                <div class="alert-mini info" data-i18n="gc_use_case">
                    💡 Use this when you already have field-by-field address (e.g. from a form input). For free-text search, use SearchText.
                </div>
            </div>

            <!-- SearchNearby -->
            <div class="op-panel" id="op-places-search-nearby">
                <div class="breadcrumb-mini">Places V2 / SearchNearby</div>
                <h1>SearchNearby <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc" data-i18n="sn_desc">Find POI dalam radius dari satu titik, opsional filter by category. Tidak butuh QueryText — cuma "tunjukin yang dekat tipe X".</p>

                <div class="alert-mini soon" data-i18n-html="soon_nearby"><span class="soon-pill">⏳ Coming Soon</span> <strong>Not available in region <code>ap-southeast-1</code></strong>. Workaround: use <code>SearchText</code> with <code>Filter.Circle</code> + category keyword in QueryText.</div>

                <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/search-nearby?key=...</span></div>

                <h4>Request body</h4>
                <pre><code class="language-json">{
  "QueryPosition": [106.84, -6.20],
  "QueryRadius": 1000,
  "Filter": {
    "IncludeCategories": ["transit_station_bus"]
  },
  "MaxResults": 20,
  "Language": "id"
}</code></pre>

                <h4>Response</h4>
                <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Halte Transjakarta Halimun",
    "PlaceId": "AQAB...",
    "Position": [106.85, -6.24],
    "Distance": 850,
    "Categories": [{ "Id": "transit_station_bus", "Primary": true }]
  }]
}</code></pre>

                <div class="alert-mini success">
                    ✅ Untuk use case <strong>"halte terdekat"</strong> ini paling direct: 1 API call, sorted by distance, gak perlu QueryText. Pre-syaratnya kamu tau ID kategori (mis. <code>transit_station_bus</code>).
                </div>

                <h3>Catatan</h3>
                <ul>
                    <li>Max <code>QueryRadius</code> = <strong>50,000 m</strong> (50 km)</li>
                    <li>Max <code>MaxResults</code> = <strong>20</strong></li>
                </ul>
            </div>

            {{-- =============================================================== --}}
            {{-- META PANELS                                                    --}}
            {{-- =============================================================== --}}

            <!-- Overview -->
            <div class="op-panel" id="op-meta-overview">
                <div class="breadcrumb-mini">General / Overview</div>
                <h1>Overview v0 vs v2</h1>
                <p class="op-desc" data-i18n="ov_desc">Dua generation API yang masih jalan paralel. v2 = standalone mode (recommended), v0 = legacy resource-based.</p>

                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Aspek</th>
                            <th>v0 Legacy</th>
                            <th>v2 Standalone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b>Setup AWS Console</b></td>
                            <td data-i18n="meta_setup_v0">Must create Map / PlaceIndex / RouteCalculator first</td>
                            <td data-i18n="meta_setup_v2">Not needed — works immediately</td>
                        </tr>
                        <tr>
                            <td><b>Hostname</b></td>
                            <td data-i18n-html="meta_host_v0"><code>maps.geo.{region}.amazonaws.com</code> (shared)</td>
                            <td data-i18n-html="meta_host_v2">Per service: <code>maps.geo</code>, <code>places.geo</code>, <code>routes.geo</code></td>
                        </tr>
                        <tr>
                            <td><b>Path version</b></td>
                            <td data-i18n-html="meta_path_v0"><code>/{service}/v0/...</code></td>
                            <td data-i18n-html="meta_path_v2"><code>/v2/...</code></td>
                        </tr>
                        <tr>
                            <td><b>Provider</b></td>
                            <td data-i18n="meta_provider_v0">Lock per resource</td>
                            <td data-i18n="meta_provider_v2">Auto-picked per region</td>
                        </tr>
                        <tr>
                            <td><b>Status</b></td>
                            <td data-i18n="meta_status_v0">⚠️ Maintenance only</td>
                            <td data-i18n="meta_status_v2">✅ Active development</td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert-mini success">
                    <strong>Rekomendasi:</strong> Project baru pakai <strong>v2</strong>. Project existing tetap aman di v0 sampai AWS announce deprecation.
                </div>
            </div>

            <!-- Auth -->
            <div class="op-panel" id="op-meta-auth">
                <div class="breadcrumb-mini">General / Authentication</div>
                <h1>Authentication</h1>
                <p class="op-desc" data-i18n="auth_desc">Dua-duanya support API Key (recommended frontend) atau AWS SigV4 (backend).</p>

                <h3>API Key di URL</h3>
                <pre><code>?key=v1.public.eyJq...</code></pre>

                <h3>Resource ARN (per service)</h3>
                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>v0</th>
                            <th>v2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Maps</td>
                            <td><code>arn:aws:geo:...:map/{Name}</code></td>
                            <td><code>arn:aws:geo-maps:...::provider/default</code></td>
                        </tr>
                        <tr>
                            <td>Places</td>
                            <td><code>arn:aws:geo:...:place-index/{Name}</code></td>
                            <td><code>arn:aws:geo-places:...::provider/default</code></td>
                        </tr>
                        <tr>
                            <td>Routes</td>
                            <td><code>arn:aws:geo:...:route-calculator/{Name}</code></td>
                            <td><code>arn:aws:geo-routes:...::provider/default</code></td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert-mini warn">
                    ⚠️ Saat bikin API Key di AWS Console, centang <strong>actions</strong> per service yang dibutuhkan. Action yang gak dicentang akan return <code>403 Forbidden</code> (explicit deny).
                </div>
            </div>

            <!-- Quotas -->
            <div class="op-panel" id="op-meta-quotas">
                <div class="breadcrumb-mini">General / Quotas &amp; Limits</div>
                <h1>Quotas &amp; Limits</h1>
                <p class="op-desc" data-i18n="qu_desc">Limit per request untuk API Location v2.</p>

                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Operation</th>
                            <th>Param</th>
                            <th>Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SearchText</td>
                            <td>MaxResults</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td>Suggest</td>
                            <td>MaxResults</td>
                            <td>10</td>
                        </tr>
                        <tr>
                            <td>ReverseGeocode</td>
                            <td>MaxResults</td>
                            <td>4</td>
                        </tr>
                        <tr>
                            <td>SearchNearby</td>
                            <td>MaxResults</td>
                            <td>20</td>
                        </tr>
                        <tr>
                            <td data-i18n="qu_all_search">All Search</td>
                            <td>Filter.Circle.Radius</td>
                            <td>50,000 m</td>
                        </tr>
                        <tr>
                            <td>CalculateRoutes</td>
                            <td>Waypoints</td>
                            <td>23</td>
                        </tr>
                        <tr>
                            <td>CalculateRouteMatrix</td>
                            <td>Origins × Destinations</td>
                            <td>700 cells</td>
                        </tr>
                        <tr>
                            <td data-i18n="qu_rate_label">Rate limit (default)</td>
                            <td>—</td>
                            <td data-i18n="qu_tps">50 TPS / account (Places)</td>
                        </tr>
                    </tbody>
                </table>

                <p>Lengkap: <a href="https://docs.aws.amazon.com/location/latest/developerguide/location-quotas.html" target="_blank">Service Quotas</a></p>
            </div>

            <!-- Migration -->
            <div class="op-panel" id="op-meta-migration">
                <div class="breadcrumb-mini">General / Migration Guide</div>
                <h1>Migration v0 → v2</h1>
                <p class="op-desc" data-i18n="mig_desc">Checklist 3-step untuk pindahin code dari v0 (resource-based) ke v2 (standalone).</p>

                <h3>1. Maps</h3>
                <ul>
                    <li>Hapus dependency ke <code>AWS_MAP_NAME</code></li>
                    <li>Ganti URL style ke <code>/v2/styles/Standard/descriptor</code></li>
                    <li>Tambah API Key actions: <code>geo-maps:*</code></li>
                </ul>

                <h3>2. Places</h3>
                <ul>
                    <li>Hapus <code>AWS_MAP_PLACE</code> (PlaceIndex)</li>
                    <li>Endpoint: <code>/places/v0/indexes/.../search/text</code> → <code>/v2/search-text</code></li>
                    <li>Body: <code>Text</code> → <code>QueryText</code>, <code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                    <li>Response: <code>Results</code> → <code>ResultItems</code>, <code>Place.Geometry.Point</code> → <code>Position</code></li>
                </ul>

                <h3>3. Routes</h3>
                <ul>
                    <li>Hapus <code>AWS_MAP_ROUTE</code> (RouteCalculator)</li>
                    <li>Endpoint ke <code>/v2/routes</code> &amp; <code>/v2/route-matrix</code></li>
                    <li>Body: <code>DeparturePosition</code> → <code>Origin</code>, <code>WaypointPositions</code> → <code>Waypoints: [{Position}]</code></li>
                    <li>TravelMode: <code>Motorcycle</code>/<code>Walking</code> → <code>Scooter</code>/<code>Pedestrian</code></li>
                    <li>Distance v0 (km) → v2 (meter), <code>DurationSeconds</code> → <code>Duration</code></li>
                    <li>Matrix: tambah <code>RoutingBoundary: {Unbounded: true}</code></li>
                </ul>

                <div class="alert-mini info" data-i18n-html="mig_tip">
                    💡 Test on a separate endpoint first (e.g. <a href="/transjakarta-test">/transjakarta-test</a>) before migrating production code.
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>

    <script>
        // ===== Sidebar collapse/expand =====
        document.querySelectorAll('.service-header').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.parentElement.classList.toggle('collapsed');
            });
        });

        // ===== Operation switching =====
        function showOp(op) {
            document.querySelectorAll('.op-panel').forEach(p => p.classList.remove('active'));
            // Operation yang ditandai unavail → arahkan ke shared "Coming Soon" panel
            const link = document.querySelector(`.op-link[data-op="${op}"]`);
            const isUnavail = link && link.classList.contains('unavail');
            const targetId = isUnavail ? 'op-coming-soon' : 'op-' + op;
            const target = document.getElementById(targetId);
            if (target) target.classList.add('active');
            // Update title dinamis di Coming Soon panel
            if (isUnavail) {
                const opTitle = link.textContent.trim().replace(/Soon$/i, '').trim();
                document.getElementById('comingSoonTitle').textContent = opTitle;
            }
            document.querySelectorAll('.op-link').forEach(a => a.classList.remove('active'));
            if (link) {
                link.classList.add('active');
                link.closest('.service-group')?.classList.remove('collapsed');
            }
            // Reset main scroll
            document.querySelector('.main').scrollTop = 0;
            // Update URL hash
            history.replaceState(null, '', '#' + op);
        }

        document.querySelectorAll('.op-link').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                if (a.dataset.op) showOp(a.dataset.op);
            });
        });

        // ===== v0/v2 tab switcher inside panels =====
        document.querySelectorAll('.ver-tabs').forEach(tabs => {
            const buttons = tabs.querySelectorAll('button');
            const content = tabs.nextElementSibling;
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    buttons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    content.querySelectorAll(':scope > div').forEach(d => d.classList.remove('active'));
                    content.querySelector(`:scope > div[data-version="${btn.dataset.version}"]`)?.classList.add('active');
                });
            });
        });

        // ===== Search filter =====
        document.getElementById('searchBox').addEventListener('input', e => {
            const q = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.op-link').forEach(a => {
                const txt = a.textContent.toLowerCase();
                const li = a.parentElement;
                li.style.display = !q || txt.includes(q) ? '' : 'none';
            });
            // Auto-expand all groups while searching
            document.querySelectorAll('.service-group').forEach(g => {
                if (q) g.classList.remove('collapsed');
            });
        });

        // ===== Init from URL hash =====
        const hash = window.location.hash.replace('#', '');
        if (hash) showOp(hash);

        /* ============================================================
           I18N — pindah ke file: public/javascript/docs/aws-api-i18n.js
           Auto-init via window.AWSAPI_applyI18n / window.AWSAPI_I18N
           ============================================================ */
    </script>

    {{-- I18N module — auto-init di DOMContentLoaded --}}
    <script src="{{ asset('javascript/docs/aws-api-i18n.js') }}"></script>

</body>

</html>