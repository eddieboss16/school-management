<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\StudentGrade;
use App\Models\Term;
use App\Models\User;
use App\Support\StreamRank;
use Illuminate\Http\Request;
use Illuminate\Http\Request as HttpRequest;

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

    public function reportCard(HttpRequest $request, $id)
    {
        $child = $this->authorizeChild($id);
        $terms = Term::orderBy('start_date', 'desc')->get();
        $termId = $request->term_id ?? Term::activeTerm()?->id;
        $term = $termId ? $terms->firstWhere('id', $termId) : null;

        $gradesQuery = StudentGrade::where('student_id', $child->id)->with(['class.subject']);
        if ($termId) {
            $gradesQuery->where('term_id', $termId);
        }
        $grades = $gradesQuery->get()->groupBy('class_id');

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

        $attQ = Attendance::where('student_id', $child->id);
        if ($termId) {
            $attQ->where('term_id', $termId);
        }

        $totalAttendance = (clone $attQ)->count();
        $presentCount = (clone $attQ)->where('status', 'present')->count();
        $absentCount = (clone $attQ)->where('status', 'absent')->count();
        $lateCount = (clone $attQ)->where('status', 'late')->count();
        $attendancePercentage = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0;

        [$streamPosition, $streamSize] = StreamRank::forStudent($child, $termId);

        return view('parent.child-report-card', compact(
            'child', 'terms', 'termId', 'term',
            'subjectAverages', 'overallAverage',
            'totalAttendance', 'presentCount', 'absentCount', 'lateCount', 'attendancePercentage',
            'streamPosition', 'streamSize'
        ));
    }

    public function fees(Request $request, $id)
    {
        $child = $this->authorizeChild($id);
        $terms = Term::orderBy('start_date', 'desc')->get();
        $activeTerm = Term::activeTerm();
        $selectedTermId = $request->term_id ?? $activeTerm?->id;

        $fees = $selectedTermId ? FeeStructure::forStudent($child, $selectedTermId) : collect();
        $payments = FeePayment::where('student_id', $child->id)
            ->when($selectedTermId, fn ($q) => $q->where('term_id', $selectedTermId))
            ->with('term')
            ->orderBy('payment_date', 'desc')
            ->get();

        $expected = $fees->sum('amount');
        $paid = $payments->sum('amount');
        $balance = $expected - $paid;

        return view('parent.child-fees', compact(
            'child', 'terms', 'selectedTermId', 'fees', 'payments', 'expected', 'paid', 'balance'
        ));
    }

    /**
     * The ownership rule now lives in StudentPolicy. This stays as the single
     * lookup point for the four child pages; the policy denies as 404, so the
     * response is unchanged from the scoped findOrFail() it replaces.
     */
    private function authorizeChild($id)
    {
        $child = User::findOrFail($id);

        $this->authorize('view', $child);

        return $child;
    }
}
