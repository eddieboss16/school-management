<?php

/**
 * Characterization tests: a teacher may only reach classes they own.
 *
 * Ownership is enforced by query scoping in the controllers
 * (SchoolClass::where('teacher_id', ...)->findOrFail($classId)), so the
 * non-owner path is a 404, not a 403. These tests lock in that behaviour
 * before any refactor touches the controllers.
 */

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Build an independent teacher with their own grade/stream/subject, one class,
 * and one enrolled student. Mirrors the setup used by GradeEntryTest.
 *
 * @return array{0: User, 1: SchoolClass, 2: User}
 */
function isolationTeacherWithClass(): array
{
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => $stream->id]);
    $class->students()->attach($student->id);

    return [$teacher, $class, $student];
}

/**
 * Record one assessment inside a class so edit/update/delete have something to target.
 *
 * entered_by is passed explicitly: student_grades.entered_by is NOT NULL in the
 * migration but StudentGradeFactory defaults it to null, so a bare
 * StudentGrade::factory()->create() always violates the constraint.
 */
function isolationSeedGrade(SchoolClass $class, User $student, string $assessmentType = 'Midterm', float $score = 50): StudentGrade
{
    return StudentGrade::factory()->create([
        'class_id' => $class->id,
        'student_id' => $student->id,
        'term_id' => Term::factory()->create()->id,
        'assessment_type' => $assessmentType,
        'score' => $score,
        'max_score' => 100,
        'entered_by' => $class->teacher_id,
    ]);
}

// ── Teacher A cannot read Teacher B's class ──────────────────────────────────

test('teacher cannot open the grade entry roster of another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB] = isolationTeacherWithClass();

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.enter', $classB->id))
        ->assertStatus(404);
});

test('teacher cannot view the grade list of another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    isolationSeedGrade($classB, $studentB);

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.view', $classB->id))
        ->assertStatus(404);
});

test('teacher cannot export grades of another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    isolationSeedGrade($classB, $studentB);

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.export', $classB->id))
        ->assertStatus(404);
});

test('teacher cannot open the attendance marking page of another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB] = isolationTeacherWithClass();

    $this->actingAs($teacherA)
        ->get(route('teacher.attendance.mark', $classB->id))
        ->assertStatus(404);
});

test('teacher cannot view attendance history of another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB] = isolationTeacherWithClass();

    $this->actingAs($teacherA)
        ->get(route('teacher.attendance.history', $classB->id))
        ->assertStatus(404);
});

// ── Teacher A cannot write into Teacher B's class ────────────────────────────

test('teacher cannot enter grades into another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->post(route('teacher.grades.store', $classB->id), [
            'assessment_type' => 'Injected Exam',
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [$studentB->id => ['score' => 99]],
        ])
        ->assertStatus(404);

    $this->assertDatabaseMissing('student_grades', [
        'class_id' => $classB->id,
        'assessment_type' => 'Injected Exam',
    ]);
});

test('teacher cannot open the edit form for an assessment in another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    isolationSeedGrade($classB, $studentB);

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.edit', [$classB->id, 'Midterm']))
        ->assertStatus(404);
});

test('teacher cannot update grades in another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    $gradeB = isolationSeedGrade($classB, $studentB, 'Midterm', 50);

    $this->actingAs($teacherA)
        ->put(route('teacher.grades.update', [$classB->id, 'Midterm']), [
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [$gradeB->id => ['score' => 5]],
        ])
        ->assertStatus(404);

    expect((float) $gradeB->fresh()->score)->toBe(50.0);
});

