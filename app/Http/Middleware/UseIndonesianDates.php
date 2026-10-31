<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the date locale for every request.
 *
 * Under Octane, each request starts by resetting Carbon to app.locale, so
 * setting it once while booting only lasts until the first request ends.
 */
final class UseIndonesianDates
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Date::setLocale('id');

        return $next($request);
    }
}
