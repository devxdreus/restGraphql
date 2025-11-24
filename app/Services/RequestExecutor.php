<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use App\Models\QueryPreset;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class RequestExecutor
{
    private ?ApiType $requestType;

    public function __construct(
        private ApiClient    $client,
        private CacheManager $cacheManager
    )
    {
//        $this->client = ApiClient::make();
//        $this->cacheManager = new CacheManager();
    }

    public function fetchRestData(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        try {
            $metrics = MetricCollector::capture(function () use ($query) {
                return $this->client->getRestResponse($query->rest_query);
            });

            if ($metrics['response']->failed()) {
                return $this->storeAndCache($apiTest, $query, ApiType::Rest, ApiStatusType::Failed, $metrics);
            }

            return $this->storeAndCache($apiTest, $query, ApiType::Rest, ApiStatusType::Success, $metrics);
        } catch (\Exception $e) {
            return $this->storeAndCache($apiTest, $query, ApiType::Rest, ApiStatusType::Failed, []);
        }
    }

    public function fetchGraphQLData(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        try {
            $metrics = MetricCollector::capture(function () use ($query) {
                return $this->client->postGraphQL(['query' => $query->graphql_query]);
            });

            if ($metrics['response']->failed() || isset($metrics['response']['errors'])) {
                return $this->storeAndCache($apiTest, $query, ApiType::Graphql, ApiStatusType::Failed, $metrics);
            }

            return $this->storeAndCache($apiTest, $query, ApiType::Graphql, ApiStatusType::Success, $metrics);
        } catch (\Exception $e) {
            return $this->storeAndCache($apiTest, $query, ApiType::Graphql, ApiStatusType::Failed, []);
        }
    }

    public function fetchIntegrated(ApiTest $apiTest, QueryPreset $query): ApiTestResult
    {
        try {
            $metrics = MetricCollector::capture(function () use ($apiTest, $query) {
                return $this->executeIntegratedStrategy($query);
            });
        } catch (\Exception $e) {
            return $this->storeAndCache($apiTest, $query, ApiType::Integrated, ApiStatusType::Failed, [], $this->requestType);
        }

        if ($metrics['response']->failed() || isset($metrics['response']['errors'])) {
            return $this->storeAndCache($apiTest, $query, ApiType::Integrated, ApiStatusType::Failed, $metrics, $this->requestType);
        }

        return $this->storeAndCache($apiTest, $query, ApiType::Integrated, ApiStatusType::Success, $metrics, $this->requestType);
    }

    private function executeIntegratedStrategy(QueryPreset $query): Response
    {
        $restCache = $this->cacheManager->getCachedResult($query, ApiType::Rest->value);
        $graphqlCache = $this->cacheManager->getCachedResult($query, ApiType::Graphql->value);

        if (!$restCache && !$graphqlCache) {
            return $this->executePrimaryFallback($query, ApiType::Rest);
        }

        if (!$restCache) {
            return $this->executePrimaryFallback($query, ApiType::Graphql);
        }

        if (!$graphqlCache) {
            return $this->executePrimaryFallback($query, ApiType::Rest);
        }

        // Both cached, determine winner
        $winner = PerformanceEvaluator::analyzeMetrics($restCache, $graphqlCache, $query);
        return $this->executeWithFallback($query, $winner);
    }

    private function executePrimaryFallback(QueryPreset $query, ApiType $primaryType): Response
    {
        $response = $this->executeRequest($query, $primaryType);

        if ($this->isResponseFailed($response)) {
            $fallbackType = $this->getFallbackType($primaryType);
            $this->requestType = $fallbackType;
            return $this->executeRequest($query, $fallbackType);
        }

        $this->requestType = $primaryType;
        return $response;
    }

    private function executeWithFallback(QueryPreset $query, ApiType $primaryType): Response
    {
        $response = $this->executeRequest($query, $primaryType);

        if ($this->isResponseFailed($response)) {
            $fallbackType = $this->getFallbackType($primaryType);
            $this->requestType = $fallbackType;
            return $this->executeRequest($query, $fallbackType);
        }

        $this->requestType = $primaryType;
        return $response;
    }

    private function executeRequest(QueryPreset $query, ApiType $type): Response
    {
        return $type === ApiType::Rest
            ? $this->client->getRestResponse($query->rest_query)
            : $this->client->postGraphQL(['query' => $query->graphql_query]);
    }

    private function isResponseFailed(mixed $response): bool
    {
        return $response->failed() || isset($response['errors']);
    }

    private function getFallbackType(ApiType $primaryType): ApiType
    {
        return $primaryType === ApiType::Rest ? ApiType::Graphql : ApiType::Rest;
    }

    private function storeAndCache(
        ApiTest       $apiTest,
        QueryPreset   $query,
        ApiType       $apiType,
        ApiStatusType $status,
        array         $metrics,
        ?ApiType      $requestType = null,
    ): ApiTestResult
    {
        $result = ResponseFormatter::storeResult($apiTest, $query, $apiType, $status, $metrics, $requestType);

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
