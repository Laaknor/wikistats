<?php

namespace Tests\Feature;

use App\Jobs\GetCategoryCountsJob;
use App\Models\Category;
use App\Models\CategoryCount;
use App\Models\Site;
use App\Models\WikidataTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetCategoryCountsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivates_category_when_wiki_page_is_missing(): void
    {
        $site = Site::create([
            'language' => 'es',
            'family' => 'wiktionary',
            'dbname' => 'eswiktionary',
            'hostname' => 'es.wiktionary.org',
            'url' => 'https://es.wiktionary.org/',
            'enabled' => true,
        ]);

        $tracking = WikidataTracking::create([
            'item' => 'Q123',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
        ]);

        $category = Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => 'Categor%C3%ADa:Wikcionario:Esbozo',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => true,
        ]);

        Http::fake([
            'https://es.wiktionary.org/w/api.php*' => Http::response([
                'query' => [
                    'pages' => [
                        '-1' => [
                            'ns' => 14,
                            'title' => 'Categoría:Wikcionario:Esbozo',
                            'missing' => '',
                        ],
                    ],
                ],
            ]),
        ]);

        (new GetCategoryCountsJob)->handle();

        $category->refresh();

        $this->assertFalse($category->is_active);
        $this->assertTrue($category->last_sync->isToday());
        $this->assertSame(0, CategoryCount::where('category_id', $category->id)->count());
    }

    public function test_syncs_active_category_with_valid_wiki_response(): void
    {
        $site = Site::create([
            'language' => 'en',
            'family' => 'wikipedia',
            'dbname' => 'enwiki',
            'hostname' => 'en.wikipedia.org',
            'url' => 'https://en.wikipedia.org/',
            'enabled' => true,
        ]);

        $tracking = WikidataTracking::create([
            'item' => 'Q456',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
        ]);

        $category = Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => 'Category:Test_category',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => true,
        ]);

        Http::fake([
            'https://en.wikipedia.org/w/api.php*' => Http::response([
                'query' => [
                    'pages' => [
                        '42' => [
                            'pageid' => 42,
                            'ns' => 14,
                            'title' => 'Category:Test category',
                            'categoryinfo' => [
                                'pages' => 7,
                                'subcats' => 1,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        (new GetCategoryCountsJob)->handle();

        $category->refresh();

        $this->assertTrue($category->is_active);
        $this->assertSame(42, $category->mw_category_id);
        $this->assertSame('Category:Test category', $category->display_name);
        $this->assertTrue($category->last_sync->isToday());
        $this->assertDatabaseHas('category_counts', [
            'category_id' => $category->id,
            'count' => 7,
        ]);
    }

    public function test_skips_inactive_categories(): void
    {
        $site = Site::create([
            'language' => 'en',
            'family' => 'wikipedia',
            'dbname' => 'enwiki',
            'hostname' => 'en.wikipedia.org',
            'url' => 'https://en.wikipedia.org/',
            'enabled' => true,
        ]);

        $tracking = WikidataTracking::create([
            'item' => 'Q789',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
        ]);

        Category::create([
            'site_id' => $site->id,
            'wikidata_tracking_id' => $tracking->id,
            'name' => 'Category:Deleted',
            'type' => 'categorycount',
            'last_sync' => now()->subDays(30),
            'is_active' => false,
        ]);

        Http::fake();

        (new GetCategoryCountsJob)->handle();

        Http::assertNothingSent();
    }
}
