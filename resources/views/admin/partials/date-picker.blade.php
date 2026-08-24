{{--
    Pemilih satu tanggal + jam, segaya dengan pemilih rentang di dashboard.

    Pemakaian:
        @include('admin.partials.date-picker', [
            'name'  => 'expire_date',
            'value' => $someDateOrNull,     // apa pun yang bisa di-parse Carbon
            'min'   => now(),               // opsional, tanggal paling awal yang boleh dipilih
            'time'  => false,               // opsional, matikan bagian jam (kirim "Y-m-d" saja)
        ])

    Nilai dikirim sebagai "Y-m-d H:i" lewat input hidden — format yang sama dengan
    aturan validasi di controller, jadi tidak perlu normalisasi lagi. Dengan
    'time' => false, jamnya disembunyikan dan nilainya cuma "Y-m-d".
--}}
@php
    $dpName  = $name;
    $dpValue = !empty($value) ? \Carbon\Carbon::parse($value) : null;
    $dpMin   = !empty($min) ? \Carbon\Carbon::parse($min) : null;
    $dpTime  = $time ?? true;                       // false = tanggal saja
    $dpFmt   = $dpTime ? 'Y-m-d H:i' : 'Y-m-d';

    // Nama hari & bulan ikut locale aplikasi, bukan daftar yang ditulis tangan di JS.
    $dpDow = collect(range(0, 6))->map(
        fn ($i) => \Carbon\Carbon::now()->startOfWeek()->addDays($i)->translatedFormat('D')
    )->all();
    $dpMonths = collect(range(1, 12))->map(
        fn ($m) => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')
    )->all();
@endphp

