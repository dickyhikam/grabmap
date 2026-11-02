@extends('layouts.report')

@section('title', __('awsconnect.page_title'))

@push('styles')
    .cn-wrap { max-width: 880px; margin: 0 auto; }

    .cn-card {
        background: var(--card); border-radius: var(--r-card);
        box-shadow: var(--shadow-card);
        padding: 22px 24px; margin-bottom: 16px;
    }
    .cn-hero { display: flex; gap: 16px; align-items: flex-start; }
    .cn-hero .ic {
        width: 48px; height: 48px; border-radius: 15px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--green-soft); color: var(--green-text); font-size: 1.3rem;
    }
    .cn-hero .ic.warn { background: var(--warn-soft); color: var(--warn-fg); }
    .cn-hero h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 800; margin: 0 0 6px; color: var(--ink); }
    .cn-hero p { margin: 0; font-size: 0.84rem; line-height: 1.6; color: var(--muted); }

    .cn-facts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 18px; }
    @media (max-width: 720px) { .cn-facts { grid-template-columns: 1fr; } }
    .cn-fact { background: var(--surface); border-radius: 14px; padding: 11px 14px; min-width: 0; }
    .cn-fact .lb { font-size: 0.66rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .cn-fact .vl {
        display: flex; align-items: center; gap: 8px; margin-top: 4px;
        font-family: ui-monospace, monospace; font-size: 0.8rem; color: var(--ink); word-break: break-all;
    }

    .cn-step { display: flex; gap: 14px; }
    .cn-step .num {
        width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--green); color: #fff; font-weight: 800; font-size: 0.85rem;
    }
    .cn-step .body { flex: 1; min-width: 0; }
    .cn-step h2 { font-size: 0.98rem; font-weight: 700; margin: 4px 0 6px; color: var(--ink); }
    .cn-step .lead { font-size: 0.8rem; color: var(--muted); margin: 0 0 12px; line-height: 1.6; }
    .cn-step ol { margin: 0 0 12px; padding-left: 18px; font-size: 0.82rem; line-height: 1.75; color: var(--ink); }
    .cn-step code { font-size: 0.78rem; background: var(--surface); padding: 1px 6px; border-radius: 6px; color: var(--ink); }

    .tag-rec {
        display: inline-block; margin-left: 8px; vertical-align: middle;
        font-size: 0.62rem; font-weight: 700; padding: 3px 9px; border-radius: 999px;
        background: var(--green-soft); color: var(--green-text);
    }

    .copy-btn {
        border: none; border-radius: 999px; padding: 3px 10px; flex-shrink: 0;
        background: var(--card); color: var(--muted);
        font-size: 0.66rem; font-weight: 700; cursor: pointer; font-family: inherit;
    }
    .copy-btn:hover { background: var(--green); color: #fff; }

    .code-box { position: relative; margin-bottom: 12px; }
    .code-box pre {
        margin: 0; max-height: 280px; overflow: auto;
        background: var(--surface); color: var(--ink);
        border-radius: 14px; padding: 14px 16px; font-size: 0.72rem; line-height: 1.55;
    }
    .code-box .copy-btn { position: absolute; top: 8px; right: 8px; }

    .cn-btn {
        display: inline-flex; align-items: center; gap: 8px;
        border: none; border-radius: 999px; padding: 10px 18px;
        background: var(--green); color: #fff; text-decoration: none;
        font-size: 0.82rem; font-weight: 700; cursor: pointer; font-family: inherit;
    }
    .cn-btn:hover { background: var(--green-dark, #04703a); color: #fff; }
    .cn-btn.soft { background: var(--surface); color: var(--ink); }
    .cn-btn.soft:hover { background: var(--line); }

    details.alt { margin-top: 14px; border-radius: 14px; background: var(--surface); padding: 12px 16px; }
    details.alt summary { cursor: pointer; font-size: 0.82rem; font-weight: 700; color: var(--ink); }
    details.alt[open] summary { margin-bottom: 12px; }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 12px 14px; margin-bottom: 12px;
        font-size: 0.76rem; line-height: 1.55;
        background: var(--surface); color: var(--muted);
    }
    .note.warn { background: var(--warn-soft); color: var(--warn-fg); }
    .note.bad { background: var(--danger-soft); color: var(--danger-fg); }

    .f-row { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 0 14px; }
    @media (max-width: 620px) { .f-row { grid-template-columns: 1fr; } }
    .form-field { margin-bottom: 14px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.8rem; outline: none;
        font-family: ui-monospace, monospace;
    }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    /* ---------- Validasi isian ---------- */
    .input-wrap { position: relative; }
    .input-wrap .form-input { padding-right: 46px; transition: border-color 0.18s, box-shadow 0.18s, background 0.18s; }
    .in-state {
        position: absolute; right: 14px; top: 50%; width: 22px; height: 22px; margin-top: -11px;
        display: flex; align-items: center; justify-content: center; border-radius: 50%;
        font-size: 0.72rem; color: #fff; opacity: 0; transform: scale(0.4);
        transition: opacity 0.2s, transform 0.25s cubic-bezier(0.34, 1.6, 0.5, 1), background 0.2s;
        pointer-events: none;
    }
    .in-state::before { font-family: 'bootstrap-icons'; }
    .form-input.is-valid { border-color: var(--green); }
    .form-input.is-valid + .in-state { opacity: 1; transform: none; background: var(--green); }
    .form-input.is-valid + .in-state::before { content: '\F633'; }
    .form-input.is-invalid {
        border-color: var(--danger-fg); background: var(--danger-soft);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--danger-fg) 14%, transparent);
    }
    .form-input.is-invalid + .in-state { opacity: 1; transform: none; background: var(--danger-fg); }
    .form-input.is-invalid + .in-state::before { content: '\F63C'; font-size: 0.8rem; }
    .input-wrap.shake { animation: fShake 0.42s cubic-bezier(0.36, 0.07, 0.19, 0.97); }
    @keyframes fShake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(3px); }
        30%, 50%, 70% { transform: translateX(-5px); }
        40%, 60% { transform: translateX(5px); }
    }

    .form-error {
        display: flex; align-items: flex-start; gap: 8px;
        margin-top: 8px; padding: 9px 12px; border-radius: 12px;
        background: var(--danger-soft); color: var(--danger-fg);
        font-size: 0.74rem; font-weight: 500; line-height: 1.45;
        animation: errIn 0.28s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    .form-error i { margin-top: 1px; }
    @keyframes errIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .input-wrap.shake, .form-error { animation: none; }
    }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* ---------- Panel pengecekan koneksi ---------- */
    .verify {
        border-radius: 18px; padding: 18px 18px 6px; margin-bottom: 14px;
        background: var(--surface); position: relative; overflow: hidden;
        animation: vIn 0.35s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    .verify[hidden] { display: none; }
    @keyframes vIn { from { opacity: 0; transform: translateY(6px) scale(0.98); } to { opacity: 1; transform: none; } }
    .verify-bar { height: 4px; border-radius: 999px; background: var(--line); overflow: hidden; margin-bottom: 16px; }
    .verify-bar span {
        display: block; height: 100%; width: 35%; border-radius: inherit;
        background: linear-gradient(90deg, transparent, var(--green), transparent);
        animation: vSweep 1.2s ease-in-out infinite;
    }
    .verify.done .verify-bar span { width: 100%; background: var(--green); animation: none; transition: width 0.4s; }
    .verify.fail .verify-bar span { width: 100%; background: var(--danger-fg); animation: none; }
    @keyframes vSweep { from { transform: translateX(-110%); } to { transform: translateX(310%); } }

    .verify-steps { list-style: none; margin: 0; padding: 0; }
    .verify-steps li {
        display: flex; align-items: center; gap: 12px; padding: 0 0 14px;
        color: var(--muted); transition: color 0.2s;
    }
    .verify-steps b { display: block; font-size: 0.82rem; font-weight: 600; }
    .verify-steps small { display: block; font-size: 0.68rem; font-family: ui-monospace, monospace; opacity: 0.8; margin-top: 1px; }
    .vs-ic {
        width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0; position: relative;
        display: flex; align-items: center; justify-content: center;
        background: var(--card); border: 2px solid var(--line);
        transition: background 0.25s, border-color 0.25s, transform 0.25s;
    }
    .verify-steps li.run { color: var(--ink); }
    .verify-steps li.run .vs-ic { border-color: var(--green-soft); }
    .verify-steps li.run .vs-ic::after {
        content: ''; position: absolute; inset: -2px; border-radius: 50%;
        border: 2px solid transparent; border-top-color: var(--green);
        animation: vSpin 0.8s linear infinite;
    }
    .verify-steps li.ok { color: var(--ink); }
    .verify-steps li.ok .vs-ic { background: var(--green); border-color: var(--green); transform: scale(1.06); }
    .verify-steps li.ok .vs-ic i::before { content: '\F633'; font-family: 'bootstrap-icons'; color: #fff; font-style: normal; font-size: 0.9rem; }
    .verify-steps li.bad { color: var(--danger-fg); }
    .verify-steps li.bad .vs-ic { background: var(--danger-fg); border-color: var(--danger-fg); }
    .verify-steps li.bad .vs-ic i::before { content: '\F659'; font-family: 'bootstrap-icons'; color: #fff; font-style: normal; font-size: 0.8rem; }
    @keyframes vSpin { to { transform: rotate(360deg); } }

    .cn-btn[disabled] { opacity: 0.6; cursor: progress; }
    .cn-btn .spin { width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.4); border-top-color: #fff; animation: vSpin 0.7s linear infinite; }

    @media (prefers-reduced-motion: reduce) {
        .verify, .verify-bar span, .verify-steps li.run .vs-ic::after { animation: none; }
    }
@endpush

@section('content')
@php
    $done    = $invite->isUsed();
    $expired = $invite->isExpired();
    $error   = session('connect_error');
    $role    = \App\Models\AwsConnectInvite::ROLE_NAME;
@endphp

<div class="cn-wrap">
    @if($done)
        @include('aws-connect._connected')
    @elseif($expired)
        <div class="cn-card">
            <div class="cn-hero">
                <div class="ic warn"><i class="bi bi-clock-history"></i></div>
                <div>
                    <h1>{{ __('awsconnect.expired_title') }}</h1>
                    <p>{{ __('awsconnect.expired_desc') }}</p>
                </div>
            </div>
        </div>
    @else
        {{-- ===================== Pembuka ===================== --}}
        <div class="cn-card">
            <div class="cn-hero">
                <div class="ic"><i class="bi bi-cloud-check-fill"></i></div>
                <div>
                    <h1>{{ __('awsconnect.hero_title', ['label' => $invite->label]) }}</h1>
                    <p>{{ __('awsconnect.hero_desc') }}</p>
                </div>
            </div>

            <div class="cn-facts">
                <div class="cn-fact">
                    <div class="lb">{{ __('awsconnect.fact_account') }}</div>
                    <div class="vl"><span id="fAccount">{{ $invite->trusted_account_id }}</span>
                        <button type="button" class="copy-btn" data-copy="fAccount">{{ __('awsconnect.copy') }}</button></div>
                </div>
                <div class="cn-fact">
                    <div class="lb">External ID</div>
                    <div class="vl"><span id="fExternal">{{ $invite->external_id }}</span>
                        <button type="button" class="copy-btn" data-copy="fExternal">{{ __('awsconnect.copy') }}</button></div>
                </div>
                <div class="cn-fact">
                    <div class="lb">{{ __('awsconnect.fact_role') }}</div>
                    <div class="vl"><span id="fRole">{{ $role }}</span>
                        <button type="button" class="copy-btn" data-copy="fRole">{{ __('awsconnect.copy') }}</button></div>
                </div>
            </div>

            <div class="note" style="margin:16px 0 0;">
                <i class="bi bi-shield-check"></i>
                <span>{{ __('awsconnect.hero_safe', ['date' => $invite->expires_at->wib()->translatedFormat('d M Y H:i') . ' WIB']) }}</span>
            </div>
        </div>

        {{-- ===================== 1. Cost Explorer ===================== --}}
        <div class="cn-card">
            <div class="cn-step">
                <div class="num">1</div>
                <div class="body">
                    <h2>{{ __('awsconnect.s1_title') }}</h2>
                    <p class="lead">{{ __('awsconnect.s1_lead') }}</p>
                    <ol>
                        <li>{!! __('awsconnect.s1_a') !!}</li>
                        <li>{!! __('awsconnect.s1_b') !!}</li>
                    </ol>
                    <div class="note" style="margin:0;">
                        <i class="bi bi-info-circle-fill"></i><span>{{ __('awsconnect.s1_note') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== 2. Buat role ===================== --}}
        <div class="cn-card">
            <div class="cn-step">
                <div class="num">2</div>
                <div class="body">
                    <h2>{{ __('awsconnect.s2_title') }} <span class="tag-rec">{{ __('awsconnect.recommended') }}</span></h2>
                    <p class="lead">{{ __('awsconnect.s2_lead') }}</p>

                    <a href="{{ route('aws-connect.template', $invite->token) }}" class="cn-btn" data-no-loader style="margin-bottom:14px;">
                        <i class="bi bi-download"></i> {{ __('awsconnect.s2_download') }}
                    </a>

                    <ol>
                        <li>{!! __('awsconnect.s2_a') !!}</li>
                        <li>{!! __('awsconnect.s2_b') !!}</li>
                        <li>{!! __('awsconnect.s2_c') !!}</li>
                        <li>{!! __('awsconnect.s2_d') !!}</li>
                        <li>{!! __('awsconnect.s2_e') !!}</li>
                        <li>{!! __('awsconnect.s2_f') !!}</li>
                    </ol>

                    <details class="alt">
                        <summary>{{ __('awsconnect.manual_title') }}</summary>
                        <ol>
                            <li>{!! __('awsconnect.m_a') !!}</li>
                            <li>{!! __('awsconnect.m_b', ['account' => e($invite->trusted_account_id)]) !!}</li>
                            <li>{!! __('awsconnect.m_c', ['external' => e($invite->external_id)]) !!}</li>
                            <li>{!! __('awsconnect.m_d') !!}</li>
                        </ol>
                        <div class="code-box">
                            <button type="button" class="copy-btn" data-copy="permJson">{{ __('awsconnect.copy') }}</button>
                            <pre id="permJson">{{ $permissionJson }}</pre>
                        </div>
                        <ol start="5">
                            <li>{!! __('awsconnect.m_e') !!}</li>
                            <li>{!! __('awsconnect.m_f', ['role' => e($role)]) !!}</li>
                            <li>{!! __('awsconnect.m_g') !!}</li>
                        </ol>
                        <div class="form-hint" style="margin-bottom:8px;">{{ __('awsconnect.m_trust') }}</div>
                        <div class="code-box" style="margin-bottom:0;">
                            <button type="button" class="copy-btn" data-copy="trustJson">{{ __('awsconnect.copy') }}</button>
                            <pre id="trustJson">{{ $trustJson }}</pre>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        {{-- ===================== 3. Kirim Role ARN ===================== --}}
        <div class="cn-card" id="submit">
            <div class="cn-step">
                <div class="num">3</div>
                <div class="body">
                    <h2>{{ __('awsconnect.s3_title') }}</h2>
                    <p class="lead">{{ __('awsconnect.s3_lead') }}</p>

                    @if($error)
                        <div class="note bad">
                            <i class="bi bi-x-circle-fill"></i>
                            <span>
                                <b>{{ $error['step'] === 'assume' ? __('awsconnect.err_assume') : __('awsconnect.err_list') }}</b><br>
                                {{ $error['step'] === 'assume' ? __('awsconnect.err_assume_hint', ['role' => $role]) : __('awsconnect.err_list_hint') }}
                                @if($error['error'])
                                    <br><code style="background:transparent;padding:0;">{{ $error['error'] }}</code>
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="note bad" id="liveError" hidden>
                        <i class="bi bi-x-circle-fill"></i>
                        <span><b data-err-title></b><br><span data-err-hint></span><br><code style="background:transparent;padding:0;" data-err-raw></code></span>
                    </div>

                    {{-- Panel pengecekan: tampil selama kami mencoba masuk lewat role klien. --}}
                    <div class="verify" id="verifyPanel" hidden aria-live="polite">
                        <div class="verify-bar"><span></span></div>
                        <ol class="verify-steps">
                            <li data-step="assume"><span class="vs-ic"><i></i></span><span><b>{{ __('awsconnect.v_assume') }}</b><small>sts:AssumeRole · External ID</small></span></li>
                            <li data-step="list_keys"><span class="vs-ic"><i></i></span><span><b>{{ __('awsconnect.v_keys') }}</b><small>geo:ListKeys · {{ $invite->region }}</small></span></li>
                            <li data-step="dashboard"><span class="vs-ic"><i></i></span><span><b>{{ __('awsconnect.v_dash') }}</b><small>{{ __('awsconnect.v_dash_d') }}</small></span></li>
                        </ol>
                    </div>

                    <form method="POST" action="{{ route('aws-connect.store', $invite->token) }}" id="connectForm" novalidate>
                        @csrf
                        <div class="f-row">
                            <div class="form-field">
                                <label class="form-label-sm" for="fRoleArn">Role ARN</label>
                                <div class="input-wrap">
                                    <input type="text" name="role_arn" id="fRoleArn" maxlength="2048" autocomplete="off" spellcheck="false"
                                           data-rule="arn" data-required="{{ __('awsconnect.err_arn_required') }}" data-invalid="{{ __('awsconnect.err_arn') }}"
                                           class="form-input @error('role_arn') is-invalid @enderror"
                                           value="{{ old('role_arn') }}" placeholder="arn:aws:iam::123456789012:role/{{ $role }}">
                                    <span class="in-state" aria-hidden="true"></span>
                                </div>
                                @error('role_arn')
                                    <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                                @else
                                    <div class="form-hint">{{ __('awsconnect.arn_hint') }}</div>
                                @enderror
                            </div>
                            <div class="form-field">
                                <label class="form-label-sm" for="fRegion">Region</label>
                                <div class="input-wrap">
                                    <input type="text" name="region" id="fRegion" maxlength="32"
                                           data-rule="region" data-required="{{ __('awsconnect.err_region_required') }}" data-invalid="{{ __('awsaccounts.err_region') }}"
                                           class="form-input @error('region') is-invalid @enderror"
                                           value="{{ old('region', $invite->region) }}">
                                    <span class="in-state" aria-hidden="true"></span>
                                </div>
                                @error('region')
                                    <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                                @else
                                    <div class="form-hint">{{ __('awsconnect.region_public_hint') }}</div>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="cn-btn" id="connectBtn">
                            <i class="bi bi-plug-fill"></i> <span>{{ __('awsconnect.s3_btn') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="cn-card">
            <div class="note" style="margin:0;">
                <i class="bi bi-cash-coin"></i><span>{{ __('awsconnect.cost_note') }}</span>
            </div>
            <div class="note" style="margin:10px 0 0;">
                <i class="bi bi-shield-lock-fill"></i><span>{{ __('awsconnect.revoke_how', ['role' => $role]) }}</span>
            </div>
        </div>
    @endif
</div>
@endsection

@section('footer-note', __('awsconnect.footer'))

@push('scripts')
<script>
    // Clipboard API hanya ada di secure context (HTTPS/localhost); di HTTP biasa
    // jatuh ke textarea + execCommand, sama seperti gmCopy di panel admin.
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise((resolve, reject) => {
            const area = document.createElement('textarea');
            area.value = text;
            area.setAttribute('readonly', '');
            area.style.cssText = 'position:fixed;top:-1000px;left:-1000px;opacity:0;';
            document.body.appendChild(area);

            area.select();
            area.setSelectionRange(0, text.length);

            let ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }

            area.remove();
            ok ? resolve() : reject(new Error('copy-failed'));
        });
    }

    document.querySelectorAll('[data-copy]').forEach((btn) => {
        // Tombol berikon hanya mengganti teks di <span>-nya, ikonnya tetap.
        const target = btn.querySelector('span') ?? btn;
        const label = target.textContent;

        btn.addEventListener('click', async () => {
            const text = document.getElementById(btn.dataset.copy).textContent.trim();
            try {
                await copyText(text);
                target.textContent = @json(__('awsconnect.copied_short'));
            } catch (e) {
                target.textContent = '✕';
            }
            setTimeout(() => { target.textContent = label; }, 1500);
        });
    });

    // ---------- Kirim Role ARN dengan panel pengecekan ----------
    // Server mengerjakan semua langkah dalam satu permintaan; panel menandai
    // langkah yang benar-benar gagal (dari jawaban server), bukan tebakan.
    (function () {
        const form  = document.getElementById('connectForm');
        const panel = document.getElementById('verifyPanel');
        const btn   = document.getElementById('connectBtn');
        const errBox = document.getElementById('liveError');
        if (!form || !panel || !window.fetch) return;

        const steps = [...panel.querySelectorAll('[data-step]')];
        const inputs = [...form.querySelectorAll('[data-rule]')];
        const RULES = {
            arn: /^arn:aws:iam::\d{12}:role\/[\w+=,.@\/-]{1,512}$/,
            region: /^[a-z0-9-]+$/,
        };

        function hintOf(input) {
            return input.closest('.form-field').querySelector('.form-hint');
        }

        function fieldError(input, message) {
            fieldClear(input);
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');

            const el = document.createElement('div');
            el.className = 'form-error';
            el.dataset.live = '1';
            el.setAttribute('role', 'alert');
            el.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
            el.querySelector('span').textContent = message;
            input.closest('.form-field').appendChild(el);
            if (hintOf(input)) hintOf(input).hidden = true;
        }

        function fieldClear(input) {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            input.closest('.form-field').querySelectorAll('.form-error').forEach((el) => el.remove());
            if (hintOf(input)) hintOf(input).hidden = false;
        }

        // Kosong / salah format / benar — yang benar diberi centang hijau.
        function check(input) {
            const value = input.value.trim();
            if (!value) { fieldError(input, input.dataset.required); return false; }
            if (!RULES[input.dataset.rule].test(value)) { fieldError(input, input.dataset.invalid); return false; }
            fieldClear(input);
            input.classList.add('is-valid');
            return true;
        }

        function shake(input) {
            const wrap = input.closest('.input-wrap');
            wrap.classList.remove('shake');
            void wrap.offsetWidth;
            wrap.classList.add('shake');
        }

        inputs.forEach((input) => {
            // ARN yang ditempel sering membawa spasi atau baris baru di ujungnya.
            input.addEventListener('paste', () => setTimeout(() => { input.value = input.value.trim(); check(input); }));
            input.addEventListener('blur', () => { if (input.value.trim()) check(input); });
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid') || input.classList.contains('is-valid')) check(input);
            });
            if (input.value.trim() && !input.classList.contains('is-invalid')) check(input);
        });
        const btnHtml = btn.innerHTML;
        const wait = (ms) => new Promise((r) => setTimeout(r, ms));
        const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function mark(li, state) {
            li.classList.remove('run', 'ok', 'bad');
            if (state) li.classList.add(state);
        }

        function reset() {
            panel.classList.remove('done', 'fail');
            steps.forEach((li) => mark(li, null));
            errBox.hidden = true;
        }

        function showError(title, hint, raw) {
            errBox.querySelector('[data-err-title]').textContent = title || '';
            errBox.querySelector('[data-err-hint]').textContent = hint || '';
            errBox.querySelector('[data-err-raw]').textContent = raw || '';
            errBox.hidden = false;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Periksa di browser dulu; semua galat tampil sekaligus.
            const bad = inputs.filter((input) => !check(input));
            if (bad.length) {
                bad.forEach(shake);
                bad[0].focus();
                return;
            }

            inputs.forEach((input) => { input.value = input.value.trim(); });
            reset();

            document.querySelectorAll('.cn-card .note.bad:not(#liveError)').forEach((el) => el.remove());
            panel.hidden = false;
            mark(steps[0], 'run');
            btn.disabled = true;
            btn.innerHTML = '<span class="spin"></span> <span>' + @json(__('awsconnect.v_checking')) + '</span>';

            const started = Date.now();
            let res, data = {};
            try {
                res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                data = await res.json().catch(() => ({}));
            } catch (err) {
                res = null;
            }

            // Biar langkah pertama sempat terlihat berjalan.
            const minRun = still ? 0 : 900;
            if (Date.now() - started < minRun) await wait(minRun - (Date.now() - started));

            if (res && res.ok && data.ok) {
                const gap = still ? 0 : 380;
                for (let i = 0; i < steps.length; i++) {
                    mark(steps[i], 'ok');
                    if (steps[i + 1]) mark(steps[i + 1], 'run');
                    await wait(gap);
                }
                panel.classList.add('done');
                await wait(still ? 0 : 450);
                window.location.href = data.redirect;
                return;
            }

            panel.classList.add('fail');
            btn.disabled = false;
            btn.innerHTML = btnHtml;

            if (res && res.status === 422 && data.errors) {
                // Galat validasi: tandai isian, panel tidak relevan.
                panel.hidden = true;
                Object.entries(data.errors).forEach(([name, messages]) => {
                    const input = form.querySelector('[name="' + name + '"]');
                    if (!input) return;
                    fieldError(input, messages[0]);
                    shake(input);
                });
                return;
            }

            if (data.step) {
                const failAt = steps.findIndex((li) => li.dataset.step === data.step);
                steps.forEach((li, i) => mark(li, i < failAt ? 'ok' : (i === failAt ? 'bad' : null)));
                showError(data.title, data.hint, data.error);
                return;
            }

            mark(steps[0], 'bad');
            if (res && res.status === 410) {
                window.location.reload();
                return;
            }
            showError(@json(__('awsconnect.v_network')), '', res ? res.status + ' ' + res.statusText : '');
        });
    })();
</script>
@endpush
