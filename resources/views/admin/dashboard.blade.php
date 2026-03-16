@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
    .welcome-banner {
        background: linear-gradient(135deg, #0a2e1a 0%, #0d4a28 40%, var(--grab-green-dark) 100%);
        border-radius: 20px;
        padding: 28px 32px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -5%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(0,177,79,0.2) 0%, transparent 70%);
        border-radius: 50%;
    }
    .welcome-banner * { position: relative; z-index: 1; }
    .welcome-banner h4 { font-size: 1.3rem; font-weight: 700; margin-bottom: 4px; }
    .welcome-banner p { color: rgba(255,255,255,0.6); font-size: 0.85rem; margin: 0; }

    .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media (max-width: 992px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }

    .mini-stat {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .mini-stat .icon-box {
        width: 48px; height: 48px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; flex-shrink: 0;
    }
    .mini-stat .stat-num { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: #1a1a2e; }
    .mini-stat .stat-lbl { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; font-weight: 600; }

    .section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #adb5bd; }

    .top-company-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f2f5;
    }
    .top-company-row:last-child { border-bottom: none; }
    .top-rank {
        width: 28px; height: 28px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700;
    }
    .top-rank.r1 { background: #fff8e1; color: #f59e0b; }
    .top-rank.r2 { background: #f0f2f5; color: #6c757d; }
    .top-rank.r3 { background: #fde8e8; color: #e07a5f; }

    .cost-cat-card {
        background: #f8f9fa;
        border-radius: 14px;
        padding: 18px;
        text-align: center;
    }
    .cost-cat-card .cat-icon {
        width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; margin: 0 auto 10px;
    }
    .cost-cat-card .cat-cost { font-size: 1.25rem; font-weight: 700; color: #1a1a2e; }
    .cost-cat-card .cat-label { font-size: 0.78rem; color: #6c757d; font-weight: 500; }
    .cost-cat-card .cat-count { font-size: 0.72rem; color: #adb5bd; margin-top: 2px; }

    .quick-link {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 18px; border-radius: 12px;
        border: 1.5px solid #e2e8f0; background: #fff;
        text-decoration: none; color: #1a1a2e;
        transition: all 0.15s;
    }
    .quick-link:hover { border-color: var(--grab-green); background: var(--grab-green-light); color: var(--grab-green-dark); }
    .quick-link .ql-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; background: #f0f2f5; color: #6c757d;
        transition: all 0.15s;
    }
    .quick-link:hover .ql-icon { background: var(--grab-green); color: #fff; }
    .quick-link .ql-text { font-size: 0.85rem; font-weight: 600; }
    .quick-link .ql-desc { font-size: 0.75rem; color: #6c757d; }
@endpush

@section('content')
    {{-- Welcome Banner --}}
    <div class="welcome-banner">
        <h4>GrabMaps Admin Dashboard</h4>
        <p>Overview penggunaan AWS Location Service dan manajemen company</p>
    </div>

    {{-- Stat Grid --}}
    <div class="stat-grid">
        <div class="mini-stat">
            <div class="icon-box" style="background: var(--grab-green-light); color: var(--grab-green);">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <div class="stat-num">{{ $activeCompanies }}<span style="font-size:0.85rem; color:#6c757d; font-weight:400;">/{{ $totalCompanies }}</span></div>
                <div class="stat-lbl">Companies Aktif</div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="icon-box" style="background: #eef2ff; color: #6366f1;">
                <i class="bi bi-key-fill"></i>
            </div>
            <div>
                <div class="stat-num">{{ $apiKeysData['active'] }}<span style="font-size:0.85rem; color:#6c757d; font-weight:400;">/{{ $apiKeysData['total'] }}</span></div>
                <div class="stat-lbl">API Keys Aktif</div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="icon-box" style="background: #fff3e0; color: #f59e0b;">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div>
                <div class="stat-num">{{ number_format($localRequestsThisMonth) }}</div>
                <div class="stat-lbl">Requests Bulan Ini</div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="icon-box" style="background: #fde8e8; color: #dc3545;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-num" style="color: {{ $localEstimatedCost > 0 ? '#1a1a2e' : '#6c757d' }};">${{ number_format($localEstimatedCost, 2) }}</div>
                <div class="stat-lbl">Est. Biaya Bulan Ini</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Daily Chart --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-bar-chart-line"></i> Request Harian (30 Hari Terakhir)</div>
                    @php
                        $allDays = collect();
                        for ($i = 29; $i >= 0; $i--) {
                            $date = now()->subDays($i)->format('Y-m-d');
                            $allDays[$date] = $localDaily[$date] ?? 0;
                        }
                        $maxCount = $allDays->max() ?: 1;
                    @endphp

                    @if($allDays->sum() === 0)
                    <div class="empty-state"><i class="bi bi-bar-chart d-block"></i><p class="text-muted mb-0">Belum ada data request.</p></div>
                    @else
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
                        <small class="text-muted" style="font-size:0.72rem;">{{ now()->subDays(29)->format('d M') }}</small>
                        <small class="text-muted" style="font-size:0.72rem;">{{ now()->format('d M') }}</small>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Cost Breakdown by Category (CloudWatch) --}}
            @if($cloudwatchData)
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="section-title mb-0"><i class="bi bi-wallet2"></i> Estimasi Biaya Bulan Ini (CloudWatch)</div>
                        <span class="fw-bold" style="font-size:1.1rem; color:var(--grab-green);">${{ number_format($cloudwatchData['total_cost'], 2) }}</span>
                    </div>
                    @php
                        $catData = [
                            ['label' => 'Maps', 'icon' => 'bi-map', 'color' => 'var(--grab-green)', 'bg' => 'var(--grab-green-light)', 'count' => $cloudwatchData['by_category']['maps'], 'rate' => 0.04],
                            ['label' => 'Places / Search', 'icon' => 'bi-search', 'color' => '#6366f1', 'bg' => '#eef2ff', 'count' => $cloudwatchData['by_category']['places'], 'rate' => 4.00],
                            ['label' => 'Routes', 'icon' => 'bi-sign-turn-right', 'color' => '#f59e0b', 'bg' => '#fff3e0', 'count' => $cloudwatchData['by_category']['routes'], 'rate' => 5.00],
                        ];
                    @endphp
                    <div class="row g-3">
                        @foreach($catData as $cat)
                        <div class="col-md-4">
                            <div class="cost-cat-card">
                                <div class="cat-icon" style="background: {{ $cat['bg'] }}; color: {{ $cat['color'] }};">
                                    <i class="bi {{ $cat['icon'] }}"></i>
                                </div>
                                <div class="cat-cost">${{ number_format(($cat['count'] / 1000) * $cat['rate'], 2) }}</div>
                                <div class="cat-label">{{ $cat['label'] }}</div>
                                <div class="cat-count">{{ number_format($cat['count']) }} requests</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Endpoint Breakdown (Local) --}}
            @if($localByEndpoint->isNotEmpty())
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-diagram-3"></i> Breakdown per Endpoint (Bulan Ini)</div>
                    @php $endpointMax = $localByEndpoint->max() ?: 1; @endphp
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:0.85rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e2e8f0;">
                                    <th style="width:200px;">Endpoint</th>
                                    <th>Usage</th>
                                    <th class="text-end" style="width:100px;">Requests</th>
                                    <th class="text-end" style="width:90px;">Est. Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($localByEndpoint->sortDesc() as $type => $count)
                                @php $label = \App\Models\ApiUsageLog::ENDPOINT_LABELS[$type] ?? $type; @endphp
                                <tr>
                                    <td><span class="fw-medium">{{ $label }}</span></td>
                                    <td>
                                        <div class="op-bar-track"><div class="op-bar-fill" style="width: {{ ($count / $endpointMax) * 100 }}%;"></div></div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format($count) }}</td>
                                    <td class="text-end fw-semibold" style="color:#f59e0b;">${{ number_format(\App\Models\ApiUsageLog::estimateCost($type, $count), 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Top Companies --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-trophy"></i> Top Companies Bulan Ini</div>
                    @if($topCompanies->isEmpty())
                    <div class="text-center text-muted py-3" style="font-size:0.85rem;">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem;"></i>
                        Belum ada data
                    </div>
                    @else
                    @foreach($topCompanies as $idx => $item)
                    <div class="top-company-row">
                        <div class="top-rank {{ $idx === 0 ? 'r1' : ($idx === 1 ? 'r2' : 'r3') }}">{{ $idx + 1 }}</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.85rem;">{{ $item->company->name }}</div>
                            <small class="text-muted">{{ $item->company->slug }}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold" style="font-size:0.9rem;">{{ number_format($item->count) }}</div>
                            <small class="text-muted">requests</small>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>

            {{-- Budget Status --}}
            @php
                $budget = 200;
                $spent = $cloudwatchData ? $cloudwatchData['total_cost'] : $localEstimatedCost;
                $budgetPct = min(($spent / $budget) * 100, 100);
                $budgetColor = $spent >= $budget ? '#dc3545' : ($spent >= $budget * 0.75 ? '#f59e0b' : 'var(--grab-green)');
            @endphp
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-speedometer2"></i> Budget Status</div>
                    <div class="text-center mb-3">
                        <div class="fw-bold" style="font-size: 2rem; color: {{ $budgetColor }};">${{ number_format($spent, 2) }}</div>
                        <small class="text-muted">dari ${{ number_format($budget) }} budget bulanan</small>
                    </div>
                    <div style="height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden;">
                        <div style="height:100%; width:{{ $budgetPct }}%; background:{{ $budgetColor }}; border-radius:5px; transition: width 0.5s;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted" style="font-size:0.72rem;">$0</small>
                        <small style="font-size:0.72rem; font-weight:600; color:{{ $budgetColor }};">{{ number_format($budgetPct, 0) }}%</small>
                        <small class="text-muted" style="font-size:0.72rem;">${{ number_format($budget) }}</small>
                    </div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-lightning"></i> Quick Links</div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('admin.companies.create') }}" class="quick-link">
                            <div class="ql-icon"><i class="bi bi-plus-lg"></i></div>
                            <div>
                                <div class="ql-text">Tambah Company</div>
                                <div class="ql-desc">Buat company baru dengan fitur maps</div>
                            </div>
                        </a>
                        <a href="{{ route('admin.api-keys.index') }}" class="quick-link">
                            <div class="ql-icon"><i class="bi bi-key"></i></div>
                            <div>
                                <div class="ql-text">Manage API Keys</div>
                                <div class="ql-desc">Lihat dan assign keys dari AWS</div>
                            </div>
                        </a>
                        <a href="{{ url('/') }}" class="quick-link" target="_blank">
                            <div class="ql-icon"><i class="bi bi-map"></i></div>
                            <div>
                                <div class="ql-text">Buka Homepage</div>
                                <div class="ql-desc">Lihat demo map utama</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
