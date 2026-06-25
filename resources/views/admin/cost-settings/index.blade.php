@extends('layouts.admin')

@section('title', 'Pengaturan Biaya — Kurs & Pajak')

@push('styles')
    /* ===== Hero tiles ===== */
    .cs-hero { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 18px; margin-bottom: 26px; }
    @media (max-width: 991px) { .cs-hero { grid-template-columns: 1fr; } }

    .cs-tile { background:#fff; border-radius:18px; padding:22px; box-shadow:0 1px 3px rgba(0,0,0,.04), 0 6px 18px rgba(0,0,0,.05); position:relative; overflow:hidden; }
    .cs-tile .tile-head { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
    .cs-tile .tile-ic { width:42px; height:42px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .cs-tile .tile-lbl { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700; color:#8a94a0; }
    .cs-tile .tile-val { font-size:1.85rem; font-weight:800; color:#1a1a2e; line-height:1; }

    .cs-tile.featured { background:linear-gradient(135deg,#0a2e1a 0%,#0d4a28 45%,var(--grab-green-dark) 100%); color:#fff; }
    .cs-tile.featured::after { content:''; position:absolute; right:-30px; bottom:-40px; width:160px; height:160px; background:radial-gradient(circle, rgba(0,177,79,.35) 0%, transparent 70%); border-radius:50%; }
    .cs-tile.featured > *:not(.badge-active) { position:relative; z-index:1; }
    .cs-tile.featured .tile-lbl { color:rgba(255,255,255,.7); }
    .cs-tile.featured .tile-val { color:#fff; }
    .cs-tile.featured .tile-ic { background:rgba(255,255,255,.15); color:#fff; }

    .badge-active { position:absolute; top:18px; right:18px; z-index:2; background:rgba(255,255,255,.2); color:#fff; font-size:.64rem; font-weight:700; padding:5px 11px; border-radius:999px; letter-spacing:.05em; display:inline-flex; align-items:center; gap:5px; }
    .badge-active .dot { width:7px; height:7px; border-radius:50%; background:#7CFFB2; box-shadow:0 0 0 3px rgba(124,255,178,.25); }

    .kurs-meta { display:flex; flex-wrap:wrap; gap:7px; margin-top:16px; }
    .kurs-chip { background:rgba(255,255,255,.12); color:rgba(255,255,255,.92); font-size:.72rem; padding:6px 11px; border-radius:9px; display:inline-flex; align-items:center; gap:6px; }

    .inline-edit { display:flex; gap:7px; margin-top:14px; }
    .inline-edit .form-control { border-radius:10px; font-weight:700; border:1.5px solid #e8ecef; }
    .inline-edit .form-control:focus { border-color:var(--grab-green); box-shadow:0 0 0 .2rem rgba(0,177,79,.12); }
    .inline-edit .btn { border-radius:10px; padding-inline:14px; }

    /* ===== Cards ===== */
    .cs-card { background:#fff; border-radius:18px; box-shadow:0 1px 3px rgba(0,0,0,.04), 0 6px 18px rgba(0,0,0,.05); overflow:hidden; }
    .cs-card-head { padding:18px 22px; border-bottom:1px solid #f1f3f5; display:flex; align-items:center; gap:12px; }
    .cs-card-head .h-ic { width:38px; height:38px; border-radius:11px; background:var(--grab-green-light); color:var(--grab-green-dark); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .cs-card-head h6 { margin:0; font-weight:700; font-size:.95rem; }
    .cs-card-head .sub { font-size:.75rem; color:#8a94a0; }
    .cs-card-body { padding:22px; }

    .cs-label { font-size:.8rem; font-weight:600; color:#475569; margin-bottom:6px; }
    .cs-input { border-radius:11px; border:1.5px solid #e8ecef; padding:.55rem .8rem; font-size:.9rem; }
    .cs-input:focus { border-color:var(--grab-green); box-shadow:0 0 0 .2rem rgba(0,177,79,.12); }
    .input-group-text { border-radius:11px; border:1.5px solid #e8ecef; background:#f8fafc; font-weight:600; color:#64748b; }

    .src-chips { display:flex; flex-wrap:wrap; gap:7px; margin-top:9px; }
    .src-chip { font-size:.74rem; padding:6px 13px; border-radius:999px; border:1.5px solid #e8ecef; background:#fff; color:#475569; cursor:pointer; transition:.15s; }
    .src-chip:hover { border-color:var(--grab-green); color:var(--grab-green-dark); background:var(--grab-green-light); }

    /* ===== Bukti panel ===== */
    .bukti-row { display:flex; justify-content:space-between; gap:14px; padding:11px 0; border-bottom:1px dashed #eef1f4; font-size:.86rem; }
    .bukti-row:last-child { border-bottom:none; }
    .bukti-row .k { color:#94a3b8; white-space:nowrap; }
    .bukti-row .v { font-weight:600; text-align:right; color:#1a1a2e; }
    .bukti-empty { text-align:center; color:#94a3b8; padding:24px 0; }

    /* ===== History ===== */
    .hist-table { width:100%; font-size:.85rem; }
    .hist-table thead th { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; font-weight:700; padding:10px 12px; border-bottom:2px solid #f1f3f5; }
    .hist-table tbody td { padding:13px 12px; border-bottom:1px solid #f4f6f8; vertical-align:middle; }
    .hist-table tbody tr.is-active { background:var(--grab-green-light); }
    .hist-table tbody tr:last-child td { border-bottom:none; }
    .pill { font-size:.68rem; font-weight:700; padding:5px 11px; border-radius:999px; display:inline-flex; align-items:center; gap:5px; }
    .pill.on { background:var(--grab-green); color:#fff; }
    .pill.off { background:#eef1f4; color:#94a3b8; }
@endpush

@section('header')
<div class="page-header">
    <div class="container">
        <a href="{{ route('admin.dashboard') }}" class="back-link d-inline-flex align-items-center gap-1 mb-3">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
        <h4 class="fw-bold mb-1" style="font-size:1.4rem;">
            <i class="bi bi-cash-coin me-2" style="opacity:0.7;"></i>Pengaturan Biaya
        </h4>
        <p style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-bottom:0;">
            Kurs USD&rarr;IDR (dengan bukti &amp; history), tarif PPN, dan ambang peringatan budget — dipakai untuk estimasi biaya &amp; invoice semua company.
        </p>
    </div>
</div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 border-0" style="border-radius:13px;">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0" style="border-radius:13px;">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ===== HERO: ringkasan setting ===== --}}
    <div class="cs-hero">
        {{-- Kurs aktif (featured) --}}
        <div class="cs-tile featured">
            @if($activeRate)<span class="badge-active"><span class="dot"></span> AKTIF</span>@endif
            <div class="tile-head">
                <div class="tile-ic"><i class="bi bi-cash-stack"></i></div>
                <div class="tile-lbl">Kurs Aktif · USD → IDR</div>
            </div>
            @if($activeRate)
                <div class="d-flex align-items-baseline gap-2">
                    <span class="tile-val">Rp {{ number_format((float)$activeRate->rate, 0, ',', '.') }}</span>
                    <span style="opacity:.7; font-size:.9rem;">/ USD</span>
                </div>
                <div class="kurs-meta">
                    <span class="kurs-chip"><i class="bi bi-calendar3"></i> {{ $activeRate->rate_date->format('d M Y') }}</span>
                    <span class="kurs-chip"><i class="bi bi-bank"></i> {{ $activeRate->source }}</span>
                    @if($activeRate->reference)<span class="kurs-chip"><i class="bi bi-paperclip"></i> {{ \Illuminate\Support\Str::limit($activeRate->reference, 22) }}</span>@endif
                </div>
            @else
                <div class="tile-val" style="font-size:1.2rem; opacity:.9;">Belum diatur</div>
                <p style="opacity:.7; font-size:.8rem; margin:10px 0 0;">Tambah kurs pertama di form di bawah.</p>
            @endif
        </div>

        {{-- PPN --}}
        <div class="cs-tile">
            <div class="tile-head">
                <div class="tile-ic" style="background:#eef2ff; color:#6366f1;"><i class="bi bi-percent"></i></div>
                <div class="tile-lbl">Tarif PPN</div>
            </div>
            <div class="tile-val">{{ rtrim(rtrim(number_format($taxPercent, 2), '0'), '.') }}<span style="font-size:1rem; color:#94a3b8; font-weight:600;">%</span></div>
            <form method="POST" action="{{ route('admin.cost-settings.tax') }}" class="inline-edit">
                @csrf
                <input type="number" name="tax_percent" value="{{ $taxPercent }}" min="0" max="100" step="0.01" class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-grab btn-sm" title="Simpan PPN"><i class="bi bi-check-lg"></i></button>
            </form>
            <small class="text-muted d-block mt-2" style="font-size:.72rem;">Standar Indonesia 11%.</small>
        </div>

        {{-- Budget --}}
        <div class="cs-tile">
            <div class="tile-head">
                <div class="tile-ic" style="background:#fff3e0; color:#f59e0b;"><i class="bi bi-bell"></i></div>
                <div class="tile-lbl">Ambang Budget</div>
            </div>
            <div class="tile-val"><span style="font-size:1.1rem; color:#94a3b8; font-weight:600;">$</span>{{ rtrim(rtrim(number_format($budgetAlert, 2), '0'), '.') }}</div>
            <form method="POST" action="{{ route('admin.cost-settings.budget') }}" class="inline-edit">
                @csrf
                <input type="number" name="budget_alert_usd" value="{{ $budgetAlert }}" min="0" step="1" class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-grab btn-sm" title="Simpan budget"><i class="bi bi-check-lg"></i></button>
            </form>
            <small class="text-muted d-block mt-2" style="font-size:.72rem;">Samakan dengan AWS Budgets. Dashboard memperingatkan saat mendekati.</small>
        </div>
    </div>

    {{-- ===== Area kerja: form kurs + bukti ===== --}}
    <div class="row g-4 mb-4">
        {{-- Tambah / Update Kurs --}}
        <div class="col-lg-7">
            <div class="cs-card h-100">
                <div class="cs-card-head">
                    <div class="h-ic"><i class="bi bi-plus-lg"></i></div>
                    <div>
                        <h6>Tambah / Update Kurs</h6>
                        <div class="sub">Kurs baru otomatis jadi aktif &amp; tersimpan sebagai bukti</div>
                    </div>
                </div>
                <div class="cs-card-body">
                    <form method="POST" action="{{ route('admin.cost-settings.rate.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="cs-label">Kurs (Rp / USD) <span class="text-danger">*</span></div>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="rate" value="{{ old('rate') }}" min="1" step="1" class="form-control cs-input" placeholder="16500" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="cs-label">Kurs per tanggal <span class="text-danger">*</span></div>
                                <input type="date" name="rate_date" value="{{ old('rate_date', now()->toDateString()) }}" class="form-control cs-input" required>
                            </div>
                            <div class="col-12">
                                <div class="cs-label">Sumber kurs <span class="text-danger">*</span></div>
                                <input type="text" id="srcInput" name="source" value="{{ old('source', 'Kurs Pajak (KMK)') }}" class="form-control cs-input" required>
                                <div class="src-chips">
                                    <span class="src-chip" onclick="document.getElementById('srcInput').value='Kurs Pajak (KMK)'">Kurs Pajak (KMK)</span>
                                    <span class="src-chip" onclick="document.getElementById('srcInput').value='Bank Indonesia (JISDOR)'">Bank Indonesia (JISDOR)</span>
                                    <span class="src-chip" onclick="document.getElementById('srcInput').value='Kurs Tengah BI'">Kurs Tengah BI</span>
                                    <span class="src-chip" onclick="document.getElementById('srcInput').value='Manual'">Manual</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="cs-label">No. Referensi / Link bukti</div>
                                <input type="text" name="reference" value="{{ old('reference') }}" class="form-control cs-input" placeholder="No. KMK / URL sumber kurs">
                            </div>
                            <div class="col-12">
                                <div class="cs-label">Catatan</div>
                                <textarea name="note" rows="2" class="form-control cs-input" placeholder="Keterangan tambahan (opsional)">{{ old('note') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="keep_current_active" value="1" class="form-check-input" id="keepActive" {{ old('keep_current_active') ? 'checked' : '' }}>
                                    <label class="form-check-label text-muted" for="keepActive" style="font-size:0.83rem;">Simpan saja, jangan jadikan aktif (kurs aktif tetap yang lama)</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-grab mt-3 px-4"><i class="bi bi-save me-1"></i>Simpan Kurs</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bukti kurs aktif --}}
        <div class="col-lg-5">
            <div class="cs-card h-100">
                <div class="cs-card-head">
                    <div class="h-ic"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <h6>Bukti Kurs Aktif</h6>
                        <div class="sub">Dipakai untuk semua estimasi &amp; invoice client</div>
                    </div>
                </div>
                <div class="cs-card-body">
                    @if($activeRate)
                        <div class="bukti-row"><span class="k">Kurs</span><span class="v">Rp {{ number_format((float)$activeRate->rate, 0, ',', '.') }} / USD</span></div>
                        <div class="bukti-row"><span class="k">Kurs per tanggal</span><span class="v">{{ $activeRate->rate_date->format('d M Y') }}</span></div>
                        <div class="bukti-row"><span class="k">Sumber</span><span class="v">{{ $activeRate->source }}</span></div>
                        <div class="bukti-row"><span class="k">Bukti / Ref</span><span class="v">{{ $activeRate->reference ?: '—' }}</span></div>
                        @if($activeRate->note)<div class="bukti-row"><span class="k">Catatan</span><span class="v">{{ $activeRate->note }}</span></div>@endif
                        <div class="bukti-row"><span class="k">Diinput</span><span class="v">{{ $activeRate->created_by }}<br><small class="text-muted fw-normal">{{ $activeRate->created_at?->format('d M Y H:i') }}</small></span></div>
                    @else
                        <div class="bukti-empty">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:1.8rem;"></i>
                            Belum ada kurs aktif.<br>Tambah dulu di form sebelah.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== History ===== --}}
    <div class="cs-card">
        <div class="cs-card-head">
            <div class="h-ic"><i class="bi bi-clock-history"></i></div>
            <div>
                <h6>History Kurs</h6>
                <div class="sub">Jejak semua kurs sebagai bukti penagihan</div>
            </div>
        </div>
        <div class="cs-card-body pt-2">
            <div class="table-responsive">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Kurs per tgl</th>
                            <th class="text-end">Kurs (Rp/USD)</th>
                            <th>Sumber</th>
                            <th>Bukti / Ref</th>
                            <th>Diinput</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $r)
                            <tr class="{{ $r->is_active ? 'is-active' : '' }}">
                                <td class="fw-semibold">{{ $r->rate_date->format('d M Y') }}</td>
                                <td class="text-end fw-bold">{{ number_format((float)$r->rate, 0, ',', '.') }}</td>
                                <td>{{ $r->source }}</td>
                                <td class="text-muted">{{ $r->reference ?: '—' }}@if($r->note)<br><small>{{ $r->note }}</small>@endif</td>
                                <td class="text-muted"><small>{{ $r->created_by }}<br>{{ $r->created_at?->format('d M Y H:i') }}</small></td>
                                <td class="text-center">
                                    @if($r->is_active)
                                        <span class="pill on"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                                    @else
                                        <span class="pill off">Arsip</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @unless($r->is_active)
                                        <form method="POST" action="{{ route('admin.cost-settings.rate.activate', $r) }}" onsubmit="return confirm('Jadikan kurs ini aktif?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-grab">Jadikan aktif</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada history kurs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rates->hasPages())<div class="mt-3">{{ $rates->links() }}</div>@endif
        </div>
    </div>
@endsection
