{{--
    Pemilih angka yang bisa digeser — pasangan slider + kotak angka yang saling ikut.

    Pemakaian:
        @include('admin.partials.amount-slider', [
            'name'    => 'budget_usd',
            'value'   => $budget?->limit_usd,
            'max'     => 300,              // opsional (bawaan 300)
            'step'    => 5,                // opsional (bawaan 5)
            'prefix'  => '$',              // opsional, simbol di depan angka
            'unit'    => '%',              // opsional, simbol di belakang angka
            'limit'   => 100,              // opsional, batas atas yang boleh diketik
            'presets' => [25, 50, 100, 170],
            'zero'    => __('ui.no_limit'),// teks saat nilainya 0
            'suffix'  => __('ui.per_month'),
        ])

    Yang dikirim ke server tetap <input type="number" name="..."> — validasi di
    controller tidak perlu diubah. Slider-nya memakai <input type="range"> asli
    supaya papan ketik, sentuh, dan pembaca layar ikut jalan tanpa JS tambahan.
--}}
@php
    $asName    = $name;
    $asId      = 'as_' . \Illuminate\Support\Str::slug($name, '_');
    $asMax     = $max ?? 300;
    $asStep    = $step ?? 5;
    $asPrefix  = $prefix ?? '';
    $asUnit    = $unit ?? '';
    $asLimit   = $limit ?? 1000000;
    $asSuffix  = $suffix ?? '';
    $asZero    = $zero ?? '';
    $asPresets = $presets ?? [];

    // Nilai lama (validasi gagal) menang atas nilai tersimpan.
    $asValue = old($name, $value);
    $asValue = $asValue === null || $asValue === '' ? 0 : (float) $asValue;

    // Skala hanya dilebarkan kalau nilainya memang tidak muat — kalau tidak,
    // skala kecil (mis. persen 0–25) ikut membengkak jadi ratusan dan geserannya
    // jadi tumpul. Pecahannya menyesuaikan besaran skala, sama seperti di JS.
    if ($asValue > $asMax) {
        $grain = $asMax <= 50 ? 10 : 100;
        $asMax = min((int) (ceil($asValue / $grain) * $grain), $asLimit);
    }
@endphp

