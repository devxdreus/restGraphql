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

        $this->apiTest = ApiTest::create([
            'count' => $count,
        ]);

        try {
            for ($i = 1; $i <= $count; $i++) {
                $this->info("Running iteration {$i} of {$count}");

                foreach (Query::all() as $query) {
                    $this->info("Running query: {$query->name}");

                    $restResult = $this->apiTest->results()->create([
                        'query_id' => $query->id,
                        'preset_id' => $query->activePreset->id,
                        'api_type' => ApiType::Rest,
                        'request_type' => ApiType::Rest,
                        'status' => ApiStatusType::Processing,
                    ]);
                    $restData = $this->fetchRestData($query->activePreset);
                    $restResult->update($restData);

                    $graphqlResult = $this->apiTest->results()->create([
                        'query_id' => $query->id,
                        'preset_id' => $query->activePreset->id,
                        'api_type' => ApiType::Graphql,
                        'request_type' => ApiType::Graphql,
                        'status' => ApiStatusType::Processing,
                    ]);
                    $graphqlData = $this->fetchGraphQLData($query->activePreset);
                    $graphqlResult->update($graphqlData);

                    $integratedResult = $this->apiTest->results()->create([
                        'query_id' => $query->id,
                        'preset_id' => $query->activePreset->id,
                        'api_type' => ApiType::Integrated,
                        'status' => ApiStatusType::Processing,
                    ]);
                    $integratedData = $this->fetchIntegrated($query->activePreset);
                    $integratedResult->update($integratedData);
                }
            }

            $this->apiTest->update([
                'status' => ApiStatusType::Success,
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $this->apiTest->update([
                'status' => ApiStatusType::Failed,
                'completed_at' => now(),
            ]);

            $this->error('API test failed');
            $this->error($e->getMessage());
            return static::FAILURE;
        }

        $this->info("API test completed");

        return static::SUCCESS;
    }

    private function fetchRestData(QueryPreset $query): array
    {
        try {
            $startTime = microtime(true);
            $memoryBefore = memory_get_usage();

            $response = Http::withToken($this->token)
                ->get($this->restUrl . '/' . $query->rest_query);

            $memoryAfter = memory_get_usage();
            $endTime = microtime(true);

            $responseTime = round(($endTime - $startTime) * 1000);
            $payloadSize = strlen($response->body());
            $memoryUsage = $memoryAfter - $memoryBefore;

            if ($response->failed()) {
                $this->error('REST API Request Failed');
                $this->error('Status: ' . $response->status());

                $data = [
                    'status' => ApiStatusType::Failed,
                ];

                Cache::put("api_query_{$query->query_id}_preset_{$query->id}_rest", $data);

                return $data;
            }

            $this->line("REST - Response time: {$responseTime}ms");
            $this->line("REST - Payload size: {$payloadSize} bytes");
            $this->line("REST - Memory usage: {$memoryUsage} bytes");

            $data = [
                'status' => ApiStatusType::Success,
                'response_time' => $responseTime,
                'payload_size' => $payloadSize,
                'mem_usage' => $memoryUsage,
            ];

            Cache::put("api_query_{$query->query_id}_preset_{$query->id}_rest", $data);

            $data['response'] = $response->json();
            return $data;

        } catch (\Exception $e) {
            $this->error('REST API Error');
            $this->error($e->getMessage());

            $data = [
                'status' => ApiStatusType::Failed,
            ];

            Cache::put("api_query_{$query->query_id}_preset_{$query->id}_rest", $data);

            return $data;
        }
    }

    private function fetchGraphQLData(QueryPreset $query): array
    {
        try {
            $startTime = microtime(true);
            $memoryBefore = memory_get_usage();

            $response = Http::withToken($this->token)
                ->post($this->graphqlUrl, ['query' => $query->graphql_query]);

            $memoryAfter = memory_get_usage();
            $endTime = microtime(true);

            $responseTime = round(($endTime - $startTime) * 1000);
            $payloadSize = strlen($response->body());
            $memoryUsage = $memoryAfter - $memoryBefore;

            if ($response->failed() || isset($response['errors'])) {
                $this->error('GraphQL API Request Failed');
                $this->error('Status: ' . $response->status());

                $data = [
                    'status' => ApiStatusType::Failed,
                ];

                Cache::put("api_query_{$query->query_id}_preset_{$query->id}_graphql", $data);

                return $data;
            }

            $this->line("GraphQL - Response time: {$responseTime}ms");
            $this->line("GraphQL - Payload size: {$payloadSize} bytes");
            $this->line("GraphQL - Memory usage: {$memoryUsage} bytes");

            $data = [
                'status' => ApiStatusType::Success,
                'response_time' => $responseTime,
                'payload_size' => $payloadSize,
                'mem_usage' => $memoryUsage,
            ];

            Cache::put("api_query_{$query->query_id}_preset_{$query->id}_graphql", $data);

            $data['response'] = $response->json();
            return $data;

        } catch (\Exception $e) {
            $this->error('GraphQL API Error');
            $this->error($e->getMessage());

            $data = [
                'status' => ApiStatusType::Failed,
            ];

            Cache::put("api_query_{$query->query_id}_preset_{$query->id}_graphql", $data);

            return $data;
        }
    }

    private function fetchIntegrated(QueryPreset $query): array
    {
        $restCache = Cache::get("api_query_{$query->query_id}_preset_{$query->id}_rest");
        $graphqlCache = Cache::get("api_query_{$query->query_id}_preset_{$query->id}_graphql");

        if ($restCache && $graphqlCache) {
            $this->line('Integrated: rest & graphql cache found, using integrated');

            $result = $this->analyzeMetric($restCache, $graphqlCache, $query);

            if ($result === ApiType::Rest) {
                $this->line('Integrated using REST API');
                $data = $this->fetchRestData($query);
            } else {
                $this->line('Integrated using GraphQL API');
                $data = $this->fetchGraphQLData($query);
            }

            $data['request_type'] = $result;

            return $data;
        }

        if ($restCache) {
            $this->line('Integrated: rest cache found, using graphql');

            $graphqlData = $this->fetchGraphQLData($query);

            $graphqlData['request_type'] = ApiType::Graphql;

            return $graphqlData;
        }

        if ($graphqlCache) {
            $this->line('Integrated: graphql cache found, using rest');

            $restData = $this->fetchRestData($query);

            $restData['request_type'] = ApiType::Rest;

            return $restData;
        }

        return [
            'status' => ApiStatusType::Failed,
            'request_type' => ApiType::Integrated,
        ];
    }

    private function analyzeMetric(array $rest, array $graphql, QueryPreset $query): ApiType
    {
        if ($rest['status'] === ApiStatusType::Failed) {
            return ApiType::Graphql;
        }
        if ($graphql['status'] === ApiStatusType::Failed) {
            return ApiType::Rest;
        }

        $weight = [
            'response_time' => 0.5,
            'payload_size' => 0.2,
            'mem_usage' => 0.3,
        ];

        // build min max accross all metric
        $metric = ['response_time', 'payload_size', 'mem_usage'];
        $mins = [];
        $maxs = [];

        foreach ($metric as $m) {
            $min = $query->testResults()->success()->min($m);
            $max = $query->testResults()->success()->max($m);

            $this->warn("$m - min: {$min} max: {$max}");

            if ($min === $max) { // avoid division by zero; neutral band
                $min -= 1.0;
                $max += 1.0;
            }

            $mins[$m] = $min;
            $maxs[$m] = $max;
        }

        $norm = function (array $x) use ($metric, $mins, $maxs): array {
            $out = [];
            foreach ($metric as $m) {
                $val = (float)($x[$m] ?? 0);
                $den = $maxs[$m] - $mins[$m];
                $out[$m] = $den != 0.0 ? max(0.0, min(1.0, ($val - $mins[$m]) / $den)) : 0.5;
            }
            return $out;
        };

        $norm_rest = $norm($rest);
        $norm_graphql = $norm($graphql);

        $this->warn('Rest norm: ' . json_encode($norm_rest));
        $this->warn('GraphQL norm: ' . json_encode($norm_graphql));

        $score = function (array $xN) use ($weight): float {
            return
                $weight['response_time'] * $xN['response_time'] +
                $weight['payload_size'] * $xN['payload_size'] +
                $weight['mem_usage'] * $xN['mem_usage'];
        };

        $score_rest = $score($norm_rest);
        $score_graphql = $score($norm_graphql);

        $this->warn("Score: rest $score_rest, graphql $score_graphql");;

        if (abs($score_rest - $score_graphql) < 1e-9) {
            $winner = ($a['payload_size'] ?? INF) <= ($b['payload_size'] ?? INF) ? ApiType::Rest : ApiType::Graphql;
        } else {
            $winner = ($score_rest < $score_graphql) ? ApiType::Rest : ApiType::Graphql;
        }

        return $winner;
    }
}
