@extends('layouts.admin')

@section('title', 'API Key Usage — ' . $keyName)

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
@endpush

@push('styles')
    .header-content { position: relative; z-index: 1; }
    .header-key-name { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.02em; }
    .header-meta { display: flex; gap: 24px; margin-top: 16px; }
    .header-meta-item { display: flex; flex-direction: column; gap: 2px; }
    .header-meta-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.4); font-weight: 600; }
    .header-meta-value { font-size: 0.85rem; color: rgba(255,255,255,0.9); font-weight: 500; }
    .header-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; backdrop-filter: blur(8px); }

    .filter-bar { background: #fff; border-radius: 16px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.06); }
    .filter-input-box {
        display: flex; align-items: center; gap: 8px; height: 40px; padding: 0 14px;
        background: #f8f9fa; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 0.85rem; cursor: pointer; transition: border-color 0.15s;
    }
    .filter-input-box:hover { border-color: var(--grab-green); }
    .filter-btn { height: 40px; border-radius: 10px; font-size: 0.85rem; padding: 0 16px; }

    .flatpickr-calendar { border-radius: 12px !important; box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important; border: none !important; }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: var(--grab-green) !important; border-color: var(--grab-green) !important; }
    .flatpickr-day.inRange { background: var(--grab-green-light) !important; border-color: var(--grab-green-light) !important; box-shadow: -5px 0 0 var(--grab-green-light), 5px 0 0 var(--grab-green-light) !important; }
    .flatpickr-day.today:not(.selected) { border-color: var(--grab-green) !important; }
@endpush

