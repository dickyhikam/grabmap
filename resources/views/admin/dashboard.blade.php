@extends('layouts.admin-v2')

@section('title', 'Dashboard')

@push('styles')
    /* Token khusus grafik — ikut tema, pola sama dengan token di layout. */
    :root {
        --bar-a: #cae5d4;
        --bar-b: #bbdfc8;
        --bar-top-a: #0b7c40;
        --bar-top-b: #066934;
        --bar-accent: #066934;
    }
    :root[data-theme="dark"] {
        --bar-a: rgba(0, 177, 79, 0.26);
        --bar-b: rgba(0, 177, 79, 0.16);
        --bar-top-a: #14b862;
        --bar-top-b: #0d9a50;
        --bar-accent: #0d9a50;
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]):not([data-theme="dark"]) {
            --bar-a: rgba(0, 177, 79, 0.26);
            --bar-b: rgba(0, 177, 79, 0.16);
            --bar-top-a: #14b862;
            --bar-top-b: #0d9a50;
            --bar-accent: #0d9a50;
        }
    }

    /* ---------- Grid utama ---------- */
    /* Dua wilayah: kiri (dua kolom + tabel melebar di bawahnya) dan sidebar kanan.
       Sengaja tanpa row-span supaya tinggi sidebar tidak menyisakan celah di kiri. */
    .dash-grid {
        display: grid;
        grid-template-columns: minmax(0, 2.6fr) minmax(0, 1.15fr);
        gap: 16px;
        align-items: start;
    }
    .dash-left { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .dash-left-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
        gap: 16px;
        align-items: start;
    }
    .dash-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

    @media (max-width: 1200px) {
        .dash-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 760px) {
        .dash-left-top { grid-template-columns: 1fr; }
    }

    /* ---------- Kartu biaya (gaya kartu kredit) ---------- */
    .cost-card {
        background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 45%, #04703a 100%);
        border-radius: 18px;
        padding: 18px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .cost-card::after {
        content: '';
        position: absolute;
        right: -60px; top: -70px;
        width: 190px; height: 190px;
        background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 65%);
        border-radius: 50%;
    }
    .cost-card > * { position: relative; z-index: 1; }
    .cost-card .cc-brand { font-size: 0.78rem; font-weight: 700; letter-spacing: 0.04em; }
    .cost-card .cc-label { font-size: 0.7rem; color: rgba(255,255,255,0.75); margin-top: 14px; }
    .cost-card .cc-value { font-size: 1.75rem; margin: 2px 0 16px; }
    .cost-card .cc-value .cents { color: rgba(255,255,255,0.6); }
    .cost-card .cc-foot { display: flex; justify-content: space-between; font-size: 0.7rem; color: rgba(255,255,255,0.8); }

    /* ---------- Sparkline ---------- */
    .spark-wrap { position: relative; margin: 4px 0 14px; }
    .spark-wrap svg { display: block; width: 100%; height: 78px; }

    /* ---------- Baris kategori / top list ---------- */
    .q-row {
        display: flex; align-items: center; gap: 11px;
        padding: 10px 0; border-bottom: 1px solid var(--line);
    }
    .q-row:last-child { border-bottom: none; }
    .q-row-name { font-size: 0.82rem; font-weight: 600; }
    .q-row-sub { font-size: 0.7rem; color: var(--muted); }
    .q-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); flex-shrink: 0; }

    .q-track { height: 6px; background: var(--surface); border-radius: 999px; overflow: hidden; }
    .q-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--green), #4bd07f); }

    .avatar-stack { display: flex; align-items: center; }
    .avatar-stack .av {
        width: 42px; height: 42px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; font-weight: 700; color: #fff;
        border: 3px solid var(--card); margin-left: -12px;
    }
    .avatar-stack .av:first-child { margin-left: 0; }
    .avatar-stack .more { background: var(--green); }

    /* ---------- Tabel ---------- */
    .q-table { width: 100%; font-size: 0.82rem; border-collapse: separate; border-spacing: 0; }
    .q-table th {
        font-size: 0.7rem; font-weight: 600; color: var(--muted);
        text-align: left; padding: 0 14px 12px; white-space: nowrap;
    }
    .q-table td { padding: 12px 14px; border-top: 1px solid var(--line); }
    .q-table tbody tr:hover td { background: var(--surface); }
    .q-table tbody tr td:first-child { border-radius: 12px 0 0 12px; }
    .q-table tbody tr td:last-child { border-radius: 0 12px 12px 0; }
    .q-table tfoot td { padding: 10px 14px; border-top: 1px solid var(--line); }
