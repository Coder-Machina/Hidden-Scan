<?php

namespace App\Http\Controllers;

use App\Models\User;

class PublicProfileController extends Controller
{
    public function show(string $username)
    {
        // Trouver l'utilisateur par username ou ID
        $user = User::where('username', $username)
                    ->orWhere('id', $username)
                    ->firstOrFail();

        // Si le profil n'est pas public, seuls les administrateurs/modérateurs et le propriétaire peuvent le consulter
        $viewer = auth()->user();
        $canView = $user->profile_public 
            || ($viewer && ($viewer->id === $user->id || $viewer->isStaffOrAdmin()));

        if (! $canView) {
            abort(404, 'Ce profil est privé ou introuvable.');
        }

        // Relation d'historique de lecture
        $readingRelation = method_exists($user, 'readingHistory')
            ? $user->readingHistory()
            : (method_exists($user, 'readingProgress') ? $user->readingProgress() : null);

        // Relation de favoris
        $favoritesRelation = method_exists($user, 'favorites') ? $user->favorites() : null;

        // Stats de lecture — adapter selon les tables existantes
        $stats = [
            'mangas_lus'    => $readingRelation ? ($readingRelation->distinct('manga_id')->count('manga_id') ?? 0) : 0,
            'chapitres_lus' => $readingRelation ? ($readingRelation->count() ?? 0) : 0,
            'favoris'       => $favoritesRelation ? ($favoritesRelation->count() ?? 0) : 0,
            'membre_depuis' => $user->created_at ? $user->created_at->diffForHumans() : 'Récemment',
        ];

        // Mangas favoris publics (max 12 pour la page)
        $favoris = $favoritesRelation
            ? $favoritesRelation->whereHas('manga')->with('manga')->latest()->take(12)->get()
            : collect();

        // Derniers mangas lus (max 8)
        $recents = $readingRelation
            ? $readingRelation->whereHas('manga')->with('manga')->latest()->take(8)->get()
            : collect();

        return view('profile.public', compact('user', 'stats', 'favoris', 'recents'));
    }
}
