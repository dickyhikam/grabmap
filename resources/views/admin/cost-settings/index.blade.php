@extends('layouts.admin-v2')

@section('title', __('costs.title'))

@push('styles')
    /* ---------- Ringkasan ---------- */
    .cs-top {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr) minmax(0, 1fr);
        gap: 16px; margin-bottom: 16px;
    }
    @media (max-width: 980px) { .cs-top { grid-template-columns: 1fr; } }

    /* Kartu kurs aktif — segaya kartu biaya di dashboard. */
    .rate-card {
        background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 45%, #04703a 100%);
        border-radius: 24px; padding: 22px; color: #fff;
        position: relative; overflow: hidden;
    }
    .rate-card::after {
        content: ''; position: absolute; right: -60px; top: -70px;
        width: 210px; height: 210px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, transparent 65%);
    }
    .rate-card > * { position: relative; z-index: 1; }
    .rate-head { display: flex; align-items: center; gap: 10px; }
    .rate-head .ic {
        width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255, 255, 255, 0.16);
    }
    .rate-lbl { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: rgba(255, 255, 255, 0.78); }
    .rate-val {
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
        font-size: 2rem; letter-spacing: -0.03em; line-height: 1.1; margin-top: 14px;
    }
    .rate-val .unit { font-size: 0.9rem; font-weight: 600; opacity: 0.72; margin-left: 6px; letter-spacing: 0; }
    .rate-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 14px; }
    .rate-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255, 255, 255, 0.14); color: rgba(255, 255, 255, 0.94);
        font-size: 0.71rem; padding: 6px 11px; border-radius: 999px;
    }
    .rate-live {
        position: absolute; top: 20px; right: 20px; z-index: 2;
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255, 255, 255, 0.18); color: #fff;
        font-size: 0.62rem; font-weight: 700; letter-spacing: 0.06em;
        padding: 5px 11px; border-radius: 999px;
    }
    .rate-live .dot {
        width: 7px; height: 7px; border-radius: 50%; background: #7cffb2;
        box-shadow: 0 0 0 3px rgba(124, 255, 178, 0.25);
        animation: pulse 2.4s ease-in-out infinite;
    }
    @keyframes pulse { 50% { opacity: 0.45; } }

    /* Kartu angka yang bisa diubah di tempat. */
    .num-card { display: flex; flex-direction: column; }
    .num-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .num-ic {
        width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 0.95rem;
    }
    .num-lbl { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); }
    .num-val {
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800;
        font-size: 1.9rem; letter-spacing: -0.03em; line-height: 1;
    }
    .num-val .sm { font-size: 1rem; font-weight: 600; color: var(--muted); }
    .num-hint { font-size: 0.68rem; color: var(--muted); margin-top: 10px; line-height: 1.5; }

    .num-edit {
        margin-left: auto; border: none; background: var(--surface); color: var(--muted);
        width: 32px; height: 32px; border-radius: 11px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }
    .num-edit:hover { background: var(--green); color: #fff; }
    .num-edit:active { transform: scale(0.92); }

    /* Tampilan & formulir bertukar tempat, tingginya dijaga sama. */
    .num-face[hidden], .num-form[hidden] { display: none; }
    .num-face, .num-form { animation: faceIn 0.24s cubic-bezier(0.34, 1.4, 0.5, 1); }
    @keyframes faceIn {
        from { opacity: 0; transform: translateY(-5px); }
        to   { opacity: 1; transform: none; }
    }
    .num-form { display: flex; flex-direction: column; gap: 9px; }
    .num-input-row { display: flex; align-items: center; gap: 8px; }
    .num-affix { font-weight: 700; color: var(--muted); font-size: 0.95rem; }
    .num-btns { display: flex; gap: 8px; }
    .num-btns .btn-soft, .num-btns .btn-solid { flex: 1; padding: 9px 12px; font-size: 0.78rem; }

    /* ---------- Formulir & bukti ---------- */
    .cs-mid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 16px; margin-bottom: 16px; align-items: start; }
    @media (max-width: 980px) { .cs-mid { grid-template-columns: 1fr; } }

    .f-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
    @media (max-width: 620px) { .f-row { grid-template-columns: 1fr; } }

    .form-field { margin-bottom: 18px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 600; color: var(--faint); }
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    textarea.form-input { height: auto; padding: 12px 14px; resize: vertical; line-height: 1.6; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* Input dengan awalan Rp di dalamnya. */
    .with-prefix { position: relative; }
    .with-prefix .pfx {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        font-size: 0.82rem; font-weight: 700; color: var(--muted); pointer-events: none; z-index: 2;
    }
    .with-prefix .form-input { padding-left: 42px; font-family: ui-monospace, monospace; }

    .src-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 8px; }
    .src-chips .lb { font-size: 0.68rem; color: var(--muted); margin-right: 2px; }
    .src-chip {
        border: 1px solid var(--line); background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 5px 12px;
        font-size: 0.7rem; font-weight: 600; cursor: pointer; transition: all 0.15s;
    }
    .src-chip:hover { border-color: var(--green); color: var(--ink); }
    .src-chip.on { background: var(--green); border-color: var(--green); color: #fff; }

    .check-row {
        display: flex; gap: 11px; align-items: flex-start;
        background: var(--surface); border-radius: 14px;
        padding: 12px 14px; margin-bottom: 18px;
        font-size: 0.8rem; cursor: pointer;
    }
    .check-row input { accent-color: var(--green); width: 17px; height: 17px; margin-top: 1px; flex-shrink: 0; }
    .check-row .ds { display: block; font-size: 0.69rem; color: var(--muted); margin-top: 2px; }

    .kv { display: flex; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--line); font-size: 0.8rem; }
    .kv:last-child { border-bottom: none; }
    .kv .k { color: var(--muted); white-space: nowrap; }
    .kv .v { font-weight: 600; text-align: right; word-break: break-word; }
    .kv .v .sub { display: block; font-weight: 500; font-size: 0.7rem; color: var(--muted); }

    .info-item {
        display: flex; gap: 12px; padding: 11px 0;
        border-bottom: 1px solid var(--line);
        animation: infoIn 0.3s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .info-item:last-of-type { border-bottom: none; }
    @keyframes infoIn {
        from { opacity: 0; transform: translateX(8px); }
        to   { opacity: 1; transform: none; }
    }
    .info-dot {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
    }
    .info-title { font-weight: 600; font-size: 0.82rem; }
    .info-desc { font-size: 0.71rem; color: var(--muted); margin-top: 3px; line-height: 1.5; }
    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }

    .soft-note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 11px 13px; margin-top: 14px;
        font-size: 0.71rem; line-height: 1.5;
        background: var(--surface); color: var(--muted);
    }

    .soft-count {
        font-size: 0.7rem; font-weight: 700; color: var(--muted);
        background: var(--surface); border-radius: 999px; padding: 2px 8px; margin-left: 4px;
    }

    /* ---------- Riwayat ---------- */
    .h-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.83rem; }
    .h-table th {
        font-size: 0.68rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: 0.05em;
        text-align: left; padding: 0 16px 12px; white-space: nowrap;
    }
    .h-table td { padding: 12px 16px; border-top: 1px solid var(--line); vertical-align: middle; }
    .h-table tbody tr { animation: rowIn 0.32s cubic-bezier(0.34, 1.4, 0.5, 1) backwards; }
    .h-table tbody tr:hover td { background: var(--surface); }
    .h-table tbody tr td:first-child { border-radius: 14px 0 0 14px; }
    .h-table tbody tr td:last-child { border-radius: 0 14px 14px 0; }
    .h-table tbody tr.on td { background: var(--green-soft); }
    .h-table .num { font-family: ui-monospace, monospace; font-weight: 700; }
    .h-table .dim { color: var(--muted); font-size: 0.75rem; }
    @keyframes rowIn {
        from { opacity: 0; transform: translateY(9px); }
        to   { opacity: 1; transform: none; }
    }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.68rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok    { background: var(--green); color: #fff; }
    .pill-badge.plain { background: var(--surface); color: var(--muted); }

    .pager { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 14px; margin-top: 4px; border-top: 1px solid var(--line); }
    .pager-info { font-size: 0.75rem; color: var(--muted); margin-right: auto; }
    .pager-btn {
        min-width: 34px; height: 34px; padding: 0 10px;
        border-radius: 12px; border: none; background: var(--surface);
        color: var(--ink); font-size: 0.78rem; font-weight: 600;
        display: inline-flex; align-items: center; justify-content: center;
        text-decoration: none; transition: background 0.15s, color 0.15s;
    }
    .pager-btn:hover { background: var(--green-soft); color: var(--green-text); }
    .pager-btn.on { background: var(--green); color: #fff; }
    .pager-btn.off { opacity: 0.4; pointer-events: none; }
    .pager-gap { color: var(--muted); font-size: 0.78rem; padding: 0 2px; }

    .table-wrap { overflow-x: auto; margin: 0 -6px; padding: 0 6px; }

    /* ---------- Modal ganti kurs aktif ---------- */
    .gm-modal {
        position: fixed; inset: 0; z-index: 1250;
        display: flex; align-items: flex-start; justify-content: center;
        padding: 10vh 16px 16px;
        background: rgba(12, 18, 15, 0.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }
    .gm-modal.open { opacity: 1; visibility: visible; }
    .gm-modal-card {
        width: 100%; max-width: 400px;
        background: var(--card); border-radius: 24px;
        box-shadow: var(--shadow-pop); overflow: hidden;
        transform: translateY(-14px) scale(0.97); opacity: 0;
        transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.5, 1), opacity 0.2s ease;
    }
    .gm-modal.open .gm-modal-card { transform: none; opacity: 1; }
    .gm-modal-head { text-align: center; padding: 26px 24px 18px; }
    .gm-modal-icon {
        width: 54px; height: 54px; border-radius: 18px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.35rem; margin-bottom: 12px;
        background: var(--green-soft); color: var(--green-text);
    }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-sub { font-size: 0.77rem; color: var(--muted); margin-top: 5px; }
    .gm-modal-body { padding: 0 24px 24px; }

    .swap { margin-bottom: 14px; }
    .swap-row {
        display: flex; align-items: center; gap: 11px;
        background: var(--surface); border-radius: 16px;
        padding: 12px 14px; border: 1.5px solid transparent;
    }
    .swap-row.out { opacity: 0.62; }
    .swap-row.out .swap-ic { background: var(--card); color: var(--faint); }
    .swap-row.out .nm { text-decoration: line-through; }
    .swap-row.in { border-color: var(--green); background: var(--green-soft); }
    .swap-row.in .swap-ic { background: var(--green); color: #fff; }
    .swap-ic {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
    }
    .swap-tx { flex: 1; min-width: 0; }
    .swap-tx .nm { display: block; font-weight: 700; font-size: 0.9rem; font-family: ui-monospace, monospace; }
    .swap-tx .rg { display: block; font-size: 0.68rem; color: var(--muted); }
    .swap-tag { font-size: 0.63rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
    .swap-row.in .swap-tag { color: var(--green-text); }
    .swap-arrow { text-align: center; color: var(--faint); font-size: 0.85rem; padding: 5px 0; }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 11px 13px; margin-bottom: 16px;
        font-size: 0.72rem; line-height: 1.5;
        background: var(--green-soft); color: var(--green-text);
    }
    .gm-modal .btn-row > .btn-soft, .gm-modal .btn-row > .btn-solid { flex: 1; padding-left: 12px; padding-right: 12px; }
@endpush

@section('content')
@php
    // Angka tanpa nol di belakang koma: 11,00 → 11; 0,50 → 0,5.
    $trim = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
@endphp

<div class="q-page-head">
    <div>
        <h1 class="q-title">{{ __('costs.title') }}</h1>
        <div class="q-card-sub" style="margin-top:4px;">{{ __('costs.subtitle') }}</div>
    </div>
</div>

{{-- ===================== Ringkasan ===================== --}}
<div class="cs-top">
    <div class="rate-card">
        @if($activeRate)
            <span class="rate-live"><span class="dot"></span> {{ __('costs.badge_active') }}</span>
        @endif

        <div class="rate-head">
            <span class="ic"><i class="bi bi-cash-stack"></i></span>
            <span class="rate-lbl">{{ __('costs.rate_active') }}</span>
        </div>

        @if($activeRate)
            <div class="rate-val">
                Rp {{ number_format((float) $activeRate->rate, 0, ',', '.') }}<span class="unit">{{ __('costs.per_usd') }}</span>
            </div>
            <div class="rate-chips">
                <span class="rate-chip"><i class="bi bi-calendar3"></i> {{ $activeRate->rate_date->translatedFormat('d M Y') }}</span>
                <span class="rate-chip"><i class="bi bi-bank"></i> {{ $activeRate->source }}</span>
                @if($activeRate->reference)
                    <span class="rate-chip"><i class="bi bi-paperclip"></i> {{ \Illuminate\Support\Str::limit($activeRate->reference, 24) }}</span>
                @endif
            </div>
        @else
            <div class="rate-val" style="font-size:1.3rem;">{{ __('costs.rate_none') }}</div>
            <div class="rate-chips"><span class="rate-chip">{{ __('costs.rate_none_d') }}</span></div>
        @endif
    </div>

    {{-- PPN --}}
    <div class="q-card num-card">
        <div class="num-head">
            <span class="num-ic" style="background:var(--tone-indigo-bg);color:var(--tone-indigo-fg);"><i class="bi bi-percent"></i></span>
            <span class="num-lbl">{{ __('costs.tax') }}</span>
            @can('cost_settings.update')
                <button type="button" class="num-edit" data-edit="tax" title="{{ __('costs.edit') }}">
                    <i class="bi bi-pencil"></i>
                </button>
            @endcan
        </div>

        <div class="num-face" id="taxFace">
            <div class="num-val">{{ $trim($taxPercent) }}<span class="sm">%</span></div>
            <div class="num-hint">{{ __('costs.tax_hint') }}</div>
        </div>

        @can('cost_settings.update')
            <form method="POST" action="{{ route('admin.cost-settings.tax') }}" class="num-form" id="taxForm"
                  @unless($errors->has('tax_percent')) hidden @endunless>
                @csrf
                @include('admin.partials.amount-slider', [
                    'name'    => 'tax_percent',
                    'value'   => $taxPercent,
                    'max'     => 25,
                    'step'    => 0.5,
                    'unit'    => '%',
                    'limit'   => 100,
                    'presets' => [0, 10, 11, 12],
                ])
                @error('tax_percent')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                <div class="num-btns">
                    <button type="button" class="btn-soft" data-cancel="tax">{{ __('costs.cancel') }}</button>
                    <button type="submit" class="btn-solid"><i class="bi bi-check-lg"></i> {{ __('costs.save') }}</button>
                </div>
            </form>
        @endcan
    </div>

    {{-- Ambang budget --}}
    <div class="q-card num-card">
        <div class="num-head">
            <span class="num-ic" style="background:var(--warn-soft);color:var(--warn-fg);"><i class="bi bi-bell-fill"></i></span>
            <span class="num-lbl">{{ __('costs.budget') }}</span>
            @can('cost_settings.update')
                <button type="button" class="num-edit" data-edit="budget" title="{{ __('costs.edit') }}">
                    <i class="bi bi-pencil"></i>
                </button>
            @endcan
        </div>

        <div class="num-face" id="budgetFace">
            <div class="num-val"><span class="sm">$</span>{{ $trim($budgetAlert) }}</div>
            <div class="num-hint">{{ __('costs.budget_hint') }} {{ __('costs.keyb_global') }}</div>
        </div>

        @can('cost_settings.update')
            <form method="POST" action="{{ route('admin.cost-settings.budget') }}" class="num-form" id="budgetForm"
                  @unless($errors->has('budget_alert_usd')) hidden @endunless>
                @csrf
                @include('admin.partials.amount-slider', [
                    'name'    => 'budget_alert_usd',
                    'value'   => $budgetAlert,
                    'max'     => 500,
                    'step'    => 10,
                    'prefix'  => '$',
                    'suffix'  => __('ui.per_month'),
                    'zero'    => __('ui.no_limit'),
                    'presets' => [100, 170, 250],
                ])
                @error('budget_alert_usd')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                <div class="num-btns">
                    <button type="button" class="btn-soft" data-cancel="budget">{{ __('costs.cancel') }}</button>
                    <button type="submit" class="btn-solid"><i class="bi bi-check-lg"></i> {{ __('costs.save') }}</button>
                </div>
            </form>
        @endcan
    </div>
</div>

{{-- ===================== Formulir kurs + bukti ===================== --}}
<div class="cs-mid">
    <div class="q-card" style="padding:24px;">
        <div class="q-card-head">
            <div class="d-flex align-items-center gap-2">
                <div class="q-icon-box"><i class="bi bi-plus-lg"></i></div>
                <div>
                    <div class="q-card-title">{{ __('costs.add_title') }}</div>
                    <div class="q-card-sub">{{ __('costs.add_sub') }}</div>
                </div>
            </div>
        </div>

        @can('cost_settings.update')
            <form method="POST" action="{{ route('admin.cost-settings.rate.store') }}" data-validate>
                @csrf

                <div class="f-row">
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('costs.rate_lbl') }}<span class="req">*</span></label>
                        <div class="with-prefix">
                            <span class="pfx">Rp</span>
                            {{-- Teks, bukan number: input angka bawaan browser tidak bisa
                                 menampilkan pemisah ribuan. Nilainya dinormalkan lagi di
                                 controller, jadi tetap benar walau JS mati. --}}
                            <input type="text" name="rate" id="rateInput" required
                                   data-v-number="id" data-v-min="1" data-v-max="1000000"
                                   inputmode="numeric" autocomplete="off"
                                   class="form-input @error('rate') is-invalid @enderror"
                                   placeholder="{{ __('costs.rate_ph') }}"
                                   value="{{ old('rate') }}">
                        </div>
                        @error('rate')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label-sm">{{ __('costs.date_lbl') }}<span class="req">*</span></label>
                        @include('admin.partials.date-picker', [
                            'name'  => 'rate_date',
                            'value' => old('rate_date', now()->toDateString()),
                            'time'  => false,
                        ])
                        @error('rate_date')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('costs.source_lbl') }}<span class="req">*</span></label>
                    <input type="text" name="source" id="srcInput" maxlength="100" required
                           class="form-input @error('source') is-invalid @enderror"
                           value="{{ old('source', 'Kurs Pajak (KMK)') }}">
                    <div class="src-chips" id="srcChips">
                        <span class="lb">{{ __('costs.source_quick') }}</span>
                        @foreach(['Kurs Pajak (KMK)', 'Bank Indonesia (JISDOR)', 'Kurs Tengah BI', 'Manual'] as $src)
                            <button type="button" class="src-chip" data-src="{{ $src }}">{{ $src }}</button>
                        @endforeach
                    </div>
                    @error('source')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('costs.ref_lbl') }} <span class="opt">{{ __('costs.optional') }}</span>
                    </label>
                    <input type="text" name="reference" maxlength="255" value="{{ old('reference') }}"
                           class="form-input @error('reference') is-invalid @enderror" placeholder="{{ __('costs.ref_ph') }}">
                    @error('reference')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('costs.note_lbl') }} <span class="opt">{{ __('costs.optional') }}</span>
                    </label>
                    <textarea name="note" rows="2" maxlength="1000"
                              class="form-input @error('note') is-invalid @enderror"
                              placeholder="{{ __('costs.note_ph') }}">{{ old('note') }}</textarea>
                    @error('note')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <label class="check-row">
                    <input type="checkbox" name="keep_current_active" value="1" @checked(old('keep_current_active'))>
                    <span>
                        <span style="font-weight:600;">{{ __('costs.keep_active') }}</span>
                        <span class="ds">{{ __('costs.keep_active_d') }}</span>
                    </span>
                </label>

                <div class="btn-row" style="justify-content:flex-end;">
                    <button type="submit" class="btn-solid"><i class="bi bi-save"></i> {{ __('costs.save_rate') }}</button>
                </div>
            </form>
        @else
            <div class="q-empty"><i class="bi bi-lock"></i>{{ __('ui.no_access') }}</div>
        @endcan
    </div>

    {{-- Bukti kurs aktif + penjelasan singkat --}}
    <div class="d-flex flex-column gap-3">
    <div class="q-card">
        <div class="q-card-head">
            <div class="d-flex align-items-center gap-2">
                <div class="q-icon-box"><i class="bi bi-shield-check"></i></div>
                <div>
                    <div class="q-card-title">{{ __('costs.proof_title') }}</div>
                    <div class="q-card-sub">{{ __('costs.proof_sub') }}</div>
                </div>
            </div>
        </div>

        @if($activeRate)
            <div class="kv">
                <span class="k">{{ __('costs.k_rate') }}</span>
                <span class="v" style="font-family:ui-monospace,monospace;">Rp {{ number_format((float) $activeRate->rate, 0, ',', '.') }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('costs.k_date') }}</span>
                <span class="v">{{ $activeRate->rate_date->translatedFormat('d M Y') }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('costs.k_source') }}</span>
                <span class="v">{{ $activeRate->source }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('costs.k_ref') }}</span>
                <span class="v">{{ $activeRate->reference ?: '—' }}</span>
            </div>
            @if($activeRate->note)
                <div class="kv">
                    <span class="k">{{ __('costs.k_note') }}</span>
                    <span class="v" style="font-weight:500;">{{ $activeRate->note }}</span>
                </div>
            @endif
            <div class="kv">
                <span class="k">{{ __('costs.k_by') }}</span>
                <span class="v">
                    {{ $activeRate->created_by }}
                    <span class="sub">{{ $activeRate->created_at?->translatedFormat('d M Y H:i') }}</span>
                </span>
            </div>
        @else
            <div class="q-empty"><i class="bi bi-inbox"></i>{{ __('costs.proof_empty') }}</div>
        @endif
    </div>

    <div class="q-card">
        <div class="q-card-head">
            <div class="d-flex align-items-center gap-2">
                <div class="q-icon-box"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <div class="q-card-title">{{ __('costs.how_title') }}</div>
                    <div class="q-card-sub">{{ __('costs.how_sub') }}</div>
                </div>
            </div>
        </div>

        @foreach([
            ['ic' => 'bi-cash-stack', 'tone' => 'tone-green',  't' => __('costs.how_1'), 'd' => __('costs.how_1_d')],
            ['ic' => 'bi-percent',    'tone' => 'tone-violet', 't' => __('costs.how_2'), 'd' => __('costs.how_2_d')],
            ['ic' => 'bi-bell-fill',  'tone' => 'tone-amber',  't' => __('costs.how_3'), 'd' => __('costs.how_3_d')],
        ] as $info)
            <div class="info-item" style="animation-delay: {{ $loop->index * 40 }}ms;">
                <div class="info-dot {{ $info['tone'] }}"><i class="bi {{ $info['ic'] }}"></i></div>
                <div style="min-width:0;">
                    <div class="info-title">{{ $info['t'] }}</div>
                    <div class="info-desc">{{ $info['d'] }}</div>
                </div>
            </div>
        @endforeach

        <div class="soft-note"><i class="bi bi-info-circle-fill"></i><span>{{ __('costs.how_note') }}</span></div>
    </div>
    </div>
