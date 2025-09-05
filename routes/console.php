<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::job(new App\Jobs\GetWikidataTrackingJob())->hourlyAt(14);
Schedule::job(new App\Jobs\GetCategoryCountsJob())->everyFiveMinutes;
Schedule::command('horizon:snapshot')->everyFiveMinutes();