<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\CredentialSendLog;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Notifications\SendCredentialsNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($request->query('verified') === 'yes') {
            $query->whereNotNull('email_verified_at');
        } elseif ($request->query('verified') === 'no') {
            $query->whereNull('email_verified_at');
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $users = $query->latest()->paginate(15)->appends($request->query());

        $stats = [
            'total'      => User::count(),
            'admins'     => User::where('role', User::ROLE_ADMIN)->count(),
            'verified'   => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
            'active'     => User::where('is_active', true)->count(),
            'inactive'   => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'ends_with:@grabtaxi.com', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'in:admin,user'],
            'notify_email' => ['nullable', 'email', 'max:255'],
            'auto_verify' => ['nullable', 'in:1'],
        ], [
            'email.ends_with' => 'Email must use @grabtaxi.com domain.',
            'email.unique' => 'This email is already registered.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
            'email_verified_at' => !empty($validated['auto_verify']) ? now() : null,
        ]);

        // Send credentials to notification email (or user email if not specified)
        $notifyEmail = !empty($validated['notify_email']) ? $validated['notify_email'] : $user->email;

        try {
            // Route notification to custom email address
            $notifiable = (new class($notifyEmail, $user->name, $user->email) extends \Illuminate\Notifications\AnonymousNotifiable {
                public string $name;
                public string $email;

                public function __construct(string $routeEmail, string $name, string $email)
                {
                    $this->route('mail', $routeEmail);
                    $this->name = $name;
                    $this->email = $email;
                }
            });

            $notifiable->notify(new AccountCreatedNotification(
                password: $validated['password'],
                adminName: $request->user()->name,
                recipientEmail: $notifyEmail,
                isReset: false,
            ));

            $sentTo = $notifyEmail;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send account notification: ' . $e->getMessage());
            $sentTo = null;
        }

        $msg = "User \"{$user->name}\" created successfully.";
        if ($sentTo) {
            $msg .= " Credentials sent to {$sentTo}.";
        }

        return redirect()->route('admin.users.index')->with('success', $msg);
    }

    public function edit(User $user)
    {
        $recentLogs = AuthLog::where('user_id', $user->id)
            ->latest('created_at')
            ->take(10)
            ->get();

        $credentialLogs = CredentialSendLog::where('user_id', $user->id)
            ->with('sender')
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('admin.users.edit', compact('user', 'recentLogs', 'credentialLogs'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'in:admin,user'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower(trim($validated['email']));
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', "User \"{$user->name}\" updated successfully.");
    }

    public function toggleStatus(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->isActive() ? 'activated' : 'deactivated';
        return back()->with('success', "User \"{$user->name}\" has been {$status}.");
    }

    public function resetAndSend(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'send_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        $sendTo = !empty($validated['send_to_email']) ? $validated['send_to_email'] : $user->email;

        try {
            $notifiable = new class($sendTo, $user->name, $user->email) extends \Illuminate\Notifications\AnonymousNotifiable {
                public string $name;
                public string $email;
                public function __construct(string $routeEmail, string $name, string $email)
                {
                    $this->route('mail', $routeEmail);
                    $this->name = $name;
                    $this->email = $email;
                }
            };
            $notifiable->notify(new AccountCreatedNotification(
                password: $validated['new_password'],
                adminName: $request->user()->name,
                recipientEmail: $sendTo,
                isReset: true,
            ));
            return back()->with('success', "Password reset for \"{$user->name}\" and credentials sent to {$sendTo}.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send reset notification: ' . $e->getMessage());
            return back()->with('success', "Password reset for \"{$user->name}\". Email delivery failed — share credentials manually.");
        }
    }

    public function sendCredentials(Request $request, User $user)
    {
        $validated = $request->validate([
            'send_to_email' => ['required', 'email', 'max:255'],
            'new_password' => ['required', 'string', Password::min(8)->letters()->numbers()],
        ]);

        $sendTo = $validated['send_to_email'];
        $password = $validated['new_password'];

        // Always reset password — existing hash can't be reversed
        $user->password = Hash::make($password);
        $user->save();

        try {
            $notifiable = new class($sendTo, $user->name, $user->email) extends \Illuminate\Notifications\AnonymousNotifiable {
                public string $name;
                public string $email;
                public function __construct(string $routeEmail, string $name, string $email)
                {
                    $this->route('mail', $routeEmail);
                    $this->name = $name;
                    $this->email = $email;
                }
            };

            $notifiable->notify(new SendCredentialsNotification(
                adminName: $request->user()->name,
                password: $password,
                recipientEmail: $sendTo,
            ));

            CredentialSendLog::create([
                'user_id' => $user->id,
                'sent_by' => $request->user()->id,
                'sent_to_email' => $sendTo,
                'include_password' => true,
                'status' => 'success',
            ]);

            return back()->with('success', "Credentials for \"{$user->name}\" sent to {$sendTo}. Password has been updated.");

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("SendCredentials failed for user {$user->id}: " . $e->getMessage());

            CredentialSendLog::create([
                'user_id' => $user->id,
                'sent_by' => $request->user()->id,
                'sent_to_email' => $sendTo,
                'include_password' => true,
                'status' => 'fail',
                'failed_reason' => $e->getMessage(),
            ]);

            return back()->with('error', "Failed to send credentials: {$e->getMessage()}");
        }
    }

    public function resendVerification(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('error', "User \"{$user->name}\" is already verified.");
        }

        $user->sendEmailVerificationNotification();
        return back()->with('success', "Verification email sent to {$user->email}.");
    }

    public function destroy(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User \"{$name}\" deleted.");
    }
}
