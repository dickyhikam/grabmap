{{--
    Satu kartu perusahaan di board. Dirender juga sendirian oleh PipelineController
    (balasan fetch) — jadi semua yang dibutuhkan dihitung di sini, bukan di index.
    Butuh: $company (dari cardQuery), $requests (request 7 hari).
--}}
@php
    $today    = now()->wib()->toDateString();
    $due      = $company->next_action_due?->toDateString();
    // Tanggal dibandingkan sebagai teks Y-m-d — tenggat tidak punya jam, dan
    // membandingkan objek beda zona (UTC vs WIB) bisa meleset sehari.
    $dueState = !$due ? '' : ($due < $today ? 'over' : ($due === $today ? 'today' : 'later'));
    $since    = ($company->stage_changed_at ?? $company->created_at)?->wib()->toDateString() ?? $today;
    $days     = (int) \Carbon\Carbon::parse($since)->diffInDays(\Carbon\Carbon::parse($today));

    $keys    = (int) $company->api_keys_count;
    $shares  = (int) $company->active_shares_count;
    $notes   = (int) $company->notes_count;
    $short   = fn ($n) => $n >= 1000000
        ? rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'jt'
        : ($n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k' : (string) $n);
    $initials = $company->owner
        ? collect(explode(' ', $company->owner->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')
        : null;
@endphp
<article class="pl-card {{ $company->is_active ? '' : 'is-off' }} {{ $dueState === 'over' ? 'is-over' : '' }}"
         tabindex="0" role="button" aria-haspopup="dialog"
         data-card="{{ $company->getRouteKey() }}"
         data-name="{{ mb_strtolower($company->name) }}"
         data-owner="{{ $company->owner_id }}"
         data-due="{{ $dueState }}">
    <div class="pl-card-top">
        <span class="pl-logo">
            @if($company->logo_path)
                <img src="{{ asset($company->logo_path) }}" alt="">
            @else
                {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
            @endif
        </span>
        <span class="pl-card-name">{{ $company->name }}</span>
    </div>

    @if($company->next_action)
        <div class="pl-next {{ $dueState }}">
            <i class="bi bi-arrow-return-right"></i>
            <span class="tx">{{ $company->next_action }}</span>
            @if($due)
                <span class="pl-due">
                    @if($dueState === 'over')
                        {{ __('pipeline.due_over', ['date' => $company->next_action_due->translatedFormat('d M')]) }}
                    @elseif($dueState === 'today')
                        {{ __('pipeline.due_today') }}
                    @else
                        {{ $company->next_action_due->translatedFormat('d M') }}
                    @endif
                </span>
            @endif
        </div>
    @endif

    @if($company->closed_from)
        <div class="pl-closed"><i class="bi bi-sign-stop"></i> {{ __('pipeline.closed_from', ['stage' => $company->closed_from]) }}</div>
    @endif

    <div class="pl-sigs">
        @if($openClar = (int) $company->open_clarifications_count)
            @php $t = trans_choice('pipeline.sig_clar', $openClar, ['count' => $openClar]); @endphp
            <span class="pl-sig ask" title="{{ $t }}" aria-label="{{ $t }}"><i class="bi bi-patch-question-fill"></i>{{ $openClar }}</span>
        @endif

        @php $t = $company->is_active ? __('pipeline.sig_demo_on') : __('pipeline.sig_demo_off'); @endphp
        <span class="pl-sig {{ $company->is_active ? 'on' : '' }}" title="{{ $t }}" aria-label="{{ $t }}"><i class="bi bi-map"></i></span>

        @php $t = $keys ? trans_choice('pipeline.sig_keys', $keys, ['count' => $keys]) : __('pipeline.sig_no_keys'); @endphp
        <span class="pl-sig {{ $keys ? 'on' : '' }}" title="{{ $t }}" aria-label="{{ $t }}"><i class="bi bi-key-fill"></i>@if($keys){{ $keys }}@endif</span>

        @if($company->owned_aws_accounts_count)
            <span class="pl-sig on" title="{{ __('pipeline.sig_aws') }}" aria-label="{{ __('pipeline.sig_aws') }}"><i class="bi bi-cloud-check-fill"></i></span>
        @elseif($company->pending_invites_count)
            <span class="pl-sig wait" title="{{ __('pipeline.sig_aws_wait') }}" aria-label="{{ __('pipeline.sig_aws_wait') }}"><i class="bi bi-cloud-arrow-up-fill"></i></span>
        @endif

        {{-- Pemakaian baru berarti kalau sudah ada key; tanpa key, kosong itu wajar. --}}
        @if($keys)
            @php $t = $requests ? __('pipeline.sig_usage', ['count' => number_format($requests, 0, ',', '.')]) : __('pipeline.sig_no_usage'); @endphp
            <span class="pl-sig {{ $requests ? 'on' : 'warn' }}" title="{{ $t }}" aria-label="{{ $t }}"><i class="bi bi-activity"></i>{{ $short($requests) }}</span>
        @endif

        @if($shares)
            @php $t = trans_choice('pipeline.sig_shares', $shares, ['count' => $shares]); @endphp
            <span class="pl-sig on" title="{{ $t }}" aria-label="{{ $t }}"><i class="bi bi-link-45deg"></i>{{ $shares }}</span>
        @endif

        @if($notes)
            @php $t = trans_choice('pipeline.sig_notes', $notes, ['count' => $notes]); @endphp
            <span class="pl-sig" title="{{ $t }}" aria-label="{{ $t }}"><i class="bi bi-chat-left-text"></i>{{ $notes }}</span>
        @endif
    </div>

    <div class="pl-card-foot">
        <span class="pl-age {{ $days >= 30 ? 'old' : '' }}" title="{{ trans_choice('pipeline.days_in_stage', $days, ['count' => $days]) }}">
            <i class="bi bi-clock"></i> {{ trans_choice('pipeline.days_short', $days, ['count' => $days]) }}
        </span>
        @if($initials)
            <span class="pl-avatar" title="{{ __('pipeline.owner_is', ['name' => $company->owner->name]) }}">{{ $initials }}</span>
        @else
            <span class="pl-avatar empty" title="{{ __('pipeline.no_owner') }}"><i class="bi bi-person"></i></span>
        @endif
    </div>
</article>
