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

    /* ---------- Pilihan aksi per layanan ---------- */
    .svc-list { display: flex; flex-direction: column; gap: 10px; }

    .svc-group {
        border: 1.5px solid var(--line); border-radius: 18px;
        overflow: hidden; transition: border-color 0.18s, background 0.18s;
    }
    .svc-group.on { border-color: var(--green); }

    .svc-head {
        display: flex; align-items: center; gap: 11px;
        padding: 13px 14px;
        background: var(--surface);
        transition: background 0.18s;
    }
    .svc-group.on .svc-head { background: var(--green-soft); }
    .svc-head .ic {
        width: 36px; height: 36px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        background: var(--card); color: var(--muted); font-size: 0.95rem; flex-shrink: 0;
        transition: background 0.18s, color 0.18s;
    }
    .svc-group.on .svc-head .ic { background: var(--green); color: #fff; }
    .svc-head .nm { font-weight: 700; font-size: 0.85rem; }
    .svc-head .ds { font-size: 0.68rem; color: var(--muted); }
    .svc-head .picked {
        font-size: 0.68rem; font-weight: 700; color: var(--muted);
        background: var(--card); border-radius: 999px; padding: 4px 10px; white-space: nowrap;
    }
    .svc-group.on .svc-head .picked { color: var(--green-text); }

    .svc-actions {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 2px 10px; padding: 10px 14px 14px;
    }

    .act {
        display: flex; align-items: center; gap: 9px;
        padding: 7px 0; font-size: 0.79rem; cursor: pointer;
    }
    .act input { accent-color: var(--green); width: 16px; height: 16px; flex-shrink: 0; }
    .act .txt { flex: 1; min-width: 0; }
    .act .code { font-size: 0.63rem; color: var(--faint); font-family: ui-monospace, monospace; }
    .act.locked { cursor: default; opacity: 0.65; }

    /* Input dengan awalan mata uang di dalamnya. */
    .with-prefix { position: relative; }
    .with-prefix .pfx {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        font-size: 0.82rem; font-weight: 700; color: var(--muted); pointer-events: none; z-index: 2;
    }
    .with-prefix .form-input { padding-left: 34px; font-family: ui-monospace, monospace; }

    /* ---------- Masa berlaku ---------- */
    .exp-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .exp-chip {
        padding: 9px 16px; border-radius: 999px;
        border: 1.5px solid var(--line); background: var(--surface);
        font-size: 0.79rem; font-weight: 600; color: var(--muted);
        cursor: pointer; transition: all 0.18s;
    }
    .exp-chip:hover { border-color: var(--green); color: var(--ink); }
    input:checked + .exp-chip { background: var(--green); border-color: var(--green); color: #fff; box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3); }
    .exp-date { margin-top: 12px; }
    .exp-date[hidden] { display: none; }

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

    // Layanan yang aktif pada key dibaca dari AllowActions-nya (mode ubah).
    $actions = collect($key['restrictions']['AllowActions'] ?? []);
    $current = [
        'maps'   => $actions->contains(fn ($a) => str_starts_with($a, 'geo-maps')),
        'places' => $actions->contains(fn ($a) => str_starts_with($a, 'geo-places')),
        'routes' => $actions->contains(fn ($a) => str_starts_with($a, 'geo-routes')),
    ];
    $referers = $key['restrictions']['AllowReferers'] ?? [];

    $catalog = config('geo_actions');
    $labels  = __('geo_actions.keys');

    // Aksi yang sudah menempel di key (mode ubah).
    $keyActions = collect($key['restrictions']['AllowActions'] ?? []);

    // Nilai terpilih: dari input lama (validasi gagal) atau dari key itu sendiri.
    $chosen = old('actions', $keyActions->all());

    // Mode masa berlaku: default "selamanya" untuk key baru, ikut keadaan key saat mengubah.
    $mode = old('expiry_mode', $isNew ? 'never' : ($key['expire_time'] ? 'date' : 'never'));
    $presets = [30, 90, 180, 365];
    $expireValue = old('expire_date', $key['expire_time'] ?? now()->addDays(90)->format('Y-m-d H:i'));
    $expireValue = \Carbon\Carbon::parse($expireValue)->format('Y-m-d H:i');
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

            <div class="form-field">
                <label class="form-label-sm">
                    {{ __('apikeys.name') }}@if($isNew)<span class="req">*</span>@endif
                </label>
                <input type="text" name="key_name" class="form-input @error('key_name') is-invalid @enderror"
                       maxlength="100"
                       @if($isNew) data-v-pattern="^[A-Za-z0-9_.\-]+$" data-v-msg="{{ __('apikeys.err_name') }}" @endif
                       placeholder="{{ __('apikeys.name_ph') }}"
                       value="{{ old('key_name', $keyName ?? '') }}"
                       {{ $isNew ? 'required' : 'readonly' }}
                       style="{{ $isNew ? '' : 'font-family:ui-monospace,monospace;' }}">
                @if($isNew)<div class="form-hint">{{ __('apikeys.name_hint') }}</div>@endif
                @error('key_name')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('apikeys.description_lbl') }}</label>
                <textarea name="description" class="form-input" rows="2" maxlength="1000"
                          style="font-family:inherit;font-size:0.85rem;"
                          placeholder="{{ __('apikeys.description_ph') }}">{{ old('description', $key['description'] ?? '') }}</textarea>
                @error('description')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">
                    {{ __('apikeys.services') }}@if($isNew)<span class="req">*</span>@endif
                </label>

                <div class="svc-list" @if($isNew) data-v-group data-v-msg="{{ __('apikeys.err_actions') }}" @endif>
                    @foreach($catalog as $groupKey => $group)
                        @php
                            $groupAll   = in_array($group['wildcard'], $chosen, true);
                            $groupPicks = array_values(array_intersect($group['actions'], $chosen));
                            $groupOn    = $groupAll || count($groupPicks) > 0;
                        @endphp
                        <div class="svc-group {{ $groupOn ? 'on' : '' }}" data-group="{{ $groupKey }}">
                            <div class="svc-head">
                                <span class="ic"><i class="bi {{ $group['icon'] }}"></i></span>
                                <span style="flex:1;min-width:0;">
                                    <span class="nm d-block">{{ __('geo_actions.groups.' . $groupKey) }}</span>
                                    <span class="ds">{{ __('geo_actions.group_desc.' . $groupKey) }}</span>
                                </span>
                                <span class="picked" data-picked>
                                    {{ $groupAll ? __('geo_actions.all.' . $groupKey) : ($groupPicks ? count($groupPicks) . '/' . count($group['actions']) : '—') }}
                                </span>
                            </div>

                            <div class="svc-actions">
                                {{-- "Semua" mengirim wildcard; aksi satuan grup ini jadi tidak perlu ikut. --}}
                                <label class="act {{ $isNew ? '' : 'locked' }}">
                                    <input type="checkbox" name="actions[]" value="{{ $group['wildcard'] }}"
                                           data-all @checked($groupAll) {{ $isNew ? '' : 'disabled' }}>
                                    <span class="txt"><b>{{ __('geo_actions.all.' . $groupKey) }}</b></span>
                                </label>

                                @foreach($group['actions'] as $action)
                                    <label class="act {{ $isNew ? '' : 'locked' }}">
                                        <input type="checkbox" name="actions[]" value="{{ $action }}"
                                               @checked(in_array($action, $chosen, true)) {{ $isNew ? '' : 'disabled' }}>
                                        <span class="txt">{{ $labels[$action] ?? $action }}</span>
                                        <span class="code">{{ \Illuminate\Support\Str::after($action, ':') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($isNew)<div class="form-hint">{{ __('apikeys.services_hint') }}</div>@endif
                @error('actions')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                @error('actions.*')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('apikeys.referers') }}</label>
                @if($isNew)
                    <textarea name="allow_referers" class="form-input" rows="3" maxlength="2000"
                              placeholder="{{ __('apikeys.referers_ph') }}">{{ old('allow_referers') }}</textarea>
                    <div class="form-hint">{{ __('apikeys.referers_hint') }}</div>
                    @error('allow_referers')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                @else
                    <textarea class="form-input" rows="{{ max(count($referers), 1) }}" readonly>{{ $referers ? implode("\n", $referers) : '—' }}</textarea>
                @endif
            </div>

            <div class="form-field">
                <label class="form-label-sm">
                    {{ __('apikeys.budget') }} <span style="font-weight:600;color:var(--faint);">{{ __('apikeys.budget_month') }}</span>
                </label>
                @include('admin.partials.amount-slider', [
                    'name'    => 'budget_usd',
                    'value'   => $budget?->limit_usd,
                    'max'     => 300,
                    'step'    => 5,
                    'prefix'  => '$',
                    'suffix'  => __('ui.per_month'),
                    'zero'    => __('ui.no_limit'),
                    'presets' => [25, 50, 100, 170],
                ])
                <div class="form-hint">{{ __('apikeys.budget_hint') }}</div>
                @error('budget_usd')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('apikeys.expiry') }}<span class="req">*</span></label>
                <div class="exp-row" id="expRow">
                    <label style="cursor:pointer;">
                        <input type="radio" name="expiry_mode" value="never" class="d-none" @checked($mode === 'never')>
                        <span class="exp-chip">{{ __('apikeys.exp_never') }}</span>
                    </label>

                    @foreach($presets as $days)
                        <label style="cursor:pointer;">
                            <input type="radio" name="expiry_mode" value="preset" class="d-none"
                                   data-preset="{{ $days }}"
                                   @checked($mode === 'preset' && (int) old('preset_days') === $days)>
                            <span class="exp-chip">{{ __('apikeys.exp_days', ['count' => $days]) }}</span>
                        </label>
                    @endforeach

                    <label style="cursor:pointer;">
                        <input type="radio" name="expiry_mode" value="date" class="d-none" @checked($mode === 'date')>
                        <span class="exp-chip"><i class="bi bi-calendar3"></i> {{ __('apikeys.exp_custom') }}</span>
                    </label>
                </div>

                <input type="hidden" name="preset_days" id="presetDays" value="{{ old('preset_days') }}">

                <div class="exp-date" id="expDate" @unless($mode === 'date') hidden @endunless>
                    @include('admin.partials.date-picker', [
                        'name'  => 'expire_date',
                        'value' => $expireValue,
                        'min'   => now()->addDay(),
                    ])
                    <div class="form-hint">{{ __('apikeys.exp_hint') }}</div>
                </div>

                @error('expiry_mode')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                @error('expire_date')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                @error('preset_days')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

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
                        <span class="v">{{ $key['create_time'] ? \Carbon\Carbon::parse($key['create_time'])->translatedFormat('d M Y H:i') : '—' }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.expires') }}</span>
                        <span class="v">{{ $key['expire_time'] ? \Carbon\Carbon::parse($key['expire_time'])->translatedFormat('d M Y H:i') : __('apikeys.never') }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.account') }}</span>
                        <span class="v">{{ $account?->name ?? '.env' }}</span>
                    </div>
                    <div class="kv">
                        <span class="k">{{ __('apikeys.region') }}</span>
                        <span class="v">{{ $region }}</span>
                    </div>

                    <a href="{{ route('admin.api-keys.usage', ['keyName' => $keyName, 'account' => $account?->id]) }}"
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

@push('scripts')
<script>
    (function () {
        const row = document.getElementById('expRow');
        if (!row) return;

        const dateBox = document.getElementById('expDate');
        const presetInput = document.getElementById('presetDays');

        function sync() {
            const picked = row.querySelector('input[name="expiry_mode"]:checked');
            if (!picked) return;

            dateBox.hidden = picked.value !== 'date';
            // preset_days dikirim lewat hidden input — tiap chip preset punya jumlah harinya sendiri.
            presetInput.value = picked.value === 'preset' ? (picked.dataset.preset ?? '') : '';
        }

        row.addEventListener('change', sync);
        sync();
    })();

    // ---------- Pilihan aksi ----------
    (function () {
        const groups = document.querySelectorAll('.svc-group');
        if (!groups.length) return;

        groups.forEach((group) => {
            const all = group.querySelector('input[data-all]');
            const singles = [...group.querySelectorAll('input[type="checkbox"]:not([data-all])')];
            if (!all || all.disabled) return;

            const label = group.querySelector('[data-picked]');
            const allLabel = all.parentElement.textContent.trim();

            function paint() {
                // Saat "Semua" dipilih, aksi satuan tidak perlu ikut dikirim.
                singles.forEach((box) => {
                    box.disabled = all.checked;
                    box.parentElement.classList.toggle('locked', all.checked);
                    if (all.checked) box.checked = false;
                });

                const picked = singles.filter((b) => b.checked).length;
                group.classList.toggle('on', all.checked || picked > 0);
                label.textContent = all.checked ? allLabel : (picked ? picked + '/' + singles.length : '—');
            }

            group.addEventListener('change', paint);
            paint();
        });
    })();
</script>
@endpush
