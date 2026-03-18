<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\StudentGrade;

class DashboardController extends Controller
{
    public function index() {
        $student = auth()->user();

        // student's enrolled classes
        $classes = $student->enrolledClasses()
        ->with(['grade', 'stream', 'subject', 'teacher'])
        ->get();

        $totalClasses = $classes->count();

        // Get attendance statistics
        $totalAttendance = Attendance::where('student_id', $student->id)->count();
        $presentCount = Attendance::where('student_id', $student->id)->where('status', 'present')->count();
        $absentCount = Attendance::where('student_id', $student->id)->where('status', 'absent')->count();
        $lateCount = Attendance::where('student_id', $student->id)->where('status', 'late')->count();

        $attendancePercentage = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;

        return view('dashboard', compact('student', 'classes', 'totalClasses', 'attendancePercentage', 'presentCount', 'absentCount', 'lateCount'));
    }

    public function attendance() {
        $student = auth()->user();

        // Get all attendance records for student
        $attendanceRecords = Attendance::where('student_id', $student->id)
            ->with(['class.subject', 'class.teacher'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('student.attendance', compact('student', 'attendanceRecords'));
    }

    public function grades() {
        $student = auth()->user();

        // Get all grades for the student grouped by class
        $grades = StudentGrade::where('student_id', $student->id)
        ->with(['class.subject', 'class.teacher'])
        ->orderBy('assessment_date', 'desc')
        ->get()
        ->groupBy('class_id');

        return view('student.grades', compact('student', 'grades'));
    }
}
