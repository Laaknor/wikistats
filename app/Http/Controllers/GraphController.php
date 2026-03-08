<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Chart;
use App\Models\CategoryCount;
use App\Models\Site;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;
use Illuminate\Support\Collection;

class GraphController extends Controller
{
    /** Default colors for multiple series (Chart.js rgba or hex) */
    private const CHART_COLORS = [
        ['bg' => 'rgba(38, 185, 154, 0.31)', 'border' => 'rgba(38, 185, 154, 0.7)'],
        ['bg' => 'rgba(59, 130, 246, 0.31)', 'border' => 'rgba(59, 130, 246, 0.7)'],
        ['bg' => 'rgba(245, 158, 11, 0.31)', 'border' => 'rgba(245, 158, 11, 0.7)'],
        ['bg' => 'rgba(239, 68, 68, 0.31)', 'border' => 'rgba(239, 68, 68, 0.7)'],
        ['bg' => 'rgba(139, 92, 246, 0.31)', 'border' => 'rgba(139, 92, 246, 0.7)'],
    ];

    /**
     * Show large graph for a category
     */
    public function show(Site $site, $graph)
    {
        // Handle both single and double URL encoding
        $decodedGraph = urldecode($graph);
        $category = Category::where('site_id', $site->id)
            ->where(function ($q) use ($decodedGraph, $graph) {
                $q->where('name', $decodedGraph)
                  ->orWhere('name', $graph); // Also try the original in case it's not encoded
            })
            ->firstOrFail();
        
        $graph = $this->buildChart($category, 'large');
        if (!$graph) {
            return view('graph.show', ['graph' => null, 'error' => 'Failed to create chart']);
        }
        return view('graph.show', compact('graph'));
    }

    /**
     * Show large graph for a defined chart (multiple categories combined).
     * Chart can be per-site or a template (site_id null) using WikidataTrackings; series resolved for this site.
     */
    public function showChart(Site $site, string $chartSlug)
    {
        $chart = Chart::where('slug', $chartSlug)
            ->where(fn ($q) => $q->where('site_id', $site->id)->orWhereNull('site_id'))
            ->with(['categories', 'trackings'])
            ->firstOrFail();

        $categories = $chart->getCategoriesForSite($site);
        if ($categories->isEmpty()) {
            return view('graph.show', ['graph' => null, 'error' => 'This chart has no data for this wiki.']);
        }

        $graph = $this->buildChartFromCategories($chart->name, $categories, 'large');
        if (!$graph) {
            return view('graph.show', ['graph' => null, 'error' => 'Failed to create chart']);
        }
        return view('graph.show', compact('graph'));
    }

    /**
     * Show small graph for a defined chart (for embedding on site page).
     */
    public function showSmallChart(Site $site, string $chartSlug)
    {
        $chart = Chart::where('slug', $chartSlug)
            ->where(fn ($q) => $q->where('site_id', $site->id)->orWhereNull('site_id'))
            ->with(['categories', 'trackings'])
            ->firstOrFail();

        $categories = $chart->getCategoriesForSite($site);
        if ($categories->isEmpty()) {
            return view('graph.small', ['chart' => null, 'error' => 'This chart has no data for this wiki.']);
        }

        $chartObj = $this->buildChartFromCategories($chart->name, $categories, 'small');
        if (!$chartObj) {
            return view('graph.small', ['chart' => null, 'error' => 'Failed to create chart']);
        }
        return view('graph.small', ['chart' => $chartObj]);
    }

    /**
     * Show small graph for a category
     */
    public function showSmall(Site $site, $graph)
    {
        // Handle both single and double URL encoding
        $decodedGraph = urldecode($graph);
        $category = Category::where('site_id', $site->id)
            ->where(function ($q) use ($decodedGraph, $graph) {
                $q->where('name', $decodedGraph)
                  ->orWhere('name', $graph); // Also try the original in case it's not encoded
            })
            ->firstOrFail();
        
        $chart = $this->buildChart($category, 'small');
        if (!$chart) {
            return view('graph.small', ['chart' => null, 'error' => 'Failed to create chart']);
        }
        return view('graph.small', compact('chart'));
    }

