<?php

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helpers ─────────────────────────────────────────────────────────────────────

function makeGradeEntry(User $student, SchoolClass $class, Term $term, float $score, float $maxScore = 100): void
{
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    StudentGrade::factory()->create([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'term_id' => $term->id,
        'score' => $score,
        'max_score' => $maxScore,
        'assessment_type' => 'Exam',
        'assessment_date' => now()->format('Y-m-d'),
        'entered_by' => $teacher->id,
    ]);
}

// ── Rank tests ────────────────────────────────────────────────────────────────

test('student with highest average is ranked 1st', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $class = SchoolClass::factory()->create([
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $top = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $middle = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $bottom = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    makeGradeEntry($top, $class, $term, 90);
    makeGradeEntry($middle, $class, $term, 70);
    makeGradeEntry($bottom, $class, $term, 50);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.generate', ['id' => $top->id, 'term_id' => $term->id]));

    $response->assertOk();
    $response->assertSee('1');
    $response->assertSee('out of 3');
});

test('student with lowest average is ranked last', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $class = SchoolClass::factory()->create([
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $top = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $bottom = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    makeGradeEntry($top, $class, $term, 80);
    makeGradeEntry($bottom, $class, $term, 40);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.generate', ['id' => $bottom->id, 'term_id' => $term->id]));

    $response->assertOk();
    $response->assertSee('2');
    $response->assertSee('out of 2');
});

test('students with equal averages share the same rank', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $term = Term::factory()->create(['is_active' => true]);

    $class = SchoolClass::factory()->create([
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $studentA = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $studentB = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    makeGradeEntry($studentA, $class, $term, 75);
    makeGradeEntry($studentB, $class, $term, 75);

    $responseA = $this->actingAs($admin)
        ->get(route('admin.reports.generate', ['id' => $studentA->id, 'term_id' => $term->id]));
    $responseB = $this->actingAs($admin)
        ->get(route('admin.reports.generate', ['id' => $studentB->id, 'term_id' => $term->id]));

    $responseA->assertOk()->assertSee('out of 2');
    $responseB->assertOk()->assertSee('out of 2');
});

test('student with no stream shows N/A for position', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => null]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.generate', $student->id));

    $response->assertOk();
    $response->assertSee('N/A');
});

test('rank filters correctly by term', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $term1 = Term::factory()->create(['is_active' => false]);
    $term2 = Term::factory()->create(['is_active' => true]);

    $class = SchoolClass::factory()->create([
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $studentA = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $studentB = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);

    // Term 1: A is top
    makeGradeEntry($studentA, $class, $term1, 90);
    makeGradeEntry($studentB, $class, $term1, 60);

    // Term 2: B is top
    makeGradeEntry($studentA, $class, $term2, 55);
    makeGradeEntry($studentB, $class, $term2, 85);

    // In term 2, studentA should be ranked 2nd
    $response = $this->actingAs($admin)
        ->get(route('admin.reports.generate', ['id' => $studentA->id, 'term_id' => $term2->id]));

    $response->assertOk()->assertSee('out of 2');
});
