@extends('layouts.admin-v2')

@section('title', $company->name)

@push('styles')
    .co-grid2 {
        display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
        gap: 16px; align-items: start;
        margin-bottom: 16px;
    }
    @media (max-width: 1050px) { .co-grid2 { grid-template-columns: 1fr; } }
    .co-col { display: flex; flex-direction: column; gap: 16px; min-width: 0; }

    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    /* Kepala perusahaan */
    .co-head { display: flex; align-items: center; gap: 15px; }
    .co-logo {
        width: 56px; height: 56px; border-radius: 17px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface); color: var(--muted); font-size: 1.15rem; overflow: hidden;
    }
    .co-logo img { width: 100%; height: 100%; object-fit: contain; }
    .co-slug { font-size: 0.75rem; color: var(--muted); font-family: ui-monospace, monospace; }

    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.plain { background: var(--surface); color: var(--muted); }
    .pill-badge.warn  { background: var(--warn-soft); color: var(--warn-fg); }

    /* Daftar key */
    .key-row { display: flex; align-items: center; gap: 11px; padding: 11px 0; border-bottom: 1px solid var(--line); }
    .key-row:last-of-type { border-bottom: none; }
    .key-ic {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface); color: var(--muted); font-size: 0.8rem;
    }
    .key-row.primary .key-ic { background: var(--green-soft); color: var(--green-text); }
    .key-nm { font-weight: 600; font-size: 0.82rem; font-family: ui-monospace, monospace; word-break: break-all; }
    .key-lb { font-size: 0.69rem; color: var(--muted); margin-top: 1px; }
    .key-act { display: flex; gap: 6px; flex-shrink: 0; }

    /* Daftar link */
    .sh-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
        gap: 12px;
        align-items: start;
    }
    @media (max-width: 560px) { .sh-grid { grid-template-columns: 1fr; } }

    .sh {
        border: 1.5px solid var(--line); border-radius: 18px;
        padding: 14px;
        animation: shIn 0.3s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
    }
    .sh.off { opacity: 0.62; }
    @keyframes shIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }
    .sh-top { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
    .sh-nm { font-weight: 700; font-size: 0.86rem; }
    .sh-meta { font-size: 0.69rem; color: var(--muted); margin-top: 2px; }

    .sh-keys { display: flex; flex-wrap: wrap; gap: 5px; margin: 9px 0 11px; }
    .sh-key {
        font-size: 0.67rem; font-weight: 600; font-family: ui-monospace, monospace;
        background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 4px 10px;
    }
    .sh-key.all { font-family: inherit; background: var(--green-soft); color: var(--green-text); }

    .link-box {
        display: flex; align-items: center; gap: 8px;
        background: var(--surface); border-radius: 12px; padding: 8px 10px; margin-bottom: 10px;
    }
    .link-box input {
        flex: 1; min-width: 0; border: none; background: none; outline: none;
        font-family: ui-monospace, monospace; font-size: 0.72rem; color: var(--ink);
    }
    .sh-act { display: flex; flex-wrap: wrap; gap: 6px; }

    /* ---------- Riwayat akses ---------- */
    .sh-visits { margin: 4px 0 10px; }
    .sh-visit-toggle {
        display: inline-flex; align-items: center; gap: 7px;
        border: none; background: none; padding: 4px 0;
        font-size: 0.72rem; font-weight: 600; color: var(--muted); cursor: pointer;
        transition: color 0.15s;
    }
    .sh-visit-toggle:hover { color: var(--ink); }
    .sh-visit-toggle .caret { font-size: 0.62rem; transition: transform 0.2s; }
    .sh-visit-toggle.open .caret { transform: rotate(180deg); }

    .visit-list {
        margin-top: 8px; border-radius: 14px;
        background: var(--surface); padding: 6px 10px;
        animation: visitIn 0.24s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    .visit-list[hidden] { display: none; }
    @keyframes visitIn {
        from { opacity: 0; transform: translateY(-5px); }
        to   { opacity: 1; transform: none; }
    }

    .visit { display: flex; align-items: center; gap: 9px; padding: 8px 0; border-bottom: 1px solid var(--line); }
    .visit:last-of-type { border-bottom: none; }
    .visit-ic {
        width: 26px; height: 26px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--card); color: var(--muted); font-size: 0.68rem;
    }
    .visit-top { display: flex; align-items: center; gap: 6px; }
    .visit-top .ip { font-family: ui-monospace, monospace; font-size: 0.74rem; font-weight: 600; }
    .visit-top .hits {
        font-size: 0.62rem; font-weight: 700; color: var(--green-text);
        background: var(--green-soft); border-radius: 999px; padding: 1px 7px;
    }
    .visit-sub { display: block; font-size: 0.66rem; color: var(--muted); margin-top: 1px; }
    .visit-time { font-size: 0.66rem; color: var(--faint); white-space: nowrap; flex-shrink: 0; }

    .visit-note {
        display: flex; gap: 7px; align-items: flex-start;
        font-size: 0.65rem; color: var(--muted); line-height: 1.5;
        padding: 9px 0 4px; border-top: 1px solid var(--line);
    }

    /* Formulir link baru */
    .form-field { margin-bottom: 14px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 7px; }
    .form-input {
        width: 100%; height: 44px;
        border-radius: 14px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 14px; font-size: 0.85rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* Pemilih tanggal ikut selebar kolom lain di kartu ini. */
    .form-field .dp { max-width: none; }

    .scope-row { display: flex; gap: 8px; margin-bottom: 12px; }
    .scope {
        flex: 1; position: relative; text-align: center; cursor: pointer;
        border: 1.5px solid var(--line); border-radius: 14px; padding: 11px 10px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        transition: all 0.16s;
    }
    .scope input { position: absolute; opacity: 0; pointer-events: none; }
    .scope:has(input:checked) { border-color: var(--green); background: var(--green-soft); color: var(--green-text); }

    .pick-list { display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px; }
    .pick-list[hidden] { display: none; }
    .pick {
        display: flex; align-items: center; gap: 9px;
        padding: 8px 10px; border-radius: 12px; background: var(--surface);
        font-size: 0.78rem; cursor: pointer;
    }
    .pick input { accent-color: var(--green); width: 16px; height: 16px; }
    .pick .nm { font-family: ui-monospace, monospace; font-weight: 600; }
    .pick .lb { font-size: 0.68rem; color: var(--muted); }
@endpush

@section('content')
<div class="q-page-head">
    <div>
        <a href="{{ route('admin.companies.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('companies.back') }}
        </a>
        <h1 class="q-title">{{ $company->name }}</h1>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ url('/' . $company->slug) }}" target="_blank" class="q-pill" data-no-loader>
            <i class="bi bi-map"></i> {{ __('companies.open_map') }}
        </a>
        @can('companies.update')
            <a href="{{ route('admin.companies.edit', $company) }}" class="q-pill q-pill-green">
                <i class="bi bi-pencil"></i> {{ __('companies.edit') }}
            </a>
        @endcan
    </div>
