@extends('layouts.admin-v2')

@section('title', __('apikeys.usage_title') . ' — ' . $keyName)

@push('styles')
    /* Token warna grafik — CSS batangnya sendiri ada di partial bar-chart. */
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

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    .stat-row {
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px; margin-bottom: 16px;
    }
    @media (max-width: 1100px) { .stat-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px)  { .stat-row { grid-template-columns: 1fr; } }

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
    .tone-bad    { background: var(--danger-soft); color: var(--danger-fg); }

    /* Batas biaya per key */
    .bg-nums { display: flex; align-items: baseline; gap: 7px; margin-bottom: 10px; }
    .bg-nums .now {
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
        font-size: 1.5rem; letter-spacing: -0.03em; color: var(--green-text);
    }
    .bg-nums .now.near { color: var(--warn-fg); }
    .bg-nums .now.over { color: var(--danger-fg); }
    .bg-nums .max { font-size: 0.82rem; color: var(--muted); font-weight: 600; }

    .bg-bar { height: 9px; border-radius: 999px; background: var(--surface); overflow: hidden; }
    .bg-bar .fill {
        display: block; height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, var(--green), #4bd07f);
        transition: width 0.5s cubic-bezier(0.34, 1.2, 0.6, 1);
    }
    .bg-bar .fill.near { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .bg-bar .fill.over { background: linear-gradient(90deg, #dc2626, #f87171); }

    .bg-foot { display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--muted); margin-top: 8px; }
    .bg-foot .over { color: var(--danger-fg); font-weight: 700; }

    .usage-grid {
        display: grid; grid-template-columns: minmax(0, 2.6fr) minmax(0, 1.15fr);
        gap: 16px; align-items: start;
    }
    .usage-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    @media (max-width: 1200px) { .usage-grid { grid-template-columns: 1fr; } }

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

    .kv { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 0.8rem; }
    .kv:last-child { border-bottom: none; }
    .kv .k { color: var(--muted); }
    .kv .v { font-weight: 600; text-align: right; word-break: break-all; }

    .cat-row { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--line); }
    .cat-row:last-child { border-bottom: none; }
    .cat-ic {
        width: 32px; height: 32px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;
        background: var(--surface);
    }

    .gm-modal {
        position: fixed; inset: 0; z-index: 1250;
        display: flex; align-items: flex-start; justify-content: center;
        padding: 10vh 16px 16px;
        background: rgba(12, 18, 15, 0.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }
    .gm-modal.open { opacity: 1; visibility: visible; }
    .gm-modal-card {
        width: 100%; max-width: 430px;
        background: var(--card); border-radius: 24px;
        box-shadow: var(--shadow-pop); overflow: visible;
        transform: translateY(-14px) scale(0.97); opacity: 0;
        transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.5, 1), opacity 0.2s ease;
    }
    .gm-modal.open .gm-modal-card { transform: none; opacity: 1; }
    .gm-modal-head { text-align: center; padding: 26px 24px 18px; }
    .gm-modal-icon {
        width: 52px; height: 52px; border-radius: 16px; margin: 0 auto 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        background: var(--surface); color: var(--muted);
    }
    .gm-modal-icon.tone-green { background: var(--green-soft); color: var(--green-text); }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-sub { font-size: 0.77rem; color: var(--muted); margin-top: 5px; }
    .gm-modal-body { padding: 0 24px 24px; }

    .share-link-wrap { display: flex; gap: 8px; margin-bottom: 12px; }
    .share-link-input {
        flex: 1; min-width: 0; border: none; background: var(--surface);
        border-radius: 14px; padding: 11px 14px; font-size: 0.72rem; color: var(--ink);
    }
    .share-meta {
        display: flex; flex-direction: column; gap: 6px;
        font-size: 0.72rem; color: var(--muted); margin-bottom: 14px;
    }
    .share-meta i { margin-right: 4px; }
    .share-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .share-actions form { flex: 1; min-width: 140px; }
    .share-actions button { width: 100%; }
    .share-label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 6px; }

    .note {
        display: flex; align-items: flex-start; gap: 8px;
        padding: 10px 12px; border-radius: 12px; font-size: 0.74rem; line-height: 1.4;
    }
    .note.info { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
@endpush

@section('content')
@php
    use App\Services\AwsLocationService;

    $rangeStart = \Carbon\Carbon::parse($startDate);
    $rangeEnd   = \Carbon\Carbon::parse($endDate);
    $rangeLabel = $rangeStart->translatedFormat('d M') . ' – ' . $rangeEnd->translatedFormat('d M Y');

    // Deret harian selalu penuh sepanjang rentang, supaya hari kosong tetap terlihat.
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

    $expired = $keyInfo && $keyInfo['expire_time'] && \Carbon\Carbon::parse($keyInfo['expire_time'])->isPast();

    // Batas biaya per key dibandingkan dengan biaya AWS (sebelum PPN), sama seperti
    // AWS Budgets — PPN itu pajak lokal, tidak ikut ditagih AWS.
    $budgetLimit = $budget ? (float) $budget->limit_usd : 0.0;
    $budgetRatio = $budgetLimit > 0 ? $totalCost / $budgetLimit : 0.0;
    $budgetState = $budgetLimit <= 0 ? null
        : ($budgetRatio >= 1 ? 'over' : ($budgetRatio >= \App\Models\ApiKeyBudget::NEAR_RATIO ? 'near' : 'ok'));
@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.api-keys.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('apikeys.back') }}
        </a>
        <h1 class="q-title">{{ __('apikeys.usage_title') }} <span class="soft">{{ $keyName }}</span></h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        @include('admin.partials.date-range')

        <a href="{{ route('admin.api-keys.usage', array_filter(['keyName' => $keyName, 'start' => $startDate, 'end' => $endDate, 'operation' => $filterOperation, 'account' => $account?->id, 'refresh' => 1])) }}"
           class="q-pill q-pill-green" data-spin title="{{ __('dash.refresh_hint') }}">
            <i class="bi bi-arrow-clockwise"></i> {{ __('apikeys.refresh') }}
        </a>

        <a href="{{ route('admin.api-keys.invoice', ['keyName' => $keyName, 'start' => $startDate, 'end' => $endDate, 'account' => $account?->id]) }}"
           target="_blank" class="q-pill" data-no-loader>
            <i class="bi bi-file-earmark-pdf"></i> {{ __('apikeys.invoice') }}
        </a>

        @can('api_keys.update')
            <button type="button" class="q-pill" id="shareOpenBtn">
                <i class="bi bi-share"></i> {{ __('apikeys.share') }}
            </button>
        @endcan
    </div>
