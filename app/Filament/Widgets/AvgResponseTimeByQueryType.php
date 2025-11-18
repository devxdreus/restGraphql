<?php

namespace App\Filament\Widgets;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Helpers\PerformanceBadge;
use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class AvgResponseTimeByQueryType extends TableWidget
{
    protected static ?string $heading = 'Rata-Rata Waktu Respons per Kategori Skenario';

    protected int|string|array $columnStart = 2;

    public function table(Table $table): Table
    {
        $data = [];
        $queries = Query::all();
        foreach ($queries as $query) {
            $data[$query->id] = [
                'type' => $query->type->getLabel(),
                'rest' => $query->avgRestResponseTime,
                'graphql' => $query->avgGraphqlResponseTime,
                'integrated' => $query->avgIntegratedResponseTime,
            ];
        }

        $data = collect($data)->groupBy('type');

        $avg = $data->map(function (Collection $item, $key) {
            return [
                'type' => $key,
                'rest' => (int)$item->avg('rest'),
                'graphql' => (int)$item->avg('graphql'),
                'integrated' => (int)$item->avg('integrated'),
            ];
        });

        return $table
            ->records(fn(): array => $avg->all())
            ->columns([
                TextColumn::make('type')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('rest')
                    ->label('REST')
                    ->numeric()
                    ->formatStateUsing(fn($state) => Number::format($state) . 'ms'),

                TextColumn::make('graphql')
                    ->label('GraphQL')
                    ->numeric()
                    ->formatStateUsing(fn($state) => Number::format($state) . 'ms'),

                TextColumn::make('integrated')
                    ->label('Integrated')
                    ->badge()
                    ->formatStateUsing(fn($state) => Number::format($state) . 'ms')
                    ->color(fn($record) => PerformanceBadge::getColor(
                        (float)$record['integrated'] ?? 0,
                        (float)$record['rest'] ?? 0,
                        (float)$record['graphql'] ?? 0
                    ))
                    ->icon(fn($record) => PerformanceBadge::getIcon(
                        (float)$record['integrated'] ?? 0,
                        (float)$record['rest'] ?? 0,
                        (float)$record['graphql'] ?? 0
                    )),
            ]);
    }
}
