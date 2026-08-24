{{--
    Command palette (⌘K / Ctrl+K).
    Indeksnya kecil dan dibangun di server: halaman admin, perusahaan, akun AWS,
    dan beberapa aksi cepat — jadi pencarian terjadi seketika di browser tanpa
    request per ketikan. Kalau daftar perusahaan tumbuh besar, ganti bagian
    "Perusahaan" dengan endpoint JSON + debounce.
--}}
@php
    $paletteItems = [];

    foreach ($menu as $m) {
        $paletteItems[] = [
            'g'    => __('ui.search_pages'),
            'i'    => $m['icon'],
            'l'    => $m['label'],
            's'    => __('ui.search_page_sub'),
            'u'    => route($m['route']),
            'k'    => $m['route'],
        ];
    }

    $paletteItems[] = ['g' => __('ui.search_actions'), 'i' => 'bi-key',             'l' => __('ui.action_add_key'),     's' => __('ui.action_add_key_d'),     'u' => route('admin.api-keys.create'),  'k' => 'add key baru tambah'];
    $paletteItems[] = ['g' => __('ui.search_actions'), 'i' => 'bi-arrow-clockwise', 'l' => __('ui.action_refresh'),     's' => __('ui.action_refresh_d'),     'u' => url()->current() . '?refresh=1', 'k' => 'refresh reload cloudwatch'];
    $paletteItems[] = ['g' => __('ui.search_actions'), 'i' => 'bi-map',             'l' => __('ui.open_homepage'),      's' => __('ui.action_homepage_d'),    'u' => url('/'),                        'k' => 'home peta map'];
    $paletteItems[] = ['g' => __('ui.search_actions'), 'i' => 'bi-code-slash',      'l' => __('ui.api_tester'),         's' => __('ui.action_tester_d'),      'u' => route('pageRouteTester'),        'k' => 'test api route'];

    foreach ($paletteAccounts as $acc) {
        $paletteItems[] = [
            'g' => __('ui.aws_accounts'),
            'i' => 'bi-cloud-fill',
            'l' => $acc->name,
            's' => $acc->region . ' · ' . $acc->maskedAccessKey(),
            'u' => route('admin.aws-accounts.edit', $acc),
            'k' => $acc->region . ' ' . $acc->account_number,
        ];
    }

    // Perusahaan sementara tidak diindeks — menunya sedang disembunyikan.
    // Kalau menu Perusahaan dimunculkan lagi, kembalikan blok ini (lihat riwayat git).

@endphp

