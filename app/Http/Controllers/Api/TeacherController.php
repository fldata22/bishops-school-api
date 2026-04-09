<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Teacher::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $teacher = Teacher::create($validated);
        return response()->json(['data' => $teacher], 201);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        return response()->json(['data' => $teacher]);
    }

    public function update(Request $request, Teacher $teacher): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $teacher->update($validated);
        return response()->json(['data' => $teacher]);
    }

    public function destroy(Teacher $teacher): JsonResponse
    {
        $teacher->delete();
        return response()->json(null, 204);
    }
}
