<?php

namespace App\Filament\Resources;

use App\Enums\ApiType;
use App\Filament\Resources\ApiTestResource\Pages;
use App\Filament\Resources\ApiTestResource\RelationManagers\ResultsRelationManager;
use App\Filament\Resources\ApiTestResource\Widgets\QueryResponseTimeComparisonChart;
use App\Models\ApiTest;
use App\Models\Query;
use BackedEnum;
use Carbon\CarbonInterval;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ApiTestResource extends Resource
{
    protected static ?string $model = ApiTest::class;

    protected static ?string $slug = 'api-tests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Test Details')
                    ->description('Test details and statistics')
                    ->aside()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('Test ID'),

                        TextEntry::make('count')
                            ->label('Total Iterasi'),

                        TextEntry::make('duration')
                            ->label('Durasi')
                            ->formatStateUsing(fn($state) => CarbonInterval::seconds($state)->cascade()->forHumans()),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('results.count')
                            ->label('Total Request')
                            ->state(
                                fn(ApiTest $record) => $record->count * Query::count() * count(ApiType::values())
                            ),

                        TextEntry::make('created_at')
                            ->label('Test Mulai'),

//                        TextEntry::make('completed_at')
//                            ->label('Test Selesai'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Test ID'),

                TextColumn::make('count')
                    ->label('Total Iterasi'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('duration')
                    ->label('Durasi')
                    ->formatStateUsing(fn($state) => CarbonInterval::seconds($state)->cascade()->forHumans()),

                TextColumn::make('created_at')
                    ->label('Test Mulai')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
//                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiTests::route('/'),
//            'create' => Pages\CreateApiTest::route('/create'),
            'view' => Pages\ViewApiTest::route('/{record}'),
            'edit' => Pages\EditApiTest::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ResultsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            QueryResponseTimeComparisonChart::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
