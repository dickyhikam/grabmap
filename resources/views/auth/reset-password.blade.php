<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Reset Password — Grab Maps Admin</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
@include('auth.partials.styles')
    </style>
</head>

<body>
    @if ($errors->any())
    <div class="toast-alert" id="errorToast">
        <div class="toast-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="toast-content">
            <div class="toast-title">Gagal</div>
            <div class="toast-msg">{{ $errors->first() }}</div>
        </div>
        <button class="toast-close" onclick="document.getElementById('errorToast').classList.remove('show')">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    @endif

    <div class="auth-card">
        <div class="auth-header">
            <img src="{{ asset('logo.png') }}" alt="Grab">
            <h4>Buat Password Baru</h4>
            <small>Masukkan password baru untuk akun kamu</small>
        </div>
        <div class="auth-body">
            <div class="domain-badge">
                <div class="badge-icon"><i class="bi bi-shield-lock"></i></div>
                <span>Minimal <b>8 karakter</b>, mengandung huruf dan angka</span>
            </div>

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" name="email" class="form-control-modern"
                            value="{{ old('email', $email) }}" {{ $email ? 'readonly' : 'required' }}
                            placeholder="you@grabtaxi.com">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Baru <span class="required">*</span></label>
                    <div class="input-wrap has-toggle" id="passwordWrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password" class="form-control-modern" id="passwordInput"
                            placeholder="••••••••" required autofocus>
                        <button type="button" class="password-toggle" data-target="passwordInput" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
                    <div class="input-wrap has-toggle" id="passwordConfirmWrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password_confirmation" class="form-control-modern" id="passwordConfirmInput"
                            placeholder="••••••••" required>
                        <button type="button" class="password-toggle" data-target="passwordConfirmInput" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-grab-modern">
                    <i class="bi bi-check2-circle"></i>
                    <span>Simpan Password Baru</span>
                </button>
            </form>

            <div class="auth-footer">
                <a href="{{ route('login') }}">Kembali ke Login</a>
            </div>
        </div>
    </div>

    <script>
        const toast = document.getElementById('errorToast');
        if (toast) {
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => toast.classList.remove('show'), 6000);
        }

        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                const icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'bi bi-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'bi bi-eye';
                }
            });
        });
    </script>
</body>

</html>
