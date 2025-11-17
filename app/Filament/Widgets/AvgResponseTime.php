<?php

namespace App\Filament\Widgets;

use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AvgResponseTime extends TableWidget
{
    protected static ?string $heading = 'Rata-rata Response Time';

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
                avg(case when api_test_results.api_type = \'rest\' then api_test_results.response_time end) as avg_rest_response_time,
                avg(case when api_test_results.api_type = \'graphql\' then api_test_results.response_time end) as avg_graphql_response_time,
                avg(case when api_test_results.api_type = \'integrated\' then api_test_results.response_time end) as avg_integrated_response_time
            ')
            ->orderBy('api_test_results.query_id')
            ->get();

        return $table
            ->records(fn(): array => $data->toArray())
            ->poll('3s')
            ->columns([
                TextColumn::make('query_name')
                    ->label('Query'),

                TextColumn::make('avg_rest_response_time')
                    ->label('Rest')
                    ->formatStateUsing(fn($state) => (int)$state . 'ms'),

                TextColumn::make('avg_graphql_response_time')
                    ->label('GraphQL')
                    ->formatStateUsing(fn($state) => (int)$state . 'ms'),

                TextColumn::make('avg_integrated_response_time')
                    ->label('Integrated')
                    ->formatStateUsing(fn($state) => (int)$state . 'ms')
                    ->badge()
                    ->color(fn($record) => $this->getColor($record))
                    ->icon(fn($record) => $this->getIcon($record))
                ,
            ])
            ->paginated(false);
    }

    private function getColor(array $record): string
    {
        if ($record['avg_integrated_response_time'] < $record['avg_rest_response_time'] &&
            $record['avg_integrated_response_time'] < $record['avg_graphql_response_time']) {
            return 'success';
        }

        if ($record['avg_integrated_response_time'] < $record['avg_rest_response_time'] ||
            $record['avg_integrated_response_time'] < $record['avg_graphql_response_time']) {
            return 'warning';
        }

        return 'danger';
    }

    private function getIcon(array $record): string|Heroicon
    {
        if ($record['avg_integrated_response_time'] < $record['avg_rest_response_time'] &&
            $record['avg_integrated_response_time'] < $record['avg_graphql_response_time']) {
            return Heroicon::OutlinedArrowTrendingDown;
        }

        if ($record['avg_integrated_response_time'] < $record['avg_rest_response_time'] ||
            $record['avg_integrated_response_time'] < $record['avg_graphql_response_time']) {
            return Heroicon::OutlinedArrowTrendingDown;
        }

        return Heroicon::OutlinedArrowTrendingUp;
    }
}
