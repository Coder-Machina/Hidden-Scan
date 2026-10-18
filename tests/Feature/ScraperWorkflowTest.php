<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Jobs\ScrapeFullMangaJob;
use App\Jobs\ScrapeMultiSourceMangaJob;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use App\Models\User;
use App\Services\ScraperService;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
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
            'priority'             => 1,
            'last_chapter_scraped' => '1',
            'status'               => 'active',
        ]);

        $this->assertTrue($manga->scraperManga->is($scraperManga));
        $this->assertCount(1, $manga->scraperMangas);
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
            'priority'          => 1,
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
            'priority'          => 1,
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

    public function test_filament_scraper_source_crud(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        // 1. List
        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ScraperSources\Pages\ListScraperSources::class)
            ->assertSuccessful();

        // 2. Create
        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ScraperSources\Pages\CreateScraperSource::class)
            ->fillForm([
                'name'              => 'Flame Comics',
                'base_url'          => 'https://flamecomics.xyz',
                'sel_chapter_list'  => '.chapter-item',
                'sel_chapter_title' => '.chapter-item a',
                'sel_chapter_url'   => '.chapter-item a',
                'sel_chapter_pages' => '.reading-content img',
                'active'            => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('scraper_sources', [
            'name'     => 'Flame Comics',
            'base_url' => 'https://flamecomics.xyz',
        ]);
    }

    public function test_intelligent_relay_takes_over_when_primary_source_is_behind(): void
    {
        $sourcePrimary = ScraperSource::create([
            'name'              => 'Source A (Primary)',
            'base_url'          => 'https://source-a.test',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $sourceBackup = ScraperSource::create([
            'name'              => 'Source B (Backup Relay)',
            'base_url'          => 'https://source-b.test',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $manga = Manga::create([
            'title'       => 'Multi Source Manga',
            'slug'        => 'multi-source-manga',
            'synopsis'    => 'Synopsis',
            'type'        => MangaType::MANGA,
            'status'      => MangaStatus::EN_COURS,
            'views_count' => 0,
        ]);

        // Source 1 (Priorité 1)
        $sm1 = ScraperManga::create([
            'manga_id'          => $manga->id,
            'scraper_source_id' => $sourcePrimary->id,
            'source_url'        => 'https://source-a.test/manga/test',
            'priority'          => 1,
            'status'            => 'active',
        ]);

        // Source 2 (Priorité 2 - Secours)
        $sm2 = ScraperManga::create([
            'manga_id'          => $manga->id,
            'scraper_source_id' => $sourceBackup->id,
            'source_url'        => 'https://source-b.test/manga/test',
            'priority'          => 2,
            'status'            => 'active',
        ]);

        // Source 1 est en retard : elle a seulement le chapitre 1
        $htmlSourceA = '<li class="wp-manga-chapter"><a href="https://source-a.test/ch1">Chapitre 1</a></li>';

        // Source 2 est plus à jour : elle a le chapitre 1 ET le chapitre 2 !
        $htmlSourceB = '
            <li class="wp-manga-chapter"><a href="https://source-b.test/ch1">Chapitre 1</a></li>
            <li class="wp-manga-chapter"><a href="https://source-b.test/ch2">Chapitre 2</a></li>
        ';

        $fakeImg = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        Http::fake([
            'https://source-a.test/manga/test' => Http::response($htmlSourceA, 200),
            'https://source-a.test/ch1' => Http::response('<div class="page-break"><img src="https://source-a.test/p1.jpg" /></div>', 200),
            'https://source-a.test/p1.jpg' => Http::response($fakeImg, 200, ['Content-Type' => 'image/gif']),

            'https://source-b.test/manga/test' => Http::response($htmlSourceB, 200),
            'https://source-b.test/ch2' => Http::response('<div class="page-break"><img src="https://source-b.test/p2.jpg" /></div>', 200),
            'https://source-b.test/p2.jpg' => Http::response($fakeImg, 200, ['Content-Type' => 'image/gif']),
        ]);

        $service = new ScraperService();
        $result = $service->syncMangaMultiSources($manga);

        // 2 chapitres importés au total : Chap 1 via Source A, puis Chap 2 relayé via Source B !
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(2, $manga->chapters()->count());
        $this->assertTrue($manga->chapters()->where('number', 1)->exists());
        $this->assertTrue($manga->chapters()->where('number', 2)->exists());
        $this->assertEquals('1', $sm1->fresh()->last_chapter_scraped);
        $this->assertEquals('2', $sm2->fresh()->last_chapter_scraped);
    }

    public function test_intelligent_relay_falls_back_when_primary_source_fails(): void
    {
        $sourcePrimary = ScraperSource::create([
            'name'              => 'Broken Source A',
            'base_url'          => 'https://broken-a.test',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $sourceBackup = ScraperSource::create([
            'name'              => 'Working Source B',
            'base_url'          => 'https://working-b.test',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $manga = Manga::create([
            'title'       => 'Broken Primary Manga',
            'slug'        => 'broken-primary-manga',
            'synopsis'    => 'Synopsis',
            'type'        => MangaType::MANGA,
            'status'      => MangaStatus::EN_COURS,
            'views_count' => 0,
        ]);

        $sm1 = ScraperManga::create([
            'manga_id'          => $manga->id,
            'scraper_source_id' => $sourcePrimary->id,
            'source_url'        => 'https://broken-a.test/manga/test',
            'priority'          => 1,
            'status'            => 'active',
        ]);

        $sm2 = ScraperManga::create([
            'manga_id'          => $manga->id,
            'scraper_source_id' => $sourceBackup->id,
            'source_url'        => 'https://working-b.test/manga/test',
            'priority'          => 2,
            'status'            => 'active',
        ]);

        $fakeImg = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        Http::fake([
            // Source 1 plante avec une erreur 500 serveur
            'https://broken-a.test/*' => Http::response('Internal Server Error', 500),

            // Source 2 fonctionne et fournit le chapitre 1
            'https://working-b.test/manga/test' => Http::response('<li class="wp-manga-chapter"><a href="https://working-b.test/ch1">Chapitre 1</a></li>', 200),
            'https://working-b.test/ch1' => Http::response('<div class="page-break"><img src="https://working-b.test/p1.jpg" /></div>', 200),
            'https://working-b.test/p1.jpg' => Http::response($fakeImg, 200, ['Content-Type' => 'image/gif']),
        ]);

        $service = new ScraperService();
        $result = $service->syncMangaMultiSources($manga);

        // Le relais a fonctionné : Source A est en erreur, Source B a pris le relais et importé le chapitre 1 !
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals('error', $sm1->fresh()->status);
        $this->assertNotNull($sm1->fresh()->last_error);

        $this->assertEquals('active', $sm2->fresh()->status);
        $this->assertEquals('1', $sm2->fresh()->last_chapter_scraped);
        $this->assertEquals(1, $manga->chapters()->count());
    }
}
