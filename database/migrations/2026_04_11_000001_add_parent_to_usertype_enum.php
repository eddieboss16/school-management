<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN is MySQL-only. SQLite (used in tests) ignores this migration
        // because the base migration already includes 'parent' in the enum.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN usertype ENUM('admin', 'teacher', 'student', 'parent') NOT NULL DEFAULT 'student'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN usertype ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student'");
    }
};
