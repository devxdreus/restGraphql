<?php

namespace Database\Factories;

use App\Enums\ApiType;
use App\Models\ApiTest;
use App\Models\ApiTestResult;
use App\Models\Query;
use App\Models\QueryPreset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ApiTestResultFactory extends Factory
{
    protected $model = ApiTestResult::class;

    public function definition(): array
    {
        return [
            'api_type' => $this->faker->randomElement(ApiType::values()),
            'response' => $this->faker->words(),
            'payload' => $this->faker->randomNumber(3),
            'cpu_usage' => $this->faker->randomFloat(2, 0, 50),
            'mem_usage' => $this->faker->randomFloat(2, 0, 50),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'api_test_id' => ApiTest::factory(),
            'query_id' => Query::factory(),
            'preset_id' => QueryPreset::factory(),
        ];
    }
}
