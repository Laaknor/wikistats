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
        $categories = Category::where('last_sync','<',now()->subDays(7))->inRandomOrder()->take(1)->get();
        foreach($categories as $category) {
            $url = $category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$category->name.'&format=json';
            $response = Http::get($url);
            if(!$response->ok()) {
                Log::error('Failed to fetch categoryinfo', [
                    'category_id' => $category->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                continue;
            }
            $data = $response->json();
            if (!isset($data['query']['pages'])) {
                Log::error('Missing pages key in categoryinfo response', [
                    'category_id' => $category->id,
                    'response' => $data,
                ]);
                continue;
            }
            
            foreach($data['query']['pages'] as $c) {
                $subcats = $c['categoryinfo']['subcats'] ?? 0;
                $pages = $c['categoryinfo']['pages'] ?? 0;

                if($subcats > $pages) {
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
                    $catMemberResponse = Http::get($category->site->url.'w/api.php?action=query&list=categorymembers&cmtitle='.$category->name.'&cmtype=subcat&cmlimit=500&format=json');
                    if(!$catMemberResponse->ok()) {
                        Log::error('Failed to fetch categorymembers', [
                            'category_id' => $category->id,
                            'status' => $catMemberResponse->status(),
                            'body' => $catMemberResponse->body(),
                        ]);
                        continue;
                    }
                    $catmembers = $catMemberResponse->json();
                    if (!isset($catmembers['query']['categorymembers'])) {
                        Log::error('Missing categorymembers key', [
                            'category_id' => $category->id,
                            'response' => $catmembers,
                        ]);
                        continue;
                    }
                    $subcat_sumcount = 0;
                    $category_query = "";
                    $subcat_runs = 0;
                    foreach($catmembers['query']['categorymembers'] as $subcat) {
                        $title = str_replace(' ','_',$subcat['title']);
                        $category_query .= $title.'|';
                        $subcat_runs++;
                        if($subcat_runs > 10) {
                            $subcatResponse = Http::get($category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$category_query.'&format=json');
                            if(!$subcatResponse->ok()) {
                                Log::error('Failed to fetch subcategory batch', [
                                    'category_id' => $category->id,
                                    'titles' => $category_query,
                                    'status' => $subcatResponse->status(),
                                    'body' => $subcatResponse->body(),
                                ]);
                                $subcat_runs = 0;
                                $category_query = "";
                                continue;
                            }
                            $subcatcount = $subcatResponse->json();
                            if (!isset($subcatcount['query']['pages'])) {
                                Log::warning('Missing pages key in subcategory batch', [
                                    'category_id' => $category->id,
                                    'titles' => $category_query,
                                    'response' => $subcatcount,
                                ]);
                                $subcat_runs = 0;
                                $category_query = "";
                                continue;
                            }
                            $subcat_runs = 0;
                            $pages = $subcatcount['query']['pages'];
                            if (!array_key_exists($subcat['pageid'], $pages)) {
                                Log::warning('Subcategory page missing in batch', [
                                    'category_id' => $category->id,
                                    'subcat_pageid' => $subcat['pageid'],
                                    'titles' => $category_query,
                                    'response_keys' => array_keys($pages),
                                ]);
                                $category_query = "";
                                continue;
                            }
                            $category_query = "";
                            $primaryPage = $pages[$subcat['pageid']]['categoryinfo']['pages'] ?? 0;
                            $subcat_sumcount += $primaryPage;
                            foreach($pages as $pageId => $page) {
                                $subcat_sumcount += $page['categoryinfo']['pages'] ?? 0;
                            }
                            sleep(2); // Do not overload the API
                        }
                    }
                    
                    CategoryCount::updateOrCreate([
                        'category_id' => $category->id,
                        'date' => now()->format('Y-m-d')
                    ],[
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
