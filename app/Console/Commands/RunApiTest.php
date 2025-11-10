<?php

namespace App\Console\Commands;

use App\Models\Query;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RunApiTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:run {--count=1 : Number of times to run the test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('api.github.token');
        $restUrl = config('api.github.endpoint.rest');
        $graphqlUrl = config('api.github.endpoint.graphql');
        $count = $this->option('count');

        $query = Query::find(1)->activePreset;

        for ($i = 1; $i <= $count; $i++) {
            $this->info("Running iteration {$i} of {$count}");
            $this->fetchRestData($token, $restUrl, $query->rest_query);
            $this->fetchGraphQLData($token, $graphqlUrl, $query->graphql_query);
        }

        return static::SUCCESS;
    }

    private function fetchRestData(string $token, string $url, string $query): void
    {
        try {
            $startTime = microtime(true);
            $memoryBefore = memory_get_usage();
            $response = Http::withToken($token)
                ->get($url . '/' . $query);
            $memoryAfter = memory_get_usage();
            $endTime = microtime(true);

            $responseTime = round(($endTime - $startTime) * 1000);
            $payloadSize = strlen($response->body());
            $memoryUsage = $memoryAfter - $memoryBefore;

            if ($response->successful()) {
                $this->line("REST - Response time: {$responseTime}ms");
                $this->line("REST - Payload size: {$payloadSize} bytes");
                $this->line("REST - Memory usage: {$memoryUsage} bytes");
            } else {
                $this->error('REST API Request Failed');
                $this->error('Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('REST API Error');
            $this->error($e->getMessage());
        }
    }

    private function fetchGraphQLData(string $token, string $url, string $query): void
    {
        try {
            $startTime = microtime(true);
            $memoryBefore = memory_get_usage();
            $response = Http::withToken($token)
                ->post($url, ['query' => $query]);
            $memoryAfter = memory_get_usage();
            $endTime = microtime(true);

            $responseTime = round(($endTime - $startTime) * 1000);
            $payloadSize = strlen($response->body());
            $memoryUsage = $memoryAfter - $memoryBefore;

            if ($response->successful() && !isset($response['errors'])) {
                $this->line("GraphQL - Response time: {$responseTime}ms");
                $this->line("GraphQL - Payload size: {$payloadSize} bytes");
                $this->line("GraphQL - Memory usage: {$memoryUsage} bytes");
            } else {
                $this->error('GraphQL API Request Failed');
                $this->error('Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('GraphQL API Error');
            $this->error($e->getMessage());
        }
    }
}
