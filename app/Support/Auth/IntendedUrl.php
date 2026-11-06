<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;

/**
 * Guard-aware post-login destinations.
 * Stores separate intended URLs for customer (`user`) and professional guards
 * so one login cannot consume the other guard's destination.
 */
class IntendedUrl
{
    public const USER_KEY = 'url.intended.user';

    public const PROFESSIONAL_KEY = 'url.intended.professional';

    public static function sessionKey(string $guard): string
    {
        return match ($guard) {
            'professional' => self::PROFESSIONAL_KEY,
            default => self::USER_KEY,
        };
    }

    public static function remember(Request $request, string $guard): void
    {
        if ($request->expectsJson()) {
            return;
        }

        $path = '/'.ltrim($request->getRequestUri(), '/');
        $pathOnly = parse_url($path, PHP_URL_PATH) ?: '/';

        if (self::isAuthEntryPath($pathOnly)) {
            return;
        }

        if (! self::isCompatiblePath($pathOnly, $guard)) {
            return;
        }

        $full = $request->fullUrl();
        session([
            self::sessionKey($guard) => $full,
            // Keep Laravel's default key in sync for this guard only.
            'url.intended' => $full,
        ]);
    }

    /**
     * Consume a safe intended destination for the given guard, or null.
     */
    public static function pull(string $guard): ?string
    {
        $key = self::sessionKey($guard);
        $candidates = [
            session()->pull($key),
        ];

        $shared = session('url.intended');
        if (is_string($shared) && $shared !== '') {
            $sharedSafe = self::sanitize($shared, $guard);
            if ($sharedSafe !== null) {
                session()->forget('url.intended');
                $candidates[] = $shared;
            }
        }

        foreach ($candidates as $candidate) {
            $safe = self::sanitize(is_string($candidate) ? $candidate : null, $guard);
            if ($safe !== null) {
                // Clear the other representation once consumed.
                session()->forget([$key, 'url.intended']);

                return $safe;
            }
        }

        session()->forget($key);

        return null;
    }

    public static function defaultFor(string $guard): string
    {
        return match ($guard) {
            'professional' => route('professional.dashboard'),
            'user' => route('user.dashboard'),
            default => route('home'),
        };
    }

    public static function resolve(string $guard, ?string $fallback = null): string
    {
        return self::pull($guard) ?? $fallback ?? self::defaultFor($guard);
    }

    /**
     * Allow only same-application destinations compatible with the guard.
     */
    public static function sanitize(?string $url, string $guard): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $pathWithQuery = self::toInternalPath($url);
        if ($pathWithQuery === null) {
            return null;
        }

        $pathOnly = parse_url($pathWithQuery, PHP_URL_PATH) ?: '/';

        if (self::isAuthEntryPath($pathOnly)) {
            return null;
        }

        if (! self::isCompatiblePath($pathOnly, $guard)) {
            return null;
        }

        return url($pathWithQuery);
    }

    public static function isCompatiblePath(string $pathOnly, string $guard): bool
    {
        $path = '/'.ltrim($pathOnly, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        if (str_starts_with($path, '/admin')) {
            return false;
        }

        if ($guard === 'professional') {
            if (str_starts_with($path, '/user')) {
                return false;
            }

            // Professional panel only (not public /professionals*).
            return (bool) preg_match('#^/professional(/|$)#', $path);
        }

        // Customer (`user`) guard: block professional panel routes.
        if (preg_match('#^/professional(/|$)#', $path)) {
            return false;
        }

        return true;
    }

    public static function isAuthEntryPath(string $pathOnly): bool
    {
        $path = '/'.ltrim($pathOnly, '/');
        $blocked = [
            '/login',
            '/register',
            '/force-login',
            '/forgot-password',
            '/reset-password',
            '/admin/login',
            '/professional/login',
            '/professional/register',
            '/professional/forgot-password',
            '/professional/reset-password',
        ];

        foreach ($blocked as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private static function toInternalPath(string $url): ?string
    {
        if (str_starts_with($url, '/')) {
            return $url;
        }

        if (preg_match('#^https?://#i', $url) !== 1) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if (! in_array($host, self::allowedHosts(), true)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }

        if (! empty($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    private static function allowedHosts(): array
    {
        $hosts = [];

        foreach ([config('app.url'), request()->getSchemeAndHttpHost()] as $base) {
            $host = parse_url((string) $base, PHP_URL_HOST);
            if (! is_string($host) || $host === '') {
                continue;
            }

            $host = strtolower($host);
            $hosts[] = $host;

            if ($host === 'localhost') {
                $hosts[] = '127.0.0.1';
            }
            if ($host === '127.0.0.1') {
                $hosts[] = 'localhost';
            }
        }

        return array_values(array_unique($hosts));
    }
}
