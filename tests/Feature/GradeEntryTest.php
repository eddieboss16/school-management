<?php

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Teacher grade entry ───────────────────────────────────────────────────────

test('teacher can enter grades for their own class', function () {
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $grade   = Grade::factory()->create();
    $stream  = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $term    = Term::factory()->create(['is_active' => true]);

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
        'grade_id'   => $grade->id,
        'stream_id'  => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $class->students()->attach($student->id);

    $this->actingAs($teacher)
        ->post(route('teacher.grades.store', $class->id), [
            'assessment_type' => 'Midterm Exam',
            'assessment_date' => now()->format('Y-m-d'),
            'max_score'       => 100,
            'grades'          => [$student->id => ['score' => 78, 'remarks' => '']],
        ])
        ->assertRedirect(route('teacher.grades.view', $class->id));

    $this->assertDatabaseHas('student_grades', [
        'class_id'        => $class->id,
        'student_id'      => $student->id,
        'score'           => 78,
        'assessment_type' => 'Midterm Exam',
    ]);
});

test('teacher cannot enter grades for another teacher\'s class', function () {
    $teacherA = User::factory()->create(['usertype' => 'teacher']);
    $teacherB = User::factory()->create(['usertype' => 'teacher']);
    $grade    = Grade::factory()->create();
    $stream   = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject  = Subject::factory()->create();

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacherB->id,
        'grade_id'   => $grade->id,
        'stream_id'  => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $class->students()->attach($student->id);

    $this->actingAs($teacherA)
        ->post(route('teacher.grades.store', $class->id), [
            'assessment_type' => 'Midterm Exam',
            'assessment_date' => now()->format('Y-m-d'),
            'max_score'       => 100,
            'grades'          => [$student->id => ['score' => 90]],
        ])
        ->assertStatus(404);
});

// ── StudentGrade percentage accessor ─────────────────────────────────────────

test('student grade percentage is calculated correctly', function () {
    $grade = \App\Models\StudentGrade::factory()->make(['score' => 75, 'max_score' => 100]);
    expect($grade->percentage)->toBe(75.0);
});

test('student grade percentage handles zero max_score without dividing by zero', function () {
    $grade = \App\Models\StudentGrade::factory()->make(['score' => 0, 'max_score' => 0]);
    expect((float) $grade->percentage)->toBe(0.0);
});
