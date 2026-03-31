@extends('layouts.admin')

@section('title', isset($company) ? 'Edit ' . $company->name : 'Tambah Company')

@push('styles')
    .logo-preview-wrap {
        width: 80px; height: 80px;
        border: 2px dashed #dee2e6; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: #fff; overflow: hidden;
    }
    .logo-preview-wrap img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .feature-card {
        border: 1.5px solid #e2e8f0; border-radius: 12px;
        padding: 12px 16px; transition: all 0.15s;
    }
    .feature-card:has(input[type=checkbox]:checked) { border-color: var(--grab-green); background: var(--grab-green-light); }
    .feature-sub { font-size: 0.875rem; }
    .url-preview {
        font-family: monospace; font-size: 0.9rem;
        background: #f0f2f5; border-radius: 8px;
        padding: 6px 12px; color: #495057;
    }
@endpush

@section('content')
<div style="max-width: 680px; margin: 0 auto;">
    <div class="mb-4">
        <a href="{{ route('admin.companies.index') }}" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left me-1"></i>{{ __('admin.back') }}
        </a>
        <h4 class="fw-bold mt-2 mb-0">{{ isset($company) ? __('admin.edit_company') : __('admin.add_new_company') }}</h4>
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
        'search' => ['icon' => 'bi-search', 'label' => 'Search / Autocomplete', 'desc' => 'Pencarian tempat dengan autocomplete', 'sub' => 'language'],
        'route' => ['icon' => 'bi-sign-turn-right', 'label' => 'Route Calculation', 'desc' => 'Hitung rute antara dua titik', 'sub' => 'modes'],
        'reverse_geocode' => ['icon' => 'bi-geo-alt', 'label' => 'Reverse Geocode', 'desc' => 'Klik peta untuk mendapatkan alamat', 'sub' => 'language'],
        'route_matrix' => ['icon' => 'bi-grid-3x3', 'label' => 'Route Matrix', 'desc' => 'Multi-destinasi routing', 'sub' => 'modes'],
    ];
    $travelModes = [
        'Car' => ['icon' => 'bi-car-front-fill', 'label' => 'Car'],
        'Motorcycle' => ['icon' => 'bi-bicycle', 'label' => 'Motorcycle'],
        'Walking' => ['icon' => 'bi-person-walking', 'label' => 'Walking'],
    ];
    $enabledFeatures = old('features', $isEdit ? $company->features->where('is_enabled', true)->pluck('feature_key')->toArray() : $features);
    $defaultSettings = [
        'search' => ['language' => 'id'], 'route' => ['modes' => ['Car', 'Motorcycle']],
        'reverse_geocode' => ['language' => 'id'], 'route_matrix' => ['modes' => ['Car', 'Motorcycle']],
    ];
    $savedSettings = $isEdit ? $company->features->mapWithKeys(fn($f) => [$f->feature_key => $f->settings ?? []])->toArray() : [];
    $featureSettings = old('feature_settings', array_merge($defaultSettings, $savedSettings));
    @endphp

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">{{ __('admin.company_info') }}</h6>
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('admin.company_name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $isEdit ? $company->name : '') }}" placeholder="e.g. TransJakarta" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">{{ __('admin.slug_url') }} <span class="text-danger">*</span></label>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $isEdit ? $company->slug : '') }}" placeholder="e.g. transjakarta" required
                        pattern="[a-z0-9\-]+" title="Hanya huruf kecil, angka, dan tanda hubung">
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="mt-2">
                        <small class="text-muted">URL: </small>
                        <span class="url-preview">{{ url('/') }}/<span id="slugDisplay">{{ old('slug', $isEdit ? $company->slug : 'slug-company') }}</span></span>
                    </div>
                </div>
                <div class="mb-1">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                            {{ old('is_active', $isEdit ? $company->is_active : true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('admin.status_active_desc') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-1">{{ __('admin.company_logo') }}</h6>
                <small class="text-muted d-block mb-3">{{ __('admin.logo_format') }}</small>
                <div class="d-flex align-items-center gap-3">
                    <div class="logo-preview-wrap">
                        @if($isEdit && $company->logo_path)
                        <img id="logoPreviewImg" src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}">
                        <i class="bi bi-image text-muted fs-4" id="logoPlaceholder" style="display:none;"></i>
                        @else
                        <i class="bi bi-image text-muted fs-4" id="logoPlaceholder"></i>
                        <img id="logoPreviewImg" src="" alt="Preview" style="display:none;">
                        @endif
                    </div>
                    <div>
                        <input type="file" name="logo" id="logo" class="form-control @error('logo') is-invalid @enderror"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp" style="max-width:280px;">
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-semibold mb-1">{{ __('admin.aws_api_key') }}</h6>
                <small class="text-muted d-block mb-3">{{ __('admin.aws_key_desc') }}</small>
                @if($isEdit && $company->aws_api_key_name)
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 mb-3" style="background: var(--grab-green-light); border: 1px solid #a3cfbb;">
                        <div>
                            <div class="fw-medium" style="color:var(--grab-green-dark);"><i class="bi bi-key-fill me-1"></i>{{ $company->aws_api_key_name }}</div>
                            <small class="text-muted">{{ __('admin.api_key_from_aws') }}</small>
                        </div>
                        <span class="badge {{ $company->aws_key_active ? 'bg-success' : 'bg-secondary' }}">{{ $company->aws_key_active ? __('admin.key_active') : __('admin.key_inactive') }}</span>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="aws_key_active" id="aws_key_active" value="1"
                            {{ old('aws_key_active', $company->aws_key_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="aws_key_active">API Key Aktif</label>
                    </div>
                @elseif($isEdit)
                    <div class="p-3 rounded-3 mb-3" style="background: #f8f9fa; border: 1.5px dashed #e2e8f0;">
                        <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>{{ __('admin.key_not_assigned') }}</span>
                    </div>
                @else
                    <div class="p-3 rounded-3 mb-3" style="background: #f8f9fa; border: 1.5px dashed #e2e8f0;">
                        <span class="text-muted"><i class="bi bi-info-circle me-1"></i>{{ __('admin.key_assign_after') }}</span>
                    </div>
                @endif
                <a href="{{ route('admin.api-keys.index') }}" class="btn btn-sm btn-outline-grab"><i class="bi bi-key me-1"></i>{{ __('admin.manage_keys') }}</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="fw-semibold mb-1">{{ __('admin.map_features') }}</h6>
                <small class="text-muted d-block mb-3">{{ __('admin.map_features_desc') }}</small>
                <div class="d-flex flex-column gap-2">
                    @foreach($features as $key)
                    @php
                    $isChecked = in_array($key, $enabledFeatures);
                    $subType = $featureInfo[$key]['sub'];
                    $settings = $featureSettings[$key] ?? $defaultSettings[$key];
                    @endphp
                    <div class="feature-card">
                        <label class="d-flex align-items-start gap-3 w-100 mb-0" style="cursor:pointer;">
                            <input type="checkbox" name="features[]" value="{{ $key }}" {{ $isChecked ? 'checked' : '' }}
                                class="form-check-input mt-1 flex-shrink-0" onchange="toggleSub('{{ $key }}', this.checked)">
                            <div class="flex-grow-1">
                                <div class="fw-medium d-flex align-items-center gap-2">
                                    <i class="bi {{ $featureInfo[$key]['icon'] }}" style="color:var(--grab-green);"></i>
                                    {{ $featureInfo[$key]['label'] }}
                                </div>
                                <small class="text-muted">{{ $featureInfo[$key]['desc'] }}</small>
                            </div>
                        </label>
                        <div class="feature-sub" id="sub-{{ $key }}" style="{{ $isChecked ? '' : 'display:none;' }}">
                            <div class="pt-2 mt-2 border-top">
                                @if($subType === 'modes')
                                <small class="fw-semibold text-dark d-block mb-2">{{ __('admin.travel_mode') }}</small>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach($travelModes as $mode => $modeInfo)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="feature_settings[{{ $key }}][modes][]"
                                            value="{{ $mode }}" id="{{ $key }}_mode_{{ strtolower($mode) }}"
                                            {{ in_array($mode, $settings['modes'] ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $key }}_mode_{{ strtolower($mode) }}">
                                            <i class="bi {{ $modeInfo['icon'] }} me-1" style="color:var(--grab-green);"></i>{{ $modeInfo['label'] }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @elseif($subType === 'language')
                                <small class="fw-semibold text-dark d-block mb-2">{{ __('admin.result_language') }}</small>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="feature_settings[{{ $key }}][language]"
                                            value="id" id="{{ $key }}_lang_id" {{ ($settings['language'] ?? 'id') === 'id' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $key }}_lang_id">Indonesia</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="feature_settings[{{ $key }}][language]"
                                            value="en" id="{{ $key }}_lang_en" {{ ($settings['language'] ?? 'id') === 'en' ? 'checked' : '' }}>
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
            <button type="submit" class="btn btn-grab px-4"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? __('admin.save_changes') : __('admin.save_company') }}</button>
            <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">{{ __('admin.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const slugInput = document.getElementById('slug');
    const slugDisplay = document.getElementById('slugDisplay');

    @if(!isset($company))
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

    function toggleSub(key, visible) {
        const sub = document.getElementById('sub-' + key);
        if (sub) sub.style.display = visible ? '' : 'none';
    }

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
@endpush
