{{--
    Pemilih rentang tanggal: dua bulan berdampingan, preset cepat, dan hitungan hari.
    Menerapkan rentang = pindah halaman dengan ?start=&end= (controller yang menjepit
    nilainya). Catatan penting: rentang yang belum pernah diambil akan menembak
    CloudWatch sekali — karena itu ada peringatan kecil di kaki panel.
--}}
@php
    $drStart = \Carbon\Carbon::parse($startDate);
    $drEnd   = \Carbon\Carbon::parse($endDate);
    $drDays  = $drStart->diffInDays($drEnd) + 1;

    // Nama hari & bulan diambil dari Carbon supaya ikut locale aplikasi,
    // bukan daftar yang ditulis tangan di JS.
    $drDow = collect(range(0, 6))->map(
        fn ($i) => \Carbon\Carbon::now()->startOfWeek()->addDays($i)->translatedFormat('D')
    )->all();
    $drMonths = collect(range(1, 12))->map(
        fn ($m) => \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F')
    )->all();
@endphp

<style>
    .dr { position: relative; }

    .dr-panel {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        width: min(680px, calc(100vw - 32px));
        background: var(--card);
        border-radius: 22px;
        box-shadow: var(--shadow-pop);
        padding: 6px;
        z-index: 1100;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px) scale(0.98);
        transform-origin: top right;
        transition:
            transform 0.26s cubic-bezier(0.34, 1.4, 0.5, 1),
            opacity 0.18s ease,
            visibility 0.18s;
    }

    .dr.open .dr-panel { opacity: 1; visibility: visible; transform: none; }

    .dr-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 12px 12px 10px;
        border-bottom: 1px solid var(--line);
    }

    .dr-preset {
        border: none;
        background: var(--surface);
        color: var(--muted);
        border-radius: 999px;
        padding: 7px 14px;
        font-size: 0.74rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }

    .dr-preset:hover { color: var(--ink); }
    .dr-preset:active { transform: scale(0.95); }
    .dr-preset.on { background: var(--green); color: #fff; }

    .dr-months { display: flex; gap: 8px; padding: 14px 12px 4px; }
    .dr-month { flex: 1; min-width: 0; }

    @media (max-width: 640px) {
        .dr-month.second { display: none; }
        .dr-panel { width: calc(100vw - 24px); right: auto; left: 50%; transform: translate(-50%, -8px) scale(0.98); }
        .dr.open .dr-panel { transform: translate(-50%, 0); }
    }

    .dr-mhead {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 0 4px 12px;
        font-size: 0.84rem;
        font-weight: 700;
    }

    .dr-nav {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        background: none;
        color: var(--muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s, color 0.15s;
    }

    .dr-nav:hover { background: var(--surface); color: var(--ink); }
    .dr-nav[disabled] { opacity: 0.3; cursor: default; }
    .dr-nav.ghost { visibility: hidden; }

    .dr-grid { display: grid; grid-template-columns: repeat(7, 1fr); }

    .dr-dow {
        text-align: center;
        font-size: 0.66rem;
        font-weight: 600;
        color: var(--muted);
        padding-bottom: 8px;
    }

    .dr-cell { position: relative; }

    .dr-day {
        width: 100%;
        aspect-ratio: 1;
        border: none;
        background: none;
        color: var(--ink);
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: 50%;
        cursor: pointer;
        position: relative;
        z-index: 1;
        transition: background 0.14s, color 0.14s, transform 0.12s;
    }

    .dr-day:hover:not([disabled]) { background: var(--surface); }
    .dr-day[disabled] { color: var(--faint); cursor: default; }
    .dr-day.blank { visibility: hidden; }
    .dr-day.today { box-shadow: inset 0 0 0 1.5px var(--line); }

    /* Pita rentang digambar di belakang angka supaya menyambung antar kolom. */
    .dr-cell.in::before {
        content: '';
        position: absolute;
        inset: 6% 0;
        background: var(--green-soft);
    }

    .dr-cell.in.edge-start::before { border-radius: 999px 0 0 999px; }
    .dr-cell.in.edge-end::before { border-radius: 0 999px 999px 0; }
    .dr-cell.in.edge-start.edge-end::before { border-radius: 999px; }

    .dr-day.pick {
        background: var(--green);
        color: #fff;
        box-shadow: 0 4px 12px rgba(0, 177, 79, 0.35);
    }

    .dr-day.pick:hover { background: var(--green); }

    .dr-foot {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 12px 14px;
        border-top: 1px solid var(--line);
        margin-top: 8px;
    }

    .dr-count { font-size: 0.78rem; font-weight: 700; }
    .dr-hint { font-size: 0.68rem; color: var(--muted); }
    .dr-actions { margin-left: auto; display: flex; gap: 8px; }

    .dr-btn {
        border: none;
        border-radius: 999px;
        padding: 9px 18px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s, transform 0.12s;
    }

    .dr-btn:active { transform: scale(0.96); }
    .dr-btn.ghost { background: var(--surface); color: var(--ink); }
    .dr-btn.solid { background: var(--green); color: #fff; box-shadow: 0 6px 16px rgba(0, 177, 79, 0.3); }
    .dr-btn.solid:hover { background: var(--green-dark); }

    @media (prefers-reduced-motion: reduce) {
        .dr-panel { transition: opacity 0.15s ease, visibility 0.15s; transform: none; }
    }
</style>

<div class="dr" id="dateRange"
     data-start="{{ $drStart->format('Y-m-d') }}"
     data-end="{{ $drEnd->format('Y-m-d') }}"
     data-today="{{ now()->format('Y-m-d') }}"
     data-max-days="92">

    <button type="button" class="q-pill" data-dr-toggle>
        <i class="bi bi-calendar3"></i>
        <span id="drLabel">
            {{ $drStart->translatedFormat('d M Y') }} &ndash; {{ $drEnd->translatedFormat('d M Y') }}
        </span>
        <i class="bi bi-chevron-down" style="font-size:0.65rem;"></i>
    </button>

    <div class="dr-panel">
        <div class="dr-presets" id="drPresets">
            <button type="button" class="dr-preset" data-preset="month">{{ __('ui.range_this_month') }}</button>
            <button type="button" class="dr-preset" data-preset="7">{{ __('ui.range_last_days', ['count' => 7]) }}</button>
            <button type="button" class="dr-preset" data-preset="30">{{ __('ui.range_last_days', ['count' => 30]) }}</button>
            <button type="button" class="dr-preset" data-preset="prev-month">{{ __('ui.range_prev_month') }}</button>
        </div>

        <div class="dr-months" id="drMonths"></div>

        <div class="dr-foot">
            <div>
                <div class="dr-count" id="drCount">{{ __('ui.range_days', ['count' => $drDays]) }}</div>
                <div class="dr-hint">{{ __('ui.range_note') }}</div>
            </div>
            <div class="dr-actions">
                <button type="button" class="dr-btn ghost" data-dr-cancel>{{ __('ui.cancel') }}</button>
                <button type="button" class="dr-btn solid" data-dr-apply>{{ __('ui.apply') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('dateRange');
        if (!root) return;

        const panel = root.querySelector('.dr-panel');
        const monthsEl = document.getElementById('drMonths');
        const countEl = document.getElementById('drCount');
        const presetsEl = document.getElementById('drPresets');

        const DOW = @json($drDow);
        const MONTHS = @json($drMonths);
        const DAYS_LABEL = @json(__('ui.range_days', ['count' => '{n}']));
        const MAX_LABEL = @json(__('ui.range_max'));

        const MAX_DAYS = Number(root.dataset.maxDays);
        const today = parse(root.dataset.today);

        let start = parse(root.dataset.start);
        let end = parse(root.dataset.end);
        let anchor = null;            // klik pertama saat memilih rentang baru
        let hover = null;             // pratinjau rentang saat kursor bergerak
        let view = new Date(end.getFullYear(), end.getMonth() - 1, 1);

        function parse(s) {
            const [y, m, d] = s.split('-').map(Number);
            return new Date(y, m - 1, d);
        }

        function fmt(d) {
            const p = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
        }

        const dayDiff = (a, b) => Math.round((b - a) / 86400000);
        const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);

        function renderMonth(base, idx) {
            const y = base.getFullYear();
            const m = base.getMonth();
            const first = new Date(y, m, 1);
            const lead = (first.getDay() + 6) % 7;          // Senin = 0
            const total = new Date(y, m + 1, 0).getDate();

            // Batas navigasi: bulan depan tidak boleh melewati bulan berjalan.
            const nextBlocked = idx === 1 &&
                (y > today.getFullYear() || (y === today.getFullYear() && m >= today.getMonth()));

            let html = '<div class="dr-month' + (idx === 1 ? ' second' : '') + '">'
                + '<div class="dr-mhead">'
                + (idx === 0
                    ? '<button type="button" class="dr-nav" data-nav="-1"><i class="bi bi-chevron-left"></i></button>'
                    : '<button type="button" class="dr-nav ghost"></button>')
                + '<span>' + MONTHS[m] + ' ' + y + '</span>'
                + (idx === 1
                    ? '<button type="button" class="dr-nav" data-nav="1"' + (nextBlocked ? ' disabled' : '') + '><i class="bi bi-chevron-right"></i></button>'
                    : '<button type="button" class="dr-nav ghost"></button>')
                + '</div><div class="dr-grid">';

            DOW.forEach(d => { html += '<div class="dr-dow">' + d + '</div>'; });

            // Pratinjau memakai anchor + posisi kursor supaya pita terasa hidup.
            let a = start, b = end;
            if (anchor) {
                const other = hover || anchor;
                a = anchor <= other ? anchor : other;
                b = anchor <= other ? other : anchor;
            }

            for (let i = 0; i < lead; i++) html += '<div class="dr-cell"><button class="dr-day blank"></button></div>';

            for (let d = 1; d <= total; d++) {
                const date = new Date(y, m, d);
                const key = fmt(date);
                const future = date > today;
                const inRange = !future && date >= a && date <= b;
                const isStart = key === fmt(a);
                const isEnd = key === fmt(b);
                const col = (lead + d - 1) % 7;

                const cellCls = [
                    'dr-cell',
                    inRange ? 'in' : '',
                    inRange && (isStart || col === 0 || d === 1) ? 'edge-start' : '',
                    inRange && (isEnd || col === 6 || d === total) ? 'edge-end' : '',
                ].filter(Boolean).join(' ');

                const dayCls = [
                    'dr-day',
                    (isStart || isEnd) && inRange ? 'pick' : '',
                    key === fmt(today) ? 'today' : '',
                ].filter(Boolean).join(' ');

                html += '<div class="' + cellCls + '">'
                    + '<button type="button" class="' + dayCls + '" data-d="' + key + '"'
                    + (future ? ' disabled' : '') + '>' + d + '</button></div>';
            }

            return html + '</div></div>';
        }

        // Preset mana yang persis sama dengan rentang saat ini (untuk disorot).
        function matchedPreset() {
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            const prevStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const prevEnd = new Date(today.getFullYear(), today.getMonth(), 0);

            if (fmt(start) === fmt(prevStart) && fmt(end) === fmt(prevEnd)) return 'prev-month';
            if (fmt(end) !== fmt(today)) return null;
            if (fmt(start) === fmt(monthStart)) return 'month';

            const days = dayDiff(start, end) + 1;
            if (days === 7) return '7';
            if (days === 30) return '30';
            return null;
        }

        function render() {
            const second = new Date(view.getFullYear(), view.getMonth() + 1, 1);
            monthsEl.innerHTML = renderMonth(view, 0) + renderMonth(second, 1);

            const days = dayDiff(start, end) + 1;
            countEl.textContent = DAYS_LABEL.replace('{n}', days) + (days >= MAX_DAYS ? ' ' + MAX_LABEL : '');

            const active = matchedPreset();
            presetsEl.querySelectorAll('.dr-preset').forEach((b) => {
                b.classList.toggle('on', b.dataset.preset === active);
            });

            const label = (d) => d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear();
            document.getElementById('drLabel').textContent = label(start) + ' – ' + label(end);
        }

        function pick(dateStr) {
            const date = parse(dateStr);

            if (!anchor) {
                anchor = date;
                hover = date;
                start = date;
                end = date;
            } else {
                let a = anchor, b = date;
                if (b < a) [a, b] = [b, a];

                // Jepit di sisi klien juga supaya angkanya konsisten dengan yang dilihat.
                if (dayDiff(a, b) + 1 > MAX_DAYS) a = addDays(b, -(MAX_DAYS - 1));

                start = a;
                end = b;
                anchor = null;
                hover = null;
            }

            render();
        }

        function apply() {
            const url = new URL(window.location.href);
            url.searchParams.set('start', fmt(start));
            url.searchParams.set('end', fmt(end));
            url.searchParams.delete('refresh');
            window.location.href = url.toString();
        }

        function close() {
            root.classList.remove('open');
            anchor = null;
            hover = null;
            start = parse(root.dataset.start);
            end = parse(root.dataset.end);
            view = new Date(end.getFullYear(), end.getMonth() - 1, 1);
            render();
        }

        root.addEventListener('click', (e) => {
            if (e.target.closest('[data-dr-toggle]')) {
                root.classList.contains('open') ? close() : root.classList.add('open');
                return;
            }
            if (e.target.closest('[data-dr-cancel]')) { close(); return; }
            if (e.target.closest('[data-dr-apply]')) { apply(); return; }

            const nav = e.target.closest('[data-nav]');
            if (nav && !nav.disabled) {
                view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.nav), 1);
                render();
                return;
            }

            const preset = e.target.closest('[data-preset]');
            if (preset) {
                const p = preset.dataset.preset;
                if (p === 'month') {
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    end = today;
                } else if (p === 'prev-month') {
                    start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    end = new Date(today.getFullYear(), today.getMonth(), 0);
                } else {
                    end = today;
                    start = addDays(today, -(Number(p) - 1));
                }
                anchor = null;
                view = new Date(end.getFullYear(), end.getMonth() - 1, 1);
                render();
                return;
            }

            const day = e.target.closest('.dr-day[data-d]');
            if (day && !day.disabled) pick(day.dataset.d);
        });

        monthsEl.addEventListener('mouseover', (e) => {
            if (!anchor) return;
            const day = e.target.closest('.dr-day[data-d]');
            if (!day || day.disabled) return;
            const next = parse(day.dataset.d);
            if (hover && fmt(hover) === fmt(next)) return;
            hover = next;
            render();
        });

        // Klik prev/next/tanggal menggambar ulang kalender, sehingga elemen yang diklik
        // sudah lepas dari DOM saat pengecekan "klik di luar" berjalan. Asal klik karena
        // itu dicatat lebih dulu di fase capture, sebelum DOM-nya berubah.
        let clickedInside = false;

        document.addEventListener('click', (e) => { clickedInside = root.contains(e.target); }, true);

        document.addEventListener('click', () => {
            if (root.classList.contains('open') && !clickedInside) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && root.classList.contains('open')) close();
        });

        render();
    })();
</script>
