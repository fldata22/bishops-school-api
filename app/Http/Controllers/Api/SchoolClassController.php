<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SchoolClass::with('teacher');

        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_id' => 'nullable|exists:teachers,id',
            'category' => 'nullable|in:non_consecrated,newly_consecrated',
        ]);
        $class = SchoolClass::create($validated);
        return response()->json(['data' => $class], 201);
    }

    public function show(SchoolClass $class): JsonResponse
    {
        return response()->json(['data' => $class->load('teacher')]);
    }

    public function update(Request $request, SchoolClass $class): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'teacher_id' => 'nullable|exists:teachers,id',
            'category' => 'nullable|in:non_consecrated,newly_consecrated',
        ]);
        $class->update($validated);
        return response()->json(['data' => $class]);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        $class->delete();
        return response()->json(null, 204);
    }
}
