<?php

namespace App\Services;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\QueryPreset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            if ($query->query_id < 15) {
                Log::error($apiType->value . ' failed', @$metrics['response']?->collect()->toArray() ?? []);
            } else {
                Log::error($apiType->value . ' failed', json_decode(json_encode(simplexml_load_string(@$metrics['response']->body())), true));
            }
        }

        $data = array_merge([
            'query_id' => $query->query_id,
            'preset_id' => $query->id,
            'api_type' => $apiType,
            'status' => $status,
            'request_type' => $apiType,
        ], $metrics ?? [], $extraData);

        if (isset($data['response'])) {
            if (Str::contains($data['response']->header('content-type'), 'json')) {
                $data['response'] = $data['response']->collect() ?? [];
            } else {
                $data['response'] = json_decode(json_encode(simplexml_load_string($data['response']->body())), true);;
            }

        }

        return $apiTest->results()->create($data);
    }
}
