<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A direct port of the authorizeChild() helper in Parent\DashboardController:
 *
 *     User::where('usertype', 'student')->where('parent_id', $parent->id)->findOrFail($id)
 *
 * Both conditions are preserved deliberately. The usertype check is what stops
 * a parent passing another parent's user id, and the parent_id check is what
 * stops them passing another parent's child.
 *
 * Denials use denyAsNotFound() so the response stays a 404, matching the scoped
 * findOrFail() this replaces — a parent cannot distinguish "not your child"
 * from "no such user".
 *
 * NOTE: this policy is bound to User::class, so it governs every
 * authorize(..., $user) call on a User model. It intentionally has no admin
 * bypass: admin routes do not use policies today, and adding one here would go
 * beyond porting the existing behaviour.
 */
class StudentPolicy
{
    /** View a student's grades, attendance, report card, or fees. */
    public function view(User $user, User $student): Response
    {
        return $student->usertype === 'student' && $student->parent_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
