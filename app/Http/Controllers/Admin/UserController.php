<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $users = $query->latest()->paginate(15)->appends($request->query());

        $stats = [
            'total'      => User::count(),
            'admins'     => User::where('role', User::ROLE_ADMIN)->count(),
            'verified'   => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function edit(User $user)
    {
        $recentLogs = AuthLog::where('user_id', $user->id)
            ->latest('created_at')
            ->take(10)
            ->get();

        return view('admin.users.edit', compact('user', 'recentLogs'));
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

    public function toggleVerification(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            $user->email_verified_at = null;
        } else {
            $user->email_verified_at = now();
        }
        $user->save();

        $status = $user->hasVerifiedEmail() ? 'verified' : 'unverified';
        return back()->with('success', "User \"{$user->name}\" is now {$status}.");
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
