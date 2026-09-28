<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_se_connecter_in_navbar(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('login'));
        $response->assertSee('Se connecter');
        $response->assertSee('title="Menu principal"', false);
    }

    public function test_authenticated_user_sees_avatar_and_profile_menu_in_navbar(): void
    {
        $user = User::factory()->create([
            'name' => 'JeanDupont',
            'email' => 'jean@test.com',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('Mon Profil');
        $response->assertSee('Mode Rattrapage');
        $response->assertSee('Ma Bibliothèque');
        $response->assertSee('Se déconnecter');
        $response->assertSee('userMobileMenuOpen');
        $response->assertDontSee('title="Menu principal"', false);
    }
}