@endpush

@section('content')
@php
    // ---- Data harian, mengikuti rentang yang dipilih ----
    $dailyRaw   = $cw['daily'] ?? [];
    $rangeStart = \Carbon\Carbon::parse($startDate);
    $rangeEnd   = \Carbon\Carbon::parse($endDate);

    $allDays = collect();
    for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
        $key = $d->format('Y-m-d');
        $allDays[$key] = $dailyRaw[$key] ?? 0;
    }
    $rangeCount = $allDays->count();
    $days7      = $allDays->slice(-7);

    // Label rentang: sebut "bulan ini" hanya kalau memang persis bulan berjalan.
    $isCurrentMonth = $startDate === now()->startOfMonth()->format('Y-m-d')
        && $endDate === now()->format('Y-m-d');
    $rangeLabel = $isCurrentMonth
        ? __('dash.this_month')
        : $rangeStart->translatedFormat('d M') . ' – ' . $rangeEnd->translatedFormat('d M Y');

    // ---- Tren: paruh akhir vs paruh awal rentang (selalu terdefinisi, data asli) ----
    $half     = intdiv($rangeCount, 2);
    $sumLate  = $half > 0 ? $allDays->slice(-$half)->sum() : 0;
    $sumEarly = $half > 0 ? $allDays->slice(0, $half)->sum() : 0;
    $deltaPct = $sumEarly > 0 ? (($sumLate - $sumEarly) / $sumEarly) * 100 : null;

    // ---- Panel grafik: 7 hari terakhir + seluruh rentang (kalau memang lebih panjang) ----
    $panes = $rangeCount > 7
        ? [
            ['key' => '7',   'label' => __('dash.chart_days', ['count' => 7]), 'data' => $days7,  'dense' => false],
            ['key' => 'all', 'label' => __('dash.chart_days', ['count' => $rangeCount]), 'data' => $allDays, 'dense' => $rangeCount > 14],
          ]
        : [
            ['key' => 'all', 'label' => __('dash.chart_days', ['count' => $rangeCount]), 'data' => $allDays, 'dense' => false],
          ];

    // ---- Helper format ----
    $short = function ($n) {
        if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'jt';
        if ($n >= 1000)    return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        return (string) round($n);
    };
    // Inisial dua huruf: "Transjakarta" → TR, "key-kai" → KK.
    $initialsOf = function ($text) {
        $parts = preg_split('/[\s\-_]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        return mb_strtoupper(mb_substr($text, 0, 2));
    };
    $money = function ($v) {
        $p = explode('.', number_format($v, 2, '.', ','));
        return ['int' => $p[0], 'cents' => $p[1]];
    };
    $grand = $money($grandCost);
    $sub   = $money($totalCost);

    // ---- Sparkline 30 hari ----
    $sparkVals = $allDays->values()->all();
    $sparkMax  = max(max($sparkVals) ?: 0, 1);
    $pts = [];
    foreach ($sparkVals as $i => $v) {
        $x = count($sparkVals) > 1 ? ($i / (count($sparkVals) - 1)) * 100 : 0;
        $y = 40 - (($v / $sparkMax) * 32) - 4;
        $pts[] = round($x, 2) . ',' . round($y, 2);
    }
    $sparkLine = 'M ' . implode(' L ', $pts);
    $sparkArea = $sparkLine . ' L 100,40 L 0,40 Z';

    // ---- Budget ----
    $rawPct      = $budgetAlert > 0 ? ($grandCost / $budgetAlert) * 100 : 0;
    $budgetColor = $grandCost >= $budgetAlert ? '#dc2626' : ($rawPct >= 80 ? '#f59e0b' : '#00B14F');

    // ---- Top perusahaan / API key ----
    $topKeys = collect($cw['by_key'] ?? [])->take(5);
    $avatarColors = ['#00B14F', '#6366f1', '#f59e0b', '#0ea5e9', '#ec4899'];

    $catData = [
        ['key' => 'maps',   'label' => __('dash.cat_maps'),            'icon' => 'bi-map',             'color' => '#00B14F'],
        ['key' => 'places', 'label' => __('dash.cat_places'), 'icon' => 'bi-search',          'color' => '#6366f1'],
        ['key' => 'routes', 'label' => __('dash.cat_routes'),          'icon' => 'bi-sign-turn-right', 'color' => '#f59e0b'],
    ];

    $firstName = trim(explode(' ', auth()->user()->name ?? '')[0] ?? '');
@endphp

{{-- ===== Page head ===== --}}
<div class="q-page-head">
    <h1 class="q-title">
        {{ __('dash.welcome') }} <span class="soft">{{ $firstName }}</span>
    </h1>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @include('admin.partials.date-range')

        <a href="{{ route('admin.dashboard', ['start' => $startDate, 'end' => $endDate, 'refresh' => 1]) }}"
           class="q-pill q-pill-green" data-spin title="{{ __('dash.refresh_hint') }}">
            <i class="bi bi-arrow-clockwise"></i> {{ __('dash.refresh') }}
        </a>
    </div>
</div>

{{-- Alert di bawah ini adalah KEADAAN, bukan pesan sekali-tayang: selama kondisinya
     masih berlaku ia harus tetap terbaca, jadi sengaja tidak dijadikan toast. --}}
@if(!empty($cw['error']))
    <div class="q-alert bad">
        <span class="q-alert-icon"><i class="bi bi-x-lg"></i></span>
        <div class="q-alert-body">{{ __('dash.aws_error', ['error' => $cw['error']]) }}</div>
        <a href="{{ route('admin.aws-accounts.index') }}" class="q-alert-action">{{ __('dash.check_account') }}</a>
    </div>
@endif

@if($budgetAlert > 0 && $grandCost >= $budgetAlert)
    <div class="q-alert bad">
        <span class="q-alert-icon"><i class="bi bi-exclamation-octagon-fill"></i></span>
        <div class="q-alert-body">
            <strong>{{ __('dash.budget_over') }}</strong>
            {{ __('dash.budget_body', [
                'range'     => $rangeLabel,
                'amount'    => '$' . number_format($grandCost, 2),
                'idr'       => number_format($grandCost * $idrRate, 0, ',', '.'),
                'pct'       => number_format($rawPct, 0),
                'threshold' => '$' . number_format($budgetAlert, 0),
            ]) }}
        </div>
        <a href="{{ route('admin.cost-settings.index') }}" class="q-alert-action">{{ __('dash.budget_edit') }}</a>
    </div>
@elseif($budgetAlert > 0 && $rawPct >= 80)
    <div class="q-alert warn">
        <span class="q-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="q-alert-body">
            <strong>{{ __('dash.budget_near') }}</strong>
            {{ __('dash.budget_body', [
                'range'     => $rangeLabel,
                'amount'    => '$' . number_format($grandCost, 2),
                'idr'       => number_format($grandCost * $idrRate, 0, ',', '.'),
                'pct'       => number_format($rawPct, 0),
                'threshold' => '$' . number_format($budgetAlert, 0),
            ]) }}
        </div>
        <a href="{{ route('admin.cost-settings.index') }}" class="q-alert-action">{{ __('dash.budget_edit') }}</a>
    </div>
@endif

{{-- Batas per API key — terpisah dari ambang global, jadi bisa muncul bersamaan. --}}
@foreach($keyBudgets as $kb)
    <div class="q-alert {{ $kb['state'] === 'over' ? 'bad' : 'warn' }}">
        <span class="q-alert-icon">
            <i class="bi bi-{{ $kb['state'] === 'over' ? 'exclamation-octagon-fill' : 'exclamation-triangle-fill' }}"></i>
        </span>
        <div class="q-alert-body">
            <strong>
                {{ $kb['state'] === 'over'
                    ? __('dash.key_budget_over', ['key' => $kb['key_name']])
                    : __('dash.key_budget_near', ['key' => $kb['key_name']]) }}
            </strong>
            {{ __('dash.key_budget_body', [
                'amount'    => '$' . number_format($kb['cost'], 2),
                'threshold' => '$' . number_format($kb['limit'], 2),
                'pct'       => number_format($kb['ratio'] * 100, 0),
                'range'     => $rangeLabel,
            ]) }}
        </div>
        @can('api_keys.view')
            <a href="{{ route('admin.api-keys.usage', array_filter([
                    'keyName' => $kb['key_name'],
                    'account' => $kb['budget']->aws_account_id,
                    'start'   => $startDate,
                    'end'     => $endDate,
               ])) }}" class="q-alert-action">{{ __('dash.key_budget_open') }}</a>
        @endcan
    </div>
