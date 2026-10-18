<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Jobs\ScrapeFullMangaJob;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use App\Models\User;
use App\Services\ScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScraperWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_scraper_source_and_manga_relationships(): void
    {
        $source = ScraperSource::create([
            'name'              => 'poseidon-scan',
            'base_url'          => 'https://poseidon-scan.net',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_date'  => '.chapter-release-date',
            'sel_chapter_pages' => '.page-break img',
            'headers'           => ['User-Agent' => 'TestBot'],
            'active'            => true,
        ]);

        $manga = Manga::create([
            'title'       => 'Test Manga',
            'slug'        => 'test-manga',
            'synopsis'    => 'Un synopsis de test.',
            'type'        => MangaType::MANGA,
            'status'      => MangaStatus::EN_COURS,
            'views_count' => 0,
        ]);

        $scraperManga = ScraperManga::create([
            'manga_id'             => $manga->id,
            'scraper_source_id'    => $source->id,
            'source_url'           => 'https://poseidon-scan.net/manga/test-manga',
            'last_chapter_scraped' => '1',
            'status'               => 'active',
        ]);

        $this->assertTrue($manga->scraperManga->is($scraperManga));
        $this->assertTrue($scraperManga->manga->is($manga));
        $this->assertTrue($scraperManga->scraperSource->is($source));
    }

    public function test_scraper_controller_sync_and_toggle_routes(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $source = ScraperSource::create([
            'name'              => 'poseidon-scan',
            'base_url'          => 'https://poseidon-scan.net',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $manga = Manga::create([
            'title'       => 'Test Manga Sync',
            'slug'        => 'test-manga-sync',
            'synopsis'    => 'Synopsis',
            'type'        => MangaType::MANGA,
            'status'      => MangaStatus::EN_COURS,
            'views_count' => 0,
        ]);

        $scraperManga = ScraperManga::create([
            'manga_id'          => $manga->id,
            'scraper_source_id' => $source->id,
            'source_url'        => 'https://poseidon-scan.net/manga/test-manga-sync',
            'status'            => 'active',
        ]);

        // Sync route test
        $response = $this->actingAs($user)->post(route('admin.scraper.sync', $scraperManga));
        $response->assertRedirect();
        Queue::assertPushedOn('scraper', ScrapeNewChaptersJob::class);

        // Toggle route test
        $this->assertEquals('active', $scraperManga->fresh()->status);
        $toggleResponse = $this->actingAs($user)->patch(route('admin.scraper.toggle', $scraperManga));
        $toggleResponse->assertRedirect();
        $this->assertEquals('paused', $scraperManga->fresh()->status);

        // Toggle back
        $this->actingAs($user)->patch(route('admin.scraper.toggle', $scraperManga));
        $this->assertEquals('active', $scraperManga->fresh()->status);
    }

    public function test_scraper_service_parses_chapters_and_images_correctly(): void
    {
        $source = ScraperSource::create([
            'name'              => 'poseidon-scan',
            'base_url'          => 'https://poseidon-scan.net',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_date'  => '.chapter-release-date',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $manga = Manga::create([
            'title'       => 'Solo Leveling Test',
            'slug'        => 'solo-leveling-test',
            'synopsis'    => 'Synopsis',
            'type'        => MangaType::MANHWA,
            'status'      => MangaStatus::EN_COURS,
            'views_count' => 0,
        ]);

        $scraperManga = ScraperManga::create([
            'manga_id'          => $manga->id,
            'scraper_source_id' => $source->id,
            'source_url'        => 'https://poseidon-scan.net/manga/solo-leveling-test',
            'status'            => 'active',
        ]);

        $mockMangaHtml = '
            <div class="chapters-container">
                <li class="wp-manga-chapter">
                    <a href="https://poseidon-scan.net/manga/solo-leveling-test/chapitre-2">Chapitre 2</a>
                    <span class="chapter-release-date">Hier</span>
                </li>
                <li class="wp-manga-chapter">
                    <a href="https://poseidon-scan.net/manga/solo-leveling-test/chapitre-1">Chapitre 1</a>
                    <span class="chapter-release-date">Il y a 2 jours</span>
                </li>
            </div>
        ';

        $mockChapterHtml = '
            <div class="reading-content">
                <div class="page-break"><img data-src="https://poseidon-scan.net/uploads/p1.jpg" /></div>
                <div class="page-break"><img src="https://poseidon-scan.net/uploads/p2.jpg" /></div>
            </div>
        ';

        // Fake 1x1 transparent GIF/JPEG image content
        $fakeImageBytes = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        Http::fake([
            'https://poseidon-scan.net/manga/solo-leveling-test' => Http::response($mockMangaHtml, 200),
            'https://poseidon-scan.net/manga/solo-leveling-test/chapitre-1' => Http::response($mockChapterHtml, 200),
            'https://poseidon-scan.net/uploads/*' => Http::response($fakeImageBytes, 200, ['Content-Type' => 'image/gif']),
        ]);

        $service = new ScraperService();

        // 1. Test chapter list
        $chapters = $service->getChapterList($scraperManga);
        $this->assertCount(2, $chapters);
        // Sequential check (sorted ascending)
        $this->assertEquals(1.0, $chapters[0]['number']);
        $this->assertEquals('https://poseidon-scan.net/manga/solo-leveling-test/chapitre-1', $chapters[0]['url']);
        $this->assertEquals(2.0, $chapters[1]['number']);

        // 2. Test pages scraping
        $pageUrls = $service->scrapeChapterPages($source, $chapters[0]['url']);
        $this->assertCount(2, $pageUrls);
        $this->assertEquals('https://poseidon-scan.net/uploads/p1.jpg', $pageUrls[0]);
        $this->assertEquals('https://poseidon-scan.net/uploads/p2.jpg', $pageUrls[1]);

        // 3. Test single chapter scraping & page creation
        $chapter = $service->scrapeSingleChapter($scraperManga, $chapters[0]);
        $this->assertNotNull($chapter);
        $this->assertEquals(1.0, (float) $chapter->number);
        $this->assertEquals('chapitre-1', $chapter->slug);
        $this->assertEquals(ChapterStatus::PUBLIE, $chapter->status);
        $this->assertEquals(2, $chapter->pages()->count());
        $this->assertEquals('1', $scraperManga->fresh()->last_chapter_scraped);
    }
}
