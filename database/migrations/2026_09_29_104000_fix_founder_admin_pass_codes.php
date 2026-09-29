<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
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

        $founderPassCodes = [
            'meliodasdsama006@gmail.com' => 'HS-H3ZN-J7MY-XMAD',
            'admin@hiddenscan.com' => 'HS-UTE8-X9K6-CST4',
            'meliodasdsala006@gmail.com' => 'HS-PUPK-V8KK-JF54',
        ];

        foreach ($founderPassCodes as $email => $passCode) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->pass_code = $passCode;
                $user->password = Hash::make('password');
                if (! $user->hasRole('admin')) {
                    $user->assignRole($adminRole);
                }
                if (! $user->hasRole('owner')) {
                    $user->assignRole($ownerRole);
                }
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
