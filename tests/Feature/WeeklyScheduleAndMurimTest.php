<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MurimRankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyScheduleAndMurimTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_page_renders_successfully(): void
    {
        $response = $this->get(route('planning'));

        $response->assertStatus(200);
        $response->assertSee('Planning des Sorties');
        $response->assertSee('Lundi');
        $response->assertSee('Dimanche');
    }

    public function test_murim_service_calculates_correct_ranks(): void
    {
        // 0 chapters -> Initié Martial
        $rank0 = MurimRankService::getRankForCount(0);
        $this->assertEquals(1, $rank0['id']);
        $this->assertEquals('Initié Martial', $rank0['title']);

        // 15 chapters -> Disciple Émérite
        $rank15 = MurimRankService::getRankForCount(15);
        $this->assertEquals(2, $rank15['id']);
        $this->assertEquals('Disciple Émérite', $rank15['title']);

        // 35 chapters -> Guerrier d'Élite
        $rank35 = MurimRankService::getRankForCount(35);
        $this->assertEquals(3, $rank35['id']);
        $this->assertEquals("Guerrier d'Élite", $rank35['title']);

        // 70 chapters -> Adepte du Qi Pur
        $rank70 = MurimRankService::getRankForCount(70);
        $this->assertEquals(4, $rank70['id']);
        $this->assertEquals('Adepte du Qi Pur', $rank70['title']);

        // 120 chapters -> Maître Martial
        $rank120 = MurimRankService::getRankForCount(120);
        $this->assertEquals(5, $rank120['id']);
        $this->assertEquals('Maître Martial', $rank120['title']);
    }

    public function test_murim_api_returns_status_for_guest(): void
    {
        $response = $this->getJson(route('api.progress.murim', [
            'local_read_count' => 15,
            'streak' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => [
                'read_count' => 15,
                'streak' => 3,
                'current' => [
                    'id' => 2,
                    'title' => 'Disciple Émérite',
                ],
            ],
        ]);
    }

    public function test_home_page_displays_planning_and_discord_dojo(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Planning des Sorties');
        $response->assertSee('Rejoins la communauté');
        $response->assertSee('https://discord.gg/sMUeFSks4');
    }

    public function test_authenticated_user_can_choose_spiritual_beast(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('profile.spiritual_beast'), [
            'spiritual_beast' => 'lunar_wolf',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'beast_status' => [
                'beast' => [
                    'key' => 'lunar_wolf',
                    'name' => "Loup Lunaire d'Argent",
                ],
            ],
        ]);

        $this->assertEquals('lunar_wolf', $user->fresh()->spiritual_beast);
    }

    public function test_authenticated_user_can_equip_unlocked_avatar_frame(): void
    {
        $user = User::factory()->create();

        // Should succeed for novice frame
        $response = $this->actingAs($user)->postJson(route('profile.avatar_frame'), [
            'avatar_frame' => 'novice',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'frame' => [
                'key' => 'novice',
            ],
        ]);

        $this->assertEquals('novice', $user->fresh()->avatar_frame);
    }

    public function test_user_cannot_equip_locked_frame_without_enough_chapters(): void
    {
        $user = User::factory()->create();

        // divine_sovereign requires 500 chapters
        $response = $this->actingAs($user)->postJson(route('profile.avatar_frame'), [
            'avatar_frame' => 'divine_sovereign',
        ]);

        $response->assertStatus(422);
        $this->assertNotEquals('divine_sovereign', $user->fresh()->avatar_frame);
    }
}
