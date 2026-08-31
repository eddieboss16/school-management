<?php

/**
 * The KCSE 12-point grading scale, pinned at every band boundary.
 *
 * Boundaries are inclusive lower bounds, so a percentage sitting exactly on a
 * boundary takes the higher band. There is no F on this scale — E is the fail
 * grade.
 */

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\GradesPostedNotification;
use App\Support\Grading;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** [percentage, letter, points] across every band and its lower edge. */
dataset('kcse boundaries', [
    'top of scale'  => [100.0, 'A', 12],
    'exactly A'     => [80.0, 'A', 12],
    'just below A'  => [79.99, 'A-', 11],
    'exactly A-'    => [75.0, 'A-', 11],
    'just below A-' => [74.99, 'B+', 10],
    'exactly B+'    => [70.0, 'B+', 10],
    'just below B+' => [69.99, 'B', 9],
    'exactly B'     => [65.0, 'B', 9],
    'just below B'  => [64.99, 'B-', 8],
    'exactly B-'    => [60.0, 'B-', 8],
    'just below B-' => [59.99, 'C+', 7],
    'exactly C+'    => [55.0, 'C+', 7],
    'just below C+' => [54.99, 'C', 6],
    'exactly C'     => [50.0, 'C', 6],
    'just below C'  => [49.99, 'C-', 5],
    'exactly C-'    => [45.0, 'C-', 5],
    'just below C-' => [44.99, 'D+', 4],
    'exactly D+'    => [40.0, 'D+', 4],
    'just below D+' => [39.99, 'D', 3],
    'exactly D'     => [35.0, 'D', 3],
    'just below D'  => [34.99, 'D-', 2],
    'exactly D-'    => [30.0, 'D-', 2],
    'just below D-' => [29.99, 'E', 1],
    'zero'          => [0.0, 'E', 1],
]);

test('letter and points at each KCSE boundary', function (float $percentage, string $letter, int $points) {
    expect(Grading::letter($percentage))->toBe($letter)
        ->and(Grading::points($percentage))->toBe($points);
})->with('kcse boundaries');

test('the scale has no F — E is the fail grade', function () {
    $letters = collect(config('grading.letters'))->pluck('letter')
        ->push(config('grading.fallback.letter'))
        ->all();

    expect($letters)->not->toContain('F')
        ->and($letters)->toContain('E')
        ->and($letters)->toHaveCount(12);
});

test('letter grade accepts int and null', function () {
    expect(Grading::letter(80))->toBe('A')
        ->and(Grading::letter(0))->toBe('E')
        ->and(Grading::letter(null))->toBe('E')
        ->and(Grading::points(null))->toBe(1);
});

// ── Colour is keyed to the letter tier, not a separate percentage cutoff ────

dataset('tier boundaries', [
    'A  top'    => [100.0, 'A'],
    'A  exact'  => [80.0, 'A'],
    'A- still A'=> [75.0, 'A'],
    'B+ starts' => [74.99, 'B'],
    'B- exact'  => [60.0, 'B'],
    'C+ starts' => [59.99, 'C'],
    'C- exact'  => [45.0, 'C'],
    'D+ starts' => [44.99, 'D'],
    'D- exact'  => [30.0, 'D'],
    'E  starts' => [29.99, 'E'],
    'zero'      => [0.0, 'E'],
]);

test('colour tier is the base letter at each boundary', function (float $percentage, string $expected) {
    expect(Grading::tier($percentage))->toBe($expected);
})->with('tier boundaries');

test('badge and text classes follow the letter tier', function () {
    // A- is green because it is an A-tier grade, not because it clears 80.
    expect(Grading::badgeClass(80))->toBe('bg-green-100 text-green-800')
        ->and(Grading::badgeClass(75))->toBe('bg-green-100 text-green-800')
        ->and(Grading::badgeClass(74))->toBe('bg-blue-100 text-blue-800')
        ->and(Grading::badgeClass(60))->toBe('bg-blue-100 text-blue-800')
        ->and(Grading::badgeClass(59))->toBe('bg-yellow-100 text-yellow-800')
        ->and(Grading::badgeClass(45))->toBe('bg-yellow-100 text-yellow-800')
        ->and(Grading::badgeClass(44))->toBe('bg-orange-100 text-orange-800')
        ->and(Grading::badgeClass(30))->toBe('bg-orange-100 text-orange-800')
        ->and(Grading::badgeClass(29))->toBe('bg-red-100 text-red-800');

    expect(Grading::textClass(75))->toBe('text-green-600')
        ->and(Grading::textClass(60))->toBe('text-blue-600')
        ->and(Grading::textClass(45))->toBe('text-yellow-600')
        ->and(Grading::textClass(30))->toBe('text-orange-600')
        ->and(Grading::textClass(0))->toBe('text-red-600');
});

