@extends('layouts.admin')

@section('title', 'Simulator Biaya')

@push('css')
<link href="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.css" rel="stylesheet" />
@endpush

@push('styles')
    /* =========================================================
       CATATAN: stack 'styles' di layouts/admin.blade.php berada DI DALAM
       elemen style milik layout. Blok ini HARUS berisi CSS mentah dan
       tidak boleh dibungkus tag style lagi — tag bersarang membuat parser
       CSS membuang rule pertama, yaitu tempat variabel warna didefinisikan,
       sehingga semua var() di bawah ikut kosong. Ikuti pola yang sama
       seperti admin/cost-settings dan admin/dashboard.

       SIMULATOR — bahasa visual mengikuti admin yang sudah ada:
       kartu putih, border #e2e8f0, aksen Grab green, font Inter.
       Warna operasi konsisten di seluruh halaman:
         hijau  = Maps (tile)      → murah, volume besar
         oranye = Places (search)  → mahal, volume kecil
         abu    = gratis
       Semua animasi mati otomatis kalau user memilih reduce-motion.
       ========================================================= */

    .sim-page {
        --line: #e2e8f0;
        --line-soft: #f1f5f9;
        --ink: #0f172a;
        --ink-2: #475569;
        --ink-3: #94a3b8;
        --surface-2: #f8fafc;
        --maps: #00B14F;
        --places: #f97316;
        --danger: #dc2626;
        --r-sm: 8px;
        --r-md: 12px;
        --r-lg: 16px;
    }

    /* ---------- Utilitas ---------- */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .sim-page :focus-visible {
        outline: 2px solid var(--maps);
        outline-offset: 2px;
        border-radius: var(--r-sm);
    }

    .num {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }

    /* =========================================================
       HEADER + STAT STRIP
       ========================================================= */
    .sim-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .sim-hero h1 {
        font-size: 1.4rem;
        font-weight: 800;
        margin: 0 0 5px;
        letter-spacing: -0.025em;
        color: var(--ink);
    }

    .sim-hero p {
        font-size: 0.84rem;
        color: #64748b;
        margin: 0;
        max-width: 68ch;
        line-height: 1.6;
    }

    .stat-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
        margin-bottom: 18px;
    }

    .stat {
        position: relative;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: var(--r-lg);
        padding: 16px 17px;
        overflow: hidden;
        transition: border-color .22s ease, transform .22s ease;
    }

    .stat:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
    }

    .stat-ico {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.92rem;
        margin-bottom: 11px;
    }

    .stat-ico.maps {
        background: rgba(0, 177, 79, .11);
        color: #009640;
    }

    .stat-ico.places {
        background: rgba(249, 115, 22, .12);
        color: #ea580c;
    }

    .stat-ico.neutral {
        background: #f1f5f9;
        color: #64748b;
    }

    .stat-label {
        font-size: 0.66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--ink-3);
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--ink);
        line-height: 1.1;
    }

    .stat-sub {
        font-size: 0.71rem;
        color: var(--ink-3);
        margin-top: 4px;
    }

    /* Kartu biaya dibuat gelap supaya jadi titik jangkar mata */
    .stat.accent {
        background: #0f172a;
        border-color: #0f172a;
    }

    .stat.accent .stat-label {
        color: #64748b;
    }

    .stat.accent .stat-value {
        color: #fff;
    }

    .stat.accent .stat-sub {
        color: #94a3b8;
    }

    .stat.accent .stat-ico {
        background: rgba(0, 177, 79, .18);
        color: #4ade80;
    }

    /* Kilau saat nilainya berubah */
    .flash::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 177, 79, .1);
        animation: flashOut .8s ease-out forwards;
        pointer-events: none;
    }

    @keyframes flashOut {
        to {
            opacity: 0;
        }
    }

    /* =========================================================
       KARTU
       ========================================================= */
    .sim-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: var(--r-lg);
        padding: 22px;
    }

    .sim-card+.sim-card {
        margin-top: 16px;
    }

    .sim-head {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 18px;
    }

    .sim-step {
        flex: 0 0 auto;
        width: 27px;
        height: 27px;
        border-radius: var(--r-sm);
        background: rgba(0, 177, 79, .11);
        color: #009640;
        font-size: 0.78rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .3s ease, color .3s ease;
    }

    .is-done .sim-step {
        background: var(--maps);
        color: #fff;
    }

    .is-done .sim-step::before {
        content: '\F26E';
        font-family: 'bootstrap-icons';
    }

    .is-done .sim-step span {
        display: none;
    }

    .sim-head h2 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 0 0 3px;
        color: var(--ink);
        letter-spacing: -0.012em;
    }

    .sim-head p {
        font-size: 0.79rem;
        color: var(--ink-3);
        margin: 0;
        line-height: 1.55;
    }

    /* Masuk bertahap */
    .reveal {
        opacity: 0;
        transform: translateY(12px);
        animation: revealUp .55s cubic-bezier(.2, .7, .3, 1) forwards;
    }

    .d1 {
        animation-delay: .05s;
    }

    .d2 {
        animation-delay: .1s;
    }

    .d3 {
        animation-delay: .15s;
    }

    .d4 {
        animation-delay: .2s;
    }

    @keyframes revealUp {
        to {
            opacity: 1;
            transform: none;
        }
    }

    /* =========================================================
       FORM
       ========================================================= */
    .sim-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--ink-2);
        display: block;
        margin-bottom: 6px;
    }

    .sim-input {
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        padding: 10px 13px;
        font-size: 0.84rem;
        width: 100%;
        font-family: inherit;
        color: var(--ink);
        background: #fff;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .sim-input::placeholder {
        color: #b6c2d2;
    }

    .sim-input:focus {
        outline: 0;
        border-color: var(--maps);
        box-shadow: 0 0 0 3px rgba(0, 177, 79, .13);
    }

    .sim-setup {
        display: grid;
        grid-template-columns: minmax(0, 2.2fr) minmax(0, 1fr) auto auto;
        gap: 10px;
        align-items: end;
    }

    /* =========================================================
       TOMBOL
       ========================================================= */
    .sim-btn {
        border: 1px solid var(--line);
        background: #fff;
        border-radius: 9px;
        padding: 9px 15px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--ink-2);
        cursor: pointer;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        white-space: nowrap;
        transition: border-color .18s, color .18s, background .18s, transform .12s;
    }

    .sim-btn:hover:not(:disabled) {
        border-color: var(--maps);
        color: #009640;
        background: rgba(0, 177, 79, .07);
    }

    .sim-btn:active:not(:disabled) {
        transform: translateY(1px);
    }

    .sim-btn:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    .sim-btn-primary {
        background: var(--maps);
        border-color: var(--maps);
        color: #fff;
    }

    .sim-btn-primary:hover:not(:disabled) {
        background: #009640;
        border-color: #009640;
        color: #fff;
    }

    .sim-btn-sm {
        padding: 5px 11px;
        font-size: 0.73rem;
        border-radius: 7px;
    }

    .btn-spin {
        width: 13px;
        height: 13px;
        border: 2px solid rgba(255, 255, 255, .4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* =========================================================
       CALLOUT
       ========================================================= */
    .sim-note {
        font-size: 0.77rem;
        line-height: 1.65;
        background: var(--surface-2);
        border: 1px solid var(--line);
        border-left: 3px solid #cbd5e1;
        padding: 11px 14px;
        border-radius: 0 9px 9px 0;
        color: var(--ink-2);
    }

    .sim-note.warn {
        background: #fffbeb;
        border-color: #fde68a;
        border-left-color: #f59e0b;
        color: #92400e;
    }

    .sim-note.error {
        background: #fef2f2;
        border-color: #fecaca;
        border-left-color: var(--danger);
        color: #991b1b;
    }

    /* =========================================================
       PETA
       ========================================================= */
    /* Kedua kolom dibiarkan meregang sama tinggi, lalu area peta yang
       menyerap sisa ruangnya — supaya tidak ada celah kosong di bawah
       card peta ketika panel kanan lebih panjang. */
    .sim-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.62fr) minmax(340px, 1fr);
        gap: 16px;
        align-items: stretch;
    }

    #cardMap {
        display: flex;
        flex-direction: column;
    }

    .map-frame {
        position: relative;
        border-radius: var(--r-md);
        overflow: hidden;
        background: #eef2f6;
        flex: 1 1 auto;
        min-height: 440px;
        border: 1px solid var(--line);
    }

    #simMap {
        width: 100%;
        height: 100%;
    }

    /* HUD melayang di atas peta — angka bergerak persis saat user menggeser */
    .map-hud {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 2;
        display: flex;
        gap: 1px;
        background: rgba(226, 232, 240, .8);
        border-radius: 10px;
        overflow: hidden;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
        opacity: 0;
        transform: translateY(-6px);
        transition: opacity .3s ease, transform .3s ease;
        pointer-events: none;
    }

    .map-hud.on {
        opacity: 1;
        transform: none;
    }

    .hud-cell {
        background: rgba(255, 255, 255, .92);
        padding: 7px 12px;
        min-width: 64px;
    }

    .hud-k {
        font-size: 0.6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--ink-3);
    }

    .hud-v {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -0.02em;
        line-height: 1.25;
    }

    .hud-v.maps {
        color: #009640;
    }

    /* Nilai uang butuh banyak desimal — dikecilkan supaya HUD tidak melebar */
    .hud-v.money {
        font-size: 0.78rem;
    }

    .map-overlay {
        position: absolute;
        inset: 0;
        z-index: 3;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-align: center;
        padding: 24px;
        background: #f8fafc;
        color: var(--ink-3);
        font-size: 0.84rem;
        transition: opacity .35s ease, visibility .35s ease;
    }

    .map-overlay[hidden] {
        display: none;
    }

    .map-overlay.fading {
        opacity: 0;
        visibility: hidden;
    }

    .map-overlay .bi {
        font-size: 1.8rem;
        color: #cbd5e1;
    }

    .map-skeleton {
        position: absolute;
        inset: 0;
        background: linear-gradient(100deg, #eef2f6 30%, #f8fafc 50%, #eef2f6 70%);
        background-size: 220% 100%;
        animation: shimmer 1.3s linear infinite;
    }

    @keyframes shimmer {
        to {
            background-position: -220% 0;
        }
    }

    .map-toolbar {
        display: flex;
        gap: 9px;
        margin-top: 13px;
        flex-wrap: wrap;
        align-items: center;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 5px 11px;
        border-radius: 20px;
        background: var(--surface-2);
        border: 1px solid var(--line);
        color: var(--ink-2);
    }

    .switch {
        font-size: 0.77rem;
        color: var(--ink-2);
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        user-select: none;
    }

    .switch input {
        accent-color: var(--maps);
        width: 15px;
        height: 15px;
        cursor: pointer;
    }

    /* =========================================================
       METER
       ========================================================= */
    /* Tidak lagi sticky: kolom ini yang menentukan tinggi baris, dan card
       peta di sebelahnya yang mengikuti. Sticky pada grid item yang
       meregang tidak punya ruang untuk bergerak. */
    .meter-col {
        display: flex;
        flex-direction: column;
    }

    .meter-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 3px;
    }

    .live-dot {
        position: relative;
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #cbd5e1;
        margin-right: 7px;
        transition: background .2s ease;
    }

    .live-dot.active {
        background: var(--maps);
    }

    .live-dot.active::after {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 50%;
        border: 2px solid var(--maps);
        animation: ping .9s cubic-bezier(0, 0, .2, 1) infinite;
    }

    @keyframes ping {

        75%,
        100% {
            transform: scale(2.1);
            opacity: 0;
        }
    }

    /* Sparkline aktivitas — 1 batang = 1 detik terakhir */
    .spark {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        height: 40px;
        padding: 8px 10px 7px;
        background: var(--surface-2);
        border: 1px solid var(--line);
        border-radius: 10px;
        margin: 12px 0 14px;
    }

    .spark i {
        flex: 1;
        min-width: 0;
        background: #dbe4ee;
        border-radius: 1.5px;
        height: 4%;
        transition: height .32s cubic-bezier(.2, .7, .3, 1), background .32s ease;
    }

    .spark i.hot {
        background: var(--maps);
    }

    .spark-empty {
        font-size: 0.72rem;
        color: var(--ink-3);
        align-self: center;
        width: 100%;
        text-align: center;
    }

    /* Pembagi biaya Maps vs Places — inti argumennya ada di sini */
    .split {
        margin-bottom: 15px;
    }

    .split-bar {
        display: flex;
        height: 9px;
        border-radius: 5px;
        overflow: hidden;
        background: var(--line-soft);
    }

    .split-bar i {
        display: block;
        width: 0;
        transition: width .6s cubic-bezier(.2, .7, .3, 1);
    }

    .split-bar i.maps {
        background: var(--maps);
    }

    .split-bar i.places {
        background: var(--places);
    }

    .split-legend {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 8px;
        font-size: 0.73rem;
    }

    .split-legend b {
        font-weight: 700;
        color: var(--ink);
    }

    .legend-k {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--ink-2);
    }

    .op-row {
        padding: 10px 0;
        border-bottom: 1px solid var(--line-soft);
        animation: rowIn .35s cubic-bezier(.2, .7, .3, 1) both;
    }

    .op-row:last-child {
        border-bottom: 0;
    }

    @keyframes rowIn {
        from {
            opacity: 0;
            transform: translateX(-6px);
        }
    }

    .op-top {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 10px;
        align-items: baseline;
    }

    .op-name {
        font-size: 0.8rem;
        font-weight: 600;
        color: #334155;
        display: flex;
        align-items: center;
        gap: 7px;
        min-width: 0;
    }

    .op-name span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .op-meta {
        font-size: 0.69rem;
        color: var(--ink-3);
        margin-top: 2px;
    }

    .op-count {
        font-weight: 700;
        color: var(--ink);
        font-size: 0.84rem;
        min-width: 52px;
        text-align: right;
        line-height: 1.15;
    }

    .op-count small {
        display: block;
        font-size: 0.58rem;
        font-weight: 700;
        color: var(--ink-3);
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-top: 1px;
    }

    .op-sub {
        font-size: 0.68rem;
        color: var(--ink-3);
        margin-top: 3px;
    }

    .op-sub .sep {
        color: #cbd5e1;
        margin: 0 3px;
    }

    .op-cost {
        min-width: 72px;
        text-align: right;
        font-weight: 600;
        font-size: 0.78rem;
        color: var(--ink-3);
    }

    .op-cost.billed {
        color: #ea580c;
    }

    .op-bar {
        height: 4px;
        border-radius: 3px;
        background: var(--line-soft);
        margin-top: 7px;
        overflow: hidden;
    }

    .op-bar i {
        display: block;
        height: 100%;
        border-radius: 3px;
        width: 0;
        transition: width .55s cubic-bezier(.2, .7, .3, 1);
    }

    .bg-maps {
        background: var(--maps);
    }

    .bg-places {
        background: var(--places);
    }

    .bg-free {
        background: #cbd5e1;
    }

    .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex: 0 0 auto;
    }

    .meter-empty {
        text-align: center;
        padding: 24px 10px;
        color: var(--ink-3);
        font-size: 0.79rem;
        line-height: 1.6;
    }

    .meter-empty .bi {
        font-size: 1.5rem;
        color: #dbe3ec;
        display: block;
        margin-bottom: 7px;
    }

    .total-box {
        background: var(--surface-2);
        border: 1px solid var(--line);
        border-radius: var(--r-md);
        padding: 15px;
        margin-top: 15px;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 0.79rem;
        color: var(--ink-2);
        padding: 3px 0;
    }

    .proj {
        margin-top: 11px;
        padding-top: 10px;
        border-top: 1px dashed var(--line);
        font-size: 0.73rem;
        color: var(--ink-3);
    }

    .proj-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 10px;
    }

    .proj-head b {
        color: var(--ink-2);
        font-weight: 700;
    }

    .proj-val {
        font-size: 0.92rem;
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -0.02em;
        white-space: nowrap;
    }

    .proj-range {
        width: 100%;
        margin: 10px 0 0;
        accent-color: var(--maps);
        cursor: pointer;
    }

    .proj-scale {
        display: flex;
        justify-content: space-between;
        font-size: 0.64rem;
        color: #b6c2d2;
        margin-top: 1px;
    }

    .total-line.grand {
        font-size: 1.2rem;
        font-weight: 800;
        color: #009640;
        padding-top: 10px;
        margin-top: 8px;
        border-top: 1px solid var(--line);
        letter-spacing: -0.02em;
    }

    /* =========================================================
       SKENARIO
       ========================================================= */
    .scn-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
        margin-top: 16px;
    }

    .scn {
        position: relative;
        border: 1px solid var(--line);
        border-radius: var(--r-md);
        padding: 15px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: border-color .2s ease, transform .2s ease, background .2s ease;
    }

    .scn:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
    }

    .scn.running {
        background: rgba(0, 177, 79, .05);
        border-color: rgba(0, 177, 79, .38);
    }

    .scn-ico {
        width: 27px;
        height: 27px;
        border-radius: var(--r-sm);
        background: #f1f5f9;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        margin-bottom: 10px;
    }

    .scn.done .scn-ico {
        background: rgba(0, 177, 79, .12);
        color: #009640;
    }

    .scn-title {
        font-size: 0.84rem;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.01em;
    }

    .scn-desc {
        font-size: 0.74rem;
        color: var(--ink-3);
        margin-top: 5px;
        line-height: 1.55;
        flex: 1;
    }

    .scn-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-top: 13px;
    }

    .scn-result {
        font-size: 0.73rem;
        color: var(--ink-3);
        opacity: 0;
        transform: translateY(4px);
        transition: opacity .3s ease, transform .3s ease;
    }

    .scn.done .scn-result {
        opacity: 1;
        transform: none;
    }

    .scn-result b {
        color: var(--ink);
        font-weight: 700;
        font-size: 0.82rem;
    }

    .scn-progress {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 2px;
        background: rgba(0, 177, 79, .16);
        overflow: hidden;
        opacity: 0;
        transition: opacity .2s ease;
    }

    .scn.running .scn-progress {
        opacity: 1;
    }

    .scn-progress::after {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--maps);
        transform: translateX(-100%);
        animation: sweep 1.15s ease-in-out infinite;
    }

    @keyframes sweep {
        50% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(100%);
        }
    }

    /* ---------- Pemilih mode Normal / Hemat ---------- */
    .seg {
        display: inline-flex;
        border: 1px solid var(--line);
        border-radius: 9px;
        overflow: hidden;
        background: #fff;
    }

    .seg button {
        border: 0;
        background: #fff;
        padding: 7px 15px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--ink-2);
        cursor: pointer;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .18s ease, color .18s ease;
    }

    .seg button+button {
        border-left: 1px solid var(--line);
    }

    .seg button:hover:not(.on) {
        background: var(--surface-2);
    }

    .seg button.on {
        background: var(--maps);
        color: #fff;
    }

    .saver-panel {
        border: 1px solid var(--line);
        border-radius: var(--r-md);
        padding: 15px 16px;
        margin-top: 14px;
        background: var(--surface-2);
    }

    .saver-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .saver-opts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(215px, 1fr));
        gap: 10px 20px;
        margin-top: 12px;
    }

    .saver-opt {
        font-size: 0.77rem;
        color: var(--ink-2);
        display: flex;
        align-items: flex-start;
        gap: 8px;
        cursor: pointer;
        line-height: 1.5;
    }

    .saver-opt input {
        accent-color: var(--maps);
        width: 15px;
        height: 15px;
        margin-top: 1px;
        flex: 0 0 auto;
        cursor: pointer;
    }

    .saver-opt small {
        display: block;
        color: var(--ink-3);
        font-size: 0.7rem;
    }

    /* ---------- Kolom selisih di tabel hasil ---------- */
    .delta-good {
        color: #009640;
        font-weight: 700;
    }

    .delta-none {
        color: var(--ink-3);
    }

    .delta-bad {
        color: #b45309;
        font-weight: 700;
    }

    .warn-mark {
        display: inline-block;
        font-size: 0.63rem;
        font-weight: 700;
        color: #b45309;
        background: #fef3c7;
        border-radius: 20px;
        padding: 1px 7px;
        margin-left: 6px;
        white-space: nowrap;
    }

    .sum-row td {
        background: var(--surface-2) !important;
        font-weight: 700;
        border-top: 2px solid var(--line);
    }

    /* =========================================================
       TABEL HASIL
       ========================================================= */
    .result-table {
        width: 100%;
        font-size: 0.8rem;
        border-collapse: collapse;
        min-width: 660px;
    }

    .result-table th {
        text-align: left;
        font-size: 0.66rem;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--ink-3);
        font-weight: 700;
        padding: 10px 11px;
        border-bottom: 1px solid var(--line);
        white-space: nowrap;
        background: var(--surface-2);
    }

    .result-table th:first-child {
        border-radius: var(--r-sm) 0 0 0;
    }

    .result-table th:last-child {
        border-radius: 0 var(--r-sm) 0 0;
    }

    .result-table th.r,
    .result-table td.r {
        text-align: right;
    }

    .result-table td {
        padding: 11px;
        border-bottom: 1px solid var(--line-soft);
        color: #334155;
    }

    .result-table tbody tr {
        animation: rowIn .35s cubic-bezier(.2, .7, .3, 1) both;
        transition: background .18s ease;
    }

    .result-table tbody tr:hover td {
        background: var(--surface-2);
    }

    .table-scroll {
        overflow-x: auto;
        border: 1px solid var(--line);
        border-radius: var(--r-md);
    }

    #resultsWrap.showing {
        animation: expand .45s cubic-bezier(.2, .7, .3, 1) both;
    }

    @keyframes expand {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    .place-item {
        padding: 8px 0;
        border-bottom: 1px solid var(--line-soft);
        animation: rowIn .3s ease both;
    }

    .place-item:last-child {
        border-bottom: 0;
    }

    /* =========================================================
       RESPONSIF
       ========================================================= */
    @media (max-width: 1200px) {
        .stat-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .sim-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .map-frame {
            min-height: 390px;
        }
    }

    @media (max-width: 640px) {
        .stat-strip {
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
        }

        .sim-setup {
            grid-template-columns: 1fr 1fr;
        }

        .sim-setup>div:first-child {
            grid-column: 1 / -1;
        }

        .sim-card {
            padding: 17px;
        }

        .map-frame {
            min-height: 320px;
        }

        .stat-value {
            font-size: 1.3rem;
        }

        .total-line.grand {
            font-size: 1.05rem;
        }
    }

    /* ---------- Hormati preferensi reduce-motion ---------- */
    @media (prefers-reduced-motion: reduce) {

        .sim-page *,
        .sim-page *::before,
        .sim-page *::after {
            animation-duration: .001ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .001ms !important;
        }
    }
@endpush

@section('content')
<div class="sim-page">

    <div class="sim-hero reveal">
        <div>
            <h1>Simulator Biaya AWS Location</h1>
            <p>
                Pakai API key sendiri, gerakkan peta, lalu lihat berapa request yang terpanggil dan berapa biayanya.
                Request ditembak langsung ke AWS dari browser — jadi angkanya sama dengan yang ditagih.
            </p>
        </div>
    </div>

    {{-- ============ STAT STRIP ============ --}}
    <div class="stat-strip reveal d1">
        <div class="stat" id="statReq">
            <div class="stat-ico neutral" aria-hidden="true"><i class="bi bi-activity"></i></div>
            <div class="stat-label">Request Ditagih</div>
            <div class="stat-value num" id="sReq">0</div>
            <div class="stat-sub num" id="sReqRaw">0 diminta peta</div>
        </div>
        <div class="stat" id="statTile">
            <div class="stat-ico maps" aria-hidden="true"><i class="bi bi-grid-3x3"></i></div>
            <div class="stat-label">Tile Unik</div>
            <div class="stat-value num" id="sTile">0</div>
            <div class="stat-sub num" id="sTileCost">$0.00 · $0.04 per 1.000</div>
        </div>
        <div class="stat" id="statPlaces">
            <div class="stat-ico places" aria-hidden="true"><i class="bi bi-search"></i></div>
            <div class="stat-label">Panggilan Places</div>
            <div class="stat-value num" id="sPlaces">0</div>
            <div class="stat-sub num" id="sPlacesCost">$0.00 · $0.50 per 1.000</div>
        </div>
        <div class="stat accent" id="statCost">
            <div class="stat-ico" aria-hidden="true"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">Biaya Berjalan</div>
            <div class="stat-value num" id="sCost">$0.0000</div>
            <div class="stat-sub num" id="sCostIdr">≈ Rp 0 · sudah termasuk PPN</div>
        </div>
    </div>

    {{-- ============ 1. API KEY ============ --}}
    <div class="sim-card reveal d2" id="cardKey">
        <div class="sim-head">
            <div class="sim-step" aria-hidden="true"><span>1</span></div>
            <div>
                <h2>API Key</h2>
                <p>Pakai key khusus tes, bukan key produksi. Key hanya disimpan di browser ini.</p>
            </div>
        </div>

        <form id="setupForm" novalidate>
            <div class="sim-setup">
                <div>
                    <label class="sim-label" for="apiKeyInput">API Key</label>
                    <input type="password" id="apiKeyInput" class="sim-input" placeholder="v1.public.xxxxx…"
                        autocomplete="off" spellcheck="false" aria-describedby="keyStatus">
                </div>
                <div>
                    <label class="sim-label" for="regionInput">Region</label>
                    <input type="text" id="regionInput" class="sim-input" value="{{ $region }}" spellcheck="false">
                </div>
                <button class="sim-btn" id="toggleKeyBtn" type="button" aria-label="Tampilkan API key" aria-pressed="false">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
                <button class="sim-btn sim-btn-primary" id="loadMapBtn" type="submit">
                    <i class="bi bi-play-fill" aria-hidden="true"></i> Muat Peta
                </button>
            </div>
        </form>

        <div style="display:flex;gap:15px;align-items:center;margin-top:13px;flex-wrap:wrap;">
            <label class="switch" for="rememberKey">
                <input type="checkbox" id="rememberKey"> Ingat key di browser ini
            </label>
            <button class="sim-btn sim-btn-sm" id="forgetKeyBtn" type="button">Hapus key tersimpan</button>
            <span id="keyStatus" style="font-size:0.75rem;color:#94a3b8;" role="status"></span>
        </div>

        <div id="setupError" class="sim-note error" style="margin-top:13px;display:none;" role="alert"></div>

        <div class="sim-note warn" style="margin-top:13px;">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <strong>Simulasi ini menagih beneran.</strong> Setiap tile dan pencarian yang terhitung di halaman ini
            adalah request nyata ke AWS. Biaya yang tampil adalah biaya yang benar-benar keluar dari key tersebut.
        </div>
    </div>

    {{-- ============ 2. PETA + METER ============ --}}
    <div class="sim-grid reveal d3" style="margin-top:16px;">

        <div class="sim-card" id="cardMap" style="margin-top:0;">
            <div class="sim-head" style="margin-bottom:14px;">
                <div class="sim-step" aria-hidden="true"><span>2</span></div>
                <div>
                    <h2>Peta</h2>
                    <p>Zoom, geser, klik — semua terhitung seketika.</p>
                </div>
            </div>

            <div class="map-frame">
                <div id="simMap" role="application" aria-label="Peta simulasi AWS Location"></div>

                <div class="map-hud" id="mapHud" aria-hidden="true">
                    <div class="hud-cell">
                        <div class="hud-k">Zoom</div>
                        <div class="hud-v num" id="hudZoom">—</div>
                    </div>
                    <div class="hud-cell">
                        <div class="hud-k">Tile</div>
                        <div class="hud-v num maps" id="hudTile">0</div>
                    </div>
                    <div class="hud-cell">
                        <div class="hud-k">Biaya tile</div>
                        <div class="hud-v num money maps" id="hudTileCost">$0</div>
                    </div>
                    <div class="hud-cell">
                        <div class="hud-k">Total +PPN</div>
                        <div class="hud-v num money" id="hudTotal">$0</div>
                    </div>
                </div>

                <div class="map-overlay" id="mapIdle">
                    <i class="bi bi-map" aria-hidden="true"></i>
                    <div>Masukkan API key lalu klik <strong>Muat Peta</strong></div>
                </div>

                <div class="map-overlay" id="mapLoading" hidden aria-live="polite">
                    <div class="map-skeleton" aria-hidden="true"></div>
                    <div style="position:relative;font-weight:600;color:#64748b;">Memuat peta…</div>
                </div>
            </div>

            <div class="map-toolbar">
                <label class="sim-label" for="maxZoomSelect" style="margin:0;">Batas zoom</label>
                <select id="maxZoomSelect" class="sim-input" style="width:auto;padding:6px 10px;font-size:0.77rem;">
                    <option value="22">Tanpa batas (22)</option>
                    <option value="18">18</option>
                    <option value="16" selected>16</option>
                    <option value="14">14</option>
                </select>

                <label class="switch" for="clickReverse" style="margin-left:6px;">
                    <input type="checkbox" id="clickReverse"> Klik peta = reverse geocode
                </label>

                <span class="chip" style="margin-left:auto;">
                    <span class="dot bg-maps" aria-hidden="true"></span> Maps
                    <span class="dot bg-places" style="margin-left:7px;" aria-hidden="true"></span> Places
                </span>
            </div>
        </div>

        <div class="meter-col">
            {{-- METER --}}
            <div class="sim-card" style="margin-top:0;">
                <div class="meter-head">
                    <h2 style="font-size:0.95rem;font-weight:700;margin:0;color:var(--ink);">
                        <span class="live-dot" id="liveDot" aria-hidden="true"></span>Request Terhitung
                    </h2>
                    <button class="sim-btn sim-btn-sm" id="resetCounterBtn" type="button">Reset</button>
                </div>
                <p style="font-size:0.76rem;color:var(--ink-3);margin:0;line-height:1.55;">
                    Dihitung sejak <strong>Muat Peta</strong> atau <strong>Reset</strong> terakhir, termasuk skenario.
                    URL yang sama hanya dihitung sekali — <strong>dengan asumsi</strong> permintaan ulang dilayani
                    cache browser sehingga tidak pernah sampai ke AWS. Halaman ini tidak mengamati jaringan, jadi
                    kalau cache browser dimatikan, tagihan sebenarnya mendekati angka “permintaan peta”.
                </p>

                <div class="spark" id="spark" aria-hidden="true"></div>

                <div class="split" id="splitWrap" style="display:none;">
                    <div class="split-bar">
                        <i class="maps" id="splitMaps"></i>
                        <i class="places" id="splitPlaces"></i>
                    </div>
                    <div class="split-legend">
                        <span class="legend-k"><span class="dot bg-maps" aria-hidden="true"></span> Maps <b id="splitMapsPct">0%</b></span>
                        <span class="legend-k"><span class="dot bg-places" aria-hidden="true"></span> Places <b id="splitPlacesPct">0%</b></span>
                    </div>
                    <p style="font-size:0.7rem;color:var(--ink-3);margin:7px 0 0;line-height:1.5;" id="splitNote"></p>
                </div>

                <div id="opBreakdown"></div>

                <div class="total-box" aria-live="polite" aria-atomic="true">
                    <div class="total-line">
                        <span>Request ditagih</span>
                        <strong id="totalReq" class="num">0</strong>
                    </div>
                    <div class="total-line" style="font-size:0.72rem;color:var(--ink-3);padding-top:0;">
                        <span>dari <span id="totalRaw" class="num">0</span> permintaan peta</span>
                    </div>
                    <div class="total-line" style="margin-top:6px;"><span>Subtotal</span><span id="subtotalUsd" class="num">$0</span></div>
                    <div class="total-line"><span>PPN {{ round($taxRate * 100, 2) }}%</span><span id="taxUsd" class="num">$0</span></div>
                    <div class="total-line grand"><span>Total</span><span id="grandUsd" class="num">$0</span></div>
                    <div class="total-line" style="justify-content:flex-end;padding-top:2px;">
                        <span id="grandIdr" class="num" style="font-size:0.79rem;color:var(--ink-3);">≈ Rp 0</span>
                    </div>

                    {{-- Angka per sesi terlalu kecil untuk bermakna. Proyeksi ini
                         yang biasanya dipakai orang saat membaca hasilnya.
                         Slidernya juga mengatur kolom di tabel skenario, supaya
                         satu halaman tidak memakai dua basis sesi berbeda. --}}
                    <div class="proj">
                        <div class="proj-head">
                            <span>Kalau terjadi di <b id="sesiLabel" class="num">100.000</b> sesi</span>
                            <span class="proj-val num" id="projK">$0</span>
                        </div>
                        <label class="sr-only" for="sesiRange">Jumlah sesi untuk proyeksi biaya</label>
                        <input type="range" id="sesiRange" class="proj-range"
                            min="0" max="8" step="1" value="4">
                        <div class="proj-scale" aria-hidden="true">
                            <span>1rb</span><span>100rb</span><span>10jt</span>
                        </div>
                    </div>
                </div>

                <p style="font-size:0.7rem;color:var(--ink-3);margin:11px 0 0;line-height:1.55;">
                    Kurs Rp {{ number_format($usdRate, 0, ',', '.') }}/USD
                    @if($rateDate) — per {{ $rateDate }} @endif
                    · <a href="{{ route('admin.cost-settings.index') }}" style="color:var(--ink-2);">ubah</a>
                </p>
            </div>

            {{-- PLACES --}}
            <div class="sim-card">
                <h2 style="font-size:0.95rem;font-weight:700;margin:0 0 3px;color:var(--ink);">Uji Places</h2>
                <p style="font-size:0.77rem;color:var(--ink-3);margin:0 0 13px;">
                    Satu pencarian = biaya 12,5 tile. Coba dan lihat sendiri.
                </p>

                <label class="sr-only" for="placeQuery">Kata kunci pencarian tempat</label>
                <input type="search" id="placeQuery" class="sim-input" placeholder="mis. Stasiun Gambir">

                <div style="display:flex;gap:9px;margin-top:10px;">
                    <button class="sim-btn" id="btnSuggest" type="button" style="flex:1;">Suggest</button>
                    <button class="sim-btn" id="btnSearchText" type="button" style="flex:1;">SearchText</button>
                </div>

                <div id="placeResult" style="font-size:0.77rem;color:var(--ink-2);margin-top:12px;max-height:170px;overflow:auto;"
                    role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>

    {{-- ============ 3. SKENARIO ============ --}}
    <div class="sim-card reveal d4" style="margin-top:16px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
            <div class="sim-head" style="margin-bottom:0;">
                <div class="sim-step" aria-hidden="true"><span>3</span></div>
                <div>
                    <h2>Skenario Zoom &amp; Geser</h2>
                    <p style="max-width:68ch;">
                        Tiap skenario memuat ulang peta dari nol supaya cache kosong, lalu menjalankan gerakan
                        kamera yang sama. Hasilnya bisa dibandingkan apel ke apel.
                    </p>
                </div>
            </div>
            <div style="display:flex;gap:9px;flex-wrap:wrap;align-items:center;">
                <div class="seg" role="group" aria-label="Mode peta untuk menjalankan skenario">
                    <button type="button" class="mode-btn on" data-mode="normal" aria-pressed="true">Normal</button>
                    <button type="button" class="mode-btn" data-mode="saver" aria-pressed="false">
                        <i class="bi bi-piggy-bank" aria-hidden="true"></i> Hemat
                    </button>
                </div>
                <button class="sim-btn" id="clearResultsBtn" type="button">Kosongkan hasil</button>
                <button class="sim-btn" id="runAllBtn" type="button">
                    <i class="bi bi-play-circle" aria-hidden="true"></i> Jalankan semua
                </button>
                <button class="sim-btn sim-btn-primary" id="compareBtn" type="button">
                    <i class="bi bi-bar-chart-line-fill" aria-hidden="true"></i> Bandingkan Normal vs Hemat
                </button>
            </div>
        </div>

        {{-- Pengaturan mode hemat --}}
        <div class="saver-panel">
            <div class="saver-title">
                <i class="bi bi-piggy-bank" aria-hidden="true"></i> Isi Mode Hemat
            </div>
            <p style="font-size:0.75rem;color:var(--ink-3);margin:5px 0 0;line-height:1.55;">
                Centang apa saja yang dianggap "hemat". Tombol Bandingkan menjalankan tiap skenario dua kali —
                sekali tanpa batasan ini, sekali dengan — lalu menghitung selisih biayanya.
            </p>

            <div class="saver-opts">
                <label class="saver-opt" for="optMaxZoom">
                    <input type="checkbox" id="optMaxZoom" checked>
                    <span>Batasi zoom maksimum ke 16
                        <small>Menghemat saat user menjelajah, bukan saat diam.
                            Untuk satu layar diam justru bisa menambah tile — di atas
                            batas zoom sumber (±16) MapLibre membesarkan satu tile
                            yang sama, jadi zoom dalam malah butuh lebih sedikit.</small></span>
                </label>
                <label class="saver-opt" for="optJump">
                    <input type="checkbox" id="optJump" checked>
                    <span>Ganti <code>flyTo</code> dengan <code>jumpTo</code>
                        <small>Hindari tile di zoom perantara sepanjang animasi</small></span>
                </label>
                <label class="saver-opt" for="optPitch">
                    <input type="checkbox" id="optPitch" checked>
                    <span>Matikan pitch &amp; rotate
                        <small>Peta miring menarik tile jauh lebih banyak</small></span>
                </label>
                <label class="saver-opt" for="optWorld">
                    <input type="checkbox" id="optWorld" checked>
                    <span>Matikan <code>renderWorldCopies</code>
                        <small>Jangan render salinan dunia di kiri-kanan</small></span>
                </label>
                <label class="saver-opt" for="optBounds">
                    <input type="checkbox" id="optBounds">
                    <span>Kunci area ke Jabodetabek
                        <small>Menghemat besar, tapi mengubah apa yang bisa dijangkau user</small></span>
                </label>
            </div>
        </div>

        <div class="scn-grid" id="scenarioList"></div>
        <p id="scenarioStatus" class="sr-only" role="status" aria-live="polite"></p>

        <div id="resultsWrap" style="margin-top:22px;display:none;">
            <h2 style="font-size:0.95rem;font-weight:700;margin:0 0 11px;color:var(--ink);">Hasil</h2>
            <div class="table-scroll">
                <table class="result-table">
                    <caption class="sr-only">Perbandingan jumlah tile dan biaya tiap skenario antara mode normal dan mode hemat</caption>
                    <thead>
                        <tr>
                            <th scope="col">Skenario</th>
                            <th scope="col" class="r">Tile normal</th>
                            <th scope="col" class="r">Tile hemat</th>
                            <th scope="col" class="r">Hemat</th>
                            <th scope="col" class="r">Normal / <span id="thSesiA">100rb</span> sesi</th>
                            <th scope="col" class="r">Hemat / <span id="thSesiB">100rb</span> sesi</th>
                            <th scope="col" class="r">Selisih</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody"></tbody>
                </table>
            </div>
            <div class="sim-note" style="margin-top:15px;" id="resultNote"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/maplibre-gl@3.6.0/dist/maplibre-gl.js"></script>
<script>
    (function () {
        'use strict';

        const PRICING  = @json($pricing);
        const TAX_RATE = {{ $taxRate }};
        const USD_RATE = {{ $usdRate }};
        const STORAGE_KEY = 'sim_aws_api_key';

        const JAKARTA  = [106.8456, -6.2088];
        const SURABAYA = [112.7521, -7.2575];

        const REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const SPARK_BARS = 44;

        // Nama operasi mengikuti metric CloudWatch AWS.
        // group menentukan warna: maps (hijau), places (oranye), free (abu).
        const OP_META = [
            { op: 'GetMapTile',            label: 'GetMapTile',            group: 'maps',   note: 'tile peta' },
            { op: 'GetMapStyleDescriptor', label: 'GetMapStyleDescriptor', group: 'free',   note: 'gratis' },
            { op: 'GetMapGlyphs',          label: 'GetMapGlyphs',          group: 'free',   note: 'gratis' },
            { op: 'GetMapSprites',         label: 'GetMapSprites',         group: 'free',   note: 'gratis' },
            { op: 'Suggest',               label: 'Suggest',               group: 'places', note: 'autocomplete' },
            { op: 'SearchText',            label: 'SearchText',            group: 'places', note: 'pencarian' },
            { op: 'ReverseGeocode',        label: 'ReverseGeocode',        group: 'places', note: 'koordinat → alamat' },
            { op: 'GetPlace',              label: 'GetPlace',              group: 'places', note: 'detail tempat' },
        ];

        const PLACES_OPS = OP_META.filter(m => m.group === 'places').map(m => m.op);
        const MAPS_OPS   = OP_META.filter(m => m.group === 'maps').map(m => m.op);

        const $ = (id) => document.getElementById(id);

        let map = null;
        let apiKey = '';
        let region = '{{ $region }}';
        let busy = false;

        /* =========================================================
           METER
           sessionMeter  → akumulasi yang tampil di stat strip & panel
           scenarioMeter → di-reset tiap skenario, dipakai untuk tabel hasil
           URL unik dicatat supaya request yang persis sama tidak dihitung
           dua kali — mencerminkan cache browser yang memang tidak menagih.
           ========================================================= */
        // counts = URL unik  → inilah yang ditagih AWS
        // raw    = tiap panggilan → termasuk yang dilayani cache browser
        const newMeter = () => ({ counts: {}, raw: {}, seen: new Set() });

        let sessionMeter  = newMeter();
        let scenarioMeter = null;

        function meterHit(meter, op, urlKey) {
            if (!meter) return;
            meter.raw[op] = (meter.raw[op] || 0) + 1;
            if (meter.seen.has(urlKey)) return;
            meter.seen.add(urlKey);
            meter.counts[op] = (meter.counts[op] || 0) + 1;
        }

        // Render di-throttle ke satu frame: satu skenario bisa memicu ratusan
        // tile sekaligus, dan menggambar ulang panel tiap tile bikin peta tersendat.
        let renderQueued = false;
        function queueRender() {
            if (renderQueued) return;
            renderQueued = true;
            requestAnimationFrame(() => { renderQueued = false; renderAll(); });
        }

        let sparkBucket = 0;
        function record(op, urlKey) {
            const before = sessionMeter.counts[op] || 0;
            meterHit(sessionMeter, op, urlKey);
            meterHit(scenarioMeter, op, urlKey);
            if ((sessionMeter.counts[op] || 0) !== before) sparkBucket++;
            queueRender();
            pulseLive();
        }

        function classify(resourceType, url) {
            switch (resourceType) {
                case 'Tile':        return 'GetMapTile';
                case 'Style':
                case 'Source':      return 'GetMapStyleDescriptor';
                case 'Glyphs':      return 'GetMapGlyphs';
                case 'SpriteJSON':
                case 'SpriteImage': return 'GetMapSprites';
                default:            return url.indexOf('/tiles/') !== -1 ? 'GetMapTile' : null;
            }
        }

        const costOf = (counts, ops) => {
            let usd = 0;
            for (const op in counts) {
                if (ops && ops.indexOf(op) === -1) continue;
                usd += (counts[op] / 1000) * (PRICING[op] || 0);
            }
            return usd;
        };

        const totalOf = (counts, ops) => {
            let n = 0;
            for (const op in counts) {
                if (ops && ops.indexOf(op) === -1) continue;
                n += counts[op];
            }
            return n;
        };

        /* ================= FORMAT + ANGKA BERGERAK ================= */

        const fmtUsd = (v, d = 4) => '$' + v.toFixed(d);

        // Nilai kecil butuh desimal lebih banyak. 9 tile = $0.00036; kalau
        // dipaksa 4 desimal jadi $0.0004 — meleset 11% dan bikin orang
        // mengira angkanya tidak nyambung dengan tarifnya.
        function fmtUsdSmart(v) {
            if (!v) return '$0';
            if (v >= 0.01) return '$' + v.toFixed(4);
            return '$' + v.toFixed(7).replace(/0+$/, '').replace(/\.$/, '');
        }

        // Angka yang berada dalam satu kolom HARUS pakai jumlah desimal yang
        // sama. Kalau presisinya beda per baris, nilai yang digitnya lebih
        // panjang terbaca lebih besar padahal lebih kecil — persis kesalahan
        // yang membuat PPN $0.0000396 tampak melebihi subtotal $0.00036.
        // Presisi diambil dari nilai terkecil, cukup untuk 3 angka berarti.
        function sharedDecimals(values) {
            const positives = values.filter(v => v > 0);
            if (!positives.length) return 2;
            const min = Math.min.apply(null, positives);
            if (min >= 0.01) return 4;
            for (let d = 4; d <= 8; d++) {
                if (min * Math.pow(10, d) >= 100) return d;
            }
            return 8;
        }

        const fmtUsdFixed = (d) => (v) => '$' + v.toFixed(d);

        // Basis proyeksi, dipakai bersama oleh kotak total dan tabel skenario.
        // Nilainya dipilih dari daftar tetap, bukan slider bebas, supaya angka
        // yang muncul selalu bulat dan enak dibacakan saat presentasi.
        const SESSION_STEPS = [1000, 5000, 10000, 50000, 100000, 500000, 1000000, 5000000, 10000000];
        let sessionCount = 100000;

        function fmtSesi(n) {
            if (n >= 1000000) return (n / 1000000) + 'jt';
            if (n >= 1000)    return (n / 1000) + 'rb';
            return String(n);
        }
        const fmtIdr = (v) => 'Rp ' + Math.round(v).toLocaleString('id-ID');
        const fmtInt = (v) => Math.round(v).toLocaleString('id-ID');

        // Angka dianimasikan supaya perubahannya kelihatan, bukan loncat begitu
        // saja. Dimatikan kalau user minta reduce-motion.
        const tweens = new WeakMap();
        function setNumber(el, to, format) {
            if (!el) return;
            const prev = tweens.get(el);
            if (prev && prev.target === to) return;
            if (prev && prev.raf) cancelAnimationFrame(prev.raf);

            const from = prev ? prev.value : 0;
            if (REDUCED || Math.abs(to - from) < 1e-9) {
                tweens.set(el, { value: to, target: to, raf: 0 });
                el.textContent = format(to);
                return;
            }

            const start = performance.now();
            const step = (now) => {
                const t = Math.min(1, (now - start) / 420);
                const val = from + (to - from) * (1 - Math.pow(1 - t, 3));
                el.textContent = format(val);
                tweens.set(el, { value: t < 1 ? val : to, target: to, raf: t < 1 ? requestAnimationFrame(step) : 0 });
            };
            tweens.set(el, { value: from, target: to, raf: requestAnimationFrame(step) });
        }

        // Kilau singkat di kartu stat saat nilainya naik.
        const lastStat = {};
        function flashStat(id, value) {
            if (REDUCED) return;
            if (lastStat[id] === undefined) { lastStat[id] = value; return; }
            if (value <= lastStat[id]) { lastStat[id] = value; return; }
            lastStat[id] = value;
            const el = $(id);
            el.classList.remove('flash');
            void el.offsetWidth;
            el.classList.add('flash');
        }

        /* ================= SPARKLINE ================= */

        const sparkData = new Array(SPARK_BARS).fill(0);

        function initSpark() {
            $('spark').innerHTML = Array.from({ length: SPARK_BARS }, () => '<i></i>').join('');
        }

        function tickSpark() {
            sparkData.push(sparkBucket);
            sparkBucket = 0;
            if (sparkData.length > SPARK_BARS) sparkData.shift();

            const max = Math.max.apply(null, sparkData);
            const bars = $('spark').children;
            for (let i = 0; i < bars.length; i++) {
                const v = sparkData[i] || 0;
                bars[i].style.height = (max > 0 ? Math.max(4, (v / max) * 100) : 4) + '%';
                bars[i].classList.toggle('hot', v > 0);
            }
        }

        /* ================= RENDER ================= */

        function renderAll() {
            const counts = sessionMeter.counts;

            const nAll    = totalOf(counts);
            const nTile   = counts['GetMapTile'] || 0;
            const nPlaces = totalOf(counts, PLACES_OPS);
            const cMaps   = costOf(counts, MAPS_OPS);
            const cPlaces = costOf(counts, PLACES_OPS);
            const sub     = costOf(counts);
            const tax     = sub * TAX_RATE;
            const grand   = sub + tax;

            /* --- stat strip --- */
            setNumber($('sReq'),    nAll,    fmtInt);
            setNumber($('sTile'),   nTile,   fmtInt);
            setNumber($('sPlaces'), nPlaces, fmtInt);
            setNumber($('sCost'),   grand,   v => fmtUsd(v));
            $('sReqRaw').textContent     = fmtInt(totalOf(sessionMeter.raw)) + ' diminta peta';
            // Dua kartu ini berdampingan dan sering dibandingkan langsung —
            // presisinya ikut diseragamkan.
            const statFmt = fmtUsdFixed(sharedDecimals([cMaps, cPlaces]));
            $('sTileCost').textContent   = statFmt(cMaps) + ' · $0.04 per 1.000';
            $('sPlacesCost').textContent = statFmt(cPlaces) + ' · $0.50 per 1.000';
            $('sCostIdr').textContent    = '≈ ' + fmtIdr(grand * USD_RATE) + ' · sudah termasuk PPN';
            flashStat('statReq', nAll);
            flashStat('statTile', nTile);
            flashStat('statPlaces', nPlaces);

            /* --- HUD peta --- */
            // Biaya tile dipisah dari total, karena HUD berada tepat di sebelah
            // jumlah tile dan satu angka "biaya" di situ pasti dibaca sebagai
            // biaya tile — padahal total juga memuat Places dan PPN.
            const hudFmt = fmtUsdFixed(sharedDecimals([cMaps, grand]));
            setNumber($('hudTile'),     nTile, fmtInt);
            setNumber($('hudTileCost'), cMaps, hudFmt);
            setNumber($('hudTotal'),    grand, hudFmt);

            /* --- pembagi biaya Maps vs Places --- */
            const splitWrap = $('splitWrap');
            if (sub > 0) {
                splitWrap.style.display = 'block';
                const pMaps = (cMaps / sub) * 100;
                $('splitMaps').style.width   = pMaps + '%';
                $('splitPlaces').style.width = (100 - pMaps) + '%';
                $('splitMapsPct').textContent   = Math.round(pMaps) + '%';
                $('splitPlacesPct').textContent = Math.round(100 - pMaps) + '%';

                let note = '';
                if (nTile > 0 && nPlaces > 0) {
                    // Presisi seragam: kalau tidak, $0.00048 dan $0.00050 sama-sama
                    // tampil "$0.0005" sementara bar-nya bilang 49% vs 51%.
                    const nf = fmtUsdFixed(sharedDecimals([cMaps, cPlaces]));
                    note = `${fmtInt(nTile)} tile menghabiskan ${nf(cMaps)}, sementara ` +
                           `${fmtInt(nPlaces)} panggilan Places menghabiskan ${nf(cPlaces)}.`;
                } else if (nTile > 0) {
                    note = 'Belum ada panggilan Places — coba kotak "Uji Places" untuk melihat perbandingannya.';
                }
                $('splitNote').textContent = note;
            } else {
                splitWrap.style.display = 'none';
            }

            /* --- rincian per operasi --- */
            const box = $('opBreakdown');
            const rows = OP_META.filter(m => counts[m.op]);

            if (!rows.length) {
                box.innerHTML =
                    '<div class="meter-empty"><i class="bi bi-activity" aria-hidden="true"></i>' +
                    'Belum ada request.<br>Muat peta atau jalankan skenario.</div>';
            } else {
                const max = Math.max.apply(null, rows.map(m => counts[m.op]));

                // Kolom biaya di rincian ini juga satu kolom — presisinya
                // diseragamkan dengan alasan yang sama seperti kotak total.
                const opFmt = fmtUsdFixed(sharedDecimals(
                    rows.map(m => (counts[m.op] / 1000) * (PRICING[m.op] || 0))
                ));

                box.innerHTML = rows.map(m => {
                    const n    = counts[m.op];
                    const raw  = sessionMeter.raw[m.op] || n;
                    const cached = raw - n;
                    const rate = PRICING[m.op] || 0;
                    const cost = (n / 1000) * rate;
                    const free = !(rate > 0);
                    const pct  = max ? Math.max(2, (n / max) * 100) : 0;
                    const unit = m.op === 'GetMapTile' ? 'tile' : 'panggilan';

                    return `
                        <div class="op-row">
                            <div class="op-top">
                                <div class="op-name">
                                    <span class="dot bg-${m.group}" aria-hidden="true"></span>
                                    <span>${m.label}</span>
                                </div>
                                <div class="op-count num">${fmtInt(n)}<small>${free ? 'unik' : 'ditagih'}</small></div>
                                <div class="op-cost num ${free ? '' : 'billed'}">${free ? 'gratis' : opFmt(cost)}</div>
                            </div>
                            <div class="op-meta">${m.note} · $${rate.toFixed(2)} per 1.000</div>
                            <div class="op-sub">
                                ${fmtInt(raw)} ${unit} diminta peta
                                ${cached > 0
                                    ? `<span class="sep">·</span> ${fmtInt(cached)} dilayani cache browser`
                                    : '<span class="sep">·</span> tidak ada pengulangan'}
                            </div>
                            <div class="op-bar"><i class="bg-${m.group}" data-w="${pct}"></i></div>
                        </div>`;
                }).join('');

                // Lebar bar di-set setelah node masuk DOM supaya transisinya jalan.
                requestAnimationFrame(() => {
                    box.querySelectorAll('.op-bar i').forEach(el => { el.style.width = el.dataset.w + '%'; });
                });
            }

            /* --- kotak total --- */
            // Subtotal, PPN, dan Total berbagi satu presisi supaya bisa
            // dibandingkan lurus dari atas ke bawah.
            const boxFmt = fmtUsdFixed(sharedDecimals([sub, tax, grand]));

            setNumber($('totalReq'),    nAll,  fmtInt);
            setNumber($('subtotalUsd'), sub,   boxFmt);
            setNumber($('taxUsd'),      tax,   boxFmt);
            setNumber($('grandUsd'),    grand, boxFmt);
            setNumber($('grandIdr'),    grand * USD_RATE, v => '≈ ' + fmtIdr(v));
            $('totalRaw').textContent = fmtInt(totalOf(sessionMeter.raw));
            $('sesiLabel').textContent = fmtInt(sessionCount);
            $('projK').textContent    = fmtUsd(grand * sessionCount, 2) +
                                        '  ·  ' + fmtIdr(grand * sessionCount * USD_RATE);
        }

        let pulseTimer = null;
        function pulseLive() {
            const dot = $('liveDot');
            dot.classList.add('active');
            clearTimeout(pulseTimer);
            pulseTimer = setTimeout(() => dot.classList.remove('active'), 800);
        }

        /* ================= PETA ================= */

        const styleUrl = () =>
            `https://maps.geo.${region}.amazonaws.com/v2/styles/Standard/descriptor?color-scheme=Light`;

        function showOverlay(which) {
            [$('mapIdle'), $('mapLoading')].forEach(el => { el.hidden = true; el.classList.remove('fading'); });
            if (which) $(which).hidden = false;
        }

        function hideOverlays() {
            [$('mapIdle'), $('mapLoading')].forEach(el => {
                if (el.hidden) return;
                el.classList.add('fading');
                setTimeout(() => { el.hidden = true; el.classList.remove('fading'); }, REDUCED ? 0 : 360);
            });
            $('mapHud').classList.add('on');
        }

        function showError(msg) {
            const box = $('setupError');
            box.innerHTML = '<i class="bi bi-x-octagon-fill" aria-hidden="true"></i> ' + escapeHtml(msg);
            box.style.display = 'block';
        }

        const clearError = () => { $('setupError').style.display = 'none'; };
        const currentMaxZoom = () => parseFloat($('maxZoomSelect').value) || 22;

        // Isi mode hemat dibaca dari centangan, bukan dari konstanta —
        // supaya tiap batasan bisa dinyalakan/dimatikan sendiri dan efeknya
        // terhadap biaya bisa diuji satu per satu.
        const SAVER_MAX_ZOOM = 16;
        const JABODETABEK = [[106.30, -6.60], [107.20, -5.90]];

        const saverConfig = () => ({
            maxZoom: $('optMaxZoom').checked,
            jump:    $('optJump').checked,
            noPitch: $('optPitch').checked,
            noWorld: $('optWorld').checked,
            bounds:  $('optBounds').checked
        });

        function buildMap(opts) {
            opts = opts || {};
            const container = $('simMap');
            const sv = opts.saver ? saverConfig() : null;

            if (map) { map.remove(); map = null; }
            container.innerHTML = '';
            showOverlay('mapLoading');

            let maxZoom = opts.maxZoom != null ? opts.maxZoom : currentMaxZoom();
            if (sv && sv.maxZoom) maxZoom = Math.min(maxZoom, SAVER_MAX_ZOOM);

            const cfg = {
                container: container,
                style: styleUrl(),
                center: opts.center || JAKARTA,
                zoom: opts.zoom != null ? opts.zoom : 12,
                maxZoom: maxZoom,
                attributionControl: false,
                // Satu-satunya jalan masuk semua request peta — dipakai sekaligus
                // untuk menyisipkan API key dan menghitung request.
                transformRequest: (url, resourceType) => {
                    const op = classify(resourceType, url);
                    if (op) record(op, url);

                    if (url.indexOf('amazonaws.com') !== -1 && url.indexOf('key=') === -1) {
                        url += (url.indexOf('?') === -1 ? '?' : '&') + 'key=' + encodeURIComponent(apiKey);
                    }
                    return { url };
                }
            };

            if (sv && sv.noWorld) cfg.renderWorldCopies = false;
            if (sv && sv.noPitch) { cfg.maxPitch = 0; cfg.dragRotate = false; cfg.pitchWithRotate = false; }
            if (sv && sv.bounds) cfg.maxBounds = JABODETABEK;

            map = new maplibregl.Map(cfg);

            map.addControl(new maplibregl.NavigationControl(), 'top-right');
            map.addControl(new maplibregl.AttributionControl({ customAttribution: '© Grab, © AWS' }), 'bottom-right');

            const showZoom = () => { $('hudZoom').textContent = map.getZoom().toFixed(1); };
            map.on('zoom', showZoom);
            showZoom();

            map.on('load', () => { hideOverlays(); clearError(); });

            map.on('error', (e) => {
                const msg = (e && e.error && e.error.message) || 'Gagal memuat peta';
                showError(msg + ' — cek API key & region.');
                showOverlay('mapIdle');
                $('mapHud').classList.remove('on');
            });

            map.on('click', async (e) => {
                if (!$('clickReverse').checked) return;
                const r = await callPlaces('ReverseGeocode', 'reverse-geocode', {
                    QueryPosition: [e.lngLat.lng, e.lngLat.lat],
                    MaxResults: 1
                });
                const top = r && r.ResultItems && r.ResultItems[0];
                showPlaceResult(top ? [top] : []);
            });

            // Skenario memakai maxZoom-nya sendiri — samakan dropdown supaya
            // tidak menampilkan angka yang berbeda dari yang sedang dipakai peta.
            const mz = String(opts.maxZoom != null ? opts.maxZoom : currentMaxZoom());
            const sel = $('maxZoomSelect');
            if (Array.prototype.some.call(sel.options, o => o.value === mz)) sel.value = mz;

            $('cardKey').classList.add('is-done');
            map.resize();
            return map;
        }

        function waitIdle(timeout) {
            timeout = timeout || 10000;
            return new Promise(resolve => {
                let done = false;
                const finish = () => { if (!done) { done = true; clearTimeout(t); resolve(); } };
                const t = setTimeout(finish, timeout);
                map.once('idle', finish);
            });
        }

        /* ================= PLACES ================= */

        async function callPlaces(op, path, body) {
            const url = `https://places.geo.${region}.amazonaws.com/v2/${path}?key=${encodeURIComponent(apiKey)}`;
            // Setiap panggilan Places selalu ditagih — kuncinya dibuat unik, bukan URL.
            record(op, op + ':' + Date.now() + ':' + Math.random());
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                return await res.json();
            } catch (err) {
                showPlaceError('Gagal memanggil Places — cek API key, region, atau koneksi.');
                return null;
            }
        }

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function showPlaceError(msg) {
            $('placeResult').innerHTML =
                `<span style="color:var(--danger);"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> ${escapeHtml(msg)}</span>`;
        }

        function showPlaceResult(items) {
            const box = $('placeResult');
            if (!items || !items.length) {
                box.innerHTML = '<span style="color:var(--ink-3);">Tidak ada hasil.</span>';
                return;
            }
            box.innerHTML = items.slice(0, 5).map(it => {
                const title = it.Title || (it.Address && it.Address.Label) || '(tanpa nama)';
                const addr  = (it.Address && it.Address.Label) || '';
                return `<div class="place-item">
                    <div style="font-weight:600;color:#334155;">${escapeHtml(title)}</div>
                    ${addr && addr !== title ? `<div style="font-size:0.72rem;color:var(--ink-3);margin-top:1px;">${escapeHtml(addr)}</div>` : ''}
                </div>`;
            }).join('');
        }

        /* ================= SKENARIO ================= */

        // `target` dipakai untuk mendeteksi apakah mode hemat mengubah hasil
        // akhir (zoom tidak sampai tujuan / area terkunci). Penghematan yang
        // datang dari berkurangnya kemampuan harus ditandai, bukan diklaim gratis.
        const SCENARIOS = [
            {
                id: 'zoom-step-18',
                name: 'Zoom bertahap 12 → 18',
                icon: 'bi-sort-numeric-up',
                desc: 'Naik satu level per langkah, tunggu tile selesai tiap level. Pola paling umum kalau zoom dikendalikan tombol +/−.',
                start: { center: JAKARTA, zoom: 12, maxZoom: 22 },
                target: { zoom: 18 },
                run: async () => { for (let z = 13; z <= 18; z++) { map.setZoom(z); await waitIdle(); } }
            },
            {
                id: 'zoom-jump-18',
                name: 'Zoom langsung 12 → 18',
                icon: 'bi-box-arrow-in-down',
                desc: 'Lompat sekali ke zoom tujuan, tanpa singgah di level antara.',
                start: { center: JAKARTA, zoom: 12, maxZoom: 22 },
                target: { zoom: 18 },
                run: async () => { map.jumpTo({ zoom: 18 }); await waitIdle(); }
            },
            {
                id: 'pitch-rotate',
                name: 'Miringkan &amp; putar peta',
                icon: 'bi-badge-3d',
                desc: 'Pitch 60° lalu rotate 90° di zoom 15. Peta miring menarik tile jauh ke arah horizon.',
                start: { center: JAKARTA, zoom: 15, maxZoom: 22 },
                target: { pitch: 60 },
                run: async () => {
                    map.easeTo({ pitch: 60, duration: 600 }); await waitIdle();
                    map.easeTo({ bearing: 90, duration: 600 }); await waitIdle();
                }
            },
            {
                id: 'fly-far',
                name: 'flyTo Jakarta → Surabaya',
                icon: 'bi-airplane',
                desc: 'Animasi busur: zoom out, geser, zoom in. Menarik tile di zoom perantara sepanjang lintasan.',
                start: { center: JAKARTA, zoom: 13, maxZoom: 22 },
                target: { zoom: 13, center: SURABAYA },
                run: async (sv) => {
                    if (sv && sv.jump) map.jumpTo({ center: SURABAYA, zoom: 13 });
                    else map.flyTo({ center: SURABAYA, zoom: 13, duration: 3000 });
                    await waitIdle(15000);
                }
            },
            {
                id: 'jump-far',
                name: 'jumpTo Jakarta → Surabaya',
                icon: 'bi-lightning',
                desc: 'Pindah langsung tanpa frame animasi. Pembanding untuk flyTo di atas.',
                start: { center: JAKARTA, zoom: 13, maxZoom: 22 },
                target: { zoom: 13, center: SURABAYA },
                run: async () => { map.jumpTo({ center: SURABAYA, zoom: 13 }); await waitIdle(); }
            },
            {
                id: 'pan-5',
                name: 'Geser 5 layar ke timur',
                icon: 'bi-arrow-left-right',
                desc: 'panBy berulang di zoom 14. Menggambarkan user yang menjelajah peta tanpa batas area.',
                start: { center: JAKARTA, zoom: 14, maxZoom: 22 },
                target: {},
                run: async () => {
                    for (let i = 0; i < 5; i++) {
                        map.panBy([map.getContainer().clientWidth * 0.7, 0], { duration: 300 });
                        await waitIdle();
                    }
                }
            }
        ];

        // results[i] = { id, name, normal: {tiles,other,usd}|null, saver: {...}|null, note }
        const results = [];
        let uiMode = 'normal';

        // Apakah batasan hemat membuat skenario tidak mencapai tujuannya?
        function clampNote(s) {
            const notes = [];
            const t = s.target || {};
            if (t.zoom != null && map.getZoom() < t.zoom - 0.05) notes.push('zoom dibatasi');
            if (t.pitch != null && map.getPitch() < t.pitch - 1) notes.push('pitch dimatikan');
            if (t.center) {
                const c = map.getCenter();
                if (Math.abs(c.lng - t.center[0]) + Math.abs(c.lat - t.center[1]) > 0.5) notes.push('area dikunci');
            }
            return notes.length ? notes.join(' · ') : null;
        }

        function renderScenarioList() {
            $('scenarioList').innerHTML = SCENARIOS.map(s => `
                <div class="scn" id="scn-${s.id}">
                    <div class="scn-ico" aria-hidden="true"><i class="bi ${s.icon}"></i></div>
                    <div class="scn-title">${s.name}</div>
                    <div class="scn-desc">${s.desc}</div>
                    <div class="scn-foot">
                        <span class="scn-result num" id="scnres-${s.id}"></span>
                        <button class="sim-btn sim-btn-sm scenario-run" data-id="${s.id}" type="button"
                            aria-label="Jalankan skenario ${s.name}">Jalankan</button>
                    </div>
                    <div class="scn-progress" aria-hidden="true"></div>
                </div>
            `).join('');

            document.querySelectorAll('.scenario-run').forEach(btn => {
                btn.addEventListener('click', () => runScenario(btn.dataset.id));
            });
        }

        function setBusy(state, label) {
            busy = state;
            $('runAllBtn').disabled  = state;
            $('compareBtn').disabled = state;
            document.querySelectorAll('.scenario-run, .mode-btn').forEach(b => { b.disabled = state; });

            $('runAllBtn').innerHTML = state
                ? '<span class="btn-spin" aria-hidden="true" style="border-color:rgba(71,85,105,.3);border-top-color:#475569;"></span> …'
                : '<i class="bi bi-play-circle" aria-hidden="true"></i> Jalankan semua';

            $('compareBtn').innerHTML = state
                ? `<span class="btn-spin" aria-hidden="true"></span> ${escapeHtml(label || 'Menjalankan…')}`
                : '<i class="bi bi-bar-chart-line-fill" aria-hidden="true"></i> Bandingkan Normal vs Hemat';
        }

        const announce = (msg) => { $('scenarioStatus').textContent = msg; };

        // Menjalankan satu skenario dalam satu mode dan mengembalikan ukurannya.
        // Peta selalu dibangun ulang dari nol supaya cache tile MapLibre kosong —
        // tanpa ini jalannya yang kedua akan terlihat "gratis".
        async function measure(s, mode) {
            const sv = mode === 'saver' ? saverConfig() : null;

            buildMap(Object.assign({}, s.start, { saver: mode === 'saver' }));
            await waitIdle(15000);

            // Baru mulai hitung SETELAH peta awal selesai, supaya yang terukur
            // murni biaya gerakan kameranya.
            scenarioMeter = newMeter();
            await s.run(sv);
            await waitIdle(4000);

            const counts = Object.assign({}, scenarioMeter.counts);
            scenarioMeter = null;

            const tiles = counts['GetMapTile'] || 0;
            return {
                tiles,
                other: totalOf(counts) - tiles,
                usd:   costOf(counts) * (1 + TAX_RATE),
                note:  mode === 'saver' ? clampNote(s) : null
            };
        }

        function upsert(s, mode, data) {
            let rec = results.find(r => r.id === s.id);
            if (!rec) { rec = { id: s.id, name: s.name, normal: null, saver: null, note: null }; results.push(rec); }
            rec[mode] = data;
            if (mode === 'saver') rec.note = data.note;
            return rec;
        }

        function paintScenarioFoot(rec) {
            const parts = [];
            if (rec.normal) parts.push(`Normal <b>${fmtInt(rec.normal.tiles)}</b>`);
            if (rec.saver)  parts.push(`Hemat <b>${fmtInt(rec.saver.tiles)}</b>`);
            $('scnres-' + rec.id).innerHTML = parts.join(' · ');
        }

        async function runScenario(id, mode) {
            if (!requireKey()) return;
            const s = SCENARIOS.find(x => x.id === id);
            if (!s || busy) return;
            mode = mode || uiMode;

            const row = $('scn-' + s.id);
            row.classList.add('running');
            row.classList.remove('done');
            setBusy(true, s.name);
            announce('Menjalankan ' + s.name + ' mode ' + (mode === 'saver' ? 'hemat' : 'normal'));

            const data = await measure(s, mode);
            const rec  = upsert(s, mode, data);

            paintScenarioFoot(rec);
            row.classList.remove('running');
            row.classList.add('done');
            renderResults();
            setBusy(false);
            announce(s.name + ' selesai: ' + data.tiles + ' tile.');
        }

        async function runAll() {
            if (!requireKey()) return;
            for (const s of SCENARIOS) await runScenario(s.id, uiMode);
            announce('Semua skenario selesai.');
        }

        // Tiap skenario dijalankan dua kali berturut-turut — normal lalu hemat —
        // supaya perbandingannya apel ke apel pada kondisi yang sama.
        async function runCompare() {
            if (!requireKey()) return;
            if (busy) return;

            for (const s of SCENARIOS) {
                const row = $('scn-' + s.id);
                row.classList.add('running');
                row.classList.remove('done');

                for (const mode of ['normal', 'saver']) {
                    setBusy(true, s.name + ' · ' + (mode === 'saver' ? 'hemat' : 'normal'));
                    announce('Mengukur ' + s.name + ' mode ' + mode);
                    const rec = upsert(s, mode, await measure(s, mode));
                    paintScenarioFoot(rec);
                    renderResults();
                }

                row.classList.remove('running');
                row.classList.add('done');
            }

            setBusy(false);
            announce('Perbandingan normal vs hemat selesai.');
        }

        function renderResults() {
            const wrap = $('resultsWrap');
            if (!results.length) {
                wrap.style.display = 'none';
                wrap.classList.remove('showing');
                return;
            }
            if (wrap.style.display === 'none') {
                wrap.style.display = 'block';
                wrap.classList.add('showing');
            }

            const K = sessionCount;
            const dash = '<span class="delta-none">—</span>';
            $('thSesiA').textContent = fmtSesi(K);
            $('thSesiB').textContent = fmtSesi(K);

            // Mode hemat tidak selalu menang. Selisih negatif diberi warna
            // berbeda supaya tidak terbaca sebagai penghematan.
            const deltaClass = (v) => v == null || Math.abs(v) < 1e-9
                ? 'delta-none' : (v > 0 ? 'delta-good' : 'delta-bad');

            $('resultsBody').innerHTML = results.map(r => {
                const n = r.normal, s = r.saver;
                const both = n && s;
                const pct  = both && n.tiles > 0 ? (1 - s.tiles / n.tiles) * 100 : null;
                const save = both ? (n.usd - s.usd) * K : null;

                return `
                <tr>
                    <td>${escapeHtml(r.name)}${r.note ? `<span class="warn-mark">${escapeHtml(r.note)}</span>` : ''}</td>
                    <td class="r num" style="font-weight:700;">${n ? fmtInt(n.tiles) : dash}</td>
                    <td class="r num" style="font-weight:700;">${s ? fmtInt(s.tiles) : dash}</td>
                    <td class="r num ${deltaClass(pct)}">${pct == null ? dash : Math.round(pct) + '%'}</td>
                    <td class="r num">${n ? fmtUsd(n.usd * K, 2) : dash}</td>
                    <td class="r num">${s ? fmtUsd(s.usd * K, 2) : dash}</td>
                    <td class="r num ${deltaClass(save)}">${save == null ? dash : fmtUsd(save, 2) + `<br>
                        <span style="font-weight:500;font-size:0.7rem;">${fmtIdr(save * USD_RATE)}</span>`}</td>
                </tr>`;
            }).join('');

            // Baris total hanya dari skenario yang sudah punya kedua angkanya.
            const paired = results.filter(r => r.normal && r.saver);
            if (paired.length) {
                const tN = paired.reduce((a, r) => a + r.normal.tiles, 0);
                const tS = paired.reduce((a, r) => a + r.saver.tiles, 0);
                const cN = paired.reduce((a, r) => a + r.normal.usd, 0) * K;
                const cS = paired.reduce((a, r) => a + r.saver.usd, 0) * K;
                const pct = tN > 0 ? Math.round((1 - tS / tN) * 100) : 0;

                $('resultsBody').insertAdjacentHTML('beforeend', `
                    <tr class="sum-row">
                        <td>Total ${paired.length} skenario</td>
                        <td class="r num">${fmtInt(tN)}</td>
                        <td class="r num">${fmtInt(tS)}</td>
                        <td class="r num ${deltaClass(pct)}">${pct}%</td>
                        <td class="r num">${fmtUsd(cN, 2)}</td>
                        <td class="r num">${fmtUsd(cS, 2)}</td>
                        <td class="r num ${deltaClass(cN - cS)}">${fmtUsd(cN - cS, 2)}<br>
                            <span style="font-weight:500;font-size:0.7rem;">${fmtIdr((cN - cS) * USD_RATE)}</span></td>
                    </tr>`);
            }

            /* --- kesimpulan --- */
            let note = 'Jalankan <strong>Bandingkan Normal vs Hemat</strong> untuk mengisi kedua kolom. ' +
                       `Kolom "per ${fmtSesi(K)} sesi" adalah biaya kalau pola gerakan itu terjadi ` +
                       `sekali per sesi, untuk ${fmtInt(K)} sesi.`;

            if (paired.length) {
                const tN   = paired.reduce((a, r) => a + r.normal.tiles, 0);
                const tS   = paired.reduce((a, r) => a + r.saver.tiles, 0);
                const save = (paired.reduce((a, r) => a + r.normal.usd, 0) -
                              paired.reduce((a, r) => a + r.saver.usd, 0)) * K;
                const pct  = tN > 0 ? Math.round((1 - tS / tN) * 100) : 0;
                const flagged = paired.filter(r => r.note);

                note = `Mode hemat memangkas <strong>${pct}%</strong> tile pada ${paired.length} skenario
                    (${fmtInt(tN)} → ${fmtInt(tS)}). Untuk ${fmtInt(K)} sesi, selisihnya
                    <strong>${fmtUsd(save, 2)}</strong> (${fmtIdr(save * USD_RATE)}).`;

                if (flagged.length) {
                    note += `<br><br><strong>Perlu dicatat:</strong> ${flagged.length} skenario
                        (${flagged.map(f => escapeHtml(f.name)).join(', ')}) hematnya sebagian datang dari
                        berkurangnya kemampuan — zoom tidak sampai tujuan atau area terkunci. Itu keputusan produk,
                        bukan penghematan gratis. Sisanya murni efisiensi.`;
                }

                // Kalau ada skenario yang justru lebih mahal, itu perlu dijelaskan —
                // bukan disembunyikan di balik angka total yang tetap positif.
                const worse = paired.filter(r => r.saver.tiles > r.normal.tiles);
                if (worse.length) {
                    note += `<br><br><strong>Mode hemat kalah di ${worse.length} skenario</strong>
                        (${worse.map(f => escapeHtml(f.name)).join(', ')}). Penyebab paling umum: membatasi zoom
                        maksimum. Di atas batas zoom sumber tile (±16) AWS tidak menyediakan tile baru — MapLibre
                        membesarkan satu tile yang sama, sehingga zoom lebih dalam justru butuh lebih sedikit tile
                        untuk mengisi layar. Batas zoom menghemat saat user <em>menjelajah</em>, bukan saat diam.`;
                }
            }
            $('resultNote').innerHTML = note;
        }

        /* ================= WIRING ================= */

        function readKey() {
            apiKey = $('apiKeyInput').value.trim();
            region = $('regionInput').value.trim() || '{{ $region }}';
            return apiKey;
        }

        function requireKey() {
            if (readKey()) { clearError(); return true; }
            showError('API key masih kosong.');
            $('apiKeyInput').focus();
            return false;
        }

        $('setupForm').addEventListener('submit', (e) => {
            e.preventDefault();
            if (!requireKey()) return;

            if ($('rememberKey').checked) {
                localStorage.setItem(STORAGE_KEY, apiKey);
                $('keyStatus').textContent = 'Key tersimpan di browser ini.';
            }
            sessionMeter = newMeter();
            renderAll();
            buildMap({ center: JAKARTA, zoom: 12, maxZoom: currentMaxZoom(), saver: uiMode === 'saver' });
        });

        // Ganti mode: peta interaktif ikut dibangun ulang dengan batasan yang
        // dipilih, supaya bedanya bisa dirasakan langsung sambil menggeser.
        document.querySelectorAll('.mode-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                uiMode = btn.dataset.mode;
                document.querySelectorAll('.mode-btn').forEach(b => {
                    const on = b === btn;
                    b.classList.toggle('on', on);
                    b.setAttribute('aria-pressed', String(on));
                });
                if (map && apiKey) {
                    buildMap({ center: JAKARTA, zoom: 12, maxZoom: currentMaxZoom(), saver: uiMode === 'saver' });
                }
            });
        });

        $('compareBtn').addEventListener('click', runCompare);

        // Slider basis sesi — menggerakkan kotak proyeksi dan tabel skenario
        // sekaligus. 'input' supaya angkanya ikut saat digeser, bukan setelah lepas.
        $('sesiRange').addEventListener('input', (e) => {
            const idx = Math.min(SESSION_STEPS.length - 1, Math.max(0, +e.target.value));
            sessionCount = SESSION_STEPS[idx];
            renderAll();
            renderResults();
        });

        $('toggleKeyBtn').addEventListener('click', (e) => {
            const input = $('apiKeyInput');
            const icon  = e.currentTarget.querySelector('i');
            const show  = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            e.currentTarget.setAttribute('aria-pressed', String(show));
            e.currentTarget.setAttribute('aria-label', show ? 'Sembunyikan API key' : 'Tampilkan API key');
        });

        $('forgetKeyBtn').addEventListener('click', () => {
            localStorage.removeItem(STORAGE_KEY);
            $('apiKeyInput').value = '';
            $('rememberKey').checked = false;
            $('keyStatus').textContent = 'Key tersimpan sudah dihapus.';
        });

        $('resetCounterBtn').addEventListener('click', () => {
            sessionMeter = newMeter();
            renderAll();
        });

        $('maxZoomSelect').addEventListener('change', () => {
            if (map) map.setMaxZoom(currentMaxZoom());
        });

        $('runAllBtn').addEventListener('click', runAll);

        $('clearResultsBtn').addEventListener('click', () => {
            results.length = 0;
            document.querySelectorAll('.scn').forEach(el => el.classList.remove('done'));
            document.querySelectorAll('.scn-result').forEach(el => { el.innerHTML = ''; });
            renderResults();
            announce('Hasil dikosongkan.');
        });

        async function doPlaces(op, path) {
            if (!requireKey()) return;
            const q = $('placeQuery').value.trim();
            if (q.length < 2) { showPlaceError('Ketik minimal 2 karakter.'); return; }
            const r = await callPlaces(op, path, { QueryText: q, MaxResults: 5 });
            if (r) showPlaceResult(r.ResultItems || []);
        }

        $('btnSuggest').addEventListener('click', () => doPlaces('Suggest', 'suggest'));
        $('btnSearchText').addEventListener('click', () => doPlaces('SearchText', 'search-text'));

        $('placeQuery').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); doPlaces('SearchText', 'search-text'); }
        });

        // Pulihkan key tersimpan
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            $('apiKeyInput').value = saved;
            $('rememberKey').checked = true;
            $('keyStatus').textContent = 'Key diambil dari penyimpanan browser.';
        }

        // Tinggi area peta sekarang mengikuti kolom kanan, dan kolom itu
        // memanjang sendiri saat baris rincian bertambah. MapLibre tidak
        // memantau ukuran container-nya, jadi harus diberi tahu manual.
        if ('ResizeObserver' in window) {
            let resizeRaf = 0;
            new ResizeObserver(() => {
                if (!map) return;
                cancelAnimationFrame(resizeRaf);
                resizeRaf = requestAnimationFrame(() => map.resize());
            }).observe(document.querySelector('.map-frame'));
        }

        showOverlay('mapIdle');
        initSpark();
        renderScenarioList();
        renderAll();
        setInterval(tickSpark, 1000);
    })();
</script>
@endpush
