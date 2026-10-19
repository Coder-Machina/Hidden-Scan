<?php

namespace Tests\Feature;

use App\Models\Manga;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use App\Services\ScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScraperSmartExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_extracts_chapters_from_nextjs_rsc_payload(): void
    {
        $manga = Manga::create([
            'title' => 'Test Next Manga',
            'slug' => 'test-next-manga',
            'synopsis' => 'Test',
            'views_count' => 0,
        ]);
        $source = ScraperSource::create([
            'name' => 'Poseidon Test',
            'base_url' => 'https://poseidon-scans.net',
            'sel_chapter_list' => '.wp-manga-chapter',
            'sel_chapter_title' => '.wp-manga-chapter a',
            'sel_chapter_url' => '.wp-manga-chapter a',
            'sel_chapter_pages' => '.page-break img',
            'active' => true,
        ]);

        $scraperManga = ScraperManga::create([
            'manga_id' => $manga->id,
            'scraper_source_id' => $source->id,
            'source_url' => 'https://poseidon-scans.net/serie/test-manga',
            'status' => 'active',
            'priority' => 1,
        ]);

        // Simule un HTML Next.js avec RSC payload sans classe .wp-manga-chapter
        $fakeHtml = '<html><head><title>Test Manga</title></head><body>
            <script>self.__next_f.push([1,"{\"manga\":{\"chapters\":[{\"number\":1,\"title\":\"Le Debut\",\"createdAt\":\"2026-01-01T00:00:00Z\"},{\"number\":2,\"title\":\"La Suite\",\"createdAt\":\"2026-01-02T00:00:00Z\"}]}}"])</script>
        </body></html>';

        Http::fake([
            'https://poseidon-scans.net/serie/test-manga' => Http::response($fakeHtml, 200),
        ]);

        $service = new ScraperService();
        $chapters = $service->getChapterList($scraperManga);

        $this->assertCount(2, $chapters);
        $this->assertEquals(1.0, $chapters[0]['number']);
        $this->assertEquals('Chapitre 1 - Le Debut', $chapters[0]['title']);
        $this->assertEquals('https://poseidon-scans.net/serie/test-manga/chapter/1', $chapters[0]['url']);
        $this->assertEquals(2.0, $chapters[1]['number']);
    }

    public function test_extracts_chapters_from_fallback_selectors_when_configured_fails(): void
    {
        $manga = Manga::create([
            'title' => 'Fallback Manga',
            'slug' => 'fallback-manga',
            'synopsis' => 'Test',
            'views_count' => 0,
        ]);
        $source = ScraperSource::create([
            'name' => 'Generic Source',
            'base_url' => 'https://generic-scans.com',
            'sel_chapter_list' => '.wrong-selector',
            'sel_chapter_title' => 'a',
            'sel_chapter_url' => 'a',
            'sel_chapter_pages' => '.wrong-page-selector',
            'active' => true,
        ]);

        $scraperManga = ScraperManga::create([
            'manga_id' => $manga->id,
            'scraper_source_id' => $source->id,
            'source_url' => 'https://generic-scans.com/manga/fallback',
            'status' => 'active',
            'priority' => 1,
        ]);

        $fakeHtml = '<html><body>
            <div id="chapterlist">
                <ul>
                    <li><a href="/chapter/1">Chapitre 1 : L\'éveil</a></li>
                    <li><a href="/chapter/2">Chapitre 2 : La bataille</a></li>
                </ul>
            </div>
        </body></html>';

        Http::fake([
            'https://generic-scans.com/manga/fallback' => Http::response($fakeHtml, 200),
        ]);

        $service = new ScraperService();
        $chapters = $service->getChapterList($scraperManga);

        $this->assertCount(2, $chapters);
        $this->assertEquals(1.0, $chapters[0]['number']);
        $this->assertEquals(2.0, $chapters[1]['number']);
    }
}