@section('header')
<div class="page-header">
    <div class="container header-content">
        <a href="{{ route('admin.api-keys.index') }}" class="back-link d-inline-flex align-items-center gap-1 mb-3">
            <i class="bi bi-arrow-left"></i> {{ __('admin.back_to_keys') }}
        </a>
        <div class="d-flex align-items-start justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-key-fill" style="font-size:1.2rem; color:rgba(255,255,255,0.8);"></i>
                    </div>
                    <div>
                        <h4 class="header-key-name mb-0">{{ $keyName }}</h4>
                        <small style="opacity:0.5; font-size:0.8rem;">
                            {{ $keyInfo ? ($keyInfo['description'] ?: 'AWS Location Service API Key') : 'AWS Location Service API Key' }}
                        </small>
                    </div>
                </div>
            </div>
            <div>
                @if($assignedCompany)
                    <span class="header-badge" style="background: rgba(52,216,118,0.15); color: #5eff9e;">
                        <i class="bi bi-building"></i> {{ $assignedCompany->name }}
                    </span>
                @else
                    <span class="header-badge" style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.4);">
                        <i class="bi bi-dash-circle"></i> {{ __('admin.not_assigned') }}
                    </span>
                @endif
            </div>
        </div>
        @if($keyInfo)
        <div class="header-meta">
            <div class="header-meta-item">
                <span class="header-meta-label">{{ __('admin.created') }}</span>
                <span class="header-meta-value">{{ $keyInfo['create_time'] ? \Carbon\Carbon::parse($keyInfo['create_time'])->format('d M Y') : '—' }}</span>
            </div>
            <div class="header-meta-item">
                <span class="header-meta-label">{{ __('admin.expire') }}</span>
                <span class="header-meta-value">{{ $keyInfo['expire_time'] ? \Carbon\Carbon::parse($keyInfo['expire_time'])->format('d M Y') : __('admin.no_expire') }}</span>
            </div>
            <div class="header-meta-item">
                <span class="header-meta-label">{{ __('admin.status') }}</span>
                <span class="header-meta-value">
                    @if($keyInfo['expire_time'] && \Carbon\Carbon::parse($keyInfo['expire_time'])->isPast())
                        <span style="color:#ff6b6b;">{{ __('admin.expired') }}</span>
                    @else
                        <span style="color:#5eff9e;">{{ __('admin.active') }}</span>
                    @endif
                </span>
            </div>
            <div class="header-meta-item">
                <span class="header-meta-label">{{ __('admin.region') }}</span>
                <span class="header-meta-value">{{ config('aws.region') }}</span>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('content')
    @if($keyError)
    <div class="alert alert-warning shadow-sm"><i class="bi bi-exclamation-triangle me-1"></i> Gagal ambil detail key: {{ $keyError }}</div>
    @endif

    {{-- Filter Bar --}}
    <div class="filter-bar mb-4">
        <form method="GET" action="{{ route('admin.api-keys.usage', $keyName) }}" id="filterForm">
            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <span class="text-muted" style="font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em;">{{ __('admin.quick_range') }}</span>
                @foreach([7 => '7D', 14 => '14D', 30 => '30D', 60 => '60D', 90 => '90D'] as $d => $label)
                @php $qStart = now()->subDays($d - 1)->format('Y-m-d'); $qEnd = now()->format('Y-m-d'); $isActive = ($startDate === $qStart && $endDate === $qEnd); @endphp
                <button type="button" class="btn btn-sm {{ $isActive ? 'btn-grab' : 'btn-outline-secondary' }} quick-range"
                    style="border-radius:8px; min-width:48px; font-size:0.8rem;" data-start="{{ $qStart }}" data-end="{{ $qEnd }}">{{ $label }}</button>
                @endforeach
                <div class="vr mx-1" style="height:20px;"></div>
                @if($isCached)<span class="text-muted" style="font-size:0.72rem;"><i class="bi bi-clock-history"></i> {{ __('admin.cached', ['min' => 30]) }}</span>@endif
                <a href="{{ route('admin.api-keys.usage', array_filter(['keyName' => $keyName, 'start' => $startDate, 'end' => $endDate, 'operation' => $filterOperation, 'refresh' => 1])) }}"
                    class="btn btn-sm btn-outline-grab" style="margin-left:auto;"><i class="bi bi-arrow-clockwise"></i></a>
            </div>

            <input type="hidden" name="start" id="filterStart" value="{{ $startDate }}">
            <input type="hidden" name="end" id="filterEnd" value="{{ $endDate }}">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="filter-input-box date-range-box" id="dateRangeBox">
                    <i class="bi bi-calendar3 text-muted"></i>
                    <span id="dateRangeLabel" style="font-weight:500; white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </span>
                    <i class="bi bi-chevron-down text-muted" style="font-size:0.65rem;"></i>
                </div>
                <input type="text" id="dateRangePicker" style="position:absolute; opacity:0; width:0; height:0;">
                <div class="filter-input-box">
                    <i class="bi bi-diagram-3 text-muted"></i>
                    <select name="operation" class="border-0 bg-transparent" style="font-weight:500; outline:none; width:180px; -webkit-appearance:none; appearance:none;">
                        <option value="">{{ __('admin.all_operations') }}</option>
                        @foreach($operations as $op)
                        <option value="{{ $op }}" {{ $filterOperation === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                    <i class="bi bi-chevron-down text-muted" style="font-size:0.65rem;"></i>
                </div>
                <button type="submit" class="btn btn-grab filter-btn"><i class="bi bi-search me-1"></i>{{ __('admin.filter') }}</button>
                @if($filterOperation || $startDate !== now()->subDays(29)->format('Y-m-d') || $endDate !== now()->format('Y-m-d'))
                <a href="{{ route('admin.api-keys.usage', $keyName) }}" class="btn btn-outline-secondary filter-btn d-flex align-items-center justify-content-center" style="width:40px; padding:0;"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>

    @if($metrics['error'])
    <div class="card mb-4">
        <div class="card-body empty-state">
            <i class="bi bi-exclamation-triangle d-block" style="color:#dc3545;"></i>
            <h6 class="fw-semibold mb-2">{{ __('admin.failed_metrics') }}</h6>
            <p class="text-muted mb-2" style="font-size:0.85rem;">{{ $metrics['error'] }}</p>
            <small class="text-muted">{{ __('admin.permission_needed', ['permission' => 'cloudwatch:GetMetricData']) }}</small>
        </div>
    </div>
    @else
    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: var(--grab-green-light); color: var(--grab-green);"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div><div class="stat-label">{{ __('admin.total_request') }}</div><div class="stat-value">{{ number_format($metrics['total']) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #eef2ff; color: #6366f1;"><i class="bi bi-graph-up"></i></div>
                    <div><div class="stat-label">{{ __('admin.avg_per_day') }}</div><div class="stat-value">{{ $metrics['total'] > 0 ? number_format($metrics['total'] / $days, 0) : '0' }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #fff3e0; color: #f59e0b;"><i class="bi bi-calendar-range"></i></div>
                    <div><div class="stat-label">{{ __('admin.period') }}</div><div class="stat-value" style="font-size:1.1rem;">{{ __('admin.days', ['count' => $days]) }}</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Chart --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart-line me-1 text-muted"></i> {{ __('admin.request_per_day') }}</h6>
                @if($filterOperation)
                <span class="badge fw-normal px-3 py-2" style="background: var(--grab-green-light); color: var(--grab-green); border-radius: 8px; font-size:0.78rem;">
                    <i class="bi bi-funnel-fill me-1"></i>{{ $filterOperation }}
                </span>
                @endif
            </div>
            @if(empty($metrics['daily']))
            <div class="empty-state"><i class="bi bi-bar-chart d-block"></i><p class="text-muted mb-0">{{ __('admin.no_request_period') }}</p></div>
            @else
            @php
                $allDays = collect();
                $current = \Carbon\Carbon::parse($startDate)->copy();
                $endParsed = \Carbon\Carbon::parse($endDate);
                while ($current->lte($endParsed)) { $allDays[$current->format('Y-m-d')] = $metrics['daily'][$current->format('Y-m-d')] ?? 0; $current->addDay(); }
                $maxCount = $allDays->max() ?: 1;
            @endphp
            <div class="chart-container">
                <div class="chart-bar">
                    @foreach($allDays as $date => $count)
                    <div class="bar-wrapper">
                        <div class="bar" style="height: {{ max(($count / $maxCount) * 100, 1.5) }}%;">
                            <div class="bar-tooltip">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}<br><strong>{{ number_format($count) }} requests</strong></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <small class="text-muted" style="font-size:0.75rem;">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</small>
                <small class="text-muted" style="font-size:0.75rem;">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</small>
            </div>
            @endif
        </div>
    </div>

    {{-- Operation Breakdown + Cost --}}
    @if(!empty($metrics['operations']))
    @php
        $pricing = [
            'GetMapTile' => 0.04, 'GetMapStyleDescriptor' => 0, 'GetMapGlyphs' => 0.04, 'GetMapSprites' => 0.04,
            'SearchPlaceIndexForSuggestions' => 4.00, 'SearchPlaceIndexForText' => 4.00, 'SearchPlaceIndexForPosition' => 4.00, 'GetPlace' => 4.00,
            'CalculateRoute' => 5.00, 'CalculateRouteMatrix' => 5.00,
        ];
        $categories = [
            'maps' => ['GetMapTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites'],
            'places' => ['SearchPlaceIndexForSuggestions', 'SearchPlaceIndexForText', 'SearchPlaceIndexForPosition', 'GetPlace'],
            'routes' => ['CalculateRoute', 'CalculateRouteMatrix'],
        ];
        $categoryLabels = ['maps' => 'Maps', 'places' => 'Places / Search', 'routes' => 'Routes'];
        $categoryIcons = ['maps' => 'bi-map', 'places' => 'bi-search', 'routes' => 'bi-sign-turn-right'];
        $totalCost = 0;
        $categoryTotals = [];
        foreach ($categories as $cat => $ops) { $c = 0; foreach ($ops as $op) { $c += $metrics['operations'][$op] ?? 0; } $categoryTotals[$cat] = $c; }
        $opMax = max(array_values($metrics['operations'])) ?: 1;
    @endphp

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-diagram-3 me-1 text-muted"></i> {{ __('admin.breakdown_operation') }}</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:0.85rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e2e8f0;">
                            <th style="width:220px;">{{ __('admin.operation') }}</th><th>{{ __('admin.usage') }}</th>
                            <th class="text-end" style="width:90px;">{{ __('admin.requests') }}</th><th class="text-end" style="width:80px;">{{ __('admin.per_1k') }}</th>
                            <th class="text-end" style="width:90px;">{{ __('admin.est_cost') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($metrics['operations'] as $opName => $opCount)
                        @php $rate = $pricing[$opName] ?? 0; $cost = ($opCount / 1000) * $rate; $totalCost += $cost; @endphp
                        <tr>
                            <td><span class="fw-medium">{{ $opName }}</span></td>
                            <td><div class="op-bar-track"><div class="op-bar-fill" style="width: {{ ($opCount / $opMax) * 100 }}%;"></div></div></td>
                            <td class="text-end fw-semibold">{{ number_format($opCount) }}</td>
                            <td class="text-end text-muted">${{ number_format($rate, 2) }}</td>
                            <td class="text-end fw-semibold" style="color: {{ $cost > 0 ? '#f59e0b' : '#6c757d' }};">${{ number_format($cost, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid #e2e8f0;">
                            <td class="fw-bold">{{ __('admin.total') }}</td><td></td>
                            <td class="text-end fw-bold">{{ number_format(array_sum($metrics['operations'])) }}</td><td></td>
                            <td class="text-end fw-bold" style="color: var(--grab-green); font-size:1rem;">${{ number_format($totalCost, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Budget Alert + Cost per Category --}}
    @php
        $budgetLimit = 200;
        $budgetPct = min(($totalCost / $budgetLimit) * 100, 100);
        $budgetOver = $totalCost >= $budgetLimit;
        $budgetWarn = $totalCost >= ($budgetLimit * 0.75);
        $budgetColor = $budgetOver ? '#dc3545' : ($budgetWarn ? '#f59e0b' : 'var(--grab-green)');
        $categoryCosts = [];
        foreach ($categories as $cat => $ops) { $cc = 0; foreach ($ops as $op) { $cc += (($metrics['operations'][$op] ?? 0) / 1000) * ($pricing[$op] ?? 0); } $categoryCosts[$cat] = $cc; }
    @endphp

    @if($budgetOver || $budgetWarn)
    <div class="card mb-4" style="border-left: 4px solid {{ $budgetColor }} !important;">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon" style="background: {{ $budgetOver ? '#fde8e8' : '#fff8e1' }}; color: {{ $budgetColor }};"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="flex-grow-1">
                <div class="fw-semibold" style="font-size:0.9rem;">
                    @if($budgetOver) {{ __('admin.budget_exceeded', ['limit' => number_format($budgetLimit)]) }}
                    @else {{ __('admin.budget_warning', ['pct' => number_format($budgetPct, 0), 'limit' => number_format($budgetLimit)]) }}
                    @endif
                </div>
                <small class="text-muted">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold" style="font-size:1.25rem; color: {{ $budgetColor }};">${{ number_format($totalCost, 2) }}</div>
                <small class="text-muted">/ ${{ number_format($budgetLimit) }}</small>
            </div>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-wallet2 me-1 text-muted"></i> {{ __('admin.cost_by_cat') }}</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size:0.75rem;">{{ __('admin.budget') }}</span>
                    <div style="width:120px; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                        <div style="height:100%; width:{{ $budgetPct }}%; background:{{ $budgetColor }}; border-radius:3px;"></div>
                    </div>
                    <span style="font-size:0.78rem; font-weight:600; color:{{ $budgetColor }};">${{ number_format($totalCost, 2) }}/${{ $budgetLimit }}</span>
                </div>
            </div>
            <div class="row g-3">
                @foreach($categories as $cat => $ops)
                @php $catCost = $categoryCosts[$cat]; $catCount = $categoryTotals[$cat]; $catPct = $totalCost > 0 ? ($catCost / $totalCost) * 100 : 0; @endphp
                <div class="col-md-4">
                    <div class="p-3 rounded-3" style="background: #f8f9fa;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $categoryIcons[$cat] }}" style="color: var(--grab-green);"></i>
                            <span class="fw-medium" style="font-size:0.85rem;">{{ $categoryLabels[$cat] }}</span>
                        </div>
                        <div class="fw-bold" style="font-size:1.25rem; color: {{ $catCost > 0 ? '#1a1a2e' : '#6c757d' }};">${{ number_format($catCost, 2) }}</div>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <small class="text-muted">{{ number_format($catCount) }} {{ __('admin.requests') }}</small>
                            @if($catPct > 0)<small class="fw-medium" style="color: var(--grab-green);">{{ number_format($catPct, 0) }}%</small>@endif
                        </div>
                        <div style="height:4px; background:#e2e8f0; border-radius:2px; overflow:hidden; margin-top:6px;">
                            <div style="height:100%; width:{{ $catPct }}%; background: var(--grab-green); border-radius:2px;"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    const startInput = document.getElementById('filterStart');
    const endInput = document.getElementById('filterEnd');
    const label = document.getElementById('dateRangeLabel');
    const box = document.getElementById('dateRangeBox');

    function formatDate(d) {
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    const fp = flatpickr('#dateRangePicker', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        defaultDate: ['{{ $startDate }}', '{{ $endDate }}'],
        maxDate: 'today',
        locale: { rangeSeparator: ' — ' },
        onChange: function(dates) {
            if (dates.length === 2) {
                startInput.value = dates[0].toISOString().split('T')[0];
                endInput.value = dates[1].toISOString().split('T')[0];
                label.textContent = formatDate(dates[0]) + ' — ' + formatDate(dates[1]);
            }
        },
        onOpen: function() { box.style.borderColor = '#00B14F'; },
        onClose: function() { box.style.borderColor = '#e2e8f0'; }
    });

    box.addEventListener('click', () => fp.open());

    document.querySelectorAll('.quick-range').forEach(btn => {
        btn.addEventListener('click', () => {
            startInput.value = btn.dataset.start;
            endInput.value = btn.dataset.end;
            document.getElementById('filterForm').submit();
        });
    });
</script>
@endpush
