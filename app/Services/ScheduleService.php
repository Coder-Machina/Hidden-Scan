<?php

namespace App\Services;

use App\Models\Manga;
use App\Models\ReleaseSchedule;
use Carbon\Carbon;

class ScheduleService
{
    /**
     * Configuration par défaut du planning hebdomadaire
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
     * Initialise les sorties par défaut si la table est vide
     */
    public static function seedDefaultsIfNeeded(): void
    {
        try {
            if (ReleaseSchedule::count() > 0) {
                return;
            }

            $config = self::getScheduleConfig();
            foreach ($config as $dayKey => $day) {
                $sort = 0;
                foreach ($day['slots'] as $slot) {
                    $manga = Manga::where('slug', $slot['slug'])->first();
                    if ($manga) {
                        ReleaseSchedule::create([
                            'day_of_week' => $dayKey,
                            'manga_id' => $manga->id,
                            'release_time' => $slot['time'],
                            'status_label' => $slot['status_label'],
                            'is_priority' => (bool) ($slot['is_priority'] ?? false),
                            'is_active' => true,
                            'sort_order' => $sort++,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorer si la table n'est pas encore migrée
        }
    }

    /**
     * Retourne le planning complet hydraté avec les modèles Manga depuis la DB
     */
    public static function getHydratedSchedule(): array
    {
        self::seedDefaultsIfNeeded();

        $daysDef = [
            'lundi' => ['label' => 'Lundi', 'short' => 'Lun', 'iso' => 1],
            'mardi' => ['label' => 'Mardi', 'short' => 'Mar', 'iso' => 2],
            'mercredi' => ['label' => 'Mercredi', 'short' => 'Mer', 'iso' => 3],
            'jeudi' => ['label' => 'Jeudi', 'short' => 'Jeu', 'iso' => 4],
            'vendredi' => ['label' => 'Vendredi', 'short' => 'Ven', 'iso' => 5],
            'samedi' => ['label' => 'Samedi', 'short' => 'Sam', 'iso' => 6],
            'dimanche' => ['label' => 'Dimanche', 'short' => 'Dim', 'iso' => 7],
        ];

        $todayIso = Carbon::now('Europe/Paris')->dayOfWeekIso; // 1 (Lundi) -> 7 (Dimanche)

        $schedules = ReleaseSchedule::with(['manga' => function ($q) {
            $q->with(['chapters' => function ($c) {
                $c->published()->orderBy('number', 'desc')->take(1);
            }]);
        }])
        ->active()
        ->orderBy('sort_order', 'asc')
        ->orderBy('id', 'asc')
        ->get()
        ->groupBy('day_of_week');

        $result = [];
        foreach ($daysDef as $key => $meta) {
            $daySchedules = $schedules->get($key, collect());
            $items = [];

            foreach ($daySchedules as $sched) {
                if ($sched->manga) {
                    $items[] = [
                        'manga' => $sched->manga,
                        'time' => $sched->release_time ?: '18h00',
                        'status_label' => $sched->status_label ?: 'Régulier',
                        'is_priority' => (bool) $sched->is_priority,
                        'latest_chapter' => $sched->manga->chapters->first(),
                    ];
                }
            }

            $result[$key] = [
                'day_key' => $key,
                'label' => $meta['label'],
                'short' => $meta['short'],
                'iso_day' => $meta['iso'],
                'is_today' => ($meta['iso'] === $todayIso),
                'items' => $items,
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
