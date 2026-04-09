<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => SchoolClass::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $class = SchoolClass::create($validated);
        return response()->json(['data' => $class], 201);
    }

    public function show(SchoolClass $class): JsonResponse
    {
        return response()->json(['data' => $class]);
    }

    public function update(Request $request, SchoolClass $class): JsonResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $class->update($validated);
        return response()->json(['data' => $class]);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        $class->delete();
        return response()->json(null, 204);
    }
}
