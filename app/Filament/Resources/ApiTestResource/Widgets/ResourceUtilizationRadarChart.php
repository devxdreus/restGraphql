<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\ChartWidget;

class ResourceUtilizationRadarChart extends ChartWidget
{
    protected ?string $heading = 'Resource Utilization Comparison';

    public ?ApiTest $record = null;

    public ?string $filter = '1';

    protected function getData(): array
    {
        $results = $this->record->results()->success()->where('query_id', $this->filter);

        $restStats = $results->clone()->rest()->selectRaw('
            AVG(response_time) as avg_response_time,
            AVG(mem_usage) as avg_mem_usage,
            AVG(cpu_usage) as avg_cpu_usage,
            AVG(payload_size) as avg_payload_size
        ')->first();

        $graphqlStats = $results->clone()->graphql()->selectRaw('
            AVG(response_time) as avg_response_time,
            AVG(mem_usage) as avg_mem_usage,
            AVG(cpu_usage) as avg_cpu_usage,
            AVG(payload_size) as avg_payload_size
        ')->first();

        $integratedStats = $results->clone()->integrated()->selectRaw('
            AVG(response_time) as avg_response_time,
            AVG(mem_usage) as avg_mem_usage,
            AVG(cpu_usage) as avg_cpu_usage,
            AVG(payload_size) as avg_payload_size
        ')->first();

        // Normalisasi data untuk radar chart (scale 0-100)
        $maxResponseTime = max($restStats->avg_response_time, $graphqlStats->avg_response_time, $integratedStats->avg_response_time);
        $maxMemUsage = max($restStats->avg_mem_usage, $graphqlStats->avg_mem_usage, $integratedStats->avg_mem_usage);
        $maxCpuUsage = max($restStats->avg_cpu_usage, $graphqlStats->avg_cpu_usage, $integratedStats->avg_cpu_usage);
        $maxPayloadSize = max($restStats->avg_payload_size, $graphqlStats->avg_payload_size, $integratedStats->avg_payload_size);

        return [
            'datasets' => [
                [
                    'label' => 'Rest',
                    'data' => [
                        ($restStats->avg_response_time / $maxResponseTime) * 100,
                        ($restStats->avg_mem_usage / $maxMemUsage) * 100,
                        ($restStats->avg_cpu_usage / $maxCpuUsage) * 100,
                        ($restStats->avg_payload_size / $maxPayloadSize) * 100,
                    ],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'GraphQL',
                    'data' => [
                        ($graphqlStats->avg_response_time / $maxResponseTime) * 100,
                        ($graphqlStats->avg_mem_usage / $maxMemUsage) * 100,
                        ($graphqlStats->avg_cpu_usage / $maxCpuUsage) * 100,
                        ($graphqlStats->avg_payload_size / $maxPayloadSize) * 100,
                    ],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Integrated',
                    'data' => [
                        ($integratedStats->avg_response_time / $maxResponseTime) * 100,
                        ($integratedStats->avg_mem_usage / $maxMemUsage) * 100,
                        ($integratedStats->avg_cpu_usage / $maxCpuUsage) * 100,
                        ($integratedStats->avg_payload_size / $maxPayloadSize) * 100,
                    ],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                ]
            ],
            'labels' => ['Response Time', 'Memory Usage', 'CPU Usage', 'Payload Size'],
        ];
    }

    protected function getType(): string
    {
        return 'radar';
    }

    protected function getFilters(): ?array
    {
        return Query::all()->pluck('name', 'id')->toArray();
    }
}
