<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Chart;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $sites = Site::orderBy('family', 'asc')->orderBy('hostname', 'asc')->get();

        return view('sites.index', compact('sites'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Site $site)
    {
        $categories = Category::where('site_id', $site->id)
            ->where('is_active', true)
            ->with('wikidata_tracking')
            ->get();
        $charts = Chart::where('site_id', $site->id)
            ->orWhereNull('site_id')
            ->with(['categories', 'trackings'])
            ->get()
            ->filter(fn (Chart $chart) => $chart->getCategoriesForSite($site)->isNotEmpty());

        $categoriesByGroup = $categories->groupBy(fn ($c) => $c->wikidata_tracking?->group ?? 'other');
        $chartsByGroup = $charts->groupBy(fn ($c) => $c->group ?? 'other');

        $groupOrder = ['maintenance' => 'Maintenance', 'content' => 'Content', 'other' => 'Other'];
        $tabs = collect($groupOrder)->keys()->filter(function ($key) use ($categoriesByGroup, $chartsByGroup) {
            return $categoriesByGroup->get($key, collect())->isNotEmpty()
                || ($chartsByGroup->get($key, collect())->isNotEmpty());
        })->values()->all();

        $activeTab = request()->query('tab');
        if (! in_array($activeTab, $tabs, true)) {
            $activeTab = $tabs[0] ?? null;
        }

        return view('sites.show', compact('site', 'categoriesByGroup', 'chartsByGroup', 'groupOrder', 'tabs', 'activeTab'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        //
    }
}
