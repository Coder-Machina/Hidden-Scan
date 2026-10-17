<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'profile_public')) {
                $table->boolean('profile_public')->default(true)->after('bio');
            }
        });

        // Initialiser un username unique pour les utilisateurs existants
        $users = \App\Models\User::whereNull('username')->get();
        foreach ($users as $user) {
            $base = Str::slug($user->name, '');
            if (empty($base)) {
                $base = 'user' . $user->id;
            }
            $username = strtolower($base);
            $count = 1;
            while (\App\Models\User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = strtolower($base) . $count;
                $count++;
            }
            $user->updateQuietly([
                'username' => $username,
                'profile_public' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
            if (Schema::hasColumn('users', 'profile_public')) {
                $table->dropColumn('profile_public');
            }
        });
    }
};
