<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\QueryPreset;
use Illuminate\Support\Facades\Log;

class ResponseFormatter
{
    public static function storeResult(
        ApiTest       $apiTest,
        QueryPreset   $query,
        ApiType       $apiType,
        ApiStatusType $status,
                      $metrics = null,
        array         $extraData = []
    ): ApiTestResult
    {
        if ($status == ApiStatusType::Failed) {
            Log::error($apiType->value . ' failed', @$metrics['response']?->collect()->toArray() ?? []);
        }

        $data = array_merge([
            'query_id' => $query->query_id,
            'preset_id' => $query->id,
            'api_type' => $apiType,
            'status' => $status,
            'request_type' => $apiType,
        ], $metrics ?? [], $extraData);

        if (isset($data['response'])) {
            $data['response'] = $data['response']->collect() ?? [];
        }

        return $apiTest->results()->create($data);
    }
}
