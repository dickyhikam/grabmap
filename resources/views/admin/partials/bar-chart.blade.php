{{--
    Bar chart harian — dipakai dashboard maupun halaman pemakaian API key.

    Pemakaian:
        @include('admin.partials.bar-chart', [
            'series' => $collectionTanggalKeJumlah,   // ['2026-08-01' => 1234, ...]
            'pane'   => '7',        // opsional, penanda untuk tombol tab
            'dense'  => false,      // batang tipis untuk rentang panjang
            'hidden' => false,
        ])

    Badge angka + knob muncul saat hover; tanggal lengkapnya ditulis ulang di label
    sumbu bawah lewat JS (penting untuk rentang panjang yang labelnya diselang-seling).
--}}
@php
    $bcData   = $series;
    $bcDense  = $dense ?? ($bcData->count() > 14);
    $bcPane   = $pane ?? 'all';
    $bcHidden = $hidden ?? false;

    $bcMax  = $bcData->max() ?: 1;
    $bcMag  = pow(10, floor(log10(max($bcMax, 1))));
    $bcStep = max($bcMag / 2, 1);
    $bcAxis = max(ceil($bcMax / $bcStep) * $bcStep, 1);

    // Label sumbu X diselang supaya tidak lebih dari ±8 label.
    $bcEvery = max(1, (int) ceil($bcData->count() / 8));

    // Skala log dipakai kalau rentang nilainya lebar — batang 300 tidak boleh
    // terlihat sama dengan batang 0 saat puncaknya 18.000.
    $bcNonZero = $bcData->filter(fn ($v) => $v > 0);
    $bcMin     = $bcNonZero->count() ? $bcNonZero->min() : 0;
    $bcWide    = $bcMin > 0 && $bcMax / $bcMin >= 20;
    $bcLogMax  = log10(1 + $bcMax);

    $bcShort = function ($n) {
        if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'jt';
        if ($n >= 1000)    return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
        return (string) round($n);
    };
@endphp

