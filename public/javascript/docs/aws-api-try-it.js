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

    function init(config) {
        const { prefix, panelId, proxy, presets, defaultPreset, metaFormatter, method } = config;
        const httpMethod = method || 'POST';
        const fmt = metaFormatter || defaultMetaFormatter;

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
                respEl.className = 'resp-body';
                respEl.textContent = '⏳ Sending request...';

                const t0 = performance.now();
                try {
                    const opts = {
                        method: httpMethod,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                    };
                    if (httpMethod !== 'GET') opts.body = JSON.stringify(parsed);

                    const res = await fetch(proxy, opts);
                    const ms = Math.round(performance.now() - t0);
                    const data = await res.json();

                    statusEl.textContent = `${res.status} ${res.statusText}`;
                    statusEl.className = `status-pill ${res.ok ? 'ok' : 'bad'}`;
                    metaEl.innerHTML = fmt(data, ms, res.ok);

                    if (!res.ok) respEl.classList.add('error');
                    respEl.textContent = JSON.stringify(data, null, 2);
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

    // Generic copy-to-clipboard helper
    if (!window.copyToClipboard) {
        window.copyToClipboard = (id, btn) => {
            const el = document.getElementById(id);
            const txt = (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') ? el.value : el.textContent;
            navigator.clipboard.writeText(txt).then(() => {
                const orig = btn.innerHTML;
                btn.innerHTML = '✓ Copied';
                setTimeout(() => btn.innerHTML = orig, 1500);
            });
        };
    }

    window.AWSAPI_TryIt = { init };
})(window);
