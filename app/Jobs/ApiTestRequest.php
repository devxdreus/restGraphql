<?php

namespace App\Jobs;

use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\QueryPreset;
use App\Services\ApiTestService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApiTestRequest implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ApiType     $apiType,
        public ApiTest     $apiTest,
        public QueryPreset $preset,
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->apiType === ApiType::Rest) {
            ApiTestService::make()->fetchRestData($this->apiTest, $this->preset);
        }

        if ($this->apiType === ApiType::Graphql) {
            ApiTestService::make()->fetchGraphQLData($this->apiTest, $this->preset);
        }

        if ($this->apiType === ApiType::Integrated) {
            ApiTestService::make()->fetchIntegrated($this->apiTest, $this->preset);
        }
    }
}
