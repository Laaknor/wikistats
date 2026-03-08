<?php

namespace App\Filament\Resources\Charts\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    protected static ?string $title = 'Categories (series)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                TextInput::make('label')
                    ->maxLength(255)
                    ->placeholder('Override display name on chart'),
                TextInput::make('color')
                    ->maxLength(255)
                    ->placeholder('e.g. rgba(38, 185, 154, 0.7)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('display_name')
                    ->placeholder(fn ($record) => $record->name)
                    ->label('Category'),
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
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->where('site_id', $this->getOwnerRecord()->site_id))
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Category')
                            ->searchable(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        TextInput::make('label')
                            ->maxLength(255),
                        TextInput::make('color')
                            ->maxLength(255),
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
