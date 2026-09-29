<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_to_filament_with_pass_code(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $user = User::factory()->create([
            'email' => 'admin_test@hiddenscan.com',
            'pass_code' => 'HS-TEST-ADMI-1234',
        ]);
        $user->assignRole($role);

        \Livewire\Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'email' => 'HS-TEST-ADMI-1234',
                'password' => '',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_login_to_filament_with_email_and_password(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $user = User::factory()->create([
            'email' => 'staff_member@hiddenscan.com',
            'password' => bcrypt('secret1234'),
        ]);
        $user->assignRole($role);

        \Livewire\Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'email' => 'staff_member@hiddenscan.com',
                'password' => 'secret1234',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_regular_reader_cannot_access_filament_with_reader_pass(): void
    {
        $user = User::factory()->create([
            'email' => 'simple_reader@hiddenscan.com',
            'pass_code' => 'HS-READ-ONLY-0000',
        ]);

        \Livewire\Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'email' => 'HS-READ-ONLY-0000',
                'password' => '',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }
}
