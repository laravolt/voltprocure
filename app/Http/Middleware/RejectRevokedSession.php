<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logout ends a session for good, whichever session driver is in use.
 *
 * With SESSION_DRIVER=cookie (the Docker default) the session lives in the
 * browser, so invalidate() cannot destroy a copy of the old cookie. Logout
 * therefore records the session id as revoked for the session lifetime, and
 * this middleware signs out any request that still carries it.
 */
final class RejectRevokedSession
{
    public static function revoke(Session $session): void
    {
        Cache::put(self::key($session->getId()), true, now()->addMinutes(config()->integer('session.lifetime', 120)));
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && Cache::has(self::key($request->session()->getId()))) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $next($request);
    }

    private static function key(string $sessionId): string
    {
        return 'revoked-session:'.hash('sha256', $sessionId);
    }
}
