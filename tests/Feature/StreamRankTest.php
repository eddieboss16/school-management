<?php

/**
 * Stream ranking.
 *
 * The calculation used to be a private method copied into both
 * ReportCardController and Parent\DashboardController, with a note asking that
 * they be kept in sync and nothing enforcing it. It now lives in
 * App\Support\StreamRank.
 *
 * The ranking rules are asserted directly against that class; one integration
 * test then pins all three surfaces — admin, student, parent — to the same
 * answer, which is the part that could silently drift before.
 */

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Support\StreamRank;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A stream of students scoring the given marks in one subject.
 *
 * @param  array<string,int|null>  $scores  label => mark (null = no grades)
 * @return array{students: array<string,User>, parents: array<string,User>,
 *               term: Term, otherTerm: Term, class: SchoolClass, teacher: User, admin: User}
 */
function streamOf(array $scores): array
{
    $admin = User::factory()->create(['usertype' => 'admin']);
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $term = Term::factory()->create(['is_active' => true]);
    $other = Term::factory()->create(['is_active' => false]);

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => $subject->id,
    ]);

    $students = [];
    $parents = [];

    foreach ($scores as $label => $score) {
        $parent = User::factory()->create(['usertype' => 'parent']);
        $student = User::factory()->create([
            'usertype' => 'student',
            'stream_id' => $stream->id,
            'parent_id' => $parent->id,
        ]);

        $class->students()->attach($student->id);

        if ($score !== null) {
            StudentGrade::factory()->create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'term_id' => $term->id,
                'score' => $score,
                'max_score' => 100,
                'assessment_type' => 'Exam',
                'assessment_date' => now()->format('Y-m-d'),
                'entered_by' => $teacher->id,
            ]);
        }

        $students[$label] = $student;
        $parents[$label] = $parent;
    }

    return compact('students', 'parents', 'term', 'class', 'teacher', 'admin')
        + ['otherTerm' => $other];
}

// ── Ranking rules ───────────────────────────────────────────────────────────

test('students rank in descending order of average', function () {
    $f = streamOf(['top' => 90, 'middle' => 70, 'bottom' => 50]);

    expect(StreamRank::forStudent($f['students']['top'], $f['term']->id))->toBe([1, 3])
        ->and(StreamRank::forStudent($f['students']['middle'], $f['term']->id))->toBe([2, 3])
        ->and(StreamRank::forStudent($f['students']['bottom'], $f['term']->id))->toBe([3, 3]);
});

test('tied students share a position and consume the places behind them', function () {
    $f = streamOf(['a' => 75, 'b' => 75, 'c' => 40]);

    expect(StreamRank::forStudent($f['students']['a'], $f['term']->id))->toBe([1, 3])
        ->and(StreamRank::forStudent($f['students']['b'], $f['term']->id))->toBe([1, 3])
        // Two firsts, so the next student is third rather than second.
        ->and(StreamRank::forStudent($f['students']['c'], $f['term']->id))->toBe([3, 3]);
});

test('a lone student in a stream is first of one', function () {
    $f = streamOf(['only' => 61]);

    expect(StreamRank::forStudent($f['students']['only'], $f['term']->id))->toBe([1, 1]);
});

test('a student with no stream has no position', function () {
    $student = User::factory()->create(['usertype' => 'student', 'stream_id' => null]);

    expect(StreamRank::forStudent($student))->toBe([null, null]);
});

test('a student with no grades ranks last rather than being dropped', function () {
    $f = streamOf(['scored' => 55, 'ungraded' => null]);

    expect(StreamRank::forStudent($f['students']['scored'], $f['term']->id))->toBe([1, 2])
        ->and(StreamRank::forStudent($f['students']['ungraded'], $f['term']->id))->toBe([2, 2]);
});

test('ranking is scoped to the term', function () {
    $f = streamOf(['a' => 90, 'b' => 60]);

    // Reverse the standings in the other term.
    foreach (['a' => 30, 'b' => 95] as $label => $score) {
        StudentGrade::factory()->create([
            'student_id' => $f['students'][$label]->id,
            'class_id' => $f['class']->id,
            'term_id' => $f['otherTerm']->id,
            'score' => $score,
            'max_score' => 100,
            'assessment_type' => 'Exam',
            'assessment_date' => now()->format('Y-m-d'),
            'entered_by' => $f['teacher']->id,
        ]);
    }

    expect(StreamRank::forStudent($f['students']['a'], $f['term']->id))->toBe([1, 2])
        ->and(StreamRank::forStudent($f['students']['a'], $f['otherTerm']->id))->toBe([2, 2]);
});

test('with no term the ranking spans every term at once', function () {
    $f = streamOf(['a' => 90, 'b' => 60]);

    // b outscores a heavily in the other term, enough to overturn the order.
    StudentGrade::factory()->create([
        'student_id' => $f['students']['b']->id,
        'class_id' => $f['class']->id,
        'term_id' => $f['otherTerm']->id,
        'score' => 100,
        'max_score' => 100,
        'assessment_type' => 'Exam',
        'assessment_date' => now()->format('Y-m-d'),
        'entered_by' => $f['teacher']->id,
    ]);

    // a: 90. b: mean of 60 and 100 = 80.
    expect(StreamRank::forStudent($f['students']['a']))->toBe([1, 2])
        ->and(StreamRank::forStudent($f['students']['b']))->toBe([2, 2]);
});

test('students in another stream do not affect the ranking', function () {
    $f = streamOf(['a' => 50]);

    $otherStream = Stream::factory()->create(['grade_id' => Grade::factory()->create()->id]);
    User::factory()->count(3)->create(['usertype' => 'student', 'stream_id' => $otherStream->id]);

    expect(StreamRank::forStudent($f['students']['a'], $f['term']->id))->toBe([1, 1]);
});

// ── Every surface reports the same rank ─────────────────────────────────────

test('admin, student and parent report cards agree on the rank', function () {
    $f = streamOf(['top' => 90, 'bottom' => 50]);
    $termId = $f['term']->id;

    foreach (['top', 'bottom'] as $label) {
        $student = $f['students'][$label];
        $parent = $f['parents'][$label];

        $fromAdmin = $this->actingAs($f['admin'])
            ->get(route('admin.reports.generate', ['id' => $student->id, 'term_id' => $termId]))
            ->assertOk();

        $fromStudent = $this->actingAs($student)
            ->get(route('student.report', ['term_id' => $termId]))
            ->assertOk();

        $fromParent = $this->actingAs($parent)
            ->get(route('parent.child.report_card', ['id' => $student->id, 'term_id' => $termId]))
            ->assertOk();

        $expected = StreamRank::forStudent($student, $termId);

        foreach (['admin' => $fromAdmin, 'student' => $fromStudent, 'parent' => $fromParent] as $who => $response) {
            expect([$response->viewData('streamPosition'), $response->viewData('streamSize')])
                ->toBe($expected, "{$who} report card disagreed on the rank for {$label}");
        }
    }
});

test('neither controller keeps a private copy of the calculation', function () {
    foreach ([
        \App\Http\Controllers\ReportCardController::class,
        \App\Http\Controllers\Parent\DashboardController::class,
    ] as $controller) {
        expect(method_exists($controller, 'computeStreamRank'))
            ->toBeFalse("{$controller} still defines computeStreamRank");
    }
});
