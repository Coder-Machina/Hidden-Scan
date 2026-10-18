<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StrictRolesAndTwoSecondsScrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_modo_and_uploader_roles_exist_after_seeding(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $allowedRoles = ['admin', 'modo', 'uploader'];
        $dbRoles = array_values(array_unique(array_map('strtolower', Role::pluck('name')->toArray())));

        $this->assertEqualsCanonicalizing($allowedRoles, $dbRoles);
        $this->assertNotContains('owner', $dbRoles);
        $this->assertNotContains('traducteur', $dbRoles);
        $this->assertNotContains('cleaner', $dbRoles);
    }

    public function test_homepage_hero_slider_contains_two_second_auto_scroll(): void
    {
        $manga = Manga::create([
            'title' => 'Solo Leveling Hero',
            'slug' => 'solo-leveling-hero',
            'status' => 'en_cours',
            'is_featured' => true,
        ]);

        Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Ch 1',
            'slug' => 'ch-1',
            'status' => 'publie',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('startAutoplay', false);
        $response->assertSee('2000', false); // 2-second interval
    }

    public function test_chapter_reader_contains_two_second_auto_scroll_toggle(): void
    {
        $manga = Manga::create([
            'title' => 'Tower of God',
            'slug' => 'tower-of-god',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Ch 1',
            'slug' => 'ch-1',
            'status' => 'publie',
        ]);

        $response = $this->get(route('chapter.show', [$manga->slug, $chapter->slug]));

        $response->assertStatus(200);
        $response->assertSee('toggleAutoScroll', false);
        $response->assertSee('startAutoScroll', false);
        $response->assertSee('2000', false);
        $response->assertSee('Défilement 2s', false);
    }
}
