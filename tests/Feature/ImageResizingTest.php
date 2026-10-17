<?php

namespace Tests\Feature;

use App\Filament\Resources\Mangas\MangaResource;
use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class ImageResizingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_manga_edit_page_with_cover_and_banner_editor(): void
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole($role);

        $manga = Manga::create([
            'title' => 'Test Manga For Editor',
            'slug' => 'test-manga-for-editor',
            'synopsis' => 'Testing image editor on manga form',
            'type' => \App\Enums\MangaType::MANGA,
            'status' => \App\Enums\MangaStatus::EN_COURS,
        ]);

        $response = $this->actingAs($admin)->get(MangaResource::getUrl('edit', ['record' => $manga]));

        $response->assertOk();
        $response->assertSee('Couverture');
        $response->assertSee('Bannière');
    }

    public function test_process_chapter_zip_resizes_large_images(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $manga = Manga::create([
            'title' => 'Test Resize Manga',
            'slug' => 'test-resize-manga',
            'synopsis' => 'A manga for resizing testing',
            'type' => 'manga',
            'status' => 'en_cours',
        ]);

        $chapter = Chapter::create([
            'manga_id' => $manga->id,
            'number' => 1,
            'title' => 'Chapitre 1',
            'slug' => 'chapitre-1',
        ]);

        // Créer une grande image 2400x1600 px en mémoire avec Intervention Image
        $manager = new ImageManager(new Driver());
        $largeImage = $manager->createImage(2400, 1600);
        $encodedJpg = $largeImage->encode(new JpegEncoder(quality: 90));

        // Créer une archive ZIP contenant cette image
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($tempZipPath, ZipArchive::CREATE);
        $zip->addFromString('01.jpg', (string) $encodedJpg);
        $zip->close();

        // Enregistrer le ZIP sur le disque local
        $zipRelativePath = 'uploads/zips/test.zip';
        Storage::disk('local')->put($zipRelativePath, file_get_contents($tempZipPath));
        @unlink($tempZipPath);

        // Exécuter le job avec maxPageWidth = 1200
        $job = new ProcessChapterZip(
            chapter: $chapter,
            zipPath: $zipRelativePath,
            publishWhenReady: false,
            maxPageWidth: 1200
        );
        $job->handle();

        // Vérifier que la page a été créée et redimensionnée
        $page = ChapterPage::where('chapter_id', $chapter->id)->first();
        $this->assertNotNull($page);
        $this->assertTrue(Storage::disk('public')->exists($page->image_path));

        $savedContent = Storage::disk('public')->get($page->image_path);
        $savedImage = $manager->decode($savedContent);

        $this->assertEquals(1200, $savedImage->width(), 'L\'image importée de 2400px doit être redimensionnée à max 1200px');
        $this->assertEquals(800, $savedImage->height(), 'Le ratio d\'aspect (3:2) doit être conservé');
    }

    public function test_user_profile_avatar_upload_is_automatically_resized(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Avatar Tester',
        ]);

        $manager = new ImageManager(new Driver());
        $bigAvatar = $manager->createImage(1200, 1200);
        $encodedJpg = $bigAvatar->encode(new JpegEncoder(quality: 90));

        $tempAvatarPath = tempnam(sys_get_temp_dir(), 'avatar_') . '.jpg';
        file_put_contents($tempAvatarPath, (string) $encodedJpg);

        $uploadedFile = new UploadedFile(
            path: $tempAvatarPath,
            originalName: 'big_avatar.jpg',
            mimeType: 'image/jpeg',
            error: null,
            test: true
        );

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Avatar Tester',
            'email' => $user->email,
            'avatar_file' => $uploadedFile,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar));

        $savedImage = $manager->decode(Storage::disk('public')->get($user->avatar));
        $this->assertLessThanOrEqual(400, $savedImage->width(), 'L\'avatar doit être redimensionné à max 400px');
        $this->assertLessThanOrEqual(400, $savedImage->height(), 'L\'avatar doit être redimensionné à max 400px');

        @unlink($tempAvatarPath);
    }
}
