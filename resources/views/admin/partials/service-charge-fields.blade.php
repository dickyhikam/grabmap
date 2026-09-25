{{--
    Field service charge untuk formulir akun AWS dan perusahaan.

    $scCompany     true = formulir perusahaan (ada pilihan "ikut akun")
    $scCurrent     ?ServiceCharge — tarif yang berlaku sekarang untuk cakupan ini
    $scAccountRule ?ServiceCharge — tarif akun (hanya untuk perusahaan)
    $scHasAccount  bool — perusahaan terhubung ke akun AWS
    $scHistory     Collection<ServiceCharge>
--}}
@php
    $scCompany     = $scCompany ?? false;
    $scCurrent     = $scCurrent ?? null;
    $scAccountRule = $scAccountRule ?? null;
    $scHasAccount  = $scHasAccount ?? true;
    $scHistory     = $scHistory ?? collect();

    $pctText = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',') . '%';
    $idrText = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $ruleText = function ($rule) use ($pctText, $idrText) {
        if (!$rule || $rule->isZero()) {
            return __('servicecharge.none');
        }
        return __('servicecharge.summary', ['pct' => $pctText($rule->percent), 'min' => $idrText($rule->monthly_min_idr)]);
    };

    $ownCustom = $scCurrent && !$scCurrent->inherits();
    $mode = old('sc_mode', $scCompany ? ($ownCustom ? 'custom' : 'inherit') : 'custom');
    $pctValue = old('sc_percent', $ownCustom ? (float) $scCurrent->percent : 0);
    $minValue = old('sc_min_idr', $ownCustom ? (float) $scCurrent->monthly_min_idr : 0);
@endphp

@push('styles')
    .sc-box { border-top: 1px solid var(--line); margin-top: 6px; padding-top: 18px; }
    .sc-head { display: flex; align-items: center; gap: 11px; margin-bottom: 14px; }
    .sc-head .ic {
        width: 36px; height: 36px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--warn-soft); color: var(--warn-fg); font-size: 0.9rem;
    }
    .sc-head .nm { font-weight: 700; font-size: 0.88rem; }
    .sc-head .sb { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }

    .sc-modes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
    @media (max-width: 620px) { .sc-modes { grid-template-columns: 1fr; } }
    .sc-mode {
        display: flex; gap: 10px; align-items: flex-start; cursor: pointer;
        border: 1px solid var(--line); border-radius: 14px; padding: 12px 14px;
        transition: border-color 0.15s, background 0.15s;
    }
    .sc-mode input { margin-top: 3px; accent-color: var(--green); }
    .sc-mode .t { font-size: 0.8rem; font-weight: 700; }
    .sc-mode .d { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }
    .sc-mode:has(input:checked) { border-color: var(--green); background: var(--green-soft); }
    .sc-mode:has(input:disabled) { opacity: 0.5; cursor: not-allowed; }

    .sc-affix { position: relative; }
    .sc-affix .form-input { padding-right: 40px; }
    .sc-affix.pre .form-input { padding-left: 40px; padding-right: 14px; }
    .sc-affix .ax {
        position: absolute; top: 0; bottom: 0; display: flex; align-items: center;
        font-size: 0.78rem; font-weight: 700; color: var(--muted); pointer-events: none;
    }
    .sc-affix:not(.pre) .ax { right: 14px; }
    .sc-affix.pre .ax { left: 14px; }

    .sc-fields[hidden] { display: none; }
    .sc-how {
        display: flex; gap: 8px; font-size: 0.72rem; line-height: 1.55; color: var(--muted);
        background: var(--surface); border-radius: 12px; padding: 10px 12px; margin-bottom: 14px;
    }
    .sc-how i { color: var(--warn-fg); }

    .sc-hist { margin-top: 4px; }
    .sc-hist .lb { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; }
    .sc-hist-row {
        display: flex; justify-content: space-between; gap: 12px;
        padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 0.76rem;
    }
    .sc-hist-row:last-child { border-bottom: none; }
    .sc-hist-row .dt { font-weight: 600; white-space: nowrap; }
    .sc-hist-row .by { color: var(--faint); font-size: 0.68rem; }
    .sc-hist-row .vl { text-align: right; }
    .sc-hist-row.now .dt::after {
        content: '•'; color: var(--green); margin-left: 6px;
    }
