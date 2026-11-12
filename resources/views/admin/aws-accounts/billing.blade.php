@extends($public ? 'layouts.report' : 'layouts.admin-v2')

@section('title', __('awsbilling.title') . ' · ' . $account->name)

@php
    $usd = fn ($v) => ($v < -0.004 ? '−' : '') . '$' . number_format(abs((float) $v), abs($v) > 0 && abs($v) < 0.01 ? 4 : 2, '.', ',');
    $rp  = fn ($v) => (round($v) < 0 ? '−' : '') . 'Rp ' . number_format(abs(round((float) $v)), 0, ',', '.');
    $num = fn ($v) => number_format((float) $v, 0, ',', '.');
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $monthQ = $start->format('Y-m');

    // Satu view untuk admin dan link publik: tautan bulan mengikuti jalurnya,
    // dan semua aksi (tarik ulang, invoice, share) hanya untuk admin berizin.
    $billUrl = fn ($m = null) => $public
        ? route('aws-billing.public', array_filter(['token' => $share->token, 'month' => $m]))
        : route('admin.aws-accounts.billing', array_filter([$account, 'month' => $m]));
    $canEdit = !$public && auth()->user()?->can('aws_accounts.update');
    $shareOn = $share && $share->is_active;

    // Periode dalam bulan (kosong = sebulan penuh). Label "1–10 Sep".
    $hasPeriods = count($periods) > 1;
    $pLabel = fn ($p) => $p['start']->format('j') . '–' . $p['end']->translatedFormat('j M');
    $pTones = ['#00b14f', '#2f80ed', '#f5a623', '#8e5cf7', '#e8505b', '#14b8a6'];
    $feeRatio = fn ($r) => $r['idr'] > 0 ? $r['fee'] / $r['idr'] : 0;
    $products = __('awsconnect.products');

    // Navigasi bulan: opsi diurutkan dari bulan ini mundur.
    $optKeys = collect($monthOptions)->map->format('Y-m')->values();
    $idx = $optKeys->search($monthQ);
    $prevMonth = $idx !== false ? $optKeys->get($idx + 1) : null;
    $nextMonth = $idx !== false && $idx > 0 ? $optKeys->get($idx - 1) : null;

    $statusKey = $complete ? 'complete' : (!$monthClosed ? 'running' : 'partial');

    $daily = [];
    for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
        $daily[$d->format('Y-m-d')] = $usage['daily'][$d->format('Y-m-d')] ?? 0.0;
    }
    $dailyMax = max($daily ?: [0]) ?: 1;

    $syncedAt = collect([$account->service_costs_synced_at, $account->usage_types_synced_at])->filter()->max();
    $syncError = $account->service_costs_error ?: $account->usage_types_error;
    $serviceTotal = $services['subtotal'] ?: 1;

    $feePct = $split && $split['percent'] !== null ? $pct($split['percent']) . '%' : '';
    $vatPct = $split ? $pct($split['vat_rate'] * 100) : $pct($taxRate * 100);
    // Management fee & VAT bisa dimatikan per akun; barisnya disembunyikan.
    $hasFee = $split['has_fee'] ?? true;
    $hasVat = $split['has_vat'] ?? true;
    $heroSub = match (true) {
        $hasFee && $hasVat => __('awsbilling.hero_sub'),
        $hasFee            => __('awsbilling.hero_sub_fee'),
        $hasVat            => __('awsbilling.hero_sub_vat'),
        default            => __('awsbilling.hero_sub_aws'),
    };
    $openInvoice = $errors->hasAny(['total_usd', 'total_idr', 'invoice_number', 'note']);

    // Invoice resmi AWS bulan ini (Invoicing API). Yang utama dipakai mengisi
    // formulir invoice otomatis kalau admin belum mengisinya.
    $awsMain = $awsInvoices->first(fn ($i) => !$i->isCreditMemo());
    // Angka resmi AWS sedang dipakai (belum ada invoice manual): formulir terisi angka itu.
    $prefill = $invoiceSource === 'aws';
    // Di link publik hanya status yang pasti yang ditampilkan, bukan petunjuk izin.
    $showBill = !$public || in_array($billStatus, ['issued', 'pending'], true);

    // Export CSV tersedia di admin maupun link publik selama rincian Rupiah ada.
    $canExport = $split && $detail;
    $exportUrl = $public
        ? route('aws-billing.public.export', $share->token)
        : route('admin.aws-accounts.billing.export', $account);
@endphp

