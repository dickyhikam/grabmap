@extends('layouts.admin-v2')

@section('title', __('profile.title'))

@push('styles')
    .pf-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1000px) { .pf-grid { grid-template-columns: 1fr; } }
    .pf-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

    /* ---------- Kartu identitas ---------- */
    .who {
        display: flex; align-items: center; gap: 15px;
        background: var(--surface); border-radius: 18px;
        padding: 16px; margin-bottom: 20px;
    }
    .who-avatar {
        width: 58px; height: 58px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem; font-weight: 700;
        text-transform: uppercase; flex-shrink: 0;
        background: linear-gradient(140deg, var(--green), var(--green-deep));
    }
    .who-avatar.is-admin { background: linear-gradient(140deg, #8b5cf6, #6d28d9); }
    .who-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 1.1rem; letter-spacing: -0.02em;
    }
    .who-mail { font-size: 0.78rem; color: var(--muted); word-break: break-all; }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok   { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.warn { background: var(--warn-soft); color: var(--warn-fg); }
    .pill-badge.plain { background: var(--card); color: var(--muted); }

    /* ---------- Formulir ---------- */
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
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-input[readonly] { opacity: 0.72; cursor: not-allowed; }
    .form-input.has-eye { padding-right: 44px; }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    .pw-rules { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 9px; }
    .pw-rule {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.68rem; font-weight: 600;
        padding: 4px 10px; border-radius: 999px;
        background: var(--surface); color: var(--muted);
        transition: background 0.18s, color 0.18s;
    }
    .pw-rule.ok { background: var(--green-soft); color: var(--green-text); }

    .btn-row { justify-content: flex-end; margin-top: 4px; }

    /* ---------- Sisi kanan ---------- */
    .kv { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 0.8rem; }
    .kv:last-child { border-bottom: none; }
    .kv .k { color: var(--muted); white-space: nowrap; }
    .kv .v { font-weight: 600; text-align: right; }

    .perm-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .perm {
        font-size: 0.69rem; font-weight: 600;
        background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 5px 11px;
        animation: permIn 0.28s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    @keyframes permIn {
        from { opacity: 0; transform: translateY(5px); }
        to   { opacity: 1; transform: none; }
    }

    .act-scroll {
        overflow-y: auto; max-height: 340px;
        margin-right: -6px; padding-right: 6px;
        scrollbar-width: thin; scrollbar-color: var(--line) transparent;
    }
    .act-scroll::-webkit-scrollbar { width: 7px; }
    .act-scroll::-webkit-scrollbar-thumb { background: var(--line); border-radius: 8px; }

    .act-item {
        display: flex; gap: 11px; padding: 10px 0;
        border-bottom: 1px solid var(--line);
        animation: permIn 0.3s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .act-item:last-child { border-bottom: none; }
    .act-dot {
        width: 32px; height: 32px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 0.78rem;
    }
    .tone-ok     { background: var(--green-soft); color: var(--green-text); }
    .tone-bad    { background: var(--danger-soft); color: var(--danger-fg); }
    .tone-warn   { background: var(--warn-soft); color: var(--warn-fg); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-plain  { background: var(--surface); color: var(--muted); }
    .act-title { font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .act-meta { font-size: 0.69rem; color: var(--muted); margin-top: 2px; }
@endpush

@section('content')
@php
    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<div class="q-page-head">
    <div>
        <h1 class="q-title">{{ __('profile.title') }}</h1>
        <div class="q-card-sub" style="margin-top:4px;">{{ __('profile.subtitle') }}</div>
    </div>
</div>

<div class="pf-grid">
    {{-- ===================== Kiri: yang bisa diubah ===================== --}}
    <div class="pf-col">
        <div class="q-card" style="padding:24px;">
            <div class="who">
                <div class="who-avatar {{ $user->isAdmin() ? 'is-admin' : '' }}">{{ $initials }}</div>
                <div style="min-width:0;">
                    <div class="who-name">{{ $user->name }}</div>
                    <div class="who-mail">{{ $user->email }}</div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="pill-badge plain">{{ $user->roleName() }}</span>
                        @if($user->hasVerifiedEmail())
                            <span class="pill-badge ok"><i class="bi bi-patch-check-fill"></i> {{ __('profile.verified') }}</span>
                        @else
                            <span class="pill-badge warn"><i class="bi bi-exclamation-circle-fill"></i> {{ __('profile.unverified') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.profile.update') }}" data-validate>
                @csrf
                @method('PUT')

                <div class="form-field">
                    <label class="form-label-sm">{{ __('profile.name') }}<span class="req">*</span></label>
                    <input type="text" name="name" maxlength="255" required
                           class="form-input @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}">
                    @error('name')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('profile.email') }}</label>
                    <input type="email" class="form-input" value="{{ $user->email }}" readonly>
                    <div class="form-hint">{{ __('profile.email_hint') }}</div>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-solid"><i class="bi bi-check-lg"></i> {{ __('profile.save') }}</button>
                </div>
            </form>
        </div>

        {{-- Ganti kata sandi --}}
        <div class="q-card" style="padding:24px;">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-shield-lock-fill"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('profile.password') }}</div>
                        <div class="q-card-sub">{{ __('profile.password_sub') }}</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.profile.password') }}" id="pwForm">
                @csrf
                @method('PUT')

                <div class="form-field">
                    <label class="form-label-sm">{{ __('profile.current') }}<span class="req">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="current_password" id="pwCurrent" autocomplete="current-password"
                               class="form-input has-eye @error('current_password') is-invalid @enderror"
                               placeholder="{{ __('profile.current_ph') }}">
                        <button type="button" class="pw-eye" data-toggle-pw="pwCurrent" tabindex="-1"><i class="bi bi-eye"></i></button>
                    </div>
                    @error('current_password')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('profile.new') }}<span class="req">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="pwNew" autocomplete="new-password"
                               class="form-input has-eye @error('password') is-invalid @enderror"
                               placeholder="{{ __('profile.new_ph') }}">
                        <button type="button" class="pw-eye" data-toggle-pw="pwNew" tabindex="-1"><i class="bi bi-eye"></i></button>
                    </div>

                    <div class="pw-rules" id="pwRules">
                        <span class="pw-rule" data-rule="min"><i class="bi bi-circle"></i> {{ __('profile.rule_min') }}</span>
                        <span class="pw-rule" data-rule="letter"><i class="bi bi-circle"></i> {{ __('profile.rule_letter') }}</span>
                        <span class="pw-rule" data-rule="number"><i class="bi bi-circle"></i> {{ __('profile.rule_number') }}</span>
                    </div>

                    @error('password')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('profile.repeat') }}<span class="req">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="password_confirmation" id="pwConfirm" autocomplete="new-password"
                               class="form-input has-eye" placeholder="{{ __('profile.repeat_ph') }}">
                        <button type="button" class="pw-eye" data-toggle-pw="pwConfirm" tabindex="-1"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-solid"><i class="bi bi-key"></i> {{ __('profile.change') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Kanan: informasi ===================== --}}
    <div class="pf-col">
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('profile.account') }}</div>
                        <div class="q-card-sub">{{ __('profile.account_sub') }}</div>
                    </div>
                </div>
            </div>

            <div class="kv">
                <span class="k">{{ __('profile.role') }}</span>
                <span class="v">{{ $user->roleName() }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('profile.status') }}</span>
                <span class="v" style="color:{{ $user->isActive() ? 'var(--green-text)' : 'var(--warn-fg)' }};">
                    {{ $user->isActive() ? __('profile.active') : __('profile.inactive') }}
                </span>
            </div>
            <div class="kv">
                <span class="k">{{ __('profile.joined') }}</span>
                <span class="v">{{ $user->created_at?->translatedFormat('d M Y') ?? __('profile.never') }}</span>
            </div>
            <div class="kv">
                <span class="k">{{ __('profile.last_login') }}</span>
                <span class="v">{{ $lastLogin?->created_at?->translatedFormat('d M Y H:i') ?? __('profile.never') }}</span>
            </div>
        </div>

        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('profile.access') }}</div>
                        <div class="q-card-sub">{{ __('profile.access_sub') }}</div>
                    </div>
                </div>
            </div>

            @if($permissions)
                <div class="perm-list">
                    @foreach($permissions as $label)
                        <span class="perm" style="animation-delay: {{ $loop->index * 24 }}ms;">{{ $label }}</span>
                    @endforeach
                </div>
            @else
                <div class="q-empty" style="padding:18px 10px;">
                    <i class="bi bi-shield-slash"></i>{{ __('profile.access_none') }}
                </div>
            @endif
        </div>

        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('profile.activity') }}</div>
                        <div class="q-card-sub">{{ __('profile.activity_sub') }}</div>
                    </div>
                </div>
            </div>

            @if($recentLogs->count())
                <div class="act-scroll">
                    @foreach($recentLogs as $log)
                        @php
                            $tone = match($log->action) {
                                'login'               => $log->status === 'success' ? 'tone-ok' : 'tone-bad',
                                'password_change',
                                'reset_password'      => 'tone-violet',
                                'email_verification'  => 'tone-violet',
                                'resend_verification',
                                'forgot_password'     => 'tone-warn',
                                default               => 'tone-plain',
                            };
                            $icon = match($log->action) {
                                'login'               => 'bi-box-arrow-in-right',
                                'logout'              => 'bi-box-arrow-left',
                                'register'            => 'bi-person-plus-fill',
                                'password_change',
                                'reset_password'      => 'bi-key-fill',
                                'forgot_password'     => 'bi-envelope-arrow-up',
                                'email_verification'  => 'bi-patch-check-fill',
                                default               => 'bi-activity',
                            };
                        @endphp
                        <div class="act-item" style="animation-delay: {{ $loop->index * 26 }}ms;">
                            <div class="act-dot {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                            <div style="min-width:0;">
                                <div class="act-title">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                    @if($log->status === 'fail')
                                        <span class="pill-badge" style="background:var(--danger-soft);color:var(--danger-fg);">{{ __('profile.failed') }}</span>
                                    @endif
                                </div>
                                <div class="act-meta">
                                    {{ $log->created_at?->diffForHumans() }}
                                    @if($log->ip_address) · {{ $log->ip_address }} @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="q-empty" style="padding:18px 10px;">
                    <i class="bi bi-clock-history"></i>{{ __('profile.activity_empty') }}
                </div>
            @endif
        </div>
    </div>
