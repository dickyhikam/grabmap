<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isReset ? 'Password Reset' : ($isSend ? 'Your Account Credentials' : 'Account Created') }}</title>
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

            .cta-btn {
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
                        <td class="email-header" style="background:linear-gradient(135deg,{{ $isReset ? '#2563eb 0%,#1d4ed8' : '#00B14F 0%,#008b3d' }} 100%);padding:42px 36px 36px;text-align:center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" style="padding-bottom:14px;">
                                        <div style="display:inline-block;width:72px;height:72px;background:rgba(255,255,255,0.18);border-radius:20px;line-height:72px;text-align:center;">
                                            @if($isReset)
                                            <span style="font-size:36px;color:#ffffff;line-height:72px;">🔑</span>
                                            @elseif($isSend)
                                            <span style="font-size:36px;color:#ffffff;line-height:72px;">📨</span>
                                            @else
                                            <span style="font-size:36px;color:#ffffff;line-height:72px;">🎉</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px;">
                                @if($isReset)
                                Password Reset
                                @elseif($isSend)
                                Your Account Credentials
                                @else
                                Welcome to Grab Maps!
                                @endif
                            </h1>
                            <p style="color:rgba(255,255,255,0.92);margin:8px 0 0;font-size:14px;">
                                @if($isReset)
                                Your password has been updated
                                @elseif($isSend)
                                Login details shared by your administrator
                                @else
                                Your admin account is ready
                                @endif
                            </p>
                        </td>
                    </tr>

                    <!-- BODY -->
                    <tr>
                        <td class="email-body" style="padding:38px 42px 36px;color:#1f2937;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
                                Hi <b style="color:#00B14F;">{{ $recipientEmail }}</b>,
                            </p>
                            <p style="margin:0 0 22px;font-size:15px;line-height:1.65;color:#4b5563;">
                                @if($isReset)
                                Your password has been reset by <b>{{ $adminName }}</b>. Use the credentials below to sign in.
                                @elseif($isSend)
                                <b>{{ $adminName }}</b> has shared your Grab Maps account credentials with you.
                                @else
                                An admin account has been created for you by <b>{{ $adminName }}</b>. You can now access the Grab Maps Admin Dashboard.
                                @endif
                            </p>

                            <!-- Credentials Card -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <p style="margin:0 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#9ca3af;font-weight:700;">
                                            Your Login Details
                                        </p>

                                        <!-- Email -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:14px;">
                                            <tr>
                                                <td width="32" style="vertical-align:top;padding-top:2px;">
                                                    <div style="width:28px;height:28px;background:#dcfce7;border-radius:8px;text-align:center;line-height:28px;font-size:13px;">📧</div>
                                                </td>
                                                <td style="padding-left:12px;">
                                                    <p style="margin:0;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Email</p>
                                                    <p style="margin:2px 0 0;font-size:16px;font-weight:700;color:#1f2937;letter-spacing:-0.3px;">{{ $userEmail }}</p>
                                                </td>
                                            </tr>
                                        </table>

                                        @if($password)
                                        <!-- Divider -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding:12px 0;">
                                                    <div style="border-top:1px dashed #e5e7eb;"></div>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Password -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td width="32" style="vertical-align:top;padding-top:2px;">
                                                    <div style="width:28px;height:28px;background:#fef3c7;border-radius:8px;text-align:center;line-height:28px;font-size:13px;">🔑</div>
                                                </td>
                                                <td style="padding-left:12px;">
                                                    <p style="margin:0;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Password</p>
                                                    <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#1f2937;font-family:'Courier New',monospace;letter-spacing:1px;background:#fffbeb;padding:8px 12px;border-radius:8px;border:1px solid #fde68a;display:inline-block;">{{ $password }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                        @else
                                        <!-- No password included -->
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding:12px 0;">
                                                    <div style="border-top:1px dashed #e5e7eb;"></div>
                                                </td>
                                            </tr>
                                        </table>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td width="32" style="vertical-align:top;padding-top:2px;">
                                                    <div style="width:28px;height:28px;background:#f0f2f5;border-radius:8px;text-align:center;line-height:28px;font-size:13px;">🔒</div>
                                                </td>
                                                <td style="padding-left:12px;">
                                                    <p style="margin:0;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Password</p>
                                                    <p style="margin:2px 0 0;font-size:13px;color:#6b7280;font-style:italic;">Use your existing password</p>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:4px 0 24px;">
                                        <a href="{{ $loginUrl }}" class="cta-btn"
                                            style="display:inline-block;background:linear-gradient(135deg,#00B14F 0%,#008b3d 100%);color:#ffffff;text-decoration:none;padding:15px 40px;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.2px;box-shadow:0 6px 20px rgba(0,177,79,0.35);">
                                            → Sign In to Admin Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            @if($password)
                            <!-- Security warning -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;margin-bottom:24px; display:none;">
                                <tr>
                                    <td style="padding:14px 18px;font-size:13px;color:#991b1b;line-height:1.5;">
                                        <b>⚠️ Security Notice</b><br>
                                        <span style="color:#b91c1c;">Please change your password immediately after signing in. Do not share these credentials with anyone.</span>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Login URL fallback -->
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">
                                If the button doesn't work, open this URL in your browser:
                            </p>
                            <p style="margin:0 0 20px;padding:12px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:11px;color:#4b5563;word-break:break-all;font-family:'Courier New',monospace;line-height:1.5;">
                                {{ $loginUrl }}
                            </p>

                            <!-- Divider -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="border-top:1px solid #e5e7eb;padding-top:18px;">
                                        <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.6;">
                                            <b style="color:#6b7280;">Didn't expect this?</b> If you believe this was sent in error, please contact your administrator or ignore this email.
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