<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\ChartWidget;

class RequestTypeCountChart extends ChartWidget
{
    protected ?string $heading = 'Request Type Count Distribution';

    public ?ApiTest $record = null;

    protected function getData(): array
    {
        $queries = Query::all();
        $apiTypes = [ApiType::Rest, ApiType::Graphql];

        $data = [];
        foreach ($apiTypes as $apiType) {
            foreach ($queries as $query) {
                $data[$apiType->value][] = $query->testResults()
                    ->where('api_test_id', $this->record->id)
                    ->where('api_type', ApiType::Integrated)
                    ->where('request_type', $apiType)
                    ->count();
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
            ]
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
