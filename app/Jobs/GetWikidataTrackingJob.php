<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use App\Models\WikidataTracking;
use App\Models\Site;
use App\Models\Category;

class GetWikidataTrackingJob implements ShouldQueue
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
        $wd =WikidataTracking::where('last_sync', '<', now()->subDays(1))->orderBy('last_sync','asc')->first();
        if($wd) {
        $data = Http::get('https://wikidata.org/w/rest.php/wikibase/v1/entities/items/'.$wd->item.'?_fields=sitelinks')->json();
        foreach($data['sitelinks'] as $sitelink) 
        {
            $site = Site::parseUrl($sitelink['url']);
            $category = Category::updateOrCreate([
                'site_id' => $site->id,
                'wikidata_tracking_id' => $wd->id,
                'name' => explode('/wiki/',$sitelink['url'])[1],
                'type' => $wd->type,
            ]);
                $wd->last_sync = now();
                $wd->save();
            }
        }
    }
}
