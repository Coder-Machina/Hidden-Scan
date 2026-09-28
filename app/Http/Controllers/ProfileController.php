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

        // Calculate dynamic global rank
        $globalRank = \App\Models\User::where('xp', '>', $user->xp ?? 0)->count() + 1;

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
            'globalRank' => $globalRank,
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

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'bio' => $validated['bio'] ?? null,
            'favorite_genre' => $validated['favorite_genre'] ?? null,
            'reader_mode' => $validated['reader_mode'] ?? 'vertical',
        ]);

        // Handle Avatar
        if ($request->hasFile('avatar_file')) {
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $user->avatar = $path;
        } elseif (!empty($validated['avatar_preset'])) {
            $user->avatar = $validated['avatar_preset'];
        } elseif (!empty($validated['avatar_url'])) {
            $user->avatar = $validated['avatar_url'];
        }

        // Handle Banner
        if ($request->hasFile('banner_file')) {
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
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
