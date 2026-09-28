<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Models\Chapter;
use App\Models\Favorite;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Manga $manga;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manga = Manga::create([
            'title' => 'Solo Leveling',
            'slug' => 'solo-leveling',
            'synopsis' => 'Chasseur de rang E',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);
    }

    public function test_user_can_toggle_favorite_via_api(): void
    {
        $this->actingAs($this->user);

        // Add favorite
        $response = $this->postJson('/api/favorites/toggle', [
            'slug' => $this->manga->slug,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_favorite' => true,
            ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);

        // Remove favorite
        $response2 = $this->postJson('/api/favorites/toggle', [
            'slug' => $this->manga->slug,
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_favorite' => false,
            ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);
    }

    public function test_user_can_sync_favorites_from_localstorage(): void
    {
        $this->actingAs($this->user);

        $manga2 = Manga::create([
            'title' => 'Omniscient Reader',
            'slug' => 'omniscient-reader',
            'type' => MangaType::MANHWA,
            'status' => MangaStatus::EN_COURS,
        ]);

        $response = $this->postJson('/api/favorites/sync', [
            'slugs' => [$this->manga->slug, $manga2->slug],
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'manga_id' => $manga2->id,
        ]);
    }

    public function test_publishing_chapter_creates_notification_for_favorite_users(): void
    {
        // User favorites the manga
        Favorite::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);

        // Second user who didn't favorite
        $otherUser = User::factory()->create();

        // Create and publish chapter
        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 10,
            'title' => 'L\'éveil des ombres',
            'slug' => 'chapitre-10',
            'status' => ChapterStatus::PUBLIE,
            'published_at' => now(),
        ]);

        // Verify notification in database for user
        $this->assertEquals(1, $this->user->notifications()->count());
        $notification = $this->user->notifications()->first();
        $this->assertEquals($chapter->id, $notification->data['chapter_id']);
        $this->assertEquals('Solo Leveling', $notification->data['manga_title']);
        $this->assertEquals(10, $notification->data['chapter_number']);

        // Verify other user did NOT receive notification
        $this->assertEquals(0, $otherUser->notifications()->count());
    }

    public function test_draft_chapter_does_not_notify_until_published(): void
    {
        Favorite::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);

        // Create draft chapter
        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 11,
            'title' => 'Brouillon',
            'slug' => 'chapitre-11',
            'status' => ChapterStatus::BROUILLON,
        ]);

        $this->assertEquals(0, $this->user->notifications()->count());

        // Update status to PUBLIE
        $chapter->status = ChapterStatus::PUBLIE;
        $chapter->published_at = now();
        $chapter->save();

        $this->assertEquals(1, $this->user->notifications()->count());
    }

    public function test_api_can_fetch_and_mark_notifications_as_read(): void
    {
        Favorite::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);

        $chapter = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $this->actingAs($this->user);

        // Fetch notifications
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 1,
            ]);

        $notifId = $response->json('notifications.0.id');

        // Mark as read
        $readResponse = $this->postJson("/api/notifications/{$notifId}/read");
        $readResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);

        // Verify unread count is 0
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    public function test_api_can_mark_all_notifications_as_read(): void
    {
        Favorite::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
        ]);

        Chapter::create(['manga_id' => $this->manga->id, 'number' => 1, 'slug' => 'ch-1', 'status' => ChapterStatus::PUBLIE]);
        Chapter::create(['manga_id' => $this->manga->id, 'number' => 2, 'slug' => 'ch-2', 'status' => ChapterStatus::PUBLIE]);

        $this->actingAs($this->user);

        $this->assertEquals(2, $this->user->unreadNotifications()->count());

        $response = $this->postJson('/api/notifications/mark-all-read');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 0,
            ]);

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }
}
