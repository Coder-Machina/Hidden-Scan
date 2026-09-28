<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_featured_mangas_link_to_their_detail_page(): void
    {
        $manga = \App\Models\Manga::create([
            'title' => 'Featured Hero Manga',
            'slug' => 'featured-hero-manga',
            'synopsis' => 'Un manga mis en avant.',
            'type' => \App\Enums\MangaType::MANHWA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Featured Hero Manga');
        $response->assertSee(route('manga.show', 'featured-hero-manga'));
    }
}
