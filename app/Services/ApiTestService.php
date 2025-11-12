<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\QueryPreset;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ApiTestService
{
    private const CACHE_KEY_PATTERN = 'api_query_%d_preset_%d_%s';

    private const METRICS = ['response_time', 'payload_size', 'mem_usage', 'cpu_usage'];

    private const METRIC_WEIGHTS = [
        'response_time' => 0.5,
        'payload_size' => 0.2,
        'mem_usage' => 0.2,
        'cpu_usage' => 0.1,
    ];

    public function __construct(
        private readonly string $token,
        private readonly string $restUrl,
        private readonly string $graphqlUrl
    )
    {
    }

    public static function make(): self
    {
        return new self(
            config('api.github.token'),
            config('api.github.endpoint.rest'),
            config('api.github.endpoint.graphql')
        );
    }

    public function fetchRestData(QueryPreset $query): array
    {
        try {
            $metrics = $this->captureMetrics(function () use ($query) {
                return Http::withToken($this->token)
                    ->get($this->restUrl . '/' . $query->rest_query);
            });

            if ($metrics['response']->failed()) {
                return $this->handleFailedRequest($query, 'rest');
            }

            return $this->handleSuccessfulRequest($metrics, $query, 'rest');
        } catch (\Exception $e) {
            return $this->handleRequestException($query, 'rest');
        }
    }

    public function fetchGraphQLData(QueryPreset $query): array
    {
        try {
            $metrics = $this->captureMetrics(function () use ($query) {
                return Http::withToken($this->token)
                    ->post($this->graphqlUrl, ['query' => $query->graphql_query]);
            });

            if ($metrics['response']->failed() || isset($metrics['response']['errors'])) {
                return $this->handleFailedRequest($query, 'graphql');
            }

            return $this->handleSuccessfulRequest($metrics, $query, 'graphql');
        } catch (\Exception $e) {
            return $this->handleRequestException($query, 'graphql');
        }
    }

    public function fetchIntegrated(QueryPreset $query): array
    {
        $restCache = $this->getCachedResult($query, 'rest');
        $graphqlCache = $this->getCachedResult($query, 'graphql');

        if ($restCache && $graphqlCache) {
            return $this->fetchIntegratedFromBothCaches($query, $restCache, $graphqlCache);
        }

        if ($restCache) {
            $data = $this->fetchGraphQLData($query);
            $data['request_type'] = ApiType::Graphql;
            return $data;
        }

        if ($graphqlCache) {
            $data = $this->fetchRestData($query);
            $data['request_type'] = ApiType::Rest;
            return $data;
        }

        return [
            'status' => ApiStatusType::Failed,
            'request_type' => ApiType::Integrated,
        ];
    }

    public function analyzeMetric(array $rest, array $graphql, QueryPreset $query): ApiType
    {
        if ($rest['status'] === ApiStatusType::Failed) {
            return ApiType::Graphql;
        }
        if ($graphql['status'] === ApiStatusType::Failed) {
            return ApiType::Rest;
        }

        $minMaxValues = $this->calculateMinMaxForMetrics($query);
        $normalizedRest = $this->normalizeMetrics($rest, $minMaxValues);
        $normalizedGraphql = $this->normalizeMetrics($graphql, $minMaxValues);

        $scoreRest = $this->calculateScore($normalizedRest);
        $scoreGraphql = $this->calculateScore($normalizedGraphql);

        return $this->determineWinner($scoreRest, $scoreGraphql, $rest, $graphql);
    }

    public function getCachedResult(QueryPreset $query, string $type): ?array
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        return Cache::get($key);
    }

    private function captureMetrics(callable $requestCallback): array
    {
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage();
        $cpuBefore = $this->getCpuTime();

        $response = $requestCallback();

        $memoryAfter = memory_get_usage();
        $cpuAfter = $this->getCpuTime();
        $endTime = microtime(true);

        $responseTime = round(($endTime - $startTime) * 1000);
        $cpuTime = $cpuAfter - $cpuBefore;

        return [
            'response' => $response,
            'response_time' => $responseTime,
            'payload_size' => strlen($response->body()),
            'mem_usage' => $memoryAfter - $memoryBefore,
            'cpu_usage' => $responseTime > 0 ? round(($cpuTime / ($responseTime * 1000)) * 100, 2) : 0,
            'cpu_time' => $cpuTime,
        ];
    }

    private function handleSuccessfulRequest(array $metrics, QueryPreset $query, string $cacheKey): array
    {
        $data = [
            'status' => ApiStatusType::Success,
            'response_time' => $metrics['response_time'],
            'payload_size' => $metrics['payload_size'],
            'mem_usage' => $metrics['mem_usage'],
            'cpu_usage' => $metrics['cpu_usage'],
        ];

        $this->cacheResult($query, $cacheKey, $data);
        $data['response'] = $metrics['response']->json();

        return $data;
    }

    private function handleFailedRequest(QueryPreset $query, string $cacheKey): array
    {
        $data = ['status' => ApiStatusType::Failed];
        $this->cacheResult($query, $cacheKey, $data);
        return $data;
    }

    private function handleRequestException(QueryPreset $query, string $cacheKey): array
    {
        $data = ['status' => ApiStatusType::Failed];
        $this->cacheResult($query, $cacheKey, $data);
        return $data;
    }

    private function cacheResult(QueryPreset $query, string $type, array $data): void
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        Cache::put($key, $data);
    }

    private function fetchIntegratedFromBothCaches(QueryPreset $query, array $restCache, array $graphqlCache): array
    {
        $winner = $this->analyzeMetric($restCache, $graphqlCache, $query);
        $isRest = $winner === ApiType::Rest;

        $data = $isRest ? $this->fetchRestData($query) : $this->fetchGraphQLData($query);
        $data['request_type'] = $winner;

        return $data;
    }

    private function calculateMinMaxForMetrics(QueryPreset $query): array
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

    private function normalizeMetrics(array $data, array $minMaxValues): array
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

    private function calculateScore(array $normalizedMetrics): float
    {
        $score = 0.0;

        foreach (self::METRIC_WEIGHTS as $metric => $weight) {
            $score += $weight * $normalizedMetrics[$metric];
        }

        return $score;
    }

    private function determineWinner(float $scoreRest, float $scoreGraphql, array $rest, array $graphql): ApiType
    {
        if (abs($scoreRest - $scoreGraphql) < 1e-9) {
            return ($rest['payload_size'] ?? INF) <= ($graphql['payload_size'] ?? INF)
                ? ApiType::Rest
                : ApiType::Graphql;
        }

        return $scoreRest < $scoreGraphql ? ApiType::Rest : ApiType::Graphql;
    }

    private function getCpuTime(): int
    {
        $usage = getrusage();

        $userTime = ($usage['ru_utime.tv_sec'] * 1000000) + $usage['ru_utime.tv_usec'];
        $systemTime = ($usage['ru_stime.tv_sec'] * 1000000) + $usage['ru_stime.tv_usec'];

        return $userTime + $systemTime;
    }
}
