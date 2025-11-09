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
use Filament\Forms\Components\Textarea;
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
                TextInput::make('id')
                    ->hiddenOn('create'),
                TextInput::make('query_id')
                    ->required()
                    ->numeric(),
                Select::make('preset_id')
                    ->relationship('preset', 'name')
                    ->required(),
                Select::make('api_type')
                    ->options(ApiType::class)
                    ->required(),
                Select::make('request_type')
                    ->options(ApiType::class)
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
                Textarea::make('response')
                    ->required()
                    ->columnSpanFull()
                    ->formatStateUsing(fn($state) => json_encode($state, JSON_PRETTY_PRINT))
                    ->rows(8),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('api_type')
            ->columns([
                TextColumn::make('queryModel.name')
                    ->label('Query')
                    ->sortable(),
                TextColumn::make('api_type')
                    ->label('API')
                    ->badge()
                    ->searchable(),
//                TextColumn::make('request_type')
//                    ->label('Request')
//                    ->badge()
//                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payload')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . ' bytes')
                    ->sortable(),
                TextColumn::make('response_time')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . 'ms')
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
//                CreateAction::make(),
//                AssociateAction::make(),
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
