<?php

namespace App\Http\Middleware;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class CheckScheduledChaptersMiddleware
{
    protected static ?int $lastCheck = null;

    public function handle(Request $request, Closure $next): Response
    {
        // En local ou en secours, vérifie au maximum toutes les 10 secondes
        $now = time();
        if (static::$lastCheck === null || ($now - static::$lastCheck) > 10) {
            static::$lastCheck = $now;

            try {
                if (Chapter::where('status', ChapterStatus::PROGRAMME)
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now())
                    ->exists()) {
                    Artisan::call('chapters:publish-scheduled');
                }
            } catch (\Throwable $e) {
                // Ignore pour ne jamais bloquer la requête utilisateur
            }
        }

        return $next($request);
    }
}
