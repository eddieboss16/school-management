<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Stream;

class StudentController extends Controller
{
    public function index() {
        $students = User::where('usertype', 'student')
        ->with('stream.grade')
        ->orderBy('created_at', 'desc')
        ->paginate(10);
 
        return view('admin.students', [
            'students' => $students
        ]);
    }

    public function create() {
        $streams = Stream::with('grade')->orderBy('grade_id')->get();
        return view('admin.students-create', compact('streams'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => ['required', 'string', 'Max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'admission_number' => ['nullable', 'string', 'unique:users', 'max:50'],
            'stream_id' => ['nullable', 'exists:streams,id'],
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

        // Auto-generate admission number if not provided
        $admissionNumber = $request->admission_number;
        if (empty($admissionNumber)) {
            $year = date('Y');
            $lastStudent = User::where('usertype', 'student')
                ->where('admission_number', 'like', "STD{$year}%")
                ->orderBy('admission_number', 'desc')
                ->first();

            if ($lastStudent) {
                $lastNumber = intval(substr($lastStudent->admission_number, -3));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $admissionNumber = "STD{$year}" . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' =>'student',
            'stream_id' => $request->stream_id,
            'admission_number' => $admissionNumber,
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.students')->with('success', 'Student created successfully.');
    }

    public function edit($id) {
        $student = User::findOrFail($id);

        // Security check - make sure it's actually a student
        if ($student->usertype !== 'student') {
            return redirect()->route('admin.students')->with('error', 'Invalid student ID');
        }
        $streams = Stream::with('grade')->orderBy('grade_id')->get();

        return view('admin.students-edit', compact('student', 'streams'));
    }

    public function update(Request $request, $id) {
        $student = User::findOrFail($id);

        // Security check
        if ($student->usertype !== 'student') {
            return redirect()->route('admin.students')->with('error', 'Invalid student ID');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'stream_id' => ['nullable', 'exists:streams,id'],
            'admission_number' => ['nullable', 'string', 'max:50', 'unique:users,admission_number,' . $id],
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
        $student->stream_id = $request->stream_id;
        $student->admission_number = $request->admission_number;

        // Only update password if provided
        if ($request->filled('password')) {
            $student->password = bcrypt($request->password);
        }

        $student->save();

        return redirect()->route('admin.students')->with('success', 'student updated successfully!');
    }

    public function destroy($id) {
        $student = User::findOrFail($id);

        // Security check
        if ($student->usertype !== 'student') {
            return redirect()->route('admin.students')->with('error', 'Invalid student ID');
        }

        $student->delete();

        return redirect()->route('admin.students')->with('success', 'Student deleted successfully!');
    }
}
