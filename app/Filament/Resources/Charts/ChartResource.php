<?php

namespace App\Filament\Resources\Charts;

use App\Filament\Resources\Charts\Pages\CreateChart;
use App\Filament\Resources\Charts\Pages\EditChart;
use App\Filament\Resources\Charts\Pages\ListCharts;
use App\Filament\Resources\Charts\RelationManagers\CategoriesRelationManager;
use App\Filament\Resources\Charts\RelationManagers\TrackingsRelationManager;
use App\Models\Chart;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChartResource extends Resource
{
    protected static ?string $model = Chart::class;

    public static function canAccess(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('site_id')
                    ->relationship('site', 'hostname')
                    ->searchable()
                    ->preload()
                    ->placeholder('All wikis (template)')
                    ->helperText('Leave empty to show this chart on every wiki using the trackings below.'),
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, callable $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                \Filament\Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        function (?Chart $record) {
                            $sid = $record?->site_id ?? request()->input('site_id');
                            $rule = Rule::unique('charts', 'slug')->ignore($record);
                            if ($sid === null || $sid === '') {
                                return $rule->whereNull('site_id');
                            }
                            return $rule->where('site_id', $sid);
                        },
                    ])
                    ->helperText('Unique per site (or unique among templates if "All wikis"). Used in the chart URL.'),
                \Filament\Forms\Components\Select::make('group')
                    ->options([
                        'maintenance' => 'Maintenance',
                        'content' => 'Content',
                    ])
                    ->placeholder('Other')
                    ->helperText('Tab on the site page where this chart appears (when it has categories).'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('site.hostname')
                    ->sortable()
                    ->searchable()
                    ->placeholder('All wikis'),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('series_source')
                    ->label('Series')
                    ->formatStateUsing(fn (Chart $record) => $record->trackings()->count() > 0
                        ? $record->trackings()->count() . ' trackings'
                        : $record->categories()->count() . ' categories'),
            ])
            ->defaultSort('site_id')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TrackingsRelationManager::class,
            CategoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharts::route('/'),
            'create' => CreateChart::route('/create'),
            'edit' => EditChart::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }
}
