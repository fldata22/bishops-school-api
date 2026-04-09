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
        $sessions = Session::where('module_id', $module->id)->get();
        $sessionIds = $sessions->pluck('id');
        $totalTopics = count($module->topics);

        $topicsTaught = $sessions->pluck('topic_index')->unique()->count();
        $completionRate = $totalTopics > 0 ? (float) round(($topicsTaught / $totalTopics) * 100, 1) : 0.0;

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

        $topicAttendance = collect($module->topics)->map(function ($topicName, $index) use ($sessionIds, $sessions) {
            $topicSessionIds = $sessions->where('topic_index', $index)->pluck('id');
            $total = Attendance::whereIn('session_id', $topicSessionIds)->count();
            $present = Attendance::whereIn('session_id', $topicSessionIds)->where('status', 'present')->count();
            return [
                'topic_index' => $index,
                'topic_name' => $topicName,
                'rate' => $total > 0 ? (float) round(($present / $total) * 100, 1) : null,
            ];
        });

        return new JsonResponse(['data' => [
            'module' => ['id' => $module->id, 'name' => $module->name, 'code' => $module->code, 'total_topics' => $totalTopics],
            'completion_rate' => $completionRate,
            'topics_taught' => $topicsTaught,
            'attendance_rate' => $attendanceRate,
            'class_breakdown' => $classBreakdown->values(),
            'topic_attendance' => $topicAttendance->values(),
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
