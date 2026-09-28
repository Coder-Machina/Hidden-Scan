<?php

namespace Tests\Feature;

use App\Enums\ChapterStatus;
use App\Enums\MangaStatus;
use App\Enums\MangaType;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\Manga;
use App\Models\User;
use App\Notifications\NewChapterNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SequentialChapterPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected Manga $manga;
    protected User $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manga = Manga::create([
            'title' => 'Test Manga Sequential',
            'slug' => 'test-manga-sequential',
            'type' => MangaType::MANGA,
            'status' => MangaStatus::EN_COURS,
        ]);

        $this->subscriber = User::factory()->create();
        $this->subscriber->favorites()->create(['manga_id' => $this->manga->id]);
    }

    public function test_chapter_2_waits_for_chapter_1_to_publish_first(): void
    {
        Notification::fake();

        // 2 chapters created during bulk upload, both in CONTROLE
        $ch1 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::CONTROLE,
        ]);

        $ch2 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 2,
            'slug' => 'chapitre-2',
            'status' => ChapterStatus::CONTROLE,
        ]);

        // Simulate Chapter 2 finishing processing first and having pages
        ChapterPage::create([
            'chapter_id' => $ch2->id,
            'page_number' => 1,
            'image_path' => 'chapters/2/1.webp',
        ]);

        // Chapter 2 calls publishSequential()
        $ch2->publishSequential();

        // Chapter 2 MUST still be in CONTROLE because Chapter 1 is not published yet!
        $this->assertEquals(ChapterStatus::CONTROLE, $ch2->fresh()->status);
        $this->assertNull($ch2->fresh()->published_at);
        Notification::assertNothingSent();

        // Now Chapter 1 finishes processing its pages
        ChapterPage::create([
            'chapter_id' => $ch1->id,
            'page_number' => 1,
            'image_path' => 'chapters/1/1.webp',
        ]);

        // Chapter 1 calls publishSequential()
        $ch1->publishSequential();

        // Chapter 1 MUST be published!
        $this->assertEquals(ChapterStatus::PUBLIE, $ch1->fresh()->status);
        $this->assertNotNull($ch1->fresh()->published_at);

        // AND Chapter 2 MUST have been published in cascade!
        $this->assertEquals(ChapterStatus::PUBLIE, $ch2->fresh()->status);
        $this->assertNotNull($ch2->fresh()->published_at);

        // Chapter 2 published_at must be >= Chapter 1 published_at
        $this->assertTrue($ch2->fresh()->published_at->gte($ch1->fresh()->published_at));

        // Notifications were sent for both chapters
        Notification::assertSentTo(
            $this->subscriber,
            NewChapterNotification::class,
            function ($notification) use ($ch1) {
                return $notification->chapter->id === $ch1->id;
            }
        );

        Notification::assertSentTo(
            $this->subscriber,
            NewChapterNotification::class,
            function ($notification) use ($ch2) {
                return $notification->chapter->id === $ch2->id;
            }
        );
    }

    public function test_three_chapters_publish_in_strict_numerical_order(): void
    {
        Notification::fake();

        $ch1 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 1,
            'slug' => 'chapitre-1',
            'status' => ChapterStatus::CONTROLE,
        ]);

        $ch2 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 2,
            'slug' => 'chapitre-2',
            'status' => ChapterStatus::CONTROLE,
        ]);

        $ch3 = Chapter::create([
            'manga_id' => $this->manga->id,
            'number' => 3,
            'slug' => 'chapitre-3',
            'status' => ChapterStatus::CONTROLE,
        ]);

        // Chapters 3 and 2 finish processing before Chapter 1
        ChapterPage::create(['chapter_id' => $ch3->id, 'page_number' => 1, 'image_path' => 'chapters/3/1.webp']);
        ChapterPage::create(['chapter_id' => $ch2->id, 'page_number' => 1, 'image_path' => 'chapters/2/1.webp']);

        $ch3->publishSequential();
        $ch2->publishSequential();

        // Both 3 and 2 should wait in CONTROLE
        $this->assertEquals(ChapterStatus::CONTROLE, $ch3->fresh()->status);
        $this->assertEquals(ChapterStatus::CONTROLE, $ch2->fresh()->status);

        // Chapter 1 finishes
        ChapterPage::create(['chapter_id' => $ch1->id, 'page_number' => 1, 'image_path' => 'chapters/1/1.webp']);
        $ch1->publishSequential();

        // All three chapters must now be published
        $this->assertEquals(ChapterStatus::PUBLIE, $ch1->fresh()->status);
        $this->assertEquals(ChapterStatus::PUBLIE, $ch2->fresh()->status);
        $this->assertEquals(ChapterStatus::PUBLIE, $ch3->fresh()->status);

        // Verify chronological order: ch1 <= ch2 <= ch3
        $t1 = $ch1->fresh()->published_at->timestamp;
        $t2 = $ch2->fresh()->published_at->timestamp;
        $t3 = $ch3->fresh()->published_at->timestamp;

        $this->assertTrue($t1 <= $t2);
        $this->assertTrue($t2 <= $t3);
    }

    public function test_bulk_zip_inspection_and_sorting_detects_chapter_numbers(): void
    {
        $tempDir = storage_path('app/temp/test_sorting');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create 3 zips with out-of-order chapter numbers inside
        $zip3Path = $tempDir . '/fast_upload_3.zip';
        $zip1Path = $tempDir . '/slow_upload_1.zip';
        $zip2Path = $tempDir . '/medium_upload_2.zip';

        $z3 = new \ZipArchive();
        $z3->open($zip3Path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $z3->addFromString('Chapitre 3/01.jpg', 'fake-image-3');
        $z3->close();

        $z1 = new \ZipArchive();
        $z1->open($zip1Path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $z1->addFromString('Chapitre 1/01.jpg', 'fake-image-1');
        $z1->close();

        $z2 = new \ZipArchive();
        $z2->open($zip2Path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $z2->addFromString('Chapitre 2/01.jpg', 'fake-image-2');
        $z2->close();

        // Simulate unordered array as received by Filament when fast uploads finish first
        $zipFiles = [$zip3Path, $zip1Path, $zip2Path];

        $inspectedFiles = [];
        foreach ($zipFiles as $zipFile) {
            $detectedNumber = null;
            $zip = new \ZipArchive();
            if ($zip->open($zipFile) === true) {
                for ($i = 0; $i < min(10, $zip->numFiles); $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (preg_match('/(?:chapitre|chapter|ch|ep|épisode|episode)[\s._-]*(\d+(?:\.\d+)?)/i', $entryName, $m)) {
                        $detectedNumber = (float) $m[1];
                        break;
                    }
                }
                $zip->close();
            }

            $inspectedFiles[] = [
                'file' => $zipFile,
                'detected_number' => $detectedNumber,
            ];
        }

        // Sort them
        usort($inspectedFiles, function ($a, $b) {
            return $a['detected_number'] <=> $b['detected_number'];
        });

        // The order MUST now be Chapter 1, Chapter 2, Chapter 3!
        $this->assertEquals(1.0, $inspectedFiles[0]['detected_number']);
        $this->assertEquals($zip1Path, $inspectedFiles[0]['file']);

        $this->assertEquals(2.0, $inspectedFiles[1]['detected_number']);
        $this->assertEquals($zip2Path, $inspectedFiles[1]['file']);

        $this->assertEquals(3.0, $inspectedFiles[2]['detected_number']);
        $this->assertEquals($zip3Path, $inspectedFiles[2]['file']);

        // Clean up
        @unlink($zip1Path);
        @unlink($zip2Path);
        @unlink($zip3Path);
        @rmdir($tempDir);
    }
}
