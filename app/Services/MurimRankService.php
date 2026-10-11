<?php

namespace App\Services;

class MurimRankService
{
    public static function getRanks(): array
    {
        return [
            1 => [
                'id' => 1,
                'title' => 'Disciple Tiers-Rang',
                'hangeul' => '三流武士',
                'pinyin' => 'Sanryu',
                'badge' => '🥉',
                'min_chapters' => 0,
                'max_chapters' => 5,
                'color' => '#A0A0C0',
                'aura' => 'Aura Naissante',
                'description' => "Vos méridiens s'ouvrent à peine. Vous faites vos premiers pas dans les manuscrits secrets du monde martial.",
                'quote' => 'Le chemin de mille lieues commence par un seul chapitre.',
            ],
            2 => [
                'id' => 2,
                'title' => 'Guerrier de Second Rang',
                'hangeul' => '二流武士',
                'pinyin' => 'Iryu',
                'badge' => '🥈',
                'min_chapters' => 6,
                'max_chapters' => 20,
                'color' => '#818CF8',
                'aura' => 'Qi Fluant',
                'description' => "Votre Qi intérieur commence à circuler sans entrave. Vous surpassez désormais les simples roturiers.",
                'quote' => 'La lame commence à s\'affûter, le souffle s\'harmonise.',
            ],
            3 => [
                'id' => 3,
                'title' => 'Guerrier de Premier Rang',
                'hangeul' => '一流武士',
                'pinyin' => 'Ilryu',
                'badge' => '🥇',
                'min_chapters' => 21,
                'max_chapters' => 50,
                'color' => '#38BDF8',
                'aura' => 'Maîtrise Élémentaire',
                'description' => "Maîtrise solide du Dantian. Les techniques fondamentales n'ont plus aucun secret pour votre esprit.",
                'quote' => 'Une réputation commence à résonner dans les auberges du Jianghu.',
            ],
            4 => [
                'id' => 4,
                'title' => 'Expert du Qi Intérieur',
                'hangeul' => '絶頂',
                'pinyin' => 'Peak Master',
                'badge' => '⚔️',
                'min_chapters' => 51,
                'max_chapters' => 100,
                'color' => '#F59E0B',
                'aura' => 'Épée de Qi',
                'description' => "L'Épée de Qi jaillit de vos doigts. Peu d'experts osent croiser votre regard lors d'une lecture nocturne.",
                'quote' => 'Couper le vent, fendre les ténèbres sans faire un bruit.',
            ],
            5 => [
                'id' => 5,
                'title' => 'Grand Maître Martial',
                'hangeul' => '超絶頂',
                'pinyin' => 'Transcendental Master',
                'badge' => '🥋',
                'min_chapters' => 101,
                'max_chapters' => 250,
                'color' => '#FF2A55',
                'aura' => 'Volonté Suprême',
                'description' => "Fusion absolue de l'aura et du corps. Les grands anciens des clans s'inclinent avec respect à votre passage.",
                'quote' => 'Le véritable maître ne dégaine pas ; le monde plie sous sa présence.',
            ],
            6 => [
                'id' => 6,
                'title' => 'Transcendant Céleste',
                'hangeul' => '化境',
                'pinyin' => 'Profound Realm',
                'badge' => '⚡',
                'min_chapters' => 251,
                'max_chapters' => 500,
                'color' => '#8B5CF6',
                'aura' => 'Ascension Divine',
                'description' => "Vous avez brisé les chaînes de la condition mortelle. Les lois du Qi s'adaptent à vos désirs de lecture.",
                'quote' => 'Au-delà du ciel se trouve un autre ciel.',
            ],
            7 => [
                'id' => 7,
                'title' => 'Dieu Martial des Ombres',
                'hangeul' => '玄境',
                'pinyin' => 'Divine Sovereign',
                'badge' => '👑',
                'min_chapters' => 501,
                'max_chapters' => 999999,
                'color' => '#FFD700',
                'aura' => 'Souverain Éternel',
                'description' => "L'incarnation légendaire du Tao Martial. Vous régnez sans partage sur l'ensemble du Murim et des Ombres.",
                'quote' => 'Le début et la fin. Le Souverain de tous les chapitres.',
            ],
        ];
    }

    /**
     * Calcule le statut de rang d'un utilisateur à partir de son nombre de chapitres lus
     */
    public static function calculateStatus(int $readCount, int $streak = 0): array
    {
        $ranks = self::getRanks();
        $currentRank = $ranks[1];
        $nextRank = null;

        foreach ($ranks as $id => $rank) {
            if ($readCount >= $rank['min_chapters']) {
                $currentRank = $rank;
                $nextRank = $ranks[$id + 1] ?? null;
            }
        }

        $percent = 100;
        $chaptersNeeded = 0;

        if ($nextRank) {
            $range = $nextRank['min_chapters'] - $currentRank['min_chapters'];
            $progress = max(0, $readCount - $currentRank['min_chapters']);
            $percent = $range > 0 ? min(100, round(($progress / $range) * 100)) : 100;
            $chaptersNeeded = max(0, $nextRank['min_chapters'] - $readCount);
        }

        return [
            'read_count' => $readCount,
            'streak' => $streak,
            'current' => $currentRank,
            'next' => $nextRank,
            'percent' => $percent,
            'chapters_needed' => $chaptersNeeded,
            'all_ranks' => array_values($ranks),
        ];
    }

    /**
     * Retourne directement le rang actuel pour un nombre de chapitres lus
     */
    public static function getRankForCount(int $readCount): array
    {
        return self::calculateStatus($readCount)['current'];
    }
}

