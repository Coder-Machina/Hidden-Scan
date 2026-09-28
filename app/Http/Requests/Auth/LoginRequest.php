<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->filled('pass_code')) {
            return [
                'pass_code' => ['required', 'string', 'min:6', 'max:40'],
            ];
        }

        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // 1. Authentification par Pass Secret
        if ($this->filled('pass_code')) {
            $code = strtoupper(trim($this->input('pass_code')));
            $user = \App\Models\User::where('pass_code', $code)->first();

            if (! $user) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'pass_code' => 'Pass secret invalide ou introuvable. Vérifiez votre saisie.',
                ]);
            }

            if ($user->isBanned()) {
                $reason = $user->ban_reason ? ' Motif : ' . $user->ban_reason : '';

                throw ValidationException::withMessages([
                    'pass_code' => 'Ce compte a été suspendu par un administrateur.' . $reason,
                ]);
            }

            $user->update(['last_ip_address' => $this->ip()]);
            Auth::login($user, $this->boolean('remember', true));
            RateLimiter::clear($this->throttleKey());
            return;
        }

        // 2. Authentification classique (Email / Mot de passe pour staff & utilisateurs)
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (Auth::user()?->isBanned()) {
            $reason = Auth::user()->ban_reason ? ' Motif : ' . Auth::user()->ban_reason : '';
            Auth::logout();
            $this->session()->invalidate();
            $this->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Ce compte a été suspendu par un administrateur.' . $reason,
            ]);
        }

        Auth::user()->update(['last_ip_address' => $this->ip()]);
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $field = $this->filled('pass_code') ? 'pass_code' : 'email';

        throw ValidationException::withMessages([
            $field => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        if ($this->filled('pass_code')) {
            return Str::transliterate('pass|' . $this->input('pass_code') . '|' . $this->ip());
        }

        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
