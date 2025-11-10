<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class GithubCheckConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check connection to GitHub API (Rest & GraphQL)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('api.github.token');
        $restUrl = config('api.github.endpoint.rest');
        $graphqlUrl = config('api.github.endpoint.graphql');

        $this->checkRestApi($token, $restUrl);
        $this->checkGraphQLApi($token, $graphqlUrl);

        return static::SUCCESS;
    }

    private function checkRestApi(string $token, string $url): void
    {
        try {
            $response = Http::withToken($token)
                ->get($url . '/user');

            if ($response->successful()) {
                $this->info('REST API Connection: Success');
                $this->info('User: ' . $response->json('login'));
            } else {
                $this->error('REST API Connection: Failed');
                $this->error('Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('REST API Connection: Error');
            $this->error($e->getMessage());
        }
    }

    private function checkGraphQLApi(string $token, string $url): void
    {
        try {
            $query = <<<'GRAPHQL'
            query {
                viewer {
                    login
                }
            }
            GRAPHQL;

            $response = Http::withToken($token)
                ->post($url, ['query' => $query]);

            if ($response->successful() && !isset($response['errors'])) {
                $this->info('GraphQL API Connection: Success');
                $this->info('User: ' . $response->json('data.viewer.login'));
            } else {
                $this->error('GraphQL API Connection: Failed');
                $this->error('Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('GraphQL API Connection: Error');
            $this->error($e->getMessage());
        }
    }
}
