@extends('layouts.report')

@section('title', __('apikeys.share_report_title'))

@push('styles')
    :root {
        --bar-a: #cae5d4; --bar-b: #bbdfc8;
        --bar-top-a: #0b7c40; --bar-top-b: #066934; --bar-accent: #066934;
    }
    :root[data-theme="dark"] {
        --bar-a: rgba(0, 177, 79, 0.26); --bar-b: rgba(0, 177, 79, 0.16);
        --bar-top-a: #14b862; --bar-top-b: #0d9a50; --bar-accent: #0d9a50;
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]):not([data-theme="dark"]) {
            --bar-a: rgba(0, 177, 79, 0.26); --bar-b: rgba(0, 177, 79, 0.16);
            --bar-top-a: #14b862; --bar-top-b: #0d9a50; --bar-accent: #0d9a50;
        }
    }

    /* ---------- Kepala laporan (identitas klien) ---------- */
    .report-id {
        display: flex; align-items: center; gap: 16px;
        background: var(--card); border-radius: var(--r-card);
        padding: 18px 20px; margin-bottom: 16px;
        box-shadow: var(--shadow-card);
    }
    .report-id-mark {
        width: 54px; height: 54px; border-radius: 16px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--green-soft); color: var(--green-text); font-size: 1.3rem;
        overflow: hidden;
    }
    .report-id-mark img { width: 100%; height: 100%; object-fit: contain; }
    .report-id-eyebrow {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.12em;
        text-transform: uppercase; color: var(--muted);
    }
    .report-id-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 1.45rem; letter-spacing: -0.03em;
        line-height: 1.15; margin: 2px 0 0;
    }
    .report-id-meta {
        display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 7px;
        font-size: 0.73rem; color: var(--muted);
    }
    .report-id-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .report-id-meta .stale { color: var(--warn-fg); font-weight: 600; }

    .print-btn {
        display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0;
        border: none; border-radius: 999px; padding: 10px 18px;
        background: var(--surface); color: var(--ink);
        font-size: 0.78rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }
    .print-btn:hover { background: var(--green); color: #fff; }
    a.print-btn { text-decoration: none; }
    .report-actions { display: flex; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
    .print-btn:active { transform: scale(0.96); }

    @media (max-width: 620px) {
        .report-id { flex-wrap: wrap; }
        .report-actions { width: 100%; }
        .print-btn { flex: 1; justify-content: center; }
    }

    /* ---------- Kartu biaya (jangkar visual) ---------- */
    .cost-card {
        background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 45%, #04703a 100%);
        border-radius: var(--r-card); padding: 18px 20px; color: #fff;
        position: relative; overflow: hidden;
        /* Total di kiri, panel rincian di kanan; turun ke bawah kalau sempit. */
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: 14px 28px;
    }
    .cost-card::after {
        content: ''; position: absolute; right: -60px; top: -70px;
        width: 190px; height: 190px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, transparent 65%);
    }
    .cost-card > * { position: relative; z-index: 1; }
    .cc-main { flex: 1 1 200px; min-width: 0; }
    .cc-top { display: flex; align-items: center; gap: 7px; color: rgba(255, 255, 255, 0.8); }
    .cc-lbl {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; color: rgba(255, 255, 255, 0.8);
    }
    .cc-val {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 1.75rem; letter-spacing: -0.03em;
        line-height: 1.1; margin: 8px 0 4px;
    }
    .cc-val .cents { color: rgba(255, 255, 255, 0.62); }
    .cc-idr { font-size: 0.85rem; font-weight: 700; }
    .cc-note { font-size: 0.66rem; color: rgba(255, 255, 255, 0.72); margin-top: 2px; }
    /* Rincian dolar dan rupiahnya sejajar per baris supaya mudah dibandingkan. */
    .cc-break {
        flex: 0 0 auto;
        display: grid; grid-template-columns: auto auto auto; gap: 4px 16px;
        padding: 10px 14px; border-radius: 14px; background: rgba(255, 255, 255, 0.1);
        font-size: 0.72rem; color: rgba(255, 255, 255, 0.88); white-space: nowrap;
    }
    .cc-break .num { text-align: right; font-weight: 600; }
    .cc-break .idr { color: rgba(255, 255, 255, 0.72); font-weight: 500; }
    /* Kurs ditulis di samping total: semua angka rupiah bergantung padanya. */
    .cc-rate {
        display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
        padding: 3px 10px; border-radius: 999px;
        background: rgba(255, 255, 255, 0.16); color: #fff;
        font-size: 0.68rem; font-weight: 700; white-space: nowrap;
    }
    .cc-rate.custom, .cc-rate.off { background: #fde68a; color: #7c2d12; }

    /* ---------- Cetak / simpan PDF ---------- */
    @media print {
        .print-btn, .report-actions, .dr, .report-top .dr, [data-dr] { display: none !important; }
        body { background: #fff; }
        .q-card, .report-id { box-shadow: none; border: 1px solid #e6e9eb; break-inside: avoid; }
        .cost-card, .u-table tr.cat-head td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .detail-grid { grid-template-columns: minmax(0, 1fr); }
    }

    /* Dua kartu angka ditumpuk di kiri; kartu biaya mengambil kolom kanan
       setinggi keduanya supaya rinciannya punya ruang tanpa meregangkan kartu lain. */
    .stat-row {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
        gap: 16px; margin-bottom: 16px;
    }
    .stat-row .cost-card { grid-column: 2; grid-row: 1 / span 2; }
    @media (max-width: 760px) {
        .stat-row { grid-template-columns: 1fr; }
        .stat-row .cost-card { grid-column: auto; grid-row: auto; }
    }

    .stat-tile { display: flex; align-items: center; gap: 14px; }
    .stat-tile .ic {
        width: 44px; height: 44px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .stat-tile .val { font-size: 1.4rem; }
    .stat-tile .val .cents { color: var(--faint); }
    .stat-tile .lbl { font-size: 0.7rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-tile .sub { font-size: 0.68rem; color: var(--muted); margin-top: 2px; }

    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }

    /* ---------- Kurs yang bisa digeser ---------- */
    .rate-card { margin-bottom: 16px; padding: 16px 20px 12px; }
    .rate-head { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
    .rate-lbl {
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: var(--muted);
    }
    .rate-val { display: flex; align-items: baseline; gap: 5px; margin-top: 3px; }
    .rate-val .pfx { font-size: 0.95rem; font-weight: 700; color: var(--muted); }
    .rate-val input {
        width: 5.5ch; border: none; background: none; outline: none; padding: 0;
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
        font-size: 1.35rem; letter-spacing: -0.03em; color: var(--ink);
    }
    .rate-val input:focus { color: var(--green-text); }
    .rate-val .sfx { font-size: 0.72rem; font-weight: 600; color: var(--muted); }

    .rate-tag {
        margin-left: auto; align-self: center;
        font-size: 0.64rem; font-weight: 700; letter-spacing: 0.04em;
        background: var(--green-soft); color: var(--green-text);
        border-radius: 999px; padding: 4px 11px;
    }
    .rate-tag.custom { background: var(--warn-soft); color: var(--warn-fg); }
    .rate-meta { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }
    .rate-card.off { box-shadow: inset 0 0 0 2px var(--warn-fg), var(--shadow-card); }
    .rate-warn {
        display: flex; gap: 8px; align-items: flex-start; margin-top: 10px;
        padding: 9px 12px; border-radius: 12px;
        background: var(--warn-soft); color: var(--warn-fg);
        font-size: 0.74rem; font-weight: 600; line-height: 1.4;
    }
    /* Catatan batas pemakaian laporan. Nadanya pemberitahuan, bukan peringatan
       galat, supaya tetap terbaca tanpa membuat halaman terlihat bermasalah. */
    .est-note {
        display: flex; gap: 12px; align-items: flex-start;
        background: var(--card); border-radius: var(--r-card);
        border-left: 4px solid var(--warn-fg);
        padding: 14px 18px; margin-bottom: 16px;
        box-shadow: var(--shadow-card);
    }
    .est-note-ic { color: var(--warn-fg); font-size: 1rem; line-height: 1.3; flex-shrink: 0; }
    .est-note-title { font-size: 0.82rem; font-weight: 700; margin-bottom: 2px; }
    .est-note-body { font-size: 0.74rem; color: var(--muted); line-height: 1.5; margin: 0; }

    .th-rate { display: block; font-size: 0.62rem; font-weight: 600; color: var(--muted); text-transform: none; letter-spacing: 0; }
    .th-rate.custom { color: var(--warn-fg); }

    .rate-reset {
        align-self: center; margin-left: auto;
        display: inline-flex; align-items: center; gap: 6px;
        border: none; border-radius: 999px; padding: 7px 14px;
        background: var(--surface); color: var(--ink);
        font-size: 0.72rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .rate-reset:hover { background: var(--green); color: #fff; }
    .rate-reset[hidden] { display: none; }
    /* Hanya di layar: saat dicetak tombolnya disembunyikan, jadi tag tetap rata kanan. */
    @media screen { .rate-reset:not([hidden]) ~ .rate-tag { margin-left: 0; } }

    .rate-track { position: relative; padding-top: 14px; }

    .rate-track input[type="range"] {
        -webkit-appearance: none; appearance: none;
        width: 100%; height: 20px; background: none; outline: none; cursor: pointer; display: block;
    }
    .rate-track input[type="range"]::-webkit-slider-runnable-track {
        height: 6px; border-radius: 999px;
        background: linear-gradient(90deg, var(--green) 0 var(--pct, 50%), var(--line) var(--pct, 50%) 100%);
    }
    .rate-track input[type="range"]::-moz-range-track { height: 6px; border-radius: 999px; background: var(--line); }
    .rate-track input[type="range"]::-moz-range-progress { height: 6px; border-radius: 999px; background: var(--green); }
    .rate-track input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none;
        width: 18px; height: 18px; margin-top: -6px;
        border-radius: 50%; border: 3px solid #fff; background: var(--green);
        box-shadow: 0 2px 8px rgba(0, 177, 79, 0.45);
        transition: transform 0.14s cubic-bezier(0.34, 1.5, 0.5, 1);
    }
    .rate-track input[type="range"]::-moz-range-thumb {
        width: 18px; height: 18px; border-radius: 50%;
        border: 3px solid #fff; background: var(--green);
    }
    .rate-track input[type="range"]:hover::-webkit-slider-thumb { transform: scale(1.12); }

    /* Penanda kurs resmi di atas rel. */
    .rate-mark {
        position: absolute; top: 0; transform: translateX(-50%);
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        pointer-events: none;
    }
    .rate-mark .dot { width: 2px; height: 12px; border-radius: 2px; background: var(--faint); }
    .rate-mark .tx {
        font-size: 0.58rem; font-weight: 700; letter-spacing: 0.06em;
        text-transform: uppercase; color: var(--faint); white-space: nowrap;
        order: -1;
    }

    .rate-foot {
        display: flex; justify-content: space-between; gap: 10px;
        font-size: 0.65rem; color: var(--faint); margin-top: 2px;
    }
    .rate-foot .hint { color: var(--muted); text-align: center; }

    /* Saat dicetak, kurs yang dipakai tetap tertulis: semua angka rupiah bergantung padanya. */
    @media print {
        .rate-track, .rate-foot, .rate-reset { display: none !important; }
        .rate-card { padding: 8px 14px; margin-bottom: 10px; }
        /* Kertas lebih sempit dari 760px; tetap dua kolom supaya grafik muat di halaman 1. */
        .stat-row { grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr); }
        .stat-row .cost-card { grid-column: 2; grid-row: 1 / span 2; }
    }

    /* Perbandingan lebar sama dengan .stat-row, cuma dibalik sisinya: kolom
       sempitnya selebar kartu Total requests di atas, jadi tepinya segaris. */
    .detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
        margin-top: 16px;
    }
    .detail-main, .detail-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .detail-side .rate-card { margin-bottom: 0; }

    /* Di kolom sempit, keterangan kurs turun satu baris supaya batas bawah dan
       atasnya tetap di ujung kiri-kanan rel. */
    .detail-side .rate-foot { flex-wrap: wrap; }
    .detail-side .rate-foot .hint { flex: 1 0 100%; order: 3; margin-top: 3px; }

    /* Dua kolom hanya kalau kolom kirinya masih ≥760px — lebar minimum tabel
       operasi sejak ada kolom Rupiah. Di 1300px kolom kiri tinggal ±764px; di
       bawah itu tabelnya mulai menggeser ke samping, jadi kartu ditumpuk. */
    @media (max-width: 1300px) { .detail-grid { grid-template-columns: minmax(0, 1fr); } }

    .u-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.82rem; }
    .u-table th {
        font-size: 0.68rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: 0.05em;
        text-align: left; padding: 0 14px 12px; white-space: nowrap;
    }
    .u-table td { padding: 11px 14px; border-top: 1px solid var(--line); }
    .u-table tbody tr:hover td { background: var(--surface); }
    .u-table tbody tr td:first-child { border-radius: 12px 0 0 12px; }
    .u-table tbody tr td:last-child { border-radius: 0 12px 12px 0; }
    .u-table tfoot td { padding: 10px 14px; border-top: 1px solid var(--line); }
    /* Semua kolom angka seragam: rata kanan, tidak patah, dan lebar digitnya
       tetap supaya rupiah antar baris sejajar dan enak dibandingkan. */
    .u-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; width: 1%; min-width: 118px; }
    .u-table .idr-cell { white-space: nowrap; }

    /* Kolom nama operasi yang boleh menyusut; bar porsi dan harga satuan AWS
       ikut di dalamnya, bukan jadi kolom sendiri. */
    .op-cell { min-width: 0; }
    .op-name { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .op-rate { font-size: 0.68rem; color: var(--muted); font-variant-numeric: tabular-nums; }
    /* Bar dibiarkan selebar kolomnya: kalau dipotong, ada jarak kosong antara
       ujung bar dan kolom angka, dan panjangnya jadi sulit dibanding-bandingkan. */
    .op-track { height: 5px; margin-top: 8px; border-radius: 999px; background: var(--surface); overflow: hidden; }
    .op-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--green), #4bd07f); }

    .q-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); flex-shrink: 0; }
    .q-track { height: 6px; background: var(--surface); border-radius: 999px; overflow: hidden; }
    .q-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--green), #4bd07f); }

    .cat-label { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
    .cat-ic {
        width: 26px; height: 26px; border-radius: 8px; color: var(--cat);
        display: flex; align-items: center; justify-content: center; font-size: 0.78rem; flex-shrink: 0;
        background: var(--card);
    }

    /* Baris induk kategori: latar abu + garis warna di tepi kiri, supaya operasi
       yang menjorok di bawahnya jelas miliknya. */
    .u-table tr.cat-head td { background: var(--surface); padding-top: 13px; padding-bottom: 13px; }
    .u-table tr.cat-head:hover td { background: var(--surface); }
    .u-table tr.cat-head td:first-child { box-shadow: inset 3px 0 0 var(--cat); }
    .cat-share {
        font-size: 0.68rem; font-weight: 600; color: var(--muted);
        padding: 1px 7px; border-radius: 999px; background: var(--card);
    }
    .u-table tr.cat-item td:first-child { padding-left: 30px; }
    .u-table tr.cat-item .q-dot { background: var(--muted); opacity: 0.55; }

    /* Di layar sempit tabel dipecah jadi tumpukan: tiap baris satu blok dengan
       label di kiri dan angkanya di kanan. Tidak ada lagi geseran ke samping. */
    @media (max-width: 560px) {
        .u-table thead { display: none; }
        .u-table, .u-table tbody, .u-table tfoot, .u-table tr, .u-table td { display: block; width: auto; }
        .u-table tr { border-top: 1px solid var(--line); padding: 10px 2px; }
        .u-table tbody tr:first-child { border-top: none; }
        .u-table td, .u-table tfoot td { border: none; padding: 3px 0; }
        .u-table td.num { display: flex; align-items: baseline; justify-content: space-between; gap: 14px; }
        .u-table td.num::before {
            content: attr(data-label);
            font-size: 0.68rem; font-weight: 600; letter-spacing: 0.04em;
            text-transform: uppercase; color: var(--muted);
        }
        .u-table tbody tr:hover td, .u-table tr.cat-head td { background: transparent; }
        .u-table tr.cat-head { background: var(--surface); border-radius: 12px; padding: 10px 12px; }
        .u-table tr.cat-head td:first-child { box-shadow: none; }
        .u-table tr.cat-item td:first-child { padding-left: 0; }
    }
@endpush

@section('header-actions')
    @include('admin.partials.date-range')
@endsection

@if(!empty($keyTabs) && $keyTabs->count())
@section('top-tabs')
    {{-- Tab ini hanya berisi key yang memang tercakup link-nya. --}}
    <div class="rp-tabs" role="tablist">
        @php
            $tabBase = ['token' => $share->share_token, 'start' => $startDate, 'end' => $endDate];
        @endphp

        <a href="{{ route('usage-report.show', $tabBase) }}"
           class="rp-tab {{ $activeKey ? '' : 'on' }}" role="tab" data-no-loader>
            <i class="bi bi-collection"></i> {{ __('apikeys.tab_all_keys') }}
        </a>

        @foreach($keyTabs as $tab)
            <a href="{{ route('usage-report.show', $tabBase + ['key' => $tab->key_name]) }}"
               class="rp-tab {{ $activeKey === $tab->key_name ? 'on' : '' }}" role="tab" data-no-loader>
                {{ $tab->label ?: $tab->key_name }}
            </a>
        @endforeach
    </div>
@endsection
@endif

@section('content')
@php
    use App\Services\AwsLocationService;

    $reportTitle = $assignedCompany?->name ?? __('apikeys.share_report_title');

    // Saat satu key dipilih lewat tab, judulnya menyebut key itu supaya laporan
    // yang dicetak tidak ambigu.
    $activeKeyName = $activeKey ?? null;

    $rangeStart = \Carbon\Carbon::parse($startDate);
    $rangeEnd   = \Carbon\Carbon::parse($endDate);
    $rangeLabel = $rangeStart->wib()->translatedFormat('d M') . ' – ' . $rangeEnd->wib()->translatedFormat('d M Y');

    $daily = collect();
    for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
        $key = $d->format('Y-m-d');
        $daily[$key] = $metrics['daily'][$key] ?? 0;
    }

    $ops       = $metrics['operations'] ?? [];
    $totalReq  = $metrics['total'] ?? 0;
    $totalCost = AwsLocationService::estimateCost($ops);

    // Service charge + PPN dihitung di controller (App\Models\ServiceCharge).
    // Service charge jasa PT Alfa di luar AWS, jadi hanya dalam Rupiah: persen
    // dari total AWS + PPN yang dirupiahkan, atau minimum bulanan Rp.
    $sc        = $charge;
    $tax       = $sc['tax'];
    $totalVat  = $sc['total_vat'];
    $opMax     = $ops ? max(array_values($ops)) : 1;

    // Label dasar hitungan dikirim keduanya kalau persen dan minimum sama-sama
    // ada, karena pemenangnya bisa berubah saat kurs digeser.
    $scMinIdr = $sc['min_idr'];
    $scLabels = $sc['percent'] > 0 && $scMinIdr > 0
        ? ['percent' => \App\Models\ServiceCharge::basisLabel(['basis' => 'percent'] + $sc),
           'minimum' => \App\Models\ServiceCharge::basisLabel(['basis' => 'minimum'] + $sc)]
        : null;

    // Kurs yang dipakai beserta tanggalnya. Kalau tanggal kurs bukan di bulan
    // akhir periode laporan, angka rupiah bisa berbeda dengan tagihan final.
    $rateDate = $activeRate?->rate_date;
    $rateOffPeriod = $rateDate && $rateDate->format('Y-m') !== $rangeEnd->format('Y-m');
    $rateText = 'Rp ' . number_format($idrRate, 0, ',', '.');

    $money = function ($v) {
        $p = explode('.', number_format($v, 2, '.', ','));
        return ['int' => $p[0], 'cents' => $p[1]];
    };

    // Biaya yang lebih kecil dari satu sen ditulis apa adanya dengan empat desimal
    // ($0.0017), bukan dibulatkan jadi $0.00 yang terbaca seperti tidak ada data.
    $usd = fn ($v) => '$' . number_format($v, $v > 0 && $v < 0.01 ? 4 : 2);

    // Nilai awal dengan kurs resmi; skrip kurs di bawah menimpanya dengan kurs pembaca.
    $rp = fn ($usdPart) => 'Rp ' . number_format($usdPart * $idrRate, 0, ',', '.');
    $rpIdr = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
    $tiny = $totalVat > 0 && $totalVat < 0.01;
    $grandTiny = explode('.', number_format($totalVat, 4, '.', ''));
    $grandParts = $money($totalVat);

    $categories = [
        'maps'   => ['label' => __('dash.cat_maps'),   'icon' => 'bi-map',             'color' => '#00B14F', 'ops' => ['GetMapTile', 'GetTile', 'GetMapStyleDescriptor', 'GetMapGlyphs', 'GetMapSprites']],
        'places' => ['label' => __('dash.cat_places'), 'icon' => 'bi-search',          'color' => '#6366f1', 'ops' => ['SearchText', 'ReverseGeocode', 'Suggest', 'GetPlace']],
        'routes' => ['label' => __('dash.cat_routes'), 'icon' => 'bi-sign-turn-right', 'color' => '#f59e0b', 'ops' => ['CalculateRoutes', 'CalculateRouteMatrix']],
    ];

    // Rincian operasi dikelompokkan per kategori supaya sebangun dengan invoice:
    // tiap kategori membawa subtotalnya sendiri, operasinya menjorok di bawahnya.
    // Operasi di luar daftar kategori tetap dicetak, tanpa induk, supaya tidak hilang.
    $grouped = [];
    $claimed = [];

    foreach ($categories as $cat) {
        $rows = [];
        foreach ($cat['ops'] as $op) {
            if (isset($ops[$op])) {
                $rows[$op] = $ops[$op];
                $claimed[$op] = true;
            }
        }

        if (!$rows) {
            continue;
        }

        arsort($rows);
        $catCost = AwsLocationService::estimateCost($rows);

        $grouped[] = $cat + [
            'rows'  => $rows,
            'count' => array_sum($rows),
            'cost'  => $catCost,
            'share' => $totalCost > 0 ? ($catCost / $totalCost) * 100 : 0,
        ];
    }

    $ungrouped = array_diff_key($ops, $claimed);