</div>

<div class="q-card" style="margin-bottom:16px;">
    <div class="co-head">
        <div class="co-logo">
            @if($company->logo_path)
                <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}">
            @else
                <i class="bi bi-buildings"></i>
            @endif
        </div>
        <div style="flex:1;min-width:0;">
            <div class="co-slug">/{{ $company->slug }}</div>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="pill-badge {{ $company->is_active ? 'ok' : 'warn' }}">
                    {{ $company->is_active ? __('companies.active') : __('companies.inactive') }}
                </span>
                <span class="pill-badge plain">
                    <i class="bi bi-key-fill"></i>
                    {{ __('companies.keys_count', ['count' => $company->apiKeys->count()]) }}
                </span>
                @if($company->awsAccount)
                    <span class="pill-badge plain"><i class="bi bi-cloud-fill"></i> {{ $company->awsAccount->name }}</span>
                @endif
            </div>
        </div>

        @can('companies.update')
            @if($company->apiKeys->count())
                <form method="POST" action="{{ route('admin.companies.refresh-usage', $company) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-soft" data-spin>
                        <i class="bi bi-arrow-clockwise"></i> {{ __('companies.refresh') }}
                    </button>
                </form>
            @endif
        @endcan
    </div>
</div>

<div class="co-grid2">
    {{-- ===================== API key ===================== --}}
    <div class="co-col">
        <div class="q-card">
            <div class="q-card-head">
                <div class="d-flex align-items-center gap-2">
                    <div class="q-icon-box"><i class="bi bi-key-fill"></i></div>
                    <div>
                        <div class="q-card-title">{{ __('companies.keys_title') }}</div>
                        <div class="q-card-sub">{{ __('companies.keys_sub') }}</div>
                    </div>
                </div>
            </div>

            @forelse($company->apiKeys as $key)
                <div class="key-row {{ $key->is_primary ? 'primary' : '' }}">
                    <span class="key-ic"><i class="bi bi-key-fill"></i></span>
                    <span style="flex:1;min-width:0;">
                        <span class="key-nm">{{ $key->key_name }}</span>
                        <span class="key-lb">
                            {{ $key->label ?: ($key->awsAccount?->name ?? '.env') }}
                            @if($key->is_primary) · {{ __('companies.primary') }} @endif
                        </span>
                    </span>
                    @can('companies.update')
                        <span class="key-act">
                            @unless($key->is_primary)
                                <form method="POST" action="{{ route('admin.companies.keys.primary', [$company, $key]) }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="q-ghost-btn" title="{{ __('companies.set_primary') }}">
                                        <i class="bi bi-star"></i>
                                    </button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('admin.companies.keys.detach', [$company, $key]) }}" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="q-ghost-btn danger" title="{{ __('companies.detach') }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        </span>
                    @endcan
                </div>
            @empty
                <div class="q-empty" style="padding:18px 10px;">
                    <i class="bi bi-key"></i>{{ __('companies.keys_empty') }}
                </div>
            @endforelse

            @can('companies.update')
                @if($availableKeys)
                    <form method="POST" action="{{ route('admin.companies.keys.attach', $company) }}" style="margin-top:14px;">
                        @csrf
                        <div class="form-field" style="margin-bottom:10px;">
                            {{-- Satu dropdown untuk semua akun: nilainya "akunId|namaKey". --}}
                            <select name="key_ref" class="form-input" required>
                                @foreach($availableKeys as $group)
                                    <optgroup label="{{ $group['name'] }}">
                                        @foreach($group['keys'] as $name)
                                            <option value="{{ $group['id'] }}|{{ $name }}">{{ $name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="form-hint">{{ __('companies.keys_multi') }}</div>
                        </div>
                        <div class="form-field" style="margin-bottom:10px;">
                            <input type="text" name="label" maxlength="100" class="form-input"
                                   placeholder="{{ __('companies.label_ph') }}">
                        </div>
                        <button type="submit" class="btn-soft" style="width:100%;">
                            <i class="bi bi-plus-lg"></i> {{ __('companies.attach') }}
                        </button>
                    </form>
                @else
                    <div class="form-hint" style="margin-top:12px;">{{ __('companies.attach_none') }}</div>
                @endif
            @endcan
        </div>
    </div>

    {{-- ===================== Link laporan baru ===================== --}}
    <div class="co-col">

        @can('companies.update')
            @if($company->apiKeys->count())
                <div class="q-card">
                    <div class="q-card-head">
                        <div class="d-flex align-items-center gap-2">
                            <div class="q-icon-box"><i class="bi bi-plus-lg"></i></div>
                            <div>
                                <div class="q-card-title">{{ __('companies.share_new') }}</div>
                                <div class="q-card-sub">{{ __('companies.share_new_sub') }}</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.companies.shares.store', $company) }}" id="shareForm">
                        @csrf

                        <div class="form-field">
                            <label class="form-label-sm">{{ __('companies.share_label') }}</label>
                            <input type="text" name="label" maxlength="100" class="form-input"
                                   placeholder="{{ __('companies.share_label_ph') }}" value="{{ old('label') }}">
                        </div>

                        <label class="form-label-sm">{{ __('companies.scope') }}</label>
                        <div class="scope-row">
                            <label class="scope">
                                <input type="radio" name="scope" value="all" data-scope @checked(old('scope', 'all') === 'all')>
                                <i class="bi bi-collection"></i> {{ __('companies.scope_all') }}
                            </label>
                            <label class="scope">
                                <input type="radio" name="scope" value="pick" data-scope @checked(old('scope') === 'pick')>
                                <i class="bi bi-check2-square"></i> {{ __('companies.scope_pick') }}
                            </label>
                        </div>

                        <div class="pick-list" id="pickList" @unless(old('scope') === 'pick') hidden @endunless>
                            @foreach($company->apiKeys as $key)
                                <label class="pick">
                                    <input type="checkbox" name="key_ids[]" value="{{ $key->id }}"
                                           @checked(in_array((string) $key->id, (array) old('key_ids', []), true))>
                                    <span style="flex:1;min-width:0;">
                                        <span class="nm">{{ $key->key_name }}</span>
                                        @if($key->label)<span class="lb"> · {{ $key->label }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="form-field">
                            <label class="form-label-sm">{{ __('companies.share_expires') }}</label>
                            @include('admin.partials.date-picker', [
                                'name'  => 'expires_at',
                                'value' => old('expires_at'),
                                'min'   => now()->addDay(),
                                'time'  => false,
                            ])
                            <div class="form-hint">{{ __('companies.share_expires_hint') }}</div>
                        </div>

                        <button type="submit" class="btn-solid" style="width:100%;">
                            <i class="bi bi-link-45deg"></i> {{ __('companies.enable') }}
                        </button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>

{{-- Daftar link dibuat selebar halaman: satu perusahaan bisa punya banyak. --}}
<div class="q-card">
    <div class="q-card-head">
        <div class="d-flex align-items-center gap-2">
            <div class="q-icon-box"><i class="bi bi-link-45deg"></i></div>
            <div>
                <div class="q-card-title">
                    {{ __('companies.share_title') }}
                    <span class="pill-badge plain" style="margin-left:4px;">{{ $company->usageShares->count() }}</span>
                </div>
                <div class="q-card-sub">{{ __('companies.share_sub') }}</div>
            </div>
        </div>

        {{-- Dengan banyak link, riwayat per kartu jadi tidak praktis — daftar
             gabungannya ada di halaman sendiri. --}}
        @if($company->usageShares->count())
            <a href="{{ route('admin.companies.access-log', $company) }}" class="btn-soft" style="padding:8px 15px;font-size:0.75rem;">
                <i class="bi bi-clock-history"></i> {{ __('companies.visits_all') }}
            </a>
        @endif
    </div>

            <div class="sh-grid">
            @forelse($company->usageShares as $share)
                @php $on = $share->isActive(); @endphp
                <div class="sh {{ $on ? '' : 'off' }}" style="animation-delay: {{ $loop->index * 40 }}ms;">
                    <div class="sh-top">
                        <div style="flex:1;min-width:0;">
                            <div class="sh-nm">{{ $share->label ?: __('companies.share_untitled') }}</div>
                            <div class="sh-meta">
                                {{ $share->share_last_accessed_at
                                    ? __('companies.last_seen', ['time' => $share->share_last_accessed_at->diffForHumans()])
                                    : __('companies.never_seen') }}
                                @if($share->share_expires_at)
                                    · {{ __('apikeys.share_expires', ['date' => $share->share_expires_at->wib()->translatedFormat('d M Y')]) }}
                                @endif
                            </div>
                        </div>
                        <span class="pill-badge {{ $on ? 'ok' : 'plain' }}">
                            {{ $on ? __('companies.share_active') : __('companies.share_inactive') }}
                        </span>
                    </div>

                    <div class="sh-keys">
                        @if($share->keys->isEmpty())
                            <span class="sh-key all"><i class="bi bi-collection"></i> {{ __('companies.scope_all') }}</span>
                        @else
                            @foreach($share->keys as $key)
                                <span class="sh-key">{{ $key->key_name }}</span>
                            @endforeach
                        @endif
                    </div>

                    @if($on)
                        <div class="link-box">
                            <input type="text" id="url{{ $share->id }}" value="{{ $share->publicUrl() }}" readonly>
                            <button type="button" class="q-ghost-btn" data-copy="url{{ $share->id }}" title="{{ __('companies.copy') }}">
                                <i class="bi bi-clipboard"></i>
                            </button>
                            <a href="{{ $share->publicUrl() }}" target="_blank" class="q-ghost-btn" data-no-loader title="{{ __('companies.open') }}">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    @endif

                    {{-- Link terbuka tanpa login, jadi riwayat akses inilah kendalinya. --}}
                    <div class="sh-visits">
                        <button type="button" class="sh-visit-toggle" data-visits="v{{ $share->id }}">
                            <i class="bi bi-eye"></i>
                            @if($share->visits_count)
                                {{ __('companies.visits_opens', ['count' => $share->visits_sum_hits ?? $share->visits_count]) }}
                                · {{ __('companies.visits_readers', ['count' => $share->visits_count]) }}
                            @else
                                {{ __('companies.visits_none') }}
                            @endif
                            @if($share->visits_count)<i class="bi bi-chevron-down caret"></i>@endif
                        </button>

                        @if($share->visits_count)
                            <div class="visit-list" id="v{{ $share->id }}" hidden>
                                @foreach($share->visits as $visit)
                                    <div class="visit">
                                        <span class="visit-ic"><i class="bi bi-person"></i></span>
                                        <span style="flex:1;min-width:0;">
                                            <span class="visit-top">
                                                <span class="ip">{{ $visit->ip_address ?: '—' }}</span>
                                                @if($visit->hits > 1)
                                                    <span class="hits">{{ __('companies.visits_hits', ['count' => $visit->hits]) }}</span>
                                                @endif
                                            </span>
                                            <span class="visit-sub">
                                                {{ $visit->device() }}
                                                @if($visit->viewed_key) · {{ $visit->viewed_key }} @endif
                                                @if($visit->viewed_range) · {{ $visit->viewed_range }} @endif
                                            </span>
                                        </span>
                                        <span class="visit-time">{{ $visit->last_seen_at?->diffForHumans() }}</span>
                                    </div>
                                @endforeach

                                <div class="visit-note"><i class="bi bi-shield-lock"></i> {{ __('companies.visits_note') }}</div>
                            </div>
                        @endif
                    </div>

                    @can('companies.update')
                        <div class="sh-act">
                            <form method="POST" action="{{ route('admin.companies.shares.toggle', [$company, $share]) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-soft" style="padding:8px 14px;font-size:0.75rem;">
                                    <i class="bi bi-{{ $on ? 'slash-circle' : 'check-circle' }}"></i>
                                    {{ $on ? __('companies.disable') : __('companies.enable_again') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.companies.shares.regenerate', [$company, $share]) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-soft" style="padding:8px 14px;font-size:0.75rem;">
                                    <i class="bi bi-arrow-repeat"></i> {{ __('companies.regenerate') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.companies.shares.destroy', [$company, $share]) }}" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-soft" style="padding:8px 14px;font-size:0.75rem;">
                                    <i class="bi bi-trash"></i> {{ __('companies.delete') }}
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            @empty
                <div class="q-empty" style="padding:18px 10px;grid-column:1/-1;">
                    <i class="bi bi-link-45deg"></i>{{ __('companies.share_none') }}
                </div>
            @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Daftar centang key hanya relevan kalau cakupannya "pilih key tertentu".
    (function () {
        const list = document.getElementById('pickList');
        if (!list) return;

        document.querySelectorAll('[data-scope]').forEach((radio) => {
            radio.addEventListener('change', () => {
                list.hidden = document.querySelector('[data-scope]:checked')?.value !== 'pick';
            });
        });
    })();

    // Riwayat akses dibuka per link.
    (function () {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-visits]');
            if (!btn) return;

            const list = document.getElementById(btn.dataset.visits);
            if (!list) return;

            list.hidden = !list.hidden;
            btn.classList.toggle('open', !list.hidden);
        });
    })();

    // Salin link laporan.
    (function () {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-copy]');
            if (!btn) return;

            const input = document.getElementById(btn.dataset.copy);
            window.gmCopy(input.value).then(() => {
                window.gmToast?.(@json(__('companies.copied')), 'ok');
                btn.querySelector('i').className = 'bi bi-check-lg';
                setTimeout(() => { btn.querySelector('i').className = 'bi bi-clipboard'; }, 1600);
            }, () => window.gmToast?.(@json(__('ui.copy_failed')), 'bad'));
        });
    })();
</script>
@endpush
