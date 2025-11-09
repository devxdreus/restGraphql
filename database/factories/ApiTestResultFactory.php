<?php

namespace Database\Factories;

use App\Enums\ApiStatusType;
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
            'request_type' => $this->faker->randomElement(ApiType::values()),
            'status' => $this->faker->randomElement(ApiStatusType::values()),
            'response' => $this->faker->words(10),
            'payload' => $this->faker->randomNumber(3),
            'response_time' => $this->faker->numberBetween(800, 1200),
            'cpu_usage' => $this->faker->randomFloat(2, 0, 50),
            'mem_usage' => $this->faker->randomFloat(2, 0, 50),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'api_test_id' => ApiTest::inRandomOrder()?->first() ?? ApiTest::factory(),
            'query_id' => Query::inRandomOrder()?->first() ?? Query::factory(),
            'preset_id' => QueryPreset::inRandomOrder()?->first() ?? QueryPreset::factory(),
        ];
    }

    public function responseTime(int $type): Factory
    {
        return $this->state(function (array $attributes) use ($type) {
            if ($type == 1) {
                $time = $this->faker->numberBetween(500, 700);
            }
            if ($type == 2) {
                $time = $this->faker->numberBetween(700, 900);
            }
            if ($type == 3) {
                $time = $this->faker->numberBetween(900, 1100);
            }
            if ($type < 1 || $type > 3) {
                throw new \Exception('Invalid type');
            }
            return [
                'response_time' => $time,
            ];
        });
    }
}
