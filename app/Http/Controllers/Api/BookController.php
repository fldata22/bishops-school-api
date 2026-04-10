<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function store(Request $request, Module $module): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'chapters' => 'required|array|min:1',
            'chapters.*' => 'string',
            'position' => 'nullable|integer|min:0',
        ]);
        $validated['module_id'] = $module->id;
        $validated['position'] = $validated['position'] ?? ($module->books()->max('position') + 1);
        $book = Book::create($validated);
        return response()->json(['data' => $book], 201);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'chapters' => 'sometimes|array|min:1',
            'chapters.*' => 'string',
            'position' => 'sometimes|integer|min:0',
        ]);
        $book->update($validated);
        return response()->json(['data' => $book]);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();
        return response()->json(null, 204);
    }
}
