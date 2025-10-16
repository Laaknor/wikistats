<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::job(new App\Jobs\GetWikidataTrackingJob())->hourly()
    ->when(function() {
        return App\Models\WikidataTracking::where('last_sync','<',now()->subDays(7))->count() > 0;
    });
Schedule::job(new App\Jobs\GetCategoryCountsJob())->everyMinute()
    ->when(function() {
        return App\Models\Category::where('last_sync','<',now()->subDays(7))->count() > 0;
    });
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::job(new App\Jobs\GetSiteInfoJob())->everyFiveMinutes()
    ->when(function() {
        return App\Models\Site::where('last_siteinfo','<',now()->subDays(7))->count() > 0;
    });
Schedule::job(new App\Jobs\GetArchiveMetadataJob())->everyFiveMinutes()
    ->when(function() {
        return App\Models\ArchiveItem::where('last_sync',null)->count() > 0;
    });