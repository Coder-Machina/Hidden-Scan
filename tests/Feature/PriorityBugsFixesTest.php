<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityBugsFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BUG 1: Barre "Reprendre la lecture"
     */
    public function test_bug1_resume_bar_hidden_for_guests(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertDontSee('resume-bar');
        $response->assertDontSee('REPRENDRE LA LECTURE');
    }

    public function test_bug1_resume_bar_hidden_for_user_with_no_history(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertOk();
        $response->assertDontSee('resume-bar');
        $response->assertDontSee('REPRENDRE LA LECTURE');
    }

    public function test_bug1_resume_bar_shown_for_authenticated_user_with_reading_history(): void
    {
        $user = User::factory()->create();

        $manga = Manga::create([
            'title' => 'Solo Hunter',
            'slug' => 'solo-hunter',
            'synopsis' => 'Hunter adventure',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 42,
            'title' => 'Chapitre 42',
            'slug' => 'chapitre-42',
            'status' => ChapterStatus::PUBLIE,
        ]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
            'chapter_id' => $chapter->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertOk();
        $response->assertSee('resume-bar');
        $response->assertSee('REPRENDRE LA LECTURE');
        $response->assertSee('Solo Hunter');
        $response->assertSee('Chapitre 42');
        $response->assertSee(route('chapter.show', ['solo-hunter', 'chapitre-42']));
    }

    /**
     * BUG 2: Statut formaté sans underscore
     */
    public function test_bug2_statut_formate_accessor_removes_underscore(): void
    {
        $manga = new Manga(['status' => MangaStatus::EN_COURS]);
        $this->assertEquals('EN COURS', $manga->statut_formate);

        $mangaTermine = new Manga(['status' => MangaStatus::TERMINE]);
        $this->assertEquals('TERMINÉ', $mangaTermine->statut_formate);

        $mangaPause = new Manga(['status' => MangaStatus::PAUSE]);
        $this->assertEquals('EN PAUSE', $mangaPause->statut_formate);
    }

    public function test_bug2_statut_formate_displayed_on_views(): void
    {
        $manga = Manga::create([
            'title' => 'Martial Peak Test',
            'slug' => 'martial-peak-test',
            'synopsis' => 'A martial journey',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
            'is_featured' => true,
        ]);

        Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        // Sur la page d'accueil (Hero)
        $homeRes = $this->get(route('home'));
        $homeRes->assertOk();
        $homeRes->assertSee('EN COURS');
        $homeRes->assertDontSee('EN_COURS');

        // Sur la fiche détail
        $showRes = $this->get(route('manga.show', $manga->slug));
        $showRes->assertOk();
        $showRes->assertSee('EN COURS');
        $showRes->assertDontSee('EN_COURS');
    }

    /**
     * BUG 3: Mangas sans chapitres exclus des sections
     */
    public function test_bug3_mangas_without_chapters_are_not_featured_or_popular(): void
    {
        // Manga sans chapitre
        $emptyManga = Manga::create([
            'title' => 'Manga Vide Sans Chapitre',
            'slug' => 'manga-vide',
            'synopsis' => 'Aucun chapitre',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
            'is_featured' => true,
            'views_count' => 99999,
        ]);

        // Manga avec chapitre
        $activeManga = Manga::create([
            'title' => 'Manga Actif Avec Chapitres',
            'slug' => 'manga-actif',
            'synopsis' => 'Avec chapitre',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
            'is_featured' => true,
            'views_count' => 500,
        ]);

        Chapter::create([
            'manga_id' => $activeManga->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Manga Actif Avec Chapitres');
        $response->assertDontSee('Manga Vide Sans Chapitre');
    }

    /**
     * BUG 4: Balises Open Graph & og:image
     */
    public function test_bug4_open_graph_image_tags_present(): void
    {
        $this->assertFileExists(public_path('images/og-default.jpg'));

        $manga = Manga::create([
            'title' => 'Manga Cover Test',
            'slug' => 'manga-cover-test',
            'synopsis' => 'Synopsis',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
            'cover_image' => 'https://example.com/cover.jpg',
        ]);

        Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Ch 1',
            'slug' => 'ch-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        // Page d'accueil : image par défaut
        $homeRes = $this->get(route('home'));
        $homeRes->assertOk();
        $homeRes->assertSee('<meta property="og:image"', false);
        $homeRes->assertSee('images/og-default.jpg');
        $homeRes->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

        // Fiche manga : image personnalisée du manga
        $showRes = $this->get(route('manga.show', $manga->slug));
        $showRes->assertOk();
        $showRes->assertSee('https://example.com/cover.jpg');
    }

    /**
     * BUG 5: Une seule navbar dans le DOM avec conditions internes
     */
    public function test_bug5_single_navbar_and_conditional_links(): void
    {
        // Visiteur non connecté
        $guestRes = $this->get(route('home'));
        $guestRes->assertOk();

        // Une seule balise <nav id="main-navbar"
        $this->assertEquals(1, substr_count($guestRes->getContent(), '<nav id="main-navbar"'));
        
        // Extraire la navbar
        preg_match('/<nav id="main-navbar".*?<\/nav>/s', $guestRes->getContent(), $guestMatches);
        $this->assertNotEmpty($guestMatches);
        $guestNavbar = $guestMatches[0];

        $this->assertStringContainsString('Surprends-moi', $guestNavbar);
        $this->assertStringContainsString(route('manga.random'), $guestNavbar);
        $this->assertStringContainsString('Se connecter', $guestNavbar);
        $this->assertStringNotContainsString('Bibliothèque', $guestNavbar);

        // Utilisateur connecté
        $user = User::factory()->create();
        $userRes = $this->actingAs($user)->get(route('home'));
        $userRes->assertOk();

        $this->assertEquals(1, substr_count($userRes->getContent(), '<nav id="main-navbar"'));

        preg_match('/<nav id="main-navbar".*?<\/nav>/s', $userRes->getContent(), $userMatches);
        $this->assertNotEmpty($userMatches);
        $userNavbar = $userMatches[0];

        $this->assertStringContainsString(route('library'), $userNavbar);
        $this->assertStringContainsString('Bibliothèque', $userNavbar);
        $this->assertStringNotContainsString('Se connecter', $userNavbar);
        $this->assertStringNotContainsString('Surprends-moi', $userNavbar);
    }
}
