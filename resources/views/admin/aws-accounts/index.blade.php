@extends('layouts.admin-v2')

@section('title', __('awsaccounts.title'))

@push('styles')
    .acc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 16px; }
    @media (max-width: 540px) { .acc-grid { grid-template-columns: 1fr; } }

    .acc-card {
        display: flex; flex-direction: column;
        animation: accIn 0.34s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
        transition: box-shadow 0.18s ease, transform 0.18s ease;
    }
    .acc-card:hover { transform: translateY(-2px); box-shadow: 0 6px 26px rgba(20, 27, 24, 0.09); }
    .acc-card.off { opacity: 0.72; }
    @keyframes accIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: none; }
    }

    .acc-head { display: flex; align-items: flex-start; gap: 12px; }
    .acc-ic {
        width: 42px; height: 42px; border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
        background: var(--surface); color: var(--muted);
    }
    .acc-card.is-default .acc-ic { background: var(--green-soft); color: var(--green-text); }
    .acc-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 0.98rem; letter-spacing: -0.02em;
    }
    .acc-sub { font-size: 0.72rem; color: var(--muted); margin-top: 2px; }
    .acc-badges { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 5px; }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.plain { background: var(--surface); color: var(--muted); }
    .pill-badge.warn  { background: var(--warn-soft); color: var(--warn-fg); }

    .acc-meta {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px; margin-top: 14px; padding-top: 13px;
        border-top: 1px solid var(--line);
    }
    .acc-meta .lb { font-size: 0.63rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); }
    .acc-meta .vl { font-size: 0.79rem; font-weight: 600; margin-top: 2px; font-family: ui-monospace, monospace; }
    .acc-meta .vl.plain { font-family: inherit; }

    .acc-notes {
        margin-top: 12px; padding: 10px 12px;
        background: var(--surface); border-radius: 12px;
        font-size: 0.75rem; color: var(--muted); line-height: 1.5;
    }

    .acc-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; padding-top: 14px; }
    .acc-btn {
        display: inline-flex; align-items: center; gap: 6px;
        border: none; background: var(--surface); color: var(--ink);
        border-radius: 999px; padding: 8px 14px;
        font-size: 0.76rem; font-weight: 600; cursor: pointer; text-decoration: none;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }
    .acc-btn:hover { background: var(--green); color: #fff; }
    .acc-btn:active { transform: scale(0.96); }
    .acc-btn.danger:hover { background: #dc2626; color: #fff; }
    .acc-btn[disabled] { opacity: 0.45; cursor: not-allowed; }
    .acc-btn[disabled]:hover { background: var(--surface); color: var(--ink); }

    /* Modal hapus */
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
        background: var(--danger-soft); color: var(--danger-fg);
    }
    .gm-modal-icon.star { background: var(--green-soft); color: var(--green-text); }

    /* Perpindahan default: akun lama di atas, akun baru di bawah. */
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
    .swap-tx .nm { display: block; font-weight: 700; font-size: 0.85rem; }
    .swap-tx .rg { display: block; font-size: 0.68rem; color: var(--muted); font-family: ui-monospace, monospace; }
    .swap-tag { font-size: 0.63rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
    .swap-row.in .swap-tag { color: var(--green-text); }
    .swap-arrow { text-align: center; color: var(--faint); font-size: 0.85rem; padding: 5px 0; }

    .note.ok { background: var(--green-soft); color: var(--green-text); }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-sub { font-size: 0.77rem; color: var(--muted); margin-top: 5px; }
    .gm-modal-body { padding: 0 24px 24px; }
    .who {
        display: flex; align-items: center; gap: 11px;
        background: var(--surface); border-radius: 14px;
        padding: 11px 14px; margin-bottom: 16px;
        font-weight: 600; font-size: 0.85rem;
    }
    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 11px 13px; margin-bottom: 16px;
        font-size: 0.72rem; line-height: 1.5;
        background: var(--danger-soft); color: var(--danger-fg);
    }
    .btn-row > .btn-soft, .btn-row > .btn-solid { flex: 1; padding-left: 12px; padding-right: 12px; }
@endpush

@section('content')
<div class="q-page-head">
    <h1 class="q-title">{{ __('awsaccounts.title') }} <span class="soft">{{ $accounts->count() }}</span></h1>
    @can('aws_accounts.create')
        <a href="{{ route('admin.aws-accounts.create') }}" class="q-pill q-pill-green">
            <i class="bi bi-plus-lg"></i> {{ __('awsaccounts.add') }}
        </a>
    @endcan
</div>

@if($accounts->isEmpty())
    <div class="q-card">
        <div class="q-empty">
            <i class="bi bi-cloud"></i>
            <div style="font-weight:700;color:var(--ink);margin-bottom:6px;">{{ __('awsaccounts.empty_title') }}</div>
            <div style="max-width:460px;margin:0 auto 16px;">
                {{ __('awsaccounts.empty_desc') }}
                @if($envConfigured) {{ __('awsaccounts.env_note') }} @endif
            </div>
            @can('aws_accounts.create')
                <a href="{{ route('admin.aws-accounts.create') }}" class="q-pill q-pill-green">
                    <i class="bi bi-plus-lg"></i> {{ __('awsaccounts.add') }}
                </a>
            @endcan
        </div>
    </div>
@else
    <div class="acc-grid">
        @foreach($accounts as $account)
            <div class="q-card acc-card {{ $account->is_default ? 'is-default' : '' }} {{ $account->is_active ? '' : 'off' }}"
                 style="animation-delay: {{ $loop->index * 40 }}ms;">
                <div class="acc-head">
                    <div class="acc-ic"><i class="bi bi-cloud-fill"></i></div>
                    <div style="flex:1;min-width:0;">
                        <div class="acc-name">{{ $account->name }}</div>
                        <div class="acc-sub">
                            {{ $account->account_number
                                ? __('awsaccounts.account_id') . ' ' . $account->account_number
                                : __('awsaccounts.no_account_id') }}
                            · {{ __('awsaccounts.companies', ['count' => $account->companies_count]) }}
                        </div>
                    </div>
                    <div class="acc-badges">
                        @if($account->is_default)
                            <span class="pill-badge ok"><i class="bi bi-star-fill"></i> {{ __('awsaccounts.default') }}</span>
                        @endif
                        <span class="pill-badge {{ $account->is_active ? 'plain' : 'warn' }}">
                            {{ $account->is_active ? __('awsaccounts.active') : __('awsaccounts.inactive') }}
                        </span>
                    </div>
                </div>

                <div class="acc-meta">
                    <div>
                        <div class="lb">{{ __('awsaccounts.access_key') }}</div>
                        <div class="vl">{{ $account->maskedAccessKey() }}</div>
                    </div>
                    <div>
                        <div class="lb">{{ __('awsaccounts.region') }}</div>
                        <div class="vl">{{ $account->region }}</div>
                    </div>
                    <div>
                        <div class="lb">{{ __('awsaccounts.secret') }}</div>
                        <div class="vl plain">
                            @if($account->secret_access_key)
                                <span style="color:var(--green-text);">
                                    <i class="bi bi-lock-fill"></i> {{ __('awsaccounts.secret_saved') }}
                                </span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="lb">{{ __('awsaccounts.verified') }}</div>
                        <div class="vl plain">
                            {{ $account->last_verified_at?->diffForHumans() ?? __('awsaccounts.never_tested') }}
                        </div>
                    </div>
                </div>

                @if($account->notes)
                    <div class="acc-notes">{{ $account->notes }}</div>
                @endif

                <div class="acc-actions">
                    @can('api_keys.view')
                        <a href="{{ route('admin.api-keys.index', ['account' => $account->getRouteKey()]) }}" class="acc-btn">
                            <i class="bi bi-key"></i> {{ __('awsaccounts.api_keys') }}
                        </a>
                    @endcan

                    @can('aws_accounts.update')
                        <a href="{{ route('admin.aws-accounts.edit', $account) }}" class="acc-btn">
                            <i class="bi bi-pencil"></i> {{ __('awsaccounts.edit') }}
                        </a>

                        <form method="POST" action="{{ route('admin.aws-accounts.test', $account) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="acc-btn" {{ $account->hasCredentials() ? '' : 'disabled' }}>
                                <i class="bi bi-plug"></i> {{ __('awsaccounts.test') }}
                            </button>
                        </form>

                        @if(!$account->is_default && $account->is_active)
                            <form method="POST" action="{{ route('admin.aws-accounts.default', $account) }}"
                                  id="setdef-{{ $account->id }}" style="margin:0;">
                                @csrf
                                {{-- Pindah default selalu lewat konfirmasi: satu klik di sini mengubah
                                     akun yang dipakai dashboard, API key, dan simulator. --}}
                                <button type="button" class="acc-btn"
                                        data-setdef data-form="setdef-{{ $account->id }}"
                                        data-name="{{ $account->name }}" data-region="{{ $account->region }}">
                                    <i class="bi bi-star"></i> {{ __('awsaccounts.make_default') }}
                                </button>
                            </form>
                        @endif
                    @endcan

                    @can('aws_accounts.delete')
                        <button type="button" class="acc-btn danger"
                                data-delete data-key="{{ $account->id }}" data-name="{{ $account->name }}"
                                data-used="{{ $account->companies_count }}"
                                @if($account->companies_count > 0) title="{{ __('awsaccounts.in_use', ['count' => $account->companies_count]) }}" disabled @endif>
                            <i class="bi bi-trash"></i> {{ __('awsaccounts.delete') }}
                        </button>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    @can('aws_accounts.update')
        @php $currentDefault = $accounts->firstWhere('is_default', true); @endphp
        <div class="gm-modal" id="defaultModal" role="dialog" aria-modal="true">
            <div class="gm-modal-card">
                <div class="gm-modal-head">
                    <div class="gm-modal-icon star"><i class="bi bi-star-fill"></i></div>
                    <div class="gm-modal-title">{{ __('awsaccounts.switch_title') }}</div>
                    <div class="gm-modal-sub">{{ __('awsaccounts.switch_sub') }}</div>
                </div>
                <div class="gm-modal-body">
                    @if($currentDefault)
                        <div class="swap">
                            <div class="swap-row out">
                                <span class="swap-ic"><i class="bi bi-star-fill"></i></span>
                                <span class="swap-tx">
                                    <span class="nm">{{ $currentDefault->name }}</span>
                                    <span class="rg">{{ $currentDefault->region }}</span>
                                </span>
                                <span class="swap-tag">{{ __('awsaccounts.switch_from') }}</span>
                            </div>

                            <div class="swap-arrow"><i class="bi bi-arrow-down"></i></div>

                            <div class="swap-row in">
                                <span class="swap-ic"><i class="bi bi-star-fill"></i></span>
                                <span class="swap-tx">
                                    <span class="nm" id="defName">—</span>
                                    <span class="rg" id="defRegion">—</span>
                                </span>
                                <span class="swap-tag">{{ __('awsaccounts.switch_to') }}</span>
                            </div>
                        </div>

                        <div class="note ok">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>{{ __('awsaccounts.switch_note', ['name' => $currentDefault->name]) }}</span>
                        </div>
                    @else
                        <div class="who">
                            <i class="bi bi-star-fill" style="color:var(--green-text);"></i>
                            <span id="defName">—</span>
                        </div>
                        <span id="defRegion" hidden></span>
                        <div class="note ok">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>{{ __('awsaccounts.switch_first') }}</span>
                        </div>
                    @endif

                    <div class="btn-row">
                        <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                        <button type="button" class="btn-solid" id="defConfirm">
                            <i class="bi bi-star-fill"></i> {{ __('awsaccounts.switch_confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('aws_accounts.delete')
        <div class="gm-modal" id="deleteModal" role="dialog" aria-modal="true">
            <div class="gm-modal-card">
                <div class="gm-modal-head">
                    <div class="gm-modal-icon"><i class="bi bi-trash3-fill"></i></div>
                    <div class="gm-modal-title">{{ __('awsaccounts.delete_title') }}</div>
                    <div class="gm-modal-sub">{{ __('awsaccounts.delete_sub') }}</div>
                </div>
                <div class="gm-modal-body">
                    <div class="who"><i class="bi bi-cloud-fill" style="color:var(--muted);"></i><span id="delName">—</span></div>
                    <div class="note"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ __('awsaccounts.delete_note') }}</span></div>

                    <form method="POST" id="delForm">
                        @csrf
                        @method('DELETE')
                        <div class="btn-row">
                            <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                            <button type="submit" class="btn-solid danger">
                                <i class="bi bi-trash"></i> {{ __('awsaccounts.delete') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const modals = [...document.querySelectorAll('.gm-modal')];
        if (!modals.length) return;

        const delModal = document.getElementById('deleteModal');
        const defModal = document.getElementById('defaultModal');
        const BASE = @json(url('/admin/aws-accounts'));

        function closeAll() {
            modals.forEach((m) => m.classList.remove('open'));
        }

        document.addEventListener('click', (e) => {
            // Tutup: tombol batal, atau klik pada latar gelap di luar kartu.
            if (e.target.closest('[data-close]') || modals.includes(e.target)) {
                closeAll();
                return;
            }

            const del = e.target.closest('[data-delete]');
            if (del && !del.disabled && delModal) {
                document.getElementById('delName').textContent = del.dataset.name;
                document.getElementById('delForm').action = BASE + '/' + del.dataset.key;
                delModal.classList.add('open');
                return;
            }

            const setdef = e.target.closest('[data-setdef]');
            if (setdef && defModal) {
                document.getElementById('defName').textContent = setdef.dataset.name;
                document.getElementById('defRegion').textContent = setdef.dataset.region;
                // Formulir yang akan dikirim disimpan di modal, bukan di tombol konfirmasi,
                // supaya konfirmasi yang sama bisa dipakai semua kartu.
                defModal.dataset.form = setdef.dataset.form;
                defModal.classList.add('open');
            }
        });

        const confirmBtn = document.getElementById('defConfirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                const form = document.getElementById(defModal.dataset.form || '');
                if (!form) return;
                closeAll();
                // requestSubmit() melepas event 'submit' — page loader ikut menyala.
                form.requestSubmit ? form.requestSubmit() : form.submit();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAll();
        });
    })();
</script>
@endpush
