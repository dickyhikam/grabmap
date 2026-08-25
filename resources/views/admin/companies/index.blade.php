@extends('layouts.admin-v2')

@section('title', __('companies.title'))

@push('styles')
    .co-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 16px; }
    @media (max-width: 520px) { .co-grid { grid-template-columns: 1fr; } }

    .co-card {
        display: flex; flex-direction: column;
        animation: coIn 0.34s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
        transition: box-shadow 0.18s ease, transform 0.18s ease;
    }
    .co-card:hover { transform: translateY(-2px); box-shadow: 0 6px 26px rgba(20, 27, 24, 0.09); }
    .co-card.off { opacity: 0.72; }
    @keyframes coIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: none; }
    }

    .co-head { display: flex; align-items: flex-start; gap: 13px; }
    .co-logo {
        width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: var(--surface); color: var(--muted); font-size: 1rem;
        overflow: hidden;
    }
    .co-logo img { width: 100%; height: 100%; object-fit: contain; }
    .co-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800; font-size: 0.98rem; letter-spacing: -0.02em;
    }
    .co-slug { font-size: 0.72rem; color: var(--muted); font-family: ui-monospace, monospace; margin-top: 2px; }

    .co-badges { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 5px; }
    .pill-badge {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.66rem; font-weight: 700;
        padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pill-badge.ok    { background: var(--green-soft); color: var(--green-text); }
    .pill-badge.plain { background: var(--surface); color: var(--muted); }
    .pill-badge.warn  { background: var(--warn-soft); color: var(--warn-fg); }

    .co-meta {
        display: flex; flex-wrap: wrap; gap: 8px;
        margin-top: 14px; padding-top: 13px; border-top: 1px solid var(--line);
    }
    .co-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--surface); border-radius: 999px;
        padding: 6px 12px; font-size: 0.72rem; color: var(--muted);
    }
    .co-chip i { font-size: 0.75rem; }
    .co-chip.on { background: var(--green-soft); color: var(--green-text); font-weight: 600; }

    .co-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto; padding-top: 14px; }
    .co-btn {
        display: inline-flex; align-items: center; gap: 6px;
        border: none; background: var(--surface); color: var(--ink);
        border-radius: 999px; padding: 8px 14px;
        font-size: 0.76rem; font-weight: 600; cursor: pointer; text-decoration: none;
        transition: background 0.15s, color 0.15s, transform 0.12s;
    }
    .co-btn:hover { background: var(--green); color: #fff; }
    .co-btn:active { transform: scale(0.96); }
@endpush

@section('content')
<div class="q-page-head">
    <h1 class="q-title">{{ __('companies.title') }} <span class="soft">{{ $companies->count() }}</span></h1>
    @can('companies.create')
        <a href="{{ route('admin.companies.create') }}" class="q-pill q-pill-green">
            <i class="bi bi-plus-lg"></i> {{ __('companies.add') }}
        </a>
    @endcan
</div>

@if($companies->isEmpty())
    <div class="q-card">
        <div class="q-empty">
            <i class="bi bi-buildings"></i>
            <div style="font-weight:700;color:var(--ink);margin-bottom:6px;">{{ __('companies.empty') }}</div>
            <div style="max-width:440px;margin:0 auto 16px;">{{ __('companies.empty_hint') }}</div>
            @can('companies.create')
                <a href="{{ route('admin.companies.create') }}" class="q-pill q-pill-green">
                    <i class="bi bi-plus-lg"></i> {{ __('companies.add') }}
                </a>
            @endcan
        </div>
    </div>
@else
    <div class="co-grid">
        @foreach($companies as $company)
            @php $shared = (int) ($company->active_shares_count ?? 0); @endphp
            <div class="q-card co-card {{ $company->is_active ? '' : 'off' }}"
                 style="animation-delay: {{ $loop->index * 40 }}ms;">
                <div class="co-head">
                    <div class="co-logo">
                        @if($company->logo_path)
                            <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}">
                        @else
                            <i class="bi bi-buildings"></i>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="co-name">{{ $company->name }}</div>
                        <div class="co-slug">/{{ $company->slug }}</div>
                    </div>
                    <div class="co-badges">
                        <span class="pill-badge {{ $company->is_active ? 'plain' : 'warn' }}">
                            {{ $company->is_active ? __('companies.active') : __('companies.inactive') }}
                        </span>
                        @if($shared)
                            <span class="pill-badge ok">
                                <i class="bi bi-link-45deg"></i> {{ __('companies.shares_count', ['count' => $shared]) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="co-meta">
                    <span class="co-chip {{ $company->api_keys_count ? 'on' : '' }}">
                        <i class="bi bi-key-fill"></i>
                        {{ $company->api_keys_count
                            ? __('companies.keys_count', ['count' => $company->api_keys_count])
                            : __('companies.no_keys') }}
                    </span>
                    <span class="co-chip">
                        <i class="bi bi-sliders"></i>
                        {{ __('companies.features', ['count' => $company->features_count]) }}
                    </span>
                    @if($company->awsAccount)
                        <span class="co-chip"><i class="bi bi-cloud-fill"></i> {{ $company->awsAccount->name }}</span>
                    @endif
                </div>

                <div class="co-actions">
                    <a href="{{ route('admin.companies.show', $company) }}" class="co-btn">
                        <i class="bi bi-box-arrow-in-right"></i> {{ __('companies.detail') }}
                    </a>
                    <a href="{{ url('/' . $company->slug) }}" target="_blank" class="co-btn" data-no-loader>
                        <i class="bi bi-map"></i> {{ __('companies.open_map') }}
                    </a>
                    @can('companies.update')
                        <a href="{{ route('admin.companies.edit', $company) }}" class="co-btn">
                            <i class="bi bi-pencil"></i> {{ __('companies.edit') }}
                        </a>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
