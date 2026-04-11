<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StudentGrade;
use App\Models\Attendance;
use App\Models\SchoolClass;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportCardController extends Controller
{
    // Generate report card for the authenticated student
    public function studentReportCard() {
        $studentId = auth()->id();
        $student = User::with(['stream.grade', 'enrolledClasses.subject', 'enrolledClasses.teacher'])
            ->where('usertype', 'student')
            ->findOrFail($studentId);
        
        // Get all grades grouped by class
        $grades = StudentGrade::where('student_id', $studentId)
            ->with(['class.subject'])
            ->get()
            ->groupBy('class_id');
        
        // Calculate subject averages
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
        
        // Get attendance summary
        $totalAttendance = Attendance::where('student_id', $studentId)->count();
        $presentCount = Attendance::where('student_id', $studentId)->where('status', 'present')->count();
        $absentCount = Attendance::where('student_id', $studentId)->where('status', 'absent')->count();
        $lateCount = Attendance::where('student_id', $studentId)->where('status', 'late')->count();
        $attendancePercentage = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;
        
        return view('reports.report-card', compact(
            'student', 
            'subjectAverages', 
            'overallAverage',
            'totalAttendance',
            'presentCount',
            'absentCount',
            'lateCount',
            'attendancePercentage'
        ));
    }

    // Generate report card for a student
    public function generate($studentId) {
        $student = User::with(['stream.grade', 'enrolledClasses.subject', 'enrolledClasses.teacher'])
            ->where('usertype', 'student')
            ->findOrFail($studentId);
        
        // Get all grades grouped by class
        $grades = StudentGrade::where('student_id', $studentId)
            ->with(['class.subject'])
            ->get()
            ->groupBy('class_id');
        
        // Calculate subject averages
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
        
        // Get attendance summary
        $totalAttendance = Attendance::where('student_id', $studentId)->count();
        $presentCount = Attendance::where('student_id', $studentId)->where('status', 'present')->count();
        $absentCount = Attendance::where('student_id', $studentId)->where('status', 'absent')->count();
        $lateCount = Attendance::where('student_id', $studentId)->where('status', 'late')->count();
        $attendancePercentage = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;
        
        return view('reports.report-card', compact(
            'student', 
            'subjectAverages', 
            'overallAverage',
            'totalAttendance',
            'presentCount',
            'absentCount',
            'lateCount',
            'attendancePercentage'
        ));
    }
    
    // Student downloads their own report card as PDF
    public function studentDownloadPdf() {
        return $this->downloadPdf(auth()->id());
    }

    // Download report card as PDF (admin or student)
    public function downloadPdf($studentId) {
        $student = User::with(['stream.grade', 'enrolledClasses.subject', 'enrolledClasses.teacher'])
            ->where('usertype', 'student')
            ->findOrFail($studentId);

        $grades = StudentGrade::where('student_id', $studentId)
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

        $totalAttendance = Attendance::where('student_id', $studentId)->count();
        $presentCount = Attendance::where('student_id', $studentId)->where('status', 'present')->count();
        $absentCount = Attendance::where('student_id', $studentId)->where('status', 'absent')->count();
        $lateCount = Attendance::where('student_id', $studentId)->where('status', 'late')->count();
        $attendancePercentage = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;

        $pdf = Pdf::loadView('reports.report-card-pdf', compact(
            'student',
            'subjectAverages',
            'overallAverage',
            'totalAttendance',
            'presentCount',
            'absentCount',
            'lateCount',
            'attendancePercentage'
        ))->setPaper('a4', 'portrait');

        $filename = 'report-card-' . str_replace(' ', '-', strtolower($student->name)) . '.pdf';

        return $pdf->download($filename);
    }

    // Admin: List all students to generate reports for
    public function index() {
        $students = User::where('usertype', 'student')
            ->with('stream.grade')
            ->orderBy('name')
            ->paginate(20);
        
        return view('admin.reports-index', compact('students'));
    }
}
