<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Jobs\ApiTestRequest;
use App\Models\ApiTest;
use App\Models\Query;
use App\Models\QueryPreset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TestDispatcher
{
    public function dispatchTests(int $count = 1): ApiTest
    {
        Cache::flush();

        $apiTest = ApiTest::create(['count' => $count]);

        for ($i = 1; $i <= $count; $i++) {
            foreach (Query::all() as $query) {
                $this->dispatchQueryTests($apiTest, $query->activePreset);
            }
        }

        $apiTest->update([
            'status' => ApiStatusType::Success,
            'completed_at' => now(),
        ]);

        return $apiTest;
    }

    public function dispatchQueryTests(ApiTest $apiTest, QueryPreset $preset): void
    {
        ApiTestRequest::dispatch(\App\Enums\ApiType::Rest, $apiTest, $preset);

        ApiTestRequest::dispatch(\App\Enums\ApiType::Graphql, $apiTest, $preset);

        ApiTestRequest::dispatch(\App\Enums\ApiType::Integrated, $apiTest, $preset);
    }
}
