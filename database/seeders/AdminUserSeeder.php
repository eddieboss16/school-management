<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Eddie Boss',
            'email' => 'admin@school.com',
            'usertype' => 'admin',
            'password' => bcrypt('Admin@123'),
            'email_verified_at' => now(),
        ]);
    }
}