@endpush

<div class="sc-box" data-sc>
    <div class="sc-head">
        <span class="ic"><i class="bi bi-percent"></i></span>
        <span>
            <span class="nm d-block">{{ __('servicecharge.title') }}</span>
            <span class="sb">{{ $scCompany ? __('servicecharge.sub_company') : __('servicecharge.sub_account') }}</span>
        </span>
    </div>

    @if($scCompany)
        <div class="sc-modes">
            <label class="sc-mode">
                <input type="radio" name="sc_mode" value="inherit" @checked($mode === 'inherit')>
                <span>
                    <span class="t d-block">{{ __('servicecharge.mode_inherit') }}</span>
                    <span class="d d-block">
                        {{ $scAccountRule ? $ruleText($scAccountRule) : __('servicecharge.inherit_none') }}
                    </span>
                </span>
            </label>
            <label class="sc-mode">
                <input type="radio" name="sc_mode" value="custom" @checked($mode === 'custom')>
                <span>
                    <span class="t d-block">{{ __('servicecharge.mode_custom') }}</span>
                    <span class="d d-block">{{ __('servicecharge.zero_hint') }}</span>
                </span>
            </label>
        </div>
        @unless($scHasAccount)
            <div class="form-hint" style="margin:-8px 0 14px;">{{ __('servicecharge.no_account') }}</div>
        @endunless
    @endif

    <div class="sc-fields" data-sc-fields @if($scCompany && $mode === 'inherit') hidden @endif>
        <div class="f-row">
            <div class="form-field">
                <label class="form-label-sm">{{ __('servicecharge.percent') }}</label>
                <div class="sc-affix">
                    <input type="number" name="sc_percent" min="0" max="100" step="0.001" inputmode="decimal"
                           class="form-input @error('sc_percent') is-invalid @enderror" value="{{ $pctValue }}">
                    <span class="ax">%</span>
                </div>
                @error('sc_percent')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('servicecharge.min') }}</label>
                <div class="sc-affix pre">
                    <input type="number" name="sc_min_idr" min="0" step="1" inputmode="numeric"
                           class="form-input @error('sc_min_idr') is-invalid @enderror" value="{{ $minValue }}">
                    <span class="ax">Rp</span>
                </div>
                @error('sc_min_idr')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
            </div>
        </div>

        <div class="sc-how">
            <i class="bi bi-info-circle-fill"></i>
            <span>{{ __('servicecharge.how') }} @unless($scCompany){{ __('servicecharge.zero_hint') }}@endunless</span>
        </div>
    </div>

    <div class="form-field">
        <label class="form-label-sm">{{ __('servicecharge.effective') }}</label>
        <input type="date" name="sc_effective" class="form-input @error('sc_effective') is-invalid @enderror"
               value="{{ old('sc_effective', now()->wib()->toDateString()) }}" style="max-width:240px;">
        @error('sc_effective')
            <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
        @else
            <div class="form-hint">{{ __('servicecharge.effective_hint') }}</div>
        @enderror
    </div>

    @if($scHistory->isNotEmpty())
        <div class="sc-hist">
            <div class="lb">{{ __('servicecharge.history') }}</div>
            @foreach($scHistory as $row)
                <div @class(['sc-hist-row', 'now' => $scCurrent && $row->id === $scCurrent->id])>
                    <span>
                        <span class="dt d-block">{{ $row->effective_from->translatedFormat('d M Y') }}</span>
                        @if($row->created_by)
                            <span class="by">{{ __('servicecharge.by', ['name' => $row->created_by]) }}</span>
                        @endif
                    </span>
                    <span class="vl">{{ $row->inherits() ? __('servicecharge.history_inherit') : $ruleText($row) }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if($scCompany)
    @push('scripts')
    <script>
        // Field tarif hanya tampil saat memilih tarif khusus.
        (function () {
            const box = document.querySelector('[data-sc]');
            if (!box) return;
            const fields = box.querySelector('[data-sc-fields]');
            box.querySelectorAll('input[name="sc_mode"]').forEach((r) => {
                r.addEventListener('change', () => { fields.hidden = r.value === 'inherit' && r.checked; });
            });
        })();
    </script>
    @endpush
@endif
