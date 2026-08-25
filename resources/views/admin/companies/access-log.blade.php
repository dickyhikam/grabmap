@extends('layouts.admin-v2')

@section('title', __('companies.visits_title'))

@push('styles')
    .back-pill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 0.78rem; font-weight: 600; color: var(--muted);
        text-decoration: none; margin-bottom: 6px;
        transition: color 0.15s, transform 0.15s;
    }
    .back-pill:hover { color: var(--ink); transform: translateX(-2px); }

    .filter-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
    .filter {
        display: inline-flex; align-items: center; gap: 7px;
        border: 1px solid var(--line); background: var(--card); color: var(--muted);
        border-radius: 999px; padding: 8px 15px;
        font-size: 0.76rem; font-weight: 600; text-decoration: none;
        transition: all 0.15s;
    }
    .filter:hover { border-color: var(--green); color: var(--ink); }
    .filter.on { background: var(--green); border-color: var(--green); color: #fff; }
    .filter .n {
        font-size: 0.66rem; font-weight: 800;
        background: var(--surface); color: var(--muted);
        border-radius: 999px; padding: 1px 7px;
    }
    .filter.on .n { background: rgba(255, 255, 255, 0.22); color: #fff; }

    .v-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.83rem; }
    .v-table th {
        font-size: 0.68rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: 0.05em;
        text-align: left; padding: 0 16px 12px; white-space: nowrap;
    }
    .v-table td { padding: 12px 16px; border-top: 1px solid var(--line); vertical-align: middle; }
    .v-table tbody tr { animation: rowIn 0.3s cubic-bezier(0.34, 1.4, 0.5, 1) backwards; }
    .v-table tbody tr:hover td { background: var(--surface); }
    .v-table tbody tr td:first-child { border-radius: 14px 0 0 14px; }
    .v-table tbody tr td:last-child { border-radius: 0 14px 14px 0; }
    @keyframes rowIn {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }

    .v-ip { font-family: ui-monospace, monospace; font-weight: 600; }
    .v-dim { color: var(--muted); font-size: 0.75rem; }
    .v-hits {
        font-size: 0.66rem; font-weight: 800; color: var(--green-text);
        background: var(--green-soft); border-radius: 999px; padding: 2px 9px;
    }
    .v-link {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 0.74rem; font-weight: 600;
        background: var(--surface); border-radius: 999px; padding: 4px 11px;
    }

    .table-wrap { overflow-x: auto; margin: 0 -6px; padding: 0 6px; }

    .pager { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-top: 14px; margin-top: 4px; border-top: 1px solid var(--line); }
    .pager-info { font-size: 0.75rem; color: var(--muted); margin-right: auto; }
    .pager-btn {
        min-width: 34px; height: 34px; padding: 0 10px;
        border-radius: 12px; border: none; background: var(--surface);
        color: var(--ink); font-size: 0.78rem; font-weight: 600;
        display: inline-flex; align-items: center; justify-content: center;
        text-decoration: none; transition: background 0.15s, color 0.15s;
    }
    .pager-btn:hover { background: var(--green-soft); color: var(--green-text); }
    .pager-btn.on { background: var(--green); color: #fff; }
    .pager-btn.off { opacity: 0.4; pointer-events: none; }

    .note {
        display: flex; gap: 9px; align-items: flex-start;
        border-radius: 14px; padding: 12px 14px; margin-bottom: 16px;
        font-size: 0.73rem; line-height: 1.55;
        background: var(--surface); color: var(--muted);
    }
@endpush

@section('content')
<div class="q-page-head">
    <div>
        <a href="{{ route('admin.companies.show', $company) }}" class="back-pill">
            <i class="bi bi-arrow-left"></i> {{ $company->name }}
        </a>
        <h1 class="q-title">{{ __('companies.visits_title') }} <span class="soft">{{ $visits->total() }}</span></h1>
    </div>
</div>

<div class="note">
    <i class="bi bi-shield-lock"></i>
    <span>{{ __('companies.visits_note') }}</span>
</div>

@if($shares->count() > 1)
    <div class="filter-row">
        <a href="{{ route('admin.companies.access-log', $company) }}" class="filter {{ $active ? '' : 'on' }}">
            {{ __('companies.visits_all_links') }}
        </a>
        @foreach($shares as $share)
            <a href="{{ route('admin.companies.access-log', [$company, 'link' => $share->id]) }}"
               class="filter {{ $active?->id === $share->id ? 'on' : '' }}">
                {{ $share->label ?: __('companies.share_untitled') }}
                <span class="n">{{ $share->visits()->count() }}</span>
            </a>
        @endforeach
    </div>
@endif

<div class="q-card">
    @if($visits->count())
        <div class="table-wrap">
            <table class="v-table">
                <thead>
                    <tr>
                        <th>{{ __('companies.visit_when') }}</th>
                        <th>{{ __('companies.visit_who') }}</th>
                        <th>{{ __('companies.visit_link') }}</th>
                        <th>{{ __('companies.visit_viewed') }}</th>
                        <th style="text-align:right;">{{ __('companies.visit_hits') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visits as $visit)
                        <tr style="animation-delay: {{ $loop->index * 20 }}ms;">
                            <td style="white-space:nowrap;">
                                {{ $visit->last_seen_at?->wib()->translatedFormat('d M Y H:i') }}
                                <span class="v-dim" style="display:block;">{{ $visit->last_seen_at?->diffForHumans() }}</span>
                            </td>
                            <td>
                                <span class="v-ip">{{ $visit->ip_address ?: '—' }}</span>
                                <span class="v-dim" style="display:block;">{{ $visit->device() }}</span>
                            </td>
                            <td>
                                <span class="v-link">
                                    <i class="bi bi-link-45deg"></i>
                                    {{ $visit->share?->label ?: __('companies.share_untitled') }}
                                </span>
                            </td>
                            <td class="v-dim">
                                {{ $visit->viewed_range ?: '—' }}
                                @if($visit->viewed_key)
                                    <span style="display:block;font-family:ui-monospace,monospace;">{{ $visit->viewed_key }}</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <span class="v-hits">{{ $visit->hits }}×</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            <div class="pager-info">
                {{ __('companies.visits_showing', [
                    'from' => $visits->firstItem(), 'to' => $visits->lastItem(), 'total' => $visits->total(),
                ]) }}
            </div>

            @if($visits->hasPages())
                <a href="{{ $visits->previousPageUrl() ?? '#' }}" class="pager-btn {{ $visits->onFirstPage() ? 'off' : '' }}">
                    <i class="bi bi-chevron-left"></i>
                </a>
                @foreach($visits->getUrlRange(max(1, $visits->currentPage() - 2), min($visits->lastPage(), $visits->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" class="pager-btn {{ $page === $visits->currentPage() ? 'on' : '' }}">{{ $page }}</a>
                @endforeach
                <a href="{{ $visits->nextPageUrl() ?? '#' }}" class="pager-btn {{ $visits->hasMorePages() ? '' : 'off' }}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            @endif
        </div>
    @else
        <div class="q-empty">
            <i class="bi bi-eye-slash"></i>{{ __('companies.visits_none') }}
        </div>
    @endif
</div>
@endsection
