<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\QueryPreset;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function fetchRestData(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        try {
            $metrics = $this->captureMetrics(function () use ($query) {
                return Http::withToken($this->token)
                    ->get($this->restUrl . '/' . $query->rest_query);
            });

            if ($metrics['response']->failed()) {
                return $this->storeTestResult($apiTest, $query, ApiType::Rest, ApiStatusType::Failed, $metrics);
            }

            return $this->storeTestResult(
                $apiTest,
                $query,
                ApiType::Rest,
                ApiStatusType::Success,
                $metrics
            );
        } catch (\Exception $e) {
            Log::error('Rest error : ' . $e->getMessage());
            return $this->storeTestResult($apiTest, $query, ApiType::Rest, ApiStatusType::Failed);
        }
    }

    public function fetchGraphQLData(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        try {
            $metrics = $this->captureMetrics(function () use ($query) {
                return Http::withToken($this->token)
                    ->post($this->graphqlUrl, ['query' => $query->graphql_query]);
            });

            if ($metrics['response']->failed() || isset($metrics['response']['errors'])) {
                return $this->storeTestResult($apiTest, $query, ApiType::Graphql, ApiStatusType::Failed, $metrics);
            }

            return $this->storeTestResult(
                $apiTest,
                $query,
                ApiType::Graphql,
                ApiStatusType::Success,
                $metrics
            );
        } catch (\Exception $e) {
            Log::error('Graphql error : ' . $e->getMessage());
            return $this->storeTestResult($apiTest, $query, ApiType::Graphql, ApiStatusType::Failed);
        }
    }

    public function fetchIntegrated(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        while (!$restCache = $this->getCachedResult($query, 'rest')) {
            $this->fetchRestData($apiTest, $query);
        }

        while (!$graphqlCache = $this->getCachedResult($query, 'graphql')) {
            $this->fetchGraphQLData($apiTest, $query);
        }

        return $this->fetchIntegratedFromBothCaches($apiTest, $query, $restCache, $graphqlCache);
    }

    private function fetchIntegratedFromBothCaches(ApiTest $apiTest, QueryPreset $query, array $restCache, array $graphqlCache): ApiTestResult
    {
        $winner = $this->analyzeMetric($restCache, $graphqlCache, $query);
        $isRest = $winner === ApiType::Rest;


        $result = $isRest ? $this->fetchRestData($apiTest, $query) : $this->fetchGraphQLData($apiTest, $query);

        $result->update(['api_type' => ApiType::Integrated]);
        return $result;
    }

    private function storeTestResult(
        ApiTest       $apiTest,
        QueryPreset   $query,
        ApiType       $apiType,
        ApiStatusType $status,
                      $metrics = null,
    ): ApiTestResult
    {
        if ($status == ApiStatusType::Failed) {
            Log::error($apiType->value . ' failed', @$metrics['response']?->collect()->toArray() ?? []);
        }
        $data = [
            'query_id' => $query->query_id,
            'preset_id' => $query->id,
            'api_type' => $apiType,
            'status' => $status,
            'request_type' => $apiType,
        ];

        $data = array_merge($data, $metrics ?? []);

        if (isset($data['response'])) {
            $data['response'] = $data['response']->collect() ?? [];
        }

        $result = $apiTest->results()->create($data);

        unset($data['response']);
        $this->putCachedResult($query, $apiType->value, $data);

        return $result;
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

    public function putCachedResult(QueryPreset $query, string $type, array $value): bool
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        return Cache::put($key, $value);
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
