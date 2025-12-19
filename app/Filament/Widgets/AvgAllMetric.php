<?php

namespace App\Filament\Widgets;

use App\Enums\ApiType;
use App\Models\ApiTestResult;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class AvgAllMetric extends TableWidget
{
    protected static ?string $heading = 'Rata-rata Semua Metric Pengukuran';

    public function table(Table $table): Table
    {
        $records = ApiTestResult::query()
            ->success()
            ->groupBy('api_type')
            ->where('mem_usage', '>', 0)
            ->selectRaw('
                api_type,
                avg(response_time) as avg_response_time,
                avg(mem_usage) as avg_mem_usage,
                avg(cpu_usage) as avg_cpu_usage
            ')
            ->get();

        return $table
            ->records(fn(): array => $records->toArray())
            ->columns([
                TextColumn::make('api_type')
                    ->label('API')
                    ->formatStateUsing(fn($state): string => ucwords($state)),

                TextColumn::make('avg_response_time')
                    ->label('Response Time')
                    ->formatStateUsing(fn($state) => Number::format($state, 0) . 'ms'),

                TextColumn::make('avg_mem_usage')
                    ->label('Memory Usage')
                    ->formatStateUsing(fn($state) => Number::format($state / 1024, 2) . ' KB'),

                TextColumn::make('avg_cpu_usage')
                    ->label('CPU Usage')
                    ->formatStateUsing(fn($state) => Number::format($state, 2) . '%'),
            ]);
    }
}
