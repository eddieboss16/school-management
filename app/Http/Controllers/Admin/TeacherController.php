<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\FindsUsersByType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\ActivityLog;

class TeacherController extends Controller
{
    use FindsUsersByType;

    public function index() {
        $teachers = User::where('usertype', 'teacher')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('admin.teachers', [
            'teachers' => $teachers
        ]);
    }

    public function create() {
        return view('admin.teachers-create');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        $teacher = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => 'teacher',
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        ActivityLog::record('created', 'Teacher', $teacher->id, "Created teacher: {$teacher->name}");

        return redirect()->route('admin.teachers')->with('success', 'Teacher created successfully.');
    }

    public function edit($id) {
        $teacher = $this->findUserOfType($id, 'teacher');

        if ($teacher instanceof RedirectResponse) {
            return $teacher;
        }

        return view('admin.teachers-edit', compact('teacher'));
    }

    public function update(Request $request, $id) {
        $teacher = $this->findUserOfType($id, 'teacher');

        if ($teacher instanceof RedirectResponse) {
            return $teacher;
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => [
                'nullable',
                'confirmed',
                Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        $teacher->name = $request->name;
        $teacher->email = $request->email;

        // Only update password if provided
        if ($request->filled('password')) {
            $teacher->password = bcrypt($request->password);
        }

        $teacher->save();

        ActivityLog::record('updated', 'Teacher', $teacher->id, "Updated teacher: {$teacher->name}");

        return redirect()->route('admin.teachers')->with('success', 'Teacher updated successfully!');
    }

    public function destroy($id) {
        $teacher = $this->findUserOfType($id, 'teacher');

        if ($teacher instanceof RedirectResponse) {
            return $teacher;
        }

        ActivityLog::record('deleted', 'Teacher', $teacher->id, "Deleted teacher: {$teacher->name}");

        $teacher->delete();

        return redirect()->route('admin.teachers')->with('success', 'Teacher deleted successfully!');
    }
}
