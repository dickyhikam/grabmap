{{--
    Skeleton + progress bar saat pindah halaman.
    Halamannya server-rendered, jadi jeda yang terasa adalah waktu tunggu respons —
    paling kentara di "Refresh data" yang menembak AWS CloudWatch. Progress bar muncul
    seketika, skeleton baru menyusul setelah 350ms supaya halaman cepat tidak berkedip.
--}}
<style>
    .route-bar {
        position: fixed;
        top: 0;
        left: 0;
        height: 3px;
        width: 0;
        z-index: 1400;
        background: linear-gradient(90deg, var(--green), #5ddc93);
        box-shadow: 0 0 14px rgba(0, 177, 79, 0.65);
        border-radius: 0 3px 3px 0;
        opacity: 0;
        transition: width 0.25s ease, opacity 0.25s ease;
    }

    .route-bar.on { opacity: 1; }

    .skeleton-veil {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        min-height: 100vh;
        z-index: 40;
        background: var(--page);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.24s ease, visibility 0.24s;
    }

    .skeleton-veil.on { opacity: 1; visibility: visible; }

    .sk {
        position: relative;
        overflow: hidden;
        background: var(--card);
        border-radius: 22px;
        box-shadow: var(--shadow-card);
    }

    .sk::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(20, 27, 24, 0.06), transparent);
        transform: translateX(-100%);
        animation: skSheen 1.4s infinite;
    }

    :root[data-theme="dark"] .sk::after {
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    }

    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]):not([data-theme="dark"]) .sk::after {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
        }
    }

    @keyframes skSheen {
        to { transform: translateX(100%); }
    }

    .sk-line {
        height: 11px;
        border-radius: 999px;
        background: var(--surface);
        margin-bottom: 10px;
    }

    .sk-line.w40 { width: 40%; }
    .sk-line.w60 { width: 60%; }
    .sk-line.w75 { width: 75%; }
    .sk-line.tall { height: 26px; margin-bottom: 14px; }

    .sk-head { height: 42px; width: 320px; max-width: 70%; border-radius: 14px; margin-bottom: 20px; }

    .sk-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr) minmax(0, 1.15fr);
        gap: 16px;
        align-items: start;
    }

    .sk-col { display: flex; flex-direction: column; gap: 16px; }
    .sk-card { padding: 20px; }
    .sk-card .sk-block { height: 120px; border-radius: 16px; background: var(--surface); }
    .sk-card.tall .sk-block { height: 215px; }

    @media (max-width: 1200px) { .sk-grid { grid-template-columns: 1fr; } }

    @media (prefers-reduced-motion: reduce) {
        .sk::after { animation: none; }
        .route-bar { transition: opacity 0.2s ease; }
    }
</style>

<div class="route-bar" id="routeBar"></div>

<div class="skeleton-veil" id="pageSkeleton" aria-hidden="true">
    <div class="sk sk-head"></div>

    <div class="sk-grid">
        <div class="sk-col">
            <div class="sk sk-card">
                <div class="sk-line tall w60"></div>
                <div class="sk-block"></div>
            </div>
            <div class="sk sk-card">
                <div class="sk-line w40"></div>
                <div class="sk-line tall w75"></div>
            </div>
        </div>

        <div class="sk-col">
            <div class="sk sk-card tall">
                <div class="sk-line w40"></div>
                <div class="sk-block"></div>
            </div>
            <div class="sk sk-card">
                <div class="sk-line w60"></div>
                <div class="sk-line w75"></div>
                <div class="sk-line w40"></div>
            </div>
        </div>

        <div class="sk-col">
            <div class="sk sk-card">
                <div class="sk-line w40"></div>
                <div class="sk-block"></div>
            </div>
            <div class="sk sk-card">
                <div class="sk-line w60"></div>
                <div class="sk-line w75"></div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const bar = document.getElementById('routeBar');
        const veil = document.getElementById('pageSkeleton');
        let tick = null;
        let delay = null;

        function start() {
            if (bar.classList.contains('on')) return;

            let w = 10;
            bar.classList.add('on');
            bar.style.width = w + '%';

            // Merambat pelan lalu menahan di 88% — selebihnya menunggu respons asli.
            tick = setInterval(() => {
                w = Math.min(w + Math.random() * 11, 88);
                bar.style.width = w + '%';
            }, 240);

            delay = setTimeout(() => veil.classList.add('on'), 350);
        }

        function stop() {
            clearInterval(tick);
            clearTimeout(delay);
            bar.style.width = '100%';
            setTimeout(() => {
                bar.classList.remove('on');
                bar.style.width = '0';
                veil.classList.remove('on');
            }, 260);
        }

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
            if (link.dataset.noLoader !== undefined) return;

            const url = new URL(link.getAttribute('href'), location.href);
            if (url.origin !== location.origin) return;
            if (url.href.split('#')[0] === location.href.split('#')[0]) return; // anchor di halaman yang sama

            start();
        });

        document.addEventListener('submit', (e) => {
            // Handler form berjalan lebih dulu (fase target) — kalau ia membatalkan submit
            // karena validasi gagal, tidak ada navigasi yang perlu ditunggu.
            if (e.defaultPrevented) return;
            start();
        });

        // Kembali lewat tombol back: halaman diambil dari cache, loader harus dilepas.
        window.addEventListener('pageshow', (e) => { if (e.persisted) stop(); });
    })();
</script>
