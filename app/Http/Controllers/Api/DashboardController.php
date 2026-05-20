<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $totalAttendance = Attendance::count();
        $presentCount = Attendance::where('status', 'present')->count();

        // Overall class attendance: average attendance rate across classes with session data
        $classRates = SchoolClass::all()->map(function ($class) {
            $total = Attendance::whereHas('session', fn ($q) => $q->where('class_id', $class->id))->count();
            $present = Attendance::whereHas('session', fn ($q) => $q->where('class_id', $class->id))
                ->where('status', 'present')->count();
            return $total > 0 ? (float) round(($present / $total) * 100, 1) : null;
        })->filter(fn ($rate) => $rate !== null);
        $overallClassAttendance = $classRates->count() > 0 ? (float) round($classRates->avg(), 1) : 0;

        // Overall module attendance: average across modules with session data
        $moduleRates = Module::all()->map(function ($module) {
            $total = Attendance::whereHas('session', fn ($q) => $q->where('module_id', $module->id))->count();
            $present = Attendance::whereHas('session', fn ($q) => $q->where('module_id', $module->id))
                ->where('status', 'present')->count();
            return $total > 0 ? (float) round(($present / $total) * 100, 1) : null;
        })->filter(fn ($rate) => $rate !== null);
        $overallModuleAttendance = $moduleRates->count() > 0 ? (float) round($moduleRates->avg(), 1) : 0;

        // Teacher targets: pooled lesson coverage across ALL modules. Every
        // teacher is responsible for every module (assignment to a class
        // implies teaching all modules in it), so the denominator is the same
        // for everyone: total chapters across every module in the system.
        $bookChapterCount = [];
        $totalChapters = 0;
        foreach (Module::with('books')->get() as $module) {
            foreach ($module->books as $book) {
                $count = count($book->chapters ?? []);
                $bookChapterCount[$book->id] = $count;
                $totalChapters += $count;
            }
        }

        $teacherTargets = Teacher::all()->map(function ($teacher) use ($bookChapterCount, $totalChapters) {
            // Distinct (book_id, chapter_index) taught by this teacher.
            // The isset() drops sessions whose book no longer exists;
            // the index check drops chapters deleted since the session was logged.
            $taught = Session::where('teacher_id', $teacher->id)
                ->get(['book_id', 'chapter_index'])
                ->filter(fn ($s) => isset($bookChapterCount[$s->book_id])
                    && $s->chapter_index < $bookChapterCount[$s->book_id])
                ->unique(fn ($s) => $s->book_id . '-' . $s->chapter_index)
                ->count();

            $rate = $totalChapters > 0
                ? (float) round(($taught / $totalChapters) * 100, 1)
                : 0.0;

            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'rate' => $rate,
            ];
        });

        return new JsonResponse(['data' => [
            'overall_class_attendance' => $overallClassAttendance,
            'overall_module_attendance' => $overallModuleAttendance,
            'students_enrolled' => Student::count(),
            'teacher_count' => Teacher::count(),
            'teacher_targets' => $teacherTargets->values(),
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