@once
<style>
    .dp { position: relative; display: inline-block; width: 100%; max-width: 340px; }

    .dp-trigger {
        display: flex; align-items: center; gap: 10px; width: 100%;
        height: 46px; padding: 0 14px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        font-size: 0.85rem; font-weight: 500; cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .dp-trigger:hover { border-color: var(--muted); }
    .dp.open .dp-trigger {
        border-color: var(--green); background: var(--card);
        box-shadow: 0 0 0 4px var(--green-soft);
    }
    .dp-trigger .lead { color: var(--muted); }
    .dp-label { flex: 1; text-align: left; }
    .dp-label.empty { color: var(--muted); }
    .dp-caret { font-size: 0.6rem; color: var(--muted); transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.5, 1); }
    .dp.open .dp-caret { transform: rotate(180deg); }

    .dp-panel {
        position: absolute; top: calc(100% + 8px); left: 0;
        width: 322px; z-index: 1120;
        background: var(--card); border-radius: 20px;
        box-shadow: var(--shadow-pop); padding: 6px;
        opacity: 0; visibility: hidden;
        transform: translateY(-6px) scale(0.98); transform-origin: top left;
        transition:
            transform 0.26s cubic-bezier(0.34, 1.4, 0.5, 1),
            opacity 0.16s ease, visibility 0.16s;
    }
    .dp.open .dp-panel { opacity: 1; visibility: visible; transform: none; }
    .dp.drop-up .dp-panel { top: auto; bottom: calc(100% + 8px); transform-origin: bottom left; }

    .dp-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 6px 12px; font-size: 0.84rem; font-weight: 700;
    }
    .dp-nav {
        width: 30px; height: 30px; border-radius: 50%;
        border: none; background: none; color: var(--muted); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s, color 0.15s;
    }
    .dp-nav:hover { background: var(--surface); color: var(--ink); }
    .dp-nav[disabled] { opacity: 0.3; cursor: default; }

    .dp-grid { display: grid; grid-template-columns: repeat(7, 1fr); padding: 0 4px; }
    .dp-dow {
        text-align: center; font-size: 0.64rem; font-weight: 600;
        color: var(--muted); padding-bottom: 8px;
    }
    .dp-day {
        aspect-ratio: 1; border: none; background: none;
        color: var(--ink); font-size: 0.78rem; font-weight: 600;
        border-radius: 50%; cursor: pointer;
        transition: background 0.14s, color 0.14s;
    }
    .dp-day:hover:not([disabled]) { background: var(--surface); }
    .dp-day[disabled] { color: var(--faint); cursor: default; }
    .dp-day.blank { visibility: hidden; }
    .dp-day.today { box-shadow: inset 0 0 0 1.5px var(--line); }
    .dp-day.pick {
        background: var(--green); color: #fff;
        box-shadow: 0 4px 12px rgba(0, 177, 79, 0.35);
    }
    /* Wajib: .dp-day:hover:not([disabled]) lebih spesifik daripada .dp-day.pick,
       jadi tanpa baris ini tanggal terpilih kehilangan latar hijaunya saat disentuh
       dan tulisan putihnya nyaris tak terbaca. */
    .dp-day.pick:hover { background: var(--green-dark); color: #fff; }

    .dp-foot {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 10px 8px; margin-top: 6px;
        border-top: 1px solid var(--line);
    }
    /* ---------- Jam: segmen yang bisa diketik, di-scroll, atau ditekan panah ---------- */
    .dp-clock {
        display: flex; align-items: center; gap: 8px; flex: 1;
        height: 38px; padding: 0 10px;
        border-radius: 12px; border: 1px solid var(--line);
        background: var(--surface);
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .dp-clock.focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 3px var(--green-soft); }
    .dp-clock > .bi { color: var(--muted); font-size: 0.8rem; }

    .dp-segs { display: flex; align-items: center; gap: 2px; }
    .dp-seg {
        width: 30px; height: 28px; padding: 0;
        border: none; background: none; color: var(--ink);
        font-size: 0.9rem; font-weight: 700; text-align: center;
        font-variant-numeric: tabular-nums;
        border-radius: 8px; outline: none;
        transition: background 0.15s;
    }
    .dp-seg:hover { background: var(--surface); }
    .dp-seg:focus { background: var(--green-soft); color: var(--green-text); }
    .dp-colon { font-weight: 700; color: var(--muted); }

    .dp-steps { display: flex; flex-direction: column; gap: 1px; margin-left: auto; }
    .dp-step {
        width: 22px; height: 15px; padding: 0;
        border: none; background: none; color: var(--muted);
        font-size: 0.55rem; line-height: 1; cursor: pointer;
        border-radius: 5px; transition: background 0.15s, color 0.15s;
    }
    .dp-step:hover { background: var(--green-soft); color: var(--green-text); }

    .dp-quick[hidden], .dp-clock[hidden] { display: none; }
    .dp-quick { display: flex; gap: 6px; flex-wrap: wrap; padding: 0 10px 10px; }
    .dp-quick button {
        border: none; background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 5px 11px;
        font-size: 0.68rem; font-weight: 700; cursor: pointer;
        font-variant-numeric: tabular-nums;
        transition: background 0.15s, color 0.15s;
    }
    .dp-quick button:hover { background: var(--green-soft); color: var(--green-text); }
    .dp-quick button.on { background: var(--green); color: #fff; }
    .dp-done {
        border: none; border-radius: 999px; padding: 9px 18px;
        background: var(--green); color: #fff;
        font-size: 0.78rem; font-weight: 700; cursor: pointer;
    }
    .dp-done:hover { background: var(--green-dark); }

    @media (prefers-reduced-motion: reduce) {
        .dp-panel { transition: opacity 0.15s ease, visibility 0.15s; transform: none; }
        .dp-caret { transition: none; }
    }
</style>
@endonce

<div class="dp" data-dp
     data-value="{{ $dpValue?->format($dpFmt) }}"
     data-min="{{ $dpMin?->format('Y-m-d') }}"
     data-with-time="{{ $dpTime ? '1' : '0' }}"
     data-today="{{ now()->format('Y-m-d') }}"
     data-dow="{{ json_encode($dpDow) }}"
     data-months="{{ json_encode($dpMonths) }}"
     data-empty="{{ __('ui.pick_date') }}">

    <input type="hidden" name="{{ $dpName }}" value="{{ $dpValue?->format($dpFmt) }}">

    <button type="button" class="dp-trigger" data-dp-toggle>
        <i class="bi bi-calendar3 lead"></i>
        <span class="dp-label {{ $dpValue ? '' : 'empty' }}">
            {{ $dpValue ? $dpValue->translatedFormat($dpTime ? 'd M Y, H:i' : 'd M Y') : __('ui.pick_date') }}
        </span>
        <i class="bi bi-chevron-down dp-caret"></i>
    </button>

    <div class="dp-panel">
        <div class="dp-head">
            <button type="button" class="dp-nav" data-dp-nav="-1"><i class="bi bi-chevron-left"></i></button>
            <span data-dp-title>—</span>
            <button type="button" class="dp-nav" data-dp-nav="1"><i class="bi bi-chevron-right"></i></button>
        </div>

        <div class="dp-grid" data-dp-grid></div>

        <div class="dp-quick" data-dp-quick @unless($dpTime) hidden @endunless>
            @foreach(['00:00', '09:00', '12:00', '17:00', '23:59'] as $preset)
                <button type="button" data-time="{{ $preset }}">{{ $preset }}</button>
            @endforeach
        </div>

        <div class="dp-foot">
            <div class="dp-clock" data-dp-clock @unless($dpTime) hidden @endunless>
                <i class="bi bi-clock"></i>
                <div class="dp-segs">
                    <input class="dp-seg" data-seg="h" inputmode="numeric" maxlength="2"
                           aria-label="{{ __('ui.hour') }}" value="{{ $dpValue?->format('H') ?? '23' }}">
                    <span class="dp-colon">:</span>
                    <input class="dp-seg" data-seg="m" inputmode="numeric" maxlength="2"
                           aria-label="{{ __('ui.minute') }}" value="{{ $dpValue?->format('i') ?? '59' }}">
                </div>
                <div class="dp-steps">
                    <button type="button" class="dp-step" data-step="1" aria-label="+"><i class="bi bi-chevron-up"></i></button>
                    <button type="button" class="dp-step" data-step="-1" aria-label="−"><i class="bi bi-chevron-down"></i></button>
                </div>
            </div>
            <button type="button" class="dp-done" data-dp-done>{{ __('ui.apply') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-dp]').forEach(setup);

        function setup(root) {
            const input  = root.querySelector('input[type="hidden"]');
            const label  = root.querySelector('.dp-label');
            const grid   = root.querySelector('[data-dp-grid]');
            const title  = root.querySelector('[data-dp-title]');
            const clock  = root.querySelector('[data-dp-clock]');
            const segH   = root.querySelector('[data-seg="h"]');
            const segM   = root.querySelector('[data-seg="m"]');
            const quick  = root.querySelector('[data-dp-quick]');

            // Mode tanggal-saja: bagian jam disembunyikan dan nilainya cuma "Y-m-d".
            const withTime = root.dataset.withTime !== '0';

            const DOW    = JSON.parse(root.dataset.dow);
            const MONTHS = JSON.parse(root.dataset.months);
            const EMPTY  = root.dataset.empty;

            const parse = (s) => {
                if (!s) return null;
                const [d, t] = s.split(' ');
                const [y, m, day] = d.split('-').map(Number);
                const [hh, mm] = (t || '00:00').split(':').map(Number);
                return new Date(y, m - 1, day, hh, mm);
            };
            const pad = (n) => String(n).padStart(2, '0');
            const fmtDate = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            const sameDay = (a, b) => a && b && fmtDate(a) === fmtDate(b);

            const today = parse(root.dataset.today + ' 00:00');
            const min   = parse(root.dataset.min);
            let picked  = parse(root.dataset.value);
            let view    = new Date((picked ?? today).getFullYear(), (picked ?? today).getMonth(), 1);

            // ---------- Jam ----------
            const clamp = (n, max) => Math.min(Math.max(Number.isFinite(n) ? n : 0, 0), max);

            function readTime() {
                if (!withTime) return [0, 0];
                return [clamp(parseInt(segH.value, 10), 23), clamp(parseInt(segM.value, 10), 59)];
            }

            function paintTime() {
                if (!withTime) return;
                const [h, m] = readTime();
                segH.value = pad(h);
                segM.value = pad(m);

                const current = pad(h) + ':' + pad(m);
                quick.querySelectorAll('[data-time]').forEach((b) => {
                    b.classList.toggle('on', b.dataset.time === current);
                });
            }

            function commit() {
                if (!picked) return;

                const [hh, mm] = readTime();
                picked.setHours(hh, mm, 0, 0);

                const clock = pad(picked.getHours()) + ':' + pad(picked.getMinutes());
                const day   = picked.getDate() + ' ' + MONTHS[picked.getMonth()].slice(0, 3) + ' ' + picked.getFullYear();

                input.value = withTime ? fmtDate(picked) + ' ' + clock : fmtDate(picked);
                label.textContent = withTime ? day + ', ' + clock : day;
                label.classList.remove('empty');
            }

            function render() {
                title.textContent = MONTHS[view.getMonth()] + ' ' + view.getFullYear();

                const first = new Date(view.getFullYear(), view.getMonth(), 1);
                const lead  = (first.getDay() + 6) % 7;          // Senin = 0
                const total = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();

                let html = DOW.map((d) => '<div class="dp-dow">' + d + '</div>').join('');
                for (let i = 0; i < lead; i++) html += '<button type="button" class="dp-day blank"></button>';

                for (let d = 1; d <= total; d++) {
                    const date = new Date(view.getFullYear(), view.getMonth(), d);
                    const off  = min && date < new Date(min.getFullYear(), min.getMonth(), min.getDate());
                    const cls  = ['dp-day',
                        sameDay(date, picked) ? 'pick' : '',
                        sameDay(date, today) ? 'today' : ''].filter(Boolean).join(' ');

                    html += '<button type="button" class="' + cls + '" data-day="' + fmtDate(date) + '"'
                          + (off ? ' disabled' : '') + '>' + d + '</button>';
                }

                grid.innerHTML = html;
            }

            root.addEventListener('click', (e) => {
                if (e.target.closest('[data-dp-toggle]')) {
                    const room = window.innerHeight - root.getBoundingClientRect().bottom;
                    root.classList.toggle('drop-up', room < 400);
                    root.classList.toggle('open');
                    render();
                    return;
                }

                const nav = e.target.closest('[data-dp-nav]');
                if (nav && !nav.disabled) {
                    view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.dpNav), 1);
                    render();
                    return;
                }

                const day = e.target.closest('[data-day]');
                if (day && !day.disabled) {
                    picked = parse(day.dataset.day + ' 00:00');
                    commit();
                    render();
                    return;
                }

                const preset = withTime ? e.target.closest('[data-time]') : null;
                if (preset) {
                    const [h, m] = preset.dataset.time.split(':');
                    segH.value = h;
                    segM.value = m;
                    paintTime();
                    commit();
                    return;
                }

                if (e.target.closest('[data-dp-done]')) {
                    commit();
                    root.classList.remove('open');
                }
            });

            // Ketik angka, tekan ↑/↓, atau gulirkan roda mouse di atas segmen.
            (withTime ? [segH, segM] : []).forEach((seg) => {
                const max = seg === segH ? 23 : 59;
                const stepBy = seg === segH ? 1 : 5;

                const bump = (dir) => {
                    const [h, m] = readTime();
                    const value = seg === segH ? h : m;
                    const next = (value + dir * stepBy + (max + 1)) % (max + 1);
                    seg.value = pad(next);
                    paintTime();
                    commit();
                };

                seg.addEventListener('focus', () => { clock.classList.add('focus'); seg.select(); });
                seg.addEventListener('blur', () => { clock.classList.remove('focus'); paintTime(); commit(); });
                seg.addEventListener('input', () => {
                    seg.value = seg.value.replace(/\D/g, '').slice(0, 2);
                    if (seg.value.length === 2) { paintTime(); commit(); }
                });
                seg.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowUp')   { e.preventDefault(); bump(1); }
                    if (e.key === 'ArrowDown') { e.preventDefault(); bump(-1); }
                });
                seg.addEventListener('wheel', (e) => {
                    if (document.activeElement !== seg) return;
                    e.preventDefault();
                    bump(e.deltaY < 0 ? 1 : -1);
                }, { passive: false });

                seg.dataset.bump = '1';
                seg._bump = bump;
            });

            root.querySelectorAll('[data-step]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    // Panah mengikuti segmen yang sedang aktif; menit kalau tidak ada.
                    const target = document.activeElement === segH ? segH : segM;
                    target._bump(Number(btn.dataset.step));
                    target.focus();
                });
            });

            // Grid digambar ulang saat diklik, jadi asal klik dicatat lebih dulu di
            // fase capture — kalau tidak, tombolnya sudah lepas dari DOM saat dicek.
            let inside = false;
            document.addEventListener('click', (e) => { inside = root.contains(e.target); }, true);
            document.addEventListener('click', () => { if (!inside) root.classList.remove('open'); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') root.classList.remove('open'); });

            paintTime();
            render();
        }
    })();
</script>
@endpush
@endonce
