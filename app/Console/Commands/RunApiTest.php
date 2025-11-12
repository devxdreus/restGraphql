<?php

namespace App\Console\Commands;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\Query;
use App\Models\QueryPreset;
use App\Services\ApiTestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RunApiTest extends Command
{
    protected $signature = 'api:run {--count=1 : Number of times to run the test}';

    protected $description = 'Run API performance tests comparing REST, GraphQL, and Integrated approaches';

    private ?ApiTest $apiTest = null;
    private ApiTestService $apiTestService;

    public function __construct()
    {
        parent::__construct();

        $this->apiTestService = ApiTestService::make();
    }

    public function handle(): int
    {
        $count = (int)$this->option('count');
        $this->info("Running API test with {$count} iterations");

        Cache::flush();
        $this->call('github:limit');

        $this->apiTest = ApiTest::create(['count' => $count]);

        try {
            $this->runTestIterations($count);
            $this->completeTest(ApiStatusType::Success);
            $this->info("API test completed successfully");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->completeTest(ApiStatusType::Failed);
            $this->error('API test failed: ' . $e->getMessage());
            return self::FAILURE;
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
        $this->runTest($preset, ApiType::Rest, function ($preset) {
            $data = $this->apiTestService->fetchRestData($preset);
            $this->logTestResult('REST', $data);
            return $data;
        });

        // GraphQL test
        $this->runTest($preset, ApiType::Graphql, function ($preset) {
            $data = $this->apiTestService->fetchGraphQLData($preset);
            $this->logTestResult('GraphQL', $data);
            return $data;
        });

        // Integrated test
        $this->runTest($preset, ApiType::Integrated, function ($preset) {
            $restCache = $this->apiTestService->getCachedResult($preset, 'rest');
            $graphqlCache = $this->apiTestService->getCachedResult($preset, 'graphql');

            if ($restCache && $graphqlCache) {
                $this->line('Integrated: both caches found, analyzing metrics');
            } elseif ($restCache) {
                $this->line('Integrated: REST cache found, using GraphQL');
            } elseif ($graphqlCache) {
                $this->line('Integrated: GraphQL cache found, using REST');
            }

            $data = $this->apiTestService->fetchIntegrated($preset);

            if (isset($data['request_type'])) {
                $this->line('Integrated: using ' . $data['request_type']->value . ' API');
            }

            $this->logTestResult('Integrated', $data);
            return $data;
        }, false);
    }

    private function runTest(
        QueryPreset $preset,
        ApiType     $apiType,
        callable    $fetchCallback,
        bool        $matchRequestType = true
    ): void
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

    private function logTestResult(string $apiType, array $data): void
    {
        if ($data['status'] === ApiStatusType::Failed) {
            $this->error("{$apiType}: Request failed");
            return;
        }

        $this->line("{$apiType} - Response time: {$data['response_time']}ms");
        $this->line("{$apiType} - Payload size: {$data['payload_size']} bytes");
        $this->line("{$apiType} - Memory usage: {$data['mem_usage']} bytes");
        $this->line("{$apiType} - CPU usage: {$data['cpu_usage']}%");
    }

    private function completeTest(ApiStatusType $status): void
    {
        $this->apiTest->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }
}
