<?php

namespace App\Console\Commands;

use App\Models\ArchiveFile;
use App\Models\ArchiveItem;
use App\Models\Category;
use App\Models\CategoryCount;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WikistatsGetCategoryLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wikistats:getcategorycount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Archive.org Category Count from a file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sites = Site::query()->pluck('dbname')->all();
        $categoryLinksFile = $this->findProcessableCategoryLinksFile($sites);

        if (! $categoryLinksFile) {
            $skipped = $this->unsyncedCategoryLinksWithoutPageCount($sites);
            if ($skipped > 0) {
                $this->warn("Skipped {$skipped} categorylinks dump(s) because the matching page dump is missing.");
            }

            $this->finish($sites);

            return self::SUCCESS;
        }

        $archiveItem = ArchiveItem::query()->find($categoryLinksFile->archive_item_id);
        $pageFile = $this->findPageFile($categoryLinksFile);

        if (! $archiveItem || ! $pageFile) {
            $this->warn('Required categorylinks and page dumps were not both found. Skipping download.');
            $this->finish($sites);

            return self::SUCCESS;
        }

        $this->info('Required dumps found: '.$categoryLinksFile->filename.' and '.$pageFile->filename);

        try {
            $this->downloadArchiveFile($archiveItem, $categoryLinksFile);
            $this->downloadArchiveFile($archiveItem, $pageFile);

            $categoryLinksPath = $this->localPath($archiveItem, $categoryLinksFile);
            $pagePath = $this->localPath($archiveItem, $pageFile);

            if (! file_exists($categoryLinksPath) || ! file_exists($pagePath)) {
                $this->warn('Downloaded files not found. Skipping import.');

                return self::SUCCESS;
            }

            $this->importSqlDump($categoryLinksPath, 'categorylinks');
            $this->importSqlDump($pagePath, 'page');
            $this->recordCategoryCounts($categoryLinksFile, $archiveItem);

            $categoryLinksFile->last_sync = now();
            $categoryLinksFile->save();
        } finally {
            $this->cleanupTempFiles($archiveItem);
            $this->finish($sites);
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $sites
     */
    private function findProcessableCategoryLinksFile(array $sites): ?ArchiveFile
    {
        return $this->constrainToMatchingPageDump($this->categoryLinksQuery($sites))
            ->inRandomOrder()
            ->first();
    }

    private function findPageFile(ArchiveFile $categoryLinksFile): ?ArchiveFile
    {
        return ArchiveFile::query()
            ->where('archive_item_id', $categoryLinksFile->archive_item_id)
            ->where('dbname', $categoryLinksFile->dbname)
            ->where('filename', 'like', '%-page.sql.gz')
            ->first();
    }

    /**
     * @param  list<string>  $sites
     * @return Builder<ArchiveFile>
     */
    private function categoryLinksQuery(array $sites): Builder
    {
        return ArchiveFile::query()
            ->where('filename', 'like', '%-%-%categorylinks%.sql.gz')
            ->whereNull('last_sync')
            ->whereIn('dbname', $sites);
    }

    /**
     * @param  Builder<ArchiveFile>  $query
     * @return Builder<ArchiveFile>
     */
    private function constrainToMatchingPageDump(Builder $query): Builder
    {
        return $query->whereExists(function ($query): void {
            $query->selectRaw('1')
                ->from('archive_files as page_files')
                ->whereColumn('page_files.archive_item_id', 'archive_files.archive_item_id')
                ->whereColumn('page_files.dbname', 'archive_files.dbname')
                ->where('page_files.filename', 'like', '%-page.sql.gz');
        });
    }

    /**
     * @param  list<string>  $sites
     */
    private function unsyncedCategoryLinksWithoutPageCount(array $sites): int
    {
        return $this->categoryLinksQuery($sites)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('archive_files as page_files')
                    ->whereColumn('page_files.archive_item_id', 'archive_files.archive_item_id')
                    ->whereColumn('page_files.dbname', 'archive_files.dbname')
                    ->where('page_files.filename', 'like', '%-page.sql.gz');
            })
            ->count();
    }

    /**
     * @param  list<string>  $sites
     */
    private function finish(array $sites): void
    {
        $this->dropImportedTables();
        $this->reportRemainingCount($sites);
    }

    /**
     * @param  list<string>  $sites
     */
    private function reportRemainingCount(array $sites): void
    {
        $count = $this->constrainToMatchingPageDump($this->categoryLinksQuery($sites))->count();

        $this->info('Count of categorylinkfiles left to process: '.$count);
    }

    private function downloadArchiveFile(ArchiveItem $item, ArchiveFile $file): void
    {
        exec('ia download '.$item->identifier.' '.$file->filename.' --destdir=temp/');
        $this->info('Downloaded: '.$file->filename);
    }

    private function localPath(ArchiveItem $item, ArchiveFile $file): string
    {
        return 'temp/'.$item->identifier.'/'.$file->filename;
    }

    private function importSqlDump(string $path, string $label): void
    {
        $this->info('Starting to import SQL-file '.$label);
        $this->info('Starttidspunkt: '.now());
        exec('zcat '.$path.' | mysql');
        $this->info('Imported SQL-file '.$label);
        $this->info('Sluttidspunkt: '.now());
    }

    private function recordCategoryCounts(ArchiveFile $file, ArchiveItem $item): void
    {
        $dbname = explode('-', $file->filename)[0];
        $date = $item->publish_date;
        $this->info('DBName: '.$dbname);
        $this->info('Date: '.$date);

        $site = Site::query()->where('dbname', $dbname)->first();
        $this->info('Site: '.$site->url);

        $categories = Category::query()->where('site_id', $site->id)->get();
        foreach ($categories as $category) {
            $this->info('Category: '.$category->display_name);
            $catname = str_replace(' ', '_', substr(strstr($category->display_name, ':', false), 1));

            if ($category->type === 'subcategorycount') {
                $subcategories = DB::select(
                    'SELECT COUNT(*) AS antall FROM categorylinks cl WHERE cl_type = ? AND cl_to IN (SELECT page_title FROM page WHERE page_id IN (SELECT cl_from FROM categorylinks WHERE cl_to = ? AND cl_type = ?));',
                    ['page', $catname, 'subcat']
                );
                $categoryCount = $subcategories[0]->antall;
            } else {
                $count = DB::select(
                    'SELECT COUNT(*) AS amount FROM categorylinks WHERE cl_to = ? AND cl_type = ?',
                    [$catname, 'page']
                );
                $categoryCount = $count[0]->amount;
            }

            $this->info('Count: '.$categoryCount);
            CategoryCount::updateOrCreate([
                'category_id' => $category->id,
                'date' => $date,
            ], [
                'count' => $categoryCount,
            ]);
        }
    }

    private function cleanupTempFiles(ArchiveItem $item): void
    {
        exec('rm -rf temp/'.$item->identifier);
        $this->info('Removed temp file');
    }

    private function dropImportedTables(): void
    {
        Schema::dropIfExists('categorylinks');
        Schema::dropIfExists('page');
    }
}
