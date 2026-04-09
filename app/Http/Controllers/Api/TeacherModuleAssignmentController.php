<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherModuleAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherModuleAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TeacherModuleAssignment::with(['teacher', 'module', 'schoolClass']);
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'module_id' => 'required|exists:modules,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $exists = TeacherModuleAssignment::where($validated)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'This assignment already exists.',
                'errors' => ['teacher_id' => ['Duplicate assignment.']],
            ], 422);
        }

        $assignment = TeacherModuleAssignment::create($validated);
        return response()->json(['data' => $assignment], 201);
    }

    public function destroy(TeacherModuleAssignment $teacherModuleAssignment): JsonResponse
    {
        $teacherModuleAssignment->delete();
        return response()->json(null, 204);
    }
}
