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
                'description' => 'Name of the top-100 C projects by stars',
                'rest_query' => 'search/repositories?q=stars:0..*+language:c&sort=stars&order=desc&page=1&per_page=100',
                'graphql_query' => 'query {
    search(query:"stars:0..* language:c", type: REPOSITORY,first:100) {
        nodes {
            ... on Repository {
                nameWithOwner
            }
        }
    }
}
            ',
            ],
            'Q2' => [
                'name' => 'Q2',
                'type' => $complex,
                'description' => 'Total number and body of the 1K most recent PRs',
                'rest_query' => 'repos/torvalds/linux/pulls?&state=all&sort=created&direction=desc&page=1&per_page=100',
                'graphql_query' => 'query {
    repository(owner: "torvalds", name: "linux"){
        pullRequests(first:100, orderBy:{field:CREATED_AT, direction:DESC}){
            pageInfo{
                hasNextPage
                endCursor
            }
            nodes{
                repository{
                    nameWithOwner
                }
                title
                number
            }
        }
    }
}
',
            ],
            'Q3' => [
                'name' => 'Q3',
                'type' => $simple,
                'description' => 'Body of comments from PR',
                'rest_query' => 'repos/torvalds/linux/pulls/988',
                'graphql_query' => 'query {
  repository(owner: "torvalds", name: "linux") {
    pullRequest(number: 988) {
      number
      title
      comments(first: 100) {
        pageInfo {
          hasNextPage
          endCursor
        }
        nodes {
          bodyText
        }
      }
    }
  }
}
            ',
            ],
            'Q4' => [
                'name' => 'Q4',
                'type' => $simple,
                'description' => 'Name and URL of the top-5 projects by stars (in any programming language)',
                'rest_query' => 'search/repositories?q=stars:0..*&sort=stars&order=desc&page=1&per_page=5',
                'graphql_query' => 'query {
  search(query:"stars:0..*", type:REPOSITORY, first:5){
    nodes{
      ... on Repository{
        nameWithOwner
        url
        stargazerCount
      }
    }
  }
}
            ',
            ],
            'Q5' => [
                'name' => 'Q5',
                'type' => $simple,
                'description' => 'number of commits,branches, bugs, releases and contributors',
                'rest_query' => 'repos/torvalds/linux/commits?q=&page=1&per_page=100',
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
                'description' => 'Title and body of closed bugs',
                'rest_query' => 'repos/matplotlib/Matplotlib/issues?q=&state=closed&labels=status:+confirmed+bug&sort=created&direction=asc&page=1&per_page=100',
                'graphql_query' => 'query {
  repository(owner: "matplotlib", name: "Matplotlib"){
    issues(labels: "status: confirmed bug", states:CLOSED, first:100){
      pageInfo{
        hasNextPage
        endCursor
      }
      totalCount
        nodes{
          title
          body
        }
    	}
  }
}
            ',
            ],
            'Q7' => [
                'name' => 'Q7',
                'type' => $simple,
                'description' => 'Body comments of closed bugs',
                'rest_query' => 'repos/matplotlib/Matplotlib/issues/28551/comments?&page=1&per_page=100',
                'graphql_query' => 'query {
  search(type: ISSUE, query: "repo:matplotlib/matplotlib in:title [Bug]: Possible issue with Matplotlib 3.9.1 wheel on Windows only" , first: 1) {
    nodes {
      ... on Issue {
        comments(first:100){
              pageInfo{
                hasNextPage
                endCursor
              }
              nodes{
                bodyText
              }
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
                'description' => 'Name and URL of Java projects created before Jan, 2012, with 10+ stars, and 1+ commits',
                'rest_query' => 'search/repositories?q=created:<2012-01-01+pushed:>=2016-07-01+stars:>10+size:>1000&page=1&per_page=100',
                'graphql_query' => 'query {
  search(query:"language:java stars:>10 created:<2012-01-01 pushed:>=2016-07-01 size:>1000", type:REPOSITORY, first:100){
    pageInfo{
        hasNextPage
        endCursor
    }
    nodes{
      ... on Repository{
        nameWithOwner
        url
      }
    }
  }
}
            ',
            ],
            'Q9' => [
                'name' => 'Q9',
                'type' => $simple,
                'description' => 'Number of stars of specific projects',
                'rest_query' => 'repos/torvalds/linux',
                'graphql_query' => 'query {
  repository(owner: "torvalds", name: "linux"){
    stargazers{
      totalCount
    }
  }
}
            ',
            ],
            'Q10' => [
                'name' => 'Q10',
                'type' => $simple,
                'description' => 'Name of repositories with at least 1K stars',
                'rest_query' => 'search/repositories?q=stars:>=1000&page=1&per_page=100',
                'graphql_query' => 'query {
  search(query:"stars:>1000", type:REPOSITORY, first:100){
    pageInfo{
        hasNextPage
        endCursor
    }
    nodes{
      ... on Repository{
        nameWithOwner
      }
    }
  }
}
            ',
            ],
            'Q11' => [
                'name' => 'Q11',
                'type' => $complex,
                'description' => 'Number of commits in a repository',
                'rest_query' => 'repos/torvalds/linux/commits?q=&page=1&per_page=100',
                'graphql_query' => 'query {
  repository(owner: "torvalds", name: "linux"){
    ref(qualifiedName:"master"){
          target{
            ... on Commit{
              history{
                totalCount
              }
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
                'description' => 'Number of releases, stars, and language of project',
                'rest_query' => 'repos/torvalds/linux',
                'graphql_query' => 'query {
  repository(owner: "bitcoin", name: "bitcoin") {
    tags: refs(refPrefix: "refs/tags/") {
      totalCount
    }
    primaryLanguage {
      name
    }
    stargazers {
      totalCount
    }
  }
}
            ',
            ],
            'Q13' => [
                'name' => 'Q13',
                'type' => $complex,
                'description' => 'Title, body, date and project name of open issues tagged with a bug tag',
                'rest_query' => 'search/issues?q=repo:bitcoin/bitcoin+is:issue+label:bug+state:closed+created:>2016-11-07&per_page=100&page=1',
                'graphql_query' => 'query {
  search(
    query: "repo:bitcoin/bitcoin state:closed created:>2016-11-07 label:bug"
    type: ISSUE
    first: 100
  ) {
    pageInfo {
      hasNextPage
      endCursor
    }
    nodes {
      ... on Issue {
        repository {
          nameWithOwner
        }
        title
        body
        createdAt
      }
    }
  }
}
            ',
            ],
            'Q14' => [
                'name' => 'Q14',
                'type' => $complex,
                'description' => 'Body comments of issue',
                'rest_query' => 'repos/matplotlib/matplotlib/issues/28551/comments?per_page=100&page=1',
                'graphql_query' => 'query {
  search(
    type: ISSUE
    query: "repo:bitcoin/bitcoin in:title bug"
    first: 1
  ) {
    nodes {
      ... on Issue {
        comments(first: 100) {
          pageInfo {
            hasNextPage
            endCursor
          }
          nodes {
            bodyText
          }
        }
      }
    }
  }
}
            ',
            ],
            'Q15' => [
                'name' => 'Q15',
                'type' => $simple,
                'description' => 'View detail of paper',
                'rest_query' => 'query?id_list=1706.03762',
                'graphql_query' => 'query Q15_ViewSinglePaper {
  paper(id: "1706.03762") {
    id
    title
    published
  }
}
            ',
            ],
            'Q16' => [
                'name' => 'Q16',
                'type' => $complex,
                'description' => 'Search paper in all category',
                'rest_query' => 'query?search_query=cat:cs.AI&start=0&max_results=1000&sortBy=submittedDate&sortOrder=descending',
                'graphql_query' => 'query {
    feed(
        limit: 100
    ){
        id
        title
        published
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
