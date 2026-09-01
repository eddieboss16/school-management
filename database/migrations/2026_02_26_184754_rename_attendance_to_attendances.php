<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename attendance → attendances if the old table still exists
        if (Schema::hasTable('attendance') && ! Schema::hasTable('attendances')) {
            Schema::rename('attendance', 'attendances');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendances') && ! Schema::hasTable('attendance')) {
            Schema::rename('attendances', 'attendance');
        }
    }
};
