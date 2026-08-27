@extends('layouts.admin-v2')

@php
    $isEdit = isset($company);
@endphp

@section('title', $isEdit ? __('companies.form_edit') : __('companies.form_add'))

@push('styles')
    .co-single { max-width: 720px; }

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
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-input.is-invalid { border-color: var(--danger-fg); background: var(--danger-soft); }
    .form-input.mono { font-family: ui-monospace, monospace; }
    .form-error { display: flex; gap: 6px; font-size: 0.72rem; color: var(--danger-fg); margin-top: 6px; }
    .form-hint { font-size: 0.68rem; color: var(--muted); margin-top: 6px; }

    /* ---------- Dropzone logo ---------- */
    .drop {
        display: flex; align-items: center; gap: 13px;
        background: var(--surface); border: 1.5px dashed var(--line);
        border-radius: 18px; padding: 14px; cursor: pointer;
        transition: border-color 0.18s, background 0.18s, transform 0.18s;
    }
    .drop:hover { border-color: var(--green); }
    .drop.over {
        border-color: var(--green); background: var(--green-soft);
        transform: scale(1.01);
    }
    .drop.has { border-style: solid; }

    .drop-prev {
        width: 52px; height: 52px; border-radius: 15px; flex-shrink: 0;
        background: var(--card); color: var(--faint);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; overflow: hidden;
        transition: transform 0.28s cubic-bezier(0.34, 1.5, 0.5, 1);
    }
    .drop.has .drop-prev { color: var(--green-text); }
    .drop-prev img { width: 100%; height: 100%; object-fit: contain; animation: prevIn 0.34s cubic-bezier(0.34, 1.5, 0.5, 1); }
    .drop:hover .drop-prev { transform: scale(1.06) rotate(-2deg); }

    @keyframes prevIn {
        from { opacity: 0; transform: scale(0.7); }
        to   { opacity: 1; transform: none; }
    }

    .drop-tx { flex: 1; min-width: 0; }
    .drop-tx .t { display: block; font-size: 0.82rem; font-weight: 600; word-break: break-all; }
    .drop-tx .d { display: block; font-size: 0.68rem; color: var(--muted); margin-top: 2px; }

    .drop-act {
        width: 30px; height: 30px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--card); color: var(--muted); font-size: 0.72rem;
        transition: background 0.15s, color 0.15s;
    }
    .drop-act:hover { background: var(--danger-soft); color: var(--danger-fg); }
    .drop-act[hidden] { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .drop, .drop-prev, .drop-prev img { transition: none; animation: none; }
    }

    /* Sakelar */
    .sw-row {
        display: flex; align-items: center; gap: 13px;
        padding: 13px 15px; border-radius: 16px; position: relative;
        background: var(--surface); margin-bottom: 10px;
        cursor: pointer;
    }
    .sw-row input { position: absolute; opacity: 0; pointer-events: none; }
    .sw-row .tx { flex: 1; min-width: 0; }
    .sw-row .nm { font-weight: 600; font-size: 0.84rem; }
    .sw-row .ds { font-size: 0.7rem; color: var(--muted); margin-top: 2px; }
    .sw {
        width: 42px; height: 24px; border-radius: 999px; flex-shrink: 0; position: relative;
        background: var(--line); transition: background 0.22s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    .sw::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 18px; height: 18px; border-radius: 50%; background: #fff;
        transition: transform 0.22s cubic-bezier(0.34, 1.4, 0.5, 1);
    }
    .sw-row input:checked ~ .sw { background: var(--green); }
    .sw-row input:checked ~ .sw::after { transform: translateX(18px); }
    .sw-row input:focus-visible ~ .sw { box-shadow: 0 0 0 4px var(--green-soft); }


    /* Daftar API key */
    .key-row {
        display: flex; align-items: center; gap: 11px;
        padding: 11px 0; border-bottom: 1px solid var(--line);
    }
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

    /* Link share */
    .link-box {
        display: flex; align-items: center; gap: 8px;
        background: var(--surface); border-radius: 14px; padding: 10px 12px;
        margin-bottom: 12px;
    }
    .link-box input {
        flex: 1; min-width: 0; border: none; background: none; outline: none;
        font-family: ui-monospace, monospace; font-size: 0.74rem; color: var(--ink);
    }
    .btn-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn-row.end { justify-content: flex-end; }
