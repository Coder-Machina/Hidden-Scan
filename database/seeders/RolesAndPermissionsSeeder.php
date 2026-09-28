<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Définir les permissions
        $permissions = [
            // Mangas
            'view_any_manga',
            'view_manga',
            'create_manga',
            'update_manga',
            'delete_manga',
            'restore_manga',
            
            // Chapitres
            'view_any_chapter',
            'view_chapter',
            'create_chapter',
            'update_chapter',
            'delete_chapter',
            'publish_chapter', // Spécifique au workflow
            
            // Taxonomies (Auteurs, Artistes, Genres, Tags)
            'manage_taxonomies',
            
            // Commentaires & Modération
            'view_comments',
            'delete_comments',
            'manage_reports',
            
            // Staff & Settings
            'manage_users',
            'view_dashboard_stats',
            'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Définir les 3 rôles Staff et associer les permissions
        
        // Admin (Rouge) : Tous les droits
        $adminRole = Role::firstOrCreate(['name' => StaffRole::ADMIN->value]);
        $adminRole->givePermissionTo(Permission::all());

        // Modo (Violet) : Modération des commentaires & signalements
        $modoRole = Role::firstOrCreate(['name' => StaffRole::MODO->value]);
        $modoRole->givePermissionTo([
            'view_any_manga', 'view_manga',
            'view_comments', 'delete_comments', 'manage_reports',
            'view_dashboard_stats',
        ]);

        // Uploader (Bleu ciel) : Gestion & upload des mangas et chapitres
        $uploaderRole = Role::firstOrCreate(['name' => StaffRole::UPLOADER->value]);
        $uploaderRole->givePermissionTo([
            'view_any_manga', 'view_manga', 'create_manga', 'update_manga',
            'view_any_chapter', 'view_chapter', 'create_chapter', 'update_chapter', 'publish_chapter',
            'manage_taxonomies',
            'view_dashboard_stats',
        ]);

        // Migrer les anciens rôles vers les 3 nouveaux rôles pour la base de données
        foreach (\App\Models\User::all() as $user) {
            if ($user->hasAnyRole(['owner', 'Owner', 'Administrateur', 'admin'])) {
                $user->assignRole($adminRole);
            }
            if ($user->hasAnyRole(['modo', 'Modérateur', 'moderateur'])) {
                $user->assignRole($modoRole);
            }
            if ($user->hasAnyRole(['uploader'])) {
                $user->assignRole($uploaderRole);
            }
        }

        // 3. Créer le compte admin par défaut si non existant
        if (!\App\Models\User::where('email', 'admin@hiddenscan.com')->exists()) {
            $admin = \App\Models\User::create([
                'name' => 'Admin HiddenScan',
                'email' => 'admin@hiddenscan.com',
                'password' => bcrypt('password'),
            ]);
            
            $admin->assignRole($adminRole);
        }
    }
}
