<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to a route when its feature key is under maintenance.
 *
 * Register on any route:
 *   Route::get('/pricing', ...)->middleware('feature.maintenance:pricing');
 *
 * Registry lives in config/maintenance.php.
 * Toggle via env: MAINTENANCE_<KEY>=true
 *
 * Bypass on prod (for QA/preview): append ?bypass=<token> where <token>
 * matches config('maintenance.bypass_token'). Sticks for the session.
 */
class FeatureMaintenance
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $config = config("maintenance.{$feature}");

        if (! is_array($config) || ! ($config['enabled'] ?? false)) {
            return $next($request);
        }

        // Bypass check: query param OR cookie
        $bypassToken = config('maintenance.bypass_token');
        if ($bypassToken) {
            if ($request->query('bypass') === $bypassToken) {
                // Stick bypass for this session so subsequent nav works
                cookie()->queue('maintenance_bypass', $bypassToken, 60 * 4); // 4 hours
                return $next($request);
            }
            if ($request->cookie('maintenance_bypass') === $bypassToken) {
                return $next($request);
            }
        }

        // JSON / API request → return structured 503 instead of HTML
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error'   => 'Service under maintenance',
                'feature' => $feature,
                'message' => "This feature ({$config['name']}) is temporarily unavailable.",
            ], 503);
        }

        return response()->view('errors.maintenance', [
            'feature'   => $config['name']        ?? 'This feature',
            'featureId' => $config['description'] ?? 'this feature',
        ], 503);
    }
}
