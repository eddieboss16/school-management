<?php

/**
 * The grading scale, pinned at every band boundary.
 *
 * These were previously six copies of an @if/@elseif chain spread across the
 * report templates. The boundaries are inclusive lower bounds, so a score
 * exactly on a boundary takes the higher band — that is the behaviour the
 * inline chains had, and it is what these assertions lock in.
 */

use App\Support\Grading;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('letter boundaries', [
    'well above top band' => [100.0, 'A'],
    'exactly A'           => [70.0, 'A'],
    'just below A'        => [69.99, 'B'],
    'exactly B'           => [60.0, 'B'],
    'just below B'        => [59.99, 'C'],
    'exactly C'           => [50.0, 'C'],
    'just below C'        => [49.99, 'D'],
    'exactly D'           => [40.0, 'D'],
    'just below D'        => [39.99, 'F'],
    'zero'                => [0.0, 'F'],
]);

test('letter grade at each boundary', function (float $percentage, string $expected) {
    expect(Grading::letter($percentage))->toBe($expected);
})->with('letter boundaries');

test('letter grade accepts int and null', function () {
    expect(Grading::letter(70))->toBe('A')
        ->and(Grading::letter(0))->toBe('F')
        ->and(Grading::letter(null))->toBe('F');
});

dataset('colour boundaries', [
    'top'          => [100.0, 'good'],
    'exactly good' => [70.0, 'good'],
    'below good'   => [69.99, 'fair'],
    'exactly fair' => [50.0, 'fair'],
    'below fair'   => [49.99, 'poor'],
    'zero'         => [0.0, 'poor'],
]);

test('colour band at each boundary', function (float $percentage, string $expected) {
    expect(Grading::band($percentage))->toBe($expected);
})->with('colour boundaries');

test('badge and text classes follow the colour band', function () {
    expect(Grading::badgeClass(70))->toBe('bg-green-100 text-green-800')
        ->and(Grading::badgeClass(50))->toBe('bg-yellow-100 text-yellow-800')
        ->and(Grading::badgeClass(49))->toBe('bg-red-100 text-red-800')
        ->and(Grading::textClass(70))->toBe('text-green-600')
        ->and(Grading::textClass(50))->toBe('text-yellow-600')
        ->and(Grading::textClass(49))->toBe('text-red-600');
});

test('pdf badge class pairs with the letter', function () {
    expect(Grading::pdfBadgeClass(70))->toBe('grade-badge grade-a')
        ->and(Grading::pdfBadgeClass(60))->toBe('grade-badge grade-b')
        ->and(Grading::pdfBadgeClass(50))->toBe('grade-badge grade-c')
        ->and(Grading::pdfBadgeClass(40))->toBe('grade-badge grade-d')
        ->and(Grading::pdfBadgeClass(39))->toBe('grade-badge grade-f');
});

test('the scale is driven by config, not hardcoded in the helper', function () {
    config(['grading.letters' => [80 => 'A', 65 => 'B'], 'grading.fallback' => 'U']);

    expect(Grading::letter(85))->toBe('A')
        ->and(Grading::letter(70))->toBe('B')
        ->and(Grading::letter(60))->toBe('U');
});

// ── The notification keeps its own, different scale ─────────────────────────

test('DOCUMENTED DIVERGENCE: the email letter differs from the report card letter', function () {
    // GradesPostedNotification uses an 11-band scale with different cut-offs.
    // At 70% a report card says A while the email says B+. This test exists so
    // the divergence is visible and deliberate rather than an unnoticed bug.
    $notification = new ReflectionClass(\App\Notifications\GradesPostedNotification::class);
    $method       = $notification->getMethod('getLetterGrade');
    $method->setAccessible(true);

    $instance = $notification->newInstanceWithoutConstructor();

    expect($method->invoke($instance, 70.0))->toBe('B+')
        ->and(Grading::letter(70.0))->toBe('A');
});
