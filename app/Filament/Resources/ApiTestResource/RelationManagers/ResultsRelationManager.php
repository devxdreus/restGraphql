<?php

namespace App\Filament\Resources\ApiTestResource\RelationManagers;

use App\Enums\ApiType;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('query_id')
                    ->required()
                    ->numeric(),
                Select::make('preset_id')
                    ->relationship('preset', 'name')
                    ->required(),
                Select::make('api_type')
                    ->options(ApiType::class)
                    ->required(),
                TextInput::make('response')
                    ->required(),
                TextInput::make('payload')
                    ->required()
                    ->numeric(),
                TextInput::make('cpu_usage')
                    ->required()
                    ->numeric(),
                TextInput::make('mem_usage')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('api_test_id')
            ->columns([
                TextColumn::make('queryModel.name')
                    ->label('Query')
                    ->sortable(),
                TextColumn::make('preset.name')
                    ->searchable(),
                TextColumn::make('api_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('payload')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . ' bytes')
                    ->sortable(),
                TextColumn::make('cpu_usage')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->sortable(),
                TextColumn::make('mem_usage')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
//                EditAction::make(),
//                DissociateAction::make(),
//                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
