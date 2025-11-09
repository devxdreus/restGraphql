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

    public function type(int $time, int $cpu, int $mem): Factory
    {
        return $this->state(function (array $attributes) use ($time, $cpu, $mem) {
            $ranges = [
                'time' => [
                    1 => [500, 700],
                    2 => [700, 900],
                    3 => [900, 1100]
                ],
                'cpu' => [
                    1 => [10, 20],
                    2 => [20, 30],
                    3 => [30, 40]
                ],
                'mem' => [
                    1 => [10, 20],
                    2 => [20, 30],
                    3 => [30, 40]
                ]
            ];

            if ($time < 1 || $time > 3 || $cpu < 1 || $cpu > 3 || $mem < 1 || $mem > 3) {
                throw new \Exception('Invalid type');
            }

            return [
                'response_time' => $this->faker->numberBetween(...$ranges['time'][$time]),
                'cpu_usage' => $this->faker->randomFloat(2, ...$ranges['cpu'][$cpu]),
                'mem_usage' => $this->faker->randomFloat(2, ...$ranges['mem'][$mem])
            ];
        });
    }
}
