<?php

namespace Database\Factories;

use App\Models\Query;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class QueryFactory extends Factory
{
    protected $model = Query::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->numerify('Q##'),
            'description' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
