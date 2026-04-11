<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StudentGrade;
use App\Models\Attendance;

class DashboardController extends Controller
{
    public function index()
    {
        $parent = auth()->user();
        $children = $parent->children;

        return view('parent.dashboard', compact('children'));
    }

    public function grades($id)
    {
        $child = $this->authorizeChild($id);

        $grades = StudentGrade::where('student_id', $child->id)
            ->with(['class.subject', 'class.teacher'])
            ->orderBy('assessment_date', 'desc')
            ->get()
            ->groupBy('class_id');

        return view('parent.child-grades', compact('child', 'grades'));
    }

    public function attendance($id)
    {
        $child = $this->authorizeChild($id);

        $attendanceRecords = Attendance::where('student_id', $child->id)
            ->with(['class.subject', 'class.teacher'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('parent.child-attendance', compact('child', 'attendanceRecords'));
    }

    public function reportCard($id)
    {
        $child = $this->authorizeChild($id);

        $grades = StudentGrade::where('student_id', $child->id)
            ->with(['class.subject'])
            ->get()
            ->groupBy('class_id');

        $subjectAverages = [];
        $overallTotal = 0;
        $subjectCount = 0;

        foreach ($grades as $classId => $classGrades) {
            $average = round($classGrades->avg('percentage'), 2);
            $subjectAverages[$classId] = [
                'subject' => $classGrades->first()->class->subject->name,
                'average' => $average,
                'assessments' => $classGrades,
            ];
            $overallTotal += $average;
            $subjectCount++;
        }

        $overallAverage = $subjectCount > 0 ? round($overallTotal / $subjectCount, 2) : 0;

        $totalAttendance = Attendance::where('student_id', $child->id)->count();
        $presentCount = Attendance::where('student_id', $child->id)->where('status', 'present')->count();
        $absentCount = Attendance::where('student_id', $child->id)->where('status', 'absent')->count();
        $lateCount = Attendance::where('student_id', $child->id)->where('status', 'late')->count();
        $attendancePercentage = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;

        return view('parent.child-report-card', compact(
            'child',
            'subjectAverages',
            'overallAverage',
            'totalAttendance',
            'presentCount',
            'absentCount',
            'lateCount',
            'attendancePercentage'
        ));
    }

    private function authorizeChild($id)
    {
        $parent = auth()->user();
        $child = User::where('usertype', 'student')
            ->where('parent_id', $parent->id)
            ->findOrFail($id);

        return $child;
    }
}
