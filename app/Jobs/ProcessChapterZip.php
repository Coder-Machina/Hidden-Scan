<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\ChapterPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
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

        if ($zip->open(Storage::path($this->zipPath)) !== true) {
            return;
        }

        $extractPath = Storage::path('temp/chapter_' . $this->chapter->id);
        $zip->extractTo($extractPath);
        $zip->close();

        $files = glob($extractPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
        natsort($files);
        $files = array_values($files);

        $destFolder = 'chapters/' . $this->chapter->id;

        foreach ($files as $index => $file) {
            $pageNumber = $index + 1;
            $filename   = $pageNumber . '.webp';
            $destPath   = $destFolder . '/' . $filename;

            $image = Image::read($file)->toWebp(85);
            Storage::put($destPath, $image);

            ChapterPage::create([
                'chapter_id'  => $this->chapter->id,
                'image_path'  => $destPath,
                'page_number' => $pageNumber,
            ]);
        }

        Storage::deleteDirectory('temp/chapter_' . $this->chapter->id);
        Storage::delete($this->zipPath);
    }
}