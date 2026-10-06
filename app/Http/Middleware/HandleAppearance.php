<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    private const ALLOWED = ['light', 'dark', 'system'];

    /**
     * The cookie is left unencrypted so the client can set it, which means its
     * value is whatever the browser sends. It ends up inside an inline script,
     * so anything outside the three real values falls back to the default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance');

        View::share('appearance', in_array($appearance, self::ALLOWED, true) ? $appearance : 'system');

        return $next($request);
    }
}
