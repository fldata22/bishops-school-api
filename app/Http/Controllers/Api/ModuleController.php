<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Module::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'topics' => 'required|array',
            'topics.*' => 'string',
        ]);
        $module = Module::create($validated);
        return response()->json(['data' => $module], 201);
    }

    public function show(Module $module): JsonResponse
    {
        return response()->json(['data' => $module]);
    }

    public function update(Request $request, Module $module): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'topics' => 'required|array',
            'topics.*' => 'string',
        ]);
        $module->update($validated);
        return response()->json(['data' => $module]);
    }

    public function destroy(Module $module): JsonResponse
    {
        $module->delete();
        return response()->json(null, 204);
    }
}
