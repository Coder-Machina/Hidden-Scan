<?php

namespace App\Services;

use App\Models\Artist;
use App\Models\Author;
use App\Models\Genre;
use App\Models\Manga;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MangaMetadataService
{
    /**
     * Recherche d'œuvres selon la source choisie.
     */
    public function search(string $query, string $source = 'anilist'): array
    {
        $results = match ($source) {
            'mangadex' => $this->searchMangaDex($query),
            'jikan' => $this->searchJikan($query),
            default => $this->searchAniList($query),
        };

        foreach ($results as $item) {
            \Illuminate\Support\Facades\Cache::put("manga_meta:{$item['source']}:{$item['id']}", $item, 600);
        }

        return $results;
    }

    /**
     * Récupère les métadonnées d'une œuvre par source et identifiant.
     */
    public function getDetails(string $source, string $id): ?array
    {
        $cached = \Illuminate\Support\Facades\Cache::get("manga_meta:{$source}:{$id}");
        if ($cached) {
            return $cached;
        }

        $results = $this->search($id, $source);
        foreach ($results as $item) {
            if ((string) $item['id'] === (string) $id) {
                return $item;
            }
        }

        return $results[0] ?? null;
    }

    /**
     * Recherche via AniList (GraphQL)
     */
    protected function searchAniList(string $query): array
    {
        $graphql = <<<'GRAPHQL'
        query ($search: String) {
          Page(page: 1, perPage: 8) {
            media(search: $search, type: MANGA) {
              id
              title {
                romaji
                english
                native
              }
              description(asHtml: false)
              countryOfOrigin
              format
              status
              startDate {
                year
              }
              genres
              staff(perPage: 8) {
                edges {
                  role
                  node {
                    name {
                      full
                    }
                  }
                }
              }
              coverImage {
                extraLarge
                large
              }
              bannerImage
            }
          }
        }
        GRAPHQL;

        try {
            $response = Http::timeout(8)->post('https://graphql.anilist.co', [
                'query' => $graphql,
                'variables' => ['search' => $query],
            ]);

            if (!$response->successful()) {
                Log::warning('AniList API error: ' . $response->status());
                return [];
            }

            $mediaList = $response->json('data.Page.media', []);
            $results = [];

            foreach ($mediaList as $item) {
                $title = $item['title']['english'] ?? $item['title']['romaji'] ?? $item['title']['native'] ?? 'Sans titre';
                $altTitle = $item['title']['romaji'] ?? $item['title']['native'] ?? null;

                $type = match ($item['countryOfOrigin'] ?? null) {
                    'KR' => 'manhwa',
                    'CN', 'TW' => 'manhua',
                    default => ($item['format'] === 'MANHWA' ? 'manhwa' : ($item['format'] === 'MANHUA' ? 'manhua' : 'manga')),
                };

                $status = match ($item['status'] ?? null) {
                    'RELEASING' => 'en_cours',
                    'FINISHED' => 'termine',
                    'HIATUS' => 'pause',
                    'CANCELLED' => 'abandonne',
                    default => 'en_cours',
                };

                $authors = [];
                $artists = [];
                foreach ($item['staff']['edges'] ?? [] as $staffEdge) {
                    $role = strtolower($staffEdge['role'] ?? '');
                    $name = $staffEdge['node']['name']['full'] ?? null;
                    if (!$name) continue;

                    if (str_contains($role, 'story') || str_contains($role, 'original') || str_contains($role, 'author') || str_contains($role, 'writer')) {
                        $authors[] = $name;
                    } elseif (str_contains($role, 'art') || str_contains($role, 'illustrat') || str_contains($role, 'character')) {
                        $artists[] = $name;
                    }
                }

                $cleanDesc = strip_tags($item['description'] ?? '');
                // Nettoie les balises markdown/spoiler d'anilist
                $cleanDesc = preg_replace('/~!(.*?)!~/', '', $cleanDesc);
                $cleanDesc = trim(preg_replace('/\s+/', ' ', $cleanDesc));

                $results[] = [
                    'source' => 'anilist',
                    'id' => (string) $item['id'],
                    'title' => $title,
                    'alt_title' => $altTitle,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => $cleanDesc,
                    'release_year' => $item['startDate']['year'] ?? null,
                    'authors' => array_values(array_unique($authors)),
                    'artists' => array_values(array_unique($artists)),
                    'genres' => $item['genres'] ?? [],
                    'cover_url' => $item['coverImage']['extraLarge'] ?? $item['coverImage']['large'] ?? null,
                    'banner_url' => $item['bannerImage'] ?? null,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('AniList API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Recherche via MangaDex (REST v5)
     */
    protected function searchMangaDex(string $query): array
    {
        try {
            $response = Http::timeout(8)->get('https://api.mangadex.org/manga', [
                'title' => $query,
                'limit' => 8,
                'includes' => ['author', 'artist', 'cover_art'],
                'contentRating' => ['safe', 'suggestive'],
            ]);

            if (!$response->successful()) {
                return [];
            }

            $results = [];
            $data = $response->json('data', []);

            foreach ($data as $item) {
                $id = $item['id'];
                $attrs = $item['attributes'] ?? [];

                $titles = $attrs['title'] ?? [];
                $title = $titles['fr'] ?? $titles['en'] ?? $titles['ja-ro'] ?? reset($titles) ?: 'Sans titre';

                $descriptions = $attrs['description'] ?? [];
                $synopsis = $descriptions['fr'] ?? $descriptions['en'] ?? reset($descriptions) ?: '';
                $synopsis = strip_tags(trim($synopsis));

                $origLang = $attrs['originalLanguage'] ?? 'ja';
                $type = match ($origLang) {
                    'ko' => 'manhwa',
                    'zh', 'zh-hk' => 'manhua',
                    default => 'manga',
                };

                $status = match ($attrs['status'] ?? 'ongoing') {
                    'ongoing' => 'en_cours',
                    'completed' => 'termine',
                    'hiatus' => 'pause',
                    'cancelled' => 'abandonne',
                    default => 'en_cours',
                };

                $authors = [];
                $artists = [];
                $coverFile = null;

                foreach ($item['relationships'] ?? [] as $rel) {
                    if ($rel['type'] === 'author' && !empty($rel['attributes']['name'])) {
                        $authors[] = $rel['attributes']['name'];
                    } elseif ($rel['type'] === 'artist' && !empty($rel['attributes']['name'])) {
                        $artists[] = $rel['attributes']['name'];
                    } elseif ($rel['type'] === 'cover_art' && !empty($rel['attributes']['fileName'])) {
                        $coverFile = $rel['attributes']['fileName'];
                    }
                }

                $coverUrl = $coverFile ? "https://uploads.mangadex.org/covers/{$id}/{$coverFile}" : null;

                $genres = [];
                foreach ($attrs['tags'] ?? [] as $tag) {
                    $tagName = $tag['attributes']['name']['fr'] ?? $tag['attributes']['name']['en'] ?? null;
                    if ($tagName) {
                        $genres[] = $tagName;
                    }
                }

                $results[] = [
                    'source' => 'mangadex',
                    'id' => $id,
                    'title' => $title,
                    'alt_title' => $titles['en'] ?? null,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => $synopsis,
                    'release_year' => !empty($attrs['year']) ? (int) $attrs['year'] : null,
                    'authors' => array_values(array_unique($authors)),
                    'artists' => array_values(array_unique($artists)),
                    'genres' => array_values(array_unique($genres)),
                    'cover_url' => $coverUrl,
                    'banner_url' => null,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('MangaDex API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Recherche via Jikan / MyAnimeList (REST v4)
     */
    protected function searchJikan(string $query): array
    {
        try {
            $response = Http::timeout(8)->get('https://api.jikan.moe/v4/manga', [
                'q' => $query,
                'limit' => 8,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $results = [];
            $data = $response->json('data', []);

            foreach ($data as $item) {
                $type = match (strtolower($item['type'] ?? 'manga')) {
                    'manhwa' => 'manhwa',
                    'manhua' => 'manhua',
                    default => 'manga',
                };

                $status = match (strtolower($item['status'] ?? '')) {
                    'publishing' => 'en_cours',
                    'finished' => 'termine',
                    'on hiatus' => 'pause',
                    'discontinued' => 'abandonne',
                    default => 'en_cours',
                };

                $authors = [];
                foreach ($item['authors'] ?? [] as $a) {
                    if (!empty($a['name'])) {
                        // Inverse "Oda, Eiichiro" -> "Eiichiro Oda"
                        $parts = explode(',', $a['name']);
                        $authors[] = count($parts) === 2 ? trim($parts[1]) . ' ' . trim($parts[0]) : trim($a['name']);
                    }
                }

                $genres = [];
                foreach ($item['genres'] ?? [] as $g) {
                    if (!empty($g['name'])) $genres[] = $g['name'];
                }
                foreach ($item['themes'] ?? [] as $t) {
                    if (!empty($t['name'])) $genres[] = $t['name'];
                }

                $coverUrl = $item['images']['webp']['large_image_url']
                    ?? $item['images']['jpg']['large_image_url']
                    ?? null;

                $synopsis = strip_tags($item['synopsis'] ?? '');
                // Retire la signature [Written by MAL Rewrite]
                $synopsis = preg_replace('/\[Written by MAL Rewrite\]/i', '', $synopsis);

                $results[] = [
                    'source' => 'jikan',
                    'id' => (string) $item['mal_id'],
                    'title' => $item['title'],
                    'alt_title' => $item['title_english'] ?? $item['title_japanese'] ?? null,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => trim($synopsis),
                    'release_year' => $item['published']['prop']['from']['year'] ?? null,
                    'authors' => array_values(array_unique($authors)),
                    'artists' => [],
                    'genres' => array_values(array_unique($genres)),
                    'cover_url' => $coverUrl,
                    'banner_url' => null,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('Jikan API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Dictionnaire de traduction des genres anglais vers le français.
     */
    public static array $genreTranslations = [
        'action' => 'Action',
        'adventure' => 'Aventure',
        'comedy' => 'Comédie',
        'drama' => 'Drame',
        'fantasy' => 'Fantastique',
        'horror' => 'Horreur',
        'mystery' => 'Mystère',
        'psychological' => 'Psychologique',
        'romance' => 'Romance',
        'sci-fi' => 'Science-Fiction',
        'science fiction' => 'Science-Fiction',
        'slice of life' => 'Tranche de vie',
        'supernatural' => 'Surnaturel',
        'historical' => 'Historique',
        'martial arts' => 'Arts Martiaux',
        'sports' => 'Sport',
        'thriller' => 'Thriller',
        'isekai' => 'Isekai',
        'reincarnation' => 'Réincarnation',
        'school life' => 'Vie scolaire',
        'school' => 'Vie scolaire',
        'time travel' => 'Voyage dans le temps',
        'magic' => 'Magie',
        'military' => 'Militaire',
        'music' => 'Musique',
        'parody' => 'Parodie',
        'vampire' => 'Vampire',
        'demons' => 'Démons',
        'game' => 'Jeu vidéo',
        'video game' => 'Jeu vidéo',
        'harem' => 'Harem',
        'ecchi' => 'Ecchi',
        'tragedy' => 'Tragédie',
        'crime' => 'Policier / Crime',
        'superhero' => 'Super-héros',
        'survival' => 'Survie',
        'monsters' => 'Monstres',
        'urban fantasy' => 'Fantastique urbain',
        'post-apocalyptic' => 'Post-apocalyptique',
    ];

    /**
     * Traduit une liste de genres anglais vers le français.
     */
    public function translateGenres(array $genres): array
    {
        $translated = [];
        foreach ($genres as $genre) {
            $key = strtolower(trim($genre));
            $translated[] = self::$genreTranslations[$key] ?? ucfirst($genre);
        }
        return array_values(array_unique($translated));
    }

    /**
     * Traduit automatiquement un texte (synopsis) anglais vers le français.
     * Utilise Google Translate en priorité avec fallback robuste sur MyMemory.
     */
    public function translateToFrench(string $text): string
    {
        $clean = trim($text);
        if (empty($clean)) {
            return '';
        }

        // Nettoyer d'éventuelles balises HTML simples tout en gardant les retours à la ligne
        $clean = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $clean));

        // 1. Essai prioritaire : Google Translate (haute précision, rapide, sans limitation de 500 car.)
        $googleTranslated = $this->translateWithGoogle($clean);
        if (!empty($googleTranslated)) {
            return $googleTranslated;
        }

        // 2. Fallback secondaire : MyMemory (découpage sécurisé par morceaux < 380 car.)
        $myMemoryTranslated = $this->translateWithMyMemory($clean);
        if (!empty($myMemoryTranslated)) {
            return $myMemoryTranslated;
        }

        return $text;
    }

    /**
     * Traduction via Google Translate (endpoint public extension)
     */
    protected function translateWithGoogle(string $text): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => '*/*',
                ])
                ->get('https://translate.googleapis.com/translate_a/single', [
                    'client' => 'dict-chrome-ex',
                    'sl' => 'auto',
                    'tl' => 'fr',
                    'dt' => 't',
                    'q' => $text,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                    $translated = '';
                    foreach ($data[0] as $segment) {
                        if (isset($segment[0]) && is_string($segment[0])) {
                            $translated .= $segment[0];
                        }
                    }
                    $translated = trim($translated);
                    if (!empty($translated)) {
                        return $translated;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Google Translate Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Traduction via MyMemory en solution de secours
     */
    protected function translateWithMyMemory(string $text): ?string
    {
        $paragraphs = explode("\n", $text);
        $translatedParagraphs = [];

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (empty($para)) {
                continue;
            }

            $chunks = $this->splitTextIntoChunks($para, 380);
            $translatedChunks = [];

            foreach ($chunks as $chunk) {
                try {
                    $response = Http::timeout(8)
                        ->withHeaders(['User-Agent' => 'HiddenScan/1.0'])
                        ->get('https://api.mymemory.translated.net/get', [
                            'q' => $chunk,
                            'langpair' => 'en|fr',
                            'de' => 'contact@hiddenscan.test',
                        ]);

                    if ($response->successful() && $response->json('responseStatus') == 200) {
                        $translatedText = html_entity_decode($response->json('responseData.translatedText'), ENT_QUOTES, 'UTF-8');
                        $translatedChunks[] = $translatedText ?: $chunk;
                    } else {
                        $translatedChunks[] = $chunk;
                    }
                } catch (\Throwable $e) {
                    Log::warning('MyMemory Translation Error: ' . $e->getMessage());
                    $translatedChunks[] = $chunk;
                }
            }

            $translatedParagraphs[] = implode(' ', $translatedChunks);
        }

        $result = trim(implode("\n\n", array_filter($translatedParagraphs)));
        return !empty($result) ? $result : null;
    }

    /**
     * Découpe un texte en morceaux sans couper les phrases ni les mots,
     * garantissant qu'aucun morceau ne dépasse $maxLen (limite MyMemory).
     */
    protected function splitTextIntoChunks(string $text, int $maxLen = 380): array
    {
        if (mb_strlen($text) <= $maxLen) {
            return [$text];
        }

        $sentences = preg_split('/(?<=[.?!])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($sentence) > $maxLen) {
                if (!empty($current)) {
                    $chunks[] = $current;
                    $current = '';
                }
                $words = explode(' ', $sentence);
                $wordChunk = '';
                foreach ($words as $word) {
                    if (mb_strlen($wordChunk . ' ' . $word) <= $maxLen) {
                        $wordChunk = trim($wordChunk . ' ' . $word);
                    } else {
                        if (!empty($wordChunk)) {
                            $chunks[] = $wordChunk;
                        }
                        $wordChunk = $word;
                    }
                }
                if (!empty($wordChunk)) {
                    $chunks[] = $wordChunk;
                }
                continue;
            }

            if (mb_strlen($current . ' ' . $sentence) <= $maxLen) {
                $current = trim($current . ' ' . $sentence);
            } else {
                if (!empty($current)) {
                    $chunks[] = $current;
                }
                $current = $sentence;
            }
        }

        if (!empty($current)) {
            $chunks[] = $current;
        }

        return !empty($chunks) ? $chunks : [$text];
    }

    /**
     * Télécharge une image distante et l'enregistre sur le disque public.
     */
    public function downloadAndStoreImage(string $url, string $directory = 'mangas/covers'): ?string
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'HiddenScan/1.0',
            ])->get($url);

            if (!$response->successful()) {
                return null;
            }

            $content = $response->body();
            if (empty($content)) {
                return null;
            }

            $extension = 'webp';
            $contentType = $response->header('Content-Type');
            if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
                $extension = 'jpg';
            } elseif (str_contains($contentType, 'png')) {
                $extension = 'png';
            }

            $fileName = $directory . '/' . Str::random(32) . '.' . $extension;
            Storage::disk('public')->put($fileName, $content);

            return $fileName;
        } catch (\Throwable $e) {
            Log::error('Erreur téléchargement image externe: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronise les genres, auteurs et artistes en BDD et retourne leurs identifiants.
     */
    public function syncRelations(array $metadata): array
    {
        $genres = $this->translateGenres($metadata['genres'] ?? []);
        $genreIds = [];
        foreach ($genres as $genreName) {
            $name = trim($genreName);
            if (empty($name)) continue;

            $genre = Genre::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $genreIds[] = $genre->id;
        }

        $authorIds = [];
        foreach ($metadata['authors'] ?? [] as $authorName) {
            $name = trim($authorName);
            if (empty($name)) continue;

            $author = Author::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $authorIds[] = $author->id;
        }

        $artistIds = [];
        foreach ($metadata['artists'] ?? [] as $artistName) {
            $name = trim($artistName);
            if (empty($name)) continue;

            $artist = Artist::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $artistIds[] = $artist->id;
        }

        return [
            'genre_ids' => array_values(array_unique($genreIds)),
            'author_ids' => array_values(array_unique($authorIds)),
            'artist_ids' => array_values(array_unique($artistIds)),
        ];
    }
}
