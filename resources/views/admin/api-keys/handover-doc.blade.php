<!DOCTYPE html>
{{--
    Dokumen serah-terima API key — halaman cetak (Save as PDF dari browser),
    mengikuti cara invoice di modul biaya. Gayanya berdiri sendiri, tidak ikut
    tema admin, supaya hasil cetaknya tidak berubah saat tema panel diubah.
--}}
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Key Handover — {{ $keyName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; color: #1a1a2e; background: #eef1f4; font-size: 13px; line-height: 1.5; }
        .toolbar { max-width: 800px; margin: 18px auto 0; display: flex; justify-content: space-between; gap: 10px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; border: 1px solid #cbd3da; background: #fff; color: #1a1a2e; text-decoration: none; font-weight: 600; font-size: 13px; cursor: pointer; }
        .btn-primary { background: #00b14f; border-color: #00b14f; color: #fff; }
        .sheet { max-width: 800px; margin: 14px auto 40px; background: #fff; padding: 40px 44px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); border-radius: 4px; }

        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #00b14f; padding-bottom: 16px; margin-bottom: 22px; }
        .brand { font-size: 20px; font-weight: 700; color: #0d4a28; }
        .brand small { display: block; font-size: 11px; font-weight: 500; color: #6c757d; margin-top: 2px; }
        .logo { max-height: 50px; max-width: 160px; object-fit: contain; }
        .head-right { text-align: right; }
        .head-right .client { font-size: 11px; font-weight: 700; color: #6c757d; letter-spacing: 0.06em; text-transform: uppercase; }
        .head-right .svc { font-size: 13px; font-weight: 700; margin-top: 6px; }
        .head-right .sub { font-size: 11px; color: #6c757d; }

        h1 { font-size: 26px; letter-spacing: -0.02em; }
        .lead { font-size: 13px; color: #6c757d; margin-top: 2px; margin-bottom: 18px; }

        .strip { display: flex; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 26px; }
        .strip > div { flex: 1; padding: 10px 14px; text-align: center; border-right: 1px solid #e2e8f0; background: #fafbfc; }
        .strip > div:last-child { border-right: none; }
        .strip .label { text-transform: uppercase; letter-spacing: 0.05em; color: #8a94a0; font-size: 9px; font-weight: 700; }
        .strip .v { font-weight: 700; font-size: 13px; margin-top: 2px; }
        .strip .v.ok { color: #00913f; }
        .strip .v.bad { color: #c62828; }

        h2 { font-size: 16px; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #00b14f; }
        section { margin-bottom: 24px; }

        table.kv { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; }
        table.kv td { padding: 8px 12px; border-bottom: 1px solid #eef1f4; font-size: 12px; vertical-align: top; }
        table.kv tr:last-child td { border-bottom: none; }
        table.kv td.k { width: 190px; font-weight: 600; background: #fafbfc; }
        table.kv td.mono { font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 11px; color: #4a5560; }

        .cred { border: 1px solid #00b14f; border-radius: 6px; overflow: hidden; }
        .cred-head { display: flex; }
        .cred-head .tag { background: #00b14f; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; padding: 8px 16px; text-transform: uppercase; }
        .cred-head .hint { flex: 1; font-size: 11px; font-style: italic; color: #6c757d; padding: 8px 16px; text-align: center; }
        .cred-val { font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 10.5px; line-height: 1.7; padding: 12px 16px; word-break: break-all; border-top: 1px solid #e2e8f0; }
        .cred-foot { background: #f1faf4; border-top: 1px solid #cfe9da; font-size: 11px; padding: 8px 16px; }
        .cred-foot b { font-weight: 700; }

        .svc-grid { display: flex; gap: 14px; flex-wrap: wrap; }
        .svc-box { flex: 1; min-width: 180px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .svc-box .t { background: #fafbfc; border-bottom: 1px solid #e2e8f0; padding: 7px 12px; font-weight: 700; font-size: 12px; }
        .svc-box ul { list-style: none; padding: 8px 12px; }
        .svc-box li { font-size: 12px; padding: 2px 0; }
        .svc-box li::before { content: '•'; color: #00b14f; font-weight: 700; margin-right: 7px; }

        .notice { border: 1px solid #e8a33d; border-radius: 6px; overflow: hidden; margin-bottom: 24px; }
        .notice .t { background: #f0a020; color: #fff; font-size: 11.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 9px 16px; }
        .notice .b { background: #fdf8ef; padding: 12px 16px; }
        .notice .b p { font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        .notice ul { list-style: none; }
        .notice li { font-size: 11.5px; padding: 2px 0 2px 14px; position: relative; }
        .notice li::before { content: '•'; color: #e8a33d; position: absolute; left: 0; font-weight: 700; }

        table.res { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; }
        table.res th { background: #0d4a28; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; padding: 8px 10px; text-align: left; }
        table.res td { padding: 8px 10px; border-bottom: 1px solid #eef1f4; font-size: 11.5px; vertical-align: top; }
        table.res tr:last-child td { border-bottom: none; }
        table.res td.svc { font-weight: 700; width: 70px; }
        table.res td.arn { font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 10px; color: #4a5560; word-break: break-all; }

        ul.notes { list-style: none; }
        ul.notes li { font-size: 12px; padding: 3px 0 3px 14px; position: relative; }
        ul.notes li::before { content: '•'; color: #00b14f; position: absolute; left: 0; font-weight: 700; }

        .foot { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 11px; color: #8a94a0; font-weight: 600; }

        @media print {
            body { background: #fff; font-size: 12px; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; max-width: 100%; padding: 0; border-radius: 0; }
            section { break-inside: avoid; }
            @page { margin: 16mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $backUrl }}" class="btn">&larr; Back to API Keys</a>
        <button onclick="window.print()" class="btn btn-primary">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                @if($company?->logo_path)
                    <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @else
                    <div class="brand">{{ config('app.name', 'GrabMaps') }}<small>API Key Delivery</small></div>
                @endif
            </div>
            <div class="head-right">
                @if($company)<div class="client">{{ $company->name }}</div>@endif
                <div class="svc">Amazon Location Service API Key</div>
                <div class="sub">{{ $environment }} | {{ $region }}</div>
            </div>
        </div>

        <h1>API Key Handover</h1>
        <div class="lead">{{ $provider }} &mdash; {{ $keyName }}</div>

        @php
            $isProd  = \Illuminate\Support\Str::lower($environment) === 'production';
            $billing = $isProd ? 'Production usage / billing' : 'Non-production usage';
            $n = 0;
        @endphp

        <div class="strip">
            <div>
                <div class="label">Status</div>
                <div class="v {{ $status === __('apikeys.active') ? 'ok' : 'bad' }}">{{ $status }}</div>
            </div>
            <div>
                <div class="label">Environment</div>
                <div class="v">{{ $environment }}</div>
            </div>
            <div>
                <div class="label">Expiration</div>
                <div class="v">{{ $expire ? $expire->wib()->format('d M Y, H:i') . ' WIB' : 'No expiry' }}</div>
            </div>
        </div>

        @if($isProd)
            <div class="notice">
                <div class="t">Production usage &amp; billing notice</div>
                <div class="b">
                    <p>This API key is intended exclusively for the client's live production environment.</p>
                    <ul>
                        <li>Do not use this key for development, testing, staging, UAT, demonstrations, or internal validation.</li>
                        <li>Requests made with this key are classified as production consumption and should be tracked separately for usage monitoring and billing allocation.</li>
                        <li>Use a separate non-production API key for all development and testing workloads so the two remain clearly separated.</li>
                    </ul>
                </div>
            </div>
        @endif

        <section>
            <h2>{{ ++$n }}. Configuration Summary</h2>
            <table class="kv">
                <tr><td class="k">API Key Name</td><td>{{ $keyName }}</td></tr>
                <tr><td class="k">Description</td><td>{{ $key['description'] ?: '—' }}</td></tr>
                <tr><td class="k">Service / Data Provider</td><td>{{ $provider }}</td></tr>
                <tr><td class="k">AWS Region</td><td>{{ $regionLabel }} - {{ $region }}</td></tr>
                <tr>
                    <td class="k">Expiration (UTC)</td>
                    <td>
                        @if($expire)
                            {{ $expire->format('d F Y, H:i') }} UTC ({{ $expire->format('Y-m-d\TH:i:s.v\Z') }})
                        @else
                            No expiry
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="k">Expiration (Local)</td>
                    <td>{{ $expire ? $expire->wib()->format('d F Y, H:i') . ' WIB (UTC+7)' : 'No expiry' }}</td>
                </tr>
                @if(!empty($key['key_arn']))
                    <tr><td class="k">API Key ARN</td><td class="mono">{{ $key['key_arn'] }}</td></tr>
                @endif
                <tr><td class="k">Usage / Billing Class</td><td>{{ $billing }}</td></tr>
            </table>
        </section>

        <section>
            <h2>{{ ++$n }}. API Key Value</h2>
            <div class="cred">
                <div class="cred-head">
                    <div class="tag">Confidential credential</div>
                    <div class="hint">Copy exactly - no spaces or line breaks</div>
                </div>
                <div class="cred-val">{{ $key['key'] }}</div>
                <div class="cred-foot">
                    <b>Secure handling:</b> Transmit this document through an approved secure channel and limit access to authorized personnel.
                </div>
            </div>
        </section>

        <section>
            <h2>{{ ++$n }}. Resources and Authorized Actions</h2>
            @if($resources)
                <table class="res">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Resource ARN</th>
                            <th style="width:200px;">Authorized Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resources as $row)
                            <tr>
                                <td class="svc">{{ $row['service'] }}</td>
                                <td class="arn">{{ $row['arn'] }}</td>
                                <td>{{ implode(', ', $row['actions']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size:12px;color:#8a94a0;">No actions are allowed on this key.</p>
            @endif
        </section>

        @if($referers)
            <section>
                <h2>{{ ++$n }}. Allowed Referers</h2>
                <table class="kv">
                    @foreach($referers as $referer)
                        <tr><td class="mono">{{ $referer }}</td></tr>
                    @endforeach
                </table>
                <p style="font-size:11px;color:#8a94a0;margin-top:8px;">
                    Requests from any other origin are rejected by AWS.
                </p>
            </section>
        @endif

        <section>
            <h2>{{ ++$n }}. Usage, Security and Billing Notes</h2>
            <ul class="notes">
                <li>Use this API key only for its designated environment and the authorized Maps, Places, and Routes operations listed above.</li>
                <li>Keep the credential confidential and limit distribution to authorized client and project personnel.</li>
                @if($isProd)
                    <li>Production traffic must use this key so production consumption remains separately identifiable for monitoring and billing allocation.</li>
                @else
                    <li>Do not use this key for live production traffic or production billing measurement.</li>
                @endif
                <li>For browser or mobile usage, apply appropriate client restrictions before deployment (for example, allowed web domains or application identifiers).</li>
                <li>Do not commit the key to public source repositories, publish it in documentation portals, or include it in unrestricted client-side builds.</li>
                <li>If exposure or misuse is suspected, contact us immediately so the key can be revoked or rotated, and update the consuming application.</li>
            </ul>
        </section>

        <div class="foot">
            <span>CONFIDENTIAL &mdash; Client Delivery</span>
            <span>Version 1.0 | Issued {{ now()->wib()->format('d F Y') }}</span>
        </div>
    </div>
</body>
</html>
