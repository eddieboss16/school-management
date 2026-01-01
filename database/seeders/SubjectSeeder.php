<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // Check if subjects already exist
        if (Subject::count() > 0) {
            $this->command->info('Subject already exist. Skipping seeder.');
            return;
        }

        Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'description' => 'Mathematics subject']);
        Subject::create(['name' => 'English', 'code' => 'ENG', 'description' => 'English subject']);
        Subject::create(['name' => 'Kiswahili', 'code' => 'KIS', 'description' => 'Kiswahili subject']);
        Subject::create(['name' => 'Biology', 'code' => 'BIO', 'description' => 'Biology subject']);
        Subject::create(['name' => 'Chemistry', 'code' => 'CHEM', 'description' => 'Chemistry subject']);
        Subject::create(['name' => 'Physics', 'code' => 'PHY', 'description' => 'Physics subject']);
        Subject::create(['name' => 'History', 'code' => 'HIST', 'description' => 'History subject']);
        Subject::create(['name' => 'Geography', 'code' => 'GEO', 'description' => 'Geography subject']);
        Subject::create(['name' => 'CRE', 'code' => 'CRE', 'description' => 'Christian Religious Eduction subject']);
        Subject::create(['name' => 'Business Studies', 'code' => 'BUS', 'description' => 'Business Studies subject']);
        Subject::create(['name' => 'Computer Science', 'code' => 'CS', 'description' => 'Computer Science subject']);
        Subject::create(['name' => 'Agriculture', 'code' => 'AGR', 'description' => 'Agriculture subject']);
            
        $this->command->info('Subject seeded successfully.');
    }
}
