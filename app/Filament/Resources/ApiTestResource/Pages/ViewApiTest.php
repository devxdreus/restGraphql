<?php

namespace App\Filament\Resources\ApiTestResource\Pages;

use App\Filament\Resources\ApiTestResource;
use App\Filament\Resources\ApiTestResource\Widgets\ResponseTimeChart;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApiTest extends ViewRecord
{
    protected static string $resource = ApiTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResponseTimeChart::class,
        ];
    }
}