test('teacher cannot update a grade belonging to another class through their own class route', function () {
    [$teacherA, $classA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    $gradeB = isolationSeedGrade($classB, $studentB, 'Midterm', 50);

    // classA is legitimately owned by teacherA, but the grade id belongs to classB.
    $this->actingAs($teacherA)
        ->put(route('teacher.grades.update', [$classA->id, 'Midterm']), [
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [$gradeB->id => ['score' => 5]],
        ])
        ->assertStatus(404);

    expect((float) $gradeB->fresh()->score)->toBe(50.0);
});

test('teacher cannot delete an assessment in another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    $gradeB = isolationSeedGrade($classB, $studentB);

    $this->actingAs($teacherA)
        ->delete(route('teacher.grades.destroy', [$classB->id, 'Midterm']))
        ->assertStatus(404);

    expect($gradeB->fresh()->deleted_at)->toBeNull();
});

test('deleting an assessment by name only affects the acting teacher own class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();

    $gradeA = isolationSeedGrade($classA, $studentA, 'Midterm');
    $gradeB = isolationSeedGrade($classB, $studentB, 'Midterm');

    $this->actingAs($teacherA)
        ->delete(route('teacher.grades.destroy', [$classA->id, 'Midterm']))
        ->assertRedirect(route('teacher.grades.view', $classA->id));

    expect($gradeA->fresh()->deleted_at)->not->toBeNull()
        ->and($gradeB->fresh()->deleted_at)->toBeNull();
});

test('teacher cannot mark attendance for another teacher class', function () {
    [$teacherA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->post(route('teacher.attendance.store', $classB->id), [
            'date' => now()->format('Y-m-d'),
            'attendance' => [$studentB->id => 'absent'],
        ])
        ->assertStatus(404);

    $this->assertDatabaseMissing('attendances', ['class_id' => $classB->id]);
});

// ── Positive control: Teacher A retains full access to their own class ───────

test('teacher can open the grade entry roster of their own class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.enter', $classA->id))
        ->assertOk()
        ->assertSee($studentA->name);
});

test('teacher can enter and then view grades in their own class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->post(route('teacher.grades.store', $classA->id), [
            'assessment_type' => 'Midterm',
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [$studentA->id => ['score' => 71]],
        ])
        ->assertRedirect(route('teacher.grades.view', $classA->id));

    $this->assertDatabaseHas('student_grades', [
        'class_id' => $classA->id,
        'student_id' => $studentA->id,
        'score' => 71,
    ]);

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.view', $classA->id))
        ->assertOk();
});

test('teacher can edit and update grades in their own class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    $gradeA = isolationSeedGrade($classA, $studentA, 'Midterm', 40);

    $this->actingAs($teacherA)
        ->get(route('teacher.grades.edit', [$classA->id, 'Midterm']))
        ->assertOk();

    $this->actingAs($teacherA)
        ->put(route('teacher.grades.update', [$classA->id, 'Midterm']), [
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [$gradeA->id => ['score' => 88]],
        ])
        ->assertRedirect(route('teacher.grades.view', $classA->id));

    expect((float) $gradeA->fresh()->score)->toBe(88.0);
});

test('teacher can export grades for their own class and the file holds only that class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();
    isolationSeedGrade($classA, $studentA);
    isolationSeedGrade($classB, $studentB);

    $response = $this->actingAs($teacherA)
        ->get(route('teacher.grades.export', $classA->id))
        ->assertOk();

    expect($response->headers->get('content-type'))->toStartWith('text/csv')
        ->and($response->headers->get('content-disposition'))->toContain('attachment');

    $csv = $response->streamedContent();

    expect($csv)->toContain($studentA->name)
        ->and($csv)->not->toContain($studentB->name);
});

test('teacher can mark and review attendance for their own class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->get(route('teacher.attendance.mark', $classA->id))
        ->assertOk();

    $this->actingAs($teacherA)
        ->post(route('teacher.attendance.store', $classA->id), [
            'date' => now()->format('Y-m-d'),
            'attendance' => [$studentA->id => 'present'],
        ])
        ->assertRedirect(route('teacher.attendance.history', $classA->id));

    $this->assertDatabaseHas('attendances', [
        'class_id' => $classA->id,
        'student_id' => $studentA->id,
        'status' => 'present',
    ]);

    $this->actingAs($teacherA)
        ->get(route('teacher.attendance.history', $classA->id))
        ->assertOk();
});

