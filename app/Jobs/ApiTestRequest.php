<?php

namespace App\Jobs;

use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\QueryPreset;
use App\Services\RequestExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApiTestRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ApiType     $apiType,
        public ApiTest     $apiTest,
        public QueryPreset $preset,
    )
    {
    }

    public function handle(): void
    {
        $executor = new RequestExecutor();

        if ($this->apiType === ApiType::Rest) {
            $executor->fetchRestData($this->apiTest, $this->preset);
        }

        if ($this->apiType === ApiType::Graphql) {
            $executor->fetchGraphQLData($this->apiTest, $this->preset);
        }

        if ($this->apiType === ApiType::Integrated) {
            $executor->fetchIntegrated($this->apiTest, $this->preset);
        }
    }
}
