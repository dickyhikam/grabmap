        /* ---------- Dropdown pengganti <select> bawaan ----------
           Select aslinya tetap ada di DOM (disembunyikan) supaya form submit dan
           event change yang sudah dipakai halaman tetap bekerja. */
        .gm-select { position: relative; width: 100%; }

        .gm-select-native {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }

        .gm-select-btn {
            width: 100%;
            height: 42px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink);
            font-size: 0.82rem;
            font-weight: 500;
            text-align: left;
            padding: 0 34px 0 38px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .gm-select-btn:hover { border-color: var(--muted); }

        .gm-select.open > .gm-select-btn,
        .gm-select-btn:focus-visible {
            border-color: var(--green);
            background: var(--card);
            box-shadow: 0 0 0 4px var(--green-soft);
            outline: none;
        }

        .gm-select-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .gm-select-caret {
            position: absolute;
            right: 14px;
            top: 50%;
            font-size: 0.6rem;
            color: var(--muted);
            transform: translateY(-50%);
            transition: transform 0.28s cubic-bezier(0.34, 1.4, 0.5, 1);
            pointer-events: none;
        }

        .gm-select.open .gm-select-caret { transform: translateY(-50%) rotate(180deg); }

        .gm-select-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 100%;
            max-height: 274px;
            overflow-y: auto;
            background: var(--card);
            border-radius: 16px;
            box-shadow: var(--shadow-pop);
            padding: 6px;
            z-index: 1150;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px) scale(0.98);
            transform-origin: top center;
            transition:
                opacity 0.16s ease,
                transform 0.26s cubic-bezier(0.34, 1.4, 0.5, 1),
                visibility 0.16s;
            scrollbar-width: thin;
        }

        .gm-select.open .gm-select-panel { opacity: 1; visibility: visible; transform: none; }
        .gm-select.drop-up .gm-select-panel { top: auto; bottom: calc(100% + 8px); transform-origin: bottom center; }

        .gm-select-opt {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 9px 12px;
            border: none;
            background: none;
            border-radius: 11px;
            font-size: 0.82rem;
            color: var(--ink);
            text-align: left;
            cursor: pointer;
            animation: gmOptIn 0.24s cubic-bezier(0.34, 1.4, 0.5, 1) backwards;
        }

        @keyframes gmOptIn {
            from { opacity: 0; transform: translateY(5px); }
            to   { opacity: 1; transform: none; }
        }

        .gm-select-group {
            padding: 8px 12px 4px;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--faint);
        }
        .gm-select-group + .gm-select-opt { margin-top: 0; }

        .gm-select-opt:hover, .gm-select-opt.cursor { background: var(--surface); }
        .gm-select-opt.sel { color: var(--green-text); font-weight: 700; }
        .gm-select-opt[disabled] { opacity: 0.45; cursor: default; }
        .gm-select-opt .tick { width: 14px; flex-shrink: 0; font-size: 0.72rem; opacity: 0; }
        .gm-select-opt.sel .tick { opacity: 1; }
        .gm-select-opt .txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        @media (prefers-reduced-motion: reduce) {
            .gm-select-panel { transition: opacity 0.12s ease, visibility 0.12s; transform: none; }
            .gm-select-opt { animation: none; }
            .gm-select-caret { transition: none; }
        }
