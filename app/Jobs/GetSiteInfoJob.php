<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use App\Models\Site;
use App\Models\Siteinfo;

class GetSiteInfoJob implements ShouldQueue
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
        $sites = Site::where('last_siteinfo','<',now()->subDays(1))->orWhere('last_siteinfo',null)->orderBy('last_siteinfo','asc')->take(5)->get();
        foreach($sites as $site) {
            $url = $site->url.'w/api.php?action=query&meta=siteinfo&siprop=statistics&format=json';
            $data = Http::get($url)->json();
            foreach($data['query']['statistics'] as $stat => $count) {
                if(in_array($stat,['pages','users','admins','images','edits','articles'])) {
                    Siteinfo::updateOrCreate([
                        'site_id' => $site->id,
                        'info' => $stat,
                        'date' => now()->format('Y-m-d'),
                        'count' => $count,
                    ]);
                }
                else {
                    
                }
            }
            $site->last_siteinfo = now();
            $site->save();
        }
    }
}
