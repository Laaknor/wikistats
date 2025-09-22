<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class CategoryCountChart extends ChartWidget
{
    public $category;
    protected ?string $heading = 'Category Count';


    protected function getData(): array
    {
        #dd($this->category->category_counts->pluck('count'));
        return ['datasets' => [
            'label' => 'Label',
            'data' => [1,2,3,4,5],
        ],
        'labels' => ['Label 1', 'Label 2', 'Label 3', 'Label 4', 'Label 5'],
    ];
        return [
            'datasets' => [
                'label' => $this->category->name,
                'data' => $this->category->category_counts->pluck('count'),
            ],
            'labels' => $this->category->category_counts->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

}
