/**
 * Request Builder untuk /docs/aws-api.
 *
 * Menggantikan tiga bagian yang dulu menjelaskan hal yang sama dengan cara
 * berbeda: blok Request Syntax statis, kartu Field Rules, dan kartu tier harga.
 * Di sini aturannya jadi bentuk kontrolnya sendiri — yang wajib pilih satu jadi
 * radio, yang boleh jamak jadi chip — dan keranjang harganya dihitung ulang
 * tiap kali pilihan berubah.
 *
 * Isi tiap operasi ada di aws-api-schemas.js. Berkas ini sengaja tidak tahu
 * apa pun soal SearchText atau Places.
 *
 * Tombol kirimnya tidak memanggil AWS sendiri: JSON hasil rakitan ditaruh di
 * editor panel "Try it Live" yang sudah ada lalu tombol Send-nya diklik. Jadi
 * penanganan API key, proxy, dan penulisan respons tetap satu jalur.
 */
(function (window, document) {
    'use strict';

    const TIER_RANK = { Label: 0, Core: 1, Advanced: 2, Premium: 3, Stored: 4 };

    function t(key, fallback) {
        const lang = document.documentElement.lang || 'en';
        const dict = (window.AWSAPI_I18N || {})[lang] || (window.AWSAPI_I18N || {}).en || {};
        return dict[key] || fallback || key;
    }

    const el = (tag, cls, html) => {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (html != null) n.innerHTML = html;
        return n;
    };

    /** Menaruh nilai di jalur bertitik: "Filter.Circle" → { Filter: { Circle: ... } } */
    function setPath(obj, path, value) {
        const parts = path.split('.');
        let cur = obj;
        parts.slice(0, -1).forEach(p => { cur[p] = cur[p] || {}; cur = cur[p]; });
        cur[parts[parts.length - 1]] = value;
    }

    function build(root, schema) {
        // ---- keadaan builder: satu entri per field, plus daftar fitur tambahan
        const state = { fields: {}, features: [] };
        schema.groups.forEach(g => {
            (g.fields || []).forEach(f => {
                state.fields[f.name] = { on: !!f.on, value: clone(f.value), def: f };
            });
        });

        function clone(v) { return v == null ? v : JSON.parse(JSON.stringify(v)); }

        // ---------- perakitan nilai ----------
        function valueOf(f) {
            const st = state.fields[f.name];
            switch (f.type) {
                case 'number': return Number(st.value);
                case 'lnglat': return [Number(st.value[0]), Number(st.value[1])];
                case 'bbox': return st.value.map(Number);
                case 'circle': return { Center: [Number(st.value.Center[0]), Number(st.value.Center[1])], Radius: Number(st.value.Radius) };
                case 'list': return String(st.value).split(',').map(s => s.trim()).filter(Boolean);
                case 'bool': return true;
                case 'poslist': return String(st.value).split(';').map(pair => {
                    const [lng, lat] = pair.split(',').map(n => Number(n.trim()));
                    return { Position: [lng, lat] };
                }).filter(p => p.Position.every(Number.isFinite));
                default: return st.value;
            }
        }

        function buildJson() {
            const out = {};
            schema.groups.forEach(g => (g.fields || []).forEach(f => {
                if (state.fields[f.name].on) setPath(out, f.name, valueOf(f));
            }));
            if (state.features.length) out.AdditionalFeatures = state.features.slice();
            return out;
        }

        /**
         * Operasi GET (GetPlace) tidak punya badan JSON — parameternya di path dan
         * query string. AWS menolak nilai jamak yang dipisah koma, jadi tiap nilai
         * AdditionalFeatures ditulis sebagai parameter tersendiri.
         */
        function buildQuery() {
            const parts = [];
            let path = schema.pathTemplate || '';
            schema.groups.forEach(g => (g.fields || []).forEach(f => {
                if (!state.fields[f.name].on) return;
                const val = valueOf(f);
                if (f.inPath) path = path.replace('{' + f.name + '}', encodeURIComponent(val));
                else parts.push(`${f.query}=${encodeURIComponent(val)}`);
            }));
            state.features.forEach(v => parts.push(`${schema.featureQuery}=${encodeURIComponent(v)}`));
            return path + (parts.length ? '\n      ?' + parts.join('\n      &') : '');
        }

        const isQuery = () => schema.transport === 'query';
        const preview = () => isQuery() ? buildQuery() : JSON.stringify(buildJson(), null, 2);

        function currentTier() {
            let tier = schema.baseTier;
            const raise = candidate => {
                if (TIER_RANK[candidate] > TIER_RANK[tier]) tier = candidate;
            };
            if (state.features.length) {
                schema.groups.forEach(g => (g.options || []).forEach(o => {
                    if (state.features.includes(o.value) && o.tier) raise(o.tier);
                }));
            }
            schema.groups.forEach(g => (g.fields || []).forEach(f => {
                if (!f.tierWhen || !state.fields[f.name].on) return;
                const hit = f.tierWhen[state.fields[f.name].value];
                if (hit) raise(hit);
            }));
            return tier;
        }

        /** Semua yang bikin request ditolak AWS, dikumpulkan jadi satu daftar. */
        function problems() {
            const out = [];
            schema.groups.forEach(g => {
                const chosen = (g.fields || []).filter(f => state.fields[f.name].on);
                if (g.kind === 'one-or-more' && !chosen.length) out.push(t(g.warnKey));
                if (g.kind === 'exactly-one' && chosen.length !== 1) out.push(t(g.warnKey));
                (g.fields || []).forEach(f => {
                    if (!state.fields[f.name].on) return;
                    if (f.type === 'number') {
                        const n = Number(state.fields[f.name].value);
                        if (!Number.isFinite(n) || (f.min != null && n < f.min) || (f.max != null && n > f.max)) {
                            out.push(`${f.name}: ${f.min}–${f.max}`);
                        }
                    }
                    if (f.type === 'poslist' && !valueOf(f).length) {
                        out.push(`${f.name}: lng,lat`);
                    }
                    if (f.maxLength && String(state.fields[f.name].value).length > f.maxLength) {
                        out.push(`${f.name}: max ${f.maxLength}`);
                    }
                    if (f.type === 'circle' && f.max && Number(state.fields[f.name].value.Radius) > f.max) {
                        out.push(`Filter.Circle.Radius: max ${f.max}`);
                    }
                });
            });
            return out;
        }

        // ---------- kontrol nilai per jenis field ----------
        function valueControl(f) {
            const st = state.fields[f.name];
            const wrap = el('span', 'rb-val');

            const num = (get, set) => {
                const i = el('input', 'rb-num');
                i.type = 'text';
                i.inputMode = 'decimal';
                i.value = get();
                i.addEventListener('input', () => { set(i.value); refresh(); });
                return i;
            };
            const text = (get, set, ph) => {
                const i = el('input', 'rb-text');
                i.type = 'text';
                i.value = get() == null ? '' : get();
                if (ph) i.placeholder = ph;
                i.addEventListener('input', () => { set(i.value); refresh(); });
                return i;
            };

            if (f.type === 'string' || f.type === 'list') {
                wrap.appendChild(text(() => st.value, v => { st.value = v; }, f.placeholder));
            } else if (f.type === 'number') {
                wrap.appendChild(num(() => st.value, v => { st.value = v; }));
                if (f.min != null) wrap.appendChild(el('span', 'rb-range', `${f.min}–${f.max}`));
            } else if (f.type === 'lnglat') {
                wrap.appendChild(num(() => st.value[0], v => { st.value[0] = v; }));
                wrap.appendChild(num(() => st.value[1], v => { st.value[1] = v; }));
                wrap.appendChild(el('span', 'rb-range', 'lng, lat'));
            } else if (f.type === 'bbox') {
                [0, 1, 2, 3].forEach(i => wrap.appendChild(num(() => st.value[i], v => { st.value[i] = v; })));
                wrap.appendChild(el('span', 'rb-range', 'w, s, e, n'));
            } else if (f.type === 'circle') {
                wrap.appendChild(num(() => st.value.Center[0], v => { st.value.Center[0] = v; }));
                wrap.appendChild(num(() => st.value.Center[1], v => { st.value.Center[1] = v; }));
                wrap.appendChild(num(() => st.value.Radius, v => { st.value.Radius = v; }));
                // Tanpa label kolom di sini: catatan di bawah barisnya sudah
                // menyebut center + radius beserta batasnya.
                wrap.appendChild(el('span', 'rb-range', 'lng, lat, m'));
            } else if (f.type === 'bool') {
                // Cukup kotak centangnya; nilainya selalu true kalau dikirim.
            } else if (f.type === 'poslist') {
                wrap.appendChild(text(() => st.value, v => { st.value = v; }, '106.82,-6.17; 106.85,-6.20'));
                wrap.appendChild(el('span', 'rb-range', 'lng,lat; lng,lat'));
            } else if (f.type === 'enum') {
                const sel = el('select', 'rb-select');
                f.options.forEach(o => {
                    const opt = el('option', null, o);
                    opt.value = o;
                    sel.appendChild(opt);
                });
                sel.value = st.value;
                sel.addEventListener('change', () => { st.value = sel.value; refresh(); });
                wrap.appendChild(sel);
            }
            return wrap;
        }

        // ---------- rangka ----------
        root.innerHTML = '';
        root.classList.add('rb');

        const head = el('div', 'rb-head');
        head.appendChild(el('span', 'rb-title', `<i class="bi bi-ui-checks"></i> <span data-i18n="bld_title">Request Builder</span>`));
        const tierBox = el('span', 'rb-tier');
        head.appendChild(tierBox);
        root.appendChild(head);

        const body = el('div', 'rb-body');
        const left = el('div', 'rb-groups');
        const right = el('div', 'rb-out');
        body.appendChild(left);
        body.appendChild(right);
        root.appendChild(body);

        schema.groups.forEach(g => left.appendChild(renderGroup(g)));

        function renderGroup(g) {
            const box = el('div', 'rb-group');
            const rule = {
                'one-or-more': 'bld_rule_oneplus',
                'exactly-one': 'bld_rule_exactly1',
                required: 'bld_rule_required',
                any: 'bld_rule_any',
                multi: 'bld_rule_multi'
            }[g.kind];
            box.appendChild(el('div', 'rb-group-head',
                `<span class="rb-group-title" data-i18n="${g.titleKey}"></span>` +
                `<span class="rb-rule rb-rule-${g.kind}" data-i18n="${rule}"></span>`));

            if (g.kind === 'multi') {
                const chips = el('div', 'rb-chips');
                g.options.forEach(o => {
                    const chip = el('button', 'rb-chip' + (o.unsupported ? ' is-blocked' : ''));
                    chip.type = 'button';
                    chip.innerHTML = `<code>${o.value}</code>` +
                        (o.tier ? ` <span class="tier-pill tier-${o.tier.toLowerCase()}">${o.tier}</span>` : '');
                    if (o.unsupported) {
                        // Nilai yang ditolak region ini tetap ditampilkan — kalau
                        // disembunyikan, orang akan mencarinya di dokumen AWS dan
                        // mengira halaman ini yang kurang lengkap.
                        chip.disabled = true;
                        chip.setAttribute('data-i18n-title', 'bld_unsupported');
                        chip.appendChild(el('i', 'bi bi-slash-circle rb-chip-x'));
                    } else {
                        chip.addEventListener('click', () => {
                            const i = state.features.indexOf(o.value);
                            i < 0 ? state.features.push(o.value) : state.features.splice(i, 1);
                            chip.classList.toggle('is-on', i < 0);
                            refresh();
                        });
                    }
                    chips.appendChild(chip);
                });
                box.appendChild(chips);
                box.appendChild(groupNote(g));
                return box;
            }

            g.fields.forEach(f => {
                const row = el('label', 'rb-field');

                // Field wajib tidak diberi kotak centang: ia selalu ikut terkirim,
                // dan centang yang tidak bisa dilepas cuma bikin bingung.
                if (f.required) {
                    state.fields[f.name].on = true;
                    row.appendChild(el('span', 'rb-req', '<i class="bi bi-asterisk"></i>'));
                    row.appendChild(el('code', 'rb-name', f.name));
                    row.appendChild(valueControl(f));
                    if (f.noteKey) row.appendChild(el('span', 'rb-note', `<span data-i18n-html="${f.noteKey}"></span>`));
                    box.appendChild(row);
                    return;
                }

                const input = el('input');
                input.type = g.kind === 'exactly-one' ? 'radio' : 'checkbox';
                input.name = g.id;
                input.checked = state.fields[f.name].on;
                input.addEventListener('change', () => {
                    if (g.kind === 'exactly-one') {
                        g.fields.forEach(other => { state.fields[other.name].on = other.name === f.name; });
                    } else {
                        state.fields[f.name].on = input.checked;
                    }
                    syncChecks();
                    refresh();
                });
                row.appendChild(input);
                row.appendChild(el('code', 'rb-name', f.name));
                row.appendChild(valueControl(f));

                const tierHint = f.tierWhen ? Object.values(f.tierWhen)[0] : null;
                if (tierHint) row.appendChild(el('span', `tier-pill tier-${tierHint.toLowerCase()} rb-field-tier`, tierHint));
                if (f.noteKey) row.appendChild(el('span', 'rb-note', `<span data-i18n-html="${f.noteKey}"></span>`));

                f._input = input;
                box.appendChild(row);
            });
            box.appendChild(groupNote(g));
            return box;
        }

        /** Catatan setingkat grup — dipakai untuk hal yang tidak menempel pada satu
            field, misalnya nilai yang ditolak region atau field yang tidak berguna
            di sini sama sekali. */
        function groupNote(g) {
            return el('div', 'rb-note rb-group-note',
                g.noteKey ? `<span data-i18n-html="${g.noteKey}"></span>` : '');
        }

        function syncChecks() {
            schema.groups.forEach(g => (g.fields || []).forEach(f => {
                if (f._input) f._input.checked = state.fields[f.name].on;
            }));
        }

        // ---------- keluaran ----------
        const outHead = el('div', 'rb-out-head',
            `<span data-i18n="${schema.transport === 'query' ? 'bld_url' : 'bld_json'}">Request JSON</span>`);
        const copyBtn = el('button', 'rb-copy', '<i class="bi bi-clipboard"></i> <span data-i18n="btn_copy_short">Salin</span>');
        copyBtn.type = 'button';
        outHead.appendChild(copyBtn);
        right.appendChild(outHead);

        const pre = el('pre', 'rb-json');
        const code = el('code');
        pre.appendChild(code);
        right.appendChild(pre);

        const warnBox = el('div', 'rb-warn');
        right.appendChild(warnBox);

        const hintBox = el('div', 'rb-hints');
        right.appendChild(hintBox);

        const sendBtn = el('button', 'rb-send', `<i class="bi bi-play-fill"></i> <span data-i18n="bld_send">Kirim ke AWS</span>`);
        sendBtn.type = 'button';
        right.appendChild(sendBtn);

        copyBtn.addEventListener('click', () => {
            // Helper bersama dari aws-api-try-it.js: di halaman http, papan klip
            // browser tidak tersedia dan ia jatuh ke jalur execCommand.
            if (window.AWSAPI_copyText) window.AWSAPI_copyText(code.textContent, copyBtn, code);
        });

        sendBtn.addEventListener('click', () => {
            const run = document.getElementById(schema.tryPrefix + '-run');
            if (!run) return;

            if (isQuery()) {
                // Panel GET punya kotak isian sendiri (PlaceId, bahasa, fitur);
                // builder mengisinya lalu menekan tombolnya.
                Object.entries(schema.sendInputs || {}).forEach(([id, source]) => {
                    const input = document.getElementById(id);
                    if (!input) return;
                    input.value = source === '@features'
                        ? state.features.join(',')
                        : (state.fields[source] && state.fields[source].on ? valueOf(state.fields[source].def) : '');
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
            } else {
                const editor = document.getElementById(schema.tryPrefix + '-req-preview');
                if (!editor) return;
                editor.value = JSON.stringify(buildJson(), null, 2);
                editor.dispatchEvent(new Event('input', { bubbles: true }));
            }
            run.click();
            document.getElementById(schema.tryPrefix + '-resp')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        function refresh() {
            code.textContent = preview();

            const tier = currentTier();
            tierBox.innerHTML = `<span data-i18n="bld_tier_now">Tier</span> ` +
                `<span class="tier-pill tier-${tier.toLowerCase()}">${tier}</span>`;

            const errs = problems();
            warnBox.innerHTML = errs.length
                ? `<i class="bi bi-exclamation-triangle-fill"></i> ${errs.join(' · ')}`
                : '';
            warnBox.classList.toggle('is-on', errs.length > 0);
            sendBtn.disabled = errs.length > 0;

            // Blok respons yang baru muncul kalau field tertentu ikut dikirim.
            const hints = (schema.responseHints || []).filter(h =>
                (h.when.additionalFeature && state.features.includes(h.when.additionalFeature)) ||
                (h.when.field && state.fields[h.when.field] && state.fields[h.when.field].on));
            hintBox.innerHTML = hints.length
                ? `<span data-i18n="bld_resp_adds">Respons ikut memuat</span> ` +
                  hints.map(h => `<code>${h.block}</code>`).join(' ')
                : '';

            if (window.AWSAPI_applyI18n) window.AWSAPI_applyI18n(document.documentElement.lang || 'en');
        }

        refresh();
        return refresh;
    }

    /**
     * Tab per operasi: Request (perakit + hasilnya) / Respons / Error.
     *
     * Hasil panggilan sengaja tinggal di tab Request bersama perakitnya —
     * memisahkannya ke tab sendiri membuat balasan yang baru diminta jadi
     * tersembunyi. Yang dipisah hanya bacaan: bentuk respons dan daftar error.
     */
    function initTabs() {
        document.querySelectorAll('.op-tabs').forEach(bar => {
            const scope = bar.parentElement;
            const show = name => {
                bar.querySelectorAll('.op-tab-btn').forEach(b => b.classList.toggle('is-on', b.dataset.tab === name));
                scope.querySelectorAll(':scope > .op-tab').forEach(t => t.classList.toggle('is-on', t.dataset.tab === name));
            };
            bar.querySelectorAll('.op-tab-btn').forEach(btn => {
                btn.addEventListener('click', () => show(btn.dataset.tab));
            });
        });
    }

    function init() {
        initTabs();
        const refreshers = [];
        document.querySelectorAll('[data-builder]').forEach(root => {
            const schema = (window.AWSAPI_SCHEMAS || {})[root.dataset.builder];
            if (schema) refreshers.push(build(root, schema));
        });

        // Ganti bahasa hanya menyentuh elemen ber-data-i18n; teks yang dirakit
        // builder (peringatan, pil tier) harus digambar ulang sendiri.
        const original = window.AWSAPI_applyI18n;
        if (original && refreshers.length) {
            let painting = false;
            window.AWSAPI_applyI18n = function (lang) {
                original(lang);
                if (painting) return;
                painting = true;
                refreshers.forEach(fn => fn());
                painting = false;
            };
        }
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();
})(window, document);
