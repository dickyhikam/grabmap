@extends('layouts.admin')

@section('title', 'Company Management')

@push('styles')
    .badge-active { background-color: #198754; }
    .badge-inactive { background-color: #6c757d; }
    .logo-thumb { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; background: #fff; border: 1.5px solid #e2e8f0; padding: 4px; }
    .feature-pill { font-size: 0.75rem; padding: 2px 8px; border-radius: 20px; border: 1px solid #e2e8f0; background: #f8f9fa; color: #495057; margin: 1px; display: inline-block; }
    .feature-pill.on { background: var(--grab-green-light); border-color: #a3cfbb; color: #0a5c2e; }
    .btn-action {
        border-radius: 8px; font-size: 0.8rem; padding: 5px 10px;
        border: 1.5px solid #e2e8f0; background: #fff; color: #495057;
        transition: all 0.15s;
    }
    .btn-action:hover { border-color: var(--grab-green); color: var(--grab-green); background: var(--grab-green-light); }
@endpush

@section('header')
<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1" style="font-size:1.4rem;">
                    <i class="bi bi-building me-2" style="opacity:0.7;"></i>{{ __('admin.companies_title') }}
                </h4>
                <p style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-bottom:0;">
                    {{ __('admin.companies_subtitle') }}
                </p>
            </div>
            <a href="{{ route('admin.companies.create') }}" class="btn btn-grab px-4">
                <i class="bi bi-plus-lg me-1"></i>{{ __('admin.add_company') }}
            </a>
        </div>
    </div>
</div>
@endsection

@section('content')
    @if($companies->isEmpty())
    <div class="card">
        <div class="card-body empty-state">
            <div style="width:64px; height:64px; border-radius:16px; background:#f0f2f5; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="bi bi-building" style="font-size:1.5rem; color:#adb5bd;"></i>
            </div>
            <h5 class="fw-semibold mb-1">{{ __('admin.no_companies') }}</h5>
            <p class="text-muted mb-0">{{ __('admin.no_companies_desc') }}</p>
        </div>
    </div>
    @else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('admin.company') }}</th>
                        <th>{{ __('admin.url') }}</th>
                        <th>{{ __('admin.active_features') }}</th>
                        <th>{{ __('admin.api_key') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th class="text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $company)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if($company->logo_path)
                                    <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}" class="logo-thumb">
                                @else
                                    <div class="logo-thumb d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-building"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                    <small class="text-muted">{{ $company->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ url('/' . $company->slug) }}" target="_blank" class="text-decoration-none" style="color:var(--grab-green);">
                                <i class="bi bi-box-arrow-up-right me-1" style="font-size:0.75rem;"></i>/{{ $company->slug }}
                            </a>
                        </td>
                        <td>
                            @php
                                $company->load('features');
                                $featureLabels = ['search' => 'Search', 'route' => 'Route', 'reverse_geocode' => 'Rev. Geo', 'route_matrix' => 'Matrix'];
                            @endphp
                            @foreach($featureLabels as $key => $label)
                                <span class="feature-pill {{ $company->isFeatureEnabled($key) ? 'on' : '' }}">{{ $label }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($company->aws_api_key)
                                @if($company->aws_key_active)
                                    <span class="badge" style="background:var(--grab-green);"><i class="bi bi-key-fill"></i> {{ __('admin.key_active') }}</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-key"></i> {{ __('admin.key_inactive') }}</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($company->is_active)
                                <span class="badge badge-active">{{ __('admin.active') }}</span>
                            @else
                                <span class="badge badge-inactive">{{ __('admin.inactive') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.companies.usage', $company) }}" class="btn-action me-1" title="Usage">
                                <i class="bi bi-bar-chart"></i>
                            </a>
                            <a href="{{ route('admin.companies.invoice', $company) }}" target="_blank" class="btn-action me-1" title="Invoice / PDF tagihan">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <a href="{{ route('admin.companies.edit', $company) }}" class="btn-action me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.companies.toggle-status', $company) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-action" title="{{ $company->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="bi bi-toggle-{{ $company->is_active ? 'on text-success' : 'off' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endsection
