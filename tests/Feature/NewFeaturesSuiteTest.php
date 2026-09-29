<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\ChapterReport;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\User;
use App\Services\DiscordWebhookService;
use App\Services\MangaMetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewFeaturesSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected Manga $manga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manga = Manga::create([
            'title' => 'Test Manga Scheduler',
            'slug' => 'test-manga-scheduler',
            'synopsis' => 'Un manga pour tester les nouvelles fonctionnalités.',
            'type' => 'manga',
            'status' => 'en_cours',
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       1. RELEASE SCHEDULER TESTS
       ═══════════════════════════════════════════════════════ */

    public function test_scheduled_chapter_is_hidden_from_public_reader(): void
    {
        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 10,
            'slug' => 'chapitre-10',
            'status' => ChapterStatus::PROGRAMME,
            'scheduled_at' => now()->addDays(2),
        ]);

        ChapterPage::create([
            'chapter_id' => $chapter->id,
            'page_number' => 1,
            'image_path' => 'chapters/10/1.webp',
        ]);

        // Essai d'accès public au chapitre programmé
        $response = $this->get(route('chapter.show', [$this->manga->slug, $chapter->slug]));

        $response->assertStatus(404);
    }

    public function test_scheduled_chapter_publishes_when_due(): void
    {
        // Chapitre dont l'heure de publication est passée
        $dueChapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PROGRAMME,
            'scheduled_at' => now()->subMinute(),
        ]);

        // Chapitre programmé pour demain
        $futureChapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 2,
            'slug' => 'chapitre-2',
            'status' => ChapterStatus::PROGRAMME,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->artisan('chapters:publish-scheduled')
            ->assertExitCode(0);

        // Le chapitre échu doit être passé en PUBLIE
        $this->assertEquals(ChapterStatus::PUBLIE, $dueChapter->fresh()->status);
        $this->assertNotNull($dueChapter->fresh()->published_at);

        // Le chapitre futur doit rester PROGRAMME
        $this->assertEquals(ChapterStatus::PROGRAMME, $futureChapter->fresh()->status);
        $this->assertNull($futureChapter->fresh()->published_at);
    }

    /* ═══════════════════════════════════════════════════════
       2. DISCORD WEBHOOK TESTS
       ═══════════════════════════════════════════════════════ */

    public function test_discord_command_fails_when_unconfigured(): void
    {
        config(['services.discord.webhook_url' => null]);

        $this->artisan('discord:test')
            ->assertExitCode(1);
    }

    public function test_discord_command_succeeds_when_webhook_responds(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.com/api/webhooks/test/mock']);

        Http::fake([
            'https://discord.com/api/webhooks/*' => Http::response([], 204),
        ]);

        $this->artisan('discord:test')
            ->assertExitCode(0);
    }

    public function test_discord_service_sends_chapter_embed(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.com/api/webhooks/test/mock']);

        Http::fake([
            'https://discord.com/api/webhooks/*' => Http::response([], 204),
        ]);

        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 5,
            'title' => 'L\'éveil des pouvoirs',
            'slug' => 'chapitre-5',
            'status' => ChapterStatus::PUBLIE,
            'published_at' => now(),
        ]);

        $service = app(DiscordWebhookService::class);
        $result = $service->sendChapterNotification($chapter);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            $embed = $request->data()['embeds'][0] ?? [];
            return str_contains($embed['title'], 'Test Manga Scheduler')
                && str_contains($embed['description'], 'Chapitre 5');
        });
    }

    /* ═══════════════════════════════════════════════════════
       3. MANGA METADATA SERVICE TESTS
       ═══════════════════════════════════════════════════════ */

    public function test_metadata_service_syncs_relations_correctly(): void
    {
        $service = app(MangaMetadataService::class);

        $relations = $service->syncRelations([
            'genres' => ['Action', 'Aventure', 'Fantaisie'],
            'authors' => ['Chugong'],
            'artists' => ['DUBU'],
        ]);

        $this->assertCount(3, $relations['genre_ids']);
        $this->assertCount(1, $relations['author_ids']);
        $this->assertCount(1, $relations['artist_ids']);

        $this->assertDatabaseHas('genres', ['name' => 'Action', 'slug' => 'action']);
        $this->assertDatabaseHas('authors', ['name' => 'Chugong', 'slug' => 'chugong']);
        $this->assertDatabaseHas('artists', ['name' => 'DUBU', 'slug' => 'dubu']);
    }

    public function test_metadata_service_translates_english_genres_to_french(): void
    {
        $service = app(MangaMetadataService::class);

        $translated = $service->translateGenres(['Fantasy', 'Sci-Fi', 'Supernatural', 'Martial Arts', 'Slice of Life']);

        $this->assertEquals(['Fantastique', 'Science-Fiction', 'Surnaturel', 'Arts Martiaux', 'Tranche de vie'], $translated);
    }

    public function test_import_metadata_action_selected_item_resolves_label_without_exception(): void
    {
        $action = \App\Filament\Resources\Mangas\Actions\ImportMetadataAction::make();
        $schema = $action->getSchema(\Filament\Schemas\Schema::make());

        $selectedItemField = collect($schema->getComponents())->first(fn ($c) => $c->getName() === 'selected_item');

        $this->assertNotNull($selectedItemField);

        \Illuminate\Support\Facades\Cache::put('manga_meta:anilist:12345', [
            'id' => '12345',
            'title' => 'Test Solo Leveling',
            'alt_title' => 'Only I Level Up',
            'type' => 'manhwa',
            'status' => 'termine',
            'release_year' => 2018,
            'synopsis' => 'Test',
            'source' => 'anilist',
        ], 600);

        $refProp = new \ReflectionProperty($selectedItemField, 'getOptionLabelUsing');
        $refProp->setAccessible(true);
        $closure = $refProp->getValue($selectedItemField);

        $this->assertNotNull($closure);

        $label = $closure('anilist::12345');

        $this->assertNotNull($label);
        $this->assertStringContainsString('Test Solo Leveling', $label);
        $this->assertStringContainsString('(2018)', $label);
        $this->assertStringContainsString('[MANHWA]', $label);
    }

    /* ═══════════════════════════════════════════════════════
       4. CHAPTER REPORTS TESTS
       ═══════════════════════════════════════════════════════ */

    public function test_reader_can_submit_chapter_report(): void
    {
        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('chapter.report', $chapter->id), [
            'type' => 'page_manquante',
            'page_number' => 7,
            'message' => 'La page 7 ne s\'affiche pas',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('chapter_reports', [
            'chapter_id' => $chapter->id,
            'manga_id' => $this->manga->id,
            'type' => 'page_manquante',
            'page_number' => 7,
            'status' => 'en_attente',
            'message' => 'La page 7 ne s\'affiche pas',
        ]);
    }

    public function test_reports_relation_manager_badge_reflects_pending_count(): void
    {
        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
            'published_at' => now(),
        ]);

        ChapterReport::create([
            'chapter_id' => $chapter->id,
            'manga_id' => $this->manga->id,
            'type' => 'ordre_inverse',
            'status' => 'en_attente',
        ]);

        ChapterReport::create([
            'chapter_id' => $chapter->id,
            'manga_id' => $this->manga->id,
            'type' => 'mauvaise_traduction',
            'status' => 'resolu',
        ]);

        $badge = \App\Filament\Resources\Mangas\RelationManagers\ReportsRelationManager::getBadge(
            $this->manga,
            \App\Filament\Resources\Mangas\Pages\EditManga::class
        );

        // Seul le signalement en attente doit être compté dans le badge
        $this->assertEquals('1', $badge);
    }

    public function test_edit_manga_fill_form_method_exists_and_can_be_called(): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $component = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Mangas\Pages\EditManga::class, [
                'record' => $this->manga->id,
            ]);

        $component->assertSuccessful();
        $component->call('fillForm');
        $component->assertSuccessful();
    }
}
