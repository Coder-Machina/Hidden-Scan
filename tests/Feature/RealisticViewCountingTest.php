<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RealisticViewCountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createManga(array $attributes = []): Manga
    {
        return Manga::create(array_merge([
            'title' => 'Test Manga',
            'slug' => 'test-manga-' . uniqid(),
            'synopsis' => 'A test synopsis',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
            'views_count' => 0,
        ], $attributes));
    }

    private function createChapter(Manga $manga, array $attributes = []): Chapter
    {
        return Chapter::create(array_merge([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapter 1',
            'slug' => 'chapter-1-' . uniqid(),
            'status' => ChapterStatus::PUBLIE,
            'views_count' => 0,
        ], $attributes));
    }

    public function test_record_view_increments_chapter_and_manga_views(): void
    {
        $manga = $this->createManga(['views_count' => 10]);
        $chapter = $this->createChapter($manga, ['views_count' => 5]);

        $counted = $chapter->recordView('192.168.1.100');

        $this->assertTrue($counted);
        $this->assertEquals(6, $chapter->fresh()->views_count);
        $this->assertEquals(11, $manga->fresh()->views_count);
    }

    public function test_record_view_respects_cooldown_anti_spam(): void
    {
        $manga = $this->createManga();
        $chapter = $this->createChapter($manga);

        // Premier appel : comptabilisé
        $firstCall = $chapter->recordView('192.168.1.50');
        $this->assertTrue($firstCall);
        $this->assertEquals(1, $chapter->fresh()->views_count);
        $this->assertEquals(1, $manga->fresh()->views_count);

        // Deuxième appel immédiat (spam / F5) : ignoré
        $secondCall = $chapter->recordView('192.168.1.50');
        $this->assertFalse($secondCall);
        $this->assertEquals(1, $chapter->fresh()->views_count);
        $this->assertEquals(1, $manga->fresh()->views_count);

        // Appel d'une autre IP : comptabilisé
        $thirdCall = $chapter->recordView('192.168.1.51');
        $this->assertTrue($thirdCall);
        $this->assertEquals(2, $chapter->fresh()->views_count);
        $this->assertEquals(2, $manga->fresh()->views_count);
    }

    public function test_track_view_endpoint_records_reading_and_syncs_progress_for_user(): void
    {
        $user = User::factory()->create();
        $manga = $this->createManga();
        $chapter = $this->createChapter($manga);

        $response = $this->actingAs($user)
            ->postJson(route('chapter.track_view', $chapter->id));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'counted' => true,
                'views_count' => 1,
            ]);

        $this->assertEquals(1, $chapter->fresh()->views_count);
        $this->assertEquals(1, $manga->fresh()->views_count);

        // Vérifier que la progression de lecture a été synchronisée
        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
            'manga_id' => $manga->id,
            'is_read' => true,
        ]);
    }

    public function test_visiting_manga_details_page_does_not_increment_views(): void
    {
        $manga = $this->createManga(['views_count' => 0]);

        // La simple visite de la fiche manga ne doit JAMAIS compter de vue
        $this->get(route('manga.show', $manga->slug));
        $this->assertEquals(0, $manga->fresh()->views_count);

        $this->get(route('manga.show', $manga->slug));
        $this->assertEquals(0, $manga->fresh()->views_count);
    }
}
