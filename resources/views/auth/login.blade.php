<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login — Grab Maps Admin</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
@include('auth.partials.styles')
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
            @elseif (session('remainingAttempts') !== null && session('remainingAttempts') < 5 && session('remainingAttempts')> 0)
                @php $rem = session('remainingAttempts'); $used = 5 - $rem; @endphp
                <div class="attempts-warning">
                    <div class="aw-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div><b>{{ $rem }}</b> attempt{{ $rem !== 1 ? 's' : '' }} remaining before lockout</div>
                        <div class="attempts-dots">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="attempts-dot {{ $i < $used ? 'used' : 'remaining' }}">
                        </div>
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

            <div style="text-align:right; margin:-4px 0 14px;">
                <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
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

        <div class="auth-footer" hidden>
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