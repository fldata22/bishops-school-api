<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Teacher;
use App\Models\TeacherModuleAssignment;
use Illuminate\Http\JsonResponse;

class TeacherCoverageController extends Controller
{
    public function __invoke(Teacher $teacher): JsonResponse
    {
        $moduleIds = TeacherModuleAssignment::where('teacher_id', $teacher->id)
            ->distinct()->pluck('module_id');

        $modules = Module::with('books')->whereIn('id', $moduleIds)->orderBy('id')->get();

        // book_id => chapter count, for every book in the assigned modules.
        $bookChapterCount = [];
        foreach ($modules as $module) {
            foreach ($module->books as $book) {
                $bookChapterCount[$book->id] = count($book->chapters ?? []);
            }
        }

        // Sessions for this teacher in any assigned module, after the stale-index guard.
        $validSessions = Session::where('teacher_id', $teacher->id)
            ->whereIn('module_id', $moduleIds)
            ->get(['module_id', 'class_id', 'book_id', 'chapter_index', 'date'])
            ->filter(fn ($s) => isset($bookChapterCount[$s->book_id])
                && $s->chapter_index < $bookChapterCount[$s->book_id]);

        $sessionsByModule = $validSessions->groupBy('module_id');
        $classNames = SchoolClass::pluck('name', 'id');

        $moduleData = $modules->map(function ($module) use ($sessionsByModule, $classNames) {
            $moduleSessions = $sessionsByModule->get($module->id, collect());

            // (book_id . '-' . chapter_index) => max date taught (Y-m-d)
            $chapterDates = [];
            foreach ($moduleSessions as $s) {
                $key = $s->book_id . '-' . $s->chapter_index;
                $date = $s->date->format('Y-m-d');
                if (!isset($chapterDates[$key]) || $date > $chapterDates[$key]) {
                    $chapterDates[$key] = $date;
                }
            }

            $books = $module->books->sortBy('position')->values()->map(function ($book) use ($chapterDates) {
                $chapters = collect($book->chapters ?? [])->values()->map(function ($title, $idx) use ($book, $chapterDates) {
                    $key = $book->id . '-' . $idx;
                    $taught = isset($chapterDates[$key]);
                    return [
                        'index' => $idx,
                        'title' => $title,
                        'taught' => $taught,
                        'last_taught_date' => $taught ? $chapterDates[$key] : null,
                    ];
                });
                return [
                    'id' => $book->id,
                    'name' => $book->name,
                    'position' => $book->position,
                    'chapters' => $chapters->values(),
                ];
            });

            $total = $module->books->sum(fn ($b) => count($b->chapters ?? []));
            $taught = count($chapterDates);
            $rate = $total > 0 ? (float) round(($taught / $total) * 100, 1) : 0.0;

            $classes = $moduleSessions->pluck('class_id')->unique()->sort()->values()
                ->map(fn ($cid) => ['id' => $cid, 'name' => $classNames[$cid] ?? null]);

            return [
                'id' => $module->id,
                'name' => $module->name,
                'code' => $module->code,
                'rate' => $rate,
                'taught_chapters' => $taught,
                'total_chapters' => $total,
                'classes' => $classes->values(),
                'books' => $books->values(),
            ];
        });

        $totalChapters = $modules->sum(fn ($m) => $m->books->sum(fn ($b) => count($b->chapters ?? [])));
        $taughtChapters = $validSessions
            ->unique(fn ($s) => $s->book_id . '-' . $s->chapter_index)
            ->count();
        $rate = $totalChapters > 0
            ? (float) round(($taughtChapters / $totalChapters) * 100, 1)
            : 0.0;

        return new JsonResponse(['data' => [
            'teacher' => ['id' => $teacher->id, 'name' => $teacher->name],
            'rate' => $rate,
            'taught_chapters' => $taughtChapters,
            'total_chapters' => $totalChapters,
            'modules' => $moduleData->values(),
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
