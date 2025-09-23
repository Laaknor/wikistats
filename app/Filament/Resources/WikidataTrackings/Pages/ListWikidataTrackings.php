<?php

namespace App\Filament\Resources\WikidataTrackings\Pages;

use App\Filament\Resources\WikidataTrackings\WikidataTrackingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWikidataTrackings extends ListRecords
{
    protected static string $resource = WikidataTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
