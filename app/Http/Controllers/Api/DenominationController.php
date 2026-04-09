<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Denomination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DenominationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Denomination::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:20',
        ]);
        $denomination = Denomination::create($validated);
        return response()->json(['data' => $denomination], 201);
    }

    public function show(Denomination $denomination): JsonResponse
    {
        return response()->json(['data' => $denomination]);
    }

    public function update(Request $request, Denomination $denomination): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:20',
        ]);
        $denomination->update($validated);
        return response()->json(['data' => $denomination]);
    }

    public function destroy(Denomination $denomination): JsonResponse
    {
        $denomination->delete();
        return response()->json(null, 204);
    }
}
