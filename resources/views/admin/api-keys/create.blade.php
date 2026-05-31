@extends('layouts.admin')

@section('title', 'Create API Key')

@section('header')
<div class="page-header">
    <div class="container">
        <a href="{{ route('admin.api-keys.index') }}" class="back-link">
            <i class="bi bi-arrow-left me-1"></i> Back to API Keys
        </a>
        <h4 class="fw-bold mt-2 mb-1" style="font-size:1.4rem;">
            <i class="bi bi-plus-circle-fill me-2" style="opacity:0.7;"></i> Create New API Key
        </h4>
        <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;margin:0;">
            Create a new AWS Location Service API key with scoped resources
        </p>
    </div>
</div>
@endsection

@push('styles')
    .svc-card {
        position: relative;
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px;
        cursor: pointer;
        transition: all 0.15s;
        background: #fff;
        height: 100%;
    }
    .svc-card:hover { border-color: #00B14F; }
    .svc-card input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .svc-card input:checked + .svc-card-inner {
        color: #00B14F;
    }
    .svc-card:has(input:checked) {
        border-color: #00B14F;
        background: #f0fdf4;
    }
    .svc-card-inner {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    .svc-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
    }
    .svc-name {
        font-weight: 700;
        font-size: 0.92rem;
        color: #1f2937;
    }
    .svc-desc {
        font-size: 0.72rem;
        color: #6b7280;
        line-height: 1.35;
    }
    .svc-check {
        position: absolute;
        top: 10px;
        right: 10px;
        color: #00B14F;
        font-size: 1.1rem;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .svc-card:has(input:checked) .svc-check { opacity: 1; }

    .expiry-simple {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 8px;
    }
    .expiry-chip {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        cursor: pointer;
        text-align: center;
        font-size: 0.78rem;
        font-weight: 600;
        color: #374151;
        background: #fff;
        transition: all 0.15s;
        position: relative;
    }
    .expiry-chip input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .expiry-chip:hover { border-color: #00B14F; }
    .expiry-chip:has(input:checked) {
        border-color: #00B14F;
        background: #f0fdf4;
        color: #00B14F;
    }

    /* Keyboard focus indicators (WCAG 2.4.7) */
    .svc-card:has(input:focus-visible),
    .expiry-chip:has(input:focus-visible) {
        outline: 2px solid #00B14F;
        outline-offset: 2px;
        border-color: #00B14F;
    }
    /* Fallback for engines without :has() */
    .svc-card:focus-within,
    .expiry-chip:focus-within {
        outline: 2px solid #00B14F;
        outline-offset: 2px;
    }
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if(session('error'))
            <div class="alert alert-danger shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.api-keys.store') }}" id="createKeyForm">
                    @csrf

                    <!-- Key Name -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-key-fill me-1" style="color:#16a34a;"></i> Key Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="key_name" class="form-control"
                            value="{{ old('key_name') }}"
                            placeholder="e.g. transjakarta-maps-key" required maxlength="100"
                            pattern="[A-Za-z0-9_.\-]+"
                            title="Letters, digits, underscore, hyphen, period">
                        <div class="form-text" style="font-size:0.72rem;">
                            Hanya boleh: huruf, angka, <code>_</code> <code>-</code> <code>.</code> (maks 100 char). Key name tidak bisa diubah setelah dibuat.
                        </div>
                        @error('key_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-chat-text me-1" style="color:#7c3aed;"></i> Description
                        </label>
                        <textarea name="description" class="form-control" rows="2"
                            placeholder="Untuk apa key ini? (opsional)"
                            maxlength="1000">{{ old('description') }}</textarea>
                        @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Services / Resources -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2" style="font-size:0.85rem;">
                            <i class="bi bi-shield-fill-check me-1" style="color:#2563eb;"></i>
                            Allowed Services <span class="text-danger">*</span>
                        </label>
                        <div class="form-text mb-3" style="font-size:0.72rem;">
                            Pilih satu atau lebih AWS Location Service yang boleh diakses key ini. Resource ARN otomatis di-set untuk region <code>{{ $region }}</code>.
                        </div>

                        @php $oldSvc = old('services', []); @endphp

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="svc-card">
                                    <input type="checkbox" name="services[]" value="maps" {{ in_array('maps', $oldSvc) ? 'checked' : '' }}>
                                    <i class="bi bi-check-circle-fill svc-check"></i>
                                    <div class="svc-card-inner">
                                        <div class="svc-icon" style="background:linear-gradient(135deg,#16a34a,#15803d);">
                                            <i class="bi bi-map-fill"></i>
                                        </div>
                                        <div>
                                            <div class="svc-name">Maps</div>
                                            <div class="svc-desc">Tiles, styles, glyphs, sprites, static maps</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="svc-card">
                                    <input type="checkbox" name="services[]" value="places" {{ in_array('places', $oldSvc) ? 'checked' : '' }}>
                                    <i class="bi bi-check-circle-fill svc-check"></i>
                                    <div class="svc-card-inner">
                                        <div class="svc-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                                            <i class="bi bi-search"></i>
                                        </div>
                                        <div>
                                            <div class="svc-name">Places</div>
                                            <div class="svc-desc">Search, suggest, reverse-geocode</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="svc-card">
                                    <input type="checkbox" name="services[]" value="routes" {{ in_array('routes', $oldSvc) ? 'checked' : '' }}>
                                    <i class="bi bi-check-circle-fill svc-check"></i>
                                    <div class="svc-card-inner">
                                        <div class="svc-icon" style="background:linear-gradient(135deg,#ea580c,#c2410c);">
                                            <i class="bi bi-signpost-split-fill"></i>
                                        </div>
                                        <div>
                                            <div class="svc-name">Routes</div>
                                            <div class="svc-desc">Calculate routes & route matrix</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        @error('services')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('services.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>

                    <!-- AllowReferers (optional) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-globe2 me-1" style="color:#0891b2;"></i> Allowed Referers <span class="text-muted" style="font-weight:400;">(optional)</span>
                        </label>
                        <textarea name="allow_referers" class="form-control" rows="3"
                            placeholder="https://example.com/*&#10;https://*.transjakarta.co.id/*"
                            maxlength="2000">{{ old('allow_referers') }}</textarea>
                        <div class="form-text" style="font-size:0.72rem;">
                            Satu pola URL per baris. Kosongkan kalau key boleh dipakai dari domain mana saja.
                        </div>
                        @error('allow_referers')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Expiry (simple) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-2" style="font-size:0.85rem;">
                            <i class="bi bi-stopwatch-fill me-1" style="color:#d97706;"></i> Expiry
                        </label>

                        @php $defaultMode = old('expiry_mode', 'never'); @endphp

                        <div class="expiry-simple mb-2">
                            <label class="expiry-chip">
                                <input type="radio" name="expiry_mode" value="never" {{ $defaultMode === 'never' ? 'checked' : '' }}>
                                <i class="bi bi-infinity me-1"></i> Never
                            </label>
                            <label class="expiry-chip">
                                <input type="radio" name="expiry_mode" value="preset" data-preset="30" {{ $defaultMode === 'preset' && old('preset_days') == 30 ? 'checked' : '' }}>
                                30 Days
                            </label>
                            <label class="expiry-chip">
                                <input type="radio" name="expiry_mode" value="preset" data-preset="90" {{ $defaultMode === 'preset' && old('preset_days') == 90 ? 'checked' : '' }}>
                                90 Days
                            </label>
                            <label class="expiry-chip">
                                <input type="radio" name="expiry_mode" value="preset" data-preset="365" {{ $defaultMode === 'preset' && old('preset_days') == 365 ? 'checked' : '' }}>
                                1 Year
                            </label>
                            <label class="expiry-chip">
                                <input type="radio" name="expiry_mode" value="date" {{ $defaultMode === 'date' ? 'checked' : '' }}>
                                <i class="bi bi-calendar3 me-1"></i> Custom
                            </label>
                        </div>
                        <input type="hidden" name="preset_days" id="presetDaysInput" value="{{ old('preset_days') }}">
                        <div id="customDateWrap" style="display:{{ $defaultMode === 'date' ? 'block' : 'none' }};">
                            <input type="datetime-local" name="expire_date" class="form-control" id="customDateInput"
                                min="{{ now()->addMinutes(1)->format('Y-m-d\TH:i') }}"
                                value="{{ old('expire_date', now()->addDays(90)->format('Y-m-d\TH:i')) }}"
                                @disabled($defaultMode !== 'date')>
                            <div class="form-text" style="font-size:0.72rem;">Timezone: {{ config('app.timezone') }}</div>
                        </div>
                        @error('expiry_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('expire_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('preset_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Info -->
                    <div class="alert" style="background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca;border-radius:12px;font-size:0.78rem;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <b>Catatan:</b> Setelah key dibuat, kamu bisa assign ke company di halaman list. Restrictions tidak bisa diubah lewat panel ini setelah dibuat (gunakan AWS Console).
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('admin.api-keys.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-grab px-4" style="border-radius:10px;background:#00B14F;color:#fff;border:none;font-weight:600;">
                            <i class="bi bi-plus-circle-fill me-1"></i> Create Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const chips = document.querySelectorAll('.expiry-chip input[name="expiry_mode"]');
        const dateWrap = document.getElementById('customDateWrap');
        const presetInput = document.getElementById('presetDaysInput');

        const dateInput = document.getElementById('customDateInput');
        function sync() {
            const checked = document.querySelector('.expiry-chip input[name="expiry_mode"]:checked');
            if (!checked) return;
            const mode = checked.value;
            const isDate = mode === 'date';
            dateWrap.style.display = isDate ? 'block' : 'none';
            if (dateInput) dateInput.disabled = !isDate;
            presetInput.value = (mode === 'preset' && checked.dataset.preset) ? checked.dataset.preset : '';
        }

        chips.forEach(c => c.addEventListener('change', sync));
        sync();
    })();
</script>
@endpush