<style>
    .search-overlay {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 12vh 16px 16px;
        background: rgba(12, 18, 15, 0.42);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }

    .search-overlay.open { opacity: 1; visibility: visible; }

    .search-panel {
        width: 100%;
        max-width: 580px;
        max-height: 74vh;
        display: flex;
        flex-direction: column;
        background: var(--card);
        border-radius: 22px;
        box-shadow: var(--shadow-pop);
        overflow: hidden;
        transform: translateY(-12px) scale(0.97);
        opacity: 0;
        transition:
            transform 0.28s cubic-bezier(0.34, 1.4, 0.5, 1),
            opacity 0.18s ease;
    }

    .search-overlay.open .search-panel { transform: none; opacity: 1; }

    .search-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--line);
    }

    .search-head > i { color: var(--muted); font-size: 1.05rem; }

    .search-head input {
        flex: 1;
        border: none;
        background: none;
        outline: none;
        font-size: 0.95rem;
        font-weight: 500;
        color: var(--ink);
        min-width: 0;
    }

    .search-head input::placeholder { color: var(--muted); font-weight: 400; }

    .kbd {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: var(--muted);
        background: var(--surface);
        border-radius: 7px;
        padding: 4px 7px;
        white-space: nowrap;
    }

    .search-results {
        overflow-y: auto;
        padding: 8px;
        scrollbar-width: thin;
    }

    .search-results::-webkit-scrollbar { width: 8px; }
    .search-results::-webkit-scrollbar-thumb { background: var(--line); border-radius: 8px; }

    .search-group {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--muted);
        padding: 10px 12px 6px;
    }

    .search-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 9px 12px;
        border-radius: 14px;
        text-decoration: none;
        color: var(--ink);
        position: relative;
        animation: searchRowIn 0.26s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
        animation-delay: calc(var(--i, 0) * 22ms);
    }

    @keyframes searchRowIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }

    .search-item .si-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        background: var(--surface);
        color: var(--muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
        transition: background 0.15s, color 0.15s;
    }

    .search-item .si-text { min-width: 0; flex: 1; display: flex; flex-direction: column; }
    .search-item .si-title { display: block; font-size: 0.85rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .search-item .si-sub { display: block; font-size: 0.71rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .search-item .si-go { font-size: 0.75rem; color: var(--muted); opacity: 0; transform: translateX(-4px); transition: all 0.15s; }
    .search-item mark { background: none; color: var(--green-text); font-weight: 700; padding: 0; }

    .search-item.active {
        background: var(--surface);
        color: var(--ink);
    }

    .search-item.active .si-icon { background: var(--green); color: #fff; }
    .search-item.active .si-go { opacity: 1; transform: none; }

    .search-empty { text-align: center; padding: 34px 16px; color: var(--muted); font-size: 0.84rem; }
    .search-empty i { display: block; font-size: 1.6rem; margin-bottom: 8px; color: var(--faint); }

    .search-foot {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px 16px;
        border-top: 1px solid var(--line);
        font-size: 0.68rem;
        color: var(--muted);
        flex-wrap: wrap;
    }

    .search-foot span { display: inline-flex; align-items: center; gap: 5px; }

    @media (prefers-reduced-motion: reduce) {
        .search-overlay, .search-panel { transition: opacity 0.12s ease; }
        .search-panel { transform: none; }
        .search-item { animation: none; }
    }
</style>

<div class="search-overlay" id="searchOverlay" role="dialog" aria-modal="true" aria-label="{{ __('ui.search') }}">
    <div class="search-panel">
        <div class="search-head">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" autocomplete="off" spellcheck="false"
                   placeholder="{{ __('ui.search_placeholder') }}">
            <span class="kbd">ESC</span>
        </div>

        <div class="search-results" id="searchResults"></div>

        <div class="search-foot">
            <span><span class="kbd">↑</span><span class="kbd">↓</span> {{ __('ui.search_pick') }}</span>
            <span><span class="kbd">↵</span> {{ __('ui.search_open') }}</span>
            <span><span class="kbd">ESC</span> {{ __('ui.search_close') }}</span>
        </div>
    </div>
</div>

<script>
    (function () {
        const ITEMS = @json($paletteItems);
        const NO_RESULT = @json(__('ui.search_no_result'));
        const GROUP_PAGES = @json(__('ui.search_pages'));
        const GROUP_ACTIONS = @json(__('ui.search_actions'));

        const overlay = document.getElementById('searchOverlay');
        const input = document.getElementById('searchInput');
        const results = document.getElementById('searchResults');
        let cursor = 0;
        let shown = [];
        let lastFocus = null;

        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));

        // Tandai potongan yang cocok, setelah teksnya di-escape.
        const mark = (text, q) => {
            const safe = esc(text);
            if (!q) return safe;
            const at = text.toLowerCase().indexOf(q);
            if (at < 0) return safe;
            return esc(text.slice(0, at)) + '<mark>' + esc(text.slice(at, at + q.length)) + '</mark>' + esc(text.slice(at + q.length));
        };

        function match(q) {
            // Daftar awal (belum mengetik): halaman + aksi cepat, memakai label yang sudah dilokalkan.
            if (!q) return ITEMS.filter(it => it.g === GROUP_ACTIONS || it.g === GROUP_PAGES);

            const scored = ITEMS
                .map((it) => {
                    const label = it.l.toLowerCase();
                    const hay = (it.l + ' ' + it.s + ' ' + (it.k || '')).toLowerCase();
                    let score = -1;
                    if (label.startsWith(q)) score = 0;
                    else if (label.includes(q)) score = 1;
                    else if (hay.includes(q)) score = 2;
                    return { it, score };
                })
                .filter(r => r.score >= 0);

            // Urutan grup ditentukan oleh kecocokan terbaik di dalamnya, lalu semua item
            // satu grup dirapatkan — kalau tidak, judul grup bisa muncul berulang.
            const rank = {};
            let next = 0;
            [...scored]
                .sort((a, b) => a.score - b.score)
                .forEach((r) => { if (rank[r.it.g] === undefined) rank[r.it.g] = next++; });

            return scored
                .sort((a, b) =>
                    (rank[a.it.g] - rank[b.it.g]) ||
                    (a.score - b.score) ||
                    a.it.l.localeCompare(b.it.l))
                .map(r => r.it)
                .slice(0, 24);
        }

        function render() {
            const q = input.value.trim().toLowerCase();
            shown = match(q);
            cursor = 0;

            if (!shown.length) {
                results.innerHTML = '<div class="search-empty"><i class="bi bi-slash-circle"></i>'
                    + NO_RESULT.replace(':query', esc(input.value.trim())) + '</div>';
                return;
            }

            let html = '';
            let group = null;

            shown.forEach((it, i) => {
                if (it.g !== group) {
                    group = it.g;
                    html += '<div class="search-group">' + esc(group) + '</div>';
                }
                html += '<a class="search-item' + (i === 0 ? ' active' : '') + '" href="' + esc(it.u) + '"'
                    + ' data-i="' + i + '" style="--i:' + i + '">'
                    + '<span class="si-icon"><i class="bi ' + esc(it.i) + '"></i></span>'
                    + '<span class="si-text">'
                    + '<span class="si-title">' + mark(it.l, q) + '</span>'
                    + '<span class="si-sub">' + mark(it.s, q) + '</span>'
                    + '</span>'
                    + '<i class="bi bi-arrow-return-left si-go"></i>'
                    + '</a>';
            });

            results.innerHTML = html;
        }

        function move(step) {
            const rows = results.querySelectorAll('.search-item');
            if (!rows.length) return;
            rows[cursor]?.classList.remove('active');
            cursor = (cursor + step + rows.length) % rows.length;
            rows[cursor].classList.add('active');
            rows[cursor].scrollIntoView({ block: 'nearest' });
        }

        function open() {
            lastFocus = document.activeElement;
            overlay.classList.add('open');
            input.value = '';
            render();
            // Fokus setelah frame pertama supaya animasi panel tidak tersendat.
            requestAnimationFrame(() => input.focus());
        }

        function close() {
            overlay.classList.remove('open');
            lastFocus?.focus();
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-search-open]')) {
                e.preventDefault();
                open();
                return;
            }
            // Klik di luar panel menutup palette.
            if (overlay.classList.contains('open') && e.target === overlay) close();
        });

        document.addEventListener('keydown', (e) => {
            const isOpen = overlay.classList.contains('open');

            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                isOpen ? close() : open();
                return;
            }

            if (!isOpen) return;

            if (e.key === 'Escape') { e.preventDefault(); close(); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Enter') {
                const row = results.querySelectorAll('.search-item')[cursor];
                // click(), bukan location.href — supaya loader halaman ikut jalan.
                if (row) { e.preventDefault(); row.click(); }
            }
        });

        input.addEventListener('input', render);

        results.addEventListener('mousemove', (e) => {
            const row = e.target.closest('.search-item');
            if (!row) return;
            const i = Number(row.dataset.i);
            if (i === cursor) return;
            results.querySelectorAll('.search-item')[cursor]?.classList.remove('active');
            cursor = i;
            row.classList.add('active');
        });
    })();
</script>