test('grade list of one class never contains records from another class', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    [, $classB, $studentB] = isolationTeacherWithClass();

    isolationSeedGrade($classA, $studentA, 'Midterm');
    isolationSeedGrade($classB, $studentB, 'Midterm');

    $response = $this->actingAs($teacherA)
        ->get(route('teacher.grades.view', $classA->id))
        ->assertOk();

    $classIds = collect($response->viewData('grades'))
        ->flatten(1)
        ->pluck('class_id')
        ->unique()
        ->values()
        ->all();

    expect($classIds)->toBe([$classA->id]);
});

// ── Writes are refused for students not enrolled in the class ───────────────
//
// Owning the class is not sufficient. These previously documented a gap: a
// teacher could write rows for a student belonging to another class, and
// ReportCardController::buildReportData selects on student_id alone, so the
// row surfaced on the victim's report card. Both writes are now rejected.

test('grade entry rejects a student who is not enrolled in the class', function () {
    [$teacherA, $classA] = isolationTeacherWithClass();
    [, , $studentB] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->post(route('teacher.grades.store', $classA->id), [
            'assessment_type' => 'Outsider Exam',
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [$studentB->id => ['score' => 99]],
        ])
        ->assertStatus(302)
        ->assertSessionHasErrors('grades');

    $this->assertDatabaseMissing('student_grades', [
        'class_id' => $classA->id,
        'student_id' => $studentB->id,
    ]);
});

test('attendance marking rejects a student who is not enrolled in the class', function () {
    [$teacherA, $classA] = isolationTeacherWithClass();
    [, , $studentB] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->post(route('teacher.attendance.store', $classA->id), [
            'date' => now()->format('Y-m-d'),
            'attendance' => [$studentB->id => 'absent'],
        ])
        ->assertStatus(302)
        ->assertSessionHasErrors('attendance');

    $this->assertDatabaseMissing('attendances', [
        'class_id' => $classA->id,
        'student_id' => $studentB->id,
    ]);
});

test('a mixed batch is rejected whole rather than silently dropping the outsider', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    [, , $studentB] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);

    $this->actingAs($teacherA)
        ->post(route('teacher.grades.store', $classA->id), [
            'assessment_type' => 'Mixed Batch',
            'assessment_date' => now()->format('Y-m-d'),
            'max_score' => 100,
            'grades' => [
                $studentA->id => ['score' => 60],
                $studentB->id => ['score' => 99],
            ],
        ])
        ->assertStatus(302)
        ->assertSessionHasErrors('grades');

    // The enrolled student's row is rejected too — the batch is atomic.
    $this->assertDatabaseMissing('student_grades', ['assessment_type' => 'Mixed Batch']);
});

test('attendance for an unenrolled student does not wipe existing records for that date', function () {
    [$teacherA, $classA, $studentA] = isolationTeacherWithClass();
    [, , $studentB] = isolationTeacherWithClass();
    Term::factory()->create(['is_active' => true]);
    $date = now()->format('Y-m-d');

    $this->actingAs($teacherA)
        ->post(route('teacher.attendance.store', $classA->id), [
            'date' => $date,
            'attendance' => [$studentA->id => 'present'],
        ])
        ->assertRedirect(route('teacher.attendance.history', $classA->id));

    // store() deletes the day's rows before re-inserting, so the enrollment
    // check has to reject before that delete runs.
    $this->actingAs($teacherA)
        ->post(route('teacher.attendance.store', $classA->id), [
            'date' => $date,
            'attendance' => [$studentB->id => 'absent'],
        ])
        ->assertStatus(302)
        ->assertSessionHasErrors('attendance');

    $this->assertDatabaseHas('attendances', [
        'class_id' => $classA->id,
        'student_id' => $studentA->id,
        'status' => 'present',
        'deleted_at' => null,
    ]);
});
