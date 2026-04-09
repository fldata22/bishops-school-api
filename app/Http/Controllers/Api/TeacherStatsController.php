<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

class TeacherStatsController extends Controller
{
    public function __invoke(Teacher $teacher): JsonResponse
    {
        $sessions = Session::where('teacher_id', $teacher->id)->get();
        $totalSessions = $sessions->count();

        $classIds = $sessions->pluck('class_id')->unique();
        $classes = SchoolClass::whereIn('id', $classIds)->get()->map(function ($class) use ($teacher) {
            $classSessionIds = Session::where('teacher_id', $teacher->id)
                ->where('class_id', $class->id)->pluck('id');
            $sessionCount = $classSessionIds->count();
            $total = Attendance::whereIn('session_id', $classSessionIds)->count();
            $present = Attendance::whereIn('session_id', $classSessionIds)->where('status', 'present')->count();
            return [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'sessions' => $sessionCount,
                'attendance_rate' => $total > 0 ? (float) round(($present / $total) * 100, 1) : 0.0,
            ];
        });

        $monthlyBreakdown = $sessions->groupBy(function ($session) {
            return $session->date->format('Y-m');
        })->map(function ($group, $month) {
            return ['month' => $month, 'sessions' => $group->count()];
        })->values();

        return new JsonResponse(['data' => [
            'teacher' => ['id' => $teacher->id, 'name' => $teacher->name],
            'total_sessions' => $totalSessions,
            'classes' => $classes->values(),
            'monthly_breakdown' => $monthlyBreakdown,
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
