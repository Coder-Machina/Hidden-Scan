<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);

        $founders = [
            'meliodasdsama006@gmail.com',
            'meliodasdsala006@gmail.com',
            'admin@hiddenscan.com',
            'admin@hidden-scan.com',
        ];

        foreach ($founders as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                if (! $user->hasRole('admin')) {
                    $user->assignRole($adminRole);
                }
                if (! $user->hasRole('owner')) {
                    $user->assignRole($ownerRole);
                }
                if (! $user->pass_code) {
                    $user->pass_code = User::generateUniquePassCode();
                }
                $user->password = \Illuminate\Support\Facades\Hash::make('password');
                $user->save();
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
