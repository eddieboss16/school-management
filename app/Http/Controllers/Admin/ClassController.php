<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\User;

class ClassController extends Controller
{
    public function index() {
        $classes = SchoolClass::with(['grade', 'stream', 'subject', 'teacher'])
        ->orderBy('grade_id')
        ->paginate(15);

        return view('admin.classes', compact('classes'));
    }

    public function create() {
        $grades = Grade::orderBy('order')->get();
        $streams = Stream::with('grade')->orderBy('grade_id')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::where('usertype', 'teacher')->orderBy('name')->get();
        
        return view('admin.classes-create', compact('grades', 'streams', 'subjects', 'teachers'));
    }

    public function store(Request $request) {
        $request->validate([
        'grade_id' => ['required', 'exists:grades,id'],
        'stream_id' => ['required', 'exists:streams,id'],
        'subject_id' => ['required', 'exists:subjects,id'],
        'teacher_id' => ['required', 'exists:users,id'],
        'capacity' => ['required', 'integer', 'min:1', 'max:100'],
    ]);

    // Generate class code (e.g., "G7-NORTH-MATH")
        $grade = Grade::find($request->grade_id);
        $stream = Stream::find($request->stream_id);
        $subject = Subject::find($request->subject_id);
        
        $classCode = strtoupper(
            'G' . $grade->order . '-' . 
            $stream->name . '-' . 
            $subject->code
        );

        SchoolClass::create([
            'grade_id' => $request->grade_id,
            'stream_id' => $request->stream_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'class_code' => $classCode,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('admin.classes')->with('success', 'Class created successfully!');
    }

    public function edit($id) {
        $class = SchoolClass::findOrFail($id);
        $grades = Grade::orderBy('order')->get();
        $streams = Stream::with('grade')->orderBy('grade_id')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::where('usertype', 'teacher')->orderBy('name')->get();
        
        return view('admin.classes-edit', compact('class', 'grades', 'streams', 'subjects', 'teachers'));
    }

    public function update(Request $request, $id) {
        $class = SchoolClass::findOrFail($id);
        
        $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'stream_id' => ['required', 'exists:streams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:users,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        // Regenerate class code
        $grade = Grade::find($request->grade_id);
        $stream = Stream::find($request->stream_id);
        $subject = Subject::find($request->subject_id);
        
        $classCode = strtoupper(
            'G' . $grade->order . '-' . 
            $stream->name . '-' . 
            $subject->code
        );

        $class->update([
            'grade_id' => $request->grade_id,
            'stream_id' => $request->stream_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'class_code' => $classCode,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('admin.classes')->with('success', 'Class updated successfully!');
    }

    public function destroy($id) {
        $class = SchoolClass::findOrFail($id);
        $class->delete();
        
        return redirect()->route('admin.classes')->with('success', 'Class deleted successfully!');
    }
}
