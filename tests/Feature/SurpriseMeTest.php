<?php

namespace Tests\Feature;

use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurpriseMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirects_to_random_manga(): void
    {
        $manga = Manga::create([
            'title' => 'Solo Leveling',
            'slug' => 'solo-leveling',
            'synopsis' => 'Hunter story',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::TERMINE,
        ]);

        $response = $this->get('/surprise-moi');

        $response->assertStatus(302);
        $response->assertRedirect(route('manga.show', $manga->slug));
        $response->assertSessionHas('surprise_info');
    }

    public function test_user_with_favorite_genre_targets_matching_manga(): void
    {
        $genreAction = Genre::create(['name' => 'Action', 'slug' => 'action']);
        $genreRomance = Genre::create(['name' => 'Romance', 'slug' => 'romance']);

        $mangaAction = Manga::create([
            'title' => 'Action Manga',
            'slug' => 'action-manga',
            'synopsis' => 'Action packed',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaAction->genres()->attach($genreAction->id);

        $mangaRomance = Manga::create([
            'title' => 'Romance Manga',
            'slug' => 'romance-manga',
            'synopsis' => 'Love story',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaRomance->genres()->attach($genreRomance->id);

        $user = User::factory()->create([
            'favorite_genre' => 'Action',
        ]);

        $response = $this->actingAs($user)->get('/surprise-moi');

        $response->assertStatus(302);
        $response->assertRedirect(route('manga.show', $mangaAction->slug));
        $response->assertSessionHas('surprise_info', function ($info) {
            return str_contains($info, 'Action');
        });
    }

    public function test_user_with_favorites_history_targets_matching_genres(): void
    {
        $genreShonen = Genre::create(['name' => 'Shonen', 'slug' => 'shonen']);
        $genreSeinen = Genre::create(['name' => 'Seinen', 'slug' => 'seinen']);

        $manga1 = Manga::create([
            'title' => 'Shonen 1',
            'slug' => 'shonen-1',
            'synopsis' => 'Adventure 1',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $manga1->genres()->attach($genreShonen->id);

        $manga2 = Manga::create([
            'title' => 'Shonen 2',
            'slug' => 'shonen-2',
            'synopsis' => 'Adventure 2',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $manga2->genres()->attach($genreShonen->id);

        $user = User::factory()->create();
        // Add manga1 to favorites
        $user->favorites()->create(['manga_id' => $manga1->id]);

        $response = $this->actingAs($user)->get('/surprise-moi');

        $response->assertStatus(302);
        // It should prioritize manga2 because it has the same genre but is not yet in favorites
        $response->assertRedirect(route('manga.show', $manga2->slug));
        $response->assertSessionHas('surprise_info', function ($info) {
            return str_contains($info, 'Shonen');
        });
    }

    public function test_guest_with_favs_param_targets_matching_manga(): void
    {
        $genreCyber = Genre::create(['name' => 'Cyberpunk', 'slug' => 'cyberpunk']);

        $favManga = Manga::create([
            'title' => 'Ghost In Shell',
            'slug' => 'ghost-in-shell',
            'synopsis' => 'Cyborg',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::TERMINE,
        ]);
        $favManga->genres()->attach($genreCyber->id);

        $recManga = Manga::create([
            'title' => 'Akira',
            'slug' => 'akira',
            'synopsis' => 'Neo Tokyo',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::TERMINE,
        ]);
        $recManga->genres()->attach($genreCyber->id);

        $response = $this->get('/surprise-moi?favs=ghost-in-shell');

        $response->assertStatus(302);
        $response->assertSessionHas('surprise_info', function ($info) {
            return str_contains($info, 'Cyberpunk');
        });
    }

    public function test_explicit_genre_param_targets_requested_genre(): void
    {
        $genreComedy = Genre::create(['name' => 'Comédie', 'slug' => 'comedie']);
        $genreDrama = Genre::create(['name' => 'Drame', 'slug' => 'drame']);

        $mangaComedy = Manga::create([
            'title' => 'Funny Manga',
            'slug' => 'funny-manga',
            'synopsis' => 'Laughter',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaComedy->genres()->attach($genreComedy->id);

        $mangaDrama = Manga::create([
            'title' => 'Sad Manga',
            'slug' => 'sad-manga',
            'synopsis' => 'Tears',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);
        $mangaDrama->genres()->attach($genreDrama->id);

        $response = $this->get('/surprise-moi?genre=comedie');

        $response->assertStatus(302);
        $response->assertRedirect(route('manga.show', $mangaComedy->slug));
        $response->assertSessionHas('surprise_info', function ($info) {
            return str_contains($info, 'Comédie');
        });
    }

    public function test_when_no_manga_exists_redirects_to_catalog_with_error(): void
    {
        $response = $this->get('/surprise-moi');

        $response->assertStatus(302);
        $response->assertRedirect(route('manga.index'));
        $response->assertSessionHas('error');
    }
}
