<?php

/**
 * Regression only — this file asserts existing CheckRole behaviour so that a
 * later refactor has something to prove it did not silently change it.
 *
 * CheckRole does not throw 403. A signed-in user who hits a route belonging to
 * another role is redirected to their OWN dashboard with an 'Access denied.'
 * flash, so the system never confirms that the foreign route exists.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** [acting usertype, foreign route name, route name of the acting user's own dashboard] */
dataset('role mismatches', [
    'admin hitting a teacher route'   => ['admin', 'teacher.dashboard', 'admin.dashboard'],
    'admin hitting a parent route'    => ['admin', 'parent.dashboard', 'admin.dashboard'],
    'admin hitting a student route'   => ['admin', 'dashboard', 'admin.dashboard'],

    'teacher hitting an admin route'  => ['teacher', 'admin.dashboard', 'teacher.dashboard'],
    'teacher hitting a parent route'  => ['teacher', 'parent.dashboard', 'teacher.dashboard'],
    'teacher hitting a student route' => ['teacher', 'dashboard', 'teacher.dashboard'],

    'student hitting an admin route'  => ['student', 'admin.dashboard', 'dashboard'],
    'student hitting a teacher route' => ['student', 'teacher.dashboard', 'dashboard'],
    'student hitting a parent route'  => ['student', 'parent.dashboard', 'dashboard'],

    'parent hitting an admin route'   => ['parent', 'admin.dashboard', 'parent.dashboard'],
    'parent hitting a teacher route'  => ['parent', 'teacher.dashboard', 'parent.dashboard'],
    'parent hitting a student route'  => ['parent', 'dashboard', 'parent.dashboard'],
]);

test('a role mismatch redirects to the acting user own dashboard', function (string $usertype, string $foreignRoute, string $ownDashboard) {
    $user = User::factory()->create(['usertype' => $usertype]);

    $this->actingAs($user)
        ->get(route($foreignRoute))
        ->assertRedirect(route($ownDashboard));
})->with('role mismatches');

test('a role mismatch flashes access denied rather than returning a 403', function (string $usertype, string $foreignRoute, string $ownDashboard) {
    $user = User::factory()->create(['usertype' => $usertype]);

    $response = $this->actingAs($user)->get(route($foreignRoute));

    $response->assertStatus(302);
    $response->assertSessionHas('error', 'Access denied.');
})->with('role mismatches');

test('the redirect target does not render foreign content', function (string $usertype, string $foreignRoute, string $ownDashboard) {
    $user = User::factory()->create(['usertype' => $usertype]);

    $response = $this->actingAs($user)->get(route($foreignRoute));

    // On a rendered page TestResponse::$original holds the View; on a redirect it
    // holds the RedirectResponse. So this asserts the foreign view never rendered.
    // (The body is not empty — Symfony redirects ship an HTML meta-refresh stub —
    // so asserting on markup would prove nothing.)
    expect($response->original)->not->toBeInstanceOf(\Illuminate\View\View::class);
    $response->assertRedirect(route($ownDashboard));
})->with('role mismatches');

// ── Middleware runs before the controller ───────────────────────────────────

test('a role mismatch is rejected before the controller looks up the record', function () {
    $parent = User::factory()->create(['usertype' => 'parent']);

    // Class 999999 does not exist. If the redirect happens, CheckRole ran before
    // the controller's findOrFail() — no 404 that would confirm the route exists.
    $this->actingAs($parent)
        ->get(route('teacher.attendance.mark', 999999))
        ->assertRedirect(route('parent.dashboard'));

    $this->actingAs($parent)
        ->get(route('admin.fees.student', 999999))
        ->assertRedirect(route('parent.dashboard'));
});

test('nested admin routes are protected by the same group middleware as the dashboard', function () {
    $teacher = User::factory()->create(['usertype' => 'teacher']);

    foreach (['admin.students', 'admin.teachers', 'admin.fees.structures', 'admin.activity-log', 'admin.reports.index'] as $adminRoute) {
        $this->actingAs($teacher)
            ->get(route($adminRoute))
            ->assertRedirect(route('teacher.dashboard'));
    }
});

// ── Unauthenticated users are sent to login, not to a dashboard ─────────────

test('an unauthenticated user hitting a role route is sent to login', function () {
    foreach (['admin.dashboard', 'teacher.dashboard', 'parent.dashboard', 'dashboard'] as $routeName) {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }
});
