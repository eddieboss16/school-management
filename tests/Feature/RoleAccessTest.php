<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Role redirect after login ─────────────────────────────────────────────────

test('admin is redirected to admin dashboard after login', function () {
    $admin = User::factory()->create(['usertype' => 'admin']);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('teacher is redirected to teacher dashboard after login', function () {
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    $this->post('/login', ['email' => $teacher->email, 'password' => 'password'])
        ->assertRedirect(route('teacher.dashboard'));
});

test('student is redirected to student dashboard after login', function () {
    $student = User::factory()->create(['usertype' => 'student']);

    $this->post('/login', ['email' => $student->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('parent is redirected to parent dashboard after login', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);

    $this->post('/login', ['email' => $parent->email, 'password' => 'password'])
        ->assertRedirect(route('parent.dashboard'));
});

// ── Role middleware blocks cross-access ───────────────────────────────────────

test('student cannot access admin dashboard', function () {
    $student = User::factory()->create(['usertype' => 'student']);

    $this->actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertRedirect();
});

test('teacher cannot access admin dashboard', function () {
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    $this->actingAs($teacher)
        ->get(route('admin.dashboard'))
        ->assertRedirect();
});

test('parent cannot access admin dashboard', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);

    $this->actingAs($parent)
        ->get(route('admin.dashboard'))
        ->assertRedirect();
});

test('student cannot access teacher routes', function () {
    $student = User::factory()->create(['usertype' => 'student']);

    $this->actingAs($student)
        ->get(route('teacher.dashboard'))
        ->assertRedirect();
});

// ── Public register route is disabled ────────────────────────────────────────

test('public register route does not exist', function () {
    $this->get('/register')->assertStatus(404);
    $this->post('/register')->assertStatus(404);
});

// ── Unauthenticated users are redirected to login ─────────────────────────────

test('unauthenticated user is redirected from admin dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from student dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
