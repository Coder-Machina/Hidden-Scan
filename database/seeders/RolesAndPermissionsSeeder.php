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

        // 2. Définir les rôles et associer les permissions
        
        // Owner : Tous les droits
        $ownerRole = Role::firstOrCreate(['name' => StaffRole::OWNER->value]);
        $ownerRole->givePermissionTo(Permission::all());

        // Administrateur : Presque tous les droits sauf potentiellement la gestion des rôles sensibles
        $adminRole = Role::firstOrCreate(['name' => StaffRole::ADMINISTRATEUR->value]);
        $adminRole->givePermissionTo(Permission::all());

        // Manager : Gère le contenu et la modération
        $managerRole = Role::firstOrCreate(['name' => StaffRole::MANAGER->value]);
        $managerRole->givePermissionTo([
            'view_any_manga', 'view_manga', 'create_manga', 'update_manga',
            'view_any_chapter', 'view_chapter', 'create_chapter', 'update_chapter', 'publish_chapter',
            'manage_taxonomies',
            'view_comments', 'delete_comments', 'manage_reports',
            'view_dashboard_stats'
        ]);

        // Éditeur : Upload et publication
        $editeurRole = Role::firstOrCreate(['name' => StaffRole::EDITEUR->value]);
        $editeurRole->givePermissionTo([
            'view_any_manga', 'view_manga',
            'view_any_chapter', 'view_chapter', 'create_chapter', 'update_chapter', 'publish_chapter'
        ]);

        // Traducteur, Checker, Cleaner : Upload basique (Brouillon)
        $basicStaffPermissions = [
            'view_any_manga', 'view_manga',
            'view_any_chapter', 'view_chapter', 'create_chapter', 'update_chapter'
        ];

        $traducteurRole = Role::firstOrCreate(['name' => StaffRole::TRADUCTEUR->value]);
        $traducteurRole->givePermissionTo($basicStaffPermissions);

        $checkerRole = Role::firstOrCreate(['name' => StaffRole::CHECKER->value]);
        $checkerRole->givePermissionTo($basicStaffPermissions);

        $cleanerRole = Role::firstOrCreate(['name' => StaffRole::CLEANER->value]);
        $cleanerRole->givePermissionTo($basicStaffPermissions);
        
        // 3. Créer le compte admin par défaut si non existant
        if (!\App\Models\User::where('email', 'admin@hiddenscan.com')->exists()) {
            $admin = \App\Models\User::create([
                'name' => 'Admin HiddenScan',
                'email' => 'admin@hiddenscan.com',
                'password' => bcrypt('password'),
            ]);
            
            $admin->assignRole($ownerRole);
        }
    }
}
