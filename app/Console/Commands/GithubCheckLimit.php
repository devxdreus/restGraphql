<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GithubCheckLimit extends Command
{
    protected $signature = 'github:limit';

    protected $description = 'Check GitHub API limit';

    public function handle(): int
    {
        $token = config('api.github.token');
        $restUrl = config('api.github.endpoint.rest');
        $graphqlUrl = config('api.github.endpoint.graphql');

        $restResult = $this->checkRestApiLimit($token, $restUrl);
        $graphQlResult = $this->checkGraphQLApiLimit($token, $graphqlUrl);

        $headers = ['Type', 'Limit', 'Remaining', 'Reset Time'];
        $results = [$restResult, $graphQlResult];

        $this->table($headers, $results);

        return static::SUCCESS;
    }

    private function checkRestApiLimit(string $token, string $url): array
    {
        $result = [
            'Type' => 'REST API',
            'Limit' => 'N/A',
            'Remaining' => 'N/A',
            'Reset Time' => 'N/A',
        ];

        try {
            $response = Http::withToken($token)->get($url . '/rate_limit');

            if ($response->successful()) {
                $limits = $response->json('resources.core');
                $result['Limit'] = $limits['limit'];
                $result['Remaining'] = $limits['remaining'];
                $result['Reset Time'] = Carbon::createFromTimestampUTC($limits['reset'])->setTimezone('Asia/Makassar')->format('d-m-Y H:i:s');
            } else {
                $result['Limit'] = 'Error';
                $result['Remaining'] = 'Failed';
                $result['Reset Time'] = 'Status: ' . $response->status();
            }
        } catch (\Exception $e) {
            $result['Limit'] = 'Error';
            $result['Remaining'] = 'Exception';
            $result['Reset Time'] = $e->getMessage();
        }

        return $result;
    }

    private function checkGraphQLApiLimit(string $token, string $url): array
    {
        $result = [
            'Type' => 'GraphQL API',
            'Limit' => 'N/A',
            'Remaining' => 'N/A',
            'Reset Time' => 'N/A',
        ];

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

            $response = Http::withToken($token)->post($url, ['query' => $query]);

            if ($response->successful() && !isset($response['errors'])) {
                $limits = $response->json('data.rateLimit');
                $result['Limit'] = $limits['limit'];
                $result['Remaining'] = $limits['remaining'];
                $result['Reset Time'] = Carbon::parse($limits['resetAt'])
                    ->setTimezone('Asia/Makassar')
                    ->format('d-m-Y H:i:s');
            } else {
                $result['Limit'] = 'Error';
                $result['Remaining'] = 'Failed';
                $result['Reset Time'] = 'Status: ' . $response->status() . (isset($response['errors']) ? ', ' . json_encode($response['errors']) : '');
            }
        } catch (\Exception $e) {
            $result['Limit'] = 'Error';
            $result['Remaining'] = 'Exception';
            $result['Reset Time'] = $e->getMessage();
        }

        return $result;
    }
}
