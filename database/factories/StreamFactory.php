<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'grade_id' => Grade::factory(),
            'name' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'capacity' => 40,
        ];
    }
}
