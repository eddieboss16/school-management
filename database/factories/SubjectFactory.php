<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Mathematics', 'English', 'Kiswahili', 'Science', 'Social Studies',
                'Religious Education', 'Creative Arts', 'Physical Education',
            ]),
            'code' => fake()->unique()->bothify('??###'),
        ];
    }
}
