{{--
    Isi panel samping satu perusahaan. Dirender oleh PipelineController dan
    disisipkan lewat fetch. Butuh: $company (cardQuery + stage + activities),
    $requests, $stages, $users.
--}}
@php
    $keys    = (int) $company->api_keys_count;
    $shares  = (int) $company->active_shares_count;
    $owned   = (int) $company->owned_aws_accounts_count;
    $pending = (int) $company->pending_invites_count;

    // state: ok = beres, wait = menunggu pihak lain, off = belum, none = memang tidak perlu.
    $status = [
        [
            'icon'  => 'bi-map', 'label' => __('pipeline.demo_page'),
            'state' => $company->is_active ? 'ok' : 'off',
            'text'  => $company->is_active ? __('pipeline.sig_demo_on') : __('pipeline.sig_demo_off'),
            'href'  => $company->is_active ? url('/' . $company->slug) : null,
        ],
        [
            'icon'  => 'bi-key-fill', 'label' => __('pipeline.api_keys'),
            'state' => $keys ? 'ok' : 'off',
            'text'  => $keys ? trans_choice('pipeline.sig_keys', $keys, ['count' => $keys]) : __('pipeline.sig_no_keys'),
        ],
        [
            'icon'  => 'bi-cloud-fill', 'label' => __('pipeline.aws_account'),
            'state' => $owned ? 'ok' : ($pending ? 'wait' : 'none'),
            'text'  => $owned ? __('pipeline.sig_aws') : ($pending ? __('pipeline.sig_aws_wait') : __('pipeline.sig_aws_none')),
        ],
        [
            'icon'  => 'bi-activity', 'label' => __('pipeline.usage_7d'),
            'state' => $requests ? 'ok' : ($keys ? 'warn' : 'off'),
            'text'  => $requests
                ? __('pipeline.sig_usage', ['count' => number_format($requests, 0, ',', '.')])
                : __('pipeline.sig_no_usage'),
        ],
        [
            'icon'  => 'bi-link-45deg', 'label' => __('pipeline.report_links'),
            'state' => $shares ? 'ok' : 'off',
            'text'  => $shares ? trans_choice('pipeline.sig_shares', $shares, ['count' => $shares]) : __('pipeline.sig_no_shares'),
        ],
    ];
    $stateIcon = ['ok' => 'bi-check-lg', 'wait' => 'bi-hourglass-split', 'warn' => 'bi-exclamation-lg', 'off' => 'bi-dash-lg', 'none' => 'bi-dash-lg'];

    $profileLabels = __('pipeline.profile_fields');
    $fmtDate = fn (?string $d) => $d ? \Carbon\Carbon::parse($d)->translatedFormat('d M Y') : null;
