<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffRolesAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_without_staff_role_cannot_access_admin_panel(): void
    {
        $reader = User::factory()->create();

        $response = $this->actingAs($reader)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_role_has_red_badge_and_can_access_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        $badge = $admin->staff_badge;

        $this->assertNotNull($badge);
        $this->assertEquals('Admin', $badge['name']);
        $this->assertEquals('red', $badge['color']);
        $this->assertEquals('#dc2626', $badge['hex']);
        $this->assertTrue($admin->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_modo_role_has_purple_badge_and_can_access_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'modo']);
        $modo = User::factory()->create();
        $modo->assignRole($role);

        $badge = $modo->staff_badge;

        $this->assertNotNull($badge);
        $this->assertEquals('Modo', $badge['name']);
        $this->assertEquals('purple', $badge['color']);
        $this->assertEquals('#8b5cf6', $badge['hex']);
        $this->assertTrue($modo->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_uploader_role_has_sky_badge_and_can_access_admin(): void
    {
        $role = Role::firstOrCreate(['name' => 'uploader']);
        $uploader = User::factory()->create();
        $uploader->assignRole($role);

        $badge = $uploader->staff_badge;

        $this->assertNotNull($badge);
        $this->assertEquals('Uploader', $badge['name']);
        $this->assertEquals('sky', $badge['color']);
        $this->assertEquals('#0284c7', $badge['hex']);
        $this->assertTrue($uploader->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_regular_user_has_no_staff_badge(): void
    {
        $reader = User::factory()->create();

        $this->assertNull($reader->staff_badge);
        $this->assertFalse($reader->canAccessPanel(filament()->getPanel('admin')));
    }
}
