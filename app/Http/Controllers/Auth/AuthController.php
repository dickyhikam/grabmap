<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const ALLOWED_DOMAIN = 'grabtaxi.com';
    private const MAX_ATTEMPTS   = 5;
    private const DECAY_SECONDS  = 900; // 15 minutes

    // ─── Helpers ──────────────────────────────────────

    private function throttleKey(string $email): string
    {
        return 'login-attempts|' . strtolower(trim($email));
    }

    private function logAuth(Request $request, string $action, string $status, ?int $userId = null, ?string $email = null, ?string $failedReason = null): void
    {
        try {
            AuthLog::create([
                'user_id'       => $userId,
                'email'         => $email ?? strtolower(trim((string) $request->input('email', ''))),
                'action'        => $action,
                'status'        => $status,
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
                'failed_reason' => $failedReason,
            ]);
        } catch (\Throwable $e) {
            // Auth logging should never break the auth flow
            \Illuminate\Support\Facades\Log::warning('AuthLog write failed: ' . $e->getMessage());
        }
    }

    // ─── Login ────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email', "ends_with:@" . self::ALLOWED_DOMAIN],
            'password' => ['required'],
        ], [
            'email.ends_with' => 'Email must use @' . self::ALLOWED_DOMAIN . ' domain',
        ]);

        $throttleKey = $this->throttleKey($credentials['email']);

        // Check if locked out
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->logAuth($request, AuthLog::ACTION_LOGIN, AuthLog::STATUS_FAIL,
                null, $credentials['email'], 'rate_limited');

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Too many login attempts.'])
                ->with('lockoutSeconds', $seconds)
                ->with('remainingAttempts', 0);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $this->logAuth($request, AuthLog::ACTION_LOGIN, AuthLog::STATUS_SUCCESS,
                Auth::id(), $credentials['email']);

            return redirect()->intended(route('admin.dashboard'));
        }

        // Failed — increment attempts
        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
        $remaining = RateLimiter::remaining($throttleKey, self::MAX_ATTEMPTS);

        $this->logAuth($request, AuthLog::ACTION_LOGIN, AuthLog::STATUS_FAIL,
            null, $credentials['email'], 'invalid_credentials');

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Invalid email or password.'])
            ->with('remainingAttempts', $remaining);
    }

    // ─── Register ─────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                "ends_with:@" . self::ALLOWED_DOMAIN,
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.ends_with' => 'Only @' . self::ALLOWED_DOMAIN . ' emails are allowed to register.',
            'email.unique' => 'This email is already registered. Please sign in instead.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);

        $this->logAuth($request, AuthLog::ACTION_REGISTER, AuthLog::STATUS_SUCCESS,
            $user->id, $data['email']);

        return redirect()->route('verification.notice');
    }

    // ─── Logout ───────────────────────────────────────

    public function logout(Request $request)
    {
        $user = $request->user();

        $this->logAuth($request, AuthLog::ACTION_LOGOUT, AuthLog::STATUS_SUCCESS,
            $user?->id, $user?->email);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ─── Email Verification ───────────────────────────

    public function showVerifyNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }
        if ($request->user()->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($request->user()));

            $this->logAuth($request, AuthLog::ACTION_EMAIL_VERIFICATION, AuthLog::STATUS_SUCCESS,
                $request->user()->id, $request->user()->email);
        }
        return redirect()->route('admin.dashboard')->with('verified', true);
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }
        $request->user()->sendEmailVerificationNotification();

        $this->logAuth($request, AuthLog::ACTION_RESEND_VERIFICATION, AuthLog::STATUS_SUCCESS,
            $request->user()->id, $request->user()->email);

        return back()->with('resent', true);
    }
}
