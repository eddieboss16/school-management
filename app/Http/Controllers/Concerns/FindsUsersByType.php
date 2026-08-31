<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * The admin user controllers all begin the same way: load a user by id, then
 * bail out if that id belongs to a different kind of user. This collects the
 * six copies of that block into one place.
 *
 * It is deliberately NOT a Policy. The check is input validation, not
 * authorization — admins legitimately reach every record, and `role:admin`
 * is the gate. Its response is a 302 back to the listing with a flash
 * message, which is what the admin UI expects; a policy would make it a
 * 403/404 error page. See the access-control section of CLAUDE.md.
 */
trait FindsUsersByType
{
    /**
     * Load a user and confirm they are of the expected type.
     *
     * Returns the User on success, or a RedirectResponse the caller must
     * return as-is. A missing id still throws ModelNotFoundException (404),
     * exactly as the inline findOrFail() did.
     */
    protected function findUserOfType(int|string $id, string $usertype): User|RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->usertype !== $usertype) {
            [$route, $message] = $this->guardFailureFor($usertype);

            return redirect()->route($route)->with('error', $message);
        }

        return $user;
    }

    /**
     * Where to send the admin, and what to tell them, when the id is the
     * wrong kind of user. Kept verbatim from the blocks this replaced.
     *
     * @return array{0: string, 1: string}
     */
    private function guardFailureFor(string $usertype): array
    {
        return match ($usertype) {
            'student' => ['admin.students', 'Invalid student ID'],
            'teacher' => ['admin.teachers', 'Invalid teacher ID'],
        };
    }
}