@endpush

@section('content')
@php
    $action = $isEdit ? route('admin.companies.update', $company) : route('admin.companies.store');

@endphp

<div class="q-page-head">
    <div>
        <a href="{{ route('admin.companies.index') }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ __('companies.back') }}
        </a>
        <h1 class="q-title">
            @if($isEdit)
                {{ __('companies.form_edit') }} <span class="soft">{{ $company->name }}</span>
            @else
                {{ __('companies.form_add') }} <span class="soft">{{ __('companies.word') }}</span>
            @endif
        </h1>
    </div>
</div>

<div class="co-single">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-validate id="coForm">
            @csrf
            @if($isEdit) @method('PUT') @endif

            <div class="q-card" style="padding:24px;">
                <div class="q-card-head">
                    <div class="d-flex align-items-center gap-2">
                        <div class="q-icon-box"><i class="bi bi-buildings"></i></div>
                        <div>
                            <div class="q-card-title">{{ __('companies.identity') }}</div>
                            <div class="q-card-sub">{{ __('companies.identity_sub') }}</div>
                        </div>
                    </div>
                </div>

                <div class="f-row">
                    <div class="form-field">
                        <label class="form-label-sm">{{ __('companies.name') }}<span class="req">*</span></label>
                        <input type="text" name="name" id="coName" maxlength="255" required
                               class="form-input @error('name') is-invalid @enderror"
                               placeholder="{{ __('companies.name_ph') }}"
                               value="{{ old('name', $company->name ?? '') }}">
                        @error('name')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label-sm">{{ __('companies.slug') }}<span class="req">*</span></label>
                        <input type="text" name="slug" id="coSlug" maxlength="100" required
                               class="form-input mono @error('slug') is-invalid @enderror"
                               data-v-pattern="^[a-z0-9\-]+$" data-v-msg="{{ __('companies.slug_err') }}"
                               placeholder="transjakarta"
                               value="{{ old('slug', $company->slug ?? '') }}">
                        <div class="form-hint">{{ __('companies.slug_hint') }}</div>
                        @error('slug')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('companies.logo') }} <span class="opt">{{ __('companies.optional') }}</span>
                    </label>
                    {{-- Seret berkas ke area ini atau klik; pratinjaunya langsung
                         berganti tanpa menunggu simpan. --}}
                    <label class="drop {{ $isEdit && $company->logo_path ? 'has' : '' }}" id="logoDrop">
                        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg,.webp" hidden>

                        <span class="drop-prev" id="logoPrev">
                            @if($isEdit && $company->logo_path)
                                <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}">
                            @else
                                <i class="bi bi-image"></i>
                            @endif
                        </span>

                        <span class="drop-tx">
                            <span class="t" id="logoName">{{ __('companies.logo_cta') }}</span>
                            <span class="d">{{ __('companies.logo_hint') }}</span>
                        </span>

                        <span class="drop-act" id="logoClear" role="button" title="{{ __('companies.logo_clear') }}" hidden>
                            <i class="bi bi-x-lg"></i>
                        </span>
                    </label>
                    @error('logo')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label-sm">{{ __('companies.account') }}</label>
                    <select name="aws_account_id" class="form-input">
                        <option value="">—</option>
                        @foreach($awsAccounts as $account)
                            <option value="{{ $account->id }}"
                                @selected((string) old('aws_account_id', $company->aws_account_id ?? '') === (string) $account->id)>
                                {{ $account->name }} · {{ $account->region }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">{{ __('companies.account_hint') }}</div>
                </div>

                <div class="form-field">
                    <label class="form-label-sm">
                        {{ __('companies.map_key') }} <span class="opt">{{ __('companies.optional') }}</span>
                    </label>
                    <input type="text" name="aws_api_key" maxlength="1000"
                           class="form-input mono @error('aws_api_key') is-invalid @enderror"
                           placeholder="v1.public.…"
                           value="{{ old('aws_api_key', ($isEdit && $company->aws_api_key) ? '********' : '') }}">
                    <div class="form-hint">{{ __('companies.map_key_hint') }}</div>
                    @error('aws_api_key')<div class="form-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>

                <label class="sw-row">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $company->is_active ?? true))>
                    <span class="tx">
                        <span class="nm d-block">{{ __('companies.is_active') }}</span>
                        <span class="ds">{{ __('companies.is_active_d') }}</span>
                    </span>
                    <span class="sw"></span>
                </label>

                <label class="sw-row">
                    <input type="hidden" name="aws_key_active" value="0">
                    <input type="checkbox" name="aws_key_active" value="1"
                           @checked(old('aws_key_active', $company->aws_key_active ?? true))>
                    <span class="tx">
                        <span class="nm d-block">{{ __('companies.map_key_active') }}</span>
                    </span>
                    <span class="sw"></span>
                </label>

                <div class="btn-row end" style="margin-top:18px;">
                    <a href="{{ route('admin.companies.index') }}" class="btn-soft"><i class="bi bi-x-lg"></i> {{ __('ui.cancel') }}</a>
                    <button type="submit" class="btn-solid">
                        <i class="bi bi-{{ $isEdit ? 'check-lg' : 'plus-lg' }}"></i>
                        {{ $isEdit ? __('companies.save') : __('companies.create') }}
                    </button>
                </div>
            </div>
        </form>
