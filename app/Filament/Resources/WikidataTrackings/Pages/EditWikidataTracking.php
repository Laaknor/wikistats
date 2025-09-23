<?php

namespace App\Filament\Resources\WikidataTrackings\Pages;

use App\Filament\Resources\WikidataTrackings\WikidataTrackingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWikidataTracking extends EditRecord
{
    protected static string $resource = WikidataTrackingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
