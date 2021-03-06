<?php

namespace AwStudio\Redirects\Middleware;

use AwStudio\Redirects\RedirectRouter;
use Closure;
use Illuminate\Http\Request;

class RedirectRoutesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $redirect = app(RedirectRouter::class)->getRedirectFor($request);

        return $redirect ?? $response;
    }
}
