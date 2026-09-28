<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar_file')) {
            $avatarPath = $request->file('avatar_file')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Génère un nouveau Pass secret anonyme en 1 clic.
     */
    public function storePass(Request $request): RedirectResponse
    {
        $passCode = User::generateUniquePassCode();
        $shortId = substr(str_replace('-', '', $passCode), 2, 4);
        $name = $request->filled('name')
            ? \Illuminate\Support\Str::limit(strip_tags($request->input('name')), 30, '')
            : 'Lecteur-' . $shortId;

        $dummyEmail = 'pass_' . strtolower(str_replace('-', '', $passCode)) . '@anon.hiddenscan.local';

        $user = User::create([
            'name' => $name,
            'email' => $dummyEmail,
            'pass_code' => $passCode,
            'password' => Hash::make(\Illuminate\Support\Str::random(40)),
            'last_ip_address' => $request->ip(),
        ]);

        event(new Registered($user));

        Auth::login($user, remember: true);

        return redirect()->route('home')->with('new_pass_code', $passCode);
    }
}
