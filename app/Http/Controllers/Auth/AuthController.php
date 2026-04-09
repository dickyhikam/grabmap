<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Allowed email domain for registration & login.
     */
    private const ALLOWED_DOMAIN = 'grabtaxi.com';

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Normalize email to lowercase so login matches the stored (lowercase) email
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email', "ends_with:@" . self::ALLOWED_DOMAIN],
            'password' => ['required'],
        ], [
            'email.ends_with' => 'Email must use @' . self::ALLOWED_DOMAIN . ' domain',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Invalid email or password.']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Normalize email to lowercase BEFORE validation so unique check is case-insensitive
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
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        // Fire Registered event → triggers email verification notification
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Show the email verification notice page.
     */
    public function showVerifyNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.verify-email');
    }

    /**
     * Handle the email verification link click.
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }
        if ($request->user()->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($request->user()));
        }
        return redirect()->route('admin.dashboard')->with('verified', true);
    }

    /**
     * Resend the email verification notification.
     */
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('admin.dashboard');
        }
        $request->user()->sendEmailVerificationNotification();
        return back()->with('resent', true);
    }
}
