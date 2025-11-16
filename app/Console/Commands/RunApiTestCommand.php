<?php

namespace App\Console\Commands;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Jobs\ApiTestRequest;
use App\Jobs\RunApiTestJob;
use App\Models\ApiTest;
use App\Models\Query;
use App\Models\QueryPreset;
use App\Services\ApiTestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RunApiTestCommand extends Command
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
            for ($i = 1; $i <= $count; $i++) {
                $this->info("Running iteration {$i} of {$count}");

                foreach (Query::all()->shuffle() as $query) {
                    $this->info("Running query: {$query->name}");
                    $this->dispatchQueryTests($query);
                }
            }

            $this->completeTest(ApiStatusType::Success);
            $this->info("API test completed successfully");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->completeTest(ApiStatusType::Failed);
            $this->error('API test failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function executeQueryTests(Query $query): void
    {
        $preset = $query->activePreset;

        // REST test
        $restData = $this->apiTestService->fetchRestData($this->apiTest, $preset);
        $this->logTestResult('REST', $restData);

        // GraphQL test
        $graphqlData = $this->apiTestService->fetchGraphQLData($this->apiTest, $preset);
        $this->logTestResult('GraphQL', $graphqlData);

        // Integrated test
        $integratedData = $this->apiTestService->fetchIntegrated($this->apiTest, $preset);
        if (isset($integratedData['request_type'])) {
            $this->line('Integrated: menggunakan ' . $integratedData['request_type']->value . ' API');
        }
        $this->logTestResult('Integrated', $integratedData);
    }

    private function dispatchQueryTests(Query $query): void
    {
        $preset = $query->activePreset;

        $this->line('Dispatching REST request for ' . $query->name);
        ApiTestRequest::dispatch(ApiType::Rest, $this->apiTest, $preset);

        $this->line('Dispatching GraphQL request for ' . $query->name);
        ApiTestRequest::dispatch(ApiType::Graphql, $this->apiTest, $preset);

        $this->line('Dispatching Integrated request for ' . $query->name);
        ApiTestRequest::dispatch(ApiType::Integrated, $this->apiTest, $preset);
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
