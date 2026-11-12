        // ---- Dropdown pengganti <select> ----
        // Select aslinya tidak dibuang: nilainya tetap ikut terkirim saat submit dan
        // event 'change' tetap dilepas, jadi kode halaman yang mendengarkannya tetap jalan.
        // Tandai <select data-native> kalau ada yang mau dibiarkan bawaan.
        (function () {
            function enhance(select) {
                if (select.dataset.gmSelect || select.multiple || select.size > 1) return;
                select.dataset.gmSelect = '1';

                const wrap = document.createElement('div');
                wrap.className = 'gm-select';
                select.parentNode.insertBefore(wrap, select);
                wrap.appendChild(select);
                select.classList.add('gm-select-native');
                select.tabIndex = -1;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'gm-select-btn';
                btn.setAttribute('aria-haspopup', 'listbox');
                btn.setAttribute('aria-expanded', 'false');
                btn.innerHTML = '<span class="gm-select-label"></span><i class="bi bi-chevron-down gm-select-caret"></i>';

                const panel = document.createElement('div');
                panel.className = 'gm-select-panel';
                panel.setAttribute('role', 'listbox');

                // Judul <optgroup> ikut dirender sebagai pemisah — dipakai mis. saat
                // memilih API key yang bisa berasal dari beberapa akun AWS.
                let lastGroup = null;

                Array.from(select.options).forEach((opt, i) => {
                    const group = opt.parentElement?.tagName === 'OPTGROUP' ? opt.parentElement.label : null;
                    if (group && group !== lastGroup) {
                        const head = document.createElement('div');
                        head.className = 'gm-select-group';
                        head.textContent = group;
                        panel.appendChild(head);
                    }
                    lastGroup = group;

                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'gm-select-opt';
                    row.dataset.value = opt.value;
                    row.style.setProperty('--i', i);
                    row.style.animationDelay = (i * 20) + 'ms';
                    if (opt.disabled) row.disabled = true;
                    row.innerHTML = '<i class="bi bi-check-lg tick"></i><span class="txt"></span>';
                    row.querySelector('.txt').textContent = opt.text;
                    row.setAttribute('role', 'option');
                    panel.appendChild(row);
                });

                wrap.append(btn, panel);

                const rows = () => Array.from(panel.querySelectorAll('.gm-select-opt'));
                let cursor = Math.max(select.selectedIndex, 0);

                function paint() {
                    const value = select.value;
                    btn.querySelector('.gm-select-label').textContent = select.options[select.selectedIndex]?.text ?? '';
                    rows().forEach((r, i) => {
                        r.classList.toggle('sel', r.dataset.value === value);
                        r.classList.toggle('cursor', i === cursor);
                        r.setAttribute('aria-selected', r.dataset.value === value ? 'true' : 'false');
                    });
                }

                function openPanel() {
                    closeAllSelects(wrap);
                    // Buka ke atas kalau ruang di bawah tidak cukup.
                    const room = window.innerHeight - btn.getBoundingClientRect().bottom;
                    wrap.classList.toggle('drop-up', room < 240);
                    wrap.classList.add('open');
                    btn.setAttribute('aria-expanded', 'true');
                    cursor = Math.max(select.selectedIndex, 0);
                    paint();
                    rows()[cursor]?.scrollIntoView({ block: 'nearest' });
                }

                function closePanel() {
                    wrap.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                }

                function choose(value) {
                    if (select.value !== value) {
                        select.value = value;
                        select.dispatchEvent(new Event('input', { bubbles: true }));
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    paint();
                    closePanel();
                    btn.focus();
                }

                btn.addEventListener('click', () => {
                    wrap.classList.contains('open') ? closePanel() : openPanel();
                });

                panel.addEventListener('click', (e) => {
                    const row = e.target.closest('.gm-select-opt');
                    if (row && !row.disabled) choose(row.dataset.value);
                });

                btn.addEventListener('keydown', (e) => {
                    const isOpen = wrap.classList.contains('open');

                    if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key) && !isOpen) {
                        e.preventDefault();
                        openPanel();
                        return;
                    }
                    if (!isOpen) return;

                    const list = rows();
                    if (e.key === 'Escape') { e.preventDefault(); closePanel(); }
                    else if (e.key === 'ArrowDown') { e.preventDefault(); cursor = Math.min(cursor + 1, list.length - 1); paint(); list[cursor].scrollIntoView({ block: 'nearest' }); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); cursor = Math.max(cursor - 1, 0); paint(); list[cursor].scrollIntoView({ block: 'nearest' }); }
                    else if (e.key === 'Home') { e.preventDefault(); cursor = 0; paint(); }
                    else if (e.key === 'End') { e.preventDefault(); cursor = list.length - 1; paint(); }
                    else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choose(list[cursor].dataset.value); }
                });

                // Kalau nilainya diubah dari kode lain, labelnya ikut menyesuaikan.
                select.addEventListener('change', paint);

                paint();
            }

            function closeAllSelects(except) {
                document.querySelectorAll('.gm-select.open').forEach((el) => {
                    if (el !== except) {
                        el.classList.remove('open');
                        el.querySelector('.gm-select-btn')?.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.gm-select')) closeAllSelects(null);
            });

            document.querySelectorAll('select:not([data-native])').forEach(enhance);

            // Halaman bisa memanggil ini setelah menyisipkan select baru (mis. di modal).
            window.gmEnhanceSelects = (root = document) =>
                root.querySelectorAll('select:not([data-native])').forEach(enhance);
        })();
