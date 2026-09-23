<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitComments
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'comments:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            abort(429, 'Trop de commentaires. Veuillez patienter.');
        }

        RateLimiter::hit($key, 60 * 5); // 5 minutes

        return $next($request);
    }
}
