<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participation;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
        ]);

        $participation = Participation::where('class_id', $validated['class_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        return response()->json(['data' => $participation]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'date' => 'required|date',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.participation_level' => 'required|integer|min:1|max:4',
        ]);

        $studentIds = collect($validated['records'])->pluck('student_id');
        $invalid = Student::whereIn('id', $studentIds)
            ->where('class_id', '!=', $validated['class_id'])
            ->exists();

        if ($invalid) {
            return response()->json([
                'message' => 'Some students do not belong to the specified class.',
                'errors' => ['records' => ['All students must belong to the specified class.']],
            ], 422);
        }

        $participation = Participation::updateOrCreate(
            ['class_id' => $validated['class_id'], 'date' => $validated['date']],
            [
                'teacher_id' => $validated['teacher_id'] ?? null,
                'records' => $validated['records'],
            ],
        );

        return response()->json(['data' => $participation], $participation->wasRecentlyCreated ? 201 : 200);
    }
}
