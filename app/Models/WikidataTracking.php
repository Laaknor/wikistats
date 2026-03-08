<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikidataTracking extends Model
{
    protected $guarded = [];

    public function categories()
    {
        return $this->hasMany(Category::class, 'wikidata_tracking_id');
    }

    public function charts()
    {
        return $this->belongsToMany(Chart::class, 'chart_wikidata_tracking')
            ->withPivot(['sort_order', 'label', 'color']);
    }
}
