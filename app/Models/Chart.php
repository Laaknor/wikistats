<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Chart extends Model
{
    protected $guarded = [];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'chart_category')
            ->withPivot(['sort_order', 'label', 'color'])
            ->orderByPivot('sort_order');
    }

    public function trackings()
    {
        return $this->belongsToMany(WikidataTracking::class, 'chart_wikidata_tracking')
            ->withPivot(['sort_order', 'label', 'color'])
            ->orderByPivot('sort_order');
    }

    /**
     * Whether this chart is a template (appears on all wikis via trackings).
     */
    public function isTemplate(): bool
    {
        return $this->site_id === null;
    }

    /**
     * Get the categories to use for this chart on a given site.
     * If the chart has trackings, resolves one category per tracking for the site.
     * Otherwise returns directly attached categories (only if chart belongs to this site).
     */
    public function getCategoriesForSite(Site $site): Collection
    {
        $trackings = $this->trackings;

        if ($trackings->isNotEmpty()) {
            $categories = collect();
            foreach ($trackings as $tracking) {
                $category = Category::where('site_id', $site->id)
                    ->where('wikidata_tracking_id', $tracking->id)
                    ->where('is_active', true)
                    ->first();
                if ($category) {
                    $category->setRelation('pivot', (object) [
                        'label' => $tracking->pivot->label ?? null,
                        'color' => $tracking->pivot->color ?? null,
                    ]);
                    $categories->push($category);
                }
            }

            return $categories;
        }

        if ($this->site_id !== null && $this->site_id !== $site->id) {
            return collect();
        }

        return $this->categories;
    }
}
