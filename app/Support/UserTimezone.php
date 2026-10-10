<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Fuseau horaire réel de l'utilisateur (détecté par le navigateur via le cookie "hs_tz").
 * Évite les décalages entre l'heure saisie dans le panel et l'heure du serveur.
 */
class UserTimezone
{
    public const COOKIE = 'hs_tz';

    public static function get(): string
    {
        $tz = request()?->cookie(self::COOKIE);

        if (is_string($tz) && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }

        return config('app.timezone');
    }

    /**
     * Formate une date (stockée dans le fuseau de l'app) dans le fuseau de l'utilisateur.
     */
    public static function format(CarbonInterface|string|null $date, string $format = 'd/m/Y H:i'): ?string
    {
        if (! $date) {
            return null;
        }

        $carbon = $date instanceof CarbonInterface ? $date->copy() : Carbon::parse($date, config('app.timezone'));

        return $carbon->setTimezone(self::get())->format($format);
    }

    /**
     * Demain à 18h00, heure de l'utilisateur (exprimé dans le fuseau de l'app).
     */
    public static function defaultSchedule(): Carbon
    {
        return now(self::get())->addDay()->setTime(18, 0)->setTimezone(config('app.timezone'));
    }

    public static function helperText(): string
    {
        return 'Heure de votre appareil (' . self::get() . ') — il est actuellement ' . now(self::get())->format('H:i') . '.';
    }
}
