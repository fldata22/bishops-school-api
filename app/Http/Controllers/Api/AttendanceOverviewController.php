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

class AttendanceOverviewController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $totalAttendance = Attendance::count();
        $totalPresent = Attendance::where('status', 'present')->count();
        $overallRate = $totalAttendance > 0 ? (float) round(($totalPresent / $totalAttendance) * 100, 1) : 0.0;

        // Today's counts
        $todaySessions = Session::whereDate('date', now()->toDateString())->pluck('id');
        $presentToday = Attendance::whereIn('session_id', $todaySessions)->where('status', 'present')->count();
        $absentToday = Attendance::whereIn('session_id', $todaySessions)->where('status', 'absent')->count();

        // Class attendance
        $classAttendance = SchoolClass::all()->map(function ($class) {
            $sessions = Session::where('class_id', $class->id);
            $sessionsThisMonth = (clone $sessions)->where('date', '>=', now()->startOfMonth())->count();
            $sessionIds = $sessions->pluck('id');
            $total = Attendance::whereIn('session_id', $sessionIds)->count();
            $present = Attendance::whereIn('session_id', $sessionIds)->where('status', 'present')->count();
            $studentCount = Student::where('class_id', $class->id)->count();
            return [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'rate' => $total > 0 ? (float) round(($present / $total) * 100, 1) : 0.0,
                'sessions_this_month' => $sessionsThisMonth,
                'present' => $present,
                'total' => $studentCount,
            ];
        });

        // Module attendance
        $moduleAttendance = Module::all()->map(function ($module) {
            $sessionIds = Session::where('module_id', $module->id)->pluck('id');
            $sessionCount = $sessionIds->count();
            $total = Attendance::whereIn('session_id', $sessionIds)->count();
            $present = Attendance::whereIn('session_id', $sessionIds)->where('status', 'present')->count();
            return [
                'module_id' => $module->id,
                'module_name' => $module->name,
                'code' => $module->code,
                'sessions' => $sessionCount,
                'topics' => count($module->topics),
                'rate' => $total > 0 ? (float) round(($present / $total) * 100, 1) : 0.0,
            ];
        });

        // Teacher activity
        $totalSessions = Session::count();
        $teacherActivity = Teacher::all()->map(function ($teacher) use ($totalSessions) {
            $sessionCount = Session::where('teacher_id', $teacher->id)->count();
            return [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'sessions' => $sessionCount,
                'percentage_of_total' => $totalSessions > 0 ? (float) round(($sessionCount / $totalSessions) * 100, 1) : 0.0,
            ];
        });

        // Critical alerts
        $criticalAlerts = $this->getCriticalAlerts();

        // Weekly trends
        $weeklyTrends = $this->getWeeklyTrends();

        return new JsonResponse(['data' => [
            'overall_rate' => $overallRate,
            'total_students' => Student::count(),
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'teacher_count' => Teacher::count(),
            'critical_alerts' => $criticalAlerts,
            'class_attendance' => $classAttendance->values(),
            'module_attendance' => $moduleAttendance->values(),
            'teacher_activity' => $teacherActivity->values(),
            'weekly_trends' => $weeklyTrends,
        ]], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    private function getCriticalAlerts(): array
    {
        $alerts = [];
        $students = Student::with('schoolClass')->get();
        foreach ($students as $student) {
            $records = Attendance::where('student_id', $student->id)
                ->join('sessions', 'attendance.session_id', '=', 'sessions.id')
                ->orderBy('sessions.date', 'desc')
                ->select('attendance.status')
                ->get();
            $consecutive = 0;
            foreach ($records as $record) {
                if ($record->status === 'absent') {
                    $consecutive++;
                } else {
                    break;
                }
            }
            if ($consecutive >= 3) {
                $alerts[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'class_name' => $student->schoolClass->name,
                    'consecutive_absences' => $consecutive,
                ];
            }
        }
        return $alerts;
    }

    private function getWeeklyTrends(): array
    {
        $trends = [];
        $startOfWeek = now()->startOfWeek();
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = $startOfWeek->copy()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekLabel = $weekStart->format('o-\\WW');
            $sessionIds = Session::whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])->pluck('id');
            $total = Attendance::whereIn('session_id', $sessionIds)->count();
            $present = Attendance::whereIn('session_id', $sessionIds)->where('status', 'present')->count();
            if ($total > 0) {
                $trends[] = ['week' => $weekLabel, 'rate' => (float) round(($present / $total) * 100, 1)];
            }
        }
        return $trends;
    }
}
