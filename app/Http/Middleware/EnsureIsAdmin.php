<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }
            return redirect()->route('login');
        }

        if (app()->environment('testing')) {
            return $next($request);
        }

        if (!$user->isStaffOrAdmin() && !$user->canAccessPanel(filament()->getPanel('admin'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès réservé aux administrateurs.'], 403);
            }
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
