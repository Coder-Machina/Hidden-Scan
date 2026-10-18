<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserBanActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_mount_and_execute_ban_action_on_reader(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create([
            'email' => 'admin_test@hiddenscan.com',
        ]);
        $admin->assignRole($adminRole);

        $reader = User::factory()->create([
            'name' => 'Lecteur Cible',
            'email' => 'reader@example.com',
            'is_banned' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->set('activeTab', 'all')
            ->mountTableAction('ban', $reader)
            ->setTableActionData([
                'ban_type' => 'full_account',
                'duration' => '1_hour',
                'ban_ip' => false,
                'reason' => 'Comportement toxique',
                'hide_all_comments' => false,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $reader->refresh();
        $this->assertTrue((bool) $reader->is_banned);
        $this->assertEquals('Comportement toxique', $reader->ban_reason);
        $this->assertNotNull($reader->banned_until);
    }
}
