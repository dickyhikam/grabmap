<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoiceNo }} — {{ $company?->name ?? $keyName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; color: #1a1a2e; background: #eef1f4; font-size: 13px; line-height: 1.5; }
        .toolbar { max-width: 800px; margin: 18px auto 0; display: flex; justify-content: space-between; gap: 10px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; border: 1px solid #cbd3da; background: #fff; color: #1a1a2e; text-decoration: none; font-weight: 600; font-size: 13px; cursor: pointer; }
        .btn-primary { background: #00b14f; border-color: #00b14f; color: #fff; }
        .sheet { max-width: 800px; margin: 14px auto 40px; background: #fff; padding: 44px 48px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); border-radius: 4px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #00b14f; padding-bottom: 20px; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: 700; color: #0d4a28; }
        .brand small { display: block; font-size: 11px; font-weight: 500; color: #6c757d; margin-top: 2px; }
        .logo { max-height: 52px; max-width: 160px; object-fit: contain; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 18px; letter-spacing: 0.06em; color: #1a1a2e; text-transform: uppercase; }
        .doc-title .no { font-size: 12px; color: #6c757d; font-weight: 600; margin-top: 4px; }
        .meta { display: flex; justify-content: space-between; gap: 24px; margin-bottom: 26px; }
        .meta .block { font-size: 12px; }
        .meta .label { text-transform: uppercase; letter-spacing: 0.05em; color: #8a94a0; font-size: 10px; font-weight: 700; margin-bottom: 4px; }
        .meta .v { font-weight: 600; }
        .meta .muted { color: #6c757d; font-weight: 400; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        thead th { background: #0d4a28; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; padding: 9px 10px; text-align: left; }
        thead th.r { text-align: right; }
        tbody td { padding: 9px 10px; border-bottom: 1px solid #eef1f4; font-size: 12px; }
        tbody td.r { text-align: right; font-variant-numeric: tabular-nums; }
        tbody tr:nth-child(even) { background: #fafbfc; }
        .op-name { font-weight: 600; }
        .op-desc { color: #8a94a0; font-size: 11px; }
        .totals { width: 320px; margin-left: auto; margin-top: 14px; }
        .totals .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .totals .row.sub { border-top: 1px solid #e2e8f0; }
        .totals .row.grand { border-top: 2px solid #0d4a28; margin-top: 4px; padding-top: 10px; font-weight: 700; font-size: 15px; color: #0d4a28; }
        .totals .muted { color: #6c757d; }
        .kurs { margin-top: 26px; background: #f6f8f9; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; }
        .kurs .label { text-transform: uppercase; letter-spacing: 0.05em; color: #8a94a0; font-size: 10px; font-weight: 700; margin-bottom: 6px; }
        .kurs table { width: auto; }
        .kurs td { border: none; padding: 2px 0; font-size: 12px; }
        .kurs td:first-child { color: #6c757d; padding-right: 18px; }
        .grand-idr { margin-top: 18px; background: #ecf9f1; border: 1px solid #b8e6c9; border-radius: 10px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
        .grand-idr .lbl { font-size: 12px; color: #0d4a28; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
        .grand-idr .amt { font-size: 22px; font-weight: 700; color: #0d4a28; font-variant-numeric: tabular-nums; }
        .note { margin-top: 22px; font-size: 11px; color: #8a94a0; line-height: 1.6; border-top: 1px dashed #d7dde2; padding-top: 14px; }
        .foot { margin-top: 28px; display: flex; justify-content: space-between; font-size: 11px; color: #8a94a0; }
        @media print {
            body { background: #fff; font-size: 12px; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; max-width: 100%; padding: 0; border-radius: 0; }
            @page { margin: 16mm; }
        }
    </style>
</head>
<body>
    @php
        $opLabels = [
            'GetMapTile' => 'Map tiles', 'GetTile' => 'Map tiles',
            'GetMapStyleDescriptor' => 'Map asset (style)', 'GetMapGlyphs' => 'Map asset (fonts)', 'GetMapSprites' => 'Map asset (icons)',
            'SearchText' => 'Address/place search (text)',
            'ReverseGeocode' => 'Reverse geocode (coordinates → address)',
            'Suggest' => 'Search suggestions (autocomplete)',
            'GetPlace' => 'Place details (POI)',
            'CalculateRoutes' => 'Route calculation', 'CalculateRouteMatrix' => 'Route matrix',
        ];
        $pricing = \App\Services\AwsLocationService::PRICING;
    @endphp

    <div class="toolbar">
        <a href="{{ $backUrl }}" class="btn">← Back to Usage</a>
        <button onclick="window.print()" class="btn btn-primary">🖨 Print / Save as PDF</button>
    </div>

    <div class="sheet">
        {{-- Header --}}
        <div class="head">
            <div>
                @if($company?->logo_path)
                    <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @else
                    <div class="brand">{{ config('app.name', 'GrabMaps') }}<small>AWS Location Service — Usage Billing</small></div>
                @endif
            </div>
            <div class="doc-title">
                <h1>Billing Statement</h1>
                <div class="no">{{ $invoiceNo }}</div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="meta">
            <div class="block">
                <div class="label">Billed to</div>
                <div class="v">{{ $company?->name ?? $keyName }}</div>
                @if($company)<div class="muted">Slug: {{ $company->slug }}</div>@endif
                @if($keyName)<div class="muted">API Key: {{ $keyName }}</div>@endif
            </div>
            <div class="block" style="text-align:right;">
                <div class="label">Billing Period</div>
                <div class="v">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
                <div class="muted">Issued: {{ $issuedAt->timezone('Asia/Jakarta')->format('d M Y') }}</div>
                @if($fetchedAt)<div class="muted">Data as of: {{ $fetchedAt->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</div>@endif
            </div>
        </div>

        @if(empty($operations))
            <p style="padding:30px 0; text-align:center; color:#8a94a0;">No usage data for this period.</p>
        @else
        {{-- Rincian --}}
        <table>
            <thead>
                <tr>
                    <th style="width:30px;">#</th>
                    <th>AWS Operation</th>
                    <th class="r" style="width:110px;">Requests</th>
                    <th class="r" style="width:120px;">Price /1,000</th>
                    <th class="r" style="width:120px;">Subtotal (USD)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($operations as $op => $count)
                    @php $rate = $pricing[$op] ?? 0; $cost = ($count / 1000) * $rate; @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="op-name">{{ $op }}</div>
                            <div class="op-desc">{{ $opLabels[$op] ?? '' }}</div>
                        </td>
                        <td class="r">{{ number_format($count) }}</td>
                        <td class="r">${{ number_format($rate, 2) }}</td>
                        <td class="r">${{ number_format($cost, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals USD --}}
        <div class="totals">
            <div class="row sub"><span class="muted">Subtotal</span><span>${{ number_format($subtotal, 2) }}</span></div>
            <div class="row"><span class="muted">VAT {{ round($taxRate * 100, 2) }}%</span><span>${{ number_format($tax, 2) }}</span></div>
            <div class="row grand"><span>Total (USD)</span><span>${{ number_format($grand, 2) }}</span></div>
        </div>

        {{-- Exchange rate / reference --}}
        <div class="kurs">
            <div class="label">Exchange Rate (reference)</div>
            <table>
                <tr><td>Rate</td><td><strong>Rp {{ number_format($idrRate, 0, ',', '.') }}</strong> / USD</td></tr>
                @if(!empty($activeRate))
                    <tr><td>Source</td><td>{{ $activeRate->source }}</td></tr>
                    <tr><td>Rate date</td><td>{{ $activeRate->rate_date->format('d M Y') }}</td></tr>
                    @if($activeRate->reference)<tr><td>Reference</td><td>{{ $activeRate->reference }}</td></tr>@endif
                    @if($activeRate->note)<tr><td>Note</td><td>{{ $activeRate->note }}</td></tr>@endif
                @endif
            </table>
        </div>

        {{-- IDR conversion --}}
        <div class="totals" style="margin-top:14px;">
            <div class="row"><span class="muted">Subtotal</span><span>Rp {{ number_format($subtotal * $idrRate, 0, ',', '.') }}</span></div>
            <div class="row"><span class="muted">VAT {{ round($taxRate * 100, 2) }}%</span><span>Rp {{ number_format($tax * $idrRate, 0, ',', '.') }}</span></div>
        </div>
        <div class="grand-idr">
            <span class="lbl">Total Amount Due</span>
            <span class="amt">Rp {{ number_format($grand * $idrRate, 0, ',', '.') }}</span>
        </div>

        <div class="note">
            <strong>Note:</strong> Charges are calculated from AWS Location Service usage during the period above
            (number of requests × official AWS pricing + VAT {{ round($taxRate * 100, 2) }}%), then converted to Rupiah using the exchange rate shown.
        </div>
        @endif

        <div class="foot">
            <span>Generated by {{ config('app.name', 'GrabMaps') }} — {{ $issuedAt->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</span>
            <span>{{ $invoiceNo }}</span>
        </div>
    </div>

    <script>
        // Auto-open the print dialog when opened with ?print=1
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.addEventListener('load', () => setTimeout(() => window.print(), 400));
        }
    </script>
</body>
</html>
