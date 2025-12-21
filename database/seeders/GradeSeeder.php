<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grade;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            // Junior Secondary
            ['name' => 'Grade 7', 'level' => 'Junior Secondary', 'order' => 7],
            ['name' => 'Grade 8', 'level' => 'Junior Secondary', 'order' => 8],
            ['name' => 'Grade 9', 'level' => 'Junior Secondary', 'order' => 9],

            // Senior Secondary
            ['name' => 'Grade 10', 'level' => 'Senior Secondary', 'order' => 10],
            ['name' => 'Grade 11', 'level' => 'Senior Secondary', 'order' => 11],
            ['name' => 'Grade 12', 'level' => 'Senior Secondary', 'order' => 12],
        ];

        foreach ($grades as $grade) {
            Grade::create($grade);
        }
    }
}
