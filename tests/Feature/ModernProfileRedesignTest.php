<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Manga;
use App\Models\Chapter;
use App\Models\ReadingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModernProfileRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_contains_profile_dropdown_and_settings_link(): void
    {
        $user = User::factory()->create([
            'name' => 'GrandMaitreDao',
            'pass_code' => 'HS-TEST-PASS-1234',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('GrandMaitreDao');
        $response->assertSee('Menu profil utilisateur');
        $response->assertSee(route('profile.edit', ['tab' => 'overview']));
        $response->assertSee(route('profile.edit', ['tab' => 'settings']));
        $response->assertSee(route('library'));
        $response->assertSee(route('logout'));
    }

    public function test_profile_page_renders_with_hero_metrics_and_five_tabs(): void
    {
        $user = User::factory()->create([
            'name' => 'DiscipleCéleste',
            'pass_code' => 'HS-DISCIPLE-9999',
            'streak' => 12,
            'qi_shields' => 2,
            'bio' => 'Sur le chemin du Dao Martial infini.',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);

        // Hero Header & Identity
        $response->assertSee('DiscipleCéleste');
        $response->assertSee('12j');
        $response->assertSee('Carte Martiale');
        $response->assertSee('Modifier profil');
        $response->assertSee('Paramètres');

        // 5 Metric Cards
        $response->assertSee('Chapitres Lus');
        $response->assertSee('Favoris');
        $response->assertSee('Flamme Martiale');
        $response->assertSee('Commentaires');

        // 5 Modern Tabs
        $response->assertSee('Vue d\'ensemble', false);
        $response->assertSee('Favoris & Historique', false);
        $response->assertSee('Voie Martiale & Murim', false);
        $response->assertSee('Classement');
        $response->assertSee('Paramètres');

        // Murim & Dao features
        $response->assertSee('Guide des 10 Royaumes');
        $response->assertSee('Bête Spirituelle');
        $response->assertSee('Auras & Cadres d\'Avatar', false);
        $response->assertSee('Tier List S à F');

        // Settings Subtabs & Pass Secret
        $response->assertSee('Mon Profil');
        $response->assertSee('Sécurité & Pass', false);
        $response->assertSee('Zone de Danger');
        $response->assertSee('Mon Pass Secret (Connexion Anonyme)');
    }

    public function test_user_can_access_settings_tab_directly_via_query_param(): void
    {
        $user = User::factory()->create([
            'name' => 'CultivateurSettings',
            'pass_code' => 'HS-SETT-1111-2222',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'settings']));

        $response->assertStatus(200);
        $response->assertSee('currentTab: \'settings\'', false);
    }

    public function test_user_can_access_murim_tab_directly_via_query_param(): void
    {
        $user = User::factory()->create([
            'name' => 'CultivateurMurim',
            'pass_code' => 'HS-MURI-3333-4444',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'murim']));

        $response->assertStatus(200);
        $response->assertSee('currentTab: \'murim\'', false);
    }
}
