<?php

namespace App\Filament\Resources\ApiTestResource\Pages;

use App\Filament\Resources\ApiTestResource;
use App\Services\TestDispatcher;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListApiTests extends ListRecords
{
    protected static string $resource = ApiTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startTest')
                ->modalWidth(Width::Small)
                ->modalSubmitActionLabel('Start')
                ->requiresConfirmation()
                ->schema([
                    TextInput::make('count')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                ])
                ->action(
                    fn(array $data) => TestDispatcher::make()->dispatchTests($data['count'])
                )
        ];
    }
}
