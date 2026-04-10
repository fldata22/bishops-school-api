<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use Illuminate\Http\JsonResponse;

class ModuleProgressController extends Controller
{
    public function __invoke(Module $module): JsonResponse
    {
        $books = $module->books()->get();
        $sessions = Session::where('module_id', $module->id)->get();
        $sessionIds = $sessions->pluck('id');

        // Total chapters = sum of all chapter arrays across all books
        $totalChapters = $books->sum(fn ($b) => count($b->chapters));

        // Distinct (book_id, chapter_index) pairs taught
        $chaptersTaught = $sessions->unique(fn ($s) => $s->book_id . '-' . $s->chapter_index)->count();
        $completionRate = $totalChapters > 0 ? (float) round(($chaptersTaught / $totalChapters) * 100, 1) : 0.0;

        $totalRecords = Attendance::whereIn('session_id', $sessionIds)->count();
        $presentRecords = Attendance::whereIn('session_id', $sessionIds)->where('status', 'present')->count();
        $attendanceRate = $totalRecords > 0 ? (float) round(($presentRecords / $totalRecords) * 100, 1) : 0.0;

        $classIds = $sessions->pluck('class_id')->unique();
        $classBreakdown = SchoolClass::whereIn('id', $classIds)->get()->map(function ($class) use ($module) {
            $classSessionIds = Session::where('module_id', $module->id)->where('class_id', $class->id)->pluck('id');
            $total = Attendance::whereIn('session_id', $classSessionIds)->count();
            $present = Attendance::whereIn('session_id', $classSessionIds)->where('status', 'present')->count();
            return [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'rate' => $total > 0 ? (float) round(($present / $total) * 100, 1) : 0.0,
                'sessions' => $classSessionIds->count(),
            ];
        });

        // Per-book breakdown
        $bookBreakdown = $books->map(function ($book) use ($sessions) {
            $bookSessions = $sessions->where('book_id', $book->id);
            $chaptersTaughtInBook = $bookSessions->unique('chapter_index')->count();
            $totalChaptersInBook = count($book->chapters);
            return [
                'book_id' => $book->id,
                'book_name' => $book->name,
                'total_chapters' => $totalChaptersInBook,
                'chapters_taught' => $chaptersTaughtInBook,
                'rate' => $totalChaptersInBook > 0 ? (float) round(($chaptersTaughtInBook / $totalChaptersInBook) * 100, 1) : 0.0,
            ];
        });

        // Per-chapter attendance (renamed from topic_attendance)
        $chapterAttendance = $books->flatMap(function ($book) use ($sessions) {
            return collect($book->chapters)->map(function ($chapterName, $index) use ($book, $sessions) {
                $chapterSessionIds = $sessions
                    ->where('book_id', $book->id)
                    ->where('chapter_index', $index)
                    ->pluck('id');
                $total = Attendance::whereIn('session_id', $chapterSessionIds)->count();
                $present = Attendance::whereIn('session_id', $chapterSessionIds)->where('status', 'present')->count();
                return [
                    'book_id' => $book->id,
                    'book_name' => $book->name,
                    'chapter_index' => $index,
                    'chapter_name' => $chapterName,
                    'rate' => $total > 0 ? (float) round(($present / $total) * 100, 1) : null,
                ];
            });
        });

        return new JsonResponse(['data' => [
            'module' => ['id' => $module->id, 'name' => $module->name, 'code' => $module->code, 'total_chapters' => $totalChapters],
            'completion_rate' => $completionRate,
            'chapters_taught' => $chaptersTaught,
            'attendance_rate' => $attendanceRate,
            'class_breakdown' => $classBreakdown->values(),
            'book_breakdown' => $bookBreakdown->values(),
            'chapter_attendance' => $chapterAttendance->values(),
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
