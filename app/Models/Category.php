<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $guarded = [];
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
}
