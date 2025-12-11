<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;

class DashboardController extends Controller
{
    // Student's
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

    public function createStudent() {
        return view('admin.students-create');
    }

    public function storeStudent(Request $request) {
        $request->validate([
            'name' => ['required', 'string', 'Max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'usertype' =>'student',
            'password' => bcrypt($request->password),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.students')->with('success', 'Student created successfully.');
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
