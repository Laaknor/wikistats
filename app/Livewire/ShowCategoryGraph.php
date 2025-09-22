<?php

namespace App\Livewire;

use Livewire\Component;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use App\Models\Category;
use App\Models\CategoryCount;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
class ShowCategoryGraph extends Component
{
    public $ChartModel;

    public function mount()
    {

        
    }

    public function render()
    {
        return view('livewire.show-category-graph');
    }

    public function showChart()
    {
        $start = Carbon::parse(CategoryCount::min('created_at'));
        $end = now();
        $period = CarbonPeriod::create($start, "1 day", $end);

        $
    }
}
