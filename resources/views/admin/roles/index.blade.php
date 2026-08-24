@extends('layouts.admin-v2')

@section('title', __('roles.title'))

@push('styles')
    .role-list { display: flex; flex-direction: column; }
    .role-row {
        display: flex; align-items: center; gap: 14px;
        padding: 16px; border-radius: 16px;
        border-bottom: 1px solid var(--line);
        animation: rowIn 0.32s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .role-row:last-child { border-bottom: none; }
    .role-row:hover { background: var(--surface); }
    @keyframes rowIn {
        from { opacity: 0; transform: translateY(9px); }
        to   { opacity: 1; transform: none; }
    }

    .role-mark {
        width: 44px; height: 44px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .role-name { font-weight: 700; font-size: 0.92rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .role-desc { font-size: 0.74rem; color: var(--muted); margin-top: 2px; }

    .chip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 3px 9px; border-radius: 999px; white-space: nowrap;
    }
    .chip.sys  { background: var(--surface); color: var(--muted); }
    .chip.full { background: var(--green-soft); color: var(--green-text); }

    .role-meta { text-align: right; font-size: 0.74rem; color: var(--muted); white-space: nowrap; }
    .role-meta b { display: block; font-size: 0.86rem; color: var(--ink); }

    .tone-violet { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .tone-green  { background: var(--green-soft); color: var(--green-text); }
    .tone-blue   { background: rgba(14, 165, 233, 0.16); color: #0284c7; }
    .tone-amber  { background: var(--warn-soft); color: var(--warn-fg); }
    .tone-rose   { background: var(--danger-soft); color: var(--danger-fg); }
    .tone-slate  { background: var(--surface); color: var(--muted); }

    .role-row:hover .q-ghost-btn { background: var(--card); }
    .role-row:hover .q-ghost-btn:hover { background: var(--green); color: #fff; }
    .role-row:hover .q-ghost-btn.danger:hover { background: #dc2626; color: #fff; }
    .q-ghost-btn.danger:hover { background: #dc2626; color: #fff; }

    /* Modal konfirmasi hapus */
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
@endpush

@section('content')
<div class="q-page-head">
    <h1 class="q-title">{{ __('roles.title') }} <span class="soft">{{ $roles->count() }}</span></h1>
    @can('roles.create')
        <a href="{{ route('admin.roles.create') }}" class="q-pill q-pill-green">
            <i class="bi bi-plus-lg"></i> {{ __('roles.add') }}
        </a>
    @endcan
</div>

<div class="q-card">
    @if($roles->isEmpty())
        <div class="q-empty"><i class="bi bi-shield-lock"></i>{{ __('roles.empty') }}</div>
    @else
        <div class="role-list">
            @foreach($roles as $role)
                <div class="role-row" style="animation-delay: {{ $loop->index * 30 }}ms;">
                    <div class="role-mark tone-{{ $role->color }}">
                        <i class="bi bi-{{ $role->isFullAccess() ? 'shield-fill-check' : 'shield' }}"></i>
                    </div>

                    <div style="flex:1;min-width:0;">
                        <div class="role-name">
                            {{ $role->name }}
                            @if($role->is_system)<span class="chip sys">{{ __('roles.system') }}</span>@endif
                            @if($role->isFullAccess())<span class="chip full">{{ __('roles.full_access') }}</span>@endif
                        </div>
                        <div class="role-desc">
                            {{ $role->description ?: __('roles.perm_count', ['count' => count($role->permissions ?? []), 'total' => $total]) }}
                        </div>
                    </div>

                    <div class="role-meta">
                        <b>{{ $role->isFullAccess() ? $total : count($role->permissions ?? []) }}/{{ $total }}</b>
                        {{ __('roles.col_access') }}
                    </div>

                    <div class="role-meta">
                        <b>{{ $role->users_count }}</b>
                        {{ __('roles.col_users') }}
                    </div>

                    <div class="d-flex gap-2">
                        @can('roles.update')
                            <a href="{{ route('admin.roles.edit', $role) }}" class="q-ghost-btn" title="{{ __('users.edit') }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endcan
                        @unless($role->is_system)
                        @can('roles.delete')
                            <button type="button" class="q-ghost-btn danger" title="{{ __('roles.delete') }}"
                                    data-delete data-key="{{ $role->uuid }}" data-name="{{ $role->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endcan
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="gm-modal" id="deleteModal" role="dialog" aria-modal="true">
    <div class="gm-modal-card">
        <div class="gm-modal-head">
            <div class="gm-modal-icon"><i class="bi bi-trash3-fill"></i></div>
            <div class="gm-modal-title">{{ __('roles.delete_title') }}</div>
            <div class="gm-modal-sub">{{ __('roles.delete_sub') }}</div>
        </div>
        <div class="gm-modal-body">
            <div class="who"><i class="bi bi-shield" style="color:var(--muted);"></i><span id="delName">—</span></div>
            <div class="note"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ __('roles.delete_note') }}</span></div>

            <form method="POST" id="delForm">
                @csrf
                @method('DELETE')
                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close style="flex:1;">{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn-solid danger" style="flex:1;">
                        <i class="bi bi-trash"></i> {{ __('roles.delete') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const ROLES = @json(url('/admin/roles'));
        const modal = document.getElementById('deleteModal');

        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-close]') || e.target === modal) {
                modal.classList.remove('open');
                return;
            }

            const btn = e.target.closest('[data-delete]');
            if (!btn) return;

            document.getElementById('delName').textContent = btn.dataset.name;
            document.getElementById('delForm').action = ROLES + '/' + btn.dataset.key;
            modal.classList.add('open');
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') modal.classList.remove('open');
        });
    })();
</script>
@endpush
