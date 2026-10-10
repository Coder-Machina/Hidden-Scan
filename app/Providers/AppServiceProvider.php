<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les dates du panel (sélecteurs + affichage) utilisent le fuseau horaire réel de l'appareil
        \Filament\Support\Facades\FilamentTimezone::set(fn () => \App\Support\UserTimezone::get());
    }
}
