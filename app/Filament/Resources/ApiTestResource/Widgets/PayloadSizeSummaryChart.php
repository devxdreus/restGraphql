<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\ChartWidget;

class PayloadSizeSummaryChart extends ChartWidget
{
    protected ?string $heading = 'Payload Size Summary Chart';

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
                    ->avg('payload_size');
            }
        }

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