@once
@push('styles')
    /* Ruang di atas plot dipesan untuk badge hover supaya tidak menabrak judul kartu. */
    .q-chart { display: flex; gap: 10px; margin-top: 30px; }
    .q-yaxis {
        display: flex; flex-direction: column; justify-content: space-between;
        height: 215px; font-size: 0.68rem; color: var(--muted); text-align: right; flex-shrink: 0;
    }
    .q-yaxis[hidden] { display: none; }
    .q-plot { position: relative; flex: 1; height: 215px; min-width: 0; }
    .q-gl { position: absolute; left: 0; right: 0; border-top: 1px dashed var(--line); }
    .q-bars { position: absolute; inset: 0; display: flex; align-items: flex-end; gap: 4px; }
    .q-bar-col { flex: 1; height: 100%; display: flex; align-items: flex-end; justify-content: center; min-width: 0; }
    .q-bar {
        width: 100%; max-width: 46px;
        height: var(--h-lin);
        border-radius: 13px;
        background: repeating-linear-gradient(45deg, var(--bar-a) 0 5px, var(--bar-b) 5px 10px);
        position: relative;
        transition: filter 0.15s;
    }
    /* Bar yang disentuh yang menggelap — tidak ada bar yang ditandai permanen. */
    .q-bar:hover { background: repeating-linear-gradient(45deg, var(--bar-top-a) 0 5px, var(--bar-top-b) 5px 10px); }
    .dense .q-bar { max-width: 14px; border-radius: 7px; }
    .scale-log .q-bar { height: var(--h-log); }
    /* Hari tanpa permintaan: garis dasar tipis, bukan batang — supaya beda jelas
       dengan nilai kecil yang tetap punya batang. */
    .q-bar.is-zero { height: 3px !important; background: var(--line); opacity: 0.85; }
    .q-bar.is-zero:hover { background: var(--line); }

    .q-scale {
        position: absolute; right: 0; top: -26px; z-index: 1;
        display: inline-flex; gap: 2px; padding: 2px;
        background: var(--soft, rgba(127,127,127,0.10)); border-radius: 999px;
    }
    .q-scale button {
        border: none; background: transparent; color: var(--muted);
        font-size: 0.66rem; font-weight: 700; letter-spacing: 0.02em;
        padding: 3px 10px; border-radius: 999px; cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }
    .q-scale button.on { background: var(--card); color: var(--ink); box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
    .dense .q-bars { gap: 2px; }

    .q-knob {
        position: absolute; top: -7px; left: 50%;
        width: 15px; height: 15px; border-radius: 50%;
        background: var(--bar-accent); border: 3px solid var(--card);
        box-shadow: 0 2px 6px rgba(6, 105, 52, 0.35);
        transform: translateX(-50%) scale(0.4);
        opacity: 0;
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.15s ease;
        pointer-events: none;
    }
    .q-bar-badge {
        position: absolute; bottom: calc(100% + 14px); left: 50%;
        background: var(--bar-accent); color: #fff;
        font-size: 0.66rem; font-weight: 700; line-height: 1.35;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
        transform: translateX(-50%) translateY(4px);
        opacity: 0;
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.15s ease;
        pointer-events: none;
        z-index: 3;
    }
    .q-bar-badge::after {
        content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
        border: 4px solid transparent; border-top-color: var(--bar-accent);
    }
    .q-bar:hover .q-knob { opacity: 1; transform: translateX(-50%) scale(1); }
    .q-bar:hover .q-bar-badge { opacity: 1; transform: translateX(-50%) translateY(0); }

    .q-xaxis { display: flex; gap: 4px; margin-top: 9px; }
    .q-xaxis span {
        flex: 1; text-align: center; font-size: 0.68rem; color: var(--muted);
        white-space: nowrap; overflow: hidden;
        transition: color 0.15s ease;
    }
    .dense .q-xaxis { gap: 2px; }
    /* Pada rentang padat hanya tiap label ke-N yang berisi teks, jadi membiarkannya
       meluber justru perlu — kalau dipotong, tanggalnya jadi "23/0". */
    .dense .q-xaxis span { overflow: visible; }
    .q-xaxis span.on { color: var(--ink); font-weight: 700; overflow: visible; position: relative; z-index: 2; }

    .chart-pane[hidden] { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .q-knob, .q-bar-badge { transition: opacity 0.12s ease; }
    }
@endpush
@endonce

<div class="chart-pane {{ $bcDense ? 'dense' : '' }}" data-pane="{{ $bcPane }}"
     data-suggest-scale="{{ $bcWide ? 'log' : 'lin' }}" @if($bcHidden) hidden @endif>
    <div class="q-chart">
        <div class="q-yaxis" data-axis="lin">
            @for($t = 4; $t >= 0; $t--)
                <span>{{ $bcShort($bcAxis * $t / 4) }}</span>
            @endfor
        </div>
        <div class="q-yaxis" data-axis="log" hidden>
            @for($t = 4; $t >= 0; $t--)
                <span>{{ $bcShort(round(pow(1 + $bcMax, $t / 4) - 1)) }}</span>
            @endfor
        </div>
        <div class="q-plot">
            <div class="q-scale" role="group" aria-label="Skala sumbu Y">
                <button type="button" data-scale="lin">Linear</button>
                <button type="button" data-scale="log">Log</button>
            </div>
            @for($t = 0; $t <= 4; $t++)
                <div class="q-gl" style="top: {{ $t * 25 }}%;"></div>
            @endfor
            <div class="q-bars">
                @foreach($bcData as $date => $count)
                    <div class="q-bar-col">
                        @php
                            $bcLin = $count > 0 ? max(($count / $bcAxis) * 100, 2) : 0;
                            $bcLog = $count > 0 && $bcLogMax > 0 ? max(log10(1 + $count) / $bcLogMax * 100, 2) : 0;
                        @endphp
                        <div class="q-bar {{ $count > 0 ? '' : 'is-zero' }}"
                             style="--h-lin: {{ round($bcLin, 2) }}%; --h-log: {{ round($bcLog, 2) }}%;"
                             data-label="{{ \Carbon\Carbon::parse($date)->wib()->translatedFormat('D, d M') }}">
                            <span class="q-bar-badge">{{ number_format($count) }}</span>
                            <span class="q-knob"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="q-xaxis" style="padding-left: {{ $bcDense ? 40 : 34 }}px;">
        @foreach($bcData as $date => $count)
            <span>
                @if($bcEvery === 1 || $loop->index % $bcEvery === 0)
                    {{ \Carbon\Carbon::parse($date)->translatedFormat($bcDense ? 'd/m' : 'D d') }}
                @endif
            </span>
        @endforeach
    </div>
</div>

@once
@push('scripts')
<script>
    // Skala sumbu Y: log dipilih otomatis saat sebaran nilainya lebar, kecuali
    // pengguna sudah pernah memilih sendiri.
    const gmScaleSaved = (() => {
        try { return localStorage.getItem('gm_chart_scale'); } catch (e) { return null; }
    })();

    function gmApplyScale(scale) {
        document.querySelectorAll('.chart-pane').forEach((pane) => {
            const log = scale === 'log';
            pane.classList.toggle('scale-log', log);
            pane.querySelectorAll('.q-yaxis').forEach((ax) => {
                ax.hidden = (ax.dataset.axis === 'log') !== log;
            });
            pane.querySelectorAll('.q-scale button').forEach((b) => {
                b.classList.toggle('on', b.dataset.scale === scale);
            });
        });
    }

    document.querySelectorAll('.q-scale button').forEach((btn) => {
        btn.addEventListener('click', () => {
            const scale = btn.dataset.scale;
            try { localStorage.setItem('gm_chart_scale', scale); } catch (e) {}
            gmApplyScale(scale);
        });
    });

    gmApplyScale(gmScaleSaved || (document.querySelector('.chart-pane')?.dataset.suggestScale ?? 'lin'));

    // Hover bar → angkanya di badge, tanggalnya ditulis di label sumbu bawah.
    document.querySelectorAll('.chart-pane').forEach((pane) => {
        const bars = pane.querySelectorAll('.q-bar');
        const labels = pane.querySelectorAll('.q-xaxis span');

        bars.forEach((bar, i) => {
            const label = labels[i];
            if (!label) return;

            bar.addEventListener('mouseenter', () => {
                label.dataset.orig = label.textContent;
                label.textContent = bar.dataset.label;
                label.classList.add('on');
            });

            bar.addEventListener('mouseleave', () => {
                label.textContent = label.dataset.orig ?? '';
                label.classList.remove('on');
            });
        });
    });
</script>
@endpush
@endonce
