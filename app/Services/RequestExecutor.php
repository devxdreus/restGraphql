<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use App\Models\QueryPreset;
use Illuminate\Support\Facades\Log;

class RequestExecutor
{
    public function __construct(
        private ApiClient    $client,
        private CacheManager $cacheManager
    )
    {
//        $this->client = ApiClient::make();
//        $this->cacheManager = new CacheManager();
    }

    public function fetchRestData(ApiTest $apiTest, QueryPreset $query, ?ApiTestResult $result = null): ApiTestResult
    {
        // query yang menggunakan endpoint search
        // untuk menghindari rate limit
        if (in_array($query->query_id, [1, 4, 8, 10, 13])) {
            sleep(2);
        }

        try {
            $metrics = MetricCollector::capture(function () use ($query) {
                if ($query->query_id < 15) {
                    return $this->client->getRestResponse($query->rest_query);
                }
                return $this->client->getArxivRestResponse($query->rest_query);
            });

            if ($metrics['response']->failed()) {
                return $this->storeAndCache($apiTest, $query, ApiType::Rest, ApiStatusType::Failed, $metrics, $result);
            }

            return $this->storeAndCache($apiTest, $query, ApiType::Rest, ApiStatusType::Success, $metrics, $result);
        } catch (\Exception $e) {
            return $this->storeAndCache($apiTest, $query, ApiType::Rest, ApiStatusType::Failed, [], $result);
        }
    }

    public function fetchGraphQLData(ApiTest $apiTest, QueryPreset $query, ?ApiTestResult $result = null): ApiTestResult
    {
        try {
            $metrics = MetricCollector::capture(function () use ($query) {
                if ($query->query_id < 15) {
                    return $this->client->postGraphQL(['query' => $query->graphql_query]);
                }
                return $this->client->getArxivGraphQLResponse(['query' => $query->graphql_query]);
            });

            if ($metrics['response']->failed() || isset($metrics['response']['errors'])) {
                return $this->storeAndCache($apiTest, $query, ApiType::Graphql, ApiStatusType::Failed, $metrics, $result);
            }

            return $this->storeAndCache($apiTest, $query, ApiType::Graphql, ApiStatusType::Success, $metrics, $result);
        } catch (\Exception $e) {
            return $this->storeAndCache($apiTest, $query, ApiType::Graphql, ApiStatusType::Failed, [], $result);
        }
    }

    public function fetchIntegrated(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        $restCache = $this->getOrFetchMetricResult($apiTest, $query, ApiType::Rest);
        $graphqlCache = $this->getOrFetchMetricResult($apiTest, $query, ApiType::Graphql);

        $winner = PerformanceEvaluator::analyzeMetrics($restCache, $graphqlCache, $query);

        if ($winner === ApiType::Rest) {
            $resultData = $this->fetchRestData($apiTest, $query);

        } else {
            $resultData = $this->fetchGraphQLData($apiTest, $query);
        }

        if ($resultData->status === ApiStatusType::Failed) {
            $resultData = $this->fetchFallbackApi($apiTest, $query, $resultData);
        }

        $resultData->update(['api_type' => ApiType::Integrated]);

        return $resultData;
    }

    private function getOrFetchMetricResult(ApiTest $apiTest, QueryPreset $query, ApiType $type): array
    {
        $cached = $this->cacheManager->getCachedResult($query, $type->value);

        if ($cached) {
            return $cached;
        }

        // Lakukan fetching tanpa menyimpan ke DB
        if ($type === ApiType::Rest) {
            $metrics = MetricCollector::capture(function () use ($query) {
                if ($query->query_id < 15) {
                    return $this->client->getRestResponse($query->rest_query);
                }
                return $this->client->getArxivRestResponse($query->rest_query);
            });

            $status = !$metrics['response']->failed() ? ApiStatusType::Success : ApiStatusType::Failed;
        } else { // GraphQL
            $metrics = MetricCollector::capture(function () use ($query) {
                if ($query->query_id < 15) {
                    return $this->client->postGraphQL(['query' => $query->graphql_query]);
                }
                return $this->client->getArxivGraphQLResponse(['query' => $query->graphql_query]);
            });

            $status = (!$metrics['response']->failed() && !isset($metrics['response']['errors']))
                ? ApiStatusType::Success
                : ApiStatusType::Failed;
        }

        $cachedData = [
            'status' => $status,
            'response_time' => $metrics['response_time'] ?? 0,
            'payload_size' => $metrics['payload_size'] ?? 0,
            'mem_usage' => $metrics['mem_usage'] ?? 0,
            'cpu_usage' => $metrics['cpu_usage'] ?? 0,
        ];

        $this->cacheManager->putCachedResult($query, $type->value, $cachedData);

        return $cachedData;
    }

    private function fetchFallbackApi(ApiTest $apiTest, QueryPreset $query, ApiTestResult $result): ApiTestResult
    {
        if ($result->request_type === ApiType::Rest) {
            $resultData = $this->fetchGraphQLData($apiTest, $query, $result);
        } else {
            $resultData = $this->fetchRestData($apiTest, $query, $result);
        }

        return $resultData;
    }

    private function storeAndCache(
        ApiTest        $apiTest,
        QueryPreset    $query,
        ApiType        $apiType,
        ApiStatusType  $status,
        array          $metrics,
        ?ApiTestResult $testResult = null
    ): ApiTestResult
    {
        if ($testResult) {
            $result = ResponseFormatter::updateResult($query, $apiType, $status, $metrics, $testResult);
        } else {
            $result = ResponseFormatter::storeResult($apiTest, $query, $apiType, $status, $metrics);
        }

        $cachedData = array_merge($result->toArray(), $metrics);
        unset($cachedData['response']);
        $this->cacheManager->putCachedResult($query, $apiType->value, $cachedData);

        $this->markIfCompleted($apiTest);

        return $result;
    }

    private function markIfCompleted(ApiTest $apiTest): void
    {
        $expectedTotal = $apiTest->count * Query::count() * count(ApiType::values());
        if ($expectedTotal == $apiTest->results()->count()) {
            $apiTest->update([
                'status' => ApiStatusType::Success,
                'completed_at' => now(),
            ]);
        }
    }
}
