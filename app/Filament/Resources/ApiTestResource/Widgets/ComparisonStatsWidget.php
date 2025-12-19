<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ComparisonStatsWidget extends StatsOverviewWidget
{
    public ?ApiTest $record = null;

    public ?string $filter = '1';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        if (!$this->record) {
            $results = ApiTestResult::query()->success();
        } else {
            $results = $this->record->results()->success();
        }

        // Response Time Stats
        $restAvgResponseTime = $results->clone()->rest()->avg('response_time');
        $graphqlAvgResponseTime = $results->clone()->graphql()->avg('response_time');
        $integratedAvgResponseTime = $results->clone()->integrated()->avg('response_time');

        $improvementResponseTimeVsRest = $restAvgResponseTime > 0 ? (($restAvgResponseTime - $integratedAvgResponseTime) / $restAvgResponseTime) * 100 : 0;
        $improvementResponseTimeVsGraphQL = $graphqlAvgResponseTime > 0 ? (($graphqlAvgResponseTime - $integratedAvgResponseTime) / $graphqlAvgResponseTime) * 100 : 0;

        // Hitung rata-rata dari Rest & GraphQL untuk Response Time
        $avgRestGraphqlResponseTime = ($restAvgResponseTime + $graphqlAvgResponseTime) / 2;
        $overallImprovementResponseTime = $avgRestGraphqlResponseTime > 0 ? (($avgRestGraphqlResponseTime - $integratedAvgResponseTime) / $avgRestGraphqlResponseTime) * 100 : 0;

        // Payload Size Stats
        $restAvgPayloadSize = $results->clone()->rest()->avg('payload_size');
        $graphqlAvgPayloadSize = $results->clone()->graphql()->avg('payload_size');
        $integratedAvgPayloadSize = $results->clone()->integrated()->avg('payload_size');

        $improvementPayloadSizeVsRest = $restAvgPayloadSize > 0 ? (($restAvgPayloadSize - $integratedAvgPayloadSize) / $restAvgPayloadSize) * 100 : 0;
        $improvementPayloadSizeVsGraphQL = $graphqlAvgPayloadSize > 0 ? (($graphqlAvgPayloadSize - $integratedAvgPayloadSize) / $graphqlAvgPayloadSize) * 100 : 0;

        // Hitung rata-rata dari Rest & GraphQL untuk Payload Size
        $avgRestGraphqlPayloadSize = ($restAvgPayloadSize + $graphqlAvgPayloadSize) / 2;
        $overallImprovementPayloadSize = $avgRestGraphqlPayloadSize > 0 ? (($avgRestGraphqlPayloadSize - $integratedAvgPayloadSize) / $avgRestGraphqlPayloadSize) * 100 : 0;

        // Memory Usage Stats
        $restAvgMemUsage = $results->clone()->rest()->where('mem_usage', '>', 0)->avg('mem_usage');
        $graphqlAvgMemUsage = $results->clone()->graphql()->where('mem_usage', '>', 0)->avg('mem_usage');
        $integratedAvgMemUsage = $results->clone()->integrated()->where('mem_usage', '>', 0)->avg('mem_usage');

        $improvementMemUsageVsRest = $restAvgMemUsage > 0 ? (($restAvgMemUsage - $integratedAvgMemUsage) / $restAvgMemUsage) * 100 : 0;
        $improvementMemUsageVsGraphQL = $graphqlAvgMemUsage > 0 ? (($graphqlAvgMemUsage - $integratedAvgMemUsage) / $graphqlAvgMemUsage) * 100 : 0;

        // Hitung rata-rata dari Rest & GraphQL untuk Memory Usage
        $avgRestGraphqlMemUsage = ($restAvgMemUsage + $graphqlAvgMemUsage) / 2;
        $overallImprovementMemUsage = $avgRestGraphqlMemUsage > 0 ? (($avgRestGraphqlMemUsage - $integratedAvgMemUsage) / $avgRestGraphqlMemUsage) * 100 : 0;

        // CPU Usage Stats
        $restAvgCpuUsage = $results->clone()->rest()->avg('cpu_usage');
        $graphqlAvgCpuUsage = $results->clone()->graphql()->avg('cpu_usage');
        $integratedAvgCpuUsage = $results->clone()->integrated()->avg('cpu_usage');

        $improvementCpuUsageVsRest = $restAvgCpuUsage > 0 ? (($restAvgCpuUsage - $integratedAvgCpuUsage) / $restAvgCpuUsage) * 100 : 0;
        $improvementCpuUsageVsGraphQL = $graphqlAvgCpuUsage > 0 ? (($graphqlAvgCpuUsage - $integratedAvgCpuUsage) / $graphqlAvgCpuUsage) * 100 : 0;

        // Hitung rata-rata dari Rest & GraphQL untuk CPU Usage
        $avgRestGraphqlCpuUsage = ($restAvgCpuUsage + $graphqlAvgCpuUsage) / 2;
        $overallImprovementCpuUsage = $avgRestGraphqlCpuUsage > 0 ? (($avgRestGraphqlCpuUsage - $integratedAvgCpuUsage) / $avgRestGraphqlCpuUsage) * 100 : 0;

        // Hitung berapa query yang lebih baik
        $totalQueries = Query::count();
        $fasterResponseTimeVsRest = $this->countBetterQueries('rest', 'response_time');
        $fasterResponseTimeVsGraphQL = $this->countBetterQueries('graphql', 'response_time');

        $smallerPayloadVsRest = $this->countBetterQueries('rest', 'payload_size');
        $smallerPayloadVsGraphQL = $this->countBetterQueries('graphql', 'payload_size');

        $lowerMemUsageVsRest = $this->countBetterQueries('rest', 'mem_usage');
        $lowerMemUsageVsGraphQL = $this->countBetterQueries('graphql', 'mem_usage');

        $lowerCpuUsageVsRest = $this->countBetterQueries('rest', 'cpu_usage');
        $lowerCpuUsageVsGraphQL = $this->countBetterQueries('graphql', 'cpu_usage');

        return [
            // Response Time Stats
            Stat::make('Avg Response Time (Integrated)', Number::format(($integratedAvgResponseTime), 0) . ' ms')
                ->description(number_format(abs($overallImprovementResponseTime), 2) . '% ' . ($overallImprovementResponseTime > 0 ? 'Faster' : 'Slower'))
                ->descriptionIcon($overallImprovementResponseTime > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($overallImprovementResponseTime > 0 ? 'success' : 'danger'),

            Stat::make('vs Rest (' . Number::format($restAvgResponseTime, 0) . ' ms)', Number::format($improvementResponseTimeVsRest, 2) . '%')
                ->description("{$fasterResponseTimeVsRest}/{$totalQueries} Query Faster")
                ->descriptionIcon($improvementResponseTimeVsRest > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementResponseTimeVsRest > 0 ? 'success' : 'danger'),

            Stat::make('vs GraphQl (' . Number::format($graphqlAvgResponseTime, 0) . ' ms)', Number::format($improvementResponseTimeVsGraphQL, 2) . '%')
                ->description("{$fasterResponseTimeVsGraphQL}/{$totalQueries} Query Faster")
                ->descriptionIcon($improvementResponseTimeVsGraphQL > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementResponseTimeVsGraphQL > 0 ? 'success' : 'danger'),

            // Payload Size Stats
            Stat::make('Avg Payload Size (Integrated)', Number::format($integratedAvgPayloadSize / 1024, 2) . ' KB')
                ->description(Number::format(abs($overallImprovementPayloadSize), 2) . '% ' . ($overallImprovementPayloadSize > 0 ? 'Smaller' : 'Larger'))
                ->descriptionIcon($overallImprovementPayloadSize > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($overallImprovementPayloadSize > 0 ? 'success' : 'danger'),

            Stat::make('vs Rest (' . Number::format($restAvgPayloadSize / 1024, 2) . ' kb)', Number::format($improvementPayloadSizeVsRest, 2) . '%')
                ->description("{$smallerPayloadVsRest}/{$totalQueries} Query Smaller")
                ->descriptionIcon($improvementPayloadSizeVsRest > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementPayloadSizeVsRest > 0 ? 'success' : 'danger'),

            Stat::make('vs GraphQL (' . Number::format($graphqlAvgPayloadSize / 1024, 2) . ' kb)', Number::format($improvementPayloadSizeVsGraphQL, 2) . '%')
                ->description("{$smallerPayloadVsGraphQL}/{$totalQueries} Query Smaller")
                ->descriptionIcon($improvementPayloadSizeVsGraphQL > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementPayloadSizeVsGraphQL > 0 ? 'success' : 'danger'),

            // Memory Usage Stats
            Stat::make('Avg Memory Usage (Integrated)', Number::format($integratedAvgMemUsage / 1024, 2) . ' KB')
                ->description(Number::format(abs($overallImprovementMemUsage), 2) . '% ' . ($overallImprovementMemUsage > 0 ? 'Lower' : 'Higher'))
                ->descriptionIcon($overallImprovementMemUsage > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($overallImprovementMemUsage > 0 ? 'success' : 'danger'),

            Stat::make('vs Rest (' . Number::format($restAvgMemUsage / 1024, 2) . ' kb)', Number::format($improvementMemUsageVsRest, 2) . '%')
                ->description("{$lowerMemUsageVsRest}/{$totalQueries} Query Lower")
                ->descriptionIcon($improvementMemUsageVsRest > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementMemUsageVsRest > 0 ? 'success' : 'danger'),

            Stat::make('vs GraphQL (' . Number::format($graphqlAvgMemUsage / 1024, 2) . ' kb)', Number::format($improvementMemUsageVsGraphQL, 2) . '%')
                ->description("{$lowerMemUsageVsGraphQL}/{$totalQueries} Query Lower")
                ->descriptionIcon($improvementMemUsageVsGraphQL > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementMemUsageVsGraphQL > 0 ? 'success' : 'danger'),

            // CPU Usage Stats
            Stat::make('Avg CPU Usage (Integrated)', Number::format($integratedAvgCpuUsage, 2) . '%')
                ->description(Number::format(abs($overallImprovementCpuUsage), 2) . '% ' . ($overallImprovementCpuUsage > 0 ? 'Lower' : 'Higher'))
                ->descriptionIcon($overallImprovementCpuUsage > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($overallImprovementCpuUsage > 0 ? 'success' : 'danger'),

            Stat::make('vs Rest (' . Number::format($restAvgCpuUsage, 2) . '%)', Number::format($improvementCpuUsageVsRest, 2) . '%')
                ->description("{$lowerCpuUsageVsRest}/{$totalQueries} Query Lower")
                ->descriptionIcon($improvementCpuUsageVsRest > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementCpuUsageVsRest > 0 ? 'success' : 'danger'),

            Stat::make('vs GraphQL (' . Number::format($graphqlAvgCpuUsage, 2) . '%)', Number::format($improvementCpuUsageVsGraphQL, 2) . '%')
                ->description("{$lowerCpuUsageVsGraphQL}/{$totalQueries} Query Lower")
                ->descriptionIcon($improvementCpuUsageVsGraphQL > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementCpuUsageVsGraphQL > 0 ? 'success' : 'danger'),
        ];
    }

    private function countBetterQueries(string $compareWith, string $column): int
    {
        $queries = Query::all();
        $betterCount = 0;

        foreach ($queries as $query) {
            if (!$this->record) {
                $results = ApiTestResult::query()->success()->where('query_id', $query->id);
            } else {
                $results = $this->record->results()->success()->where('query_id', $query->id);
            }

            $compareAvg = $results->clone()->where('api_type', $compareWith)->avg($column);
            $integratedAvg = $results->clone()->integrated()->avg($column);

            // Lower is better untuk semua metrics
            if ($integratedAvg && $compareAvg && $integratedAvg < $compareAvg) {
                $betterCount++;
            }
        }

        return $betterCount;
    }
}