</div>

@include('admin.partials.form-validate')
@endsection

@push('scripts')
<script>
    // Slug mengikuti nama perusahaan sampai orangnya mengetik slug sendiri —
    // setelah itu tidak pernah ditimpa lagi.
    (function () {
        const name = document.getElementById('coName');
        const slug = document.getElementById('coSlug');
        if (!name || !slug) return;

        let locked = slug.value.trim() !== '';

        const slugify = (value) => value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 100);

        slug.addEventListener('input', () => { locked = slug.value.trim() !== ''; });

        name.addEventListener('input', () => {
            if (locked) return;
            slug.value = slugify(name.value);
            slug.dispatchEvent(new Event('input', { bubbles: true }));
            locked = false;                       // tetap ikut selama slug belum disentuh
        });
    })();

    // Dropzone logo: seret-lepas, klik, pratinjau langsung, dan tombol hapus.
    (function () {
        const drop = document.getElementById('logoDrop');
        if (!drop) return;

        const input = drop.querySelector('input[type="file"]');
        const prev  = document.getElementById('logoPrev');
        const label = document.getElementById('logoName');
        const clear = document.getElementById('logoClear');
        const idle  = label.textContent;

        function show(file) {
            if (!file) return;

            label.textContent = file.name;
            drop.classList.add('has');
            clear.hidden = false;

            if (!file.type.startsWith('image/')) return;
            const url = URL.createObjectURL(file);
            prev.innerHTML = '<img alt="">';
            prev.querySelector('img').src = url;
        }

        input.addEventListener('change', () => show(input.files[0]));

        ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (e) => {
            e.preventDefault();
            drop.classList.add('over');
        }));
        ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, (e) => {
            e.preventDefault();
            drop.classList.remove('over');
        }));

        drop.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            input.files = e.dataTransfer.files;
            show(file);
        });

        clear.addEventListener('click', (e) => {
            // Label membungkus input file, jadi kliknya jangan diteruskan.
            e.preventDefault();
            e.stopPropagation();

            input.value = '';
            prev.innerHTML = '<i class="bi bi-image"></i>';
            label.textContent = idle;
            drop.classList.remove('has');
            clear.hidden = true;
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
