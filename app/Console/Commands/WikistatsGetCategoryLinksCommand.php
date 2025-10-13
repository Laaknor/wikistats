<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ArchiveItem;
use App\Models\ArchiveFile;
use App\Models\Site;
use App\Models\Category;
use App\Models\CategoryCount;
use Illuminate\Support\Facades\DB;

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
        $file =ArchiveFile::where('filename','like','%-%-%categorylinks%')->where('last_sync',null)->whereIn('dbname',$sites)->inRandomOrder()->first();
        if($file) {
            $item = ArchiveItem::find($file->archive_item_id);
            $download = exec("ia download $item->identifier $file->filename --destdir=temp/");
         
            $this->info('Downloaded: '.$file->filename);
            $this->info('Starting to import SQL-file');
            $this->info("Starttidspunkt: ".now());
            exec("zcat temp/".$item->identifier."/".$file->filename." | mysql -u ".env("DB_USERNAME")." -p".env("DB_PASSWORD")." ".env("DB_DATABASE"));
            
            $this->info('Imported SQL-file');
            $this->info("Sluttidspunkt: ".now());

            $explode = explode('-', $file->filename);
            $dbname = $explode[0];
            $date = $item->publish_date;
            $this->info('DBName: '.$dbname);
            $this->info('Date: '.$date);

            $site = Site::where('dbname',$dbname)->first();
            $this->info('Site: '.$site->url);
            $categories = Category::where('site_id',$site->id)->get();
            foreach($categories as $category) {
                $this->info('Category: '.$category->name);
                $catname =substr(strstr($category->name, ":", false), 1);
                $count = DB::select("SELECT COUNT(*) AS amount FROM categorylinks WHERE cl_to = '".$catname."' AND cl_type = 'page'");
                $this->info('Count: '.$count[0]->amount);
                CategoryCount::updateOrCreate([
                    'category_id' => $category->id,
                    'date' => $date
                ], [
                    'count' => $count[0]->amount,
                ]);
            }
            $file->last_sync = now();
            $file->save();
            exec("rm -rf temp/".$item->identifier);
            $this->info('Removed temp file');
            

        }
    }
}
