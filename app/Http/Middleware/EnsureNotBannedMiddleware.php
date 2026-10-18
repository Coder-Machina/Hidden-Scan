<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBannedMiddleware
{
    /**
     * Handle an incoming request.
     * Bloque radicalement tout utilisateur ou adresse IP bannie du site et de son compte.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Vérifier si l'utilisateur connecté est banni
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isBanned()) {
                $reason = $user->ban_reason;
                $bannedUntil = $user->banned_until;
                $userId = $user->id;

                // Déconnexion radicale
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $response = response()->view('errors.banned', [
                    'banType' => 'account',
                    'reason' => $reason,
                    'bannedUntil' => $bannedUntil,
                ], 403);

                // Poser un cookie persistant pour bloquer également la navigation invitée résiduelle sur ce navigateur
                return $response->withCookie(cookie('hs_banned_uid', (string) $userId, 60 * 24 * 30));
            }
        }

        // 2. Vérifier si le navigateur possède un cookie d'un compte banni
        if ($bannedUid = $request->cookie('hs_banned_uid')) {
            $user = \App\Models\User::find($bannedUid);
            if ($user && $user->isBanned()) {
                return response()->view('errors.banned', [
                    'banType' => 'account',
                    'reason' => $user->ban_reason,
                    'bannedUntil' => $user->banned_until,
                ], 403);
            }
        }

        // 3. Les membres du Staff authentifiés ne sont jamais bloqués par restriction IP
        if (Auth::check() && Auth::user()->isStaffOrAdmin()) {
            return $next($request);
        }

        // 4. Vérifier si l'adresse IP est bannie (support sha1, sha256 et IP brute)
        if (Schema::hasTable('banned_ips')) {
            $ip = $request->ip();

            if ($ip && !in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
                $sha1 = sha1($ip);
                $sha256 = hash('sha256', $ip . config('app.key'));

                $bannedIp = DB::table('banned_ips')
                    ->where(function ($query) use ($ip, $sha1, $sha256) {
                        $query->where('ip_address', $ip)
                              ->orWhere('ip_hash', $sha1)
                              ->orWhere('ip_hash', $sha256);
                    })
                    ->where('ban_type', 'all')
                    ->first();

                if ($bannedIp) {
                    // Vérifier si le ban IP temporaire est expiré
                    if (!empty($bannedIp->banned_until) && now()->greaterThanOrEqualTo($bannedIp->banned_until)) {
                        DB::table('banned_ips')->where('id', $bannedIp->id)->delete();
                    } else {
                        return response()->view('errors.banned', [
                            'banType' => 'ip',
                            'reason' => $bannedIp->reason,
                            'bannedUntil' => !empty($bannedIp->banned_until) ? \Carbon\Carbon::parse($bannedIp->banned_until) : null,
                        ], 403);
                    }
                }
            }
        }

        $response = $next($request);

        // Si le cookie de ban est présent mais que l'utilisateur n'est plus banni, nettoyer le cookie
        if ($request->hasCookie('hs_banned_uid')) {
            $response->headers->clearCookie('hs_banned_uid');
        }

        return $response;
    }
}
