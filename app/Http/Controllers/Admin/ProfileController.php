<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Profil milik sendiri. Berbeda dengan menu Pengguna yang mengelola akun orang lain,
 * halaman ini tidak butuh izin apa pun — siapa pun yang sudah masuk boleh membuka
 * dan mengubah datanya sendiri.
 *
 * Email sengaja tidak bisa diubah sendiri: mengganti email berarti mengganti
 * identitas masuk dan status verifikasinya, jadi biarkan itu lewat admin.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('admin.profile', [
            'user'        => $user,
            'recentLogs'  => AuthLog::where('user_id', $user->id)->latest('created_at')->take(12)->get(),
            'lastLogin'   => AuthLog::where('user_id', $user->id)
                ->where('action', AuthLog::ACTION_LOGIN)
                ->where('status', AuthLog::STATUS_SUCCESS)
                ->latest('created_at')
                ->skip(1)          // yang paling baru adalah sesi yang sedang berjalan
                ->first(),
            'permissions' => $this->permissionLabels($user->role),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($validated);

        return redirect()->route('admin.profile')->with('success', __('profile.saved'));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => __('profile.err_current'),
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        // Dicatat seperti kejadian auth lain supaya muncul di daftar aktivitas.
        AuthLog::create([
            'user_id'    => $user->id,
            'email'      => $user->email,
            'action'     => 'password_change',
            'status'     => AuthLog::STATUS_SUCCESS,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.profile')->with('success', __('profile.pw_saved'));
    }

    /**
     * Daftar izin role yang sedang dipakai, sudah berlabel manusiawi.
     *
     * @return array<int, string>
     */
    private function permissionLabels(?Role $role): array
    {
        if (!$role) {
            return [];
        }

        if ($role->isFullAccess()) {
            return [__('profile.full_access')];
        }

        // Daftarnya rata dengan kunci bertitik ('dashboard.view'), jadi diambil
        // sekali lalu diindeks langsung — __('permissions.keys.dashboard.view')
        // akan dibaca Laravel sebagai jalur bersarang dan meleset.
        $labels = __('permissions.keys');

        return collect($role->permissions ?? [])
            ->map(fn (string $key) => $labels[$key] ?? $key)
            ->values()
            ->all();
    }
}
