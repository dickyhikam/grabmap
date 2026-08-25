@extends('layouts.report')

@section('title', __('apikeys.share_report_title'))

@push('styles')
    :root {
        --bar-a: #cae5d4; --bar-b: #bbdfc8;
        --bar-top-a: #0b7c40; --bar-top-b: #066934; --bar-accent: #066934;
    }
    :root[data-theme="dark"] {
        --bar-a: rgba(0, 177, 79, 0.26); --bar-b: rgba(0, 177, 79, 0.16);
        --bar-top-a: #14b862; --bar-top-b: #0d9a50; --bar-accent: #0d9a50;
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]):not([data-theme="dark"]) {
            --bar-a: rgba(0, 177, 79, 0.26); --bar-b: rgba(0, 177, 79, 0.16);
            --bar-top-a: #14b862; --bar-top-b: #0d9a50; --bar-accent: #0d9a50;
        }
    }

    /* ---------- Kepala laporan (identitas klien) ---------- */
    .report-id {
        display: flex; align-items: center; gap: 16px;
        background: var(--card); border-radius: var(--r-card);
        padding: 18px 20px; margin-bottom: 16px;
        box-shadow: var(--shadow-card);
    }
    .report-id-mark {
        width: 54px; height: 54px; border-radius: 16px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--green-soft); color: var(--green-text); font-size: 1.3rem;
        overflow: hidden;
    }
    .report-id-mark img { width: 100%; height: 100%; object-fit: contain; }
    .report-id-eyebrow {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.12em;
        text-transform: uppercase; color: var(--muted);
    }
    .report-id-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 1.45rem; letter-spacing: -0.03em;
        line-height: 1.15; margin: 2px 0 0;
    }
    .report-id-meta {
        display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 7px;
        font-size: 0.73rem; color: var(--muted);
    }
    .report-id-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .report-id-meta .stale { color: var(--warn-fg); font-weight: 600; }

    .print-btn {
        display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0;
        border: none; border-radius: 999px; padding: 10px 18px;
        background: var(--surface); color: var(--ink);
        font-size: 0.78rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }
    .print-btn:hover { background: var(--green); color: #fff; }
    .print-btn:active { transform: scale(0.96); }

    @media (max-width: 620px) {
        .report-id { flex-wrap: wrap; }
        .print-btn { width: 100%; justify-content: center; }
    }

    /* ---------- Kartu biaya (jangkar visual) ---------- */
    .cost-card {
        background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 45%, #04703a 100%);
        border-radius: var(--r-card); padding: 18px 20px; color: #fff;
        position: relative; overflow: hidden;
        display: flex; flex-direction: column; justify-content: center;
    }
    .cost-card::after {
        content: ''; position: absolute; right: -60px; top: -70px;
        width: 190px; height: 190px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, transparent 65%);
    }
    .cost-card > * { position: relative; z-index: 1; }
    .cc-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .cc-lbl {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; color: rgba(255, 255, 255, 0.8);
    }
    .cc-val {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 1.75rem; letter-spacing: -0.03em;
        line-height: 1.1; margin: 8px 0 10px;
    }
    .cc-val .cents { color: rgba(255, 255, 255, 0.62); }
    .cc-foot {
        display: flex; justify-content: space-between; gap: 10px;
        font-size: 0.7rem; color: rgba(255, 255, 255, 0.85);
    }

    /* ---------- Cetak / simpan PDF ---------- */
    @media print {
        .print-btn, .dr, .report-top .dr, [data-dr] { display: none !important; }
        body { background: #fff; }
        .q-card, .report-id { box-shadow: none; border: 1px solid #e6e9eb; break-inside: avoid; }
        /* ---------- Kurs yang bisa digeser ---------- */
    .rate-card { margin-bottom: 16px; padding: 16px 20px 12px; }
    .rate-head { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
    .rate-lbl {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: var(--muted);
    }
    .rate-val { display: flex; align-items: baseline; gap: 5px; margin-top: 3px; }
    .rate-val .pfx { font-size: 0.95rem; font-weight: 700; color: var(--muted); }
    .rate-val input {
        width: 5.5ch; border: none; background: none; outline: none; padding: 0;
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
        font-size: 1.35rem; letter-spacing: -0.03em; color: var(--ink);
    }
    .rate-val input:focus { color: var(--green-text); }
    .rate-val .sfx { font-size: 0.72rem; font-weight: 600; color: var(--muted); }

    .rate-tag {
        margin-left: auto; align-self: center;
        font-size: 0.64rem; font-weight: 700; letter-spacing: 0.04em;
        background: var(--green-soft); color: var(--green-text);
        border-radius: 999px; padding: 4px 11px;
    }
    .rate-tag.custom { background: var(--warn-soft); color: var(--warn-fg); }

    .rate-reset {
        align-self: center; margin-left: auto;
        display: inline-flex; align-items: center; gap: 6px;
        border: none; border-radius: 999px; padding: 7px 14px;
        background: var(--surface); color: var(--ink);
        font-size: 0.72rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .rate-reset:hover { background: var(--green); color: #fff; }
    .rate-reset[hidden] { display: none; }
    .rate-reset:not([hidden]) ~ .rate-tag { margin-left: 0; }

    .rate-track { position: relative; padding-top: 14px; }

    .rate-track input[type="range"] {
        -webkit-appearance: none; appearance: none;
        width: 100%; height: 20px; background: none; outline: none; cursor: pointer; display: block;
    }
    .rate-track input[type="range"]::-webkit-slider-runnable-track {
        height: 6px; border-radius: 999px;
        background: linear-gradient(90deg, var(--green) 0 var(--pct, 50%), var(--line) var(--pct, 50%) 100%);
    }
    .rate-track input[type="range"]::-moz-range-track { height: 6px; border-radius: 999px; background: var(--line); }
    .rate-track input[type="range"]::-moz-range-progress { height: 6px; border-radius: 999px; background: var(--green); }
    .rate-track input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none;
        width: 18px; height: 18px; margin-top: -6px;
        border-radius: 50%; border: 3px solid #fff; background: var(--green);
        box-shadow: 0 2px 8px rgba(0, 177, 79, 0.45);
        transition: transform 0.14s cubic-bezier(0.34, 1.5, 0.5, 1);
    }
    .rate-track input[type="range"]::-moz-range-thumb {
        width: 18px; height: 18px; border-radius: 50%;
        border: 3px solid #fff; background: var(--green);
    }
    .rate-track input[type="range"]:hover::-webkit-slider-thumb { transform: scale(1.12); }

    /* Penanda kurs resmi di atas rel. */
    .rate-mark {
        position: absolute; top: 0; transform: translateX(-50%);
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        pointer-events: none;
    }
    .rate-mark .dot { width: 2px; height: 12px; border-radius: 2px; background: var(--faint); }
    .rate-mark .tx {
        font-size: 0.58rem; font-weight: 700; letter-spacing: 0.06em;
        text-transform: uppercase; color: var(--faint); white-space: nowrap;
        order: -1;
    }

    .rate-foot {
        display: flex; justify-content: space-between; gap: 10px;
        font-size: 0.65rem; color: var(--faint); margin-top: 2px;
    }
    .rate-foot .hint { color: var(--muted); text-align: center; }

    @media print { .rate-card { display: none; } }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(560px, 1fr));
        gap: 16px;
        align-items: start;
        margin-top: 16px;
    }
    @media (max-width: 620px) { .detail-grid { grid-template-columns: 1fr; } }

    .usage-grid { grid-template-columns: 1fr; }
        .cost-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }

    .stat-row {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px; margin-bottom: 16px;
    }
    @media (max-width: 760px) { .stat-row { grid-template-columns: 1fr; } }

    .stat-tile { display: flex; align-items: center; gap: 14px; }
    .stat-tile .ic {
        width: 44px; height: 44px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .stat-tile .val { font-size: 1.4rem; }
    .stat-tile .val .cents { color: var(--faint); }
    .stat-tile .lbl { font-size: 0.7rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-tile .sub { font-size: 0.68rem; color: var(--muted); margin-top: 2px; }

    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }

    /* ---------- Kurs yang bisa digeser ---------- */
    .rate-card { margin-bottom: 16px; padding: 16px 20px 12px; }
    .rate-head { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
    .rate-lbl {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: var(--muted);
    }
    .rate-val { display: flex; align-items: baseline; gap: 5px; margin-top: 3px; }
    .rate-val .pfx { font-size: 0.95rem; font-weight: 700; color: var(--muted); }
    .rate-val input {
        width: 5.5ch; border: none; background: none; outline: none; padding: 0;
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
        font-size: 1.35rem; letter-spacing: -0.03em; color: var(--ink);
    }
    .rate-val input:focus { color: var(--green-text); }
    .rate-val .sfx { font-size: 0.72rem; font-weight: 600; color: var(--muted); }

    .rate-tag {
        margin-left: auto; align-self: center;
        font-size: 0.64rem; font-weight: 700; letter-spacing: 0.04em;
        background: var(--green-soft); color: var(--green-text);
        border-radius: 999px; padding: 4px 11px;
    }
    .rate-tag.custom { background: var(--warn-soft); color: var(--warn-fg); }

    .rate-reset {
        align-self: center; margin-left: auto;
        display: inline-flex; align-items: center; gap: 6px;
        border: none; border-radius: 999px; padding: 7px 14px;
        background: var(--surface); color: var(--ink);
        font-size: 0.72rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .rate-reset:hover { background: var(--green); color: #fff; }
    .rate-reset[hidden] { display: none; }
    .rate-reset:not([hidden]) ~ .rate-tag { margin-left: 0; }

    .rate-track { position: relative; padding-top: 14px; }

    .rate-track input[type="range"] {
        -webkit-appearance: none; appearance: none;
        width: 100%; height: 20px; background: none; outline: none; cursor: pointer; display: block;
    }
    .rate-track input[type="range"]::-webkit-slider-runnable-track {
        height: 6px; border-radius: 999px;
        background: linear-gradient(90deg, var(--green) 0 var(--pct, 50%), var(--line) var(--pct, 50%) 100%);
    }
    .rate-track input[type="range"]::-moz-range-track { height: 6px; border-radius: 999px; background: var(--line); }
    .rate-track input[type="range"]::-moz-range-progress { height: 6px; border-radius: 999px; background: var(--green); }
    .rate-track input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none;
        width: 18px; height: 18px; margin-top: -6px;
        border-radius: 50%; border: 3px solid #fff; background: var(--green);
        box-shadow: 0 2px 8px rgba(0, 177, 79, 0.45);
        transition: transform 0.14s cubic-bezier(0.34, 1.5, 0.5, 1);
    }
    .rate-track input[type="range"]::-moz-range-thumb {
        width: 18px; height: 18px; border-radius: 50%;
        border: 3px solid #fff; background: var(--green);
    }
    .rate-track input[type="range"]:hover::-webkit-slider-thumb { transform: scale(1.12); }

    /* Penanda kurs resmi di atas rel. */
    .rate-mark {
        position: absolute; top: 0; transform: translateX(-50%);
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        pointer-events: none;
    }
    .rate-mark .dot { width: 2px; height: 12px; border-radius: 2px; background: var(--faint); }
    .rate-mark .tx {
        font-size: 0.58rem; font-weight: 700; letter-spacing: 0.06em;
        text-transform: uppercase; color: var(--faint); white-space: nowrap;
        order: -1;
    }

    .rate-foot {
        display: flex; justify-content: space-between; gap: 10px;
        font-size: 0.65rem; color: var(--faint); margin-top: 2px;
    }
    .rate-foot .hint { color: var(--muted); text-align: center; }

    @media print { .rate-card { display: none; } }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(560px, 1fr));
        gap: 16px;
        align-items: start;
        margin-top: 16px;
    }
    @media (max-width: 620px) { .detail-grid { grid-template-columns: 1fr; } }

    .usage-grid {
        display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        gap: 16px; align-items: start;
    }
    .usage-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    @media (max-width: 900px) { .usage-grid { grid-template-columns: 1fr; } }

    .u-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.82rem; }
    .u-table th {
        font-size: 0.68rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: 0.05em;
        text-align: left; padding: 0 14px 12px; white-space: nowrap;
    }
    .u-table td { padding: 11px 14px; border-top: 1px solid var(--line); }
    .u-table tbody tr:hover td { background: var(--surface); }
    .u-table tbody tr td:first-child { border-radius: 12px 0 0 12px; }
    .u-table tbody tr td:last-child { border-radius: 0 12px 12px 0; }
    .u-table tfoot td { padding: 10px 14px; border-top: 1px solid var(--line); }

    .q-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); flex-shrink: 0; }
    .q-track { height: 6px; background: var(--surface); border-radius: 999px; overflow: hidden; }
    .q-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--green), #4bd07f); }

    .cat-row { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .cat-row:last-child { border-bottom: none; }
    .cat-ic {
        width: 32px; height: 32px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;
        background: var(--surface);
    }
@endpush

@section('header-actions')
    @include('admin.partials.date-range')
@endsection

@if(!empty($keyTabs) && $keyTabs->count())
@section('top-tabs')
    {{-- Tab ini hanya berisi key yang memang tercakup link-nya. --}}
    <div class="rp-tabs" role="tablist">
        @php
            $tabBase = ['token' => $share->share_token, 'start' => $startDate, 'end' => $endDate];
        @endphp

        <a href="{{ route('usage-report.show', $tabBase) }}"
           class="rp-tab {{ $activeKey ? '' : 'on' }}" role="tab" data-no-loader>
            <i class="bi bi-collection"></i> {{ __('apikeys.tab_all_keys') }}
        </a>

        @foreach($keyTabs as $tab)
            <a href="{{ route('usage-report.show', $tabBase + ['key' => $tab->key_name]) }}"
               class="rp-tab {{ $activeKey === $tab->key_name ? 'on' : '' }}" role="tab" data-no-loader>
                {{ $tab->label ?: $tab->key_name }}
            </a>
        @endforeach
    </div>
@endsection
@endif

@section('content')
@php
    use App\Services\AwsLocationService;

    $reportTitle = $assignedCompany?->name ?? __('apikeys.share_report_title');

    // Saat satu key dipilih lewat tab, judulnya menyebut key itu supaya laporan
    // yang dicetak tidak ambigu.
    $activeKeyName = $activeKey ?? null;

    $rangeStart = \Carbon\Carbon::parse($startDate);
    $rangeEnd   = \Carbon\Carbon::parse($endDate);
    $rangeLabel = $rangeStart->wib()->translatedFormat('d M') . ' – ' . $rangeEnd->wib()->translatedFormat('d M Y');

    $daily = collect();
    for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
        $key = $d->format('Y-m-d');
        $daily[$key] = $metrics['daily'][$key] ?? 0;
    }

    $ops       = $metrics['operations'] ?? [];
    $totalReq  = $metrics['total'] ?? 0;
    $totalCost = AwsLocationService::estimateCost($ops);
    $tax       = $totalCost * $taxRate;
    $grand     = $totalCost + $tax;
    $opMax     = $ops ? max(array_values($ops)) : 1;

    $money = function ($v) {
        $p = explode('.', number_format($v, 2, '.', ','));
        return ['int' => $p[0], 'cents' => $p[1]];
    };

    // Biaya yang lebih kecil dari satu sen ditulis apa adanya dengan empat desimal
    // ($0.0017), bukan dibulatkan jadi $0.00 yang terbaca seperti tidak ada data.
    $usd = fn ($v) => '$' . number_format($v, $v > 0 && $v < 0.01 ? 4 : 2);
    $tiny = $grand > 0 && $grand < 0.01;
    $grandTiny = explode('.', number_format($grand, 4, '.', ''));
    $grandParts = $money($grand);

    $categories = [
        'maps'   => ['label' => __('dash.cat_maps'),   'icon' => 'bi-map',             'color' => '#00B14F', 'ops' => ['GetMapTile', 'GetTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites']],
        'places' => ['label' => __('dash.cat_places'), 'icon' => 'bi-search',          'color' => '#6366f1', 'ops' => ['SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace']],
        'routes' => ['label' => __('dash.cat_routes'), 'icon' => 'bi-sign-turn-right', 'color' => '#f59e0b', 'ops' => ['CalculateRoutes', 'CalculateRouteMatrix']],
    ];
