<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaderboardStaffExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'modo', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'uploader', 'guard_name' => 'web']);
    }

    public function test_admins_and_modos_are_never_included_in_leaderboard_even_with_reads()
    {
        $manga = Manga::create([
            'title' => 'Test Manga',
            'slug' => 'test-manga',
            'status' => 'en_cours',
            'type' => 'manhwa',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => 'publie',
        ]);

        // Récupérer l'admin (déjà créé par la migration clean slate) ou en créer un
        $admin = User::firstOrCreate(
            ['email' => 'meliodasdsama006@gmail.com'],
            ['name' => 'Meliodas', 'password' => bcrypt('password')]
        );
        $admin->update(['xp' => 500]);
        $admin->assignRole('admin');

        ReadingProgress::create([
            'user_id' => $admin->id,
            'chapter_id' => $chapter->id,
            'manga_id' => $manga->id,
            'is_read' => true,
        ]);

        // Créer un modo avec lecture et XP
        $modo = User::firstOrCreate(
            ['email' => 'modo@test.com'],
            ['name' => 'ModérateurTest', 'password' => bcrypt('password')]
        );
        $modo->update(['xp' => 300]);
        $modo->assignRole('modo');

        ReadingProgress::create([
            'user_id' => $modo->id,
            'chapter_id' => $chapter->id,
            'manga_id' => $manga->id,
            'is_read' => true,
        ]);

        // Créer un vrai lecteur classique
        $reader = User::create([
            'name' => 'LecteurFidèle',
            'email' => 'reader@test.com',
            'password' => bcrypt('password'),
            'xp' => 100,
        ]);

        ReadingProgress::create([
            'user_id' => $reader->id,
            'chapter_id' => $chapter->id,
            'manga_id' => $manga->id,
            'is_read' => true,
        ]);

        $leaderboard = LeaderboardService::getTopCultivators(10, $admin);

        // Seul le lecteur classique doit être classé
        $this->assertEquals(1, $leaderboard['total_cultivators']);
        $this->assertNull($leaderboard['current_user_rank']); // L'admin est hors classement
        $this->assertEquals('LecteurFidèle', $leaderboard['list'][0]['name'] ?? $leaderboard['all'][0]['name']);

        // Vérifier que ni l'admin ni le modo ne figurent dans le classement
        $rankedNames = collect($leaderboard['all'])->pluck('name')->toArray();
        $this->assertNotContains('Meliodas', $rankedNames);
        $this->assertNotContains('ModérateurTest', $rankedNames);
    }

    public function test_leaderboard_page_shows_staff_out_of_ranking_indicator()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@hiddenscan.com'],
            ['name' => 'AdminUser', 'password' => bcrypt('password')]
        );
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/classement');

        $response->assertStatus(200);
        $response->assertSee('Staff / Admin');
        $response->assertSee('(Hors classement)');
    }
}
