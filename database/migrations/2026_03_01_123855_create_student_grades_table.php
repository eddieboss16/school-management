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
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('assessment_type'); // e.g., "Quiz 1", "Midterm", "Final Exam"
            $table->decimal('score', 5, 2); // e.g., 85.50
            $table->decimal('max_score', 5, 2)->default(100); // e.g., 100
            $table->date('assessment_date');
            $table->text('remarks')->nullable();
            $table->foreignId('entered_by')->constrained('users'); // Teacher who entered it
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
