<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\CategoryCount;
use Illuminate\Support\Facades\Log;

class GetCategoryCountsJob implements ShouldQueue
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
        $categories = Category::where('type','categorycount')->where('last_sync','<',now()->subDays(1))->orderBy('last_sync','asc')->take(5)->get();
        foreach($categories as $category) {
            $url = $category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$category->name.'&format=json';
            $data = Http::get($url)->json();
            foreach($data['query']['pages'] as $c) {
                if(isset($c['categoryinfo']['pages'])) {
                    CategoryCount::updateOrCreate([
                        'category_id' => $category->id,
                            'date' => now()->format('Y-m-d'),
                            'count' => $c['categoryinfo']['pages'],
                        ]);
                }
                else {
                    Log::error( "No pages found for ".$category->name);
                }
            }
            $category->last_sync = now();
            $category->save();
        }
    }
}
