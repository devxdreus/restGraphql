<?php

namespace Database\Factories;

use App\Models\Query;
use App\Models\QueryPreset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class QueryPresetFactory extends Factory
{
    protected $model = QueryPreset::class;

    public function definition(): array
    {
        return [
            'rest_query' => $this->faker->url(),
            'graphql_query' => $this->faker->url(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'query_id' => Query::inRandomOrder()->first() ?? Query::factory(),
        ];
    }
}
