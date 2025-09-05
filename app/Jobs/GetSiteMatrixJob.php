<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use App\Models\Site;

class GetSiteMatrixJob implements ShouldQueue
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
    public static function handle(): void
    {
        //
        $sitematrix = Http::get('https://www.mediawiki.org/w/api.php?action=sitematrix&format=json');
        foreach($sitematrix->json()['sitematrix'] as $language => $site) {

        if($language == 'count')
            continue;

            foreach($site as $s) {
                echo "Langcode: ".$language['code'] . " Family: " . $s['sitename'] . " DBName: " . $s['dbname'] . " URL: " . $s['url'] . "\n";
                Site::updateOrCreate([
                    'language' => $language['code'],
                    'family' => $s['sitename'],
                    'dbname' => $s['dbname'],
                    'url' => $s['url'],
                ]);
            }
        }
    }
}
