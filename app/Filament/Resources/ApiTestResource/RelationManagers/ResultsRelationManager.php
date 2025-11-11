<?php

namespace App\Filament\Resources\ApiTestResource\RelationManagers;

use App\Enums\ApiStatusType;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

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
                TextInput::make('payload_size')
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
                    ->sortable()
                    ->searchable(),
                TextColumn::make('api_type')
                    ->label('API')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('request_type')
                    ->label('Request')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payload_size')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . ' bytes')
                    ->sortable(),
                TextColumn::make('response_time')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => $state . 'ms')
                    ->sortable(),
                TextColumn::make('mem_usage')
                    ->numeric(locale: 'id')
                    ->formatStateUsing(fn($state) => Number::format($state) . ' bytes')
                    ->sortable(),
                TextColumn::make('cpu_usage')
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
            ->poll('3s')
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('query_id')
                    ->label('Query')
                    ->relationship('queryModel', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                SelectFilter::make('api_type')
                    ->label('API Type')
                    ->options(ApiType::class)
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ApiStatusType::class)
                    ->multiple()
                    ->searchable(),
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
