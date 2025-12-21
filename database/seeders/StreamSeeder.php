<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Stream;

class StreamSeeder extends Seeder
{
    public function run(): void
    {
        // Get all grades
        $grades = Grade::all();

        // Create streams A, B, C for each grade
        foreach ($grades as $grade) {
            Stream::create([
                'grade_id' => $grade->id,
                'name' => 'A',
                'capacity' => 40,
            ]);

            Stream::create([
                'grade_id' => $grade->id,
                'name' => 'B',
                'capacity' => 40,
            ]);

            Stream::create([
                'grade_id' => $grade->id,
                'name' => 'C',
                'capacity' => 40,
            ]);
        }
    }
}
