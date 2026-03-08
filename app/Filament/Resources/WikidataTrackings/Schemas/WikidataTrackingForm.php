<?php

namespace App\Filament\Resources\WikidataTrackings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WikidataTrackingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('item')
                    ->required(),
                Select::make('type')
                    ->required()
                    ->options([
                        'categorycount' => 'Category Count',
                    ]),
                Select::make('group')
                    ->options([
                        'maintenance' => 'Maintenance',
                        'content' => 'Content',
                    ])
                    ->placeholder('Other')
                    ->helperText('Used to group categories into tabs on the site page (Maintenance / Content).'),
                TextInput::make('name')
                    ->default(null),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
