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
        public bool $publishWhenReady = false,
    ) {}

    public function handle(): void
    {
        // Prevent timeout during heavy image decoding/encoding
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $zip = new ZipArchive();
        $fullPath = null;
        $disk = null;

        // Try local disk (where Filament FileUpload saves when disk is 'local')
        if (Storage::disk('local')->exists($this->zipPath)) {
            $fullPath = Storage::disk('local')->path($this->zipPath);
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($this->zipPath)) {
            $fullPath = Storage::disk('public')->path($this->zipPath);
            $disk = 'public';
        } elseif (file_exists($this->zipPath)) {
            $fullPath = $this->zipPath;
        } elseif (file_exists(storage_path('app/private/' . $this->zipPath))) {
            $fullPath = storage_path('app/private/' . $this->zipPath);
            $disk = 'local';
        } elseif (file_exists(storage_path('app/public/' . $this->zipPath))) {
            $fullPath = storage_path('app/public/' . $this->zipPath);
            $disk = 'public';
        } elseif (file_exists(storage_path('app/' . $this->zipPath))) {
            $fullPath = storage_path('app/' . $this->zipPath);
        }

        $extractPath = storage_path('app/temp/chapter_' . $this->chapter->id);

        if ($fullPath && $zip->open($fullPath) === true) {
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();
        } elseif (!is_dir($extractPath)) {
            Log::error('Impossible d\'ouvrir le ZIP et aucun dossier temporaire trouvé', ['path' => $fullPath ?? $this->zipPath]);
            return;
        }

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
            Log::error('Aucun fichier image trouvé dans le ZIP', ['path' => $fullPath]);
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);
            return;
        }

        natsort($files);
        $files = array_values($files);

        $manager = new ImageManager(new Driver());
        $destFolder = 'chapters/' . $this->chapter->id;
        Storage::disk('public')->makeDirectory($destFolder);

        // Delete existing pages in DB to prevent duplicates
        $this->chapter->pages()->delete();

        foreach ($files as $index => $file) {
            @set_time_limit(120); // Reset timer for each page being processed
            $pageNumber = $index + 1;
            $destPath   = $destFolder . '/' . $pageNumber . '.webp';

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'webp') {
                Storage::disk('public')->put($destPath, file_get_contents($file));
            } else {
                $encoded = $manager->decode($file)->encode(new WebpEncoder(quality: 82));
                Storage::disk('public')->put($destPath, (string) $encoded);
            }

            ChapterPage::create([
                'chapter_id'  => $this->chapter->id,
                'image_path'  => $destPath,
                'page_number' => $pageNumber,
            ]);
        }

        \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

        if ($disk) {
            Storage::disk($disk)->delete($this->zipPath);
        } elseif ($fullPath && file_exists($fullPath)) {
            @unlink($fullPath);
        }

        Log::info('Job ZIP terminé avec succès', [
            'chapter_id' => $this->chapter->id,
            'pages' => count($files),
        ]);

        if ($this->publishWhenReady) {
            $this->chapter->publishSequential();
        } elseif ($this->chapter->status === \App\Enums\ChapterStatus::PUBLIE) {
            $this->chapter->notifyFavoriteUsers();
        }
    }
}