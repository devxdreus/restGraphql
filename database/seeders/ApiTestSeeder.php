<?php

namespace Database\Seeders;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use Illuminate\Database\Seeder;

class ApiTestSeeder extends Seeder
{
    public function run(): void
    {
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
