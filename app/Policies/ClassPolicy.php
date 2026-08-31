<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Centralises the teacher-owns-class check that was previously repeated as
 * SchoolClass::where('teacher_id', $teacher->id)->findOrFail($classId)
 * in GradeController and AttendanceController.
 *
 * Denials use denyAsNotFound() rather than the default 403 so that the HTTP
 * response is identical to the scoped findOrFail() it replaces: a teacher
 * probing another teacher's class id cannot tell "not yours" from "no such
 * class". Changing this to 403 would leak class existence.
 *
 * Registered against SchoolClass in AppServiceProvider — the naming convention
 * would look for SchoolClassPolicy, so this binding must stay explicit.
 */
class ClassPolicy
{
    /** Read a class: roster, grade list, attendance history, exports. */
    public function view(User $user, SchoolClass $class): Response
    {
        return $this->ownsClass($user, $class);
    }

    /** Write to a class: enter/update/delete grades, mark attendance. */
    public function update(User $user, SchoolClass $class): Response
    {
        return $this->ownsClass($user, $class);
    }

    private function ownsClass(User $user, SchoolClass $class): Response
    {
        return $class->teacher_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
