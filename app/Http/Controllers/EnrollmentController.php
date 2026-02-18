<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\User;

class EnrollmentController extends Controller
{
    // Show enrollment page for a specific class
    public function show($classId)
    {
        $class = SchoolClass::with(['grade', 'stream', 'subject', 'teacher', 'students'])
            ->findOrFail($classId);
        
        // Get available students (same stream, not already enrolled)
        $availableStudents = User::where('usertype', 'student')
            ->where('stream_id', $class->stream_id)
            ->whereDoesntHave('enrolledClasses', function($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->orderBy('name')
            ->get();
        
        return view('admin.enrollments', compact('class', 'availableStudents'));
    }
    
    // Enroll a student in a class
    public function store(Request $request, $classId)
    {
        $class = SchoolClass::findOrFail($classId);
        
        $request->validate([
            'student_id' => ['required', 'exists:users,id'],
        ]);
        
        // Check capacity
        if ($class->students()->count() >= $class->capacity) {
            return back()->with('error', 'Class is at full capacity!');
        }
        
        // Check if student is in the correct stream
        $student = User::findOrFail($request->student_id);
        if ($student->stream_id != $class->stream_id) {
            return back()->with('error', 'Student is not in the correct stream for this class!');
        }
        
        // Check if already enrolled
        if ($class->students()->where('student_id', $request->student_id)->exists()) {
            return back()->with('error', 'Student is already enrolled in this class!');
        }
        
        // Enroll the student
        $class->students()->attach($request->student_id);
        
        return back()->with('success', 'Student enrolled successfully!');
    }
    
    // Remove a student from a class
    public function destroy($classId, $studentId)
    {
        $class = SchoolClass::findOrFail($classId);
        
        // Remove the enrollment
        $class->students()->detach($studentId);
        
        return back()->with('success', 'Student removed from class successfully!');
    }
    
    // Bulk enroll all students from the stream
    public function bulkEnroll($classId)
    {
        $class = SchoolClass::with('students')->findOrFail($classId);
        
        // Get all students from the same stream who aren't enrolled yet
        $students = User::where('usertype', 'student')
            ->where('stream_id', $class->stream_id)
            ->whereDoesntHave('enrolledClasses', function($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->pluck('id');
        
        // Check capacity
        $currentEnrolled = $class->students()->count();
        $availableSlots = $class->capacity - $currentEnrolled;
        
        if ($students->count() > $availableSlots) {
            return back()->with('error', "Not enough space! Only {$availableSlots} slots available.");
        }
        
        // Enroll all students
        $class->students()->attach($students);
        
        return back()->with('success', "{$students->count()} students enrolled successfully!");
    }
}
