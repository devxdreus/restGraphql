<?php

namespace App\Filament\Widgets;

use App\Models\ApiTest;
use App\Models\ApiTestResult;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TestTrendsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Performance Trends Over Tests';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'response_time';

    protected function getData(): array
    {
        $metric = $this->filter;

        $apiTests = ApiTest::withAvg(['results as rest_avg' => function ($query) use ($metric) {
            $query->where('api_type', 'rest')->success();
        }], $metric)
            ->withAvg(['results as graphql_avg' => function ($query) use ($metric) {
                $query->where('api_type', 'graphql')->success();
            }], $metric)
            ->withAvg(['results as integrated_avg' => function ($query) use ($metric) {
                $query->where('api_type', 'integrated')->success();
            }], $metric)
            ->orderBy('id')
            ->take(20)
            ->get();

        $restData = $apiTests->pluck('rest_avg')->map(function ($value) use ($metric) {
            return $this->formatValue($value, $metric);
        })->toArray();

        $graphqlData = $apiTests->pluck('graphql_avg')->map(function ($value) use ($metric) {
            return $this->formatValue($value, $metric);
        })->toArray();

        $integratedData = $apiTests->pluck('integrated_avg')->map(function ($value) use ($metric) {
            return $this->formatValue($value, $metric);
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'REST',
                    'data' => $restData,
                    'borderColor' => Color::Teal[500],
                    'backgroundColor' => Color::Teal[100],
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $graphqlData,
                    'borderColor' => Color::Blue[500],
                    'backgroundColor' => Color::Blue[100],
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Integrated',
                    'data' => $integratedData,
                    'borderColor' => Color::Red[500],
                    'backgroundColor' => Color::Red[100],
                    'fill' => false,
                    'tension' => 0.3,
                ]
            ],
            'labels' => $apiTests->pluck('id')->map(fn($id) => 'Test #' . $id)->toArray(),
        ];
    }

    private function formatValue($value, $metric)
    {
        if (!$value) return 0;

        return match ($metric) {
            'mem_usage', 'payload_size' => round($value / 1024, 2), // Convert to KB
            'cpu_usage' => round($value * 100, 2), // Convert to percentage
            default => round($value, 2)
        };
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'response_time' => 'Response Time (ms)',
            'mem_usage' => 'Memory Usage (KB)',
            'cpu_usage' => 'CPU Usage (%)',
            'payload_size' => 'Payload Size (KB)',
        ];
    }
}
