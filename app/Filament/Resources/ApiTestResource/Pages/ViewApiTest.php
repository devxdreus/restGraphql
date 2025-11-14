<?php

namespace App\Filament\Resources\ApiTestResource\Pages;

use App\Filament\Resources\ApiTestResource;
use App\Filament\Resources\ApiTestResource\Widgets\ComparisonStatsWidget;
use App\Filament\Resources\ApiTestResource\Widgets\QueryCpuUsageComparisonChart;
use App\Filament\Resources\ApiTestResource\Widgets\CpuUsageDistributionChart;
use App\Filament\Resources\ApiTestResource\Widgets\EfficiencyScoreWidget;
use App\Filament\Resources\ApiTestResource\Widgets\QueryMemoryUsageComparisonChart;
use App\Filament\Resources\ApiTestResource\Widgets\MemoryUsageDistributionChart;
use App\Filament\Resources\ApiTestResource\Widgets\QueryPayloadSizeComparisonChart;
use App\Filament\Resources\ApiTestResource\Widgets\PayloadSizeDistributionChart;
use App\Filament\Resources\ApiTestResource\Widgets\ResourceUtilizationRadarChart;
use App\Filament\Resources\ApiTestResource\Widgets\QueryResponseTimeComparisonChart;
use App\Filament\Resources\ApiTestResource\Widgets\ResponseTimeDistributionChart;
use App\Filament\Resources\ApiTestResource\Widgets\SuccessRateWidget;
use App\Filament\Widgets\AvgCpuUsage;
use App\Filament\Widgets\AvgMemUsage;
use App\Filament\Widgets\AvgPayloadSize;
use App\Filament\Widgets\AvgResponseTime;
use App\Models\Query;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewApiTest extends ViewRecord
{
    protected static string $resource = ApiTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            AvgResponseTime::class,
            AvgPayloadSize::class,
            AvgMemUsage::class,
            AvgCpuUsage::class,
            SuccessRateWidget::class,
            QueryResponseTimeComparisonChart::class,
            ResponseTimeDistributionChart::class,
            QueryPayloadSizeComparisonChart::class,
            PayloadSizeDistributionChart::class,
            QueryMemoryUsageComparisonChart::class,
            MemoryUsageDistributionChart::class,
            QueryCpuUsageComparisonChart::class,
            CpuUsageDistributionChart::class,
//            ResourceUtilizationRadarChart::class,
            ComparisonStatsWidget::class,
//            EfficiencyScoreWidget::class,
        ];
    }
}
