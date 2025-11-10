<?php

namespace App\Filament\Widgets;

use App\Models\ApiTestResult;
use App\Models\Query;
use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class AvgResponseTimeByQueryChart extends ChartWidget
{
    protected ?string $heading = 'Avg Response Time By Query Chart';

    protected int|string|array $columnSpan = 'full';

    protected array $data = [];

    protected function getData(): array
    {
        $queries = Query::all();

        $labels = $queries->pluck('name')->toArray();

        $this->data['rest'] = $queries->map(
            fn(Query $query) => $query->averageByColumnAndApiType('response_time', 'rest')
        )->toArray();

        $this->data['graphql'] = $queries->map(
            fn(Query $query) => $query->averageByColumnAndApiType('response_time', 'graphql')
        )->toArray();

        $this->data['integrated'] = $queries->map(
            fn(Query $query) => $query->averageByColumnAndApiType('response_time', 'integrated')
        )->toArray();

        // Cari index mana yang memiliki nilai terendah untuk setiap query
        $dataCount = count($this->data['rest']);
        $minIndexes = ['rest' => [], 'graphql' => [], 'integrated' => []];

        for ($i = 0; $i < $dataCount; $i++) {
            $values = [
                'rest' => $this->data['rest'][$i] ?? PHP_FLOAT_MAX,
                'graphql' => $this->data['graphql'][$i] ?? PHP_FLOAT_MAX,
                'integrated' => $this->data['integrated'][$i] ?? PHP_FLOAT_MAX,
            ];

            $minKey = array_keys($values, min($values))[0];
            $minIndexes[$minKey][] = $i;
        }

        // Fungsi untuk membuat array warna berdasarkan index yang di-highlight
        $createColorsArray = function ($dataCount, $highlightIndexes, $normalColor, $highlightColor) {
            $colors = [];
            for ($i = 0; $i < $dataCount; $i++) {
                $colors[] = in_array($i, $highlightIndexes) ? $highlightColor : $normalColor;
            }
            return $colors;
        };

        return [
            'datasets' => [
                [
                    'label' => 'REST',
                    'data' => $this->data['rest'],
                    'backgroundColor' => $createColorsArray($dataCount, $minIndexes['rest'], 'rgba(255,255,255,.2)', Color::Teal[500]),
                    'borderColor' => $createColorsArray($dataCount, $minIndexes['rest'], Color::Teal[500], Color::Teal[500]),
                    'borderWidth' => 1,
//                    'borderRadius' => 10,
//                    'borderSkipped' => false
                ],
                [
                    'label' => 'GraphQL',
                    'data' => $this->data['graphql'],
                    'backgroundColor' => $createColorsArray($dataCount, $minIndexes['graphql'], 'rgba(255,255,255,.2)', Color::Blue[500]),
                    'borderColor' => $createColorsArray($dataCount, $minIndexes['graphql'], Color::Blue[500], Color::Blue[500]),
                    'borderWidth' => 1,
//                    'borderRadius' => 10,
                ],
                [
                    'label' => 'Integrated',
                    'data' => $this->data['integrated'],
                    'backgroundColor' => $createColorsArray($dataCount, $minIndexes['integrated'], 'rgba(255,255,255,.2)', Color::Red[500]),
                    'borderColor' => $createColorsArray($dataCount, $minIndexes['integrated'], Color::Red[500], Color::Red[500]),
                    'borderWidth' => 1,
//                    'borderRadius' => 10,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        $min = collect($this->data)->min(fn($data) => min($data));

        $minValue = floor($min / 50) * 50;

        return [
            'scales' => [
                'y' => [
                    'min' => $minValue,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
