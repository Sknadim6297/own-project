<?php

namespace App\Http\Middleware;

use App\Support\Auth\IntendedUrl;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }
        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        if ($request->is('professional') || $request->is('professional/*')) {
            return route('professional.login');
        }

        return route('login');
    }

    /**
     * Remember a guard-specific intended URL before Laravel's guest redirect.
     *
     * @param  array<int, string>  $guards
     */
    protected function unauthenticated($request, array $guards)
    {
        $guard = $guards[0] ?? null;

        if (in_array($guard, ['user', 'professional'], true)) {
            IntendedUrl::remember($request, $guard);
        }

        parent::unauthenticated($request, $guards);
    }
}