@endphp

{{-- Laporan ini dibaca klien, jadi yang dibesarkan namanya sendiri — bukan
     judul generik. Logo dipakai kalau perusahaannya punya. --}}
<div class="report-id">
    <div class="report-id-mark">
        @if($assignedCompany?->logo_path)
            <img src="{{ asset($assignedCompany->logo_path) }}" alt="{{ $assignedCompany->name }}">
        @else
            <i class="bi bi-buildings"></i>
        @endif
    </div>

    <div style="flex:1;min-width:0;">
        <div class="report-id-eyebrow">
            {{ isset($usage) ? __('apikeys.share_company_title') : __('apikeys.share_report_title') }}
        </div>
        <h1 class="report-id-name">{{ $reportTitle }}</h1>
        <div class="report-id-meta">
            <span><i class="bi bi-calendar3"></i> {{ $rangeLabel }}</span>
            <span><i class="bi bi-clock"></i> {{ __('apikeys.share_days', ['count' => $days]) }}</span>
            @isset($usage)
                <span>
                    <i class="bi bi-key"></i>
                    @if($activeKeyName)
                        <span style="font-family:ui-monospace,monospace;">{{ $activeKeyName }}</span>
                    @else
                        {{ __('apikeys.share_keys_count', ['count' => count($usage['per_key'])]) }}
                    @endif
                </span>
            @endisset
            @if($fetchedAt)
                <span @class(['stale' => $fetchedAt->lt(now()->subDay())])>
                    <i class="bi bi-arrow-repeat"></i>
                    {{ __('apikeys.fetched', ['time' => $fetchedAt->wib()->format('d M H:i')]) }}
                </span>
            @endif
        </div>
    </div>

    <div class="report-actions">
        {{-- Rentang dan tab key yang sedang dilihat ikut dibawa; kurs hasil
             geseran ditambahkan oleh skrip di bawah saat tombol diklik. --}}
        <a class="print-btn" data-export data-no-loader
           href="{{ route('usage-report.export', array_filter([
               'token' => $share->share_token,
               'start' => $startDate,
               'end'   => $endDate,
               'key'   => $activeKey ?? null,
               'lang'  => app()->getLocale(),
           ])) }}">
            <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('apikeys.share_export') }}
        </a>
        <button type="button" class="print-btn" onclick="window.print()">
            <i class="bi bi-printer"></i> {{ __('apikeys.share_print') }}
        </button>
    </div>
