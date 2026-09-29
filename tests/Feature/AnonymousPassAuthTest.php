<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnonymousPassAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_can_generate_an_anonymous_pass_in_one_click(): void
    {
        $response = $this->post(route('register.pass'), [
            'name' => 'ShadowReader',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('new_pass_code');

        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertNotNull($user->pass_code);
        $this->assertStringStartsWith('HS-', $user->pass_code);
        $this->assertEquals('ShadowReader', $user->name);
    }

    public function test_reader_can_login_with_pass_code(): void
    {
        $user = User::factory()->create([
            'name' => 'Kuro',
            'pass_code' => 'HS-7F2A-9K3M-P8X4',
        ]);

        $response = $this->post(route('login'), [
            'pass_code' => 'hs-7f2a-9k3m-p8x4', // Test case insensitivity & trimming
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_reader_cannot_login_with_invalid_pass_code(): void
    {
        $response = $this->post(route('login'), [
            'pass_code' => 'HS-INVALID-CODE-9999',
        ]);

        $response->assertSessionHasErrors('pass_code');
        $this->assertGuest();
    }

    public function test_banned_reader_cannot_login_with_pass_code(): void
    {
        $user = User::factory()->create([
            'pass_code' => 'HS-BANN-ED00-9999',
            'is_banned' => true,
            'ban_reason' => 'Spam répété',
        ]);

        $response = $this->post(route('login'), [
            'pass_code' => 'HS-BANN-ED00-9999',
        ]);

        $response->assertSessionHasErrors('pass_code');
        $this->assertGuest();
    }

    public function test_staff_can_still_login_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@hidden-scan.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@hidden-scan.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_and_register_pages_do_not_expose_staff_login(): void
    {
        $loginRes = $this->get(route('login'));
        $loginRes->assertStatus(200);
        $loginRes->assertDontSee('Email Staff');
        $loginRes->assertDontSee('Connexion Staff');
        $loginRes->assertDontSee('HS-7F2A-9K3M-P8X4');
        $loginRes->assertSee('HS-••••-••••-••••');

        $registerRes = $this->get(route('register'));
        $registerRes->assertStatus(200);
        $registerRes->assertDontSee('Créer un compte classique');
        $registerRes->assertDontSee('admin@hidden-scan.com');
    }

    public function test_reader_settings_shows_pass_manager_and_not_password_form(): void
    {
        $user = User::factory()->create([
            'name' => 'AnonReader',
            'pass_code' => 'HS-AAAA-BBBB-CCCC',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'settings']));
        $response->assertStatus(200);
        $response->assertSee('Mon Pass Secret');
        $response->assertSee('HS-AAAA-BBBB-CCCC');
        $response->assertDontSee('Modifier le mot de passe');
        $response->assertDontSee('Mot de passe actuel');
    }

    public function test_reader_can_regenerate_pass_secret(): void
    {
        $user = User::factory()->create([
            'pass_code' => 'HS-OLD1-OLD2-OLD3',
        ]);

        $response = $this->actingAs($user)->post(route('profile.pass.regenerate'));
        $response->assertRedirect(route('profile.edit', ['tab' => 'settings']));
        $response->assertSessionHas('status', 'pass-regenerated');

        $user->refresh();
        $this->assertNotEquals('HS-OLD1-OLD2-OLD3', $user->pass_code);
        $this->assertStringStartsWith('HS-', $user->pass_code);
    }

    public function test_reader_can_delete_account_without_password(): void
    {
        $user = User::factory()->create([
            'pass_code' => 'HS-DEL1-DEL2-DEL3',
        ]);

        $response = $this->actingAs($user)->delete(route('profile.destroy'));
        $response->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_authenticated_user_can_visit_register_and_see_current_account_notice(): void
    {
        $user = User::factory()->create([
            'name' => 'LecteurActuel',
            'pass_code' => 'HS-CURR-PASS-0001',
        ]);

        $response = $this->actingAs($user)->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Vous êtes actuellement connecté :');
        $response->assertSee('LecteurActuel');
        $response->assertSee('HS-CURR-PASS-0001');
    }

    public function test_authenticated_user_can_generate_a_new_pass_and_switch_accounts(): void
    {
        $oldUser = User::factory()->create([
            'name' => 'OldUser',
            'pass_code' => 'HS-OLDD-PASS-0001',
        ]);

        $response = $this->actingAs($oldUser)->post(route('register.pass'), [
            'name' => 'BrandNewUser',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $newUser = auth()->user();
        $this->assertNotEquals($oldUser->id, $newUser->id);
        $this->assertEquals('BrandNewUser', $newUser->name);
        $this->assertStringStartsWith('HS-', $newUser->pass_code);
    }
}
