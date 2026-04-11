<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\StreamController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [StudentDashboardController::class, 'index'])
->middleware(['auth', 'verified', 'role:student'])
->name('dashboard');

// Student attendance route
Route::get('/student/attendance', [StudentDashboardController::class, 'attendance'])
    ->middleware(['auth', 'role:student'])
    ->name('student.attendance');

// Student grades route
Route::get('/student/grades', [StudentDashboardController::class, 'grades'])
->middleware(['auth', 'role:student'])
->name('student.grades');

// Student report card route
Route::get('/student/report-card', [ReportCardController::class, 'studentReportCard'])
->middleware(['auth', 'role:student'])->name('student.report');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Parent
Route::middleware(['auth', 'role:parent'])->group(function () {
    Route::get('/parent/dashboard', [ParentDashboardController::class, 'index'])->name('parent.dashboard');
    Route::get('/parent/child/{id}/grades', [ParentDashboardController::class, 'grades'])->name('parent.child.grades');
    Route::get('/parent/child/{id}/attendance', [ParentDashboardController::class, 'attendance'])->name('parent.child.attendance');
    Route::get('/parent/child/{id}/report-card', [ParentDashboardController::class, 'reportCard'])->name('parent.child.report_card');
});

 // Admin
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/students', [StudentController::class, 'index'])->name('admin.students');
    Route::get('/admin/students/create', [StudentController::class, 'create'])->name('admin.students.create');
    Route::post('/admin/students', [StudentController::class, 'store'])->name('admin.students.store');
    Route::get('/admin/students/{id}/edit', [StudentController::class, 'edit'])->name('admin.students.edit');
    Route::put('/admin/students/{id}', [StudentController::class, 'update'])->name('admin.students.update');
    Route::delete('/admin/students/{id}', [StudentController::class, 'destroy'])->name('admin.students.destroy');

    Route::get('/admin/teachers', [TeacherController::class, 'index'])->name('admin.teachers');
    Route::get('/admin/teachers/create', [TeacherController::class, 'create'])->name('admin.teachers.create');
    Route::post('/admin/teachers', [TeacherController::class, 'store'])->name('admin.teachers.store');
    Route::get('/admin/teachers/{id}/edit', [TeacherController::class, 'edit'])->name('admin.teachers.edit');
    Route::put('/admin/teachers/{id}', [TeacherController::class, 'update'])->name('admin.teachers.update');
    Route::delete('/admin/teachers/{id}', [TeacherController::class, 'destroy'])->name('admin.teachers.destroy');

    // Stream management routes
    Route::get('/admin/streams', [StreamController::class, 'index'])->name('admin.streams');
    Route::get('/admin/streams/create', [StreamController::class, 'create'])->name('admin.streams.create');
    Route::post('/admin/streams', [StreamController::class, 'store'])->name('admin.streams.store');
    Route::get('/admin/streams/{id}/edit', [StreamController::class, 'edit'])->name('admin.streams.edit');
    Route::put('/admin/streams/{id}', [StreamController::class, 'update'])->name('admin.streams.update');
    Route::delete('/admin/streams/{id}', [StreamController::class, 'destroy'])->name('admin.streams.destroy');

    // Subject management routes
    Route::get('/admin/subjects', [SubjectController::class, 'index'])->name('admin.subjects');
    Route::get('/admin/subjects/create', [SubjectController::class, 'create'])->name('admin.subjects.create');
    Route::post('/admin/subjects', [SubjectController::class, 'store'])->name('admin.subjects.store');
    Route::get('/admin/subjects/{id}/edit', [SubjectController::class, 'edit'])->name('admin.subjects.edit');
    Route::put('/admin/subjects/{id}', [SubjectController::class, 'update'])->name('admin.subjects.update');
    Route::delete('/admin/subjects/{id}', [SubjectController::class, 'destroy'])->name('admin.subjects.destroy');

    // Class management routes
    Route::get('/admin/classes', [ClassController::class, 'index'])->name('admin.classes');
    Route::get('/admin/classes/create', [ClassController::class, 'create'])->name('admin.classes.create');
    Route::post('/admin/classes', [ClassController::class, 'store'])->name('admin.classes.store');
    Route::get('/admin/classes/{id}/edit', [ClassController::class, 'edit'])->name('admin.classes.edit');
    Route::put('/admin/classes/{id}', [ClassController::class, 'update'])->name('admin.classes.update');
    Route::delete('/admin/classes/{id}', [ClassController::class, 'destroy'])->name('admin.classes.destroy');

    // Enrollment management routes
    Route::get('/admin/classes/{id}/enrollments', [EnrollmentController::class, 'show'])
        ->name('admin.enrollments.show');
    Route::post('/admin/classes/{id}/enrollments', [EnrollmentController::class, 'store'])
        ->name('admin.enrollments.store');
    Route::delete('/admin/classes/{classId}/enrollments/{studentId}', [EnrollmentController::class, 'destroy'])
        ->name('admin.enrollments.destroy');
    Route::post('/admin/classes/{id}/enrollments/bulk', [EnrollmentController::class, 'bulkEnroll'])
        ->name('admin.enrollments.bulk');

        // Report card routes
    Route::get('/admin/reports', [ReportCardController::class, 'index'])
        ->name('admin.reports.index');
    Route::get('/admin/reports/student/{id}', [ReportCardController::class, 'generate'])
        ->name('admin.reports.generate');
});

 // Teacher
Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');

    // Attendance routes
    Route::get('/teacher/classes/{id}/attendance/mark', [AttendanceController::class, 'mark'])
    ->name('teacher.attendance.mark');
    Route::post('/teacher/classes/{id}/attendance', [AttendanceController::class, 'store'])
    ->name('teacher.attendance.store');
    Route::get('/teacher/classes/{id}/attendance/history', [AttendanceController::class, 'history'])
    ->name('teacher.attendance.history');

    // Grade routes
    Route::get('/teacher/classes/{id}/grades/enter', [GradeController::class, 'enter'])
    ->name('teacher.grades.enter');
    Route::post('/teacher/classes/{id}/grades', [GradeController::class, 'store'])
    ->name('teacher.grades.store');
    Route::get('/teacher/classes/{id}/grade', [GradeController::class, 'view'])
    ->name('teacher.grades.view');

    // Add these new routes
    Route::get('/teacher/classes/{id}/grades/{assessmentType}/edit', [GradeController::class, 'edit'])
    ->name('teacher.grades.edit');
    Route::put('/teacher/classes/{id}/grades/{assessmentType}', [GradeController::class, 'update'])
    ->name('teacher.grades.update');
    Route::delete('/teacher/classes/{id}/grades/{assessmentType}', [GradeController::class, 'destroy'])
    ->name('teacher.grades.destroy');
});

use App\Http\Controllers\Auth\ParentRegistrationController;

Route::middleware('guest')->group(function () {
    Route::get('/parent/register', [ParentRegistrationController::class, 'create'])->name('parent.register');
    Route::post('/parent/register', [ParentRegistrationController::class, 'store'])->name('parent.register.store');
});

require __DIR__.'/auth.php';

