<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\StudentGrade;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCardController extends Controller
{
    // ── Student: view own report card ─────────────────────────────

    public function studentReportCard(Request $request)
    {
        $student  = $this->loadStudent(auth()->id());
        $terms    = Term::orderBy('start_date', 'desc')->get();
        $termId   = $request->term_id ?? Term::activeTerm()?->id;
        $term     = $termId ? $terms->firstWhere('id', $termId) : null;
        $data     = $this->buildReportData($student, $termId);

        return view('reports.report-card', array_merge($data, compact('terms', 'termId', 'term')));
    }

    public function studentDownloadPdf(Request $request)
    {
        $termId = $request->term_id ?? Term::activeTerm()?->id;
        return $this->downloadPdf(auth()->id(), $termId);
    }

    // ── Admin: list students ───────────────────────────────────────

    public function index()
    {
        $students = User::where('usertype', 'student')
            ->with('stream.grade')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.reports-index', compact('students'));
    }

    // ── Admin: view or download a specific student's report card ──

    public function generate(Request $request, $studentId)
    {
        $student  = $this->loadStudent($studentId);
        $terms    = Term::orderBy('start_date', 'desc')->get();
        $termId   = $request->term_id ?? Term::activeTerm()?->id;
        $term     = $termId ? $terms->firstWhere('id', $termId) : null;
        $data     = $this->buildReportData($student, $termId);

        return view('reports.report-card', array_merge($data, compact('terms', 'termId', 'term')));
    }

    public function downloadPdf($studentId, ?int $termId = null)
    {
        if ($termId === null) {
            $termId = request('term_id') ? (int) request('term_id') : Term::activeTerm()?->id;
        }

        $student = $this->loadStudent($studentId);
        $terms   = Term::orderBy('start_date', 'desc')->get();
        $term    = $termId ? $terms->firstWhere('id', $termId) : null;
        $data    = $this->buildReportData($student, $termId);

        $pdf = Pdf::loadView('reports.report-card-pdf', array_merge($data, compact('term')))
            ->setPaper('a4', 'portrait');

        $suffix   = $term ? '-' . str_replace(' ', '-', strtolower($term->name)) : '';
        $filename = 'report-card-' . str_replace(' ', '-', strtolower($student->name)) . $suffix . '.pdf';

        return $pdf->download($filename);
    }

    // ── Admin: export attendance as CSV ───────────────────────────

    public function exportAttendanceCsv($studentId): StreamedResponse
    {
        $student = User::where('usertype', 'student')->findOrFail($studentId);

        $records = Attendance::where('student_id', $studentId)
            ->with(['class.subject', 'class.teacher'])
            ->orderBy('date', 'desc')
            ->get();

        $filename = 'attendance-' . str_replace(' ', '-', strtolower($student->name)) . '.csv';

        $response = new StreamedResponse(function () use ($records, $student) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student', $student->name]);
            fputcsv($handle, ['Admission No.', $student->admission_number ?? '']);
            fputcsv($handle, []);
            fputcsv($handle, ['Date', 'Subject', 'Teacher', 'Status', 'Notes']);

            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->date->format('Y-m-d'),
                    $record->class->subject->name,
                    $record->class->teacher->name,
                    ucfirst($record->status),
                    $record->notes ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    // ── Shared helpers ─────────────────────────────────────────────

    private function loadStudent(int $studentId): User
    {
        return User::with(['stream.grade', 'enrolledClasses.subject', 'enrolledClasses.teacher'])
            ->where('usertype', 'student')
            ->findOrFail($studentId);
    }

    private function buildReportData(User $student, ?int $termId = null): array
    {
        $gradesQuery = StudentGrade::where('student_id', $student->id)
            ->with(['class.subject']);

        if ($termId) {
            $gradesQuery->where('term_id', $termId);
        }

        $grades = $gradesQuery->get()->groupBy('class_id');

        $subjectAverages = [];
        $overallTotal    = 0;
        $subjectCount    = 0;

        foreach ($grades as $classId => $classGrades) {
            $average = round($classGrades->avg('percentage'), 2);
            $subjectAverages[$classId] = [
                'subject'     => $classGrades->first()->class->subject->name,
                'average'     => $average,
                'assessments' => $classGrades,
            ];
            $overallTotal += $average;
            $subjectCount++;
        }

        $overallAverage = $subjectCount > 0 ? round($overallTotal / $subjectCount, 2) : 0;

        $attendanceQuery = Attendance::where('student_id', $student->id);
        if ($termId) {
            $attendanceQuery->where('term_id', $termId);
        }

        $totalAttendance     = (clone $attendanceQuery)->count();
        $presentCount        = (clone $attendanceQuery)->where('status', 'present')->count();
        $absentCount         = (clone $attendanceQuery)->where('status', 'absent')->count();
        $lateCount           = (clone $attendanceQuery)->where('status', 'late')->count();
        $attendancePercentage = $totalAttendance > 0
            ? round(($presentCount / $totalAttendance) * 100, 1)
            : 0;

        [$streamPosition, $streamSize] = $this->computeStreamRank($student, $termId);

        return compact(
            'student',
            'subjectAverages',
            'overallAverage',
            'totalAttendance',
            'presentCount',
            'absentCount',
            'lateCount',
            'attendancePercentage',
            'streamPosition',
            'streamSize'
        );
    }

    /**
     * Compute this student's rank within their stream for the given term.
     * Returns [position, total_students]. Position is 1-based.
     * Returns [null, null] if the student has no stream assigned.
     */
    private function computeStreamRank(User $student, ?int $termId = null): array
    {
        if (!$student->stream_id) {
            return [null, null];
        }

        // All students in the same stream
        $streamStudentIds = User::where('usertype', 'student')
            ->where('stream_id', $student->stream_id)
            ->pluck('id');

        $total = $streamStudentIds->count();

        if ($total <= 1) {
            return [1, $total];
        }

        // All grades for all stream students in one query
        $allGradesQuery = StudentGrade::whereIn('student_id', $streamStudentIds);
        if ($termId) {
            $allGradesQuery->where('term_id', $termId);
        }

        $allGrades = $allGradesQuery->get()->groupBy('student_id');

        // Compute overall average per student (average of subject averages — same formula as report card)
        $averages = [];
        foreach ($streamStudentIds as $sid) {
            $studentGrades = $allGrades->get($sid, collect());
            if ($studentGrades->isEmpty()) {
                $averages[$sid] = 0.0;
                continue;
            }
            $byClass       = $studentGrades->groupBy('class_id');
            $subjectAvgs   = $byClass->map(fn($g) => $g->avg('percentage'));
            $averages[$sid] = round($subjectAvgs->avg(), 2);
        }

        // Sort descending; ties share the same position (dense rank)
        arsort($averages);
        $position = 1;
        $prev     = null;
        $rank     = 1;
        foreach ($averages as $sid => $avg) {
            if ($prev !== null && $avg < $prev) {
                $rank = $position;
            }
            if ($sid === $student->id) {
                return [$rank, $total];
            }
            $prev = $avg;
            $position++;
        }

        return [null, $total];
    }
}
