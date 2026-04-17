<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeStructureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'term_id'     => Term::factory(),
            'grade_id'    => null,
            'name'        => fake()->randomElement(['Tuition Fee', 'Activity Fee', 'Library Fee', 'Exam Fee']),
            'amount'      => fake()->numberBetween(500, 10000),
            'description' => null,
        ];
    }
}
