<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\CategoryCount;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use IcehouseVentures\LaravelChartjs\Facades\Chartjs;

class GraphController extends Controller
{
    /**
     * Show large graph for a category
     */
    public function show(Category $category)
    {
        $graph = $this->buildChart($category, 'large');
        if (!$graph) {
            return view('graph.show', ['graph' => null, 'error' => 'Failed to create chart']);
        }
        return view('graph.show', compact('graph'));
    }

    /**
     * Show small graph for a category
     */
    public function showSmall(Category $category)
    {
        $chart = $this->buildChart($category, 'small');
        if (!$chart) {
            return view('graph.small', ['chart' => null, 'error' => 'Failed to create chart']);
        }
        return view('graph.small', compact('chart'));
    }

    /**
     * Generate small chart as image
     */
    public function showSmallImage(Category $category)
    {
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
}

