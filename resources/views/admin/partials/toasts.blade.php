{{--
    Toast overlay untuk pesan sekali-tayang (session flash).
    Peringatan yang sifatnya keadaan — misalnya budget AWS terlampaui — sengaja TIDAK
    dijadikan toast: itu harus tetap terbaca selama kondisinya masih berlaku.
    JS-nya juga mengekspos window.gmToast(pesan, tipe) untuk dipakai dari mana saja.
--}}
<style>
    .gm-toast-stack {
        position: fixed;
        top: 18px;
        right: 18px;
        z-index: 1500;
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: min(380px, calc(100vw - 32px));
        pointer-events: none;
    }

    .gm-toast {
        pointer-events: auto;
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: var(--card);
        border-radius: 16px;
        box-shadow: var(--shadow-pop);
        padding: 14px 14px 15px;
        overflow: hidden;
        transform: translateX(24px) scale(0.98);
        opacity: 0;
        animation: gmToastIn 0.34s cubic-bezier(0.34, 1.4, 0.5, 1) forwards;
    }

    @keyframes gmToastIn {
        to { transform: none; opacity: 1; }
    }

    /* Menghilang: geser keluar sambil melipat tingginya supaya tumpukan ikut rapat. */
    .gm-toast.out {
        animation: none;
        opacity: 0;
        transform: translateX(24px);
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        margin-top: -10px;
        transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .gm-toast-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
        color: #fff;
    }

    .gm-toast.ok .gm-toast-icon { background: var(--green); }
    .gm-toast.bad .gm-toast-icon { background: #dc2626; }
    .gm-toast.warn .gm-toast-icon { background: #f59e0b; }

    .gm-toast-body { flex: 1; min-width: 0; font-size: 0.82rem; line-height: 1.45; padding-top: 4px; }

    .gm-toast-close {
        border: none;
        background: none;
        color: var(--muted);
        font-size: 0.8rem;
        cursor: pointer;
        padding: 4px;
        border-radius: 8px;
        flex-shrink: 0;
        transition: background 0.15s, color 0.15s;
    }

    .gm-toast-close:hover { background: var(--surface); color: var(--ink); }

    .gm-toast-life {
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 100%;
        transform-origin: left;
        animation: gmToastLife linear forwards;
    }

    .gm-toast.ok .gm-toast-life { background: var(--green); }
    .gm-toast.bad .gm-toast-life { background: #dc2626; }
    .gm-toast.warn .gm-toast-life { background: #f59e0b; }

    .gm-toast:hover .gm-toast-life { animation-play-state: paused; }

    @keyframes gmToastLife {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }

    @media (max-width: 600px) {
        .gm-toast-stack { top: 12px; right: 12px; left: 12px; width: auto; }
    }

    @media (prefers-reduced-motion: reduce) {
        .gm-toast { animation: none; opacity: 1; transform: none; }
        .gm-toast-life { animation: none; }
    }
</style>

<div class="gm-toast-stack" id="toastStack" aria-live="polite" aria-atomic="false"></div>

<script>
    (function () {
        const stack = document.getElementById('toastStack');
        const ICON = { ok: 'bi-check-lg', bad: 'bi-x-lg', warn: 'bi-exclamation-lg' };

        function dismiss(el) {
            if (el.dataset.going) return;
            el.dataset.going = '1';
            el.style.maxHeight = el.offsetHeight + 'px';   // kunci tinggi dulu agar bisa dilipat
            requestAnimationFrame(() => el.classList.add('out'));
            setTimeout(() => el.remove(), 320);
        }

        window.gmToast = function (message, type = 'ok', life = 5000) {
            const el = document.createElement('div');
            // Prefiks gm- wajib: kelas .toast milik Bootstrap memaksa display:none.
            el.className = 'gm-toast ' + type;
            el.innerHTML =
                '<span class="gm-toast-icon"><i class="bi ' + (ICON[type] || ICON.ok) + '"></i></span>'
                + '<div class="gm-toast-body"></div>'
                + '<button type="button" class="gm-toast-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>'
                + '<span class="gm-toast-life" style="animation-duration:' + life + 'ms"></span>';

            el.querySelector('.gm-toast-body').textContent = message;
            el.querySelector('.gm-toast-close').addEventListener('click', () => dismiss(el));

            // Umur toast mengikuti bilah progres, termasuk saat dijeda karena hover.
            el.querySelector('.gm-toast-life').addEventListener('animationend', () => dismiss(el));

            stack.appendChild(el);
            return el;
        };

        @if(session('success'))
            window.gmToast(@json(session('success')), 'ok');
        @endif

        @if(session('error'))
            window.gmToast(@json(session('error')), 'bad', 7000);
        @endif

        @if(session('warning'))
            window.gmToast(@json(session('warning')), 'warn', 7000);
        @endif
    })();
</script>
