{{--
    Validasi sisi klien untuk formulir template admin — perilakunya sama dengan
    formulir Pengguna: dicek saat blur, dicek ulang tiap ketikan setelah field
    pernah salah, dan saat submit semuanya dicek sekaligus lalu fokus lompat ke
    yang pertama bermasalah.

    Ini hanya mempercepat umpan balik. Aturan yang sesungguhnya tetap di controller.

    Pemakaian: tandai formulirnya dengan data-validate, lalu beri atribut pada input:

        required                     wajib diisi
        type="email"                 format email
        maxlength="100"              panjang maksimal (browser sudah menahan ketikan)
        data-v-pattern="^[a-z0-9\-]+$"   pola yang harus cocok
        data-v-digits="12"           harus sekian digit angka
        data-v-min="1" data-v-max="1000000"   rentang angka
        data-v-number="id"           angka bergaya Indonesia ("16.500") sebelum dicek
        data-v-msg="..."             pesan untuk aturan bentuk (pola / jumlah digit)
        data-v-msg-required="..."    pesan khusus saat kolomnya kosong

    Untuk grup kotak centang, bungkus dan tandai wadahnya:
        <div data-v-group data-v-msg="...">   ... <input type="checkbox" name="x[]"> ...
--}}
@once
@push('styles')
    /* Wadah pilihan (mis. daftar layanan API key) ikut ditandai saat kosong. */
    [data-v-group].is-invalid {
        border-radius: 18px;
        box-shadow: 0 0 0 1.5px var(--danger-fg);
    }
@endpush

@push('scripts')
@php
    $vMessages = [
        'required'  => __('validate.required'),
        'email'     => __('validate.email'),
        'pattern'   => __('validate.pattern'),
        'digits'    => __('validate.digits', ['count' => ':count']),
        'number'    => __('validate.number'),
        'min'       => __('validate.min', ['min' => ':min']),
        'max'       => __('validate.max', ['max' => ':max']),
        'maxlength' => __('validate.maxlength', ['max' => ':max']),
        'pickOne'   => __('validate.pick_one'),
    ];
@endphp
<script>
    (function () {
        const MSG = @json($vMessages);

        document.querySelectorAll('form[data-validate]').forEach(setup);

        function setup(form) {
            // Validasi bawaan browser dimatikan dari JS, bukan dari markup: kalau
            // skrip ini gagal dimuat, gelembung bawaan tetap jadi jaring pengaman.
            form.setAttribute('novalidate', '');

            const fields = [...form.querySelectorAll('[required], [data-v-pattern], [data-v-digits], [data-v-min], [data-v-max], [data-v-number]')]
                .filter((el) => !el.disabled && el.type !== 'hidden');
            const groups = [...form.querySelectorAll('[data-v-group]')];

            // ---------- Menandai error di bawah field ----------
            function box(target) {
                return target.closest('.form-field') ?? target.parentElement;
            }

            function setError(target, message) {
                clearError(target);
                target.classList.add('is-invalid');

                const el = document.createElement('div');
                el.className = 'form-error';
                el.dataset.live = '1';
                el.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
                el.querySelector('span').textContent = message;
                box(target).appendChild(el);
            }

            function clearError(target) {
                target.classList.remove('is-invalid');
                box(target)?.querySelectorAll('.form-error[data-live]').forEach((el) => el.remove());
            }

            // ---------- Aturan ----------
            const fill = (text, key, value) => text.replace(':' + key, value);

            // "16.500" / "16.512,45" → 16500 / 16512.45, sepadan dengan normalisasi di controller.
            function toNumber(raw, mode) {
                let value = String(raw).trim();
                if (mode === 'id') {
                    value = value.includes(',')
                        ? value.replace(/\./g, '').replace(',', '.')
                        : (/^\d{1,3}(\.\d{3})+$/.test(value) ? value.replace(/\./g, '') : value);
                }
                return Number(value);
            }

            function check(el) {
                const value = (el.value ?? '').trim();
                // data-v-msg menjelaskan BENTUK yang benar (pola/jumlah digit), jadi
                // hanya dipakai untuk aturan itu — kolom kosong tetap berbunyi "wajib
                // diisi", bukan "formatnya belum sesuai".
                const own   = el.dataset.vMsg;

                if (el.hasAttribute('required') && !value) {
                    setError(el, el.dataset.vMsgRequired || MSG.required);
                    return false;
                }

                // Field opsional yang dibiarkan kosong tidak perlu dicek lebih jauh.
                if (!value) { clearError(el); return true; }

                if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    setError(el, MSG.email);
                    return false;
                }

                if (el.dataset.vDigits) {
                    const n = Number(el.dataset.vDigits);
                    if (!new RegExp('^\\d{' + n + '}$').test(value)) {
                        setError(el, own || fill(MSG.digits, 'count', n));
                        return false;
                    }
                }

                if (el.dataset.vPattern && !new RegExp(el.dataset.vPattern).test(value)) {
                    setError(el, own || MSG.pattern);
                    return false;
                }

                if (el.maxLength > 0 && value.length > el.maxLength) {
                    setError(el, fill(MSG.maxlength, 'max', el.maxLength));
                    return false;
                }

                if (el.dataset.vMin !== undefined || el.dataset.vMax !== undefined || el.dataset.vNumber) {
                    const num = toNumber(value, el.dataset.vNumber);

                    if (!Number.isFinite(num)) { setError(el, MSG.number); return false; }
                    if (el.dataset.vMin !== undefined && num < Number(el.dataset.vMin)) {
                        setError(el, fill(MSG.min, 'min', el.dataset.vMin)); return false;
                    }
                    if (el.dataset.vMax !== undefined && num > Number(el.dataset.vMax)) {
                        setError(el, fill(MSG.max, 'max', el.dataset.vMax)); return false;
                    }
                }

                clearError(el);
                return true;
            }

            function checkGroup(group) {
                const picked = group.querySelectorAll('input[type="checkbox"]:checked').length > 0;

                if (picked) { clearError(group); return true; }
                setError(group, group.dataset.vMsg || MSG.pickOne);
                return false;
            }

            // ---------- Pemasangan ----------
            fields.forEach((el) => {
                el.addEventListener('blur', () => check(el));
                el.addEventListener('input', () => { if (el.classList.contains('is-invalid')) check(el); });
                // Pemilih tanggal/dropdown melepas 'change', bukan 'input'.
                el.addEventListener('change', () => { if (el.classList.contains('is-invalid')) check(el); });
            });

            groups.forEach((group) => {
                group.addEventListener('change', () => { if (group.classList.contains('is-invalid')) checkGroup(group); });
            });

            form.addEventListener('submit', (e) => {
                // Semua dicek dulu supaya seluruh error tampil sekaligus, bukan satu per satu.
                const ok = [...fields.map(check), ...groups.map(checkGroup)].every(Boolean);
                if (ok) return;

                e.preventDefault();
                const first = form.querySelector('.is-invalid');
                first?.focus?.();
                first?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            });

            // Error dari server: fokuskan yang pertama supaya tidak perlu dicari.
            const fromServer = form.querySelector('.form-input.is-invalid');
            if (fromServer) fromServer.focus({ preventScroll: true });
        }
    })();
</script>
@endpush
@endonce
