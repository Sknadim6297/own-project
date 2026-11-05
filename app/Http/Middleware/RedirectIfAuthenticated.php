<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Default `guest` uses the web guard; also recognise app-specific guards.
        if ($guards === []) {
            $guards = [null, 'web', 'user', 'admin', 'professional'];
        }

        foreach ($guards as $guard) {
            if (! Auth::guard($guard)->check()) {
                continue;
            }

            return match ($guard) {
                'admin' => redirect('/admin/dashboard'),
                'professional' => redirect('/professional/dashboard'),
                'user' => redirect()->route('home'),
                default => redirect()->route('home'),
            };
        }

        return $next($request);
    }
}
