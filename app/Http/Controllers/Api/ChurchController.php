<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Church;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChurchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Church::query();
        if ($request->has('denomination_id')) {
            $query->where('denomination_id', $request->denomination_id);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'denomination_id' => 'required|exists:denominations,id',
        ]);
        $church = Church::create($validated);
        return response()->json(['data' => $church], 201);
    }

    public function show(Church $church): JsonResponse
    {
        return response()->json(['data' => $church]);
    }

    public function update(Request $request, Church $church): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'denomination_id' => 'required|exists:denominations,id',
        ]);
        $church->update($validated);
        return response()->json(['data' => $church]);
    }

    public function destroy(Church $church): JsonResponse
    {
        $church->delete();
        return response()->json(null, 204);
    }
}
