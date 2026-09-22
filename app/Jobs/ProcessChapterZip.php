<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\ChapterPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use ZipArchive;

class ProcessChapterZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Chapter $chapter,
        public string $zipPath,
    ) {}

    public function handle(): void
    {
        $zip = new ZipArchive();
        $fullPath = Storage::path($this->zipPath);

        if ($zip->open($fullPath) !== true) {
            Log::error('Impossible d\'ouvrir le ZIP', ['path' => $fullPath]);
            return;
        }

        $extractPath = Storage::path('temp/chapter_' . $this->chapter->id);
        $zip->extractTo($extractPath);
        $zip->close();

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|webp)$/i', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }

        if (empty($files)) {
            Log::error('Aucun fichier image trouvé dans le ZIP');
            return;
        }

        natsort($files);
        $files = array_values($files);

        $manager = new ImageManager(new Driver());
        $destFolder = 'chapters/' . $this->chapter->id;

        foreach ($files as $index => $file) {
            $pageNumber = $index + 1;
            $destPath   = $destFolder . '/' . $pageNumber . '.webp';

            $encoded = $manager->decode($file)->encode(new WebpEncoder(quality: 85));
            Storage::disk('public')->put($destPath, $encoded);

            ChapterPage::create([
                'chapter_id'  => $this->chapter->id,
                'image_path'  => $destPath,
                'page_number' => $pageNumber,
            ]);
        }

        Storage::deleteDirectory('temp/chapter_' . $this->chapter->id);
        Storage::delete($this->zipPath);

        Log::info('Job ZIP terminé', ['pages' => count($files)]);
    }
}