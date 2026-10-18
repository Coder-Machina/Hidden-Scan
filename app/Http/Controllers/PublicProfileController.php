<?php

namespace App\Http\Controllers;

use App\Models\User;

class PublicProfileController extends Controller
{
    public function show(string $username)
    {
        $decoded = trim(urldecode($username));
        $normalized = mb_strtolower($decoded);

        // Trouver l'utilisateur par username, name ou ID (compatible PostgreSQL et MySQL/SQLite)
        $user = User::where(function ($query) use ($decoded, $normalized) {
            $query->whereRaw('LOWER(username) = ?', [$normalized])
                  ->orWhereRaw('LOWER(name) = ?', [$normalized])
                  ->orWhere('username', $decoded)
                  ->orWhere('name', $decoded);

            if (is_numeric($decoded)) {
                $query->orWhere('id', (int) $decoded);
            }
        })->first();

        if (! $user) {
            abort(404, 'Ce profil est privé ou introuvable.');
        }

        // Si le profil n'est pas public, seuls les administrateurs/modérateurs et le propriétaire peuvent le consulter
        $viewer = auth()->user();
        $isOwnerOrStaff = $viewer && ($viewer->id === $user->id || $viewer->isStaffOrAdmin());
        $isPublic = ($user->profile_public ?? true);

        if (! $isPublic && ! $isOwnerOrStaff) {
            abort(404, 'Ce profil est privé ou introuvable.');
        }

        // Stats de lecture — requêtes isolées pour éviter toute pollution de Builder sous PostgreSQL
        $mangasLus = 0;
        $chapitresLus = 0;
        if (method_exists($user, 'readingProgress')) {
            $mangasLus = (int) $user->readingProgress()->where('is_read', true)->distinct()->count('manga_id');
            $chapitresLus = (int) $user->readingProgress()->where('is_read', true)->count();
        }

        $favoritesCount = method_exists($user, 'favorites') ? (int) $user->favorites()->count() : 0;

        $stats = [
            'mangas_lus'    => $mangasLus,
            'chapitres_lus' => $chapitresLus,
            'favoris'       => $favoritesCount,
            'membre_depuis' => $user->created_at ? $user->created_at->diffForHumans() : 'Récemment',
        ];

        // Mangas favoris publics (max 12 pour la page)
        $favoris = method_exists($user, 'favorites')
            ? $user->favorites()->whereHas('manga')->with('manga')->latest()->take(12)->get()
            : collect();

        // Derniers mangas lus (max 8)
        $recents = method_exists($user, 'readingProgress')
            ? $user->readingProgress()->whereHas('manga')->with('manga')->latest()->take(8)->get()
            : collect();

        return view('profile.public', compact('user', 'stats', 'favoris', 'recents'));
    }
}
