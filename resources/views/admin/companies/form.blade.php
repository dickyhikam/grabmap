<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($company) ? 'Edit ' . $company->name : 'Tambah Company' }} | GrabMaps Admin</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
        }

        .navbar-brand img {
            height: 28px;
        }

        .logo-preview-wrap {
            width: 80px;
            height: 80px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            overflow: hidden;
        }

        .logo-preview-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .feature-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.15s;
        }

        .feature-card:has(input[type=checkbox]:checked) {
            border-color: #198754;
            background: #f0faf4;
        }

        .feature-sub {
            font-size: 0.875rem;
        }

        .url-preview {
            font-family: monospace;
            font-size: 0.9rem;
            background: #f1f3f5;
            border-radius: 6px;
            padding: 6px 12px;
            color: #495057;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #00B14F;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('admin.companies.index') }}">
                <img src="{{ asset('logo.png') }}" alt="Grab">
                <span class="fw-semibold" style="font-size:0.95rem;">Maps Admin</span>
            </a>
        </div>
    </nav>

    <div class="container py-4" style="max-width: 680px;">

        <div class="mb-4">
            <a href="{{ route('admin.companies.index') }}" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <h4 class="fw-bold mt-2 mb-0">{{ isset($company) ? 'Edit Company' : 'Tambah Company Baru' }}</h4>
            @isset($company)
            <small class="text-muted">{{ $company->name }}</small>
            @endisset
        </div>

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php
        $isEdit = isset($company);
        $action = $isEdit ? route('admin.companies.update', $company) : route('admin.companies.store');
        $features = ['search', 'route', 'reverse_geocode', 'route_matrix'];

        $featureInfo = [
        'search' => [
        'icon' => 'bi-search',
        'label' => 'Search / Autocomplete',
        'desc' => 'Pencarian tempat dengan autocomplete',
        'sub' => 'language',
        ],
        'route' => [
        'icon' => 'bi-sign-turn-right',
        'label' => 'Route Calculation',
        'desc' => 'Hitung rute antara dua titik',
        'sub' => 'modes',
        ],
        'reverse_geocode' => [
        'icon' => 'bi-geo-alt',
        'label' => 'Reverse Geocode',
        'desc' => 'Klik peta untuk mendapatkan alamat',
        'sub' => 'language',
        ],
        'route_matrix' => [
        'icon' => 'bi-grid-3x3',
        'label' => 'Route Matrix',
        'desc' => 'Multi-destinasi routing',
        'sub' => 'modes',
        ],
        ];

        $travelModes = [
        'Car' => ['icon' => 'bi-car-front-fill', 'label' => 'Car'],
        'Motorcycle' => ['icon' => 'bi-bicycle', 'label' => 'Motorcycle'],
        'Walking' => ['icon' => 'bi-person-walking', 'label' => 'Walking'],
        ];

        $enabledFeatures = old('features', $isEdit
        ? $company->features->where('is_enabled', true)->pluck('feature_key')->toArray()
        : $features
        );

        // Default settings for each feature
        $defaultSettings = [
        'search' => ['language' => 'id'],
        'route' => ['modes' => ['Car', 'Motorcycle']],
        'reverse_geocode' => ['language' => 'id'],
        'route_matrix' => ['modes' => ['Car', 'Motorcycle']],
        ];

        // Load saved settings (edit mode) or use defaults (create mode)
        $savedSettings = $isEdit
        ? $company->features->mapWithKeys(fn($f) => [$f->feature_key => $f->settings ?? []])->toArray()
        : [];

        $featureSettings = old('feature_settings', array_merge($defaultSettings, $savedSettings));
        @endphp

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Informasi Company</h6>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Nama Company <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $isEdit ? $company->name : '') }}"
                            placeholder="e.g. TransJakarta" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Slug (URL) <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                            value="{{ old('slug', $isEdit ? $company->slug : '') }}"
                            placeholder="e.g. transjakarta" required
                            pattern="[a-z0-9\-]+" title="Hanya huruf kecil, angka, dan tanda hubung">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="mt-2">
                            <small class="text-muted">URL yang dihasilkan: </small>
                            <span class="url-preview">{{ url('/') }}/<span id="slugDisplay">{{ old('slug', $isEdit ? $company->slug : 'slug-company') }}</span></span>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-medium">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                {{ old('is_active', $isEdit ? $company->is_active : true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Aktif (company page dapat diakses)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Logo Company</h6>
                    <small class="text-muted d-block mb-3">
                        Akan ditampilkan sebagai "Grab × [Logo Company]". Format: PNG, JPG, SVG, WebP. Max 2MB.
                        @if($isEdit && $company->logo_path)
                        Logo saat ini akan diganti jika Anda memilih file baru.
                        @endif
                    </small>

                    <div class="d-flex align-items-center gap-3">
                        <div class="logo-preview-wrap">
                            @if($isEdit && $company->logo_path)
                            <img id="logoPreviewImg" src="{{ Storage::url($company->logo_path) }}" alt="{{ $company->name }}">
                            <i class="bi bi-image text-muted fs-4" id="logoPlaceholder" style="display:none;"></i>
                            @else
                            <i class="bi bi-image text-muted fs-4" id="logoPlaceholder"></i>
                            <img id="logoPreviewImg" src="" alt="Preview" style="display:none;">
                            @endif
                        </div>
                        <div>
                            <input type="file" name="logo" id="logo"
                                class="form-control @error('logo') is-invalid @enderror"
                                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                style="max-width:280px;">
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Fitur Maps</h6>
                    <small class="text-muted d-block mb-3">Pilih fitur dan konfigurasi detail masing-masing.</small>

                    <div class="d-flex flex-column gap-2">
                        @foreach($features as $key)
                        @php
                        $isChecked = in_array($key, $enabledFeatures);
                        $subType = $featureInfo[$key]['sub'];
                        $settings = $featureSettings[$key] ?? $defaultSettings[$key];
                        @endphp
                        <div class="feature-card" id="card-{{ $key }}">
                            <label class="d-flex align-items-start gap-3 w-100 mb-0" style="cursor:pointer;">
                                <input type="checkbox" name="features[]" value="{{ $key }}"
                                    {{ $isChecked ? 'checked' : '' }}
                                    class="form-check-input mt-1 flex-shrink-0"
                                    onchange="toggleSub('{{ $key }}', this.checked)">
                                <div class="flex-grow-1">
                                    <div class="fw-medium d-flex align-items-center gap-2">
                                        <i class="bi {{ $featureInfo[$key]['icon'] }} text-success"></i>
                                        {{ $featureInfo[$key]['label'] }}
                                    </div>
                                    <small class="text-muted">{{ $featureInfo[$key]['desc'] }}</small>
                                </div>
                            </label>

                            {{-- Sub-options --}}
                            <div class="feature-sub" id="sub-{{ $key }}" style="{{ $isChecked ? '' : 'display:none;' }}">
                                <div class="pt-2 mt-2 border-top">

                                    @if($subType === 'modes')
                                    <small class="fw-semibold text-dark d-block mb-2">Travel Mode:</small>
                                    <div class="d-flex gap-3 flex-wrap">
                                        @foreach($travelModes as $mode => $modeInfo)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                name="feature_settings[{{ $key }}][modes][]"
                                                value="{{ $mode }}"
                                                id="{{ $key }}_mode_{{ strtolower($mode) }}"
                                                {{ in_array($mode, $settings['modes'] ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $key }}_mode_{{ strtolower($mode) }}">
                                                <i class="bi {{ $modeInfo['icon'] }} text-success me-1"></i>{{ $modeInfo['label'] }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>

                                    @elseif($subType === 'language')
                                    <small class="fw-semibold text-dark d-block mb-2">Bahasa Hasil:</small>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="feature_settings[{{ $key }}][language]"
                                                value="id" id="{{ $key }}_lang_id"
                                                {{ ($settings['language'] ?? 'id') === 'id' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $key }}_lang_id">Indonesia</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="feature_settings[{{ $key }}][language]"
                                                value="en" id="{{ $key }}_lang_en"
                                                {{ ($settings['language'] ?? 'id') === 'en' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $key }}_lang_en">English</label>
                                        </div>
                                    </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Company' }}
                </button>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const slugInput = document.getElementById('slug');
        const slugDisplay = document.getElementById('slugDisplay');

        @if(!isset($company))
        // Auto-generate slug dari nama (hanya untuk create)
        let slugManuallyEdited = false;
        document.getElementById('name').addEventListener('input', function() {
            if (!slugManuallyEdited) {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                slugInput.value = slug;
                slugDisplay.textContent = slug || 'slug-company';
            }
        });
        slugInput.addEventListener('input', function() {
            slugManuallyEdited = true;
            slugDisplay.textContent = this.value || 'slug-company';
        });
        @else
        slugInput.addEventListener('input', function() {
            slugDisplay.textContent = this.value || 'slug-company';
        });
        @endif

        // Toggle feature sub-options
        function toggleSub(key, visible) {
            const sub = document.getElementById('sub-' + key);
            if (sub) sub.style.display = visible ? '' : 'none';
        }

        // Logo preview
        document.getElementById('logo').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('logoPreviewImg').src = e.target.result;
                    document.getElementById('logoPreviewImg').style.display = 'block';
                    document.getElementById('logoPlaceholder').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>