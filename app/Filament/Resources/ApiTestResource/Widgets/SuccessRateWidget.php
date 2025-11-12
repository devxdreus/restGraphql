<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuccessRateWidget extends StatsOverviewWidget
{
    public ?ApiTest $record = null;

    public ?string $filter = '1';

    protected function getStats(): array
    {
        $results = $this->record->results()->where('query_id', $this->filter);

        $restTotal = $results->clone()->rest()->count();
        $restSuccess = $results->clone()->rest()->success()->count();
        $restSuccessRate = $restTotal > 0 ? ($restSuccess / $restTotal) * 100 : 0;

        $graphqlTotal = $results->clone()->graphql()->count();
        $graphqlSuccess = $results->clone()->graphql()->success()->count();
        $graphqlSuccessRate = $graphqlTotal > 0 ? ($graphqlSuccess / $graphqlTotal) * 100 : 0;

        $integratedTotal = $results->clone()->integrated()->count();
        $integratedSuccess = $results->clone()->integrated()->success()->count();
        $integratedSuccessRate = $integratedTotal > 0 ? ($integratedSuccess / $integratedTotal) * 100 : 0;

        return [
            Stat::make('Rest Success Rate', number_format($restSuccessRate, 2) . '%')
                ->description($restSuccess . ' / ' . $restTotal . ' requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($restSuccessRate >= 95 ? 'success' : ($restSuccessRate >= 80 ? 'warning' : 'danger')),

            Stat::make('GraphQL Success Rate', number_format($graphqlSuccessRate, 2) . '%')
                ->description($graphqlSuccess . ' / ' . $graphqlTotal . ' requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($graphqlSuccessRate >= 95 ? 'success' : ($graphqlSuccessRate >= 80 ? 'warning' : 'danger')),

            Stat::make('Integrated Success Rate', number_format($integratedSuccessRate, 2) . '%')
                ->description($integratedSuccess . ' / ' . $integratedTotal . ' requests')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($integratedSuccessRate >= 95 ? 'success' : ($integratedSuccessRate >= 80 ? 'warning' : 'danger')),
        ];
    }
}
