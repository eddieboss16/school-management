<?php

/**
 * The policies tested directly, without going through HTTP.
 *
 * The controller-level behaviour is already covered by TeacherIsolationTest and
 * ParentIsolationTest; these assert the rules themselves, including the denial
 * STATUS, which is the part most likely to drift (a plain deny() would turn
 * every 404 in this app into a 403).
 */

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\User;
use App\Policies\ClassPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

function policyTestClass(User $teacher): SchoolClass
{
    $grade = Grade::factory()->create();
    $stream = Stream::factory()->create(['grade_id' => $grade->id]);

    return SchoolClass::factory()->create([
        'teacher_id' => $teacher->id,
        'grade_id' => $grade->id,
        'stream_id' => $stream->id,
        'subject_id' => Subject::factory()->create()->id,
    ]);
}

// ── ClassPolicy ─────────────────────────────────────────────────────────────

test('ClassPolicy allows the owning teacher to view and update', function () {
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $class = policyTestClass($teacher);
    $policy = new ClassPolicy;

    expect($policy->view($teacher, $class)->allowed())->toBeTrue()
        ->and($policy->update($teacher, $class)->allowed())->toBeTrue();
});

test('ClassPolicy denies a teacher who does not own the class', function () {
    $owner = User::factory()->create(['usertype' => 'teacher']);
    $other = User::factory()->create(['usertype' => 'teacher']);
    $class = policyTestClass($owner);
    $policy = new ClassPolicy;

    expect($policy->view($other, $class)->allowed())->toBeFalse()
        ->and($policy->update($other, $class)->allowed())->toBeFalse();
});

test('ClassPolicy denies as 404 so class existence is not leaked', function () {
    $owner = User::factory()->create(['usertype' => 'teacher']);
    $other = User::factory()->create(['usertype' => 'teacher']);
    $class = policyTestClass($owner);
    $policy = new ClassPolicy;

    expect($policy->view($other, $class)->status())->toBe(404)
        ->and($policy->update($other, $class)->status())->toBe(404);
});

test('ClassPolicy denies non-teacher roles that happen to hold a user id', function () {
    $teacher = User::factory()->create(['usertype' => 'teacher']);
    $class = policyTestClass($teacher);
    $policy = new ClassPolicy;

    foreach (['student', 'parent', 'admin'] as $usertype) {
        $user = User::factory()->create(['usertype' => $usertype]);
        expect($policy->view($user, $class)->allowed())->toBeFalse();
    }
});

// ── StudentPolicy ───────────────────────────────────────────────────────────

test('StudentPolicy allows a parent to view their own child', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);
    $child = User::factory()->create(['usertype' => 'student', 'parent_id' => $parent->id]);

    expect((new StudentPolicy)->view($parent, $child)->allowed())->toBeTrue();
});

test('StudentPolicy denies a parent viewing another parent child', function () {
    $parentA = User::factory()->create(['usertype' => 'parent']);
    $parentB = User::factory()->create(['usertype' => 'parent']);
    $childB = User::factory()->create(['usertype' => 'student', 'parent_id' => $parentB->id]);

    expect((new StudentPolicy)->view($parentA, $childB)->allowed())->toBeFalse();
});

test('StudentPolicy denies a student with no parent link', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);
    $unlinked = User::factory()->create(['usertype' => 'student', 'parent_id' => null]);

    expect((new StudentPolicy)->view($parent, $unlinked)->allowed())->toBeFalse();
});

test('StudentPolicy denies a non-student target even when parent_id matches', function () {
    // Guards the usertype half of the ported check: without it, a parent could
    // pass a non-student user id that happens to carry their parent_id.
    $parent = User::factory()->create(['usertype' => 'parent']);
    $notPupil = User::factory()->create(['usertype' => 'teacher', 'parent_id' => $parent->id]);

    expect((new StudentPolicy)->view($parent, $notPupil)->allowed())->toBeFalse();
});

test('StudentPolicy denies as 404 so user existence is not leaked', function () {
    $parentA = User::factory()->create(['usertype' => 'parent']);
    $parentB = User::factory()->create(['usertype' => 'parent']);
    $childB = User::factory()->create(['usertype' => 'student', 'parent_id' => $parentB->id]);

    expect((new StudentPolicy)->view($parentA, $childB)->status())->toBe(404);
});

// ── Registration ────────────────────────────────────────────────────────────

test('both policies are resolved by the Gate', function () {
    expect(Gate::getPolicyFor(SchoolClass::class))->toBeInstanceOf(ClassPolicy::class)
        ->and(Gate::getPolicyFor(User::class))->toBeInstanceOf(StudentPolicy::class);
});

test('Gate authorization through the container matches the policy result', function () {
    $owner = User::factory()->create(['usertype' => 'teacher']);
    $other = User::factory()->create(['usertype' => 'teacher']);
    $class = policyTestClass($owner);

    expect(Gate::forUser($owner)->allows('view', $class))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $class))->toBeFalse();
});
