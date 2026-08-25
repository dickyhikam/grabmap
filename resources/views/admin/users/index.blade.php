@extends('layouts.admin-v2')

@section('title', __('users.title'))

@push('styles')
    /* ---------- Ringkasan ---------- */
    .stat-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    @media (max-width: 900px) { .stat-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

    .stat-tile { display: flex; align-items: center; gap: 14px; }
    .stat-tile .ic {
        width: 44px; height: 44px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .stat-tile .val { font-size: 1.45rem; }
    .stat-tile .lbl {
        font-size: 0.7rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: 0.05em;
    }

    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-ok     { background: var(--green-soft); color: var(--green-text); }
    .tone-bad    { background: var(--danger-soft); color: var(--danger-fg); }
    .tone-warn   { background: var(--warn-soft); color: var(--warn-fg); }

    /* ---------- Filter ---------- */
    .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .field { position: relative; display: flex; align-items: center; }
    /* z-index perlu: .gm-select juga positioned dan datang setelah ikon ini di DOM. */
    .field > i.lead {
        position: absolute; left: 14px; color: var(--muted);
        font-size: 0.85rem; pointer-events: none; z-index: 2;
    }

    .input, .select {
        height: 42px;
        border-radius: 999px;
        border: 1px solid var(--line);
        background: var(--surface);
        color: var(--ink);
        font-size: 0.82rem;
        padding: 0 16px 0 38px;
        outline: none;
        width: 100%;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .select { appearance: none; padding-right: 34px; cursor: pointer; }
    .input:focus, .select:focus {
        border-color: var(--green);
        background: var(--card);
        box-shadow: 0 0 0 4px var(--green-soft);
    }
    .input::placeholder { color: var(--muted); }

    .filter-search { flex: 1; min-width: 210px; }
    .filter-sel { width: 172px; }
    .filter-rows { width: 148px; }

    /* Header tabel yang bisa diurutkan */
    .th-sort {
        display: inline-flex; align-items: center; gap: 6px;
        color: inherit; text-decoration: none;
        transition: color 0.15s;
    }
    .th-sort i { font-size: 0.72rem; opacity: 0.45; transition: opacity 0.15s, color 0.15s; }
    .th-sort:hover { color: var(--ink); }
    .th-sort:hover i { opacity: 0.9; }
    .th-sort.on { color: var(--green-text); }
    .th-sort.on i { opacity: 1; color: var(--green-text); }

    /* ---------- Tabel ---------- */
    .u-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.83rem; }
    .u-table th {
        font-size: 0.68rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: 0.05em;
        text-align: left; padding: 0 16px 12px; white-space: nowrap;
    }
    .u-table td { padding: 12px 16px; border-top: 1px solid var(--line); vertical-align: middle; }
    .u-table tbody tr { animation: rowIn 0.32s cubic-bezier(0.34, 1.4, 0.5, 1) backwards; }
    .u-table tbody tr:hover td { background: var(--surface); }
    .u-table tbody tr td:first-child { border-radius: 14px 0 0 14px; }
    .u-table tbody tr td:last-child { border-radius: 0 14px 14px 0; }

    @keyframes rowIn {
        from { opacity: 0; transform: translateY(9px); }
        to   { opacity: 1; transform: none; }
    }

    .u-avatar {
        width: 38px; height: 38px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 0.76rem; font-weight: 700;
        text-transform: uppercase; flex-shrink: 0;
    }
    .u-avatar.is-admin { background: linear-gradient(140deg, #8b5cf6, #6d28d9); }
    .u-avatar.is-user  { background: linear-gradient(140deg, var(--green), var(--green-deep)); }

    .u-name { font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .u-mail { font-size: 0.73rem; color: var(--muted); }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.69rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    /* Warna badge role — pilihannya dibatasi di RoleController::COLORS. */
    .pill-badge.role-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pill-badge.role-green  { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.role-blue   { background: rgba(14, 165, 233, 0.16); color: #0284c7; }
    .pill-badge.role-amber  { background: var(--warn-soft); color: var(--warn-fg); }
    .pill-badge.role-rose   { background: var(--danger-soft); color: var(--danger-fg); }
    .pill-badge.role-slate  { background: var(--surface); color: var(--muted); }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.warn  { background: var(--warn-soft); color: var(--warn-fg); }

    /* ---------- Sakelar status ---------- */
    .sw {
        display: inline-flex; align-items: center; gap: 10px;
        border: none; background: none; padding: 4px 8px 4px 4px;
        border-radius: 999px; cursor: pointer;
    }
    .sw:hover { background: var(--card); }
    .sw-track {
        width: 42px; height: 24px; border-radius: 999px;
        background: var(--line); position: relative; flex-shrink: 0;
        transition: background 0.28s ease, box-shadow 0.28s ease;
    }
    .sw.on .sw-track { background: var(--green); box-shadow: 0 3px 10px rgba(0, 177, 79, 0.3); }
    .sw-thumb {
        position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; border-radius: 50%;
        background: #fff; color: var(--muted);
        display: flex; align-items: center; justify-content: center; font-size: 0.55rem;
        transition: transform 0.34s cubic-bezier(0.34, 1.5, 0.5, 1), color 0.2s;
    }
    .sw.on .sw-thumb { transform: translateX(18px); color: var(--green-dark); }
    .sw-label { font-size: 0.78rem; font-weight: 600; color: var(--muted); }
    .sw.on .sw-label { color: var(--green-text); }

    .q-ghost-btn.danger:hover { background: #dc2626; color: #fff; }
    .q-ghost-btn.violet:hover { background: #6d28d9; color: #fff; }

    /* Baris yang di-hover memakai --surface — warna yang sama dengan latar tombol aksi,
       jadi tombolnya ikut hilang. Saat baris disorot, tombol dinaikkan ke --card. */
    .u-table tbody tr:hover .q-ghost-btn { background: var(--card); }
    .u-table tbody tr:hover .q-ghost-btn:hover { background: var(--green); color: #fff; }
    .u-table tbody tr:hover .q-ghost-btn.danger:hover { background: #dc2626; color: #fff; }
    .u-table tbody tr:hover .q-ghost-btn.violet:hover { background: #6d28d9; color: #fff; }

    /* ---------- Pager ---------- */
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

    /* ---------- Modal ---------- */
    .gm-modal {
        position: fixed; inset: 0; z-index: 1250;
        display: flex; align-items: flex-start; justify-content: center;
        padding: 8vh 16px 16px;
        background: rgba(12, 18, 15, 0.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }
    .gm-modal.open { opacity: 1; visibility: visible; }

    .gm-modal-card {
        width: 100%; max-width: 440px; max-height: 84vh;
        display: flex; flex-direction: column;
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
    }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; letter-spacing: -0.02em; }
    .gm-modal-sub { font-size: 0.77rem; color: var(--muted); margin-top: 5px; }
    .gm-modal-body { padding: 0 24px 24px; overflow-y: auto; }

    .who {
        display: flex; align-items: center; gap: 11px;
        background: var(--surface); border-radius: 14px;
        padding: 11px 14px; margin-bottom: 16px;
    }
    .who .nm { font-weight: 600; font-size: 0.85rem; }
    .who .em { font-size: 0.72rem; color: var(--muted); word-break: break-all; }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 11px 13px;
        font-size: 0.72rem; line-height: 1.5; margin-bottom: 16px;
    }
    .note.bad  { background: var(--danger-soft); color: var(--danger-fg); }
    .note.warn { background: var(--warn-soft); color: var(--warn-fg); }
    .note.info { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }

    .form-field { margin-bottom: 15px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 6px; }
    .form-input {
        width: 100%; height: 44px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px 0 40px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }
    .form-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.85rem; }
    .form-magic {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        border: none; background: none; color: var(--muted); cursor: pointer;
        padding: 6px; border-radius: 8px; transition: color 0.15s, background 0.15s;
    }
    .form-magic:hover { background: var(--green-soft); color: var(--green-text); }


    /* Gaya dasar .btn-soft / .btn-solid ada di layout; di modal keduanya dibagi rata. */
    .btn-row > .btn-soft, .btn-row > .btn-solid { flex: 1; padding-left: 12px; padding-right: 12px; }

    @media (prefers-reduced-motion: reduce) {
        .u-table tbody tr { animation: none; }
        .gm-modal-card { transform: none; }
    }
@endpush

@section('content')
@php
    $initialsOf = fn ($name) => collect(explode(' ', $name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $hasFilter = request()->hasAny(['search', 'role', 'verified', 'status']);
@endphp

<div class="q-page-head">
    <h1 class="q-title">{{ __('users.title') }} <span class="soft">{{ $stats['total'] }}</span></h1>
    @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="q-pill q-pill-green">
            <i class="bi bi-person-plus-fill"></i> {{ __('users.add') }}
        </a>
    @endcan
</div>

{{-- ===== Ringkasan ===== --}}
<div class="stat-row">
    @foreach([
        ['lbl' => __('users.stat_total'),      'val' => $stats['total'],      'ic' => 'bi-people-fill',            'tone' => 'tone-violet'],
        ['lbl' => __('users.stat_active'),     'val' => $stats['active'],     'ic' => 'bi-check-circle-fill',      'tone' => 'tone-ok'],
        ['lbl' => __('users.stat_inactive'),   'val' => $stats['inactive'],   'ic' => 'bi-slash-circle-fill',      'tone' => 'tone-bad'],
        ['lbl' => __('users.stat_unverified'), 'val' => $stats['unverified'], 'ic' => 'bi-exclamation-circle-fill','tone' => 'tone-warn'],
    ] as $stat)
        <div class="q-card stat-tile">
            <div class="ic {{ $stat['tone'] }}"><i class="bi {{ $stat['ic'] }}"></i></div>
            <div>
                <div class="q-num val">{{ $stat['val'] }}</div>
                <div class="lbl">{{ $stat['lbl'] }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- ===== Filter ===== --}}
<div class="q-card" style="margin-bottom:16px;">
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar" id="filterForm">
        <div class="field filter-search">
            <i class="bi bi-search lead"></i>
            <input type="text" name="search" class="input" placeholder="{{ __('users.search') }}" value="{{ request('search') }}">
        </div>

        <div class="field filter-sel">
            <i class="bi bi-shield lead"></i>
            <select name="role" class="select" data-auto>
                <option value="">{{ __('users.all_roles') }}</option>
                @foreach($roles as $r)
                    <option value="{{ $r->slug }}" @selected(request('role') === $r->slug)>{{ $r->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field filter-sel">
            <i class="bi bi-toggle-on lead"></i>
            <select name="status" class="select" data-auto>
                <option value="">{{ __('users.all_status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('users.active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('users.inactive') }}</option>
            </select>
        </div>

        <div class="field filter-sel">
            <i class="bi bi-envelope lead"></i>
            <select name="verified" class="select" data-auto>
                <option value="">{{ __('users.all_email') }}</option>
                <option value="yes" @selected(request('verified') === 'yes')>{{ __('users.verified') }}</option>
                <option value="no" @selected(request('verified') === 'no')>{{ __('users.not_verified') }}</option>
            </select>
        </div>

        <div class="field filter-rows">
            <i class="bi bi-list-ol lead"></i>
            <select name="per_page" class="select" data-auto>
                @foreach($perPageOptions as $opt)
                    <option value="{{ $opt }}" @selected($perPage === $opt)>{{ $opt }} {{ __('users.per_page') }}</option>
                @endforeach
            </select>
        </div>

        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">

        <button type="submit" class="q-pill q-pill-green" style="padding:11px 18px;">
            <i class="bi bi-funnel"></i> {{ __('users.apply_filter') }}
        </button>

        @if($hasFilter)
            <a href="{{ route('admin.users.index') }}" class="q-ghost-btn" title="{{ __('users.clear_filter') }}">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>
</div>

{{-- ===== Tabel ===== --}}
<div class="q-card">
    @if($users->count())
        <div class="table-responsive">
            <table class="u-table">
                <thead>
                    <tr>
                        @foreach([
                            'name'       => __('users.col_user'),
                            'role'       => __('users.col_role'),
                            'status'     => __('users.col_status'),
                            'verified'   => __('users.col_email'),
                            'created_at' => __('users.col_registered'),
                        ] as $key => $label)
                            @php
                                $active = $sort === $key;
                                $next   = $active && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <th>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next, 'page' => 1]) }}"
                                   class="th-sort {{ $active ? 'on' : '' }}"
                                   title="{{ __('users.sort_by', ['column' => $label]) }}">
                                    {{ $label }}
                                    <i class="bi bi-{{ $active ? ($dir === 'asc' ? 'sort-up' : 'sort-down') : 'chevron-expand' }}"></i>
                                </a>
                            </th>
                        @endforeach
                        <th class="text-end">{{ __('users.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php $isMe = $user->id === auth()->id(); @endphp
                        <tr style="animation-delay: {{ $loop->index * 28 }}ms;">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="u-avatar {{ $user->isAdmin() ? 'is-admin' : 'is-user' }}">
                                        {{ $initialsOf($user->name) }}
                                    </div>
                                    <div style="min-width:0;">
                                        <div class="u-name text-truncate" style="max-width:220px;">
                                            {{ $user->name }}
                                            @if($isMe)<span class="pill-badge ok">{{ __('users.you') }}</span>@endif
                                        </div>
                                        <div class="u-mail text-truncate" style="max-width:230px;">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="pill-badge role-{{ $user->role?->color ?? 'slate' }}">
                                    <i class="bi bi-{{ $user->isAdmin() ? 'shield-fill-check' : 'person' }}"></i>
                                    {{ $user->roleName() }}
                                </span>
                            </td>

                            <td>
                                @if($isMe || !auth()->user()->can('users.update'))
                                    <span class="pill-badge {{ $user->isActive() ? 'ok' : 'warn' }}">
                                        <i class="bi bi-{{ $user->isActive() ? 'check-circle-fill' : 'slash-circle' }}"></i>
                                        {{ $user->isActive() ? __('users.active') : __('users.inactive') }}
                                    </span>
                                @else
                                    <button type="button" class="sw {{ $user->isActive() ? 'on' : '' }}"
                                            data-status data-key="{{ $user->getRouteKey() }}"
                                            data-name="{{ $user->name }}"
                                            data-active="{{ $user->isActive() ? 1 : 0 }}">
                                        <span class="sw-track">
                                            <span class="sw-thumb">
                                                <i class="bi {{ $user->isActive() ? 'bi-check-lg' : 'bi-slash-lg' }}"></i>
                                            </span>
                                        </span>
                                        <span class="sw-label">{{ $user->isActive() ? __('users.active') : __('users.inactive') }}</span>
                                    </button>
                                @endif
                            </td>

                            <td>
                                @if($user->hasVerifiedEmail())
                                    <span class="pill-badge ok"><i class="bi bi-patch-check-fill"></i> {{ __('users.verified') }}</span>
                                    <div style="font-size:0.66rem;color:var(--muted);margin-top:3px;">
                                        {{ $user->email_verified_at->wib()->translatedFormat('d M Y') }}
                                    </div>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="pill-badge warn"><i class="bi bi-exclamation-circle"></i> {{ __('users.not_verified') }}</span>
                                        @can('users.update')
                                            <form method="POST" action="{{ route('admin.users.resend-verification', $user) }}" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="q-ghost-btn" title="{{ __('users.resend_verification') }}"
                                                        style="width:28px;height:28px;font-size:0.7rem;">
                                                    <i class="bi bi-envelope-arrow-up"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div>{{ $user->created_at?->wib()->translatedFormat('d M Y') }}</div>
                                <div style="font-size:0.68rem;color:var(--muted);">{{ $user->created_at?->diffForHumans() }}</div>
                            </td>

                            <td>
                                <div class="d-flex gap-2 justify-content-end">
                                    @can('users.update')
                                        <a href="{{ route('admin.users.edit', $user) }}" class="q-ghost-btn" title="{{ __('users.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('users.credentials')
                                        <button type="button" class="q-ghost-btn violet" title="{{ __('users.send_credentials') }}"
                                                data-send data-key="{{ $user->getRouteKey() }}"
                                                data-name="{{ $user->name }}" data-email="{{ $user->email }}">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    @endcan
                                    @can('users.delete')
                                        @unless($isMe)
                                            <button type="button" class="q-ghost-btn danger" title="{{ __('users.delete') }}"
                                                    data-delete data-key="{{ $user->getRouteKey() }}"
                                                    data-name="{{ $user->name }}" data-email="{{ $user->email }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            <div class="pager-info">
                {{ __('users.showing', ['from' => $users->firstItem(), 'to' => $users->lastItem(), 'total' => $users->total()]) }}
            </div>

            @if($users->hasPages())

                <a href="{{ $users->previousPageUrl() ?? '#' }}"
                   class="pager-btn {{ $users->onFirstPage() ? 'off' : '' }}" aria-label="{{ __('ui.prev') }}">
                    <i class="bi bi-chevron-left"></i>
                </a>

                @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" class="pager-btn {{ $page === $users->currentPage() ? 'on' : '' }}">{{ $page }}</a>
                @endforeach

                @if($users->currentPage() + 2 < $users->lastPage())
                    <span class="pager-gap">…</span>
                    <a href="{{ $users->url($users->lastPage()) }}" class="pager-btn">{{ $users->lastPage() }}</a>
                @endif

                <a href="{{ $users->nextPageUrl() ?? '#' }}"
                   class="pager-btn {{ $users->hasMorePages() ? '' : 'off' }}" aria-label="{{ __('ui.next') }}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            @endif
        </div>
    @else
        <div class="q-empty">
            <i class="bi bi-people"></i>
            {{ $hasFilter ? __('users.empty_filtered') : __('users.empty') }}
        </div>
    @endif
</div>

{{-- ===================== Modal: konfirmasi (hapus / status) ===================== --}}
<div class="gm-modal" id="confirmModal" role="dialog" aria-modal="true">
    <div class="gm-modal-card" style="max-width:400px;">
        <div class="gm-modal-head">
            <div class="gm-modal-icon" id="cfmIcon"><i class="bi bi-shield-fill-check"></i></div>
            <div class="gm-modal-title" id="cfmTitle"></div>
            <div class="gm-modal-sub" id="cfmSub"></div>
        </div>
        <div class="gm-modal-body">
            <div class="who">
                <i class="bi bi-person-fill" style="color:var(--muted);"></i>
                <div style="min-width:0;">
                    <div class="nm" id="cfmName">—</div>
                    <div class="em" id="cfmEmail" style="display:none;">—</div>
                </div>
            </div>

            <div class="note bad" id="cfmNote" style="display:none;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span id="cfmNoteText"></span>
            </div>

            <form method="POST" id="cfmForm">
                @csrf
                <input type="hidden" name="_method" id="cfmMethod" value="PATCH">
                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn-solid" id="cfmSubmit">
                        <i class="bi bi-check-lg"></i> <span id="cfmSubmitText"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== Modal: kirim kredensial ===================== --}}
<div class="gm-modal" id="sendModal" role="dialog" aria-modal="true">
    <div class="gm-modal-card">
        <div class="gm-modal-head">
            <div class="gm-modal-icon" style="background:var(--tone-indigo-bg);color:var(--tone-indigo-fg);">
                <i class="bi bi-send-fill"></i>
            </div>
            <div class="gm-modal-title">{{ __('users.send_title') }}</div>
            <div class="gm-modal-sub">{{ __('users.send_sub') }}</div>
        </div>
        <div class="gm-modal-body">
            <div class="who">
                <i class="bi bi-person-fill" style="color:var(--muted);"></i>
                <div style="min-width:0;">
                    <div class="nm" id="scName">—</div>
                    <div class="em" id="scEmail">—</div>
                </div>
            </div>

            <form method="POST" id="scForm">
                @csrf
                <div class="form-field">
                    <label class="form-label-sm">{{ __('users.new_password') }}</label>
                    <div style="position:relative;">
                        <i class="bi bi-lock-fill form-icon"></i>
                        <input type="text" name="new_password" id="scPwd" class="form-input" required minlength="8" style="padding-right:82px;">
                        <button type="button" class="pw-eye on" data-toggle-pw="scPwd"
                                aria-pressed="true" aria-label="{{ __('ui.hide_password') }}">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                        <button type="button" class="form-magic" data-gen="scPwd" title="{{ __('ui.generate') }}" style="right:42px;">
                            <i class="bi bi-magic"></i>
                        </button>
                    </div>
                    <div class="form-hint">{{ __('users.send_pwd_hint') }}</div>
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('users.send_to') }}</label>
                    <div style="position:relative;">
                        <i class="bi bi-envelope-fill form-icon"></i>
                        <input type="email" name="send_to_email" id="scTo" class="form-input" placeholder="{{ __('users.send_to_ph') }}" required>
                    </div>
                </div>

                <div class="note warn">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>{{ __('users.send_note') }}</span>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn-solid info"><i class="bi bi-send"></i> {{ __('users.send_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@php
    // Teks modal konfirmasi dikirim ke JS. Dibangun di sini, bukan sebagai array
    // multi-baris di dalam @json(...) — Blade tidak bisa mem-parse yang seperti itu.
    $confirmText = [
        'delete_title'     => __('users.delete_title'),
        'delete_sub'       => __('users.delete_sub'),
        'delete_note'      => __('users.delete_note'),
        'delete'           => __('users.delete'),
        'deactivate_title' => __('users.deactivate_title'),
        'deactivate_sub'   => __('users.deactivate_sub'),
        'deactivate_note'  => __('users.deactivate_note'),
        'deactivate_btn'   => __('users.deactivate_btn'),
        'activate_title'   => __('users.activate_title'),
        'activate_sub'     => __('users.activate_sub'),
        'activate_btn'     => __('users.activate_btn'),
    ];
@endphp

@push('scripts')
<script>
    (function () {
        const USERS = @json(url('/admin/users'));
        const T = @json($confirmText);

        const modals = {
            confirm: document.getElementById('confirmModal'),
            send: document.getElementById('sendModal'),
        };

        const open = (m) => m.classList.add('open');
        const closeAll = () => Object.values(modals).forEach(m => m.classList.remove('open'));

        function randomPassword(len) {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
            const pick = new Uint32Array(len);
            crypto.getRandomValues(pick);
            let out = '';
            for (let i = 0; i < len; i++) out += chars[pick[i] % chars.length];
            // Dijamin memenuhi syarat: minimal satu huruf dan satu angka.
            return out.slice(0, len - 2) + 'A3';
        }

        document.addEventListener('click', (e) => {
            // --- Tutup ---
            if (e.target.closest('[data-close]')) { closeAll(); return; }
            const backdrop = Object.values(modals).find(m => e.target === m);
            if (backdrop) { closeAll(); return; }

            // --- Kirim kredensial ---
            const send = e.target.closest('[data-send]');
            if (send) {
                document.getElementById('scName').textContent = send.dataset.name;
                document.getElementById('scEmail').textContent = send.dataset.email;
                document.getElementById('scForm').action = USERS + '/' + send.dataset.key + '/send-credentials';
                document.getElementById('scTo').value = send.dataset.email;
                document.getElementById('scPwd').value = randomPassword(12);
                open(modals.send);
                return;
            }

            // --- Hapus ---
            const del = e.target.closest('[data-delete]');
            if (del) {
                setConfirm({
                    icon: 'bi-trash3-fill', tone: 'tone-bad',
                    title: T.delete_title, sub: T.delete_sub,
                    name: del.dataset.name, email: del.dataset.email,
                    note: T.delete_note,
                    method: 'DELETE', action: USERS + '/' + del.dataset.key,
                    button: T.delete, danger: true,
                });
                return;
            }

            // --- Aktif / nonaktif ---
            const sw = e.target.closest('[data-status]');
            if (sw) {
                const isActive = sw.dataset.active === '1';
                setConfirm({
                    icon: isActive ? 'bi-slash-circle-fill' : 'bi-check-circle-fill',
                    tone: isActive ? 'tone-bad' : 'tone-ok',
                    title: isActive ? T.deactivate_title : T.activate_title,
                    sub: isActive ? T.deactivate_sub : T.activate_sub,
                    name: sw.dataset.name, email: null,
                    note: isActive ? T.deactivate_note : null,
                    method: 'PATCH', action: USERS + '/' + sw.dataset.key + '/toggle-status',
                    button: isActive ? T.deactivate_btn : T.activate_btn, danger: isActive,
                });
                return;
            }

            // --- Buat kata sandi acak ---
            const gen = e.target.closest('[data-gen]');
            if (gen) {
                const input = document.getElementById(gen.dataset.gen);
                input.value = randomPassword(12);
                input.focus();
            }
        });

        function setConfirm(o) {
            const icon = document.getElementById('cfmIcon');
            icon.className = 'gm-modal-icon ' + o.tone;
            icon.innerHTML = '<i class="bi ' + o.icon + '"></i>';

            document.getElementById('cfmTitle').textContent = o.title;
            document.getElementById('cfmSub').textContent = o.sub;
            document.getElementById('cfmName').textContent = o.name;

            const email = document.getElementById('cfmEmail');
            email.textContent = o.email || '';
            email.style.display = o.email ? 'block' : 'none';

            const note = document.getElementById('cfmNote');
            document.getElementById('cfmNoteText').textContent = o.note || '';
            note.style.display = o.note ? 'flex' : 'none';

            document.getElementById('cfmMethod').value = o.method;
            document.getElementById('cfmForm').action = o.action;
            document.getElementById('cfmSubmit').className = 'btn-solid' + (o.danger ? ' danger' : '');
            document.getElementById('cfmSubmitText').textContent = o.button;

            open(modals.confirm);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAll();
        });

        // Select filter langsung menerapkan — tombol "Terapkan" untuk kolom pencarian.
        document.querySelectorAll('#filterForm [data-auto]').forEach((sel) => {
            sel.addEventListener('change', () => document.getElementById('filterForm').submit());
        });
    })();
</script>
@endpush
