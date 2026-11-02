{{--
    Laporan untuk perusahaan yang memakai akun AWS sendiri (biaya dari tagihan
    Cost Explorer). Semua angka utamanya datang dari tagihan, bukan dari API key,
    jadi tata letaknya disusun ulang di sekitar tagihan itu. Variabel dari show.blade.php.
--}}
@php
    $providerLabel = fn (?string $p) => $p ? (\App\Models\AwsUsageTypeDaily::PROVIDERS[$p] ?? $p) : '—';
    $perKey = collect($usage['per_key'] ?? [])->filter(fn ($k) => $k['has_data']);
@endphp

<section class="ca">
    {{-- ===================== Kepala: siapa, kapan, berapa ===================== --}}
    <div class="ca-hero">
        <div class="ca-top">
            <div class="ca-id">
                <div class="ca-logo">
                    @if($assignedCompany?->logo_path)
                        <img src="{{ asset($assignedCompany->logo_path) }}" alt="{{ $assignedCompany->name }}">
                    @else
                        <i class="bi bi-buildings"></i>
                    @endif
                </div>
                <div style="min-width:0;">
                    <div class="ca-eyebrow">{{ __('awsconnect.ca_eyebrow') }}</div>
                    <h1 class="ca-name">{{ $reportTitle }}</h1>
                </div>
            </div>

            <div class="ca-actions">
                <a class="ca-act" data-export data-no-loader
                   href="{{ route('usage-report.export', array_filter([
                       'token' => $share->share_token, 'start' => $startDate, 'end' => $endDate,
                       'key' => $activeKey ?? null, 'lang' => app()->getLocale(),
                   ])) }}">
                    <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('apikeys.share_export') }}
                </a>
                <button type="button" class="ca-act" onclick="window.print()">
                    <i class="bi bi-printer"></i> {{ __('apikeys.share_print') }}
                </button>
            </div>
        </div>

        <div class="ca-chips">
            <span><i class="bi bi-calendar3"></i> {{ $rangeLabel }}</span>
            <span><i class="bi bi-clock"></i> {{ __('apikeys.share_days', ['count' => $days]) }}</span>
            @foreach($billing['providers'] as $p)
                <span><i class="bi bi-globe2"></i> {{ $providerLabel($p) }}</span>
            @endforeach
            @if($billing['synced_at'])
                <span @class(['stale' => $billing['synced_at']->lt(now()->subDay())])>
                    <i class="bi bi-arrow-repeat"></i> {{ __('apikeys.fetched', ['time' => $billing['synced_at']->wib()->format('d M H:i')]) }}
                </span>
            @endif
        </div>

        <div class="ca-money">
            <div class="ca-grand">
                <div class="lb">{{ $sc['active'] ? __('apikeys.grand_total') : __('apikeys.total_vat') }}</div>
                @if($sc['active'])
                    <div class="vl" data-idr data-idr-role="grand" data-idr-prefix="Rp ">{{ $rpIdr($sc['grand_idr']) }}</div>
                    <div class="sb">AWS + {{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }} {{ $usd($totalVat) }} + {{ __('servicecharge.line') }}</div>
                @else
                    <div class="vl">{{ $usd($totalVat) }}</div>
                    <div class="sb" data-idr="{{ $totalVat }}">≈ {{ $rp($totalVat) }}</div>
                @endif
                <div class="ca-src"><i class="bi bi-receipt"></i> {{ __('awsconnect.cost_from_bill') }}</div>
            </div>

            <div class="ca-break">
                <span class="k">AWS · {{ __('awsconnect.cost_bill_short') }}</span>
                <span class="n">{{ $usd($totalCost) }}</span>
                <span class="n idr" data-idr="{{ $totalCost }}" data-idr-prefix="Rp ">{{ $rp($totalCost) }}</span>

                <span class="k">+ {{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }}</span>
                <span class="n">{{ $usd($tax) }}</span>
                <span class="n idr" data-idr="{{ $tax }}" data-idr-prefix="Rp ">{{ $rp($tax) }}</span>

                @if($sc['active'])
                    <span class="k">
                        + {{ __('servicecharge.line') }}
                        @if($scLabels)
                            @foreach($scLabels as $basis => $label)
                                <small data-sc-basis="{{ $basis }}" @if($sc['basis'] !== $basis) hidden @endif>({{ $label }})</small>
                            @endforeach
                        @endif
                    </span>
                    <span class="n">—</span>
                    <span class="n idr" data-idr data-idr-role="sc" data-idr-prefix="Rp ">{{ $rpIdr($sc['charge_idr']) }}</span>
                @endif
            </div>
        </div>
    </div>

    @if(empty($billing['summary']['rows']))
        <div class="q-alert">
            <span class="q-alert-icon"><i class="bi bi-info-circle-fill"></i></span>
            <div class="q-alert-body">{{ $billing['error'] ? __('awsconnect.bill_unavailable') : __('awsconnect.bill_empty') }}</div>
        </div>
    @else
        {{-- ===================== Ringkasan cepat ===================== --}}
        @include('usage-report._billing', ['part' => 'kpis'])

        {{-- ===================== Grafik harian + kurs & komposisi ===================== --}}
        <div class="ca-grid">
            <div class="q-card ca-chart">
                <div class="q-card-head">
                    <div>
                        <div class="q-card-title">{{ __('awsconnect.bill_daily') }}</div>
                        <div class="q-card-sub">{{ __('awsconnect.ca_daily_sub', ['range' => $rangeLabel]) }}</div>
                    </div>
                </div>
                @include('usage-report._billing', ['part' => 'daily'])
            </div>

            <div class="ca-side">
                @include('usage-report._rate-card')
                <div class="q-card">
                    @include('usage-report._billing', ['part' => 'mix'])
                </div>
            </div>
        </div>

        {{-- ===================== Rincian tagihan ===================== --}}
        <div class="q-card">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('awsconnect.ca_table_title') }}</div>
                    <div class="q-card-sub">{{ __('awsconnect.bill_sub') }}</div>
                </div>
            </div>
            @include('usage-report._billing', ['part' => 'table'])
        </div>

        {{-- ===================== Resource & API key ===================== --}}
        <div class="ca-grid even">
            <div class="q-card">
                @include('usage-report._billing', ['part' => 'resources'])
            </div>

            <div class="q-card">
                <div class="panel-h">{{ __('awsconnect.ca_keys_title') }}</div>
                @forelse($perKey as $key)
                    <div class="res-item">
                        <span class="res-ic"><i class="bi bi-key-fill"></i></span>
                        <span style="flex:1;min-width:0;">
                            <span class="res-nm">{{ $key['name'] }}</span>
                            <span class="res-tp">{{ number_format($key['total'], 0, ',', '.') }} {{ __('apikeys.requests') }}</span>
                        </span>
                        <span class="ca-keycost">{{ $usd($key['cost']) }}</span>
                    </div>
                @empty
                    <div class="ca-nokeys">
                        <i class="bi bi-key"></i>
                        <span>{{ __('awsconnect.ca_no_keys') }}</span>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <div class="ca-foot">
        <i class="bi bi-info-circle"></i>
        <span>{{ __('awsconnect.bill_foot') }}</span>
    </div>
