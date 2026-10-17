<?php

namespace App\Http\Controllers;

use App\Models\User;

class PublicProfileController extends Controller
{
    public function show(string $username)
    {
        // Trouver l'utilisateur par username (accessible par tous si public, ou par le propriétaire connecté)
        $user = User::where('username', $username)
                    ->when(auth()->id() !== null, function ($query) {
                        $query->where(function ($q) {
                            $q->where('profile_public', true)
                              ->orWhere('id', auth()->id());
                        });
                    }, function ($query) {
                        $query->where('profile_public', true);
                    })
                    ->firstOrFail();

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
