@props([
    // Judul dan penjelasan singkat di sebelah logo.
    'title',
    'subtitle' => null,

    // Tujuan tombol kembali. Kalau null, tombolnya tidak dirender.
    'back' => null,
    'backLabel' => 'Kembali',

    // Logo GrabMaps di kiri judul.
    'logo' => true,
])

{{--
    Kepala halaman GrabMaps.

    Melebar penuh saat di puncak halaman, menyusut jadi kartu mengambang begitu
    digulir. Gayanya di public/css/gm-page-head.css — halaman yang memakainya
    wajib memuat berkas itu di <head>:

        <link rel="stylesheet" href="/css/gm-page-head.css?v={{ '{{' }} filemtime(public_path('css/gm-page-head.css')) }}">

    Halaman yang punya token temanya sendiri bisa memetakannya ke --gm-*
    (lihat blok pemetaan di public/css/pricing-v2.css).

    Tiga slot:
      tools  — kontrol yang jarang disentuh (bahasa, tema). Mengatup saat
               kepala menyusut supaya ruangnya bergantian dengan `stats`.
      stats  — ringkasan yang justru baru muncul saat kepala menyusut.
      slot   — tombol aksi di kanan, selalu terlihat.

    Contoh:
        <x-page-head title="AWS Location Service API Tester"
                     subtitle="Debug & test AWS Location Service APIs"
                     :back="route('pageHome')" back-label="Kembali ke peta">
            <button class="head-btn is-solid">API Key</button>
            <button class="head-btn">API Reference</button>
        </x-page-head>
--}}
<div class="head-shell" id="headShell">
    <header class="page-head" id="pageHead">
        @if($back)
        <a href="{{ $back }}" class="head-back" aria-label="{{ $backLabel }}" data-tip="{{ $backLabel }}">
            <i class="bi bi-arrow-left"></i>
        </a>
        @endif

        @if($logo)
        <a href="{{ $back ?? url('/') }}" class="head-logo" aria-label="GrabMaps" data-tip="GrabMaps">
            <img src="{{ asset('logo.png') }}" alt="GrabMaps">
        </a>
        @endif

        <div class="head-text">
            <h1>{{ $title }}</h1>
            @if($subtitle)
            <p>{{ $subtitle }}</p>
            @endif
        </div>

        @isset($tools)
        <div class="head-tools">{{ $tools }}</div>
        @endisset

        @isset($stats)
        <div class="head-stats" id="headStats" aria-live="polite">{{ $stats }}</div>
        @endisset

        {{ $slot }}
    </header>
</div>

