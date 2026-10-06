<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
         * Unlike the public site (DECISIONS #10), every panel page is rendered
         * per request already, so a nonce costs nothing here. It has to exist
         * before the view renders: Vite stamps it on the tags it emits, and the
         * root template stamps it on the one inline script.
         */
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // The Vite dev server is a second origin with its own websocket, and
        // a policy that allowed it would be a different policy. Locally it is
        // simply not sent; nothing deployed runs hot.
        if (! Vite::isRunningHot()) {
            $response->headers->set('Content-Security-Policy', $this->policy($nonce, $request->isSecure()));
        }

        // Only over a connection that is actually secure, so a plain-HTTP
        // local server never tells a browser to refuse it for two years.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Styles keep 'unsafe-inline' because the panel's popovers and menus are
     * positioned with style attributes computed at runtime, and a nonce
     * cannot cover an attribute. Scripts get no such allowance.
     */
    private function policy(string $nonce, bool $secure): string
    {
        return implode('; ', array_filter([
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            // Over plain HTTP this would send a local server's own assets to
            // an https:// URL that is not listening.
            $secure ? 'upgrade-insecure-requests' : null,
        ]));
    }
}
