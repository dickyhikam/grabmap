<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">
    <title>{{ __('pricing.title') }} - GrabMaps vs Google Maps</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/pricing.css">

    <style>
        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 50%, #e8f0fe 100%);
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(700px 400px at 5% 5%, rgba(0,177,79,0.10), transparent 60%),
                radial-gradient(700px 400px at 95% 20%, rgba(37,99,235,0.08), transparent 60%),
                radial-gradient(600px 400px at 50% 100%, rgba(245,158,11,0.06), transparent 60%);
            pointer-events: none;
            z-index: 0;
            animation: floatBg 22s ease-in-out infinite alternate;
        }
        @keyframes floatBg {
            0% { transform: translate(0,0) scale(1); }
            100% { transform: translate(-30px, 20px) scale(1.05); }
        }
        body > * { position: relative; z-index: 1; }

        .grab-logo { height: 24px; width: auto; }

        /* ─── Reading progress bar ─── */
        .price-progress {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; z-index: 9999; pointer-events: none;
        }
        .price-progress-fill {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, #00B14F, #10d966, #f59e0b);
            transition: width 0.1s linear;
            box-shadow: 0 0 10px rgba(0,177,79,0.5);
        }

        /* ─── Hero enhancements ─── */
        .hero-section {
            position: relative;
            overflow: hidden;
        }
        .hero-section::before,
        .hero-section::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
        }
        .hero-section::before {
            top: -30%; right: -10%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 70%);
            animation: heroBlob 18s ease-in-out infinite;
        }
        .hero-section::after {
            bottom: -40%; left: -15%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(16,217,102,0.18), transparent 70%);
            animation: heroBlob 24s ease-in-out infinite reverse;
        }
        @keyframes heroBlob {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(30px, 20px) scale(1.08); }
        }
        .hero-section > .container { position: relative; z-index: 2; }
        .hero-section h1 {
            animation: fadeInUp 0.8s ease both;
            letter-spacing: -0.02em;
        }
        .hero-section p {
            animation: fadeInUp 0.8s ease 0.1s both;
        }
        .hero-section small {
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ─── Calculator polish ─── */
        .calculator-card {
            transition: box-shadow 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s cubic-bezier(0.4,0,0.2,1);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .calculator-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,0.08), 0 4px 12px rgba(0,177,79,0.08) !important;
        }
        .btn-calculate {
            position: relative; overflow: hidden;
            transition: all 0.2s cubic-bezier(0.4,0,0.2,1) !important;
        }
        .btn-calculate::before {
            content: '';
            position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.5s;
        }
        .btn-calculate:hover::before { left: 100%; }
        .btn-calculate:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,177,79,0.35) !important; }

        /* ─── Chart cards ─── */
        .chart-card {
            transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08), 0 6px 16px rgba(0,0,0,0.05);
        }

        /* ═══════════════════════════════════════════════════════════
           PROVIDER TABS — Compare / AWS / Google
           Filter table columns via body[data-provider-view]
           ═══════════════════════════════════════════════════════════ */
        .provider-tabs-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease both;
        }
        .provider-tabs {
            display: inline-flex;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 16px;
            padding: 6px;
            gap: 4px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06), 0 2px 6px rgba(0,0,0,0.04);
        }
        .provider-tab {
            background: transparent;
            border: none;
            padding: 10px 20px;
            border-radius: 11px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            position: relative;
        }
        .provider-tab .provider-emoji { font-size: 1rem; }
        .provider-tab:hover { color: #1f2937; background: rgba(0,0,0,0.03); }
        .provider-tab.active {
            background: #fff;
            color: #1f2937;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.04);
            transform: translateY(-1px);
        }
        .provider-tab[data-view="compare"].active { color: #7c3aed; }
        .provider-tab[data-view="compare"].active::before {
            content: '';
            position: absolute; inset: -2px;
            border-radius: 13px;
            background: linear-gradient(135deg, #7c3aed, #ec4899);
            z-index: -1;
            opacity: 0.15;
        }
        .provider-tab[data-view="als"].active { color: #d97706; }
        .provider-tab[data-view="als"].active::before {
            content: '';
            position: absolute; inset: -2px;
            border-radius: 13px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            z-index: -1;
            opacity: 0.15;
        }
        .provider-tab[data-view="google"].active { color: #2563eb; }
        .provider-tab[data-view="google"].active::before {
            content: '';
            position: absolute; inset: -2px;
            border-radius: 13px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            z-index: -1;
            opacity: 0.15;
        }

        /* Column visibility rules (default = compare, shows all) */
        /* AWS-only view: hide Google columns */
        body[data-provider-view="als"] .col-google-price,
        body[data-provider-view="als"] .col-google-free,
        body[data-provider-view="als"] .col-google-cost,
        body[data-provider-view="als"] .col-savings,
        body[data-provider-view="als"] .td-google,
        body[data-provider-view="als"] .td-free,
        body[data-provider-view="als"] .td-cost-google,
        body[data-provider-view="als"] .td-savings,
        body[data-provider-view="als"] .subtotal-google,
        body[data-provider-view="als"] .subtotal-savings {
            display: none !important;
        }

        /* Google-only view: hide AWS columns */
        body[data-provider-view="google"] .col-als-price,
        body[data-provider-view="google"] .col-als-cost,
        body[data-provider-view="google"] .col-savings,
        body[data-provider-view="google"] .td-als,
        body[data-provider-view="google"] .td-cost-als,
        body[data-provider-view="google"] .td-savings,
        body[data-provider-view="google"] .subtotal-als,
        body[data-provider-view="google"] .subtotal-savings {
            display: none !important;
        }

        /* Hide ALS-exclusive category rows when in Google-only view */
        body[data-provider-view="google"] .pricing-card.als-only-card {
            display: none;
        }
        /* Show "Google doesn't have this" banner for hidden category */
        body[data-provider-view="google"] .als-only-notice {
            display: block;
        }
        .als-only-notice { display: none; }

        /* Provider column color hints — subtle bg tint */
        body[data-provider-view="compare"] .td-als,
        body[data-provider-view="compare"] .td-cost-als,
        body[data-provider-view="compare"] .subtotal-als {
            background: rgba(245, 158, 11, 0.05);
        }
        body[data-provider-view="compare"] .td-google,
        body[data-provider-view="compare"] .td-free,
        body[data-provider-view="compare"] .td-cost-google,
        body[data-provider-view="compare"] .subtotal-google {
            background: rgba(37, 99, 235, 0.045);
        }

        /* ─── Pricing card polish ─── */
        .pricing-card {
            transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .pricing-card:hover {
            box-shadow: 0 16px 40px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
        }
        .pricing-card-header .category-badge {
            transition: transform 0.2s;
        }
        .pricing-card:hover .category-badge {
            transform: scale(1.05);
        }
        .unified-table tbody tr {
            transition: background-color 0.15s;
        }
        .unified-table tbody tr:hover:not(.row-disabled):not(.subtotal-row) {
            background: rgba(0,177,79,0.04);
        }

        /* ─── Key insights ─── */
        .insight-card {
            transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .insight-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1), 0 6px 16px rgba(0,0,0,0.06);
        }
        .insight-icon {
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .insight-card:hover .insight-icon {
            transform: scale(1.12) rotate(-4deg);
        }

        /* ─── Reveal-on-scroll ─── */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ─── Custom scrollbar ─── */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.15);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,177,79,0.4); }

        /* ─── ALS-only notice card ─── */
        .als-only-notice {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 20px;
            text-align: center;
            color: #78350f;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .als-only-notice i {
            color: #d97706;
            margin-right: 6px;
        }

        @media (max-width: 640px) {
            .provider-tab { padding: 8px 12px; font-size: 0.78rem; }
            .provider-tab .provider-label { display: none; }
        }
    </style>
</head>

<body>
    <div class="price-progress"><div class="price-progress-fill" id="priceProgressFill"></div></div>

    <!-- NAVBAR -->
    <nav class="pricing-navbar">
        <div class="container">
            <a href="{{ route('pageHome') }}" class="navbar-brand">
                <img src="logo.png" alt="Grab Logo" class="grab-logo">
            </a>
            <div class="navbar-actions">
                <div class="lang-switcher-dropdown">
                    <label for="langSelect" class="lang-label">{{ __('pricing.language') }}:</label>
                    <select id="langSelect" class="lang-select" aria-label="{{ __('pricing.language') }}" onchange="window.location.href='{{ route('pricing') }}?lang='+this.value">
                        @foreach(config('pricing_locales', []) as $code => $info)
                        <option value="{{ $code }}" {{ app()->getLocale() === $code ? 'selected' : '' }}>{{ $info['label'] }} ({{ $info['country'] }})</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('pricing.admin') }}" class="btn-back" hidden>
                    <i class="bi bi-gear"></i> {{ __('pricing.admin') }}
                </a>
                <a href="{{ route('pageHome') }}" class="btn-back">
                    <i class="bi bi-map"></i> {{ __('pricing.back_to_map') }}
                </a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero-section">
        <div class="container">
            <h1>{{ __('pricing.title') }}</h1>
            <p>{{ __('pricing.subtitle') }}</p>
            <small>{{ __('pricing.per_request') }}</small>
        </div>
    </section>

    <!-- CALCULATOR -->
    <section class="content-section">
        <div class="calculator-card animate-in">
            <h5><i class="bi bi-calculator"></i> {{ __('pricing.cost_calculator') }}</h5>
            <p class="calc-subtitle">{{ __('pricing.calc_subtitle') }}</p>

            <div class="calc-input-wrapper">
                <div class="calc-input-box">
                    <span class="calc-input-icon"><i class="bi bi-hash"></i></span>
                    <input type="text" id="volumeInput" value="50,000" inputmode="numeric" autocomplete="off" placeholder="e.g. 50,000" onkeydown="if(event.key==='Enter')calculateCosts()" onfocus="this.select()">
                    <button type="button" class="calc-input-clear" id="clearVolume" title="{{ __('pricing.clear') }}"><i class="bi bi-x-lg"></i></button>
                    <span class="calc-input-suffix">{{ __('pricing.req_month') }}</span>
                </div>
                <button class="btn-calculate" onclick="calculateCosts()">
                    <i class="bi bi-graph-up"></i> {{ __('pricing.calculate') }}
                </button>
            </div>

            <div class="calc-input-error" id="volumeError">{{ __('pricing.volume_error') }}</div>

            <div class="slider-wrapper">
                <input type="range" id="volumeSlider" class="volume-slider" min="0" max="500000" step="1000" value="50000">
                <div class="slider-labels">
                    <span>0</span>
                    <span>100K</span>
                    <span>200K</span>
                    <span>300K</span>
                    <span>400K</span>
                    <span>500K</span>
                </div>
            </div>

            <div id="calculatorSummary"></div>
        </div>
    </section>

    <!-- CHARTS -->
    <section class="content-section">
        <div class="charts-section animate-in delay-1">
            <div class="chart-card">
                <h6><i class="bi bi-bar-chart-fill" style="color:var(--grab-green)"></i> {{ __('pricing.cost_by_category') }}</h6>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h6><i class="bi bi-graph-up-arrow" style="color:var(--grab-green)"></i> {{ __('pricing.cost_curve') }}</h6>
                <div class="chart-controls" id="lineChartControls"></div>
                <div class="chart-wrapper">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- PROVIDER TABS — Compare / Amazon Location / Google Maps -->
    <section class="content-section" style="padding-bottom:0;">
        <div class="provider-tabs-wrap">
            <div class="provider-tabs" role="tablist" aria-label="Provider view">
                <button type="button" class="provider-tab active" data-view="compare" role="tab" aria-selected="true">
                    <span class="provider-emoji">⚖️</span>
                    <span class="provider-label">{{ __('pricing.tab_compare') ?? 'Compare' }}</span>
                </button>
                <button type="button" class="provider-tab" data-view="als" role="tab" aria-selected="false">
                    <span class="provider-emoji">🟠</span>
                    <span class="provider-label">Amazon Location</span>
                </button>
                <button type="button" class="provider-tab" data-view="google" role="tab" aria-selected="false">
                    <span class="provider-emoji">🔵</span>
                    <span class="provider-label">Google Maps</span>
                </button>
            </div>
        </div>
    </section>

    <!-- UNIFIED PRICING TABLES -->
    <section class="content-section pricing-tables-section animate-in delay-2">
        <!-- Shown only in Google-only view: notice that some categories are AWS-exclusive -->
        <div class="als-only-notice">
            <i class="bi bi-info-circle-fill"></i>
            {{ __('pricing.als_only_notice') ?? 'Some categories (Trackers, Geofences) are exclusive to Amazon Location Service — switch to Compare or AWS view to see them.' }}
        </div>

        @foreach($categories as $category)
        @php
        $allAlsOnly = $category->items->where('als_only', true)->count() === $category->items->count();
        @endphp
        <div class="pricing-card {{ $allAlsOnly ? 'als-only-card' : '' }}" data-category="{{ $category->slug }}">
            <div class="pricing-card-header">
                <div class="pricing-card-title-wrap">
                    <h5>{{ $category->name_translated ?? $category->name }}</h5>
                    @if($category->description_translated ?? $category->description)
                    <button type="button" class="info-trigger" data-info="{{ e($category->description_translated ?? $category->description) }}" title="{{ __('pricing.what_is_this') }}"><i class="bi bi-question-circle"></i></button>
                    @endif
                </div>
                <span class="category-badge {{ $allAlsOnly ? 'badge-als-only' : 'badge-both' }}">
                    {{ $allAlsOnly ? __('pricing.als_only') : __('pricing.both_platforms') }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="unified-table">
                    <colgroup>
                        <col style="width:40px;">
                        <col style="width:auto;">
                        <col style="width:80px;">
                        <col style="width:110px;">
                        <col style="width:110px;">
                        <col style="width:90px;">
                        <col style="width:110px;">
                        <col style="width:110px;">
                        <col style="width:100px;">
                    </colgroup>
                    <thead>
                        <tr>
                            @php
                            $recCount = $category->items->where('is_recommended', true)->count();
                            $totalCount = $category->items->count();
                            $checkAllChecked = $recCount === $totalCount && $totalCount > 0;
                            $checkAllIndeterminate = $recCount > 0 && $recCount < $totalCount;
                            @endphp
                            <th class="col-check"><input type="checkbox" class="check-all" data-category="{{ $category->slug }}" {{ $checkAllChecked ? 'checked' : '' }}></th>
                            <th>{{ __('pricing.api') }}</th>
                            <th>{{ __('pricing.tier') }}</th>
                            <th class="col-price col-als-price">{{ __('pricing.als_1k') }}</th>
                            <th class="col-price col-google-price">{{ __('pricing.google_1k') }}</th>
                            <th class="col-free col-google-free">{{ __('pricing.free_tier') }}</th>
                            <th class="col-cost col-als-cost">{{ __('pricing.als_cost') }}</th>
                            <th class="col-cost col-google-cost">{{ __('pricing.google_cost') }}</th>
                            <th class="col-savings">{{ __('pricing.savings') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->items as $item)
                        <tr data-item-id="{{ $item->id }}" data-tier-group="{{ $item->tier_group ?? '' }}" data-is-recommended="{{ $item->is_recommended ? '1' : '0' }}" class="{{ !$item->is_recommended ? 'row-disabled' : '' }}">
                            <td class="td-check"><input type="checkbox" class="item-check" data-item-id="{{ $item->id }}" data-tier-group="{{ $item->tier_group ?? '' }}" data-is-recommended="{{ $item->is_recommended ? '1' : '0' }}" {{ $item->is_recommended ? 'checked' : '' }}></td>
                            <td class="td-api">
                                <span class="api-name-cell">{{ $item->api_name_translated ?? $item->api_name }}</span>
                                @if($item->description_translated ?? $item->description)
                                <button type="button" class="info-trigger" data-info="{{ e($item->description_translated ?? $item->description) }}" title="{{ __('pricing.what_is_this') }}"><i class="bi bi-question-circle"></i></button>
                                @endif
                            </td>
                            <td>
                                @if($item->tier)
                                @php
                                $tierClass = match(strtolower($item->tier)) {
                                'core' => 'tier-core',
                                'advanced' => 'tier-advanced',
                                'premium' => 'tier-premium',
                                'stored' => 'tier-stored',
                                default => 'tier-default',
                                };
                                @endphp
                                <span class="tier-badge {{ $tierClass }}">{{ $item->tier }}</span>
                                @else
                                <span class="text-muted-cell">-</span>
                                @endif
                            </td>
                            <td class="td-price td-als">
                                {{ $item->als_price !== null ? '$' . number_format((float)$item->als_price, 4) : '-' }}
                            </td>
                            <td class="td-price td-google">
                                {{ $item->google_price !== null ? '$' . number_format((float)$item->google_price, 4) : 'N/A' }}
                            </td>
                            <td class="td-free">
                                @if($item->google_free_threshold > 0)
                                {{ number_format($item->google_free_threshold) }}
                                @else
                                <span class="text-muted-cell">-</span>
                                @endif
                            </td>
                            <td class="td-cost td-cost-als">-</td>
                            <td class="td-cost td-cost-google">-</td>
                            <td class="td-savings">-</td>
                        </tr>
                        @endforeach
                        <tr class="subtotal-row" data-category-subtotal="{{ $category->slug }}">
                            <td colspan="6"><strong>{{ __('pricing.subtotal') }}</strong></td>
                            <td class="td-cost td-cost-als subtotal-als">-</td>
                            <td class="td-cost td-cost-google subtotal-google">-</td>
                            <td class="td-savings subtotal-savings">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </section>

    <!-- KEY INSIGHTS -->
    <section class="content-section insights-section animate-in delay-3">
        <h4 style="text-align:center;font-weight:700;margin-bottom:24px;">{{ __('pricing.key_insights') }}</h4>
        <div class="insights-grid" id="insightsGrid">
            <div class="insight-card">
                <div class="insight-icon green">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <span class="insight-value" id="insightBreakeven">~14K</span>
                <h6>{{ __('pricing.breakeven_point') }}</h6>
                <p id="insightBreakevenDesc">{{ __('pricing.breakeven_desc', ['count' => '14,000']) }}</p>
            </div>
            <div class="insight-card">
                <div class="insight-icon blue">
                    <i class="bi bi-percent"></i>
                </div>
                <span class="insight-value" id="insightMapTiles">96%</span>
                <h6>{{ __('pricing.map_tiles_savings') }}</h6>
                <p id="insightMapTilesDesc">{{ __('pricing.map_tiles_desc', ['als' => '0.04', 'google' => '1.00']) }}</p>
            </div>
            <div class="insight-card">
                <div class="insight-icon amber">
                    <i class="bi bi-star"></i>
                </div>
                <span class="insight-value" id="insightExclusive">4+</span>
                <h6>{{ __('pricing.exclusive_features') }}</h6>
                <p>{{ __('pricing.exclusive_desc') }}</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="pricing-footer">
        <div class="container">
            <p>
                {{ __('pricing.sources') }}:
                <a href="https://aws.amazon.com/location/pricing/" target="_blank">Amazon Location Service Pricing</a>
                &middot;
                <a href="https://mapsplatform.google.com/pricing/" target="_blank">Google Maps Platform Pricing</a>
            </p>
            <p style="margin-top:4px;">
                <a href="{{ route('pricing.admin') }}">{{ __('pricing.manage_pricing') }}</a>
            </p>
        </div>
    </footer>

    <!-- INFO POPOVER (single instance) -->
    <div id="infoPopover" class="info-popover" role="dialog" aria-label="{{ __('pricing.api_description') }}">
        <button type="button" class="info-popover-close" aria-label="{{ __('pricing.close') }}"><i class="bi bi-x-lg"></i></button>
        <div class="info-popover-content"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @php
        $pricingT = [
            'summary_als' => __('pricing.summary_als'),
            'summary_google' => __('pricing.summary_google'),
            'summary_comparable' => __('pricing.summary_comparable'),
            'you_save' => __('pricing.you_save'),
            'per_month' => __('pricing.per_month'),
            'als_exclusive' => __('pricing.als_exclusive'),
            'trackers_geofences' => __('pricing.trackers_geofences'),
            'als_only_badge' => __('pricing.als_only_badge'),
            'monthly_requests' => __('pricing.monthly_requests'),
            'cost_usd' => __('pricing.cost_usd'),
            'breakeven_desc' => __('pricing.breakeven_desc', ['count' => ':count']),
            'map_tiles_desc' => __('pricing.map_tiles_desc', ['als' => ':als', 'google' => ':google']),
        ];
    @endphp
    <script>
        // ---- TRANSLATIONS ----
        const pricingT = @json($pricingT);

        // ---- DATA FROM DB ----
        const pricingData = @json($categories);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // ---- SYNC SLIDER & INPUT ----
        const volumeInput = document.getElementById('volumeInput');
        const volumeSlider = document.getElementById('volumeSlider');

        function getVolume() {
            return parseInt(volumeInput.value.replace(/,/g, '')) || 0;
        }

        function formatNumber(n) {
            return Math.max(0, Math.min(10000000, n)).toLocaleString();
        }

        // ---- DEBOUNCE ----
        let debounceCalc = null;
        let debounceLineChart = null;

        function scheduleAutoCalc() {
            if (debounceCalc) clearTimeout(debounceCalc);
            debounceCalc = setTimeout(() => {
                const v = getVolume();
                if (v > 0) calculateCosts();
                debounceCalc = null;
            }, 600);
        }

        function scheduleLineChartUpdate() {
            if (debounceLineChart) clearTimeout(debounceLineChart);
            debounceLineChart = setTimeout(() => {
                updateLineChartVolume();
                debounceLineChart = null;
            }, 150);
        }

        // ---- CLEAR BUTTON ----
        document.getElementById('clearVolume').addEventListener('click', () => {
            volumeInput.value = '';
            volumeSlider.value = 0;
            volumeInput.focus();
        });

        volumeSlider.addEventListener('input', () => {
            volumeInput.value = formatNumber(parseInt(volumeSlider.value));
            scheduleAutoCalc();
            scheduleLineChartUpdate();
        });

        volumeInput.addEventListener('input', () => {
            document.querySelector('.calc-input-box').classList.remove('has-error');
            document.getElementById('volumeError').classList.remove('visible');

            const raw = volumeInput.value.replace(/[^0-9]/g, '');
            const num = parseInt(raw) || 0;
            const clamped = Math.max(0, Math.min(10000000, num));

            const pos = volumeInput.selectionStart;
            const oldLen = volumeInput.value.length;

            volumeInput.value = formatNumber(clamped);

            const newLen = volumeInput.value.length;
            volumeInput.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));

            if (clamped <= parseInt(volumeSlider.max)) {
                volumeSlider.value = clamped;
            }
            scheduleAutoCalc();
            scheduleLineChartUpdate();
        });

        // ---- CHARTS ----
        let barChart = null;
        let lineChart = null;
        let lastCalcData = null;

        // ---- CHECKBOX LOGIC ----
        function getCheckedItemIds() {
            const checked = new Set();
            document.querySelectorAll('.item-check:checked').forEach(cb => {
                checked.add(parseInt(cb.dataset.itemId));
            });
            return checked;
        }

        function reRender() {
            if (lastCalcData) {
                renderResults(lastCalcData);
                renderBarChart(lastCalcData);
            }
        }

        // After calculate: sync tier_group checkboxes to match the tier that applies to current volume
        function syncTierGroupFromResults(data) {
            const tierGroupActive = new Map(); // tier_group -> item id that has cost
            data.results.forEach(cat => {
                cat.items.forEach(item => {
                    if (item.tier_group && (item.als_cost !== null || item.google_cost !== null)) {
                        tierGroupActive.set(item.tier_group, item.id);
                    }
                });
            });
            document.querySelectorAll('.item-check').forEach(cb => {
                const tg = cb.dataset.tierGroup;
                if (!tg) return; // Only sync tier_group items; leave recommended (non-tier) as-is
                const activeId = tierGroupActive.get(tg);
                const shouldCheck = activeId === parseInt(cb.dataset.itemId);
                cb.checked = shouldCheck;
                cb.closest('tr').classList.toggle('row-disabled', !shouldCheck);
            });
            // Sync "Select All" state
            document.querySelectorAll('.pricing-card').forEach(card => {
                const allBoxes = card.querySelectorAll('.item-check');
                const checkAll = card.querySelector('.check-all');
                const allChecked = [...allBoxes].every(b => b.checked);
                const someChecked = [...allBoxes].some(b => b.checked);
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && someChecked;
            });
        }

        // Mutual exclusivity: when checking a tier_group item, uncheck others in same group
        function syncTierGroupExclusivity(checkedCb) {
            const tg = checkedCb.dataset.tierGroup;
            if (!tg) return;
            const card = checkedCb.closest('.pricing-card');
            card.querySelectorAll(`.item-check[data-tier-group="${tg}"]`).forEach(b => {
                if (b !== checkedCb) {
                    b.checked = false;
                    b.closest('tr').classList.add('row-disabled');
                }
            });
        }

        // Individual checkbox → re-render + sync "Select All" + tier_group exclusivity
        document.querySelectorAll('.item-check').forEach(cb => {
            cb.addEventListener('change', () => {
                const row = cb.closest('tr');
                row.classList.toggle('row-disabled', !cb.checked);
                if (cb.checked) syncTierGroupExclusivity(cb);

                const card = cb.closest('.pricing-card');
                const allBoxes = card.querySelectorAll('.item-check');
                const checkAll = card.querySelector('.check-all');
                const allChecked = [...allBoxes].every(b => b.checked);
                const someChecked = [...allBoxes].some(b => b.checked);
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && someChecked;

                reRender();
            });
        });

        // "Select All" checkbox → toggle all in category (for tier_group: only first per group)
        document.querySelectorAll('.check-all').forEach(cb => {
            cb.addEventListener('change', () => {
                const card = cb.closest('.pricing-card');
                const seenTierGroups = new Set();
                card.querySelectorAll('.item-check').forEach(box => {
                    const tg = box.dataset.tierGroup;
                    if (tg) {
                        if (cb.checked && !seenTierGroups.has(tg)) {
                            seenTierGroups.add(tg);
                            box.checked = true;
                            box.closest('tr').classList.remove('row-disabled');
                        } else {
                            box.checked = false;
                            box.closest('tr').classList.add('row-disabled');
                        }
                    } else {
                        box.checked = cb.checked;
                        box.closest('tr').classList.toggle('row-disabled', !cb.checked);
                    }
                });
                reRender();
            });
        });

        // ---- CALCULATE ----
        async function calculateCosts() {
            const volume = getVolume();
            const inputBox = document.querySelector('.calc-input-box');
            const errorEl = document.getElementById('volumeError');

            if (!volume || volume <= 0) {
                inputBox.classList.add('has-error');
                errorEl.classList.add('visible');
                volumeInput.focus();
                return;
            }
            inputBox.classList.remove('has-error');
            errorEl.classList.remove('visible');

            try {
                const response = await fetch('/api/pricing/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        volume
                    }),
                });

                const data = await response.json();
                lastCalcData = data;
                syncTierGroupFromResults(data);
                renderResults(data);
                renderBarChart(data);
                updateLineChartVolume();
            } catch (err) {
                console.error('Calculate error:', err);
            }
        }

        // Format number as USD currency
        function formatUSD(value) {
            return value.toLocaleString('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function renderResults(data) {
            // --- Calculate totals (separate comparable vs exclusive) ---
            let comparableAls = 0,
                comparableGoogle = 0,
                exclusiveAls = 0;
            const checkedIds = getCheckedItemIds();

            data.results.forEach(cat => {
                cat.items.forEach(item => {
                    if (!checkedIds.has(item.id)) return;
                    if (!item.als_only && item.als_cost !== null && item.google_cost !== null) {
                        comparableAls += item.als_cost;
                        comparableGoogle += item.google_cost;
                    } else if (item.als_cost !== null) {
                        exclusiveAls += item.als_cost;
                    }
                });
            });

            const savings = comparableGoogle > 0 ? ((1 - (comparableAls / comparableGoogle)) * 100).toFixed(1) : 0;
            const savedAmount = comparableGoogle - comparableAls;

            // --- Summary cards ---
            const summaryContainer = document.getElementById('calculatorSummary');
            summaryContainer.innerHTML = `<div class="result-summary">
                <div class="summary-card summary-als">
                    <div class="summary-label">${pricingT.summary_als}</div>
                    <div class="summary-value">${formatUSD(comparableAls)}</div>
                    <div class="summary-sub">${pricingT.summary_comparable}</div>
                </div>
                <div class="summary-card summary-google">
                    <div class="summary-label">${pricingT.summary_google}</div>
                    <div class="summary-value">${formatUSD(comparableGoogle)}</div>
                    <div class="summary-sub">${pricingT.summary_comparable}</div>
                </div>
                <div class="summary-card summary-savings">
                    <div class="summary-label">${pricingT.you_save}</div>
                    <div class="summary-value">${savings}%</div>
                    <div class="summary-sub">${formatUSD(savedAmount)}${pricingT.per_month}</div>
                </div>
                <div class="summary-card summary-exclusive">
                    <div class="summary-label">${pricingT.als_exclusive}</div>
                    <div class="summary-value">${formatUSD(exclusiveAls)}</div>
                    <div class="summary-sub">${pricingT.trackers_geofences}</div>
                </div>
            </div>`;

            // --- Update table cells per item ---
            data.results.forEach(cat => {
                let catComparableAls = 0,
                    catComparableGoogle = 0;
                let catAlsAll = 0,
                    catGoogleAll = 0;

                cat.items.forEach(item => {
                    const row = document.querySelector(`tr[data-item-id="${item.id}"]`);
                    if (!row) return;

                    const isChecked = checkedIds.has(item.id);

                    if (isChecked) {
                        const alsCostStr = item.als_cost !== null ? formatUSD(item.als_cost) : '-';
                        const googleCostStr = item.google_cost !== null ? formatUSD(item.google_cost) : '-';

                        row.querySelector('.td-cost-als').textContent = alsCostStr;
                        row.querySelector('.td-cost-google').textContent = googleCostStr;

                        if (item.als_only) {
                            row.querySelector('.td-savings').innerHTML = '<span class="als-only-badge">' + pricingT.als_only_badge + '</span>';
                        } else if (item.savings_percent !== null && item.savings_percent > 0) {
                            row.querySelector('.td-savings').innerHTML = `<span class="savings-badge"><i class="bi bi-check-circle-fill"></i> ${item.savings_percent}%</span>`;
                        } else {
                            row.querySelector('.td-savings').textContent = '-';
                        }

                        // Subtotal counts
                        if (item.als_cost !== null) catAlsAll += item.als_cost;
                        if (item.google_cost !== null) catGoogleAll += item.google_cost;
                        if (!item.als_only && item.als_cost !== null && item.google_cost !== null) {
                            catComparableAls += item.als_cost;
                            catComparableGoogle += item.google_cost;
                        }
                    } else {
                        row.querySelector('.td-cost-als').textContent = '-';
                        row.querySelector('.td-cost-google').textContent = '-';
                        row.querySelector('.td-savings').textContent = '-';
                    }
                });

                // Update subtotal row
                const subtotalRow = document.querySelector(`tr[data-category-subtotal="${cat.slug}"]`);
                if (subtotalRow) {
                    subtotalRow.querySelector('.subtotal-als').innerHTML = `<strong>${formatUSD(catAlsAll)}</strong>`;
                    subtotalRow.querySelector('.subtotal-google').innerHTML = `<strong>${formatUSD(catGoogleAll)}</strong>`;
                    const catSavings = catComparableGoogle > 0 ? ((1 - (catComparableAls / catComparableGoogle)) * 100).toFixed(1) : '-';
                    subtotalRow.querySelector('.subtotal-savings').innerHTML = catComparableGoogle > 0 ? `<strong>${catSavings}%</strong>` : '-';
                }
            });
        }

        // ---- BAR CHART ----
        function renderBarChart(data) {
            const ctx = document.getElementById('barChart').getContext('2d');

            const labels = [];
            const alsData = [];
            const googleData = [];

            const checkedIds = getCheckedItemIds();
            data.results.forEach(cat => {
                labels.push(cat.name);
                let alsTotal = 0,
                    googleTotal = 0;
                cat.items.forEach(item => {
                    if (!checkedIds.has(item.id)) return;
                    alsTotal += item.als_cost || 0;
                    googleTotal += item.google_cost || 0;
                });
                alsData.push(parseFloat(alsTotal.toFixed(2)));
                googleData.push(parseFloat(googleTotal.toFixed(2)));
            });

            if (barChart) barChart.destroy();

            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: pricingT.summary_als,
                            data: alsData,
                            backgroundColor: 'rgba(0, 177, 79, 0.8)',
                            borderColor: '#00B14F',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                        {
                            label: pricingT.summary_google,
                            data: googleData,
                            backgroundColor: 'rgba(66, 133, 244, 0.8)',
                            borderColor: '#4285F4',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 8, right: 8, bottom: 0, left: 8 }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: $${ctx.raw.toFixed(2)}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => '$' + v
                            }
                        }
                    }
                }
            });
        }

        // ---- LINE CHART ----
        let activeLineChartItem = null;
        let comparableLineItems = [];

        function buildLineChartControls() {
            const container = document.getElementById('lineChartControls');
            comparableLineItems = [];

            pricingData.forEach(cat => {
                cat.items.forEach(item => {
                    if (item.als_price !== null && item.google_price !== null) {
                        const label = (item.api_name_translated || item.api_name) + (item.tier ? ` (${item.tier})` : '');
                        comparableLineItems.push({
                            label,
                            alsRate: parseFloat(item.als_price),
                            googleRate: parseFloat(item.google_price),
                            freeThreshold: item.google_free_threshold || 0,
                        });
                    }
                });
            });

            if (comparableLineItems.length === 0) return;

            comparableLineItems.forEach((item, idx) => {
                const btn = document.createElement('button');
                btn.className = 'btn-chip' + (idx === 0 ? ' active' : '');
                btn.textContent = item.label.length > 25 ? item.label.substring(0, 22) + '...' : item.label;
                btn.title = item.label;
                btn.onclick = () => {
                    container.querySelectorAll('.btn-chip').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    activeLineChartItem = item;
                    renderLineChart(item);
                };
                container.appendChild(btn);
            });

            activeLineChartItem = comparableLineItems[0];
            renderLineChart(activeLineChartItem);
        }

        function updateLineChartVolume() {
            if (activeLineChartItem) renderLineChart(activeLineChartItem);
        }

        function renderLineChart(item) {
            const ctx = document.getElementById('lineChart').getContext('2d');
            const maxVolume = getVolume() || 50000;
            const steps = 25;
            const stepSize = maxVolume / steps;

            const volumes = [];
            const alsLine = [];
            const googleLine = [];

            for (let i = 0; i <= steps; i++) {
                const v = Math.round(i * stepSize);
                volumes.push(v);
                alsLine.push(parseFloat(((v / 1000) * item.alsRate).toFixed(2)));
                const billable = Math.max(0, v - item.freeThreshold);
                googleLine.push(parseFloat(((billable / 1000) * item.googleRate).toFixed(2)));
            }

            if (lineChart) lineChart.destroy();

            lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: volumes.map(v => v >= 1000 ? (v / 1000) + 'K' : v),
                    datasets: [{
                            label: pricingT.summary_als,
                            data: alsLine,
                            borderColor: '#00B14F',
                            backgroundColor: 'rgba(0,177,79,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                        {
                            label: pricingT.summary_google,
                            data: googleLine,
                            borderColor: '#4285F4',
                            backgroundColor: 'rgba(66,133,244,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: $${ctx.raw.toFixed(2)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: pricingT.monthly_requests
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: pricingT.cost_usd
                            },
                            ticks: {
                                callback: v => '$' + v
                            }
                        }
                    }
                }
            });
        }

        // ---- KEY INSIGHTS (dynamic from pricingData) ----
        function renderKeyInsights() {
            let breakeven = null;
            let mapTilesItem = null;
            let exclusiveCount = 0;

            pricingData.forEach(cat => {
                cat.items.forEach(item => {
                    if (item.als_only) exclusiveCount++;
                    if (item.api_name === 'Map Tiles' && item.als_price != null && item.google_price != null) {
                        mapTilesItem = item;
                    }
                    if (breakeven == null && item.als_price != null && item.google_price != null && (item.google_free_threshold || 0) > 0) {
                        const als = parseFloat(item.als_price);
                        const google = parseFloat(item.google_price);
                        const free = item.google_free_threshold || 0;
                        if (als < google) {
                            const v = (google * free) / (google - als);
                            breakeven = Math.round(v);
                        }
                    }
                });
            });

            const breakevenEl = document.getElementById('insightBreakeven');
            const breakevenDescEl = document.getElementById('insightBreakevenDesc');
            if (breakeven != null && breakevenEl) {
                breakevenEl.textContent = breakeven >= 1000 ? '~' + (breakeven / 1000) + 'K' : '~' + breakeven;
                breakevenDescEl.textContent = pricingT.breakeven_desc.replace(':count', breakeven.toLocaleString());
            }

            const mapTilesEl = document.getElementById('insightMapTiles');
            const mapTilesDescEl = document.getElementById('insightMapTilesDesc');
            if (mapTilesItem && mapTilesEl) {
                const als = parseFloat(mapTilesItem.als_price);
                const google = parseFloat(mapTilesItem.google_price);
                const pct = google > 0 ? Math.round((1 - als / google) * 100) : 0;
                mapTilesEl.textContent = pct + '%';
                mapTilesDescEl.textContent = pricingT.map_tiles_desc.replace(':als', als.toFixed(2)).replace(':google', google.toFixed(2));
            }

            const exclusiveEl = document.getElementById('insightExclusive');
            if (exclusiveEl) exclusiveEl.textContent = exclusiveCount > 0 ? exclusiveCount + '+' : '4+';
        }

        // ---- INFO POPOVER ----
        (function initInfoPopover() {
            const popover = document.getElementById('infoPopover');
            const content = popover.querySelector('.info-popover-content');
            let hideTimeout = null;

            function show(trigger, text) {
                if (hideTimeout) clearTimeout(hideTimeout);
                content.textContent = text;
                popover.style.top = '-9999px';
                popover.style.left = '0';
                popover.classList.add('visible');

                requestAnimationFrame(() => {
                    const rect = trigger.getBoundingClientRect();
                    const popRect = popover.getBoundingClientRect();
                    let top = rect.top - popRect.height - 10;
                    let left = rect.left + (rect.width / 2) - (popRect.width / 2);

                    if (top < 10) {
                        top = rect.bottom + 10;
                        popover.classList.add('above');
                    } else {
                        popover.classList.remove('above');
                    }
                    if (left < 10) left = 10;
                    if (left + popRect.width > window.innerWidth - 10) left = window.innerWidth - popRect.width - 10;

                    popover.style.top = top + 'px';
                    popover.style.left = left + 'px';
                });
            }

            function hide() {
                hideTimeout = setTimeout(() => popover.classList.remove('visible'), 50);
            }

            document.querySelectorAll('.info-trigger').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const text = btn.dataset.info;
                    if (!text) return;
                    if (popover.classList.contains('visible') && content.textContent === text) {
                        popover.classList.remove('visible');
                        return;
                    }
                    show(btn, text);
                });
            });

            const closeBtn = popover.querySelector('.info-popover-close');
            if (closeBtn) closeBtn.addEventListener('click', () => popover.classList.remove('visible'));

            document.addEventListener('click', (e) => {
                if (e.target.closest('.info-trigger') || e.target.closest('#infoPopover')) return;
                hide();
            });
        })();

        // Sync "Select All" state based on current item checkboxes
        function syncCheckAllState() {
            document.querySelectorAll('.pricing-card').forEach(card => {
                const allBoxes = card.querySelectorAll('.item-check');
                const checkAll = card.querySelector('.check-all');
                const allChecked = [...allBoxes].every(b => b.checked);
                const someChecked = [...allBoxes].some(b => b.checked);
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && someChecked;
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderKeyInsights();
            syncCheckAllState();
            buildLineChartControls();
            calculateCosts();

            /* ═══ Provider tabs (Compare / AWS / Google) ═══ */
            const savedView = (() => {
                try { return localStorage.getItem('pricing_provider_view') || 'compare'; } catch (_) { return 'compare'; }
            })();
            function setProviderView(view) {
                document.body.setAttribute('data-provider-view', view);
                document.querySelectorAll('.provider-tab').forEach(t => {
                    const active = t.dataset.view === view;
                    t.classList.toggle('active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                try { localStorage.setItem('pricing_provider_view', view); } catch (_) {}
                // Charts re-render so bars reflect visible providers
                if (typeof reRender === 'function') reRender();
            }
            document.querySelectorAll('.provider-tab').forEach(tab => {
                tab.addEventListener('click', () => setProviderView(tab.dataset.view));
            });
            setProviderView(savedView);

            /* ═══ Reading progress bar ═══ */
            const bar = document.getElementById('priceProgressFill');
            if (bar) {
                const updateBar = () => {
                    const doc = document.documentElement;
                    const scrollTop = window.scrollY || doc.scrollTop;
                    const scrollHeight = doc.scrollHeight - doc.clientHeight;
                    const pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
                    bar.style.width = pct + '%';
                };
                window.addEventListener('scroll', updateBar, { passive: true });
                updateBar();
            }

            /* ═══ Reveal-on-scroll for key sections ═══ */
            const revealTargets = document.querySelectorAll(
                '.pricing-card, .insight-card, .chart-card, .calculator-card'
            );
            revealTargets.forEach((el, i) => {
                el.classList.add('reveal');
                el.style.transitionDelay = Math.min(i * 45, 260) + 'ms';
            });
            const revealObs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        revealObs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            revealTargets.forEach(el => revealObs.observe(el));
        });
    </script>
</body>

</html>