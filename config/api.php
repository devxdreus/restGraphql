<?php

return [
    'github' => [
        'token' => env('GITHUB_TOKEN'),

        'endpoint' => [
            'rest' => env('GITHUB_REST_URL', 'https://api.github.com'),
            'graphql' => env('GITHUB_GRAPHQL_URL', 'https://api.github.com/graphql'),
        ]
    ],

    'arxiv' => [
        'endpoint' => [
            'rest' => env('ARXIV_REST_URL', 'http://export.arxiv.org/api'),
            'graphql' => env('ARXIV_GRAPHQL_URL', 'http://172.26.230.80:5000/graphql')
        ]
    ]
];
