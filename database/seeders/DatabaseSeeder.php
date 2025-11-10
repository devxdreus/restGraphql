<?php

namespace Database\Seeders;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
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
        $data = [];
        for ($i = 1; $i <= 14; $i++) {
            $data[] = [
                'name' => "Q$i",
                'description' => "Query $i",
            ];
        }
        Query::insert($data);

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

        // this will generate random data
//        ApiTest::factory(10)
//            ->has(ApiTestResult::factory()->count(10), 'results')
//            ->create();

        // this will generate all succeeded tests
        ApiTest::factory(10)
            ->create([
                'status' => ApiStatusType::Success
            ])
            ->map(function ($test) {
                // for 3 queries
                for ($q = 1; $q <= 14; $q++) {

                    $time = rand(1, 3);
                    $cpu = rand(1, 3);
                    $mem = rand(1, 3);
                    for ($j = 1; $j <= 10; $j++) {
                        ApiTestResult::factory()
                            ->type($time, $cpu, $mem)
                            ->create([
                                'api_test_id' => $test->id,
                                'status' => ApiStatusType::Success,
                                'api_type' => ApiType::Rest,
                                'request_type' => ApiType::Rest,
                                'query_id' => Query::find($q),
                                'preset_id' => Query::find($q)->activePreset
                            ]);
                    }
                    $time = rand(1, 3);
                    $cpu = rand(1, 3);
                    $mem = rand(1, 3);
                    for ($j = 1; $j <= 10; $j++) {
                        ApiTestResult::factory()
                            ->type($time, $cpu, $mem)
                            ->create([
                                'api_test_id' => $test->id,
                                'status' => ApiStatusType::Success,
                                'api_type' => ApiType::Graphql,
                                'request_type' => ApiType::Graphql,
                                'query_id' => Query::find($q),
                                'preset_id' => Query::find($q)->activePreset
                            ]);
                    }
                    $time = rand(1, 3);
                    $cpu = rand(1, 3);
                    $mem = rand(1, 3);
                    $useRest = rand(0, 1);
                    for ($j = 1; $j <= 10; $j++) {
                        ApiTestResult::factory()
                            ->type($time, $cpu, $mem)
                            ->create([
                                'api_test_id' => $test->id,
                                'status' => ApiStatusType::Success,
                                'api_type' => ApiType::Integrated,
                                'request_type' => $useRest ? ApiType::Rest : ApiType::Graphql,
                                'query_id' => Query::find($q),
                                'preset_id' => Query::find($q)->activePreset
                            ]);
                    }

                }
            });
    }
}
