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
</div>
@endsection

@section('content')

<!-- Stats -->
<div class="row g-3 mb-4">
    @foreach([
        ['label' => 'Total Users', 'value' => $stats['total'], 'icon' => 'bi-people-fill', 'bg' => '#ede9fe', 'color' => '#7c3aed'],
        ['label' => 'Admins', 'value' => $stats['admins'], 'icon' => 'bi-shield-fill-check', 'bg' => '#dcfce7', 'color' => '#16a34a'],
        ['label' => 'Verified', 'value' => $stats['verified'], 'icon' => 'bi-patch-check-fill', 'bg' => '#dbeafe', 'color' => '#2563eb'],
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
            <div style="min-width:140px;">
                <label class="form-label small fw-semibold mb-1">Verified</label>
                <select name="verified" class="form-select" style="border-radius:10px;border-color:#e2e8f0;">
                    <option value="">All</option>
                    <option value="yes" {{ request('verified') === 'yes' ? 'selected' : '' }}>Verified</option>
                    <option value="no" {{ request('verified') === 'no' ? 'selected' : '' }}>Unverified</option>
                </select>
            </div>
            <button type="submit" class="btn btn-grab px-3" style="height:38px;">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'role', 'verified']))
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
                        <th>Email Status</th>
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
                        <td>
                            @if($user->hasVerifiedEmail())
                                <span class="badge" style="background:#dcfce7;color:#166534;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                    <i class="bi bi-patch-check-fill me-1"></i>Verified
                                </span>
                            @else
                                <span class="badge" style="background:#fef3c7;color:#92400e;padding:5px 10px;border-radius:6px;font-size:0.72rem;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:0.8rem;color:#4b5563;">{{ $user->created_at?->format('d M Y') }}</div>
                            <div style="font-size:0.68rem;color:#9ca3af;">{{ $user->created_at?->diffForHumans() }}</div>
                        </td>
                        <td class="text-end" style="padding-right:20px;">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;padding:4px 10px;font-size:0.75rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.users.toggle-verification', $user) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $user->hasVerifiedEmail() ? 'btn-outline-warning' : 'btn-outline-success' }}" style="border-radius:8px;padding:4px 10px;font-size:0.75rem;" title="{{ $user->hasVerifiedEmail() ? 'Revoke verification' : 'Verify manually' }}">
                                        <i class="bi {{ $user->hasVerifiedEmail() ? 'bi-x-circle' : 'bi-check-circle' }}"></i>
                                    </button>
                                </form>
                                @if(!$isCurrentUser)
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Delete user {{ addslashes($user->name) }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px;padding:4px 10px;font-size:0.75rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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
@endsection
