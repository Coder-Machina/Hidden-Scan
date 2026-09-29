<?php

namespace Tests\Feature;

use App\Filament\Resources\Artists\ArtistResource;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Authors\AuthorResource;
use App\Filament\Resources\Chapters\ChapterResource;
use App\Filament\Resources\CommentReports\CommentReportResource;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Genres\GenreResource;
use App\Filament\Resources\Mangas\MangaResource;
use App\Filament\Resources\Tags\TagResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionsIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'modo', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'uploader', 'guard_name' => 'web']);
    }

    public function test_uploader_only_accesses_manga_and_chapters_and_metadata(): void
    {
        $uploader = User::factory()->create();
        $uploader->assignRole('uploader');

        $this->actingAs($uploader);

        $this->assertTrue(MangaResource::canViewAny());
        $this->assertTrue(ChapterResource::canViewAny());
        $this->assertTrue(AuthorResource::canViewAny());
        $this->assertTrue(ArtistResource::canViewAny());
        $this->assertTrue(GenreResource::canViewAny());
        $this->assertTrue(TagResource::canViewAny());

        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(CommentResource::canViewAny());
        $this->assertFalse(CommentReportResource::canViewAny());
        $this->assertFalse(AuditLogResource::canViewAny());
    }

    public function test_modo_only_accesses_moderation_and_comments(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $this->actingAs($modo);

        $this->assertTrue(CommentResource::canViewAny());
        $this->assertTrue(CommentReportResource::canViewAny());

        $this->assertFalse(MangaResource::canViewAny());
        $this->assertFalse(ChapterResource::canViewAny());
        $this->assertFalse(AuthorResource::canViewAny());
        $this->assertFalse(ArtistResource::canViewAny());
        $this->assertFalse(GenreResource::canViewAny());
        $this->assertFalse(TagResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(AuditLogResource::canViewAny());
    }

    public function test_admin_accesses_all_resources(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $this->assertTrue(MangaResource::canViewAny());
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(CommentResource::canViewAny());
        $this->assertTrue(CommentReportResource::canViewAny());
        $this->assertTrue(AuditLogResource::canViewAny());
    }

    public function test_owner_accesses_all_resources(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner);

        $this->assertTrue(MangaResource::canViewAny());
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(CommentResource::canViewAny());
        $this->assertTrue(CommentReportResource::canViewAny());
        $this->assertTrue(AuditLogResource::canViewAny());
    }
}
