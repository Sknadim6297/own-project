<?php

namespace App\Http\Middleware;

use App\Services\Terms\TermsAcceptanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerTermsAccepted
{
    public function __construct(
        protected TermsAcceptanceService $terms
    ) {
    }

    /**
     * Block authenticated customers who have not accepted the current Terms.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('user');

        if (! $user) {
            return $next($request);
        }

        if ($this->terms->hasAcceptedCurrent($user)) {
            return $next($request);
        }

        if ($request->routeIs(
            'terms.accept.show',
            'terms.accept',
            'terms.decline',
            'user.logout',
            'logout'
        )) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'terms_required',
                'message' => 'You must accept the Terms & Conditions before continuing.',
                'redirect_url' => route('terms.accept.show'),
            ], 403);
        }

        return redirect()->route('terms.accept.show');
    }
}