    /**
     * Generate small chart as image
     */
    public function showSmallImage(Site $site, $graph)
    {
        // Handle both single and double URL encoding
        $decodedGraph = urldecode($graph);
        $category = Category::where('site_id', $site->id)
            ->where(function ($q) use ($decodedGraph, $graph) {
                $q->where('name', $decodedGraph)
                  ->orWhere('name', $graph); // Also try the original in case it's not encoded
            })
            ->firstOrFail();
        // For now, create a simple placeholder image
        // In production, you would use a library like Puppeteer, wkhtmltopdf, or similar to convert charts to images
        
        // Create a simple chart-like image using GD
        $width = 300;
        $height = 120;
        $image = imagecreate($width, $height);
        
        // Set colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 156, 163, 175);
        $blue = imagecolorallocate($image, 38, 185, 154);
        $darkGray = imagecolorallocate($image, 107, 114, 128);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Add border
        imagerectangle($image, 0, 0, $width-1, $height-1, $gray);
        
        // Get some data for the chart
        $minDate = CategoryCount::where('category_id', $category->id)->min('date');
        if ($minDate) {
            // Get some sample data points
            $dataPoints = [];
            $start = Carbon::parse($minDate);
            $end = now();
            $period = CarbonPeriod::create($start, "1 month", $end);
            
            $i = 0;
            foreach ($period as $date) {
                if ($i >= 10) break; // Limit to 10 points for small chart
                
                $endDate = $date->copy()->endOfMonth();
                $startDate = $date->copy()->startOfMonth();
                
                $avgCount = CategoryCount::where('date', '>=', $startDate)
                    ->where('date', '<=', $endDate)
                    ->where('category_id', $category->id)
                    ->avg('count');
                
                if ($avgCount !== null) {
                    $dataPoints[] = round($avgCount);
                }
                $i++;
            }
            
            if (!empty($dataPoints)) {
                // Draw simple line chart
                $maxValue = max($dataPoints);
                $minValue = min($dataPoints);
                $range = $maxValue - $minValue;
                if ($range == 0) $range = 1;
                
                $padding = 20;
                $chartWidth = $width - 2 * $padding;
                $chartHeight = $height - 2 * $padding;
                
                // Draw line
                $prevX = null;
                $prevY = null;
                foreach ($dataPoints as $index => $value) {
                    $x = $padding + ($index * $chartWidth / (count($dataPoints) - 1));
                    $y = $padding + $chartHeight - (($value - $minValue) * $chartHeight / $range);
                    
                    if ($prevX !== null && $prevY !== null) {
                        imageline($image, $prevX, $prevY, $x, $y, $blue);
                    }
                    
                    // Draw point
                    imagefilledellipse($image, $x, $y, 4, 4, $blue);
                    
                    $prevX = $x;
                    $prevY = $y;
                }
            } else {
                // No data message
                $text = "No Data";
                imagestring($image, 3, $width/2 - 20, $height/2 - 10, $text, $darkGray);
            }
        } else {
            // No data message
            $text = "No Data";
            imagestring($image, 3, $width/2 - 20, $height/2 - 10, $text, $darkGray);
        }
        
        // Output image
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }

