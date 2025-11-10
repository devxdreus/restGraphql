<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

class GithubCheckLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check GitHub API limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('api.github.token');
        $restUrl = config('api.github.endpoint.rest');
        $graphqlUrl = config('api.github.endpoint.graphql');

        $this->checkRestApiLimit($token, $restUrl);
        $this->checkGraphQLApiLimit($token, $graphqlUrl);

        return static::SUCCESS;
    }

    private function checkRestApiLimit(string $token, string $url): void
    {
        try {
            $response = Http::withToken($token)
                ->get($url . '/rate_limit');

            if ($response->successful()) {
                $limits = $response->json('resources.core');
                $this->info('REST API Rate Limit:');
                $this->info('Limit: ' . $limits['limit']);
                $this->info('Remaining: ' . $limits['remaining']);
                $this->info('Reset Time: ' . date('Y-m-d H:i:s', $limits['reset']));
            } else {
                $this->error('REST API Rate Limit Check: Failed');
                $this->error('Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('REST API Rate Limit Check: Error');
            $this->error($e->getMessage());
        }
    }

    private function checkGraphQLApiLimit(string $token, string $url): void
    {
        try {
            $query = <<<'GRAPHQL'
            query {
                rateLimit {
                    limit
                    remaining
                    resetAt
                }
            }
            GRAPHQL;

            $response = Http::withToken($token)
                ->post($url, ['query' => $query]);

            if ($response->successful() && !isset($response['errors'])) {
                $limits = $response->json('data.rateLimit');
                $this->info('GraphQL API Rate Limit:');
                $this->info('Limit: ' . $limits['limit']);
                $this->info('Remaining: ' . $limits['remaining']);
                $this->info('Reset Time: ' . $limits['resetAt']);
            } else {
                $this->error('GraphQL API Rate Limit Check: Failed');
                $this->error('Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('GraphQL API Rate Limit Check: Error');
            $this->error($e->getMessage());
        }
    }
}
