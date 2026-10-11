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

    /**
     * Liste des Bêtes Spirituelles de l'Ombre disponibles
     */
    public static function getSpiritualBeasts(): array
    {
        return [
            'shadow_crow' => [
                'key' => 'shadow_crow',
                'name' => 'Corbeau des Ombres',
                'element' => 'Ténèbres & Illusion',
                'icon' => '🦅',
                'color' => '#8B5CF6',
                'secondary_color' => '#6D28D9',
                'personality' => 'Observateur silencieux et stratège impitoyable.',
                'description' => "Guide spirituel ancestral des guerriers de l'ombre, guidant votre vision à travers les manuscrits interdits.",
                'stages' => [
                    1 => [
                        'stage' => 1,
                        'name' => 'Oisillon des Brumes',
                        'min_chapters' => 0,
                        'icon' => '🪶',
                        'description' => 'Un jeune oisillon aux plumes de fumée noire qui commence à éveiller sa perception du Qi.',
                    ],
                    2 => [
                        'stage' => 2,
                        'name' => 'Messager d\'Ébène',
                        'min_chapters' => 20,
                        'icon' => '🦅',
                        'description' => 'Ses serres s\'imprègnent d\'énergie spirituelle. Il repère les nouveaux chapitres avant tout le monde.',
                    ],
                    3 => [
                        'stage' => 3,
                        'name' => 'Corbeau Spectral des Abysses',
                        'min_chapters' => 100,
                        'icon' => '🔮',
                        'description' => 'Son croassement fend l\'espace spirituel. Une aura violette enveloppe son maître.',
                    ],
                    4 => [
                        'stage' => 4,
                        'name' => 'Seigneur Corbeau du Néant Primordial',
                        'min_chapters' => 250,
                        'icon' => '👑',
                        'description' => 'Forme mythique ultime : les ombres du Murim se plient à son commandement.',
                    ],
                ],
            ],
            'lunar_wolf' => [
                'key' => 'lunar_wolf',
                'name' => 'Loup Lunaire d\'Argent',
                'element' => 'Givre & Instinct Sanguin',
                'icon' => '🐺',
                'color' => '#38BDF8',
                'secondary_color' => '#0284C7',
                'personality' => 'Fier, loyal et infatigable lecteur nocturne.',
                'description' => "Protecteur féroce qui puise sa force dans les lectures sous la pleine lune.",
                'stages' => [
                    1 => [
                        'stage' => 1,
                        'name' => 'Louveteau d\'Argent',
                        'min_chapters' => 0,
                        'icon' => '🐾',
                        'description' => 'Un jeune loup aux yeux d\'azur qui apprend à humer le parfum de l\'encre des rouleaux.',
                    ],
                    2 => [
                        'stage' => 2,
                        'name' => 'Traqueur Nocturne',
                        'min_chapters' => 20,
                        'icon' => '🐺',
                        'description' => 'Chasseur agile bravant les tempêtes de Qi avec une endurance remarquable.',
                    ],
                    3 => [
                        'stage' => 3,
                        'name' => 'Alpha du Croissant Céleste',
                        'min_chapters' => 100,
                        'icon' => '❄️',
                        'description' => 'Son hurlement résonne à travers les montagnes du Jianghu, décuplant votre concentration.',
                    ],
                    4 => [
                        'stage' => 4,
                        'name' => 'Fenrir Ancestral de Glace Éternelle',
                        'min_chapters' => 250,
                        'icon' => '🌌',
                        'description' => 'Incarnation du givre céleste. Une présence colossale qui glace le sang des imposteurs.',
                    ],
                ],
            ],
            'celestial_fox' => [
                'key' => 'celestial_fox',
                'name' => 'Renard Céleste de Braise',
                'element' => 'Feu Sacré & Charisme',
                'icon' => '🦊',
                'color' => '#F59E0B',
                'secondary_color' => '#D97706',
                'personality' => 'Mystérieux, raffiné et doté d\'une sagesse millénaire.',
                'description' => "Créature légendaire dont chaque queue débloquée confère une compréhension transcendante des intrigues.",
                'stages' => [
                    1 => [
                        'stage' => 1,
                        'name' => 'Renardeau de Braise',
                        'min_chapters' => 0,
                        'icon' => '🦊',
                        'description' => 'Une étincelle vive et espiègle aux poils roux flamboyants.',
                    ],
                    2 => [
                        'stage' => 2,
                        'name' => 'Esprit Renard à Trois Queues',
                        'min_chapters' => 20,
                        'icon' => '🔥',
                        'description' => 'Trois flammes mystiques dansent autour de lui, réchauffant le Dantian de son disciple.',
                    ],
                    3 => [
                        'stage' => 3,
                        'name' => 'Kitsune Céleste à Six Queues',
                        'min_chapters' => 100,
                        'icon' => '✨',
                        'description' => 'Maître de l\'illusion et des secrets anciens, illuminant chaque case de webtoon.',
                    ],
                    4 => [
                        'stage' => 4,
                        'name' => 'Divinité Renard à Neuf Queues',
                        'min_chapters' => 250,
                        'icon' => '☀️',
                        'description' => 'Le sommet de l\'illumination. Ses neuf queues enflammées couronnent le sommet du panthéon.',
                    ],
                ],
            ],
            'azure_dragon' => [
                'key' => 'azure_dragon',
                'name' => 'Dragon Azur de Foudre',
                'element' => 'Foudre Céleste & Puissance',
                'icon' => '🐉',
                'color' => '#10B981',
                'secondary_color' => '#059669',
                'personality' => 'Majestueux, dominateur et assoiffé d\'ascension suprême.',
                'description' => "Le grand Dragon Oriental des mythes fondateurs, maître des tempêtes et de la foudre bleue.",
                'stages' => [
                    1 => [
                        'stage' => 1,
                        'name' => 'Vipère de Foudre',
                        'min_chapters' => 0,
                        'icon' => '⚡',
                        'description' => 'Un jeune serpent imprégné de crépitements électriques dans le bassin de Qi.',
                    ],
                    2 => [
                        'stage' => 2,
                        'name' => 'Wyrm du Vent Azur',
                        'min_chapters' => 20,
                        'icon' => '🌪️',
                        'description' => 'Des cornes d\'émeraude poussent sur son crâne. Il fend les nuages à la vitesse de l\'éclair.',
                    ],
                    3 => [
                        'stage' => 3,
                        'name' => 'Dragon Tempétueux Ancestral',
                        'min_chapters' => 100,
                        'icon' => '🐉',
                        'description' => 'Le tonnerre gronde à chaque page tournée. Sa protection martiale est inébranlable.',
                    ],
                    4 => [
                        'stage' => 4,
                        'name' => 'Souverain Dragon Primordial des Cieux',
                        'min_chapters' => 250,
                        'icon' => '⚡👑',
                        'description' => 'Le maître de la création martiale. Rien ne peut entraver son éveil absolu.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Récupère les données et le stade d'évolution actuel d'une bête selon le nombre de chapitres
     */
    public static function getBeastStatus(string $beastKey, int $readCount): array
    {
        $beasts = self::getSpiritualBeasts();
        $beast = $beasts[$beastKey] ?? $beasts['shadow_crow'];

        $currentStage = $beast['stages'][1];
        $nextStage = null;

        foreach ($beast['stages'] as $stNum => $stageData) {
            if ($readCount >= $stageData['min_chapters']) {
                $currentStage = $stageData;
                $nextStage = $beast['stages'][$stNum + 1] ?? null;
            }
        }

        $percent = 100;
        $chaptersNeeded = 0;
        if ($nextStage) {
            $range = $nextStage['min_chapters'] - $currentStage['min_chapters'];
            $progress = max(0, $readCount - $currentStage['min_chapters']);
            $percent = $range > 0 ? min(100, round(($progress / $range) * 100)) : 100;
            $chaptersNeeded = max(0, $nextStage['min_chapters'] - $readCount);
        }

        return [
            'beast' => $beast,
            'current_stage' => $currentStage,
            'next_stage' => $nextStage,
            'percent' => $percent,
            'chapters_needed' => $chaptersNeeded,
        ];
    }

    /**
     * Liste des Cadres d'Avatar & Auras Animées
     */
    public static function getAvatarFrames(int $readCount = 0): array
    {
        $frames = [
            'novice' => [
                'key' => 'novice',
                'name' => 'Discrétion du Disciple',
                'min_chapters' => 0,
                'color' => '#6B7280',
                'badge' => '🥋',
                'css_class' => 'frame-novice',
                'border_style' => 'border-white/20',
                'description' => 'Cadre sobre et épuré des premiers pas dans la secte.',
            ],
            'crimson_flame' => [
                'key' => 'crimson_flame',
                'name' => 'Flamme Écarlate',
                'min_chapters' => 20,
                'color' => '#EF4444',
                'badge' => '🔥',
                'css_class' => 'frame-crimson-flame',
                'border_style' => 'border-red-500 shadow-[0_0_15px_rgba(239,68,68,0.5)]',
                'description' => 'Lueur ardente de Qi martial embrasé.',
            ],
            'purple_lightning' => [
                'key' => 'purple_lightning',
                'name' => 'Foudre Pourpre',
                'min_chapters' => 50,
                'color' => '#8B5CF6',
                'badge' => '⚡',
                'css_class' => 'frame-purple-lightning',
                'border_style' => 'border-purple-500 shadow-[0_0_18px_rgba(139,92,246,0.6)]',
                'description' => 'Arcs électriques mystiques crépitant autour de votre esprit.',
            ],
            'golden_celestial' => [
                'key' => 'golden_celestial',
                'name' => 'Aura Céleste Dorée',
                'min_chapters' => 100,
                'color' => '#F59E0B',
                'badge' => '✨',
                'css_class' => 'frame-golden-celestial',
                'border_style' => 'border-amber-400 shadow-[0_0_22px_rgba(245,158,11,0.7)]',
                'description' => 'Reflets d\'or pur impériaux accordés aux Grands Maîtres.',
            ],
            'abyssal_sovereign' => [
                'key' => 'abyssal_sovereign',
                'name' => 'Nébuleuse Abyssale',
                'min_chapters' => 250,
                'color' => '#EC4899',
                'badge' => '🌌',
                'css_class' => 'frame-abyssal-sovereign',
                'border_style' => 'border-pink-500 shadow-[0_0_25px_rgba(236,72,153,0.8)]',
                'description' => 'Poussière d\'étoiles cosmiques réservée aux Transcendants.',
            ],
            'divine_sovereign' => [
                'key' => 'divine_sovereign',
                'name' => 'Couronne du Dieu Martial',
                'min_chapters' => 500,
                'color' => '#FFD700',
                'badge' => '👑',
                'css_class' => 'frame-divine-sovereign',
                'border_style' => 'border-yellow-300 shadow-[0_0_30px_rgba(255,215,0,0.9)] animate-pulse',
                'description' => 'Le sommet légendaire absolu : aura divine incandescente.',
            ],
        ];

        foreach ($frames as &$f) {
            $f['unlocked'] = ($readCount >= $f['min_chapters']);
        }

        return $frames;
    }

    /**
     * Traite et met à jour le streak et le bouclier spirituel de l'utilisateur
     */
    public static function processStreakAndShields(\App\Models\User $user): array
    {
        $today = \Carbon\Carbon::now('Europe/Paris')->format('Y-m-d');
        $yesterday = \Carbon\Carbon::now('Europe/Paris')->subDay()->format('Y-m-d');
        $lastReadDate = $user->last_read_at ? \Carbon\Carbon::parse($user->last_read_at)->timezone('Europe/Paris')->format('Y-m-d') : null;

        $shieldUsed = false;
        $shieldEarned = false;

        if (!$lastReadDate) {
            // Première lecture
            $user->streak = 1;
        } elseif ($lastReadDate === $today) {
            // Déjà lu aujourd'hui, rien ne change
        } elseif ($lastReadDate === $yesterday) {
            // Lecture consécutive normale
            $user->streak = ($user->streak ?? 0) + 1;

            // Tous les 7 jours de streak consécutif, gain d'un Bouclier de Qi (max 2)
            if ($user->streak % 7 === 0 && ($user->qi_shields ?? 0) < 2) {
                $user->qi_shields = ($user->qi_shields ?? 0) + 1;
                $shieldEarned = true;
            }
        } else {
            // Un ou plusieurs jours ont été sautés
            if (($user->qi_shields ?? 0) > 0) {
                // Sauvegarde de la flamme grâce au Bouclier Spirituel de Qi !
                $user->qi_shields = max(0, $user->qi_shields - 1);
                $user->streak = max(1, ($user->streak ?? 1) + 1);
                $shieldUsed = true;
            } else {
                // Rupture de flamme
                $user->streak = 1;
            }
        }

        $user->last_read_at = now();
        $user->save();

        return [
            'streak' => $user->streak,
            'qi_shields' => $user->qi_shields ?? 0,
            'shield_used' => $shieldUsed,
            'shield_earned' => $shieldEarned,
        ];
    }
}


