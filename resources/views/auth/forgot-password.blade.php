<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Lupa Password — Grab Maps Admin</title>
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
            <h4>Lupa Password?</h4>
            <small>Masukkan email akunmu untuk menerima tautan reset</small>
        </div>
        <div class="auth-body">
            @if (session('status'))
            <div class="domain-badge" style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);">
                <div class="badge-icon"><i class="bi bi-check-lg"></i></div>
                <span>{{ session('status') }}</span>
            </div>
            @else
            <div class="domain-badge">
                <div class="badge-icon"><i class="bi bi-envelope-paper"></i></div>
                <span>Kami akan kirim tautan untuk membuat <b>password baru</b> ke email kamu</span>
            </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <div class="input-wrap" id="emailWrap">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" name="email" class="form-control-modern" id="emailInput"
                            placeholder="you@grabtaxi.com" value="{{ old('email') }}" required autofocus>
                        <i class="bi bi-check-circle-fill input-validator"></i>
                    </div>
                </div>

                <button type="submit" class="btn-grab-modern">
                    <i class="bi bi-send-fill"></i>
                    <span>Kirim Tautan Reset</span>
                </button>
            </form>

            <div class="auth-footer">
                Ingat password kamu? <a href="{{ route('login') }}">Kembali ke Login</a>
            </div>
        </div>
    </div>

    <script>
        const toast = document.getElementById('errorToast');
        if (toast) {
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => toast.classList.remove('show'), 6000);
        }
    </script>
</body>

</html>
