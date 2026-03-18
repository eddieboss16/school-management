<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\SchoolClass;

class DashboardController extends Controller
{
    public function index() {
        $totalStudents = User::where('usertype', 'student')->count();
        $totalTeachers = User::where('usertype', 'teacher')->count();
        $totalUsers = User::count();
        $totalStreams = Stream::count();
        $totalSubjects = Subject::count();
        $totalClasses = SchoolClass::count();

        return view('admin.dashboard', [
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalUsers' => $totalUsers,
            'totalStreams' => $totalStreams,
            'totalSubjects' => $totalSubjects,
            'totalClasses' => $totalClasses,
        ]);
    }
}
