<?php

return [
    'github' => [
        'token' => env('GITHUB_TOKEN'),

        'endpoint' => [
            'rest' => env('GITHUB_REST_URL', 'https://api.github.com'),
            'graphql' => env('GITHUB_GRAPHQL_URL', 'https://api.github.com/graphql'),
        ]
    ]
];
