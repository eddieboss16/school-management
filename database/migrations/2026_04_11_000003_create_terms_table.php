<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Term 1 2024"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        if (! Schema::hasColumn('student_grades', 'term_id')) {
            Schema::table('student_grades', function (Blueprint $table) {
                $table->foreignId('term_id')->nullable()->constrained()->onDelete('set null');
            });
        }

        if (! Schema::hasColumn('attendances', 'term_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('term_id')->nullable()->constrained()->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->dropColumn('term_id');
        });

        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->dropColumn('term_id');
        });

        Schema::dropIfExists('terms');
    }
};
