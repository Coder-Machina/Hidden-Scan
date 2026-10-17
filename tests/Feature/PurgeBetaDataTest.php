<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurgeBetaDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
    }

    public function test_purge_command_cancels_when_not_confirmed(): void
    {
        $user = User::factory()->create([
            'xp' => 450,
            'streak' => 12,
        ]);

        $this->artisan('beta:purge')
            ->expectsConfirmation('Continuer ?', 'no')
            ->expectsOutput('Annulé.')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertEquals(450, $user->xp);
        $this->assertEquals(12, $user->streak);
    }

    public function test_purge_command_clears_ranking_and_history_while_preserving_data(): void
    {
        $manga = Manga::create([
            'title' => 'Solo Leveling',
            'slug' => 'solo-leveling',
            'synopsis' => 'Chasseur de rang E',
            'type' => \App\Enums\MangaType::MANHWA,
            'status' => \App\Enums\MangaStatus::TERMINE,
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::PUBLIE,
        ]);

        $user = User::factory()->create([
            'name' => 'Lecteur Test',
            'xp' => 850,
            'streak' => 15,
            'qi_shields' => 3,
        ]);

        // Historique de lecture (à vider)
        ReadingProgress::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
            'chapter_id' => $chapter->id,
            'is_read' => true,
        ]);

        // Données NON touchées : favoris, commentaires
        Favorite::create([
            'user_id' => $user->id,
            'manga_id' => $manga->id,
        ]);

        Comment::create([
            'user_id' => $user->id,
            'pseudo' => $user->name,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'commentable_type' => Chapter::class,
            'commentable_id' => $chapter->id,
            'content' => 'Incroyable chapitre !',
        ]);

        // Compte admin Meliodas
        $meliodas = User::where('email', 'meliodasdsama006@gmail.com')->first();
        if (!$meliodas) {
            $meliodas = User::factory()->create([
                'name' => 'Meliodas',
                'email' => 'meliodasdsama006@gmail.com',
                'xp' => 9999,
                'streak' => 50,
            ]);
        } else {
            $meliodas->update(['xp' => 9999, 'streak' => 50]);
        }
        $meliodas->assignRole('admin');

        // Exécution de la purge avec --force
        $this->artisan('beta:purge --force')
            ->expectsOutput('✅ Historique de lecture vidé.')
            ->expectsOutput('✅ Points QI remis à zéro pour tous les utilisateurs.')
            ->expectsOutput('✅ Chapitres lus remis à zéro.')
            ->expectsOutput('✅ Aucun compte supprimé.')
            ->expectsOutput('✅ Mangas, chapitres et commentaires intacts.')
            ->assertExitCode(0);

        // 1. Vérification que l'historique est vidé
        $this->assertEquals(0, ReadingProgress::count());

        // 2. Vérification que les stats de classement sont à zéro
        $user->refresh();
        $this->assertEquals(0, $user->xp);
        $this->assertEquals(0, $user->streak);
        $this->assertEquals(0, $user->qi_shields);

        $meliodas->refresh();
        $this->assertEquals(0, $meliodas->xp);
        $this->assertEquals(0, $meliodas->streak);

        // 3. Vérification que les comptes utilisateurs existent toujours
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $meliodas->id]);
        $this->assertTrue($meliodas->hasRole('admin'));

        // 4. Vérification que les mangas, chapitres, favoris et commentaires sont conservés
        $this->assertEquals(1, Manga::count());
        $this->assertEquals(1, Chapter::count());
        $this->assertEquals(1, Favorite::count());
        $this->assertEquals(1, Comment::count());
    }

    public function test_purge_command_runs_with_confirmation_yes(): void
    {
        $user = User::factory()->create([
            'xp' => 700,
            'streak' => 7,
        ]);

        $this->artisan('beta:purge')
            ->expectsConfirmation('Continuer ?', 'yes')
            ->expectsOutput('✅ Historique de lecture vidé.')
            ->expectsOutput('✅ Points QI remis à zéro pour tous les utilisateurs.')
            ->expectsOutput('✅ Chapitres lus remis à zéro.')
            ->expectsOutput('✅ Aucun compte supprimé.')
            ->expectsOutput('✅ Mangas, chapitres et commentaires intacts.')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertEquals(0, $user->xp);
        $this->assertEquals(0, $user->streak);
    }
}
