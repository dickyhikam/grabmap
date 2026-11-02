@extends('layouts.admin-v2')

@section('title', $key ? __('apikeys.form_edit') : __('apikeys.form_add'))

@push('styles')
    .key-grid2 {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1000px) { .key-grid2 { grid-template-columns: 1fr; } }

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    .form-field { margin-bottom: 18px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    textarea.form-input { height: auto; padding: 12px 14px; resize: vertical; line-height: 1.6; font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input:disabled, .form-input[readonly] { opacity: 0.7; cursor: not-allowed; }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* ---------- Sisi kanan ---------- */
    .info-item {
        display: flex; gap: 12px; padding: 11px 0;
        border-bottom: 1px solid var(--line);
        animation: infoIn 0.3s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .info-item:last-child { border-bottom: none; }
    @keyframes infoIn {
        from { opacity: 0; transform: translateX(8px); }
        to   { opacity: 1; transform: none; }
    }
    .info-dot {
        width: 34px; height: 34px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; flex-shrink: 0;
    }
    .info-title { font-weight: 600; font-size: 0.82rem; }
    .info-desc { font-size: 0.71rem; color: var(--muted); margin-top: 3px; line-height: 1.5; }

    .kv { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 0.8rem; }
    .kv:last-child { border-bottom: none; }
    .kv .k { color: var(--muted); }
    .kv .v { font-weight: 600; text-align: right; word-break: break-all; }

    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }
    .tone-bad    { background: var(--danger-soft); color: var(--danger-fg); }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 12px 14px; margin-bottom: 18px;
        font-size: 0.74rem; line-height: 1.5;
        background: var(--surface); color: var(--muted);
    }

    .btn-row { justify-content: flex-end; }
@endpush

@section('content')
@php
    $isNew = $key === null;

@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.api-keys.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('apikeys.back') }}
        </a>
        <h1 class="q-title">
            @if($isNew)
                {{ __('apikeys.form_add') }} <span class="soft">{{ __('apikeys.form_add_word') }}</span>
            @else
                {{ __('apikeys.form_edit') }} <span class="soft">{{ $keyName }}</span>
            @endif
        </h1>
    </div>
</div>

<form method="POST" id="keyForm" data-validate
      action="{{ $isNew ? route('admin.api-keys.store') : route('admin.api-keys.update', ['keyName' => $keyName]) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless
    <input type="hidden" name="account" value="{{ $account?->id }}">

    <div class="key-grid2">
        {{-- ===================== Formulir ===================== --}}
        <div class="q-card" style="padding:24px;">
            @unless($isNew)
                <div class="note"><i class="bi bi-lock-fill"></i><span>{{ __('apikeys.locked_note') }}</span></div>
            @endunless

            @include('admin.api-keys._fields', ['key' => $key, 'keyName' => $keyName, 'budget' => $budget])

            <div class="btn-row">
                <a href="{{ route('admin.api-keys.index') }}" class="btn-soft"><i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}</a>
                <button type="submit" class="btn-solid">
                    <i class="bi bi-{{ $isNew ? 'plus-lg' : 'check-lg' }}"></i>
                    {{ $isNew ? __('apikeys.create_btn') : __('apikeys.save_btn') }}
                </button>
            </div>
        </div>

        {{-- ===================== Sisi kanan ===================== --}}
        <div>
            @if($isNew)
                <div class="q-card">
                    <div class="q-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="q-icon-box"><i class="bi bi-info-circle"></i></div>
                            <div>
                                <div class="q-card-title">{{ __('apikeys.info_title') }}</div>
                                <div class="q-card-sub">{{ __('apikeys.info_sub') }}</div>
                            </div>
                        </div>
                    </div>

                    @foreach([
                        ['ic' => 'bi-cloud-check-fill', 'tone' => 'tone-green',  't' => __('apikeys.info_1'), 'd' => __('apikeys.info_1_d', ['name' => $account?->name ?? '.env', 'region' => $region])],
                        ['ic' => 'bi-lock-fill',        'tone' => 'tone-violet', 't' => __('apikeys.info_2'), 'd' => __('apikeys.info_2_d')],
                        ['ic' => 'bi-shield-exclamation','tone' => 'tone-amber', 't' => __('apikeys.info_3'), 'd' => __('apikeys.info_3_d')],
                    ] as $info)
                        <div class="info-item" style="animation-delay: {{ $loop->index * 40 }}ms;">
                            <div class="info-dot {{ $info['tone'] }}"><i class="bi {{ $info['ic'] }}"></i></div>
                            <div style="min-width:0;">
                                <div class="info-title">{{ $info['t'] }}</div>
                                <div class="info-desc">{{ $info['d'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="q-card">
                    <div class="q-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="q-icon-box"><i class="bi bi-key-fill"></i></div>
                            <div>
                                <div class="q-card-title">{{ __('apikeys.detail_title') }}</div>
                                <div class="q-card-sub">{{ __('apikeys.detail_sub') }}</div>
                            </div>
                        </div>
                    </div>

                    @php $expired = $key['expire_time'] && \Carbon\Carbon::parse($key['expire_time'])->isPast(); @endphp
                    <div class="kv">
                        <span class="k">{{ __('apikeys.status') }}</span>
                        <span class="v" style="color:{{ $expired ? 'var(--danger-fg)' : 'var(--green-text)' }};">
                            {{ $expired ? __('apikeys.expired') : __('apikeys.active') }}
                        </span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.created') }}</span>
                        <span class="v">{{ $key['create_time'] ? \Carbon\Carbon::parse($key['create_time'])->wib()->translatedFormat('d M Y H:i') : '—' }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.expires') }}</span>
                        <span class="v">{{ $key['expire_time'] ? \Carbon\Carbon::parse($key['expire_time'])->wib()->translatedFormat('d M Y H:i') : __('apikeys.never') }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.account') }}</span>
                        <span class="v">{{ $account?->name ?? '.env' }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.region') }}</span>
                        <span class="v">{{ $region }}</span>
                    </div>

                    <a href="{{ route('admin.api-keys.usage', ['keyName' => $keyName, 'account' => $account?->getRouteKey()]) }}"
                       class="btn-soft" style="width:100%;margin-top:16px;">
                        <i class="bi bi-bar-chart"></i> {{ __('apikeys.usage') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</form>
@include('admin.partials.form-validate')
@endsection
