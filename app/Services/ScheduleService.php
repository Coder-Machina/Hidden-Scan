<?php

namespace App\Services;

use App\Models\Manga;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * Configuration du planning hebdomadaire des sorties
     */
    public static function getScheduleConfig(): array
    {
        return [
            'lundi' => [
                'day_key' => 'lundi',
                'label' => 'Lundi',
                'short' => 'Lun',
                'iso_day' => 1,
                'slots' => [
                    ['slug' => 'genius-grandson-of-the-loan-shark-king', 'time' => '18h00', 'status_label' => 'Officiel HS', 'is_priority' => true],
                    ['slug' => 'academys-genius-swordmaster', 'time' => '20h00', 'status_label' => 'Régulier', 'is_priority' => false],
                ],
            ],
            'mardi' => [
                'day_key' => 'mardi',
                'label' => 'Mardi',
                'short' => 'Mar',
                'iso_day' => 2,
                'slots' => [
                    ['slug' => 'the-reborn-young-lord-is-an-assassin', 'time' => '18h00', 'status_label' => 'Régulier', 'is_priority' => false],
                    ['slug' => 'why-i-quit-being-the-demon-king', 'time' => '20h00', 'status_label' => 'Régulier', 'is_priority' => false],
                ],
            ],
            'mercredi' => [
                'day_key' => 'mercredi',
                'label' => 'Mercredi',
                'short' => 'Mer',
                'iso_day' => 3,
                'slots' => [
                    ['slug' => 'the-returners-road-to-retirement', 'time' => '18h00', 'status_label' => 'Officiel HS', 'is_priority' => true],
                    ['slug' => 'omniscient-reader', 'time' => '20h00', 'status_label' => 'Régulier', 'is_priority' => false],
                ],
            ],
            'jeudi' => [
                'day_key' => 'jeudi',
                'label' => 'Jeudi',
                'short' => 'Jeu',
                'iso_day' => 4,
                'slots' => [
                    ['slug' => 'nano-machine', 'time' => '18h00', 'status_label' => 'Murim Peak', 'is_priority' => false],
                    ['slug' => 'solo-leveling', 'time' => '20h00', 'status_label' => 'Légende', 'is_priority' => false],
                ],
            ],
            'vendredi' => [
                'day_key' => 'vendredi',
                'label' => 'Vendredi',
                'short' => 'Ven',
                'iso_day' => 5,
                'slots' => [
                    ['slug' => 'my-passive-skills-are-invincible', 'time' => '18h00', 'status_label' => 'Officiel HS', 'is_priority' => true],
                    ['slug' => 'hoegwi-suseonjeon', 'time' => '20h00', 'status_label' => 'Cultivation', 'is_priority' => false],
                ],
            ],
            'samedi' => [
                'day_key' => 'samedi',
                'label' => 'Samedi',
                'short' => 'Sam',
                'iso_day' => 6,
                'slots' => [
                    ['slug' => 'one-piece', 'time' => '12h00', 'status_label' => 'Hebdomadaire', 'is_priority' => false],
                ],
            ],
            'dimanche' => [
                'day_key' => 'dimanche',
                'label' => 'Dimanche',
                'short' => 'Dim',
                'iso_day' => 7,
                'slots' => [
                    ['slug' => 'all-football-talents-are-mine', 'time' => '18h00', 'status_label' => 'Officiel HS', 'is_priority' => true],
                ],
            ],
        ];
    }

    /**
     * Retourne le planning complet hydraté avec les modèles Manga
     */
    public static function getHydratedSchedule(): array
    {
        $config = self::getScheduleConfig();
        $allSlugs = [];

        foreach ($config as $day) {
            foreach ($day['slots'] as $slot) {
                $allSlugs[] = $slot['slug'];
            }
        }

        $mangas = Manga::whereIn('slug', array_unique($allSlugs))
            ->with(['chapters' => function ($q) {
                $q->published()->orderBy('number', 'desc')->take(1);
            }])
            ->get()
            ->keyBy('slug');

        $todayIso = Carbon::now('Europe/Paris')->dayOfWeekIso; // 1 (Lundi) -> 7 (Dimanche)

        $result = [];
        foreach ($config as $key => $day) {
            $hydratedItems = [];
            foreach ($day['slots'] as $slot) {
                $manga = $mangas->get($slot['slug']);
                if ($manga) {
                    $hydratedItems[] = [
                        'manga' => $manga,
                        'time' => $slot['time'],
                        'status_label' => $slot['status_label'],
                        'is_priority' => $slot['is_priority'],
                        'latest_chapter' => $manga->chapters->first(),
                    ];
                }
            }

            $result[$key] = [
                'day_key' => $day['day_key'],
                'label' => $day['label'],
                'short' => $day['short'],
                'iso_day' => $day['iso_day'],
                'is_today' => ($day['iso_day'] === $todayIso),
                'items' => $hydratedItems,
            ];
        }

        return $result;
    }

    /**
     * Retourne la clé du jour d'aujourd'hui
     */
    public static function getTodayKey(): string
    {
        $map = [
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ];

        $todayIso = Carbon::now('Europe/Paris')->dayOfWeekIso;
        return $map[$todayIso] ?? 'lundi';
    }
}
