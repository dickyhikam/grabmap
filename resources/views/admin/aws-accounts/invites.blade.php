@extends('layouts.admin-v2')

@section('title', __('awsconnect.admin_title'))

@push('styles')
    .inv-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 16px;
        align-items: start;
        margin-bottom: 16px;
    }
    @media (max-width: 1000px) { .inv-grid { grid-template-columns: 1fr; } }

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    .f-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
    @media (max-width: 620px) { .f-row { grid-template-columns: 1fr; } }

    .form-field { margin-bottom: 18px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-label-sm .opt { font-weight: 600; color: var(--faint); }
    .req { color: var(--danger-fg); margin-left: 3px; font-weight: 700; }
    .form-input {
        width: 100%; height: 46px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .form-input.mono { font-family: ui-monospace, monospace; font-size: 0.8rem; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; line-height: 1.5; }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 12px 14px; margin-bottom: 14px;
        font-size: 0.74rem; line-height: 1.55;
        background: var(--surface); color: var(--muted);
    }
    .note.warn { background: var(--warn-soft); color: var(--warn-fg); }

    .code-box { position: relative; }
    .code-box pre {
        margin: 0; max-height: 260px; overflow: auto;
        background: var(--surface); color: var(--ink);
        border-radius: 14px; padding: 14px 16px;
        font-size: 0.72rem; line-height: 1.55;
    }
    .code-box .copy {
        position: absolute; top: 8px; right: 8px;
        border: none; border-radius: 999px; padding: 5px 11px;
        background: var(--card); color: var(--muted);
        font-size: 0.68rem; font-weight: 700; cursor: pointer;
    }
    .code-box .copy:hover { background: var(--green); color: #fff; }

    .steps { margin: 0 0 14px; padding-left: 18px; font-size: 0.78rem; line-height: 1.7; color: var(--ink); }

    /* ---------- Daftar undangan ---------- */
    .inv-head { flex-wrap: wrap; gap: 12px; }
    .inv-tabs { display: flex; flex-wrap: wrap; gap: 4px; padding: 4px; border-radius: 999px; background: var(--surface); }
    .inv-tab {
        display: inline-flex; align-items: center; gap: 7px;
        border: none; border-radius: 999px; padding: 7px 14px;
        background: transparent; color: var(--muted);
        font-size: 0.74rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s, color 0.15s, box-shadow 0.15s;
    }
    .inv-tab .n { min-width: 20px; padding: 1px 7px; border-radius: 999px; background: var(--card); font-size: 0.66rem; text-align: center; }
    .inv-tab:hover { color: var(--ink); }
    .inv-tab.on { background: var(--card); color: var(--ink); box-shadow: 0 1px 6px rgba(20, 27, 24, 0.08); }
    .inv-tab.on .n { background: var(--green); color: #fff; }

    .inv-list { display: flex; flex-direction: column; gap: 10px; }
    .inv-row {
        position: relative;
        display: grid; align-items: center; gap: 14px 22px;
        /* Kolom aksi lebarnya tetap: dengan 'auto' tiap baris punya lebar sendiri
           (jumlah tombolnya beda), sehingga kolom lain ikut bergeser. */
        grid-template-columns: minmax(220px, 1.25fr) minmax(170px, 0.9fr) minmax(250px, 1.3fr) 236px;
        padding: 14px 16px 14px 20px; border-radius: 18px;
        background: var(--surface); border: 1px solid transparent;
        transition: border-color 0.18s, background 0.18s, transform 0.18s;
        animation: invIn 0.34s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .inv-row::before {
        content: ''; position: absolute; left: 0; top: 14px; bottom: 14px; width: 4px; border-radius: 0 4px 4px 0;
        background: var(--line);
    }
    .inv-row.st-pending::before { background: var(--tone-indigo-fg); }
    .inv-row.st-used::before { background: var(--green); }
    .inv-row.st-expired { opacity: 0.78; }
    .inv-row.st-expired::before { background: var(--warn-fg); }
    .inv-row:hover { background: var(--card); border-color: var(--line); }
    .inv-row.fresh { border-color: var(--green); background: var(--green-soft); }
    .inv-row[hidden] { display: none; }
    @keyframes invIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    @media (max-width: 1100px) {
        .inv-row { grid-template-columns: 1fr 1fr; }
        .inv-act { grid-column: 1 / -1; justify-content: flex-start; }
    }
    @media (max-width: 640px) { .inv-row { grid-template-columns: 1fr; } }

    .inv-who { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .inv-av {
        width: 42px; height: 42px; border-radius: 14px; flex-shrink: 0; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: var(--card); color: var(--ink);
        font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 0.8rem;
        box-shadow: inset 0 0 0 1px var(--line);
    }
    .inv-av img { width: 100%; height: 100%; object-fit: contain; padding: 5px; }
    .inv-nm { display: block; font-weight: 700; font-size: 0.88rem; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .inv-meta { display: block; margin-top: 3px; font-size: 0.7rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .inv-meta a { color: var(--muted); text-decoration: none; font-weight: 600; }
    .inv-meta a:hover { color: var(--green-text); }

    .inv-state { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; min-width: 0; }
    .inv-sub { font-size: 0.7rem; color: var(--muted); }
    .inv-life { display: block; width: 100%; max-width: 150px; height: 4px; border-radius: 999px; background: var(--line); overflow: hidden; }
    .inv-life span { display: block; height: 100%; border-radius: inherit; background: var(--tone-indigo-fg); }

    .inv-trust { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .inv-src { font-size: 0.78rem; font-weight: 600; color: var(--ink); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .inv-src i { color: var(--muted); }
    .inv-src .acct { font-family: ui-monospace, monospace; font-size: 0.68rem; font-weight: 500; color: var(--muted); }
    .ext-chip {
        align-self: flex-start;
        display: inline-flex; align-items: center; gap: 8px; max-width: 100%;
        border: 1px dashed var(--line); border-radius: 10px; padding: 5px 10px;
        background: var(--card); color: var(--ink); cursor: pointer; text-align: left;
        transition: border-color 0.15s;
    }
    .ext-chip .k { font-size: 0.6rem; font-weight: 700; color: var(--faint); text-transform: uppercase; letter-spacing: 0.05em; flex-shrink: 0; }
    .ext-chip code { font-size: 0.7rem; color: var(--ink); background: none; padding: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ext-chip i { font-size: 0.7rem; color: var(--faint); flex-shrink: 0; }
    .ext-chip:hover { border-color: var(--green); }
    .ext-chip:hover i { color: var(--green-text); }

    .inv-act { display: flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: wrap; }
    .act-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 7px;
        height: 36px; padding: 0 14px; border: none; border-radius: 999px;
        background: var(--card); color: var(--ink); box-shadow: inset 0 0 0 1px var(--line);
        font-size: 0.74rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap;
        transition: background 0.15s, color 0.15s, box-shadow 0.15s, transform 0.15s;
    }
    .act-btn:hover { background: var(--green); color: #fff; box-shadow: none; }
    .act-btn.icon { width: 36px; padding: 0; }
    .act-btn.primary { background: var(--green); color: #fff; box-shadow: 0 4px 12px rgba(0, 177, 79, 0.28); }
    .act-btn.primary:hover { transform: translateY(-1px); color: #fff; }
    .act-btn.danger:hover { background: var(--danger-fg); color: #fff; }

    .inv-none { padding: 26px 10px; text-align: center; font-size: 0.8rem; color: var(--muted); }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.plain { background: var(--surface); color: var(--muted); }
    .pill-badge.warn  { background: var(--warn-soft); color: var(--warn-fg); }
    .pill-badge.wait  { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pill-badge.wait .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: waitPulse 1.6s ease-in-out infinite; }
    @keyframes waitPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
    @media (prefers-reduced-motion: reduce) { .inv-row, .pill-badge.wait .dot { animation: none; } }


    .btn-row { display: flex; justify-content: flex-end; gap: 10px; }

    /* ---------- Modal konfirmasi ---------- */
    .gm-modal {
        position: fixed; inset: 0; z-index: 1250;
        display: flex; align-items: flex-start; justify-content: center;
        padding: 10vh 16px 16px;
        background: rgba(12, 18, 15, 0.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden;
        transition: opacity 0.2s ease, visibility 0.2s;
    }
    .gm-modal.open { opacity: 1; visibility: visible; }
    .gm-modal-card {
        width: 100%; max-width: 420px;
        background: var(--card); border-radius: 24px;
        box-shadow: var(--shadow-pop); overflow: hidden;
        transform: translateY(-14px) scale(0.97); opacity: 0;
        transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.5, 1), opacity 0.2s ease;
    }
    .gm-modal.open .gm-modal-card { transform: none; opacity: 1; }
    .gm-modal-head { text-align: center; padding: 26px 24px 18px; }
    .gm-modal-icon {
        position: relative; width: 54px; height: 54px; border-radius: 18px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.45rem; margin-bottom: 12px;
        background: var(--danger-soft); color: var(--danger-fg);
    }
    .gm-modal-icon .slash { position: absolute; font-size: 1.6rem; }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-sub { font-size: 0.77rem; color: var(--muted); margin-top: 5px; }
    .gm-modal-body { padding: 0 24px 24px; }
    .gm-modal .btn-row > * { flex: 1; justify-content: center; }
    .who {
        display: flex; align-items: center; gap: 11px;
        background: var(--surface); border-radius: 14px;
        padding: 11px 14px; margin-bottom: 12px;
    }
    .who-ic {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--card); color: var(--muted); font-size: 0.85rem;
    }
    .who-nm { display: block; font-weight: 700; font-size: 0.85rem; }
    .who-sb { display: block; font-size: 0.7rem; color: var(--muted); margin-top: 2px; overflow-wrap: anywhere; }
    .who-sb code { font-size: 0.68rem; color: var(--muted); background: none; padding: 0; }
    .gm-modal .note { margin-bottom: 16px; }
    .gm-modal .note[hidden] { display: none; }
    @media (prefers-reduced-motion: reduce) {
        .gm-modal, .gm-modal-card { transition: none; }
    }
@endpush

@section('content')
@php
    $regions = ['ap-southeast-1', 'ap-southeast-3', 'ap-southeast-5', 'us-east-1'];
    $freshId = session('new_invite');
@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.aws-accounts.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('awsaccounts.back') }}
        </a>
        <h1 class="q-title">{{ __('awsconnect.admin_title') }} <span class="soft">{{ $invites->count() }}</span></h1>
    </div>
</div>

<div class="inv-grid">
    {{-- ===================== Buat undangan ===================== --}}
    <form method="POST" action="{{ route('admin.aws-invites.store') }}" class="q-card" style="padding:24px;">
        @csrf
        <div class="q-card-head">
            <div class="d-flex align-items-center gap-2">
                <div class="q-icon-box"><i class="bi bi-envelope-plus"></i></div>
                <div>
                    <div class="q-card-title">{{ __('awsconnect.new_title') }}</div>
                    <div class="q-card-sub">{{ __('awsconnect.new_sub') }}</div>
                </div>
            </div>
        </div>

        @if($sourceAccounts->isEmpty())
            <div class="note warn">
                <i class="bi bi-exclamation-triangle-fill"></i><span>{{ __('awsconnect.no_source') }}</span>
            </div>
        @endif

        <div class="f-row">
            <div class="form-field">
                <label class="form-label-sm">{{ __('awsconnect.label') }}<span class="req">*</span></label>
                <input type="text" name="label" maxlength="100" required
                       class="form-input @error('label') is-invalid @enderror"
                       value="{{ old('label') }}" placeholder="{{ __('awsconnect.label_ph') }}">
                @error('label')
                    <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                @else
                    <div class="form-hint">{{ __('awsconnect.label_hint') }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('awsconnect.company') }} <span class="opt">{{ __('awsaccounts.optional') }}</span></label>
                <select name="company_id" class="form-input">
                    <option value="">{{ __('awsconnect.company_none') }}</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <div class="form-hint">{{ __('awsconnect.company_hint') }}</div>
            </div>
        </div>

        <div class="f-row">
            <div class="form-field">
                <label class="form-label-sm">{{ __('awsconnect.source') }}<span class="req">*</span></label>
                <select name="source_account_id" class="form-input @error('source_account_id') is-invalid @enderror" required>
                    @foreach($sourceAccounts as $source)
                        <option value="{{ $source->id }}" @selected(old('source_account_id', $sourceAccounts->firstWhere('is_default', true)?->id) == $source->id)>
                            {{ $source->name }}{{ $source->account_number ? ' · ' . $source->account_number : '' }}
                        </option>
                    @endforeach
                </select>
                @error('source_account_id')
                    <div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>
                @else
                    <div class="form-hint">{{ __('awsconnect.source_hint') }}</div>
                @enderror
            </div>

            <div class="form-field">
                <label class="form-label-sm">{{ __('awsaccounts.region') }}<span class="req">*</span></label>
                <select name="region" class="form-input">
                    @foreach($regions as $r)
                        <option value="{{ $r }}" @selected(old('region', 'ap-southeast-1') === $r)>{{ $r }}</option>
                    @endforeach
                </select>
                <div class="form-hint">{{ __('awsconnect.region_hint') }}</div>
            </div>
        </div>

        <div class="form-field">
            <label class="form-label-sm">{{ __('awsconnect.days') }}<span class="req">*</span></label>
            <select name="days" class="form-input">
                @foreach([3, 7, 14, 30] as $d)
                    <option value="{{ $d }}" @selected((int) old('days', 7) === $d)>{{ __('awsconnect.days_n', ['n' => $d]) }}</option>
                @endforeach
            </select>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn-solid" @disabled($sourceAccounts->isEmpty())>
                <i class="bi bi-link-45deg"></i> {{ __('awsconnect.create_btn') }}
            </button>
        </div>
    </form>

    {{-- ===================== Persiapan akun sumber ===================== --}}
    <div class="q-card" style="padding:24px;">
        <div class="q-card-head">
            <div class="d-flex align-items-center gap-2">
                <div class="q-icon-box"><i class="bi bi-shield-check"></i></div>
                <div>
                    <div class="q-card-title">{{ __('awsconnect.prep_title') }}</div>
                    <div class="q-card-sub">{{ __('awsconnect.prep_sub') }}</div>
                </div>
            </div>
        </div>

        <ol class="steps">
            <li>{{ __('awsconnect.prep_1') }}</li>
            <li>{{ __('awsconnect.prep_2') }}</li>
            <li>{{ __('awsconnect.prep_3') }}</li>
        </ol>

        <div class="code-box">
            <button type="button" class="copy" data-copy-target="srcPolicy"><i class="bi bi-clipboard"></i> {{ __('awsaccounts.secret_copy') }}</button>
            <pre id="srcPolicy">{{ json_encode($sourcePolicy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <div class="note" style="margin:14px 0 0;">
            <i class="bi bi-info-circle-fill"></i><span>{{ __('awsconnect.prep_note') }}</span>
        </div>
    </div>
</div>

{{-- ===================== Daftar undangan ===================== --}}
@php
    $statusOf = fn ($i) => $i->isUsed() ? 'used' : ($i->isExpired() ? 'expired' : 'pending');
    $counts = $invites->groupBy($statusOf)->map->count();
@endphp
<div class="q-card">
    <div class="q-card-head inv-head">
        <div class="d-flex align-items-center gap-2">
            <div class="q-icon-box"><i class="bi bi-list-check"></i></div>
            <div>
                <div class="q-card-title">{{ __('awsconnect.list_title') }}</div>
                <div class="q-card-sub">{{ __('awsconnect.list_sub') }}</div>
            </div>
        </div>

        @if($invites->isNotEmpty())
            {{-- Saring per status di browser — daftarnya pendek, tidak perlu ke server. --}}
            <div class="inv-tabs" role="tablist">
                @foreach(['all' => $invites->count(), 'pending' => $counts['pending'] ?? 0, 'used' => $counts['used'] ?? 0, 'expired' => $counts['expired'] ?? 0] as $tab => $n)
                    <button type="button" class="inv-tab {{ $tab === 'all' ? 'on' : '' }}" data-filter="{{ $tab }}" role="tab">
                        {{ __('awsconnect.tab_' . $tab) }} <span class="n">{{ $n }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if($invites->isEmpty())
        <div class="q-empty">
            <i class="bi bi-envelope"></i>
            <div>{{ __('awsconnect.list_empty') }}</div>
        </div>
    @else
        <div class="inv-list">
            @foreach($invites as $invite)
                @php
                    $status   = $statusOf($invite);
                    $company  = $invite->company;
                    $dashUrl  = $invite->usageShare?->isActive() ? $invite->usageShare->publicUrl() : null;
                    $account  = $invite->awsAccount;
                    $resCount = collect($account?->resources ?? [])->except('denied')->flatten(1)->count();
                    // Sisa masa berlaku sebagai bilah: penuh saat baru dibuat, habis saat kedaluwarsa.
                    $life     = max(1, $invite->created_at->diffInSeconds($invite->expires_at));
                    $left     = max(0, min(100, now()->diffInSeconds($invite->expires_at, false) / $life * 100));
                    $initials = \Illuminate\Support\Str::of($invite->label)->explode(' ')->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                @endphp
                <div class="inv-row st-{{ $status }} {{ $invite->id === $freshId ? 'fresh' : '' }}" data-status="{{ $status }}"
                     style="animation-delay: {{ min($loop->index, 10) * 35 }}ms;">

                    {{-- Klien --}}
                    <div class="inv-who">
                        <span class="inv-av">
                            @if($company?->logo_path)
                                <img src="{{ asset($company->logo_path) }}" alt="">
                            @else
                                {{ mb_strtoupper($initials) }}
                            @endif
                        </span>
                        <span style="min-width:0;">
                            <span class="inv-nm">{{ $invite->label }}</span>
                            <span class="inv-meta">
                                @if($company && $company->name !== $invite->label)
                                    <a href="{{ route('admin.companies.show', $company) }}"><i class="bi bi-buildings"></i> {{ $company->name }}</a> ·
                                @elseif($company)
                                    <a href="{{ route('admin.companies.show', $company) }}"><i class="bi bi-buildings"></i> {{ __('awsconnect.company') }}</a> ·
                                @endif
                                {{ $invite->creator?->name
                                    ? __('awsconnect.made_by', ['name' => $invite->creator->name, 'time' => $invite->created_at->diffForHumans()])
                                    : __('awsconnect.made_at', ['time' => $invite->created_at->diffForHumans()]) }}
                            </span>
                        </span>
                    </div>

                    {{-- Status --}}
                    <div class="inv-state">
                        @if($status === 'used')
                            <span class="pill-badge ok"><i class="bi bi-check-circle-fill"></i> {{ __('awsconnect.st_used') }}</span>
                            <span class="inv-sub">
                                {{ $invite->used_at->wib()->translatedFormat('d M Y, H:i') }}
                                @if($invite->keys_found !== null) · {{ __('awsconnect.keys_n', ['n' => $invite->keys_found]) }} @endif
                                @if($resCount) · {{ __('awsconnect.resources_n', ['n' => $resCount]) }} @endif
                            </span>
                        @elseif($status === 'expired')
                            <span class="pill-badge warn"><i class="bi bi-clock-history"></i> {{ __('awsconnect.st_expired') }}</span>
                            <span class="inv-sub">{{ __('awsconnect.ended_at', ['date' => $invite->expires_at->wib()->translatedFormat('d M Y, H:i')]) }}</span>
                        @else
                            <span class="pill-badge wait"><span class="dot"></span> {{ __('awsconnect.st_pending') }}</span>
                            <span class="inv-sub" title="{{ $invite->expires_at->wib()->translatedFormat('d M Y, H:i') }} WIB">
                                {{ __('awsconnect.ends_in', ['time' => $invite->expires_at->diffForHumans(['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE])]) }}
                            </span>
                            <span class="inv-life"><span style="width: {{ round($left, 1) }}%;"></span></span>
                        @endif
                    </div>

                    {{-- Kepercayaan: akun sumber & External ID --}}
                    <div class="inv-trust">
                        <span class="inv-src">
                            <i class="bi bi-cloud-fill"></i> {{ $invite->sourceAccount?->name ?? '—' }}
                            <span class="acct">{{ $invite->trusted_account_id }}</span>
                        </span>
                        <button type="button" class="ext-chip" data-copy-text="{{ $invite->external_id }}" title="{{ __('awsconnect.copy') }} External ID">
                            <span class="k">External ID</span>
                            <code>{{ $invite->external_id }}</code>
                            <i class="bi bi-copy"></i>
                        </button>
                    </div>

                    {{-- Aksi --}}
                    <div class="inv-act">
                        @if($status === 'used')
                            @if($dashUrl)
                                <a href="{{ $dashUrl }}" target="_blank" rel="noopener" class="act-btn primary" data-no-loader>
                                    <i class="bi bi-bar-chart-line-fill"></i> {{ __('awsconnect.open_dashboard') }}
                                </a>
                                <button type="button" class="act-btn icon" data-copy-text="{{ $dashUrl }}" title="{{ __('awsconnect.copy_dashboard') }}">
                                    <i class="bi bi-link-45deg"></i>
                                </button>
                            @endif
                            @if($account)
                                <a href="{{ route('admin.aws-accounts.edit', $account) }}" class="act-btn icon" title="{{ __('awsconnect.open_account') }}">
                                    <i class="bi bi-cloud"></i>
                                </a>
                            @endif
                        @elseif($status === 'pending')
                            <button type="button" class="act-btn" data-copy-text="{{ $invite->url() }}">
                                <i class="bi bi-link-45deg"></i> {{ __('awsconnect.copy_link') }}
                            </button>
                            <a href="{{ $invite->url() }}" target="_blank" rel="noopener" class="act-btn icon" title="{{ __('awsconnect.open_invite') }}" data-no-loader>
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        @endif

                        @unless($status === 'used')
                            <button type="button" class="act-btn icon danger" title="{{ __('awsconnect.revoke') }}"
                                    data-revoke data-action="{{ route('admin.aws-invites.destroy', $invite) }}"
                                    data-label="{{ $invite->label }}"
                                    data-company="{{ $company?->name ?? __('awsconnect.company_none') }}"
                                    data-external="{{ $invite->external_id }}"
                                    data-expired="{{ $status === 'expired' ? 1 : 0 }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endunless
                    </div>
                </div>
            @endforeach

            <div class="inv-none" hidden>{{ __('awsconnect.tab_empty') }}</div>
        </div>
    @endif
</div>
{{-- Konfirmasi batalkan undangan --}}
<div class="gm-modal" id="revokeModal" role="dialog" aria-modal="true" aria-labelledby="revokeTitle">
    <div class="gm-modal-card">
        <div class="gm-modal-head">
            <div class="gm-modal-icon"><i class="bi bi-link-45deg"></i><i class="bi bi-slash-lg slash"></i></div>
            <div class="gm-modal-title" id="revokeTitle">{{ __('awsconnect.revoke_title') }}</div>
            <div class="gm-modal-sub">{{ __('awsconnect.revoke_sub') }}</div>
        </div>
        <div class="gm-modal-body">
            <div class="who">
                <span class="who-ic"><i class="bi bi-envelope-fill"></i></span>
                <span style="flex:1;min-width:0;">
                    <span class="who-nm" data-r-label>—</span>
                    <span class="who-sb"><span data-r-company>—</span> · <code data-r-external>—</code></span>
                </span>
            </div>
            <div class="note" data-r-note-pending>
                <i class="bi bi-exclamation-triangle-fill"></i><span>{{ __('awsconnect.revoke_note') }}</span>
            </div>
            <div class="note" data-r-note-expired hidden>
                <i class="bi bi-info-circle-fill"></i><span>{{ __('awsconnect.revoke_note_expired') }}</span>
            </div>

            <form method="POST" id="revokeForm">
                @csrf
                @method('DELETE')
                <div class="btn-row">
                    <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn-solid danger">
                        <i class="bi bi-x-circle"></i> {{ __('awsconnect.revoke_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        function copied(text) {
            window.gmCopy(text).then(
                () => window.gmToast?.(@json(__('awsconnect.copied')), 'ok'),
                () => window.gmToast?.(@json(__('ui.copy_failed')), 'bad'),
            );
        }

        document.querySelectorAll('[data-copy-text]').forEach((btn) => {
            btn.addEventListener('click', () => copied(btn.dataset.copyText));
        });

        document.querySelectorAll('[data-copy-target]').forEach((btn) => {
            btn.addEventListener('click', () => copied(document.getElementById(btn.dataset.copyTarget).textContent));
        });
    })();

    // ---------- Saring daftar per status ----------
    (function () {
        const tabs = document.querySelectorAll('.inv-tab');
        const rows = [...document.querySelectorAll('.inv-row')];
        const none = document.querySelector('.inv-none');
        if (!tabs.length) return;

        tabs.forEach((tab) => tab.addEventListener('click', () => {
            tabs.forEach((t) => t.classList.toggle('on', t === tab));
            const want = tab.dataset.filter;
            let shown = 0;
            rows.forEach((row) => {
                row.hidden = want !== 'all' && row.dataset.status !== want;
                if (!row.hidden) shown++;
            });
            if (none) none.hidden = shown > 0;
        }));
    })();

    // ---------- Modal batalkan undangan ----------
    (function () {
        const modal = document.getElementById('revokeModal');
        const form  = document.getElementById('revokeForm');
        if (!modal || !form) return;

        const close = () => modal.classList.remove('open');

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-revoke]');
            if (btn) {
                form.action = btn.dataset.action;
                modal.querySelector('[data-r-label]').textContent = btn.dataset.label;
                modal.querySelector('[data-r-company]').textContent = btn.dataset.company;
                modal.querySelector('[data-r-external]').textContent = btn.dataset.external;
                // Undangan kedaluwarsa memang sudah mati — pesannya cukup "dibersihkan".
                const expired = btn.dataset.expired === '1';
                modal.querySelector('[data-r-note-pending]').hidden = expired;
                modal.querySelector('[data-r-note-expired]').hidden = !expired;
                modal.classList.add('open');
                modal.querySelector('[data-close]').focus();
                return;
            }

            // Tutup: tombol batal, atau klik latar gelap di luar kartu.
            if (e.target.closest('[data-close]') || e.target === modal) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('open')) close();
        });
    })();
</script>
@endpush
