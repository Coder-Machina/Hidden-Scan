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
            'mangaupdates' => $this->searchMangaUpdates($query),
            'kitsu' => $this->searchKitsu($query),
            'myanimelist', 'jikan' => $this->searchMyAnimeList($query),
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
        if ($cached && !empty($cached['synopsis']) && !empty($cached['authors'])) {
            return $cached;
        }

        $item = match ($source) {
            'mangadex' => $this->getMangaDexDetails($id, $cached),
            'mangaupdates' => $this->getMangaUpdatesDetails($id, $cached),
            'kitsu' => $this->getKitsuDetails($id, $cached),
            'myanimelist', 'jikan' => $this->getMyAnimeListDetails($id, $cached),
            default => $this->getAniListDetails($id, $cached),
        };

        if ($item) {
            \Illuminate\Support\Facades\Cache::put("manga_meta:{$source}:{$id}", $item, 600);
            return $item;
        }

        return $cached;
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
     * Recherche via MyAnimeList (API officielle rapide + fallback Jikan)
     */
    protected function searchMyAnimeList(string $query): array
    {
        // 1. Essai direct via l'API officielle préfixe de MyAnimeList
        try {
            $response = Http::timeout(6)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://myanimelist.net/',
                'Accept' => 'application/json',
            ])->get('https://myanimelist.net/search/prefix.json', [
                'type' => 'manga',
                'keyword' => $query,
                'v' => '1',
            ]);

            if ($response->successful()) {
                $categories = $response->json('categories', []);
                $results = [];

                foreach ($categories as $cat) {
                    if (($cat['type'] ?? '') !== 'manga') continue;
                    foreach ($cat['items'] ?? [] as $item) {
                        $payload = $item['payload'] ?? [];
                        $type = match (strtolower($payload['media_type'] ?? 'manga')) {
                            'manhwa' => 'manhwa',
                            'manhua' => 'manhua',
                            default => 'manga',
                        };

                        $status = match (strtolower($payload['status'] ?? '')) {
                            'finished' => 'termine',
                            'publishing' => 'en_cours',
                            'on hiatus' => 'pause',
                            default => 'en_cours',
                        };

                        // Nettoie l'URL pour obtenir l'image haute définition
                        $coverUrl = $item['image_url'] ?? null;
                        if ($coverUrl) {
                            $coverUrl = preg_replace('/\/r\/\d+x\d+\//', '/', $coverUrl);
                        }

                        $results[] = [
                            'source' => 'myanimelist',
                            'id' => (string) $item['id'],
                            'title' => $item['name'] ?? 'Sans titre',
                            'alt_title' => null,
                            'type' => $type,
                            'status' => $status,
                            'synopsis' => '',
                            'release_year' => !empty($payload['start_year']) ? (int) $payload['start_year'] : null,
                            'authors' => [],
                            'artists' => [],
                            'genres' => [],
                            'cover_url' => $coverUrl,
                            'banner_url' => null,
                        ];
                    }
                }

                if (!empty($results)) {
                    return $results;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('MAL prefix search warning: ' . $e->getMessage());
        }

        // 2. Fallback Jikan
        return $this->searchJikan($query);
    }

    /**
     * Récupère les détails complets d'un manga sur MyAnimeList
     */
    protected function getMyAnimeListDetails(string $id, ?array $cached = null): ?array
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://myanimelist.net/',
            ])->get("https://myanimelist.net/manga/{$id}");

            if ($response->successful()) {
                $html = $response->body();

                $title = $cached['title'] ?? '';
                if (empty($title) && preg_match('/<meta property="og:title" content="(.*?)"/i', $html, $m)) {
                    $title = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
                }

                $synopsis = '';
                if (preg_match('/<span itemprop="description">([\s\S]*?)<\/span>/i', $html, $m)) {
                    $synopsis = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8');
                    $synopsis = preg_replace('/\[Written by MAL Rewrite\]/i', '', $synopsis);
                }

                $authors = [];
                if (preg_match('/Authors?:<\/span>([\s\S]*?)<\/div>/i', $html, $m)) {
                    if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $m[1], $ma)) {
                        foreach ($ma[1] as $a) {
                            $parts = explode(',', $a);
                            $authors[] = count($parts) === 2 ? trim($parts[1]) . ' ' . trim($parts[0]) : trim($a);
                        }
                    }
                }

                $genres = [];
                if (preg_match_all('/<span class="dark_text">(Genre|Genres|Theme|Themes):<\/span>([\s\S]*?)<\/div>/i', $html, $mg, PREG_SET_ORDER)) {
                    foreach ($mg as $match) {
                        if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $match[2], $g)) {
                            foreach ($g[1] as $item) {
                                $trimmed = trim($item);
                                if (!empty($trimmed)) $genres[] = $trimmed;
                            }
                        }
                    }
                }

                $coverUrl = $cached['cover_url'] ?? null;
                if (empty($coverUrl) && preg_match('/<meta property="og:image" content="(.*?)"/i', $html, $m)) {
                    $coverUrl = $m[1];
                }

                $type = $cached['type'] ?? 'manga';
                if (preg_match('/Type:<\/span>[\s\n]*<a[^>]*>([^<]+)<\/a>/i', $html, $m)) {
                    $type = match (strtolower(trim($m[1]))) {
                        'manhwa' => 'manhwa',
                        'manhua' => 'manhua',
                        default => 'manga',
                    };
                }

                $status = $cached['status'] ?? 'en_cours';
                if (preg_match('/Status:<\/span>[\s\n]*([^<]+)/i', $html, $m)) {
                    $status = match (strtolower(trim($m[1]))) {
                        'finished' => 'termine',
                        'on hiatus' => 'pause',
                        'discontinued' => 'abandonne',
                        default => 'en_cours',
                    };
                }

                $year = $cached['release_year'] ?? null;
                if (!$year && preg_match('/Published:<\/span>[\s\n]*([A-Za-z]+ \d{1,2}, )?(\d{4})/i', $html, $m)) {
                    $year = (int) $m[2];
                }

                return [
                    'source' => 'myanimelist',
                    'id' => $id,
                    'title' => $title ?: 'Sans titre',
                    'alt_title' => $cached['alt_title'] ?? null,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => trim($synopsis),
                    'release_year' => $year,
                    'authors' => array_values(array_unique($authors)),
                    'artists' => [],
                    'genres' => array_values(array_unique($genres)),
                    'cover_url' => $coverUrl,
                    'banner_url' => null,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('MAL details scraping failed: ' . $e->getMessage());
        }

        // Fallback Jikan par ID si MAL direct a échoué
        try {
            $response = Http::timeout(6)->get("https://api.jikan.moe/v4/manga/{$id}");
            if ($response->successful()) {
                $item = $response->json('data', []);
                return [
                    'source' => 'myanimelist',
                    'id' => $id,
                    'title' => $item['title'] ?? ($cached['title'] ?? 'Sans titre'),
                    'alt_title' => $item['title_english'] ?? null,
                    'type' => strtolower($item['type'] ?? 'manga') === 'manhwa' ? 'manhwa' : 'manga',
                    'status' => strtolower($item['status'] ?? '') === 'finished' ? 'termine' : 'en_cours',
                    'synopsis' => trim(strip_tags($item['synopsis'] ?? '')),
                    'release_year' => $item['published']['prop']['from']['year'] ?? ($cached['release_year'] ?? null),
                    'authors' => array_column($item['authors'] ?? [], 'name'),
                    'artists' => [],
                    'genres' => array_column($item['genres'] ?? [], 'name'),
                    'cover_url' => $item['images']['webp']['large_image_url'] ?? ($cached['cover_url'] ?? null),
                    'banner_url' => null,
                ];
            }
        } catch (\Throwable $e) {}

        return $cached;
    }

    /**
     * Recherche via Kitsu.io (API REST JSON:API officielle)
     */
    protected function searchKitsu(string $query): array
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'Accept' => 'application/vnd.api+json',
                'User-Agent' => 'HiddenScan/1.0',
            ])->get('https://kitsu.io/api/edge/manga', [
                'filter[text]' => $query,
                'page[limit]' => 8,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json('data', []);
            $results = [];

            foreach ($data as $item) {
                $attrs = $item['attributes'] ?? [];

                $type = match (strtolower($attrs['subtype'] ?? 'manga')) {
                    'manhwa' => 'manhwa',
                    'manhua' => 'manhua',
                    default => 'manga',
                };

                $status = match (strtolower($attrs['status'] ?? '')) {
                    'finished' => 'termine',
                    'current' => 'en_cours',
                    'unreleased' => 'a_venir',
                    default => 'en_cours',
                };

                $year = !empty($attrs['startDate']) ? (int) substr($attrs['startDate'], 0, 4) : null;
                $coverUrl = $attrs['posterImage']['large'] ?? $attrs['posterImage']['original'] ?? null;

                $results[] = [
                    'source' => 'kitsu',
                    'id' => (string) $item['id'],
                    'title' => $attrs['canonicalTitle'] ?? $attrs['titles']['en'] ?? $attrs['titles']['en_jp'] ?? 'Sans titre',
                    'alt_title' => $attrs['titles']['en_jp'] ?? $attrs['titles']['ja_jp'] ?? null,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => trim($attrs['synopsis'] ?? ''),
                    'release_year' => $year,
                    'authors' => [],
                    'artists' => [],
                    'genres' => [],
                    'cover_url' => $coverUrl,
                    'banner_url' => $attrs['coverImage']['original'] ?? null,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('Kitsu API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails Kitsu avec catégories et genres
     */
    protected function getKitsuDetails(string $id, ?array $cached = null): ?array
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'Accept' => 'application/vnd.api+json',
                'User-Agent' => 'HiddenScan/1.0',
            ])->get("https://kitsu.io/api/edge/manga/{$id}", [
                'include' => 'categories,genres',
            ]);

            if ($response->successful()) {
                $item = $response->json('data', []);
                $attrs = $item['attributes'] ?? [];
                $included = $response->json('included', []);

                $genres = [];
                foreach ($included as $inc) {
                    if (isset($inc['attributes']['title'])) $genres[] = $inc['attributes']['title'];
                    if (isset($inc['attributes']['name'])) $genres[] = $inc['attributes']['name'];
                }

                $type = match (strtolower($attrs['subtype'] ?? 'manga')) {
                    'manhwa' => 'manhwa',
                    'manhua' => 'manhua',
                    default => 'manga',
                };

                $status = match (strtolower($attrs['status'] ?? '')) {
                    'finished' => 'termine',
                    'current' => 'en_cours',
                    'unreleased' => 'a_venir',
                    default => 'en_cours',
                };

                return [
                    'source' => 'kitsu',
                    'id' => $id,
                    'title' => $attrs['canonicalTitle'] ?? $attrs['titles']['en'] ?? ($cached['title'] ?? 'Sans titre'),
                    'alt_title' => $attrs['titles']['en_jp'] ?? null,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => trim($attrs['synopsis'] ?? ($cached['synopsis'] ?? '')),
                    'release_year' => !empty($attrs['startDate']) ? (int) substr($attrs['startDate'], 0, 4) : ($cached['release_year'] ?? null),
                    'authors' => $cached['authors'] ?? [],
                    'artists' => $cached['artists'] ?? [],
                    'genres' => array_values(array_unique($genres)),
                    'cover_url' => $attrs['posterImage']['original'] ?? $attrs['posterImage']['large'] ?? ($cached['cover_url'] ?? null),
                    'banner_url' => $attrs['coverImage']['original'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Kitsu getDetails Exception: ' . $e->getMessage());
        }

        return $cached;
    }

    /**
     * Recherche via MangaUpdates / Baka-Updates (API officielle v1)
     */
    protected function searchMangaUpdates(string $query): array
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'User-Agent' => 'HiddenScan/1.0',
                'Accept' => 'application/json',
            ])->post('https://api.mangaupdates.com/v1/series/search', [
                'search' => $query,
                'stype' => 'title',
                'perpage' => 8,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $results = [];
            $records = $response->json('results', []);

            foreach ($records as $item) {
                $rec = $item['record'] ?? [];
                if (empty($rec['series_id'])) continue;

                $type = match (strtolower($rec['type'] ?? 'manga')) {
                    'manhwa' => 'manhwa',
                    'manhua' => 'manhua',
                    default => 'manga',
                };

                $genres = array_column($rec['genres'] ?? [], 'genre');
                $coverUrl = $rec['image']['url']['original'] ?? $rec['image']['url']['thumb'] ?? null;
                $synopsis = html_entity_decode(strip_tags($rec['description'] ?? ''), ENT_QUOTES, 'UTF-8');

                $results[] = [
                    'source' => 'mangaupdates',
                    'id' => (string) $rec['series_id'],
                    'title' => html_entity_decode($rec['title'], ENT_QUOTES, 'UTF-8'),
                    'alt_title' => null,
                    'type' => $type,
                    'status' => 'en_cours',
                    'synopsis' => trim($synopsis),
                    'release_year' => !empty($rec['year']) ? (int) $rec['year'] : null,
                    'authors' => [],
                    'artists' => [],
                    'genres' => array_values(array_unique($genres)),
                    'cover_url' => $coverUrl,
                    'banner_url' => null,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('MangaUpdates API Exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails complets MangaUpdates (auteurs, statut, synopsis étendu)
     */
    protected function getMangaUpdatesDetails(string $id, ?array $cached = null): ?array
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'User-Agent' => 'HiddenScan/1.0',
                'Accept' => 'application/json',
            ])->get("https://api.mangaupdates.com/v1/series/{$id}");

            if ($response->successful()) {
                $data = $response->json();

                $type = match (strtolower($data['type'] ?? 'manga')) {
                    'manhwa' => 'manhwa',
                    'manhua' => 'manhua',
                    default => 'manga',
                };

                $statusStr = strtolower($data['status'] ?? '');
                $status = str_contains($statusStr, 'complete') ? 'termine' : (str_contains($statusStr, 'hiatus') ? 'pause' : 'en_cours');

                $authors = [];
                $artists = [];
                foreach ($data['authors'] ?? [] as $a) {
                    $name = trim($a['name'] ?? '');
                    if (empty($name)) continue;
                    $role = strtolower($a['type'] ?? '');
                    if (str_contains($role, 'art')) {
                        $artists[] = $name;
                    } else {
                        $authors[] = $name;
                    }
                }

                $genres = array_column($data['genres'] ?? [], 'genre');
                $categories = array_column($data['categories'] ?? [], 'category');
                $allGenres = array_merge($genres, array_slice($categories, 0, 5));

                $synopsis = html_entity_decode(strip_tags($data['description'] ?? ($cached['synopsis'] ?? '')), ENT_QUOTES, 'UTF-8');
                $coverUrl = $data['image']['url']['original'] ?? ($cached['cover_url'] ?? null);

                return [
                    'source' => 'mangaupdates',
                    'id' => $id,
                    'title' => html_entity_decode($data['title'] ?? ($cached['title'] ?? 'Sans titre'), ENT_QUOTES, 'UTF-8'),
                    'alt_title' => null,
                    'type' => $type,
                    'status' => $status,
                    'synopsis' => trim($synopsis),
                    'release_year' => !empty($data['year']) ? (int) $data['year'] : ($cached['release_year'] ?? null),
                    'authors' => array_values(array_unique($authors)),
                    'artists' => array_values(array_unique($artists)),
                    'genres' => array_values(array_unique($allGenres)),
                    'cover_url' => $coverUrl,
                    'banner_url' => null,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('MangaUpdates getDetails Exception: ' . $e->getMessage());
        }

        return $cached;
    }

    /**
     * Récupère les détails AniList par ID
     */
    protected function getAniListDetails(string $id, ?array $cached = null): ?array
    {
        if ($cached && !empty($cached['synopsis']) && !empty($cached['authors'])) {
            return $cached;
        }

        $graphql = <<<'GRAPHQL'
        query ($id: Int) {
          Media(id: $id, type: MANGA) {
            id
            title { romaji english native }
            description(asHtml: false)
            countryOfOrigin
            format
            status
            startDate { year }
            genres
            staff(perPage: 8) {
              edges {
                role
                node { name { full } }
              }
            }
            coverImage { extraLarge large }
            bannerImage
          }
        }
        GRAPHQL;

        try {
            $response = Http::timeout(8)->post('https://graphql.anilist.co', [
                'query' => $graphql,
                'variables' => ['id' => (int) $id],
            ]);

            if ($response->successful()) {
                $item = $response->json('data.Media');
                if ($item) {
                    $title = $item['title']['english'] ?? $item['title']['romaji'] ?? $item['title']['native'] ?? 'Sans titre';
                    $altTitle = $item['title']['romaji'] ?? null;
                    $type = match ($item['countryOfOrigin'] ?? null) {
                        'KR' => 'manhwa',
                        'CN', 'TW' => 'manhua',
                        default => ($item['format'] === 'MANHWA' ? 'manhwa' : ($item['format'] === 'MANHUA' ? 'manhua' : 'manga')),
                    };
                    $status = match ($item['status'] ?? null) {
                        'FINISHED' => 'termine',
                        'HIATUS' => 'pause',
                        'CANCELLED' => 'abandonne',
                        default => 'en_cours',
                    };
                    $authors = [];
                    $artists = [];
                    foreach ($item['staff']['edges'] ?? [] as $edge) {
                        $role = strtolower($edge['role'] ?? '');
                        $name = $edge['node']['name']['full'] ?? null;
                        if (!$name) continue;
                        if (str_contains($role, 'story') || str_contains($role, 'original') || str_contains($role, 'author')) {
                            $authors[] = $name;
                        } elseif (str_contains($role, 'art') || str_contains($role, 'illustrat')) {
                            $artists[] = $name;
                        }
                    }
                    return [
                        'source' => 'anilist',
                        'id' => (string) $item['id'],
                        'title' => $title,
                        'alt_title' => $altTitle,
                        'type' => $type,
                        'status' => $status,
                        'synopsis' => trim(strip_tags($item['description'] ?? '')),
                        'release_year' => $item['startDate']['year'] ?? null,
                        'authors' => array_values(array_unique($authors)),
                        'artists' => array_values(array_unique($artists)),
                        'genres' => array_values(array_unique($item['genres'] ?? [])),
                        'cover_url' => $item['coverImage']['extraLarge'] ?? $item['coverImage']['large'] ?? null,
                        'banner_url' => $item['bannerImage'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        return $cached;
    }

    /**
     * Récupère les détails MangaDex par ID
     */
    protected function getMangaDexDetails(string $id, ?array $cached = null): ?array
    {
        if ($cached && !empty($cached['synopsis']) && !empty($cached['authors'])) {
            return $cached;
        }

        try {
            $response = Http::timeout(8)->get("https://api.mangadex.org/manga/{$id}", [
                'includes' => ['author', 'artist', 'cover_art'],
            ]);

            if ($response->successful()) {
                $item = $response->json('data', []);
                $attrs = $item['attributes'] ?? [];
                $relationships = $item['relationships'] ?? [];

                $titles = $attrs['title'] ?? [];
                $title = $titles['fr'] ?? $titles['en'] ?? reset($titles) ?: 'Sans titre';
                $descriptions = $attrs['description'] ?? [];
                $synopsis = $descriptions['fr'] ?? $descriptions['en'] ?? reset($descriptions) ?: '';

                $authors = [];
                $artists = [];
                $coverFileName = null;
                foreach ($relationships as $rel) {
                    if ($rel['type'] === 'author' && !empty($rel['attributes']['name'])) {
                        $authors[] = $rel['attributes']['name'];
                    } elseif ($rel['type'] === 'artist' && !empty($rel['attributes']['name'])) {
                        $artists[] = $rel['attributes']['name'];
                    } elseif ($rel['type'] === 'cover_art' && !empty($rel['attributes']['fileName'])) {
                        $coverFileName = $rel['attributes']['fileName'];
                    }
                }

                $coverUrl = $coverFileName ? "https://uploads.mangadex.org/covers/{$id}/{$coverFileName}.512.jpg" : ($cached['cover_url'] ?? null);
                $genres = [];
                foreach ($attrs['tags'] ?? [] as $tag) {
                    if (!empty($tag['attributes']['name']['en'])) {
                        $genres[] = $tag['attributes']['name']['en'];
                    }
                }

                return [
                    'source' => 'mangadex',
                    'id' => $id,
                    'title' => $title,
                    'alt_title' => $titles['en'] ?? null,
                    'type' => match ($attrs['originalLanguage'] ?? '') { 'ko' => 'manhwa', 'zh' => 'manhua', default => 'manga' },
                    'status' => match ($attrs['status'] ?? '') { 'completed' => 'termine', 'hiatus' => 'pause', 'cancelled' => 'abandonne', default => 'en_cours' },
                    'synopsis' => trim(strip_tags($synopsis)),
                    'release_year' => !empty($attrs['year']) ? (int) $attrs['year'] : ($cached['release_year'] ?? null),
                    'authors' => array_values(array_unique($authors)),
                    'artists' => array_values(array_unique($artists)),
                    'genres' => array_values(array_unique($genres)),
                    'cover_url' => $coverUrl,
                    'banner_url' => null,
                ];
            }
        } catch (\Throwable $e) {}

        return $cached;
    }

    /**
     * Recherche via Jikan / MyAnimeList (REST v4 fallback)
     */
    protected function searchJikan(string $query): array
    {
        try {
            $response = Http::timeout(6)->get('https://api.jikan.moe/v4/manga', [
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
                $synopsis = preg_replace('/\[Written by MAL Rewrite\]/i', '', $synopsis);

                $results[] = [
                    'source' => 'myanimelist',
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
            Log::warning('Jikan API Exception: ' . $e->getMessage());
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