@once
<style>
    .as-value {
        display: flex; align-items: baseline; gap: 4px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .as-pfx { font-size: 1.05rem; font-weight: 700; color: var(--muted); }
    .as-num {
        width: 2.6ch;
        border: none; background: none; outline: none; padding: 0;
        font: inherit; font-size: 1.6rem; font-weight: 800;
        letter-spacing: -0.03em; color: var(--ink);
        -moz-appearance: textfield;
    }
    .as-num::-webkit-outer-spin-button, .as-num::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .as-num:focus { color: var(--green-text); }
    .as-unit { font-size: 1.05rem; font-weight: 700; color: var(--muted); margin-left: -1px; }
    .as-sfx { font-size: 0.75rem; font-weight: 600; color: var(--muted); margin-left: 2px; }
    .as.is-zero .as-num, .as.is-zero .as-pfx, .as.is-zero .as-unit { color: var(--faint); }
    .as.is-zero .as-sfx { display: none; }
    .as-zero { font-size: 0.75rem; font-weight: 700; color: var(--muted); margin-left: 2px; }
    .as:not(.is-zero) .as-zero { display: none; }

    /* Rel geser — dasarnya <input type="range"> asli, hanya dipoles. */
    .as-range {
        -webkit-appearance: none; appearance: none;
        width: 100%; height: 22px; margin: 10px 0 0;
        background: none; outline: none; cursor: pointer;
        display: block;
    }
    .as-range::-webkit-slider-runnable-track {
        height: 8px; border-radius: 999px;
        background: linear-gradient(90deg, var(--green) 0 var(--as-pct, 0%), var(--line) var(--as-pct, 0%) 100%);
    }
    .as-range::-moz-range-track { height: 8px; border-radius: 999px; background: var(--line); }
    .as-range::-moz-range-progress { height: 8px; border-radius: 999px; background: var(--green); }

    .as-range::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none;
        width: 20px; height: 20px; margin-top: -6px;
        border-radius: 50%; border: 3px solid #fff;
        background: var(--green); box-shadow: 0 2px 8px rgba(0, 177, 79, 0.45);
        transition: transform 0.14s cubic-bezier(0.34, 1.5, 0.5, 1), box-shadow 0.14s;
    }
    .as-range::-moz-range-thumb {
        width: 20px; height: 20px; border-radius: 50%;
        border: 3px solid #fff; background: var(--green);
        box-shadow: 0 2px 8px rgba(0, 177, 79, 0.45);
    }
    .as-range:hover::-webkit-slider-thumb { transform: scale(1.14); }
    .as-range:active::-webkit-slider-thumb { transform: scale(1.2); box-shadow: 0 0 0 8px var(--green-soft); }
    .as-range:focus-visible::-webkit-slider-thumb { box-shadow: 0 0 0 6px var(--green-soft); }

    .as.is-zero .as-range::-webkit-slider-thumb { background: var(--muted); box-shadow: none; }
    .as.is-zero .as-range::-moz-range-thumb { background: var(--muted); box-shadow: none; }

    .as-scale { display: flex; justify-content: space-between; font-size: 0.65rem; color: var(--faint); margin-top: 2px; }

    .as-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .as-chip {
        border: 1px solid var(--line); background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 5px 12px;
        font-size: 0.7rem; font-weight: 700; cursor: pointer;
        transition: all 0.15s;
    }
    .as-chip:hover { border-color: var(--green); color: var(--ink); }
    .as-chip.on { background: var(--green); border-color: var(--green); color: #fff; }

    @media (prefers-reduced-motion: reduce) {
        .as-range::-webkit-slider-thumb { transition: none; }
    }
</style>
@endonce

<div class="as {{ $asValue <= 0 ? 'is-zero' : '' }}" data-as id="{{ $asId }}">
    <div class="as-value">
        <span class="as-pfx">{{ $asPrefix }}</span>
        <input type="number" name="{{ $asName }}" class="as-num" data-as-num
               min="0" max="{{ $asLimit }}" step="{{ $asStep }}" inputmode="decimal"
               value="{{ $asValue > 0 ? rtrim(rtrim(number_format($asValue, 2, '.', ''), '0'), '.') : 0 }}">
        @if($asUnit)<span class="as-unit">{{ $asUnit }}</span>@endif
        <span class="as-sfx">{{ $asSuffix }}</span>
        <span class="as-zero">{{ $asZero }}</span>
    </div>

    <input type="range" class="as-range" data-as-range
           min="0" max="{{ $asMax }}" step="{{ $asStep }}" value="{{ min($asValue, $asMax) }}"
           data-limit="{{ $asLimit }}"
           aria-label="{{ $asName }}">

    <div class="as-scale">
        <span>{{ $asPrefix }}0{{ $asUnit }}</span>
        <span data-as-top>{{ $asPrefix }}{{ $asMax }}{{ $asUnit }}</span>
    </div>

    @if($asPresets)
        <div class="as-chips" data-as-chips>
            @foreach($asPresets as $preset)
                <button type="button" class="as-chip" data-as-preset="{{ $preset }}">{{ $asPrefix }}{{ $preset }}{{ $asUnit }}</button>
            @endforeach
            @if($asZero)
                <button type="button" class="as-chip" data-as-preset="0">{{ $asZero }}</button>
            @endif
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-as]').forEach(setup);

        function setup(root) {
            const num   = root.querySelector('[data-as-num]');
            const range = root.querySelector('[data-as-range]');
            const chips = root.querySelectorAll('[data-as-preset]');
            const scale = root.querySelector('[data-as-top]');
            const prefix = root.querySelector('.as-pfx')?.textContent ?? '';
            const unit   = root.querySelector('.as-unit')?.textContent ?? '';
            const limit  = Number(range.dataset.limit || 1000000);

            // Isian yang lebih besar dari skala akan memperlebar skalanya, bukan
            // memotong nilainya — batas $1.000 tetap bisa diketik.
            function grow(value) {
                const max = Number(range.max);
                if (value <= max) return;

                // Skala kecil (mis. persen) tumbuh per 10, skala uang per 100 —
                // dan tidak pernah melewati batas yang diizinkan controller.
                const grain = max <= 50 ? 10 : 100;
                range.max = Math.min(Math.ceil(value / grain) * grain, limit);
                if (scale) scale.textContent = prefix + range.max + unit;
            }

            function fit() {
                num.style.width = Math.max(1, String(num.value).length) + 0.35 + 'ch';
            }

            function paint(value) {
                fit();
                const pct = (value / Number(range.max)) * 100;
                range.style.setProperty('--as-pct', Math.min(pct, 100) + '%');
                root.classList.toggle('is-zero', !(value > 0));
                chips.forEach((c) => c.classList.toggle('on', Number(c.dataset.asPreset) === value));
            }

            function fromRange() {
                num.value = range.value;
                paint(Number(range.value));
            }

            function fromNum() {
                // Dijepit ke batas yang sama dengan aturan validasi controller —
                // ketikan di luar rentang diperbaiki di tempat, bukan ditolak
                // setelah formulirnya dikirim.
                const value = Math.min(Math.max(0, Number(num.value) || 0), limit);
                if (num.value !== '' && Number(num.value) !== value) {
                    num.value = value;
                }
                grow(value);
                range.value = Math.min(value, Number(range.max));
                paint(value);
            }

            range.addEventListener('input', fromRange);
            num.addEventListener('input', fromNum);
            num.addEventListener('blur', () => { if (num.value === '') { num.value = 0; fromNum(); } });

            chips.forEach((chip) => chip.addEventListener('click', () => {
                const value = Number(chip.dataset.asPreset);
                num.value = value;
                fromNum();
            }));

            fromNum();
        }
    })();
</script>
@endpush
@endonce
