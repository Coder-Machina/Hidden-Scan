<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $users = array (
  0 => 
  array (
    'name' => 'Meliodas',
    'email' => 'meliodasdsama006@gmail.com',
    'password' => '$2y$12$YfvutpWtyw16jTZTcBkPnepjz9JaE7x5kNLt3GvRWgNKCScd6XVaS',
    'email_verified_at' => NULL,
    'roles' => 
    array (
      0 => 'owner',
    ),
  ),
  1 => 
  array (
    'name' => 'Admin HiddenScan',
    'email' => 'admin@hiddenscan.com',
    'password' => '$2y$12$/rJ7p9sYPAFb6DpaceDgx.aHlVFiNERPly1nHNksjmePBm4wB5uSG',
    'email_verified_at' => NULL,
    'roles' => 
    array (
      0 => 'owner',
    ),
  ),
  2 => 
  array (
    'name' => 'Meliodas',
    'email' => 'meliodasdsala006@gmail.com',
    'password' => '$2y$12$lTFzaZeHY.EZvHHV6.tn7eUXI/oePO5g5tO4py8CfIGlyElsaqqcW',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  3 => 
  array (
    'name' => 'King Grey, Arthur Leywin',
    'email' => 'agentshadowonyx@gmail.com',
    'password' => '$2y$12$CBgUO5zW9YSkKcu/ZCqxmur5pNHbMrxevsaySYS02YK7VLvQjhOcq',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  4 => 
  array (
    'name' => 'K',
    'email' => 'marcelmeliodas@gmail.com',
    'password' => '$2y$12$S3iTXEaMOUxQS3o3F9RHKe6hyPGq1Afw8tbNoK9u00raYYH.TNtUy',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  5 => 
  array (
    'name' => 'Chnwww',
    'email' => 'chnwwwed@gmail.com',
    'password' => '$2y$12$Qs/4jfOSq4FfnlK.NvdtIeneB2uu/bZDIYOcoOokA65SeoAOXuUoq',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  6 => 
  array (
    'name' => 'Zoro',
    'email' => 'shadowmeliodas205@gmail.com',
    'password' => '$2y$12$eIOEtjt7UfYVQWCvzaq7MOxnohqvoEz2uuwkUhEIiUoN5ycDMwvgq',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  7 => 
  array (
    'name' => 'Fana56',
    'email' => 's20869769@gmail.com',
    'password' => '$2y$12$c4A53zKC1S1FDunViEUJ1udHgP0IU0erKCknH9n..m.QJ5gRi5Oxq',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  8 => 
  array (
    'name' => 'La peace ✌️',
    'email' => 'isaacmusul5@gmail.com',
    'password' => '$2y$12$z8ZnMn8CsSaJTVpgytcAIuSSTGpzoxnD2g8MY3nx1k8BO6EhaAOk.',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  9 => 
  array (
    'name' => '3DX2Y',
    'email' => 'hansayah7@gmail.com',
    'password' => '$2y$12$wTYAAoQHZzaA8Zf6D6H4QeE2c.ZkxOo4D.oeWZOnsNseuKJYtwcIK',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  10 => 
  array (
    'name' => 'Jordy',
    'email' => 'kfljordan@gmail.com',
    'password' => '$2y$12$emlCh0IB7Eeh9HvOzYuQ7.2CYRyZcqbuydiRPvZi3aSVAPGbPm2.S',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  11 => 
  array (
    'name' => 'Frimeur bio',
    'email' => 'frimeurbio89@gmail.com',
    'password' => '$2y$12$krrJOJkUeqaAVTglgz9VxueQm/cqgDzHxmIqRt2DulrvzCFxL8HqG',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  12 => 
  array (
    'name' => 'E.N.D',
    'email' => 'vince.fr92@gmail.com',
    'password' => '$2y$12$WOCagwf0Z1hO/9g/CT7goeh.AqQls6qWBoJwoiW00x1Pt8UxEc7nW',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  13 => 
  array (
    'name' => 'white',
    'email' => 'difaha4458@kingdais.com',
    'password' => '$2y$12$PwNrB.qjY9pLFWj94wcvQOKVELt6WbarCT963UUXU8QL1mxrVKbC6',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
  14 => 
  array (
    'name' => 'traumahahahahahah',
    'email' => 'monmariioda@gmail.com',
    'password' => '$2y$12$brzZr8S8pK/dN2.27ZjhsutjqbIX7OPwq6WIlYlaB7RcOtv5RWjl2',
    'email_verified_at' => NULL,
    'roles' => 
    array (
    ),
  ),
);
        foreach ($users as $uData) {
            $roles = $uData['roles'] ?? [];
            unset($uData['roles']);

            $user = User::updateOrCreate(
                ['email' => $uData['email']],
                $uData
            );

            if (!empty($roles)) {
                $user->syncRoles($roles);
            }
        }

        $authors = array (
  0 => 
  array (
    'name' => 'Kim Gwi Rang ',
    'slug' => 'Kim Gwi Rang ',
  ),
  1 => 
  array (
    'name' => 'Ganjjajang',
    'slug' => 'Ganjjajang',
  ),
  2 => 
  array (
    'name' => 'Seohee',
    'slug' => 'Seohee',
  ),
  3 => 
  array (
    'name' => 'So-Ryeong Gi',
    'slug' => 'so-ryeong-gi',
  ),
  4 => 
  array (
    'name' => 'Chu-Gong',
    'slug' => 'chu-gong',
  ),
  5 => 
  array (
    'name' => 'Hyeon-Gun',
    'slug' => 'hyeon-gun',
  ),
  6 => 
  array (
    'name' => 'Oda Eiichirou (尾田栄一郎)',
    'slug' => 'oda-eiichirou',
  ),
  7 => 
  array (
    'name' => 'DAUL (다울)',
    'slug' => 'daul',
  ),
  8 => 
  array (
    'name' => 'Ting Yi (益廷)',
    'slug' => 'ting-yi',
  ),
  9 => 
  array (
    'name' => 'Yi Bai Jun (一白均)',
    'slug' => 'yi-bai-jun',
  ),
  10 => 
  array (
    'name' => 'TurtleMe',
    'slug' => 'turtleme',
  ),
  11 => 
  array (
    'name' => 'Great H (현절무)',
    'slug' => 'great-h',
  ),
  12 => 
  array (
    'name' => 'Hanjung Wolya (한중월야)',
    'slug' => 'hanjung-wolya',
  ),
  13 => 
  array (
    'name' => 'Geobalhan',
    'slug' => 'geobalhan',
  ),
  14 => 
  array (
    'name' => 'Hanjung Worya',
    'slug' => 'hanjung-worya',
  ),
  15 => 
  array (
    'name' => 'Eiichirou Oda',
    'slug' => 'eiichirou-oda',
  ),
  16 => 
  array (
    'name' => 'Sang-Hyeok Lee',
    'slug' => 'sang-hyeok-lee',
  ),
  17 => 
  array (
    'name' => 'Amazing (엄청난)',
    'slug' => 'amazing',
  ),
  18 => 
  array (
    'name' => 'Swing Bat',
    'slug' => 'swing-bat',
  ),
  19 => 
  array (
    'name' => 'Coffee Lime',
    'slug' => 'coffee-lime',
  ),
  20 => 
  array (
    'name' => 'Singsyong',
    'slug' => 'singsyong',
  ),
  21 => 
  array (
    'name' => 'UMI',
    'slug' => 'umi',
  ),
  22 => 
  array (
    'name' => 'Geomnem',
    'slug' => 'geomnem',
  ),
);
        foreach ($authors as $aData) {
            Author::firstOrCreate(['slug' => $aData['slug']], $aData);
        }

        $artists = array (
  0 => 
  array (
    'name' => 'Leesam',
    'slug' => 'Leesam',
  ),
  1 => 
  array (
    'name' => 'Seong-Rak Jang',
    'slug' => 'seong-rak-jang',
  ),
  2 => 
  array (
    'name' => 'Oda Eiichirou (尾田栄一郎)',
    'slug' => 'oda-eiichirou',
  ),
  3 => 
  array (
    'name' => 'REDICE Studio (레드아이스 스튜디오)',
    'slug' => 'redice-studio',
  ),
  4 => 
  array (
    'name' => 'INsa',
    'slug' => 'insa',
  ),
  5 => 
  array (
    'name' => 'Ciwei Zuo Feiji (刺猬坐飞机)',
    'slug' => 'ciwei-zuo-feiji',
  ),
  6 => 
  array (
    'name' => 'YOYO',
    'slug' => 'yoyo',
  ),
  7 => 
  array (
    'name' => 'Wudijiurenwang',
    'slug' => 'wudijiurenwang',
  ),
  8 => 
  array (
    'name' => 'Fuyuki23',
    'slug' => 'fuyuki23',
  ),
  9 => 
  array (
    'name' => '333335',
    'slug' => '333335',
  ),
  10 => 
  array (
    'name' => 'Eginhardt',
    'slug' => 'eginhardt',
  ),
  11 => 
  array (
    'name' => 'GGBG (금강불괴)',
    'slug' => 'ggbg',
  ),
  12 => 
  array (
    'name' => 'Geumgangbulgoe',
    'slug' => 'geumgangbulgoe',
  ),
  13 => 
  array (
    'name' => 'Dae-il Kim',
    'slug' => 'dae-il-kim',
  ),
  14 => 
  array (
    'name' => 'Kim Muhyeon (김무현)',
    'slug' => 'kim-muhyeon',
  ),
  15 => 
  array (
    'name' => 'Sleepy-C',
    'slug' => 'sleepy-c',
  ),
);
        foreach ($artists as $artData) {
            Artist::firstOrCreate(['slug' => $artData['slug']], $artData);
        }

        $genres = array (
  0 => 
  array (
    'name' => 'Action',
    'slug' => 'action',
  ),
  1 => 
  array (
    'name' => 'Aventure ',
    'slug' => 'aventure',
  ),
  2 => 
  array (
    'name' => 'Drame',
    'slug' => 'drame',
  ),
  3 => 
  array (
    'name' => 'Psychologique',
    'slug' => 'Psychologique',
  ),
  4 => 
  array (
    'name' => 'Seinen',
    'slug' => 'Seinen',
  ),
  5 => 
  array (
    'name' => 'Fantastique',
    'slug' => 'fantastique',
  ),
  6 => 
  array (
    'name' => 'Award Winning',
    'slug' => 'award-winning',
  ),
  7 => 
  array (
    'name' => 'Science-Fiction',
    'slug' => 'science-fiction',
  ),
  8 => 
  array (
    'name' => 'Monstres',
    'slug' => 'monstres',
  ),
  9 => 
  array (
    'name' => 'Animals',
    'slug' => 'animals',
  ),
  10 => 
  array (
    'name' => 'Comédie',
    'slug' => 'comedie',
  ),
  11 => 
  array (
    'name' => 'Gore',
    'slug' => 'gore',
  ),
  12 => 
  array (
    'name' => 'Long Strip',
    'slug' => 'long-strip',
  ),
  13 => 
  array (
    'name' => 'Full Color',
    'slug' => 'full-color',
  ),
  14 => 
  array (
    'name' => 'Arts Martiaux',
    'slug' => 'arts-martiaux',
  ),
  15 => 
  array (
    'name' => 'Harem',
    'slug' => 'harem',
  ),
  16 => 
  array (
    'name' => 'Web Comic',
    'slug' => 'web-comic',
  ),
  17 => 
  array (
    'name' => 'Réincarnation',
    'slug' => 'reincarnation',
  ),
  18 => 
  array (
    'name' => 'Démons',
    'slug' => 'demons',
  ),
  19 => 
  array (
    'name' => 'Magie',
    'slug' => 'magie',
  ),
  20 => 
  array (
    'name' => 'Isekai',
    'slug' => 'isekai',
  ),
  21 => 
  array (
    'name' => 'Vie scolaire',
    'slug' => 'vie-scolaire',
  ),
  22 => 
  array (
    'name' => 'Adaptation',
    'slug' => 'adaptation',
  ),
  23 => 
  array (
    'name' => 'Tragédie',
    'slug' => 'tragedie',
  ),
  24 => 
  array (
    'name' => 'Wuxia',
    'slug' => 'wuxia',
  ),
  25 => 
  array (
    'name' => 'Voyage dans le temps',
    'slug' => 'voyage-dans-le-temps',
  ),
  26 => 
  array (
    'name' => 'Surnaturel',
    'slug' => 'surnaturel',
  ),
);
        foreach ($genres as $gData) {
            Genre::firstOrCreate(['slug' => $gData['slug']], $gData);
        }

        $tags = array (
  0 => 
  array (
    'name' => 'Action',
    'slug' => 'action',
  ),
  1 => 
  array (
    'name' => 'avanture ',
    'slug' => 'aventure',
  ),
  2 => 
  array (
    'name' => 'Drame',
    'slug' => 'drame',
  ),
  3 => 
  array (
    'name' => 'Psychologique',
    'slug' => 'Psychologique',
  ),
);
        foreach ($tags as $tData) {
            Tag::firstOrCreate(['slug' => $tData['slug']], $tData);
        }

        $mangas = array (
  0 => 
  array (
    'title' => 'Genius Grandson of the Loan Shark King ',
    'slug' => 'genius-grandson-of-the-loan-shark-king',
    'synopsis' => 'Kim Mu-hyuk, le petit-fils du roi des usuriers, pourra-t-il monter sur le trône de l\'impitoyable empire des usuriers, craint même par les gangsters ? Kim Mu-hyuk, le petit-fils du roi requin prêteur Cheon Tae-san, auquel même les riches élites de Corée du Sud se sont inclinées, s\'efforce d\'être reconnu comme membre de la famille Cheon. Malgré ses efforts, Mu-Hyuk est trahi et tué par ses oncles dans une lutte acharnée pour l\'héritage de son grand-père. Cependant, au lieu de mourir, il se réveille dans les années 1990’, avec une seconde chance de réécrire son destin. Déterminé à empêcher la mort de sa grand-mère, qui l\'a élevé, et à récupérer l\'héritage de la famille Cheon, Mu-hyuk entreprend de préserver ses amitiés rompues et de se venger brutalement de ses oncles et de tous ceux qui se dressent sur son chemin. Bien qu\'autrefois un fauteur de troubles,Mu-hyuk atteint rapidement le sommet tant sur le plan scolaire que social, gagnant ainsi la confiance de son grand-père. Alors qu\'il consolide sa place dans la famille, Mu-hyuk commence à manipuler le monde souterrain, prenant le contrôle d\'organisations criminelles et de personnalités influentes de la politique et des affaires. Peu à peu, il ouvre la voie pour réaliser ses ambitions et se venger, étape par étape.',
    'type' => 'manhwa',
    'status' => 'en_cours',
    'release_year' => '2024',
    'cover_image' => 'mangas/covers/01M36QM6RK8QGSBYKCT3EENWSZ.png',
    'banner_image' => 'mangas/banners/01M36QM6RMXV397XMGJ5VGTRRQ.png',
    'is_featured' => true,
    'views_count' => 193,
    'average_rating' => 5.0,
    'ratings_count' => 3,
    'genres' => 
    array (
      0 => 'action',
      1 => 'drame',
      2 => 'Psychologique',
      3 => 'Seinen',
    ),
    'tags' => 
    array (
      0 => 'action',
      1 => 'drame',
      2 => 'Psychologique',
    ),
    'authors' => 
    array (
      0 => 'Kim Gwi Rang ',
    ),
    'artists' => 
    array (
      0 => 'Leesam',
    ),
    'chapters' => 
    array (
      0 => 
      array (
        'number' => 1,
        'title' => NULL,
        'slug' => 'chapitre-1',
        'status' => 'publie',
        'views_count' => 87,
        'published_at' => NULL,
      ),
    ),
  ),
  1 => 
  array (
    'title' => 'The Returner\'s Road to Retirement',
    'slug' => 'the-returners-road-to-retirement',
    'synopsis' => 'Après deux décennies passées à combattre des monstres extradimensionnels et à protéger le monde, Dae-in Lim est enfin prêt à prendre sa retraite et à profiter d\'une vie paisible. Cependant, le lendemain de sa fête de départ à la retraite, il se réveille et se retrouve dans le temps… avant même d\'avoir acquis ses pouvoirs. Armé de la connaissance de l\'avenir, Dae-in est déterminé à assurer sa fortune et à prendre à nouveau sa retraite, mais cette fois dans les trois ans. (Source : WEBTOON)',
    'type' => 'manhwa',
    'status' => 'en_cours',
    'release_year' => '2023',
    'cover_image' => 'mangas/covers/IA3BSgjLkwzCYrHFtR241LkTgii0Zc9J.png',
    'banner_image' => 'mangas/banners/01M36TBSAJYFW7B3VK7WMT2134.jpg',
    'is_featured' => true,
    'views_count' => 105,
    'average_rating' => 5.0,
    'ratings_count' => 4,
    'genres' => 
    array (
      0 => 'action',
      1 => 'fantastique',
    ),
    'tags' => 
    array (
      0 => 'action',
      1 => 'aventure',
    ),
    'authors' => 
    array (
      0 => 'Ganjjajang',
      1 => 'Seohee',
    ),
    'artists' => 
    array (
      0 => 'insa',
    ),
    'chapters' => 
    array (
      0 => 
      array (
        'number' => 1,
        'title' => NULL,
        'slug' => 'chapitre-1',
        'status' => 'publie',
        'views_count' => 5,
        'published_at' => '2026-09-28 13:46:31',
      ),
      1 => 
      array (
        'number' => 2,
        'title' => NULL,
        'slug' => 'chapitre-2',
        'status' => 'publie',
        'views_count' => 3,
        'published_at' => '2026-09-28 13:46:58',
      ),
      2 => 
      array (
        'number' => 3,
        'title' => NULL,
        'slug' => 'chapitre-3',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:47:25',
      ),
      3 => 
      array (
        'number' => 4,
        'title' => NULL,
        'slug' => 'chapitre-4',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:47:48',
      ),
      4 => 
      array (
        'number' => 5,
        'title' => NULL,
        'slug' => 'chapitre-5',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:48:06',
      ),
      5 => 
      array (
        'number' => 6,
        'title' => NULL,
        'slug' => 'chapitre-6',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:48:29',
      ),
      6 => 
      array (
        'number' => 7,
        'title' => NULL,
        'slug' => 'chapitre-7',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:48:51',
      ),
      7 => 
      array (
        'number' => 8,
        'title' => NULL,
        'slug' => 'chapitre-8',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:49:17',
      ),
      8 => 
      array (
        'number' => 9,
        'title' => NULL,
        'slug' => 'chapitre-9',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 13:49:43',
      ),
      9 => 
      array (
        'number' => 10,
        'title' => NULL,
        'slug' => 'chapitre-10',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 17:50:56',
      ),
      10 => 
      array (
        'number' => 11,
        'title' => NULL,
        'slug' => 'chapitre-11',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 18:00:09',
      ),
    ),
  ),
  2 => 
  array (
    'title' => 'One Piece',
    'slug' => 'one-piece',
    'synopsis' => 'Enfant, Monkey D. Luffy a été inspiré pour devenir pirate en écoutant les contes du boucanier Shanks « aux cheveux roux ». Mais sa vie a changé lorsque Luffy a accidentellement mangé le fruit du diable Gum-Gum et a acquis le pouvoir de s\'étirer comme du caoutchouc... au prix de ne plus jamais pouvoir nager ! Des années plus tard, jurant toujours de devenir le roi des pirates, Luffy se lance dans son aventure... seul dans une barque, à la recherche du légendaire "One Piece", considéré comme le plus grand trésor du monde... ',
    'type' => 'manga',
    'status' => 'en_cours',
    'release_year' => '1997',
    'cover_image' => 'mangas/covers/3Bo9YAYzyr8fOUik8EMRCO7rvD7S33MR.jpg',
    'banner_image' => NULL,
    'is_featured' => true,
    'views_count' => 1,
    'average_rating' => 0.0,
    'ratings_count' => 0,
    'genres' => 
    array (
      0 => 'action',
      1 => 'aventure',
      2 => 'fantastique',
      3 => 'comedie',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'eiichirou-oda',
    ),
    'artists' => 
    array (
    ),
    'chapters' => 
    array (
    ),
  ),
  3 => 
  array (
    'title' => 'Nano Machine',
    'slug' => 'nano-machine',
    'synopsis' => 'La nanotechnologie rencontre les arts martiaux à la Mashin Academy. La mère de Yeo-Un n’est peut-être pas l’une des six épouses officielles du Grand Prêtre, mais le sang de son père le qualifie néanmoins pour avoir une chance au poste de Prêtre mineur. Une mystérieuse injection de nanomachine provenant d\'un futur descendant aidera-t-elle Yeo-un dans cette compétition féroce contre ses puissants demi-frères et sœurs ? ',
    'type' => 'manhwa',
    'status' => 'en_cours',
    'release_year' => '2020',
    'cover_image' => 'mangas/covers/o2HZ06glhAf3McFris7ePbZuHg3iN6VT.jpg',
    'banner_image' => NULL,
    'is_featured' => true,
    'views_count' => 0,
    'average_rating' => 0.0,
    'ratings_count' => 0,
    'genres' => 
    array (
      0 => 'action',
      1 => 'aventure',
      2 => 'fantastique',
      3 => 'science-fiction',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'geobalhan',
      1 => 'hanjung-worya',
    ),
    'artists' => 
    array (
      0 => 'geumgangbulgoe',
    ),
    'chapters' => 
    array (
    ),
  ),
  4 => 
  array (
    'title' => 'Solo Leveling',
    'slug' => 'solo-leveling',
    'synopsis' => 'Dans un monde où des êtres éveillés appelés « Chasseurs » doivent combattre des monstres mortels pour protéger l’humanité, Seong Jin-U, surnommé « le chasseur le plus faible de toute l’humanité », se retrouve dans une lutte constante pour sa survie. Un jour, après qu\'une rencontre brutale dans un donjon surpuissant anéantit son groupe et menace de mettre fin à ses jours, un mystérieux système le choisit comme seul joueur : Jin-U a eu la rare opportunité d\'améliorer ses capacités, peut-être au-delà de toutes limites connues. Suivez le voyage de Jin-U alors qu\'il affronte des ennemis toujours plus puissants, humains et monstres, pour découvrir les secrets des donjons et l\'étendue ultime de ses pouvoirs. (Source : Tappytoon) Remarque : Comprend 22 chapitres supplémentaires.',
    'type' => 'manhwa',
    'status' => 'termine',
    'release_year' => '2018',
    'cover_image' => 'mangas/covers/NAqO2xsIkbmEDMpNui03jv4MmPKtEvuX.jpg',
    'banner_image' => NULL,
    'is_featured' => true,
    'views_count' => 1,
    'average_rating' => 0.0,
    'ratings_count' => 0,
    'genres' => 
    array (
      0 => 'action',
      1 => 'aventure',
      2 => 'fantastique',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'so-ryeong-gi',
      1 => 'chu-gong',
      2 => 'hyeon-gun',
    ),
    'artists' => 
    array (
      0 => 'seong-rak-jang',
    ),
    'chapters' => 
    array (
    ),
  ),
  5 => 
  array (
    'title' => 'Why I Quit Being the Demon King',
    'slug' => 'why-i-quit-being-the-demon-king',
    'synopsis' => 'Demiurgos DCLXVI, ou Deus, est la 666ème résurrection du Roi Démon, destiné à combattre un guerrier du clan Pure Blood tous les 100 ans pour libérer ses camarades démons de l\'enfer. Mais au lieu d\'attendre, Deus s\'échappe 20 ans plus tôt pour se forger une nouvelle identité parmi les humains. (Source : WEBTOON)',
    'type' => 'manhwa',
    'status' => 'en_cours',
    'release_year' => '2024',
    'cover_image' => 'mangas/covers/DFN8qcVsRQBxxGQbwGHjglnv0GhYv8E5.jpg',
    'banner_image' => NULL,
    'is_featured' => false,
    'views_count' => 1,
    'average_rating' => 5.0,
    'ratings_count' => 1,
    'genres' => 
    array (
      0 => 'action',
      1 => 'aventure',
      2 => 'fantastique',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'sang-hyeok-lee',
    ),
    'artists' => 
    array (
      0 => 'dae-il-kim',
    ),
    'chapters' => 
    array (
    ),
  ),
  6 => 
  array (
    'title' => 'Hoegwi Suseonjeon',
    'slug' => 'hoegwi-suseonjeon',
    'synopsis' => 'La régression infinie est-elle une bénédiction ou une malédiction ? Pour Eunhyeon, c’est un destin auquel il souhaite désespérément échapper. Sept êtres tombent dans le monde des pratiquants. Chacun a des talents rares, mais Eunhyeon semble n\'en avoir aucun et lutte pour survivre dans ce monde étrange. Pourtant, après sa mort, il se retrouve au début de tout, réalisant que son talent est la capacité de régression infinie. Ici commence la lutte acharnée d’Eunhyeon pour se libérer.',
    'type' => 'manhwa',
    'status' => 'pause',
    'release_year' => '2025',
    'cover_image' => 'mangas/covers/GAAns3Sok1hu8e1AztkTxLlzzBcWY0gW.jpg',
    'banner_image' => NULL,
    'is_featured' => false,
    'views_count' => 1,
    'average_rating' => 0.0,
    'ratings_count' => 0,
    'genres' => 
    array (
      0 => 'action',
      1 => 'aventure',
      2 => 'fantastique',
      3 => 'long-strip',
      4 => 'full-color',
      5 => 'arts-martiaux',
      6 => 'web-comic',
      7 => 'isekai',
      8 => 'adaptation',
      9 => 'voyage-dans-le-temps',
      10 => 'surnaturel',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'amazing',
    ),
    'artists' => 
    array (
      0 => 'kim-muhyeon',
    ),
    'chapters' => 
    array (
    ),
  ),
  7 => 
  array (
    'title' => 'The Reborn Young Lord is an Assassin',
    'slug' => 'the-reborn-young-lord-is-an-assassin',
    'synopsis' => 'Cyan Vert, fils illégitime du duc et plus grand assassin de l\'empire, est trahi par le frère vertueux dans lequel il a toujours vécu. Mais juste avant que le coup fatal ne lui frappe à la gorge, Cyan se réveille et découvre qu\'il n\'est à nouveau qu\'un garçon. Le jeune seigneur renaît, et cette fois, il ne vivra dans l\'ombre de personne ! ',
    'type' => 'manhwa',
    'status' => 'en_cours',
    'release_year' => '2024',
    'cover_image' => 'mangas/covers/jvaZTb3naPVsQVhWUQgyZj0dvbBUYiv0.jpg',
    'banner_image' => NULL,
    'is_featured' => false,
    'views_count' => 0,
    'average_rating' => 0.0,
    'ratings_count' => 0,
    'genres' => 
    array (
      0 => 'action',
      1 => 'fantastique',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'swing-bat',
      1 => 'coffee-lime',
    ),
    'artists' => 
    array (
    ),
    'chapters' => 
    array (
      0 => 
      array (
        'number' => 1,
        'title' => NULL,
        'slug' => 'chapitre-1',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 21:16:19',
      ),
      1 => 
      array (
        'number' => 2,
        'title' => NULL,
        'slug' => 'chapitre-2',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 21:27:56',
      ),
      2 => 
      array (
        'number' => 3,
        'title' => NULL,
        'slug' => 'chapitre-3',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 21:28:07',
      ),
      3 => 
      array (
        'number' => 4,
        'title' => NULL,
        'slug' => 'chapitre-4',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 21:28:20',
      ),
      4 => 
      array (
        'number' => 5,
        'title' => NULL,
        'slug' => 'chapitre-5',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 21:28:32',
      ),
    ),
  ),
  8 => 
  array (
    'title' => 'Omniscient Reader',
    'slug' => 'omniscient-reader',
    'synopsis' => 'À l’époque, Dok-Ja n’en avait aucune idée. Il ne savait pas que son roman Web préféré, « Trois façons de survivre à l\'Apocalypse », allait prendre vie et qu\'il deviendrait la seule personne à savoir comment le monde allait finir. Il ne savait pas non plus qu’il finirait par devenir le protagoniste de ce roman devenu réalité. Désormais, Dok-Ja entreprendra un voyage pour changer le cours de l\'histoire et sauver l\'humanité une fois pour toutes. ',
    'type' => 'manhwa',
    'status' => 'en_cours',
    'release_year' => '2020',
    'cover_image' => 'mangas/covers/1Tf0CbSeuvvbh0uJJP0REJAYu3aP6mrD.jpg',
    'banner_image' => NULL,
    'is_featured' => true,
    'views_count' => 0,
    'average_rating' => 0.0,
    'ratings_count' => 0,
    'genres' => 
    array (
      0 => 'action',
      1 => 'aventure',
      2 => 'fantastique',
    ),
    'tags' => 
    array (
    ),
    'authors' => 
    array (
      0 => 'singsyong',
      1 => 'umi',
      2 => 'geomnem',
    ),
    'artists' => 
    array (
      0 => 'sleepy-c',
    ),
    'chapters' => 
    array (
      0 => 
      array (
        'number' => 0,
        'title' => NULL,
        'slug' => 'chapitre-0',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 22:12:24',
      ),
      1 => 
      array (
        'number' => 1,
        'title' => NULL,
        'slug' => 'chapitre-1',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 22:12:37',
      ),
      2 => 
      array (
        'number' => 2,
        'title' => NULL,
        'slug' => 'chapitre-2',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 22:12:48',
      ),
      3 => 
      array (
        'number' => 3,
        'title' => NULL,
        'slug' => 'chapitre-3',
        'status' => 'publie',
        'views_count' => 0,
        'published_at' => '2026-09-28 22:13:05',
      ),
    ),
  ),
);
        foreach ($mangas as $mData) {
            $genreSlugs = $mData['genres'] ?? [];
            $tagSlugs = $mData['tags'] ?? [];
            $authorSlugs = $mData['authors'] ?? [];
            $artistSlugs = $mData['artists'] ?? [];
            $chapters = $mData['chapters'] ?? [];

            unset($mData['genres'], $mData['tags'], $mData['authors'], $mData['artists'], $mData['chapters']);

            $manga = Manga::updateOrCreate(
                ['slug' => $mData['slug']],
                $mData
            );

            if (!empty($genreSlugs)) {
                $genreIds = Genre::whereIn('slug', $genreSlugs)->pluck('id');
                $manga->genres()->sync($genreIds);
            }

            if (!empty($tagSlugs)) {
                $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id');
                $manga->tags()->sync($tagIds);
            }

            if (!empty($authorSlugs)) {
                $authorIds = Author::whereIn('slug', $authorSlugs)->pluck('id');
                $manga->authors()->sync($authorIds);
            }

            if (!empty($artistSlugs)) {
                $artistIds = Artist::whereIn('slug', $artistSlugs)->pluck('id');
                $manga->artists()->sync($artistIds);
            }

            foreach ($chapters as $cData) {
                Chapter::updateOrCreate(
                    [
                        'manga_id' => $manga->id,
                        'number' => $cData['number'],
                    ],
                    $cData
                );
            }
        }
    }
}