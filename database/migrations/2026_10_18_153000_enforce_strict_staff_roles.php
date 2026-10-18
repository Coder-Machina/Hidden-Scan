<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Assurer que les rôles stricts ADMIN, MODO, UPLOADER existent
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $modoRole = Role::firstOrCreate(['name' => 'modo', 'guard_name' => 'web']);
        $uploaderRole = Role::firstOrCreate(['name' => 'uploader', 'guard_name' => 'web']);

        // 2. Transférer tous les utilisateurs ayant le rôle 'owner' ou 'Administrateur' vers 'admin'
        $obsoleteAdminRoles = Role::whereIn('name', ['owner', 'Owner', 'Administrateur', 'administrateur'])->pluck('id');
        if ($obsoleteAdminRoles->isNotEmpty()) {
            $userIds = DB::table('model_has_roles')
                ->whereIn('role_id', $obsoleteAdminRoles)
                ->where('model_type', User::class)
                ->pluck('model_id');

            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if ($user && !$user->hasRole('admin')) {
                    $user->assignRole('admin');
                }
            }
        }

        // 3. Supprimer tous les rôles non autorisés (tout sauf admin, modo, uploader)
        $allowedRoles = ['admin', 'Admin', 'modo', 'Modo', 'uploader', 'Uploader'];
        $rolesToDelete = Role::whereNotIn('name', $allowedRoles)->get();

        foreach ($rolesToDelete as $role) {
            DB::table('model_has_roles')->where('role_id', $role->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            $role->delete();
        }
    }

    public function down(): void
    {
        // Pas de rollback nécessaire
    }
};
