@extends('layouts.admin')

@section('title', 'Usage — ' . $company->name)

@push('styles')
    .key-status { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px; font-size:0.875rem; }
    .op-bar-track { height:8px; background:#eef2f6; border-radius:4px; overflow:hidden; }
    .op-bar-fill { height:100%; background:var(--grab-green); border-radius:4px; }
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
            {{ $company->name }} ({{ $company->slug }}) — data langsung dari AWS CloudWatch
        </p>
    </div>
</div>
@endsection

@section('content')
    {{-- API Key Status + freshness + refresh --}}
    <div class="card mb-4">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="fw-semibold mb-1">{{ __('admin.api_key_status') }}</h6>
                @if($keyName)
                    <span class="key-status bg-success bg-opacity-10 text-success">
                        <i class="bi bi-key-fill"></i> {{ $keyName }}
                    </span>
                @else
                    <span class="key-status bg-light text-muted"><i class="bi bi-dash-circle"></i> {{ __('admin.key_not_set') }}</span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($fetchedAt)
                    @php $stale = $fetchedAt->lt(now()->subDay()); @endphp
                    <span class="{{ $stale ? 'text-warning fw-semibold' : 'text-muted' }}" style="font-size:0.75rem;" title="Waktu data terakhir diambil dari AWS (tidak real-time)">
                        <i class="bi bi-clock-history"></i> Data per {{ $fetchedAt->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB{{ $stale ? ' — klik Refresh' : '' }}
                    </span>
                @endif
                @if($keyName)
                <a href="{{ route('admin.companies.invoice', ['company' => $company, 'start' => $startDate, 'end' => $endDate]) }}"
                    target="_blank" class="btn btn-sm btn-grab" title="Buka dokumen tagihan siap cetak / Simpan PDF"><i class="bi bi-file-earmark-pdf me-1"></i>Invoice / PDF</a>
                <a href="{{ route('admin.companies.usage', ['company' => $company, 'start' => $startDate, 'end' => $endDate, 'refresh' => 1]) }}"
                    class="btn btn-sm btn-outline-grab" title="Ambil data terbaru dari AWS"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</a>
                @endif
            </div>
        </div>
    </div>

    @if(!$keyName)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> Company ini belum punya API key AWS. Set dulu di <a href="{{ route('admin.companies.edit', $company) }}">edit company</a> supaya usage AWS bisa ditampilkan.</div>
    @elseif($metrics['error'])
        <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> Gagal ambil data AWS: {{ $metrics['error'] }}</div>
    @endif

    @php
        $pricing = \App\Services\AwsLocationService::PRICING;
        $ops = $metrics['operations'] ?? [];
        $opMax = !empty($ops) ? (max(array_values($ops)) ?: 1) : 1;
        $tax = $totalCost * $taxRate;
        $grand = $totalCost + $tax;
    @endphp

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: var(--grab-green-light); color: var(--grab-green);"><i class="bi bi-lightning-charge-fill"></i></div>
                <div><div class="stat-label">Total Request (periode)</div><div class="stat-value">{{ number_format($metrics['total'] ?? 0) }}</div></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: #fff3e0; color: #f59e0b;"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label">Estimasi Biaya + PPN</div>
                    <div class="stat-value" style="color:var(--grab-green);">${{ number_format($grand, 2) }}</div>
                    <small class="text-muted">≈ Rp {{ number_format($grand * $idrRate, 0, ',', '.') }}</small>
                </div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card"><div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: #eef2ff; color: #6366f1;"><i class="bi bi-calendar-range"></i></div>
                <div><div class="stat-label">Periode</div><div class="stat-value" style="font-size:1rem;">{{ \Carbon\Carbon::parse($startDate)->format('d M') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div></div>
            </div></div>
        </div>
    </div>

    {{-- Breakdown --}}
    @if(!empty($ops))
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-diagram-3 me-1 text-muted"></i> Rincian per Operasi</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0" style="font-size:0.85rem;">
                    <thead>
                        <tr style="border-bottom:2px solid #e2e8f0;">
                            <th style="width:220px;">Operasi</th><th>Pemakaian</th>
                            <th class="text-end" style="width:90px;">Request</th>
                            <th class="text-end" style="width:80px;">$/1k</th>
                            <th class="text-end" style="width:90px;">Est. Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ops as $opName => $opCount)
                            @php $rate = $pricing[$opName] ?? 0; $cost = ($opCount / 1000) * $rate; @endphp
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
                        <tr style="border-top:2px solid #e2e8f0;">
                            <td class="fw-semibold">Subtotal</td><td></td>
                            <td class="text-end fw-semibold">{{ number_format(array_sum($ops)) }}</td><td></td>
                            <td class="text-end fw-semibold">${{ number_format($totalCost, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">PPN {{ round($taxRate * 100, 2) }}%</td><td></td><td></td><td></td>
                            <td class="text-end text-muted">${{ number_format($tax, 2) }}</td>
                        </tr>
                        <tr style="border-top:1px solid #e2e8f0;">
                            <td class="fw-bold">Total + PPN</td><td></td><td></td><td></td>
                            <td class="text-end fw-bold" style="color: var(--grab-green); font-size:1rem;">
                                ${{ number_format($grand, 2) }}
                                <div class="fw-normal text-muted" style="font-size:0.72rem;">≈ Rp {{ number_format($grand * $idrRate, 0, ',', '.') }}</div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="text-muted mb-0 mt-2" style="font-size:0.72rem;">
                <i class="bi bi-info-circle me-1"></i>Angka pemakaian = data CloudWatch AWS. Biaya = pemakaian × harga resmi AWS, lalu ditambah PPN {{ round($taxRate * 100, 2) }}%. Bisa meleset ~5% dari tagihan final (CloudWatch menghitung sedikit beda dari sistem penagihan AWS).
            </p>
            <div class="d-flex align-items-center flex-wrap gap-2 mt-2 p-2 rounded-3" style="background:#f8f9fa; font-size:0.78rem;">
                <i class="bi bi-cash-coin text-muted"></i>
                <span class="text-muted">Kurs dipakai:</span>
                <span class="fw-semibold">Rp {{ number_format($idrRate, 0, ',', '.') }}/USD</span>
                @if(!empty($activeRate))
                    <span class="text-muted">&mdash; {{ $activeRate->source }} per {{ $activeRate->rate_date->format('d M Y') }}@if($activeRate->reference) ({{ $activeRate->reference }})@endif</span>
                @endif
                <a href="{{ route('admin.cost-settings.index') }}" class="btn btn-sm btn-outline-grab ms-auto"><i class="bi bi-pencil me-1"></i>Ubah kurs / pajak</a>
            </div>
        </div>
    </div>
    @elseif($keyName && !$metrics['error'])
        <div class="card mb-4"><div class="card-body text-center text-muted py-4">
            <i class="bi bi-inbox d-block" style="font-size:1.6rem;"></i>
            Belum ada data untuk periode ini. Klik <strong>Refresh</strong> untuk ambil dari AWS.
        </div></div>
    @endif

    {{-- Daily Chart --}}
    @if(!empty($metrics['daily']))
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-line me-1 text-muted"></i> Request per Hari</h6>
            @php
                $daily = $metrics['daily'];
                $maxCount = max(array_values($daily)) ?: 1;
                $allDays = [];
                $cur = \Carbon\Carbon::parse($startDate); $endP = \Carbon\Carbon::parse($endDate);
                while ($cur->lte($endP)) { $d = $cur->format('Y-m-d'); $allDays[$d] = $daily[$d] ?? 0; $cur->addDay(); }
            @endphp
            <div class="d-flex align-items-end gap-1" style="height:160px;">
                @foreach($allDays as $date => $count)
                    <div class="flex-fill d-flex flex-column justify-content-end" style="min-width:2px;" title="{{ \Carbon\Carbon::parse($date)->format('d M Y') }}: {{ number_format($count) }} request">
                        <div style="height: {{ max(($count / $maxCount) * 100, 1.5) }}%; background: var(--grab-green); border-radius:3px 3px 0 0;"></div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between mt-2">
                <small class="text-muted" style="font-size:0.72rem;">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</small>
                <small class="text-muted" style="font-size:0.72rem;">{{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</small>
            </div>
        </div>
    </div>
    @endif
@endsection
