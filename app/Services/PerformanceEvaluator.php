<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\QueryPreset;

class PerformanceEvaluator
{
    private const METRICS = ['response_time', 'payload_size', 'mem_usage', 'cpu_usage'];

    private const METRIC_WEIGHTS = [
        'response_time' => 0.5,
        'payload_size' => 0.2,
        'mem_usage' => 0.2,
        'cpu_usage' => 0.1,
    ];

    public static function analyzeMetrics(array $rest, array $graphql, QueryPreset $query): ApiType
    {
        if ($rest['status'] === ApiStatusType::Failed) {
            return ApiType::Graphql;
        }
        if ($graphql['status'] === ApiStatusType::Failed) {
            return ApiType::Rest;
        }

        $minMaxValues = self::calculateMinMaxForMetrics($query);
        $normalizedRest = self::normalizeMetrics($rest, $minMaxValues);
        $normalizedGraphql = self::normalizeMetrics($graphql, $minMaxValues);

        $scoreRest = self::calculateScore($normalizedRest);
        $scoreGraphql = self::calculateScore($normalizedGraphql);

        return self::determineWinner($scoreRest, $scoreGraphql, $rest, $graphql);
    }

    private static function calculateMinMaxForMetrics(QueryPreset $query): array
    {
        $minMaxValues = [];
        foreach (self::METRICS as $metric) {
            $min = $query->testResults()->success()->min($metric);
            $max = $query->testResults()->success()->max($metric);

            if ($min === $max) {
                $min -= 1.0;
                $max += 1.0;
            }

            $minMaxValues[$metric] = ['min' => $min, 'max' => $max];
        }
        return $minMaxValues;
    }

    private static function normalizeMetrics(array $data, array $minMaxValues): array
    {
        $normalized = [];
        foreach (self::METRICS as $metric) {
            $value = (float)($data[$metric] ?? 0);
            $min = $minMaxValues[$metric]['min'];
            $max = $minMaxValues[$metric]['max'];
            $range = $max - $min;

            $normalized[$metric] = $range != 0.0
                ? max(0.0, min(1.0, ($value - $min) / $range))
                : 0.5;
        }
        return $normalized;
    }

    private static function calculateScore(array $normalizedMetrics): float
    {
        $score = 0.0;
        foreach (self::METRIC_WEIGHTS as $metric => $weight) {
            $score += $weight * $normalizedMetrics[$metric];
        }
        return $score;
    }

    private static function determineWinner(float $scoreRest, float $scoreGraphql, array $rest, array $graphql): ApiType
    {
        if (abs($scoreRest - $scoreGraphql) < 1e-9) {
            return ($rest['payload_size'] ?? INF) <= ($graphql['payload_size'] ?? INF)
                ? ApiType::Rest
                : ApiType::Graphql;
        }

        return $scoreRest < $scoreGraphql ? ApiType::Rest : ApiType::Graphql;
    }
}
