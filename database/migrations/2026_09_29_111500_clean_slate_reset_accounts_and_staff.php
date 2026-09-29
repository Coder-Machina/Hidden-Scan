<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Définir et s'assurer que les rôles existent
        $roles = [
            'owner' => Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']),
            'admin' => Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']),
            'modo' => Role::firstOrCreate(['name' => 'modo', 'guard_name' => 'web']),
            'uploader' => Role::firstOrCreate(['name' => 'uploader', 'guard_name' => 'web']),
        ];

        // 2. Supprimer tous les anciens comptes et réinitialiser proprement
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('audit_logs')->truncate();
        DB::table('favorites')->truncate();
        DB::table('reading_progress')->truncate();
        DB::table('users')->truncate();

        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            DB::table('sessions')->truncate();
        }

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // 3. Créer les comptes fondateurs et staff initiaux
        $staffAccounts = [
            [
                'name' => 'Meliodas',
                'email' => 'meliodasdsama006@gmail.com',
                'pass_code' => 'HS-MELI-ODAS-0001',
                'password' => Hash::make('password'),
                'roles' => ['owner', 'admin'],
            ],
            [
                'name' => 'Co-Admin',
                'email' => 'admin@hiddenscan.com',
                'pass_code' => 'HS-ADMI-SCAN-0002',
                'password' => Hash::make('password'),
                'roles' => ['owner', 'admin'],
            ],
            [
                'name' => 'Modérateur Principal',
                'email' => 'modo@hiddenscan.com',
                'pass_code' => 'HS-MODO-SCAN-0003',
                'password' => Hash::make('password'),
                'roles' => ['modo'],
            ],
            [
                'name' => 'Uploader Principal',
                'email' => 'uploader@hiddenscan.com',
                'pass_code' => 'HS-UPLO-ADER-0004',
                'password' => Hash::make('password'),
                'roles' => ['uploader'],
            ],
        ];

        foreach ($staffAccounts as $acc) {
            $user = User::create([
                'name' => $acc['name'],
                'email' => $acc['email'],
                'pass_code' => $acc['pass_code'],
                'password' => $acc['password'],
                'email_verified_at' => now(),
            ]);

            foreach ($acc['roles'] as $roleName) {
                $user->assignRole($roles[$roleName]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas d'inversion nécessaire
    }
};
