<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\FindsUsersByType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Stream;
use App\Models\ActivityLog;
use App\Support\AdmissionNumber;

class StudentController extends Controller
{
    use FindsUsersByType;

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

        // Auto-generate admission number if not provided. AdmissionNumber
        // claims the next value from a locked per-year counter, so two
        // simultaneous admissions cannot be handed the same number.
        $admissionNumber = $request->admission_number ?: AdmissionNumber::next();

        $student = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' =>'student',
            'stream_id' => $request->stream_id,
            'admission_number' => $admissionNumber,
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        ActivityLog::record('created', 'Student', $student->id, "Created student: {$student->name} ({$admissionNumber})");

        return redirect()->route('admin.students')->with('success', 'Student created successfully.');
    }

    public function edit($id) {
        $student = $this->findUserOfType($id, 'student');

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $streams = Stream::with('grade')->orderBy('grade_id')->get();
        $parents = User::where('usertype', 'parent')->orderBy('name')->get();

        return view('admin.students-edit', compact('student', 'streams', 'parents'));
    }

    public function update(Request $request, $id) {
        $student = $this->findUserOfType($id, 'student');

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'stream_id'        => ['nullable', 'exists:streams,id'],
            'admission_number' => ['nullable', 'string', 'max:50', 'unique:users,admission_number,' . $id],
            'parent_id'        => ['nullable', 'exists:users,id'],
            'password'         => [
                'nullable',
                'confirmed',
                Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        // Ensure the linked parent is actually a parent account
        if ($request->filled('parent_id')) {
            $parentUser = User::find($request->parent_id);
            if (!$parentUser || $parentUser->usertype !== 'parent') {
                return back()->withErrors(['parent_id' => 'Invalid parent selected.'])->withInput();
            }
        }

        $student->name             = $request->name;
        $student->email            = $request->email;
        $student->stream_id        = $request->stream_id;
        $student->admission_number = $request->admission_number;
        $student->parent_id        = $request->parent_id ?: null;

        if ($request->filled('password')) {
            $student->password = bcrypt($request->password);
        }

        $student->save();

        ActivityLog::record('updated', 'Student', $student->id, "Updated student: {$student->name}");

        return redirect()->route('admin.students')->with('success', 'Student updated successfully!');
    }

    public function destroy($id) {
        $student = $this->findUserOfType($id, 'student');

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        ActivityLog::record('deleted', 'Student', $student->id, "Deleted student: {$student->name} ({$student->admission_number})");

        $student->delete();

        return redirect()->route('admin.students')->with('success', 'Student deleted successfully!');
    }
}
