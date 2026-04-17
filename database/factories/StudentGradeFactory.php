<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentGradeFactory extends Factory
{
    protected $model = \App\Models\StudentGrade::class;

    public function definition(): array
    {
        $score    = fake()->numberBetween(0, 100);
        $maxScore = 100;

        return [
            'class_id'        => SchoolClass::factory(),
            'student_id'      => User::factory()->create(['usertype' => 'student'])->id,
            'term_id'         => Term::factory(),
            'assessment_type' => fake()->randomElement(['Midterm Exam', 'End Term Exam', 'CAT 1', 'CAT 2']),
            'score'           => $score,
            'max_score'       => $maxScore,
            'assessment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'remarks'         => null,
            'entered_by'      => null,
        ];
    }
}
