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

class ModuleProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_module_progress(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro', 'Ch1', 'Ch2', 'Ch3', 'Ch4']]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        $s1 = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'teacher_id' => $teacher->id, 'date' => '2026-04-07', 'topic_index' => 0]);
        $s2 = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'teacher_id' => $teacher->id, 'date' => '2026-04-08', 'topic_index' => 1]);

        Attendance::create(['session_id' => $s1->id, 'student_id' => $student->id, 'status' => 'present']);
        Attendance::create(['session_id' => $s2->id, 'student_id' => $student->id, 'status' => 'present']);

        $response = $this->getJson("/api/modules/{$module->id}/progress");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['module', 'completion_rate', 'topics_taught', 'attendance_rate', 'class_breakdown', 'topic_attendance']])
            ->assertJsonPath('data.module.total_topics', 5)
            ->assertJsonPath('data.completion_rate', 40.0)
            ->assertJsonPath('data.topics_taught', 2)
            ->assertJsonPath('data.attendance_rate', 100.0)
            ->assertJsonPath('data.class_breakdown.0.class_name', 'Makarios');
    }

    public function test_returns_404_for_nonexistent_module(): void
    {
        $response = $this->getJson('/api/modules/999/progress');
        $response->assertStatus(404);
    }
}
