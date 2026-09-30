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

    public function test_staff_role_modification_persists_across_production_seeder(): void
    {
        // 1. Initialiser un utilisateur membre du staff avec un role modo
        $roleModo = Role::firstOrCreate(['name' => 'modo', 'guard_name' => 'web']);
        $roleAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $staffUser = User::where('email', 'modo@hiddenscan.com')->first();
        if (! $staffUser) {
            $staffUser = User::create([
                'name' => 'Modérateur Principal',
                'email' => 'modo@hiddenscan.com',
                'pass_code' => 'HS-MODO-SCAN-0003',
                'password' => bcrypt('secret-password-123'),
            ]);
        }
        $staffUser->syncRoles([$roleModo]);

        // 2. L'admin promeut ce membre en "Admin" et change son nom dans le panel "Equipe Staff"
        $staffUser->update(['name' => 'Promu Admin General']);
        $staffUser->syncRoles([$roleAdmin]);

        // 3. Executer le seeder de production (simulant un redeploiement de conteneur)
        $this->seed(\Database\Seeders\ProductionDataSeeder::class);

        // 4. Verifier que la modification reste : il est toujours Admin et son nom est preserve
        $staffUser->refresh();
        $this->assertEquals('Promu Admin General', $staffUser->name);
        $this->assertTrue($staffUser->hasRole('admin'));
        $this->assertFalse($staffUser->hasRole('modo'));
    }

    public function test_newly_created_staff_member_persists_across_production_seeder(): void
    {
        $roleUploader = Role::firstOrCreate(['name' => 'uploader', 'guard_name' => 'web']);
        $newStaff = User::create([
            'name' => 'Nouveau Recrue Uploader',
            'email' => 'nouvelle_recrue@hiddenscan.com',
            'pass_code' => 'HS-NEW-UPLO-9999',
            'password' => bcrypt('recrue_pass'),
        ]);
        $newStaff->syncRoles([$roleUploader]);

        $this->seed(\Database\Seeders\ProductionDataSeeder::class);

        $newStaff->refresh();
        $this->assertEquals('Nouveau Recrue Uploader', $newStaff->name);
        $this->assertTrue($newStaff->hasRole('uploader'));
    }
}
