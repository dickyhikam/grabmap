@extends('layouts.admin')

@section('title', 'Edit User')

@section('header')
<div class="page-header">
    <div class="container">
        <a href="{{ route('admin.users.index') }}" class="back-link">
            <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
        <h4 class="fw-bold mt-2 mb-1" style="font-size:1.4rem;">
            <i class="bi bi-person-gear me-2" style="opacity:0.7;"></i> Edit User
        </h4>
        <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;margin:0;">{{ $user->email }}</p>
    </div>
</div>
@endsection

@section('content')
<div class="row g-4">
    <!-- Left: Edit form -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">

                <!-- User info header -->
                @php
                    $initials = collect(explode(' ', $user->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                @endphp
                <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                    <div style="width:52px;height:52px;border-radius:50%;background:{{ $user->isAdmin() ? 'linear-gradient(135deg,#7c3aed,#6d28d9)' : 'linear-gradient(135deg,#00B14F,#008b3d)' }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;text-transform:uppercase;flex-shrink:0;">
                        {{ $initials }}
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:1rem;">{{ $user->name }}</div>
                        <div class="text-muted" style="font-size:0.78rem;">{{ $user->email }}</div>
                        <div class="d-flex gap-2 mt-1">
                            @if($user->isAdmin())
                                <span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:0.62rem;">Admin</span>
                            @else
                                <span class="badge" style="background:#f0f2f5;color:#6c757d;font-size:0.62rem;">User</span>
                            @endif
                            @if($user->hasVerifiedEmail())
                                <span class="badge" style="background:#dcfce7;color:#166534;font-size:0.62rem;"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                            @else
                                <span class="badge" style="background:#fef3c7;color:#92400e;font-size:0.62rem;">Unverified</span>
                            @endif
                            <span class="badge" style="background:#f0f2f5;color:#6c757d;font-size:0.62rem;">Joined {{ $user->created_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-person me-1" style="color:#00B14F;"></i> Full Name
                        </label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-envelope me-1" style="color:#00B14F;"></i> Email
                        </label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-shield me-1" style="color:#7c3aed;"></i> Role
                        </label>
                        <div class="d-flex gap-2">
                            <label class="role-card flex-grow-1">
                                <input type="radio" name="role" value="user" {{ old('role', $user->role) === 'user' ? 'checked' : '' }}>
                                <div class="role-card-inner">
                                    <div style="width:36px;height:36px;border-radius:10px;background:#f0f2f5;display:flex;align-items:center;justify-content:center;">
                                        <i class="bi bi-person" style="color:#6c757d;font-size:1.1rem;"></i>
                                    </div>
                                    <div>
                                        <div class="role-title">User</div>
                                        <div class="role-desc">Standard access</div>
                                    </div>
                                </div>
                            </label>
                            <label class="role-card flex-grow-1">
                                <input type="radio" name="role" value="admin" {{ old('role', $user->role) === 'admin' ? 'checked' : '' }}>
                                <div class="role-card-inner">
                                    <div style="width:36px;height:36px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                                        <i class="bi bi-shield-fill-check" style="color:#7c3aed;font-size:1.1rem;"></i>
                                    </div>
                                    <div>
                                        <div class="role-title">Admin</div>
                                        <div class="role-desc">Full access + user management</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-lock me-1" style="color:#d97706;"></i> New Password
                            <small class="text-muted fw-normal">(leave blank to keep current)</small>
                        </label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password..." autocomplete="new-password">
                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-lock me-1" style="color:#d97706;"></i> Confirm Password
                        </label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password..." autocomplete="new-password">
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-grab px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Activity log -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="font-size:0.9rem;">
                    <i class="bi bi-clock-history me-1" style="color:#0891b2;"></i> Recent Activity
                </h6>

                @if($recentLogs->count())
                <div class="activity-timeline">
                    @foreach($recentLogs as $log)
                    @php
                        $actionConfig = match($log->action) {
                            'login' => ['icon' => 'bi-box-arrow-in-right', 'color' => $log->status === 'success' ? '#16a34a' : '#dc2626', 'bg' => $log->status === 'success' ? '#dcfce7' : '#fee2e2'],
                            'register' => ['icon' => 'bi-person-plus-fill', 'color' => '#7c3aed', 'bg' => '#ede9fe'],
                            'logout' => ['icon' => 'bi-box-arrow-left', 'color' => '#6c757d', 'bg' => '#f0f2f5'],
                            'email_verification' => ['icon' => 'bi-patch-check-fill', 'color' => '#2563eb', 'bg' => '#dbeafe'],
                            'resend_verification' => ['icon' => 'bi-envelope-arrow-up', 'color' => '#d97706', 'bg' => '#fef3c7'],
                            default => ['icon' => 'bi-activity', 'color' => '#6c757d', 'bg' => '#f0f2f5'],
                        };
                    @endphp
                    <div class="activity-item">
                        <div class="activity-dot" style="background:{{ $actionConfig['bg'] }};color:{{ $actionConfig['color'] }};">
                            <i class="bi {{ $actionConfig['icon'] }}"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-action">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                @if($log->status === 'fail')
                                    <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:0.6rem;padding:2px 6px;border-radius:4px;">Failed</span>
                                @endif
                            </div>
                            @if($log->failed_reason)
                                <div class="activity-detail">{{ str_replace('_', ' ', $log->failed_reason) }}</div>
                            @endif
                            <div class="activity-meta">
                                {{ $log->created_at?->diffForHumans() }}
                                @if($log->ip_address)
                                    <span class="text-muted">· {{ $log->ip_address }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <i class="bi bi-clock-history text-muted" style="font-size:2rem;opacity:0.5;"></i>
                    <p class="text-muted small mt-2 mb-0">No activity yet</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Credential Send History -->
        @if($credentialLogs->count())
        <div class="card mt-3">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="font-size:0.9rem;">
                    <i class="bi bi-send-fill me-1" style="color:#2563eb;"></i> Credential Send History
                </h6>
                <div class="activity-timeline">
                    @foreach($credentialLogs as $clog)
                    <div class="activity-item">
                        <div class="activity-dot" style="background:{{ $clog->status === 'success' ? '#dbeafe' : '#fee2e2' }};color:{{ $clog->status === 'success' ? '#2563eb' : '#dc2626' }};">
                            <i class="bi {{ $clog->status === 'success' ? 'bi-send-check-fill' : 'bi-send-x-fill' }}"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-action">
                                Sent to <b>{{ $clog->sent_to_email }}</b>
                                @if($clog->include_password)
                                    <span class="badge" style="background:#fef3c7;color:#92400e;font-size:0.58rem;padding:2px 5px;border-radius:4px;">+ password</span>
                                @endif
                                @if($clog->status === 'fail')
                                    <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:0.58rem;padding:2px 5px;border-radius:4px;">Failed</span>
                                @endif
                            </div>
                            <div class="activity-meta">
                                {{ $clog->created_at?->diffForHumans() }}
                                · by {{ $clog->sender?->name ?? 'System' }}
                            </div>
                            @if($clog->failed_reason)
                                <div class="activity-detail">{{ Str::limit($clog->failed_reason, 60) }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
/* Role selector cards */
.role-card {
    cursor: pointer;
}
.role-card input[type="radio"] {
    display: none;
}
.role-card-inner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s;
}
.role-card:hover .role-card-inner {
    border-color: var(--grab-green);
}
.role-card input[type="radio"]:checked + .role-card-inner {
    border-color: var(--grab-green);
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.1);
}
.role-title { font-weight: 600; font-size: 0.85rem; color: #1f2937; }
.role-desc { font-size: 0.7rem; color: #6b7280; }

/* Activity timeline */
.activity-timeline { display: flex; flex-direction: column; gap: 0; }
.activity-item {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f2f5;
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.activity-content { flex: 1; min-width: 0; }
.activity-action { font-weight: 600; font-size: 0.8rem; color: #1f2937; display: flex; align-items: center; gap: 6px; }
.activity-detail { font-size: 0.72rem; color: #dc2626; margin-top: 2px; }
.activity-meta { font-size: 0.68rem; color: #9ca3af; margin-top: 2px; }
@endpush
@endsection
