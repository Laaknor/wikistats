<?php

namespace App\Filament\Resources\WikidataTrackings;

use App\Filament\Resources\WikidataTrackings\Pages\CreateWikidataTracking;
use App\Filament\Resources\WikidataTrackings\Pages\EditWikidataTracking;
use App\Filament\Resources\WikidataTrackings\Pages\ListWikidataTrackings;
use App\Filament\Resources\WikidataTrackings\Schemas\WikidataTrackingForm;
use App\Filament\Resources\WikidataTrackings\Tables\WikidataTrackingsTable;
use App\Models\WikidataTracking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WikidataTrackingResource extends Resource
{
    protected static ?string $model = WikidataTracking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WikidataTrackingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WikidataTrackingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWikidataTrackings::route('/'),
            'create' => CreateWikidataTracking::route('/create'),
            'edit' => EditWikidataTracking::route('/{record}/edit'),
        ];
    }
}
