<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\GraphController;

#Route::view('/', 'welcome');
Route::get('/', function() {
    return redirect()->route('site.index');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
Route::resource('site', SiteController::class)
    ->parameters(['site' => 'site:hostname']);

// Graph routes nested under sites
Route::get('site/{site:hostname}/graph/{graph:name}', [GraphController::class, 'show'])
    ->name('graph.show');
Route::get('site/{site:hostname}/graph-small/{graph:name}', [GraphController::class, 'showSmall'])
    ->name('graph.small');
Route::get('site/{site:hostname}/graph-image/{graph:name}', [GraphController::class, 'showSmallImage'])
    ->name('graph.image');
// Combined chart (multiple categories) by slug
Route::get('site/{site:hostname}/chart/{chartSlug}', [GraphController::class, 'showChart'])
    ->name('chart.show');
Route::get('site/{site:hostname}/chart-small/{chartSlug}', [GraphController::class, 'showSmallChart'])
    ->name('chart.small');
Route::get('test', [App\Http\Controllers\TestController::class, 'index']);
Route::get('about', [App\Http\Controllers\StaticPageController::class, 'about'])->name('about');