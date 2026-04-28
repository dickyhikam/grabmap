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

    <header class="topbar">
        <span class="brand">📚 AWS Location Service Reference <small>v0 (legacy) &amp; v2 (standalone)</small></span>
        <input type="text" class="search-box" placeholder="🔍 Search operation..." id="searchBox">
    </header>

    <div class="layout">
        <!-- ========================== SIDEBAR ========================== -->
        <aside class="sidebar">

            <!-- Maps V2 -->
            <div class="service-group" data-service="maps">
                <button class="service-header">
                    <i class="bi bi-caret-down-fill caret"></i>
                    Maps
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
                    Places
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
                    Routes
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
                    General Topics
                </button>
                <ul class="operations">
                    <li><a class="op-link" data-op="meta-overview">Overview v0 vs v2</a></li>
                    <li><a class="op-link" data-op="meta-auth">Authentication</a></li>
                    <li><a class="op-link" data-op="meta-quotas">Quotas &amp; Limits</a></li>
                    <li><a class="op-link" data-op="meta-migration">Migration Guide</a></li>
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
                    <h2>AWS Location Service Reference</h2>
                    <p>Pilih operation di sidebar kiri untuk lihat detail endpoint, request body, response shape, dan perbandingan v0 ↔ v2.</p>
                    <p class="text-muted small">
                        Default provider di <code>ap-southeast-1</code> = <strong>GrabMaps</strong>.
                        Dokumentasi ini fokus untuk integrasi API.
                    </p>
                </div>
            </div>

            <!-- Shared "Coming Soon" panel (untuk operations yang belum tersedia di region) -->
            <div class="op-panel" id="op-coming-soon">
                <div class="welcome-panel">
                    <div class="icon" style="color:#f59e0b;"><i class="bi bi-hourglass-split"></i></div>
                    <h2><span id="comingSoonTitle">Operation</span></h2>
                    <p style="margin-bottom:18px;">
                        <span style="display:inline-block;background:linear-gradient(90deg,#fbbf24,#f59e0b);color:#fff;padding:4px 14px;border-radius:999px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">⏳ Coming Soon</span>
                    </p>
                    <p>Operation ini <strong>belum tersedia</strong> di API key kamu untuk region <code>ap-southeast-1</code>.</p>
                    <p class="text-muted small">
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
                <p class="op-desc">Hitung rute dari Origin ke Destination, dengan opsi waypoints, mode kendaraan, hindari toll/ferry, dan turn-by-turn instructions.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://routes.geo.{region}.amazonaws.com/routes/v0/calculators/{Calc}/calculate/route?key=...</span></div>
                        <h4>Request body</h4>
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
                        <h4>Response</h4>
                        <pre><code class="language-json">{
  "Summary": { "Distance": 5.2, "DurationSeconds": 720 },
  "Legs": [{ "Distance": 5.2, "DurationSeconds": 720,
             "Geometry": { "LineString": [[106.84,-6.20], ...] } }]
}</code></pre>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>https://routes.geo.{region}.amazonaws.com/v2/routes?key=...</span></div>
                        <h4>Request body</h4>
                        <pre><code class="language-json">{
  "Origin": [106.84, -6.20],
  "Destination": [106.85, -6.24],
  "Waypoints": [{ "Position": [106.846, -6.21] }],
  "TravelMode": "Car",
  "LegGeometryFormat": "Simple",
  "InstructionsMeasurementSystem": "Metric",
  "Locale": "id",
  "Avoid": { "TollRoads": true }
}</code></pre>
                        <h4>Response</h4>
                        <pre><code class="language-json">{
  "Routes": [{
    "Summary": { "Distance": 5200, "Duration": 720 },
    "Legs": [{
      "Distance": 5200, "Duration": 720,
      "Geometry": { "LineString": [[106.84,-6.20], ...] }
    }]
  }]
}</code></pre>
                        <div class="alert-mini warn">
                            ⚠️ <strong>Perubahan field:</strong> <code>DeparturePosition→Origin</code>, <code>DestinationPosition→Destination</code>, <code>WaypointPositions[lng,lat]→Waypoints[{Position}]</code>, <code>AvoidTolls→Avoid.TollRoads</code>, <code>Motorcycle→Scooter</code>, <code>Walking→Pedestrian</code>. <br>Distance v2 dalam <strong>meter</strong> (bukan km).
                        </div>
                    </div>
                </div>

                <h3>TravelMode</h3>
                <table class="param-table">
                    <thead>
                        <tr>
                            <th>v0</th>
                            <th>v2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>Car</code></td>
                            <td><code>Car</code></td>
                        </tr>
                        <tr>
                            <td><code>Motorcycle</code></td>
                            <td><code>Scooter</code></td>
                        </tr>
                        <tr>
                            <td><code>Walking</code></td>
                            <td><code>Pedestrian</code></td>
                        </tr>
                        <tr>
                            <td><code>Truck</code></td>
                            <td><code>Truck</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CalculateRouteMatrix -->
            <div class="op-panel" id="op-routes-calculate-route-matrix">
                <div class="breadcrumb-mini">Routes V2 / CalculateRouteMatrix</div>
                <h1>CalculateRouteMatrix</h1>
                <p class="op-desc">Hitung jarak &amp; waktu untuk semua kombinasi origin × destination — efisien untuk "find nearest" use case.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/routes/v0/calculators/{Calc}/calculate/route-matrix?key=...</span></div>
                        <pre><code class="language-json">{
  "DeparturePositions": [[106.84, -6.20]],
  "DestinationPositions": [[106.85,-6.24], [106.86,-6.25]],
  "TravelMode": "Car",
  "DistanceUnit": "Kilometers"
}</code></pre>
                        <h4>Response (km, DurationSeconds)</h4>
                        <pre><code class="language-json">{
  "RouteMatrix": [[
    { "Distance": 1.2, "DurationSeconds": 240 },
    { "Distance": 2.5, "DurationSeconds": 480 }
  ]]
}</code></pre>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/route-matrix?key=...</span></div>
                        <pre><code class="language-json">{
  "Origins": [{ "Position": [106.84,-6.20] }],
  "Destinations": [
    { "Position": [106.85,-6.24] },
    { "Position": [106.86,-6.25] }
  ],
  "TravelMode": "Car",
  "RoutingBoundary": { "Unbounded": true },
  "Avoid": { "TollRoads": false }
}</code></pre>
                        <h4>Response (meter, Duration)</h4>
                        <pre><code class="language-json">{
  "RouteMatrix": [[
    { "Distance": 1200, "Duration": 240 },
    { "Distance": 2500, "Duration": 480 }
  ]]
}</code></pre>
                        <div class="alert-mini warn">
                            ⚠️ <code>RoutingBoundary</code> <strong>WAJIB</strong> di v2. Set <code>{ "Unbounded": true }</code> untuk perilaku v0.
                        </div>
                    </div>
                </div>

                <h3>Limit</h3>
                <p>Max <strong>700 cells</strong> per request (Origins × Destinations). Mis. 7×100 atau 100×7.</p>
            </div>

            <!-- CalculateIsolines -->
            <div class="op-panel" id="op-routes-calculate-isolines">
                <div class="breadcrumb-mini">Routes V2 / CalculateIsolines</div>
                <h1>CalculateIsolines <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc">Polygon area "reachable within X minutes" atau "within Y km" dari satu titik. Cocok untuk visualisasi service coverage.</p>

                <div class="alert-mini soon"><span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong>. Action ini belum ke-list di AWS Console permissions. Tunggu rilis AWS atau pakai workaround di bawah.</div>

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
                <div class="alert-mini info">
                    💡 Use case: visualisasi <strong>"halte yang bisa dicapai dalam 10 menit jalan kaki"</strong> dengan polygon overlay di MapLibre.
                </div>
            </div>

            <!-- OptimizeWaypoints -->
            <div class="op-panel" id="op-routes-optimize-waypoints">
                <div class="breadcrumb-mini">Routes V2 / OptimizeWaypoints</div>
                <h1>OptimizeWaypoints <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc">TSP solver — kasih AWS list of waypoints, dia kembalikan urutan optimal (ngirit jarak/waktu).</p>

                <div class="alert-mini soon"><span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong>. Untuk fitur ini, implement nearest-neighbor TSP sendiri di JS atau pakai library.</div>

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
                <p class="op-desc">Snap GPS trace yang noisy ke jalan terdekat. Berguna untuk clean trip log GPS dari kendaraan.</p>

                <div class="alert-mini soon"><span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong>. Belum di-roll out untuk provider GrabMaps.</div>

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
                <div class="alert-mini info">
                    💡 Pasangkan dengan trip recording dari aplikasi ojol untuk dapatkan trip line yang bersih (tanpa zigzag karena sinyal GPS).
                </div>
            </div>

            {{-- =============================================================== --}}
            {{-- MAPS OPERATIONS                                                --}}
            {{-- =============================================================== --}}

            <!-- GetStyleDescriptor -->
            <div class="op-panel" id="op-maps-get-style-descriptor">
                <div class="breadcrumb-mini">Maps V2 / GetStyleDescriptor</div>
                <h1>GetStyleDescriptor</h1>
                <p class="op-desc">Return MapLibre style spec JSON. URL ini langsung ditaroh di property <code>style</code> waktu inisialisasi MapLibre map.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/style-descriptor?key=...</span></div>
                        <p>Pakai <strong>custom Map resource</strong> yang dibuat dulu di AWS Console. <code>{MapName}</code> = nama resource yang kamu pilih.</p>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/styles/{Style}/descriptor?key=...&amp;color-scheme=Light&amp;political-view=IDN</span></div>
                        <table class="param-table">
                            <thead>
                                <tr>
                                    <th>Param</th>
                                    <th>Values</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{Style}</code></td>
                                    <td>Standard | Monochrome | Hybrid | Satellite</td>
                                    <td>Di <code>ap-southeast-1</code>: cuma Standard &amp; Monochrome</td>
                                </tr>
                                <tr>
                                    <td><code>color-scheme</code></td>
                                    <td>Light | Dark</td>
                                    <td>Hanya berlaku di Standard / Monochrome</td>
                                </tr>
                                <tr>
                                    <td><code>political-view</code></td>
                                    <td>IDN, MYS, ARG, MAR, ...</td>
                                    <td>ISO-3 country code</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <h3>Contoh integrasi MapLibre</h3>
                <pre><code class="language-javascript">const map = new maplibregl.Map({
    container: 'map',
    style: `https://maps.geo.${REGION}.amazonaws.com/v2/styles/Standard/descriptor?key=${API_KEY}&color-scheme=Light`,
    center: [106.8456, -6.2088],
    zoom: 12
});</code></pre>
            </div>

            <!-- GetTile -->
            <div class="op-panel" id="op-maps-get-tile">
                <div class="breadcrumb-mini">Maps V2 / GetTile</div>
                <h1>GetTile</h1>
                <p class="op-desc">Vector / raster tile per koordinat z/x/y. URL pattern ini ada di field <code>tiles</code> dalam style descriptor — biasanya gak perlu di-call manual.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/tiles/{z}/{x}/{y}?key=...</span></div>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/tiles/{Style}/{ColorScheme}/{Variant}/{z}/{x}/{y}?key=...</span></div>
                        <p>Tile format tergantung Style: vector (PBF) untuk Standard/Monochrome, raster (PNG/JPEG) untuk Satellite/Hybrid.</p>
                    </div>
                </div>
            </div>

            <!-- GetGlyphs -->
            <div class="op-panel" id="op-maps-get-glyphs">
                <div class="breadcrumb-mini">Maps V2 / GetGlyphs</div>
                <h1>GetGlyphs</h1>
                <p class="op-desc">Font glyphs (PBF) untuk text rendering di vector tiles. Auto-fetched oleh MapLibre, gak perlu di-call manual.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/glyphs/{fontstack}/{range}?key=...</span></div>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/glyphs/{Style}/{fontstack}/{range}?key=...</span></div>
                    </div>
                </div>
                <p><code>fontstack</code> = nama font (mis. "Noto Sans Regular"), <code>range</code> = unicode range (mis. "0-255").</p>
            </div>

            <!-- GetSprites -->
            <div class="op-panel" id="op-maps-get-sprites">
                <div class="breadcrumb-mini">Maps V2 / GetSprites</div>
                <h1>GetSprites</h1>
                <p class="op-desc">Sprite sheet (PNG + JSON) untuk icon point-of-interest di map. Auto-fetched oleh MapLibre.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/maps/v0/maps/{MapName}/sprites?key=...</span></div>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/sprites/{Style}/{ColorScheme}/{Variant}/{file}?key=...</span></div>
                        <p><code>{file}</code> = <code>sprites.json</code>, <code>sprites.png</code>, <code>sprites@2x.json</code>, atau <code>sprites@2x.png</code>.</p>
                    </div>
                </div>
            </div>

            <!-- GetStaticMap -->
            <div class="op-panel" id="op-maps-get-static-map">
                <div class="breadcrumb-mini">Maps V2 / GetStaticMap</div>
                <h1>GetStaticMap <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc">Render map jadi gambar PNG/JPEG single. Cocok untuk thumbnail, preview di card, email, atau social sharing.</p>

                <div class="alert-mini soon"><span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong>. Maps Action di Console cuma punya <code>GetTile</code>. Sementara, fallback pakai screenshot MapLibre canvas.</div>

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
                            <td>Pusat peta</td>
                        </tr>
                        <tr>
                            <td><code>Zoom</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td>0-22</td>
                        </tr>
                        <tr>
                            <td><code>Width</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td>Pixel</td>
                        </tr>
                        <tr>
                            <td><code>Height</code><span class="req">REQ</span></td>
                            <td><span class="type-tag">number</span></td>
                            <td>Pixel</td>
                        </tr>
                        <tr>
                            <td><code>Style</code></td>
                            <td>—</td>
                            <td>Standard | Monochrome | Hybrid | Satellite</td>
                        </tr>
                        <tr>
                            <td><code>FileFormat</code></td>
                            <td>—</td>
                            <td>png | jpeg</td>
                        </tr>
                        <tr>
                            <td><code>Pins</code></td>
                            <td><span class="type-tag">array</span></td>
                            <td>Marker overlay opsional</td>
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
                <p class="op-desc">Pencarian Place (POI / alamat / area) berbasis free-form text. Cocok untuk search bar dengan tombol "Search". Mendukung bias position, filter geografis (BoundingBox/Circle/Country), dan filter kategori.</p>

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
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-arrow-up-right"></i></span> Request Syntax</div>
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

                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-list-ul"></i></span> Request Parameters</div>
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
                                    <td>Free-form keyword (1-200 char). *Wajib salah satu antara <code>QueryText</code> atau <code>QueryId</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>QueryId</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td>Alternatif: ID dari hasil Suggest sebelumnya</td>
                                </tr>
                                <tr>
                                    <td><code>MaxResults</code></td>
                                    <td><span class="type-tag">number</span></td>
                                    <td>—</td>
                                    <td>1–20, default 20</td>
                                </tr>
                                <tr>
                                    <td><code>BiasPosition</code></td>
                                    <td><span class="type-tag">[lng, lat]</span></td>
                                    <td>—</td>
                                    <td>Bias ranking + reference untuk field <code>Distance</code> di response. <strong>Exactly 1</strong> dari BiasPosition / Filter.BoundingBox / Filter.Circle.<br><strong>📌 Pakai ini kalau butuh Distance</strong> — Filter.Circle/BoundingBox tidak men-trigger Distance di <code>ap-southeast-1</code>.</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.BoundingBox</code></td>
                                    <td><span class="type-tag">[w, s, e, n]</span></td>
                                    <td>—</td>
                                    <td>Batasi hasil dalam kotak (west, south, east, north)</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.Circle</code></td>
                                    <td><span class="type-tag">object</span></td>
                                    <td>—</td>
                                    <td><code>Center: [lng,lat]</code>, <code>Radius: meter</code> (max 50000)</td>
                                </tr>
                                <tr>
                                    <td><code>Filter.IncludeCountries</code></td>
                                    <td><span class="type-tag">array&lt;string&gt;</span></td>
                                    <td>—</td>
                                    <td>ISO-3 country codes, mis. <code>["IDN"]</code></td>
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
                                    <td>BCP 47 (mis. <code>id</code>, <code>en</code>)</td>
                                </tr>
                                <tr>
                                    <td><code>PoliticalView</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td>ISO-3 country code (mis. <code>IDN</code>)</td>
                                </tr>
                                <tr>
                                    <td><code>IntendedUse</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td><code>SingleUse</code> (default) | <code>Storage</code></td>
                                </tr>
                                <tr>
                                    <td><code>NextToken</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>—</td>
                                    <td>Pagination cursor</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- ====================== FIELD RULES ====================== --}}
                        <div class="doc-section-h"><span class="ic blue"><i class="bi bi-shuffle"></i></span> Field Rules — yang bisa &amp; tidak bisa di-combo</div>

                        <div class="rules-grid">
                            <div class="rule-card required">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-check-square-fill"></i></span>
                                    Wajib salah satu (one-of)
                                </div>
                                <div class="field-list">
                                    <code>QueryText</code>
                                    <span class="sep">OR</span>
                                    <code>QueryId</code>
                                </div>
                                <div class="rule-note">Salah satu wajib di body. Boleh dua-duanya disebut tapi yang dipakai sesuai prioritas AWS internal.</div>
                            </div>

                            <div class="rule-card exclusive">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-x-octagon-fill"></i></span>
                                    Saling eksklusif (exactly one)
                                </div>
                                <div class="field-list">
                                    <code>BiasPosition</code>
                                    <span class="sep">XOR</span>
                                    <code>Filter.BoundingBox</code>
                                    <span class="sep">XOR</span>
                                    <code>Filter.Circle</code>
                                </div>
                                <div class="rule-note">
                                    Pakai 2 atau lebih = error 400 <em>"Exactly one of..."</em>. Pakai semua-nya kosong → no spatial bias (hasil global).<br>
                                    💡 <strong>Trade-off pemilihan:</strong>
                                    <br>· <code>BiasPosition</code> → ranking-bias + ada field <code>Distance</code> di response
                                    <br>· <code>Filter.Circle</code> / <code>BoundingBox</code> → hard cut-off geografis tapi <strong>tanpa Distance</strong> (empiris di <code>ap-southeast-1</code>)
                                </div>
                            </div>

                            <div class="rule-card combo">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-puzzle-fill"></i></span>
                                    Boleh dikombinasi
                                </div>
                                <div class="field-list">
                                    <code>Filter.IncludeCountries</code>
                                    <span class="sep">+</span>
                                    <code>BiasPosition</code>/<code>Circle</code>/<code>BoundingBox</code>
                                </div>
                                <div class="rule-note">IncludeCountries adalah filter aditif (AND), bukan exclusive — jadi bisa dipakai bareng spatial filter mana pun.</div>
                            </div>

                            <div class="rule-card combo">
                                <div class="rule-header">
                                    <span class="ic"><i class="bi bi-layers-fill"></i></span>
                                    Independen (pakai bebas)
                                </div>
                                <div class="field-list">
                                    <code>MaxResults</code>
                                    <code>Language</code>
                                    <code>PoliticalView</code>
                                    <code>AdditionalFeatures</code>
                                    <code>IntendedUse</code>
                                    <code>NextToken</code>
                                </div>
                                <div class="rule-note">Field-field ini gak punya constraint conflict satu sama lain. Boleh dipakai semua atau tidak sama sekali.</div>
                            </div>
                        </div>

                        {{-- ====================== COMMON ERRORS ====================== --}}
                        <div class="doc-section-h"><span class="ic" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-exclamation-triangle-fill"></i></span> Common Errors &amp; Validation</div>

                        <table class="error-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Trigger</th>
                                    <th>Pesan AWS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td>2+ dari <code>BiasPosition</code> / <code>Filter.BoundingBox</code> / <code>Filter.Circle</code> dikirim bareng</td>
                                    <td><em>"Exactly one of the following fields must be set: BiasPosition, Filter.BoundingBox, Filter.Circle."</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td>Tidak ada <code>QueryText</code> dan tidak ada <code>QueryId</code></td>
                                    <td><em>"Either QueryText or QueryId is required."</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td><code>MaxResults</code> &gt; 20</td>
                                    <td><em>"Member must have value less than or equal to 20"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td><code>Filter.Circle.Radius</code> &gt; 50000</td>
                                    <td><em>"Member must have value less than or equal to 50000"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td>Coordinate format salah (mis. <code>[lat, lng]</code> bukan <code>[lng, lat]</code>)</td>
                                    <td><em>Hasil aneh / kosong</em> — AWS gak validasi range, coord ditelan apa adanya</td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">400</span></td>
                                    <td><code>Filter.IncludeCountries</code> bukan ISO-3 (mis. "Indonesia" atau "ID")</td>
                                    <td><em>"Validation failed: country code must be 3 letters"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td>API Key tidak punya action <code>geo-places:SearchText</code></td>
                                    <td><em>"User is not authorized to access this resource with an explicit deny"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">403</span></td>
                                    <td>API Key salah atau missing <code>?key=</code></td>
                                    <td><em>"The security token included in the request is invalid"</em></td>
                                </tr>
                                <tr>
                                    <td><span class="err-code">429</span></td>
                                    <td>Rate limit kelampauan (default 50 TPS)</td>
                                    <td><em>"Rate exceeded"</em> — implement retry dengan backoff</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- ====================== RESPONSE ====================== --}}
                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-arrow-down-left"></i></span> Response Syntax</div>
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

                        <div class="doc-section-h"><span class="ic purple"><i class="bi bi-list-ul"></i></span> Response Fields</div>
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
                                    <td>ID unik AWS — pakai untuk GetPlace lihat detail penuh</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].PlaceType</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>PointOfInterest | Address | Street | District | Region | dst.</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Title</code></td>
                                    <td><span class="type-tag">string</span></td>
                                    <td>Nama display utama</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Position</code></td>
                                    <td><span class="type-tag">[lng, lat]</span></td>
                                    <td>Koordinat tengah place</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Distance</code></td>
                                    <td><span class="type-tag">number</span></td>
                                    <td>
                                        <strong>Meter</strong> dari titik referensi.<br>
                                        ⚠️ <strong>Empiris di <code>ap-southeast-1</code>:</strong> field ini cuma muncul saat request pakai <code>BiasPosition</code>. Saat pakai <code>Filter.Circle</code> atau <code>Filter.BoundingBox</code>, field <code>Distance</code> <strong>tidak ada</strong> di response.<br>
                                        Workaround: hitung Haversine di JS dari <code>Position</code> ke origin.
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Address</code></td>
                                    <td><span class="type-tag">object</span></td>
                                    <td>Alamat terstruktur (label + komponen)</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].MapView</code></td>
                                    <td><span class="type-tag">[w,s,e,n]</span></td>
                                    <td>Bounding box untuk fit map</td>
                                </tr>
                                <tr>
                                    <td><code>ResultItems[].Categories</code></td>
                                    <td><span class="type-tag">array</span></td>
                                    <td>Kategori AWS (mis. <code>transit_station_bus</code>)</td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- ====================== TRY IT LIVE (V2 only) ====================== --}}
                        <div class="doc-section-h"><span class="ic orange"><i class="bi bi-play-circle"></i></span> Try it Live</div>

                        {{-- Preset buttons — kombinasi valid sesuai field rules --}}
                        <div class="preset-row">
                            <span class="preset-label"><i class="bi bi-bookmark-fill"></i>&nbsp;Presets</span>
                            <button class="preset-btn" data-preset="bias">📍 BiasPosition <span class="pico">simple</span></button>
                            <button class="preset-btn" data-preset="circle">⭕ Filter.Circle <span class="pico">radius 2km</span></button>
                            <button class="preset-btn" data-preset="bbox">🟦 Filter.BoundingBox <span class="pico">Jakarta</span></button>
                            <button class="preset-btn" data-preset="minimal">📝 Minimal <span class="pico">QueryText only</span></button>
                            <button class="preset-btn" data-preset="full">🎛️ All Features <span class="pico">+TimeZone</span></button>
                            <button class="preset-btn" data-preset="error">💥 Error case <span class="pico">2 spatial filter</span></button>
                        </div>

                        <div class="try-it">
                            <div class="try-it-pane right" style="border-right:0;">
                                <div class="try-it-pane-header">
                                    <span>
                                        <i class="bi bi-code-slash"></i> Request Body
                                        <span class="json-status ok" id="st-json-status">VALID</span>
                                    </span>
                                    <div style="display:flex;gap:6px;">
                                        <button class="btn-copy" onclick="copyToClipboard('st-req-preview', this)">📋 Copy</button>
                                        <button class="btn-copy" id="st-format-btn" type="button">✨ Format</button>
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
                                        <i class="bi bi-play-fill"></i> Send Request
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
                            <button class="btn-copy" id="st-resp-copy" style="margin-left:auto;background:#e2e8f0;color:#334155;border:1px solid #cbd5e1;display:none;">📋 Copy Response</button>
                        </div>
                        <div id="st-resp" class="resp-body empty">Klik <b>Send Request</b> di atas untuk lihat live response dari AWS.</div>

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
                            (function() {
                                // 🔒 No AWS region/key exposed di sini — semua fetch lewat Laravel proxy
                                const PROXY_URL = '/api/places/search';
                                const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                const $ = id => document.getElementById(id);

                                /* ============================================================
                                   PRESETS — kombinasi valid (+ 1 contoh error case)
                                   ============================================================ */
                                const PRESETS = {
                                    bias: {
                                        QueryText: "halte TransJakarta",
                                        BiasPosition: [106.8456, -6.2088],
                                        MaxResults: 5,
                                        Language: "id",
                                        Filter: {
                                            IncludeCountries: ["IDN"]
                                        }
                                    },
                                    circle: {
                                        QueryText: "halte TransJakarta",
                                        Filter: {
                                            Circle: {
                                                Center: [106.8456, -6.2088],
                                                Radius: 2000
                                            },
                                            IncludeCountries: ["IDN"]
                                        },
                                        MaxResults: 10,
                                        Language: "id"
                                    },
                                    bbox: {
                                        QueryText: "stasiun",
                                        Filter: {
                                            // Jakarta bounding box [west, south, east, north]
                                            BoundingBox: [106.689, -6.371, 106.971, -6.089],
                                            IncludeCountries: ["IDN"]
                                        },
                                        MaxResults: 10,
                                        Language: "id"
                                    },
                                    minimal: {
                                        QueryText: "Monas Jakarta"
                                    },
                                    full: {
                                        QueryText: "halte TransJakarta",
                                        BiasPosition: [106.8456, -6.2088],
                                        Filter: {
                                            IncludeCountries: ["IDN"]
                                        },
                                        MaxResults: 10,
                                        Language: "id",
                                        PoliticalView: "IDN",
                                        AdditionalFeatures: ["TimeZone"],
                                        IntendedUse: "SingleUse"
                                    },
                                    error: {
                                        // ⚠️ Sengaja salah: 2 spatial filter dipakai bareng
                                        QueryText: "halte",
                                        BiasPosition: [106.8456, -6.2088],
                                        Filter: {
                                            Circle: {
                                                Center: [106.8456, -6.2088],
                                                Radius: 2000
                                            }
                                        }
                                    }
                                };

                                /* ============================================================
                                   JSON EDITOR HELPERS
                                   ============================================================ */
                                function setJsonEditor(obj) {
                                    $('st-req-preview').value = JSON.stringify(obj, null, 2);
                                    setJsonStatus(true);
                                }

                                function setJsonStatus(valid) {
                                    const tag = $('st-json-status');
                                    const ed = $('st-req-preview');
                                    if (valid) {
                                        tag.textContent = 'VALID';
                                        tag.className = 'json-status ok';
                                        ed.classList.remove('invalid');
                                    } else {
                                        tag.textContent = 'INVALID';
                                        tag.className = 'json-status invalid';
                                        ed.classList.add('invalid');
                                    }
                                }

                                function tryParseJson() {
                                    try {
                                        return JSON.parse($('st-req-preview').value);
                                    } catch (e) {
                                        return null;
                                    }
                                }

                                // JSON editor input → live validate
                                $('st-req-preview').addEventListener('input', () => {
                                    setJsonStatus(tryParseJson() !== null);
                                });

                                // Format JSON button — re-pretty-print
                                $('st-format-btn').addEventListener('click', () => {
                                    const parsed = tryParseJson();
                                    if (parsed) setJsonEditor(parsed);
                                });

                                // Preset buttons → load PRESETS[name]
                                document.querySelectorAll('.preset-row .preset-btn').forEach(btn => {
                                    btn.addEventListener('click', () => {
                                        const preset = PRESETS[btn.dataset.preset];
                                        if (preset) setJsonEditor(preset);
                                    });
                                });

                                // Initial load
                                setJsonEditor(PRESETS.bias);

                                /* ============================================================
                                   SEND REQUEST — pakai isi textarea (single source of truth)
                                   ============================================================ */
                                $('st-run').addEventListener('click', async () => {
                                    const btn = $('st-run');
                                    const respEl = $('st-resp');
                                    const statusEl = $('st-status');
                                    const metaEl = $('st-meta');
                                    const spinner = $('st-spinner');
                                    const respCopy = $('st-resp-copy');

                                    const parsed = tryParseJson();
                                    if (!parsed) {
                                        respEl.className = 'resp-body error';
                                        respEl.textContent = '❌ JSON tidak valid — perbaiki dulu sebelum send.';
                                        statusEl.textContent = 'INVALID JSON';
                                        statusEl.className = 'status-pill bad';
                                        return;
                                    }

                                    btn.disabled = true;
                                    spinner.style.display = 'inline-block';
                                    statusEl.textContent = '...';
                                    statusEl.className = 'status-pill idle';
                                    metaEl.textContent = '';
                                    respCopy.style.display = 'none';
                                    respEl.className = 'resp-body';
                                    respEl.textContent = '⏳ Sending request to AWS...';

                                    const t0 = performance.now();
                                    try {
                                        const res = await fetch(PROXY_URL, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': CSRF
                                            },
                                            body: JSON.stringify(parsed)
                                        });
                                        const elapsed = Math.round(performance.now() - t0);
                                        const data = await res.json();

                                        statusEl.textContent = `${res.status} ${res.statusText}`;
                                        statusEl.className = `status-pill ${res.ok ? 'ok' : 'bad'}`;
                                        const count = (data.ResultItems || []).length;
                                        metaEl.innerHTML = `<b>${elapsed}ms</b> · ${res.ok ? `<b>${count}</b> result${count !== 1 ? 's' : ''}` : 'error'}`;

                                        if (!res.ok) respEl.classList.add('error');
                                        respEl.textContent = JSON.stringify(data, null, 2);
                                        respCopy.style.display = 'inline-block';
                                    } catch (err) {
                                        const elapsed = Math.round(performance.now() - t0);
                                        respEl.classList.add('error');
                                        respEl.textContent = 'Network error: ' + err.message;
                                        statusEl.textContent = 'NETWORK ERROR';
                                        statusEl.className = 'status-pill bad';
                                        metaEl.innerHTML = `<b>${elapsed}ms</b>`;
                                    } finally {
                                        btn.disabled = false;
                                        spinner.style.display = 'none';
                                    }
                                });

                                // Copy buttons (pakai .value untuk textarea, .textContent untuk div)
                                if (!window.copyToClipboard) {
                                    window.copyToClipboard = (id, btn) => {
                                        const el = document.getElementById(id);
                                        const txt = (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') ? el.value : el.textContent;
                                        navigator.clipboard.writeText(txt).then(() => {
                                            const orig = btn.innerHTML;
                                            btn.innerHTML = '✓ Copied';
                                            setTimeout(() => btn.innerHTML = orig, 1500);
                                        });
                                    };
                                }

                                $('st-resp-copy').addEventListener('click', e => copyToClipboard('st-resp', e.currentTarget));
                            })();
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

                        <div class="alert-mini warn">
                            <strong>Beda dari v2:</strong>
                            <ul style="margin:6px 0 0 18px;">
                                <li>Field <code>Text</code> → <code>QueryText</code></li>
                                <li><code>FilterCountries</code> → <code>Filter.IncludeCountries</code></li>
                                <li>Response: <code>Results[].Place.Geometry.Point</code> → <code>ResultItems[].Position</code></li>
                                <li><code>MaxResults</code> max 10 (v2: 20)</li>
                                <li>Wajib bikin <code>PlaceIndex</code> resource dulu di AWS Console</li>
                            </ul>
                        </div>

                    </div> {{-- end v0 tab --}}

                </div> {{-- end ver-content --}}
            </div>

            <!-- Suggest -->
            <div class="op-panel" id="op-places-suggest">
                <div class="breadcrumb-mini">Places V2 / Suggest</div>
                <h1>Suggest</h1>
                <p class="op-desc">Type-ahead autocomplete — return Place hits + Query refinement. Pakai untuk dropdown live di search bar.</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/places/v0/indexes/{Idx}/search/suggestions?key=...</span></div>
                        <pre><code class="language-json">{
  "Text": "halte tj",
  "BiasPosition": [106.84, -6.20],
  "MaxResults": 5
}</code></pre>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/suggest?key=...</span></div>
                        <pre><code class="language-json">{
  "QueryText": "halte tj",
  "BiasPosition": [106.84, -6.20],
  "MaxResults": 5,
  "Language": "id"
}</code></pre>
                        <h4>Response</h4>
                        <pre><code class="language-json">{
  "ResultItems": [{
    "Title": "Halte Transjakarta Halimun",
    "SuggestResultItemType": "Place",
    "Place": {
      "PlaceId": "AQABA...",
      "Position": [106.85, -6.24],
      "Distance": 850
    }
  }, {
    "Title": "halte tj jakarta",
    "SuggestResultItemType": "Query"
  }]
}</code></pre>
                        <div class="alert-mini info">
                            💡 Filter <code>SuggestResultItemType === "Place"</code> kalau cuma butuh actual place hits, skip refinement queries.
                        </div>
                    </div>
                </div>
                <p>Limit MaxResults: v2 max <strong>10</strong>.</p>
            </div>

            <!-- ReverseGeocode -->
            <div class="op-panel" id="op-places-reverse-geocode">
                <div class="breadcrumb-mini">Places V2 / ReverseGeocode</div>
                <h1>ReverseGeocode</h1>
                <p class="op-desc">Koordinat → alamat terdekat. Pakai saat user click di peta dan kamu mau tau "ini di mana".</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/places/v0/indexes/{Idx}/search/position?key=...</span></div>
                        <pre><code class="language-json">{
  "Position": [106.84, -6.20],
  "MaxResults": 1
}</code></pre>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method POST">POST</span><span>/v2/reverse-geocode?key=...</span></div>
                        <pre><code class="language-json">{
  "QueryPosition": [106.84, -6.20],
  "MaxResults": 1,
  "Language": "id"
}</code></pre>
                    </div>
                </div>
                <p>Limit MaxResults: v2 max <strong>4</strong>.</p>
            </div>

            <!-- GetPlace -->
            <div class="op-panel" id="op-places-get-place">
                <div class="breadcrumb-mini">Places V2 / GetPlace</div>
                <h1>GetPlace</h1>
                <p class="op-desc">Detail lengkap suatu Place by <code>PlaceId</code> (yang didapat dari Search/Suggest sebelumnya).</p>

                <div class="ver-tabs">
                    <button data-version="v0">v0 Legacy</button>
                    <button data-version="v2" class="active">v2 Standalone</button>
                </div>
                <div class="ver-content">
                    <div data-version="v0">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/places/v0/indexes/{Idx}/places/{PlaceId}?key=...</span></div>
                    </div>
                    <div data-version="v2" class="active">
                        <div class="endpoint-line"><span class="method GET">GET</span><span>/v2/place/{PlaceId}?key=...&amp;additional-features=Contact,Hours&amp;language=id</span></div>
                        <h4>Response</h4>
                        <pre><code class="language-json">{
  "Title": "Halte Transjakarta Halimun",
  "PlaceId": "AQABA...",
  "Position": [106.85, -6.24],
  "Address": { "Label": "...", "PostalCode": "12980" },
  "Categories": [{ "Id": "transit_station_bus", "Primary": true }],
  "TimeZone": { "Name": "Asia/Jakarta", "OffsetSeconds": 25200 },
  "OpeningHours": [...]
}</code></pre>
                    </div>
                </div>
                <h3>AdditionalFeatures (v2 only)</h3>
                <ul>
                    <li><code>TimeZone</code> — informasi zona waktu</li>
                    <li><code>Contact</code> — phone, website, email</li>
                    <li><code>Hours</code> — jam operasional</li>
                    <li><code>Phonemes</code> — pelafalan</li>
                </ul>
            </div>

            <!-- Autocomplete -->
            <div class="op-panel" id="op-places-autocomplete">
                <div class="breadcrumb-mini">Places V2 / Autocomplete</div>
                <h1>Autocomplete <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc">Type-ahead khusus untuk <strong>alamat</strong> (street, address number, postal code) — bukan untuk POI.</p>

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

                <div class="alert-mini warn">
                    ⚠️ <strong>Tidak return <code>Position</code></strong> — harus call GetPlace per item kalau butuh koordinat. Untuk find POI, lebih efisien pakai <strong>Suggest</strong> (return Position langsung).
                </div>
                <div class="alert-mini soon">
                    <span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong> per saat ini. Cek AWS Console untuk available actions.
                </div>

                <h3>IncludePlaceTypes valid values</h3>
                <p><code>Locality</code> | <code>PostalCode</code> | <code>Intersection</code> | <code>Street</code> | <code>PointAddress</code> | <code>InterpolatedAddress</code></p>
                <p><code>PointOfInterest</code> <strong>tidak valid</strong> di Autocomplete — pakai SearchText / SearchNearby.</p>
            </div>

            <!-- Geocode -->
            <div class="op-panel" id="op-places-geocode">
                <div class="breadcrumb-mini">Places V2 / Geocode</div>
                <h1>Geocode <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc">Alamat terstruktur (street, city, postal) → koordinat. Lebih akurat dari SearchText untuk address lookup karena input-nya sudah parsed.</p>

                <div class="alert-mini soon"><span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong>. Workaround: pakai <code>SearchText</code> dengan QueryText terstruktur.</div>

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
                <div class="alert-mini info">
                    💡 Pakai ini kalau kamu sudah punya field-by-field alamat (mis. dari form input). Untuk free-text search, pakai SearchText.
                </div>
            </div>

            <!-- SearchNearby -->
            <div class="op-panel" id="op-places-search-nearby">
                <div class="breadcrumb-mini">Places V2 / SearchNearby</div>
                <h1>SearchNearby <span class="badge bg-primary">v2 only</span></h1>
                <p class="op-desc">Find POI dalam radius dari satu titik, opsional filter by category. Tidak butuh QueryText — cuma "tunjukin yang dekat tipe X".</p>

                <div class="alert-mini soon"><span class="soon-pill">⏳ Coming Soon</span> <strong>Belum tersedia di region <code>ap-southeast-1</code></strong>. Workaround: pakai <code>SearchText</code> dengan <code>Filter.Circle</code> + QueryText keyword kategori.</div>

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
                <p class="op-desc">Dua generation API yang masih jalan paralel. v2 = standalone mode (recommended), v0 = legacy resource-based.</p>

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
                            <td>Wajib bikin Map / PlaceIndex / RouteCalculator</td>
                            <td>Tidak perlu — langsung pakai</td>
                        </tr>
                        <tr>
                            <td><b>Hostname</b></td>
                            <td><code>maps.geo.{region}.amazonaws.com</code> (shared)</td>
                            <td>Per service: <code>maps.geo</code>, <code>places.geo</code>, <code>routes.geo</code></td>
                        </tr>
                        <tr>
                            <td><b>Path version</b></td>
                            <td><code>/{service}/v0/...</code></td>
                            <td><code>/v2/...</code></td>
                        </tr>
                        <tr>
                            <td><b>Provider</b></td>
                            <td>Lock per resource</td>
                            <td>Auto pilih per region</td>
                        </tr>
                        <tr>
                            <td><b>Status</b></td>
                            <td>⚠️ Maintenance only</td>
                            <td>✅ Active development</td>
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
                <p class="op-desc">Dua-duanya support API Key (recommended frontend) atau AWS SigV4 (backend).</p>

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
                <p class="op-desc">Limit per request untuk API Location v2.</p>

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
                            <td>Semua Search</td>
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
                            <td>Rate limit (default)</td>
                            <td>—</td>
                            <td>50 TPS / akun (Places)</td>
                        </tr>
                    </tbody>
                </table>

                <p>Lengkap: <a href="https://docs.aws.amazon.com/location/latest/developerguide/location-quotas.html" target="_blank">Service Quotas</a></p>
            </div>

            <!-- Migration -->
            <div class="op-panel" id="op-meta-migration">
                <div class="breadcrumb-mini">General / Migration Guide</div>
                <h1>Migration v0 → v2</h1>
                <p class="op-desc">Checklist 3-step untuk pindahin code dari v0 (resource-based) ke v2 (standalone).</p>

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

                <div class="alert-mini info">
                    💡 Test di endpoint terpisah dulu (mis. <a href="/transjakarta-test">/transjakarta-test</a>) sebelum migrasi production code.
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
    </script>

</body>

</html>