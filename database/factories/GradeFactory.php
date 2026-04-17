<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    public function definition(): array
    {
        static $order = 1;
        return [
            'name'  => 'Grade ' . $order,
            'level' => 'primary',
            'order' => $order++,
        ];
    }
}
