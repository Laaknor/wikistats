<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Site;
use App\Models\WikidataTracking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

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
        $wd = WikidataTracking::where('last_sync', '<', now()->subDays(1))->orderBy('last_sync', 'asc')->first();
        if ($wd) {
            $data = Http::get('https://wikidata.org/w/rest.php/wikibase/v1/entities/items/'.$wd->item.'?_fields=sitelinks')->json();
            foreach ($data['sitelinks'] as $sitelink) {
                $site = Site::parseUrl($sitelink['url']);
                $category = Category::firstOrCreate([
                    'site_id' => $site->id,
                    'wikidata_tracking_id' => $wd->id,
                    'name' => rawurldecode(explode('/wiki/', $sitelink['url'], 2)[1]),
                ], [
                    'type' => $wd->type,
                ]);
            }
            $wd->last_sync = now();
            $wd->save();
        }
    }
}
