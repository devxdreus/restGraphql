<?php

namespace App\Filament\Widgets;

use App\Helpers\PerformanceBadge;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class AvgCpuUsage extends TableWidget
{
    protected static ?string $heading = 'Rata-rata Penggunaan CPU';

    public ?ApiTest $record = null;

    public function table(Table $table): Table
    {
        if ($this->record) {
            $query = $this->record->results()->success();
        } else {
            $query = ApiTestResult::query()->success();
        }

        $data = $query->groupBy('api_test_results.query_id', 'queries.name')
            ->leftJoin('queries', 'api_test_results.query_id', '=', 'queries.id')
            ->selectRaw('
                api_test_results.query_id,
                queries.name as query_name,
                avg(case when api_test_results.api_type = \'rest\' then api_test_results.cpu_usage end) as avg_rest_cpu_usage,
                avg(case when api_test_results.api_type = \'graphql\' then api_test_results.cpu_usage end) as avg_graphql_cpu_usage,
                avg(case when api_test_results.api_type = \'integrated\' then api_test_results.cpu_usage end) as avg_integrated_cpu_usage,
                (
                    (
                        avg(case when api_test_results.api_type = \'rest\' then api_test_results.cpu_usage end)
                        - avg(case when api_test_results.api_type = \'integrated\' then api_test_results.cpu_usage end)
                    ) / nullif(avg(case when api_test_results.api_type = \'rest\' then api_test_results.cpu_usage end), 0)
                ) * 100 as vs_rest,
                (
                    (
                        avg(case when api_test_results.api_type = \'graphql\' then api_test_results.cpu_usage end)
                        - avg(case when api_test_results.api_type = \'integrated\' then api_test_results.cpu_usage end)
                    ) / nullif(avg(case when api_test_results.api_type = \'graphql\' then api_test_results.cpu_usage end), 0)
                ) * 100 as vs_graphql
            ')
            ->orderBy('api_test_results.query_id')
            ->get();

        return $table
            ->records(fn(): array => $data->toArray())
            ->poll('3s')
            ->columns([
                TextColumn::make('query_name')
                    ->label('Query'),

                TextColumn::make('avg_rest_cpu_usage')
                    ->label('Rest')
                    ->formatStateUsing(fn($state) => Number::format($state, 2) . '%'),

                TextColumn::make('avg_graphql_cpu_usage')
                    ->label('GraphQL')
                    ->formatStateUsing(fn($state) => Number::format($state, 2) . '%'),

                TextColumn::make('avg_integrated_cpu_usage')
                    ->label('Integrated')
                    ->formatStateUsing(fn($state) => Number::format($state, 2) . '%')
                    ->badge()
                    ->color(fn($record) => PerformanceBadge::getColor(
                        (float)$record['avg_integrated_cpu_usage'] ?? 0,
                        (float)$record['avg_rest_cpu_usage'] ?? 0,
                        (float)$record['avg_graphql_cpu_usage'] ?? 0
                    ))
                    ->icon(fn($record) => PerformanceBadge::getIcon(
                        (float)$record['avg_integrated_cpu_usage'] ?? 0,
                        (float)$record['avg_rest_cpu_usage'] ?? 0,
                        (float)$record['avg_graphql_cpu_usage'] ?? 0
                    )),

                TextColumn::make('vs_rest')
                    ->label('VS Rest')
                    ->formatStateUsing(fn($state) => (int)$state . '%'),

                TextColumn::make('vs_graphql')
                    ->label('VS GraphQL')
                    ->formatStateUsing(fn($state) => (int)$state . '%'),
            ])
            ->paginated(false);
    }
}
