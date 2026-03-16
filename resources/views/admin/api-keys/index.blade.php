@extends('layouts.admin')

@section('title', 'AWS API Keys')

@push('styles')
    .key-card {
        border-radius: 16px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        padding: 20px;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .key-card:hover {
        border-color: var(--grab-green);
        box-shadow: 0 4px 16px rgba(0, 177, 79, 0.1);
        transform: translateY(-2px);
    }
    .key-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .key-name { font-size: 1rem; font-weight: 700; color: #1a1a2e; letter-spacing: -0.01em; }
    .key-desc { font-size: 0.8rem; color: #6c757d; margin-top: 2px; }
    .key-meta { display: flex; gap: 20px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f0f2f5; }
    .key-meta-item { display: flex; flex-direction: column; gap: 1px; }
    .key-meta-label { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.06em; color: #adb5bd; font-weight: 600; }
    .key-meta-value { font-size: 0.82rem; font-weight: 500; color: #495057; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .badge-company {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 500;
        background: var(--grab-green-light); color: #0a5c2e;
    }
    .badge-unassigned {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 500;
        background: #f0f2f5; color: #adb5bd;
    }
    .key-actions { display: flex; gap: 6px; margin-top: 14px; }
    .btn-key {
        border-radius: 10px; font-size: 0.8rem; font-weight: 500; padding: 6px 14px;
        border: 1.5px solid #e2e8f0; background: #fff; color: #495057;
        transition: all 0.15s; text-decoration: none;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-key:hover { border-color: var(--grab-green); color: var(--grab-green); background: var(--grab-green-light); }
    .btn-key.btn-key-primary { border-color: var(--grab-green); color: var(--grab-green); }
    .btn-key.btn-key-primary:hover { background: var(--grab-green); color: #fff; }
    .btn-key.btn-key-danger { border-color: #fde8e8; color: #dc3545; }
    .btn-key.btn-key-danger:hover { background: #fde8e8; }
    .stat-mini {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 20px; background: rgba(255, 255, 255, 0.08);
        border-radius: 12px; backdrop-filter: blur(8px);
    }
    .stat-mini-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; font-size: 1rem;
        background: rgba(255, 255, 255, 0.12); color: rgba(255, 255, 255, 0.8);
    }
    .stat-mini-value { font-size: 1.25rem; font-weight: 700; color: #fff; }
    .stat-mini-label { font-size: 0.72rem; color: rgba(255, 255, 255, 0.5); text-transform: uppercase; letter-spacing: 0.05em; }
@endpush

@section('header')
<div class="page-header">
    <div class="container">
        <h4 class="fw-bold mb-1" style="font-size:1.4rem;">
            <i class="bi bi-key-fill me-2" style="opacity:0.7;"></i>AWS API Keys
        </h4>
        <p style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-bottom:20px;">
            Kelola API keys dari AWS Location Service — assign ke company dan lihat usage.
        </p>

        @if($hasCredentials && !$error && !empty($keys))
        @php
            $totalKeys = count($keys);
            $activeKeys = collect($keys)->filter(fn($k) => !($k['expire_time'] && \Carbon\Carbon::parse($k['expire_time'])->isPast()))->count();
            $assignedCount = $companies->whereNotNull('aws_api_key_name')->count();
        @endphp
        <div class="d-flex gap-3 flex-wrap">
            <div class="stat-mini">
                <div class="stat-mini-icon"><i class="bi bi-key"></i></div>
                <div>
                    <div class="stat-mini-value">{{ $totalKeys }}</div>
                    <div class="stat-mini-label">Total Keys</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-mini-value">{{ $activeKeys }}</div>
                    <div class="stat-mini-label">Active</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon"><i class="bi bi-link-45deg"></i></div>
                <div>
                    <div class="stat-mini-value">{{ $assignedCount }}</div>
                    <div class="stat-mini-label">Assigned</div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('content')
    @if(!$hasCredentials)
    <div class="card">
        <div class="card-body empty-state">
            <div style="width:64px; height:64px; border-radius:16px; background:#fff3e0; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="bi bi-shield-lock" style="font-size:1.5rem; color:#f59e0b;"></i>
            </div>
            <h5 class="fw-semibold mb-2">AWS Credentials Belum Dikonfigurasi</h5>
            <p class="text-muted mb-3" style="max-width:450px; margin:0 auto;">
                Untuk mengakses API keys dari AWS Location Service, isi credentials di file <code>.env</code>
            </p>
            <div class="rounded-3 p-3 mx-auto" style="max-width: 380px; text-align: left; background: #f8f9fa; font-family: monospace; font-size: 0.82rem;">
                AWS_ACCESS_KEY_ID=your_access_key<br>
                AWS_SECRET_ACCESS_KEY=your_secret_key
            </div>
            <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;">
                IAM permission: <code>geo:ListKeys</code>, <code>geo:DescribeKey</code>, <code>cloudwatch:GetMetricData</code>
            </p>
        </div>
    </div>
    @elseif($error)
    <div class="card">
        <div class="card-body empty-state">
            <div style="width:64px; height:64px; border-radius:16px; background:#fde8e8; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="bi bi-exclamation-triangle" style="font-size:1.5rem; color:#dc3545;"></i>
            </div>
            <h5 class="fw-semibold mb-2">Gagal Mengambil Data dari AWS</h5>
            <div class="alert alert-danger d-inline-block" style="border-radius:10px;">{{ $error }}</div>
            <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;">
                Pastikan IAM user memiliki permission <code>geo:ListKeys</code> dan credentials sudah benar.
            </p>
        </div>
    </div>
    @elseif(empty($keys))
    <div class="card">
        <div class="card-body empty-state">
            <div style="width:64px; height:64px; border-radius:16px; background:#f0f2f5; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="bi bi-key" style="font-size:1.5rem; color:#adb5bd;"></i>
            </div>
            <h5 class="fw-semibold mb-1">Tidak Ada API Key</h5>
            <p class="text-muted mb-0">Belum ada API key di AWS Location Service untuk region <strong>{{ config('aws.region') }}</strong>.</p>
        </div>
    </div>
    @else
    <div class="row g-3">
        @foreach($keys as $key)
        @php
            $isExpired = $key['expire_time'] && \Carbon\Carbon::parse($key['expire_time'])->isPast();
            $assignedCompany = $companies->firstWhere('aws_api_key_name', $key['key_name']);
        @endphp
        <div class="col-md-6">
            <div class="key-card">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="key-icon" style="background: {{ $isExpired ? '#fde8e8' : 'var(--grab-green-light)' }}; color: {{ $isExpired ? '#dc3545' : 'var(--grab-green)' }};">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <div>
                            <div class="key-name">{{ $key['key_name'] }}</div>
                            <div class="key-desc">{{ $key['description'] ?: 'No description' }}</div>
                        </div>
                    </div>
                    <span class="status-dot" style="background:{{ $isExpired ? '#dc3545' : 'var(--grab-green)' }};" title="{{ $isExpired ? 'Expired' : 'Active' }}"></span>
                </div>

                <div class="mt-3">
                    @if($assignedCompany)
                        <span class="badge-company"><i class="bi bi-building"></i> {{ $assignedCompany->name }}</span>
                    @else
                        <span class="badge-unassigned"><i class="bi bi-dash-circle"></i> Belum di-assign</span>
                    @endif
                </div>

                <div class="key-meta">
                    <div class="key-meta-item">
                        <span class="key-meta-label">Status</span>
                        <span class="key-meta-value" style="color:{{ $isExpired ? '#dc3545' : 'var(--grab-green)' }};">{{ $isExpired ? 'Expired' : 'Active' }}</span>
                    </div>
                    <div class="key-meta-item">
                        <span class="key-meta-label">Dibuat</span>
                        <span class="key-meta-value">{{ $key['create_time'] ? \Carbon\Carbon::parse($key['create_time'])->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="key-meta-item">
                        <span class="key-meta-label">Expire</span>
                        <span class="key-meta-value">{{ $key['expire_time'] ? \Carbon\Carbon::parse($key['expire_time'])->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="key-meta-item">
                        <span class="key-meta-label">Region</span>
                        <span class="key-meta-value">{{ config('aws.region') }}</span>
                    </div>
                </div>

                <div class="key-actions">
                    <a href="{{ route('admin.api-keys.usage', $key['key_name']) }}" class="btn-key"><i class="bi bi-bar-chart"></i> Usage</a>
                    @if(!$isExpired)
                    <button type="button" class="btn-key btn-key-primary" data-bs-toggle="modal" data-bs-target="#assignModal" data-key-name="{{ $key['key_name'] }}">
                        <i class="bi bi-link-45deg"></i> Assign
                    </button>
                    @endif
                    @if($assignedCompany)
                    <form method="POST" action="{{ route('admin.api-keys.unassign') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="company_id" value="{{ $assignedCompany->id }}">
                        <button type="submit" class="btn-key btn-key-danger"><i class="bi bi-x-lg"></i> Unassign</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Assign Modal --}}
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.api-keys.assign') }}">
                    @csrf
                    <input type="hidden" name="key_name" id="modalKeyName">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold">Assign API Key</h5>
                            <small class="text-muted">Pilih company untuk key ini</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="p-3 rounded-3 mb-3" style="background:#f8f9fa;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-key-fill" style="color:var(--grab-green);"></i>
                                <span class="fw-semibold" id="modalKeyNameDisplay"></span>
                            </div>
                        </div>
                        <label class="form-label fw-medium">Company</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">— Pilih Company —</option>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}">
                                {{ $company->name }}
                                @if($company->aws_api_key_name) (key: {{ $company->aws_api_key_name }}) @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" style="border-radius:10px;" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-grab px-4"><i class="bi bi-link-45deg me-1"></i>Assign Key</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    const assignModal = document.getElementById('assignModal');
    if (assignModal) {
        assignModal.addEventListener('show.bs.modal', function(event) {
            const keyName = event.relatedTarget.getAttribute('data-key-name');
            document.getElementById('modalKeyName').value = keyName;
            document.getElementById('modalKeyNameDisplay').textContent = keyName;
        });
    }
</script>
@endpush
