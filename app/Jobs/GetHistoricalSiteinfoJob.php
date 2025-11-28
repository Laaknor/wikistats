<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Site;
use App\Models\ArchiveFile;
use App\Models\ArchiveItem;
use App\Models\Siteinfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class GetHistoricalSiteinfoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $sites = Site::all()->pluck('dbname')->toArray();
        $files = ArchiveFile::where('filename','like','%-%-%site_stats%')->where('last_sync',null)->whereIn('dbname',$sites)->inRandomOrder()->first();
        if($files) {
            $item = ArchiveItem::find($files->archive_item_id);
            $download = exec("ia download $item->identifier $files->filename --destdir=temp/");
            Log::info('Downloaded: '.$files->filename);
            Log::info('Starting to import SQL-file');
            Log::info("Starttidspunkt: ".now());
            $folder = "temp/".$item->identifier."/";
            $filename = $folder.$files->filename;
            if(!file_exists($filename)) {
                Log::warn('Downloaded file not found: '.$files->filename.'. Skipping.');
                exit;
            }
            if(Str::endsWith($filename,'.gz')) {
                $newFilename = Str::replaceLast('.gz','',$filename);
                exec("gunzip -c ".$filename." > ".$newFilename);
                
                $filename = $newFilename;
            }
            
            exec("cat ".$filename." | mysql");
            Log::info('Imported SQL-file');
            $explode = explode('-', $files->filename);
            $dbname = $explode[0];
            $date = $item->publish_date;

            $site = Site::where('dbname',$dbname)->first();
            $db = DB::select("SELECT * FROM site_stats LIMIT 1");
            Siteinfo::updateOrCreate([
                'site_id' => $site->id,
                'info' => 'edits',
                'date' => $date,
                'count' => $db[0]->ss_total_edits,
            ]);
            Siteinfo::updateOrCreate([
                'site_id' => $site->id,
                'info' => 'activeusers',
                'date' => $date,
                'count' => $db[0]->ss_active_users,
            ]);
            Siteinfo::updateOrCreate([
                'site_id' => $site->id,
                'info' => 'pages',
                'date' => $date,
                'count' => $db[0]->ss_total_pages,
            ]);
           
            Log::info("Sluttidspunkt: ".now());
            $files->last_sync = now();
            $files->save();
            exec("rm -rf temp/".$item->identifier);
            Schema::dropIfExists('site_stats');
            Log::info('Removed temp file');

        }

    }
}