@endphp

{{-- Laporan ini dibaca klien, jadi yang dibesarkan namanya sendiri — bukan
     judul generik. Logo dipakai kalau perusahaannya punya. --}}
<div class="report-id">
    <div class="report-id-mark">
        @if($assignedCompany?->logo_path)
            <img src="{{ asset($assignedCompany->logo_path) }}" alt="{{ $assignedCompany->name }}">
        @else
            <i class="bi bi-buildings"></i>
        @endif
    </div>

    <div style="flex:1;min-width:0;">
        <div class="report-id-eyebrow">
            {{ isset($usage) ? __('apikeys.share_company_title') : __('apikeys.share_report_title') }}
        </div>
        <h1 class="report-id-name">{{ $reportTitle }}</h1>
        <div class="report-id-meta">
            <span><i class="bi bi-calendar3"></i> {{ $rangeLabel }}</span>
            <span><i class="bi bi-clock"></i> {{ __('apikeys.share_days', ['count' => $days]) }}</span>
            @isset($usage)
                <span>
                    <i class="bi bi-key"></i>
                    @if($activeKeyName)
                        <span style="font-family:ui-monospace,monospace;">{{ $activeKeyName }}</span>
                    @else
                        {{ __('apikeys.share_keys_count', ['count' => count($usage['per_key'])]) }}
                    @endif
                </span>
            @endisset
            @if($fetchedAt)
                <span @class(['stale' => $fetchedAt->lt(now()->subDay())])>
                    <i class="bi bi-arrow-repeat"></i>
                    {{ __('apikeys.fetched', ['time' => $fetchedAt->wib()->format('d M H:i')]) }}
                </span>
            @endif
        </div>
    </div>

    <button type="button" class="print-btn" onclick="window.print()">
        <i class="bi bi-printer"></i> {{ __('apikeys.share_print') }}
    </button>
