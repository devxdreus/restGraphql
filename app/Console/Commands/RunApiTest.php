<?php

namespace App\Console\Commands;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\Query;
use App\Models\QueryPreset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RunApiTest extends Command
{
    protected $signature = 'api:run {--count=1 : Number of times to run the test}';

    protected $description = 'Command description';

    protected ?string $token;
    protected ?string $restUrl;
    protected ?string $graphqlUrl;
    protected ?ApiTest $apiTest;

    private const CACHE_KEY_PATTERN = 'api_query_%d_preset_%d_%s';

    private const METRICS = ['response_time', 'payload_size', 'mem_usage', 'cpu_usage'];

    private const METRIC_WEIGHTS = [
        'response_time' => 0.5,
        'payload_size' => 0.2,
        'mem_usage' => 0.2,
        'cpu_usage' => 0.1,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->token = config('api.github.token');
        $this->restUrl = config('api.github.endpoint.rest');
        $this->graphqlUrl = config('api.github.endpoint.graphql');
    }

    public function handle()
    {
        $count = $this->option('count');
        $this->info("Running API test with {$count} iterations");

        Cache::flush();

        $this->call('github:limit');

        $this->apiTest = ApiTest::create(['count' => $count]);

        try {
            $this->runTestIterations($count);
            $this->completeTest(ApiStatusType::Success);
            $this->info("API test completed");
            return static::SUCCESS;
        } catch (\Exception $e) {
            $this->completeTest(ApiStatusType::Failed);
            $this->error('API test failed');
            $this->error($e->getMessage());
            return static::FAILURE;
        }
    }

    private function runTestIterations(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->info("Running iteration {$i} of {$count}");

            foreach (Query::all()->shuffle() as $query) {
                $this->info("Running query: {$query->name}");
                $this->executeQueryTests($query);
            }
        }
    }

    private function executeQueryTests(Query $query): void
    {
        $preset = $query->activePreset;

        // REST test
        $this->createAndUpdateResult($preset, ApiType::Rest, function ($preset) {
            return $this->fetchRestData($preset);
        });

        // GraphQL test
        $this->createAndUpdateResult($preset, ApiType::Graphql, function ($preset) {
            return $this->fetchGraphQLData($preset);
        });

        // Integrated test
        $this->createAndUpdateResult($preset, ApiType::Integrated, function ($preset) {
            return $this->fetchIntegrated($preset);
        }, false);
    }

    private function createAndUpdateResult(QueryPreset $preset, ApiType $apiType, callable $fetchCallback, bool $matchRequestType = true): void
    {
        $result = $this->apiTest->results()->create([
            'query_id' => $preset->query_id,
            'preset_id' => $preset->id,
            'api_type' => $apiType,
            'request_type' => $matchRequestType ? $apiType : null,
            'status' => ApiStatusType::Processing,
        ]);

        $data = $fetchCallback($preset);
        $result->update($data);
    }

    private function fetchRestData(QueryPreset $query): array
    {
        try {
            $metrics = $this->captureMetrics(function () use ($query) {
                return Http::withToken($this->token)
                    ->get($this->restUrl . '/' . $query->rest_query);
            });

            if ($metrics['response']->failed()) {
                return $this->handleFailedRequest('REST', $query, 'rest');
            }

            return $this->handleSuccessfulRequest('REST', $metrics, $query, 'rest');
        } catch (\Exception $e) {
            return $this->handleRequestException('REST', $e, $query, 'rest');
        }
    }

    private function fetchGraphQLData(QueryPreset $query): array
    {
        try {
            $metrics = $this->captureMetrics(function () use ($query) {
                return Http::withToken($this->token)
                    ->post($this->graphqlUrl, ['query' => $query->graphql_query]);
            });

            if ($metrics['response']->failed() || isset($metrics['response']['errors'])) {
                return $this->handleFailedRequest('GraphQL', $query, 'graphql');
            }

            return $this->handleSuccessfulRequest('GraphQL', $metrics, $query, 'graphql');
        } catch (\Exception $e) {
            return $this->handleRequestException('GraphQL', $e, $query, 'graphql');
        }
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

    private function handleSuccessfulRequest(string $apiType, array $metrics, QueryPreset $query, string $cacheKey): array
    {
        $this->logMetrics($apiType, $metrics);

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

    private function handleFailedRequest(string $apiType, QueryPreset $query, string $cacheKey): array
    {
        $this->error("{$apiType} API Request Failed");

        $data = ['status' => ApiStatusType::Failed];
        $this->cacheResult($query, $cacheKey, $data);

        return $data;
    }

    private function handleRequestException(string $apiType, \Exception $e, QueryPreset $query, string $cacheKey): array
    {
        $this->error("{$apiType} API Error");
        $this->error($e->getMessage());

        $data = ['status' => ApiStatusType::Failed];
        $this->cacheResult($query, $cacheKey, $data);

        return $data;
    }

    private function logMetrics(string $apiType, array $metrics): void
    {
        $this->line("{$apiType} - Response time: {$metrics['response_time']}ms");
        $this->line("{$apiType} - Payload size: {$metrics['payload_size']} bytes");
        $this->line("{$apiType} - Memory usage: {$metrics['mem_usage']} bytes");
//        $this->line("{$apiType} - CPU time: {$metrics['cpu_time']}μs");
        $this->line("{$apiType} - CPU usage: {$metrics['cpu_usage']}%");
    }

    private function cacheResult(QueryPreset $query, string $type, array $data): void
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        Cache::put($key, $data);
    }

    private function getCachedResult(QueryPreset $query, string $type): ?array
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        return Cache::get($key);
    }

    private function fetchIntegrated(QueryPreset $query): array
    {
        $restCache = $this->getCachedResult($query, 'rest');
        $graphqlCache = $this->getCachedResult($query, 'graphql');

        if ($restCache && $graphqlCache) {
            return $this->fetchIntegratedFromBothCaches($query, $restCache, $graphqlCache);
        }

        if ($restCache) {
            return $this->fetchWithRequestType($query, ApiType::Graphql, 'rest cache found, using graphql');
        }

        if ($graphqlCache) {
            return $this->fetchWithRequestType($query, ApiType::Rest, 'graphql cache found, using rest');
        }

        return [
            'status' => ApiStatusType::Failed,
            'request_type' => ApiType::Integrated,
        ];
    }

    private function fetchIntegratedFromBothCaches(QueryPreset $query, array $restCache, array $graphqlCache): array
    {
        $this->line('Integrated: rest & graphql cache found, using integrated');

        $winner = $this->analyzeMetric($restCache, $graphqlCache, $query);
        $isRest = $winner === ApiType::Rest;

        $this->line('Integrated using ' . ($isRest ? 'REST' : 'GraphQL') . ' API');

        $data = $isRest ? $this->fetchRestData($query) : $this->fetchGraphQLData($query);
        $data['request_type'] = $winner;

        return $data;
    }

    private function fetchWithRequestType(QueryPreset $query, ApiType $apiType, string $message): array
    {
        $this->line("Integrated: {$message}");

        $data = $apiType === ApiType::Rest
            ? $this->fetchRestData($query)
            : $this->fetchGraphQLData($query);

        $data['request_type'] = $apiType;

        return $data;
    }

    private function analyzeMetric(array $rest, array $graphql, QueryPreset $query): ApiType
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

    private function completeTest(ApiStatusType $status): void
    {
        $this->apiTest->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }

    private function getCpuTime(): int
    {
        $usage = getrusage();

        $userTime = ($usage['ru_utime.tv_sec'] * 1000000) + $usage['ru_utime.tv_usec'];
        $systemTime = ($usage['ru_stime.tv_sec'] * 1000000) + $usage['ru_stime.tv_usec'];

        return $userTime + $systemTime;
    }
}