@endforeach

<div class="dash-grid">
  <div class="dash-left">
    <div class="dash-left-top">
    {{-- ===================== KOLOM KIRI ===================== --}}
    <div class="dash-col">
        {{-- Kartu biaya --}}
        <div class="q-card">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('dash.cost_title', ['range' => $rangeLabel]) }}</div>
                    <div class="q-card-sub">{{ __('dash.cost_sub', ['pct' => round($taxRate * 100, 2)]) }}</div>
                </div>
                <a href="{{ route('admin.cost-settings.index') }}" class="q-ghost-btn" title="Kurs & pajak">
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div class="cost-card">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="cc-brand">{{ __('dash.cost_brand') }}</span>
                    <i class="bi bi-broadcast" style="opacity:0.75;"></i>
                </div>
                <div class="cc-label">{{ __('dash.cost_total') }}</div>
                <div class="cc-value q-num">${{ $grand['int'] }}<span class="cents">.{{ $grand['cents'] }}</span></div>
                <div class="cc-foot">
                    <span>≈ Rp {{ number_format($grandCost * $idrRate, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format($idrRate, 0, ',', '.') }}/USD</span>
                </div>
            </div>
        </div>

        {{-- Request pada rentang terpilih --}}
        <div class="q-card">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="q-card-sub">{{ __('dash.requests_title', ['range' => $rangeLabel]) }}</div>
                @if($deltaPct !== null)
                    <span class="q-delta {{ $deltaPct < 0 ? 'down' : '' }}">
                        <i class="bi bi-arrow-{{ $deltaPct < 0 ? 'down' : 'up' }}-right"></i>
                        {{ number_format(abs($deltaPct), 1) }}%
                    </span>
                @else
                    <span class="q-delta flat">{{ __('dash.delta_new') }}</span>
                @endif
            </div>
            <div class="q-num" style="font-size:1.6rem;">{{ number_format($totalRequests) }}</div>
            <div class="q-card-sub mt-1">
                @if($deltaPct !== null)
                    {{ __('dash.delta_halves', ['late' => number_format($sumLate), 'early' => number_format($sumEarly)]) }}
                @else
                    {{ __('dash.delta_none') }}
                @endif
            </div>
        </div>

        {{-- Perusahaan & API key --}}
        <div class="q-card">
            <div class="q-row" style="padding-top:0;">
                <div class="q-icon-box tone-green">
                    <i class="bi bi-building"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="q-row-name">{{ $activeCompanies }} <span class="fw-normal text-muted">/ {{ $totalCompanies }}</span></div>
                    <div class="q-row-sub">{{ __('admin.active_companies') }}</div>
                </div>
                <a href="{{ route('admin.companies.index') }}" class="q-ghost-btn"><i class="bi bi-arrow-up-right"></i></a>
            </div>
            <div class="q-row" style="padding-bottom:0;">
                <div class="q-icon-box tone-indigo">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="q-row-name">{{ $apiKeysData['active'] }} <span class="fw-normal text-muted">/ {{ $apiKeysData['total'] }}</span></div>
                    <div class="q-row-sub">{{ __('admin.active_api_keys') }}</div>
                </div>
                <a href="{{ route('admin.api-keys.index') }}" class="q-ghost-btn"><i class="bi bi-arrow-up-right"></i></a>
            </div>
        </div>
    </div>

    {{-- ===================== KOLOM TENGAH ===================== --}}
    <div class="dash-col">
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-bar-chart-line"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('dash.chart_title') }}</div>
                        <div class="q-card-sub">{{ __('dash.chart_sub', ['range' => $rangeLabel]) }}</div>
                    </div>
                </div>
                @if(count($panes) > 1)
                <div class="d-flex align-items-center gap-2">
                    <div class="q-toggle" id="chartToggle">
                        @foreach($panes as $p)
                            <button type="button" class="{{ $loop->first ? 'active' : '' }}"
                                    data-range="{{ $p['key'] }}">{{ $p['label'] }}</button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            @if($allDays->sum() === 0)
                <div class="q-empty">
                    <i class="bi bi-bar-chart"></i>
                    {{ __('dash.no_data') }}
                </div>
            @else
                @foreach($panes as $chart)
                    @php $data = $chart['data']; @endphp
                    @include('admin.partials.bar-chart', [
                        'series' => $data,
                        'pane'   => $chart['key'],
                        'dense'  => $chart['dense'],
                        'hidden' => !$loop->first,
                    ])
                @endforeach
            @endif
        </div>

    </div>

    </div>{{-- /dash-left-top --}}

    {{-- ===================== TABEL OPERASI (melebar penuh di wilayah kiri) ===================== --}}
    @if(!empty($operations))
        <div class="q-card">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('dash.ops_title') }}</div>
                    <div class="q-card-sub">{{ __('dash.ops_sub', ['pct' => round($taxRate * 100, 2)]) }}</div>
                </div>
                <a href="{{ route('admin.cost-settings.index') }}" class="q-ghost-btn" title="{{ __('ui.cost_settings') }}">
                    <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            @php $opMax = max(array_values($operations)) ?: 1; @endphp
            <div class="table-responsive">
                <table class="q-table">
                    <thead>
                        <tr>
                            <th style="width:210px;">{{ __('dash.ops_op') }}</th>
                            <th>{{ __('admin.usage') }}</th>
                            <th class="text-end">{{ __('admin.requests') }}</th>
                            <th class="text-end">$/1k</th>
                            <th class="text-end">{{ __('admin.est_cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($operations as $op => $count)
                            @php
                                $rate = \App\Services\AwsLocationService::PRICING[$op] ?? 0;
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
                                <td class="text-end" style="color:var(--muted);">${{ number_format($rate, 2) }}</td>
                                <td class="text-end fw-semibold">${{ number_format($cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="color:var(--muted);">{{ __('dash.subtotal') }}</td>
                            <td class="text-end fw-semibold">{{ number_format($totalRequests) }}</td>
                            <td></td>
                            <td class="text-end fw-semibold">${{ $sub['int'] }}.{{ $sub['cents'] }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" style="color:var(--muted);">{{ __('dash.vat', ['pct' => round($taxRate * 100, 2)]) }}</td>
                            <td class="text-end" style="color:var(--muted);">${{ number_format($tax, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="fw-bold">{{ __('dash.total_vat') }}</td>
                            <td class="text-end">
                                <div class="q-num" style="font-size:1.05rem;color:var(--green-text);">
                                    ${{ $grand['int'] }}<span class="cents">.{{ $grand['cents'] }}</span>
                                </div>
                                <div style="font-size:0.7rem;color:var(--muted);">
                                    ≈ Rp {{ number_format($grandCost * $idrRate, 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="q-card-sub mt-2">
                <i class="bi bi-info-circle me-1"></i>{{ __('dash.ops_note') }}
                @if($fetchedAt)
                    {{ __('dash.fetched_at', ['time' => $fetchedAt->timezone('Asia/Jakarta')->format('d M Y H:i')]) }}
                @else
                    {{ __('dash.no_snapshot') }}
                @endif
            </div>
        </div>
    @endif
  </div>{{-- /dash-left --}}

    {{-- ===================== SIDEBAR KANAN ===================== --}}
    <div class="dash-col">
        {{-- Status budget --}}
        <div class="q-card">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('dash.budget_title') }}</div>
                    <div class="q-card-sub">{{ __('dash.budget_threshold', ['amount' => '$' . number_format($budgetAlert, 0)]) }}</div>
                </div>
                <a href="{{ route('admin.cost-settings.index') }}" class="q-ghost-btn"><i class="bi bi-arrow-up-right"></i></a>
            </div>

            <div class="text-center">
                <div class="q-card-sub">{{ __('dash.budget_used', ['range' => $rangeLabel]) }}</div>
                <div class="q-num" style="font-size:1.9rem;color:{{ $budgetColor }};">
                    ${{ $grand['int'] }}<span class="cents">.{{ $grand['cents'] }}</span>
                </div>
            </div>

            <div class="spark-wrap">
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="sparkFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#00B14F" stop-opacity="0.28" />
                            <stop offset="100%" stop-color="#00B14F" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <path d="{{ $sparkArea }}" fill="url(#sparkFill)" />
                    <path d="{{ $sparkLine }}" fill="none" stroke="#00B14F" stroke-width="1.6"
                          vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
                </svg>
            </div>

            <div class="q-track mb-2">
                <div class="q-fill" style="width: {{ min($rawPct, 100) }}%; background: {{ $budgetColor }};"></div>
            </div>
            <div class="d-flex justify-content-between" style="font-size:0.7rem;color:var(--muted);">
                <span>$0</span>
                <span style="font-weight:700;color:{{ $budgetColor }};">{{ number_format($rawPct, 0) }}%</span>
                <span>${{ number_format($budgetAlert, 0) }}</span>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('admin.cost-settings.index') }}" class="q-pill q-pill-green flex-grow-1 justify-content-center"
                   style="padding:9px 14px;font-size:0.78rem;">
                    {{ __('ui.cost_settings') }} <i class="bi bi-arrow-up-right"></i>
                </a>
                <a href="{{ route('admin.api-keys.index') }}" class="q-pill flex-grow-1 justify-content-center"
                   style="padding:9px 14px;font-size:0.78rem;background:var(--surface);box-shadow:none;">
                    API Keys <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        {{-- Biaya per kategori --}}
        @if($totalRequests > 0)
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('dash.cat_title') }}</div>
                        <div class="q-card-sub">{{ __('dash.cat_sub') }}</div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-baseline gap-2 mb-2">
                <div class="q-num" style="font-size:1.7rem;">${{ $sub['int'] }}<span class="cents">.{{ $sub['cents'] }}</span></div>
                <span class="q-delta">{{ number_format($totalCost > 0 ? ($catCost['places'] / $totalCost) * 100 : 0, 1) }}% Places</span>
            </div>

            @foreach($catData as $cat)
                @php $share = $totalCost > 0 ? ($catCost[$cat['key']] / $totalCost) * 100 : 0; @endphp
                <div class="q-row">
                    <div class="q-icon-box" style="width:32px;height:32px;border-radius:10px;color:{{ $cat['color'] }};">
                        <i class="bi {{ $cat['icon'] }}"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="q-row-name">{{ $cat['label'] }}</div>
                        <div class="q-row-sub">{{ number_format($catCount[$cat['key']]) }} {{ __('dash.requests_word') }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold" style="font-size:0.85rem;">${{ number_format($catCost[$cat['key']], 2) }}</div>
                        <div class="q-row-sub">{{ number_format($share, 1) }}%</div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- Top pemakai --}}
        <div class="q-card">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('dash.top_title') }}</div>
                    <div class="q-card-sub">{{ __('dash.top_sub', ['range' => $rangeLabel]) }}</div>
                </div>
                <a href="{{ route('admin.companies.index') }}" class="q-ghost-btn"><i class="bi bi-arrow-up-right"></i></a>
            </div>

            @if($topKeys->isEmpty())
                <div class="q-empty"><i class="bi bi-inbox"></i>{{ __('dash.no_data_short') }}</div>
            @else
                <div class="avatar-stack mb-3">
                    @foreach($topKeys as $keyName => $count)
                        @php
                            $comp = $companyByKey[$keyName] ?? null;
                            $label = $comp->name ?? $keyName;
                        @endphp
                        @if($loop->index < 4)
                            <div class="av" style="background: {{ $avatarColors[$loop->index] }};" title="{{ $label }}">
                                {{ $initialsOf($label) }}
                            </div>
                        @endif
                    @endforeach
                    @if($topKeys->count() > 4)
                        <div class="av more">+{{ $topKeys->count() - 4 }}</div>
                    @endif
                </div>

                @foreach($topKeys as $keyName => $count)
                    @php
                        $comp  = $companyByKey[$keyName] ?? null;
                        $share = $totalRequests > 0 ? ($count / $totalRequests) * 100 : 0;
                    @endphp
                    <div class="q-row">
                        <span class="q-dot" style="background: {{ $avatarColors[$loop->index % count($avatarColors)] }};"></span>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="q-row-name text-truncate">{{ $comp->name ?? $keyName }}</div>
                            <div class="q-row-sub text-truncate"><i class="bi bi-key"></i> {{ $keyName }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold" style="font-size:0.85rem;">{{ $short($count) }}</div>
                            <div class="q-row-sub">{{ number_format($share, 1) }}%</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Pemakaian per akun AWS --}}
        @if(count($byAccount) > 1)
        <div class="q-card">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('dash.by_account') }}</div>
                    <div class="q-card-sub">{{ __('dash.accounts_active', ['count' => count($byAccount)]) }}</div>
                </div>
                <a href="{{ route('admin.aws-accounts.index') }}" class="q-ghost-btn"><i class="bi bi-arrow-up-right"></i></a>
            </div>
            @foreach($byAccount as $accountName => $count)
                @php $share = $totalRequests > 0 ? ($count / $totalRequests) * 100 : 0; @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="q-row-name"><i class="bi bi-cloud-fill me-1" style="color:var(--green);"></i>{{ $accountName }}</span>
                        <span class="q-row-sub">{{ $short($count) }} · {{ number_format($share, 1) }}%</span>
                    </div>
                    <div class="q-track"><div class="q-fill" style="width: {{ $share }}%;"></div></div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('chartToggle')?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-range]');
        if (!btn) return;
        document.querySelectorAll('#chartToggle button').forEach(b => b.classList.toggle('active', b === btn));
        document.querySelectorAll('.chart-pane').forEach(p => {
            p.hidden = p.dataset.pane !== btn.dataset.range;
        });
    });
</script>
@endpush
