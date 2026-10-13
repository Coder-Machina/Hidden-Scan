<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class MurimRankService
{
    /**
     * Les 10 Royaumes de Cultivation Martiale (100% Français, dynamiques et immersifs)
     */
    public static function getRanks(): array
    {
        return [
            1 => [
                'id' => 1,
                'title' => 'Initié Martial',
                'hangeul' => '三流',
                'pinyin' => 'San Liu (3e Rang)',
                'badge' => '🥋',
                'min_xp' => 0,
                'max_xp' => 150,
                'min_chapters' => 0,
                'max_chapters' => 9,
                'color' => '#94A3B8',
                'aura' => 'Aura Naissante',
                'description' => "L'éveil spirituel commence. Vos méridiens s'ouvrent à l'énergie vitale des récits.",
                'quote' => 'Un voyage de dix mille lieues commence par un premier chapitre.',
            ],
            2 => [
                'id' => 2,
                'title' => 'Disciple Émérite',
                'hangeul' => '二流',
                'pinyin' => 'Er Liu (2nd Rang)',
                'badge' => '🥉',
                'min_xp' => 151,
                'max_xp' => 450,
                'min_chapters' => 10,
                'max_chapters' => 29,
                'color' => '#60A5FA',
                'aura' => 'Flux de Qi Naissant',
                'description' => 'Le Qi circule désormais avec fluidité dans vos méridiens. Vous dépassez le commun des mortels.',
                'quote' => 'La discipline quotidienne forge l\'esprit et affine la lame.',
            ],
            3 => [
                'id' => 3,
                'title' => 'Guerrier d\'Élite',
                'hangeul' => '一流',
                'pinyin' => 'Yi Liu (1er Rang)',
                'badge' => '🥈',
                'min_xp' => 451,
                'max_xp' => 950,
                'min_chapters' => 30,
                'max_chapters' => 59,
                'color' => '#38BDF8',
                'aura' => 'Lame des Ombres',
                'description' => "Votre volonté est aussi tranchante que l'acier. Vos techniques de lecture s'accélèrent.",
                'quote' => 'Ni le vent ni l\'ombre ne peuvent troubler la concentration du guerrier.',
            ],
            4 => [
                'id' => 4,
                'title' => 'Adepte du Qi Pur',
                'hangeul' => '絶頂',
                'pinyin' => 'Jueding (Sommet)',
                'badge' => '🥇',
                'min_xp' => 951,
                'max_xp' => 1700,
                'min_chapters' => 60,
                'max_chapters' => 109,
                'color' => '#34D399',
                'aura' => 'Circulation Céleste',
                'description' => 'Votre Dantian condense un Qi pur et étincelant. Chaque lecture renforce votre essence.',
                'quote' => 'Le Qi nourrit l\'âme comme l\'encre sublime le parchemin.',
            ],
            5 => [
                'id' => 5,
                'title' => 'Maître Martial',
                'hangeul' => '超絶頂',
                'pinyin' => 'Chao Jueding (Transcendant)',
                'badge' => '⚔️',
                'min_xp' => 1701,
                'max_xp' => 2800,
                'min_chapters' => 110,
                'max_chapters' => 179,
                'color' => '#F59E0B',
                'aura' => 'Aura Déchaînée',
                'description' => 'Une aura écrasante émane de votre présence. Les auberges du Jianghu chantent vos exploits.',
                'quote' => 'Fendre les cieux et dominer la nuit d\'un simple regard.',
            ],
            6 => [
                'id' => 6,
                'title' => 'Grand Maître Vénérable',
                'hangeul' => '化境',
                'pinyin' => 'Huajing (Transformation)',
                'badge' => '🌪️',
                'min_xp' => 2801,
                'max_xp' => 4300,
                'min_chapters' => 180,
                'max_chapters' => 279,
                'color' => '#FB923C',
                'aura' => 'Domination Spirituelle',
                'description' => "Les patriarches des grandes sectes s'inclinent avec déférence. Votre maîtrise approche l'absolu.",
                'quote' => 'Le maître ne cherche pas le conflit : le monde s\'harmonise autour de lui.',
            ],
            7 => [
                'id' => 7,
                'title' => 'Seigneur des Ombres',
                'hangeul' => '極境',
                'pinyin' => 'Jijing (Apex)',
                'badge' => '🔮',
                'min_xp' => 4301,
                'max_xp' => 6500,
                'min_chapters' => 280,
                'max_chapters' => 419,
                'color' => '#EC4899',
                'aura' => 'Transcendance Mortelle',
                'description' => 'Vous avez brisé les limites du corps humain. Vous lisez à la vitesse de la pensée.',
                'quote' => 'Fouler les ténèbres sans jamais être corrompu par elles.',
            ],
            8 => [
                'id' => 8,
                'title' => 'Transcendant Céleste',
                'hangeul' => '玄境',
                'pinyin' => 'Xuanjing (Mystique)',
                'badge' => '⚡',
                'min_xp' => 6501,
                'max_xp' => 9500,
                'min_chapters' => 420,
                'max_chapters' => 599,
                'color' => '#8B5CF6',
                'aura' => 'Harmonie Primordiale',
                'description' => 'Harmonie complète avec les lois secrètes du monde martial. Votre nom résonne dans tous les mondes.',
                'quote' => 'Au-dessus des nuages se trouve la lumière éternelle.',
            ],
            9 => [
                'id' => 9,
                'title' => 'Souverain Martial',
                'hangeul' => '生死境',
                'pinyin' => 'Shengsijing (Vie & Mort)',
                'badge' => '👑',
                'min_xp' => 9501,
                'max_xp' => 14000,
                'min_chapters' => 600,
                'max_chapters' => 849,
                'color' => '#E11D48',
                'aura' => 'Maître des Cieux',
                'description' => "Le sommet de l'art martial. Vous contrôlez les flux de la destinée et régnez sur l'arène.",
                'quote' => 'Un seul mot suffit à commander mille légions de disciples.',
            ],
            10 => [
                'id' => 10,
                'title' => 'Dieu Martial Suprême',
                'hangeul' => '神境',
                'pinyin' => 'Shenjing (Divin)',
                'badge' => '☀️',
                'min_xp' => 14001,
                'max_xp' => 999999,
                'min_chapters' => 850,
                'max_chapters' => 99999,
                'color' => '#FFD700',
                'aura' => 'L\'Apex Immortel',
                'description' => "L'Apex Divin du Jianghu. Vous avez atteint l'immortalité spirituelle et transcendé l'univers.",
                'quote' => 'Je suis le début, la fin et la flamme éternelle des arts martiaux.',
            ],
        ];
    }

    /**
     * Calcule le statut de rang d'un utilisateur à partir de son Qi (XP) ou de ses lectures
     */
    public static function calculateStatus(int $readCount, int $streak = 0, ?int $customXp = null, ?User $user = null): array
    {
        $ranks = self::getRanks();

        // Calcul du Qi (XP) :
        $xp = $customXp ?? ($user ? ($user->xp ?? 0) : 0);
        if ($xp <= 0) {
            $commentsCount = $user ? $user->comments()->count() : 0;
            $favCount = $user ? $user->favorites()->count() : 0;
            $xp = ($readCount * 15) + ($streak * 25) + ($commentsCount * 20) + ($favCount * 10);
        }

        $currentRank = $ranks[1];
        $nextRank = null;

        foreach ($ranks as $id => $rank) {
            if ($xp >= $rank['min_xp']) {
                $currentRank = $rank;
                $nextRank = $ranks[$id + 1] ?? null;
            }
        }

        $percent = 100;
        $xpNeeded = 0;
        $chaptersNeeded = 0;

        if ($nextRank) {
            $range = $nextRank['min_xp'] - $currentRank['min_xp'];
            $progress = max(0, $xp - $currentRank['min_xp']);
            $percent = $range > 0 ? min(100, round(($progress / $range) * 100)) : 100;
            $xpNeeded = max(0, $nextRank['min_xp'] - $xp);
            $chaptersNeeded = (int) ceil($xpNeeded / 15);
        }

        // Quêtes quotidiennes de cultivation
        $quests = self::getDailyQuests($readCount, $streak, $user);

        return [
            'read_count' => $readCount,
            'streak' => $streak,
            'xp' => $xp,
            'level' => max(1, (int) floor($xp / 50) + 1),
            'current' => $currentRank,
            'next' => $nextRank,
            'percent' => $percent,
            'xp_needed' => $xpNeeded,
            'chapters_needed' => $chaptersNeeded,
            'quests' => $quests,
            'all_ranks' => array_values($ranks),
        ];
    }

    /**
     * Alias de calculateStatus pour compatibilité
     */
    public static function calculate(int $readCount, int $streak = 0, ?int $xp = null, ?User $user = null): array
    {
        return self::calculateStatus($readCount, $streak, $xp, $user);
    }

    /**
     * Quêtes quotidiennes de cultivation spirituelle
     */
    public static function getDailyQuests(int $readCount = 0, int $streak = 0, ?User $user = null): array
    {
        $hasReadToday = false;
        $hasCommentedToday = false;
        $todayReadChapters = 0;

        if ($user) {
            $today = Carbon::now('Europe/Paris')->format('Y-m-d');
            $hasReadToday = $user->last_read_at && Carbon::parse($user->last_read_at)->timezone('Europe/Paris')->format('Y-m-d') === $today;

            $todayReadChapters = $user->readingProgress()
                ->whereDate('updated_at', Carbon::today('Europe/Paris'))
                ->count();
            if ($hasReadToday && $todayReadChapters === 0) {
                $todayReadChapters = 1;
            }

            $hasCommentedToday = $user->comments()
                ->whereDate('created_at', Carbon::today('Europe/Paris'))
                ->exists();
        } else {
            $hasReadToday = $readCount > 0;
            $todayReadChapters = min($readCount, 3);
        }

        return [
            [
                'id' => 'meditation',
                'title' => 'Méditation Quotidienne',
                'icon' => '📜',
                'description' => 'Lire au moins 1 chapitre aujourd\'hui.',
                'reward_xp' => 30,
                'current' => $hasReadToday ? 1 : 0,
                'target' => 1,
                'completed' => $hasReadToday,
            ],
            [
                'id' => 'flame_keeper',
                'title' => 'Gardien de la Flamme',
                'icon' => '🔥',
                'description' => 'Maintenir une flamme consécutive active (streak ≥ 1).',
                'reward_xp' => 40,
                'current' => $streak > 0 ? 1 : 0,
                'target' => 1,
                'completed' => $streak > 0,
            ],
            [
                'id' => 'jianghu_echo',
                'title' => 'Écho du Jianghu',
                'icon' => '💬',
                'description' => 'Partager vos impressions en postant 1 commentaire.',
                'reward_xp' => 35,
                'current' => $hasCommentedToday ? 1 : 0,
                'target' => 1,
                'completed' => $hasCommentedToday,
            ],
            [
                'id' => 'breakthrough',
                'title' => 'Percée Intensive',
                'icon' => '⚡',
                'description' => 'Dévorer 5 chapitres dans la même journée.',
                'reward_xp' => 80,
                'current' => min(5, $todayReadChapters),
                'target' => 5,
                'completed' => $todayReadChapters >= 5,
            ],
        ];
    }

    /**
     * Retourne directement le rang actuel pour un nombre de chapitres lus ou d'XP
     */
    public static function getRankForCount(int $readCount, int $streak = 0, ?int $xp = null): array
    {
        return self::calculateStatus($readCount, $streak, $xp)['current'];
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
                'description' => 'Protecteur féroce qui puise sa force dans les lectures sous la pleine lune.',
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
                'description' => 'Créature légendaire dont chaque queue débloquée confère une compréhension transcendante des intrigues.',
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
                'description' => 'Le grand Dragon Oriental des mythes fondateurs, maître des tempêtes et de la foudre bleue.',
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
    public static function processStreakAndShields(User $user): array
    {
        $today = Carbon::now('Europe/Paris')->format('Y-m-d');
        $yesterday = Carbon::now('Europe/Paris')->subDay()->format('Y-m-d');
        $lastReadDate = $user->last_read_at ? Carbon::parse($user->last_read_at)->timezone('Europe/Paris')->format('Y-m-d') : null;

        $shieldUsed = false;
        $shieldEarned = false;

        if (! $lastReadDate) {
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
