<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN usertype ENUM('admin', 'teacher', 'student', 'parent') NOT NULL DEFAULT 'student'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN usertype ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student'");
    }
};
