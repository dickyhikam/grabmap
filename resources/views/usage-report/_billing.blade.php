{{--
    Pemakaian menurut tagihan AWS (Cost Explorer per usage type) untuk perusahaan
    yang memakai akun AWS sendiri. $part memilih bagian yang dirender:
    alert · card (semua di bawah, satu kartu) · kpis · mix · daily · resources · table.
    Butuh: $billing, $metrics, $rp, $startDate, $endDate.
--}}
@php
    $sum        = $billing['summary'];
    $billCost   = $sum['total_cost'];
    $keyTotal   = (int) ($metrics['total'] ?? 0);
    $keyCost    = \App\Services\AwsLocationService::estimateCost($metrics['operations'] ?? []);
    // Tagihan jauh di atas hitungan per API key: sebagian besar pemakaian tidak lewat API key.
    $gap        = $billCost > 0 && $billCost > $keyCost * 1.2 + 0.01;
    $products   = __('awsconnect.products');
    $typeLabels = __('awsconnect.resource_types');
    $providerLabel = fn (?string $p) => $p ? (\App\Models\AwsUsageTypeDaily::PROVIDERS[$p] ?? $p) : '—';

    $rows      = $sum['rows'];
    $billed    = collect($rows)->filter(fn ($r) => $r['cost'] > 0)->values();
    $palette   = ['#00b14f', '#2f80ed', '#f5a623', '#8e5cf7', '#e8505b', '#14b8a6', '#94a3b8'];
    $colorOf   = fn ($i) => $palette[min($i, count($palette) - 1)];
    $icons     = ['Geocode' => 'bi-geo-alt-fill', 'ReverseGeocode' => 'bi-pin-map-fill', 'SearchText' => 'bi-search',
                  'Maps-Tiles' => 'bi-map-fill', 'GeofenceMonth' => 'bi-bounding-box', 'ResourceList' => 'bi-gear', 'ResourceCreateReadUpdateDelete' => 'bi-gear'];
    $iconOf    = fn ($p) => $icons[$p] ?? (str_starts_with($p, 'Places') ? 'bi-search' : (str_starts_with($p, 'Routes') ? 'bi-sign-turn-right' : 'bi-box'));

    // Provider dengan biaya terbesar.
    $byProvCost = collect($rows)->filter(fn ($r) => $r['provider'])->groupBy('provider')->map(fn ($g) => $g->sum('cost'))->sortDesc();
    $topProv    = $byProvCost->keys()->first() ?? ($billing['providers'][0] ?? null);
    $topProvPct = $billCost > 0 && $topProv ? ($byProvCost[$topProv] ?? 0) / $billCost * 100 : null;

    $topUse     = collect($rows)->sortByDesc('cost')->first();
    $coverage   = $billCost > 0 ? min(100, $keyCost / $billCost * 100) : null;

    // Grafik harian: setiap hari dalam rentang, termasuk yang kosong.
    $dailyBill = [];
    for ($d = \Carbon\Carbon::parse($startDate); $d->lte(\Carbon\Carbon::parse($endDate)); $d->addDay()) {
        $dailyBill[$d->format('Y-m-d')] = $sum['daily'][$d->format('Y-m-d')] ?? 0.0;
    }
    $dailyMax  = max(array_values($dailyBill) ?: [0]) ?: 1;
    $billDays  = count(array_filter($dailyBill));
    $avgDay    = $billDays ? $billCost / $billDays : 0;
    $usd2      = fn ($v) => '$' . number_format($v, $v > 0 && $v < 0.01 ? 4 : 2);
@endphp