</div>

@if(!$fetchedAt)
    <div class="q-alert">
        <span class="q-alert-icon"><i class="bi bi-info-circle-fill"></i></span>
        <div class="q-alert-body">{{ __('apikeys.share_no_data') }}</div>
    </div>
@endif

@isset($usage)
    @if($usage['missing'] > 0)
        {{-- Halaman ini sengaja tidak menembak AWS; key tanpa snapshot ditandai
             apa adanya supaya angkanya tidak dikira nol pemakaian. --}}
        <div class="q-alert warn">
            <span class="q-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="q-alert-body">{{ __('apikeys.share_missing_keys', ['count' => $usage['missing']]) }}</div>
        </div>
    @endif
@endisset

@if($metrics['error'] ?? false)
    <div class="q-alert bad">
        <span class="q-alert-icon"><i class="bi bi-x-lg"></i></span>
        <div class="q-alert-body">{{ __('apikeys.share_data_unavailable') }}</div>
    </div>
@endif

<div class="stat-row">
    <div class="q-card stat-tile">
        <div class="ic tone-violet"><i class="bi bi-lightning-charge-fill"></i></div>
        <div>
            <div class="q-num val">{{ number_format($totalReq) }}</div>
            <div class="lbl">{{ __('apikeys.total_requests') }}</div>
        </div>
    </div>

    <div class="q-card stat-tile">
        <div class="ic tone-green"><i class="bi bi-graph-up"></i></div>
        <div>
            <div class="q-num val">{{ $totalReq > 0 ? number_format($totalReq / max($days, 1), 0) : 0 }}</div>
            <div class="lbl">{{ __('apikeys.avg_per_day') }}</div>
        </div>
    </div>

    <div class="cost-card">
        <div class="cc-top">
            <span class="cc-lbl">{{ __('apikeys.est_cost') }}</span>
            <i class="bi bi-wallet2"></i>
        </div>
        <div class="cc-val">
            @if($tiny)
                ${{ $grandTiny[0] }}<span class="cents">.{{ $grandTiny[1] }}</span>
            @else
                ${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span>
            @endif
        </div>
        <div class="cc-foot">
            <span data-idr="{{ $grand }}">≈ Rp {{ number_format($grand * $idrRate, 0, ',', '.') }}</span>
            <span>{{ __('apikeys.incl_tax', ['pct' => round($taxRate * 100, 2)]) }}</span>
        </div>
    </div>
</div>

{{-- Kurs bawaan diambil dari menu Kurs & Pajak. Pembaca boleh menggesernya
     untuk hitungan kasar sendiri; angka rupiah di halaman ini ikut berubah,
     sementara angka dolarnya tidak tersentuh. --}}
<div class="q-card rate-card" data-rate-card
     data-default="{{ (int) $idrRate }}"
     data-min="{{ (int) round($idrRate * 0.8) }}"
     data-max="{{ (int) round($idrRate * 1.2) }}">
    <div class="rate-head">
        <div>
            <div class="rate-lbl">{{ __('apikeys.rate_title') }}</div>
            <div class="rate-val">
                <span class="pfx">Rp</span>
                <input type="text" inputmode="numeric" data-rate-num value="{{ number_format($idrRate, 0, ',', '.') }}">
                <span class="sfx">/ USD</span>
            </div>
        </div>

        <button type="button" class="rate-reset" data-rate-reset hidden>
            <i class="bi bi-arrow-counterclockwise"></i> {{ __('apikeys.rate_reset') }}
        </button>

        <span class="rate-tag" data-rate-tag
              data-official="{{ __('apikeys.rate_official') }}"
              data-custom="{{ __('apikeys.rate_custom') }}">{{ __('apikeys.rate_official') }}</span>
    </div>

    <div class="rate-track">
        <input type="range" data-rate-range
               min="{{ (int) round($idrRate * 0.8) }}"
               max="{{ (int) round($idrRate * 1.2) }}"
               step="10" value="{{ (int) $idrRate }}">
        {{-- Penanda kurs resmi: posisinya tetap, jadi kelihatan seberapa jauh
             geserannya dari angka yang dipakai sistem. --}}
        <span class="rate-mark" data-rate-mark><span class="dot"></span><span class="tx">{{ __('apikeys.rate_official') }}</span></span>
    </div>

    <div class="rate-foot">
        <span>Rp {{ number_format(round($idrRate * 0.8), 0, ',', '.') }}</span>
        <span class="hint">{{ __('apikeys.rate_hint') }}</span>
        <span>Rp {{ number_format(round($idrRate * 1.2), 0, ',', '.') }}</span>
    </div>
</div>

<div class="usage-grid">
    <div class="usage-col">
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-bar-chart-line"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('apikeys.daily_chart') }}</div>
                        <div class="q-card-sub">{{ __('apikeys.daily_chart_sub', ['range' => $rangeLabel]) }}</div>
                    </div>
                </div>
            </div>

            @if($daily->sum() === 0)
                <div class="q-empty"><i class="bi bi-bar-chart"></i>{{ __('apikeys.no_usage') }}</div>
            @else
                @include('admin.partials.bar-chart', ['series' => $daily])
            @endif
        </div>

    </div>

    <div class="usage-col">
        @if($totalReq > 0)
            <div class="q-card">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-wallet2"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('apikeys.cat_title') }}</div>
                            <div class="q-card-sub">{{ __('dash.cat_sub') }}</div>
                        </div>
                    </div>
                </div>

                @foreach($categories as $cat)
                    @php
                        $catCount = collect($cat['ops'])->sum(fn ($op) => $ops[$op] ?? 0);
                        $catCost  = AwsLocationService::estimateCost(collect($cat['ops'])->mapWithKeys(fn ($op) => [$op => $ops[$op] ?? 0])->all());
                        $sharePct = $totalCost > 0 ? ($catCost / $totalCost) * 100 : 0;
                    @endphp
                    <div class="cat-row">
                        <div class="cat-ic" style="color: {{ $cat['color'] }};"><i class="bi {{ $cat['icon'] }}"></i></div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div style="font-size:0.82rem;font-weight:600;">{{ $cat['label'] }}</div>
                            <div style="font-size:0.7rem;color:var(--muted);">{{ number_format($catCount) }} {{ __('dash.requests_word') }}</div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:0.85rem;font-weight:600;">{{ $usd($catCost) }}</div>
                            <div style="font-size:0.7rem;color:var(--muted);">{{ number_format($sharePct, 1) }}%</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Tabel rincian ditaruh berdampingan supaya lebar halaman terpakai dan tidak
     ada kolom yang berhenti duluan. Satu kartu saja otomatis melebar penuh. --}}
