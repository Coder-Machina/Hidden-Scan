<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProfileDataService;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function show(string $username, Request $request)
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
        $isOwner = $viewer && $viewer->id === $user->id;
        $isStaff = $viewer && $viewer->isStaffOrAdmin();
        $isPublic = ($user->profile_public ?? true);

        if (! $isPublic && ! $isOwner && ! $isStaff) {
            abort(404, 'Ce profil est privé ou introuvable.');
        }

        $data = ProfileDataService::getProfileData(
            $user,
            $viewer,
            $request->query('tab')
        );

        return view('profile.edit', $data);
    }
}