@endphp
<div class="pl-panel-inner" data-company="{{ $company->getRouteKey() }}">
    <header class="pl-panel-head">
        <span class="pl-logo lg">
            @if($company->logo_path)
                <img src="{{ asset($company->logo_path) }}" alt="">
            @else
                {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
            @endif
        </span>
        <div class="pl-panel-id">
            <h2 class="pl-panel-name" id="plPanelTitle">{{ $company->name }}</h2>
            <div class="pl-panel-sub">
                @if($company->stage)
                    <span class="pl-chip pl-tone" data-color="{{ $company->stage->color }}">{{ $company->stage->name }}</span>
                @endif
                @unless($company->is_active)
                    <span class="pl-chip off">{{ __('pipeline.inactive') }}</span>
                @endunless
            </div>
        </div>
        <button type="button" class="pl-x" data-panel-close aria-label="{{ __('pipeline.close') }}">
            <i class="bi bi-x-lg"></i>
        </button>
    </header>

    <div class="pl-panel-links">
        <a href="{{ route('admin.companies.show', $company) }}" class="btn-soft"><i class="bi bi-box-arrow-in-right"></i> {{ __('pipeline.open_detail') }}</a>
        @can('companies.update')
            <a href="{{ route('admin.companies.edit', $company) }}" class="btn-soft"><i class="bi bi-pencil"></i> {{ __('pipeline.edit_company') }}</a>
        @endcan
    </div>

    <section class="pl-sec">
        <div class="pl-sec-title">{{ __('pipeline.status') }} <span class="hint">{{ __('pipeline.status_hint') }}</span></div>
        <ul class="pl-status">
            @foreach($status as $row)
                <li class="{{ $row['state'] }}">
                    <span class="ic"><i class="bi {{ $row['icon'] }}"></i></span>
                    <span class="lb">{{ $row['label'] }}</span>
                    <span class="tx">
                        @if(!empty($row['href']))
                            <a href="{{ $row['href'] }}" target="_blank" rel="noopener" data-no-loader>{{ $row['text'] }} <i class="bi bi-box-arrow-up-right"></i></a>
                        @else
                            {{ $row['text'] }}
                        @endif
                    </span>
                    <span class="st"><i class="bi {{ $stateIcon[$row['state']] }}"></i></span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="pl-sec">
        <div class="pl-sec-title">{{ __('pipeline.progress') }}</div>
        @can('companies.update')
            <form action="{{ route('admin.pipeline.update', $company) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.saved') }}">
                @csrf
                @method('PUT')
                <div class="pl-grid">
                    <div class="form-field">
                        <label class="form-label-sm" for="plStage">{{ __('pipeline.stage') }}</label>
                        <select name="stage" id="plStage" class="form-input">
                            @foreach($stages->groupBy('category') as $category => $group)
                                <optgroup label="{{ __('pipeline.categories.' . $category) }}">
                                    @foreach($group as $s)
                                        <option value="{{ $s->getRouteKey() }}" @selected($s->id === (int) $company->pipeline_stage_id)>{{ $s->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label-sm" for="plOwner">{{ __('pipeline.owner') }}</label>
                        <select name="owner_id" id="plOwner" class="form-input">
                            <option value="">{{ __('pipeline.owner_none') }}</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected($u->id === (int) $company->owner_id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label-sm" for="plNext">{{ __('pipeline.next_action') }}</label>
                    <input type="text" name="next_action" id="plNext" class="form-input" maxlength="255"
                           value="{{ $company->next_action }}" placeholder="{{ __('pipeline.next_action_ph') }}">
                </div>
                <div class="pl-grid end">
                    <div class="form-field">
                        <label class="form-label-sm" for="plDue">{{ __('pipeline.due') }}</label>
                        <input type="date" name="next_action_due" id="plDue" class="form-input"
                               value="{{ $company->next_action_due?->toDateString() }}">
                    </div>
                    <div class="form-field">
                        <button type="submit" class="btn-solid w-100"><i class="bi bi-check2"></i> {{ __('pipeline.save') }}</button>
                    </div>
                </div>
            </form>
        @else
            <dl class="pl-readonly">
                <dt>{{ __('pipeline.owner') }}</dt><dd>{{ $company->owner?->name ?? __('pipeline.owner_none') }}</dd>
                <dt>{{ __('pipeline.next_action') }}</dt><dd>{{ $company->next_action ?: '—' }}</dd>
                <dt>{{ __('pipeline.due') }}</dt><dd>{{ $company->next_action_due?->translatedFormat('d M Y') ?? '—' }}</dd>
            </dl>
        @endcan
    </section>

    <section class="pl-sec">
        @php $openClar = $company->clarifications->where('status', '!=', 'clarified')->count(); @endphp
        <div class="pl-sec-title">
            {{ __('pipeline.clarifications') }}
            @if($openClar)<span class="hint">{{ __('pipeline.clar_open_count', ['count' => $openClar]) }}</span>@endif
        </div>

        <ul class="pl-clars">
            @forelse($company->clarifications as $clar)
                <li class="pl-clar {{ $clar->status }}">
                    <div class="pl-clar-top">
                        <span class="pl-clar-st">{{ __('pipeline.clar_status.' . $clar->status) }}</span>
                        @can('companies.update')
                            @unless($clar->isDone())
                                <form action="{{ route('admin.pipeline.clarifications.update', [$company, $clar]) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.clar_saved') }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="question" value="{{ $clar->question }}">
                                    <input type="hidden" name="answer" value="{{ $clar->answer }}">
                                    <input type="hidden" name="status" value="clarified">
                                    <button type="submit" class="pl-mini"><i class="bi bi-check2"></i> {{ __('pipeline.clar_mark_done') }}</button>
                                </form>
                            @endunless
                        @endcan
                    </div>
                    <div class="q">{{ $clar->question }}</div>
                    @if($clar->answer)
                        <div class="a"><i class="bi bi-reply-fill"></i> {{ $clar->answer }}</div>
                    @endif

                    @can('companies.update')
                        <details class="pl-clar-edit">
                            <summary>{{ __('pipeline.clar_edit') }}</summary>
                            <form action="{{ route('admin.pipeline.clarifications.update', [$company, $clar]) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.clar_saved') }}">
                                @csrf
                                @method('PUT')
                                <div class="pl-seg" role="radiogroup" aria-label="Status">
                                    @foreach(\App\Models\CompanyClarification::STATUSES as $st)
                                        <label class="{{ $st }}">
                                            <input type="radio" name="status" value="{{ $st }}" @checked($clar->status === $st)>
                                            <span>{{ __('pipeline.clar_status.' . $st) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <textarea name="question" class="form-input" rows="3" maxlength="2000" required aria-label="{{ __('pipeline.clar_question') }}">{{ $clar->question }}</textarea>
                                <textarea name="answer" class="form-input" rows="2" maxlength="2000" aria-label="{{ __('pipeline.clar_answer') }}"
                                          placeholder="{{ __('pipeline.clar_answer_ph') }}">{{ $clar->answer }}</textarea>
                                <div class="pl-clar-actions">
                                    <button type="submit" class="btn-solid"><i class="bi bi-check2"></i> {{ __('pipeline.save') }}</button>
                                </div>
                            </form>
                            <form action="{{ route('admin.pipeline.clarifications.destroy', [$company, $clar]) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.clar_removed') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pl-del-btn"><i class="bi bi-trash3"></i> {{ __('pipeline.remove') }}</button>
                            </form>
                        </details>
                    @endcan
                </li>
            @empty
                <li class="pl-act-empty">{{ __('pipeline.clar_none') }}</li>
            @endforelse
        </ul>

        @can('companies.update')
            <form action="{{ route('admin.pipeline.clarifications.store', $company) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.clar_added') }}" class="pl-note-form">
                @csrf
                <textarea name="question" class="form-input" rows="2" maxlength="2000" required
                          aria-label="{{ __('pipeline.clar_question') }}" placeholder="{{ __('pipeline.clar_question_ph') }}"></textarea>
                <button type="submit" class="btn-soft"><i class="bi bi-patch-question"></i> {{ __('pipeline.clar_add') }}</button>
            </form>
        @endcan
    </section>

    <section class="pl-sec">
        <div class="pl-sec-title">{{ __('pipeline.profile') }}</div>
        @can('companies.update')
            <form action="{{ route('admin.pipeline.profile', $company) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.saved') }}">
                @csrf
                @method('PUT')
                <div class="form-field">
                    <label class="form-label-sm" for="plNeeds">{{ __('pipeline.needs') }}</label>
                    <textarea name="needs" id="plNeeds" class="form-input" rows="4" maxlength="5000"
                              placeholder="{{ __('pipeline.needs_ph') }}">{{ $company->needs }}</textarea>
                </div>
                <div class="pl-grid">
                    <div class="form-field">
                        <label class="form-label-sm" for="plWeb">{{ __('pipeline.website') }}</label>
                        <div class="pl-inline">
                            <input type="text" name="website" id="plWeb" class="form-input" inputmode="url" maxlength="255"
                                   value="{{ $company->website }}" placeholder="https://">
                            @if($company->website)
                                <a href="{{ $company->website }}" target="_blank" rel="noopener" class="pl-open" data-no-loader aria-label="{{ __('pipeline.mom_open') }}"><i class="bi bi-box-arrow-up-right"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="form-label-sm" for="plPicExt">{{ __('pipeline.pic_external') }}</label>
                        <input type="text" name="pic_external" id="plPicExt" class="form-input" maxlength="120"
                               value="{{ $company->pic_external }}" placeholder="{{ __('pipeline.pic_external_ph') }}">
                    </div>
                </div>
                <div class="form-field">
                    <span class="form-label-sm">{{ __('pipeline.mom') }}</span>
                    <div class="pl-links" data-links>
                        @foreach($company->mom_links ?? [] as $i => $link)
                            <div class="pl-link-row">
                                <input type="text" name="mom_links[{{ $i }}][label]" class="form-input" maxlength="120"
                                       value="{{ $link['label'] ?? '' }}" placeholder="{{ __('pipeline.mom_label_ph') }}" aria-label="{{ __('pipeline.mom_label_ph') }}">
                                <input type="url" name="mom_links[{{ $i }}][url]" class="form-input" maxlength="1000"
                                       value="{{ $link['url'] ?? '' }}" placeholder="https://" aria-label="{{ __('pipeline.mom_url') }}">
                                <a href="{{ $link['url'] ?? '#' }}" target="_blank" rel="noopener" class="pl-open" data-no-loader aria-label="{{ __('pipeline.mom_open') }}"><i class="bi bi-box-arrow-up-right"></i></a>
                                <button type="button" class="pl-open" data-link-remove aria-label="{{ __('pipeline.remove') }}"><i class="bi bi-x-lg"></i></button>
                            </div>
                        @endforeach
                    </div>
                    <template data-link-tpl>
                        <div class="pl-link-row">
                            <input type="text" name="mom_links[__i__][label]" class="form-input" maxlength="120" placeholder="{{ __('pipeline.mom_label_ph') }}" aria-label="{{ __('pipeline.mom_label_ph') }}">
                            <input type="url" name="mom_links[__i__][url]" class="form-input" maxlength="1000" placeholder="https://" aria-label="{{ __('pipeline.mom_url') }}">
                            <button type="button" class="pl-open" data-link-remove aria-label="{{ __('pipeline.remove') }}"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </template>
                    <button type="button" class="pl-mini" data-link-add><i class="bi bi-plus-lg"></i> {{ __('pipeline.mom_add') }}</button>
                </div>
                <button type="submit" class="btn-solid"><i class="bi bi-check2"></i> {{ __('pipeline.save') }}</button>
            </form>
        @else
            <dl class="pl-readonly">
                <dt>{{ __('pipeline.needs') }}</dt><dd class="pre">{{ $company->needs ?: '—' }}</dd>
                <dt>{{ __('pipeline.website') }}</dt>
                <dd>@if($company->website)<a href="{{ $company->website }}" target="_blank" rel="noopener" data-no-loader>{{ $company->website }}</a>@else — @endif</dd>
                <dt>{{ __('pipeline.pic_external') }}</dt><dd>{{ $company->pic_external ?: '—' }}</dd>
                <dt>{{ __('pipeline.mom') }}</dt>
                <dd>
                    @forelse($company->mom_links ?? [] as $link)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener" data-no-loader class="d-block">{{ $link['label'] ?: $link['url'] }}</a>
                    @empty
                        —
                    @endforelse
                </dd>
            </dl>
        @endcan
    </section>

    <section class="pl-sec">
        <div class="pl-sec-title">{{ __('pipeline.history') }}</div>

        @can('companies.update')
            <form action="{{ route('admin.pipeline.note', $company) }}" method="POST" data-panel-form data-ok="{{ __('pipeline.note_added') }}" class="pl-note-form">
                @csrf
                <textarea name="body" class="form-input" rows="2" maxlength="2000" required
                          aria-label="{{ __('pipeline.note') }}" placeholder="{{ __('pipeline.note_ph') }}"></textarea>
                <button type="submit" class="btn-soft"><i class="bi bi-chat-left-text"></i> {{ __('pipeline.note_add') }}</button>
            </form>
        @endcan

        <ol class="pl-acts">
            @forelse($company->activities as $act)
                @php
                    $meta = $act->meta ?? [];
                    $line = match ($act->type) {
                        'created' => !empty($meta['stage'])
                            ? __('pipeline.act_created', ['stage' => $meta['stage']])
                            : __('pipeline.act_created_plain'),
                        'moved'   => __('pipeline.act_moved', [
                            'from' => $meta['from'] ?? __('pipeline.unknown_stage'),
                            'to'   => $meta['to'] ?? __('pipeline.unknown_stage'),
                        ]),
                        'note'    => __('pipeline.act_note'),
                        'imported' => __('pipeline.act_imported'),
                        'clarify' => match ($meta['action'] ?? null) {
                            'added'   => __('pipeline.act_clar_added', ['q' => $meta['question'] ?? '']),
                            'status'  => __('pipeline.act_clar_status', [
                                'q'      => $meta['question'] ?? '',
                                'status' => __('pipeline.clar_status.' . ($meta['status'] ?? 'open')),
                            ]),
                            default   => __('pipeline.act_clar_removed', ['q' => $meta['question'] ?? '']),
                        },
                        'updated' => collect([
                            array_key_exists('profile', $meta)
                                ? __('pipeline.act_profile', ['fields' => collect($meta['profile'])->map(fn ($f) => $profileLabels[$f] ?? $f)->implode(', ')])
                                : null,
                            array_key_exists('owner', $meta)
                                ? ($meta['owner'] ? __('pipeline.act_owner', ['name' => $meta['owner']]) : __('pipeline.act_owner_cleared'))
                                : null,
                            array_key_exists('next_action', $meta)
                                ? ($meta['next_action']
                                    ? __('pipeline.act_next', ['text' => $meta['next_action']])
                                        . (!empty($meta['due']) ? ' (' . __('pipeline.act_next_due', ['date' => $fmtDate($meta['due'])]) . ')' : '')
                                    : __('pipeline.act_next_cleared'))
                                : null,
                        ])->filter()->implode('; '),
                        default   => $act->type,
                    };
                    if (($meta['source'] ?? null) === 'sheet' && $act->type !== 'imported') {
                        $line .= ' ' . __('pipeline.via_import');
                    }
                    $icon = ['imported' => 'bi-file-earmark-spreadsheet', 'clarify' => 'bi-patch-question', 'created' => 'bi-plus-lg', 'moved' => 'bi-arrow-left-right', 'note' => 'bi-chat-left-text', 'updated' => 'bi-pencil'][$act->type] ?? 'bi-dot';
                @endphp
                <li class="pl-act {{ $act->type }}">
                    <span class="ic"><i class="bi {{ $icon }}"></i></span>
                    <div class="bd">
                        <div class="ln"><strong>{{ $act->user?->name ?? __('pipeline.system') }}</strong> {{ $line }}</div>
                        @if($act->type === 'note')
                            <div class="nt">{{ $act->body }}</div>
                        @endif
                        <time class="tm" datetime="{{ $act->created_at->toIso8601String() }}">{{ $act->created_at->wib()->translatedFormat('d M Y, H:i') }}</time>
                    </div>
                </li>
            @empty
                <li class="pl-act-empty">{{ __('pipeline.history_empty') }}</li>
            @endforelse
        </ol>
    </section>
</div>
