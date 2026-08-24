<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /** Warna badge yang boleh dipakai — dibatasi supaya tetap sesuai tema. */
    private const COLORS = ['green', 'violet', 'blue', 'amber', 'rose', 'slate'];

    public function index()
    {
        $roles = Role::query()->withCount('users')->orderByDesc('is_system')->orderBy('name')->get();

        return view('admin.roles.index', [
            'roles'    => $roles,
            'total'    => count(Role::allPermissionKeys()),
        ]);
    }

    public function create()
    {
        return view('admin.roles.form', [
            'role'   => null,
            'colors' => self::COLORS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $role = Role::create($data);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('roles.created', ['name' => $role->name]));
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role'   => $role,
            'colors' => self::COLORS,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validated($request, $role);

        // Role berakses penuh (admin) izinnya tidak bisa dikurangi lewat form —
        // kalau bisa, admin gampang mengunci dirinya sendiri di luar panel.
        if ($role->isFullAccess()) {
            unset($data['permissions'], $data['slug']);
        }

        $role->update($data);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('roles.updated', ['name' => $role->name]));
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', __('roles.cannot_delete_system'));
        }

        $inUse = $role->users()->count();
        if ($inUse > 0) {
            return back()->with('error', __('roles.cannot_delete_used', ['name' => $role->name, 'count' => $inUse]));
        }

        $name = $role->name;
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('roles.deleted', ['name' => $name]));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Role $role = null): array
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:60'],
            'slug'          => ['nullable', 'string', 'max:60', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'description'   => ['nullable', 'string', 'max:255'],
            'color'         => ['required', Rule::in(self::COLORS)],
            'permissions'   => ['array'],
            // Hanya kunci yang benar-benar dikenal aplikasi yang boleh masuk.
            'permissions.*' => [Rule::in(Role::allPermissionKeys())],
        ]);

        // Kolom opsional tidak muncul di hasil validasi kalau tidak dikirim.
        $validated['slug'] = Str::slug(($validated['slug'] ?? '') ?: $validated['name']);
        $validated['permissions'] = array_values($validated['permissions'] ?? []);

        return $validated;
    }
}
