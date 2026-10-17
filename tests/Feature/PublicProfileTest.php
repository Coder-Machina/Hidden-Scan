<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_can_be_viewed_by_anyone(): void
    {
        $user = User::factory()->create([
            'name' => 'Shadow Monk',
            'username' => 'shadowmonk',
            'bio' => 'Grand maître des arts martiaux.',
            'profile_public' => true,
        ]);

        $response = $this->get(route('profile.public', ['username' => 'shadowmonk']));

        $response->assertOk();
        $response->assertSee('Shadow Monk');
        $response->assertSee('@shadowmonk');
        $response->assertSee('Grand maître des arts martiaux.');
        $response->assertSee('Mangas lus');
        $response->assertSee('Favoris');
    }

    public function test_public_profile_returns_404_for_nonexistent_user(): void
    {
        $response = $this->get('/user/doesnotexist');

        $response->assertNotFound();
    }

    public function test_private_profile_returns_404_for_third_party(): void
    {
        $user = User::factory()->create([
            'username' => 'secretuser',
            'profile_public' => false,
        ]);

        // Guest
        $response = $this->get(route('profile.public', ['username' => 'secretuser']));
        $response->assertNotFound();

        // Autre utilisateur
        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)->get(route('profile.public', ['username' => 'secretuser']));
        $response->assertNotFound();
    }

    public function test_private_profile_can_be_viewed_by_owner(): void
    {
        $user = User::factory()->create([
            'name' => 'Owner Name',
            'username' => 'owneruser',
            'profile_public' => false,
        ]);

        $response = $this->actingAs($user)->get(route('profile.public', ['username' => 'owneruser']));

        $response->assertOk();
        $response->assertSee('Owner Name');
        $response->assertSee('Ce profil est privé.');
    }

    public function test_public_profile_shows_favorites_and_recents(): void
    {
        $user = User::factory()->create([
            'username' => 'otakureader',
            'profile_public' => true,
        ]);

        $manga = Manga::create([
            'title' => 'Legend of the Northern Blade',
            'slug' => 'legend-of-the-northern-blade',
            'synopsis' => 'A great martial arts story',
            'type' => 'manhwa',
            'status' => 'en_cours',
        ]);

        $chapter = \App\Models\Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
        ]);

        Favorite::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
        ]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
            'chapter_id' => $chapter->id,
            'is_read' => true,
        ]);

        $response = $this->get(route('profile.public', ['username' => 'otakureader']));

        $response->assertOk();
        $response->assertSee('Legend of the Northern Blade');
        $response->assertSee('Favoris');
        $response->assertSee('Récemment lus');
    }
}
