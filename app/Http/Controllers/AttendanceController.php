<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use App\Notifications\StudentAbsentNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // Schow attendance marking for specific class
    public function mark($classId)
    {
        $class = SchoolClass::with(['grade', 'stream', 'subject', 'students'])
            ->findOrFail($classId);

        $this->authorize('view', $class);

        $today = Carbon::today();

        // Check if attendance already marked for today
        $existingAttendance = Attendance::where('class_id', $classId)
            ->where('date', $today)
            ->pluck('student_id')
            ->toArray();

        return view('teacher.attendance-mark', compact('class', 'today', 'existingAttendance'));
    }

    // Store attendance records
    public function store(Request $request, $classId)
    {
        $teacher = auth()->user();

        $class = SchoolClass::findOrFail($classId);

        $this->authorize('update', $class);

        // Owning the class is not enough — every submitted student must also be
        // enrolled in it, or a teacher can mark attendance for another class's student.
        $enrolledIds = $class->students()->pluck('users.id')->all();

        $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array', function ($attribute, $value, $fail) use ($enrolledIds) {
                if (! is_array($value)) {
                    return;
                }

                $notEnrolled = array_diff(array_keys($value), $enrolledIds);

                if (! empty($notEnrolled)) {
                    $fail('These students are not enrolled in this class: '.implode(', ', $notEnrolled).'.');
                }
            }],
            'attendance.*' => ['in:present,absent,late,excused'],
        ]);

        $date = Carbon::parse($request->date);

        // Delete existing attendance for this date (if re-marking)
        Attendance::where('class_id', $classId)
            ->where('date', $date)
            ->delete();

        $activeTerm = Term::activeTerm();

        // Pre-load students with their parent for notification
        $studentMap = User::whereIn('id', array_keys($request->attendance))
            ->with('parent')
            ->get()
            ->keyBy('id');

        // Create new attendance records
        foreach ($request->attendance as $studentId => $status) {
            Attendance::create([
                'class_id' => $classId,
                'student_id' => $studentId,
                'term_id' => $activeTerm?->id,
                'date' => $date,
                'status' => $status,
                'notes' => $request->notes[$studentId] ?? null,
                'marked_by' => $teacher->id,
            ]);

            // Notify parent if child is absent or late
            if (in_array($status, ['absent', 'late'])) {
                $student = $studentMap[$studentId] ?? null;
                if ($student && $student->parent && $student->parent->email) {
                    $student->parent->notify(
                        new StudentAbsentNotification($student, $class, $status, $date)
                    );
                }
            }
        }

        return redirect()->route('teacher.attendance.history', $classId)
            ->with('success', 'Attendance marked successfully for '.$date->format('M d, Y'));
    }

    // View attendance history for a class
    public function history($classId)
    {
        $class = SchoolClass::with(['grade', 'stream', 'subject'])
            ->findOrFail($classId);

        $this->authorize('view', $class);

        // Get all attendance grouped by date
        $attendanceRecords = Attendance::where('class_id', $classId)
            ->with('student')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('date');

        return view('teacher.attendance-history', compact('class', 'attendanceRecords'));
    }
}
