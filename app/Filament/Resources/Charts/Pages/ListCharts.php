<?php

namespace App\Filament\Resources\Charts\Pages;

use App\Filament\Resources\Charts\ChartResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCharts extends ListRecords
{
    protected static string $resource = ChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