</div>

@if($budgetState === 'over' || $budgetState === 'near')
    <div class="q-alert {{ $budgetState === 'over' ? 'bad' : 'warn' }}">
        <span class="q-alert-icon"><i class="bi bi-{{ $budgetState === 'over' ? 'exclamation-octagon-fill' : 'exclamation-triangle-fill' }}"></i></span>
        <div class="q-alert-body">
            <strong>{{ $budgetState === 'over' ? __('apikeys.budget_over_t') : __('apikeys.budget_near_t') }}</strong><br>
            {{ __('apikeys.budget_used', [
                'used'  => '$' . number_format($totalCost, 2),
                'limit' => '$' . number_format($budgetLimit, 2),
            ]) }}
            ({{ number_format($budgetRatio * 100, 0) }}%) · {{ $rangeLabel }}
        </div>
        @can('api_keys.update')
            <a href="{{ route('admin.api-keys.edit', ['keyName' => $keyName, 'account' => $account?->id]) }}" class="q-alert-action">
                {{ __('apikeys.edit') }}
            </a>
        @endcan
    </div>
@endif

@if($keyError)
    <div class="q-alert warn">
        <span class="q-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="q-alert-body">{{ $keyError }}</div>
    </div>
@endif

@if($metrics['error'])
    <div class="q-alert bad">
        <span class="q-alert-icon"><i class="bi bi-x-lg"></i></span>
        <div class="q-alert-body">
            <strong>{{ __('apikeys.metrics_failed') }}</strong><br>{{ $metrics['error'] }}
        </div>
    </div>
