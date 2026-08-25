<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0 !important;
            }

            .email-body {
                padding: 28px 22px !important;
            }

            .email-header {
                padding: 32px 22px 28px !important;
            }

            .verify-btn {
                padding: 14px 24px !important;
                font-size: 15px !important;
            }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="email-container" style="max-width:600px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.08);">

                    <!-- HEADER -->
                    <tr>
                        <td class="email-header" style="background:linear-gradient(135deg,#00B14F 0%,#008b3d 100%);padding:42px 36px 36px;text-align:center;position:relative;">
                            <!-- Icon -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" style="padding-bottom:14px;">
                                        <div style="display:inline-block;width:72px;height:72px;background:rgba(255,255,255,0.18);border-radius:20px;line-height:72px;text-align:center;">
                                            <span style="font-size:36px;color:#ffffff;line-height:72px;">✉</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:800;letter-spacing:-0.5px;">
                                Verify Your Email
                            </h1>
                            <p style="color:rgba(255,255,255,0.92);margin:8px 0 0;font-size:14px;">
                                One more step to access Grab Maps Admin
                            </p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td class="email-body" style="padding:38px 42px 36px;color:#1f2937;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#1f2937;">
                                Hi <b style="color:#00B14F;">{{ $user->name }}</b>,
                            </p>
                            <p style="margin:0 0 22px;font-size:15px;line-height:1.65;color:#4b5563;">
                                Thanks for signing up to <b>Grab Maps Admin</b>! Please verify your email address by clicking the button below to activate your account.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:8px 0 28px;">
                                        <a href="{{ $verificationUrl }}" class="verify-btn"
                                            style="display:inline-block;background:linear-gradient(135deg,#00B14F 0%,#008b3d 100%);color:#ffffff;text-decoration:none;padding:15px 40px;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.2px;box-shadow:0 6px 20px rgba(0,177,79,0.35);">
                                            ✓ Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry warning -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 18px;font-size:13px;color:#78350f;line-height:1.5;">
                                        <b>⏱ This link expires in {{ $expiresInHours }} hours</b><br>
                                        <span style="color:#92400e;">For your security, please verify before {{ now()->addHours($expiresInHours)->wib()->format('d M Y, H:i') }} (server time).</span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Alternative URL -->
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
                                If the button doesn't work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0 0 24px;padding:12px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:11px;color:#4b5563;word-break:break-all;font-family:'Courier New',monospace;line-height:1.5;">
                                {{ $verificationUrl }}
                            </p>

                            <!-- Divider -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="border-top:1px solid #e5e7eb;padding-top:22px;">
                                        <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                                            <b style="color:#6b7280;">Didn't sign up?</b> If you didn't create an account on Grab Maps Admin, you can safely ignore this email — no account will be created.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f9fafb;padding:24px 36px;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="margin:0 0 6px;font-size:13px;color:#6b7280;font-weight:600;">
                                Grab Maps Admin
                            </p>
                            <p style="margin:0;font-size:11px;color:#9ca3af;line-height:1.5;">
                                Powered by AWS Location Service · GrabMaps Southeast Asia
                            </p>
                            <p style="margin:10px 0 0;font-size:10px;color:#d1d5db;">
                                © {{ date('Y') }} Grab. This is an automated message — please do not reply.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
