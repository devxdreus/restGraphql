<?php

namespace App\Filament\Resources\ApiTestResource\Widgets;

use App\Models\ApiTest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EfficiencyScoreWidget extends StatsOverviewWidget
{
    public ?ApiTest $record = null;

    public ?string $filter = '1';

    protected function getStats(): array
    {
        $results = $this->record->results()->success()->where('query_id', $this->filter);

        // Ambil data untuk setiap API type
        $integratedStats = $results->clone()->integrated()->selectRaw('
            AVG(response_time) as avg_response_time,
            AVG(mem_usage) as avg_mem_usage,
            AVG(cpu_usage) as avg_cpu_usage,
            AVG(payload_size) as avg_payload_size
        ')->first();

        $restStats = $results->clone()->rest()->selectRaw('
            AVG(response_time) as avg_response_time,
            AVG(mem_usage) as avg_mem_usage,
            AVG(cpu_usage) as avg_cpu_usage,
            AVG(payload_size) as avg_payload_size
        ')->first();

        $graphqlStats = $results->clone()->graphql()->selectRaw('
            AVG(response_time) as avg_response_time,
            AVG(mem_usage) as avg_mem_usage,
            AVG(cpu_usage) as avg_cpu_usage,
            AVG(payload_size) as avg_payload_size
        ')->first();

        // Hitung efficiency score (semakin rendah semakin baik)
        $integratedScore = $this->calculateEfficiencyScore($integratedStats);
        $restScore = $this->calculateEfficiencyScore($restStats);
        $graphqlScore = $this->calculateEfficiencyScore($graphqlStats);
    
        return [
            Stat::make('Integrated Efficiency Score', number_format($integratedScore, 2))
                ->description('Lower is better')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($this->getScoreColor($integratedScore, $restScore, $graphqlScore)),

            Stat::make('Memory Efficiency', number_format($integratedStats->avg_mem_usage / 1024, 2) . ' KB')
                ->description('Average memory usage')
                ->descriptionIcon('heroicon-o-cpu-chip')
                ->color('info'),

            Stat::make('Payload Efficiency', number_format($integratedStats->avg_payload_size / 1024, 2) . ' KB')
                ->description('Average payload size')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }

    private function calculateEfficiencyScore($stats): float
    {
        // Weighted score: response time (40%), memory (30%), cpu (20%), payload (10%)
        return ($stats->avg_response_time * 0.4) +
            ($stats->avg_mem_usage / 1000 * 0.3) +
            ($stats->avg_cpu_usage * 100 * 0.2) +
            ($stats->avg_payload_size / 1000 * 0.1);
    }

    private function getScoreColor(float $integrated, float $rest, float $graphql): string
    {
        $minScore = min($integrated, $rest, $graphql);

        if ($integrated === $minScore) {
            return 'success';

        } elseif ($integrated <= $minScore * 1.2) {
            // Jika skor integrated <= 120% dari skor minimum
            return 'warning';
        }

        // Jika skor integrated > 120% dari skor minimum, performa kurang baik
        return 'danger';
    }
}
