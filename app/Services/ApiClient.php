<?php

namespace App\Services;

use App\Enums\ApiType;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    private readonly string $token;
    private readonly string $restUrl;
    private readonly string $graphqlUrl;

    public function __construct()
    {
        $this->token = config('api.github.token');
        $this->restUrl = config('api.github.endpoint.rest');
        $this->graphqlUrl = config('api.github.endpoint.graphql');
    }

    public static function make(): ApiClient
    {
        return new self();
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
