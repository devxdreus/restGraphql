<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiTestResource\Pages;
use App\Filament\Resources\ApiTestResource\RelationManagers\ResultsRelationManager;
use App\Filament\Resources\ApiTestResource\Widgets\ResponseTimeChart;
use App\Models\ApiTest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ApiTestResource extends Resource
{
    protected static ?string $model = ApiTest::class;

    protected static ?string $slug = 'api-tests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('count')
                    ->required(),

                TextInput::make('status')
                    ->required(),

                DatePicker::make('completed_at')
                    ->label('Completed Date'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn(?ApiTest $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn(?ApiTest $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('count'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('duration')
                    ->formatStateUsing(fn($state) => $state . ' seconds'),

                TextColumn::make('created_at')
                    ->dateTime(),

                TextColumn::make('completed_at')
                    ->dateTime(),
            ])
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
            ResponseTimeChart::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
