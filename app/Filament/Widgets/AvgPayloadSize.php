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

class AvgPayloadSize extends TableWidget
{
    protected static ?string $heading = 'Rata-rata Ukuran Payload';

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
                avg(case when api_test_results.api_type = \'rest\' then api_test_results.payload_size end) as avg_rest_payload_size,
                avg(case when api_test_results.api_type = \'graphql\' then api_test_results.payload_size end) as avg_graphql_payload_size,
                avg(case when api_test_results.api_type = \'integrated\' then api_test_results.payload_size end) as avg_integrated_payload_size,
                (
                    (
                        avg(case when api_test_results.api_type = \'rest\' then api_test_results.payload_size end)
                        - avg(case when api_test_results.api_type = \'integrated\' then api_test_results.payload_size end)
                    ) / nullif(avg(case when api_test_results.api_type = \'rest\' then api_test_results.payload_size end), 0)
                ) * 100 as vs_rest,
                (
                    (
                        avg(case when api_test_results.api_type = \'graphql\' then api_test_results.payload_size end)
                        - avg(case when api_test_results.api_type = \'integrated\' then api_test_results.payload_size end)
                    ) / nullif(avg(case when api_test_results.api_type = \'graphql\' then api_test_results.payload_size end), 0)
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

                TextColumn::make('avg_rest_payload_size')
                    ->label('Rest')
                    ->formatStateUsing(fn($state) => Number::format($state / 1024, 2) . ' KB'),

                TextColumn::make('avg_graphql_payload_size')
                    ->label('GraphQL')
                    ->formatStateUsing(fn($state) => Number::format($state / 1024, 2) . ' KB'),

                TextColumn::make('avg_integrated_payload_size')
                    ->label('Integrated')
                    ->formatStateUsing(fn($state) => Number::format($state / 1024, 2) . ' KB')
                    ->badge()
                    ->color(fn($record) => PerformanceBadge::getColor(
                        (float)$record['avg_integrated_payload_size'] ?? 0,
                        (float)$record['avg_rest_payload_size'] ?? 0,
                        (float)$record['avg_graphql_payload_size'] ?? 0
                    ))
                    ->icon(fn($record) => PerformanceBadge::getIcon(
                        (float)$record['avg_integrated_payload_size'] ?? 0,
                        (float)$record['avg_rest_payload_size'] ?? 0,
                        (float)$record['avg_graphql_payload_size'] ?? 0
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