test('screen colours agree with the PDF stylesheet tier for tier', function () {
    // pdfBadgeClass picks .grade-a … .grade-e; the screen tier must be the
    // same letter, or a badge would print a different colour than it displays.
    foreach ([100, 80, 75, 74, 60, 59, 45, 44, 30, 29, 0] as $score) {
        $tier = Grading::tier($score);

        expect(Grading::pdfBadgeClass($score))
            ->toBe('grade-badge grade-' . strtolower($tier), "tier mismatch at {$score}%");
    }
});

test('the js scale exposes every tier in descending order', function () {
    $scale = Grading::scaleForJs();

    expect($scale)->toHaveCount(12)
        ->and($scale[0]['min'])->toBe(80)
        ->and($scale[0]['text'])->toBe('text-green-600')
        ->and(end($scale)['min'])->toBe(0)
        ->and(end($scale)['text'])->toBe('text-red-600');

    $mins   = array_column($scale, 'min');
    $sorted = $mins;
    rsort($sorted);

    expect($mins)->toBe($sorted, 'js scale must be descending so find() picks the highest match');
});

test('pdf badge class collapses to the base letter the stylesheet defines', function () {
    // The print stylesheet only carries grade-a .. grade-e, so A-/A share a
    // style, as do B+/B/B-.
    expect(Grading::pdfBadgeClass(80))->toBe('grade-badge grade-a')
        ->and(Grading::pdfBadgeClass(75))->toBe('grade-badge grade-a')
        ->and(Grading::pdfBadgeClass(70))->toBe('grade-badge grade-b')
        ->and(Grading::pdfBadgeClass(60))->toBe('grade-badge grade-b')
        ->and(Grading::pdfBadgeClass(55))->toBe('grade-badge grade-c')
        ->and(Grading::pdfBadgeClass(40))->toBe('grade-badge grade-d')
        ->and(Grading::pdfBadgeClass(29))->toBe('grade-badge grade-e');
});

test('the scale is driven by config, not hardcoded in the helper', function () {
    config([
        'grading.letters'  => [90 => ['letter' => 'Distinction', 'points' => 2]],
        'grading.fallback' => ['letter' => 'Pass', 'points' => 1],
    ]);

    expect(Grading::letter(95))->toBe('Distinction')
        ->and(Grading::points(95))->toBe(2)
        ->and(Grading::letter(80))->toBe('Pass');
});

// ── The email and the report card must agree ────────────────────────────────

test('the emailed letter matches the report card letter at every KCSE boundary', function () {
    $grade   = Grade::factory()->create();
    $stream  = Stream::factory()->create(['grade_id' => $grade->id]);
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $parent  = User::factory()->create(['usertype' => 'parent']);
    $student = User::factory()->create([
        'usertype' => 'student', 'stream_id' => $stream->id, 'parent_id' => $parent->id,
    ]);

    $subject = new Subject();
    $subject->name = 'Mathematics';
    $subject->code = 'MTH-GRD';
    $subject->save();

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id, 'grade_id' => $grade->id,
        'stream_id'  => $stream->id, 'subject_id' => $subject->id,
    ]);

    foreach ([100, 80, 79, 75, 74, 70, 69, 65, 64, 60, 59, 55, 54, 50, 49, 45, 44, 40, 39, 35, 34, 30, 29, 0] as $score) {
        $percentage = (float) $score;

        $mail = (new GradesPostedNotification(
            $student, $class, 'Boundary Exam', $percentage, 100.0, $percentage
        ))->toMail($parent);

        preg_match('/Grade:\s*(\S+)/', implode("\n", $mail->introLines), $matches);

        expect($matches[1] ?? null)->toBe(
            Grading::letter($percentage),
            "email letter disagreed with the report card at {$score}%"
        );
    }
});

test('the notification no longer carries its own grading scale', function () {
    expect(method_exists(GradesPostedNotification::class, 'getLetterGrade'))->toBeFalse();
});
