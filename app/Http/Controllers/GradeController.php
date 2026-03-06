<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Models\StudentGrade;

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
        
        $request->validate([
            'assessment_type' => ['required', 'string', 'max:255'],
            'assessment_date' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'grades' => ['required', 'array'],
            'grades.*.score' => ['required', 'numeric', 'min:0'],
        ]);
        
        // Create grade records for each student
        foreach ($request->grades as $studentId => $gradeData) {
            if (isset($gradeData['score'])) {
                StudentGrade::create([
                    'class_id' => $classId,
                    'student_id' => $studentId,
                    'assessment_type' => $request->assessment_type,
                    'score' => $gradeData['score'],
                    'max_score' => $request->max_score,
                    'assessment_date' => $request->assessment_date,
                    'remarks' => $gradeData['remarks'] ?? null,
                    'entered_by' => $teacher->id,
                ]);
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
        
        // Update each grade record
        foreach ($request->grades as $gradeId => $gradeData) {
            $grade = StudentGrade::findOrFail($gradeId);
            
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