</div>

{{-- ===================== Batas per API key ===================== --}}
<div class="q-card" style="margin-bottom:16px;">
    <div class="q-card-head">
        <div class="d-flex align-items-center gap-2">
            <div class="q-icon-box"><i class="bi bi-bell-fill"></i></div>
            <div>
                <div class="q-card-title">{{ __('costs.keyb_title') }} <span class="soft-count">{{ $keyBudgets->count() }}</span></div>
                <div class="q-card-sub">{{ __('costs.keyb_sub') }}</div>
            </div>
        </div>
    </div>

    @if($keyBudgets->count())
        <div class="table-wrap">
            <table class="h-table">
                <thead>
                    <tr>
                        <th>{{ __('costs.keyb_key') }}</th>
                        <th>{{ __('costs.keyb_acc') }}</th>
                        <th style="text-align:right;">{{ __('costs.keyb_limit') }}</th>
                        <th>{{ __('costs.keyb_by') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keyBudgets as $kb)
                        <tr style="animation-delay: {{ $loop->index * 26 }}ms;">
                            <td style="font-weight:600;font-family:ui-monospace,monospace;">{{ $kb->key_name }}</td>
                            <td>{{ $kb->awsAccount?->name ?? '.env' }}</td>
                            <td class="num" style="text-align:right;">${{ rtrim(rtrim(number_format((float) $kb->limit_usd, 2), '0'), '.') }}</td>
                            <td class="dim" style="white-space:nowrap;">
                                {{ $kb->updated_by ?: '—' }}
                                <span style="display:block;">{{ $kb->updated_at?->translatedFormat('d M Y H:i') }}</span>
                            </td>
                            <td style="text-align:right;">
                                @can('api_keys.view')
                                    <a href="{{ route('admin.api-keys.usage', array_filter(['keyName' => $kb->key_name, 'account' => $kb->aws_account_id])) }}"
                                       class="q-ghost-btn" title="{{ __('costs.keyb_open') }}"><i class="bi bi-box-arrow-up-right"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="q-empty"><i class="bi bi-bell-slash"></i>{{ __('costs.keyb_empty') }}</div>
    @endif

    <div class="soft-note"><i class="bi bi-info-circle-fill"></i><span>{{ __('costs.keyb_note') }}</span></div>
</div>

{{-- ===================== Riwayat ===================== --}}
<div class="q-card">
    <div class="q-card-head">
        <div class="d-flex align-items-center gap-2">
            <div class="q-icon-box"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="q-card-title">{{ __('costs.hist_title') }}</div>
                <div class="q-card-sub">{{ __('costs.hist_sub') }}</div>
            </div>
        </div>
    </div>

    @if($rates->count())
        <div class="table-wrap">
            <table class="h-table">
                <thead>
                    <tr>
                        <th>{{ __('costs.th_date') }}</th>
                        <th style="text-align:right;">{{ __('costs.th_rate') }}</th>
                        <th>{{ __('costs.th_source') }}</th>
                        <th>{{ __('costs.th_ref') }}</th>
                        <th>{{ __('costs.th_by') }}</th>
                        <th style="text-align:center;">{{ __('costs.th_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rates as $r)
                        <tr class="{{ $r->is_active ? 'on' : '' }}" style="animation-delay: {{ $loop->index * 26 }}ms;">
                            <td style="font-weight:600;white-space:nowrap;">{{ $r->rate_date->translatedFormat('d M Y') }}</td>
                            <td class="num" style="text-align:right;">{{ number_format((float) $r->rate, 0, ',', '.') }}</td>
                            <td>{{ $r->source }}</td>
                            <td class="dim">
                                {{ $r->reference ?: '—' }}
                                @if($r->note)<span style="display:block;">{{ \Illuminate\Support\Str::limit($r->note, 40) }}</span>@endif
                            </td>
                            <td class="dim" style="white-space:nowrap;">
                                {{ $r->created_by }}
                                <span style="display:block;">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</span>
                            </td>
                            <td style="text-align:center;">
                                @if($r->is_active)
                                    <span class="pill-badge ok"><i class="bi bi-check-circle-fill"></i> {{ __('costs.st_active') }}</span>
                                @else
                                    <span class="pill-badge plain">{{ __('costs.st_archive') }}</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                @can('cost_settings.update')
                                    @unless($r->is_active)
                                        <form method="POST" action="{{ route('admin.cost-settings.rate.activate', $r) }}"
                                              id="act-{{ $r->id }}" style="margin:0;">
                                            @csrf
                                            <button type="button" class="q-ghost-btn" title="{{ __('costs.activate') }}"
                                                    data-activate data-form="act-{{ $r->id }}"
                                                    data-rate="Rp {{ number_format((float) $r->rate, 0, ',', '.') }}"
                                                    data-meta="{{ $r->rate_date->translatedFormat('d M Y') }} · {{ $r->source }}">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            <div class="pager-info">
                {{ __('costs.showing', ['from' => $rates->firstItem(), 'to' => $rates->lastItem(), 'total' => $rates->total()]) }}
            </div>

            @if($rates->hasPages())
                <a href="{{ $rates->previousPageUrl() ?? '#' }}"
                   class="pager-btn {{ $rates->onFirstPage() ? 'off' : '' }}" aria-label="{{ __('ui.prev') }}">
                    <i class="bi bi-chevron-left"></i>
                </a>

                @foreach($rates->getUrlRange(max(1, $rates->currentPage() - 2), min($rates->lastPage(), $rates->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" class="pager-btn {{ $page === $rates->currentPage() ? 'on' : '' }}">{{ $page }}</a>
                @endforeach

                @if($rates->currentPage() + 2 < $rates->lastPage())
                    <span class="pager-gap">…</span>
                    <a href="{{ $rates->url($rates->lastPage()) }}" class="pager-btn">{{ $rates->lastPage() }}</a>
                @endif

                <a href="{{ $rates->nextPageUrl() ?? '#' }}"
                   class="pager-btn {{ $rates->hasMorePages() ? '' : 'off' }}" aria-label="{{ __('ui.next') }}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            @endif
        </div>
    @else
        <div class="q-empty"><i class="bi bi-clock-history"></i>{{ __('costs.hist_empty') }}</div>
    @endif
</div>

@can('cost_settings.update')
    <div class="gm-modal" id="activateModal" role="dialog" aria-modal="true">
        <div class="gm-modal-card">
            <div class="gm-modal-head">
                <div class="gm-modal-icon"><i class="bi bi-arrow-left-right"></i></div>
                <div class="gm-modal-title">{{ __('costs.swap_title') }}</div>
                <div class="gm-modal-sub">{{ __('costs.swap_sub') }}</div>
            </div>
            <div class="gm-modal-body">
                @if($activeRate)
                    <div class="swap">
                        <div class="swap-row out">
                            <span class="swap-ic"><i class="bi bi-cash-stack"></i></span>
                            <span class="swap-tx">
                                <span class="nm">Rp {{ number_format((float) $activeRate->rate, 0, ',', '.') }}</span>
                                <span class="rg">{{ $activeRate->rate_date->translatedFormat('d M Y') }} · {{ $activeRate->source }}</span>
                            </span>
                            <span class="swap-tag">{{ __('costs.swap_from') }}</span>
                        </div>

                        <div class="swap-arrow"><i class="bi bi-arrow-down"></i></div>

                        <div class="swap-row in">
                            <span class="swap-ic"><i class="bi bi-cash-stack"></i></span>
                            <span class="swap-tx">
                                <span class="nm" id="actRate">—</span>
                                <span class="rg" id="actMeta">—</span>
                            </span>
                            <span class="swap-tag">{{ __('costs.swap_to') }}</span>
                        </div>
                    </div>

                    <div class="note"><i class="bi bi-info-circle-fill"></i><span>{{ __('costs.swap_note') }}</span></div>
                @else
                    <div class="swap">
                        <div class="swap-row in">
                            <span class="swap-ic"><i class="bi bi-cash-stack"></i></span>
                            <span class="swap-tx">
                                <span class="nm" id="actRate">—</span>
                                <span class="rg" id="actMeta">—</span>
                            </span>
                            <span class="swap-tag">{{ __('costs.swap_to') }}</span>
                        </div>
                    </div>
                    <div class="note"><i class="bi bi-info-circle-fill"></i><span>{{ __('costs.swap_first') }}</span></div>
                @endif

                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                    <button type="button" class="btn-solid" id="actConfirm">
                        <i class="bi bi-check2-circle"></i> {{ __('costs.swap_confirm') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endcan
@include('admin.partials.form-validate')
@endsection

@push('scripts')
<script>
    // Kartu PPN & budget: tampilan angka bertukar dengan formulirnya di tempat.
    (function () {
        document.addEventListener('click', (e) => {
            const open = e.target.closest('[data-edit]');
            if (open) {
                flip(open.dataset.edit, true);
                return;
            }

            const cancel = e.target.closest('[data-cancel]');
            if (cancel) flip(cancel.dataset.cancel, false);
        });

        function flip(key, editing) {
            const face = document.getElementById(key + 'Face');
            const form = document.getElementById(key + 'Form');
            if (!face || !form) return;

            face.hidden = editing;
            form.hidden = !editing;
            if (editing) form.querySelector('input')?.focus();
        }
    })();

    // Kurs ditulis dengan gaya rupiah (16.500) sambil diketik. Titik hanya hiasan
    // pemisah ribuan; koma tetap boleh untuk pecahan (mis. JISDOR 16.512,45).
    (function () {
        const input = document.getElementById('rateInput');
        if (!input) return;

        function format(raw) {
            // Sisakan angka dan paling banyak satu koma.
            let clean = raw.replace(/[^\d,]/g, '');
            const parts = clean.split(',');
            let int = parts.shift().replace(/^0+(?=\d)/, '');
            const dec = parts.length ? ',' + parts.join('').slice(0, 2) : '';

            return (int ? int.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '') + dec;
        }

        input.addEventListener('input', () => {
            const before = input.value;
            const atEnd  = input.selectionStart === before.length;
            const value  = format(before);

            if (value === before) return;

            input.value = value;
            if (!atEnd) {
                // Selisih panjang = jumlah titik yang bertambah/berkurang.
                const shift = value.length - before.length;
                const pos   = Math.max(0, (input.selectionStart || 0) + shift);
                input.setSelectionRange(pos, pos);
            }
        });

        input.value = format(input.value);
    })();

    // Chip sumber kurs hanya mengisi input — teksnya tetap bebas diketik.
    (function () {
        const input = document.getElementById('srcInput');
        const chips = document.getElementById('srcChips');
        if (!input || !chips) return;

        const paint = () => chips.querySelectorAll('.src-chip').forEach((c) => {
            c.classList.toggle('on', c.dataset.src === input.value.trim());
        });

        chips.addEventListener('click', (e) => {
            const chip = e.target.closest('.src-chip');
            if (!chip) return;
            input.value = chip.dataset.src;
            paint();
        });

        input.addEventListener('input', paint);
        paint();
    })();

    // Ganti kurs aktif lewat konfirmasi — satu kurs aktif untuk semua estimasi & invoice.
    (function () {
        const modal = document.getElementById('activateModal');
        if (!modal) return;

        const close = () => modal.classList.remove('open');

        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-close]') || e.target === modal) {
                close();
                return;
            }

            const btn = e.target.closest('[data-activate]');
            if (!btn) return;

            document.getElementById('actRate').textContent = btn.dataset.rate;
            document.getElementById('actMeta').textContent = btn.dataset.meta;
            modal.dataset.form = btn.dataset.form;
            modal.classList.add('open');
        });

        document.getElementById('actConfirm').addEventListener('click', () => {
            const form = document.getElementById(modal.dataset.form || '');
            if (!form) return;
            close();
            // requestSubmit() melepas event 'submit' — page loader ikut menyala.
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    })();
</script>
@endpush
