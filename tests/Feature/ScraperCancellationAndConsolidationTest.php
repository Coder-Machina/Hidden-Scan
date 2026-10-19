<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\Manga;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use App\Models\User;
use App\Services\ScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScraperCancellationAndConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Manga $manga;
    private ScraperSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);

        $this->manga = Manga::create([
            'title'       => 'Test Solo Leveling',
            'slug'        => 'test-solo-leveling',
            'synopsis'    => 'Un chasseur de rang E devient surpuissant.',
            'type'        => MangaType::MANGA,
            'status'      => MangaStatus::EN_COURS,
            'views_count' => 0,
        ]);

        $this->source = ScraperSource::create([
            'name'              => 'poseidon-scan',
            'base_url'          => 'https://poseidon-scans.net',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);
    }

    public function test_cancel_endpoint_sets_flag_and_cleans_queued_jobs(): void
    {
        $scraperManga = ScraperManga::create([
            'manga_id'          => $this->manga->id,
            'scraper_source_id' => $this->source->id,
            'source_url'        => 'https://poseidon-scans.net/serie/solo-leveling',
            'priority'          => 1,
            'status'            => 'importing',
            'cancel_requested'  => false,
        ]);

        // Insère un faux job dans la table jobs
        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->insert([
                'queue'        => 'scraper',
                'payload'      => json_encode([
                    'displayName' => 'App\\Jobs\\ScrapeNewChaptersJob',
                    'data' => [
                        'commandName' => 'App\\Jobs\\ScrapeNewChaptersJob',
                        'command'     => serialize(['id' => $scraperManga->id]),
                    ],
                    'id' => $scraperManga->id,
                ]),
                'attempts'     => 0,
                'reserved_at'  => null,
                'available_at' => now()->timestamp,
                'created_at'   => now()->timestamp,
            ]);
            $this->assertEquals(1, DB::table('jobs')->count());
        }

        $response = $this->actingAs($this->admin)->postJson(route('admin.scraper.cancel', $scraperManga));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status'  => 'annulé',
            ]);

        $scraperManga->refresh();
        $this->assertTrue($scraperManga->cancel_requested);
        $this->assertEquals('annulé', $scraperManga->status);

        if (Schema::hasTable('jobs')) {
            $this->assertEquals(0, DB::table('jobs')->count());
        }
    }

    public function test_scraper_job_exits_cleanly_at_beginning_of_chapter_if_cancelled(): void
    {
        $scraperManga = ScraperManga::create([
            'manga_id'                 => $this->manga->id,
            'scraper_source_id'        => $this->source->id,
            'source_url'               => 'https://poseidon-scans.net/serie/solo-leveling',
            'priority'                 => 1,
            'status'                   => 'importing',
            'cancel_requested'         => true,
            'current_scraping_chapter' => '5',
            'last_chapter_scraped'     => '4',
        ]);

        $job = new ScrapeNewChaptersJob($scraperManga);
        $service = app(ScraperService::class);
        $job->handle($service);

        $scraperManga->refresh();
        $this->assertEquals('annulé', $scraperManga->status);
        $this->assertFalse($scraperManga->cancel_requested);
        $this->assertNull($scraperManga->current_scraping_chapter);
        $this->assertEquals('4', $scraperManga->last_chapter_scraped);
    }

    public function test_cancellation_during_chapter_purges_incomplete_chapter_and_retains_completed(): void
    {
        // 1. Chapitre 1 déjà terminé et publié
        $chap1 = Chapter::create([
            'manga_id'     => $this->manga->id,
            'number'       => 1,
            'title'        => 'Chapitre 1',
            'slug'         => 'chapitre-1',
            'status'       => ChapterStatus::PUBLIE,
            'published_at' => now(),
        ]);
        ChapterPage::create([
            'chapter_id'  => $chap1->id,
            'image_path'  => 'chapters/' . $chap1->id . '/page_1.jpg',
            'page_number' => 1,
        ]);
        Storage::disk('public')->put('chapters/' . $chap1->id . '/page_1.jpg', 'img-data');

        $scraperManga = ScraperManga::create([
            'manga_id'             => $this->manga->id,
            'scraper_source_id'    => $this->source->id,
            'source_url'           => 'https://poseidon-scans.net/serie/solo-leveling',
            'priority'             => 1,
            'status'               => 'importing',
            'cancel_requested'     => false,
            'last_chapter_scraped' => '1',
        ]);

        // Simule le téléchargement du chapitre 2 qui compte 3 pages
        $fakeImg = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        Http::fake([
            'https://poseidon-scans.net/ch2' => Http::response('
                <div class="page-break"><img src="https://poseidon-scans.net/p1.jpg" /></div>
                <div class="page-break"><img src="https://poseidon-scans.net/p2.jpg" /></div>
                <div class="page-break"><img src="https://poseidon-scans.net/p3.jpg" /></div>
            ', 200),
            'https://poseidon-scans.net/p1.jpg' => function () use ($scraperManga, $fakeImg) {
                // Simule que l'admin clique sur "Annuler l'import" pendant qu'il télécharge l'image
                $scraperManga->update(['cancel_requested' => true]);
                return Http::response($fakeImg, 200, ['Content-Type' => 'image/gif']);
            },
            'https://poseidon-scans.net/p2.jpg' => Http::response($fakeImg, 200, ['Content-Type' => 'image/gif']),
            'https://poseidon-scans.net/p3.jpg' => Http::response($fakeImg, 200, ['Content-Type' => 'image/gif']),
        ]);

        $service = app(ScraperService::class);
        $resultChap2 = $service->scrapeSingleChapter($scraperManga, [
            'number' => 2,
            'title'  => 'Chapitre 2',
            'url'    => 'https://poseidon-scans.net/ch2',
        ]);

        // Le chapitre 2 incomplet a été supprimé proprement
        $this->assertNull($resultChap2);
        $this->assertFalse(Chapter::where('manga_id', $this->manga->id)->where('number', 2)->exists());

        // Le chapitre 1 complété auparavant reste intact et publié !
        $this->assertTrue(Chapter::where('manga_id', $this->manga->id)->where('number', 1)->exists());
        $this->assertEquals(1, $this->manga->chapters()->count());
        $this->assertTrue(Storage::disk('public')->exists('chapters/' . $chap1->id . '/page_1.jpg'));

        // Le statut du scraperManga est "annulé" avec arrêt au chapitre 1
        $scraperManga->refresh();
        $this->assertEquals('annulé', $scraperManga->status);
        $this->assertFalse($scraperManga->cancel_requested);
        $this->assertEquals('1', $scraperManga->last_chapter_scraped);
    }

    public function test_status_endpoint_returns_exact_labels_for_all_states(): void
    {
        $scraperManga = ScraperManga::create([
            'manga_id'                 => $this->manga->id,
            'scraper_source_id'        => $this->source->id,
            'source_url'               => 'https://poseidon-scans.net/serie/solo-leveling',
            'priority'                 => 1,
            'status'                   => 'importing',
            'current_scraping_chapter' => '4',
            'total_chapters_detected'  => 67,
            'last_chapter_scraped'     => '3',
        ]);

        // 1. Statut importing
        $res = $this->actingAs($this->admin)->getJson(route('admin.scraper.status', $scraperManga));
        $res->assertOk();
        $this->assertEquals('Import en cours — Chapitre 4 / 67', $res->json('status_text'));
        $this->assertTrue($res->json('is_importing'));
        $this->assertEquals(6, $res->json('progress_percent')); // 4/67 = ~6%

        // 2. Statut active
        $scraperManga->update([
            'status'               => 'active',
            'last_chapter_scraped' => '67',
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('admin.scraper.status', $scraperManga));
        $this->assertStringContainsString('Actif — Dernier import : Ch. 67', $res->json('status_text'));
        $this->assertFalse($res->json('is_importing'));
        $this->assertEquals(100, $res->json('progress_percent'));

        // 3. Statut annulé
        $scraperManga->update([
            'status'               => 'annulé',
            'last_chapter_scraped' => '4',
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('admin.scraper.status', $scraperManga));
        $this->assertEquals('Annulé — Arrêté au Ch. 4 / 67', $res->json('status_text'));
        $this->assertEquals(5, $res->json('next_chapter')); // Permet "Reprendre depuis Ch. 5"

        // 4. Statut paused
        $scraperManga->update([
            'status'               => 'paused',
            'last_chapter_scraped' => '67',
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('admin.scraper.status', $scraperManga));
        $this->assertEquals('En pause — Ch. 67 importé', $res->json('status_text'));

        // 5. Statut error
        $scraperManga->update([
            'status'     => 'error',
            'last_error' => 'Cloudflare 403 Forbidden',
        ]);
        $res = $this->actingAs($this->admin)->getJson(route('admin.scraper.status', $scraperManga));
        $this->assertEquals('Erreur — Cloudflare 403 Forbidden', $res->json('status_text'));
    }

    public function test_attach_endpoint_adds_source_and_dispatches_job(): void
    {
        Queue::fake();

        $res = $this->actingAs($this->admin)->postJson(route('admin.scraper.attach'), [
            'manga_id'   => $this->manga->id,
            'name'       => 'Asura Scans',
            'source_url' => 'https://asuracomic.net/series/solo-leveling',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'scraperManga' => [
                    'source_name'  => 'Asura Scans',
                    'status'       => 'importing',
                    'status_text'  => 'Import en cours — Ch. 0 / ...',
                    'is_importing' => true,
                ],
            ]);

        $this->assertDatabaseHas('scraper_mangas', [
            'manga_id'   => $this->manga->id,
            'source_url' => 'https://asuracomic.net/series/solo-leveling',
            'status'     => 'importing',
        ]);

        Queue::assertPushedOn('scraper', ScrapeNewChaptersJob::class);
    }

    public function test_reorder_endpoint_updates_sources_priorities(): void
    {
        $sm1 = ScraperManga::create([
            'manga_id'          => $this->manga->id,
            'scraper_source_id' => $this->source->id,
            'source_url'        => 'https://source-1.test/manga',
            'priority'          => 1,
            'status'            => 'active',
        ]);

        $source2 = ScraperSource::create([
            'name'              => 'Source 2',
            'base_url'          => 'https://source-2.test',
            'sel_chapter_list'  => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url'   => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active'            => true,
        ]);

        $sm2 = ScraperManga::create([
            'manga_id'          => $this->manga->id,
            'scraper_source_id' => $source2->id,
            'source_url'        => 'https://source-2.test/manga',
            'priority'          => 2,
            'status'            => 'active',
        ]);

        // Inverse l'ordre : source 2 en premier, source 1 en deuxième
        $res = $this->actingAs($this->admin)->patchJson(route('admin.scraper.reorder', $sm1), [
            'order' => [$sm2->id, $sm1->id],
        ]);

        $res->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(1, $sm2->fresh()->priority);
        $this->assertEquals(2, $sm1->fresh()->priority);
    }

    public function test_detach_endpoint_removes_source(): void
    {
        $sm = ScraperManga::create([
            'manga_id'          => $this->manga->id,
            'scraper_source_id' => $this->source->id,
            'source_url'        => 'https://source-to-delete.test/manga',
            'priority'          => 1,
            'status'            => 'active',
        ]);

        $res = $this->actingAs($this->admin)->deleteJson(route('admin.scraper.detach', $sm));

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('scraper_mangas', ['id' => $sm->id]);
    }
}
