<?php

use App\Jobs\GetArchiveMetadataJob;
use App\Jobs\GetCategoryCountsJob;
use App\Jobs\GetHistoricalSiteinfoJob;
use App\Jobs\GetSiteInfoJob;
use App\Jobs\GetWikidataTrackingJob;
use App\Models\ArchiveFile;
use App\Models\ArchiveItem;
use App\Models\Category;
use App\Models\Site;
use App\Models\WikidataTracking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::job(new GetWikidataTrackingJob)->hourly()
    ->when(function () {
        return WikidataTracking::where('last_sync', '<', now()->subDays(7))->count() > 0;
    });
Schedule::job(new GetCategoryCountsJob)->everyMinute()
    ->when(function () {
        return Category::query()->dueForSync()->count() > 0;
    });
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::job(new GetSiteInfoJob)->everyFiveMinutes()
    ->when(function () {
        return Site::where('last_siteinfo', '<', now()->subDays(7))->count() > 0;
    });
Schedule::job(new GetArchiveMetadataJob)->everyFiveMinutes()
    ->when(function () {
        return ArchiveItem::where('last_sync', null)->count() > 0;
    });
Schedule::job(new GetHistoricalSiteinfoJob)->yearly()
    ->when(function () {
        return ArchiveFile::where('filename', 'like', '%-%-%site_stats%')->where('last_sync', null)->count() > 0;
    });
