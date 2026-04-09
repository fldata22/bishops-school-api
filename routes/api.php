<?php
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\DenominationController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeacherModuleAssignmentController;
use Illuminate\Support\Facades\Route;

Route::apiResource('denominations', DenominationController::class);
Route::apiResource('churches', ChurchController::class);
Route::apiResource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
Route::apiResource('teachers', TeacherController::class);
Route::apiResource('modules', ModuleController::class);
Route::apiResource('students', StudentController::class);
Route::apiResource('teacher-module-assignments', TeacherModuleAssignmentController::class)
    ->only(['index', 'store', 'destroy']);
Route::apiResource('sessions', SessionController::class)->except(['update']);
