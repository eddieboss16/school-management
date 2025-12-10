<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{
    public function student() {
        $user = auth()->user();

        return view('dashboard', [
            'userName' => $user->name,
            'userEmail' => $user->email,
        ]);
    }

    public function students() {
        $students = User::where('usertype', 'student')->get();

        return view('admin.students', [
            'students' => $students
        ]);
    }

    public function teachers() {
        $teachers = User::where('usertype', 'teacher')->get();

        return view('admin.teachers', [
            'teachers' => $teachers
        ]);
    }

    public function admin() {
        $totalStudents = User::where('usertype', 'student')->count();
        $totalTeachers = User::where('usertype', 'teacher')->count();
        $totalUsers = User::count();

        return view('admin.dashboard', [
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalUsers' => $totalUsers,
        ]);
    }
}
