<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\ChartWidget;

class ResponseTimeSumamryChart extends ChartWidget
{
    protected ?string $heading = 'Response Time Chart';

    public ?ApiTest $record = null;

    protected function getData(): array
    {
        $record = $this->record->results()->success()->groupBy('query_id');
        $rest = $record->clone()->rest()->selectRaw('ROUND(AVG(response_time), 2) as avg_response_time')->pluck('avg_response_time');
        $graphql = $record->clone()->graphql()->selectRaw('ROUND(AVG(response_time), 2) as avg_response_time')->pluck('avg_response_time');
        $integrated = $record->clone()->integrated()->selectRaw('ROUND(AVG(response_time), 2) as avg_response_time')->pluck('avg_response_time');

        $qIds = $record->clone()->pluck('query_id');
        $labels = Query::whereIn('id', $qIds)->pluck('name');

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Rest',
                    'data' => $rest,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $graphql,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Integrated',
                    'data' => $integrated,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
