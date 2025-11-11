<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\ChartWidget;

class ResponseTimeSumamryChart extends ChartWidget
{
    protected ?string $heading = 'Response Time Chart';

    public ?ApiTest $record = null;

    protected function getData(): array
    {
        $queries = Query::all();
        $apiTypes = ApiType::values();

        $data = [];
        foreach ($apiTypes as $apiType) {
            foreach ($queries as $query) {
                $data[$apiType][] = (int)$query->testResults()
                    ->success()
                    ->where('api_test_id', $this->record->id)
                    ->where('api_type', $apiType)
                    ->orderBy('query_id')
                    ->avg('response_time');
            }
        }

        $record = $this->record->results()->success()
            ->selectRaw('query_id, ROUND(AVG(response_time)) as avg_response_time')
            ->groupBy('query_id')
            ->orderBy('query_id');
        $rest = $record->clone()->rest()->pluck('avg_response_time');
        $graphql = $record->clone()->graphql()->pluck('avg_response_time');
        $integrated = $record->clone()->integrated()->pluck('avg_response_time');

        $qIds = $record->clone()->pluck('query_id');
        $labels = Query::whereIn('id', $qIds)->pluck('name');

        return [
            'labels' => $queries->pluck('name'),
            'datasets' => [
                [
                    'label' => 'Rest',
                    'data' => $data[ApiType::Rest->value],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $data[ApiType::Graphql->value],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Integrated',
                    'data' => $data[ApiType::Integrated->value],
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
