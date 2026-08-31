<?php

/**
 * The admin usertype guards: passing an id that belongs to a different kind of
 * user redirects back to the listing with a flash message.
 *
 * This is input validation, not authorization — admins reach every record, and
 * `role:admin` is the gate. The response is deliberately a 302 + flash rather
 * than the 403/404 a Policy would produce, so these assertions pin the exact
 * shape: status, redirect target, and message text.
 *
 * Written while extracting the six duplicated guard blocks into
 * FindsUsersByType. Nothing covered these responses before, and the extraction
 * silently turned three of them into 500s until this caught it.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('student routes expecting a student id', [
    'edit'    => ['get', 'admin.students.edit'],
    'update'  => ['put', 'admin.students.update'],
    'destroy' => ['delete', 'admin.students.destroy'],
]);

dataset('teacher routes expecting a teacher id', [
    'edit'    => ['get', 'admin.teachers.edit'],
    'update'  => ['put', 'admin.teachers.update'],
    'destroy' => ['delete', 'admin.teachers.destroy'],
]);

test('student routes reject a non-student id with a redirect and flash', function (string $verb, string $routeName) {
    $admin   = User::factory()->create(['usertype' => 'admin']);
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    $this->actingAs($admin)
        ->{$verb}(route($routeName, $teacher->id))
        ->assertStatus(302)
        ->assertRedirect(route('admin.students'))
        ->assertSessionHas('error', 'Invalid student ID');

    // The wrong-type user must be left completely untouched.
    expect($teacher->fresh()->deleted_at)->toBeNull()
        ->and($teacher->fresh()->usertype)->toBe('teacher');
})->with('student routes expecting a student id');

test('teacher routes reject a non-teacher id with a redirect and flash', function (string $verb, string $routeName) {
    $admin   = User::factory()->create(['usertype' => 'admin']);
    $student = User::factory()->create(['usertype' => 'student']);

    $this->actingAs($admin)
        ->{$verb}(route($routeName, $student->id))
        ->assertStatus(302)
        ->assertRedirect(route('admin.teachers'))
        ->assertSessionHas('error', 'Invalid teacher ID');

    expect($student->fresh()->deleted_at)->toBeNull()
        ->and($student->fresh()->usertype)->toBe('student');
})->with('teacher routes expecting a teacher id');

// ── The guard must not swallow the missing-record case ──────────────────────

test('a missing id is still a 404, not a redirect', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);

    $this->actingAs($admin)->get(route('admin.students.edit', 999999))->assertStatus(404);
    $this->actingAs($admin)->get(route('admin.teachers.edit', 999999))->assertStatus(404);
});

// ── Correct-type ids still work ─────────────────────────────────────────────

test('the guard lets a correctly typed id through', function () {
    $admin   = User::factory()->create(['usertype' => 'admin']);
    $student = User::factory()->create(['usertype' => 'student']);
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    $this->actingAs($admin)->get(route('admin.students.edit', $student->id))->assertOk();
    $this->actingAs($admin)->get(route('admin.teachers.edit', $teacher->id))->assertOk();
});
