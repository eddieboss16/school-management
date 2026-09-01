<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->create(['usertype' => 'student'])->id,
            'term_id' => Term::factory(),
            'amount' => fake()->numberBetween(500, 10000),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'reference_number' => null,
            'notes' => null,
            'recorded_by' => null,
        ];
    }
}
