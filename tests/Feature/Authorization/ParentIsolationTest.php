<?php

/**
 * Characterization tests: a parent may only reach children linked to them.
 *
 * Every Parent\DashboardController action funnels through the private
 * authorizeChild() helper, which scopes by usertype + parent_id and calls
 * findOrFail() — so the non-owner path is a 404. authorizeChild() is private
 * and therefore covered here at the feature level rather than in isolation.
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

uses(RefreshDatabase::class);

/**
 * Build an independent parent with one linked child who has grades,
 * attendance, and a fee payment of their own.
 *
 * @return array{0: User, 1: User}
 */
function isolationParentWithChild(string $admissionNumber): array
{
    $parent = User::factory()->create(['usertype' => 'parent']);

    $grade   = Grade::factory()->create();
    $stream  = Stream::factory()->create(['grade_id' => $grade->id]);
    $subject = Subject::factory()->create();
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $term    = Term::factory()->create();

    $child = User::factory()->create([
        'usertype'         => 'student',
        'parent_id'        => $parent->id,
        'stream_id'        => $stream->id,
        'admission_number' => $admissionNumber,
    ]);

    $class = SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
        'grade_id'   => $grade->id,
        'stream_id'  => $stream->id,
        'subject_id' => $subject->id,
    ]);
    $class->students()->attach($child->id);

    // entered_by is required by the schema even though the factory defaults it to null.
    StudentGrade::factory()->create([
        'class_id'   => $class->id,
        'student_id' => $child->id,
        'term_id'    => $term->id,
        'score'      => 80,
        'max_score'  => 100,
        'entered_by' => $teacher->id,
    ]);

    Attendance::create([
        'class_id'   => $class->id,
        'student_id' => $child->id,
        'term_id'    => $term->id,
        'date'       => now(),
        'status'     => 'present',
        'marked_by'  => $teacher->id,
    ]);

    FeePayment::factory()->create([
        'student_id' => $child->id,
        'term_id'    => $term->id,
        'amount'     => 1500,
    ]);

    return [$parent, $child];
}

/** Every parent route that accepts a child id. */
dataset('parent child routes', [
    'grades'      => ['parent.child.grades'],
    'attendance'  => ['parent.child.attendance'],
    'report card' => ['parent.child.report_card'],
    'fees'        => ['parent.child.fees'],
]);

// ── Parent A cannot reach Parent B's child ───────────────────────────────────

test('parent cannot view another parent child', function (string $routeName) {
    [$parentA] = isolationParentWithChild('ADM-ISO-A1');
    [, $childB] = isolationParentWithChild('ADM-ISO-B1');

    $this->actingAs($parentA)
        ->get(route($routeName, $childB->id))
        ->assertStatus(404);
})->with('parent child routes');

test('parent cannot view a student who has no parent linked', function (string $routeName) {
    [$parentA] = isolationParentWithChild('ADM-ISO-A2');
    $unlinked = User::factory()->create(['usertype' => 'student', 'parent_id' => null]);

    $this->actingAs($parentA)
        ->get(route($routeName, $unlinked->id))
        ->assertStatus(404);
})->with('parent child routes');

test('parent cannot pass a non-student user id as a child', function (string $routeName) {
    [$parentA] = isolationParentWithChild('ADM-ISO-A3');
    [$parentB] = isolationParentWithChild('ADM-ISO-B3');

    // A parent account id is not a student, so the usertype filter must reject it.
    $this->actingAs($parentA)
        ->get(route($routeName, $parentB->id))
        ->assertStatus(404);
})->with('parent child routes');

test('parent cannot reach another parent child by adding a term filter', function () {
    [$parentA] = isolationParentWithChild('ADM-ISO-A4');
    [, $childB] = isolationParentWithChild('ADM-ISO-B4');
    $term = Term::factory()->create(['is_active' => true]);

    $this->actingAs($parentA)
        ->get(route('parent.child.report_card', $childB->id) . '?term_id=' . $term->id)
        ->assertStatus(404);
});

// ── Positive control: Parent A keeps full access to their own child ─────────

test('parent can view their own child pages', function (string $routeName) {
    [$parentA, $childA] = isolationParentWithChild('ADM-ISO-A5');

    $this->actingAs($parentA)
        ->get(route($routeName, $childA->id))
        ->assertOk()
        ->assertViewHas('child', fn (User $child) => $child->id === $childA->id);
})->with('parent child routes');

test('parent dashboard lists only their own children', function () {
    [$parentA, $childA] = isolationParentWithChild('ADM-ISO-A6');
    [, $childB] = isolationParentWithChild('ADM-ISO-B6');

    $response = $this->actingAs($parentA)
        ->get(route('parent.dashboard'))
        ->assertOk();

    $childIds = collect($response->viewData('children'))->pluck('id')->all();

    expect($childIds)->toBe([$childA->id])
        ->and($childIds)->not->toContain($childB->id);
});

test('child grades page contains only that child records', function () {
    [$parentA, $childA] = isolationParentWithChild('ADM-ISO-A7');
    [, $childB] = isolationParentWithChild('ADM-ISO-B7');

    $response = $this->actingAs($parentA)
        ->get(route('parent.child.grades', $childA->id))
        ->assertOk();

    $studentIds = collect($response->viewData('grades'))
        ->flatten(1)
        ->pluck('student_id')
        ->unique()
        ->values()
        ->all();

    expect($studentIds)->toBe([$childA->id])
        ->and($studentIds)->not->toContain($childB->id);
});

test('child fee page contains only that child payments', function () {
    [$parentA, $childA] = isolationParentWithChild('ADM-ISO-A8');
    [, $childB] = isolationParentWithChild('ADM-ISO-B8');

    $response = $this->actingAs($parentA)
        ->get(route('parent.child.fees', $childA->id))
        ->assertOk();

    $studentIds = collect($response->viewData('payments'))
        ->pluck('student_id')
        ->unique()
        ->values()
        ->all();

    expect($studentIds)->not->toContain($childB->id);
});

// ── Registration cannot be used to claim someone else's child ───────────────

test('registration still refuses a student who already has a parent and leaves the link intact', function () {
    [$parentB, $childB] = isolationParentWithChild('ADM-ISO-B9');

    $this->post(route('parent.register.store'), [
        'name'                  => 'Attacker',
        'email'                 => 'attacker-isolation@example.com',
        'admission_number'      => 'ADM-ISO-B9',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertSessionHasErrors('admission_number');

    // The original link must survive the attempt.
    expect($childB->fresh()->parent_id)->toBe($parentB->id);
    $this->assertDatabaseMissing('users', ['email' => 'attacker-isolation@example.com']);
});

test('a newly registered parent cannot see children they did not claim', function () {
    [, $childB] = isolationParentWithChild('ADM-ISO-B10');

    $ownStudent = User::factory()->create([
        'usertype'         => 'student',
        'admission_number' => 'ADM-ISO-C10',
        'parent_id'        => null,
    ]);

    $this->post(route('parent.register.store'), [
        'name'                  => 'New Parent',
        'email'                 => 'new-parent-isolation@example.com',
        'admission_number'      => 'ADM-ISO-C10',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertRedirect(route('parent.dashboard'));

    $newParent = User::where('email', 'new-parent-isolation@example.com')->firstOrFail();

    $this->actingAs($newParent)
        ->get(route('parent.child.grades', $childB->id))
        ->assertStatus(404);

    $this->actingAs($newParent)
        ->get(route('parent.child.grades', $ownStudent->id))
        ->assertOk();
});
