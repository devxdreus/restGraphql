<?php

namespace App\Filament\Widgets;

use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AvgCpuUsageByQueryChart extends ChartWidget
{
    protected ?string $heading = 'Avg Cpu Usage By Query Chart';

    protected function getData(): array
    {
        $queries = Query::all();

        $labels = $queries->pluck('name')->toArray();

        $restData = $queries->map(
            fn(Query $query) => $query->averageByColumnAndApiType('cpu_usage', 'rest')
        )->toArray();

        $graphqlData = $queries->map(
            fn(Query $query) => $query->averageByColumnAndApiType('cpu_usage', 'graphql')
        )->toArray();

        $integratedData = $queries->map(
            fn(Query $query) => $query->averageByColumnAndApiType('cpu_usage', 'integrated')
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'REST',
                    'data' => $restData,
//                    'backgroundColor' => Color::Teal[300],
                    'borderColor' => Color::Teal[400],
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $graphqlData,
//                    'backgroundColor' => Color::Blue[300],
                    'borderColor' => Color::Blue[400],
                ],
                [
                    'label' => 'Integrated',
                    'data' => $integratedData,
//                    'backgroundColor' => Color::Red[300],
                    'borderColor' => Color::Red[400],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
