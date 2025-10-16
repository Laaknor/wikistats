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
        $categories = Category::where('last_sync','<',now()->subDays(7))->orderBy('last_sync','asc')->take(2)->get();
        foreach($categories as $category) {
            $url = $category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$category->name.'&format=json';
            $data = Http::get($url)->json();
            
            foreach($data['query']['pages'] as $c) {
                if($c['categoryinfo']['subcats'] > $c['categoryinfo']['pages']) {
                    if($category->type != 'subcategorycount') $category->type = 'subcategorycount';
                } else {
                    if($category->type != 'categorycount') $category->type = 'categorycount';
                }
                if($category->type == 'categorycount') {
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
                } // End if categorycount
                if($category->type == 'subcategorycount') {
                    $catmembers = Http::get($category->site->url.'w/api.php?action=query&list=categorymembers&cmtitle='.$category->name.'&cmtype=subcat&cmlimit=500&format=json')->json();
                    $subcat_sumcount = 0;
                    foreach($catmembers['query']['categorymembers'] as $subcat) {
                        $subcatcount = Http::get($category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$subcat['title'].'&format=json')->json();
                        if(isset($subcatcount['query']['pages'][$subcat['pageid']]['categoryinfo']['pages'])) {
                            $subcat_sumcount += $subcatcount['query']['pages'][$subcat['pageid']]['categoryinfo']['pages'];
                        }
                        sleep(1); // Do not overload the API
                    }
                    CategoryCount::updateOrCreate([
                        'category_id' => $category->id,
                        'date' => now()->format('Y-m-d'),
                        'count' => $subcat_sumcount,
                    ]);
                }
                if($category->mw_category_id != $c['pageid']) {
                    $category->mw_category_id = $c['pageid'];
                }
                if($category->display_name != $c['title']) {
                    $category->display_name = $c['title'];
                }
            }
            $category->last_sync = now();
            $category->save();
        }
    }
}
