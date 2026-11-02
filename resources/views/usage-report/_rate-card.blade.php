{{-- Kartu kurs yang bisa digeser pembaca. Skrip kurs di show.blade.php mencarinya lewat [data-rate-card]. --}}
{{-- Kurs bawaan diambil dari menu Kurs & Pajak. Pembaca boleh menggesernya
     untuk hitungan kasar sendiri; angka rupiah di halaman ini ikut berubah,
     sementara angka dolarnya tidak tersentuh. --}}
<div class="q-card rate-card {{ $rateOffPeriod ? 'off' : '' }}" data-rate-card
     data-default="{{ $idrRate }}"
     data-min="{{ (int) round($idrRate * 0.8) }}"
     data-max="{{ (int) round($idrRate * 1.2) }}"
     data-total-vat="{{ $totalVat }}"
     data-sc-pct="{{ $sc['percent'] }}"
     data-sc-min-idr="{{ $scMinIdr }}">
    <div class="rate-head">
        <div>
            <div class="rate-lbl">{{ __('apikeys.rate_title') }}</div>
            <div class="rate-val">
                <span class="pfx">Rp</span>
                <input type="text" inputmode="decimal" data-rate-num value="{{ number_format($idrRate, 0, ',', '.') }}">
                <span class="sfx">/ USD</span>
            </div>
            @if($rateDate)
                <div class="rate-meta">
                    {{ __('apikeys.rate_date', ['date' => $rateDate->translatedFormat('d M Y')]) }}
                    @if($activeRate->source) · {{ $activeRate->source }} @endif
                </div>
            @endif
        </div>

        <button type="button" class="rate-reset" data-rate-reset hidden>
            <i class="bi bi-arrow-counterclockwise"></i> {{ __('apikeys.rate_reset') }}
        </button>

        <span class="rate-tag" data-rate-tag
              data-official="{{ __('apikeys.rate_official') }}"
              data-custom="{{ __('apikeys.rate_custom') }}">{{ __('apikeys.rate_official') }}</span>
    </div>

    @if($rateOffPeriod)
        <div class="rate-warn">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ __('apikeys.rate_off_period', [
                'date'   => $rateDate->translatedFormat('d M Y'),
                'period' => $rangeEnd->translatedFormat('F Y'),
            ]) }}</span>
        </div>
    @endif

    <div class="rate-track">
        <input type="range" data-rate-range
               min="{{ (int) round($idrRate * 0.8) }}"
               max="{{ (int) round($idrRate * 1.2) }}"
               step="10" value="{{ (int) $idrRate }}">
        {{-- Penanda kurs resmi: posisinya tetap, jadi kelihatan seberapa jauh
             geserannya dari angka yang dipakai sistem. --}}
        <span class="rate-mark" data-rate-mark><span class="dot"></span><span class="tx">{{ __('apikeys.rate_official') }}</span></span>
    </div>

    <div class="rate-foot">
        <span>Rp {{ number_format(round($idrRate * 0.8), 0, ',', '.') }}</span>
        <span class="hint">{{ __('apikeys.rate_hint') }}</span>
        <span>Rp {{ number_format(round($idrRate * 1.2), 0, ',', '.') }}</span>
    </div>
</div>
