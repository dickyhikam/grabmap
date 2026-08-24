@extends('layouts.admin-v2')

@php $editing = isset($account); @endphp

@section('title', $editing ? __('awsaccounts.form_edit_title') : __('awsaccounts.form_add_title'))

@push('styles')
    .acc-grid2 {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1000px) { .acc-grid2 { grid-template-columns: 1fr; } }

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    .sec { margin-bottom: 22px; }
    .sec-head {
        display: flex; align-items: center; gap: 11px;
        padding-bottom: 13px; margin-bottom: 16px;
        border-bottom: 1px solid var(--line);
    }
    .sec-head .ic {
        width: 36px; height: 36px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: var(--green-soft); color: var(--green-text); font-size: 0.9rem; flex-shrink: 0;
    }
    .sec-head .nm { font-weight: 700; font-size: 0.88rem; }
    .sec-head .sb { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }

    .f-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
    @media (max-width: 620px) { .f-row { grid-template-columns: 1fr; } }

    .form-field { margin-bottom: 18px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 600; color: var(--faint); text-transform: none; letter-spacing: 0; }
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    textarea.form-input { height: auto; padding: 12px 14px; resize: vertical; line-height: 1.6; }
    .form-input.mono { font-family: ui-monospace, monospace; font-size: 0.8rem; letter-spacing: 0.01em; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-input.has-eye { padding-right: 44px; }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* Region cepat-pilih */
    .reg-quick { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 8px; }
    .reg-quick .lb { font-size: 0.68rem; color: var(--muted); margin-right: 2px; }
    .reg-chip {
        border: 1px solid var(--line); background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 4px 11px;
        font-size: 0.68rem; font-weight: 600; font-family: ui-monospace, monospace;
        cursor: pointer; transition: all 0.15s;
    }
    .reg-chip:hover { border-color: var(--green); color: var(--ink); }
    .reg-chip.on { background: var(--green); border-color: var(--green); color: #fff; }

    /* Sakelar */
    .sw-row {
        display: flex; align-items: center; gap: 13px;
        padding: 13px 15px; border-radius: 16px; position: relative;
        background: var(--surface); margin-bottom: 10px;
        cursor: pointer; transition: background 0.16s;
    }
    .sw-row:hover { background: var(--line); }
    .sw-row .tx { flex: 1; min-width: 0; }
    .sw-row .nm { font-weight: 600; font-size: 0.83rem; }
    .sw-row .ds { font-size: 0.69rem; color: var(--muted); margin-top: 2px; }
    .sw-row input { position: absolute; opacity: 0; pointer-events: none; }

    .sw {
        width: 44px; height: 25px; border-radius: 999px;
        background: var(--line); flex-shrink: 0; position: relative;
        transition: background 0.22s cubic-bezier(0.34, 1.3, 0.6, 1);
    }
    .sw::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 19px; height: 19px; border-radius: 50%;
        background: var(--card); box-shadow: 0 1px 4px rgba(0, 0, 0, 0.22);
        transition: transform 0.22s cubic-bezier(0.34, 1.3, 0.6, 1);
    }
    .sw-row input:checked ~ .sw { background: var(--green); }
    .sw-row input:checked ~ .sw::after { transform: translateX(19px); }
    .sw-row input:focus-visible ~ .sw { box-shadow: 0 0 0 4px var(--green-soft); }
    .sw-row input:disabled ~ .sw { opacity: 0.55; }
    .sw-row.locked { cursor: default; opacity: 0.75; }

    .swap-warn {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 11px 14px; margin: -2px 0 10px;
        font-size: 0.73rem; line-height: 1.5;
        background: var(--warn-soft); color: var(--warn-fg);
        animation: warnIn 0.28s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    .swap-warn[hidden] { display: none; }
    @keyframes warnIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: none; }
    }

    /* Sisi kanan */
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
    .info-desc { font-size: 0.71rem; color: var(--muted); margin-top: 3px; line-height: 1.5; font-family: ui-monospace, monospace; }

    .kv { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 0.8rem; }
    .kv:last-child { border-bottom: none; }
    .kv .k { color: var(--muted); }
    .kv .v { font-weight: 600; text-align: right; word-break: break-all; }

    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 12px 14px; margin-bottom: 18px;
        font-size: 0.74rem; line-height: 1.5;
        background: var(--surface); color: var(--muted);
    }

    .btn-row { justify-content: flex-end; margin-top: 4px; }
