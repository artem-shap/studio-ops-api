<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public API is consumed by studio-ops-web server-side and by nothing else.
 * A browser never reaches these routes, so a shared secret in a header is the
 * whole authentication story — there is no user session to attach to.
 *
 * This is also why the project has no CORS configuration: cross-origin requests
 * are not something these routes are meant to serve.
 */
class EnsureStudioApiKey
{
    public const HEADER = 'X-Studio-Key';

    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.studio.api_key');
        $provided = $request->header(self::HEADER);

        // A missing configured key must never mean "allow everything".
        if (! is_string($expected) || $expected === '') {
            abort(500, 'Studio API key is not configured.');
        }

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            abort(401);
        }

        return $next($request);
    }
}
