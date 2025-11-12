<?php

namespace App\Filament\Widgets;

use App\Models\ApiTestResult;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverallSuccessRateWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Query all results
        $results = ApiTestResult::query();

        // Calculate overall success rate
        $totalRequests = $results->count();
        $successfulRequests = $results->clone()->success()->count();
        $overallSuccessRate = $totalRequests > 0 ? ($successfulRequests / $totalRequests) * 100 : 0;

        // REST success rate
        $restTotal = $results->clone()->rest()->count();
        $restSuccess = $results->clone()->rest()->success()->count();
        $restSuccessRate = $restTotal > 0 ? ($restSuccess / $restTotal) * 100 : 0;

        // GraphQL success rate
        $graphqlTotal = $results->clone()->graphql()->count();
        $graphqlSuccess = $results->clone()->graphql()->success()->count();
        $graphqlSuccessRate = $graphqlTotal > 0 ? ($graphqlSuccess / $graphqlTotal) * 100 : 0;

        // Integrated success rate
        $integratedTotal = $results->clone()->integrated()->count();
        $integratedSuccess = $results->clone()->integrated()->success()->count();
        $integratedSuccessRate = $integratedTotal > 0 ? ($integratedSuccess / $integratedTotal) * 100 : 0;

        return [
            Stat::make('Overall Success Rate', number_format($overallSuccessRate, 2) . '%')
                ->description($successfulRequests . ' / ' . $totalRequests . ' total requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($overallSuccessRate >= 95 ? 'success' : ($overallSuccessRate >= 80 ? 'warning' : 'danger'))
                ->chart($this->getSuccessRateChart()),

            Stat::make('Integrated Success Rate', number_format($integratedSuccessRate, 2) . '%')
                ->description($integratedSuccess . ' / ' . $integratedTotal . ' requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($integratedSuccessRate >= 95 ? 'success' : ($integratedSuccessRate >= 80 ? 'warning' : 'danger')),

            Stat::make('REST Success Rate', number_format($restSuccessRate, 2) . '%')
                ->description($restSuccess . ' / ' . $restTotal . ' requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($restSuccessRate >= 95 ? 'success' : ($restSuccessRate >= 80 ? 'warning' : 'danger')),

            Stat::make('GraphQL Success Rate', number_format($graphqlSuccessRate, 2) . '%')
                ->description($graphqlSuccess . ' / ' . $graphqlTotal . ' requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($graphqlSuccessRate >= 95 ? 'success' : ($graphqlSuccessRate >= 80 ? 'warning' : 'danger')),
        ];
    }

    private function getSuccessRateChart(): array
    {
        // Get success rate for last 7 tests
        $tests = \App\Models\ApiTest::latest()->take(7)->get()->reverse();

        return $tests->map(function ($test) {
            $total = $test->results()->count();
            $success = $test->results()->success()->count();
            return $total > 0 ? ($success / $total) * 100 : 0;
        })->toArray();
    }
}
