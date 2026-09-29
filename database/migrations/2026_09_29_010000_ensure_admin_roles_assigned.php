<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. S'assurer que les rôles existent
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'modo', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'uploader', 'guard_name' => 'web']);

        // 2. Donner toutes les permissions au rôle admin
        $adminRole->givePermissionTo(Permission::all());

        // 3. Assigner le rôle admin aux comptes fondateurs
        $founderEmails = [
            'meliodasdsama006@gmail.com',
            'meliodasdsala006@gmail.com',
            'admin@hiddenscan.com',
            'admin@hidden-scan.com',
        ];

        foreach ($founderEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole($adminRole);
            }
        }

        // Réinitialiser le cache des permissions Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
