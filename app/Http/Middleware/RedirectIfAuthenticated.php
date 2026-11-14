<?php

namespace App\Http\Middleware;

use App\Services\Terms\TermsAcceptanceService;
use App\Support\Auth\IntendedUrl;
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
        // Prefer named guards first — a null/default guard must not fall through to Home
        // when the customer (`user`) session is authenticated.
        if ($guards === []) {
            $guards = ['user', 'professional', 'admin', 'web'];
        }

        foreach ($guards as $guard) {
            if (! Auth::guard($guard)->check()) {
                continue;
            }

            return match ($guard) {
                'admin' => redirect('/admin/dashboard'),
                'professional' => redirect(IntendedUrl::resolve('professional')),
                'user' => redirect($this->customerDestination()),
                'web' => Auth::guard('user')->check()
                    ? redirect($this->customerDestination())
                    : redirect()->route('home'),
                default => Auth::guard('user')->check()
                    ? redirect($this->customerDestination())
                    : (Auth::guard('professional')->check()
                        ? redirect(IntendedUrl::resolve('professional'))
                        : redirect()->route('home')),
            };
        }

        return $next($request);
    }

    private function customerDestination(): string
    {
        $terms = app(TermsAcceptanceService::class);
        $user = Auth::guard('user')->user();

        if ($user
            && $terms->shouldEnforceAfterLogin()
            && ! $terms->hasAcceptedCurrent($user)
        ) {
            return route('terms.accept.show');
        }

        return IntendedUrl::resolve('user');
    }
}
