<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siteinfo extends Model
{
    //
    protected $guarded = [];
    public $timestamps = false;
    public $autoincrement = false;

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
