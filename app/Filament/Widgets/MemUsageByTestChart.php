<?php

namespace App\Filament\Widgets;

use App\Models\ApiTest;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MemUsageByTestChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Mem Usage By Test Chart';

    protected function getData(): array
    {
        $apiTests = ApiTest::withAvg(['results as rest_avg' => function ($query) {
            $query->where('api_type', 'rest');
            if (!empty($this->pageFilters['query_id'])) {
                $query->where('query_id', $this->pageFilters['query_id']);
            }
        }], 'mem_usage')
            ->withAvg(['results as graphql_avg' => function ($query) {
                $query->where('api_type', 'graphql');
                if (!empty($this->pageFilters['query_id'])) {
                    $query->where('query_id', $this->pageFilters['query_id']);
                }
            }], 'mem_usage')
            ->withAvg(['results as integrated_avg' => function ($query) {
                $query->where('api_type', 'integrated');
                if (!empty($this->pageFilters['query_id'])) {
                    $query->where('query_id', $this->pageFilters['query_id']);
                }
            }], 'mem_usage')
            ->orderBy('id')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Rest',
                    'data' => $apiTests->pluck('rest_avg')->toArray(),
//                    'backgroundColor' => Color::Teal[400],
                    'borderColor' => Color::Teal[400],
                    'borderWidth' => 1,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $apiTests->pluck('graphql_avg')->toArray(),
//                    'backgroundColor' => Color::self::Blue[400],
                    'borderColor' => Color::Blue[400],
                    'borderWidth' => 1,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Integrated',
                    'data' => $apiTests->pluck('integrated_avg')->toArray(),
//                    'backgroundColor' => Color::self::self::Red[400],
                    'borderColor' => Color::Red[400],
                    'borderWidth' => 1,
                    'tension' => 0.3,
                ]
            ],
            'labels' => $apiTests->pluck('id')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
