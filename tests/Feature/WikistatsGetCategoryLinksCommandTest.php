<?php

namespace Tests\Feature;

use App\Models\ArchiveFile;
use App\Models\ArchiveItem;
use App\Models\Category;
use App\Models\CategoryCount;
use App\Models\Site;
use App\Models\WikidataTracking;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WikistatsGetCategoryLinksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_categories_with_apostrophes_from_archive_tables(): void
    {
        [$site, $archiveItem] = $this->createSiteAndArchiveItem();
        $tracking = $this->createTracking();

        $category = Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => "Category:Merc'hed",
            'display_name' => "Category:Merc'hed",
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => true,
        ]);

        $subcategory = Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => "Category:Merc'hed_subcats",
            'display_name' => "Category:Merc'hed subcats",
            'type' => 'subcategorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => true,
        ]);

        $categoryLinksFile = $this->createCategoryLinksFile($archiveItem);
        $this->createPageFile($archiveItem);

        Schema::create('categorylinks', function (Blueprint $table) {
            $table->integer('cl_from');
            $table->string('cl_to');
            $table->string('cl_type');
        });

        Schema::create('page', function (Blueprint $table) {
            $table->integer('page_id');
            $table->string('page_title');
        });

        DB::table('categorylinks')->insert([
            ['cl_from' => 1, 'cl_to' => "Merc'hed", 'cl_type' => 'page'],
            ['cl_from' => 2, 'cl_to' => "Merc'hed", 'cl_type' => 'page'],
            ['cl_from' => 10, 'cl_to' => "Merc'hed_subcats", 'cl_type' => 'subcat'],
            ['cl_from' => 11, 'cl_to' => "Merc'hed_subcategory", 'cl_type' => 'page'],
            ['cl_from' => 12, 'cl_to' => "Merc'hed_subcategory", 'cl_type' => 'page'],
            ['cl_from' => 13, 'cl_to' => "Merc'hed_subcategory", 'cl_type' => 'page'],
        ]);

        DB::table('page')->insert([
            ['page_id' => 10, 'page_title' => "Merc'hed_subcategory"],
        ]);

        $tempPath = base_path('temp/'.$archiveItem->identifier);
        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        file_put_contents($tempPath.'/brwiki-20260601-categorylinks.sql.gz', gzencode(''));
        file_put_contents($tempPath.'/brwiki-20260601-page.sql.gz', gzencode(''));

        $this->withStubbedCliTools(function () use ($category, $subcategory, $categoryLinksFile) {
            $this->artisan('wikistats:getcategorycount')
                ->assertExitCode(0);

            $this->assertDatabaseHas('category_counts', [
                'category_id' => $category->id,
                'date' => '2026-06-01',
                'count' => 2,
            ]);

            $this->assertDatabaseHas('category_counts', [
                'category_id' => $subcategory->id,
                'date' => '2026-06-01',
                'count' => 3,
            ]);

            $this->assertNotNull($categoryLinksFile->fresh()->last_sync);
            $this->assertFalse(Schema::hasTable('categorylinks'));
            $this->assertFalse(Schema::hasTable('page'));
        });
    }

    public function test_skips_download_when_page_dump_is_missing(): void
    {
        [$site, $archiveItem] = $this->createSiteAndArchiveItem();
        $tracking = $this->createTracking();

        Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => 'Category:People',
            'display_name' => 'Category:People',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => true,
        ]);

        $categoryLinksFile = $this->createCategoryLinksFile($archiveItem);
        $downloadMarker = base_path('temp/ia-download-called');

        $this->withStubbedCliTools(function () use ($categoryLinksFile, $downloadMarker) {
            $this->artisan('wikistats:getcategorycount')
                ->expectsOutput('Skipped 1 categorylinks dump(s) because the matching page dump is missing.')
                ->assertExitCode(0);

            $this->assertFileDoesNotExist($downloadMarker);
            $this->assertNull($categoryLinksFile->fresh()->last_sync);
            $this->assertSame(0, CategoryCount::query()->count());
        }, $downloadMarker);
    }

    public function test_skips_import_when_downloaded_dumps_are_missing(): void
    {
        [$site, $archiveItem] = $this->createSiteAndArchiveItem();
        $tracking = $this->createTracking();

        Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => 'Category:People',
            'display_name' => 'Category:People',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => true,
        ]);

        $categoryLinksFile = $this->createCategoryLinksFile($archiveItem);
        $this->createPageFile($archiveItem);
        $downloadMarker = base_path('temp/ia-download-called');

        $this->withStubbedCliTools(function () use ($categoryLinksFile, $downloadMarker) {
            $this->artisan('wikistats:getcategorycount')
                ->expectsOutput('Downloaded files not found. Skipping import.')
                ->assertExitCode(0);

            $this->assertFileExists($downloadMarker);
            $this->assertNull($categoryLinksFile->fresh()->last_sync);
            $this->assertSame(0, CategoryCount::query()->count());
        }, $downloadMarker);
    }

    /**
     * @return array{0: Site, 1: ArchiveItem}
     */
    private function createSiteAndArchiveItem(): array
    {
        $site = Site::create([
            'language' => 'br',
            'family' => 'wikipedia',
            'dbname' => 'brwiki',
            'hostname' => 'br.wikipedia.org',
            'url' => 'https://br.wikipedia.org/',
            'enabled' => true,
        ]);

        $archiveItem = ArchiveItem::create([
            'identifier' => 'brwiki-20260601',
            'publish_date' => '2026-06-01',
            'last_sync' => now(),
            'is_active' => true,
        ]);

        return [$site, $archiveItem];
    }

    private function createTracking(): WikidataTracking
    {
        return WikidataTracking::create([
            'item' => 'Q123',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
        ]);
    }

    private function createCategoryLinksFile(ArchiveItem $archiveItem): ArchiveFile
    {
        return ArchiveFile::create([
            'archive_item_id' => $archiveItem->id,
            'filename' => 'brwiki-20260601-categorylinks.sql.gz',
            'dbname' => 'brwiki',
            'last_sync' => null,
        ]);
    }

    private function createPageFile(ArchiveItem $archiveItem): ArchiveFile
    {
        return ArchiveFile::create([
            'archive_item_id' => $archiveItem->id,
            'filename' => 'brwiki-20260601-page.sql.gz',
            'dbname' => 'brwiki',
            'last_sync' => null,
        ]);
    }

    private function withStubbedCliTools(callable $callback, ?string $downloadMarker = null): void
    {
        $tempRoot = base_path('temp');
        if (! is_dir($tempRoot)) {
            mkdir($tempRoot, 0777, true);
        }

        $binPath = $tempRoot.'/test-bin';
        if (! is_dir($binPath)) {
            mkdir($binPath, 0777, true);
        }

        $iaScript = "#!/bin/sh\n";
        if ($downloadMarker !== null) {
            $iaScript .= 'echo called > '.escapeshellarg($downloadMarker)."\n";
        }
        $iaScript .= "exit 0\n";

        file_put_contents($binPath.'/ia', $iaScript);
        file_put_contents($binPath.'/mysql', "#!/bin/sh\ncat >/dev/null\nexit 0\n");
        chmod($binPath.'/ia', 0755);
        chmod($binPath.'/mysql', 0755);

        $originalPath = getenv('PATH') ?: '';
        putenv('PATH='.$binPath.PATH_SEPARATOR.$originalPath);

        try {
            $callback();
        } finally {
            putenv('PATH='.$originalPath);

            if ($downloadMarker !== null && file_exists($downloadMarker)) {
                unlink($downloadMarker);
            }

            foreach (glob($binPath.'/*') ?: [] as $file) {
                unlink($file);
            }

            if (is_dir($binPath)) {
                rmdir($binPath);
            }
        }
    }
}
