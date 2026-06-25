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
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4>{{ __('admin.dashboard_title') }}</h4>
                <p>{{ __('admin.dashboard_subtitle') }}</p>
            </div>
            <div class="text-end">
                @if($fetchedAt)
                    @php $stale = $fetchedAt->lt(now()->subDay()); @endphp
                    <div style="font-size:0.72rem; color:{{ $stale ? '#ffd27a' : 'rgba(255,255,255,0.6)' }};" title="Waktu data terakhir diambil dari AWS (tidak real-time)">
                        <i class="bi bi-clock-history"></i> Data per {{ $fetchedAt->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB{{ $stale ? ' — perlu refresh' : '' }}
                    </div>
                @else
                    <div style="font-size:0.72rem; color:rgba(255,255,255,0.6);"><i class="bi bi-info-circle"></i> Belum ada data — klik Refresh</div>
                @endif
                <a href="{{ route('admin.dashboard', ['refresh' => 1]) }}" class="btn btn-sm btn-light mt-2" title="Ambil data terbaru dari AWS CloudWatch">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh data
                </a>
            </div>
        </div>
    </div>

    @if(!empty($cw['error']))
        <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> Gagal ambil data AWS: {{ $cw['error'] }}</div>
    @endif

    {{-- Peringatan Budget (selaras AWS Budgets) --}}
    @php $budgetPctTop = $budgetAlert > 0 ? ($grandCost / $budgetAlert) * 100 : 0; @endphp
    @if($budgetAlert > 0 && $grandCost >= $budgetAlert)
        <div class="alert alert-danger d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-octagon-fill mt-1"></i>
            <div>
                <strong>Ambang peringatan budget AWS terlampaui.</strong>
                Estimasi biaya bulan ini <strong>${{ number_format($grandCost, 2) }}</strong> (≈ Rp {{ number_format($grandCost * $idrRate, 0, ',', '.') }})
                sudah <strong>{{ number_format($budgetPctTop, 0) }}%</strong> dari ambang AWS Budgets <strong>${{ number_format($budgetAlert, 0) }}</strong>.
                Cek pemakaian per company sebelum biaya membengkak.
            </div>
        </div>
    @elseif($budgetAlert > 0 && $budgetPctTop >= 80)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>Mendekati ambang peringatan budget AWS.</strong>
                Estimasi biaya <strong>${{ number_format($grandCost, 2) }}</strong> sudah <strong>{{ number_format($budgetPctTop, 0) }}%</strong>
                dari ambang AWS Budgets <strong>${{ number_format($budgetAlert, 0) }}</strong>.
            </div>
        </div>
    @endif

    {{-- Stat Grid --}}
    <div class="stat-grid">
        <div class="mini-stat">
            <div class="icon-box" style="background: var(--grab-green-light); color: var(--grab-green);">
                <i class="bi bi-building"></i>
            </div>
            <div>
                <div class="stat-num">{{ $activeCompanies }}<span style="font-size:0.85rem; color:#6c757d; font-weight:400;">/{{ $totalCompanies }}</span></div>
                <div class="stat-lbl">{{ __('admin.active_companies') }}</div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="icon-box" style="background: #eef2ff; color: #6366f1;">
                <i class="bi bi-key-fill"></i>
            </div>
            <div>
                <div class="stat-num">{{ $apiKeysData['active'] }}<span style="font-size:0.85rem; color:#6c757d; font-weight:400;">/{{ $apiKeysData['total'] }}</span></div>
                <div class="stat-lbl">{{ __('admin.active_api_keys') }}</div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="icon-box" style="background: #fff3e0; color: #f59e0b;">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div>
                <div class="stat-num">{{ number_format($totalRequests) }}</div>
                <div class="stat-lbl">{{ __('admin.requests_this_month') }}</div>
            </div>
        </div>
        <div class="mini-stat">
            <div class="icon-box" style="background: #fde8e8; color: #dc3545;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="stat-num" style="color: {{ $grandCost > 0 ? '#1a1a2e' : '#6c757d' }};">${{ number_format($grandCost, 2) }}</div>
                <div class="stat-lbl">{{ __('admin.est_cost_this_month') }} <span style="text-transform:none;">+ PPN</span></div>
                @if($grandCost > 0)<div style="font-size:0.72rem; color:#6c757d;">≈ Rp {{ number_format($grandCost * $idrRate, 0, ',', '.') }}</div>@endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column --}}
        <div class="col-lg-8">
            {{-- Daily Chart --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-bar-chart-line"></i> {{ __('admin.daily_requests') }}</div>
                    @php
                        $cwDaily = $cw['daily'] ?? [];
                        $allDays = collect();
                        for ($i = 29; $i >= 0; $i--) {
                            $date = now()->subDays($i)->format('Y-m-d');
                            $allDays[$date] = $cwDaily[$date] ?? 0;
                        }
                        $maxCount = $allDays->max() ?: 1;
                    @endphp

                    @if($allDays->sum() === 0)
                    <div class="empty-state"><i class="bi bi-bar-chart d-block"></i><p class="text-muted mb-0">{{ __('admin.no_request_data') }}</p></div>
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
            @if($totalRequests > 0)
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="section-title mb-0"><i class="bi bi-wallet2"></i> {{ __('admin.cost_by_category') }}</div>
                        <span class="fw-bold" style="font-size:1.1rem; color:var(--grab-green);">${{ number_format($totalCost, 2) }}</span>
                    </div>
                    @php
                        $catData = [
                            ['key' => 'maps',   'label' => 'Maps',            'icon' => 'bi-map',             'color' => 'var(--grab-green)', 'bg' => 'var(--grab-green-light)'],
                            ['key' => 'places', 'label' => 'Places / Search', 'icon' => 'bi-search',          'color' => '#6366f1',           'bg' => '#eef2ff'],
                            ['key' => 'routes', 'label' => 'Routes',          'icon' => 'bi-sign-turn-right', 'color' => '#f59e0b',           'bg' => '#fff3e0'],
                        ];
                    @endphp
                    <div class="row g-3">
                        @foreach($catData as $cat)
                        <div class="col-md-4">
                            <div class="cost-cat-card">
                                <div class="cat-icon" style="background: {{ $cat['bg'] }}; color: {{ $cat['color'] }};">
                                    <i class="bi {{ $cat['icon'] }}"></i>
                                </div>
                                <div class="cat-cost">${{ number_format($catCost[$cat['key']], 2) }}</div>
                                <div class="cat-label">{{ $cat['label'] }}</div>
                                <div class="cat-count">{{ number_format($catCount[$cat['key']]) }} requests</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Rincian per Operasi (CloudWatch) --}}
            @if(!empty($operations))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-diagram-3"></i> Rincian per Operasi</div>
                    @php $opMax = max(array_values($operations)) ?: 1; @endphp
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:0.85rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e2e8f0;">
                                    <th style="width:200px;">Operasi</th>
                                    <th>{{ __('admin.usage') }}</th>
                                    <th class="text-end" style="width:100px;">{{ __('admin.requests') }}</th>
                                    <th class="text-end" style="width:70px;">$/1k</th>
                                    <th class="text-end" style="width:90px;">{{ __('admin.est_cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($operations as $op => $count)
                                @php $rate = \App\Services\AwsLocationService::PRICING[$op] ?? 0; $cost = ($count / 1000) * $rate; @endphp
                                <tr>
                                    <td><span class="fw-medium">{{ $op }}</span></td>
                                    <td><div class="op-bar-track"><div class="op-bar-fill" style="width: {{ ($count / $opMax) * 100 }}%;"></div></div></td>
                                    <td class="text-end fw-semibold">{{ number_format($count) }}</td>
                                    <td class="text-end text-muted">${{ number_format($rate, 2) }}</td>
                                    <td class="text-end fw-semibold" style="color:{{ $cost > 0 ? '#f59e0b' : '#6c757d' }};">${{ number_format($cost, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="border-top:2px solid #e2e8f0;">
                                    <td class="fw-semibold">Subtotal</td><td></td>
                                    <td class="text-end fw-semibold">{{ number_format($totalRequests) }}</td><td></td>
                                    <td class="text-end fw-semibold">${{ number_format($totalCost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">PPN {{ round($taxRate * 100, 2) }}%</td><td></td><td></td><td></td>
                                    <td class="text-end text-muted">${{ number_format($tax, 2) }}</td>
                                </tr>
                                <tr style="border-top:1px solid #e2e8f0;">
                                    <td class="fw-bold">Total + PPN</td><td></td><td></td><td></td>
                                    <td class="text-end fw-bold" style="color:var(--grab-green); font-size:1rem;">
                                        ${{ number_format($grandCost, 2) }}
                                        <div class="fw-normal text-muted" style="font-size:0.72rem;">≈ Rp {{ number_format($grandCost * $idrRate, 0, ',', '.') }}</div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mt-3 p-2 rounded-3" style="background:#f8f9fa; font-size:0.78rem;">
                        <i class="bi bi-cash-coin text-muted"></i>
                        <span class="text-muted">Kurs dipakai:</span>
                        <span class="fw-semibold">Rp {{ number_format($idrRate, 0, ',', '.') }}/USD</span>
                        @if(!empty($activeRate))
                            <span class="text-muted">&mdash; {{ $activeRate->source }} per {{ $activeRate->rate_date->format('d M Y') }}@if($activeRate->reference) ({{ $activeRate->reference }})@endif</span>
                        @endif
                        <a href="{{ route('admin.cost-settings.index') }}" class="btn btn-sm btn-outline-grab ms-auto"><i class="bi bi-pencil me-1"></i>Ubah kurs / pajak</a>
                    </div>
                    <p class="text-muted mb-0 mt-2" style="font-size:0.72rem;">
                        <i class="bi bi-info-circle me-1"></i>Angka pemakaian = data CloudWatch AWS (gabungan semua API key). Biaya = pemakaian × harga resmi AWS + PPN {{ round($taxRate * 100, 2) }}%. Bisa meleset ~5% dari tagihan final.
                    </p>
                </div>
            </div>
            @endif
        </div>

        {{-- Right Column --}}
        <div class="col-lg-4">
            {{-- Most Used API Keys (CloudWatch) --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-trophy"></i> {{ __('admin.most_used_apis') }}</div>
                    @if(empty($cw['by_key']))
                    <div class="text-center text-muted py-3" style="font-size:0.85rem;">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem;"></i>
                        {{ __('admin.no_data') }}
                    </div>
                    @else
                    @foreach(collect($cw['by_key'])->take(5) as $keyName => $count)
                    @php $comp = $companyByKey[$keyName] ?? null; @endphp
                    <div class="top-company-row">
                        <div class="top-rank {{ $loop->index === 0 ? 'r1' : ($loop->index === 1 ? 'r2' : 'r3') }}">{{ $loop->iteration }}</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.85rem;">
                                @if($comp)
                                    <i class="bi bi-building me-1" style="color:var(--grab-green); font-size:0.8rem;"></i>{{ $comp->name }}
                                @else
                                    <i class="bi bi-key me-1" style="color:var(--grab-green); font-size:0.8rem;"></i>{{ $keyName }}
                                @endif
                            </div>
                            <small class="text-muted" style="font-size:0.7rem;"><i class="bi bi-key"></i> {{ $keyName }}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold" style="font-size:0.9rem;">{{ number_format($count) }}</div>
                            <small class="text-muted">{{ __('admin.requests') }}</small>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>

            {{-- Budget Status (selaras AWS Budgets) --}}
            @php
                $spent = $grandCost;
                $rawPct = $budgetAlert > 0 ? ($spent / $budgetAlert) * 100 : 0;
                $barPct = min($rawPct, 100);
                $budgetColor = $spent >= $budgetAlert ? '#dc3545' : ($rawPct >= 80 ? '#f59e0b' : 'var(--grab-green)');
            @endphp
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-speedometer2"></i> {{ __('admin.budget_status') }}</div>
                    <div class="text-center mb-3">
                        <div class="fw-bold" style="font-size: 2rem; color: {{ $budgetColor }};">${{ number_format($spent, 2) }}</div>
                        <small class="text-muted">dari ambang AWS Budgets ${{ number_format($budgetAlert, 0) }}</small>
                    </div>
                    <div style="height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden;">
                        <div style="height:100%; width:{{ $barPct }}%; background:{{ $budgetColor }}; border-radius:5px; transition: width 0.5s;"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted" style="font-size:0.72rem;">$0</small>
                        <small style="font-size:0.72rem; font-weight:600; color:{{ $budgetColor }};">{{ number_format($rawPct, 0) }}%</small>
                        <small class="text-muted" style="font-size:0.72rem;">${{ number_format($budgetAlert, 0) }}</small>
                    </div>
                    @if($spent >= $budgetAlert)
                        <div class="mt-2 text-center" style="font-size:0.72rem; color:#dc3545; font-weight:600;"><i class="bi bi-exclamation-octagon-fill me-1"></i>Melebihi ambang peringatan</div>
                    @elseif($rawPct >= 80)
                        <div class="mt-2 text-center" style="font-size:0.72rem; color:#f59e0b; font-weight:600;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Mendekati ambang peringatan</div>
                    @endif
                    <a href="{{ route('admin.cost-settings.index') }}" class="d-block text-center mt-2 text-muted" style="font-size:0.72rem;">Ubah ambang budget</a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="section-title"><i class="bi bi-lightning"></i> {{ __('admin.quick_links') }}</div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('admin.companies.create') }}" class="quick-link">
                            <div class="ql-icon"><i class="bi bi-plus-lg"></i></div>
                            <div>
                                <div class="ql-text">{{ __('admin.add_company') }}</div>
                                <div class="ql-desc">{{ __('admin.add_company_desc') }}</div>
                            </div>
                        </a>
                        <a href="{{ route('admin.api-keys.index') }}" class="quick-link">
                            <div class="ql-icon"><i class="bi bi-key"></i></div>
                            <div>
                                <div class="ql-text">{{ __('admin.manage_api_keys') }}</div>
                                <div class="ql-desc">{{ __('admin.manage_api_keys_desc') }}</div>
                            </div>
                        </a>
                        <a href="{{ url('/') }}" class="quick-link" target="_blank">
                            <div class="ql-icon"><i class="bi bi-map"></i></div>
                            <div>
                                <div class="ql-text">{{ __('admin.open_homepage') }}</div>
                                <div class="ql-desc">{{ __('admin.open_homepage_desc') }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
