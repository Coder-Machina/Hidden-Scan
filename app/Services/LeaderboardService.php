<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * Retourne le classement des meilleurs cultivateurs / lecteurs
     */
    public static function getTopCultivators(int $limit = 30, ?User $currentUser = null): array
    {
        // Récupérer uniquement les utilisateurs réels non-staff ayant lu au moins un chapitre ou gagné des points
        $realUsers = User::where('is_banned', false)
            ->withCount(['readingProgress' => function ($q) {
                $q->where('is_read', true);
            }])
            ->get()
            ->filter(function ($u) {
                // Les admins, modos et membres du staff sont strictement hors classement (même s'ils lisent)
                if (method_exists($u, 'isStaffOrAdmin') && $u->isStaffOrAdmin()) {
                    return false;
                }

                $readCount = (int) $u->reading_progress_count;
                $xp = (int) ($u->xp ?? ($readCount * 15 + ($u->streak ?? 0) * 10));
                return $readCount > 0 || $xp > 0;
            })
            ->map(function ($u) {
                $readCount = (int) $u->reading_progress_count;
                $xp = (int) ($u->xp ?? ($readCount * 15 + ($u->streak ?? 0) * 10));
                $murim = MurimRankService::calculateStatus($readCount, (int) ($u->streak ?? 0));

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'avatar_url' => $u->avatar_url,
                    'is_staff' => (bool) $u->staff_badge,
                    'staff_badge' => $u->staff_badge,
                    'xp' => $xp,
                    'read_count' => $readCount,
                    'streak' => (int) ($u->streak ?? 0),
                    'murim_title' => $murim['current']['title'],
                    'murim_badge' => $murim['current']['badge'],
                    'murim_icon' => $murim['current']['icon'] ?? 'swords',
                    'murim_aura' => $murim['current']['aura'],
                    'murim_color' => $murim['current']['color'],
                    'level' => $u->level,
                    'is_current_user' => false,
                ];
            });

        // Trier uniquement les lecteurs actifs
        $all = $realUsers
            ->sortByDesc(fn ($item) => $item['xp'] * 1000 + $item['read_count'])
            ->values();

        $ranked = [];
        $currentPosition = null;

        foreach ($all as $index => $item) {
            $rankNum = $index + 1;
            $item['rank'] = $rankNum;

            if ($currentUser && $item['id'] === $currentUser->id) {
                $item['is_current_user'] = true;
                $currentPosition = $rankNum;
            }

            $ranked[] = $item;
        }

        // Si l'utilisateur connecté est un admin ou membre du staff : strictement hors-classement
        if ($currentUser && method_exists($currentUser, 'isStaffOrAdmin') && $currentUser->isStaffOrAdmin()) {
            $currentPosition = null;
        } elseif ($currentUser && !$currentPosition) {
            // Si le lecteur connecté a lu des chapitres mais n'est pas dans le top visible
            $userRead = $currentUser->readingProgress()->where('is_read', true)->count();
            $userXp = (int) ($currentUser->xp ?? ($userRead * 15));
            if ($userRead > 0 || $userXp > 0) {
                $higherCount = $all->where('xp', '>', $userXp)->count();
                $currentPosition = $higherCount + 1;
            } else {
                $currentPosition = null;
            }
        }

        $podium = count($ranked) >= 3 ? array_slice($ranked, 0, 3) : [];
        $list = count($ranked) >= 3 ? array_slice($ranked, 3, $limit - 3) : $ranked;

        return [
            'podium' => $podium,
            'list' => $list,
            'all' => array_slice($ranked, 0, $limit),
            'total_cultivators' => count($ranked),
            'current_user_rank' => $currentPosition,
        ];
    }

    /**
     * Génère la grille du calendrier d'assiduité (Suivi de lecture / Heatmap) pour un utilisateur
     */
    public static function getMonthlyHeatmap(?User $user, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?: (int) Carbon::now('Europe/Paris')->year;
        $month = $month ?: (int) Carbon::now('Europe/Paris')->month;

        $startDate = Carbon::createFromDate($year, $month, 1, 'Europe/Paris')->startOfDay();
        $daysInMonth = $startDate->daysInMonth;
        $monthLabel = $startDate->translatedFormat('F Y');

        // Récupérer les chapitres lus par jour depuis la base de données
        $readsByDay = [];
        if ($user) {
            $rows = $user->readingProgress()
                ->where('is_read', true)
                ->whereBetween('created_at', [$startDate, $startDate->copy()->endOfMonth()])
                ->selectRaw('DATE(created_at) as read_date, COUNT(*) as cnt')
                ->groupBy('read_date')
                ->pluck('cnt', 'read_date')
                ->toArray();
            $readsByDay = $rows;
        }

        $days = [];
        $totalMonthChapters = 0;
        $activeDays = 0;
        $bestDay = 0;
        $bestDayLabel = null;

        $todayStr = Carbon::now('Europe/Paris')->format('Y-m-d');

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($year, $month, $d, 'Europe/Paris');
            $dateStr = $date->format('Y-m-d');
            $count = (int) ($readsByDay[$dateStr] ?? 0);

            $totalMonthChapters += $count;
            if ($count > 0) {
                $activeDays++;
                if ($count > $bestDay) {
                    $bestDay = $count;
                    $bestDayLabel = $date->format('j M');
                }
            }

            // Déterminer le niveau d'intensité de Qi (0 à 4)
            $intensity = 0;
            if ($count >= 8) {
                $intensity = 4; // Cramoisi incandescent
            } elseif ($count >= 4) {
                $intensity = 3; // Écarlate vif
            } elseif ($count >= 2) {
                $intensity = 2; // Rouge braise
            } elseif ($count >= 1) {
                $intensity = 1; // Lueur de Qi
            }

            $days[] = [
                'day_number' => $d,
                'date' => $dateStr,
                'day_of_week_iso' => $date->dayOfWeekIso, // 1 (Lun) -> 7 (Dim)
                'chapters_read' => $count,
                'intensity' => $intensity,
                'is_today' => ($dateStr === $todayStr),
                'is_future' => ($dateStr > $todayStr),
            ];
        }

        // Offset de départ (Lundi = 1 => 0 cases vides, Dimanche = 7 => 6 cases vides)
        $leadingEmptyDays = $startDate->dayOfWeekIso - 1;

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => ucfirst($monthLabel),
            'leading_empty_days' => $leadingEmptyDays,
            'days' => $days,
            'total_chapters' => $totalMonthChapters,
            'active_days' => $activeDays,
            'best_day_count' => $bestDay,
            'best_day_label' => $bestDayLabel ?: '—',
            'assiduity_rate' => $daysInMonth > 0 ? round(($activeDays / $daysInMonth) * 100) : 0,
        ];
    }
}