</div>

{{-- Laporan ini dibaca klien dan angkanya mendekati tagihan, jadi batasnya
     dinyatakan di muka, bukan hanya di catatan kaki: ini hitungan dari metrik
     CloudWatch, bukan salinan invoice AWS. --}}
<div class="est-note">
    <span class="est-note-ic"><i class="bi bi-info-circle-fill"></i></span>
    <div>
        <div class="est-note-title">{{ __('apikeys.estimate_title') }}</div>
        <p class="est-note-body">{{ __('apikeys.estimate_body') }}</p>
    </div>
</div>

@if(!$fetchedAt)
    <div class="q-alert">
        <span class="q-alert-icon"><i class="bi bi-info-circle-fill"></i></span>
        <div class="q-alert-body">{{ __('apikeys.share_no_data') }}</div>
    </div>
@endif

@isset($usage)
    @if($usage['missing'] > 0)
        {{-- Halaman ini sengaja tidak menembak AWS; key tanpa snapshot ditandai
             apa adanya supaya angkanya tidak dikira nol pemakaian. --}}
        <div class="q-alert warn">
            <span class="q-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="q-alert-body">{{ __('apikeys.share_missing_keys', ['count' => $usage['missing']]) }}</div>
        </div>
    @endif
@endisset

@if($metrics['error'] ?? false)
    <div class="q-alert bad">
        <span class="q-alert-icon"><i class="bi bi-x-lg"></i></span>
        <div class="q-alert-body">{{ __('apikeys.share_data_unavailable') }}</div>
    </div>
