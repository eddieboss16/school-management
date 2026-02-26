<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Schow attendance marking for specific class
    public function mark($classId) {
        $teacher = auth()->user();

        $class = SchoolClass::with(['grade', 'stream', 'subject', 'students'])
        ->where('teacher_id', $teacher->id)
        ->findOrFail($classId);
        
        $today = Carbon::today();

        // Check if attendance already marked for today
        $existingAttendance = Attendance::where('class_id', $classId)
        ->where('date', $today)
        ->pluck('student_id')
        ->toArray();

        return view('teacher.attendance-mark', compact('class', 'today', 'existingAttendance'));
    }

    // Store attendance records
    public function store(Request $request, $classId) {
        $teacher = auth()->user();

        $class = SchoolClass::where('teacher_id', $teacher->id)->findOrFail($classId);

        $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['in:present,absent,late,excused'],
        ]);

        $date = Carbon::parse($request->date);

        // Delete existing attendance for this date (if re-marking)
        Attendance::where('class_id', $classId)
        ->where('date', $date)
        ->delete();

        // Create new attendance records
        foreach ($request->attendance as $studentId => $status) {
            Attendance::create([
                'class_id' => $classId,
                'student_id' => $studentId,
                'date' => $date,
                'status' => $status,
                'notes' => $request->notes[$studentId] ?? null,
                'marked_by' => $teacher->id,
            ]);
        }

        return redirect()->route('teacher.attendance.history', $classId)
        ->with('success', 'Attendance marked successfully for ' . $date->format('M d, Y'));
    }

    // View attendance history for a class
    public function history($classId) {
        $teacher = auth()->user();

        $class = SchoolClass::with(['grade', 'stream', 'subject'])
        ->where('teacher_id', $teacher->id)
        ->findOrFail($classId);

        // Get attendance grouped by date(last 30 days)
        $attendanceRecords = Attendance::where('class_id', $classId)
        ->where('date', '>=', Carbon::now()->subDays(30))
        ->with('student')
        ->orderBy('date', 'desc')
        ->get()
        ->groupBy('date');

        return view('teacher.attendance-history', compact('class', 'attendanceRecords'));
    }
}
