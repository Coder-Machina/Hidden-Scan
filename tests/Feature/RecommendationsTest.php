<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationsTest extends TestCase
{
    use RefreshDatabase;

    protected RecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecommendationService::class);
    }

    public function test_recommendation_service_scores_and_ranks_by_similarity(): void
    {
        $genreAction = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $genreRomance = Genre::create(['name' => 'Romance', 'slug' => 'romance']);
        $author = Author::create(['name' => 'Park Jin', 'slug' => 'park-jin']);

        $mangaA = Manga::create([
            'title' => 'Manga A',
            'slug' => 'manga-a',
            'synopsis' => 'Action adventure',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaA->genres()->attach($genreAction->id);
        $mangaA->authors()->attach($author->id);

        $mangaB = Manga::create([
            'title' => 'Manga B',
            'slug' => 'manga-b',
            'synopsis' => 'Another action story by same author',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaB->genres()->attach($genreAction->id);
        $mangaB->authors()->attach($author->id);

        $mangaC = Manga::create([
            'title' => 'Manga C',
            'slug' => 'manga-c',
            'synopsis' => 'Pure romance',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaC->genres()->attach($genreRomance->id);

        $recommendations = $this->service->getRecommendationsForManga($mangaA);

        $this->assertNotEmpty($recommendations);
        $topReco = $recommendations[0];
        $this->assertEquals($mangaB->id, $topReco['id']);
        $this->assertStringContainsString('Park Jin', $topReco['reason']);
        $this->assertStringContainsString('Action', $topReco['reason']);
        $this->assertGreaterThanOrEqual(80, $topReco['match_percent']);
    }

    public function test_authenticated_user_with_reading_progress_receives_recommendations_in_library(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Fantasy', 'slug' => 'fantasy']);

        $mangaA = Manga::create([
            'title' => 'Solo Hunter',
            'slug' => 'solo-hunter',
            'synopsis' => 'Hunter story',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaA->genres()->attach($genre->id);

        $chapterA = Chapter::create([
            'manga_id' => $mangaA->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $mangaB = Manga::create([
            'title' => 'Shadow Mage',
            'slug' => 'shadow-mage',
            'synopsis' => 'Mage adventure',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaB->genres()->attach($genre->id);

        Chapter::create([
            'manga_id' => $mangaB->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        // User reads manga A
        ReadingProgress::create([
            'user_id' => $user->id,
            'manga_id' => $mangaA->id,
            'chapter_id' => $chapterA->id,
            'is_read' => true,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/bibliotheque?tab=recommendations');

        $response->assertStatus(200);
        $response->assertSee('Si t\'as aimé X, lis Y', false);
        $response->assertSee('Solo Hunter');
    }

    public function test_api_recommendations_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create(['name' => 'Shonen', 'slug' => 'shonen']);

        $mangaA = Manga::create([
            'title' => 'Hero Legend',
            'slug' => 'hero-legend',
            'synopsis' => 'Hero story',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaA->genres()->attach($genre->id);

        $chapterA = Chapter::create([
            'manga_id' => $mangaA->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $mangaB = Manga::create([
            'title' => 'Dragon Sword',
            'slug' => 'dragon-sword',
            'synopsis' => 'Sword master',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaB->genres()->attach($genre->id);

        ReadingProgress::create([
            'user_id' => $user->id,
            'manga_id' => $mangaA->id,
            'chapter_id' => $chapterA->id,
            'is_read' => true,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/recommendations');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('recommendations'));
        $this->assertEquals('Hero Legend', $response->json('recommendations.0.source_manga.title'));
        $this->assertEquals('Dragon Sword', $response->json('recommendations.0.recommendations.0.title'));
    }

    public function test_api_recommendations_for_guest_with_slugs(): void
    {
        $genre = Genre::create(['name' => 'Cyberpunk', 'slug' => 'cyberpunk']);

        $mangaA = Manga::create([
            'title' => 'Neo Tokyo',
            'slug' => 'neo-tokyo',
            'synopsis' => 'Future city',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaA->genres()->attach($genre->id);

        $mangaB = Manga::create([
            'title' => 'Cyber Knight',
            'slug' => 'cyber-knight',
            'synopsis' => 'Cyborg knight',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaB->genres()->attach($genre->id);

        $response = $this->getJson('/api/recommendations?slugs=neo-tokyo');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('recommendations'));
        $this->assertEquals('Neo Tokyo', $response->json('recommendations.0.source_manga.title'));
        $this->assertEquals('Cyber Knight', $response->json('recommendations.0.recommendations.0.title'));
    }

    public function test_api_recommendations_returns_empty_when_no_history_or_progress(): void
    {
        $response = $this->getJson('/api/recommendations');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'recommendations' => [],
        ]);
    }
}
