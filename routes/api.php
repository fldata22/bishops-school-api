<?php
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceOverviewController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DenominationController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ModuleProgressController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherModuleAssignmentController;
use App\Http\Controllers\Api\TeacherStatsController;
use Illuminate\Support\Facades\Route;

Route::apiResource('denominations', DenominationController::class);
Route::apiResource('churches', ChurchController::class);
Route::apiResource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
Route::apiResource('teachers', TeacherController::class);
Route::apiResource('modules', ModuleController::class);
Route::apiResource('students', StudentController::class);
Route::post('students/{student}/image', [StudentController::class, 'uploadImage']);
Route::apiResource('teacher-module-assignments', TeacherModuleAssignmentController::class)
    ->only(['index', 'store', 'destroy']);
Route::apiResource('sessions', SessionController::class)->except(['update']);
Route::patch('attendance/{attendance}', [AttendanceController::class, 'update']);
Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy']);

Route::get('dashboard', DashboardController::class);
Route::get('attendance-overview', AttendanceOverviewController::class);
Route::get('students/{student}/profile', StudentProfileController::class);
Route::get('teachers/{teacher}/stats', TeacherStatsController::class);
Route::get('modules/{module}/progress', ModuleProgressController::class);

Route::post('modules/{module}/books', [BookController::class, 'store']);
Route::put('books/{book}', [BookController::class, 'update']);
Route::delete('books/{book}', [BookController::class, 'destroy']);
