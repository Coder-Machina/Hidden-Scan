<?php

namespace Tests\Feature;

use App\Filament\Resources\Comments\Tables\CommentsTable;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\Comment;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Livewire\Livewire;
use PharData;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class ArchiveUploadAndModerationBanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'modo']);
        Role::firstOrCreate(['name' => 'uploader']);
    }

    public function test_process_chapter_zip_extracts_cbz_archive(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $manga = Manga::create([
            'title' => 'Test Manga CBZ',
            'slug' => 'test-manga-cbz',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapitre 1 CBZ',
            'slug' => 'chapitre-1',
            'status' => 'brouillon',
        ]);

        $manager = new ImageManager(new Driver());
        $sampleImage = $manager->createImage(800, 1200);
        $encodedJpg = $sampleImage->encode(new JpegEncoder(quality: 85));

        $tempCbzPath = tempnam(sys_get_temp_dir(), 'test_cbz_') . '.cbz';
        $zip = new ZipArchive();
        $zip->open($tempCbzPath, ZipArchive::CREATE);
        $zip->addFromString('01.jpg', (string) $encodedJpg);
        $zip->close();

        $cbzRelativePath = 'uploads/zips/chapter_1.cbz';
        Storage::disk('local')->put($cbzRelativePath, file_get_contents($tempCbzPath));
        @unlink($tempCbzPath);

        $job = new ProcessChapterZip(
            chapter: $chapter,
            zipPath: $cbzRelativePath,
            publishWhenReady: false,
            maxPageWidth: 1400
        );
        $job->handle();

        $page = ChapterPage::where('chapter_id', $chapter->id)->first();
        $this->assertNotNull($page, 'Une page doit avoir été créée depuis l\'archive CBZ');
        $this->assertTrue(Storage::disk('public')->exists($page->image_path));
    }

    public function test_process_chapter_zip_extracts_tar_archive(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $manga = Manga::create([
            'title' => 'Test Manga TAR',
            'slug' => 'test-manga-tar',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 2,
            'title' => 'Chapitre 2 TAR',
            'slug' => 'chapitre-2',
            'status' => 'brouillon',
        ]);

        $manager = new ImageManager(new Driver());
        $sampleImage = $manager->createImage(800, 1200);
        $encodedJpg = $sampleImage->encode(new JpegEncoder(quality: 85));

        $tempTarPath = tempnam(sys_get_temp_dir(), 'test_tar_') . '.tar';
        @unlink($tempTarPath); // PharData expects tar not to be empty file if creating
        $tar = new PharData($tempTarPath);
        $tar->addFromString('01.jpg', (string) $encodedJpg);

        $tarRelativePath = 'uploads/zips/chapter_2.tar';
        Storage::disk('local')->put($tarRelativePath, file_get_contents($tempTarPath));
        @unlink($tempTarPath);

        $job = new ProcessChapterZip(
            chapter: $chapter,
            zipPath: $tarRelativePath,
            publishWhenReady: false,
            maxPageWidth: 1400
        );
        $job->handle();

        $page = ChapterPage::where('chapter_id', $chapter->id)->first();
        $this->assertNotNull($page, 'Une page doit avoir été créée depuis l\'archive TAR');
        $this->assertTrue(Storage::disk('public')->exists($page->image_path));
    }

    public function test_moderator_can_view_users_resource(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $this->actingAs($modo);
        $this->assertTrue(UserResource::canViewAny());
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canDelete(new User()));
    }

    public function test_moderator_can_ban_user_temporarily(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $reader = User::factory()->create([
            'is_banned' => false,
        ]);

        Livewire::actingAs($modo)
            ->test(ListUsers::class)
            ->set('activeTab', 'all')
            ->mountTableAction('ban', $reader)
            ->setTableActionData([
                'ban_type' => 'full_account',
                'duration' => '24_hours',
                'ban_ip' => false,
                'reason' => 'Insultes répétées',
                'hide_all_comments' => false,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $reader->refresh();
        $this->assertTrue((bool) $reader->is_banned);
        $this->assertEquals('Insultes répétées', $reader->ban_reason);
        $this->assertNotNull($reader->banned_until);
        $this->assertTrue($reader->banned_until->isFuture());
        $this->assertTrue($reader->isBanned());
    }

    public function test_user_temporary_ban_automatically_expires(): void
    {
        $reader = User::factory()->create([
            'is_banned' => true,
            'banned_at' => now()->subDays(2),
            'banned_until' => now()->subHour(), // Expired 1 hour ago
            'ban_reason' => 'Test expiré',
        ]);

        // When isBanned() is called, it checks banned_until and auto-rehabilitates
        $this->assertFalse($reader->isBanned());
        $reader->refresh();
        $this->assertFalse((bool) $reader->is_banned);
        $this->assertNull($reader->banned_until);
    }

    public function test_moderator_can_edit_ban_to_remove_permanent_ban_and_set_temporary(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $bannedReader = User::factory()->create([
            'is_banned' => true,
            'banned_at' => now()->subDays(5),
            'banned_until' => null, // Permanent
            'ban_reason' => 'Définitif',
        ]);

        Livewire::actingAs($modo)
            ->test(ListUsers::class)
            ->set('activeTab', 'all')
            ->mountTableAction('edit_ban', $bannedReader)
            ->setTableActionData([
                'action_choice' => 'modify',
                'ban_type' => 'full_account',
                'duration' => '3_days', // Changing permanent ban to 3 days!
                'reason' => 'Sanction réduite à 3 jours après excuses',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $bannedReader->refresh();
        $this->assertTrue((bool) $bannedReader->is_banned);
        $this->assertNotNull($bannedReader->banned_until, 'Le ban permanent a été converti en temporaire');
        $this->assertTrue($bannedReader->banned_until->isFuture());
    }

    public function test_moderator_can_lift_ban_completely_via_edit_ban(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $bannedReader = User::factory()->create([
            'is_banned' => true,
            'banned_at' => now()->subDay(),
            'banned_until' => null,
            'ban_reason' => 'Définitif',
        ]);

        Livewire::actingAs($modo)
            ->test(ListUsers::class)
            ->set('activeTab', 'all')
            ->mountTableAction('edit_ban', $bannedReader)
            ->setTableActionData([
                'action_choice' => 'lift',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $bannedReader->refresh();
        $this->assertFalse((bool) $bannedReader->is_banned);
        $this->assertNull($bannedReader->banned_until);
        $this->assertNull($bannedReader->ban_reason);
    }

    public function test_moderator_cannot_ban_administrator(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($modo)
            ->test(ListUsers::class)
            ->set('activeTab', 'all')
            ->assertTableActionHidden('ban', $admin);
    }

    public function test_moderator_can_ban_author_temporarily_via_comments_table(): void
    {
        $modo = User::factory()->create();
        $modo->assignRole('modo');

        $author = User::factory()->create([
            'is_banned' => false,
            'is_comment_banned' => false,
        ]);

        $manga = Manga::create([
            'title' => 'Manga Comm',
            'slug' => 'manga-comm',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Ch 1',
            'slug' => 'ch-1',
            'status' => 'publie',
        ]);

        $comment = Comment::create([
            'commentable_type' => 'App\Models\Manga',
            'commentable_id' => $manga->id,
            'user_id' => $author->id,
            'pseudo' => $author->name,
            'ip_hash' => sha1('1.2.3.4'),
            'ip_address' => '1.2.3.4',
            'content' => 'Commentaire à modérer',
        ]);

        Livewire::actingAs($modo)
            ->test(\App\Filament\Resources\Comments\Pages\ListComments::class)
            ->mountTableAction('ban', $comment)
            ->setTableActionData([
                'ban_type' => 'comment_only',
                'duration' => '7_days',
                'reason' => 'Spam intempestif',
                'hide_all_comments' => true,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $author->refresh();
        $this->assertTrue((bool) $author->is_comment_banned);
        $this->assertNotNull($author->comment_banned_until);
        $this->assertTrue($author->comment_banned_until->isFuture());
        $this->assertEquals('Spam intempestif', $author->comment_ban_reason);

        // Modify ban to lift permanent or change duration
        Livewire::actingAs($modo)
            ->test(\App\Filament\Resources\Comments\Pages\ListComments::class)
            ->mountTableAction('edit_ban', $comment)
            ->setTableActionData([
                'action_choice' => 'lift',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $author->refresh();
        $this->assertFalse((bool) $author->is_comment_banned);
        $this->assertNull($author->comment_banned_until);
    }
}

