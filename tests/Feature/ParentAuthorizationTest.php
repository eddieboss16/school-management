<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Parent registration ───────────────────────────────────────────────────────

test('parent can register with a valid admission number', function () {
    $student = User::factory()->create([
        'usertype' => 'student',
        'admission_number' => 'ADM001',
        'parent_id' => null,
    ]);

    $this->post(route('parent.register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'admission_number' => 'ADM001',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertRedirect(route('parent.dashboard'));

    $student->refresh();
    expect($student->parent_id)->not->toBeNull();
});

test('parent cannot register with an invalid admission number', function () {
    $this->post(route('parent.register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'admission_number' => 'INVALID',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertSessionHasErrors('admission_number');
});

test('parent cannot claim a student who already has a parent', function () {
    $existingParent = User::factory()->create(['usertype' => 'parent']);
    $student = User::factory()->create([
        'usertype' => 'student',
        'admission_number' => 'ADM002',
        'parent_id' => $existingParent->id,
    ]);

    $this->post(route('parent.register.store'), [
        'name' => 'Attacker',
        'email' => 'attacker@example.com',
        'admission_number' => 'ADM002',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertSessionHasErrors('admission_number');
});

// ── Parent can only see their own children ────────────────────────────────────

test('parent can view their own child grades page', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);
    $child = User::factory()->create(['usertype' => 'student', 'parent_id' => $parent->id]);

    $this->actingAs($parent)
        ->get(route('parent.child.grades', $child->id))
        ->assertOk();
});

test('parent cannot view another parent\'s child grades', function () {
    $parentA = User::factory()->create(['usertype' => 'parent']);
    $parentB = User::factory()->create(['usertype' => 'parent']);
    $childB = User::factory()->create(['usertype' => 'student', 'parent_id' => $parentB->id]);

    $this->actingAs($parentA)
        ->get(route('parent.child.grades', $childB->id))
        ->assertStatus(404);
});

test('parent cannot view a child page with no parent relationship', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);
    $unlinkedStudent = User::factory()->create(['usertype' => 'student', 'parent_id' => null]);

    $this->actingAs($parent)
        ->get(route('parent.child.grades', $unlinkedStudent->id))
        ->assertStatus(404);
});
