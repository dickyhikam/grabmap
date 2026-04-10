@extends('layouts.admin')

@section('title', 'Edit API Key')

@section('header')
<div class="page-header">
    <div class="container">
        <a href="{{ route('admin.api-keys.index') }}" class="back-link">
            <i class="bi bi-arrow-left me-1"></i> Back to API Keys
        </a>
        <h4 class="fw-bold mt-2 mb-1" style="font-size:1.4rem;">
            <i class="bi bi-pencil-square me-2" style="opacity:0.7;"></i> Edit API Key
        </h4>
        <p style="color:rgba(255,255,255,0.7);font-size:0.85rem;margin:0;">
            <code style="background:rgba(255,255,255,0.1);padding:2px 8px;border-radius:6px;color:#fff;">{{ $keyName }}</code>
        </p>
    </div>
</div>
@endsection

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
                <form method="POST" action="{{ route('admin.api-keys.update', $keyName) }}" id="editKeyForm">
                    @csrf
                    @method('PUT')

                    <!-- Key Info (read-only) -->
                    <div class="p-3 mb-4 rounded-3" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:44px;height:44px;background:linear-gradient(135deg,#00B14F,#008b3d);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;flex-shrink:0;">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold" style="font-size:0.95rem;color:#1f2937;">{{ $key['key_name'] }}</div>
                                <div class="small text-muted" style="font-size:0.75rem;">
                                    <i class="bi bi-calendar-event me-1"></i> Created: {{ $key['create_time'] ?? '-' }}
                                </div>
                                <div class="small text-muted" style="font-size:0.75rem;">
                                    <i class="bi bi-clock-history me-1"></i> Current expiry:
                                    @if($key['expire_time'])
                                        <span class="text-warning fw-semibold">{{ $key['expire_time'] }}</span>
                                    @else
                                        <span class="text-success fw-semibold">Never expires</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">
                            <i class="bi bi-chat-text me-1" style="color:#7c3aed;"></i> Description
                        </label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description for this API key..." maxlength="1000">{{ old('description', $key['description']) }}</textarea>
                        @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    @php
                        // Determine default mode based on current key data
                        $hasExpiry = !empty($key['expire_time']);
                        $defaultMode = old('expiry_mode', $hasExpiry ? 'date' : 'never');
                        // Format current expiry for datepicker (Y-m-d H:i)
                        // If current expiry is in the past, fall back to now+90 days (must be future)
                        $currentExpiryCarbon = $hasExpiry ? \Carbon\Carbon::parse($key['expire_time']) : null;
                        $currentExpiryFormatted = ($currentExpiryCarbon && $currentExpiryCarbon->isFuture())
                            ? $currentExpiryCarbon->format('Y-m-d H:i')
                            : now()->addDays(90)->format('Y-m-d H:i');
                    @endphp

                    <!-- Expiry Mode -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3" style="font-size:0.85rem;">
                            <i class="bi bi-stopwatch-fill me-1" style="color:#d97706;"></i> Expiry Settings
                        </label>

                        <!-- Mode 1: Never -->
                        <div class="expiry-option" id="optNever">
                            <input type="radio" name="expiry_mode" id="expiryNever" value="never" {{ $defaultMode === 'never' ? 'checked' : '' }}>
                            <label for="expiryNever">
                                <div class="opt-icon" style="background:#dcfce7;color:#16a34a;">
                                    <i class="bi bi-infinity"></i>
                                </div>
                                <div class="opt-text">
                                    <div class="opt-title">Never Expires</div>
                                    <div class="opt-desc">Key will remain active until manually deleted</div>
                                </div>
                                <i class="bi bi-check-circle-fill opt-check"></i>
                            </label>
                        </div>

                        <!-- Mode 2: Quick presets -->
                        <div class="expiry-option" id="optPreset">
                            <input type="radio" name="expiry_mode" id="expiryPreset" value="preset" {{ $defaultMode === 'preset' ? 'checked' : '' }}>
                            <label for="expiryPreset">
                                <div class="opt-icon" style="background:#dbeafe;color:#2563eb;">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </div>
                                <div class="opt-text">
                                    <div class="opt-title">Quick Preset</div>
                                    <div class="opt-desc">Choose a common expiry duration</div>
                                </div>
                                <i class="bi bi-check-circle-fill opt-check"></i>
                            </label>
                            <div class="opt-extra" id="presetExtra">
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach([['days'=>30,'label'=>'30 Days'],['days'=>90,'label'=>'90 Days'],['days'=>180,'label'=>'6 Months'],['days'=>365,'label'=>'1 Year']] as $preset)
                                    <label class="preset-chip">
                                        <input type="radio" name="preset_days" value="{{ $preset['days'] }}" {{ old('preset_days') == $preset['days'] ? 'checked' : '' }}>
                                        <span>{{ $preset['label'] }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Mode 3: Specific date -->
                        <div class="expiry-option" id="optDate">
                            <input type="radio" name="expiry_mode" id="expiryDate" value="date" {{ $defaultMode === 'date' ? 'checked' : '' }}>
                            <label for="expiryDate">
                                <div class="opt-icon" style="background:#fef3c7;color:#d97706;">
                                    <i class="bi bi-calendar3"></i>
                                </div>
                                <div class="opt-text">
                                    <div class="opt-title">Specific Date</div>
                                    <div class="opt-desc">Set a custom expiry date and time</div>
                                </div>
                                <i class="bi bi-check-circle-fill opt-check"></i>
                            </label>
                            <div class="opt-extra" id="dateExtra">
                                <!-- Custom Date Picker Card -->
                                <div class="datepicker-card">
                                    <div class="datepicker-display" onclick="document.getElementById('expireDateInput')._flatpickr.open()">
                                        <div class="dp-date-block">
                                            <div class="dp-day" id="dpDay">—</div>
                                            <div class="dp-month-year">
                                                <div class="dp-month" id="dpMonth">—</div>
                                                <div class="dp-weekday" id="dpWeekday">—</div>
                                            </div>
                                        </div>
                                        <div class="dp-divider"></div>
                                        <div class="dp-time-block">
                                            <i class="bi bi-clock dp-time-icon"></i>
                                            <div class="dp-time" id="dpTime">—</div>
                                        </div>
                                        <div class="dp-edit-icon">
                                            <i class="bi bi-pencil-fill"></i>
                                        </div>
                                    </div>
                                    <input type="text" name="expire_date" id="expireDateInput" class="dp-hidden-input"
                                        value="{{ old('expire_date', $currentExpiryFormatted) }}">
                                </div>

                                <!-- Quick action buttons -->
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="button" class="dp-quick-btn" data-quick="hour" data-amount="1"><i class="bi bi-plus-circle me-1"></i>1h</button>
                                    <button type="button" class="dp-quick-btn" data-quick="day" data-amount="1"><i class="bi bi-plus-circle me-1"></i>1d</button>
                                    <button type="button" class="dp-quick-btn" data-quick="day" data-amount="7"><i class="bi bi-plus-circle me-1"></i>1w</button>
                                    <button type="button" class="dp-quick-btn" data-quick="day" data-amount="30"><i class="bi bi-plus-circle me-1"></i>1mo</button>
                                </div>

                                <!-- Footer info -->
                                <div class="d-flex align-items-center justify-content-between mt-3 p-2 rounded-3" style="background:#fef3c7;border:1px solid #fde68a;">
                                    <small style="font-size:0.7rem;color:#78350f;">
                                        <i class="bi bi-globe2 me-1"></i> <b>{{ config('app.timezone') }}</b>
                                    </small>
                                    <small id="dateRelativeHint" style="font-size:0.72rem;font-weight:700;color:#7c3aed;">
                                        <i class="bi bi-clock-history me-1"></i> <span id="dateRelativeText">—</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        @error('expiry_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('expire_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('preset_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <!-- Note about restrictions -->
                    <div class="alert" style="background:#eef2ff;border:1px solid #c7d2fe;color:#4338ca;border-radius:12px;font-size:0.78rem;">
                        <i class="bi bi-shield-fill-check me-1"></i>
                        <b>Note:</b> Resource restrictions (Maps/Places/Routes actions) cannot be edited from here. Use AWS Console to modify permissions.
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('admin.api-keys.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
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
</div>

@push('styles')
.expiry-option {
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    margin-bottom: 10px;
    transition: all 0.2s;
    overflow: hidden;
    background: #fff;
}
.expiry-option input[type="radio"] {
    display: none;
}
.expiry-option label {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    cursor: pointer;
    margin: 0;
    transition: all 0.15s;
}
.expiry-option:hover {
    border-color: #00B14F;
}
.expiry-option input[type="radio"]:checked + label {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}
.expiry-option:has(input[type="radio"]:checked) {
    border-color: #00B14F;
    box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.1);
}
.opt-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.opt-text {
    flex: 1;
    min-width: 0;
}
.opt-title {
    font-weight: 600;
    font-size: 0.88rem;
    color: #1f2937;
    line-height: 1.3;
}
.opt-desc {
    font-size: 0.74rem;
    color: #6b7280;
    margin-top: 1px;
}
.opt-check {
    color: #00B14F;
    font-size: 1.2rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.expiry-option input[type="radio"]:checked ~ label .opt-check {
    opacity: 1;
}
.opt-extra {
    padding: 0 16px 14px 70px;
    display: none;
}
.expiry-option:has(input[type="radio"]:checked) .opt-extra {
    display: block;
}
.preset-chip {
    cursor: pointer;
}
.preset-chip input[type="radio"] {
    display: none;
}
.preset-chip span {
    display: inline-block;
    padding: 7px 14px;
    border-radius: 999px;
    background: #fff;
    border: 1.5px solid #e5e7eb;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7280;
    transition: all 0.15s;
}
.preset-chip:hover span {
    border-color: #2563eb;
    color: #2563eb;
}
.preset-chip input[type="radio"]:checked + span {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}

/* === Custom Datepicker Card === */
.datepicker-card { position: relative; }

.dp-hidden-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.datepicker-display {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 20px;
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 2px solid #fde68a;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.datepicker-display:hover {
    border-color: #d97706;
    box-shadow: 0 6px 20px rgba(217, 119, 6, 0.15);
    transform: translateY(-1px);
}

.dp-date-block {
    display: flex;
    align-items: center;
    gap: 12px;
}

.dp-day {
    font-size: 2.4rem;
    font-weight: 800;
    line-height: 1;
    color: #92400e;
    letter-spacing: -1px;
    min-width: 48px;
    text-align: center;
}

.dp-month-year {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.dp-month {
    font-size: 0.9rem;
    font-weight: 700;
    color: #78350f;
    text-transform: uppercase;
    letter-spacing: 1px;
    line-height: 1.1;
}

.dp-weekday {
    font-size: 0.7rem;
    font-weight: 600;
    color: #b45309;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.dp-divider {
    width: 1px;
    height: 44px;
    background: rgba(180, 83, 9, 0.25);
}

.dp-time-block {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dp-time-icon {
    font-size: 1.2rem;
    color: #d97706;
}

.dp-time {
    font-size: 1.4rem;
    font-weight: 700;
    color: #92400e;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.5px;
}

.dp-edit-icon {
    margin-left: auto;
    width: 36px;
    height: 36px;
    background: #fff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
    box-shadow: 0 2px 6px rgba(217, 119, 6, 0.15);
    transition: all 0.2s;
}

.datepicker-display:hover .dp-edit-icon {
    background: #d97706;
    color: #fff;
    transform: rotate(15deg);
}

/* Quick action buttons */
.dp-quick-btn {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
}
.dp-quick-btn:hover {
    border-color: #d97706;
    color: #d97706;
    background: #fef3c7;
    transform: translateY(-1px);
}
.dp-quick-btn:active {
    transform: translateY(0);
}

/* === Flatpickr custom theme === */
.flatpickr-calendar {
    background: #fff !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
    border: none !important;
    padding: 12px !important;
    font-family: 'Inter', sans-serif !important;
    z-index: 9999 !important;
    margin-top: 12px !important;
    width: 320px !important;
}
.flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after { display: none !important; }
.flatpickr-calendar.open { display: inline-block !important; }
.flatpickr-days, .dayContainer {
    width: 296px !important;
    min-width: 296px !important;
    max-width: 296px !important;
}
.flatpickr-rContainer { width: 100% !important; }

.flatpickr-months {
    padding: 8px 4px 12px !important;
}
.flatpickr-month {
    background: transparent !important;
    color: #1f2937 !important;
    font-weight: 700 !important;
}
.flatpickr-current-month {
    font-size: 0.95rem !important;
    padding: 0 !important;
    font-weight: 700 !important;
}
.flatpickr-current-month .flatpickr-monthDropdown-months {
    font-weight: 700 !important;
    color: #1f2937 !important;
    background: transparent !important;
}
.flatpickr-current-month input.cur-year {
    font-weight: 700 !important;
    color: #1f2937 !important;
}
.flatpickr-prev-month, .flatpickr-next-month {
    color: #6b7280 !important;
    fill: #6b7280 !important;
    border-radius: 8px !important;
    transition: all 0.15s !important;
    padding: 6px !important;
    width: 32px !important;
    height: 32px !important;
}
.flatpickr-prev-month:hover, .flatpickr-next-month:hover {
    background: #fef3c7 !important;
    color: #d97706 !important;
    fill: #d97706 !important;
}

.flatpickr-weekdays {
    background: transparent !important;
    padding: 4px 0 !important;
}
.flatpickr-weekday {
    color: #9ca3af !important;
    font-weight: 700 !important;
    font-size: 0.7rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    background: transparent !important;
}

.dayContainer {
    padding: 4px !important;
}
.flatpickr-day {
    border-radius: 10px !important;
    font-weight: 600 !important;
    font-size: 0.82rem !important;
    color: #1f2937 !important;
    border: none !important;
    height: 38px !important;
    line-height: 38px !important;
    max-width: 38px !important;
    transition: all 0.15s !important;
}
.flatpickr-day:hover {
    background: #fef3c7 !important;
    color: #d97706 !important;
}
.flatpickr-day.today {
    background: transparent !important;
    color: #d97706 !important;
    border: 1.5px solid #d97706 !important;
    font-weight: 800 !important;
}
.flatpickr-day.today:hover {
    background: #fef3c7 !important;
}
.flatpickr-day.selected, .flatpickr-day.selected:hover {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
    color: #fff !important;
    border-color: transparent !important;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35) !important;
}
.flatpickr-day.flatpickr-disabled, .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay {
    color: #d1d5db !important;
}
.flatpickr-day.flatpickr-disabled:hover {
    background: transparent !important;
    cursor: not-allowed !important;
}

/* Time picker */
.flatpickr-time {
    border-top: 1px solid #e5e7eb !important;
    background: transparent !important;
    padding: 12px 8px 4px !important;
}
.flatpickr-time input {
    color: #1f2937 !important;
    font-size: 1rem !important;
    font-weight: 700 !important;
    background: #f9fafb !important;
    border-radius: 8px !important;
    height: 36px !important;
    padding: 0 8px !important;
}
.flatpickr-time input:hover, .flatpickr-time input:focus {
    background: #fef3c7 !important;
}
.flatpickr-time .flatpickr-time-separator {
    color: #1f2937 !important;
    font-weight: 800 !important;
}
.flatpickr-time .flatpickr-am-pm {
    color: #1f2937 !important;
    font-weight: 700 !important;
}
.flatpickr-time .numInputWrapper:hover {
    background: transparent !important;
}
.flatpickr-time .arrowUp:after { border-bottom-color: #d97706 !important; }
.flatpickr-time .arrowDown:after { border-top-color: #d97706 !important; }
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    (function() {
        const input = document.getElementById('expireDateInput');
        const hint = document.getElementById('dateRelativeText');
        if (!input) return;

        const dpDay = document.getElementById('dpDay');
        const dpMonth = document.getElementById('dpMonth');
        const dpWeekday = document.getElementById('dpWeekday');
        const dpTime = document.getElementById('dpTime');
        const monthsShort = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
        const weekdaysShort = ['SUN','MON','TUE','WED','THU','FRI','SAT'];

        function pad(n) { return String(n).padStart(2, '0'); }

        function updateDisplay(date) {
            if (!date) { dpDay.textContent = '—'; dpMonth.textContent = '—'; dpWeekday.textContent = '—'; dpTime.textContent = '—'; return; }
            dpDay.textContent = date.getDate();
            dpMonth.textContent = monthsShort[date.getMonth()] + ' ' + date.getFullYear();
            dpWeekday.textContent = weekdaysShort[date.getDay()];
            dpTime.textContent = pad(date.getHours()) + ':' + pad(date.getMinutes());
        }

        function updateRelative(date) {
            if (!date || !hint) return;
            const now = new Date();
            const diffMs = date - now;
            if (diffMs <= 0) {
                hint.textContent = 'Past date';
                hint.parentElement.style.color = '#dc2626';
                return;
            }
            hint.parentElement.style.color = '#7c3aed';
            const diffMin = Math.floor(diffMs / 60000);
            const diffHr = Math.floor(diffMin / 60);
            const diffDay = Math.floor(diffHr / 24);
            const diffMo = Math.floor(diffDay / 30);
            const diffYr = Math.floor(diffDay / 365);
            let rel;
            if (diffYr >= 1) rel = `in ${diffYr} year${diffYr > 1 ? 's' : ''}`;
            else if (diffMo >= 1) rel = `in ${diffMo} month${diffMo > 1 ? 's' : ''}`;
            else if (diffDay >= 1) rel = `in ${diffDay} day${diffDay > 1 ? 's' : ''}`;
            else if (diffHr >= 1) rel = `in ${diffHr} hour${diffHr > 1 ? 's' : ''}`;
            else rel = `in ${diffMin} minute${diffMin > 1 ? 's' : ''}`;
            hint.textContent = rel;
        }

        const fp = flatpickr(input, {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            minDate: "today",
            minuteIncrement: 5,
            defaultDate: input.value || null,
            appendTo: document.body,
            positionElement: document.querySelector('.datepicker-display'),
            position: "below center",
            static: false,
            onChange: function(selectedDates) {
                const d = selectedDates[0];
                updateDisplay(d);
                updateRelative(d);
            },
            onReady: function(selectedDates) {
                const d = selectedDates[0];
                updateDisplay(d);
                updateRelative(d);
            }
        });

        // Quick action buttons (+1h, +1d, +1w, +1mo)
        document.querySelectorAll('.dp-quick-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const unit = btn.dataset.quick;
                const amount = parseInt(btn.dataset.amount, 10);
                const base = (fp.selectedDates[0] || new Date());
                const next = new Date(base);
                if (unit === 'hour') next.setHours(next.getHours() + amount);
                else if (unit === 'day') next.setDate(next.getDate() + amount);
                fp.setDate(next, true);
            });
        });
    })();
</script>
@endpush
@endsection
