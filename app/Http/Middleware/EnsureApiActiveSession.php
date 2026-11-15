<?php

namespace App\Http\Middleware;

use App\Models\ActiveSession;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps mobile Sanctum tokens aligned with website ActiveSession force-login.
 * When the website (or another mobile client) clears other ActiveSession rows,
 * bearer tokens whose session_id was removed are rejected.
 */
class EnsureApiActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $accessToken = $user->currentAccessToken();

        if (! $accessToken instanceof PersonalAccessToken) {
            return $next($request);
        }

        $sessionKey = 'sanctum:'.$accessToken->id;

        $valid = ActiveSession::where('user_id', $user->id)
            ->where('session_id', $sessionKey)
            ->exists();

        if (! $valid) {
            $accessToken->delete();

            return response()->json([
                'status' => 'error',
                'message' => 'Your session has ended because you logged in from another device.',
            ], 401);
        }

        ActiveSession::where('user_id', $user->id)
            ->where('session_id', $sessionKey)
            ->update(['last_active_at' => now()]);

        return $next($request);
    }
}
