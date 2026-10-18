<?php

namespace Database\Seeders;

use App\Models\ScraperSource;
use Illuminate\Database\Seeder;

class ScraperSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ScraperSource::updateOrCreate(
            ['name' => 'poseidon-scan'],
            [
                'base_url'          => 'https://poseidon-scan.net',
                'sel_chapter_list'  => '.wp-manga-chapter',
                'sel_chapter_title' => '.wp-manga-chapter a',
                'sel_chapter_url'   => '.wp-manga-chapter a',
                'sel_chapter_date'  => '.chapter-release-date',
                'sel_chapter_pages' => '.page-break img',
                'headers'           => [
                    'Referer'    => 'https://poseidon-scan.net',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ],
                'active'            => true,
            ]
        );
    }
}
