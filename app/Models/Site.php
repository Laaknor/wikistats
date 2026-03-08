<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $guarded = [];
    //
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function charts()
    {
        return $this->hasMany(Chart::class);
    }
    public static function parseUrl($url) 
    {
        $parsed = parse_url($url);
        $split = explode('.', $parsed['host']);
        if($split[1] == 'wikipedia') {
            $dbname = $split[0].'wiki';
        } else {
            $dbname = $split[0].$split[1];
        }
        return Site::firstOrCreate([
            'language' => $split[0],
            'family' => $split[1],
            'dbname' => $dbname,
            'hostname' => $parsed['host'],
            'url' => $parsed['scheme'].'://'.$parsed['host'].'/',
        ]);
    }
}
