<?php

namespace Tests\Feature;

use App\Filament\Resources\Mangas\MangaResource;
use App\Filament\Resources\Mangas\RelationManagers\ChaptersRelationManager;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MangaChaptersPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapters_relation_manager_is_registered_on_manga_resource(): void
    {
        $relations = MangaResource::getRelations();

        $this->assertContains(ChaptersRelationManager::class, $relations);
    }

    public function test_global_chapters_page_redirects_to_mangas_index(): void
    {
        $admin = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole($role);

        $response = $this->actingAs($admin)->get('/admin/chapters');

        $response->assertStatus(302);
        $response->assertRedirect(MangaResource::getUrl('index'));
    }

    public function test_admin_can_access_manga_edit_page_with_chapters(): void
    {
        $admin = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole($role);

        $manga = Manga::create([
            'title' => 'Test Manga',
            'slug' => 'test-manga',
            'synopsis' => 'Test synopsis',
            'type' => \App\Enums\MangaType::MANHWA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Premier Chapitre',
            'slug' => 'chapitre-1',
            'status' => \App\Enums\ChapterStatus::PUBLIE,
        ]);

        $response = $this->actingAs($admin)->get(MangaResource::getUrl('edit', ['record' => $manga]));

        $response->assertStatus(200);
        $response->assertSee('Informations');
        $response->assertSee('Chapitres');
    }
}
