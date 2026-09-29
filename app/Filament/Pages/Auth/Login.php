<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable | null
    {
        return 'Connexion Administration';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Connectez-vous avec vos identifiants Staff ou votre Pass Secret administrateur.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email ou Pass Secret (HS-••••)')
            ->placeholder('admin@hiddenscan.com ou HS-XXXX-XXXX-XXXX')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Mot de passe (optionnel si vous utilisez votre Pass Secret)')
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1"> {{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $input = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $remember = (bool) ($data['remember'] ?? false);

        $cleanInput = strtoupper(str_replace(' ', '', $input));
        $rawCode = str_replace('-', '', $cleanInput);

        // 1. Authentification directe par Pass Secret
        if (str_starts_with($cleanInput, 'HS') || empty($password)) {
            $user = User::where('pass_code', $cleanInput)
                ->orWhereRaw("REPLACE(pass_code, '-', '') = ?", [$rawCode])
                ->first();

            if ($user) {
                if ($user->isBanned()) {
                    throw ValidationException::withMessages([
                        'data.email' => 'Ce compte a été suspendu par un administrateur.',
                    ]);
                }

                if ($user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
                    $user->update(['last_ip_address' => request()->ip()]);
                    Filament::auth()->login($user, $remember);
                    session()->regenerate();

                    return app(LoginResponse::class);
                }

                throw ValidationException::withMessages([
                    'data.email' => 'Ce Pass Secret ne dispose pas des droits nécessaires pour accéder au panneau d\'administration.',
                ]);
            }

            if (empty($password)) {
                throw ValidationException::withMessages([
                    'data.email' => 'Pass Secret introuvable ou mot de passe manquant.',
                ]);
            }
        }

        // 2. Authentification par Email + Mot de passe
        if (Filament::auth()->attempt(['email' => $input, 'password' => $password], $remember)) {
            /** @var User $user */
            $user = Filament::auth()->user();

            if ($user->isBanned()) {
                Filament::auth()->logout();
                session()->invalidate();
                session()->regenerateToken();

                throw ValidationException::withMessages([
                    'data.email' => 'Ce compte a été suspendu par un administrateur.',
                ]);
            }

            if ($user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
                $user->update(['last_ip_address' => request()->ip()]);
                session()->regenerate();

                return app(LoginResponse::class);
            }

            Filament::auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages([
                'data.email' => 'Ce compte ne dispose pas des droits d\'accès au panneau d\'administration.',
            ]);
        }

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}
