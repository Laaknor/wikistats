<?php

namespace App\Livewire;

use Livewire\Component;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use App\Models\Category;
use App\Models\CategoryCount;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;

class ShowCategoryGraph extends Component
{
   
    
    public $category;

    public function mount(Category $category)
    {
        $this->category = $category;
    }

    #public function render()
    #{
    #    return view('livewire.show-category-graph');
    #}

    public function render()
    {
        // Validate category exists
        if (!$this->category) {
            return view('livewire.show-category-graph', ['graph' => null, 'error' => 'Category not found']);
        }

        // Get date range, with fallback if no data exists
        $minDate = CategoryCount::min('date');
        if (!$minDate) {
            // No data available, create empty chart
            $chart = Chartjs::build()
                ->name("Category_".$this->category->name."_Count")
                ->type("line")
                ->size(["width" => 400, "height" => 300])
                ->labels([])
                ->datasets([
                    [
                        "label" => "CategoryCount",
                        "backgroundColor" => "rgba(38, 185, 154, 0.31)",
                        "borderColor" => "rgba(38, 185, 154, 0.7)",
                        "data" => []
                    ]
                ]);
            
            return view('livewire.show-category-graph', compact('chart'));
        }

        $start = Carbon::parse($minDate);
        $end = now();
        $period = CarbonPeriod::create($start, "1 month", $end);

        $categoryCountPerMonth = collect($period)->map(function ($date) {
            $endDate = $date->copy()->endOfWeek();
            $startDate = $date->copy()->startOfWeek();

            $avgCount = CategoryCount::where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->where('category_id', $this->category->id)
                ->avg('count');

            return [
                "count" => $avgCount ?? 0, // Handle null values
                "month" => $endDate->format("Y-m-d")
            ];
        });
        
        $data = $categoryCountPerMonth->pluck("count")->toArray();
        $labels = $categoryCountPerMonth->pluck("month")->toArray();

        $graph = Chartjs::build()
            ->name("Category_".$this->category->id."_Count")
            ->type("line")
            ->size(["width" => 400, "height" => 300])
            ->labels($labels)
            ->datasets([
                [
                    "label" => $this->category->name,
                    "backgroundColor" => "rgba(38, 185, 154, 0.31)",
                    "borderColor" => "rgba(38, 185, 154, 0.7)",
                    "data" => $data
                ]
            ])
            ->options([
                'scales' => [
                    'x' => [
                        'type' => 'time',
                        'time' => [
                            'unit' => 'month'
                        ],
                        'min' => $start->format("Y-m-d"),
                    ]
                ],
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Monthly '.$this->category->name.' Count'
                    ]
                ]
            ]);

        // Validate chart was created successfully
        if (!$graph) {
            
            return view('livewire.show-category-graph', ['chart' => null, 'error' => 'Failed to create chart']);
        }
        #dd($chart);
        #dd($this->category);
        return view('livewire.show-category-graph', compact('graph'));
    }
}
