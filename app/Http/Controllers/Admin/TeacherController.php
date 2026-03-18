<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;

class TeacherController extends Controller
{
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

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' => 'teacher',
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.teachers')->with('success', 'Teacher created successfully.');
    }

    public function edit($id) {
        $teacher = User::findOrFail($id);

        // Security check - make sure it's actually a teacher
        if ($teacher->usertype !== 'teacher') {
            return redirect()->route('admin.teachers')->with('error', 'Invalid teacher ID');
        }

        return view('admin.teachers-edit', compact('teacher'));
    }

    public function update(Request $request, $id) {
        $teacher = User::findOrFail($id);

        // Security check
        if ($teacher->usertype !== 'teacher') {
            return redirect()->route('admin.teachers')->with('error', 'Invalid teacher ID');
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

        return redirect()->route('admin.teachers')->with('success', 'teacher updated successfully!');
    }

    public function destroy($id) {
        $teacher = User::findOrFail($id);

        // Security check
        if ($teacher->usertype !== 'teacher') {
            return redirect()->route('admin.teachers')->with('error', 'Invalid teacher ID');
        }

        $teacher->delete();

        return redirect()->route('admin.teachers')->with('success', 'Teacher deleted successfully!');
    }
}
