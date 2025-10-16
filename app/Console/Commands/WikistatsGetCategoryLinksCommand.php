<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ArchiveItem;
use App\Models\ArchiveFile;
use App\Models\Site;
use App\Models\Category;
use App\Models\CategoryCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    public function handle()
    {
        //
        $sites = Site::all()->pluck('dbname')->toArray();
        $file =ArchiveFile::where('filename','like','%-%-%categorylinks%.sql.gz')->where('last_sync',null)->whereIn('dbname',$sites)->inRandomOrder()->first();
        if($file) {
            $item = ArchiveItem::find($file->archive_item_id);
            $download = exec("ia download $item->identifier $file->filename --destdir=temp/");
         
            $this->info('Downloaded: '.$file->filename);
            $this->info('Starting to import SQL-file');
            $this->info("Starttidspunkt: ".now());
            
            exec("zcat temp/".$item->identifier."/".$file->filename." | mysql");
            
            $this->info('Imported SQL-file categorylinks');
            $this->info("Sluttidspunkt: ".now());
            $findPageFile = ArchiveFile::where('archive_item_id',$file->archive_item_id)->where('filename','like','%page.sql.gz')->first();
            if($findPageFile) {
                $item = ArchiveItem::find($findPageFile->archive_item_id);
                $download = exec("ia download $item->identifier $findPageFile->filename --destdir=temp/");
                $this->info('Downloaded: '.$findPageFile->filename);
                $this->info('Starting to import SQL-file page');
                $this->info("Starttidspunkt: ".now());
                exec("zcat temp/".$item->identifier."/".$findPageFile->filename." | mysql");
                $this->info('Imported SQL-file page');
                $this->info("Sluttidspunkt: ".now());
            }

            $explode = explode('-', $file->filename);
            $dbname = $explode[0];
            $date = $item->publish_date;
            $this->info('DBName: '.$dbname);
            $this->info('Date: '.$date);

            $site = Site::where('dbname',$dbname)->first();
            $this->info('Site: '.$site->url);
            $categories = Category::where('site_id',$site->id)->get();
            foreach($categories as $category) {
                $category_count = 0;
                $this->info('Category: '.$category->display_name);
                $catname = str_replace(' ','_',substr(strstr($category->display_name, ":", false), 1));
                if ($category->type === 'subcategorycount') {
                    $subcategories = DB::select("SELECT COUNT(*) AS antall FROM categorylinks cl WHERE cl_type = 'page' AND cl_to IN (SELECT page_title FROM page WHERE page_id IN (SELECT cl_from FROM categorylinks WHERE cl_to = '$catname' AND cl_type = 'subcat'));");

                    $category_count = $subcategories[0]->antall;
                } else {
                    $count = DB::select("SELECT COUNT(*) AS amount FROM categorylinks WHERE cl_to = '".$catname."' AND cl_type = 'page'");
                    $category_count = $count[0]->amount;
                }
                $this->info('Count: '.$category_count);
                CategoryCount::updateOrCreate([
                    'category_id' => $category->id,
                    'date' => $date
                ], [
                    'count' => $category_count,
                ]);
            }
            $file->last_sync = now();
            $file->save();
            exec("rm -rf temp/".$item->identifier);
            $this->info('Removed temp file');
            

        }
    }
}
