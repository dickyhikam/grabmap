@php
    // Sampul PDF pengajuan (dompdf): hanya tabel dan blok sederhana, tanpa flex/grid.
    $usd = fn ($v) => '$' . number_format((float) $v, 2, '.', ',');
    $rp  = fn ($v) => 'Rp ' . number_format(round((float) $v), 0, ',', '.');
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $paid = $claim->isPaid();
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<title>{{ $claim->code() }} · {{ $account->name }}</title>
<style>
    @page { margin: 34px 40px 50px; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #141b18; line-height: 1.45; }
    .muted { color: #8a938f; }
    .band { background: #067a3c; color: #fff; border-radius: 10px; padding: 18px 22px; }
    .band table { width: 100%; border-collapse: collapse; }
    .band .lbl { font-size: 8px; letter-spacing: 1.5px; text-transform: uppercase; color: #cdebd9; }
    .band .title { font-size: 17px; font-weight: bold; margin-top: 3px; }
    .band .sub { font-size: 9.5px; color: #e3f4ea; margin-top: 2px; }
    .band .code { font-size: 20px; font-weight: bold; text-align: right; }
    .pill { display: inline-block; border-radius: 10px; padding: 3px 9px; font-size: 8.5px; font-weight: bold; margin-top: 4px; }
    .pill.paid { background: #e8f8ef; color: #067a3c; }
    .pill.submitted { background: #eef0ff; color: #4f46e5; }

    .kpi { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 16px -8px 0; }
    .kpi td { background: #f2f4f4; border-radius: 8px; padding: 10px 12px; width: 25%; vertical-align: top; }
    .kpi .k { font-size: 8px; color: #8a938f; text-transform: uppercase; letter-spacing: 0.8px; }
    .kpi .v { font-size: 12px; font-weight: bold; margin-top: 3px; }
    .kpi .big { font-size: 15px; color: #067a3c; }

    h2 { font-size: 11.5px; margin: 22px 0 8px; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th { text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; color: #8a938f; border-bottom: 1.5px solid #dfe4e2; padding: 6px 6px; }
    table.grid td { padding: 7px 6px; border-bottom: 1px solid #ebeeee; vertical-align: top; }
    table.grid .r { text-align: right; white-space: nowrap; }
    table.grid .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }
    table.grid tfoot td { font-weight: bold; border-bottom: 0; border-top: 1.5px solid #141b18; }
    table.grid tfoot tr.grand td { font-size: 12px; color: #067a3c; border-top: 0; padding-top: 4px; }

    .note { background: #f2f4f4; border-radius: 8px; padding: 9px 12px; margin-top: 12px; }
    ol.att { margin: 0; padding-left: 16px; }
    ol.att li { padding: 2px 0; }
    .foot { position: fixed; bottom: -32px; left: 0; right: 0; font-size: 7.5px; color: #8a938f; border-top: 1px solid #ebeeee; padding-top: 5px; }
</style>
</head>
<body>
    <div class="foot">
        {{ __('awsclaims.pdf_footer', ['code' => $claim->code(), 'at' => now()->wib()->translatedFormat('d M Y, H:i'), 'by' => $printedBy ?? '—']) }}
    </div>

    <div class="band">
        <table>
            <tr>
                <td>
                    <div class="lbl">{{ __('awsclaims.pdf_label') }}</div>
                    <div class="title">{{ __('awsclaims.pdf_title') }}</div>
                    <div class="sub">{{ $account->name }}@if($account->account_number) · AWS {{ $account->account_number }}@endif</div>
                </td>
                <td style="text-align:right; vertical-align:top; padding-right:4px;">
                    <div class="code">{{ $claim->code() }}</div>
                    <span class="pill {{ $paid ? 'paid' : 'submitted' }}">{{ __('awsclaims.st_' . $claim->status) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="kpi">
        <tr>
            <td><div class="k">{{ __('awsclaims.line_total') }}</div><div class="v big">{{ $rp($claim->total_idr) }}</div></td>
            <td><div class="k">{{ __('awsclaims.pdf_period') }}</div><div class="v">{{ trans_choice('awsclaims.n_months', $claim->items->count(), ['n' => $claim->items->count()]) }}</div><div class="muted">{{ $claim->items->first()?->month->translatedFormat('M Y') }}@if($claim->items->count() > 1) – {{ $claim->items->last()->month->translatedFormat('M Y') }}@endif</div></td>
            <td><div class="k">{{ __('awsclaims.submitted_on') }}</div><div class="v">{{ $claim->submitted_on->translatedFormat('d M Y') }}</div>@if($claim->created_by)<div class="muted">{{ __('awsclaims.by', ['by' => $claim->created_by]) }}</div>@endif</td>
            <td><div class="k">{{ __('awsclaims.paid_on') }}</div><div class="v">{{ $claim->paid_on ? $claim->paid_on->translatedFormat('d M Y') : '—' }}</div></td>
        </tr>
    </table>

    <h2>{{ __('awsclaims.pdf_detail') }}</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('awsclaims.col_month') }}</th>
                <th>{{ __('awsclaims.col_invoice') }}</th>
                <th class="r">{{ __('awsclaims.col_usd') }}</th>
                <th class="r">{{ __('awsclaims.col_idr') }}</th>
                @if($claim->include_fee)<th class="r">{{ __('awsclaims.line_fee', ['pct' => $pct($claim->fee_percent)]) }}</th>@endif
                @if($claim->include_vat)<th class="r">{{ __('awsclaims.line_vat', ['pct' => $pct($claim->vat_rate * 100)]) }}</th>@endif
                <th class="r">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($claim->items as $it)
                @php $sum = $summaries->get($it->invoice_id); @endphp
                <tr>
                    <td><b>{{ $it->month->translatedFormat('F Y') }}</b></td>
                    <td>
                        <span class="mono">{{ $it->invoice_id ?? '—' }}</span>
                        @if($sum?->issued_at)<br><span class="muted">{{ __('awsbilling.bs_issued_on', ['date' => $sum->issued_at->wib()->translatedFormat('d M Y')]) }}</span>@endif
                    </td>
                    <td class="r">{{ $usd($it->usd) }}</td>
                    <td class="r">{{ $rp($it->aws_idr) }}</td>
                    @if($claim->include_fee)<td class="r">{{ $rp($it->fee_idr) }}</td>@endif
                    @if($claim->include_vat)<td class="r">{{ $rp($it->vat_idr) }}</td>@endif
                    <td class="r"><b>{{ $rp($it->total_idr) }}</b></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="r">{{ $usd($claim->total_usd) }}</td>
                <td class="r">{{ $rp($claim->aws_idr) }}</td>
                @if($claim->include_fee)<td class="r">{{ $rp($claim->fee_idr) }}</td>@endif
                @if($claim->include_vat)<td class="r">{{ $rp($claim->vat_idr) }}</td>@endif
                <td class="r">{{ $rp($claim->total_idr) }}</td>
            </tr>
        </tfoot>
    </table>
    <div class="muted" style="margin-top:6px;">{{ __('awsclaims.pdf_source') }}</div>

    @if($claim->note)
        <div class="note"><b>{{ __('awsclaims.note') }}:</b> {{ $claim->note }}</div>
    @endif

    <h2>{{ __('awsclaims.pdf_attachments') }}</h2>
    <ol class="att">
        @foreach($claim->items as $it)
            @if($it->invoice_id)
                <li>{{ __('awsclaims.pdf_att_line', ['invoice' => $it->invoice_id, 'month' => $it->month->translatedFormat('F Y')]) }}</li>
            @endif
        @endforeach
    </ol>
    <div class="muted" style="margin-top:4px;">{{ __('awsclaims.pdf_att_note') }}</div>

</body>
</html>
