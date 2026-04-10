<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Grab Maps Admin</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --grab-green: #00B14F;
            --grab-dark: #008b3d;
            --grab-light: #e6f7ed;
            --error: #ef4444;
            --error-light: #fef2f2;
            --warning: #f59e0b;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            background:
                linear-gradient(135deg, rgba(0, 60, 25, 0.78) 0%, rgba(0, 139, 61, 0.55) 50%, rgba(0, 30, 15, 0.85) 100%),
                url('{{ asset('images/auth-bg.jpg') }}') center/cover no-repeat;
            background-attachment: fixed;
        }

        /* Subtle vignette overlay for depth */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, transparent 0%, rgba(0, 0, 0, 0.4) 100%);
            pointer-events: none;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            background: linear-gradient(135deg, #00B14F 0%, #008b3d 100%);
            color: #fff;
            padding: 36px 36px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .auth-header img {
            height: 38px;
            margin-bottom: 14px;
            filter: brightness(0) invert(1);
            position: relative;
            z-index: 1;
        }

        .auth-header h4 {
            font-weight: 800;
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: -0.3px;
            position: relative;
            z-index: 1;
        }

        .auth-header small {
            opacity: 0.92;
            font-size: 0.8rem;
            display: block;
            margin-top: 4px;
            position: relative;
            z-index: 1;
        }

        .auth-body {
            padding: 32px 36px 36px;
        }

        .domain-badge {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.75rem;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
        }

        .domain-badge .badge-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #00B14F;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.78rem;
            color: #374151;
            margin-bottom: 7px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-label .required {
            color: var(--error);
            margin-left: 3px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s;
            z-index: 2;
        }

        .input-wrap .input-validator {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            opacity: 0;
            transition: all 0.2s;
            z-index: 2;
        }

        .input-wrap.valid .input-validator {
            opacity: 1;
            color: var(--success);
        }

        .input-wrap.invalid .input-validator {
            opacity: 1;
            color: var(--error);
        }

        .form-control-modern {
            width: 100%;
            padding: 14px 16px 14px 46px;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #fff;
            transition: all 0.2s;
            color: #1f2937;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--grab-green);
            box-shadow: 0 0 0 4px rgba(0, 177, 79, 0.1);
        }

        .form-control-modern:focus + .input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--grab-green);
        }

        .input-wrap.invalid .form-control-modern {
            border-color: var(--error);
            background: #fffbfb;
        }

        .input-wrap.valid .form-control-modern {
            border-color: var(--success);
        }

        .input-wrap.invalid .form-control-modern:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        /* Password show/hide toggle */
        .input-wrap.has-toggle .form-control-modern {
            padding-right: 46px;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            z-index: 3;
        }

        .password-toggle:hover {
            color: var(--grab-green);
            background: rgba(0, 177, 79, 0.08);
        }

        .password-toggle:focus {
            outline: none;
        }

        .field-error {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-top: 6px;
            color: var(--error);
            font-size: 0.74rem;
            font-weight: 500;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.25s;
        }

        .input-wrap.invalid + .field-error {
            opacity: 1;
            max-height: 80px;
            margin-top: 6px;
        }

        .field-error i {
            margin-top: 1px;
        }

        .form-check-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .form-check-modern label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.82rem;
            color: #6b7280;
            user-select: none;
        }

        .form-check-modern input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--grab-green);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.78rem;
            color: var(--grab-green);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .btn-grab-modern {
            background: linear-gradient(135deg, #00B14F 0%, #008b3d 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 0.92rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(0, 177, 79, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-grab-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-grab-modern:hover::before {
            left: 100%;
        }

        .btn-grab-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 177, 79, 0.4);
        }

        .btn-grab-modern:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 0.84rem;
            color: #6b7280;
        }

        .auth-footer a {
            color: var(--grab-green);
            font-weight: 700;
            text-decoration: none;
            position: relative;
        }

        .auth-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--grab-green);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s;
        }

        .auth-footer a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* Modern Toast Alert */
        .toast-alert {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(-120%);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 14px 20px 14px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            max-width: 90vw;
            z-index: 9999;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border-left: 4px solid var(--error);
        }

        .toast-alert.show {
            transform: translateX(-50%) translateY(0);
        }

        .toast-alert .toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--error-light);
            color: var(--error);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .toast-alert .toast-content {
            flex: 1;
            min-width: 0;
        }

        .toast-alert .toast-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .toast-alert .toast-msg {
            font-size: 0.78rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .toast-alert .toast-close {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            font-size: 1rem;
            transition: color 0.2s;
        }

        .toast-alert .toast-close:hover {
            color: #1f2937;
        }

        /* Lockout banner */
        .lockout-banner {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1.5px solid #fecaca;
            border-radius: 14px;
            padding: 16px 18px;
            font-size: 0.82rem;
            color: #991b1b;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            animation: slideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .lockout-banner .lockout-icon {
            width: 42px;
            height: 42px;
            background: #fee2e2;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            color: #dc2626;
        }

        .lockout-banner .lockout-timer {
            font-size: 1.4rem;
            font-weight: 800;
            color: #dc2626;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.5px;
            margin-left: auto;
            min-width: 50px;
            text-align: right;
        }

        .lockout-banner .lockout-text {
            flex: 1;
            font-weight: 500;
            line-height: 1.4;
        }

        .lockout-banner .lockout-text b {
            color: #dc2626;
        }

        /* Attempts warning */
        .attempts-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1.5px solid #fde68a;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 0.8rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        .attempts-warning .aw-icon {
            width: 36px;
            height: 36px;
            background: #fef3c7;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            color: #d97706;
        }

        .attempts-dots {
            display: flex;
            gap: 5px;
            margin-top: 6px;
        }

        .attempts-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #fde68a;
            transition: background 0.2s;
        }

        .attempts-dot.used {
            background: #dc2626;
        }

        .attempts-dot.remaining {
            background: #16a34a;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <!-- Toast Alert -->
    @if ($errors->any())
        <div class="toast-alert" id="errorToast">
            <div class="toast-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">{{ session('lockoutSeconds') ? 'Account Locked' : 'Login Failed' }}</div>
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
            <h4>Welcome Back</h4>
            <small>Sign in to your Grab Maps account</small>
        </div>
        <div class="auth-body">
            <div class="domain-badge">
                <div class="badge-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <span>Only <b>@grabtaxi.com</b> accounts are allowed to sign in</span>
            </div>

            {{-- Lockout countdown --}}
            @if (session('lockoutSeconds'))
                <div class="lockout-banner" id="lockoutBanner">
                    <div class="lockout-icon">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div class="lockout-text">
                        <b>Account locked</b><br>
                        Too many failed attempts. Try again in:
                    </div>
                    <div class="lockout-timer" id="lockoutTimer">--:--</div>
                </div>
            @elseif (session('remainingAttempts') !== null && session('remainingAttempts') < 5 && session('remainingAttempts') > 0)
                @php $rem = session('remainingAttempts'); $used = 5 - $rem; @endphp
                <div class="attempts-warning">
                    <div class="aw-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div><b>{{ $rem }}</b> attempt{{ $rem !== 1 ? 's' : '' }} remaining before lockout</div>
                        <div class="attempts-dots">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="attempts-dot {{ $i < $used ? 'used' : 'remaining' }}"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <div class="input-wrap" id="emailWrap">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" name="email" class="form-control-modern" id="emailInput"
                            placeholder="you@grabtaxi.com" value="{{ old('email') }}" required>
                        <i class="bi bi-check-circle-fill input-validator"></i>
                    </div>
                    <div class="field-error" id="emailError">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Email must use @grabtaxi.com domain</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <div class="input-wrap has-toggle" id="passwordWrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password" class="form-control-modern" id="passwordInput"
                            placeholder="••••••••" required>
                        <button type="button" class="password-toggle" data-target="passwordInput" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="field-error" id="passwordError">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Password is required</span>
                    </div>
                </div>

                <div class="form-check-modern">
                    <label>
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="btn-grab-modern" id="submitBtn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Sign In</span>
                </button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="{{ route('register') }}">Create one</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-show error toast & auto-hide after 6s
        const toast = document.getElementById('errorToast');
        if (toast) {
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => toast.classList.remove('show'), 6000);
        }

        // Real-time validation
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const emailWrap = document.getElementById('emailWrap');
        const passwordWrap = document.getElementById('passwordWrap');
        const emailError = document.getElementById('emailError').querySelector('span');

        function validateEmail(showInvalid = false) {
            const val = emailInput.value.trim();
            emailWrap.classList.remove('valid', 'invalid');
            if (!val) {
                if (showInvalid) {
                    emailError.textContent = 'Email is required';
                    emailWrap.classList.add('invalid');
                }
                return false;
            }
            const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRe.test(val)) {
                emailError.textContent = 'Invalid email format';
                emailWrap.classList.add('invalid');
                return false;
            }
            if (!val.toLowerCase().endsWith('@grabtaxi.com')) {
                emailError.textContent = 'Email must use @grabtaxi.com domain';
                emailWrap.classList.add('invalid');
                return false;
            }
            emailWrap.classList.add('valid');
            return true;
        }

        function validatePassword(showInvalid = false) {
            const val = passwordInput.value;
            passwordWrap.classList.remove('valid', 'invalid');
            if (!val) {
                if (showInvalid) passwordWrap.classList.add('invalid');
                return false;
            }
            passwordWrap.classList.add('valid');
            return true;
        }

        emailInput.addEventListener('blur', () => validateEmail(true));
        emailInput.addEventListener('input', () => {
            if (emailWrap.classList.contains('invalid')) validateEmail(true);
            else if (emailInput.value) validateEmail(false);
        });
        passwordInput.addEventListener('blur', () => validatePassword(true));
        passwordInput.addEventListener('input', () => {
            if (passwordWrap.classList.contains('invalid')) validatePassword(true);
            else if (passwordInput.value) validatePassword(false);
        });

        // Password show/hide toggle
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.target;
                const input = document.getElementById(targetId);
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

        // === Lockout countdown timer ===
        const lockoutBanner = document.getElementById('lockoutBanner');
        if (lockoutBanner) {
            let secs = {{ session('lockoutSeconds', 0) }};
            const timerEl = document.getElementById('lockoutTimer');
            const submitBtn = document.getElementById('submitBtn');
            const formInputs = document.querySelectorAll('#loginForm input:not([type="hidden"])');

            // Disable everything during lockout
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Locked</span>';
            formInputs.forEach(i => i.disabled = true);

            function tick() {
                if (secs <= 0) {
                    lockoutBanner.remove();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> <span>Sign In</span>';
                    formInputs.forEach(i => i.disabled = false);
                    return;
                }
                const m = Math.floor(secs / 60);
                const s = secs % 60;
                timerEl.textContent = m + ':' + String(s).padStart(2, '0');
                secs--;
                setTimeout(tick, 1000);
            }
            tick();
        }

        // Pre-validate on load if old value exists
        if (emailInput.value) validateEmail(true);

        // Form submit guard (prevents double-submit)
        const loginForm = document.getElementById('loginForm');
        loginForm.addEventListener('submit', (e) => {
            if (loginForm.dataset.submitted === 'true') {
                e.preventDefault();
                return;
            }
            const okEmail = validateEmail(true);
            const okPwd = validatePassword(true);
            if (!okEmail || !okPwd) {
                e.preventDefault();
                return;
            }
            loginForm.dataset.submitted = 'true';
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing in...';
        });
    </script>
</body>

</html>
