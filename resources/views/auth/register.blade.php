<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Register — Grab Maps Admin</title>
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
            padding: 16px;
            position: relative;
            overflow-x: hidden;
            background:
                linear-gradient(135deg, rgba(0, 60, 25, 0.78) 0%, rgba(0, 139, 61, 0.55) 50%, rgba(0, 30, 15, 0.85) 100%),
                url('{{ asset('images/auth-bg.jpg') }}') center/cover no-repeat;
            background-attachment: fixed;
        }

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
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
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
            padding: 20px 28px 18px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .auth-header img {
            height: 28px;
            margin-bottom: 6px;
            filter: brightness(0) invert(1);
            position: relative;
            z-index: 1;
        }

        .auth-header h4 {
            font-weight: 800;
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: -0.3px;
            position: relative;
            z-index: 1;
        }

        .auth-header small {
            opacity: 0.92;
            font-size: 0.72rem;
            display: block;
            margin-top: 2px;
            position: relative;
            z-index: 1;
        }

        .auth-body {
            padding: 20px 28px 24px;
        }

        .domain-badge {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 0.7rem;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
        }

        .domain-badge .badge-icon {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: #00B14F;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.7rem;
            color: #374151;
            margin-bottom: 5px;
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
            padding: 10px 14px 10px 42px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.85rem;
            font-family: inherit;
            background: #fff;
            transition: all 0.2s;
            color: #1f2937;
        }

        .input-wrap .input-icon {
            left: 14px;
            font-size: 0.92rem;
        }

        .input-wrap .input-validator {
            right: 14px;
            font-size: 1rem;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--grab-green);
            box-shadow: 0 0 0 4px rgba(0, 177, 79, 0.1);
        }

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
            padding-right: 42px;
        }

        .password-toggle {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: 0.95rem;
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
            margin-top: 5px;
            color: var(--error);
            font-size: 0.72rem;
            font-weight: 500;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.25s;
        }

        .input-wrap.invalid + .field-error {
            opacity: 1;
            max-height: 80px;
        }

        .field-hint {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 5px;
            color: #9ca3af;
            font-size: 0.72rem;
        }

        /* Compact password strength meter */
        .strength-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }

        .strength-meter {
            flex: 1;
            display: flex;
            gap: 3px;
            height: 4px;
        }

        .strength-bar {
            flex: 1;
            background: #e5e7eb;
            border-radius: 2px;
            transition: background 0.3s;
        }

        .strength-bar.active-weak {
            background: var(--error);
        }

        .strength-bar.active-medium {
            background: var(--warning);
        }

        .strength-bar.active-strong {
            background: var(--success);
        }

        .strength-text {
            font-size: 0.65rem;
            font-weight: 700;
            color: #9ca3af;
            min-width: 60px;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .strength-text.weak {
            color: var(--error);
        }

        .strength-text.medium {
            color: var(--warning);
        }

        .strength-text.strong {
            color: var(--success);
        }

        .btn-grab-modern {
            background: linear-gradient(135deg, #00B14F 0%, #008b3d 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(0, 177, 79, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 4px;
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

        .btn-grab-modern:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .auth-footer {
            text-align: center;
            margin-top: 14px;
            font-size: 0.78rem;
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

        /* Toast Alert */
        .toast-alert {
            position: fixed;
            top: calc(24px + env(safe-area-inset-top, 0px));
            left: 50%;
            transform: translateX(-50%) translateY(-200%);
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 14px 20px 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 340px;
            max-width: calc(100vw - 24px);
            z-index: 9999;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            border-left: 4px solid var(--error);
        }

        .toast-alert.show {
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 640px) {
            .toast-alert {
                top: calc(12px + env(safe-area-inset-top, 0px));
                min-width: 0;
                width: calc(100vw - 24px);
                padding: 12px 14px;
            }
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
            margin-bottom: 4px;
        }

        .toast-alert .toast-msg {
            font-size: 0.76rem;
            color: #6b7280;
            line-height: 1.5;
        }

        .toast-alert .toast-msg ul {
            margin: 4px 0 0;
            padding-left: 16px;
        }

        .toast-alert .toast-close {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
        }
    </style>
</head>

<body>
    @if ($errors->any())
        <div class="toast-alert" id="errorToast">
            <div class="toast-icon">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">Registration Failed</div>
                <div class="toast-msg">
                    <ul>
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button class="toast-close" onclick="document.getElementById('errorToast').classList.remove('show')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    @endif

    <div class="auth-card">
        <div class="auth-header">
            <img src="{{ asset('logo.png') }}" alt="Grab">
            <h4>Create Account</h4>
            <small>Register with your @grabtaxi.com email</small>
        </div>
        <div class="auth-body">
            <div class="domain-badge">
                <div class="badge-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <span>Only employees with <b>@grabtaxi.com</b> email can register</span>
            </div>

            <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <div class="input-wrap" id="nameWrap">
                        <i class="bi bi-person-fill input-icon"></i>
                        <input type="text" name="name" class="form-control-modern" id="nameInput"
                            placeholder="John Doe" value="{{ old('name') }}" required>
                        <i class="bi bi-check-circle-fill input-validator"></i>
                    </div>
                    <div class="field-error">
                        <i class="bi bi-info-circle-fill"></i>
                        <span id="nameErrMsg">Name is required</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <div class="input-wrap" id="emailWrap">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" name="email" class="form-control-modern" id="emailInput"
                            placeholder="you@grabtaxi.com" value="{{ old('email') }}" required>
                        <i class="bi bi-check-circle-fill input-validator"></i>
                    </div>
                    <div class="field-error">
                        <i class="bi bi-info-circle-fill"></i>
                        <span id="emailErrMsg">Email must use @grabtaxi.com domain</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <div class="input-wrap has-toggle" id="passwordWrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password" class="form-control-modern" id="passwordInput"
                            placeholder="Min 8 characters" required>
                        <button type="button" class="password-toggle" data-target="passwordInput" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="strength-row">
                        <div class="strength-meter">
                            <div class="strength-bar" id="bar1"></div>
                            <div class="strength-bar" id="bar2"></div>
                            <div class="strength-bar" id="bar3"></div>
                            <div class="strength-bar" id="bar4"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Min 8 chars</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                    <div class="input-wrap has-toggle" id="confirmWrap">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password_confirmation" class="form-control-modern" id="confirmInput"
                            placeholder="Repeat password" required>
                        <button type="button" class="password-toggle" data-target="confirmInput" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="field-error">
                        <i class="bi bi-info-circle-fill"></i>
                        <span id="confirmErrMsg">Passwords do not match</span>
                    </div>
                </div>

                <button type="submit" class="btn-grab-modern" id="submitBtn">
                    <i class="bi bi-person-plus-fill"></i>
                    <span>Create Account</span>
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-show error toast
        const toast = document.getElementById('errorToast');
        if (toast) {
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => toast.classList.remove('show'), 8000);
        }

        const nameInput = document.getElementById('nameInput');
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const confirmInput = document.getElementById('confirmInput');
        const nameWrap = document.getElementById('nameWrap');
        const emailWrap = document.getElementById('emailWrap');
        const passwordWrap = document.getElementById('passwordWrap');
        const confirmWrap = document.getElementById('confirmWrap');

        function setValid(wrap, isValid) {
            wrap.classList.remove('valid', 'invalid');
            if (isValid === true) wrap.classList.add('valid');
            else if (isValid === false) wrap.classList.add('invalid');
        }

        function validateName(showInvalid = false) {
            const val = nameInput.value.trim();
            if (!val) {
                if (showInvalid) {
                    document.getElementById('nameErrMsg').textContent = 'Name is required';
                    setValid(nameWrap, false);
                } else setValid(nameWrap, null);
                return false;
            }
            if (val.length < 2) {
                document.getElementById('nameErrMsg').textContent = 'Name must be at least 2 characters';
                setValid(nameWrap, false);
                return false;
            }
            setValid(nameWrap, true);
            return true;
        }

        function validateEmail(showInvalid = false) {
            const val = emailInput.value.trim();
            if (!val) {
                if (showInvalid) {
                    document.getElementById('emailErrMsg').textContent = 'Email is required';
                    setValid(emailWrap, false);
                } else setValid(emailWrap, null);
                return false;
            }
            const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRe.test(val)) {
                document.getElementById('emailErrMsg').textContent = 'Invalid email format';
                setValid(emailWrap, false);
                return false;
            }
            if (!val.toLowerCase().endsWith('@grabtaxi.com')) {
                document.getElementById('emailErrMsg').textContent = 'Email must use @grabtaxi.com domain';
                setValid(emailWrap, false);
                return false;
            }
            setValid(emailWrap, true);
            return true;
        }

        function checkPasswordStrength(val) {
            const checks = {
                length: val.length >= 8,
                letter: /[a-zA-Z]/.test(val),
                number: /[0-9]/.test(val),
                symbol: /[^a-zA-Z0-9]/.test(val)
            };
            const score = Object.values(checks).filter(Boolean).length;
            return { checks, score };
        }

        function updatePasswordUI() {
            const val = passwordInput.value;
            const { score } = checkPasswordStrength(val);

            const bars = ['bar1', 'bar2', 'bar3', 'bar4'].map(id => document.getElementById(id));
            bars.forEach(b => b.className = 'strength-bar');
            const strengthText = document.getElementById('strengthText');
            strengthText.className = 'strength-text';

            if (val.length === 0) {
                strengthText.textContent = 'Min 8 chars';
                return;
            }

            let level = 'weak';
            let levelText = 'Weak';
            let activeBars = 1;
            if (score >= 4) {
                level = 'strong';
                levelText = 'Very Strong';
                activeBars = 4;
            } else if (score === 3) {
                level = 'strong';
                levelText = 'Strong';
                activeBars = 3;
            } else if (score === 2) {
                level = 'medium';
                levelText = 'Medium';
                activeBars = 2;
            }

            for (let i = 0; i < activeBars; i++) {
                bars[i].classList.add(`active-${level}`);
            }
            strengthText.classList.add(level);
            strengthText.textContent = levelText;
        }

        function validatePassword(showInvalid = false) {
            const val = passwordInput.value;
            const { checks, score } = checkPasswordStrength(val);
            if (!val) {
                if (showInvalid) setValid(passwordWrap, false);
                else setValid(passwordWrap, null);
                return false;
            }
            if (!checks.length || !checks.letter || !checks.number) {
                setValid(passwordWrap, false);
                return false;
            }
            setValid(passwordWrap, true);
            return true;
        }

        function validateConfirm(showInvalid = false) {
            const val = confirmInput.value;
            const pwd = passwordInput.value;
            if (!val) {
                if (showInvalid) {
                    document.getElementById('confirmErrMsg').textContent = 'Confirm password is required';
                    setValid(confirmWrap, false);
                } else setValid(confirmWrap, null);
                return false;
            }
            if (val !== pwd) {
                document.getElementById('confirmErrMsg').textContent = 'Passwords do not match';
                setValid(confirmWrap, false);
                return false;
            }
            setValid(confirmWrap, true);
            return true;
        }

        // Bind events
        nameInput.addEventListener('blur', () => validateName(true));
        nameInput.addEventListener('input', () => {
            if (nameWrap.classList.contains('invalid')) validateName(true);
            else if (nameInput.value) validateName(false);
        });

        emailInput.addEventListener('blur', () => validateEmail(true));
        emailInput.addEventListener('input', () => {
            if (emailWrap.classList.contains('invalid')) validateEmail(true);
            else if (emailInput.value) validateEmail(false);
        });

        passwordInput.addEventListener('input', () => {
            updatePasswordUI();
            if (passwordWrap.classList.contains('invalid')) validatePassword(true);
            else if (passwordInput.value) validatePassword(false);
            // Re-validate confirm if it has value
            if (confirmInput.value) validateConfirm(false);
        });
        passwordInput.addEventListener('blur', () => validatePassword(true));

        confirmInput.addEventListener('blur', () => validateConfirm(true));
        confirmInput.addEventListener('input', () => {
            if (confirmWrap.classList.contains('invalid')) validateConfirm(true);
            else if (confirmInput.value) validateConfirm(false);
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

        // Pre-validate
        if (nameInput.value) validateName(true);
        if (emailInput.value) validateEmail(true);

        // Submit guard (prevents double-submit)
        const registerForm = document.getElementById('registerForm');
        registerForm.addEventListener('submit', (e) => {
            if (registerForm.dataset.submitted === 'true') {
                e.preventDefault();
                return;
            }
            const ok = [validateName(true), validateEmail(true), validatePassword(true), validateConfirm(true)]
                .every(v => v);
            if (!ok) {
                e.preventDefault();
                return;
            }
            registerForm.dataset.submitted = 'true';
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating account...';
        });
    </script>
</body>

</html>
