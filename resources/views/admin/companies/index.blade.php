<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Company Management | GrabMaps</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
        .navbar-brand img { height: 28px; }
        .badge-active { background-color: #198754; }
        .badge-inactive { background-color: #6c757d; }
        .logo-thumb { width: 40px; height: 40px; object-fit: contain; border-radius: 6px; background: #fff; border: 1px solid #dee2e6; padding: 4px; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; }
        .feature-pill { font-size: 0.75rem; padding: 2px 8px; border-radius: 20px; border: 1px solid #dee2e6; background: #f1f3f5; color: #495057; margin: 1px; display: inline-block; }
        .feature-pill.on { background: #d1e7dd; border-color: #a3cfbb; color: #0a3622; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background: #00B14F;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('admin.companies.index') }}">
            <img src="{{ asset('logo.png') }}" alt="Grab">
            <span class="fw-semibold" style="font-size:0.95rem;">Maps Admin</span>
        </a>
        <span class="navbar-text text-white-50" style="font-size:0.8rem;">Company Management</span>
    </div>
</nav>

<div class="container py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Companies</h4>
            <small class="text-muted">Kelola perusahaan dan fitur maps mereka</small>
        </div>
        <a href="{{ route('admin.companies.create') }}" class="btn btn-success">
            <i class="bi bi-plus-lg me-1"></i>Tambah Company
        </a>
    </div>

    @if($companies->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-building fs-1 mb-3 d-block"></i>
                <p class="mb-1 fw-semibold">Belum ada company</p>
                <small>Klik "Tambah Company" untuk memulai.</small>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Company</th>
                            <th>URL</th>
                            <th>Fitur Aktif</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($company->logo_path)
                                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="logo-thumb">
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
                                <a href="{{ url('/' . $company->slug) }}" target="_blank" class="text-decoration-none text-success">
                                    <i class="bi bi-box-arrow-up-right me-1" style="font-size:0.75rem;"></i>/{{ $company->slug }}
                                </a>
                            </td>
                            <td>
                                @php
                                    $company->load('features');
                                    $featureLabels = ['search' => 'Search', 'route' => 'Route', 'reverse_geocode' => 'Rev. Geo', 'route_matrix' => 'Matrix'];
                                @endphp
                                @foreach($featureLabels as $key => $label)
                                    <span class="feature-pill {{ $company->isFeatureEnabled($key) ? 'on' : '' }}">
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                @if($company->is_active)
                                    <span class="badge badge-active">Active</span>
                                @else
                                    <span class="badge badge-inactive">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-outline-secondary me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.companies.toggle-status', $company) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if($company->is_active)
                                        <button type="submit" class="btn btn-sm btn-success" title="Nonaktifkan">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Aktifkan">
                                            <i class="bi bi-toggle-off"></i>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
