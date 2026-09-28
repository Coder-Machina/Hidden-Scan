<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingProgressTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Manga $manga;
    protected Chapter $chapter1;
    protected Chapter $chapter2;
    protected Chapter $chapter3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manga = Manga::create([
            'title' => 'Test Manga',
            'slug' => 'test-manga',
            'synopsis' => 'A test synopsis',
            'type' => \App\Enums\MangaType::MANGA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
        ]);

        $this->chapter1 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'title' => 'Chapter 1',
            'slug' => 'chapter-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $this->chapter2 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 2,
            'title' => 'Chapter 2',
            'slug' => 'chapter-2',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $this->chapter3 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 3,
            'title' => 'Chapter 3',
            'slug' => 'chapter-3',
            'status' => ChapterStatus::PUBLIE,
        ]);
    }

    public function test_guest_cannot_toggle_progress()
    {
        $response = $this->postJson('/api/progress/toggle', [
            'chapter_id' => $this->chapter1->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_toggle_chapter_read_status()
    {
        // 1. Toggle ON
        $response = $this->actingAs($this->user)->postJson('/api/progress/toggle', [
            'chapter_id' => $this->chapter1->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_read' => true,
                'chapter_id' => $this->chapter1->id,
                'read_count' => 1,
                'total_chapters' => 3,
            ]);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $this->user->id,
            'chapter_id' => $this->chapter1->id,
            'is_read' => true,
        ]);

        // 2. Toggle OFF
        $response2 = $this->actingAs($this->user)->postJson('/api/progress/toggle', [
            'chapter_id' => $this->chapter1->id,
        ]);

        $response2->assertOk()
            ->assertJson([
                'success' => true,
                'is_read' => false,
                'read_count' => 0,
            ]);
    }

    public function test_user_can_mark_chapters_up_to_specified_chapter()
    {
        $response = $this->actingAs($this->user)->postJson('/api/progress/mark-up-to', [
            'chapter_id' => $this->chapter2->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'marked_count' => 2,
                'read_count' => 2,
                'total_chapters' => 3,
            ]);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $this->user->id,
            'chapter_id' => $this->chapter1->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $this->user->id,
            'chapter_id' => $this->chapter2->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseMissing('reading_progress', [
            'user_id' => $this->user->id,
            'chapter_id' => $this->chapter3->id,
        ]);
    }

    public function test_get_manga_progress()
    {
        ReadingProgress::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $this->chapter1->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/progress/' . $this->manga->id);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'manga_id' => $this->manga->id,
                'read_count' => 1,
                'total_chapters' => 3,
                'read_chapter_ids' => [$this->chapter1->id],
            ]);
    }

    public function test_get_catch_up_api_returns_unread_chapters_sorted_by_release_date()
    {
        // Set distinct release dates
        $this->chapter2->update(['published_at' => now()->subDays(2)]);
        $this->chapter3->update(['published_at' => now()->subHour()]);

        // User has read chapter 1 only (manga is ongoing with 2 unread chapters)
        ReadingProgress::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $this->chapter1->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/progress/catch-up');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'unread_total' => 2,
            ]);

        $data = $response->json('catch_up');
        $this->assertCount(2, $data);
        // Chapter 3 is newer than Chapter 2, so it must be first
        $this->assertEquals($this->chapter3->id, $data[0]['chapter_id']);
        $this->assertEquals($this->chapter2->id, $data[1]['chapter_id']);
    }

    public function test_catch_up_excludes_completed_series_and_unstarted_series()
    {
        // 1. Unstarted manga
        $unstartedManga = Manga::create([
            'title' => 'Unstarted Manga',
            'slug' => 'unstarted-manga',
            'type' => \App\Enums\MangaType::MANGA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
        ]);
        Chapter::create([
            'manga_id' => $unstartedManga->id,
            'number' => 1,
            'title' => 'Ch 1',
            'slug' => 'ch-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        // 2. Mark ALL chapters of $this->manga as read
        ReadingProgress::create(['user_id' => $this->user->id, 'manga_id' => $this->manga->id, 'chapter_id' => $this->chapter1->id, 'is_read' => true]);
        ReadingProgress::create(['user_id' => $this->user->id, 'manga_id' => $this->manga->id, 'chapter_id' => $this->chapter2->id, 'is_read' => true]);
        ReadingProgress::create(['user_id' => $this->user->id, 'manga_id' => $this->manga->id, 'chapter_id' => $this->chapter3->id, 'is_read' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/progress/catch-up');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'catch_up' => [],
                'unread_total' => 0,
            ]);
    }

    public function test_library_view_renders_catch_up_tab_successfully()
    {
        // Read 1 chapter so catch up has 2 unread
        ReadingProgress::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $this->chapter1->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/bibliotheque');
        $response->assertOk()
            ->assertSee('Rattrapage')
            ->assertSee('Mode Rattrapage')
            ->assertSee('Chapitres non lus');
    }

    public function test_catch_up_full_lifecycle_mark_as_read()
    {
        // Manga has 3 chapters. User reads chapter 1.
        ReadingProgress::create([
            'user_id' => $this->user->id,
            'manga_id' => $this->manga->id,
            'chapter_id' => $this->chapter1->id,
            'is_read' => true,
        ]);

        // 1. Initial catch-up has 2 unread chapters
        $r1 = $this->actingAs($this->user)->getJson('/api/progress/catch-up');
        $r1->assertOk()->assertJson(['unread_total' => 2]);

        // 2. Mark chapter 2 as read via toggle
        $toggleResp = $this->actingAs($this->user)->postJson('/api/progress/toggle', [
            'chapter_id' => $this->chapter2->id,
        ]);
        $toggleResp->assertOk()->assertJson(['success' => true, 'is_read' => true]);

        // 3. Catch-up now has only 1 unread chapter (chapter 3)
        $r2 = $this->actingAs($this->user)->getJson('/api/progress/catch-up');
        $r2->assertOk()->assertJson(['unread_total' => 1]);
        $this->assertEquals($this->chapter3->id, $r2->json('catch_up.0.chapter_id'));

        // 4. Mark chapter 3 as read via toggle
        $this->actingAs($this->user)->postJson('/api/progress/toggle', [
            'chapter_id' => $this->chapter3->id,
        ]);

        // 5. Catch-up is now completely clear (0 unread, series completed)
        $r3 = $this->actingAs($this->user)->getJson('/api/progress/catch-up');
        $r3->assertOk()->assertJson(['unread_total' => 0, 'catch_up' => []]);
    }
}