</section>

@push('styles')
    .ca { display: flex; flex-direction: column; gap: 16px; }

    /* ---------- Kepala ---------- */
    .ca-hero {
        position: relative; overflow: hidden; color: #fff;
        border-radius: var(--r-card); padding: 24px 26px;
        background:
            radial-gradient(110% 120% at 100% 0%, rgba(255, 255, 255, 0.16) 0%, transparent 45%),
            radial-gradient(80% 140% at 0% 100%, rgba(0, 0, 0, 0.22) 0%, transparent 55%),
            linear-gradient(135deg, #00b14f 0%, #058a43 50%, #04612f 100%);
        box-shadow: 0 22px 44px -24px rgba(0, 110, 50, 0.6);
    }
    .ca-hero::after {
        content: ''; position: absolute; right: -80px; bottom: -110px; width: 320px; height: 320px; border-radius: 50%;
        border: 46px solid rgba(255, 255, 255, 0.06); pointer-events: none;
    }
    .ca-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; position: relative; z-index: 1; }
    .ca-id { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .ca-logo {
        width: 56px; height: 56px; border-radius: 17px; flex-shrink: 0; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: #fff; color: #046c35; font-size: 1.4rem;
        box-shadow: 0 6px 16px -6px rgba(0, 0, 0, 0.35);
    }
    .ca-logo img { width: 100%; height: 100%; object-fit: contain; padding: 7px; }
    .ca-eyebrow { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.09em; text-transform: uppercase; opacity: 0.82; }
    .ca-name { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.55rem; font-weight: 800; letter-spacing: -0.02em; margin: 3px 0 0; color: #fff; }
    .ca-actions { display: flex; gap: 8px; }
    .ca-act {
        display: inline-flex; align-items: center; gap: 7px;
        border: none; border-radius: 999px; padding: 9px 16px;
        background: rgba(255, 255, 255, 0.16); color: #fff; text-decoration: none;
        font-size: 0.78rem; font-weight: 700; font-family: inherit; cursor: pointer;
        backdrop-filter: blur(6px); transition: background 0.15s;
    }
    .ca-act:hover { background: rgba(255, 255, 255, 0.28); color: #fff; }

    .ca-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 16px; position: relative; z-index: 1; }
    .ca-chips span {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 11px; border-radius: 999px;
        background: rgba(255, 255, 255, 0.14); font-size: 0.72rem; font-weight: 600;
    }
    .ca-chips span.stale { background: rgba(255, 196, 0, 0.28); }

    .ca-money {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: end;
        margin-top: 22px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.18);
        position: relative; z-index: 1;
    }
    @media (max-width: 760px) { .ca-money { grid-template-columns: 1fr; } }
    .ca-grand .lb { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.8; }
    .ca-grand .vl { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.5rem; font-weight: 800; letter-spacing: -0.03em; line-height: 1.1; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .ca-grand .sb { font-size: 0.8rem; opacity: 0.85; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .ca-src {
        display: inline-flex; align-items: center; gap: 6px; margin-top: 12px;
        padding: 4px 11px; border-radius: 999px; background: rgba(255, 255, 255, 0.16);
        font-size: 0.7rem; font-weight: 700;
    }
    .ca-break {
        display: grid; grid-template-columns: 1fr auto auto; gap: 8px 18px; align-items: baseline;
        background: rgba(0, 0, 0, 0.14); border-radius: 18px; padding: 14px 18px;
        font-size: 0.8rem;
    }
    .ca-break .k { opacity: 0.88; }
    .ca-break .k small { opacity: 0.8; margin-left: 4px; }
    .ca-break .n { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ca-break .n.idr { opacity: 0.85; font-weight: 600; }

    /* ---------- Isi ---------- */
    .ca .bill-kpis { margin-bottom: 0; }
    .ca .kpi { background: var(--card); box-shadow: var(--shadow-card); padding: 16px 18px; }
    .ca-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(300px, 1fr); gap: 16px; align-items: start; }
    .ca-grid.even { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    @media (max-width: 960px) { .ca-grid, .ca-grid.even { grid-template-columns: 1fr; } }
    .ca-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .ca-side .rate-card { margin-bottom: 0; }
    .ca-side .rate-foot { flex-wrap: wrap; }
    .ca-side .rate-foot .hint { flex: 1 0 100%; order: 3; margin-top: 3px; }
    .ca .q-card { margin-bottom: 0; }

    /* Grafik harian lebih tinggi di layout ini, dengan garis bantu. */
    .ca-chart .day-bars {
        height: 230px; padding-top: 4px;
        background: repeating-linear-gradient(to top, var(--line) 0 1px, transparent 1px 25%);
    }
    .ca-chart .day-bars .day { background: transparent; }

    .ca-keycost { font-size: 0.78rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .ca-nokeys {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 12px 14px; border-radius: 14px; background: var(--surface);
        font-size: 0.76rem; line-height: 1.55; color: var(--muted);
    }

    .ca-foot { display: flex; gap: 8px; font-size: 0.7rem; line-height: 1.5; color: var(--muted); padding: 0 4px; }

    @media print {
        .ca-hero { -webkit-print-color-adjust: exact; print-color-adjust: exact; box-shadow: none; }
        .ca-actions { display: none; }
        .ca .kpi { box-shadow: none; border: 1px solid #e6e9eb; }
    }
@endpush
