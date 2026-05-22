<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sync' => 'datetime',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function wikidata_tracking()
    {
        return $this->belongsTo(WikidataTracking::class);
    }

    public function category_counts()
    {
        return $this->hasMany(CategoryCount::class);
    }

    public function charts()
    {
        return $this->belongsToMany(Chart::class, 'chart_category')
            ->withPivot(['sort_order', 'label', 'color']);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeDueForSync(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('last_sync', '<', now()->subDays(7));
    }

    /**
     * Title for MediaWiki API requests (decoded, underscores).
     */
    public function wikiApiTitle(): string
    {
        return str_replace(' ', '_', rawurldecode($this->name));
    }

    /**
     * @param  array<string, mixed>  $page
     */
    public static function pageIsMissingOnWiki(array $page): bool
    {
        return ! isset($page['pageid']);
    }

    /**
     * Mark category as deleted on the wiki; stop future sync attempts.
     */
    public function markDeletedOnWiki(): void
    {
        $this->is_active = false;
        $this->last_sync = now();
        $this->save();
    }
}