@push('styles')
    /* ---------- Kepala ---------- */
    .bl-back { display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 600; color: var(--muted); text-decoration: none; margin-bottom: 6px; }
    .bl-back:hover { color: var(--green-text); }
    .bl-tools { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .bl-tools form { margin: 0; }

    .mstep { display: inline-flex; align-items: center; gap: 4px; background: var(--card); border-radius: 999px; padding: 4px; box-shadow: var(--shadow-card); }
    .mstep a, .mstep span.dis {
        width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        color: var(--ink); text-decoration: none; font-size: 0.8rem; transition: background 0.15s, color 0.15s;
    }
    .mstep a:hover { background: var(--green); color: #fff; }
    .mstep span.dis { color: var(--faint); }
    .mstep select { border: none; background: transparent; color: var(--ink); font-weight: 700; font-size: 0.84rem; padding: 0 6px; min-width: 150px; }
    /* Dropdown bulan memakai komponen bertema (partials/gm-select), dibuat
       menyatu dengan pil panah kiri-kanan: tanpa kotak, setinggi tombolnya. */
    .mstep .gm-select { min-width: 164px; }
    .mstep .gm-select-btn {
        height: 36px; border-radius: 999px; padding: 0 28px;
        justify-content: center; font-weight: 700; font-size: 0.84rem;
        background: transparent; border-color: transparent; box-shadow: none;
    }
    .mstep .gm-select-btn:hover { background: var(--surface); border-color: transparent; }
    .mstep .gm-select.open > .gm-select-btn { background: var(--surface); border-color: transparent; box-shadow: none; }
    .mstep .gm-select-panel { left: 50%; transform: translate(-50%, -6px) scale(0.98); min-width: 190px; }
    .mstep .gm-select.open .gm-select-panel { transform: translate(-50%, 0); }

    .bl-meta { display: flex; flex-wrap: wrap; gap: 6px 16px; align-items: center; margin: -6px 0 18px; font-size: 0.76rem; color: var(--muted); }
    .bl-meta span { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 4px 6px; min-width: 0; }
    .bl-meta .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--faint); }
    .bl-meta .dot.ok { background: var(--green); box-shadow: 0 0 0 4px var(--green-soft); }
    .bl-meta .dot.warn { background: var(--warn-fg); box-shadow: 0 0 0 4px var(--warn-soft); }
    .bl-meta .err { color: var(--danger-fg); }

    .bl-iam { border-radius: 16px; padding: 14px 16px; margin-bottom: 16px; background: var(--danger-soft, rgba(220, 38, 38, 0.08)); color: var(--ink); }
    .bl-iam-head { display: flex; gap: 10px; align-items: flex-start; font-size: 0.78rem; line-height: 1.55; }
    .bl-iam-head > i { color: var(--danger-fg); font-size: 1rem; margin-top: 1px; }
    .bl-iam-head b { display: block; color: var(--danger-fg); }
    .bl-iam-head span { color: var(--muted); }
    .bl-iam-code { display: flex; gap: 10px; align-items: flex-start; margin-top: 10px; }
    .bl-iam-code textarea {
        flex: 1; min-width: 0; resize: none; border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px;
        background: var(--card); color: var(--ink); font: 0.72rem/1.5 ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    @media (max-width: 600px) { .bl-iam-code { flex-direction: column; } }
    .bl-note {
        display: flex; gap: 10px; align-items: flex-start;
        border-radius: 16px; padding: 12px 14px; margin-bottom: 16px;
        font-size: 0.78rem; line-height: 1.55; background: var(--warn-soft); color: var(--warn-fg);
    }

    /* ---------- Hero: total invoice ke klien ---------- */
    .bl-top { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 16px; margin-bottom: 22px; }
    @media (max-width: 1000px) { .bl-top { grid-template-columns: minmax(0, 1fr); } }

    .hero {
        position: relative; overflow: hidden; color: #fff;
        border-radius: 26px; padding: 26px 28px;
        background:
            radial-gradient(120% 140% at 100% 0%, rgba(255,255,255,0.16) 0%, transparent 45%),
            linear-gradient(140deg, #0b8f45 0%, #067a3c 45%, #04552a 100%);
        box-shadow: 0 18px 40px rgba(4, 85, 42, 0.28);
    }
    .hero::before {
        content: ''; position: absolute; inset: 0; pointer-events: none; opacity: 0.08;
        background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 18px 18px;
    }
    .hero > * { position: relative; }
    .hero-top { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
    .hero-lbl { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.75); }
    .hero-month { font-size: 0.74rem; font-weight: 700; background: rgba(255,255,255,0.14); border-radius: 999px; padding: 5px 12px; white-space: nowrap; }
    .hero-val { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; letter-spacing: -0.035em; font-size: clamp(2rem, 4.4vw, 3rem); line-height: 1.05; margin: 10px 0 4px; }
    .hero-sub { font-size: 0.78rem; color: rgba(255,255,255,0.75); }

    .flow { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap: 8px; align-items: stretch; margin-top: 22px; }
    .flow-step { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.14); border-radius: 16px; padding: 12px 14px; min-width: 0; }
    .flow-step .k { font-size: 0.68rem; color: rgba(255,255,255,0.72); font-weight: 600; }
    .flow-step .v { font-weight: 800; font-size: 0.98rem; margin-top: 3px; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .flow-step .s { font-size: 0.66rem; color: rgba(255,255,255,0.6); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .flow-op { display: flex; align-items: center; color: rgba(255,255,255,0.6); font-weight: 800; }
    @media (max-width: 640px) {
        .hero { padding: 22px 18px; }
        .flow { grid-template-columns: minmax(0, 1fr); }
        .flow-op { justify-content: center; height: 10px; }
    }

    /* Slider kurs (simulasi) */
    .fx { margin-top: 18px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.16); }
    .fx-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
    .fx-lbl { font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.78); display: inline-flex; gap: 6px; align-items: center; }
    .fx-val { display: inline-flex; align-items: center; gap: 4px; padding: 2px 4px 2px 10px; border-radius: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22); cursor: text; transition: background 0.12s, border-color 0.12s; }
    .fx-val:hover { background: rgba(255,255,255,0.16); }
    .fx-val:focus-within { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.55); }
    .fx-val > span { font-weight: 800; font-size: 0.92rem; }
    .fx-val input { width: 9ch; border: none; outline: none; background: transparent; color: #fff; font: inherit; font-weight: 800; font-size: 0.92rem; font-variant-numeric: tabular-nums; padding: 2px 4px; }
    .fx-val.bad { border-color: #fca5a5; }
    .fx-tag { font-size: 0.62rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; background: #fbbf24; color: #3b2a02; border-radius: 999px; padding: 3px 9px; }
    .fx-reset { margin-left: auto; border: none; background: rgba(255,255,255,0.16); color: #fff; border-radius: 999px; padding: 5px 12px; font-size: 0.7rem; font-weight: 700; cursor: pointer; }
    .fx-reset:hover { background: rgba(255,255,255,0.26); }
    .fx input[type=range] { -webkit-appearance: none; appearance: none; width: 100%; height: 6px; border-radius: 999px; background: rgba(255,255,255,0.22); outline: none; cursor: pointer; }
    .fx input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 22px; height: 22px; border-radius: 50%; background: #fff; border: 4px solid #0b8f45; box-shadow: 0 4px 12px rgba(0,0,0,0.25); transition: transform 0.12s; }
    .fx input[type=range]::-webkit-slider-thumb:hover { transform: scale(1.12); }
    .fx input[type=range]::-moz-range-thumb { width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 4px solid #0b8f45; box-shadow: 0 4px 12px rgba(0,0,0,0.25); }
    .fx input[type=range]:focus-visible { box-shadow: 0 0 0 4px rgba(255,255,255,0.3); }
    .fx-scale { display: flex; justify-content: space-between; gap: 8px; font-size: 0.64rem; color: rgba(255,255,255,0.6); margin-top: 8px; }
    .fx-scale #fxDelta { text-align: center; font-weight: 700; color: rgba(255,255,255,0.85); }
    .is-sim [data-f], .is-sim [data-lin] { color: #b45309; }
    .hero.is-sim [data-f] { color: #fde68a; }

    /* ---------- Cek invoice ---------- */
    .chk { display: flex; flex-direction: column; }
    .chk-state { display: flex; gap: 14px; align-items: center; }
    .chk-ic { width: 52px; height: 52px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; background: var(--surface); color: var(--muted); }
    .chk-ic.ok { background: var(--green-soft); color: var(--green-text); }
    .chk-ic.bad { background: var(--danger-soft); color: var(--danger-fg); }
    .chk-t { font-weight: 800; font-size: 0.98rem; letter-spacing: -0.01em; }
    .chk-s { font-size: 0.74rem; color: var(--muted); margin-top: 2px; line-height: 1.45; }
    .chk-list { margin: 18px 0 0; padding: 0; list-style: none; }
    .chk-list li { display: flex; justify-content: space-between; gap: 10px; padding: 9px 0; border-top: 1px solid var(--line); font-size: 0.8rem; }
    .chk-list li span:first-child { color: var(--muted); }
    .chk-list li span:first-child { flex-shrink: 0; }
    .chk-list li b { font-variant-numeric: tabular-nums; text-align: right; min-width: 0; overflow-wrap: anywhere; }
    .chk-foot { margin-top: auto; padding-top: 16px; display: flex; gap: 8px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
    .chk-by { font-size: 0.68rem; color: var(--faint); }

    /* Status tagihan resmi AWS (Invoicing API) */
    .bs { margin-top: 16px; border-radius: 16px; background: var(--surface); padding: 12px 14px; font-size: 0.78rem; }
    .bs-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .bs-k { color: var(--muted); font-weight: 600; margin-right: auto; }
    .bs-pill { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 3px 10px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.02em; background: var(--card); color: var(--muted); }
    .bs-pill.issued { background: var(--green-soft); color: var(--green-text); }
    .bs-pill.pending { background: var(--warn-soft); color: var(--warn-fg); }
    .bs-pill.denied, .bs-pill.error { background: var(--danger-soft); color: var(--danger-fg); }
    .bs-note { color: var(--muted); font-size: 0.72rem; line-height: 1.5; margin-top: 8px; }
    .bs-note code { font-size: 0.7rem; background: var(--card); border-radius: 6px; padding: 1px 5px; color: var(--ink); overflow-wrap: anywhere; }
    .bs-inv { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 2px 12px; }
    .bs-inv:first-of-type { border-top: 0; padding-top: 0; }
    .bs-no { font-weight: 800; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .bs-no small { font-weight: 700; color: var(--danger-fg); margin-left: 4px; }
    .bs-pdf { display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 9px; border-radius: 999px; background: var(--card); color: var(--green-text); font-size: 0.68rem; font-weight: 700; text-decoration: none; vertical-align: 1px; }
    .bs-pdf:hover { background: var(--green-soft); }
    .bs-amt { font-weight: 800; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .bs-sub { color: var(--muted); font-size: 0.7rem; }
    .bs-sub.r { text-align: right; white-space: nowrap; }
    .bs-due.late { color: var(--warn-fg); font-weight: 700; }

    /* ---------- Per perusahaan ---------- */
    .sec-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    .sec-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.15rem; letter-spacing: -0.02em; }
    .sec-sub { font-size: 0.75rem; color: var(--muted); margin-top: 2px; max-width: 640px; }

    .co-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(360px, 100%), 1fr)); gap: 16px; margin-bottom: 22px; align-items: start; }
    .co { display: flex; flex-direction: column; animation: coIn 0.34s cubic-bezier(0.34, 1.3, 0.5, 1) backwards; }
    @keyframes coIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .co-head { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .co-av { width: 42px; height: 42px; font-size: 0.78rem !important; letter-spacing: -0.02em; border-radius: 14px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; background: var(--green-soft); color: var(--green-text); }
    .co-av.none { background: var(--surface); color: var(--muted); }
    .co-name { font-weight: 800; font-size: 0.95rem; letter-spacing: -0.01em; }
    .co-keys { font-size: 0.7rem; color: var(--muted); margin-top: 1px; }
    .co-total { margin-left: auto; text-align: right; }
    .co-total .v { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.02em; white-space: nowrap; }
    .co-total .k { font-size: 0.66rem; color: var(--muted); font-weight: 600; }
    .co-share { display: flex; align-items: center; gap: 10px; margin: 16px 0 4px; }
    .co-bar { flex: 1; height: 8px; border-radius: 999px; background: var(--surface); overflow: hidden; }
    .co-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--green), var(--green-dark)); }
    .co-share b { font-size: 0.74rem; min-width: 46px; text-align: right; }
    .co-lines { margin-top: 10px; }
    .co-line { display: flex; justify-content: space-between; gap: 10px; font-size: 0.79rem; padding: 7px 0; }
    .co-line + .co-line { border-top: 1px dashed var(--line); }
    .co-line span { color: var(--muted); }
    .co-line b { font-weight: 600; font-variant-numeric: tabular-nums; }

    .co details { margin-top: 12px; border-top: 1px solid var(--line); }
    .co summary {
        list-style: none; cursor: pointer; padding: 12px 0 2px; font-size: 0.78rem; font-weight: 700; color: var(--green-text);
        display: flex; align-items: center; gap: 6px;
    }
    .co summary::-webkit-details-marker { display: none; }
    .co summary i { transition: transform 0.2s; }
    .co details[open] summary i { transform: rotate(90deg); }

    .kb { margin-top: 12px; background: var(--surface); border-radius: 16px; padding: 14px; }
    .kb-head { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 4px 10px; align-items: baseline; margin-bottom: 8px; }
    .kb-sum { margin-left: auto; }
    .kb-name { font-family: ui-monospace, monospace; font-size: 0.78rem; font-weight: 700; }
    .kb-sum { font-size: 0.74rem; font-weight: 700; white-space: nowrap; }
    .kb-sum small { color: var(--muted); font-weight: 600; margin-left: 4px; }
    /* Kolom angka berlebar tetap supaya USD & Rupiah sejajar antar baris. */
    .op { display: grid; grid-template-columns: minmax(0, 1fr) 76px 116px; column-gap: 12px; align-items: baseline; padding: 9px 0 8px; font-size: 0.78rem; }
    .op + .op { border-top: 1px solid var(--line); }
    .op-n { min-width: 0; }
    .op-n .cat, .grp .cat { display: inline-block; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); background: var(--card); border-radius: 6px; padding: 1px 6px; margin-right: 6px; }
    .op-n .nm { font-family: ui-monospace, monospace; overflow-wrap: anywhere; }
    .op-n .rq { display: block; font-size: 0.66rem; color: var(--muted); margin-top: 2px; }
    .op .u, .grp-h .u { color: var(--muted); text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .op .i, .grp-h .i { font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .op-bar { grid-column: 1 / -1; height: 3px; border-radius: 999px; background: var(--card); margin-top: 7px; overflow: hidden; }
    .op-bar span { display: block; height: 100%; border-radius: 999px; background: var(--green); opacity: 0.7; }

    .kb-tools { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 10px; font-size: 0.7rem; color: var(--muted); font-weight: 600; }
    .seg { display: inline-flex; background: var(--surface); border-radius: 999px; padding: 3px; gap: 2px; }
    .seg button { border: none; background: none; border-radius: 999px; padding: 6px 12px; font-size: 0.72rem; font-weight: 700; color: var(--muted); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s, color 0.15s; }
    .seg button.on { background: var(--card); color: var(--ink); box-shadow: 0 1px 4px rgba(20, 27, 24, 0.12); }
    .seg button:not(.on):hover { color: var(--ink); }

    .grp + .grp { margin-top: 10px; }
    .grp-h { display: grid; grid-template-columns: minmax(0, 1fr) 76px 116px; column-gap: 12px; align-items: center; background: var(--card); border-radius: 12px; padding: 8px 10px; font-size: 0.78rem; }
    .grp-h .i { font-weight: 800; }
    .grp-n { min-width: 0; display: flex; align-items: center; flex-wrap: wrap; gap: 2px 0; }
    .grp-h .cat { background: var(--green-soft); color: var(--green-text); }
    .grp-c { font-size: 0.66rem; color: var(--muted); }
    .op.sub { padding-left: 12px; padding-right: 10px; }
    @media (max-width: 480px) {
        .op, .grp-h { grid-template-columns: minmax(0, 1fr) auto; }
        .op .u, .grp-h .u { display: none; }
    }
    .kb-zero { font-size: 0.66rem; color: var(--faint); margin-top: 6px; }
    .co-note { font-size: 0.68rem; color: var(--muted); margin-top: 10px; line-height: 1.5; }

    .empty-card { text-align: center; color: var(--muted); font-size: 0.82rem; padding: 30px 16px; }

    /* ---------- Detail teknis (dilipat) ---------- */
    .tech > summary {
        list-style: none; cursor: pointer; display: flex; align-items: center; gap: 12px;
        background: var(--card); border-radius: var(--r-card); padding: 16px 20px; box-shadow: var(--shadow-card);
    }
    .tech > summary::-webkit-details-marker { display: none; }
    .tech > summary .chev { margin-left: auto; width: 32px; height: 32px; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center; transition: transform 0.2s; }
    .tech[open] > summary .chev { transform: rotate(180deg); }
    .tech[open] > summary { border-radius: var(--r-card) var(--r-card) 0 0; box-shadow: none; }
    .tech-body { background: var(--card); border-radius: 0 0 var(--r-card) var(--r-card); padding: 4px 20px 20px; box-shadow: var(--shadow-card); }
    .tech-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 28px; }
    @media (max-width: 1000px) { .tech-grid { grid-template-columns: minmax(0, 1fr); } }
    .tech h4 { font-size: 0.78rem; font-weight: 800; margin: 16px 0 8px; }

    .bl-table { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
    .bl-table th { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); text-align: left; padding: 0 8px 8px; }
    .bl-table td { padding: 9px 8px; border-top: 1px solid var(--line); vertical-align: middle; }
    .bl-table .r { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .bl-table tfoot td { font-weight: 800; border-top: 2px solid var(--line); }
    .bl-table tr.muted td { color: var(--muted); }
    .bl-code { font-family: ui-monospace, monospace; font-size: 0.66rem; color: var(--muted); }
    .bl-scroll { overflow-x: auto; }

    .bl-stats { display: flex; gap: 18px; flex-wrap: wrap; font-size: 0.72rem; color: var(--muted); margin-bottom: 4px; }
    .bl-stats b { color: var(--ink); font-variant-numeric: tabular-nums; }
    .bl-chart { position: relative; display: flex; align-items: flex-end; gap: 3px; height: 150px; padding-top: 46px; }
    .bl-chart .avg { position: absolute; left: 0; right: 0; border-top: 1.5px dashed var(--faint); pointer-events: none; }
    .bl-chart .b { flex: 1; min-width: 0; height: 100%; display: flex; align-items: flex-end; cursor: default; }
    /* Redup lewat warna, bukan opacity — opacity ikut menurun ke tooltip di dalamnya. */
    .bl-chart .bar { position: relative; display: block; width: 100%; border-radius: 5px 5px 2px 2px; background: color-mix(in srgb, var(--green) 70%, var(--card)); transition: background 0.15s; }
    .bl-chart .b.zero .bar { background: var(--line); height: 3px !important; }
    .bl-chart .b.peak .bar { background: var(--green); }
    .bl-chart .b:hover .bar { background: var(--green-dark); }
    /* Tooltip menempel di atas batangnya; di tepi kiri/kanan digeser supaya tidak terpotong. */
    .bl-chart .tip {
        position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translate(-50%, 4px);
        background: var(--ink); color: var(--card); border-radius: 10px; padding: 6px 10px;
        font-size: 0.66rem; line-height: 1.35; text-align: center; white-space: nowrap;
        box-shadow: var(--shadow-pop); pointer-events: none; opacity: 0; transition: opacity 0.12s, transform 0.12s; z-index: 5;
    }
    .bl-chart .tip b { display: block; font-size: 0.8rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .bl-chart .tip::after { content: ''; position: absolute; top: 100%; left: 50%; margin-left: -5px; border: 5px solid transparent; border-top-color: var(--ink); }
    .bl-chart .b.edge-l .tip { left: 0; transform: translate(0, 4px); }
    .bl-chart .b.edge-l .tip::after { left: 10px; }
    .bl-chart .b.edge-r .tip { left: auto; right: 0; transform: translate(0, 4px); }
    .bl-chart .b.edge-r .tip::after { left: auto; right: 5px; }
    .bl-chart .b:hover .tip { opacity: 1; transform: translate(-50%, 0); }
    .bl-chart .b.edge-l:hover .tip, .bl-chart .b.edge-r:hover .tip { transform: none; }
    .bl-axis { display: flex; justify-content: space-between; font-size: 0.64rem; color: var(--muted); margin-top: 8px; }

    /* ---------- Periode dalam bulan ---------- */
    .pbar { display: flex; gap: 4px; margin-top: 18px; }
    .pbar-seg { position: relative; min-width: 0; background: rgba(255,255,255,0.1); border-radius: 12px; padding: 9px 11px; overflow: hidden; }
    .pbar-fill { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: var(--tone); }
    .pbar-l { display: block; font-size: 0.7rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pbar-v { display: block; font-size: 0.66rem; color: rgba(255,255,255,0.7); }

    .pd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr)); gap: 16px; margin-bottom: 22px; align-items: start; }
    .pd { border-top: 4px solid var(--tone); animation: coIn 0.34s cubic-bezier(0.34, 1.3, 0.5, 1) backwards; }
    .pd-head { display: flex; align-items: center; gap: 8px; }
    .pd-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--tone); }
    .pd-name { font-weight: 800; font-size: 0.92rem; }
    .pd-share { margin-left: auto; font-size: 0.72rem; font-weight: 800; color: var(--muted); background: var(--surface); border-radius: 999px; padding: 3px 9px; }
    .pd-total { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.45rem; letter-spacing: -0.02em; margin-top: 12px; }
    .pd-sub { font-size: 0.7rem; color: var(--muted); }
    .pd-cos { margin-top: 10px; background: var(--surface); border-radius: 14px; padding: 4px 12px; }
    .pd-co { display: flex; justify-content: space-between; gap: 10px; font-size: 0.76rem; padding: 7px 0; }
    .pd-co + .pd-co { border-top: 1px solid var(--line); }
    .pd-co .n { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pd-co small { color: var(--muted); margin-left: 4px; }
    .pd-co b { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .is-sim.pd [data-f] { color: #b45309; }

    .co-periods { margin-top: 8px; background: var(--surface); border-radius: 14px; padding: 4px 12px; }
    .co-period { display: flex; justify-content: space-between; gap: 10px; font-size: 0.76rem; padding: 7px 0; }
    .co-period + .co-period { border-top: 1px solid var(--line); }
    .co-period span { color: var(--muted); display: inline-flex; align-items: center; gap: 7px; }
    .co-period i { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .co-period b { font-variant-numeric: tabular-nums; }

    .bl-chart .b.cut { position: relative; }
    .bl-chart .b.cut::after { content: ''; position: absolute; right: -2.5px; top: 30px; bottom: -4px; border-right: 2px dashed var(--ink); opacity: 0.35; pointer-events: none; }

    .cal { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 12px; }
    .cal-dn { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; color: var(--muted); text-align: center; padding-bottom: 4px; }
    .cal-d { height: 38px; border: none; border-radius: 10px; background: var(--surface); color: var(--ink); font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: background 0.12s; }
    .cal-d.in-a { background: color-mix(in srgb, var(--green) 14%, var(--surface)); }
    .cal-d:hover:not([disabled]) { background: color-mix(in srgb, var(--green) 25%, var(--surface)); }
    .cal-d.on, .cal-d.on:hover { background: var(--green); color: #fff; box-shadow: 0 4px 12px rgba(0, 177, 79, 0.35); }
    .cal-d[disabled] { opacity: 0.4; cursor: not-allowed; }
    .cal-prev { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 0.76rem; color: var(--muted); min-height: 30px; margin-bottom: 14px; }
    .cal-prev span { background: var(--green-soft); color: var(--green-text); font-weight: 700; border-radius: 999px; padding: 4px 10px; }
    .cal-prev i { font-size: 0.65rem; }

    /* ---------- Link publik ---------- */
    .sh-link { display: flex; gap: 8px; margin-bottom: 12px; }
    .sh-link input { flex: 1; min-width: 0; height: 44px; border-radius: 14px; border: 1px solid var(--line); background: var(--surface); color: var(--ink); padding: 0 12px; font-family: ui-monospace, monospace; font-size: 0.72rem; outline: none; }
    .sh-link .btn-solid { white-space: nowrap; }
    .sh-stats { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 0.72rem; color: var(--muted); margin-bottom: 14px; }
    .sh-stats a { margin-left: auto; color: var(--green-text); font-weight: 700; text-decoration: none; }
    .sh-warn { display: flex; gap: 8px; align-items: flex-start; background: var(--warn-soft); color: var(--warn-fg); border-radius: 14px; padding: 10px 12px; font-size: 0.72rem; line-height: 1.5; margin-bottom: 14px; }
    .sh-points { list-style: none; padding: 0; margin: 0 0 16px; }
    .sh-points li { display: flex; gap: 8px; font-size: 0.78rem; padding: 6px 0; }
    .sh-points i { color: var(--green-text); }
    .btn-row form { flex: 1; margin: 0; }

    /* ---------- Modal invoice ---------- */
    .gm-modal {
        position: fixed; inset: 0; z-index: 1250; display: flex; align-items: flex-start; justify-content: center;
        padding: 10vh 16px 16px; background: rgba(12, 18, 15, 0.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s;
    }
    .gm-modal.open { opacity: 1; visibility: visible; }
    .gm-modal-card {
        width: 100%; max-width: 460px; background: var(--card); border-radius: 24px; box-shadow: var(--shadow-pop);
        transform: translateY(-14px) scale(0.97); opacity: 0; transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.5, 1), opacity 0.2s ease;
        padding: 24px;
    }
    .gm-modal.open .gm-modal-card { transform: none; opacity: 1; }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-sub { font-size: 0.76rem; color: var(--muted); margin: 4px 0 18px; line-height: 1.5; }
    .ex-card .gm-modal-sub { margin-bottom: 14px; }
    .form-field { margin-bottom: 14px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 600; color: var(--faint); }
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input {
        width: 100%; height: 46px; border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink); padding: 0 14px; font-size: 0.86rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .with-prefix { position: relative; }
    .with-prefix .pfx { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 0.78rem; font-weight: 700; color: var(--muted); pointer-events: none; z-index: 2; }
    .with-prefix .form-input { padding-left: 50px; font-family: ui-monospace, monospace; }
    .f-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 520px) { .f-row { grid-template-columns: 1fr; } }
    .btn-row { display: flex; gap: 10px; margin-top: 6px; }
    .btn-row > * { flex: 1; }
    /* ---------- Modal export CSV (admin & publik) ---------- */
    .ex-card { max-width: 1040px; max-height: 88vh; overflow-y: auto; scrollbar-width: thin; }
    /* Dua kolom: pilihan di kiri, contoh file + kurs + tombol di kanan (menempel saat digulir). */
    .ex-layout { display: grid; grid-template-columns: minmax(0, 440px) minmax(0, 1fr); gap: 24px; align-items: start; }
    .ex-side { position: sticky; top: 0; min-width: 0; }
    .ex-side .ex-prev { margin-bottom: 12px; }
    .ex-side .ex-rate { margin-bottom: 12px; }
    @media (max-width: 900px) {
        .ex-card { max-width: 540px; }
        .ex-layout { grid-template-columns: minmax(0, 1fr); gap: 0; }
        .ex-side { position: static; }
    }
    .ex-modal { padding-top: 3vh; }
    .ex-group { border: 0; padding: 0; margin: 0 0 12px; min-width: 0; }
    .ex-group legend {
        display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;
        width: 100%; padding: 0; margin-bottom: 8px; font-size: 0.72rem; font-weight: 700; color: var(--muted);
    }
    .ex-group input[type=checkbox] { accent-color: var(--green); width: 16px; height: 16px; margin: 0; flex-shrink: 0; }
    .ex-list { border: 1px solid var(--line); border-radius: 14px; max-height: 188px; overflow-y: auto; padding: 4px; margin-top: 6px; scrollbar-width: thin; }
    .ex-check { display: flex; align-items: center; gap: 10px; padding: 7px 10px; border-radius: 10px; cursor: pointer; font-size: 0.8rem; color: var(--ink); }
    .ex-check:hover { background: var(--surface); }
    .ex-check .n { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ex-check small { color: var(--muted); font-size: 0.72rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ex-all { background: var(--surface); font-weight: 700; }

    .ex-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .ex-chip { position: relative; }
    .ex-chip input, .ex-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .ex-chip span {
        display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 999px;
        border: 1px solid var(--line); background: var(--card); color: var(--ink);
        font-size: 0.76rem; font-weight: 600; cursor: pointer; transition: border-color 0.15s, background 0.15s, color 0.15s;
    }
    .ex-chip span::before {
        content: ''; width: 14px; height: 14px; border-radius: 4px; box-sizing: border-box; flex-shrink: 0;
        border: 1.5px solid var(--faint); font-size: 10px; line-height: 12px; text-align: center; font-weight: 900;
    }
    .ex-chip input:checked + span { border-color: var(--green); background: var(--green-soft); color: var(--green-text); }
    .ex-chip input:checked + span::before { content: '✓'; color: #fff; background: var(--green); border-color: var(--green); }
    .ex-chip input:focus-visible + span, .ex-seg input:focus-visible + span { box-shadow: 0 0 0 3px var(--green-soft); }

    .ex-seg { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 4px; background: var(--surface); border-radius: 14px; padding: 4px; }
    .ex-seg label { position: relative; min-width: 0; }
    .ex-seg label > span {
        display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
        min-height: 36px; padding: 6px 6px; border-radius: 10px; line-height: 1.25;
        font-size: 0.76rem; font-weight: 700; color: var(--muted); cursor: pointer; transition: background 0.15s, color 0.15s;
    }
    .ex-type-l { display: inline-flex; align-items: center; gap: 6px; }
    .ex-type-l i { font-size: 0.9rem; }
    .ex-seg label > span small { font-size: 0.66rem; font-weight: 500; color: var(--faint); margin-top: 2px; }
    .ex-seg input:checked + span { background: var(--card); color: var(--ink); box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08); }
    @media (max-width: 480px) { .ex-seg { grid-auto-flow: row; } }

    .ex-presets { display: flex; gap: 4px; flex-wrap: wrap; }
    .ex-presets button {
        border: 0; background: var(--surface); color: var(--muted); font: inherit; font-size: 0.68rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; cursor: pointer;
    }
    .ex-presets button.on { background: var(--green-soft); color: var(--green-text); }

    .ex-rate { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border-radius: 14px; background: var(--surface); margin-bottom: 16px; font-size: 0.78rem; color: var(--ink); }
    .ex-rate > i { color: var(--green-text); font-size: 1rem; margin-top: 1px; }
    .ex-rate b { font-variant-numeric: tabular-nums; }
    .ex-rate small { display: block; color: var(--muted); margin-top: 3px; line-height: 1.45; }
    .ex-tag { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 999px; background: var(--warn-soft); color: var(--warn-fg); font-size: 0.66rem; font-weight: 700; }
    .ex-err { color: var(--danger-fg); font-size: 0.72rem; margin-top: 6px; }
    .ex-btn {
        height: 44px; border-radius: 999px; border: 0; font: inherit; font-size: 0.82rem; font-weight: 700; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: var(--surface); color: var(--ink);
    }
    .ex-btn.primary { background: var(--green); color: #fff; box-shadow: 0 6px 18px rgba(0, 177, 79, 0.28); }
    .ex-btn.primary:hover { filter: brightness(0.95); }
    .ex-pretty .ex-chip span { padding: 4px 10px; font-size: 0.7rem; }
    .ex-hint { font-size: 0.7rem; line-height: 1.4; color: var(--muted); margin-top: 5px; }

    /* Pratinjau isi file */
    .ex-prev { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; margin-bottom: 16px; }
    .ex-prev-head { display: flex; justify-content: space-between; gap: 8px; padding: 9px 12px; background: var(--surface); font-size: 0.72rem; font-weight: 700; color: var(--muted); }
    .ex-fmt-badge { padding: 2px 9px; border-radius: 999px; background: var(--card); color: var(--ink); font-weight: 700; }
    .ex-fmt-badge.flash { animation: exFlash 0.9s ease; }
    @keyframes exFlash { 0%, 40% { background: var(--green); color: #fff; } 100% { background: var(--card); color: var(--ink); } }
    .ex-prev-scroll { overflow-x: auto; scrollbar-width: thin; }
    .ex-prev table { border-collapse: collapse; width: 100%; font-size: 0.7rem; white-space: nowrap; font-variant-numeric: tabular-nums; color: var(--ink); }
    .ex-prev th, .ex-prev td { padding: 6px 10px; border-bottom: 1px solid var(--line); text-align: left; }
    .ex-prev th { font-weight: 700; color: var(--muted); }
    .ex-prev td.n, .ex-prev th.n { text-align: right; }
    .ex-prev tr.ex-r-sec td { font-weight: 800; color: var(--green-text); }
    .ex-prev tr.ex-r-key td { color: var(--muted); }
    .ex-prev tr.ex-r-op td { color: var(--faint); font-size: 0.66rem; }
    .ex-prev tr.ex-r-tot td { font-weight: 800; }
    .ex-prev-note { display: flex; gap: 6px; padding: 7px 12px; font-size: 0.7rem; color: var(--muted); border-bottom: 1px solid var(--line); }
    .ex-prev-raw { padding: 8px 12px; background: var(--surface); font-size: 0.68rem; overflow-x: auto; white-space: nowrap; scrollbar-width: thin; }
    .ex-prev-raw .lbl { color: var(--muted); font-weight: 700; margin-right: 6px; }
    .ex-prev-raw code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--ink); background: none; }
@endpush

@section('content')
@unless($public)
    <a href="{{ route('admin.aws-accounts.index') }}" class="bl-back"><i class="bi bi-arrow-left"></i> {{ __('awsbilling.back') }}</a>
@endunless

<div class="q-page-head">
    <h1 class="q-title">{{ __('awsbilling.title') }} <span class="soft">{{ $account->name }}</span></h1>

    <div class="bl-tools">
        <div class="mstep">
            @if($prevMonth)
                <a href="{{ $billUrl($prevMonth) }}" title="{{ __('awsbilling.prev_month') }}"><i class="bi bi-chevron-left"></i></a>
            @else
                <span class="dis"><i class="bi bi-chevron-left"></i></span>
            @endif
            <form method="GET" action="{{ $billUrl() }}">
                <select name="month" onchange="this.form.submit()" aria-label="{{ __('awsbilling.month') }}">
                    @foreach($monthOptions as $opt)
                        <option value="{{ $opt->format('Y-m') }}" @selected($opt->format('Y-m') === $monthQ)>{{ $opt->translatedFormat('F Y') }}</option>
                    @endforeach
                </select>
            </form>
            @if($nextMonth)
                <a href="{{ $billUrl($nextMonth) }}" title="{{ __('awsbilling.next_month') }}"><i class="bi bi-chevron-right"></i></a>
            @else
                <span class="dis"><i class="bi bi-chevron-right"></i></span>
            @endif
        </div>

        {{-- Export ada di admin maupun link publik. --}}
        @if($canExport)
            <button type="button" class="q-pill" data-open-modal="exportModal">
                <i class="bi bi-download"></i> {{ __('awsbilling.export') }}
            </button>
        @endif

        @if($canEdit)
            <form method="POST" action="{{ route('admin.aws-accounts.billing.refresh', $account) }}">
                @csrf
                <input type="hidden" name="month" value="{{ $monthQ }}">
                <button type="submit" class="q-pill" title="{{ __('awsbilling.refresh_hint') }}" {{ $account->hasCredentials() ? '' : 'disabled' }}>
                    <i class="bi bi-arrow-repeat"></i> {{ __('awsbilling.refresh') }}
                </button>
            </form>
            <button type="button" class="q-pill" data-open-modal="periodModal" title="{{ __('awsbilling.periods_hint') }}">
                <i class="bi bi-scissors"></i> {{ $hasPeriods ? trans_choice('awsbilling.periods_btn_on', count($periods), ['n' => count($periods)]) : __('awsbilling.periods_btn') }}
            </button>
            <button type="button" class="q-pill {{ $shareOn ? '' : 'q-pill-green' }}" data-open-modal="shareModal">
                <i class="bi {{ $shareOn ? 'bi-link-45deg' : 'bi-share' }}"></i> {{ $shareOn ? __('awsbilling.share_btn_on') : __('awsbilling.share_btn') }}
            </button>
        @endif
    </div>
</div>

<div class="bl-meta">
    <span><i class="dot {{ $statusKey === 'complete' ? 'ok' : ($statusKey === 'partial' ? 'warn' : '') }}"></i> {{ __('awsbilling.status_' . $statusKey) }}</span>
    <span><i class="bi bi-database"></i> {{ $lastDataDay ? __('awsbilling.data_until', ['date' => $lastDataDay->translatedFormat('d M Y')]) : __('awsbilling.data_none') }}</span>
    <span title="{{ __('awsbilling.schedule_hint') }}">
        <i class="bi bi-clock-history"></i>
        {{ __('awsbilling.schedule', ['time' => config('aws.usage_refresh_time', '08:00')]) }}
        · {{ $syncedAt ? __('awsbilling.last_sync', ['at' => $syncedAt->wib()->translatedFormat('d M Y, H:i')]) : __('awsbilling.never_synced') }}
    </span>
    @if($syncError && !$public)
        <span class="err" title="{{ $syncError }}"><i class="bi bi-exclamation-triangle-fill"></i> {{ \Illuminate\Support\Str::limit(__('awsbilling.sync_error', ['error' => $syncError]), 80) }}</span>
    @endif
</div>

{{-- AWS menolak karena izin IAM kurang: sebutkan user & izinnya, beri policy siap salin. --}}
@if($syncError && !$public && preg_match('/User: (arn:\S+) is not authorized to perform: ([a-z0-9-]+:[A-Za-z0-9]+)/', $syncError, $denied))
    @php
        $deniedUser = \Illuminate\Support\Str::afterLast($denied[1], '/');
        $deniedPolicy = json_encode([
            'Version'   => '2012-10-17',
            'Statement' => [['Effect' => 'Allow', 'Action' => [$denied[2]], 'Resource' => '*']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    @endphp
    <div class="bl-iam">
        <div class="bl-iam-head">
            <i class="bi bi-shield-lock-fill"></i>
            <div>
                <b>{{ __('awsbilling.iam_title', ['user' => $deniedUser, 'action' => $denied[2]]) }}</b>
                <span>{{ __('awsbilling.iam_body', ['user' => $deniedUser]) }}</span>
            </div>
        </div>
        <div class="bl-iam-code">
            <textarea id="iamPolicy" readonly rows="{{ substr_count($deniedPolicy, "\n") + 1 }}" spellcheck="false">{{ $deniedPolicy }}</textarea>
            <button type="button" class="q-pill" data-copy-text="iamPolicy" data-copied="{{ __('awsbilling.iam_copied') }}"><i class="bi bi-clipboard"></i> {{ __('awsbilling.iam_copy') }}</button>
        </div>
    </div>
@endif

@if(!$hasServices)
    <div class="bl-note"><i class="bi bi-info-circle-fill"></i><span>{{ __('awsbilling.no_services_note') }}</span></div>
@elseif($monthClosed && $lastDataDay && $lastDataDay->format('Y-m-d') < $end->format('Y-m-d'))
    <div class="bl-note"><i class="bi bi-hourglass-split"></i><span>{{ __('awsbilling.partial_note', ['date' => $lastDataDay->translatedFormat('d M Y')]) }}</span></div>
@endif

<div class="bl-top">
    {{-- 1. Angka utama --}}
    <div class="hero" @if($split) data-row="total" data-idr="{{ $split['total']['idr'] }}" data-pct="{{ $split['percent'] ?? '' }}" data-min="0" @endif>
        <div class="hero-top">
            <div class="hero-lbl">{{ $split ? __('awsbilling.hero_label') : __('awsbilling.hero_label_aws') }}</div>
            <div class="hero-month">{{ $start->translatedFormat('F Y') }}</div>
        </div>

        @if($split)
            <div class="hero-val" data-f="total">{{ $rp($split['total']['total']) }}</div>
            <div class="hero-sub">{{ $heroSub }}</div>

            <div class="flow" style="grid-template-columns: {{ implode(' auto ', array_fill(0, 1 + $hasFee + $hasVat, '1fr')) }};">
                <div class="flow-step">
                    <div class="k">{{ __('awsbilling.flow_aws') }}</div>
                    <div class="v" data-f="idr">{{ $rp($split['total']['idr']) }}</div>
                    <div class="s">{{ $usd($split['total']['usd']) }} · {{ __('awsbilling.incl_aws_vat') }}</div>
                </div>
                @if($hasFee)
                    <div class="flow-op">+</div>
                    <div class="flow-step">
                        <div class="k">{{ trim(__('awsbilling.flow_fee') . ' ' . $feePct) }}</div>
                        <div class="v" data-f="fee">{{ $rp($split['total']['fee']) }}</div>
                        <div class="s">PT. Tim Alfa</div>
                    </div>
                @endif
                @if($hasVat)
                    <div class="flow-op">+</div>
                    <div class="flow-step">
                        <div class="k">VAT {{ $vatPct }}%</div>
                        <div class="v" data-f="vat">{{ $rp($split['total']['vat']) }}</div>
                        <div class="s">{{ $hasFee ? __('awsbilling.flow_vat_sub') : __('awsbilling.flow_vat_sub_nofee') }}</div>
                    </div>
                @endif
            </div>

            @if($hasPeriods)
                <div class="pbar" aria-label="{{ __('awsbilling.periods_title') }}">
                    @foreach($periods as $pp)
                        <div class="pbar-seg" style="flex: {{ max($pp['share'], 4) }}; --tone: {{ $pTones[$loop->index % count($pTones)] }};">
                            <span class="pbar-fill"></span>
                            <span class="pbar-l">{{ $pLabel($pp) }}</span>
                            <span class="pbar-v">{{ number_format($pp['share'], 1, ',', '.') }}%</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @php
                $baseRate = $detail['rate'] ?? ($split['total']['idr'] / max($split['total']['usd'], 0.01));
                $rMin = floor($baseRate * 0.85 / 100) * 100;
                $rMax = ceil($baseRate * 1.15 / 100) * 100;
            @endphp
            <div class="fx" data-base="{{ $baseRate }}">
                <div class="fx-head">
                    <span class="fx-lbl"><i class="bi bi-currency-exchange"></i> {{ __('awsbilling.fx_label') }}</span>
                    <label class="fx-val" title="{{ __('awsbilling.fx_type') }}">
                        <span>Rp</span>
                        <input type="text" id="fxVal" inputmode="decimal" autocomplete="off" spellcheck="false"
                               value="{{ number_format($baseRate, 2, ',', '.') }}" aria-label="{{ __('awsbilling.fx_label') }}">
                    </label>
                    <span class="fx-tag" id="fxTag" hidden>{{ __('awsbilling.fx_sim') }}</span>
                    <button type="button" class="fx-reset" id="fxReset" hidden><i class="bi bi-arrow-counterclockwise"></i> {{ __('awsbilling.fx_reset') }}</button>
                </div>
                <input type="range" id="fxRange" min="{{ $rMin }}" max="{{ $rMax }}" step="1" value="{{ round($baseRate) }}"
                       aria-label="{{ __('awsbilling.fx_label') }}">
                <div class="fx-scale">
                    <span>Rp {{ number_format($rMin, 0, ',', '.') }}</span>
                    <span id="fxDelta">{{ __('awsbilling.fx_hint') }}</span>
                    <span>Rp {{ number_format($rMax, 0, ',', '.') }}</span>
                </div>
            </div>
        @else
            <div class="hero-val">{{ $usd($total) }}</div>
            <div class="hero-sub">
                {{ $rate ? $rp(round($total, 2) * $rate) : __('awsbilling.rate_none') }}
                · {{ $taxEstimated ? __('awsbilling.tax_est_short') : __('awsbilling.incl_aws_vat') }}
            </div>
        @endif
    </div>

    {{-- 2. Cek invoice AWS --}}
    <div class="q-card chk">
        @php
            $chk = !$invoice ? 'none' : (!$reconcile ? 'wait' : ($reconcile['matched'] ? 'ok' : 'bad'));
        @endphp
        <div class="chk-state">
            <div class="chk-ic {{ $chk === 'ok' ? 'ok' : ($chk === 'bad' ? 'bad' : '') }}">
                <i class="bi {{ ['none' => 'bi-receipt', 'wait' => 'bi-hourglass-split', 'ok' => 'bi-patch-check-fill', 'bad' => 'bi-exclamation-octagon-fill'][$chk] }}"></i>
            </div>
            <div>
                <div class="chk-t">{{ __('awsbilling.chk_' . $chk) }}</div>
                <div class="chk-s">
                    @if($chk === 'ok')
                        {{ __('awsbilling.matched_sub', ['diff' => $usd(abs($reconcile['diff']))]) }}
                    @elseif($chk === 'bad')
                        {{ __('awsbilling.mismatch_sub', ['diff' => ($reconcile['diff'] > 0 ? '+' : '−') . $usd(abs($reconcile['diff']))]) }}
                    @elseif($chk === 'wait')
                        {{ __('awsbilling.inv_waiting') }}
                    @else
                        {{ __('awsbilling.inv_none_sub') }}
                    @endif
                </div>
            </div>
        </div>

        <ul class="chk-list">
            <li><span>{{ __('awsbilling.chk_invoice') }}</span><b>{{ $invoice ? $usd($invoice->total_usd) . ' · ' . $rp($invoice->total_idr) : '—' }}</b></li>
            <li><span>{{ __('awsbilling.chk_app') }}</span><b>{{ $usd($total) }}</b></li>
            <li>
                <span>{{ __('awsbilling.chk_rate') }}</span>
                <b title="{{ $rateSource === 'table' ? $storedRate->source . ', ' . $storedRate->rate_date->translatedFormat('d M Y') : '' }}">
                    {{ $rate ? 'Rp ' . number_format($rate, 2, ',', '.') : '—' }}
                    @if($rateSource === 'table')<small style="color:var(--faint);font-weight:600;">({{ __('awsbilling.rate_from_table') }})</small>@endif
                    @if($rateSource === 'aws')<small style="color:var(--faint);font-weight:600;">(AWS)</small>@endif
                </b>
            </li>
        </ul>

        @if($showBill)
            <div class="bs">
                <div class="bs-top">
                    <span class="bs-k">{{ __('awsbilling.bs_title') }}</span>
                    <span class="bs-pill {{ $billStatus }}">
                        <i class="bi {{ ['issued' => 'bi-check2-circle', 'pending' => 'bi-hourglass-split', 'denied' => 'bi-shield-lock', 'error' => 'bi-exclamation-triangle', 'none' => 'bi-dash-circle', 'unknown' => 'bi-question-circle'][$billStatus] }}"></i>
                        {{ __('awsbilling.bs_' . $billStatus) }}
                    </span>
                </div>

                @foreach($awsInvoices as $ai)
                    <div class="bs-inv">
                        <span class="bs-no">{{ $ai->invoice_id }}@if($ai->isCreditMemo())<small>{{ __('awsbilling.bs_credit_memo') }}</small>@endif
                            @unless($public)
                                <a class="bs-pdf" href="{{ route('admin.aws-accounts.billing.invoice-pdf', [$account, $ai->invoice_id]) }}" target="_blank" rel="noopener" title="{{ __('awsbilling.bs_pdf_title') }}"><i class="bi bi-download"></i> PDF</a>
                            @endunless
                        </span>
                        <span class="bs-amt">{{ $ai->base_currency === 'USD' ? $usd($ai->base_amount) : number_format((float) $ai->base_amount, 2, ',', '.') . ' ' . $ai->base_currency }}</span>
                        <span class="bs-sub">
                            {{ __('awsbilling.bs_issued_on', ['date' => $ai->issued_at?->wib()->translatedFormat('d M Y') ?? '—']) }}
                            @if($ai->due_at)
                                · <span class="bs-due {{ $ai->isPastDue() ? 'late' : '' }}">{{ __('awsbilling.bs_due_on', ['date' => $ai->due_at->wib()->translatedFormat('d M Y')]) }}</span>
                            @endif
                        </span>
                        <span class="bs-sub r">{{ $ai->idrAmount() !== null ? $rp($ai->idrAmount()) : '' }}</span>
                    </div>
                @endforeach

                @if($billStatus !== 'issued')
                    <div class="bs-note">
                        @if($billStatus === 'denied')
                            {!! __('awsbilling.bs_denied_sub', ['perm' => '<code>invoicing:ListInvoiceSummaries</code>']) !!}
                        @elseif($billStatus === 'error')
                            {{ __('awsbilling.bs_error_sub') }} <code>{{ $account->invoices_error }}</code>
                        @else
                            {{ __('awsbilling.bs_' . $billStatus . '_sub') }}
                        @endif
                    </div>
                @elseif($awsMain?->isPastDue())
                    <div class="bs-note">{{ __('awsbilling.bs_past_due_sub') }}</div>
                @endif
            </div>
        @endif

        <div class="chk-foot">
            <span class="chk-by">
                @if($invoice)
                    {{ $invoice->invoice_number }}{{ $invoice->invoice_number && !$public ? ' · ' : '' }}
                    @if($invoiceSource === 'aws')
                        {{ __('awsbilling.inv_from_aws') }}
                    @else
                        @unless($public){{ __('awsbilling.inv_by', ['by' => $invoice->created_by ?? '—', 'ago' => $invoice->updated_at?->diffForHumans()]) }}@endunless
                    @endif
                @endif
            </span>
            @if($canEdit)
                <button type="button" class="{{ $invoice ? 'btn-soft' : 'btn-solid' }}" data-open-modal="invModal">
                    <i class="bi {{ $invoice ? 'bi-pencil' : 'bi-plus-lg' }}"></i> {{ $invoice ? __('awsbilling.inv_edit') : __('awsbilling.inv_fill') }}
                </button>
            @endif
        </div>
    </div>
</div>

{{-- 3a. Per periode (kalau bulan dipecah) --}}
@if($hasPeriods)
    <div class="sec-head">
        <div>
            <div class="sec-title">{{ __('awsbilling.periods_title') }}</div>
            <div class="sec-sub">{{ __('awsbilling.periods_sub') }}</div>
        </div>
    </div>
    <div class="pd-grid">
        @foreach($periods as $pp)
            @php $pt = $pp['split']['total']; @endphp
            <div class="q-card pd" style="--tone: {{ $pTones[$loop->index % count($pTones)] }}; animation-delay: {{ $loop->index * 50 }}ms;"
                 data-row="per" data-idr="{{ $pt['idr'] }}" data-fr="{{ $feeRatio($pt) }}">
                <div class="pd-head">
                    <span class="pd-dot"></span>
                    <span class="pd-name">{{ $pLabel($pp) }}</span>
                    <span class="pd-share">{{ number_format($pp['share'], 1, ',', '.') }}%</span>
                </div>
                <div class="pd-total" data-f="total">{{ $rp($pt['total']) }}</div>
                <div class="pd-sub">{{ $usd($pt['usd']) }} · {{ trans_choice('awsbilling.n_days', $pp['start']->diffInDays($pp['end']) + 1, ['n' => $pp['start']->diffInDays($pp['end']) + 1]) }}</div>
                <div class="co-lines">
                    <div class="co-line"><span>{{ __('awsbilling.flow_aws') }}</span><b data-f="idr">{{ $rp($pt['idr']) }}</b></div>
                    @if($hasFee)<div class="co-line"><span>{{ trim(__('awsbilling.flow_fee') . ' ' . $feePct) }}</span><b data-f="fee">{{ $rp($pt['fee']) }}</b></div>@endif
                    @if($hasVat)<div class="co-line"><span>VAT {{ $vatPct }}%</span><b data-f="vat">{{ $rp($pt['vat']) }}</b></div>@endif
                </div>
                @if(count($pp['split']['rows']))
                    <div class="pd-cos">
                        @foreach($pp['split']['rows'] as $r)
                            <div class="pd-co" data-row="per" data-idr="{{ $r['idr'] }}" data-fr="{{ $feeRatio($r) }}">
                                <span class="n">{{ $r['name'] ?? __('awsbilling.unassigned') }} <small>{{ number_format($r['share'], 1, ',', '.') }}%</small></span>
                                <b data-f="total">{{ $rp($r['total']) }}</b>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="kb-zero">{{ __('awsbilling.no_usage') }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- 3. Per perusahaan --}}
<div class="sec-head">
    <div>
        <div class="sec-title">{{ __('awsbilling.co_title') }}</div>
        <div class="sec-sub">{{ $invoice ? __('awsbilling.co_sub_invoice') : __('awsbilling.co_sub_app') }}</div>
    </div>
</div>

@if($split && $detail)
    @php $detailByName = collect($detail['companies'])->keyBy(fn ($c) => $c['name'] ?? ''); @endphp
    <div class="co-grid">
        @foreach($split['rows'] as $row)
            @php
                $co = $detailByName[$row['name'] ?? ''] ?? null;
                // Singkatan di dalam kurung dipakai kalau ada: "PT Kereta Api Indonesia (KAI)" → KAI.
                $initials = !$row['name'] ? '?' : (preg_match('/\(([^)]{2,4})\)/', $row['name'], $mm)
                    ? mb_strtoupper($mm[1])
                    : mb_strtoupper(mb_substr(preg_replace('/^(PT\.?|CV\.?)\s+/i', '', $row['name']), 0, 2)));
            @endphp
            <div class="q-card co" style="animation-delay: {{ $loop->index * 50 }}ms;" data-row="co" data-idr="{{ $row['idr'] }}" data-pct="{{ $row['percent'] }}" data-min="{{ $row['min'] }}">
                <div class="co-head">
                    <div class="co-av {{ $row['name'] ? '' : 'none' }}">{{ $initials }}</div>
                    <div style="min-width:0;">
                        <div class="co-name">{{ $row['name'] ?? __('awsbilling.unassigned') }}</div>
                        <div class="co-keys">{{ trans_choice('awsbilling.n_keys', count($row['keys']), ['n' => count($row['keys'])]) }}</div>
                    </div>
                    <div class="co-total">
                        <div class="k">{{ __('awsbilling.co_total') }}</div>
                        <div class="v" data-f="total">{{ $rp($row['total']) }}</div>
                    </div>
                </div>

                <div class="co-share">
                    <div class="co-bar"><span style="width: {{ max(1, $row['share']) }}%;"></span></div>
                    <b>{{ number_format($row['share'], 1, ',', '.') }}%</b>
                </div>

                <div class="co-lines">
                    <div class="co-line"><span>{{ __('awsbilling.flow_aws') }} <small>({{ $usd($row['usd']) }})</small></span><b data-f="idr">{{ $rp($row['idr']) }}</b></div>
                    @if($hasFee)<div class="co-line"><span>{{ trim(__('awsbilling.flow_fee') . ' ' . ($split['percent'] !== null ? $feePct : $pct($row['percent']) . '%')) }}</span><b data-f="fee">{{ $rp($row['fee']) }}</b></div>@endif
                    @if($hasVat)<div class="co-line"><span>VAT {{ $vatPct }}%</span><b data-f="vat">{{ $rp($row['vat']) }}</b></div>@endif
                </div>

                @if($hasPeriods)
                    <div class="co-periods">
                        @foreach($periods as $pp)
                            @php $pr = collect($pp['split']['rows'])->first(fn ($x) => $x['name'] === $row['name']); @endphp
                            <div class="co-period" @if($pr) data-row="per" data-idr="{{ $pr['idr'] }}" data-fr="{{ $feeRatio($pr) }}" @endif>
                                <span><i style="background: {{ $pTones[$loop->index % count($pTones)] }};"></i> {{ $pLabel($pp) }}</span>
                                <b @if($pr) data-f="total" @endif>{{ $pr ? $rp($pr['total']) : '—' }}</b>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($co)
                    <details data-kb-detail>
                        <summary><i class="bi bi-chevron-right"></i> {{ __('awsbilling.co_detail') }}</summary>

                        <div class="kb-tools">
                            <span>{{ __('awsbilling.view_label') }}</span>
                            <div class="seg" role="group" aria-label="{{ __('awsbilling.view_label') }}">
                                <button type="button" class="on" data-view="cost" aria-pressed="true"><i class="bi bi-sort-down"></i> {{ __('awsbilling.view_cost') }}</button>
                                <button type="button" data-view="group" aria-pressed="false"><i class="bi bi-collection"></i> {{ __('awsbilling.view_group') }}</button>
                            </div>
                        </div>

                        @foreach($co['keys'] as $kb)
                            @php
                                $lines = collect($kb['categories'])->flatMap(fn ($c) => collect($c['lines'])->map(fn ($l) => $l + ['cat' => $c['name']]));
                                $used = $lines->filter(fn ($l) => $l['count'] > 0)->sortByDesc('usd')->values();
                                $zero = $lines->count() - $used->count();
                                $keyUsd = $kb['usd'] ?: 1;
                                $groups = collect($kb['categories'])
                                    ->map(fn ($c) => $c + ['used' => collect($c['lines'])->filter(fn ($l) => $l['count'] > 0)->sortByDesc('usd')->values()])
                                    ->filter(fn ($c) => $c['used']->count())
                                    ->sortByDesc('usd');
                            @endphp
                            <div class="kb">
                                <div class="kb-head">
                                    <span class="kb-name"><i class="bi bi-key" style="color:var(--muted);"></i> {{ $kb['name'] }}</span>
                                    <span class="kb-sum"><span data-lin="{{ $kb['idr'] }}">{{ $rp($kb['idr']) }}</span><small>{{ $usd($kb['usd']) }} {{ __('awsbilling.before_tax') }}</small></span>
                                </div>

                                @if($used->isEmpty())
                                    <div class="kb-zero">{{ __('awsbilling.no_usage') }}</div>
                                @else
                                    {{-- Tampilan 1: semua operasi, biaya terbesar dulu --}}
                                    <div class="kb-view" data-view="cost">
                                        @foreach($used as $ln)
                                            <div class="op">
                                                <div class="op-n">
                                                    <span class="cat">{{ $ln['cat'] }}</span><span class="nm">{{ $ln['op'] }}</span>
                                                    <span class="rq">{{ $num($ln['count']) }} {{ __('awsbilling.req_unit') }}</span>
                                                </div>
                                                <div class="u">{{ $usd($ln['usd']) }}</div>
                                                <div class="i" data-lin="{{ $ln['idr'] }}">{{ $rp($ln['idr']) }}</div>
                                                <div class="op-bar"><span style="width: {{ max(0.8, $ln['usd'] / $keyUsd * 100) }}%;"></span></div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Tampilan 2: per kategori dengan subtotal --}}
                                    <div class="kb-view" data-view="group" hidden>
                                        @foreach($groups as $g)
                                            <div class="grp">
                                                <div class="grp-h">
                                                    <span class="grp-n">
                                                        <span class="cat">{{ $g['name'] }}</span>
                                                        <span class="grp-c">{{ trans_choice('awsbilling.n_ops', $g['used']->count(), ['n' => $g['used']->count()]) }} · {{ number_format($g['usd'] / $keyUsd * 100, 1, ',', '.') }}%</span>
                                                    </span>
                                                    <span class="u">{{ $usd($g['usd']) }}</span>
                                                    <span class="i" data-lin="{{ $g['idr'] }}">{{ $rp($g['idr']) }}</span>
                                                </div>
                                                @foreach($g['used'] as $ln)
                                                    <div class="op sub">
                                                        <div class="op-n">
                                                            <span class="nm">{{ $ln['op'] }}</span>
                                                            <span class="rq">{{ $num($ln['count']) }} {{ __('awsbilling.req_unit') }}</span>
                                                        </div>
                                                        <div class="u">{{ $usd($ln['usd']) }}</div>
                                                        <div class="i" data-lin="{{ $ln['idr'] }}">{{ $rp($ln['idr']) }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($zero)
                                        <div class="kb-zero">{{ trans_choice('awsbilling.zero_ops', $zero, ['n' => $zero]) }}</div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                        <div class="co-note">{{ __('awsbilling.co_detail_note') }}</div>
                    </details>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="q-card empty-card" style="margin-bottom:22px;">
        {{ count($keys) ? __('awsbilling.split_no_rate') : __('awsbilling.empty_keys') }}
    </div>
@endif

{{-- 4. Detail teknis AWS --}}
<details class="tech">
    <summary>
        <div class="q-icon-box"><i class="bi bi-cloud"></i></div>
        <div>
            <div class="q-card-title">{{ __('awsbilling.tech_title') }}</div>
            <div class="q-card-sub">{{ __('awsbilling.tech_sub', ['total' => $usd($total)]) }}</div>
        </div>
        <span class="chev"><i class="bi bi-chevron-down"></i></span>
    </summary>

    <div class="tech-body">
        <div class="tech-grid">
            <div>
                <h4>{{ __('awsbilling.by_service') }}</h4>
                @if($hasServices)
                    <div class="bl-scroll">
                    <table class="bl-table">
                        <tbody>
                            @foreach($services['services'] as $name => $cost)
                                <tr>
                                    <td>{{ $name }}</td>
                                    <td class="r" style="color:var(--muted);">{{ number_format($cost / $serviceTotal * 100, 1, ',', '.') }}%</td>
                                    <td class="r">{{ $usd($cost) }}</td>
                                </tr>
                            @endforeach
                            <tr class="muted">
                                <td>{{ $taxEstimated ? __('awsbilling.tax_est', ['pct' => $pct($taxRate * 100)]) : 'Tax (' . __('awsbilling.tax') . ')' }}</td>
                                <td></td>
                                <td class="r">{{ $usd($tax) }}</td>
                            </tr>
                        </tbody>
                        <tfoot><tr><td>{{ __('awsbilling.total') }}</td><td></td><td class="r">{{ $usd($total) }}</td></tr></tfoot>
                    </table>
                    </div>
                @else
                    <div class="empty-card">{{ __('awsbilling.no_services_note') }}</div>
                @endif

                <h4 style="margin-top:22px;">{{ __('awsbilling.daily') }}</h4>
                @php
                    $billedDays = array_filter($daily, fn ($c) => $c > 0);
                    $dailyAvg = $billedDays ? array_sum($billedDays) / count($billedDays) : 0;
                    $peakDate = $billedDays ? array_search(max($billedDays), $billedDays) : null;
                    $dayCount = count($daily);
                @endphp
                @if($billedDays)
                    <div class="bl-stats">
                        <span>{{ __('awsbilling.daily_avg') }} <b>{{ $usd($dailyAvg) }}</b></span>
                        <span>{{ __('awsbilling.daily_peak') }} <b>{{ $usd($billedDays[$peakDate]) }}</b> · {{ \Carbon\Carbon::parse($peakDate)->translatedFormat('d M') }}</span>
                    </div>
                @endif
                <div class="bl-chart">
                    @if($dailyAvg > 0)
                        {{-- Garis rata-rata; tinggi area batang = 100% dari tinggi chart dikurangi padding atas. --}}
                        <span class="avg" style="bottom: calc((100% - 46px) * {{ $dailyAvg / $dailyMax }});" title="{{ __('awsbilling.daily_avg') }}"></span>
                    @endif
                    @foreach($daily as $date => $cost)
                        <div class="b {{ in_array((int) substr($date, 8, 2), $cuts, true) && $hasPeriods ? 'cut' : '' }} {{ $cost > 0 ? '' : 'zero' }} {{ $date === $peakDate ? 'peak' : '' }} {{ $loop->index < 3 ? 'edge-l' : ($loop->index >= $dayCount - 3 ? 'edge-r' : '') }}">
                            <span class="bar" style="height: {{ $cost > 0 ? max(2, $cost / $dailyMax * 100) : 0 }}%;">
                                <span class="tip">{{ \Carbon\Carbon::parse($date)->translatedFormat('D, d M') }}<b>{{ $usd($cost) }}</b></span>
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="bl-axis">
                    <span>{{ $start->translatedFormat('d M') }}</span>
                    <span>{{ $start->copy()->addDays(14)->translatedFormat('d M') }}</span>
                    <span>{{ $end->translatedFormat('d M') }}</span>
                </div>
            </div>

            <div>
                <h4>{{ __('awsbilling.by_usage') }}</h4>
                @if(count($usage['rows']))
                    <div class="bl-scroll">
                        <table class="bl-table">
                            <thead><tr><th>{{ __('awsbilling.usage_type') }}</th><th class="r">{{ __('awsbilling.quantity') }}</th><th class="r">{{ __('awsbilling.cost') }}</th></tr></thead>
                            <tbody>
                                @foreach($usage['rows'] as $row)
                                    <tr class="{{ $row['cost'] > 0.005 ? '' : 'muted' }}">
                                        <td>
                                            <div>{{ $products[$row['product']] ?? $row['product'] }}</div>
                                            <div class="bl-code">{{ $row['usage_type'] }}</div>
                                        </td>
                                        <td class="r">{{ $num($row['quantity']) }}</td>
                                        <td class="r">{{ $usd($row['cost']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot><tr><td>{{ __('awsbilling.total') }}</td><td></td><td class="r">{{ $usd($usage['total_cost']) }}</td></tr></tfoot>
                        </table>
                    </div>
                @else
                    <div class="empty-card">{{ __('awsbilling.empty_usage') }}</div>
                @endif
            </div>
        </div>
    </div>
</details>


{{-- Modal export CSV — admin dan link publik sama. --}}
@if($canExport)
    @php
        $exCols = \App\Services\BillingExport::columnsFor(['split' => $split]);
        $exPresets = array_filter([
            'all' => $exCols,
            'aws' => ['usd', 'aws'],
            'fee' => array_values(array_intersect(['fee', 'vat'], $exCols)) ?: null,
        ]);
        // Judul kolom sama persis dengan di file (fee membawa persennya kalau seragam).
        $exColLabel = fn ($c) => $c === 'fee' && $split['percent'] !== null
            ? __('awsbilling.csv_col_fee_pct', ['fee' => $pct($split['percent'])])
            : __('awsbilling.csv_col_' . $c, ['pct' => $vatPct]);

        // Pratinjau: angka asli halaman ini, dibentuk ulang di browser sesuai pilihan.
        $exNums = fn ($r) => array_intersect_key($r, array_flip(['usd', 'idr', 'fee', 'invoice', 'vat', 'total']));
        // Baris operasi (yang dipakai saja), bentuknya sama dengan tabel operasi di file.
        $exOps = function (?array $det) {
            $out = [];
            foreach ($det['companies'] ?? [] as $co) {
                foreach ($co['keys'] as $kb) {
                    foreach ($kb['categories'] as $cat) {
                        foreach ($cat['lines'] as $ln) {
                            if ($ln['count'] > 0) {
                                $out[] = ['id' => (int) ($co['company_id'] ?? 0), 'name' => $co['name'] ?? __('awsbilling.unassigned'),
                                    'key' => $kb['name'], 'cat' => $cat['name'], 'op' => $ln['op'], 'n' => $ln['count'], 'usd' => $ln['usd'], 'keyUsd' => $kb['usd']];
                            }
                        }
                    }
                }
            }
            return $out;
        };
        $exSec = fn ($label, $sp, $det) => [
            'ops'   => $exOps($det),
            'label' => $label,
            'pct'   => $sp['percent'],
            'total' => $exNums($sp['total']),
            'rows'  => array_map(fn ($r) => [
                'id'    => (int) ($r['company_id'] ?? 0),
                'name'  => $r['name'] ?? __('awsbilling.unassigned'),
                'share' => $r['share'],
                'pct'   => $r['percent'],
                'v'     => $exNums($r),
                'keys'  => array_map(fn ($k) => ['name' => $k['name'], 'share' => $k['share'], 'v' => $exNums($k)], $r['keys'] ?? []),
            ], $sp['rows']),
        ];
        $exSections = ['month' => $exSec(__('awsbilling.csv_sec_month', ['range' => $start->format('j') . '–' . $end->translatedFormat('j M Y')]), $split, $detail)];
        foreach ($periods as $i => $pp) {
            $exSections[(string) $i] = $exSec(__('awsbilling.csv_sec_period', [
                'range' => $pp['start']->format('j') . '–' . $pp['end']->translatedFormat('j M Y'),
                'share' => $pct($pp['share']),
            ]), $pp['split'], $pp['detail'] ?? null);
        }
        $exData = [
            'sections' => $exSections,
            'cols'     => collect($exCols)->mapWithKeys(fn ($c) => [$c => $exColLabel($c)]),
            'L'        => collect(['h_level', 'h_company', 'h_key', 'h_share', 'h_fee_pct', 'lv_company', 'lv_key', 'lv_total', 'lv_total_sel',
                'lv_op', 'h_category', 'h_operation', 'h_requests'])
                ->mapWithKeys(fn ($k) => [$k => __('awsbilling.csv_' . $k)]),
            'hints'    => collect(['company', 'key', 'op'])->mapWithKeys(fn ($k) => [$k => __('awsbilling.export_hint_level_' . $k)]),
            'more'     => __('awsbilling.export_preview_more'),
            'fmt'      => collect(['id', 'intl'])->mapWithKeys(fn ($f) => [$f => 'CSV · ' . __('awsbilling.export_fmt_' . $f) . ' · ' . __('awsbilling.export_fmt_' . $f . '_hint')])
                ->put('xlsx', __('awsbilling.export_type_xlsx') . ' · Rp 1.234 · $1,23'),
            'dl'       => ['xlsx' => __('awsbilling.export_download_xlsx'), 'csv' => __('awsbilling.export_download')],
        ];
    @endphp
    <script type="application/json" id="exData">{!! json_encode($exData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
    <div class="gm-modal ex-modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportTitle">
        <div class="gm-modal-card ex-card">
            <div class="gm-modal-title" id="exportTitle">{{ __('awsbilling.export_title', ['month' => $start->translatedFormat('F Y')]) }}</div>
            <div class="gm-modal-sub">{{ __('awsbilling.export_sub') }}</div>

            <form method="GET" action="{{ $exportUrl }}" id="exportForm" data-no-loader>
                <input type="hidden" name="month" value="{{ $monthQ }}">
                @if($public)<input type="hidden" name="lang" value="{{ app()->getLocale() }}">@endif
                <input type="hidden" name="rate" value="" id="exRate" disabled>

                <div class="ex-layout">
                <div class="ex-opts">
                {{-- Perusahaan --}}
                <fieldset class="ex-group" data-need-one>
                    <legend>{{ __('awsbilling.export_companies') }}</legend>
                    <label class="ex-check ex-all">
                        <input type="checkbox" id="exAll" checked>
                        <span class="n">{{ __('awsbilling.export_all') }}</span>
                        <small>{{ count($split['rows']) }}</small>
                    </label>
                    <div class="ex-list">
                        @foreach($split['rows'] as $row)
                            <label class="ex-check">
                                <input type="checkbox" name="co[]" value="{{ (int) ($row['company_id'] ?? 0) }}" checked data-co>
                                <span class="n">{{ $row['name'] ?? __('awsbilling.unassigned') }}</span>
                                <small>{{ $rp($row['total']) }}</small>
                            </label>
                        @endforeach
                    </div>
                    <div class="ex-hint">{{ __('awsbilling.export_hint_companies') }}</div>
                    <div class="ex-err" hidden>{{ __('awsbilling.export_pick_one') }}</div>
                </fieldset>

                {{-- Periode (hanya kalau bulannya dipecah) --}}
                @if($hasPeriods)
                    <fieldset class="ex-group" data-need-one>
                        <legend>{{ __('awsbilling.export_periods') }}</legend>
                        <div class="ex-chips">
                            <label class="ex-chip"><input type="checkbox" name="per[]" value="month" checked><span>{{ __('awsbilling.export_full_month') }}</span></label>
                            @foreach($periods as $i => $pp)
                                <label class="ex-chip"><input type="checkbox" name="per[]" value="{{ $i }}" checked><span>{{ $pLabel($pp) }}</span></label>
                            @endforeach
                        </div>
                        <div class="ex-hint">{{ __('awsbilling.export_hint_periods') }}</div>
                        <div class="ex-err" hidden>{{ __('awsbilling.export_pick_one') }}</div>
                    </fieldset>
                @else
                    <input type="hidden" name="per[]" value="month">
                @endif

                {{-- Tingkat rincian --}}
                <fieldset class="ex-group">
                    <legend>{{ __('awsbilling.export_level') }}</legend>
                    <div class="ex-seg">
                        @foreach(['company', 'key', 'op'] as $lv)
                            <label><input type="radio" name="level" value="{{ $lv }}" @checked($lv === 'key')><span>{{ __('awsbilling.export_level_' . $lv) }}</span></label>
                        @endforeach
                    </div>
                    <div class="ex-hint" id="exLevelHint">{{ __('awsbilling.export_hint_level_key') }}</div>
                </fieldset>

                {{-- Kolom --}}
                <fieldset class="ex-group" data-need-one>
                    <legend>
                        <span>{{ __('awsbilling.export_cols') }}</span>
                        <span class="ex-presets">
                            @foreach($exPresets as $key => $cols)
                                <button type="button" data-preset="{{ implode(',', $cols) }}">{{ __('awsbilling.export_preset_' . $key) }}</button>
                            @endforeach
                        </span>
                    </legend>
                    <div class="ex-chips">
                        @foreach($exCols as $c)
                            <label class="ex-chip"><input type="checkbox" name="cols[]" value="{{ $c }}" checked><span>{{ $exColLabel($c) }}</span></label>
                        @endforeach
                    </div>
                    <div class="ex-hint">{{ __('awsbilling.export_hint_cols', ['pct' => $vatPct]) }}</div>
                    <div class="ex-err" hidden>{{ __('awsbilling.export_pick_one') }}</div>
                </fieldset>


                {{-- Format angka — hanya CSV; Excel menyimpan angka asli berformat Rp/$ --}}
                <fieldset class="ex-group" id="exFmtGroup" hidden>
                    <legend>
                        <span>{{ __('awsbilling.export_format') }}</span>
                        <span class="ex-pretty">
                            <input type="hidden" name="pretty" value="0">
                            <label class="ex-chip"><input type="checkbox" name="pretty" value="1" checked><span>{{ __('awsbilling.export_pretty') }}</span></label>
                        </span>
                    </legend>
                    <div class="ex-seg">
                        @foreach(['id', 'intl'] as $f)
                            <label>
                                <input type="radio" name="fmt" value="{{ $f }}" @checked($f === 'id')>
                                <span>{{ __('awsbilling.export_fmt_' . $f) }}<small>{{ __('awsbilling.export_fmt_' . $f . '_hint') }}</small></span>
                            </label>
                        @endforeach
                    </div>
                    <div class="ex-hint">{{ __('awsbilling.export_pretty_hint') }}</div>
                </fieldset>
                </div>{{-- /ex-opts --}}

                <div class="ex-side">
                {{-- Jenis file --}}
                <fieldset class="ex-group">
                    <legend>{{ __('awsbilling.export_type') }}</legend>
                    <div class="ex-seg">
                        @foreach(['xlsx' => 'bi-file-earmark-spreadsheet', 'csv' => 'bi-filetype-csv'] as $tp => $icon)
                            <label>
                                <input type="radio" name="type" value="{{ $tp }}" @checked($tp === 'xlsx')>
                                <span><span class="ex-type-l"><i class="bi {{ $icon }}"></i> {{ __('awsbilling.export_type_' . $tp) }}</span><small>{{ __('awsbilling.export_type_' . $tp . '_hint') }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- Contoh isi file: ikut berubah setiap pilihan diganti --}}
                <div class="ex-prev">
                    <div class="ex-prev-head">
                        <span><i class="bi bi-eye"></i> {{ __('awsbilling.export_preview') }}</span>
                        <span class="ex-fmt-badge" id="exFmtBadge"></span>
                    </div>
                    <div class="ex-prev-scroll"><table id="exPrevTable"></table></div>
                    <div class="ex-prev-note" id="exPrevNote" hidden></div>
                    <div class="ex-prev-note" id="exPrevSim" hidden><i class="bi bi-exclamation-triangle"></i> {{ __('awsbilling.export_preview_sim') }}</div>
                    <div class="ex-prev-raw" id="exPrevRawBox">
                        <span class="lbl">{{ __('awsbilling.export_preview_raw') }}</span>
                        <code id="exPrevRaw"></code>
                    </div>
                </div>

                {{-- Kurs yang akan tertulis di file --}}
                <div class="ex-rate">
                    <i class="bi bi-currency-exchange"></i>
                    <div>
                        <b>{{ __('awsbilling.export_rate') }}: 1 USD = <span id="exRateText">Rp {{ number_format($detail['rate'], 2, ',', '.') }}</span></b>
                        <span class="ex-tag" id="exRateTag" hidden>{{ __('awsbilling.export_rate_sim') }}</span>
                        <small>{{ __('awsbilling.export_rate_note') }}</small>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="button" class="ex-btn" data-close>{{ __('ui.cancel') }}</button>
                    <button type="submit" class="ex-btn primary"><i class="bi bi-download"></i> <span id="exDlLabel">{{ __('awsbilling.export_download_xlsx') }}</span></button>
                </div>
                </div>{{-- /ex-side --}}
                </div>{{-- /ex-layout --}}
            </form>
        </div>
    </div>
@endif

{{-- Modal isi / ubah invoice --}}
@if($canEdit)
    <div class="gm-modal {{ $openInvoice ? 'open' : '' }}" id="invModal" role="dialog" aria-modal="true" aria-labelledby="invTitle">
        <div class="gm-modal-card">
            <div class="gm-modal-title" id="invTitle">{{ __('awsbilling.inv_modal_title', ['month' => $start->translatedFormat('F Y')]) }}</div>
            <div class="gm-modal-sub">{{ __('awsbilling.inv_modal_sub') }}@if($prefill) {{ __('awsbilling.inv_prefilled') }}@endif</div>

            <form method="POST" action="{{ route('admin.aws-accounts.billing.invoice', $account) }}" data-validate>
                @csrf
                <input type="hidden" name="month" value="{{ $monthQ }}">

                <div class="form-field">
                    <label class="form-label-sm">{{ __('awsbilling.inv_usd') }}<span class="req">*</span></label>
                    <div class="with-prefix">
                        <span class="pfx">USD</span>
                        <input type="text" name="total_usd" required inputmode="decimal" autocomplete="off"
                               class="form-input @error('total_usd') is-invalid @enderror" placeholder="790.02"
                               value="{{ old('total_usd', $invoice ? number_format($invoice->total_usd, 2, '.', '') : '') }}">
                    </div>
                    @error('total_usd')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('awsbilling.inv_idr') }}<span class="req">*</span></label>
                    <div class="with-prefix">
                        <span class="pfx">IDR</span>
                        <input type="text" name="total_idr" required inputmode="numeric" autocomplete="off"
                               class="form-input @error('total_idr') is-invalid @enderror" placeholder="14,364,343"
                               value="{{ old('total_idr', $invoice ? number_format($invoice->total_idr, 0, ',', '.') : '') }}">
                    </div>
                    @error('total_idr')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="f-row">
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('awsbilling.inv_number') }} <span class="opt">({{ __('awsbilling.optional') }})</span></label>
                        <input type="text" name="invoice_number" maxlength="80" class="form-input" value="{{ old('invoice_number', $invoice?->invoice_number) }}">
                    </div>
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('awsbilling.inv_note') }} <span class="opt">({{ __('awsbilling.optional') }})</span></label>
                        <input type="text" name="note" maxlength="255" class="form-input" value="{{ old('note', $invoice?->note) }}">
                    </div>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn-solid"><i class="bi bi-check2"></i> {{ __('awsbilling.inv_save') }}</button>
                </div>
            </form>
        </div>
    </div>
    {{-- Modal pecah periode --}}
    <div class="gm-modal" id="periodModal" role="dialog" aria-modal="true" aria-labelledby="periodTitle">
        <div class="gm-modal-card">
            <div class="gm-modal-title" id="periodTitle">{{ __('awsbilling.periods_modal_title', ['month' => $start->translatedFormat('F Y')]) }}</div>
            <div class="gm-modal-sub">{{ __('awsbilling.periods_modal_sub') }}</div>

            <form method="POST" action="{{ route('admin.aws-accounts.billing.periods', $account) }}" id="periodForm">
                @csrf
                <input type="hidden" name="month" value="{{ $monthQ }}">
                <div class="cal">
                    {{-- 1 Jan 2024 jatuh hari Senin: dipakai untuk nama hari sesuai bahasa. --}}
                    @for($i = 0; $i < 7; $i++)
                        <span class="cal-dn">{{ \Carbon\Carbon::create(2024, 1, 1 + $i)->translatedFormat('D') }}</span>
                    @endfor
                    @for($i = 1; $i < $start->dayOfWeekIso; $i++)<span></span>@endfor
                    @for($d = 1; $d <= $start->daysInMonth; $d++)
                        <button type="button" class="cal-d {{ in_array($d, $cuts, true) ? 'on' : '' }}" data-day="{{ $d }}"
                                {{ $d === $start->daysInMonth ? 'disabled' : '' }}>{{ $d }}</button>
                    @endfor
                </div>
                <div class="cal-prev" id="periodPreview"></div>
                <div id="periodInputs"></div>

                <div class="btn-row">
                    <button type="button" class="btn-soft" id="periodClear">{{ __('awsbilling.periods_clear') }}</button>
                    <button type="submit" class="btn-solid"><i class="bi bi-check2"></i> {{ __('awsbilling.periods_save') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal link publik --}}
    <div class="gm-modal {{ request()->boolean('share') ? 'open' : '' }}" id="shareModal" role="dialog" aria-modal="true" aria-labelledby="shareTitle">
        <div class="gm-modal-card">
            <div class="gm-modal-title" id="shareTitle">{{ __('awsbilling.share_title') }}</div>
            <div class="gm-modal-sub">{{ __('awsbilling.share_sub', ['account' => $account->name]) }}</div>

            @if($shareOn)
                <div class="sh-link">
                    <input type="text" readonly value="{{ $share->url($monthQ) }}" id="shareUrl" onclick="this.select()">
                    <button type="button" class="btn-solid" data-copy-text="shareUrl"><i class="bi bi-clipboard"></i> {{ __('awsbilling.share_copy') }}</button>
                </div>
                <div class="sh-stats">
                    <span><i class="bi bi-eye"></i> {{ trans_choice('awsbilling.share_views', $share->view_count, ['n' => $share->view_count]) }}</span>
                    <span>{{ $share->last_viewed_at ? __('awsbilling.share_last_view', ['ago' => $share->last_viewed_at->diffForHumans()]) : __('awsbilling.share_never_viewed') }}</span>
                    <a href="{{ $share->url($monthQ) }}" target="_blank" rel="noopener">{{ __('awsbilling.share_open') }} <i class="bi bi-box-arrow-up-right"></i></a>
                </div>
                <div class="sh-warn"><i class="bi bi-shield-exclamation"></i><span>{{ __('awsbilling.share_warn') }}</span></div>
                <div class="btn-row">
                    <form method="POST" action="{{ route('admin.aws-accounts.billing.share.regenerate', $account) }}" onsubmit="return confirm(@js(__('awsbilling.share_regen_confirm')))">
                        @csrf <input type="hidden" name="month" value="{{ $monthQ }}">
                        <button type="submit" class="btn-soft" style="width:100%;"><i class="bi bi-arrow-repeat"></i> {{ __('awsbilling.share_regen') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.aws-accounts.billing.share.disable', $account) }}">
                        @csrf <input type="hidden" name="month" value="{{ $monthQ }}">
                        <button type="submit" class="btn-solid danger" style="width:100%;"><i class="bi bi-slash-circle"></i> {{ __('awsbilling.share_disable') }}</button>
                    </form>
                </div>
            @else
                <ul class="sh-points">
                    <li><i class="bi bi-check2"></i> {{ __('awsbilling.share_point_1') }}</li>
                    <li><i class="bi bi-check2"></i> {{ __('awsbilling.share_point_2') }}</li>
                    <li><i class="bi bi-check2"></i> {{ __('awsbilling.share_point_3') }}</li>
                </ul>
                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                    <form method="POST" action="{{ route('admin.aws-accounts.billing.share', $account) }}">
                        @csrf <input type="hidden" name="month" value="{{ $monthQ }}">
                        <button type="submit" class="btn-solid" style="width:100%;"><i class="bi bi-link-45deg"></i> {{ $share ? __('awsbilling.share_reenable') : __('awsbilling.share_enable') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection

@if($public)
    @section('footer-note', __('awsbilling.public_footer', ['time' => config('aws.usage_refresh_time', '08:00')]))
@else
    @include('admin.partials.form-validate')
@endif

@push('scripts')
<script>
    (function () {
        // Halaman publik tidak memuat helper panel admin — sediakan pengganti kecil.
        const copy = window.gmCopy || ((text) => navigator.clipboard
            ? navigator.clipboard.writeText(text)
            : new Promise((ok, fail) => {
                const t = Object.assign(document.createElement('textarea'), { value: text });
                document.body.appendChild(t); t.select();
                const done = document.execCommand('copy'); t.remove();
                done ? ok() : fail();
            }));
        const toast = window.gmToast || (() => {});

        document.querySelectorAll('[data-copy-text]').forEach((btn) => btn.addEventListener('click', () => {
            const el = document.getElementById(btn.dataset.copyText);
            Promise.resolve(copy(el.value))
                .then(() => toast(btn.dataset.copied || @json(__('awsbilling.share_copied')), 'ok'))
                .catch(() => toast(@json(__('awsbilling.copy_failed')), 'bad'));
        }));

        // Simulasi kurs: semua angka Rupiah dihitung ulang di browser dari nilai
        // dasar (kurs invoice). Tidak disimpan; tombol salin tetap memakai kurs asli.
        const fx = document.querySelector('.fx');
        if (fx) {
            const base = parseFloat(fx.dataset.base);
            const range = document.getElementById('fxRange');
            const vatRate = @json($split['vat_rate'] ?? 0.11);
            const fmt = (v) => (Math.round(v) < 0 ? '−' : '') + 'Rp ' + Math.abs(Math.round(v)).toLocaleString('id-ID');
            const totalEl = document.querySelector('[data-row="total"]');
            const line = (idr, fee) => { const inv = idr + fee; const vat = Math.round(inv * vatRate); return { idr, fee, vat, total: inv + vat }; };
            const paint = (row, v) => ['idr', 'fee', 'vat', 'total'].forEach((k) => row.querySelectorAll(':scope [data-f="' + k + '"]').forEach((el) => {
                if (el.closest('[data-row]') === row) el.textContent = fmt(v[k]);
            }));
            const compute = (rate) => {
                const f = rate / base;
                let feeSum = 0;
                document.querySelectorAll('[data-row="co"]').forEach((row) => {
                    const idr = parseFloat(row.dataset.idr) * f;
                    const fee = Math.max(idr * parseFloat(row.dataset.pct || 0) / 100, parseFloat(row.dataset.min || 0));
                    feeSum += fee;
                    paint(row, line(idr, fee));
                });
                // Baris periode: fee sebanding (rasio fee/tagihan dari server).
                document.querySelectorAll('[data-row="per"]').forEach((row) => {
                    const idr = parseFloat(row.dataset.idr) * f;
                    paint(row, line(idr, idr * parseFloat(row.dataset.fr || 0)));
                });
                let total = null;
                if (totalEl) {
                    const idr = parseFloat(totalEl.dataset.idr) * f;
                    const fee = totalEl.dataset.pct !== '' ? idr * parseFloat(totalEl.dataset.pct) / 100 : feeSum;
                    total = line(idr, fee);
                    paint(totalEl, total);
                }
                document.querySelectorAll('[data-lin]').forEach((el) => { el.textContent = fmt(parseFloat(el.dataset.lin) * f); });
                return total;
            };
            const baseLine = compute(base);
            const sim = (on) => document.querySelectorAll('.hero, .co, .pd').forEach((el) => el.classList.toggle('is-sim', on));

            const valEl = document.getElementById('fxVal');
            const valBox = valEl.closest('.fx-val');
            const show2 = (r) => r.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            // Terima "18.228,69", "18228,69", "18228.69", "Rp18.228". Titik dianggap
            // pemisah ribuan bila ada koma, ada >1 titik, atau tepat 3 digit di belakangnya.
            const parseRate = (txt) => {
                let t = String(txt).replace(/[^\d.,]/g, '');
                if (!t) return null;
                if (t.includes(',')) t = t.replace(/\./g, '').replace(',', '.');
                else if ((t.match(/\./g) || []).length > 1 || /\.\d{3}$/.test(t)) t = t.replace(/\./g, '');
                const v = parseFloat(t);
                return Number.isFinite(v) && v > 0 ? Math.round(v * 100) / 100 : null;
            };
            let rate = base;
            // Kurs simulasi diingat per browser, per akun + bulan. Kalau kurs invoice
            // berubah sejak disimpan, simpanan lama dibuang.
            const fxKey = 'gm-fx:' + @json($account->getRouteKey() . ':' . $start->format('Y-m'));
            const store = (r) => {
                try {
                    if (Math.abs(r - base) < 0.005) localStorage.removeItem(fxKey);
                    else localStorage.setItem(fxKey, JSON.stringify({ rate: r, base }));
                } catch (e) {}
            };
            // Kurs aktif disimpan terpisah dari slider: slider per Rp 1, ketikan boleh
            // sampai 2 desimal dan boleh di luar rentang slider.
            const apply = (r, from) => {
                rate = r;
                fx.dataset.current = r;
                store(r);
                const t = compute(r);
                const on = Math.abs(r - base) >= 0.005;
                if (from !== 'range') range.value = Math.round(r);
                if (from !== 'text') valEl.value = show2(r);
                document.getElementById('fxTag').hidden = !on;
                document.getElementById('fxReset').hidden = !on;
                const d = t && baseLine ? t.total - baseLine.total : 0;
                document.getElementById('fxDelta').textContent = on
                    ? @json(__('awsbilling.fx_delta')).replace(':diff', (d >= 0 ? '+' : '−') + fmt(Math.abs(d)).replace('−', ''))
                    : @json(__('awsbilling.fx_hint'));
                sim(on);
            };

            range.addEventListener('input', () => apply(parseFloat(range.value), 'range'));
            valEl.addEventListener('input', () => {
                const r = parseRate(valEl.value);
                valBox.classList.toggle('bad', r === null);
                if (r !== null) apply(r, 'text');
            });
            const commit = () => { valBox.classList.remove('bad'); valEl.value = show2(rate); };
            valEl.addEventListener('blur', commit);
            let before = base;
            valEl.addEventListener('focus', () => { before = rate; valEl.select(); });
            valEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { commit(); valEl.blur(); }
                else if (e.key === 'Escape') { apply(before); valEl.blur(); }
                else if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    const step = e.shiftKey ? 100 : 1;
                    apply(Math.max(1, Math.round((rate + (e.key === 'ArrowUp' ? step : -step)) * 100) / 100));
                }
            });
            document.getElementById('fxReset').addEventListener('click', () => apply(base));

            try {
                const saved = JSON.parse(localStorage.getItem(fxKey) || 'null');
                if (saved && Math.abs(saved.base - base) < 0.005 && saved.rate > 0) apply(saved.rate);
                else localStorage.removeItem(fxKey);
            } catch (e) {}
        }

        // Tampilan rincian per key: urut biaya atau per kategori. Satu pilihan
        // berlaku untuk semua kartu, diingat per browser.
        const applyView = (view) => {
            document.querySelectorAll('[data-kb-detail]').forEach((d) => {
                d.querySelectorAll('.seg button').forEach((b) => {
                    const on = b.dataset.view === view;
                    b.classList.toggle('on', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                d.querySelectorAll('.kb-view').forEach((v) => { v.hidden = v.dataset.view !== view; });
            });
        };
        let savedView = 'cost';
        try { savedView = localStorage.getItem('gm-bill-view') || 'cost'; } catch (e) {}
        applyView(savedView === 'group' ? 'group' : 'cost');
        document.querySelectorAll('[data-kb-detail] .seg button').forEach((b) => b.addEventListener('click', () => {
            applyView(b.dataset.view);
            try { localStorage.setItem('gm-bill-view', b.dataset.view); } catch (e) {}
        }));

        // Pemilih tanggal potong: klik tanggal = periode berakhir di tanggal itu.
        const pForm = document.getElementById('periodForm');
        if (pForm) {
            const monthLbl = @json($start->translatedFormat('M'));
            const last = {{ $start->daysInMonth }};
            const prev = document.getElementById('periodPreview');
            const inputs = document.getElementById('periodInputs');
            const sync = () => {
                const cuts = [...pForm.querySelectorAll('.cal-d.on')].map((b) => +b.dataset.day).sort((a, b) => a - b);
                inputs.innerHTML = cuts.map((d) => '<input type="hidden" name="cuts[]" value="' + d + '">').join('');
                let from = 1; const parts = [];
                cuts.concat([last]).forEach((d) => { parts.push(from + '–' + d + ' ' + monthLbl); from = d + 1; });
                prev.innerHTML = cuts.length
                    ? parts.map((t) => '<span>' + t + '</span>').join('<i class="bi bi-chevron-right"></i>')
                    : @json(__('awsbilling.periods_none'));
                pForm.querySelectorAll('.cal-d').forEach((b) => {
                    const d = +b.dataset.day;
                    b.classList.toggle('in-a', cuts.length > 0 && cuts.filter((c) => c < d).length % 2 === 0);
                });
            };
            pForm.querySelectorAll('.cal-d').forEach((b) => b.addEventListener('click', () => {
                if (!b.classList.contains('on') && pForm.querySelectorAll('.cal-d.on').length >= {{ \App\Models\AwsBillingSplit::MAX_CUTS }}) return;
                b.classList.toggle('on'); sync();
            }));
            document.getElementById('periodClear').addEventListener('click', () => {
                pForm.querySelectorAll('.cal-d.on').forEach((b) => b.classList.remove('on')); sync();
            });
            sync();
        }

        // ?share=1 dipakai untuk membuka modal setelah aksi share; buang dari URL.
        if (new URLSearchParams(location.search).has('share')) {
            const u = new URL(location.href); u.searchParams.delete('share');
            history.replaceState(null, '', u);
        }

        // Modal invoice & link publik (hanya ada di admin).
        const modals = [...document.querySelectorAll('.gm-modal')];
        const closeAll = () => modals.forEach((m) => m.classList.remove('open'));
        document.querySelectorAll('[data-open-modal]').forEach((b) => b.addEventListener('click', () => {
            const m = document.getElementById(b.dataset.openModal);
            if (!m) return;
            m.classList.add('open');
            setTimeout(() => m.querySelector('input:not([type=hidden])')?.focus(), 80);
        }));
        modals.forEach((m) => m.addEventListener('click', (e) => { if (e.target === m || e.target.closest('[data-close]')) closeAll(); }));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
        // ---- Modal export CSV
        const exForm = document.getElementById('exportForm');
        if (exForm) {
            const exAll = document.getElementById('exAll');
            const cos = [...exForm.querySelectorAll('[data-co]')];
            const colInputs = [...exForm.querySelectorAll('input[name="cols[]"]')];
            const presets = [...exForm.querySelectorAll('[data-preset]')];

            const syncAll = () => {
                const n = cos.filter((c) => c.checked).length;
                exAll.checked = n === cos.length;
                exAll.indeterminate = n > 0 && n < cos.length;
                renderPreview();
            };

            // ---- Pratinjau: bentuk baris persis seperti BillingExport di server.
            const exData = JSON.parse(document.getElementById('exData').textContent);
            const L = exData.L;
            const FIELD = { usd: 'usd', aws: 'idr', fee: 'fee', invoice: 'invoice', vat: 'vat', total: 'total' };
            const MAX_LINES = 5;
            function renderPreview() {
                const fd = new FormData(exForm);
                const per = fd.getAll('per[]');
                const sec = exData.sections[per[0] ?? 'month'] || exData.sections.month;
                const level = fd.get('level') || 'key';
                const cols = fd.getAll('cols[]');
                const xlsx = (fd.get('type') || 'xlsx') === 'xlsx';
                const intl = !xlsx && fd.get('fmt') === 'intl';
                document.getElementById('exFmtGroup').hidden = xlsx;
                document.getElementById('exPrevRawBox').hidden = xlsx;
                document.getElementById('exDlLabel').textContent = exData.dl[xlsx ? 'xlsx' : 'csv'];
                const ids = fd.getAll('co[]').map(Number);
                const allSel = ids.length === sec.rows.length;
                const withPct = cols.includes('fee');
                const withOps = level === 'op';
                const maxLines = withOps ? 8 : MAX_LINES;

                const pretty = xlsx || fd.getAll('pretty').pop() === '1';
                // Sama dengan BillingExport::numText(): "Rp 826.085", "$97.31", "90.000" saat
                // pretty aktif; tanpa itu angka polos tanpa pemisah ribuan.
                const num = (v, d, kind) => {
                    let x = Number(Number(v || 0).toFixed(d));
                    if (x === 0) x = 0;
                    let [i, f] = Math.abs(x).toFixed(d).split('.');
                    if (pretty) i = i.replace(/\B(?=(\d{3})+(?!\d))/g, intl ? ',' : '.');
                    let out = i + (f ? (intl ? '.' : ',') + f : '');
                    if (pretty && kind === 'idr') out = 'Rp ' + out;
                    if (pretty && kind === 'usd') out = '$' + out;
                    return (x < 0 ? '-' : '') + out;
                };
                const vals = (v) => cols.map((c) => num(v[FIELD[c]], c === 'usd' ? 2 : 0, c === 'usd' ? 'usd' : 'idr'));
                const scale = (v, f) => Object.fromEntries(Object.entries(v).map(([k, x]) => [k, x * f]));
                const ops = (sec.ops || []).filter((o) => ids.includes(o.id));
                const reqOf = (list) => num(list.reduce((a, o) => a + o.n, 0), 0, 'count');
                // Sama dengan BillingExport: kolom operasi hanya ada di tingkat "+ Per operasi".
                const line = (lv, co, key, opCells, share, pct, v) => [
                    lv, co, key, ...(withOps ? opCells : []), num(share, 2), ...(withPct ? [pct] : []), ...vals(v),
                ];

                const head = [L.h_level, L.h_company, L.h_key,
                    ...(withOps ? [L.h_category, L.h_operation, L.h_requests] : []),
                    L.h_share, ...(withPct ? [L.h_fee_pct] : []), ...cols.map((c) => exData.cols[c])];
                const body = [];
                let hidden = 0;
                const push = (l) => (body.length < maxLines ? body.push(l) : hidden++);

                sec.rows.filter((r) => ids.includes(r.id)).forEach((r) => {
                    const coOps = ops.filter((o) => o.id === r.id);
                    push(['ex-r-co', line(L.lv_company, r.name, '', ['', '', reqOf(coOps)], r.share, num(r.pct, 2), r.v)]);
                    if (level === 'company') return;
                    r.keys.forEach((k) => {
                        const kOps = coOps.filter((o) => o.key === k.name);
                        push(['ex-r-key', line(L.lv_key, r.name, k.name, ['', '', reqOf(kOps)], k.share, '', k.v)]);
                        if (!withOps) return;
                        kOps.forEach((o) => {
                            const f = o.keyUsd > 0 ? o.usd / o.keyUsd : 0;
                            push(['ex-r-op', line(L.lv_op, r.name, k.name, [o.cat, o.op, num(o.n, 0, 'count')], k.share * f, '', scale(k.v, f))]);
                        });
                    });
                });

                let total;
                if (allSel) {
                    total = line(L.lv_total, '', '', ['', '', reqOf(ops)], 100, sec.pct !== null ? num(sec.pct, 2) : '', sec.total);
                } else {
                    const sum = { usd: 0, idr: 0, fee: 0, invoice: 0, vat: 0, total: 0 };
                    let share = 0;
                    sec.rows.filter((r) => ids.includes(r.id)).forEach((r) => {
                        share += r.share;
                        Object.keys(sum).forEach((k) => { sum[k] += Number(r.v[k] || 0); });
                    });
                    total = line(L.lv_total_sel, '', '', ['', '', reqOf(ops)], share, '', sum);
                }

                const table = document.getElementById('exPrevTable');
                table.replaceChildren();
                const numFrom = withOps ? 5 : 3;
                const add = (cells, cls, tag = 'td') => {
                    const row = document.createElement('tr');
                    if (cls) row.className = cls;
                    cells.forEach((c, i) => {
                        const cell = document.createElement(tag);
                        cell.textContent = c;
                        if (i >= numFrom) cell.className = 'n';
                        row.appendChild(cell);
                    });
                    table.appendChild(row);
                };
                const t = document.createElement('tr');
                t.className = 'ex-r-sec';
                const tc = document.createElement('td');
                tc.colSpan = head.length;
                tc.textContent = '▶ ' + sec.label;
                t.appendChild(tc);
                table.appendChild(t);
                add(head, '', 'th');
                body.forEach(([cls, cells]) => add(cells, cls));
                add(total, 'ex-r-tot');

                const note = document.getElementById('exPrevNote');
                note.hidden = hidden === 0;
                note.textContent = exData.more.replace(':n', hidden);
                document.getElementById('exLevelHint').textContent = exData.hints[level];

                // Format aktif tertulis di kepala contoh; menyala sebentar saat diganti.
                const badge = document.getElementById('exFmtBadge');
                const fmtKey = xlsx ? 'xlsx' : (intl ? 'intl' : 'id');
                if (badge.dataset.fmt && badge.dataset.fmt !== fmtKey) {
                    badge.classList.remove('flash');
                    void badge.offsetWidth;
                    badge.classList.add('flash');
                }
                badge.dataset.fmt = fmtKey;
                badge.textContent = exData.fmt[fmtKey];

                // Satu baris mentah, persis seperti di file (aturan kutip fputcsv).
                const sep = intl ? ',' : ';';
                const first = body[0] ? body[0][1] : total;
                document.getElementById('exPrevRaw').textContent = first
                    .map((c) => (/[\s"]/.test(c) || c.includes(sep) ? '"' + c.replace(/"/g, '""') + '"' : c))
                    .join(sep);
            }

            exAll.addEventListener('change', () => { cos.forEach((c) => { c.checked = exAll.checked; }); syncAll(); });
            cos.forEach((c) => c.addEventListener('change', syncAll));

            // Tombol cepat kolom menyala kalau pilihan sekarang persis sama.
            const markPreset = () => {
                const now = colInputs.filter((i) => i.checked).map((i) => i.value).join(',');
                presets.forEach((b) => b.classList.toggle('on', b.dataset.preset === now));
                renderPreview();
            };
            presets.forEach((b) => b.addEventListener('click', () => {
                const want = b.dataset.preset.split(',');
                colInputs.forEach((i) => { i.checked = want.includes(i.value); });
                markPreset();
            }));
            colInputs.forEach((i) => i.addEventListener('change', markPreset));
            exForm.querySelectorAll('input[name="per[]"], input[name="level"], input[name="fmt"], input[name="pretty"], input[name="type"]')
                .forEach((i) => i.addEventListener('change', renderPreview));
            markPreset();

            // Kurs: ikut penggeser kurs di halaman. Kurs simulasi dikirim ke server
            // dan ditandai jelas di file; kurs resmi tidak perlu dikirim.
            const fxEl = document.querySelector('.fx');
            const rateIn = document.getElementById('exRate');
            const syncRate = () => {
                if (!fxEl) return;
                const base = parseFloat(fxEl.dataset.base);
                const cur = parseFloat(fxEl.dataset.current || fxEl.dataset.base);
                const sim = Math.abs(cur - base) >= 0.005;
                document.getElementById('exRateText').textContent = 'Rp ' + cur.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('exRateTag').hidden = !sim;
                document.getElementById('exPrevSim').hidden = !sim;
                rateIn.disabled = !sim;
                rateIn.value = sim ? cur.toFixed(2) : '';
            };
            document.querySelectorAll('[data-open-modal="exportModal"]').forEach((b) => b.addEventListener('click', syncRate));

            exForm.addEventListener('submit', (e) => {
                // Unduhan, bukan pindah halaman: dibuat lewat URL supaya loader tidak tertahan.
                e.preventDefault();
                let ok = true;
                exForm.querySelectorAll('[data-need-one]').forEach((g) => {
                    const any = [...g.querySelectorAll('input[type=checkbox][name]')].some((i) => i.checked);
                    g.querySelector('.ex-err').hidden = any;
                    ok = ok && any;
                });
                if (!ok) return;

                syncRate();
                const url = new URL(exForm.action);
                new FormData(exForm).forEach((v, k) => url.searchParams.append(k, v));
                window.location.href = url.toString();
                closeAll();
            });
        }
    })();
</script>
@endpush