@endpush

@section('content')
@php
    $regions = ['ap-southeast-1', 'ap-southeast-3', 'ap-southeast-5', 'us-east-1', 'eu-west-1'];
    $regionValue = old('region', $account->region ?? config('aws.region', 'ap-southeast-1'));
@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.aws-accounts.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('awsaccounts.back') }}
        </a>
        <h1 class="q-title">
            @if($editing)
                {{ __('awsaccounts.form_edit') }} <span class="soft">{{ $account->name }}</span>
            @else
                {{ __('awsaccounts.form_add') }} <span class="soft">{{ __('awsaccounts.form_add_word') }}</span>
            @endif
        </h1>
    </div>
</div>

<form method="POST" id="accForm" data-validate
      action="{{ $editing ? route('admin.aws-accounts.update', $account) : route('admin.aws-accounts.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="acc-grid2">
        {{-- ===================== Formulir ===================== --}}
        <div class="q-card" style="padding:24px;">

            {{-- Identitas --}}
            <div class="sec">
                <div class="sec-head">
                    <span class="ic"><i class="bi bi-card-heading"></i></span>
                    <span>
                        <span class="nm d-block">{{ __('awsaccounts.identity') }}</span>
                        <span class="sb">{{ __('awsaccounts.identity_sub') }}</span>
                    </span>
                </div>

                <div class="f-row">
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('awsaccounts.name') }}<span class="req">*</span></label>
                        <input type="text" name="name" class="form-input @error('name') is-invalid @enderror"
                               value="{{ old('name', $account->name ?? '') }}"
                               placeholder="{{ __('awsaccounts.name_ph') }}" maxlength="100" required>
                        @error('name')
                            <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                        @else
                            <div class="form-hint">{{ __('awsaccounts.name_hint') }}</div>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label-sm">
                            {{ __('awsaccounts.account_id_lbl') }} <span class="opt">{{ __('awsaccounts.optional') }}</span>
                        </label>
                        <input type="text" name="account_number" inputmode="numeric" maxlength="12"
                               data-v-digits="12" data-v-msg="{{ __('awsaccounts.err_account_id') }}"
                               class="form-input mono @error('account_number') is-invalid @enderror"
                               value="{{ old('account_number', $account->account_number ?? '') }}"
                               placeholder="{{ __('awsaccounts.account_id_ph') }}">
                        @error('account_number')
                            <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                        @else
                            <div class="form-hint">{{ __('awsaccounts.account_id_hint') }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Kredensial --}}
            <div class="sec">
                <div class="sec-head">
                    <span class="ic"><i class="bi bi-shield-lock-fill"></i></span>
                    <span>
                        <span class="nm d-block">{{ __('awsaccounts.credentials') }}</span>
                        <span class="sb">{{ __('awsaccounts.credentials_sub') }}</span>
                    </span>
                </div>

                <div class="f-row">
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('awsaccounts.access_key') }}<span class="req">*</span></label>
                        <input type="text" name="access_key_id" autocomplete="off" maxlength="128"
                               class="form-input mono @error('access_key_id') is-invalid @enderror"
                               value="{{ old('access_key_id', $account->access_key_id ?? '') }}"
                               placeholder="{{ __('awsaccounts.access_key_ph') }}" required>
                        @error('access_key_id')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label-sm">
                            {{ __('awsaccounts.secret') }}@unless($editing)<span class="req">*</span>@endunless
                        </label>
                        <div style="position:relative;">
                            <input type="password" name="secret_access_key" id="accSecret" autocomplete="new-password"
                                   class="form-input mono has-eye @error('secret_access_key') is-invalid @enderror"
                                   placeholder="{{ $editing ? '••••••••••••••••' : __('awsaccounts.secret_ph') }}"
                                   {{ $editing ? '' : 'required' }}>
                            <button type="button" class="pw-eye" data-toggle-pw="accSecret" tabindex="-1" aria-label="{{ __('awsaccounts.secret') }}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('secret_access_key')
                            <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                        @elseif($editing)
                            <div class="form-hint">{{ __('awsaccounts.secret_keep') }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('awsaccounts.region') }}<span class="req">*</span></label>
                    <input type="text" name="region" id="accRegion" maxlength="32"
                           data-v-pattern="^[a-z0-9\-]+$" data-v-msg="{{ __('awsaccounts.err_region') }}"
                           class="form-input mono @error('region') is-invalid @enderror"
                           value="{{ $regionValue }}" placeholder="ap-southeast-1" required>

                    <div class="reg-quick" id="regQuick">
                        <span class="lb">{{ __('awsaccounts.region_quick') }}</span>
                        @foreach($regions as $r)
                            <button type="button" class="reg-chip {{ $r === $regionValue ? 'on' : '' }}" data-region="{{ $r }}">{{ $r }}</button>
                        @endforeach
                    </div>

                    @error('region')
                        <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                    @else
                        <div class="form-hint">{{ __('awsaccounts.region_hint') }}</div>
                    @enderror
                </div>
            </div>

            {{-- Status & catatan --}}
            <div class="sec">
                <div class="sec-head">
                    <span class="ic"><i class="bi bi-toggles"></i></span>
                    <span>
                        <span class="nm d-block">{{ __('awsaccounts.options') }}</span>
                        <span class="sb">{{ __('awsaccounts.options_sub') }}</span>
                    </span>
                </div>

                <label class="sw-row">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))>
                    <span class="tx">
                        <span class="nm d-block">{{ __('awsaccounts.is_active') }}</span>
                        <span class="ds">{{ __('awsaccounts.is_active_d') }}</span>
                    </span>
                    <span class="sw"></span>
                </label>

                @php
                    $lockedDefault = $editing && $account->is_default;
                    // Akun default yang berlaku sekarang — kalau bukan akun ini, menyalakan
                    // sakelar berarti melepas akun itu dari default.
                    $current      = $currentDefault ?? null;
                    $willReplace  = $current && (!$editing || $current->id !== $account->id);
                @endphp
                <label class="sw-row {{ $lockedDefault ? 'locked' : '' }}">
                    <input type="checkbox" name="is_default" value="1" id="accDefault"
                           @checked(old('is_default', $account->is_default ?? false)) {{ $lockedDefault ? 'disabled' : '' }}>
                    <span class="tx">
                        <span class="nm d-block">{{ __('awsaccounts.is_default') }}</span>
                        <span class="ds">
                            @if($lockedDefault)
                                {{ __('awsaccounts.default_locked') }}
                            @elseif($willReplace)
                                {{ __('awsaccounts.is_default_d') }}
                            @else
                                {{ __('awsaccounts.first_default') }}
                            @endif
                        </span>
                    </span>
                    <span class="sw"></span>
                </label>

                @if($willReplace && !$lockedDefault)
                    {{-- Muncul hanya saat sakelar dinyalakan: satu default per aplikasi, jadi
                         akun yang sekarang default pasti dilepas. --}}
                    <div class="swap-warn" id="defWarn" hidden>
                        <i class="bi bi-arrow-left-right"></i>
                        <span>{!! __('awsaccounts.default_warn', ['name' => '<b>' . e($current->name) . '</b>']) !!}</span>
                    </div>
                @endif

                <div class="form-field" style="margin-top:18px;">
                    <label class="form-label-sm">
                        {{ __('awsaccounts.notes') }} <span class="opt">{{ __('awsaccounts.optional') }}</span>
                    </label>
                    <textarea name="notes" rows="2" maxlength="1000"
                              class="form-input @error('notes') is-invalid @enderror"
                              placeholder="{{ __('awsaccounts.notes_ph') }}">{{ old('notes', $account->notes ?? '') }}</textarea>
                    @error('notes')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>
            </div>

            <div class="btn-row">
                <a href="{{ route('admin.aws-accounts.index') }}" class="btn-soft"><i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}</a>
                <button type="submit" class="btn-solid">
                    <i class="bi bi-{{ $editing ? 'check-lg' : 'plus-lg' }}"></i>
                    {{ $editing ? __('awsaccounts.save_btn') : __('awsaccounts.create_btn') }}
                </button>
            </div>
        </div>

        {{-- ===================== Sisi kanan ===================== --}}
        <div class="d-flex flex-column gap-3">
            @if($editing)
                <div class="q-card">
                    <div class="q-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="q-icon-box"><i class="bi bi-cloud-fill"></i></div>
                            <div>
                                <div class="q-card-title">{{ __('awsaccounts.status_title') }}</div>
                                <div class="q-card-sub">{{ __('awsaccounts.status_sub') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="kv">
                        <span class="k">{{ __('awsaccounts.options') }}</span>
                        <span class="v" style="color:{{ $account->is_active ? 'var(--green-text)' : 'var(--warn-fg)' }};">
                            {{ $account->is_active ? __('awsaccounts.active') : __('awsaccounts.inactive') }}
                        </span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('awsaccounts.default') }}</span>
                        <span class="v">{{ $account->is_default ? __('awsaccounts.yes') : __('awsaccounts.no') }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('awsaccounts.secret') }}</span>
                        <span class="v">{{ $account->secret_access_key ? __('awsaccounts.secret_saved') : __('awsaccounts.secret_missing') }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('awsaccounts.verified') }}</span>
                        <span class="v">{{ $account->last_verified_at?->translatedFormat('d M Y H:i') ?? __('awsaccounts.never_tested') }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('awsaccounts.added') }}</span>
                        <span class="v">{{ $account->created_at?->translatedFormat('d M Y') ?? '—' }}</span>
                    </div>

                    <div class="form-hint" style="margin-top:14px;">{{ __('awsaccounts.test_hint') }}</div>

                    @can('api_keys.view')
                        <a href="{{ route('admin.api-keys.index', ['account' => $account->id]) }}"
                           class="btn-soft" style="width:100%;margin-top:12px;">
                            <i class="bi bi-key"></i> {{ __('awsaccounts.api_keys') }}
                        </a>
                    @endcan
                </div>
            @endif

            <div class="q-card">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('awsaccounts.iam_title') }}</div>
                            <div class="q-card-sub">{{ __('awsaccounts.iam_sub') }}</div>
                        </div>
                    </div>
                </div>

                @foreach([
                    ['ic' => 'bi-key-fill',        'tone' => 'tone-green',  't' => __('awsaccounts.iam_1'), 'd' => __('awsaccounts.iam_1_d')],
                    ['ic' => 'bi-graph-up',        'tone' => 'tone-violet', 't' => __('awsaccounts.iam_2'), 'd' => __('awsaccounts.iam_2_d')],
                    ['ic' => 'bi-pencil-fill',     'tone' => 'tone-amber',  't' => __('awsaccounts.iam_3'), 'd' => __('awsaccounts.iam_3_d')],
                ] as $info)
                    <div class="info-item" style="animation-delay: {{ $loop->index * 40 }}ms;">
                        <div class="info-dot {{ $info['tone'] }}"><i class="bi {{ $info['ic'] }}"></i></div>
                        <div style="min-width:0;">
                            <div class="info-title">{{ $info['t'] }}</div>
                            <div class="info-desc">{{ $info['d'] }}</div>
                        </div>
                    </div>
                @endforeach

                <div class="note" style="margin:16px 0 0;">
                    <i class="bi bi-lock-fill"></i><span>{{ __('awsaccounts.credentials_sub') }}.</span>
                </div>
            </div>
        </div>
    </div>
</form>
@include('admin.partials.form-validate')
@endsection

@push('scripts')
<script>
    (function () {
        // Chip region hanya mengisi input — nilainya tetap bebas diketik.
        const input = document.getElementById('accRegion');
        const quick = document.getElementById('regQuick');
        if (!input || !quick) return;

        function paint() {
            quick.querySelectorAll('.reg-chip').forEach((c) => {
                c.classList.toggle('on', c.dataset.region === input.value.trim());
            });
        }

        quick.addEventListener('click', (e) => {
            const chip = e.target.closest('.reg-chip');
            if (!chip) return;
            input.value = chip.dataset.region;
            paint();
        });

        input.addEventListener('input', paint);
    })();

    (function () {
        const box = document.getElementById('defWarn');
        const toggle = document.getElementById('accDefault');
        if (!box || !toggle) return;

        const sync = () => { box.hidden = !toggle.checked; };
        toggle.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
