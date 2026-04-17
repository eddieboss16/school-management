<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('student_grades', 'deleted_at')) {
            Schema::table('student_grades', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // The attendance table was originally created as 'attendance' (singular).
        // Support both names so this migration runs cleanly on both fresh and existing DBs.
        $attendanceTable = Schema::hasTable('attendances') ? 'attendances' : 'attendance';
        if (!Schema::hasColumn($attendanceTable, 'deleted_at')) {
            Schema::table($attendanceTable, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('student_grades', 'deleted_at')) {
            Schema::table('student_grades', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        $attendanceTable = Schema::hasTable('attendances') ? 'attendances' : 'attendance';
        if (Schema::hasColumn($attendanceTable, 'deleted_at')) {
            Schema::table($attendanceTable, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
