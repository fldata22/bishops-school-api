<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::query();
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('church_id')) {
            $query->where('church_id', $request->church_id);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'church_id' => 'required|exists:churches,id',
            'gender' => 'required|in:male,female',
        ]);
        $student = Student::create($validated);
        return response()->json(['data' => $student], 201);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json(['data' => $student]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classes,id',
            'church_id' => 'required|exists:churches,id',
            'gender' => 'required|in:male,female',
        ]);
        $student->update($validated);
        return response()->json(['data' => $student]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();
        return response()->json(null, 204);
    }
}
