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

                // Déconnexion radicale de toutes les sessions
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->view('errors.banned', [
                    'banType' => 'account',
                    'reason' => $reason,
                    'bannedUntil' => $bannedUntil,
                ], 403);
            }
        }

        // 2. Vérifier si l'adresse IP est bannie
        if (Auth::check() && Auth::user()->isStaffOrAdmin()) {
            return $next($request);
        }

        if (Schema::hasTable('banned_ips')) {
            $ip = $request->ip();
            $ipHash = sha1($ip);

            $bannedIp = DB::table('banned_ips')
                ->where(function ($query) use ($ip, $ipHash) {
                    $query->where('ip_address', $ip)
                          ->orWhere('ip_hash', $ipHash);
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

        return $next($request);
    }
}
