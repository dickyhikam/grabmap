@extends('layouts.admin-v2')

@section('title', __('awsclaims.title') . ' · ' . $account->name)

@php
    $usd = fn ($v) => '$' . number_format((float) $v, 2, '.', ',');
    $rp  = fn ($v) => 'Rp ' . number_format(round((float) $v), 0, ',', '.');
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');

    $rowsC = collect($rows);
    $ready = $rowsC->where('status', 'unclaimed');
    $pendingRow = $rowsC->firstWhere('status', 'pending');
    $submitted = $claims->where('status', \App\Models\AwsBillClaim::SUBMITTED);
    $paid = $claims->where('status', \App\Models\AwsBillClaim::PAID);
    $hasInvoices = $rowsC->contains(fn ($r) => $r['invoice']);
    $openNew = $errors->hasAny(['months', 'fee_percent', 'status', 'submitted_on', 'paid_on', 'note']);

    // Bar progres: porsi Rupiah tiap status dari semua yang tercatat.
    $amt = [
        'unclaimed' => (float) $ready->sum(fn ($r) => $r['invoice']->idrAmount()),
        'submitted' => (float) $submitted->sum('total_idr'),
        'paid'      => (float) $paid->sum('total_idr'),
    ];
    $amtAll = array_sum($amt) ?: 1;

    // Strip bulan: terlama di kiri, terbaru di kanan, maksimal 12.
    $strip = $rowsC->reverse()->take(-12)->values();

    // Teks untuk JS. Disusun di sini karena @json tidak tahan tanda kurung di dalam teks.
    $jsText = [
        'selected' => __('awsclaims.selected'), 'loading' => __('awsclaims.docs_loading'), 'pdf' => __('awsclaims.docs_pdf'),
        'supp' => __('awsclaims.docs_supp'), 'noSupp' => __('awsclaims.docs_none_supp'), 'copied' => __('awsclaims.copied'),
        'paidTitle' => __('awsclaims.paid_modal_title'), 'fee' => __('awsclaims.line_fee'), 'vat' => __('awsclaims.line_vat'),
        'pdfBusy' => __('awsclaims.pdf_busy'), 'pdfPartial' => __('awsclaims.pdf_partial'), 'pdfFailed' => __('awsclaims.pdf_failed'),
    ];
    $oldMonths = old('months', []);

    // Riwayat aktivitas: kalimat utama + keterangan kecil.
    $actIcon = ['created' => 'bi-send-fill', 'created_paid' => 'bi-check-circle-fill', 'paid' => 'bi-check-circle-fill', 'unpaid' => 'bi-arrow-counterclockwise', 'cancelled' => 'bi-x-circle-fill'];
    $actTone = ['created' => 'indigo', 'created_paid' => 'green', 'paid' => 'green', 'unpaid' => 'amber', 'cancelled' => 'red'];
    $actMeta = function ($a) use ($rp) {
        $m = $a->meta ?? [];
        $bits = [];
        if (!empty($m['months'])) {
            $bits[] = collect($m['months'])->map(fn ($x) => \Carbon\Carbon::createFromFormat('Y-m-d', $x . '-01')->translatedFormat('M Y'))->implode(', ')
                . (isset($m['total']) ? ' · ' . $rp($m['total']) : '');
        }
        if (!empty($m['paid_on'])) {
            $bits[] = __('awsclaims.act_paid_on', ['date' => \Carbon\Carbon::parse($m['paid_on'])->translatedFormat('d M Y')]);
        }
        if (!empty($m['proof'])) {
            $bits[] = __('awsclaims.act_proof', ['name' => $m['proof']]);
        }

        return implode(' · ', $bits);
    };
    $actByClaim = $activities->whereNotNull('aws_bill_claim_id')->groupBy('aws_bill_claim_id');

    // Rekap TSV per pengajuan, siap tempel ke spreadsheet atau email.
    $tsv = function ($c) {
        $n = fn ($v) => (string) round((float) $v);
        $lines = [__('awsclaims.tsv_head')];
        foreach ($c->items as $it) {
            $lines[] = implode("\t", [$it->month->translatedFormat('F Y'), $it->invoice_id, number_format($it->usd, 2, '.', ''), $n($it->aws_idr), $n($it->fee_idr), $n($it->vat_idr), $n($it->total_idr)]);
        }
        $lines[] = implode("\t", [__('awsclaims.tsv_total'), '', number_format($c->total_usd, 2, '.', ''), $n($c->aws_idr), $n($c->fee_idr), $n($c->vat_idr), $n($c->total_idr)]);

        return implode("\n", $lines);
    };
@endphp

