<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveFile extends Model
{
    //
    protected $guarded = [];
    protected $casts = [
        'collection' => 'array',
    ];
}
