<?php

/**
 * Characterization tests: a student may only reach their own records.
 *
 * NOTE ON SHAPE — pulled from routes/web.php, not assumed:
 * no route behind `role:student` takes an id parameter. Every student page
 * derives identity from auth()->id()/auth()->user(), so there is no
 * "student A passes student B's id" attack surface to probe on those routes.
 *
 * These tests therefore lock in three things instead:
 *   1. that the identity-derived shape itself holds (a structural guard that
 *      fails the moment someone adds an id parameter to a student route);
 *   2. that student pages leak no other student's records;
 *   3. that the id-taking routes in the admin and parent namespaces stay
 *      closed to a student.
 */

use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * Build an independent student sharing a stream with the others (so stream
 * ranking has something to compare) but owning distinct academic records.
 *
 * @return array{0: User, 1: SchoolClass}
 */
function isolationStudentWithRecords(Stream $stream, Term $term, string $admissionNumber, int $score, string $status): array
{
    $subject = Subject::factory()->create();
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    $student = User::factory()->create([
        'usertype' => 'student',
        'stream_id' => $stream->id,
        'admission_number' => $admissionNumber,
    ]);

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
        'grade_id' => $stream->grade_id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);
    $class->students()->attach($student->id);

    // entered_by is required by the schema even though the factory defaults it to null.
    StudentGrade::factory()->create([
        'class_id' => $class->id,
        'student_id' => $student->id,
        'term_id' => $term->id,
        'score' => $score,
        'max_score' => 100,
        'entered_by' => $teacher->id,
    ]);

    Attendance::create([
        'class_id' => $class->id,
        'student_id' => $student->id,
        'term_id' => $term->id,
        'date' => now(),
        'status' => $status,
        'marked_by' => $teacher->id,
    ]);

    FeePayment::factory()->create([
        'student_id' => $student->id,
        'term_id' => $term->id,
        'amount' => $score * 10,
    ]);

    return [$student, $class];
}

/** Two independent students in the same stream, each with their own records. */
function isolationTwoStudents(): array
{
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $term = Term::factory()->create(['is_active' => true]);

    [$studentA] = isolationStudentWithRecords($stream, $term, 'ADM-STU-A', 90, 'present');
    [$studentB] = isolationStudentWithRecords($stream, $term, 'ADM-STU-B', 30, 'absent');

    return [$studentA, $studentB, $term];
}

// ── Structural guard: student routes carry no id parameter ──────────────────

test('no route behind the student role accepts an id parameter', function () {
    $studentRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('role:student', $route->gatherMiddleware(), true));

    expect($studentRoutes)->not->toBeEmpty();

    foreach ($studentRoutes as $route) {
        $this->assertSame(
            [],
            $route->parameterNames(),
            "Route [{$route->uri()}] takes a parameter. Student pages derive identity from the "
            .'session; adding a parameter opens a cross-student surface that needs its own '
            .'ownership check and its own isolation test.'
        );
    }
});

// ── Student pages expose only the authenticated student's records ───────────

test('student grades page contains only the authenticated student records', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('student.grades'))
        ->assertOk();

    $studentIds = collect($response->viewData('grades'))
        ->flatten(1)
        ->pluck('student_id')
        ->unique()
        ->values()
        ->all();

    expect($studentIds)->toBe([$studentA->id])
        ->and($studentIds)->not->toContain($studentB->id);
});

test('student attendance page contains only the authenticated student records', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('student.attendance'))
        ->assertOk();

    $studentIds = collect($response->viewData('attendanceRecords')->items())
        ->pluck('student_id')
        ->unique()
        ->values()
        ->all();

    expect($studentIds)->toBe([$studentA->id])
        ->and($studentIds)->not->toContain($studentB->id);
});

test('student fees page contains only the authenticated student payments', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('student.fees'))
        ->assertOk();

    $studentIds = collect($response->viewData('payments'))
        ->pluck('student_id')
        ->unique()
        ->values()
        ->all();

    expect($studentIds)->toBe([$studentA->id])
        ->and($studentIds)->not->toContain($studentB->id);
});

test('student report card is built for the authenticated student only', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('student.report'))
        ->assertOk();

    expect($response->viewData('student')->id)->toBe($studentA->id);

    $response->assertDontSee('ADM-STU-B');
    expect($studentB->admission_number)->toBe('ADM-STU-B');
});

test('student dashboard counts only the authenticated student attendance', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('dashboard'))
        ->assertOk();

    // studentA has exactly one 'present' record; studentB has one 'absent'.
    expect($response->viewData('student')->id)->toBe($studentA->id)
        ->and($response->viewData('presentCount'))->toBe(1)
        ->and($response->viewData('absentCount'))->toBe(0);
});

// ── Parameter tampering on student pages is ignored ─────────────────────────

test('student pages ignore an injected student id query parameter', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('student.report').'?student_id='.$studentB->id.'&id='.$studentB->id)
        ->assertOk();

    expect($response->viewData('student')->id)->toBe($studentA->id);

    $feesResponse = $this->actingAs($studentA)
        ->get(route('student.fees').'?student_id='.$studentB->id)
        ->assertOk();

    $studentIds = collect($feesResponse->viewData('payments'))->pluck('student_id')->unique()->all();

    expect($studentIds)->not->toContain($studentB->id);
});

test('student report card pdf is generated for the authenticated student only', function () {
    [$studentA] = isolationTwoStudents();

    $response = $this->actingAs($studentA)
        ->get(route('student.report.pdf'))
        ->assertOk();

    expect($response->headers->get('content-disposition'))
        ->toContain(str_replace(' ', '-', strtolower($studentA->name)));
});

// ── Id-taking routes in other namespaces stay closed to a student ───────────

test('student cannot reach another student records through admin routes', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $this->actingAs($studentA)
        ->get(route('admin.reports.generate', $studentB->id))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($studentA)
        ->get(route('admin.reports.pdf', $studentB->id))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($studentA)
        ->get(route('admin.reports.attendance.csv', $studentB->id))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($studentA)
        ->get(route('admin.fees.student', $studentB->id))
        ->assertRedirect(route('dashboard'));
});

test('student cannot reach another student records through parent routes', function () {
    [$studentA, $studentB] = isolationTwoStudents();

    $this->actingAs($studentA)
        ->get(route('parent.child.grades', $studentB->id))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($studentA)
        ->get(route('parent.child.report_card', $studentB->id))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($studentA)
        ->get(route('parent.child.fees', $studentB->id))
        ->assertRedirect(route('dashboard'));
});

test('student cannot record a fee payment against another student', function () {
    [$studentA, $studentB, $term] = isolationTwoStudents();

    $this->actingAs($studentA)
        ->post(route('admin.fees.student.payment', $studentB->id), [
            'term_id' => $term->id,
            'amount' => 1,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('fee_payments', [
        'student_id' => $studentB->id,
        'amount' => 1,
    ]);
});
