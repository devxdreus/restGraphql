<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\ChartWidget;

class QueryMemoryUsageComparisonChart extends ChartWidget
{
    protected ?string $heading = 'Mem Usage Chart';

    public ?ApiTest $record = null;

    public ?string $filter = '1';

    protected function getData(): array
    {
        $record = $this->record->results()->orderBy('created_at')->where('query_id', $this->filter);
        $rest = $record->clone()->rest()->pluck('mem_usage');
        $graphql = $record->clone()->graphql()->pluck('mem_usage');
        $integrated = $record->clone()->integrated()->pluck('mem_usage');

        $labels = range(1, $this->record->count);

        return [
            'datasets' => [
                [
                    'label' => 'Rest',
                    'data' => $rest->toArray(),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $graphql->toArray(),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Integrated',
                    'data' => $integrated->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'tension' => 0.3,
                ]
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        return Query::all()->pluck('name', 'id')->toArray();
    }

    protected function getType(): string
    {
        return 'line';
    }
}