@endif

{{-- ===== Ringkasan ===== --}}
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

    <div class="q-card stat-tile">
        <div class="ic {{ $expired ? 'tone-bad' : 'tone-green' }}">
            <i class="bi bi-{{ $expired ? 'x-circle-fill' : 'check-circle-fill' }}"></i>
        </div>
        <div>
            <div class="q-num val" style="font-size:1.05rem;">
                {{ $expired ? __('apikeys.expired') : __('apikeys.active') }}
            </div>
            <div class="lbl">{{ __('apikeys.status') }}</div>
            <div class="sub">
                @if($fetchedAt)
                    @php $stale = $fetchedAt->lt(now()->subDay()); @endphp
                    <span @if($stale) style="color:var(--warn-fg);" @endif>
                        {{ __('apikeys.fetched', ['time' => $fetchedAt->timezone('Asia/Jakarta')->format('d M H:i')]) }}{{ $stale ? ' — ' . __('apikeys.stale') : '' }}
                    </span>
                @else
                    {{ __('apikeys.no_snapshot') }}
                @endif
            </div>
        </div>
    </div>
</div>

<div class="usage-grid">
    {{-- ===================== Kiri ===================== --}}
    <div class="usage-col">
        {{-- Grafik harian --}}
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-bar-chart-line"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('apikeys.daily_chart') }}</div>
                        <div class="q-card-sub">{{ __('apikeys.daily_chart_sub', ['range' => $rangeLabel]) }}</div>
                    </div>
                </div>

                {{-- Filter operasi: langsung diterapkan saat dipilih. --}}
                <form method="GET" action="{{ route('admin.api-keys.usage', $keyName) }}" id="opForm">
                    <input type="hidden" name="account" value="{{ $account?->id }}">
                    <input type="hidden" name="start" value="{{ $startDate }}">
                    <input type="hidden" name="end" value="{{ $endDate }}">
                    <select name="operation" class="select" style="width:200px;padding-left:16px;" data-auto>
                        <option value="">{{ __('apikeys.all_operations') }}</option>
                        @foreach($operations as $op)
                            <option value="{{ $op }}" @selected($filterOperation === $op)>{{ $op }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($daily->sum() === 0)
                <div class="q-empty"><i class="bi bi-bar-chart"></i>{{ __('apikeys.no_usage') }}</div>
            @else
                @include('admin.partials.bar-chart', ['series' => $daily])
            @endif
        </div>

        {{-- Rincian per operasi --}}
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
                                <th class="text-end">$/1k</th>
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
                                    <td class="text-end" style="color:var(--muted);">${{ number_format($rate, 2) }}</td>
                                    <td class="text-end fw-semibold">${{ number_format($cost, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="color:var(--muted);">{{ __('apikeys.subtotal') }}</td>
                                <td class="text-end fw-semibold">{{ number_format(array_sum($ops)) }}</td>
                                <td></td>
                                <td class="text-end fw-semibold">${{ number_format($totalCost, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="color:var(--muted);">{{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }}</td>
                                <td class="text-end" style="color:var(--muted);">${{ number_format($tax, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="fw-bold">{{ __('apikeys.total_vat') }}</td>
                                <td class="text-end">
                                    <div class="q-num" style="font-size:1.05rem;color:var(--green-text);">
                                        ${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span>
                                    </div>
                                    <div style="font-size:0.7rem;color:var(--muted);">
                                        ≈ Rp {{ number_format($grand * $idrRate, 0, ',', '.') }}
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ===================== Kanan ===================== --}}
    <div class="usage-col">
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('apikeys.key_info') }}</div>
                        <div class="q-card-sub">{{ __('apikeys.key_info_sub') }}</div>
                    </div>
                </div>
                @can('api_keys.update')
                    <a href="{{ route('admin.api-keys.edit', ['keyName' => $keyName, 'account' => $account?->id]) }}"
                       class="q-ghost-btn" title="{{ __('apikeys.edit') }}"><i class="bi bi-pencil"></i></a>
                @endcan
            </div>

            <div class="kv">
                <span class="k">{{ __('apikeys.company') }}</span>
                <span class="v">{{ $assignedCompany->name ?? __('apikeys.none') }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('apikeys.created') }}</span>
                <span class="v">{{ $keyInfo && $keyInfo['create_time'] ? \Carbon\Carbon::parse($keyInfo['create_time'])->translatedFormat('d M Y') : '—' }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('apikeys.expires') }}</span>
                <span class="v" @if($expired) style="color:var(--danger-fg);" @endif>
                    {{ $keyInfo && $keyInfo['expire_time'] ? \Carbon\Carbon::parse($keyInfo['expire_time'])->translatedFormat('d M Y') : __('apikeys.never') }}
                </span>
            </div>
            <div class="kv">
                <span class="k">{{ __('apikeys.account') }}</span>
                <span class="v">{{ $account?->name ?? '.env' }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('apikeys.region') }}</span>
                <span class="v">{{ $account?->region ?? config('aws.region') }}</span>
            </div>
        </div>

        {{-- Batas biaya key ini --}}
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-bell-fill"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('apikeys.budget_card') }}</div>
                        <div class="q-card-sub">{{ __('apikeys.budget_sub') }}</div>
                    </div>
                </div>
            </div>

            @if($budgetLimit > 0)
                <div class="bg-nums">
                    <span class="now {{ $budgetState }}">${{ number_format($totalCost, 2) }}</span>
                    <span class="max">/ ${{ number_format($budgetLimit, 2) }}</span>
                </div>

                <div class="bg-bar">
                    <span class="fill {{ $budgetState }}" style="width: {{ min($budgetRatio * 100, 100) }}%;"></span>
                </div>

                <div class="bg-foot">
                    <span>{{ number_format($budgetRatio * 100, 0) }}%</span>
                    <span @class(['over' => $budgetState === 'over'])>
                        @if($budgetRatio >= 1)
                            {{ __('apikeys.budget_over', ['amount' => '$' . number_format($totalCost - $budgetLimit, 2)]) }}
                        @else
                            {{ __('apikeys.budget_left', ['amount' => '$' . number_format($budgetLimit - $totalCost, 2)]) }}
                        @endif
                    </span>
                </div>
            @else
                <div class="q-empty" style="padding:18px 10px;">
                    <i class="bi bi-bell-slash"></i>{{ __('apikeys.budget_none') }}
                </div>
                @can('api_keys.update')
                    <a href="{{ route('admin.api-keys.edit', ['keyName' => $keyName, 'account' => $account?->id]) }}"
                       class="btn-soft" style="width:100%;">
                        <i class="bi bi-sliders"></i> {{ __('apikeys.budget_set') }}
                    </a>
                @endcan
            @endif
        </div>

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
                        $share    = $totalCost > 0 ? ($catCost / $totalCost) * 100 : 0;
                    @endphp
                    <div class="cat-row">
                        <div class="cat-ic" style="color: {{ $cat['color'] }};"><i class="bi {{ $cat['icon'] }}"></i></div>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div style="font-size:0.82rem;font-weight:600;">{{ $cat['label'] }}</div>
                            <div style="font-size:0.7rem;color:var(--muted);">{{ number_format($catCount) }} {{ __('dash.requests_word') }}</div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:0.85rem;font-weight:600;">${{ number_format($catCost, 2) }}</div>
                            <div style="font-size:0.7rem;color:var(--muted);">{{ number_format($share, 1) }}%</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@can('api_keys.update')
    @php
        $shareActive = $share && $share->isActive();
        $shareUrl = $shareActive
            ? $share->publicUrl(['start' => $startDate, 'end' => $endDate])
            : '';
    @endphp

    <div class="gm-modal" id="shareModal" role="dialog" aria-modal="true">
        <div class="gm-modal-card" style="max-width:520px;">
            <div class="gm-modal-head">
                <div class="gm-modal-icon tone-green"><i class="bi bi-share"></i></div>
                <div class="gm-modal-title">{{ __('apikeys.share_title') }}</div>
                <div class="gm-modal-sub">{{ __('apikeys.share_sub') }}</div>
            </div>
            <div class="gm-modal-body">
                @if($shareActive)
                    <div class="share-link-wrap">
                        <input type="text" class="share-link-input" id="shareLinkInput" readonly value="{{ $shareUrl }}">
                        <button type="button" class="btn-solid" id="shareCopyBtn" data-copied="{{ __('apikeys.share_copied') }}">
                            <i class="bi bi-clipboard"></i> {{ __('apikeys.share_copy') }}
                        </button>
                    </div>

                    <div class="share-meta">
                        @if($share->share_last_accessed_at)
                            <span><i class="bi bi-eye"></i> {{ __('apikeys.share_last_viewed', ['time' => $share->share_last_accessed_at->timezone('Asia/Jakarta')->format('d M Y H:i')]) }}</span>
                        @endif
                        @if($share->share_expires_at)
                            <span><i class="bi bi-hourglass-split"></i> {{ __('apikeys.share_expires', ['date' => $share->share_expires_at->translatedFormat('d M Y')]) }}</span>
                        @endif
                    </div>

                    <div class="share-actions">
                        <form method="POST" action="{{ route('admin.api-keys.share.regenerate', ['keyName' => $keyName, 'account' => $account?->id]) }}">
                            @csrf
                            <button type="submit" class="btn-soft"><i class="bi bi-arrow-repeat"></i> {{ __('apikeys.share_regenerate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.api-keys.share.disable', ['keyName' => $keyName, 'account' => $account?->id]) }}">
                            @csrf
                            <button type="submit" class="btn-solid danger"><i class="bi bi-x-lg"></i> {{ __('apikeys.share_disable') }}</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.api-keys.share.enable', ['keyName' => $keyName, 'account' => $account?->id]) }}">
                        @csrf
                        <label class="share-label" for="expires_days">{{ __('apikeys.share_expiry_lbl') }}</label>
                        <select name="expires_days" id="expires_days" class="select" style="width:100%;margin-bottom:14px;">
                            <option value="">{{ __('apikeys.share_expiry_never') }}</option>
                            <option value="30">{{ __('apikeys.exp_days', ['count' => 30]) }}</option>
                            <option value="90">{{ __('apikeys.exp_days', ['count' => 90]) }}</option>
                            <option value="180">{{ __('apikeys.exp_days', ['count' => 180]) }}</option>
                            <option value="365">{{ __('apikeys.exp_days', ['count' => 365]) }}</option>
                        </select>
                        <div class="note info" style="margin-bottom:14px;">
                            <i class="bi bi-shield-check"></i>
                            <span>{{ __('apikeys.share_security_note') }}</span>
                        </div>
                        <div class="btn-row">
                            <button type="button" class="btn-soft" data-share-close>{{ __('ui.cancel') }}</button>
                            <button type="submit" class="btn-solid"><i class="bi bi-link-45deg"></i> {{ __('apikeys.share_enable') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endcan
@endsection

@push('scripts')
<script>
    document.querySelectorAll('#opForm [data-auto]').forEach((sel) => {
        sel.addEventListener('change', () => document.getElementById('opForm').submit());
    });

    (function () {
        const modal = document.getElementById('shareModal');
        const openBtn = document.getElementById('shareOpenBtn');
        if (!modal || !openBtn) return;

        const close = () => modal.classList.remove('open');

        openBtn.addEventListener('click', () => modal.classList.add('open'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-share-close]')) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });

        const copyBtn = document.getElementById('shareCopyBtn');
        const linkInput = document.getElementById('shareLinkInput');
        if (copyBtn && linkInput) {
            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(linkInput.value);
                    const orig = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="bi bi-check2"></i> ' + copyBtn.dataset.copied;
                    setTimeout(() => { copyBtn.innerHTML = orig; }, 2000);
                } catch (e) {
                    linkInput.select();
                    document.execCommand('copy');
                }
            });
        }
    })();
</script>
@endpush
