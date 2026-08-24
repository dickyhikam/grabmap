<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pagar rute berbasis izin: ->middleware('permission:users.manage').
 * Beberapa izin boleh disebut sekaligus (dipisah koma) — cukup punya salah satu.
 */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, __('roles.no_access'));
    }
}
