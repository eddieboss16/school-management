<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\StudentGrade;
use App\Models\Term;
use App\Models\User;
use App\Notifications\GradesPostedNotification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeController extends Controller
{
    // Show grade entry form for specific class
    public function enter($classId) {
        $teacher = auth()->user();

        $class = SchoolClass::with(['grade', 'stream', 'subject', 'students'])
            ->where('teacher_id', $teacher->id)
            ->findOrFail($classId);

        return view('teacher.grades-enter', compact('class'));
    }
    
    // Store grade records
    public function store(Request $request, $classId)
    {
        $teacher = auth()->user();
        
        $class = SchoolClass::where('teacher_id', $teacher->id)->findOrFail($classId);
        
        // Owning the class is not enough — every submitted student must also be
        // enrolled in it, or a teacher can write grades onto another class's student.
        $enrolledIds = $class->students()->pluck('users.id')->all();

        $request->validate([
            'assessment_type' => ['required', 'string', 'max:255'],
            'assessment_date' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'grades' => ['required', 'array', function ($attribute, $value, $fail) use ($enrolledIds) {
                if (! is_array($value)) {
                    return;
                }

                $notEnrolled = array_diff(array_keys($value), $enrolledIds);

                if (! empty($notEnrolled)) {
                    $fail('These students are not enrolled in this class: ' . implode(', ', $notEnrolled) . '.');
                }
            }],
            'grades.*.score' => ['required', 'numeric', 'min:0'],
        ]);
        
        $activeTerm = Term::activeTerm();

        // Pre-load class with relations needed for notification
        $class->loadMissing(['grade', 'stream', 'subject']);

        // Pre-load students with their parent for notification
        $studentIds = array_keys(array_filter($request->grades, fn($g) => isset($g['score'])));
        $studentMap = User::whereIn('id', $studentIds)
            ->with('parent')
            ->get()
            ->keyBy('id');

        // Create grade records for each student
        foreach ($request->grades as $studentId => $gradeData) {
            if (isset($gradeData['score'])) {
                $score      = (float) $gradeData['score'];
                $maxScore   = (float) $request->max_score;
                $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

                StudentGrade::create([
                    'class_id'        => $classId,
                    'student_id'      => $studentId,
                    'term_id'         => $activeTerm?->id,
                    'assessment_type' => $request->assessment_type,
                    'score'           => $score,
                    'max_score'       => $maxScore,
                    'assessment_date' => $request->assessment_date,
                    'remarks'         => $gradeData['remarks'] ?? null,
                    'entered_by'      => $teacher->id,
                ]);

                // Notify parent that grades have been posted
                $student = $studentMap[$studentId] ?? null;
                if ($student && $student->parent && $student->parent->email) {
                    $student->parent->notify(
                        new GradesPostedNotification($student, $class, $request->assessment_type, $score, $maxScore, $percentage)
                    );
                }
            }
        }

        return redirect()->route('teacher.grades.view', $classId)
            ->with('success', 'Grades entered successfully for ' . $request->assessment_type);
    }
    
    // View all grades for a class
    public function view($classId)
    {
        $teacher = auth()->user();
        
        $class = SchoolClass::with(['grade', 'stream', 'subject', 'students'])
            ->where('teacher_id', $teacher->id)
            ->findOrFail($classId);
        
        // Get all grades for this class grouped by assessment type
        $grades = StudentGrade::where('class_id', $classId)
            ->with('student')
            ->orderBy('assessment_date', 'desc')
            ->get()
            ->groupBy('assessment_type');
        
        return view('teacher.grades-view', compact('class', 'grades'));
    }
    // Edit a specific grade assessment
    public function edit($classId, $assessmentType)
    {
        $teacher = auth()->user();
        
        $class = SchoolClass::with(['grade', 'stream', 'subject', 'students'])
            ->where('teacher_id', $teacher->id)
            ->findOrFail($classId);
        
        // Get all grades for this assessment
        $grades = StudentGrade::where('class_id', $classId)
            ->where('assessment_type', $assessmentType)
            ->with('student')
            ->get()
            ->keyBy('student_id');
        
        if ($grades->isEmpty()) {
            return redirect()->route('teacher.grades.view', $classId)
                ->with('error', 'Assessment not found.');
        }
        
        $assessmentInfo = $grades->first();
        
        return view('teacher.grades-edit', compact('class', 'grades', 'assessmentInfo', 'assessmentType'));
    }

    // Update grades for an assessment
    public function update(Request $request, $classId, $assessmentType)
    {
        $teacher = auth()->user();
        
        $class = SchoolClass::where('teacher_id', $teacher->id)->findOrFail($classId);
        
        $request->validate([
            'assessment_date' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'grades' => ['required', 'array'],
            'grades.*.score' => ['required', 'numeric', 'min:0'],
        ]);
        
        // Update each grade record - verify it belongs to this class
        foreach ($request->grades as $gradeId => $gradeData) {
            $grade = StudentGrade::where('class_id', $classId)->findOrFail($gradeId);

            $grade->update([
                'score' => $gradeData['score'],
                'max_score' => $request->max_score,
                'assessment_date' => $request->assessment_date,
                'remarks' => $gradeData['remarks'] ?? null,
            ]);
        }
        
        return redirect()->route('teacher.grades.view', $classId)
            ->with('success', 'Grades updated successfully for ' . $assessmentType);
    }

    // Export grades for a class as CSV
    public function exportCsv($classId): StreamedResponse
    {
        $teacher = auth()->user();
        $class = SchoolClass::with(['grade', 'stream', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->findOrFail($classId);

        $grades = StudentGrade::where('class_id', $classId)
            ->with('student')
            ->orderBy('assessment_date')
            ->get();

        $filename = 'grades-' . strtolower(str_replace(' ', '-', $class->subject->name))
            . '-' . $class->grade->name . $class->stream->name . '.csv';

        $response = new StreamedResponse(function () use ($grades, $class) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Class', $class->grade->name . ' ' . $class->stream->name . ' — ' . $class->subject->name
            ]);
            fputcsv($handle, []);
            fputcsv($handle, ['Student Name', 'Admission No.', 'Assessment', 'Date', 'Score', 'Max Score', 'Percentage', 'Remarks']);

            foreach ($grades as $grade) {
                fputcsv($handle, [
                    $grade->student->name,
                    $grade->student->admission_number ?? '',
                    $grade->assessment_type,
                    $grade->assessment_date->format('Y-m-d'),
                    $grade->score,
                    $grade->max_score,
                    $grade->percentage . '%',
                    $grade->remarks ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    // Delete an entire assessment
    public function destroy($classId, $assessmentType)
    {
        $teacher = auth()->user();
        
        $class = SchoolClass::where('teacher_id', $teacher->id)->findOrFail($classId);
        
        // Delete all grades for this assessment
        StudentGrade::where('class_id', $classId)
            ->where('assessment_type', $assessmentType)
            ->delete();
        
        return redirect()->route('teacher.grades.view', $classId)
            ->with('success', 'Assessment deleted successfully.');
    }
}
