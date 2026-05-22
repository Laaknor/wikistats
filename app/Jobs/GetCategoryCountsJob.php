<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\CategoryCount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
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
        $categories = Category::query()
            ->dueForSync()
            ->inRandomOrder()
            ->take(1)
            ->get();

        foreach ($categories as $category) {
            $this->syncCategory($category);
        }
    }

    protected function syncCategory(Category $category): void
    {
        $title = rawurlencode($category->wikiApiTitle());
        $url = $category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$title.'&format=json';
        $response = Http::get($url);

        if (! $response->ok()) {
            Log::error('Failed to fetch categoryinfo', [
                'category_id' => $category->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $data = $response->json();
        if (! isset($data['query']['pages'])) {
            Log::error('Missing pages key in categoryinfo response', [
                'category_id' => $category->id,
                'response' => $data,
            ]);

            return;
        }

        foreach ($data['query']['pages'] as $page) {
            if (Category::pageIsMissingOnWiki($page)) {
                Log::warning('Category page missing on wiki; deactivating', [
                    'category_id' => $category->id,
                    'name' => $category->name,
                    'wiki_api_title' => $category->wikiApiTitle(),
                    'api_page' => $page,
                ]);
                $category->markDeletedOnWiki();

                return;
            }

            $subcats = $page['categoryinfo']['subcats'] ?? 0;
            $pages = $page['categoryinfo']['pages'] ?? 0;

            if ($subcats > $pages) {
                if ($category->type != 'subcategorycount') {
                    $category->type = 'subcategorycount';
                }
            } else {
                if ($category->type != 'categorycount') {
                    $category->type = 'categorycount';
                }
            }

            if ($category->type == 'categorycount') {
                if (isset($page['categoryinfo']['pages'])) {
                    CategoryCount::updateOrCreate([
                        'category_id' => $category->id,
                        'date' => now()->format('Y-m-d'),
                        'count' => $page['categoryinfo']['pages'],
                    ]);
                } else {
                    Log::error('No pages found for '.$category->name);
                }
            }

            if ($category->type == 'subcategorycount') {
                $this->syncSubcategoryCount($category);
            }

            if (isset($page['pageid']) && $category->mw_category_id != $page['pageid']) {
                $category->mw_category_id = $page['pageid'];
            }

            if (isset($page['title']) && $category->display_name != $page['title']) {
                $category->display_name = $page['title'];
            }
        }

        $category->last_sync = now();
        $category->save();
    }

    protected function syncSubcategoryCount(Category $category): void
    {
        $cmtitle = rawurlencode($category->wikiApiTitle());
        $catMemberResponse = Http::get($category->site->url.'w/api.php?action=query&list=categorymembers&cmtitle='.$cmtitle.'&cmtype=subcat&cmlimit=500&format=json');

        if (! $catMemberResponse->ok()) {
            Log::error('Failed to fetch categorymembers', [
                'category_id' => $category->id,
                'status' => $catMemberResponse->status(),
                'body' => $catMemberResponse->body(),
            ]);

            return;
        }

        $catmembers = $catMemberResponse->json();
        if (! isset($catmembers['query']['categorymembers'])) {
            Log::error('Missing categorymembers key', [
                'category_id' => $category->id,
                'response' => $catmembers,
            ]);

            return;
        }

        $subcat_sumcount = 0;
        $category_query = '';
        $subcat_runs = 0;
        $subcat_titles = [];

        foreach ($catmembers['query']['categorymembers'] as $subcat) {
            $title = str_replace(' ', '_', $subcat['title']);
            $category_query .= $title.'|';
            $subcat_titles[] = $title;
            $subcat_runs++;

            if ($subcat_runs >= 10) {
                $subcat_sumcount += $this->fetchSubcategoryBatchSum($category, rtrim($category_query, '|'), $subcat_titles);
                $subcat_runs = 0;
                $category_query = '';
                $subcat_titles = [];
                sleep(2);
            }
        }

        if ($subcat_runs > 0 && ! empty($category_query)) {
            $subcat_sumcount += $this->fetchSubcategoryBatchSum($category, rtrim($category_query, '|'), $subcat_titles);
        }

        CategoryCount::updateOrCreate([
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
        ], [
            'count' => $subcat_sumcount,
        ]);
    }

    /**
     * @param  array<int, string>  $subcat_titles
     */
    protected function fetchSubcategoryBatchSum(Category $category, string $categoryQuery, array $subcat_titles): int
    {
        $titles = rawurlencode($categoryQuery);
        $subcatResponse = Http::get($category->site->url.'w/api.php?action=query&prop=categoryinfo&titles='.$titles.'&format=json');

        if (! $subcatResponse->ok()) {
            Log::error('Failed to fetch subcategory batch', [
                'category_id' => $category->id,
                'titles' => $categoryQuery,
                'status' => $subcatResponse->status(),
                'body' => $subcatResponse->body(),
            ]);

            return 0;
        }

        $subcatcount = $subcatResponse->json();
        if (! isset($subcatcount['query']['pages'])) {
            Log::warning('Missing pages key in subcategory batch', [
                'category_id' => $category->id,
                'titles' => $categoryQuery,
                'response' => $subcatcount,
            ]);

            return 0;
        }

        $sum = 0;
        foreach ($subcatcount['query']['pages'] as $page) {
            $apiTitle = str_replace(' ', '_', $page['title'] ?? '');
            if (in_array($apiTitle, $subcat_titles, true)) {
                $sum += $page['categoryinfo']['pages'] ?? 0;
            }
        }

        return $sum;
    }
}
