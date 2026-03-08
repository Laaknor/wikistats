<?php

namespace App\Filament\Resources\Charts\Pages;

use App\Filament\Resources\Charts\ChartResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChart extends EditRecord
{
    protected static string $resource = ChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
