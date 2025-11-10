<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AvgResponseTimeByQueryChart;
use App\Filament\Widgets\ResponseTimeByTestChart;
use App\Models\Query;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public ?string $filter = 'all';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('query_id')
                    ->label('Query')
                    ->options(function () {
                        return Query::pluck('name', 'id')->toArray();
                    })
                    ->placeholder('All')
            ]);
    }

    public function getWidgets(): array
    {
        return [
            ResponseTimeByTestChart::class,
            AvgResponseTimeByQueryChart::class,
        ];
    }
}