    /**
     * Build chart based on size (small or large)
     */
    private function buildChart(Category $category, $size = 'large')
    {
        // Get date range, with fallback if no data exists
        $minDate = CategoryCount::where('category_id',$category->id)->min('date');
        
        // Set chart dimensions based on size
        $chartSize = $size === 'small' 
            ? ["width" => 300, "height" => 120] 
            : ["width" => 800, "height" => 400];
            
        $chartName = $size === 'small' 
            ? "Category_".$category->id."_Small" 
            : "Category_".$category->id."_Large";
            
        if (!$minDate) {
            // No data available, create empty chart
            $chart = Chartjs::build()
                ->name($chartName)
                ->type("line")
                ->size($chartSize)
                ->labels([])
                ->datasets([
                    [
                        "label" => "No Data",
                        "backgroundColor" => "rgba(156, 163, 175, 0.2)",
                        "borderColor" => "rgba(156, 163, 175, 0.8)",
                        "data" => [],
                    ]
                ]);
            
            return $chart;
        }

        $start = Carbon::parse($minDate);
        $end = now();
        $period = CarbonPeriod::create($start, "1 month", $end);

        $categoryCountPerMonth = collect($period)->map(function ($date) use ($category) {
            $endDate = $date->copy()->endOfMonth();
            $startDate = $date->copy()->startOfMonth();

            $avgCount = CategoryCount::where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->where('category_id', $category->id)
                ->avg('count');

            // Only include months that have data (avgCount is not null)
            if ($avgCount !== null) {
                return [
                    "count" => round($avgCount),
                    "month" => $endDate->format("Y-m")
                ];
            }
            return null; // Return null for months with no data
        })->filter(); // Remove null entries (months with no data)
        
        $data = $categoryCountPerMonth->pluck("count")->toArray();
        $labels = $categoryCountPerMonth->pluck("month")->toArray();

        // Configure chart options based on size
        $chartOptions = [];
        if ($size === 'small') {
            // Small chart - minimal styling
            $chartOptions = [
                'plugins' => [
                    'legend' => ['display' => false],
                    'title' => ['display' => false]
                ],
                'scales' => [
                    'x' => ['display' => false],
                    'y' => ['display' => false]
                ],
                'elements' => [
                    'point' => ['radius' => 1]
                ]
            ];
        } else {
            // Large chart - full styling
            $chartOptions = [
                'scales' => [
                    'x' => [
                        'type' => 'time',
                        'time' => [
                            'unit' => 'month'
                        ],
                        'min' => $start->format("Y-m-d"),
                    ],
                    'y' => [
                        'min' => 0,
                    ]
                ],
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Monthly '.$category->display_name.' Count'
                    ]
                ]
            ];
        }

        $chart = Chartjs::build()
            ->name($chartName)
            ->type("line")
            ->size($chartSize)
            ->labels($labels)
            ->datasets([
                [
                    "label" => $category->display_name,
                    "backgroundColor" => "rgba(38, 185, 154, 0.31)",
                    "borderColor" => "rgba(38, 185, 154, 0.7)",
                    "data" => $data,
                    "spanGaps" => true,
                    "borderWidth" => $size === 'small' ? 1 : 2,
                    "pointRadius" => $size === 'small' ? 1 : 3
                ]
            ])
            ->options($chartOptions);

        // Validate chart was created successfully
        if (!$chart) {
            return null;
        }

        return $chart;
    }

    /**
     * Build a chart with multiple category series (for Chart model or combo view).
     *
     * @param  string  $title  Chart title (large size only).
     * @param  Collection<int, Category>  $categories  Categories with pivot (label, color) when from Chart.
     */
    private function buildChartFromCategories(string $title, Collection $categories, string $size = 'large')
    {
        $categoryIds = $categories->pluck('id')->toArray();
        $minDate = CategoryCount::whereIn('category_id', $categoryIds)->min('date');
        $chartSize = $size === 'small'
            ? ['width' => 300, 'height' => 120]
            : ['width' => 800, 'height' => 400];
        $chartName = 'Chart_' . $categories->implode('id', '_') . '_' . $size;

        if (!$minDate) {
            return Chartjs::build()
                ->name($chartName)
                ->type('line')
                ->size($chartSize)
                ->labels([])
                ->datasets([[
                    'label' => 'No Data',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.2)',
                    'borderColor' => 'rgba(156, 163, 175, 0.8)',
                    'data' => [],
                ]]);
        }

        $start = Carbon::parse($minDate);
        $end = now();
        $period = CarbonPeriod::create($start, '1 month', $end);
        $allMonths = collect($period)->map(fn ($date) => $date->copy()->endOfMonth()->format('Y-m'))->values()->toArray();

        $datasets = [];
        $index = 0;
        foreach ($categories as $category) {
            $pivot = $category->pivot ?? null;
            $label = $pivot && $pivot->label ? $pivot->label : ($category->display_name ?? $category->name);
            $colorIndex = $index % count(self::CHART_COLORS);
            $colors = self::CHART_COLORS[$colorIndex];
            if ($pivot && $pivot->color) {
                $colors = ['bg' => $pivot->color, 'border' => $pivot->color];
            }

            $monthlyData = [];
            foreach ($period as $date) {
                $endDate = $date->copy()->endOfMonth();
                $startDate = $date->copy()->startOfMonth();
                $avgCount = CategoryCount::where('category_id', $category->id)
                    ->where('date', '>=', $startDate)
                    ->where('date', '<=', $endDate)
                    ->avg('count');
                $monthlyData[$endDate->format('Y-m')] = $avgCount !== null ? round($avgCount) : null;
            }

            $data = array_map(fn ($m) => $monthlyData[$m] ?? null, $allMonths);
            $datasets[] = [
                'label' => $label,
                'backgroundColor' => $colors['bg'],
                'borderColor' => $colors['border'],
                'data' => $data,
                'spanGaps' => true,
                'borderWidth' => $size === 'small' ? 1 : 2,
                'pointRadius' => $size === 'small' ? 1 : 3,
            ];
            $index++;
        }

        $chartOptions = [];
        if ($size === 'small') {
            $chartOptions = [
                'plugins' => ['legend' => ['display' => false], 'title' => ['display' => false]],
                'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
                'elements' => ['point' => ['radius' => 1]],
            ];
        } else {
            $chartOptions = [
                'scales' => [
                    'x' => ['type' => 'time', 'time' => ['unit' => 'month'], 'min' => $start->format('Y-m-d')],
                    'y' => ['min' => 0],
                ],
                'plugins' => [
                    'title' => ['display' => true, 'text' => $title],
                ],
            ];
        }

        return Chartjs::build()
            ->name($chartName)
            ->type('line')
            ->size($chartSize)
            ->labels($allMonths)
            ->datasets($datasets)
            ->options($chartOptions);
    }
}

