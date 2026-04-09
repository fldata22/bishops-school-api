<?php
namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Church;
use App\Models\Denomination;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/attendance-overview');
        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'overall_rate', 'total_students', 'present_today', 'absent_today', 'teacher_count',
                'critical_alerts', 'class_attendance', 'module_attendance', 'teacher_activity', 'weekly_trends',
            ]]);
    }

    public function test_present_today_counts_correctly(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro']]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $s1 = Student::create(['name' => 'A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);
        $s2 = Student::create(['name' => 'B', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'female']);

        $session = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'teacher_id' => $teacher->id, 'date' => now()->toDateString(), 'topic_index' => 0]);
        Attendance::create(['session_id' => $session->id, 'student_id' => $s1->id, 'status' => 'present']);
        Attendance::create(['session_id' => $session->id, 'student_id' => $s2->id, 'status' => 'absent']);

        $response = $this->getJson('/api/attendance-overview');
        $response->assertJsonPath('data.present_today', 1)->assertJsonPath('data.absent_today', 1);
    }

    public function test_critical_alerts_detects_consecutive_absences(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['A', 'B', 'C']]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'Priscilla', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'female']);

        for ($i = 0; $i < 3; $i++) {
            $session = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'teacher_id' => $teacher->id, 'date' => now()->subDays(2 - $i)->toDateString(), 'topic_index' => $i]);
            Attendance::create(['session_id' => $session->id, 'student_id' => $student->id, 'status' => 'absent']);
        }

        $response = $this->getJson('/api/attendance-overview');
        $response->assertJsonCount(1, 'data.critical_alerts')
            ->assertJsonPath('data.critical_alerts.0.student_name', 'Priscilla')
            ->assertJsonPath('data.critical_alerts.0.consecutive_absences', 3);
    }
}
