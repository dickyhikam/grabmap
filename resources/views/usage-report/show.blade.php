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

@section('content')
@php
    use App\Services\AwsLocationService;

    $reportTitle = $assignedCompany?->name ?? __('apikeys.share_report_title');

    $rangeStart = \Carbon\Carbon::parse($startDate);
    $rangeEnd   = \Carbon\Carbon::parse($endDate);
    $rangeLabel = $rangeStart->translatedFormat('d M') . ' – ' . $rangeEnd->translatedFormat('d M Y');

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
    $grandParts = $money($grand);

    $categories = [
        'maps'   => ['label' => __('dash.cat_maps'),   'icon' => 'bi-map',             'color' => '#00B14F', 'ops' => ['GetMapTile', 'GetTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites']],
        'places' => ['label' => __('dash.cat_places'), 'icon' => 'bi-search',          'color' => '#6366f1', 'ops' => ['SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace']],
        'routes' => ['label' => __('dash.cat_routes'), 'icon' => 'bi-sign-turn-right', 'color' => '#f59e0b', 'ops' => ['CalculateRoutes', 'CalculateRouteMatrix']],
    ];
@endphp

<div class="q-page-head">
    <div>
        <h1 class="q-title">{{ __('apikeys.share_report_title') }}</h1>
        <p style="margin:4px 0 0;color:var(--muted);font-size:0.82rem;">{{ $reportTitle }} · {{ $rangeLabel }}</p>
    </div>
</div>

@if(!$fetchedAt)
    <div class="q-alert">
        <span class="q-alert-icon"><i class="bi bi-info-circle-fill"></i></span>
        <div class="q-alert-body">{{ __('apikeys.share_no_data') }}</div>
    </div>
@endif

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

    <div class="q-card stat-tile">
        <div class="ic tone-amber"><i class="bi bi-wallet2"></i></div>
        <div>
            <div class="q-num val">${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span></div>
            <div class="lbl">{{ __('apikeys.est_cost') }}</div>
            <div class="sub">
                {{ __('apikeys.incl_tax', ['pct' => round($taxRate * 100, 2)]) }} ·
                ≈ Rp {{ number_format($grand * $idrRate, 0, ',', '.') }}
            </div>
        </div>
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
                                    <td class="text-end fw-semibold">${{ number_format($cost, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="color:var(--muted);">{{ __('apikeys.subtotal') }}</td>
                                <td class="text-end fw-semibold">{{ number_format(array_sum($ops)) }}</td>
                                <td class="text-end fw-semibold">${{ number_format($totalCost, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="color:var(--muted);">{{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }}</td>
                                <td class="text-end" style="color:var(--muted);">${{ number_format($tax, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="fw-bold">{{ __('apikeys.total_vat') }}</td>
                                <td class="text-end">
                                    <div class="q-num" style="font-size:1.05rem;color:var(--green-text);">
                                        ${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="usage-col">
        @if($fetchedAt)
            <div class="q-card">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('apikeys.share_data_freshness') }}</div>
                            <div class="q-card-sub">
                                @php $stale = $fetchedAt->lt(now()->subDay()); @endphp
                                <span @if($stale) style="color:var(--warn-fg);" @endif>
                                    {{ __('apikeys.fetched', ['time' => $fetchedAt->timezone('Asia/Jakarta')->format('d M H:i')]) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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
                            <div style="font-size:0.85rem;font-weight:600;">${{ number_format($catCost, 2) }}</div>
                            <div style="font-size:0.7rem;color:var(--muted);">{{ number_format($sharePct, 1) }}%</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@section('footer-note')
    {{ __('apikeys.share_disclaimer') }}
    @if($share->share_expires_at)
        · {{ __('apikeys.share_expires', ['date' => $share->share_expires_at->translatedFormat('d M Y')]) }}
    @endif
@endsection
