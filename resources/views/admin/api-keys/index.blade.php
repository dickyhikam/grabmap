@extends('layouts.admin-v2')

@section('title', __('apikeys.title'))

@push('styles')
    /* ---------- Tab penyaring ----------
       Tab ini sekaligus menggantikan kartu ringkasan: angkanya sama persis,
       jadi tidak perlu ditampilkan dua kali. Penyaringan terjadi di browser —
       semua key sudah ada di halaman, jadi tidak perlu menembak AWS lagi. */
    /* Tab tinggal di topbar, sebelah brand — tingginya disamakan dengan pill lain. */
    .key-tabs {
        display: inline-flex; gap: 4px;
        background: var(--card); border-radius: 999px;
        padding: 5px; box-shadow: var(--shadow-card);
        max-width: 100%; overflow-x: auto; scrollbar-width: none;
    }
    .key-tabs::-webkit-scrollbar { display: none; }

    .key-tab {
        display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;
        border: none; background: none; cursor: pointer;
        border-radius: 999px; padding: 9px 16px;
        font-size: 0.8rem; font-weight: 600; color: var(--muted);
        transition: background 0.18s, color 0.18s;
    }
    .key-tab:hover { color: var(--ink); }
    .key-tab .count {
        font-size: 0.68rem; font-weight: 800;
        background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 2px 8px; min-width: 22px;
        transition: background 0.18s, color 0.18s;
    }
    .key-tab.on { background: var(--green); color: #fff; box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3); }
    .key-tab.on .count { background: rgba(255, 255, 255, 0.22); color: #fff; }

    .key-card[hidden] { display: none; }

    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }
    .tone-bad    { background: var(--danger-soft); color: var(--danger-fg); }
    .tone-slate  { background: var(--surface); color: var(--muted); }

    /* ---------- Kartu key ---------- */
    .key-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 16px; }
    @media (max-width: 520px) { .key-grid { grid-template-columns: 1fr; } }

    .key-card {
        display: flex; flex-direction: column;
        animation: keyIn 0.34s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
        transition: box-shadow 0.18s ease, transform 0.18s ease;
    }
    .key-card:hover { transform: translateY(-2px); box-shadow: 0 6px 26px rgba(20, 27, 24, 0.09); }
    @keyframes keyIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: none; }
    }

    .key-head { display: flex; align-items: flex-start; gap: 12px; }
    .key-icon {
        width: 42px; height: 42px; border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .key-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 0.98rem; letter-spacing: -0.02em;
        word-break: break-all;
    }
    .key-desc { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.68rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.bad   { background: var(--danger-soft); color: var(--danger-fg); }
    .pill-badge.plain { background: var(--surface); color: var(--muted); }

    .key-meta {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px; margin: 14px 0 0; padding-top: 13px;
        border-top: 1px solid var(--line);
    }
    .key-meta .lb { font-size: 0.63rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); }
    .key-meta .vl { font-size: 0.79rem; font-weight: 600; margin-top: 2px; }

    .key-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .key-btn {
        display: inline-flex; align-items: center; gap: 6px;
        border: none; background: var(--surface); color: var(--ink);
        border-radius: 999px; padding: 8px 14px;
        font-size: 0.76rem; font-weight: 600; cursor: pointer; text-decoration: none;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }
    .key-btn:hover { background: var(--green); color: #fff; }
    .key-btn:active { transform: scale(0.96); }
    .key-btn.danger:hover { background: #dc2626; color: #fff; }

    /* ---------- Modal ---------- */
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
        width: 100%; max-width: 430px;
        background: var(--card); border-radius: 24px;
        box-shadow: var(--shadow-pop); overflow: visible;
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
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-sub { font-size: 0.77rem; color: var(--muted); margin-top: 5px; }
    .gm-modal-body { padding: 0 24px 24px; }

    .who {
        display: flex; align-items: center; gap: 11px;
        background: var(--surface); border-radius: 14px;
        padding: 11px 14px; margin-bottom: 16px;
        font-weight: 600; font-size: 0.84rem; word-break: break-all;
    }
    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 11px 13px; margin: 14px 0 16px;
        font-size: 0.72rem; line-height: 1.5;
    }
    .note.info { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .note.warn { background: var(--warn-soft); color: var(--warn-fg); }

    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .btn-row > .btn-soft, .btn-row > .btn-solid { flex: 1; padding-left: 12px; padding-right: 12px; }
@endpush

@php
    // Dihitung di luar section: dipakai oleh tab di topbar sekaligus daftar di bawah.
    $totalKeys  = count($keys);
    $activeKeys = collect($keys)->filter(fn ($k) => !($k['expire_time'] && \Carbon\Carbon::parse($k['expire_time'])->isPast()))->count();
    $keyNames   = collect($keys)->pluck('key_name')->all();
    $assignedCount   = $accountCompanies->whereNotNull('aws_api_key_name')->whereIn('aws_api_key_name', $keyNames)->count();
    $expiredCount    = $totalKeys - $activeKeys;
    $unassignedCount = $totalKeys - $assignedCount;
    $showTabs        = $hasCredentials && !$error && $totalKeys > 0;
    $canAssign       = config('features.api_key_assign');
@endphp

@if($showTabs)
@section('top-tabs')
    <div class="key-tabs" id="keyTabs" role="tablist">
        @foreach([
            ['k' => 'all',        'l' => __('apikeys.tab_all'),        'n' => $totalKeys],
            ['k' => 'active',     'l' => __('apikeys.tab_active'),     'n' => $activeKeys],
            ['k' => 'expired',    'l' => __('apikeys.tab_expired'),    'n' => $expiredCount],
            ...($canAssign ? [['k' => 'unassigned', 'l' => __('apikeys.tab_unassigned'), 'n' => $unassignedCount]] : []),
        ] as $tab)
            <button type="button" class="key-tab {{ $loop->first ? 'on' : '' }}"
                    data-tab="{{ $tab['k'] }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $tab['l'] }} <span class="count">{{ $tab['n'] }}</span>
            </button>
        @endforeach
    </div>
@endsection
@endif

@section('content')

<div class="q-page-head">
    <h1 class="q-title">
        {{ __('apikeys.title') }}
        @if($hasCredentials && !$error)<span class="soft">{{ $totalKeys }}</span>@endif
    </h1>

    {{-- Akun yang sedang dilihat sudah ditampilkan pill di topbar, tidak perlu diulang. --}}
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @can('api_keys.create')
            @if($hasCredentials)
                <a href="{{ route('admin.api-keys.create', ['account' => $account?->id]) }}" class="q-pill q-pill-green">
                    <i class="bi bi-plus-lg"></i> {{ __('apikeys.add') }}
                </a>
            @endif
        @endcan
    </div>
</div>

@if(!$hasCredentials)
    <div class="q-card">
        <div class="q-empty">
            <i class="bi bi-shield-lock"></i>
            <div style="font-weight:700;color:var(--ink);margin-bottom:6px;">{{ __('apikeys.no_creds_title') }}</div>
            <div style="max-width:460px;margin:0 auto 16px;">{{ __('apikeys.no_creds_desc') }}</div>
            @can('aws_accounts.create')
                <a href="{{ route('admin.aws-accounts.create') }}" class="q-pill q-pill-green">
                    <i class="bi bi-plus-lg"></i> {{ __('apikeys.add_account') }}
                </a>
            @endcan
            <div style="font-size:0.7rem;margin-top:16px;">
                {{ __('apikeys.iam_needs') }}: <code>geo:ListKeys</code>, <code>geo:DescribeKey</code>, <code>cloudwatch:GetMetricData</code>
            </div>
        </div>
    </div>
@elseif($error)
    <div class="q-alert bad">
        <span class="q-alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div class="q-alert-body">
            <strong>{{ __('apikeys.error_title') }}</strong><br>
            {{ $error }}<br>
            <span style="opacity:0.8;">{{ __('apikeys.error_desc', ['permission' => 'geo:ListKeys']) }}</span>
        </div>
        @can('aws_accounts.view')
            <a href="{{ route('admin.aws-accounts.index') }}" class="q-alert-action">{{ __('dash.check_account') }}</a>
        @endcan
    </div>
@elseif(empty($keys))
    <div class="q-card">
        <div class="q-empty">
            <i class="bi bi-key"></i>
            <div style="font-weight:700;color:var(--ink);margin-bottom:6px;">{{ __('apikeys.empty_title') }}</div>
            <div style="margin-bottom:16px;">{{ __('apikeys.empty_desc', ['region' => $region]) }}</div>
            @can('api_keys.create')
                <a href="{{ route('admin.api-keys.create', ['account' => $account?->id]) }}" class="q-pill q-pill-green">
                    <i class="bi bi-plus-lg"></i> {{ __('apikeys.empty_cta') }}
                </a>
            @endcan
        </div>
    </div>
@else
    {{-- ===== Daftar key ===== --}}
    <div class="key-grid">
        @foreach($keys as $key)
            @php
                $expired = $key['expire_time'] && \Carbon\Carbon::parse($key['expire_time'])->isPast();
                $company = $accountCompanies->firstWhere('aws_api_key_name', $key['key_name']);
            @endphp
            <div class="q-card key-card"
                 data-status="{{ $expired ? 'expired' : 'active' }}"
                 data-assigned="{{ $company ? '1' : '0' }}"
                 style="animation-delay: {{ $loop->index * 40 }}ms;">
                <div class="key-head">
                    <div class="key-icon {{ $expired ? 'tone-bad' : 'tone-green' }}"><i class="bi bi-key-fill"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="key-name">{{ $key['key_name'] }}</div>
                        <div class="key-desc">{{ $key['description'] ?: __('apikeys.no_description') }}</div>
                    </div>
                    <span class="pill-badge {{ $expired ? 'bad' : 'ok' }}">
                        <i class="bi bi-{{ $expired ? 'x-circle-fill' : 'check-circle-fill' }}"></i>
                        {{ $expired ? __('apikeys.expired') : __('apikeys.active') }}
                    </span>
                </div>

                <div style="margin-top:12px;">
                    @if($company)
                        <span class="pill-badge ok"><i class="bi bi-building"></i> {{ $company->name }}</span>
                    @else
                        <span class="pill-badge plain"><i class="bi bi-dash-circle"></i> {{ __('apikeys.not_assigned') }}</span>
                    @endif
                </div>

                <div class="key-meta">
                    <div>
                        <div class="lb">{{ __('apikeys.created') }}</div>
                        <div class="vl">{{ $key['create_time'] ? \Carbon\Carbon::parse($key['create_time'])->translatedFormat('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="lb">{{ __('apikeys.expires') }}</div>
                        <div class="vl" @if($expired) style="color:var(--danger-fg);" @endif>
                            {{ $key['expire_time'] ? \Carbon\Carbon::parse($key['expire_time'])->translatedFormat('d M Y') : __('apikeys.never') }}
                        </div>
                    </div>
                    <div>
                        <div class="lb">{{ __('apikeys.region') }}</div>
                        <div class="vl">{{ $region }}</div>
                    </div>
                </div>

                <div class="key-actions">
                    <a href="{{ route('admin.api-keys.usage', ['keyName' => $key['key_name'], 'account' => $account?->id]) }}" class="key-btn">
                        <i class="bi bi-bar-chart"></i> {{ __('apikeys.usage') }}
                    </a>

                    @can('api_keys.update')
                        <a href="{{ route('admin.api-keys.edit', ['keyName' => $key['key_name'], 'account' => $account?->id]) }}" class="key-btn">
                            <i class="bi bi-pencil"></i> {{ __('apikeys.edit') }}
                        </a>
                    @endcan

                    @if($canAssign)
                        @can('api_keys.assign')
                            @unless($expired)
                                <button type="button" class="key-btn" data-assign data-key="{{ $key['key_name'] }}">
                                    <i class="bi bi-link-45deg"></i> {{ __('apikeys.assign') }}
                                </button>
                            @endunless

                            @if($company)
                                <button type="button" class="key-btn danger"
                                        data-unassign data-company="{{ $company->id }}" data-name="{{ $company->name }}">
                                    <i class="bi bi-x-lg"></i> {{ __('apikeys.unassign') }}
                                </button>
                            @endif
                        @endcan
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="q-card" id="tabEmpty" hidden style="margin-top:16px;">
        <div class="q-empty"><i class="bi bi-funnel"></i>{{ __('apikeys.tab_empty') }}</div>
    </div>

    {{-- ===================== Modal: pasangkan ke perusahaan ===================== --}}
    @if($canAssign)
    @can('api_keys.assign')
    <div class="gm-modal" id="assignModal" role="dialog" aria-modal="true">
        <div class="gm-modal-card">
            <div class="gm-modal-head">
                <div class="gm-modal-icon tone-green"><i class="bi bi-link-45deg"></i></div>
                <div class="gm-modal-title">{{ __('apikeys.assign_title') }}</div>
                <div class="gm-modal-sub">{{ __('apikeys.assign_sub') }}</div>
            </div>
            <div class="gm-modal-body">
                <div class="who"><i class="bi bi-key-fill" style="color:var(--green-text);"></i><span id="asgKey">—</span></div>

                <form method="POST" action="{{ route('admin.api-keys.assign') }}">
                    @csrf
                    <input type="hidden" name="key_name" id="asgKeyInput">
                    <input type="hidden" name="account" value="{{ $account?->id }}">

                    <label class="form-label-sm">{{ __('apikeys.company') }}</label>
                    <select name="company_id" class="select" required style="width:100%;padding-left:16px;">
                        <option value="">{{ __('apikeys.choose') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">
                                {{ $company->name }}@if($company->aws_api_key_name) — {{ __('apikeys.has_key', ['name' => $company->aws_api_key_name]) }}@endif
                            </option>
                        @endforeach
                    </select>

                    @if($account)
                        <div class="note info">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>{{ __('apikeys.assign_note', ['name' => $account->name]) }}</span>
                        </div>
                    @endif

                    <div class="btn-row">
                        <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn-solid"><i class="bi bi-link-45deg"></i> {{ __('apikeys.assign_btn') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===================== Modal: lepas key ===================== --}}
    <div class="gm-modal" id="unassignModal" role="dialog" aria-modal="true">
        <div class="gm-modal-card" style="max-width:400px;">
            <div class="gm-modal-head">
                <div class="gm-modal-icon tone-bad"><i class="bi bi-x-circle-fill"></i></div>
                <div class="gm-modal-title">{{ __('apikeys.unassign_title') }}</div>
                <div class="gm-modal-sub">{{ __('apikeys.unassign_sub') }}</div>
            </div>
            <div class="gm-modal-body">
                <div class="who"><i class="bi bi-building" style="color:var(--muted);"></i><span id="unName">—</span></div>
                <div class="note warn"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ __('apikeys.unassign_note') }}</span></div>

                <form method="POST" action="{{ route('admin.api-keys.unassign') }}">
                    @csrf
                    <input type="hidden" name="account" value="{{ $account?->id }}">
                    <input type="hidden" name="company_id" id="unCompany">
                    <div class="btn-row">
                        <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn-solid danger"><i class="bi bi-x-lg"></i> {{ __('apikeys.unassign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
    @endif
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const assign = document.getElementById('assignModal');
        const unassign = document.getElementById('unassignModal');
        if (!assign && !unassign) return;

        const closeAll = () => [assign, unassign].forEach(m => m?.classList.remove('open'));

        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-close]') || e.target === assign || e.target === unassign) {
                closeAll();
                return;
            }

            const asg = e.target.closest('[data-assign]');
            if (asg) {
                document.getElementById('asgKey').textContent = asg.dataset.key;
                document.getElementById('asgKeyInput').value = asg.dataset.key;
                assign.classList.add('open');
                return;
            }

            const una = e.target.closest('[data-unassign]');
            if (una) {
                document.getElementById('unName').textContent = una.dataset.name;
                document.getElementById('unCompany').value = una.dataset.company;
                unassign.classList.add('open');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAll();
        });
    })();

    // ---------- Tab penyaring ----------
    (function () {
        const tabs = document.getElementById('keyTabs');
        if (!tabs) return;

        const cards = [...document.querySelectorAll('.key-card')];
        const empty = document.getElementById('tabEmpty');

        const matches = (card, tab) => ({
            all:        true,
            active:     card.dataset.status === 'active',
            expired:    card.dataset.status === 'expired',
            unassigned: card.dataset.assigned === '0',
        })[tab] ?? true;

        function apply(tab, push) {
            let shown = 0;
            cards.forEach((card) => {
                const ok = matches(card, tab);
                card.hidden = !ok;
                if (ok) shown++;
            });

            empty.hidden = shown > 0;

            tabs.querySelectorAll('.key-tab').forEach((btn) => {
                const on = btn.dataset.tab === tab;
                btn.classList.toggle('on', on);
                btn.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            // URL ikut menyimpan tab supaya bisa dibagikan / bertahan saat refresh.
            if (push) {
                const url = new URL(window.location.href);
                tab === 'all' ? url.searchParams.delete('status') : url.searchParams.set('status', tab);
                history.replaceState(null, '', url);
            }
        }

        tabs.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-tab]');
            if (btn) apply(btn.dataset.tab, true);
        });

        const initial = new URL(window.location.href).searchParams.get('status');
        if (initial) apply(initial, false);
    })();
</script>
@endpush