@endif

<div class="stat-row">
    <div class="q-card stat-tile">
        <div class="ic tone-violet"><i class="bi bi-lightning-charge-fill"></i></div>
        <div>
            <div class="q-num val">{{ number_format($totalReq) }}</div>
            <div class="lbl">{{ __('apikeys.total_requests') }}</div>
        </div>
    </div>

    <div class="q-card stat-tile">
        <div class="ic tone-green"><i class="bi bi-graph-up"></i></div>
        <div>
            <div class="q-num val">{{ $totalReq > 0 ? number_format($totalReq / max($days, 1), 0) : 0 }}</div>
            <div class="lbl">{{ __('apikeys.avg_per_day') }}</div>
        </div>
    </div>

    <div class="cost-card">
        <div class="cc-main">
            <div class="cc-top">
                <i class="bi bi-wallet2"></i>
                <span class="cc-lbl">{{ __('apikeys.est_cost') }}</span>
            </div>
            {{-- Dengan service charge, total tagihannya dalam Rupiah (service charge
                 tidak punya nilai dolar); dolarnya tinggal AWS + PPN. --}}
            @if($sc['active'])
                <div class="cc-val" data-idr data-idr-role="grand" data-idr-prefix="Rp ">{{ $rpIdr($sc['grand_idr']) }}</div>
                <div class="cc-idr">AWS + {{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }} {{ $usd($totalVat) }}</div>
            @else
                <div class="cc-val">
                    @if($tiny)
                        ${{ $grandTiny[0] }}<span class="cents">.{{ $grandTiny[1] }}</span>
                    @else
                        ${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span>
                    @endif
                </div>
                <div class="cc-idr" data-idr="{{ $totalVat }}">≈ {{ $rp($totalVat) }}</div>
            @endif
            <div class="cc-rate {{ $rateOffPeriod ? 'off' : '' }}" data-rate-echo data-off="{{ $rateOffPeriod ? 1 : 0 }}"
                 data-tpl="{{ __('apikeys.rate_chip', ['rate' => ':rate']) }}">
                <i class="bi bi-currency-exchange"></i>
                <span data-rate-echo-text>{{ __('apikeys.rate_chip', ['rate' => $rateText]) }}</span>
            </div>
            <div class="cc-note">
                {{ $sc['active']
                    ? __('servicecharge.incl', ['pct' => round($taxRate * 100, 2)])
                    : __('apikeys.incl_tax', ['pct' => round($taxRate * 100, 2)]) }}
            </div>
        </div>

        {{-- Rincian singkat supaya tiap komponen terlihat langsung dalam dolar
             dan rupiah, tidak hanya terselip di tabel rincian bawah. --}}
        <div class="cc-break">
            <span>AWS</span>
            <span class="num">{{ $usd($totalCost) }}</span>
            <span class="num idr" data-idr="{{ $totalCost }}" data-idr-prefix="Rp ">{{ $rp($totalCost) }}</span>

            <span>+ {{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }}</span>
            <span class="num">{{ $usd($tax) }}</span>
            <span class="num idr" data-idr="{{ $tax }}" data-idr-prefix="Rp ">{{ $rp($tax) }}</span>

            @if($sc['active'])
                <span>+ {{ __('servicecharge.line') }}</span>
                <span class="num">—</span>
                <span class="num idr" data-idr data-idr-role="sc" data-idr-prefix="Rp ">{{ $rpIdr($sc['charge_idr']) }}</span>
            @endif
        </div>
    </div>
</div>

<div class="q-card">
    <div class="q-card-head">
        <div class="d-flex align-items-center gap-2">
            <div class="q-icon-box"><i class="bi bi-bar-chart-line"></i></div>
            <div>
                <div class="q-card-title">{{ __('apikeys.daily_chart') }}</div>
                <div class="q-card-sub">{{ __('apikeys.daily_chart_sub', ['range' => $rangeLabel]) }}</div>
            </div>
        </div>
    </div>

    @if($daily->sum() === 0)
        <div class="q-empty"><i class="bi bi-bar-chart"></i>{{ __('apikeys.no_usage') }}</div>
    @else
        @include('admin.partials.bar-chart', ['series' => $daily])
    @endif
</div>

{{-- Rincian operasi mengambil kolom lebar di kiri; kurs dan porsi per API key
     ditumpuk di kolom sempit sebelah kanan, selebar kartu Total requests. --}}
<div class="detail-grid">
    <div class="detail-main">
        @if(!empty($ops) || $sc['active'])
            <div class="q-card">
                <div class="q-card-head">
                    <div>
                        <div class="q-card-title">{{ __('apikeys.ops_title') }}</div>
                        <div class="q-card-sub">{{ __('apikeys.ops_sub') }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="u-table">
                        <thead>
                            <tr>
                                <th>{{ __('apikeys.op') }}</th>
                                <th class="num">{{ __('apikeys.requests') }}</th>
                                <th class="num">{{ __('apikeys.est_cost') }}</th>
                                <th class="num">
                                    {{ __('apikeys.cost_idr') }}
                                    <span class="th-rate" data-rate-echo data-tpl="@ :rate / USD">
                                        <span data-rate-echo-text>@ {{ $rateText }} / USD</span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grouped as $cat)
                                <tr class="cat-head" style="--cat: {{ $cat['color'] }};">
                                    <td>
                                        <div class="cat-label">
                                            <div class="cat-ic"><i class="bi {{ $cat['icon'] }}"></i></div>
                                            <span class="fw-semibold">{{ $cat['label'] }}</span>
                                            <span class="cat-share">{{ number_format($cat['share'], 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="num fw-semibold" data-label="{{ __('apikeys.requests') }}">{{ number_format($cat['count']) }}</td>
                                    <td class="num fw-semibold" data-label="{{ __('apikeys.est_cost') }}">{{ $usd($cat['cost']) }}</td>
                                    <td class="num fw-semibold idr-cell" data-label="{{ __('apikeys.cost_idr') }}" data-idr="{{ $cat['cost'] }}" data-idr-prefix="Rp ">{{ $rp($cat['cost']) }}</td>
                                </tr>

                                @foreach($cat['rows'] as $op => $count)
                                    @include('usage-report.partials.op-row', ['op' => $op, 'count' => $count, 'child' => true])
                                @endforeach
                            @endforeach

                            @foreach($ungrouped as $op => $count)
                                @include('usage-report.partials.op-row', ['op' => $op, 'count' => $count, 'child' => false])
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td style="color:var(--muted);">{{ __('apikeys.subtotal') }}</td>
                                <td class="num fw-semibold" data-label="{{ __('apikeys.requests') }}">{{ number_format(array_sum($ops)) }}</td>
                                <td class="num fw-semibold" data-label="{{ __('apikeys.est_cost') }}">{{ $usd($totalCost) }}</td>
                                <td class="num fw-semibold idr-cell" data-label="{{ __('apikeys.cost_idr') }}" data-idr="{{ $totalCost }}" data-idr-prefix="Rp ">{{ $rp($totalCost) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="color:var(--muted);">{{ __('apikeys.vat', ['pct' => round($taxRate * 100, 2)]) }}</td>
                                <td class="num" style="color:var(--muted);" data-label="{{ __('apikeys.est_cost') }}">{{ $usd($tax) }}</td>
                                <td class="num idr-cell" style="color:var(--muted);" data-label="{{ __('apikeys.cost_idr') }}"
                                    data-idr="{{ $tax }}" data-idr-prefix="Rp ">{{ $rp($tax) }}</td>
                            </tr>
                            {{-- Service charge PT Alfa adalah jasa di luar AWS: dihitung dan
                                 ditagih dalam Rupiah dari total AWS + PPN, tanpa nilai dolar. --}}
                            <tr>
                                <td colspan="2" class="{{ $sc['active'] ? 'fw-semibold' : 'fw-bold' }}">{{ __('apikeys.total_vat') }}</td>
                                <td class="num" data-label="{{ __('apikeys.est_cost') }}">
                                    <div class="q-num" @if($sc['active']) style="font-size:0.95rem;" @else style="font-size:1.05rem;color:var(--green-text);" @endif>
                                        @if($tiny)
                                            ${{ $grandTiny[0] }}<span class="cents">.{{ $grandTiny[1] }}</span>
                                        @else
                                            ${{ $grandParts['int'] }}<span class="cents">.{{ $grandParts['cents'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="num idr-cell" data-label="{{ __('apikeys.cost_idr') }}">
                                    <div class="q-num" @if($sc['active']) style="font-size:0.95rem;" @else style="font-size:1.05rem;color:var(--green-text);" @endif
                                         data-idr="{{ $totalVat }}" data-idr-prefix="Rp ">{{ $rp($totalVat) }}</div>
                                </td>
                            </tr>
                            @if($sc['active'])
                                <tr>
                                    <td colspan="2" style="color:var(--muted);">
                                        {{ __('servicecharge.line') }}
                                        @if($scLabels)
                                            @foreach($scLabels as $basis => $label)
                                                <span data-sc-basis="{{ $basis }}" @if($sc['basis'] !== $basis) hidden @endif>({{ $label }})</span>
                                            @endforeach
                                        @else
                                            ({{ \App\Models\ServiceCharge::basisLabel($sc) }})
                                        @endif
                                    </td>
                                    <td class="num" style="color:var(--muted);" data-label="{{ __('apikeys.est_cost') }}">&mdash;</td>
                                    <td class="num idr-cell" style="color:var(--muted);" data-label="{{ __('apikeys.cost_idr') }}"
                                        data-idr data-idr-role="sc" data-idr-prefix="Rp ">{{ $rpIdr($sc['charge_idr']) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="fw-bold">{{ __('apikeys.grand_total') }}</td>
                                    <td class="num" style="color:var(--muted);" data-label="{{ __('apikeys.est_cost') }}">&mdash;</td>
                                    <td class="num idr-cell" data-label="{{ __('apikeys.cost_idr') }}">
                                        <div class="q-num" style="font-size:1.05rem;color:var(--green-text);"
                                             data-idr data-idr-role="grand" data-idr-prefix="Rp ">{{ $rpIdr($sc['grand_idr']) }}</div>
                                    </td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

    </div>

    <div class="detail-side">
        {{-- Kurs bawaan diambil dari menu Kurs & Pajak. Pembaca boleh menggesernya
             untuk hitungan kasar sendiri; angka rupiah di halaman ini ikut berubah,
             sementara angka dolarnya tidak tersentuh. --}}
        <div class="q-card rate-card {{ $rateOffPeriod ? 'off' : '' }}" data-rate-card
             data-default="{{ $idrRate }}"
             data-min="{{ (int) round($idrRate * 0.8) }}"
             data-max="{{ (int) round($idrRate * 1.2) }}"
             data-total-vat="{{ $totalVat }}"
             data-sc-pct="{{ $sc['percent'] }}"
             data-sc-min-idr="{{ $scMinIdr }}">
            <div class="rate-head">
                <div>
                    <div class="rate-lbl">{{ __('apikeys.rate_title') }}</div>
                    <div class="rate-val">
                        <span class="pfx">Rp</span>
                        <input type="text" inputmode="decimal" data-rate-num value="{{ number_format($idrRate, 0, ',', '.') }}">
                        <span class="sfx">/ USD</span>
                    </div>
                    @if($rateDate)
                        <div class="rate-meta">
                            {{ __('apikeys.rate_date', ['date' => $rateDate->translatedFormat('d M Y')]) }}
                            @if($activeRate->source) · {{ $activeRate->source }} @endif
                        </div>
                    @endif
                </div>

                <button type="button" class="rate-reset" data-rate-reset hidden>
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('apikeys.rate_reset') }}
                </button>

                <span class="rate-tag" data-rate-tag
                      data-official="{{ __('apikeys.rate_official') }}"
                      data-custom="{{ __('apikeys.rate_custom') }}">{{ __('apikeys.rate_official') }}</span>
            </div>

            @if($rateOffPeriod)
                <div class="rate-warn">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ __('apikeys.rate_off_period', [
                        'date'   => $rateDate->translatedFormat('d M Y'),
                        'period' => $rangeEnd->translatedFormat('F Y'),
                    ]) }}</span>
                </div>
            @endif

            <div class="rate-track">
                <input type="range" data-rate-range
                       min="{{ (int) round($idrRate * 0.8) }}"
                       max="{{ (int) round($idrRate * 1.2) }}"
                       step="10" value="{{ (int) $idrRate }}">
                {{-- Penanda kurs resmi: posisinya tetap, jadi kelihatan seberapa jauh
                     geserannya dari angka yang dipakai sistem. --}}
                <span class="rate-mark" data-rate-mark><span class="dot"></span><span class="tx">{{ __('apikeys.rate_official') }}</span></span>
            </div>

            <div class="rate-foot">
                <span>Rp {{ number_format(round($idrRate * 0.8), 0, ',', '.') }}</span>
                <span class="hint">{{ __('apikeys.rate_hint') }}</span>
                <span>Rp {{ number_format(round($idrRate * 1.2), 0, ',', '.') }}</span>
            </div>
        </div>
        @isset($usage)
            <div class="q-card">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-key-fill"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('apikeys.share_per_key') }}</div>
                            <div class="q-card-sub">{{ __('apikeys.share_per_key_sub') }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="u-table">
                        <thead>
                            <tr>
                                <th>{{ __('apikeys.share_key_col') }}</th>
                                <th class="num">{{ __('apikeys.requests') }}</th>
                                <th class="num">{{ __('apikeys.est_cost') }}</th>
                                <th class="num">{{ __('apikeys.cost_idr') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usage['per_key'] as $row)
                                <tr>
                                    <td>
                                        <span style="font-family:ui-monospace,monospace;font-weight:600;">{{ $row['name'] }}</span>
                                        @if($row['label'])
                                            <span style="display:block;font-size:0.7rem;color:var(--muted);">{{ $row['label'] }}</span>
                                        @endif
                                        @unless($row['has_data'])
                                            <span style="display:block;font-size:0.7rem;color:var(--warn-fg);">
                                                {{ __('apikeys.share_key_no_data') }}
                                            </span>
                                        @endunless

                                        {{-- Porsi menempel pada nama key, bukan kolom sendiri:
                                             kolomnya habis dipakai angka yang tidak boleh terpotong. --}}
                                        <div class="op-track" title="{{ __('apikeys.share_portion') }}">
                                            <div class="op-fill" style="width: {{ $totalCost > 0 ? ($row['cost'] / $totalCost) * 100 : 0 }}%;"></div>
                                        </div>
                                        <span class="op-rate">{{ __('apikeys.share_portion') }} {{ $totalCost > 0 ? number_format(($row['cost'] / $totalCost) * 100, 1) : '0,0' }}%</span>
                                    </td>
                                    <td class="num" data-label="{{ __('apikeys.requests') }}">{{ number_format($row['total']) }}</td>
                                    <td class="num" data-label="{{ __('apikeys.est_cost') }}">{{ $usd($row['cost']) }}</td>
                                    <td class="num idr-cell" data-label="{{ __('apikeys.cost_idr') }}" data-idr="{{ $row['cost'] }}" data-idr-prefix="Rp ">{{ $rp($row['cost']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endisset
    </div>
</div>
@endsection

@section('footer-note')
    {{ __('apikeys.share_disclaimer') }}
    @if($share->share_expires_at)
        · {{ __('apikeys.share_expires', ['date' => $share->share_expires_at->wib()->translatedFormat('d M Y')]) }}
    @endif
@endsection

@push('scripts')
<script>
    // Kurs yang bisa digeser. Hanya mengubah tampilan rupiah di halaman ini —
    // angka dolarnya (yang datang dari AWS) tidak ikut disentuh.
    (function () {
        const card = document.querySelector('[data-rate-card]');
        if (!card) return;

        const range = card.querySelector('[data-rate-range]');
        const num   = card.querySelector('[data-rate-num]');
        const mark  = card.querySelector('[data-rate-mark]');
        const tag   = card.querySelector('[data-rate-tag]');
        const reset = card.querySelector('[data-rate-reset]');

        const DEFAULT = Number(card.dataset.default);
        const MIN = Number(card.dataset.min);
        const MAX = Number(card.dataset.max);
        const STORE = 'gm-report-rate';

        // Service charge = max(persen x (AWS + PPN) dalam Rupiah, minimum Rp).
        // Mana yang menang bergantung pada kurs, jadi dihitung ulang di sini
        // seperti di export.
        const TOTAL_VAT  = Number(card.dataset.totalVat || 0);
        const SC_PCT     = Number(card.dataset.scPct || 0) / 100;
        const SC_MIN_IDR = Number(card.dataset.scMinIdr || 0);

        const clamp = (v) => Math.min(Math.max(v, MIN), MAX);
        const group = (v) => Math.round(v).toLocaleString('id-ID');
        const pct = (v) => ((v - MIN) / (MAX - MIN)) * 100;

        // Penanda kurs resmi dipasang sekali; posisinya tidak ikut bergeser.
        mark.style.left = pct(DEFAULT) + '%';

        function paint(value, typing) {
            // Kurs resmi boleh berdesimal (16.457,50 tampil "16.458"); angka yang
            // pembulatannya sama dianggap kurs resmi itu sendiri, sama seperti Excel.
            if (Math.round(value) === Math.round(DEFAULT)) value = DEFAULT;

            range.value = value;
            range.style.setProperty('--pct', pct(value) + '%');
            if (!typing) num.value = group(value);

            const official = value === DEFAULT;
            tag.textContent = official ? tag.dataset.official : tag.dataset.custom;
            tag.classList.toggle('custom', !official);
            reset.hidden = official;

            // Kurs khusus ikut ke file Excel; kurs resmi tidak perlu dikirim.
            const exp = document.querySelector('[data-export]');
            if (exp) {
                const url = new URL(exp.href);
                official ? url.searchParams.delete('rate') : url.searchParams.set('rate', Math.round(value));
                exp.href = url.toString();
            }

            const totalVatRp = TOTAL_VAT * value;
            const minWins = SC_MIN_IDR > totalVatRp * SC_PCT;
            const scRp = minWins ? SC_MIN_IDR : totalVatRp * SC_PCT;

            document.querySelectorAll('[data-idr]').forEach((el) => {
                const role = el.dataset.idrRole;
                const rp = role === 'sc' ? scRp
                    : role === 'grand' ? totalVatRp + scRp
                    : Number(el.dataset.idr) * value;
                el.textContent = (el.dataset.idrPrefix ?? '≈ Rp ') + group(rp);
            });

            // Kurs yang sedang dipakai ditulis ulang di setiap penanda kurs;
            // kurs manual ditandai warna peringatan.
            document.querySelectorAll('[data-rate-echo]').forEach((el) => {
                el.querySelector('[data-rate-echo-text]').textContent = el.dataset.tpl.replace(':rate', 'Rp ' + group(value));
                el.classList.toggle('custom', !official);
            });

            document.querySelectorAll('[data-sc-basis]').forEach((el) => {
                el.hidden = el.dataset.scBasis !== (minWins ? 'minimum' : 'percent');
            });

            try {
                official ? localStorage.removeItem(STORE) : localStorage.setItem(STORE, value);
            } catch (e) {}
        }

        range.addEventListener('input', () => paint(Number(range.value)));

        // Format id-ID: titik = ribuan, koma = desimal. Kurs manual dibulatkan ke
        // rupiah penuh, sama dengan yang dikirim ke export.
        const parseId = (s) => Math.round(parseFloat(s.replace(/[^\d,]/g, '').replace(',', '.'))) || 0;

        num.addEventListener('input', () => {
            const [int = '', ...rest] = num.value.replace(/[^\d,]/g, '').split(',');
            num.value = (int ? Number(int).toLocaleString('id-ID') : '') + (rest.length ? ',' + rest.join('').slice(0, 2) : '');
            const v = parseId(num.value);
            if (v) paint(clamp(v), true);
        });
        num.addEventListener('blur', () => paint(clamp(parseId(num.value) || DEFAULT)));

        reset.addEventListener('click', () => paint(DEFAULT));

        let start = DEFAULT;
        try {
            const saved = Number(localStorage.getItem(STORE));
            if (saved) start = clamp(saved);
        } catch (e) {}

        paint(start);
    })();
</script>
@endpush