@once
<script>
    // Kepala menyusut jadi kartu saat digulir. Tingginya dibagikan lewat
    // --head-h supaya elemen lain yang melekat bisa berhenti tepat di bawahnya.
    (function () {
        const head = document.getElementById('pageHead');
        const shell = document.getElementById('headShell');
        if (!head || !shell) return;

        const sync = () => {
            const stuck = window.scrollY > 12;
            head.classList.toggle('is-stuck', stuck);
            shell.classList.toggle('is-stuck', stuck);
            document.documentElement.style.setProperty('--head-h', shell.offsetHeight + 'px');
        };

        window.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    })();

    // ---- RODA BAHASA ----
    // Kode yang berada di tengah diberi penekanan; sisanya meredup ke tepi.
    // Gulir roda tetikus dipetakan ke gulir mendatar supaya terasa seperti
    // pemilih angka, bukan daftar yang harus digeser dengan bilah gulir.
    (function () {
        const track = document.getElementById('langTrack');
        const wheel = document.getElementById('langWheel');
        if (!track || !wheel) return;

        const items = [...track.querySelectorAll('.lang-item')];

        // Tepi yang sudah mentok tidak diberi gradien pudar — kalau tidak, pil
        // terlihat seperti terpotong padahal memang tidak bisa digeser lagi.
        function markEdges() {
            const max = track.scrollWidth - track.clientWidth;
            wheel.classList.toggle('at-start', track.scrollLeft <= 1);
            wheel.classList.toggle('at-end', track.scrollLeft >= max - 1);
        }

        function markCenter() {
            const mid = track.scrollLeft + track.clientWidth / 2;
            let nearest = items[0];
            let best = Infinity;
            items.forEach(item => {
                const c = item.offsetLeft + item.offsetWidth / 2;
                const d = Math.abs(c - mid);
                if (d < best) { best = d; nearest = item; }
            });
            items.forEach(i => i.classList.toggle('is-center', i === nearest));
            markEdges();
        }

        const centerActive = () => {
            const active = track.querySelector('.lang-item.active') || items[0];
            const prev = track.style.scrollBehavior;
            track.style.scrollBehavior = 'auto';
            track.scrollLeft = active.offsetLeft + active.offsetWidth / 2 - track.clientWidth / 2;
            track.style.scrollBehavior = prev;
            markCenter();
        };

        // Berhenti di antara dua kode terasa seperti macet, jadi setiap gulir
        // yang mereda dikunci ke kode terdekat.
        let snapTimer = null;
        let goTimer = null;
        let userMoved = false;

        // Roda ini pemilih, bukan sekadar sorotan: begitu berhenti di kode
        // lain, bahasanya benar-benar berpindah. Ditunda sesaat supaya kode
        // yang cuma terlewat saat menggeser tidak ikut membuka halaman.
        const goToCentered = () => {
            const nearest = track.querySelector('.lang-item.is-center');
            if (!nearest || !userMoved) return;
            if (nearest.classList.contains('active')) return;
            wheel.classList.add('is-loading');
            if (typeof window.gmBeforeLangChange === 'function') window.gmBeforeLangChange();
            window.location.href = nearest.href;
        };

        const snapToNearest = () => {
            const nearest = track.querySelector('.lang-item.is-center');
            if (!nearest) return;
            const target = nearest.offsetLeft + nearest.offsetWidth / 2 - track.clientWidth / 2;
            clearTimeout(goTimer);
            if (Math.abs(target - track.scrollLeft) >= 1) {
                track.scrollTo({ left: target, behavior: 'smooth' });
            }
            goTimer = setTimeout(goToCentered, 420);
        };
        const scheduleSnap = () => {
            clearTimeout(snapTimer);
            snapTimer = setTimeout(snapToNearest, 140);
        };

        track.addEventListener('scroll', () => {
            markCenter();
            clearTimeout(goTimer);
            if (!dragging) scheduleSnap();
        }, { passive: true });

        track.addEventListener('wheel', (e) => {
            if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
            e.preventDefault();
            userMoved = true;
            track.scrollLeft += e.deltaY;
        }, { passive: false });

        // Geser dengan tetikus atau jari. Kalau jaraknya cukup jauh, klik
        // pada kode yang kebetulan ada di bawah kursor tidak ikut jalan.
        let dragging = false;
        let pressing = false;
        let startX = 0;
        let startScroll = 0;
        let moved = 0;

        track.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            pressing = true;
            dragging = false;
            moved = 0;
            startX = e.clientX;
            startScroll = track.scrollLeft;
        });

        track.addEventListener('pointermove', (e) => {
            if (!pressing) return;
            const dx = e.clientX - startX;
            moved = Math.max(moved, Math.abs(dx));

            // Penangkapan pointer baru dinyalakan setelah benar-benar
            // menggeser. Kalau dinyalakan sejak ditekan, sasaran klik
            // berpindah ke wadahnya dan kode di samping jadi tidak bisa
            // diklik sama sekali.
            if (!dragging && moved > 3) {
                dragging = true;
                userMoved = true;
                track.setPointerCapture(e.pointerId);
                track.classList.add('is-dragging');
            }
            if (dragging) track.scrollLeft = startScroll - dx;
        });

        const endDrag = (e) => {
            pressing = false;
            if (!dragging) return;
            dragging = false;
            track.classList.remove('is-dragging');
            try { track.releasePointerCapture(e.pointerId); } catch (err) { /* sudah lepas */ }
            snapToNearest();
        };
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);

        track.addEventListener('click', (e) => {
            if (moved > 6) {
                e.preventDefault();
                moved = 0;
                return;
            }
            const item = e.target.closest('.lang-item');
            if (!item) return;
            clearTimeout(goTimer);
            wheel.classList.add('is-loading');
            if (typeof window.gmBeforeLangChange === 'function') window.gmBeforeLangChange();
        });

        window.addEventListener('resize', centerActive);
        centerActive();
    })();

    // Tooltip untuk semua kontrol ber-data-tip. Satu elemen dipakai bersama dan
    // ditempel ke <body>, jadi tidak terpotong pembungkus ber-overflow hidden.
    (function () {
        const tip = document.createElement('div');
        tip.className = 'gm-tip';
        document.body.appendChild(tip);

        let showTimer = null;
        let current = null;

        function show(el) {
            current = el;
            tip.textContent = el.dataset.tip;
            tip.style.left = '0px';
            tip.style.top = '0px';

            const r = el.getBoundingClientRect();
            const t = tip.getBoundingClientRect();
            const left = Math.max(8, Math.min(r.left + r.width / 2 - t.width / 2, window.innerWidth - t.width - 8));
            const below = r.top - t.height - 10 < 8;

            tip.classList.toggle('is-below', below);
            tip.style.left = Math.round(left) + 'px';
            tip.style.top = Math.round(below ? r.bottom + 10 : r.top - t.height - 10) + 'px';
            tip.style.setProperty('--tip-arrow', Math.round(r.left + r.width / 2 - left) + 'px');
            tip.classList.add('is-on');
        }

        const hide = () => {
            clearTimeout(showTimer);
            current = null;
            tip.classList.remove('is-on');
        };

        document.addEventListener('pointerover', (e) => {
            const el = e.target.closest('[data-tip]');
            if (!el || el === current) return;
            clearTimeout(showTimer);
            showTimer = setTimeout(() => show(el), 60);
        });
        document.addEventListener('pointerout', (e) => { if (e.target.closest('[data-tip]')) hide(); });
        document.addEventListener('focusin', (e) => {
            const el = e.target.closest('[data-tip]');
            if (el) show(el);
        });
        document.addEventListener('focusout', hide);
        window.addEventListener('scroll', hide, { passive: true });
        document.addEventListener('click', hide);
    })();
</script>
@endonce
