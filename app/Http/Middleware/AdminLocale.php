<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('admin_locale', 'en');
        if (in_array($locale, ['en', 'id'])) {
            app()->setLocale($locale);
        }
        return $next($request);
    }
}
