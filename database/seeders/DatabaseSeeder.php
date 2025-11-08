<?php

namespace Database\Seeders;

use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Query::insert([
            [
                'name' => 'Q1',
                'description' => 'Query 1',
            ],
            [
                'name' => 'Q2',
                'description' => 'Query 2',
            ],
            [
                'name' => 'Q3',
                'description' => 'Query 3',
            ],
        ]);

        $queries = Query::all();
        foreach ($queries as $query) {
            $query->presets()->createMany([
                [
                    'name' => 'Preset 1',
                    'rest_query' => 'https://example.com/api/v1/users',
                    'graphql_query' => 'query { users { id name } }',
                    'description' => 'Description of Preset 1',
                    'is_active' => true,
                ],
                [
                    'name' => 'Preset 2',
                    'rest_query' => 'https://example.com/api/v1/users/1',
                    'graphql_query' => 'query { user(id: 1) { id name } }',
                    'description' => 'Description of Preset 2',
                ]
            ]);
        }

        ApiTest::factory(10)
            ->has(ApiTestResult::factory()->count(10), 'results')
            ->create();
    }
}
