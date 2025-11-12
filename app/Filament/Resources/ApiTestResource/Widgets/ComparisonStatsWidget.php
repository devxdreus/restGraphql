<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use App\Models\Query;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ComparisonStatsWidget extends StatsOverviewWidget
{
    public ?ApiTest $record = null;

    public ?string $filter = '1';

    protected function getStats(): array
    {
        $results = $this->record->results()->success()->where('query_id', $this->filter);

        $restAvg = $results->clone()->rest()->avg('response_time');
        $graphqlAvg = $results->clone()->graphql()->avg('response_time');
        $integratedAvg = $results->clone()->integrated()->avg('response_time');

        // Hitung improvement percentage
        $improvementVsRest = $restAvg > 0 ? (($restAvg - $integratedAvg) / $restAvg) * 100 : 0;
        $improvementVsGraphQL = $graphqlAvg > 0 ? (($graphqlAvg - $integratedAvg) / $graphqlAvg) * 100 : 0;

        // Hitung berapa query yang lebih cepat
        $totalQueries = Query::count();
        $fasterQueriesVsRest = $this->countFasterQueries('rest');
        $fasterQueriesVsGraphQL = $this->countFasterQueries('graphql');

        return [
            Stat::make('Avg Response Time (Integrated)', number_format($integratedAvg, 2) . 'ms')
                ->description('Integrated method average')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success'),

            Stat::make('Improvement vs Rest', number_format($improvementVsRest, 2) . '%')
                ->description("{$fasterQueriesVsRest}/{$totalQueries} Query " . ($improvementVsRest > 0 ? 'Faster' : 'Slower'))
                ->descriptionIcon($improvementVsRest > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementVsRest > 0 ? 'success' : 'danger'),

            Stat::make('Improvement vs GraphQL', number_format($improvementVsGraphQL, 2) . '%')
                ->description("{$fasterQueriesVsGraphQL}/{$totalQueries} Query " . ($improvementVsGraphQL > 0 ? 'Faster' : 'Slower'))
                ->descriptionIcon($improvementVsGraphQL > 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($improvementVsGraphQL > 0 ? 'success' : 'danger'),
        ];
    }

    private function countFasterQueries(string $compareWith): int
    {
        $queries = Query::all();
        $fasterCount = 0;

        foreach ($queries as $query) {
            $results = $this->record->results()->success()->where('query_id', $query->id);

            $compareAvg = $results->clone()->where('api_type', $compareWith)->avg('response_time');
            $integratedAvg = $results->clone()->integrated()->avg('response_time');

            if ($integratedAvg && $compareAvg && $integratedAvg < $compareAvg) {
                $fasterCount++;
            }
        }

        return $fasterCount;
    }
}
