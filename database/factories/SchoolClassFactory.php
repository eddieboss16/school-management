<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolClassFactory extends Factory
{
    protected $model = \App\Models\SchoolClass::class;

    public function definition(): array
    {
        return [
            'grade_id' => Grade::factory(),
            'stream_id' => Stream::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => User::factory()->create(['usertype' => 'teacher'])->id,
            'class_code' => strtoupper(Str::random(6)),
            'capacity' => 40,
        ];
    }
}
