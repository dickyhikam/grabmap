{{-- Tampilan setelah klien terhubung: konfirmasi + link dashboard pemakaiannya. --}}
@php
    $share     = $invite->usageShare;
    $dashUrl   = $share?->isActive() ? $share->publicUrl() : null;
    $account   = $invite->awsAccount;
    $justNow   = $invite->used_at->gt(now()->subMinutes(10));

    // Resource Location di akun klien beserta data source-nya (provider).
    $res       = $account?->resources ?? [];
    $resList   = collect(['place_indexes', 'maps', 'route_calculators', 'geofence_collections'])
        ->flatMap(fn ($t) => collect($res[$t] ?? [])->map(fn ($r) => ['type' => $t] + $r));
    $sources   = $resList->pluck('data_source')->filter()->unique()->values();
@endphp

@push('styles')
    /* ---------- Panggung sukses ---------- */
    .ok-stage {
        position: relative; overflow: hidden; text-align: center;
        background: var(--card); border-radius: var(--r-card);
        box-shadow: var(--shadow-card);
        padding: 44px 24px 30px; margin-bottom: 16px;
    }
    .ok-stage::before {
        content: ''; position: absolute; inset: -40% -10% auto; height: 120%;
        background: radial-gradient(closest-side, var(--green-soft), transparent 70%);
        pointer-events: none;
    }
    .ok-stage > * { position: relative; }

    .ok-mark { width: 104px; height: 104px; margin: 0 auto 20px; position: relative; }
    .ok-mark svg { width: 100%; height: 100%; display: block; }
    .ok-mark .ring { fill: none; stroke: var(--green); stroke-width: 5; stroke-linecap: round;
        stroke-dasharray: 302; stroke-dashoffset: 302; animation: okRing 0.9s cubic-bezier(0.65, 0, 0.35, 1) forwards; }
    .ok-mark .tick { fill: none; stroke: var(--green); stroke-width: 6; stroke-linecap: round; stroke-linejoin: round;
        stroke-dasharray: 60; stroke-dashoffset: 60; animation: okTick 0.45s 0.75s cubic-bezier(0.65, 0, 0.35, 1) forwards; }
    .ok-mark .halo { position: absolute; inset: 0; border-radius: 50%; box-shadow: 0 0 0 0 var(--green-soft);
        animation: okHalo 1.6s 1.1s ease-out infinite; }
    @keyframes okRing { to { stroke-dashoffset: 0; } }
    @keyframes okTick { to { stroke-dashoffset: 0; } }
    @keyframes okHalo {
        0%   { box-shadow: 0 0 0 0 rgba(0, 177, 79, 0.28); }
        100% { box-shadow: 0 0 0 26px rgba(0, 177, 79, 0); }
    }

    /* Butiran kecil yang memercik sekali saat baru terhubung. */
    .ok-burst { position: absolute; left: 50%; top: 96px; width: 0; height: 0; }
    .ok-burst i {
        position: absolute; width: 7px; height: 7px; border-radius: 2px; opacity: 0;
        background: var(--c, var(--green));
        animation: okBurst 1.1s 0.8s cubic-bezier(0.2, 0.7, 0.3, 1) forwards;
    }
    @keyframes okBurst {
        0%   { opacity: 1; transform: translate(0, 0) rotate(0deg) scale(1); }
        100% { opacity: 0; transform: translate(var(--x), var(--y)) rotate(var(--r)) scale(0.6); }
    }

    .ok-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.6rem; font-weight: 800;
        letter-spacing: -0.02em; color: var(--ink); margin: 0 0 8px;
        opacity: 0; animation: okUp 0.5s 0.9s ease forwards; }
    .ok-sub { max-width: 520px; margin: 0 auto; font-size: 0.88rem; line-height: 1.6; color: var(--muted);
        opacity: 0; animation: okUp 0.5s 1s ease forwards; }
    @keyframes okUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .ok-chips { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 20px; }
    .ok-chip {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 13px; border-radius: 999px;
        background: var(--surface); color: var(--ink);
        font-size: 0.74rem; font-weight: 600;
        opacity: 0; animation: okUp 0.45s ease forwards;
    }
    .ok-chip i { color: var(--green-text); }

    /* ---------- Kartu dashboard ---------- */
    .dash-card {
        position: relative; overflow: hidden;
        border-radius: var(--r-card); padding: 26px; margin-bottom: 16px; color: #fff;
        background: linear-gradient(135deg, #00b14f 0%, #058a43 48%, #04612f 100%);
        box-shadow: 0 18px 40px -18px rgba(0, 120, 55, 0.55);
        opacity: 0; animation: okUp 0.55s 1.15s ease forwards;
    }
    .dash-card::after {
        content: ''; position: absolute; right: -60px; top: -60px; width: 240px; height: 240px; border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.22), transparent 65%);
        pointer-events: none;
    }
    .dash-top { display: flex; align-items: flex-start; gap: 14px; }
    .dash-ic {
        width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(6px);
    }
    .dash-eyebrow { font-size: 0.66rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; opacity: 0.8; }
    .dash-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 800; margin-top: 3px; }
    .dash-desc { font-size: 0.8rem; opacity: 0.88; margin-top: 4px; line-height: 1.55; }

    .dash-url {
        display: flex; align-items: center; gap: 10px; margin-top: 18px;
        background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 14px; padding: 10px 12px;
    }
    .dash-url code {
        flex: 1; min-width: 0; color: #fff; background: none; padding: 0;
        font-size: 0.74rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .dash-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
    .dash-btn {
        display: inline-flex; align-items: center; gap: 8px;
        border: none; border-radius: 999px; padding: 11px 20px;
        font-size: 0.84rem; font-weight: 700; font-family: inherit; cursor: pointer; text-decoration: none;
        transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .dash-btn.primary { background: #fff; color: #046c35; box-shadow: 0 6px 18px -6px rgba(0, 0, 0, 0.35); }
    .dash-btn.primary:hover { transform: translateY(-1px); color: #046c35; }
    .dash-btn.glass { background: rgba(255, 255, 255, 0.16); color: #fff; }
    .dash-btn.glass:hover { background: rgba(255, 255, 255, 0.26); color: #fff; }
    .dash-note { display: flex; gap: 8px; margin-top: 16px; font-size: 0.74rem; line-height: 1.5; opacity: 0.9; }

    /* ---------- Langkah berikutnya & rincian ---------- */
    .ok-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 16px; }
    @media (max-width: 760px) { .ok-grid { grid-template-columns: 1fr; } }
    .ok-grid .cn-card { margin-bottom: 0; opacity: 0; animation: okUp 0.5s 1.3s ease forwards; }
    .ok-grid .cn-card + .cn-card { animation-delay: 1.38s; }
    .ok-h { font-size: 0.82rem; font-weight: 700; color: var(--ink); margin: 0 0 14px; }

    .tl { list-style: none; margin: 0; padding: 0; }
    .tl li { display: flex; gap: 12px; position: relative; padding-bottom: 16px; }
    .tl li:last-child { padding-bottom: 0; }
    .tl li:not(:last-child)::before {
        content: ''; position: absolute; left: 13px; top: 28px; bottom: 2px; width: 2px; background: var(--line);
    }
    .tl .dot {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 0.78rem;
        background: var(--surface); color: var(--muted);
    }
    .tl li.now .dot { background: var(--green-soft); color: var(--green-text); }
    .tl li.now .dot i { animation: spin 1.2s linear infinite; }
    .tl .t { font-size: 0.8rem; font-weight: 600; color: var(--ink); margin-top: 4px; }
    .tl .d { font-size: 0.72rem; color: var(--muted); margin-top: 2px; line-height: 1.5; }

    .kv-row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--line); font-size: 0.78rem; }
    .kv-row:last-child { border-bottom: none; }
    .kv-row .k { color: var(--muted); }
    .kv-row .v { font-weight: 600; color: var(--ink); text-align: right; word-break: break-all; font-family: ui-monospace, monospace; font-size: 0.74rem; }

    @keyframes spin { to { transform: rotate(360deg); } }

    @media (prefers-reduced-motion: reduce) {
        .ok-mark .ring, .ok-mark .tick { animation-duration: 0s; animation-delay: 0s; }
        .ok-mark .halo, .ok-burst { display: none; }
        .ok-title, .ok-sub, .ok-chip, .dash-card, .ok-grid .cn-card { animation: none; opacity: 1; }
    }
@endpush

<div class="ok-stage">
    <div class="ok-mark">
        <span class="halo"></span>
        <svg viewBox="0 0 104 104" aria-hidden="true">
            <circle class="ring" cx="52" cy="52" r="48"></circle>
            <path class="tick" d="M32 54 l13 13 l27 -29"></path>
        </svg>
    </div>

    @if($justNow)
        <div class="ok-burst" aria-hidden="true">
            @foreach([
                ['-120px', '-40px', '#00b14f', '160deg'], ['120px', '-30px', '#f5b400', '-140deg'],
                ['-90px', '-95px', '#2f80ed', '90deg'],   ['95px', '-90px', '#00b14f', '-80deg'],
                ['-150px', '20px', '#f5b400', '200deg'],  ['150px', '25px', '#2f80ed', '-210deg'],
                ['-40px', '-120px', '#00b14f', '45deg'],  ['45px', '-125px', '#e8505b', '-60deg'],
                ['-70px', '60px', '#e8505b', '120deg'],   ['75px', '55px', '#00b14f', '-130deg'],
            ] as [$x, $y, $c, $r])
                <i style="--x:{{ $x }};--y:{{ $y }};--c:{{ $c }};--r:{{ $r }};"></i>
            @endforeach
        </div>
    @endif

    <h1 class="ok-title">{{ __('awsconnect.ok_title') }}</h1>
    <p class="ok-sub">{{ __('awsconnect.ok_sub', ['label' => $invite->label]) }}</p>

    <div class="ok-chips">
        <span class="ok-chip" style="animation-delay:1.05s"><i class="bi bi-shield-check"></i> {{ __('awsconnect.ok_chip_role') }}</span>
        @if($invite->keys_found !== null)
            <span class="ok-chip" style="animation-delay:1.12s"><i class="bi bi-key-fill"></i> {{ __('awsconnect.ok_chip_keys', ['n' => $invite->keys_found]) }}</span>
        @endif
        @if($resList->isNotEmpty())
            <span class="ok-chip" style="animation-delay:1.16s"><i class="bi bi-boxes"></i> {{ __('awsconnect.ok_chip_resources', ['n' => $resList->count()]) }}</span>
        @endif
        @foreach($sources as $src)
            <span class="ok-chip" style="animation-delay:1.18s"><i class="bi bi-globe2"></i> {{ \App\Models\AwsUsageTypeDaily::PROVIDERS[$src] ?? $src }}</span>
        @endforeach
        <span class="ok-chip" style="animation-delay:1.21s"><i class="bi bi-arrow-repeat"></i> {{ __('awsconnect.ok_chip_daily') }}</span>
    </div>

    @if($invite->keys_found === 0)
        {{-- Klien tanpa API key (mis. Place Index v1 lewat IAM): angkanya datang dari tagihan. --}}
        <div class="note" style="max-width:560px;margin:18px auto 0;text-align:left;opacity:0;animation:okUp 0.45s 1.25s ease forwards;">
            <i class="bi bi-receipt"></i><span>{{ __('awsconnect.ok_no_keys_note') }}</span>
        </div>
    @endif
</div>

@if($dashUrl)
    <div class="dash-card">
        <div class="dash-top">
            <div class="dash-ic"><i class="bi bi-bar-chart-line-fill"></i></div>
            <div style="min-width:0;">
                <div class="dash-eyebrow">{{ __('awsconnect.dash_eyebrow') }}</div>
                <div class="dash-title">{{ __('awsconnect.dash_title', ['label' => $invite->label]) }}</div>
                <div class="dash-desc">{{ __('awsconnect.dash_desc') }}</div>
            </div>
        </div>

        <div class="dash-url">
            <i class="bi bi-link-45deg"></i>
            <code id="dashUrl">{{ $dashUrl }}</code>
        </div>

        <div class="dash-actions">
            <a href="{{ $dashUrl }}" target="_blank" rel="noopener" class="dash-btn primary" data-no-loader>
                <i class="bi bi-box-arrow-up-right"></i> {{ __('awsconnect.dash_open') }}
            </a>
            <button type="button" class="dash-btn glass" data-copy="dashUrl" data-copy-label>
                <i class="bi bi-clipboard"></i> <span>{{ __('awsconnect.dash_copy') }}</span>
            </button>
        </div>

        <div class="dash-note">
            <i class="bi bi-bookmark-star-fill"></i><span>{{ __('awsconnect.dash_note') }}</span>
        </div>
    </div>
@else
    <div class="cn-card">
        <div class="note" style="margin:0;">
            <i class="bi bi-envelope-paper-fill"></i><span>{{ __('awsconnect.dash_later') }}</span>
        </div>
    </div>
@endif

<div class="ok-grid">
    <div class="cn-card">
        <div class="ok-h">{{ __('awsconnect.next_title') }}</div>
        <ul class="tl">
            <li class="{{ $justNow ? 'now' : '' }}">
                <span class="dot"><i class="bi {{ $justNow ? 'bi-arrow-repeat' : 'bi-check-lg' }}"></i></span>
                <span><span class="t d-block">{{ __('awsconnect.next_1') }}</span><span class="d d-block">{{ __('awsconnect.next_1_d') }}</span></span>
            </li>
            <li>
                <span class="dot"><i class="bi bi-bar-chart-line"></i></span>
                <span><span class="t d-block">{{ __('awsconnect.next_2') }}</span><span class="d d-block">{{ __('awsconnect.next_2_d') }}</span></span>
            </li>
            <li>
                <span class="dot"><i class="bi bi-calendar-check"></i></span>
                <span><span class="t d-block">{{ __('awsconnect.next_3') }}</span><span class="d d-block">{{ __('awsconnect.next_3_d') }}</span></span>
            </li>
        </ul>
    </div>

    <div class="cn-card">
        <div class="ok-h">{{ __('awsconnect.detail_title') }}</div>
        <div class="kv-row"><span class="k">{{ __('awsconnect.detail_account') }}</span><span class="v">{{ $account?->account_number ?? '—' }}</span></div>
        <div class="kv-row"><span class="k">{{ __('awsconnect.fact_role') }}</span><span class="v">{{ \Illuminate\Support\Str::afterLast((string) $account?->role_arn, '/') ?: '—' }}</span></div>
        <div class="kv-row"><span class="k">Region</span><span class="v">{{ $account?->region ?? '—' }}</span></div>
        <div class="kv-row"><span class="k">{{ __('awsconnect.detail_at') }}</span><span class="v">{{ $invite->used_at->wib()->translatedFormat('d M Y H:i') }} WIB</span></div>
        <div class="note" style="margin:14px 0 0;">
            <i class="bi bi-shield-lock-fill"></i><span>{{ __('awsconnect.revoke_how', ['role' => \App\Models\AwsConnectInvite::ROLE_NAME]) }}</span>
        </div>
    </div>
</div>
