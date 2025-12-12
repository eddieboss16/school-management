<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'student'])->middleware(['auth', 'verified', 'role:student'])
->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [App\Http\Controllers\DashboardController::class, 'admin'])
    ->name('admin.dashboard');

    Route::get('/admin/students', [App\Http\Controllers\DashboardController::class, 'students'])
    ->name('admin.students');
    Route::get('/admin/students/create', [App\Http\Controllers\DashboardController::class, 'createStudent'])
    ->name('admin.students.create');
    Route::post('/admin/students', [App\Http\Controllers\DashboardController::class, 'storeStudent'])
    ->name('admin.students.store');

    Route::get('/admin/students/{id}/edit', [App\Http\Controllers\DashboardController::class, 'editStudent'])
    ->name('admin.students.edit');
    Route::put('/admin/students/{id}', [App\Http\Controllers\DashboardController::class, 'updateStudent'])
    ->name('admin.students.update');
    Route::delete('/admin/students/{id}', [App\Http\Controllers\DashboardController::class, 'destroyStudent'])
    ->name('admin.students.destroy');

    Route::get('/admin/teachers', [App\Http\Controllers\DashboardController::class, 'teachers'])
    ->name('admin.teachers');
    
    Route::get('/admin/teachers/create', [App\Http\Controllers\DashboardController::class, 'createTeacher'])
    ->name('admin.teachers.create');
    Route::post('/admin/teachers', [App\Http\Controllers\DashboardController::class, 'storeTeacher'])
    ->name('admin.teachers.store');

    Route::get('/admin/teachers/{id}/edit', [App\Http\Controllers\DashboardController::class, 'editTeacher'])
    ->name('admin.teachers.edit');
    Route::put('/admin/teachers/{id}', [App\Http\Controllers\DashboardController::class, 'updateTeacher'])
    ->name('admin.teachers.update');
    Route::delete('/admin/teachers/{id}', [App\Http\Controllers\DashboardController::class, 'destroyTeacher'])
    ->name('admin.teachers.destroy');
});

Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/teacher/dashboard', function () {
        return view('teacher.dashboard');
    })->name('teacher.dashboard');
});

require __DIR__.'/auth.php';
