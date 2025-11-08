<?php

namespace Database\Factories;

use App\Enums\ApiStatusType;
use App\Models\ApiTest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ApiTestFactory extends Factory
{
    protected $model = ApiTest::class;

    public function definition(): array
    {
        return [
            'count' => $this->faker->randomElement([10, 50, 100, 200]),
            'status' => $this->faker->randomElement(ApiStatusType::values()),
            'completed_at' => Carbon::now()->addMinutes(rand(5, 10)),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
