<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Book;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Session::with(['schoolClass', 'module', 'teacher']);
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('module_id')) {
            $query->where('module_id', $request->module_id);
        }
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'module_id' => 'required|exists:modules,id',
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'book_id' => 'required|exists:books,id',
            'chapter_index' => 'required|integer|min:0',
            'attendance' => 'required|array|min:1',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent',
            'attendance.*.participation_level' => 'nullable|integer|min:1|max:4',
        ]);

        // Verify the book belongs to the specified module
        $book = Book::find($validated['book_id']);
        if ($book->module_id !== (int) $validated['module_id']) {
            return response()->json([
                'message' => 'Book does not belong to the specified module',
            ], 422);
        }

        // Validate all students belong to the session's class
        $studentIds = collect($validated['attendance'])->pluck('student_id');
        $invalidStudents = Student::whereIn('id', $studentIds)
            ->where('class_id', '!=', $validated['class_id'])
            ->exists();

        if ($invalidStudents) {
            return response()->json([
                'message' => 'Some students do not belong to the specified class.',
                'errors' => ['attendance' => ['All students must belong to the session class.']],
            ], 422);
        }

        $session = DB::transaction(function () use ($validated) {
            $session = Session::create([
                'class_id' => $validated['class_id'],
                'module_id' => $validated['module_id'],
                'book_id' => $validated['book_id'],
                'chapter_index' => $validated['chapter_index'],
                'teacher_id' => $validated['teacher_id'],
                'date' => $validated['date'],
            ]);

            foreach ($validated['attendance'] as $record) {
                Attendance::create([
                    'session_id' => $session->id,
                    'student_id' => $record['student_id'],
                    'status' => $record['status'],
                    'participation_level' => $record['status'] === 'present'
                        ? ($record['participation_level'] ?? null)
                        : null,
                ]);
            }

            return $session;
        });

        return response()->json(['data' => $session->load('attendanceRecords')], 201);
    }

    public function show(Session $session): JsonResponse
    {
        return response()->json([
            'data' => $session->load(['schoolClass', 'module', 'book', 'teacher', 'attendanceRecords.student']),
        ]);
    }

    public function destroy(Session $session): JsonResponse
    {
        $session->delete();
        return response()->json(null, 204);
    }
}
