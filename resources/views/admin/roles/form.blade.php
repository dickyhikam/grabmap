@extends('layouts.admin-v2')

@section('title', $role ? __('roles.form_edit') : __('roles.form_add'))

@push('styles')
    .role-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr);
        gap: 16px;
        align-items: start;
    }
    @media (max-width: 1000px) { .role-form-grid { grid-template-columns: 1fr; } }

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    .form-field { margin-bottom: 16px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 500; text-transform: none; }
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    textarea.form-input { height: auto; padding: 12px 14px; resize: vertical; min-height: 84px; line-height: 1.5; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* Pilihan warna badge */
    .color-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .color-dot {
        width: 34px; height: 34px; border-radius: 50%;
        border: 2px solid transparent; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 0.7rem;
        transition: transform 0.15s, border-color 0.15s;
    }
    .color-dot:hover { transform: scale(1.08); }
    input:checked + .color-dot { border-color: var(--ink); }
    input:checked + .color-dot i { opacity: 1; }
    .color-dot i { opacity: 0; transition: opacity 0.15s; }
    .c-green  { background: #00B14F; }
    .c-violet { background: #7c3aed; }
    .c-blue   { background: #0ea5e9; }
    .c-amber  { background: #f59e0b; }
    .c-rose   { background: #e11d48; }
    .c-slate  { background: #64748b; }

    /* Daftar izin */
    .perm-group { margin-bottom: 18px; }
    .perm-group:last-child { margin-bottom: 0; }
    .perm-head {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em;
        text-transform: uppercase; color: var(--muted);
        padding-bottom: 8px; margin-bottom: 6px;
        border-bottom: 1px solid var(--line);
    }
    .perm-item {
        display: flex; align-items: center; gap: 11px;
        padding: 10px 12px; border-radius: 13px;
        cursor: pointer; transition: background 0.15s;
        animation: permIn 0.26s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    @keyframes permIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }
    .perm-item:hover { background: var(--surface); }
    .perm-item input { accent-color: var(--green); width: 17px; height: 17px; flex-shrink: 0; }
    .perm-label { font-size: 0.83rem; font-weight: 500; }
    .perm-key { font-size: 0.66rem; color: var(--faint); font-family: ui-monospace, monospace; }
    .perm-item.locked { opacity: 0.55; cursor: default; }

    .mini-btn {
        border: none; background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 4px 12px;
        font-size: 0.66rem; font-weight: 700; cursor: pointer; white-space: nowrap;
        transition: background 0.15s, color 0.15s;
    }
    .mini-btn:hover { background: var(--green-soft); color: var(--green-text); }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 12px 14px; margin-bottom: 16px;
        font-size: 0.74rem; line-height: 1.5;
        background: var(--surface); color: var(--muted);
    }
    .note.info { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }

    .btn-row { justify-content: flex-end; }
@endpush

@section('content')
@php
    $isNew    = $role === null;
    $granted  = old('permissions', $role?->permissions ?? []);
    $locked   = $role?->isFullAccess() ?? false;   // role admin: izin tidak bisa diubah
    $catalog  = \App\Models\Role::catalog();
    // Ambil seluruh peta label sekali; __('...keys.dashboard.view') tidak bisa dipakai
    // karena titik di dalam kunci dibaca Laravel sebagai jalur array bersarang.
    $permLabels = __('permissions.keys');
@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.roles.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('roles.back') }}
        </a>
        <h1 class="q-title">
            @if($isNew)
                {{ __('roles.form_add') }} <span class="soft">{{ __('roles.form_add_word') }}</span>
            @else
                {{ __('roles.form_edit') }} <span class="soft">{{ $role->name }}</span>
            @endif
        </h1>
    </div>
</div>

<form method="POST" id="roleForm" data-validate
      action="{{ $isNew ? route('admin.roles.store') : route('admin.roles.update', $role) }}">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="role-form-grid">
        {{-- ---------- Identitas role ---------- --}}
        <div class="q-card" style="padding:24px;">
            @if($role?->is_system)
                <div class="note"><i class="bi bi-lock-fill"></i><span>{{ __('roles.system_note') }}</span></div>
            @endif

            <div class="form-field">
                <label class="form-label-sm">{{ __('roles.name') }}<span class="req">*</span></label>
                <input type="text" name="name" class="form-input @error('name') is-invalid @enderror"
                       placeholder="{{ __('roles.name_ph') }}" required maxlength="60"
                       value="{{ old('name', $role->name ?? '') }}">
                @error('name')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('roles.slug') }} <span class="opt">{{ __('users.optional') }}</span></label>
                <input type="text" name="slug" class="form-input @error('slug') is-invalid @enderror"
                       placeholder="finance" maxlength="60" {{ $locked ? 'readonly' : '' }}
                       @unless($locked) data-v-pattern="^[A-Za-z0-9_-]+$" data-v-msg="{{ __('roles.err_slug') }}" @endunless
                       value="{{ old('slug', $role->slug ?? '') }}">
                <div class="form-hint">{{ __('roles.slug_hint') }}</div>
                @error('slug')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('roles.description') }}</label>
                <textarea name="description" class="form-input" rows="3" maxlength="255"
                          placeholder="{{ __('roles.description_ph') }}">{{ old('description', $role->description ?? '') }}</textarea>
                @error('description')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('roles.color') }}</label>
                <div class="color-row">
                    @foreach($colors as $color)
                        <label style="cursor:pointer;display:inline-flex;">
                            <input type="radio" name="color" value="{{ $color }}" class="d-none"
                                   @checked(old('color', $role->color ?? 'green') === $color)>
                            <span class="color-dot c-{{ $color }}"><i class="bi bi-check-lg"></i></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ---------- Izin ---------- --}}
        <div class="q-card" style="padding:24px;">
            <div class="q-card-head">
                <div>
                    <div class="q-card-title">{{ __('roles.permissions') }}</div>
                    <div class="q-card-sub">{{ __('roles.perm_hint') }}</div>
                </div>
                @unless($locked)
                    <div class="d-flex gap-2">
                        <button type="button" class="mini-btn" data-perm-all>{{ __('roles.select_all') }}</button>
                        <button type="button" class="mini-btn" data-perm-none>{{ __('roles.clear_all') }}</button>
                    </div>
                @endunless
            </div>

            @if($locked)
                <div class="note info"><i class="bi bi-shield-fill-check"></i><span>{{ __('roles.admin_note') }}</span></div>
            @endif

            @php $i = 0; @endphp
            @foreach($catalog as $group => $keys)
                <div class="perm-group">
                    <div class="perm-head">
                        <span>{{ __('permissions.groups.' . $group) }}</span>
                        @unless($locked)
                            <button type="button" class="mini-btn" data-perm-group="{{ $group }}">{{ __('roles.toggle_group') }}</button>
                        @endunless
                    </div>

                    @foreach($keys as $key)
                        <label class="perm-item {{ $locked ? 'locked' : '' }}" style="animation-delay: {{ ($i++) * 18 }}ms;">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" data-group="{{ $group }}"
                                   @checked($locked || in_array($key, $granted, true))
                                   {{ $locked ? 'disabled' : '' }}>
                            <span style="min-width:0;">
                                <span class="perm-label d-block">{{ $permLabels[$key] ?? $key }}</span>
                                <span class="perm-key">{{ $key }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endforeach

            <div class="btn-row" style="margin-top:20px;">
                <a href="{{ route('admin.roles.index') }}" class="btn-soft"><i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}</a>
                <button type="submit" class="btn-solid">
                    <i class="bi bi-check-lg"></i> {{ $isNew ? __('roles.create') : __('roles.save') }}
                </button>
            </div>
        </div>
    </div>
</form>
@include('admin.partials.form-validate')
@endsection

@push('scripts')
<script>
    document.addEventListener('click', (e) => {
        const all = e.target.closest('[data-perm-all]');
        const none = e.target.closest('[data-perm-none]');

        if (all || none) {
            document.querySelectorAll('#roleForm input[name="permissions[]"]:not([disabled])')
                .forEach((box) => { box.checked = Boolean(all); });
            return;
        }

        // Toggle per grup: kalau belum semua tercentang, centang semua; kalau sudah, kosongkan.
        const group = e.target.closest('[data-perm-group]');
        if (!group) return;

        const boxes = document.querySelectorAll(
            '#roleForm input[name="permissions[]"][data-group="' + group.dataset.permGroup + '"]:not([disabled])'
        );
        const allChecked = [...boxes].every((box) => box.checked);
        boxes.forEach((box) => { box.checked = !allChecked; });
    });
</script>
@endpush
