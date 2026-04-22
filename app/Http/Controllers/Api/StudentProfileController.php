<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\Participation;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentProfileController extends Controller
{
    public function __invoke(Student $student): JsonResponse
    {
        $student->load(['schoolClass', 'church']);

        $records = Attendance::where('student_id', $student->id)->get();
        $presentCount = $records->where('status', 'present')->count();
        $absentCount = $records->where('status', 'absent')->count();
        $total = $records->count();
        $rate = $total > 0 ? (float) round(($presentCount / $total) * 100, 1) : 0.0;

        // Legacy source: participation_level stored on attendance rows.
        $participationLevels = $records->where('status', 'present')
            ->whereNotNull('participation_level')
            ->pluck('participation_level')
            ->all();

        // New source: Participation rows keyed by class + date with a JSON
        // records array of { student_id, participation_level }.
        foreach (Participation::all() as $row) {
            foreach ((array) $row->records as $entry) {
                if ((int) ($entry['student_id'] ?? 0) === $student->id && isset($entry['participation_level'])) {
                    $participationLevels[] = (int) $entry['participation_level'];
                }
            }
        }

        $participationAvg = count($participationLevels) > 0
            ? (float) round(array_sum($participationLevels) / count($participationLevels), 1)
            : null;

        // Module breakdown
        $moduleIds = Session::whereHas('attendanceRecords', fn ($q) => $q->where('student_id', $student->id))
            ->distinct()->pluck('module_id');

        $moduleBreakdown = Module::whereIn('id', $moduleIds)->get()->map(function ($module) use ($student) {
            $sessionIds = Session::where('module_id', $module->id)->pluck('id');
            $moduleRecords = Attendance::where('student_id', $student->id)->whereIn('session_id', $sessionIds)->get();
            $moduleTotal = $moduleRecords->count();
            $modulePresent = $moduleRecords->where('status', 'present')->count();
            return [
                'module_id' => $module->id,
                'module_name' => $module->name,
                'rate' => $moduleTotal > 0 ? (float) round(($modulePresent / $moduleTotal) * 100, 1) : 0.0,
            ];
        });

        return new JsonResponse(['data' => [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'class' => $student->schoolClass?->name,
                'church' => $student->church?->name,
                'gender' => $student->gender,
            ],
            'attendance_rate' => $rate,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'participation_average' => $participationAvg,
            'module_breakdown' => $moduleBreakdown->values(),
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }
}
