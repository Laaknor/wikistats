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
Route::resource('graph', GraphController::class);