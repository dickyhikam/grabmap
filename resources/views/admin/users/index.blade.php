@extends('layouts.admin')

@section('title', 'Users')

@section('header')
<div class="page-header">
    <div class="container">
        <h4 class="fw-bold mt-2 mb-1" style="font-size:1.4rem;">
            <i class="bi bi-people-fill me-2" style="opacity:0.7;"></i> User Management
        </h4>
        <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;margin:0;">Manage registered accounts and permissions</p>
    </div>
    <div class="container mt-3">
        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" style="font-weight:600;font-size:0.85rem;" onclick="openCreateModal()">
            <i class="bi bi-person-plus-fill me-1"></i> Create User
        </button>
    </div>
</div>
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Total Users', 'value' => $stats['total'], 'icon' => 'bi-people-fill', 'bg' => '#ede9fe', 'color' => '#7c3aed'],
        ['label' => 'Active', 'value' => $stats['active'], 'icon' => 'bi-check-circle-fill', 'bg' => '#dcfce7', 'color' => '#16a34a'],
        ['label' => 'Inactive', 'value' => $stats['inactive'], 'icon' => 'bi-slash-circle-fill', 'bg' => '#fee2e2', 'color' => '#dc2626'],
        ['label' => 'Unverified', 'value' => $stats['unverified'], 'icon' => 'bi-exclamation-circle-fill', 'bg' => '#fef3c7', 'color' => '#d97706'],
    ] as $stat)
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:{{ $stat['bg'] }};color:{{ $stat['color'] }};">
                    <i class="bi {{ $stat['icon'] }}"></i>
                </div>
                <div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-value">{{ $stat['value'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1" style="min-width:200px;">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text" style="border-radius:10px 0 0 10px;background:#f0f2f5;border-color:#e2e8f0;">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control" placeholder="Name or email..." value="{{ request('search') }}" style="border-radius:0 10px 10px 0;border-color:#e2e8f0;">
                </div>
            </div>
            <div style="min-width:130px;">
                <label class="form-label small fw-semibold mb-1">Role</label>
                <select name="role" class="form-select" style="border-radius:10px;border-color:#e2e8f0;">
                    <option value="">All</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                </select>
            </div>
            <div style="min-width:130px;">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select" style="border-radius:10px;border-color:#e2e8f0;">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="min-width:130px;">
                <label class="form-label small fw-semibold mb-1">Email</label>
                <select name="verified" class="form-select" style="border-radius:10px;border-color:#e2e8f0;">
                    <option value="">All</option>
                    <option value="yes" {{ request('verified') === 'yes' ? 'selected' : '' }}>Verified</option>
                    <option value="no" {{ request('verified') === 'no' ? 'selected' : '' }}>Unverified</option>
                </select>
            </div>
            <button type="submit" class="btn btn-grab px-3" style="height:38px;">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'role', 'verified', 'status']))
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary px-3" style="height:38px;border-radius:10px;">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        @if($users->count())
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="padding:14px 20px;">User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th class="text-end" style="padding-right:20px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    @php
                        $initials = collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                        $isCurrentUser = $user->id === auth()->id();
                    @endphp
                    <tr>
                        <td style="padding:14px 20px;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:38px;height:38px;border-radius:50%;background:{{ $user->isAdmin() ? 'linear-gradient(135deg,#7c3aed,#6d28d9)' : 'linear-gradient(135deg,#00B14F,#008b3d)' }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;text-transform:uppercase;flex-shrink:0;">
                                    {{ $initials }}
                                </div>
                                <div style="min-width:0;">
                                    <div class="fw-semibold text-truncate" style="font-size:0.88rem;max-width:200px;">
                                        {{ $user->name }}
                                        @if($isCurrentUser)
                                            <span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:0.6rem;font-weight:600;vertical-align:middle;">You</span>
                                        @endif
                                    </div>
                                    <div class="text-muted text-truncate" style="font-size:0.75rem;max-width:220px;">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge" style="background:#ede9fe;color:#6d28d9;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                    <i class="bi bi-shield-fill-check me-1"></i>Admin
                                </span>
                            @else
                                <span class="badge" style="background:#f0f2f5;color:#6c757d;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                    <i class="bi bi-person me-1"></i>User
                                </span>
                            @endif
                        </td>
                        {{-- Status (Active/Inactive) toggle --}}
                        <td>
                            @if(!$isCurrentUser)
                            <div class="verify-toggle-wrap {{ $user->isActive() ? 'is-verified' : 'is-unverified' }}"
                                 onclick="openStatusModal({{ $user->id }}, {{ $user->isActive() ? 'true' : 'false' }}, '{{ addslashes($user->name) }}')">
                                <div class="verify-toggle-track">
                                    <div class="verify-toggle-thumb">
                                        <i class="bi {{ $user->isActive() ? 'bi-check-lg' : 'bi-slash-lg' }}"></i>
                                    </div>
                                </div>
                                <div class="verify-toggle-label">
                                    <span class="verify-status-text">{{ $user->isActive() ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                            @else
                            <span class="badge" style="background:#dcfce7;color:#166534;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                <i class="bi bi-check-circle-fill me-1"></i>Active
                            </span>
                            @endif
                        </td>
                        {{-- Email verification (read-only badge + resend) --}}
                        <td>
                            @if($user->hasVerifiedEmail())
                                <span class="badge" style="background:#dcfce7;color:#166534;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                    <i class="bi bi-patch-check-fill me-1"></i>Verified
                                </span>
                                <div style="font-size:0.62rem;color:#9ca3af;margin-top:2px;">{{ $user->email_verified_at->format('d M Y') }}</div>
                            @else
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" style="background:#fef3c7;color:#92400e;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                        <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                    </span>
                                    <form method="POST" action="{{ route('admin.users.resend-verification', $user) }}" class="d-inline" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:6px;padding:3px 8px;font-size:0.65rem;font-weight:600;" title="Resend verification email">
                                            <i class="bi bi-envelope-arrow-up"></i> Resend
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:0.8rem;color:#4b5563;">{{ $user->created_at?->format('d M Y') }}</div>
                            <div style="font-size:0.68rem;color:#9ca3af;">{{ $user->created_at?->diffForHumans() }}</div>
                        </td>
                        <td class="text-end" style="padding-right:20px;">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:4px 10px;font-size:0.75rem;" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius:8px;padding:4px 10px;font-size:0.75rem;" title="Send credentials"
                                    onclick="openSendCredModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}')">
                                    <i class="bi bi-send"></i>
                                </button>
                                @if(!$isCurrentUser)
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;padding:4px 10px;font-size:0.75rem;" title="Delete"
                                    onclick="openDeleteModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-3 border-top">
            {{ $users->links() }}
        </div>
        @endif

        @else
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p class="text-muted mb-0">No users found</p>
        </div>
        @endif
    </div>
</div>

<!-- Custom Confirm Overlay (no Bootstrap Modal dependency) -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-card" id="confirmCard">
        <div class="vm-header" id="cfmHeader">
            <div class="vm-icon" id="cfmIcon"><i class="bi bi-shield-fill-check"></i></div>
            <h6 class="vm-title" id="cfmTitle">Confirm</h6>
            <p class="vm-desc" id="cfmDesc">Are you sure?</p>
        </div>
        <div class="vm-body">
            <div class="vm-user-pill">
                <i class="bi bi-person-fill"></i>
                <div style="flex:1;min-width:0;">
                    <div id="cfmUserName" style="font-weight:600;">—</div>
                    <div id="cfmUserEmail" style="font-size:0.72rem;color:#6b7280;font-weight:400;display:none;">—</div>
                </div>
            </div>
            <div id="cfmWarning" class="p-2 mt-2 rounded-2 d-flex align-items-start gap-2" style="background:#fef2f2;border:1px solid #fecaca;font-size:0.72rem;color:#991b1b;display:none;">
                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                <span id="cfmWarningText"></span>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn flex-grow-1 vm-btn-cancel" onclick="closeConfirm()">Cancel</button>
                <form method="POST" id="cfmForm" class="flex-grow-1" style="margin:0;">
                    @csrf
                    <input type="hidden" name="_method" id="cfmMethod" value="PATCH">
                    <button type="submit" class="btn w-100 vm-btn-confirm" id="cfmConfirmBtn">
                        <i class="bi bi-check-lg me-1"></i> <span id="cfmConfirmText">Confirm</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
/* ── Verification Toggle ── */
.verify-toggle-wrap {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 6px 12px 6px 6px;
    border-radius: 999px;
    transition: all 0.2s;
    user-select: none;
}
.verify-toggle-wrap:hover { background: #f9fafb; }

.verify-toggle-track {
    width: 44px;
    height: 24px;
    border-radius: 999px;
    position: relative;
    transition: background 0.3s;
}
.is-verified .verify-toggle-track {
    background: linear-gradient(135deg, #00B14F, #16a34a);
    box-shadow: 0 2px 8px rgba(0, 177, 79, 0.3);
}
.is-unverified .verify-toggle-track {
    background: #d1d5db;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.verify-toggle-thumb {
    position: absolute;
    top: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
}
.is-verified .verify-toggle-thumb { left: 22px; }
.is-verified .verify-toggle-thumb i { color: #16a34a; font-size: 0.65rem; }
.is-unverified .verify-toggle-thumb { left: 2px; }
.is-unverified .verify-toggle-thumb i { color: #9ca3af; font-size: 0.65rem; }

.verify-toggle-label { display: flex; flex-direction: column; gap: 1px; }
.verify-status-text { font-size: 0.78rem; font-weight: 600; line-height: 1.2; }
.is-verified .verify-status-text { color: #166534; }
.is-unverified .verify-status-text { color: #92400e; }
.verify-date { font-size: 0.62rem; color: #9ca3af; }

/* ── Create Role Cards ── */
.create-role-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #6b7280;
    transition: all 0.2s;
    text-align: center;
    justify-content: center;
}
.create-role-card:hover { border-color: var(--grab-green); }
.create-role-card.selected {
    border-color: var(--grab-green);
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    color: #166534;
    box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.1);
}

/* ── Custom Confirm Overlay (100% custom, no Bootstrap Modal) ── */
.confirm-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.confirm-overlay.active {
    display: flex;
}
.confirm-card {
    background: #fff;
    border-radius: 18px;
    width: 100%;
    max-width: 360px;
    overflow: hidden;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
    animation: confirmIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes confirmIn {
    from { opacity: 0; transform: scale(0.9) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.vm-header { text-align: center; padding: 28px 24px 20px; }
.vm-header.verify-mode { background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
.vm-header.revoke-mode { background: linear-gradient(135deg, #fef2f2, #fee2e2); }
.vm-icon {
    width: 56px; height: 56px; border-radius: 16px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.6rem; margin-bottom: 10px;
}
.vm-header.verify-mode .vm-icon { background: #dcfce7; color: #16a34a; }
.vm-header.revoke-mode .vm-icon { background: #fee2e2; color: #dc2626; }
.vm-title { font-weight: 700; font-size: 1rem; color: #1f2937; margin: 0 0 4px; }
.vm-desc { font-size: 0.78rem; color: #6b7280; margin: 0; }
.vm-body { padding: 16px 24px 24px; }
.vm-user-pill {
    display: flex; align-items: center; gap: 8px;
    background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;
    padding: 10px 14px; font-size: 0.85rem; font-weight: 600; color: #1f2937;
}
.vm-user-pill i { color: #6c757d; }
.vm-btn-cancel {
    background: #f0f2f5; border: none; border-radius: 10px;
    font-weight: 600; font-size: 0.82rem; color: #6c757d; padding: 10px;
    cursor: pointer;
}
.vm-btn-cancel:hover { background: #e2e8f0; color: #1f2937; }
.vm-btn-confirm {
    border: none; border-radius: 10px; font-weight: 600;
    font-size: 0.82rem; color: #fff; padding: 10px; transition: all 0.15s;
    cursor: pointer;
}
.vm-btn-confirm.verify-action {
    background: linear-gradient(135deg, #00B14F, #16a34a);
    box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3);
}
.vm-btn-confirm.verify-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0, 177, 79, 0.4);
}
.vm-btn-confirm.revoke-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.vm-btn-confirm.revoke-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
}
@endpush

<!-- Send Credentials Overlay -->
<div class="confirm-overlay" id="sendCredOverlay">
    <div class="confirm-card" style="max-width:420px;">
        <div class="vm-header" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
            <div class="vm-icon" style="background:#dbeafe;color:#2563eb;">
                <i class="bi bi-send-fill"></i>
            </div>
            <h6 class="vm-title">Send Credentials</h6>
            <p class="vm-desc">Set a new password and send login details via email</p>
        </div>
        <div class="vm-body" style="padding:20px 24px 24px;">
            <div class="vm-user-pill mb-3">
                <i class="bi bi-person-fill"></i>
                <div style="flex:1;min-width:0;">
                    <div id="scUserName" style="font-weight:600;">—</div>
                    <div id="scUserEmail" style="font-size:0.72rem;color:#6b7280;font-weight:400;">—</div>
                </div>
            </div>

            <form method="POST" id="scForm">
                @csrf

                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">
                        Password <span class="text-danger">*</span>
                    </label>
                    <div style="position:relative;">
                        <i class="bi bi-lock-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#d97706;"></i>
                        <input type="text" name="new_password" class="form-control" required minlength="8" style="padding-left:36px;padding-right:40px;" id="scPwdInput">
                        <button type="button" class="btn btn-sm" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);color:#d97706;border:none;" onclick="generateScPwd()" title="Generate random password">
                            <i class="bi bi-magic"></i>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:0.65rem;">This will become the user's new password</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">
                        Send to email <span class="text-danger">*</span>
                    </label>
                    <div style="position:relative;">
                        <i class="bi bi-envelope-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#2563eb;"></i>
                        <input type="email" name="send_to_email" class="form-control" placeholder="recipient@email.com" required style="padding-left:36px;" id="scSendTo">
                    </div>
                    <small class="text-muted" style="font-size:0.65rem;">The email address that will receive the credentials</small>
                </div>

                <div class="p-2 rounded-2 mb-3 small d-flex align-items-start gap-2" style="background:#fef3c7;border:1px solid #fde68a;font-size:0.7rem;color:#92400e;">
                    <i class="bi bi-info-circle-fill mt-1"></i>
                    <span>The email will contain the login email + new password. The user's current password will be replaced.</span>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn flex-grow-1 vm-btn-cancel" onclick="closeSendCredModal()">Cancel</button>
                    <button type="submit" class="btn flex-grow-1" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:10px;font-weight:600;font-size:0.82rem;padding:10px;box-shadow:0 4px 12px rgba(37,99,235,0.3);">
                        <i class="bi bi-send me-1"></i> Send Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create User Overlay -->
<div class="confirm-overlay" id="createOverlay">
    <div class="confirm-card" style="max-width:460px;">
        <div class="vm-header verify-mode" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
            <div class="vm-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h6 class="vm-title">Create New User</h6>
            <p class="vm-desc">Add a new account and send credentials via email</p>
        </div>
        <div class="vm-body" style="padding:20px 24px 24px;">
            <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
                @csrf

                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold mb-1">Full Name <span class="text-danger">*</span></label>
                        <div style="position:relative;">
                            <i class="bi bi-person-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                            <input type="text" name="name" class="form-control" placeholder="John Doe" required style="padding-left:36px;">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">Email <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <i class="bi bi-envelope-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                        <input type="email" name="email" class="form-control" placeholder="user@grabtaxi.com" required style="padding-left:36px;">
                    </div>
                    <small class="text-muted" style="font-size:0.68rem;">Must use @grabtaxi.com domain</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">Password <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <i class="bi bi-lock-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;"></i>
                        <input type="text" name="password" class="form-control" placeholder="Min 8 chars, letters + numbers" required minlength="8" style="padding-left:36px;" id="createPwdInput">
                        <button type="button" class="btn btn-sm" style="position:absolute;right:4px;top:50%;transform:translateY(-50%);color:#9ca3af;border:none;font-size:0.75rem;" onclick="generatePassword()" title="Generate random password">
                            <i class="bi bi-magic"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">Role</label>
                    <div class="d-flex gap-2">
                        <label class="flex-grow-1" style="cursor:pointer;">
                            <input type="radio" name="role" value="user" checked class="d-none" id="createRoleUser">
                            <div class="create-role-card" id="createRoleUserCard">
                                <i class="bi bi-person" style="color:#6c757d;"></i>
                                <span>User</span>
                            </div>
                        </label>
                        <label class="flex-grow-1" style="cursor:pointer;">
                            <input type="radio" name="role" value="admin" class="d-none" id="createRoleAdmin">
                            <div class="create-role-card" id="createRoleAdminCard">
                                <i class="bi bi-shield-fill-check" style="color:#7c3aed;"></i>
                                <span>Admin</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded-3" style="background:#f0f9ff;border:1px solid #bae6fd;">
                    <label class="form-label small fw-semibold mb-1" style="color:#0369a1;">
                        <i class="bi bi-send-fill me-1"></i> Send credentials to <small class="fw-normal text-muted">(optional)</small>
                    </label>
                    <div style="position:relative;">
                        <i class="bi bi-at" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#0ea5e9;"></i>
                        <input type="email" name="notify_email" class="form-control" placeholder="personal@gmail.com or leave empty" style="padding-left:36px;">
                    </div>
                    <small class="text-muted" style="font-size:0.65rem;">
                        If empty, credentials will be sent to the user's @grabtaxi.com email. Use a different email if the user can't access their work email yet.
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="auto_verify" value="1" id="autoVerify" checked>
                    <label class="form-check-label small" for="autoVerify">
                        <span class="fw-semibold">Auto-verify email</span>
                        <span class="text-muted d-block" style="font-size:0.68rem;">Skip email verification — user can login immediately</span>
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn flex-grow-1 vm-btn-cancel" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn flex-grow-1 vm-btn-confirm verify-action">
                        <i class="bi bi-person-plus me-1"></i> Create & Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const overlay = document.getElementById('confirmOverlay');
    const createOverlay = document.getElementById('createOverlay');
    const sendCredOverlay = document.getElementById('sendCredOverlay');

    function openSendCredModal(userId, userName, userEmail) {
        document.getElementById('scUserName').textContent = userName;
        document.getElementById('scUserEmail').textContent = userEmail;
        document.getElementById('scForm').action = '/admin/users/' + userId + '/send-credentials';
        document.getElementById('scSendTo').value = '';
        generateScPwd();
        sendCredOverlay.classList.add('active');
    }

    function closeSendCredModal() { sendCredOverlay.classList.remove('active'); }

    sendCredOverlay.addEventListener('click', function(e) {
        if (e.target === sendCredOverlay) closeSendCredModal();
    });

    function generateScPwd() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
        let pwd = '';
        for (let i = 0; i < 10; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        pwd = pwd.substring(0, 8) + 'A' + '3' + '!';
        document.getElementById('scPwdInput').value = pwd;
    }

    function openCreateModal() { createOverlay.classList.add('active'); }
    function closeCreateModal() { createOverlay.classList.remove('active'); }

    createOverlay.addEventListener('click', function(e) {
        if (e.target === createOverlay) closeCreateModal();
    });

    // Generate random password
    function generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$';
        let pwd = '';
        for (let i = 0; i < 12; i++) pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        // Ensure at least 1 letter + 1 number
        pwd = pwd.substring(0, 10) + 'A' + '3';
        document.getElementById('createPwdInput').value = pwd;
    }

    // Role card toggle visual
    document.querySelectorAll('input[name="role"]').forEach(r => {
        r.addEventListener('change', () => {
            document.querySelectorAll('.create-role-card').forEach(c => c.classList.remove('selected'));
            r.nextElementSibling.classList.add('selected');
        });
    });
    // Init selected state
    document.querySelector('input[name="role"]:checked')?.nextElementSibling?.classList.add('selected');

    function closeConfirm() {
        overlay.classList.remove('active');
    }

    // Close on backdrop click (not card)
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeConfirm();
    });

    // Close on Escape — any active overlay
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (overlay.classList.contains('active')) closeConfirm();
            if (createOverlay.classList.contains('active')) closeCreateModal();
            if (sendCredOverlay.classList.contains('active')) closeSendCredModal();
        }
    });

    function openDeleteModal(userId, userName, userEmail) {
        document.getElementById('cfmHeader').className = 'vm-header revoke-mode';
        document.getElementById('cfmIcon').innerHTML = '<i class="bi bi-trash3-fill"></i>';
        document.getElementById('cfmTitle').textContent = 'Delete User';
        document.getElementById('cfmDesc').textContent = 'This action cannot be undone.';
        document.getElementById('cfmUserName').textContent = userName;
        const emailEl = document.getElementById('cfmUserEmail');
        emailEl.textContent = userEmail;
        emailEl.style.display = 'block';
        const warningEl = document.getElementById('cfmWarning');
        document.getElementById('cfmWarningText').textContent = 'All data associated with this user including activity logs will be permanently removed.';
        warningEl.style.display = 'flex';
        document.getElementById('cfmMethod').value = 'DELETE';
        document.getElementById('cfmForm').action = '/admin/users/' + userId;
        document.getElementById('cfmConfirmBtn').className = 'btn w-100 vm-btn-confirm revoke-action';
        document.getElementById('cfmConfirmText').textContent = 'Delete';
        overlay.classList.add('active');
    }

    function openStatusModal(userId, isActive, userName) {
        document.getElementById('cfmUserName').textContent = userName;
        document.getElementById('cfmUserEmail').style.display = 'none';
        document.getElementById('cfmWarning').style.display = 'none';
        document.getElementById('cfmMethod').value = 'PATCH';
        document.getElementById('cfmForm').action = '/admin/users/' + userId + '/toggle-status';

        if (isActive) {
            document.getElementById('cfmHeader').className = 'vm-header revoke-mode';
            document.getElementById('cfmIcon').innerHTML = '<i class="bi bi-slash-circle-fill"></i>';
            document.getElementById('cfmTitle').textContent = 'Deactivate User';
            document.getElementById('cfmDesc').textContent = 'This user will not be able to log in.';
            document.getElementById('cfmConfirmBtn').className = 'btn w-100 vm-btn-confirm revoke-action';
            document.getElementById('cfmConfirmText').textContent = 'Deactivate';
            document.getElementById('cfmWarning').style.display = 'flex';
            document.getElementById('cfmWarningText').textContent = 'The user will be logged out and blocked from signing in until reactivated.';
        } else {
            document.getElementById('cfmHeader').className = 'vm-header verify-mode';
            document.getElementById('cfmIcon').innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            document.getElementById('cfmTitle').textContent = 'Activate User';
            document.getElementById('cfmDesc').textContent = 'Re-enable access for this user?';
            document.getElementById('cfmConfirmBtn').className = 'btn w-100 vm-btn-confirm verify-action';
            document.getElementById('cfmConfirmText').textContent = 'Activate';
        }

        overlay.classList.add('active');
    }
</script>
@endpush
@endsection
