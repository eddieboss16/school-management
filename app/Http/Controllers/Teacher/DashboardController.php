<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;

class DashboardController extends Controller
{
    public function index() {
        $teacher = auth()->user();
        $classes = SchoolClass::with(['grade', 'stream', 'subject', 'students'])
        ->where('teacher_id', $teacher->id)
        ->get();

        $totalClasses = $classes->count();
        $totalStudents = $classes->sum(function($class) {
            return $class->students->count();
        });
        return view('teacher.dashboard', compact('classes', 'totalClasses', 'totalStudents'));
    }
}
