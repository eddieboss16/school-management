<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Grade;
use App\Models\Stream;

class DashboardController extends Controller
{
    // Student's
    public function student() {
        $user = auth()->user();

        return view('dashboard', [
            'userName' => $user->name,
            'userEmail' => $user->email,
        ]);
    }

    public function students() {
        $students = User::where('usertype', 'student')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('admin.students', [
            'students' => $students
        ]);
    }

    public function createStudent() {
        return view('admin.students-create');
    }

    public function storeStudent(Request $request) {
        $request->validate([
            'name' => ['required', 'string', 'Max:255'],
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
            'usertype' =>'student',
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.students')->with('success', 'Student created successfully.');
    }

    public function editStudent($id) {
        $student = User::findOrFail($id);

        // Security check - make sure it's actually a student
        if ($student->usertype !== 'student') {
            return redirect()->route('admin.students')->with('error', 'Invalid student ID');
        }

        return view('admin.students-edit', compact('student'));
    }

    public function updateStudent(Request $request, $id) {
        $student = User::findOrFail($id);

        // Security check
        if ($student->usertype !== 'student') {
            return redirect()->route('admin.students')->with('error', 'Invalid student ID');
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

        $student->name = $request->name;
        $student->email = $request->email;

        // Only update password if provided
        if ($request->filled('password')) {
            $student->password = bcrypt($request->password);
        }

        $student->save();

        return redirect()->route('admin.students')->with('success', 'student updated successfully!');
    }

    public function destroyStudent($id) {
        $student = User::findOrFail($id);

        // Security check
        if ($student->usertype !== 'student') {
            return redirect()->route('admin.students')->with('error', 'Invalid student ID');
        }

        $student->delete();

        return redirect()->route('admin.students')->with('success', 'Student deleted successfully!');
    }


    // Teacher
    public function teachers() {
        $teachers = User::where('usertype', 'teacher')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('admin.teachers', [
            'teachers' => $teachers
        ]);
    }

    public function createTeacher() {
        return view('admin.teachers-create');
    }

    public function storeTeacher(Request $request) {
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

    public function editTeacher($id) {
        $teacher = User::findOrFail($id);

        // Security check - make sure it's actually a student
        if ($teacher->usertype !== 'teacher') {
            return redirect()->route('admin.teachers')->with('error', 'Invalid student ID');
        }

        return view('admin.teachers-edit', compact('teacher'));
    }

    public function updateTeacher(Request $request, $id) {
        $teacher = User::findOrFail($id);

        // Security check
        if ($teacher->usertype !== 'teacher') {
            return redirect()->route('admin.teachers')->with('error', 'Invalid student ID');
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

    public function destroyTeacher($id) {
        $teacher = User::findOrFail($id);

        // Security check
        if ($teacher->usertype !== 'teacher') {
            return redirect()->route('admin.teachers')->with('error', 'Invalid teacher ID');
        }

        $teacher->delete();

        return redirect()->route('admin.teachers')->with('success', 'Teacher deleted successfully!');
    }

    // Admin
    public function admin() {
        $totalStudents = User::where('usertype', 'student')->count();
        $totalTeachers = User::where('usertype', 'teacher')->count();
        $totalUsers = User::count();
        $totalStreams = Stream::count();

        return view('admin.dashboard', [
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalUsers' => $totalUsers,
            'totalStreams' => $totalStreams,
        ]);
    }

    // streams
    public function streams() {
        $streams = Stream::with('grade')
        ->orderBy('grade_id')
        ->paginate(15);

        return view('admin.streams', compact('streams'));
    }

    public function createStream() {
        $grades = Grade::orderBy('order')->get();
        return view('admin.streams-create', compact('grades'));
    }

    public function storeStream(Request $request) {
        $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        Stream::create([
            'grade_id' => $request->grade_id,
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('admin.streams')->with('success', 'Stream created successfully!');
    }

    public function editStream($id) {
        $stream = Stream::findOrFail($id);
        $grades = Grade::orderBy('order')->get();

        return view('admin.streams-edit', compact('stream', 'grades'));
    }

    public function updateStream(Request $request, $id) {
        $stream = Stream::findOrFail($id);

        $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $stream->update([
            'grade_id' => $request->grade_id,
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('admin.streams')->with('success', 'Stream updated successfully!');
    }

    public function destroyStream($id) {
        $stream = Stream::findOrFail($id);
        $stream->delete();

        return redirect()->route('admin.streams')->with('success', 'Stream deleted successfully!');
    }
}
