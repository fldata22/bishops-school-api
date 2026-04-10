<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Module::with('books')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'books' => 'sometimes|array',
            'books.*.name' => 'required|string|max:255',
            'books.*.chapters' => 'required|array|min:1',
            'books.*.chapters.*' => 'string',
        ]);

        $module = DB::transaction(function () use ($validated) {
            $module = Module::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
            ]);

            foreach ($validated['books'] ?? [] as $position => $bookData) {
                $module->books()->create([
                    'name' => $bookData['name'],
                    'chapters' => $bookData['chapters'],
                    'position' => $position,
                ]);
            }

            return $module->load('books');
        });

        return response()->json(['data' => $module], 201);
    }

    public function show(Module $module): JsonResponse
    {
        return response()->json(['data' => $module->load('books')]);
    }

    public function update(Request $request, Module $module): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'books' => 'sometimes|array',
            'books.*.name' => 'required|string|max:255',
            'books.*.chapters' => 'required|array|min:1',
            'books.*.chapters.*' => 'string',
        ]);

        $module = DB::transaction(function () use ($validated, $module) {
            $module->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
            ]);

            if (array_key_exists('books', $validated)) {
                // WARNING: This is destructive — deleting books cascades to sessions that reference them.
                $module->books()->delete();

                foreach ($validated['books'] as $position => $bookData) {
                    $module->books()->create([
                        'name' => $bookData['name'],
                        'chapters' => $bookData['chapters'],
                        'position' => $position,
                    ]);
                }
            }

            return $module->load('books');
        });

        return response()->json(['data' => $module]);
    }

    public function destroy(Module $module): JsonResponse
    {
        $module->delete();
        return response()->json(null, 204);
    }
}
