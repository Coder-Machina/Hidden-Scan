<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        
        // Comments stats
        $commentsCount = $user->comments()->count();
        $likesReceived = $user->comments()->sum('likes_count') ?? 0;
        $recentComments = $user->comments()
            ->with(['commentable'])
            ->latest()
            ->take(6)
            ->get();

        $genres = \App\Models\Genre::orderBy('name')->get();

        // Manga lookup mapping for covers & titles in library / reading history
        $mangasLookup = \App\Models\Manga::all(['id', 'title', 'slug', 'cover_image'])->mapWithKeys(function ($m) {
            return [$m->slug => [
                'title' => $m->title,
                'cover' => $m->cover_image ? \Illuminate\Support\Facades\Storage::url($m->cover_image) : null,
            ]];
        });

        return view('profile.edit', [
            'user' => $user,
            'commentsCount' => $commentsCount,
            'likesReceived' => $likesReceived,
            'recentComments' => $recentComments,
            'genres' => $genres,
            'mangasLookup' => $mangasLookup,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $fillData = [
            'name' => $validated['name'],
            'bio' => $validated['bio'] ?? null,
            'favorite_genre' => $validated['favorite_genre'] ?? null,
            'reader_mode' => $validated['reader_mode'] ?? 'vertical',
        ];

        if (!empty($validated['email'])) {
            $fillData['email'] = $validated['email'];
        }

        $user->fill($fillData);

        // Handle Avatar
        if (!empty($validated['avatar_data'])) {
            $user->avatar = $validated['avatar_data'];
        } elseif ($request->hasFile('avatar_file')) {
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $user->avatar = $path;
        } elseif (!empty($validated['avatar_preset'])) {
            $user->avatar = $validated['avatar_preset'];
        } elseif (!empty($validated['avatar_url'])) {
            $user->avatar = $validated['avatar_url'];
        }

        // Handle Banner
        if (!empty($validated['banner_data'])) {
            $user->banner = $validated['banner_data'];
        } elseif ($request->hasFile('banner_file')) {
            $bannerPath = $request->file('banner_file')->store('banners', 'public');
            $user->banner = $bannerPath;
        } elseif (!empty($validated['banner_url'])) {
            $user->banner = $validated['banner_url'];
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Synchronise le profil entre le client (localStorage) et le serveur.
     */
    public function sync(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Non authentifié'], 401);
        }

        $dirty = false;

        // Si le serveur n'a pas d'avatar et que le client en fournit un
        if (empty($user->avatar) && $request->filled('avatar')) {
            $user->avatar = $request->input('avatar');
            $dirty = true;
        }

        // Si le serveur n'a pas de bannière et que le client en fournit une
        if (empty($user->banner) && $request->filled('banner')) {
            $user->banner = $request->input('banner');
            $dirty = true;
        }

        // Si le pseudo serveur est par défaut et que le client en a un personnalisé
        if ($request->filled('name') && str_starts_with($user->name, 'Lecteur-') && !str_starts_with($request->input('name'), 'Lecteur-')) {
            $user->name = \Illuminate\Support\Str::limit(strip_tags($request->input('name')), 30, '');
            $dirty = true;
        }

        // Bio
        if (empty($user->bio) && $request->filled('bio')) {
            $user->bio = \Illuminate\Support\Str::limit(strip_tags($request->input('bio')), 1000, '');
            $dirty = true;
        }

        // Préférences
        if (empty($user->favorite_genre) && $request->filled('favorite_genre')) {
            $user->favorite_genre = $request->input('favorite_genre');
            $dirty = true;
        }

        if ($request->filled('reader_mode') && in_array($request->input('reader_mode'), ['vertical', 'single'])) {
            $user->reader_mode = $request->input('reader_mode');
            $dirty = true;
        }

        if ($dirty) {
            $user->save();
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'pass_code' => $user->pass_code,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'banner' => $user->banner,
                'banner_url' => $user->banner_url,
                'bio' => $user->bio,
                'favorite_genre' => $user->favorite_genre,
                'reader_mode' => $user->reader_mode,
            ],
        ]);
    }

    /**
     * Régénère un nouveau Pass Secret pour un lecteur anonyme.
     */
    public function regeneratePass(Request $request): RedirectResponse
    {
        $user = $request->user();

        $newPass = \App\Models\User::generateUniquePassCode();
        $user->pass_code = $newPass;
        $user->save();

        return Redirect::route('profile.edit', ['tab' => 'settings'])->with([
            'status' => 'pass-regenerated',
            'new_pass_code' => $newPass,
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Si l'utilisateur est un lecteur avec Pass Secret, pas besoin de mot de passe
        if (!$user->pass_code) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
