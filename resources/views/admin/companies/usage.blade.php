@extends('layouts.admin')

@section('title', 'Usage — ' . $company->name)

@push('styles')
    .key-status {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; border-radius: 8px; font-size: 0.875rem;
    }
@endpush

@section('header')
<div class="page-header">
    <div class="container">
        <a href="{{ route('admin.companies.index') }}" class="back-link d-inline-flex align-items-center gap-1 mb-3">
            <i class="bi bi-arrow-left"></i> {{ __('admin.companies') }}
        </a>
        <h4 class="fw-bold mb-1" style="font-size:1.4rem;">
            <i class="bi bi-bar-chart-line me-2" style="opacity:0.7;"></i>{{ __('admin.usage_dashboard') }}
        </h4>
        <p style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-bottom:0;">
            {{ $company->name }} ({{ $company->slug }}) — {{ __('admin.data_from_local') }}
        </p>
    </div>
</div>
@endsection

@section('content')
    {{-- API Key Status --}}
    <div class="card mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div>
                <h6 class="fw-semibold mb-1">{{ __('admin.api_key_status') }}</h6>
                @if($company->aws_api_key)
                    @if($company->aws_key_active)
                        <span class="key-status bg-success bg-opacity-10 text-success">
                            <i class="bi bi-key-fill"></i> {{ __('admin.key_active_own') }}
                        </span>
                    @else
                        <span class="key-status bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-key"></i> {{ __('admin.key_inactive_fallback') }}
                        </span>
                    @endif
                @else
                    <span class="key-status bg-light text-muted">
                        <i class="bi bi-dash-circle"></i> {{ __('admin.key_not_set') }}
                    </span>
                @endif
            </div>
            <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-grab">
                <i class="bi bi-pencil me-1"></i>{{ __('admin.edit') }}
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: var(--grab-green-light); color: var(--grab-green);">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <div>
                        <div class="stat-label">{{ __('admin.total_all_time') }}</div>
                        <div class="stat-value">{{ number_format($totalAllTime) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #eef2ff; color: #6366f1;">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <div class="stat-label">{{ __('admin.this_month') }}</div>
                        <div class="stat-value">{{ number_format($totalThisMonth) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #fff3e0; color: #f59e0b;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-label">{{ __('admin.est_cost_month') }}</div>
                        <div class="stat-value" style="color:var(--grab-green);">${{ number_format($estimatedCost, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown Table --}}
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-diagram-3 me-1 text-muted"></i> {{ __('admin.breakdown_endpoint') }}</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('admin.endpoint') }}</th>
                            <th class="text-end">{{ __('admin.requests') }}</th>
                            <th class="text-end">{{ __('admin.price_per_1k') }}</th>
                            <th class="text-end">{{ __('admin.subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $endpointLabels = \App\Models\ApiUsageLog::ENDPOINT_LABELS;
                            $costPer1000 = \App\Models\ApiUsageLog::COST_PER_1000;
                        @endphp
                        @forelse($costPer1000 as $type => $rate)
                            @php
                                $count = $monthlyCounts[$type] ?? 0;
                                $subtotal = \App\Models\ApiUsageLog::estimateCost($type, $count);
                            @endphp
                            <tr>
                                <td><span class="fw-medium">{{ $endpointLabels[$type] ?? $type }}</span></td>
                                <td class="text-end">{{ number_format($count) }}</td>
                                <td class="text-end text-muted">${{ number_format($rate, 2) }}</td>
                                <td class="text-end fw-medium">${{ number_format($subtotal, 4) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th>{{ __('admin.total') }}</th>
                            <th class="text-end">{{ number_format($totalThisMonth) }}</th>
                            <th></th>
                            <th class="text-end" style="color:var(--grab-green);">${{ number_format($estimatedCost, 4) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Daily Chart --}}
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-line me-1 text-muted"></i> {{ __('admin.daily_requests_30') }}</h6>
            @if($dailyCounts->isEmpty())
                <div class="empty-state">
                    <i class="bi bi-bar-chart d-block"></i>
                    <p class="text-muted mb-0">{{ __('admin.no_request_data') }}</p>
                </div>
            @else
                @php
                    $maxCount = $dailyCounts->max() ?: 1;
                    $allDays = collect();
                    for ($i = 29; $i >= 0; $i--) {
                        $date = now()->subDays($i)->format('Y-m-d');
                        $allDays[$date] = $dailyCounts[$date] ?? 0;
                    }
                @endphp
                <div class="chart-container">
                    <div class="chart-bar">
                        @foreach($allDays as $date => $count)
                        <div class="bar-wrapper">
                            <div class="bar" style="height: {{ max(($count / $maxCount) * 100, 1.5) }}%;">
                                <div class="bar-tooltip">
                                    {{ \Carbon\Carbon::parse($date)->format('d M Y') }}<br>
                                    <strong>{{ number_format($count) }} requests</strong>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted" style="font-size:0.75rem;">{{ now()->subDays(29)->format('d M Y') }}</small>
                    <small class="text-muted" style="font-size:0.75rem;">{{ now()->format('d M Y') }}</small>
                </div>
            @endif
        </div>
    </div>
@endsection
