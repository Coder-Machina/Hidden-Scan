<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Chapters\ChapterResource;
use App\Filament\Resources\CommentReports\CommentReportResource;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAndProfileUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_dropdown_does_not_reveal_pass_code(): void
    {
        $user = User::factory()->create([
            'name' => 'JeanLecteur',
            'pass_code' => 'HS-9999-8888-7777',
            'email' => 'pass_999988887777@anon.hiddenscan.local',
        ]);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);

        // The dropdown header must not print the pass_code
        $response->assertDontSee('HS-9999-8888-7777');
        $response->assertSee('Compte Anonyme');
    }

    public function test_profile_overview_does_not_reveal_pass_code(): void
    {
        $user = User::factory()->create([
            'name' => 'JeanLecteur',
            'pass_code' => 'HS-1234-5678-9012',

        ]);

        $response = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'overview']));
        $response->assertStatus(200);

        // The overview / Aperçu panel card must NOT be present
        $response->assertDontSee('Votre Pass Secret Anonyme');
    }

    public function test_profile_settings_contains_spoiler_for_pass_code(): void
    {
        $user = User::factory()->create([
            'name' => 'JeanLecteur',
            'pass_code' => 'HS-SECRET-CODE-TEST',

        ]);

        $response = $this->actingAs($user)->get(route('profile.edit', ['tab' => 'settings']));
        $response->assertStatus(200);

        // The settings tab contains the spoiler container
        $response->assertSee('Mon Pass Secret (Connexion Anonyme)');
        $response->assertSee('Spoiler');
        $response->assertSee('Cliquez pour révéler le code');
        $response->assertSee('HS-SECRET-CODE-TEST');
    }

    public function test_audit_log_target_title_displays_sanction_target_user_name_and_reason(): void
    {
        $admin = User::factory()->create(['name' => 'SuperAdmin']);

        // Account ban
        $banLog = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'ban_account',
            'model_type' => User::class,
            'model_id' => 42,
            'new_values' => [
                'target_user' => 'UtilisateurIndiscipliné',
                'reason' => 'Spam répété',
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertEquals('Sanction', $banLog->model_name);
        $this->assertEquals('Sanction : Bannissement de UtilisateurIndiscipliné (Motif : Spam répété)', $banLog->target_title);

        // Comment ban
        $commentBanLog = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'ban_comments',
            'model_type' => Comment::class,
            'model_id' => 99,
            'new_values' => [
                'target_user' => 'TrollDuNet',
                'reason' => 'Propos injurieux',
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertEquals('Sanction', $commentBanLog->model_name);
        $this->assertEquals('Sanction : Ban commentaires de TrollDuNet (Motif : Propos injurieux)', $commentBanLog->target_title);

        // Unban
        $unbanLog = AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'unban',
            'model_type' => User::class,
            'model_id' => 42,
            'new_values' => [
                'target_user' => 'UtilisateurRepenti',
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertEquals('Sanction', $unbanLog->model_name);
        $this->assertEquals('Sanction : Débannissement de UtilisateurRepenti', $unbanLog->target_title);
    }

    public function test_chapter_resource_navigation_is_disabled(): void
    {
        $this->assertFalse(ChapterResource::shouldRegisterNavigation());
    }

    public function test_audit_log_resource_navigation_sort_is_at_the_bottom(): void
    {
        $this->assertEquals(999, AuditLogResource::getNavigationSort());
    }

    public function test_comment_report_resource_cannot_be_created_manually(): void
    {
        $this->assertFalse(CommentReportResource::canCreate());
    }

    public function test_application_locale_is_french(): void
    {
        $this->assertEquals('fr', config('app.locale'));
        $this->assertEquals('fr', config('app.fallback_locale'));
    }

    public function test_home_page_includes_mangas_even_without_published_chapters(): void
    {
        // 1 Manga with 0 chapters
        $manga = Manga::create([
            'title' => 'One Piece',
            'type' => \App\Enums\MangaType::MANGA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
            'slug' => 'one-piece-test',
        ]);

        // 1 Manhwa with 0 chapters
        $manhwa = Manga::create([
            'title' => 'Solo Leveling',
            'type' => \App\Enums\MangaType::MANHWA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
            'slug' => 'solo-leveling-test',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Both are present in the response
        $response->assertSee('One Piece');
        $response->assertSee('Solo Leveling');

        // Both types are counted in the filter bar
        $latestUpdates = $response->viewData('latest_updates');
        $this->assertTrue($latestUpdates->contains('slug', 'one-piece-test'));
        $this->assertTrue($latestUpdates->contains('slug', 'solo-leveling-test'));
        $this->assertEquals(1, $latestUpdates->where('type.value', 'manga')->count());
        $this->assertEquals(1, $latestUpdates->where('type.value', 'manhwa')->count());
    }

    public function test_manga_show_places_comments_section_at_the_end(): void
    {
        $manga = Manga::create([
            'title' => 'One Piece',
            'type' => \App\Enums\MangaType::MANGA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
            'slug' => 'one-piece-comments-pos-test',
            'synopsis' => 'Un équipage de pirates recherche le trésor ultime.',
        ]);

        $response = $this->get(route('manga.show', $manga->slug));
        $response->assertStatus(200);

        $content = $response->getContent();

        $synopsisPos = strpos($content, 'Un équipage de pirates recherche le trésor ultime.');
        $infoPos = strpos($content, '<h2 class="section-title-info">');
        $chaptersPos = strpos($content, '<div class="chapters-container-card">');
        $commentsPos = strpos($content, 'id="manga-comments-section"');

        $this->assertNotFalse($synopsisPos);
        $this->assertNotFalse($infoPos);
        $this->assertNotFalse($chaptersPos);
        $this->assertNotFalse($commentsPos);

        // Ordre logique garanti : Synopsis -> Informations & Genres -> Chapitres -> Commentaires à la fin
        $this->assertLessThan($infoPos, $synopsisPos);
        $this->assertLessThan($chaptersPos, $infoPos);
        $this->assertLessThan($commentsPos, $chaptersPos);
    }

    public function test_user_can_update_profile_with_data_uri_avatar_and_banner(): void
    {
        $user = User::factory()->create([
            'pass_code' => 'HS-TEST-AVAT-0001',
        ]);

        $dataAvatar = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $dataBanner = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'CustomMeliodas',
            'avatar_data' => $dataAvatar,
            'banner_data' => $dataBanner,
            'bio' => 'Nouvelle bio test',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertEquals('CustomMeliodas', $user->name);
        $this->assertEquals($dataAvatar, $user->avatar);
        $this->assertEquals($dataBanner, $user->banner);
        $this->assertEquals($dataAvatar, $user->avatar_url);
        $this->assertEquals($dataBanner, $user->banner_url);
        $this->assertEquals('Nouvelle bio test', $user->bio);
    }

    public function test_profile_sync_endpoint_restores_missing_avatar_and_banner(): void
    {
        $user = User::factory()->create([
            'name' => 'Lecteur-1234',
            'avatar' => null,
            'banner' => null,
            'pass_code' => 'HS-SYNC-TEST-0002',
        ]);

        $dataAvatar = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $dataBanner = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

        $response = $this->actingAs($user)->postJson(route('api.profile.sync'), [
            'name' => 'MeliodasLeVrai',
            'avatar' => $dataAvatar,
            'banner' => $dataBanner,
            'bio' => 'Bio synchronisée',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('user.name', 'MeliodasLeVrai');
        $response->assertJsonPath('user.avatar', $dataAvatar);

        $user->refresh();
        $this->assertEquals('MeliodasLeVrai', $user->name);
        $this->assertEquals($dataAvatar, $user->avatar);
        $this->assertEquals($dataBanner, $user->banner);
        $this->assertEquals('Bio synchronisée', $user->bio);
    }

    public function test_valid_pass_code_auto_restores_user_on_login(): void
    {
        $passCode = 'HS-RECO-7777-8888';
        $this->assertDatabaseMissing('users', ['pass_code' => $passCode]);

        $response = $this->post(route('login'), [
            'pass_code' => $passCode,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['pass_code' => $passCode]);
    }

    public function test_production_data_seeder_does_not_overwrite_manga_modifications(): void
    {
        // 1. Initialiser une oeuvre comme si elle avait ete modifiee par un admin via le panel
        $manga = Manga::create([
            'title' => 'Titre Modifie Par Admin',
            'slug' => 'genius-grandson-of-the-loan-shark-king',
            'synopsis' => 'Ce synopsis a ete personnalise depuis le panel admin.',
            'type' => 'manga',
            'status' => 'termine',
            'release_year' => '2025',
        ]);

        // 2. Executer le seeder de production (simulant un redemarrage / redeploiement conteneur)
        $this->seed(\Database\Seeders\ProductionDataSeeder::class);

        // 3. Verifier que les modifications de l'admin restent et n'ont PAS ete ecrasees
        $manga->refresh();
        $this->assertEquals('Titre Modifie Par Admin', $manga->title);
        $this->assertEquals('Ce synopsis a ete personnalise depuis le panel admin.', $manga->synopsis);
        $this->assertEquals('manga', $manga->type?->value ?? (string) $manga->type);
        $this->assertEquals('termine', $manga->status?->value ?? (string) $manga->status);
        $this->assertEquals('2025', $manga->release_year);
    }

    public function test_production_data_seeder_does_not_reset_existing_user_password_or_name(): void
    {
        // 1. Un utilisateur admin existe (issu des migrations) et personnalise son nom et mot de passe
        $customPassword = \Illuminate\Support\Facades\Hash::make('SuperSecretAdminPassword123!');
        $user = User::where('email', 'meliodasdsama006@gmail.com')->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Meliodas Initial',
                'email' => 'meliodasdsama006@gmail.com',
                'pass_code' => 'HS-MELI-ODAS-0001',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
            ]);
        }

        $user->update([
            'name' => 'Meliodas Custom Name',
            'password' => $customPassword,
        ]);

        // 2. Executer le seeder (simulant un redemarrage / redeploiement de conteneur)
        $this->seed(\Database\Seeders\ProductionDataSeeder::class);

        // 3. Verifier que son nom et son mot de passe n'ont pas ete reinitialises
        $user->refresh();
        $this->assertEquals('Meliodas Custom Name', $user->name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('SuperSecretAdminPassword123!', $user->password));
    }
}

