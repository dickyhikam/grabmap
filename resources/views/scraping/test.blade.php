<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Mini Paste Tanpa DB</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 900px;
            margin: 20px auto;
        }

        textarea {
            width: 100%;
            height: 150px;
            font-family: monospace;
        }

        pre {
            background: #f4f4f4;
            padding: 10px;
            overflow-x: auto;
        }

        .snippet {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
        }

        .meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        button {
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <h1>Mini Paste (Array JS + localStorage)</h1>

    <h2>Buat Snippet Baru</h2>
    <form id="form-snippet">
        <label>Judul (opsional):</label><br>
        <input type="text" id="title" style="width:100%; margin-bottom:8px;"><br>

        <label>Code:</label><br>
        <textarea id="code" required></textarea><br>

        <button type="submit">Simpan</button>
    </form>

    <h2>Snippet Kamu</h2>
    <div id="list"></div>

    <script>
        // bikin / ambil deviceId dulu
        function getOrCreateDeviceId() {
            let id = localStorage.getItem('device_id');
            if (!id) {
                if (crypto.randomUUID) {
                    id = crypto.randomUUID();
                } else {
                    id = 'dev-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
                }
                localStorage.setItem('device_id', id);
            }
            return id;
        }

        const deviceId = getOrCreateDeviceId();

        // STORAGE_KEY jadi unik per browser
        const STORAGE_KEY = `snippets_${deviceId}`;
        console.log(STORAGE_KEY);


        let snippets = [];

        function escapeHtml(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function loadFromStorage() {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            try {
                snippets = JSON.parse(raw);
            } catch (e) {
                snippets = [];
            }
        }

        function saveToStorage() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(snippets));
        }

        function renderSnippets() {
            const list = document.getElementById('list');
            list.innerHTML = '';

            if (!snippets.length) {
                list.innerHTML = '<p>Belum ada snippet tersimpan.</p>';
                return;
            }

            for (const s of snippets) {
                const div = document.createElement('div');
                div.className = 'snippet';
                div.innerHTML = `
                    <div class="meta">
                        <strong>${s.title || '(tanpa judul)'}</strong><br>
                        <small>Dibuat: ${new Date(s.createdAt).toLocaleString()}</small>
                    </div>
                    <pre>${escapeHtml(s.code)}</pre>
                    <button data-id="${s.id}" class="btn-hapus">Hapus</button>`;
                list.appendChild(div);
            }

            // tombol hapus
            document.querySelectorAll('.btn-hapus').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-id');
                    snippets = snippets.filter(s => String(s.id) !== String(id));
                    saveToStorage();
                    renderSnippets();
                });
            });
        }

        document.getElementById('form-snippet').addEventListener('submit', (e) => {
            e.preventDefault();

            const title = document.getElementById('title').value;
            const code = document.getElementById('code').value;

            if (!code.trim()) {
                alert('Code tidak boleh kosong');
                return;
            }

            const snippet = {
                id: Date.now(), // ID sederhana
                title,
                code,
                createdAt: new Date().toISOString()
            };

            snippets.unshift(snippet);
            saveToStorage();
            renderSnippets();

            document.getElementById('title').value = '';
            document.getElementById('code').value = '';
        });

        loadFromStorage();
        renderSnippets();
    </script>
</body>

</html>