@push('styles')
    .cl-back { display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 600; color: var(--muted); text-decoration: none; margin-bottom: 6px; }
    .cl-back:hover { color: var(--green-text); }
    .cl-lead { font-size: 0.8rem; color: var(--muted); margin: -4px 0 22px; max-width: 720px; line-height: 1.5; }

    /* ---------- Hero ---------- */
    .cl-hero {
        position: relative; overflow: hidden; color: #fff; border-radius: 28px; padding: 28px 30px 24px; margin-bottom: 26px;
        background:
            radial-gradient(110% 140% at 100% 0%, rgba(255,255,255,0.16) 0%, transparent 45%),
            linear-gradient(140deg, #0b8f45 0%, #067a3c 45%, #04552a 100%);
        box-shadow: 0 18px 40px rgba(4, 85, 42, 0.28);
    }
    .cl-hero::before { content: ''; position: absolute; inset: 0; pointer-events: none; opacity: 0.08; background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 18px 18px; }
    .cl-hero > * { position: relative; }
    .h-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
    .h-lbl { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.75); }
    .h-val { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; letter-spacing: -0.035em; font-size: clamp(2.1rem, 4.6vw, 3.1rem); line-height: 1.05; margin: 10px 0 4px; font-variant-numeric: tabular-nums; }
    .h-sub { font-size: 0.8rem; color: rgba(255,255,255,0.78); }
    .h-cta { display: inline-flex; align-items: center; gap: 8px; border: 0; border-radius: 999px; padding: 12px 20px; background: #fff; color: #05622f; font-weight: 800; font-size: 0.84rem; box-shadow: 0 8px 22px rgba(0,0,0,0.18); cursor: pointer; transition: transform 0.15s; }
    .h-cta:hover { transform: translateY(-1px); }
    .h-cta:disabled { opacity: 0.5; cursor: default; transform: none; }

    .h-bar { display: flex; gap: 4px; height: 10px; margin: 24px 0 12px; }
    .h-bar span { border-radius: 999px; min-width: 6px; }
    .h-bar .unclaimed { background: #fcd34d; }
    .h-bar .submitted { background: #c7d2fe; }
    .h-bar .paid { background: rgba(255,255,255,0.9); }
    .h-leg { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 640px) { .h-leg { grid-template-columns: 1fr; } }
    .h-leg > div { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.14); border-radius: 16px; padding: 11px 14px; }
    .h-leg .k { display: flex; align-items: center; gap: 7px; font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.75); }
    .h-leg .k i { width: 8px; height: 8px; border-radius: 50%; }
    .h-leg .v { font-weight: 800; font-size: 1rem; margin-top: 3px; font-variant-numeric: tabular-nums; }
    .h-leg .s { font-size: 0.66rem; color: rgba(255,255,255,0.6); }

    .h-strip { display: flex; gap: 6px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.14); overflow-x: auto; scrollbar-width: none; }
    .h-strip::-webkit-scrollbar { display: none; }
    .h-m { flex: 1 0 58px; text-align: center; border-radius: 12px; padding: 7px 4px 8px; background: rgba(255,255,255,0.06); text-decoration: none; color: #fff; }
    .h-m .mo { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }
    .h-m .yr { font-size: 0.58rem; color: rgba(255,255,255,0.55); }
    .h-m .dot { display: block; width: 8px; height: 8px; border-radius: 50%; margin: 6px auto 0; background: rgba(255,255,255,0.25); }
    .h-m.unclaimed .dot { background: #fcd34d; box-shadow: 0 0 0 3px rgba(252,211,77,0.25); }
    .h-m.submitted .dot { background: #c7d2fe; }
    .h-m.paid .dot { background: #fff; }
    .h-m.pending { opacity: 0.55; }
    .h-m:hover { background: rgba(255,255,255,0.14); color: #fff; }

    /* ---------- Tata letak dua kolom ---------- */
    .cl-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 22px; align-items: start; }
    @media (max-width: 1100px) { .cl-grid { grid-template-columns: minmax(0, 1fr); } }
    .sec-h { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin-bottom: 12px; }
    .sec-t { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.12rem; letter-spacing: -0.02em; display: flex; align-items: center; gap: 8px; }
    .sec-t .n { font-size: 0.7rem; font-weight: 800; border-radius: 999px; padding: 2px 9px; background: var(--card); color: var(--muted); box-shadow: var(--shadow-card); }
    .sec-s { font-size: 0.74rem; color: var(--muted); margin-top: 2px; }
    .sec-act { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .btn-solid.sm { padding: 7px 14px; font-size: 0.74rem; }
    .btn-solid:disabled { opacity: 0.45; box-shadow: none; cursor: default; }
    .link-btn { border: 0; background: none; color: var(--green-text); font-size: 0.74rem; font-weight: 700; padding: 0; cursor: pointer; white-space: nowrap; }

    /* ---------- Kartu bulan siap diajukan ---------- */
    .ms { display: flex; flex-direction: column; gap: 10px; }
    .m-card {
        position: relative; display: flex; align-items: center; gap: 14px; padding: 14px 16px; border-radius: 20px; background: var(--card); margin: 0;
        box-shadow: var(--shadow-card); border: 1.5px solid transparent; cursor: pointer; transition: border-color 0.15s, background 0.15s, transform 0.15s;
    }
    .m-card:hover { transform: translateY(-1px); }
    .m-card input { position: absolute; opacity: 0; pointer-events: none; }
    .m-card.sel { border-color: var(--green); background: color-mix(in srgb, var(--green-soft) 60%, var(--card)); }
    .m-chk { width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--line); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.8rem; transition: all 0.15s; }
    .m-card.sel .m-chk { background: var(--green); border-color: var(--green); }
    .m-chk i { opacity: 0; }
    .m-card.sel .m-chk i { opacity: 1; }
    .m-cal { width: 50px; flex-shrink: 0; border-radius: 14px; overflow: hidden; text-align: center; background: var(--surface); }
    .m-cal .mo { background: var(--warn-fg); color: #fff; font-size: 0.6rem; font-weight: 800; letter-spacing: 0.06em; padding: 3px 0; text-transform: uppercase; }
    .m-cal .yr { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 0.82rem; padding: 4px 0 5px; }
    .m-card.sel .m-cal .mo { background: var(--green); }
    .m-body { flex: 1; min-width: 0; }
    .m-name { font-weight: 800; font-size: 0.9rem; }
    .m-inv { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 3px; font-family: ui-monospace, monospace; font-size: 0.7rem; color: var(--muted); }
    .m-amt { text-align: right; flex-shrink: 0; }
    .m-amt .v { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .m-amt .s { font-size: 0.68rem; color: var(--faint); font-variant-numeric: tabular-nums; }
    .m-card.pending { cursor: default; opacity: 0.75; box-shadow: none; background: transparent; border: 1.5px dashed var(--line); }
    .m-card.pending:hover { transform: none; }
    .m-card.pending .m-cal .mo { background: var(--faint); }

    .doc-btn { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; border: 0; background: var(--surface); color: var(--green-text); font: 700 0.66rem/1.6 Inter, sans-serif; cursor: pointer; }
    .doc-btn:hover { background: var(--green-soft); }

    .empty { text-align: center; padding: 36px 20px; border-radius: 22px; border: 1.5px dashed var(--line); color: var(--muted); font-size: 0.8rem; line-height: 1.5; }
    .empty i { display: block; font-size: 1.8rem; color: var(--green-text); margin-bottom: 8px; }

    /* ---------- Kartu pengajuan ---------- */
    .cs { display: flex; flex-direction: column; gap: 14px; }
    .c-card { position: relative; background: var(--card); border-radius: 24px; box-shadow: var(--shadow-card); padding: 20px 22px 18px 26px; overflow: hidden; }
    .c-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--tone); }
    .c-card.submitted { --tone: #6366f1; }
    .c-card.paid { --tone: var(--green); }
    .c-head { display: flex; align-items: center; gap: 10px; }
    .c-code { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 0.95rem; }
    .c-by { font-size: 0.68rem; color: var(--faint); }
    .pill { margin-left: auto; display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 4px 11px; font-size: 0.7rem; font-weight: 800; white-space: nowrap; }
    .pill.submitted { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
    .pill.paid { background: var(--green-soft); color: var(--green-text); }
    .c-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; margin-top: 12px; flex-wrap: wrap; }
    .c-total { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.7rem; letter-spacing: -0.03em; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .c-total small { display: block; font-family: Inter, sans-serif; font-size: 0.68rem; font-weight: 600; color: var(--muted); letter-spacing: 0; margin-top: 3px; }

    .stp { display: flex; align-items: flex-start; min-width: 220px; }
    .stp .st { text-align: center; font-size: 0.64rem; color: var(--muted); width: 82px; }
    .stp .st b { display: block; font-size: 0.68rem; color: var(--ink); margin-top: 4px; white-space: nowrap; }
    .stp .st i { display: inline-flex; width: 24px; height: 24px; border-radius: 50%; align-items: center; justify-content: center; font-size: 0.72rem; background: var(--surface); color: var(--faint); }
    .stp .st.on i { background: var(--tone); color: #fff; box-shadow: 0 0 0 4px color-mix(in srgb, var(--tone) 18%, transparent); }
    .stp .ln { flex: 1; height: 2px; margin-top: 11px; background: var(--line); border-radius: 2px; min-width: 24px; }
    .stp .ln.on { background: var(--tone); }

    .c-months { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 16px; }
    .c-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 5px 4px 11px; border-radius: 12px; background: var(--surface); font-size: 0.72rem; font-weight: 700; }

    .c-det { margin-top: 14px; border-top: 1px solid var(--line); padding-top: 10px; }
    .c-det summary { list-style: none; cursor: pointer; font-size: 0.74rem; font-weight: 700; color: var(--muted); display: inline-flex; align-items: center; gap: 6px; }
    .c-det summary::-webkit-details-marker { display: none; }
    .c-det summary i { transition: transform 0.2s; }
    .c-det[open] summary i { transform: rotate(90deg); }
    .c-lines { margin: 8px 0 0; padding: 0; list-style: none; font-size: 0.78rem; }
    .c-lines li { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; }
    .c-lines li span { color: var(--muted); }
    .c-lines li small { color: var(--faint); font-family: ui-monospace, monospace; }
    .c-lines li b { font-variant-numeric: tabular-nums; }
    .c-lines li.tot { border-top: 1px dashed var(--line); margin-top: 4px; padding-top: 9px; }
    .c-lines li.tot.last span, .c-lines li.tot.last b { color: var(--ink); font-weight: 800; }
    .c-note { font-size: 0.74rem; color: var(--muted); margin-top: 12px; padding: 9px 12px; border-radius: 12px; background: var(--surface); overflow-wrap: anywhere; }
    .c-note i { color: var(--faint); margin-right: 6px; }

    .c-act { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 16px; }
    .c-act form { margin: 0; }
    .c-act .btn-soft, .c-act .btn-solid { padding: 8px 14px; font-size: 0.74rem; }
    .c-act .sp { flex: 1; }
    .ghost-danger { border: 0; background: none; color: var(--danger-fg); font-size: 0.74rem; font-weight: 700; padding: 8px 10px; cursor: pointer; border-radius: 10px; }
    .ghost-danger:hover { background: var(--danger-soft); }

    /* ---------- Riwayat aktivitas ---------- */
    .act-wrap { margin-top: 30px; }
    .act-card { background: var(--card); border-radius: var(--r-card); box-shadow: var(--shadow-card); padding: 8px 22px; }
    .act { list-style: none; margin: 0; padding: 0; }
    .act li { position: relative; display: flex; gap: 14px; padding: 14px 0; }
    .act li + li { border-top: 1px solid var(--line); }
    .act-ic { width: 34px; height: 34px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
    .act-ic.indigo { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
    .act-ic.green { background: var(--green-soft); color: var(--green-text); }
    .act-ic.amber { background: var(--warn-soft); color: var(--warn-fg); }
    .act-ic.red { background: var(--danger-soft); color: var(--danger-fg); }
    .act-b { flex: 1; min-width: 0; }
    .act-t { font-size: 0.82rem; }
    .act-t b { font-weight: 800; }
    .act-t a { color: inherit; font-weight: 800; text-decoration: none; border-bottom: 1px dashed var(--faint); }
    .act-t .gone { font-weight: 800; text-decoration: line-through; color: var(--muted); }
    .act-m { font-size: 0.7rem; color: var(--muted); margin-top: 2px; overflow-wrap: anywhere; }
    .act-time { font-size: 0.7rem; color: var(--faint); white-space: nowrap; text-align: right; }
    .act-time b { display: block; color: var(--muted); font-weight: 700; }
    .c-log { list-style: none; margin: 10px 0 0; padding: 10px 0 0; border-top: 1px dashed var(--line); font-size: 0.72rem; }
    .c-log li { display: flex; gap: 8px; padding: 3px 0; color: var(--muted); }
    .c-log li i { font-size: 0.7rem; margin-top: 2px; }
    .c-log li span:last-child { margin-left: auto; color: var(--faint); white-space: nowrap; }

    /* ---------- Bilah pilihan ---------- */
    .sel-bar {
        position: fixed; left: 50%; bottom: 22px; transform: translate(-50%, 160%); z-index: 1100;
        display: flex; align-items: center; gap: 14px; padding: 8px 8px 8px 22px; border-radius: 999px;
        background: rgba(20, 27, 24, 0.88); color: #fff; backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 18px 50px rgba(0,0,0,0.3); transition: transform 0.3s cubic-bezier(0.34, 1.3, 0.5, 1); max-width: calc(100vw - 32px);
    }
    .sel-bar.show { transform: translate(-50%, 0); }
    .sel-bar .t { font-size: 0.7rem; color: rgba(255,255,255,0.65); white-space: nowrap; }
    .sel-bar .t b { display: block; color: #fff; font-size: 1rem; font-variant-numeric: tabular-nums; }
    .sel-bar .x { border: 0; background: rgba(255,255,255,0.1); color: #fff; width: 38px; height: 38px; border-radius: 50%; cursor: pointer; flex-shrink: 0; }
    .sel-bar .x:hover { background: rgba(255,255,255,0.2); }

    /* ---------- Modal ---------- */
    .gm-modal { position: fixed; inset: 0; z-index: 1250; display: flex; align-items: flex-start; justify-content: center; padding: 7vh 16px 16px; background: rgba(12, 18, 15, 0.45); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s; overflow-y: auto; }
    .gm-modal.open { opacity: 1; visibility: visible; }
    .gm-modal-card { width: 100%; max-width: 500px; background: var(--card); border-radius: 26px; box-shadow: var(--shadow-pop); padding: 26px; transform: translateY(-14px) scale(0.97); opacity: 0; transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.5, 1), opacity 0.2s ease; }
    .gm-modal.open .gm-modal-card { transform: none; opacity: 1; }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.1rem; }
    .gm-modal-sub { font-size: 0.76rem; color: var(--muted); margin: 4px 0 18px; line-height: 1.5; }
    .form-field { margin-bottom: 14px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 600; color: var(--faint); }
    .form-input { width: 100%; height: 44px; border-radius: 14px; border: 1px solid var(--line); background: var(--surface); color: var(--ink); padding: 0 14px; font-size: 0.86rem; outline: none; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input[type=file] { padding: 9px 12px; height: auto; font-size: 0.78rem; }
    .form-error { font-size: 0.72rem; color: var(--danger-fg); margin: -4px 0 10px; }
    .form-hint { font-size: 0.68rem; color: var(--faint); margin-top: 6px; }
    .f-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 520px) { .f-row { grid-template-columns: 1fr; } }
    .btn-row { display: flex; gap: 10px; margin-top: 8px; }
    .btn-row > * { flex: 1; }

    .md-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
    .md-chips span { padding: 5px 11px; border-radius: 999px; background: var(--green-soft); color: var(--green-text); font-size: 0.72rem; font-weight: 700; }
    .tg { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 16px; background: var(--surface); margin-bottom: 8px; cursor: pointer; }
    .tg .tx { flex: 1; min-width: 0; font-size: 0.82rem; font-weight: 700; }
    .tg .tx small { display: block; font-weight: 500; color: var(--muted); font-size: 0.68rem; margin-top: 1px; }
    .tg input[type=checkbox] { appearance: none; -webkit-appearance: none; width: 40px; height: 24px; border-radius: 999px; background: var(--line); position: relative; cursor: pointer; flex-shrink: 0; transition: background 0.2s; margin: 0; }
    .tg input[type=checkbox]::after { content: ''; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s; }
    .tg input[type=checkbox]:checked { background: var(--green); }
    .tg input[type=checkbox]:checked::after { transform: translateX(16px); }
    .tg .pct-in { display: flex; align-items: center; gap: 4px; font-size: 0.78rem; color: var(--muted); }
    .tg .pct-in input { width: 66px; height: 32px; border-radius: 10px; border: 1px solid var(--line); background: var(--card); color: var(--ink); padding: 0 8px; font-size: 0.8rem; text-align: right; }
    .tg .pct-in input:disabled { opacity: 0.45; }

    .rcpt { border-radius: 18px; padding: 6px 16px; margin: 14px 0 16px; background: var(--card); border: 1px solid var(--line); font-size: 0.8rem; }
    .rcpt div { display: flex; justify-content: space-between; padding: 8px 0; }
    .rcpt div + div { border-top: 1px dashed var(--line); }
    .rcpt span { color: var(--muted); }
    .rcpt b { font-variant-numeric: tabular-nums; }
    .rcpt .tot span { color: var(--ink); font-weight: 800; }
    .rcpt .tot b { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; color: var(--green-text); }

    .seg { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; border-radius: 14px; background: var(--surface); }
    .seg label { margin: 0; position: relative; }
    .seg input { position: absolute; opacity: 0; pointer-events: none; }
    .seg span { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 8px; border-radius: 11px; font-size: 0.78rem; font-weight: 700; color: var(--muted); cursor: pointer; transition: all 0.15s; }
    .seg input:checked + span { background: var(--card); color: var(--ink); box-shadow: 0 1px 4px rgba(0,0,0,0.08); }

    .docs-list { display: flex; flex-direction: column; gap: 8px; }
    .docs-list a { display: flex; align-items: center; gap: 12px; padding: 13px 16px; border-radius: 16px; background: var(--surface); color: var(--ink); text-decoration: none; font-size: 0.84rem; font-weight: 700; }
    .docs-list a:hover { background: var(--green-soft); color: var(--green-text); }
    .docs-list a i { font-size: 1.15rem; color: var(--green-text); }
    .docs-list a .ar { margin-left: auto; font-size: 0.9rem; color: var(--faint); }
    .docs-msg { font-size: 0.8rem; color: var(--muted); padding: 10px 2px; display: flex; align-items: center; gap: 8px; }
    .docs-msg.err { color: var(--danger-fg); }
@endpush

@section('content')
<a href="{{ route('admin.aws-accounts.billing', $account) }}" class="cl-back"><i class="bi bi-arrow-left"></i> {{ __('awsclaims.back') }}</a>

<div class="q-page-head">
    <h1 class="q-title">{{ __('awsclaims.title') }} <span class="soft">{{ $account->name }}</span></h1>
</div>
<p class="cl-lead">{{ __('awsclaims.sub') }}</p>

{{-- Hero: yang masih bisa diajukan + gambaran semua bulan --}}
<div class="cl-hero">
    <div class="h-top">
        <div>
            <div class="h-lbl">{{ __('awsclaims.sum_unclaimed') }}</div>
            <div class="h-val">{{ $rp($amt['unclaimed']) }}</div>
            <div class="h-sub">{{ $ready->count() ? __('awsclaims.hero_sub', ['n' => $ready->count()]) : __('awsclaims.hero_sub_none') }}</div>
        </div>
        @if($canEdit)
            <button type="button" class="h-cta" id="claimAll" {{ $ready->count() ? '' : 'disabled' }}>
                <i class="bi bi-send-fill"></i> {{ __('awsclaims.claim_all') }}
            </button>
        @endif
    </div>

    <div class="h-bar" aria-hidden="true">
        @foreach(['paid', 'submitted', 'unclaimed'] as $k)
            @if($amt[$k] > 0)<span class="{{ $k }}" style="flex: {{ $amt[$k] / $amtAll }};"></span>@endif
        @endforeach
    </div>
    <div class="h-leg">
        <div>
            <div class="k"><i style="background:#fcd34d;"></i> {{ __('awsclaims.sum_unclaimed') }}</div>
            <div class="v">{{ $rp($amt['unclaimed']) }}</div>
            <div class="s">{{ __('awsclaims.sum_months', ['n' => $ready->count()]) }} · {{ __('awsclaims.sum_unclaimed_hint') }}</div>
        </div>
        <div>
            <div class="k"><i style="background:#c7d2fe;"></i> {{ __('awsclaims.sum_submitted') }}</div>
            <div class="v">{{ $rp($amt['submitted']) }}</div>
            <div class="s">{{ __('awsclaims.sum_claims', ['n' => $submitted->count()]) }}</div>
        </div>
        <div>
            <div class="k"><i style="background:#fff;"></i> {{ __('awsclaims.sum_paid') }}</div>
            <div class="v">{{ $rp($amt['paid']) }}</div>
            <div class="s">{{ __('awsclaims.sum_claims', ['n' => $paid->count()]) }}</div>
        </div>
    </div>

    @if($strip->count() > 1)
        <div class="h-strip" aria-label="{{ __('awsclaims.strip') }}">
            @foreach($strip as $r)
                <a class="h-m {{ $r['status'] }}" href="{{ $r['claim'] ? '#claim-' . $r['claim']->uuid : '#month-' . $r['month']->format('Y-m') }}"
                   title="{{ $r['month']->translatedFormat('F Y') }} · {{ __('awsclaims.st_' . $r['status']) }}{{ $r['invoice'] ? ' · ' . $rp($r['invoice']->idrAmount()) : '' }}">
                    <div class="mo">{{ $r['month']->translatedFormat('M') }}</div>
                    <div class="yr">{{ $r['month']->format('Y') }}</div>
                    <span class="dot"></span>
                </a>
            @endforeach
        </div>
    @endif
</div>

<div class="cl-grid">
    {{-- Kiri: bulan yang siap diajukan --}}
    <section>
        <div class="sec-h">
            <div>
                <div class="sec-t">{{ __('awsclaims.ready_title') }} <span class="n">{{ $ready->count() }}</span></div>
                <div class="sec-s">{{ __('awsclaims.ready_sub') }}</div>
            </div>
            @if($canEdit && $ready->count())
                <div class="sec-act">
                    @if($ready->count() > 1)
                        <button type="button" class="link-btn" id="selAll">{{ __('awsclaims.select_all_btn') }}</button>
                    @endif
                    <button type="button" class="btn-solid sm" id="selGoTop" disabled><i class="bi bi-send-fill"></i> {{ __('awsclaims.submit_selected') }}</button>
                </div>
            @endif
        </div>

        <div class="ms">
            @if(!$hasInvoices)
                <div class="empty"><i class="bi bi-cloud-download"></i>{{ __('awsclaims.no_invoices') }}</div>
            @elseif($ready->isEmpty())
                <div class="empty"><i class="bi bi-check2-all"></i>{{ __('awsclaims.ready_empty') }}</div>
            @endif

            @foreach($ready as $r)
                @php $inv = $r['invoice']; @endphp
                <label class="m-card" id="month-{{ $r['month']->format('Y-m') }}">
                    @if($canEdit)
                        <input type="checkbox" class="m-pick" value="{{ $r['month']->format('Y-m') }}" data-label="{{ $r['month']->translatedFormat('M Y') }}" data-idr="{{ $inv->idrAmount() }}">
                        <span class="m-chk"><i class="bi bi-check-lg"></i></span>
                    @endif
                    <span class="m-cal"><span class="mo d-block">{{ $r['month']->translatedFormat('M') }}</span><span class="yr d-block">{{ $r['month']->format('Y') }}</span></span>
                    <span class="m-body">
                        <span class="m-name d-block">{{ $r['month']->translatedFormat('F Y') }}</span>
                        <span class="m-inv">
                            {{ $inv->invoice_id }}
                            <button type="button" class="doc-btn" data-docs="{{ route('admin.aws-accounts.billing.invoice-documents', [$account, $inv->invoice_id]) }}" data-inv="{{ $inv->invoice_id }}">
                                <i class="bi bi-paperclip"></i> {{ __('awsclaims.docs') }}
                            </button>
                        </span>
                    </span>
                    <span class="m-amt">
                        <span class="v d-block">{{ $rp($inv->idrAmount()) }}</span>
                        <span class="s d-block">{{ $usd($inv->base_amount) }}</span>
                    </span>
                </label>
            @endforeach

            @if($pendingRow)
                <div class="m-card pending">
                    <span class="m-cal"><span class="mo d-block">{{ $pendingRow['month']->translatedFormat('M') }}</span><span class="yr d-block">{{ $pendingRow['month']->format('Y') }}</span></span>
                    <span class="m-body">
                        <span class="m-name d-block">{{ $pendingRow['month']->translatedFormat('F Y') }}</span>
                        <span class="m-inv" style="font-family:inherit;">{{ __('awsclaims.pending_sub') }}</span>
                    </span>
                    <span class="m-amt"><span class="s d-block"><i class="bi bi-hourglass-split"></i> {{ __('awsclaims.st_pending') }}</span></span>
                </div>
            @endif
        </div>
    </section>

    {{-- Kanan: riwayat pengajuan --}}
    <section>
        <div class="sec-h">
            <div>
                <div class="sec-t">{{ __('awsclaims.claims_title') }} <span class="n">{{ $claims->count() }}</span></div>
                <div class="sec-s">{{ __('awsclaims.claims_sub') }}</div>
            </div>
        </div>

        <div class="cs">
            @if($claims->isEmpty())
                <div class="empty"><i class="bi bi-inbox"></i>{{ __('awsclaims.claims_empty') }}</div>
            @endif

            @foreach($claims as $c)
                <article class="c-card {{ $c->isPaid() ? 'paid' : 'submitted' }}" id="claim-{{ $c->uuid }}">
                    <div class="c-head">
                        <div>
                            <div class="c-code">{{ $c->code() }}</div>
                            @if($c->created_by)<div class="c-by">{{ __('awsclaims.by', ['by' => $c->created_by]) }}</div>@endif
                        </div>
                        <span class="pill {{ $c->isPaid() ? 'paid' : 'submitted' }}">
                            <i class="bi {{ $c->isPaid() ? 'bi-check-circle-fill' : 'bi-clock-history' }}"></i> {{ __('awsclaims.st_' . $c->status) }}
                        </span>
                    </div>

                    <div class="c-row">
                        <div class="c-total">
                            {{ $rp($c->total_idr) }}
                            <small>{{ trans_choice('awsclaims.n_months', $c->items->count(), ['n' => $c->items->count()]) }} · {{ $usd($c->total_usd) }}</small>
                        </div>
                        <div class="stp">
                            <div class="st on"><i class="bi bi-send-fill"></i><b>{{ $c->submitted_on->translatedFormat('d M Y') }}</b>{{ __('awsclaims.step_submitted') }}</div>
                            <div class="ln {{ $c->isPaid() ? 'on' : '' }}"></div>
                            <div class="st {{ $c->isPaid() ? 'on' : '' }}"><i class="bi bi-check-lg"></i><b>{{ $c->paid_on ? $c->paid_on->translatedFormat('d M Y') : '—' }}</b>{{ __('awsclaims.step_paid') }}</div>
                        </div>
                    </div>

                    <div class="c-months">
                        @foreach($c->items as $it)
                            <span class="c-chip">
                                {{ $it->month->translatedFormat('M Y') }}
                                @if($it->invoice_id)
                                    <button type="button" class="doc-btn" title="{{ $it->invoice_id }}" data-docs="{{ route('admin.aws-accounts.billing.invoice-documents', [$account, $it->invoice_id]) }}" data-inv="{{ $it->invoice_id }}"><i class="bi bi-paperclip"></i></button>
                                @endif
                            </span>
                        @endforeach
                    </div>

                    <details class="c-det">
                        <summary><i class="bi bi-chevron-right"></i> {{ __('awsclaims.details') }}</summary>
                        <ul class="c-lines">
                            @foreach($c->items as $it)
                                <li><span>{{ $it->month->translatedFormat('F Y') }} <small>{{ $it->invoice_id }}</small></span><b>{{ $rp($it->total_idr) }}</b></li>
                            @endforeach
                            <li class="tot"><span>{{ __('awsclaims.line_aws') }}</span><b>{{ $rp($c->aws_idr) }}</b></li>
                            @if($c->include_fee)<li><span>{{ __('awsclaims.line_fee', ['pct' => $pct($c->fee_percent)]) }}</span><b>{{ $rp($c->fee_idr) }}</b></li>@endif
                            @if($c->include_vat)<li><span>{{ __('awsclaims.line_vat', ['pct' => $pct($c->vat_rate * 100)]) }}</span><b>{{ $rp($c->vat_idr) }}</b></li>@endif
                            <li class="tot last"><span>{{ __('awsclaims.line_total') }}</span><b>{{ $rp($c->total_idr) }}</b></li>
                        </ul>
                        @if($log = $actByClaim->get($c->id))
                            <ul class="c-log" aria-label="{{ __('awsclaims.act_log') }}">
                                @foreach($log->sortBy('created_at') as $a)
                                    <li>
                                        <i class="bi {{ $actIcon[$a->action] ?? 'bi-dot' }}"></i>
                                        <span>{{ __('awsclaims.act_' . $a->action, ['who' => $a->actor ?? '—', 'code' => $a->claim_code]) }}</span>
                                        <span>{{ $a->created_at?->wib()->translatedFormat('d M Y, H:i') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </details>

                    @if($c->note)<div class="c-note"><i class="bi bi-chat-left-text"></i>{{ $c->note }}</div>@endif

                    <div class="c-act">
                        <button type="button" class="btn-soft" data-pdf="{{ route('admin.aws-accounts.claims.pdf', [$account, $c]) }}" data-att="{{ route('admin.aws-accounts.claims.attachments', [$account, $c]) }}"><i class="bi bi-file-earmark-pdf"></i> <span>{{ __('awsclaims.pdf_btn') }}</span></button>
                        <button type="button" class="btn-soft" data-copy="{{ $tsv($c) }}"><i class="bi bi-clipboard"></i> {{ __('awsclaims.copy') }}</button>
                        @if($c->proof_path)
                            <a href="{{ route('admin.aws-accounts.claims.proof', [$account, $c]) }}" target="_blank" rel="noopener" class="btn-soft"><i class="bi bi-file-earmark-check"></i> {{ __('awsclaims.proof') }}</a>
                        @endif
                        <span class="sp"></span>
                        @if($canEdit)
                            @if($c->isPaid())
                                <form method="POST" action="{{ route('admin.aws-accounts.claims.unpaid', [$account, $c]) }}" onsubmit="return confirm(this.dataset.msg)" data-msg="{{ __('awsclaims.mark_unpaid_confirm', ['code' => $c->code()]) }}">
                                    @csrf
                                    <button type="submit" class="ghost-danger">{{ __('awsclaims.mark_unpaid') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.aws-accounts.claims.destroy', [$account, $c]) }}" onsubmit="return confirm(this.dataset.msg)" data-msg="{{ __('awsclaims.cancel_confirm', ['code' => $c->code()]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ghost-danger">{{ __('awsclaims.cancel_claim') }}</button>
                                </form>
                                <button type="button" class="btn-solid" data-paid="{{ route('admin.aws-accounts.claims.paid', [$account, $c]) }}" data-code="{{ $c->code() }}" data-min="{{ $c->submitted_on->format('Y-m-d') }}">
                                    <i class="bi bi-check2"></i> {{ __('awsclaims.mark_paid') }}
                                </button>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>

{{-- Riwayat aktivitas --}}
<section class="act-wrap">
    <div class="sec-h">
        <div>
            <div class="sec-t">{{ __('awsclaims.act_title') }}</div>
            <div class="sec-s">{{ __('awsclaims.act_sub') }}</div>
        </div>
    </div>
    @if($activities->isEmpty())
        <div class="empty"><i class="bi bi-clock-history"></i>{{ __('awsclaims.act_empty') }}</div>
    @else
        <div class="act-card">
            <ul class="act">
                @foreach($activities as $a)
                    @php
                        $live = !$a->aws_bill_claim_id ? null : $claims->firstWhere('id', $a->aws_bill_claim_id);
                        $codeHtml = $live
                            ? '<a href="#claim-' . e($live->uuid) . '">' . e($a->claim_code) . '</a>'
                            : '<span class="gone">' . e($a->claim_code) . '</span>';
                    @endphp
                    <li>
                        <span class="act-ic {{ $actTone[$a->action] ?? 'indigo' }}"><i class="bi {{ $actIcon[$a->action] ?? 'bi-dot' }}"></i></span>
                        <div class="act-b">
                            <div class="act-t">{!! __('awsclaims.act_' . $a->action, ['who' => '<b>' . e($a->actor ?? '—') . '</b>', 'code' => $codeHtml]) !!}</div>
                            @if($m = $actMeta($a))<div class="act-m">{{ $m }}</div>@endif
                        </div>
                        <div class="act-time"><b>{{ $a->created_at?->wib()->translatedFormat('d M Y') }}</b>{{ $a->created_at?->wib()->format('H:i') }} WIB</div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

@if($canEdit)
    {{-- Bilah pilihan --}}
    <div class="sel-bar" id="selBar" aria-live="polite">
        <span class="t"><span id="selCount"></span><b id="selSum"></b></span>
        <button type="button" class="x" id="selClear" aria-label="{{ __('awsclaims.clear') }}" title="{{ __('awsclaims.clear') }}"><i class="bi bi-x-lg"></i></button>
        <button type="button" class="btn-solid" id="selGo"><i class="bi bi-send-fill"></i> {{ __('awsclaims.submit') }}</button>
    </div>

    {{-- Modal buat pengajuan --}}
    <div class="gm-modal {{ $openNew ? 'open' : '' }}" id="newModal" role="dialog" aria-modal="true" aria-labelledby="newTitle">
        <div class="gm-modal-card">
            <div class="gm-modal-title" id="newTitle">{{ __('awsclaims.modal_title') }}</div>
            <div class="gm-modal-sub">{{ __('awsclaims.modal_sub') }}</div>

            <form method="POST" action="{{ route('admin.aws-accounts.claims.store', $account) }}" id="newForm">
                @csrf
                <div id="newMonths"></div>
                <div class="md-chips" id="newChips"></div>
                @error('months')<div class="form-error">{{ $message }}</div>@enderror

                <label class="tg">
                    <span class="tx">{{ __('awsclaims.opt_fee') }}<small>{{ __('awsclaims.opt_fee_sub') }}</small></span>
                    <span class="pct-in"><input type="number" name="fee_percent" id="feePct" step="0.01" min="0" max="100" value="{{ old('fee_percent', $feePercent) }}" aria-label="%"> %</span>
                    <input type="checkbox" name="include_fee" value="1" id="optFee" @checked(old('include_fee'))>
                </label>
                @error('fee_percent')<div class="form-error">{{ $message }}</div>@enderror
                <label class="tg">
                    <span class="tx">{{ __('awsclaims.opt_vat', ['pct' => $pct($vatRate * 100)]) }}<small>{{ __('awsclaims.opt_vat_sub') }}</small></span>
                    <input type="checkbox" name="include_vat" value="1" id="optVat" @checked(old('include_vat'))>
                </label>

                <div class="rcpt" id="calc">
                    <div><span>{{ __('awsclaims.line_aws') }}</span><b data-c="aws">—</b></div>
                    <div data-row="fee"><span>Management fee</span><b data-c="fee">—</b></div>
                    <div data-row="vat"><span>VAT</span><b data-c="vat">—</b></div>
                    <div class="tot"><span>{{ __('awsclaims.line_total') }}</span><b data-c="total">—</b></div>
                </div>

                <div class="form-field">
                    <span class="form-label-sm">{{ __('awsclaims.status_label') }}</span>
                    <div class="seg">
                        <label><input type="radio" name="status" value="submitted" @checked(old('status', 'submitted') === 'submitted')><span><i class="bi bi-send"></i> {{ __('awsclaims.status_submitted') }}</span></label>
                        <label><input type="radio" name="status" value="paid" @checked(old('status') === 'paid')><span><i class="bi bi-check2-circle"></i> {{ __('awsclaims.status_paid') }}</span></label>
                    </div>
                    <div class="form-hint">{{ __('awsclaims.status_paid_hint') }}</div>
                </div>

                <div class="f-row">
                    <div class="form-field">
                        <label class="form-label-sm" for="subOn">{{ __('awsclaims.submitted_on') }}</label>
                        <input type="date" name="submitted_on" id="subOn" class="form-input" required value="{{ old('submitted_on', now()->wib()->format('Y-m-d')) }}">
                        @error('submitted_on')<div class="form-error" style="margin-top:6px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-field" id="paidField">
                        <label class="form-label-sm" for="paidOn">{{ __('awsclaims.paid_on') }}</label>
                        <input type="date" name="paid_on" id="paidOn" class="form-input" value="{{ old('paid_on') }}">
                        @error('paid_on')<div class="form-error" style="margin-top:6px;">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label-sm" for="noteIn">{{ __('awsclaims.note') }} <span class="opt">{{ __('awsclaims.optional') }}</span></label>
                    <input type="text" name="note" id="noteIn" maxlength="500" class="form-input" placeholder="{{ __('awsclaims.note_ph') }}" value="{{ old('note') }}">
                </div>

                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('awsclaims.cancel') }}</button>
                    <button type="submit" class="btn-solid"><i class="bi bi-send-fill"></i> {{ __('awsclaims.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal tandai dibayar --}}
    <div class="gm-modal" id="paidModal" role="dialog" aria-modal="true" aria-labelledby="paidTitle">
        <div class="gm-modal-card">
            <div class="gm-modal-title" id="paidTitle"></div>
            <div class="gm-modal-sub">{{ __('awsclaims.paid_modal_sub') }}</div>
            <form method="POST" action="" id="paidForm" enctype="multipart/form-data">
                @csrf
                <div class="form-field">
                    <label class="form-label-sm" for="paidOn2">{{ __('awsclaims.paid_on') }}</label>
                    <input type="date" name="paid_on" id="paidOn2" class="form-input" required value="{{ now()->wib()->format('Y-m-d') }}">
                </div>
                <div class="form-field">
                    <label class="form-label-sm" for="proofIn">{{ __('awsclaims.proof') }} <span class="opt">{{ __('awsclaims.optional') }}</span></label>
                    <input type="file" name="proof" id="proofIn" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <div class="form-hint">{{ __('awsclaims.proof_hint') }}</div>
                </div>
                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('awsclaims.cancel') }}</button>
                    <button type="submit" class="btn-solid"><i class="bi bi-check2"></i> {{ __('awsclaims.mark_paid') }}</button>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- Modal dokumen resmi AWS --}}
<div class="gm-modal" id="docsModal" role="dialog" aria-modal="true" aria-labelledby="docsTitle">
    <div class="gm-modal-card">
        <div class="gm-modal-title" id="docsTitle">{{ __('awsclaims.docs_title') }}</div>
        <div class="gm-modal-sub">{{ __('awsclaims.docs_sub') }}</div>
        <div id="docsBody"></div>
        <div class="btn-row" style="margin-top:16px;"><button type="button" class="btn-soft" data-close>{{ __('awsclaims.close') }}</button></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const T = @json($jsText);
    const VAT = @json($vatRate);
    const toast = window.gmToast || ((m) => alert(m));
    const rp = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // ---- Modal
    const modals = [...document.querySelectorAll('.gm-modal')];
    const closeAll = () => modals.forEach((m) => m.classList.remove('open'));
    modals.forEach((m) => m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close]')) closeAll(); }));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });

    // ---- Pilih bulan
    const picks = [...document.querySelectorAll('input.m-pick')];
    const bar = document.getElementById('selBar');
    const chosen = () => picks.filter((p) => p.checked);
    const refresh = () => {
        const sel = chosen();
        picks.forEach((p) => p.closest('.m-card').classList.toggle('sel', p.checked));
        if (!bar) return;
        bar.classList.toggle('show', sel.length > 0);
        document.getElementById('selCount').textContent = T.selected.replace(':n', sel.length);
        document.getElementById('selSum').textContent = rp(sel.reduce((s, p) => s + Number(p.dataset.idr), 0));
        const all = document.getElementById('selAll');
        if (all) all.dataset.on = sel.length === picks.length ? '1' : '';
        const top = document.getElementById('selGoTop');
        if (top) top.disabled = sel.length === 0;
    };
    picks.forEach((p) => p.addEventListener('change', refresh));
    const setAll = (on) => { picks.forEach((p) => { p.checked = on; }); refresh(); };
    document.getElementById('selAll')?.addEventListener('click', (e) => setAll(!e.currentTarget.dataset.on));
    document.getElementById('selClear')?.addEventListener('click', () => setAll(false));

    // ---- Modal pengajuan: hitung ulang saat opsi berubah (sama dengan server: VAT dibulatkan per bulan).
    const form = document.getElementById('newForm');
    if (form) {
        const optFee = document.getElementById('optFee'), feePct = document.getElementById('feePct'), optVat = document.getElementById('optVat');
        const paidField = document.getElementById('paidField'), paidOn = document.getElementById('paidOn');
        const calc = () => {
            const pct = optFee.checked ? (parseFloat(feePct.value) || 0) : 0;
            let aws = 0, fee = 0, vat = 0;
            chosen().forEach((p) => {
                const a = Number(p.dataset.idr), f = a * pct / 100;
                aws += a; fee += f; vat += optVat.checked ? Math.round((a + f) * VAT) : 0;
            });
            const set = (k, v) => { form.querySelector('[data-c="' + k + '"]').textContent = rp(v); };
            set('aws', aws); set('fee', fee); set('vat', vat); set('total', aws + fee + vat);
            form.querySelector('[data-row="fee"]').hidden = !optFee.checked;
            form.querySelector('[data-row="fee"] span').textContent = T.fee.replace(':pct', String(pct).replace('.', ','));
            form.querySelector('[data-row="vat"]').hidden = !optVat.checked;
            form.querySelector('[data-row="vat"] span').textContent = T.vat.replace(':pct', String(Math.round(VAT * 10000) / 100).replace('.', ','));
            feePct.disabled = !optFee.checked;
        };
        const syncStatus = () => {
            const paid = form.querySelector('input[name=status]:checked')?.value === 'paid';
            paidField.style.visibility = paid ? 'visible' : 'hidden';
            paidOn.required = paid;
        };
        [optFee, feePct, optVat].forEach((el) => el.addEventListener('input', calc));
        form.querySelectorAll('input[name=status]').forEach((r) => r.addEventListener('change', syncStatus));

        const openNew = () => {
            const sel = chosen();
            document.getElementById('newMonths').innerHTML = sel.map((p) => '<input type="hidden" name="months[]" value="' + esc(p.value) + '">').join('');
            document.getElementById('newChips').innerHTML = sel.map((p) => '<span>' + esc(p.dataset.label) + '</span>').join('');
            calc(); syncStatus();
            document.getElementById('newModal').classList.add('open');
        };
        document.getElementById('selGo')?.addEventListener('click', openNew);
        document.getElementById('selGoTop')?.addEventListener('click', openNew);
        document.getElementById('claimAll')?.addEventListener('click', () => { setAll(true); openNew(); });

        // Kembali dari validasi gagal: pilih ulang bulan yang tadi dikirim.
        const old = @json($oldMonths);
        if (old.length) { picks.forEach((p) => { p.checked = old.includes(p.value); }); refresh(); openNew(); }
        else { calc(); syncStatus(); }
    }

    // ---- Tandai dibayar
    const paidModal = document.getElementById('paidModal');
    document.querySelectorAll('[data-paid]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('paidForm').action = b.dataset.paid;
        document.getElementById('paidTitle').textContent = T.paidTitle.replace(':code', b.dataset.code);
        document.getElementById('paidOn2').min = b.dataset.min;
        paidModal.classList.add('open');
    }));

    // ---- Dokumen resmi AWS: URL sementara diminta saat dibuka. Di dalam kartu
    // bulan, klik tombol ini tidak ikut mencentang bulannya.
    const docsBody = document.getElementById('docsBody');
    document.querySelectorAll('[data-docs]').forEach((b) => b.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('docsTitle').textContent = b.dataset.inv;
        docsBody.innerHTML = '<div class="docs-msg"><span class="spinner-border spinner-border-sm"></span> ' + esc(T.loading) + '</div>';
        document.getElementById('docsModal').classList.add('open');
        try {
            const res = await fetch(b.dataset.docs, { headers: { Accept: 'application/json' } });
            const d = await res.json();
            if (!res.ok) throw new Error(d.error || res.statusText);
            const link = (url, label, icon) => '<a href="' + esc(url) + '" target="_blank" rel="noopener"><i class="bi ' + icon + '"></i> ' + esc(label) + '<i class="bi bi-box-arrow-up-right ar"></i></a>';
            let html = link(d.pdf, T.pdf, 'bi-file-earmark-pdf-fill');
            (d.supplemental || []).forEach((u, i) => { html += link(u, T.supp.replace(':n', i + 1), 'bi-paperclip'); });
            if (!(d.supplemental || []).length) html += '<div class="docs-msg">' + esc(T.noSupp) + '</div>';
            docsBody.innerHTML = '<div class="docs-list">' + html + '</div>';
        } catch (err) {
            docsBody.innerHTML = '<div class="docs-msg err"><i class="bi bi-exclamation-triangle"></i> ' + esc(err.message) + '</div>';
        }
    }));

    // ---- Unduh PDF: sampul dari server + PDF invoice asli AWS, digabung di browser
    // dengan pdf-lib (dimuat saat pertama dipakai).
    let pdfLib = null;
    const loadPdfLib = () => pdfLib || (pdfLib = new Promise((ok, fail) => {
        const sc = document.createElement('script');
        sc.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js';
        sc.onload = () => ok(window.PDFLib);
        sc.onerror = () => { pdfLib = null; fail(new Error('pdf-lib')); };
        document.head.appendChild(sc);
    }));
    const b64 = (s) => Uint8Array.from(atob(s), (c) => c.charCodeAt(0));
    document.querySelectorAll('[data-pdf]').forEach((b) => b.addEventListener('click', async () => {
        const label = b.querySelector('span'), orig = label.textContent;
        b.disabled = true; label.textContent = T.pdfBusy;
        try {
            const [lib, cover, att] = await Promise.all([
                loadPdfLib(),
                fetch(b.dataset.pdf).then((r) => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.arrayBuffer(); }),
                fetch(b.dataset.att, { headers: { Accept: 'application/json' } }).then((r) => r.json()).catch((e) => ({ files: [], errors: [e.message] })),
            ]);
            const out = await lib.PDFDocument.create();
            const add = async (bytes) => {
                const src = await lib.PDFDocument.load(bytes, { ignoreEncryption: true });
                (await out.copyPages(src, src.getPageIndices())).forEach((p) => out.addPage(p));
            };
            await add(cover);
            const errors = [...(att.errors || [])];
            for (const f of att.files || []) {
                try { await add(b64(f.data)); } catch (e) { errors.push(f.invoice + ': ' + e.message); }
            }
            const url = URL.createObjectURL(new Blob([await out.save()], { type: 'application/pdf' }));
            const a = Object.assign(document.createElement('a'), { href: url, download: att.name || 'pengajuan.pdf' });
            document.body.appendChild(a); a.click(); a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 10000);
            if (errors.length) toast(T.pdfPartial.replace(':errors', errors.join('; ')), 'bad');
        } catch (e) {
            toast(T.pdfFailed.replace(':error', e.message), 'bad');
        } finally {
            b.disabled = false; label.textContent = orig;
        }
    }));

    // ---- Salin rekap
    document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => {
        navigator.clipboard.writeText(b.dataset.copy).then(() => toast(T.copied, 'ok')).catch(() => {});
    }));

    refresh();
})();
</script>
@endpush
