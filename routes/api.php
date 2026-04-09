<?php
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\DenominationController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Route;

Route::apiResource('denominations', DenominationController::class);
Route::apiResource('churches', ChurchController::class);
Route::apiResource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
Route::apiResource('teachers', TeacherController::class);
Route::apiResource('modules', ModuleController::class);
Route::apiResource('students', StudentController::class);
