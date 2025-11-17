<?php

namespace App\Services;

use App\Enums\ApiType;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    public function __construct(
        private readonly string $token,
        private readonly string $restUrl,
        private readonly string $graphqlUrl
    )
    {
    }

    public static function make(): self
    {
        return new self(
            config('api.github.token'),
            config('api.github.endpoint.rest'),
            config('api.github.endpoint.graphql')
        );
    }

    public function getRestResponse(string $endpoint): Response
    {
        return Http::withToken($this->token)
            ->get($this->restUrl . '/' . $endpoint);
    }

    public function postGraphQL(array $query): Response
    {
        return Http::withToken($this->token)
            ->post($this->graphqlUrl, $query);
    }
}
