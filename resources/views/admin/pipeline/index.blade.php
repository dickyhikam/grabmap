@extends('layouts.admin-v2')

@section('title', __('pipeline.title'))

@php
    $total     = $byStage->sum(fn ($cards) => $cards->count());
    $canUpdate = (bool) auth()->user()?->can('companies.update');
    $canCreate = (bool) auth()->user()?->can('companies.create');
    $categoryTones = \App\Models\PipelineStage::CATEGORIES;

    $jsText = [
        'failed'      => __('pipeline.failed'),
        'move_failed' => __('pipeline.move_failed'),
        'add_stage'   => __('pipeline.add_stage'),
        'edit_stage'  => __('pipeline.edit_stage'),
        'loading'     => __('ui.loading'),
        'collapse'    => __('pipeline.collapse'),
        'expand'      => __('pipeline.expand'),
    ];
    $jsUrls = [
        'move'  => route('admin.pipeline.move'),
        'order' => route('admin.pipeline.stages.order'),
        'stage' => route('admin.pipeline.stages.store'),
        'show'  => url('/admin/pipeline/companies') . '/',
    ];
@endphp

@push('styles')
    /* Warna kolom: pasangan latar/teks per nama warna. Lima memakai token tema yang
       sudah ada; biru & toska belum punya token, jadi didefinisikan di sini lengkap
       dengan varian gelapnya. */
    :root { --pl-sky-bg: #e6f4fd; --pl-sky-fg: #0369a1; --pl-teal-bg: #e3f6f3; --pl-teal-fg: #0f766e; --pl-col: rgba(255, 255, 255, 0.55); }
    :root[data-theme="dark"] { --pl-sky-bg: rgba(14, 165, 233, 0.16); --pl-sky-fg: #7dd3fc; --pl-teal-bg: rgba(20, 184, 166, 0.16); --pl-teal-fg: #5eead4; --pl-col: rgba(255, 255, 255, 0.03); }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]):not([data-theme="dark"]) { --pl-sky-bg: rgba(14, 165, 233, 0.16); --pl-sky-fg: #7dd3fc; --pl-teal-bg: rgba(20, 184, 166, 0.16); --pl-teal-fg: #5eead4; --pl-col: rgba(255, 255, 255, 0.03); }
    }
    .pl-tone[data-color="slate"]  { --st-bg: var(--surface);        --st-fg: var(--muted); }
    .pl-tone[data-color="indigo"] { --st-bg: var(--tone-indigo-bg); --st-fg: var(--tone-indigo-fg); }
    .pl-tone[data-color="sky"]    { --st-bg: var(--pl-sky-bg);      --st-fg: var(--pl-sky-fg); }
    .pl-tone[data-color="teal"]   { --st-bg: var(--pl-teal-bg);     --st-fg: var(--pl-teal-fg); }
    .pl-tone[data-color="amber"]  { --st-bg: var(--warn-soft);      --st-fg: var(--warn-fg); }
    .pl-tone[data-color="green"]  { --st-bg: var(--green-soft);     --st-fg: var(--green-text); }
    .pl-tone[data-color="red"]    { --st-bg: var(--danger-soft);    --st-fg: var(--danger-fg); }

    /* ---------- Kepala halaman ---------- */
    .pl-head { align-items: flex-end; }
    .pl-subtitle { font-size: 0.8rem; color: var(--muted); margin-top: 6px; }
    .pl-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .pl-search {
        display: flex; align-items: center; gap: 8px;
        height: 40px; padding: 0 14px; border-radius: 999px;
        background: var(--card); box-shadow: var(--shadow-card); color: var(--muted);
        margin: 0;
    }
    .pl-search input { border: none; outline: none; background: transparent; color: var(--ink); width: 180px; font-size: 0.82rem; }
    .pl-search:focus-within { box-shadow: 0 0 0 3px var(--green-soft), var(--shadow-card); }
    .pl-filters { display: flex; gap: 4px; padding: 4px; border-radius: 999px; background: var(--card); box-shadow: var(--shadow-card); }
    .pl-filters button {
        border: none; background: transparent; color: var(--muted);
        border-radius: 999px; padding: 6px 13px; font-size: 0.76rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s, color 0.15s;
    }
    .pl-filters button:hover { color: var(--ink); }
    .pl-filters button.on { background: var(--green); color: #fff; }
    .pl-filters .n { font-size: 0.66rem; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: var(--surface); color: var(--muted); }
    .pl-filters .n:empty { display: none; }
    .pl-filters button.on .n { background: rgba(255, 255, 255, 0.22); color: #fff; }
    .pl-filters .n.alert { background: var(--danger-soft); color: var(--danger-fg); }

    /* ---------- Ringkasan & kelompok kategori ---------- */
    .pl-summary { display: flex; flex-wrap: wrap; gap: 8px; margin: -6px 0 14px; }
    .pl-sum {
        display: inline-flex; align-items: center; gap: 9px;
        border: none; border-radius: 999px; padding: 6px 7px 6px 14px;
        background: var(--st-bg); color: var(--st-fg);
        font-size: 0.78rem; font-weight: 700; transition: transform 0.12s;
    }
    .pl-sum:hover { transform: translateY(-1px); }
    .pl-sum .n { min-width: 26px; padding: 2px 8px; border-radius: 999px; background: var(--card); color: var(--ink); font-size: 0.74rem; text-align: center; }

    .pl-group {
        flex: 0 0 auto; height: 100%;
        display: flex; flex-direction: column;
        background: var(--st-bg); border-radius: 26px; padding: 0 10px 10px;
        scroll-snap-align: start;
    }
    .pl-group-head { display: flex; align-items: center; gap: 8px; padding: 11px 4px 9px 8px; min-width: 0; }
    .pl-group-name { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 0.86rem; color: var(--st-fg); white-space: nowrap; }
    .pl-group-count { font-size: 0.72rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; background: var(--card); color: var(--ink); }
    .pl-group-hint { font-size: 0.7rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1; }
    .pl-group-cols { flex: 1; min-height: 0; display: flex; align-items: flex-start; gap: 10px; }
    .pl-group-cols > .pl-col { height: auto; }

    /* Kategori yang diciutkan jadi pita tegak — tetap terlihat jumlahnya. */
    .pl-group.collapsed { width: 54px; padding: 10px 0; cursor: pointer; align-items: center; }
    .pl-group.collapsed .pl-group-cols, .pl-group.collapsed .pl-group-hint { display: none; }
    .pl-group.collapsed .pl-group-head { flex-direction: column; padding: 0; gap: 10px; }
    .pl-group.collapsed .pl-group-name { writing-mode: vertical-rl; }
    .pl-group.collapsed .pl-icon { margin-left: 0; order: -1; }

    /* ---------- Board ---------- */
    .pl-board {
        display: flex; align-items: flex-start; gap: 14px;
        overflow-x: auto; padding: 2px 2px 14px;
        height: calc(100vh - 250px); min-height: 440px;
        scroll-snap-type: x proximity;
    }
    .pl-col {
        flex: 0 0 280px; max-height: 100%;
        display: flex; flex-direction: column;
        background: var(--pl-col); border: 1px solid var(--line); border-radius: var(--r-card);
        scroll-snap-align: start;
    }
    @media (max-width: 620px) { .pl-col { flex-basis: 84vw; } .pl-search input { width: 120px; } }
    .pl-col-head {
        display: flex; align-items: center; gap: 8px;
        padding: 14px 12px 10px 14px;
    }
    .pl-col-head[title] { cursor: grab; }
    .pl-col-name {
        display: inline-flex; align-items: center; gap: 7px; min-width: 0;
        background: var(--st-bg); color: var(--st-fg);
        padding: 5px 12px; border-radius: 999px;
        font-size: 0.76rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .pl-col-name::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .pl-col-count { font-size: 0.74rem; font-weight: 700; color: var(--muted); }
    .pl-col-desc { margin: -2px 14px 10px; font-size: 0.7rem; line-height: 1.45; color: var(--muted); }
    .form-label-sm .opt { font-weight: 600; color: var(--faint); }
    .pl-icon {
        margin-left: auto; width: 30px; height: 30px; border-radius: 10px; flex-shrink: 0;
        border: none; background: transparent; color: var(--muted);
        display: inline-flex; align-items: center; justify-content: center;
    }
    .pl-icon:hover, .pl-icon:focus-visible { background: var(--card); color: var(--ink); }

    .pl-list {
        flex: 1; min-height: 60px; overflow-y: auto;
        display: flex; flex-direction: column; gap: 9px;
        padding: 2px 10px 10px;
    }
    .pl-list.is-empty::before {
        content: attr(data-empty);
        display: flex; align-items: center; justify-content: center;
        min-height: 74px; border: 1.5px dashed var(--line); border-radius: 16px;
        font-size: 0.74rem; color: var(--faint);
    }
    .pl-col.is-ghost { opacity: 0.4; }

    /* ---------- Kartu ---------- */
    .pl-card {
        background: var(--card); border-radius: 16px; box-shadow: var(--shadow-card);
        padding: 12px 13px 11px; cursor: pointer; outline: none;
        border: 1.5px solid transparent;
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.12s;
    }
    .pl-card[hidden] { display: none; }
    .pl-card:hover { box-shadow: 0 6px 22px rgba(20, 27, 24, 0.1); }
    .pl-card:focus-visible { border-color: var(--green); }
    .pl-card.is-current { border-color: var(--green); }
    .pl-card.is-over { box-shadow: inset 3px 0 0 var(--danger-fg), var(--shadow-card); }
    .pl-card.is-ghost { opacity: 0.35; }
    .pl-card.is-chosen { box-shadow: var(--shadow-pop); }
    .pl-card-top { display: flex; align-items: center; gap: 10px; }
    .pl-logo {
        width: 30px; height: 30px; border-radius: 10px; flex-shrink: 0; overflow: hidden;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--surface); color: var(--muted); font-weight: 800; font-size: 0.8rem;
    }
    .pl-logo img { width: 100%; height: 100%; object-fit: contain; }
    .pl-logo.lg { width: 48px; height: 48px; border-radius: 15px; font-size: 1.1rem; }
    .pl-card-name { font-weight: 700; font-size: 0.86rem; line-height: 1.3; min-width: 0; overflow-wrap: anywhere; }
    .pl-card.is-off .pl-card-name { color: var(--muted); }

    .pl-next {
        display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap;
        margin-top: 9px; font-size: 0.75rem; color: var(--ink); line-height: 1.4;
    }
    .pl-next > i { color: var(--muted); font-size: 0.72rem; }
    .pl-next .tx { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .pl-due { font-size: 0.66rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: var(--surface); color: var(--muted); white-space: nowrap; }
    .pl-next.over .pl-due  { background: var(--danger-soft); color: var(--danger-fg); }
    .pl-next.today .pl-due { background: var(--warn-soft); color: var(--warn-fg); }

    .pl-sigs { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; }
    .pl-sig {
        display: inline-flex; align-items: center; gap: 4px;
        height: 22px; padding: 0 7px; border-radius: 8px;
        background: var(--surface); color: var(--faint);
        font-size: 0.68rem; font-weight: 700;
    }
    .pl-sig i { font-size: 0.72rem; }
    .pl-sig.on   { background: var(--green-soft); color: var(--green-text); }
    .pl-sig.wait { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pl-sig.warn { background: var(--warn-soft); color: var(--warn-fg); }

    .pl-card-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; }
    .pl-age { font-size: 0.68rem; color: var(--muted); display: inline-flex; align-items: center; gap: 4px; }
    .pl-age.old { color: var(--warn-fg); font-weight: 600; }
    .pl-avatar {
        width: 24px; height: 24px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--green); color: #fff; font-size: 0.6rem; font-weight: 800; text-transform: uppercase;
    }
    .pl-avatar.empty { background: var(--surface); color: var(--faint); font-size: 0.72rem; }

    /* ---------- Tambah cepat ---------- */
    .pl-add { padding: 0 10px 12px; }
    .pl-add-btn {
        width: 100%; border: none; text-decoration: none; background: transparent; color: var(--muted);
        border-radius: 12px; padding: 9px 10px; text-align: left;
        font-size: 0.78rem; font-weight: 600; display: flex; align-items: center; gap: 7px;
    }
    .pl-add-btn:hover { background: var(--card); color: var(--ink); }

    .form-input {
        width: 100%; height: 42px; border-radius: 13px; border: 1px solid var(--line);
        background: var(--surface); color: var(--ink);
        padding: 0 13px; font-size: 0.84rem; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    textarea.form-input { height: auto; padding: 10px 13px; resize: vertical; line-height: 1.55; }
    .form-input:focus { border-color: var(--green); background: var(--card); box-shadow: 0 0 0 4px var(--green-soft); }
    .form-field { margin-bottom: 14px; }
    .form-label-sm { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); margin-bottom: 6px; }
    .form-hint { font-size: 0.7rem; color: var(--muted); margin-top: 6px; }

    /* ---------- Panel samping ---------- */
    .pl-scrim {
        position: fixed; inset: 0; z-index: 1230;
        background: rgba(12, 18, 15, 0.32);
        opacity: 0; visibility: hidden; transition: opacity 0.2s, visibility 0.2s;
    }
    .pl-scrim.open { opacity: 1; visibility: visible; }
    .pl-panel {
        position: fixed; top: 0; right: 0; bottom: 0; z-index: 1240;
        width: min(460px, 100vw);
        background: var(--card); box-shadow: var(--shadow-pop);
        border-radius: 26px 0 0 26px;
        transform: translateX(104%); visibility: hidden;
        transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), visibility 0.32s;
    }
    .pl-panel.open { transform: none; visibility: visible; }
    @media (max-width: 520px) { .pl-panel { border-radius: 0; } }
    .pl-panel-scroll { height: 100%; overflow-y: auto; overscroll-behavior: contain; }
    body.pl-lock { overflow: hidden; }
    .pl-panel-inner { padding: 22px 22px 30px; }
    .pl-panel-loading { display: flex; align-items: center; justify-content: center; gap: 10px; height: 100%; color: var(--muted); font-size: 0.8rem; }

    .pl-panel-head { display: flex; align-items: flex-start; gap: 13px; }
    .pl-panel-id { flex: 1; min-width: 0; }
    .pl-panel-name { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.15rem; letter-spacing: -0.02em; margin: 2px 0 6px; overflow-wrap: anywhere; }
    .pl-panel-sub { display: flex; flex-wrap: wrap; gap: 6px; }
    .pl-chip { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 0.68rem; font-weight: 700; background: var(--st-bg, var(--surface)); color: var(--st-fg, var(--muted)); }
    .pl-chip.off { background: var(--warn-soft); color: var(--warn-fg); }
    .pl-x {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        border: none; background: var(--surface); color: var(--ink);
        display: inline-flex; align-items: center; justify-content: center;
    }
    .pl-x:hover { background: var(--line); }
    .pl-panel-links { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0 4px; }

    .pl-sec { margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--line); }
    .pl-sec-title { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); margin-bottom: 12px; }
    .pl-sec-title .hint { text-transform: none; letter-spacing: 0; font-weight: 500; color: var(--faint); margin-left: 4px; }

    .pl-status { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
    .pl-status li {
        display: grid; grid-template-columns: 30px 108px 1fr 22px; align-items: center; gap: 10px;
        background: var(--surface); border-radius: 13px; padding: 8px 10px; font-size: 0.78rem;
    }
    .pl-status .ic { width: 30px; height: 30px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--card); color: var(--muted); }
    .pl-status .lb { font-weight: 600; color: var(--muted); font-size: 0.74rem; }
    .pl-status .tx { min-width: 0; }
    .pl-status .tx a { color: var(--green-text); text-decoration: none; font-weight: 600; }
    .pl-status .tx a i { font-size: 0.65rem; }
    .pl-status .st { font-size: 0.85rem; color: var(--faint); text-align: center; }
    .pl-status li.ok .ic   { background: var(--green-soft); color: var(--green-text); }
    .pl-status li.ok .st   { color: var(--green-text); }
    .pl-status li.wait .ic { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pl-status li.wait .st { color: var(--tone-indigo-fg); }
    .pl-status li.warn .ic { background: var(--warn-soft); color: var(--warn-fg); }
    .pl-status li.warn .st { color: var(--warn-fg); }
    .pl-status li.off .tx  { color: var(--muted); }
    @media (max-width: 420px) { .pl-status li { grid-template-columns: 30px 1fr 22px; } .pl-status .lb { display: none; } }

    .pl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; }
    .pl-grid.end { align-items: end; }
    @media (max-width: 420px) { .pl-grid { grid-template-columns: 1fr; } }
    .pl-readonly { display: grid; grid-template-columns: 130px 1fr; gap: 8px 12px; margin: 0; font-size: 0.8rem; }
    .pl-readonly dt { color: var(--muted); font-weight: 600; }
    .pl-readonly dd { margin: 0; }

    .pl-note-form { display: grid; gap: 8px; justify-items: end; margin-bottom: 16px; }
    .pl-acts { list-style: none; margin: 0; padding: 0; position: relative; }
    .pl-acts::before { content: ''; position: absolute; left: 14px; top: 6px; bottom: 6px; width: 1.5px; background: var(--line); }
    .pl-act { position: relative; display: flex; gap: 12px; padding: 0 0 14px; }
    .pl-act .ic {
        width: 29px; height: 29px; border-radius: 50%; flex-shrink: 0; z-index: 1;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--surface); color: var(--muted); font-size: 0.72rem; border: 3px solid var(--card);
    }
    .pl-act.moved .ic   { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pl-act.note .ic    { background: var(--warn-soft); color: var(--warn-fg); }
    .pl-act.created .ic { background: var(--green-soft); color: var(--green-text); }
    .pl-act .bd { min-width: 0; padding-top: 4px; }
    .pl-act .ln { font-size: 0.78rem; line-height: 1.45; overflow-wrap: anywhere; }
    .pl-act .nt {
        margin-top: 6px; padding: 9px 12px; border-radius: 12px;
        background: var(--surface); font-size: 0.78rem; line-height: 1.55;
        white-space: pre-wrap; overflow-wrap: anywhere;
    }
    .pl-act .tm { display: block; font-size: 0.66rem; color: var(--faint); margin-top: 3px; }
    .pl-act-empty { font-size: 0.78rem; color: var(--muted); padding-left: 40px; }

    /* ---------- Kartu: berhenti & klarifikasi ---------- */
    .pl-closed { margin-top: 8px; font-size: 0.7rem; font-weight: 700; color: var(--danger-fg); display: flex; align-items: center; gap: 5px; }
    .pl-sig.ask { background: var(--warn-soft); color: var(--warn-fg); }

    /* ---------- Panel: klarifikasi ---------- */
    .pl-clars { list-style: none; margin: 0 0 14px; padding: 0; display: grid; gap: 8px; }
    .pl-clar { background: var(--surface); border-radius: 14px; padding: 10px 12px; border-left: 3px solid var(--warn-fg); }
    .pl-clar.waiting   { border-left-color: var(--tone-indigo-fg); }
    .pl-clar.clarified { border-left-color: var(--green); }
    .pl-clar.clarified .q { color: var(--muted); }
    .pl-clar-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 5px; }
    .pl-clar-st { font-size: 0.64rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--warn-fg); }
    .pl-clar.waiting .pl-clar-st   { color: var(--tone-indigo-fg); }
    .pl-clar.clarified .pl-clar-st { color: var(--green-text); }
    .pl-clar .q, .pl-clar .a { font-size: 0.78rem; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
    .pl-clar .a { margin-top: 6px; color: var(--muted); }
    .pl-clar-edit { margin-top: 6px; }
    .pl-clar-edit summary { font-size: 0.7rem; font-weight: 700; color: var(--muted); cursor: pointer; width: max-content; }
    .pl-clar-edit form { display: grid; gap: 8px; margin-top: 8px; }
    .pl-clar-actions { display: flex; justify-content: flex-end; }
    .pl-mini {
        border: none; background: var(--card); color: var(--ink); border-radius: 999px;
        padding: 4px 11px; font-size: 0.7rem; font-weight: 700;
        display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    }
    .pl-mini:hover { background: var(--green); color: #fff; }
    .pl-seg { display: flex; gap: 4px; padding: 3px; background: var(--card); border-radius: 999px; width: max-content; }
    .pl-seg label { margin: 0; cursor: pointer; }
    .pl-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .pl-seg span { display: block; padding: 4px 11px; border-radius: 999px; font-size: 0.7rem; font-weight: 700; color: var(--muted); }
    .pl-seg input:checked + span { background: var(--warn-soft); color: var(--warn-fg); }
    .pl-seg .waiting input:checked + span   { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); }
    .pl-seg .clarified input:checked + span { background: var(--green-soft); color: var(--green-text); }
    .pl-seg input:focus-visible + span { box-shadow: 0 0 0 3px var(--green-soft); }

    /* ---------- Panel: profil ---------- */
    .pl-inline { display: flex; gap: 6px; align-items: center; }
    .pl-open {
        width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border: none; background: var(--surface); color: var(--muted); text-decoration: none; font-size: 0.78rem;
    }
    .pl-open:hover { background: var(--green); color: #fff; }
    .pl-links { display: grid; gap: 6px; margin-bottom: 8px; }
    .pl-link-row { display: grid; grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr) auto auto; gap: 6px; align-items: center; }
    .pl-link-row .form-input { height: 38px; font-size: 0.78rem; }
    .pl-readonly dd.pre { white-space: pre-wrap; }

    /* ---------- Modal kolom ---------- */
    .gm-modal {
        position: fixed; inset: 0; z-index: 1250;
        display: flex; align-items: flex-start; justify-content: center;
        padding: 10vh 16px 16px;
        background: rgba(12, 18, 15, 0.45);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s;
    }
    .gm-modal.open { opacity: 1; visibility: visible; }
    .gm-modal-card {
        width: 100%; max-width: 400px;
        background: var(--card); border-radius: 24px; box-shadow: var(--shadow-pop); overflow: hidden;
        transform: translateY(-14px) scale(0.97); opacity: 0;
        transition: transform 0.3s cubic-bezier(0.34, 1.4, 0.5, 1), opacity 0.2s ease;
    }
    .gm-modal.open .gm-modal-card { transform: none; opacity: 1; }
    .gm-modal-head { text-align: center; padding: 26px 24px 16px; }
    .gm-modal-icon {
        width: 54px; height: 54px; border-radius: 18px; margin-bottom: 12px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem;
        background: var(--green-soft); color: var(--green-text);
    }
    .gm-modal-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.05rem; }
    .gm-modal-body { padding: 0 24px 24px; }
    .pl-swatches { display: flex; flex-wrap: wrap; gap: 8px; }
    .pl-swatch { position: relative; cursor: pointer; margin: 0; }
    .pl-swatch input { position: absolute; opacity: 0; pointer-events: none; }
    .pl-swatch .dot {
        width: 34px; height: 34px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--st-bg); color: var(--st-fg); border: 2px solid transparent;
        transition: border-color 0.15s, transform 0.12s;
    }
    .pl-swatch .dot::before { content: ''; width: 10px; height: 10px; border-radius: 50%; background: currentColor; }
    .pl-swatch input:checked + .dot { border-color: var(--st-fg); transform: scale(1.06); }
    .pl-swatch input:focus-visible + .dot { box-shadow: 0 0 0 3px var(--green-soft); }
    .pl-cats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .pl-cat { position: relative; margin: 0; cursor: pointer; }
    .pl-cat input { position: absolute; opacity: 0; pointer-events: none; }
    .pl-cat .box {
        display: block; height: 100%; padding: 9px 12px; border-radius: 14px;
        background: var(--surface); border: 1.5px solid transparent; transition: border-color 0.15s, background 0.15s;
    }
    .pl-cat .nm { display: block; font-size: 0.78rem; font-weight: 700; }
    .pl-cat .hn { display: block; font-size: 0.66rem; color: var(--muted); margin-top: 2px; line-height: 1.35; }
    .pl-cat input:checked + .box { background: var(--st-bg); border-color: var(--st-fg); }
    .pl-cat input:checked + .box .nm { color: var(--st-fg); }
    .pl-cat input:focus-visible + .box { box-shadow: 0 0 0 3px var(--green-soft); }
    .pl-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
    .pl-stage-del { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); }
    .pl-del-btn {
        border: none; background: transparent; color: var(--danger-fg);
        font-size: 0.78rem; font-weight: 700; padding: 0;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .pl-del-btn[disabled] { color: var(--faint); cursor: not-allowed; }
@endpush

@section('content')
<div class="q-page-head pl-head">
    <div>
        <h1 class="q-title">{{ __('pipeline.title') }} <span class="soft" id="plTotal">{{ $total }}</span></h1>
        <div class="pl-subtitle">{{ __('pipeline.subtitle') }}</div>
    </div>
    <div class="pl-tools">
        <label class="pl-search">
            <i class="bi bi-search"></i>
            <input type="search" id="plSearch" placeholder="{{ __('pipeline.search_ph') }}" aria-label="{{ __('pipeline.search_ph') }}" autocomplete="off">
        </label>
        <div class="pl-filters" role="group" aria-label="Filter">
            <button type="button" class="on" data-filter="all" aria-pressed="true">{{ __('pipeline.filter_all') }}</button>
            <button type="button" data-filter="mine" aria-pressed="false">{{ __('pipeline.filter_mine') }} <span class="n" id="plCntMine"></span></button>
            <button type="button" data-filter="over" aria-pressed="false">{{ __('pipeline.filter_over') }} <span class="n alert" id="plCntOver"></span></button>
        </div>
        @if($canUpdate)
            <button type="button" class="q-pill" data-stage-new><i class="bi bi-layout-three-columns"></i> {{ __('pipeline.add_stage') }}</button>
        @endif
    </div>
</div>

{{-- Ringkasan per kategori — sekali lihat: berapa yang belum trial, onboarding, live, berhenti. --}}
<div class="pl-summary">
    @foreach($groups as $category => $groupStages)
        <button type="button" class="pl-sum pl-tone" data-color="{{ $categoryTones[$category] }}" data-jump="{{ $category }}"
                title="{{ __('pipeline.category_hints.' . $category) }}">
            <span class="nm">{{ __('pipeline.categories.' . $category) }}</span>
            <span class="n" data-sum="{{ $category }}">{{ $groupStages->sum(fn ($st) => $byStage->get($st->id, collect())->count()) }}</span>
        </button>
    @endforeach
</div>

<div class="pl-board" id="plBoard">
    @foreach($groups as $category => $groupStages)
        <div class="pl-group pl-tone" data-color="{{ $categoryTones[$category] }}" data-category="{{ $category }}">
            <div class="pl-group-head">
                <span class="pl-group-name">{{ __('pipeline.categories.' . $category) }}</span>
                <span class="pl-group-count" data-group-count></span>
                <span class="pl-group-hint">{{ __('pipeline.category_hints.' . $category) }}</span>
                <button type="button" class="pl-icon" data-collapse aria-expanded="true"
                        aria-label="{{ __('pipeline.collapse') }}: {{ __('pipeline.categories.' . $category) }}"
                        data-label-collapse="{{ __('pipeline.collapse') }}" data-label-expand="{{ __('pipeline.expand') }}">
                    <i class="bi bi-arrows-collapse-vertical"></i>
                </button>
            </div>
            <div class="pl-group-cols" data-group-cols>
                    @foreach($groupStages as $stage)
                @php $cards = $byStage->get($stage->id, collect()); @endphp
                <section class="pl-col pl-tone" data-color="{{ $stage->color }}" data-stage="{{ $stage->getRouteKey() }}" aria-label="{{ $stage->name }}">
                    <header class="pl-col-head" @if($canUpdate) title="{{ __('pipeline.stage_drag') }}" @endif>
                        <span class="pl-col-name">{{ $stage->name }}</span>
                        <span class="pl-col-count" data-count>{{ $cards->count() }}</span>
                        @if($canUpdate)
                            <button type="button" class="pl-icon" data-stage-edit
                                    data-url="{{ route('admin.pipeline.stages.update', $stage) }}"
                                    data-delete-url="{{ route('admin.pipeline.stages.destroy', $stage) }}"
                                    data-name="{{ $stage->name }}" data-category="{{ $stage->category }}" data-description="{{ $stage->description }}" data-color="{{ $stage->color }}"
                                    aria-label="{{ __('pipeline.edit_stage') }}: {{ $stage->name }}">
                                <i class="bi bi-three-dots"></i>
                            </button>
                        @endif
                    </header>
                    @if($stage->description)
                        <p class="pl-col-desc">{{ $stage->description }}</p>
                    @endif

                    <div class="pl-list" data-list data-empty="{{ __('pipeline.drop_here') }}">
                        @foreach($cards as $company)
                            @include('admin.pipeline.card', ['company' => $company, 'requests' => $usage[$company->id] ?? 0])
                        @endforeach
                    </div>

                    @if($canCreate)
                        {{-- Memakai formulir perusahaan yang lengkap; kolom ini jadi tahap awalnya. --}}
                        <div class="pl-add">
                            <a href="{{ route('admin.companies.create', ['stage' => $stage->getRouteKey(), 'from' => 'pipeline']) }}" class="pl-add-btn">
                                <i class="bi bi-plus-lg"></i> {{ __('pipeline.add_company') }}
                            </a>
                        </div>
                    @endif
                </section>
            @endforeach
            </div>
        </div>
    @endforeach
</div>

<div class="pl-scrim" id="plScrim"></div>
<aside class="pl-panel" id="plPanel" role="dialog" aria-modal="true" aria-labelledby="plPanelTitle" aria-hidden="true">
    <div class="pl-panel-scroll" id="plPanelBody"></div>
</aside>

@if($canUpdate)
    <div class="gm-modal" id="stageModal" role="dialog" aria-modal="true" aria-labelledby="stageModalTitle">
        <div class="gm-modal-card">
            <div class="gm-modal-head">
                <div class="gm-modal-icon"><i class="bi bi-kanban"></i></div>
                <div class="gm-modal-title" id="stageModalTitle"></div>
            </div>
            <div class="gm-modal-body">
                <form method="POST" id="stageForm" action="{{ route('admin.pipeline.stages.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" id="stageMethod" disabled>
                    <div class="form-field">
                        <label class="form-label-sm" for="stageName">{{ __('pipeline.stage_name') }}</label>
                        <input type="text" name="name" id="stageName" class="form-input" maxlength="60" required
                               placeholder="{{ __('pipeline.stage_name_ph') }}">
                    </div>
                    <div class="form-field">
                        <label class="form-label-sm" for="stageDesc">{{ __('pipeline.stage_desc') }} <span class="opt">{{ __('pipeline.optional') }}</span></label>
                        <textarea name="description" id="stageDesc" class="form-input" rows="2" maxlength="255"
                                  placeholder="{{ __('pipeline.stage_desc_ph') }}"></textarea>
                    </div>
                    <div class="form-field">
                        <span class="form-label-sm">{{ __('pipeline.stage_category') }}</span>
                        <div class="pl-cats">
                            @foreach($categoryTones as $cat => $tone)
                                <label class="pl-cat pl-tone" data-color="{{ $tone }}">
                                    <input type="radio" name="category" value="{{ $cat }}" @checked($loop->first)>
                                    <span class="box">
                                        <span class="nm">{{ __('pipeline.categories.' . $cat) }}</span>
                                        <span class="hn">{{ __('pipeline.category_hints.' . $cat) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-field">
                        <span class="form-label-sm">{{ __('pipeline.stage_color') }}</span>
                        <div class="pl-swatches">
                            @foreach(\App\Models\PipelineStage::COLORS as $color)
                                <label class="pl-swatch pl-tone" data-color="{{ $color }}" title="{{ __('pipeline.colors.' . $color) }}">
                                    <input type="radio" name="color" value="{{ $color }}" @checked($loop->first)>
                                    <span class="dot"></span>
                                    <span class="visually-hidden">{{ __('pipeline.colors.' . $color) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="pl-modal-actions">
                        <button type="button" class="btn-soft" data-close>{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn-solid">{{ __('pipeline.save') }}</button>
                    </div>
                </form>

                <form method="POST" id="stageDeleteForm" class="pl-stage-del" hidden>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="pl-del-btn" id="stageDeleteBtn"><i class="bi bi-trash3"></i> {{ __('pipeline.stage_delete') }}</button>
                    <div class="form-hint" id="stageDeleteHint" hidden>{{ __('pipeline.stage_delete_hint') }}</div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (function () {
        const CSRF       = document.querySelector('meta[name="csrf-token"]').content;
        const ME         = @json((string) auth()->id());
        const CAN_UPDATE = @json($canUpdate);
        const URLS       = @json($jsUrls);
        const T          = @json($jsText);

        const board     = document.getElementById('plBoard');
        const panel     = document.getElementById('plPanel');
        const panelBody = document.getElementById('plPanelBody');
        const scrim     = document.getElementById('plScrim');
        const toast     = (msg, type) => window.gmToast && window.gmToast(msg, type);

        // ---- Bantuan ----
        async function send(url, method = 'GET', body = null) {
            const opts = { method, headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } };
            if (body instanceof FormData) {
                opts.body = body;
            } else if (body) {
                opts.body = JSON.stringify(body);
                opts.headers['Content-Type'] = 'application/json';
            }

            const res  = await fetch(url, opts);
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(first || data.message || T.failed);
            }
            return data;
        }

        function toNode(html) {
            const tpl = document.createElement('template');
            tpl.innerHTML = html.trim();
            return tpl.content.firstElementChild;
        }

        const cardEl = (uuid) => board.querySelector('.pl-card[data-card="' + uuid + '"]');
        const colEl  = (uuid) => board.querySelector('.pl-col[data-stage="' + uuid + '"]');

        // ---- Penyaring & hitungan ----
        let filter = 'all';
        let query  = '';

        function refresh() {
            const cards = [...board.querySelectorAll('.pl-card')];
            cards.forEach((card) => {
                const okQuery  = !query || card.dataset.name.includes(query);
                const okFilter = filter === 'all'
                    || (filter === 'mine' && card.dataset.owner === ME)
                    || (filter === 'over' && card.dataset.due === 'over');
                card.hidden = !(okQuery && okFilter);
            });

            const narrowed = filter !== 'all' || query !== '';
            board.querySelectorAll('.pl-col').forEach((col) => {
                const all  = col.querySelectorAll('.pl-card').length;
                const seen = col.querySelectorAll('.pl-card:not([hidden])').length;
                col.querySelector('[data-count]').textContent = narrowed ? seen + '/' + all : all;
                col.querySelector('[data-list]').classList.toggle('is-empty', all === 0);
            });

            board.querySelectorAll('.pl-group').forEach((group) => {
                const all  = group.querySelectorAll('.pl-card').length;
                const seen = group.querySelectorAll('.pl-card:not([hidden])').length;
                group.querySelector('[data-group-count]').textContent = narrowed ? seen + '/' + all : all;
                const sum = document.querySelector('[data-sum="' + group.dataset.category + '"]');
                if (sum) sum.textContent = all;
            });

            document.getElementById('plTotal').textContent = cards.length;
            const mine = cards.filter((c) => c.dataset.owner === ME).length;
            const over = cards.filter((c) => c.dataset.due === 'over').length;
            document.getElementById('plCntMine').textContent = mine || '';
            document.getElementById('plCntOver').textContent = over || '';

            const current = board.querySelector('.pl-card.is-current');
            if (current) current.classList.remove('is-current');
            if (openUuid) cardEl(openUuid)?.classList.add('is-current');
        }

        document.getElementById('plSearch').addEventListener('input', (e) => {
            query = e.target.value.trim().toLowerCase();
            refresh();
        });

        document.querySelectorAll('[data-filter]').forEach((btn) => {
            btn.addEventListener('click', () => {
                filter = btn.dataset.filter;
                document.querySelectorAll('[data-filter]').forEach((b) => {
                    b.classList.toggle('on', b === btn);
                    b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
                });
                refresh();
            });
        });

        // Kartu dari server menggantikan yang lama; kalau tahapnya berubah,
        // kartunya ikut pindah ke dasar kolom barunya.
        function placeCard(data) {
            const node = toNode(data.card);
            const old  = cardEl(node.dataset.card);
            const col  = data.stage ? colEl(data.stage) : null;

            if (old && col && old.closest('.pl-col') !== col) {
                old.remove();
                col.querySelector('[data-list]').appendChild(node);
            } else if (old) {
                old.replaceWith(node);
            } else if (col) {
                col.querySelector('[data-list]').appendChild(node);
            }

            refresh();
            return node;
        }

        // ---- Seret & lepas ----
        if (window.Sortable && CAN_UPDATE) {
            board.querySelectorAll('[data-list]').forEach((list) => {
                Sortable.create(list, {
                    group: 'cards',
                    draggable: '.pl-card',
                    animation: 170,
                    // Di layar sentuh, tahan sebentar dulu — supaya geser biasa tetap menggulir.
                    delay: 160,
                    delayOnTouchOnly: true,
                    ghostClass: 'is-ghost',
                    chosenClass: 'is-chosen',
                    onStart: (evt) => { evt.item._plNext = evt.item.nextElementSibling; },
                    onEnd: onCardDrop,
                });
            });

            // Urutan kolom hanya bisa diubah di dalam kategorinya sendiri — pindah
            // kategori lewat modal kolom, supaya tidak terjadi tanpa sengaja.
            board.querySelectorAll('[data-group-cols]').forEach((cols) => Sortable.create(cols, {
                draggable: '.pl-col',
                handle: '.pl-col-head',
                filter: 'button',
                preventOnFilter: false,
                direction: 'horizontal',
                animation: 170,
                ghostClass: 'is-ghost',
                onStart: (evt) => { evt.item._plNext = evt.item.nextElementSibling; },
                onEnd: onColumnDrop,
            }));
        }

        async function onCardDrop(evt) {
            if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

            const card  = evt.item;
            const col   = evt.to.closest('.pl-col');
            const order = [...evt.to.querySelectorAll('.pl-card')].map((c) => c.dataset.card);
            refresh();

            try {
                placeCard(await send(URLS.move, 'POST', { company: card.dataset.card, stage: col.dataset.stage, order }));
            } catch (err) {
                // Kembalikan ke tempat semula supaya layar tidak berbohong soal isi database.
                evt.from.insertBefore(card, card._plNext && card._plNext.parentNode === evt.from ? card._plNext : null);
                refresh();
                toast(T.move_failed, 'bad');
            }
        }

        async function onColumnDrop(evt) {
            if (evt.oldIndex === evt.newIndex) return;

            const col   = evt.item;
            const order = [...board.querySelectorAll('.pl-col')].map((c) => c.dataset.stage);

            try {
                await send(URLS.order, 'POST', { order });
            } catch (err) {
                evt.from.insertBefore(col, col._plNext && col._plNext.parentNode === evt.from ? col._plNext : null);
                toast(err.message, 'bad');
            }
        }

        // ---- Panel samping ----
        let openUuid  = null;
        let lastFocus = null;

        function setUrl(uuid) {
            const url = new URL(location.href);
            uuid ? url.searchParams.set('c', uuid) : url.searchParams.delete('c');
            history.replaceState(null, '', url);
        }

        function fillPanel(html) {
            panelBody.innerHTML = html;
            window.gmEnhanceSelects && window.gmEnhanceSelects(panelBody);
        }

        async function openPanel(uuid) {
            openUuid  = uuid;
            lastFocus = cardEl(uuid);
            panelBody.innerHTML = '<div class="pl-panel-loading"><span class="spinner-border spinner-border-sm"></span> ' + T.loading + '</div>';
            panel.classList.add('open');
            scrim.classList.add('open');
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('pl-lock');
            setUrl(uuid);
            refresh();

            try {
                const data = await send(URLS.show + uuid);
                if (openUuid !== uuid) return;
                fillPanel(data.panel);
                panelBody.querySelector('[data-panel-close]')?.focus();
            } catch (err) {
                closePanel();
                toast(err.message, 'bad');
            }
        }

        function closePanel() {
            if (!openUuid) return;
            openUuid = null;
            panel.classList.remove('open');
            scrim.classList.remove('open');
            panel.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('pl-lock');
            setUrl(null);
            refresh();
            lastFocus?.focus();
        }

        panelBody.addEventListener('click', (e) => {
            if (e.target.closest('[data-panel-close]')) return closePanel();

            // Baris link MOM: tambah dari <template>, hapus barisnya langsung.
            const addLink = e.target.closest('[data-link-add]');
            if (addLink) {
                const field = addLink.parentNode;
                const html  = field.querySelector('[data-link-tpl]').innerHTML.replaceAll('__i__', 'n' + Date.now());
                const row   = toNode(html);
                field.querySelector('[data-links]').appendChild(row);
                row.querySelector('input').focus();
                return;
            }
            const removeLink = e.target.closest('[data-link-remove]');
            if (removeLink) removeLink.closest('.pl-link-row').remove();
        });
        scrim.addEventListener('click', closePanel);

        panelBody.addEventListener('submit', async (e) => {
            const form = e.target.closest('form[data-panel-form]');
            if (!form) return;
            e.preventDefault();

            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true;
            try {
                const data = await send(form.action, 'POST', new FormData(form));
                fillPanel(data.panel);
                placeCard(data);
                toast(form.dataset.ok, 'ok');
            } catch (err) {
                toast(err.message, 'bad');
                btn.disabled = false;
            }
        });

        // Klik / Enter pada kartu membuka panel.
        board.addEventListener('click', (e) => {
            const toggle = e.target.closest('[data-collapse]');
            const shut   = e.target.closest('.pl-group.collapsed');
            if (toggle || shut) {
                const group = (toggle || shut).closest('.pl-group');
                setCollapsed(group, !group.classList.contains('collapsed'));
                return;
            }

            const card = e.target.closest('.pl-card');
            if (card) openPanel(card.dataset.card);
        });

        board.addEventListener('keydown', (e) => {
            const card = e.target.closest('.pl-card');
            if (card && e.target === card && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                openPanel(card.dataset.card);
            }
        });

        // ---- Ciutkan kategori ----
        // Disimpan per browser saja — ini kenyamanan tampilan, bukan data.
        const COLLAPSE_KEY = 'pl-collapsed';
        let collapsed = [];
        try { collapsed = JSON.parse(localStorage.getItem(COLLAPSE_KEY) || '[]'); } catch (e) { collapsed = []; }

        function setCollapsed(group, on, save = true) {
            group.classList.toggle('collapsed', on);
            const btn = group.querySelector('[data-collapse]');
            btn.setAttribute('aria-expanded', on ? 'false' : 'true');
            btn.setAttribute('aria-label', (on ? btn.dataset.labelExpand : btn.dataset.labelCollapse) + ': ' + group.querySelector('.pl-group-name').textContent);
            btn.querySelector('i').className = 'bi ' + (on ? 'bi-arrows-expand-vertical' : 'bi-arrows-collapse-vertical');
            if (!save) return;
            collapsed = [...board.querySelectorAll('.pl-group.collapsed')].map((g) => g.dataset.category);
            try { localStorage.setItem(COLLAPSE_KEY, JSON.stringify(collapsed)); } catch (e) { /* mode privat */ }
        }

        board.querySelectorAll('.pl-group').forEach((g) => setCollapsed(g, collapsed.includes(g.dataset.category), false));

        document.querySelectorAll('[data-jump]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const group = board.querySelector('.pl-group[data-category="' + btn.dataset.jump + '"]');
                if (!group) return;
                if (group.classList.contains('collapsed')) setCollapsed(group, false);
                group.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
            });
        });

        // ---- Modal kolom ----
        const stageModal = document.getElementById('stageModal');

        function openStageModal(btn) {
            const form   = document.getElementById('stageForm');
            const name   = document.getElementById('stageName');
            const del    = document.getElementById('stageDeleteForm');
            const filled = btn ? btn.closest('.pl-col').querySelectorAll('.pl-card').length : 0;

            document.getElementById('stageModalTitle').textContent = btn ? T.edit_stage : T.add_stage;
            form.action = btn ? btn.dataset.url : URLS.stage;
            document.getElementById('stageMethod').disabled = !btn;
            name.value = btn ? btn.dataset.name : '';
            document.getElementById('stageDesc').value = btn ? (btn.dataset.description || '') : '';

            const color = btn ? btn.dataset.color : null;
            form.querySelectorAll('input[name="color"]').forEach((r, i) => { r.checked = color ? r.value === color : i === 0; });
            const category = btn ? btn.dataset.category : null;
            form.querySelectorAll('input[name="category"]').forEach((r, i) => { r.checked = category ? r.value === category : i === 0; });

            del.hidden = !btn;
            if (btn) {
                del.action = btn.dataset.deleteUrl;
                document.getElementById('stageDeleteBtn').disabled = filled > 0;
                document.getElementById('stageDeleteHint').hidden = filled === 0;
            }

            stageModal.classList.add('open');
            setTimeout(() => name.focus(), 60);
        }

        if (stageModal) {
            document.addEventListener('click', (e) => {
                const edit = e.target.closest('[data-stage-edit]');
                if (edit) return openStageModal(edit);
                if (e.target.closest('[data-stage-new]')) return openStageModal(null);
                if (e.target.closest('[data-close]') || e.target === stageModal) stageModal.classList.remove('open');
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            if (stageModal?.classList.contains('open')) return stageModal.classList.remove('open');
            closePanel();
        });

        refresh();

        // Tautan langsung ke satu perusahaan: ?c=<uuid> membuka panelnya.
        const deep = new URLSearchParams(location.search).get('c');
        if (deep && cardEl(deep)) openPanel(deep);

        @if($errors->any())
            toast(@json($errors->first()), 'bad');
        @endif
    })();
</script>
@endpush
