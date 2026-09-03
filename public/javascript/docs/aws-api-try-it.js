/**
 * AWS API Reference — generic "Try it Live" module
 *
 * Pakai:
 *   AWSAPI_TryIt.init({
 *     prefix: 'st',                       // prefix ID (st-req-preview, st-run, dst.)
 *     panelId: 'op-places-search-text',   // root panel id (untuk scope query preset buttons)
 *     proxy: '/api/places/search',        // Laravel proxy URL
 *     presets: { bias: {...}, circle: {...}, ... },
 *     defaultPreset: 'bias',
 *     metaFormatter: (data) => 'X ms · Y results',  // optional: custom meta line
 *     method: 'POST'                      // optional, default POST
 *   });
 *
 * HTML yang dibutuhkan (dengan {prefix} = "st"):
 *   #st-json-status   .json-status indicator
 *   #st-req-preview   <textarea> JSON editor
 *   #st-format-btn    Format button
 *   #st-run           Send Request button
 *   #st-spinner       Spinner element
 *   #st-status        Response status pill
 *   #st-meta          Response meta (timing, count)
 *   #st-resp          Response body container
 *   .preset-btn[data-preset="..."]  inside #panelId — preset trigger buttons
 */
(function (window) {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function $(id) { return document.getElementById(id); }

    function setEditor(prefix, obj) {
        $(prefix + '-req-preview').value = JSON.stringify(obj, null, 2);
        setStatus(prefix, true);
    }

    function setStatus(prefix, valid) {
        const tag = $(prefix + '-json-status');
        const ed = $(prefix + '-req-preview');
        if (!tag || !ed) return;
        if (valid) {
            tag.textContent = 'VALID';
            tag.className = 'json-status ok';
            ed.classList.remove('invalid');
        } else {
            tag.textContent = 'INVALID';
            tag.className = 'json-status invalid';
            ed.classList.add('invalid');
        }
    }

    function tryParse(prefix) {
        try { return JSON.parse($(prefix + '-req-preview').value); }
        catch (e) { return null; }
    }

    function defaultMetaFormatter(data, ms, ok) {
        const count = (data.ResultItems || data.Routes || []).length;
        return `<b>${ms}ms</b> · ${ok ? `<b>${count}</b> result${count !== 1 ? 's' : ''}` : 'error'}`;
    }

    /**
     * Get raw AWS direct URL from the panel's "try-it-url" header (no key substitution).
     * Used for direct Send Request (we append the user's key explicitly with proper encoding).
     */
    function getAwsUrlRaw(panelId) {
        try {
            const urls = document.querySelectorAll('#' + panelId + ' .try-it-url > div > span:nth-child(2)');
            const first = urls[0]?.textContent?.trim();
            return first && first.startsWith('http') ? first : null;
        } catch (_) { return null; }
    }

    /**
     * Resolve the AWS direct URL from the panel's "try-it-url" header.
     * Picks the first URL (which is the AWS endpoint), falls back to proxy URL.
     * If user has configured their own API Key + region (via Key Inspector),
     * substitutes the region in the URL and appends the user's actual key.
     */
    function getAwsUrl(panelId) {
        try {
            const urls = document.querySelectorAll('#' + panelId + ' .try-it-url > div > span:nth-child(2)');
            let first = urls[0]?.textContent?.trim();
            if (!first || !first.startsWith('http')) return null;

            // Always substitute if user key configured
            const userKey = window.AWSAPI_UserKey;
            if (userKey && userKey.apiKey) {
                if (userKey.region) {
                    first = first.replace(/(?:ap|us|eu|sa|af|me|ca|cn)-[a-z]+-\d/g, userKey.region);
                }
                first = first.replace(/key=\.\.\.|key=\*\*\*|key=\{[^}]+\}/g, 'key=' + userKey.apiKey);
            }
            return first;
        } catch (_) { return null; }
    }

    /** Generate curl command from method + URL + body */
    function buildCurl(method, url, body) {
        let cmd = `curl -X ${method} "${url}" \\\n  -H "Content-Type: application/json"`;
        if (body && method !== 'GET') {
            const bodyStr = JSON.stringify(body, null, 2).split('\n').map((l, i) => i === 0 ? l : '  ' + l).join('\n');
            cmd += ` \\\n  -d '${bodyStr}'`;
        }
        return cmd;
    }

    /** Generate JS fetch() code */
    function buildJs(method, url, body) {
        const bodyPart = body && method !== 'GET' ? `,\n  body: JSON.stringify(${JSON.stringify(body, null, 2)})` : '';
        return `const res = await fetch("${url}", {\n  method: "${method}",\n  headers: { "Content-Type": "application/json" }${bodyPart}\n});\nconst data = await res.json();\nconsole.log(data);`;
    }

    /** Generate PHP cURL code */
    function buildPhp(method, url, body) {
        const bodyPart = body && method !== 'GET' ? `\n$body = json_encode(${JSON.stringify(body, null, 2).replace(/^/gm, '    ')});\n` : '';
        const setOpt = body && method !== 'GET' ? `\ncurl_setopt($ch, CURLOPT_POSTFIELDS, $body);` : '';
        return `<?php${bodyPart}
$ch = curl_init("${url}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "${method}");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);${setOpt}
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
print_r($data);`;
    }

    /** Inject "Copy as curl" + Code snippet tabs button row into the Try-It panel */
    function injectExtraActions(prefix, panelId, proxy, httpMethod) {
        const sendRow = document.querySelector('#' + panelId + ' .send-row');
        if (!sendRow) return;

        // Avoid double-inject
        if (sendRow.querySelector('.tryit-copy-curl')) return;

        // Container for extra actions
        const wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;gap:6px;margin-left:auto;align-items:center;';

        // Copy curl button
        const curlBtn = document.createElement('button');
        curlBtn.className = 'btn-copy tryit-copy-curl';
        curlBtn.type = 'button';
        curlBtn.innerHTML = '🔗 curl';
        curlBtn.title = 'Copy as curl';
        curlBtn.onclick = () => {
            const url = getAwsUrl(panelId) || proxy;
            const body = tryParse(prefix);
            const curl = buildCurl(httpMethod, url, body);
            navigator.clipboard.writeText(curl).then(() => {
                const o = curlBtn.innerHTML;
                curlBtn.innerHTML = '✓ Copied';
                setTimeout(() => curlBtn.innerHTML = o, 1500);
            });
        };

        // View code button (opens modal with JS/PHP/curl tabs)
        const codeBtn = document.createElement('button');
        codeBtn.className = 'btn-copy tryit-view-code';
        codeBtn.type = 'button';
        codeBtn.innerHTML = '📋 code';
        codeBtn.title = 'View code (JS / PHP / curl)';
        codeBtn.onclick = () => {
            const url = getAwsUrl(panelId) || proxy;
            const body = tryParse(prefix);
            showCodeModal(httpMethod, url, body);
        };

        wrap.appendChild(curlBtn);
        wrap.appendChild(codeBtn);
        sendRow.appendChild(wrap);
    }

    /** Code snippet modal (lazy-create on first open) */
    function ensureCodeModal() {
        if (document.getElementById('awsapi-code-modal')) return;
        const modal = document.createElement('div');
        modal.id = 'awsapi-code-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px;';
        modal.innerHTML = `
            <div style="background:#1e1e2e;border-radius:14px;max-width:900px;width:100%;max-height:85vh;display:flex;flex-direction:column;overflow:hidden;color:#cdd6f4;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.1);">
                    <strong style="font-size:0.95rem;">📋 Code snippet</strong>
                    <button id="awsapi-code-close" style="background:none;border:none;color:#cdd6f4;cursor:pointer;font-size:1.2rem;padding:0 6px;">×</button>
                </div>
                <div style="display:flex;gap:4px;padding:10px 20px 0;border-bottom:1px solid rgba(255,255,255,0.05);">
                    <button class="awsapi-code-tab active" data-lang="curl">🐚 curl</button>
                    <button class="awsapi-code-tab" data-lang="js">📜 JavaScript</button>
                    <button class="awsapi-code-tab" data-lang="php">🐘 PHP</button>
                    <button id="awsapi-code-copy" style="margin-left:auto;background:#10b981;color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:0.78rem;cursor:pointer;font-weight:600;">Copy</button>
                </div>
                <pre style="margin:0;padding:18px 20px;overflow:auto;flex:1;background:#181825;"><code id="awsapi-code-content" class="language-bash" style="font-size:0.78rem;font-family:'SF Mono',Consolas,monospace;"></code></pre>
            </div>
        `;
        document.body.appendChild(modal);

        // Close handlers
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
        document.getElementById('awsapi-code-close').onclick = () => modal.style.display = 'none';

        // Tab handlers
        modal.querySelectorAll('.awsapi-code-tab').forEach(tab => {
            tab.style.cssText = 'background:transparent;border:none;color:#7f849c;padding:8px 14px;font-size:0.82rem;cursor:pointer;border-bottom:2px solid transparent;font-weight:600;';
            tab.onclick = () => {
                modal.querySelectorAll('.awsapi-code-tab').forEach(t => {
                    t.style.color = '#7f849c';
                    t.style.borderBottomColor = 'transparent';
                    t.classList.remove('active');
                });
                tab.style.color = '#10b981';
                tab.style.borderBottomColor = '#10b981';
                tab.classList.add('active');
                renderCodeForLang(tab.dataset.lang);
            };
        });
        // Initial active style
        const firstTab = modal.querySelector('.awsapi-code-tab.active');
        if (firstTab) { firstTab.style.color = '#10b981'; firstTab.style.borderBottomColor = '#10b981'; }

        // Copy handler
        document.getElementById('awsapi-code-copy').onclick = (ev) => {
            const txt = document.getElementById('awsapi-code-content').textContent;
            navigator.clipboard.writeText(txt).then(() => {
                ev.target.textContent = '✓ Copied';
                setTimeout(() => ev.target.textContent = 'Copy', 1500);
            });
        };
    }

    let _codeState = { method: 'POST', url: '', body: null };
    function renderCodeForLang(lang) {
        const code = document.getElementById('awsapi-code-content');
        if (!code) return;
        const { method, url, body } = _codeState;
        let text, langClass;
        if (lang === 'curl') { text = buildCurl(method, url, body); langClass = 'language-bash'; }
        else if (lang === 'js') { text = buildJs(method, url, body); langClass = 'language-javascript'; }
        else { text = buildPhp(method, url, body); langClass = 'language-php'; }
        code.className = langClass;
        code.textContent = text;
        if (window.Prism) Prism.highlightElement(code);
    }
    function showCodeModal(method, url, body) {
        ensureCodeModal();
        _codeState = { method, url, body };
        document.getElementById('awsapi-code-modal').style.display = 'flex';
        // Reset to curl tab
        document.querySelectorAll('.awsapi-code-tab').forEach(t => {
            t.classList.remove('active');
            t.style.color = '#7f849c';
            t.style.borderBottomColor = 'transparent';
        });
        const curlTab = document.querySelector('.awsapi-code-tab[data-lang="curl"]');
        if (curlTab) { curlTab.classList.add('active'); curlTab.style.color = '#10b981'; curlTab.style.borderBottomColor = '#10b981'; }
        renderCodeForLang('curl');
    }

    /** Render a JSON value with syntax highlighting via Prism */
    function renderPrettyJson(container, data) {
        if (!container) return;
        const text = JSON.stringify(data, null, 2);
        container.innerHTML = `<pre style="margin:0;background:transparent;"><code class="language-json" style="font-size:0.78rem;">${escapeHtml(text)}</code></pre>`;
        if (window.Prism) Prism.highlightElement(container.querySelector('code'));
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /** Show badge state on Send buttons — "🔑 Using: <name>" when key set, "🔒 Set key first" when not */
    function refreshSendBadges() {
        const userKey = window.AWSAPI_UserKey;
        const hasKey = !!(userKey && userKey.apiKey);
        document.querySelectorAll('.btn-send').forEach(btn => {
            let badge = btn.querySelector('.tryit-direct-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'tryit-direct-badge';
                btn.appendChild(badge);
            }
            if (hasKey) {
                badge.style.cssText = 'background:#dcfce7;color:#166534;padding:2px 8px;border-radius:8px;font-size:0.65rem;font-weight:700;margin-left:8px;border:1px solid #bbf7d0;';
                badge.innerHTML = '🔑 ' + (userKey.name || 'My Key');
            } else {
                badge.style.cssText = 'background:#fef2f2;color:#991b1b;padding:2px 8px;border-radius:8px;font-size:0.65rem;font-weight:700;margin-left:8px;border:1px solid #fecaca;';
                badge.innerHTML = '🔒 Set key first';
            }
        });
    }
    // Expose so the docs page can call it on Save
    window.AWSAPI_TryIt_refreshBadges = refreshSendBadges;

    function init(config) {
        const { prefix, panelId, proxy, presets, defaultPreset, metaFormatter, method } = config;
        const httpMethod = method || 'POST';
        const fmt = metaFormatter || defaultMetaFormatter;

        // 0. Inject Copy-as-curl + View-code buttons into send-row
        injectExtraActions(prefix, panelId, proxy, httpMethod);
        refreshSendBadges();

        // 1. JSON editor live validation
        const editor = $(prefix + '-req-preview');
        if (editor) {
            editor.addEventListener('input', () => setStatus(prefix, tryParse(prefix) !== null));
        }

        // 2. Format button
        const formatBtn = $(prefix + '-format-btn');
        if (formatBtn) {
            formatBtn.addEventListener('click', () => {
                const p = tryParse(prefix);
                if (p) setEditor(prefix, p);
            });
        }

        // 3. Preset buttons (scoped ke panel)
        if (panelId && presets) {
            document.querySelectorAll('#' + panelId + ' .preset-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const p = presets[btn.dataset.preset];
                    if (p) setEditor(prefix, p);
                });
            });
        }

        // 4. Initial load
        if (presets && defaultPreset && presets[defaultPreset]) {
            setEditor(prefix, presets[defaultPreset]);
        }

        // 5. Send Request handler
        const runBtn = $(prefix + '-run');
        if (runBtn) {
            runBtn.addEventListener('click', async () => {
                const respEl = $(prefix + '-resp');
                const statusEl = $(prefix + '-status');
                const metaEl = $(prefix + '-meta');
                const spinner = $(prefix + '-spinner');

                const parsed = tryParse(prefix);
                if (!parsed && httpMethod === 'POST') {
                    respEl.className = 'resp-body error';
                    respEl.textContent = '❌ JSON invalid';
                    statusEl.textContent = 'INVALID JSON';
                    statusEl.className = 'status-pill bad';
                    return;
                }

                runBtn.disabled = true;
                if (spinner) spinner.style.display = 'inline-block';
                statusEl.textContent = '...';
                statusEl.className = 'status-pill idle';
                metaEl.textContent = '';
                respEl.classList.remove('error', 'direct-mode');
                respEl.className = 'resp-body';
                respEl.textContent = '⏳ Sending request...';

                // ==== MANDATORY: user's API Key required for Send Request ====
                // No proxy fallback anymore — env key stays server-side only.
                const userKey = window.AWSAPI_UserKey;
                if (!userKey || !userKey.apiKey) {
                    respEl.className = 'resp-body error';
                    respEl.innerHTML = '🔒 <b>API Key required</b> — click the <b>🔑 My Key</b> button at the top to configure your AWS Location Service API Key first. Your key stays in this browser only.';
                    statusEl.textContent = 'KEY REQUIRED';
                    statusEl.className = 'status-pill bad';
                    metaEl.textContent = '';
                    runBtn.disabled = false;
                    if (spinner) spinner.style.display = 'none';
                    // Auto-open the modal for convenience
                    const openBtn = document.getElementById('btnKeyInspector');
                    if (openBtn) {
                        openBtn.classList.add('pulse-alert');
                        setTimeout(() => openBtn.classList.remove('pulse-alert'), 2000);
                    }
                    return;
                }

                const t0 = performance.now();
                try {
                    let awsUrl = getAwsUrlRaw(panelId);
                    if (!awsUrl) {
                        respEl.className = 'resp-body error';
                        respEl.textContent = '❌ AWS direct URL not found in this panel.';
                        statusEl.textContent = 'URL NOT FOUND';
                        statusEl.className = 'status-pill bad';
                        runBtn.disabled = false;
                        if (spinner) spinner.style.display = 'none';
                        return;
                    }
                    // Replace placeholders + append user's key
                    if (userKey.region) awsUrl = awsUrl.replace(/\{region\}/g, userKey.region);
                    awsUrl = awsUrl.replace(/key=\.\.\.|key=\*\*\*|key=\{[^}]+\}/g, '');
                    const sep = awsUrl.includes('?') ? '&' : '?';
                    const targetUrl = awsUrl + sep + 'key=' + encodeURIComponent(userKey.apiKey);
                    const targetOpts = {
                        method: httpMethod,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
                    };
                    respEl.classList.add('direct-mode');
                    if (httpMethod !== 'GET') targetOpts.body = JSON.stringify(parsed);

                    const res = await fetch(targetUrl, targetOpts);
                    const ms = Math.round(performance.now() - t0);
                    const data = await res.json();

                    statusEl.textContent = `${res.status} ${res.statusText}`;
                    statusEl.className = `status-pill ${res.ok ? 'ok' : 'bad'}`;
                    metaEl.innerHTML = fmt(data, ms, res.ok);

                    if (!res.ok) respEl.classList.add('error');
                    renderPrettyJson(respEl, data);
                } catch (err) {
                    const ms = Math.round(performance.now() - t0);
                    respEl.classList.add('error');
                    respEl.textContent = 'Network error: ' + err.message;
                    statusEl.textContent = 'NETWORK ERROR';
                    statusEl.className = 'status-pill bad';
                    metaEl.innerHTML = `<b>${ms}ms</b>`;
                } finally {
                    runBtn.disabled = false;
                    if (spinner) spinner.style.display = 'none';
                }
            });
        }
    }

    /**
     * Salin teks ke papan klip.
     *
     * navigator.clipboard hanya ada di secure context. Halaman ini sering dibuka
     * lewat http (grabmap.test, IP lokal, demo internal) — di sana objeknya tidak
     * ada sama sekali, jadi versi lama melempar TypeError dan tombol Copy diam
     * saja tanpa pesan. Karena itu selalu disediakan jalur cadangan textarea +
     * execCommand yang jalan di http.
     */
    function copyText(text, btn, sourceEl) {
        const done = ok => {
            if (!ok && sourceEl) selectContents(sourceEl);
            if (!btn) return;
            const orig = btn.dataset.origHtml || btn.innerHTML;
            btn.dataset.origHtml = orig;
            btn.innerHTML = ok ? '✓ Copied' : '⌘C / Ctrl+C';
            setTimeout(() => { btn.innerHTML = orig; delete btn.dataset.origHtml; }, ok ? 1500 : 3000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => done(true), () => done(fallback(text)));
            return;
        }
        done(fallback(text));
    }

    /**
     * Kalau menyalin benar-benar gagal (browser tanpa izin papan klip, jendela
     * tidak fokus), teksnya diseleksi supaya pengguna tinggal menekan Ctrl+C —
     * lebih berguna daripada tombol yang cuma bilang gagal.
     */
    function selectContents(node) {
        try {
            const range = document.createRange();
            range.selectNodeContents(node);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } catch (e) { /* biarkan */ }
    }

    function fallback(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        // Di luar layar tapi tetap bisa diseleksi; display:none bikin execCommand gagal.
        ta.style.cssText = 'position:fixed;top:-1000px;left:-1000px;opacity:0;';
        document.body.appendChild(ta);
        ta.select();
        ta.setSelectionRange(0, ta.value.length);
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(ta);
        return ok;
    }

    window.AWSAPI_copyText = copyText;

    // Generic copy-to-clipboard helper
    if (!window.copyToClipboard) {
        window.copyToClipboard = (id, btn) => {
            const el = document.getElementById(id);
            if (!el) return;
            const isField = el.tagName === 'TEXTAREA' || el.tagName === 'INPUT';
            copyText(isField ? el.value : el.textContent, btn, isField ? null : el);
        };
    }

    window.AWSAPI_TryIt = { init };
})(window);
