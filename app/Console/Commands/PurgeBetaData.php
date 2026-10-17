<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeBetaData extends Command
{
    protected $signature   = 'beta:purge {--force : Bypass confirmation}';
    protected $description = 'Remet les données de classement à zéro avant le lancement beta';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->warn('⚠️  Cette commande remet le classement à zéro.');
            $this->warn('Aucun compte utilisateur ne sera supprimé.');
            $this->warn('Mangas, chapitres et commentaires sont conservés.');
            $this->line('');
            $this->info('Compte admin conservé : Meliodas');
            $this->line('');

            if (!$this->confirm('Continuer ?')) {
                $this->info('Annulé.');
                return Command::SUCCESS;
            }
        }

        DB::beginTransaction();

        try {
            // 1. Vider l'historique de lecture de tout le monde
            //    Dans ce projet, la table physique d'historique de lecture est reading_progress
            if (Schema::hasTable('reading_progress')) {
                DB::table('reading_progress')->delete();
            }

            if (Schema::hasTable('reading_history')) {
                DB::table('reading_history')->delete();
            }

            // 2. Remettre toutes les stats de classement à zéro
            //    Adaptation dynamique selon les colonnes réellement présentes sur users
            $userStatsUpdates = [];

            if (Schema::hasColumn('users', 'xp')) {
                $userStatsUpdates['xp'] = 0;
            }

            if (Schema::hasColumn('users', 'streak')) {
                $userStatsUpdates['streak'] = 0;
            }

            if (Schema::hasColumn('users', 'qi_shields')) {
                $userStatsUpdates['qi_shields'] = 0;
            }

            if (Schema::hasColumn('users', 'last_read_at')) {
                $userStatsUpdates['last_read_at'] = null;
            }

            if (Schema::hasColumn('users', 'points_qi')) {
                $userStatsUpdates['points_qi'] = 0;
            }

            if (Schema::hasColumn('users', 'chapitres_lus')) {
                $userStatsUpdates['chapitres_lus'] = 0;
            }

            if (!empty($userStatsUpdates)) {
                User::query()->update($userStatsUpdates);
            }

            // 3. Vérifier que Meliodas existe toujours et conserve son rôle admin
            $meliodas = User::where('name', 'Meliodas')
                ->orWhere('email', 'meliodasdsama006@gmail.com')
                ->first();

            if ($meliodas && method_exists($meliodas, 'hasRole')) {
                if (!$meliodas->hasRole('admin') && !$meliodas->hasRole('Admin') && !$meliodas->hasRole('owner')) {
                    $meliodas->assignRole('admin');
                }
            }

            DB::commit();

            $this->info('✅ Historique de lecture vidé.');
            $this->info('✅ Points QI remis à zéro pour tous les utilisateurs.');
            $this->info('✅ Chapitres lus remis à zéro.');
            $this->info('✅ Aucun compte supprimé.');
            $this->info('✅ Mangas, chapitres et commentaires intacts.');
            if ($meliodas) {
                $this->info('✅ Compte Meliodas vérifié et protégé (rôle admin garanti).');
            }
            $this->line('');
            $this->info('Le site est prêt pour la beta.');

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Erreur : ' . $e->getMessage());
            $this->error('Aucune donnée modifiée — rollback effectué.');
            return Command::FAILURE;
        }
    }
}
