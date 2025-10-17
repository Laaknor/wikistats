<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    /**
     * Display the about page.
     */
    public function about()
    {
        return view('about');
    }
}
