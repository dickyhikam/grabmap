@extends('layouts.admin-v2')

@section('title', $user ? __('users.form_edit_title') : __('users.form_add_title'))

@push('styles')
    .edit-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    .edit-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

    /* Kolom kanan mengalir apa adanya. Trik "tinggi ikut formulir" dilepas sejak ada
       kartu akses — dengan tiga kartu, pembagian tingginya bikin isi saling terpotong.
       Sebagai gantinya tiap daftar panjang punya scroll sendiri. */
    .edit-side .q-card { display: flex; flex-direction: column; min-height: 0; }

    .act-scroll {
        overflow-y: auto;
        max-height: 300px;
        margin-right: -6px;
        padding-right: 6px;
        scrollbar-width: thin;
        scrollbar-color: var(--line) transparent;
    }
    .card-cred .act-scroll { max-height: 190px; }
    .act-scroll::-webkit-scrollbar { width: 7px; }
    .act-scroll::-webkit-scrollbar-thumb { background: var(--line); border-radius: 8px; }
    .act-scroll::-webkit-scrollbar-track { background: transparent; }

    @media (max-width: 1000px) {
        .edit-grid { grid-template-columns: 1fr; align-items: start; }
        .act-scroll, .card-cred .act-scroll { max-height: 380px; }
    }

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    /* ---------- Kartu identitas ---------- */
    .who-card {
        display: flex; align-items: center; gap: 14px;
        background: var(--surface); border-radius: 18px;
        padding: 16px; margin-bottom: 20px;
    }
    .who-avatar {
        width: 54px; height: 54px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.05rem; font-weight: 700;
        text-transform: uppercase; flex-shrink: 0;
    }
    .who-avatar.is-admin { background: linear-gradient(140deg, #8b5cf6, #6d28d9); }
    .who-avatar.is-user  { background: linear-gradient(140deg, var(--green), var(--green-deep)); }
    .who-name { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; letter-spacing: -0.02em; }
    .who-mail { font-size: 0.76rem; color: var(--muted); word-break: break-all; }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 3px 9px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.role-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pill-badge.role-green  { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.role-blue   { background: rgba(14, 165, 233, 0.16); color: #0284c7; }
    .pill-badge.role-amber  { background: var(--warn-soft); color: var(--warn-fg); }
    .pill-badge.role-rose   { background: var(--danger-soft); color: var(--danger-fg); }
    .pill-badge.role-slate  { background: var(--card); color: var(--muted); }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.warn  { background: var(--warn-soft); color: var(--warn-fg); }
    .pill-badge.plain { background: var(--card); color: var(--muted); }

    /* ---------- Form ---------- */
    .form-field { margin-bottom: 16px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 500; text-transform: none; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px 0 42px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.9rem; }
    .form-error {
        display: flex; align-items: flex-start; gap: 6px;
        font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px;
        animation: errIn 0.24s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    @keyframes errIn {
        from { opacity: 0; transform: translateY(-4px); }
        to   { opacity: 1; transform: none; }
    }

    /* Tanda wajib + keadaan tidak valid */
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-input.is-invalid:focus { border-color: var(--danger-fg); box-shadow: 0 0 0 4px var(--danger-soft); }

    /* Syarat kata sandi — berubah hijau begitu terpenuhi */
    .pw-rules { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 9px; }
    .pw-rule {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.68rem; font-weight: 600;
        padding: 4px 10px; border-radius: 999px;
        background: var(--surface); color: var(--muted);
        transition: background 0.2s, color 0.2s;
    }
    .pw-rule i { font-size: 0.6rem; }
    .pw-rule.ok { background: var(--green-soft); color: var(--green-text); }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    .role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 10px; }
    @media (max-width: 560px) { .role-grid { grid-template-columns: 1fr; } }
    .role-card {
        display: flex; align-items: center; gap: 12px;
        padding: 13px; border-radius: 16px;
        border: 1.5px solid var(--line);
        cursor: pointer; transition: all 0.18s;
    }
    .role-card:hover { border-color: var(--green); }
    .role-card .ic {
        width: 38px; height: 38px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
        background: var(--card); color: var(--muted);
    }
    .role-card .rt { font-weight: 700; font-size: 0.84rem; }
    .role-card .rd { font-size: 0.68rem; color: var(--muted); }
    input:checked + .role-card {
        border-color: var(--green);
        background: var(--green-soft);
    }
    input:checked + .role-card .ic { background: var(--green); color: #fff; }

    /* Gaya dasar .btn-soft / .btn-solid ada di layout; di sini hanya perataannya. */
    .btn-row { justify-content: flex-end; }

    .pw-magic {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        border: none; background: none; color: var(--muted); cursor: pointer;
        padding: 7px; border-radius: 9px; line-height: 1;
        transition: background 0.15s, color 0.15s;
    }
    .pw-magic:hover { background: var(--green-soft); color: var(--green-text); }

    .check-row { display: flex; gap: 10px; align-items: flex-start; font-size: 0.8rem; margin-bottom: 18px; }
    .check-row input { accent-color: var(--green); width: 17px; height: 17px; margin-top: 2px; flex-shrink: 0; }

    /* ---------- Kartu akses role ---------- */
    .access-pane[hidden] { display: none; }

    .access-head {
        display: flex; align-items: center; gap: 10px;
        padding-bottom: 12px; margin-bottom: 6px;
        border-bottom: 1px solid var(--line);
    }
    .access-head .nm { font-weight: 700; font-size: 0.88rem; }
    .access-head .sb { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }

    .access-group {
        font-size: 0.64rem; font-weight: 700; letter-spacing: 0.05em;
        text-transform: uppercase; color: var(--muted);
        margin: 12px 0 4px;
    }

    .access-item {
        display: flex; align-items: center; gap: 9px;
        padding: 5px 0; font-size: 0.79rem;
        animation: accessIn 0.24s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    @keyframes accessIn {
        from { opacity: 0; transform: translateX(6px); }
        to   { opacity: 1; transform: none; }
    }
    .access-item i { color: var(--green-text); font-size: 0.75rem; flex-shrink: 0; }

    .access-scroll { max-height: 320px; overflow-y: auto; scrollbar-width: thin; margin-right: -6px; padding-right: 6px; }
    .access-scroll::-webkit-scrollbar { width: 7px; }
    .access-scroll::-webkit-scrollbar-thumb { background: var(--line); border-radius: 8px; }

    /* ---------- Linimasa aktivitas ---------- */
    .act-item {
        display: flex; gap: 12px; padding: 11px 0;
        border-bottom: 1px solid var(--line);
        animation: actIn 0.3s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .act-item:last-child { border-bottom: none; }
    @keyframes actIn {
        from { opacity: 0; transform: translateX(8px); }
        to   { opacity: 1; transform: none; }
    }
    .act-dot {
        width: 34px; height: 34px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem; flex-shrink: 0;
    }
    .act-body { flex: 1; min-width: 0; }
    .act-title { font-weight: 600; font-size: 0.81rem; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .act-detail { font-size: 0.71rem; color: var(--danger-fg); margin-top: 3px; }
    .act-meta { font-size: 0.68rem; color: var(--muted); margin-top: 3px; }

    .tone-ok     { background: var(--green-soft); color: var(--green-text); }
    .tone-bad    { background: var(--danger-soft); color: var(--danger-fg); }
    .tone-warn   { background: var(--warn-soft); color: var(--warn-fg); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-plain  { background: var(--surface); color: var(--muted); }

    @media (prefers-reduced-motion: reduce) {
        .act-item { animation: none; }
    }
@endpush

@section('content')
@php
    // Satu view untuk dua mode: $user null = tambah, ada isinya = ubah.
    $isNew    = $user === null;
    // Semua panel akses dirender sekaligus lalu disembunyikan; JS cukup menukar mana
    // yang tampil, jadi isinya tetap benar walau JS mati (panel role terpilih ikut terbuka).
    $permLabels  = __('permissions.keys');
    $permCatalog = \App\Models\Role::catalog();
    $permTotal   = count(\App\Models\Role::allPermissionKeys());
    $selectedRoleUuid = old('role', $user->role?->uuid ?? $roles->firstWhere('slug', 'user')?->uuid);
    $initials = $isNew ? '' : collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.users.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('users.back') }}
        </a>
        <h1 class="q-title">
            @if($isNew)
                {{ __('users.form_add_title') }} <span class="soft">{{ __('users.form_add_word') }}</span>
            @else
                {{ __('users.form_edit_title') }} <span class="soft">{{ explode(' ', $user->name)[0] }}</span>
            @endif
        </h1>
    </div>
</div>

<div class="edit-grid">
    {{-- ===================== Formulir ===================== --}}
    <div class="edit-col">
        <div class="q-card" style="padding:24px;">
            @unless($isNew)
                <div class="who-card">
                    <div class="who-avatar {{ $user->isAdmin() ? 'is-admin' : 'is-user' }}">{{ $initials }}</div>
                    <div style="min-width:0;">
                        <div class="who-name">{{ $user->name }}</div>
                        <div class="who-mail">{{ $user->email }}</div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if($user->isAdmin())
                                <span class="pill-badge admin"><i class="bi bi-shield-fill-check"></i> {{ __('users.role_admin') }}</span>
                            @else
                                <span class="pill-badge user"><i class="bi bi-person"></i> {{ __('users.role_user') }}</span>
                            @endif

                            @if($user->hasVerifiedEmail())
                                <span class="pill-badge ok"><i class="bi bi-patch-check-fill"></i> {{ __('users.verified') }}</span>
                            @else
                                <span class="pill-badge warn"><i class="bi bi-exclamation-circle"></i> {{ __('ui.unverified') }}</span>
                            @endif

                            <span class="pill-badge plain">{{ __('users.joined', ['when' => $user->created_at?->diffForHumans()]) }}</span>
                        </div>
                    </div>
                </div>
            @endunless

            <form method="POST" id="userForm" data-mode="{{ $isNew ? 'create' : 'edit' }}"
                  action="{{ $isNew ? route('admin.users.store') : route('admin.users.update', $user) }}" novalidate>
                @csrf
                @unless($isNew) @method('PUT') @endunless

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('users.full_name') }}<span class="req" title="{{ __('users.required_mark') }}">*</span>
                    </label>
                    <div style="position:relative;">
                        <i class="bi bi-person-fill form-icon"></i>
                        <input type="text" name="name" id="fName" class="form-input @error('name') is-invalid @enderror" required
                               placeholder="{{ $isNew ? 'John Doe' : '' }}"
                               value="{{ old('name', $user->name ?? '') }}">
                    </div>
                    @error('name')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('users.email') }}<span class="req" title="{{ __('users.required_mark') }}">*</span>
                    </label>
                    <div style="position:relative;">
                        <i class="bi bi-envelope-fill form-icon"></i>
                        <input type="email" name="email" id="fEmail" class="form-input @error('email') is-invalid @enderror" required
                               placeholder="user@grabtaxi.com"
                               value="{{ old('email', $user->email ?? '') }}">
                    </div>
                    @if($isNew)<div class="form-hint">{{ __('users.email_hint') }}</div>@endif
                    @error('email')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('users.role') }}<span class="req" title="{{ __('users.required_mark') }}">*</span>
                    </label>
                    <div class="role-grid">
                        @foreach($roles as $r)
                            <label style="cursor:pointer;">
                                <input type="radio" name="role" value="{{ $r->uuid }}" class="d-none"
                                       @checked($selectedRoleUuid === $r->uuid)>
                                <div class="role-card">
                                    <span class="ic"><i class="bi bi-{{ $r->isFullAccess() ? 'shield-fill-check' : 'person' }}"></i></span>
                                    <span style="min-width:0;">
                                        <span class="rt d-block">{{ $r->name }}</span>
                                        <span class="rd">
                                            {{ $r->description ?: ($r->isFullAccess()
                                                ? __('roles.full_access')
                                                : __('roles.perm_count', ['count' => count($r->permissions ?? []), 'total' => count(\App\Models\Role::allPermissionKeys())])) }}
                                        </span>
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('role')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('users.password') }}@if($isNew)<span class="req" title="{{ __('users.required_mark') }}">*</span>@endif
                        @unless($isNew)<span class="opt">{{ __('users.password_optional') }}</span>@endunless
                    </label>
                    <div style="position:relative;">
                        <i class="bi bi-lock-fill form-icon"></i>
                        <input type="{{ $isNew ? 'text' : 'password' }}" name="password" id="pwNew"
                               class="form-input @error('password') is-invalid @enderror"
                               {{ $isNew ? 'required' : '' }} minlength="8"
                               placeholder="{{ $isNew ? __('users.password_ph_new') : __('users.password_ph_edit') }}"
                               autocomplete="new-password"
                               style="padding-right:{{ $isNew ? 82 : 46 }}px;"
                               value="{{ old('password') }}">
                        <button type="button" class="pw-eye {{ $isNew ? 'on' : '' }}" data-toggle-pw="pwNew"
                                aria-pressed="{{ $isNew ? 'true' : 'false' }}"
                                aria-label="{{ $isNew ? __('ui.hide_password') : __('ui.show_password') }}">
                            <i class="bi bi-eye{{ $isNew ? '-slash' : '' }}"></i>
                        </button>
                        @if($isNew)
                            <button type="button" class="pw-magic" data-gen="pwNew" title="{{ __('ui.generate') }}" style="right:42px;">
                                <i class="bi bi-magic"></i>
                            </button>
                        @endif
                    </div>
                    @error('password')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror

                    <div class="pw-rules" id="pwRules" hidden>
                        <span class="pw-rule" data-rule="min"><i class="bi bi-circle"></i> {{ __('users.pw_min') }}</span>
                        <span class="pw-rule" data-rule="letter"><i class="bi bi-circle"></i> {{ __('users.pw_letter') }}</span>
                        <span class="pw-rule" data-rule="number"><i class="bi bi-circle"></i> {{ __('users.pw_number') }}</span>
                    </div>
                </div>

                @if($isNew)
                    <div class="form-field">
                        <label class="form-label-sm">
                            {{ __('users.notify_email') }} <span class="opt">{{ __('users.optional') }}</span>
                        </label>
                        <div style="position:relative;">
                            <i class="bi bi-at form-icon"></i>
                            <input type="email" name="notify_email" class="form-input"
                                   placeholder="{{ __('users.notify_ph') }}" value="{{ old('notify_email') }}">
                        </div>
                        <div class="form-hint">
                            {{ __('users.notify_hint') }}
                        </div>
                        @error('notify_email')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>

                    <label class="check-row">
                        <input type="checkbox" name="auto_verify" value="1" @checked(old('auto_verify', '1'))>
                        <span>
                            <span style="font-weight:600;">{{ __('users.auto_verify') }}</span>
                            <span style="display:block;font-size:0.7rem;color:var(--muted);">
                                {{ __('users.auto_verify_desc') }}
                            </span>
                        </span>
                    </label>
                @else
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('users.password_repeat') }}</label>
                        <div style="position:relative;">
                            <i class="bi bi-lock-fill form-icon"></i>
                            <input type="password" name="password_confirmation" id="pwConfirm" class="form-input"
                                   placeholder="{{ __('users.password_repeat_ph') }}" autocomplete="new-password"
                                   style="padding-right:46px;">
                            <button type="button" class="pw-eye" data-toggle-pw="pwConfirm"
                                    aria-pressed="false" aria-label="{{ __('ui.show_password') }}">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <div class="btn-row">
                    <a href="{{ route('admin.users.index') }}" class="btn-soft">
                        <i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}
                    </a>
                    <button type="submit" class="btn-solid">
                        <i class="bi bi-{{ $isNew ? 'person-plus' : 'check-lg' }}"></i>
                        {{ $isNew ? __('users.create_btn') : __('ui.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== Sisi kanan ===================== --}}
    <div class="edit-col {{ $isNew ? '' : 'edit-side' }}">
        {{-- Akses yang dibawa role terpilih --}}
        <div class="q-card card-access">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-shield-lock"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('users.access_title') }}</div>
                        <div class="q-card-sub">{{ __('users.access_sub') }}</div>
                    </div>
                </div>
                @can('roles.view')
                    <a href="{{ route('admin.roles.index') }}" class="q-ghost-btn" title="{{ __('users.access_manage') }}">
                        <i class="bi bi-arrow-up-right"></i>
                    </a>
                @endcan
            </div>

            @foreach($roles as $r)
                @php $granted = $r->permissions ?? []; @endphp
                <div class="access-pane" data-access="{{ $r->uuid }}" @unless($selectedRoleUuid === $r->uuid) hidden @endunless>
                    <div class="access-head">
                        <span class="pill-badge role-{{ $r->color }}">
                            <i class="bi bi-{{ $r->isFullAccess() ? 'shield-fill-check' : 'shield' }}"></i> {{ $r->name }}
                        </span>
                        <div style="min-width:0;">
                            <div class="sb">
                                {{ $r->isFullAccess()
                                    ? __('roles.full_access')
                                    : __('roles.perm_count', ['count' => count($granted), 'total' => $permTotal]) }}
                            </div>
                        </div>
                    </div>

                    <div class="access-scroll">
                        @php $shown = 0; @endphp
                        @foreach($permCatalog as $group => $keys)
                            @php $visible = $r->isFullAccess() ? $keys : array_values(array_intersect($keys, $granted)); @endphp
                            @if(count($visible))
                                <div class="access-group">{{ __('permissions.groups.' . $group) }}</div>
                                @foreach($visible as $key)
                                    <div class="access-item" style="animation-delay: {{ ($shown++) * 16 }}ms;">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>{{ $permLabels[$key] ?? $key }}</span>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach

                        @if($shown === 0)
                            <div class="q-empty" style="padding:22px 10px;">
                                <i class="bi bi-shield-slash"></i>{{ __('users.access_none') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($isNew)
            <div class="q-card">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-info-circle"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('users.after_save') }}</div>
                            <div class="q-card-sub">{{ __('users.after_save_sub') }}</div>
                        </div>
                    </div>
                </div>

                @foreach([
                    ['ic' => 'bi-person-check-fill', 'tone' => 'tone-ok', 't' => __('users.step_active'), 'd' => __('users.step_active_d')],
                    ['ic' => 'bi-envelope-arrow-up-fill', 'tone' => 'tone-violet', 't' => __('users.step_email'), 'd' => __('users.step_email_d')],
                    ['ic' => 'bi-patch-check-fill', 'tone' => 'tone-warn', 't' => __('users.step_verify'), 'd' => __('users.step_verify_d')],
                ] as $step)
                    <div class="act-item" style="animation-delay: {{ $loop->index * 40 }}ms;">
                        <div class="act-dot {{ $step['tone'] }}"><i class="bi {{ $step['ic'] }}"></i></div>
                        <div class="act-body">
                            <div class="act-title">{{ $step['t'] }}</div>
                            <div class="act-meta" style="line-height:1.5;">{{ $step['d'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="q-card card-act">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('users.activity') }}</div>
                            <div class="q-card-sub">{{ __('users.activity_sub') }}</div>
                        </div>
                    </div>
                </div>

                @if($recentLogs->count())
                  <div class="act-scroll">
                    @foreach($recentLogs as $log)
                        @php
                            $tone = match($log->action) {
                                'login'               => $log->status === 'success' ? 'tone-ok' : 'tone-bad',
                                'register'            => 'tone-violet',
                                'email_verification'  => 'tone-violet',
                                'resend_verification' => 'tone-warn',
                                default               => 'tone-plain',
                            };
                            $icon = match($log->action) {
                                'login'               => 'bi-box-arrow-in-right',
                                'register'            => 'bi-person-plus-fill',
                                'logout'              => 'bi-box-arrow-left',
                                'email_verification'  => 'bi-patch-check-fill',
                                'resend_verification' => 'bi-envelope-arrow-up',
                                default               => 'bi-activity',
                            };
                        @endphp
                        <div class="act-item" style="animation-delay: {{ $loop->index * 30 }}ms;">
                            <div class="act-dot {{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                            <div class="act-body">
                                <div class="act-title">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                    @if($log->status === 'fail')
                                        <span class="pill-badge warn" style="background:var(--danger-soft);color:var(--danger-fg);">{{ __('users.failed') }}</span>
                                    @endif
                                </div>
                                @if($log->failed_reason)
                                    <div class="act-detail">{{ str_replace('_', ' ', $log->failed_reason) }}</div>
                                @endif
                                <div class="act-meta">
                                    {{ $log->created_at?->diffForHumans() }}
                                    @if($log->ip_address) · {{ $log->ip_address }} @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                  </div>
                @else
                    <div class="q-empty"><i class="bi bi-clock-history"></i>{{ __('users.activity_empty') }}</div>
                @endif
            </div>

            @if($credentialLogs->count())
                <div class="q-card card-cred">
                    <div class="q-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="q-icon-box"><i class="bi bi-send-fill"></i></div>
                            <div>
                                <div class="q-card-title">{{ __('users.cred_history') }}</div>
                                <div class="q-card-sub">{{ __('users.cred_history_sub') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="act-scroll">
                    @foreach($credentialLogs as $clog)
                        <div class="act-item" style="animation-delay: {{ $loop->index * 30 }}ms;">
                            <div class="act-dot {{ $clog->status === 'success' ? 'tone-violet' : 'tone-bad' }}">
                                <i class="bi {{ $clog->status === 'success' ? 'bi-send-check-fill' : 'bi-send-x-fill' }}"></i>
                            </div>
                            <div class="act-body">
                                <div class="act-title">
                                    {{ __('users.sent_to') }} <b>{{ $clog->sent_to_email }}</b>
                                    @if($clog->include_password)
                                        <span class="pill-badge warn">{{ __('users.with_password') }}</span>
                                    @endif
                                    @if($clog->status === 'fail')
                                        <span class="pill-badge warn" style="background:var(--danger-soft);color:var(--danger-fg);">{{ __('users.failed') }}</span>
                                    @endif
                                </div>
                                <div class="act-meta">
                                    {{ $clog->created_at?->diffForHumans() }} · {{ __('users.by') }} {{ $clog->sender?->name ?? __('users.system') }}
                                </div>
                                @if($clog->failed_reason)
                                    <div class="act-detail">{{ Str::limit($clog->failed_reason, 60) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
@php
    // Pesan validasi untuk sisi klien. Ini hanya mempercepat umpan balik —
    // aturan yang sesungguhnya tetap divalidasi di UserController.
    $vMsg = [
        'required'     => __('users.err_required'),
        'emailFormat'  => __('users.err_email_format'),
        'emailDomain'  => __('users.err_email_domain'),
        'pwWeak'       => __('users.err_pw_weak'),
        'pwMatch'      => __('users.err_pw_match'),
    ];
@endphp
<script>
    (function () {
        const form = document.getElementById('userForm');
        if (!form) return;

        const isNew = form.dataset.mode === 'create';
        const MSG = @json($vMsg);

        const pw = document.getElementById('pwNew');
        const pwConfirm = document.getElementById('pwConfirm');
        const rules = document.getElementById('pwRules');

        // ---------- Pembuat kata sandi acak (mode tambah) ----------
        document.addEventListener('click', (e) => {
            const gen = e.target.closest('[data-gen]');
            if (!gen) return;

            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
            const pick = new Uint32Array(12);
            crypto.getRandomValues(pick);

            let out = '';
            for (let i = 0; i < pick.length; i++) out += chars[pick[i] % chars.length];

            const input = document.getElementById(gen.dataset.gen);
            input.value = out.slice(0, 10) + 'A3';   // dijamin ada huruf + angka
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // ---------- Kartu akses mengikuti role yang dipilih ----------
        document.querySelectorAll('#userForm input[name="role"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                document.querySelectorAll('[data-access]').forEach((pane) => {
                    pane.hidden = pane.dataset.access !== radio.value;
                });
            });
        });

        // ---------- Menandai error di bawah field ----------
        function setError(input, message) {
            clearError(input);
            input.classList.add('is-invalid');

            const box = document.createElement('div');
            box.className = 'form-error';
            box.dataset.live = '1';
            box.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
            box.querySelector('span').textContent = message;
            input.closest('.form-field').appendChild(box);
        }

        function clearError(input) {
            input.classList.remove('is-invalid');
            input.closest('.form-field')?.querySelectorAll('.form-error[data-live]').forEach(el => el.remove());
        }

        // ---------- Aturan kata sandi, mengikuti Password::min(8)->letters()->numbers() ----------
        function pwChecks(value) {
            return { min: value.length >= 8, letter: /[a-zA-Z]/.test(value), number: /[0-9]/.test(value) };
        }

        function paintRules() {
            if (!rules) return;
            const value = pw.value;
            // Di mode ubah, kolom sandi boleh kosong — daftar syarat baru muncul saat diisi.
            rules.hidden = !isNew && value === '';

            const checks = pwChecks(value);
            rules.querySelectorAll('.pw-rule').forEach((chip) => {
                const ok = checks[chip.dataset.rule];
                chip.classList.toggle('ok', ok);
                chip.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
        }

        // ---------- Validasi per field ----------
        function checkName() {
            const el = document.getElementById('fName');
            if (!el.value.trim()) { setError(el, MSG.required); return false; }
            clearError(el); return true;
        }

        function checkEmail() {
            const el = document.getElementById('fEmail');
            const value = el.value.trim();

            if (!value) { setError(el, MSG.required); return false; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) { setError(el, MSG.emailFormat); return false; }
            // Domain hanya dipaksa saat membuat akun baru — sama seperti aturan di server.
            if (isNew && !value.toLowerCase().endsWith('@grabtaxi.com')) { setError(el, MSG.emailDomain); return false; }

            clearError(el); return true;
        }

        function checkPassword() {
            const value = pw.value;
            if (!isNew && value === '') { clearError(pw); return true; }
            if (!value) { setError(pw, MSG.required); return false; }

            const checks = pwChecks(value);
            if (!checks.min || !checks.letter || !checks.number) { setError(pw, MSG.pwWeak); return false; }

            clearError(pw); return true;
        }

        function checkConfirm() {
            if (!pwConfirm) return true;
            if (pw.value === '' && pwConfirm.value === '') { clearError(pwConfirm); return true; }
            if (pw.value !== pwConfirm.value) { setError(pwConfirm, MSG.pwMatch); return false; }
            clearError(pwConfirm); return true;
        }

        // ---------- Pemasangan ----------
        // Cek saat blur; setelah field pernah salah, cek ulang tiap ketikan supaya
        // pesannya hilang begitu diperbaiki.
        [['fName', checkName], ['fEmail', checkEmail]].forEach(([id, check]) => {
            const el = document.getElementById(id);
            el.addEventListener('blur', check);
            el.addEventListener('input', () => { if (el.classList.contains('is-invalid')) check(); });
        });

        pw.addEventListener('input', () => {
            paintRules();
            if (pw.classList.contains('is-invalid')) checkPassword();
            if (pwConfirm && pwConfirm.classList.contains('is-invalid')) checkConfirm();
        });
        pw.addEventListener('blur', checkPassword);

        if (pwConfirm) {
            pwConfirm.addEventListener('blur', checkConfirm);
            pwConfirm.addEventListener('input', () => { if (pwConfirm.classList.contains('is-invalid')) checkConfirm(); });
        }

        form.addEventListener('submit', (e) => {
            // Semua dicek supaya seluruh error tampil sekaligus, bukan satu per satu.
            const ok = [checkName(), checkEmail(), checkPassword(), checkConfirm()].every(Boolean);
            if (ok) return;

            e.preventDefault();
            const first = form.querySelector('.form-input.is-invalid');
            first?.focus();
            first?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });

        paintRules();

        // Error dari server: fokuskan yang pertama supaya tidak perlu dicari.
        const serverError = form.querySelector('.form-input.is-invalid');
        if (serverError) serverError.focus({ preventScroll: true });
    })();
</script>
@endpush