@switch($part)
    @case('alert')
    @if($gap)
        <div class="bill-alert">
            <span class="bill-alert-ic"><i class="bi bi-receipt-cutoff"></i></span>
            <div>
                <div class="bill-alert-title">
                    {{ $keyTotal === 0 ? __('awsconnect.bill_gap_zero_title') : __('awsconnect.bill_gap_title') }}
                </div>
                <p class="bill-alert-body">
                    {{ __('awsconnect.bill_gap_body', ['bill' => '$' . number_format($billCost, 2), 'keys' => '$' . number_format($keyCost, 2)]) }}
                </p>
                <a href="#billing" class="bill-alert-link" data-no-loader>{{ __('awsconnect.bill_gap_link') }} <i class="bi bi-arrow-down"></i></a>
            </div>
        </div>
    @endif
        @break

    @case('kpis')
        {{-- Ringkasan cepat --}}
        <div class="bill-kpis">
            <div class="kpi">
                <div class="kpi-lb">{{ __('awsconnect.kpi_provider') }}</div>
                <div class="kpi-vl">{{ $topProv ? $providerLabel($topProv) : '—' }}</div>
                <div class="kpi-sb">{{ $topProvPct !== null ? __('awsconnect.kpi_share', ['pct' => number_format($topProvPct, 0)]) : __('awsconnect.kpi_no_provider') }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-lb">{{ __('awsconnect.kpi_top') }}</div>
                <div class="kpi-vl">{{ $topUse ? ($products[$topUse['product']] ?? $topUse['product']) : '—' }}</div>
                <div class="kpi-sb">
                    @if($topUse)
                        {{ number_format($topUse['quantity'], 0, ',', '.') }} {{ $topUse['unit'] !== 'N/A' ? $topUse['unit'] : '' }}
                    @endif
                </div>
            </div>
            <div class="kpi">
                <div class="kpi-lb">{{ __('awsconnect.kpi_coverage') }}</div>
                <div class="kpi-vl {{ $coverage !== null && $coverage < 50 ? 'warn' : '' }}">{{ $coverage !== null ? number_format($coverage, 0) . '%' : '—' }}</div>
                <div class="kpi-bar"><span style="width: {{ $coverage ?? 0 }}%;"></span></div>
                <div class="kpi-sb">{{ __('awsconnect.kpi_coverage_sub', ['keys' => $usd2($keyCost), 'bill' => $usd2($billCost)]) }}</div>
            </div>
            <div class="kpi">
                <div class="kpi-lb">{{ __('awsconnect.kpi_avg') }}</div>
                <div class="kpi-vl">{{ $usd2($avgDay) }}</div>
                <div class="kpi-sb">{{ __('awsconnect.kpi_avg_sub', ['n' => $billDays]) }}</div>
            </div>
        </div>
        @break

    @case('mix')
        {{-- Komposisi biaya per layanan --}}
        <div class="panel-h">{{ __('awsconnect.bill_mix') }}</div>
        @if($billed->isNotEmpty())
            <div class="mix-bar">
                @foreach($billed as $i => $row)
                    <span style="width: {{ max(1.5, $row['cost'] / max($billCost, 0.000001) * 100) }}%; background: {{ $colorOf($i) }};"
                          title="{{ $products[$row['product']] ?? $row['product'] }} · {{ $usd2($row['cost']) }}"></span>
                @endforeach
            </div>
            <div class="mix-legend">
                @foreach($billed as $i => $row)
                    <span><i style="background: {{ $colorOf($i) }};"></i>{{ $products[$row['product']] ?? $row['product'] }}
                        <b>{{ number_format($row['cost'] / max($billCost, 0.000001) * 100, 1, ',', '.') }}%</b></span>
                @endforeach
            </div>
        @else
            <div class="panel-empty">{{ __('awsconnect.bill_free') }}</div>
        @endif
        @break

    @case('daily')
        {{-- Biaya harian --}}
        <div class="panel-h">{{ __('awsconnect.bill_daily') }}</div>
        <div class="day-bars" style="--n: {{ count($dailyBill) }};">
            @foreach($dailyBill as $date => $cost)
                <span class="day {{ $cost > 0 ? '' : 'zero' }}" data-tip="{{ \Carbon\Carbon::parse($date)->translatedFormat('d M') }} · {{ $usd2($cost) }}">
                    <i style="height: {{ $cost > 0 ? max(4, $cost / $dailyMax * 100) : 0 }}%;"></i>
                </span>
            @endforeach
        </div>
        <div class="day-axis">
            <span>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M') }}</span>
            <span>{{ __('awsconnect.bill_daily_max', ['v' => $usd2($dailyMax)]) }}</span>
            <span>{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M') }}</span>
        </div>
        @break

    @case('resources')
        {{-- Resource Location di akun klien --}}
        <div class="bill-panel">
            <div class="panel-h">{{ __('awsconnect.bill_resources') }}</div>
            @forelse($billing['resources'] as $res)
                <div class="res-item">
                    <span class="res-ic"><i class="bi {{ ['place_indexes' => 'bi-search', 'maps' => 'bi-map', 'route_calculators' => 'bi-sign-turn-right', 'geofence_collections' => 'bi-bounding-box'][$res['type']] ?? 'bi-box' }}"></i></span>
                    <span style="flex:1;min-width:0;">
                        <span class="res-nm">{{ $res['name'] }}</span>
                        <span class="res-tp">{{ $typeLabels[$res['type']] ?? $res['type'] }}</span>
                    </span>
                    @if(!empty($res['data_source']))
                        <span class="prov-pill p-{{ strtolower($res['data_source']) }}">{{ $providerLabel($res['data_source']) }}</span>
                    @endif
                </div>
            @empty
                <div class="panel-empty">{{ __('awsconnect.bill_no_resources') }}</div>
            @endforelse
        </div>
        @break

    @case('table')
        @if($rows)
            {{-- Rincian per usage type --}}
            <div class="table-responsive">
                <table class="u-table bill-table">
                    <thead>
                        <tr>
                            <th>{{ __('awsconnect.bill_col_item') }}</th>
                            <th>{{ __('awsconnect.bill_col_provider') }}</th>
                            <th class="num">{{ __('awsconnect.bill_col_qty') }}</th>
                            <th class="share-col">{{ __('awsconnect.bill_col_share') }}</th>
                            <th class="num">USD</th>
                            <th class="num">{{ __('apikeys.cost_idr') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $pct = $billCost > 0 ? $row['cost'] / $billCost * 100 : 0;
                                $ci  = $billed->search(fn ($b) => $b['usage_type'] === $row['usage_type']);
                            @endphp
                            <tr>
                                <td>
                                    <div class="item">
                                        <span class="item-ic" style="--c: {{ $ci !== false ? $colorOf($ci) : 'var(--faint)' }};"><i class="bi {{ $iconOf($row['product']) }}"></i></span>
                                        <span style="min-width:0;">
                                            <span class="item-nm">{{ $products[$row['product']] ?? $row['product'] }}</span>
                                            <span class="ut-code">{{ $row['usage_type'] }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($row['provider'])
                                        <span class="prov-pill p-{{ strtolower($row['provider']) }}">{{ $providerLabel($row['provider']) }}</span>
                                        <span class="api-gen">API {{ $row['api'] }}</span>
                                    @else
                                        <span style="color:var(--faint);">—</span>
                                    @endif
                                </td>
                                <td class="num">
                                    {{ number_format($row['quantity'], $row['quantity'] < 10 && floor($row['quantity']) != $row['quantity'] ? 3 : 0, ',', '.') }}
                                    @if($row['unit'] && $row['unit'] !== 'N/A')<span class="unit">{{ $row['unit'] }}</span>@endif
                                </td>
                                <td class="share-col">
                                    <span class="share"><span style="width: {{ $pct }}%; background: {{ $ci !== false ? $colorOf($ci) : 'var(--line)' }};"></span></span>
                                    <span class="share-n">{{ number_format($pct, 1, ',', '.') }}%</span>
                                </td>
                                <td class="num">{{ $usd2($row['cost']) }}</td>
                                <td class="num idr-cell" data-idr="{{ $row['cost'] }}" data-idr-prefix="Rp ">{{ $rp($row['cost']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="font-weight:700;">{{ __('awsconnect.bill_total') }}</td>
                            <td class="num" style="font-weight:700;">{{ $usd2($billCost) }}</td>
                            <td class="num idr-cell" style="font-weight:700;" data-idr="{{ $billCost }}" data-idr-prefix="Rp ">{{ $rp($billCost) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
        <div class="panel-empty" style="margin-top:16px;">
            <i class="bi bi-receipt"></i> {{ $billing['error'] ? __('awsconnect.bill_unavailable') : __('awsconnect.bill_empty') }}
        </div>
        @endif
        @break

    @case('card')
        <section class="bill" id="billing">
        {{-- Kepala: total tagihan sebagai angka utama --}}
        <header class="bill-hero">
            <div class="bill-hero-tx">
                <div class="bill-eyebrow"><i class="bi bi-receipt"></i> {{ __('awsconnect.bill_eyebrow') }}</div>
                <h2 class="bill-title">{{ __('awsconnect.bill_title') }}</h2>
                <p class="bill-sub">{{ __('awsconnect.bill_sub') }}</p>
                @if($billing['providers'])
                    <div class="prov-row">
                        @foreach($billing['providers'] as $p)
                            <span class="prov-pill p-{{ strtolower($p) }}"><i class="bi bi-globe2"></i> {{ $providerLabel($p) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="bill-total">
                <div class="lb">{{ __('awsconnect.bill_total') }}</div>
                <div class="vl">{{ $usd2($billCost) }}</div>
                <div class="idr" data-idr="{{ $billCost }}" data-idr-prefix="≈ Rp ">≈ {{ $rp($billCost) }}</div>
            </div>
        </header>

            @if($rows)
                @include('usage-report._billing', ['part' => 'kpis'])
                <div class="bill-grid">
                    <div class="bill-panel">
                        @include('usage-report._billing', ['part' => 'mix'])
                        <div style="margin-top:20px;">@include('usage-report._billing', ['part' => 'daily'])</div>
                    </div>
                    <div class="bill-panel">@include('usage-report._billing', ['part' => 'resources'])</div>
                </div>
            @endif
            @include('usage-report._billing', ['part' => 'table'])

        <footer class="bill-foot">
            <i class="bi bi-info-circle"></i>
            <span>
                {{ __('awsconnect.bill_foot') }}
                @if($billing['synced_at'])
                    · {{ __('apikeys.fetched', ['time' => $billing['synced_at']->wib()->format('d M H:i')]) }}
                @endif
            </span>
        </footer>
        </section>
        @break
@endswitch

@once
@push('styles')
    .bill-alert {
        display: flex; gap: 14px; align-items: flex-start;
        background: var(--card); border-radius: var(--r-card); box-shadow: var(--shadow-card);
        border-left: 4px solid var(--warn-fg);
        padding: 16px 18px; margin-bottom: 16px;
    }
    .bill-alert-ic {
        width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--warn-soft); color: var(--warn-fg); font-size: 1rem;
    }
    .bill-alert-title { font-weight: 700; font-size: 0.88rem; color: var(--ink); }
    .bill-alert-body { margin: 4px 0 6px; font-size: 0.78rem; line-height: 1.55; color: var(--muted); }
    .bill-alert-link { font-size: 0.76rem; font-weight: 700; color: var(--green-text); text-decoration: none; }

    /* ---------- Bagian tagihan ---------- */
    .bill {
        margin-top: 16px; padding: 22px; border-radius: var(--r-card);
        background: var(--card); box-shadow: var(--shadow-card);
    }
    .bill-hero {
        position: relative; overflow: hidden;
        display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap;
        margin: -22px -22px 18px; padding: 24px 24px 20px;
        border-radius: var(--r-card) var(--r-card) 0 0;
        background:
            radial-gradient(120% 140% at 100% 0%, var(--green-soft) 0%, transparent 55%),
            radial-gradient(90% 120% at 0% 100%, var(--tone-indigo-bg) 0%, transparent 60%);
        border-bottom: 1px solid var(--line);
    }
    .bill-hero-tx { flex: 1; min-width: 260px; }
    .bill-eyebrow { display: inline-flex; align-items: center; gap: 6px; font-size: 0.66rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: var(--green-text); }
    .bill-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem; font-weight: 800; letter-spacing: -0.01em; color: var(--ink); margin: 6px 0 4px; }
    .bill-sub { font-size: 0.8rem; color: var(--muted); margin: 0 0 12px; line-height: 1.55; max-width: 560px; }
    .bill-total { text-align: right; }
    .bill-total .lb { font-size: 0.68rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .bill-total .vl { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.1rem; font-weight: 800; letter-spacing: -0.02em; color: var(--ink); line-height: 1.15; margin-top: 4px; font-variant-numeric: tabular-nums; }
    .bill-total .idr { font-size: 0.82rem; font-weight: 600; color: var(--muted); font-variant-numeric: tabular-nums; }
    @media (max-width: 620px) { .bill-total { text-align: left; } }

    .prov-row { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .prov-pill {
        display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 999px;
        font-size: 0.68rem; font-weight: 700; white-space: nowrap;
        background: var(--surface); color: var(--muted);
    }
    .prov-pill.p-grab { background: var(--green-soft); color: var(--green-text); }
    .prov-pill.p-esri, .prov-pill.p-here { background: var(--warn-soft); color: var(--warn-fg); }
    .prov-pill.p-default { background: var(--warn-soft); color: var(--warn-fg); }
    .api-gen { display: block; margin-top: 4px; font-size: 0.62rem; font-weight: 700; color: var(--faint); letter-spacing: 0.03em; }

    .bill-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
    @media (max-width: 960px) { .bill-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .kpi { background: var(--surface); border-radius: 16px; padding: 14px 16px; min-width: 0; }
    .kpi-lb { font-size: 0.64rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .kpi-vl { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.08rem; font-weight: 800; color: var(--ink); margin-top: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .kpi-vl.warn { color: var(--warn-fg); }
    .kpi-sb { font-size: 0.7rem; color: var(--muted); margin-top: 3px; }
    .kpi-bar { height: 5px; border-radius: 999px; background: var(--line); overflow: hidden; margin-top: 8px; }
    .kpi-bar span { display: block; height: 100%; border-radius: inherit; background: var(--green); }

    .bill-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 10px; margin-bottom: 16px; }
    @media (max-width: 860px) { .bill-grid { grid-template-columns: 1fr; } }
    .bill-panel { border: 1px solid var(--line); border-radius: 16px; padding: 16px; min-width: 0; }
    .panel-h { font-size: 0.72rem; font-weight: 700; color: var(--ink); margin-bottom: 10px; }
    .panel-empty { font-size: 0.76rem; color: var(--muted); padding: 10px 0; }

    .mix-bar { display: flex; gap: 3px; height: 14px; border-radius: 999px; overflow: hidden; }
    .mix-bar span { display: block; height: 100%; }
    .mix-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-top: 10px; font-size: 0.72rem; color: var(--muted); }
    .mix-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .mix-legend i { width: 9px; height: 9px; border-radius: 3px; }
    .mix-legend b { color: var(--ink); font-weight: 700; }

    .day-bars { display: grid; grid-template-columns: repeat(var(--n), minmax(0, 1fr)); gap: 3px; height: 96px; align-items: end; }
    .day-bars .day { height: 100%; display: flex; align-items: flex-end; border-radius: 4px; background: var(--surface); }
    .day-bars .day i { display: block; width: 100%; border-radius: 4px; background: linear-gradient(180deg, #2fd27a, #00b14f); transition: filter 0.15s; }
    .day-bars .day { position: relative; }
    .day-bars .day:hover i { filter: brightness(1.1); }
    /* Tooltip ringan tanpa skrip: tanggal + biaya hari itu. */
    .day-bars .day:hover::after {
        content: attr(data-tip); position: absolute; left: 50%; bottom: calc(100% + 6px); transform: translateX(-50%);
        padding: 5px 9px; border-radius: 8px; white-space: nowrap; pointer-events: none; z-index: 3;
        background: var(--ink); color: var(--card); font-size: 0.66rem; font-weight: 700;
    }
    .day-bars .day:first-child:hover::after { left: 0; transform: none; }
    .day-bars .day:last-child:hover::after { left: auto; right: 0; transform: none; }
    .day-axis { display: flex; justify-content: space-between; margin-top: 6px; font-size: 0.64rem; color: var(--faint); }

    .res-item { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); }
    .res-item:last-child { border-bottom: none; }
    .res-ic { width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--surface); color: var(--muted); font-size: 0.8rem; }
    .res-nm { display: block; font-family: ui-monospace, monospace; font-size: 0.74rem; font-weight: 600; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .res-tp { display: block; font-size: 0.66rem; color: var(--muted); margin-top: 1px; }

    .bill-table .item { display: flex; align-items: center; gap: 10px; }
    .item-ic {
        width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
        color: var(--c); background: color-mix(in srgb, var(--c) 14%, transparent);
    }
    .item-nm { display: block; font-weight: 600; }
    .ut-code { display: block; font-family: ui-monospace, monospace; font-size: 0.64rem; color: var(--faint); margin-top: 2px; }
    .bill-table .unit { margin-left: 4px; font-size: 0.66rem; color: var(--muted); }
    .share-col { min-width: 130px; white-space: nowrap; }
    .share { display: inline-block; width: 70px; height: 6px; border-radius: 999px; background: var(--surface); overflow: hidden; vertical-align: middle; }
    .share span { display: block; height: 100%; border-radius: inherit; }
    .share-n { margin-left: 8px; font-size: 0.7rem; color: var(--muted); font-variant-numeric: tabular-nums; }

    .bill-foot { display: flex; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line); font-size: 0.7rem; line-height: 1.5; color: var(--muted); }

    @media print {
        .bill { box-shadow: none; border: 1px solid #e6e9eb; }
        .bill-hero, .mix-bar span, .day-bars .day i, .kpi-bar span, .share span, .item-ic { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
@endpush
@endonce
