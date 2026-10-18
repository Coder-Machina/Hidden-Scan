<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_navbar_contains_mobile_and_desktop_labels_and_classes(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);

        // Check navbar structure
        $response->assertSee('navbar__logo', false);
        $response->assertSee('navbar__logo-text', false);
        $response->assertSee('navbar__search-icon', false);
        $response->assertSee('navbar__auth', false);

        // Check responsive labels for login
        $response->assertSee('label-desktop">Se connecter</span>', false);
        $response->assertSee('label-mobile">Connexion</span>', false);

        // Check responsive labels for register
        $response->assertSee('label-desktop">S\'inscrire</span>', false);
        $response->assertSee('label-mobile">Inscription</span>', false);

        // Check that register button is not hidden with Tailwind's hidden sm:inline-flex
        $response->assertDontSee('btn-secondary hidden sm:inline-flex', false);
        $response->assertSee('btn-register', false);
        $response->assertSee('btn-login', false);
    }

    public function test_css_contains_mobile_navbar_rules(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('.navbar__logo span', $css);
        $this->assertStringContainsString('.navbar__logo-text', $css);
        $this->assertStringContainsString('.navbar__auth', $css);
        $this->assertStringContainsString('a.btn-login', $css);
        $this->assertStringContainsString('a.btn-register', $css);
        $this->assertStringContainsString('.label-desktop', $css);
        $this->assertStringContainsString('.label-mobile', $css);
    }
}
