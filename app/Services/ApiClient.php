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
    private mixed $arxivRest;
    private mixed $arxivGraphql;

    public function __construct()
    {
        $this->token = config('api.github.token');
        $this->restUrl = config('api.github.endpoint.rest');
        $this->graphqlUrl = config('api.github.endpoint.graphql');

        $this->arxivRest = config('api.arxiv.endpoint.rest');
        $this->arxivGraphql = config('api.arxiv.endpoint.graphql');
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

    public function getArxivRestResponse(string $endpoint): Response
    {
        return Http::get($this->arxivRest . '/' . $endpoint);
    }

    public function getArxivGraphQLResponse(array $query): Response
    {
        return Http::post($this->arxivGraphql, $query);
    }
}
