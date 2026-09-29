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
}