</div>

@include('admin.partials.form-validate')
@endsection

@push('scripts')
@php
    $pwMsg = [
        'required' => __('profile.err_required'),
        'weak'     => __('profile.err_weak'),
        'match'    => __('profile.err_match'),
    ];
@endphp
<script>
    // Formulir kata sandi divalidasi sendiri: aturannya saling terkait (ulangan harus
    // cocok, syarat kekuatan dari Password::min(8)->letters()->numbers()), jadi tidak
    // cukup dengan atribut seperti formulir lain.
    (function () {
        const form = document.getElementById('pwForm');
        if (!form) return;

        const MSG = @json($pwMsg);
        const current = document.getElementById('pwCurrent');
        const pw = document.getElementById('pwNew');
        const confirm = document.getElementById('pwConfirm');
        const rules = document.getElementById('pwRules');

        function box(el) { return el.closest('.form-field'); }

        function setError(el, message) {
            clearError(el);
            el.classList.add('is-invalid');
            const node = document.createElement('div');
            node.className = 'form-error';
            node.dataset.live = '1';
            node.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
            node.querySelector('span').textContent = message;
            box(el).appendChild(node);
        }

        function clearError(el) {
            el.classList.remove('is-invalid');
            box(el)?.querySelectorAll('.form-error[data-live]').forEach((n) => n.remove());
        }

        const checks = (value) => ({
            min: value.length >= 8,
            letter: /[a-zA-Z]/.test(value),
            number: /[0-9]/.test(value),
        });

        function paintRules() {
            const state = checks(pw.value);
            rules.querySelectorAll('.pw-rule').forEach((chip) => {
                const ok = state[chip.dataset.rule];
                chip.classList.toggle('ok', ok);
                chip.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
        }

        function checkCurrent() {
            if (!current.value) { setError(current, MSG.required); return false; }
            clearError(current); return true;
        }

        function checkNew() {
            if (!pw.value) { setError(pw, MSG.required); return false; }
            const state = checks(pw.value);
            if (!state.min || !state.letter || !state.number) { setError(pw, MSG.weak); return false; }
            clearError(pw); return true;
        }

        function checkConfirm() {
            if (pw.value !== confirm.value) { setError(confirm, MSG.match); return false; }
            clearError(confirm); return true;
        }

        [[current, checkCurrent], [pw, checkNew], [confirm, checkConfirm]].forEach(([el, check]) => {
            el.addEventListener('blur', check);
            el.addEventListener('input', () => { if (el.classList.contains('is-invalid')) check(); });
        });

        pw.addEventListener('input', () => {
            paintRules();
            if (confirm.classList.contains('is-invalid')) checkConfirm();
        });

        form.addEventListener('submit', (e) => {
            const ok = [checkCurrent(), checkNew(), checkConfirm()].every(Boolean);
            if (ok) return;

            e.preventDefault();
            const first = form.querySelector('.form-input.is-invalid');
            first?.focus();
            first?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });

        paintRules();
    })();
</script>
@endpush
