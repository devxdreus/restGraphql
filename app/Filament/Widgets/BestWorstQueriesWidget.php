<?php

namespace App\Filament\Widgets;

use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class BestWorstQueriesWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Query Performance Analysis';

    public function table(Table $table): Table
    {
//         Get all queries with their performance metrics
        $queries = Query::all()->map(function ($query) {
            $results = ApiTestResult::where('query_id', $query->id)->success();

            $restAvg = $results->clone()->rest()->avg('response_time');
            $graphqlAvg = $results->clone()->graphql()->avg('response_time');
            $integratedAvg = $results->clone()->integrated()->avg('response_time');

            // Determine best API type for this query
            $avgValues = [
                'REST' => $restAvg ?: PHP_FLOAT_MAX,
                'GraphQL' => $graphqlAvg ?: PHP_FLOAT_MAX,
                'Integrated' => $integratedAvg ?: PHP_FLOAT_MAX,
            ];

            $bestApiType = array_keys($avgValues, min($avgValues))[0];
            $bestValue = min($avgValues);

            return [
                'id' => $query->id,
                'name' => $query->name,
                'rest_avg' => $restAvg,
                'graphql_avg' => $graphqlAvg,
                'integrated_avg' => $integratedAvg,
                'best_api_type' => $bestApiType,
                'best_value' => $bestValue === PHP_FLOAT_MAX ? null : $bestValue,
            ];
        })->sortBy('best_value');

        return $table
            ->query(
                Query::query()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Query Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rest_avg')
                    ->label('REST Avg (ms)')
                    ->getStateUsing(function ($record) use ($queries) {
                        $query = $queries->firstWhere('id', $record->id);
                        return $query['rest_avg'] ? number_format($query['rest_avg'], 2) : 'N/A';
                    })
                    ->sortable(),

                TextColumn::make('graphql_avg')
                    ->label('GraphQL Avg (ms)')
                    ->getStateUsing(function ($record) use ($queries) {
                        $query = $queries->firstWhere('id', $record->id);
                        return $query['graphql_avg'] ? number_format($query['graphql_avg'], 2) : 'N/A';
                    })
                    ->sortable(),

                TextColumn::make('integrated_avg')
                    ->label('Integrated Avg (ms)')
                    ->getStateUsing(function ($record) use ($queries) {
                        $query = $queries->firstWhere('id', $record->id);
                        return $query['integrated_avg'] ? number_format($query['integrated_avg'], 2) : 'N/A';
                    })
                    ->sortable(),

                TextColumn::make('best_api_type')
                    ->label('Best API Type')
                    ->getStateUsing(function ($record) use ($queries) {
                        $query = $queries->firstWhere('id', $record->id);
                        return $query['best_api_type'];
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Integrated' => 'success',
                        'REST' => 'warning',
                        'GraphQL' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('best_value')
                    ->label('Best Time (ms)')
                    ->getStateUsing(function ($record) use ($queries) {
                        $query = $queries->firstWhere('id', $record->id);
                        return $query['best_value'] ? number_format($query['best_value'], 2) : 'N/A';
                    })
                    ->sortable(),
            ]);
    }
}
