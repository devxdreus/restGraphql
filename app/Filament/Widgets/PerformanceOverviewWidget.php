<?php

namespace App\Filament\Widgets;

use App\Models\ApiTestResult;
use Filament\Support\Numbers\NumberFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerformanceOverviewWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $results = ApiTestResult::query()->success();

        // Response Time
        $restAvgResponseTime = $results->clone()->rest()->avg('response_time');
        $graphqlAvgResponseTime = $results->clone()->graphql()->avg('response_time');
        $integratedAvgResponseTime = $results->clone()->integrated()->avg('response_time');

        // Memory Usage
        $restAvgMemUsage = $results->clone()->rest()->avg('mem_usage');
        $graphqlAvgMemUsage = $results->clone()->graphql()->avg('mem_usage');
        $integratedAvgMemUsage = $results->clone()->integrated()->avg('mem_usage');

        // CPU Usage
        $restAvgCpuUsage = $results->clone()->rest()->avg('cpu_usage');
        $graphqlAvgCpuUsage = $results->clone()->graphql()->avg('cpu_usage');
        $integratedAvgCpuUsage = $results->clone()->integrated()->avg('cpu_usage');

        // Payload Size
        $restAvgPayloadSize = $results->clone()->rest()->avg('payload_size');
        $graphqlAvgPayloadSize = $results->clone()->graphql()->avg('payload_size');
        $integratedAvgPayloadSize = $results->clone()->integrated()->avg('payload_size');

        // Determine best performer for each metric (lower is better)
        $bestResponseTime = min($restAvgResponseTime, $graphqlAvgResponseTime, $integratedAvgResponseTime);
        $bestMemUsage = min($restAvgMemUsage, $graphqlAvgMemUsage, $integratedAvgMemUsage);
        $bestCpuUsage = min($restAvgCpuUsage, $graphqlAvgCpuUsage, $integratedAvgCpuUsage);
        $bestPayloadSize = min($restAvgPayloadSize, $graphqlAvgPayloadSize, $integratedAvgPayloadSize);

        return [
            // Response Time
            Stat::make('Avg Response Time', 'Comparison')
                ->description(
                    'REST: ' . number_format($restAvgResponseTime, 0) . 'ms | ' .
                    'GraphQL: ' . number_format($graphqlAvgResponseTime, 0) . 'ms | ' .
                    'Integrated: ' . number_format($integratedAvgResponseTime, 0) . 'ms'
                )
                ->descriptionIcon('heroicon-o-clock')
                ->color($this->getColorForValue($integratedAvgResponseTime, $bestResponseTime)),

            // Memory Usage
            Stat::make('Avg Memory Usage', 'Comparison')
                ->description(
                    'REST: ' . number_format($restAvgMemUsage / 1024, 2) . 'KB | ' .
                    'GraphQL: ' . number_format($graphqlAvgMemUsage / 1024, 2) . 'KB | ' .
                    'Integrated: ' . number_format($integratedAvgMemUsage / 1024, 2) . 'KB'
                )
                ->descriptionIcon('heroicon-o-cpu-chip')
                ->color($this->getColorForValue($integratedAvgMemUsage, $bestMemUsage)),

            // CPU Usage
            Stat::make('Avg CPU Usage', 'Comparison')
                ->description(
                    'REST: ' . number_format($restAvgCpuUsage, 2) . '% | ' .
                    'GraphQL: ' . number_format($graphqlAvgCpuUsage, 2) . '% | ' .
                    'Integrated: ' . number_format($integratedAvgCpuUsage, 2) . '%'
                )
                ->descriptionIcon('heroicon-o-cpu-chip')
                ->color($this->getColorForValue($integratedAvgCpuUsage, $bestCpuUsage)),

            // Payload Size
            Stat::make('Avg Payload Size', 'Comparison')
                ->description(
                    'REST: ' . number_format($restAvgPayloadSize / 1024, 2) . 'KB | ' .
                    'GraphQL: ' . number_format($graphqlAvgPayloadSize / 1024, 2) . 'KB | ' .
                    'Integrated: ' . number_format($integratedAvgPayloadSize / 1024, 2) . 'KB'
                )
                ->descriptionIcon('heroicon-o-document-text')
                ->color($this->getColorForValue($integratedAvgPayloadSize, $bestPayloadSize)),
        ];
    }

    private function getColorForValue(float $value, float $bestValue): string
    {
        if ($value === $bestValue) {
            return 'success';
        } elseif ($value <= $bestValue * 1.2) {
            return 'warning';
        }
        return 'danger';
    }
}