<div class="detail-grid">
        @if(!empty($ops))
            <div class="q-card">
                <div class="q-card-head">
                    <div>
                        <div class="q-card-title">{{ __('apikeys.ops_title') }}</div>
                        <div class="q-card-sub">{{ __('apikeys.ops_sub') }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="u-table">
                        <thead>
                            <tr>
                                <th style="width:200px;">{{ __('apikeys.op') }}</th>
                                <th>{{ __('apikeys.usage') }}</th>
                                <th class="text-end">{{ __('apikeys.requests') }}</th>
                                <th class="text-end">{{ __('apikeys.est_cost') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ops as $op => $count)
                                @php
                                    $rate = AwsLocationService::PRICING[$op] ?? 0;
                                    $cost = ($count / 1000) * $rate;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="q-dot"></span>
                                            <span class="fw-semibold">{{ $op }}</span>
                                        </div>
                                    </td>
                                    <td style="min-width:130px;">
                                        <div class="q-track"><div class="q-fill" style="width: {{ ($count / $opMax) * 100 }}%;"></div></div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($count) }}</td>
                                    <td class="text-end fw-semibold">{{ $usd($cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="color:var(--muted);">{{ __('apikeys.subtotal') }}</td>
                                <td class="text-end fw-semibold">{{ number_format(array_sum($ops)) }}</td>
                                <td class="text-end fw-semibold">{{ $usd($totalCost) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="color:var(--muted);">{{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }}</td>
                                <td class="text-end" style="color:var(--muted);">{{ $usd($tax) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="fw-bold">{{ __('apikeys.total_vat') }}</td>
                                <td class="text-end">
                                    <div class="q-num" style="font-size:1.05rem;color:var(--green-text);">
                                        @if($tiny)
                                            ${{ $grandTiny[0] }}<span class="cents">.{{ $grandTiny[1] }}</span>
                                        @else
                                            ${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        @isset($usage)
            <div class="q-card">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-key-fill"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('apikeys.share_per_key') }}</div>
                            <div class="q-card-sub">{{ __('apikeys.share_per_key_sub') }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="u-table">
                        <thead>
                            <tr>
                                <th>{{ __('apikeys.share_key_col') }}</th>
                                <th class="text-end">{{ __('apikeys.requests') }}</th>
                                <th class="text-end">{{ __('apikeys.est_cost') }}</th>
                                <th class="text-end">{{ __('apikeys.share_portion') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usage['per_key'] as $row)
                                <tr>
                                    <td>
                                        <span style="font-family:ui-monospace,monospace;font-weight:600;">{{ $row['name'] }}</span>
                                        @if($row['label'])
                                            <span style="display:block;font-size:0.7rem;color:var(--muted);">{{ $row['label'] }}</span>
                                        @endif
                                        @unless($row['has_data'])
                                            <span style="display:block;font-size:0.7rem;color:var(--warn-fg);">
                                                {{ __('apikeys.share_key_no_data') }}
                                            </span>
                                        @endunless
                                    </td>
                                    <td class="text-end">{{ number_format($row['total']) }}</td>
                                    <td class="text-end">{{ $usd($row['cost']) }}</td>
                                    <td class="text-end" style="color:var(--muted);">
                                        {{ $totalCost > 0 ? number_format(($row['cost'] / $totalCost) * 100, 1) : '0,0' }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endisset
</div>
@endsection

@section('footer-note')
    {{ __('apikeys.share_disclaimer') }}
    @if($share->share_expires_at)
        · {{ __('apikeys.share_expires', ['date' => $share->share_expires_at->wib()->translatedFormat('d M Y')]) }}
    @endif
@endsection

@push('scripts')
<script>
    // Kurs yang bisa digeser. Hanya mengubah tampilan rupiah di halaman ini —
    // angka dolarnya (yang datang dari AWS) tidak ikut disentuh.
    (function () {
        const card = document.querySelector('[data-rate-card]');
        if (!card) return;

        const range = card.querySelector('[data-rate-range]');
        const num   = card.querySelector('[data-rate-num]');
        const mark  = card.querySelector('[data-rate-mark]');
        const tag   = card.querySelector('[data-rate-tag]');
        const reset = card.querySelector('[data-rate-reset]');

        const DEFAULT = Number(card.dataset.default);
        const MIN = Number(card.dataset.min);
        const MAX = Number(card.dataset.max);
        const STORE = 'gm-report-rate';

        const clamp = (v) => Math.min(Math.max(v, MIN), MAX);
        const group = (v) => Math.round(v).toLocaleString('id-ID');
        const pct = (v) => ((v - MIN) / (MAX - MIN)) * 100;

        // Penanda kurs resmi dipasang sekali; posisinya tidak ikut bergeser.
        mark.style.left = pct(DEFAULT) + '%';

        function paint(value, typing) {
            range.value = value;
            range.style.setProperty('--pct', pct(value) + '%');
            if (!typing) num.value = group(value);

            const official = Math.round(value) === DEFAULT;
            tag.textContent = official ? tag.dataset.official : tag.dataset.custom;
            tag.classList.toggle('custom', !official);
            reset.hidden = official;

            document.querySelectorAll('[data-idr]').forEach((el) => {
                el.textContent = '≈ Rp ' + group(Number(el.dataset.idr) * value);
            });

            try {
                official ? localStorage.removeItem(STORE) : localStorage.setItem(STORE, value);
            } catch (e) {}
        }

        range.addEventListener('input', () => paint(Number(range.value)));

        num.addEventListener('input', () => {
            const digits = num.value.replace(/[^\d]/g, '');
            num.value = digits ? Number(digits).toLocaleString('id-ID') : '';
            if (digits) paint(clamp(Number(digits)), true);
        });
        num.addEventListener('blur', () => paint(clamp(Number(num.value.replace(/[^\d]/g, '')) || DEFAULT)));

        reset.addEventListener('click', () => paint(DEFAULT));

        let start = DEFAULT;
        try {
            const saved = Number(localStorage.getItem(STORE));
            if (saved) start = clamp(saved);
        } catch (e) {}

        paint(start);
    })();
</script>
@endpush
