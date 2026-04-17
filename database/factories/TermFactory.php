<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TermFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', 'now');
        return [
            'name'       => fake()->unique()->randomElement(['Term 1 2026', 'Term 2 2026', 'Term 3 2026', 'Term 1 2025', 'Term 2 2025']),
            'start_date' => $start,
            'end_date'   => fake()->dateTimeBetween($start, '+4 months'),
            'is_active'  => false,
        ];
    }
}
