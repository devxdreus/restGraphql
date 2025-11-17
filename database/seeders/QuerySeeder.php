<?php

namespace Database\Seeders;

use App\Enums\QueryType;
use App\Models\Query;
use Illuminate\Database\Seeder;

class QuerySeeder extends Seeder
{
    public function run(): void
    {
        $simple = QueryType::Simple->value;
        $complex = QueryType::Complex->value;

        $queries = [
            'Q1' => [
                'name' => 'Q1',
                'type' => $simple,
                'description' => 'Query to search repositories with stars > 1',
                'rest_query' => 'search/repositories?q=stars:>1&sort=stars&order=desc&per_page=100',
                'graphql_query' => 'query {
    search(query: "stars:>1", type: REPOSITORY, first: 100) {
        nodes {
            ... on Repository {
                name
            }
        }
    }
}
            ',
            ],
            'Q2' => [
                'name' => 'Q2',
                'type' => $complex,
                'description' => 'Top 10 repos with stars > 1000 and their PR details',
                'rest_query' => 'search/repositories?q=stars:>1000&sort=stars&order=desc&per_page=10',
                'graphql_query' => 'query {
    search(query: "stars:>1000", type: REPOSITORY, first: 10) {
        nodes {
            ... on Repository {
                name
                pullRequests(first: 100) {
                    totalCount
                    nodes {
                        title
                        body
                        createdAt
                        author { login }
                    }
                }
            }
        }
    }
}
',
            ],
            'Q3' => [
                'name' => 'Q3',
                'type' => $simple,
                'description' => 'Comments on PR #2 of facebook/react',
                'rest_query' => 'repos/facebook/react/pulls/2/comments',
                'graphql_query' => 'query {
    repository(owner: "facebook", name: "react") {
        pullRequest(number: 2) {
            comments(first: 100) {
                nodes { body }
            }
        }
    }
}
            ',
            ],
            'Q4' => [
                'name' => 'Q4',
                'type' => $simple,
                'description' => 'Top 5 repos with stars > 1 (name & url)',
                'rest_query' => 'search/repositories?q=stars:>1&sort=stars&order=desc&per_page=5',
                'graphql_query' => 'query {
    search(query: "stars:>1", type: REPOSITORY, first: 5) {
        nodes {
            ... on Repository {
                name
                url
            }
        }
    }
}
            ',
            ],
            'Q5' => [
                'name' => 'Q5',
                'type' => $simple,
                'description' => 'Repos with stars > 10000 and repo metadata counts (refs, issues, releases, users, commits)',
                'rest_query' => 'search/repositories?q=stars:>10000&sort=stars&order=desc&per_page=7',
                'graphql_query' => 'query {
    search(query: "stars:>10000", type: REPOSITORY, first: 7) {
        nodes {
            ... on Repository {
                name
                refs(refPrefix: "refs/heads/", first: 100) { totalCount }
                issues(states: OPEN, labels: ["bug"], first: 0) { totalCount }
                releases(first: 0) { totalCount }
                mentionableUsers(first: 0) { totalCount }
                defaultBranchRef {
                    target {
                        ... on Commit {
                            history { totalCount }
                        }
                    }
                }
            }
        }
    }
}
            ',
            ],
            'Q6' => [
                'name' => 'Q6',
                'type' => $complex,
                'description' => 'Closed issues with label bug in facebook/react (latest 100)',
                'rest_query' => 'repos/facebook/react/issues?state=closed&labels=bug&per_page=100&page=1',
                'graphql_query' => 'query {
    repository(owner: "facebook", name: "react") {
        issues(states: CLOSED, labels: ["bug"], first: 100, orderBy: {field: CREATED_AT, direction: DESC}) {
            nodes { title body }
        }
    }
}
            ',
            ],
            'Q7' => [
                'name' => 'Q7',
                'type' => $simple,
                'description' => 'Comments on issue #10 for a given repo (fallback: facebook/react)',
                'rest_query' => 'repos/facebook/react/issues/10/comments',
                'graphql_query' => 'query {
    repository(owner: "facebook", name: "react") {
        issue(number: 10) {
            comments(first: 100) {
                nodes {
                    body
                }
            }
        }
    }
}
            ',
            ],
            'Q8' => [
                'name' => 'Q8',
                'type' => $simple,
                'description' => 'Java repositories with stars > 10 (top 50) + metadata',
                'rest_query' => 'search/repositories?q=language:java+stars:>10&sort=stars&order=desc',
                'graphql_query' => 'query {
    search(query: "language:java stars:>10", type: REPOSITORY, first: 50) {
        nodes {
            ... on Repository {
                name
                url
                description
                stargazerCount
                createdAt
                pushedAt
            }
        }
    }
}
            ',
            ],
            'Q9' => [
                'name' => 'Q9',
                'type' => $simple,
                'description' => 'Stargazer count for facebook/react',
                'rest_query' => 'repos/facebook/react',
                'graphql_query' => 'query {
    repository(owner: "facebook", name: "react") {
        stargazerCount
    }
}
            ',
            ],
            'Q10' => [
                'name' => 'Q10',
                'type' => $simple,
                'description' => 'First 100 repos with stars >= 1000 (names only)',
                'rest_query' => 'search/repositories?q=stars:>=1000&per_page=100',
                'graphql_query' => 'query {
    search(query: "stars:>=1000", type: REPOSITORY, first: 100) {
        nodes {
            ... on Repository { name }
        }
    }
}
            ',
            ],
            'Q11' => [
                'name' => 'Q11',
                'type' => $complex,
                'description' => 'Commit history total on default branch of facebook/react (REST grabs latest commit)',
                'rest_query' => 'repos/facebook/react/commits?per_page=1',
                'graphql_query' => 'query {
    repository(owner: "facebook", name: "react") {
        defaultBranchRef {
            target {
                ... on Commit {
                    history { totalCount }
                }
            }
        }
    }
}
            ',
            ],
            'Q12' => [
                'name' => 'Q12',
                'type' => $simple,
                'description' => 'Repos with stars > 10000 (first 8) + releases count, stargazers, languages',
                'rest_query' => 'search/repositories?q=stars:>10000&sort=stars&order=desc&per_page=8',
                'graphql_query' => 'query {
    search(query: "stars:>10000", type: REPOSITORY, first: 8) {
        nodes {
            ... on Repository {
                name
                releases { totalCount }
                stargazerCount
                languages(first: 10) { nodes { name } }
            }
        }
    }
}
            ',
            ],
            'Q13' => [
                'name' => 'Q13',
                'type' => $complex,
                'description' => 'Open issues labeled bug across GitHub',
                'rest_query' => 'search/issues?q=is:issue+is:open+label:bug',
                'graphql_query' => 'query {
    search(query: "is:issue is:open label:bug", type: ISSUE, first: 100) {
        nodes {
            ... on Issue {
                title
                body
                createdAt
                repository { name }
            }
        }
    }
}
            ',
            ],
            'Q14' => [
                'name' => 'Q14',
                'type' => $complex,
                'description' => 'Issue #2 (title + comments) for facebook/react',
                'rest_query' => 'repos/facebook/react/issues/10/comments',
                'graphql_query' => 'query {
    repository(owner: "facebook", name: "react") {
        issue(number: 10) {
            title
            comments(first: 100) {
                nodes {
                    body
                }
            }
        }
    }
}
            ',
            ],
        ];

        // Insert queries
        $queryData = collect($queries)->map(function ($query, $key) {
            return [
                'name' => $query['name'],
                'type' => $query['type'],
                'description' => $query['description'],
            ];
        })->values()->toArray();

        Query::insert($queryData);

        // Create presets for each query
        $queryModels = Query::all();
        foreach ($queryModels as $queryModel) {
            $queryData = $queries[$queryModel->name];
            $queryModel->presets()->create([
                'name' => 'Default Preset',
                'rest_query' => $queryData['rest_query'],
                'graphql_query' => $queryData['graphql_query'],
                'description' => 'Default preset for ' . $queryModel->name,
                'is_active' => true,
            ]);
        }
    }
}
