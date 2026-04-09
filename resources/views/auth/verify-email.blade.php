<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — Grab Maps Admin</title>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --grab-green: #00B14F;
            --grab-dark: #008b3d;
            --success: #10b981;
        }

        * { box-sizing: border-box; }

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

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at center, transparent 0%, rgba(0, 0, 0, 0.4) 100%);
            pointer-events: none;
        }

        .verify-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            max-width: 460px;
            width: 100%;
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .verify-header {
            background: linear-gradient(135deg, #00B14F 0%, #008b3d 100%);
            color: #fff;
            padding: 36px 32px 28px;
            position: relative;
            overflow: hidden;
        }

        .verify-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .verify-icon {
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
            animation: pulse 2.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(255, 255, 255, 0); }
        }

        .verify-header h4 {
            font-weight: 800;
            margin: 0;
            font-size: 1.4rem;
            position: relative;
            z-index: 1;
        }

        .verify-header small {
            opacity: 0.92;
            font-size: 0.82rem;
            display: block;
            margin-top: 4px;
            position: relative;
            z-index: 1;
        }

        .verify-body {
            padding: 30px 36px 32px;
        }

        .verify-message {
            color: #4b5563;
            font-size: 0.88rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .email-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 18px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .email-pill i { color: var(--grab-green); }

        .alert-success-pill {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #6ee7b7;
            color: #065f46;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success-pill i {
            font-size: 1.1rem;
            color: var(--success);
        }

        .btn-grab-modern {
            background: linear-gradient(135deg, #00B14F 0%, #008b3d 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 13px;
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

        .btn-grab-modern:hover::before { left: 100%; }

        .btn-grab-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 177, 79, 0.4);
        }

        .btn-secondary-link {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            padding: 8px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .btn-secondary-link:hover {
            color: var(--grab-green);
        }

        .help-box {
            background: #f9fafb;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 18px;
            text-align: left;
            font-size: 0.76rem;
            color: #6b7280;
            line-height: 1.6;
            border: 1px solid #e5e7eb;
        }

        .help-box .help-title {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .help-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .help-box li {
            margin-bottom: 3px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 18px 0;
            color: #d1d5db;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="verify-card">
        <div class="verify-header">
            <div class="verify-icon">
                <i class="bi bi-envelope-check-fill"></i>
            </div>
            <h4>Verify Your Email</h4>
            <small>One more step to access your account</small>
        </div>
        <div class="verify-body">
            @if (session('resent'))
                <div class="alert-success-pill">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Verification link has been re-sent to your email</span>
                </div>
            @endif

            <p class="verify-message">
                We've sent a verification link to:
            </p>
            <div class="email-pill">
                <i class="bi bi-envelope-fill"></i>
                <span>{{ auth()->user()->email }}</span>
            </div>
            <p class="verify-message">
                Click the link in the email to activate your account and access the admin dashboard.
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-grab-modern">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Resend Verification Email</span>
                </button>
            </form>

            <div class="divider">OR</div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-secondary-link">
                    <i class="bi bi-box-arrow-left me-1"></i> Log out and use another account
                </button>
            </form>

            <div class="help-box">
                <div class="help-title">
                    <i class="bi bi-info-circle-fill" style="color:#00B14F;"></i> Didn't receive the email?
                </div>
                <ul>
                    <li>Check your spam or junk folder</li>
                    <li>Make sure <b>{{ auth()->user()->email }}</b> is correct</li>
                    <li>Wait a few minutes — emails can be delayed</li>
                    <li>Click <b>Resend</b> button above to send a new link</li>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>
