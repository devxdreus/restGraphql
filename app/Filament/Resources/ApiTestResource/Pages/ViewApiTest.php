<?php

namespace App\Filament\Resources\ApiTestResource\Pages;

use App\Filament\Resources\ApiTestResource;
use App\Filament\Resources\ApiTestResource\Widgets\CpuUsageByQueryChart;
use App\Filament\Resources\ApiTestResource\Widgets\MemUsageByQueryChart;
use App\Filament\Resources\ApiTestResource\Widgets\ResponseTimeByQueryChart;
use App\Models\Query;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

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
            ResponseTimeByQueryChart::class,
            CpuUsageByQueryChart::class,
            MemUsageByQueryChart::class,
        ];
    }
}
