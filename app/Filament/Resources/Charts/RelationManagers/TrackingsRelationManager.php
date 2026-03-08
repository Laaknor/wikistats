<?php

namespace App\Filament\Resources\Charts\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TrackingsRelationManager extends RelationManager
{
    protected static string $relationship = 'trackings';

    protected static ?string $title = 'Trackings (series from Wikidata)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                TextInput::make('label')
                    ->maxLength(255)
                    ->placeholder('Override label on chart'),
                ColorPicker::make('color'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->placeholder(fn ($record) => $record->item)
                    ->label('Tracking'),
                \Filament\Tables\Columns\TextColumn::make('item')
                    ->label('Wikidata item'),
                \Filament\Tables\Columns\TextColumn::make('pivot.sort_order')
                    ->sortable()
                    ->label('Order'),
                \Filament\Tables\Columns\TextColumn::make('pivot.label')
                    ->placeholder('—')
                    ->label('Label'),
                \Filament\Tables\Columns\TextColumn::make('pivot.color')
                    ->placeholder('—')
                    ->label('Color'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Wikidata tracking')
                            ->searchable(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        TextInput::make('label')
                            ->maxLength(255),
                        ColorPicker::make('color'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(fn (Schema $schema): Schema => $this->form($schema)),
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
